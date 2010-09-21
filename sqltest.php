<?php
/**
 * used to test out the various sql commands
 *
 * Useful to test various DBMSs
 *
 * \author Kevin Richardson <borismalcov@users.sourceforge.net>
 * \version $Id: sqltest.php,v 1.3 2006/07/19 19:54:52 borismalcov Exp $
 */

include("lib/main.php");

connect_sql();
$info = query("SELECT * FROM `users`") or die("Error.");
while($row = result($info, 0))
{
	print($row->ID . ":" . $row->username . ":" . $row->firstname . 
" " . $row->surname . "<br />\n");
}
disconnect_sql();


?>
