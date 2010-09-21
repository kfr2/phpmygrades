<?php
/**
 * phpmygrades's installation script.
 *
 * It sets up the database, an administrator account, etc.
 *
 * \author Kevin Richardson <borismalcov@users.sourceforge.net>
 * \version $Id: install.php,v 1.7 2006/07/19 19:54:52 borismalcov Exp $
 */

include("lib/main.php");

if(is_file("include/config.php")){ die("phpmygrades is already installed."); }


if(isset($_POST['install']))
{
	if(isset($_POST['servertype']) AND $_POST['servertype'] != ""){ $server_type = escape_string($_POST['servertype']); } else{ cust_die("You must submit the database server type."); }
	if(isset($_POST['server']) AND $_POST['server'] != ""){ $server = escape_string($_POST['server']); } else{ cust_die("You must submit the server's address."); }
	if(isset($_POST['databaseuser']) AND $_POST['databaseuser'] != ""){ $username = escape_string($_POST['databaseuser']); } else{ cust_die("You must submit which user you'd like to connect to the database as."); }
	if(isset($_POST['databasepassword']) AND $_POST['databasepassword'] != ""){ $password = escape_string($_POST['databasepassword']); } else{ cust_die("You must submit the username's password."); }
	if(isset($_POST['databasename']) AND $_POST['databasename'] != ""){ $database = escape_string($_POST['databasename']); } else{ cust_die("You must submit the name of the database you'd like to use."); }
	
	if(isset($_POST['username']) AND $_POST['username'] != ""){ $admin_username = escape_string(htmlspecialchars($_POST['username']));} else{ cust_die("You must submit the admin's username."); }
	if(isset($_POST['pass1']) AND isset($_POST['pass2']) && $_POST['pass1'] != $_POST['pass2']){ cust_die("Please make sure the admin's passwords match."); }
	$admin_password = escape_string(htmlspecialchars($_POST['pass1']));
	if(strlen($admin_password) < 5){ cust_die("Your admin password must be at least 6 characters long."); }
	if($admin_password == $admin_username){ cust_die("Your admin password may not be your admin username."); }
	$admin_password = md5(md5($admin_password));
	
	if(isset($_POST['realname']) AND $_POST['realname'] != ""){ $realname = escape_string(htmlspecialchars($_POST['realname'])); } else{ $realname = ""; }
	if(isset($_POST['emailaddress']) AND $_POST['emailaddress'] != ""){ $emailaddress = escape_string(htmlspecialchars($_POST['emailaddress'])); } else{ $emailaddress = ""; }
	
	// split the name into its pieces...
	list($firstname, $surname) = explode(" ", $realname);

	// get the set up file
	$sql_file = "include/setup.sql";
	$handle = fopen($sql_file, "r");
	$sql_file = fread($handle, filesize($sql_file));
	fclose($handle);
	
	// break it down into several queries
	$setup_query = explode(";\n", $sql_file);

	
	define("server_type", "$server_type");
	define("server", "$server");
	define("username", "$username");
	define("password", "$password");
	define("database", "$database");
	
	// add the SQL tables
	connect_sql();

	foreach($setup_query as $thing)
	{
		if($thing != "")
		{	
			@query($thing) or die("Error setting up the database tables");
		}
	}

	
	@query("INSERT INTO `users` (`username`, `password`, `type`, `firstname`, `surname`, `email`) VALUES ('$admin_username', '$admin_password', '3', '$firstname', '$surname', '$emailaddress')");
	disconnect_sql();

	// generate the configuration file
	$handle = @fopen("include/config.php", "w");

$content = <<< EOT
<?php\n
define("current_version", "0.1.3");

define("server_type", "$server_type");
define("server", "$server");
define("username", "$username");
define("password", "$password");
define("database", "$database");
define("server_root", "");

define("school_name", "");
define("dateformat", "");
define("timeformat", "");

define("number_of_periods", 4);
define("number_of_semesters", 4);

define("enable_forums", 0);
define("current_semester", 1);

?>
EOT;

	display_header("phpmygrades:  installer");
	print("<div class=\"title\">phpmygrades installer</div>");
	print("<div class=\"container2\">");



	if(is_writable("include/") == FALSE)
	{
		print("Error writing the configuration file.  (The web server probably does not have write access to the include directory.)  Please create config.php in the include/ directory, and fill it with the following:<blockquote>" . str_replace(">", "&gt;", str_replace("<", "&lt;", $content)) . "</blockquote>After doing so, delete this file (install.php) and login at the <a href=\"index.php\">main page</a>");
		die();
	}

	@fwrite($handle, $content) or die("Error writing the configuration file.  (The web server probably does not have write access to the include directory.)  Please create config.php in the include/ directory, and fill it with the following:<blockquote>" . str_replace(">", "&gt;", str_replace("<", "&lt;", $content)) . "</blockquote>After doing so, delete this file (install.php) and login at the <a href=\"index.php\">main page</a>");
	fclose($handle);


	print("phpmygrades is now set up.  Delete this file and then login at the <a href=\"index.php\">main page</a>.");
	

		
}

else
{
	display_header("phpmygrades:  installer");
        print("<div class=\"title\">phpmygrades installer</div>");
        print("<div class=\"container2\">");

	any_errors();
	
print("
<p>Welcome to the phpmygrades installer.  Please supply me with the following information, and I'll install the system for you.</p>
<table>
<form action=\"install.php\" method=\"post\" name=\"setup\">
<tr><th colspan=\"2\">Database Information</th></tr>
<tr><td>type:</td><td><select name=\"servertype\"><option selected value=\"mysql\">MySQL<option value=\"postgresql\">PostgreSQL -- not tested(please submit any bugs)</select><td></tr>
<tr><td>server:</td><td><input type=\"text\" name=\"server\" value=\"localhost\" /></td></tr>
<tr><td>username:</td><td><input type=\"text\" name=\"databaseuser\" /></td></tr>
<tr><td>password:</td><td><input type=\"password\" name=\"databasepassword\" /></td></tr>
<tr><td>database:</td><td><input type=\"text\" name=\"databasename\" value=\"phpmygrades\" /></td></tr>
<tr><td colspan=\"2\"><br /><hr /><br /></td></tr>
<tr><th colspan=\"2\">Admin Account</th></tr>
<tr><td>username:</td><td><input type=\"text\" name=\"username\" value=\"admin\" /></td></tr>
<tr><td>password:</td><td><input type=\"password\" name=\"pass1\" /></td></tr>
<tr><td>password (again):</td><td><input type=\"password\" name=\"pass2\" /></td></tr>
<tr><td>real name (first name) (surname):</td><td><input type=\"text\" name=\"realname\" /></td></tr>
<tr><td>e-mail address:</td><td><input type=\"text\" name=\"emailaddress\" /></td></tr>
<tr><td><input type=\"submit\" name=\"install\" value=\"Install\" /></td></tr>
</form>
</table>
");
}
?>

</div>

<?php
display_copyright();
display_footer();
?>
