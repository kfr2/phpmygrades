<?php
/**
 * allows an administrator to add classes, teachers, and students
 *
 * \author Kevin Richardson <borismalcov@users.sourceforge.net>
 * \version $Id: add.php,v 1.10 2006/07/19 19:54:52 borismalcov Exp $
 */


include("lib/main.php");

if(is_logged_in() == FALSE){ $_SESSION['not_this_page'] = 1; cust_die("You'll need to login to access the page you've requested."); }
if(user_type() != "admin"){ $_SESSION['not_this_page'] = 1; cust_die("You may not access the page you've requested."); }

// produces a printer-friendly page (so account information can be distributed easily)
if(isset($_GET['printer']))
{
	if(!isset($_GET['name']) OR !isset($_GET['username']) OR !isset($_GET['password'])){ cust_die("Please make sure you followed the correct link."); }
	
	$name = htmlspecialchars($_GET['name']);
	$username = htmlspecialchars($_GET['username']);
	$password = htmlspecialchars($_GET['password']);
	
	print("<html><head><link rel=\"stylesheet\" type=\"text/css\" href=\"style/main.css\"><title>{$name}</title></head><body>");
	print("<p class=\"title\">{$name}</p>");
	print("Your login information is as follows:<hr width=\"50%\" align=\"left\" />");
	print("<table>");
	print("<tr><td>Username:</td><td>{$username}</td></tr><tr><td>Password:</td><td>{$password}</td></tr>");
	print("</table></body></html>");
	
	die();
}

display_header("addition script");

display_menu();
print("<div class=\"container2\">");


if(isset($_GET['student']))
{
	connect_sql();

	// see if the admin has entered classes or not; if he or she hasn't, alert them to the fact.
	$number_of_classes = @query("SELECT 1 FROM `classes`") or die("Error checking the database.");
	if(num_rows($number_of_classes) == 0){ cust_die("You must <a href=\"add.php?class\" title=\"add a class\">add classes</a> before you add a student."); }

	disconnect_sql();

	print("
	<table><form name=\"the_form\" action=\"add.php\" method=\"post\">
	<tr><td class=\"title\">Add a Student</td></tr>
	<tr><td style=\"font-weight: bold\">First Name:</td><td><input type=\"text\" name=\"firstname\" maxlength=\"50\" /></td></tr>
	<tr><td style=\"font-weight: bold\">Last Name:</td><td><input type=\"text\" name=\"surname\" maxlength=\"75\" /></td></tr>
	<tr><td style=\"font-weight: bold\">Gender:</td><td><select name=\"gender\"><option value=\"f\" class=\"tdcolour0\">female</option><option value=\"m\" class=\"tdcolour1\">male></option></select></td></tr>
	<tr><td style=\"font-weight: bold\">Student ID:</td><td><input type=\"text\" name=\"studentid\" maxlength=\"25\" /></td></tr>
	<tr><td colspan=\"2\" class=\"small\"><em>Note:  Leave the following fields blank to make the system automatically generate them.</i></small></td></tr>
	<tr><td>Username:</td><td><input type=\"text\" name=\"username\" maxlength=\"30\" /></td></tr>
	<tr><td>Password:</td><td><input type=\"password\" name=\"pass1\" maxlength=\"30\" /></td></tr>
	<tr><td>Confirm password:</td><td><input type=\"password\" name=\"pass2\" maxlength=\"30\" /></td></tr>
	<tr><td colspan=\"2\"><hr /></td></tr>
	<tr><td class=\"title\">Classes</td></tr>
	");
	
	connect_sql();
	
	for($i=1;$i<=number_of_semesters;$i++)
	{
		print("<tr><td align=\"center\"><em>Grading Period {$i}</em></td></tr>");
		
		for($j=1;$j<=number_of_periods;$j++)
		{
			$tdcolour = 0;
	
			$class_list = @query("SELECT * FROM `classes` WHERE `period`='$j'") or die("Error getting the list of classes");
	
			print("\n<tr><td>Period {$j}:</td><td><select name=\"{$i}.{$j}\">\n");
		
			while($row = result($class_list))
			{
				$id = $row->ID;
				$name = stripslashes($row->name);
				$teacher_id = $row->teacher;
				
				$teacher_names = @query("SELECT `firstname`, `surname` FROM `users` WHERE `ID`='$teacher_id' LIMIT 1") or die("Error getting the teacher's name.");
				$row2 = result($teacher_names);
				$teacher = stripslashes($row2->firstname) . " " . stripslashes($row2->surname);
				
				$semester = stripslashes($row->semester);
				
				if(ereg($i, $semester) == TRUE)
				{
					print("<option value=\"{$id}\" class=\"tdcolour{$tdcolour}\">{$name}, taught by {$teacher}</option>\n");
					if($tdcolour == 1){ $tdcolour = 0; }
					else{ $tdcolour++; }
				}
			}
		
			print("</select></td>\n");
			print("</tr>\n");
		}
		
		// so a hr isn't shown under the last semester set
		if($i != number_of_semesters){ print("<tr><td colspan=\"3\"><hr /></td></tr>"); }
	}
	
		disconnect_sql();
	
		print("
		<tr><td colspan=\"2\"><hr /></td></tr>
		<tr><td><input type=\"submit\" name=\"addstudent\" value=\"add this student\" /></td></tr>
		</form></table>
		");
}

elseif(isset($_POST['addstudent']))
{
	connect_sql();

	if(!isset($_POST['firstname']) OR $_POST['firstname'] == "")
	{
		cust_die("You must submit the student's first name.");
	}
	$firstname = escape_string($_POST['firstname']);
	
	if(!isset($_POST['surname']) OR $_POST['surname'] == "")
	{
		cust_die("You must submit the student's last name.");
	}
	$surname = escape_string($_POST['surname']);
	
	if(!isset($_POST['studentid']) OR $_POST['studentid'] == "")
	{
		cust_die("You must submit the student's ID number.");
	}
	$realstudentid = escape_string($_POST['studentid']);
	
	$email = "";
	
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
                $original = $username;

		$n = 2;

                while(is_username_taken($username) == TRUE)
                {
                        $username = $original . $n;
                        $n++;
                }
	}
	
	if(!isset($_POST['gender']) OR $_POST['gender'] == "")
	{
		cust_die("You must submit the student's gender.");
	}
	elseif($_POST['gender'] != "m" AND $_POST['gender'] != "f"){ cust_die("That is not a valid gender..."); }
	$gender = escape_string($_POST['gender']);
	
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
	
	
	// add the user to the database
	add_user($username, $cryptedpass, "1", $firstname, $surname, $gender, $email);
	
	
	$student_id = @query("SELECT `ID` FROM `users` WHERE `username`='$username' LIMIT 1") or die("Error getting the student's ID number.");
	$student_id = command_result($student_id, 0);
	
	
	$classes = "";
	// add 'em to the student table
	for($i=1;$i<=number_of_semesters;$i++)
	{
		$classes .= "{$i}\{";
		
		for($day=1; $day<= 5; $day++)
		{
			switch($day)
			{
				case 1: $classes .= "Mon"; break;
				case 2: $classes .= "Tue"; break;
				case 3: $classes .= "Wed"; break;
				case 4: $classes .= "Thu"; break;
				case 5: $classes .= "Fri"; break;
			}
			
			$classes .= "[";
		
			for($j=1;$j<=number_of_periods;$j++)
			{
				$classes .= $_POST["{$i}_{$j}"];
				if($j != number_of_periods){ $classes .= ","; }
			}
			
			$classes .= "]";
		}
		
		$classes .= "}";
	}
	
	@query("INSERT INTO `students` (`ID`, `studentid`, `classes`) VALUES ('$student_id', '$realstudentid', '$classes')") or die("Error inserting information into the student table.");
	
	print("The user was successfully added.  <a href=\"add.php?printer&amp;name={$firstname}%20{$surname}&amp;username={$username}&amp;password={$password}\" target=\"_blank\" onClick=\"window.open('add.php?printer&amp;name={$firstname}%20{$surname}&amp;username={$username}&amp;password={$password}','printer_page','width=650,height=400'); return false;\" title=\"printer-friendly page\">Click here</a> if you'd like a printer-friendly page for distribution.  (It will open in a new window.)<br /><a href=\"add.php?student\" title=\"add another student\">Add another student</a>?");

	disconnect_sql();
}


elseif(isset($_GET['teacher']))
{
	print("
	<table><form action=\"add.php\" method=\"post\">
	<tr><td class=\"title\">Add a Teacher</td></tr>
	<tr><td style=\"font-weight: bold\">First Name:</td><td><input type=\"text\" name=\"firstname\" maxlength=\"50\" /></td></tr>
	<tr><td style=\"font-weight: bold\">Last Name:</td><td><input type=\"text\" name=\"surname\" maxlength=\"75\" /></td></tr>
	<tr><td style=\"font-weight: bold\">Gender:</td><td><select name=\"gender\"><option value=\"f\" class=\"tdcolour0\">female</option><option value=\"m\" class=\"tdcolour1\">male</option></select></td></tr>
	<tr><td>E-mail address:</td><td><input type=\"text\" name=\"email\" maxlength=\"75\" /></td></tr>
	<tr><td colspan=\"2\" class=\"small\"><em>Note:  Leave the following fields blank to make the system automatically generate them.</em></td></tr>
	<tr><td>Username:</td><td><input type=\"text\" name=\"username\" maxlength=\"30\" /></td></tr>
	<tr><td>Password:</td><td><input type=\"password\" name=\"pass1\" maxlength=\"30\" /></td></tr>
	<tr><td>Confirm password:</td><td><input type=\"password\" name=\"pass2\" maxlength=\"30\" /></td></tr>
	<tr><td><input type=\"submit\" name=\"addteacher\" value=\"add this teacher\" /></td></tr>
	</form></table>
	");

}

elseif(isset($_POST['addteacher']))
{
	connect_sql();

	if(!isset($_POST['firstname']) OR $_POST['firstname'] == "")
	{
		cust_die("You must submit the teacher's first name.");
	}
	$firstname = escape_string($_POST['firstname']);
	
	if(!isset($_POST['surname']) OR $_POST['surname'] == "")
	{
		cust_die("You must submit the teacher's last name.");
	}
	$surname = escape_string($_POST['surname']);
	
	if(!isset($_POST['gender']) OR $_POST['gender'] == "")
	{
		cust_die("You must submit the teacher's gender.");
	}
	elseif($_POST['gender'] != "m" AND $_POST['gender'] != "f"){ cust_die("That is not a valid gender..."); }
	$gender = escape_string($_POST['gender']);
	
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
		$original = $username;

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
	
	add_user($username, $cryptedpass, "2", $firstname, $surname, $gender, $email);
	disconnect_sql();
	
	print("The user was successfully added.  <a href=\"add.php?printer&amp;name={$firstname}%20{$surname}&amp;username={$username}&amp;password={$password}\" target=\"_blank\" onClick=\"window.open('add.php?printer&amp;name={$firstname}%20{$surname}&amp;username={$username}&amp;password={$password}','printer_page','width=650,height=400'); return false;\" title=\"printer-friendly page\">Click here</a> if you'd like a printer-friendly page for distribution.  (It will open in a new window.)<br /><a href=\"add.php?teacher\" title=\"add another teacher\">Add another teacher</a>?");

}


elseif(isset($_GET['class']))
{
	connect_sql();
	
	$teacher_list = @query("SELECT `firstname`, `surname`, `ID` FROM `users` WHERE `type`='2'") or die("Error retrieving the list of teachers.");

	// if there are no teachers, don't allow them to add the class
	if(num_rows($teacher_list) == 0){ cust_die("You must <a href=\"add.php?teacher\" title=\"add a teacher\">add a teacher</a> before you can add classes."); }

	$tdcolour = 0;

	$teachers = "";
	while($row = result($teacher_list))
	{
		$teacher_name = stripslashes($row->firstname) . " " . stripslashes($row->surname);
		$teachers .= "<option value=\"{$row->ID}\" class=\"tdcolour{$tdcolour}\">{$teacher_name}</option>";
		if($tdcolour == 1){ $tdcolour = 0; }
		else{ $tdcolour++; }
	}
	
	disconnect_sql();

$stuff = <<< EOT
<table><form action="add.php" method="post">
<tr><td style="font-weight: bold">Class name:</td><td><input type="text" name="classname" maxlength="75" /></td></tr>
<tr><td style="font-weight: bold">Period:</td><td><select name="period">
EOT;

$tdcolour = 0;

for($i=1;$i<=number_of_periods;$i++)
{
	$stuff .= "<option value=\"{$i}\" class=\"tdcolour{$tdcolour}\">{$i}</option>";
	if($tdcolour == 1){ $tdcolour = 0; }
	else{ $tdcolour++; }
}

$stuff .= <<< EOT
</select></td></tr>
<tr><td style="font-weight: bold">Teacher:</td><td><select name="teacher">{$teachers}</select>
<tr><td style="font-weight: bold">Room:</td><td><input type="text" name="room" maxlength="15" /></td></tr>
<tr><td style="font-weight: bold">Grading periods(separate with commas):</td><td><input type="text" name="semester" maxlength="10" /></td></tr>
<tr><td><input type="submit" name="addclass" value="add the class" /></td></tr>
</form></table>
EOT;
print($stuff);
}


elseif(isset($_POST['addclass']))
{
	if(!isset($_POST['classname']) OR $_POST['classname'] == ""){ cust_die("You must specify the class's name."); }
	if(!isset($_POST['period']) OR $_POST['period'] == ""){ cust_die("You must specify the period the class takes place during."); }
	if(!isset($_POST['teacher']) OR $_POST['teacher'] == ""){ cust_die("You must select a teacher for the class.  (Perhaps you need to <a href=\"add.php?teacher\" alt=\"add a teacher\">add a few</a?>?)"); }
	if(!isset($_POST['room']) OR $_POST['room'] == ""){ cust_die("You must submit which room the class takes place in."); }
	if(!isset($_POST['semester']) OR $_POST['semester'] == ""){ cust_die("You must specify which grading period(s) the class happens during."); }
	
	$classname = escape_string($_POST['classname']);
	$period = escape_string($_POST['period']);
	$teacher = escape_string($_POST['teacher']);
	$room = escape_string($_POST['room']);
	$semester = escape_string($_POST['semester']);
	
	connect_sql();

	@query("INSERT INTO `classes` (`name`, `teacher`, `room`, `period`, `semester`) VALUES ('$classname', '$teacher', '$room',  '$period', '$semester')") or die("Error updating the database.");

	disconnect_sql();
	
	print("The class has been added.  <a href=\"add.php?class\" title=\"add a class\">Add another</a>?");
}


else
{
	any_errors();
	print("Would you like to add a <a href=\"add.php?class\" title=\"add a class\">class</a>, <a href=\"add.php?teacher\" title=\"add a teacher\">teacher</a>, or <a href=\"add.php?student\" title=\"add a student\">student</a>?"); }


print("</div>");

display_copyright();
display_footer();
?>
