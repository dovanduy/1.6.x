<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="Virtual Switch";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}

$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');



$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop_all();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start_all();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--reconfigure"){$GLOBALS["OUTPUT"]=true;reconfigure();die();}
if($argv[1]=="--status"){$GLOBALS["OUTPUT"]=true;vde_status();die();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;reconfigure();die();}
if($argv[1]=="--vlan"){$GLOBALS["OUTPUT"]=true;vde_plug2tap_vlan($argv[2]);die();}




function restart() {
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $oldpid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
		
	stop_all(true);
	reconfigure(true);
	sleep(1);
	start_all(true);
	
	
	
}

function reconfigure($aspid=false){
	$unix=new unix();
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $oldpid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	
	
	$sql="SELECT nic FROM nics_vde GROUP BY nic";
	
	
	
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} ".mysql_num_rows($results)." switche(s)\n";}
	
	
	$GLOBALS["SCRIPTS"][]="# [".__LINE__."]:". mysql_num_rows($results). " switche(s)";
	if(!$q->ok){return null;}
	
	@mkdir("/etc/vde_switch_config",0755,true);
	$rm=$unix->find_program("rm");
	shell_exec("$rm -rf /etc/vde_switch_config/*");
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		@mkdir("/etc/vde_switch_config/{$ligne["nic"]}");
		
	}
	
	$sql="SELECT * FROM nics_vde";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} real interface {$ligne["nic"]} for virt{$ligne["ID"]}\n";}
		@file_put_contents("/etc/vde_switch_config/{$ligne["nic"]}/{$ligne["ID"]}.conf",serialize($ligne));
		@file_put_contents("/etc/vde_switch_config/{$ligne["ID"]}.conf",serialize($ligne));
	}
	
}

function start_all($aspid=false){
	
	$unix=new unix();
	$sock=new sockets();
	$GLOBALS["TITLENAME"]=$GLOBALS["TITLENAME"]." ($ID)";
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $oldpid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$unix=new unix();
	$sysctl=$unix->find_program("sysctl");
	$dirs=$unix->dirdir("/etc/vde_switch_config");
	shell_exec("$sysctl -w net.ipv4.ip_forward=1 >/dev/null 2>&1");
	
	while (list ($num, $ligne) = each ($dirs) ){
		$eth=basename($num);
		$GLOBALS["TITLENAME"]="Virtual Switch for $eth";
		vde_switch($eth);
		
	}
	
	
	
	
	
}


function stop_all($aspid=false){
	
	$unix=new unix();
	
	
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $oldpid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}	
	
	$dirs=$unix->dirdir("/etc/vde_switch_config");
	while (list ($num, $ligne) = each ($dirs) ){
		$eth=basename($num);
		$GLOBALS["TITLENAME"]="Virtual Switch for $eth";
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]}\n";}
		vde_switch_down($eth);
	
	}
		
		
	
	
	
	
}

function vde_status($aspid=false){
	$unix=new unix();
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $oldpid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	
	$ips=$unix->NETWORK_ALL_INTERFACES();
	$ifconfig=$unix->find_program("ifconfig");
	$ip=$unix->find_program("ip");
	while (list ($eth, $ligne) = each ($ips) ){
		if(!preg_match("#^virt([0-9]+)#", $eth,$re)){
			if($GLOBALS["VERBOSE"]){echo "$eth SKIP...\n";}
			continue;}
		$ID=$re[1];
		$ligne=unserialize(@file_get_contents("/etc/vde_switch_config/{$ID}.conf"));
		$eth=$ligne["nic"];
		$virtname="virt$ID";
		
		$pid=vde_switch_pid($eth);
		if($unix->process_exists($pid)){
			$ARRAY[$virtname]["VDE"]=$pid;
			$ARRAY[$virtname]["VDE_RUN"]=$unix->PROCCESS_TIME_MIN($pid);
		}
		

		$pid=vde_plug2tap_pid($virtname);
		if($unix->process_exists($pid)){
			$ARRAY[$virtname]["PCAP"]=$pid;
			$ARRAY[$virtname]["PCAP_RUN"]=$unix->PROCCESS_TIME_MIN($pid);
		}
		
	}
	
	
	if($GLOBALS["VERBOSE"]){print_r($ARRAY);}
	@mkdir("/usr/share/artica-postfix/ressources/logs/web",0777,true);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/vde_status", serialize($ARRAY));
	@chmod(0755,"/usr/share/artica-postfix/ressources/logs/web/vde_status");

	
	
}

function vde_config_shutdown($ID){
	$unix=new unix();
	$virtname="virt{$ID}";
	$ligne=unserialize(@file_get_contents("/etc/vde_switch_config/{$ID}.conf"));
	$echo =$unix->find_program("echo");
	$eth=$ligne["nic"];
	$ipaddr=$ligne["ipaddr"];
	$gateway=$ligne["gateway"];
	$cdir=$ligne["cdir"];
	$netmask=$ligne["netmask"];
	$ips=$unix->NETWORK_ALL_INTERFACES();
	
	vde_plug2tap_down($ID);
	
	if(!isset($ips[$virtname])){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: $virtname already unplugged\n";}
		return;
	}
	$ifconfig=$unix->find_program("ifconfig");
	$ip=$unix->find_program("ip");
	shell_exec("$ifconfig $virtname down >/dev/null 2>&1");
	shell_exec("$ips link delete $virtname >/dev/null 2>&1");
	if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: $virtname unplugged\n";}
	
	
}

function vde_check_routes($ID){
	$virtname="virt{$ID}";
	$unix=new unix();
	$ligne=unserialize(@file_get_contents("/etc/vde_switch_config/{$ID}.conf"));
	$ip=$unix->find_program("ip");
	$ifconfig=$unix->find_program("ifconfig");
	exec("$ip route 2>&1",$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#(.+?)\s+dev#",$line,$re)){
			$ROUTES[$re[1]]=true;
		}
		
	}
	
	$ipaddr=$ligne["ipaddr"];
	$gateway=$ligne["gateway"];
	$cdir=$ligne["cdir"];	
	$metric=$ligne["metric"];
	$netmask=$ligne["netmask"];
	if(!is_numeric($metric)){$metric="10{$ID}";}
	if($gateway==null){return;}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Adding $gateway to $virtname\n";}
	
	
	if($cdir<>null){
		if(isset($ROUTES[$cdir])){if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} route $cdir already set\n";}return;}
		shell_exec("$ip route add $gateway dev $virtname >/dev/null 2>&1");
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Adding $cdir to $virtname trough $gateway\n";}
		shell_exec("$ifconfig $virtname up >/dev/null 2>&1");
		$cmd="$ip route add $cdir via $gateway dev $virtname metric $metric";
		if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
		shell_exec($cmd);
		
		return;
	}
	
	$t=explode(".",$ipaddr);
	$t[3]=0;
	$net=@implode(".", $t);
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Adding $cdir to $virtname trough $gateway\n";}
	shell_exec("$ifconfig $virtname up >/dev/null 2>&1");
	shell_exec("$ip route add $gateway dev $virtname >/dev/null 2>&1");
	shell_exec("$ip route add $net/$netmask via $gateway dev $virtname metric $metric");
	
	
	
}


function vde_config($ID){
	
	$unix=new unix();
	
	
	$vde_tunctl=$unix->find_program("vde_tunctl");
	$echo=$unix->find_program("echo");
	$virtname="virt{$ID}";
	
	$GLOBALS["TITLENAME"]="Network Interface $virtname";
	if(!is_file("/etc/vde_switch_config/{$ID}.conf")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} /etc/vde_switch_config/{$ID}.conf no such file\n";}
		return;
	}
	
	$ligne=unserialize(@file_get_contents("/etc/vde_switch_config/{$ID}.conf"));
	
	$echo =$unix->find_program("echo");
	$eth=$ligne["nic"];	
	$ipaddr=$ligne["ipaddr"];
	$gateway=$ligne["gateway"];
	$cdir=$ligne["cdir"];
	$netmask=$ligne["netmask"];
	$ips=$unix->ifconfig_interfaces_list();
	
	
	
	if($ips[$virtname]==$ipaddr){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]}, $ipaddr/$netmask already defined\n";}
		vde_check_routes($ID);
		vde_plug2tap($ID);
		vde_check_routes($ID);
		return;
	}

	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]}, $ipaddr/$netmask\n";}
	$ifconfig=$unix->find_program("ifconfig");
	$ip=$unix->find_program("ip");
	$metric=$ligne["metric"];
	$arp=$unix->find_program("arp");
	shell_exec("$vde_tunctl -t $virtname >/dev/null");
	$ligne=unserialize(@file_get_contents("/etc/vde_switch_config/{$ID}.conf"));
	
	if(!is_numeric($metric)){$metric="10{$ID}";}
	shell_exec("$ifconfig $virtname up >/dev/null 2>&1");
	shell_exec("$ifconfig $virtname $ipaddr netmask $netmask up >/dev/null 2>&1");
	shell_exec("$echo \"1\" > /proc/sys/net/ipv4/conf/$virtname/proxy_arp");
	
	
	if($gateway==null){
		vde_plug2tap($ID);
		return;
	}
	
	$rt_tables=$unix->load_rt_table();
	$last_rt_table_number=$unix->last_rt_table_number();
	if(!isset($rt_tables[$virtname])){
		$last_rt_table_number=$last_rt_table_number+1;
		$lastnumber=$rt_tables;
		shell_exec("$echo \"$last_rt_table_number\t$virtname\" >> /etc/iproute2/rt_tables");
	}
	
	shell_exec("$ip route add $gateway dev $virtname table $eth");
	if(is_file($arp)){shell_exec("$arp -Ds $gateway $eth pub");}
	
	if($cdir<>null){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]}, $cdir -> $gateway\n";}
		shell_exec("$ip route add $cdir dev $virtname src $ipaddr table $virtname");
		shell_exec("$ip route add default via $gateway dev $eth table $virtname");
		vde_plug2tap($ID);
		return;
	}
	
	$t=explode(".",$ipaddr);
	$t[3]=0;
	$net=@implode(".", $t);
	shell_exec("$ip route add $net/$netmask dev $virtname src $ipaddr table $virtname");
	shell_exec("$ip route add default via $gateway dev $eth table $virtname");
	vde_plug2tap($ID);
	
	
}

function vde_plug2tap_down($ID){
	$unix=new unix();
	$sock=new sockets();
	
	$ligne=unserialize(@file_get_contents("/etc/vde_switch_config/{$ID}.conf"));
	$eth=$ligne["nic"];
	$virtname="virt$ID";
	$GLOBALS["TITLENAME"]="Interface Plug $virtname";
	
	
	$pid=vde_plug2tap_pid($virtname);
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service already stopped\n";}
		@unlink("/var/run/$virtname.pid");
		return;
	}
	
	
	
	$ifconfig=$unix->find_program("ifconfig");
	$ip=$unix->find_program("ip");
	$ligne=unserialize(@file_get_contents("/etc/vde_switch_config/{$ID}.conf"));
	shell_exec("$ifconfig $virtname down >/dev/null 2>&1");	
	
	$kill=$unix->find_program("kill");
	$pid=vde_plug2tap_pid($virtname);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	shell_exec("$kill $pid >/dev/null 2>&1");
	for($i=0;$i<5;$i++){
		$pid=vde_plug2tap_pid($virtname);
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}
	
	$pid=vde_plug2tap_pid($virtname);
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		@unlink("/var/run/$virtname.pid");
		return;
	}
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	shell_exec("$kill -9 $pid >/dev/null 2>&1");
	for($i=0;$i<5;$i++){
		$pid=vde_plug2tap_pid($virtname);
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}
	
	$pid=vde_plug2tap_pid($virtname);
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}	
	@unlink("/var/run/$virtname.pid");
	
}


function vde_plug2tap($ID){
	$unix=new unix();
	$sock=new sockets();
	
	
	
	$Masterbin=$unix->find_program("vde_plug2tap");
	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]}, vde_plug2tap not installed\n";}
		return;
	}
	
	
	
	$ligne=unserialize(@file_get_contents("/etc/vde_switch_config/{$ID}.conf"));
	$eth=$ligne["nic"];
	$virtname="virt$ID";
	$vlan=$ligne["vlan"];
	$GLOBALS["TITLENAME"]="Interface Plug $virtname";
	$nohup=$unix->find_program("nohup");
	
	$pid=vde_plug2tap_pid($virtname);
	
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} service already started $pid since {$timepid}Mn...\n";}
		vde_plug2tap_vlan($ID);
		return;
	}
	@unlink("/var/run/$virtname.pid");
	$port=$ligne["port"];
	if(!is_numeric($port)){$port=1;}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]}\n";}
	$cmd="$Masterbin -s /var/run/switch$eth --port=$port --daemon -P /var/run/$virtname.pid $virtname >/dev/null 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} Switch $eth Interface $virtname port $port\n";}
	shell_exec($cmd);
	
	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=vde_plug2tap_pid($virtname);
		if($unix->process_exists($pid)){break;}
	}
	
	$pid=vde_plug2tap_pid($virtname);
	
	
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		vde_plug2tap_vlan($ID);
		
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	}	
	
}

function vde_plug2tap_vlan($ID){
	
	$unix=new unix();
	$vdecmd=$unix->find_program("vdecmd");
	if(!is_file($vdecmd)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: vdecmd no such binary\n";}
		return;
	}
	$ligne=unserialize(@file_get_contents("/etc/vde_switch_config/{$ID}.conf"));
	$vlan=$ligne["vlan"];
	if(!is_numeric($vlan)){$vlan=0;}
	if($vlan==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} No VLAN\n";}
		return;
	}
	$eth=$ligne["nic"];
	$port=$ligne["port"];
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} Create VLAN $vlan into Switch $eth\n";}
	$sock="/var/run/switchM$eth";
	if($GLOBALS["VERBOSE"]){echo "$vdecmd -s $sock vlan/create $vlan\n";}
	shell_exec("$vdecmd -s $sock vlan/create $vlan");
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} Adding VLAN $vlan to port $port Switch $eth\n";}
	
	//if($GLOBALS["VERBOSE"]){echo "$vdecmd -s $sock vlan/addport $vlan $port\n";}
	//shell_exec("$vdecmd -s $sock vlan/addport $vlan $port >/dev/null 2>&1");
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} Linking VLAN $vlan to port $port Switch $eth\n";}
	if($GLOBALS["VERBOSE"]){echo "$vdecmd -s $sock port/setvlan $port $vlan\n";}
	shell_exec("$vdecmd -s $sock port/setvlan $port $vlan >/dev/null 2>&1");
}


function vde_plug2tap_pid($virtname){
	if($GLOBALS["VERBOSE"]){echo "PID: /var/run/$virtname.pid\n";}
	$pid=trim(@file_get_contents("/var/run/$virtname.pid"));
	$unix=new unix();
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("vde_plug2tap");
	return $unix->PIDOF_PATTERN("$Masterbin.*?$virtname");
}






function vde_switch($eth){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("vde_switch");
	$vde_tunctl=$unix->find_program("vde_tunctl");
	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]}, vde switch not installed\n";}
		return;
	}

	$pid=vde_switch_pid($eth);

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} service already started $pid since {$timepid}Mn...\n";}
		vde_pcapplug($eth);
		return;
	}
	
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	
	$cmd="$nohup $Masterbin -s /var/run/switch$eth -M /var/run/switchM$eth -daemon -p /var/run/switch-$eth.pid >/dev/null 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	shell_exec($cmd);

	for($i=1;$i<4;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/3\n";}
		sleep(1);
		$pid=vde_switch_pid($eth);
		if($unix->process_exists($pid)){break;}
	}
	
	$pid=vde_switch_pid($eth);
	if(!$unix->process_exists($pid)){	
		shell_exec($cmd);
		sleep(1);
	}

	$pid=vde_switch_pid($eth);
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		vde_pcapplug($eth);
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	}
}

function vde_nics($eth){
	
	
	$c=0;
	foreach (glob("/etc/vde_switch_config/$eth/*") as $filename) {
		$c++;
		$file=basename($filename);
		if(preg_match("#([0-9]+)\.conf$#", $filename,$re)){
			vde_config($re[1],true);
		}
	}	
	
	$GLOBALS["TITLENAME"]="Virtual Interfaces ($eth)";
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} $c Interface(s)\n";}
	
	
}
function vde_nics_shutdown($eth){


	$c=0;
	foreach (glob("/etc/vde_switch_config/$eth/*") as $filename) {
		$c++;
		$file=basename($filename);
		if(preg_match("#([0-9]+)\.conf$#", $filename,$re)){
			vde_config_shutdown($re[1],true);
		}
	}

	$GLOBALS["TITLENAME"]="Virtual Interfaces ($eth)";
	if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} $c Interface(s)\n";}


}
function vde_pcapplug($eth){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("vde_pcapplug");
	
	
	$GLOBALS["TITLENAME"]="Capture Plug ($eth)";
	
	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]}, vde switch not installed\n";}
		return;
	}
	
	$pid=vde_pcapplug_pid($eth);
	
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} service already started $pid since {$timepid}Mn...\n";}
		vde_nics($eth);
		return;
	}
	
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$Masterbin -s /var/run/switch$eth -d -P /var/run/switch{$eth}p.pid $eth >/dev/null 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} service on real interface $eth\n";}
	shell_exec($cmd);
	
	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=vde_pcapplug_pid($eth);
		if($unix->process_exists($pid)){break;}
	}
	
	$pid=vde_pcapplug_pid($eth);
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		vde_nics($eth);
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	}	
}

function vde_switch_down($eth){

	$pid=vde_switch_pid($eth);
	$GLOBALS["TITLENAME"]="Virtual Switch ($eth)";
	$unix=new unix();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		vde_pcapplug_down($eth);
		vde_nics_shutdown($eth);
		return;
	}
	
	vde_pcapplug_down($eth);
	vde_nics_shutdown($eth);
	
	$pid=vde_switch_pid($eth);
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	$rm=$unix->find_program("rm");
	$GLOBALS["TITLENAME"]="Virtual Switch ($eth)";
	if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	shell_exec("$kill $pid >/dev/null 2>&1");
	for($i=0;$i<5;$i++){
		$pid=vde_switch_pid($eth);
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=vde_switch_pid($eth);
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} cleaning sockets\n";}
		shell_exec("rm -rf /var/run/switch$eth");
		@unlink("/var/run/switch{$eth}p.pid");
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	shell_exec("$kill -9 $pid >/dev/null 2>&1");
	for($i=0;$i<5;$i++){
		$pid=vde_switch_pid($eth);
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} cleaning sockets\n";}
	shell_exec("rm -rf /var/run/switch$eth");
	@unlink("/var/run/switch{$eth}p.pid");
	

}
function vde_pcapplug_down($eth){
	
	$pid=vde_pcapplug_pid($eth);
	$GLOBALS["TITLENAME"]="Capture Plug ($eth)";
	$unix=new unix();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		return;
	}
	$pid=vde_pcapplug_pid($eth);
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	



	if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	shell_exec("$kill $pid >/dev/null 2>&1");
	for($i=0;$i<5;$i++){
		$pid=vde_pcapplug_pid($eth);
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=vde_pcapplug_pid($eth);
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	shell_exec("$kill -9 $pid >/dev/null 2>&1");
	for($i=0;$i<5;$i++){
		$pid=vde_pcapplug_pid($eth);
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}

}

function vde_switch_pid($eth){
	$pid=trim(@file_get_contents("/var/run/switch-$eth.pid"));
	$unix=new unix();
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("vde_switch");
	return $unix->PIDOF_PATTERN("$Masterbin.*?$eth");
	
}
function vde_pcapplug_pid($eth){
	if($GLOBALS["VERBOSE"]){echo "PID: /var/run/switch{$eth}p.pid\n";}
	$pid=trim(@file_get_contents("/var/run/switch{$eth}p.pid"));
	$unix=new unix();
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("vde_pcapplug");
	return $unix->PIDOF_PATTERN("$Masterbin.*?switchp{$eth}.pid");	
}


?>