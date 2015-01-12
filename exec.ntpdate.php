<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.artica.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.ini.inc');
include_once('ressources/class.ntpd.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');
$GLOBALS["CHECKS"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["JUST_PING"]=false;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["WRITEPROGRESS"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--checks#",implode(" ",$argv))){$GLOBALS["CHECKS"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}

xtstart();

function xtstart(){
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidtimeNTP="/etc/artica-postfix/pids/exec.squid.watchdog.php.start_watchdog.ntp.time";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "ReStarting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	$sock=new sockets();
	$NtpdateAD=intval($sock->GET_INFO("NtpdateAD"));
	$NTPDClientEnabled=intval($sock->GET_INFO("NTPDClientEnabled"));
	if($NtpdateAD==1){$NTPDClientEnabled=1;}
	if($NTPDClientEnabled==0){return;}
	
	$NTPDClientPool=intval($sock->GET_INFO("NTPDClientPool"));
	if($NTPDClientPool==0){$NTPDClientPool=120;}
	$pidtimeNTPT=$unix->file_time_min($pidtimeNTP);
	
	if(!$GLOBALS["FORCE"]){
		if($pidtimeNTPT<$NTPDClientPool){return;}
	}
	
	@unlink($pidtimeNTP);
	@file_put_contents($pidtimeNTP, time());
	
	if($NtpdateAD==1){
		
		$nohup=$unix->find_program("nohup");
		$php=$unix->LOCATE_PHP5_BIN();
		shell_exec("$nohup $php /usr/share/artica-postfix/exec.kerbauth.php --ntpdate >/dev/null 2>&1 &");
		return;
	}
	
	
	
	
	
	$ntpdate=$unix->find_program("ntpdate");
	$q=new mysql();
	
	$sql="SELECT * FROM ntpd_servers ORDER BY `ntpd_servers`.`order` ASC";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(mysql_num_rows($results)==0){
		$ntp=new ntpd();
		$ntp->builddefaults_servers();
		$results=$q->QUERY_SQL($sql,"artica_backup");
	}
	

	
	
	if(!$q->ok){echo "$q->mysql_error<br>";}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$serv=trim($ligne["ntp_servers"]);
		if($serv==null){continue;}
		$serv2=explode(" ",$serv);
		if(count($serv2)>1){
		$f[]=$serv2[0];
		}else{
			$f[]=$ligne["ntp_servers"];
		}
		
	}
	
	if(count($f)==0){return;}
	$SERVERS=@implode(" ", $f);
	
	exec("$ntpdate -v $SERVERS 2>&1",$results);
	
	
	while (list ($num, $text) = each ($results) ){
		$unix->ToSyslog($text,false,"ntpd");
		
	}
	
	$hwclock=$unix->find_program("hwclock");
	if(is_file($hwclock)){
		$unix->ToSyslog("sync the Hardware time with $hwclock",false,"ntpd");
		shell_exec("$hwclock --systohc");
	}
	
}


