<?php
if(is_file("/etc/artica-postfix/FROM_ISO")){if(is_file("/etc/init.d/artica-cd")){print "Starting......: ".date("H:i:s")." artica-". basename(__FILE__)." Waiting Artica-CD to finish\n";die();}}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["SERVICE_NAME"]="Network traffic probe";
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
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');


$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();die();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;build();die();}
if($argv[1]=="--clean"){$GLOBALS["OUTPUT"]=true;cleanstorage();die();}


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
	if(!is_numeric($Enablentopng)){$Enablentopng=0;}
	$EnableIntelCeleron=intval($sock->GET_INFO("EnableIntelCeleron"));
	if($EnableIntelCeleron==1){$Enablentopng=0;}
	
	if($Enablentopng==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Disabled ( see Enablentopng )...\n";}
		stop();
		return;		
	}
	
	
	build();
	$masterbin=$unix->find_program("ntopng");
	if(!is_file($masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Reload........: [INIT]: {$GLOBALS["SERVICE_NAME"]} not installed\n";}
		return;
	}
	$pid=ntopng_pid();
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
	CheckFilesAndSecurity();
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]}  done\n";}


}

function all_interfaces(){
	$unix=new unix();
	
	$masterbin=$unix->find_program("ntopng");
	exec("$masterbin -h 2>&1",$results);
	while (list ($index, $ligne) = each ($results) ){
		if(preg_match("#\s+([0-9])\.\s+(.+)#", $ligne,$re)){
			$arrayINT[trim($re[2])]=$re[1];
		}
		
	}
		
	
	$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
	while (list ($Interface, $ligne) = each ($NETWORK_ALL_INTERFACES) ){
		if($Interface=="lo"){continue;}
		if($ligne["IPADDR"]=="0.0.0.0"){continue;}
		if(preg_match("#(.*?):#", $Interface)){continue;}
		$TRA[$Interface]=$Interface;
	}
	while (list ($Interface, $ligne) = each ($TRA) ){
		$num=$arrayINT[$Interface];
		if(!is_numeric($num)){continue;}
		$b[]="-i $num";
	}
	return @implode(" ", $b);
	
}

function start($nopid=false){
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
	
	
	
	$pid=ntopng_pid();
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already running since {$time}Mn...\n";}
		return;
	}
	
	$Enablentopng=$sock->GET_INFO("Enablentopng");
	if(!is_numeric($Enablentopng)){$Enablentopng=0;}
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	$EnableIntelCeleron=intval($sock->GET_INFO("EnableIntelCeleron"));
	if($SquidPerformance>2){$Enablentopng=0;}
	if($EnableIntelCeleron==1){$Enablentopng=0;}
	if($Enablentopng==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Disabled ( see Enablentopng )...\n";}
		return;		
	}
	$masterbin=$unix->find_program("ntopng");
	if(!is_file($masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Not installed...\n";}
		return;		
	}
	
	$redis_pid=redis_pid();
	if(!$unix->process_exists($redis_pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Starting redis-server\n";}
	}
	$redis_pid=redis_pid();
	if(!$unix->process_exists($redis_pid)){
		$php=$unix->LOCATE_PHP5_BIN();
		system("$php /usr/share/artica-postfix/exec.redis-server.php --start");
		
	}
	$redis_pid=redis_pid();
	if(!$unix->process_exists($redis_pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} failed, unable to start redis-server\n";}
		return;
	}
	
	CheckFilesAndSecurity();
	$version=ntopng_version();
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Starting service v$version\n";}
	
	
	$net=new networkscanner();
	
	while (list ($num, $maks) = each ($net->networklist)){
		if(trim($maks)==null){continue;}
		if(isset($net->Networks_disabled[$maks])){continue;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Analyze $maks\n";}
		$hash[$maks]=$maks;
	}
	while (list ($a, $b) = each ($hash)){ $MASKZ[]=$a; }
	
	$arrayConf=unserialize(base64_decode($sock->GET_INFO("ntopng")));
	
	
	if(!is_numeric($arrayConf["HTTP_PORT"])){$arrayConf["HTTP_PORT"]=3000;}
	
	if(intval($arrayConf["ENABLE_LOGIN"])==1){
		$ldap=new clladp();
		$rediscli=$unix->find_program("redis-cli");
		shell_exec("$rediscli SET ntopng.user.$ldap->ldap_admin.full_name $ldap->ldap_admin");
		shell_exec("$rediscli SET ntopng.user.$ldap->ldap_admin.group administrator");
		shell_exec("$rediscli SET ntopng.user.$ldap->ldap_admin.password ".md5($ldap->ldap_password));
		
	}
	
	
	$f[]=$masterbin;
	$f[]="--daemon";
	$f[]="--verbose";
	$f[]="--dns-mode 1";
	$f[]="--http-port {$arrayConf["HTTP_PORT"]}";
	if(intval($arrayConf["ENABLE_LOGIN"])==0){
		$f[]="-l 1";
	}
	$f[]="--local-networks \"".@implode(",", $MASKZ)."\"";
	$f[]="--user root";
	$f[]="--data-dir /home/ntopng";
	$f[]="--pid /var/run/ntopng/ntopng.pid";
	
	$f[]=all_interfaces();

	$cmd=@implode(" ", $f);
	shell_exec($cmd);
	
	$c=1;
	for($i=0;$i<10;$i++){
		sleep(1);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Starting service waiting $c/10\n";}
		$pid=ntopng_pid();
		if($unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Success PID $pid\n";}
			break;
		}
		$c++;
	}
	
	$pid=ntopng_pid();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $cmd\n";}
	}
	
}

function CheckFilesAndSecurity(){
	$unix=new unix();
	$f[]="/var/run/ntopng";
	$f[]="/var/log/ntopng";
	$f[]="/home/ntopng";
	

	
	while (list ($num, $val) = each ($f)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} checking \"$val\"\n";}
		if(!is_dir($val)){@mkdir($val,0755,true);}
		//$unix->chown_func("redis","redis","$val/*");
	}
	
}

function stop(){

	$unix=new unix();
	$sock=new sockets();
	$masterbin=$unix->find_program("ntopng");
	
	
	
	if(!is_file($masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Not installed\n";}
		return;
		
	}
	
	
	$pid=ntopng_pid();
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
		$pid=ntopng_pid();
		if(!$unix->process_exists($pid)){break;}
		unix_system_kill($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=ntopng_pid();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} shutdown - force - pid $pid...\n";}
	
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=ntopng_pid();
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

function ntopng_version(){
	$unix=new unix();
	if(isset($GLOBALS["ntopng_version"])){return $GLOBALS["ntopng_version"];}
	$masterbin=$unix->find_program("ntopng");
	if(!is_file($masterbin)){return "0.0.0";}
	exec("$masterbin -h 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#ntopng.*?v\.([0-9\.]+)#", $val,$re)){
			$GLOBALS["ntopng_version"]=trim($re[1]);
			return $GLOBALS["ntopng_version"];
		}
	}
}

function ntopng_pid(){
	$unix=new unix();
	$masterbin=$unix->find_program("ntopng");
	$pid=$unix->get_pid_from_file('/var/run/ntopng/ntopng.pid');
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF($masterbin);
}
function redis_pid(){
	$unix=new unix();
	$masterbin=$unix->find_program("redis-server");
	$pid=$unix->get_pid_from_file('/var/run/redis/redis-server.pid');
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF_PATTERN($masterbin." -f /etc/redis/redis.conf");
}

function cleanstorage(){
	
	$sock=new sockets();
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".time";
	$CacheFile="/etc/artica-postfix/settings/Daemons/NTOPNgSize";
	$pid=file_get_contents("$pidfile");
	if($GLOBALS["VERBOSE"]){echo "$timefile\n";}
	
	if(system_is_overloaded(basename(__FILE__))){die();}
	
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$timeMin=$unix->PROCCESS_TIME_MIN($pid);
		if($timeMin>240){
			system_admin_events("Too many TTL, $pid will be killed",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			$kill=$unix->find_program("kill");
			unix_system_kill_force($pid);
		}else{
			die();
		}
	}
	if(is_file($CacheFile)){
		if(!$GLOBALS["FORCE"]){
			$TimeExec=$unix->file_time_min($timefile);
			if($TimeExec<1880){return;}
		}
	}
	@unlink($timefile);
	@file_put_contents($timefile, time());	
	
	$sock=new sockets();
	$arrayConf=unserialize(base64_decode($sock->GET_INFO("ntopng")));
	
	$Enablentopng=$sock->GET_INFO("Enablentopng");
	if(!is_numeric($Enablentopng)){$Enablentopng=0;}
	$EnableIntelCeleron=intval($sock->GET_INFO("EnableIntelCeleron"));
	if($EnableIntelCeleron==1){$Enablentopng=0;}
	if(!is_numeric($arrayConf["HTTP_PORT"])){$arrayConf["HTTP_PORT"]=3000;}
	if(!is_numeric($arrayConf["ENABLE_LOGIN"])){$arrayConf["ENABLE_LOGIN"]=0;}
	if(!is_numeric($arrayConf["MAX_DAYS"])){$arrayConf["MAX_DAYS"]=30;}
	if(!is_numeric($arrayConf["MAX_SIZE"])){$arrayConf["MAX_SIZE"]=5000;}
	
	$rm=$unix->find_program("rm");
	$size=$unix->DIRSIZE_MB("/home/ntopng");
	
	
	if($size>$arrayConf["MAX_SIZE"]){
		shell_exec("$rm -rf /home/ntopng");
		$redis=$unix->find_program("redis-cli");
		shell_exec("$redis flushall");
		squid_admin_mysql(1, "Removing NTOP NG directory {$size}MB, exceed {$arrayConf["MAX_SIZE"]}MB", null,__FILE__,__LINE__);
		shell_exec("/etc/init.d/ntopng restart");
	}
	
	$ThisYear=date("y");
	$directory="/home/ntopng/db";
	
	if(!is_dir($directory)){return;}
	
	$unix=new unix();
	
	
	
	if(is_dir("/home/ntopng/db/{$ThisYear}")){
		echo "Scanning /home/ntopng/db/{$ThisYear}\n";
		$directory="/home/ntopng/db/{$ThisYear}";
		$thisMonth=date("m");
		if(strlen($thisMonth)==1){$thisMonth="0{$thisMonth}";}
		if(!is_dir($directory)){return;}
		
		echo "Skip /home/ntopng/db/{$ThisYear}/{$thisMonth}\n";
		$dirs=$unix->dirdir($directory);
		
		
		while (list ($scanneddir, $line) = each ($dirs)){
			
			$month=basename($scanneddir);
			if($month==$thisMonth){
				echo "Skip $thisMonth\n";
				continue;
			}
			
			echo "Remove $scanneddir\n";
			shell_exec("$rm -rf $scanneddir");
		}
			
		if($arrayConf["MAX_DAYS"]==30){return;}
	
		echo "/home/ntopng/db/{$ThisYear}/{$thisMonth}";
		$dirs=$unix->dirdir("/home/ntopng/db/{$ThisYear}/{$thisMonth}");
		if($dirs<$arrayConf["MAX_DAYS"]){return;}
		while (list ($scanneddir, $line) = each ($dirs)){
			$basename=basename($scanneddir);
			$T[$basename]=$scanneddir;
			
		}
		
		ksort($T);
		print_r($T);
			
		$CurrentDays=count($T);
		$Tokeep=$CurrentDays-$arrayConf["MAX_DAYS"];
		if($Tokeep<1){return;}
		
		echo "Keeping $Tokeep days\n";
		$c=0;
		while (list ($dir, $path) = each ($T)){
			echo "Remove $path\n";
			shell_exec("$rm -rf $path");
			$c++;
			if($c>=$Tokeep){break;}
		}
	}
	

	
}

