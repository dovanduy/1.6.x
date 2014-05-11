<?php
if(preg_match("#--verbose#",implode(" ",$argv))){
	$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
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
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");


MAIN_MENU();

function MAIN_MENU(){
$unix=new unix();
$clear=$unix->find_program("clear");
if(is_file($clear)){system("$clear");}

echo "NETWORK CONFIGURATOR Menu\n";
echo "---------------------------------------------\n";
echo "Mofify network parameters........: [1]\n";
echo "Reload/Restart Network...........: [2]\n";
echo "Remove any Firewall rules........: [3]\n";
echo "DNS setup........................: [4]\n";
echo "Exit menu........................: [q]\n";
echo "\n";

$answer=trim(strtolower(fgets(STDIN)));

switch ($answer) {
	case "1":ACTION_NETWORK();break;
	case "2":ACTION_NETWORK_RESTART();break;
	case "3":ACTION_KILL_IPTABLES();break;
	case "4":ACTION_DNS();break;
	case "q":die();break;
	default:
		;
	break;
	
	MAIN_MENU();
	return;
	
}


}

function ACTION_KILL_IPTABLES(){
	$unix=new unix();
	echo "Warning, this operation will remove all NAT/REDIRECT methods.\n";
	echo "Do you need to perform this operation ?\n";
	echo "Enter y to confirm or any key to abort.\n";
	$answer=trim(strtolower(fgets(STDIN)));
	if($answer<>"y"){return;}
	$iptables=$unix->find_program("iptables");
	
	shell_exec("$iptables -P INPUT ACCEPT");
	shell_exec("$iptables -P FORWARD ACCEPT");
	shell_exec("$iptables -P OUTPUT ACCEPT");
	shell_exec("$iptables -t nat -F");
	shell_exec("$iptables -t mangle -F");
	shell_exec("$iptables -F");
	shell_exec("$iptables -X");
	echo "\nSuccess\nPress any key to exit.\n";
	$answer = trim(strtolower(fgets(STDIN)));
	return;		
	
	
}

function ACTION_NETWORK_RESTART(){
	$unix=new unix();
	$clear=$unix->find_program("clear");
	if(is_file($clear)){system("$clear");}
	system("/etc/init.d/artica-ifup stop");
	system("/etc/init.d/artica-ifup start");
	echo "\nPress any key to exit.\n";
	$answer = trim(strtolower(fgets(STDIN)));
	return;	
}

function ACTION_DNS(){
	$unix=new unix();
	$clear=$unix->find_program("clear");
	if(is_file($clear)){system("$clear");}	
	
	echo "This wizard will help to configure DNS.\n";
	echo "Press q letter to exit or any key to continue:";
	$answer = trim(strtolower(fgets(STDIN)));
	if($answer=="q"){return;}
	if(is_file($clear)){system("$clear");}
	
	$tcp=new IP();
	$GLOBALS["PROGRESS"]=true;
	
	$f=explode("\n",@file_get_contents("/etc/resolv.conf"));
	while (list ($gpid, $val) = each ($f) ){
		if(preg_match("#^domain\s+(.+)#", $val,$re)){ $domain=$re[1]; break; }
	}
	
	
	
	$DN1=ASK_DNS1();
	$DN2=ASK_DNS2();
	
	echo "Set the search domain suffix: [$domain]\n";
	$domain2 = trim(strtolower(fgets(STDIN)));
	if($domain2<>null){$domain=$domain2;}
	
	
	echo "DNS server 1........: \"$DN1\"\n";
	echo "DNS server 2........: \"$DN2\"\n";
	echo "Search domain suffix: \"$domain\"\n";
	echo "\n";
	//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	echo "If your are agree with these settings\n";
	echo "Press any key to apply settings or press \"q\" to return to menu.\n";
	$answer = trim(strtolower(fgets(STDIN)));
	if($answer=="q"){return;}	
	
	echo "Loading DNS library...\n";
	$GLOBALS["PROGRESS"]=true;
	$resolv=new resolv_conf();
	$resolv->MainArray["DNS1"]=$DN1;
	$resolv->MainArray["DNS2"]=$DN2;
	$resolv->MainArray["DOMAINS1"]=$domain;
	echo "Saving DNS items ( please wait )...\n";
	$resolv->save();
	
	if($domain<>null){
		$f[]="domain $domain";
		$f[]="search $domain";
	}
	$f[]="options attempts:5 timeout:5";
	$f[]="nameserver $DN1";
	$f[]="nameserver $DN2\n";
	
	@file_put_contents("/etc/resolv.conf", @implode("\n", $f));
	
	echo "Saving DNS items done...\n";
	echo "Press any key to return to menu.";
	$answer = trim(strtolower(fgets(STDIN)));
	MAIN_MENU();
	
}
		

function ACTION_NETWORK(){
$unix=new unix();
$clear=$unix->find_program("clear");
if(is_file($clear)){system("$clear");}

$users=new usersMenus();
$q=new mysql();

if(!$q->BD_CONNECT(true)){
	echo "There is an issue while connecting to MySQL\n$q->mysql_error\nPress Key to exit.\n";
	$line = fgets(STDIN);
	return;

}

$DEFAULT=null;
$net=new networking();
$interfaces=$net->Local_interfaces();
unset($interfaces["lo"]);
if(isset($interfaces["eth0"])){$DEFAULT="eth0";}

while (list ($num, $letter) = each ($interfaces) ){
	$int[]="\"$num\"";
}
if($DEFAULT==null){$DEFAULT=$int[0];}
$q->BuildTables();

echo "This wizard will help to configure network.\n";
echo "Press q letter to exit or any key to continue:";
$answer = trim(strtolower(fgets(STDIN)));
if($answer=="q"){return;}
if(is_file($clear)){system("$clear");}

echo "Give here the interface name of the network interface\n";
echo "you need to setup.\n\n";
echo "Should be one of :".@implode(", ", $int)."\n";
echo "Default: [$DEFAULT]\n";
$NIC = trim(strtolower(fgets(STDIN)));
if($NIC==null){$NIC=$DEFAULT;}
$ETH_IP=trim(ASK_ETH_IP($NIC));
$GATEWAY=trim(ASK_GATEWAY($NIC));
$NETMASK=trim(ASK_NETMASK($NIC));
$DNS=trim(ASK_DNS1($NIC));
if(is_file($clear)){system("$clear");}

echo "Your Settings:\n";
echo "Interface.........: \"$NIC\"\n";
echo "IP address........: \"$ETH_IP\"\n";
echo "Gateway...........: \"$GATEWAY\"\n";
echo "Netmask...........: \"$NETMASK\"\n";
echo "DNS server 1......: \"$DNS\"\n";
echo "\n";
//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
echo "If your are agree with these settings\n";
echo "Press any key to apply settings or press \"q\" to return to menu.\n";
$answer = trim(strtolower(fgets(STDIN)));
if($answer=="q"){return;}

echo "5%] Please Wait, saving configuration...\n";

$nics=new system_nic($NIC);
$nics->eth=$NIC;
$nics->IPADDR=$ETH_IP;
$nics->NETMASK=$NETMASK;
$nics->GATEWAY=$GATEWAY;
$nics->DNS1=$DNS;
$nics->dhcp=0;
$nics->metric=1;
$nics->defaultroute=1;
$nics->enabled=1;
echo "7%] Please Wait, saving Networks parameters to MySQL DB...\n";
if(!$nics->SaveNic()){
	echo "There is an issue while saving your settings\n";
	echo "Press any key to exit.\n";
	$answer = trim(strtolower(fgets(STDIN)));
	return;
}
	
	
	
	echo "10%] Please Wait, building configuration....\n";
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$php5=$php;
	shell_exec2("$php5 ".dirname(__FILE__)." /exec.virtuals-ip.php --build --force >/dev/null 2>&1");
	echo "20%] Please Wait, apply network configuration....\n";
	shell_exec2("$php5 /usr/share/artica-postfix/exec.initslapd.php");
	shell_exec2("/etc/init.d/artica-ifup start");
	echo "30%] Please Wait, restarting services....\n";
	
	$unix->THREAD_COMMAND_SET("$php5 /usr/share/artica-postfix/exec.postfix.maincf.php --reconfigure");
	$unix->THREAD_COMMAND_SET("/usr/share/artica-postfix/bin/artica-install --reconfigure-cyrus");
	shell_exec2("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");
	shell_exec2("$nohup /etc/init.d/nginx restart >/dev/null 2>&1 &");
	shell_exec2("$nohup /etc/init.d/monit restart >/dev/null 2>&1 &");
	echo "30%] Please Wait, Changing IP address to $NIC....\n";
	$ifconfig=$unix->find_program("ifconfig");
	shell_exec2("$ifconfig $NIC down");
	shell_exec2("$ifconfig $NIC $ETH_IP netmask $NETMASK up");
	shell_exec2("/bin/ip route add 127.0.0.1 dev lo");
	if($GATEWAY<>"0.0.0.0"){
		echo "31%] Please Wait, Define default gateway to $GATEWAY....\n";
		shell_exec2("/sbin/route add $GATEWAY dev $NIC");
		$route=$unix->find_program("route");
		shell_exec("$route add -net 0.0.0.0 gw $GATEWAY dev $NIC metric 1");
	}
	echo "80%] Please Wait, Changing DNS to $DNS....\n";
	echo "81%] Please Wait, Loading DNS library\n";
	$GLOBALS["PROGRESS"]=true;
	$resolv=new resolv_conf();
	echo "92%] Set DNS1 to $DNS\n";
	$resolv->MainArray["DNS1"]=$DNS;
	$resolv->output=true;
	echo "93%] Saving config\n";
	$resolvDatas=$resolv->build();
	echo "94%] Saving /etc/resolv.conf\n";
	@file_put_contents("/etc/resolv.conf", $resolvDatas);
	echo "95%] Restarting Artica Web Console\n";
	shell_exec2("$nohup /etc/init.d/artica-webconsole restart");
	echo "100%] Configuration done.\n";
	echo "Press any key to return to menu.";
	$answer = trim(strtolower(fgets(STDIN)));
	MAIN_MENU();
}


function shell_exec2($cmd){
	echo "Executing: $cmd\n";
	shell_exec($cmd);
}


function ASK_ETH_IP($NIC){
$tcp=new IP();

$unix=new unix();
$sock=new sockets();
$clear=$unix->find_program("clear");
if(is_file($clear)){system("$clear");}
if($NIC=="eth0"){
	$savedsettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
	$DEFAULT=$savedsettings["IPADDR"];
}
if($DEFAULT==null){
	$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
	$DEFAULT=$NETWORK_ALL_INTERFACES[$NIC]["IPADDR"];
}
	echo "$NIC TCP/IP address:\n";
	echo "Set here the IP address of your $NIC interface (default $DEFAULT)\n";
	$ip=trim(strtolower(fgets(STDIN)));
	if($ip==null){$ip=$DEFAULT;}
	
	
	if(!$tcp->isValid($ip)){
		echo "$ip is not a valid IP address\n";
		echo "Type q to exit or press key to retry\n";
		$answer = trim(strtolower(fgets(STDIN)));
		if($answer=="q"){return;}
		ASK_ETH_IP($NIC);
		return $ip;
	}
	
	return $ip;
}


function ASK_GATEWAY($NIC){
	$tcp=new IP();
	$DEFAULT=null;
	$unix=new unix();
	$sock=new sockets();
	$clear=$unix->find_program("clear");
	if(is_file($clear)){system("$clear");}
	if($NIC=="eth0"){
		$savedsettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
		$DEFAULT=$savedsettings["GATEWAY"];
	}
	
	if($DEFAULT==null){
		$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
		$DEFAULT=$NETWORK_ALL_INTERFACES[$NIC]["GATEWAY"];
	}
	
	echo "Gateway TCP/IP address:\n";
	echo "Set here the Gateway address of your $NIC interface\nset \"0.0.0.0\" for no gateway (default $DEFAULT)\n";
	$ip=trim(strtolower(fgets(STDIN)));
	if($ip==null){$ip=$DEFAULT;}
	
	if(!$tcp->isValid($ip)){
		echo "$ip is not a valid IP address\n";
		echo "Type q to exit or press key to retry\n";
		$answer = trim(strtolower(fgets(STDIN)));
		if($answer=="q"){return;}
		ASK_GATEWAY($NIC);
		return;
	}

	return $ip;
}


function ASK_NETMASK($NIC){
	$tcp=new IP();
	$DEFAULT=null;
	$unix=new unix();
	$sock=new sockets();
	$clear=$unix->find_program("clear");
	if(is_file($clear)){system("$clear");}
	$DEFAULT="255.255.255.0";
	if($NIC=="eth0"){
		$savedsettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
		$DEFAULT=$savedsettings["NETMASK"];
	}
	
	if($DEFAULT==null){
		$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
		$DEFAULT=$NETWORK_ALL_INTERFACES[$NIC]["NETMASK"];
	}	
	
	echo "Netmask address:\n";
	echo "Set here the Netmask of your $NIC interface (default $DEFAULT)\n";
	$ip=trim(strtolower(fgets(STDIN)));
	if($ip==null){$ip=$DEFAULT;}

	if(!$tcp->isValid($ip)){
		echo "`$ip` is not a valid IP address\n";
		echo "Type q to exit or press key to retry\n";
		$answer = trim(strtolower(fgets(STDIN)));
		if($answer=="q"){return;}
		ASK_NETMASK($NIC);
		return;
	}

	return $ip;
}
function ASK_DNS1($NIC=null){
	$tcp=new IP();
	$DEFAULT=null;
	$unix=new unix();
	$sock=new sockets();
	$f=explode("\n",@file_get_contents("/etc/resolv.conf"));
	
	
	while (list ($gpid, $val) = each ($f) ){
		if(preg_match("#^nameserver\s+(.+)#", $val,$re)){
			$DEFAULT=$re[1];
			break;
		}
		
	}
	
	
	$clear=$unix->find_program("clear");
	if(is_file($clear)){system("$clear");}
	
	if($NIC=="eth0"){
		$savedsettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
		if($savedsettings["DNS1"]<>null){
		$DEFAULT=$savedsettings["DNS1"];
		}
	}

	

	echo "DNS address:\n";
	echo "Set here the IP address of your first DNS server (default $DEFAULT)\n";
	$ip=trim(strtolower(fgets(STDIN)));
	if($ip==null){$ip=$DEFAULT;}

	if(!$tcp->isValid($ip)){
		echo "$ip is not a valid IP address\n";
		echo "Type q to exit or press key to retry\n";
		$answer = trim(strtolower(fgets(STDIN)));
		if($answer=="q"){return;}
		
		return ASK_DNS1($NIC);
	}

	return $ip;
}
function ASK_DNS2(){
	$tcp=new IP();
	$DEFAULT=null;
	$unix=new unix();
	$sock=new sockets();
	$f=explode("\n",@file_get_contents("/etc/resolv.conf"));


	while (list ($gpid, $val) = each ($f) ){
		if(preg_match("#^nameserver\s+(.+)#", $val,$re)){
			$DNS[]=$re[1];
			if(count($DNS)>1){break;}
		}

	}


	$clear=$unix->find_program("clear");
	if(is_file($clear)){system("$clear");}

	if(count($DNS)>1){$DEFAULT=$DNS[1];}


	echo "DNS address (2):\n";
	echo "Set here the backup IP DNS server address  (default $DEFAULT)\n";
	$ip=trim(strtolower(fgets(STDIN)));
	if($ip==null){$ip=$DEFAULT;}

	if(!$tcp->isValid($ip)){
		echo "$ip is not a valid IP address\n";
		echo "Type q to exit or press key to retry\n";
		$answer = trim(strtolower(fgets(STDIN)));
		if($answer=="q"){return;}
		return ASK_DNS2();
		
	}

	return $ip;
}
