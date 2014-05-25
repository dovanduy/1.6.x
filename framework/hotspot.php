<?php
$GLOBALS["CACHE_FILE"]="/etc/artica-postfix/iptables-hostspot.conf";
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["release-mac-period"])){release_mac_period();exit;}
if(isset($_GET["release-mac"])){release_mac();exit;}
if(isset($_GET["services-status"])){services_status();exit;}
if(isset($_GET["restart-firewall"])){restart_firewall();exit;}
if(isset($_GET["stop-firewall"])){stop_firewall();exit;}
if(isset($_GET["stop-web"])){stop_web();exit;}
if(isset($_GET["restart-web"])){restart_web();exit;}
if(isset($_GET["remove-session"])){remove_session();exit;}
if(isset($_GET["ArticaHotSpotInterface"])){ArticaHotSpotInterface();exit;}

while (list ($num, $line) = each ($_GET)){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die();

function release_mac_period(){
	$ip=$_GET["ipaddr"];
	$minutesToAdd=$_GET["release-mac-period"];
	$mac=$_GET["MAC"];
	
	$unix=new unix();
	$iptables=$unix->find_program("iptables");
	$md5key=$_GET["md5key"];

	$IPADDRS=$unix->NETWORK_ALL_INTERFACES(true);
	if(isset($IPADDRS[$ip])){return;}

	$squid_http_port=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaHotSpotPort");
	$squid_ssl_port=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaSSLHotSpotPort");
	$ArticaSSLHotSpotPort=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaSSLHotSpotPort");
	$HotSpotAsBridge=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/HotSpotAsBridge"));


	
	
	
	$time=time();
	$startTime = date("H:i");
	$endTime = date("H:i", strtotime("+$minutesToAdd minutes", $time));
	$datestop=date("Y-m-d")."T$endTime";
	$xtime="-m time --timestart $startTime --timestop $endTime --datestop $datestop";

	if($md5key==null){
		$md5key=md5("$startTime$endTime$ip$mac");
	}
	
	$time=time();
	$suffixTables="-m comment --comment \"HotSpotSession-$md5key\"";
	$mark_http="-m mark --mark 99";
	$mark_https="-m mark --mark 98";
	
	if($HotSpotAsBridge==1){$mark_http=null;$mark_https=null;}
	
	if($HotSpotAsBridge==0){$f[]="$iptables -t mangle -I PREROUTING -m mac --mac-source $mac -p tcp -m tcp --dport 80 -j internet -m comment --comment HotSpotSession-$md5key";}
	$f[]="$iptables -t nat -I PREROUTING -m mac --mac-source $mac -p tcp $mark_http -m tcp --dport 80 -j REDIRECT --to-port $squid_http_port $xtime -m comment --comment HotSpotSession-$md5key";

	if($HotSpotAsBridge==0){$f[]="$iptables -t mangle -I PREROUTING -m mac --mac-source $mac -p tcp -m tcp --dport 443 -j internssl -m comment --comment HotSpotSession-$md5key";}
	$f[]="$iptables -t nat -I PREROUTING -m mac --mac-source $mac -p tcp $mark_https -m tcp --dport 443 -j REDIRECT --to-port $squid_ssl_port $xtime -m comment --comment HotSpotSession-$md5key";

	while (list ($num, $ligne) = each ($f) ){
		writelogs_framework($ligne,__FUNCTION__,__FILE__,__LINE__);
		shell_exec($ligne);
	}

	$php=$unix->LOCATE_PHP5_BIN();
	$conntrack=$unix->find_program("conntrack");
	shell_exec("$conntrack -D -s $ip");
	shell_exec("$php /usr/share/artica-postfix/exec.squid.php --kreconfigure");
}

function release_mac(){
	$ip=$_GET["ip"];
	$mac=$_GET["release-mac"];
	$unix=new unix();
	$iptables=$unix->find_program("iptables");
	$md5key=$_GET["md5key"];
	
	$IPADDRS=$unix->NETWORK_ALL_INTERFACES(true);
	if(isset($IPADDRS[$ip])){return;}
	
	$squid_http_port=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaHotSpotPort");
	$squid_ssl_port=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaSSLHotSpotPort");
	$ArticaSSLHotSpotPort=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaSSLHotSpotPort");
	$HotSpotAsBridge=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/HotSpotAsBridge"));

	
	
	$time=time();
	$suffixTables="-m comment --comment \"HotSpotSession-$md5key\"";
	
	
	if($HotSpotAsBridge==0){$f[]="$iptables -t mangle -I PREROUTING -m mac --mac-source $mac -p tcp -m tcp --dport 80 -j internet -m comment --comment HotSpotSession-$md5key";}
	$f[]="$iptables -t nat -I PREROUTING -m mac --mac-source $mac -p tcp -m mark --mark 99 -m tcp --dport 80 -j REDIRECT --to-port $squid_http_port -m comment --comment HotSpotSession-$md5key";
	
	if($HotSpotAsBridge==0){$f[]="$iptables -t mangle -I PREROUTING -m mac --mac-source $mac -p tcp -m tcp --dport 443 -j internssl -m comment --comment HotSpotSession-$md5key";}
	$f[]="$iptables -t nat -I PREROUTING -m mac --mac-source $mac -p tcp -m mark --mark 98 -m tcp --dport 443 -j REDIRECT --to-port $squid_ssl_port -m comment --comment HotSpotSession-$md5key";
		
	
	
	
	
	
	while (list ($num, $ligne) = each ($f) ){
		writelogs_framework($ligne,__FUNCTION__,__FILE__,__LINE__);
		shell_exec($ligne);
	}
	
	$php=$unix->LOCATE_PHP5_BIN();
	$conntrack=$unix->find_program("conntrack");
	shell_exec("$conntrack -D -s $ip");
	shell_exec("$php /usr/share/artica-postfix/exec.squid.php --kreconfigure");
}

function remove_session(){
	$mac=$_GET["MAC"];
	$ip=$_GET["ip"];
	
	writelogs_framework("MAC: $mac, IP=$ip ",__FUNCTION__,__FILE__,__LINE__);
	
	$unix=new unix();
	$tmp=$unix->TEMP_DIR();
	$iptables_save=$unix->find_program("iptables-save");
	$iptables_restore=$unix->find_program("iptables-restore");
	
	
	shell_exec("$iptables_save > {$GLOBALS["CACHE_FILE"]}");
	$data=file_get_contents($GLOBALS["CACHE_FILE"]);
	$datas=explode("\n",$data);

	$c=0;
	$tab=array();
	while (list ($num, $ligne) = each ($datas) ){
		if($ligne==null){continue;}
		if($mac<>null){
			if(preg_match("#$mac#i",$ligne)){$c++;continue;}
		}
		if($ip<>null){
			if(preg_match("#$ip#i",$ligne)){$c++;continue;}
		}
		$tab[]=$ligne."\n";
	}
	$t=time();
	file_put_contents("$tmp/$t.conf",@implode("\n", $tab));
	system("$iptables_restore < $tmp/$t.conf");
	@unlink("$tmp/$t.conf");
	shell_exec("$iptables_save > {$GLOBALS["CACHE_FILE"]}");
	
	$conntrack=$unix->find_program("conntrack");
	if($ip<>null){
		shell_exec("$conntrack -D -s $ip");
	}
	
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	writelogs_framework("Framework: Remove firewall session $mac/$ip",__FUNCTION__,__FILE__,__LINE__);
	hotspot_admin_mysql(2, "Framework: Remove firewall session $mac/$ip", "Remove firewall session $mac/$ip");
	shell_exec("$nohup $php /usr/share/artica-postfix/exec.artica.hotspot.php --remove-mysql-sessions --force >/dev/null 2>&1 &");
}


function ArticaHotSpotInterface(){
	
	$ArticaHotSpotInterface=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaHotSpotInterface");
	
	$ArticaSplashHotSpotPort=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaSplashHotSpotPort");
	$ArticaSplashHotSpotPortSSL=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaSplashHotSpotPortSSL");
	
	
	
	if(!is_numeric($ArticaSplashHotSpotPort)){$ArticaSplashHotSpotPort=16080;}
	if(!is_numeric($ArticaSplashHotSpotPortSSL)){$ArticaSplashHotSpotPortSSL=16443;}
	
	
	$unix=new unix();
	$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
	
	while (list ($interface, $line) = each ($NETWORK_ALL_INTERFACES) ){
		$IP2=$line["IPADDR"];
		if($interface=="lo"){continue;}
		if($IP2==null){continue;}
		if($IP2=="0.0.0.0"){continue;}
		$AVAIINT[]=$interface;
	}
	
	if($ArticaHotSpotInterface==null){$ArticaHotSpotInterface=$AVAIINT[0];}
	
	
	$ipaddr=trim($NETWORK_ALL_INTERFACES[$ArticaHotSpotInterface]["IPADDR"]);
	
	writelogs_framework("ArticaHotSpotInterface = $ArticaHotSpotInterface IPADDR:$ipaddr",__FUNCTION__,__FILE__,__LINE__);
	
	if( ($ipaddr=="0.0.0.0") OR ($ipaddr==null)){
		$ArticaHotSpotInterface=$AVAIINT[0];
		writelogs_framework("NEw ArticaHotSpotInterface = {$AVAIINT[0]}",__FUNCTION__,__FILE__,__LINE__);
		$ipaddr=$NETWORK_ALL_INTERFACES[$ArticaHotSpotInterface]["IPADDR"];
		
	}
	
	writelogs_framework("http://$ipaddr:$ArticaSplashHotSpotPort/hotspot.php",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>http://$ipaddr:$ArticaSplashHotSpotPort/hotspot.php</articadatascgi>";
	
}

function restart_firewall(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$nohup $php /usr/share/artica-postfix/exec.artica.hotspot.php --restart >/dev/null 2>&1 &");
	
}

function stop_firewall(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$nohup $php /usr/share/artica-postfix/exec.artica.hotspot.php --stop >/dev/null 2>&1 &");
	shell_exec("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");
}
function stop_web(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$nohup $php /usr/share/artica-postfix/exec.hostpot-web.php --stop >/dev/null 2>&1 &");
	shell_exec("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");
}

function restart_web(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$nohup $php /usr/share/artica-postfix/exec.hostpot-web.php --restart >/dev/null 2>&1 &");
	shell_exec("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");
}
function services_status(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	exec("$php /usr/share/artica-postfix/exec.status.php --hotspot 2>&1",$results);
	echo "<articadatascgi>". base64_encode(@implode("\n", $results))."</articadatascgi>";
	
}



