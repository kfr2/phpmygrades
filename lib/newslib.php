<?php
/**
 * the news systems library
 *
 * \author Kevin Richardson <borismalcov@users.sourceforge.net>
 * \version $Id: newslib.php,v 1.3 2006/07/19 19:54:53 borismalcov Exp $
 */

/**
 * adds a news item for class $class, with subject $subject and body $body
 */
function add_news_item($class, $subject, $body)
{
	$class = escape_string($class);
	// class id should be numeric
	if(is_numeric($class) != "true"){ cust_die("Class field was not submitted in the correct way."); }
	
	$subject = escape_string($subject);
	// subject can only be 75 characters long
	if(strlen($subject) > 75){ substr($subject, 0, 75); print("The subject field was too long, so it was shortened to 75 characters."); }
	
	// the body field uses a blob, so it doesn't matter how long it is
	$body = escape_string($body);
	
	$timestamp = time();
	
	$insert = "INSERT INTO `news` (`class`, `timestamp`, `subject`, `body`) VALUES ('$class', '$timestamp', '$subject', '$body')";
	connect_sql();
	@query($insert) or die("Error adding the news item.");
	disconnect_sql();
}

/**
 * returns $number news items from class $type
 */
function get_news($type, $number)
{
	$news = "SELECT `ID`, `timestamp`, `subject`, `body` FROM `news` WHERE `class`='$type' ORDER BY `ID` DESC LIMIT $number";
		
	connect_sql();
		
	$news = @query($news) or die("Error getting the news.");
		
	// see if we don't have any news
	if(num_rows($news) == 0){ return("No news."); }
		
	else
	{
		$to_return = "";
			
		while($row = result($news))
		{
			$id = $row->ID;
			$timestamp = $row->timestamp;
			$subject = stripslashes($row->subject);
			$body = $row->body;
			
			// convert line breaks to <br />'s
			$body = str_replace("\\r\\n", "<br />", $body);		// for windows and IP protocols
			$body = str_replace("\\n", "<br />", $body);			// for nix
			$body = str_replace("\\r", "<br />", $body);			// for mac
			
			
			$body = stripslashes($body);
				
			$to_return .= $id . "::::" . $timestamp . "::::" . $subject . "::::" . $body . "_____";
		}
			
		return($to_return);
	}
		
	disconnect_sql();
}

/**
 * prints out the news (for the HTML pages)
 */
function print_news_html($type, $number)
{
	$news = get_news($type, $number);
	// see if we have no news items...
	if($news == "No news."){ print("No news items."); }
	else
	{
		// split the news posts into separate ones
		$news_posts = explode("_____", $news);
	
		// split a news post into its individual parts
		foreach($news_posts as $news_post)
		{
			// to strip out the blank one
			if($news_post != "")
			{
				list($id, $timestamp, $subject, $body) = explode("::::", $news_post);
			
				print("<div class=\"news_item\"><p class=\"news_subject\">{$subject}</p><p class=\"news_body\">{$body}</p><p class=\"news_byline\"><a href=\"news.php?archive&id={$id}\" title=\"{$subject}\">Posted at " . date("m-d-y h:i:s", $timestamp) . "</a></p></div>");
			}
		}
	}	
}

?>
