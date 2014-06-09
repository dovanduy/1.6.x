<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="SALS Auth Daemon";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}



$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}




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
	sleep(1);
	build();
	start(true);

}

function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("saslauthd");
	$instances=5;
	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, saslauthd not installed\n";}
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
		$instances=2;
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} not enough memory instances = $instances\n";}
		return;
	}
	
	$EnableDaemon=1;
	$users=new settings_inc();
	if(!$users->POSTFIX_INSTALLED){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Postfix: Not installed\n";}
		if(!$users->cyrus_imapd_installed){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Cyrus Impad: Not installed\n";}
			$EnableDaemon=0;
		}
	}
	
	

	if($EnableDaemon==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see Postfix/Cyrus)\n";}
		stop();
		return;
	}



	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		return;
	}
	$EnableDaemon=1;
	



	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	$ln=$unix->find_program("ln");
	$chmod=$unix->find_program("chmod");
	$EnableVirtualDomainsInMailBoxes=$sock->GET_INFO("EnableVirtualDomainsInMailBoxes");
	$SaslAuthdConfigured=$sock->GET_INFO("SaslAuthdConfigured");
	$CyrusToAD=$sock->GET_INFO("CyrusToAD");
	if(!is_numeric($EnableVirtualDomainsInMailBoxes)){$EnableVirtualDomainsInMailBoxes=0;}
	if(!is_numeric($SaslAuthdConfigured)){$SaslAuthdConfigured=0;}
	if(!is_numeric($CyrusToAD)){$CyrusToAD=0;}

	
	@mkdir("/var/run/saslauthd",0755,true);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} EnableVirtualDomainsInMailBoxes = $EnableVirtualDomainsInMailBoxes\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} CyrusToAD = $CyrusToAD\n";}	
	
	$mech="ldap";
	if($EnableVirtualDomainsInMailBoxes==1){
		$moinsr='-r ';
	}
	
	if($CyrusToAD==1){
		$mech='pam';
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} saslauthd enable pam authentifications\n";}
		shell_exec($unix->LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.cyrus.php --kinit >/dev/null 2>&1');
	}
	
	if(!$SaslAuthdConfigured){build();$sock->SET_INFO("SaslAuthdConfigured",1);}
	
	$cmd="$Masterbin $moinsr -a $mech -c -m /var/run/saslauthd -n $instances";
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
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} symlink from /var/run/saslauthd to /var/run/sasl2\n";}
		shell_exec("$ln -sf /var/run/saslauthd /var/run/sasl2 >/dev/null 2>&1");
		@mkdir('/var/spool/postfix/var',0755,true);
		shell_exec("$ln -sf /var/run /var/spool/postfix/var/run >/dev/null 2>&1");
		shell_exec("$chmod 0755 /var/run/saslauthd >/dev/null 2>&1");
		shell_exec("$chmod 0777 /var/run/saslauthd/* >/dev/null 2>&1");
		
		

	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	}


}

function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		return;
	}
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	



	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
		unix_system_kill_force($pid);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}

}

function PID_NUM(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file(PID_PATH());
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("saslauthd");
	return $unix->PIDOF($Masterbin);

}
//#########################################################################################
function PID_PATH(){
	if(is_file('/var/run/saslauthd/saslauthd.pid')){return('/var/run/saslauthd/saslauthd.pid');}
	if(is_file('/var/run/saslauthd.pid')){return('/var/run/saslauthd.pid');}
	if(is_file('/var/run/saslauthd/saslauthd.pid')){return('/var/run/saslauthd/saslauthd.pid');}
}
//#########################################################################################
function saslauthd_conf(){
	if(is_file("/etc/saslauthd.conf")){return "/etc/saslauthd.conf";}
	if(is_file("/usr/local/etc/saslauthd.conf")){return "/usr/local/etc/saslauthd.conf";}
	return "/etc/saslauthd.conf";
}



function build(){
	$sock=new sockets();
	$unix=new unix();

	$EnableMechLogin=$sock->GET_INFO("EnableMechLogin");
	$EnableMechPlain=$sock->GET_INFO("EnableMechPlain");
	$EnableMechDigestMD5=$sock->GET_INFO("EnableMechDigestMD5");
	$EnableMechCramMD5=$sock->GET_INFO("EnableMechCramMD5");
	if(!is_numeric($EnableMechLogin)){$EnableMechLogin=1;}
	if(!is_numeric($EnableMechPlain)){$EnableMechPlain=1;}
	
	if(!is_numeric($EnableMechDigestMD5)){$EnableMechDigestMD5=0;}
	if(!is_numeric($EnableMechCramMD5)){$EnableMechCramMD5=0;}
	
	if($EnableMechLogin==1){$mech_list[]="LOGIN";}
	if($EnableMechPlain==1){$mech_list[]="PLAIN";}
	if($EnableMechDigestMD5==1){$mech_list[]="DIGEST-MD5";}
	if($EnableMechCramMD5==1){$mech_list[]="CRAM-MD5";}
	
	$ldap=new clladp();
	$conf[]="ldap_servers: ldap://$ldap->ldap_host:$ldap->ldap_port/";
	$conf[]="ldap_version: 3";
	$conf[]="ldap_search_base: dc=organizations,$ldap->suffix";
	$conf[]="ldap_scope: sub";
	$conf[]="ldap_filter: uid=%u";
	$conf[]="ldap_auth_method: bind";
	$conf[]="ldap_bind_dn: cn=$ldap->ldap_admin,$ldap->suffix";
	$conf[]="ldap_password: $ldap->ldap_password";
	$conf[]="ldap_timeout: 10";
	$conf[]="";
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} ldap://$ldap->ldap_host:$ldap->ldap_port\n";}
	@file_put_contents(saslauthd_conf(),@implode("\n",$conf));	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}  ".@implode(" ", $mech_list)."\n";}
	
	$f[]="pwcheck_method: saslauthd";
	$f[]="mech_list: ".@implode(" ", $mech_list);
	$f[]="minimum_layer: 0";
	$f[]="log_level: 5";

	
	@mkdir('/etc/postfix/sasl',0755);
	@file_put_contents('/etc/postfix/sasl/smtpd.conf',@implode("\n", $f));
	if(!is_file("/usr/lib/sasl2/smtpd.conf")){
		$ln=$unix->find_program("ln"); 
		shell_exec("$ln -s /etc/postfix/sasl/smtpd.conf  /usr/lib/sasl2/smtpd.conf >/dev/null 2>&1"); 
	}
	
	
	
	
}
