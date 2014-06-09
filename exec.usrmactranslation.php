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

if(is_array($argv)){if(preg_match("#--update#",implode(" ",$argv))){$GLOBALS["UPDATE"]=true;}}
if(is_array($argv)){if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}}



if($argv[1]=='--build'){build();die();}



build();

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
	
	$sock=new sockets();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");	
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	
	if($EnableRemoteStatisticsAppliance==1){download_mydb();return;}
	if(!function_exists("IsPhysicalAddress")){include_once(dirname(__FILE__)."/ressources/class.templates.inc");}
	if(!class_exists("mysql_squid_builder")){include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");}
	
	
	$unix=new unix();
	$arpd=$unix->find_program("arpd");
	$chmod=$unix->find_program("chmod");
	if(is_file($arpd)){
		exec("$arpd -l 2>&1",$results);
		while (list ($num, $line) = each ($results)){
			if(preg_match("#([0-9]+)\s+([0-9\.]+)\s+([0-9a-z\:]+)#", $line,$re)){
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
			$MACS["MACS"][$ligne["MacAddress"]]["UID"]=$re[1];
			UPDATE_HOURS_MAC($ligne["MacAddress"],$re[1]);
			continue;
		}
		UPDATE_HOURS_MAC($ligne["MacAddress"],$ligne["uid"]);
		$MACS["MACS"][$ligne["MacAddress"]]["UID"]=$ligne["uid"];
		
	}
	
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM webfilters_ipaddr WHERE LENGTH(uid)>1";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		$MACS["MACS"][$ligne["ipaddr"]]["UID"]=$ligne["uid"];
		UPDATE_HOURS_IP($ligne["ipaddr"],$ligne["uid"]);
		if($ligne["hostname"]<>null){$MACS["MACS"][$ligne["ipaddr"]]["HOST"]=$ligne["hostname"];}
	}
	
	
	$CountDeMac=count($MACS["MACS"]);
	$CountDeIP=count($MACS["IPS"]);
	
	@file_put_contents("/etc/squid3/usersMacs.db", serialize($MACS));
	@file_put_contents("/usr/share/artica-postfix/ressources/databases/usersMacs.db",serialize($MACS));
	shell_exec("$chmod 755 /etc/squid3/usersMacs.db");
	shell_exec("$chmod 755 /usr/share/artica-postfix/ressources/databases/usersMacs.db");
	
	if($CountDeMac==0){
		if($CountDeIP==0){
			return;
		}
	}
	
	if($EnableWebProxyStatsAppliance==1){notify_remote_proxys_usersMacs();return;}
	squid_admin_mysql(2, "Translation members database updated $CountDeMac MACs, $CountDeIP Ips", null,__FILE__,__LINE__);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	squid_admin_mysql(1, "Ask to reload the proxy after update Translation members database", null,__FILE__,__LINE__);
	$cmd="$nohup $php5 ". dirname(__FILE__)."/exec.squid.php --reconfigure-squid --force >/dev/null 2>&1 &";
	shell_exec($cmd);
	
}

function UPDATE_HOURS_MAC($MAC,$name){
	if(!$GLOBALS["UPDATE"]){return;}
	$q=new mysql_squid_builder();
	$LIST_TABLES_HOURS_TEMP=$q->LIST_TABLES_HOURS_TEMP();
	
	while (list ($tablename, $ligne) = each ($LIST_TABLES_HOURS_TEMP) ){
		$q->QUERY_SQL("UPDATE $tablename SET `uid`='$name' WHERE MAC='$MAC'");
		
	}
}
function UPDATE_HOURS_IP($IP,$name){
	if(!$GLOBALS["UPDATE"]){return;}
	$q=new mysql_squid_builder();
	$LIST_TABLES_HOURS_TEMP=$q->LIST_TABLES_HOURS_TEMP();

	while (list ($tablename, $ligne) = each ($LIST_TABLES_HOURS_TEMP) ){
		$q->QUERY_SQL("UPDATE $tablename SET `uid`='$name' WHERE `client`='$IP'");

	}
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