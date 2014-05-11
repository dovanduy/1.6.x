<?php
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.samba.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
$GLOBALS["MECMDS"]=@implode(" ", $argv);
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if($argv[1]=='--check'){check();die();}
if($argv[1]=='--exists'){InMemQUestion();die();}
if($argv[1]=='--rebuild'){run();die();}
if($argv[1]=='--pid'){echo getPID()."\n";die();}
if($argv[1]=='--run'){echo run()."\n";die();}



function check(){
	
	$unix=new unix();
	$MEMORY=$unix->MEM_TOTAL_INSTALLEE();
	
	if($MEMORY<624288){
		writelogs(basename(__FILE__).":Too low memory, die();",basename(__FILE__),__FILE__,__LINE__);
		die();
	}	
	
$EnablePhileSight=GET_INFO_DAEMON("EnablePhileSight");
if($EnablePhileSight==null){$EnablePhileSight=0;}

	if($EnablePhileSight==0){
		writelogs("feature disabled, aborting...",__FUNCTION__,__FILE__,__LINE__);
		die();
	}
	
	
	if(system_is_overloaded()){
		writelogs("System overloaded, aborting this feature for the moment",__FUNCTION__,__FILE__,__LINE__);
		die();
	}
	@mkdir("/opt/artica/philesight");

	$unix=new unix();
	$min=$unix->file_time_min("/opt/artica/philesight/database.db");
	$sock=new sockets();
	$rr=$sock->GET_INFO("PhileSizeRefreshEach");
	if($rr==null){$rr=120;}
	if($rr=="disable"){die();}
	writelogs("/opt/artica/philesight/database.db = $min minutes, $rr minutes to run",__FUNCTION__,__FILE__,__LINE__);
	if($min>=$rr){
		run();
	}
}


function InMemQUestion(){
	$unix=new unix();
	$pid=$unix->PIDOF_PATTERN("philesight --db");
	if($unix->process_exists($pid)){return true;}
	return false;
}
function run(){
	$unix=new unix();
	$sock=new sockets();
	$PhileSizeCpuLimit=$sock->GET_INFO("PhileSizeCpuLimit");
	if($PhileSizeCpuLimit==null){$PhileSizeCpuLimit=0;}
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=@file_get_contents($pidfile);
	if($oldpid<100){$oldpid=null;}
	$unix=new unix();
	if($unix->process_exists($oldpid,basename(__FILE__))){
		system_admin_events("Already executed PID $oldpid", __FILE__, __FUNCTION__, __LINE__, "disks");
		die();
	}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);		
	
	$t=time();
	chdir("/usr/share/artica-postfix/bin");
	$NICE=EXEC_NICE();
	$unix=new unix();
	$tmpfile=$unix->FILE_TEMP();
	$cmd="$NICE /usr/share/artica-postfix/bin/philesight --db /opt/artica/philesight/database.db --index / 2>&1";
	
	exec($cmd,$results);
	$database_size=$unix->file_size_human("/opt/artica/philesight/database.db");
	$took=$unix->distanceOfTimeInWords($t,time(),true);
	system_admin_events("Scanning the root directory done took $took: ".@implode("\n", $results), __FILE__, __FUNCTION__, __LINE__, "disks");
	
	sleep(3);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#run database recovery#",$ligne)){
			system_admin_events("Database is corrupted, delete it", __FILE__, __FUNCTION__, __LINE__, "disks");
			$corrupted=true;
		}
	}
	
	if($corrupted){
		@unlink("/opt/artica/philesight/database.db");
		$unix->THREAD_COMMAND_SET($GLOBALS["MECMDS"]);
	}
	
}

function getPID(){
	$unix=new unix();
	exec($unix->find_program("pgrep"). " -l -f \"/usr/share/artica-postfix/bin/philesight\"",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#pgrep#",$ligne)){continue;}
		if(preg_match("#^([0-9]+).+?philesight#",$ligne,$re)){return $re[1];}
	}	
}


?>