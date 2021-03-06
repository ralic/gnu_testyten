<?php
# <one line to give a brief idea of what this does.>
# 
# Copyright 1999-2000 (c) The SourceForge Crew
#  Copyright 2004      (c) ...
# 
# This file is part of Savane.
# 
# Savane is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as
# published by the Free Software Foundation, either version 3 of the
# License, or (at your option) any later version.
# 
# Savane is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
# 
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.


require_once('../../include/init.php');

extract(sane_import('request', array('people_cat', 'people_skills')));
extract(sane_import('post', array('post_changes', 'cat_name', 'skill_name')));

if (user_ismember(1,'A'))
{

  if ($post_changes) 
    {
      /*
			Update the database
      */

      if ($people_cat) 
	{

	  $result = db_execute("INSERT INTO people_job_category (name) VALUES (?)", array($cat_name));
	  if (!$result) 
	    {
	      print db_error();
	      fb(_("Error inserting value"));
	    }

	  fb(_("Category Inserted"));

	} 
      else if ($people_skills) 
	{

	  $result=db_execute("INSERT INTO people_skill (name) VALUES (?)", array($skill_name));
	  if (!$result) 
	    {
	      print db_error();
	      fb(_("Error inserting value"));
	    }

	  fb(_("Skill Inserted"));
	  /*
		} else if ($people_cat_mod) 
{

			$sql="UPDATE people_category SET category_name='$cat_name' WHERE people_category_id='$people_cat_id' AND group_id='$group_id'";
			$result=db_query($sql);
			if (!$result || db_affected_rows($result) < 1) 
{
  ' Error modifying bug category ';
  print db_error();
} else {
  ' Bug Category Modified ';
}

} else if ($people_group_mod) 
{

  $sql="UPDATE people_group SET group_name = '$group_name' WHERE people_group_id='$people_group_id' AND group_id='$group_id'";
  $result=db_query($sql);
  if (!$result || db_affected_rows($result) < 1) 
    {
      ' Error modifying bug cateogry ';
      print db_error();
    } else {
      ' Bug Category Modified ';
    }
  */
      }

} 
/*
		Show UI forms
*/

if ($people_cat) 
{
  /*
			Show categories and blank row
  */  

  print site_header(array('title'=>'Add/Change Categories'));


  print '<h2>'._("Add Job Categories").'</h2>';

  /*
			List of possible categories for this group
  */
  $result = db_query("SELECT category_id,name FROM people_job_category");
  print "<P>";
  if ($result && db_numrows($result) > 0) 
    {
      utils_show_result_set($result,_("Existing Categories"),'people_cat');
    } 
  else 
    {
      print '
				<h1>'._("No job categories").'</h1>';
      print db_error();
    }


  print '<p>';
  print '<h3>'._("Add a new job category:").'</h3>';
  print '<p>';
  print '<form action="'.$_SERVER['PHP_SELF'].'" method="post">';
  print '<input type="hidden" name="people_cat" value="y" />';
  print '<input type="hidden" name="post_changes" value="y" />';
  print '<h4>'._("New Category Name:").'</h4>';
  print '<input type="text" name="cat_name" value="" size="15" maxlength="30" /><br />';
  print '<p>';
  print '<strong><span class="warn">'._("Once you add a category, it cannot be deleted").'</span></strong></p>';
  print '<p>';
  print '<input type="submit" name="submit" value="'._("Add").'" /></p>';
  print '</form>';
	

  site_project_footer(array());

} 
else if ($people_skills) 
{
  /*
			Show people_groups and blank row
  */
  print site_header(array('title'=>'Add/Change People Skills'));


  print '<h2>'._("Add Job Skills").'</h2>';

  /*
			List of possible people_groups for this group
  */
  $result = db_query("SELECT skill_id,name FROM people_skill");
  print "<p>";
  if ($result && db_numrows($result) > 0) 
    {
      utils_show_result_set($result,_("Existing Skills"),"people_skills");
    } 
  else 
    {
      print db_error();
      print "\n<h2>"._("No Skills Found").'</h2>';
    }
		
  print '<p><h3>'._("Add a new skill:").'</h3></p>';
  print '<p>
		<form action="'.$_SERVER['PHP_SELF'].'" method="post">
		<input type="hidden" name="people_skills" value="y" />
		<input type="hidden" name="post_changes" value="y" /></p>';
  print '<h4>'._("New Skill Name:").'</h4>';
  print '<input type="text" name="skill_name" value="" size="15" maxlength="30" /><br />';
  print '<p><strong><span class="warn">'._("Once you add a skill, it cannot be deleted").'</span></strong></p>';
  print '<p><input type="submit" name="submit" value="'._("Add").'" /></p>';
  print '</form>';

  site_project_footer(array());

} 
else
{
  /*
			Show main page
  */

  print site_header(array('title'=>'People Administration'));

  print '<h2>'._("Help Wanted Administration").'</h2>';

  print '<p><a href="'.$_SERVER['PHP_SELF'].'?people_cat=1">'._("Add Job Categories").'</a><br />';
  #	print "\nAdd categories of bugs like, 'mail module','gant chart module','interface', etc<P>";

  print "\n<a href=\"{$_SERVER['PHP_SELF']}?people_skills=1\">"._("Add Job Skills").'</a><br />';
  #	print "\nAdd Groups of bugs like 'future requests','unreproducible', etc<P>";

  site_project_footer(array());
}

} 
else
{
  exit_permission_denied();
}
