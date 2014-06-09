<?php
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.http.pear.inc');
include_once(dirname(__FILE__).'/ressources/class.artica-meta.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
$GLOBALS["TITLENAME"]="vnStat daemon";
	if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	
	$system_is_overloaded=system_is_overloaded(basename(__FILE__));
	
	if($system_is_overloaded){
		writelogs("System is overloaded ({$GLOBALS["SYSTEM_INTERNAL_LOAD"]}), aborting...","MAIN",__FILE__,__LINE__);
		die();
	}	
	

if($argv[1]=='--build'){$GLOBALS["OUTPUT"]=true;build();exit;}
if($argv[1]=='--stats'){build_stats();exit;}

$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}




function restart() {
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	stop(true);
	sleep(1);
	start(true);

}


function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("vnstatd");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, vnstatd not installed\n";}
		return;
	}

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
	$EnableVnStat=$sock->GET_INFO("EnableVnStat");
	if(!is_numeric($EnableVnStat)){$EnableVnStat=0;}

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Enabled: $EnableVnStat\n";}
	if($EnableVnStat==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableVnStat)\n";}
		stop(true);
		return;
	}

	$php5=$unix->LOCATE_PHP5_BIN();
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");

	$CMDS[]="--daemon";
	$CMDS[]="--pidfile /var/run/vnstat.pid";
	
	$cmd="$Masterbin ".@implode(" ", $CMDS)." >/dev/null 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}

	shell_exec($cmd);




	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}

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




	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
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
	$pid=$unix->get_pid_from_file("/var/run/vnstat.pid");
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("vnstatd");
	return $unix->PIDOF($Masterbin);
}
	
function build(){
	$unix=new unix();
	$sock=new sockets();
	$EnableVnStat=$sock->GET_INFO("EnableVnStat");
	if(!is_numeric($EnableVnStat)){$EnableVnStat=0;}
	$vnstat=$unix->find_program("vnstat");

	if(!is_file($vnstat)){
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} VnStat Not installed\n";}
		return;
	}
	
	if(!is_file($EnableVnStat==0)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} VnStat is disabled ( see EnableVnStat )\n";}
		return;
	}

$conf[]="# vnStat 1.10 config file";
$conf[]="##";
$conf[]="";
$conf[]="# default interface";
$conf[]="Interface \"eth0\"";
$conf[]="";
$conf[]="# location of the database directory";
$conf[]="DatabaseDir \"/var/lib/vnstat\"";
$conf[]="";
$conf[]="# locale (LC_ALL) (\"-\" = use system locale)";
$conf[]="Locale \"-\"";
$conf[]="";
$conf[]="# on which day should months change";
$conf[]="MonthRotate 1";
$conf[]="";
$conf[]="# date output formats for -d, -m, -t and -w";
$conf[]="# see 'man date' for control codes";
$conf[]="DayFormat    \"%x\"";
$conf[]="MonthFormat  \"%b '%y\"";
$conf[]="TopFormat    \"%x\"";
$conf[]="";
$conf[]="# characters used for visuals";
$conf[]="RXCharacter       \"%\"";
$conf[]="TXCharacter       \":\"";
$conf[]="RXHourCharacter   \"r\"";
$conf[]="TXHourCharacter   \"t\"";
$conf[]="";
$conf[]="# how units are prefixed when traffic is shown";
$conf[]="# 0 = IEC standard prefixes (KiB/MiB/GiB/TiB)";
$conf[]="# 1 = old style binary prefixes (KB/MB/GB/TB)";
$conf[]="UnitMode 1";
$conf[]="";
$conf[]="# output style";
$conf[]="# 0 = minimal & narrow, 1 = bar column visible";
$conf[]="# 2 = same as 1 except rate in summary and weekly";
$conf[]="# 3 = rate column visible";
$conf[]="OutputStyle 3";
$conf[]="";
$conf[]="# used rate unit (0 = bytes, 1 = bits)";
$conf[]="RateUnit 1";
$conf[]="";
$conf[]="# maximum bandwidth (Mbit) for all interfaces, 0 = disable feature";
$conf[]="# (unless interface specific limit is given)";
$conf[]="MaxBandwidth 100";
$conf[]="";
$conf[]="# interface specific limits";
$conf[]="#  example 8Mbit limit for eth0 (remove # to activate):";
$conf[]="#MaxBWeth0 8";
$conf[]="";
$conf[]="# how many seconds should sampling for -tr take by default";
$conf[]="Sampletime 5";
$conf[]="";
$conf[]="# default query mode";
$conf[]="# 0 = normal, 1 = days, 2 = months, 3 = top10";
$conf[]="# 4 = dumpdb, 5 = short, 6 = weeks, 7 = hours";
$conf[]="QueryMode 0";
$conf[]="";
$conf[]="# filesystem disk space check (1 = enabled, 0 = disabled)";
$conf[]="CheckDiskSpace 1";
$conf[]="";
$conf[]="# database file locking (1 = enabled, 0 = disabled)";
$conf[]="UseFileLocking 1";
$conf[]="";
$conf[]="# how much the boot time can variate between updates (seconds)";
$conf[]="BootVariation 15";
$conf[]="";
$conf[]="# log days without traffic to daily list (1 = enabled, 0 = disabled)";
$conf[]="TrafficlessDays 1";
$conf[]="";
$conf[]="";
$conf[]="# vnstatd";
$conf[]="##";
$conf[]="";
$conf[]="# how often (in seconds) interface data is updated";
$conf[]="UpdateInterval 30";
$conf[]="";
$conf[]="# how often (in seconds) interface status changes are checked";
$conf[]="PollInterval 5";
$conf[]="";
$conf[]="# how often (in minutes) data is saved to file";
$conf[]="SaveInterval 5";
$conf[]="";
$conf[]="# how often (in minutes) data is saved when all interface are offline";
$conf[]="OfflineSaveInterval 30";
$conf[]="";
$conf[]="# force data save when interface status changes (1 = enabled, 0 = disabled)";
$conf[]="SaveOnStatusChange 1";
$conf[]="";
$conf[]="# enable / disable logging (0 = disabled, 1 = logfile, 2 = syslog)";
$conf[]="UseLogging 2";
$conf[]="";
$conf[]="# file used for logging if UseLogging is set to 1";
$conf[]="LogFile \"/var/log/vnstat.log\"";
$conf[]="";
$conf[]="# file used as daemon pid / lock file";
$conf[]="PidFile \"/var/run/vnstat.pid\"";
$conf[]="";
$conf[]="";
$conf[]="# vnstati";
$conf[]="##";
$conf[]="";
$conf[]="# title timestamp format";
$conf[]="HeaderFormat \"%x %H:%M\"";
$conf[]="";
$conf[]="# show hours with rate (1 = enabled, 0 = disabled)";
$conf[]="HourlyRate 1";
$conf[]="";
$conf[]="# show rate in summary (1 = enabled, 0 = disabled)";
$conf[]="SummaryRate 1";
$conf[]="";
$conf[]="# layout of summary (1 = with monthly, 0 = without monthly)";
$conf[]="SummaryLayout 1";
$conf[]="";
$conf[]="# transparent background (1 = enabled, 0 = disabled)";
$conf[]="TransparentBg 1";
$conf[]="";
$conf[]="# image colors";
$conf[]="CBackground     \"FFFFFF\"";
$conf[]="CEdge           \"AEAEAE\"";
$conf[]="CHeader         \"606060\"";
$conf[]="CHeaderTitle    \"FFFFFF\"";
$conf[]="CHeaderDate     \"FFFFFF\"";
$conf[]="CText           \"000000\"";
$conf[]="CLine           \"B0B0B0\"";
$conf[]="CLineL          \"-\"";
$conf[]="CRx             \"92CF00\"";
$conf[]="CTx             \"606060\"";
$conf[]="CRxD            \"-\"";
$conf[]="CTxD            \"-\"";
$conf[]="";
@file_put_contents("/etc/vnstat.conf",implode("\n",$conf));
if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} building configuration done.\n";}


$net=new networking();
$interfaces=$net->Local_interfaces();
while (list ($eth, $eth1) = each ($interfaces) ){
	echo "Starting......: ".date("H:i:s")." VnStat check $eth interface\n";
	shell_exec("vnstat -u -i $eth --nick \"$eth\"");
	
}



}


function build_stats(){
$unix=new unix();
$vnstat=$unix->find_program("vnstat");
$vnstati=$unix->find_program("vnstati");


$sock=new sockets();
$EnableVnStat=$sock->GET_INFO("EnableVnStat");
if(!is_numeric($EnableVnStat)){$EnableVnStat=0;}

if(!is_file($vnstat)){
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} VnStat Not installed\n";}
	return;
}

if(!is_file($EnableVnStat==0)){
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} VnStat is disabled ( see EnableVnStat )\n";}
	return;
}


$cmd="$vnstat -q";
if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
exec($cmd." 2>&1",$results);



while (list ($index, $num) = each ($results) ){
	if(preg_match("#([a-z0-9\:]+):(.*)#",$num,$re)){
		if(preg_match("#data available yet#",$re[2])){continue;}
		$interface=trim($re[1]);
		echo "Starting......: ".date("H:i:s")." VnStat check $interface\n";
		//resumer
		$cmdr[]="$vnstati --noheader -s -i \"$interface\" -c 15 -o /usr/share/artica-postfix/ressources/logs/vnstat-$interface-resume.png";
		$cmdr[]="$vnstati --noheader -h -i \"$interface\" -c 15 -o /usr/share/artica-postfix/ressources/logs/vnstat-$interface-hourly.png";
		$cmdr[]="$vnstati --noheader -d -i \"$interface\" -c 15 -o /usr/share/artica-postfix/ressources/logs/vnstat-$interface-daily.png";
		$cmdr[]="$vnstati --noheader -m -i \"$interface\" -c 15 -o /usr/share/artica-postfix/ressources/logs/vnstat-$interface-monthly.png";
		$cmdr[]="$vnstati --noheader -t -i \"$interface\" -c 15 -o /usr/share/artica-postfix/ressources/logs/vnstat-$interface-top.png";
		$nics[]=$interface;
		}
}

if(is_array($cmdr)){
	while (list ($index, $cmds) = each ($cmdr) ){
		if($GLOBALS["VERBOSE"]){echo "\n\n$cmds\n";}
		exec($cmds,$results);
		if($GLOBALS["VERBOSE"]){while (list ($a, $b) = each ($results) ){echo "Starting......: ".date("H:i:s")." VnStat $b\n";}}
		
	}
	
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/vnstat-array.db",serialize($nics));
	shell_exec("/bin/chmod 770 /usr/share/artica-postfix/ressources/logs/vnstat-*");
}
	
}
?>