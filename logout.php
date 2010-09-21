<?php
/**
 * logs a user out
 *
 * \author Kevin Richardson <borismalcov@users.sourceforge.net>
 * \version $Id: logout.php,v 1.3 2006/07/19 19:54:52 borismalcov Exp $
 */


include("lib/main.php");

if(is_logged_in() == TRUE){ unset($_SESSION['id']); unset($_SESSION['type']); unset($_SESSION['name']); unset($_SESSION['username']); }
header("Location: index.php");
print("You are now being transferred to the main page.  If you aren't, 
please <a href=\"index.php\" alt=\"index\">click here</a>.");



?>
