<?php
/**
 * phpmygrades's upgrade script.
 *
 * It (mainly) just updates the database.
 *
 * \author Kevin Richardson <borismalcov@users.sourceforge.net>
 * \version $Id: upgrade.php,v 1.6 2006/07/19 19:54:52 borismalcov Exp $
 */

include("lib/main.php");

if(!is_file("include/config.php")){ die("phpmygrades is not installed.  Perhaps you want to run the <a href=\"install.php\" title=\"installation script\">installation script</a> instead?"); }

$this_version = "0.1.3";

// see if the latest version is already installed.
if(current_version == $this_version){ die("You already have this version installed."); }


connect_sql();

// do different things depending upon what version is currently installed

$sql_file = "include/" . current_version . ".sql";

$handle = @fopen($sql_file, "r") or die("Error opening SQL file.");
$sql_file = fread($handle, filesize($sql_file));
fclose($handle);

$setup_query = explode(";\n", $sql_file);

foreach($setup_query as $query)
{
	@query($query) or die("Error updating the database.");
}

disconnect_sql();


// once everything is done, update the config. file
$handle = @fopen("include/config.php", "r+") or die("Error updating config.php.  Please load it manually (in a text editor) and change current_version's value to {$this_version}");
$content = fread($handle, filesize("include/config.php"));

// do more checking on this later.  It is, however, not needed now.

switch(current_version)
{
	case "0.1.0":
		$content = str_replace("0.1.0", "0.1.2", $content);
		$content = str_replace("//", "", $content);
	break;

	case "0.1.1":
		$content = str_replace("0.1.1", "0.1.2", $content);
	break;

	case "0.1.2":
		$content = str_replace("0.1.2", "0.1.3", $content);
	break;
}

fwrite($handle, $content);
fclose($handle);

print("Done.  Delete upgrade.php and install.php.");

?>
