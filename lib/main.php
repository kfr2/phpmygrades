<?php
/**
 * This file calls the assorted library files into one place so they're easier to manager and include
 *
 * \author Kevin Richardson <borismalcov@users.sourceforge.net>
 * \version $Id: main.php,v 1.3 2006/07/19 19:54:53 borismalcov Exp $
 */

session_start();

if(is_file("include/config.php")){ include("include/config.php"); }

include("authlib.php");
include("sqllib.php");
include("printlib.php");
include("maillib.php");
include("newslib.php");
include("xmllib.php");

?>
