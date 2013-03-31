<?php

include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.auth.tail.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.tail.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');

$GLOBALS["VERBOSE"]=false;
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$pid=getmypid();
$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
@mkdir("/etc/artica-postfix/pids",0755,true);
$oldpid=@file_get_contents($pidfile);
$unix=new unix();
$GLOBALS["NOHUP"]=$unix->find_program("nohup");
$GLOBALS["PHP5"]=$unix->LOCATE_PHP5_BIN();
if($unix->process_exists($oldpid,basename(__FILE__))){writelogs("Already running $oldpid, aborting","MAIN",__FILE__,__LINE__);events("Already running $oldpid, aborting ");die();}
events("running $pid update $pidfile....");
file_put_contents($pidfile,$pid);
$sock=new sockets();
$GLOBALS["COUNTLINES"]=1;
$EnableRemoteSyslogStatsAppliance=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableRemoteSyslogStatsAppliance"));
$DisableArticaProxyStatistics=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/DisableArticaProxyStatistics"));
$EnableKavICAPRemote=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableKavICAPRemote"));
$kavicapserverEnabled=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableKavICAPRemote"));
if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
if(!is_numeric($EnableRemoteSyslogStatsAppliance)){$EnableRemoteSyslogStatsAppliance=0;}
if(!is_numeric($EnableKavICAPRemote)){$EnableKavICAPRemote=0;}
if(is_file("/etc/artica-postfix/PROXYTINY_APPLIANCE")){$DisableArticaProxyStatistics=1;}

$GLOBALS["EnableRemoteSyslogStatsAppliance"]=$EnableRemoteSyslogStatsAppliance;
$GLOBALS["DisableArticaProxyStatistics"]=$DisableArticaProxyStatistics;
$GLOBALS["EnableKavICAPRemote"]=$EnableKavICAPRemote;
events("SQUID ENGINE: EnableRemoteStatisticsAppliance = $EnableRemoteSyslogStatsAppliance");
if($GLOBALS["VERBOSE"]){events("waiting event in VERBOSE MODE....");}


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
	
	if(dustbin($buffer)){return null;}
	
	if($GLOBALS["VERBOSE"]){events(" - > ". __LINE__);}
	
	
	$GLOBALS["COUNTLINES"]++;
	if($GLOBALS["COUNTLINES"]>20){
		@unlink("/etc/artica-postfix/pids/squid-cache-logs.time");
		@file_put_contents("/etc/artica-postfix/pids/squid-cache-logs.time", time());
		$GLOBALS["COUNTLINES"]=0;
	}
	

	if(preg_match("#FATAL:\s+Failed to make swap directory\s+(.+?):.*?13.*?Permission denied#", $buffer,$re)){
		$dirname=trim($re[1]);
		$basename=basename($dirname);
		if(is_numeric($basename)){$dirname=dirname($dirname);}
		events("$dirname ($basename) Permisssions denied... Line:".__LINE__);
		return;
		
	}
	
	if(preg_match("#WARNING: Disk space over limit#", $buffer)){
		events("Disk space over limit Line: refresh swap.state".__LINE__);
		shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.watchdog.php --swapstate >/dev/null 2>&1 &");
		
	}
	
	
	if(preg_match("#\|\s+(.+?):\s+\(2\)\s+No such file or directory#", $buffer,$re)){
		if(strpos($buffer, "storeDirClean")==0){
			$dirname=trim($re[1]);
			events("$dirname No such file or directory... Line:".__LINE__);
			@mkdir("$dirname",0755);
			@chown($dirname, "squid");
			@chgrp($dirname, "squid");
			squid_admin_notifs("Missing directory $dirname\r\nsquid-cache claim\r\n$buffer\r\nArtica have automatically created this directory....", __FUNCTION__, __FILE__, __LINE__, "proxy");
		}
		return;
	}
	
	if(preg_match("#storeDirClean:\s+(.+?):\s+\(2\)\s+No such file or directory#", $buffer,$re)){
		$file="/etc/artica-postfix/pids/".md5("storeDirCleanNo such file or directory");
		$timefile=file_time_min($file);
		$dirname=trim($re[1]);
		events("$dirname No such file or directory... Line:".__LINE__);
		@mkdir("$dirname",0755);
		@chown($dirname, "squid");
		@chgrp($dirname, "squid");	
		if($timefile>5){	
			squid_admin_notifs("Suspicious removed cache $dirname\r\nsquid-cache claim\r\n$buffer\r\nIt seems that this cache directory was removed after the started service\r\nChecks that your have created your caches \"outside\" /var/cache/squid*\r\nr", __FUNCTION__, __FILE__, __LINE__, "proxy");
			@unlink($timefile);
			@file_put_contents($file, time());
		}
		return;
	}
	
	if(strpos($buffer,"Run 'squid -z' to create swap directories")){
		$file="/etc/artica-postfix/pids/".md5("Run 'squid -z' to create swap directories");
		$timefile=file_time_min($file);
		if($timefile>5){
			events("SQUID -Z !!!... Line:".__LINE__);
			squid_admin_notifs("Missing some caches directories\r\nsquid-cache claim\r\n$buffer\r\nArtica will launch the directory creation", __FUNCTION__, __FILE__, __LINE__, "proxy");
			shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.smp.php --squid-z-fly >/dev/null 2>&1 &");
			@unlink($timefile);
			@file_put_contents($file, time());
		}else{
			events("SQUID -Z !!!... TIMEOUT Line:".__LINE__);
		}
		return;
	}
	
	if(strpos($buffer,"| Reconfiguring Squid Cache")>0){
		$file="/etc/artica-postfix/pids/".md5("Reconfiguring Squid Cache");
		$timefile=file_time_min($file);
		if($timefile>1){
			events("Reconfiguring Squid Cache Line:".__LINE__);
			squid_admin_notifs("Reconfiguring Squid Cache done.\r\nsquid-cache was reseted with new configurations\r\n$buffer\r\n", __FUNCTION__, __FILE__, __LINE__, "proxy");
			@unlink($timefile);
			@file_put_contents($file, time());	
			
		}
		return;
	}
	
	if(strpos($buffer,"FATAL: Bungled squid.conf line")){
		events("Bad configuration file!".__LINE__);
		squid_admin_notifs("Bad configuration file!\r\nsquid-cache claim\r\n$buffer\r\nTry to run the configuration compilation on Artica or contact our support team...", __FUNCTION__, __FILE__, __LINE__, "proxy");
		@unlink($timefile);
		@file_put_contents($file, time());	
		return;
	}
	
	if(preg_match("#FATAL ERROR: cannot connect to ufdbguardd daemon socket: Connection timed out#",$buffer)){
		$file="/etc/artica-postfix/pids/".md5("FATAL:ufdbguardd daemon socket: Connection timed out");
		$timefile=file_time_min($file);
		if($timefile>5){
			events("FATAL: ufdbguardd daemon socket:timed out ".__LINE__);	
			squid_admin_notifs("Issue on Webfiltering Daemon!\r\nsquid-cache claim\r\n$buffer\r\nThe Webfiltering Dameon will disconnected from proxy service will be reloaded", __FUNCTION__, __FILE__, __LINE__, "proxy");
			@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableUfdbGuard",0);
			shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");
			shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/bin/artica-make APP_UFDBGUARD >/dev/null 2>&1 &");
			@unlink($timefile);
			@file_put_contents($file, time());	
			return;
		}	
		events("FATAL: ufdbguardd daemon socket:timed out".__LINE__);
		return;
		
	}
	
	
	
	if(preg_match("#FATAL: Received Segment Violation\.\.\.dying#",$buffer)){
		$file="/etc/artica-postfix/pids/".md5("FATAL: Received Segment Violation");
		$timefile=file_time_min($file);
		events("FATAL: Received Segment Violation".__LINE__);
		if($timefile>2){
			squid_admin_notifs("Proxy service was crashed!\r\nsquid-cache claim\r\n$buffer\r\nThe service will be restarted", __FUNCTION__, __FILE__, __LINE__, "proxy");
			@unlink($timefile);
			@file_put_contents($file, time());
			shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.watchdog.php --restart --force --". basename(__FILE__).".l.".__LINE__." >/dev/null 2>&1 &");
			
			return;
		}
		events("FATAL: Received Segment Violation -> timeout".__LINE__);
		return;
	}
	
	if(preg_match("#optional ICAP service is down after an options fetch failure:\s+icap:.*?1344\/av\/reqmod#",$buffer)){
		$file="/etc/artica-postfix/pids/".md5("KasperskyIcapDown");
		$timefile=file_time_min($file);

		if($timefile>2){
				port1344_notavailable();
				@unlink($timefile);
				@file_put_contents($file, time());
				shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.watchdog.php --restart --force --". basename(__FILE__).".l.".__LINE__." >/dev/null 2>&1 &");
				return;
			}
		events("ICAP service is down after an options (1344) -> timeout".__LINE__);
		return;
	}
	
	
	if(strpos($buffer,"Terminated abnormally")){
		$file="/etc/artica-postfix/pids/".md5("Terminated abnormally");
		$timefile=file_time_min($file);
		if($timefile>1){
			events("Terminated abnormally ".__LINE__);
			squid_admin_notifs("Squid Terminated abnormally\r\nsquid-cache claim\r\n$buffer\r\nThis just a notification, Artica will checks your settings and determine what is the issue...", __FUNCTION__, __FILE__, __LINE__, "proxy");
			@unlink($timefile);
			@file_put_contents($file, time());			
		}
		return;
	}
	events("Not Filtered \"$buffer\" Line:".__LINE__);
}

//squid_admin_notifs("Squid success to start PID $pid\n$executed", __FUNCTION__, __FILE__, __LINE__, "proxy");


function port1344_notavailable($buffer){
	$EnableRemoteSyslogStatsAppliance=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableRemoteSyslogStatsAppliance"));
	$DisableArticaProxyStatistics=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/DisableArticaProxyStatistics"));
	$EnableKavICAPRemote=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableKavICAPRemote"));
	$kavicapserverEnabled=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/kavicapserverEnabled"));
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	if(!is_numeric($EnableRemoteSyslogStatsAppliance)){$EnableRemoteSyslogStatsAppliance=0;}
	if(!is_numeric($EnableKavICAPRemote)){$EnableKavICAPRemote=0;}
	if(!is_numeric($kavicapserverEnabled)){$kavicapserverEnabled=0;}	
	$Disabled=false;
	if(!is_file("/opt/kaspersky/kav4proxy/sbin/kav4proxy-kavicapserver")){$Disabled=true;}
	if($EnableRemoteSyslogStatsAppliance==1){$Disabled=true;}
	if($EnableKavICAPRemote==1){$Disabled=true;}
	if($kavicapserverEnabled==0){$Disabled=true;}
	
	events("Warning, Kaspersky ICAP server is down (port 1344). Disabled = $Disabled".__LINE__);
	
	if($Disabled){
		squid_admin_notifs("Kaspersky ICAP service down!\nSquid-Cache claim\n$buffer\nBut it seems that the ICAP server is disabled...\nArtica will reconfigure the service", __FUNCTION__, __FILE__, __LINE__, "proxy");
		shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");
		return;
	}
	
	squid_admin_notifs("Kaspersky ICAP service down!\nSquid-Cache claim\n$buffer\nArtica will restart the Kaspersky ICAP server...\nArtica will reconfigure the service", __FUNCTION__, __FILE__, __LINE__, "proxy");
	shell_exec("{$GLOBALS["NOHUP"]} /etc/init.d/artica-postfix restart kav4proxy >/dev/null 2>&1 &");
	
	
	
	
}


function dustbin($buffer){
	if(strpos($buffer, "helperOpenServers")>1){return true;}
	if(strpos($buffer, "| Adding")>1){return true;}
	if(strpos($buffer, "| WARNING:")>1){return true;}
	if(strpos($buffer, "| Logfile:")>1){return true;}
	if(strpos($buffer, "| DNS Socket")>1){return true;}
	if(strpos($buffer, "| pinger:")>1){return true;}
	if(strpos($buffer, "| Accepting")>1){return true;}
	if(strpos($buffer, "| Startup:")>1){return true;}
	if(strpos($buffer, "| Starting")>1){return true;}
	if(strpos($buffer, "| Pinger socket opened")>1){return true;}
	if(strpos($buffer, "Initializing")>1){return true;}
	if(strpos($buffer, "Icons.")>1){return true;}
	if(strpos($buffer, "| Squid plugin")>1){return true;}
	if(strpos($buffer, "| Warning:")>1){return true;}
	if(strpos($buffer, "| Processing")>1){return true;}
	if(strpos($buffer, "| Adaptation support")>1){return true;}
	if(strpos($buffer, "| Shutdown:")>1){return true;}
	if(strpos($buffer, "descriptors available")>1){return true;}
	if(strpos($buffer, "| Local cache digest enabled")>1){return true;}
	if(strpos($buffer, "| Closing Pinger")>1){return true;}
	if(strpos($buffer, "Store buckets")>1){return true;}
	if(strpos($buffer, "| Max Swap size")>1){return true;}
	if(strpos($buffer, "Target number of buckets")>1){return true;}
	if(strpos($buffer, "storeLateRelease: released")>1){return true;}
	if(strpos($buffer, "| HTCP Disabled")>1){return true;}
	if(strpos($buffer, "Stop receiving")>1){return true;}
	if(strpos($buffer, "| Sending ICP messages")>1){return true;}
	if(strpos($buffer, "| Stop sending ICP from")>1){return true;}
	if(strpos($buffer, "| Unlinkd pipe opened")>1){return true;}
	if(strpos($buffer, "| Swap maxSize")>1){return true;}
	if(strpos($buffer, "| Max Mem")>1){return true;}
	if(strpos($buffer, "| Rebuilding storage")>1){return true;}
	if(strpos($buffer, "| Using Least")>1){return true;}
	if(strpos($buffer, "| Set Current Directory")>1){return true;}
	if(strpos($buffer, "| Done reading /")>1){return true;}
	if(strpos($buffer, "| Beginning Validation")>1){return true;}
	if(strpos($buffer, "Completed Validation")>1){return true;}
	if(strpos($buffer, "|   Validated")>1){return true;}
	if(strpos($buffer, "|   store_swap_size")>1){return true;}
	if(strpos($buffer, "Squid is already running")>1){return true;}
	if(strpos($buffer, "| Process Roles")>1){return true;}
	if(strpos($buffer, "mallinfo")>1){return true;}
	if(strpos($buffer, "| Process ID")>1){return true;}
	if(strpos($buffer, "| Closing HTTP port")>1){return true;}
	if(strpos($buffer, "| storeDirWriteCleanLogs: Starting")>1){return true;}
	if(strpos($buffer, "|   Finished.  Wrote")>1){return true;}
	if(strpos($buffer, "|   Took")>1){return true;}
	if(strpos($buffer, "| NOTE:")>1){return true;}
	if(strpos(" $buffer", "| Closing HTTP port")>1){return true;}
	if(strpos(" $buffer", "NETDB state saved")>1){return true;}
	if(strpos(" $buffer", "User-Agent:")>1){return true;}
	if(strpos(" $buffer", "Accept-Language:")>1){return true;}
	if(strpos(" $buffer", "Accept-Encoding:")>1){return true;}
	if(strpos(" $buffer", "Connection:")>1){return true;}
	if(strpos(" $buffer", "CPU Usage:")>1){return true;}
	if(strpos(" $buffer", "Entries scanned")>1){return true;}
	if(strpos(" $buffer", "Maximum Resident Size")>1){return true;}
	if(strpos(" $buffer", "With invalid flags")>1){return true;}
	if(strpos(" $buffer", "Objects loaded")>1){return true;}
	if(strpos(" $buffer", "Objects expired")>1){return true;}
	if(strpos(" $buffer", "Objects cancelled")>1){return true;}
	if(strpos(" $buffer", "Duplicate URLs purged")>1){return true;}
	if(strpos(" $buffer", "Swapfile clashes avoided")>1){return true;}
	if(strpos(" $buffer", "Sending HTCP messages from")>1){return true;}
	if(strpos(" $buffer", "Preparing for shutdown after")>1){return true;}
	if(strpos(" $buffer", "| Logfile Daemon")>1){return true;}
	if(strpos(" $buffer", "| ipcacheAddEntryFromHosts: Bad IP address")>1){return true;}
	if(strpos(" $buffer", "| Finished rebuilding storage from disk")>1){return true;}
	if(strpos(" $buffer", "| Disabling Authentication on port")>1){return true;}
	if(strpos(" $buffer", "| Logfile Daemon: closing log daemon")>1){return true;}
	if(strpos(" $buffer", "| Disabling IPv6 on port")>1){return true;}
}
function events($text){
	$pid=@getmypid();
	$date=@date("h:i:s");
	$logFile="/var/log/squid/cache.watchdog.log";

	$size=@filesize($logFile);
	if($size>1000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$pid ".basename(__FILE__)." $text\n");
	@fclose($f);
}
