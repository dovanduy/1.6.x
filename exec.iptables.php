<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.iptables.exec.rules.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');


if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
$unix=new unix();
$sock=new sockets();
$pidfile="/etc/artica-postfix/".basename(__FILE__).".pid";
if($unix->process_exists(@file_get_contents($pidfile),basename(__FILE__))){echo "Starting......: iptables configurator already executed PID ". @file_get_contents($pidfile)."\n";die();}
$pid=getmypid();
echo "Starting......: iptables configurator running $pid\n";
file_put_contents($pidfile,$pid);
$ip=new iptables_exec();
$ip->buildrules();
