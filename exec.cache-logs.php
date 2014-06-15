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
$pid=@file_get_contents($pidfile);
$unix=new unix();
$GLOBALS["WEBFISSUE"]=0;
$GLOBALS["WEBPROCISSUE"]=0;
$GLOBALS["NOHUP"]=$unix->find_program("nohup");
$GLOBALS["GREP"]=$unix->find_program("grep");
$GLOBALS["TAIL"]=$unix->find_program("tail");
$GLOBALS["PHP5"]=$unix->LOCATE_PHP5_BIN();
$GLOBALS["CHMOD"]=$unix->find_program("chmod");
$GLOBALS["CHOWN"]=$unix->find_program("chown");
$GLOBALS["UMOUNT"]=$unix->find_program("umount");
$GLOBALS["SCRIPT_SUFFIX"]="--script=".basename(__FILE__);

$GLOBALS["RM"]=$unix->find_program("rm");
$GLOBALS["DF"]=$unix->find_program("df");
$GLOBALS["IFCONFIG"]=$unix->find_program("ifconfig");
$GLOBALS["SQUIDBIN"]=$unix->LOCATE_SQUID_BIN();
$GLOBALS["CLASS_UNIX"]=$unix;
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if($unix->process_exists($pid,basename(__FILE__))){writelogs("Already running $pid, aborting","MAIN",__FILE__,__LINE__);events("Already running $pid, aborting ");die();}
$GLOBALS["HAARP_PORT"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaarpPort");
$GLOBALS["HAARP_ENABLE"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableHaarp");
$GLOBALS["sslcrtd_program"] = $unix->squid_locate_generic_bin("ssl_crtd");
if(!is_numeric($GLOBALS["HAARP_PORT"])){$GLOBALS["HAARP_PORT"]=0;}
if($GLOBALS["HAARP_PORT"]==0){$GLOBALS["HAARP_PORT"]=rand(35000, 64000);$GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaarpPort", $GLOBALS["HAARP_PORT"]);}


events("HAARP_ENABLE =  {$GLOBALS["HAARP_ENABLE"]}");
events("HAARP_PORT   =  {$GLOBALS["HAARP_PORT"]}");
events("Running $pid update $pidfile....");
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

if(!isset($GLOBALS["MonitConfig"]["ALLOW_RETURN_1CPU"])){$GLOBALS["MonitConfig"]["ALLOW_RETURN_1CPU"]=1;}
if(!isset($GLOBALS["MonitConfig"]["DisableWebFilteringNetFailed"])){$GLOBALS["MonitConfig"]["DisableWebFilteringNetFailed"]=1;}
if(!is_numeric($GLOBALS["MonitConfig"]["ALLOW_RETURN_1CPU"])){$GLOBALS["MonitConfig"]["ALLOW_RETURN_1CPU"]=1;}
if(!is_numeric($GLOBALS["MonitConfig"]["DisableWebFilteringNetFailed"])){$GLOBALS["MonitConfig"]["DisableWebFilteringNetFailed"]=1;}
if(!is_numeric($GLOBALS["MonitConfig"]["NotifyDNSIssues"])){$GLOBALS["MonitConfig"]["NotifyDNSIssues"]=0;}
if(!is_numeric($GLOBALS["MonitConfig"]["DNSIssuesMAX"])){$GLOBALS["MonitConfig"]["DNSIssuesMAX"]=1;}
if(!is_numeric($GLOBALS["MonitConfig"]["RestartWhenCrashes"])){$GLOBALS["MonitConfig"]["RestartWhenCrashes"]=1;}
if(!is_numeric($GLOBALS["MonitConfig"]["WEBPROCISSUE"])){$GLOBALS["MonitConfig"]["WEBPROCISSUE"]=3;}
if(!is_numeric($GLOBALS["MonitConfig"]["watchdog"])){$GLOBALS["MonitConfig"]["watchdog"]=1;}
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
	
	if(preg_match("#FD [0-9]+ Closing HTTP connection#", $buffer)){return;}
	if(preg_match("#temporary disabling.*?digest from#", $buffer)){return;}
	if(preg_match("#kid[0-9]+\| .*?\/[0-9]+ exists#", $buffer)){return;}
	
	
	
//*******************************************************************************************************************
if(preg_match("#Squid Cache.*?:\s+Exiting normally#",$buffer,$re)){
	if(TimeStampTTL(__LINE__,1)){
		squid_admin_mysql(2,"Proxy service was normally stopped","Proxy claim\n$buffer\n",__FILE__,__LINE__);
	}
	return;
}
//*******************************************************************************************************************	
if(preg_match("#FATAL: Received Bus Error...dying#",$buffer,$re)){
	if(TimeStampTTL(__LINE__,5)){
		squid_admin_mysql(0,"Bus Error !","Proxy claim\n$buffer\nThis caused by an hardware issue or a proxy bug ( please contact our support team)\nArtica will try to start Proxy if it is not running",__FILE__,__LINE__);
		shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.watchdog.php --start {$GLOBALS["SCRIPT_SUFFIX"]} >/dev/null 2>&1 &");
	}
	return;	
}
//*******************************************************************************************************************	
if(preg_match("#FATAL: Unable to open HTTPS Socket#",$buffer,$re)){
	if(TimeStampTTL(__LINE__,5)){
		squid_admin_mysql(0,"Port conflict issue on HTTPS socket -> Restart proxy service","$buffer\n",__FILE__,__LINE__);
		shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.watchdog.php --restart --force {$GLOBALS["SCRIPT_SUFFIX"]} >/dev/null 2>&1 &");
	}
	return;	
}
//*******************************************************************************************************************
if(preg_match("#FATAL: The basicauthenticator helpers are crashing too rapidly, need help#",$buffer,$re)){
	if(TimeStampTTL(__LINE__,2)){
		$text[]=$buffer;
		$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
		while (list ($num, $line) = each ($f)){if(preg_match("#auth_param basic program#", $line)){ $text[]="basicauthenticator: $line"; }	}
		squid_admin_mysql(0,"basicauthenticator extension is crashing",@implode("\n", $text),__FILE__,__LINE__);
	}
	return;	
}	
//*******************************************************************************************************************	
if(preg_match("#ERROR: URL-rewrite produces invalid request:#",$buffer,$re)){
	if(TimeStampTTL(__LINE__,5)){
		squid_admin_mysql(0,"Redirector Web filter miss-configured, reconfigure Web filter","$buffer\n",__FILE__,__LINE__);
		shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squidguard.php --build --force --restart {$GLOBALS["SCRIPT_SUFFIX"]} >/dev/null 2>&1 &");
	}
	return;	
}	
//*******************************************************************************************************************	
if(preg_match("#assertion failed: mem\.cc:([0-9]+):\s+\"(.+?)\"#",$buffer,$re)){
	if(TimeStampTTL(__LINE__,1)){
		squid_admin_mysql(1,"Memory assertion failed mem.cc Line {$re[1]} - {$re[2]}","$buffer\n",__FILE__,__LINE__);
	}
	return;	
}	
//*******************************************************************************************************************	
if(preg_match("#optional ICAP service is down after an options fetch failure:\s+icap:\/\/(.+?):([0-9]+)\/(.+?)\s+#",$buffer,$re)){
	if(TimeStampTTL(__LINE__,2)){
		squid_admin_mysql(1,"ICAP service {$re[3]} down {$re[1]}:{$re[2]}","$buffer\n",__FILE__,__LINE__);
	}
	return;	
}	
//*******************************************************************************************************************	
if(preg_match("#logfileHandleWrite: daemon:: error writing\s+(.*)#",$buffer,$re)){
	squid_admin_mysql(2,"Error writing to Artica statistics dameon","$buffer\n",__FILE__,__LINE__);
	return;	
}
//*******************************************************************************************************************
if(preg_match("#kid([0-9]+)\|\s+Reconfiguring Squid Cache#",$buffer,$re)){
	events("CPU.{$re[1]} Reconfiguring Proxy service",__LINE__);
	squid_admin_mysql(2,"CPU.{$re[1]} Reconfiguring Proxy service","$buffer\n",__FILE__,__LINE__);
}
//*******************************************************************************************************************
if(preg_match("#WARNING: Unexpected change of authentication scheme.*?client\s+(.*?)\)#",$buffer,$re)){
	events("{$re[1]} Unexpected change of authentication scheme",__LINE__);
	squid_admin_mysql(1,"Computer {$re[1]} Unexpected change of authentication scheme","$buffer\n",__FILE__,__LINE__);
	return;
}
//*******************************************************************************************************************	
if(preg_match("#WARNING: All [0-9]+\/([0-9]+) redirector processes are busy#",$buffer,$re)){
	events("{$re[1]} Process reached for Web filtering daemon, Artica will increase the value...",__LINE__);
	squid_admin_mysql(2,"{$re[1]} Process reached for Web filtering daemon, Artica will increase the value","$buffer\n",__FILE__,__LINE__);
	shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.watchdog.php --redirectors-more >/dev/null 2>&1 &");
	return;
}	
//*******************************************************************************************************************	
if(preg_match("#\| WARNING: HTTP header contains NULL characters \{Host:\s+(.+)#i", $buffer,$re)){
	events("{$re[1]} seems to crash",__LINE__);
	squid_admin_mysql(0,"Webserver \"{$re[1]}\" seems to crashing","script of this source server seems broken\n$buffer\n",__FILE__,__LINE__);
	return;
}	
// *******************************************************************************************************************	
if(preg_match("#squidaio_queue_request: WARNING - Queue congestion#i", $buffer)){
	events("squidaio_queue_request error issue ->`$buffer`",__LINE__);
	if(TimeStampTTL(__LINE__,2)){
		squid_admin_mysql(2,"Queue congestion","$buffer\n".@file_get_contents("/usr/share/artica-postfix/ressources/databases/SQUID_ERROR_QUEUE_CONGESTION.db"),__FILE__,__LINE__);
	}
	return;
}
// *******************************************************************************************************************	
	if(preg_match("#WARNING: Consider increasing the number of ads_group processes in your config file#i", $buffer)){
		events("ads_group error issue ->`$buffer`",__LINE__);
		if(TimeStampTTL(125,2)){
			squid_admin_mysql(0,"Queue overload on external ACL ads_group add more.","$buffer\n",__FILE__,__LINE__);
			shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.watchdog.php --external-acl-children-more >/dev/null 2>&1 &");
		}	
		return;	
	}
// *******************************************************************************************************************	
	if(preg_match("#WARNING: external ACL 'ads_group' queue overload. Using stale result#i", $buffer)){
			events("ads_group error issue ->`$buffer`",__LINE__);
			if(TimeStampTTL(125,2)){
				squid_admin_mysql(0,"Queue overload on external ACL ads_group add more.","$buffer\n",__FILE__,__LINE__);
				shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.watchdog.php --external-acl-children-more >/dev/null 2>&1 &");
			}
	}
// *******************************************************************************************************************	
	if(preg_match("#PHP Startup: Unable to load dynamic library#i", $buffer)){
		shell_exec("/usr/share/artica-postfix/bin/artica-install --php-include");
		shell_exec("{$GLOBALS["NOHUP"]} /etc/init.d/squid reload --cache-logs >/dev/null 2>&1");
	}
	
// *******************************************************************************************************************	
	if(preg_match("#errorTryLoadText:\s+'(.+?)':.*?Permission denied#",$buffer,$re)){
		@chown($re[1],"squid");
		return;
	}
// *******************************************************************************************************************	
	if(preg_match("#PHP Warning.*?Unable to load dynamic library '(.+?)'.*?undefined symbol#",$buffer,$re)){
		if(is_file($re[1])){
			@copy($re[1], $re[1].".".time().".bak");
			@unlink($re[1]);
			shell_exec("{$GLOBALS["NOHUP"]} /etc/init.d/artica-webconsole restart >/dev/null 2>&1");
		}
		return;
	}
	
// *******************************************************************************************************************	
	if(preg_match("#FATAL: Rock cache_dir at\s+(.+?)\s+failed to open db file#",$buffer,$re)){
		events("disk write error issue ->`$buffer`",__LINE__);
		if(TimeStampTTL(__LINE__,10)){
			squid_admin_mysql(0,"Rock cache_dir issue","$buffer\nrun cache -Z",__FILE__,__LINE__);
			shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.smp.php --squid-z-fly >/dev/null 2>&1 &");
		}
		return;
	}

// *******************************************************************************************************************	
	if(preg_match("#basic_ldap_auth: WARNING, could not bind to binddn.*?Can.*?contact LDAP server#",$buffer,$re)){
		events("basic_ldap_auth error issue ->`$buffer`",__LINE__);
		if(TimeStampTTL(__LINE__,2)){
			squid_admin_mysql(0,"Remote LDAP server issue unable to contact specified LDAP Port","$buffer\n",__FILE__,__LINE__);
		}
		return;
	}
	
// *******************************************************************************************************************	
	if(preg_match("#diskHandleWrite:\s+FD\s+.*?disk write error:.*?No space left on device#",$buffer,$re)){
		events("disk write error issue ->`$buffer`",__LINE__);
		if(TimeStampTTL(__LINE__,10)){
			squid_admin_mysql(0,"No space left on device, rebuild all caches","$buffer\nIt seems the one of caches or all caches handle your disk space.\nReconstruct caches procedure as been executed.",__FILE__,__LINE__);
			shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.rebuild.caches.php >/dev/null 2>&1 &");
		}
		return;
	}
	
// *******************************************************************************************************************	
	if(preg_match("#commBind:\s+Cannot bind socket FD\s+[0-9]+\s+to.*?(::1|127\.0\.0\.1)\]:.*?Cannot assign requested address#",$buffer,$re)){
		if(TimeStampTTL(__LINE__,1)){
			events("127.0.0.1 issue",__LINE__);
			squid_admin_mysql(0,"Loopback interface issue - {$re[1]}","$buffer\nArtica will reconfigure loopback");
			shell_exec("{$GLOBALS["IFCONFIG"]} lo 127.0.0.1 netmask 255.255.255.0 up >/dev/null 2>&1");
			shell_exec("/etc/init.d/squid reload {$GLOBALS["SCRIPT_SUFFIX"]}");
			return;
		}			
	}
// *******************************************************************************************************************	
	if(preg_match("#Uninitialized SSL certificate database directory:\s+(.+?)\.#",$buffer,$re)){
		if(TimeStampTTL(__LINE__,1)){
			$Directory=$re[1];
			events("SSL issue on `$Directory`",__LINE__);
			squid_admin_mysql(0,"Uninitialized SSL certificate database directory","$buffer\nArtica will initalize `$Directory` with `{$GLOBALS["sslcrtd_program"]}`");
			shell_exec("{$GLOBALS["RM"]} -rf $Directory >/dev/null 2>&1");
			shell_exec("{$GLOBALS["sslcrtd_program"]} -c -s $Directory >/dev/null 2>&1");
			shell_exec("{$GLOBALS["CHOWN"]} -R squid:squid $Directory >/dev/null 2>&1");
			shell_exec("{$GLOBALS["NOHUP"]} /etc/init.d/squid start --cache-logs >/dev/null 2>&1 &");
			return;
		}
	}
// *******************************************************************************************************************	
	if(preg_match("#FATAL: Ipc::Mem::Segment::open failed to shm_open.*?squid-squid-page-pool\.shm.*?No such file or directory#",$buffer)){
		if(TimeStampTTL(__LINE__,1)){
			events("Ipc::Mem::Segment:: issue on squid-squid-page-pool.shm -> restart".__LINE__);
			squid_admin_mysql(0,"SMP Memory issue","$buffer\nThe proxy service will be restarted");
			squid_admin_notifs("Warning, SMP Memory issue.\n$buffer\nThe proxy service will be restarted",__FUNCTION__,__FILE__,__LINE__,"watchdog");
			shell_exec("{$GLOBALS["NOHUP"]} /etc/init.d/squid restart --cache-logs >/dev/null 2>&1 &");
		}
		events("Ipc::Mem::Segment:: issue on squid-squid-page-pool.shm need to wait 1mn".__LINE__);
	}
// *******************************************************************************************************************	
	if(preg_match("#TCP connection to\s+127\.0\.0\.1\/{$GLOBALS["HAARP_PORT"]}\s+failed#",$buffer)){
		events("HTTP connection failed to Haarp cache system times Line:".__LINE__);
		if(TimeStampTTL(__LINE__,3)){
			@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableHaarp", 0);
			shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --build >/dev/null 2>&1 &");
			squid_admin_mysql(0,"Haarp issues","Proxy service have issues with haarp,disable it\n$buffer\n the Haarp service will be restarted");
			squid_admin_notifs("Warning, Haarp issues.\nProxy service have issues with haarp,\n$buffer\n the Haarp service will be restarted");
			shell_exec("{$GLOBALS["NOHUP"]} /etc/init.d/haarp restart >/dev/null 2>&1 &");
		}
		return;
	}
	
	// *******************************************************************************************************************	
	if(preg_match("#Detected DEAD Parent.*?HaarpPeer#",$buffer)){
		events("HTTP connection failed to Haarp cache system times Line:".__LINE__);
		if(TimeStampTTL(__LINE__,3)){
			@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableHaarp", 0);
			shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --build >/dev/null 2>&1 &");
			squid_admin_mysql(0,"Haarp issues","Proxy service have issues with haarp,service will be disabled\n$buffer\n the Haarp service will be restarted");
			squid_admin_notifs("Warning, Haarp issues.\nProxy service have issues with haarp,\n$buffer\n the service will be restarted");
			shell_exec("{$GLOBALS["NOHUP"]} /etc/init.d/haarp restart >/dev/null 2>&1 &");
		}
		return;
	}
	
// *******************************************************************************************************************	

	if(preg_match("#cannot connect to ufdbguardd daemon socket#",$buffer,$re )){
		if($GLOBALS["MonitConfig"]["watchdog"]==0){return;}
		if($GLOBALS["MonitConfig"]["DisableWebFilteringNetFailed"]==0){return;}
		if(TimeStampTTL(__LINE__,5)){
			$GLOBALS["WEBFISSUE"]++;
			squid_admin_mysql(0,"Web filtering issue","$buffer\nWeb filtering will be disabled");
			squid_admin_notifs("Web filtering issue.\n$buffer\nWeb filtering will be disabled",__FUNCTION__,__FILE__,__LINE__,"watchdog");
			events("Web filtering issue $buffer Line:".__LINE__);
			if($GLOBALS["WEBFISSUE"]>2){
				shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --disableUFDB >/dev/null 2>&1 &");
				$GLOBALS["WEBFISSUE"]=0;
			}
		}
		return; 
	}
	
	// *******************************************************************************************************************	
	if(preg_match("#ERROR: URL-rewrite produces invalid request#",$buffer,$re )){
		if($GLOBALS["MonitConfig"]["watchdog"]==0){return;}
		if($GLOBALS["MonitConfig"]["DisableWebFilteringNetFailed"]==0){return;}
		if(TimeStampTTL(__LINE__,5)){
			squid_admin_mysql(0,"Web filtering compatiblity issue","Proxy claim:\n$buffer\nWeb filtering will be disabled for compatibilities issues\nreturn back to 3.3x versions\nWe currently investigate on the compatibility");
			squid_admin_notifs("Web filtering compatiblity issue.\n$buffer\nProxy claim:\n$buffer\nWeb filtering will be disabled for compatibilities issues\nreturn back to 3.3x versions\nWe currently investigate on the compatibility",__FUNCTION__,__FILE__,__LINE__,"watchdog");
			events("Web filtering issue $buffer Line:".__LINE__);
			shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --disableUFDB >/dev/null 2>&1 &");
		}
		return; 
	}
	
	// *******************************************************************************************************************	
	if(preg_match("#TCP connection to (.+?)\/([0-9]+)\s+failed#",$buffer,$re )){
		if(!isset($GLOBALS["CNXFAILED"][$re[1]])){$GLOBALS["CNXFAILED"][$re[1]]=0;}
		$GLOBALS["CNXFAILED"][$re[1]]=$GLOBALS["CNXFAILED"][$re[1]]+1;
		events("{$re[1]} HTTP connection failed ({$GLOBALS["CNXFAILED"][$re[1]]}) times Line:".__LINE__);
	}
// *******************************************************************************************************************	
	if(preg_match("#ipcacheParse: No Address records in response to '(.+?)'#", $buffer,$re)){
		if($GLOBALS["MonitConfig"]["NotifyDNSIssues"]==0){reset($GLOBAL["DNSISSUES"]);return;}
		$curdate=date("YmdHi");
		$GLOBALS["DNSISSUES"][$curdate][$re[1]]=true;
		if(count($GLOBALS["DNSISSUES"][$curdate]+1)>$GLOBALS["MonitConfig"]["DNSIssuesMAX"]){
			while (list ($num, $ligne) = each ($GLOBALS["DNSISSUES"][$curdate]) ){$t[]=$num;}
			reset($GLOBALS["DNSISSUES"]);
			$report=NETWORK_REPORT();
			squid_admin_mysql(1,"DNS issues","Proxy service have issues to Resolve these websites::\n".@implode("\n", $t)."\n$report");
			squid_admin_notifs("Warning, ". count($t)." DNS issues.\nProxy service have issues to Resolve these websites::\n".@implode("\n", $t)."\n$report",__FUNCTION__,__FILE__,__LINE__,"watchdog");
		}
		return;
	}
// *******************************************************************************************************************	
	if(preg_match("#commBind: Cannot bind socket FD.*?Address already in use#", $buffer)){
		if(TimeStampTTL(__LINE__,5)){
			squid_admin_mysql(0,"Bind Socket issue","$buffer\nProxy service have issues to bind port\n$buffer\nArtica will restart the proxy service");
			squid_admin_notifs("Warning, Bind Socket issue.\n$buffer\nProxy service have issues to bind port\n$buffer\nArtica will restart the proxy service",__FUNCTION__,__FILE__,__LINE__,"watchdog");
			shell_exec("{$GLOBALS["NOHUP"]} /etc/init.d/squid start --crashed >/dev/null 2>&1 &");
		}
		return;
	}	
	// *******************************************************************************************************************	
	
	if(preg_match("#FATAL: kid[0-9]+ registration timed out#", $buffer)){
		$GLOBALS["WEBPROCISSUE"]++;
		squid_admin_mysql(0,"Warning, Processor issue count:{$GLOBALS["WEBPROCISSUE"]} (max {$GLOBALS["MonitConfig"]["WEBPROCISSUE"]} times)",$buffer,__FILE__,__LINE__);

		if(TimeStampTTL(__LINE__,2)){
			squid_admin_mysql(0,"Processor issue!!: SMP is disabled, you should consider return back to 1 CPU","The detected error was:\n$buffer\n",__FILE__,__LINE__);
				
		}
		return;
	}
	// *******************************************************************************************************************	

if(preg_match("#abandoning local=(.*?):.*?remote=(.*?):#", $buffer,$re)){
		$client=$re[2];
		$hostname=gethostbyaddr($re[2]);
		events("$client [$hostname] KeepAlive session was disconnected from this user Line:".__LINE__);
		return;
	}
	
//*******************************************************************************************************************	
	if(preg_match("#ERROR:\s+(.+?)\/00: \(2\) No such file or directory#", $buffer,$re)){
		if(TimeStampTTL(__LINE__,2)){
			$dirname=trim($re[1]);
			events("$dirname -> no cache created -> squid-z Line:".__LINE__);
			shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.smp.php --squid-z-fly >/dev/null 2>&1 &");
			return;
		}
	}
// *******************************************************************************************************************	

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
// *******************************************************************************************************************	
	
	if(preg_match("#\/home\/squid\/cache\/MemBooster([0-9]+)\/.*?No space left on device#",$buffer,$re)){
		events("No space left on Memory Booster MemBooster{$re[1]}: line:".__LINE__);
		if(TimeStampTTL(__LINE__.$re[1],3)){
			squid_admin_mysql(1,"Memory cache full","The cache memory MemBooster({$re[1]}) will be flushed to 0 and the proxy service will be restarted");
			squid_admin_notifs("Warning, Memory cache, full\nThe cache memory MemBooster({$re[1]}) will be flushed to 0 and the proxy service will be restarted",__FUNCTION__,__FILE__,__LINE__,"watchdog");
			shell_exec("{$GLOBALS["UMOUNT"]} -l /home/squid/cache/MemBooster{$re[1]}");
			shell_exec("{$GLOBALS["NOHUP"]} /etc/init.d/squid restart --force --cache-logs 2>&1");
		}
		return;
	}
// *******************************************************************************************************************	
	if(preg_match("#FATAL: Write failure.*?check your disk space#", $buffer)){
		events("Fatal: Write failure: Disk space over limit (cannot determine which path) Line:".__LINE__);
		if(TimeStampTTL(__LINE__,10)){
			exec("{$GLOBALS["DF"]} -h 2>&1",$defres);
			squid_admin_mysql(0,"Write failure - disk space issue","check your disk space for Proxy cache service.\nHere the status of your storage system:".@implode("\n", $defres));
			squid_admin_notifs("Warning, check your disk space for Proxy cache service.\nHere the status of your storage system:".@implode("\n", $defres),__FUNCTION__,__FILE__,__LINE__,"watchdog"); 
			
		}
		return;
	}
	
// *******************************************************************************************************************	

	
	
	if(preg_match("#WARNING: Disk space over limit#", $buffer)){
		if(TimeStampTTL(__LINE__,5)){
			squid_admin_mysql(1,"Disk space over limit","$buffer\nswapstate will be executed");
			events("Disk space over limit Line: refresh swap.state".__LINE__);
			shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.watchdog.php --swapstate >/dev/null 2>&1 &");
		}
		return;
	}
// *******************************************************************************************************************	
	if(preg_match("#errorTryLoadText: '(.+?)':\s+\(13\) Permission denied#i", $buffer,$re)){
		if(!is_file($re[1])){@file_put_contents($re[1], "\n");}
		@chmod($re[1], 0777);
		@chown($re[1], "squid");
		@chown(dirname($re[1]), "squid");
		return;
		
	}
// *******************************************************************************************************************	
	if(preg_match("#Squid Cache.*?Terminated abnormally#", $buffer)){
		
		if($GLOBALS["MonitConfig"]["RestartWhenCrashes"]==0){
			exec("{$GLOBALS["TAIL"]} -n 50 /var/log/squid/cache.log 2>&1",$results);
			squid_admin_mysql(0,"Squid Cache Terminated Abnormally","squid-cache claim\r\n$buffer\r\nThis just a notification, Artica will checks your settings and determine what is the issue...\n".@implode("\n", $results),__FILE__,__LINE__);
			squid_admin_notifs("Squid Cache Terminated Abnormally.\r\nsquid-cache claim\r\n$buffer\r\nThis just a notification, Artica will checks your settings and determine what is the issue...", __FUNCTION__, __FILE__, __LINE__, "proxy");
			return;
		}
		
		
		if(TimeStampTTL(__LINE__,5)){
			exec("{$GLOBALS["TAIL"]} -n 50 /var/log/squid/cache.log 2>&1",$results);
			squid_admin_mysql(1,"Squid Cache Terminated Abnormally","squid-cache claim\r\n$buffer\r\nArtica will restart the proxy service.\nPiece of logs:".@implode("\n", $results));
			squid_admin_notifs("Squid Cache Terminated abnormally.\n$buffer\nProxy service have issues\n$buffer\nArtica will start the proxy service\nPiece of logs:".@implode("\n", $results),__FUNCTION__,__FILE__,__LINE__,"watchdog");
			shell_exec("{$GLOBALS["NOHUP"]} /etc/init.d/squid start --crashed >/dev/null 2>&1 &");
		}
		
		return;		
		
	}
// *******************************************************************************************************************	
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
			squid_admin_mysql(1,"external ACL queue overload","$text");
			@unlink($file);
			@file_put_contents($file, time());
			return;
		}
		events("external ACL (queue overload) timeout ({$timefile}mn) line:".__LINE__);
		return;
		
	}
// *******************************************************************************************************************			
	
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
			squid_admin_mysql(1, "Permission denied: Reconfiguring squid-cache","Permission as been set to $dirname");
			squid_admin_mysql(1, "Reconfiguring proxy service",null,__FILE__,__LINE__);
			$cmd="{$GLOBALS["NOHUP"]} {$GLOBALS["CHOWN"]} -R squid:squid $dirname >/dev/null 2>&1 &";
			shell_exec("/etc/init.d/squid reload {$GLOBALS["SCRIPT_SUFFIX"]}");
			events("$cmd".__LINE__);
			shell_exec($cmd);			
			@unlink($file);
			@file_put_contents($file, time());			
		}else{
			events("$dirname Timeout {$timefile}Mn need to wait 3mn Line chown just swap.state :".__LINE__);
			swapstate($dirname);		
			
			if(!isset($GLOBALS["SQUIDBIN"])){$unix=new unix();$GLOBALS["SQUIDBIN"]=$unix->LOCATE_SQUID_BIN();}
			squid_admin_mysql(1, "Permission denied: Reconfiguring squid-cache","Permission as been set to $dirname");
			squid_admin_mysql(1, "Reconfiguring proxy service",null,__FILE__,__LINE__);
			$cmd="/etc/init.d/squid reload {$GLOBALS["SCRIPT_SUFFIX"]} >/dev/null 2>&1 &";
			events("$cmd".__LINE__);
			shell_exec($cmd);			
				
		}
		
		return;
	}
// *******************************************************************************************************************	
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
			squid_admin_mysql(1, "Permission denied: Reconfiguring squid-cache","Permission as been set to $dirname");
			$cmd="{$GLOBALS["NOHUP"]} {$GLOBALS["CHOWN"]} -R squid:squid $dirname && /etc/init.d/squid reload {$GLOBALS["SCRIPT_SUFFIX"]} >/dev/null 2>&1 &";
			events("$cmd".__LINE__);
			shell_exec($cmd);
			@unlink($file);
			@file_put_contents($file, time());
			
		}else{
			events("$dirname Timeout {$timefile}Mn need to wait 3mn Line:".__LINE__);
			swapstate($dirname);
				
		}
	
		return;
	}	
// *******************************************************************************************************************	
	if(preg_match("#Detected DEAD Parent:\s+(.+)#", $buffer,$re)){
		events("DEAD Parent {$re[1]} -> exec.squid.watchdog.php --dead-parent {$re[1]}".__LINE__);
		shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.watchdog.php --dead-parent \"{$re[1]}\" >/dev/null 2>&1 &");
		return;
	}
// *******************************************************************************************************************	
	if(preg_match("#kid[0-9]+.*?ERROR:\s+(.+):\s+\(2\)\s+No such file or directory#i", $buffer,$re)){
		$dir=$re[1];
		if(strpos(" $dir", "/")==0){return;}
		$dirname=dirname($dir);
		$file="/etc/artica-postfix/pids/squid.cache.path.".md5($dirname);
		$fileMail="/etc/artica-postfix/pids/squid.miss_dir";
		$timefile=file_time_min($file);
		events("$dirname No such file or directory... Line:".__LINE__);
		if($timefile>10){
			$timefile=file_time_min($fileMail);
			if($timefile>10){
				squid_admin_mysql(1,"Missing directory $dirname","squid-cache claim\r\n$buffer\r\nArtica have automatically created this directory....");
				squid_admin_notifs("Missing directory $dirname\r\nsquid-cache claim\r\n$buffer\r\nArtica have automatically created this directory....", __FUNCTION__, __FILE__, __LINE__, "proxy");
				@unlink($fileMail);
				@file_put_contents($fileMail, time());
			}
			shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.smp.php --squid-z-fly >/dev/null 2>&1 &");
			@unlink($file);
			@file_put_contents($file, time());
		}
		
		return;
	}
// *******************************************************************************************************************	
	if(preg_match("#Preparing for shutdown after\s+([0-9]+)\s+requests#",$buffer,$re)){
		squid_admin_mysql(2,"Proxy will be stopped after {$re[1]} requests","$buffer");
		return;
	}
// *******************************************************************************************************************	
	if(preg_match("#Store rebuilding is\s+([0-9\.,]+)#",$buffer,$re)){
		if($re[1]=="0.00"){return;}
		squid_admin_mysql(2,"Store rebuilding {$re[1]}%","$buffer");
		$intval=intval($re[1]);
		if($intval>100){
			$file="/etc/artica-postfix/pids/Squid.Store.rebuilding";
			$timefile=file_time_min($file);
			if($timefile>10){
				squid_admin_mysql(0,"Store rebuilding task {$re[1]}% is over 100% - Reset caches","$buffer");
				shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.rebuild.caches.php --reindex >/dev/null 2>&1 &");
				@unlink($file);
				@file_put_contents($file, time());
				return;
			}
		}
		events("Store rebuilding is: {$re[1]}% = $intval swap.state [do nothing] line:".__LINE__);
		return;
	}
// *******************************************************************************************************************	
	if(preg_match("#commBind: Cannot bind socket FD [0-9]+ to\s+(.*?)\s+\([0-9]+\) No such file or directory#", $buffer,$re)){
		if(TimeStampTTL(__LINE__,2)){
			squid_admin_mysql(1,"Cannot bind socket to {$re[1]}","$buffer",__FILE__,__LINE__);
		}
		return;
	}
	
// *******************************************************************************************************************	
	if(preg_match("#\|\s+(.+?):\s+\(2\)\s+No such file or directory#", $buffer,$re)){
		if(preg_match("#\/usr\/share\/squid3#i",$buffer)){return;}
		if(TimeStampTTL(__LINE__,2)){
			squid_admin_mysql(1,"{$re[1]} No such file or directory","$buffer",__FILE__,__LINE__);
		}
		return;
	}
// *******************************************************************************************************************	
	if(preg_match("#kid[0-9]+\|\s+\/(.+?)\/[0-9]+\/[0-9A-Z]+$#", $buffer)){
		return;
	}
	
// *******************************************************************************************************************	
	if(preg_match("#storeDirClean:\s+(.+?):\s+\(2\)\s+No such file or directory#", $buffer,$re)){
		$file="/etc/artica-postfix/pids/".md5("storeDirCleanNo::NoSuchFileOrDirectory");
		$fileMail="/etc/artica-postfix/pids/squid.miss_dir";
		$timefile=file_time_min($file);
		$dirname=trim($re[1]);
		events("$dirname No such file or directory... Line:".__LINE__);
		@mkdir("$dirname",0755);
		@chown($dirname, "squid");
		@chgrp($dirname, "squid");	
		if($timefile>5){	
			$timefile=file_time_min($fileMail);
			if($timefile>10){
				squid_admin_mysql(1,"Suspicious removed object $dirname","Suspicious removed object $dirname\r\nsquid-cache claim\r\n$buffer\r\nIt seems that this cache directory was removed after the started service\r\nChecks that your have created your caches \"outside\" /var/cache/squid*\r\n");
				squid_admin_notifs("Suspicious removed object $dirname\r\nsquid-cache claim\r\n$buffer\r\nIt seems that this cache directory was removed after the started service\r\nChecks that your have created your caches \"outside\" /var/cache/squid*\r\n", __FUNCTION__, __FILE__, __LINE__, "proxy");
				@unlink($fileMail);
				@file_put_contents($file, time());
			}
		@unlink($file);
		@file_put_contents($file, time());
	}
	return;
	}
// *******************************************************************************************************************	
	
	if(preg_match("#DiskThreadsDiskFile::openDone:.*?No such file or directory#", $buffer,$re)){
		$file="/etc/artica-postfix/pids/".md5("DiskThreadsDiskFile::openDone:NoSuchFileOrDirectory");
		$timefile=file_time_min($file);
		$fileMail="/etc/artica-postfix/pids/squid.miss_dir";
		events("DiskThreadsDiskFile:: \"$buffer\" [do nothing]");
		if($timefile<15){return;}
		squid_admin_mysql(1,"Cache object issue on disk","squid-cache claim\r\n$buffer\r\nIt seems that some caches objects was removed after the started service\r\nJust an information, nothing will be done.");
		$timefile=file_time_min($fileMail);
		@unlink($file);
		@file_put_contents($file, time());
		return;
	}
// *******************************************************************************************************************	
if(preg_match("#Failed to verify one of the swap directories#", $buffer,$re)){
		$file="/etc/artica-postfix/pids/".md5("Failed to verify one of the swap directories");
		$timefile=file_time_min($file);
		$fileMail="/etc/artica-postfix/pids/squid.miss_dir";
		events("Failed to verify one of the swap directories [ - squid -z ?]");
		if($timefile<5){return;}
		$timefile=file_time_min($fileMail);
		if($timefile>10){
			squid_admin_mysql(0,"Missing Caches !!","squid-cache claim\r\n$buffer\r\nIt seems that caches directory was removed after the started service\r\nArtica start the procedure to verify caches..\r\n");
			squid_admin_notifs("Missing Caches !!\r\nsquid-cache claim\r\n$buffer\r\nIt seems that caches directory was removed after the started service\r\nArtica start the procedure to verify caches..\r\nr", __FUNCTION__, __FILE__, __LINE__, "proxy");
			@unlink($file);
			@file_put_contents($file, time());
		}
				
		@unlink($file);
		@file_put_contents($file, time());
		shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.smp.php --squid-z-fly >/dev/null 2>&1 &");
		return;
}
// *******************************************************************************************************************
if(preg_match("#kid[0-9]+\|\s+\/home\/squid\/cache\/MemBooster[0-9]+\/#", $buffer,$re)){
	$file="/etc/artica-postfix/pids/".md5("MemBoosterFailed");
	$timefile=file_time_min($file);
	if($timefile<15){return;}
	squid_admin_mysql(1,"Cache object issue MemBoosters","squid-cache claim\r\n$buffer\r\nIt seems that some caches objects was removed after the started service\r\nJust an information, nothing will be done.");
	@unlink($file);
	@file_put_contents($file, time());
	//shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.watchdog.php --CleanMemBoosters >/dev/null 2>&1");
	}
// *******************************************************************************************************************	
	if(strpos($buffer,"Old swap file detected")>0){
		events("Old swap file detected...".__LINE__);
		//squid_admin_notifs("Missing some caches directories\r\nsquid-cache claim\r\n$buffer\r\nArtica will reset all caches", __FUNCTION__, __FILE__, __LINE__, "proxy");
		//shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.rebuild.caches.php");
		return;
	}
// *******************************************************************************************************************	
	if(strpos($buffer,"Run 'squid -z' to create swap directories")){
		$file="/etc/artica-postfix/pids/".md5("Run 'squid -z' to create swap directories");
		$timefile=file_time_min($file);
		if($timefile>5){
			events("SQUID -Z !!!... Line:".__LINE__);
			squid_admin_mysql(0,"Missing Caches !!","squid-cache claim\r\n$buffer\r\nArtica will launch the directory creation");
			squid_admin_notifs("Missing some caches directories\r\nsquid-cache claim\r\n$buffer\r\nArtica will launch the directory creation", __FUNCTION__, __FILE__, __LINE__, "proxy");
			shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.smp.php --squid-z-fly >/dev/null 2>&1 &");
			@unlink($file);
			@file_put_contents($file, time());
		}else{
			events("SQUID -Z !!!... TIMEOUT Line:".__LINE__);
		}
		return;
	}
// *******************************************************************************************************************	
	if(strpos($buffer,"| Reconfiguring Squid Cache")>0){
		$file="/etc/artica-postfix/pids/".md5("Reconfiguring Squid Cache");
		$timefile=file_time_min($file);
		if($timefile>1){
			events("Reconfiguring Squid Cache Line:".__LINE__);
			@unlink($file);
			@file_put_contents($file, time());	
			
		}
		return;
	}
// *******************************************************************************************************************	
	if(strpos($buffer,"Fatal: Bungled squid.conf line")){
		events("Bad configuration file!".__LINE__);
		squid_admin_mysql(0,"Bad configuration file","squid-cache claim\r\n$buffer\r\nTry to run the configuration compilation on Artica or contact our support team...");
		squid_admin_notifs("Bad configuration file!\r\nsquid-cache claim\r\n$buffer\r\nTry to run the configuration compilation on Artica or contact our support team...", __FUNCTION__, __FILE__, __LINE__, "proxy");	
		return;
	}
// *******************************************************************************************************************	
	if(preg_match("#FATAL ERROR: cannot connect to ufdbguardd daemon socket: Connection timed out#",$buffer)){
		$file="/etc/artica-postfix/pids/".md5("Fatal:ufdbguardd daemon socket: Connection timed out");
		$timefile=file_time_min($file);
		if($timefile>5){
			events("Fatal: ufdbguardd daemon socket:timed out ".__LINE__);
			squid_admin_mysql(0,"Issue on Webfiltering Daemon!","squid-cache claim\r\n$buffer\r\nThe Webfiltering Dameon will disconnected from proxy service will be reloaded");
			squid_admin_notifs("Issue on Webfiltering Daemon!\r\nsquid-cache claim\r\n$buffer\r\nThe Webfiltering Dameon will disconnected from proxy service will be reloaded", __FUNCTION__, __FILE__, __LINE__, "proxy");
			@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableUfdbGuard",0);
			shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");
			shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/bin/artica-make APP_UFDBGUARD >/dev/null 2>&1 &");
			@unlink($file);
			@file_put_contents($file, time());	
			return;
		}	
		events("Fatal: ufdbguardd daemon socket:timed out ".__LINE__);
		return;
		
	}
// *******************************************************************************************************************	
	
	if(preg_match("#:\s+(.+?):\s+\(13\)\s+Permission denied#",$buffer,$re)){
		@chown($re[1],"squid");
		@chgrp($re[1],"squid");
		events("Add squid:squid permission on `{$re[1]}` ".__LINE__);
		if(preg_match("#pinger$#", $re[1])){
			events("Add chmod 04755 on `{$re[1]}` ".__LINE__);
			shell_exec("{$GLOBALS["CHOWN"]} root {$re[1]}");
			shell_exec("{$GLOBALS["CHMOD"]} 4755 {$re[1]}");
		}
		return;
	}
// *******************************************************************************************************************	
	
	
	if(preg_match("#FATAL: Received Segment Violation\.\.\.dying#",$buffer)){
		$file="/etc/artica-postfix/pids/".md5("Fatal: Received Segment Violation");
		$timefile=file_time_min($file);
		events("Fatal: Received Segment Violation ".__LINE__);
		
		if($GLOBALS["MonitConfig"]["RestartWhenCrashes"]==0){
			squid_admin_mysql(1,"Received Segment Violation","squid-cache claim\r\n$buffer");
			squid_admin_notifs("Received Segment Violation\r\nsquid-cache claim\r\n$buffer\r\nThis just a notification, Artica will checks your settings and determine what is the issue...", __FUNCTION__, __FILE__, __LINE__, "proxy");
			return;
		}

	}
	
	if(preg_match("#optional ICAP service is down after an options fetch failure:\s+icap:.*?1344\/av\/reqmod#",$buffer)){
		$file="/etc/artica-postfix/pids/".md5("KasperskyIcapDown");
		$timefile=file_time_min($file);
		
		if($timefile>2){
				squid_admin_mysql(1,"ICAP service is down, reloading squid-cache service","$buffer");
				port1344_notavailable();
				@unlink($file);
				@file_put_contents($file, time());
				shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.watchdog.php --reload --force --exec-status=".__LINE__." >/dev/null 2>&1 &");
				return;
			}
		events("ICAP service is down after an options (1344) -> timeout Line:".__LINE__);
		return;
	}
	
	
	if(strpos($buffer,"Terminated abnormally")){
		if($GLOBALS["MonitConfig"]["RestartWhenCrashes"]==0){
			squid_admin_mysql(1,"Squid Terminated abnormally","$buffer");
			squid_admin_notifs("Squid Terminated abnormally\r\nsquid-cache claim\r\n$buffer\r\nThis just a notification, Artica will checks your settings and determine what is the issue...", __FUNCTION__, __FILE__, __LINE__, "proxy");
			return;
		}
		$file="/etc/artica-postfix/pids/".md5("Terminated abnormally");
		$timefile=file_time_min($file);
		if($timefile>1){
			events("Terminated abnormally ".__LINE__);
			squid_admin_mysql(0,"Squid Terminated abnormally","$buffer\nProxy will be restarted");
			squid_admin_notifs("Squid Terminated abnormally\r\nsquid-cache claim\r\n$buffer\r\nThis just a notification, Artica will checks your settings and determine what is the issue...", __FUNCTION__, __FILE__, __LINE__, "proxy");
			@unlink($file);
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
		squid_admin_mysql(2,"Kaspersky ICAP service down","Squid-Cache claim\n$buffer\nBut it seems that the ICAP server is disabled...\nArtica will reconfigure the service");
		squid_admin_notifs("Kaspersky ICAP service down!\nSquid-Cache claim\n$buffer\nBut it seems that the ICAP server is disabled...\nArtica will reconfigure the service", __FUNCTION__, __FILE__, __LINE__, "proxy");
		shell_exec("{$GLOBALS["NOHUP"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");
		return;
	}
	squid_admin_mysql(1,"Kaspersky ICAP service down","Squid-Cache claim\n$buffer\nArtica will restart the Kaspersky ICAP server...\nArtica will reconfigure the service");
	squid_admin_notifs("Kaspersky ICAP service down!\nSquid-Cache claim\n$buffer\nArtica will restart the Kaspersky ICAP server...\nArtica will reconfigure the service", __FUNCTION__, __FILE__, __LINE__, "proxy");
	shell_exec("{$GLOBALS["NOHUP"]} /etc/init.d/artica-postfix restart kav4proxy >/dev/null 2>&1 &");
	
	
	
	
}

function TimeStampTTL($line,$mins){
	
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}
	
	}
	
	$filename="/etc/artica-postfix/pids/".basename(__FILE__).".$line.time";
	$unix=new unix();
	$Time=$GLOBALS["CLASS_UNIX"]->file_time_min($filename);
	if($Time<$mins){
		events("TimeStampTTL(); = {$Time}mn, need to wait at least {$mins}mn Called by line:$line");
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
	if(strpos($buffer, "found KEY_PRIVATE")>1){return true;}
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
	if(strpos($buffer, "Fatal: pinger: Unable to open any ICMP sockets")>1){return true;}
	if(strpos($buffer, "helperOpenServers")>1){return true;}
	if(strpos($buffer, "Stop accepting HTCP on")>1){return true;}
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
	if(strpos($buffer, "fqdncacheParse: No PTR record for")>1){return true;}
	
	if(strpos($buffer, "Starting ext_time_quota_acl.cc")>1){return true;}
	if(strpos($buffer, "Sending SNMP messages from")>1){return true;}
	if(strpos($buffer, "Closing SNMP receiving")>1){return true;}
	if(strpos($buffer, "Stop sending HTCP")>1){return true;}
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
	if(strpos(" $buffer", "calling free_func for 0x")>1){return true;}
	if(strpos(" $buffer", "avoid write on theFile")>1){return true;}
	if(strpos(" $buffer", "| Making directories in /")>1){return true;}
	if(strpos(" $buffer", "faults with physical i/o")>1){return true;}
	if(strpos(" $buffer", "| Configuring Parent")>1){return true;}
	if(strpos(" $buffer", "getsockopt(SO_ORIGINAL_DST)")>1){return true;}
	if(strpos(" $buffer", "xrename: Cannot rename")>1){return true;}
	if(strpos(" $buffer", "Squid modules loaded")>1){return true;}
	if(strpos(" $buffer", "Ready to serve requests")>1){return true;}
	if(strpos(" $buffer", "squid-internal-mgr")>1){return true;}
	if(strpos(" $buffer", "internalStart: unknown request")>1){return true;}
	if(strpos(" $buffer", "Waiting for requests")>1){return true;}
	if(strpos(" $buffer", "if needed, or if running Squid for the ")>1){return true;}
	if(strpos(" $buffer", "Store logging disabled")>1){return true;}
	if(strpos(" $buffer", "X-Real-IP")>1){return true;}
	if(strpos(" $buffer", "Via: 1.1")>1){return true;}
	if(strpos(" $buffer", "GET /")>1){return true;}
	if(strpos(" $buffer", "Cache-Control:")>1){return true;}
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
function NETWORK_REPORT(){
	$unix=new unix();
	return $unix->NETWORK_REPORT();
	$results[]="Report....:";
	$ifconfig=$unix->find_program("ifconfig");
	exec("$ifconfig -a 2>&1",$results);
	$ip=$unix->find_program("ip");
	exec("$ip link show 2>&1",$results);
	exec("$ip addr 2>&1",$results);
	$results[]="Routes....:";
	exec("$ip route 2>&1",$results);
	return @implode("\r\n", $results);
}