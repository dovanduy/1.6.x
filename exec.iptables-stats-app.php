<?php
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["EBTABLES"]=false;
$GLOBALS["OUTPUT"]=true;
$GLOBALS["PROGRESS"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');


if($argv[1]=="--delete"){delete();exit;}
if($argv[1]=="--restart"){restart();exit;}

buildScript();

function restart(){
	
	buildScript();
	if(!is_file("/etc/init.d/iptables-statsapp")){return;}
	system("/etc/init.d/iptables-statsapp restart");
}

function buildScript(){
	
	$unix=new unix();
	$sock=new sockets();
	$GLOBALS["echobin"]=$unix->find_program("echo");
	$php=$unix->LOCATE_PHP5_BIN();
	$sh=array();
	
	$parse_rules=parse_rules();
	
	
	
	
	
	if($parse_rules==null){
		script_uninstall();
		return;
	}
	
	$sh[]="#!/bin/sh -e";
	$sh[]="### BEGIN INIT INFO";
	$sh[]="# Builded on ". date("Y-m-d H:i:s");
	$sh[]="# Provides:          fw-stats";
	$sh[]="# Required-Start:    \$local_fs";
	$sh[]="# Required-Stop:     \$local_fs";
	$sh[]="# Should-Start:		";
	$sh[]="# Should-Stop:		";
	$sh[]="# Default-Start:     S";
	$sh[]="# Default-Stop:      0 6";
	$sh[]="# Short-Description: start and stop the Firewall-stats";
	$sh[]="# Description:       Artica Firewall Statistics Appliance";
	$sh[]="### END INIT INFO";
	$sh[]="case \"\$1\" in";
	$sh[]="start)";
	
	$sh[]="if [ ! -f /etc/rsyslog.d/stats-appliance.conf ]; then";
	$sh[]="{$GLOBALS["echobin"]} \"Building syslog configuration\"";
	$sh[]="\t$php /usr/share/artica-postfix/exec.syslog-engine.php --build-server || true";
	$sh[]="\t/etc/init.d/rsyslog restart || true";
	$sh[]="fi";
	
	$sh[]="if [ -f /etc/rsyslog.d/stats-appliance.conf ]; then";
	$sh[]="{$GLOBALS["echobin"]} \"Syslog configuration [OK]\"";
	$sh[]="fi";	
	
	$sh[]="{$GLOBALS["echobin"]} \"Removing Iptables rules\"";
	$sh[]=$php." ".__FILE__." --delete >/dev/null || true";
	$sh[]=$parse_rules;
	$sh[]="{$GLOBALS["echobin"]} \"Iptables rules done.\"";
	$sh[]=";;";
	$sh[]="  stop)";
	$sh[]=$php." ".__FILE__." --delete >/dev/null || true";
	$sh[]=";;";
	$sh[]="  reconfigure)";
	$sh[]="{$GLOBALS["echobin"]} \"Removing Iptables rules\"";
	$sh[]=$php." ".__FILE__." --delete >/dev/null || true";
	$sh[]=$php." ".__FILE__." >/dev/null || true";
	$sh[]="{$GLOBALS["echobin"]} \"Iptables rules done.\"";
	$sh[]="{$GLOBALS["echobin"]} \"Running builded script\"";
	$sh[]="/etc/init.d/iptables-statsapp start";
	$sh[]=";;";
	
	$sh[]="  restart)";
	$sh[]="{$GLOBALS["echobin"]} \"Removing Iptables rules\"";
	$sh[]=$php." ".__FILE__." --delete >/dev/null || true";
	$sh[]=$php." ".__FILE__." >/dev/null || true";
	$sh[]="{$GLOBALS["echobin"]} \"Iptables rules done.\"";
	$sh[]="{$GLOBALS["echobin"]} \"Running builded script\"";
	$sh[]="/etc/init.d/iptables-statsapp start";
	$sh[]=";;";
	
	
	$sh[]="*)";
	$sh[]=" echo \"Usage: $0 {start ,restart,configure or stop only}\"";
	$sh[]="exit 1";
	$sh[]=";;";
	$sh[]="esac";
	$sh[]="exit 0\n";
	@file_put_contents("/etc/init.d/iptables-statsapp", @implode("\n", $sh));
	script_install();
}






function parse_rules(){
	$unix=new unix();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$InfluxAdminPort=intval($sock->GET_INFO("InfluxAdminPort"));
	if($InfluxAdminPort==0){$InfluxAdminPort=8083;}
	$iptables=$unix->find_program("iptables");
	$c=0;
	$sql="SELECT * FROM influxIPClients";
	$results = $q->QUERY_SQL($sql);
	
	if(mysql_num_rows($results)==0){return null;}
		
		$f[]="\t$iptables -I INPUT -p tcp --destination-port 8086 -j REJECT --reject-with tcp-reset -m comment --comment \"ArticaStatsAppliance\" || true";
		$f[]="\t$iptables -I INPUT -p tcp --destination-port $InfluxAdminPort -j REJECT --reject-with tcp-reset -m comment --comment \"ArticaStatsAppliance\" || true";
		$f[]="\t$iptables -I INPUT -s 127.0.0.1 -p tcp --destination-port 8086 -j ACCEPT -m comment --comment \"ArticaStatsAppliance\" || true";
		$f[]="\t$iptables -I INPUT -s 127.0.0.1 -p tcp --destination-port $InfluxAdminPort -j ACCEPT -m comment --comment \"ArticaStatsAppliance\" || true";
	
		
	$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
	
	while (list ($interface, $ligne) = each ($NETWORK_ALL_INTERFACES) ){
		$IPADDR=$ligne["IPADDR"];
		if($interface=="lo"){continue;}
		$f[]="\t$iptables -I INPUT -s $IPADDR -p tcp --destination-port 8086 -j ACCEPT -m comment --comment \"ArticaStatsAppliance\" || true";
		$f[]="\t$iptables -I INPUT -s $IPADDR -p tcp --destination-port $InfluxAdminPort -j ACCEPT -m comment --comment \"ArticaStatsAppliance\" || true";
	}
			
		
	$Ipclass=new IP();
	while ($ligne = mysql_fetch_assoc($results)) {
		$ipaddr=$ligne["ipaddr"];
		$isServ=intval($ligne["isServ"]);
		
		if(!$Ipclass->isIPAddressOrRange($ipaddr)){continue;}
		
		if($isServ==1){
			$f[]="\t$iptables -I INPUT -s $ipaddr -p tcp --destination-port 8086 -j ACCEPT -m comment --comment \"ArticaStatsAppliance\" || true";
		}else{
			$f[]="\t$iptables -I INPUT -s $ipaddr -p tcp --destination-port $InfluxAdminPort -j ACCEPT -m comment --comment \"ArticaStatsAppliance\" || true";
		}
		
		
	}
	

	return @implode("\n", $f);
	
}

function script_install(){


	@chmod("/etc/init.d/iptables-statsapp",0755);
	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f iptables-statsapp defaults >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add iptables-statsapp >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 1234 iptables-statsapp on >/dev/null 2>&1");
	}

}

function script_uninstall(){
	
	if(!is_file("/etc/init.d/iptables-statsapp")){return;}
	
	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f iptables-statsapp remove >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --del iptables-statsapp >/dev/null 2>&1");
		
	}
	@unlink("/etc/init.d/iptables-statsapp");
	
}
function delete(){
	$d=0;
	$unix=new unix();
	$iptables_save=$unix->find_program("iptables-save");
	$iptables_restore=$unix->find_program("iptables-restore");
	system("$iptables_save > /etc/artica-postfix/iptables2.conf");
	$data=file_get_contents("/etc/artica-postfix/iptables2.conf");
	$datas=explode("\n",$data);
	$pattern2="#.+?ArticaStatsAppliance#";

	
	while (list ($num, $ligne) = each ($datas) ){
		if($ligne==null){continue;}
		if(preg_match($pattern2,$ligne)){$d++;continue;}

		$conf=$conf . $ligne."\n";
	}
	file_put_contents("/etc/artica-postfix/iptables.new2.conf",$conf);
	system("$iptables_restore < /etc/artica-postfix/iptables.new2.conf");
	echo "Starting......: ".date("H:i:s")." Removing $d iptables rule(s) done...\n";

}