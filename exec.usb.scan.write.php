<?php
$GLOBALS["VERBOSE"]=false;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.nginx.inc');
include_once(dirname(__FILE__).'/ressources/class.freeweb.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.reverse.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.resolv.conf.inc');
include_once(dirname(__FILE__).'/ressources/class.usb-scan.inc');
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

$unix=new unix();


$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
$pid=$unix->get_pid_from_file($pidfile);
if($unix->process_exists($pid,basename(__FILE__))){
	$time=$unix->PROCCESS_TIME_MIN($pid);
	if($GLOBALS["OUTPUT"]){echo "service Already Artica task running PID $pid since {$time}mn\n";}
	return;
}
@file_put_contents($pidfile, getmypid());
if($GLOBALS["VERBOSE"]){echo "Verbosed !!!\n";}

$usb=new usbscan();
$datas=$usb->disks_list();
@file_put_contents("/usr/share/artica-postfix/ressources/usb.scan.inc", $datas);
@chmod("/usr/share/artica-postfix/ressources/usb.scan.inc",0755);
include_once("/usr/share/artica-postfix/ressources/usb.scan.inc");
