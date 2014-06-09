<?php
$GLOBALS["NOREBOOT"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){
	$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
}

if(preg_match("#noreboot#",implode(" ",$argv))){
	$GLOBALS["NOREBOOT"]=true;
}

include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/ressources/class.resolv.conf.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");

if($argv[1]=="--automation"){automation();exit;}
if($argv[1]=="--articaweb"){create_articaweb($argv["2"]);die();}
if($argv[1]=="--genuid"){
		$unix=new unix();
		echo "Dynamic: ";
		echo $unix->gen_uuid()."\nCurrent: ".$unix->GetUniqueID()."\n";die();}
if($argv[1]=="--tests-network"){testnetworks($argv["2"]);die();}

WizardExecute();


function testnetworks(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){die();}	
	@file_get_contents($pidfile,getmypid());
	
	shell_exec("/etc/init.d/mysql restart");
	
	$users=new usersMenus();
	$q=new mysql();
	$q->BuildTables();
	$sock=new sockets();
	$savedsettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
	
	if(!is_array($savedsettings)){die();}
	if(count($savedsettings)<4){die();}

	if($q->COUNT_ROWS("nics", "artica_backup")==0){
		WizardExecute();
		@file_put_contents("/etc/artica-postfix/TESTS_NETWORK_EXECUTED", time());
	}
	
	
}

function writeprogress($perc,$text){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/wizard.progress";
	$array["POURC"]=$perc;
	$array["TEXT"]=$text;
	echo "$text\n";
	@mkdir("/usr/share/artica-postfix/ressources/logs/web",true,0755);
	@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);
	
}

function automation(){
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	$sock=new sockets();
	$users=new usersMenus();
	$unix=new unix();
	
	if(!is_file("/usr/share/artica-postfix/ressources/logs/web/AutomationScript.conf")){
		echo "AutomationScript.conf no such file...\n";
		writeprogress(110,"AutomationScript.conf no such file...");
		return;
	}
	
	$ipClass=new IP();
	$users=new usersMenus();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));	
	$php=$unix->LOCATE_PHP5_BIN();
	
	$AutomationScript=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/AutomationScript.conf");
	if(preg_match("#<SQUIDCONF>(.*?)</SQUIDCONF>#is", $AutomationScript,$rz)){
		$squidconf=$rz[1];
		if(strlen($squidconf)>10){
			echo "Squid.conf = ".strlen($squidconf)." bytes\n";
			$AutomationScript=str_replace("<SQUIDCONF>{$rz[1]}</SQUIDCONF>", "", $AutomationScript);
			@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/SquidToImport.conf", $squidconf);
			$squidconf=null;
			writeprogress(5,"Importing old Squid.conf");
			system("$php /usr/share/artica-postfix/exec.squid.import.conf.php --import \"/usr/share/artica-postfix/ressources/logs/web/SquidToImport.conf\" --verbose");
			@unlink("/usr/share/artica-postfix/ressources/logs/web/SquidToImport.conf");
		}
	}
	
	
	
	
	$data=explode("\n",$AutomationScript);
	$WizardStatsAppliance=unserialize(base64_decode($sock->GET_INFO("WizardStatsAppliance")));
	writeprogress(5,"Analyze configuration file...");
	
	while (list ($num, $ligne) = each ($data) ){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
	
		if(preg_match("#^\##", trim($ligne))){continue;}
		if(!preg_match("#(.+?)=(.+)#", $ligne,$re)){continue;}
		$key=trim($re[1]);
		$value=trim($re[2]);
		echo "Parsing $key = \"$value\"\n";
		
		if($key=="WizardStatsApplianceDisconnected"){
				$sock->SET_INFO("WizardStatsApplianceDisconnected", $value); 
				$sock->SET_INFO("EnableMySQLSyslogWizard", 1); 
				$sock->SET_INFO("WizardStatsApplianceSeen", 1); 
				
				continue; }
		if($key=="WizardStatsAppliance_server"){$WizardStatsAppliance["SERVER"]=$value; continue; }
		if($key=="WizardStatsAppliance_port"){$WizardStatsAppliance["PORT"]=$value; continue; }
		if($key=="WizardStatsAppliance_username"){$WizardStatsAppliance["MANAGER"]=$value; continue; }
		if($key=="WizardStatsAppliance_password"){$WizardStatsAppliance["MANAGER-PASSWORD"]=$value; continue; }		
		if(preg_match("#BackupSquidLogs#", $key)){$sock->SET_INFO($key, $value);}
		
		
		if($key=="caches"){ $WizardSavedSettings["CACHES"][]=$value; continue; }
		$WizardSavedSettings[$key]=$value;
		$KerbAuthInfos[$key]=$value;
	}
	writeprogress(6,"Analyze configuration file...");
	$sock->SaveConfigFile(base64_encode(serialize($WizardStatsAppliance)), "WizardStatsAppliance");
	$WizardSavedSettings["ARTICAVERSION"]=$users->ARTICA_VERSION;
	
	
	$ProxyDNSCount=0;
	if(isset($WizardSavedSettings["EnableKerbAuth"])){
		$sock->SET_INFO("EnableKerbAuth", $WizardSavedSettings["EnableKerbAuth"]);
		$sock->SET_INFO("UseADAsNameServer", $WizardSavedSettings["UseADAsNameServer"]);
		$sock->SET_INFO("NtpdateAD", $WizardSavedSettings["NtpdateAD"]);
		if($WizardSavedSettings["UseADAsNameServer"]==1){
			if($ipClass->isValid($WizardSavedSettings["ADNETIPADDR"])){
				$WizardSavedSettings["DNS1"]=$WizardSavedSettings["ADNETIPADDR"];
				$q=new mysql_squid_builder();
				$q->QUERY_SQL("INSERT INTO dns_servers (dnsserver,zOrder) VALUES ('{$WizardSavedSettings["ADNETIPADDR"]}','$ProxyDNSCount')");
			}
		}
	
	}	
	writeprogress(7,"Analyze configuration file...");
	if(isset($WizardSavedSettings["ProxyDNS"])){
		$ProxyDNS=explode(",",$WizardSavedSettings["ProxyDNS"]);
		$c=1;
		while (list ($num, $nameserver) = each ($ProxyDNS) ){
			if(!$ipClass->isValid($nameserver)){continue;}
			$ProxyDNSCount++;
			$q=new mysql_squid_builder();
			$q->QUERY_SQL("INSERT INTO dns_servers (dnsserver,zOrder) VALUES ('{$WizardSavedSettings["ADNETIPADDR"]}','$ProxyDNSCount')");
		}
	}	
	
	writeprogress(8,"Analyze configuration file...");
	if(isset($WizardSavedSettings["ENABLE_PING_GATEWAY"])){
		$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
		$MonitConfig["ENABLE_PING_GATEWAY"]=$WizardSavedSettings["ENABLE_PING_GATEWAY"];
		$MonitConfig["PING_GATEWAY"]=$WizardSavedSettings["PING_GATEWAY"];
		$MonitConfig["MAX_PING_GATEWAY"]=$WizardSavedSettings["MAX_PING_GATEWAY"];
		$MonitConfig["PING_FAILED_RELOAD_NET"]=$WizardSavedSettings["PING_FAILED_RELOAD_NET"];
		$MonitConfig["PING_FAILED_REBOOT"]=$WizardSavedSettings["PING_FAILED_REBOOT"];
		$MonitConfig["PING_FAILED_REPORT"]=$WizardSavedSettings["PING_FAILED_REPORT"];
		$MonitConfig["PING_FAILED_FAILOVER"]=$WizardSavedSettings["PING_FAILED_FAILOVER"];
		$sock->SaveConfigFile(base64_encode(serialize($MonitConfig)), "SquidWatchdogMonitConfig");
	}	
	writeprogress(9,"Analyze configuration file...");
	$sock->SET_INFO("timezones",$WizardSavedSettings["timezones"]);
	$nic=new system_nic();
	$hostname=$WizardSavedSettings["netbiosname"].".".$WizardSavedSettings["domain"];
	$nic->set_hostname($hostname);
	$data=$sock->getFrameWork("system.php?zoneinfo-set=".urlencode(base64_encode($WizardSavedSettings["timezones"])));
	$Encoded=base64_encode(serialize($WizardSavedSettings));
	$sock->SaveConfigFile($Encoded,"WizardSavedSettings");
	
	writeprogress(10,"Analyze configuration file...");
	$TuningParameters=unserialize(base64_decode($sock->GET_INFO("MySQLSyslogParams")));
	if(isset($WizardSavedSettings["MySQLSyslogUsername"])){$TuningParameters["username"]=$WizardSavedSettings["MySQLSyslogUsername"];}
	if(isset($WizardSavedSettings["MySQLSyslogPassword"])){$TuningParameters["password"]=$WizardSavedSettings["MySQLSyslogPassword"];}
	if(isset($WizardSavedSettings["MySQLSyslogServer"])){$TuningParameters["mysqlserver"]=$WizardSavedSettings["MySQLSyslogServer"];}
	if(isset($WizardSavedSettings["MySQLSyslogServerPort"])){$TuningParameters["RemotePort"]=$WizardSavedSettings["MySQLSyslogServerPort"];}
	if(isset($WizardSavedSettings["MySQLSyslogWorkDir"])){$TuningParameters["MySQLSyslogWorkDir"]=$WizardSavedSettings["MySQLSyslogWorkDir"];}
	if(isset($WizardSavedSettings["MySQLSyslogType"])){$TuningParameters["MySQLSyslogType"]=$WizardSavedSettings["MySQLSyslogType"];}
	$sock->SaveConfigFile(base64_encode(serialize($TuningParameters)), "MySQLSyslogParams");
	$sock->SET_INFO("MySQLSyslogType", $WizardSavedSettings["MySQLSyslogType"]);
	$sock->SET_INFO("MySQLSyslogWorkDir", $WizardSavedSettings["MySQLSyslogWorkDir"]);
	$sock->SET_INFO("EnableSyslogDB", $WizardSavedSettings["EnableSyslogDB"]);
	
	if(isset($WizardSavedSettings["EnableCNTLM"])){
		$sock->SET_INFO("EnableCNTLM", $WizardSavedSettings["EnableCNTLM"]);
		$sock->SET_INFO("CnTLMPORT", $WizardSavedSettings["CnTLMPORT"]);
	}
	
	if(isset($WizardSavedSettings["DisableSpecialCharacters"])){
		$sock->SET_INFO("DisableSpecialCharacters", $WizardSavedSettings["DisableSpecialCharacters"]);
	}
	
	if(isset($WizardSavedSettings["SambaBindInterface"])){
		$sock->SET_INFO("SambaBindInterface", $WizardSavedSettings["SambaBindInterface"]);
	}
	
	writeprogress(11,"Analyze configuration file...");
	if(isset($WizardSavedSettings["EnableSNMPD"])){
		$sock->SET_INFO("EnableSNMPD", $WizardSavedSettings["EnableSNMPD"]);
		$sock->SET_INFO("SNMPDCommunity", $WizardSavedSettings["SNMPDCommunity"]);
		$sock->SET_INFO("SNMPDNetwork", $WizardSavedSettings["SNMPDNetwork"]);
		$sock->getFrameWork("snmpd.php?restart=yes");
	}
	
	
	writeprogress(12,"Analyze configuration file...");
	if(isset($WizardSavedSettings["DisableArticaProxyStatistics"])){$sock->SET_INFO("DisableArticaProxyStatistics", $WizardSavedSettings["DisableArticaProxyStatistics"]);}
	if(isset($WizardSavedSettings["EnableProxyLogHostnames"])){$sock->SET_INFO("EnableProxyLogHostnames", $WizardSavedSettings["EnableProxyLogHostnames"]);}
	if(isset($WizardSavedSettings["EnableSargGenerator"])){$sock->SET_INFO("EnableSargGenerator", $WizardSavedSettings["EnableSargGenerator"]);}
	
	if(isset($WizardSavedSettings["CACHES"])){
		if(count($WizardSavedSettings["CACHES"])>0){
			$q=new mysql_squid_builder();
			$order=1;
			while (list ($index, $line) = each ($WizardSavedSettings["CACHES"]) ){
				$order++;
				$CONFCACHE=explode(",",$line);
				$cachename=$CONFCACHE[0];
				$CPU=$CONFCACHE[1];
				$cache_directory=$CONFCACHE[2];
				$cache_type=$CONFCACHE[3];
				$size=$CONFCACHE[4];
				$cache_dir_level1=$CONFCACHE[5];
				$cache_dir_level2=$CONFCACHE[6];
				if($cache_type=="tmpfs"){ $users=new usersMenus(); $memMB=$users->MEM_TOTAL_INSTALLEE/1024; $memMB=$memMB-1500; if($size>$memMB){ $size=$memMB-100; }}
				$q->QUERY_SQL("INSERT IGNORE INTO squid_caches_center
						(cachename,cpu,cache_dir,cache_type,cache_size,cache_dir_level1,cache_dir_level2,enabled,percentcache,usedcache,zOrder)
						VALUES('$cachename',$CPU,'$cache_directory','$cache_type','$size','$cache_dir_level1','$cache_dir_level2',1,0,0,$order)","artica_backup");
			}
		}
	}
	
	writeprogress(13,"Analyze configuration file...");
	if(isset($WizardSavedSettings["Blacklists"])){
		if($WizardSavedSettings["EnableWebFiltering"]==1){
			$tp=explode(",",$WizardSavedSettings["Blacklists"]);
			$q=new mysql_squid_builder();
			while (list ($key, $category) = each ($tp) ){
				if(trim($category)==null){continue;}
				$sql="INSERT IGNORE INTO webfilter_blks (webfilter_id,category,modeblk) VALUES ('0','$category','0')";
				$q->QUERY_SQL($sql);
	
			}
		}
	
	}
	
	writeprogress(14,"Analyze configuration file...");
	if(isset($WizardSavedSettings["EnableTransparent"])){
		$sock->SET_INFO("hasProxyTransparent",$WizardSavedSettings["EnableTransparent"]);
		if( $WizardSavedSettings["EnableTransparent"] ==1){
			$squid=new squidbee();
			$squid->listen_port=$WizardSavedSettings["TransparentPort"];
			$squid->second_listen_port=$WizardSavedSettings["proxy_listen_port"];
			$WizardSavedSettings["proxy_listen_port"]=$WizardSavedSettings["TransparentPort"];
			$squid->SaveToLdap(true);
		}
	
	}
	
	writeprogress(15,"Analyze configuration file...");
	if(isset($WizardSavedSettings["cache_mem"])){
		$squid=new squidbee();
		$squid->global_conf_array["cache_mem"]=$WizardSavedSettings["cache_mem"];
		$squid->global_conf_array["fqdncache_size"]=$WizardSavedSettings["fqdncache_size"];
		$squid->global_conf_array["ipcache_size"]=$WizardSavedSettings["ipcache_size"];
		$squid->global_conf_array["ipcache_low"]=$WizardSavedSettings["ipcache_low"];
		$squid->global_conf_array["ipcache_high"]=$WizardSavedSettings["ipcache_high"];
		$squid->SaveToLdap(true);
	}
	
	
	if(isset($WizardSavedSettings["swappiness"])){
		$swappiness_saved=unserialize(base64_decode($sock->GET_INFO("kernel_values")));
		$swappiness_saved["swappiness"]=$WizardSavedSettings["swappiness"];
		$sock->SaveConfigFile( base64_encode(serialize($swappiness_saved)),"kernel_values");
		$sock->getFrameWork("cmd.php?sysctl-setvalue={$WizardSavedSettings["swappiness"]}&key=".base64_encode("vm.swappiness"));
	}
	
	writeprogress(16,"Analyze configuration file...");
	if(isset($WizardSavedSettings["ManagerAccount"])){
		$ldap=new clladp();
		if($ldap->suffix==null){$suffix="dc=nodomain";}
		$username=urlencode($WizardSavedSettings["ManagerAccount"]);
		$password=urlencode(base64_encode($WizardSavedSettings["ManagerPassword"]));
		$cmd="cmd.php?ChangeLDPSSET=yes&ldap_server=127.0.0.1&ldap_port=389&suffix=".urlencode($suffix);
		$cmd=$cmd."&change_ldap_server_settings=no&username=$username&password=$password";
		$datas=$sock->getFrameWork("$cmd");
	}
	
	writeprogress(17,"Analyze configuration file...");
	$sock->SET_INFO("EnableUfdbGuard", $WizardSavedSettings["EnableWebFiltering"]);
	$sock->SET_INFO("EnableArpDaemon", $WizardSavedSettings["EnableArpDaemon"]);
	$sock->SET_INFO("EnablePHPFPM",0);
	$sock->SET_INFO("EnableFreeWeb",$WizardSavedSettings["EnableFreeWeb"]);
	$sock->SET_INFO("SlapdThreads", $WizardSavedSettings["SlapdThreads"]);
	$sock->SET_INFO("EnableVnStat", 0);
	$sock->SET_INFO("WizardSavedSettingsSend", 1);	
	
	
	$savedsettings["ARTICAVERSION"]=$users->ARTICA_VERSION;
	$Encoded=base64_encode(serialize($WizardSavedSettings));
	@file_put_contents("/etc/artica-postfix/settings/Daemons/WizardSavedSettings", $Encoded);
	
	
	writeprogress(18,"Analyze configuration file...{finish}");
	WizardExecute();
		
}


function WizardExecute(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){die();}
	$pid=$unix->PIDOF_PATTERN(basename(__FILE__));
	if($pid<>getmypid()){return;}
	$uuid=$unix->GetUniqueID();
	writeprogress(20,"Server ID: $uuid");
	sleep(2);
	writeprogress(20,"Scanning hardware/software");
	shell_exec("/etc/init.d/artica-process1 start");

	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");	
	@file_get_contents($pidfile,getmypid());

	$users=new usersMenus();
	$q=new mysql();
	writeprogress(25,"Creating databases");
	$q->BuildTables();
	$sock=new sockets();
	$savedsettings=unserialize(base64_decode(file_get_contents("/etc/artica-postfix/settings/Daemons/WizardSavedSettings")));
	$ArticaDBPath=$sock->GET_INFO("ArticaDBPath");
	if($ArticaDBPath==null){$ArticaDBPath="/opt/articatech";}
	

	if(!is_array($savedsettings)){
		writeprogress(110,"No saved settings Corrupted Array...");
		die();
	}
	if(count($savedsettings)<4){
		writeprogress(110,"No saved settings too less elements...");
		die();
	}
		
	writeprogress(30,"Creating services");
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.initslapd.php  --force >/dev/null 2>&1 &");
	
	if(is_dir("$ArticaDBPath/data")){
		writeprogress(40,"Starting services");
		shell_exec("$nohup /etc/init.d/squid-db start >/dev/null 2>&1 &");
	}
	$KEEPNET=$savedsettings["KEEPNET"];
	if($KEEPNET==1){
		writeprogress(100,"Done");
		@file_put_contents("/etc/artica-postfix/WIZARD_INSTALL_EXECUTED", time());
		shell_exec("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");
		shell_exec("$nohup /etc/init.d/monit restart >/dev/null 2>&1 &");
		FINAL___();
		return;
	}
	

	
	$Encoded=base64_encode(serialize($savedsettings));
	@file_put_contents("/etc/artica-postfix/settings/Daemons/WizardSavedSettings", $Encoded);
	
	if(isset($savedsettings["GoldKey"])){
		if($sock->IsGoldKey($savedsettings["GoldKey"])){
			$WORKDIR=base64_decode("L3Vzci9sb2NhbC9zaGFyZS9hcnRpY2E=");
			$WORKFILE=base64_decode('LmxpYw==');
			$WORKPATH="$WORKDIR/$WORKFILE";
			@file_put_contents($WORKPATH, "TRUE");
			$LicenseInfos=unserialize(base64_decode($sock->GET_INFO("LicenseInfos")));
			$LicenseInfos["UUID"]=$savedsettings["UUID_FIRST"];
			$LicenseInfos["TIME"]=time();
			$sock->SaveConfigFile(base64_encode(serialize($LicenseInfos)), "LicenseInfos");
		}
	}
	
	
	writeprogress(60,"Building networks");
	$nics=new system_nic("eth0");
	$nics->CheckMySQLFields();
	
	$nics->eth="eth0";
	$nics->IPADDR=$savedsettings["IPADDR"];
	$nics->NETMASK=$savedsettings["NETMASK"];;
	$nics->GATEWAY=$savedsettings["GATEWAY"];;
	$nics->BROADCAST=$savedsettings["BROADCAST"];;
	$nics->DNS1=$savedsettings["DNS1"];;
	$nics->DNS2=$savedsettings["DNS2"];;
	$nics->dhcp=0;
	$nics->metric=$savedsettings["metric"];
	$nics->enabled=1;
	$nics->defaultroute=1;
	writeprogress(60,"Saving networks");
	$nics->SaveNic();

	writeprogress(60,"Loading resolv library");
	$resolv=new resolv_conf();
	$arrayNameServers[0]=$savedsettings["DNS1"];
	$arrayNameServers[1]=$savedsettings["DNS2"];
	$resolv->MainArray["DNS1"]=$arrayNameServers[0];
	$resolv->MainArray["DNS2"]=$arrayNameServers[1];
	writeprogress(60,"Saving DNS settings");
	$resolv->save();

	$netbiosname=$savedsettings["netbiosname"];
	if(isset($savedsettings["domain"])){
		$domainname=$savedsettings["domain"];
	}

	
	$sock=new sockets();
	
	$nic=new system_nic();
	writeprogress(60,"Setting hostname");
	$nic->set_hostname("$netbiosname.$domainname");
	
	writeprogress(60,"Building resolv configuration");
	$sock->getFrameWork("services.php?resolvConf=yes");
	writeprogress(60,"Settings permissions");
	$sock->getFrameWork("services.php?folders-security=yes");
	writeprogress(60,"Building caches pages...");
	$sock->getFrameWork("services.php?cache-pages=yes");
	sleep(1);
	
	$ldap=new clladp();
	writeprogress(60,"Building {$savedsettings["organization"]}");
	$ldap->AddOrganization($savedsettings["organization"]);
	$ldap->AddDomainEntity($savedsettings["organization"],$savedsettings["smtp_domainname"]);
	$sock=new sockets();
	
	writeprogress(60,"Building network scripts");
	shell_exec("$php5 /usr/share/artica-postfix/exec.virtuals-ip.php >/dev/null 2>&1");
	$unix->THREAD_COMMAND_SET("$php5 /usr/share/artica-postfix/exec.postfix.maincf.php --reconfigure");
	$unix->THREAD_COMMAND_SET("/usr/share/artica-postfix/bin/artica-install --reconfigure-cyrus");

	$FreeWebAdded=false;
	sleep(3);
	
	if(!is_file("/etc/artica-postfix/WIZARD_INSTALL_EXECUTED")){
		if(!$GLOBALS["NOREBOOT"]){$reboot=true;}
		$rebootWarn=null;
	}

	if($users->SQUID_INSTALLED){
		include_once(dirname(__FILE__)."/ressources/class.squid.inc");
		$squid=new squidbee();
		if(is_numeric($savedsettings["proxy_listen_port"])){
			$squid->listen_port=$savedsettings["proxy_listen_port"];
				
		}
		
		if($q->COUNT_ROWS("squid_caches_center", "artica_backup")==0){
			$cachename=basename($squid->CACHE_PATH);
			$q->QUERY_SQL("INSERT IGNORE INTO `squid_caches_center` (cachename,cpu,cache_dir,cache_type,cache_size,cache_dir_level1,cache_dir_level2,enabled,percentcache,usedcache,remove)
			VALUES('$cachename',1,'$squid->CACHE_PATH','$squid->CACHE_TYPE','2000','128','256',1,0,0,0)","artica_backup");
		}
		
		$squid->SaveToLdap();
		writeprogress(65,"Reconfiguring Proxy");
		$unix->THREAD_COMMAND_SET("$php5 /usr/share/artica-postfix/exec.squid.php --build --force");
	}

	if(isset($savedsettings["EnablePDNS"])){
		$sock->SET_INFO("EnablePDNS",$savedsettings["EnablePDNS"]);
	}
	
	if(isset($savedsettings["EnableDHCPServer"])){
		$sock->SET_INFO("EnableDHCPServer",$savedsettings["EnableDHCPServer"]);
	}
	if(isset($savedsettings["EnableFreeRadius"])){
		$sock->SET_INFO("EnableFreeRadius",$savedsettings["EnableFreeRadius"]);
		$sock->getFrameWork("freeradius.php?restart=yes");
	}
	$restart_artica_status=false;
	if($savedsettings["adminwebserver"]<>null){
		writeprogress(67,"Creating Webservices$rebootWarn");
		$sock->SET_INFO("EnableFreeWeb", 1);
		writeprogress(60,"Restarting Artica Status");
		$restart_artica_status=true;
		restart_artica_status();
		writeprogress(68,"Restarting Web services");
		restart_apache_src();
		writeprogress(69,"Creating default website {$savedsettings["adminwebserver"]}");
		include_once(dirname(__FILE__)."/ressources/class.freeweb.inc");
		$free=new freeweb($savedsettings["adminwebserver"]);
		$free->servername=$savedsettings["adminwebserver"];
		$free->groupware="ARTICA_MINIADM";
		$free->CreateSite();
		writeprogress(69,"Building default website {$savedsettings["adminwebserver"]}");
		rebuild_vhost($savedsettings["adminwebserver"]);
	}

	if($savedsettings["second_webadmin"]<>null){
		$sock->SET_INFO("EnableFreeWeb", 1);
		if(!$restart_artica_status){
			writeprogress(70,"Creating Webservices$rebootWarn");
			restart_artica_status();
			restart_apache_src();
		}
		include_once(dirname(__FILE__)."/ressources/class.freeweb.inc");
		$free=new freeweb($savedsettings["second_webadmin"]);
		$free->servername=$savedsettings["second_webadmin"];
		$free->groupware="ARTICA_ADM";
		$free->CreateSite();
		rebuild_vhost($savedsettings["second_webadmin"]);
	}

	if($savedsettings["administrator"]<>null){
		writeprogress(75,"Creating Accounts$rebootWarn");
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT id FROM radgroupcheck WHERE groupname='administrators' LIMIT 0,1","artica_backup"));
		$gpid=$ligne["id"];
		if(!is_numeric($gpid)){$gpid=0;}
		if($gpid==0){
			$sql="INSERT IGNORE INTO radgroupcheck  (`groupname`, `attribute`,`op`, `value`) VALUES ('administrators', 'Auth-Type',':=', 'Accept');";
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){$gpid=0;}else{$gpid=$q->last_id;}
				
			if($gpid>0){
				$savedsettings["administrator"]=mysql_escape_string2($savedsettings["administrator"]);
				$administratorpass=mysql_escape_string2(url_decode_special_tool($savedsettings["administratorpass"]));
				$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT value FROM radcheck WHERE username='{$savedsettings["administrator"]}' LIMIT 0,1","artica_backup"));
				if(trim($ligne["value"])==null){
					$sql="INSERT IGNORE INTO radcheck (`username`, `attribute`, `value`) VALUES ('{$savedsettings["administrator"]}', 'Cleartext-Password', '{$savedsettings["administratorpass"]}');";
					$q->QUERY_SQL($sql,"artica_backup");
				}else{
					$sql="UPDATE radcheck SET `value`='{$savedsettings["administratorpass"]}' WHERE username='{$savedsettings["administrator"]}'";
					$q->QUERY_SQL($sql,"artica_backup");
				}
					
				$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT username FROM radcheck WHERE username='{$savedsettings["administrator"]}' AND groupname='administrators' LIMIT 0,1","artica_backup"));
				if(trim($ligne["username"])==null){
					$sql="insert into radusergroup (username, groupname, priority,gpid) VALUES ('{$savedsettings["administrator"]}', 'administrators', 1,$gpid);";
					$q->QUERY_SQL($sql,"artica_backup");
				}
			}
	
		}
	
	}


	if($savedsettings["statsadministrator"]<>null){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT id FROM radgroupcheck WHERE groupname='WebStatsAdm' LIMIT 0,1","artica_backup"));
		$gpid=$ligne["id"];
		if(!is_numeric($gpid)){$gpid=0;}
		if($gpid==0){
			$sql="INSERT IGNORE INTO radgroupcheck  (`groupname`, `attribute`,`op`, `value`) VALUES ('WebStatsAdm', 'Auth-Type',':=', 'Accept');";
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){$gpid=0;}else{$gpid=$q->last_id;}
	
			if($gpid>0){
				$savedsettings["statsadministrator"]=mysql_escape_string2($savedsettings["statsadministrator"]);
				$administratorpass=mysql_escape_string2(url_decode_special_tool($savedsettings["statsadministratorpass"]));
				$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT value FROM radcheck WHERE username='{$savedsettings["statsadministrator"]}' LIMIT 0,1","artica_backup"));
				if(trim($ligne["value"])==null){
					$sql="INSERT IGNORE INTO radcheck (`username`, `attribute`, `value`) VALUES ('{$savedsettings["statsadministrator"]}', 'Cleartext-Password', '{$savedsettings["statsadministratorpass"]}');";
					$q->QUERY_SQL($sql,"artica_backup");
				}else{
					$sql="UPDATE radcheck SET `value`='{$savedsettings["statsadministratorpass"]}' WHERE username='{$savedsettings["statsadministrator"]}'";
					$q->QUERY_SQL($sql,"artica_backup");
					if(!$q->ok){echo $q->mysql_error;}
				}
					
				$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT username FROM radcheck WHERE username='{$savedsettings["statsadministrator"]}' AND groupname='WebStatsAdm' LIMIT 0,1","artica_backup"));
				if(trim($ligne["username"])==null){
					$sql="insert into radusergroup (username, groupname, priority,gpid) VALUES ('{$savedsettings["statsadministrator"]}', 'WebStatsAdm', 1,$gpid);";
					$q->QUERY_SQL($sql,"artica_backup");
					if(!$q->ok){echo $q->mysql_error;}
				}
			}
		}
	}
	$reboot=false;
	writeprogress(80,"Checking parameters$rebootWarn");
	
	
	if(!is_file("/etc/artica-postfix/WIZARD_INSTALL_EXECUTED")){
		@file_put_contents("/etc/artica-postfix/WIZARD_INSTALL_EXECUTED", time());

	}
	
	$unix->THREAD_COMMAND_SET("$php5 /usr/share/artica-postfix/exec.initslapd.php");
	
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	if($EnableKerbAuth==1){
		writeprogress(82,"Launch Active Directory connection....{please_wait}");
		system("$php5 /usr/share/artica-postfix/exec.kerbauth.php --build --force --verbose");
	}
	
	
	if($savedsettings["EnableWebFiltering"]==1){
		EnableWebFiltering();
		$unix->THREAD_COMMAND_SET("$php5 /usr/share/artica-postfix/exec.update.squid.tlse.php --force");
		
	}

	
	if($users->POSTFIX_INSTALLED){
		$unix->THREAD_COMMAND_SET("$php5 /usr/share/artica-postfix/exec.postfix.maincf.php --build --force");
	}
	
	
	$serverbin=$unix->find_program("zarafa-server");
	if(is_file($serverbin)){
		writeprogress(85,"Restarting Zarafa services$rebootWarn");
		shell_exec("$php5 /usr/share/artica-postfix/exec.initdzarafa.php");
		shell_exec("$php5 /usr/share/artica-postfix/exec.zarafa-db.php --init");
		shell_exec("/etc/init.d/zarafa-db restart");
		shell_exec("/etc/init.d/zarafa-server restart");
		shell_exec("/etc/init.d/zarafa-web restart");
	}
	
	writeprogress(90,"Restarting services$rebootWarn");
	shell_exec("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");
	shell_exec("$nohup /etc/init.d/monit restart >/dev/null 2>&1 &");
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.monit.php --build >/dev/null 2>&1");
	shell_exec("$nohup /usr/share/artica-postfix/exec.web-community-filter.php --register  >/dev/null 2>&1 &");
	
	$time=$unix->file_time_min("/etc/artica-postfix/WIZARD_INSTALL_EXECUTED");
	if(!$reboot){
		writeprogress(100,"done");
		FINAL___();
		return;
	}
	writeprogress(100,"Rebooting");
	sleep(10);
	shell_exec($unix->find_program("reboot"));
}

function FINAL___(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.web-community-filter.php --register >/dev/null 2>&1 &");
	shell_exec($cmd);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.schedules.php --output >/dev/null 2>&1 &");
	shell_exec($cmd);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.php --build-schedules >/dev/null 2>&1 &");
	shell_exec($cmd);
		
	
}



function create_articaweb($websitename){
	if($websitename==null){return;}
	$sock=new sockets();
	$sock->SET_INFO("EnableFreeWeb", 1);
	restart_artica_status();
	restart_apache_src();
	include_once(dirname(__FILE__)."/ressources/class.freeweb.inc");
	$free=new freeweb($websitename);
	$free->servername=$websitename;
	$free->groupware="ARTICA_ADM";
	$free->CreateSite();
	rebuild_vhost($websitename);
}
function rebuild_vhost($servername){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.freeweb.php --sitename $servername >/dev/null 2>&1");
	shell_exec($cmd);
	$unix->THREAD_COMMAND_SET("$php /usr/share/artica-postfix/exec.freeweb.php --sitename $servername");

}
function restart_artica_status(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");	
	shell_exec("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");
	
}

function restart_apache_src(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup /etc/init.d/artica-postfix restart apachesrc >/dev/null 2>&1 &");	
	
}
function EnableWebFiltering(){
	$q=new mysql_squid_builder();
	$q->CheckTables();
	$unix=new unix();
	$sock=new sockets();
	$q->CheckTables();	
	$q->QUERY_SQL("INSERT INTO `webfilter_rules` (`ID`, `groupmode`, `enabled`, `groupname`, `BypassSecretKey`, `endofrule`, `blockdownloads`, `naughtynesslimit`, `searchtermlimit`, `bypass`, `deepurlanalysis`, `UseExternalWebPage`, `ExternalWebPage`, `freeweb`, `sslcertcheck`, `sslmitm`, `GoogleSafeSearch`, `TimeSpace`, `TemplateError`, `TemplateColor1`, `TemplateColor2`, `RewriteRules`, `zOrder`, `AllSystems`, `UseSecurity`, `embeddedurlweight`) VALUES (1, 1, 1, 'Everybody', '', 'any', 0, 50, 30, 0, 0, 0, '', '', 0, 0, 0, '', '', NULL, NULL, '', 0, 1, 0, NULL);");
	$q->QUERY_SQL("INSERT INTO `webfilter_blkgp` (`ID`, `groupname`, `enabled`) VALUES (1, 'Dangerous surf', 1);");
	$q->QUERY_SQL("INSERT INTO `webfilter_blklnk` (`ID`, `zmd5`, `webfilter_blkid`, `webfilter_ruleid`, `blacklist`) VALUES (1, '5f93f983524def3dca464469d2cf9f3e', 1, 1, 0);");
	$q->QUERY_SQL("INSERT INTO `webfilter_blkcnt` (`ID`, `webfilter_blkid`, `category`) VALUES (1, 1, 'hacking'), (2, 1, 'phishtank'), (3, 1, 'phishing'), (4, 1, 'proxy'), (5, 1, 'malware'), (6, 1, 'spyware'), (7, 1, 'suspicious'), (8, 1, 'tracker'), (9, 1, 'warez');");
	@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableUfdbGuard", 1);
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php /usr/share/artica-postfix/exec.squidguard.php --build --force >/dev/null 2>&1");
	shell_exec("$php /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1");
	shell_exec("/etc/init.d/ufdb restart");
}

?>