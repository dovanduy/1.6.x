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


initd_debian();


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
		echo "Starting......: WINBIND waiting pipe...$i/5\n";
		reset($dirs);
		while (list ($dir, $val) = each ($dirs) ){
			if($GLOBALS["VERBOSE"]){echo "cheks $dir/pipe\n";}
			$array=$unix->alt_stat("$dir/pipe");
			
			if(isset($array["file"])){
				echo "Starting......: WINBIND setfacl_squid:: apply Squid settings in $dir/pipe\n";
				shell_exec("$setfacl -R -m u:squid:rwx $dir/pipe >/dev/null 2>&1");
				shell_exec("$setfacl -R -m g:squid:rwx $dir/pipe >/dev/null 2>&1");
				shell_exec("$chmod 1777 $dir/pipe");
				echo "Starting......: WINBIND setfacl_squid:: reloading squid\n";
				
				if(!$without_reload){
					squid_watchdog_events("Reconfiguring Proxy parameters...");;
					shell_exec("$squidbin -k reconfigure >/dev/null 2>&1");}
				return;
			}else{
				if($GLOBALS["VERBOSE"]){echo "$dir/pipe no such file\n";}
			}
		}
		sleep(1);
	}
	echo "Starting......: WINBIND setfacl_squid:: waiting pipe done...\n";
	
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
				echo "Starting......: WINBIND DirsPrivileges:: 1777 in $dir for squid\n";
				shell_exec("$chmod 1777 $dir");
				@mkdir("$dir/winbindd_privileged",0750,true);
				echo "Starting......: WINBIND DirsPrivileges:: 0750 in $dir/winbindd_privileged\n";
				shell_exec("$chmod 0750 $dir/winbindd_privileged");
				chgrp("$dir/winbindd_privileged", "winbindd_priv");
			}
		}
		
	}
		
	
	
	while (list ($dir, $val) = each ($dirs) ){
		if(is_dir("$dir/winbindd_privileged")){
			echo "Starting......: WINBIND DirsPrivileges:: 0750 in $dir/winbindd_privileged\n";
			shell_exec("$chmod 0750 $dir/winbindd_privileged");}
			chgrp("$dir/winbindd_privileged", "winbindd_priv");
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
	
	
	if(!$nopid){
		$pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=$unix->get_pid_from_file($pidpath);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			echo "Starting......: WINBIND Already running start process exists\n";
			Winbindd_events("Already running start process exists",__FUNCTION__,__LINE__);
			return;
		}
	}
	
	if(is_run()){
		echo "Starting......: WINBIND already running....\n";
		Winbindd_events("Winbindd ask to start But already running",__FUNCTION__,__LINE__);
		echo "Starting......: WINBIND check privileges...\n";
		DirsPrivileges();
		return;
	}
	
	
	$winbindd=$unix->find_program("winbindd");
	echo "Starting......: WINBIND $winbindd....\n";
	
	
	
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
	
	$ulimit=$unix->find_program("ulimit");
	Winbindd_events("Winbindd set ulimit to 65500",__FUNCTION__,__LINE__);
	shell_exec("$ulimit -n 65500 >/dev/null 2>&1");
	
	DirsPrivileges();
	shell_exec($winbindd." -D");
	for($i=0;$i<10;$i++){
		if(is_run()){break;}
		echo "Starting......: WINBIND (start) waiting to run\n";
		sleep(1);
	}
	if(is_run()){
		$pid=WINBIND_PID();
		Winbindd_events("Winbindd start success PID $pid",__FUNCTION__,__LINE__);
		echo "Starting......: WINBIND (start) success PID $pid\n";
	}else{
		echo "Starting......: WINBIND (start) failed\n";
	}
}

function restart(){
	
	if(!is_run()){
		echo "Starting......: WINBIND (restart) not running, start it...\n";
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
				Winbindd_events("Winbindd ask to restart need to wait 60Mn pid:$pid $timepid",__FUNCTION__,__LINE__);
				return;
			}else{
				echo "Starting......: WINBIND (restart) not running, start it...\n";
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
			echo "Starting......: WINBIND reloading...\n";
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
			echo "Stopping WINBIND.............: killing {$re[1]} pid(s)\n";
			shell_exec("$kill -9 {$re[1]} >/dev/null");
		}
	}
	
}


function initd_debian(){
	$unix=new unix();
	$sock=new sockets();
	$settings=new settings_inc();
	$winbindd=$unix->find_program("winbindd");
	if(!is_file($winbindd)){
		echo "Starting......: WINBIND no such binary...\n";
		return;
	}
	$servicebin=$unix->find_program("update-rc.d");
	
	if(!is_file($servicebin)){
		echo "Starting......: WINBIND not a Debian system...\n";
		return;
	}
	$php5=$unix->LOCATE_PHP5_BIN();	
	$cat=$unix->find_program("cat");
	$mkdir=$unix->find_program("mkdir");
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	$smbcontrol=$unix->find_program("smbcontrol");
	$SquidInstalled=0;
	if($settings->SQUID_INSTALLED){
		$SquidInstalled=1;
	}
	
	if(!is_numeric("$EnableKerbAuth")){$EnableKerbAuth=0;}
	$setfacl=$unix->find_program("setfacl")	;
	echo "Starting......: WINBIND writing init.d script..\n";
	echo "Starting......: WINBIND binary: `$winbindd`\n";
	echo "Starting......: WINBIND Squid+Winbindd ?: `$EnableKerbAuth`\n";
	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          winbind";
	$f[]="# Required-Start:    \$network \$remote_fs \$syslog";
	$f[]="# Required-Stop:     \$network \$remote_fs \$syslog";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: start Winbind daemon";
	$f[]="### END INIT INFO";
	$f[]="";
	$f[]="";
	$f[]="PATH=/sbin:/bin:/usr/sbin:/usr/bin:/usr/local/sbin:/usr/local/bin";
	$f[]="DAEMON=$winbindd";
	$f[]="PIDDIR=/var/run/samba";
	$f[]="SMBCONTROL=$smbcontrol";
	$f[]="WINBINDPID=\$PIDDIR/winbindd.pid";
	$f[]="EnableKerbAuth=$EnableKerbAuth";
	$f[]="SquidInstalled=$SquidInstalled";
	$f[]="# clear conflicting settings from the environment";
	$f[]="unset TMPDIR";
	$f[]="";
	$f[]="# See if the daemon is there";
	$f[]="test -x \$DAEMON || exit 0";
	$f[]="";
	$f[]=". /lib/lsb/init-functions";
	$f[]="";
	$f[]="case \"\$1\" in";
	$f[]="	start)";
	$f[]="		$php5 ".__FILE__." --start";
	$f[]="		;;";
	$f[]="";
	$f[]="	stop)";
	$f[]="		echo \"Stopping WINBIND.............: winbind\"\n";
	$f[]="		$php5 ".__FILE__." --stop";
	$f[]="		;;";
	$f[]="";
	$f[]="	restart|force-reload)";
	$f[]="		$php5 ".__FILE__." --restart";
	$f[]="		;;";
	$f[]="	reload)";
	$f[]="		$php5 ".__FILE__." --restart";
	$f[]="		;;";	
	$f[]="";
	$f[]="	status)";
	$f[]="		status_of_proc -p \$WINBINDPID \$DAEMON winbind && exit 0 || exit \$?";
	$f[]="		;;";
	$f[]="	*)";
	$f[]="		echo \"Usage: /etc/init.d/winbind {start|stop|restart|force-reload|reload|status}\"";
	$f[]="		exit 1";
	$f[]="		;;";
	$f[]="esac";
	$f[]="";
	@file_put_contents("/etc/init.d/winbind", @implode("\n", $f));
	@chmod("/etc/init.d/winbind", 0755);
	shell_exec("$servicebin -f winbind defaults >/dev/null 2>&1");
	echo "configuring...: WINBIND init.d debian mode done\n";
	
}