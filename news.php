<?php
/**
 * allows users to add, edit, or view news items
 *
 * \author Kevin Richardson <borismalcov@users.sourceforge.net>
 * \version $Id: news.php,v 1.8 2006/07/19 19:54:52 borismalcov Exp $
 */

include("lib/main.php");

// allows them to get their news via a RSS feed
if(isset($_GET['xml']))
{
	// if a user wants a 'latest news' feed...
	if(isset($_GET['u']))
	{
		if(is_numeric($_GET['u']) == FALSE){ die("Invalid user ID."); } $user = escape_string($_GET['u']);
		if(!isset($_GET['p']) OR is_numeric($_GET['p']) == FALSE){ die("Invalid password."); }
		
		$actual_password = gen_rss_pass($user);
		if($actual_password != escape_string($_GET['p'])){ die("Incorrect password."); }

		$number = 3;

		$lastbuild = time();
		rss_header("latest class news", "latest news for various classes", server_root . "news.php", $lastbuild);

		// get the classes the user is currently in
		$users_classes = classes_by_semester($user, current_semester);

		// get rid of the extra comma
		$users_classes = substr($users_classes, 0, strlen($users_classes) - 1);
	
		$classes = explode(",", $users_classes);
	
		foreach($classes as $class)
		{
			// get the class's name
			$class_data = get_class_data($class);
			$class_data = explode("::", $class_data);
			$class_name = $class_data[0];
		
			$news = get_news($class, $number);
		
			if($news != "No news.")
			{
				$news_post = explode("_____", $news);
				foreach($news_post as $the_news)
				{
					if($the_news != "")
					{
						$the_news = explode("::::", $the_news);
						$timestamp = $the_news[1];
						$subject = $the_news[2];
						$body = $the_news[3];
						rss_item($class_name . ":  " . $subject, $body, server_root . "news.php", $timestamp);
					}
				}
			}
		}
		
		rss_footer();
		die();
	}


	if(!isset($_GET['class']) OR is_numeric($_GET['class']) != "true"){ die("Invalid class ID."); }
	
	$class = escape_string($_GET['class']);
	
	if($class == 0)
	{
		connect_sql();
		
		$posts = @query("SELECT * FROM `news` WHERE `class`='0' ORDER BY `id` DESC LIMIT 5") or die("Error getting the posts.");
		$last_post = @query("SELECT MAX(timestamp) FROM `news` WHERE `class`='0'") or die("Error getting information from the database.");
		$last_post = command_result($last_post, 0);
		
		header("Content-type: text/xml");
		
		rss_header(school_name . " news", "news for " . school_name, server_root . "news.php", $last_post);
		
		while($row = result($posts))
		{
			$id = $row->ID;
			$timestamp = $row->timestamp;
			$subject = stripslashes($row->subject);
			$body = $row->body;

                        $body = str_replace("\\r\\n", "<br />", $body);
                        $body = stripslashes($body);
			
			rss_item($subject, $body, server_root . "news.php" . "?archive%26id={$id}", $timestamp);
		}		
		
		rss_footer();
		
		disconnect_sql();
	}
	
	else
	{
		connect_sql();
		
		// see if $class is a class
		$is_class = @query("SELECT 1 FROM `classes` WHERE `ID`='$class' LIMIT 1") or die("Error checking the database.");
		if(num_rows($is_class) == 0){ die("Invalid class."); }
		
		$class_info = @query("SELECT * FROM `classes` WHERE `ID`='$class' LIMIT 1") or die("Error getting information from the database.");

		while($row = result($class_info))
		{
			$period = $row->period;
			$class_name = stripslashes($row->name);
		}
		
		$class_name = "Period {$period} {$class_name}";
		
		$last_post = @query("SELECT MAX(timestamp) FROM `news` WHERE `class`='$class'") or die("Error getting information from the database.");
		$last_post = command_result($last_post, 0);
		
		$posts = @query("SELECT * FROM `news` WHERE `class`='$class' LIMIT 5") or die("Error getting the posts from the database.");
		
		header("Content-type: text/xml");
		
		rss_header($class_name . " news", "news for " . $class_name, server_root . "news.php", $last_post);
		
		while($row = result($posts))
		{
			$id = $row->ID;
			$timestamp = $row->timestamp;
			$subject = stripslashes($row->subject);
			$body = stripslashes($row->body);
			
			rss_item($subject, $body, server_root . "news.php?archive%26id={$id}", $timestamp);
		}		
		
		rss_footer();
		
		disconnect_sql();
	}
		
	die();
}

elseif(isset($_GET['archive']))
{
	if(!isset($_GET['id']) OR is_numeric($_GET['id']) != "true"){ cust_die("Don't mess with that."); }
	$id = escape_string($_GET['id']);
	
	connect_sql();

	// show 'em the post.  It's not a big deal if they see another class's news	
	$post = @query("SELECT * FROM `news` WHERE `id`='$id' LIMIT 1") or die("Error getting the post from the database.");
	
	while($row = result($post))
	{
		$timestamp = $row->timestamp;
		$subject = stripslashes($row->subject);
		$body = stripslashes($row->body);
		
		display_header("News: {$subject}");
		print("<div class=\"container2\"><div class=\"news_background\"><div class=\"news_item\">");
		print("<p class=\"news_subject\">{$subject}</p>\n<p class=\"news_body\">{$body}</p>\n<p class=\"news_byline\">Posted at " . date(dateformat . timeformat, $timestamp) . "</p>");
		print("</div></div><p><a href=\"index.php\" title=\"home\">main page</a></p></div>");
	}
	
	disconnect_sql();

	die();
}

display_header("news");
if(is_logged_in() == FALSE){ $_SESSION['not_this_page'] = 1; cust_die("You'll need to login to access the page you've requested."); }

display_menu();
print("<div class=\"container2\">");

if(isset($_GET['add']))
{
	if(isset($_GET['class']))
	{
		if(is_numeric($_GET['class']) != "true"){ die("Don't mess with that."); }
		$class_id = escape_string($_GET['class']);
	
		// see if they're able to add news to the class (if they're an admin, they may, so don't bother checking for them)
		if(user_type() != "admin")
		{
			if(user_type() == "user"){ cust_die("You may not add news."); }
			
			// so, they're a teacher.  See if they teach the class they want to add news to
			$user_id = $_SESSION['id'];

			connect_sql();

			$query = @query("SELECT 1 FROM `classes` WHERE `ID`='$class_id' AND `teacher`='$user_id' LIMIT 1") or die("Error checking the database.");
			if(num_rows($query) == 0){ cust_die("You may not add news to that class."); }
			disconnect_sql();
			
		}
		
		// they're able to: show 'em the form
		print("<table>\n<form action=\"news.php\" method=\"post\"><tr><td>Subject:</td><td><input type=\"text\" name=\"subject\" maxlength=\"75\" /></td></tr>\n<tr><td>Body:</td><td><textarea name=\"body\" rows=\"10\" cols=\"40\"></textarea></td></tr>\n<tr><td><input type=\"hidden\" name=\"class\" value=\"{$class_id}\" /><input type=\"submit\" name=\"addnews\" value=\"post\" /></td></tr>\n</form></table>\n");
	}
	
	else
	{
		// if they're an administrator, allow them to add global news or news for an individual class
		if(user_type() == "admin")
		{
			print("Add news for <form action=\"news.php\" method=\"get\"><input type=\"hidden\" name=\"add\"><select name=\"class\"><option value=\"0\" class=\"tdcolour0\">everyone\n");
			
			// get the list of classes
			
			connect_sql();
			
			$tdcolour = 1;

			$classes = @query("SELECT * FROM `classes` ORDER BY `period`, `ID`");
			while($row = result($classes))
			{
				$ID = $row->ID;
				$period = $row->period;
				$semesters = $row->semester;
				$class_name = stripslashes($row->name);
				
				print("<option value=\"{$ID}\" class=\"tdcolour{$tdcolour}\">Grading Period {$semesters} Period {$period} {$class_name}\n");
				if($tdcolour == 1){ $tdcolour = 0; }
				else{ $tdcolour++; }
			}
			
			disconnect_sql();
			
			print("</select><input type=\"submit\" value=\"go\" /></form>\n");
			
		}
		
		// if they're a teacher, allow them to add news for their classes
		elseif(user_type() == "teacher")
		{
			$teacher_id = $_SESSION['id'];
			
			print("<form action=\"news.php\" method=\"get\">Add news for <input type=\"hidden\" name=\"add\" /><select name=\"class\">");
			
			connect_sql();
			
			$tdcolour = 0;

			$classes = @query("SELECT * FROM `classes` WHERE `teacher`='$teacher_id' ORDER BY `period`") or die("Error checking the database.");
			
			while($row = result($classes))
			{
				$ID = $row->ID;
				$period = $row->period;
				$class_name = $row->name;
				
				print("<option value=\"{$ID}\" class=\"tdcolour{$tdcolour}\">Period {$period} {$class_name}\n");
				if($tdcolour == 1){ $tdcolour = 0; }
				else{ $tdcolour++; }
			}
			
			disconnect_sql();
			
			print("</select><input type=\"submit\" value=\"go\" /></form>");
		}
	}
}

elseif(isset($_GET['edit']))
{
	if(user_type() == "user"){ cust_die("You can't edit the news."); }

	if(isset($_GET['id']))
	{
		if($_GET['id'] == "NULL"){ cust_die("You must submit the news post you'd like to edit, not the class."); }
		if($_GET['id'] == "" OR is_numeric($_GET['id']) != "true"){ cust_die("Don't mess with the ID. ;)"); }
		$id = escape_string($_GET['id']);
		
		// see what class the news post belongs to
		connect_sql();

		$class_id = @query("SELECT `class` FROM `news` WHERE `ID`='$id'") or die("Error checking the database.");
		$result = result($class_id);
		$class_id = $result->class;		

		// see if they can access the news
		if(user_type() == "teacher")
		{
			if(teacher_teaches($_SESSION['id'], $class_id) == FALSE){ cust_die("You may not edit that news post."); }
		}
		
		// okay, they're good: show 'em the post and whatnot
		$news_post = @query("SELECT * FROM `news` WHERE `ID`='$id'") or die("Error getting the news post from the database.");
		while($row = result($news_post))
		{
			$subject = stripslashes($row->subject);
			$body = $row->body;
			
			$body = str_replace("\\r\\n", "<br />", $body);
			$body = stripslashes($body);
			$body = str_replace("<br />", "\r\n", $body);
						
			print("<table><form action=\"news.php\" method=\"post\"><input type=\"hidden\" name=\"id\" value=\"{$id}\" /><tr><td>Subject:</td><td><input type=\"text\" name=\"subject\" value=\"{$subject}\" /></td></tr><tr><td>Body:</td><td><textarea name=\"body\" rows=\"10\" cols=\"40\">{$body}</textarea></td></tr><tr><td><input type=\"submit\" name=\"edit\" value=\"edit\" /></td></tr></form></table>");
		}
	
		disconnect_sql();
	}
	
	else
	{
		if(user_type() == "admin")
		{
			print("News article:  <form action=\"news.php\" method=\"get\"><select name=\"id\">");

			$tdcolour = 0;

			connect_sql();
			
			$posts = @query("SELECT * FROM `news` WHERE `class`='0' ORDER BY `ID` DESC");
			while($row = result($posts))
			{
				$ID = $row->ID;
				$subject = stripslashes($row->subject);
				print("<option value=\"{$ID}\" class=\"tdcolour{$tdcolour}\">{$subject}\n");

				if($tdcolour == 1){ $tdcolour = 0; }
				else{ $tdcolour++; }
			}
			
			disconnect_sql();
			
			print("</select><input type=\"submit\" name=\"edit\" value=\"edit\" /><input type=\"submit\" name=\"delete\" value=\"delete\" /></form>\n");	
		}
		
		// if they're a teacher, allow them to add news for their classes
		elseif(user_type() == "teacher")
		{
			$teacher_id = $_SESSION['id'];
			
			print("<form action=\"news.php\" method=\"get\">News article: <select name=\"id\">");
			
			connect_sql();

			$tdcolour = 0;
			
			$classes = @query("SELECT * FROM `classes` WHERE `teacher`='$teacher_id' ORDER BY `period`") or die("Error checking the database.");
			
			while($row = result($classes))
			{
				$class_id = $row->ID;
				$class_name = stripslashes($row->name);
				$period = $row->period;
				print("<option value=\"NULL\" class=\"tdcolour{$tdcolour}\">----Pd. {$period} {$class_name}----\n");

				$news_for_class = @query("SELECT * FROM `news` WHERE `class`='$class_id' ORDER BY `ID` DESC") or die("Error getting the news posts.");
			
				while($row2 = result($news_for_class))
				{
					$news_id = $row2->ID;
					$subject = $row2->subject;
				
					print("<option value=\"{$news_id}\" class=\"tdcolour{$tdcolour}\">{$subject}\n");
				}
				
				if($tdcolour == 1){ $tdcolour = 0; }
				else{ $tdcolour++; }
			}
			
			disconnect_sql();
			
			print("</select><input type=\"submit\" name=\"edit\" value=\"edit\" /><input type=\"submit\" name=\"delete\" value=\"delete\" /></form>\n");
		}
	}
}

elseif(isset($_POST['edit']))
{
	if(user_type() == "user"){ cust_die("You can't edit news posts."); }

	if(!isset($_POST['id']) OR $_POST['id'] == "" OR is_numeric($_POST['id']) != "true"){ cust_die("You must submit the news post you'd like to edit."); }
	if($_POST['id'] == "NULL"){ cust_die("Please select the post you'd like to edit, not the class."); }
	
	$id = escape_string($_POST['id']);
	
	if(!isset($_POST['subject']) OR $_POST['subject'] == ""){ cust_die("You must submit the subject."); }
	$subject = escape_string($_POST['subject']);
	
	if(!isset($_POST['body']) OR $_POST['body'] == ""){ cust_die("You must submit the body."); }
	$body = escape_string($_POST['body']);
	
	connect_sql();
	
	if(user_type() == "teacher")
	{
		$class_id = @query("SELECT `class` FROM `news` WHERE `ID`='$id'") or die("Error checking the database.");
		$result = result($class_id);
		$class_id = $result->class;
	
		if(teacher_teaches($teacher_id, $class_id) == "FALSE"){ cust_die("You can't edit that news post."); }	
	}
	
	// update it
	@query("UPDATE `news` SET `subject`='$subject', `body`='$body' WHERE `ID`='$id'") or die("Error updating the database.");
	
	disconnect_sql();
	
	print("The news post was edited.  <a href=\"news.php?edit\" title=\"edit a post\">Edit another</a>?");
}

elseif(isset($_GET['delete']))
{
	if(user_type() == "user"){ cust_die("You can't delete news posts."); }

	if(!isset($_GET['id']) OR $_GET['id'] == "" OR is_numeric($_GET['id']) != "true"){ cust_die("You must submit the news post you'd like to delete."); }
	if($_GET['id'] == "NULL"){ cust_die("Please select the post you'd like to delete, not the class."); }
	
	$id = escape_string($_GET['id']);
	
	connect_sql();
	
	// see if they're able to delete the post:  if they're an admin, they don't check
	if(user_type() == "teacher")
	{
		$teacher_id = $_SESSION['id'];
	
		// see if they can delete it
		$class_id = @query("SELECT `class` FROM `news` WHERE `ID`='$id'") or die("Error checking the database.");
		$result = result($class_id);
		$class_id = $result->class;
	
		if(teacher_teaches($teacher_id, $class_id) == "FALSE"){ cust_die("You can't delete that news post."); }
	}
	
	@query("DELETE FROM `news` WHERE `ID`='$id' LIMIT 1") or die("Error deleting the post.");
	
	disconnect_sql();
	
	print("The post has been deleted.");
}

// if they're posting news
elseif(isset($_POST['addnews']))
{
	if(user_type() == "user"){ cust_die("You may not add news."); }
	
	if(!isset($_POST['class']) OR $_POST['class'] == "" OR is_numeric($_POST['class']) != "true"){ cust_die("Don't mess with the class ID. ;D"); }
	$class_id = escape_string($_POST['class']);
	
	if(!isset($_POST['subject']) OR $_POST['subject'] == ""){ cust_die("You must submit a subject."); }
	if(!isset($_POST['body']) OR $_POST['body'] == ""){ cust_die("You must submit the body."); }
	
	// see if they can add news to the class (not needed if they're an admin)
	if(user_type() != "admin")
	{
		$user_id = $_SESSION['id'];

		connect_sql();
		$query = @query("SELECT 1 FROM `classes` WHERE `ID`='$class_id' AND `teacher`='$user_id' LIMIT 1") or die("Error checking the database.");
		if(num_rows($query) == 0){ cust_die("You may not add news to that class."); }
		disconnect_sql();
	}
		
	$subject = escape_string(htmlspecialchars($_POST['subject']));
	$body = escape_string(htmlspecialchars($_POST['body']));
	
	$timestamp = time();	
	
	connect_sql();
	add_news_item($class_id, $subject, $body);
	disconnect_sql();
	
	print("The news item was added.  <a href=\"news.php?add\" title=\"add a news item\">Add another</a>?");
}

else
{
	any_errors();

	if(user_type() == "user")
	{
		display_latest_news($_SESSION['id'], 3);
	}

	else
	{
		print("You can add or edit news articles by following the respective links on your menu.");
	}
}

print("</div>");
display_copyright();
display_footer();

?>
