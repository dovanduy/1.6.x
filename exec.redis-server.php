<?php
if(is_file("/etc/artica-postfix/FROM_ISO")){if(is_file("/etc/init.d/artica-cd")){print "Starting......: ".date("H:i:s")." artica-". basename(__FILE__)." Waiting Artica-CD to finish\n";die();}}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["SERVICE_NAME"]="Persistent key-value db";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');


$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();die();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;build();die();}

function restart($nopid=false){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if(!$nopid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
	}
	@file_put_contents($pidfile, getmypid());
	stop(true);
	build();
	start(true);
}

function reload($nopid=false){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if(!$nopid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
	}
	@file_put_contents($pidfile, getmypid());

	$sock=new sockets();
	$Enablentopng=$sock->GET_INFO("Enablentopng");
	if(!is_numeric($Enablentopng)){$Enablentopng=1;}
	if($Enablentopng==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Disabled ( see Enablentopng )...\n";}
		return;		
	}
	
	
	build();
	$masterbin=$unix->find_program("redis-server");
	if(!is_file($masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Reload........: [INIT]: {$GLOBALS["SERVICE_NAME"]} not installed\n";}
		return;
	}
	$pid=redis_pid();
	$kill=$unix->find_program("kill");
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Reload........: [INIT]: {$GLOBALS["SERVICE_NAME"]} Service running since {$time}Mn...\n";}
		unix_system_HUP($pid);
		return;
	}
	start(true);
}

function NETWORK_ALL_INTERFACES(){
	if(isset($GLOBALS["NETWORK_ALL_INTERFACES"])){return $GLOBALS["NETWORK_ALL_INTERFACES"];}
	$unix=new unix();
	$GLOBALS["NETWORK_ALL_INTERFACES"]=$unix->NETWORK_ALL_INTERFACES(true);
	unset($GLOBALS["NETWORK_ALL_INTERFACES"]["127.0.0.1"]);
}


function build(){
	$sock=new sockets();
	$unix=new unix();
	$sysctl=$unix->find_program("sysctl");
	shell_exec("$sysctl \"vm.overcommit_memory=1\" 2>&1");
	
	$f[]="daemonize yes";
	$f[]="pidfile /var/run/redis/redis-server.pid";
	$f[]="port 6379";
	$f[]="bind 127.0.0.1";
	$f[]="# unixsocket /var/run/redis/redis.sock";
	$f[]="# unixsocketperm 755";
	$f[]="timeout 0";
	$f[]="loglevel notice";
	$f[]="logfile /var/log/redis/redis-server.log";
	$f[]="syslog-enabled yes";
	$f[]="syslog-ident redis-server";
	$f[]="syslog-facility local5";
	$f[]="databases 16";
	$f[]="save 900 1";
	$f[]="save 300 10";
	$f[]="save 60 10000";
	$f[]="rdbcompression yes";
	$f[]="dbfilename dump.rdb";
	$f[]="dir /home/redis";
	$f[]="slave-serve-stale-data yes";
	$f[]="# maxclients 128";
	$f[]="# maxmemory <bytes>";
	$f[]="# maxmemory-policy volatile-lru";
	$f[]="# maxmemory-samples 3";
	$f[]="appendonly no";
	$f[]="appendfsync everysec";
	$f[]="no-appendfsync-on-rewrite no";
	$f[]="auto-aof-rewrite-percentage 100";
	$f[]="auto-aof-rewrite-min-size 64mb";
	$f[]="slowlog-log-slower-than 10000";
	$f[]="slowlog-max-len 128";
	$f[]="vm-enabled no";
	$f[]="vm-swap-file /home/redis/redis.swap";
	$f[]="vm-max-memory 0";
	$f[]="vm-page-size 32";
	$f[]="vm-pages 134217728";
	$f[]="vm-max-threads 4";
	$f[]="hash-max-zipmap-entries 512";
	$f[]="hash-max-zipmap-value 64";
	$f[]="list-max-ziplist-entries 512";
	$f[]="list-max-ziplist-value 64";
	$f[]="set-max-intset-entries 512";
	$f[]="zset-max-ziplist-entries 128";
	$f[]="zset-max-ziplist-value 64";
	$f[]="activerehashing yes";
	CheckFilesAndSecurity();
	
	@file_put_contents("/etc/redis/redis.conf", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} /etc/redis/redis.conf done\n";}


}

function start($nopid=false){
	$unix=new unix();
	
	$sock=new sockets();
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if(!$nopid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
	}
	
	
	
	$pid=redis_pid();
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already running since {$time}Mn...\n";}
		return;
	}
	
	$Enablentopng=$sock->GET_INFO("Enablentopng");
	if(!is_numeric($Enablentopng)){$Enablentopng=1;}
	if($Enablentopng==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Disabled ( see Enablentopng )...\n";}
		return;		
	}
	$masterbin=$unix->find_program("redis-server");
	if(!is_file($masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Not installed...\n";}
		return;		
	}
	
	CheckFilesAndSecurity();
	$version=redis_version();
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Starting service v$version\n";}
	$cmd="$masterbin /etc/redis/redis.conf";
	shell_exec($cmd);
	
	$c=1;
	for($i=0;$i<10;$i++){
		sleep(1);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Starting service waiting $c/10\n";}
		$pid=redis_pid();
		if($unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Success PID $pid\n";}
			break;
		}
		$c++;
	}
	
	$pid=redis_pid();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $cmd\n";}
	}
	
}

function CheckFilesAndSecurity(){
	$unix=new unix();
	$unix->CreateUnixUser("redis","redis");
	$f[]="/var/run/redis";
	$f[]="/var/log/redis";
	$f[]="/home/redis";
	$f[]="/etc/redis";
	
	while (list ($num, $val) = each ($f)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} checking \"$val\"\n";}
		if(!is_dir($val)){@mkdir($val,0755,true);}
		$unix->chown_func("redis","redis","$val/*");
	}
	
}

function stop(){

	$unix=new unix();
	$sock=new sockets();
	$masterbin=$unix->find_program("redis-server");
	
	
	$pid=redis_pid();
	if(!is_file($masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Not installed\n";}
		return;
		
	}

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already stopped...\n";}
		return;
	}

	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	


	

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=redis_pid();
		if(!$unix->process_exists($pid)){break;}
		unix_system_kill($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=redis_pid();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} shutdown - force - pid $pid...\n";}
	
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=redis_pid();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";}
		unix_system_kill_force($pid);
		sleep(1);
	}

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success stopped...\n";}
		return;
	}else{
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} failed...\n";}
	}
}

function redis_version(){
	$unix=new unix();
	if(isset($GLOBALS["redis_version"])){return $GLOBALS["redis_version"];}
	$masterbin=$unix->find_program("redis-server");
	if(!is_file($masterbin)){return "0.0.0";}
	exec("$masterbin -v 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#Redis server version\s+(.+)#", $val,$re)){
			$GLOBALS["redis_version"]=trim($re[1]);
			return $GLOBALS["redis_version"];
		}
	}
}

function redis_pid(){
	$unix=new unix();
	$masterbin=$unix->find_program("redis-server");
	$pid=$unix->get_pid_from_file('/var/run/redis/redis-server.pid');
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF_PATTERN($masterbin." -f /etc/redis/redis.conf");
}