<?php
$GLOBALS["VERBOSE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");

initd_debian();

function initd_debian(){
$unix=new unix();
$sock=new sockets();
$servicebin=$unix->find_program("update-rc.d");
if(!is_file($servicebin)){return;}
	
$f[]="#!/bin/bash";
$f[]="#";
$f[]="### BEGIN INIT INFO";
$f[]="# Provides:          mysql";
$f[]="# Required-Start:    \$remote_fs \$syslog";
$f[]="# Required-Stop:     \$remote_fs \$syslog";
$f[]="# Should-Start:      \$network \$time";
$f[]="# Should-Stop:       \$network \$time";
$f[]="# Default-Start:     2 3 4 5";
$f[]="# Default-Stop:      0 1 6";
$f[]="# Short-Description: Start and stop the mysql database server daemon";
$f[]="# Description:       Controls the main MySQL database server daemon \"mysqld\"";
$f[]="#                    and its wrapper script \"mysqld_safe\".";
$f[]="### END INIT INFO";
$f[]="#";	
$f[]="case \"\$1\" in";
$f[]=" start)";
$f[]="    /usr/share/artica-postfix/bin/artica-install -watchdog mysql \$3";
$f[]="    ;;";
$f[]="";
$f[]="  stop)";
$f[]="    /usr/share/artica-postfix/bin/artica-install -shutdown mysql \$3";
$f[]="    ;;";
$f[]="";
$f[]=" restart)";
$f[]="     /usr/share/artica-postfix/bin/artica-install -shutdown mysql \$3";
$f[]="     sleep 3";
$f[]="     /usr/share/artica-postfix/bin/artica-install -watchdog mysql \$3";
$f[]="    ;;";
$f[]="";
$f[]="  *)";
$f[]="    echo \"Usage: \$0 {start|stop|restart} {ldap|} (+ 'debug' for more infos)\"";
$f[]="    exit 1";
$f[]="    ;;";
$f[]="esac";
$f[]="exit 0";

if(is_file("/etc/init.d/mysql")){$updatercd=false;if(!is_file("/etc/init.d/mysql.bak")){@copy("/etc/init.d/mysql", "/etc/init.d/mysql.bak");}}else{$updatercd=true;}
@file_put_contents("/etc/init.d/mysql", @implode("\n", $f));
if($updatercd){
	shell_exec("$servicebin -f mysql defaults >/dev/null 2>&1");
}
echo "Starting......: /etc/init.d/mysql done...\n";

}