<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="Kernel Optimization";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--reboot#",implode(" ",$argv),$re)){$GLOBALS["REBOOT"]=true;}
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["REBOOT"]=false;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');



$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;build();die();}

function restart() {
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	stop(true);
	build();
	sleep(1);
	start(true);

}


function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("sysctl");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, sysctl not installed\n";}
		return;
	}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}..\n";}
	shell_exec("$Masterbin -p >/dev/null;");
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Done.\n";}
}


function build(){
	$unix=new unix();
	$sock=new sockets();
	$Isagetway=false;
	$INSTALL_SERVICE=false;
	$KernelSendRedirects=$sock->GET_INFO("KernelSendRedirects");
	if(!is_numeric($KernelSendRedirects)){$KernelSendRedirects=1;}
	$conntrack=$unix->find_program("conntrack");
	$EnableChilli=$sock->GET_INFO("EnableChilli");
	$EnableArticaAsGateway=$sock->GET_INFO("EnableArticaAsGateway");
	if(!is_numeric($EnableChilli)){$EnableChilli=0;}
	$hasProxyTransparent=$sock->GET_INFO("hasProxyTransparent");
	if(!is_numeric($hasProxyTransparent)){$hasProxyTransparent=0;}
	
	$hostname=trim(@file_get_contents("/etc/artica-postfix/FULL_HOSTNAME"));
	
	if($EnableChilli==1){$Isagetway=true;}
	if($EnableArticaAsGateway==1){$Isagetway=true;}
	if($hasProxyTransparent==1){$Isagetway=true;}
	if(is_file("/etc/artica-postfix/IPTABLES_BR_BRIDGE")){$Isagetway=true;}
	if(is_file("/etc/artica-postfix/IPTABLES_BRIDGE")){$Isagetway=true;}
	
	$EnableipV6=$sock->GET_INFO("EnableipV6");
	if(!is_numeric($EnableipV6)){$EnableipV6=0;}
	
	$ARRAY=unserialize(base64_decode($sock->GET_INFO("kernel_values")));
	
	$swappiness=intval($ARRAY["swappiness"]);
	if($swappiness==0){$swappiness=10;}
	$tcp_max_syn_backlog=$ARRAY["tcp_max_syn_backlog"];
	if(!is_numeric($tcp_max_syn_backlog)){$tcp_max_syn_backlog=1024;}
	$EnableTCPOptimize=$sock->GET_INFO("EnableTCPOptimize");
	$DisableConntrack=intval($sock->GET_INFO("DisableConntrack"));
	if(!is_numeric($EnableTCPOptimize)){$EnableTCPOptimize=1;}
	
	$DisableTCPOptimizations=$sock->GET_INFO("DisableTCPOptimizations");
	if($DisableTCPOptimizations==1){$EnableTCPOptimize=0;}
	
	
	$echo=$unix->find_program("echo");
	$modprobe=$unix->find_program("modprobe");
	
	$DisableTCPEn=intval(trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/DisableTCPEn")));
	$DisableTCPWindowScaling=intval(trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/DisableTCPWindowScaling")));
	$EnableSystemOptimize=intval(trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableSystemOptimize")));
	
	
	
	$tcp_ecn=2;
	$tcp_window_scaling=1;
	if($DisableTCPWindowScaling==1){$tcp_window_scaling=0;}
	if($DisableTCPEn==1){$tcp_ecn=0;}
	$sysctl=$unix->find_program("sysctl");
	
	
	$f[]="#";
	$f[]="# /etc/sysctl.conf - Configuration file for setting system variables";
	$f[]="# See /etc/sysctl.d/ for additonal system variables";
	$f[]="# See sysctl.conf (5) for information.";
	$f[]="#";
	$f[]="";
	$f[]="#kernel.domainname = example.com";
	$f[]="";
	$f[]="# Uncomment the following to stop low-level messages on console";
	$f[]="#kernel.printk = 3 4 1 3";
	$f[]="";
	$f[]="##############################################################";
	
	// /proc/sys/vm/dirty_ratio defaults to 20% of RAM
	// /proc/sys/vm/dirty_background_ratio defaults to 10%of RAM
	
	$memory=$unix->MEM_TOTAL_INSTALLEE()*1024;
	$dirty_ratio=round($memory*0.8);
	
	if($EnableSystemOptimize==0){
		shell_exec("$echo 33554432 >/proc/sys/vm/dirty_background_bytes");
		shell_exec("$echo $dirty_ratio >/proc/sys/vm/dirty_bytes");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Memory: $memory bytes\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} dirty_ratio = $dirty_ratio bytes \n";}
	}else{
		$swappiness=0;
		shell_exec("$echo 1024 > /sys/block/sda/queue/nr_requests");
		
	}
	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	

	
	$t=explode(".",$hostname);
	if(count($t)>0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} `hostname` = '$hostname'\n";}
		$f[]="kernel.hostname={$t[0]}";
		unset($t[0]);
		$f[]="kernel.domainname=".@implode(".", $t);
	}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} `swappiness` = '{$swappiness}%'\n";}
	$f[]="vm.swappiness = $swappiness";
	$f[]="net.ipv4.icmp_ignore_bogus_error_responses = 1";
	$f[]="net.ipv4.tcp_window_scaling = $tcp_window_scaling";
	$f[]="net.ipv4.tcp_ecn = $tcp_ecn";
	$f[]="net.ipv4.tcp_sack = 1";
	$f[]="net.ipv4.tcp_fack = 1";
	$f[]="net.ipv4.tcp_timestamps = 1";
	$f[]="net.ipv4.icmp_echo_ignore_broadcasts = 1";
	$f[]="";
	if($EnableSystemOptimize==1){	
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} set dirty pages\n";}		
		$f[]="vm.dirty_background_ratio = 4";
		$f[]="vm.dirty_background_bytes = 33554432";
		$f[]="vm.dirty_ratio = 64";

		shell_exec("$echo \"100663296\" > /proc/sys/vm/dirty_bytes");
		shell_exec("$echo \"33554432\" > /proc/sys/vm/dirty_background_bytes");
		
		if(is_file($squidbin)){
			if(is_file("/proc/sys/net/local/dgram/recvspace")){
				$f[]="net.local.dgram.recvspace=262144";
			}
			if(is_file("/proc/sys/net/local/dgram/maxdgram")){
				$f[]="net.local.dgram.maxdgram=16384";
			}
		}
		
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} revert dirty pages to default\n";}
		$f[]="vm.dirty_background_ratio = 10";
		$f[]="vm.dirty_ratio = 20";
		$f[]="vm.dirty_background_bytes = 0";
		$f[]="vm.dirty_bytes = 0";
	}
	
	if(is_file($conntrack)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} conntrack installed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} conntrack DisableConntrack = $DisableConntrack\n";}

		
		if($DisableConntrack==1){
			$f[]="net.ipv4.netfilter.ip_conntrack_generic_timeout = 600";
			$f[]="net.ipv4.netfilter.ip_conntrack_tcp_timeout_syn_sent = 120";
			$f[]="net.ipv4.netfilter.ip_conntrack_tcp_timeout_syn_sent2 = 120";
			$f[]="net.ipv4.netfilter.ip_conntrack_tcp_timeout_syn_recv = 60";
			$f[]="net.ipv4.netfilter.ip_conntrack_tcp_timeout_established = 432000";
			$f[]="net.ipv4.netfilter.ip_conntrack_tcp_timeout_fin_wait = 120";
			$f[]="net.ipv4.netfilter.ip_conntrack_tcp_timeout_close_wait = 60";
			$f[]="net.ipv4.netfilter.ip_conntrack_tcp_timeout_last_ack = 30";
			$f[]="net.ipv4.netfilter.ip_conntrack_tcp_timeout_time_wait = 120";
			$f[]="net.ipv4.netfilter.ip_conntrack_tcp_timeout_close = 10";
			$f[]="net.ipv4.netfilter.ip_conntrack_tcp_timeout_max_retrans = 300";
			$f[]="net.ipv4.netfilter.ip_conntrack_tcp_loose = 1";
			$f[]="net.ipv4.netfilter.ip_conntrack_tcp_be_liberal = 0";
			$f[]="net.ipv4.netfilter.ip_conntrack_tcp_max_retrans = 3";
			$f[]="net.ipv4.netfilter.ip_conntrack_udp_timeout = 30";
			$f[]="net.ipv4.netfilter.ip_conntrack_udp_timeout_stream = 180";
			$f[]="net.ipv4.netfilter.ip_conntrack_icmp_timeout = 30";
			$f[]="net.ipv4.netfilter.ip_conntrack_max = 32088";
			$f[]="net.ipv4.netfilter.ip_conntrack_log_invalid = 0";
			$f[]="net.netfilter.nf_conntrack_acct = 0";
			shell_exec("$echo 8022 >/sys/module/nf_conntrack/parameters/hashsize >/dev/null 2>&1");
		}else{
		
			shell_exec("$modprobe nf_conntrack >/dev/null 2>&1");
			shell_exec("$modprobe nf_conntrack_ipv4 >/dev/null 2>&1");
			$nf_conntrack_max=196608;
			$ip_conntrack_tcp_timeout_established=86400;
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} conntrack MAX....: $nf_conntrack_max\n";}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} conntrack Timeout: $ip_conntrack_tcp_timeout_established\n";}
		
			
			if(is_file("/proc/sys/net/netfilter/nf_conntrack_acct")){
				$f[]="net.netfilter.nf_conntrack_acct = 1";
				$f[]="net.netfilter.nf_conntrack_checksum = 0";
				$f[]="net.netfilter.nf_conntrack_max = $nf_conntrack_max";
				$f[]="net.netfilter.nf_conntrack_tcp_timeout_established = $ip_conntrack_tcp_timeout_established";
				$f[]="net.netfilter.nf_conntrack_udp_timeout = 60";
				$f[]="net.netfilter.nf_conntrack_udp_timeout_stream = 180";
				$f[]="net.ipv4.netfilter.ip_conntrack_generic_timeout = 600";
				$f[]="net.ipv4.netfilter.ip_conntrack_tcp_timeout_syn_sent = 120";
				$f[]="net.ipv4.netfilter.ip_conntrack_tcp_timeout_syn_sent2 = 120";
				$f[]="net.ipv4.netfilter.ip_conntrack_tcp_timeout_syn_recv = 60";
				$f[]="net.ipv4.netfilter.ip_conntrack_tcp_timeout_established = $ip_conntrack_tcp_timeout_established";
				$f[]="net.ipv4.netfilter.ip_conntrack_tcp_timeout_fin_wait = 120";
				$f[]="net.ipv4.netfilter.ip_conntrack_tcp_timeout_close_wait = 60";
				$f[]="net.ipv4.netfilter.ip_conntrack_tcp_timeout_last_ack = 30";
				$f[]="net.ipv4.netfilter.ip_conntrack_tcp_timeout_time_wait = 120";
				$f[]="net.ipv4.netfilter.ip_conntrack_tcp_timeout_close = 10";
				$f[]="net.ipv4.netfilter.ip_conntrack_tcp_timeout_max_retrans = 300";
				$f[]="net.ipv4.netfilter.ip_conntrack_tcp_loose = 1";
				$f[]="net.ipv4.netfilter.ip_conntrack_tcp_be_liberal = 0";
				$f[]="net.ipv4.netfilter.ip_conntrack_tcp_max_retrans = 3";
				$f[]="net.ipv4.netfilter.ip_conntrack_udp_timeout = 30";
				$f[]="net.ipv4.netfilter.ip_conntrack_udp_timeout_stream = 180";
				$f[]="net.ipv4.netfilter.ip_conntrack_icmp_timeout = 30";
				$f[]="net.ipv4.netfilter.ip_conntrack_max = $nf_conntrack_max";
				$f[]="net.ipv4.netfilter.ip_conntrack_checksum = 1";
				$f[]="net.ipv4.netfilter.ip_conntrack_log_invalid = 0";
				shell_exec("$echo ".round($nf_conntrack_max/8)." > /sys/module/nf_conntrack/parameters/hashsize >/dev/null 2>&1");
			}
		}
	
	}
	

	if($EnableTCPOptimize){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Optimize TCP stack\n";}
		$f[]="net.ipv4.tcp_rfc1337 = 1";
		$f[]="net.ipv4.tcp_syn_retries = 3";
		$f[]="net.ipv4.tcp_synack_retries = 2";
		if($tcp_max_syn_backlog<1025){$tcp_max_syn_backlog=10240;}
		$wmem_max=trim(intval(@file_get_contents("/proc/sys/net/core/wmem_max")));
		
		$f[]="net.ipv4.tcp_max_syn_backlog = $tcp_max_syn_backlog";
		$f[]="net.ipv4.tcp_timestamps = 0";
		$f[]="net.ipv4.tcp_fin_timeout = 15";
		$f[]="net.ipv4.tcp_keepalive_time = 1800";
		$f[]="net.ipv4.tcp_reordering = 5";
		$f[]="net.ipv4.tcp_synack_retries = 3";
		$f[]="net.ipv4.tcp_max_tw_buckets = 360000";
		$f[]="net.core.netdev_max_backlog = 4000";
		$f[]="net.core.rmem_default = 262144";
		$f[]="net.core.rmem_max = 262144";
		$f[]="net.core.wmem_max = 262144";
		$f[]="net.ipv4.tcp_rmem=10240 87380 $wmem_max";
		$f[]="net.ipv4.tcp_wmem=10240 87380 $wmem_max";
		$f[]="net.ipv4.tcp_mem = $wmem_max $wmem_max $wmem_max";
		$f[]="net.ipv4.conf.all.log_martians=0";
		$f[]="net.ipv4.ip_local_port_range = 1024 65000";
		$f[]="net.ipv4.tcp_window_scaling = $tcp_window_scaling";
		$f[]="net.ipv4.tcp_ecn = $tcp_ecn";
		$f[]="net.ipv4.tcp_low_latency =1 ";
		$f[]="net.ipv4.tcp_timestamps=1";
		$f[]="net.ipv4.tcp_sack=1";
		$f[]="net.ipv4.tcp_no_metrics_save=1";
		$f[]="net.core.netdev_max_backlog=16384";
		$f[]="net.core.rmem_max=12582912";
		$f[]="net.core.wmem_max = 12582912";
		$f[]="net.core.wmem_default = 65535";
		$f[]="net.core.optmem_max = 40960";
		$f[]="net.ipv6.conf.all.accept_redirects = 1";
		$f[]="net.ipv6.conf.all.accept_source_route = 0";
		
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} TCP stack set to default\n";}
		$f[]="net.ipv4.tcp_rfc1337 = 0";
		$f[]="net.ipv4.tcp_low_latency = 0 ";
		$f[]="net.ipv4.tcp_syn_retries = 5";
		$f[]="net.ipv4.tcp_synack_retries = 5";
		$f[]="net.ipv4.tcp_max_syn_backlog = 512";
		$f[]="net.ipv4.tcp_timestamps = 1";
		$f[]="net.ipv4.tcp_fin_timeout = 60";
		$f[]="net.ipv4.tcp_keepalive_time = 7200";
		$f[]="net.ipv4.tcp_reordering = 3";
		$f[]="net.ipv4.tcp_max_tw_buckets = 65536";
		$f[]="net.ipv4.ip_local_port_range = 32768	61000";
		$f[]="net.core.rmem_default = 229376";
		$f[]="net.core.netdev_max_backlog = 1000";
		$f[]="net.core.rmem_max = 131071";
		$f[]="net.core.wmem_max = 131071";
		$f[]="net.ipv4.tcp_rmem = 4096	87380	1033696";
		$f[]="net.ipv4.tcp_wmem = 4096	16384	1033696";
		$f[]="net.ipv4.tcp_mem = 24225	32303	48450";
		$f[]="net.ipv4.tcp_window_scaling = $tcp_window_scaling";
		$f[]="net.ipv4.tcp_ecn = $tcp_ecn";
		$f[]="net.ipv4.tcp_sack = 1";
		$f[]="net.ipv4.tcp_no_metrics_save = 0";
		$f[]="net.core.netdev_max_backlog = 1000";
		$f[]="net.core.rmem_max = 131071";
		$f[]="net.core.wmem_max = 131071";
		$f[]="net.core.wmem_default = 229376";
		$f[]="net.core.optmem_max = 20480";
		$f[]="net.ipv4.icmp_echo_ignore_broadcasts = 1";
		$f[]="net.ipv4.conf.all.send_redirects = 1";
		$f[]="net.ipv4.conf.all.secure_redirects = 1";
		$f[]="net.ipv4.conf.all.accept_redirects = 1";
		$f[]="net.ipv4.conf.all.accept_source_route = 0";
		$f[]="net.ipv4.conf.all.arp_accept = 0";
		$f[]="net.ipv4.conf.all.arp_ignore = 0";
		$f[]="net.ipv4.conf.all.arp_announce = 0";
		$f[]="net.ipv4.conf.all.arp_filter = 0";
		$f[]="net.ipv4.conf.all.arp_notify = 0";
		$f[]="net.ipv4.ip_nonlocal_bind = 0";
		$f[]="net.ipv4.conf.all.log_martians=0";
		$f[]="net.ipv4.tcp_max_syn_backlog = 512";
		
	}
	
	
	if($Isagetway){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Set as gateway...\n";}
		$f[]="net.ipv4.ip_forward=1";
	
		if($EnableipV6==1){
			$f[]="net.ipv6.conf.all.send_redirects = $KernelSendRedirects";
			$f[]="net.ipv6.conf.all.forwarding=1";
			$f[]="net.ipv6.conf.all.accept_redirects = 1";
			$f[]="net.ipv6.conf.all.accept_source_route = 1";
			$f[]="net.ipv6.conf.all.arp_accept=0";
			$f[]="net.ipv6.conf.all.arp_ignore=1";
			$f[]="net.ipv6.conf.all.arp_announce=2";
			$f[]="net.ipv6.conf.all.arp_filter=1";
			$f[]="net.ipv6.conf.all.arp_notify=1";
		}
		
	}
	
	
	
	if($EnableipV6==1){
		$f[]="net.ipv6.conf.all.disable_ipv6 = 0";
		$f[]="net.ipv6.conf.default.disable_ipv6 = 0";
		$f[]="net.ipv6.conf.lo.disable_ipv6 = 0";
	}else{
		$f[]="net.ipv6.conf.all.disable_ipv6 = 1";
		$f[]="net.ipv6.conf.default.disable_ipv6 = 1";
		$f[]="net.ipv6.conf.lo.disable_ipv6 = 1";	
	}
	
	
	$SCRIPT[]="#!/bin/sh";
	$SCRIPT[]="### BEGIN INIT INFO";
	$SCRIPT[]="# Provides:         artica-optimize";
	$SCRIPT[]="# Required-Start:    \$local_fs";
	$SCRIPT[]="# Required-Stop:     \$local_fs";
	$SCRIPT[]="# Should-Start:";
	$SCRIPT[]="# Should-Stop:";
	$SCRIPT[]="# Default-Start:     2 3 4 5";
	$SCRIPT[]="# Default-Stop:      0 1 6";
	$SCRIPT[]="# Short-Description: artica-optimize";
	$SCRIPT[]="# chkconfig: - 80 75";
	$SCRIPT[]="# description: artica-optimize";
	$SCRIPT[]="### END INIT INFO";
	$SCRIPT[]="case \"\$1\" in";
	$SCRIPT[]=" start)";
	
	$SCRIPT[]="echo \"Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}\"";
	$echo=$unix->find_program("echo");
	while (list ($index, $line) = each ($f)){
		if(!preg_match("#(.+?)=(.+)#", $line,$re)){continue;}
		$SCRIPT[]="$sysctl -w \"".trim($re[1])."=".trim($re[2])."\" >/dev/null 2>&1 || true";
	}
	$SCRIPT[]="echo \"Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}\" done.";
	$SCRIPT[]=";;";
	$SCRIPT[]="*)";
    $SCRIPT[]="echo \"Usage: $0 start\"";
    $SCRIPT[]="exit 1";
    $SCRIPT[]=";;";
	$SCRIPT[]="esac";
	$SCRIPT[]="exit 0";
	$SCRIPT[]="";

	if(!is_file("/etc/init.d/artica-optimize")){$INSTALL_SERVICE=true;}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/init.d/artica-optimize done\n";}
	@file_put_contents("/etc/init.d/artica-optimize",@implode("\n", $SCRIPT));
	@chmod("/etc/init.d/artica-optimize",0755);
	
	$f[]="";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Installing init service\n";}
	@file_put_contents("/etc/sysctl.conf", @implode("\n", $f));
		
	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f artica-optimize defaults >/dev/null 2>&1");
	}
		
	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add artica-optimize >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 artica-optimize on >/dev/null 2>&1");
	}
		
	shell_exec("/etc/init.d/artica-optimize start");
	
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/sysctl.conf done\n";}
	if($GLOBALS["REBOOT"]){$reboot=$unix->find_program("reboot");shell_exec("$reboot");}
	start(true);
}

function stop(){}
