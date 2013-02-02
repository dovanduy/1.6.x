<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__)."/framework/class.settings.inc");

if($argv[1]=="--status"){echo status();return;}

fstab();
ismounted();

function fstab(){
	$sock=new sockets();
	$unix=new unix();
	$DisableSquidSNMPMode=$sock->GET_INFO("DisableSquidSNMPMode");
	if(!is_numeric($DisableSquidSNMPMode)){$DisableSquidSNMPMode=1;}
	$mkdir=$unix->find_program("mkdir");
	$mount=$unix->find_program("mount");
	if(!is_dir("/dev/shm")){
		echo "Starting......: [SMP] creating /dev/shm directory..\n";
		shell_exec("$mkdir -m 1777 /dev/shm");
	}
	echo "Starting......: [SMP] checking fstab...\n";
	$datas=explode("\n",@file_get_contents("/etc/fstab"));
	
	while (list ($num, $val) = each ($datas)){
		if(preg_match("#^shm.*?tmpfs#", $val,$re)){
			echo "Starting......: [SMP] checking fstab already set...\n";
			return;
		}
		
	}
	
	echo "Starting......: [SMP] Adding SHM mount point\n";
	$datas[]="shm\t/dev/shm\ttmpfs\tnodev,nosuid,noexec\t0\t0";
	@file_put_contents("/etc/fstab", @implode("\n", $datas)."\n");
	echo "Starting......: [SMP] mounting shm point\n";
	exec("$mount shm 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		echo "Starting......: [SMP] mounting shm `$val`\n";
	}
}

function ismounted(){
	$unix=new unix();
	$datas=explode("\n",@file_get_contents("/proc/mounts"));
	while (list ($num, $val) = each ($datas)){
		if(preg_match("#^shm\s+\/dev\/shm\s+tmpfs#", $val,$re)){
			echo "Starting......: [SMP] shm is mounted\n";
			return;
		}
	}	
	$mount=$unix->find_program("mount");
	echo "Starting......: [SMP] mounting shm point\n";
	exec("$mount shm 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		echo "Starting......: [SMP] mounting shm `$val`\n";
	}
}

function status(){
	$unix=new unix();
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	$DisableSquidSNMPMode=$sock->GET_INFO("DisableSquidSNMPMode");
	if(!is_numeric($DisableSquidSNMPMode)){$DisableSquidSNMPMode=1;}
	if($DisableSquidSNMPMode==1){return;}
	$pidof=$unix->find_program("pidof");
	$squidbin=$unix->find_program("squid");	
	
	
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
	exec("$pidof $squidbin 2>&1",$results);
	$tb=explode(" ",@implode("", $results));
	
	while (list ($num, $pid) = each ($tb)){
		$pid=trim($pid);
		$other=null;$other1="<i>{main}</i>";
		if(!is_numeric($pid)){continue;}
		$filename="/proc/$pid/cmdline";
		if(!is_file($filename)){continue;}
		$names=@file_get_contents($filename);
		if(preg_match("#^\((.*?)\)#", $names,$re)){
			$other=":".$re[1];
			$other1="<i>Proc:{$re[1]}</i>";
		}
		
		$l[]="[SQUID$other]";
		$l[]="service_name=APP_SQUID";
		$l[]="master_version=". squid_master_status_version();
		$l[]="service_cmd=squid-cache";
		$l[]="service_disabled=1";
		$l[]="watchdog_features=1";
		$l[]="binpath=$squidbin";
		$l[]="explain=SQUID_CACHE_TINYTEXT";
		$l[]="remove_cmd=--squid-remove";
		$l[]="family=squid";
		$l[]="other=$other1";
		$l[]="running=1";
		$l[]=$unix->GetMemoriesOfChild($pid,true);		
		
	}
	
	
	echo @implode("\n", $l);
	
	
}

function squid_master_status_version(){
	if(isset($GLOBALS["squid_master_status_version"])){return $GLOBALS["squid_master_status_version"];}
	$unix=new unix();
	$squidbin=$unix->find_program("squid");
	if($squidbin==null){$squidbin=$unix->find_program("squid3");}
	exec("$squidbin -v 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#Squid Cache: Version.*?([0-9\.]+)#", $val,$re)){
			if($GLOBALS["VERBOSE"]){echo "Starting......: Squid : Version (as root) '{$re[1]}'\n";}
			$GLOBALS["squid_master_status_version"]=$re[1];
			return $GLOBALS["squid_master_status_version"];
		}
	}
	if($GLOBALS["VERBOSE"]){echo "Warning !!!!!! cannot find version in $squidbin ! !!\n";}
}

