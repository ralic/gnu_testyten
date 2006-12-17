<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id: sessions.php 4977 2005-11-15 17:38:40Z yeupou $
#
#  Copyright 2004      (c) Mathieu Roy <yeupou--gnu.org>
#
# The Savane project is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# The Savane project is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with the Savane project; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA


require "../../include/pre.php";

register_globals_off();

# Check if the user is logged in.
session_require(array('isloggedin'=>'1'));


########################################################################
# Update the database
if (sane_get("func") == "del")
{
  $dsession_hash = sane_get("dsession_hash");
  $dip_addr = sane_get("dip_addr");
  $dtime = sane_get("dtime");
  $dkeep_one = sane_get("dkeep_one");
  
  if ($dsession_hash && $dip_addr && $dtime)
    {
      # Delete one session
      $dsession_hash = substr($dsession_hash, 0, 6)."%";
      $sql = "DELETE FROM session WHERE session_hash like '$dsession_hash' "
	 . " AND ip_addr='$dip_addr'"
	 . " AND time='$dtime'"
	 . " AND user_id='".user_getid()."'"
	 . " LIMIT 1";
      if (db_query($sql))
	{ fb(_("Old session deleted")); }
      else
	{ fb(_("Failed to delete old session"), 1); }
    }
  else if ($dkeep_one) 
    {
      # Delete all sessions apart from the current one
      $session_hash = sane_cookie("session_hash");
      $sql = "DELETE FROM session WHERE session_hash<>'$session_hash' "
	. " AND user_id='".user_getid()."'";

      if (db_query($sql))
	{ fb(_("Old sessions deleted")); }
      else
	{ fb(_("Failed to delete old sessions"), 1); }
    }
  else 	   
    {
      fb(_("Parameters missing, update canceled"), 1);
    }
}



########################################################################
# Actually prints the HTML page

site_user_header(array('title'=>_("Manage sessions"),
		       'context'=>'account'));



$res = db_query("SELECT session_hash,ip_addr,time FROM session WHERE "
		 . "user_id = '".user_getid()."' "
		 . "ORDER BY time DESC");

if (db_numrows($res) < 1)
{
  exit_error(_("No session found."));
}

print $HTML->box_top(_("Opened Sessions"));

$i = 0;
while ($row = db_fetch_array($res))
{
  $i++;
  if ($i > 1)
    { print $HTML->box_nextitem(utils_get_alt_row_color($i)); }

  # We destroy a part of the session hash because in no case we want to 
  # provide in clear text that complete information that could be used for
  # forgery (even if it is true that this page access is normally properly
  # restricted)	     
  
  $dsession_hash = substr($row['session_hash'], 0, 6)."...";

  # Do not incitate users to kill their own session
  print '<span class="trash">';
  if (sane_cookie("session_hash") != $row['session_hash'])
    {
      print utils_link($_SERVER['PHP_SELF'].'?func=del&amp;dsession_hash='.$dsession_hash.'&amp;dip_addr='.$row['ip_addr'].'&amp;dtime='.$row['time'],
		       '<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/trash.png" border="0" alt="'._("Kill this session").'" />');
    }
  else
    { print _("Current session"); }
  print '</span>';

  # I18N
  # The variables are: session identifier, time, remote host
  print sprintf(_("Session %s opened on %s from %s"), $dsession_hash, utils_format_date($row['time']), gethostbyaddr($row['ip_addr']))."<br />&nbsp;";

}

# Allow to kill sessions apart the current one,
# if more than 3 sessions were counted
# (otherwise, it looks overkill)
if ($i > 3)
{
  $i++;
  print $HTML->box_nextitem(utils_get_alt_row_color($i));
  print '<span class="trash">';
  print utils_link($_SERVER['PHP_SELF'].'?func=del&amp;dkeep_one=1',
		       '<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/trash.png" border="0" alt="'._("Kill all sessions").'" />');
  print '</span>'; 
  print '<em>'._("All sessions apart from the current one").'</em><br />&nbsp;';

}

print $HTML->box_bottom();


site_user_footer(array());


?>
