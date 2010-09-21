<?php
/**
 * the index file.
 *
 * It displays the login form or the user's menu/mainpage
 *
 * \author Kevin Richardson <borismalcov@users.sourceforge.net>
 * \version $Id: index.php,v 1.3 2006/07/19 19:54:52 borismalcov Exp $
 */

include("lib/main.php");

display_header("phpmygrades: main");

if(is_logged_in() == TRUE)
{
	display_mainpage();
}

else
{
print("
<div class=\"title\">phpmygrades</div>

<div class=\"container2\">
<div class=\"right\"><a href=\"news.php?xml&amp;class=0\" title=\"RSS feed for global news\"><img src=\"images/xml.gif\" alt=\"RSS Feed\" /></a></div>
<div class=\"news_background\">
");

print_news_html(0, 3);

print("
</div>
<hr class=\"mainpagehr\" />
<table>
<form action=\"login.php\" method=\"post\" name=\"login_form\">
<tr><td>username:</td><td><input type=\"text\" name=\"user\" maxlength=\"30\" /></td></tr>
<tr><td>password:</td><td><input type=\"password\" name=\"pass\" maxlength=\"70\" /></td></tr>
<tr><td><input type=\"submit\" name=\"login\" value=\"login\" /></td></tr>
<tr><td colspan=\"2\" class=\"right\"><small><i><a href=\"recoverpass.php\" title=\"forget your password?\">Forget your password</a>?</i></small></td></tr>
</form>
</table>
</div>
");
	
	display_copyright();
	display_footer();
}

?>
