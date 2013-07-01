<?php
$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
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


if($argv[1]=="--articaweb"){create_articaweb($argv["2"]);die();}
if($argv[1]=="--tests-network"){testnetworks($argv["2"]);die();}

WizardExecute();


function testnetworks(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=@file_get_contents($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){die();}	
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


function WizardExecute(){

	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$oldpid=@file_get_contents($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){die();}
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");	
	@file_get_contents($pidfile,getmypid());

	$users=new usersMenus();
	$q=new mysql();
	$q->BuildTables();
	$sock=new sockets();
	$savedsettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));

	if(!is_array($savedsettings)){die();}
	if(count($savedsettings)<4){die();}
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.initslapd.php  --force >/dev/null 2>&1 &");
	
	if(is_dir("/opt/articatech/data")){
		shell_exec("$nohup /etc/init.d/artica-postfix start articadb >/dev/null 2>&1 &");
	}
	$KEEPNET=$savedsettings["KEEPNET"];
	if($KEEPNET==1){
		@file_put_contents("/etc/artica-postfix/WIZARD_INSTALL_EXECUTED", time());
		shell_exec("$nohup /etc/init.d/artica-status restart >/dev/null 2>&1 &");
		return;
	}
	
	
	
	$nics=new system_nic("eth0");
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
	$nics->SaveNic();

	$resolv=new resolv_conf();
	$arrayNameServers[0]=$savedsettings["DNS1"];
	$arrayNameServers[1]=$savedsettings["DNS2"];
	$resolv->MainArray["DNS1"]=$arrayNameServers[0];
	$resolv->MainArray["DNS2"]=$arrayNameServers[1];
	$resolv->save();

	$netbiosname=$savedsettings["netbiosname"];
	$domainname=$savedsettings["domain"];
	$resolv=new resolv_conf();
	$resolv->MainArray["DNS1"]=$arrayNameServers[0];
	$resolv->MainArray["DNS2"]=$arrayNameServers[1];
	$resolv->save();
	$sock=new sockets();
	$sock->SET_INFO("myhostname","$netbiosname.$domainname");
	$sock->getFrameWork("cmd.php?ChangeHostName=$netbiosname.$domainname");
	$sock->getFrameWork("services.php?resolvConf=yes");
	$sock->getFrameWork("services.php?folders-security=yes");
	$sock->getFrameWork("services.php?cache-pages=yes");
	sleep(1);
	$sock->getFrameWork("services.php?resolvConf=yes");
	sleep(1);
	$ldap=new clladp();
	$ldap->AddOrganization($savedsettings["organization"]);
	$ldap->AddDomainEntity($savedsettings["organization"],$savedsettings["smtp_domainname"]);
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?reconfigure-postfix=yes");
	$sock->getFrameWork("cmd.php?squid-rebuild=yes");
	$sock->getFrameWork("cyrus.php?service-cmds=restart");
	$FreeWebAdded=false;
	sleep(3);

	if($users->SQUID_INSTALLED){
		include_once(dirname(__FILE__)."/ressources/class.squid.inc");
		$squid=new squidbee();
		if(is_numeric($savedsettings["proxy_listen_port"])){
			$squid->listen_port=$savedsettings["proxy_listen_port"];
				
		}
		$squid->SaveToLdap();
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
	
	if($savedsettings["adminwebserver"]<>null){
		$sock->SET_INFO("EnableFreeWeb", 1);
		restart_artica_status();
		restart_apache_src();
		include_once(dirname(__FILE__)."/ressources/class.freeweb.inc");
		$free=new freeweb($savedsettings["adminwebserver"]);
		$free->servername=$savedsettings["adminwebserver"];
		$free->groupware="ARTICA_MINIADM";
		$free->CreateSite();
		rebuild_vhost($savedsettings["adminwebserver"]);
	}

	if($savedsettings["second_webadmin"]<>null){
		$sock->SET_INFO("EnableFreeWeb", 1);
		restart_artica_status();
		restart_apache_src();
		include_once(dirname(__FILE__)."/ressources/class.freeweb.inc");
		$free=new freeweb($savedsettings["second_webadmin"]);
		$free->servername=$savedsettings["second_webadmin"];
		$free->groupware="ARTICA_ADM";
		$free->CreateSite();
		rebuild_vhost($savedsettings["second_webadmin"]);
	}

	if($savedsettings["administrator"]<>null){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT id FROM radgroupcheck WHERE groupname='administrators' LIMIT 0,1","artica_backup"));
		$gpid=$ligne["id"];
		if(!is_numeric($gpid)){$gpid=0;}
		if($gpid==0){
			$sql="INSERT IGNORE INTO radgroupcheck  (`groupname`, `attribute`,`op`, `value`) VALUES ('administrators', 'Auth-Type',':=', 'Accept');";
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){$gpid=0;}else{$gpid=$q->last_id;}
				
			if($gpid>0){
				$savedsettings["administrator"]=mysql_escape_string($savedsettings["administrator"]);
				$administratorpass=mysql_escape_string(url_decode_special_tool($savedsettings["administratorpass"]));
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
				$savedsettings["statsadministrator"]=mysql_escape_string($savedsettings["statsadministrator"]);
				$administratorpass=mysql_escape_string(url_decode_special_tool($savedsettings["statsadministratorpass"]));
				$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT value FROM radcheck WHERE username='{$savedsettings["statsadministrator"]}' LIMIT 0,1","artica_backup"));
				if(trim($ligne["value"])==null){
					$sql="INSERT IGNORE INTO radcheck (`username`, `attribute`, `value`) VALUES ('{$savedsettings["statsadministrator"]}', 'Cleartext-Password', '{$savedsettings["statsadministratorpass"]}');";
					$q->QUERY_SQL($sql,"artica_backup");
				}else{
					$sql="UPDATE radcheck SET `value`='{$savedsettings["statsadministratorpass"]}' WHERE username='{$savedsettings["statsadministrator"]}'";
					$q->QUERY_SQL($sql,"artica_backup");
				}
					
				$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT username FROM radcheck WHERE username='{$savedsettings["statsadministrator"]}' AND groupname='WebStatsAdm' LIMIT 0,1","artica_backup"));
				if(trim($ligne["username"])==null){
					$sql="insert into radusergroup (username, groupname, priority,gpid) VALUES ('{$savedsettings["statsadministrator"]}', 'WebStatsAdm', 1,$gpid);";
					$q->QUERY_SQL($sql,"artica_backup");
				}
			}
		}
	}
	
	@file_put_contents("/etc/artica-postfix/WIZARD_INSTALL_EXECUTED", time());
	
	shell_exec("$php5 /usr/share/artica-postfix/exec.initslapd.php");
	
	
	if($savedsettings["EnableWebFiltering"]==1){
		EnableWebFiltering();
		shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.update.squid.tlse.php --force >/dev/null 2>&1 &");
		$unix->THREAD_COMMAND_SET("$php5 /usr/share/artica-postfix/exec.squid.php --build --force");
	}
	if($savedsettings["EnableYoutubeCache"]==1){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableHaarp", 1);
		shell_exec("/etc/init.d/haarp restart");
		shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1");
	}

	
	shell_exec("$nohup /etc/init.d/artica-status restart >/dev/null 2>&1 &");
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
	shell_exec("$nohup /etc/init.d/artica-postfix restart artica-status >/dev/null 2>&1 &");
	
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