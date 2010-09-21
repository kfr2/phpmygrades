<?php
/**
 * a XML library for phpmygrades
 *
 * \author Kevin Richardson <borismalcov@users.sourceforge.net>
 * \version $Id: xmllib.php,v 1.4 2006/07/19 19:54:53 borismalcov Exp $
 */

/**
 * returns a password for things like the RSS feeds
 *
 * gets the password for $id from the database and makes it 8 chars long
 */
function gen_rss_pass($id)
{
	connect_sql();

	$pass = "SELECT `password` FROM `users` WHERE `id`='$id' LIMIT 1";
	$pass = @query($pass) or die("Error getting information from the database.");
	$result = result($pass);
	$pass = $result->password;
	$pass = substr($pass, 0, 6);
	
	// replace the characters with numbers
	$pass = str_replace(array("a", "b", "c"), "1", $pass);
	$pass = str_replace(array("d", "e", "f"), "2", $pass);
		
	
	return($pass);

	disconnect_sql();
}


/**
 * generates the rss header
 */
function rss_header($title, $description, $link, $lastbuild)
{
	$builddate = date("D, d M Y H:i:s T", $lastbuild);

$stuff = <<< EOT
<?xml version="1.0" encoding="utf-8"?>
<?xml-stylesheet href="style/rss.css" type="text/css"?>
<rss version="2.0"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
	xmlns:admin="http://webns.net/mvcb/"
	xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
	xmlns:content="http://purl.org/rss/1.0/modules/content/">

<!--
Greetings.  This file is actually a data file that should be viewed in a feed reader.
You can find a popular list of them at http://blogspace.com/rss/readers
-->

<channel>
<title>$title</title>
<link>$link</link>
<description>$description</description>
<language>en-us</language>
<copyright>Copyright 2004-2005, Kevin Richardson and contributors</copyright>
<lastBuildDate>$builddate</lastBuildDate>
<generator>phpmygrades</generator>
<docs>http://blogs.law.harvard.edu/tech/rss</docs>
<ttl>360</ttl>\n
EOT;

header("Content-type: text/xml");
print($stuff);

}


/**
 * prints the bottom of the rss file
 */
function rss_footer()
{
	print("</channel>\n</rss>");
}


/**
 * generates items for the rss feed
 */
function rss_item($title, $body, $link, $timestamp)
{
	$pubdate = date("D, d M Y H:i:s T", $timestamp);
	print("<item>\n<title>{$title}</title>\n<link>\n{$link}</link>\n<description>\n<![CDATA[ {$body} ]]>\n</description>\n");
	print("<guid isPermaLink=\"true\">\n{$link}\n</guid>\n<pubDate>\n{$pubdate}</pubDate>\n</item>\n");
	
}

/**
 * return a user's latest grades as a rss feed
 *
 * gets $user's $number latest grades (from $class; 'all' for all their classes),
 * using get_latest_grades(), and makes a rss feed out of them
 */
function rss_latest_grades($user, $number, $class)
{
	$real_name = "SELECT `firstname`,`surname` FROM `users` WHERE `ID`='$user' LIMIT 1";
	$realname = query($real_name) or die("Error getting information from the database.");
	while($row = result($realname))
	{
		$real_name = stripslashes($row->firstname) . " " . stripslashes($row->surname);
	}

	$latest_date = "SELECT MAX(`date_assigned`) FROM `grades` WHERE `student_ID`='$user' LIMIT 1";
	$latest_date = @query($latest_date) or die("Error getting information from the database.");
	$latest_date = command_result($latest_date, 0);

	rss_header("{$real_name}'s grades", "your latest grades", "http://" . server_root . "classes.php", $latest_date);

	$grades = get_latest_grades($user, $number, $class, current_semester);
	// break the grades string down into individual grades
	$grades = explode("--", $grades);
	
	foreach($grades as $grade)
	{
		// to get rid of the empty grade
		if($grade != "")
		{
			// break the grade string down into its individual pieces
			list($class_id, $assign_id, $assign_name, $assign_date, $points_possible, $points_scored, $grading_period) = split("::", $grade);
			// get the class's name and print the grade
			$class_name = @query("SELECT `name` FROM `classes` WHERE `ID`='$class_id' LIMIT 1") or die("Error getting class name."); $result = result($class_name); $class_name = $result->name;
			
			
			// time to rss-ify 'em
			rss_item($class_name . "-- " .$assign_name, "{$points_scored}/{$points_possible}", server_root . "/assignment.php?class={$class_id}%26id={$assign_id}", $assign_date);
		}
	}
	
	rss_footer();
}

?>
