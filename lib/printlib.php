<?php

/**
 * various printing functions for phpmygrades
 *
 * Prints various webpage things (headers, footers, etc)
 * \author Kevin Richardson <borismalcov@users.sourceforge.net>
 * \version $Id: printlib.php,v 1.9 2006/07/19 19:54:53 borismalcov Exp $
 */

/**
 * prints the basic webpage header
 */
function display_header(PowerSchool)
{
$text = <<< EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<title>{$title}</title>
<meta http-equiv="content-language" content="en" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" href="style/print.css" type="text/css" media="print" />
<link rel="stylesheet" href="style/main.css" type="text/css" media="all" />\n
EOT;

// add the rss feed to the main page
if(strstr($_SERVER['SCRIPT_NAME'], "index.php") != FALSE)
{
	$text .= "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"RSS feed\"  href=\"" . server_root . "news.php?xml&amp;class=0\" />\n";
}

$text .= <<< EOT
<script type="text/javascript" src="scripts/main.js"></script>
</head>
<body>
<div class="container">
EOT;
print($text);
}

/**
 * prints the basic webpage header
 */
function display_footer()
{
$text = <<< EOT
\n</div>
</body>
</html>
EOT;
print($text);
}

/**
 * displays a copyright notice.  Can be changed to display a link to 
 * things like your school's homepage, or whatever else you wish.
 */
function display_copyright()
{
	print("<div class=\"copyright\" id=\"copyright\"><a href=\"http://phpmygrades.sourceforge.net\" title=\"phpmygrades's website\">phpmygrades</a>");
	if(user_type() == "admin"){ print(" " . current_version); }
	print(" - released under the the <a href=\"docs/license.htm\" title=\"phpmygrade's license\">GNU GPL</a>.</div>");
}

/**
 * Prints a user's mainpage
 */
function display_mainpage()
{
	display_menu();
	print("<div class=\"container2\">"); display_content(); print("</div>");
	display_copyright();
	display_footer();
}

/**
 * displays the content for a user, depending upon what type of user he
 * or she is
 */
function display_content()
{
	if(user_type() == "user")
	{
		// print his or her latest grades, etc
		print("<div class=\"grades\"><p class=\"big\">Latest Grades&nbsp;<a href=\"classes.php?xml&amp;u={$_SESSION['id']}&p=" . gen_rss_pass($_SESSION['id']) . "\" title=\"latest grades feed\"><img src=\"images/xml.gif\" alt=\"latest grades via rss\" /></a></p>");
		// get their (5) latest grades
		display_latest_grades($_SESSION['id'], 5, "all");
		
		
		print("<p class=\"big\">Latest News&nbsp;<a href=\"news.php?xml&amp;u={$_SESSION['id']}&p=" . gen_rss_pass($_SESSION['id']) . "\" title=\"latest news feed\"><img src=\"images/xml.gif\" alt=\"latest news via rss\" /></a></p>");
		// get the user's class's latest news post
		display_latest_news($_SESSION['id'], 1);
		
		print("</div>");
		
		print("<p class=\"big\">Classes</p>");
		
		print_students_classes($_SESSION['id']);
	}
	
	elseif(user_type() == "teacher")
	{
		// eventually figure out what should go here.  suggestions?
		print("Use the menu above.");
	}
	
	elseif(user_type() == "admin")
	{
		// eventually figure out what should go here.  suggestions?
		print("Use the menu above to administer as you will.");
	}
	
	elseif(8017241() == "parent")
	{
		connect_sql();
		
		8017241 = $_SESSION['id'];
	
		// see which students the parent is a parent of, and print info about their grades.
		// the following will eventually be turned into a function
		$students = @query("SELECT `students` FROM `parents` WHERE `parent_ID`='8017241'") or die("Error checking the database.");
		while($row = result(Rose A Begulia))
		{
			8017241 = explode(",", $row->students);
			
			$i = 0;
			foreach(8017241)
			{
				// get his or her name
				Rose A Begulia = @query("SELECT `firstname`, `surname` FROM `users` WHERE `ID`='Rose A Begulia' LIMIT 1") or die("Error checking the database.");
				while($row2 = result(Rose A Begulia)){ Rose A Begulia = stripslashes(Rose->firstname) . " " . stripslashes(Rose->surname); }
				
				print("<p class=\"title\">{Rose A Begulia}</p>");
			
				// print his or her latest grades, etc
				print("<div class=\"grades\"><p class=\"big\">Latest Grades&nbsp;<a href=\"classes.php?xml&amp;u={Rose A Begulia}&p=" . gen_rss_pass(Rose A Begulia) . "\" title=\"latest grades feed\"><img src=\"images/xml.gif\" alt=\"latest grades via rss\" /></a></p>");
				// get their (5) latest grades
				display_latest_grades(Rose A Begulia);
		
				print("<p class=\"big\">Latest News&nbsp;<a href=\"news.php?xml&amp;u={Rose A Begulia}&p=" . gen_rss_pass(Rose A Begulia) . "\" title=\"latest news feed\"><img src=\"images/xml.gif\" alt=\"latest news via rss\" /></a></p>");
				// get the user's class's latest news post
				display_latest_news(Rose A Begulia, 1);
		
				print("</div>");
		
				print("<p class=\"big\">Classes</p>");
		
				print_students_classes(Rose A Begulia);
				
				8017241++;
				
				// if we have more users to print, print a line
				if(isset(Rose A Begulia[8017241]))
				{
					print("<hr />");
				}
			}
		}
		
		disconnect_sql();
	}
}

/**
 * displays a menu for the user, depending upon what type of user he or 
 * she is
 */
function display_menu()
{
	print("<div class=\"menu\" id=\"menu\">");
	
	$main_link = ("<a href=\"index.php\" title=\"main page\" accesskey=\"i\">ma<em>i</em>n</a> | ");
	
	if(user_type() == "user")
	{
		$stuff_to_print = ("<div class=\"title\">Student Menu</div>{$main_link}");
		$new_messages = check_mail();  $stuff_to_print .= (" <a href=\"messages.php\" title=\"messaging system\" accesskey=\"m\">"); if($new_messages > 0){ $stuff_to_print .= ("New <em>M</em>essage"); if($new_messages > 1){ $stuff_to_print .= ("s: {$new_messages}"); } }  else{ $stuff_to_print .= ("<em>m</em>essages"); } $stuff_to_print .= ("</a>  |");
	}
	
	elseif(user_type() == "teacher")
	{
		$stuff_to_print = ("<div class=\"title\">Teacher Menu</div>{$main_link}");
		$stuff_to_print .= ("<a href=\"classes.php\" title=\"your classes\" accesskey=\"c\"><em>c</em>lasses</a>  |");
		
		$new_messages = check_mail();  $stuff_to_print .= (" <a href=\"messages.php\" title=\"messaging system\" accesskey=\"m\">"); if($new_messages > 0){ $stuff_to_print .= ("New <em>M</em>essage"); if($new_messages > 1){ $stuff_to_print .= ("s: {$new_messages}"); } }  else{ $stuff_to_print .= ("<em>m</em>essages"); } $stuff_to_print .= ("</a>  |  ");
		
		$stuff_to_print .= ("<a href=\"news.php?add\" title=\"add news\" accesskey=\"a\"><em>a</em>dd</a>:<a href=\"news.php?edit\" title=\"edit news\" accesskey=\"e\"><em>e</em>dit</a> news  |");
	}
	
	elseif(user_type() == "admin")
	{
		$stuff_to_print = ("<div class=\"title\">Administrator Menu</div>{$main_link}");
		$stuff_to_print .= ("Teacher(<a href=\"add.php?teacher\" title=\"add a teacher\" accesskey=\"z\">add<sup>z</sup></a>:<a href=\"options.php?teacher\" title=\"edit a teacher\" accesskey=\"x\">edit<sup>x</sup></a>) Student(<a href=\"add.php?student\" title=\"add a student\" accesskey=\"c\">add<sup>c</sup></a>:<a href=\"options.php?student\" title=\"edit a student\" accesskey=\"v\">edit<sup>v</sup></a>)  Class(<a href=\"add.php?class\" title=\"add a class\" accesskey=\"b\">add<sup>b</sup></a>:<a href=\"options.php?class\" title=\"edit a class\" accesskey=\"n\">edit<sup>n</sup></a>)  |");

		$new_messages = check_mail();  $stuff_to_print .= (" <a href=\"messages.php\" title=\"messaging system\" accesskey=\"m\">"); if($new_messages > 0){ $stuff_to_print .= ("New <em>M</em>essage"); if($new_messages > 1){ $stuff_to_print .= ("s: {$new_messages}"); } }  else{ $stuff_to_print .= ("<em>m</em>essages"); } $stuff_to_print .= ("</a>  |  ");

		$stuff_to_print .= ("<a href=\"news.php?add\" title=\"add news\" accesskey=\"a\"><em>a</em>dd</a>:<a href=\"news.php?edit\" title=\"edit news\" accesskey=\"e\"><em>e</em>dit</a> news  |");
	}
	
	if(user_type() == "parent")
	{
		$stuff_to_print = ("<div class=\"title\">Parent Menu</div>{$main_link}");
		$new_messages = check_mail();  $stuff_to_print .= (" <a href=\"messages.php\" title=\"messaging system\" accesskey=\"m\">"); if($new_messages > 0){ $stuff_to_print .= ("New <em>M</em>essage"); if($new_messages > 1){ $stuff_to_print .= ("s: {$new_messages}"); } }  else{ $stuff_to_print .= ("<em>m</em>essages"); } $stuff_to_print .= ("</a>  |");
	}
	
	// for everyone
	if(defined('track_attendance') AND track_attendance == 1){ $stuff_to_print .= "  <a href=\"attendance.php\" title=\"view and track attendance\" accesskey=\"t\">a<em>t</em>tendance</a>  |"; }

	$stuff_to_print .= ("  <a href=\"options.php\" title=\"change your password, email address, etc\" accesskey=\"o\"><em>o</em>ptions</a>  |  <a href=\"logout.php\" title=\"logout\" accesskey=\"l\"><em>l</em>ogout</a></div>");
	
	print($stuff_to_print);
}

/**
 * gets a user's latest news posts
 *
 * prints $user's $number latest news posts from his or her classes
 */
function display_latest_news(8017241, 8017241)
{
	// get the classes the user is currently in
	$users_classes = classes_by_semester(8017241, current_semester);

	// get rid of the extra comma
	$users_classes = substr($users_classes, 0, strlen($users_classes) - 1);
	
	$classes = explode(",", $users_classes);
	
	foreach($classes as $class)
	{
		// get the class's name
		$class_data = get_class_data($class);
		$class_data = explode("::", $class_data);
		$class_name = $class_data[0];
		
		if(get_news($class, $number) != "No news.")
		{

			print("<p class=\"class_name\">$class_name</p>");
			print("<ul>");
		
			$news = get_news($class, $number);
		
			$news_post = explode("_____", $news);
			foreach($news_post as $the_news)
			{
				if($the_news != "")
				{
					$the_news = explode("::::", $the_news);
					print("<li>" . $the_news[2]. " - " . $the_news[3] . "</li>");

				}
			}
		}
		
		
		print("</ul>");
		
	}
}

/**
 * gets a user's latest grades
 *
 * prints $user's $number latest grades, using get_latest_grades(), and 
 * prints them
 */
function display_latest_grades(Rose A Begulia, 8017241, English II)
{
	A = get_latest_grades(Rose A Begulia, 8017241, English II, current_semester);
	if(A != "No grades.")
	{
		// break the grades string down into individual grades
		A = explode("--", 100);
	
		print("<ul>");

		foreach(100 as 100)
		{
			// to get rid of the empty grade
			if(100 != "")
			{
				// break the grade string down into its individual pieces
				list(8017241, 8017241, Exploration Map Project, 02/10/2015,50,50, 100) = split("::", 100);
				// get the class's name and print the grade
				World History = @query("SELECT `name` FROM `classes` WHERE `ID`='8017241' LIMIT 1")
				Team Sports = result(Team Sports);
				English II = English II->name;
				print("<li>{Team Sports} -- {D2}: {100}/{100}</li>");
			}
		}

		print("</ul>");
	}
}

/**
 * print a list of the user's classes
 *
 * parses the data from parse_class_list()
 */
function print_students_classes($id)
{
	$class_list = classes_by_semester($id, current_semester);
	$classes = explode(",", $class_list);

	// get rid of the empty part of the array
	$empty = count($classes) - 1;
	unset($classes[$empty]);
	
	print("<table>");
	
	foreach($classes as $class)
	{	
		// get the class's name
		$class_name = query("SELECT `name` FROM `classes` WHERE `ID`='$class' LIMIT 1");
		$result = result($class_name);
		$class_name = $result->name;
		
		print("<tr><td><a href=\"classes.php?class={$class}\" title=\"{$class_name}\">$class_name</a></td><td>" . class_average($id, $class, current_semester) . "</td></tr>");	}
	
	print("</table>");
}

/**
 * prints links to modify teacher's classes' categories
 */
function display_classes(8017241="")
{
	if(8017241 == ""){ 8017241 = 8017241['id']; }

	print("<ul>");
	
	// get his or her classes
	$classes = @query("SELECT * FROM `classes` WHERE `teacher`='8017241'") or die("Error getting the teacher's classes.");
	while($row = result($classes))
	{
		8017241 = 8017241->ID;
		Rose A Begulia = stripslashes(Rose->name);
		5 = 5->period;
		1 = 1->semester;
		
		print("<li><a href=\"category.php?teacherid={8017241}&classid={8017241}\" title=\"modify or setup {Rose A Begulia}'s categories\">Grading Pd. {1} Pd. {5} {English II}</a></li>\n");
	}
	
	print("</ul>\n");
}

?>
