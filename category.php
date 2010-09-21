<?php
/**
 * allows a teacher to setup, modify, or delete his or her class' categories
 *
 * \author Kevin Richardson <borismalcov@users.sourceforge.net>
 * \version $Id: category.php,v 1.4 2006/07/19 19:54:52 borismalcov Exp $
 */

include("lib/main.php");

display_header("categories");

if(is_logged_in() == FALSE){ $_SESSION['not_this_page'] = 1; cust_die("You'll need to login to access the page you've requested."); }
if(user_type() != "teacher"){ $_SESSION['not_this_page'] = 1; cust_die("This page is for teachers."); }

display_menu();
print("<div class=\"container2\">");

connect_sql();

if(isset($_GET['teacherid']))
{
	// see if the ID is valid
	if(is_numeric($_GET['teacherid']) == FALSE){ cust_die("Invalid ID."); }
	if(is_teacher(escape_string(htmlspecialchars($_GET['teacherid']))) == FALSE){ die("Invalid ID."); }
	
	if($_SESSION['id'] != $_GET['teacherid']){ cust_die("Session ID and teacher ID do not match."); }
	
	$teacherid = escape_string(htmlspecialchars($_GET['teacherid']));


	if(isset($_GET['classid']))
	{
		// see if it's a valid ID; if it is, see if the teacher teaches it
		if(is_numeric($_GET['classid']) == FALSE){ cust_die("Invalid ID."); }
		$classid = escape_string(htmlspecialchars($_GET['classid']));
		
		if(teacher_teaches($teacherid, $classid) == FALSE){ cust_die("The teacher does not teach that class."); }
		
		// removes a category
		if(isset($_GET['delete']))
		{
			if(is_numeric($_GET['delete']) == FALSE){ cust_die("Invalid ID."); }
			$to_delete = escape_string(htmlspecialchars($_GET['delete']));
			
			$is_category = @query("SELECT 1 FROM `categories` WHERE `ID`='$to_delete' AND `class`='$classid' LIMIT 1") or die("Error checking the database.");
			if(num_rows($is_category) == 0){ cust_die("Invalid ID."); }
			
			// make sure he or she wants to delete the category
			if(isset($_GET['confirm']))
			{
				@query("DELETE FROM `categories` WHERE `ID`='$to_delete' LIMIT 1") or die("Error removing the category from the database.");
			
				print("Done.  Back to <a href=\"category.php?teacherid={$teacherid}&classid={$classid}\" title=\"class's category page\">the class's category page</a>?");
			}
			
			else
			{
				print("<p>Are you sure you want to remove the category?</p>\n<ul>\n<li><a href=\"classes.php\" title=\"classes page\">No, take me back to my classes page</a>.</li>\n<li><a href=\"category.php?teacherid={$teacherid}&classid={$classid}&delete={$to_delete}&confirm\" title=\"delete\">Yes, please remove it</a>.</li>\n</ul>\n");
			}
		}
		
		elseif(isset($_GET['modifyweight']))
		{
			// make sure its valid and whatnot
			if(is_numeric($_GET['modifyweight']) == FALSE){ cust_die("Invalid ID."); }
			$weight_to_modify = escape_string(htmlspecialchars($_GET['modifyweight']));
			
			$is_weight = @query("SELECT 1 FROM `categories` WHERE `ID`='$weight_to_modify' AND `class`='$classid' LIMIT 1") or die("Error checking the database.");
			if(num_rows($is_weight) == 0){ cust_die("Invalid ID."); }
			
			// 'kay, it's good. Get its information and print a form
			
			$info = @query("SELECT * FROM `categories` WHERE `ID`='$weight_to_modify' LIMIT 1") or die("Error checking the database.");
			
			while($row = result($info))
			{
				print("<table id=\"modifyweighttable\"><form name=\"modifyweightform\" action=\"category.php\" method=\"post\">\n");
				print("<tr><td>Name:</td><td><input type=\"text\" name=\"categoryname\" value=\"" . stripslashes($row->name) . "\" width=\"50\" maxlength=\"50\" /></td></tr>\n");
				print("<tr><td>Weight:</td><td><input type=\"text\" name=\"categoryweight\" value=\"" . stripslashes($row->weight) . "\" width=\"10\" maxlength=\"10\" /></td></tr>\n");
				print("<tr><td><input type=\"hidden\" name=\"categoryid\" value=\"{$row->ID}\" /><input type=\"hidden\" name=\"classid\" value=\"{$classid}\" /><input type=\"submit\" name=\"modifycategory\" value=\"modify\"></td><td><a href=\"category.php?teacherid={$teacherid}&classid={$classid}&delete={$weight_to_modify}\" title=\"delete\">delete</a></td></tr>\n");
				print("</form></table>\n");
			}
		}
		
		else
		{
			// okay, everything seems to be good.  If the class has categories, list them.  If not, show a form for creating them.
		
			if(class_has_categories($classid) == TRUE)
			{
				print("<p style=\"font-weight: bold\">Modify a category:</p>");
		
				print("<ul>\n");
			
				// list the categories with a link to modify them
				$categories = return_categories($classid);
				$categories = explode(":::::", $categories);
		
				foreach($categories as $part)
				{
					if($part != "")
					{
						list($id, $category) = explode(":", $part);
				
						// get its weight
						$weight = @query("SELECT `weight` FROM `categories` WHERE `ID`='$id' LIMIT 1") or die("Error checking the database.");
						while($row = result($weight))
						{
							print("<li><a href=\"category.php?teacherid={$_SESSION['id']}&classid={$classid}&modifyweight={$id}\" title=\"modify weight\">{$category}:  {$row->weight}%</a></li>\n");
						}
					}
				}
			
				print("</ul>");
			
			
				print("<table id=\"categorytable\"><form id=\"categoryform\" action=\"category.php\" method=\"post\">\n");
				print("<tr><th colspan=\"2\">Add a category</th></tr>\n");
				print("<tr><td>Category name:</td><td><input type=\"text\" name=\"categoryname\" width=\"50\" maxlength=\"50\" /></td></tr>\n");
				print("<tr><td>Weight (out of 100):</td><td><input type=\"text\" name=\"categoryweight\" width=\"10\" maxlength=\"10\" /></td></tr>\n");
				print("<tr><td><input type=\"hidden\" name=\"classid\" value=\"{$classid}\" /><input type=\"submit\" name=\"addcategory\" value=\"add\" /></td></tr>\n");
				print("</form></table>\n");
			}
		
			else
			{
				print("<table id=\"categorytable\"><form id=\"categoryform\" action=\"category.php\" method=\"post\">\n");
				print("<tr><th colspan=\"2\">Add a category</th></tr>\n");
				print("<tr><td>Category name:</td><td><input type=\"text\" name=\"categoryname\" width=\"50\" maxlength=\"50\" /></td></tr>\n");
				print("<tr><td>Weight (out of 100):</td><td><input type=\"text\" name=\"categoryweight\" width=\"10\" maxlength=\"10\" /></td></tr>\n");
				print("<tr><td><input type=\"hidden\" name=\"classid\" value=\"{$classid}\" /><input type=\"submit\" name=\"addcategory\" value=\"add\" /></td></tr>\n");
				print("</form></table>\n");
			}
		}
	}
	
	else
	{
		print("<p>Setup or modify categories of:</p>");
	
		display_classes();
	}
}

elseif(isset($_POST['addcategory']))
{
	$teacherid = $_SESSION['id'];

	if(!isset($_POST['categoryname']) OR $_POST['categoryname'] == ""){ cust_die("You must submit the category's name."); }
	if(strlen($_POST['categoryname']) > 50){ cust_die("Your category's name must be 50 characters or fewer."); }
	$categoryname = escape_string(htmlspecialchars($_POST['categoryname']));
	
	if(!isset($_POST['classid']) OR $_POST['classid'] == ""){ cust_die("You must submit the class's ID."); }
	if(is_numeric($_POST['classid']) == FALSE){ cus_die("Invalid class ID."); }
	$classid = escape_string(htmlspecialchars($_POST['classid']));
	
	// make sure the user teachers the class
	if(teacher_teaches($teacherid, $classid) == FALSE){ cust_die("You do not teach that class."); }
	
	if(!isset($_POST['categoryweight']) OR $_POST['categoryweight'] == ""){ cust_die("You must submit the category's weight."); } 
	if(strlen($_POST['categoryweight']) > 10){ cust_die("The category's weight must be 10 characters or fewer."); }
	if($_POST['categoryweight'] > 100){ cust_die("The category's weight cannot exceed 100"); }
	$categoryweight = escape_string(htmlspecialchars($_POST['categoryweight']));
	
	// if the class already has categories, make sure their combined total doesn't exceed 100
	if(class_has_categories($classid) == TRUE)
	{
		$total = "";
	
		$categories = return_categories($classid);
		$categories = explode(":::::", $categories);
		
		foreach($categories as $part)
		{
			if($part != "")
			{
				list($id, $category) = explode(":", $part);
			
				// get the category's weight
				$weight = @query("SELECT `weight` FROM `categories` WHERE `ID`='$id' LIMIT 1") or die("Error checking the database.");
				while($row = result($weight))
				{
					$total += $row->weight;
				}
			}
		}
		
		$total += $categoryweight;
		
		if($total > 100){ cust_die("The total weights of your categories will exceed 100; this cannot happen."); }
	}
	
	// okay, 'tis good.  Add it.
	@query("INSERT INTO `categories` (`teacher`, `class`, `name`, `weight`) VALUES ('$teacherid', '$classid', '$categoryname', '$categoryweight')") or die("Error adding the weight to the database.");
	
	print("Done.  <a href=\"category.php?teacherid={$teacherid}&classid={$classid}\" title=\"add another\">Add another</a>?");
}

elseif(isset($_POST['modifycategory']))
{
	$teacherid = $_SESSION['id'];

	if(!isset($_POST['categoryname']) OR $_POST['categoryname'] == ""){ cust_die("You must submit the category's name."); }
	if(strlen($_POST['categoryname']) > 50){ cust_die("Your category's name must be 50 characters or fewer."); }
	$categoryname = escape_string(htmlspecialchars($_POST['categoryname']));
	
	if(!isset($_POST['classid']) OR $_POST['classid'] == ""){ cust_die("You must submit the class's ID."); }
	if(is_numeric($_POST['classid']) == FALSE){ cus_die("Invalid class ID."); }
	$classid = escape_string(htmlspecialchars($_POST['classid']));
	
	// make sure the user teachers the class
	if(teacher_teaches($teacherid, $classid) == FALSE){ cust_die("You do not teach that class."); }
	
	if(!isset($_POST['categoryid']) OR $_POST['categoryid'] == ""){ cust_die("You must submit the category's ID."); }
	if(is_numeric($_POST['categoryid']) == FALSE){ cus_die("Invalid category ID."); }
	$categoryid = escape_string(htmlspecialchars($_POST['categoryid']));
	
	// make sure it's valid
	$occurance = @query("SELECT 1 FROM `categories` WHERE `ID`='$categoryid' AND `class`='$classid' LIMIT 1") or die("Error checking the database.");
	if(num_rows($occurance) == 0){ cust_die("Invalid category ID."); }
	
	if(!isset($_POST['categoryweight']) OR $_POST['categoryweight'] == ""){ cust_die("You must submit the category's weight."); } 
	if(strlen($_POST['categoryweight']) > 10){ cust_die("The category's weight must be 10 characters or fewer."); }
	if($_POST['categoryweight'] > 100){ cust_die("The category's weight cannot exceed 100"); }
	$categoryweight = escape_string(htmlspecialchars($_POST['categoryweight']));

	// make sure the categories' combined total doesn't exceed 100
	$total = "";
	
	$categories = return_categories($classid);
	$categories = explode(":::::", $categories);
		
	foreach($categories as $part)
	{
		if($part != "")
		{
			list($id, $category) = explode(":", $part);
			
			// we don't want to include the modified category twice
			if($id != $categoryid)
			{
			
				// get the category's weight
				$weight = @query("SELECT `weight` FROM `categories` WHERE `ID`='$id' LIMIT 1") or die("Error checking the database.");
				while($row = result($weight))
				{
					$total += $row->weight;
				}
			}
		}
	}
		
	$total += $categoryweight;
		
	if($total > 100){ cust_die("The total weights of your categories will exceed 100; this cannot happen."); }

	// 'kay, update the database.
	@query("UPDATE `categories` SET `name`='$categoryname', `weight`='$categoryweight' WHERE `ID`='$categoryid'") or die("Error updating the database.");
	
	print("Done.  Back to the <a href=\"category.php?teacherid={$teacherid}&classid={$classid}\" title=\"categories page\">class's category page</a>, or the <a href=\"category.php?teacherid={$teacherid}\" title=\"main category page\">main category page</a>?");
}

else
{	
	any_errors();
	
	$id = $_SESSION['id'];
	
	// print some documentation about categories
	
	print("<p>Categories are used to weigh assignments differently.  For example, you can have tests weighted at 50%, and 50% of a student's grade will come from his or her tests.</p>\n");
	print("<p>Currently, you must setup categories for each of your classes.  If it is requested, this can be eventually changed so your classes can use the same categories.</p>\n");
	print("<p>Below are links to setup or modify your classes' categories.</p>\n");
	
	display_classes();
}

disconnect_sql();

print("</div>");
display_copyright();
display_footer();
?>
