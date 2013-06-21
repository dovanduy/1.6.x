<?php
$GLOBALS["FORCE"]=false;
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["BYPASS"]=true;$GLOBALS["REBUILD"]=false;$GLOBALS["OLD"]=false;$GLOBALS["FORCE"]=false;$GLOBALS["ROOT"]=true;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");

die();

if($argv[1]=="--stats-appliance"){die();SendToStatisticsAppliance();die();}

squidrrd();
function squidrrd(){
	die();
	if(!$GLOBALS["FORCE"]){
		if(system_is_overloaded(basename(__FILE__))){
			writelogs("Overloaded system {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting task",__FILE__,__FUNCTION__,__FILE__,__LINE__);
			die();
		}
	}
	$perlfile="/usr/share/artica-postfix/bin/install/rrd/squid-rrd.pl";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$timefile1="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time1";
	if(!is_file($perlfile)){writelogs(basename($perlfile)." no such file !!!",__FUNCTION__,__FILE__,__LINE__);return;}
	
	$unix=new unix();
	$timeExc=$unix->file_time_min($timefile1);
	if(!$GLOBALS["FORCE"]){if($timeExc<5){return;}}
	@unlink($timefile1);
	
	$oldpid=$unix->get_pid_from_file($pidfile);
	
	if(!$GLOBALS["FORCE"]){
		if($unix->process_exists($oldpid,basename(__FILE__))){
				writelogs("Already executed PID $oldpid",__FUNCTION__,__FILE__,__LINE__);
				return;
		}
		@file_put_contents($pidfile, getmypid());
	}else{
		$timeExc=1000;
	}
	
	$port=$unix->squid_get_alternate_port();
	if(preg_match("#(.+?):([0-9]+)#", $port,$re)){$port=$re[2];}
	if($GLOBALS["VERBOSE"]){echo "Choosen port was $port\n";}
	$NICE=EXEC_NICE();
	$cmd="$NICE$perlfile 127.0.0.1:$port";
	writelogs($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	@file_put_contents($timefile1, time());
	$timeExc=$unix->file_time_min($timefile);
	if($GLOBALS["FORCE"]){$timeExc=1000;}
	if($timeExc>10){
		@unlink($timefile);
		$cmd="$NICE/usr/share/artica-postfix/bin/install/rrd/squid-rrdex.pl";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		shell_exec($cmd);
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




