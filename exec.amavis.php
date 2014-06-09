<?php
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	$GLOBALS["EXECUTED_AS_ROOT"]=true;
	$GLOBALS["OUTPUT"]=false;
	if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	include(dirname(__FILE__).'/ressources/class.amavis.inc');
	include_once(dirname(__FILE__).'/framework/frame.class.inc');
	include_once(dirname(__FILE__).'/framework/class.unix.inc');
	if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
	$GLOBALS["TITLENAME"]="Amavisd-New daemon";
	$me=basename(__FILE__);
	
	
	if($argv[1]=="--whitelist"){$GLOBALS["OUTPUT"]=true;buildWhitelist();exit;}
	
	$unix=new unix();
	$pidpath="/etc/artica-postfix/pids/$me.pid";
	$pid=$unix->get_pid_from_file($pidpath);
	if($unix->process_exists($pid,$me)){
		echo "Starting......: ".date("H:i:s")." amavisd-new already executed pid $pid\n";
		die();
	}
	
	@file_put_contents($pidpath, getmypid());
	
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php /usr/share/artica-postfix/exec.spamassassin.php --sa-update >/dev/null 2>&1 &";	
	shell_exec($cmd);
	
	echo "Starting......: ".date("H:i:s")." amavisd-new build configuration\n";
	
	$amavis=new amavis();
	$amavis->CheckDKIM();
	$conf=$amavis->buildconf();
	
	PatchPyzor();
	
	$tpl[]="template-spam-admin.txt";
	$tpl[]="template-spam-sender.txt";
	$tpl[]="template-dsn.txt";
	$tpl[]="template-virus-admin.txt";
	$tpl[]="template-virus-recipient.txt";
	$tpl[]="template-virus-sender.txt";
	
	@mkdir("/usr/local/etc/amavis",0755,true);
	while (list ($index, $file) = each ($tpl)){
		if(!is_file("/usr/local/etc/amavis/$file")){
			echo "Starting......: ".date("H:i:s")." amavisd-new installing template $file\n";
			@copy("/usr/share/artica-postfix/bin/install/amavis/$file","/usr/local/etc/amavis/$file");
			
		}
	}
	
	
	echo "Starting......: ".date("H:i:s")." amavisd-new ". strlen($conf)." bytes length\n";
	@file_put_contents("/usr/local/etc/amavisd.conf",$conf);
	shell_exec("/bin/chown -R postfix:postfix /etc/amavis/dkim >/dev/null 2>&1");
	shell_exec("/bin/chown -R postfix:postfix /usr/local/etc/amavis >/dev/null 2>&1");
	shell_exec("/bin/chown -R postfix:postfix /usr/local/etc/amavis/* >/dev/null 2>&1");
	shell_exec("/bin/chown root:root /var/amavis-plugins/check-external-users.conf");
	shell_exec("/bin/chown root:root /var/amavis-plugins");
	shell_exec("/bin/chmod 755 /var/amavis-plugins");
	shell_exec("/bin/chmod -R 755 /etc/amavis/dkim >/dev/null 2>&1");
	shell_exec("/bin/chmod -R 755 /usr/local/etc/amavis >/dev/null 2>&1");
	shell_exec("/bin/chmod -R 755 /usr/local/etc/amavis/* >/dev/null 2>&1");
	
	
	if(is_dir("/etc/mail/spamassassin")){
		shell_exec("/bin/chmod -R 666 /etc/mail/spamassassin");
		shell_exec("/bin/chown -R postfix:postfix /etc/mail/spamassassin");
		shell_exec("/bin/chmod 755 /etc/mail/spamassassin");		
	}
	if(is_dir("/etc/spamassassin")){
		shell_exec("/bin/chmod -R 666 /etc/spamassassin");
		shell_exec("/bin/chmod 755 /etc/spamassassin");
		shell_exec("/bin/chown -R postfix:postfix /etc/spamassassin");
	}
	
	if(is_dir("/var/lib/spamassassin")){
		shell_exec("/bin/chmod -R 755 /var/lib/spamassassin");
		shell_exec("/bin/chown -R postfix:postfix /var/lib/spamassassin");
	}	
	

	
	echo "Starting......: ".date("H:i:s")." amavisd-new done\n";

	$unix=new unix();
	$unix->THREAD_COMMAND_SET($unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.spamassassin.php");
	
	
	
	
function PatchPyzor(){
	$unix=new unix();
	$pyzor=$unix->find_program("pyzor");
	if(!is_file($pyzor)){
		echo "Starting......: ".date("H:i:s")." amavisd-new pyzor is not installed\n";
		return;
	}
	
	$f[]="#!/usr/bin/python -W ignore::DeprecationWarning";
	$f[]="import os";
	$f[]="os.umask(0077)";
	$f[]="import pyzor.client";
	$f[]="pyzor.client.run()";	

	@file_put_contents($pyzor, @implode("\n", $f));
	echo "Starting......: ".date("H:i:s")." amavisd-new pyzor is now patched\n";

}


function buildWhitelist($aspid=false){
	$unix=new unix();
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Building......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$amavis=new amavis();
	$amavis->whitelist_sender_maps();
	if($GLOBALS["OUTPUT"]){echo "Building......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Building Whitelist done\n";}
	reload(true);
}


function reload($aspid=false){
	$unix=new unix();
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Reloading.....: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$amavisbin=$unix->LOCATE_AMAVISD_BIN_PATH();
	$pid=PID_NUM();
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Reloading.....: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Not running\n";}
		return;
	}
	
	$TTL=$unix->PROCESS_TTL($pid);
	$nohup=$unix->find_program("nohup");
	if($GLOBALS["OUTPUT"]){echo "Reloading.....: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} reloading PID $pid running since {$TTL}mn\n";}
	$cmd="$nohup $amavisbin -c /usr/local/etc/amavisd.conf reload >/dev/null 2>&1 &";
	shell_exec($cmd);
}
//#############################################################################
function AMAVISD_PID_PATH(){
if(is_file('/var/spool/postfix/var/run/amavisd-new/amavisd-new.pid')){return "/var/spool/postfix/var/run/amavisd-new/amavisd-new.pid";}
if(is_file('/var/run/amavis/amavisd.pid')){return '/var/run/amavis/amavisd.pid';}
}
//#############################################################################

function PID_NUM(){
	$unix=new unix();
	$AMAVISD_PID_PATH=AMAVISD_PID_PATH();
	$pid=$unix->get_pid_from_file($AMAVISD_PID_PATH);
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF_PATTERN("amavisd \(master");

}

function AMAVISD_BIN_PATH(){
	if(is_file('/usr/local/sbin/amavisd')){return '/usr/local/sbin/amavisd';}
	if(is_file('/usr/sbin/amavisd-new')){return '/usr/sbin/amavisd-new';}

}
//#############################################################################

function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=AMAVISD_BIN_PATH();

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, arpd not installed\n";}
		return;
	}

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
	
	$pid=PID_NUM();
	
	if($unix->MEM_TOTAL_INSTALLEE()<624288){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} not enough memory\n";}
		if($unix->process_exists($pid)){stop();}
		return;
	}

	

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		return;
	}

	$EnableAmavisDaemon=intval($sock->GET_INFO("EnableAmavisDaemon"));
	$EnableAmavisInMasterCF=intval($sock->GET_INFO("EnableAmavisInMasterCF"));
	$AmavisMemoryInRAM=intval($sock->GET_INFO("AmavisMemoryInRAM"));
	$EnablePostfixMultiInstance=intval($sock->GET_INFO("EnablePostfixMultiInstance"));
	$EnableStopPostfix=intval($sock->GET_INFO("EnableStopPostfix"));

	
	

	if($EnableAmavisDaemon==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableArpDaemon)\n";}
		return;
	}

	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	
	if ($ArpdKernelLevel>0){$ArpdKernelLevel_string=" -a $ArpdKernelLevel";}
	$Interfaces=$unix->NETWORK_ALL_INTERFACES();
	$nic=new system_nic();
	while (list ($Interface, $ligne) = each ($Interfaces) ){
		if($Interface=="lo"){continue;}
		if($ligne["IPADDR"]=="0.0.0.0"){continue;}
		$Interface=$nic->NicToOther($Interface);
		$TRA[$Interface]=$Interface;
	}
	
	while (list ($Interface, $ligne) = each ($TRA) ){$TR[]=$Interface; }
	@mkdir('/var/lib/arpd',0755,true);
	
	$f[]="$Masterbin -b /var/lib/arpd/arpd.db";
	$f[]=$ArpdKernelLevel;
	
	if(count($TR)>0){
		$f[]="-k ".@implode($TR," ");
	}
	
	
	$cmd=@implode(" ", $f) ." >/dev/null 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	
	shell_exec($cmd);
	
	
	

	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	}


}






	
	
?>