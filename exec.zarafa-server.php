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

echo "php ".__FILE__." --stop ( stop the zarafa-server)\n";
echo "php ".__FILE__." --start ( start the zarafa-server)\n";

function XZARAFA_SERVER_PID(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/zarafa-server.pid");
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF_PATTERN("zarafa-server -c /etc/zarafa/server.cfg");

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
		recursive_remove_directory("/usr/share/zarafa-webapp/webapp-1.4.svn42633");
		
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
	procmap();
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
	$suffix=null;
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
	$pid=XZARAFA_SERVER_PID();

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: zarafa-server already stopped...\n";}
		return;
	}
	
	system_admin_events("Warning, Ordered to stop Zarafa service",__FUNCTION__,__FILE__,__LINE__,"mailboxes");
	
	if($GLOBALS["KILL"]){
		$killopt=" -9";
		@unlink("/tmp/zarafa-upgrade-lock");
		$suffix=" (forced)";
	}

	if(is_file("/tmp/zarafa-upgrade-lock")){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: zarafa-server database upgrade is taking place.\n";}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Do not stop this process bacause it may render your database unusable..\n";}
		return;
	}

	$time=$unix->PROCCESS_TIME_MIN($pid);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: zarafa-server Daemon with a ttl of {$time}mn$suffix\n";}
	$kill=$unix->find_program("kill");

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: zarafa-server killing smoothly PID $pid...$suffix\n";}
	shell_exec("$kill$killopt $pid");
	sleep(1);

	for($i=1;$i<60;$i++){
		$pid=XZARAFA_SERVER_PID();
		if(!$unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: zarafa-server pid $pid successfully stopped ...$suffix\n";}
			break;
		}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: zarafa-server wait $i/60$suffix\n";}
		shell_exec("$kill$killopt $pid");
		sleep(1);
	}
	
	
	if($GLOBALS["KILL"]){
		$zarafadmin=$unix->find_program("zarafa-admin");
		$pid=$unix->PIDOF($zarafadmin);
		if($unix->process_exists($pid)){
			for($i=1;$i<60;$i++){
				if(!$unix->process_exists($pid)){break;}
				if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Stopping zarafa-admin PID $pid$suffix\n";}
				unix_system_kill_force($pid);
				$pid=$unix->PIDOF($zarafadmin);
			}
		}
		
		$createuser_pid=$unix->PIDOF_PATTERN("createuser.d");
		if($unix->process_exists($createuser_pid)){
			for($i=1;$i<60;$i++){
				if(!$unix->process_exists($createuser_pid)){break;}
				if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Stopping createuser.d PID $createuser_pid$suffix\n";}
				unix_system_kill_force($createuser_pid);
				$createuser_pid=$unix->PIDOF_PATTERN("createuser.d");
			}
		}
	}
	
	$pid=XZARAFA_SERVER_PID();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: zarafa-server daemon success...$suffix\n";}
		return;
	}
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: zarafa-server daemon failed...$suffix\n";}
}


function procmap(){
	$f[]="# Saved By Artica on ".date("Y-m-d H:i:s");
	$f[]="# PR_EC_ENABLED_FEATURES";
	$f[]="0x67B3101E	=	zarafaEnabledFeatures";
	$f[]="";
	$f[]="# PR_EC_DISABLED_FEATURES";
	$f[]="0x67B4101E	=	zarafaDisabledFeatures";
	$f[]="";
	$f[]="# PR_EC_ARCHIVE_SERVERS";
	$f[]="0x67C4101E	=	zarafaUserArchiveServers";
	$f[]="";
	$f[]="# PR_EC_ARCHIVE_COUPLINGS";
	$f[]="0x67C5101E	=	zarafaUserArchiveCouplings";
	$f[]="";
	$f[]="# PR_EC_EXCHANGE_DN";
	$f[]="0x6788001E	=	";
	$f[]="";
	$f[]="# PR_BUSINESS_TELEPHONE_NUMBER";
	$f[]="0x3A08001E	=	telephoneNumber";
	$f[]="";
	$f[]="# PR_BUSINESS2_TELEPHONE_NUMBER";
	$f[]="0x3A1B101E	=	otherTelephone";
	$f[]="";
	$f[]="# PR_BUSINESS_FAX_NUMBER";
	$f[]="0x3A24001E	=	otherFacsimileTelephoneNumber";
	$f[]="";
	$f[]="# PR_MOBILE_TELEPHONE_NUMBER";
	$f[]="0x3A1C001E	=	mobile";
	$f[]="";
	$f[]="# PR_HOME_TELEPHONE_NUMBER";
	$f[]="0x3A09001E	=	homePhone";
	$f[]="";
	$f[]="# PR_HOME2_TELEPHONE_NUMBER";
	$f[]="0x3A2F101E	=	otherHomePhone";
	$f[]="";
	$f[]="# PR_PRIMARY_FAX_NUMBER";
	$f[]="0x3A23001E	=	facsimileTelephoneNumber";
	$f[]="";
	$f[]="# PR_PAGER_TELEPHONE_NUMBER";
	$f[]="0x3A21001E	=	pager";
	$f[]="";
	$f[]="# PR_COMMENT";
	$f[]="#0x3004001E	=	description";
	$f[]="";
	$f[]="# PR_DEPARTMENT_NAME (OpenLDAP: departmentNumber, ADS: department)";
	$f[]="0x3A18001E	=	department";
	$f[]="";
	$f[]="# PR_OFFICE_LOCATION";
	$f[]="0x3A19001E	=	physicalDeliveryOfficeName";
	$f[]="";
	$f[]="# PR_GIVEN_NAME";
	$f[]="0x3A06001E	=	givenName";
	$f[]="";
	$f[]="# PR_SURNAME";
	$f[]="0x3A11001E	=	sn";
	$f[]="";
	$f[]="# PR_CHILDRENS_NAMES";
	$f[]="0x3A58101E	=	o";
	$f[]="";
	$f[]="# PR_BUSINESS_ADDRESS_CITY";
	$f[]="0x3A27001E	=	l";
	$f[]="";
	$f[]="# PR_TITLE";
	$f[]="0x3A17001E	=	title";
	$f[]="";
	$f[]="# PR_USER_CERTIFICATE";
	$f[]="#0x3A220102	=	userCertificate";
	$f[]="";
	$f[]="# PR_INITIALS";
	$f[]="0x3A0A001E	=	initials";
	$f[]="";
	$f[]="# PR_LANGUAGE";
	$f[]="0x3A0C001E	=	preferredLanguage";
	$f[]="";
	$f[]="# PR_ORGANIZATIONAL_ID_NUMBER";
	$f[]="0x3A10001E	=	employeeNumber";
	$f[]="";
	$f[]="# PR_POSTAL_ADDRESS (business address if made into contact)";
	$f[]="0x3A15001E	=	postalAddress";
	$f[]="";
	$f[]="# PR_COMPANY_NAME (ADS only)";
	$f[]="0x3A16001E	=	company";
	$f[]="";
	$f[]="# PR_COUNTRY";
	$f[]="0x3A26001E	=	co";
	$f[]="";
	$f[]="# PR_STATE_OR_PROVINCE";
	$f[]="0x3A28001E	=	st";
	$f[]="";
	$f[]="# PR_STREET_ADDRESS (encoded in ads?)";
	$f[]="0x3A29001E	=	streetAddress";
	$f[]="";
	$f[]="# PR_POSTAL_CODE";
	$f[]="0x3A2A001E	=	postalCode";
	$f[]="";
	$f[]="# PR_POST_OFFICE_BOX";
	$f[]="0x3A2B001E	=	postOfficeBox";
	$f[]="";
	$f[]="# PR_ASSISTANT (should result in a DN to another user)";
	$f[]="0x3A30001E	=	assistant";
	$f[]="";
	$f[]="# PR_EMS_AB_WWW_HOME_PAGE";
	$f[]="0x8175101E	=	url";
	$f[]="";
	$f[]="# PR_BUSINESS_HOME_PAGE";
	$f[]="0x3A51001E	=	wWWHomePage";
	$f[]="";
	$f[]="# This enables GAB contact photos";
	$f[]="# Please note that the MAPI property has a 4K size limit";
	$f[]="# Larger images will not be shown";
	$f[]="# Recommended sizes are either 96x96 or 128x128";
	$f[]="# For AD, it is recommended to use thumbnailPhoto";
	$f[]="";
	$f[]="# PR_EMS_AB_THUMBNAIL_PHOTO";
	$f[]="0x8C9E0102	=	jpegPhoto";
	$f[]="";
	$f[]="# PR_EMS_AB_X509_CERT (aka PR_EMS_AB_TAGGED_X509_CERT)";
	$f[]="0x8C6A1102	=	userCertificate;binary";
	$f[]="";
	$f[]="# The following extra's can only be enabled when using ADS";
	$f[]="# or when you have OpenLDAP 2.4+ with the memberof overlay";
	$f[]="# enabled (see slapo-memberof(5) manpage).";
	$f[]="";
	$f[]="# PR_EMS_AB_IS_MEMBER_OF_DL";
	$f[]="#0x80081102	=	memberOf";
	$f[]="";
	$f[]="# PR_EMS_AB_REPORTS";
	$f[]="#0x800E1102	=	directReports";
	$f[]="";
	$f[]="# PR_MANAGER_NAME (should result in a DN to another user)";
	$f[]="#0x8005001E	=	manager";
	$f[]="";
	$f[]="# PR_EMS_AB_OWNER";
	$f[]="#0x800C001E	=	managedBy";
	$f[]="";
	$f[]="# PR_EMS_AB_OBJECT_GUID";
	$f[]="0x8C6D0102	=	EntryUUID\n";	
	@file_put_contents("/etc/zarafa/ldap.propmap.cfg", @implode("\n", $f));
}

?>