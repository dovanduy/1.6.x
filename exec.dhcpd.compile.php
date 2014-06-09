<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
if(is_array($argv)){
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	if(preg_match("#--no-reload#",implode(" ",$argv))){$GLOBALS["NORELOAD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	
}
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.dhcpd.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.iptables-chains.inc');
include_once(dirname(__FILE__) . '/ressources/class.baseunix.inc');
include_once(dirname(__FILE__) . '/ressources/class.bind9.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
$GLOBALS["ASROOT"]=true;
if($argv[1]=='--bind'){compile_bind();die();}
	if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
	if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
	if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
	if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();die();}
	if($argv[1]=="--reload-if-run"){$GLOBALS["OUTPUT"]=true;reload_if_run();die();}
	



BuildDHCP();

function BuildDHCP($nopid=false){

	$LOGBIN="DHCP Server";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".time";
	
	
	$unix=new unix();
	if(!$nopid){
		if(!$GLOBALS["FORCE"]){
			if($unix->file_time_min($timefile)<2){
				if($GLOBALS["VERBOSE"]){echo "$timefile -> is less than 2mn\n";}
				return;
			}
		}
	}
	
	$ldap=new clladp();
	if($ldap->ldapFailed){echo "Starting......: ".date("H:i:s")." [INIT]: $LOGBIN ldap connection failed,aborting\n";return;}
	if(!$ldap->ExistsDN("dc=organizations,$ldap->suffix")){echo "Starting......: ".date("H:i:s")." DHCP SERVER dc=organizations,$ldap->suffix no such branch, aborting\n";return;	}
	echo "Starting......: ".date("H:i:s")." [INIT]: $LOGBIN ldap connection success\n";
	$dhcpd=new dhcpd();
	$conf=$dhcpd->BuildConf();
	$confpath=dhcp3Config();
	$unix=new unix();
	@mkdir(dirname($confpath),null,true);
	@file_put_contents($confpath,$conf);
	echo "Starting......: ".date("H:i:s")." [INIT]: $LOGBIN saving \"$confpath\" (". strlen($conf)." bytes) done\n";
	
	if(!$unix->UnixUserExists("dhcpd")){
		$unix->CreateUnixUser("dhcpd","dhcpd");
	}
	if(!is_dir("/var/lib/dhcp3")){@mkdir("/var/lib/dhcp3",0755,true);}
	$unix->chown_func("dhcpd","dhcpd", "/var/lib/dhcp3/*");
	$unix->chmod_func(0755, "/var/lib/dhcp3");
	$complain=$unix->find_program("aa-complain");
	
	if(is_file($complain)){
		$dhcpd3=$unix->DHCPD_BIN_PATH();
		if(is_file($dhcpd3)){shell_exec("$complain $dhcpd3 >/dev/null 2>&1");}
	}
	
	@unlink($timefile);
	@file_put_contents($timefile, time());
	
	$sock=new sockets();
	$sock->getFrameWork("dnsmasq.php?restart=yes");
	$sock->getFrameWork("services.php?restart-monit=yes");
	$sock->getFrameWork("cmd.php?restart-artica-status=yes");
	
}

function compile_bind(){
	$bind=new bind9();
	$bind->Compile();
	$bind->SaveToLdap();
}

function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$LOGBIN="DHCP Server";
	$binpath=$unix->DHCPD_BIN_PATH();
	if(!is_file($binpath)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $LOGBIN, not installed\n";}
		return;
	}
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $LOGBIN, Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$pid=PID_NUM();	
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $LOGBIN Service already started $pid since {$timepid}Mn...\n";}
		return;
	}
	
	$EnableDHCPServer=$sock->GET_INFO("EnableDHCPServer");
	if(!is_numeric($EnableDHCPServer)){$EnableDHCPServer=0;}
	
	$EnableChilli=$sock->GET_INFO("EnableChilli");
	if(!is_numeric($EnableChilli)){$EnableChilli=0;}
	
	
	
	if($EnableChilli==1){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $LOGBIN replaced by HotSpot feature...\n";}$EnableDHCPServer=0;}
	if($EnableDHCPServer==0){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $LOGBIN service disabled\n";}return;}	
	
	@mkdir("/var/run/dhcp3-server",0755,true);
	@mkdir("/var/lib/dhcp3",0755,true);
	
	if(!is_file("/var/lib/dhcp3/dhcpd.other")){@file_put_contents("/var/lib/dhcp3/dhcpd.other", "#");}
	if(!is_file("/var/lib/dhcp3/dhcpd.leases")){@file_put_contents("/var/lib/dhcp3/dhcpd.leases", "#");}
	$unix->SystemCreateUser("dhcpd","dhcpd");
	$unix->chown_func("dhcpd", "dhcpd","/var/run/dhcp3-server");
	$unix->chown_func("dhcpd", "dhcpd","/var/lib/dhcp3/dhcpd.leases");
	$unix->chown_func("dhcpd", "dhcpd","/var/lib/dhcp3/dhcpd.leases~");
	
	
	$DHCP3ListenNIC=$sock->GET_INFO('DHCP3ListenNIC');
	if($DHCP3ListenNIC==null){$DHCP3ListenNIC="eth0";}
	echo "Starting......: ".date("H:i:s")." [INIT]: $LOGBIN Listen $DHCP3ListenNIC\n";
	echo "Starting......: ".date("H:i:s")." [INIT]: $LOGBIN building settings...\n";
	BuildDHCP(true);
	
	$CMD[]="$binpath -q -pf ".PID_PATH();
	$CMD[]="-cf ".dhcp3Config();
	$CMD[]="-lf /var/lib/dhcp3/dhcpd.leases";
	$CMD[]=$DHCP3ListenNIC;
	$cmd=@implode(" ", $CMD);
	
	echo "Starting......: ".date("H:i:s")." [INIT]: $LOGBIN service..\n";
	
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	shell_exec($cmd);
	
	for($i=0;$i<6;$i++){
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $LOGBIN service waiting $i/6...\n";}
		sleep(1);
	}
	
	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $LOGBIN service Success service started pid:$pid...\n";}
		return;
	}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $LOGBIN service failed...\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: `$cmd`\n";}
	
	
}
//##############################################################################
function restart(){
	$unix=new unix();
	$LOGBIN="DHCP Server";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $LOGBIN Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	stop(true);
	start(true);

}

function reload_if_run(){
	$pid=PID_NUM();
	$unix=new unix();
	if(!$unix->process_exists($pid)){die();}
	reload();
}

function reload(){
	$unix=new unix();
	$LOGBIN="DHCP Server";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $LOGBIN Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	$pid=PID_NUM();
	$time=$unix->PROCCESS_TIME_MIN($pid);
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	BuildDHCP(true);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $LOGBIN reloading PID $pid since {$time}mn\n";}	
	stop(true);
	start(true);

}
//##############################################################################
function stop($aspid=false){
	$LOGBIN="DHCP Server";
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $LOGBIN service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: $LOGBIN service already stopped...\n";}
		return;
	}
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");



	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: $LOGBIN service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $LOGBIN service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: $LOGBIN service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: $LOGBIN service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $LOGBIN service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: $LOGBIN service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: $LOGBIN service failed...\n";}

}
//##############################################################################
function PID_NUM(){
	$filename=PID_PATH();
	$pid=trim(@file_get_contents($filename));
	$unix=new unix();
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF($unix->DHCPD_BIN_PATH());
}
//##############################################################################
function PID_PATH(){
	return '/var/run/dhcpd.pid';
}
//##############################################################################

function dhcp3Config(){
	
	$f[]="/etc/dhcp3/dhcpd.conf";
	$f[]="/etc/dhcpd.conf";
	$f[]="/etc/dhcpd/dhcpd.conf";
	while (list ($index, $filename) = each ($f) ){
		if(is_file($filename)){return $filename;}
	} 
	return "/etc/dhcp3/dhcpd.conf";
	
}
?>