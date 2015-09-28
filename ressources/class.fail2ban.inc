<?php

class fail2ban{
	
	
	function fail2ban(){
		
		
		
	}
	
	function buildinit(){
		$INITD_PATH="/etc/init.d/fail2ban";
		$f[]="#! /bin/sh";
		$f[]="### BEGIN INIT INFO";
		$f[]="# Provides:          fail2ban";
		$f[]="# Required-Start:    \$local_fs \$remote_fs";
		$f[]="# Required-Stop:     \$local_fs \$remote_fs";
		$f[]="# Should-Start:      \$time \$network \$syslog iptables firehol shorewall ipmasq";
		$f[]="# Should-Stop:       \$network \$syslog iptables firehol shorewall ipmasq";
		$f[]="# Default-Start:     2 3 4 5";
		$f[]="# Default-Stop:      0 1 6";
		$f[]="# Short-Description: Start/stop fail2ban";
		$f[]="# Description:       Start/stop fail2ban, a daemon scanning the log files and";
		$f[]="#                    banning potential attackers.";
		$f[]="### END INIT INFO";
		$f[]="";
		$f[]="# Author: Aaron Isotton <aaron@isotton.com>";
		$f[]="# Modified: by Yaroslav Halchenko <debian@onerussian.com>";
		$f[]="#  reindented + minor corrections + to work on sarge without modifications";
		$f[]="#";
		$f[]="PATH=/usr/sbin:/usr/bin:/sbin:/bin";
		$f[]="DESC=\"authentication failure monitor\"";
		$f[]="NAME=fail2ban";
		$f[]="";
		$f[]="# fail2ban-client is not a daemon itself but starts a daemon and";
		$f[]="# loads its with configuration";
		$f[]="DAEMON=/usr/bin/\$NAME-client";
		$f[]="SCRIPTNAME=/etc/init.d/\$NAME";
		$f[]="";
		$f[]="# Ad-hoc way to parse out socket file name";
		$f[]="SOCKFILE=`grep -h '^[^#]*socket *=' /etc/\$NAME/\$NAME.conf /etc/\$NAME/\$NAME.local 2>/dev/null | tail -n 1 | sed -e 's/.*socket *= *//g' -e 's/ *\$//g'`";
		$f[]="[ -z \"\$SOCKFILE\" ] && SOCKFILE='/tmp/fail2ban.sock'";
		$f[]="";
		$f[]="# Exit if the package is not installed";
		$f[]="[ -x \"\$DAEMON\" ] || exit 0";
		$f[]="ENABLED=1";
		$f[]="if [ -f /etc/artica-postfix/settings/Daemons/EnableFail2Ban ]; then";
		$f[]="	ENABLED=`/bin/cat /etc/artica-postfix/settings/Daemons/EnableFail2Ban`";
		$f[]="fi";
	
		
		
		$f[]="";
		$f[]="# Read configuration variable file if it is present";
		$f[]="[ -r /etc/default/\$NAME ] && . /etc/default/\$NAME";
		$f[]="DAEMON_ARGS=\"\$FAIL2BAN_OPTS\"";
		$f[]="";
		$f[]="# Load the VERBOSE setting and other rcS variables";
		$f[]="[ -f /etc/default/rcS ] && . /etc/default/rcS";
		$f[]="";
		$f[]="# Predefine what can be missing from lsb source later on -- necessary to run";
		$f[]="# on sarge. Just present it in a bit more compact way from what was shipped";
		$f[]="log_daemon_msg () {";
		$f[]="	[ -z \"\$1\" ] && return 1";
		$f[]="	echo -n \"\$1:\"";
		$f[]="	[ -z \"\$2\" ] || echo -n \" \$2\"";
		$f[]="}";
		$f[]="";
		$f[]="# Define LSB log_* functions.";
		$f[]="# Depend on lsb-base (>= 3.0-6) to ensure that this file is present.";
		$f[]="# Actually has to (>=2.0-7) present in sarge. log_daemon_msg is predefined";
		$f[]="#  so we must be ok";
		$f[]=". /lib/lsb/init-functions";
		$f[]="";
		$f[]="if [[ \$ENABLED -eq 0 ]]; then";
		$f[]="	log_info_msg \"Using Fail2Ban is disabled - STOPPING -\"";
		$f[]="	exit 0";
		$f[]="fi";		
		
		
		$f[]="#";
		$f[]="# Shortcut function for abnormal init script interruption";
		$f[]="#";
		$f[]="report_bug()";
		$f[]="{";
		$f[]="	echo \$*";
		$f[]="	echo \"Please submit a bug report to Debian BTS (reportbug fail2ban)\"";
		$f[]="	exit 1";
		$f[]="}";
		$f[]="";
		$f[]="#";
		$f[]="# Helper function to check if socket is present, which is often left after";
		$f[]="# abnormal exit of fail2ban and needs to be removed";
		$f[]="#";
		$f[]="check_socket()";
		$f[]="{";
		$f[]="	# Return";
		$f[]="	#	0 if socket is present and readable";
		$f[]="	#	1 if socket file is not present";
		$f[]="	#	2 if socket file is present but not readable";
		$f[]="	#	3 if socket file is present but is not a socket";
		$f[]="	[ -e \"\$SOCKFILE\" ] || return 1";
		$f[]="	[ -r \"\$SOCKFILE\" ] || return 2";
		$f[]="	[ -S \"\$SOCKFILE\" ] || return 3";
		$f[]="	return 0";
		$f[]="}";
		$f[]="";
		$f[]="#";
		$f[]="# Function that starts the daemon/service";
		$f[]="#";
		$f[]="do_start()";
		$f[]="{";
		$f[]="	# Return";
		$f[]="	#	0 if daemon has been started";
		$f[]="	#	1 if daemon was already running";
		$f[]="	#	2 if daemon could not be started";
		$f[]="	do_status && return 1";
		$f[]="";
		$f[]="	if [ -e \"\$SOCKFILE\" ]; then";
		$f[]="		log_failure_msg \"Socket file \$SOCKFILE is present\"";
		$f[]="		[ \"\$1\" = \"force-start\" ] && log_success_msg \"Starting anyway as requested\" || return 2";
		$f[]="		DAEMON_ARGS=\"\$DAEMON_ARGS -x\"";
		$f[]="	fi";
		$f[]="";
		$f[]="	# Assure that /var/run/fail2ban exists";
		$f[]="	[ -d /var/run/fail2ban ] || mkdir -p /var/run/fail2ban";
		$f[]="";
		$f[]="	start-stop-daemon --start --quiet --chuid root --exec \$DAEMON -- \$DAEMON_ARGS start > /dev/null || return 2";
		$f[]="";
		$f[]="	return 0";
		$f[]="}";
		$f[]="";
		$f[]="";
		$f[]="#";
		$f[]="# Function that checks the status of fail2ban and returns";
		$f[]="# corresponding code";
		$f[]="#";
		$f[]="do_status()";
		$f[]="{";
		$f[]="	\$DAEMON ping > /dev/null";
		$f[]="	return \$?";
		$f[]="}";
		$f[]="";
		$f[]="#";
		$f[]="# Function that stops the daemon/service";
		$f[]="#";
		$f[]="do_stop()";
		$f[]="{";
		$f[]="	# Return";
		$f[]="	#	0 if daemon has been stopped";
		$f[]="	#	1 if daemon was already stopped";
		$f[]="	#	2 if daemon could not be stopped";
		$f[]="	#	other if a failure occurred";
		$f[]="	\$DAEMON status > /dev/null || return 1";
		$f[]="	\$DAEMON stop > /dev/null || return 2";
		$f[]="";
		$f[]="	# now we need actually to wait a bit since it might take time";
		$f[]="	# for server to react on client's stop request. Especially";
		$f[]="	# important for restart command on slow boxes";
		$f[]="	count=1";
		$f[]="	while do_status && [ \$count -lt 60 ]; do";
		$f[]="		sleep 1";
		$f[]="		count=\$((\$count+1))";
		$f[]="	done";
		$f[]="	[ \$count -lt 60 ] || return 3 # failed to stop";
		$f[]="";
		$f[]="	return 0";
		$f[]="}";
		$f[]="";
		$f[]="#";
		$f[]="# Function to reload configuration";
		$f[]="#";
		$f[]="do_reload() {";
		$f[]="	\$DAEMON reload > /dev/null && return 0 || return 1";
		$f[]="	return 0";
		$f[]="}";
		$f[]="";
		$f[]="# yoh:";
		$f[]="# shortcut function to don't duplicate case statements and to don't use";
		$f[]="# bashisms (arrays). Fixes #368218";
		$f[]="#";
		$f[]="log_end_msg_wrapper()";
		$f[]="{";
		$f[]="	[ \$1 -lt \$2 ] && value=0 || value=1";
		$f[]="	log_end_msg \$value";
		$f[]="}";
		$f[]="";
		$f[]="command=\"\$1\"";
		$f[]="case \"\$command\" in";
		$f[]="	start|force-start)";
		$f[]="		[ \"\$VERBOSE\" != no ] && log_daemon_msg \"Starting \$DESC\" \"\$NAME\"";
		$f[]="		do_start \"\$command\"";
		$f[]="		[ \"\$VERBOSE\" != no ] && log_end_msg_wrapper \$? 2";
		$f[]="		;;";
		$f[]="";
		$f[]="	stop)";
		$f[]="		[ \"\$VERBOSE\" != no ] && log_daemon_msg \"Stopping \$DESC\" \"\$NAME\"";
		$f[]="		do_stop";
		$f[]="		[ \"\$VERBOSE\" != no ] && log_end_msg_wrapper \$? 2";
		$f[]="		;;";
		$f[]="";
		$f[]="	restart|force-reload)";
		$f[]="		log_daemon_msg \"Restarting \$DESC\" \"\$NAME\"";
		$f[]="		do_stop";
		$f[]="		case \"\$?\" in";
		$f[]="			0|1)";
		$f[]="				do_start";
		$f[]="				log_end_msg_wrapper \$? 1";
		$f[]="				;;";
		$f[]="			*)";
		$f[]="				# Failed to stop";
		$f[]="				log_end_msg 1";
		$f[]="				;;";
		$f[]=" 		esac";
		$f[]="		;;";
		$f[]="";
		$f[]="	reload|force-reload)";
		$f[]="        log_daemon_msg \"Reloading \$DESC\" \"\$NAME\"";
		$f[]="        do_reload";
		$f[]="        log_end_msg \$?";
		$f[]="        ;;";
		$f[]="";
		$f[]="	status)";
		$f[]="		log_daemon_msg \"Status of \$DESC\"";
		$f[]="		do_status";
		$f[]="		case \$? in";
		$f[]="			0)  log_success_msg \" \$NAME is running\" ;;";
		$f[]="			255)";
		$f[]="				check_socket";
		$f[]="				case \$? in";
		$f[]="					1)  log_warning_msg \" \$NAME is not running\" ;;";
		$f[]="					0)  log_failure_msg \" \$NAME is not running but \$SOCKFILE exists\" ;;";
		$f[]="					2)  log_failure_msg \" \$SOCKFILE not readable, status of \$NAME is unknown\";;";
		$f[]="					3)  log_failure_msg \" \$SOCKFILE exists but not a socket, status of \$NAME is unknown\";;";
		$f[]="					*)  report_bug \"Unknown return code from \$NAME:check_socket.\";;";
		$f[]="				esac";
		$f[]="				;;";
		$f[]="			*)  report_bug \"Unknown \$NAME status code\"";
		$f[]="		esac";
		$f[]="		;;";
		$f[]="	*)";
		$f[]="		echo \"Usage: \$SCRIPTNAME {start|force-start|stop|restart|force-reload|status}\" >&2";
		$f[]="		exit 3";
		$f[]="		;;";
		$f[]="esac";
		$f[]="";
		$f[]=":";

		echo "fail2ban: [INFO] Writing /etc/init.d/fail2ban with new config\n";
		@unlink($INITD_PATH);
		@file_put_contents($INITD_PATH, @implode("\n", $f));
		@chmod($INITD_PATH,0755);
		
		if(is_file('/usr/sbin/update-rc.d')){
			shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
		}
		
		
		
	}
	
	
	
}

