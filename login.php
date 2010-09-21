<?php
/**
 * Processes a user's request to login, or prints the form to do so.
 *
 * \author Kevin Richardson <borismalcov@users.sourceforge.net>
 * \version $Id: login.php,v 1.4 2006/07/19 19:54:52 borismalcov Exp $
 */

include("lib/main.php");

if(isset($_POST['login']))
{
	if(isset($_POST['user']) AND $_POST['user'] != ""){ $user = escape_string($_POST['user']); } else{ cust_die("You need to submit your username."); }
	if(strlen($user) > 30){ cust_die("Usernames may only be up to 30 characters long."); }
	
	if(isset($_POST['pass']) AND $_POST['pass'] != ""){ $pass = escape_string($_POST['pass']); } else{ cust_die("You need to submit your password."); }
	if(strlen($pass) > 70){ cust_die("Is your password really <i>that</i> long?"); }
	$pass = md5(md5($pass));
	
	// see if the pair is found
	connect_sql();

	$results = @query("SELECT `ID`, `type`, `firstname`, `surname` FROM `users` WHERE `username`='$user' AND `password`='$pass' LIMIT 1") or die("Error.");

	// if the login failed, log it and tell the user
	if(num_rows($results) == 0)
	{
		$timestamp = time();
		$ip = $_SERVER['REMOTE_ADDR'];
		
		@query("INSERT INTO `failed` (`user`, `timestamp`, `ip`) VALUES ('$user', '$timestamp', '$ip')");
		
		cust_die("Your login attempt failed.  Please try again.");
	}
	
	
	// if it did not fail, let the user access the system
	$timestamp = time();
	$ip = $_SERVER['REMOTE_ADDR'];
		
	@query("INSERT INTO `logins` (`user`, `timestamp`, `ip`) VALUES ('$user', '$timestamp', '$ip')");

	while($row = result($results))
	{
		$_SESSION['type'] = $row->type;
		$_SESSION['id'] = $row->ID;
		$_SESSION['name'] = stripslashes($row->firstname) . " " . stripslashes($row->surname);
		$_SESSION['username'] = $user;
	}

	header("Location: index.php");
	print("You are now logged in.  If you aren't transferred automatically, please <a href=\"index.php\">click here</a>.");
	

	disconnect_sql();
}

else
{
	display_header("phpmygrades: login");
	print("<div class=\"title\">login</div><div class=\"container2\">");

	any_errors();

print("
<table>
<form action=\"login.php\" method=\"post\" name=\"login_form\">
<tr><td>username:</td><td><input type=\"text\" name=\"user\" maxlength=\"30\" /></td></tr>
<tr><td>password:</td><td><input type=\"password\" name=\"pass\" maxlength=\"70\" /></td></tr>
<tr><td><input type=\"submit\" name=\"login\" value=\"login\" /></td></tr>
<tr><td colspan=\"2\" class=\"right\"><small><i><a href=\"recoverpass.php\" title=\"forget your password?\">Forget your password</a>?</i></small></td></tr>
</form>
</table>
");

}

print("</div>");

display_copyright();
display_footer();

?>
