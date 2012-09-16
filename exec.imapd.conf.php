<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');

if(posix_getuid()<>0){
	die("Cannot be used in web server mode\n\n");
}

include_once(dirname(__FILE__).'/ressources/class.templates.inc');
$line="";
$conf="";

if(!is_file("/etc/imapd.conf")){
	write_syslog("Unable to stat /etc/imapd.conf, aborting",__FILE__);
	exit(0);
}



$tbl=explode("\n",file_get_contents("/etc/imapd.conf"));
if(!is_array($tbl)){die();}
while (list ($num, $ligne) = each ($tbl) ){
	if(preg_match("#^([a-z0-9\_\-]+):(.+)#",$ligne,$re)){
		$ri[trim($re[1])]=trim($re[2]);
		
	}
	
}

if(!is_array($ri)){die();}
$sock= new sockets();
$CyrusPartitionDefault=$sock->GET_INFO("CyrusPartitionDefault");

if($ri["partition-default"]==null){
	$sock=new sockets();
	if($CyrusPartitionDefault<>null){$ri["partition-default"]=$CyrusPartitionDefault;}
	else{$ri["partition-default"]="/var/spool/cyrus/mail";}
}

while (list ($num, $ligne) = each ($ri) ){
	$conf=$conf . "$num:$ligne\n";
	}
echo $conf."\n";

write_syslog("Cleaning /etc/imapd.conf done with ". strlen($conf)+' bytes',__FILE__);
file_put_contents("/etc/imapd.conf",$conf);
die();


function initd(){
$unix=new unix();

$f[]="#! /bin/sh";
$f[]="#";
$f[]="### BEGIN INIT INFO";
$f[]="# Provides: cyrus-common-2.2";
$f[]="# Required-Start: \$remote_fs \$syslog \$network";
$f[]="# Required-Stop: \$remote_fs \$syslog \$network";
$f[]="# Default-Start: 2 3 4 5";
$f[]="# Default-Stop: 0 1 6";
$f[]="# Short-Description: common init system for cyrus 2.2 IMAP/POP3 daemons.";
$f[]="# Description: common init system the for cyrus 2.2 IMAP/POP3 daemons.";
$f[]="#              starts the central cyrus 2.2 master process, which can ";
$f[]="#              then start various services depending on configuration.";
$f[]="#              Typically starts IMAP and POP3 daemons, but might also";
$f[]="#              start an NNTP daemon and various helper daemons for";
$f[]="#              distributed mail/news storage systems (high-performance";
$f[]="#              and/or high-reliability setups).";
$f[]="### END INIT INFO";
$f[]="#";
$f[]="#		Copyright 2001-2005 by Henrique de Moraes Holschuh <hmh@debian.org>";
$f[]="#		Various modifications done by Sven Mueller <debian@incase.de>";
$f[]="#		Distributed under the GPL version 2";
$f[]="#";
$f[]="# \$Id: cyrus-common-2.2.cyrus2.2.init - Artica - 759 2008-04-02 15:06:19Z sven \$";
$f[]="";
$f[]="# Make sure we get sane results on borked locales";
$f[]="LC_ALL=C";
$f[]="export LC_ALL";
$f[]="";
$f[]="# Overridable defaults";
$f[]="unset CYRUS_VERBOSE";
$f[]="unset LISTENQUEUE";
$f[]="unset CONF";
$f[]="unset MASTERCONF";
$f[]="[ -r /etc/default/cyrus2.2 ] && . /etc/default/cyrus2.2";
$f[]="";
$f[]="[ \"x\${CYRUS_VERBOSE}\" != \"x\" ] && export CYRUS_VERBOSE";
$f[]="# Make sure the master process is daemonized";
$f[]="OPTIONS=\"\${OPTIONS} -d\"";
$f[]="[ \"x\${CONF}\" != \"x\" ] && OPTIONS=\"-C \${CONF} \${OPTIONS}\"";
$f[]="[ \"x\${MASTERCONF}\" != \"x\" ] && OPTIONS=\"-M \${MASTERCONF} \${OPTIONS}\"";
$f[]="[ \"x\${LISTENQUEUE}\" != \"x\" ] && OPTIONS=\"-l \${LISTENQUEUE} \${OPTIONS}\"";
$f[]="";
$f[]="PATH=/sbin:/usr/sbin:/bin:/usr/bin";
$f[]="DAEMON=/usr/sbin/cyrmaster";
$f[]="NAME=cyrmaster";
$f[]="[ \"x\${PIDFILE}\" = \"x\" ] && PIDFILE=\"/var/run/\${NAME}.pid\"";
$f[]="DESC=\"Cyrus IMAPd\"";
$f[]="";
$f[]="# Check if Cyrus 2.2 is installed";
$f[]="test -x \${DAEMON} || exit 0";
$f[]="grep -qE '^PACKAGE_VERSION[[:blank:]]+2[.]2' \ ";
$f[]="	/usr/lib/cyrus/cyrus-hardwired-config.txt >/dev/null 2>&1 || exit 0";
$f[]="";
$f[]="set -e";
$f[]="";
$f[]="START=\"--start --quiet --pidfile \${PIDFILE} --exec \${DAEMON} --name \${NAME} -- \${OPTIONS}\"";
$f[]="";
$f[]="verifydb() {";
$f[]="   while read -r DBKEY DBVALUE ; do";
$f[]="	match=`sort -u < \$1 | gawk \"/^\${DBKEY}[[:blank:]]/ { print \\\$2 }\"`";
$f[]="	[ \"x\${match}\" != \"x\${DBVALUE}\" ] && return 0";
$f[]="   done";
$f[]="   return 1";
$f[]="}";
$f[]="";
$f[]="createdir() {";
$f[]="# \$1 = user";
$f[]="# \$2 = group";
$f[]="# \$3 = permissions (octal)";
$f[]="# \$4 = path to directory";
$f[]="	[ -d \"\$4\" ] || mkdir -p \"\$4\"";
$f[]="	chown -c -h \"\$1:\$2\" \"\$4\"";
$f[]="	chmod -c \"\$3\" \"\$4\"";
$f[]="}";
$f[]="";
$f[]="missingstatoverride () {";
$f[]="	echo \"\$0: You are missing a dpkg-statoverride on \$1.  Add it.\" >&2";
$f[]="	exit 1";
$f[]="}";
$f[]="";
$f[]="fixdirs () {";
$f[]="	dir=`dpkg-statoverride --list /var/run/cyrus` \ ";
$f[]="		|| missingstatoverride /var/run/cyrus";
$f[]="	[ -z \"\$dir\" ] \ ";
$f[]="		|| createdir \$dir";
$f[]="	dir=`dpkg-statoverride --list /var/run/cyrus/socket` \ ";
$f[]="		|| missingstatoverride /var/run/cyrus/socket";
$f[]="	[ -z \"\$dir\" ] \ ";
$f[]="		|| createdir \$dir";
$f[]="}";
$f[]="";
$f[]="check_status () {";
$f[]="	if [ \"\$1\" = \"verbose\" ]; then";
$f[]="		PRINTIT=echo";
$f[]="	else";
$f[]="		PRINTIT=true ";
$f[]="	fi";
$f[]="	if [ ! -f \${PIDFILE} ]; then";
$f[]="		# using [c] in the grep avoids catching the grep ";
$f[]="		# process itself";
$f[]="		if ps auxww | grep -qE 'usr/sbin/[c]yrmaster' ; then";
$f[]="			# Damn, PID file doesn't exist, but cyrmaster process";
$f[]="			# exists. Though strictly speaking, we should not";
$f[]="			# do this, reconstruct the PID file here.";
$f[]="			pidof /usr/sbin/cyrmaster > /dev/null 2>&1 \ ";
$f[]="			&& pidof /usr/sbin/cyrmaster > \${PIDFILE}";
$f[]="			\${PRINTIT} \"cyrmaster running with PID `cat \${PIDFILE}`\"";
$f[]="			return 0";
$f[]="		fi";
$f[]="	fi	";
$f[]="	if [ -s \${PIDFILE} ] && kill -0 `cat \${PIDFILE}` > /dev/null 2>&1; then";
$f[]="		\${PRINTIT} \"cyrmaster running with PID `cat \${PIDFILE}`\"";
$f[]="		return 0";
$f[]="	else";
$f[]="		# the PID file might simply not match the cyrmaster process.";
$f[]="		if pidof /usr/sbin/cyrmaster > /dev/null 2>&1 ; then";
$f[]="			# go ahead and fix it";
$f[]="			pidof /usr/sbin/cyrmaster > \${PIDFILE}";
$f[]="			\${PRINTIT} \"cyrmaster running with PID `cat \${PIDFILE}`\"";
$f[]="			return 0";
$f[]="		else";
$f[]="			# no process and/or no PID file, return failure";
$f[]="			\${PRINTIT} \"cyrmaster not running with\"";
$f[]="			return 1";
$f[]="		fi";
$f[]="	fi";
$f[]="	# this point should never be reached, return unknown status if it ";
$f[]="	# is anyway";
$f[]="	return 4";
$f[]="}";
$f[]="";
$f[]="case \"\$1\" in";
$f[]="  start)";
$f[]="  	# Verify if there are old Cyrus 1.5 spools that were not upgraded";
$f[]="	[ -f /var/lib/cyrus/mailboxes -a -d /var/lib/cyrus/deliverdb -a \ ";
$f[]="	  -d /var/spool/cyrus/mail/user -a ! -d /var/spool/cyrus/mail/stage. ] && {";
$f[]="	  	echo \"\$0: It appears that you still have an version 1.5 spool\" 1>&2";
$f[]="		echo \"\$0: that needs to be upgraded. Please refer to the guide\" 1>&2";
$f[]="		echo \"\$0: at /usr/share/doc/cyrus-common-2.2/UPGRADE.Debian\" 1>&2";
$f[]="		echo";
$f[]="		echo \"\$0: Cyrmaster not started.\"";
$f[]="		exit 6";
$f[]="	}";
$f[]="	# Verify consistency of database backends";
$f[]="	[ -f /usr/lib/cyrus/cyrus-db-types.active ] && {";
$f[]="		# is it safe to start cyrmaster? compare \"key value\" pairs";
$f[]="		# from the (old) active database types file with the new one";
$f[]="		( sort -u /usr/lib/cyrus/cyrus-db-types.active \ ";
$f[]="		  | grep DBENGINE \ ";
$f[]="		  | verifydb /usr/lib/cyrus/cyrus-db-types.txt \ ";
$f[]="		) && {";
$f[]="		    echo \"\$0: Database backends mismatch! You must manually\" 1>&2";
$f[]="		    echo \"\$0: verify and update the Cyrus databases to the\" 1>&2";
$f[]="		    echo \"\$0: new backends.\" 1>&2";
$f[]="		    echo \"\$0: Please refer to /usr/share/doc/cyrus-common-2.2/README.Debian\" 1>&2";
$f[]="		    echo \"\$0: for instructions.\" 1>&2";
$f[]="		    echo";
$f[]="		    echo \"\$0: Cyrmaster not started.\"";
$f[]="		    exit 6";
$f[]="		}";
$f[]="	}";
$f[]="	echo -n \"Starting \${DESC}: \"";
$f[]="	fixdirs";
$f[]="	if check_status ; then";
$f[]="		echo \"\${DAEMON} already running.\"";
$f[]="		exit 0";
$f[]="	fi";
$f[]="	if start-stop-daemon \${START} >/dev/null 2>&1 ; then";
$f[]="		echo \"\$NAME.\"";
$f[]="	else";
$f[]="		if ! check_status ; then";
$f[]="			echo \"(failed).\"";
$f[]="			exit 1";
$f[]="		fi";
$f[]="	fi";
$f[]="	;;";
$f[]="  stop)";
$f[]="	echo -n \"Stopping \$DESC: \"";
$f[]="	if start-stop-daemon --stop --quiet --pidfile /var/run/\$NAME.pid \ ";
$f[]="		--name \${NAME} --quiet --startas \$DAEMON >/dev/null 2>&1 ; then";
$f[]="		echo \"\$NAME.\"";
$f[]="		rm -f \${PIDFILE}";
$f[]="		exit 0";
$f[]="	else";
$f[]="		# process running?";
$f[]="		if check_status; then";
$f[]="			# Yes, report failure.";
$f[]="			echo \"(failed).\"";
$f[]="			exit 1";
$f[]="		else";
$f[]="			# No, return as if stopped a running process ";
$f[]="			# successfully.";
$f[]="			echo \".\"";
$f[]="			rm -f \${PIDFILE}";
$f[]="			exit 0";
$f[]="		fi";
$f[]="	fi";
$f[]="	;;";
$f[]="  reload|force-reload)";
$f[]="	echo \"Reloading \$DESC configuration files.\" ";
$f[]="	if start-stop-daemon --stop --signal 1 --quiet \ ";
$f[]="		--name \${NAME} --pidfile /var/run/\$NAME.pid >/dev/null 2>&1 ; then";
$f[]="		exit 0";
$f[]="	else";
$f[]="		exit 1";
$f[]="	fi";
$f[]="  	;;";
$f[]="  restart)";
$f[]="  	\$0 stop && {";
$f[]="	  echo -n \"Waiting for complete shutdown...\"";
$f[]="	  i=5";
$f[]="	  while [ \$i -gt 0 ] ; do";
$f[]="	  	# exit look when server is not running";
$f[]="	  	check_status || break";
$f[]="		sleep 2s";
$f[]="		i=\$((\$i - 1))";
$f[]="		echo -n \".\"";
$f[]="	  done";
$f[]="	  [ \$i -eq 0 ] && {";
$f[]="	  	echo";
$f[]="		echo \"fatal: incomplete shutdown detected, aborting.\"";
$f[]="		exit 1";
$f[]="	  }";
$f[]="	  echo";
$f[]="	}";
$f[]="	exec \$0 start";
$f[]="	;;";
$f[]="  status)";
$f[]="  	check_status verbose";
$f[]="	exit \$?";
$f[]="	;;";
$f[]="  try-restart)";
$f[]="  	check_status";
$f[]="	if [ \"\$?\" -eq 0 ]; then";
$f[]="		exec \$0 restart";
$f[]="	else";
$f[]="  		# LSB says to return 0 in try-restart if the service is";
$f[]="		# not running.";
$f[]="		exit 0";
$f[]="	fi";
$f[]="	;;";
$f[]="  *)";
$f[]="	echo \"Usage: \$0 {start|stop|restart|reload|force-reload}\" 1>&2";
$f[]="	exit 1";
$f[]="	;;";
$f[]="esac";
$f[]="";
$f[]="exit 0";
$f[]="";	
}

?>