<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["VERBOSE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;
$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.nginx.inc');
include_once(dirname(__FILE__).'/ressources/class.freeweb.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.reverse.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');




	$GLOBALS["ARGVS"]=implode(" ",$argv);
	if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
	if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
	if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
	if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();die();}
	



function PID_NUM(){
	$filename=PID_PATH();
	$pid=trim(@file_get_contents($filename));
	$unix=new unix();
	if($unix->process_exists($pid)){return $pid;}
	
}
//##############################################################################
function PID_PATH(){
	if(isset($GLOBALS["PID_PATH"])){return $GLOBALS["PID_PATH"];}
	$unix=new unix();

	if(!isset($GLOBALS["QUEUE_DIRECTORY"])){
		$postconf=$unix->find_program("postconf");
		exec("$postconf queue_directory 2>&1",$results);
		while (list ($num, $line) = each ($results)){
			$line=trim($line);
			if($line==null){continue;}
			if(preg_match("#^queue_directory.*?=(.+)#", $line,$re)){
				$GLOBALS["QUEUE_DIRECTORY"]=trim($re[1]);
				break;
			}
			
		}
	}
	
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."/line: "."{$GLOBALS["QUEUE_DIRECTORY"]}/pid/master.pid\n";}
	$GLOBALS["PID_PATH"]="{$GLOBALS["QUEUE_DIRECTORY"]}/pid/master.pid";
	return $GLOBALS["PID_PATH"];
}
//##############################################################################
function restart(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: nginx Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());	
	stop(true);
	start(true);
	
}
//##############################################################################
function reload(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Postfix Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	$postconf=$unix->find_program("postconf");
	$postfix=$unix->find_program("postfix");
	$unixsocket=$users->cyrus_lmtp_path;
	if($unixsocket==null){$unixsocket="/var/spool/postfix/var/run/cyrus/socket/lmtp";}
	@chown($unixsocket, "postfix");
	@chgrp($unixsocket, "postfix");
	@chmod($unixsocket,0777);
	
	if(is_file("/etc/sasldb2")){
		@chown("/etc/sasldb2", "postfix");
		@chgrp("/etc/sasldb2", "postfix");
	}
	
	shell_exec("$postfix reload");

}
//##############################################################################
function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$postconf=$unix->find_program("postconf");
	$postfix=$unix->find_program("postfix");
	$usermod=$unix->find_program("usermod");
	$users=new usersMenus();
	if(!is_file($postconf)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Postfix, not installed\n";}
		return;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Postfix Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Postfix Service already started $pid since {$timepid}Mn...\n";}
		return;
	}

	$EnablePostfix=$sock->GET_INFO("EnablePostfix");
	$EnableStopPostfix=$sock->GET_INFO("EnableStopPostfix");
	if(!is_numeric($EnablePostfix)){$EnablePostfix=1;}
	if(!is_numeric($EnableStopPostfix)){$EnableStopPostfix=0;}
	if($EnableStopPostfix==1){$EnablePostfix=0;}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Postfix EnablePostfix     = $EnablePostfix\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Postfix EnableStopPostfix = $EnableStopPostfix\n";}
	if($EnablePostfix==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Postfix service disabled\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Postfix checking postfix user\n";}
	$unix->CreateUnixUser("postfix","postfix");
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Postfix checking clamav user\n";}
	$unix->CreateUnixUser("clamav","clamav");
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Postfix checking postdrop group\n";}
	$unix->SystemCreateGroup("postdrop");
	
	
	
	shell_exec("$usermod -a -G postfix clamav >/dev/null 2>&1");
	@mkdir("/var/amavis",0755,true);
	@chmod("/var/amavis", 0755);
	if(!is_file("/etc/postfix/relay_domains.db")){@touch("/etc/postfix/relay_domains"); shell_exec("postmap hash:/etc/postfix/relay_domains"); }
	if(is_file("/etc/sasldb2")){
		@chown("/etc/sasldb2", "postfix");
		@chgrp("/etc/sasldb2", "postfix");
	}
	
	
	
	$unixsocket=$users->cyrus_lmtp_path;
	if($unixsocket==null){$unixsocket="/var/spool/postfix/var/run/cyrus/socket/lmtp";}
	@chown($unixsocket, "postfix");
	@chgrp($unixsocket, "postfix");
	@chmod($unixsocket,0777);
	
	
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$TMPFILE=$unix->FILE_TEMP();
	$cmd="$nohup $postfix start >$TMPFILE 2>&1 &";
	
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	shell_exec($cmd);

	for($i=0;$i<6;$i++){
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Postfix service waiting $i/6...\n";}
		sleep(1);
	}

	$f=explode("\n",@file_get_contents($TMPFILE));
	@unlink($TMPFILE);
	while (list ($num, $line) = each ($f)){
		if(trim($line)==null){continue;}
		if(strpos($line, "unused parameter:")>0){continue;}
		
		if(preg_match("#fatal:.*?directory\s+(.+?):\s+Permission denied#", $line,$re)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: permission error on \"{$re[1]}\"\n";}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Running permission tool\n";}
			exec("$postfix set-permissions 2>&1",$results2);
			while (list ($num, $line) = each ($results2)){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $line\n";}
			}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: You need to restart again the service\n";}
		}
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $line\n";}
		
	}
	
	
	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Postfix service Success service started pid:$pid...\n";}
		return;
	}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Postfix service failed...\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $cmd\n";}
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	
}
function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Postfix service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Postfix Pid file: ". PID_PATH()."\n";}
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Postfix service already stopped...\n";}
		return;
	}
	
	$postconf=$unix->find_program("postconf");
	$postfix=$unix->find_program("postfix");
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Postfix service Shutdown pid $pid...\n";}
	
	
	
	shell_exec("$postfix stop >/dev/null 2>&1");
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Postfix service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Postfix service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Postfix service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Postfix service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Postfix service success...\n";}
		return;
	}
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Postfix service failed...\n";}
	
}



?>