<?php
# <one line to give a brief idea of what this does.>
# 
# Copyright 1999-2000 (c) The SourceForge Crew
# Copyright 2000-2003 (c) Free Software Foundation
#
# Copyright 2006 (c) Mathieu Roy <yeupou--gnu.org>
#
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
require_once('../../include/project/admin.php');
register_globals_off();

if (member_check(0, $group_id))
{
  site_project_header(array('title'=>_("Project History"),'group'=>$group_id,'context'=>'ahome'));
  
  show_grouphistory($group_id);

  site_project_footer(array());
}
else
{
  exit_permission_denied();
}
