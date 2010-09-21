<?php
/**
 * allows teachers to add, edit, and remove assignments
 *
 * \author Kevin Richardson <borismalcov@users.sourceforge.net>
 * \version $Id: assignment.php,v 1.9 2006/07/19 19:54:52 borismalcov Exp $
 */


include("lib/main.php");

display_header("assignments");

if(is_logged_in() == FALSE){ $_SESSION['not_this_page'] = 1; cust_die("You'll need to login to access the page you've requested."); }


display_menu();

print("<div class=\"container2\">");


if(isset($_GET['add']))
{
	if(user_type() != "teacher"){ cust_die("You may not access this page."); }
	if(!isset($_GET['id']) OR $_GET['id'] == "" OR is_numeric($_GET['id'] == FALSE)){ cust_die("Which class would you like to add an assignment to?  Make sure you follow the correct links."); }
	
	// see if the class is valid, and if so, if user may access the class
	connect_sql();
	
	$class_id = escape_string($_GET['id']);
	
	// see if it's a valid class
	$valid_class = @query("SELECT 1 FROM `classes` WHERE `ID`='$class_id'") or die("Error checking the database."); 
	if(num_rows($valid_class) == 0){ cust_die("That is not a valid class."); }
	
	if(teacher_teaches($_SESSION['id'], $class_id) == FALSE){ cust_die("You may not add grades to that class."); }

	print("<table><form action=\"assignment.php\" method=\"post\" name=\"add_grade\" id=\"add_grade\">\n");
	print("<tr><th>Assignment name:</th><td><input type=\"text\" name=\"assignment_name\" maxlength=\"75\" /></td></tr>\n");
	print("<tr><th>Assignment description:</th><td><input type=\"text\" name=\"description\" maxlength=\"255\" /></td></tr>");
	print("<tr><th>Date assigned:</th><td><input type=\"radio\" name=\"assigned\" value=\"today\" checked>Today</td>\n");
	print("<td><input type=\"radio\" name=\"assigned\" value=\"custom\" onClick=\"populate('0');\">Other:</td>\n");
	print("<td><select name=\"month\" onChange=\"populate(this.selectedIndex);\"><option value=\"01\">January<option value=\"02\">February<option value=\"03\">March<option value=\"04\">April<option value=\"05\">May<option value=\"06\">June<option value=\"07\">July<option value=\"08\">August<option value=\"09\">September<option value=\"10\">October<option value=\"11\">November<option value=\"12\">December</select></td><td><select name=\"day\"><option></option></select></td><td><input type=\"text\" name=\"year\" maxlength=\"4\" value=\"" . date("Y") . "\" /></td></tr>\n");
	print("<tr><th>Category:</th><td>");
	
	// if teacher has categories set up, list 'em.  Else, print a link to set up categories.
	if(class_has_categories($class_id) == TRUE)
	{
		print("<select name=\"category\">\n");
	
		$categories = return_categories($class_id);
		$categories = explode(":::::", $categories);

		$tdcolour = 0;
		
		foreach($categories as $part)
		{
			if($part != "")
			{
				list($id, $category) = explode(":", $part);
				
				print("<option value=\"{$id}\" class=\"tdcolour{$tdcolour}\">{$category}\n");
			}

			if($tdcolour == 0){ $tdcolour++; }
			else{ $tdcolour = 0; }
		}
		
		print("</select>");
	}
	
	else
	{
		print("<a href=\"category.php?teacherid={$_SESSION['id']}&classid={$class_id}\" title=\"create categories\">create</a>\n");
	}

	print("</td></tr>\n");
	
	print("<tr><th>Points possible:</th><td><input type=\"text\" name=\"points_possible\" /></td></tr>");
	print("<tr><td colspan=\"2\"><hr /></td></tr>\n");
	print("<tr><th>Student Name/ID</th><th>Points Scored</th><th>comment</th></tr>\n");
	print("<tr><td></td><td><div class=\"small\"><em>\"x\" if missing the assignment</em></div></td><td></td>");
	
	// get the list of students
	$students = explode(",", get_students($class_id));
	foreach($students as $student)
	{
		// get user's name
		$student_name2 = @query("SELECT `firstname`, `surname` FROM `users` WHERE `ID`='$student'") or die("Error getting information from the database.");
		while($row = result($student_name2))
		{
			$student_name = stripslashes($row->firstname) . " " . stripslashes($row->surname);
			
			$info = @query("SELECT `studentID` FROM `students` WHERE `ID`='$student' LIMIT 1") or die("Error checking the database.");
			while($row2 = result($info))
			{
				print("<tr><td>{$student_name} -- " . $row2->studentID . "</td><td><input type=\"text\" name=\"{$student}_scored\" /></td><td><input type=\"text\" name=\"{$student}_comment\" size=\"15\" maxlength=\"255\" /></td></tr>\n");
			}
		}
	}
	
	disconnect_sql();
	
	print("<tr><td><input type=\"hidden\" name=\"classid\" value=\"{$class_id}\" /><input type=\"submit\" name=\"addgrade\" value=\"add the assignment\" /></td></tr></form></table>\n");
}

elseif(isset($_POST['addgrade']))
{
	if(user_type() != "teacher"){ cust_die("You may not access this page."); }

	if(!isset($_POST['classid'])){ cust_die("The form was not submitted correctly.  Are you using a good web browser?"); }
	if(!isset($_POST['assignment_name']) OR $_POST['assignment_name'] == ""){ cust_die("You must give the assignment a name."); }	$assign_name = escape_string(htmlspecialchars($_POST['assignment_name']));
	if(!isset($_POST['description']) OR $_POST['description'] == ""){ $description = ""; } else{ $description = escape_string(htmlspecialchars($_POST['description'])); }
	if(!isset($_POST['points_possible']) OR $_POST['points_possible'] == ""){ cust_die("You must submit how many points were possible on the assignment."); }	$points_possible = escape_string(htmlspecialchars($_POST['points_possible']));
	if(!isset($_POST['category']) OR $_POST['category'] == "" OR is_numeric($_POST['category']) == FALSE){ cust_die("You must submit the assignment's category.  If you need to setup categories, go to your classes page."); } else{ $category = escape_string(htmlspecialchars($_POST['category'])); }
	
	if($_POST['assigned'] == "today")
	{
		$timestamp = time();
	}
	
	elseif($_POST['assigned'] == "custom")
	{
		$month = escape_string($_POST['month']);
		// if there's a 0 in front of the month, remove it.
		$month = str_replace("0", "", $month);
		
		$day = escape_string($_POST['day']);
		$year = escape_string($_POST['year']);
		
		$timestamp = mktime(0, 0, 0, $month, $day, $year);
	}	
	

	$class_id = escape_string($_POST['classid']);

	if(is_numeric($class_id) == FALSE){ cust_die("That is not a valid class."); }
	

	connect_sql();

	// see if it's a valid class
	$valid_class = @query("SELECT 1 FROM `classes` WHERE `ID`='$class_id' LIMIT 1") or die("Error checking the database."); 
	if(num_rows($valid_class) == 0){ cust_die("That is not a valid class."); }

	if(teacher_teaches($_SESSION['id'], $class_id) == FALSE){ cust_die("You may not add grades to that class."); }
	
	// see if the category is valid
	$valid_category  = @query("SELECT 1 FROM `categories` WHERE `ID`='$category' LIMIT 1") or die("Error checking the database.");
	if(num_rows($valid_category) == 0){ cust_die("Invalid category."); }
	
	$students = get_students($class_id);
	$students = explode(",", $students);
	
	foreach($students as $student)
	{
		if(!isset($_POST[$student . "_scored"]) OR $_POST[$student . "_scored"] == "")
		{
			cust_die("You must fill in a grade for all students.  If a student doesn't have that grade, fill the box in with an x.");
		}
	}
	
	// get the assignment's number
	$assign_number = @query("SELECT MAX(`assign_number`) FROM `grades` WHERE `class_id`='$class_id'") or die("Error getting information from the database.");
	$result = command_result($assign_number, 0);
	$assign_number = $result;
	$assign_number++;
	
	$grading_period = current_semester;

	foreach($students as $student)
	{
		$points_scored = escape_string($_POST[$student . "_scored"]);
		$query = "INSERT INTO `grades` (`class_id`, `assign_number`, `assign_name`, `assign_desc`, `date_assigned`, `grading_period`, `student_ID`, `points_possible`, `points_scored`";

		if(isset($_POST[$student . "_comment"]))
		{
			if(strlen($_POST[$student . "_comment"]) <= 255)
			{
				$comment = escape_string(htmlspecialchars($_POST[$student . "_comment"]));
				$query .= ", `comment`";
			}
		}

		$query .= ", `category`) VALUES ('$class_id', '$assign_number', '$assign_name', '$description', '$timestamp', '$grading_period', '$student', '$points_possible', '$points_scored'";

		if(isset($comment))
		{
			$query .= ", '$comment'";
		}

		$query .= ", '$category')";
		@query($query) or die("Error adding the assignment to the database.");
	}

	disconnect_sql();	
	
	print("The assignment has been added.  Back to the <a href=\"assignment.php\" title=\"assignments\">assignment menu</a>?");
}

elseif(isset($_GET['delete']))
{
        if(user_type() != "teacher"){ cust_die("You may not access this page."); }
        if(!isset($_GET['assign_id']) OR $_GET['assign_id'] == "" OR is_numeric($_GET['assign_id'] == FALSE)){ cust_die("You must submit the ID of the assignment that you'd like to delete."); }

	$assignment_id = escape_string($_GET['assign_id']);

	connect_sql();

        // see if it's a valid assignment, and if so, see if the teacher may access it
        $valid_assignment = @query("SELECT `class_id` FROM `grades` WHERE `assign_number`='$assignment_id' LIMIT 1") or die("Error checking the database.");
        if(num_rows($valid_assignment) == 0){ cust_die("That is not a valid assignment."); }
        $results = result($valid_assignment);
        $class_id = $results->class_id;

	if(teacher_teaches($_SESSION['id'], $class_id) == FALSE){ cust_die("You may not edit that class's grades."); }

	// if everything else is good, see if they've confirmed the delete.  If they haven't, show them a link that will allow them to confirm it.

	if(isset($_GET['confirmed']))
	{
		connect_sql();
		@query("DELETE FROM `grades` WHERE `assign_number`='$assignment_id'") or die("Error removing the assignment from the database.");
		disconnect_sql();

		print("The assignment has been deleted.  Back to the assignment <a href=\"assignment.php\" title=\"assignment page\">assignment page</a>?");
	}

	else
	{
		print("<p style=\"font-weight: bold\">Are you sure you want to delete this assignment?</p><p><a href=\"assignment.php?delete&assign_id={$assignment_id}&confirmed\" title=\"Permanently delete the assignment.\">Yes, I am.</a></p><p><a href=\"assignment.php\" title=\"assignment page\">No, I am not.</a></p>");
	}
}


elseif(isset($_GET['edit']))
{
	if(user_type() != "teacher"){ cust_die("You can't do anything here."); }
	if(!isset($_GET['id']) OR $_GET['id'] == "" OR is_numeric($_GET['id'] == FALSE)){ cust_die("Which class's assignments would you like to edit?  Make sure you follow the correct links."); }
	
	$class_id = escape_string($_GET['id']);
	
	// see if the class is valid, and if so, if user may access the class
	connect_sql();
	
	// see if it's a valid class
	$valid_class = @query("SELECT 1 FROM `classes` WHERE `ID`='$class_id'") or die("Error checking the database."); 
	if(num_rows($valid_class) == 0){ cust_die("That is not a valid class."); }
	
	if(teacher_teaches($_SESSION['id'], $class_id) == FALSE){ cust_die("You may not edit that class's grades."); }
	
	// if a certain assignment is picked, display the form that allows editing of it; else, display links to edit assignments

	if(isset($_GET['assign_id']) AND is_numeric($_GET['assign_id']) == TRUE)
	{
		$assignment_id = escape_string($_GET['assign_id']);
	
		// see if it's a valid assignment, and if so, see if the teacher may access it
		$valid_assignment = @query("SELECT `class_id` FROM `grades` WHERE `assign_number`='$assignment_id'") or die("Error checking the database.");
		if(num_rows($valid_assignment) == 0){ cust_die("That is not a valid assignment."); }
		$results = result($valid_assignment);
		$class_id = $results->class_id;
	
		if(teacher_teaches($_SESSION['id'], $class_id) == FALSE){ cust_die("You may not edit that class's grades."); }

		// get the assignment's info from the database
		$assignment_info_query = "SELECT * FROM `grades` WHERE `assign_number`='$assignment_id'";
		$assignment_info = @query($assignment_info_query) or die("Error getting information from the database.");
		while($row = result($assignment_info))
		{
			$assignment_name = stripslashes($row->assign_name);
			$description = stripslashes($row->assign_desc);
			$points_possible = $row->points_possible;
			$assignment_category = $row->category;
		}
	
		print("<table><form action=\"assignment.php\" method=\"post\" name=\"edit_grade\">\n");
		print("<tr><th>Assignment name:</th><td><input type=\"text\" name=\"assignment_name\" value=\"{$assignment_name}\" /></td><td><a href=\"assignment.php?delete&assign_id={$assignment_id}\" title=\"delete this assignment\">Delete this assignment</a>.</td></tr>\n");
		print("<tr><th>Assignment description:</th><td><input type=\"text\" name=\"description\" value=\"{$description}\" /></td></tr>");
		
		print("<tr><th>Category:</th><td>");
	
		// if teacher has categories set up, list 'em.  Else, print a link to set up categories.
		if(class_has_categories($class_id) == TRUE)
		{
			$tdcolour = 0;

			print("<select name=\"category\">\n");
	
			$categories = return_categories($class_id);
			$categories = explode(":::::", $categories);
		
			foreach($categories as $part)
			{
				if($part != "")
				{
					list($id, $category) = explode(":", $part);
				
					print("<option value=\"{$id}\" class=\"tdcolour{$tdcolour}\" ");
					
					if($id == $assignment_category){ print(" selected"); }
					
					print(">{$category}\n");

					if($tdcolour == 0){ $tdcolour++; }
					else{ $tdcolour = 0; }
				}
			}
		
			print("</select>");
		}
	
		else
		{
			print("<a href=\"category.php?teacherid={$_SESSION['id']}&classid={$class_id}\" title=\"create categories\">create</a>\n");
		}
		
		print("<tr><th>Points possible:</th><td><input type=\"text\" name=\"points_possible\" value=\"{$points_possible}\" /></td></tr>");
		print("<tr><td colspan=\"2\"><hr /></td></tr>\n");
		print("<tr><th>Student Name/ID</th><th>Points Scored</th><th>comment</th></tr>\n");
		print("<tr><td></td><td><div class=\"small\"><em>\"x\" if missing the assignment</em></div></td><td></td>");
		
		// get a list of the class's students; if a student doesn't have score for the assignment, print a form to add 'em
		$students = get_students($class_id);
		
		// make it an array
		$students = explode(",", $students);
		
		// stupid way to do this, but it's pretty much needed until the database is changed around a bit (coming very soon)
		$assignment_info = query($assignment_info_query) or die("Error getting information from the database.");
		while($row = result($assignment_info))
		{
			$student = $row->student_ID;
			
			// remove the student from the students array (used to see if we need to add any new student to the table)
			foreach($students as $key => $value)
			{
				if($value == $student){ unset($students[$key]); break; }
			}
		
			// get user's name
			$info = @query("SELECT `firstname`, `surname` FROM `users` WHERE `ID`='$student'") or die("Error getting information from the database.");
			while($parts = result($info))
			{
				$student_name = stripslashes($parts->surname) . ", " . stripslashes($parts->firstname);
			
				$comment = stripslashes($row->comment);
			
				$studentid = @query("SELECT `studentID` FROM `students` WHERE `ID`='$student' LIMIT 1") or die("Error getting information from the database.");
				while($row2 = result($studentid))
				{
					print("<tr><td>{$student_name} -- " . $row2->studentID . "</td><td><input type=\"text\" name=\"{$student}_scored\" value=\"{$row->points_scored}\" /></td><td><input type=\"text\" name=\"{$student}_comment\" value=\"{$comment}\" /></td></tr>\n");
				}
			}
		}
		
		// 'kay, see if we need to print any more students.
		if(isset($students))
		{
			foreach($students as $key => $value)
			{
				if($value != "")
				{
					// get the student's name
					$name = @query("SELECT `surname`, `firstname` FROM `users` WHERE `ID`='$value' LIMIT 1") or die("Error getting information from the database.");
					while($info = result($name))
					{
						$student_name = stripslashes($info->surname) . ", " . stripslashes($info->firstname);

						$studentid = @query("SELECT `studentID` FROM `students` WHERE `ID`='$value' LIMIT 1") or die("Error getting information from the database.");
						while($row = result($studentid))
						{
							print("<tr><td>{$student_name} -- " . $row->studentID . "</td><td><input type=\"text\" name=\"{$value}_scored\" /></td><td><input type=\"text\" name=\"{$value}_comment\" /></td></tr>\n");
						}
					}
				}
			}
		}
		
	disconnect_sql();
	
	print("<tr><td><input type=\"hidden\" name=\"classid\" value=\"{$class_id}\" /><input type=\"hidden\" name=\"assignid\" value=\"{$assignment_id}\" /><input type=\"submit\" name=\"editgrade\" value=\"edit the assignment\" /></td></tr></form></table>\n");


	}
	
	else
	{
		// retrieve a list of assignments...
		$assignments = @query("SELECT * FROM `grades` WHERE `class_id`='$class_id' ORDER BY `assign_number` DESC") or die("Error getting information from the database.");
		
		print("Choose an assignment to edit:<ul>");
		
		$displayed = array();
		while($assignment = result($assignments))
		{
			$assign_number = $assignment->assign_number;
			
			// see if the assignment is already in the $displayed array; if it is not, put it there
			if(in_array($assign_number, $displayed) == FALSE)
			{
				$displayed[] = $assign_number;
			}
		}
		
		// yeah, silly way to do it, but I'm not sure how else to get the names from the array...
		foreach($displayed as $display)
		{
			$assign_number = $display[0];

			$assign_name = @query("SELECT `assign_name` FROM `grades` WHERE `assign_number`='$assign_number'") or die("Error.");
			$assign_name = result($assign_name);
			$assignment_name = $assign_name->assign_name;
			
			print("<li><a href=\"assignment.php?edit&id={$class_id}&assign_id={$assign_number}\" title=\"edit this assignment\">{$assignment_name}</a></li>");
		}
		
		print("</ul>");
		
	}	
}


elseif(isset($_POST['editgrade']))
{
	if(user_type() != "teacher"){ cust_die("You can't do anything here."); }

	if(!isset($_POST['classid'])){ cust_die("The form was not submitted correctly.  Are you using a good web browser?"); }
	if(!isset($_POST['assignment_name']) OR $_POST['assignment_name'] == ""){ cust_die("You must give the assignment a name."); }	$assign_name = escape_string($_POST['assignment_name']);
	if(!isset($_POST['description']) OR $_POST['description'] == ""){ $description = ""; } else{ $description = escape_string(htmlspecialchars($_POST['description'])); }
	if(!isset($_POST['points_possible']) OR $_POST['points_possible'] == ""){ cust_die("You must submit how many points were possible on the assignment."); }	$points_possible = escape_string($_POST['points_possible']);
	if(!isset($_POST['assignid']) OR $_POST['assignid'] == ""){ cust_die("You must submit the assignment's ID."); } $assign_id = escape_string($_POST['assignid']);
	if(!isset($_POST['category']) OR $_POST['category'] == "" OR is_numeric($_POST['category']) == FALSE){ cust_die("You must submit the assignment's category.  If you need to setup categories, go to your classes page."); } else{ $category = escape_string(htmlspecialchars($_POST['category'])); }
	
	$class_id = escape_string($_POST['classid']);
	if(is_numeric($class_id) == FALSE){ cust_die("That is not a valid class."); }

	connect_sql();

	// see if it's a valid class
	$valid_class = @query("SELECT 1 FROM `classes` WHERE `ID`='$class_id'") or die("Error checking the database."); 
	if(num_rows($valid_class) == 0){ cust_die("That is not a valid class."); }

	if(teacher_teaches($_SESSION['id'], $class_id) == FALSE){ cust_die("You may not edit that class's grades."); }
	
	// see if the category is valid
	$valid_category  = @query("SELECT 1 FROM `categories` WHERE `ID`='$category' LIMIT 1") or die("Error checking the database.");
	if(num_rows($valid_category) == 0){ cust_die("Invalid category."); }
	
	$students = get_students($class_id);
	$students = explode(",", $students);
	
	foreach($students as $student)
	{
		if(!isset($_POST[$student . "_scored"]) OR $_POST[$student . "_scored"] == "")
		{
			cust_die("You must fill in a grade for all students.  If a student doesn't have that grade, fill the box in with an x.");
		}
	}
	
	foreach($students as $student)
	{
		if(isset($_POST[$student . "_comment"]))
		{
			if(strlen($_POST[$student . "_comment"]) <= 255)
			{
				$comment = escape_string(htmlspecialchars($_POST[$student . "_comment"]));
			}
		}

		$points_scored = escape_string($_POST[$student . "_scored"]);
		
		// see if the student already has the grade.  if he or she does, update; else, insert
		$info = @query("SELECT 1 FROM `grades` WHERE `assign_number`='$assign_id' AND `student_ID`='$student' LIMIT 1") or die("Error checking the database.");
		if(num_rows($info) > 0){ $update = 1; } else{ $update = 0; }
		
		if($update == 1)
		{
			$query = "UPDATE `grades` SET `assign_name`='$assign_name', `assign_desc`='$description', `points_possible`='$points_possible', `points_scored`='$points_scored'";
		
			if(isset($comment)){ $query .= ", `comment`='$comment'"; }
		
			$query .= ", `category`='$category' WHERE `student_ID`='$student' AND `assign_number`='$assign_id' LIMIT 1";
		}
		
		else
		{
			$timestamp = time();
			$grading_period = current_semester;

			$assign_number = $assign_id;
		
			$query = "INSERT INTO `grades` (`class_id`, `assign_number`, `assign_name`, `assign_desc`, `date_assigned`, `grading_period`, `student_ID`, `points_possible`, `points_scored`";

			if(isset($_POST[$student . "_comment"]))
			{
				if(strlen($_POST[$student . "_comment"]) <= 255)
				{
					$comment = escape_string(htmlspecialchars($_POST[$student . "_comment"]));
					$query .= ", `comment`";
				}
			}

			$query .= ", `category`) VALUES ('$class_id', '$assign_number', '$assign_name', '$description', '$timestamp', '$grading_period', '$student', '$points_possible', '$points_scored'";

			if(isset($comment))
			{
				$query .= ", '$comment'";
			}

			$query .= ", '$category')";
		
		}
		
		@query($query) or die("Error modifying the assignment in the database.");
	}

	disconnect_sql();	
	
	print("The assignment has been edited.  Back to the <a href=\"assignment.php\" title=\"assignments\">assignment menu</a>?");

}


elseif(isset($_GET['view']))
{
	if(user_type() != "teacher"){ cust_die("You can't do anything here."); }
	if(!isset($_GET['id']) OR $_GET['id'] == "" OR is_numeric($_GET['id'] == FALSE)){ cust_die("Which class's assignments would you like to view?  Make sure you follow the correct links."); }
	
	$class_id = escape_string($_GET['id']);
	
	if(!isset($_GET['gp']) OR $_GET['gp'] == "" OR is_numeric($_GET['gp'] == FALSE)){ $grading_period = current_semester; }
	else{ $grading_period = escape_string($_GET['gp']); }
	
	// see if the class is valid, and if so, if user may access the class
	connect_sql();
	
	// see if it's a valid class
	$valid_class = @query("SELECT 1 FROM `classes` WHERE `ID`='$class_id'") or die("Error checking the database."); 
	if(num_rows($valid_class) == 0){ cust_die("That is not a valid class."); }
	
	if(teacher_teaches($_SESSION['id'], $class_id) == FALSE){ cust_die("You may not view that class's grades."); }
	
	print("<table id=\"spreadsheet\">");
	
	// get the assignments for the current (or specified) semester
	$assignment_info = @query("SELECT `assign_number` FROM `grades` WHERE `class_id`='$class_id' AND `grading_period`='$grading_period'");
	
	$assignments_to_use = array();
	
	while($assignments = result($assignment_info))
	{
		if(in_array($assignments->assign_number, $assignments_to_use) == FALSE)
		{
			$assignments_to_use[] = $assignments->assign_number;
		}
	}
	
	print("<tr class=\"spreadheader\"><td></td>");
	
	// get each assignment's name
	foreach($assignments_to_use as $assignment)
	{
		$the_name = @query("SELECT `assign_name` FROM `grades` WHERE `assign_number`='$assignment' LIMIT 1");
		while($name = result($the_name)){ print("<td>{$name->assign_name}</td>"); }
	}
	
	print("</tr>\n<tr class=\"spreadinformation\"><td>Points Possible");
	
	// get the points the assignment is worth
	foreach($assignments_to_use as $assignment)
	{
		$points_possible = @query("SELECT `points_possible` FROM `grades` WHERE `assign_number`='$assignment' LIMIT 1");
		while($points = result($points_possible)){ print("<td>{$points->points_possible}</td>"); }
	}
	
	print("</tr>");
	
	$tdcolour = 0;
	
	// get the students that attend the class
	$students = explode(",", get_students($class_id));
	foreach($students as $student)
	{
		// get user's name
		$student_name2 = @query("SELECT `firstname`, `surname` FROM `users` WHERE `ID`='$student'") or die("Error getting information from the database.");
		while($row = result($student_name2))
		{
			$student_name = stripslashes($row->firstname) . " " . stripslashes($row->surname);
			
			$info = @query("SELECT `studentID` FROM `students` WHERE `ID`='$student' LIMIT 1") or die("Error checking the database.");
			while($row2 = result($info))
			{
				print("<tr class=\"tdcolour{$tdcolour}\"><td>{$student_name}</td>");
				
				// get his or her assignments and print them
				$assignments = @query("SELECT * FROM `grades` WHERE `class_id`='$class_id' AND `grading_period`='$grading_period' AND `student_ID`='$student'");
				while($row3 = result($assignments))
				{
					print("<td>{$row3->points_scored}</td>");
				}
				
				print("</tr>");
			}
		}
		
		if($tdcolour == 0){ $tdcolour++; }
		else{ $tdcolour = 0; }
	}

	
	print("</table>");
	print("<span id=\"idspan\"></span>");
		
	// print links to view grades for the other semesters
	print("<div id=\"printlink\"><br /><hr class=\"mainpagehr\" /><a href=\"javascript:window.print()\" title=\"print this page\">print</a><br /><div id=\"namespan\">Other grading periods:  ");
	// get the grading periods the class takes place in
	$classdata = get_class_data($class_id);
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
				$other_semesters .= "<a href=\"assignment.php?view&amp;id={$class_id}&amp;gp={$semesters[$n]}\" title=\"assignments for grading period {$semesters[$n]}\">{$semesters[$n]}</a> ";
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
				$other_semesters .= "<a href=\"assignment.php?view&amp;id={$class_id}&amp;gp={$semesters[$n]}\" title=\"assignments for grading period {$semesters[$n]}\">{$semesters[$n]}</a> ";
			}
					
			$n++;
		}
	}	
	
	print("{$other_semesters}</div></div>");
	
	disconnect_sql();
}


else
{
	any_errors();
	
	if(user_type() != "teacher"){ cust_die("There is nothing for you to do here."); }

	              
	print("<table>\n<tr><th>Grading Period(s)</th><th>Period</th><th>Class Name</th></tr>");

	$id = $_SESSION['id'];

   	connect_sql();

	$classes = @query("SELECT * FROM `classes` WHERE `teacher`='$id' ORDER BY `period`") or die("Error getting your list of classes.");
   	while($row = result($classes))
   	{
		$name = stripslashes($row->name);
		$period = $row->period;
		$class_id = $row->ID;
		$semesters = $row->semester;

		print("<tr><td>{$semesters}</td><td>{$period}</td><td>{$name}</td><td><a href=\"assignment.php?add&id={$class_id}\" title=\"add an assignment\">add an assignment</a></td><td>|</td><td><a href=\"assignment.php?edit&id={$class_id}\" title=\"edit an assignment\">edit an assignment</a></td></tr>");
	}
	
	print("</table>");

}

print("</div>");
display_copyright();
display_footer();
?>
