<?php
# Manage commit hooks/triggers
# Copyright (C) 2006  Sylvain Beucler

# This file is part of the Savane project
# <http://gna.org/projects/savane/>
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
require_once('../../include/sane.php');
require_once('../../include/account.php');

# get current information
$res_grp = group_get_result($group_id);

if (db_numrows($res_grp) < 1)
{
  exit_error(_("Invalid Group"));
}

session_require(array('group'=>$group_id,'admin_flags'=>'A'));


extract(sane_import('post',
  array(
    'log_accum',
    'arr_id', 'arr_remove',
    'arr_repo_name', 'arr_match_type', 'arr_dir_list', 'arr_branches',
    'arr_emails_notif', 'arr_enable_diff', 'arr_emails_diff',
)));

#echo "<pre>\n";
if (isset($log_accum)) {
  if (isset($arr_remove) and is_array($arr_remove)) {
    foreach ($arr_remove as $hook_id => $ignored) {
      if (!ctype_digit($hook_id.''))
	exit_error(_("Non-numeric hook id") . ": [" . htmlspecialchars($hook_id) . "]");
      db_execute("DELETE cvs_hooks, cvs_hooks_log_accum FROM cvs_hooks, cvs_hooks_log_accum
                  WHERE cvs_hooks.id = cvs_hooks_log_accum.hook_id
                    AND group_id=? AND id=?",
		 array($group_id, $hook_id)) or die(mysql_error());
    }
  }
  if (isset($arr_id) and is_array($arr_id)) {
    foreach ($arr_id as $hook_id => $ignored) {
      // Input validation
      if (!ctype_digit($hook_id.'') and ($hook_id != "new"))
	exit_error(_("Non-numeric hook id") . ": [" . htmlspecialchars($hook_id) . "]");
      
      if (!isset($arr_remove[$hook_id])) {
	$repo_name = $arr_repo_name[$hook_id];
	if ($repo_name != 'sources' and $repo_name != 'web')
	  exit_error(_("Invalid repository name: " . htmlspecialchars($repo_name)));
	$match_type = $arr_match_type[$hook_id];
	if ($match_type != 'ALL' and $match_type != 'dir_list' and $match_type != 'DEFAULT')
	  exit_error(_("Invalid matching type"));
	$dir_list = $arr_dir_list[$hook_id];
	if ($match_type != 'dir_list')
	  unset($dir_list);
	else if ($dir_list == '' or preg_match('/[[:space:]]/', $dir_list) or !preg_match("/^(([a-zA-Z0-9_.+-\/]+)(,|$))+/", $dir_list))
	  exit_error(_("Invalid directories list"));
	$branches = $arr_branches[$hook_id];
	if ($branches == '')
	  unset($branches);
	$enable_diff = '0';
	if (isset($arr_enable_diff[$hook_id]))
	  $enable_diff = $arr_enable_diff[$hook_id];
	if ($enable_diff != '0' and $enable_diff != '1')
	  exit_error(_("Invalid value for enable_diff"));
	$emails_notif = $arr_emails_notif[$hook_id];
	if (!preg_match('/^(([a-zA-Z0-9_.+-]+@(([a-zA-Z0-9-])+.)+[a-zA-Z0-9]+)(,|$))+/', $emails_notif))
	  exit_error(_("Invalid list of notification e-mails"));
	$emails_diff = $arr_emails_diff[$hook_id];
	if ($emails_diff == '' or $enable_diff == '0')
	  unset($emails_diff);
	else if (!preg_match('/^(([a-zA-Z0-9_.+-]+@(([a-zA-Z0-9-])+.)+[a-zA-Z0-9]+)(,|$))*/', $emails_diff))
	  exit_error(_("Invalid list of diff notification e-mails"));

	if ($hook_id == 'new') {
	  // New entry
	  db_autoexecute('cvs_hooks',
			 array(
			       'group_id' => $group_id,
			       'repo_name' => $repo_name,
			       'match_type' => $match_type,
			       'dir_list' => (!isset($dir_list) ? null : $dir_list),
			       'hook_name' => 'log_accum',
			       'needs_refresh' => 1),
			 DB_AUTOQUERY_INSERT) or die(mysql_error());
	  $new_hook_id = mysql_insert_id();
	  db_autoexecute('cvs_hooks_log_accum',
			 array('hook_id' => $new_hook_id,
			       'branches' => (!isset($branches) ? null : $branches),
			       'emails_notif' => $emails_notif,
			       'enable_diff' => $enable_diff,
			       'emails_diff' => (!isset($emails_diff) ? null : $emails_diff)),
			 DB_AUTOQUERY_INSERT) or die(mysql_error());
	} else {
	  // Update existing entry
	  db_autoexecute('cvs_hooks, cvs_hooks_log_accum',
			 array(
			       'repo_name' => $repo_name,
			       'match_type' => $match_type,
			       'dir_list' => (!isset($dir_list) ? null : $dir_list),
			       'hook_name' => 'log_accum',
			       'needs_refresh' => 1,
			       'branches' => (!isset($branches) ? null : $branches),
			       'emails_notif' => $emails_notif,
			       'enable_diff' => $enable_diff,
			       'emails_diff' => (!isset($emails_diff) ? null : $emails_diff)),
			 DB_AUTOQUERY_UPDATE,
			 "cvs_hooks.id = cvs_hooks_log_accum.hook_id AND group_id=? AND id=?",
			 array($group_id, $hook_id)) or die(mysql_error());
	}
      }
    }
  }
}
#echo "</pre>\n";


site_project_header(array('group'=>$group_id,'context'=>'ahome'));

$available_hooks = array();
echo "<p>";
echo "Available hooks:<br />";
echo "</p>";
echo "<ul>";
foreach (glob(dirname(__FILE__).'/hooks/*.php') as $filename) {
  array_push($available_hooks, preg_replace(':.*/([0-9a-zA-Z_]+).php$:', '\1', $filename));
}

foreach ($available_hooks as $hook) {
  echo "<li>$hook</li>";
}
echo "</ul>";



$hook = 'log_accum';
// Show the project's log_accum hooks
$result =  db_execute("
SELECT hook_id, repo_name, match_type, dir_list, hook_name, needs_refresh,
	branches, emails_notif, enable_diff, emails_diff
FROM cvs_hooks
JOIN cvs_hooks_$hook ON cvs_hooks.id = hook_id
WHERE group_id = ?", array($group_id));

echo "<h2>log_accum</h2>";
echo "<h3>Current notifications</h3>";
echo "<form action='{$_SERVER['PHP_SELF']}?group=$group' method='post'>";
echo "<table>";
echo html_build_list_table_top(array('X', 'Repository', 'Match type', 'Module list', 'Branch filter', 'Notification to', 'Diff?', 'Separate diffs to', 'Updated?'));

while ($row = mysql_fetch_assoc($result)) {
  $cur= $row['hook_id'];
  echo "<tr>\n";
  echo "<td>";
  echo "<input type='hidden' name='arr_id[$cur]' value='$cur' />\n";
  echo html_build_checkbox("arr_remove[$cur]", 0);
  echo "</td>\n";
  echo "<td>";
  echo html_build_select_box_from_array(array('sources', 'web'), "arr_repo_name[$cur]", $row['repo_name'], 1);
  echo "</td>\n";
  echo "<td>";
  echo html_build_select_box_from_arrays(array('ALL', 'dir_list', 'DEFAULT'),
					 array('Always', 'Module list', 'Fallback'),
					 "arr_match_type[$cur]", $row['match_type'], 0);
  echo "</td>\n";
  echo "<td><input type='text' name='arr_dir_list[$cur]' value='{$row['dir_list']}' /></td>\n";
  echo "<td><input type='text' name='arr_branches[$cur]' value='{$row['branches']}' /></td>\n";
  echo "<td><input type='text' name='arr_emails_notif[$cur]' value='{$row['emails_notif']}' /></td>\n";
  echo "<td>";
  echo html_build_checkbox("arr_enable_diff[$cur]", $row['enable_diff']);
  echo "</td>\n";
  echo "<td><input type='text' name='arr_emails_diff[$cur]' value='{$row['emails_diff']}' /></td>\n";
  echo "<td>" . ($row['needs_refresh'] ? 'Scheduled' : 'Yes') . "</td>";
  echo "</tr>\n";
}

echo "</table>";
$caption=_("Modify");
echo "<input name='log_accum' type='submit' value='$caption' />";
echo "</form>";

// NEW
echo "<h3>New notification</h3>";
echo "<form action='{$_SERVER['PHP_SELF']}?group=$group' method='post'>";
echo "<ol>";
echo "<li>Repository: ";
echo html_build_select_box_from_array(array('sources', 'web'), "arr_repo_name[new]", $row['repo_name'], 1);
echo "</li><li>";
echo "Matching type: ";
echo html_build_select_box_from_arrays(array('ALL', 'dir_list', 'DEFAULT'),
					 array('Always', 'Module list', 'Fallback'),
					 "arr_match_type[new]", $row['match_type'], 0);
echo "<ul>
  <li><i>Always</i> is always performed (even in addition to Fallback)</li>
  <li><i>Module list</i> if you specify a list of directories (see below)<li/>
  <li><i>Fallback</i> is used when nothing matches</li>
</ul>";
echo "</li><li>";
echo "Filter by directory: if match is <i>Module list</i>, enter a list of directories separated by commas
  (eg: <code>emacs,emacs/lisp,manual</code>)<br />
  You'll only get notifications if the commit is performed in one of these directories.<br/>
  Leave blank if you want to get notifications for all the repository (default):<br />";
echo "<input type='text' name='arr_dir_list[new]' value='{$row['dir_list']}' />";
echo "</li><li>";
echo "List of comma-separated e-mails to send notifications to (eg: him@domain.org,her@domain.org):<br />";
echo "<input type='text' name='arr_emails_notif[new]' value='{$row['emails_notif']}' />";
echo "</li><li>";
echo "Send diffs? ";
echo html_build_checkbox("arr_enable_diff[new]", $row['enable_diff']);
echo "</li><li>";
echo "Optional alternate list of mails to send diffs separately to (eg: him@domain.org,her@domain.org).<br />
  If empty, the diffs will be included with the commit notifications:<br />";
echo "<input type='text' name='arr_emails_diff[new]' value='{$row['emails_diff']}' />\n";
echo "</li><li>";
echo "Filter by branch: you will be notified only of these branches' commits, separated by commas.<br />
  Enter <i>HEAD</i> if you only want trunk (non-branch) commits.<br />
  Leave blank if you want to get notifications for all commits (default):<br/>";
echo "<input type='text' name='arr_branches[new]' value='{$row['branches']}' />";
echo "</li></ol>";
echo "<input type='hidden' name='arr_id[new]' value='new' />";
$caption = _('Add');
echo "<input type='submit' name='log_accum' value='$caption' />
</form>";


$hook = 'cia';
// Show the project's log_accum hooks
$result =  db_execute("
SELECT repo_name, match_type, dir_list, hook_name, needs_refresh,
	project_account
FROM cvs_hooks
JOIN cvs_hooks_$hook ON cvs_hooks.id = hook_id
WHERE group_id = ?", array($group_id));

echo "<h2>cia (in progress)</h2>";
echo "<h3>Current CIA notifications</h3>";
echo "<form action={$_SERVER['PHP_SELF']}>";
echo "<table>";
echo html_build_list_table_top(array('X', 'Repository', 'Match type', 'Module list', 'CIA Project', 'Updated?'));

while ($row = mysql_fetch_assoc($result)) {
  echo "<tr>";
  echo "<td>";
  echo html_build_checkbox("remove[$cur]", 0);
  echo "</td>\n";
  echo "<td>";
  echo html_build_select_box_from_array(array('sources', 'web'), 'repo_name', $row['repo_name'], 1);
  echo "</td>";
  echo "<td>";
  echo html_build_select_box_from_arrays(array('ALL', 'dir_list', 'DEFAULT'),
					 array('Always', 'Module list', 'Fallback'),
					 'match_type', $row['match_type'], 0);
  echo "</td>";
  echo "<td><input type='text' name='dir_list[$cur]' value='{$row['dir_list']}' /></td>\n";
  echo "<td><INPUT TYPE='text' VALUE='{$row['project_account']}' /></td>";
  echo "<td>" . ($row['needs_refresh'] ? 0 : 1) . "</td>";
  echo "</tr>";  
}

echo "</table>";
echo "</form>";


site_project_footer(array());
