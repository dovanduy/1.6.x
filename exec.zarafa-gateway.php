<?php
$GLOBALS["KILL"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){
	echo "VERBOSED\n";
	$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;
	$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--kill#",implode(" ",$argv),$re)){$GLOBALS["KILL"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');

if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}

echo "php ".__FILE__." --stop ( stop the zarafa-gateway)\n";
//echo "php ".__FILE__." --start ( start the zarafa-server)\n";

function XZARAFA_GATEWAY_PID(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/zarafa-gateway.pid");
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF($unix->find_program("zarafa-gateway"));

}
function ZARAFADB_PID(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/zarafa-db.pid");
	if($unix->process_exists($pid)){return $pid;}
	$mysqld=$unix->find_program("mysqld");
	$pid=$unix->PIDOF_PATTERN("$mysqld.*?--pid-file=/var/run/zarafa-db.pid");
	return $pid;


}

//##############################################################################
function restart($nopid=false){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if(!$nopid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
	}
	@file_put_contents($pidfile, getmypid());
	stop(true);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: zarafa-server reconfiguring\n";}
	
	shell_exec("$php /usr/share/artica-postfix/exec.zarafa.build.stores.php --ldap-config");
	shell_exec("/usr/share/artica-postfix/bin/artica-install --zarafa-reconfigure >/dev/null 2>&1");
	start(true);
}
//##############################################################################

function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	
	$pidfile="/etc/artica-postfix/pids/zarafa-server-starter.pid";
	$PidRestore="/etc/artica-postfix/pids/zarafaRestore.pid";
	$PidLock="/etc/artica-postfix/LOCK_ZARAFA";
	
	$pid=$unix->get_pid_from_file($PidRestore);
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: zarafa-server Engine Artica Restore running PID $pid since {$time}mn\n";}
		return;
	}
	
	if(!$aspid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: zarafa-server Engine Artica Task Already running PID $pid since {$time}mn\n";}
			return;
		}
	}
	@file_put_contents($pidfile, getmypid());
	$serverbin=$unix->find_program("zarafa-server");


	if(!is_file($serverbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: zarafa-server Engine is not installed...\n";}
		return;
	}
	
	if(is_file($PidLock)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: zarafa-server !! Locked !! ( $PidLock ) aborting...\n";}
		return;
	}
	
	
	$SLAPD_PID_FILE=$unix->SLAPD_PID_PATH();
	$pid=$unix->get_pid_from_file($SLAPD_PID_FILE);
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: zarafa-server Engine OpenLDAP server is not running start it...\n";}
		shell_exec("/etc/init.d/slapd start");
		return;
	}

		
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: zarafa-server Engine Failed, OpenLDAP server is not running...\n";}		
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: zarafa-server Engine OpenLDAP server is running...\n";}
	}
	
	if(!is_file("/usr/lib/libmapi.so")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: zarafa-server fix /usr/lib/libmapi.so\n";}
		if(is_file("/home/artica/zarafa.tar.gz.old")){
			$tar=$unix->find_program("tar");
			shell_exec("$tar -xf /home/artica/zarafa.tar.gz.old -C /");
		}
	}
	
	if(is_dir("/usr/share/zarafa-webapp/webapp-1.4.svn42633")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: zarafa-server fix webapp-1.4.svn42633\n";}
		$cp=$unix->find_program("cp");
		$rm=$unix->find_program("rm");
		shell_exec("$cp -rf /usr/share/zarafa-webapp/webapp-1.4.svn42633/ /usr/share/zarafa-webapp/");
		shell_exec("$rm -rf /usr/share/zarafa-webapp/webapp-1.4.svn42633");
		
	}
	
	$ZarafaMySQLServiceType=$sock->GET_INFO("ZarafaMySQLServiceType");
	$ZarafaDedicateMySQLServer=$sock->GET_INFO("ZarafaDedicateMySQLServer");
	if(!is_numeric($ZarafaMySQLServiceType)){$ZarafaMySQLServiceType=1;}
	if(!is_numeric($ZarafaDedicateMySQLServer)){$ZarafaDedicateMySQLServer=0;}

	
	if($ZarafaDedicateMySQLServer==1){
		if($ZarafaMySQLServiceType==3){
			$PID=ZARAFADB_PID();
			if(!$unix->process_exists($PID)){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: zarafa-server Engine Failed, Zarafa Database is not running\n";}
			}
		}
	}
		
	

	$pid=XZARAFA_SERVER_PID();

	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: zarafa-server Engine already running pid $pid since {$time}mn\n";}
		return;
	}


	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: zarafa-server Engine reconfigure...\n";}
	system("/usr/share/artica-postfix/bin/artica-install --zarafa-reconfigure");
	@unlink("/usr/share/artica-postfix/ressources/logs/zarafa.notify");
	@unlink("/usr/share/artica-postfix/ressources/logs/zarafa.notify.MySQLIssue");
	$f[]=$serverbin;
	$f[]="--config=/etc/zarafa/server.cfg";
	$f[]="--ignore-database-version-conflict";
	$f[]="--ignore-unknown-config-options";
	$f[]="--ignore-attachment-storage-conflict";

	$cmdline=@implode(" ", $f);

	if($GLOBALS["VERBOSE"]){echo $cmdline."\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Starting zarafa-server daemon\n";}
	shell_exec("$cmdline 2>&1");
	sleep(1);

	for($i=0;$i<5;$i++){
		$pid=XZARAFA_SERVER_PID();
		if($unix->process_exists($pid)){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: zarafa-server daemon started pid .$pid..\n";}break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: zarafa-server daemon wait $i/5\n";}
		sleep(1);
	}

	$pid=XZARAFA_SERVER_PID();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: zarafa-server daemon failed to start\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $cmdline\n";
		}else{
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: zarafa-server daemon success PID $pid\n";}

		}
	}
}

function reload(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Reloading.....: [INIT]: Already task running PID $pid since {$time}mn\n";}
		return;
	}
	
	$pid=XZARAFA_SERVER_PID();
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Reloading.....: [INIT]: zarafa-server stopped...\n";}
		$php5=$unix->LOCATE_PHP5_BIN();
		shell_exec("$php5 ".__FILE__." --start");
		return;
	}	
	if($GLOBALS["OUTPUT"]){echo "Reloading.....: [INIT]: zarafa-server reconfigure...\n";}
	system("/usr/share/artica-postfix/bin/artica-install --zarafa-reconfigure");	
	if($GLOBALS["OUTPUT"]){echo "Reloading.....: [INIT]: zarafa-server reloading PID $pid...\n";}
	$kill=$unix->find_program("kill");
	unix_system_HUP($pid);
	
}


function stop($aspid=false){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if(!$aspid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Already task running PID $pid since {$time}mn\n";}
			return;
		}
	}
	
	@file_put_contents($pidfile, getmypid());
	$pid=XZARAFA_GATEWAY_PID();

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: zarafa-gateway already stopped...\n";}
		return;
	}
	
	$time=$unix->PROCCESS_TIME_MIN($pid);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: zarafa-gateway Daemon with a ttl of {$time}mn\n";}
	$kill=$unix->find_program("kill");

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: zarafa-gateway killing smoothly PID $pid...\n";}
	unix_system_kill($pid);
	sleep(1);

	for($i=1;$i<10;$i++){
		$pid=XZARAFA_GATEWAY_PID();
		if(!$unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: zarafa-gateway pid $pid successfully stopped ...\n";}
			break;
		}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: zarafa-gateway wait PID $pid $i/10\n";}
		unix_system_kill($pid);
		sleep(1);
	}

	$pid=XZARAFA_GATEWAY_PID();
	

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: zarafa-gateway daemon success...\n";}
		return;
	}
	
	for($i=1;$i<120;$i++){
		$pid=XZARAFA_GATEWAY_PID();
		if($unix->process_exists($pid)){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: zarafa-gateway Force KILL $pid with a ttl of {$time}mn\n";}
			unix_system_kill_force($pid);
		}else{
			break;
		}
	}

	$pid=XZARAFA_GATEWAY_PID();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: zarafa-gateway daemon success...\n";}
		return;
	}	
	
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: zarafa-gateway daemon failed...\n";}
}
?>