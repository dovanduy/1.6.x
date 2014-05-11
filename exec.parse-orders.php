<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__) .'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/class.settings.inc");

$GLOBALS["EXEC_PID_FILE"]="/etc/artica-postfix/".basename(__FILE__).".damon.pid";


$oldpid=@file_get_contents($GLOBALS["EXEC_PID_FILE"]);
$unix=new unix();

$GLOBALS["EXEC_NICE"]=$unix->EXEC_NICE();
$GLOBALS["NOHUP"]=$unix->find_program("nohup");

if($unix->process_exists($oldpid)){
	$ProcessTime=$unix->PROCCESS_TIME_MIN($oldpid);
	events("artica-background already executed pid $oldpid since $ProcessTime Minutes",__FUNCTION__,__LINE__);
	echo("Starting......: ".date("H:i:s")." artica-background Already executed pid $oldpid\n");
	die();
}

if($argv[1]=="--manual"){
	
	FillMemory();ParseLocalQueue();die();}

$sock=new sockets();
$EnableArticaBackground=$sock->GET_INFO("EnableArticaBackground");
if(!is_numeric($EnableArticaBackground)){$EnableArticaBackground=1;}
if($EnableArticaBackground==0){die();}
$GLOBALS["TOTAL_MEMORY_MB"]=$unix->TOTAL_MEMORY_MB();


if($GLOBALS["TOTAL_MEMORY_MB"]<400){
	$oldpid=@file_get_contents($GLOBALS["EXEC_PID_FILE"]);
	if($unix->process_exists($oldpid,basename(__FILE__))){events("Process Already exist pid $oldpid");die();}	
	$childpid=posix_getpid();
	echo("Starting......: ".date("H:i:s")." artica-background lower config, remove fork\n");
	@file_put_contents($GLOBALS["EXEC_PID_FILE"],$childpid);
	FillMemory();
	$renice_bin=$GLOBALS["CLASS_UNIX"]->find_program("renice");
	events("$renice_bin 19 $childpid",__FUNCTION__,__LINE__);
	shell_exec('export PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/bin/X11 &');
	shell_exec("$renice_bin 19 $childpid &");
	events("Started pid $childpid",__FUNCTION__,__LINE__);	
	ParseLocalQueue();
	if($GLOBALS["EXECUTOR_DAEMON_ENABLED"]==1){
		$nohup=$unix->find_program("nohup");
		shell_exec(trim($nohup." ".$unix->LOCATE_PHP5_BIN()." ".dirname(__FILE__)."/exec.executor.php --all >/dev/null 2>&1"));
	}
	
	die();
}




if(function_exists("pcntl_signal")){
	pcntl_signal(SIGTERM,'sig_handler');
	pcntl_signal(SIGINT, 'sig_handler');
	pcntl_signal(SIGCHLD,'sig_handler');
	pcntl_signal(SIGHUP, 'sig_handler');
}else{
	print "Starting......: ".date("H:i:s")." artica-background undefined function \"pcntl_signal\"\n";
	die();
}


set_time_limit(0);
ob_implicit_flush();
declare(ticks = 1);
$stop_server=false;
$reload=false;
$pid=pcntl_fork();


	if ($pid == -1) {
	     die("Starting......: ".date("H:i:s")." artica-background fork() call asploded!\n");
	} else if ($pid) {
	     print "Starting......: ".date("H:i:s")." artica-background fork()ed successfully.\n";
	     die();
	}


	$childpid=posix_getpid();
	@file_put_contents($GLOBALS["EXEC_PID_FILE"],$childpid);
	FillMemory();
	
	$renice_bin=$GLOBALS["CLASS_UNIX"]->find_program("renice");
	events("$renice_bin 19 $childpid",__FUNCTION__,__LINE__);
	shell_exec('export PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/bin/X11 &');
	shell_exec("$renice_bin 19 $childpid &");
	events("Started pid $childpid",__FUNCTION__,__LINE__);
	
	while ($stop_server==false) {
		sleep(10);
		ParseLocalQueue();
		if($reload){
			$reload=false;
			events("reload daemon",__FUNCTION__,__LINE__);
			FillMemory();			
		}
	}
	

function sig_handler($signo) {
    global $stop_server;
    global $reload;
    switch($signo) {
        case SIGTERM: {$stop_server = true;break;}        
        case 1: {$reload=true;}
        default: {
        	if($signo<>17){events("Receive sig_handler $signo",__FUNCTION__,__LINE__);}
        }
    }
}


function FillMemory(){
	
	include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
	$GLOBALS["CLASS_SOCKETS"]=new sockets();
	$GLOBALS["CLASS_USERS"]=new settings_inc();
	$GLOBALS["CLASS_UNIX"]=new unix();	
	$GLOBALS["TOTAL_MEMORY_MB"]=$GLOBALS["CLASS_UNIX"]->TOTAL_MEMORY_MB();	
	$GLOBALS["NICE"]=$GLOBALS["CLASS_UNIX"]->EXEC_NICE();
	$GLOBALS["NOHUP"]=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
	$GLOBALS["systemMaxOverloaded"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("systemMaxOverloaded");
	$GLOBALS["CPU_NUMBER"]=intval($GLOBALS["CLASS_USERS"]->CPU_NUMBER);
	$EnableArticaExecutor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaExecutor");
	
	if(!is_numeric($EnableArticaExecutor)){$EnableArticaExecutor=1;}	
	$GLOBALS["EXECUTOR_DAEMON_ENABLED"]=$EnableArticaExecutor;
	

	
}


function MemoryInstances(){
	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
	if(!is_file($pgrep)){return 0;}
	
	
	
	
if(!is_file($pgrep)){return;}
	
	
	$array=array();
	$cmd="$pgrep -l -f \"artica-postfix/exec\..*?\.php\" 2>&1";
	events("$cmd",__FUNCTION__,__LINE__);
	exec("$cmd",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#bin\/openvpn#",$ligne)){continue;}
		
		
		if(preg_match("#^([0-9]+)\s+.+?\s+\/usr\/share\/artica-postfix\/(.+?)\.php.*?$#",$ligne,$re)){
			$filename=trim($re[2]).".php";
			if($Toremove[$filename]){continue;}
			if(!is_numeric($re[1])){continue;}
			if(!$GLOBALS["CLASS_UNIX"]->process_exists($re[1])){continue;}
			if($GLOBALS["CLASS_UNIX"]->PID_IS_CHROOTED($re[1])){continue;}
			$time=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($re[1]);
			
			
			if($filename=="exec.artica.meta.php"){
				if($time>20){
					events("killing exec.artica.meta.php it freeze...",__FUNCTION__,__LINE__);
					shell_exec("/bin/kill -9 {$re[1]}");
					continue;
				}
			}
			
			
		 	if($filename=="exec.clean.logs.php"){
				if($time>60){
				events("killing exec.clean.logs.php it freeze...",__FUNCTION__,__LINE__);
				shell_exec("/bin/kill -9 {$re[1]}");
				continue;
				}
			}
			
			if($filename=="exec.squid.stats.php"){
				if($time>380){
				events("killing exec.squid.stats.php it freeze...",__FUNCTION__,__LINE__);
				shell_exec("/bin/kill -9 {$re[1]}");
				continue;
				}
			}
			
			if($filename=="exec.mysql.build.php"){
				if($time>30){
				events("killing exec.mysql.build.php it freeze...",__FUNCTION__,__LINE__);
				shell_exec("/bin/kill -9 {$re[1]}");
				continue;
				}
			}
						
			if($filename=="exec.smtp-hack.export.php"){
				if($time>10){
				events("killing exec.smtp-hack.export.php it freeze...",__FUNCTION__,__LINE__);
				shell_exec("/bin/kill -9 {$re[1]}");
				continue;
				}
			}
			
			if($filename=="exec.postfix-logger.php"){
				if($time>10){
				events("killing exec.postfix-logger.php it freeze...",__FUNCTION__,__LINE__);
				shell_exec("/bin/kill -9 {$re[1]}");
				continue;
				}
			}	

			if($filename=="exec.openvpn.php"){
				if($time>5){
			
				events("killing exec.openvpn.php it freeze...",__FUNCTION__,__LINE__);
				shell_exec("/bin/kill -9 {$re[1]}");
				continue;
				}
			}
			
			if($filename=="exec.test-connection.php"){
				if($time>5){
			
				events("killing exec.test-connection.php it freeze...",__FUNCTION__,__LINE__);
				shell_exec("/bin/kill -9 {$re[1]}");
				continue;
				}
			}

			if($filename=="exec.watchdog.php"){
				if($time>5){
		
					events("killing exec.openvpn.php it freeze...",__FUNCTION__,__LINE__);
					shell_exec("/bin/kill -9 {$re[1]}");
					continue;
				}				
			}
			
			if($filename=="exec.virtuals-ip.php"){
				if($time>10){
					$GLOBALS["CLASS_UNIX"]->send_email_events("[artica-background] exec.virtuals-ip.php is killed after {$time}Mn live",$ligne,"system");
					events("killing exec.virtuals-ip.php it freeze...",__FUNCTION__,__LINE__);
					shell_exec("/bin/kill -9 {$re[1]}");
					continue;
				}				
			}

			if($filename=="exec.squid-tail-injector.php"){
				if($time>65){
				events("killing exec.squid-tail-injector.php it freeze...",__FUNCTION__,__LINE__);
				shell_exec("/bin/kill -9 {$re[1]}");
				continue;
				}				
			}				
		
			
		 events("PROCESS IN MEMORY: [{$re[1]}] \"$filename\" {$time}Mn");
		 $array[]="[{$re[1]}] $filename ({$time}Mn)";
		}
	}
	
	
	$count=count($array);
	if(count($array)>0){
		events("$count processe(s) In memory:",__FUNCTION__,__LINE__);
	}
	$mem=round(((memory_get_usage()/1024)/1000),2);
	events("{$mem}MB consumed in memory",__FUNCTION__,__LINE__);
	
	
	//yorel
	exec("$pgrep -l -f \"perl.+?yorel-upd\" 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#^([0-9]+)\s+#",$ligne,$re)){
			if($GLOBALS["CLASS_UNIX"]->PID_IS_CHROOTED($re[1])){continue;}
			$time=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($re[1]);
			if($time>10){
				$GLOBALS["CLASS_UNIX"]->send_email_events("[artica-background] yorel-upd is killed after {$time}Mn live");
				events("killing yorel-upd it {$re[1]} freeze {$time}Mn...",__FUNCTION__,__LINE__);
				shell_exec("/bin/kill -9 {$re[1]}");
			}
		}
		
	}
	
	
	
	
	return $count;
	
	
}







function events($text){
		
		$filename=basename(__FILE__);
		if(!isset($GLOBALS["CLASS_UNIX"])){
			include_once(dirname(__FILE__)."/framework/class.unix.inc");
			$GLOBALS["CLASS_UNIX"]=new unix();
		}
		$GLOBALS["CLASS_UNIX"]->events("$filename $text");
		events2($text);
}

function events2($text){
		$common="/var/log/artica-postfix/parse.orders.log";
		$size=@filesize($common);
		if($size>100000){@unlink($common);}
		$pid=getmypid();
		$date=date("Y-m-d H:i:s");
		$h = @fopen($common, 'a');
		$sline="[$pid] $text";
		$line="$date [$pid] $text\n";
		@fwrite($h,$line);
		@fclose($h);
	}	


?>
