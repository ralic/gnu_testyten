#
#  Copyright 2004 (c) Mathieu Roy <yeupou--at--gnu.org>      
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
#
-- MySQL dump 9.07
--
-- Host: localhost    Database: savannah
---------------------------------------------------------
-- Server version	4.0.12-log

--
-- Table structure for table 'user_preferences'
--

CREATE TABLE group_preferences (
  group_id int(11) NOT NULL default '0',
  preference_name varchar(255) NOT NULL default '',
  preference_value text,
  PRIMARY KEY  (group_id,preference_name)
) TYPE=MyISAM;

