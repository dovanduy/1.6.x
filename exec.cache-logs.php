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
$GLOBALS["CHMOD"]=$unix->find_program("chmod");
$GLOBALS["CHOWN"]=$unix->find_program("chown");
$GLOBALS["UMOUNT"]=$unix->find_program("umount");
$GLOBALS["DF"]=$unix->find_program("df");
$GLOBALS["SQUIDBIN"]=$unix->LOCATE_SQUID_BIN();
$GLOBALS["CLASS_UNIX"]=$unix;
$GLOBALS["CLASS_SOCKETS"]=new sockets();
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
$GLOBALS["MonitConfig"]=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidWatchdogMonitConfig")));
$UfdbguardSMTPNotifs=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/UfdbguardSMTPNotifs")));
if(!isset($UfdbguardSMTPNotifs["ALLOW_RETURN_1CPU"])){$UfdbguardSMTPNotifs["ALLOW_RETURN_1CPU"]=1;}
if(!is_numeric($UfdbguardSMTPNotifs["ALLOW_RETURN_1CPU"])){$UfdbguardSMTPNotifs["ALLOW_RETURN_1CPU"]=1;}
if(!is_numeric($GLOBALS["MonitConfig"]["NotifyDNSIssues"])){$GLOBALS["MonitConfig"]["NotifyDNSIssues"]=0;}
if(!is_numeric($GLOBALS["MonitConfig"]["DNSIssuesMAX"])){$GLOBALS["MonitConfig"]["DNSIssuesMAX"]=1;}
if($GLOBALS["MonitConfig"]["DNSIssuesMAX"]==0){$GLOBALS["MonitConfig"]["DNSIssuesMAX"]=1;}
$GLOBAL["DNSISSUES"]=array();

$GLOBALS["EnableRemoteSyslogStatsAppliance"]=$EnableRemoteSyslogStatsAppliance;
$GLOBALS["DisableArticaProxyStatistics"]=$DisableArticaProxyStatistics;
$GLOBALS["EnableKavICAPRemote"]=$EnableKavICAPRemote;
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
events("Shutdown daemon...");
die();


function Parseline($buffer){
	$buffer=trim($buffer);
	if($buffer==null){return null;}
	if(dustbin($buffer)){return null;}
	if(!isset($GLOBALS["CHMOD"])){$unix=new unix();$GLOBALS["CHMOD"]=$unix->find_program("chmod");}
	
	if($GLOBALS["VERBOSE"]){events(" - > ". __LINE__);}
	
	if(count($GLOBALS["DNSISSUES"])>1){$curdate=date("YmdHi");$curcount=count($GLOBALS["DNSISSUES"][$curdate]);unset($GLOBALS["DNSISSUES"]);$GLOBALS["DNSISSUES"][$curdate]=$curcount;}
	
	$GLOBALS["COUNTLINES"]++;
	if($GLOBALS["COUNTLINES"]>20){
		@unlink("/etc/artica-postfix/pids/squid-cache-logs.time");
		@file_put_contents("/etc/artica-postfix/pids/squid-cache-logs.time", time());
		$GLOBALS["COUNTLINES"]=0;
	}
	
	
	if(preg_match("#ipcacheParse: No Address records in response to '(.+?)'#", $buffer,$re)){
		if($GLOBALS["MonitConfig"]["NotifyDNSIssues"]==0){reset($GLOBAL["DNSISSUES"]);return;}
		$curdate=date("YmdHi");
		$GLOBALS["DNSISSUES"][$curdate][$re[1]]=true;
		if(count($GLOBALS["DNSISSUES"][$curdate]+1)>$GLOBALS["MonitConfig"]["DNSIssuesMAX"]){
			while (list ($num, $ligne) = each ($GLOBALS["DNSISSUES"][$curdate]) ){$t[]=$num;}
			reset($GLOBALS["DNSISSUES"]);
			squid_admin_notifs("Warning, ". count($t)." DNS issues.\nProxy service have issues to Resolve these websites::\n".@implode("\n", $t)."",__FUNCTION__,__FILE__,__LINE__,"watchdog");
		}
		return;
	}
	
	
	
	if(preg_match("#commBind: Cannot bind socket FD.*?Address already in use#", $buffer)){
		if(TimeStampTTL(__LINE__,5)){
			squid_admin_notifs("Warning, Bind Socket issue.\n$buffer\nProxy service have issues to bind port\n$buffer\nArtica will restart the proxy service",__FUNCTION__,__FILE__,__LINE__,"watchdog");
			shell_exec("/etc/init.d/squid restart");
		}
		return;
	}	
	
	
	if(preg_match("#FATAL: kid[0-9]+ registration timed out#", $buffer)){
		if(TimeStampTTL(__LINE__,5)){
			@file_put_contents("/etc/artica-postfix/settings/Daemons/DisableSquidSNMPMode", 1);
			squid_admin_notifs("Warning, Processor issue.\n$buffer\nSMP is disabled and return back to 1 CPU",__FUNCTION__,__FILE__,__LINE__,"watchdog");
			shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");
		}
		return;
	}
	

	
	
	if(preg_match("#abandoning local=(.*?):.*?remote=(.*?):#", $buffer,$re)){
		$client=$re[2];
		$hostname=gethostbyaddr($re[2]);
		events("$client [$hostname] KeepAlive session was disconnected from this user Line:".__LINE__);
		return;
	}
	
	
	if(preg_match("#ERROR:\s+(.+?)\/00: \(2\) No such file or directory#", $buffer,$re)){
		if(TimeStampTTL(__LINE__,2)){
			$dirname=trim($re[1]);
			events("$dirname -> no cache created -> squid-z Line:".__LINE__);
			shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.smp.php --squid-z-fly >/dev/null 2>&1 &");
			return;
		}
	}
	

	if(preg_match("#FATAL:\s+Failed to make swap directory\s+(.+?):.*?13.*?Permission denied#", $buffer,$re)){
		$dirname=trim($re[1]);
		$basename=basename($dirname);
		if(is_numeric($basename)){$dirname=dirname($dirname);}
		events("$dirname -> squid:squid {$re[1]}/0755 Permisssions... Line:".__LINE__);
		@chown($dirname, "squid");
		@chgrp($dirname, "squid");
		@chmod($re[1], 0755);
		events("$dirname ($basename) Permisssions denied... Line:".__LINE__);
		return;
		
	}
	
	
	if(preg_match("#\/var\/cache\/MemBooster([0-9]+)\/.*?No space left on device#",$buffer,$re)){
		events("No space left on Memory Booster MemBooster{$re[1]}: line:".__LINE__);
		if(TimeStampTTL(__LINE__.$re[1],3)){
			squid_admin_notifs("Warning, Memory cache, full\nThe cache memory MemBooster({$re[1]}) will be flushed to 0 and the proxy service will be restarted",__FUNCTION__,__FILE__,__LINE__,"watchdog");
			shell_exec("{$GLOBALS["UMOUNT"]} -l /var/cache/MemBooster{$re[1]}");
			shell_exec("{$GLOBALS["NOHUP"]} /etc/init.d/squid restart --force 2>&1");
		}
		return;
	}
	
	if(preg_match("#FATAL: Write failure.*?check your disk space#", $buffer)){
		events("FATAL: Write failure: Disk space over limit (cannot determine which path) Line:".__LINE__);
		if(TimeStampTTL(__LINE__,10)){
			exec("{$GLOBALS["DF"]} -h 2>&1",$defres);
			squid_admin_notifs("Warning, check your disk space for Proxy cache service.\nHere the status of your storage system:".@implode("\n", $defres),__FUNCTION__,__FILE__,__LINE__,"watchdog"); 
			
		}
		return;
	}
	
	
	if(preg_match("#WARNING: Disk space over limit#", $buffer)){
		if(TimeStampTTL(__LINE__,5)){
			events("Disk space over limit Line: refresh swap.state".__LINE__);
			shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.watchdog.php --swapstate >/dev/null 2>&1 &");
		}
		return;
	}
	
	if(preg_match("#errorTryLoadText: '(.+?)':\s+\(13\) Permission denied#i", $buffer,$re)){
		if(!is_file($re[1])){@file_put_contents($re[1], "\n");}
		@chmod($re[1], 0777);
		@chown($re[1], "squid");
		@chown(dirname($re[1]), "squid");
		return;
		
	}
	
	if(preg_match("#Squid Cache.*?Terminated abnormally#", $buffer)){
		if(TimeStampTTL(__LINE__,5)){
			squid_admin_notifs("Squid Cache Terminated abnormally.\n$buffer\nProxy service have issues\n$buffer\nArtica will restart the proxy service",__FUNCTION__,__FILE__,__LINE__,"watchdog");
			shell_exec("/etc/init.d/squid restart");
		
		}
		
		return;		
		
	}
	
	if(preg_match("#WARNING: external ACL.*?queue overload. Request rejected#i", $buffer,$re)){
		$file="/etc/artica-postfix/pids/squid.external.ACL.queue.overload";
		$timefile=file_time_min($file);
		if($timefile>3){
			$SquidClientParams=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientParams")));
			if(!is_numeric($SquidClientParams["external_acl_children"])){$SquidClientParams["external_acl_children"]=5;}
			if(!is_numeric($SquidClientParams["external_acl_startup"])){$SquidClientParams["external_acl_startup"]=1;}
			if(!is_numeric($SquidClientParams["external_acl_idle"])){$SquidClientParams["external_acl_idle"]=1;}
			$SquidClientParams["external_acl_children"]=$SquidClientParams["external_acl_children"]+1;
			$SquidClientParams["external_acl_startup"]=$SquidClientParams["external_acl_startup"]+1;
			$SquidClientParams["external_acl_idle"]=$SquidClientParams["external_acl_idle"]+1;
			$GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(base64_encode(serialize($SquidClientParams)), "SquidClientParams");
			shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");
			events("external ACL (queue overload) Increase: external_acl_children:{$SquidClientParams["external_acl_children"]},external_acl_startup:{$SquidClientParams["external_acl_startup"]}, external_acl_idle:{$SquidClientParams["external_acl_idle"]} Line:".__LINE__);
			
			$text="$buffer
			New parameters has been set and the proxy service was reconfigurer with:
			external_acl_children = {$SquidClientParams["external_acl_children"]}
			external_acl_startup  = {$SquidClientParams["external_acl_startup"]}
			external_acl_idle     = {$SquidClientParams["external_acl_idle"]}
			";
				
			squid_admin_notifs($text,__FUNCTION__,__FILE__,__LINE__,"watchdog");
			@unlink($timefile);
			@file_put_contents($file, time());
			return;
		}
		events("external ACL (queue overload) timeout ({$timefile}mn) line:".__LINE__);
		return;
		
	}
			
	
	if(preg_match("#kid[0-9]+.*?ERROR opening swap log\s+(.+?)\/swap\.state: \(13\) Permission denied#",$buffer,$re)){
		$dirname=$re[1];
		$file="/etc/artica-postfix/pids/opening.swap.state.".md5($dirname);
		events("$dirname ERROR opening swap log... Line:".__LINE__);
		$timefile=file_time_min($file);
		if($timefile>3){
			swapstate($dirname);
			if(!isset($GLOBALS["CHMOD"])){$unix=new unix();$GLOBALS["CHMOD"]=$unix->find_program("chmod");}
			if(!isset($GLOBALS["CHOWN"])){$unix=new unix();$GLOBALS["CHOWN"]=$unix->find_program("chown");}
			if(!isset($GLOBALS["SQUIDBIN"])){$unix=new unix();$GLOBALS["SQUIDBIN"]=$unix->LOCATE_SQUID_BIN();}
			$cmd="{$GLOBALS["NOHUP"]} {$GLOBALS["CHMOD"]} -R 0755 $dirname >/dev/null 2>&1 &";
			shell_exec($cmd);
			events("$cmd".__LINE__);
			$cmd="{$GLOBALS["NOHUP"]} {$GLOBALS["CHOWN"]} -R squid:squid $dirname && {$GLOBALS["SQUIDBIN"]} -k reconfigure >/dev/null 2>&1 &";
			events("$cmd".__LINE__);
			shell_exec($cmd);			
			@unlink($timefile);
			@file_put_contents($file, time());			
		}else{
			events("$dirname Timeout {$timefile}Mn need to wait 3mn Line chown just swap.state :".__LINE__);
			swapstate($dirname);		
			
			if(!isset($GLOBALS["SQUIDBIN"])){$unix=new unix();$GLOBALS["SQUIDBIN"]=$unix->LOCATE_SQUID_BIN();}
			$cmd="{$GLOBALS["SQUIDBIN"]} -k reconfigure >/dev/null 2>&1 &";
			events("$cmd".__LINE__);
			shell_exec($cmd);			
				
		}
		
		return;
	}
	
	if(preg_match("#ERROR opening swap log (.+?)\/swap\.state: \(13\) Permission denied#",$buffer,$re)){
		$dirname=$re[1];
		$file="/etc/artica-postfix/pids/opening.swap.state.".md5($dirname);
		events("$dirname ERROR opening swap log... Line:".__LINE__);
		$timefile=file_time_min($file);
		if($timefile>3){
			swapstate($dirname);
			if(!isset($GLOBALS["CHMOD"])){$unix=new unix();$GLOBALS["CHMOD"]=$unix->find_program("chmod");}
			if(!isset($GLOBALS["CHOWN"])){$unix=new unix();$GLOBALS["CHOWN"]=$unix->find_program("chown");}
			if(!isset($GLOBALS["SQUIDBIN"])){$unix=new unix();$GLOBALS["SQUIDBIN"]=$unix->LOCATE_SQUID_BIN();}
			$cmd="{$GLOBALS["NOHUP"]} {$GLOBALS["CHMOD"]} -R 0775 $dirname >/dev/null 2>&1 &";
			shell_exec($cmd);
			events("$cmd".__LINE__);
			$cmd="{$GLOBALS["NOHUP"]} {$GLOBALS["CHOWN"]} -R squid:squid $dirname && {$GLOBALS["SQUIDBIN"]} -k reconfigure >/dev/null 2>&1 &";
			events("$cmd".__LINE__);
			shell_exec($cmd);
			@unlink($timefile);
			@file_put_contents($file, time());
		}else{
			events("$dirname Timeout {$timefile}Mn need to wait 3mn Line:".__LINE__);
			swapstate($dirname);
				
		}
	
		return;
	}	
	
	if(preg_match("#kid[0-9]+.*?ERROR:\s+(.+):\s+\(2\)\s+No such file or directory#i", $buffer,$re)){
		$dir=$re[1];
		$dirname=dirname($dir);
		$file="/etc/artica-postfix/pids/squid.cache.path.".md5($dirname);
		$timefile=file_time_min($file);
		events("$dirname No such file or directory... Line:".__LINE__);
		if($timefile>3){
			squid_admin_notifs("Missing directory $dirname\r\nsquid-cache claim\r\n$buffer\r\nArtica have automatically created this directory....", __FUNCTION__, __FILE__, __LINE__, "proxy");
			shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.smp.php --squid-z-fly >/dev/null 2>&1 &");
			@unlink($timefile);
			@file_put_contents($file, time());
		}
		
		return;
	}
	
	if(strpos($buffer, "| Store rebuilding is")>0){
		events("Store rebuilding is: refresh swap.state".__LINE__);
		if(TimeStampTTL(__LINE__,10)){
			events("Store rebuilding : refresh swap.state".__LINE__);
			shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.watchdog.php --swapstate >/dev/null 2>&1 &");
			
		}
		return;
	}
	
	
	if(preg_match("#\|\s+(.+?):\s+\(2\)\s+No such file or directory#", $buffer,$re)){
		if(strpos($buffer, "storeDirClean")==0){
			$dirname=trim($re[1]);
			$file="/etc/artica-postfix/pids/squid.cache.path.".md5($dirname);
			$timefile=file_time_min($file);
			events("$dirname No such file or directory... Line:".__LINE__);
			if($timefile>3){
				//@mkdir("$dirname",0755);
				//@chown($dirname, "squid");
				//@chgrp($dirname, "squid");
				squid_admin_notifs("Missing directory $dirname\r\nsquid-cache claim\r\n$buffer\r\nArtica have automatically created this directory....", __FUNCTION__, __FILE__, __LINE__, "proxy");
				shell_exec("{$GLOBALS["NOHUP"]} /etc/init.d/squid restart >/dev/null 2>&1 &");
				shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.smp.php --squid-z-fly >/dev/null 2>&1 &");
				@unlink($timefile);
				@file_put_contents($file, time());			
			}
		}
		return;
	}
	
	if(preg_match("#storeDirClean:\s+(.+?):\s+\(2\)\s+No such file or directory#", $buffer,$re)){
		$file="/etc/artica-postfix/pids/".md5("storeDirCleanNo::NoSuchFileOrDirectory");
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
	
	
	if(preg_match("#DiskThreadsDiskFile::openDone:.*?No such file or directory#", $buffer,$re)){
		$file="/etc/artica-postfix/pids/".md5("DiskThreadsDiskFile::openDone:NoSuchFileOrDirectory");
		$timefile=file_time_min($file);
		
		if($timefile>5){
			events("DiskThreadsDiskFile Missing data in caches => SQUID Z!! Line:".__LINE__);
			squid_admin_notifs("Missing Caches !!\r\nsquid-cache claim\r\n$buffer\r\nIt seems that caches directory was removed after the started service\r\nArtica start the procedure to verify caches..\r\nr", __FUNCTION__, __FILE__, __LINE__, "proxy");
			@unlink($timefile);
			@file_put_contents($file, time());
			shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.smp.php --squid-z-fly >/dev/null 2>&1 &");
		}else{
			events("Fatal: DiskThreadsDiskFile -> SQUID -Z !!!... TIMEOUT Line:".__LINE__);
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
		events("FATAL: ufdbguardd daemon socket:timed out ".__LINE__);
		return;
		
	}
	
	
	if(preg_match("#:\s+(.+?):\s+\(13\)\s+Permission denied#",$buffer,$re)){
		@chown($re[1],"squid");
		@chgrp($re[1],"squid");
		events("Add squid:squid permission on `{$re[1]}` ".__LINE__);
		if(preg_match("#pinger$#", $re[1])){
			events("Add chmod 04755 on `{$re[1]}` ".__LINE__);
			shell_exec("{$GLOBALS["CHMOD"]} 04755 {$re[1]}");
		}
		return;
	}
	
	
	
	if(preg_match("#FATAL: Received Segment Violation\.\.\.dying#",$buffer)){
		$file="/etc/artica-postfix/pids/".md5("FATAL: Received Segment Violation");
		$timefile=file_time_min($file);
		events("FATAL: Received Segment Violation ".__LINE__);
		if($timefile>2){
			events("Restarting squid-cache service with `exec.squid.watchdog.php --restart --force` Line:".__LINE__);
			squid_admin_notifs("Proxy service was crashed!\r\nsquid-cache claim\r\n$buffer\r\nThe service will be restarted", __FUNCTION__, __FILE__, __LINE__, "proxy");
			@unlink($timefile);
			@file_put_contents($file, time());
			shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.watchdog.php --restart --force --". basename(__FILE__).".l.".__LINE__." >/dev/null 2>&1 &");
			return;
		}
		events("FATAL: Received Segment Violation -> timeout {$timefile}mn, require up to 2mn Line:".__LINE__);
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
		events("ICAP service is down after an options (1344) -> timeout Line:".__LINE__);
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

function TimeStampTTL($line,$mins){
	$filename="/etc/artica-postfix/pids/".basename(__FILE__).".$line.time";
	$unix=new unix();
	$Time=$GLOBALS["CLASS_UNIX"]->file_time_min($filename);
	if($Time<$mins){
		events("TTL = {$Time}mn, need to wait at least {$mins}mn Line:".__LINE__);
		return false;
	}
	
	@unlink($filename);
	@file_put_contents($filename, time());
	return true;
}

function swapstate($dirname){
	if(is_file("$dirname/swap.state")){
		events("Apply permissions on $dirname/swap.state Line:".__LINE__);
		@chown("$dirname/swap.state", "squid");
		@chgrp("$dirname/swap.state", "squid");
		@chmod("$dirname/swap.state", 0775);
	}
	
	if(is_file("$dirname/swap.state.last-clean")){
		events("Apply permissions on $dirname/swap.state.last-clean Line:".__LINE__);
		@chown("$dirname/swap.state.last-clean", "squid");
		@chgrp("$dirname/swap.state.last-clean", "squid");
		@chmod("$dirname/swap.state.last-clean", 0775);
	}	
	
}


function dustbin($buffer){
	$buffer="    $buffer";
	// Page faults with physical i/o: 0
	if(strpos($buffer, "Open FD UNSTARTED")>1){return true;}
	if(strpos($buffer, "Using certificate")>1){return true;}
	if(strpos($buffer, "ACL is used but there is no HTTP reply")>1){return true;}
	if(strpos($buffer, "helperStatefulOpenServers: No")>1){return true;}
	if(strpos($buffer, "UFSSwapDir::openLog: Failed to open swap log")>1){return true;}
	if(strpos($buffer, "Waiting 30 seconds for active connections")>1){return true;}
	if(strpos($buffer, "Done scanning /")>1){return true;}
	if(strpos($buffer, "Closing HTTPS port")>1){return true;}
	if(strpos($buffer, "ipcacheParse: No Address records in response")>1){return true;}
	if(strpos($buffer, "Store rebuilding is: refresh swap.state")>1){return true;}
	if(strpos($buffer, "Store rebuilding : refresh swap.")>1){return true;}
	if(strpos($buffer, "Done scanning")>1){return true;}
	if(strpos($buffer, "Invalid entri")>1){return true;}
	if(strpos($buffer, "Maximum Resident Size")>1){return true;}
	if(strpos($buffer, "CPU Usage")>1){return true;}
	if(strpos($buffer, "Ordinary blocks")>1){return true;}
	if(strpos($buffer, "CPU Usage")>1){return true;}
	if(strpos($buffer, "Maximum Resident Size")>1){return true;}
	if(strpos($buffer, "total space in arena")>1){return true;}
	if(strpos($buffer, "Small blocks")>1){return true;}
	if(strpos($buffer, "Holding blocks")>1){return true;}
	if(strpos($buffer, "Free Small blocks")>1){return true;}
	if(strpos($buffer, "Free Ordinary blocks")>1){return true;}
	if(strpos($buffer, "Total in use")>1){return true;}
	if(strpos($buffer, "Total free")>1){return true;}
	if(strpos($buffer, "icmp_sock: (1) Operation not permitted")>1){return true;}
	if(strpos($buffer, "FATAL: pinger: Unable to open any ICMP sockets")>1){return true;}
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
	if(strpos(" $buffer", "recv: (111) Connection refused")>1){return true;}
	if(strpos(" $buffer", "INFO: ")>1){return true;}
	if(strpos(" $buffer", "diskHandleWrite: FD")>1){return true;}
	if(strpos(" $buffer", "calling free_func for 0x")>1){return true;}
	if(strpos(" $buffer", "avoid write on theFile")>1){return true;}
	if(strpos(" $buffer", "| Making directories in /")>1){return true;}
	if(strpos(" $buffer", "faults with physical i/o")>1){return true;}
	if(strpos(" $buffer", "| Configuring Parent")>1){return true;}
	if(strpos(" $buffer", "getsockopt(SO_ORIGINAL_DST)")>1){return true;}
	if(strpos(" $buffer", "xrename: Cannot rename")>1){return true;}
}
function events($text){
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}
		
	}	
	
	$unix=new unix();
	$unix->events($text,"/var/log/squid.watchdog.log",false,$sourcefunction,$sourceline);
}
