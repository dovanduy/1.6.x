#!/usr/bin/php
<?php
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.nics.inc');
include_once(dirname(__FILE__) . '/ressources/class.os.system.inc');
include_once(dirname(__FILE__) . '/ressources/class.openssh.inc');
$GLOBALS["TITLENAME"]="OpenSSH daemon";
$GLOBALS["MONIT"]=false;
$GLOBALS["FORCE"]=false;
if(preg_match("#--monit#", @implode(" ", $argv))){$GLOBALS["MONIT"]=true;}
if(preg_match("#--force#", @implode(" ", $argv))){$GLOBALS["FORCE"]=true;}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();exit;}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit;}
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit;}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit;}
if($argv[1]=="--progress"){$GLOBALS["OUTPUT"]=true;progress();exit;}


function build_progress($text,$pourc){
	$filename=basename(__FILE__);

	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();

		if(isset($trace[0])){
			$file=basename($trace[0]["file"]);
			$function=$trace[0]["function"];
			$line=$trace[0]["line"];
		}

		if(isset($trace[1])){
			$file=basename($trace[1]["file"]);
			$function=$trace[1]["function"];
			$line=$trace[1]["line"];
		}



	}


	echo "[{$pourc}%] $filename $text ( $function Line $line)\n";
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/sshd.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);
	if($GLOBALS["OUTPUT"]){usleep(5000);}

}

function progress(){
	$unix=new unix();
	build_progress("Writing configuration...",10);
	$ssh=new openssh();
	$data=$ssh->save(true);
	echo "Config file: $LOCATE_SSHD_CONFIG_PATH\n";
	$LOCATE_SSHD_CONFIG_PATH=$unix->LOCATE_SSHD_CONFIG_PATH();
	@file_put_contents($LOCATE_SSHD_CONFIG_PATH, $data);
	build_progress("{stopping_service}...",50);
	stop(true);
	build_progress("{starting_service}...",70);
	start(true);
	build_progress("{done}...",100);
}


function reload(){
	$unix=new unix();
	$sshd=$unix->find_program("sshd");
	if(!is_file($sshd)){return;}
	
	if(is_file("/etc/init.d/ssh")){
		system("/etc/init.d/ssh restart");
		return;
	}
	
	if(is_file("/etc/init.d/ssh")){
		system("/etc/init.d/ssh restart");
		return;
	}
	if(is_file("/etc/init.d/sshd")){
		system("/etc/init.d/sshd restart");
		return;
	}
	
	$pid=$unix->PIDOF($sshd);
	$kill=$unix->find_program("kill");
	if($unix->process_exists($pid)){
		unix_system_HUP($pid);
	}
	
	
	
}
function restart() {
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}

	$pid=PID_NUM();
	if(!$GLOBALS["FORCE"]){
		if($unix->process_exists($pid)){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($time<10){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} only restart each 10mn is allowed\n";}
				return;
			}
		}
	}


	@file_put_contents($pidfile, getmypid());
	if(is_file("/etc/init.d/ssh")){
		system("/etc/init.d/ssh restart");
		return;
	}
	
	if(is_file("/etc/init.d/ssh")){
		system("/etc/init.d/ssh restart");
		return;
	}
	if(is_file("/etc/init.d/sshd")){
		system("/etc/init.d/sshd restart");
		return;
	}
	
	
	stop(true);
	sleep(1);
	start(true);

}

function SCRIPT_FILE_PATH(){
	if(is_file("/etc/init.d/ssh")){return "/etc/init.d/ssh";}
	if(is_file("/etc/init.d/sshd")){return "/etc/init.d/sshd";}
	if(is_file("/etc/init.d/openssh")){return "/etc/init.d/openssh";}
}

function start($aspid=false){
	
	$unix=new unix();
	$sock=new sockets();

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		return;
	}
	$sshd=$unix->find_program("sshd");
	if(!is_file($sshd)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} sshd no such binary\n";}
		return;
	}
	
	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		$time=$unix->PROCESS_TTL($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} sshd already running $pid since $time\n";}
		return;
	}
	
	
	$EnableOpenSSH=$sock->GET_INFO("EnableOpenSSH");
	if(!is_numeric($EnableOpenSSH)){$EnableOpenSSH=1;}


	if($EnableOpenSSH==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableOpenSSH)\n";}
		return;
	}

	if(!is_dir("/var/run/sshd")){@mkdir("/var/run/sshd",0755,true);}
	if(!is_dir("/root/.ssh")){@mkdir("/root/.ssh",0700,true);}
	$unix->chown_func("root", "root","/root");
	@chmod("/var/run/sshd", 0755);
	shell_exec('/bin/chmod go-w /root');
	shell_exec('/bin/chmod 700 /root/.ssh');
	if(is_file('/root/.ssh/authorized_keys')){shell_exec('/bin/chmod 600 /root/.ssh/authorized_keys');}
	
	
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	
	$init=SCRIPT_FILE_PATH();
	if(is_file($init)){
		$cmd="$init 2>&1 &";
	}else{
		$cmd="$sshd >/dev/null 2>&1 &";
	}
	
	
	
	system($cmd);




	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		shell_exec("$nohup /etc/init.d/auth-tail restart >/dev/null 2>&1");

	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	}


}
function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		return;
	}
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");

	if($GLOBALS["MONIT"]){
		$pid=PID_NUM();
		if($unix->process_exists($pid)){
			@file_put_contents("/var/run/sshd.pid", $pid);
			return;
		}
	}


	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	if($GLOBALS["FORCE"]){unix_system_kill_force($pid);}
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}

}
function PID_NUM(){
	$unix=new unix();
	$sshd=$unix->find_program("sshd");
	$pidfile="/var/run/sshd.pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF($sshd);

}