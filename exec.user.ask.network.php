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


$unix=new unix();
$clear=$unix->find_program("clear");
if(is_file($clear)){system("$clear");}


$users=new usersMenus();
$q=new mysql();

if(!$q->BD_CONNECT(true)){
	echo "There is an issue while connecting to MySQL\n$q->mysql_error\nPress Key to exit.\n";
	$line = fgets(STDIN);
	die();
	
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


echo "*********************************************\n";
echo "*********** - NETWORK CONFIGURATION - *******\n";
echo "*********************************************\n\n\n";

echo "This wizard will help to configure network.\n";
echo "Press q letter to exit or any key to continue:";
$answer = trim(strtolower(fgets(STDIN)));
if($answer=="q"){die();}
if(is_file($clear)){system("$clear");}

echo "Give here the interface name of the network interface\n";
echo "you need to setup.\n\n";
echo "Should be one of :".@implode(", ", $int)."\n";
echo "Default: [$DEFAULT]\n";
$NIC = trim(strtolower(fgets(STDIN)));
if($NIC==null){$NIC=$DEFAULT;}
$ETH_IP=ASK_ETH_IP($NIC);
$GATEWAY=ASK_GATEWAY($NIC);
$NETMASK=ASK_NETMASK($NIC);
$DNS=ASK_DNS1($NIC);
if(is_file($clear)){system("$clear");}

echo "Your Settings:\n";
echo "Interface.........: $NIC\n";
echo "IP address........: $ETH_IP\n";
echo "Gateway...........: $GATEWAY\n";
echo "Netmask...........: $NETMASK\n";
echo "DNS server 1......: $DNS\n";
echo "\n";
//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
echo "If your are agree with these settings\n";
echo "OR Press any key or press \"q\" to exit.\n";
$answer = trim(strtolower(fgets(STDIN)));
if($answer=="q"){die();}

echo "10%] Please Wait, saving configuration...\n";

$nics=new system_nic($NIC);
$nics->eth=$NIC;
$nics->IPADDR=$ETH_IP;
$nics->NETMASK=$NETMASK;
$nics->GATEWAY=$GATEWAY;
$nics->DNS1=$DNS;
$nics->dhcp=0;
$nics->metric=1;
$nics->enabled=1;
if(!$nics->SaveNic()){
	echo "There is an issue while saving your settings\n";
	echo "Press any key to exit.\n";
	$answer = trim(strtolower(fgets(STDIN)));
	die();
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
shell_exec2("$nohup /etc/init.d/artica-status restart >/dev/null 2>&1 &");
shell_exec2("$nohup /etc/init.d/nginx restart >/dev/null 2>&1 &");
echo "30%] Please Wait, Changing IP address to $NIC....\n";
$ifconfig=$unix->find_program("ifconfig");
shell_exec2("$ifconfig $NIC down");
shell_exec2("$ifconfig $NIC $ETH_IP netmask $NETMASK up");
shell_exec2("/bin/ip route add 127.0.0.1 dev lo");
shell_exec2("/sbin/route add $GATEWAY dev $NIC");
$tr=explode(".",$ETH_IP);
unset($tr[3]);
$net=@implode(".", $tr).".0";
shell_exec("/bin/ip route add $net/$NETMASK dev $NIC src $ETH_IP");
echo "80%] Please Wait, Changing DNS to $DNS....\n";
$resolv=new resolv_conf();
$resolv->MainArray["DNS1"]=$DNS;
$resolvDatas=$resolv->build();
@file_put_contents("/etc/resolv.conf", $resolvDatas);


echo "100%] Configuration done.\n";
echo "Press any key to exit.";
$answer = trim(strtolower(fgets(STDIN)));
die();


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
		if($answer=="q"){die();}
		ASK_ETH_IP($NIC);
		return;
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
	echo "Set here the Gateway address of your $NIC interface (default $DEFAULT)\n";
	$ip=trim(strtolower(fgets(STDIN)));
	if($ip==null){$ip=$DEFAULT;}
	
	if(!$tcp->isValid($ip)){
		echo "$ip is not a valid IP address\n";
		echo "Type q to exit or press key to retry\n";
		$answer = trim(strtolower(fgets(STDIN)));
		if($answer=="q"){die();}
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
		echo "$ip is not a valid IP address\n";
		echo "Type q to exit or press key to retry\n";
		$answer = trim(strtolower(fgets(STDIN)));
		if($answer=="q"){die();}
		ASK_NETMASK($NIC);
		return;
	}

	return $ip;
}
function ASK_DNS1($NIC){
	$tcp=new IP();
	$DEFAULT=null;
	$unix=new unix();
	$sock=new sockets();
	$f=explode("\n",@file_get_contents("/etc/resolv.conf"));
	
	
	while (list ($gpid, $val) = each ($f) ){
		if(preg_match("#^nameserver\s+(.+)#", $val,$re)){
			$DEFAULT=$re[1];
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
		if($answer=="q"){die();}
		ASK_DNS1($NIC);
		return;
	}

	return $ip;
}

