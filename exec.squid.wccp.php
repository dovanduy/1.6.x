#!/usr/bin/php -q
<?php
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["EBTABLES"]=false;
$GLOBALS["OUTPUT"]=true;
$GLOBALS["PROGRESS"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--progress#",implode(" ",$argv))){$GLOBALS["PROGRESS"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');

if($argv[1]=="--stop"){wccp_stop();exit;}
if($argv[1]=="--remove"){wccp_remove();exit;}
if($argv[1]=="--reconfigure"){wccp();exit;}
if($argv[1]=="--squid"){squid();exit;}
if($argv[1]=="--build"){wccp();exit;}
if($argv[1]=="--verif"){wccp_verif();exit;}


function build_progress($text,$pourc){
	if(!$GLOBALS["PROGRESS"]){return;}
	echo $text."\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/squid.wccp.interface.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	sleep(1);
}

function wccp(){
	
	$unix=new unix();
	$sock=new sockets();
	$SquidWCCPEnabled=intval($sock->GET_INFO("SquidWCCPEnabled"));
	$echobin=$unix->find_program("echo");
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL("SELECT * FROM proxy_ports WHERE WCCP=1 AND enabled=1");
	if(!$q->ok){
		build_progress("MySQL {error}",110);
		if($GLOBALS["PROGRESS"]){echo $q->mysql_error;}
		if(is_file("/etc/init.d/proxy-wccp")){system("/etc/init.d/proxy-wccp start");}
		return;
	}
	
	
	$CountOfRules=mysql_num_rows($results);
	$GLOBALS["COUNTOF"]=0;
	
	if($CountOfRules==0){
		build_progress("No rule",50);
		
		build_progress("{removing}",60);
		wccp_remove();
		@file_put_contents("/etc/squid3/wccp.conf", "# --------- Cisco's Web Cache Coordination Protocol Layer 3 is not enabled\n# No router set\n");
		build_progress("{removing}",110);
		return;
	}
	$GLOBALS["SCRIPT_START"]=array();
	$GLOBALS["WCCP_ROUTER"]=array();
	
	build_progress("{building}",30);
	
	while ($ligne = mysql_fetch_assoc($results)) {
		wccp_port($ligne);
	}
	
	build_progress("{building}",40);
	
	if($GLOBALS["COUNTOF"]>0){
		$modprobe=$unix->find_program("modprobe");
		$iptables=$unix->find_program("iptables");
		
		$GLOBALS["SCRIPT_HEAD"][]="$echobin 1 >/proc/sys/net/ipv4/ip_forward || true";
		$GLOBALS["SCRIPT_HEAD"][]="$modprobe ip_conntrack || true";
		$GLOBALS["SCRIPT_HEAD"][]="$modprobe iptable_nat || true";
		$GLOBALS["SCRIPT_HEAD"][]="$iptables -t nat -A POSTROUTING -j MASQUERADE -m comment --comment \"ArticaWCCP3\" || true";
		wccp_create();
		disable_iptables();
		build_progress("disablenics()...",55);
		disablenics();
		build_progress("WCCP Proxy",60);
		wccp_squid();
		build_progress("{running}",70);
		if(is_file("/etc/init.d/proxy-wccp")){system("/etc/init.d/proxy-wccp start");}
	}else{
		build_progress("{uninstalling}",70);
		wccp_remove();
		@file_put_contents("/etc/squid3/wccp.conf", "# --------- Cisco's Web Cache Coordination Protocol Layer 3 is not enabled\n# No router set\n");
	}
	build_progress("{done}",100);
}

function squid(){
	$unix=new unix();
	$sock=new sockets();
	$SquidWCCPEnabled=intval($sock->GET_INFO("SquidWCCPEnabled"));
	$echobin=$unix->find_program("echo");
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL("SELECT * FROM proxy_ports WHERE WCCP=1 AND enabled=1");
	if(!$q->ok){
		if(is_file("/etc/init.d/proxy-wccp")){system("/etc/init.d/proxy-wccp start");}
		return;
	}
	while ($ligne = mysql_fetch_assoc($results)) {
		wccp_port($ligne);
	}
	
	wccp_squid();
	
}


function wccp_create(){
	
	$INITD_PATH="/etc/init.d/proxy-wccp";
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          proxy-wccp";
	$f[]="# Required-Start:    \$local_fs \$remote_fs \$syslog \$named \$network \$time";
	$f[]="# Required-Stop:     \$local_fs \$remote_fs \$syslog \$named \$network";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Wccp installation";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Wccp installation";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]=@implode("\n", $GLOBALS["SCRIPT_HEAD"]);
	$f[]=@implode("\n",$GLOBALS["SCRIPT_START"]);
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]=@implode("\n",$GLOBALS["SCRIPT_STOP"]);
	$f[]="$php ".__FILE__." --stop";
	$f[]="    ;;";
	
	$f[]="  verif)";
	$f[]="$php ".__FILE__." --verif";
	$f[]="    ;;";	
	
	$f[]="";
	$f[]=" restart)";
	$f[]=@implode("\n",$GLOBALS["SCRIPT_STOP"]);
	$f[]="$php ".__FILE__." --stop";
	$f[]="$php ".__FILE__." --reconfigure";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	
	
	echo "wccp: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);@file_put_contents($INITD_PATH, @implode("\n", $f));
	
	@chmod($INITD_PATH,0755);
	
	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	build_progress("$INITD_PATH {done}",45);
	
}

function wccp_stop(){
	disable_iptables();
	disablenics();
	
}


function wccp_squid(){
	
	$sock=new sockets();
	$SquidWCCPL3SSServiceID=intval($sock->GET_INFO("SquidWCCPL3SSServiceID"));
	if($SquidWCCPL3SSServiceID==0){$SquidWCCPL3SSServiceID=70;}
	

	
	if(count($GLOBALS["WCCP_ROUTER"])==0){
		@file_put_contents("/etc/squid3/wccp.conf", "# --------- Cisco's Web Cache Coordination Protocol Layer 3 is not enabled\n# No router set\n");
	}
	

	$conf[]="# --------- Cisco's Web Cache Coordination Protocol Layer 3";
	
	while (list ($szWccpip, $line) = each ($GLOBALS["WCCP_ROUTER"]) ){
		$conf[]="wccp2_router $szWccpip";
		
	}
	
	$conf[]="wccp2_forwarding_method gre";
	$conf[]="wccp2_return_method gre";
	$conf[]="wccp2_service standard 0";
	$conf[]="wccp2_rebuild_wait off";
	$conf[]="wccp2_service dynamic $SquidWCCPL3SSServiceID";
	$conf[]="wccp2_service_info $SquidWCCPL3SSServiceID protocol=tcp flags=src_ip_hash,ports_source priority=240 ports=443 ";
	$conf[]="";
	@file_put_contents("/etc/squid3/wccp.conf",@implode("\n", $conf));
	
	
}


function wccp_remove(){
	$INITD_PATH="/etc/init.d/proxy-wccp";
	wccp_stop();
	if(!is_file($INITD_PATH)){return;}


	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");
		
	}	
	
	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
	
	
}

function wccp_verif(){
	$d=0;
	$VERIF=true;
	$unix=new unix();
	$data=file_get_contents("/etc/artica-postfix/iptables.conf");
	$datas=explode("\n",$data);
	$pattern2="#.+?ArticaWCCP3#";
	$iptables_save=find_program("iptables-save");
	$ipbin=$unix->find_program("ip");
	while (list ($num, $ligne) = each ($datas) ){
		if($ligne==null){continue;}
		if(preg_match($pattern2,$ligne)){$d++;continue;}
	}

	if($d==0){wccp();return;}
	
	exec("$ipbin tunnel show 2>&1",$results);
	
	
	while (list ($index, $line) = each ($results)){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^wccp([0-9]+):\s+gre\/#", $line,$re)){continue;}
		$ID=$re[1];
		$d++;
	}
	if($d==0){wccp();return;}
	
}


function wccp_port($ligne){
	
	$unix=new unix();
	$ip=$unix->find_program("ip");
	$sysctl=$unix->find_program("sysctl");
	$eth=$ligne["nic"];
	$ID=$ligne["ID"];
	$port=$ligne["port"];
	$SquidWCCPL3Addr=$ligne["SquidWCCPL3Addr"];
	$SquidWCCPL3Route=$ligne["SquidWCCPL3Route"];
	$echobin=$unix->find_program("echo");
	$iptables=$unix->find_program("iptables");
	$route=$unix->find_program("route");
	$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
	$UseSSL=intval($ligne["UseSSL"]);
	
	build_progress("{building} eth:$eth port:$port id:$ID",30);
	
	if(!isset($NETWORK_ALL_INTERFACES[$eth])){
		if($GLOBALS["PROGRESS"]){echo "Fatal $eth -> no ip addr !!!!\n";}
		$GLOBALS["SCRIPT_CONTENT"][]="# Fatal $eth -> no ip addr";
		return;
	}

	if($NETWORK_ALL_INTERFACES[$eth]["IPADDR"]=='0.0.0.0'){
		if($GLOBALS["PROGRESS"]){echo "Fatal $eth -> no ip addr !!!!\n";}
		$GLOBALS["SCRIPT_CONTENT"][]="# Fatal $eth -> no ip addr";
		return;
	}
	

	$local_tcp=$NETWORK_ALL_INTERFACES[$eth]["IPADDR"];
	
	$GLOBALS["WCCP_ROUTER"][$SquidWCCPL3Addr]=true;
	$destport="80";
	if($UseSSL==1){$destport=443;}
	
	$GLOBALS["COUNTOF"]=$GLOBALS["COUNTOF"]+1;
	$GLOBALS["SCRIPT_START"][]="$ip link set $eth mtu 1476 || true";
	
	if($GLOBALS["PROGRESS"]){echo "$ip tunnel add wccp{$ID} mode gre remote $SquidWCCPL3Addr local $local_tcp dev $eth\n";}
	
	$GLOBALS["SCRIPT_START"][]="$ip tunnel add wccp{$ID} mode gre remote $SquidWCCPL3Addr local $local_tcp dev $eth || true";
	$GLOBALS["SCRIPT_START"][]="$ip addr add $local_tcp dev wccp{$ID}  || true";
	$GLOBALS["SCRIPT_START"][]="$ip link set wccp{$ID} up  || true";
	$GLOBALS["SCRIPT_START"][]="$sysctl -w net.ipv4.conf.wccp{$ID}.rp_filter=0 || true";
	$GLOBALS["SCRIPT_START"][]="$sysctl -w net.ipv4.conf.$eth.rp_filter=0 || true";
	$GLOBALS["SCRIPT_START"][]="$iptables -t nat -A PREROUTING -i wccp{$ID} -p tcp --dport $destport -j REDIRECT --to-port $port -m comment --comment \"ArticaWCCP3\" || true";
	

	
	
	$GLOBALS["SCRIPT_STOP"][]="$ip link set wccp{$ID} down";
	$GLOBALS["SCRIPT_STOP"][]="$ip tunnel del wccp{$ID}";
	
	
	if($SquidWCCPL3Route<>null){
		$GLOBALS["SCRIPTS"][]="$ip route add $SquidWCCPL3Route dev wccp{$ID}";
		$GLOBALS["SCRIPT_STOP"][]="$ip route del $SquidWCCPL3Route dev wccp{$ID}";
	}
	$GLOBALS["SCRIPT_STOP"][]="# # END ID $ID";
	
}




function disable_iptables(){
	$d=0;
	$data=file_get_contents("/etc/artica-postfix/iptables.conf");
	$datas=explode("\n",$data);
	$pattern2="#.+?ArticaWCCP3#";
	$iptables_save=find_program("iptables-save");
	$conf=null;
	
	while (list ($num, $ligne) = each ($datas) ){
		if($ligne==null){continue;}
		if(preg_match($pattern2,$ligne)){
			$d++;
			continue;
		}
		
		$conf=$conf . $ligne."\n";
	}
	if($d==0){return;}
	$iptables_restore=find_program("iptables-restore");
	file_put_contents("/etc/artica-postfix/iptables.new.conf",$conf);
	system("$iptables_restore < /etc/artica-postfix/iptables.new.conf");
	build_progress("Removing $d iptables rule(s) done",50);
	echo "Starting......: ".date("H:i:s")." Squid Check WCCP mode: removing $d iptables rule(s) done...\n";
	
}


function disablenics(){
	$unix=new unix();
	$sock=new sockets();
	$ipbin=$unix->find_program("ip");	
	$ifconfig=$unix->find_program("ifconfig");
	exec("$ipbin tunnel show 2>&1",$results);
	
	
	while (list ($index, $line) = each ($results)){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^wccp([0-9]+):\s+gre\/#", $line,$re)){continue;}
		$ID=$re[1];
		echo "Starting......: ".date("H:i:s")." Squid Listen removing wccp{$ID}\n";
		shell_exec("$ipbin tunnel del wccp{$ID}");
		shell_exec("$ifconfig wccp{$ID} down");
	}
	
}







function script_install(){
	
	
	@chmod("/etc/init.d/iptables-transparent",0755);
	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f iptables-transparent defaults >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add iptables-transparent >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 1234 iptables-transparent on >/dev/null 2>&1");
	}
	
}









