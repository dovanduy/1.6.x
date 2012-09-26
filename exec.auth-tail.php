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
if($unix->process_exists($oldpid,basename(__FILE__))){writelogs("Already running $oldpid, aborting","MAIN",__FILE__,__LINE__);events("Already running $oldpid, aborting ");die();}
events("running $pid update $pidfile....");
file_put_contents($pidfile,$pid);
$sock=new sockets();
$GLOBALS["COUNTLINES"]=1;
$EnableRemoteSyslogStatsAppliance=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableRemoteSyslogStatsAppliance"));
$DisableArticaProxyStatistics=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/DisableArticaProxyStatistics"));


if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
if(!is_numeric($EnableRemoteSyslogStatsAppliance)){$EnableRemoteSyslogStatsAppliance=0;}
$GLOBALS["EnableRemoteSyslogStatsAppliance"]=$EnableRemoteSyslogStatsAppliance;
$GLOBALS["DisableArticaProxyStatistics"]=$DisableArticaProxyStatistics;
events("SQUID ENGINE: EnableRemoteStatisticsAppliance = $EnableRemoteSyslogStatsAppliance");
if(is_file("/etc/artica-postfix/auth-tail-debug")){$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){events("waiting event in VERBOSE MODE....");}
@mkdir("/var/log/artica-postfix/squid-users",0755,true);
@mkdir("/var/log/artica-postfix/youtube",0755,true);
@mkdir('/var/log/artica-postfix/squid-userAgent');
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
	
	if(strpos($buffer," squid[")>0){
		if($GLOBALS["VERBOSE"]){events(" - > ". __LINE__);}
		$squid=new squid_tail();
		if($GLOBALS["VERBOSE"]){events(" - > ". __LINE__);}
		if($GLOBALS["EnableRemoteSyslogStatsAppliance"]==1){return;}
		try {
			if($GLOBALS["VERBOSE"]){events(" - > ". __LINE__);}
			if($squid->parse_tail($buffer)){
				if($GLOBALS["VERBOSE"]){events(" - > ". __LINE__);}
				return;
			}
		
		} catch (Exception $e) {events("Fatal error squid->parse_tail() ". $e->getMessage());}
		
	}

events("Not Filtered \"$buffer\"");

}

function events($text){
		$pid=@getmypid();
		$date=@date("h:i:s");
		$logFile="/var/log/artica-postfix/auth-tail.debug";
		
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		@fwrite($f, "$pid ".basename(__FILE__)." $text\n");
		@fclose($f);	
		}