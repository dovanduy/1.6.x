<?php
if(is_file("/etc/artica-postfix/FROM_ISO")){$GLOBALS["PHP5_BIN_PATH"]="/usr/bin/php5";}
$GLOBALS["VERBOSE"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["MONIT"]=false;
$GLOBALS["BY_FRAMEWORK"]=null;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--monit#",implode(" ",$argv))){$GLOBALS["MONIT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--framework=(.+?)$#",implode(" ",$argv),$re)){$GLOBALS["BY_FRAMEWORK"]=$re[1];}
if($GLOBALS["VERBOSE"]){echo "Starting in verbose mode\n";}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");

if($GLOBALS["VERBOSE"]){echo "Starting analyze command lines\n";$GLOBALS["OUTPUT"]=true;}
if($argv[1]=="syslog-deb"){die();}
if($argv[1]=="--ldapd-conf"){ldap_conf();die();}
if($argv[1]=="--dnsmasq"){dnsmasq_init_debian();die();}
if($argv[1]=="--nscd"){nscd_init_debian();die();}
if($argv[1]=="--rsyslogd-init"){rsyslogd_init();exit;}
if($argv[1]=="--start"){start_ldap();exit;}
if($argv[1]=="--stop"){stop_ldap();exit;}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart_ldap();exit;}
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
if($argv[1]=="--squidguard-http"){squidguard_http();exit;}
if($argv[1]=="--apache"){apache();exit;}
if($argv[1]=="--cntlm"){cntlm();cntlm_parent();exit;}
if($argv[1]=="--postfix"){postfix();exit;}
if($argv[1]=="--auth-tail"){auth_tail();exit;}
if($argv[1]=="--roundcube"){roundcube_http();exit;}
if($argv[1]=="--spawnfcgi"){spawnfcgi();exit;}
if($argv[1]=="--fetchmail"){fetchmail();exit;}
if($argv[1]=="--pdns"){pdns();exit;}
if($argv[1]=="--snmpd"){snmpd();exit;}
if($argv[1]=="--stunnel"){stunnel();exit;}
if($argv[1]=="--iscsi"){iscsitarget();exit;}
if($argv[1]=="--milter-greylist"){milter_greylist();exit;}
if($argv[1]=="--vde-switch"){vde_switch();exit;}
if($argv[1]=="--vnstat"){vnstat();exit;}
if($argv[1]=="--rdpproxy"){rdpproxy();exit;}
if($argv[1]=="--rdpproxy-authhook"){rdpproxy_authhook();exit;}
if($argv[1]=="--winbind"){winbind();exit;}
if($argv[1]=="--artica-status"){artica_status();exit;}
if($argv[1]=="--process1"){process1();exit;}
if($argv[1]=="--clamav"){clamav_daemon();exit;}
if($argv[1]=="--freshclam"){clamav_freshclam();exit;}
if($argv[1]=="--shorewall-db"){shorewall_db();exit;}
if($argv[1]=="--snmpd"){snmpd();exit;}
if($argv[1]=="--haproxy"){haproxy();exit;}
if($argv[1]=="--saslauthd"){saslauthd();exit;}
if($argv[1]=="--webservices"){webservices();exit;}
if($argv[1]=="--opendkim"){opendkim();exit;}
if($argv[1]=="--squid-nat"){squidnat();exit;}
if($argv[1]=="--ntopng"){ntopng();redis_server();exit;}
if($argv[1]=="--squid-stream"){squidstream();squidstream_scheduler();exit;}
if($argv[1]=="--zipproxy"){zipproxy();exit;}
if($argv[1]=="--squid-db"){$GLOBALS["OUTPUT"]=true;squid_db();exit;}
if($argv[1]=="--iredmail"){$GLOBALS["OUTPUT"]=true;iredmail();exit;}
if($argv[1]=="--sarg"){$GLOBALS["OUTPUT"]=true;sarg();exit;}
if($argv[1]=="--squid-stats-central"){$GLOBALS["OUTPUT"]=true;squid_stats_central();exit;}
if($argv[1]=="--wifidog"){$GLOBALS["OUTPUT"]=true;wifidog();exit;}
if($argv[1]=="--kav4proxy"){$GLOBALS["OUTPUT"]=true;kav4proxy();exit;}
if($argv[1]=="--l7filter"){$GLOBALS["OUTPUT"]=true;l7filter();exit;}
if($argv[1]=="--netdiscover"){$GLOBALS["OUTPUT"]=true;netdiscover();exit;}
if($argv[1]=="--ufdbcat"){$GLOBALS["OUTPUT"]=true;ufdbcat();exit;}
if($argv[1]=="--sarg-web"){$GLOBALS["OUTPUT"]=true;sargweb();exit;}
if($argv[1]=="--syncthing"){$GLOBALS["OUTPUT"]=true;syncthing();exit;}
if($argv[1]=="--hypercache-web"){$GLOBALS["OUTPUT"]=true;hypercache_http();exit;}



$unix=new unix();





	if($GLOBALS["VERBOSE"]){echo "Open unix class\n";}
	
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
	$pid=$unix->get_pid_from_file($PID_FILE);
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid,120);
		echo "slapd: [INFO] Already executed pid $pid since {$timepid}mn\n";
		die();
	}

@file_put_contents($PID_FILE, getmypid());
$GLOBALS["OUTPUT"]=true;
$functions=array("artica_syslog","squid_stats_central","vsftpd","wifidog","sarg","irqbalance","artica_firewall","artica_postfix","artica_openssh","artica_web_hotspot","artica_fw_hotspot",
		"haproxy","specialreboot","buildscript","artica_status","mysqlInit","remove_nested_services","netdiscover",
"conntrackd","process1","monit","dnsmasq_init_debian","nscd_init_debian","wsgate_init_debian","amavis","ufdbcat",
		"buildscriptSpamass_milter","buildscriptLoopDisk","buildscriptFreeRadius","pdns_recursor","l7filter",
"ifup","ftpproxy","failover","framework","webservices","ufdbguard","ufdbguard_client","phppfm","kav4proxy",
		"apache","artica_webconsole","memcached","nginx","dhcpd","cicap","vnstat","arpd","haarp","saslauthd","rsyslogd_init","CleanUbuntu","UpstartJob","squidguard_http","debian_mirror","artica_categories","cntlm","cntlm_parent","postfix","ufdb_tail","auth_tail","roundcube_http","spawnfcgi","fetchmail","squidnat","squidstream","squidstream_scheduler","pdns","snmpd","stunnel","iscsitarget","vde_switch","rdpproxy","winbind",
"clamav_daemon","shorewall_db","squid_db","zipproxy","rsyslogd_init","clamav_freshclam","ntopng","redis_server","cyrus_imapd",
		"iredmail","artica_iso","syncthing","hypercache_http");	

$countDeFunc=count($functions);
$c=0;
while (list ($num, $func) = each ($functions) ){
	$c++;
	$prc=($c/$countDeFunc)*100;
	$prc=round($prc);
	echo "\n";
	echo "{$prc}%: [INFO] Building $func() init script function\n";
	if(!function_exists($func)){continue;}
	
	try {
		$results=call_user_func($func);
	}catch (Exception $e) {
		echo "[!!!]: ERROR while running function $func ($e)\n";
	}		

}


echo "100%: [INFO] success terminated\n";
	
function artica_categories(){
	
	if(is_file("/etc/init.d/categories-db")){
		$unix=new unix();
		$php=$unix->LOCATE_PHP5_BIN();
		$nohup=$unix->find_program("nohup");
		shell_exec("$nohup ".dirname(__FILE__)."/exec.uninstall.catzdb.php >/dev/null 2>&1 &");		
	}

}
	
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

function restart_ldap_progress($text,$pourc){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/openldap.progress", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/web/openldap.progress",0755);

}

function restart_ldap(){
	$unix=new unix();
	$MYPID_FILE="/etc/artica-postfix/pids/restart_ldap.pid";
	$pid=$unix->get_pid_from_file($MYPID_FILE);
	if($unix->process_exists($pid,basename(__FILE__))){
		restart_ldap_progress("{failed}",110);
		echo "slapd: [INFO] Artica task already running pid $pid\n";
		die();
	}
	
	
	
	
	
	if(!$GLOBALS["FORCE"]){
		$lastexecution=$unix->file_time_min($MYPID_FILE);
		if($lastexecution==0){
			$unix->ToSyslog("Restarting the OpenLDAP by `{$GLOBALS["BY_FRAMEWORK"]}` aborted this command must be executed minimal each 1mn",false,basename(__FILE__));
			echo "slapd: [INFO] this command must be executed minimal each 1mn\n";
			die();
		}
	}
	
	
	@unlink($MYPID_FILE);
	
	@file_put_contents($MYPID_FILE, getmypid());
	$unix->ToSyslog("Restarting the OpenLDAP daemon by `{$GLOBALS["BY_FRAMEWORK"]}`",false,basename(__FILE__));
	restart_ldap_progress("{stopping_service}",10);
	
	stop_ldap(true);
	
	restart_ldap_progress("{starting_service}",40);
	start_ldap(true);
}

function start_ldap($aspid=false){
	$sock=new sockets();
	$ldaps=array();
	$unix=new unix();
	$kill=$unix->find_program("kill");
	
	if(!$GLOBALS["FORCE"]){
		$pid=$unix->get_pid_from_file('/etc/artica-postfix/pids/exec.backup.artica.php.restore.pid');
		if($unix->process_exists($pid)){
			$pidtime=$unix->PROCCESS_TIME_MIN($pid);
			if($pidtime<15){
				echo "slapd: [INFO] Artica restore task already running pid $pid since {$pidtime}mn\n";
				restart_ldap_progress("{success}",100);
				return;
			}
		}
	}
	
	
	$MYPID_FILE="/etc/artica-postfix/pids/start_ldap.pid";
	if(!$aspid){
		$pid=$unix->get_pid_from_file($MYPID_FILE);
		if($unix->process_exists($pid,basename(__FILE__))){
			$pidtime=$unix->PROCCESS_TIME_MIN($pid);
			$unix->ToSyslog("Artica task already running pid $pid since {$pidtime}mn",false,basename(__FILE__));
			echo "slapd: [INFO] Artica task already running pid $pid since {$pidtime}mn\n";
			if($pidtime>10){
				echo "slapd: [INFO] Killing this Artica task...\n";
				unix_system_kill_force($pid);
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
	
	$pid=$unix->get_pid_from_file($SLAPD_PID_FILE);
	if($unix->process_exists($pid)){
		$pidtime=$unix->PROCCESS_TIME_MIN($pid);
		restart_ldap_progress("{success}",100);
		echo "slapd: [INFO] slapd already running pid $pid since {$pidtime}mn\n";
		@file_put_contents($SLAPD_PID_FILE, $pid);
		return;
	}
	
	$pid=$unix->PIDOF_PATTERN($slapd);
	echo "slapd: [INFO] detecting presence of `$slapd`:$pid...\n";
	if($unix->process_exists($pid)){
		$pidtime=$unix->PROCCESS_TIME_MIN($pid);
		restart_ldap_progress("{success}",100);
		echo "slapd: [INFO] slapd already running pid $pid since {$pidtime}mn\n";
		@file_put_contents($SLAPD_PID_FILE, $pid);
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
	
	$ifconfig=$unix->find_program("ifconfig");
	echo "slapd: [INFO] start looback address...\n";
	shell_exec("$ifconfig lo 127.0.0.1 netmask 255.255.255.0 up >/dev/null 2>&1");
		
	$ldap[]="ldapi://". urlencode("/var/run/slapd/slapd.sock");
	$ldap[]="ldap://127.0.0.1:389/";
	if(is_file("/etc/artica-postfix/settings/Daemons/LdapListenIPAddr")){
		$LdapListenIPAddr=explode("\n",@file_get_contents("/etc/artica-postfix/settings/Daemons/LdapListenIPAddr"));
		while (list ($num, $ipaddr) = each ($LdapListenIPAddr)){
			$ipaddr=trim($ipaddr);
			if($ipaddr==null){continue;}
			echo "slapd: [INFO] slapd listen `$ipaddr`\n";
			
			if(!$unix->IS_IPADDR_EXISTS($ipaddr)){
				echo "slapd: [INFO] slapd `$ipaddr` does not exists\n";
				continue;
			}
			
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
	restart_ldap_progress("{starting_service}",50);
	while (list ($num, $file) = each ($shemas) ){
		if(is_file("/usr/share/artica-postfix/bin/install/$file")){
			if(is_file("$LDAP_SCHEMA_PATH/$file")){@unlink("$LDAP_SCHEMA_PATH/$file");}
			@copy("/usr/share/artica-postfix/bin/install/$file", "$LDAP_SCHEMA_PATH/$file");
			echo "slapd: [INFO] installing `$file` schema\n";
			$unix->chmod_func(0777,"$LDAP_SCHEMA_PATH/$file");
		}
	}
		 

	
	if(file_exists($ulimit)){
		shell_exec("$ulimit -HSd unlimited");
	}
	
	restart_ldap_progress("{starting_service}",60);
	if(is_dir("/usr/share/phpldapadmin/config")){
		$phpldapadmin="$php5 ".dirname(__FILE__)."/exec.phpldapadmin.php --build >/dev/null 2>&1";
		echo "slapd: [INFO] please wait, configuring PHPLdapAdminservice... \n";
		shell_exec($phpldapadmin);
	}	
	
	echo "slapd: [INFO] please wait, configuring the daemon...\n";
	
	if($unix->MEM_TOTAL_INSTALLEE()<624288){
		$sock=new sockets();
		$sock->SET_INFO("SlapdThreads", 2);
	}
	
	restart_ldap_progress("{starting_service}",70);
	@chmod("/usr/share/artica-postfix/bin/artica-install",0755);
	shell_exec("/usr/share/artica-postfix/bin/artica-install --slapdconf");
	
	echo "slapd: [INFO] please wait, building the start script...\n";
	buildscript();
	$unix->ToSyslog("Launching the OpenLDAP daemon ",false,basename(__FILE__));
	echo "slapd: [INFO] please wait, Launching the daemon...\n";
	
	if(!$unix->NETWORK_INTERFACE_OK("lo")){
		$ifconfig=$unix->find_program("ifconfig");
		shell_exec("$ifconfig lo 127.0.0.1 netmask 255.255.255.0 up >/dev/null 2>&1");
	}

	restart_ldap_progress("{starting_service}",80);
	$cdmline="$nohup $slapd$v4 -h \"$SLAPD_SERVICES\" -f $SLAPD_CONF -u root -g root -l local4$OpenLDAPLogLevelCmdline >/dev/null 2>&1 &";
	shell_exec($cdmline);
	sleep(1);
	
	for($i=0;$i<5;$i++){
		$pid=$unix->get_pid_from_file($SLAPD_PID_FILE);
		if($unix->process_exists($pid)){
			$pidtime=$unix->PROCCESS_TIME_MIN($pid);
			echo "slapd: [INFO] slapd success Running pid $pid\n";
			restart_ldap_progress("{success}",100);
			if($users->ZARAFA_INSTALLED){start_zarafa();}
			return;
		}
			
		$pid=$unix->PIDOF($slapd);
		if($unix->process_exists($pid)){
			$pidtime=$unix->PROCCESS_TIME_MIN($pid);
			echo "slapd: [INFO] slapd success Running pid $pid\n";
			if($users->ZARAFA_INSTALLED){start_zarafa();}
			restart_ldap_progress("{success}",100);
			return;
		}
		echo "slapd: [INFO] please wait, waiting service to start...\n";
		sleep(1);
				
	}
	restart_ldap_progress("{failed}",110);
	echo "slapd: [ERR ] Failed to start the service with `$cdmline`\n";
	
}

function xsyslog($text){
	echo $text."\n";
	if(function_exists("WriteToSyslogMail")){WriteToSyslogMail($text, basename(__FILE__));}

}


function stop_ldap($aspid=false){
	

	if($GLOBALS["MONIT"]){
		xsyslog("Not accept a stop order from MONIT process");
		return;}
	
	$sock=new sockets();
	$users=new usersMenus();
	$ldaps=array();
	$unix=new unix();
	$kill=$unix->find_program("kill");
	$slapd=$unix->find_program("slapd");
	$pgrep=$unix->find_program("pgrep");
	$SLAPD_PID_FILE=$unix->SLAPD_PID_PATH();
	$MYPID_FILE="/etc/artica-postfix/pids/stop_ldap.pid";
	if($users->ZARAFA_INSTALLED){stop_zarafa();}
	
	if(!$aspid){
		$pid=$unix->get_pid_from_file($MYPID_FILE);
		if($unix->process_exists($pid,basename(__FILE__))){
				$pidtime=$unix->PROCCESS_TIME_MIN($pid);
				echo "slapd: [INFO] Artica task already running pid $pid since {$pidtime}mn\n";
				if($pidtime>10){
				echo "slapd: [INFO] Killing this Artica task...\n";
				unix_system_kill_force($pid);
			}else{die();}
		}
	
		@unlink($MYPID_FILE);
		@file_put_contents($MYPID_FILE, getmypid());
	}
	
	$pid=$unix->get_pid_from_file($SLAPD_PID_FILE);
	
	
	
	
	$pid=$unix->get_pid_from_file($SLAPD_PID_FILE);
	if($unix->process_exists($pid)){
		$timeDaemon=$unix->PROCESS_TTL($pid);
		$unix->ToSyslog("Stopping the OpenLDAP daemon running since {$timeDaemon}Mn",false,basename(__FILE__));
		echo "slapd: [INFO] slapd shutdown ldap server PID:$pid...\n";
		unix_system_kill($pid);
	}else{
		$pid=$unix->PIDOF($slapd);
		if($unix->process_exists($pid)){
			echo "slapd: [INFO] slapd shutdown ldap server PID:$pid...\n";
			unix_system_kill($pid);
		}
	}
	
	
	
	for($i=0;$i<10;$i++){
		$pid=intval($unix->get_pid_from_file($SLAPD_PID_FILE));
		if($pid==0){break;}
		restart_ldap_progress("{stopping_service} stop PID:$pid",20);
		if($unix->process_exists($pid)){echo "slapd: [INFO] slapd waiting the server to stop PID:$pid...\n";sleep(1);continue;}
		$pid=$unix->PIDOF($slapd);
		if($unix->process_exists($pid)){echo "slapd: [INFO] slapd waiting the server to stop PID:$pid...\n";sleep(1);continue;}		
		
	}
	
	$pid=$unix->get_pid_from_file($SLAPD_PID_FILE);
	if($unix->process_exists($pid)){
		echo "slapd: [INFO] slapd PID:$pid still exists, kill it...\n";
		unix_system_kill_force($pid);
	}
	
	$pid=$unix->get_pid_from_file($SLAPD_PID_FILE);
	if($unix->process_exists($pid)){
		echo "slapd: [INFO] slapd PID:$pid still exists, start the force kill procedure...\n";
	}	
	
	restart_ldap_progress("{stopping_service} Checking $slapd",25);
	$pid=$unix->PIDOF($slapd);
	if($unix->process_exists($pid)){
		echo "slapd: [INFO] slapd PID:$pid still exists, kill it...\n";
		unix_system_kill_force($pid);
		return;
	}
	restart_ldap_progress("{stopping_service} Checking $slapd",28);
	exec("$pgrep -l -f $slapd 2>&1",$results);
	while (list ($num, $line) = each ($results) ){
		if(preg_match("#pgrep#", $line)){continue;}
		if(preg_match("^([0-9]+)\s+", $line,$re)){
			echo "slapd: [INFO] slapd PID:{$re[1]} still exists, kill it\n";
			unix_system_kill_force($re[1]);
			
		}
		
	}
	
	
	restart_ldap_progress("{stopping_service} {success}",30);
	echo "slapd: [INFO] slapd stopped, success...\n";
	
}

function artica_iso(){
	$unix=new unix();
	$INITD_PATH="/etc/init.d/artica-iso";
	if(!is_file($INITD_PATH)){return;}
	if(!is_file("/etc/artica-postfix/artica-as-rebooted")){return;}
	
	echo "artica-iso: [INFO] Removing startup $INITD_PATH script...\n";
	
	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");
		@unlink($INITD_PATH);
	}
	
	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." of >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");
		@unlink($INITD_PATH);
	}	
}


function squid_stats_central(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/artica-proxy-statistics";
	$php5script="exec.squid-stats-central-service.php";
	$daemonbinLog="Artica statistics Dameon";



	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         ".basename($INITD_PATH);
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
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";

	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure|reload} (+ '--verbose' for more infos)\"";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}



}



function vsftpd(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/vsftpd";
	$php5script="exec.vsftpd.php";
	$daemonbinLog="Very Secure FTP Dameon";



	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         ".basename($INITD_PATH);
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
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure|reload} (+ '--verbose' for more infos)\"";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}



}




function artica_firewall(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/artica-firewall";
	$php5script="exec.firewall.php";
	$daemonbinLog="Artica Iptables FireWall";
	
	
	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         ".basename($INITD_PATH);
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
	$f[]="    if [ -e /bin/artica-firewall.sh ]; then";
	$f[]="    	/bin/artica-firewall.sh";
	$f[]="	  fi";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --remove \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
	$f[]="    if [ -e /bin/artica-firewall.sh ]; then";
	$f[]="    	/bin/artica-firewall.sh";
	$f[]="	  fi";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reconfigure)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
	$f[]="    if [ -e /bin/artica-firewall.sh ]; then";
	$f[]="    	/bin/artica-firewall.sh";
	$f[]="	  fi";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure|reload} (+ '--verbose' for more infos)\"";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	
	
	
}

function artica_postfix(){
	
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          artica-postfix";
	$f[]="# Required-Start:    \$local_fs \$syslog";
	$f[]="# Required-Stop:     \$local_fs \$syslog";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Artica-Postfix daemon";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Manage the Artica postfix daemons.";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="   /usr/share/artica-postfix/bin/artica-install -watchdog \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="   /usr/share/artica-postfix/bin/artica-install -shutdown \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="   /usr/share/artica-postfix/bin/artica-install -shutdown \$2 \$3";
	$f[]="   /usr/share/artica-postfix/bin/artica-install -shutdown \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="   /usr/share/artica-postfix/bin/artica-install -shutdown \$2 \$3";
	$f[]="   /usr/share/artica-postfix/bin/artica-install -shutdown \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	
	$INITD_PATH="/etc/init.d/artica-postfix";
	echo "INIT: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);
	
	if(is_file('/usr/sbin/update-rc.d')){
	shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
			shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
}









function start_zarafa(){
	shell_exec("/etc/init.d/zarafa-server start");
}
function stop_zarafa(){
	shell_exec("/etc/init.d/zarafa-server stop");
}
function amavis(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$daemonbin=$unix->find_program("postconf");

	if(!is_file($daemonbin)){return;}
	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          amavis";
	$f[]="# Required-Start:    \$local_fs \$syslog";
	$f[]="# Required-Stop:     \$local_fs \$syslog";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Postfix daemon";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Extensible, configurable Postfix MTA";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="   $php ".dirname(__FILE__)."/exec.amavis.php --start \$2 \$3";

	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="   $php ".dirname(__FILE__)."/exec.amavis.php --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";

	$f[]="   $php ".dirname(__FILE__)."/exec.amavis.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="   $php ".dirname(__FILE__)."/exec.amavis.php --reload \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";

	$INITD_PATH="/etc/init.d/amavis";
	echo "amavis: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}

}


function postfix(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$daemonbin=$unix->find_program("postconf");
	
	if(!is_file($daemonbin)){return;}
	postfix_logger();
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          postfix";
	$f[]="# Required-Start:    \$local_fs \$syslog";
	$f[]="# Required-Stop:     \$local_fs \$syslog";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Postfix daemon";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Extensible, configurable Postfix MTA";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="   $php ".dirname(__FILE__)."/exec.status.php --xmail";
	$f[]="   $php ".dirname(__FILE__)."/exec.postfix.php --start \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="   $php ".dirname(__FILE__)."/exec.postfix.php --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="   $php ".dirname(__FILE__)."/exec.status.php --xmail";
	$f[]="   $php ".dirname(__FILE__)."/exec.postfix.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="   $php ".dirname(__FILE__)."/exec.postfix.php --reload \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";

	$INITD_PATH="/etc/init.d/postfix";
	echo "freeradius: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}

}

function remove_nested_services(){
	
	$f[]="/etc/init.d/bind9";
	$f[]="/etc/init.d/exim4";
	$f[]="/etc/init.d/nscd";
	
	while (list ($key, $init) = each ($f) ){
		if(!is_file($init)){continue;}
		echo "Bad services: [INFO] Remove $init\n";
		shell_exec("$init stop");
		if(is_file('/usr/sbin/update-rc.d')){
			shell_exec("/usr/sbin/update-rc.d -f " .basename($init)." remove >/dev/null 2>&1");
		}
		
		if(is_file('/sbin/chkconfig')){
			shell_exec("/sbin/chkconfig --del " .basename($init)." >/dev/null 2>&1");
			
		}
		
	}
	
}


function ufdb_tail(){
	
	if(isset($GLOBALS["ufdb_tail_executed"])){return;}
	$GLOBALS["ufdb_tail_executed"]=true;
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          ufdb-tail";
	$f[]="# Required-Start:    \$local_fs \$remote_fs \$syslog \$named \$time";
	$f[]="# Required-Stop:     \$local_fs \$remote_fs \$syslog \$named ";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: CNTLM daemon";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: UfdbGuard Watchdog logger";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="   $php ".dirname(__FILE__)."/exec.ufdbtail.php --start \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="   $php ".dirname(__FILE__)."/exec.ufdbtail.php --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="   $php ".dirname(__FILE__)."/exec.ufdbtail.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="   $php ".dirname(__FILE__)."/exec.ufdbtail.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	
	$INITD_PATH="/etc/init.d/ufdb-tail";
	echo "ufdb-tail: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);
	
	if(is_file('/usr/sbin/update-rc.d')){
	shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}


}

function roundcube_http(){
	$unix=new unix();
	if(!is_dir($unix->LOCATE_ROUNDCUBE_WEBFOLDER())){return;}
	$php=$unix->LOCATE_PHP5_BIN();
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          roundcube-http";
	$f[]="# Required-Start:    \$local_fs \$remote_fs \$syslog \$named \$time";
	$f[]="# Required-Stop:     \$local_fs \$remote_fs \$syslog \$named ";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: RoundCube HTTP daemon";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: RoundCube HTTP daemon";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="   $php ".dirname(__FILE__)."/exec.roundcube.php --start \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="   $php ".dirname(__FILE__)."/exec.roundcube.php --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="   $php ".dirname(__FILE__)."/exec.roundcube.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="   $php ".dirname(__FILE__)."/exec.roundcube.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	
	$INITD_PATH="/etc/init.d/roundcube";
	echo "roundcube: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);
	
	if(is_file('/usr/sbin/update-rc.d')){
	shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
			shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	
	
}	

function fetchmail(){

	$unix=new unix();
	$fetchmail=$unix->find_program("fetchmail");
	if(!is_file($fetchmail)){return;}
	$php=$unix->LOCATE_PHP5_BIN();
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          php5-fcgi";
	$f[]="# Required-Start:    \$local_fs \$remote_fs \$syslog \$named \$time";
	$f[]="# Required-Stop:     \$local_fs \$remote_fs \$syslog \$named ";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: PHP5 CGI Daemon";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: PHP5 CGI Daemon";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="   $php ".dirname(__FILE__)."/exec.fetchmail.php --start \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="   $php ".dirname(__FILE__)."/exec.fetchmail.php --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="   $php ".dirname(__FILE__)."/exec.fetchmail.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="   $php ".dirname(__FILE__)."/exec.fetchmail.php --reload \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	
	$INITD_PATH="/etc/init.d/fetchmail";
	echo "fetchmail: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);
	
	if(is_file('/usr/sbin/update-rc.d')){
	shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
			shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}	
}

function spawnfcgi(){
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          php5-fcgi";
	$f[]="# Required-Start:    \$local_fs \$remote_fs \$syslog \$named \$time";
	$f[]="# Required-Stop:     \$local_fs \$remote_fs \$syslog \$named ";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: PHP5 CGI Daemon";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: PHP5 CGI Daemon";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="   $php ".dirname(__FILE__)."/exec.php5-fcgi.php --start \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="   $php ".dirname(__FILE__)."/exec.php5-fcgi.php --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="   $php ".dirname(__FILE__)."/exec.php5-fcgi.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="   $php ".dirname(__FILE__)."/exec.php5-fcgi.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	
	$INITD_PATH="/etc/init.d/php5-fcgi";
	echo "php5-fcgi: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);
	
	if(is_file('/usr/sbin/update-rc.d')){
	shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
			shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	
}
	


function auth_tail(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          auth-tail";
	$f[]="# Required-Start:    \$local_fs \$remote_fs \$syslog \$named \$time";
	$f[]="# Required-Stop:     \$local_fs \$remote_fs \$syslog \$named ";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: auth-tail daemon";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: auth.log Watchdog logger";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="   $php ".dirname(__FILE__)."/exec.authtail.php --start \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="   $php ".dirname(__FILE__)."/exec.authtail.php --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="   $php ".dirname(__FILE__)."/exec.authtail.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="   $php ".dirname(__FILE__)."/exec.authtail.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	
	$INITD_PATH="/etc/init.d/auth-tail";
	echo "auth-tail: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);
	
	if(is_file('/usr/sbin/update-rc.d')){
	shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
			shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	
	
}

function artica_fw_hotspot(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/artica-hotfw";
	$daemonbinLog=basename($INITD_PATH);
	$php5script="exec.artica.hotspot.php";
	
	if(is_file("/usr/share/artica-postfix/$php5script")){@unlink("/usr/share/artica-postfix/$php5script");}
	
	if(is_file($INITD_PATH)){
		if(is_file('/usr/sbin/update-rc.d')){
			shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");
		}
		
		if(is_file('/sbin/chkconfig')){
			shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");
			
		}
	}
}

function netdiscover(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/netdiscover";
	$daemonbinLog=basename($INITD_PATH);
	$php5script="exec.netdiscover.php";
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         $daemonbinLog";
	$f[]="# Required-Start:    \$local_fs \$syslog \$network";
	$f[]="# Required-Stop:     \$local_fs \$syslog \$network";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: ARP Discover in passive mode";
	$f[]="# chkconfig: - 80 75";
	$f[]="# description: $daemonbinLog";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
	$f[]="    ;;";
	$f[]=" status)";
	$f[]="    $php /usr/share/artica-postfix/exec.status.php --netdiscover \$2 \$3";
	$f[]="    ;;";
	
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	
	$f[]=" try-restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	
	$f[]=" stats)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stats \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	
	$f[]=" build)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	
	$f[]=" force-reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|status|try-restart|restart|force-reload|reload|stats|build} (+ '--verbose' for more infos)\"";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	
	
	
	
	
}

function l7filter(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/l7filter";
	$daemonbinLog=basename($INITD_PATH);
	$php5script="exec.l7filter.php";
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         $daemonbinLog";
	$f[]="# Required-Start:    \$local_fs \$syslog \$network";
	$f[]="# Required-Stop:     \$local_fs \$syslog \$network";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Layer7 protocol daemon";
	$f[]="# chkconfig: - 80 75";
	$f[]="# description: $daemonbinLog";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
	$f[]="    ;;";
	$f[]=" status)";
	$f[]="    $php /usr/share/artica-postfix/exec.status.php --l7filter \$2 \$3";
	$f[]="    ;;";

	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";

	$f[]=" try-restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";

	$f[]=" stats)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stats \$2 \$3";
	$f[]="    ;;";
	$f[]="";

	$f[]=" build)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
	$f[]="    ;;";
	$f[]="";

	$f[]=" force-reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|status|try-restart|restart|force-reload|reload|stats|build} (+ '--verbose' for more infos)\"";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}





}

function kav4proxy(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/kav4proxy";
	$daemonbinLog=basename($INITD_PATH);
	$php5script="exec.kav4proxy.service.php";
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         $daemonbinLog";
	$f[]="# Required-Start:    \$local_fs \$syslog \$network";
	$f[]="# Required-Stop:     \$local_fs \$syslog \$network";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Kaspersky Anti-Virus for Proxy Server";
	$f[]="# chkconfig: - 80 75";
	$f[]="# description: $daemonbinLog";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
	$f[]="    ;;";
	$f[]=" status)";
	$f[]="    $php /usr/share/artica-postfix/exec.status.php --kav4proxy \$2 \$3";
	$f[]="    ;;";	
	
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	
	$f[]=" try-restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	
	$f[]=" reload_avbase)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload-avbase \$2 \$3";
	$f[]="    ;;";
	$f[]="";	

	$f[]=" stats)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stats \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	
	$f[]=" build)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
	$f[]="    ;;";
	$f[]="";	
	
	$f[]=" force-reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";
	$f[]="";	
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|status|try-restart|restart|force-reload|reload|reload_avbase|stats|build} (+ '--verbose' for more infos)\"";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	
	
	
	
	
}



function wifidog(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/wifidog";
	$daemonbinLog=basename($INITD_PATH);
	$php5script="exec.wifidog.php";
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         $daemonbinLog";
	$f[]="# Required-Start:    \$local_fs \$syslog \$network";
	$f[]="# Required-Stop:     \$local_fs \$syslog \$network";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: WIFIDog Daemon";
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
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}


}

function artica_web_hotspot(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/artica-hotspot";
	$daemonbinLog=basename($INITD_PATH);
	$php5script="exec.hostpot-web.php";
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         $daemonbinLog";
	$f[]="# Required-Start:    \$local_fs \$syslog \$network";
	$f[]="# Required-Stop:     \$local_fs \$syslog \$network";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Connection Tracker Daemon";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}	
	
	
}

function conntrackd(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/conntrackd";
	$daemonbinLog=basename($INITD_PATH);
	$php5script="exec.conntrackd.php";
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         conntrackd";
	$f[]="# Required-Start:    \$local_fs \$syslog \$network";
	$f[]="# Required-Stop:     \$local_fs \$syslog \$network";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Connection Tracker Daemon";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
}

function stunnel(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$daemonbin=$unix->LOCATE_STUNNEL();
	$INITD_PATH=$unix->LOCATE_STUNNEL_INIT();
	
	if(!is_file($daemonbin)){return;}
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          stunnel4";
	$f[]="# Required-Start:    \$local_fs \$syslog";
	$f[]="# Required-Stop:     \$local_fs";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: SNMPD daemon";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Extensible, configurable SSL tunnel daemon";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="   $php ".dirname(__FILE__)."/exec.stunnel.php --start \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="   $php ".dirname(__FILE__)."/exec.stunnel.php --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="   $php ".dirname(__FILE__)."/exec.stunnel.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="   $php ".dirname(__FILE__)."/exec.stunnel.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	
	
	echo "SNMPD: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);
	
	if(is_file('/usr/sbin/update-rc.d')){
	shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
			shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
}


function saslauthd(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/saslauthd";
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          saslauthd";
	$f[]="# Required-Start:    \$local_fs \$syslog";
	$f[]="# Required-Stop:     \$local_fs";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: saslauthd daemon";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Extensible, configurable saslauthd daemon";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="   $php ".dirname(__FILE__)."/exec.saslauthd.php --start \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="   $php ".dirname(__FILE__)."/exec.saslauthd.php --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="   $php ".dirname(__FILE__)."/exec.saslauthd.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="   $php ".dirname(__FILE__)."/exec.saslauthd.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	
	
	echo "saslauthd: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);
	
	if(is_file('/usr/sbin/update-rc.d')){
	shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
			shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}	
	
	
}


function snmpd(){
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$daemonbin=$unix->find_program("snmpd");
	$INITD_PATH="/etc/init.d/snmpd";
	
	if(!is_file($daemonbin)){return;}
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          snmpd";
	$f[]="# Required-Start:    \$local_fs \$syslog";
	$f[]="# Required-Stop:     \$local_fs";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: SNMPD daemon";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Extensible, configurable SNMP daemon";
	$f[]="### END INIT INFO";
	$f[]="export MIBS=/usr/share/mibs";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="   $php ".dirname(__FILE__)."/exec.snmpd.php --start \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="   $php ".dirname(__FILE__)."/exec.snmpd.php --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="   $php ".dirname(__FILE__)."/exec.snmpd.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="   $php ".dirname(__FILE__)."/exec.snmpd.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	
	
	echo "SNMPD: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);
	
	if(is_file('/usr/sbin/update-rc.d')){
	shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
			shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}	

	

}

function pdns(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$daemonbin=$unix->find_program("pdns_server");
	if(!is_file($daemonbin)){return;}
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          pdns";
	$f[]="# Required-Start:    \$local_fs \$syslog \$network \$time";
	$f[]="# Required-Stop:     \$local_fs";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: PowerDNS daemon";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Extensible, configurable DNS PROXY daemon";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="   $php ".dirname(__FILE__)."/exec.pdns_server.php --start \$2 \$3";
	$f[]="   $php ".dirname(__FILE__)."/exec.pdns.php --poweradmin \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="   $php ".dirname(__FILE__)."/exec.pdns_server.php --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="   $php ".dirname(__FILE__)."/exec.pdns_server.php --restart \$2 \$3";
	$f[]="   $php ".dirname(__FILE__)."/exec.pdns.php --poweradmin \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="   $php ".dirname(__FILE__)."/exec.pdns_server.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";

	$INITD_PATH="/etc/init.d/pdns";
	echo "PDNS: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	
	pdns_recursor();

}

function sarg(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          sarg";
	$f[]="# Required-Start:    \$local_fs \$remote_fs \$syslog \$named \$network \$time";
	$f[]="# Required-Stop:     \$local_fs \$remote_fs \$syslog \$named \$network";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: sarg daemon";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Extensible, configurable sarg PROXY daemon";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="   $php ".dirname(__FILE__)."/exec.sarg.php --start \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="   $php ".dirname(__FILE__)."/exec.sarg.php --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="   $php ".dirname(__FILE__)."/exec.sarg.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="   $php ".dirname(__FILE__)."/exec.sarg.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";

	$INITD_PATH="/etc/init.d/sarg";
	echo "CNTLM: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}

}
function cntlm_parent(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$daemonbin=$unix->find_program("cntlm");
	if(!is_file($daemonbin)){return;}
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          cntlm-parent";
	$f[]="# Required-Start:    \$local_fs \$remote_fs \$syslog \$named \$network \$time";
	$f[]="# Required-Stop:     \$local_fs \$remote_fs \$syslog \$named \$network";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: CNTLM Parent daemon";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Extensible, configurable NTLM PROXY daemon";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="   $php ".dirname(__FILE__)."/exec.cntlm-parent.php --start \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="   $php ".dirname(__FILE__)."/exec.cntlm-parent.php --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="   $php ".dirname(__FILE__)."/exec.cntlm-parent.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="   $php ".dirname(__FILE__)."/exec.cntlm-parent.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";

	$INITD_PATH="/etc/init.d/cntlm-parent";
	echo "CNTLM: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}

}

function cntlm(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$daemonbin=$unix->find_program("cntlm");
	if(!is_file($daemonbin)){return;}
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          cntlm";
	$f[]="# Required-Start:    \$local_fs \$remote_fs \$syslog \$named \$network \$time";
	$f[]="# Required-Stop:     \$local_fs \$remote_fs \$syslog \$named \$network";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: CNTLM daemon";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Extensible, configurable NTLM PROXY daemon";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="   $php ".dirname(__FILE__)."/exec.cntlm.php --start \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="   $php ".dirname(__FILE__)."/exec.cntlm.php --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="   $php ".dirname(__FILE__)."/exec.cntlm.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="   $php ".dirname(__FILE__)."/exec.cntlm.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";

	$INITD_PATH="/etc/init.d/cntlm";
	echo "CNTLM: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}

}

function ldap_conf(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	system("$php /usr/share/artica-postfix/exec.pam.php --ldap");
	
}

function artica_syslog(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();

	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          artica-syslog";
	$f[]="# Required-Start:    \$syslog";
	$f[]="# Required-Stop:     ";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Artica Syslog daemon";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Artica Syslog daemon";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="   $php ".dirname(__FILE__)."/exec.syslog-init.php --start \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="   $php ".dirname(__FILE__)."/exec.syslog-init.php --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="   $php ".dirname(__FILE__)."/exec.syslog-init.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="   $php ".dirname(__FILE__)."/exec.syslog-init.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	
	$INITD_PATH="/etc/init.d/artica-syslog";
	echo "INIT: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);
	
	if(is_file('/usr/sbin/update-rc.d')){
	shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
			shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}	
}

function bwm_ng(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$daemonbin=$unix->find_program("bwm-ng");
	if(!is_file($daemonbin)){return;}
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          bwm-ng";
	$f[]="# Required-Start:    \$local_fs \$remote_fs \$syslog \$named \$network \$time";
	$f[]="# Required-Stop:     \$local_fs \$remote_fs \$syslog \$named \$network";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Bandwidth Monitor NG";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Extensible, configurable Bandwidth Monitor NG daemon";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="   $php ".dirname(__FILE__)."/exec.bwm-ng.php --start \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="   $php ".dirname(__FILE__)."/exec.bwm-ng.php --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="   $php ".dirname(__FILE__)."/exec.bwm-ng.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";

	$INITD_PATH="/etc/init.d/bwm-ng";
	echo "bwm-ng: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}

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
	$f[]=" force-reload)";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}

}





function ifup(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();	
	$INITD_PATH="/etc/init.d/artica-ifup";
	if(is_file($INITD_PATH)){return;}
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
	$f[]="export LC_ALL=C";
	$f[]="";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]=" ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}

}

function debian_mirror(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$daemonbin=$unix->find_program("rsync");
	$daemonbinLog=basename($daemonbin);
	$INITD_PATH="/etc/init.d/debian-artmirror";
	$php5script="exec.debian.mirror.php";
	if(!is_file($daemonbin)){return;}


	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         debian-artmirror";
	$f[]="# Required-Start:    \$local_fs \$syslog \$network";
	$f[]="# Required-Stop:     \$local_fs \$syslog \$network";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Artica Debian Mirror builder";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	
	
}
function apacheoff(){
	
	$unix=new unix();
	$debianbin=$unix->find_program("update-rc.d");
	$redhatbin=$unix->find_program("chkconfig");
	if(is_file("/etc/init.d/apache")){$service="apache";}
	if(is_file("/etc/init.d/httpd")){$service="httpd";}
	if(is_file("/etc/init.d/artica-apache")){$service="artica-apache";}

	if($service==null){return;}
	if(is_file($debianbin)){shell_exec("$debianbin -f $service remove >/dev/null 2>&1");}
	if(is_file($redhatbin)){shell_exec("$redhatbin $service off >/dev/null 2>&1");}	
	
}

function apache(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/apache2";
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
	$f[]="    $php /usr/share/artica-postfix/exec.mounts.bind.php \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    ;;";
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/exec.mounts.bind.php \$2 \$3";
	$f[]="    ;;";	
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/exec.mounts.bind.php \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" force-reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
	$f[]="    ;;";
	$f[]="";	
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|force-reload|reload} (+ '--verbose' for more infos)\"";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	



}

function webservices(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/artica-webservices";
	$daemonbinLog="Artica Web services";
	
	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         artica-webservices";
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
	$f[]="    $php /usr/share/artica-postfix/exec.php-fpm.php --start --script \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/exec.php5-fcgi.php --start --script \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/exec.framework.php --start --script \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/exec.lighttpd.php --start --script \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/exec.freeweb.php --start --script \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/exec.squidguard-http.php --start --script \$2 \$3";
	$f[]="    /etc/init.d/artica-status reload --script \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/exec.php-fpm.php --stop --script \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/exec.php5-fcgi.php --stop --script \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/exec.framework.php --stop --script \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/exec.lighttpd.php --stop --script \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/exec.freeweb.php --stop --script \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/exec.squidguard-http.php --stop --script \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	
	$f[]="    $php /usr/share/artica-postfix/exec.php-fpm.php --restart --script \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/exec.php5-fcgi.php --restart --script \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/exec.framework.php --restart --script \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/exec.lighttpd.php --restart --script \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/exec.freeweb.php --stop --script \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/exec.freeweb.php --start --script \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/exec.squidguard-http.php --restart --script \$2 \$3";
	$f[]="    /etc/init.d/artica-status reload --script \$2 \$3";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	
	
	
}


function framework(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/artica-framework";
	$php5script="exec.framework.php";
	$daemonbinLog="Artica Framework";
	$lighttpd=$unix->find_program("lighttpd");
	if(!is_file($lighttpd)){
		$nginx=$unix->find_program("nginx");
		if(!is_file($nginx)){return;}
		$php5script="exec.nginx.php --framework";
	}

	echo "$daemonbinLog: [INFO] '$lighttpd'\n";
	$chmod=$unix->find_program("chmod");
	
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
	$f[]=checkframework_code();
	$f[]="    $php /usr/share/artica-postfix/$php5script --start --script \$2 \$3";
	$f[]="    $chmod -R 0755 /usr/share/artica-postfix/bin \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop --script \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart --script \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="";
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload --script \$2 \$3";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
		
	
}

function phppfm_fix(){
	$unix=new unix();
	$pidF="/etc/artica-postfix/pids/".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidF);
	if($unix->process_exists($pid,basename(__FILE__))){return;}
	@file_put_contents($pidF, getmypid());
	phppfm();
	shell_exec("/etc/init.d/php5-fpm start");
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup /etc/init.d/artica-framework restart >/dev/null 2>&1 &");
	shell_exec("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");
	
}
function phppfm_restartback(){
	
	if(!isPhpFpmPatched()){
		InitSlapdToSyslog("phppfm_restartback():: /etc/init.d/php5-fpm not patched..");
		phppfm();
		$unix=new unix();
		$nohup=$unix->find_program("nohup");
		InitSlapdToSyslog("phppfm_restartback():: Restarting PHP5-FPM");
		shell_exec("/etc/init.d/php5-fpm restart");
		shell_exec("$nohup /etc/init.d/artica-framework restart >/dev/null 2>&1 &");
	}
}
function isPhpFpmPatched(){
	$f=explode("\n",@file_get_contents("/etc/init.d/php5-fpm"));
	while (list ($index, $line) = each ($f) ){
		if(preg_match("#exec\.php-fpm\.php#", $line)){return true;}
		
	}
	
}



function InitSlapdToSyslog($text){

	$LOG_SEV=LOG_INFO;
	if(function_exists("openlog")){openlog(basename(__FILE__), LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
	if(function_exists("closelog")){closelog();}
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

function cyrus_imapd(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/cyrus-imapd";
	$php5script="exec.cyrus-imapd.php";
	$daemonbinLog="";
	$daemon_path=$unix->find_program("cyrmaster");

	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         cyrus-common cyrus-imapd";
	$f[]="# Required-Start:    \$local_fs \$syslog";
	$f[]="# Required-Stop:     \$local_fs \$syslog";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Common init system for cyrus IMAP/POP3 daemons";
	$f[]="# chkconfig: - 80 75";
	$f[]="# description: Common init system for cyrus IMAP/POP3 daemons";
	$f[]="### END INIT INFO";
	$f[]="PATH=/sbin:/usr/sbin:/bin:/usr/bin";
	$f[]="NAME=cyrmaster";
	$f[]="DAEMON=\"/usr/sbin/\${NAME}\"";
	$f[]="PIDFILE=\"/var/run/\${NAME}.pid\"";
	$f[]="DESC=\"Cyrus IMAPd\"";
	$f[]="# Check if Cyrus is installed (vs. removed but not purged)";
	$f[]="test -x \"\$DAEMON\" || exit 0";
	$f[]="LC_ALL=C";
	$f[]="export LC_ALL";
	$f[]="";
	
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
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";	
	$f[]=" build)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
	$f[]="    ;;";	
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reload} --verbose for more infos\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";


	echo "Cyrus-imapd: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));


	@chmod($INITD_PATH,0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");

	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}

}


function checkframework_code(){
	if(isset($GLOBALS["checkframework_code"])){return $GLOBALS["checkframework_code"];}
	$unix=new unix();
	$echo =$unix->find_program("echo");
	$php=$unix->LOCATE_PHP5_BIN();
	$f[]="\techo \"Starting......: 00:00:00 [INIT]: Artica Checking framework\"";
	$f[]="\tRESULTS=`/usr/bin/php5 /usr/share/artica-postfix/exec.lighttpd.php --tests`";
	$f[]="\t$echo \"Starting......: 00:00:00 [INIT]: Artica Checking framework answer '\$RESULTS'\"";
	$f[]="\tif  [  -z \"\$RESULTS\"  ]; then";
	$f[]="\t\t$echo \"Starting......: 00:00:00 [INIT]: Artica Checking framework failed, running Process1\"";
	$f[]="\t\t/usr/share/artica/postfix/bin/process1 --force --123 >/dev/null 2>&1 ||true";
	$f[]="\tfi";
	$GLOBALS["checkframework_code"]= @implode("\n",$f);
	return $GLOBALS["checkframework_code"];
}


function artica_webconsole(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/artica-webconsole";
	$php5script="exec.lighttpd.php";
	$daemonbinLog="Artica SSL Web console";
	$daemon_path=$unix->APACHE_LOCATE_PHP_FPM();
	$chmod=$unix->find_program("chmod");
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         artica-lighttpd";
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
	$f[]="    $chmod 0755 /usr/share/artica-postfix/bin/artica-install >/dev/null 2>&1 || true";
	$f[]="    $chmod 0755 /usr/share/artica-postfix/bin/process1 >/dev/null 2>&1 || true";
	$f[]=checkframework_code();
	$f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/exec.framework.php --start \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/exec.status-init.php --start \$2 \$3";
	
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $chmod -R 0755 /usr/share/artica-postfix/bin";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	$f[]=" restart-paused)";
	$f[]="    $chmod -R 0755 /usr/share/artica-postfix/bin";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart --pause\$2 \$3";
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
	
	if(!is_file("/etc/cron.d/webconsole")){
		$h[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin:/usr/share/artica-postfix/bin";
		$h[]="MAILTO=\"\"";
		$h[]="5 * * * *  root $php /usr/share/artica-postfix/$php5script --start >/dev/null 2>&1";
		$h[]="";
		@file_put_contents("/etc/cron.d/webconsole",@implode("\n",$h));
		@chmod("/etc/cron.d/webconsole",640);
		shell_exec("/bin/chown root:root /etc/cron.d/webconsole");
		
		$h=array();
		$h[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin:/usr/share/artica-postfix/bin";
		$h[]="MAILTO=\"\"";
		$h[]="@reboot  root $php /usr/share/artica-postfix/$php5script --start >/dev/null 2>&1";
		$h[]="";
		@file_put_contents("/etc/cron.d/webconsole-reboot",@implode("\n",$h));
		@chmod("/etc/cron.d/webconsole-reboot",640);
		shell_exec("/bin/chown root:root /etc/cron.d/webconsole-reboot");
	}
	
	
	
	
	@chmod($INITD_PATH,0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
		
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	
	$LIGHTTPD_INITD=LIGHTTPD_INITD();
	if(is_file($LIGHTTPD_INITD)){lighttpd();}


}

function lighttpd(){
	$LIGHTTPD_INITD=LIGHTTPD_INITD();
	if(!is_file($LIGHTTPD_INITD)){return;}
	$INITD_PATH=$LIGHTTPD_INITD;
	$daemonbinLog="Disabled service";
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         lighttpd";
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
	$f[]="   exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    exit 0";
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
		shell_exec("/usr/sbin/update-rc.d -f ".basename($INITD_PATH)." remove");
	
	}
	
	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	
	
	
	
}


function phppfm(){
	
	if(is_file("/etc/artica-postfix/FROM_ISO")){if(!is_file("/etc/artica-postfix/artica-iso-setup-launched")){die();}}
	
	$unix=new unix();
	if(is_file("/etc/artica-postfix/FROM_ISO")){
		$daemon_path="/usr/sbin/php5-fpm";
		$php=$GLOBALS["PHP5_BIN_PATH"];
	}else{
		$php=$unix->LOCATE_PHP5_BIN();
		$daemon_path=$unix->APACHE_LOCATE_PHP_FPM();
	}
	
	
	$INITD_PATH="/etc/init.d/php5-fpm";
	$php5script="exec.php-fpm.php";
	$daemonbinLog="PHP5 FastCGI Process Manager Daemon";
	
	
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
	$f[]="    $php /usr/share/artica-postfix/$php5script --start --script \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop --script \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart --script \$2 \$3";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
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
	$f[]="    ulimit -n 65536";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/exec.mounts.bind.php \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    ulimit -n 65536";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/exec.mounts.bind.php \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reconfigure)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
	$f[]="    ;;";	
	$f[]=" reload)";
	$f[]="    ulimit -n 65536";
	$f[]="    $php /usr/share/artica-postfix/$php5script --force-restart \$2 \$3";
	$f[]="    ;;";
	$f[]="";	
	
	$f[]=" purge)";
	$f[]="    ulimit -n 65536";
	$f[]="    $php /usr/share/artica-postfix/$php5script --purge-all-caches \$2 \$3";
	$f[]="    ;;";
	$f[]="";	
	
	
	
	$f[]=" reconfigure)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
	$f[]="    ;;";
	$f[]="";	
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure|reload} (+ '--verbose' for more infos)\"";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	
	if($restart){
		@file_put_contents("/etc/artica-postfix/ngnix.first.restart", time());
		echo "Restarting Apache Web server...\n";
		system("/etc/init.d/apache2 restart");
		echo "Restarting Nginx service...\n";
		system("$INITD_PATH restart");
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
}
function ntopng(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/ntopng";
	$php5script="exec.ntopng.php";
	$daemonbinLog="Network traffic probe";



	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         ".basename($INITD_PATH);
	$f[]="# Required-Start:    \$local_fs \$syslog \$ntopng";
	$f[]="# Required-Stop:     \$local_fs \$syslog \$ntopng";
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
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure|reload} (+ '--verbose' for more infos)\"";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}

}




function redis_server(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/redis-server";
	$php5script="exec.redis-server.php";
	$daemonbinLog="Persistent key-value db";



	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         ".basename($INITD_PATH);
	$f[]="# Required-Start:    \$local_fs \$syslog \$ntopng";
	$f[]="# Required-Stop:     \$local_fs \$syslog \$ntopng";
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
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure|reload} (+ '--verbose' for more infos)\"";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}

}

function squidstream(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/squid-stream";
	$php5script="exec.squidstream.php";
	$daemonbinLog="Squid-Cache Stream Backend";



	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         squid-stream";
	$f[]="# Required-Start:    \$local_fs \$syslog \$squid";
	$f[]="# Required-Stop:     \$local_fs \$syslog \$squid";
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
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";
	$f[]=" templates)";
	$f[]="    $php /usr/share/artica-postfix/exec.squid.templates.php --initd\$2 \$3";
	$f[]="    ;;";
	
	
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure|reload|templates} (+ '--verbose' for more infos)\"";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}

}
function squidstream_scheduler(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/vc-scheduler";
	$php5script="exec.squidstream.php";
	$daemonbinLog="start and stop Videocache Scheduler";



	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         vc-scheduler";
	$f[]="# Required-Start:    \$local_fs \$syslog \$squid";
	$f[]="# Required-Stop:     \$local_fs \$syslog \$squid";
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
	$f[]="    $php /usr/share/artica-postfix/$php5script --vc-scheduler-start \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --vc-scheduler-stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --vc-scheduler-restart \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reconfigure)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --vc-scheduler-reload \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure|reload} (+ '--verbose' for more infos)\"";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}

}
function zipproxy(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/zipproxy";
	$php5script="exec.zipproxy.php";
	$daemonbinLog="Proxy compressor";



	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         zipproxy";
	$f[]="# Required-Start:    \$local_fs \$syslog \$squid";
	$f[]="# Required-Stop:     \$local_fs \$syslog \$squid";
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
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure|reload} (+ '--verbose' for more infos)\"";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}

}

function postfix_logger(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/postfix-logger";
	$php5script="exec.service.postfix-logger.php";
	$daemonbinLog="Artica-postfix Realtime Logs";



	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         ".basename($INITD_PATH);
	$f[]="# Required-Start:    \$local_fs \$syslog \$postfix";
	$f[]="# Required-Stop:     \$local_fs \$syslog \$postfix";
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
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure|reload} (+ '--verbose' for more infos)\"";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}

}


function squidnat(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/squid-nat";
	$php5script="exec.squid27.php";
	$daemonbinLog="Squid-Cache NAT front-end";
	
	
	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         squid-nat";
	$f[]="# Required-Start:    \$local_fs \$syslog \$squid";
	$f[]="# Required-Stop:     \$local_fs \$syslog \$squid";
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
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";	
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure|reload} (+ '--verbose' for more infos)\"";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	
}

function hypercache_http(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/hypercache-web";
	$php5script="exec.HyperCacheWeb.php";
	$daemonbinLog="HyperCache Web service";



	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         hypercache-web";
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
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}



}

function squidguard_http(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/squidguard-http";
	$php5script="exec.squidguard-http.php";
	$daemonbinLog="Ufdbguard Web page error";
	
	
	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         squidguard-http";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	
		
	
}

function haarp(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/haarp";
	$php5script="exec.haarp.php";
	$daemonbinLog="Haarp For Artica";
	$rm=$unix->find_program("rm");
	if(is_file("/usr/sbin/haarp")){@unlink("/usr/sbin/haarp");}
	if(is_dir("/etc/haarp")){shell_exec("$rm -rf /etc/haarp");}
	if(is_file("/etc/init.d/haarpclean")){
		echo "$daemonbinLog: [INFO] Deleting haarpclean\n";
		if(is_file('/usr/sbin/update-rc.d')){
			shell_exec("/usr/sbin/update-rc.d -f haarpclean remove >/dev/null 2>&1");
			@unlink("/etc/init.d/haarpclean");
		}
		
		if(is_file('/sbin/chkconfig')){
			shell_exec("/sbin/chkconfig --del haarpclean >/dev/null 2>&1");
			@unlink("/etc/init.d/haarpclean");
			
		}
	}
	
	if(is_file($INITD_PATH)){
		echo "$daemonbinLog: [INFO] Deleting $INITD_PATH\n";
		if(is_file('/usr/sbin/update-rc.d')){
			shell_exec("/usr/sbin/update-rc.d -f haarp remove >/dev/null 2>&1");
			@unlink("/etc/init.d/haarp");
		}
	
		if(is_file('/sbin/chkconfig')){
			shell_exec("/sbin/chkconfig --del haarp >/dev/null 2>&1");
			@unlink("/etc/init.d/haarp");
				
		}
	}	
}
function vnstat(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/vnstat";
	$php5script="exec.vnstat.php";
	$daemonbinLog="lightweight network traffic monitor";



	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         artica-vnstat";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}


}
function rdpproxy_authhook(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/rdpproxy-authhook";
	$php5script="exec.rdpproxy.php";
	$daemonbinLog="authhook RDP Proxy Daemon";
	
	
	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:        authhook";
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
	$f[]="    $php /usr/share/artica-postfix/$php5script --authhook-start \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --authhook-stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --authhook-restart \$2 \$3";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	
	
}

function process1(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/artica-process1";
	$php5script="exec.process1.php";
	$daemonbinLog="Artica settings process";



	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:        process1";
	$f[]="# Required-Start:    \$local_fs \$syslog";
	$f[]="# Required-Stop:     \$local_fs \$syslog";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     4 5";
	$f[]="# Default-Stop:      0 1 6 4 5";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	

}
function shorewall_db(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/shorewall-db";
	$php5script="exec.shorewall-db.php";
	$daemonbinLog="Shorewall MySQL Database daemon";
	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:        shorewall-db";
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
	$f[]="    echo \"Usage: $INITD_PATH {start|stop|restart} (+ '--verbose' for more infos)\"";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	
	
}

function clamav_freshclam(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/clamav-freshclam";
	$php5script="exec.freshclam.php";
	$daemonbinLog="Clam AntiVirus userspace daemon";	
	$Provides="clamav-freshclam";
	$daemonbinLog="Clam AntiVirus virus database updater";
	
	
	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:        $Provides";
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
	$f[]="  skip)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    ;;";	
	$f[]="  status)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
	$f[]="    ;;";	
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reconfigure)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
	$f[]="    ;;";
	$f[]=" build)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
	$f[]="    ;;";
	$f[]=" force-reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --force-reload \$2 \$3";
	$f[]="    ;;";
	$f[]=" reload-database)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload-database \$2 \$3";
	$f[]="    ;;";
	$f[]=" reload-log)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload-log \$2 \$3";
	$f[]="    ;;";
	
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: $INITD_PATH {no-daemon|start|stop|restart|force-reload|reload-log|skip|status} (+ '--verbose' for more infos)\"";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	
	
}





function clamav_daemon(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/clamav-daemon";
	$php5script="exec.clamd.php";
	$daemonbinLog="Clam AntiVirus userspace daemon";


	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:        clamav-daemon";
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
	$f[]=" build)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
	$f[]="    ;;";
	$f[]=" force-reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --force-reload \$2 \$3";
	$f[]="    ;;";		
	$f[]=" reload-database)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload-database \$2 \$3";
	$f[]="    ;;";
	$f[]=" reload-log)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload-log \$2 \$3";
	$f[]="    ;;";		
	
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: $INITD_PATH {start|stop|restart|force-reload|reload-log|reload-database|status} (+ '--verbose' for more infos)\"";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}

}

function rdpproxy(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/rdpproxy";
	$php5script="exec.rdpproxy.php";
	$daemonbinLog="RDP Proxy Daemon";



	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:        rdpproxy";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	rdpproxy_authhook();

}

function winbind(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/winbind";
	$php5script="exec.winbindd.php";
	$daemonbinLog="Winbind Daemon";
	
	
	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:        winbind";
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
	$f[]="    ulimit -n 65500";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    ulimit -n 65500";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reconfigure)";
	$f[]="    ulimit -n 65500";
	$f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
	$f[]="    ;;";
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}	
	
}

function monit(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/monit";
	$php5script="exec.monit.php";
	$daemonbinLog="Monitor Daemon";



	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         artica-monit";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}


}

function haproxy(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/haproxy";
	$php5script="exec.haproxy.php";
	$daemonbinLog="Load-Balancer Daemon";



	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         artica-haproxy";
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
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}


}


function irqbalance(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/irqbalance";
	$php5script="exec.irqbalance.php";
	$daemonbinLog="daemon to balance interrupts for SMP systems";
	
	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         irqbalance";
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
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	
	
	
}

function arpd(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/arpd";
	$php5script="exec.arpd.php";
	$daemonbinLog="ARP Daemon";



	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         artica-arpd";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
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
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reconfigure)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure|reload} (+ '--verbose' for more infos)\"";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}

}

function artica_status(){
	$unix=new unix();
	$daemonbinLog="Artica Status daemon";
	$INITD_PATH="/etc/init.d/artica-status";
	$php5script="exec.status-init.php";
	$php=$unix->LOCATE_PHP5_BIN();
	$touch=$unix->find_program("touch");
	
	if(!is_file("/etc/cron.d/artica-status")){
		$z[]="MAILTO=\"\"";
		$z[]="@reboot root $php /usr/share/artica-postfix/$php5script --start --startcron >/dev/null 2>&1";
		$z[]="";
		@file_put_contents("/etc/cron.d/artica-status", @implode("\n", $z));
		shell_exec("/etc/init.d/cron reload");
		
	}
	
	$f=array();
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          artica-status";
	$f[]="# Required-Start:    \$local_fs";
	$f[]="# Required-Stop:     \$local_fs";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: $daemonbinLog";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Artica status Daemon";
	$f[]="### END INIT INFO";
	$f[]="";
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
	$f[]=" reload)";
	$f[]="    echo \"Ask artica-status to be reloaded\"";
	$f[]="    $touch /etc/artica-postfix/ARTICA_STATUS_RELOAD";
	$f[]="    ;;";
	$f[]="";	
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure|reload} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	$f[]="";	
	
	echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);
	
	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	
}
function artica_openssh(){
	$unix=new unix();
	$daemonbinLog="OpenSSHD daemon";
	$INITD_PATH="/etc/init.d/artica-ssh";
	$php5script="exec.sshd.php";
	$php=$unix->LOCATE_PHP5_BIN();
	$touch=$unix->find_program("touch");
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          artica-ssh";
	$f[]="# Required-Start:    \$local_fs";
	$f[]="# Required-Stop:     \$local_fs";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: $daemonbinLog";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Artica status Daemon";
	$f[]="### END INIT INFO";
	$f[]="";
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
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure|reload} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	$f[]="";

	echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}

}

function milter_greylist(){
	$daemonbinLog="Milter Greylist Daemon";
	
	$unix=new unix();
	$milter_greylist=$unix->find_program("milter-greylist");
	if(!is_file($milter_greylist)){return;}
	$php=$unix->LOCATE_PHP5_BIN();
	$sock=new sockets();
	$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
	if(!is_numeric($EnablePostfixMultiInstance)){$EnablePostfixMultiInstance=0;}
	
	$INITD_PATH="/etc/init.d/milter-greylist";
	
	$cmdline_start="$php /usr/share/artica-postfix/exec.milter-greylist.php --start-single";
	$cmdline_stop="$php /usr/share/artica-postfix/exec.milter-greylist.php --stop-single";
	$cmdline_restart="$php /usr/share/artica-postfix/exec.milter-greylist.php --restart-single";
	$cmdline_reload="$php /usr/share/artica-postfix/exec.milter-greylist.php --reload-single";
	if($EnablePostfixMultiInstance==1){
		$cmdline_start="$php /usr/share/artica-postfix/exec.milter-greylist.php --start";
		$cmdline_stop="$php /usr/share/artica-postfix/exec.milter-greylist.php --stop";
	}
	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          milter-greylist";
	$f[]="# Required-Start:    \$local_fs";
	$f[]="# Required-Stop:     \$local_fs";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: $daemonbinLog";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: $daemonbinLog";
	$f[]="### END INIT INFO";
	$f[]="";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $cmdline_start \$2";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $cmdline_stop \$2";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	if($EnablePostfixMultiInstance==1){
		$f[]="     $cmdline_stop \$2";
		$f[]="     sleep 3";
		$f[]="     $cmdline_start \$2";
	}else{
		$f[]="    $cmdline_restart \$2";
	}
	$f[]="    ;;";
	$f[]="  reload)";
	if($EnablePostfixMultiInstance==0){	
		$f[]="    $cmdline_reload \$2";
	}
	$f[]="    ;;";	
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reload}\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0";
	$f[]="";
	
	echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);
	
	if(is_file('/usr/sbin/update-rc.d')){
	shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
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
				shell_exec("kill -9 `pidof avahi-daemon` >/dev/null 2>&1");
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
	$f[]=" 	  $echo \"Starting......: ".date("H:i:s")." [INIT]: $daemonbinLog - Please wait\"";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]=" 	  $echo \"Stopping......: ".date("H:i:s")." [INIT]: $daemonbinLog - Please wait\"";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}

}
function opendkim(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	
	$opendkim=$unix->find_program("opendkim");
	
	if(!is_file("$opendkim")){return;}
	$f[]="#! /bin/sh";
	$f[]="#";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:		opendkim";
	$f[]="# Required-Start:	\$syslog \$time \$local_fs \$remote_fs \$named \$network";
	$f[]="# Required-Stop:	\$syslog \$time \$local_fs \$remote_fs";
	$f[]="# Default-Start:	2 3 4 5";
	$f[]="# Default-Stop:		0 1 6";
	$f[]="# Short-Description:	Start the OpenDKIM service";
	$f[]="# Description:		Enable DKIM signing and verification provided by OpenDKIM";
	$f[]="### END INIT INFO";

	$f[]="PATH=/sbin:/bin:/usr/sbin:/usr/bin";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php /usr/share/artica-postfix/exec.opendkim.php --start \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/exec.opendkim.php --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/exec.opendkim.php --restart \$2 \$3";
	
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";

	$INITD_PATH="/etc/init.d/opendkim";
	echo "OpenDKIM: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);@file_put_contents($INITD_PATH, @implode("\n", $f));

	@chmod($INITD_PATH,0755);

	if(is_file('/usr/sbin/update-rc.d')){
	shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
}

	if(is_file('/sbin/chkconfig')){
	shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
	shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
}

}
function iredmail(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$postconf=$unix->find_program("postconf");
	if(!is_file($postconf)){return;}
	$f[]="#! /bin/sh";
	$f[]="#";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:		iredmail";
	$f[]="# Required-Start:	\$syslog \$time \$local_fs \$remote_fs \$named \$network";
	$f[]="# Required-Stop:	\$syslog \$time \$local_fs \$remote_fs";
	$f[]="# Default-Start:	2 3 4 5";
	$f[]="# Default-Stop:		0 1 6";
	$f[]="# Short-Description:	Start the iredmail service";
	$f[]="# Description:		Enable iredmail";
	$f[]="### END INIT INFO";

	$f[]="PATH=/sbin:/bin:/usr/sbin:/usr/bin";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php /usr/share/artica-postfix/exec.iredmail.php --start \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/exec.iredmail.php --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/exec.iredmail.php --restart \$2 \$3";
	$f[]="    ;;";
	$f[]="";	
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/exec.iredmail.php --reload \$2 \$3";
	$f[]="    ;;";	
	
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";

	$INITD_PATH="/etc/init.d/iredmail";
	echo "OpenDKIM: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);@file_put_contents($INITD_PATH, @implode("\n", $f));

	@chmod($INITD_PATH,0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}

}



function vde_switch(){
	return;
	$unix=new unix();
	$Masterbin=$unix->find_program("vde_pcapplug");
	if(!is_file($Masterbin)){return;}
	$php=$unix->LOCATE_PHP5_BIN();
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          vde-switch";
	$f[]="# Required-Start:    \$all";
	$f[]="# Required-Stop:     \$local_fs";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: vde-switch";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: vde-switch";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php /usr/share/artica-postfix/exec.vde.php --start \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/exec.vde.php --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/exec.vde.php --restart \$2 \$3";
	$f[]="    ;;";
	$f[]=" reconfigure)";
	$f[]="    $php /usr/share/artica-postfix/exec.vde.php --reconfigure \$2 \$3";
	$f[]="    ;;";	
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";

	$INITD_PATH="/etc/init.d/vde_switch";
	echo "mailarchive-perl: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);@file_put_contents($INITD_PATH, @implode("\n", $f));

	@chmod($INITD_PATH,0755);

	if(is_file('/usr/sbin/update-rc.d')){
	shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
}

	if(is_file('/sbin/chkconfig')){
	shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
	shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
}

function specialreboot(){
	if(!is_dir("/etc/rc6.d")){return;}
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          StopWatchdog";
	$f[]="# Required-Start:    \$local_fs";
	$f[]="# Required-Stop:     \$local_fs";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Stop Artica Watchdogs";
	$f[]="# chkconfig: 56 11 89";
	$f[]="# description: Stop Artica Watchdogs";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="	 echo \"Stopping all Artica watchdogs...\"";
	$f[]="   /etc/init.d/monit stop";
	$f[]="   /etc/init.d/artica-status stop";
	$f[]="   /etc/init.d/artica-postfix stop watchdog";
	$f[]="	 echo \"Stopping all Artica watchdogs done\"";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} {ldap|} (+ 'debug' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	
	if(is_file("/etc/rc6.d/K00StopWatchdog")){@unlink("/etc/rc6.d/K00StopWatchdog");}
	$INITD_PATH="/etc/init.d/StopWatchdog";
	@file_put_contents("/etc/init.d/StopWatchdog", @implode("\n", $f));
	@chmod("/etc/init.d/StopWatchdog",0755);
	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults 1 >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
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
	shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
}


shell_exec("$php ". dirname(__FILE__)."/exec.initd-swap.php");

}





function rsyslogd_bug_check(){
	if(!is_file("/etc/init.d/rsyslog")){return;}
	$f=explode("\n",@file_get_contents("/etc/init.d/rsyslog"));
	while (list ($index, $ligne) = each ($f) ){
		if(preg_match("#Provides:\s+mysql#", $ligne)){rsyslogd_init();return;}
		
	}
	
	
}

function rsyslogd_init(){
	if(!is_file('/usr/sbin/rsyslogd')){return;}
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
	$stopmaillog="/etc/init.d/postfix stop-logger";
	$startmaillog="/etc/init.d/postfix start-logger";
	$restartmaillog="/etc/init.d/postfix-logger restart";
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
	$f[]="# Provides:          rsyslog-artica";
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
	$f[]="  /etc/init.d/auth-tail start";
	$f[]="  /etc/init.d/artica-syslog restart";
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
	$f[]="  /etc/init.d/auth-tail stop";
	$f[]="  /etc/init.d/artica-syslog restart";
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
	$f[]="  /etc/init.d/auth-tail restart";
	$f[]="  /etc/init.d/artica-syslog restart";
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
	
	@file_put_contents("/etc/init.d/syslog", @implode("\n", $f));
	shell_exec($unix->find_program("chmod")." 0755 /etc/init.d/syslog");
	
	if(!is_file("/etc/init.d/rsyslog")){
		@file_put_contents("/etc/init.d/rsyslog", @implode("\n", $f));
		shell_exec($unix->find_program("chmod")." 0755 /etc/init.d/rsyslog");
	}
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
	
	if(!is_file($servicebin)){return;}
	$php=$unix->LOCATE_PHP5_BIN();
	if(!is_file($servicebin)){return;}
	$EnableDNSMASQ=intval($sock->GET_INFO("EnableDNSMASQ"));
	
	
	
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/dnsmasq";
	$php5script="exec.dnsmasq.php";
	$daemonbinLog="DNSMASQ Daemon";



	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         dnsmasq";
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
	$f[]=" reload)";
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
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}

	
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

function sargweb(){
	$unix=new unix();
	$sock=new sockets();
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/sarg-web";
	$php5script="exec.sarg-web.php";
	$daemonbinLog="SARG Web service";
	
	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         ufdbcat";
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
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reload} (+ '--verbose' for more infos)\"";
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
		if(is_file($INITD_PATH)){shell_exec("/usr/sbin/update-rc.d -f ".basename($INITD_PATH)." remove");}
	}
	
	if(is_file('/sbin/chkconfig')){
			shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	
		
	
}

function syncthing(){
	$unix=new unix();
	$sock=new sockets();
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/syncthing";
	$php5script="exec.syncthing.php";
	$daemonbinLog="Cloud Sync Daemon";
	
	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         syncthing";
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
	$f[]="HOME=\"/home/syncthing\"";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    ;;";
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart2)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    /etc/init.d/artica-status restart --force \$2 \$3";
	$f[]="    ;;";
	$f[]="";	
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reload} (+ '--verbose' for more infos)\"";
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
		if(is_file($INITD_PATH)){shell_exec("/usr/sbin/update-rc.d -f ".basename($INITD_PATH)." remove");}
	}
	
	if(is_file('/sbin/chkconfig')){
			shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
		
	
	
}

function ufdbcat(){
	$unix=new unix();
	$sock=new sockets();
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/ufdbcat";
	$php5script="exec.ufdbcat.php";
	$daemonbinLog="Categorize Daemon";
	
	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         ufdbcat";
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
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reload} (+ '--verbose' for more infos)\"";
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
		if(is_file($INITD_PATH)){shell_exec("/usr/sbin/update-rc.d -f ".basename($INITD_PATH)." remove");}
	}
	
	if(is_file('/sbin/chkconfig')){
			shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	
	
}

function ufdbguard(){
	$unix=new unix();
	$sock=new sockets();
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/ufdb";
	$php5script="exec.ufdb.php";
	$daemonbinLog="UfdbGuard Web filter";
	

	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         ufdb";
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
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";	
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" rotatelog)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --rotatelog \$2 \$3";
	$f[]="    ;;";
	$f[]="";	
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|rotatelog} (+ '--verbose' for more infos)\"";
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
		if(is_file($INITD_PATH)){shell_exec("/usr/sbin/update-rc.d -f ".basename($INITD_PATH)." remove");}
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	if(!is_file("/etc/init.d/ufdb-tail")){ufdb_tail();}
		
}


function ufdbguard_client(){
	$unix=new unix();
	$sock=new sockets();
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/ufdb-client";
	$php5script="exec.ufdb-client.php";
	$daemonbinLog="UfdbGuard Web filter Client";


	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         ufdb-client";
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
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
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
		if(is_file($INITD_PATH)){shell_exec("/usr/sbin/update-rc.d -f ".basename($INITD_PATH)." remove");}
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	

}




function iscsitarget(){
	iscsitarget_debian();
}

function iscsitarget_debian(){
	if($GLOBALS["VERBOSE"]){echo "iscsitarget_debian()\n";}
	if(!is_file('/usr/sbin/update-rc.d')){
		echo "iscsitarget: [INFO] /usr/sbin/update-rc.d no such binary\n";
		return;}
	$unix=new unix();
	$sock=new sockets();
	$ietd=$unix->find_program("ietd");
	$php=$unix->LOCATE_PHP5_BIN();
	if(!is_file($ietd)){
		echo "iscsitarget: [INFO] ietd no such binary\n";
		return;}
	$EnableISCSI=$sock->GET_INFO("EnableISCSI");
	if(!is_numeric($EnableISCSI)){$EnableISCSI=0;}
	
	$deflog_start="Starting......: ".date("H:i:s")." [INIT]: iSCSI target";
	$deflog_sstop="Stopping......: ".date("H:i:s")." [INIT]: iSCSI target";
	if($EnableISCSI==0){$EnableISCSI_BOOL="false";}else{$EnableISCSI_BOOL="true";}
	$php5=$unix->LOCATE_PHP5_BIN();
	
	$f[]="#!/bin/sh";
	$f[]="#";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          cluster manager";
	$f[]="# Required-Start:    \$network \$time";
	$f[]="# Required-Stop:     \$network \$time";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Starts and stops the iSCSI target";
	$f[]="### END INIT INFO";
	$f[]="";
	$f[]="PID_FILE=/var/run/iscsi_trgt.pid";
	$f[]="CONFIG_FILE=/etc/ietd.conf";
	$f[]="DAEMON=$ietd";
	$f[]="";
	$f[]="PATH=/sbin:/bin:/usr/sbin:/usr/bin";
	$f[]="";
	$f[]="# Don't touch this \"memsize thingy\" unless you are blessed";
	$f[]="# with knowledge about it.";
	$f[]="MEM_SIZE=1048576";
	$f[]="";
	$f[]=". /lib/lsb/init-functions # log_{warn,failure}_msg";
	$f[]="# EnableISCSI = $EnableISCSI";
	$f[]="ISCSITARGET_ENABLE=$EnableISCSI_BOOL";
	$f[]="";
	$f[]="configure_memsize()";
	$f[]="{";
	$f[]="    if [ -e /proc/sys/net/core/wmem_max ]; then";
	$f[]="        echo \${MEM_SIZE} > /proc/sys/net/core/wmem_max";
	$f[]="    fi";
	$f[]="";
	$f[]="    if [ -e /proc/sys/net/core/rmem_max ]; then";
	$f[]="        echo \${MEM_SIZE} > /proc/sys/net/core/rmem_max";
	$f[]="    fi";
	$f[]="";
	$f[]="    if [ -e /proc/sys/net/core/wmem_default ]; then";
	$f[]="        echo \${MEM_SIZE} > /proc/sys/net/core/wmem_default";
	$f[]="    fi";
	$f[]="";
	$f[]="    if [ -e /proc/sys/net/core/rmem_default ]; then";
	$f[]="        echo \${MEM_SIZE} > /proc/sys/net/core/rmem_default";
	$f[]="    fi";
	$f[]="";
	$f[]="    if [ -e /proc/sys/net/ipv4/tcp_mem ]; then";
	$f[]="        echo \"\${MEM_SIZE} \${MEM_SIZE} \${MEM_SIZE}\" > /proc/sys/net/ipv4/tcp_mem";
	$f[]="    fi";
	$f[]="";
	$f[]="    if [ -e  /proc/sys/net/ipv4/tcp_rmem ]; then";
	$f[]="        echo \"\${MEM_SIZE} \${MEM_SIZE} \${MEM_SIZE}\" > /proc/sys/net/ipv4/tcp_rmem";
	$f[]="    fi";
	$f[]="";
	$f[]="    if [ -e /proc/sys/net/ipv4/tcp_wmem ]; then";
	$f[]="        echo \"\${MEM_SIZE} \${MEM_SIZE} \${MEM_SIZE}\" > /proc/sys/net/ipv4/tcp_wmem";
	$f[]="    fi";
	$f[]="}";
	$f[]="";
	$f[]="RETVAL=0";
	$f[]="";
	$f[]="ietd_start()";
	$f[]="{";
	$f[]="	log_daemon_msg \"$deflog_start service\"";
	$f[]="	configure_memsize";
	$f[]="	modprobe -q crc32c && modprobe -q iscsi_trgt";
	$f[]="	RETVAL=\$?";
	$f[]="	if [ \$RETVAL != \"0\" ] ;  then ";
	$f[]="		log_end_msg 1";
	$f[]="		exit \$RETVAL";
	$f[]="	fi";
	$f[]="	start-stop-daemon --start --exec \$DAEMON --quiet --oknodo";
	$f[]="	RETVAL=\$?";
	$f[]="	if [ \$RETVAL != \"0\" ]; then";
	$f[]="		log_end_msg 1";
	$f[]="		exit \$RETVAL";
	$f[]="	fi";
	$f[]="	log_end_msg 0";
	$f[]="	exit 0";
	$f[]="}";
	$f[]="	";
	$f[]="ietd_stop()";
	$f[]="{";
	$f[]="	log_daemon_msg \"Removing iSCSI enterprise target devices\"";
	$f[]="	pgrep -s `cat \$PID_FILE 2>/dev/null || echo \"x\"` >/dev/null 2>&1 ";
	$f[]="	RETVAL=\$?";
	$f[]="	if [ \$RETVAL = \"0\" ] ; then";
	$f[]="		# ugly, but ietadm does not allways provides correct exit values";
	$f[]="		RETURN=`ietadm --op delete 2>&1`";
	$f[]="		RETVAL=\$?";
	$f[]="		if [ \$RETVAL = \"0\" ] && [ \"\$RETURN\" != \"something wrong\" ] ; then";
	$f[]="			log_end_msg 0";
	$f[]="		else";
	$f[]="			log_end_msg 1";
	$f[]="			log_failure_msg \"$deflog_sstop Failed with reason: \$RETURN\"";
	$f[]="			exit \$RETVAL";
	$f[]="		fi";
	$f[]="		log_daemon_msg \"$deflog_sstop service\"";
	$f[]="		start-stop-daemon --stop --quiet --exec \$DAEMON --pidfile \$PID_FILE --oknodo";
	$f[]="		RETVAL=\$?";
	$f[]="		if [ \$RETVAL != \"0\" ]; then";
	$f[]="			log_end_msg 1";
	$f[]="		else ";
	$f[]="			log_end_msg 0";
	$f[]="		fi";
	$f[]="	else";
	$f[]="		log_end_msg 0";
	$f[]="	fi";
	$f[]="	# ugly, but pid file is not removed ba ietd";
	$f[]="	rm -f \$PID_FILE 2>/dev/null";
	$f[]="	";
	$f[]="	# check if the module is loaded at all";
	$f[]="	lsmod | grep -q iscsi_trgt";
	$f[]="	RETVAL=\$?";
	$f[]="	if [ \$RETVAL = \"0\" ] ; then";
	$f[]="		log_warning_msg \"$deflog_sstop Removing iSCSI enterprise target modules (iscsi_trgt,crc32c)\"";
	$f[]="		modprobe -r iscsi_trgt 2>/dev/null && modprobe -q crc32c 2>/dev/null";
	$f[]="		RETVAL=\$?";
	$f[]="		if [ \$RETVAL = \"0\" ]; then";
	$f[]="			log_end_msg 0";
	$f[]="		else";
	$f[]="			log_end_msg 1";
	$f[]="			# Lack of module unloading should be reported,";
	$f[]="			# but not necessarily exit non-zero";
	$f[]="		fi";
	$f[]="	fi";
	$f[]="}";
	$f[]="";
	$f[]="case \"\$1\" in";
	$f[]="  start)";
	$f[]="        if [ \"\$ISCSITARGET_ENABLE\" = \"true\" ]; then";
	$f[]="            ietd_start";
	$f[]="        else";
	$f[]="            log_warning_msg \"$deflog_start iscsitarget not enabled not starting...\"";
	$f[]="        fi";
	$f[]="        ;;";
	$f[]="  stop)";
	$f[]="        ietd_stop";
	$f[]="        $php /usr/share/artica-postfix/exec.iscsi.php --stop";
	$f[]="        ;;";
	$f[]="  restart|force-reload)";
	$f[]="        ietd_stop";
	$f[]="	sleep 1";
	$f[]="        if [ \"\$ISCSITARGET_ENABLE\" = \"true\" ]; then";
	$f[]="        	  $php5 /usr/share/artica-postfix/exec.iscsi.php --build";
	$f[]="            ietd_start";
	$f[]="        else";
	$f[]="            log_warning_msg \"$deflog_start iscsitarget not enabled not starting...\"";
	$f[]="        fi";
	$f[]="        ;;";
	$f[]="  status)";
	$f[]="	status_of_proc -p \$PID_FILE \$DAEMON \"iSCSI enterprise target\" && exit 0 || exit \$?";
	$f[]="	;;";
	$f[]="  *)";
	$f[]="        log_action_msg \"Usage: \$0 {start|stop|restart|status}\"";
	$f[]="        exit 1";
	$f[]="esac";
	$f[]="";
	$f[]="exit 0";
	$f[]="";	
	
	$INITD_PATH="/etc/init.d/iscsitarget";
	echo "iscsitarget: [INFO] Writing /etc/init.d/iscsitarget with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);
	
	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}
	
	
}

function LOCATE_SQUID_BIN(){
	$unix=new unix();
	if(isset($GLOBALS["UNIX_LOCATE_SQUID_BIN"])){return $GLOBALS["UNIX_LOCATE_SQUID_BIN"];}
	$GLOBALS["UNIX_LOCATE_SQUID_BIN"]=$unix->find_program("squid3");
	if(!is_file($GLOBALS["UNIX_LOCATE_SQUID_BIN"])){$GLOBALS["UNIX_LOCATE_SQUID_BIN"]=$unix->find_program("squid");}
	return $GLOBALS["UNIX_LOCATE_SQUID_BIN"];

}




function squid_db(){

	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$squid=LOCATE_SQUID_BIN();
	$SCRIPTFILENAME=dirname(__FILE__)."/exec.squid-db.php";
	
	if($GLOBALS["VERBOSE"]){
		echo "Starting......: ".date("H:i:s")." [INIT]: PHP...: $php\n";
		echo "Starting......: ".date("H:i:s")." [INIT]: Squid.: $squid\n";
		echo "Starting......: ".date("H:i:s")." [INIT]: Script: $SCRIPTFILENAME\n";
	}
	
	if(!is_file($squid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: MySQL daemon (squid-db) no such squid\n";}
		return;}
	if(!is_file("/etc/artica-postfix/FROM_ISO")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: MySQL daemon (squid-db) not from ISO\n";}
		return;}
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         squid-db";
	$f[]="# Required-Start:    \$local_fs \$remote_fs \$syslog \$named \$network \$time";
	$f[]="# Required-Stop:     \$local_fs \$remote_fs \$syslog \$named \$network";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Squid MySQL Statistics database";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Squid MySQL Statistics database";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php $SCRIPTFILENAME --start --byinitd \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php $SCRIPTFILENAME --stop --byinitd --force \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";

	$f[]="    $php $SCRIPTFILENAME --stop --byinitd --force \$2 \$3";
	$f[]="    $php $SCRIPTFILENAME --start --byinitd \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} {ldap|} (+ 'debug' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	
	@file_put_contents("/etc/init.d/squid-db", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: MySQL daemon (squid-db) /etc/init.d/squid-db done\n";}
	@chmod("/etc/init.d/squid-db",0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec('/usr/sbin/update-rc.d -f squid-db defaults >/dev/null 2>&1');

	}

	if(is_file('/sbin/chkconfig')){
		shell_exec('/sbin/chkconfig --add squid-db >/dev/null 2>&1');
		shell_exec('/sbin/chkconfig --level 2345 squid-db on >/dev/null 2>&1');
	}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: MySQL daemon (squid-db) success...\n";}
}

