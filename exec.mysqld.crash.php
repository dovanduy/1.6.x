<?php
$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["FLUSH"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--flush#",implode(" ",$argv))){$GLOBALS["FLUSH"]=true;}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');


if($argv[1]=="--crashed"){check_crashed();exit;}
if($argv[1]=="--crashed-squid"){check_crashed_squid();exit;}

function check_crashed_squid(){
	$FILE_LOG="/opt/squidsql/error.log";
	$DB_PATH="/opt/squidsql/data";
	
	// /etc/artica-postfix/pids/exec.mysqld.crash.php.check_crashed_squid.time
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";

	if($GLOBALS["VERBOSE"]){echo "pidTime: $pidTime\n";}


	$unix=new unix();


	$pid=$unix->get_pid_from_file($pidfile);
	$sock=new sockets();

	if($unix->process_exists($pid,basename(__FILE__))){
		$pidTime=$unix->PROCCESS_TIME_MIN($pid);
		events("Already process PID: $pid running since $pidTime minutes", __FUNCTION__, __FILE__, __LINE__, "mysql");
		if($pidTime>120){
			$kill=$unix->find_program("kill");
			unix_system_kill_force($pid);
			die();
		}
		return;
	}


	@file_put_contents($pidfile, getmypid());
	$Time=$unix->file_time_min($pidTime);
	if(!$GLOBALS["VERBOSE"]){
		if($Time<240){return;}
	}


	
	if(!is_file($FILE_LOG)){return;}

	$myisamchk=$unix->find_program("myisamchk");
	$echo=$unix->find_program("echo");
	if(!is_file($myisamchk)){return;}
	$f=explode("\n",@file_get_contents($FILE_LOG));


	$GLOBALS["CRASHED"]=array();
	$PATHS=array();
	while (list ($num, $line) = each ($f)){
		if(preg_match("#Aborted connection [0-9]+#", $line)){continue;}
		if(preg_match("#Got an error from thread_id#", $line)){continue;}
		if(preg_match("#MySQL thread id [0-9]+#", $line)){continue;}
		if(preg_match("#ERROR.*?Table.*?\/(.+?)\/(.*?)'\s+is marked as crashed#", $line,$re)){
			$GLOBALS["CRASHED"][$re[1]][]=$re[2];
			continue;
				
		}

		if(preg_match("#Incorrect key file for table './(.+?)\/(.+?)\.MYI'; try to repair it#", $line,$re)){
			$GLOBALS["CRASHED"][$re[1]][]=$re[2];
			continue;
		}
		
		if(preg_match("#Got error 127 when reading table './(.+?)\/(.+?)'#", $line,$re)){
			$GLOBALS["CRASHED"][$re[1]][]=$re[2];
			continue;			
		}

		if($GLOBALS["VERBOSE"]){echo $line." no match\n";}




	}


	if(count($GLOBALS["CRASHED"])==0){return;}

	while (list ($database, $tables) = each ($GLOBALS["CRASHED"])){
		while (list ($a, $table) = each ($tables)){
			$path="$DB_PATH/$database/$table.MYI";
			if(is_file("$DB_PATH/$database/$table.TMD")){
				@copy("$DB_PATH/$database/$table.TMD", "/var/lib/mysql/$database/$table.".time().".TMD");
				@unlink("$DB_PATH/$database/$table.TMD");
			}
			$PATHS[$path]=true;
		}

	}

	while (list ($filepath, $none) = each ($PATHS)){

		$t=time();
		$cmd="$myisamchk -f -r $filepath 2>&1";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		$results=array();
		exec("$cmd",$results);
		$Took=$unix->distanceOfTimeInWords($t,time());
		system_admin_events(basename($filepath)." repair report, took $Took",@implode("\n", $results),__FILE__,__LINE__,"mysql");
	}

	shell_exec("$echo \"\" >$FILE_LOG");


}

function check_crashed(){

	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	if($GLOBALS["VERBOSE"]){echo "pidTime: $pidTime\n";}
	
	
	$unix=new unix();
	
	
	$pid=$unix->get_pid_from_file($pidfile);
	$sock=new sockets();
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$pidTime=$unix->PROCCESS_TIME_MIN($pid);
		events("Already process PID: $pid running since $pidTime minutes", __FUNCTION__, __FILE__, __LINE__, "mysql");
		if($pidTime>120){
			$kill=$unix->find_program("kill");
			unix_system_kill_force($pid);
			die();
		}
		return;
	}
	
	
	@file_put_contents($pidfile, getmypid());
	$Time=$unix->file_time_min($pidTime);
	if(!$GLOBALS["VERBOSE"]){
		if($Time<240){return;}
	}
	
	
	
	$myisamchk=$unix->find_program("myisamchk");
	$echo=$unix->find_program("echo");
	if(!is_file($myisamchk)){return;}
	$f=explode("\n",@file_get_contents("/var/lib/mysql/mysqld.err"));
	
	
	$GLOBALS["CRASHED"]=array();
	$PATHS=array();
	while (list ($num, $line) = each ($f)){
		if(preg_match("#Aborted connection [0-9]+#", $line)){continue;}
		if(preg_match("#Got an error from thread_id#", $line)){continue;}
		if(preg_match("#MySQL thread id [0-9]+#", $line)){continue;}
		
		if(preg_match("#ERROR.*?Table.*?\/(.+?)\/(.*?)'\s+is marked as crashed#", $line,$re)){
			$GLOBALS["CRASHED"][$re[1]][]=$re[2];
			continue;
			
		}
		
		if(preg_match("#Got error 127 when reading table './(.+?)\/(.+?)'#", $line,$re)){
			$GLOBALS["CRASHED"][$re[1]][]=$re[2];
			continue;
		}
			
		if(preg_match("#Incorrect key file for table './(.+?)\/(.+?)\.MYI'; try to repair it#", $line,$re)){
			$GLOBALS["CRASHED"][$re[1]][]=$re[2];
			continue;
		}
		
		if($GLOBALS["VERBOSE"]){echo $line." no match\n";}
	
		
		
		
	}
	
	
	if(count($GLOBALS["CRASHED"])==0){return;}
	
	while (list ($database, $tables) = each ($GLOBALS["CRASHED"])){
		while (list ($a, $table) = each ($tables)){
			$path="/var/lib/mysql/$database/$table.MYI";
			if(is_file("/var/lib/mysql/$database/$table.TMD")){
				@copy("/var/lib/mysql/$database/$table.TMD", "/var/lib/mysql/$database/$table.".time().".TMD");
				@unlink("/var/lib/mysql/$database/$table.TMD");
			}
			$PATHS[$path]=true;
		}
		
	}
	
	while (list ($filepath, $none) = each ($PATHS)){
		
		$t=time();
		$cmd="$myisamchk -f -r $filepath 2>&1";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		$results=array();
		exec("$cmd",$results);
		$Took=$unix->distanceOfTimeInWords($t,time());
		system_admin_events(basename($filepath)." repair report, took $Took",@implode("\n", $results),__FILE__,__LINE__,"mysql");
	}
	
	shell_exec("$echo \"\" >/var/lib/mysql/mysqld.err");


}