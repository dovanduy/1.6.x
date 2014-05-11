<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.mysql.dump.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.mount.inc');
if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }

$GLOBALS["MAXDAYS"]=0;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#maxdays=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["MAXDAYS"]=$re[1];}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}

$unix=new unix();
if($unix->process_number_me($argv)>0){die("Already executed\n\n");}

if($argv[1]=="--squid"){purge_bysquid(false);die();}



function purge_bysquid(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=@file_get_contents($pidfile);
	if($oldpid<100){$oldpid=null;}
	
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$timepid=$unix->PROCCESS_TIME_MIN($oldpid);
		ufdbguard_admin_events("Already executed pid $oldpid since {$timepid}",__FUNCTION__,__FILE__,__LINE__,"purge");
		if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	$sock=new sockets();
	$users=new usersMenus();
	$rm=$unix->find_program("rm");
	$df=$unix->find_program("df");
	
	$DF_RESULTS[]="Scanning Artica directories in /var/log\ncurrent status:";
	exec("$df -i /var/log 2>&1",$DF_RESULTS);
	$DF_RESULTS[]="";
	exec("$df -h /var/log 2>&1",$DF_RESULTS);
	$dirs=$unix->DirFiles("/var/log/artica-postfix");
	
	
	
	while (list ($directory, $b) = each ($dirs)){
		$DF_RESULTS[]="";
		$DF_RESULTS[]="";
		$DF_RESULTS[]=date("Y-m-d H:i:s")." Removing content of $directory";
		$DF_RESULTS[]=date("Y-m-d H:i:s")." $directory Before:";
		$DF_RESULTS[]="";
		exec("$df -i $directory 2>&1",$DF_RESULTS);
		$DF_RESULTS[]="";
		exec("$df -h $directory 2>&1",$DF_RESULTS);
		shell_exec("$rm -rf $directory/* 2>&1");
		$DF_RESULTS[]=date("Y-m-d H:i:s")." $directory After removing content:";
		exec("$df -i $directory 2>&1",$DF_RESULTS);
		$DF_RESULTS[]="";
		exec("$df -h $directory 2>&1",$DF_RESULTS);
		$DF_RESULTS[]="";
	}

	squid_admin_mysql(0, "Log partition cleaning report", @implode("\n", $DF_RESULTS).__FILE__,__LINE__);
	
	
}
