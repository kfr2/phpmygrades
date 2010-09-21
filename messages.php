<?php
/**
 * the messaging script
 *
 * \author Kevin Richardson <borismalcov@users.sourceforge.net>
 * \version $Id: messages.php,v 1.9 2006/07/19 19:54:52 borismalcov Exp $
 */

include("lib/main.php");

function print_mail($content)
{
	$new_messages = check_mail();

	print("
	\n<table border=\"0\" class=\"mail\">
	<tr><td><a href=\"messages.php?compose\" title=\"compose a message\">compose</a>
	<br /><a href=\"messages.php?read\" title=\"read messages\">inbox</a>({$new_messages})
	<br /><a href=\"messages.php?outbox\" title=\"read sent messages\">outbox</a>
	<br /><a href=\"messages.php?trash\" title=\"read deleted messages\">trash</a></td>
	<td rowspan=\"2\" colspan=\"4\" class=\"mail_content_holder\">");
	
	print($content);
	
	
	print("</td></tr></table>");
}

if(isset($_GET['xml']))
{
	connect_sql();


	if(!isset($_GET['u']) || !isset($_GET['p'])){ cust_die("You need to submit a user ID and/or password string to view this XML feed."); }
	
	$id = escape_string($_GET['u']);	if(is_numeric($id) == FALSE){ die("Don't mess with the ID."); }
	$pass = escape_string($_GET['p']);
	
	$real_pass = gen_rss_pass($id);
	
	if($real_pass != $pass){ cust_die("Incorrect password."); }

	header("Content-type: text/xml");

	$latest = @query("SELECT `timestamp` FROM `mail` WHERE `to`='$id' AND `deleted`='0' ORDER BY `timestamp` DESC") or die("Error getting the messages from the database.");
	$latest = result($latest);
	$latest = $latest->timestamp;
	rss_header("phpmygrades mailbox", "your phpmygrades mailbox", "http://" . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'], $latest);
	
	$messages = @query("SELECT * FROM `mail` WHERE `to`='$id' AND `deleted`='0' ORDER BY `id`") or die("Error getting the messages from the database.");
	while($row = result($messages))
	{
		$subject = stripslashes($row->subject);
		$from = $row->from;
		$body = stripslashes($row->body);
		$link = server_root . "messages.php";
		$timestamp = $row->timestamp;
		
		$sender_query = @query("SELECT `firstname`,`surname` FROM `users` WHERE `ID`='$from' LIMIT 1") or die("Error getting information from the database.");
		while($row2 = result($sender_query))
		{
			$sender = stripslashes($row2->firstname . " " . $row2->surname);
			$body = "Sent by {$sender}:<br />" . $body;
			rss_item($subject, $body, $link, $timestamp);
		}
	}
	
	rss_footer();
	
	disconnect_sql();
	die();	
}


if(is_logged_in() == FALSE){ $_SESSION['not_this_page'] = 1; cust_die("You'll need to login to access the page you've requested."); }

display_header("messaging system");

display_menu();
print("<div class=\"container2\">");

// if they'd like to write a message
if(isset($_GET['compose']))
{
	$stuff = "
	<table><form action=\"messages.php\" method=\"post\">
	<tr><td>To:</td><td><select name=\"to\" \">";
	
	
	if(isset($_GET['id']) AND is_numeric($_GET['id']) == TRUE)
	{
		$requested_id = escape_string($_GET['id']);
		// see if they can mail the person
		connect_sql();	

		if(user_type() == "user" OR user_type() == "parent")
		{
			$permission = @query("SELECT `type` FROM `users` WHERE `ID`='$requested_id' LIMIT 1") or die("Error checking the database.");
			while($result = result($permission))
			{
				$type = $result->type;
				if($type < 2 OR $type == 4){ cust_die("You may not mail that person."); }
			}
		}

		$tdcolour = 0;

		$name_query = query("SELECT `firstname`, `surname` FROM `users` WHERE `ID`='$requested_id' LIMIT 1") or die("Error getting the user list from the database.");
		while($row = result($name_query))
		{
			$name = stripslashes($row->firstname) . " " . stripslashes($row->surname);
			$stuff .= "<option value=\"{$requested_id}\" class=\"tdcolour{$tdcolour}\">{$name}\n";

			if($tdcolour == 0){ $tdcolour++; }
			else{ $tdcolour = 0; }
		}
		
	}

	else
	{
	
		// whom can they send to?
		connect_sql();	

		$query = "SELECT `ID`, `firstname`, `surname` FROM `users`";
		if(user_type() == "user" OR user_type() == "parent"){  $query .= " WHERE `type`='2' or `type`='3'"; }
		$query .= " ORDER BY `ID`";

		$tdcolour = 0;

		$query = query($query) or die("Error getting the user list from the database");
		while($row = result($query))
		{
			if($row->ID != $_SESSION['id'])
			{
				$name = stripslashes($row->firstname) . " " . stripslashes($row->surname);
				$stuff .= "<option value=\"{$row->ID}\" class=\"tdcolour{$tdcolour}\">{$name}\n";

				if($tdcolour == 0){ $tdcolour++; }
				else{ $tdcolour = 0; }
			}
		}

		disconnect_sql();
	}	
	
	$stuff .= "
	</select></td></tr>
	<tr><td>Subject:</td><td><input type=\"text\" name=\"subject\" maxlength=\"75\" /></td></tr>
	<tr><td>Body:</td><td><textarea name=\"body\" rows=\"10\" cols=\"40\"></textarea></td></tr>
	<tr><td><input type=\"submit\" name=\"send\" value=\"send\" /></td></tr>
	</form></table>";
	
	print_mail($stuff);
}

// if they have written the message and now want to send it
elseif(isset($_POST['send']))
{
	$usertype = user_type();

	$to = escape_string($_POST['to']);
	if(is_numeric($to) == FALSE){ die("The 'to' field should work correctly if you don't mess with it."); }
	
	$subject = escape_string(htmlspecialchars($_POST['subject']));
	$body = htmlspecialchars($_POST['body']);
	$body = str_replace("\n", "<br />", $body);
	$body = escape_string($body);
	
	connect_sql();
	
	// see if they're allowed to send to their requested addressee
	$type = @query("SELECT `type` FROM `users` WHERE `ID`='$to' LIMIT 1") or cust_die("That user does not exist.");
	while($result = result($type))
	{
		$requested_type = $result->type;
	}
	
	if($usertype == "user" AND $requested_type == "1"){ cust_die("You may not message anyone besides a teacher or administrator."); }
	
	$timestamp = time();
	$from = $_SESSION['id'];
	
	@query("INSERT INTO `mail` (`from`, `to`, `subject`, `body`, `timestamp`, `read`, `deleted`) VALUES ('$from', '$to', '$subject', '$body', '$timestamp', '0', '0')") or die("Error sending the message.");
	
	print_mail("The message has been sent.  <a href=\"messages.php?compose\" title=\"send a message\">Send another</a>?");
	
	
	

	disconnect_sql();
}

// if they'd like to read a message
elseif(isset($_GET['read']))
{
	connect_sql();
	
	// if they want to read a specific message, see if they're able to, and if so, display it
	if(isset($_GET['id']))
	{
		$userid = $_SESSION['id'];
		$mailid = escape_string($_GET['id']);	if(is_numeric($mailid) == FALSE){ cust_die("You shouldn't mess with the ID. ;D"); }
		
		$message = @query("SELECT * FROM `mail` WHERE `id`='$mailid' AND `to`='$userid'") or cust_die("You may not access that message.");
		if(num_rows($message) == 0){ cust_die("That message does not exist."); }
		
		while($row = result($message))
		{
	
			$fromid = $row->from;
			$from = @query("SELECT `firstname`, `surname` FROM `users` WHERE `id`='$fromid'") or die("Error checking the database.");
			while($row2 = result($from))
			{
				$sender = stripslashes($row2->firstname) . " " . stripslashes($row2->surname);
			}

			$subject = stripslashes($row->subject);
			$body = stripslashes($row->body);
				
			$date = date(timeformat, $row->timestamp); $date .= " on "; $date .= date(dateformat, $row->timestamp);			

		$stuff = "<div class=\"mail_title\" style=\"text-decoration: underline; \">{$subject}</div><div class=\"mail_byline\">Sent {$date} by {$sender}</div><div class=\"mail_body\">{$body}</div><hr width=\"85%\" align=\"left\" /><div class=\"mail_footer\"><a href=\"messages.php?compose&amp;id={$row->from}}\" title=\"send a reply\">reply</a>";
		// only display the link to delete the post if it's not deleted
		if($row->deleted == 0){ $stuff .= "&nbsp;&nbsp;:&nbsp;&nbsp;<a href=\"messages.php?delete&amp;id={$mailid}\" title=\"delete this message\">delete</a>"; }
		$stuff .= "</div>";
	}

		// tell the database that the message has been read
		@query("UPDATE `mail` SET `read`='1' WHERE `id`='$mailid'") or die("Error updating the database.");
		
	}
	
	// if not, display a list of their messages
	else
	{
		$id = $_SESSION['id'];
		$messages = @query("SELECT * FROM `mail` WHERE `to`='$id' AND `deleted`='0' ORDER BY `id` DESC");
		
		if(num_rows($messages) == 0){ $stuff = "You do not have any messages."; }

		else
		{
			$pass = gen_rss_pass($id);
			$stuff = "<table><tr><th>From</th><th>Subject</th><th>Date</th><td><a href=\"messages.php?xml&amp;u={$id}&amp;p={$pass}\" title=\"inbox XML feed\"><img src=\"images/xml.gif\" alt=\"XML feed\" /></a></td></tr>";
			$tdcolour = 0;
			
			while($row = result($messages))
			{
				$messageid = $row->id;
			
				$from = $row->from;
				$from2 = @query("SELECT `firstname`, `surname` FROM `users` WHERE `ID`='$from'");
			
				while($row2 = result($from2))
				{
					$from = stripslashes($row2->firstname) . " " . stripslashes($row2->surname);
				}

				$subject = stripslashes($row->subject);
				
				$date = date(timeformat, $row->timestamp); $date .= " on "; $date .= date(dateformat, $row->timestamp);
				
				$stuff .= "<tr class=\"tdcolour{$tdcolour}\"><td>{$from}</td><td><a href=\"messages.php?read&amp;id={$messageid}\" title=\"read the message\">{$subject}</a></td><td>{$date}</td></tr>";
				
                                if($tdcolour == 1){ $tdcolour = 0; } else{ $tdcolour++; }
			}
			
			$stuff .= "</table>";
			
		}
	}
	
	print_mail($stuff);
	
	disconnect_sql();
}

// if they're tired of a message and would like to delete it
elseif(isset($_GET['delete']))
{
	connect_sql();

	if(!isset($_GET['id'])){ header("Location: messages.php"); die(); }
	
	$userid = $_SESSION['id'];
	$mailid = escape_string($_GET['id']);	if(is_numeric($mailid) == FALSE){ cust_die("You shouldn't mess with the ID."); }
		
	$message = @query("SELECT * FROM `mail` WHERE `id`='$mailid' AND `to`='$userid'") or cust_die("You may not access that message.");
	
	@query("UPDATE `mail` SET `deleted`='1' WHERE `id`='$mailid'") or die("Error updating the database.");
	
	print_mail("The message has been deleted.");
	
	disconnect_sql();
}


// if they'd like to see the messages they have sent
elseif(isset($_GET['outbox']))
{
	connect_sql();

	$id = $_SESSION['id'];
	$messages = @query("SELECT * FROM `mail` WHERE `from`='$id'") or die("Error getting information from the database.");
		
	if(num_rows($messages) == 0){ $stuff = "You have not sent any messages."; }

	else
	{
			
		$stuff = "<table><tr><th>To</th><th>Subject</th><th>Date</th></tr>";
		$tdcolour = 0;
			
		while($row = result($messages))
		{
			$messageid = $row->id;
			
			$to = $row->to;
			$to_name = @query("SELECT `firstname`,`surname` FROM `users` WHERE `ID`='$to'") or die("Error checking the database.");
			$to_name = result($to_name);
			$to = stripslashes($to_name->firstname) . " " . stripslashes($to_name->surname);

			$subject = stripslashes($row->subject);
			
			$date = date(timeformat, $row->timestamp); $date .= " on "; $date .= date(dateformat, $row->timestamp);
				
			$stuff .= "<tr class=\"tdcolour{$tdcolour}\"><td>{$to}</td><td><a href=\"messages.php?read&amp;id={$messageid}\" title=\"read the message\">{$subject}</a></td><td>{$date}</td></tr>";
				
                        if($tdcolour == 1){ $tdcolour = 0; } else{ $tdcolour++; }
		}
			
		$stuff .= "</table>";
		
	}
		
	print_mail($stuff);
	
	disconnect_sql();
}


// if they'd like to see their deleted messages..
elseif(isset($_GET['trash']))
{
	connect_sql();

	$id = $_SESSION['id'];
	$messages = @query("SELECT * FROM `mail` WHERE `to`='$id' AND `deleted`='1'");
		
	if(num_rows($messages) == 0){ $stuff = "You do not have anything in your trash."; }

	else
	{
			
		$stuff = "<table><tr><th>From</th><th>Subject</th><th>Date</th></tr>";
		$tdcolour = 0;
			
		while($row = result($messages))
		{
			$messageid = $row->id;
		
			$from = $row->from;
			$from2 = @query("SELECT `firstname`, `surname` FROM `users` WHERE `ID`='$from'") or die("Error getting information from the database.");
			
			while($row2 = result($from2))
			{
				$from = stripslashes($row2->firstname) . " " . stripslashes($row2->surname);
			}

			$subject = stripslashes($row->subject);
				
			$date = date(timeformat, $row->timestamp); $date .= " on "; $date .= date(dateformat, $row->timestamp);
			
			$stuff .= "<tr class=\"tdcolour{$tdcolour}\"><td>{$from}</td><td><a href=\"messages.php?read&amp;id={$messageid}\" title=\"read the message\">{$subject}</a></td><td>{$date}</td></tr>";
				
                        if($tdcolour == 1){ $tdcolour = 0; } else{ $tdcolour++; }
		}
			
		$stuff .= "</table>";
			
	}
	
	print_mail($stuff);
	
	disconnect_sql();
}


else
{
	any_errors();
	$messages = check_mail();
	$stuff = "You have {$messages} new message"; if($messages != 1){ $stuff .= "s"; } $stuff .= ".";

/*	-- coming next release

	// if the user is an administrator, print the 'system messages' box
	if(user_type() == "admin")
	{
		$stuff .= "<td rowspan=\"2\" colspan=\"4\" class=\"mail_content_holder\">System Alerts:  ";
		if(number_of_system_alerts() > 0){ $stuff .= "<b>"; } $stuff .= number_of_system_alerts(); if(number_of_system_alerts() > 0){ $stuff .= "</b>"; }
		$stuff .= "</td>";
	}
*/

	print_mail($stuff);
}

print("</div>");

display_copyright();
display_footer();

?>
