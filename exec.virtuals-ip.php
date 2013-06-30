#!/usr/bin/php
<?php
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/ressources/class.templates.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/framework/class.tcpip-parser.inc');
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.nics.inc');
include_once(dirname(__FILE__) . '/ressources/class.os.system.inc');

$GLOBALS["NO_GLOBAL_RELOAD"]=false;
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["SLEEP"]=false;
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}

if(preg_match("#--sleep#",implode(" ",$argv))){$GLOBALS["SLEEP"]=true;}
if($argv[1]=="--resolvconf"){resolvconf();exit;}
if($argv[1]=="--interfaces"){interfaces_show();die();}
//if(system_is_overloaded(basename(__FILE__))){writelogs("Fatal: Overloaded system,die()","MAIN",__FILE__,__LINE__);die();}

if($argv[1]=="--just-add"){routes();die();}
if($argv[1]=="--articalogon"){articalogon();die();}
if($argv[1]=="--ifconfig"){ifconfig_tests();exit;}
if($argv[1]=="--bridges"){bridges_build();exit;}
if($argv[1]=="--parse-tests"){ifconfig_parse($argv[2]);exit;}
if($argv[1]=="--routes"){routes();exit;}
if($argv[1]=="--routes-del"){routes_del($argv[2]);exit;}
if($argv[1]=="--vlans"){build();exit;}
if($argv[1]=="--postfix-instances"){postfix_multiples_instances();exit;}
if($argv[1]=="--ping"){ping($argv[2]);exit;}
if($argv[1]=="--ipv6"){Checkipv6();exit;}
if($argv[1]=="--ifupifdown"){ifupifdown($argv[2]);exit;}
if($argv[1]=="--reconstruct-interface"){reconstruct_interface($argv[2]);exit;}
if($argv[1]=="--ucarp"){ucarp_build();exit;}
if($argv[1]=="--ucarp-start"){ucarp_build();exit;}
if($argv[1]=="--ucarp-stop"){ucarp_stop();exit;}
if($argv[1]=="--net-rules"){persistent_net_rules();exit;}

if($argv[1]=="--routes"){routes();exit;}


if($GLOBALS["SLEEP"]){sleep(2);}
build();

//
//vconfig set_flag eth1.3 1 1
//vconfig set_flag eth1.4 1 1

//http://www.cyberciti.biz/tips/howto-configure-linux-virtual-local-area-network-vlan.html
//http://www.stg.net/vlanbridge


function ping($host){
	ini_set_verbosed();
	$unix=new unix();
	if($unix->PingHost($host)){
		echo "$host:TRUE\n";
	}else{
		echo "$host:FALSE\n";
	}
	
}

function interfaces_show(){
	$nic=new system_nic();
	$datas=$nic->root_build_debian_config();
	echo $datas;
}

function resolvconf(){
	$resolv=new resolv_conf();
	$resolvDatas=$resolv->build();
	@file_put_contents("/etc/resolv.conf", $resolvDatas);
	if(is_dir("/var/spool/postfix/etc")){@file_put_contents("/var/spool/postfix/etc/resolv.conf", $resolvDatas);}
	if(is_dir("/etc/resolvconf")){
		@mkdir("/etc/resolvconf/resolv.conf.d",0755,true);
		$f=array();
		if($resolv->MainArray["DNS1"]<>null){$f[]="nameserver {$resolv->MainArray["DNS1"]}";}
		if($resolv->MainArray["DNS2"]<>null){$f[]="nameserver {$resolv->MainArray["DNS2"]}";}
		if($resolv->MainArray["DNS3"]<>null){$f[]="nameserver {$resolv->MainArray["DNS3"]}";}
		if(count($f)>0){
			@file_put_contents("/etc/resolvconf/resolv.conf.d/base", @implode("\n", $f));
		}
	}
	
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $php5 ".dirname(__FILE__)."/exec.dnsmasq.php >/dev/null 2>&1 &");
	
	
}

function ucarp_stop(){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();
	
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		echo "Starting......: UCARP Start task already running PID: $oldpid\n";
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	$ucarp_bin=$unix->find_program("ucarp");
	if(!is_file($ucarp_bin)){echo "Starting......: UCARP Not installed...\n";return;}	
	$pids=ucarp_all_pid();
	$kill=$unix->find_program("kill");
	
	echo "Starting......: UCARP Found (".count($pids).") processe(s)\n";
	
	while (list ($pid, $line) = each ($pids) ){
		echo "Starting......: UCARP checks PID:$pid processe(s)\n";
		ucarp_stop_single($pid);
	}
	
	
}

function dev_shm(){
	if(!is_dir("/dev/shm/network")){return;}
	if(!is_dir("/etc/network")){return;}
	if(!is_link("/etc/network/run")){
		$unix=new unix();
		$ln=$unix->find_program("ln");
		shell_exec("$ln -s /dev/shm/network /etc/network/run");
	}
	if($GLOBALS["VERBOSE"]){echo "Line:".__LINE__." dev_shm ->done\n";}
	
}



function ucarp_stop_single($pid){
	$unix=new unix();
	$ucarp_bin=$unix->find_program("ucarp");
	if(!is_file($ucarp_bin)){echo "Starting......: UCARP Not installed...\n";return;}
	$kill=$unix->find_program("kill");
	$ifconfig=$unix->find_program("ifconfig");
	if(!$unix->process_exists($pid)){
		echo "Starting......: UCARP [$pid]: Not running...\n";
		return;
	}
	
	$cmdline=var_export(@file_get_contents("/proc/$pid/cmdline"),true);
	if(preg_match("#'--interface=(.+?)'#", $cmdline,$re)){
		echo "Starting......: UCARP: [$pid]: Shutting down interface ucarp:{$re[1]}...\n";
		shell_exec("$ifconfig {$re[1]}:ucarp down");
	}
		
	echo "Starting......: UCARP: [$pid]: Shutting down $pid...\n";
	for($i=0;$i<10;$i++){
		shell_exec("$kill $pid >/dev/null 2>&1");
		sleep(1);
		if(!$unix->process_exists($pid)){break;}
	}
	
	if(!$unix->process_exists($pid)){
		echo "Starting......: UCARP: [$pid]: Shutting down success...\n";
	}
}

function ucarp_build(){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();
	$sock=new sockets();
	
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		echo "Starting......: UCARP Start task already running PID: $oldpid\n";
		return;
	}
	@file_put_contents($pidfile, getmypid());
	$users=new settings_inc();
	if(!$users->CORP_LICENSE){echo "Starting......: UCARP No license set, aborting...\n";return;}
	$ucarp_bin=$unix->find_program("ucarp");
	
	if(!is_file($ucarp_bin)){echo "Starting......: UCARP Not installed...\n";return;}
	
	$sql="SELECT * FROM `nics` WHERE enabled=1 AND `ucarp-enable`=1 ORDER BY Interface";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "Starting......: UCARP: MySQL Error: $q->mysql_error\n";return;}
	$count=mysql_num_rows($results);
	@unlink("/etc/network/if-up.d/ucarp");
	if($count==0){echo "Starting......: UCARP: Network Unconfigured\n";return;}
	
	$FINAL[]="#!/bin/sh";
	
	$pid=ucarp_pid();
	$kill=$unix->find_program("kill");
	$ifconfig=$unix->find_program("ifconfig");
	
	$EnableChilli=$sock->GET_INFO("EnableChilli");
	$chilli=$unix->find_program("chilli");
	
	
	if(!is_numeric($EnableChilli)){$EnableChilli=0;}	
	if(is_file($chilli)){
		if($EnableChilli==1){
			$ChilliConf=unserialize(base64_decode($sock->GET_INFO("ChilliConf")));
			$SKIP_INTERFACE=strtolower(trim($ChilliConf["HS_LANIF"]));
		}
		
	}
	

	
	$php5=$unix->LOCATE_PHP5_BIN();
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$eth=trim(strtolower($ligne["Interface"]));
		if($SKIP_INTERFACE==$eth){
			echo "Starting......: UCARP: Skipping interface: $SKIP_INTERFACE\n";
			continue;
		}
		$downfile="/usr/share/ucarp/vip-$eth-down.sh";
		$upfile="/usr/share/ucarp/vip-$eth-up.sh";
		$ucarpcmd=array();
		$ucarpcmd[]=$ucarp_bin;
		$ucarpcmd[]="--interface=$eth";
		$ucarpcmd[]="--srcip={$ligne["IPADDR"]}";
		$ucarpcmd[]="--vhid={$ligne["ucarp-vid"]}";
		$ucarpcmd[]="--passfile=/etc/artica-postfix/ucarppass";
		
		$ucarpcmd[]="--addr={$ligne["ucarp-vip"]}";
		
		if($ligne["ucarp-master"]==0){
			$advAdd=$ligne["ucarp-advskew"]+5;
			if($advAdd>255){$advAdd=255;}
			$ucarpcmd[]="--advskew=$advAdd";
		}else{
			$ucarpcmd[]="--preempt";
			$ucarpcmd[]="--advskew=1";
			$ucarpcmd[]="--advbase=1";
			
		}
		$ucarpcmd[]="--neutral";
		$ucarpcmd[]="--ignoreifstate";
		$ucarpcmd[]="--upscript=$upfile";
		$ucarpcmd[]="--downscript=$downfile";
		$ucarpcmd[]="--daemonize";
		@file_put_contents("/etc/artica-postfix/ucarppass", "secret");
		@chmod("/etc/artica-postfix/ucarppass",0700);
		$ucarpcmdLINE=@implode(" ", $ucarpcmd);
		$FINAL[]=@implode(" ", $ucarpcmd);

		$down=array();
		$down[]="#!/bin/sh";
		$down[]="$ifconfig $eth:ucarp down";
		$down[]="$php5 ".__FILE__." --ucarp-notify $1 $2 $3 $4 $5 >/dev/null 2>&1";
		$down[]="exit 0\n";
		@file_put_contents($downfile, @implode("\n", $down));
		@chmod($downfile, 0755);
		
		$up=array();
		$up[]="#!/bin/sh";
		$up[]="$ifconfig $eth:ucarp {$ligne["ucarp-vip"]} netmask {$ligne["NETMASK"]} up";
		$up[]="$php5 ".__FILE__." --ucarp-notify $1 $2 $3 $4 $5 >/dev/null 2>&1";
		$up[]="exit 0\n";
		@file_put_contents($upfile, @implode("\n", $up));
		@chmod($upfile, 0755);	

		
		
		shell_exec($ucarpcmdLINE);
		sleep(1);
		$pid=ucarp_pid($eth);
		if($unix->process_exists($pid)){
			shell_exec("/usr/share/ucarp/vip-$eth-up.sh");
		}else{
			echo "Starting......: UCARP: Not running\n";
		}
	}	
	
	$FINAL[]="";
	echo "Starting......: UCARP: /etc/network/if-up.d/ucarp done..\n";
	@file_put_contents("/etc/network/if-up.d/ucarp", @implode("\n", $FINAL));
	@chmod("/etc/network/if-up.d/ucarp", 0755);
	
	
}

function ucarp_pid($eth=null){
	$unix=new unix();
	$ucarp_bin=$unix->find_program("ucarp");
	if($eth<>null){$eth=".*?--interface=$eth";}
	return $unix->PIDOF_PATTERN("$ucarp_bin$eth");
	
}
function ucarp_all_pid($eth=null){
	$unix=new unix();
	$ucarp_bin=$unix->find_program("ucarp");
	if($eth<>null){$eth=".*?--interface=$eth";}
	return $unix->PIDOF_PATTERN_ALL("$ucarp_bin$eth");

}

function reconstruct_interface($eth){
	$GLOBALS["NO_GLOBAL_RELOAD"]=true;
	if($GLOBALS["SLEEP"]){sleep(10);}
	build();
	ifupifdown($eth);
}

function events($text,$function,$line){
	$unix=new unix();
	$unix->events($text,"/var/log/artica-network.log",false,$function,$line);
	
	
}

function build(){
	$unix=new unix();
	$users=new usersMenus();
	$q=new mysql();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$sock=new sockets();
	$oom_kill_allocating_task=$sock->GET_INFO("oom_kill_allocating_task");
	if(!is_numeric($oom_kill_allocating_task)){$oom_kill_allocating_task=1;}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$oldpid=@file_get_contents($pidfile);
	$sysctl=$unix->find_program("sysctl");
	$ifconfig=$unix->find_program("ifconfig");
	
	if($unix->process_exists($oldpid,basename(__FILE__))){
		event("Building networks already executed PID: $oldpid",__FUNCTION__,__LINE__);
		echo "Starting......: Building networks already executed PID: $oldpid\n";
		die();
	}	
	
	if($oom_kill_allocating_task==1){
		echo "Starting......: Kernel oom_kill_allocating_task is enabled\n";
		shell_exec("$sysctl -w \"vm.oom_dump_tasks=1\" >/dev/null 2>&1");
		shell_exec("$sysctl -w \"vm.oom_kill_allocating_task=1\" >/dev/null 2>&1");
		
	}else{
		echo "Starting......: Kernel oom_kill_allocating_task is disabled\n";
		shell_exec("$sysctl -w \"vm.oom_dump_tasks=0\" >/dev/null 2>&1");
		shell_exec("$sysctl -w \"vm.oom_kill_allocating_task=0\" >/dev/null 2>&1");		
	}
	
	if($GLOBALS["VERBOSE"]){echo "Line:".__LINE__." persistent_net_rules()\n";}
	
	persistent_net_rules();
	if($GLOBALS["VERBOSE"]){echo "Line:".__LINE__." dev_shm()\n";}
	dev_shm();
	$ip=$unix->find_program("ip");
	
	$IPROUTEFOUND=false;
	exec("$ip route",$results);
	events("IP route -> ".count($results)." lines",__FUNCTION__,__LINE__);
	while (list ($index, $line) = each ($results) ){
	events("IP route -> $line",__FUNCTION__,__LINE__);
	if(preg_match("#default via#", $line)){
		events("IP route found default via -> $line",__FUNCTION__,__LINE__);
		$IPROUTEFOUND=true;
	}
	
	if(!$IPROUTEFOUND){@unlink("/etc/artica-postfix/MEM_INTERFACES");}
	if(is_file("/etc/artica-postfix/MEM_INTERFACES")){
		$MEM_INTERFACES=unserialize(@file_get_contents("/etc/artica-postfix/MEM_INTERFACES"));
	}
	if($GLOBALS["VERBOSE"]){echo "Line:".__LINE__." MEM_INTERFACES()\n";}
	$EXECUTE_CMDS=true;
	
	
	if(is_array($MEM_INTERFACES)){
		$EXECUTE_CMDS=false;
		if($GLOBALS["VERBOSE"]){echo "Line:".__LINE__." NETWORK_ALL_INTERFACES()\n";}
		$array=$unix->NETWORK_ALL_INTERFACES();
		while (list ($Interface, $ipaddr) = each ($MEM_INTERFACES) ){
			if($ipaddr==null){continue;}
			if($GLOBALS["VERBOSE"]){echo "Line:".__LINE__." $Interface Must be $ipaddr -> {$array[$Interface]["IPADDR"]}\n";}
			events("$Interface Must be $ipaddr -> {$array[$Interface]["IPADDR"]}",__FUNCTION__,__LINE__);
			if($ipaddr<>$array[$Interface]["IPADDR"]){
				events("Must rebuilded....",__FUNCTION__,__LINE__);
				$EXECUTE_CMDS=true;
				break;
			}
		}
	}
	
	if(!$EXECUTE_CMDS){
		events("Nothing to do stop function()...",__FUNCTION__,__LINE__);
		if($GLOBALS["VERBOSE"]){echo "Line:".__LINE__."  EXECUTE_CMDS -> FALSE()\n";}
		routes();
		return;
	}
	
	if($q->mysql_server=="127.0.0.1"){
		if(!is_file("/var/run/mysqld/mysqld.sock")){
			event("/var/run/mysqld/mysqld.sock no such file",__FUNCTION__,__LINE__);
			echo "Starting......: Building networks MySQL database not available starting MySQL service...\n";
			shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.initd-mysql.php >/dev/null 2>&1 &");
			shell_exec("$nohup /etc/init.d/mysql start >/dev/null 2>&1 &");
			sleep(1);
			for($i=0;$i<5;$i++){
				$q=new mysql();
				if(!is_file("/var/run/mysqld/mysqld.sock")){
					echo "Starting......: Building networks waiting MySQL database to start...$i/4\n";
					sleep(1);
				}else{
					break;
				}
			}
			if(!is_file("/var/run/mysqld/mysqld.sock")){
				event("/var/run/mysqld/mysqld.sock no such file",__FUNCTION__,__LINE__);
				echo "Starting......: Building networks MySQL database not available...\n";
				if(is_file("/etc/init.d/network-urgency.sh")){shell_exec("/etc/init.d/network-urgency.sh");}
				$unix->THREAD_COMMAND_SET($php5." ".__FILE__." --build");
				die();
			}
			
		}
	}
	
	
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.initd-mysql.php >/dev/null 2>&1 &");

	if(!$q->BD_CONNECT()){
		sleep(1);
		event("Building networks MySQL database not available starting MySQL service",__FUNCTION__,__LINE__);
		echo "Starting......: Building networks MySQL database not available starting MySQL service...\n";
		shell_exec("$nohup /etc/init.d/mysql start >/dev/null 2>&1 &");
		
		for($i=0;$i<5;$i++){
			$q=new mysql();
			if(!$q->BD_CONNECT()){
				echo "Starting......: Building networks waiting MySQL database to start...$i/4\n";
				sleep(1);
			}else{
				break;
			}
			
		}
		
		$q=new mysql();
		if(!$q->BD_CONNECT()){
			event("Building networks MySQL database not available...",__FUNCTION__,__LINE__);
			echo "Starting......: Building networks MySQL database not available...\n";
			if(is_file("/etc/init.d/network-urgency.sh")){
				event(" -> /etc/init.d/network-urgency.sh",__FUNCTION__,__LINE__);
				shell_exec("/etc/init.d/network-urgency.sh");
			}
			die();
		}
		
	}

$GLOBALS["SAVED_INTERFACES"]=array();
Checkipv6();
@file_put_contents($pidfile,getmypid());

	if($users->AS_DEBIAN_FAMILY){
		echo "Starting......: Building networks Debian family\n";
		events("-> BuildNetWorksDebian()",__FUNCTION__,__LINE__);
		BuildNetWorksDebian();
		

	}else{
		echo "Starting......: Building networks RedHat family\n";
		BuildNetWorksRedhat();
	}
	echo "Starting......: Building networks checking bridge\n";
	bridges_build();
	echo "Starting......: Building networks checking IPV6\n";
	Checkipv6();
	
	echo "Starting......: Building networks Reloading ". count($GLOBALS["SAVED_INTERFACES"])." interface(s)\n";
	
	
	if(count($GLOBALS["SAVED_INTERFACES"])==0){
		echo "Starting......: Building networks Building Ipv6 virtuals IP...\n";
		Checkipv6Virts();
	}
	
	$EXECUTE_CMDS=false;
	if(is_array($GLOBALS["MEM_INTERFACES"])){
		@file_put_contents("/etc/artica-postfix/MEM_INTERFACES", serialize($GLOBALS["MEM_INTERFACES"]));
		$array=$unix->NETWORK_ALL_INTERFACES();
		while (list ($Interface, $ipaddr) = each ($GLOBALS["MEM_INTERFACES"]) ){
			if($ipaddr==null){continue;}
			events("$Interface Must be $ipaddr -> {$array[$Interface]["IPADDR"]}");
			if($ipaddr<>$array[$Interface]["IPADDR"]){
				events("Must rebuilded....");
				$EXECUTE_CMDS=true;
				break;
			}
		}
	}
	
	$GLOBALS["SCRIPTS"][]="$ifconfig lo down";
	$GLOBALS["SCRIPTS"][]="$ifconfig lo 127.0.0.1 up";
	
	@unlink("/etc/init.d/network-urgency.sh");
	
	$sh[]="#!/bin/sh -e";
	$sh[]="# Builded on ". date("Y-m-d H:i:s");
	
	
	
	
	routes_main();

	while (list ($index, $line) = each ($GLOBALS["SCRIPTS"]) ){
		echo "Starting......: `$line`\n";
		$sh[]="echo \"Starting......: $line\"";
		$sh[]=$line;
		if($EXECUTE_CMDS){
			event("$line",__FUNCTION__,__LINE__);
			system($line);}
	}
		usleep(500);
	}	
	$sh[]="exit 0\n";
	reset($GLOBALS["SCRIPTS"]);
	@file_put_contents("/etc/init.d/network-urgency.sh", @implode("\n", $sh));
	@chmod("/etc/init.d/network-urgency.sh",0755);
	
	
	echo "Starting......: done...\n";
	
}

function BuildNetWorksDebian(){
	if(!is_file("/etc/network/interfaces")){return;}
	echo "Starting......: Building networks mode Debian\n";
	$nic=new system_nic();
	
	$datas=$nic->root_build_debian_config();
	if($datas==null){
		events("Not yet configured");
		echo "Starting......: Not yet configured\n";
		return;
	}
	
	echo "Starting......: ". strlen($datas)." bytes length\n";
	events("Saving /etc/network/interfaces");
	@file_put_contents("/etc/network/interfaces",$datas);
	bridges_build();
	$unix=new unix();
	$unix->THREAD_COMMAND_SET($unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.ip-rotator.php --build");
	
	}

function BuildNetWorksRedhat(){
	
	echo "Starting......: Building networks mode RedHat\n";
	$nic=new system_nic();
	$datas=$nic->root_build_redhat_config();
	bridges_build();
	$unix=new unix();
	$unix->THREAD_COMMAND_SET($unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.ip-rotator.php --build");
	if(!$GLOBALS["NO_GLOBAL_RELOAD"]){$unix->NETWORK_REDHAT_RESTART();}
	}


function ifconfig_tests(){
	$unix=new unix();
	$cmd=$unix->find_program("ifconfig")." -s";
	exec($cmd,$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#^(.+?)\s+[0-9]+#",$line,$re)){
			$array[trim($re[1])]=trim($re[1]);
		}
	}
	print_r($array);
	
}


function bridges_build(){
	$unix=new unix();
	$iptables=$unix->find_program("iptables");
	$sysctl=$unix->find_program("sysctl");
	$iptables_rules=array();
	$sql="SELECT * FROM iptables_bridge ORDER BY ID DESC";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");	
	if(!$q->ok){return null;}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
		$array_virtual_infos=VirtualNicInfosIPaddr($ligne["nics_virtuals_id"]);
		$nicvirtual=$array_virtual_infos["IPADDR"];
		if($nicvirtual==null){continue;}
		$nic_linked=trim($ligne["nic_linked"]);
		if(trim($nic_linked)==null){continue;}
		
		if(preg_match("#(.+?):([0-9]+)#",$nic_linked,$re)){
			$array_virtual_infos=VirtualNicInfosIPaddr($re[2]);
			$nic_linked=$array_virtual_infos["IPADDR"];
		}
		
		$id=$ligne["ID"];
		echo "Starting......: Virtuals bridge $nicvirtual to $nic_linked\n";
		$iptables_rules[]="$iptables -A FORWARD -i $nicvirtual -o $nic_linked -m state --state ESTABLISHED,RELATED -j ACCEPT -m comment --comment \"ArticaBridgesVirtual:$id\" 2>&1";
		$iptables_rules[]="$iptables -A FORWARD -i $nicvirtual -o $nic_linked -j ACCEPT -m comment --comment \"ArticaBridgesVirtual:$id\" 2>&1";
		$iptables_rules[]="$iptables -t nat -A POSTROUTING -o $nic_linked -j MASQUERADE	-m comment --comment \"ArticaBridgesVirtual:$id\" 2>&1";	
		
	}
	
	bridges_delete();
	$rules=0;
	if(count($iptables_rules)>0){
		while (list ($index, $chain) = each ($iptables_rules) ){	
			unset($results);
			exec($chain,$results);
			if(count($results)>0){
				echo "Starting......: Virtuals bridge ERROR $chain\n";
				while (list ($num, $line) = each ($results) ){echo "Starting......: Virtuals bridge ERROR $line\n";}
			}else{
				$rules=$rules+1;
			}
			
		}
	}
	if($rules>0){
		shell_exec("$sysctl -w net.ipv4.ip_forward=1");
	}
	
	echo "Starting......: Virtuals bridge adding iptables $rules rule(s)\n";
}

function bridges_delete(){
	$unix=new unix();
	echo "Starting......: Virtuals bridge Deleting old rules\n";
	$iptables_save=$unix->find_program("iptables-save");
	$iptables_restore=$unix->find_program("iptables-restore");
	$conf=null;
	$cmd="$iptables_save > /etc/artica-postfix/iptables.conf";
	if($GLOBALS["VERBOSE"]){echo "Starting......: $cmd\n";}		
	shell_exec($cmd);

	
	$data=file_get_contents("/etc/artica-postfix/iptables.conf");
	$datas=explode("\n",$data);
	$pattern="#.+?ArticaBridgesVirtual#";	
	$count=0;
while (list ($num, $ligne) = each ($datas) ){
		if($ligne==null){continue;}
		if(preg_match($pattern,$ligne)){
			if($GLOBALS["VERBOSE"]){echo "Starting......: Delete $ligne\n";}		
			$count++;continue;}
			$conf=$conf . $ligne."\n";
		}

file_put_contents("/etc/artica-postfix/iptables.new.conf",$conf);
$cmd="$iptables_restore < /etc/artica-postfix/iptables.new.conf";
if($GLOBALS["VERBOSE"]){echo "Starting......: $cmd\n";}
shell_exec("$cmd");
echo "Starting......: Virtuals bridge cleaning iptables $count rules\n";	
}


function ifconfig_parse($path=null){
	$unix=new unix();
	print_r($unix->NETWORK_DEBIAN_PARSE_ARRAY($path));
	
}

function routes_fromfile(){
	
	if(!is_file("/etc/artica-postfix/ROUTES.CACHES.TABLES")){
		echo "Starting......: Building routes, no cache file\n";
		return;
	}
	
	$unix=new unix();
	$route=$unix->find_program("route");
	$ip=$unix->find_program("ip");

	$f=explode("\n",@file_get_contents("/etc/iproute2/rt_tables"));
	while (list ($eth, $ligne) = each ($f) ){
		if(preg_match("#^([0-9]+)\s+(.+)#", $ligne,$re)){
			$tableID=$re[1];
			if($tableID==255){continue;}
			if($tableID==254){continue;}
			if($tableID==253){continue;}
			$array[$tableID]=$re[2];
		}
	
	}
	
	while (list ($id, $ligne) = each ($array) ){
		shell_exec("$ip route flush table $ligne");
	
	}	
	
	
	$array=unserialize("/etc/artica-postfix/ROUTES.CACHES.TABLES");
	$TABLES=$array["TABLES"];
	$NEXT=$array["NEXT"];
	$CMDS=$array["CMDS"];
	
	while (list ($id, $cmdline) = each ($CMDS) ){
		shell_exec($cmdline);
	}
	
	
	$f[]="255\tlocal";
	$f[]="254\tmain";
	$f[]="253\tdefault";
	$f[]="0\tunspec";
	$c=1;
	if(count($TABLES)>0){
		while (list ($id, $ligne) = each ($TABLES) ){
			$f[]="$c\t$ligne";
				
		}
	
	file_put_contents("/etc/iproute2/rt_tables", @implode("\n", $f));
			while (list ($id, $cmdline) = each ($NEXT) ){
				echo "$cmdline\n";
				shell_exec($cmdline);
			}
		}
		reset($TABLES);
		reset($NEXT);
		
	
}


function PARSECDR($pattern){
	if($pattern==null){return;}
	if(strpos($pattern, "/")==0){return $pattern;}
	
	$re=explode("/",$pattern);
	if(strpos($re[1], ".")>0){
		$tcp=new Unixipv4($re[0], $re[1]);
		return $tcp->NetMaskToCdir();
	}
	
	return $pattern;
	
	
}
function routes_main(){
	$unix=new unix();
	$route=$unix->find_program("route");
	$ip=$unix->find_program("ip");
	$types[1]="{network_nic}";
	$types[2]="{host}";
	$sql="SELECT * FROM nic_routes ORDER BY `nic`";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q=new mysql();
	$GLOBALS["SCRIPTS"][]="$route add 127.0.0.1";
	$GLOBALS["SCRIPTS"][]="$route add -net 127.0.0.0 netmask 255.0.0.0 lo";
	

	
	$results=$q->QUERY_SQL($sql,"artica_backup");
	
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$type=$ligne["type"];
		$ttype="-net";
		if($type==1){
			$ttype="-net";
			if($ligne["nic"]<>null){$dev=" dev {$ligne["nic"]}";}
			$cmd="$ip route add {$ligne["gateway"]} $dev >/dev/null 2>&1";
			$CMDS="$cmd >/dev/null 2>&1";
			$GLOBALS["SCRIPTS"][]=$CMDS;
				
		}
	
	
		if($type==2){$ttype="-host";}
		if($ligne["nic"]<>null){$dev=" dev {$ligne["nic"]}";}
		$cmd="$route add $ttype {$ligne["pattern"]} gw {$ligne["gateway"]}$dev";
		if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
		$GLOBALS["SCRIPTS"][]=$CMDS;
	
	}
	
}


function routes(){
	$unix=new unix();
	$route=$unix->find_program("route");
	$ip=$unix->find_program("ip");
	$types[1]="{network_nic}";
	$types[2]="{host}";	

	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q=new mysql();	
	
	$f=explode("\n",@file_get_contents("/etc/iproute2/rt_tables"));
	while (list ($eth, $ligne) = each ($f) ){
		if(preg_match("#^([0-9]+)\s+(.+)#", $ligne,$re)){
			$tableID=$re[1];
			if($tableID==255){continue;}
			if($tableID==254){continue;}
			if($tableID==253){continue;}
			if($tableID==0){continue;}
			$array[$tableID]=$re[2];
		}
		
	}
	
	while (list ($id, $ligne) = each ($array) ){
		echo "Starting......: Building routes, flush table `$ligne`\n";
		shell_exec("$ip route flush table $ligne");
		
	}
	

	
	
	$sql="SELECT * FROM iproute_table WHERE enable=1 ORDER BY routename";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		echo "Starting......: Building routes, $q->mysql_error\n";
		routes_fromfile();
		return;
	}
	
	
	$rtid=0;
	$countOfRoutes=mysql_num_rows($results);
	if($GLOBALS["VERBOSE"]){echo "Line:".__LINE__." $countOfRoutes routes\n";}
	if($countOfRoutes==0){return;}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$rtname=$ligne["routename"];
		$rtid++;		
		$eth=$ligne["interface"];
		$gw=$ligne["gateway"];
		
		
		if($gw<>null){
			$NEXT[]="$ip route add $gw dev $eth";
			
		}
	
		$TABLES[]=$rtname;
		echo "Starting......: Building routes, Group {$ligne["ID"]}\n";
		$sql="SELECT * FROM iproute_rules WHERE ruleid={$ligne["ID"]} AND enable=1 ORDER BY priority";
		$results2=$q->QUERY_SQL($sql,"artica_backup");
		$tt[]=array();
		
		while ($ligne2 = mysql_fetch_assoc($results2)) {
				$src=PARSECDR($ligne2["src"]);
				$destination=PARSECDR($ligne2["destination"]);
				$priority=$ligne2["priority"];
				echo "Starting......: Building routes, source=$src, dest=$destination, GW=$gw\n";
				$POS=route_between_subnet($src,$destination,$priority,$eth,$rtname);
				if($POS<>null){
					$NEXT[]="$ip $POS";
					continue;
				}
				
				$POS=route_from($src,$destination,$priority,$eth,$rtname);
				if($POS<>null){
					$NEXT[]="$ip $POS";
					continue;
				}			
	
				$POS=route_desc($src,$destination,$priority,$eth,$rtname,$gw);
				if($POS<>null){
					$NEXT[]="$ip $POS";
					continue;
				}

				
				
				
		}
	
		
	
	}
	
	$f=array();
	$f[]="255\tlocal";
	$f[]="254\tmain";
	$f[]="253\tdefault";
	$f[]="0\tunspec";
	$c=1;
	if(count($TABLES)>0){
		while (list ($id, $ligne) = each ($TABLES) ){
			$f[]="$c\t$ligne";
			shell_exec("$ip route flush table $ligne");
			
		}
		@file_put_contents("/etc/iproute2/rt_tables", @implode("\n", $f));
		while (list ($id, $cmdline) = each ($NEXT) ){
			shell_exec("$cmdline >/dev/null 2>&1");
		}
	}
	reset($TABLES);
	reset($NEXT);
	$FINAL["TABLES"]=$TABLES;
	$FINAL["NEXT"]=$NEXT;

	
	
	@file_put_contents("/etc/artica-postfix/ROUTES.CACHES.TABLES", serialize($FINAL));
	
}
function route_desc($src,$destination,$priority,$eth,$rtname,$gw){
	if($src<>null){return;}
	if($destination==null){return;}
	if($gw==null){return;}
	$prioritytext=null;
	if($priority>0){
		$prioritytext=" priority $priority ";
	}
	
	return "route add$prioritytext to $destination via $gw dev $eth table $rtname";	
	
}

function route_from($src,$destination,$priority,$eth,$rtname){
	if($src==null){return;}
	if($destination<>null){return;}
	$prioritytext=null;
	if($priority>0){
		$prioritytext=" priority $priority ";
	}
	
	return "rule add$prioritytext from $src dev $eth table $rtname";	
	
}

function route_between_subnet($src,$destination,$priority,$eth,$rtname){
	if($src==null){return;}
	if($destination==null){return;}
	$prioritytext=null;
	if($priority>0){
		$prioritytext=" priority $priority ";
	}
	
	return "rule add$prioritytext from $src to $destination dev $eth table $rtname";
}

function routes_del($md5){
	$unix=new unix();
	$route=$unix->find_program("route");	
	$q=new mysql();
	$sql="SELECT * FROM nic_routes WHERE `zmd5`='$md5'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$type=$ligne["type"];
	$ttype="-net";
	if($type==1){$ttype="-net";}
	if($type==2){$ttype="-host";}
	if($ligne["nic"]<>null){$dev=" dev {$ligne["nic"]}";}
	$cmd="$route del $ttype {$ligne["pattern"]} gw {$ligne["gateway"]}$dev";
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}	
	shell_exec("$cmd >/dev/null 2>&1");
	$sql="DELETE FROM nic_routes WHERE `zmd5`='$md5'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
	
	
}


function postfix_multiples_instances(){
	build();
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	
	$sql="SELECT ou, ip_address, `key` , `value` FROM postfix_multi WHERE `key` = 'myhostname'";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
		$hostname=$ligne["value"];
		echo "Starting......: reconfigure postfix instance $hostname\n";
		shell_exec("$php /usr/share/artica-postfix/exec.postfix-multi.php --instance-reconfigure \"$hostname\"");
	}
}

function Checkipv6Virts(){
	$unix=new unix();
	$sock=new sockets();
	$EnableipV6=$sock->GET_INFO("EnableipV6");
	if(!is_numeric($EnableipV6)){$EnableipV6=0;}	
	if($EnableipV6==0){return;}
	$q=new mysql();
	$sql="SELECT nic FROM nics_virtuals WHERE ipv6=1";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	$eths=array();
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){		
		$eths[$ligne["nic"]]=$ligne["nic"];
		
	}
	if(count($eths)==0){
		echo "Starting......: Building Ipv6 virtuals IP -> 0 interface...\n";
		return;
	}
	
	
	$echo=$unix->find_program("echo");
	$ipbin=$unix->find_program("ip");
	$ip=new IP();
	$sh=array();
	while (list ($eth, $ligne) = each ($eths) ){
		echo "Starting......: Building Ipv6 virtuals IP for `$eth` interface...\n";
		$sh[]="$echo 0 > /proc/sys/net/ipv6/conf/$eth/disable_ipv6";		
		$sh[]="$echo 0 > /proc/sys/net/ipv6/conf/$eth/autoconf";
		$sh[]="$echo 0 > /proc/sys/net/ipv6/conf/$eth/accept_ra";
		$sh[]="$echo 0 > /proc/sys/net/ipv6/conf/$eth/accept_ra_defrtr";
		$sh[]="$echo 0 > /proc/sys/net/ipv6/conf/$eth/accept_ra_pinfo";
		$sh[]="$echo 0 > /proc/sys/net/ipv6/conf/$eth/accept_ra_rtr_pref";	
		$sql="SELECT * FROM nics_virtuals WHERE ipv6=1 AND nic='$eth' ORDER BY ID DESC";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
			$ipv6addr=$ligne["ipaddr"];
			$netmask=$ligne["netmask"];
			if(!is_numeric($netmask)){$netmask=0;}
			if($netmask==0){continue;}
			if(!$ip->isIPv6($ipv6addr)){continue;}
			echo "Starting......: Building Ipv6 virtuals IP for `$eth` [$ipv6addr/$netmask]...\n";
  		    $sh[]="$ipbin addr add dev $eth $ipv6addr/$netmask";
  		 		
			
		}
		
	}
	
	if(count($sh)==0){return;}
	while (list ($num, $cmdline) = each ($sh) ){
		if($GLOBALS["VERBOSE"]){echo "Starting......: Building Ipv6 virtuals $cmdline\n";}
		shell_exec($cmdline);
	}
	

}


function Checkipv6(){
	$unix=new unix();
	$sock=new sockets();
	$EnableipV6=$sock->GET_INFO("EnableipV6");
	if(!is_numeric($EnableipV6)){$EnableipV6=0;}
	
	if($EnableipV6==0){
		echo "Starting......: Building networks IPv6 is disabled\n";
	}else{
		echo "Starting......: Building networks IPv6 is enabled\n";
	}
	
	$unix->sysctl("net.ipv6.conf.all.disable_ipv6",$EnableipV6);
	$unix->sysctl("net.ipv6.conf.default.disable_ipv6",$EnableipV6);
	$unix->sysctl("net.ipv6.conf.lo.disable_ipv6",$EnableipV6);
	
	@file_put_contents("/proc/sys/net/ipv6/conf/lo/disable_ipv6",$EnableipV6);
	@file_put_contents("/proc/sys/net/ipv6/conf/lo/disable_ipv6",$EnableipV6);
	@file_put_contents("/proc/sys/net/ipv6/conf/all/disable_ipv6",$EnableipV6);
	@file_put_contents("/proc/sys/net/ipv6/conf/default/disable_ipv6",$EnableipV6);
	echo "Starting......: Building networks IPv6 done...\n";
}

function ifupifdown($eth){
return;
}

function articalogon(){
	if(!is_file("/etc/artica-postfix/network.first.settings")){return;}
	$f=explode(";", @file_get_contents("/etc/artica-postfix/network.first.settings"));
	//l.Add(IP+';'+Gayteway+';'+netmask+';'+DNS); 
	$IPADDR=$f[0];
	$GATEWAY=$f[1];
	$NETMASK=$f[2];
	$DNS1=$f[3];
	$eth=$f[4];
	
	$nics=new system_nic($eth);
	$nics->eth=$eth;
	$nics->IPADDR=$IPADDR;
	$nics->NETMASK=$NETMASK;
	$nics->GATEWAY=$GATEWAY;
	$nics->DNS1=$DNS1;
	$nics->dhcp=0;
	$nics->enabled=1;
	$nics->NoReboot=true;
	$nics->SaveNic();
	echo "Settings $eth ($IPADDR) done...\n";
	
}
function persistent_net_rules(){
	if(!is_dir("/etc/udev/rules.d")){return;}
	$filename="/etc/udev/rules.d/70-persistent-net.rules";
	if(is_file($filename)){return;}
	
	
	$unix=new unix();
	$fz=$unix->dirdir("/sys/class/net");
	
	$final=array();
	while (list ($net, $line) = each ($fz) ){
		$line=basename($line);
		if(!preg_match("#eth[0-9]+#", $line)){continue;}
		$array=udevadm_eth($line);
		if(!$array){echo "Starting......: Building persistent rule `FAILED` for `$line`\n";continue;}
		echo "Starting......: Building persistent rule for `$line` {$array["MAC"]}\n";
		$final[]="SUBSYSTEM==\"net\", ACTION==\"add\", DRIVERS==\"?*\", ATTR{address}==\"{$array["MAC"]}\", ATTR{dev_id}==\"{$array["dev_id"]}\", ATTR{type}==\"{$array["TYPE"]}\", KERNEL==\"eth*\", NAME=\"$line\"";
		
	}
	
	if(count($final)>0){
		echo "Starting......: Building $filename done\n";
		@file_put_contents($filename, @implode("\n", $final)."\n");
		
	}
	
	
}

function udevadm_eth($eth){
	$unix=new unix();
	$udevadm=$unix->find_program("udevadm");
	if(!is_file($udevadm)){return false;}
	$MAC=null;
	$dev_id=null;
	$type=null;
	exec("udevadm info -a -p /sys/class/net/$eth",$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match('#ATTR.*?address.*?=="(.+?)"#', $line,$re)){$MAC=$re[1];continue;}
		if(preg_match('#ATTR.*?dev_id.*?=="(.+?)"#', $line,$re)){$dev_id=$re[1];continue;}
		if(preg_match('#ATTR.*?type.*?=="(.+?)"#', $line,$re)){$type=$re[1];continue;}
		
	}
	if($MAC==null){return false;}
	if($dev_id==null){return false;}
	if($type==null){return false;}
	return array("MAC"=>$MAC,"DEV"=>$dev_id,"TYPE"=>$type); 
}




//

?>
