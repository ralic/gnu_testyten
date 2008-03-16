#!/usr/bin/perl
# Copyright (C) 2007  Sylvain Beucler
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

# Init GIT repository.

use strict;
use warnings;

require Exporter;
our @ISA = qw(Exporter);
our @EXPORT = qw(GitMakeArea);
our $version = 1;

sub GitMakeArea {
    my ($name,$dir_git) = @_;
    my $warning = '';

    # %PROJECT is not mandatory, but if it is missing, it may well be 
    # a major misconfiguration.
    # It should only happen if a directory has been set for a specific 
    # project.
    unless ($dir_git =~ s/\%PROJECT/$name/) {
	$warning = " (The string \%PROJECT was not found, there may be a group type serious misconfiguration)";
    }

    unless (-e $dir_git) {
	# Layout: /srv/git/sources/project_name.git
        #         /srv/git/sources/project_name/other_module.git (TODO)
	
	# Create a repository
	my $old_umask = umask(0002);

        # --shared sets g+s on directories
	$ENV{'GIT_DIR'} = $dir_git;
	system('git-init', '--shared');
	delete $ENV{'GIT_DIR'};
	
	system('chgrp', '-R', $name, $dir_git);
        # needed to make the repo accessible via bare HTTP
	system('chmod', '+x', $dir_git.'/hooks/post-update');
	# forbid access to hooks
	system('chown', '-R', 'root:', $dir_git.'/hooks');
	system('chattr', '+i', $dir_git.'/hooks');

	# Create folder for subrepositories (need to code multi-repo support first)
	# TODO: precise directory location
	#system('mkdir', '-m', '2775', ".../$name/");
	#system('chown', "root:$name", ".../$name/");

	# 'git-cvsserver' support
	system('git-config', 'gitcvs.pserver.enabled', 1);
	system('git-config', 'gitcvs.ext.enabled', 0);
	system('git-config', 'gitcvs.dbname', '%G/gitcvs-db/sqlite');
	my $sqlite_dir = "$dir_git/gitcvs-db";
	system('mkdir', $sqlite_dir, '-m', '755');
	system('chown', 'nobody', $sqlite_dir);

	umask($old_umask);
	return ' '.$dir_git.$warning;	
    }
    return 0;
}
