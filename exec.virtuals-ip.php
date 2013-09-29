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
$GLOBALS["AFTER_REBUILD"]=false;
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["SLEEP"]=false;
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}

if(preg_match("#--sleep#",implode(" ",$argv))){$GLOBALS["SLEEP"]=true;}
if(preg_match("#--afterrebuild#",implode(" ",$argv))){$GLOBALS["AFTER_REBUILD"]=true;}

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
if($argv[1]=="--build"){build();exit;}
if($argv[1]=="--postfix-instances"){postfix_multiples_instances();exit;}
if($argv[1]=="--ping"){ping($argv[2]);exit;}
if($argv[1]=="--ipv6"){Checkipv6();exit;}
if($argv[1]=="--ifupifdown"){ifupifdown($argv[2]);exit;}
if($argv[1]=="--reconstruct-interface"){reconstruct_interface($argv[2]);exit;}
if($argv[1]=="--ucarp"){ucarp_build();exit;}
if($argv[1]=="--ucarp-start"){ucarp_start();exit;}
if($argv[1]=="--ucarp-stop"){ucarp_stop();exit;}
if($argv[1]=="--net-rules"){persistent_net_rules();exit;}
if($argv[1]=="--main-routes"){routes_main_build();exit;}
if($argv[1]=="--routes"){routes();exit;}
if($argv[1]=="--vlans-build"){vlan_build();exit;}
if($argv[1]=="--vlans-delete"){vlan_delete($argv[2]);exit;}
if($argv[1]=="--virtip-build"){virtip_build($argv[2]);exit;}
if($argv[1]=="--virtip-delete"){virtip_delete($argv[2]);exit;}
if($argv[1]=="--bridge-delete"){bridge_delete($argv[2]);exit;}
if($argv[1]=="--bridge-rm"){bridge_deletemanu($argv[2]);exit;}
if($argv[1]=="--hosts"){etc_hosts_exec();exit;}
if($argv[1]=="--hosts-defaults"){etc_hosts_defaults();exit;}
if($argv[1]=="--iptables-bridge-delete"){bridges_delete();exit;}



if($GLOBALS["SLEEP"]){sleep(2);}
dev_shm();
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

function ScriptInfo($line){
	$line=str_replace("\n", "", $line);
	$line=str_replace("\r", "", $line);
	$line=str_replace("\r\n", "", $line);
	return $line;
	
}

function dev_shm(){
	if(!is_dir("/etc/network")){return;}
	if(!is_dir("/dev/shm/network")){@mkdir("/dev/shm/network",0755,true);}
	
	if(!is_link("/etc/network/run")){
		$unix=new unix();
		$ln=$unix->find_program("ln");
		shell_exec("$ln -s /dev/shm/network /etc/network/run");
	}
	if($GLOBALS["VERBOSE"]){echo "Line:".__LINE__." dev_shm ->done\n";}
	
}

function vlan_build(){
	$nic=new system_nic();
	$nic->BuildVlans();
	if(count($GLOBALS["SCRIPTS"])>0){
		while (list ($index, $line) = each ($GLOBALS["SCRIPTS"]) ){
			$line=trim($line);
			if(substr($line, 0,1)=="#"){$sh[]=$line;continue;}
			if($line==null){continue;}
			$md=md5($line);
			if(isset($AL[$md])){continue;}
			$AL[$md]=true;			
			
			echo "Starting......: `$line`\n";
			$sh[]="echo \"Starting......: $line\"";
			events("$line",__FUNCTION__,__LINE__);
			system($line);
		}
		usleep(500);
	}
	BuildNetWorksDebian();
}
function virtip_build(){
	$nic=new system_nic();
	$nic->BuildVirtIps();
	if(count($GLOBALS["SCRIPTS"])>0){
		while (list ($index, $line) = each ($GLOBALS["SCRIPTS"]) ){
			$line=trim($line);
			if($line==null){continue;}
			$md=md5($line);
			if(substr($line, 0,1)=="#"){$sh[]=ScriptInfo($line);continue;}
			if(isset($AL[$md])){continue;}
			$AL[$md]=true;
				
			echo "Starting......: `$line`\n";
			$sh[]="echo \"Starting......: $line\"";
			events("$line",__FUNCTION__,__LINE__);
			system($line);
		}
		usleep(500);
	}
	BuildNetWorksDebian();
}

function etc_hosts_exec(){
	
	
	etc_hosts();
	if(count($GLOBALS["SCRIPTS"])>0){
		while (list ($index, $line) = each ($GLOBALS["SCRIPTS"]) ){
			$line=trim($line);
			if($line==null){continue;}
			$md=md5($line);
			if(substr($line, 0,1)=="#"){continue;}
			if(isset($AL[$md])){continue;}
			$AL[$md]=true;
			echo "Starting......: `$line`\n";
			events("$line",__FUNCTION__,__LINE__);
			system($line);
		}

	}
	
	
}

function etc_hosts(){
	$unix=new unix();
	$echo=$unix->find_program("echo");
	$sock=new sockets();
	$hostname=$sock->GET_INFO("myhostname");
	$q=new mysql();
	$DisableEtcHosts=$sock->GET_INFO("DisableEtcHosts");
	if(!is_numeric($DisableEtcHosts)){$DisableEtcHosts=0;}
	if($DisableEtcHosts==1){
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] ****      HOSTS FILE       ****";
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] Disabled by DisableEtcHosts";
		return;
	}
	
	if($q->COUNT_ROWS("net_hosts", "artica_backup")==0){
		etc_hosts_defaults();
	}
	if($q->COUNT_ROWS("net_hosts", "artica_backup")==0){
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] net_hosts issue on MySQL keep the file untouched";
		return;
	}
	
	$f[]="::1     localhost ip6-localhost ip6-loopback";
	$f[]="::1     $hostname";
	$f[]="fe00::0 ip6-localnet";
	$f[]="ff00::0 ip6-mcastprefix";
	$f[]="ff02::1 ip6-allnodes";
	$f[]="ff02::2 ip6-allrouters";
	$f[]="ff02::3 ip6-allhosts";
	$f[]="ff02::3 ip6-allhosts";

	$GLOBALS["SCRIPTS"][]="# [".__LINE__."]";
	$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
	$GLOBALS["SCRIPTS"][]="# [".__LINE__."] ****      HOSTS FILE       ****";
	$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
	$GLOBALS["SCRIPTS"][]="# [".__LINE__."]";
	$GLOBALS["SCRIPTS"][]="# this first line flush the host file";
	$GLOBALS["SCRIPTS"][]="$echo \"127.0.0.1     $hostname\" >/etc/hosts";	
	while (list ($index, $line) = each ($f) ){
		$GLOBALS["SCRIPTS"][]="$echo \"$line\" >> /etc/hosts";
	}
	
	$sql="SELECT * FROM net_hosts ORDER BY hostname";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	$ip=new IP();
	while ($ligne = mysql_fetch_assoc($results)) {
		
		while (list ($a, $b) = each ($ligne) ){
			$ligne[$a]=str_replace("\r\n", " ", $ligne[$a]);
			$ligne[$a]=str_replace("\n", " ", $ligne[$a]);
			$ligne[$a]=str_replace("\r", " ", $ligne[$a]);
			$ligne[$a]=trim($ligne[$a]);
		}
		
		if(!$ip->isValid($ligne["ipaddr"])){
			$GLOBALS["SCRIPTS"][]="# [".__LINE__."]: {$ligne["ipaddr"]} not valid for {$ligne["hostname"]}";
			continue;
		}
		
		if(trim($ligne["hostname"])==null){continue;}
		
		if(trim($ligne["alias"])<>null){$ligne["alias"]="\t{$ligne["alias"]}";}
		$lineExe=trim("{$ligne["ipaddr"]}\t{$ligne["hostname"]}{$ligne["alias"]}");
		$GLOBALS["SCRIPTS"][]="$echo \"$lineExe\" >> /etc/hosts";
		
		
	}
	
	$GLOBALS["SCRIPTS"][]="#";
	
	
}

function vlan_delete($ID){
	$sql="SELECT * FROM nics_vlan WHERE ID='$ID'";
	$q=new mysql();
	if(!is_numeric($ID)){return;}
	if($ID<1){return;}	
	$unix=new unix();
	if(!isset($GLOBALS["moprobebin"])){$GLOBALS["moprobebin"]=$unix->find_program("modprobe");}
	if(!isset($GLOBALS["vconfigbin"])){$GLOBALS["vconfigbin"]=$unix->find_program("vconfig");}
	if(!isset($GLOBALS["ifconfig"])){$GLOBALS["ifconfig"]=$unix->find_program("ifconfig");}
	if(!isset($GLOBALS["ipbin"])){$GLOBALS["ipbin"]=$unix->find_program("ip");}	
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$eth="{$ligne["nic"]}";
	$vlanid=$ligne["vlanid"];
	if($vlanid==0){return;}
	if($vlanid>0){$vlanid_text=".$vlanid";}	
	shell_exec("{$GLOBALS["ifconfig"]} $eth$vlanid_text down");
	shell_exec("{$GLOBALS["vconfigbin"]} rem eth$vlanid_text");
	$q->QUERY_SQL("DELETE FROM nics_vlan WHERE ID='$ID'","artica_backup");
	BuildNetWorksDebian();
}
function virtip_delete($ID){
	if(!is_numeric($ID)){return;}
	if($ID<1){return;}
	$sql="SELECT * FROM nics_virtuals WHERE ID='$ID'";
	$q=new mysql();
	
	$unix=new unix();
	if(!isset($GLOBALS["moprobebin"])){$GLOBALS["moprobebin"]=$unix->find_program("modprobe");}
	if(!isset($GLOBALS["vconfigbin"])){$GLOBALS["vconfigbin"]=$unix->find_program("vconfig");}
	if(!isset($GLOBALS["ifconfig"])){$GLOBALS["ifconfig"]=$unix->find_program("ifconfig");}
	if(!isset($GLOBALS["ipbin"])){$GLOBALS["ipbin"]=$unix->find_program("ip");}
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$eth="{$ligne["nic"]}:{$ligne["ID"]}";
	shell_exec("{$GLOBALS["ifconfig"]} $eth down");
	$q->QUERY_SQL("DELETE FROM nics_virtuals WHERE ID='$ID'","artica_backup");
	BuildNetWorksDebian();	
	
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

function ucarp_build($nopid=false){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();
	$sock=new sockets();
	@unlink("/usr/share/ucarp/ETH_LIST");
	
	
	if(!$nopid){
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			echo "Starting......: UCARP Start task already running PID: $oldpid\n";
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	
	$users=new settings_inc();
	if(!$users->CORP_LICENSE){
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *** FAILOVER License error  ***";
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
		echo "Starting......: UCARP No license set, aborting...\n";
		return;
	}
	$ucarp_bin=$unix->find_program("ucarp");
	
	if(!is_file($ucarp_bin)){echo "Starting......: UCARP Not installed...\n";return;}
	
	$sql="SELECT * FROM `nics` WHERE enabled=1 AND `ucarp-enable`=1 ORDER BY Interface";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		echo "Starting......: UCARP: MySQL Error: $q->mysql_error\n";
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] **** FAILOVER Mysql ERROR! ****";
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
		return;
	}
	
	$count=mysql_num_rows($results);
	@unlink("/etc/network/if-up.d/ucarp");
	if($count==0){
		echo "Starting......: UCARP: Network Unconfigured\n";
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] **** FAILOVER Unconfigured ****";
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
		return;
	}
	
	$FINAL[]="#!/bin/sh";
	
	$pid=ucarp_pid();
	$kill=$unix->find_program("kill");
	$ifconfig=$unix->find_program("ifconfig");
	
	$EnableChilli=$sock->GET_INFO("EnableChilli");
	$chilli=$unix->find_program("chilli");
	$ETHS=array();
	
	if(!is_numeric($EnableChilli)){$EnableChilli=0;}	
	if(is_file($chilli)){
		if($EnableChilli==1){
			$ChilliConf=unserialize(base64_decode($sock->GET_INFO("ChilliConf")));
			$SKIP_INTERFACE=strtolower(trim($ChilliConf["HS_LANIF"]));
			$GLOBALS["SCRIPTS"][]="# [".__LINE__."] **** Skip $SKIP_INTERFACE with chilli ****";
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
		
		system_failover_events("Configure:<br>Building configuration for $eth",__FUNCTION__,basename(__FILE__),__LINE__);
		
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
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] ****    FAILOVER $eth     ****";
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
		$GLOBALS["SCRIPTS"][]=$ucarpcmdLINE;
		$GLOBALS["SCRIPTS"][]="/usr/share/ucarp/vip-$eth-up.sh";
		
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

		
		$ETHS[$eth]=$ucarpcmdLINE;
		
	}	
	
	$FINAL[]="";
	echo "Starting......: UCARP: /etc/network/if-up.d/ucarp done..\n";
	shell_exec("$php5 ".__FILE__." --ucarp-start --afterrebuild");
	@file_put_contents("/etc/network/if-up.d/ucarp", @implode("\n", $FINAL));
	@file_put_contents("/usr/share/ucarp/ETH_LIST", serialize($ETHS));
	@chmod("/etc/network/if-up.d/ucarp", 0755);
	
	
}
function ucarp_start(){
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
	
	
	if(is_file("/etc/network/if-up.d/ucarp")){
		if(!is_file("/usr/share/ucarp/ETH_LIST")){
			if(!$GLOBALS["AFTER_REBUILD"]){ucarp_build(true);}
			return;
		}
	}
	if(!is_file("/usr/share/ucarp/ETH_LIST")){echo "Starting......: UCARP Not configured, Apply network parameters first (1)...\n";return;}
	$ETHS=unserialize(@file_get_contents("/usr/share/ucarp/ETH_LIST"));
	if(!is_array($ETHS)){
		echo "Starting......: UCARP Not configured (2 not an array), Apply network parameters first...\n";
		return;
	}
	if(count($ETHS)==0){echo "Starting......: UCARP Not configured (3)...\n";return;}
	
	while (list ($eth, $ucarpcmdLINE) = each ($ETHS) ){
		$pid=ucarp_pid($eth);
		if($unix->process_exists($pid)){
			echo "Starting......: UCARP `$eth` already running pid $pid\n";
			if(ucarp_eth_ucarped($eth)){echo "Starting......: UCARP `$eth` alreaded linked\n";continue;}
			shell_exec("/usr/share/ucarp/vip-$eth-up.sh");
			continue;
		}
		
		shell_exec($ucarpcmdLINE);
		sleep(1);
		$pid=ucarp_pid($eth);
		echo "Starting......: UCARP `$eth` PID:$pid\n";
		if(!$unix->process_exists($pid)){
			system_failover_events("Fatal:<br>Unable to start daemon for $eth",__FUNCTION__,basename(__FILE__),__LINE__);
			echo "Starting......: UCARP `$eth` failed `$ucarpcmdLINE`\n";continue;}
		if(!ucarp_eth_ucarped($eth)){
			system_failover_events("Daemon:<br>`$eth` linking to network",__FUNCTION__,basename(__FILE__),__LINE__);
			echo "Starting......: UCARP `$eth` linking to network...\n";
			shell_exec("/usr/share/ucarp/vip-$eth-up.sh");
		}
	}
	
}

function ucarp_eth_ucarped($eth){
	if(!isset($GLOBALS["ucarp_eth_ucarped"])){
		$unix=new unix();
		$ip=$unix->find_program("ip");
		exec("$ip addr 2>&1",$GLOBALS["ucarp_eth_ucarped"]);
	}
	reset($GLOBALS["ucarp_eth_ucarped"]);
	while (list ($index, $line) = each ($GLOBALS["ucarp_eth_ucarped"]) ){
		if(preg_match("#inet\s+([0-9\.]+).*?secondary $eth:ucarp#",$line)){
			return true;
		}
		
	}
	return false;
	
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

function events($text,$function=null,$line=null){
	$unix=new unix();
	
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			if($sourcefile==null){$sourcefile=basename($trace[1]["file"]);}
			if($function==null){$function=$trace[1]["function"];}
			if($line==null){$line=$trace[1]["line"];}
		}
			
	}
	
	
	$unix->events($text,"/var/log/artica-network.log",false,$function,$line);
	
	
}
function event($text,$function=null,$line=null){events($text,$function,$line);}




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
	$GLOBALS["SCRIPTS_DOWN"]=array();
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
	$echobin=$unix->find_program("echo");
	$IPROUTEFOUND=false;
	exec("$ip route",$results);
	events("IP route -> ".count($results)." lines",__FUNCTION__,__LINE__);
	
	while (list ($index, $line) = each ($results) ){
		events("IP route -> $line",__FUNCTION__,__LINE__);
	
		if(preg_match("#default via#", $line)){
			events("IP route found default via -> $line",__FUNCTION__,__LINE__);
			$IPROUTEFOUND=true;
		}
		
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
			die();
		}
		
	}
	
	
	if(!$q->TABLE_EXISTS("nics","artica_backup",true)){
		echo "Starting......: Building networks MySQL table is not yet builded..\n";
		die();
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
	
	
	$GLOBALS["SCRIPTS_TOP"][]="# [".__LINE__."]";
	$GLOBALS["SCRIPTS_TOP"][]="# [".__LINE__."] *******************************";
	$GLOBALS["SCRIPTS_TOP"][]="# [".__LINE__."] **** SETTINGS for LOOP BACK ***";
	$GLOBALS["SCRIPTS_TOP"][]="# [".__LINE__."] *******************************";
	$GLOBALS["SCRIPTS_TOP"][]="# [".__LINE__."]";	
	$GLOBALS["SCRIPTS_TOP"][]="$ifconfig lo 127.0.0.1 up";
	$GLOBALS["SCRIPTS_TOP"][]="# [".__LINE__."]";
	
	
	
	$sh=array();
	$sh[]="#!/bin/sh -e";
	$sh[]="### BEGIN INIT INFO";
	$sh[]="# Builded on ". date("Y-m-d H:i:s");
	$sh[]="# Provides:          artica-ifup";
	$sh[]="# Required-Start:    \$local_fs";
	$sh[]="# Required-Stop:     \$local_fs";
	$sh[]="# Should-Start:";
	$sh[]="# Should-Stop:";
	$sh[]="# Default-Start:     1 2 3 4";
	$sh[]="# Default-Stop:      0 1 6";
	$sh[]="# Short-Description: start and stop the network";
	$sh[]="# Description:       Artica ifup service";
	$sh[]="### END INIT INFO";
	$sh[]="case \"\$1\" in";
	$sh[]="start)";
	
	etc_hosts();
	routes_main();
	bridges_build();
	ucarp_build(true);
	
	
	$sh[]="$echobin \"\" > /var/log/net-start.log";
	$sh[]="$echobin \"  **** Apply Network configuration, please wait... ****\"";
	
	while (list ($index, $line) = each ($GLOBALS["SCRIPTS_TOP"]) ){
		$line=trim($line);
		if($line==null){continue;}
		if(substr($line, 0,1)=="#"){$sh[]=ScriptInfo($line);continue;}
		$md=md5($line);
		if(isset($AL[$md])){
			echo "Starting......: SKIPING `$line`\n";
			continue;
		}
		$AL[$md]=true;
		echo "Starting......: `$line`\n";
		
		if(strpos($line, "/etc/hosts")>0){
			$sh[]="$line";
			continue;
		}
		
		if(preg_match("#ifconfig\s+(.+?)\s+(.+?)netmask(.+?)\s+#", $line,$re)){
			$sh[]="$echobin \"adding {$re[2]}/{$re[3]} in {$re[1]} interface\"";
				
		}
		
		$sh[]="$echobin \"$line\" >>/var/log/net-start.log 2>&1";
		$sh[]="$line >>/var/log/net-start.log 2>&1 || true";	
		
	}


	while (list ($index, $line) = each ($GLOBALS["SCRIPTS"]) ){
		
			$line=trim($line);
			if($line==null){continue;}
			if(substr($line, 0,1)=="#"){$sh[]=ScriptInfo($line);continue;}
			
			if(preg_match("#^OUTPUT\s+(.+)#",$line,$re)){
				$line=str_replace('"' ,"'", $line);
				$sh[]="$echobin \"{$re[1]}\"";
				continue;
			}
				
			
			
			$md=md5($line);
			if(isset($AL[$md])){
				echo "Starting......: SKIPING `$line`\n";
				continue;
			}
			$AL[$md]=true;		
			echo "Starting......: `$line`\n";
			
			if(strpos($line, "/etc/hosts")>0){
				$sh[]="$line";
				continue;
			}			
		
		
		if(preg_match("#ifconfig\s+(.+?)\s+(.+?)netmask(.+?)\s+#", $line,$re)){
			$sh[]="$echobin \"adding {$re[2]}/{$re[3]} in {$re[1]} interface\"";
			
		}	
			
		if(strpos('echo "', $line)==0){
			$sh[]="$echobin \"$line\" >>/var/log/net-start.log 2>&1";
		}
		$sh[]="$line >>/var/log/net-start.log 2>&1 || true"; 
	}
	
	if(count($GLOBALS["SCRIPTS_ROUTES"])>0){
		$sh[]="";
		$sh[]="# [".__LINE__."]";
		$sh[]="# [".__LINE__."] *******************************";
		$sh[]="# [".__LINE__."] ****     NETWORK ROUTES    ****";
		$sh[]="# [".__LINE__."] *******************************";
		$sh[]="# [".__LINE__."]";
		while (list ($index, $line) = each ($GLOBALS["SCRIPTS_ROUTES"]) ){
		
			$line=trim($line);
			if($line==null){continue;}
			if(substr($line, 0,1)=="#"){$sh[]=ScriptInfo($line);continue;}
			$md=md5($line);
			
			
			if(isset($AL[$md])){
				if(!preg_match("#^force", $line)){
					echo "Starting......: SKIPING `$line`\n";
					continue;
				}
			}
			
			if(preg_match("#^force:(.+)#", $line,$re)){$line=$re[1];$md=md5($line);}
			
			$AL[$md]=true;
			
			
			if(preg_match("#ip route add (.+?)\s+.*?src\s+(.+)#",$line,$re)){
				$sh[]="$echobin \"Create route for network {$re[1]} for local address {$re[2]}\"";
			}
			
			if(preg_match("#ip route add (.+?)\s+via(.+?)\s+src\s+([0-9\.]+)#",$line,$re)){
				$sh[]="$echobin \"Create route for network {$re[1]} using gateway {$re[2]} for local address {$re[3]}\"";
			}
	
			$sh[]="$echobin \"$line\" >>/var/log/net-start.log 2>&1";
			if(preg_match("#\/echo\s+#", $line)){$sh[]=$line;continue;}
			$sh[]="$line >>/var/log/net-start.log 2>&1 || true";
			
		}	
	
	}
	
	
	
	
		
	$sh[]="if [ -x /etc/init.d/artica-ifup-content.sh ] ; then";
	$sh[]="	/etc/init.d/artica-ifup-content.sh";
	$sh[]="fi";
	
	$sh[]=nics_vde_build();
	
	
	
	$unix=new unix();
	$squid=$unix->LOCATE_SQUID_BIN();
	if(is_file($squid)){
		$sh[]="#Reloading squid";
		$sh[]="echo \"reloading squid ( if exists )\"";
		$sh[]="$squid -k reconfigure >>/var/log/net-start.log 2>&1 || true";
	}
	
	$sh[]="#Reloading sshd (if exists)";
	$sh[]="echo \"Reloading sshd ( if exists )\"";
	$sh[]="$php5 /usr/share/artica-postfix/exec.sshd.php --reload 2>&1 || true";
	
	$sh[]="#Reloading pdns (if exists)";
	$sh[]="echo \"Reloading PowerDNS ( if exists )\"";
	$sh[]="$php5 /usr/share/artica-postfix/exec.pdns.php --reload 2>&1 || true";
	
	$sh[]="#Reloading DHCPD (if exists)";
	$sh[]="echo \"Reloading DHCP server ( if exists )\"";
	$sh[]="$php5 /usr/share/artica-postfix/exec.dhcpd.compile.php --reload-if-run 2>&1 || true";	
	$sh[]="echo \"  ****      Apply Network configuration, done      ****\"";
	$sh[]=";;";
	$sh[]="  stop)";
	if(is_array($GLOBALS["SCRIPTS_DOWN"])){
		while (list ($index, $line) = each ($GLOBALS["SCRIPTS_DOWN"]) ){	
			if(substr($line, 0,1)=="#"){$sh[]=ScriptInfo($line);continue;}
			$sh[]="$line >>/var/log/net-stop.log 2>&1 || true";
			
		}
	}

	
	$sh[]="    ;;";	
	
	
	$sh[]="*)";
	$sh[]=" echo \"Usage: $0 {start only}\"";
	$sh[]="exit 1";
	$sh[]=";;";
	$sh[]="esac";
	$sh[]="exit 0\n";
	

	@file_put_contents("/etc/init.d/artica-ifup", @implode("\n", $sh));
	@chmod("/etc/init.d/artica-ifup",0755);
	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -fartica-ifup defaults >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add artica-ifup >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 1234 artica-ifup on >/dev/null 2>&1");
	}	
	
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

function nics_vde_build(){
	if(isset($GLOBALS["nics_vde_build"])){return;}
	$GLOBALS["nics_vde_build"]=true;
	$unix=new unix();
	$ifconfig=$unix->find_program("ifconfig");
	$vde_tunctl=$unix->find_program("vde_tunctl");
	if(!is_file($vde_tunctl)){return;}
	$php5=$unix->LOCATE_PHP5_BIN();
	
	
	$f[]="";
	$f[]="# [".__LINE__."]";
	$f[]="# [".__LINE__."] *******************************";
	$f[]="# [".__LINE__."] ****   Virtual switches    ****";
	$f[]="# [".__LINE__."] *******************************";
	$f[]="# [".__LINE__."]";
	
	$sql="SELECT nic FROM nics_vde GROUP BY nic";
	
	
	
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	$f[]="# [".__LINE__."]:". mysql_num_rows($results). " switche(s)";
	if(!$q->ok){return null;}
	
	shell_exec("$php5 ". dirname(__FILE__)."/exec.vde.php --reconfigure");	
	$f[]="$php5 ". dirname(__FILE__)."/exec.vde.php --start >>/var/log/net-start.log 2>&1 || true";
	return @implode("\n", $f);
}


function bridges_build(){
	if(isset($GLOBALS["bridges_build_executed"])){return;}
	$GLOBALS["bridges_build_executed"]=true;
	$unix=new unix();
	$iptables=$unix->find_program("iptables");
	$sysctl=$unix->find_program("sysctl");
	$php5=$unix->LOCATE_PHP5_BIN();
	$iptables_rules=array();
	$sql="SELECT * FROM iptables_bridge ORDER BY ID DESC";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");	
	if(!$q->ok){return null;}
	
	$GLOBALS["SCRIPTS"][]="";
	$GLOBALS["SCRIPTS"][]="# [".__LINE__."]";
	$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
	$GLOBALS["SCRIPTS"][]="# [".__LINE__."] ****   Iptables Bridges    ****";
	$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
	$GLOBALS["SCRIPTS"][]="# [".__LINE__."]";
	$GLOBALS["SCRIPTS"][]="# [".__LINE__."]:". mysql_num_rows($results). " rule(s)";
	$GLOBALS["SCRIPTS"][]="$php5 ". __FILE__." --iptables-bridge-delete";
	if(mysql_num_rows($results)==0){return;}
	$GLOBALS["SCRIPTS"][]="$sysctl -w net.ipv4.ip_forward=1";
	
	$NetBuilder=new system_nic();
	$NetBuilder->LoadTools();
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
		if($ligne["nics_virtuals_id"]>0){
			$array_virtual_infos=VirtualNicInfosIPaddr($ligne["nics_virtuals_id"]);
			$nicvirtual=$array_virtual_infos["IPADDR"];
			if($nicvirtual==null){continue;}
			
		}
		
		if($ligne["nic_inbound"]<>null){
			$nicvirtual=$ligne["nic_inbound"];
			$nicvirtual=$NetBuilder->NicToOther($nicvirtual);
		}
		
		$nic_linked=trim($ligne["nic_linked"]);
		if(trim($nic_linked)==null){continue;}
		if(trim($nicvirtual)==null){continue;}
		
		if(preg_match("#(.+?):([0-9]+)#",$nic_linked,$re)){
			$array_virtual_infos=VirtualNicInfosIPaddr($re[2]);
			$nic_linked=$array_virtual_infos["IPADDR"];
		}
		
		$id=$ligne["ID"];
		$nic_linked=$NetBuilder->NicToOther($nic_linked);
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."]: [$id] Virtuals bridge $nicvirtual to $nic_linked";
		$GLOBALS["SCRIPTS"][]="$iptables -A FORWARD -i $nicvirtual -o $nic_linked -m state --state ESTABLISHED,RELATED -j ACCEPT -m comment --comment \"ArticaBridgesVirtual:$id\" 2>&1";
		$GLOBALS["SCRIPTS"][]="$iptables -A FORWARD -i $nicvirtual -o $nic_linked -j ACCEPT -m comment --comment \"ArticaBridgesVirtual:$id\" 2>&1";
		$GLOBALS["SCRIPTS"][]="$iptables -t nat -A POSTROUTING -o $nic_linked -j MASQUERADE	-m comment --comment \"ArticaBridgesVirtual:$id\" 2>&1";	
		
	}

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

function routes_main_build(){
	routes();
	routes_main();
	$unix=new unix();
	$route=$unix->find_program("route");
	$ip=$unix->find_program("ip");

	
	if(count($GLOBALS["SCRIPTS"])==0){echo "No route to build\n";return;}
	
	
	
		
	while (list ($index, $line) = each ($GLOBALS["SCRIPTS"]) ){
		$line=trim($line);
		if($line==null){continue;}
		$md=md5($line);
		if(isset($AL[$md])){continue;}
		$AL[$md]=true;
		echo "Starting......: `$line`\n";
		system($line);
	}
	
	
	
		
}


function routes_main(){
	
	$unix=new unix();
	$GLOBALS["ifconfig"]=$unix->find_program("ifconfig");
	$GLOBALS["routebin"]=$unix->find_program("route");
	$GLOBALS["ipbin"]=$unix->find_program("ip");
	$GLOBALS["vconfigbin"]=$unix->find_program("vconfig");
	$GLOBALS["moprobebin"]=$unix->find_program("modprobe");	
	
	
	
	$sock=new sockets();
	$OVHNetConfig=$sock->GET_INFO("OVHNetConfig");
	if(!is_numeric($OVHNetConfig)){$OVHNetConfig=0;}
	$NetWorkBroadCastAsIpAddr=$sock->GET_INFO("NetWorkBroadCastAsIpAddr");
	$EnableChilli=$sock->GET_INFO("EnableChilli");
	if(!is_numeric($EnableChilli)){$EnableChilli=0;}
	if($EnableChilli==1){
		$ChilliConf=unserialize(base64_decode($sock->GET_INFO("ChilliConf")));
		echo "Starting......: Will skip {$ChilliConf["HS_LANIF"]} for HotSpot config\n";
		$eth_SKIP[$ChilliConf["HS_LANIF"]]=true;
	}	
	
	
	
	$route=$unix->find_program("route");
	$ip=$unix->find_program("ip");
	$types[1]="{network_nic}";
	$types[2]="{host}";
	
	$endcmdsline=array();
	$q=new mysql();
	$NetBuilder=new system_nic();
	$NetBuilder->LoadTools();
	
	$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."]";
	$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."] *******************************";
	$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."] ****     MAIN ROUTES       ****";
	$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."] *******************************";
	$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."]";
	
	$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["ipbin"]} route add 127.0.0.1 dev lo";
	//$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["routebin"]} add -net 127.0.0.0 netmask 255.0.0.0 lo";
	
	
	
	$sql="SELECT * FROM  `nics` WHERE defaultroute=1 ORDER BY Interface";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$eth=trim($ligne["Interface"]);
	$eth=str_replace("\r\n", "", $eth);
	$eth=str_replace("\r", "", $eth);
	$eth=str_replace("\n", "", $eth);
	
	if(isset($eth_SKIP[$eth])){$eth=null;}
	if($ligne["GATEWAY"]==null){$eth=null;}
	if($ligne["GATEWAY"]=="0.0.0.0"){$eth=null;}
	if($ligne["NETMASK"]=="0.0.0.0"){$eth=null;}
	$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["echobin"]} \"\" > /etc/iproute2/rt_tables";
	$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["echobin"]} \"255	local\" >> /etc/iproute2/rt_tables";
	$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["echobin"]} \"254	main\" >> /etc/iproute2/rt_tables";
	$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["echobin"]} \"253	default\" >> /etc/iproute2/rt_tables";
	$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["echobin"]} \"0	unspec\" >> /etc/iproute2/rt_tables";
	
	
	if($eth<>null){
		if(!isset($GLOBALS["DEFAULT_ROUTE_SET"])){
			$GLOBALS["SCRIPTS_ROUTES"][]="# [$eth/".__LINE__."] is set as default route.";
			$GLOBALS["DEFAULT_ROUTE_SET"]=$eth;
			$NETMASK=$ligne["NETMASK"];
			$CDIR=$NetBuilder->GetCDIRNetwork($ligne["IPADDR"],$ligne["NETMASK"]);
			$md5net=md5($CDIR);
			$GLOBALS["MD5NET"][$md5net]=true;
			
			$endcmdsline[]="{$GLOBALS["ifconfig"]} ".$NetBuilder->NicToOther($eth)." down";
			$endcmdsline[]="{$GLOBALS["ifconfig"]} ".$NetBuilder->NicToOther($eth)." up";
			$endcmdsline[]="{$GLOBALS["ipbin"]} route add default via {$ligne["GATEWAY"]} dev ".$NetBuilder->NicToOther($eth);
		}
	}
	
	
	if(!isset($GLOBALS["DEFAULT_ROUTE_SET"])){
		$GLOBALS["SCRIPTS_ROUTES"][]="# [eth0] is set as default route.";
		$nic=new system_nic("eth0");
		if($nic->GATEWAY<>null){
			$GLOBALS["DEFAULT_ROUTE_SET"]="eth0";
			$CDIR=$NetBuilder->GetCDIRNetwork($nic->IPADDR,$nic->NETMASK);
			$md5net=md5($CDIR);
			$GLOBALS["MD5NET"][$md5net]=true;
			$endcmdsline[]="force:{$GLOBALS["ifconfig"]} ".$NetBuilder->NicToOther("eth0")." down";
			$endcmdsline[]="force:{$GLOBALS["ifconfig"]} ".$NetBuilder->NicToOther("eth0")." up";
			$endcmdsline[]="{$GLOBALS["ipbin"]} route add default via $nic->GATEWAY dev ".$NetBuilder->NicToOther("eth0");
		}
	}

	$GLOBALS["rt_tables_number"]=0;
	$sql="SELECT * FROM `nics` WHERE enabled=1 ORDER BY Interface";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "Starting......: Mysql error : $q->mysql_error\n";return;}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$eth=trim($ligne["Interface"]);
		$eth=str_replace("\r\n", "", $eth);
		$eth=str_replace("\r", "", $eth);
		$eth=str_replace("\n", "", $eth);
		$ROUTES_ARRAY=unserialize($ligne["routes"]);
		
		if(isset($GLOBALS["DEFAULT_ROUTE_SET"])){if($GLOBALS["DEFAULT_ROUTE_SET"]==$eth){continue;}}
		
		$GLOBALS["SCRIPTS_ROUTES"][]="# [$eth/".__LINE__."] Main route $eth gateway {$ligne["GATEWAY"]} netmask {$ligne["NETMASK"]} ipaddr: {$ligne["IPADDR"]}";
		
		if(isset($eth_SKIP[$eth])){echo "Starting......: $eth skipping\n";$GLOBALS["SCRIPTS_ROUTES"][]="# [$eth] skipped";continue;}
		
		
		
				
		if($ligne["GATEWAY"]==null){$GLOBALS["SCRIPTS_ROUTES"][]="# [$eth/".__LINE__."] GATEWAY = null skipped";continue;}
		if($ligne["GATEWAY"]=="0.0.0.0"){$GLOBALS["SCRIPTS_ROUTES"][]="# [$eth/".__LINE__."] GATEWAY = 0.0.0.0 skipped";continue;}
		if($ligne["NETMASK"]=="0.0.0.0"){$GLOBALS["SCRIPTS_ROUTES"][]="# [$eth/".__LINE__."] NETMASK = 0.0.0.0 skipped";continue;}	
		if(trim($ligne["NETMASK"])==null){$GLOBALS["SCRIPTS_ROUTES"][]="# [$eth/".__LINE__."] NETMASK = null skipped";continue;}
		
		$GLOBALS["SCRIPTS_ROUTES"][]="# [$eth/".__LINE__."] GATEWAY = {$ligne["GATEWAY"]} add in table (default route {$ligne["defaultroute"]})";
		$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["ifconfig"]} ".$NetBuilder->NicToOther($eth)." down";
		$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["ifconfig"]} ".$NetBuilder->NicToOther($eth)." up";
		
		if($ligne["defaultroute"]==0){
			
			$GLOBALS["rt_tables_number"]++;
			$GLOBALS["SCRIPTS_ROUTES"][]="# [$eth/".__LINE__."] Table {$GLOBALS["RT_TABLES"][$eth]} named $eth";
			$GLOBALS["RT_TABLES"][$eth]=$GLOBALS["rt_tables_number"];
			$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["echobin"]} \"{$GLOBALS["rt_tables_number"]}\t$eth\" >> /etc/iproute2/rt_tables";
			$GLOBALS["SCRIPTS_ROUTES"][]="# [$eth/".__LINE__."] Table {$GLOBALS["RT_TABLES"][$eth]} named $eth";
			
			
			if(!isset($GLOBALS["GATEWAYADDED"][$ligne["GATEWAY"]])){
				$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["ipbin"]} route add {$ligne["GATEWAY"]} dev ".$NetBuilder->NicToOther($eth) ." table $eth";
				$GLOBALS["GATEWAYADDED"][$ligne["GATEWAY"]]=true;
			}
		}	
		
		$CDIR=trim($NetBuilder->GetCDIRNetwork($ligne["IPADDR"],$ligne["NETMASK"]));
		if($CDIR==null){$GLOBALS["SCRIPTS_ROUTES"][]="# GetCDIRNetwork ({$ligne["IPADDR"]},{$ligne["NETMASK"]} ) return null";}
		if(isset($ALREADYNETS[$CDIR])){$GLOBALS["SCRIPTS_ROUTES"][]="# [$eth] $CDIR already added skip it";continue;}
		
		$ALREADYNETS[$CDIR]=true;
		$eth=$NetBuilder->NicToOther($eth);
		$md5net=md5($CDIR);
		if(isset($GLOBALS["MD5NET"][$md5net])){
			$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."] [$eth] MD5NET already added skip it";
			continue;
		}
		
		
		if($ligne["defaultroute"]==0){
			if(!isset($GLOBALS["RT_TABLES"][$eth])){
				$GLOBALS["rt_tables_number"]++;
				$GLOBALS["RT_TABLES"][$eth]=$GLOBALS["rt_tables_number"];
				$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["echobin"]} \"{$GLOBALS["rt_tables_number"]}\t$eth\" >> /etc/iproute2/rt_tables";
				$GLOBALS["SCRIPTS_ROUTES"][]="# [$eth/".__LINE__."] Table {$GLOBALS["RT_TABLES"][$eth]} named $eth";
			}
			
			if(is_array($ROUTES_ARRAY)){
				$GLOBALS["SCRIPTS_ROUTES"][]="# [$eth/".__LINE__."] ".count($ROUTES_ARRAY)." Additionnal route(s)";
				if(count($ROUTES_ARRAY)>0){
					while (list ($ip, $ip_array) = each ($ROUTES_ARRAY) ){
						$NETMASK=$ip_array["NETMASK"];
						$GATEWAY=$ip_array["GATEWAY"];
						$GLOBALS["SCRIPTS_ROUTES"][]="# [$eth/".__LINE__."] $ip/$NETMASK -> $GATEWAY Table {$GLOBALS["RT_TABLES"][$eth]}/$eth";
						$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["ipbin"]} route add $GATEWAY dev $eth table $eth";
						$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["ipbin"]} route add $ip/$NETMASK via $GATEWAY src {$ligne["IPADDR"]} dev $eth table $eth";
					}
				}
			}
			
			
			$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["ipbin"]} route add $CDIR dev $eth src {$ligne["IPADDR"]} table $eth";
			$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["ipbin"]} route add default via {$ligne["GATEWAY"]} src {$ligne["IPADDR"]} dev $eth table $eth";
		
		
		}else{
			
			
			if(is_array($ROUTES_ARRAY)){
				if(count($ROUTES_ARRAY)>0){
					while (list ($ip, $ip_array) = each ($ROUTES_ARRAY) ){
						$NETMASK=$ip_array["NETMASK"];
						$GATEWAY=$ip_array["GATEWAY"];
						$CDIR=$NetBuilder->GetCDIRNetwork($ip,$NETMASK);
						if(isset($ALREADYNETS[$CDIR])){$GLOBALS["SCRIPTS_ROUTES"][]="# [$eth] $CDIR already added skip it";continue;}
						$GLOBALS["SCRIPTS_ROUTES"][]="# [$eth/".__LINE__."] $ip/$NETMASK -> $GATEWAY main route";
						$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["ipbin"]} route $GATEWAY dev $eth";
						$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["ipbin"]} route add $ip/$NETMASK via $GATEWAY src {$ligne["IPADDR"]} dev $eth ";
						$ALREADYNETS[$CDIR]=true;
					}
				}
			}
			
			
		}
		
		$GLOBALS["MD5NET"][$md5net]=true;
		$GLOBALS["SCRIPTS_ROUTES"][]="# [$eth/".__LINE__."]: Added by nic table for {$ligne["Interface"]} {$ligne["IPADDR"]}/{$ligne["NETMASK"]} -> $CDIR";
	}
	
	
	
	
	$sql="SELECT * FROM nic_routes ORDER BY `nic`";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$type=$ligne["type"];
		$ttype="-net";
		$dev=null;
		
		$GLOBALS["SCRIPTS_ROUTES"][]="# [$eth/".__LINE__."] [{$ligne["nic"]}] nic_routes table type:{$ligne["type"]},gw:{$ligne["gateway"]}"; 
		
		if($ligne["nic"]<>null){$dev=" dev ".$NetBuilder->NicToOther($ligne["nic"]);}
		
		if($GLOBALS["DEFAULT_ROUTE_SET"]<>$ligne["nic"]){
			if(!isset($GLOBALS["RT_TABLES"][$eth])){
				$GLOBALS["rt_tables_number"]++;
				$GLOBALS["RT_TABLES"][$eth]=$GLOBALS["rt_tables_number"];
				$GLOBALS["SCRIPTS_ROUTES"][]="# [$eth/".__LINE__."] ({$ligne["nic"]}) Table {$GLOBALS["RT_TABLES"][$eth]} named $eth";
				$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["echobin"]} \"{$GLOBALS["rt_tables_number"]}\t$eth\" >> /etc/iproute2/rt_tables";
			}
		}
		
		if($type==1){
			$ttype="-net";
			$ROUTE_TABLE=$GLOBALS["RT_TABLES"][$eth];
			$CMDS="$ip route add {$ligne["gateway"]} $dev table $ROUTE_TABLE";
			$GLOBALS["SCRIPTS"][]=$CMDS;
			continue;
		}
	
	
		if($type==2){
			
			$ttype="-host";
		
			$ROUTE_TABLE=$GLOBALS["RT_TABLES"][$eth];
			$md5net=md5("{$ligne["pattern"]}{$ligne["gateway"]}");
			if(isset($GLOBALS["MD5NET"][$md5net])){continue;}
			$GLOBALS["MD5NET"][$md5net]=true;
			$GLOBALS["SCRIPTS_ROUTES"][]="$ip route add {$ligne["gateway"]} dev $dev table $ROUTE_TABLE";
			$cmd="$ip route add {$ligne["pattern"]} via {$ligne["gateway"]} dev $dev table $ROUTE_TABLE";
			if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
			$GLOBALS["SCRIPTS_ROUTES"][]="#[$eth/".__LINE__."] Added by nic_routes table";
			$GLOBALS["SCRIPTS_ROUTES"][]=$CMDS;
		}
	
	}
	
	if(count($endcmdsline)>0){
		while (list ($index, $line) = each ($endcmdsline) ){
			$GLOBALS["SCRIPTS_ROUTES"][]=$line;
		}
	}
	
}


function routes(){
	$unix=new unix();
	$NetBuilder=new system_nic();
	$route=$unix->find_program("route");
	$ip=$unix->find_program("ip");
	$types[1]="{network_nic}";
	$types[2]="{host}";	

	
	
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
		$eth=$NetBuilder->NicToOther($eth);
		
		
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
	
	$NetBuilder=new system_nic();
	if($NetBuilder->IsBridged($ligne["nic"])){
		$ligne["nic"]=$ligne["BridgedTo"];
	}
	
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
	$NetBuilder=new system_nic();
	
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
	dev_shm();
	build();
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

function bridge_delete($ID){
	
	$q=new mysql();
	$nicbr="br{$ID}";
	$NetBuilder=new system_nic();
	$NetBuilder->LoadTools();
	$NICS=$NetBuilder->BuildBridges_getlinked();
	while (list ($a, $b) = each ($NICS) ){
		$q->QUERY_SQL("UPDATE `nics` SET Bridged=0, BridgedTo='' WHERE Interface='$b'","artica_backup");
		$GLOBALS["SCRIPTS_DEL"][]="{$GLOBALS["brctlbin"]} delif $nicbr $b";
		$GLOBALS["SCRIPTS_DEL"][]="{$GLOBALS["ifconfig"]} $b down";
		
	}
	
	$GLOBALS["SCRIPTS_DEL"][]="{$GLOBALS["ifconfig"]} $nicbr down";
	$GLOBALS["SCRIPTS_DEL"][]="{$GLOBALS["brctlbin"]} delbr $nicbr";
	$q->QUERY_SQL("DELETE FROM `nics_bridge` WHERE ID='$ID'","artica_backup");
	
	
	
	while (list ($id, $ligne) = each ($GLOBALS["SCRIPTS_DEL"]) ){
		echo "Starting......: `$ligne`\n";
		shell_exec("$ligne");
	
	}
	
	bridge_deletemanu($nicbr);
	BuildNetWorksDebian();
	shell_exec("/etc/init.d/artica-ifup start");
	
}

function bridge_deletemanu($eth){
	$NetBuilder=new system_nic();
	$NetBuilder->LoadTools();	
	if(!$NetBuilder->IfBridgeExists($eth)){return;}
	
	exec("{$GLOBALS["brctlbin"]} show $eth 2>&1",$result);
	while (list ($id, $ligne) = each ($result) ){
		if(preg_match("#.*\s+.*?\s+.*?\s+([a-z\.0-9]+)$#", $ligne,$re)){
			if(strtolower(trim($re[1])=="interfaces")){continue;}
			echo "Removing {$re[1]}\n";
			$GLOBALS["SCRIPTS_DEL"][]="{$GLOBALS["brctlbin"]} delif $eth {$re[1]}";
			$GLOBALS["SCRIPTS_DEL"][]="{$GLOBALS["ifconfig"]} {$re[1]} down";
			continue;
		}
		
		if(preg_match("#\s+\s+([a-z\.0-9]+)$#", $ligne,$re)){
			if(strtolower(trim($re[1])=="interfaces")){continue;}
			echo "Removing {$re[1]}\n";
			$GLOBALS["SCRIPTS_DEL"][]="{$GLOBALS["brctlbin"]} delif $eth {$re[1]}";
			$GLOBALS["SCRIPTS_DEL"][]="{$GLOBALS["ifconfig"]} {$re[1]} down";
			continue;			
		}
		
		
	}
	
	$GLOBALS["SCRIPTS_DEL"][]="{$GLOBALS["ifconfig"]} $eth down";
	$GLOBALS["SCRIPTS_DEL"][]="{$GLOBALS["brctlbin"]} delbr $eth";	
	

	while (list ($id, $ligne) = each ($GLOBALS["SCRIPTS_DEL"]) ){
		echo "Starting......: `$ligne`\n";
		shell_exec("$ligne");
	
	}	
	
}
function etc_hosts_defaults(){

	$ip=new IP();
	$datas=explode("\n",@file_get_contents("/etc/hosts"));
	while (list ($num, $ligne) = each ($datas) ){
		if(substr($ligne, 0,1)=="#"){continue;}
		if(preg_match("#^([0-9\.\:]+)\s+(.+?)\s+(.+?)$#",$ligne,$re)){
			$array[]=array("name"=>$re[2],"alias"=>$re[3],"ip"=>$re[1],"md"=>md5($ligne));
			continue;
		}
	
		if(preg_match("#^([0-9\.\:]+)\s+(.+?)$#",$ligne,$re)){
			$array[]=array("name"=>$re[2],"ip"=>$re[1],"md"=>md5($ligne));
			continue;
		}
	
	}
	
	while (list ($num, $ligne) = each ($array) ){
		if($ligne["name"]==null){if($ligne["alias"]<>null){$ligne["name"]=$ligne["alias"];$ligne["alias"]=null;}}
		$md5=md5("{$ligne["ip"]}{$ligne["name"]}");
		if(!$ip->isValid($ligne["ip"])){continue;}
		$f[]="('$md5','{$ligne["ip"]}','{$ligne["name"]}','{$ligne["alias"]}')";
	}  
	
	$q=new mysql();
	$q->BuildTables();
	$q->QUERY_SQL("INSERT IGNORE INTO net_hosts (`zmd5`,`ipaddr`,`hostname`,`alias`) VALUES ".@implode(",", $f),"artica_backup");
	
	
}

//

?>
