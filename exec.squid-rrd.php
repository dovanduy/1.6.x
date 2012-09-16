<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["BYPASS"]=true;$GLOBALS["REBUILD"]=false;$GLOBALS["OLD"]=false;$GLOBALS["FORCE"]=false;$GLOBALS["ROOT"]=true;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");


if($argv[1]=="--stats-appliance"){SendToStatisticsAppliance();die();}

squidrrd();
function squidrrd(){
	if(system_is_overloaded(basename(__FILE__))){
		writelogs("Overloaded system {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting task",__FILE__,__FUNCTION__,__FILE__,__LINE__);
		die();
	}
	$perlfile="/usr/share/artica-postfix/bin/install/rrd/squid-rrd.pl";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$timefile1="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time1";
	if(!is_file($perlfile)){writelogs(basename($perlfile)." no such file !!!",__FUNCTION__,__FILE__,__LINE__);return;}
	
	$unix=new unix();
	$timeExc=$unix->file_time_min($timefile1);
	if($timeExc<5){return;}
	@unlink($timefile1);
	
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){writelogs("Already executed PID $oldpid",__FUNCTION__,__FILE__,__LINE__);return;}
	@file_put_contents($pidfile, getmypid());
	
	$f=explode("\n", @file_get_contents("/etc/squid3/squid.conf"));
	while (list ($num, $line) = each ($f)){if(preg_match("#http_port\s+([0-9]+)#", $line,$re)){$http_port[]=$re[1];}}
	if($GLOBALS["VERBOSE"]){echo "Found ".count($http_port)." http_port\n";}
	if(count($http_port)==0){return;}
	$port=$http_port[count($http_port)-1];
	if($GLOBALS["VERBOSE"]){echo "Choosen port was $port\n";}
	$NICE=EXEC_NICE();
	$cmd="$NICE$perlfile 127.0.0.1:$port";
	writelogs($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	@file_put_contents($timefile1, time());
	$timeExc=$unix->file_time_min($timefile);
	if($timeExc>10){
		@unlink($timefile);
		shell_exec("$NICE/usr/share/artica-postfix/bin/install/rrd/squid-rrdex.pl");
		SendToStatisticsAppliance();
		@file_put_contents($timefile, time());
	}
	
	
}

function SendToStatisticsAppliance(){
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if($EnableRemoteStatisticsAppliance==0){return;}
	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
	$unix=new unix();
	$tar=$unix->find_program("tar");
	if($GLOBALS["VERBOSE"])
	if(is_file("/opt/artica/share/www/squid/rrd/graphs.tar.gz")){@unlink("/opt/artica/share/www/squid/rrd/graphs.tar.gz");}
	chdir("/opt/artica/share/www/squid/rrd");
	shell_exec("$tar -czf graphs.tar.gz *");
	$uri=$unix->SquidStatsApplianceUri();
	$curl=new ccurl("$uri/squid.blocks.listener.php");
	$curl->noproxyload=true;
	$hostname=$unix->FULL_HOSTNAME();
	if(!$curl->postFile("SQUID_GRAPHS","/opt/artica/share/www/squid/rrd/graphs.tar.gz",array("HOSTNAME"=>$hostname))){
		echo "Failed -> `$uri/squid.blocks.listener.php`\n";
		return;
	}
	
	
	echo "Success....\n";
	
	
	
	
}




