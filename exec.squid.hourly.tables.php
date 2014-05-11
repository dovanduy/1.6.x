<?php
$GLOBALS["BYPASS"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");


$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".time";


if($GLOBALS["VERBOSE"]){echo "TimeFile:$pidTime\n";}
$unix=new unix();
if(!$GLOBALS["FORCE"]){
	if($unix->file_time_min($pidTime)<15){die();}
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid)){die();}
}

@unlink($pidTime);
@file_put_contents($pidTime, time());
@file_put_contents($pidfile, getmypid());

$php5=$unix->LOCATE_PHP5_BIN();
$xtime=time();
$q=new mysql_squid_builder();
$q->TablePrimaireHour(date("YmdH",$xtime));
$q->check_youtube_hour(date("YmdH",$xtime));
$q->check_SearchWords_hour(date("YmdH",$xtime));
$q->check_quota_hour(date("YmdH",$xtime));

$f[]="#!/bin/sh";
$f[]="export LC_ALL=C";
$f[]="$php5 ".__FILE__." >/dev/null 2>&1";
$f[]="";
@file_put_contents("/etc/cron.hourly/SquidHourlyTables.sh", @implode("\n",$f));
@chmod("/etc/cron.hourly/SquidHourlyTables.sh",0755);
unset($f);
