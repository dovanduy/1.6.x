#!/usr/bin/php -q
<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="InfluxDB Daemon";
$GLOBALS["PROGRESS"]=false;
$GLOBALS["MIGRATION"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;
$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--migration#",implode(" ",$argv),$re)){$GLOBALS["MIGRATION"]=true;}

$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.influx.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');


if($argv[1]=="--start"){start();exit;}
if($argv[1]=="--stop"){die(0);exit;}



function GET_PID(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/rsyslogd.pid");
	if($unix->process_exists($pid)){return $pid;}
	$rsyslogd=$unix->find_program("rsyslogd");
	return $unix->PIDOF($rsyslogd);
	
}


function start(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	

	$PID=GET_PID();
	if($unix->process_exists($PID)){
		squid_admin_mysql(1, "Watchdog says that rsyslog is off, but exists [action=stamp-pid]", null,__FILE__,__LINE__);
		$SrcPid=intval(@file_get_contents("/var/run/rsyslogd.pid"));
		if($SrcPid<>$PID){
			squid_admin_mysql(2, "Watchdog says that rsyslog is off (PID:$SrcPid), but exists (PID:$PID) [action=stamp-pid]", null,__FILE__,__LINE__);
			@file_put_contents("/var/run/rsyslogd.pid", $PID);
		}else{
			squid_admin_mysql(2, "Watchdog says that rsyslog is off (PID:$SrcPid), but exists [action=nothing]", null,__FILE__,__LINE__);
		}
		@unlink($pidfile);
		die(1);
	}
	
	
	squid_admin_mysql(0, "Syslog daemon is down [action=start]", null,__FILE__,__LINE__);
	system("/etc/init.d/rsyslog start");
	
	$squid=$unix->LOCATE_SQUID_BIN();
	if(is_file($squid)){
		sleep(3);
		squid_admin_mysql(1, "Reloading proxy service after starting syslog daemon", null,__FILE__,__LINE__);
		system("$squid -f /etc/squid3/squid.conf -k reconfigure");
	}
}


