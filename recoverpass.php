<?php
/**
 * allows a user to recover his or her password
 *
 * \author Kevin Richardson <borismalcov@users.sourceforge.net>
 * \version $Id: recoverpass.php,v 1.5 2006/07/19 19:54:52 borismalcov Exp $
 */

include("lib/main.php");

display_header("phpmygrades: forget your password?");
print("<div class=\"title\">recover your password</div>\n<div class=\"container2\">");

if(isset($_GET['email']))
{
	print("Type your e-mail address into the box below and click on the send button.  You'll be sent an e-mail containing information on how to reset your password.<br /><br /><table><form action=\"recoverpass.php\" method=\"post\"><tr><th>E-mail address:</th><td><input type=\"text\" name=\"email\" /></td></tr><tr><td><input type=\"submit\" name=\"sendemail\" value=\"send\" /></td></tr></form></table>");
}

elseif(isset($_POST['sendemail']))
{
	$email = escape_string($_POST['email']);
	if(is_valid_email($email) == FALSE){ cust_die("That is not a valid e-mail address."); }
	
	// see if it's in the database
	connect_sql();
	$query = "SELECT 1 FROM `users` WHERE `email`='$email' LIMIT 1";
	$query = @query($query) or die("Error checking the database.");
	if(num_rows($query) == 0){ cust_die("No user has that e-mail address."); }

	$string = gen_string($email);
	
	$there = "SELECT 1 FROM `pass_recovery` WHERE `email`='$email' LIMIT 1";
	$there = @query($there) or die("Error checking the database.");
	if(num_rows($there) == 0){ $query = "INSERT INTO `pass_recovery` (`email`, `hash`) VALUES ('$email', 'string')"; }
	else{ $query = "UPDATE `pass_recovery` SET `hash`='$string' WHERE `email`='$email' LIMIT 1"; }
	
	@query($query) or die("Error updating the database.");
	
	disconnect_sql();
	
	$link = server_root . "recoverpass.php?confirm&e={$email}&s={$string}";

	$subject = "Instructions for changing your phpmygrades password.";
	$message = <<< EOT
You have requested the change of the password linked to your phpmygrades account.\n
If you want to change your password, please click on the following link: {$link}\n
If you don't want to change it, kindly ignore this e-mail.
EOT;
	@mail($email, $subject, $message, "From: phpmygrades") or die("Error sending the e-mail.");
	
	print("You have been sent an e-mail.  Follow the instructions inside it to change your password.");
}

elseif(isset($_GET['confirm']))
{
	if(!isset($_GET['e']) OR $_GET['e'] == ""){ cust_die("Make sure you followed the correct link."); }
	if(!isset($_GET['s']) OR $_GET['s'] == ""){ cust_die("Make sure you followed the correct link."); }
	
	$email = escape_string($_GET['e']);		if(is_valid_email($email) == FALSE){ cust_die("..that is not a valid e-mail address."); }
	$string = escape_string($_GET['s']);
	
	connect_sql();
	
	$in_the_database = "SELECT 1 FROM `pass_recovery` WHERE `email`='$email' LIMIT 1";
	$in_the_database = @query($in_the_database) or die("Error checking the database."); 
	if(num_rows($in_the_database) == 0){ cust_die("That e-mail address is not in the database."); }
	
	$real_string = "SELECT `hash` FROM `pass_recovery` WHERE `email`='$email' LIMIT 1";
	$real_string = @query($real_string) or die("Error checking the database.");
	$real_string = result($real_string);
	$real_string = $real_string->hash;
	
	disconnect_sql();
	
	if($real_string != $string){ cust_die("Make sure you followed the correct link."); }
	
	print("Input your new password twice, click \"change password\", and it'll be changed.");
	print("\n<table>\n<form action=\"recoverpass.php\" method=\"post\">\n<tr><td>Password:</td><td><input type=\"password\" name=\"pass1\" /></td></tr>\n<tr><td>Password (again):</td><td><input type=\"password\" name=\"pass2\" /></td></tr>\n<tr><td>\n<input type=\"hidden\" name=\"s\" value=\"{$string}\" />\n<input type=\"hidden\" name=\"e\" value=\"{$email}\" />\n<input type=\"submit\" name=\"changepassword\" value=\"change password\" /></td></tr>\n</form>\n</table>\n");
	
	
}

elseif(isset($_POST['changepassword']))
{
	if(!isset($_POST['e']) OR $_POST['e'] == ""){ cust_die("You shouldn't mess with the data.  ;D"); }
	if(!isset($_POST['s']) OR $_POST['s'] == ""){ cust_die("You shouldn't mess with the data.  ;D"); }
	
	if(!isset($_POST['pass1']) OR $_POST['pass1'] == ""){ cust_die("You must submit the password you want."); }
	if(!isset($_POST['pass2']) OR $_POST['pass2'] == ""){ cust_die("You must submit the password twice."); }
	if($_POST['pass1'] != $_POST['pass2']){ cust_die("The passwords must match."); }
	$cryptpass = md5(md5(escape_string($_POST['pass1'])));
	
	$email = escape_string($_POST['e']);		if(is_valid_email($email) == FALSE){ cust_die("..that is not a valid e-mail address."); }
	$string = escape_string($_POST['s']);
	
	connect_sql();
	
	$in_the_database = "SELECT 1 FROM `pass_recovery` WHERE `email`='$email' LIMIT 1";
	$in_the_database = @query($in_the_database) or die("Error checking the database."); 
	if(num_rows($in_the_database) == 0){ cust_die("That e-mail address is not in the database."); }
	
	$real_string = "SELECT `hash` FROM `pass_recovery` WHERE `email`='$email' LIMIT 1";
	$real_string = @query($real_string) or die("Error checking the database.");
	$real_string = result($real_string);
	$real_string = $real_string->hash;
	
	if($real_string != $string){ cust_die("You shouldn't mess with the data.  ;D"); }
	
	// update their password
	@query("UPDATE `users` SET `password`='$cryptpass' WHERE `email`='$email' LIMIT 1") or die("Error updating the database.");
	
	disconnect_sql();
	
	print("Your password has been changed.  <a href=\"login.php\" title=\"login\">Login here</a>.");
}

else
{
	any_errors();
	
	print("<p>Did you forget your password?  No big deal, you can change it in a few different ways.");
	print("<ul><li>If you have entered your email address on the options page you can <a href=\"recoverpass.php?email\" title=\"get a new password\">get a new password generated for you</a>.</li><li>If you haven't entered your e-mail address on the options page you'll need to get your administrator to reset it for you.  Talk to him or her about it the next chance you get.</li></ul>");
}

print("<hr class=\"mainpagehr\" /><a href=\"index.php\" title=\"index page\">index page</a>");

print("</div>");
display_copyright();
display_footer();
?>
