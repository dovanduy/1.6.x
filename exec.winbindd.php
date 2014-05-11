#!/usr/bin/php -q
<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["WITHOUT-RELOAD"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/framework/class.settings.inc");
$GLOBALS["CLASS_UNIX"]=new unix();

if($argv[1]=="--setfacl-squid"){setfacl_squid();exit;}
if($argv[1]=="--stop"){stop();exit;}
if($argv[1]=="--start"){start();exit;}
if($argv[1]=="--restart"){restart();exit;}
if($argv[1]=="--privs"){setfacl_squid();exit;}
if($argv[1]=="--privs-squid"){setfacl_squid(true);exit;}





function setfacl_squid($without_reload=false){
	DirsPrivileges();
	$unix=new unix();
	$sock=new sockets();
	$settings=new settings_inc();
	$setfacl=$unix->find_program("setfacl");
	$chmod=$unix->find_program("chmod");
	$squidbin=$unix->find_program("squid");
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}	
	
	$dirs["/var/run/samba/winbindd_privileged"]=true;
	$dirs["/var/lib/samba/winbindd_privileged"]=true;
	$fin=false;
	
	if(!is_file($squidbin)){$EnableKerbAuth=0;}
	if($EnableKerbAuth==0){return;}
	
	for($i=0;$i<6;$i++){
		echo "Starting......: ".date("H:i:s")." WINBIND waiting pipe...$i/5\n";
		reset($dirs);
		while (list ($dir, $val) = each ($dirs) ){
			if($GLOBALS["VERBOSE"]){echo "cheks $dir/pipe\n";}
			$array=$unix->alt_stat("$dir/pipe");
			
			if(isset($array["file"])){
				echo "Starting......: ".date("H:i:s")." WINBIND setfacl_squid:: apply Squid settings in $dir/pipe\n";
				shell_exec("$setfacl -R -m u:squid:rwx $dir/pipe >/dev/null 2>&1");
				shell_exec("$setfacl -R -m g:squid:rwx $dir/pipe >/dev/null 2>&1");
				shell_exec("$chmod 1777 $dir/pipe");
				echo "Starting......: ".date("H:i:s")." WINBIND setfacl_squid:: reloading squid\n";
				
				if(!$without_reload){
					$cmd="/etc/init.d/squid reload --script=".basename(__FILE__);
					shell_exec("$cmd >/dev/null 2>&1");
					}
				return;
			}else{
				if($GLOBALS["VERBOSE"]){echo "$dir/pipe no such file\n";}
			}
		}
		sleep(1);
	}
	echo "Starting......: ".date("H:i:s")." WINBIND setfacl_squid:: waiting pipe done...\n";
	
}
function squid_watchdog_events($text){
	$unix=new unix();
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefile=basename($trace[1]["file"]);$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];}}
	$unix->events($text,"/var/log/squid.watchdog.log",false,$sourcefunction,$sourceline);
}
function DirsPrivileges(){
	$sock=new sockets();
	$unix=new unix();
	$settings=new settings_inc();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	$chmod=$unix->find_program("chmod");
	$dirs["/var/run/samba"]=true;
	$dirs["/var/lib/samba"]=true;	
	
	$SquidInstalled=0;
	if($settings->SQUID_INSTALLED){
		$SquidInstalled=1;
	}else{
		$EnableKerbAuth=0;
	}
	
	if($EnableKerbAuth==1){
		while (list ($dir, $val) = each ($dirs) ){
			if(is_dir($dir)){
				echo "Starting......: ".date("H:i:s")." WINBIND DirsPrivileges:: 1777 in $dir for squid\n";
				shell_exec("$chmod 1777 $dir");
				@mkdir("$dir/winbindd_privileged",0750,true);
				echo "Starting......: ".date("H:i:s")." WINBIND DirsPrivileges:: 0750 in $dir/winbindd_privileged\n";
				shell_exec("$chmod 0750 $dir/winbindd_privileged");
				@chgrp("$dir/winbindd_privileged", "winbindd_priv");
			}
		}
		
	}
		
	
	
	while (list ($dir, $val) = each ($dirs) ){
		if(is_dir("$dir/winbindd_privileged")){
			echo "Starting......: ".date("H:i:s")." WINBIND DirsPrivileges:: 0750 in $dir/winbindd_privileged\n";
			shell_exec("$chmod 0750 $dir/winbindd_privileged");}
			@chgrp("$dir/winbindd_privileged", "winbindd_priv");
	}	
	
	
	
}

function is_run(){
	$unix=new unix();
	$pid=WINBIND_PID();
	if(!$unix->process_exists($pid)){return false;}
	return true;
	
}

function WINBIND_PID(){
	$pidfile="/var/run/samba/winbindd.pid";
	$unix=new unix();
	$pid=$unix->get_pid_from_file($pidfile);
	if(!$unix->process_exists($pid)){
		$winbindbin=$unix->find_program("winbindd");
		$pid=$unix->PIDOF($winbindbin);
	}
	return $pid;
}

function Winbindd_events($text,$sourcefunction=null,$sourceline=null){
	$GLOBALS["CLASS_UNIX"]->events("exec.winbindd.php::$text","/var/log/squid.watchdog.log",false,$sourcefunction,$sourceline);

}

function start($nopid=false){
	$unix=new unix();
	$sock=new sockets();
	
	if(!$nopid){
		$pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=$unix->get_pid_from_file($pidpath);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			echo "Starting......: ".date("H:i:s")." WINBIND Already running start process exists\n";
			Winbindd_events("Already running start process exists",__FUNCTION__,__LINE__);
			return;
		}
	}
	
	if(is_run()){
		echo "Starting......: ".date("H:i:s")." WINBIND already running....\n";
		Winbindd_events("Winbindd ask to start But already running",__FUNCTION__,__LINE__);
		echo "Starting......: ".date("H:i:s")." WINBIND check privileges...\n";
		DirsPrivileges();
		return;
	}
	
	
	$winbindd=$unix->find_program("winbindd");
	echo "Starting......: ".date("H:i:s")." WINBIND $winbindd....\n";
	$DisableWinbindd=$sock->GET_INFO("DisableWinbindd");
	if(!is_numeric($DisableWinbindd)){$DisableWinbindd=0;}
	$squid=$unix->LOCATE_SQUID_BIN();
	if(is_file($squid)){
		$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
		$EnableKerberosAuthentication=$sock->GET_INFO("EnableKerberosAuthentication");
		if(!is_numeric("$EnableKerberosAuthentication")){$EnableKerberosAuthentication=0;}
		if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
		if($EnableKerbAuth==1){$DisableWinbindd=0;}
	}
	
	
	
	if($DisableWinbindd==1){
		echo "Starting......: ".date("H:i:s")." WINBIND $winbindd is disabled ( see DisableWinbindd )....\n";
		stop();
		return;
	}
	
	
	$unix->CleanOldLibs();
	

	
	$pidof=$unix->find_program("pidof");
	exec("$pidof $winbindd 2>&1",$pidofr);
	$lines=trim(@implode("", $pidofr));
	Winbindd_events("Winbindd PIDOF report:" .$lines,__FUNCTION__,__LINE__);
	$tr=explode(" ",$lines);
	
	while (list ($index, $pid) = each ($tr) ){
		if(!is_numeric($pid)){continue;}
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		$cmdline=@file_get_contents("/proc/$pid/cmdline");
		Winbindd_events("Winbindd PIDOF report: $pid ({$timepid}Mn) \"$cmdline\"",__FUNCTION__,__LINE__);
		$rr[$pid]=true;
	}
	if(count($rr)>0){
		Winbindd_events("Winbindd ask to start but already running ".count($rr)." instance(s)",__FUNCTION__,__LINE__);
		DirsPrivileges();
		return;
	}
	

	
	DirsPrivileges();
	shell_exec($winbindd." -D");
	for($i=0;$i<10;$i++){
		if(is_run()){break;}
		echo "Starting......: ".date("H:i:s")." WINBIND (start) waiting to run\n";
		sleep(1);
	}
	if(is_run()){
		$pid=WINBIND_PID();
		Winbindd_events("Winbindd start success PID $pid",__FUNCTION__,__LINE__);
		echo "Starting......: ".date("H:i:s")." WINBIND (start) success PID $pid\n";
	}else{
		echo "Starting......: ".date("H:i:s")." WINBIND (start) failed\n";
	}
}

function restart(){
	
	if(!is_run()){
		echo "Starting......: ".date("H:i:s")." WINBIND (restart) not running, start it...\n";
		Winbindd_events("Winbindd (restart) not running, start it",__FUNCTION__,__LINE__);
		start(true);
		return;
	}
	
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$filetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$time=$unix->file_time_min($filetime);
	
	Winbindd_events("Winbindd ask to restart since {$time}Mn",__FUNCTION__,__LINE__);
	if(!$GLOBALS["FORCE"]){
		if($time<59){
			
			$pid=WINBIND_PID();
			if($unix->process_exists($pid)){
				$timepid=$unix->PROCESS_TTL($pid);
				echo "Starting......: ".date("H:i:s")." WINBIND ask to restart need to wait 60Mn pid:$pid $timepid\n";
				Winbindd_events("Winbindd ask to restart need to wait 60Mn pid:$pid $timepid",__FUNCTION__,__LINE__);
				return;
			}else{
				echo "Starting......: ".date("H:i:s")." WINBIND (restart) not running, start it...\n";
				shell_exec("$php5 /usr/share/artica-postfix/exec.winbindd.php --start");
			}
		}
	}
	@unlink($filetime);
	@file_put_contents($filetime, time());
	
	
	$smbcontrol=$unix->find_program("smbcontrol");
	$chmod=$unix->find_program("chmod");
	$settings=new settings_inc();
	DirsPrivileges();	

	if(!$GLOBALS["FORCE"]){
		if(is_file($smbcontrol)){
			Winbindd_events("Winbindd reloading",__FUNCTION__,__LINE__);
			echo "Starting......: ".date("H:i:s")." WINBIND reloading...\n";
			shell_exec("$smbcontrol winbindd reload-config");
			shell_exec("$smbcontrol winbindd offline");
			shell_exec("$smbcontrol winbindd online");
			setfacl_squid();
			return;
		}
	}
	Winbindd_events("Winbindd stop",__FUNCTION__,__LINE__);
	stop();
	Winbindd_events("Winbindd ask to start",__FUNCTION__,__LINE__);
	start(true);
	
}

function stop(){
	$unix=new unix();
	echo "Stopping WINBIND.............: find binaries daemons\n";
	$pidof=$unix->find_program("pidof");
	$winbindd=$unix->find_program("winbindd");
	$kill=$unix->find_program("kill");
	if(!is_file($winbindd)){return;}
	exec("$pidof $winbindd 2>&1",$results);
	while (list ($key, $val) = each ($results) ){
		if(preg_match("#([0-9\s]+)#", $val,$re)){
			
			$tb=explode(" ",$re[1]);
			while (list ($a, $b) = each ($tb) ){
				if(!is_numeric($b)){continue;}
				echo "Stopping WINBIND.............: killing $b pid\n";
				shell_exec("$kill -9 $b >/dev/null 2>&1");
			}
		}
	}
	
}


