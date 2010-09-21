<?php
/**
 * allows a user to change their options, e-mail address, etc..
 * \author Kevin Richardson <borismalcov@users.sourceforge.net>
 * \version $Id: options.php,v 1.11 2006/07/19 19:54:52 borismalcov Exp $
 */

include("lib/main.php");

// update this to include more server types
$server_types = array("mysql", "postgresql");	$current_server_type = server_type;

// update these to give the user more date/time format choices
$date_formats = array("l, F j, Y", "F j, Y", "j-M-y", "j F y", "m/d/y", "m.d.y");	$current_date_format = dateformat;
$time_formats = array("g:i a", "H:i");	$current_time_format = timeformat;


if(is_logged_in() == FALSE){ $_SESSION['not_this_page'] = 1; cust_die("You must login to access the requested page."); }

display_header("options");
display_menu(); print("<div class=\"container2\">");

// allows the admin to change another user's options
if(isset($_GET['student']))
{
	if(user_type() == "admin")
	{
		if($_GET['student'] != "")
		{
			if(is_numeric($_GET['student']) == FALSE){ die("Don't mess with that."); }
			$user = escape_string($_GET['student']);
	
			connect_sql();

			// see if the user is valid
			if(is_valid_user($user) == FALSE){ cust_die("That is not a valid user."); }
			
			disconnect_sql();
			
			// see if the admin wants to add a parental account
			if(isset($_GET['parentalaccounts']))
			{
				// allow the admin to attach an already added user (if there are any)
				// 'kay, see if any parents exist
				connect_sql();
				
				$parents = @query("SELECT * FROM `parents`") or die("Error checking the database.");
				if(num_rows($parents) > 0)
				{
					print("<table><form action=\"options.php\" method=\"post\" id=\"addexistingparentform\">\n");
					print("<tr><td class=\"title\" colspan=\"2\">Existing Parents</td></tr>\n");
					print("<tr><td>Parent:</td><td><select name=\"parent\">\n");
					
					// get the parents
					while($row = result($parents))
					{
						$parentID = $row->parent_ID;
						
						// get the parent's name
						$name = @query("SELECT `firstname`,`surname` FROM `users` WHERE `ID`='$parentID' LIMIT 1") or die("Error checking the database.");
						while($row2 = result($name))
						{
							$parentname = stripslashes($row2->firstname) . " " . stripslashes($row2->surname);
							
							print("<option value=\"{$parentID}\">{$parentname}\n");
						}
					}
					
					print("</select></td></tr>\n");
					print("<tr><td colspan=\"2\"><input type=\"hidden\" name=\"studentid\" value=\"{$user}\" /><input type=\"submit\" name=\"addexistingparent\" value=\"add\" /><input type=\"submit\" name=\"removeexistingparent\" value=\"remove\" /></td></tr>");
					print("</form></table>\n");
				}

				disconnect_sql();
				
				
				// or allow him or her to add a new one
				print("<table><form action=\"options.php\" method=\"post\" id=\"addnewparentform\">\n");
				print("<tr><td class=\"title\">Add a New Parent</td></tr>\n");
				print("<tr><td>First name:</td><td><input type=\"text\" name=\"firstname\" /></td></tr>\n");
				print("<tr><td>Last name:</td><td><input type=\"text\" name=\"surname\" /></td></tr>\n");
				print("<tr><td>Gender:</td><td><select name=\"gender\"><option value=\"f\" class=\"tdcolour0\">female<option value=\"m\" class=\"tdcolour1\">male</select></td></tr>\n");
				print("<tr><td>E-mail address (optional):</td><td><input type=\"text\" name=\"emailaddress\" /></td></tr>\n");
				print("<tr><td colspan=\"2\" class=\"small\"><em>Note:  Leave the following fields blank to make the system automatically generate them.</em></td></tr>\n");
				print("<tr><td>Username:</td><td><input type=\"text\" name=\"username\" /></td></tr>\n");
				print("<tr><td>Password:</td><td><input type=\"password\" name=\"pass1\" /></td></tr>\n");
				print("<tr><td>Confirm password:</td><td><input type=\"password\" name=\"pass2\" /></td></tr>\n");
				print("<tr><td><input type=\"hidden\" name=\"studentid\" value=\"{$user}\" /><input type=\"submit\" name=\"addnewparent\" value=\"add the parent\" /></td></tr>\n");
				print("</form></table>\n");
			}
			
			
			// allow the admin to change their classes, password, etc
			else
			{
				print("<table><form action=\"options.php\" method=\"post\">");
				print("<tr><td><p class=\"title\">Classes</p></td></tr>");
			
				// get the classes he or she is in, and allow the admin to change them

				$classes = parse_class_list($user);

				//remove the last comma
				$classes = substr($classes, 0, -1);

				// split it up into an array
				$classes = split(",", $classes);

				// for every grading period
				for($i=1;$i<=number_of_semesters;$i++)
				{
					print("<tr><td class=\"title\" align=\"center\">Grading Period {$i}</td></tr>\n");
				
					// for every day
					for($j=1;$j<=5;$j++)
					{
						print("<tr><td><em>");

						switch($j)
						{
							case 1: print("Monday"); break;
							case 2: print("Tuesday"); break;
							case 3: print("Wednesday"); break;
							case 4: print("Thursday"); break;
							case 5: print("Friday"); break;
						}

						print("</em></td></tr>\n");

						// for every period
						for($k=1;$k<=number_of_periods;$k++)
						{
							$tdcolour = 0;

							print("<tr><td>Period {$k}:</td><td><select name=\"gp{$i}d{$j}p{$k}\">\n");

							// get the possible classes, and select the one the user is currently in
							$possible_classes = @query("SELECT * FROM `classes` WHERE `semester` LIKE '%{$i}%' AND `period`='{$k}'") or die("Error getting list of classes.");
							while($class = result($possible_classes))
							{
								$id = $class->ID;
								$name = stripslashes($class->name);
								$teacher = $class->teacher;

								$teachers_name = @query("SELECT `firstname`, `surname` FROM `users` WHERE `ID`='$teacher' LIMIT 1") or die("Error getting teacher's name.");
								$row = result($teachers_name);
								$teachers_name = stripslashes($row->firstname) . " " . stripslashes($row->surname);

								print("<option value=\"{$id}\" class=\"tdcolour{$tdcolour}\"");

								if($classes[($i * $j * $k)-1] == $id){ print(" selected"); }

								print(">{$name}, taught by {$teachers_name}\n");

								if($tdcolour == 0){ $tdcolour++; }
								else{ $tdcolour = 0; }
							}

							print("</select></td></tr><tr>\n");
						}

						print("<tr><td><hr /></td></tr>");

					}

//					print("<tr><td colspan=\"3\"><hr /></td></tr>");
				}

				// get the student's student ID
				connect_sql();
				$stud_id = @query("SELECT `studentID` FROM `students` WHERE `ID`='$user' LIMIT 1") or die("Error getting the student's ID.");
				while($row = result($stud_id)){ $student_id = $row->studentID; }
				disconnect_sql();

				print("<tr><td><p class=\"title\">Password</p></td></tr>\n");
				print("<tr><td><em>Enter the user's new password twice, and click \"change\" to change their password.</em></td></tr>\n");
				print("<tr><td><input type=\"password\" name=\"pass1\" /></td></tr><tr><td><input type=\"password\" name=\"pass2\" /></td></tr>\n");
				print("<tr><td><p class=\"title\">Student ID</p></td></tr>\n");
				print("<tr><td><input type=\"text\" name=\"studentid\" value=\"{$student_id}\" /></td></tr>");
				print("<tr><td><input type=\"hidden\" name=\"user\" value=\"{$user}\" /><input type=\"submit\" name=\"modifystudent\" value=\"change\" /></td></tr></table></form>\n");

				// allow the admin to add/edit accounts for the user's parents
				print("<br /><a href=\"options.php?student={$user}&parentalaccounts\" title=\"add/edit parental accounts\">add/edit parental accounts</a>\n");

			}

		}

		else
		{
			print("<form method=\"get\" action\"options.php\">Student you would like to modify: <select type=\"text\" name=\"student\">");
		
			connect_sql();
		
			$users = @query("SELECT `ID`, `firstname`, `surname` FROM `users` WHERE `type`='1'ORDER BY `ID`") or die("Error getting information from the database.");
		
			$tdcolour = 0;
		
			while($row = result($users))
			{
				$ID = $row->ID;
				$name = stripslashes($row->firstname) . " " . stripslashes($row->surname);

				// get the student's ID number
				$studentID = @query("SELECT `studentID` FROM `students` WHERE `ID`='$ID' LIMIT 1") or die("Error getting the student's ID.");
				while($row2 = result($studentID))
				{
					print("<option value=\"{$ID}\" class=\"tdcolour{$tdcolour}\">{$name} -- " . stripslashes($row2->studentID) . "\n");
				}

                                if($tdcolour == 1){ $tdcolour = 0; } else{ $tdcolour++; }
			}
		
			disconnect_sql();


			print("</select><input type=\"submit\" value=\"go\" />");

		}	
	}

	else
	{
		cust_die("You may not view that page.");
	}
}

elseif(isset($_GET['teacher']))
{
	if(user_type() == "admin")
	{
		if($_GET['teacher'] != "")
		{
			if(is_numeric($_GET['teacher']) == FALSE){ die("Don't mess with that."); }
			$user = escape_string($_GET['teacher']);
		
			// see if the user is valid
			connect_sql();
			if(is_valid_user($user) == FALSE){ cust_die("Invalid user."); }			
			disconnect_sql();
			
			print("<table><form action=\"options.php\" method=\"post\">");
			print("<tr><td><p class=\"title\">Password</p></td></tr>");
			print("<tr><td><em>Enter the user's new password twice, and click \"change\" to change their password.</em></td></tr>");
			print("<tr><td><input type=\"password\" name=\"pass1\" /></td></tr><tr><td><input type=\"password\" name=\"pass2\" /></td></tr><tr><td><input type=\"hidden\" name=\"user\" value=\"{$user}\" /><input type=\"submit\" name=\"changepassword\" value=\"change\" /></td></tr></table></form>");
		}

		else
		{
			print("<form method=\"get\" action\"options.php\">Teacher you would like to modify: <select type=\"text\" name=\"teacher\">");
		
			connect_sql();
		
			$users = @query("SELECT `ID`, `firstname`, `surname` FROM `users` WHERE `type`='2'ORDER BY `ID`") or die("Error getting information from the database.");
		
			$tdcolour = 0;
		
			while($row = result($users))
			{
				$ID = $row->ID;
				$name = stripslashes($row->firstname) . " " . stripslashes($row->surname);
				print("<option value=\"{$ID}\" class=\"tdcolour{$tdcolour}\">{$name}\n");

                                if($tdcolour == 1){ $tdcolour = 0; } else{ $tdcolour++; }
			}
		
			disconnect_sql();


			print("</select><input type=\"submit\" value=\"go\" />");

		}	
	}

   else
   {
   	cust_die("You may not view that page.");
	}

}


elseif(isset($_POST['addexistingparent']))
{
	if(user_type() != "admin"){ cust_die("You may not view this page."); }
	
	connect_sql();
	
	if(!isset($_POST['studentid']) OR $_POST['studentid'] == "" OR is_numeric($_POST['studentid']) == FALSE){ cust_die("You must submit the student's ID.  This probably shouldn't come up; perhaps you should submit a bug report."); }
	$studentID = escape_string($_POST['studentid']);
	if(is_student($studentID) == FALSE){ cust_die("Invalid student ID."); }
	
	if(!isset($_POST['parent']) OR $_POST['parent'] == "" OR is_numeric($_POST['parent']) == FALSE){ cust_die("You must submit the parent's ID.  This probably shouldn't come up; perhaps you should submit a bug report."); }
	$parentID = escape_string($_POST['parent']);
	if(is_parent($parentID) == FALSE){ cust_die("Invalid parent ID."); }
	
	// 'kay, get the students the parent already has attached to his or her account
	$other_students = @query("SELECT `students` FROM `parents` WHERE `parent_ID`='$parentID' LIMIT 1") or die("Error checking the database.");
	while($row = result($other_students))
	{
		$students = $row->students;
		
		// add the new student
		$students .= ",{$studentID}";
		
		// update the database
		@query("UPDATE `parents` SET `students`='$students' WHERE `parent_ID`='$parentID' LIMIT 1") or die("Error updating the database.");
	}
	
	print("The parental account has been attached to the student.");
}

elseif(isset($_POST['removeexistingparent']))
{
	if(user_type() != "admin"){ cust_die("You may not view this page."); }
	
	connect_sql();
	
	if(!isset($_POST['studentid']) OR $_POST['studentid'] == "" OR is_numeric($_POST['studentid']) == FALSE){ cust_die("You must submit the student's ID.  This probably shouldn't come up; perhaps you should submit a bug report."); }
	$studentID = escape_string($_POST['studentid']);
	if(is_student($studentID) == FALSE){ cust_die("Invalid student ID."); }
	
	if(!isset($_POST['parent']) OR $_POST['parent'] == "" OR is_numeric($_POST['parent']) == FALSE){ cust_die("You must submit the parent's ID.  This probably shouldn't come up; perhaps you should submit a bug report."); }
	$parentID = escape_string($_POST['parent']);
	if(is_parent($parentID) == FALSE){ cust_die("Invalid parent ID."); }
	
	// 'kay, get the students the parent has attached to his or her account
	$other_students = @query("SELECT `students` FROM `parents` WHERE `parent_ID`='$parentID' LIMIT 1") or die("Error checking the database.");
	while($row = result($other_students))
	{
		$students = $row->students;
		
		$students = explode(",", $students);
		
		// break it into an array and search (there are better ways to do it, but this'll work)
		
		$i = 0;
		
		foreach($students as $student)
		{
			if($student == $studentID)
			{
				unset($students[$i]);
			}
			
			else
			{
				$i++;
			}
		}
		
		// 'kay, turn the array back into a string
		$students_string = "";
		
		$i = 1;
		$number_of_students = count($students);
		
		foreach($students as $student)
		{
			$students_string .= $student;
			
			// see if we need to add a comma
			if($number_of_students != $i){ $students_string .= ","; }
			else{ $i++; }
		}
		
		// update the database
		@query("UPDATE `parents` SET `students`='$students_string' WHERE `parent_ID`='$parentID' LIMIT 1") or die("Error updating the database.");
	}
	
	print("The parental account has been removed from the student.");
}


elseif(isset($_POST['addnewparent']))
{
	if(user_type() != "admin"){ cust_die("You may not view this page."); }

	connect_sql();

	if(!isset($_POST['firstname']) OR $_POST['firstname'] == "")
	{
		cust_die("You must submit the parent's first name.");
	}
	$firstname = escape_string($_POST['firstname']);
	
	if(!isset($_POST['surname']) OR $_POST['surname'] == "")
	{
		cust_die("You must submit the parents's last name.");
	}
	$surname = escape_string($_POST['surname']);
	
	if(!isset($_POST['gender']) OR $_POST['gender'] == "")
	{
		cust_die("You must submit the parents's gender.");
	}
	elseif($_POST['gender'] != "m" AND $_POST['gender'] != "f"){ cust_die("That is not a valid gender..."); }
	$gender = escape_string($_POST['gender']);
	
	if(!isset($_POST['studentid']) OR $_POST['studentid'] == "" OR is_numeric($_POST['studentid']) == FALSE){ cust_die("You must submit the student's ID.  This probably shouldn't come up; perhaps you should submit a bug report."); }
	$studentID = escape_string($_POST['studentid']);
	
	if(is_student($studentID) == FALSE){ cust_die("Invalid student ID."); }
	
	if(!isset($_POST['email']) OR $_POST['email'] == ""){ $email = ""; }
	else{ $email = escape_string($_POST['email']);	if(is_valid_email($email) == FALSE){ cust_die("The e-mail address was not in the correct format."); } }
	
	// if they haven't entered a username, generate one using the person's name
	if(!isset($_POST['username']) OR $_POST['username'] =="")
	{
		$username = $firstname . $surname;
		$username = str_replace(" ", "", $username);
		$username = strtolower($username);
		$username = substr($username, 0, 30);
		$original = $username;

      $n = 2;

      while(is_username_taken($username) == TRUE)
      {
      	$username = $original . $n;
      	$n++;
      }
	}
	
	else
	{
		$username = escape_string(htmlspecialchars($_POST['username']));
		$orignal = $username;

      $n = 2;

      while(is_username_taken($username) == TRUE)
      {
      	$username = $original . $n;
         $n++;
      }
	}
	
	// if they haven't entered a password, randomly generate one
	if(!isset($_POST['pass1']) OR $_POST['pass1'] == "")
	{
		$first = time();	$first = md5($first);
		$second = date("B");	$second = md5($second);
		$third = md5($username);
		$password = $first . $second . $third;	$password = md5($password);	$password = strrev($password);	$password = substr($password, 0, 7);
	}
	
	elseif(isset($_POST['pass1']) AND $_POST['pass1'] != "")
	{
		if(!isset($_POST['pass2']) OR $_POST['pass2'] == ""){ cust_die("You must confirm the user's password."); }
		elseif($_POST['pass1'] != $_POST['pass2']){ cust_die("The passwords did not match."); }
		else{ $password = escape_string($_POST['pass1']); }
	}
	
	$cryptedpass = md5(md5($password));
	
	add_user($username, $cryptedpass, "4", $firstname, $surname, $gender, $email);
	
	// get his or her ID
	$parentID = @query("SELECT `ID` FROM `users` WHERE `username`='$username' LIMIT 1") or die("Error checking the database.");
	while($row = result($parentID))
	{
		$theID = $row->ID;
	
		// add 'em to the `parents` table
		@query("INSERT INTO `parents` (`parent_ID`, `students`) VALUES ('$theID', '$studentID')") or die("Error inserting the parent into the database.");
	}
	
	
	disconnect_sql();
	
	print("The parent was successfully added.  <a href=\"add.php?printer&amp;name={$firstname}%20{$surname}&amp;username={$username}&amp;password={$password}\" target=\"_blank\" onClick=\"window.open('add.php?printer&amp;name={$firstname}%20{$surname}&amp;username={$username}&amp;password={$password}','printer_page','width=650,height=400'); return false;\" title=\"printer-friendly page\">Click here</a> if you'd like a printer-friendly page for distribution.  (It will open in a new window.)");	
}


elseif(isset($_POST['modifystudent']))
{
	if(user_type() == "admin")
	{

		if(isset($_POST['pass1']) AND $_POST['pass1'] != "")
		{
			if(!isset($_POST['pass2']) OR $_POST['pass2'] == ""){ cust_die("You must submit the password twice."); }

			$pass = escape_string($_POST['pass1']);
		}

		if(!isset($_POST['user']) OR $_POST['user'] == ""){ cust_die("Don't mess with that."); }
		if(is_numeric($_POST['user']) == FALSE){ cust_die("Don't mess with that."); }
	        $user = escape_string($_POST['user']);
		
		if(!isset($_POST['studentid']) OR $_POST['studentid'] == ""){ cust_die("You must submit the student's student ID."); }
		$studentID = escape_string(htmlspecialchars($_POST['studentid']));

		if(isset($pass))
		{

			if($_POST['pass1'] != $_POST['pass2']){ cust_die("The passwords must match, and the ones you submitted did not."); }

			$cryptpass = md5(md5($pass));

			connect_sql();

			@query("UPDATE `users` SET `password`='$cryptpass' WHERE `ID`='$user' LIMIT 1") or die("Error updating the user's password.");
	     		disconnect_sql();
		}

		// update the user's student ID
		connect_sql();
		@query("UPDATE `students` SET `studentID`='$studentID' WHERE `ID`='$user' LIMIT 1") or die("Error updating the user's student ID.");
		disconnect_sql();

		// build the class string that'll be updated in the database
		$classes_string = "";

		for($i=1;$i<=number_of_semesters;$i++)
		{
			$classes_string .= "$i{";

			for($j=1;$j<=5;$j++)
			{
				switch($j)
				{
					case 1: $classes_string .= "Mon["; break;
					case 2: $classes_string .= "Tue["; break;
					case 3: $classes_string .= "Wed["; break;
					case 4: $classes_string .= "Thu["; break;
					case 5: $classes_string .= "Fri["; break;
				}

				// get his or her classes..
				for($k=1;$k<=number_of_periods;$k++)
				{
					$class = escape_string($_POST['gp' . $i . 'd' . $j . 'p' . $k]);
					if(is_numeric($class) == FALSE){ cust_die("Error encountered.  You probably were messing with something.  ;)"); }
					
					$classes_string .= $class;

					if($k != number_of_periods){ $classes_string .= ","; }
				}

				$classes_string .= "]";
			}

			$classes_string .= "}";
		}

		connect_sql();

		@query("UPDATE `students` SET `classes`='$classes_string' WHERE `ID`='$user' LIMIT 1") or die("Error updating the database.");
		disconnect_sql();
	
		print("Done.  <a href=\"options.php?student\" title=\"change another?\">Change another?</a>");
	}

   else
   {
		cust_die("You may not view that page.");
   }

}

elseif(isset($_GET['class']))
{
	if(user_type() == "admin")
	{
		if($_GET['class'] != "")
		{
			if(is_numeric($_GET['class']) == FALSE){ die("Invalid ID."); }
			$class_id = escape_string($_GET['class']);
		
			connect_sql();
		
			// get the class's information
			$class_data = get_class_data($class_id);
			$class_data= explode("::", $class_data);
			
			$class_name = $class_data[0];
			$class_teacher = $class_data[1];
			$class_room = $class_data[2];
			$class_period = $class_data[3];
			$class_semester = $class_data[4];
			
			// heh, yeah, stupid work around.  I didn't really think get_class_data() through enough...
			//$n = 5;
			//while(isset($class_data[$n]))
			//{
			//	$class_semester .= ",{$class_data[$n]}";
			//	$n++;
			//}
			
			print("<form action=\"options.php\" method=\"post\"><table>\n");
			print("<tr><td>name:</td><td><input type=\"text\" name=\"classname\" value=\"{$class_name}\" /></td></tr>\n");
			print("<tr><td>teacher:</td><td><select name=\"teacher\">\n");
			
			// get the teachers, and highlight the correct one...
			$users = @query("SELECT `ID`, `firstname`, `surname` FROM `users` WHERE `type`='2'ORDER BY `ID`") or die("Error getting information from the database.");
		
			$tdcolour = 0;
		
			while($row = result($users))
			{
				$ID = $row->ID;
				$name = stripslashes($row->firstname) . " " . stripslashes($row->surname);

				print("<option value=\"{$ID}\" class=\"tdcolour{$tdcolour}\"");
                                if($tdcolour == 1){ $tdcolour = 0; } else{ $tdcolour++; }

				if($ID == $class_teacher){ print(" default"); }
				print(">{$name}\n");
			}
			
			
			print("</select></td></tr>");
			print("<tr><td>room:</td><td><input type=\"text\" name=\"classroom\" value=\"{$class_room}\" /></td></tr>");
			print("<tr><td>period:</td><td><input type=\"text\" name=\"classperiod\" value=\"{$class_period}\" /></td></tr>");
			print("<tr><td>grading periods:</td><td><input type=\"text\" name=\"classsemester\" value=\"{$class_semester}\" /></td></tr>");
			print("<tr><td><input type=\"hidden\" name=\"classid\" value=\"{$class_id}\" /></td><td><input type=\"submit\" name=\"updateclass\" value=\"update class\" /></td></tr></table></form>");
			
			disconnect_sql();

		}
		
		// give a list of classes
		else
		{
			print("<form action=\"options.php\" method=\"get\"><table><tr><td>Class to edit:</td><td><select name=\"class\">");
			
			connect_sql();
		
			$classes = @query("SELECT `ID`,`name`, `period`, `teacher`, `semester` FROM `classes` ORDER BY `ID`") or die("Error getting information from the database.");

			$tdcolour = 0;
		
			while($row = result($classes))
			{
				$ID = $row->ID;
				$class_name = stripslashes($row->name);
				$period = $row->period;
				$teacher = $row->teacher;
				$semester = $row->semester;
				
				$user_name = @query("SELECT `firstname`,`surname` FROM `users` WHERE `ID`='$teacher' LIMIT 1") or die("Error getting information from the database.");
				while($name = result($user_name))
				{
					$teachers_name = stripslashes($name->firstname) . " " . stripslashes($name->surname);
					print("<option value=\"{$ID}\" class=\"tdcolour{$tdcolour}\">gp. {$semester} pd. {$period} {$class_name}, taught by {$teachers_name}\n");
	                                if($tdcolour == 1){ $tdcolour = 0; } else{ $tdcolour++; }
				}
				
			}
		
			disconnect_sql();
			
			print("</select></td><td><input type=\"submit\" value=\"edit\" /></td></tr></table></form>");
		}
	}
}

elseif(isset($_POST['updateclass']))
{
	if(user_type() == "admin")
	{
		if(!isset($_POST['classid']) OR $_POST['classid'] == "" OR is_numeric($_POST['classid']) == FALSE){ die("Invalid class ID."); }
	
		$class_id = escape_string($_POST['classid']);
	
		// see if the class is valid
		connect_sql();
			
		$valid_class = @query("SELECT 1 FROM `classes` WHERE `ID`='$class_id' LIMIT 1") or cust_die("Error checking the database.");
		if(num_rows($valid_class) == 0){ cust_die("That is not a valid class."); }
		
		// see if everything we need is present
		if(isset($_POST['classname']) AND $_POST['classname'] != ""){ $class_name = escape_string($_POST['classname']); } else{ cust_die("Please submit the class's name."); }
		if(isset($_POST['teacher']) AND $_POST['teacher'] != "" && is_numeric($_POST['teacher']) == TRUE){ $teacher = escape_string($_POST['teacher']); } else{ cust_die("Please submit the class's teacher."); }
		if(isset($_POST['classroom']) AND $_POST['classroom'] != ""){ $classroom = escape_string($_POST['classroom']); } else{ $classroom = ""; }
		if(isset($_POST['classperiod']) AND $_POST['classperiod'] != "" && is_numeric($_POST['classperiod']) == TRUE){ $class_period = escape_string($_POST['classperiod']); } else{ cust_die("Please submit the class's period."); }
		if(isset($_POST['classsemester']) AND $_POST['classsemester'] != ""){ $class_semester = escape_string($_POST['classsemester']); } else{ cust_die("Please submit which grading periods the class will take place during."); }


		// if everything's good, update the database...
		@query("UPDATE `classes` SET `name`='$class_name', `teacher`='$teacher', `room`='$classroom', `period`='$class_period', `semester`='$class_semester' WHERE `ID`='$class_id' LIMIT 1") or die("Error updating the database.");
		
		print("Done.  <a href=\"options.php?class\" title=\"edit another class\">Edit another class</a>?");
		
		disconnect_sql();
	}
}

// allows an admin to change a teacher's password
elseif(isset($_POST['changepassword']))
{
	if(user_type() != "admin"){ cust_die("You may not view that page."); }

	connect_sql();

	if(!isset($_POST['user']) OR is_numeric($_POST['user']) == FALSE){ cust_die("Invalid user."); }
	$user = escape_string(htmlspecialchars($_POST['user']));

	// make sure the user's valid; they don't necessarily have to be a teacher
	if(is_valid_user($user) == FALSE){ cust_die("Invalid user."); }

	if(!isset($_POST['pass1']) OR $_POST['pass1'] == "" OR strlen($_POST['pass1']) < 6){ cust_die("The password must be at least 6 characters long."); }
	if(!isset($_POST['pass2']) OR $_POST['pass2'] == "" OR strlen($_POST['pass2']) < 6){ cust_die("You must enter the password twice, and it must be at least 6 characters long."); }

	if($_POST['pass1'] != $_POST['pass2']){ cust_die("The passwords must match."); }

	$cryptpass = md5(md5(escape_string($_POST['pass1'])));

	// update his or her password
	@query("UPDATE `users` SET `password`='$cryptpass' WHERE `ID`='$user' LIMIT 1") or die("Error updating the user's password.");

	print("Done.  <a href=\"options.php?teacher\" title=\"edit a teacher\">Edit another teacher</a>?");
}

elseif(isset($_POST['modifyoptions']))
{
	if(isset($_POST['email']) AND $_POST['email'] != "")
	{
		$email = escape_string($_POST['email']);
		if(is_valid_email($email) == FALSE){ cust_die("The e-mail address you entered is not valid.  Please enter a valid e-mail address."); }
	}
	else{ $email = " "; }
	
	// if they'd like to change their password
	if(isset($_POST['pass1']) AND $_POST['pass1'] != "")
	{
		if(strlen($_POST['pass1']) < 6){ cust_die("Your password must be at least 6 characters long."); }
		if($_POST['pass1'] == $_SESSION['username']){ cust_die("Your password may not be your username."); }
		
		if(!isset($_POST['pass2']) OR $_POST['pass2'] == ""){ cust_die("The two passwords did not match."); }
		
		$cryptpass = md5(md5(escape_string($_POST['pass1'])));
		
		$updatepass = 1;
	}

	$id = $_SESSION['id'];

	connect_sql();
	
	$query = "UPDATE `users` SET `email`='$email'";
	if(isset($updatepass)){ $query .= " AND `password`='$cryptpass' "; }
	$query .= "WHERE `ID`='$id'";
	
	@query($query) or die("Error updating the database.");
	
	disconnect_sql();	

	// see if the user is an administrator, allow them to modify the following options
	if(user_type() == "admin")
	{
		$server_type = escape_string($_POST['server_type']);
		$server = escape_string($_POST['database_server']);
		$database = escape_string($_POST['database']);
		$username = escape_string($_POST['database_username']);
		$password = escape_string($_POST['database_password']);
		$server_root = escape_string($_POST['server_root']);
		
		$date_format = escape_string($_POST['date_format']);
		$time_format = escape_string($_POST['time_format']);
		
		$school_name = escape_string($_POST['school_name']);
		$number_of_periods = escape_string($_POST['number_of_periods']);
		$number_of_semesters = escape_string($_POST['number_of_semesters']);
		
		$enable_forums = escape_string($_POST['enable_forums']);
		if(is_numeric($enable_forums) == FALSE){ cust_die("Don't mess with that. ;D"); }

		$track_attendance = escape_string($_POST['track_attendance']);
		if(is_numeric($track_attendance) == FALSE){ cust_die("Don't mess with that. ;D"); }
		
		$current_semester = escape_string($_POST['current_semester']);
		if(is_numeric($current_semester) == FALSE){ cust_die("Don't mess with that. ;D"); }
		
$content = <<< EOT
<?php\n
define("current_version", "0.1.3");

define("server_type", "{$server_type}");
define("server", "{$server}");
define("username", "{$username}");
define("password", "{$password}");
define("database", "{$database}");
define("server_root", "{$server_root}");

define("school_name", "{$school_name}");
define("dateformat", "{$date_format}");
define("timeformat", "{$time_format}");

define("number_of_periods", {$number_of_periods});
define("number_of_semesters", {$number_of_semesters});

define("enable_forums", {$enable_forums});
define("track_attendance", {$track_attendance});
define("current_semester", {$current_semester});

?>
EOT;
	
		$handle = @fopen("include/config.php", "w") or die("Error writing the configuration file.  (The web server probably does not have write access to the include directory.)  Please open config.php in the include/ directory, and replace it with the following:<blockquote>" . str_replace(">", "&gt;", str_replace("<", "&lt;", $content)) . "</blockquote>");
		fwrite($handle, $content);	
	}
	
	print("Your options have been saved.");
}


else
{
	any_errors();

	$id = $_SESSION['id'];
	
	connect_sql();
	$email = @query("SELECT `email` FROM `users` WHERE `ID`='$id' LIMIT 1") or die("Error getting information from the database.");
	$result = result($email);
	$email = $result->email;
	disconnect_sql();
	$email = stripslashes($email);

	print("
	<table width=\"100%\"><form action=\"options.php\" method=\"post\">
	<tr><td class=\"title\" colspan=\"2\">Personal Information</td></tr>
	<tr><td>e-mail address:</td><td><input type=\"text\" name=\"email\" maxlength=\"75\" value=\"{$email}\" /></td><td><small><em>(This will be used to e-mail you your login information if you happen to forget it.)</em></small></td></tr>
	<tr><td colspan=\"3\"><small><em>If you'd like to change your password, do so here.</em></small></td></tr>
	<tr><td>password:</td><td><input type=\"password\" name=\"pass1\" maxlength=\"30\" /></td></tr>
	<tr><td>password (again):</td><td><input type=\"password\" name=\"pass2\" maxlength=\"30\" /></td></tr>
	");
	
	if(user_type() == "admin")
	{
		print("
		<tr><td class=\"title\" colspan=\"2\"><hr />Database Information</td></tr>
		<tr><td>server type:</td><td><select name=\"server_type\">\n");

		$tdcolour = 0;

		foreach($server_types as $server_type)
		{
			print("<option value=\"{$server_type}\" class=\"tdcolour{$tdcolour}\"");
			if($current_server_type == $server_type){ print(" selected"); }
			print(">{$server_type}\n");

			if($tdcolour == 1){ $tdcolour = 0; } else{ $tdcolour++; }
		}
		
		print("</select></td></tr>
		<tr><td>server:</td><td><input type=\"text\" name=\"database_server\" value=\"" . server . "\" /></td></tr>
		<tr><td>database:</td><td><input type=\"text\" name=\"database\" value=\"". database . "\" /></td></tr>
		<tr><td>username:</td><td><input type=\"text\" name=\"database_username\" value=\"". username . "\" /></td></tr>
		<tr><td>password:</td><td><input type=\"password\" name=\"database_password\" value=\"". password . "\" /></td></tr>		
		");
		
		print("
		<tr><td class=\"title\" colspan=\"2\"><hr />Other Information</td></tr>
		<tr><td>phpmygrades root (where phpmygrades is located):</td><td><input type=\"text\" name=\"server_root\" value=\"" . server_root . "\" /></td></tr>
		<tr><td>number of periods in a day:</td><td><input type=\"text\" name=\"number_of_periods\" value=\"" . number_of_periods . "\" /></td></tr>
		<tr><td>number of grading periods:</td><td><input type=\"text\" name=\"number_of_semesters\" value=\"" . number_of_semesters . "\" /></td></tr>
		<tr><td>school name:</td><td><input type=\"text\" name=\"school_name\" value=\"" . school_name . "\" /></td></tr>
		<tr><td>date format:</td><td><select name=\"date_format\">\n");
		
		$tdcolour = 0;

		// print their date format choices
		foreach($date_formats as $date_format)
		{
			print("<option value=\"{$date_format}\" class=\"tdcolour{$tdcolour}\"");
			if($current_date_format == $date_format){ print(" selected"); }
			print(">" . date($date_format) ."\n");

			if($tdcolour == 1){ $tdcolour = 0; } else{ $tdcolour++; }
		}
		
		print("</select></td></tr>\n<tr><td>time format:</td><td><select name=\"time_format\">\n");
		
		$tdcolour = 0;

		// print their time format choices
		foreach($time_formats as $time_format)
		{
			print("<option value=\"{$time_format}\" class=\"tdcolour{$tdcolour}\"");
			if($current_time_format == $time_format){ print(" selected"); }
			print(">" . date($time_format) ."\n");

			if($tdcolour == 1){ $tdcolour = 0; } else{ $tdcolour++; }
		}
		
		
		print("</select></td></tr>");

		print("<tr><td>enable forums:</td><td><select name=\"enable_forums\">");
	
		print("<option value=\"0\" class=\"tdcolour0\"");
		if(defined('enable_forums') AND enable_forums == 0){ print("selected"); }
		print(">no<option value=\"1\" class=\"tdcolour1\"");
		if(defined('enable_forums') AND enable_forums == 1){ print("selected"); }
	
		print(">yes
		</select></td></tr>");

		print("<tr><td>track attendance:</td><td><select name=\"track_attendance\">");
		
		print("<option value=\"0\" class=\"tdcolour0\"");
		if(defined('track_attendance') AND track_attendance == 0){ print("selected"); }
		print(">no<option value=\"1\" class=\"tdcolour1\"");
		if(defined('track_attendance') AND track_attendance == 1){ print("selected"); }
		print(">yes</select></td></tr>");

		print("<tr><td>current grading period:</td><td><select name=\"current_semester\">\n");
	
		$tdcolour = 0;

		for($i=1;$i<=number_of_semesters;$i++)
		{
			print("<option class=\"tdcolour{$tdcolour}\" ");
			if($i == current_semester){ print(" selected"); }
			print(">{$i}\n");

                        if($tdcolour == 1){ $tdcolour = 0; } else{ $tdcolour++; }
		}
		
		print("</select></td></tr>\n");
			
	}


	
	print("<tr><td><input type=\"submit\" name=\"modifyoptions\" value=\"save\" /></td></tr>
	</form></table>
	");
}


print("</div>");
display_copyright();
display_footer();

?>
