<?php
/**
 * phpmygrade's authentication lib
 *
 * \author Kevin Richardson <borismalcov@users.sourceforge.net>
 * \version $Id: authlib.php,v 1.7 2006/07/19 19:54:53 borismalcov Exp $
 */

/**
 * See if a user is logged in or not.
 *
 * returns TRUE if the user is logged in; FALSE if he or she isn't.
 */
function is_logged_in()
{
	if(isset($_SESSION['id'])){ return TRUE; }
	else{ return FALSE; }
}

/**
 * Return the user's type
 *
 * if $id is submitted, return what type the user is; else, get the user from the session.
 * Can return "user," "parent," "teacher," or "admin"
 */
function user_type($id="")
{
	// look up the user's type in the database
	if($id != "")
	{
		$user_type = @query("SELECT `type` FROM `users` WHERE `ID`='$id' LIMIT 1") or die("Error checking the database.");
		while($row = result($user_type))
		{
			switch($row->type)
			{
				case 1: return "user"; break;
				case 2: return "teacher"; break;
				case 3: return "admin"; break;
				case 4: return "parent"; break;
			}
		}
	}
	
	// else, get it from his or her session
	else
	{
		if(isset($_SESSION['type']))
		{
			switch($_SESSION['type'])
			{
				case 1: return "user"; break;
				case 2: return "teacher"; break;
				case 3: return "admin"; break;
				case 4: return "parent"; break;
			}
		}

		else
		{
			return "";
		}
	}
}

/**
 * See if an email address if in a valid format
 *
 * returns TRUE if it is; false if it isn't
 */
function is_valid_email($email)
{
	$regex = "^[\'+\\./0-9A-Z^_\`a-z{|}~\-]+@[a-zA-Z0-9_\-]+(\.[a-zA-Z0-9_\-]+){1,6}$";
	if(!eregi($regex, $email)){ return FALSE; }
	else{ return TRUE; }	
}

/**
 * See if a username is taken or not
 *
 * returns TRUE if $username is already taken; else, FALSE
 */
function is_username_taken($username)
{
	$query = @query("SELECT 1 FROM `users` WHERE `username`='$username' LIMIT 1") or die("Error checking the database.");

	if(num_rows($query) != 0){ return TRUE; }
	else{ return FALSE; }
}

/**
 * generates the string that'll be used to confirm the user's request to change their password
 */
function gen_string($email)
{
	$one = crypt($email, "k3");
	$two = strrev($email);
	$three = md5($one . $two);	$three .= md5(strrev($three));	$three = strrev($three);
	$four = md5(time());
	$four = rand(5,13) . $four;
	$five = $three . $four;	$five = strrev($five);
	$result = substr($five, 0, 15);
	
	return($result);
}


/**
 * adds a user to the database
 *
 * $username is the user's username.
 * $cryptedpass is his or her password encrypted twice using md5().  i.e. md5(md5($password))
 * $type is the user's type: 1 for "user", 2 for "teacher", 3 for "admin", 4 for "parent"
 * $firstname and $surname are the user's first and surnames
 * $email is the user's e-mail address
 */
function add_user($username, $cryptedpass, $type, $firstname, $surname, $gender, $email)
{
	// see if there is already a user with the username of '$username'
	$query = "SELECT 1 FROM `users` WHERE `username`='$username'";
	$query = @query($query) or die("Error checking the database.");
	if(num_rows($query) > 0){ cust_die("The requested username is already in the database.  Please select another one."); }
	
	$add_query = "INSERT INTO `users` (`username`, `password`, `type`, `firstname`, `surname`, `gender`, `email`) VALUES ('$username', '$cryptedpass', '$type', '$firstname', '$surname', '$gender', '$email')";
	query($add_query) or die("Error adding the user's information into the database.");
}


/**
 * redirects the user to a page with an error displayed
 *
 * If $_SESSION['not_this_page'] is set, rediect to the login page.  Otherwise, redirect to the 
 * same page.
 */
function cust_die($message)
{
	$_SESSION['error'] = $message;
	if(isset($_SESSION['not_this_page'])){ header("Location: login.php"); unset($_SESSION['not_this_page']); }
	else{ header("Location: {$_SERVER['PHP_SELF']}"); }
	die();
}

/**
 * Used to display any errors (if they occur)
 *
 * Checks for $_SESSION['error'] (which is set by using cust_die()).  If it's set, print the error
 * message.
 */
function any_errors()
{
	if(isset($_SESSION['error']))
	{
		print("<p style=\"color: #ff0000; weight: bold;\">{$_SESSION['error']}</p>");
		unset($_SESSION['error']);
	}
}

/**
 * see if a teacher teaches a certain class
 *
 * returns TRUE if $teacherid teaches class $classid; else, FALSE
 */
function teacher_teaches($teacherid, $classid)
{
	$query = "SELECT 1 FROM `classes` WHERE `ID`='$classid' AND `teacher`='$teacherid'";
	$query = @query($query) or die("Error checking the database.");
	if(num_rows($query) > 0){ return TRUE; }
	else{ return FALSE; }
}

/**
 * sees if $user exists
 *
 * TRUE if true, FALSE if false
 */
function is_valid_user($user)
{
	$query = @query("SELECT 1 FROM `users` WHERE `ID`='$user' LIMIT 1") or die("Error checking the database.");
	if(num_rows($query) > 0){ return TRUE; }
	else{ return FALSE; }
}

/**
 * returns TRUE if $user is a teacher
 * \todo compress the following into a single function (like is_user_type($user, $type), etc)
 */
function is_teacher($user)
{
	$query = @query("SELECT 1 FROM `users` WHERE `ID`='$user' AND `type`='2' LIMIT 1") or die("Error checking the database.");
	if(num_rows($query) > 0){ return TRUE; }
	else{ return FALSE; }
}

/**
 * returns TRUE if $user is a student
 */
function is_student($user)
{
	$query = @query("SELECT 1 FROM `users` WHERE `ID`='$user' AND `type`='1' LIMIT 1") or die("Error checking the database.");
	if(num_rows($query) > 0){ return TRUE; }
	else{ return FALSE; }
}

/**
 * returns TRUE if $user is a parent
 */
function is_parent($user)
{
	$query = @query("SELECT 1 FROM `users` WHERE `ID`='$user' AND `type`='4' LIMIT 1") or die("Error checking the database.");
	if(num_rows($query) > 0){ return TRUE; }
	else{ return FALSE; }
}

/**
 *  returns the number of current system alerts.
 *
 * Used to tell the admins if they have alerts to look at or not..
 * \todo Work on these.
 */
function number_of_system_alerts()
{
	// check for X failed logins from IP Y within Z minutes
	// check for updates to phpmygrades
	// etc...
	return(0);
}

?>
