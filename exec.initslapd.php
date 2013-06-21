<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["FORCE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
if($argv[1]=="syslog-deb"){checkDebSyslog();die();}
if($argv[1]=="dnsmasq"){dnsmasq_init_debian();die();}
if($argv[1]=="nscd"){nscd_init_debian();die();}
if($argv[1]=="--rsyslogd-init"){rsyslogd_init();exit;}
if($argv[1]=="--start"){start_ldap();exit;}
if($argv[1]=="--stop"){stop_ldap();exit;}
if($argv[1]=="--restart"){restart_ldap();exit;}
if($argv[1]=="--spamass-milter"){buildscriptSpamass_milter();exit;}
if($argv[1]=="--mailarchive-perl"){mailarchive_perl();exit;}
if($argv[1]=="--freeradius"){buildscriptFreeRadius();exit;}
if($argv[1]=="--restart-www"){restart_artica_webservices();exit;}
if($argv[1]=="--pdns-recursor"){pdns_recursor();exit;}
if($argv[1]=="--ftp-proxy"){ftpproxy();exit;}
if($argv[1]=="--failover"){failover();exit;}
if($argv[1]=="--framework"){framework();exit;}
if($argv[1]=="--ufdbguard"){ufdbguard();exit;}
if($argv[1]=="--phppfm"){phppfm();exit;}
if($argv[1]=="--phppfm-fix"){phppfm_fix();exit;}
if($argv[1]=="--phppfm-restart-back"){phppfm_restartback();exit;}
if($argv[1]=="--artica-web"){artica_webconsole();exit;}
if($argv[1]=="--memcache"){memcached();exit;}
if($argv[1]=="--nginx"){nginx();exit;}
if($argv[1]=="--dhcpd"){dhcpd();exit;}
if($argv[1]=="--haarp"){haarp();exit;}
if($argv[1]=="--mysql"){mysqlInit();exit;}
if($argv[1]=="--ubuntu"){CleanUbuntu();exit;}


	
	$unix=new unix();
	$PID_FILE="/etc/artica-postfix/pids/".basename(__FILE__);
	$PID_TIME="/etc/artica-postfix/pids/".basename(__FILE__).".time";
	
	$timeF=$unix->file_time_min($PID_TIME);
	if(!$GLOBALS["FORCE"]){
		if($timeF<3){
				echo "slapd: [INFO] Executed since {$timeF}Mn die (use --force to bypass)..\n";
				die();
			}
	}
	
	@unlink($PID_TIME);
	@file_put_contents($PID_TIME, time());
	$oldpid=$unix->get_pid_from_file($PID_FILE);
	if($unix->process_exists($oldpid)){echo "slapd: [INFO] Already executed pid $oldpid\n";die();}
	@file_put_contents($PID_FILE, getmypid());
	buildscript();
	MONIT();
	checkDebSyslog();
	dnsmasq_init_debian();
	nscd_init_debian();
	wsgate_init_debian();
	buildscriptSpamass_milter();
	buildscriptLoopDisk();
	buildscriptFreeRadius();
	pdns_recursor();
	ifup();
	ftpproxy();
	failover();
	framework();
	ufdbguard();
	phppfm();
	apache();
	artica_webconsole();
	memcached();
	nginx();
	dhcpd();
	cicap();
	haarp();
	mysqlInit();
	CleanUbuntu();
	UpstartJob();
	
function UpstartJob(){	
	$restore=false;
	if(!is_file("/lib/init/upstart-job")){return;}
	$f=explode("\n",@file_get_contents("/lib/init/upstart-job"));
	while (list ($num, $line) = each ($f)){
		if(preg_match("#exec\.mysql\.start\.php#", $line)){
			$restore=true;
			break;
		}
	}
	
	
if($restore){
	@copy("/usr/share/artica-postfix/bin/install/upstart-job", "/lib/init/upstart-job");
	@chmod("/lib/init/upstart-job", 0755);
}
	
	
}

function restart_ldap(){
	stop_ldap(true);
	start_ldap(true);
}

function start_ldap($aspid=false){
	$sock=new sockets();
	$ldaps=array();
	$unix=new unix();
	$kill=$unix->find_program("kill");
	
	$MYPID_FILE="/etc/artica-postfix/pids/start_ldap.pid";
	if(!$aspid){
		$oldpid=$unix->get_pid_from_file($MYPID_FILE);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$pidtime=$unix->PROCCESS_TIME_MIN($oldpid);
			echo "slapd: [INFO] Artica task already running pid $oldpid since {$pidtime}mn\n";
			if($pidtime>10){
				echo "slapd: [INFO] Killing this Artica task...\n";
				shell_exec("$kill -9 $oldpid 2>&1");
			}else{
				die();
			}
		}
		
		$MYPID_FILE_TIME=$unix->file_time_min($MYPID_FILE);
		if(!$GLOBALS["FORCE"]){
			if($MYPID_FILE_TIME<1){
				echo "slapd: [INFO] Task must be executed only each 1mn (use --force to by pass)\n";
				die();
			}
		}
		
		@unlink($MYPID_FILE);
		@file_put_contents($MYPID_FILE, getmypid());
	}
	
	
	$slapd=$unix->find_program("slapd");
	$SLAPD_PID_FILE=$unix->SLAPD_PID_PATH();
	
	$oldpid=$unix->get_pid_from_file($SLAPD_PID_FILE);
	if($unix->process_exists($oldpid)){
		$pidtime=$unix->PROCCESS_TIME_MIN($oldpid);
		echo "slapd: [INFO] slapd already running pid $oldpid since {$pidtime}mn\n";
		return;
	}
	
	$oldpid=$unix->PIDOF_PATTERN($slapd);
	echo "slapd: [INFO] detecting presence of `$slapd`:$oldpid...\n";
	if($unix->process_exists($oldpid)){
		$pidtime=$unix->PROCCESS_TIME_MIN($oldpid);
		echo "slapd: [INFO] slapd already running pid $oldpid since {$pidtime}mn\n";
		return;
	}	
	
	echo "slapd: [INFO] slapd loading required values...\n";
	if(!is_file($slapd)){if(is_file('/usr/lib/openldap/slapd')){$slapd='/usr/lib/openldap/slapd';}}
	$OpenLDAPLogLevel=$sock->GET_INFO("OpenLDAPLogLevel");
	$OpenLDAPDisableSSL=$sock->GET_INFO("OpenLDAPDisableSSL");
	$EnableNonEncryptedLdapSession=$sock->GET_INFO("EnableNonEncryptedLdapSession");
	$EnableipV6=$sock->GET_INFO("EnableipV6");
	if(!is_numeric($EnableipV6)){$EnableipV6=0;}	
	if(!is_numeric($EnableNonEncryptedLdapSession)){$EnableNonEncryptedLdapSession=1;}
	$phpldapadmin=null;
	if(!is_numeric($OpenLDAPDisableSSL)){$OpenLDAPDisableSSL=0;}
	$ZARAFA_INSTALLED=0;
	if($GLOBALS["VERBOSE"]){echo "users=new usersMenus();\n";}
	$users=new usersMenus();
	if($GLOBALS["VERBOSE"]){echo "users=new usersMenus() done...;\n";}
	if(!is_dir("/var/lib/ldap")){@mkdir("/var/lib/ldap",0755,true);}
	if(!is_dir("/var/run/slapd")){@mkdir("/var/run/slapd",0755,true);}
	if(!is_numeric($OpenLDAPLogLevel)){$OpenLDAPLogLevel=0;}
	if($OpenLDAPLogLevel<>0){$OpenLDAPLogLevelCmdline=" -d $OpenLDAPLogLevel";}
	
	if(!$unix->IS_IPADDR_EXISTS("127.0.0.1")){
		shell_exec($unix->find_program("ifconfig")." lo 127.0.0.1 netmask 255.0.0.0 up >/dev/null 2>&1");
	}

	$ldap[]="ldap://127.0.0.1:389/";
	if(is_file("/etc/artica-postfix/settings/Daemons/LdapListenIPAddr")){
		$LdapListenIPAddr=explode("\n",@file_get_contents("/etc/artica-postfix/settings/Daemons/LdapListenIPAddr"));
		while (list ($num, $ipaddr) = each ($LdapListenIPAddr)){
			$ipaddr=trim($ipaddr);
			if($ipaddr==null){continue;}
			echo "slapd: [INFO] slapd listen `$ipaddr`n";
			if($EnableNonEncryptedLdapSession==0){$ldaps[]="ldaps://$ipaddr/";}
			$ldap[]="ldap://$ipaddr:389/";
		}
	}

	if(count($ldaps)>0){$SLAPD_SERVICESSSL=" ".@implode(" ", $ldaps);}
	
	$SLAPD_SERVICES=@implode(" ", $ldap).$SLAPD_SERVICESSSL;
	if($users->ZARAFA_INSTALLED){$ZARAFA_INSTALLED=1;}
	$DB_RECOVER_BIN=$unix->LOCATE_DB_RECOVER();
	$DB_ARCHIVE_BIN=$unix->LOCATE_DB_ARCHIVE();
	$LDAP_SCHEMA_PATH=$unix->LDAP_SCHEMA_PATH();
	$rm=$unix->find_program("rm");
	$SLAPD_CONF=$unix->SLAPD_CONF_PATH();
	
	$php5=$unix->LOCATE_PHP5_BIN();
	$tar=$unix->find_program("tar");
	$pidofbin=$unix->find_program("pidof");
	$ulimit=$unix->find_program("ulimit");
	$nohup=$unix->find_program("nohup");
	$mebin=__FILE__;
	$suffix=@trim(@file_get_contents("/etc/artica-postfix/ldap_settings/suffix"));
	
	
	shell_exec("$nohup /usr/share/artica-postfix/exec.virtuals-ip.php --resolvconf >/dev/null 2>&1 &");
	
	echo "slapd: [INFO] slapd `$slapd`\n";
	echo "slapd: [INFO] db_recover `$DB_RECOVER_BIN`\n";
	echo "slapd: [INFO] db_archive `$DB_ARCHIVE_BIN`\n";
	echo "slapd: [INFO] config `$SLAPD_CONF`\n";
	echo "slapd: [INFO] pid `$SLAPD_PID_FILE`\n";
	echo "slapd: [INFO] services `$SLAPD_SERVICES`\n";
	echo "slapd: [INFO] pidof `$pidofbin`\n";
	if($EnableipV6==0){
		echo "slapd: [INFO] ipv4 only...\n";
		$v4=" -4";
	}
	
	
	$kernel_tuning="$php5 ".dirname(__FILE__)."/exec.kernel-tuning.php >/dev/null 2>&1";
	if($GLOBALS["VERBOSE"]){echo "-> ARRAY;\n";}
	
	$shemas[]="core.schema";
	$shemas[]="cosine.schema";
	$shemas[]="mod_vhost_ldap.schema";
	$shemas[]="nis.schema";
	$shemas[]="inetorgperson.schema";
	$shemas[]="evolutionperson.schema";
	$shemas[]="postfix.schema";
	$shemas[]="dhcp.schema";
	$shemas[]="samba.schema";
	$shemas[]="ISPEnv.schema";
	$shemas[]="mozilla-thunderbird.schema";
	$shemas[]="officeperson.schema";
	$shemas[]="pureftpd.schema";
	$shemas[]="joomla.schema";
	$shemas[]="autofs.schema";
	$shemas[]="dnsdomain2.schema";
	$shemas[]="zarafa.schema";
	 
	while (list ($num, $file) = each ($shemas) ){
		if(is_file("/usr/share/artica-postfix/bin/install/$file")){
			if(is_file("$LDAP_SCHEMA_PATH/$file")){@unlink("$LDAP_SCHEMA_PATH/$file");}
			@copy("/usr/share/artica-postfix/bin/install/$file", "$LDAP_SCHEMA_PATH/$file");
			echo "slapd: [INFO] installing `$file` schema\n";
			$unix->chmod_func(0777,"$LDAP_SCHEMA_PATH/$file");
		}
	}
		 

	echo "slapd: [INFO] please wait, Tuning the kernel... \n";
	shell_exec($kernel_tuning);
	if(file_exists($ulimit)){
		shell_exec("$ulimit -HSd unlimited");
	}
	
	
	if(is_dir("/usr/share/phpldapadmin/config")){
		$phpldapadmin="$php5 ".dirname(__FILE__)."/exec.phpldapadmin.php --build >/dev/null 2>&1";
		echo "slapd: [INFO] please wait, configuring PHPLdapAdminservice... \n";
		shell_exec($phpldapadmin);
	}	
	
	echo "slapd: [INFO] please wait, configuring the daemon...\n";
	shell_exec("/usr/share/artica-postfix/bin/artica-install --slapdconf");
	
	echo "slapd: [INFO] please wait, building the start script...\n";
	buildscript();
		
	echo "slapd: [INFO] please wait, Launching the daemon...\n";
	$cdmline="$slapd$v4 -h \"$SLAPD_SERVICES\" -f $SLAPD_CONF -u root -g root -l local4$OpenLDAPLogLevelCmdline";
	shell_exec($cdmline);
	sleep(1);
	
	for($i=0;$i<5;$i++){
		$oldpid=$unix->get_pid_from_file($SLAPD_PID_FILE);
		if($unix->process_exists($oldpid)){
			$pidtime=$unix->PROCCESS_TIME_MIN($oldpid);
			echo "slapd: [INFO] slapd success Running pid $oldpid\n";
			if($users->ZARAFA_INSTALLED){start_zarafa();}
			return;
		}
			
		$oldpid=$unix->PIDOF($slapd);
		if($unix->process_exists($oldpid)){
			$pidtime=$unix->PROCCESS_TIME_MIN($oldpid);
			echo "slapd: [INFO] slapd success Running pid $oldpid\n";
			if($users->ZARAFA_INSTALLED){start_zarafa();}
			return;
		}
		echo "slapd: [INFO] please wait, waiting service to start...\n";
		sleep(1);
				
	}
	
	echo "slapd: [ERR ] Failed to start the service with `$cdmline`\n";
	
}


function stop_ldap($aspid=false){
	$sock=new sockets();
	$users=new usersMenus();
	$ldaps=array();
	$unix=new unix();
	$kill=$unix->find_program("kill");
	$slapd=$unix->find_program("slapd");
	$pgrep=$unix->find_program("pgrep");
	$SLAPD_PID_FILE=$unix->SLAPD_PID_PATH();
	
	if($users->ZARAFA_INSTALLED){stop_zarafa();}
	if(!$aspid){
		$oldpid=$unix->get_pid_from_file($SLAPD_PID_FILE);
		if($unix->process_exists($oldpid)){
			echo "slapd: [INFO] slapd shutdown ldap server PID:$oldpid...\n";
			shell_exec("$kill $oldpid >/dev/null 2>&1");
		}else{
			$oldpid=$unix->PIDOF($slapd);
			if($unix->process_exists($oldpid)){
				echo "slapd: [INFO] slapd shutdown ldap server PID:$oldpid...\n";
				shell_exec("$kill $oldpid >/dev/null 2>&1");
			}
		}
	}
	for($i=0;$i<10;$i++){
		$oldpid=$unix->get_pid_from_file($SLAPD_PID_FILE);
		if($unix->process_exists($oldpid)){
			echo "slapd: [INFO] slapd waiting the server to stop PID:$oldpid...\n";
			sleep(1);
			continue;
		}
		
		$oldpid=$unix->PIDOF($slapd);
		if($unix->process_exists($oldpid)){
			echo "slapd: [INFO] slapd waiting the server to stop PID:$oldpid...\n";
			sleep(1);
			continue;
		}		
		
	}
	
	$oldpid=$unix->get_pid_from_file($SLAPD_PID_FILE);
	if($unix->process_exists($oldpid)){
		echo "slapd: [INFO] slapd PID:$oldpid still exists, kill it...\n";
		shell_exec("$kill -9 $oldpid >/dev/null 2>&1");
	}
	
	$oldpid=$unix->get_pid_from_file($SLAPD_PID_FILE);
	if($unix->process_exists($oldpid)){
		echo "slapd: [INFO] slapd PID:$oldpid still exists, start the force kill procedure...\n";
	}	
	
	$oldpid=$unix->PIDOF($slapd);
	if($unix->process_exists($oldpid)){
		echo "slapd: [INFO] slapd PID:$oldpid still exists, kill it...\n";
		shell_exec("$kill -9 $oldpid >/dev/null 2>&1");
		return;
	}

	exec("$pgrep -l -f $slapd 2>&1",$results);
	while (list ($num, $line) = each ($results) ){
		if(preg_match("#pgrep#", $line)){continue;}
		if(preg_match("^([0-9]+)\s+", $line,$re)){
			echo "slapd: [INFO] slapd PID:{$re[1]} still exists, kill it\n";
			shell_exec("$kill -9 {$re[1]} >/dev/null 2>&1");
		}
		
	}
	
	
	
	echo "slapd: [INFO] slapd stopped, success...\n";
	
}

function start_zarafa(){
	shell_exec("/etc/init.d/zarafa-server start");
}
function stop_zarafa(){
	shell_exec("/etc/init.d/zarafa-server stop");
}

function buildscriptFreeRadius(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$daemonbin=$unix->find_program("freeradius");
	if(!is_file($daemonbin)){return;}
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          freeradius";
	$f[]="# Required-Start:    \$local_fs \$remote_fs \$syslog \$named \$network \$time";
	$f[]="# Required-Stop:     \$local_fs \$remote_fs \$syslog \$named \$network";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: radius daemon";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Extensible, configurable radius daemon";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="   $php ".dirname(__FILE__)."/exec.freeradius.php --start \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="   $php ".dirname(__FILE__)."/exec.freeradius.php --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="   $php ".dirname(__FILE__)."/exec.freeradius.php --stop \$2 \$3";
	$f[]="   $php ".dirname(__FILE__)."/exec.freeradius.php --start \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="   $php ".dirname(__FILE__)."/exec.freeradius.php --reload \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";	
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";

	$INITD_PATH="/etc/init.d/freeradius";
	echo "freeradius: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 2345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}

}

function ifup(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();	
	$INITD_PATH="/etc/init.d/artica-ifup";
	$f[]="#!/bin/sh -e";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          artica-ifup";
	$f[]="# Required-Start:    \$local_fs";
	$f[]="# Required-Stop:     \$local_fs";
	$f[]="# Should-Start:      ";
	$f[]="# Should-Stop:       ";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: start and stop the network";
	$f[]="# Description:       Artica ifup service";
	$f[]="### END INIT INFO";
	$f[]="";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="  $php /usr/share/artica-postfix/exec.virtuals-ip.php";
	$f[]="	/sbin/ifup lo >/dev/null 2>&1";
	$f[]="	/sbin/ifup eth0 >/dev/null 2>&1";
	$f[]=" ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    /sbin/ifdown eth0 >/dev/null 2>&1";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="	 /sbin/ifdown eth0 >/dev/null 2>&1";
	$f[]="	 $php /usr/share/artica-postfix/exec.virtuals-ip.php";
	$f[]="   /sbin/ifup eth0 >/dev/null 2>&1";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|}\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0";
	$f[]="";	
	echo "artica-ifup: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);
	
	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 2345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}	
}

function pdns_recursor(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$daemonbin=$unix->find_program("pdns_recursor");
	$daemonbinLog=basename($daemonbin);
	
	if(!is_file($daemonbin)){return;}
	$INITD_PATH="/etc/init.d/pdns-recursor";
	$sock=new sockets();
	$DisablePowerDnsManagement=$sock->GET_INFO("DisablePowerDnsManagement");
	if(!is_numeric($DisablePowerDnsManagement)){$DisablePowerDnsManagement=0;}	
	if($DisablePowerDnsManagement==1){return;}
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          pdns_recursor";
	$f[]="# Required-Start:    \$local_fs \$syslog \$network";
	$f[]="# Required-Stop:     \$local_fs \$syslog \$network";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: pdns_recursor";
	$f[]="# chkconfig: - 80 75";
	$f[]="# description: pdns_recursor is a versatile high performance recursing nameserver";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php /usr/share/artica-postfix/exec.pdns.php --start-recursor \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/exec.pdns.php --stop-recursor \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/exec.pdns.php --stop-recursor \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/exec.pdns.php --start-recursor \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";

	
	echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 2345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}

}


function ftpproxy(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$daemonbin=$unix->find_program("ftp-proxy");
	$daemonbinLog=basename($daemonbin);
	$INITD_PATH="/etc/init.d/ftp-proxy";
	$php5script="exec.ftpproxy.php";
	if(!is_file($daemonbin)){return;}
	
	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         $daemonbinLog";
	$f[]="# Required-Start:    \$local_fs \$syslog \$network";
	$f[]="# Required-Stop:     \$local_fs \$syslog \$network";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: $daemonbinLog";
	$f[]="# chkconfig: - 80 75";
	$f[]="# description: $daemonbinLog";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	
	
	echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);
	
	if(is_file('/usr/sbin/update-rc.d')){
	shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
			shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 2345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	
	
}
function apacheoff(){
	
	$unix=new unix();
	$debianbin=$unix->find_program("update-rc.d");
	$redhatbin=$unix->find_program("chkconfig");
	$service="apache2";
	if(is_file("/etc/init.d/apache2")){$service="apache2";}
	if(is_file("/etc/init.d/apache")){$service="apache";}
	if(is_file("/etc/init.d/httpd")){$service="httpd";}
	if(is_file($debianbin)){shell_exec("$debianbin -f $service defaults >/dev/null 2>&1");}
	if(is_file($redhatbin)){shell_exec("$redhatbin --add $service >/dev/null 2>&1");}	
	
}

function apache(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/artica-apache";
	$php5script="exec.freeweb.php";
	$daemonbinLog="Artica Apache init";
	apacheoff();

	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         artica-apache";
	$f[]="# Required-Start:    \$local_fs \$syslog \$network";
	$f[]="# Required-Stop:     \$local_fs \$syslog \$network";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: $daemonbinLog";
	$f[]="# chkconfig: - 80 75";
	$f[]="# description: $daemonbinLog";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";		
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";


	echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 2345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}


}


function framework(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/artica-framework";
	$php5script="exec.framework.php";
	$daemonbinLog="Artica Framework";


	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         artica-framework";
	$f[]="# Required-Start:    \$local_fs \$syslog \$network";
	$f[]="# Required-Stop:     \$local_fs \$syslog \$network";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: $daemonbinLog";
	$f[]="# chkconfig: - 80 75";
	$f[]="# description: $daemonbinLog";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";


	echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 2345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}


}

function failover(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/artica-failover";
	$php5script="exec.virtuals-ip.php";
	$daemonbinLog="Artica Failover";
	
	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         ".basename($INITD_PATH);
	$f[]="# Required-Start:    \$local_fs \$syslog \$network";
	$f[]="# Required-Stop:     \$local_fs \$syslog \$network";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: $daemonbinLog";
	$f[]="# chkconfig: - 80 75";
	$f[]="# description: $daemonbinLog";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --ucarp-start \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --ucarp-stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --ucarp-stop \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/$php5script --ucarp-start \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	
	
	echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);
	
	if(is_file('/usr/sbin/update-rc.d')){
			shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
			shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 2345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
		
	
}

function phppfm_fix(){
	$unix=new unix();
	$pidF="/etc/artica-postfix/pids/".__FUNCTION__.".pid";
	$oldpid=$unix->get_pid_from_file($pidF);
	if($unix->process_exists($oldpid,basename(__FILE__))){return;}
	@file_put_contents($pidF, getmypid());
	phppfm();
	shell_exec("/etc/init.d/php5-fpm start");
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup /etc/init.d/artica-framework restart >/dev/null 2>&1 &");
	shell_exec("$nohup /etc/init.d/artica-postfix restart artica-status >/dev/null 2>&1 &");
	
}
function phppfm_restartback(){
	phppfm();
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	shell_exec("/etc/init.d/php5-fpm restart");
	shell_exec("$nohup /etc/init.d/artica-framework restart >/dev/null 2>&1 &");
}

function LIGHTTPD_INITD(){
	$f[]="/etc/init.d/lighttpd";
	$f[]="/usr/local/etc/rc.d/lighttpd";
	$f[]="/etc/rc.d/lighttpd";
	while (list ($pid, $line) = each ($f) ){
		if(is_file($line)){return $line;}
	}	
}
//##############################################################################


function artica_webconsole(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/artica-webconsole";
	$php5script="exec.lighttpd.php";
	$daemonbinLog="Artica SSL Web console";
	$daemon_path=$unix->APACHE_LOCATE_PHP_FPM();

	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         artica-webconsole";
	$f[]="# Required-Start:    \$local_fs \$syslog";
	$f[]="# Required-Stop:     \$local_fs \$syslog";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: $daemonbinLog";
	$f[]="# chkconfig: - 80 75";
	$f[]="# description: $daemonbinLog";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";


	echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	$LIGHTTPD_INITD=LIGHTTPD_INITD();
	echo "$daemonbinLog: [INFO] Writing $LIGHTTPD_INITD with new config\n";
	@file_put_contents($LIGHTTPD_INITD, @implode("\n", $f));

	@chmod($INITD_PATH,0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
		if(is_file($LIGHTTPD_INITD)){shell_exec("/usr/sbin/update-rc.d -f ".basename($LIGHTTPD_INITD)." remove");}
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 2345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}


}


function phppfm(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/php5-fpm";
	$php5script="exec.php-fpm.php";
	$daemonbinLog="PHP5 FastCGI Process Manager Daemon";
	$daemon_path=$unix->APACHE_LOCATE_PHP_FPM();
	
	$restart=false;
	if(is_file($daemon_path)){
		if(!is_file("/etc/artica-postfix/php-fpm.first.restart")){$restart=true;}
	}

	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         php5-fpm";
	$f[]="# Required-Start:    \$local_fs \$syslog";
	$f[]="# Required-Stop:     \$local_fs \$syslog";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: $daemonbinLog";
	$f[]="# chkconfig: - 80 75";
	$f[]="# description: $daemonbinLog";
	$f[]="### END INIT INFO";
	$f[]="DAEMON=$daemon_path";
	$f[]="[ -x \"\$DAEMON\" ] || exit 0";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";


	echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 2345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	
	if($restart){
		if(is_file("/etc/init.d/artica-webconsole")){
			@file_put_contents("/etc/artica-postfix/php-fpm.first.restart", time());
			shell_exec("/etc/init.d/php5-fpm start");
			$nohup=$unix->find_program("nohup");
			shell_exec("$nohup /etc/init.d/artica-framework restart >/dev/null 2>&1 &");
			shell_exec("$nohup /etc/init.d/artica-postfix restart artica-status >/dev/null 2>&1 &");
			shell_exec("$nohup /etc/init.d/artica-webconsole restart >/dev/null 2>&1 &");
		}
	}
}
function nginx(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/nginx";
	$php5script="exec.nginx.php";
	$daemonbinLog="nginx For Artica";
	$daemon_path=$unix->find_program("nginx");
	$restart=false;
	if(!is_file("/etc/artica-postfix/ngnix.first.restart")){$restart=true;}
	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         artica-nginx";
	$f[]="# Required-Start:    \$local_fs \$syslog";
	$f[]="# Required-Stop:     \$local_fs \$syslog";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: $daemonbinLog";
	$f[]="# chkconfig: - 80 75";
	$f[]="# description: $daemonbinLog";
	$f[]="### END INIT INFO";
	
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reconfigure)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
	$f[]="    ;;";
	$f[]="";	
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	
	
	echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);
	
	if(is_file('/usr/sbin/update-rc.d')){
	shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
			shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 2345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	
	if($restart){
		@file_put_contents("/etc/artica-postfix/ngnix.first.restart", time());
		shell_exec("/etc/init.d/artica-apache restart");
		shell_exec("$INITD_PATH restart");
	}
	
	
}
function mysqlInit(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/mysql";
	$php5script="exec.mysql.start.php";
	$daemonbinLog="MySQL For Artica";
	$daemon_path=$unix->find_program("mysqld");
	

	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         mysql";
	$f[]="# Required-Start:    \$local_fs \$syslog";
	$f[]="# Required-Stop:     \$local_fs \$syslog";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: $daemonbinLog";
	$f[]="# chkconfig: - 80 75";
	$f[]="# description: $daemonbinLog";
	$f[]="### END INIT INFO";

	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reconfigure)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";


	echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 2345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
}
function haarp(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/haarp";
	$php5script="exec.haarp.php";
	$daemonbinLog="Haarp For Artica";
	
	
	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         artica-haarp";
	$f[]="# Required-Start:    \$local_fs \$syslog";
	$f[]="# Required-Stop:     \$local_fs \$syslog";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: $daemonbinLog";
	$f[]="# chkconfig: - 80 75";
	$f[]="# description: $daemonbinLog";
	$f[]="### END INIT INFO";
	
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reconfigure)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	
	
	echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);
	
	if(is_file('/usr/sbin/update-rc.d')){
			shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}
	
		if(is_file('/sbin/chkconfig')){
				shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 2345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
		
	
}


function cicap(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/c-icap";
	$php5script="exec.c-icap.php";
	$daemonbinLog="C-ICAP For Artica";
	$daemon_path=$unix->find_program("nginx");

	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         artica-cicap";
	$f[]="# Required-Start:    \$local_fs \$syslog";
	$f[]="# Required-Stop:     \$local_fs \$syslog";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: $daemonbinLog";
	$f[]="# chkconfig: - 80 75";
	$f[]="# description: $daemonbinLog";
	$f[]="### END INIT INFO";
	
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reconfigure)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	
	
	echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);
	
	if(is_file('/usr/sbin/update-rc.d')){
			shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
			shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 2345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	
}

function dhcpd(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/isc-dhcp-server";
	$php5script="exec.dhcpd.compile.php";
	$daemonbinLog="Dynamic Host Configuration Protocol Server";
	$daemon_path=$unix->DHCPD_BIN_PATH();
	

	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         isc-dhcp-server";
	$f[]="# Required-Start:    \$remote_fs \$network \$syslog";
	$f[]="# Required-Stop:     \$remote_fs \$network \$syslog";
	$f[]="# Should-Start:	   \$local_fs slapd";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: $daemonbinLog";
	$f[]="# chkconfig: - 80 75";
	$f[]="# description: $daemonbinLog";
	$f[]="### END INIT INFO";
	$f[]="RSYSLOGD=rsyslogd";
	$f[]="DAEMON_BIN=$daemon_path";
	$f[]="PATH=/sbin:/bin:/usr/sbin:/usr/bin";
	$f[]="";
	$f[]="";
	$f[]="# Exit if the package is not installed";
	$f[]="[ -x \"\$DAEMON_BIN\" ] || exit 0";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reconfigure)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";

	
	if(is_file('/etc/init.d/dhcpd')){@unlink('/etc/init.d/dhcpd');}
	if(is_file('/etc/init.d/dhcp3-server')){@unlink('/etc/init.d/dhcp3-server');}
	

	echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 2345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}

}

function CleanUbuntu(){
	$unix=new unix();
	if(is_file("/etc/default/whoopsie")){
		echo "Ubuntu: [INFO] Disabling whoopsie\n";
		@file_put_contents("/etc/default/whoopsie","[General]\nreport_crashes=false\n");
		shell_exec("/usr/bin/killall whoopsie");
		shell_exec("/etc/init.d/whoopsie stop");
		if(is_file('/usr/sbin/update-rc.d')){
			shell_exec("/usr/sbin/update-rc.d -f whoopsie remove >/dev/null 2>&1");
		}
	}
	if(is_file("/usr/sbin/console-kit-daemon")){
		echo "Ubuntu: [INFO] Disabling console-kit-daemon\n";
		shell_exec("/bin/mv /usr/sbin/console-kit-daemon /usr/sbin/console-kit-daemon.bkup");
		shell_exec("/bin/cp /bin/true /usr/sbin/console-kit-daemon");
		
	}
	
	if(is_file("/usr/sbin/bluetoothd")){
		echo "Ubuntu: [INFO] Disabling bluetoothd\n";
		shell_exec("/usr/bin/killall bluetoothd");
		shell_exec("/etc/init.d/bluetooth stop");
		if(is_file('/usr/sbin/update-rc.d')){
			shell_exec("/usr/sbin/update-rc.d -f bluetooth remove >/dev/null 2>&1");
		}
		
	}
	
	if(is_file("/etc/default/avahi-daemon")){
		echo "Ubuntu: [INFO] Disabling avahi dameon\n";
		if($unix->LINUX_CODE_NAME()=="UBUNTU"){
			@file_put_contents("/etc/default/avahi-daemon","AVAHI_DAEMON_START = 0\nAVAHI_DAEMON_DETECT_LOCAL=1\n");
		}
		if(is_file("/etc/init.d/avahi-daemon")){
			shell_exec("/etc/init.d/avahi-daemon stop");
			if(is_file('/usr/sbin/update-rc.d')){
				shell_exec("/usr/sbin/update-rc.d -f avahi-daemon remove >/dev/null 2>&1");
				shell_exec("kill -9 `pidof avahi-daemon`");
			}
		}
	}
	
}


function memcached(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/artica-memcache";
	$php5script="exec.memcached.php";
	$daemonbinLog="Memcached service";
	$daemon_path=$unix->find_program("memcached");
	$echo=$unix->find_program("echo");
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         artica-memcache";
	$f[]="# Required-Start:    \$local_fs \$syslog";
	$f[]="# Required-Stop:     \$local_fs \$syslog";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: $daemonbinLog";
	$f[]="# chkconfig: - 80 75";
	$f[]="# description: $daemonbinLog";
	$f[]="### END INIT INFO";

	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]=" 	  $echo \"Starting......: [INIT]: $daemonbinLog - Please wait\"";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]=" 	  $echo \"Stopping......: [INIT]: $daemonbinLog - Please wait\"";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]=" 	  $echo \"Restarting....: [INIT]: $daemonbinLog - Please wait\"";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";


	echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 2345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}


}
function buildscriptSpamass_milter(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$daemonbin=$unix->find_program("spamass-milter");
	if(!is_file($daemonbin)){return;}
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          spamass-milter";
	$f[]="# Required-Start:    \$local_fs \$remote_fs \$syslog \$named \$network \$time";
	$f[]="# Required-Stop:     \$local_fs \$remote_fs \$syslog \$named \$network";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Calls spamassassin to allow filtering out";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Spamassassin Milter Edition";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    /etc/init.d/artica-postfix start spamd \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    /etc/init.d/artica-postfix stop spamd \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    /etc/init.d/artica-postfix stop spamd \$2 \$3";
	$f[]="    /etc/init.d/artica-postfix start spamd \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";

	$INITD_PATH="/etc/init.d/spamass-milter";
	echo "spamassin-milter: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 2345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}

}

function mailarchive_perl(){
$unix=new unix();
$php=$unix->LOCATE_PHP5_BIN();
$f[]="#!/bin/sh";
$f[]="### BEGIN INIT INFO";
$f[]="# Provides:          mailarchive-perl";
$f[]="# Required-Start:    \$local_fs \$remote_fs \$syslog \$named \$network \$time";
$f[]="# Required-Stop:     \$local_fs \$remote_fs \$syslog \$named \$network";
$f[]="# Should-Start:";
$f[]="# Should-Stop:";
$f[]="# Default-Start:     2 3 4 5";
$f[]="# Default-Stop:      0 1 6";
$f[]="# Short-Description: mailarchive-perl";
$f[]="# chkconfig: 2345 11 89";
$f[]="# description: mailarchive-perl";
$f[]="### END INIT INFO";
$f[]="case \"\$1\" in";
$f[]=" start)";
$f[]="    $php /usr/share/artica-postfix/exec.mailarchiver.php --start \$2 \$3";
$f[]="    ;;";
$f[]="";
$f[]="  stop)";
$f[]="    $php /usr/share/artica-postfix/exec.mailarchiver.php --stop \$2 \$3";
$f[]="    ;;";
$f[]="";
$f[]=" restart)";
$f[]="    $php /usr/share/artica-postfix/exec.mailarchiver.php --stop \$2 \$3";
$f[]="    $php /usr/share/artica-postfix/exec.mailarchiver.php --start \$2 \$3";
$f[]="    ;;";
$f[]="";
$f[]="  *)";
$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
$f[]="    exit 1";
$f[]="    ;;";
$f[]="esac";
$f[]="exit 0\n";

$INITD_PATH="/etc/init.d/mailarchive-perl";
echo "mailarchive-perl: [INFO] Writing $INITD_PATH with new config\n";
@unlink($INITD_PATH);@file_put_contents($INITD_PATH, @implode("\n", $f));

@chmod($INITD_PATH,0755);

if(is_file('/usr/sbin/update-rc.d')){
shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
}

if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 2345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}

}





function buildscriptLoopDisk(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();

	
	$phpscr=dirname(__FILE__)."/exec.loopdisks.php";
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          Artica-loopdisk";
	$f[]="# Required-Start:    \$local_fs \$remote_fs \$syslog \$named \$network \$time";
	$f[]="# Required-Stop:     \$local_fs \$remote_fs \$syslog \$named \$network";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Calls spamassassin to allow filtering out";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: reconfigure loop disks after reboot";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php $phpscr \$2 \$3";
	$f[]="	  /etc/init.d/autofs reload";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";

	$INITD_PATH="/etc/init.d/artica-loopd";
	echo "artica-oopd: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);@file_put_contents($INITD_PATH, @implode("\n", $f));

	@chmod($INITD_PATH,0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 2345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
}



function buildscript(){
$unix=new unix();
$php=$unix->LOCATE_PHP5_BIN();

$f[]="#!/bin/sh";
$f[]="### BEGIN INIT INFO";
$f[]="# Provides:          slapd";
$f[]="# Required-Start:    \$local_fs \$remote_fs \$syslog \$named \$network \$time";
$f[]="# Required-Stop:     \$local_fs \$remote_fs \$syslog \$named \$network";
$f[]="# Should-Start:";
$f[]="# Should-Stop:";
$f[]="# Default-Start:     2 3 4 5";
$f[]="# Default-Stop:      0 1 6";
$f[]="# Short-Description: Start OpenLDAP server";
$f[]="# chkconfig: 2345 11 89";
$f[]="# description: OpenLDAP Daemon";
$f[]="### END INIT INFO";
$f[]="case \"\$1\" in";
$f[]=" start)";
$f[]="    $php ". __FILE__." --start --byinitd \$2 \$3";
$f[]="	 exit 0";
$f[]="    ;;";
$f[]="";
$f[]="  stop)";
$f[]="    $php ". __FILE__." --stop --byinitd --force \$2 \$3";
$f[]="	 exit 0";
$f[]="    ;;";
$f[]="";
$f[]=" restart)";
$f[]="    $php ". __FILE__." --restart --byinitd --force \$2 \$3";
$f[]="	 exit 0";
$f[]="    ;;";
$f[]="";
$f[]="  *)";
$f[]="    echo \"Usage: \$0 {start|stop|restart} {ldap|} (+ 'debug' for more infos)\"";
$f[]="    exit 1";
$f[]="    ;;";
$f[]="esac";
$f[]="exit 0\n";

$INITD_PATH=$unix->SLAPD_INITD_PATH();
echo "slapd: [INFO] Writing $INITD_PATH with new config\n";
@unlink($INITD_PATH);
@file_put_contents($INITD_PATH, @implode("\n", $f));
@chmod($INITD_PATH,0755);

if(is_file('/usr/sbin/update-rc.d')){
	shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
}

if(is_file('/sbin/chkconfig')){
	shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
	shell_exec("/sbin/chkconfig --level 2345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
}


shell_exec("$php ". dirname(__FILE__)."/exec.initd-swap.php");

}

function MONIT(){
	$unix=new unix();
	$INITD_PATH=$unix->SLAPD_INITD_PATH();
	$SLAPD_PID_FILE=$unix->SLAPD_PID_PATH();	
	
	$f[]="check process slapd with pidfile $SLAPD_PID_FILE";
	$f[]="start program = \"$INITD_PATH start\"";
	$f[]="stop program  = \"$INITD_PATH stop\"";
	$f[]="if cpu is greater than 80% for 3 cycles then alert";
	$f[]="if cpu usage > 95% for 5 cycles then restart";
	$f[]="if 3 restarts within 3 cycles then timeout";
	$f[]="if failed port 389 then restart";	
	$f[]="";
	@file_put_contents("/etc/monit/conf.d/APP_OPENLDAP.monitrc", @implode("\n", $f));
	
	
	$f=array();
	
	if(is_file("/etc/init.d/sysklogd")){
		$f[]="check process syslogd with pidfile /var/run/syslogd.pid";
		$f[]="start program = \"/etc/init.d/sysklogd start\"";
		$f[]="stop program = \"/etc/init.d/sysklogd stop\"";
		$f[]="if 5 restarts within 5 cycles then timeout";
		$f[]="check file syslogd_file with path /var/log/syslog";
		$f[]="if timestamp > 10 minutes then restart";	
		@file_put_contents("/etc/monit/conf.d/APP_SYSKLOGD.monitrc", @implode("\n", $f));
	}
	
	if(is_file("/etc/init.d/syslog")){checkDebSyslog();}
	shell_exec("/usr/share/artica-postfix/bin/artica-install --monit-check");
	
}

function checkDebSyslog(){
	 if(!is_file("/etc/rsyslog.conf")){return;}
	 $f=file("/etc/init.d/syslog");
	 $RSYSLOGD_PIDFILE=null;
	 while (list ($num, $line) = each ($f)){
	 	if(preg_match("#RSYSLOGD_PIDFILE=(.+)#", $line,$re)){
	 		$RSYSLOGD_PIDFILE=$re[1];
	 		break;
	 	}
	}
	
	$filesize=filesize("/etc/init.d/syslog");
	if($filesize<50){$RSYSLOGD_PIDFILE="/var/run/rsyslogd.pid";}
	if($RSYSLOGD_PIDFILE==null){echo "syslog: [INFO] pidfile `cannot check pid...`\n";return;}
	
	echo "syslog: [INFO] pidfile `$RSYSLOGD_PIDFILE`\n";
	
	 $f=file("/etc/rsyslog.conf");
	 while (list ($num, $line) = each ($f)){
	 	if(preg_match("#\*\.\*.*?\s+(.+)#", $line,$re)){
	 		$syslogpath=$re[1];
	 		if(substr($syslogpath, 0,1)=='-'){$syslogpath=substr($syslogpath, 1,strlen($syslogpath));}
	 		break;
	 	}
	 	
	 }
	
	echo "syslog: [INFO] syslog path `$syslogpath`\n";
	if(!is_file($syslogpath)){echo "syslog: [ERR] syslog path `$syslogpath` no such file!\n";return;}
	
	$f=array();
	$f[]="check process rsyslogd with pidfile $RSYSLOGD_PIDFILE";
	$f[]="start program = \"/etc/init.d/syslog start\"";
	$f[]="stop program = \"/etc/init.d/syslog stop\"";
	$f[]="if 5 restarts within 5 cycles then timeout";

	@file_put_contents("/etc/monit/conf.d/APP_RSYSLOGD.monitrc", @implode("\n", $f));	
	if(file_exists("/usr/sbin/rsyslogd")){rsyslogd_init();}
}


function rsyslogd_bug_check(){
	if(!is_file("/etc/init.d/rsyslog")){return;}
	$f=explode("\n",@file_get_contents("/etc/init.d/rsyslog"));
	while (list ($index, $ligne) = each ($f) ){
		if(preg_match("#Provides:\s+mysql#", $ligne)){rsyslogd_init();return;}
		
	}
	
	
}

function rsyslogd_init(){
	$unix=new unix();
	$sock=new sockets();
	$servicebin=$unix->find_program("update-rc.d");
	if(!is_file($servicebin)){
		echo "syslog: [ERR] update-rc.d no such file....\n";
		return;
	}
	
	$rsyslogd=$unix->find_program("rsyslogd");
	if(!is_file($rsyslogd)){
		echo "syslog: [ERR] rsyslogd no such file....\n";
		return;
	}
		
	$users=new usersMenus();
	$mydir=dirname(__FILE__);
	if(!is_file("/etc/init.d/syslog")){return;}
	if(!is_file($servicebin)){return;}
	$php=$unix->LOCATE_PHP5_BIN();
	$stopmaillog="/etc/init.d/artica-postfix stop postfix-logger";
	$startmaillog="/etc/init.d/artica-postfix start postfix-logger";
	$restartmaillog="/etc/init.d/artica-postfix restart postfix-logger";
	$reconfigure=$unix->LOCATE_PHP5_BIN()." ".__FILE__." --rsyslogd-init";
	
	if(!$users->POSTFIX_INSTALLED){$stopmaillog=null;$startmaillog=null;$restartmaillog=null;}
	if($users->WEBSTATS_APPLIANCE){
		echo "syslog: [INFO] syslog path Act as Syslog server...\n";
		$SYSLOG_SERVER="$php $mydir/exec.syslog-engine.php --build-server --norestart";
		$sock->SET_INFO("ActAsASyslogServer", 1);
	}
	
	$schedules="$php ".dirname(__FILE__)."/exec.schedules.php";
	
	$f[]="#! /bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          rsyslog";
	$f[]="# Required-Start:    \$remote_fs \$time";
	$f[]="# Required-Stop:     umountnfs \$time";
	$f[]="# X-Stop-After:      sendsigs";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: enhanced syslogd";
	$f[]="# Description:       Rsyslog is an enhanced multi-threaded syslogd.";
	$f[]="#                    It is quite compatible to stock sysklogd and can be ";
	$f[]="#                    used as a drop-in replacement.";
	$f[]="#                    Written by Artica on ".date("Y-m-d H:i:s");
	$f[]="### END INIT INFO";
	$f[]="";
	$f[]="#";
	$f[]="# Author: Michael Biebl <biebl@debian.org>";
	$f[]="#";
	$f[]="";
	$f[]="# PATH should only include /usr/* if it runs after the mountnfs.sh script";
	$f[]="PATH=/sbin:/usr/sbin:/bin:/usr/bin";
	$f[]="DESC=\"enhanced syslogd\"";
	$f[]="NAME=rsyslog";
	$f[]="";
	$f[]="RSYSLOGD=rsyslogd";
	$f[]="RSYSLOGD_BIN=$rsyslogd";
	$f[]="RSYSLOGD_OPTIONS=\"-c4\"";
	$f[]="RSYSLOGD_PIDFILE=/var/run/rsyslogd.pid";
	$f[]="";
	$f[]="SCRIPTNAME=/etc/init.d/\$NAME";
	$f[]="";
	$f[]="# Exit if the package is not installed";
	$f[]="[ -x \"\$RSYSLOGD_BIN\" ] || exit 0";
	$f[]="";
	$f[]="# Read configuration variable file if it is present";
	$f[]="[ -r /etc/default/\$NAME ] && . /etc/default/\$NAME";
	$f[]="";
	$f[]="# Define LSB log_* functions.";
	$f[]=". /lib/lsb/init-functions";
	$f[]="";
	$f[]="do_start()";
	$f[]="{";
	$f[]="	DAEMON=\"\$RSYSLOGD_BIN\"";
	$f[]="	DAEMON_ARGS=\"\$RSYSLOGD_OPTIONS\"";
	$f[]="	PIDFILE=\"\$RSYSLOGD_PIDFILE\"";
	$f[]="";
	$f[]="	# Return";
	$f[]="	#   0 if daemon has been started";
	$f[]="	#   1 if daemon was already running";
	$f[]="	#   other if daemon could not be started or a failure occured";
	if($SYSLOG_SERVER<>null){$f[]="	$SYSLOG_SERVER";}
	$f[]="	start-stop-daemon --start --quiet --pidfile \$PIDFILE --exec \$DAEMON -- \$DAEMON_ARGS";
	$f[]="  /etc/init.d/artica-postfix start auth-logger";
	$f[]="  /etc/init.d/artica-postfix start sysloger";
	if($startmaillog<>null){$f[]="  $startmaillog";}
	$f[]="  $schedules";
	$f[]="  $reconfigure";
	$f[]="}";
	$f[]="";
	$f[]="do_stop()";
	$f[]="{";
	$f[]="	NAME=\"\$RSYSLOGD\"";
	$f[]="	PIDFILE=\"\$RSYSLOGD_PIDFILE\"";
	$f[]="";
	$f[]="	# Return";
	$f[]="	#   0 if daemon has been stopped";
	$f[]="	#   1 if daemon was already stopped";
	$f[]="	#   other if daemon could not be stopped or a failure occurred";
	$f[]="	start-stop-daemon --stop --quiet --retry=TERM/30/KILL/5 --pidfile \$PIDFILE --name \$NAME";
	$f[]="  /etc/init.d/artica-postfix stop auth-logger";
	$f[]="  /etc/init.d/artica-postfix stop sysloger";
	$f[]="  $stopmaillog";
	if($SYSLOG_SERVER<>null){$f[]="	$SYSLOG_SERVER";}
	$f[]="}";
	$f[]="";
	$f[]="#";
	$f[]="# Tell rsyslogd to reload its configuration";
	$f[]="#";
	$f[]="do_reload() {";
	$f[]="	NAME=\"\$RSYSLOGD\"";
	$f[]="	PIDFILE=\"\$RSYSLOGD_PIDFILE\"";
	$f[]="	$reconfigure";
	$f[]="	start-stop-daemon --stop --signal HUP --quiet --pidfile \$PIDFILE --name \$NAME";
	$f[]="  /etc/init.d/artica-postfix restart auth-logger";
	$f[]="  /etc/init.d/artica-postfix restart sysloger";
	$f[]="	$restartmaillog";
	$f[]="}";
	$f[]="";
	$f[]="create_xconsole() {";
	$f[]="	XCONSOLE=/dev/xconsole";
	$f[]="	if [ \"\$(uname -s)\" = \"GNU/kFreeBSD\" ]; then";
	$f[]="		XCONSOLE=/var/run/xconsole";
	$f[]="		ln -sf \$XCONSOLE /dev/xconsole";
	$f[]="	fi";
	$f[]="	if [ ! -e \$XCONSOLE ]; then";
	$f[]="		mknod -m 640 \$XCONSOLE p";
	$f[]="		chown root:adm \$XCONSOLE";
	$f[]="		[ -x /sbin/restorecon ] && /sbin/restorecon \$XCONSOLE";
	$f[]="	fi";
	$f[]="}";
	$f[]="";
	$f[]="sendsigs_omit() {";
	$f[]="	OMITDIR=/lib/init/rw/sendsigs.omit.d";
	$f[]="	mkdir -p \$OMITDIR";
	$f[]="	rm -f \$OMITDIR/rsyslog";
	$f[]="	ln -s \$RSYSLOGD_PIDFILE \$OMITDIR/rsyslog";
	$f[]="}";
	$f[]="";
	$f[]="case \"\$1\" in";
	$f[]="  start)";
	$f[]="	log_daemon_msg \"Starting \$DESC\" \"\$RSYSLOGD\"\n";
	$f[]="	create_xconsole";
	$f[]="	do_start";
	$f[]="	case \"\$?\" in";
	$f[]="		0) sendsigs_omit";
	$f[]="		   log_end_msg 0 ;;";
	$f[]="		1) log_progress_msg \"already started\"";
	$f[]="		   log_end_msg 0 ;;";
	$f[]="		*) log_end_msg 1 ;;";
	$f[]="	esac";
	$f[]="";
	$f[]="	;;";
	$f[]="  stop)";
	$f[]="	log_daemon_msg \"Stopping \$DESC\" \"\$RSYSLOGD\"";
	$f[]="	do_stop";
	$f[]="	case \"\$?\" in";
	$f[]="		0) log_end_msg 0 ;;";
	$f[]="		1) log_progress_msg \"already stopped\"";
	$f[]="		   log_end_msg 0 ;;";
	$f[]="		*) log_end_msg 1 ;;";
	$f[]="	esac";
	$f[]="";
	$f[]="	;;";
	$f[]="  reload|force-reload)";
	$f[]="	log_daemon_msg \"Reloading \$DESC\" \"\$RSYSLOGD\"";
	$f[]="	do_reload";
	$f[]="	log_end_msg \$?";
	$f[]="	;;";
	$f[]="  restart)";
	$f[]="	\$0 stop";
	$f[]="	\$0 start";
	$f[]="	;;";
	$f[]="  status)";
	$f[]="	status_of_proc -p \$RSYSLOGD_PIDFILE \$RSYSLOGD_BIN \$RSYSLOGD && exit 0 || exit \$?";
	$f[]="	;;";
	$f[]="  *)";
	$f[]="	echo \"Usage: \$SCRIPTNAME {start|stop|restart|reload|force-reload|status}\" >&2";
	$f[]="	exit 3";
	$f[]="	;;";
	$f[]="esac";
	$f[]="";
	$f[]=":";
	$f[]="";
	@unlink("/etc/init.d/syslog");
	@chmod("/etc/init.d/syslog", 0755);
	@file_put_contents("/etc/init.d/syslog", @implode("\n", $f));
	if(!is_file("/etc/init.d/rsyslog")){@file_put_contents("/etc/init.d/rsyslog", @implode("\n", $f));}
	echo "syslog: [INFO] syslog path `/etc/init.d/syslog` done\n";
	
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program($nohup);
	
	
	
}

function check_init_rsyslogd(){
	if(!is_file("/etc/init.d/rsyslog")){return true;}
	
}

function dnsmasq_init_debian(){
	$unix=new unix();
	$sock=new sockets();
	$servicebin=$unix->find_program("update-rc.d");
	$users=new usersMenus();
	if(!is_file("/etc/init.d/dnsmasq")){return;}
	if(!is_file($servicebin)){return;}
	$php=$unix->LOCATE_PHP5_BIN();
	if(!is_file($servicebin)){return;}
	$EnableDNSMASQ=$sock->GET_INFO("EnableDNSMASQ");
	if(!is_numeric($EnableDNSMASQ)){$EnableDNSMASQ=0;}
	echo "dnsmasq: [INFO] dnsmasq enabled = `$EnableDNSMASQ`\n";	
	
	$runcmd="$php /usr/share/artica-postfix/exec.dnsmasq.php";
	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:       dnsmasq";
	$f[]="# Required-Start: \$network \$remote_fs \$syslog";
	$f[]="# Required-Stop:  \$network \$remote_fs \$syslog";
	$f[]="# Default-Start:  2 3 4 5";
	$f[]="# Default-Stop:   1";
	$f[]="# Description:    DHCP and DNS server";
	$f[]="### END INIT INFO";
	$f[]="";
	$f[]="set +e   # Don't exit on error status";
	$f[]="";
	$f[]="PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin";
	$f[]="DAEMON=/usr/sbin/dnsmasq";
	$f[]="NAME=dnsmasq";
	$f[]="DESC=\"DNS forwarder and DHCP server\"";
	$f[]="";
	$f[]="# Most configuration options in /etc/default/dnsmasq are deprecated";
	$f[]="# but still honoured.";
	$f[]="ENABLED=$EnableDNSMASQ";
	$f[]="if [ -r /etc/default/\$NAME ]; then";
	$f[]="	. /etc/default/\$NAME";
	$f[]="fi";
	$f[]="";
	$f[]="# Get the system locale, so that messages are in the correct language, and the ";
	$f[]="# charset for IDN is correct";
	$f[]="if [ -r /etc/default/locale ]; then";
	$f[]="        . /etc/default/locale";
	$f[]="        export LANG";
	$f[]="fi";
	$f[]="";
	$f[]="test -x \$DAEMON || exit 0";
	$f[]="";
	$f[]="# Provide skeleton LSB log functions for backports which don't have LSB functions.";
	$f[]="if [ -f /lib/lsb/init-functions ]; then";
	$f[]="         . /lib/lsb/init-functions";
	$f[]="else";
	$f[]="         log_warning_msg () {";
	$f[]="            echo \"\${@}.\"";
	$f[]="         }";
	$f[]="";
	$f[]="         log_success_msg () {";
	$f[]="            echo \"\${@}.\"";
	$f[]="         }";
	$f[]="";
	$f[]="         log_daemon_msg () {";
	$f[]="            echo -n \"\${1}: \$2\"";
	$f[]="         }";
	$f[]="";
	$f[]="	 log_end_msg () {";
	$f[]="            if [ \$1 -eq 0 ]; then";
	$f[]="              echo \".\"";
	$f[]="            elif [ \$1 -eq 255 ]; then";
	$f[]="              /bin/echo -e \" (warning).\"";
	$f[]="            else";
	$f[]="              /bin/echo -e \" failed!\"";
	$f[]="            fi";
	$f[]="         }";
	$f[]="fi";
	$f[]="";
	$f[]="# RESOLV_CONF:";
	$f[]="# If the resolvconf package is installed then use the resolv conf file";
	$f[]="# that it provides as the default.  Otherwise use /etc/resolv.conf as";
	$f[]="# the default.";
	$f[]="#";
	$f[]="# If IGNORE_RESOLVCONF is set in /etc/default/dnsmasq or an explicit";
	$f[]="# filename is set there then this inhibits the use of the resolvconf-provided";
	$f[]="# information.";
	$f[]="#";
	$f[]="# Note that if the resolvconf package is installed it is not possible to ";
	$f[]="# override it just by configuration in /etc/dnsmasq.conf, it is necessary";
	$f[]="# to set IGNORE_RESOLVCONF=yes in /etc/default/dnsmasq.";
	$f[]="";
	$f[]="if [ ! \"\$RESOLV_CONF\" ] &&";
	$f[]="   [ ! \"\$IGNORE_RESOLVCONF\" ] &&";
	$f[]="   [ -x /sbin/resolvconf ]";
	$f[]="then";
	$f[]="	RESOLV_CONF=/var/run/dnsmasq/resolv.conf";
	$f[]="fi";
	$f[]="";
	$f[]="for INTERFACE in \$DNSMASQ_INTERFACE; do";
	$f[]="	DNSMASQ_INTERFACES=\"\$DNSMASQ_INTERFACES -i \$INTERFACE\"";
	$f[]="done";
	$f[]="";
	$f[]="for INTERFACE in \$DNSMASQ_EXCEPT; do";
	$f[]="	DNSMASQ_INTERFACES=\"\$DNSMASQ_INTERFACES -I \$INTERFACE\"";
	$f[]="done";
	$f[]="";
	$f[]="if [ ! \"\$DNSMASQ_USER\" ]; then";
	$f[]="   DNSMASQ_USER=\"root\"";
	$f[]="fi";
	$f[]="";
	$f[]="start()";
	$f[]="{";
	$f[]="ENABLED=$EnableDNSMASQ";
	$f[]="	if [ \$ENABLED -eq 0 ]";
	$f[]="	then";
	$f[]="		log_daemon_msg \"Starting \$DESC\" \"\$NAME\" is disabled";
	$f[]="		return 2";
	$f[]="	fi";
	$f[]="$runcmd";
	$f[]="DNSMASQ_OPTS=\" -C /etc/dnsmasq.conf\"";
	$f[]="        # Return";
	$f[]="	#   0 if daemon has been started";
	$f[]="	#   1 if daemon was already running";
	$f[]="	#   2 if daemon could not be started";
	$f[]="";
	$f[]="        # /var/run may be volatile, so we need to ensure that";
	$f[]="        # /var/run/dnsmasq exists here as well as in postinst";
	$f[]="        if [ ! -d /var/run/dnsmasq ]; then";
	$f[]="           mkdir /var/run/dnsmasq || return 2";
	$f[]="           chown dnsmasq:nogroup /var/run/dnsmasq || return 2";
	$f[]="        fi";
	$f[]="";
	$f[]="	start-stop-daemon --start --quiet --pidfile /var/run/dnsmasq/\$NAME.pid --exec \$DAEMON --test > /dev/null || return 1";
	$f[]="	start-stop-daemon --start --quiet --pidfile /var/run/dnsmasq/\$NAME.pid --exec \$DAEMON -- -x /var/run/dnsmasq/\$NAME.pid \${MAILHOSTNAME:+ -m \$MAILHOSTNAME} \${MAILTARGET:+ -t \$MAILTARGET} \${DNSMASQ_USER:+ -u \$DNSMASQ_USER} \${DNSMASQ_INTERFACE:+ \$DNSMASQ_INTERFACES} \${DHCP_LEASE:+ -l \$DHCP_LEASE} \${DOMAIN_SUFFIX:+ -s \$DOMAIN_SUFFIX} \${RESOLV_CONF:+ -r \$RESOLV_CONF} \${CACHESIZE:+ -c \$CACHESIZE} \${CONFIG_DIR:+ -7 \$CONFIG_DIR} \${DNSMASQ_OPTS:+ \$DNSMASQ_OPTS} || return 2";
	$f[]="}";
	$f[]="";
	$f[]="start_resolvconf()";
	$f[]="{";
	$f[]="# If interface \"lo\" is explicitly disabled in /etc/default/dnsmasq";
	$f[]="# Then dnsmasq won't be providing local DNS, so don't add it to";
	$f[]="# the resolvconf server set.";
	$f[]="	for interface in \$DNSMASQ_EXCEPT";
	$f[]="	do";
	$f[]="		[ \$interface = lo ] && return";
	$f[]="	done";
	$f[]="";
	$f[]="        if [ -x /sbin/resolvconf ] ; then";
	$f[]="		echo \"nameserver 127.0.0.1\" | /sbin/resolvconf -a lo.\$NAME";
	$f[]="	fi";
	$f[]="	return 0";
	$f[]="}";
	$f[]="";
	$f[]="stop()";
	$f[]="{";
	$f[]="	# Return";
	$f[]="	#   0 if daemon has been stopped";
	$f[]="	#   1 if daemon was already stopped";
	$f[]="	#   2 if daemon could not be stopped";
	$f[]="	#   other if a failure occurred";
	$f[]="	start-stop-daemon --stop --quiet --retry=TERM/30/KILL/5 --pidfile /var/run/dnsmasq/\$NAME.pid --name \$NAME";
	$f[]="	RETVAL=\"\$?\"";
	$f[]="	[ \"\$RETVAL\" = 2 ] && return 2";
	$f[]="	return \"\$RETVAL\"";
	$f[]="}";
	$f[]="";
	$f[]="stop_resolvconf()";
	$f[]="{";
	$f[]="	if [ -x /sbin/resolvconf ] ; then";
	$f[]="		/sbin/resolvconf -d lo.\$NAME";
	$f[]="	fi";
	$f[]="	return 0";
	$f[]="}";
	$f[]="";
	$f[]="status()";
	$f[]="{";
	$f[]="	# Return";
	$f[]="	#   0 if daemon is running";
	$f[]="	#   1 if daemon is dead and pid file exists";
	$f[]="	#   3 if daemon is not running";
	$f[]="	#   4 if daemon status is unknown";
	$f[]="	start-stop-daemon --start --quiet --pidfile /var/run/dnsmasq/\$NAME.pid --exec \$DAEMON --test > /dev/null";
	$f[]="	case \"\$?\" in";
	$f[]="		0) [ -e \"/var/run/dnsmasq/\$NAME.pid\" ] && return 1 ; return 3 ;;";
	$f[]="		1) return 0 ;;";
	$f[]="		*) return 4 ;;";
	$f[]="	esac";
	$f[]="}";
	$f[]="";
	$f[]="case \"\$1\" in";
	$f[]="  start)";
	$f[]="	test \"\$ENABLED\" != \"0\" || exit 0";
	$f[]="	log_daemon_msg \"Starting \$DESC\" \"\$NAME\" Enabled=\$ENABLED";
	$f[]="	start";
	$f[]="	case \"\$?\" in";
	$f[]="		0)";
	$f[]="			log_end_msg 0";
	$f[]="			start_resolvconf";
	$f[]="			exit 0";
	$f[]="			;;";
	$f[]="		1)";
	$f[]="			log_success_msg \"(already running)\"";
	$f[]="			exit 0";
	$f[]="			;;";
	$f[]="		*)";
	$f[]="			log_end_msg 1";
	$f[]="			exit 1";
	$f[]="			;;";
	$f[]="	esac";
	$f[]="	;;";
	$f[]="  stop)";
	$f[]="	stop_resolvconf";
	$f[]="	if [ \"\$ENABLED\" != \"0\" ]; then";
	$f[]="             log_daemon_msg \"Stopping \$DESC\" \"\$NAME\"";
	$f[]="	fi";
	$f[]="	stop";
	$f[]="        RETVAL=\"\$?\"";
	$f[]="	if [ \"\$ENABLED\" = \"0\" ]; then";
	$f[]="	    case \"\$RETVAL\" in";
	$f[]="	       0) log_daemon_msg \"Stopping \$DESC\" \"\$NAME\"; log_end_msg 0 ;;";
	$f[]="            esac ";
	$f[]="	    exit 0";
	$f[]="	fi";
	$f[]="	case \"\$RETVAL\" in";
	$f[]="		0) log_end_msg 0 ; exit 0 ;;";
	$f[]="		1) log_warning_msg \"(not running)\" ; exit 0 ;;";
	$f[]="		*) log_end_msg 1; exit 1 ;;";
	$f[]="	esac";
	$f[]="	;;";
	$f[]="  restart|force-reload)";
	$f[]="	test \"\$ENABLED\" != \"0\" || exit 1";
	$f[]="	\$DAEMON --test \${CONFIG_DIR:+ -7 \$CONFIG_DIR} \${DNSMASQ_OPTS:+ \$DNSMASQ_OPTS} >/dev/null 2>&1";
	$f[]="	if [ \$? -ne 0 ]; then";
	$f[]="	    NAME=\"configuration syntax check\"";
	$f[]="	    RETVAL=\"2\"";
	$f[]="	else   ";
	$f[]="	    stop_resolvconf";
	$f[]="	    stop";
	$f[]="	    RETVAL=\"\$?\"";
	$f[]="        fi";
	$f[]="	log_daemon_msg \"Restarting \$DESC\" \"\$NAME\"";
	$f[]="	case \"\$RETVAL\" in";
	$f[]="		0|1)";
	$f[]="		        sleep 2";
	$f[]="			start";
	$f[]="			case \"\$?\" in";
	$f[]="				0)";
	$f[]="					log_end_msg 0";
	$f[]="					start_resolvconf";
	$f[]="					exit 0";
	$f[]="					;;";
	$f[]="			        *)";
	$f[]="					log_end_msg 1";
	$f[]="					exit 1";
	$f[]="					;;";
	$f[]="			esac";
	$f[]="			;;";
	$f[]="		*)";
	$f[]="			log_end_msg 1";
	$f[]="			exit 1";
	$f[]="			;;";
	$f[]="	esac";
	$f[]="	;;";
	$f[]="  status)";
	$f[]="	log_daemon_msg \"Checking \$DESC\" \"\$NAME\"";
	$f[]="	status";
	$f[]="	case \"\$?\" in";
	$f[]="		0) log_success_msg \"(running)\" ; exit 0 ;;";
	$f[]="		1) log_success_msg \"(dead, pid file exists)\" ; exit 1 ;;";
	$f[]="		3) log_success_msg \"(not running)\" ; exit 3 ;;";
	$f[]="		*) log_success_msg \"(unknown)\" ; exit 4 ;;";
	$f[]="	esac";
	$f[]="	;;";
	$f[]="  *)";
	$f[]="	echo \"Usage: /etc/init.d/\$NAME {start|stop|restart|force-reload|status}\" >&2";
	$f[]="	exit 3";
	$f[]="	;;";
	$f[]="esac";
	$f[]="";
	$f[]="exit 0";
	$f[]="";	
	@unlink("/etc/init.d/dnsmasq");
	@file_put_contents("/etc/init.d/dnsmasq", @implode("\n", $f));
	@chmod("/etc/init.d/dnsmasq",0755);
	echo "dnsmasq: [INFO] dnsmasq path `/etc/init.d/dnsmasq` done\n";	
}




function nscd_init_debian(){
	$unix=new unix();
	$sock=new sockets();
	$servicebin=$unix->find_program("update-rc.d");
	$users=new usersMenus();
	if(!is_file("/etc/init.d/nscd")){return;}
	if(!is_file($servicebin)){return;}
	$php=$unix->LOCATE_PHP5_BIN();
	if(!is_file($servicebin)){return;}
	$EnableNSCD=$sock->GET_INFO("EnableNSCD");
	if(!is_numeric($EnableNSCD)){$EnableNSCD=0;}
	$nscdbin=$unix->find_program("nscd");
	echo "nscd: [INFO] ncsd enabled = `$EnableNSCD`\n";
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          nscd";
	$f[]="# Required-Start:    \$remote_fs \$syslog";
	$f[]="# Required-Stop:     \$remote_fs \$syslog";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Starts the Name Service Cache Daemon";
	$f[]="### END INIT INFO";
	$f[]="";
	$f[]="#";
	$f[]="# nscd:		Starts the Name Service Cache Daemon";
	$f[]="#";
	$f[]="# description:  This is a daemon which handles passwd and group lookups";
	$f[]="#		for running programs and caches the results for the next";
	$f[]="#		query.  You should start this daemon only if you use";
	$f[]="#		slow Services like NIS or NIS+";
	$f[]="";
	$f[]="PATH=\"/sbin:/usr/sbin:/bin:/usr/bin\"";
	$f[]="NAME=\"nscd\"";
	$f[]="DESC=\"Name Service Cache Daemon\"";
	$f[]="DAEMON=\"$nscdbin\"";
	$f[]="PIDFILE=\"/var/run/nscd/nscd.pid\"";
	$f[]="";
	$f[]="# Sanity checks.";
	$f[]="umask 022";
	$f[]="[ -f /etc/nscd.conf ] || exit 0";
 	$f[]="[ -x \"\$DAEMON\" ] || exit 0";
	$f[]="[ -d /var/run/nscd ] || mkdir -p /var/run/nscd";
	$f[]=". /lib/lsb/init-functions";
	$f[]="";
	$f[]="start_nscd()";
	$f[]="{";
	$f[]="ENABLED=$EnableNSCD";
	$f[]="	if [ \$ENABLED -eq 0 ]";
	$f[]="	then";
	$f[]="		return 1";
	$f[]="	fi";
	$f[]="	log_daemon_msg \"Starting \$DESC\" \"\$NAME\"";	
	$f[]="	# Return";
	$f[]="	#   0 if daemon has been started or was already running";
	$f[]="	#   2 if daemon could not be started";
	$f[]="	start-stop-daemon --start --quiet --pidfile \"\$PIDFILE\" --exec \"\$DAEMON\" --test > /dev/null || return 0";
	$f[]="	start-stop-daemon --start --quiet --pidfile \"\$PIDFILE\" --exec \"\$DAEMON\" || return 2";
	$f[]="}";
	$f[]="";
	$f[]="stop_nscd()";
	$f[]="{";

	$f[]="	# Return";
	$f[]="	#   0 if daemon has been stopped";
	$f[]="	#   1 if daemon was already stopped";
	$f[]="	#   2 if daemon could not be stopped";
	$f[]="";
	$f[]="	# we try to stop using nscd --shutdown, that fails also if nscd is not present.";
	$f[]="	# in that case, fallback to \"good old methods\"";
	$f[]="	RETVAL=0";
	$f[]="	if ! \$DAEMON --shutdown; then";
	$f[]="		start-stop-daemon --stop --quiet --pidfile \"\$PIDFILE\" --name \"\$NAME\" --test > /dev/null";
	$f[]="		RETVAL=\"\$?\"";
	$f[]="		[ \"\$?\" -ne 0  -a  \"\$?\" -ne 1 ] && return 2";
	$f[]="	fi";
	$f[]="";
	$f[]="	# Wait for children to finish too";
	$f[]="	start-stop-daemon --stop --quiet --oknodo --retry=0/30/KILL/5 --exec \"\$DAEMON\" > /dev/null";
	$f[]="	[ \"\$?\" -ne 0  -a  \"\$?\" -ne 1 ] && return 2";
	$f[]="	rm -f \"\$PIDFILE\"";
	$f[]="	return \"\$RETVAL\"";
	$f[]="}";
	$f[]="";
	$f[]="status()";
	$f[]="{";
	$f[]="	# Return";
	$f[]="	#   0 if daemon is stopped";
	$f[]="	#   1 if daemon is running";
	$f[]="	start-stop-daemon --start --quiet --pidfile \"\$PIDFILE\" --exec \"\$DAEMON\" --test > /dev/null || return 1";
	$f[]="	return 0";
	$f[]="}";
	$f[]="";
	$f[]="case \"\$1\" in";
	$f[]="start)";
	$f[]="	start_nscd";
	$f[]="	case \"\$?\" in";
	$f[]="		0) log_end_msg 0 ; exit 0 ;;";
	$f[]="		1) log_warning_msg \" (already running).\" ; exit 0 ;;";
	$f[]="		*) log_end_msg 1 ; exit 1 ;;";
	$f[]="	esac";
	$f[]="	;;";
	$f[]="stop)";
	$f[]="	log_daemon_msg \"Stopping \$DESC\" \"\$NAME\"";
	$f[]="	stop_nscd";
	$f[]="	case \"\$?\" in";
	$f[]="		0) log_end_msg 0 ; exit 0 ;;";
	$f[]="		1) log_warning_msg \" (not running).\" ; exit 0 ;;";
	$f[]="		*) log_end_msg 1 ; exit 1 ;;";
	$f[]="	esac";
	$f[]="	;;";
	$f[]="restart|force-reload)";
	$f[]="	log_daemon_msg \"Restarting \$DESC\" \"\$NAME\"";
	$f[]="	for table in passwd group hosts ; do";
	$f[]="		\$DAEMON --invalidate \$table";
	$f[]="	done";
	$f[]="	stop_nscd";
	$f[]="	case \"\$?\" in";
	$f[]="	0|1)";
	$f[]="		start_nscd";
	$f[]="		case \"\$?\" in";
	$f[]="			0) log_end_msg 0 ; exit 0 ;;";
	$f[]="			1) log_failure_msg \" (failed -- old process is still running).\" ; exit 1 ;;";
	$f[]="			*) log_failure_msg \" (failed to start).\" ; exit 1 ;;";
	$f[]="		esac";
	$f[]="		;;";
	$f[]="	*)";
	$f[]="		log_failure_msg \" (failed to stop).\"";
	$f[]="		exit 1";
	$f[]="		;;";
	$f[]="	esac";
	$f[]="	;;";
	$f[]="status)";
	$f[]="	log_daemon_msg \"Status of \$DESC service: \"";
	$f[]="	status";
	$f[]="	case \"\$?\" in";
	$f[]="		0) log_failure_msg \"not running.\" ; exit 3 ;;";
	$f[]="		1) log_success_msg \"running.\" ; exit 0 ;;";
	$f[]="	esac";
	$f[]="	;;";
	$f[]="*)";
	$f[]="	echo \"Usage: /etc/init.d/\$NAME {start|stop|force-reload|restart|status}\" >&2";
	$f[]="	exit 1";
	$f[]="	;;";
	$f[]="esac";	
	@unlink("/etc/init.d/nscd");
	@file_put_contents("/etc/init.d/nscd", @implode("\n", $f));
	@chmod("/etc/init.d/nscd",0755);
	echo "nscd: [INFO] nscd path `/etc/init.d/nscd` done\n";		
}

function wsgate_init_debian(){
$unix=new unix();
$wsgate_bin=$unix->find_program("wsgate");
$php5=$unix->LOCATE_PHP5_BIN();	
	
$f[]="#!/bin/sh";
$f[]="### BEGIN INIT INFO";
$f[]="# Provides:          wsgate";
$f[]="# Required-Start:    \$network \$local_fs";
$f[]="# Required-Stop:";
$f[]="# Default-Start:     2 3 4 5";
$f[]="# Default-Stop:      0 1 6";
$f[]="# Short-Description: WebSocket gateway for FreeRDP-WebConnect";
$f[]="# Description:       The WebSockets gateway for FreeRDP-WebConnect allws you";
$f[]="#                    to provide browser-based RDP sessions.";
$f[]="### END INIT INFO";
$f[]="";
$f[]="# Author: Fritz Elfert <wsgate@fritz-elfert.de>";
$f[]="";
$f[]="# PATH should only include /usr/ if it runs after the mountnfs.sh script";
$f[]="PATH=/sbin:/usr/sbin:/bin:/usr/bin";
$f[]="DESC=wsgate             # Introduce a short description here";
$f[]="NAME=wsgate             # Introduce the short server's name here";
$f[]="DAEMON=\"$wsgate_bin\" # Introduce the server's location here";
$f[]="DAEMON_ARGS=\"\"             # Arguments to run the daemon with";
$f[]="PIDFILE=/var/run/wsgate/\$NAME.pid";
$f[]="SCRIPTNAME=/etc/init.d/\$NAME";
$f[]="";
$f[]="# Exit if the package is not installed";
$f[]="[ -x \$DAEMON ] || exit 0";
$f[]="";
$f[]="# Read configuration variable file if it is present";
$f[]="[ -r /etc/default/\$NAME ] && . /etc/default/\$NAME";
$f[]="";
$f[]="# Load the VERBOSE setting and other rcS variables";
$f[]=". /lib/init/vars.sh";
$f[]="";
$f[]="# Define LSB log_* functions.";
$f[]="# Depend on lsb-base (>= 3.0-6) to ensure that this file is present.";
$f[]=". /lib/lsb/init-functions";
$f[]="";
$f[]="#";
$f[]="# Function that starts the daemon/service";
$f[]="#";
$f[]="do_start()";
$f[]="{";
$f[]="    # Make shure, that bindhelper has correct permissions";
$f[]="    chown root.wsgate /usr/lib/wsgate/wsgate/bindhelper";
$f[]="    chmod 04754 /usr/lib/wsgate/wsgate/bindhelper";
$f[]="    # Create /var/run/wsgate";
$f[]="    mkdir -p /var/run/wsgate";
$f[]="    chown wsgate.wsgate /var/run/wsgate";
$f[]="    # Generate cert if necessary";
$f[]="    /usr/lib/wsgate/wsgate/keygen.sh";
$f[]="";
$f[]="    # Return";
$f[]="    #   0 if daemon has been started";
$f[]="    #   1 if daemon was already running";
$f[]="    #   2 if daemon could not be started";
$f[]="    start-stop-daemon --start --quiet --chuid wsgate:wsgate --pidfile \$PIDFILE --exec \$DAEMON --test > /dev/null \ ";
$f[]="        || return 1";
$f[]="    start-stop-daemon --start --quiet --chuid wsgate:wsgate --pidfile \$PIDFILE --exec \$DAEMON -- \ ";
$f[]="        -c /etc/wsgate.ini \$DAEMON_ARGS \ ";
$f[]="        || return 2";
$f[]="    # Add code here, if necessary, that waits for the process to be ready";
$f[]="    # to handle requests from services started subsequently which depend";
$f[]="    # on this one.  As a last resort, sleep for some time.";
$f[]="}";
$f[]="";
$f[]="#";
$f[]="# Function that stops the daemon/service";
$f[]="#";
$f[]="do_stop()";
$f[]="{";
$f[]="    # Return";
$f[]="    #   0 if daemon has been stopped";
$f[]="    #   1 if daemon was already stopped";
$f[]="    #   2 if daemon could not be stopped";
$f[]="    #   other if a failure occurred";
$f[]="    start-stop-daemon --stop --quiet --retry=TERM/30/KILL/5 --pidfile \$PIDFILE --name \$NAME";
$f[]="    RETVAL=\"\$?\"";
$f[]="    [ \"\$RETVAL\" = 2 ] && return 2";
$f[]="    # Wait for children to finish too if this is a daemon that forks";
$f[]="    # and if the daemon is only ever run from this initscript.";
$f[]="    # If the above conditions are not satisfied then add some other code";
$f[]="    # that waits for the process to drop all resources that could be";
$f[]="    # needed by services started subsequently.  A last resort is to";
$f[]="    # sleep for some time.";
$f[]="    start-stop-daemon --stop --quiet --oknodo --retry=0/30/KILL/5 --exec \$DAEMON";
$f[]="    [ \"\$?\" = 2 ] && return 2";
$f[]="    # Many daemons don't delete their pidfiles when they exit.";
$f[]="    rm -f \$PIDFILE";
$f[]="    return \"\$RETVAL\"";
$f[]="}";
$f[]="";
$f[]="#";
$f[]="# Function that sends a SIGHUP to the daemon/service";
$f[]="#";
$f[]="do_reload() {";
$f[]="    #";
$f[]="    # If the daemon can reload its configuration without";
$f[]="    # restarting (for example, when it is sent a SIGHUP),";
$f[]="    # then implement that here.";
$f[]="    #";
$f[]="    start-stop-daemon --stop --signal 1 --quiet --pidfile \$PIDFILE --name \$NAME";
$f[]="    return 0";
$f[]="}";
$f[]="";
$f[]="case \"\$1\" in";
$f[]="    start)";
$f[]="        [ \"\$VERBOSE\" != no ] && log_daemon_msg \"Starting \$DESC \" \"\$NAME\"";
$f[]="        do_start";
$f[]="        case \"\$?\" in";
$f[]="            0|1) [ \"\$VERBOSE\" != no ] && log_end_msg 0 ;;";
$f[]="        2) [ \"\$VERBOSE\" != no ] && log_end_msg 1 ;;";
$f[]="    esac";
$f[]="    ;;";
$f[]="stop)";
$f[]="    [ \"\$VERBOSE\" != no ] && log_daemon_msg \"Stopping \$DESC\" \"\$NAME\"";
$f[]="    do_stop";
$f[]="    case \"\$?\" in";
$f[]="        0|1) [ \"\$VERBOSE\" != no ] && log_end_msg 0 ;;";
$f[]="    2) [ \"\$VERBOSE\" != no ] && log_end_msg 1 ;;";
$f[]="esac";
$f[]=";;";
$f[]="  status)";
$f[]="      status_of_proc \"\$DAEMON\" \"\$NAME\" && exit 0 || exit \$?";
$f[]="      ;;";
$f[]="  #reload|force-reload)";
$f[]="      #";
$f[]="      # If do_reload() is not implemented then leave this commented out";
$f[]="      # and leave 'force-reload' as an alias for 'restart'.";
$f[]="      #";
$f[]="      #log_daemon_msg \"Reloading \$DESC\" \"\$NAME\"";
$f[]="      #do_reload";

$f[]="      #log_end_msg \$?";
$f[]="      #;;";
$f[]="  restart|force-reload)";
$f[]="      #";
$f[]="      # If the \"reload\" option is implemented then remove the";
$f[]="      # 'force-reload' alias";
$f[]="      #";
$f[]="      log_daemon_msg \"Restarting \$DESC\" \"\$NAME\"";
$f[]="      do_stop";
$f[]="      case \"\$?\" in";
$f[]="          0|1)";
$f[]="              do_start";
$f[]="              case \"\$?\" in";
$f[]="                  0) log_end_msg 0 ;;";
$f[]="              1) log_end_msg 1 ;; # Old process is still running";
$f[]="          *) log_end_msg 1 ;; # Failed to start";
$f[]="      esac";
$f[]="      ;;";
$f[]="  *)";
$f[]="      # Failed to stop";
$f[]="      log_end_msg 1";
$f[]="      ;;";
$f[]="    esac";
$f[]="    ;;";
$f[]="*)";
$f[]="    #echo \"Usage: \$SCRIPTNAME {start|stop|restart|reload|force-reload}\" >&2";
$f[]="    echo \"Usage: \$SCRIPTNAME {start|stop|status|restart|force-reload}\" >&2";
$f[]="    exit 3";
$f[]="    ;;";
$f[]="esac";
$f[]="";
$f[]=":";
$f[]="";	
@unlink("/etc/init.d/wsgate");
@file_put_contents("/etc/init.d/wsgate", @implode("\n", $f));
@chmod("/etc/init.d/wsgate",0755);
echo "wsgate: [INFO] wsgate path `/etc/init.d/wsgate` done\n";		

}

function restart_artica_webservices(){
	exec("/etc/init.d/artica-postfix restart framework 2>&1",$results);
	exec("/etc/init.d/artica-postfix restart apache 2>&1",$results);
	system_admin_events("Restarting Artica Web consoles done\n".@implode("\n", $results), __FUNCTION__, __FILE__, __LINE__, "system");
	
}

function ufdbguard(){
	$unix=new unix();
	$sock=new sockets();
	$ufdbguardd=$unix->find_program("ufdbguardd");
	if(!is_file($ufdbguardd)){return;}
	$php=$unix->LOCATE_PHP5_BIN();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	$EnableUfdbGuard=$sock->GET_INFO("EnableUfdbGuard");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if(!is_numeric($EnableUfdbGuard)){$EnableUfdbGuard=0;}
	
	if($EnableRemoteStatisticsAppliance==1){$EnableUfdbGuard=0;}
	if($EnableWebProxyStatsAppliance==1){$EnableUfdbGuard=1;}
	
	
	$f[]="#!/bin/sh";
	$f[]="#";
	$f[]="# /etc/init.d/ufdb";
	$f[]="#";
	$f[]="# ufdbGuard is copyrighted (C) 2005-2012 by URLfilterDB with all rights reserved.";
	$f[]="#";
	$f[]="# stop/start the URLfilterDB daemons";
	$f[]="#";
	$f[]="# All *nix flavors have different mechanisms for";
	$f[]="# stop/start scripts to give feedback and it is not supported.";
	$f[]="# So this script tries to give a simple and usable feedback";
	$f[]="# with the echo command only.";
	$f[]="#";
	$f[]="# chkconfig: 2345 89 26";
	$f[]="# description: ufdbguardd content filter from URLfilterDB";
	$f[]="# processname: ufdbguardd";
	$f[]="# config: /etc/sysconfig/ufdbguard";
	$f[]="#";
	$f[]="# This script should be in /etc/init.d, /sbin/init.d or equivalent.";
	$f[]="# From rc3.d there should be symbolic links to this script.";
	$f[]="# Suggested names to use in rc3.d are K26ufdb and S89ufdb.";
	$f[]="#";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides: ufdb";
	$f[]="# X-Start-Before: squid";
	$f[]="# Default-Start: 2 3 4 5";
	$f[]="# Default-Stop: 0 1 6";
	$f[]="# Required-Start: \$local_fs \$network \$named \$syslog";
	$f[]="# Required-Stop: \$local_fs \$network \$named \$syslog";
	$f[]="# Short-Description: ufdbguardd daemons from URLfilterDB";
	$f[]="# Description: content filter for Squid; ufdbguardd from URLfilterDB";
	$f[]="### END INIT INFO";
	$f[]="#";
	$f[]="# \$Id: ufdb.sh.in,v 1.17 2013/01/14 02:35:04 root Exp root \$";
	$f[]="";
	$f[]="";
	$f[]="# if /etc/sysconfig/ufdbguard exist, always use that file to set";
	$f[]="# options and do not edit this script.";
	$f[]="#";
	$f[]="if [ -r /etc/sysconfig/ufdbguard ]";
	$f[]="then";
	$f[]="   . /etc/sysconfig/ufdbguard";
	$f[]="else";
	$f[]="   CONFIGDIR=\"/etc/ufdbguard\"";
	$f[]="   BINDIR=\"/usr/bin\"";
	$f[]="";
	$f[]="   # Optional parameters";
	$f[]="   UFDB_OPTIONS=\"\"	";
	$f[]="";
	$f[]="   # Optionally use a non-root account to run the ufdbguardd and ufdbhttpd daemons";
	$f[]="   RUNAS=\"squid\"";
	$f[]="";
	$f[]="   # On some systems, regeluar expression matching is much faster with LANG=C";
	$f[]="   LANG=C";
	$f[]="   export LANG";
	$f[]="fi";
	$f[]="";
	$f[]="who=`whoami`";
	$f[]="msg=\"\"";
	$f[]="ENABLEUFDB=`cat /etc/artica-postfix/settings/Daemons/EnableUfdbGuard`";
	$f[]="";
	$f[]="# On some systems the C library has a malloc implementation which perform";
	$f[]="# allocation checks and this has a performance penalty. We disable the checks.";
	$f[]="unset MALLOC_CHECK_	# glibc";
	$f[]="unset MALLOC_OPTIONS	# BSD";
	$f[]="unset MALLOCTYPE	# AIX";
	$f[]="unset MALLOCOPTIONS	# AIX";
	$f[]="unset MALLOCDEBUG	# AIX";
	$f[]="unset UMEM_DEBUG	# Solaris";
	$f[]="unset MALLOC_DEBUG      # Solaris";
	$f[]="";
	$f[]="KERNEL=`uname -s`";
	$f[]="case \"\$KERNEL\" in";
	$f[]="    *NetBSD*)";
	$f[]="        PSALL=\"-al\" ;;";
	$f[]="    *FreeBSD*)";
	$f[]="        PSALL=\"-axj\" ;;";
	$f[]="    *OpenBSD*)";
	$f[]="        PSALL=\"-axj\" ;;";
	$f[]="    *)";
	$f[]="        # Linux and others";
	$f[]="        PSALL=\"-ef\" ;;";
	$f[]="esac";
	$f[]="";
	$f[]="case \"\$1\" in";
	$f[]="	start)";
	$f[]="		MYRUNLEVEL=\${RUNLEVEL:--1}";
	$f[]="		MYPREVLEVEL=\${PREVLEVEL:-notset}";
	$f[]="";
	$f[]="		# echo RUNLEVEL \$MYRUNLEVEL PREVLEVEL \$MYPREVLEVEL > /tmp/ufdb-runlevels";
	$f[]="		if [ \$ENABLEUFDB -eq 0 ]";
	$f[]="		then";
	$f[]="			echo \"Starting URLfilterDB daemons is disabled\"";
	$f[]="			$php ".__FILE__." --ufdbguard";
	$f[]="			return 1";
	$f[]="		fi";
	$f[]="";
	$f[]="		if [ -f /var/tmp/ufdbguardd.pid ]";
	$f[]="		   then";
	$f[]="		      xPIDS=`cat /var/tmp/ufdbguardd.pid`";
	$f[]="		      if [ -f \"/proc/\$xPIDS/status\" ]; then";
	$f[]="					echo \"Starting URLfilterDB daemons already running \$xPIDS\"";
	$f[]="					return 1";
	$f[]="			  fi";
	$f[]="		fi";
	$f[]="";
	$f[]="		if [ \$MYRUNLEVEL -ge 2  ]";
	$f[]="		then";
	$f[]="		   if [ \$MYPREVLEVEL = S  -o  \$MYPREVLEVEL = N ]";
	$f[]="		   then";
	$f[]="		      # system is booting so remove the old UNIX sockets";
	$f[]="		      rm -f /tmp/ufdbguardd-[0-9]*";
	$f[]="		   fi";
	$f[]="		fi";
	$f[]="";
	$f[]="		msg=\"Starting URLfilterDB daemons\"";
	$f[]="		if [ \"\$who\" = root  -a  \"\$RUNAS\" != \"\"  -a  \"\$RUNAS\" != root ]";
	$f[]="		then";
	$f[]="		   UFDB_RUNAS_PARAM=\"-U \$RUNAS\"";
	$f[]="		else";
	$f[]="		   UFDB_RUNAS_PARAM=\"\"";
	$f[]="	     fi";
	$f[]="";
	$f[]="	    $php /usr/share/artica-postfix/exec.squidguard.php --dbmem";
	$f[]="	    $php ".__FILE__." --ufdbguard";
	$f[]="	    /etc/init.d/artica-postfix start ufdb-tail";
	$f[]="		\$BINDIR/ufdbguardd \$UFDB_OPTIONS \$UFDB_RUNAS_PARAM -c \$CONFIGDIR/ufdbGuard.conf";
	$f[]="		exitcode=\$?";
	$f[]="		;;";
	$f[]="";
	$f[]="	stop)";
	$f[]="		msg=\"Shutting down URLfilterDB daemons\"";
	$f[]="	    $php ".__FILE__." --ufdbguard";
	$f[]="";
	$f[]="		if [ -x \$BINDIR/ufdbsignal ]";
	$f[]="		then";
	$f[]="		   \$BINDIR/ufdbsignal -C \"sigterm ufdbguardd\"";
	$f[]="		   exitcode=\$?";
	$f[]="	        else";
	$f[]="		   PIDS=\"\"";
	$f[]="		   if [ -f /var/tmp/ufdbguardd.pid ]";
	$f[]="		   then";
	$f[]="		      PIDS=`cat /var/tmp/ufdbguardd.pid`";
	$f[]="		      CHECK=`ps -p \"\$PIDS\" 2>/dev/null | grep ufdbguardd`";
	$f[]="		      if [ \"\$CHECK\" = \"\" ]";
	$f[]="		      then ";
	$f[]="			 PIDS=\"\"";
	$f[]="		      fi";
	$f[]="		   fi";
	$f[]="		   if [ \"\$PIDS\" = \"\" ]";
	$f[]="		   then";
	$f[]="		      PIDS=`ps \$PSALL | grep ufdbguardd | grep -v grep | awk '{ print \$2 }' `";
	$f[]="		   fi";
	$f[]="";
	$f[]="		   exitcode=0";
	$f[]="		   if [ \"\$PIDS\" != \"\" ]";
	$f[]="		   then";
	$f[]="		      kill -TERM \$PIDS";
	$f[]="		      exitcode=\$?";
	$f[]="		   fi";
	$f[]="	        fi";
	$f[]="";
	$f[]="		sleep 1    # give the daemon some time to do its shutdown procedure";
	$f[]="";
	$f[]="		PIDS=\"\"";
	$f[]="		if [ -f /var/tmp/ufdbhttpd.pid ]";
	$f[]="		then";
	$f[]="		   if [ -x \$BINDIR/ufdbsignal ]";
	$f[]="		   then";
	$f[]="		      \$BINDIR/ufdbsignal -C \"sigterm ufdbhttpd\"";
	$f[]="		      exitcode=\$?";
	$f[]="		   else";
	$f[]="		      PIDS=`cat /var/tmp/ufdbhttpd.pid`";
	$f[]="		      CHECK=`ps -p \"\$PIDS\" 2>/dev/null | grep ufdbhttpd`";
	$f[]="		      if [ \"\$CHECK\" = \"\" ]";
	$f[]="		      then ";
	$f[]="			 PIDS=\"\"";
	$f[]="		      fi";
	$f[]="		   fi";
	$f[]="	        fi";
	$f[]="		if [ \"\$PIDS\" = \"\" ]";
	$f[]="		then";
	$f[]="		   PIDS=`ps \$PSALL | grep ufdbhttpd | grep -v grep | awk '{ print \$2 }' `";
	$f[]="		fi";
	$f[]="";
	$f[]="		exitcode=0";
	$f[]="		if [ \"\$PIDS\" != \"\" ]";
	$f[]="		then";
	$f[]="		   kill -TERM \$PIDS";
	$f[]="		   exitcode=\$?";
	$f[]="	        fi";
	$f[]="";
	$f[]="		rm -f /tmp/ufdbguardd-[0-9][0-9][0-9][0-9][0-9]";
	$f[]="		;;";
	$f[]="";
	$f[]="	kill)";
	$f[]="		msg=\"Killing URLfilterDB daemons\"";
	$f[]="";
	$f[]="		if [ -x \$BINDIR/ufdbsignal ]";
	$f[]="		then";
	$f[]="		   \$BINDIR/ufdbsignal -C \"sigkill ufdbguardd\"";
	$f[]="		   exitcode=\$?";
	$f[]="	        else";
	$f[]="		   PIDS=\"\"";
	$f[]="		   if [ -f /var/tmp/ufdbguardd.pid ]";
	$f[]="		   then";
	$f[]="		      PIDS=`cat /var/tmp/ufdbguardd.pid`";
	$f[]="		      CHECK=`ps -p \"\$PIDS\" 2>/dev/null | grep ufdbguardd`";
	$f[]="		      if [ \"\$CHECK\" = \"\" ]";
	$f[]="		      then ";
	$f[]="			 PIDS=\"\"";
	$f[]="		      fi";
	$f[]="		   fi";
	$f[]="		   if [ \"\$PIDS\" = \"\" ]";
	$f[]="		   then";
	$f[]="		      PIDS=`ps \$PSALL | grep ufdbguardd | grep -v grep | awk '{ print \$2 }' `";
	$f[]="		   fi";
	$f[]="";
	$f[]="		   if [ \"\$PIDS\" != \"\" ]";
	$f[]="		   then";
	$f[]="		      kill -KILL \$PIDS";
	$f[]="		      exitcode=\$?";
	$f[]="		      rm -f /var/tmp/ufdbguardd.pid";
	$f[]="		      sleep 1";
	$f[]="		   fi";
	$f[]="	        fi";
	$f[]="";
	$f[]="		PIDS=\"\"";
	$f[]="		if [ -f /var/tmp/ufdbhttpd.pid ]";
	$f[]="		then";
	$f[]="		   if [ -x \$BINDIR/ufdbsignal ]";
	$f[]="		   then";
	$f[]="		      \$BINDIR/ufdbsignal -C \"sigkill ufdbhttpd\"";
	$f[]="		      exitcode=\$?";
	$f[]="		   else";
	$f[]="		      PIDS=`cat /var/tmp/ufdbhttpd.pid`";
	$f[]="		      CHECK=`ps -p \"\$PIDS\" 2>/dev/null | grep ufdbhttpd`";
	$f[]="		      if [ \"\$CHECK\" = \"\" ]";
	$f[]="		      then ";
	$f[]="			 PIDS=\"\"";
	$f[]="		      fi";
	$f[]="		   fi";
	$f[]="	        fi";
	$f[]="		if [ \"\$PIDS\" = \"\" ]";
	$f[]="		then";
	$f[]="		   PIDS=`ps \$PSALL | grep ufdbhttpd | grep -v grep | awk '{ print \$2 }' `";
	$f[]="		fi";
	$f[]="";
	$f[]="		if [ \"\$PIDS\" != \"\" ]";
	$f[]="		then";
	$f[]="		   kill -KILL \$PIDS";
	$f[]="		   exitcode=\$?";
	$f[]="		   rm -f /var/tmp/ufdbhttpd.pid";
	$f[]="	        fi";
	$f[]="";
	$f[]="		rm -f /tmp/ufdbguardd-[0-9][0-9][0-9][0-9][0-9]";
	$f[]="		;;";
	$f[]="";
	$f[]="	reconfig|reload)";
	$f[]="	    $php /usr/share/artica-postfix/exec.squidguard.php --dbmem";
	$f[]="		if [ -x \$BINDIR/ufdbsignal  -a  -f /var/tmp/ufdbguardd.pid ]";
	$f[]="		then";
	$f[]="		   \$BINDIR/ufdbsignal -C \"sighup ufdbguardd\"";
	$f[]="		   exitcode=\$?";
	$f[]="	        else";
	$f[]="		   PIDS=\"\"";
	$f[]="		   if [ -f /var/tmp/ufdbguardd.pid ]";
	$f[]="		   then";
	$f[]="		      PIDS=`cat /var/tmp/ufdbguardd.pid`";
	$f[]="		      CHECK=`ps -p \"\$PIDS\" 2>/dev/null | grep ufdbguardd`";
	$f[]="		      if [ \"\$CHECK\" = \"\" ]";
	$f[]="		      then ";
	$f[]="			 PIDS=\"\"";
	$f[]="		      fi";
	$f[]="		   fi";
	$f[]="		   if [ \"\$PIDS\" = \"\" ]";
	$f[]="		   then";
	$f[]="		      PIDS=`ps \$PSALL | grep ufdbguardd | grep -v grep | awk '{ print \$2 }' `";
	$f[]="		   fi";
	$f[]="";
	$f[]="		   if [ \"\$PIDS\" != \"\" ]";
	$f[]="		   then";
	$f[]="		      echo \"Sending HUP signal to URLfilterDB daemons to reconfigure\"";
	$f[]="		      kill -HUP \$PIDS";
	$f[]="		   fi";
	$f[]="		   exitcode=0";
	$f[]="	        fi";
	$f[]="		;;";
	$f[]="";
	$f[]="	rotatelog)";
	$f[]="		if [ -x \$BINDIR/ufdbsignal ]";
	$f[]="		then";
	$f[]="		   \$BINDIR/ufdbsignal -C \"sigusr1 ufdbguardd\"";
	$f[]="		   exitcode=\$?";
	$f[]="	        else";
	$f[]="		   PIDS=\"\"";
	$f[]="		   if [ -f /var/tmp/ufdbguardd.pid ]";
	$f[]="		   then";
	$f[]="		      PIDS=`cat /var/tmp/ufdbguardd.pid`";
	$f[]="		      CHECK=`ps -p \"\$PIDS\" 2>/dev/null | grep ufdbguardd`";
	$f[]="		      if [ \"\$CHECK\" = \"\" ]";
	$f[]="		      then ";
	$f[]="			 PIDS=\"\"";
	$f[]="		      fi";
	$f[]="		   fi";
	$f[]="		   if [ \"\$PIDS\" = \"\" ]";
	$f[]="		   then";
	$f[]="		      PIDS=`ps \$PSALL | grep ufdbguardd | grep -v grep | awk '{ print \$2 }' `";
	$f[]="		   fi";
	$f[]="";
	$f[]="		   if [ \"\$PIDS\" != \"\" ]";
	$f[]="		   then";
	$f[]="		      if tty -s ";
	$f[]="		      then";
	$f[]="			 echo \"Sending USR1 signal to URLfilterDB daemons to rotate the log file\"";
	$f[]="		      fi";
	$f[]="		      kill -USR1 \$PIDS";
	$f[]="		   fi";
	$f[]="		   exitcode=0";
	$f[]="	        fi";
	$f[]="		;;";
	$f[]="";
	$f[]="	testconfig)";
	$f[]="		\$BINDIR/ufdbguardd \$UFDB_OPTIONS \$UFDB_RUNAS_PARAM -c \$CONFIGDIR/ufdbGuard.conf -C verify";
	$f[]="		exitcode=\$?";
	$f[]="		;;";
	$f[]="";
	$f[]="	monitor)";
	$f[]="		if [ -x \$BINDIR/ufdbsignal  -a  -f /var/tmp/ufdbguardd.pid ]";
	$f[]="		then";
	$f[]="		   if tty -s";
	$f[]="		   then";
	$f[]="		      echo \"Sending USR2 signal to URLfilterDB daemons to rotate the log file\"";
	$f[]="		   fi";
	$f[]="		   \$BINDIR/ufdbsignal -C \"sigusr2 ufdbguardd\"";
	$f[]="		   exitcode=\$?";
	$f[]="	        else";
	$f[]="		   PIDS=\"\"";
	$f[]="		   if [ -f /var/tmp/ufdbguardd.pid ]";
	$f[]="		   then";
	$f[]="		      PIDS=`cat /var/tmp/ufdbguardd.pid`";
	$f[]="		      CHECK=`ps -p \"\$PIDS\" 2>/dev/null | grep ufdbguardd`";
	$f[]="		      if [ \"\$CHECK\" = \"\" ]";
	$f[]="		      then ";
	$f[]="			 PIDS=\"\"";
	$f[]="		      fi";
	$f[]="		   fi";
	$f[]="		   if [ \"\$PIDS\" = \"\" ]";
	$f[]="		   then";
	$f[]="		      PIDS=`ps \$PSALL | grep ufdbguardd | grep -v grep | awk '{ print \$2 }' `";
	$f[]="		   fi";
	$f[]="";
	$f[]="		   if [ \"\$PIDS\" != \"\" ]";
	$f[]="		   then";
	$f[]="		      if tty -s ";
	$f[]="		      then";
	$f[]="			 echo \"Sending USR2 signal to URLfilterDB daemons to trigger monitoring update\"";
	$f[]="		      fi";
	$f[]="		      kill -USR2 \$PIDS";
	$f[]="		   fi";
	$f[]="		   exitcode=0";
	$f[]="	        fi";
	$f[]="		;;";
	$f[]="";
	$f[]="	condrestart|try-restart|restart)";
	$f[]="		\$0 stop";
	$f[]="		sleep 2";
	$f[]="		\$0 start";
	$f[]="		exitcode=\$?";
	$f[]="		;;";
	$f[]="";
	$f[]="	status)";
	$f[]="		# Redhat/Fedora guidelines for exit codes:";
	$f[]="		# 0:	program is running or service is OK";
	$f[]="		# 1:	program is dead and /var/run pid file exists";
	$f[]="		# 2:	program is dead and /var/lock lock file exists";
	$f[]="		# 3:	program is not running";
	$f[]="		echo \"Checking for URLfilterDB daemons\"";
	$f[]="		PROC=`ps \$PSALL | grep -e ufdbguardd | grep -v grep`";
	$f[]="		if [ \"\$PROC\" = \"\" ]";
	$f[]="		then";
	$f[]="		   exitcode=3";
	$f[]="	        else";
	$f[]="		   exitcode=0";
	$f[]="		fi";
	$f[]="		;;";
	$f[]="";
	$f[]="	*)";
	$f[]="		echo \"Usage: \$0 <start|stop|status|restart|condrestart|try-restart|testconfig|monitor|reconfig|rotatelog|kill>\"";
	$f[]="		exit 1";
	$f[]="		;;";
	$f[]="esac";
	$f[]="";
	$f[]="if [ \"\$msg\" != \"\" ]";
	$f[]="then";
	$f[]="   if [ \$exitcode -eq 0 ]";
	$f[]="   then";
	$f[]="      echo \"\$msg OK\"";
	$f[]="   else";
	$f[]="      echo \"\$msg FAIL\"";
	$f[]="   fi";
	$f[]="fi";
	$f[]="";
	$f[]="exit \$exitcode";
	$f[]="";
	
	$INITD_PATH="/etc/init.d/ufdb";
	echo "slapd: [INFO] Writing /etc/init.d/ufdb with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);
	
	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 2345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}	
		
}
