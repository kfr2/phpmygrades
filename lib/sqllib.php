<?php
/**
 * phpmygrades's SQL library
 *
 * \author Kevin Richardson <borismalcov@users.sourceforge.net>
 * \version $Id: sqllib.php,v 1.9 2006/07/19 19:54:53 borismalcov Exp $
 */


/**
 * connects to the sql server and selects the database
 */
function connect_sql()
{
	if(server_type == "mysql")
	{
		@mysql_connect(server, username, password) or die("Could not connect to SQL server.");
		@mysql_select_db(database) or die("Could not select database.");
	}
	
	elseif(server_type == "postgresql")
	{
		@pg_connect("host=" . server . " dbname=" . database . " user=" . username . " password=" . password);
	}
}

/**
 * closes connection to the sql server
 */
function disconnect_sql()
{
	if(server_type == "mysql"){ @mysql_close(); }
	elseif(server_type == "postgresql"){ @pg_close(); }
}

/**
 * queries the database and returns the result
 */
function query($stuff)
{
	if(server_type == "mysql"){ return(mysql_query($stuff)); }
	elseif(server_type == "postgresql"){ return(pq_query($stuff)); }
}


/**
 * returns the results as an object.
 *
 * Used like: while($row = result($information)){ print($row->name); }
 */
function result($stuff)
{
	if(server_type == "mysql"){ return(mysql_fetch_object($stuff)); }
	elseif(server_type == "postgresql"){ return(pg_fetch_object($stuff)); }
}

/**
 * used if you need to run a SQL command, like MAX()
 * \todo figure out some way around using this
 */
function command_result($stuff, $number)
{
	if(server_type == "mysql"){ return(mysql_result($stuff, $number)); }
	elseif(server_type == "postgresql"){ return(pg_result($stuff, $number)); }
}

/**
 * returns the number of rows a query produces
 */
function num_rows($stuff)
{
	if(server_type == "mysql"){ return(mysql_num_rows($stuff)); }
	elseif(server_type == "postgresql"){ return(pg_num_rows($stuff)); }
}

/**
 * escapes a function to protect (somewhat) against bad things (SQL injection, etc)
 */
function escape_string($stuff)
{
	return(mysql_escape_string($stuff));
}


/**
 * gets class $class_id's information
 *
 * Returns class name::teacher id::room::period::semesters
 */
function get_class_data($class_id)
{
	$class_data = "SELECT * FROM `classes` WHERE `ID`='$class_id'";
	$class_data = @query($class_data) or die("Error checking the database.");
	
	while($row = result($class_data))
	{
		$class_name = stripslashes($row->name);
		$teacher_id = $row->teacher;
		$room = stripslashes($row->room);
		$period = $row->period;
		$semester = stripslashes($row->semester);
		
		return($class_name . "::" . $teacher_id . "::" . $room . "::" . $period . "::" . $semester);
	}
}

/**
 * gets the list of students for class $class_id, separated with a comma
 *
 * \todo Eventually improve this so it parses the `classes` table
 */
function get_students($class_id)
{
	$students = "SELECT * FROM `students` WHERE `classes` LIKE '%" . $class_id . ",%'";
	$students = @query($students) or die("Error checking the database.");

	// can come up if the user is in the last class in the DB
	if(num_rows($students) == 0)
	{
		$students = "SELECT * FROM `students` WHERE `classes` LIKE '%" . $class_id . "%'";
		$students = @query($students) or die("Error checking the database.");
	}
		
	$list = "";
	
	while($row = result($students))
	{
		$classes = $row->classes;

		if(ereg("\[" . $class_id . ",", $classes) == TRUE OR ereg("," . $class_id . ",", $classes) == TRUE OR ereg("," . $class_id . "\]", $classes) == TRUE)
		{
			$user_id = $row->ID;
			$list .= $user_id . ",";
		}
	}
		
	// get rid of the last comma
	$list = substr($list, 0, strlen($list) - 1);
	return($list);
}

/**
 * gets $user's $number latest grades (from $class; "all" for all grades)
 * 
 * returns a string in the format of:
 * class id::assignment number::assignment name::date assigned::points possible::points scored
 * splits multiple grades with "--", sans quotes
 */
function get_latest_grades($user, $number, $class, $grading_period)
{
	connect_sql();
	
	$grades = "SELECT * FROM `grades` WHERE `student_ID`='$user' AND `grading_period`='$grading_period'";
	if($class != "all"){ $grades .= " AND `class_ID`='$class' "; }
	$grades .= "ORDER BY `date_assigned` DESC";
	if($number != "all"){ $grades .= " LIMIT {$number}"; }
	$grades = @query($grades) or die("Error getting grades from the database.");
	if(num_rows($grades) == 0){ return("No grades."); }
	
	$return = "";
	
	while($row = result($grades))
	{
		// do the stuff here
		$class_id = $row->class_id;
		$assign_number = $row->assign_number;
		$assign_name = stripslashes($row->assign_name);
		$date_assigned = $row->date_assigned;
		$points_possible = $row->points_possible;
		$points_scored = $row->points_scored;
		$grading_period = $row->grading_period;
		$comment = stripslashes($row->comment);
	
		$return .= "{$class_id}::{$assign_number}::{$assign_name}::{$date_assigned}::{$points_possible}::{$points_scored}::{$grading_period}::{$comment}--";
	}
	
	return($return);
	
	disconnect_sql();
}

/**
 * returns a list of the student's classes, each class separated by a comma
 *
 * (returns the first semester's classes first, then the second semester, etc..)
 */
function parse_class_list($student_id)
{
	connect_sql();

	$classes = query("SELECT `classes` FROM `students` WHERE `id`='$student_id' LIMIT 1") or die("Error fetching class list.");
	$result = result($classes);
	$class_list = $result->classes;
	
	$regex = "/[1-" . number_of_semesters . "]\{(...\[";
		
	for($j=1;$j<=number_of_periods;$j++)
	{
		$regex .= "[\d]*";
		if($j != number_of_periods){ $regex .= ","; }
	}
		
	$regex .= "\])*\}/";
	

	// break the entire string down into semester strings
	preg_match_all($regex, $class_list, $semester_class_list, PREG_PATTERN_ORDER);
	
	$return = "";
	
	for($i=0;$i<number_of_semesters;$i++)
	{
		// break it down into invididual days...
		for($j=1;$j<=5;$j++)
		{
			switch($j)
			{
				case 1: $day = "Mon"; break;
				case 2: $day = "Tue"; break;
				case 3: $day = "Wed"; break;
				case 4: $day = "Thu"; break;
				case 5: $day = "Fri"; break;
			}
			
			$regex = "/" . $day . "\[";
			for($k=1;$k<=number_of_periods;$k++)
			{
				$regex .= "([\d]*)";
				if($k != number_of_periods){ $regex .= ","; }
			}
			$regex .= "\]/";
			
			// use the regex here
			preg_match($regex, $semester_class_list[0][$i], $classes);

		/**
		 * <ul>			
		 * <li>$classes[0] is the entire string</li>
		 * <li>$classes[1] is the first class</li>
		 * <li>$classes[2] is the second class</li>
		 * <li>...etc</li>
		 * </ul>
		*/			
			
			for($m=1;$m<=number_of_periods;$m++)
			{
				$return .= $classes[$m] . ",";
			}
		}
	}
	
	return($return);
	
	disconnect_sql();
}


/**
 * uses parse_class_list() and returns classes user $user has for semester $semester
 *
 * returned string is a list of classes separated with commas
 */
function classes_by_semester($user, $semester)
{
	$class_list = parse_class_list($user);
	$classes = explode(",", $class_list);
	$classes_per_semester = number_of_periods * 5;
	
	$up_until = $classes_per_semester * $semester -1;
	$from = $classes_per_semester * ($semester - 1);
	
	$classes_displayed = "";
	$return = "";

	for($i=$from;$i<=$up_until;$i++)
	{
		// get the class
		$class_id = $classes[$i];
			
		// display the class only if it already hasn't been displayed
		if(strpos($classes_displayed, $class_id . ",") === FALSE)
		{
			//add it to the string (so it's not displayed again)
			$classes_displayed .= "{$class_id},";
		
			// add it to the string we'll return
			$return .= "{$class_id},";
		}
	}
	
	return($return);
}

/**
 * calculate a users's class average
 *
 * Takes category weights into effect; contact me if it's not working correctly.
 */
function class_average($user, $class, $the_grading_period)
{
	$total_points_scored = 0;
	$total_points_possible = 0;
	
	// get the class's categories
	$categories = return_categories($class);
	$categories = explode(":::::", $categories);
	
	foreach($categories as $the_part)
	{
		if($the_part != "")
		{
			list($categoryid, $categoryname) = explode(":", $the_part);
			
			// get the category's weight
			$info = @query("SELECT `weight` FROM `categories` WHERE `ID`='$categoryid' LIMIT 1");
			while($row = result($info))
			{
				$categoryweight = $row->weight;
				
				$total_category_scored = 0;
				$total_category_possible = 0;
				
				// get the assignments that belong to this category
				$points = @query("SELECT * FROM `grades` WHERE `student_ID`='$user' AND `class_id`='$class' AND `grading_period`='$the_grading_period' AND (`points_scored` != 'x' OR `points_scored` != 'X') AND `category`='$categoryid'") or die("Error.");
				while($row2 = result($points))
				{
					$total_category_scored += $row2->points_scored;
					$total_category_possible += $row2->points_possible;
				}
				
				// multiple the total category scores by the category's weight, and add to the overall total
				$total_category_scored = $total_category_scored * $categoryweight;
				$total_category_possible = $total_category_possible * $categoryweight;
				
				$total_points_scored += $total_category_scored;
				$total_points_possible += $total_category_possible;
			}
		}
	}
	
	if($total_points_possible == 0 AND $total_points_scored == 0)
	{
		$total_points_possible = 1;
		$total_points_scored = 1;
	}
	
	$average = $total_points_scored / $total_points_possible;
	// ..to make it a percent..
	$average = $average * 100;
	
	// round to the nearest hundredths
	$average = round($average, 2);
	
	return($average);
}

/**
 * The old version of class_average() that doesn't use weights.
 *
 * It has been left here as a backup (in case the other function isn't working correctly).
 * It returns $user's average for class $class (not: rounds to hundredths)
 */
/*
function old_class_average($user, $class, $the_grading_period)
{
	$total_points_scored = 0;
	$total_points_possible = 0;
	
	$points = @query("SELECT * FROM `grades` WHERE `student_ID`='$user' AND `class_id`='$class' AND `grading_period`='$the_grading_period' AND (`points_scored`!='x' OR `points_scored`!='X')") or die("Error.");
	while($row = result($points))
	{
		$points_scored = $row->points_scored;
		$points_possible = $row->points_possible;
		
		$total_points_scored = $total_points_scored + $points_scored;
		$total_points_possible = $total_points_possible + $points_possible;
	}
	
	if($total_points_possible == 0 AND $total_points_scored == 0)
	{
		$total_points_possible = 1;
		$total_points_scored = 1;
	}
	
	$average = $total_points_scored / $total_points_possible;
	// ..to make it a percent..
	$average = $average * 100;
	
	// round to the nearest hundredths
	$average = round($average, 2);
	
	return($average);
}
*/

/**
 * adds $absent_student into the `absences` table with a timestamp of `timestamp`
 */
function add_absence($absent_student, $timestamp)
{
	@query("INSERT INTO `absences` (`user_ID`,`timestamp`) VALUES ('$absent_student', '$timestamp')") or die("Error adding the user to the database.");
}

/**
 * Sees if a class has categories set up for it.
 *
 * returns TRUE if $class's teacher has at least once category set up; FALSE if he or she doesn't
 */
function class_has_categories($class)
{
	$categories = @query("SELECT * FROM `categories` WHERE `class`='$class'") or die("Error getting the class's categories.");
	if(num_rows($categories) == 0){ return FALSE; }
	else{ return TRUE; }
}

/**
 * returns $class's categories as a list
 * 
 * (in the format of "id:name:::::"; "" if no categories are present)
 */
function return_categories($class)
{
	$categories = @query("SELECT * FROM `categories` WHERE `class`='$class'") or die("Error getting the teacher's categories.");
	if(num_rows($categories) == 0){ return ""; }
	
	else
	{
		$to_return = "";
	
		while($row = result($categories))
		{
			$ID = $row->ID;
			$name = stripslashes($row->name);
			
			$to_return .= $ID . ":" . $name . ":::::";
		}
		
		return($to_return);
	}
}

?>
