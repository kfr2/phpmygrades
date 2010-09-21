<?php
/**
 * the mailing system's library
 *
 * \author Kevin Richardson <borismalcov@users.sourceforge.net>
 * \version $Id: maillib.php,v 1.3 2006/07/19 19:54:53 borismalcov Exp $
 */


/**
 * sees if the user has any new mail
 */
function check_mail()
{
	$user_id = $_SESSION['id'];
	
	connect_sql();
	$query = @query("SELECT * FROM `mail` WHERE `read`='0' AND `deleted`='0' AND `to`='$user_id'") or die("Error checking the database.");
	$number = num_rows($query);
	disconnect_sql();
	
	return($number);

}


?>
