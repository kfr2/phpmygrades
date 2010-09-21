<?php
/**
 * allows users to view students' grades
 *
 * \author Kevin Richardson <borismalcov@users.sourceforge.net>
 * \version $Id: classes.php,v 1.9 2006/07/19 19:54:52 borismalcov Exp $
 */

/**
 * call all the libraries
 */
include("lib/main.php");

if(isset($_GET['xml']))
{
	connect_sql();

	if(!isset($_GET['u']) || !isset($_GET['p'])){ cust_die("You need to submit a user ID and/or password string to view this XML feed."); }
	
	/**
	 * used to see if the user submitted a correct password
	 */
	$id = escape_string($_GET['u']);	if(is_numeric($id) == FALSE){ die("Don't mess with the ID."); }
	$pass = escape_string($_GET['p']);
	
	$real_pass = gen_rss_pass($id);
	if($real_pass != $pass){ cust_die("Incorrect password."); }
	
	if(!isset($_GET['c']))
	{
		rss_latest_grades($id, 10, "all");
	}
	
	else
	{
		$class = escape_string($_GET['c']);
		if(is_numeric($class) == FALSE){ die("Don't mess with that."); }
		
		$students = get_students($class);

		if($students == $id OR strpos($students, ",{$id}") != FALSE OR strpos($students, "{$id},") != FALSE)
		{
			rss_latest_grades($id, 10, $class);
		}
		else
		{
			die("You aren't in that class.");
		}
	}
	
	disconnect_sql();
	die();	
}


display_header("classes");

if(is_logged_in() == FALSE){ $_SESSION['not_this_page'] = 1; cust_die("You'll need to login to access the page you've requested."); }


display_menu();

print("<div class=\"container2\">");

if(isset($_GET['studentlist']))
{
	if(user_type() != "user")
	{
		if(!isset($_GET['id']) OR $_GET['id'] == "")
		{
			cust_die("Which class's student list would you like to view?  (You probably encountered a bug.)");
		}
		
		$class_id = escape_string($_GET['id']);
		if(is_numeric($class_id) == FALSE){ cust_die("That is not a valid class id."); }
		
		connect_sql();
		
		// see if it's a valid class
		$valid_class = @query("SELECT 1 FROM `classes` WHERE `ID`='$class_id'") or die("Error checking the database."); 
		if(num_rows($valid_class) == 0){ cust_die("That is not a valid class."); }
		
		// get the class name and period number
		$class_data = explode("::", get_class_data($class_id));
		$class_name = $class_data[0];
		$period = $class_data[3];
		$teacher = $class_data[1];
		$teacher2 = @query("SELECT `firstname`,`surname` FROM `users` WHERE `ID`='$teacher' LIMIT 1");
		while($row = result($teacher2))
		{
			$teacher = stripslashes($row->firstname) . " " . stripslashes($row->surname);
		}
		
		print("<div class=\"title\">Students in period {$period} {$class_name}, taught by {$teacher}</div>\n");
		print("<span id=\"namespan\"><table id=\"nametable\"><tr><th>name</th><th>average</th></tr>\n");

		$students = get_students($class_id);
		$student = explode(",", $students);
		foreach($student as $student_id)
		{
			if($student_id != "")
			{
				$the_studentID = @query("SELECT `studentID` FROM `students` WHERE `ID`='$student_id'") or die("Error checking the database.");
				while($result = result($the_studentID)){ $studentID = $result->studentID; }
				$name2 = @query("SELECT `firstname`, `surname` FROM `users` WHERE `ID`='$student_id'") or die("Error checking the database.");
				while($row = result($name2))
				{
					$name = stripslashes($row->firstname) . " " . stripslashes($row->surname);
					print("<tr><td><a href=\"messages.php?compose&amp;id={$student_id}\" title=\"send a message to {$name}\">{$name}</a></td><td>" . class_average($student_id, $class_id, current_semester) . "</td></tr>\n");
				}
			}
		}
		print("</table></span>\n");


		print("<span id=\"idspan\" style=\"display: none; \"><table id=\"idtable\">\n");
		print("<tr><th>ID</th><th>average</th></tr>\n");

                $students = get_students($class_id);
                $student = explode(",", $students);
                foreach($student as $student_id)
                {
                        if($student_id != "")
                        {
                                $the_studentID = @query("SELECT `studentID` FROM `students` WHERE `ID`='$student_id'") or die("Error checking the database.");
                                while($result = result($the_studentID))
				{
					$studentID = $result->studentID;

					print("<tr><td>{$studentID}</td><td>" . class_average($student_id, $class_id, current_semester) . "</td></tr>\n");
				}
			}
		}

		print("</table></span>\n");

		print("<div id=\"printlink\"><hr /><a href=\"javascript:window.print()\" title=\"print this page\">print</a></div>");
	}
}

elseif(isset($_GET['class']))
{
	if(user_type() == "user")
	{
		$requested_class = $_GET['class'];
		if(is_numeric($requested_class) != "true"){ cust_die("Invalid class."); }
	
		// see if they're in the class
		$class_list = classes_by_semester($_SESSION['id'], current_semester);
		$classes = explode(",", $class_list);
		
		// get rid of the empty part of the array
		$empty = count($classes) - 1;
		unset($classes[$empty]);
		
		foreach($classes as $class)
		{
			$could_be_in_class = 1;
			
			if(strpos($class, $requested_class) === false)
			{
				$could_be_in_class = 0;
			}
			else
			{
				break;
			}
		}
		
		if($could_be_in_class == 0){ cust_die("You are not in that class."); }
		
		
		// okay, they are in the class.  Let 'em do what they can do...
		
		// get the class's info
		$class_info = @query("SELECT * FROM `classes` WHERE `ID`='$requested_class' LIMIT 1") or die("Error getting the class's information.");
		while($row = result($class_info))
		{
			$class_name = stripslashes($row->name);
			$teacher = $row->teacher;
			
			// get the teacher's actual name
			$teacher_name = @query("SELECT `firstname`,`surname` FROM `users` WHERE `ID`='$teacher' LIMIT 1") or die("Error getting the teacher's name.");
			$result = result($teacher_name);
			$teacher_name = stripslashes($result->firstname) . " " . stripslashes($result->surname);
			
			$room = stripslashes($row->room);
			$period = $row->period;
			$semester = $row->semester;
			
			print("<div class=\"class_info\"><p>{$class_name}, taught by <a href=\"messages.php?compose&amp;id={$teacher}\" title=\"Send {$teacher_name} a message.\">{$teacher_name}</a>");
			if(enable_forums == 1){ print("<br /><a href=\"forum.php?id={$requested_class}\" title=\"class forum\">forum</a></p>"); }
			print("</p>");
			
			// check if the requested grading period is actually when the class takes places
			if(isset($_GET['gp']) && is_numeric($_GET['gp'])){ $grading_period = escape_string($_GET['gp']); }
			else{ $grading_period = current_semester; }
			
			// get the grading periods the class takes place in
			$classdata = get_class_data($requested_class);
			$classdata = explode("::", $classdata);

			$this_semester = current_semester;
			$other_semesters = "";

			$semesters = $classdata[4];
			$semesters = explode(",", $semesters);

			if($grading_period == current_semester)
			{
				$n = 0;
			
				while(isset($semesters[$n]))
				{
					if($semesters[$n] != "" AND $semesters[$n] != $this_semester)
					{
						$other_semesters .= "<a href=\"classes.php?class={$requested_class}&amp;gp={$semesters[$n]}\" title=\"assignments for grading period {$semesters[$n]}\">{$semesters[$n]}</a> ";
					}
					
					$n++;
				}
			}

			else
			{
				$n = 0;
			
				$this_semester = $grading_period;
				$other_semesters = "";
				
				while(isset($semesters[$n]))
				{
					if($semesters[$n] != $this_semester)
					{
						$other_semesters .= "<a href=\"classes.php?class={$requested_class}&amp;gp={$semesters[$n]}\" title=\"assignments for grading period {$semesters[$n]}\">{$semesters[$n]}</a> ";
					}
					
					$n++;
				}
			}	
			
			print("<p>Current Grading Period: {$this_semester}<br />Other Grading Periods: {$other_semesters}</p>");
			
			print("<p>Average: " . class_average($_SESSION['id'], $requested_class, $grading_period) . "%</p></div>");
		}
		
		// get his or her assignments
		$list_of_grades = get_latest_grades($_SESSION['id'], "all", $requested_class, $grading_period) or die("Error getting your grades.");
		if($list_of_grades == "No grades."){ print("<br />You currently have no grades for this class."); die(); }

		$grades = explode("--", $list_of_grades);
		
		
		// for alternating colours
		$colour = 0;
		
		foreach($grades as $grade)
		{
			if($grade != "")
			{
				list($class_id, $assignment_number, $assignment_name, $date_assigned, $points_possible, $points_scored, $grading_period, $comment) = split("::", $grade);
				print("<div class=\"grades");
				if($colour == 0){ print("0"); $colour = 1; } else{ print("1"); $colour = 0; }
				print("\">{$assignment_name} -- {$points_scored}/{$points_possible} = " . round($points_scored/$points_possible*100,2) . "%");
				
				if($comment != ""){ print("&nbsp;|&nbsp;Comment: <em>{$comment}</em>"); }

				print("</div>");
			}
		}

		print("<span id=\"idspan\"></span><span id=\"namespan\"></span>");
		print("<div id=\"printlink\"><hr /><a href=\"javascript:window.print()\" title=\"print this page\">print</a></div>");
		
	}
	
	elseif(user_type() == "parent")
	{
		$parentID = $_SESSION['id'];
	
		connect_sql();
		
		// get his or her students, and print grades for each
		$students = @query("SELECT `students` FROM `parents` WHERE `parent_ID`='$parentID'") or die("Error checking the database.");
		while($row = result($students))
		{
			$student = $row->students;
			$student = explode(",", $student);
			
			$i = 0;
			
			foreach($student as $the_student)
			{
				// get his or her name
				$student_name = @query("SELECT `firstname`, `surname` FROM `users` WHERE `ID`='$the_student' LIMIT 1") or die("Error checking the database.");
				while($row2 = result($student_name)){ $students_name = stripslashes($row2->firstname) . " " . stripslashes($row2->surname); }
				
				print("<p class=\"title\">{$students_name}</p>");
			
				$requested_class = $_GET['class'];
				if(is_numeric($requested_class) != "true"){ cust_die("Invalid class."); }
	
				// see if they're in the class
				$class_list = classes_by_semester($the_student, current_semester);
				$classes = explode(",", $class_list);
		
				// get rid of the empty part of the array
				$empty = count($classes) - 1;
				unset($classes[$empty]);
		
				foreach($classes as $class)
				{
					$could_be_in_class = 1;
							
					if(strpos($class, $requested_class) === false)
					{
						$could_be_in_class = 0;
					}
					else
					{
						break;
					}
				}
		
				if($could_be_in_class == 0){ cust_die("Your student is not in that class."); }
		
		
				// okay, they are in the class.  Let 'em do what they can do...
		
				// get the class's info
				$class_info = @query("SELECT * FROM `classes` WHERE `ID`='$requested_class' LIMIT 1") or die("Error getting the class's information.");
				while($row = result($class_info))
				{
					$class_name = stripslashes($row->name);
					$teacher = $row->teacher;
			
					// get the teacher's actual name
					$teacher_name = @query("SELECT `firstname`,`surname` FROM `users` WHERE `ID`='$teacher' LIMIT 1") or die("Error getting the teacher's name.");
					$result = result($teacher_name);
					$teacher_name = stripslashes($result->firstname) . " " . stripslashes($result->surname);
			
					$room = stripslashes($row->room);
					$period = $row->period;
					$semester = $row->semester;
			
					print("<div class=\"class_info\"><p>{$class_name}, taught by <a href=\"messages.php?compose&amp;id={$teacher}\" title=\"Send {$teacher_name} a message.\">{$teacher_name}</a>");
					print("</p>");
			
					// check if the requested grading period is actually when the class takes places
					if(isset($_GET['gp']) && is_numeric($_GET['gp'])){ $grading_period = escape_string($_GET['gp']); }
					else{ $grading_period = current_semester; }
			
					// get the grading periods the class takes place in
					$classdata = get_class_data($requested_class);
					$classdata = explode(",", $classdata);

					if($grading_period == current_semester)
					{
						$this_semester = $classdata[4];
			
						$n = 5;
						$other_semesters = "";
			
						while(isset($classdata[$n]))
						{
							$other_semesters .= "<a href=\"classes.php?class={$requested_class}&amp;gp={$classdata[$n]}\" title=\"assignments for grading period {$classdata[$n]}\">{$classdata[$n]}</a> ";
							$n++;
						}
					}
					else
					{
						$this_semester = $grading_period;
						$n = 4;
						$other_semesters = "";
						while(isset($classdata[$n]))
						{
							if($classdata[$n] != $this_semester)
							{
								$other_semesters .= "<a href=\"classes.php?class={$requested_class}&amp;gp={$classdata[$n]}\" title=\"assignments for grading period {$classdata[$n]}\">{$classdata[$n]}</a> ";
							}
							$n++;
						}
					}	
			
					print("<p>Current Grading Period: {$this_semester}<br />Other Grading Periods: {$other_semesters}</p>");
			
					print("<p>Average: " . class_average($the_student, $requested_class, $grading_period) . "%</p></div>");
				}
		
				// get his or her assignments
				$list_of_grades = get_latest_grades($the_student, "all", $requested_class, $grading_period) or die("Error getting your grades.");
				if($list_of_grades == "No grades."){ print("<br />You currently have no grades for this class."); die(); }

				$grades = explode("--", $list_of_grades);
		
				// for alternating colours
				$colour = 0;
		
				foreach($grades as $grade)
				{
					if($grade != "")
					{
						list($class_id, $assignment_number, $assignment_name, $date_assigned, $points_possible, $points_scored, $grading_period, $comment) = split("::", $grade);
						print("<div class=\"grades");
						if($colour == 0){ print("0"); $colour = 1; } else{ print("1"); $colour = 0; }
						print("\">{$assignment_name} -- {$points_scored}/{$points_possible} = " . round($points_scored/$points_possible*100,2) . "%");
				
						if($comment != ""){ print("&nbsp;|&nbsp;Comment: <em>{$comment}</em>"); }

						print("</div>");
					}
				}
				
				$i++;
			
				// if we have more users to print, print a line
				if(isset($student[$i]))
				{
					print("<hr />");
				}
			}
		}
		
		disconnect_sql();
	}
	
	else
	{
		print("Apparently there is nothing for you here.");
	}
}

else
{

	any_errors();

	if(user_type() == "admin")
	{
		print("A list of all the classes, plus administration options for them, will (eventually) be displayed here.");
	}
	
	elseif(user_type() == "teacher")
	{
		
		print("<table style=\"width: 100%;\">\n<tr><th>Grading Period(s)</th><th>Period</th><th>Class Name</th></tr>");

		$id = $_SESSION['id'];

		connect_sql();
	
		$classes = @query("SELECT * FROM `classes` WHERE `teacher`='$id' ORDER BY `period`") or die("Error getting your list of classes.");
		while($row = result($classes))
		{
			$name = stripslashes($row->name);
			$period = $row->period;
			$class_id = $row->ID;
			$semesters = $row->semester;
		
			print("<tr><td>{$semesters}</td><td>{$period}</td><td>{$name}</td><td style=\"border-right: 1px dotted #000000;\"><a href=\"assignment.php?add&amp;id={$class_id}\" title=\"add an assignment\">add</a>:<a href=\"assignment.php?edit&amp;id={$class_id}\" title=\"edit an assignment\">edit</a>:<a href=\"assignment.php?view&amp;id={$class_id}\" title=\"view assignments\">view</a> an assignment</a></td>");
			if(enable_forums == 1){ print("<td style=\"border-right: 1px dotted #000000;\"><a href=\"forum.php?id={$class_id}\" title=\"class forum\">forum</a></td>"); }
			print("<td style=\"border-right: 1px dotted #000000;\"><a href=\"classes.php?studentlist&amp;id={$class_id}\" title=\"student list\" target=\"_blank\" onClick=\"window.open('classes.php?studentlist&amp;id={$class_id}','class list','width=650,height=400'); return false;\">student list</a></td><td><a href=\"category.php?teacherid={$_SESSION['id']}&classid={$class_id}\" title=\"categories\">categories</a></td></tr>");
		}
	
		disconnect_sql();

		print("</table>");
	}
	
	elseif(user_type() == "user")
	{
		print_students_classes($_SESSION['id'], current_semester);
	}

}

print("</div>");

display_copyright();
display_footer();

?>
