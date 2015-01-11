<?php
die();
if(is_file("/etc/artica-postfix/FROM_ISO")){if(is_file("/etc/init.d/artica-cd")){print "Starting......: ".date("H:i:s")." artica-". basename(__FILE__)." Waiting Artica-CD to finish\n";die();}}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["MARKHTTP"]="-m mark --mark 99";
$GLOBALS["MARKHTTPS"]="-m mark --mark 98";

$GLOBALS["SERVICE_NAME"]="HotSpot FireWall service";
$GLOBALS["TITLENAME"]="HotSpot FireWall service";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/smtp/smtp.php');

$GLOBALS["ARGVS"]=implode(" ",$argv);
$GLOBALS["CACHE_FILE"]="/etc/artica-postfix/iptables-hostspot.conf";
if($argv[1]=="--test-mail"){testmail();exit;}
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;build();die();}
if($argv[1]=="--remove-sessions"){$GLOBALS["OUTPUT"]=true;remove_sessions();die();}
if($argv[1]=="--untrack"){$GLOBALS["OUTPUT"]=true;untrack();die();}
if($argv[1]=="--remove-mysql-sessions"){$GLOBALS["OUTPUT"]=true;remove_mysql_sessions();die();}

function restart(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "ReStarting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	stop(true);
	if($GLOBALS["OUTPUT"]){echo "Restarting....: [INIT]: {$GLOBALS["TITLENAME"]} Removing sessions\n";}
	remove_sessions();
	if($GLOBALS["OUTPUT"]){echo "Restarting....: [INIT]: {$GLOBALS["TITLENAME"]} Building rules\n";}
	build();
	start(true);
	if($GLOBALS["OUTPUT"]){echo "Restarting....: [INIT]: {$GLOBALS["TITLENAME"]} Success starting PID 0\n";}
	
}


function stop($aspid=false){
	$unix=new unix();
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	
	$tmp=$unix->TEMP_DIR();
	$iptables_save=$unix->find_program("iptables-save");
	$iptables_restore=$unix->find_program("iptables-restore");
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} {$GLOBALS["CACHE_FILE"]}\n";}
	shell_exec("$iptables_save > {$GLOBALS["CACHE_FILE"]}");
	$data=file_get_contents($GLOBALS["CACHE_FILE"]);
	$datas=explode("\n",$data);
	$pattern="#.+?ArticaHotSpot#i";
	$c=0;
	while (list ($num, $ligne) = each ($datas) ){
		if($ligne==null){continue;}
		if(preg_match($pattern,$ligne)){$c++;continue;}
		if(preg_match("#:internet\s+#", $ligne)){$c++;continue;}
		if(preg_match("#:internssl\s+#i", $ligne)){$c++;continue;}
		if(preg_match("#ArticaHotSpot-[0-9]+\s+-j MARK#i", $ligne)){$c++;continue;}
		$conf=$conf . $ligne."\n";
	}
	$t=time();
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Removing $c line(s)\n";}
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Restoring datas iptables-restore < /etc/artica-postfix/iptables-hostspot.conf\n";}
	file_put_contents("$tmp/$t.conf",$conf);
	system("$iptables_restore < $tmp/$t.conf");
	shell_exec("$iptables_save > {$GLOBALS["CACHE_FILE"]}");
	@unlink("$tmp/$t.conf");
	remove_sessions();
	untrack();
}

function start($aspid=false){
	$unix=new unix();
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	
	
	$sock=new sockets();
	$iptables=$unix->find_program("iptables");
	$sysctl=$unix->find_program("sysctl");
	$ips=$unix->ifconfig_interfaces_list();
	$KernelSendRedirects=$sock->GET_INFO("KernelSendRedirects");
	if(!is_numeric($KernelSendRedirects)){$KernelSendRedirects=1;}
	$modprobe=$unix->find_program("modprobe");
	

	
	
	$EnableArticaHotSpot=$sock->GET_INFO("EnableArticaHotSpot");
	if(!is_numeric($EnableArticaHotSpot)){$EnableArticaHotSpot=0;}
	
	if($EnableArticaHotSpot==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} disabled ( see EnableArticaHotSpot )\n";}
		remove_sessions();
		remove_temp_sessions();
		return;
	}
	
	$squid=$unix->LOCATE_SQUID_BIN();
	if(!is_file($squid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Squid-cache not installed\n";}
		return;
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} loading ip_conntrack module\n";}
	shell_exec("$modprobe ip_conntrack");
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} building firewall\n";}
	
	if(!is_file("/etc/artica-postfix/hotspot.conf")){build();}
	$f=explode("\n",@file_get_contents("/etc/artica-postfix/hotspot.conf"));
	
	while (list ($num, $ligne) = each ($f) ){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} `$ligne`\n";}
		shell_exec($ligne);
		
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Patching kernel\n";}
	shell_exec("$sysctl -w net.ipv4.ip_forward=1 2>&1");
	shell_exec("$sysctl -w net.ipv4.conf.default.send_redirects=$KernelSendRedirects 2>&1");
	shell_exec("$sysctl -w net.ipv4.conf.all.send_redirects=$KernelSendRedirects 2>&1");
	shell_exec("$sysctl -w net.ipv4.conf.eth0.send_redirects=$KernelSendRedirects 2>&1");
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Delete session..\n";}
	untrack();
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Success started PID 0\n";}
}

function ebtables_rules(){
	$unix=new unix();
	$sock=new sockets();
	$ebtables=$unix->find_program("ebtables");
	$aptget=$unix->find_program("apt-get");
	if($GLOBALS["VERBOSE"]){echo "EBTABLES: [".__LINE__."] ebtables = $ebtables\n";}
	$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
	$squid=new squidbee();
	$SSL_BUMP=$squid->SSL_BUMP;
	if($GLOBALS["VERBOSE"]){echo "EBTABLES: [".__LINE__."] SSL_BUMP = $SSL_BUMP\n";}
	if(!is_file($ebtables)){
		if($GLOBALS["VERBOSE"]){echo "EBTABLES: [".__LINE__."] NO BINARY apt-get = \"$aptget\"\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." ebtables no such binary...\n";}
		$unix->DEBIAN_INSTALL_PACKAGE("ebtables");
	}
	$conntrack=$unix->find_program("conntrack");
	if(!is_file($conntrack)){
		$unix->DEBIAN_INSTALL_PACKAGE("conntrack");
	}

	$ebtables=$unix->find_program("ebtables");

	if(!is_file($ebtables)){return "# ebtables, no such binary"; }

	$q=new mysql();

	$sql="SELECT `Interface` FROM `nics` WHERE `Bridged`=1";
	if($GLOBALS["VERBOSE"]){echo "[".__LINE__."] $sql\n";}
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "$q->mysql_error\n";}
	$count=mysql_num_rows($results);
	if($count==0){
		$sock->SET_INFO("HotSpotAsBridge", 0);
		return "# ebtables, no bridge defined...";
	}
	$sock->SET_INFO("HotSpotAsBridge", 1);
	$GLOBALS["EBTABLES"]=true;
	if($GLOBALS["VERBOSE"]){echo "[".__LINE__."] EBTABLES = TRUE\n";}
	//iptables -t nat -A PREROUTING -i br0 -p tcp --dport 80 -j REDIRECT --to-port 3128
	$f[]="# ebtables, $count Interfaces SSL_BUMP = $SSL_BUMP";
	$f[]="$ebtables -t broute -A BROUTING -p IPv4 --ip-protocol 6 --ip-destination-port 80 -j redirect --redirect-target ACCEPT";
	if($SSL_BUMP==1){
		$f[]="$ebtables -t broute -A BROUTING -p IPv4 --ip-protocol 6 --ip-destination-port 443 -j redirect --redirect-target ACCEPT";

	}
	return @implode("\n", $f);
}
	
function build(){
	
	$sock=new sockets();
	$unix=new unix();
	
	$EnableArticaHotSpot=$sock->GET_INFO("EnableArticaHotSpot");
	$SquidHotSpotPort=$sock->GET_INFO("SquidHotSpotPort");
	$ArticaHotSpotPort=$sock->GET_INFO("ArticaHotSpotPort");
	$ArticaSSLHotSpotPort=$sock->GET_INFO("ArticaSSLHotSpotPort");
	$ArticaSplashHotSpotPort=$sock->GET_INFO("ArticaSplashHotSpotPort");
	$ArticaSplashHotSpotPortSSL=$sock->GET_INFO("ArticaSplashHotSpotPortSSL");
	$ArticaHotSpotInterface=$sock->GET_INFO("ArticaHotSpotInterface");
	$EnableArticaHotSpotCAS=$sock->GET_INFO("EnableArticaHotSpotCAS");
	if(!is_numeric($EnableArticaHotSpotCAS)){$EnableArticaHotSpotCAS=0;}
	if($ArticaHotSpotPort==0){
		$ArticaHotSpotPort=rand(38000, 64000);
		$sock->SET_INFO("ArticaHotSpotPort", $ArticaHotSpotPort);
	}
	
	if($ArticaSSLHotSpotPort==0){
		$ArticaSSLHotSpotPort=rand(38500, 64000);
		$sock->SET_INFO("ArticaSSLHotSpotPort", $ArticaSSLHotSpotPort);
	}
	
	if($ArticaHotSpotInterface==null){$ArticaHotSpotInterface="eth0";}
	if(!is_numeric($ArticaSplashHotSpotPort)){$ArticaSplashHotSpotPort=16080;}
	if(!is_numeric($ArticaSplashHotSpotPortSSL)){$ArticaSplashHotSpotPortSSL=16443;}
	
	$unix=new unix();
	$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
	$ipaddr=$NETWORK_ALL_INTERFACES[$ArticaHotSpotInterface]["IPADDR"];
	$GLOBALS["HOSTPOT_WEB_INTERFACE"]=$ipaddr;
	$time=time();
	
	$suffixTables="-m comment --comment \"ArticaHotSpot-$time\"";
	$q=new mysql_squid_builder();
	$ipClass=new IP();
	$iptables=$unix->find_program("iptables");
	defaults_ports();
	
	$f[]=ebtables_rules();
	if($GLOBALS["EBTABLES"]){
		$GLOBALS["MARKHTTP"]=null;
		$GLOBALS["MARKHTTPS"]=null;
	}
	
	if(!$GLOBALS["EBTABLES"]){
		$f[]="$iptables -t mangle -N internet -m comment --comment ArticaHotSpot-$time";
		$f[]="$iptables -t mangle -N internssl -m comment --comment ArticaHotSpot-$time";
		$f[]="$iptables -t mangle -A internet -j MARK --set-mark 99 -m comment --comment ArticaHotSpot-$time";
		$f[]="$iptables -t mangle -A internssl -j MARK --set-mark 98 -m comment --comment ArticaHotSpot-$time";
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} C.A.S : $EnableArticaHotSpotCAS\n";}
	$Squid_http_address="127.0.0.1:$ArticaHotSpotPort";
	$webserver_http_address="$ipaddr:$ArticaSplashHotSpotPort";
	$c=0;
	
	if($EnableArticaHotSpotCAS==1){
		$ArticaHotSpotCASHost=$sock->GET_INFO("ArticaHotSpotCASHost");
		$ArticaHotSpotCASPort=$sock->GET_INFO("ArticaHotSpotCASPort");
		$f[]=whitelist_destination($ArticaHotSpotCASHost);
	}
	
	$sql="SELECT *  FROM `hotspot_whitelist`";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $q->mysql_error\n";}return;}
	$Total=mysql_num_rows($results);
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $Total whitelisted websites\n";}
	while ($ligne = mysql_fetch_assoc($results)) {
		
		$f[]=whitelist_webserver($ligne["ipaddr"],$ligne["port"],$ligne["ssl"]);
		
	}
	
	
	$sql="SELECT *  FROM `hotspot_networks` WHERE hotspoted=0";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $q->mysql_error\n";}return;}
	$Total=mysql_num_rows($results);
	
	
	
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $Total whitelisted\n";}
	while ($ligne = mysql_fetch_assoc($results)) {
		$pattern=$ligne["pattern"];
		
		if($ipClass->IsvalidMAC($pattern)){
			$c++;
			$f[]=redirect_mac_to_proxy($pattern);
			continue;
		}
		if($ipClass->isIPAddressOrRange($pattern)){
			$c++;
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Whitelist IP: $pattern $ArticaHotSpotPort/$ArticaSSLHotSpotPort\n";}
			$f[]=redirect_ip_to_proxy($pattern);
			continue;
		}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Unkown `$pattern`\n";}
	
	}
	
	$sql="SELECT *  FROM `hotspot_networks` WHERE hotspoted=1";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $q->mysql_error\n";}return;}
	$Total=mysql_num_rows($results);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $Total hotspoted\n";}
	
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$pattern=$ligne["pattern"];
		$restrict_web=$ligne["restrict_web"];
		if($ipClass->IsvalidMAC($pattern)){
			$c++;
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} hostpot MAC: $pattern $ipaddr:$ArticaSplashHotSpotPort/$ipaddr:$ArticaSplashHotSpotPortSSL\n";}
			$f[]=redirect_mac_to_splash($pattern,$restrict_web);
			continue;
		}
		if($ipClass->isIPAddressOrRange($pattern)){
			$c++;
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} hostpot IP: $pattern $ipaddr:$ArticaSplashHotSpotPort  - $ipaddr:$ArticaSplashHotSpotPortSSL\n";}
			$f[]=redirect_ip_to_splash($pattern,$restrict_web);
			
			continue;
		}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Unkown `$pattern`\n";}
	
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $c rule(s)\n";}
	
	if($c==0){
		$f[]=redirect_ip_to_splash("0.0.0.0/0");
	}
	
	$f[]="$iptables -t nat -A POSTROUTING -j MASQUERADE $suffixTables";
	@file_put_contents("/etc/artica-postfix/hotspot.conf",@implode("\n", $f));

}

function remove_sessions(){
	$unix=new unix();
	$tmp=$unix->TEMP_DIR();
	$iptables_save=$unix->find_program("iptables-save");
	$iptables_restore=$unix->find_program("iptables-restore");
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} {$GLOBALS["CACHE_FILE"]}\n";}
	shell_exec("$iptables_save > {$GLOBALS["CACHE_FILE"]}");
	$data=file_get_contents($GLOBALS["CACHE_FILE"]);
	$datas=explode("\n",$data);
	$pattern="#.+?comment HotSpot-(.+?)\s+#";
	$pattern="#.+?comment HotSpotSession-(.+?)\s+#";
	$c=0;
	while (list ($num, $ligne) = each ($datas) ){
		if($ligne==null){continue;}
		if(preg_match($pattern,$ligne)){$c++;continue;}
		$conf=$conf . $ligne."\n";
	}
	$t=time();
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Removing $c sessions line(s)\n";}
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Restoring datas iptables-restore < /etc/artica-postfix/iptables-hostspot.conf\n";}
	file_put_contents("$tmp/$t.conf",$conf);
	system("$iptables_restore < $tmp/$t.conf");	
	@unlink("$tmp/$t.conf");
	shell_exec("$iptables_save > {$GLOBALS["CACHE_FILE"]}");
	
}

function remove_temp_sessions(){
	$unix=new unix();
	$tmp=$unix->TEMP_DIR();
	$iptables_save=$unix->find_program("iptables-save");
	$iptables_restore=$unix->find_program("iptables-restore");
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} {$GLOBALS["CACHE_FILE"]}\n";}
	shell_exec("$iptables_save > {$GLOBALS["CACHE_FILE"]}");
	$data=file_get_contents($GLOBALS["CACHE_FILE"]);
	$datas=explode("\n",$data);
	$pattern="#.+?comment HotSpotSession-(.+?)\s+#";
	$c=0;
	$MAIN=array();
	while (list ($num, $ligne) = each ($datas) ){
		if($ligne==null){continue;}
		if(preg_match($pattern,$ligne)){$c++;continue;}
		$MAIN[]=$ligne;
	}
	$t=time();
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Removing $c sessions line(s)\n";}
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Restoring datas iptables-restore < /etc/artica-postfix/iptables-hostspot.conf\n";}
	file_put_contents("$tmp/$t.conf",@implode("\n", $MAIN));
	system("$iptables_restore < $tmp/$t.conf");
	@unlink("$tmp/$t.conf");
	shell_exec("$iptables_save > {$GLOBALS["CACHE_FILE"]}");
	
}
function remove_MAC_sessions($mac){
	
	$ipClass=new IP();
	if(!$ipClass->IsvalidMAC($mac)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} invalid mac address: $mac\n";}
		return;}
	
	$unix=new unix();
	$tmp=$unix->TEMP_DIR();
	$iptables_save=$unix->find_program("iptables-save");
	$iptables_restore=$unix->find_program("iptables-restore");

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} {$GLOBALS["CACHE_FILE"]}\n";}
	shell_exec("$iptables_save > {$GLOBALS["CACHE_FILE"]}");
	$data=file_get_contents($GLOBALS["CACHE_FILE"]);
	$datas=explode("\n",$data);
	$pattern="#$mac#i";
	$c=0;
	$MAIN=array();
	while (list ($num, $ligne) = each ($datas) ){
		if($ligne==null){continue;}
		if(preg_match($pattern,$ligne)){$c++;continue;}
		$MAIN[]=$ligne;
	}
	$t=time();
	file_put_contents("$tmp/$t.conf",@implode("\n", $MAIN));
	system("$iptables_restore < $tmp/$t.conf");
	@unlink("$tmp/$t.conf");
	shell_exec("$iptables_save > {$GLOBALS["CACHE_FILE"]}");
}
function untrack(){
	$unix=new unix();
	$sock=new sockets();
	$conntrack=$unix->find_program("conntrack");
	exec("$conntrack -L 2>&1",$results);
	
	$SquidHotSpotPort=$sock->GET_INFO("SquidHotSpotPort");
	$ArticaHotSpotPort=$sock->GET_INFO("ArticaHotSpotPort");
	$ArticaSSLHotSpotPort=$sock->GET_INFO("ArticaSSLHotSpotPort");
	$ArticaSplashHotSpotPort=$sock->GET_INFO("ArticaSplashHotSpotPort");
	$ArticaSplashHotSpotPortSSL=$sock->GET_INFO("ArticaSplashHotSpotPortSSL");
	$ArticaHotSpotInterface=$sock->GET_INFO("ArticaHotSpotInterface");
	if($ArticaHotSpotPort==0){
		$ArticaHotSpotPort=rand(38000, 64000);
		$sock->SET_INFO("ArticaHotSpotPort", $ArticaHotSpotPort);
	}
	
	if($ArticaSSLHotSpotPort==0){
		$ArticaSSLHotSpotPort=rand(38500, 64000);
		$sock->SET_INFO("ArticaSSLHotSpotPort", $ArticaSSLHotSpotPort);
	}
	
	if($ArticaHotSpotInterface==null){$ArticaHotSpotInterface="eth0";}
	if(!is_numeric($ArticaSplashHotSpotPort)){$ArticaSplashHotSpotPort=16080;}
	if(!is_numeric($ArticaSplashHotSpotPortSSL)){$ArticaSplashHotSpotPortSSL=16443;}
	
	$ipsrc=array();
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#src=(.+?)\s+dst=.+?\s+sport=[0-9]+\s+dport=(80|443|$ArticaHotSpotPort|$ArticaSSLHotSpotPort|$ArticaSplashHotSpotPort|$ArticaSplashHotSpotPortSSL)#", $ligne,$re)){
			$ipaddr=$re[1];
			if($ipaddr=="127.0.0.1"){continue;}
			$ipsrc[$ipaddr]=true;
			continue;
		}
		
	}
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Connection tracked: ". count($ipsrc)."\n";}
	
	if(count($ipsrc)>0){
		while (list ($ipaddr, $ligne) = each ($ipsrc) ){
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Removing $ipaddr connection\n";}
			exec("$conntrack -D -n $ipaddr 2>&1",$results);
			
		}
		
	}
	
	
}
function whitelist_webserver($ipaddr,$port=0,$ssl=0){

	$ipClass=new IP();
	$unix=new unix();
	$iptables=$unix->find_program("iptables");
	$squid_http_port=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaHotSpotPort");
	$squid_ssl_port=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaSSLHotSpotPort");

	if(!$ipClass->isValid($ipaddr)){return;}

	$internet="internet";
	$mark_text=$GLOBALS["MARKHTTP"];
	$md5key=md5($ipaddr);
	$suffixTables="-m comment --comment \"ArticaHotSpot-$md5key\"";
	$squid_port=$squid_http_port;
	if($ssl==1){$squid_port=$squid_ssl_port;$internet="internssl";$mark=98;$mark_text=$GLOBALS["MARKHTTPS"];}
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} whitelist $ipaddr:$port ssl:$ssl\n";}
	
	
	if(!$GLOBALS["EBTABLES"]){
		$f[]="$iptables -t mangle -I PREROUTING -p tcp -m tcp -d $ipaddr --dport $port -j $internet -m comment --comment ArticaHotSpot-$md5key";
	}
	$f[]="$iptables -t nat -I PREROUTING -p tcp $mark_text -m tcp -d $ipaddr --dport $port -j REDIRECT --to-port $squid_port -m comment --comment ArticaHotSpot-$md5key";

	
	return @implode("\n", $f);
}
function whitelist_destination($hostname,$port=0){
	
	$ipClass=new IP();
	$unix=new unix();
	$iptables=$unix->find_program("iptables");
	$squid_http_port=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaHotSpotPort");
	$squid_ssl_port=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaSSLHotSpotPort");
	
	if($ipClass->isValid($hostname)){$ip=$hostname;}else{$ip=gethostbyname($hostname);}
	
	if(!$ipClass->isValid($ip)){ 
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} unable to resolve $hostname\n";}
		return;
	}
	
	
	$md5key=md5($ip);
	$suffixTables="-m comment --comment \"ArticaHotSpot-$md5key\"";
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $hostname -> $ip whitelist 80/433\n";} 
	
	if(!$GLOBALS["EBTABLES"]){$f[]="$iptables -t mangle -I PREROUTING -p tcp -m tcp -d $ip --dport 80 -j internet -m comment --comment ArticaHotSpot-$md5key";}
	$f[]="$iptables -t nat -I PREROUTING -p tcp {$GLOBALS["MARKHTTP"]} -m tcp -d $ip --dport 80 -j REDIRECT --to-port $squid_http_port -m comment --comment ArticaHotSpot-$md5key";
		
	if(!$GLOBALS["EBTABLES"]){$f[]="$iptables -t mangle -I PREROUTING -p tcp -m tcp -d $ip --dport 443 -j internssl -m comment --comment ArticaHotSpot-$md5key";}
	$f[]="$iptables -t nat -I PREROUTING -p tcp {$GLOBALS["MARKHTTPS"]} -m tcp -d $ip --dport 443 -j REDIRECT --to-port $squid_ssl_port -m comment --comment ArticaHotSpot-$md5key";
	return @implode("\n", $f);
}


function redirect_mac_to_proxy($mac){
	$unix=new unix();
	$iptables=$unix->find_program("iptables");
	$md5key=md5($mac);
	$squid_http_port=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaHotSpotPort");
	$squid_ssl_port=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaSSLHotSpotPort");
	
	if(!$GLOBALS["EBTABLES"]){$f[]="$iptables -t mangle -I PREROUTING -m mac --mac-source $mac -p tcp -m tcp --dport 80 -j internet -m comment --comment ArticaHotSpot-$md5key";}
	$f[]="$iptables -t nat -I PREROUTING -m mac --mac-source $mac -p tcp {$GLOBALS["MARKHTTP"]} -m tcp --dport 80 -j REDIRECT --to-port $squid_http_port -m comment --comment ArticaHotSpot-$md5key";
	if(!$GLOBALS["EBTABLES"]){$f[]="$iptables -t mangle -I PREROUTING -m mac --mac-source $mac -p tcp -m tcp --dport 443 -j internssl -m comment --comment ArticaHotSpot-$md5key";}
	$f[]="$iptables -t nat -I PREROUTING -m mac --mac-source $mac -p tcp {$GLOBALS["MARKHTTPS"]} -m tcp --dport 443 -j REDIRECT --to-port $squid_ssl_port -m comment --comment ArticaHotSpot-$md5key";
	return @implode("\n", $f);
}



function redirect_ip_to_proxy($ipaddr){
	$unix=new unix();
	$md5key=md5($ipaddr);
	$iptables=$unix->find_program("iptables");
	$squid_http_port=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaHotSpotPort");
	$squid_ssl_port=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaSSLHotSpotPort");
	
	
	
	
	if(!$GLOBALS["EBTABLES"]){$f[]="$iptables -t mangle -I PREROUTING -s $ipaddr -p tcp -m tcp --dport 80 -j internet -m comment --comment ArticaHotSpot-$md5key";}
	$f[]="$iptables -t nat -I PREROUTING -s $ipaddr -p tcp {$GLOBALS["MARKHTTP"]} -m tcp --dport 80 -j REDIRECT --to-port $squid_http_port -m comment --comment ArticaHotSpot-$md5key";
	
	if(!$GLOBALS["EBTABLES"]){$f[]="$iptables -t mangle -I PREROUTING -s $ipaddr -p tcp -m tcp --dport 443 -j internssl -m comment --comment ArticaHotSpot-$md5key";}
	$f[]="$iptables -t nat -I PREROUTING -s $ipaddr -p tcp {$GLOBALS["MARKHTTPS"]} -m tcp --dport 443 -j REDIRECT --to-port $squid_ssl_port -m comment --comment ArticaHotSpot-$md5key";
	
	
	return @implode("\n", $f);
}


function redirect_mac_to_splash($from,$allports=0){
	$unix=new unix();
	$iptables=$unix->find_program("iptables");
	$md5key=md5($from);
	$apache_http_port=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaSplashHotSpotPort");
	$apache_ssl_port=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaSplashHotSpotPortSSL");
	
	if(!is_numeric($apache_http_port)){$apache_http_port=16080;}
	if(!is_numeric($apache_ssl_port)){$apache_ssl_port=16443;}
	
	$ToDestinationHTTP="-j DNAT --to-destination {$GLOBALS["HOSTPOT_WEB_INTERFACE"]}:$apache_http_port";
	$ToDestinationHTTPs="-j DNAT --to-destination {$GLOBALS["HOSTPOT_WEB_INTERFACE"]}:$apache_ssl_port";
	
	if($GLOBALS["HOSTPOT_WEB_INTERFACE"]=="0.0.0.0"){$GLOBALS["HOSTPOT_WEB_INTERFACE"]=null;}
	if($GLOBALS["HOSTPOT_WEB_INTERFACE"]==null){
		$ToDestinationHTTP="-j REDIRECT --to-ports $apache_http_port";
		$ToDestinationHTTPs="-j REDIRECT --to-ports $apache_ssl_port";
	}
	
	
	
	
	if(!$GLOBALS["EBTABLES"]){$f[]="$iptables -t mangle -A PREROUTING -m mac --mac-source $from -p tcp -m tcp --dport 80 -j internet -m comment --comment ArticaHotSpot-$md5key";}
	$f[]="$iptables -t nat -A PREROUTING -m mac --mac-source $from -p tcp {$GLOBALS["MARKHTTP"]} -m tcp --dport 80 $ToDestinationHTTP -m comment --comment ArticaHotSpot-$md5key";
	
	if(!$GLOBALS["EBTABLES"]){$f[]="$iptables -t mangle -A PREROUTING -m mac --mac-source $from -p tcp -m tcp --dport 443 -j internssl -m comment --comment ArticaHotSpot-$md5key";}
	$f[]="$iptables -t nat -A PREROUTING -m mac --mac-source $from -p tcp {$GLOBALS["MARKHTTPS"]} -m tcp --dport 443 $ToDestinationHTTPs -m comment --comment ArticaHotSpot-$md5key";
	
	
	if($allports==1){
		reset($GLOBALS["RESTRICTED_PORTS"]);
		while (list ($port, $ports) = each ($GLOBALS["RESTRICTED_PORTS"]) ){
			if($port==$apache_http_port){continue;}
			if($port==$apache_ssl_port){continue;}
			if($port==80){continue;}
			if($port==443){continue;}
			if($port==53){continue;}
			if(!$GLOBALS["EBTABLES"]){$f[]="$iptables -t mangle -A PREROUTING -m mac --mac-source $from -p tcp -m tcp --dport $port -j internssl -m comment --comment ArticaHotSpot-$md5key";}
			$f[]="$iptables -t nat -A PREROUTING -m mac --mac-source $from -p tcp {$GLOBALS["MARKHTTPS"]} -m tcp --dport $port $ToDestinationHTTP -m comment --comment ArticaHotSpot-$md5key";
				
		}
	}
	
	return @implode("\n", $f);	
}

function redirect_ip_to_splash($from,$allports=0){
	$unix=new unix();
	$iptables=$unix->find_program("iptables");
	$md5key=md5($from);
	$apache_http_port=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaSplashHotSpotPort");
	$apache_ssl_port=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaSplashHotSpotPortSSL");
	if(!is_numeric($apache_http_port)){$apache_http_port=16080;}
	if(!is_numeric($apache_ssl_port)){$apache_ssl_port=16443;}
	
	$ToDestinationHTTP="-j DNAT --to-destination {$GLOBALS["HOSTPOT_WEB_INTERFACE"]}:$apache_http_port";
	$ToDestinationHTTPs="-j DNAT --to-destination {$GLOBALS["HOSTPOT_WEB_INTERFACE"]}:$apache_ssl_port";
	
	if($GLOBALS["HOSTPOT_WEB_INTERFACE"]=="0.0.0.0"){$GLOBALS["HOSTPOT_WEB_INTERFACE"]=null;}
	if($GLOBALS["HOSTPOT_WEB_INTERFACE"]==null){
		$ToDestinationHTTP="-j REDIRECT --to-ports $apache_http_port";
		$ToDestinationHTTPs="-j REDIRECT --to-ports $apache_ssl_port";
	}
	
	if(!$GLOBALS["EBTABLES"]){$f[]="$iptables -t mangle -A PREROUTING -s $from -p tcp -m tcp --dport 80 -j internet -m comment --comment ArticaHotSpot-$md5key";}
	$f[]="$iptables -t nat -A PREROUTING -s $from -p tcp {$GLOBALS["MARKHTTP"]} -m tcp --dport 80 $ToDestinationHTTP -m comment --comment ArticaHotSpot-$md5key";
	
	if(!$GLOBALS["EBTABLES"]){$f[]="$iptables -t mangle -A PREROUTING -s $from -p tcp -m tcp --dport 443 -j internssl -m comment --comment ArticaHotSpot-$md5key";}
	$f[]="$iptables -t nat -A PREROUTING -s $from -p tcp {$GLOBALS["MARKHTTPS"]} -m tcp --dport 443 $ToDestinationHTTPs -m comment --comment ArticaHotSpot-$md5key";
	
	
	if($allports==1){
		reset($GLOBALS["RESTRICTED_PORTS"]);
		while (list ($port, $ports) = each ($GLOBALS["RESTRICTED_PORTS"]) ){
			if($port==$apache_http_port){continue;}
			if($port==$apache_ssl_port){continue;}
			if($port==80){continue;}
			if($port==443){continue;}
			if($port==53){continue;}
			if(!$GLOBALS["EBTABLES"]){$f[]="$iptables -t mangle -A PREROUTING -s $from -p tcp -m tcp --dport $port -j internssl -m comment --comment ArticaHotSpot-$md5key";}
			$f[]="$iptables -t nat -A PREROUTING -s $from -p tcp {$GLOBALS["MARKHTTPS"]} -m tcp --dport $port $ToDestinationHTTP -m comment --comment ArticaHotSpot-$md5key";
			
		}
	}
		
	

	return @implode("\n", $f);
	
	
}


function remove_mysql_sessions($aspid=false){
	$unix=new unix();
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	if($GLOBALS["VERBOSE"]){$GLOBALS["FORCE"]=true;echo "pidTime: $pidTime\n";$GLOBALS["FORCE"]=true;}
	
	if(!$GLOBALS["FORCE"]){
		if(!$aspid){
			$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
			$pid=@file_get_contents($pidfile);
			if($pid<100){$pid=null;}
		
			if($unix->process_exists($pid,basename(__FILE__))){
				$timepid=$unix->PROCCESS_TIME_MIN($pid);
				events("Already executed pid $pid since {$timepid}Mn");
				return;
			}
		
			@file_put_contents($pidfile, time());
		}
		
		
		
		
	}
	if(!$GLOBALS["FORCE"]){
		$time=$unix->file_time_min($pidTime);
		if($time<10){return;}
	}
	
	@file_put_contents($pidTime, time());
	$conntrack=$unix->find_program("conntrack");
	$q=new mysql_squid_builder();
	if($q->COUNT_ROWS("hotspot_sessions")==0){
		remove_temp_sessions();
		untrack();
		$q->QUERY_SQL("DROP TABLE `hotspot_sessions`");
		$sql="CREATE TABLE `squidlogs`.`hotspot_sessions` (
			`md5` VARCHAR( 90 ) NOT NULL ,
			`logintime` BIGINT UNSIGNED ,
			`maxtime` INT UNSIGNED ,
			`finaltime` INT UNSIGNED ,
			`nextcheck` INT UNSIGNED ,
			`username` VARCHAR( 128 ) NOT NULL ,
			`MAC` VARCHAR( 90 ) NOT NULL,
			`uid` VARCHAR( 128 ) NOT NULL ,
			`hostname` VARCHAR( 128 ) NOT NULL ,
			`ipaddr` VARCHAR( 128 ) ,
			PRIMARY KEY ( `md5` ) ,
			INDEX ( `logintime` , `maxtime` , `username` ,`finaltime`,`nextcheck`),
			KEY `MAC` (`MAC`),
			KEY `uid` (`uid`),
			KEY `hostname` (`hostname`),
			KEY `ipaddr` (`ipaddr`)
			)  ENGINE = MEMORY;";
		$q->QUERY_SQL($sql);
		return;
	}
	
	$iptables_save=$unix->find_program("iptables-save");
	shell_exec("$iptables_save > {$GLOBALS["CACHE_FILE"]}");
	$data=file_get_contents($GLOBALS["CACHE_FILE"]);
	$datas=explode("\n",$data);
	
	if($GLOBALS["VERBOSE"]){echo "DEBUG: {$GLOBALS["CACHE_FILE"]} -> ".count($datas)." lines\n";}
	
	while (list ($num, $ligne) = each ($datas) ){
	
		if(!preg_match("#-m mac --mac-source (.+?)\s+.*?--comment HotSpotSession-#", $ligne,$re)){
			if($GLOBALS["VERBOSE"]){echo "DEBUG: $ligne (no match )\n";}
			continue;
		}
		$mac=trim(strtolower($re[1]));
			
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `md5` FROM hotspot_sessions WHERE MAC='$mac'"));
		if($ligne["md5"]==null){
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} remove session \"$mac\"\n";}
			hotspot_admin_mysql(2, "Session $mac is not in table", "Remove it from firewall");
			remove_MAC_sessions($mac);
		}
		
	
	}
	
	
	$time=time();
	$sql="SELECT `md5`,MAC,ipaddr,username,maxtime,nextcheck FROM hotspot_sessions WHERE nextcheck>0 AND nextcheck < $time";
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){echo $q->mysql_error."\n";}
	$count=mysql_num_rows($results);
	if($count==0){if($GLOBALS["VERBOSE"]){echo "Nothing to do...\n";}return;}
	
	if($GLOBALS["VERBOSE"]){echo "$count rows\n";}
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		
	
		
		$MAC=$ligne["MAC"];
		$username=$ligne["username"];
		$ipaddr=$ligne["ipaddr"];
		$nextcheck=$ligne["nextcheck"];
		if($GLOBALS["VERBOSE"]){
			echo "\n\n****\n\n DROP Session $MAC $username $ipaddr $nextcheck < $time\n****\n\n";
		
		}
		
		
		remove_MAC_sessions($MAC);
		
		$NextCheckEdit = strtotime("+10 minutes", time());
		
		
		$sql="UPDATE hotspot_sessions SET nextcheck=$NextCheckEdit WHERE `md5`='{$ligne["md5"]}'";
		echo "$sql\n";
		$q->QUERY_SQL($sql);
		hotspot_admin_mysql(2, "Drop session $username $ipaddr", "Hotspot session for $username reach time [$nextcheck] ".date("Y-m-d H:i:s",$nextcheck)." current ".date("Y-m-d H:i:s",$nextcheck)."\nNext check will be at:" .date("Y-m-d H:i:s",$NextCheckEdit),__FILE__,__LINE__);
		shell_exec("$conntrack -D -n $ipaddr 2>&1");
	}
	

	
	$c=0;
	$tab=array();
	
}

function defaults_ports(){
	$defaults="22,23,21,24,25,70,81,82,83,3128,88,109,110,113,119,123,143,144,150,194,201,202,203,204,205,206,207,208,209,210,220,389,3306,563,631,873,993,995,1080,1194,1863,3389,5060,5222,5223,5269,5280,5432,5900,5984,6667,6666,6697,7000,8008,8098,9009,25565,49300,8080,9000,9090,3140,3147";
	$q=new mysql_squid_builder();
	$table="hotspot_blckports";
	if($q->COUNT_ROWS($table)==0){
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`hotspot_blckports` ( `port` BIGINT UNSIGNED , `enabled` smallint(1) NOT NULL, PRIMARY KEY ( `port` ) , INDEX ( `enabled`) )  ENGINE = MYISAM;";
		$q->QUERY_SQL($sql);
		$defaultsZ=explode(",",$defaults);
		while (list ($none, $ports) = each ($defaultsZ) ){
			$sqlZ[]="($ports,1)";
		}
	
		$sql="INSERT IGNORE INTO hotspot_blckports (`port`,`enabled`) VALUES ".@implode(',', $sqlZ);
		$q->QUERY_SQL($sql);
	}
	
	$sql="SELECT port  FROM `hotspot_blckports` WHERE enabled=1";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $q->mysql_error\n";}return;}
	$Total=mysql_num_rows($results);
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $Total restricted ports\n";}
	while ($ligne = mysql_fetch_assoc($results)) {
	
		$GLOBALS["RESTRICTED_PORTS"][$ligne["port"]]=$ligne["port"];
	
	}	
	
	
	
}

function testmail(){
	$sock=new sockets();
	$HotSpotAutoRegisterWebMail=intval($sock->GET_INFO("HotSpotAutoRegisterWebMail"));
	$HotSpotAutoRegisterSMTPSrv=$sock->GET_INFO("HotSpotAutoRegisterSMTPSrv");
	$HotSpotAutoRegisterSMTPSrvPort=$sock->GET_INFO("HotSpotAutoRegisterSMTPSrvPort");
	$HotSpotAutoRegisterSMTPSender=$sock->GET_INFO("HotSpotAutoRegisterSMTPSender");
	$HotSpotAutoRegisterSMTPUser=$sock->GET_INFO("HotSpotAutoRegisterSMTPUser");
	$HotSpotAutoRegisterSMTPPass=$sock->GET_INFO("HotSpotAutoRegisterSMTPPass");
	$HotSpotAutoRegisterSMTPTls=intval($sock->GET_INFO("HotSpotAutoRegisterSMTPTls"));
	$HotSpotAutoRegisterSMTPSSL=intval($sock->GET_INFO("HotSpotAutoRegisterSMTPSSL"));
	$instance=trim($sock->getFrameWork('cmd.php?full-hostname=yes'));
	
	if($HotSpotAutoRegisterSMTPSrvPort==0){$HotSpotAutoRegisterSMTPSrvPort=25;}
	
	$smtp=new smtp();
	if($HotSpotAutoRegisterSMTPUser<>null){
		$params["auth"]=true;
		$params["user"]=$HotSpotAutoRegisterSMTPUser;
		$params["pass"]=$HotSpotAutoRegisterSMTPPass;
	}
	$params["host"]=$HotSpotAutoRegisterSMTPSrv;
	$params["port"]=$HotSpotAutoRegisterSMTPSrvPort;
	if(!$smtp->connect($params)){
		echo "Error $smtp->error_number: Could not connect to `$HotSpotAutoRegisterSMTPSrv` $smtp->error_text\n";
		return;
	}
	
	echo "Connecting OK\n";
	$random_hash = md5(date('r', time()));
	$boundary="$random_hash/$instance";
	$body[]="Return-Path: <$HotSpotAutoRegisterSMTPSender>";
	$body[]="X-Original-To: $recipient";
	$body[]="Date: ". date("D, d M Y H:i:s"). " +0100 (CET)";
	$body[]="From: $HotSpotAutoRegisterSMTPSender (Mail Delivery System)";
	$body[]="Subject: $Subject";
	$body[]="To: $recipient";
	$body[]="";
	$body[]="";
	$body[]="This is the mail system at host $instance.";
	$body[]="";
	$body[]="I'm glade to inform you that your message is";
	$body[]=" delivered to you...";
	$body[]="";
	$body[]="For further assistance, please send mail to postmaster.";
	$body[]="";
	$body[]="If you do so, please include this problem report. You can";
	$body[]="delete your own text from the attached returned message.";
	$body[]="";
	$body[]="                   The mail system";
	$body[]="";
	$body[]="";
	
	$finalbody=@implode("\r\n", $body);
	
	if(!$smtp->send(array("from"=>$HotSpotAutoRegisterSMTPSender,"recipients"=>$recipient,"body"=>$finalbody,"headers"=>null))){
		echo "Error $smtp->error_number: Could not send to `$HotSpotAutoRegisterSMTPSrv` $smtp->error_text\n";
		$smtp->quit();
		return;
	}	
	
	
	$smtp->quit();
	return;	
	
}

