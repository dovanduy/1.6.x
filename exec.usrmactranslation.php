<?php
if(is_array($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
include_once(dirname(__FILE__)."/ressources/class.groups.inc");
$GLOBALS["UPDATE"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["PROGRESS"]=false;
$GLOBALS["HOTSPOT"]=false;

if(is_array($argv)){if(preg_match("#--hotspot#",implode(" ",$argv))){$GLOBALS["HOTSPOT"]=true;}}
if(is_array($argv)){if(preg_match("#--update#",implode(" ",$argv))){$GLOBALS["UPDATE"]=true;}}
if(is_array($argv)){if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}}
if(is_array($argv)){if(preg_match("#--progress#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["UPDATE"]=true;$GLOBALS["PROGRESS"]=true;}}



if($argv[1]=='--build'){build();die();}
if($argv[1]=='--reload'){ReloadMacHelpers(true);die();}



build();

function build_progress($text,$pourc){
	if(!$GLOBALS["PROGRESS"]){return;}
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/squid.macToUid.progress", serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);
	usleep(500);
}

function build(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".time";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,__FILE__)){echo "Already PID running $pid (".basename(__FILE__).")\n";die();}
	
	$time=$unix->file_time_min($timefile);
	
	if(!$GLOBALS["FORCE"]){if($time<5){
		if($GLOBALS["VERBOSE"]){echo "{$time}mn < 5mn\n";}
		die();}}
	
	@mkdir(dirname($pidfile),0755,true);
	@file_put_contents($pidfile, getmypid());
	@unlink($timefile);
	@file_put_contents($timefile, time());
	$php=$unix->LOCATE_PHP5_BIN();	
	
	$MD5_SRC=@md5_file("/etc/squid3/usersMacs.db");
	@unlink("/etc/squid3/usersMacs.db");
	@unlink("/usr/share/artica-postfix/ressources/databases/usersMacs.db");
	
	$sock=new sockets();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");	
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	
	if($EnableRemoteStatisticsAppliance==1){download_mydb();return;}
	if(!function_exists("IsPhysicalAddress")){include_once(dirname(__FILE__)."/ressources/class.templates.inc");}
	if(!class_exists("mysql_squid_builder")){include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");}
	
	build_progress("{starting}",10);
	
	$unix=new unix();
	$arpd=$unix->find_program("arpd");
	$chmod=$unix->find_program("chmod");
	if(is_file($arpd)){
		exec("$arpd -l 2>&1",$results);
		while (list ($num, $line) = each ($results)){
			if(preg_match("#([0-9]+)\s+([0-9\.]+)\s+([0-9a-z\:]+)#", $line,$re)){
				build_progress("{$re[3]} = {$re[2]}",15);
				$MACS["MACS"][$re[3]]["IP"]=$re[2];
				$MACS["IPS"][$re[2]]=$re[3];
			}
			
		}
	}

	$q=new mysql_squid_builder();
	$sql="SELECT * FROM webfilters_nodes WHERE LENGTH(uid)>1";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		if($ligne["MAC"]=="00:00:00:00:00:00"){continue;}
		if(!IsPhysicalAddress($ligne["MAC"])){continue;}
		if($GLOBALS["VERBOSE"]){echo "{$ligne["MAC"]} = {$ligne["uid"]}\n";}
		$MACS["MACS"][$ligne["MAC"]]["UID"]=$ligne["uid"];
		$MACS["MACS"][$ligne["MAC"]]["GROUP"]=$ligne["group"];
		build_progress($ligne["MAC"],20);
		UPDATE_HOURS_MAC($ligne["MAC"],$ligne["uid"]);
		if($ligne["hostname"]<>null){$MACS["MACS"][$ligne["MAC"]]["HOST"]=$ligne["hostname"];}
	}
	
	$q=new mysql();
	$sql="SELECT * FROM hostsusers";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		if($ligne["MacAddress"]=="00:00:00:00:00:00"){continue;}
		if(!IsPhysicalAddress($ligne["MacAddress"])){continue;}
		if($GLOBALS["VERBOSE"]){echo "{$ligne["MacAddress"]} = {$ligne["uid"]}\n";}
		if(preg_match("#group:@(.+?):([0-9]+)#", $ligne["uid"],$re)){
			build_progress($ligne["MacAddress"],30);
			$MACS["MACS"][$ligne["MacAddress"]]["UID"]=$re[1];
			UPDATE_HOURS_MAC($ligne["MacAddress"],$re[1]);
			continue;
		}
		UPDATE_HOURS_MAC($ligne["MacAddress"],$ligne["uid"],30);
		$MACS["MACS"][$ligne["MacAddress"]]["UID"]=$ligne["uid"];
		
	}
	
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM webfilters_ipaddr WHERE LENGTH(uid)>1";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		build_progress($ligne["ipaddr"],40);
		$MACS["MACS"][$ligne["ipaddr"]]["UID"]=$ligne["uid"];
		$MACS["MACS"][$ligne["ipaddr"]]["GROUP"]=$ligne["group"];
		UPDATE_HOURS_IP($ligne["ipaddr"],$ligne["uid"],40);
		if($ligne["hostname"]<>null){$MACS["MACS"][$ligne["ipaddr"]]["HOST"]=$ligne["hostname"];}
	}
	
	$q=new mysql_squid_builder();
	$sql="SELECT uid,MAC,ipaddr FROM hotspot_sessions WHERE LENGTH(uid)>1";
	$results = $q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		$MACS["MACS"][$ligne["MAC"]]["UID"]=$ligne["uid"];
		$MACS["MACS"][$ligne["MAC"]]["GROUP"]="hotspot";
		$MACS["MACS"][$ligne["ipaddr"]]["UID"]=$ligne["uid"];
		$MACS["MACS"][$ligne["ipaddr"]]["GROUP"]="hotspot";
	}
	
	
	
	$CountDeMac=count($MACS["MACS"]);
	$CountDeIP=count($MACS["IPS"]);
	build_progress("{saving}...",50);
	@file_put_contents("/etc/squid3/usersMacs.db", serialize($MACS));
	$MD5_DEST=@md5_file("/etc/squid3/usersMacs.db");
	
	@file_put_contents("/usr/share/artica-postfix/ressources/databases/usersMacs.db",serialize($MACS));
	shell_exec("$chmod 755 /etc/squid3/usersMacs.db");
	shell_exec("$chmod 755 /usr/share/artica-postfix/ressources/databases/usersMacs.db");
	
	if($CountDeMac==0){
		if($CountDeIP==0){
			@unlink("/etc/squid3/usersMacs.db");
			if(IfInSquidConf()){
				build_progress("{reconfigure_proxy_service}...",80);
				shell_exec("$php /usr/share/artica-postfix/exec.squid.php --build --force");
			}
			build_progress("{done} no item...",100);
			return;
		}
	}
	
	
	
	if($MD5_DEST==$MD5_SRC){
		build_progress("{done}...",100);
		return;
	}
	
	build_progress("$CountDeMac MACs, $CountDeIP Ips",70);
	squid_admin_mysql(2, "Translation members database updated $CountDeMac MACs, $CountDeIP Ips", null,__FILE__,__LINE__);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	if(!IfInSquidConf()){
		build_progress("{reconfigure_proxy_service}...",80);
		shell_exec("$php /usr/share/artica-postfix/exec.squid.php --build --force");
		build_progress("{done}...",100);
		return;
	}
	
	build_progress("{reloading}...",80);
	ReloadMacHelpers();
	build_progress("{done}...",100);
	
}

function ReloadMacHelpers($output=false){
	$unix=new unix();
	@mkdir("/var/log/squid/reload",0755,true);
	@chown("/var/log/squid/reload","squid");
	@chgrp("/var/log/squid/reload", "squid");
	$unix=new unix();
	$pgrep=$unix->find_program("pgrep");
	$rm=$unix->find_program("rm");
	shell_exec("$rm /var/log/squid/reload/*.MACTOUID >/dev/null 2>&1");
	
	exec("$pgrep -l -f \"external_acl_usersMacs.php\" 2>&1",$results);
	
	while (list ($index, $ligne) = each ($results) ){
		if(preg_match("#pgrep#", $ligne)){continue;}
		if(!preg_match("#^([0-9]+)\s+#", $ligne,$re)){continue;}
		if($output){echo "Reload PID {$re[1]}\n";}
		@touch("/var/log/squid/reload/{$re[1]}.MACTOUID");
		@chown("/var/log/squid/reload/{$re[1]}.MACTOUID","squid");
		@chgrp("/var/log/squid/reload/{$re[1]}.MACTOUID", "squid");
		
	}
	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	shell_exec("$squidbin -f /etc/squid3/squid.conf -k reconfigure >/dev/null 2>&1");
}


function IfInSquidConf(){
	
	$f=explode("\n",@file_get_contents("/etc/squid3/external_acls.conf"));
	
	while (list ($num, $line) = each ($f)){
		if(preg_match("#external_acl_usersMacs\.php#", $line)){
			return true;
		}
		
	}
	
	return false;
	
}


function UPDATE_HOURS_MAC($MAC,$name,$prc){

}
function UPDATE_HOURS_IP($IP,$name){

}
function download_mydb(){
	$sock=new sockets();
	$unix=new unix();
	$chmod=$unix->find_program("chmod");
	$squidbin=$unix->find_program("squid3");
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid");}
	if(!is_file($squidbin)){return;}		
	$RemoteStatisticsApplianceSettings=unserialize(base64_decode($sock->GET_INFO("RemoteStatisticsApplianceSettings")));
	if(!is_numeric($RemoteStatisticsApplianceSettings["SSL"])){$RemoteStatisticsApplianceSettings["SSL"]=1;}
	if(!is_numeric($RemoteStatisticsApplianceSettings["PORT"])){$RemoteStatisticsApplianceSettings["PORT"]=9000;}
	$GLOBALS["REMOTE_SSERVER"]=$RemoteStatisticsApplianceSettings["SERVER"];
	$GLOBALS["REMOTE_SPORT"]=$RemoteStatisticsApplianceSettings["PORT"];
	$GLOBALS["REMOTE_SSL"]=$RemoteStatisticsApplianceSettings["SSL"];
	if($GLOBALS["REMOTE_SSL"]==1){$refix="https";}else{$refix="http";}
	$baseUri="$refix://{$GLOBALS["REMOTE_SSERVER"]}:{$GLOBALS["REMOTE_SPORT"]}/ressources/databases";	
	$uri="$baseUri/usersMacs.db";
	$curl=new ccurl($uri,true);
	if($curl->GetFile("/etc/squid3/usersMacs.db")){
		shell_exec("$chmod 755 /etc/squid3/usersMacs.db");
		ufdbguard_admin_events("download usersMacs.db success",__FUNCTION__,__FILE__,__LINE__,"global-compile");
	}else{
		ufdbguard_admin_events("Failed to download ufdbGuard.conf aborting `$curl->error`",__FUNCTION__,__FILE__,__LINE__,"global-compile");
		return;			
	}
	$cmd="/etc/init.d/squid reload --script=".basename(__FILE__);
	shell_exec("$cmd >/dev/null 2>&1");

		
}

function notify_remote_proxys_usersMacs(){
	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM squidservers";
	$results=$q->QUERY_SQL($sql);
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$server=$ligne["ipaddr"];
		$port=$ligne["port"];
		writelogs("remote server $server:$port",__FUNCTION__,__FILE__,__LINE__);
		if(!is_numeric($port)){continue;}
		$refix="https";
		$uri="$refix://$server:$port/squid.stats.listener.php";
		$curl=new ccurl($uri,true);
		$curl->parms["CHANGE_CONFIG"]="USERSMAC";
		if(!$curl->get()){squidstatsApplianceEvents("$server:$port","FAILED Notify change it`s configuration $curl->error for USERSMAC");continue;}
		if(preg_match("#<ANSWER>OK</ANSWER>#is",$curl->data)){squidstatsApplianceEvents("$server:$port","SUCCESS to notify change it`s configuration for USERSMAC");continue;}
		squidstatsApplianceEvents("$server:$port","FAILED Notify change it`s configuration $curl->data for USERSMAC");
	}
}