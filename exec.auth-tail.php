<?php

include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.auth.tail.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.tail.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');

$GLOBALS["VERBOSE"]=false;
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$pid=getmypid();
$pidfile="/etc/artica-postfix/".basename(__FILE__).".pid";
$oldpid=@file_get_contents($pidfile);
$unix=new unix();
$php5=$unix->LOCATE_PHP5_BIN();
$nohup=$unix->find_program("nohup");
if($unix->process_exists($oldpid,basename(__FILE__))){writelogs("Already running $oldpid, aborting","MAIN",__FILE__,__LINE__);events("Already running $oldpid, aborting ");die();}
events("running $pid update $pidfile....");
file_put_contents($pidfile,$pid);
$sock=new sockets();
$GLOBALS["COUNTLINES"]=1;
$EnableRemoteSyslogStatsAppliance=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableRemoteSyslogStatsAppliance"));
$DisableArticaProxyStatistics=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/DisableArticaProxyStatistics"));
$EnableRemoteStatisticsAppliance=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/DisableArticaProxyStatistics"));
$ActAsASyslogServer=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/ActAsASyslogServer"));
$EnableKerbAuth=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableKerbAuth"));

$GLOBALS["CMDLINE_SQUIDBRUT"]="$nohup $php5 /usr/share/artica-postfix/exec.squid-tail-injector.php --brut --nolock >/dev/null 2>&1";
$GLOBALS["SQUID32"]=false;
if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
if(!is_numeric($EnableRemoteSyslogStatsAppliance)){$EnableRemoteSyslogStatsAppliance=0;}
if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
if(!is_numeric($ActAsASyslogServer)){$ActAsASyslogServer=0;}
if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
if($ActAsASyslogServer==1){$DisableArticaProxyStatistics=0;}

if(is_file("/etc/artica-postfix/PROXYTINY_APPLIANCE")){$DisableArticaProxyStatistics=1;}


$GLOBALS["EnableRemoteSyslogStatsAppliance"]=$EnableRemoteSyslogStatsAppliance;
$GLOBALS["DisableArticaProxyStatistics"]=$DisableArticaProxyStatistics;
$GLOBALS["EnableRemoteStatisticsAppliance"]=$EnableRemoteStatisticsAppliance;
events("SQUID ENGINE: EnableRemoteStatisticsAppliance = $EnableRemoteSyslogStatsAppliance");
if(is_file("/etc/artica-postfix/auth-tail-debug")){$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){events("waiting event in VERBOSE MODE....");}
@mkdir("/var/log/artica-postfix/squid-users",0755,true);
@mkdir("/var/log/artica-postfix/squid-brut",0777,true);
@mkdir("/var/log/artica-postfix/squid-reverse",0777,true);
@mkdir("/var/log/artica-postfix/youtube",0755,true);
@mkdir('/var/log/artica-postfix/squid-userAgent');

@chmod("/var/log/artica-postfix/squid-brut",0777);
@chmod("/var/log/artica-postfix/squid-reverse",0777);

$squidver=$unix->squid_version();
if(preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)#", $squidver,$re)){$SQUID_MAJOR=$re[1];$SQUID_MINOR=$re[2];}
if($SQUID_MAJOR>2){if($SQUID_MINOR>1){$GLOBALS["SQUID32"]=true;}}

$unix=new unix();
$oldpid=$unix->get_pid_from_file("/var/run/artica-auth-tail.pid");
if($unix->process_exists($oldpid,basename(__FILE__))){
	echo "Already process exists PID $oldpid\n";
	die();
}

@file_put_contents("/var/run/artica-auth-tail.pid", getmypid());




$pipe = fopen("php://stdin", "r");
while(!feof($pipe)){
	$buffer .= fgets($pipe, 4096);
	try {
		if($GLOBALS["VERBOSE"]){events(" - > `$buffer`");}
		Parseline($buffer);
	} catch (Exception $e) {
		events("Fatal error on buffer $buffer");
	}
	
	$buffer=null;
}

fclose($pipe);
events("Shutdown...");
die();



function Parseline($buffer){
	if(!isset($GLOBALS["SQUIDCOUNT"])){$GLOBALS["SQUIDCOUNT"]=0;}
	$buffer=trim($buffer);
	if($buffer==null){return null;}
	if($GLOBALS["VERBOSE"]){events(" - > ". __LINE__);}
	$auth=new auth_tail();
	if($GLOBALS["VERBOSE"]){events(" - > ". __LINE__);}
	
	$GLOBALS["COUNTLINES"]++;
	if($GLOBALS["COUNTLINES"]>20){
		@unlink("/etc/artica-postfix/pids/auth-tail.time");
		@file_put_contents("/etc/artica-postfix/pids/auth-tail.time", time());
		$GLOBALS["COUNTLINES"]=0;
	}
	if($auth->ParseLog($buffer)){return;}
	
	if($GLOBALS["SQUID32"]){return;}
	if($GLOBALS["EnableRemoteSyslogStatsAppliance"]==1){return;}
	if($GLOBALS["DisableArticaProxyStatistics"]==1){return;}
	if($GLOBALS["EnableRemoteStatisticsAppliance"]==1){return;}
	$datelog=date("Y-m-d-H");
	
	if(strpos($buffer," squid[")>0){return;}
	if(strpos($buffer," (squid-")>0){return;}
	if(strpos($buffer," (squid):")>0){return;}
	events("Not Filtered \"$buffer\" Line:".__LINE__);
	
	
	if(strpos($buffer," squid[")>0){
	$MD5Buffer=md5($buffer);
	
		@mkdir("/var/log/artica-postfix/squid-brut/$datelog",0755,true);
		$GLOBALS["SQUIDCOUNT"]=$GLOBALS["SQUIDCOUNT"]+1;
		if($GLOBALS["SQUIDCOUNT"]>1000){shell_exec($GLOBALS["CMDLINE_SQUIDBRUT"]);$GLOBALS["SQUIDCOUNT"]=0;}
		
		
				
		if(!is_dir("/var/log/artica-postfix/squid-brut/$datelog")){
			@file_put_contents("/var/log/artica-postfix/squid-brut/$MD5Buffer", $buffer);
			return;			
		}
		
		events("/var/log/artica-postfix/squid-brut/$datelog/$MD5Buffer Line:".__LINE__);
		@file_put_contents("/var/log/artica-postfix/squid-brut/$datelog/$MD5Buffer", $buffer);
		
		if(!is_file("/var/log/artica-postfix/squid-brut/$datelog/$MD5Buffer")){
			events("/var/log/artica-postfix/squid-brut/$datelog Permission denied Line:".__LINE__);
		}
		return;
	}
	
	
	if(strpos($buffer," (squid-")>0){
		
		$GLOBALS["SQUIDCOUNT"]=$GLOBALS["SQUIDCOUNT"]+1;
		if($GLOBALS["SQUIDCOUNT"]>1000){shell_exec($GLOBALS["CMDLINE_SQUIDBRUT"]);$GLOBALS["SQUIDCOUNT"]=0;}
		
		@mkdir("/var/log/artica-postfix/squid-brut/$datelog",0755,true);
		if(!is_dir("/var/log/artica-postfix/squid-brut/$datelog")){
			@file_put_contents("/var/log/artica-postfix/squid-brut/$MD5Buffer", $buffer);
			return;
		}
		
		
		@file_put_contents("/var/log/artica-postfix/squid-brut/$datelog/$MD5Buffer", $buffer);
		if(!is_file("/var/log/artica-postfix/squid-brut/$datelog/$MD5Buffer")){
			events("/var/log/artica-postfix/squid-brut/$datelog Permission denied Line:".__LINE__);
		}
				
		return;
	}
	
	if(strpos($buffer," (squid):")>0){
		$GLOBALS["SQUIDCOUNT"]=$GLOBALS["SQUIDCOUNT"]+1;
		if($GLOBALS["SQUIDCOUNT"]>1000){shell_exec($GLOBALS["CMDLINE_SQUIDBRUT"]);$GLOBALS["SQUIDCOUNT"]=0;}
		
		@mkdir("/var/log/artica-postfix/squid-brut/$datelog",0755,true);
		if(!is_dir("/var/log/artica-postfix/squid-brut/$datelog")){
			@file_put_contents("/var/log/artica-postfix/squid-brut/$MD5Buffer", $buffer);
			return;
		}
		
		
		@file_put_contents("/var/log/artica-postfix/squid-brut/$datelog/$MD5Buffer", $buffer);
		if(!is_file("/var/log/artica-postfix/squid-brut/$datelog/$MD5Buffer")){
			events("/var/log/artica-postfix/squid-brut/$datelog Permission denied Line:".__LINE__);
		}
		
		return;		
		
	}
	
	
	
	events("Not Filtered \"$buffer\" Line:".__LINE__);
	

}

function events($text){
		$pid=@getmypid();
		$date=@date("H:i:s");
		$logFile="/var/log/artica-postfix/auth-tail.debug";
		
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		@fwrite($f, "$pid ".basename(__FILE__)." $text\n");
		@fclose($f);	
		}