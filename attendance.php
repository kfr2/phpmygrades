<?php
/**
 * the attendance tracking script
 *
 * \author Kevin Richardson <borismalcov@users.sourceforge.net>
 * \version $Id: attendance.php,v 1.6 2006/07/19 19:54:52 borismalcov Exp $
 * \todo turn some of the following into functions; a lot of it is reused a lot
 */

include("lib/main.php");

if(track_attendance == 0){ $_SESSION['not_this_page'] = 1; cust_die("The attendance features are currently disabled by the administrator."); }

display_header("attendance");

display_menu();
print("<div class=\"container2\">");

if(user_type() == "user")
{
	// I suppose we can show 'em the days they were absent on...
	$ID = $_SESSION['id'];

	connect_sql();

	$absences = @query("SELECT * FROM `absences` WHERE `user_ID`='$ID'");
	$number_of_absences = num_rows($absences);

	print("<table id=\"absencestable\">\n");
	print("<tr><th>You have been absent {$number_of_absences} time");
	if($number_of_absences != 1){ print("s"); }
	if($number_of_absences != 0){ print(":"); }
	print("</th></tr>\n");

	if($number_of_absences != 0)
	{
		$tdcolour = 0;

		while($row = result($absences))
		{
			$timestamp = $row->timestamp;
			$date = date(dateformat, $timestamp);

			print("<tr class=\"tdcolour{$tdcolour}\"><td>{$date}</td></tr>\n");

			if($tdcolour == 0){ $tdcolour++; }
			else{ $tdcolour = 0; }
		}
	}

	print("</table>\n");

	disconnect_sql();
	
	
	print("</div>");
	display_copyright();
	display_footer();
	
	die();
}

elseif(user_type() == "parent")
{
	connect_sql();
	
	$parentID = $_SESSION['id'];
	
	// see which students the parent is a parent of, and print info about their grades.
	// the following will eventually be turned into a function
	$students = @query("SELECT `students` FROM `parents` WHERE `parent_ID`='$parentID'") or die("Error checking the database.");
	while($row = result($students))
	{
		$student = explode(",", $row->students);
			
		$i = 0;
		foreach($student as $the_student)
		{
		
			// get his or her name
			$student_name = @query("SELECT `firstname`, `surname` FROM `users` WHERE `ID`='$the_student' LIMIT 1") or die("Error checking the database.");
			while($row2 = result($student_name)){ $students_name = stripslashes($row2->firstname) . " " . stripslashes($row2->surname); }
	
			$absences = @query("SELECT * FROM `absences` WHERE `user_ID`='$the_student'");
			$number_of_absences = num_rows($absences);

			print("<table id=\"absencestable\">\n");
			print("<tr><th>{$students_name} has been absent {$number_of_absences} time");
			if($number_of_absences != 1){ print("s"); }
			if($number_of_absences != 0){ print(":"); }
			print("</th></tr>\n");

			if($number_of_absences != 0)
			{
				$tdcolour = 0;

				while($row = result($absences))
				{
					$timestamp = $row->timestamp;
					$date = date(dateformat, $timestamp);

					print("<tr class=\"tdcolour{$tdcolour}\"><td>{$date}</td></tr>\n");

					if($tdcolour == 0){ $tdcolour++; }
					else{ $tdcolour = 0; }
				}
			}
			
			print("</table>\n");
			
			$i++;
			
			// if we have more users to print, print a line
			if(isset($student[$i]))
			{
				print("<hr />");
			}
		}
	}
	
	print("</div>");
	display_copyright();
	display_footer();
	
	die();
}

// prints the forms for adding a user
if(isset($_GET['add']))
{
	connect_sql();

	if(user_type() == "admin")
	{
		// this would be a very nice place to DHTMLisize;
		// it would also be handy to implement a search feature
		// (instead of displaying one big list)

		print("<table id=\"absentstudents\"><form id=\"absenceform\" action=\"attendance.php\" method=\"post\">\n<tr><th>Absent person/people:</th></tr>\n");
		print("<tr><td><select name=\"selectabsences\">\n");

		$tdcolour = 0;

		// get the student/teacher list
		$list = @query("SELECT * FROM `users` WHERE `type`='1' OR `type`='2' ORDER BY `surname`, `firstname`") or die("Error retrieving student information.");
		while($row = result($list))
		{
			$ID = $row->ID;
			$name = stripslashes($row->surname) . ", " . stripslashes($row->firstname);

			// get the user's student ID number (if available)
			if($row->type == 1)
			{
				$student_info = @query("SELECT * FROM `students` WHERE `ID`='$ID' LIMIT 1") or die("Error retrieving student information.");
				while($row2 = result($student_info))
				{
					$student_ID = stripslashes($row2->studentID);
					print("<option value=\"{$ID}\" class=\"tdcolour{$tdcolour}\">{$name} -- {$student_ID}\n");
				}
			}
			
			else
			{
				print("<option value=\"{$ID}\" class=\"tdcolour{$tdcolour}\">{$name} -- teacher\n");
			}
			
			
			if($tdcolour == 0){ $tdcolour++; }
			else{ $tdcolour = 0; }
		}

		
		print("</select></td>");

		// eventually...
		// print("<td><a href=\"javascript:void(0);\" onClick=\"add_another_absence()\" title=\"add\">add</a></td>");

		print("</tr>\n");
//		print("<tr><td><hr /></td></tr>");
// we could use javascript to keep track of the already added users here
//		print("<tr><td><hr /></td></tr>");

		print("<tr><td><input type=\"submit\" name=\"addabsences\" value=\"add absence\" /></td></tr>\n</form></table>\n");
	}

	// allows teachers to add students from one of their classes
	elseif(user_type() == "teacher")
	{
		connect_sql();
	
		$teacher_id = $_SESSION['id'];	
		$student_list = "";
		
		// get the teacher's classes
		$classes = @query("SELECT `ID` FROM `classes` WHERE `teacher`='$teacher_id'") or die("Error getting the class list from the database.");
		while($row = result($classes))
		{
			// get the students in those classes..
			if(get_students($row->ID) != "")
			{
				$student_list .= get_students($row->ID) . ",";
			}
		}
		
		// remove the last comma
		$student_list = substr($student_list, 0, strlen($student_list) - 1);

		$students = explode(",", $student_list);

		// eventually we will need to remove duplicate entries from the array (should a teacher teach a student in more than one subject)
		
		$surnames = array();
		$firstnames = array();
		$studentIDs = array();
		
		// get the students' names
		foreach($students as $student)
		{
			$name = @query("SELECT `surname`, `firstname` FROM `users` WHERE `ID`='$student' LIMIT 1") or die("Error getting the user's name.");
			while($info = result($name))
			{
				// get the students' school ID number
				$user_info = @query("SELECT `studentID` FROM `students` WHERE `ID`='$student' LIMIT 1") or die("Error getting the user's ID number.");
				while($row2 = result($user_info))
				{
					// finally!  Add 'em to the arrays
					$surnames[$student] = stripslashes($info->surname);
					$firstnames[$student] = stripslashes($info->firstname);
					$studentIDs[$student] = stripslashes($row2->studentID);
				}
			}			
		}
		

		// sort the array (by the users' surnames, and [eventually] their firstnames)
		asort($surnames);
		asort($firstnames);
		
		// 'k, it's all good:  print the form.
		
		// this would be a very nice place to DHTMLisize;
		// it would also be handy to implement a search feature
		// (instead of displaying one big list)

		print("<table id=\"absentstudents\"><form id=\"absenceform\" action=\"attendance.php\" method=\"post\">\n<tr><th>Absent student(s):</th></tr>\n");
		print("<tr><td><select name=\"selectabsences\">\n");

		$tdcolour = 0;

		foreach($surnames as $key => $surname)
		{
			// get the user's firstname using $key
			$firstname = $firstnames[$key];

			// get his or her student ID
			$studentID = $studentIDs[$key];
			
			print("<option value=\"{$key}\" class=\"tdcolour{$tdcolour}\">{$surname}, {$firstname} -- {$studentID}\n");
			
			if($tdcolour == 0){ $tdcolour++; }
			else{ $tdcolour = 0; }
		}
		
		print("</select></td>");

		// eventually...
		// print("<td><a href=\"javascript:void(0);\" onClick=\"add_another_absence()\" title=\"add\">add</a></td>");

		print("</tr>\n");
//		print("<tr><td><hr /></td></tr>");
// we could use javascript to keep track of the already added users here
//		print("<tr><td><hr /></td></tr>");

		print("<tr><td><input type=\"submit\" name=\"addabsences\" value=\"add absence\" /></td></tr>\n</form></table>\n");


		disconnect_sql();
	}

	else
	{
		cust_die("You aren't allowed to view this page.");
	}

	disconnect_sql();
}

// allows admins and teachers to view absences for the current day, show 
// how many times a user was absent, etc
//
// might as well allow 'em to view all students (even if a teacher doesn't teach him or her)
elseif(isset($_GET['view']))
{
	if(user_type() == "user"){ cust_die("You may not access this page."); }
	
	connect_sql();
	
	// if the user wants to see data on a specific user...
	if(isset($_GET['id']))
	{
		if(!is_numeric($_GET['id'])){ cust_die("Invalid ID number."); }
		$requested_ID = escape_string(htmlspecialchars($_GET['id']));
		
		connect_sql();
		
		// see if the requested ID is a user or not...
		if(is_valid_user($requested_ID) == FALSE){ cust_die("Invalid ID number."); }
		
		$user_info = @query("SELECT `surname`, `firstname` FROM `users` WHERE `ID`='$requested_ID' LIMIT 1") or die("Error getting information the database.");
		while($row = result($user_info))
		{
			$name = stripslashes($row->firstname) . " " . stripslashes($row->surname);
		}
		
		if(user_type($requested_ID) == "user")
		{
			$user_info = @query("SELECT `studentID` FROM `students` WHERE `ID`='$requested_ID' LIMIT 1") or die("Error getting information from the database.");
			while($row2 = result($user_info))
			{
				$studentID = $row2->studentID;
			}
		}
		
		else
		{
			$studentID = "teacher";
		}
		
		$times_absent = @query("SELECT * FROM `absences` WHERE `user_ID`='$requested_ID'") or die("Error getting information from the database.");
		$number_of_times_absent = num_rows($times_absent);
		
		print("<table id=\"absenceinformation\">\n");
		print("<tr><th>{$name} ({$studentID}) has been absent {$number_of_times_absent} time");
		if($number_of_times_absent != 1){ print("s"); }
		print("</th></tr>\n");

		if($number_of_times_absent != 0)
		{

			$tdcolour = 0;

			while($row = result($times_absent))
			{
				$timestamp = $row->timestamp;
			
				$date = date(dateformat, $timestamp);
		
				print("<tr class=\"tdcolour{$tdcolour}\"><td>{$date}</td></tr>\n");
		
				if($tdcolour == 0){ $tdcolour++; }
				else{ $tdcolour = 0; }
			}
		}

		print("</table>\n");
			
		disconnect_sql();
	}

	// if data from a specific user isn't requested...
	else
	{
		// it would be very easy to add more options later
		// examples include 'number this semester', etc.  This will be implemented when the number of days per semester, etc. feature is added
	
		// used to see who's absent today
		$today = date("d F Y");
		$todays_timestamp = strtotime($today);
		
		$todays_absences_list = "";
		$todays_absences = @query("SELECT `user_ID` FROM `absences` WHERE `timestamp` >= '$todays_timestamp'") or die("Error getting the absences from the database.");
		$number_today = num_rows($todays_absences);

		// the arrays will make it easy to sort by name
		$absences_array = array();
		
		$surname_array = array();
		$firstname_array = array();
		$studentID_array = array();

		while($row = result($todays_absences))
		{
			$user_ID = $row->user_ID;

			// add 'em to the array (if they aren't already in it)
			if(!in_array($user_ID, $absences_array)){ $absences_array[] = $user_ID; }
		}
		
		// get the user's info and add to the array
		
		foreach($absences_array as $key)
		{
			$name = @query("SELECT `surname`, `firstname`, `type` FROM `users` WHERE `ID`='$key' LIMIT 1") or die("Error checking the database.");
			while($row = result($name))
			{
				$surname_array[$key] = stripslashes($row->surname);
				$firstname_array[$key] = stripslashes($row->firstname);
				
				if($row->type == 1)
				{
					$user_info = @query("SELECT studentID FROM `students` WHERE `ID`='$key' LIMIT 1") or die("Error checking the database.");
					while($row2 = result($user_info))
					{
						$studentID_array[$key] = $row2->studentID;
					}
				}
				
				else
				{
					$studentID_array[$key] = "teacher";
				}
			}
		}
		
		// sort 'em
		asort($surname_array);
		asort($firstname_array);
		
		// 'k, print the data
		
		$tdcolour = 0;
		
		foreach($surname_array as $key => $surname)
		{
			$todays_absences_list .= "<tr class=\"tdcolour{$tdcolour}\"><td>{$surname}</td><td>$firstname_array[$key]</td><td>$studentID_array[$key]</td><td><a href=\"attendance.php?view&id={$key}\" title=\"more information\">information</a></td></tr>\n";
			
			if($tdcolour == 0){ $tdcolour++; }
			else{ $tdcolour = 0; }
		}

		
		// get the number of all absences
		$all_absences_list = "";
		$all_absences = @query("SELECT `user_ID` FROM `absences`") or die("Error getting the absences from the database.");
		$number_all = num_rows($all_absences);

		// the arrays will make it easy to sort by name
		$absences_array = array();
		
		$surname_array = array();
		$firstname_array = array();
		$studentID_array = array();

		while($row = result($all_absences))
		{
			$user_ID = $row->user_ID;

			// add 'em to the array (if they aren't already in it)
			if(!in_array($user_ID, $absences_array)){ $absences_array[] = $user_ID; }
		}
		
		// get the user's info and add to the array
		
		foreach($absences_array as $key)
		{
			$name = @query("SELECT `surname`, `firstname`, `type` FROM `users` WHERE `ID`='$key' LIMIT 1") or die("Error checking the database.");
			while($row = result($name))
			{
				$surname_array[$key] = stripslashes($row->surname);
				$firstname_array[$key] = stripslashes($row->firstname);
				
				if($row->type == 1)
				{
					$user_info = @query("SELECT studentID FROM `students` WHERE `ID`='$key' LIMIT 1") or die("Error checking the database.");
					while($row2 = result($user_info))
					{
						$studentID_array[$key] = $row2->studentID;
					}
				}
				
				else
				{
					$studentID_array[$key] = "teacher";
				}
			}
		}
		
		// sort 'em
		asort($surname_array);
		asort($firstname_array);
		
		// 'k, print the data
		
		$tdcolour = 0;
		
		foreach($surname_array as $key => $surname)
		{
			$all_absences_list .= "<tr class=\"tdcolour{$tdcolour}\"><td>{$surname}</td><td>$firstname_array[$key]</td><td>$studentID_array[$key]</td><td><a href=\"attendance.php?view&id={$key}\" title=\"more information\">information</a></td></tr>\n";
			
			if($tdcolour == 0){ $tdcolour++; }
			else{ $tdcolour = 0; }
		}
		
		
		print("<table id=\"absencetable\">\n");
		print("<tr><th colspan=\"2\">Number of absences...</th></tr>\n");
		print("<tr><td><a href=\"javascript:void(0);\" onClick=\"toggle_display('todayspan')\" title=\"today\">today</a></td><td>{$number_today}</td></tr>\n");
		print("<tr><td><a href=\"javascript:void(0);\" onClick=\"toggle_display('allyearspan')\" title=\"all year\">all year</a></td><td>{$number_all}</td></tr>\n");
		print("</table>\n");
		
		// for the nice dhtml effects...
		
		// today's table
		print("<span id=\"todayspan\" style=\"display: none;\">\n");
		print("<table id=\"todaytable\">\n");
		print("<tr><td colspan=\"4\"><hr /></td></tr>\n");
		print("<tr><th colspan=\"4\">Today's Absences...</th></tr>\n");
		print("<tr><td colspan=\"4\">&nbsp;</td></tr>\n");
		print("<tr><th>surname</th><th>first name</th><th>ID</th><th></th></tr>\n");
		print($todays_absences_list);
		print("</table>\n");
		print("</span>\n");
		
		print("<span id=\"allyearspan\" style=\"display: none;\">\n");
		print("<table id=\"allyeartable\">\n");
		print("<tr><td colspan=\"4\"><hr /></td></tr>\n");
		print("<tr><th colspan=\"4\">This Year's Absences...</th></tr>\n");
		print("<tr><td colspan=\"4\">&nbsp;</td></tr>\n");
		print("<tr><th>surname</th><th>first name</th><th>ID</th><th></th></tr>\n");
		print($all_absences_list);
		print("</table>\n");
		print("</span>\n");
		
	}
	
	disconnect_sql();
}


// allows teachers and admins to add an absence
elseif(isset($_POST['addabsences']))
{
	if(user_type() == "admin")
	{
		if(!isset($_POST['selectabsences']) OR $_POST['selectabsences'] == "" OR is_numeric($_POST['selectabsences']) == FALSE){ cust_die("You must submit the student who is absent."); }
		$absent_student = escape_string(htmlspecialchars($_POST['selectabsences']));

		// get the timestamp; it's used to see if the user has already been added for the day, and adding 'em if they haven't been
		$timestamp = time();

		connect_sql();

		// has the user been added?
		$latest_absence = @query("SELECT `timestamp` FROM `absences` WHERE `user_ID`='$absent_student' ORDER BY `timestamp` DESC LIMIT 1") or die("Error checking the database.");
		if(num_rows($latest_absence) != 0)
		{
			while($row = result($latest_absence))
			{
				$old_timestamp = $row->timestamp;

				// generate the date from the timestamp; compare this to today's date
				$old_date = date("dMY", $old_timestamp);
				$new_date = date("dMY", $timestamp);

				// if the dates don't match, add the user
				if($old_date != $new_date){ add_absence($absent_student, $timestamp); }
			}
		}

		// the user has no absences; add him or her
		else{ add_absence($absent_student, $timestamp); }

		disconnect_sql();

		print("Done.  <a href=\"attendance.php?add\" title=\"add another\">Add another</a>?");
	}

	// see if the student is in his or her class
	elseif(user_type() == "teacher")
	{
		if(!isset($_POST['selectabsences']) OR $_POST['selectabsences'] == "" OR is_numeric($_POST['selectabsences']) == FALSE){ cust_die("You must submit the student who is absent."); }
		$absent_student = escape_string(htmlspecialchars($_POST['selectabsences']));

		// see if the teacher teachers the user
		
		connect_sql();
	
		$teacher_id = $_SESSION['id'];	
		$student_list = "";
		
		// get the teacher's classes
		$classes = @query("SELECT `ID` FROM `classes` WHERE `teacher`='$teacher_id'") or die("Error getting the class list from the database.");
		while($row = result($classes))
		{
			// get the students in those classes..
			if(get_students($row->ID) != "")
			{
				$student_list .= get_students($row->ID) . ",";
			}
		}
		
		// remove the last comma
		$student_list = substr($student_list, 0, strlen($student_list) - 1);

		$students = explode(",", $student_list);
		
		if(!in_array($absent_student, $students)){ cust_die("You are not allowed to mark that student absent because you do not teach him or her."); }

		// okay, they're allowed to mark the student absent...
		
		// get the timestamp; it's used to see if the user has already been added for the day, and adding 'em if they haven't been
		$timestamp = time();
		
		// has the user been added?
		$latest_absence = @query("SELECT `timestamp` FROM `absences` WHERE `user_ID`='$absent_student' ORDER BY `timestamp` DESC LIMIT 1") or die("Error checking the database.");
		if(num_rows($latest_absence) != 0)
		{
			while($row = result($latest_absence))
			{
				$old_timestamp = $row->timestamp;

				// generate the date from the timestamp; compare this to today's date
				$old_date = date("dMY", $old_timestamp);
				$new_date = date("dMY", $timestamp);

				// if the dates don't match, add the user
				if($old_date != $new_date){ add_absence($absent_student, $timestamp); }
			}
		}

		// the user has no absences; add him or her
		else{ add_absence($absent_student, $timestamp); }
		
		disconnect_sql();

		print("Done.  <a href=\"attendance.php?add\" title=\"add another\">Add another</a>?");
	}

	else
	{
		cust_die("You may not view this page.");
	}
}

else
{
	any_errors();

	print("Would you like to <a href=\"attendance.php?add\" title=\"add absences\" accesskey=\"s\">a<em>d</em>d absences</a> or <a href=\"attendance.php?view\" title=\"view absences\" accesskey=\"d\">view ab<em>s</em>ences</a>?");
}

print("</div>");

display_copyright();
display_footer();

?>
