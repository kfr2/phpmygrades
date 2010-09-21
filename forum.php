<?php
/**
 * Provides a simple forum for class discussion.
 *
 * \author Kevin Richardson <borismalcov@users.sourceforge.net>
 * \version $Id: forum.php,v 1.7 2006/07/19 19:54:52 borismalcov Exp $
 */

include("lib/main.php");

display_header("forum");

if(is_logged_in() == FALSE){ $_SESSION['not_this_page'] = 1; cust_die("You'll need to login to access the page you've requested."); }
if(enable_forums == 0){ $_SESSION['not_this_page'] = 1; cust_die("Forums are not currently enabled."); }

display_menu();

print("<div class=\"container2\">");

connect_sql();

// has the user posted something?

if(isset($_POST['addtopic']))
{
	if(isset($_POST['subject']) && $_POST['subject'] != "")
	{
		$subject = escape_string(htmlspecialchars($_POST['subject']));
		if(strlen($subject) > 50){ cust_die("Your subject's length must be less than 50 characters."); } 
	}
	else{ cust_die("You must submit a subject."); }
	
	if(isset($_POST['body']) && $_POST['body'] != "")
	{
		$body = htmlspecialchars($_POST['body']);
		
		// replace \n\r's, etc, with <br />'s
		$body = str_replace("\r\n", "<br />", $body);		// for windows and IP protocols
		$body = str_replace("\n", "<br />", $body);			// for nix
		$body = str_replace("\r", "<br />", $body);			// for mac
		
		$body = escape_string($body);
	}
	
	if(!isset($_POST['classid']) || $_POST['classid'] == "" || is_numeric($_POST['classid']) === FALSE){ cust_die("Invalid class ID."); }
	$class_id = escape_string($_POST['classid']);
	
	// okay, everything is good.  First add the topic to the topics table, grab its ID, and then submit the post to the posts table
	
	@query("INSERT INTO `topics` (`name`, `class_ID`) VALUES ('$subject', '$class_id')") or die("Error adding the topic.");
	
	$topic_ID = @query("SELECT `ID` FROM `topics` WHERE `name`='$subject' LIMIT 1") or die("Error checking the database.");
	$topic_ID = result($topic_ID);
	$topic_ID = $topic_ID->ID;
	
	$timestamp = time();
	$poster = $_SESSION['id'];
	@query("INSERT INTO `posts` (`topic_ID`, `poster`, `timestamp`, `body`, `deleted`) VALUES ('$topic_ID', '$poster', '$timestamp', '$body', '0')") or die("Error adding the post.");
	
	print("Your post has been added.  Back to the <a href=\"forum.php?id={$class_id}\" title=\"class forum\">forum</a>?");
}

elseif(isset($_POST['addpost']))
{
	if(isset($_POST['body']) && $_POST['body'] != "")
	{
		$body = htmlspecialchars($_POST['body']);
		
		// replace \n\r's, etc, with <br />'s
		$body = str_replace("\r\n", "<br />", $body);		// for windows and IP protocols
		$body = str_replace("\n", "<br />", $body);			// for nix
		$body = str_replace("\r", "<br />", $body);			// for mac
		
		$body = escape_string($body);
	}
	
	if(!isset($_POST['classid']) || $_POST['classid'] == "" || is_numeric($_POST['classid']) === FALSE){ cust_die("Invalid class ID."); }
	$class_id = escape_string($_POST['classid']);

	if(!isset($_POST['topicid']) || $_POST['topicid'] == "" || is_numeric($_POST['topicid']) === FALSE){ cust_die("Invalid topic ID."); }
	$topic_id = escape_string($_POST['topicid']);
	
	// okay, everything is good.  See if the topic is valid, and if it is, submit the post to the posts table

	$is_topic = @query("SELECT 1 FROM `topics` WHERE `ID`='$topic_id' LIMIT 1") or die("Error checking the database.");
	if(num_rows($is_topic) == 0){ cust_die("Invalid topic ID."); }
	
	
	$timestamp = time();
	$poster = $_SESSION['id'];

	@query("INSERT INTO `posts` (`topic_ID`, `poster`, `timestamp`, `body`, `deleted`) VALUES ('$topic_id', '$poster', '$timestamp', '$body', '0')") or die("Error adding the post.");
	
	print("Your post has been added.  Back to the <a href=\"forum.php?id={$class_id}\" title=\"class forum\">forum</a>?");
}

// so, the user has requested a certain class's forum...
elseif(isset($_GET['id']))
{
	if($_GET['id'] != "" && is_numeric($_GET['id']) == TRUE){ $class_id = escape_string($_GET['id']); }
	else{ cust_die("Invalid class ID."); }
	
	// perhaps $class_id = 0 can be reserved for a teachers'/admins' forum...

	// see if the requested ID is an actual class.
	$is_class = @query("SELECT 1 FROM `classes` WHERE `ID`='$class_id'") or die("Error checking the database.");
	if(num_rows($is_class) == 0){ cust_die("Invalid class ID."); }
	
	// if it is, see if the user may access it.  teachers and admins may access all forums
	if(user_type() == "user")
	{
		$classes = parse_class_list($_SESSION['id']);
		if(strpos($classes, $class_id . ",") === FALSE && strpost($classes, "," . $class_id) === FALSE){ cust_die("You may not access this forum>"); }
	}
	
	// if he or she may...
	
	// allow the class's teacher (or an administrator) to delete posts or topics
	if(isset($_GET['delete']))
	{
		// if the user is an administrator or the class's teacher, allow him or her to delete the post/topic
		if(user_type() == "admin" || user_type() == "teacher")
		{
			$good = 1;
			if(user_type() == "teacher")
			{
				// see if they teach the class
				$class_data = get_class_data($class_id);
				$class_data = explode("::", $class_data);
				$teacher_id = $class_data[1];
				if($teacher_id != $_SESSION['id']){ $good = 0; }
			}
			if($good == 1)
			{
				if(isset($_GET['t']))
				{
					// see if $_GET['t'] is a valid topic
					if($_GET['t'] == "" || is_numeric($_GET['t']) === FALSE){ cust_die("Invalid topic ID."); }
					$topic_id = escape_string($_GET['t']);
					
					$valid_topic = @query("SELECT 1 FROM `topics` WHERE `ID`='$topic_id' LIMIT 1") or die("Error getting information from the database.");
					if(num_rows($valid_topic) == 0){ cust_die("Invalid topic ID."); }

					// okay, it's valid: delete it (and associated posts).
					@query("DELETE FROM `topics` WHERE `ID`='$topic_id' LIMIT 1") or die("Error deleting the topic.");
					@query("UPDATE `posts` SET `deleted`='1' WHERE `topic_ID`='$topic_id'") or die("Error deleting the topic's posts.");

					print("Done.");
					
				}
				elseif(isset($_GET['p']))
				{
					// see if $_GET['p'] is a valid post
					if($_GET['p'] == "" || is_numeric($_GET['p']) === FALSE){ cust_die("Invalid post ID."); }
					$post_id = escape_string($_GET['p']);
					
					$valid_post = @query("SELECT 1 FROM `posts` WHERE `post_ID`='$post_id' LIMIT 1") or die("Error getting information from the database.");
					if(num_rows($valid_post) == 0){ cust_die("Invalid post ID."); }

					// okay, it's valid: delete the post
					@query("UPDATE `posts` SET `deleted`='1' WHERE `post_ID`='$post_id' LIMIT 1") or die("Error deleting the post.");

					print("Done.");

				}	
			}
		}
	}
		
	print("<table name=\"topics\" class=\"posttable\">");
	
	// display the posts from the topic, if the user has chosen one
	if(isset($_GET['topic']))
	{
		if($_GET['topic'] == "" && is_numeric($_GET['topic']) === FALSE){ cust_die("Invalid class ID."); }
		$topic_ID = escape_string($_GET['topic']);
		
		// get the topic's name, and then all its posts
		$topic_name = @query("SELECT `name` FROM `topics` WHERE `ID`='$topic_ID'") or die("Error getting information from the database.");
		$topic_name = result($topic_name);
		$topic_name = stripslashes($topic_name->name);
		
		print("<tr><th>{$topic_name}</th></tr>");
	
		$n = 1;
	
		$posts = @query("SELECT * FROM `posts` WHERE `topic_ID`='$topic_ID' AND `deleted`='0' ORDER BY `post_ID`") or die("Error getting posts.");
		while($row = result($posts))
		{
			$body = stripslashes($row->body);
			$timestamp = $row->timestamp;
			$post_ID = $row->post_ID;
			$poster = $row->poster;
			// get the poster's name
			$poster_name = @query("SELECT `firstname`,`surname` FROM `users` WHERE `ID`='$poster' LIMIT 1");
			$poster_name = result($poster_name);
			$poster_name = stripslashes($poster_name->firstname) . " " . stripslashes($poster_name->surname);
			
			print("<tr><td><i>Posted at " . date(timeformat, $timestamp) . " on " . date(dateformat, $timestamp) . " by {$poster_name}</i></td>");
			
			// if the user is an administrator or the class's teacher, allow him or her to delete the post
			if(user_type() == "admin" OR user_type() == "teacher")
			{
				$good = 1;
				if(user_type() == "teacher")
				{
					// see if they teach the class
					$class_data = get_class_data($class_id);
					$class_data = explode("::", $class_data);
					$teacher_id = $class_data[1];
					if($teacher_id != $_SESSION['id']){ $good = 0; }
				}
				if($good == 1)
				{
					print("<td><a href=\"forum.php?id={$class_id}&delete&p={$post_ID}\" title=\"delete this post\">delete</a></td>");
				}
			}
			
			print("</tr><tr><td class=\"postbody{$n}\" colspan=\"2\">{$body}</td></tr>");
			
			if($n == 1){ $n++; }
			else{ $n = 1; }
		}
	
		// print a reply form
		print("<form action=\"forum.php\" method=\"post\" name=\"replyform\"><tr><td><hr class=\"mainpagehr\" /></td></tr><tr><td>Reply:</td></tr><tr><td><textarea name=\"body\" rows=\"10\" cols=\"28\"></textarea></td></tr><tr><td><input type=\"hidden\" name=\"classid\" value=\"{$class_id}\" /><input type=\"hidden\" name=\"topicid\" value=\"{$topic_ID}\" /><input type=\"submit\" name=\"addpost\" value=\"add post\" /></td></tr><tr><td>Back to the <a href=\"forum.php?id={$class_id}\" title=\"forum\">forum</a>?</td></tr></form>");
	}
	
	// show a list of topics if he or she already hasn't selected one
	else
	{
		$tdcolour = 0;

		$topics = @query("SELECT * FROM `topics` WHERE `class_ID`='$class_id' ORDER BY `ID`") or die("Error getting topics from the database.");
		while($row = result($topics))
		{
			$id = $row->ID;
			$name = stripslashes($row->name);
			
			print("<tr class=\"tdcolour{$tdcolour}\"><td><a href=\"forum.php?id={$class_id}&topic={$id}\" title=\"{$name}\">{$name}</a></td>");
			
			// if the user is an administrator or the class's teacher, allow him or her to delete the topic (and all associated posts)
			if(user_type() == "admin" OR user_type() == "teacher")
			{
				$good = 1;
				if(user_type() == "teacher")
				{
					// see if they teach the class
					$class_data = get_class_data($class_id);
					$class_data = explode("::", $class_data);
					$teacher_id = $class_data[1];
					if($teacher_id != $_SESSION['id']){ $good = 0; }
				}
				if($good == 1)
				{
					print("<td><a href=\"forum.php?id={$class_id}&delete&t={$id}\" title=\"delete this topic\">delete</a></td>");
				}
			}
			
			print("</tr>\n");

			if($tdcolour == 0){ $tdcolour++; }
			else{ $tdcolour = 0; }
		}
		
		// print a new topic form
		print("<form action=\"forum.php\" method=\"post\" name=\"addtopic\"><tr><td><hr class=\"mainpagehr\" /></td></tr><tr><th>Add a new topic</th></tr>\n<tr><td>Subject</td><td><input type=\"text\" name=\"subject\" maxlength=\"50\" /></td></tr><tr><td>Body</td><td><textarea name=\"body\" rows=\"10\" cols=\"28\"></textarea></td></tr><tr><td><input type=\"hidden\" name=\"classid\" value=\"{$class_id}\" /><input type=\"submit\" name=\"addtopic\" value=\"add topic\" /></td></tr><tr><td>Back to the <a href=\"forum.php?id={$class_id}\" title=\"forum\">forum</a>?</td></tr></form>");
	}
	
	print("</table>");
}


else
{
	print("Access the forums via your classes page.");
	
}

disconnect_sql();

print("</div>");
display_copyright();
display_footer();
?>
