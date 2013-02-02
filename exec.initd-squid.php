<?php
$GLOBALS["VERBOSE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");



squid_debian();
squid_redhat();

function squid_debian(){
$unix=new unix();
$php=$unix->LOCATE_PHP5_BIN();
$chmod=$unix->find_program("chmod");
$ln=$unix->find_program("ln");
$php=$unix->LOCATE_PHP5_BIN();
shell_exec("$php /usr/share/artica-postfix/exec.squid.watchdog.php --init");

}

function squid_redhat(){
$unix=new unix();
$redhatbin=$unix->find_program("chkconfig");	
if(!is_file($redhatbin)){return;}
$squidbin=$unix->find_program("squid");
if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
if(!is_file($squidbin)){writelogs("squid, no such binary file",__FUNCTION__,__FILE__,__LINE__);}

$php5=$unix->LOCATE_PHP5_BIN();
$SQUID_ARGS="-YC";
$SQUID_CONF="/etc/squid3/squid.conf";
$f[]="[Unit]";
$f[]="Description=Squid caching proxy";
$f[]="After=syslog.target network.target named.service";
$f[]="";
$f[]="[Service]";
$f[]="Type=forking";
$f[]="LimitNOFILE=16384";
$f[]="EnvironmentFile=/etc/sysconfig/squid";
$f[]="ExecStartPre=/usr/libexec/squid/cache_swap.sh";
$f[]="ExecStart=$squidbin $SQUID_ARGS -f $SQUID_CONF";
$f[]="ExecReload=$squidbin $SQUID_ARGS -k reconfigure -f -f $SQUID_CONF";
$f[]="ExecStop=$squidbin -k shutdown -f $SQUID_CONF";
$f[]="";
$f[]="[Install]";
$f[]="WantedBy=multi-user.target";
if(is_dir("/lib/systemd/system")){
	@file_put_contents("/lib/systemd/system/squid.service", @implode("\n", $f));
}
$f=array();
$f[]="#!/bin/bash";
$f[]="if [ -f /etc/sysconfig/squid ]; then";
$f[]="	. /etc/sysconfig/squid";
$f[]="fi";
$f[]="";
$f[]="SQUID_CONF=\${SQUID_CONF:-\"/etc/squid3/squid.conf\"}";
$f[]="";
$f[]="CACHE_SWAP=`sed -e 's/#.*//g' \$SQUID_CONF | grep cache_dir | awk '{ print \$3 }'`";
$f[]="";
$f[]="for adir in \$CACHE_SWAP; do";
$f[]="	if [ ! -d \$adir/00 ]; then";
$f[]="		echo -n \"init_cache_dir \$adir... \"";
$f[]="		squid -z -F -f \$SQUID_CONF >> /var/log/squid/squid.out 2>&1";
$f[]="	fi";
$f[]="done";
@mkdir("/usr/libexec/squid",0755,true);
@file_put_contents("/usr/libexec/squid/cache_swap.sh",@implode("\n", $f));
@chmod("/usr/libexec/squid/cache_swap.sh",0755);

$f=array();
$unix=new unix();
$php=$unix->LOCATE_PHP5_BIN();
$chmod=$unix->find_program("chmod");
$ln=$unix->find_program("ln");
$php=$unix->LOCATE_PHP5_BIN();
shell_exec("$php /usr/share/artica-postfix/exec.squid.watchdog.php --init");
}