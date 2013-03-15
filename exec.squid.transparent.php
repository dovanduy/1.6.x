<?php
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["VERBOSE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');




	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$oldpid=@file_get_contents($pidfile);
	if($oldpid<100){$oldpid=null;}
	$unix=new unix();
	if($unix->process_exists($oldpid,basename(__FILE__))){echo "Starting......: Squid Transparent mode: $oldpid -> DIE\n";die();}


$users=new usersMenus();
if($users->WEBSTATS_APPLIANCE){iptables_delete_all();die();}
if(!$users->SQUID_INSTALLED){iptables_delete_all();die();}

$sysctl=$unix->find_program("sysctl");

$sock=new sockets();
$hasProxyTransparent=$sock->GET_INFO("hasProxyTransparent");
if(!is_numeric($hasProxyTransparent)){$hasProxyTransparent=0;}
echo "Starting......: Squid Check hasProxyTransparent key ($hasProxyTransparent)...\n";
if($hasProxyTransparent==1){enable_transparent();return;}
iptables_delete_all();


function enable_transparent(){
	$squid=new squidbee();
	$unix=new unix();
	$sock=new sockets();
	$SquidBinIpaddr=trim($sock->GET_INFO("SquidBinIpaddr"));
	if($SquidBinIpaddr==null){$SquidBinIpaddr="0.0.0.0";}
	$UseTProxyMode=$sock->GET_INFO("UseTProxyMode");
	if(!is_numeric($UseTProxyMode)){$UseTProxyMode=0;}		
	$ssl_port=$squid->get_ssl_port();
	if(!is_numeric($squid->listen_port)){$squid->listen_port=3128;}
	$listen_ssl_port=$squid->listen_port+1;
	$SSL_BUMP=$squid->SSL_BUMP;
	$iptables=$unix->find_program("iptables");	
	$sysctl=$unix->find_program("sysctl");
	$ips=$unix->ifconfig_interfaces_list();
	
	unset($ips["127.0.0.1"]);
	unset($ips["lo"]);
	echo "Starting......: Squid Transparent mode: enabled in transparent mode in $squid->listen_port Port (SSL_BUMP=$SSL_BUMP) SSL PORT:$ssl_port\n";
	echo "Starting......: Squid Transparent mode: enable the gateway mode...\n";
	echo "Starting......: Squid Transparent mode: KernelSendRedirects = $KernelSendRedirects...\n";
	if($UseTProxyMode==1){
		echo "Starting......: Squid Transparent mode: Activate TProxy mode...\n";	
	}
	
	$KernelSendRedirects=$sock->GET_INFO("KernelSendRedirects");
	if(!is_numeric($KernelSendRedirects)){$KernelSendRedirects=1;}
	
	shell_exec2("$sysctl -w net.ipv4.ip_forward=1 2>&1");
	shell_exec2("$sysctl -w net.ipv4.conf.default.send_redirects=$KernelSendRedirects 2>&1");
	shell_exec2("$sysctl -w net.ipv4.conf.all.send_redirects=$KernelSendRedirects 2>&1");
	shell_exec2("$sysctl -w net.ipv4.conf.eth0.send_redirects=$KernelSendRedirects 2>&1");
	
	
	iptables_delete_all();
	if($SquidBinIpaddr=="0.0.0.0"){$SquidBinIpaddr=null;}
	if($SquidBinIpaddr=="127.0.0.1"){$SquidBinIpaddr=null;}
	if($SquidBinIpaddr<>null){$ips=array();$ips["eth0"]=$SquidBinIpaddr;}
	
	if($UseTProxyMode==1){
		
		shell_exec2("$iptables -t mangle -N DIVERT -m comment --comment \"ArticaSquidTransparent\"");
		shell_exec2("$iptables -t mangle -A DIVERT -j MARK --set-mark 1 -m comment --comment \"ArticaSquidTransparent\"");
		shell_exec2("$iptables -t mangle -A DIVERT -j ACCEPT -m comment --comment \"ArticaSquidTransparent\"");
		shell_exec2("$iptables -t mangle -A PREROUTING -p tcp -m socket -j DIVERT -m comment --comment \"ArticaSquidTransparent\"");
		shell_exec2("$iptables -t mangle -A PREROUTING -p tcp --dport 80 -j TPROXY --tproxy-mark 0x1/0x1 --on-port $squid->listen_port -m comment --comment \"ArticaSquidTransparent\"");		
				
		
	//	if($SSL_BUMP){shell_exec2("$iptables -t mangle -A PREROUTING -p tcp --dport 443 -j TPROXY --on-port $listen_ssl_port --tproxy-mark 0x1/0x1 -m comment --comment \"ArticaSquidTransparent\"");}
		return;
	}
$IPTABLES=$iptables;
$INPUTINTERFACE="eth0";
$MARKLOG="-m comment --comment \"ArticaSquidTransparent\"";
$SQUIDPORT=$squid->listen_port;
	
	while (list ($interface, $ip) = each ($ips) ){
		$SQUIDIP=$ip;
		if(preg_match("#^ham#", $interface)){
			echo "Starting......: Squid Transparent mode: Squid Transparent mode: skipping $interface interface\n";continue;}
			echo "Starting......: Squid Transparent Interface:$interface Adding ipTables rules for $ip\n";
			shell_exec2("$iptables -t nat -A PREROUTING -s $SQUIDIP -p tcp --dport 80 -j ACCEPT $MARKLOG");
		

			if($SSL_BUMP==1){
			 shell_exec2("$iptables -t nat -A PREROUTING -s $SQUIDIP -p tcp --dport 443 -j ACCEPT $MARKLOG");
			}
		
	}
	
	shell_exec2("$iptables -t nat -A PREROUTING -p tcp --dport 80 -j REDIRECT --to-port $SQUIDPORT $MARKLOG");
	if($SSL_BUMP==1){shell_exec2("$iptables -t nat -A PREROUTING -p tcp --dport 443 -j REDIRECT --to-port $ssl_port $MARKLOG");}
	shell_exec2("$iptables -t nat -A POSTROUTING -j MASQUERADE $MARKLOG");
	shell_exec2("$iptables -t mangle -A PREROUTING -p tcp --dport $SQUIDPORT -j DROP $MARKLOG");	
	if($SSL_BUMP==1){
		shell_exec2("$iptables -t mangle -A PREROUTING -p tcp --dport $ssl_port -j DROP $MARKLOG");	
	}

}



function shell_exec2($cmd){
	if($GLOBALS["VERBOSE"]){echo "Starting......: Squid Transparent mode: EXECUTE `$cmd`\n";}
	exec($cmd,$results);
	if($GLOBALS["VERBOSE"]){echo "Starting......: Squid Transparent mode:". count($results)." rows\n";}
	while (list ($index, $row) = each ($results) ){if($GLOBALS["VERBOSE"]){echo "Starting......: Squid Transparent mode: \"$row\"\n";}}
	
	reset($results);
	if($GLOBALS["VERBOSE"]){echo "Starting......: OK: `$cmd`\n";}
	return $results;
	
	
}

function iptables_delete_all(){
echo "Starting......: Squid Check Transparent mode: removing iptables rules...\n";	
$unix=new unix();
$iptables_save=$unix->find_program("iptables-save");
$iptables_restore=$unix->find_program("iptables-restore");
system("$iptables_save > /etc/artica-postfix/iptables.conf");
$data=file_get_contents("/etc/artica-postfix/iptables.conf");
$datas=explode("\n",$data);
$pattern="#.+?ArticaSquidTransparent#";	
$d=0;
while (list ($num, $ligne) = each ($datas) ){
		if($ligne==null){continue;}
		if(preg_match($pattern,$ligne)){$d++;continue;}
		if(preg_match("#-A PREROUTING -p tcp -m tcp --dport 80 -j REDIRECT --to-ports [0-9]+#i",$ligne)){$d++;continue;}
		if(preg_match("#-A PREROUTING -p tcp -m tcp --dport 443 -j REDIRECT --to-ports [0-9]+#i",$ligne)){$d++;continue;}
		$conf=$conf . $ligne."\n";
		}
	file_put_contents("/etc/artica-postfix/iptables.new.conf",$conf);
	system("$iptables_restore < /etc/artica-postfix/iptables.new.conf");
	echo "Starting......: Squid Check Transparent mode: removing $d iptables rule(s) done...\n";	
}




