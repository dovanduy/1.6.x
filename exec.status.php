<?php

if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["SCHEDULE_ID"]=0;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
$GLOBALS["FORCE"]=false;
$GLOBALS["EXECUTED_AS_ROOT"]=true;
$GLOBALS["RUN_AS_DAEMON"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["DISABLE_WATCHDOG"]=false;
$GLOBALS["BASE_ROOT"]=dirname(__FILE__);
$GLOBALS["NOSTATUSTIME"]=false;
$GLOBALS["MY-POINTER"]="/etc/artica-postfix/pids/". basename(__FILE__).".pointer";
$GLOBALS["COMMANDLINE"]=implode(" ",$argv);
if(strpos($GLOBALS["COMMANDLINE"],"--verbose")>0){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;$GLOBALS["DEBUG"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--nowachdog#",$GLOBALS["COMMANDLINE"])){$GLOBALS["DISABLE_WATCHDOG"]=true;}
if(preg_match("#--force#",$GLOBALS["COMMANDLINE"])){$GLOBALS["FORCE"]=true;}
if($GLOBALS["VERBOSE"]){echo "LoadIncludes();\n";}
LoadIncludes();

// $GLOBALS["PHP5"]=  $GLOBALS["nohup"] $GLOBALS["DISABLE_WATCHDOG"]
$sock=new sockets();
$DisableArticaStatusService=$sock->GET_INFO("DisableArticaStatusService");
if(!is_numeric($DisableArticaStatusService)){$DisableArticaStatusService=0;}
$unix=new unix();

if(is_file("/etc/artica-postfix/FROM_ISO")){
	if($unix->file_time_min("/etc/artica-postfix/FROM_ISO")<1){die();}
}
$GLOBALS["SQUID_INSTALLED"]=false;
$GLOBALS["ArticaWatchDogList"]=unserialize(base64_decode($sock->GET_INFO("ArticaWatchDogList")));
$GLOBALS["PHP5"]=$unix->LOCATE_PHP5_BIN(); 
$GLOBALS["NICE"]=$unix->EXEC_NICE();
$GLOBALS["nohup"]=$unix->find_program("nohup");
$GLOBALS["CHMOD"]=$unix->find_program("chmod");
$GLOBALS["CHOWN"]=$unix->find_program("chown");
$GLOBALS["KILLBIN"]=$unix->find_program("kill");
$GLOBALS["SquidRotateOnlySchedule"]=intval($sock->GET_INFO("SquidRotateOnlySchedule"));
$GLOBALS["SQUID_BIN"]=$unix->LOCATE_SQUID_BIN();
if(is_file($GLOBALS["SQUID_BIN"])){$GLOBALS["SQUID_INSTALLED"]=true;}
$GLOBALS["KILL"]=$GLOBALS["KILLBIN"];
if($GLOBALS["VERBOSE"]){echo "DEBUG MODE ENABLED\n";}
if($GLOBALS["VERBOSE"]){echo "command line: {$GLOBALS["COMMANDLINE"]}\n";}
$GLOBALS["AMAVIS_WATCHDOG"]=unserialize(@file_get_contents("/etc/artica-postfix/amavis.watchdog.cache"));
$GLOBALS["TOTAL_MEMORY_MB"]=$unix->TOTAL_MEMORY_MB();



if(!is_dir("/var/log/artica-postfix/rotate_events")){@mkdir("/var/log/artica-postfix/rotate_events",0755,true);}
if(!is_dir("/etc/artica-postfix/settings/Mysql")){@mkdir("/etc/artica-postfix/settings/Mysql",0755,true);}

$sock=null;
$unix=null;



if(strlen($argv[1])>0){
	events("parsing command line ".@implode(";", $argv),"MAIN",__LINE__);
	$GLOBALS["CLASS_UNIX"]=new unix();
	$GLOBALS["CLASS_SOCKETS"]=new sockets();
	$GLOBALS["CLASS_USERS"]=new settings_inc();
	include_once('/usr/share/artica-postfix/ressources/class.status.videocache.inc');
	include_once('/usr/share/artica-postfix/ressources/class.status.squid.inc');
	include_once('/usr/share/artica-postfix/ressources/class.status.postfix.inc');
	CheckCallable();
}

$mem=round(((memory_get_usage()/1024)/1000),2);events("{$mem}MB after declarations","MAIN",__LINE__);

if(strlen($argv[1])>2){events("parsing command line {$argv[1]}","MAIN",__LINE__);}
if($argv[1]=="--klms"){echo klms_status();echo klmsdb_status();echo klms_milter();die();}
if($argv[1]=="--reboot"){echo reboot();exit;}
if($argv[1]=="--all"){
		events("-> launch_all_status_cmdline()","MAIN",__LINE__);
		$GLOBALS["NOSTATUSTIME"]=true;
		launch_all_status_cmdline();
		if($GLOBALS["VERBOSE"]){echo "DONE\n\n\n";}
		die();
}
if($argv[1]=="--free"){echo getmem();exit;}
if($argv[1]=="--squid"){echo squid_master_status();exit;}
if($argv[1]=="--c-icap"){echo c_icap_master_status();exit;}
if($argv[1]=="--kav4proxy"){echo kav4Proxy_status();exit;}
if($argv[1]=="--dansguardian"){echo dansguardian_master_status();exit;}
if($argv[1]=="--wifi"){echo wpa_supplicant();;exit;}
if($argv[1]=="--fetchmail"){echo fetchmail();;exit;}
if($argv[1]=="--milter-greylist"){echo milter_greylist();;exit;}
if($argv[1]=="--framework"){echo framework();;exit;}
if($argv[1]=="--pdns"){echo pdns_server()."\n".pdns_recursor();exit;}
if($argv[1]=="--cyrus-imap"){echo cyrus_imap();exit;}
if($argv[1]=="--mysql"){echo "\n".mysql_server()."\n".mysql_mgmt()."\n". mysql_replica();exit;}
if($argv[1]=="--openldap"){echo "\n".openldap();;exit;}
if($argv[1]=="--saslauthd"){echo "\n".saslauthd();;exit;}
if($argv[1]=="--sysloger"){echo "\n".syslogger();;exit;}
if($argv[1]=="--squid-tail"){exit;}
if($argv[1]=="--amavis"){echo "\n".amavis();exit;}
if($argv[1]=="--amavis-milter"){echo"\n". amavis_milter();exit;}
if($argv[1]=="--amavisdb"){echo"\n". amavisdb();exit;}
if($argv[1]=="--xmail"){XMail();exim4();exit;}
if($argv[1]=="--bwm-ng"){echo bwm_ng();exit;}
if($argv[1]=="--ntopng"){echo ntopng()."\n".redis_server();exit;}
if($argv[1]=="--load-stats"){$GLOBALS["VERBOSE"]=true;load_stats();exit;}
if($argv[1]=="--vsftpd"){echo vsftpd();exit;}


if($argv[1]=="--boa"){echo"\n". boa();exit;}
if($argv[1]=="--lighttpd"){echo"\n". lighttpd();exit;}

if($argv[1]=="--clamav"){echo"\n". clamd()."\n".clamscan()."\n".clammilter()."\n".freshclam(); exit;}
if($argv[1]=="--retranslator"){echo"\n". retranslator_httpd(); exit;}
if($argv[1]=="--spamassassin"){echo spamassassin_milter()."\n".spamassassin();exit;}
if($argv[1]=="--postfix"){
	if($GLOBALS["VERBOSE"]){echo "Running postfix\n";}
	echo "\n".postfix();exit;}
if($argv[1]=="--postfix-logger"){echo "\n".postfix_logger();exit;}
if($argv[1]=="--mailman"){echo "\n".mailman();exit;}
if($argv[1]=="--kas3"){echo "\n".kas3_milter()."\n".kas3_ap(); exit;}
if($argv[1]=="--samba"){$GLOBALS["DISABLE_WATCHDOG"]=true;echo "\n".smbd()."\n".nmbd()."\n".winbindd()."\n".scanned_only()."\n"; exit;}
if($argv[1]=="--roundcube"){echo "\n".roundcube()."\n".roundcube_db();exit;}
if($argv[1]=="--cups"){echo "\n".cups();exit;}
if($argv[1]=="--apache-groupware"){echo "\n".apache_groupware();exit;}
if($argv[1]=="--gdm"){echo "\n".gdm();exit;}
if($argv[1]=="--console-kit"){echo "\n".consolekit();exit;}
if($argv[1]=="--xfce"){echo "\n".xfce();exit;}
if($argv[1]=="--vmtools"){echo "\n".vmtools();exit;}
if($argv[1]=="--hamachi"){echo "\n".hamachi();exit;}
if($argv[1]=="--artica-notifier"){echo "\n".artica_notifier();exit;}
if($argv[1]=="--dhcpd"){echo "\n".dhcpd_server();exit;}
if($argv[1]=="--pure-ftpd"){echo "\n".pure_ftpd();exit;}
if($argv[1]=="--mldonkey"){echo "\n".mldonkey();exit;}
if($argv[1]=="--policydw"){echo "\n".policyd_weight();exit;}
if($argv[1]=="--backuppc"){echo "\n".backuppc();exit;}
if($argv[1]=="--kav4fs"){echo "\n".kav4fs()."\n".kav4fsavs();exit;}
if($argv[1]=="--ocsweb"){echo "\n".apache_ocsweb()."\n".apache_ocsweb_download()."\n";exit;}
if($argv[1]=="--ocsagent"){echo "\n".ocs_agent();exit;}
if($argv[1]=="--openssh"){echo "\n".openssh();exit;}
if($argv[1]=="--gluster"){echo "\n".gluster();exit;}
if($argv[1]=="--auditd"){echo "\n".auditd();exit;}
if($argv[1]=="--squidguard-http"){echo "\n".squidguardweb();exit;}
if($argv[1]=="--opendkim"){echo "\n".opendkim();exit;}
if($argv[1]=="--ufdbguardd"){echo "\n".ufdbguardd()."\n".squidguardweb()."\n";ufdbguardd_client();exit;}
if($argv[1]=="--ufdb-tail"){echo "\n".ufdbguardd_tail();exit;}
if($argv[1]=="--squidguard-tail"){echo "\n".squidguard_logger();exit;}
if($argv[1]=="--dkim-milter"){echo "\n".milter_dkim();exit;}
if($argv[1]=="--dropbox"){echo "\n".dropbox();exit;}
if($argv[1]=="--artica-policy"){echo "\n".artica_policy();exit;}
if($argv[1]=="--vboxwebsrv"){echo "\n".virtualbox_webserv();exit;}
if($argv[1]=="--tftpd"){echo "\n".tftpd();exit;}
if($argv[1]=="--vdi"){echo "\n".virtualbox_webserv()."\n".tftpd()."\n".dhcpd_server();exit;}
if($argv[1]=="--crossroads"){echo "\n".crossroads();exit;}
if($argv[1]=="--artica-status"){echo "\n".artica_status();exit;}
if($argv[1]=="--artica-executor"){echo "\n".artica_executor();exit;}
if($argv[1]=="--artica-background"){echo "\n";exit;}
if($argv[1]=="--pptpd"){echo "\n".pptpd();exit;}
if($argv[1]=="--pptpd-clients"){echo "\n".pptp_clients();exit;}
if($argv[1]=="--bandwith"){echo "\n".bandwith();exit;}
if($argv[1]=="--apt-mirror"){echo "\n".apt_mirror();exit;}
if($argv[1]=="--squidclamav-tail"){echo "\n".squid_clamav_tail();exit;}
if($argv[1]=="--squidcache-tail"){echo "\n".squid_cache_tail();exit;}


if($argv[1]=="--ddclient"){echo "\n".ddclient();exit;}
if($argv[1]=="--cluebringer"){echo "\n".cluebringer();exit;}
if($argv[1]=="--apachesrc"){echo "\n".apachesrc();exit;}
if($argv[1]=="--assp"){echo "\n".assp();exit;}
if($argv[1]=="--freewebs"){echo "\n".apachesrc()."\n".pure_ftpd()."\n".tomcat()."\n".php_fpm()."\n".nginx()."\n".php_fcgi();exit;}
if($argv[1]=="--openvpn"){echo "\n".openvpn();exit;}
if($argv[1]=="--vboxguest"){echo "\n".vboxguest();exit;}
if($argv[1]=="--sabnzbdplus"){echo "\n".sabnzbdplus();exit;}
if($argv[1]=="--openvpn-clients"){echo "\n".OpenVPNClientsStatus();exit;}
if($argv[1]=="--stunnel"){echo "\n".stunnel();exit;}
if($argv[1]=="--meta-checks"){echo "\n".meta_checks();exit;}
if($argv[1]=="--smbd"){echo "\n".smbd();exit;}
if($argv[1]=="--vnstat"){echo "\n".vnstat();exit;}
if($argv[1]=="--munin"){echo "\n".munin();exit;}
if($argv[1]=="--autofs"){echo "\n".autofs();exit;}
if($argv[1]=="--greyhole"){echo "\n".greyhole();exit;}
if($argv[1]=="--amavis-watchdog"){echo "\n".AmavisWatchdog();exit;}
if($argv[1]=="--dnsmasq"){echo "\n".dnsmasq();exit;}
if($argv[1]=="--iscsi"){echo "\n".iscsi();exit;}
if($argv[1]=="--yorel"){echo "\n".watchdog_yorel();exit;}
if($argv[1]=="--watchdog-service"){echo "\n".WATCHDOG($argv[2],$argv[3]);exit;}
if($argv[1]=="--postfwd2"){echo "\n".postfwd2();exit;}
if($argv[1]=="--zarafa-watchdog"){zarafa_watchdog();exit;}
if($argv[1]=="--vps"){echo vps_servers();exit;}
if($argv[1]=="--crossroads-multiple"){echo crossroads_multiple();exit;}
if($argv[1]=="--smartd"){echo "\n".smartd();exit;}
if($argv[1]=="--watchdog-me"){echo watchdog_me();die();}
if($argv[1]=="--auth-tail"){echo auth_tail();exit;}
if($argv[1]=="--snort"){echo snort();exit;}
if($argv[1]=="--xload"){echo xLoadAvg();$GLOBALS["VERBOSE"]=true;exit;}
if($argv[1]=="--greyhole-watchdog"){greyhole_watchdog();exit;}
if($argv[1]=="--greensql"){echo greensql();exit;}
if($argv[1]=="--nscd"){echo nscd();exit;}
if($argv[1]=="--tomcat"){echo tomcat();exit;}
if($argv[1]=="--cgroups"){echo cgroups();exit;}
if($argv[1]=="--openemm"){echo openemm()."\n".openemm_sendmail();exit;}
if($argv[1]=="--exec-nice"){$GLOBALS["VERBOSE"]=true;echo "\"{$GLOBALS["CLASS_UNIX"]->EXEC_NICE()}\"\n";die();}
if($argv[1]=="--ntpd"){echo ntpd_server();die();}
if($argv[1]=="--ps-mem"){echo ps_mem();die();}
if($argv[1]=="--arpd"){echo arpd();die();}
if($argv[1]=="--netatalk"){echo netatalk();die();}
if($argv[1]=="--yaffas"){echo yaffas();die();}
if($argv[1]=="--network"){echo ifconfig_network();die();}
if($argv[1]=="--avahi-daemon"){echo avahi_daemon();die();}
if($argv[1]=="--time-capsule"){echo avahi_daemon(); echo "\n";echo netatalk();die();}
if($argv[1]=="--rrd"){echo testingrrd();die();}
if($argv[1]=="--memcached"){echo memcached();die();}
if($argv[1]=="--monit"){echo monit();die();}
if($argv[1]=="--UpdateUtility"){echo UpdateUtilityHTTP();die();}
if($argv[1]=="--zarafa-web"){echo zarafa_web();die();}
if($argv[1]=="--ejabberd"){echo ejabberd()."\n";echo pymsnt();die();}
if($argv[1]=="--lighttpd-all"){echo lighttpd()."\n";echo framework();die();}
if($argv[1]=="--arkeia"){echo arkwsd()."\n";echo arkeiad();die();}
if($argv[1]=="--haproxy"){echo haproxy();die();}
if($argv[1]=="--mailman"){echo mailman();die();}
if($argv[1]=="--mimedefang"){echo mimedefang()."\n".mimedefangmx();die();}
if($argv[1]=="--mailarchiver"){echo mailarchiver();die();}
if($argv[1]=="--articadb"){echo articadb();die();}
if($argv[1]=="--maillog"){echo maillog_watchdog();die();}
if($argv[1]=="--freeradius"){echo freeradius();die();}
if($argv[1]=="--php-pfm"){echo php_fpm()."\n".php_fcgi();die();}
if($argv[1]=="--syslog-db"){echo syslog_db();die();}
if($argv[1]=="--nginx"){echo nginx()."\n".nginx_db();die();}
if($argv[1]=="--haarp"){echo haarp();die();}

if($argv[1]=="--ftp-proxy"){echo ftp_proxy()."\n";die();}
if($argv[1]=="--rsync-debian-mirror"){echo rsync_debian_mirror()."\n";die();}
if($argv[1]=="--cntlm"){echo cntlm()."\n";die();}
if($argv[1]=="--roundcube-db"){echo roundcube_db()."\n";die();}
if($argv[1]=="--rdpproxy"){echo rdpproxy()."\n".rdpproxy_authhook();die();}
if($argv[1]=="--dnscache"){echo dnsmasq();die();}
if($argv[1]=="--vde-uniq"){echo vde_uniq($argv[2]);die();}
if($argv[1]=="--vde-all"){echo vde_all();die();}
if($argv[1]=="--ufdb"){echo ufdbguardd()."\n".ufdbguardd_client();die();}
if($argv[1]=="--ucarp"){echo ucarp();die();}
if($argv[1]=="--squid-db"){echo squid_db();die();}
if($argv[1]=="--sarg"){echo sarg();die();}
if($argv[1]=="--snmpd"){echo snmpd();die();}
if($argv[1]=="--squid-nat"){echo squid_nat();die();}
if($argv[1]=="--ziproxy"){echo ziproxy();die();}
if($argv[1]=="--iredmail"){echo iredmail();die();}


if($argv[1]=="--videocache"){
	
	$conf[]=videocache();
	$conf[]=videocache_scheduler();
	$conf[]=videocache_clients();
	echo @implode("\n",$conf);
	die();
}


if($GLOBALS["VERBOSE"]){echo "cannot understand {$argv[1]} assume perhaps it is a function\n";}


if($argv[1]=="--functions"){
	$arr = get_defined_functions();
	print_r($arr);
	die();
}



if($argv[1]=="--all-squid"){
	$cachefile="/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS";
	$GLOBALS["DISABLE_WATCHDOG"]=true;
	if(system_is_overloaded(__FILE__)){
		if($GLOBALS["SCHEDULE_ID"]>0){die();}
		if(is_file($cachefile)){
			echo @file_get_contents($cachefile);
			die();
		}
	}
	
	$conf[]=squid_master_status(true);
	$conf[]=kav4Proxy_status();
	$conf[]=proxy_pac_status();
	
	$conf[]=squidguardweb();
	$conf[]=ufdbguardd();
	$conf[]=freshclam();
	$conf[]=articadb();
	$conf[]=winbindd();
	$conf[]=squid_db();
	$conf[]=haarp();

	$conf[]=nginx();
	$conf[]=ftp_proxy();
	$conf[]=c_icap_master_status();
	$conf[]=cntlm();
	$conf[]=rdpproxy_authhook();
	$conf[]=rdpproxy();
	$conf[]=clamd();
	$conf[]=dnsmasq();
	$conf[]=ufdbguardd_client();
	$conf[]=dnsmasq_squid();
	$conf[]=ucarp();
	$conf[]=hotspot_web();
	$conf[]=hotspot_fw();
	$conf[]=sarg();
	$conf[]=squid_nat();
	$conf[]=ziproxy();
	
	@unlink($cachefile);
	@file_put_contents($cachefile, @implode("\n",$conf));
	@chmod($cachefile, 0755);
	echo @implode("\n",$conf);
	die();
	
}

if($argv[1]=="--hotspot"){
	$conf[]=hotspot_web();
	$conf[]=hotspot_fw();
	echo @implode("\n", $conf)."\n";
	die();
}

if($argv[1]=="--zarafa"){
	$GLOBALS["DISABLE_WATCHDOG"]=true;
	$conf[]=zarafa_web();
	$conf[]=zarafa_ical();
	$conf[]=zarafa_dagent();
	$conf[]=zarafa_monitor();
	$conf[]=zarafa_gateway();
	$conf[]=zarafa_spooler();
	$conf[]=zarafa_server();
	$conf[]=zarafa_server2();
	$conf[]=zarafa_licensed();
	$conf[]=zarafa_db();
	$conf[]=zarafa_indexer();
	$conf[]=yaffas();
	$conf[]=zarafa_multi();
	$conf[]=zarafa_search();
	$conf[]=php_fpm();
	echo @implode("\n",$conf);
	die();
}



if($argv[1]=="--amavis-full"){
	$conf[]=spamassassin();
	$conf[]=clamd();
	$conf[]=amavis();
	$conf[]=amavis_milter();
	$conf[]=amavisdb();
	
	echo @implode("\n",$conf);
	die();
}

$unix=new unix();

if($argv[1]=="--verbose"){unset($argv[1]);}
CheckNetInterfaces();
$time=$unix->file_time_get("exec.checkfolder-permissions.php");
if($time>10){
	print "Starting......: ".date("H:i:s")." artica-status building permissions..\n";
	shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.checkfolder-permissions.php >/dev/null 2>&1 &");
}




if(isset($argv[1])){
	if(strlen($argv[1])>0){
		write_syslog("Unable to understand {$argv[1]}",basename(__FILE__));
		die();
	}
}


if($DisableArticaStatusService==1){
	if(systemMaxOverloaded(basename(__FILE__))){events("OVERLOADED !! aborting","MAIN",__LINE__);die();}
	events("-> launch_all_status()","MAIN",__LINE__);
	launch_all_status();
	die();
}


$pidfile="/etc/artica-postfix/".basename(__FILE__).".pid";
$pid=@file_get_contents($pidfile);

if($unix->process_exists($pid,(basename(__FILE__)))){
	print "Starting......: ".date("H:i:s")." artica-status Already executed PID $pid...\n";
	die();
}
$mem=round(((memory_get_usage()/1024)/1000),2);events("{$mem}MB artica-status System Memory: {$GLOBALS["TOTAL_MEMORY_MB"]}MB","MAIN",__LINE__);
print "Starting......: ".date("H:i:s")." artica-status system memory: {$GLOBALS["TOTAL_MEMORY_MB"]}MB\n";
if(!function_exists("pcntl_fork")){$nofork=true;}
if($GLOBALS["TOTAL_MEMORY_MB"]<400){$nofork=true;}
if($DisableArticaStatusService==1){$nofork=true;}


if($nofork){
	if(systemMaxOverloaded(basename(__FILE__))){events("OVERLOADED !! aborting","MAIN",__LINE__);die();}
	print "Starting......: ".date("H:i:s")." artica-status pcntl_fork module not loaded !\n";
	$pidfile="/etc/artica-postfix/".basename(__FILE__).".pid";
	$childpid=posix_getpid();
	events("{$mem}MB artica-status Memory NO fork.... pid=$childpid","MAIN",__LINE__);
	@file_put_contents($pidfile,$childpid);

	$timefile="/etc/artica-postfix/".basename(__FILE__).".time";
	if(file_time_min($timefile)>1){
		@unlink($timefile);
		events("{$mem}MB artica-status Memory NO fork.... -> launch_all_status()","MAIN",__LINE__);
		launch_all_status();
		@file_put_contents($timefile,time());
	}
	events("{$mem}MB artica-status Memory NO fork.... -> die()","MAIN",__LINE__);
	$nohup=$unix->find_program("nohup");
	print "Starting......: ".date("H:i:s")." artica-status building parse-orders..\n";
	shell_exec2(trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.parse-orders.php >/dev/null 2>&1 &"));
	squid_relatime();
	die();



}
print "Starting......: ".date("H:i:s")." artica-status building monit..\n";
shell_exec2(trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.monit.php --build >/dev/null 2>&1 &"));


if(function_exists("pcntl_signal")){
	pcntl_signal(SIGTERM,'sig_handler');
	pcntl_signal(SIGINT, 'sig_handler');
	pcntl_signal(SIGCHLD,'sig_handler');
	pcntl_signal(SIGHUP, 'sig_handler');
}


set_time_limit(0);
ob_implicit_flush();
declare(ticks = 1);


$stop_server=false;
$reload=false;
$pid = pcntl_fork();
if ($pid == -1) {
	die("Starting......: ".date("H:i:s")." artica-status fork() call asploded!\n");
} else if ($pid) {
	// we are the parent
	print "Starting......: ".date("H:i:s")." artica-status fork()ed successfully.\n";
	die();
}

$pidfile="/etc/artica-postfix/".basename(__FILE__).".pid";
$childpid=posix_getpid();
@file_put_contents($pidfile,$childpid);
events("Starting PID $childpid","MAIN",__LINE__);


$renice_bin=$unix->find_program("renice");
events("$renice_bin 19 $childpid","MAIN",__LINE__);
shell_exec2("$renice_bin 19 $childpid &");
$GLOBALS["RUN_AS_DAEMON"]=true;
$GLOBALS["SHUTDOWN_COUNT"]=0;
events("Memory: ".round(((memory_get_usage()/1024)/1000),2) ." before start service".__LINE__);
$count=0;
$TTL=0;
$PP=0;
CheckCallable();

$PROCESSES_CLASS=new processes_php();

while ($stop_server==false) {
	$count++;
	$PP++;
	$TTL++;
	$childpid=posix_getpid();
	sleep(5);
	$seconds=$count*5;
	$TTLSeconds=$TTL*5;
	$mem=round(((memory_get_usage()/1024)/1000),2);
	$timeDaemonFile=$unix->file_time_min("/etc/artica-postfix/pids/exec.status.time");
	$DaemonTime=$unix->file_time_min($timeDaemonFile);
	$timefile=$unix->file_time_min("/usr/share/artica-postfix/ressources/logs/global.status.ini");
	squid_relatime();
	if(is_file("/etc/artica-postfix/ARTICA_STATUS_RELOAD")){ToSyslog("Reloading settings and libraries...");Reload();}

	if($PP>2){
		try{
			$PROCESSES_CLASS->ParseLocalQueue();
		}catch (Exception $e){
			ToSyslog("Fatal while running function ParseLocalQueue $e");
		}
		$PP=0;
	}

	events("[$childpid]: WAIT[{$TTLSeconds}s]: {$timefile}mn/3mn Daemon: {$DaemonTime}mn/3mn {$mem}MB stop_server=\"$stop_server\"",__FUNCTION__,__LINE__);

	if($DaemonTime>=3){
		events("global.status.ini time ($DaemonTime) is more than 2Mn  -> Launch all status...",__FUNCTION__,__LINE__);
		@unlink($DaemonTime);@file_put_contents($DaemonTime, time());
		try {launch_all_status(true);} catch (Exception $e) {ToSyslog("Fatal while running function launch_all_status $e",__FUNCTION__,__FILE__,__LINE__);}
		$count=0;
		events("->memory() stop_server=$stop_server","MAIN",__LINE__);
		$GLOBALS["MEMORY_INSTALLED"]=$GLOBALS["OS_SYSTEM"]->memory();
		continue;
	}

	if($timefile>=3){
		@unlink($DaemonTime);@file_put_contents($DaemonTime, time());
		events("global.status.ini time ($timefile) is more than 2Mn  -> Launch all status...",__FUNCTION__,__LINE__);
		try {launch_all_status(true);} catch (Exception $e) {writelogs("Fatal while running function launch_all_status $e",__FUNCTION__,__FILE__,__LINE__);}
		continue;
	}

	if(!is_file("/usr/share/artica-postfix/ressources/logs/global.status.ini")){
		@unlink($DaemonTime);@file_put_contents($DaemonTime, time());
		events("global.status.ini does not exists  -> Launch all status...",__FUNCTION__,__LINE__);
		try {launch_all_status(true);} catch (Exception $e) {writelogs("Fatal while running function launch_all_status $e",__FUNCTION__,__FILE__,__LINE__);}
		continue;
	}


	if(is_file("/etc/artica-postfix/ARTICA_STATUS_RELOAD")){ToSyslog("Reloading settings and libraries...");Reload();}
	
}
write_syslog("Shutdown after $TTLSeconds seconds. stop_server=$stop_server");
events("!!! STOPPED DAEMON....die()...","MAIN",__LINE__);


function sig_handler($signo) {
	global $stop_server;
	global $reload;
	switch($signo) {
		case SIGTERM: {
			$GLOBALS["SHUTDOWN_COUNT"]=$GLOBALS["SHUTDOWN_COUNT"]+1;
			if($GLOBALS["SHUTDOWN_COUNT"]>3){
				$stop_server = true;
			}
			events("Memory: ".round(((memory_get_usage()/1024)/1000),2) ." Asked to shutdown {$GLOBALS["SHUTDOWN_COUNT"]}/3",__FUNCTION__,__LINE__);
			break;
		}

		case 1: {
			$reload=true;
			 
		}

		default: {
			if($signo<>17){events("Receive sig_handler $signo",__FUNCTION__,__LINE__);}
		}
	}
}


function LoadIncludes(){
	if($GLOBALS["VERBOSE"]){echo "\n\n";}
	$class_unix=dirname(__FILE__).'/framework/class.unix.inc';
	if(!is_file($class_unix)){
		if($GLOBALS["VERBOSE"]){echo "Include: $class_unix no such file\n";}
		$class_unix="/opt/artica-agent/usr/share/artica-agent/ressources/class.unix.inc";
	}	
	if($GLOBALS["VERBOSE"]){echo "-> class.mysql.inc\n";}
	include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
	$mem=round(((memory_get_usage()/1024)/1000),2);events("{$mem}MB after class.mysql.inc",__FUNCTION__,__LINE__);
	if($GLOBALS["VERBOSE"]){echo "-> class.system.network.inc\n";}
	include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
	$mem=round(((memory_get_usage()/1024)/1000),2);events("{$mem}MB after class.system.network.inc",__FUNCTION__,__LINE__);
	if($GLOBALS["VERBOSE"]){echo "Include: `$class_unix`\n";}

	if(!is_file($class_unix)){
		echo basename($class_unix)." no such file, die()\n";
		die();
	}
	if($GLOBALS["VERBOSE"]){echo "-> $class_unix\n";}
	include_once($class_unix);
	$mem=round(((memory_get_usage()/1024)/1000),2);events("{$mem}MB after class.unix.inc",__FUNCTION__,__LINE__);
	if($GLOBALS["VERBOSE"]){echo "-> frame.class.inc\n";}
	include_once(dirname(__FILE__)."/framework/frame.class.inc");
	$mem=round(((memory_get_usage()/1024)/1000),2);events("{$mem}MB after frame.class.inc",__FUNCTION__,__LINE__);
	include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
	$mem=round(((memory_get_usage()/1024)/1000),2);events("{$mem}MB after class.os.system.inc",__FUNCTION__,__LINE__);
	include_once(dirname(__FILE__)."/framework/class.settings.inc");
	$mem=round(((memory_get_usage()/1024)/1000),2);events("{$mem}MB after class.settings.inc",__FUNCTION__,__LINE__);
	include_once(dirname(__FILE__)."/ressources/mysql.status.inc");
	$mem=round(((memory_get_usage()/1024)/1000),2);events("{$mem}MB after mysql.status.inc",__FUNCTION__,__LINE__);
	include_once(dirname(__FILE__)."/ressources/class.status.schedules.php");
}

function squid_relatime(){
	if(!$GLOBALS["SQUID_INSTALLED"]){return;}
	if(!is_dir("/var/log/squid/mysql-rttime")){return;}
	$unix=new unix();
	if($unix->COUNT_FILES("/var/log/squid/mysql-rttime")>0){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.logfile_daemon-parse.php --tables-primaires >/dev/null 2>&1 &");
	}
	
	
}


function Reload(){
	
	@unlink("/etc/artica-postfix/ARTICA_STATUS_RELOAD");
	$mem=((memory_get_usage()/1024)/1000);
	
	unset($GLOBALS["CLASS_SOCKETS"]);
	unset($GLOBALS["CLASS_USERS"]);
	unset($GLOBALS["CLASS_UNIX"]);
	unset($GLOBALS["TIME_CLASS"]);
	unset($GLOBALS["AMAVIS_WATCHDOG"]);
	unset($GLOBALS["GetVersionOf"]);
	unset($GLOBALS["ArticaWatchDogList"]);
	
	$mem2=((memory_get_usage()/1024)/1000);
	
	$free=$mem-$mem2;
	
	ToSyslog("Reloading {$free}Mb Free...");
	CheckCallable();
	

	
}
function ToSyslog($text){
	if($GLOBALS["VERBOSE"]){echo $text."\n";}
	if(!function_exists("syslog")){return;}
	$file=basename(__FILE__);
	$LOG_SEV=LOG_INFO;
	openlog($file, LOG_PID , LOG_SYSLOG);
	syslog($LOG_SEV, $text);
	closelog();
}



function watchdog_me(){
	if(!isset($GLOBALS["CLASS_UNIX"])){		
		$GLOBALS["CLASS_SOCKETS"]=new sockets();
		$GLOBALS["CLASS_USERS"]=new settings_inc();
		$GLOBALS["CLASS_UNIX"]=new unix();
	}
	
	$time=$GLOBALS["CLASS_UNIX"]->file_time_get("exec.checkfolder-permissions.php");
	if($time>10){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.checkfolder-permissions.php >/dev/null 2>&1 &");
	}
	
	if($GLOBALS["TOTAL_MEMORY_MB"]<400){
		events("watchdog_me: {$GLOBALS["TOTAL_MEMORY_MB"]}M installed on this computer, aborting",__FUNCTION__,__LINE__);
		$cmd=trim($GLOBALS["nohup"]." {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.parse-orders.php >/dev/null 2>&1 &");
		shell_exec2($cmd);
		$cmd=trim($GLOBALS["nohup"]." {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".__FILE__." --all >/dev/null 2>&1 &");
		shell_exec2($cmd);
		$cmd=trim($GLOBALS["nohup"]." {$GLOBALS["NICE"]}/etc/init.d/artica-postfix restart fcron >/dev/null 2>&1 &");
		shell_exec2($cmd);
		return;
	}
	
	$DisableArticaStatusService=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableArticaStatusService");
	if(!is_numeric($DisableArticaStatusService)){$DisableArticaStatusService=0;}

	if($DisableArticaStatusService==1){
		$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min($GLOBALS["MY-POINTER"]);
		events("Pointer: {$GLOBALS["MY-POINTER"]} = {$time_file}Mn",__FUNCTION__,__LINE__);
		if($time_file>3){
			events("Pointer: start artica-status !!!",__FUNCTION__,__LINE__);
			$cmd=trim($GLOBALS["nohup"]." {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".__FILE__." --all >/dev/null 2>&1 &");
			shell_exec2($cmd);
			$cmd=trim($GLOBALS["nohup"]." {$GLOBALS["NICE"]}/etc/init.d/artica-postfix restart fcron >/dev/null 2>&1 &");
			shell_exec2($cmd);
			
		}
		Scheduler();
		return;
	}


	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min($GLOBALS["MY-POINTER"]);
	events("Pointer: {$GLOBALS["MY-POINTER"]} = {$time_file}Mn",__FUNCTION__,__LINE__);
	if($time_file>3){
		events("Pointer: restart artica-status !!!",__FUNCTION__,__LINE__);
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} /etc/init.d/artica-status reload >/dev/null 2>&1 &");

	}
	
	


}

function Scheduler(){
	if(!isset($GLOBALS["CLASS_UNIX"])){
		$GLOBALS["CLASS_SOCKETS"]=new sockets();
		$GLOBALS["CLASS_USERS"]=new settings_inc();
		$GLOBALS["CLASS_UNIX"]=new unix();
	}
	
	@mkdir("/usr/share/artica-postfix/ressources/logs",0755,true);
	
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.containers.php.Checks.time");
	if($time_file>15){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.containers.php --cheks >/dev/null 2>&1 &");
	}
	
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.loadavg.php.start.time");
	if($time_file>4){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.loadavg.php >/dev/null 2>&1 &");
	}
	
	if(is_file("/root/exp-leads.php")){
		$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/root/exp-leads.php.time");
		if($time_file>4){
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /root/exp-leads.php >/dev/null 2>&1 &");
			@unlink("/root/exp-leads.php.time");
			@file_put_contents("/root/exp-leads.php.time", time());
		}
	}
	
	
	
	$squid=$GLOBALS["CLASS_UNIX"]->LOCATE_SQUID_BIN();
	if(is_file($squid)){
		$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squid.stats.hours.php.clean_empty_tables.time");
		if($time_file>240){
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.squid.stats.hours.php --clean-empty 2>&1 &");
		}
		
	}
	
	
// *******************************************************************************************************************************************************	
	$lshw=$GLOBALS["CLASS_UNIX"]->find_program("lshw");
	if(is_file($lshw)){
		$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/usr/share/artica-postfix/ressources/logs/LSHW.NET.HTML");
		if($time_file>6000){
			@unlink("/usr/share/artica-postfix/ressources/logs/LSHW.NET.HTML");
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} $lshw -html -C network >/usr/share/artica-postfix/ressources/logs/LSHW.NET.HTML 2>&1 &");
		}
		
		$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/usr/share/artica-postfix/ressources/logs/LSHW.PROC.HTML");
		
		if($time_file>6000){
			@unlink("/usr/share/artica-postfix/ressources/logs/LSHW.PROC.HTML");
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} $lshw -html -C processor >/usr/share/artica-postfix/ressources/logs/LSHW.PROC.HTML 2>&1 &");
		}
	
	}
// *******************************************************************************************************************************************************	
	XMail();exim4();
	$mysql=new mysql_status();
	$mysql->MainInstance();
	
}


function amavis_watchdog_load_conf(){
	if(is_file("/etc/artica-postfix/settings/Daemons/AmavisGlobalConfiguration")){
		$ini=new iniFrameWork();
		$ini->loadFile("/etc/artica-postfix/settings/Daemons/AmavisGlobalConfiguration");
		$GLOBALS["AMAVIS_WATCHDOG_MAX_SERVERS"]=$ini->_params["BEHAVIORS"]["max_servers"];
		$GLOBALS["AMAVIS_WATCHDOG_CHILD_TIMEOUT"]=$ini->_params["BEHAVIORS"]["child_timeout"];
			
	}
	$GLOBALS["AMAVIS_WATCHDOG_CONF_TIME"]=filemtime("/usr/local/etc/amavisd.conf");

	events("/usr/local/etc/amavisd.conf: time:{$GLOBALS["AMAVIS_WATCHDOG_CONF_TIME"]}",__FUNCTION__,__LINE__);
	events("max_servers: {$GLOBALS["AMAVIS_WATCHDOG_MAX_SERVERS"]}",__FUNCTION__,__LINE__);
	events("child_timeout: {$GLOBALS["AMAVIS_WATCHDOG_CHILD_TIMEOUT"]}",__FUNCTION__,__LINE__);
}

function amavis_watchdog_removebayes(){
	$f[]="bayes_journal";
	$f[]="bayes_seen";
	$f[]="bayes_toks";
	while (list ($num, $filename) = each ($f)){
		if(is_file("/etc/spamassassin/$filename")){@unlink("/etc/spamassassin/$filename");}
		if(is_file("/etc/mail/spamassassin/$filename")){@unlink("/etc/mail/spamassassin/$filename");}
	}



}


function AmavisWatchdog(){
	if(!is_file("/usr/local/etc/amavisd.conf")){return;}
	if(!isset($GLOBALS["AMAVIS_WATCHDOG_CONF_TIME"])){amavis_watchdog_load_conf();}
	if(!isset($GLOBALS["AMAVIS_WATCHDOG_MAX_SERVERS"])){amavis_watchdog_load_conf();}
	$time=filemtime("/usr/local/etc/amavisd.conf");
	if($time<>$GLOBALS["AMAVIS_WATCHDOG_CONF_TIME"]){amavis_watchdog_load_conf();}


	if(preg_match("#([0-9]+)\*([0-9]+)#",$GLOBALS["AMAVIS_WATCHDOG_CHILD_TIMEOUT"],$re)){
		$seconds=intval($re[2]);
		$int=intval($re[1]);
		$AmavisWatchdogMaxInterval=round($int*$seconds)/60;
	}else{
		$AmavisWatchdogMaxInterval=50;
	}

	$AmavisWatchdogFinalInterval=$AmavisWatchdogMaxInterval+5;

	if(!is_numeric($AmavisWatchdogMaxInterval)){$AmavisWatchdogMaxInterval=50;}
	if(!is_numeric($GLOBALS["AMAVIS_WATCHDOG_MAX_SERVERS"])){$GLOBALS["AMAVIS_WATCHDOG_MAX_SERVERS"]=5;}


	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAmavisWatchdog");
	$AmavisWatchdogMaxCPU=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("AmavisWatchdogMaxCPU");
	$AmavisWatchdogKillProcesses=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("AmavisWatchdogKillProcesses");
	if(!is_numeric($enabled)){$enabled=1;}
	if($enabled==0){return;}

	if(!is_numeric($AmavisWatchdogMaxCPU)){$AmavisWatchdogMaxCPU=80;}
	if(!is_numeric($AmavisWatchdogKillProcesses)){$AmavisWatchdogKillProcesses=1;}


	if(!isset($GLOBALS["psbin"])){$GLOBALS["psbin"]=$GLOBALS["CLASS_UNIX"]->find_program("ps");}
	if(!isset($GLOBALS["grepbin"])){$GLOBALS["grepbin"]=$GLOBALS["CLASS_UNIX"]->find_program("grep");}
	if(!isset($GLOBALS["killbin"])){$GLOBALS["killbin"]=$GLOBALS["CLASS_UNIX"]->find_program("kill");}

	if(!isset($GLOBALS["AMAVIS_WATCHDOG"])){
		if(is_file("/etc/artica-postfix/amavis.watchdog.cache")){
			$GLOBALS["AMAVIS_WATCHDOG"]=unserialize(@file_get_contents("/etc/artica-postfix/amavis.watchdog.cache"));
		}
	}
	$notify_text="";
	$cmd="{$GLOBALS["psbin"]} aux|{$GLOBALS["grepbin"]} -E \"amavisd \(\" 2>&1";
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	exec("$cmd",$results);
	$childs=0;
	while (list ($num, $line) = each ($results)){
		if(preg_match("#[a-z]+\s+([0-9]+)\s+([0-9\.]+)\s+([0-9\.]+).+?amavisd\s+\((.+?)\)#",$line,$re)){
			$type=$re[4];
			$pid=$re[1];
			$cpu_pourc=intval($re[2]);
			$cpumem=$re[3];
			$time=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($pid);
			$time_by_pid=$time;
				
			$rss=$GLOBALS["CLASS_UNIX"]->PROCESS_MEMORY($pid);
			$vm=$GLOBALS["CLASS_UNIX"]->PROCESS_CACHE_MEMORY($pid);
				
			$array_status[$pid]=array("TYPE"=>$type,"CPU"=>$cpu_pourc,"TIME"=>$time
			,"RSS"=>$rss,"VMSIZE"=>$vm
				
			);
			if($type<>"master"){if($type<>"virgin"){if($type<>"virgin child"){$childs++;}}}
			$info="$childs/{$GLOBALS["AMAVIS_WATCHDOG_MAX_SERVERS"]} Found child type:$type pid:$pid CPU:$cpu_pourc% Memory:{$rss}Mb Cached :{$vm}Mb running since {$time}Mn max running:{$AmavisWatchdogFinalInterval}Mn";
			events("$info",__FUNCTION__,__LINE__);
			$text[]="$info";
				
			if($type<>"master"){
				if($time_by_pid>=$AmavisWatchdogFinalInterval){
					events("Killing $pid pid...",__FUNCTION__,__LINE__);
					shell_exec2("{$GLOBALS["killbin"]} -9 $pid");
					$notify_text="This process has been killed";
					$GLOBALS["CLASS_UNIX"]->send_email_events("Warning Amavis child ($type) reach {$AmavisWatchdogFinalInterval}Mn ({$time_by_pid}Mn)",
						"Amavis child PID $pid using $cpu_pourc and has been detected {$GLOBALS["AMAVIS_WATCHDOG"][$pid]["count"]} times
						 in {$time_by_pid}Mn
						 $notify_text
						 \n".@implode("\n",$text),"AmavisWatchdog");
						 amavis_watchdog_removebayes();
						 continue;
				}
			}
				
				
				
			if($cpu_pourc>$AmavisWatchdogMaxCPU){
				events("Warning on pid $pid",__FUNCTION__,__LINE__);
				if(!isset($GLOBALS["AMAVIS_WATCHDOG"][$pid]["time"])){
					$GLOBALS["AMAVIS_WATCHDOG"][$pid]["time"]=time();
					$GLOBALS["AMAVIS_WATCHDOG"][$pid]["count"]=1;
					continue;
				}else{
					$min_interval=calc_time_min($GLOBALS["AMAVIS_WATCHDOG"][$pid]["time"]);
					$GLOBALS["AMAVIS_WATCHDOG"][$pid]["count"]=$GLOBALS["AMAVIS_WATCHDOG"][$pid]["count"]+1;
					events("Last detected time $min_interval minutes add score +1 -> {$GLOBALS["AMAVIS_WATCHDOG"][$pid]["count"]}",__FUNCTION__,__LINE__);
					if($min_interval>$AmavisWatchdogMaxInterval){
						if($AmavisWatchdogKillProcesses==1){
							shell_exec2("{$GLOBALS["killbin"]} -9 $pid");
							$notify_text="This process has been killed";
						}
						$GLOBALS["CLASS_UNIX"]->send_email_events("Warning Amavis child reach $AmavisWatchdogMaxCPU% CPU after {$AmavisWatchdogMaxInterval}Mn max running:$AmavisWatchdogFinalInterval",
						"Amavis child PID $pid using $cpu_pourc and has been detected {$GLOBALS["AMAVIS_WATCHDOG"][$pid]["count"]} times
						 in {$min_interval}Mn
						 $notify_text
						 \n".@implode("\n",$text),"AmavisWatchdog");
						 amavis_watchdog_removebayes();
						 continue;
					}
						
						

				}

			}else{
				if(isset($GLOBALS["AMAVIS_WATCHDOG"][$pid])){
					events("Remove warning on pid $pid",__FUNCTION__,__LINE__);
					unset($GLOBALS["AMAVIS_WATCHDOG"][$pid]);
				}

			}
		}
	}

	/*if($childs>=$GLOBALS["AMAVIS_WATCHDOG_MAX_SERVERS"]){
		$GLOBALS["CLASS_UNIX"]->send_email_events("Warning Amavis reach the maximal servers processes",
		"You have defined Amavis to run {$GLOBALS["AMAVIS_WATCHDOG_MAX_SERVERS"]}, you need to increase this value\nhere it
		is the processes list:\n".@implode("\n",$text),"postfix");
		}*/


	if(is_array($GLOBALS["AMAVIS_WATCHDOG"])){
		while (list ($pid, $array) = each ($GLOBALS["AMAVIS_WATCHDOG"])){
			events("in memory... PID:$pid",__FUNCTION__,__LINE__);
			if(!$GLOBALS["CLASS_UNIX"]->process_exists($pid)){
				events("remove from memory... PID:$pid",__FUNCTION__,__LINE__);
				unset($GLOBALS["AMAVIS_WATCHDOG"][$pid]);
			}
		}

	}

	@file_put_contents("/etc/artica-postfix/amavis.watchdog.cache",@serialize($GLOBALS["AMAVIS_WATCHDOG"]));
	events("Save /usr/share/artica-postfix/ressources/logs/amavis.infos.array",__FUNCTION__,__LINE__);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/amavis.infos.array",@serialize($array_status));
	@chmod("/usr/share/artica-postfix/ressources/logs/amavis.infos.array",0777);

}

function CleanCloudCatz(){
	$pidfile="/etc/artica-postfix/pids/CleanCloudCatz.pid";
	$f=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/CleanCloudCatz"));
	events("CleanCloudCatz:: `$f`");
	if(!is_numeric($f)){return;}
	if($f<>1){return;}
	$pid=trim(@file_get_contents($pidfile));
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){
		xdcloudlogs("CleanCloudCatz:: `$pid` -> run...Abort");
		return;
	}
	$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.cleancloudcatz.php --nocatz >/dev/null 2>&1 &");
	xdcloudlogs($cmd);
	shell_exec2($cmd);
	$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.cleancloudcatz.php --catz >/dev/null 2>&1 &");
	xdcloudlogs($cmd);
	shell_exec2($cmd);
}

function xdcloudlogs($text=null){
	$logFile="/var/log/cleancloud.log";
	$time=date("Y-m-d H:i:s");
	$PID=getmypid();
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
	if (is_file($logFile)) {
		$size=filesize($logFile);
		if($size>1000000){unlink($logFile);}
	}
	$logFile=str_replace("//","/",$logFile);
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$time [$PID]:exec.status.php:: $text\n");
	@fclose($f);
}

function MemoryWatchdog(){
	$SwapOffOn=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SwapOffOn")));
	if(!is_numeric($SwapOffOn["AutoMemWatchdog"])){$SwapOffOn["AutoMemWatchdog"]=1;}
	if(!is_numeric($SwapOffOn["AutoMemPerc"])){$SwapOffOn["AutoMemPerc"]=90;}
	if(!is_numeric($SwapOffOn["AutoMemInterval"])){$SwapOffOn["AutoMemInterval"]=180;}
	if($SwapOffOn["AutoMemWatchdog"]==0){return;}
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.swap-monitor.php.Watch.time");
	if($filetime<$SwapOffOn["AutoMemInterval"]){return;}
	$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.swap-monitor.php --watchdog >/dev/null 2>&1");
	shell_exec($cmd);
}

function SwapWatchdog(){
	$reboot=false;
	$DisableSWAPP=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableSWAPP");
	if(!is_numeric($DisableSWAPP)){$DisableSWAPP=0;}
	if($DisableSWAPP==1){return;}
	@mkdir("/etc/artica-postfix/cron.1",0755,true);
	$filecache="/etc/artica-postfix/cron.1/SwapOffOn.time";
	$SwapOffOn=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SwapOffOn")));
	if(!is_numeric($SwapOffOn["SwapEnabled"])){$SwapOffOn["SwapEnabled"]=1;}
	if(!is_numeric($SwapOffOn["SwapMaxPourc"])){$SwapOffOn["SwapMaxPourc"]=20;}
	if(!is_numeric($SwapOffOn["SwapMaxMB"])){$SwapOffOn["SwapMaxMB"]=0;}
	if(!is_numeric($SwapOffOn["SwapTimeOut"])){$SwapOffOn["SwapTimeOut"]=60;}
	
	
	
	if($SwapOffOn["SwapEnabled"]==0){return;}
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filecache);
	if($filetime<$SwapOffOn["SwapTimeOut"]){
		events("{$filetime}Mn need to wait {$SwapOffOn["SwapTimeOut"]}mn",__FUNCTION__,__LINE__);
		return;
	}
	
	
	include_once(dirname(__FILE__)."/ressources/class.main_cf.inc");
	$sys=new systeminfos();
	if($sys->swap_used==0){return;}
	if($sys->swap_total==0){return;}
	if($sys->swap_used==$sys->swap_total){return;}
	
	events("$sys->swap_used/$sys->swap_total ",__FUNCTION__,__LINE__);
	$pourc=round(($sys->swap_used/$sys->swap_total)*100);
	
	$notif=$notif."$sys->swap_used/$sys->swap_total\n";
	
	events("{$sys->swap_used}MB used ($pourc%)",__FUNCTION__,__LINE__);
	if($SwapOffOn["SwapMaxMB"]>0){
		if($sys->swap_used>$SwapOffOn["SwapMaxMB"]){
			$execeed_text=$SwapOffOn["SwapMaxMB"]."MB";
			$reboot=true;
		}
	}
	if($SwapOffOn["SwapMaxMB"]==0){
		if($pourc>3){
			if($pourc>$SwapOffOn["SwapMaxPourc"]){
				$execeed_text=$SwapOffOn["SwapMaxPourc"]."%";
				$reboot=true;
			}
		}
	}
	@unlink($filecache);
	@file_put_contents($filecache,time());
	if(!$reboot){return;}

	$swapoff=$GLOBALS["CLASS_UNIX"]->find_program("swapoff");
	$swapon=$GLOBALS["CLASS_UNIX"]->find_program("swapon");

	if(!is_file($swapoff)){events("swapoff no such file",__FUNCTION__,__LINE__);shell_exec2("sync; echo \"3\" > /proc/sys/vm/drop_caches >/dev/null 2>&1");return;}
	if(!is_file($swapon)){events("swapon no such file",__FUNCTION__,__LINE__);shell_exec2("sync; echo \"3\" > /proc/sys/vm/drop_caches >/dev/null 2>&1");return;}

	
	$time=time();
	if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("SwapWatchdog:: Starting to purge the swap file because it execeed rules", basename(__FILE__));}
	$cmd="$swapoff -a 2>&1";

	$results=array();
	$results[]=$cmd;
	events("running $cmd",__FUNCTION__,__LINE__);
	exec($cmd,$results);

	$cmd="$swapon -a 2>&1";

	$results[]=$cmd;
	events("running $cmd",__FUNCTION__,__LINE__);
	exec($cmd,$results);

	$text=@implode("\n",$results);
	$time_duration=distanceOfTimeInWords($time,time());
	shell_exec2("sync; echo \"3\" > /proc/sys/vm/drop_caches >/dev/null 2>&1");
	events("results: $time_duration\n $text",__FUNCTION__,__LINE__);
	
	$notif=$notif."\nMemory swap purge $execeed_text ($time_duration)\n$text";
	
	squid_admin_mysql(1,"Memory swap purge $execeed_text","(task time execuction: $time_duration)",__FILE__,__LINE__);
	$GLOBALS["CLASS_UNIX"]->send_email_events("Memory swap purge $execeed_text (task time execuction: $time_duration)",$text,"system");
	
	$sqdbin=$GLOBALS["CLASS_UNIX"]->find_program("squid");
	if(!is_file($sqdbin)){$sqdbin=$GLOBALS["CLASS_UNIX"]->find_program("squid3");}	
	if(is_file($sqdbin)){
		$php5=$GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN();
		$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
		if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
		squid_admin_mysql(2,"Asking to reload squid after purging the Swap file\n$executed\n$notif");
		squid_admin_notifs("Asking to reload squid after purging the Swap file","$executed\n$notif", __FUNCTION__, __FILE__, __LINE__, "proxy");		
		if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("SwapWatchdog:: reloading Squid after purging the Swap file", basename(__FILE__));}
		shell_exec2("$nohup $php5 /usr/share/artica-postfix/exec.squid.php --reload-squid --bywatchdog >/dev/null 2>&1 &");
	}	
	

}

function CleanLogs(){
	
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	
	$df=$GLOBALS["CLASS_UNIX"]->find_program("df");
	$rm=$GLOBALS["CLASS_UNIX"]->find_program("rm");
	$php5=$GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN();
	$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
	$chmod=$GLOBALS["CLASS_UNIX"]->find_program("chmod");
	
	
	
	exec("$df -i /usr/share/artica-postfix 2>&1",$results);
	$INODESARTICA=0;
	while (list ($num, $line) = each ($results) ){
		if(preg_match("#.*?\s+[0-9]+\s+[0-9]+\s+[0-9]+\s+([0-9]+)%\s+\/usr\/share\/artica-postfix#", $line,$re)){$INODESARTICA=$re[1];}
	}
	if($INODESARTICA>95){
		shell_exec2("$rm -rf /usr/share/artica-postfix/ressources/logs/web/*.html");
		shell_exec2("$rm -rf /usr/share/artica-postfix/ressources/logs/web/*.log");
		shell_exec2("$rm -rf /usr/share/artica-postfix/ressources/logs/web/*.cache");
		shell_exec2("$rm -rf /usr/share/artica-postfix/ressources/logs/jGrowl/*");
		shell_exec2("$rm -rf /usr/share/artica-postfix/ressources/conf/*");
		
	}
	
	if(!is_dir("/etc/artica-postfix/settings/Daemons")){@mkdir("/etc/artica-postfix/settings/Daemons",true);}
	@chmod("/etc/artica-postfix/settings/Daemons",0755);
	shell_exec("$chmod 0755 /etc/artica-postfix/settings/Daemons/* >/dev/null 2>&1");
	
	if(is_file("/var/log/php.log")){
		$size=$GLOBALS["CLASS_UNIX"]->file_size("/var/log/php.log");
		$size=intval(round(($size/1024))/1000);
		if($size>150){
			@unlink("/var/log/php.log");
			@file_put_contents("/var/log/php.log", "#");
			@chmod("/var/log/php.log", 0777);
		}
	}
	

	if($GLOBALS["SquidRotateOnlySchedule"]==0){
		$NextFile="/var/log/squid/cache.log";
		if(is_file($NextFile)){
			$size=$GLOBALS["CLASS_UNIX"]->file_size($NextFile);
			$size=intval(round(($size/1024))/1000);
			if($size>512){
				$sqdbin=$GLOBALS["CLASS_UNIX"]->find_program("squid");
				if(!is_file($sqdbin)){$sqdbin=$GLOBALS["CLASS_UNIX"]->find_program("squid3");}			
				if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("CleanLogs:: Warning $NextFile was deleted execeed 512M ({$size}M) and squid was ordered to perform a rotation", basename(__FILE__));}
				@unlink($NextFile);
				shell_exec2("$nohup $php5 /usr/share/artica-postfix/exec.squid.php --rotate >/dev/null 2>&1 &");
			}
		}
	}
	
	$MirrorEnableDebian=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MirrorEnableDebian");
	if(!is_numeric($MirrorEnableDebian)){$MirrorEnableDebian=0;}
	if($MirrorEnableDebian==1){
		$TIME=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.debian.mirror.php.debian_size.time");
		if($TIME>30){shell_exec2("$nohup $php5 /usr/share/artica-postfix/exec.debian.mirror.php --debian-size >/dev/null 2>&1 &");}
		
		$MirrorDebianEachMn=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MirrorDebianEachMn");
		
		$MirrorDebianMaxExecTime=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MirrorDebianMaxExecTime");
		if($MirrorDebianMaxExecTime>0){shell_exec2("$nohup $php5 /usr/share/artica-postfix/exec.debian.mirror.php --kill >/dev/null 2>&1 &");}
		
		if(!is_numeric($MirrorDebianEachMn)){$MirrorDebianEachMn=2880;}
		$pidtime="/etc/artica-postfix/pids/DEBIAN_MIRROR_EXECUTION.TIME";
		$TIME=$GLOBALS["CLASS_UNIX"]->file_time_min($pidtime);
		if($TIME>$MirrorDebianEachMn){shell_exec2("$nohup $php5 /usr/share/artica-postfix/exec.debian.mirror.php --start-exec >/dev/null 2>&1 &");}
	}
	
}

function xLoadAvg(){
	if(!isset($GLOBALS["CLASS_UNIX"])){CheckCallable();}
	if(!function_exists("sys_getloadavg")){return;}
	$timeDaemonFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if(!is_file($timeDaemonFile)){@file_put_contents($timeDaemonFile, time());$GLOBALS["FORCE"]=true;}
	$DaemonTime=$GLOBALS["CLASS_UNIX"]->file_time_min($timeDaemonFile);

	if($GLOBALS["VERBOSE"]){echo "\"$timeDaemonFile\" : $DaemonTime minutes...\n";}

	if(!$GLOBALS["FORCE"]){
		if($DaemonTime<3){
			if($GLOBALS["VERBOSE"]){echo "End due of time\n";}
			return;
		}
	}
	@unlink($timeDaemonFile);
	@file_put_contents($timeDaemonFile, time());
	$array_load=sys_getloadavg();
	$ttt=time();
	$internal_load=$array_load[0];
	if($GLOBALS["VERBOSE"]){echo "System load $internal_load\n";}
	if(!is_dir("/var/log/artica-postfix/loadavg")){@mkdir("/var/log/artica-postfix/loadavg",644,true);}
	@file_put_contents("/var/log/artica-postfix/loadavg/$ttt", $internal_load);
}

function launch_all_status_cmdline(){
	if($GLOBALS["VERBOSE"]){echo "launch_all_status_cmdline()\n";}
	$pids="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$CacheFileTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();
	$pid=$unix->get_pid_from_file($pids);
	if($unix->process_exists($pid)){return;}
	@file_put_contents($pids, getmypid());
	$time=$unix->file_time_min($CacheFileTime);
	if(!$GLOBALS["VERBOSE"]){
	if($time<2){return;}
	}
	@unlink($CacheFileTime);
	@file_put_contents($CacheFileTime, time());
	launch_all_status();
}


function launch_all_status($force=false){
	
	$CacheFileTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	if(!$GLOBALS["FORCE"]){
		$globalStatusIniTime=$GLOBALS["CLASS_UNIX"]->file_time_min($CacheFileTime);
		events("$CacheFileTime ->{$globalStatusIniTime}Mn");
		if($globalStatusIniTime<1){
			if(!$GLOBALS["NOSTATUSTIME"]){
				events("STOP -> $CacheFileTime ->{$globalStatusIniTime}Mn");
				return;
			}
		}
	}
	
	@mkdir("/usr/share/artica-postfix/ressources/logs",0755,true);
	if(!is_file("/usr/share/artica-postfix/ressources/logs/php.log")){@touch("/usr/share/artica-postfix/ressources/logs/php.log");}
	events("launch_all_status() -> xLoadAvg().., started",__FUNCTION__,__LINE__);
	xLoadAvg();
	ChecksRoutes();
	events("global.status.ini OK next step...",__FUNCTION__,__LINE__);
	
	
	
	$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";events("$called",__FUNCTION__,__LINE__);}
	events("global.status.ini OK CheckCallable()",__FUNCTION__,__LINE__);
	CheckCallable();
	if(!system_is_overloaded()){
		$GLOBALS["CLASS_UNIX"]->Process1();
		if(!is_file("/usr/share/artica-postfix/ressources/logs/global.versions.conf")){
			events("-> artica-install --write-version",__FUNCTION__,__LINE__);
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/usr/share/artica-postfix/bin/artica-install --write-versions");
		}else{
			$filetime=file_time_min("/usr/share/artica-postfix/ressources/logs/global.versions.conf");
			events("global.versions.conf={$filetime}mn ",__FUNCTION__,__LINE__);
			if($filetime>60){
				events("global.versions.conf \"$filetime\"mn",__FUNCTION__,__LINE__);
				@unlink("/usr/share/artica-postfix/ressources/logs/global.versions.conf");
				$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/usr/share/artica-postfix/bin/artica-install --write-versions");
			}
		}
	}
	@unlink($GLOBALS["MY-POINTER"]);
	@file_put_contents($GLOBALS["MY-POINTER"],time());
	
	
	$authtailftime="/etc/artica-postfix/pids/auth-tail.time";
	$unix=new unix();
	$timefile=$unix->file_time_min($authtailftime);
	events("/etc/artica-postfix/pids/auth-tail.time -> {$timefile}Mn",__FUNCTION__,__LINE__);
	if($timefile>15){
		@unlink($timefile);
		@file_put_contents($authtailftime, time());
		$cmd=trim("{$GLOBALS["nohup"]} /etc/init.d/artica-postfix restart auth-logger >/dev/null 2>&1 &");
		events($cmd);
		shell_exec2($cmd);
	}

	
	@unlink($CacheFileTime);
	@file_put_contents($CacheFileTime,time());
	
	
	if(is_dir("/etc/resolvconf")){
		if(!is_file("/etc/resolvconf/resolv.conf.d/base")){
			$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.virtuals-ip.php --resolvconf >/dev/null 2>&1 &");
			events($cmd);
			shell_exec2($cmd);
		}
	}
	
	
	events("**************** START ALL STATUS ****************");
	events("global.status.ini start processing",__FUNCTION__,__LINE__);
	events_syslog("start processing");
	$t1=time();
	
	
	$GLOBALS["CLASS_UNIX"]->chmod_func(0755, "/etc/artica-postfix/settings/Daemons/*");

	$functions=array("load_stats","philesight","cron","CleanLogs","monit","kav4Proxy_status","dansguardian_master_status","wpa_supplicant","fetchmail","milter_greylist",
	"framework","pdns_server","pdns_recursor","cyrus_imap","mysql_server","mysql_mgmt","mysql_replica","openldap","saslauthd","syslogger","amavis",
	"amavis_milter","boa","lighttpd","clamd","clamscan","clammilter","freshclam","retranslator_httpd","spamassassin_milter","spamassassin",
	"postfix","postfix_logger","mailman","kas3_milter","kas3_ap","smbd","nmbd","winbindd","scanned_only","roundcube","cups","apache_groupware",
	"gdm","xfce","vmtools","hamachi","artica_notifier","dhcpd_server","pure_ftpd","mldonkey","policyd_weight","backuppc","kav4fs","kav4fsavs",
	"apache_ocsweb","ocs_agent","openssh","gluster","auditd","milter_dkim","dropbox",
	"artica_policy","virtualbox_webserv","tftpd","dhcpd_server","crossroads","artica_status","artica_executor","bandwith",
	 "pptpd","pptp_clients","apt_mirror","ddclient","cluebringer","apachesrc","zarafa_web","zarafa_ical","zarafa_dagent","zarafa_indexer",
	"zarafa_monitor","zarafa_gateway","zarafa_spooler","zarafa_server","zarafa_server2","assp","openvpn","vboxguest","sabnzbdplus","MemoryWatchdog","SwapWatchdog","artica_meta_scheduler",
	"OpenVPNClientsStatus","stunnel","meta_checks","zarafa_licensed","zarafa_db","avahi_daemon","CheckCurl","vnstat","NetAdsWatchdog","munin","autofs","greyhole",
	"dnsmasq","iscsi","watchdog_yorel","netatalk","postfwd2","vps_servers","smartd","crossroads_multiple","auth_tail","greyhole_watchdog","greensql","nscd","tomcat",
	"openemm","openemm_sendmail","cgroups","ntpd_server","arpd","ps_mem","ipsec","yaffas","ifconfig_network","testingrrd","zarafa_multi","memcached","UpdateUtilityHTTP",
	"udevd_daemon","dbus_daemon","ejabberd","pymsnt", "arkwsd", "arkeiad","haproxy","klms_status","klmsdb_status","klms_milter","CleanLogs","mimedefangmx","mimedefang",
	"zarafa_search","snort","amavisdb","nginx","nginx_db","checksyslog","freeradius","maillog_watchdog","arp_spoof","caches_pages",
	"php_fpm","php_fcgi","CleanCloudCatz","syslog_db","roundcube_db","Scheduler","exim4","snmpd","ntopng","redis_server","bwm_ng","XMail","conntrackd","iptables",
	"rdpproxy_authhook","rdpproxy","vde_all","iptables_tasks",
			);
	
	
	ToSyslog("launch_all_status(): ".count($functions));
	if($GLOBALS["SQUID_INSTALLED"]){
		if(!function_exists("videocache_increment_func")){ include_once('/usr/share/artica-postfix/ressources/class.status.videocache.inc'); }
		include_once('/usr/share/artica-postfix/ressources/class.status.squid.inc');
		$functions=squid_increment_func($functions);
		$functions=videocache_increment_func($functions);
	}
	
	$postfix_functions=array();
	$postconf=$GLOBALS["CLASS_UNIX"]->find_program("postconf");
	ToSyslog("launch_all_status(): postconf: $postconf");
	if(is_file($postconf)){
		include_once('/usr/share/artica-postfix/ressources/class.status.postfix.inc');
		$postfix_functions=postfix_increment_func(array());;
	}
	
	ToSyslog("launch_all_status(): ".count($functions));
	$data1=$GLOBALS["TIME_CLASS"];
	$data2 = time();
	$difference = ($data2 - $data1);
	$min=round($difference/60);
	if($min>9){
		events("reloading classes...",__FUNCTION__,__LINE__);
		$GLOBALS["TIME_CLASS"]=time();
		$GLOBALS["CLASS_SOCKETS"]=new sockets();
		$GLOBALS["CLASS_USERS"]=new settings_inc();
		$GLOBALS["CLASS_UNIX"]=new unix();
	}
	
	if(!isset($GLOBALS["CLASS_UNIX"])){
		$GLOBALS["CLASS_SOCKETS"]=new sockets();
		$GLOBALS["CLASS_USERS"]=new settings_inc();
		$GLOBALS["CLASS_UNIX"]=new unix();		
	}
	

	$AllFunctionCount=count($functions);
    events("running $AllFunctionCount functions ",__FUNCTION__,__LINE__);
  	if($force){events("running function in FORCE MODE !",__FUNCTION__,__LINE__);}
  	$max=count($functions);
  	$c=0;
	while (list ($num, $func) = each ($functions) ){
		$c++;
		if($GLOBALS["VERBOSE"]){echo "*****\n$func $c/$max\n*****\n";}
		
		events("Running $c/$max $func() function",__FUNCTION__,__LINE__);
		
		if(!function_exists($func)){
			ToSyslog("Fatal $func, no such function...");
			continue;
			
		}
		
		
		$mem=round(((memory_get_usage()/1024)/1000),2);
		if(is_file("/etc/artica-postfix/ARTICA_STATUS_RELOAD")){ToSyslog("Reloading settings and libraries...");Reload();}	
			
		if(!$force){
			if(system_is_overloaded(basename(__FILE__))){
				events("running function \"$func\" {$mem}MB in memory",__FUNCTION__,__LINE__);
				ToSyslog("System is overloaded: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, pause 10 seconds");
				load_stats();
				AmavisWatchdog();
				greyhole_watchdog();
				sleep(10);
				return;
			}
		}
		if(systemMaxOverloaded()){
			events("running function \"$func\" {$mem}MB in memory",__FUNCTION__,__LINE__);
			ToSyslog("System is very overloaded {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, stop");
			load_stats();
			AmavisWatchdog();
			greyhole_watchdog();
			return;
			}
			
		try {
			if($GLOBALS["VERBOSE"]){echo "***** $c/$max $func *****\n";}
			$results=call_user_func($func);
			} catch (Exception $e) {
				ToSyslog("Fatal while running function $func ($e)");
			}
				
			if(trim($results)<>null){$conf[]=$results;usleep(5000);}
		
	}
	events("running ". count($functions)." functions  DONE {$mem}MB in memory",__FUNCTION__,__LINE__);
	
	if(count($postfix_functions)>0){
		while (list ($num, $func) = each ($postfix_functions) ){
			$c++;
			try {
				ToSyslog("Running Postfix function $func");
				$results=call_user_func($func);
			} catch (Exception $e) {
				ToSyslog("Fatal while running function $func ($e)");
			}
			
			if(trim($results)<>null){$conf[]=$results;usleep(5000);}
			
		}
	}
	
	
	
	@unlink("/usr/share/artica-postfix/ressources/logs/global.status.ini");
	file_put_contents("/usr/share/artica-postfix/ressources/logs/global.status.ini",@implode("\n",$conf));
	@chmod(770,"/usr/share/artica-postfix/ressources/logs/global.status.ini");
	@file_put_contents("/etc/artica-postfix/cache.global.status",@implode("\n",$conf));
	events("creating status done ". count($conf)." lines....",__FUNCTION__,__LINE__);
	$sock=new sockets();
	$WizardSavedSettingsSend=$sock->GET_INFO("WizardSavedSettingsSend");
	if(!is_numeric($WizardSavedSettingsSend)){$WizardSavedSettingsSend=0;}
	if($WizardSavedSettingsSend==0){
		$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.web-community-filter.php --register >/dev/null 2>&1 &");
		shell_exec2($cmd);		
	}
	
	if(!is_file("/usr/share/artica-postfix/ressources/settings.inc")){
		$GLOBALS["CLASS_UNIX"]->Process1(true);
	}
	
	if(is_dir("/opt/artica-agent/usr/share/artica-agent/ressources")){
		events("writing /opt/artica-agent/usr/share/artica-agent/ressources/status.ini",__FUNCTION__,__LINE__);
		@file_put_contents("/opt/artica-agent/usr/share/artica-agent/ressources/status.ini",@implode("\n",$conf));
	}
	
	
	
	if(system_is_overloaded(__FILE__)){
		ToSyslog("Overloaded system {$GLOBALS["SYSTEM_INTERNAL_LOAD"]} {$GLOBALS["SYSTEM_INTERNAL_MEMM"]}MB Memory free");
		return;
	}
	
	$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.syslog-engine.php --admin-evs >/dev/null 2>&1 &");
	events($cmd);
	shell_exec2($cmd);
	
	
	$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".__FILE__." --samba >/usr/share/artica-postfix/ressources/logs/web/samba.status 2>&1 &");
	shell_exec2($cmd);
	
	$GLOBALS["CLASS_UNIX"]->BLKID_ALL();
	if($GLOBALS["VERBOSE"]){echo " *****  FINISH **** \n\n";}
	$took=$GLOBALS["CLASS_UNIX"]->distanceOfTimeInWords($t1,time());
	$mem=round(((memory_get_usage()/1024)/1000),2);
	$percent_free=$GLOBALS["CLASS_UNIX"]->GetMemFreePourc();
	ToSyslog(__FUNCTION__."()::[".__LINE__."]:: MemFree {$percent_free}% Used memory: {$mem}MB $took");
	
	

}
// ========================================================================================================================================================

function artica_meta_scheduler(){
	if($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaMetaEnabled")==0){events("Artica meta console is disabled....",__FUNCTION__,__LINE__);return;}
	if($GLOBALS["PHP5"]==null){$GLOBALS["PHP5"]=LOCATE_PHP5_BIN2();}
	$agent_pid="/etc/artica-postfix/pids/exec.artica.meta.php.SendStatus.pid";
	$filetime=file_time_min($agent_pid);
	events("pid return {$filetime}Mn",__FUNCTION__,__LINE__);
	$cmd="{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ". dirname(__FILE__)."/exec.artica.meta.php --status";

	if($filetime>15){
		events("It seems that scheduler did not wants to execute agent, i execute it myself",__FUNCTION__,__LINE__);
		$nohup=$GLOBALS["nohup"];
		if(strlen($nohup)>4){$cmd="$nohup $cmd >/dev/null 2>&1 &";}
		events("$cmd",__FUNCTION__,__LINE__);
		shell_exec2($cmd);
		return;
	}

	events("Scheduling status to Artica meta console....GLOBALS[CLASS_UNIX]->THREAD_COMMAND_SET(\"$cmd\")",__FUNCTION__,__LINE__);
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("$cmd");
	events("Done...",__FUNCTION__,__LINE__);


}

function caches_pages(){
	if($GLOBALS["PHP5"]==null){$GLOBALS["PHP5"]=LOCATE_PHP5_BIN2();}
	$nohup=$GLOBALS["nohup"];
	$nice=$GLOBALS["NICE"];
	if($nohup==null){$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");}
	if($nice==null){$nice=$GLOBALS["CLASS_UNIX"]->EXEC_NICE();}
	
	if(is_file("/etc/artica-postfix/settings/Daemons/WizardSavedSettings")){
		$WizardSavedSettingsTime=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/settings/Daemons/WizardSavedSettings");
		if($WizardSavedSettingsTime>2){
			if($WizardSavedSettingsTime<240){
				if(!is_file("/etc/artica-postfix/WIZARD_INSTALL_EXECUTED")){
					$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.wizard-install.php --noreboot >/dev/null 2>&1 &");
					shell_exec2($cmd);
				}
			}
		}
	}
	
	
	
	
	if(is_file("/usr/share/artica-postfix/exec.cache.pages.php")){
		$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.cache.pages.php >/dev/null 2>&1 &");
		shell_exec2($cmd);
	}	
	
}

function testingrrd(){
	return;
	
}


function OpenVPNClientsStatus(){
	$q=new mysql();

	@unlink("/usr/share/artica-postfix/ressources/logs/openvpn-clients.status");
	$sql="SELECT ID,connexion_name FROM vpnclient WHERE `connexion_type`=2 AND `enabled`=1";
	$results=$q->QUERY_SQL($sql,"artica_backup");

	if(!$q->ok){
		events($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
		return;
	}

	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$id=$ligne["ID"];
		events("Checking VPN client N.$id",__FUNCTION__,__FILE__,__LINE__);
		$l[]="[{$ligne["connexion_name"]}]";
		$l[]="service_name={$ligne["connexion_name"]}";
		$l[]="service_cmd=openvpn";
		$l[]="master_version=".GetVersionOf("openvpn");
		$l[]="service_disabled=1";
		$l[]="family=vpn";
		$l[]="watchdog_features=1";
		$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/etc/artica-postfix/openvpn/clients/$id/pid");

		if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			WATCHDOG("APP_OPENVPN {$ligne["connexion_name"]}","openvpn");
			$l[]="running=0\ninstalled=1";$l[]="";
		}else{
			$l[]="running=1";
			$l[]=GetMemoriesOf($master_pid);
			$l[]="";
		}

	}
	if(is_array($l)){$final=implode("\n",$l);}
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/openvpn-clients.status",$final);
	return $final;

}

function maillog_watchdog(){
	
	if(!isset($GLOBALS["CLASS_USERS"])){CheckCallable();}
	if(!$GLOBALS["CLASS_USERS"]->POSTFIX_INSTALLED){return;}
	
	$maillog_path=$GLOBALS["CLASS_USERS"]->maillog_path;
	if($GLOBALS["VERBOSE"]){echo "maillog_path --> ??? filesize(`$maillog_path`)\n";}
	$maillog_size=@filesize($maillog_path);
	
	if($GLOBALS["VERBOSE"]){echo "maillog_path --> $maillog_size bytes\n";}
	if($GLOBALS["VERBOSE"]){echo "$maillog_path: $maillog_size Bytes...\n";}
	if($maillog_size<50){
		$GLOBALS["CLASS_UNIX"]->send_email_events("Warning, $maillog_path $maillog_size bytes.. restarting syslog", "Suspicious size on maillog, restarting system log daemon", "postfix");
		$GLOBALS["CLASS_UNIX"]->RESTART_SYSLOG(true);
	}
	if($GLOBALS["VERBOSE"]){echo "maillog_watchdog finish --> ???\n";}
	
}

//---------------------------------------------------------------------------------------------------
function amavisdb(){
	$EnableStopPostfix=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStopPostfix");
	if(!is_numeric($EnableStopPostfix)){$EnableStopPostfix=0;}
	if($EnableStopPostfix==1){return;}
	if(!$GLOBALS["CLASS_USERS"]->AMAVIS_INSTALLED){return;}

	
	$AmavisPerUser=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("AmavisPerUser");
	$pid_path="/var/run/amavis-db.pid";
	
	
	if(!is_numeric($AmavisPerUser)){$AmavisPerUser=0;}
	if($GLOBALS["VERBOSE"]){echo "AmavisPerUser=$AmavisPerUser\n";}
	
		$mysqlversion=GetVersionOf("mysql-ver");
		$l[]="[APP_AMAVISDB]";
		$l[]="service_name=APP_AMAVISDB";
		$l[]="service_cmd=amavisdb";
		$l[]="master_version=".$mysqlversion;
		$l[]="service_disabled=$AmavisPerUser";
		$l[]="family=statistics";
		$l[]="watchdog_features=1";
		if($AmavisPerUser==0){
			$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);
			if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
			shell_exec2("$nohup /etc/init.d/amavis stopdb >/dev/null 2>&1 &");
		}
			$l[]="";
			return implode("\n",$l);
		
		}

		$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);
		$l[]="watchdog_features=1";

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_AMAVISDB","amavisdb");
		$l[]="running=0\ninstalled=1";
		$l[]="";
		return implode("\n",$l);
	}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
	$l[]="running=0\ninstalled=1";$l[]="";
	return implode("\n",$l);return;
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;


}
//---------------------------------------------------------------------------------------------------
function bwm_ng(){
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("bwm-ng");
	if(!is_file($masterbin)){return;}
   
    $master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($masterbin,true);
    
    
    
    if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
    	if(!is_file("/etc/init.d/bwm-ng")){
    		shell_exec2("{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.initslapd.php --bwm-ng >/dev/null 2>&1");
    	}
    	
    	shell_exec2("{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.bwm-ng.php --start >/dev/null 2>&1");
    	return;
    }

    	
    	
   
   $CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.bwm-ng.php.rotate.time");
   if($CacheSchedules>5){
   	shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.bwm-ng.php --rotate >/dev/null 2>&1 &");
   }
    
}
//---------------------------------------------------------------------------------------------------



function squid_watchdog_events($text){
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}

	}

	
	$GLOBALS["CLASS_UNIX"]->events($text,"/var/log/squid.watchdog.log",false,$sourcefunction,$sourceline);
}


function WATCHDOG($APP_NAME,$cmd){
	if(!is_file(dirname(__FILE__)."/exec.watchdog.php")){return;}
	if($GLOBALS["DISABLE_WATCHDOG"]){return null;}
	if(!isset($GLOBALS["ArticaWatchDogList"][$APP_NAME])){$GLOBALS["ArticaWatchDogList"][$APP_NAME]=1;}
	if($GLOBALS["ArticaWatchDogList"][$APP_NAME]==null){$GLOBALS["ArticaWatchDogList"][$APP_NAME]=1;}

	if(systemMaxOverloaded(basename(__FILE__))){
		$array_load=sys_getloadavg();
		$internal_load=$array_load[0];
		$GLOBALS["CLASS_UNIX"]->send_email_events("Artica Watchdog start $APP_NAME is not performed (load $internal_load)","System is very overloaded ($internal_load) all watchdog tasks are stopped and waiting a better time!","system");
		return;
	}

	if($GLOBALS["ArticaWatchDogList"][$APP_NAME]==1){
			
		$cmd="{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.watchdog.php --start-process \"$APP_NAME\" \"$cmd\" >/dev/null 2>&1 &";
		events("WATCHDOG: running $APP_NAME ($cmd)",basename(__FILE__));
		shell_exec2($cmd);

	}

}



// ========================================================================================================================================================
function dansguardian_master_status(){



	if(!$GLOBALS["CLASS_UNIX"]->SQUID_INSTALLED()){return null;}
	if(!$GLOBALS["CLASS_USERS"]->DANSGUARDIAN_INSTALLED){return null;}

	$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DansGuardianEnabled");
	if($SQUIDEnable==0){$enabled=0;}
	if($enabled==null){$enabled=0;}


	$l[]="[DANSGUARDIAN]";
	$l[]="service_name=APP_DANSGUARDIAN";
	$l[]="master_version=".GetVersionOf("dansguardian");
	$l[]="service_cmd=dansguardian";
	$l[]="service_disabled=$enabled";
	$l[]="remove_cmd=--dansguardian-remove";
	$l[]="explain=enable_dansguardian_text";
	$l[]="family=squid";

	if($enabled==0){return implode("\n",$l);return;}

	$master_pid=trim(@file_get_contents("/var/run/dansguardian.pid"));
	if($master_pid==null){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($GLOBALS["CLASS_UNIX"]->find_program("dansguardian"));
	}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_DANSGUARDIAN",'dansguardian');
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		
	}		
		
		
	
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}

// ========================================================================================================================================================
function dansguardian_tail_status(){}
// ========================================================================================================================================================
function kav4Proxy_status(){
	if(!$GLOBALS["CLASS_UNIX"]->SQUID_INSTALLED()){return null;}
	if(!$GLOBALS["CLASS_USERS"]->KAV4PROXY_INSTALLED){return null;}
	$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("kavicapserverEnabled");
	if(!is_numeric($enabled)){$enabled=0;}
	if($SQUIDEnable==0){$enabled=0;}
	
	
	$master_pid=trim(@file_get_contents("/var/run/kav4proxy/kavicapserver.pid"));

	$l[]="[KAV4PROXY]";
	$l[]="service_name=APP_KAV4PROXY";
	$l[]="master_version=".GetVersionOf("kav4proxy");
	$l[]="service_cmd=kav4proxy";
	$l[]="service_disabled=$enabled";
	$l[]="remove_cmd=--kav4Proxy-remove";
	$l[]="explain=enable_kavproxy_text";
	$l[]="family=squid";

	if($enabled==1){
		$licenseManager=$GLOBALS["CLASS_UNIX"]->PIDOF("/opt/kaspersky/kav4proxy/bin/kav4proxy-licensemanager");
		if($GLOBALS["CLASS_UNIX"]->process_exists($licenseManager)){
			if($GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($licenseManager)>1){
				if(function_exists("WriteToSyslog")){WriteToSyslog("Killing /opt/kaspersky/kav4proxy/bin/kav4proxy-licensemanager", basename(__FILE__));}
				events("Killing /opt/kaspersky/kav4proxy/bin/kav4proxy-licensemanager $licenseManager",__FUNCTION__,__LINE__);
				$GLOBALS["CLASS_UNIX"]->KILL_PROCESS($licenseManager,9);
				
				
			}
		}
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.kaspersky-update-logs.php >/dev/null 2>&1 &");
	
	}
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF("/opt/kaspersky/kav4proxy/sbin/kav4proxy-kavicapserver");
		
	}
	
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l).kav4Proxy_keepup2date();
	}else{
		if($enabled==0){
			if(function_exists("WriteToSyslog")){WriteToSyslog("kavicapserverEnabled = 0 : Stopping kav4proxy...", basename(__FILE__));}
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/artica-postfix stop kav4proxy >/dev/null 2>&1 &");
		}
	}
	
	
	

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l).kav4Proxy_keepup2date();

}
function kav4Proxy_keepup2date(){
	if(!$GLOBALS["CLASS_USERS"]->KASPERSKY_WEB_APPLIANCE){
		if(!$GLOBALS["CLASS_UNIX"]->SQUID_INSTALLED()){return null;}
		if(!$GLOBALS["CLASS_USERS"]->KAV4PROXY_INSTALLED){return null;}
		$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
		if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
		$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("kavicapserverEnabled");
		if($SQUIDEnable==0){$enabled=0;}
	}else{
		$enabled=1;
	}

	$pid=$GLOBALS["CLASS_UNIX"]->PIDOF("/opt/kaspersky/kav4proxy/bin/kav4proxy-keepup2date");
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($pid)){return;}
	$l[]="";
	$l[]="[KAV4PROXY_KEEPUP2DATE]";
	$l[]="service_name=KAV4PROXY_KEEPUP2DATE";
	$l[]="master_version=".GetVersionOf("kav4proxy");
	$l[]="service_cmd=kav4proxy";
	$l[]="service_disabled=$enabled";
	$l[]="remove_cmd=--kav4Proxy-remove";
	$l[]="explain=enable_kavproxy_text";
	$l[]="family=squid";
	$l[]="running=1";
	$l[]=GetMemoriesOf($pid);
	$l[]="";
	return implode("\n",$l);return;
}

// ========================================================================================================================================================
function proxy_pac_status(){


	if(is_file("/opt/artica-agent/bin/php")){return null;}
	if(!$GLOBALS["CLASS_UNIX"]->SQUID_INSTALLED()){return null;}


	$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidEnableProxyPac");
	if($enabled==null){$enabled=0;}
	if($SQUIDEnable==0){$enabled=0;}

	$master_pid=trim(@file_get_contents("/var/run/proxypac.pid"));

	$l[]="[APP_PROXY_PAC]";
	$l[]="service_name=APP_PROXY_PAC";
	$l[]="master_version=1.00";
	$l[]="service_cmd=proxy-pac";
	$l[]="service_disabled=$enabled";

	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);return;}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}

// ========================================================================================================================================================
function wpa_supplicant(){
	if(!$GLOBALS["CLASS_USERS"]->WPA_SUPPLIANT_INSTALLED){return null;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WpaSuppliantEnabled");
	if($enabled==null){$enabled=1;}
	$eth=trim($GLOBALS["CLASS_UNIX"]->GET_WIRELESS_CARD());
	if(trim($eth)==null){$enabled=0;}
	$master_pid=trim(@file_get_contents("/var/run/wpa_supplicant.$eth.pid"));
	$WifiAPEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WifiAPEnable");
	if($WifiAPEnable<>1){$WifiAPEnable=0;}
	if($WifiAPEnable==0){$enabled=0;}

	$l[]="[APP_WPA_SUPPLIANT]";
	$l[]="service_name=APP_WPA_SUPPLIANT";
	$l[]="master_version=".GetVersionOf("wpa_suppliant");
	$l[]="service_cmd=wifi";
	$l[]="service_disabled=$enabled";
	$l[]="family=network";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);return;}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
// ========================================================================================================================================================
function arp_spoof(){
	if(!$GLOBALS["CLASS_USERS"]->ETTERCAP_INSTALLED){return null;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArpSpoofEnabled");
	if(!is_numeric($enabled)){$enabled=0;}
	if($enabled==0){return;}
	shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.arpspoof.php --start >/dev/null 2>&1 &");
}
// ========================================================================================================================================================
function fetchmail_version(){
	if(isset($GLOBALS["fetchmail_version"])){return $GLOBALS["fetchmail_version"];}
	$fetchmail=$GLOBALS["CLASS_UNIX"]->find_program("fetchmail");
	if(!is_file($fetchmail)){return "0.0.0";}
	exec("$fetchmail -V 2>&1",$results);

	while (list ($md, $line) = each ($results) ){
		if(preg_match("#release\s+([0-9\.]+)#", $line,$re)){
			$GLOBALS["fetchmail_version"]=$re[1];
			return $re[1];
		}
		if(preg_match("#version\s+([0-9\.]+)#", $line,$re)){
			$GLOBALS["fetchmail_version"]=$re[1];
			return $re[1];
		}
	}

	return "0.0.0";
}

function fetchmail(){



	if(!$GLOBALS["CLASS_USERS"]->fetchmail_installed){return null;}
	$EnablePostfixMultiInstance=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfixMultiInstance");
	$EnableFetchmailScheduler=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFetchmailScheduler");
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFetchmail");
	if(!is_numeric($enabled)){$enabled=0;}
	if(!is_numeric($EnableFetchmailScheduler)){$EnableFetchmailScheduler=0;}
	if($EnableFetchmailScheduler==1){return;}
	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	if($DisableMessaging==1){$enabled=0;}


	if($EnablePostfixMultiInstance<>1){
		if(!is_file("/etc/fetchmailrc")){$enabled=0;}
		$master_pid=trim(@file_get_contents("/var/run/fetchmail.pid"));
		if(preg_match("#^([0-9]+)#",$master_pid,$re)){$master_pid=$re[1];}
		$l[]="[FETCHMAIL]";
		$l[]="service_name=APP_FETCHMAIL";
		$l[]="master_version=".fetchmail_version();
		$l[]="service_cmd=/etc/init.d/fetchmail";
		$l[]="service_disabled=$enabled";
		$l[]="watchdog_features=1";
		$l[]="family=mailbox";
		 
		if($enabled==1){
			$fetchmail_count_server=fetchmail_count_server();
			if($GLOBALS["VERBOSE"]){echo "fetchmail_count_server: $fetchmail_count_server\n";}

			if($fetchmail_count_server>0){
				if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
					if(!$GLOBALS["DISABLE_WATCHDOG"]){
						shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.initslapd.php --fetchmail >/dev/null 2>&1");
						shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.fetchmail.php --start >/dev/null 2>&1 &");
					}
					$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);
					return;
				}
			}
		}

	if($enabled==0){return implode("\n",$l);return;}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
	$l[]="running=0";}else{$l[]="running=1";$l[]=GetMemoriesOf($master_pid);}
	$l[]="";
	}else{
		$enabled=1;
	}

	$master_pid=trim(@file_get_contents("/etc/artica-postfix/exec.fetmaillog.php.pid"));
	$l[]="[FETCHMAIL_LOGGER]";
	$l[]="service_name=APP_FETCHMAIL_LOGGER";
	$l[]="master_version=".fetchmail_version();
	$l[]="service_cmd=fetchmail-logger";
	$l[]="service_disabled=$enabled";
	$l[]="watchdog_features=1";

	if($enabled==1){
		if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			$fetchmail_count_server=fetchmail_count_server();
			if($GLOBALS["VERBOSE"]){echo "fetchmail_count_server: $fetchmail_count_server\n";}
			if($fetchmail_count_server>0){
				WATCHDOG("APP_FETCHMAIL_LOGGER","fetchmail-logger");
				$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);
				return;
			}else{
				return implode("\n",$l);return;
			}
		}
	}

	if($enabled==0){return implode("\n",$l);return;}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}

function fetchmail_count_server(){
	$f=explode("\n",@file_get_contents("/etc/fetchmailrc"));
	$count=0;
	while (list ( $i,$line) = each ($f)){if(preg_match("#^poll\s+(.+)#",$line)){$count=$count+1;}}
	return $count;
}

//========================================================================================================================================================


function mimedefang_version(){
	if(isset($GLOBALS["mimedefang_version"])){return $GLOBALS["mimedefang_version"];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("mimedefang");
	if(!is_file($bin)){return;}
	$string=exec("$bin -v 2>&1");
	if(preg_match("#version\s+([0-9\.]+)#",$string,$re)){
		$GLOBALS["mimedefang_version"]=$re[1];
		return $re[1];
	}
	
}
//========================================================================================================================================================

function mailarchive_pid(){
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
	exec("$pgrep -l -f milter_archiver.pl 2>&1",$results);
	if(!is_array($results)){return null;}
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#pgrep#",$ligne,$re)){continue;}
		if(!preg_match("#([0-9]+)\s+(.+)#",$ligne,$re)){continue;}
		return $re[1];
	}

}

function mimedefang(){
	$users=new settings_inc();

	if(!$GLOBALS["CLASS_USERS"]->MIMEDEFANG_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo "DEBUG:MIMEDEFANG_INSTALLED(): Not installed\n";}
		return null;


	}
	$MimeDefangEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO('MimeDefangEnabled');
	if(!is_numeric($MimeDefangEnabled)){$MimeDefangEnabled=0;}

	if($GLOBALS["VERBOSE"]){echo "DEBUG: MimeDefangEnabled..: $MimeDefangEnabled\n";}
	$pid_path="/var/spool/MIMEDefang/mimedefang.pid";
	if($GLOBALS["VERBOSE"]){echo "DEBUG: pid path....: $pid_path\n";}
	$master_pid=trim(@file_get_contents($pid_path));
	if($GLOBALS["VERBOSE"]){echo "DEBUG: master pid..: $master_pid\n";}
	
	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	if($DisableMessaging==1){$MimeDefangEnabled=0;}
	
	$l[]="[APP_MIMEDEFANG]";
	$l[]="service_name=APP_MIMEDEFANG";
	$l[]="master_version=".mimedefang_version();
	$l[]="service_cmd=mimedefang";
	$l[]="service_disabled=$MimeDefangEnabled";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=postfix";
	
	$mem=$GLOBALS["CLASS_UNIX"]->TOTAL_MEMORY_MB();
	if($mem<1500){
		$MimeDefangEnabled=0;
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("MimeDefangEnabled","0");
	}
	
	if($MimeDefangEnabled==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/artica-postfix stop mimedefang");}
		return implode("\n",$l);
	}
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_MIMEDEFANG","mimedefang");
		$l[]="running=0";
		$l[]="installed=1\n";
		return implode("\n",$l);
		return;
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================
function mimedefangmx(){
	$users=new settings_inc();

	if(!$GLOBALS["CLASS_USERS"]->MIMEDEFANG_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo "DEBUG:MIMEDEFANG_INSTALLED(): Not installed\n";}
		return null;


	}
	$MimeDefangEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO('MimeDefangEnabled');
	if(!is_numeric($MimeDefangEnabled)){$MimeDefangEnabled=0;}

	if($GLOBALS["VERBOSE"]){echo "DEBUG: MimeDefangEnabled..: $MimeDefangEnabled\n";}
	$pid_path="/var/spool/MIMEDefang/mimedefang-multiplexor.pid";
	if($GLOBALS["VERBOSE"]){echo "DEBUG: pid path....: $pid_path\n";}
	$master_pid=trim(@file_get_contents($pid_path));
	if($GLOBALS["VERBOSE"]){echo "DEBUG: master pid..: $master_pid\n";}
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program("mimedefang-multiplexor");
	$masterpid=$GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			@file_put_contents($pid_path, $master_pid);
		}
	}
	
	
	
	
	$l[]="[APP_MIMEDEFANGX]";
	$l[]="service_name=APP_MIMEDEFANGX";
	$l[]="master_version=".mimedefang_version();
	$l[]="service_cmd=mimedefang";
	$l[]="service_disabled=$MimeDefangEnabled";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=postfix";
	
	$mem=$GLOBALS["CLASS_UNIX"]->TOTAL_MEMORY_MB();
	if($mem<1500){
		$MimeDefangEnabled=0;
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("MimeDefangEnabled","0");
	}
	
	if($MimeDefangEnabled==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/artica-postfix stop mimedefang");}
		return implode("\n",$l);
	}
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_MIMEDEFANGX","mimedefang");
		$l[]="running=0";
		$l[]="installed=1\n";
		return implode("\n",$l);
		return;
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================



function assp(){
	$users=new settings_inc();

	if(!$GLOBALS["CLASS_USERS"]->ASSP_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo "DEBUG:assp(): Not installed\n";}
		return null;


	}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO('EnableASSP');
	if($enabled==null){$enabled=0;}

	if($GLOBALS["VERBOSE"]){echo "DEBUG: EnableASSP..: $enabled\n";}
	$pid_path="/usr/share/assp/pid";
	if($GLOBALS["VERBOSE"]){echo "DEBUG: pid path....: $pid_path\n";}
	$master_pid=trim(@file_get_contents($pid_path));
	if($GLOBALS["VERBOSE"]){echo "DEBUG: master pid..: $master_pid\n";}
	$l[]="[ASSP]";
	$l[]="service_name=APP_ASSP";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->ASSP_VERSION();
	$l[]="service_cmd=assp";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=postfix";
	if($enabled==0){return implode("\n",$l);return;}
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_ASSP","assp");
		$l[]="running=0";
		$l[]="installed=1\n";
		return implode("\n",$l);
		return;
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================
function framework(){
	if(!is_file("/etc/artica-postfix/framework.conf")){return;}
	$pid_path="/var/run/lighttpd/framework.pid";
	$lighttpd=$GLOBALS["CLASS_UNIX"]->find_program("lighttpd");
	if(!is_file($lighttpd)){return;}
	$master_pid=trim(@file_get_contents($pid_path));
	if($master_pid==null){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("lighttpd -f /etc/artica-postfix/framework.conf");
		if($master_pid<>null){@file_put_contents("/var/run/lighttpd/framework.pid",$master_pid);}
	}
	
	

	

	$l[]="[FRAMEWORK]";
	$l[]="service_name=APP_FRAMEWORK";
	$l[]="master_version=".GetVersionOf("lighttpd");
	$l[]="service_cmd=apache";
	$l[]="service_disabled=1";
	$l[]="watchdog_features=1";
	$l[]="pid_path=$pid_path";
	$l[]="family=system";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.framework.php --start >/dev/null 2>&1 &");
		shell_exec2($cmd);
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	
	if(!$GLOBALS["CLASS_UNIX"]->is_socket("/usr/share/artica-postfix/ressources/web/framework.sock")){
		ToSyslog("Fatal artica-postfix/ressources/web/framework.sock no such socket !!!");
		$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.framework.php --restart >/dev/null 2>&1 &");
		return;
	}
	
	@chmod("/usr/share/artica-postfix/ressources/web/framework.sock",0777);
	
	$time=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/settings/Daemons/HdparmInfos");
	$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.hdparm.php >/dev/null 2>&1 &");
	if($time>60){shell_exec2($cmd);}
	if(!is_file("/etc/init.d/artica-swap")){
		$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.initd-swap.php >/dev/null 2>&1 &");
		shell_exec2($cmd);
	}
	$WifiCardOk=$GLOBALS["CLASS_SOCKETS"]->GET_INFO('WifiCardOk');
	if(!is_numeric($WifiCardOk)){
		if($GLOBALS["CLASS_UNIX"]->file_time_get("exec.wifi.detect.cards.php")>5){
			$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.wifi.detect.cards.php --detect >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
	}
		
	if($WifiCardOk==1){
		$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.wifi.detect.cards.php --iwlist >/dev/null 2>&1 &");
		shell_exec2($cmd);
	}
	
	
	
	return implode("\n",$l);

}
//========================================================================================================================================================

function UpdateUtilityHTTP(){
	$lighttpd=$GLOBALS["CLASS_UNIX"]->find_program("lighttpd");
	if(!is_file($lighttpd)){return null;}
	$pid_path="/var/run/UpdateUtility/lighttpd.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	if($master_pid==null){
		$lighttpd=$GLOBALS["CLASS_UNIX"]->find_program("lighttpd");
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("lighttpd -f /etc/UpdateUtility/lighttpd.conf");
		if($master_pid<>null){@file_put_contents($pid_path,$master_pid);}
	}
	$UpdateUtilityEnableHTTP=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UpdateUtilityEnableHTTP");
	if(!is_numeric($UpdateUtilityEnableHTTP)){$UpdateUtilityEnableHTTP=0;}

	$l[]="[APP_UPDATEUTILITYHTTP]";
	$l[]="service_name=APP_UPDATEUTILITYHTTP";
	$l[]="master_version=".GetVersionOf("lighttpd");
	$l[]="service_cmd=UpdateUtility";
	$l[]="service_disabled=$UpdateUtilityEnableHTTP";
	$l[]="watchdog_features=1";
	$l[]="pid_path=$pid_path";
	$l[]="family=system";
	
	$arrayfile="/usr/share/artica-postfix/ressources/logs/web/UpdateUtilitySize.size.db";
	$time=$GLOBALS["CLASS_UNIX"]->file_time_min($arrayfile);
	if($arrayfile>19){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.keepup2date.php --UpdateUtility-size >/dev/null 2>&1 &");
	}
	$scan=$GLOBALS["CLASS_UNIX"]->DirFiles("UpdateUtility-.*?\.log$");
	if(count($scan)>0){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.keepup2date.php --UpdateUtility-logs >/dev/null 2>&1 &");
	}
	
	
	if($UpdateUtilityEnableHTTP==0){
		return implode("\n",$l);
		return;
	}
	
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_UPDATEUTILITYHTTP","UpdateUtility");
		$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);return;}
		$l[]="running=1";
		$l[]=GetMemoriesOf($master_pid);
		$l[]="";

		return implode("\n",$l);return;

}
//========================================================================================================================================================

function checksyslog(){
	
	$syslogpath=$GLOBALS["CLASS_UNIX"]->LOCATE_SYSLOG_PATH();
	$size=@filesize($syslogpath);
	if($GLOBALS["VERBOSE"]){echo "$syslogpath -> Size:$size\n";}
	if($size<5){
		$GLOBALS["CLASS_UNIX"]->send_email_events("Warning $syslogpath $size Bytes, restarting Syslog", "Suspicious system log size, restarting syslog daemon", "system");
		$GLOBALS["CLASS_UNIX"]->RESTART_SYSLOG(true);
	}
}





function philesight(){
	$pids=array();
	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
	$kill=$GLOBALS["CLASS_UNIX"]->find_program("kill");
	exec("$pgrep -l -f \"ruby.*?philesight\" 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#pgrep#", $line)){continue;}
		if(preg_match("#^([0-9]+)\s+#",$line,$re)){
			$pids[$re[1]]=true;
		}
		
	}
	
	if(count($pids)==0){return;}
	while (list ($pid, $line) = each ($pids)){
		$time=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($pid);
		if($time>120){
			$GLOBALS["CLASS_UNIX"]->send_email_events("Warning killing philesight process $pid running since {$time}mn", "Suspicious overloaded process", "system");
			unix_system_kill_force($pid);
			
		}
	}
}

function ucarp_version(){
	if(isset($GLOBALS["ucarp_version"])){return $GLOBALS["ucarp_version"];}
	$ucarp=$GLOBALS["CLASS_UNIX"]->find_program("ucarp");
	exec("$ucarp --help 2>&1",$results);
	while (list ($i, $line) = each ($results) ){
		$line=trim($line);
		if($line==null){continue;}
		if(preg_match("#^ucarp\s+([0-9\.]+)\s+#", $line,$re)){
			$GLOBALS["ucarp_version"]=$re[1];
			return $GLOBALS["ucarp_version"];
		}
	}
	
	return 0;
	
}

function ucarp(){
	if($GLOBALS["VERBOSE"]){echo " ********************************** UCARP ******************\n";}
	$ucarp=$GLOBALS["CLASS_UNIX"]->find_program("ucarp");
	if(!is_file($ucarp)){
		if($GLOBALS["VERBOSE"]){echo "No such binary\n";}
		
		return;}
	$enabled=1;
	$HEAD="UCARP_SLAVE";
	if(!is_file("/usr/share/ucarp/ETH_LIST")){
		if($GLOBALS["VERBOSE"]){echo " */usr/share/ucarp/ETH_LIST no such file\n";}
		return;}
	if(is_file("/usr/share/ucarp/Master")){$HEAD="UCARP_MASTER";}
	
	$ETHS=unserialize(@file_get_contents("/usr/share/ucarp/ETH_LIST"));
	while (list ($Interface, $ucarpcmdLINE) = each ($ETHS) ){
		$PID=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("$ucarp.*?--interface=$Interface");
		if($GLOBALS["CLASS_UNIX"]->process_exists($PID)){
			$l[]="[$HEAD]";
			$l[]="service_name=$HEAD";
			$l[]="master_version=".ucarp_version();
			$l[]="service_cmd=/etc/init.d/artica-failover";
			$l[]="service_disabled=1";
			$l[]="watchdog_features=1";
			$l[]="running=1";
			$l[]=GetMemoriesOf($PID);
			$l[]="";
			return implode("\n",$l);			
			
		}
		
	}
	
	$l[]="[$HEAD]";
	$l[]="service_name=$HEAD";
	$l[]="master_version=".ucarp_version();
	$l[]="service_cmd=/etc/init.d/artica-failover";
	$l[]="service_disabled=1";
	$l[]="watchdog_features=1";
	$l[]="running=0";
	$l[]="";	
	return implode("\n",$l);
	
}

function ChecksRoutes(){
	$CacheFileTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$globalStatusIniTime=$GLOBALS["CLASS_UNIX"]->file_time_min($CacheFileTime);
	if($globalStatusIniTime<1){return;}
		
	@unlink($CacheFileTime);
	@file_put_contents($CacheFileTime, time());
	
	$ip=$GLOBALS["CLASS_UNIX"]->find_program("ip");
	exec("$ip route 2>&1",$results);
	$c=0;
	while (list ($num, $ligne) = each ($results) ){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		$c++;
	}

	if($c>0){return;}
	events_syslog("kernel: [  Artica-Net] Start Network [artica-ifup] (".basename(__FILE__)."/".__LINE__.")" );
	shell_exec("/etc/init.d/artica-ifup start");
	system_admin_events("No route defined", "I can't see routes in\n".@implode("\n", $results)."\nNetwork will be rebooted",__FUNCTION__,__FILE__,__LINE__,"network",0);
	
}


function pdns_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$pdns=$GLOBALS["CLASS_UNIX"]->find_program("pdns_server");
	exec("$pdns --version 2>&1",$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#Version:\s+([0-9\.]+)#", $line,$re)){
			$GLOBALS[__FUNCTION__]=$re[1];
			return $re[1];
		}
	}
}


function pdns_server(){
	$verbose=$GLOBALS["VERBOSE"];
	if(!$GLOBALS["CLASS_USERS"]->POWER_DNS_INSTALLED){if($verbose){echo "POWER_DNS_INSTALLED -> FALSE, return\n";}}
	if(!$GLOBALS["CLASS_USERS"]->POWER_DNS_INSTALLED){return null;}
	$enabled=1;
	$DisablePowerDnsManagement=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePowerDnsManagement");
	$EnablePDNS=$GLOBALS["CLASS_USERS"]->EnablePDNS();
	$PDNSRestartIfUpToMB=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSRestartIfUpToMB");
	if($DisablePowerDnsManagement==1){$enabled=0;}
	
	if(!is_numeric($PDNSRestartIfUpToMB)){$PDNSRestartIfUpToMB=700;}

	$pdns_server=$GLOBALS["CLASS_UNIX"]->find_program("pdns_server");


	if($pdns_server==null){
		if($verbose){echo "pdns_server no such binary\n";}
		return null;
	}

	$pid_path="/var/run/pdns/pdns.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($pdns_server);
		if($master_pid<>null){@file_put_contents($pid_path,$master_pid);}
	}

	if($enabled==1){
		if($EnablePDNS==0){$enabled=0;}
	}
	$version=pdns_version();
	$GLOBALS["PDNS_VERSION"]=$version;
	if($verbose){echo "version=$version Enabled=$enabled\n";}

	$l[]="[APP_PDNS]";
	$l[]="service_name=APP_PDNS";
	$l[]="master_version=$version";
	$l[]="service_cmd=/etc/init.d/pdns";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=network";
	 
	if($enabled==0){
		if($verbose){echo "PNS is not enabled running next function -> pdns_instance()\n";}
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){if($DisablePowerDnsManagement==0){shell_exec2("/etc/init.d/pdns stop >/dev/null 2>&1 &");}}
		$instance=pdns_instance();
		return implode("\n",$l).$instance;
	}
	 
	if($verbose){echo "Detected PID: $master_pid ->  check it...\n";}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			if($verbose){echo "-> pid: [$master_pid] failed -> watchdog";}
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/pdns restart >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);
		return;
	}
	if($verbose){echo "Detected PID: $master_pid ->  Seems to be running\n";}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	if($verbose){echo "-> pdns_instance()";}
	$instance=pdns_instance();
	return implode("\n",$l).$instance;return;

}

function pdns_instance(){
	$verbose=$GLOBALS["VERBOSE"];
	$master_pid=null;
	$PDNSRestartIfUpToMB=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSRestartIfUpToMB");
	$pdns_server=$GLOBALS["CLASS_UNIX"]->find_program("pdns_server");
	if($pdns_server==null){if($verbose){echo "pdns_server no such binary\n";}return null;}

	$pidof=$GLOBALS["CLASS_UNIX"]->find_program("pidof");
	$cmd="$pidof $pdns_server-instance 2>&1";
	exec($cmd,$results);
	if($verbose){echo "$cmd return ". count($results)." rows\n";}
	while (list ($num, $ligne) = each ($results) ){
		if(trim($ligne)==null){continue;}
		if(preg_match("#^([0-9]+)#",$ligne,$re)){
			if($GLOBALS["CLASS_UNIX"]->process_exists($re[1])){$master_pid=$re[1];break;}
		}
	}


	if(!is_numeric($master_pid)){
		$results=array();
		$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
		$cmd="$pgrep -l -f $pdns_server-instance 2>&1";
		exec($cmd,$results);
		if($verbose){echo "$cmd return ". count($results)." rows\n";}
		while (list ($num, $ligne) = each ($results) ){
			if(trim($ligne)==null){continue;}
			if(preg_match("#^([0-9]+)\s+.+?pdns#",$ligne,$re)){
				if($GLOBALS["CLASS_UNIX"]->process_exists($re[1])){$master_pid=$re[1];break;}
			}
		}
	}



	if($GLOBALS["VERBOSE"]){echo "$pdns_server-instance -> $master_pid\n";}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){return;}

	$PROCESS_MEMORY=$GLOBALS["CLASS_UNIX"]->PROCESS_MEMORY($master_pid,true);
	$PROCESS_CACHE_MEMORY=$GLOBALS["CLASS_UNIX"]->PROCESS_CACHE_MEMORY($master_pid,true);
	$PDNSRestartIfUpToMBOrg=$PDNSRestartIfUpToMB;
	if($PDNSRestartIfUpToMB>0){
		$PDNSRestartIfUpToMB=$PDNSRestartIfUpToMB*1024;

		if($verbose){echo "PROCESS_MEMORY:{$PROCESS_MEMORY}KB against {$PDNSRestartIfUpToMB}KB\n";}

		if($PROCESS_MEMORY>$PDNSRestartIfUpToMB){
			$PROCESS_MEMORY_EX=round($PROCESS_MEMORY/1024,2);
			$GLOBALS["CLASS_UNIX"]->send_email_events("Watchdog: PowerDNS reach Max memory !!! ({$PROCESS_MEMORY_EX}M/{$PDNSRestartIfUpToMBOrg}M)","PowerDNS service was restarted","system");
			$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
			shell_exec2(trim("$nohup /etc/init.d/pdns restart >/dev/null 2>&1 &"));
		}}
		$l[]="";
		$l[]="";
		$l[]="[APP_PDNS_INSTANCE]";
		$l[]="service_name=APP_PDNS_INSTANCE";
		$l[]="master_version={$GLOBALS["PDNS_VERSION"]}";
		$l[]="service_cmd=pdns";
		$l[]="service_disabled=1";
		$l[]="watchdog_features=1";
		$l[]="family=network";
		$l[]="running=1";
		$l[]="master_memory=$PROCESS_MEMORY";
		$l[]="master_cached_memory=$PROCESS_CACHE_MEMORY";
		$l[]="processes_number=1";
		$l[]="master_pid=$master_pid";
		$l[]="running=1\ninstalled=1";
		$l[]="";
		return implode("\n",$l);
}

//========================================================================================================================================================
function pdns_recursor(){


	if(!$GLOBALS["CLASS_USERS"]->POWER_DNS_INSTALLED){return null;}
	$enabled=1;
	$DisablePowerDnsManagement=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePowerDnsManagement");
	if($DisablePowerDnsManagement==1){$enabled=0;}
	$pdns_server=$GLOBALS["CLASS_UNIX"]->find_program("pdns_recursor");
	if($pdns_server==null){
		if($GLOBALS["VERBOSE"]){echo "pdns_recursor no such binary\n";}
		return null;}
	if(!is_file($pdns_server)){
		if($GLOBALS["VERBOSE"]){echo "pdns_recursor no such binary\n";}
		return null;}
	$EnablePDNS=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePDNS");
	if(!is_numeric($EnablePDNS)){$EnablePDNS=0;}
	
	$DHCPDEnableCacheDNS=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPDEnableCacheDNS");
	if(!is_numeric($DHCPDEnableCacheDNS)){$DHCPDEnableCacheDNS=0;}
	if($DHCPDEnableCacheDNS==1){$EnablePDNS=0;}
	

	
	if($GLOBALS["VERBOSE"]){echo "pdns_recursor EnablePDNS=$EnablePDNS\n";}

	$pid_path="/var/run/pdns/pdns_recursor.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($pdns_server);
		if($master_pid<>null){@file_put_contents($pid_path,$master_pid);}
	}
	
	
	$EnableChilli=0;
	$chilli=$GLOBALS["CLASS_UNIX"]->find_program("chilli");
	if(is_file($chilli)){
		$EnableChilli=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableChilli");
		if(!is_numeric($EnableChilli)){$EnableChilli=0;}
		if($EnableChilli==1){$enabled=0;}
	}


	if($enabled==1){
		if($EnablePDNS==0){$enabled=0;}
	}
	
	
	

	$l[]="[PDNS_RECURSOR]";
	$l[]="service_name=APP_PDNS_RECURSOR";
	$l[]="master_version=".GetVersionOf("pdns");
	$l[]="service_cmd=/etc/init.d/pdns-recursor";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=network";
	 
	if($enabled==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if($DisablePowerDnsManagement==0){shell_exec2("/etc/init.d/pdns-recursor stop >/dev/null 2>&1 &");}}
		return implode("\n",$l);
		return;
	}
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			if($verbose){echo "-> pid: [$master_pid] failed -> watchdog";}
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/pdns-recursor restart >/dev/null 2>&1 &");
		}
		
		
		$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);return;}
		$l[]="running=1";
		$l[]=GetMemoriesOf($master_pid);
		$l[]="";

		return implode("\n",$l);return;

}

//========================================================================================================================================================
function cyrus_imap(){
	if(!$GLOBALS["CLASS_USERS"]->cyrus_imapd_installed){return null;}
	$pid_path=$GLOBALS["CLASS_UNIX"]->LOCATE_CYRUS_PIDPATH();
	$master_pid=trim(@file_get_contents($pid_path));
	$enabled=1;
	if($GLOBALS["CLASS_USERS"]->ZARAFA_INSTALLED){$enabled=0;}
	$EnableCyrusImap=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCyrusImap");
	if(!is_numeric($EnableCyrusImap)){$EnableCyrusImap=1;}
	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	if($EnableCyrusImap==0){$enabled=0;}
	if($DisableMessaging==1){$enabled=0;}
	
	$l[]="[CYRUSIMAP]";
	$l[]="service_name=APP_CYRUS";
	$l[]="master_version=".GetVersionOf("cyrus-imap");
	$l[]="service_cmd=/etc/init.d/cyrus-imapd";
	$l[]="service_disabled=1";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=mailbox";
	$l[]="service_disabled=$enabled";
	if($enabled==0){
		return implode("\n",$l);
		return;
	}
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.cyrus-imapd.php --start >/dev/null 2>&1 &");
		shell_exec2($cmd);
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	if(is_file("/var/run/saslauthd/mux")){@chmod("/var/run/saslauthd/mux", 0777);}
	
	
	$timefile="/etc/artica-postfix/croned.1/exec.cyrus.php.DirectorySize.time";
	$filetim=$GLOBALS["CLASS_UNIX"]->file_time_min($timefile);
	if($filetim>240){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.cyrus.php --DirectorySize >/dev/null 2>&1 &");
	}
	
	return implode("\n",$l);return;
	

}
function cyrus_imap_pid(){
	$pidpath=$GLOBALS["CLASS_UNIX"]->CYRUS_PID_PATH();
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pidpath);
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($pid)){
		return $GLOBALS["CLASS_UNIX"]->PIDOF($unix->CYRUS_DAEMON_BIN_PATH());
	}
	return $pid;


}


function mysql_watchdog(){
	$mysqladmin=$GLOBALS["CLASS_UNIX"]->find_program("mysqladmin");
	$zarafa_enabled=0;
	if($GLOBALS["CLASS_USERS"]->ZARAFA_INSTALLED){
		$zarafa_enabled=1;
		$pid_path="/var/run/zarafa-server.pid";
		if(!$GLOBALS["CLASS_UNIX"]->process_exists(@file_get_contents($pid_path))){
			events("Zarafa is installed but did not running...",__FUNCTION__,__LINE__);
			$zarafa_enabled=0;
		}
	}
	
	
	
	$timefile="/etc/artica-postfix/pids/MySQLRepairDBTime.time";
	$timex=$GLOBALS["CLASS_UNIX"]->file_time_min($timefile);
	if($timex>240){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.mysql.clean.php --corrupted >/dev/null 2>&1 &");
		
	}
	
	$countq=array();
	if(!is_file($mysqladmin)){
		events("mysqladmin no such file",__FUNCTION__,__LINE__);
		return;
	}

	exec("$mysqladmin processlist 2>&1",$results);

	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#\|\s+([0-9]+)\s+\|.+?\|.+?\|\s+(.+?)\s+\|.+?\|.+?\|\s+(.+?)\s+|(.+?)\|#",$ligne,$re)){
			$ID=$re[1];
			$DB=$re[2];
			$State=$re[3];
			$QUERY=$re[4];
			if($QUERY==null){continue;}
			$notifs[]="$ID db:$DB ($State) query:$QUERY";
			$md5=md5("$DB$State$QUERY");
			if(!isset($countq[$md5])){$countq[$md5]=1;}else{$countq[$md5]=$countq[$md5]+1;}
			events("$ID db:$DB ($State) $QUERY count({$countq[$md5]}) zarafa:$zarafa_enabled",__FUNCTION__,__LINE__);
			if($countq[$md5]>10){
				events("Too many same processes",__FUNCTION__,__LINE__);
				$text="It seems that the mysql server using many threads.
				this is what artica has detected:
				
				".@implode("\n",$notifs)."
				--------------------------------------------------------------------------
				Process dump :
				" .@implode("\n",$results)."\n";
					
				if($zarafa_enabled==0){
					$GLOBALS["CLASS_UNIX"]->send_email_events("Mysql too many queries (restarting mysql)",$text."\nMysql has been restarted","system");
					shell_exec2(trim("{$GLOBALS["nohup"]} /etc/init.d/mysql restart >/dev/null 2>&1 &"));
				}else{
					$GLOBALS["CLASS_UNIX"]->send_email_events("Mysql many queries (information)",$text,"system");
				}
			}
		}
	}
}




//========================================================================================================================================================

function mysqld_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	
	$mysqld=$GLOBALS["CLASS_UNIX"]->find_program("mysqld");
	exec("$mysqld --version 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){

		if(preg_match("#mysqld.*?([0-9\.\-]+)#", $ligne,$re)){
			$GLOBALS[__FUNCTION__]=$re[1];
			return $GLOBALS[__FUNCTION__];
		}
	}
}
//========================================================================================================================================================
function mysqld_init_fix(){
	$f=explode("\n",@file_get_contents("/etc/init.d/mysql"));
	while (list ($file, $line) = each ($f) ){
		if(preg_match("#RSYSLOGD=#", $line)){
			shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.initd-mysql.php");
		}
	}
	
	
}



function mysql_server(){


	if(!$GLOBALS["CLASS_USERS"]->mysql_installed ){return;}
	$program_path=$GLOBALS["CLASS_UNIX"]->find_program("mysqld");
	if($program_path==null){
		if(is_file("/usr/sbin/mysqld")){$program_path="/usr/sbin/mysqld";}
	}
	$pid_path=GetVersionOf("mysql-pid");
	if($pid_path==null){
		if($GLOBALS["VERBOSE"]){echo "Pid path is null -> PIDOF($program_path)";}
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($program_path);
	}else{
		$master_pid=trim(@file_get_contents($pid_path));
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if($GLOBALS["VERBOSE"]){echo "Pid $master_pid not in memory -> PIDOF($program_path)\n";}
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($program_path);
	}

	$mysqlversion=mysqld_version();

	$l[]="[ARTICA_MYSQL]";
	$l[]="service_name=APP_MYSQL_ARTICA";
	$l[]="master_version=$mysqlversion";
	$l[]="service_cmd=/etc/init.d/mysql";
	$l[]="service_disabled=1";
	$l[]="pid_path=$pid_path";
	$l[]="bin_path=$program_path";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	 
	$status=$GLOBALS["CLASS_UNIX"]->PROCESS_STATUS($master_pid);
	if($GLOBALS["VERBOSE"]){echo "Mysqld status = $status\n";print_r($status);}
	
	shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.mysql.start.php --watch >/dev/null 2>&1 &");
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$GLOBALS["CLASS_UNIX"]->send_email_events("MySQL not running, starting MySQL service", null, "system");
			shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.initd-mysql.php >/dev/null 2>&1");
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/mysql start >/dev/null 2>&1 &");
		}
		$l[]="";return implode("\n",$l);
		return;
	}else{
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.rrd.php --mysql >/dev/null 2>&1 &");
		mysql_watchdog();
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	
	if(!$GLOBALS["DISABLE_WATCHDOG"]){ mysqld_init_fix(); }
	exec("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.mysql.build.php --multi-status 2>&1",$result1s);
	$l[]="".@implode("\n", $result1s);
	
	
	shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.mysql.start.php --engines >/dev/null 2>&1 &");
	
	if(!$GLOBALS["DISABLE_WATCHDOG"]){
		if(!$GLOBALS["CLASS_UNIX"]->is_socket("/var/run/mysqld/mysqld.sock")){
			$GLOBALS["CLASS_UNIX"]->send_email_events("/var/run/mysqld/mysqld.sock no such socket, restarting MySQL", null, "system");
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.mysql.start.php --restart >/dev/null 2>&1 &");
		}
		
		$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
		if($DisableMessaging==0){
			$dir="/var/lib/mysql/postfixlog";
			$unix=new unix();
			$countDefiles=$GLOBALS["CLASS_UNIX"]->COUNT_FILES($dir)/2;
			if($countDefiles>500){
				shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.postfix.miltergrey.stats.php >/dev/null 2>&1 &");
			}
		}
	}
	
	$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.mysqld.crash.php.check_crashed.time");
	if($CacheSchedules>240){
		if(!system_is_overloaded()){
			$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.mysqld.crash.php --crashed >/dev/null 2>&1 &");
			if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
			shell_exec2($cmd);
		}
	}
	
	return implode("\n",$l);return;

}
//========================================================================================================================================================
function mysql_mgmt(){
	if(!$GLOBALS["CLASS_USERS"]->mysql_installed ){return;}
	$program=$GLOBALS["CLASS_UNIX"]->find_program("ndb_mgmd");
	if($program==null){return;}
	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($program);
	$EnableMysqlClusterManager=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMysqlClusterManager");
	if(!is_numeric($EnableMysqlClusterManager)){$EnableMysqlClusterManager=0;}
	if($EnableMysqlClusterManager==0){return;}
	$l[]="[MYSQL_CLUSTER_MGMT]";
	$l[]="service_name=APP_MYSQL_CLUSTER_MGMT";
	$l[]="master_version=".mysqld_version();
	$l[]="service_cmd=mysql-cluster";
	$l[]="service_disabled=$EnableMysqlClusterManager";
	$l[]="pid_path=$pid_path";
	$l[]="family=system";

	if($EnableMysqlClusterManager==0){
		return implode("\n",$l);
		return;
	}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="";return implode("\n",$l);return;}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================
function mysql_replica(){
	if(!$GLOBALS["CLASS_USERS"]->mysql_installed ){return;}
	$program=$GLOBALS["CLASS_UNIX"]->find_program("ndbd");
	if($program==null){return;}
	$EnableMysqlClusterReplicat=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMysqlClusterReplicat");
	if(!is_numeric($EnableMysqlClusterReplicat)){$EnableMysqlClusterReplicat=0;}



	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($program);
	$l[]="[MYSQL_CLUSTER_REPLICA]";
	$l[]="service_name=APP_MYSQL_CLUSTER_REPLICA";
	$l[]="master_version=".mysqld_version();
	$l[]="service_cmd=mysql-cluster";
	$l[]="service_disabled=$EnableMysqlClusterReplicat";
	$l[]="pid_path=$pid_path";
	$l[]="family=system";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="";return implode("\n",$l);return;}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================
function openldap(){
	
	if(!$GLOBALS["CLASS_USERS"]->openldap_installed){return;}
	$pid_path=GetVersionOf("openldap-pid");
	$master_pid=trim(@file_get_contents($pid_path));
	$bin_path=$GLOBALS["CLASS_UNIX"]->LOCATE_SLPAD_PATH();

	if($GLOBALS["VERBOSE"]){
		echo "pid_path = $pid_path\n";
		echo "master_pid = $master_pid\n";
		echo "bin_path = $bin_path\n";
	}

	$l[]="[LDAP]";
	$l[]="service_name=APP_LDAP";
	$l[]="master_version=".GetVersionOf("openldap");
	$l[]="service_cmd=/etc/init.d/slapd";
	$l[]="service_disabled=1";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	$l[]="bin_path=$bin_path";
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=trim($GLOBALS["CLASS_UNIX"]->PIDOF($bin_path));
		if($master_pid>3){
			@file_put_contents($pid_path,$master_pid);
		}
	}
	
	
	
	$ARRAY=$GLOBALS["CLASS_UNIX"]->ldap_GET_CONFS();
	@file_put_contents("/usr/share/artica-postfix/ressources/local_ldap.php", "<?php \$GLOBALS[\"MAIN_LOCAL_LDAP_SETTINGS\"]=\"".base64_encode(serialize($ARRAY))."\";?>");
	@chmod("/usr/share/artica-postfix/ressources/local_ldap.php",0755);
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		system_admin_events("Fatal: Local LDAP service is not running [action=restart]",__FUNCTION__,__FILE__,__LINE__,"system");
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} /etc/init.d/slapd restart >/dev/null 2>&1 &");
		$l[]="";return implode("\n",$l);return;
	}
	
	
	if(!$GLOBALS["CLASS_UNIX"]->is_socket("/var/run/slapd/slapd.sock")){
		system_admin_events("Fatal: No such  Unix socket /var/run/slapd/slapd.sock for LDAP service [action=restart]",__FUNCTION__,__FILE__,__LINE__,"system");
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} /etc/init.d/slapd restart >/dev/null 2>&1 &");
	}
	
	
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================
function saslauthd(){
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program("saslauthd");
	if(!is_file($binpath)){return;}
	$users=new settings_inc();
	if(!$GLOBALS["CLASS_USERS"]->POSTFIX_INSTALLED){
		if(!$GLOBALS["CLASS_USERS"]->cyrus_imapd_installed){
			return;
		}
	}
	
	
	
	$pid_path=GetVersionOf("saslauthd-pid");
	$master_pid=trim(@file_get_contents($pid_path));
	$l[]="[SASLAUTHD]";
	$l[]="service_name=APP_SASLAUTHD";
	$l[]="master_version=".GetVersionOf("saslauthd");
	$l[]="service_cmd=saslauthd";
	$l[]="service_disabled=1";
	$l[]="pid_path=$pid_path";
	$l[]="family=system";
	$l[]="watchdog_features=1";
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
	}
	 
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.saslauthd.php --build >/dev/null 2>&1");
			shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.saslauthd.php --start >/dev/null 2>&1");
		}
		
		$l[]="";
		return implode("\n",$l);
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	
	if(is_file("/var/run/saslauthd/mux")){@chmod("/var/run/saslauthd/mux", 0777);}

	return implode("\n",$l);return;

}
//========================================================================================================================================================
function syslogger(){
	if(!is_file("/usr/share/artica-postfix/exec.syslog.php")){return;}
	CheckCallable();
	$pid_path="/etc/artica-postfix/exec.syslog.php.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	$service_disabled=1;
	if(is_file("/etc/init.d/syslog")){@chmod("/etc/init.d/syslog",0755);}

	$l[]="[APP_SYSLOGER]";
	$l[]="service_name=APP_SYSLOGER";
	$l[]="master_version=".trim(@file_get_contents(dirname(__FILE__)."/VERSION"));
	$l[]="service_cmd=/etc/init.d/artica-syslog";
	$l[]="service_disabled=1";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	$l[]="pid_path=$pid_path";
	
	
	$size=$GLOBALS["CLASS_UNIX"]->file_size("/usr/share/artica-postfix/ressources/logs/php.log");
	if($size>104857600){@unlink("/usr/share/artica-postfix/ressources/logs/php.log");}
	
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		shell_exec2("/etc/init.d/artica-syslog restart");
		$l[]="";return implode("\n",$l);
		events("done",__FUNCTION__,__LINE__);
		return;
	}


	if(!is_file("/var/log/artica-postfix/syslogger.debug")){
		events("restart sysloger",__FUNCTION__,__LINE__);
		$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/etc/init.d/artica-syslog restart");
	}
	
	$unix=new unix();
	$timelog=$unix->file_time_min("/var/log/artica-postfix/syslogger.debug");
	events("/var/log/artica-postfix/syslogger.debug = $timelog minutes TTL",__FUNCTION__,__LINE__);

	$l[]="running=1";
	if($GLOBALS ["VERBOSE"]){echo "GetMemoriesOf -> $master_pid\n";}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	
	
	if($GLOBALS ["VERBOSE"]){echo "/var/log/auth.log ?\n";}
	if(is_file("/var/log/auth.log")){
		$authlog=@filesize("/var/log/auth.log");
		events("Syslog.../var/log/auth.log {$authlog} Bytes",__FUNCTION__,__LINE__);
		if($authlog<5){
			rsyslogd_bug_check();
			events("Restart syslog.../var/log/auth.log < 5 ",__FUNCTION__,__LINE__);
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.syslog-engine.php --restart-syslog --syslogmini >/dev/null 2>&1 &");
		}
		
	}else{
		events("Syslog.../var/log/auth.log no such file ",__FUNCTION__,__LINE__);
	}

	if(!$GLOBALS["DISABLE_WATCHDOG"]){
		$time=file_time_min("/var/log/artica-postfix/syslogger.debug");
		//writelogs("LOG TIME: $time",__FUNCTION__,__FILE__,__LINE__);
		if($time>5){
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/etc/init.d/artica-syslog restart");
		}
	}
	
	return implode("\n",$l);return;

}
function rsyslogd_bug_check(){
	if(!is_file("/etc/init.d/rsyslog")){return;}
	$f=explode("\n",@file_get_contents("/etc/init.d/rsyslog"));
	while (list ($index, $ligne) = each ($f) ){
		if(preg_match("#Provides:\s+mysql#", $ligne)){
			shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.initslapd.php --rsyslogd-init >/dev/null 2>&1");
			return;
		}
	}
}


function auth_tail(){
	if(!is_file("/usr/share/artica-postfix/exec.auth-tail.php")){return;}
	$EnableSSHD=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSSHD");
	if(strlen($GLOBALS["CLASS_USERS"]->LOCATE_AUTHLOG_PATH)==0){return;}
	if(!is_numeric($EnableSSHD)){$EnableSSHD=1;}
	if($EnableSSHD==0){return;}



	$pid_path="/etc/artica-postfix/exec.auth-tail.php.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="[APP_ARTICA_AUTH_TAIL]";
	$l[]="service_name=APP_ARTICA_AUTH_TAIL";
	$l[]="master_version=".trim(@file_get_contents(dirname(__FILE__)."/VERSION"));
	$l[]="service_cmd=/etc/init.d/auth-tail";
	$l[]="service_disabled=1";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=squid";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_ARTICA_AUTH_TAIL","auth-logger");
		$l[]="";return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//==//========================================================================================================================================================
function amavis(){
	$EnableStopPostfix=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStopPostfix");
	if(!is_numeric($EnableStopPostfix)){$EnableStopPostfix=0;}
	if($EnableStopPostfix==1){return;}
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("amavisd");
	if($bin_path==null){return null;}
	$pid_path="/var/spool/postfix/var/run/amavisd-new/amavisd-new.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$GLOBALS["DEBUG_LOGS"][]="$pid_path = $master_pid";

	
	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	if($DisableMessaging==1){$enabled=0;}

	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAmavisDaemon");
	$l[]="[AMAVISD]";
	$l[]="service_name=APP_AMAVISD_NEW";
	$l[]="master_version=".GetVersionOf("amavis");
	$l[]="service_cmd=/etc/init.d/amavis";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="family=postfix";
	$l[]="master_pid=$master_pid";
	$l[]="watchdog_features=1";
	 
	if($enabled==0){
		return implode("\n",$l);
		return;
	}
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
		exec("$pgrep -l -f \"amavisd\s+\(master\)\" 2>&1",$results);
		while (list ($num, $line) = each ($results) ){
			$GLOBALS["DEBUG_LOGS"][]="$pgrep = $line";
			if(preg_match("#([0-9]+)\s+amavis#", $line,$re)){
				$GLOBALS["DEBUG_LOGS"][]="$pgrep = PID:{$re[1]}";
				if($GLOBALS["CLASS_UNIX"]->process_exists($re[1])){$master_pid=$re[1];break;}
			}
		}
	}
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$GLOBALS["CLASS_UNIX"]->send_email_events("Amavisd-new stopped (watchdog)",
			"Artica will try to start it\n".@implode("\n", $GLOBALS["DEBUG_LOGS"]),"postfix");
		unset($GLOBALS["DEBUG_LOGS"]);
		WATCHDOG("APP_AMAVISD_NEW","amavis");
		$l[]="";return implode("\n",$l);
		return;
	}
	unset($GLOBALS["DEBUG_LOGS"]);
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	AmavisWatchdog();

	return implode("\n",$l);return;

}
//========================================================================================================================================================
function amavis_milter(){
	$EnableStopPostfix=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStopPostfix");
	if(!is_numeric($EnableStopPostfix)){$EnableStopPostfix=0;}
	if($EnableStopPostfix==1){return;}
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("amavisd-milter");
	$EnableAmavisDaemon=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAmavisDaemon");
	$EnableAmavisInMasterCF=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAmavisInMasterCF");
	$EnablePostfixMultiInstance=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfixMultiInstance");

	if($bin_path==null){return null;}
	$pid_path="/var/spool/postfix/var/run/amavisd-milter/amavisd-milter.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	if($EnableAmavisInMasterCF==1){$EnableAmavisDaemon=0;}
	if($EnablePostfixMultiInstance==1){$EnableAmavisDaemon=0;}
	
	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	if($DisableMessaging==1){$EnableAmavisDaemon=0;}

	$l[]="[AMAVISD_MILTER]";
	$l[]="service_name=APP_AMAVISD_MILTER";
	$l[]="master_version=".GetVersionOf("amavis");
	$l[]="service_cmd=amavis-milter";
	$l[]="service_disabled=$EnableAmavisDaemon";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=postfix";
	 
	if($EnableAmavisDaemon==0){
		return implode("\n",$l);
		return;
	}
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_AMAVISD_MILTER","amavis-milter");
		$l[]="";return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================
function lighttpd_version(){
	if(isset($GLOBALS["lighttpd_version"])){return $GLOBALS["lighttpd_version"];}
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("lighttpd");
	exec("$bin_path -V 2>&1",$results);
	while (list ($pid, $line) = each ($results) ){
		if(preg_match("#lighttpd.*?([0-9\.]+)#", $line,$re)){
			$GLOBALS["lighttpd_version"]=$re[1];
			return $GLOBALS["lighttpd_version"];
		}
	}
}
//========================================================================================================================================================
function iptables_version(){
	if(isset($GLOBALS["iptables_version"])){return $GLOBALS["iptables_version"];}
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("iptables");
	exec("$bin_path -V 2>&1",$results);
	while (list ($pid, $line) = each ($results) ){
		if(preg_match("#iptables v([0-9\.]+)#", $line,$re)){
			$GLOBALS["iptables_version"]=$re[1];
			return $GLOBALS["iptables_version"];
		}
	}
}
//========================================================================================================================================================



//========================================================================================================================================================
function apache_version(){
	if(isset($GLOBALS["apache_version"])){return $GLOBALS["apache_version"];}
	$GLOBALS["apache_version"]=$GLOBALS["CLASS_UNIX"]->LOCATE_APACHE_VERSION();
	return $GLOBALS["apache_version"];
}
//========================================================================================================================================================

function lighttpd(){
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("lighttpd");
	$EnableLighttpd=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLighttpd");
	$ApacheArticaEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ApacheArticaEnabled");
	$LighttpdArticaDisabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdArticaDisabled");
	$EnableArticaFrontEndToNGninx=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaFrontEndToNGninx");
	$EnableArticaFrontEndToApache=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaFrontEndToApache");
	if($ApacheArticaEnabled==1){$EnableLighttpd=0;}
	if(!is_numeric($LighttpdArticaDisabled)){$LighttpdArticaDisabled=0;}
	if(!is_numeric($EnableLighttpd)){$EnableLighttpd=1;}
	if(!is_numeric($EnableArticaFrontEndToNGninx)){$EnableArticaFrontEndToNGninx=0;}
	
	

	if($LighttpdArticaDisabled==1){$EnableLighttpd=0;}
	if(!$GLOBALS["CLASS_USERS"]->NGINX_INSTALLED){$EnableArticaFrontEndToNGninx=0;}
	if($EnableArticaFrontEndToNGninx==1){$EnableLighttpd=0;}
	
	
	
	$lighttpd_user=$GLOBALS["CLASS_UNIX"]->LIGHTTPD_USER();
	$master_version=lighttpd_version();
	$pid_path="/var/run/lighttpd/lighttpd.pid";
	$PatternPIDOF="$bin_path -f /etc/lighttpd/lighttpd.conf";
	
	if($EnableArticaFrontEndToApache==1){
		$lighttpd_user=$GLOBALS["CLASS_UNIX"]->APACHE_SRC_ACCOUNT();
		$master_version=apache_version();
		$pid_path="/var/run/artica-apache/apache.pid";
		$PatternPIDOF=$GLOBALS["CLASS_UNIX"]->LOCATE_APACHE_CTL()." -f /etc/artica-postfix/httpd.conf";
	}else{
		if($bin_path==null){return null;}
	}
	
	
	if($GLOBALS["VERBOSE"]){echo "EnableArticaFrontEndToApache:$EnableArticaFrontEndToApache\n";}
	if($GLOBALS["VERBOSE"]){echo "lighttpd-user:$lighttpd_user\n";}
	$array=stat("/usr/share/artica-postfix/logon.php");
	$activeuser=posix_getpwuid($array["uid"]);
	if($GLOBALS["VERBOSE"]){echo "Current:{$activeuser["name"]}\n";}
	if(trim($lighttpd_user)<>null){
		if($activeuser["name"]<>$lighttpd_user){
			$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["CHOWN"]} $lighttpd_user /usr/share/artica-postfix/* >/dev/null 2>&1 &");
			$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["CHOWN"]} -R $lighttpd_user /usr/share/artica-postfix/ressources >/dev/null 2>&1 &");
		}
	}
	
	
	
	$l[]="[LIGHTTPD]";
	$l[]="service_name=APP_LIGHTTPD";
	$l[]="master_version=$master_version";
	$l[]="service_cmd=/etc/init.d/artica-webconsole";
	$l[]="service_disabled=$EnableLighttpd";
	 
	$l[]="watchdog_features=1";
	$l[]="family=system";
	 
	if($EnableLighttpd==0){return implode("\n",$l);}
	 
	$APACHE_SRC_ACCOUNT=$GLOBALS["CLASS_UNIX"]->APACHE_SRC_ACCOUNT();
	$APACHE_SRC_GROUP=$GLOBALS["CLASS_UNIX"]->APACHE_SRC_GROUP();
	$GLOBALS["CLASS_UNIX"]->chown_func($APACHE_SRC_ACCOUNT,$APACHE_SRC_GROUP,"/var/lib/php5/*");
	$master_pid=trim(@file_get_contents($pid_path));
	if($master_pid==null){$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN($PatternPIDOF);}

	$l[]="pid_path=$pid_path";
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.lighttpd.php --start >/dev/null 2>&1 &");
		$l[]="";return implode("\n",$l);return;
	}else{
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.lighttpd.php --error500 >/dev/null 2>&1 &");
		
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================
function boa(){
	if(is_file("/usr/share/artica-postfix/bin/boa")){@unlink("/usr/share/artica-postfix/bin/boa");}
	if(is_file("/usr/share/artica-postfix/bin/boa.24")){@unlink("/usr/share/artica-postfix/bin/boa");}
	return; }
//========================================================================================================================================================
function clammilter(){
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("clamav-milter");
	$ClamavMilterEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamavMilterEnabled");
	if($ClamavMilterEnabled==null){$ClamavMilterEnabled=0;}
	if($bin_path==null){return null;}
	$pid_path="/var/spool/postfix/var/run/clamav/clamav-milter.pid";
	$master_pid=trim(@file_get_contents($pid_path));


	$l[]="[CLAMAV_MILTER]";
	$l[]="service_name=APP_CLAMAV_MILTER";
	$l[]="master_version=".GetVersionOf("clamav");
	$l[]="service_cmd=clammilter";
	$l[]="service_disabled=$ClamavMilterEnabled";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=postfix";
	 
	if($ClamavMilterEnabled==0){$l[]="";$l[]="";return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_CLAMAV_MILTER","clammilter");
		$l[]="";return implode("\n",$l);
		return;
	}
	 

	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================
function clamscan(){
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("clamscan");
	if($bin_path==null){return null;}
	$master_pid=1;


	$l[]="[CLAMSCAN]";
	$l[]="service_name=APP_CLAMSCAN";
	$l[]="master_version=".GetVersionOf("clamav");
	$l[]="service_cmd=";
	$l[]="service_disabled=1";
	$l[]="family=system";
	$l[]="pid_path=";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="";return implode("\n",$l);return;}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
function clamd_pid(){
	$unix=new unix();
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/clamav/clamd.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("clamd");
	return $GLOBALS["CLASS_UNIX"]->PIDOF($Masterbin);

}
function clamd_version(){
	if(isset( $GLOBALS["clamd_version"])){return  $GLOBALS["clamd_version"];}
	$unix=new unix();
	$Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("clamd");
	exec("$Masterbin -V 2>&1",$results);
	while (list ($i, $line) = each ($results) ){
		if(preg_match("#ClamAV\s+([0-9\.]+)\/#i", $line,$re)){
			$GLOBALS["clamd_version"]=$re[1];
			return $GLOBALS["clamd_version"];
		}
	}
}

//========================================================================================================================================================
function clamd(){
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("clamd");
	if(!is_file($bin_path)){return;}
	$EnableClamavDaemon=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavDaemon");
	if(!is_numeric($EnableClamavDaemon)){$EnableClamavDaemon=0;}
	
	$EnableClamavDaemonForced=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavDaemonForced");
	if(!is_numeric($EnableClamavDaemonForced)){$EnableClamavDaemonForced=0;}
	if($EnableClamavDaemonForced==1){$EnableClamavDaemon=1;}
	$CicapEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapEnabled");
	$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
	if($SQUIDEnable==1){if($CicapEnabled==1){$EnableClamavDaemon=1;}}
	
	
	$master_pid=clamd_pid();
	
		
	$l[]="[CLAMAV]";
	$l[]="service_name=APP_CLAMAV";
	$l[]="master_version=".clamd_version();
	$l[]="service_cmd=/etc/init.d/clamav-daemon";
	$l[]="service_disabled=$EnableClamavDaemon";
	$l[]="binpath=$bin_path";
	$l[]="family=system";
	$l[]="watchdog_features=1";
	$l[]="";

	if($EnableClamavDaemon==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/etc/init.d/clamav-daemon stop");
		}
	}

	if($EnableClamavDaemon==0){$l[]="";return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.clamd.php --start >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		$l[]="";
		return implode("\n",$l);
		return;
	}
	
	
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	
	
	$timeFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$timeTimeFile=$GLOBALS["CLASS_UNIX"]->file_time_min($timeFile);
	
	
	if($timeTimeFile>5){
		@unlink($timeFile);
		@file_put_contents($timeFile, time());
		$ClamavRefreshDaemonTime=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamavRefreshDaemonTime");
		$ClamavRefreshDaemonMemory=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamavRefreshDaemonMemory");
		if(!is_numeric($ClamavRefreshDaemonMemory)){$ClamavRefreshDaemonMemory=350;}	
		if(!is_numeric($ClamavRefreshDaemonTime)){$ClamavRefreshDaemonTime=60;}
		if($ClamavRefreshDaemonTime>2){
			$ClamavRefreshDaemonTime=$ClamavRefreshDaemonTime-1;
		}
		
		$rss=$GLOBALS["CLASS_UNIX"]->PROCESS_MEMORY($master_pid,false);
		$vm=$GLOBALS["CLASS_UNIX"]->PROCESS_CACHE_MEMORY($master_pid,false);
		$time=time();
		if(!is_dir("/var/log/artica-postfix/clamd-mem")){@mkdir("/var/log/artica-postfix/clamd-mem",0755,true);}
		$sql="('".date('Y-m-d H:i:s')."','$rss','$vm')";
		@file_put_contents("/var/log/artica-postfix/clamd-mem/$time", $sql);
		if($ClamavRefreshDaemonTime>10){	
			if($ClamavRefreshDaemonMemory>0){
				if($rss>$ClamavRefreshDaemonMemory){
					$GLOBALS["CLASS_UNIX"]->send_email_events("Reboot ClamAV Antivirus Daemon", " ClamAV Antivirus Daemon memory {$rss}MB exceed {$ClamavRefreshDaemonMemory}MB", "system");
					$cmd=trim("{$GLOBALS["nohup"]} /etc/init.d/clamav-daemon restart >/dev/null 2>&1 &");
					shell_exec2($cmd);
				}
			}
		
			$time=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($master_pid);
			if($time>$ClamavRefreshDaemonTime){
				$GLOBALS["CLASS_UNIX"]->send_email_events("Reboot ClamAV Antivirus Daemon", " ClamAV Antivirus Daemon TTL {$time} minutes exceed {$ClamavRefreshDaemonTime} minutes", "system");
				events("Reboot clamd daemon");
				$cmd=trim("{$GLOBALS["nohup"]} /etc/init.d/clamav-daemon restart >/dev/null 2>&1 &");
				shell_exec2($cmd);
			}
			
		}

	}
	return implode("\n",$l);return;

}
//========================================================================================================================================================

function NetAdsWatchdog(){

	$GLOBALS["PHP5"]=LOCATE_PHP5_BIN2();
	$EnableSambaActiveDirectory=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSambaActiveDirectory");
	if(!is_numeric($EnableSambaActiveDirectory)){return;}
	if($EnableSambaActiveDirectory<>1){return;}
	$net=$GLOBALS["CLASS_UNIX"]->LOCATE_NET_BIN_PATH();
	if(!is_file($net)){return;}
	exec("$net ads info 2>&1",$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#^(.+?):(.+)#",trim($line),$re)){
			events($line,__FUNCTION__,__LINE__);
			$array[trim($re[1])]=trim($re[2]);
		}
	}

	$log=@implode("\n",$results);
	unset($results);
	if($array["KDC server"]==null){
		exec("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.samba.php --build 2>&1",$results);

		$text="Artica Watchdog has detected an unlinked AD connection.:
		$log
		This is the result of re-connect operation:
		".@implode("\n",$results);

		$GLOBALS["CLASS_UNIX"]->send_email_events(
		"Connection to Active Directory Failed (Action reconnect)",
		$text,
		"system"
		
		);
	}

}
//========================================================================================================================================================

function ipsec_init(){
	if(is_file("/etc/init.d/ipsec")){return "/etc/init.d/ipsec";}
}

function ipsec_pid_path(){
	if(is_file("/var/run/charon.pid")){return "/var/run/charon.pid";}
}

function ipsec_binpath(){
	if(is_file("/usr/lib/ipsec/charon")){return "/usr/lib/ipsec/charon";}
}


function iptables(){iptables_tasks();}

function ipsec(){
	if(!$GLOBALS["CLASS_USERS"]->IPSEC_INSTALLED){return;}
	$bin_path=ipsec_binpath();
	if(!is_file($bin_path)){return;}
	$EnableIPSEC=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableIPSEC");
	if(!is_numeric($EnableIPSEC)){$EnableIPSEC=0;}
	$pid_path=ipsec_pid_path();
	$master_pid=trim(@file_get_contents($pid_path));
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin_path);}
	
	$l[]="[IPSEC]";
	$l[]="service_name=APP_IPSEC";
	$l[]="master_version=0.00";
	$l[]="service_cmd=";
	$l[]="service_disabled=$EnableIPSEC";
	$l[]="pid_path=$pid_path";
	$l[]="family=system";
	$l[]="watchdog_features=1";

	$l[]="";

	if($EnableIPSEC==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			$init=ipsec_init();
			if(is_file($init))
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("$init stop");
		}
	}

	if($EnableIPSEC==0){$l[]="";return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		//WATCHDOG("APP_FRESHCLAM","freshclam");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";


	return implode("\n",$l);return;	
}
function freshclam(){
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("freshclam");
	$EnableFreshClam=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFreshClam");
	$EnableClamavDaemon=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavDaemon");
	if($bin_path==null){return null;}
	$pid_path=GetVersionOf("freshclam-pid");
	$master_pid=trim(@file_get_contents($pid_path));
	$EnableClamavDaemon=$GLOBALS["CLASS_SOCKETS"]->EnableClamavDaemon();
	if(!is_numeric($EnableClamavDaemon)){$EnableClamavDaemon=0;}
	if(!is_numeric($EnableFreshClam)){$EnableFreshClam=0;}	
	
	
	if($GLOBALS["VERBOSE"]){
		echo "EnableClamavDaemon = $EnableClamavDaemon\n";
		echo "EnableFreshClam = $EnableFreshClam\n";
	}
	
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin_path);
	}

	

	$l[]="[FRESHCLAM]";
	$l[]="service_name=APP_FRESHCLAM";
	$l[]="master_version=".clamd_version();
	$l[]="service_cmd=freshclam";
	$l[]="service_disabled=$EnableFreshClam";
	$l[]="pid_path=$pid_path";
	$l[]="family=system";
	$l[]="watchdog_features=1";
	$l[]="";

	if($EnableClamavDaemon==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			if(!$GLOBALS["DISABLE_WATCHDOG"]){
				$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.freshclam.php --stop >/dev/null 2>&1 &");
				shell_exec2($cmd);
			}
		}
	}

	if($EnableClamavDaemon==1){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			$l[]=GetMemoriesOf($master_pid);
			$l[]="";
			return implode("\n",$l);
		}
		
		if(!system_is_overloaded(basename(__FILE__))){
			$TimeEx=$GLOBALS["CLASS_UNIX"]->file_time_min("/var/run/clamav/scheduled.time");
			if($TimeEx>120){
				$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.freshclam.php --exec >/dev/null 2>&1 &");
				shell_exec2($cmd);
			}
		}
		
		return implode("\n",$l);
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			if(!$GLOBALS["DISABLE_WATCHDOG"]){
				$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.freshclam.php --start >/dev/null 2>&1 &");
				shell_exec2($cmd);
			}
		}
		$l[]="";
		return implode("\n",$l);
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";


	return implode("\n",$l);return;

}
//========================================================================================================================================================
function retranslator_httpd(){




	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("lighttpd");
	$RetranslatorHttpdEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RetranslatorHttpdEnabled");
	if($bin_path==null){return null;}
	$pid_path="/var/run/lighttpd/lighttpd-retranslator.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	if($RetranslatorHttpdEnabled==null){$RetranslatorHttpdEnabled=0;}
	if($RetranslatorHttpdEnabled==0){return ;}

	$l[]="[KRETRANSLATOR_HTTPD]";
	$l[]="service_name=APP_KRETRANSLATOR_HTTPD";
	$l[]="master_version=".GetVersionOf("lighttpd-version");
	$l[]="service_cmd=retranslator";
	$l[]="service_disabled=$RetranslatorHttpdEnabled";
	$l[]="family=system";
	$l[]="pid_path=$pid_path";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="";return implode("\n",$l);return;}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================
function spamassassin_milter(){
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("spamass-milter");
	$SpamAssMilterEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SpamAssMilterEnabled");
	if($SpamAssMilterEnabled==null){$SpamAssMilterEnabled=0;}
	if($GLOBALS["CLASS_USERS"]->AMAVIS_INSTALLED){
		$EnableAmavisDaemon=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAmavisDaemon");
		if($EnableAmavisDaemon==1){$SpamAssMilterEnabled=0;}
	}
	if($bin_path==null){return null;}
	$pid_path="/var/run/spamass/spamass.pid";
	$master_pid=trim(@file_get_contents($pid_path));


	$l[]="[SPAMASS_MILTER]";
	$l[]="service_name=APP_SPAMASS_MILTER";
	$l[]="master_version=".GetVersionOf("spamassmilter-version");
	$l[]="service_cmd=spamd";
	$l[]="service_disabled=$SpamAssMilterEnabled";
	$l[]="pid_path=$pid_path";
	$l[]="family=postfix";
	
	$mem=$GLOBALS["CLASS_UNIX"]->TOTAL_MEMORY_MB();
	if($mem<1500){
		$SpamAssMilterEnabled=0;
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SpamAssMilterEnabled","0");
	}
	
	if($SpamAssMilterEnabled==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/artica-postfix stop spamd");}
		return implode("\n",$l);
	}	
	
	
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="";return implode("\n",$l);return;}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================
function spamassassin(){
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("spamd");
	$SpamdEnabled=GetVersionOf("spamass-enabled");

	if($GLOBALS["CLASS_USERS"]->AMAVIS_INSTALLED){
		$EnableAmavisDaemon=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAmavisDaemon");
		if($SpamdEnabled==1){$SpamdEnabled=0;}
	}
	
	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	if($DisableMessaging==1){$SpamdEnabled=0;}

	if(!is_numeric($SpamdEnabled)){$SpamdEnabled=0;}
	if($bin_path==null){return null;}
	$pid_path="/var/run/spamd.pid";
	$master_pid=trim(@file_get_contents($pid_path));


	$l[]="[SPAMASSASSIN]";
	$l[]="service_name=APP_SPAMASSASSIN";
	$l[]="master_version=".GetVersionOf("spamass");
	$l[]="service_cmd=spamd";
	$l[]="service_disabled=$SpamdEnabled";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=postfix";
	 
	if($SpamdEnabled==0){$l[]="";return implode("\n",$l);return;}
	 
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_SPAMASSASSIN","spamd");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================
function tomcat(){
	if(!$GLOBALS["CLASS_USERS"]->TOMCAT_INSTALLED){return;}

	$TomcatEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("TomcatEnable");
	if(!is_numeric($TomcatEnable)){$TomcatEnable=1;}
	if($GLOBALS["CLASS_USERS"]->OPENEMM_INSTALLED){
		$OpenEMMEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenEMMEnable");
		if(!is_numeric($OpenEMMEnable)){$OpenEMMEnable=1;}
		if($OpenEMMEnable==1){$TomcatEnable=0;}
	}

	$pid_path="/opt/openemm/tomcat/temp/tomcat.pid";
	$master_pid=trim(@file_get_contents($pid_path));


	$l[]="[APP_TOMCAT]";
	$l[]="service_name=APP_TOMCAT";
	$l[]="master_version=".$GLOBALS["CLASS_USERS"]->TOMCAT_VERSION;
	$l[]="service_cmd=spamd";
	$l[]="service_disabled=$TomcatEnable";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=web";
	 
	if($TomcatEnable==0){$l[]="";return implode("\n",$l);return;}
	 
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_TOMCAT","tomcat");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}

function cgroups(){
	if(!$GLOBALS["CLASS_USERS"]->CGROUPS_INSTALLED){return;}
	$cgrulesengd=$GLOBALS["CLASS_UNIX"]->find_program("cgrulesengd");
	if(!is_file($cgrulesengd)){return;}
	$cgroupsEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("cgroupsEnabled");
	if(!is_numeric($cgroupsEnabled)){$cgroupsEnabled=0;}

	$l[]="[APP_CGROUPS]";
	$l[]="service_name=APP_CGROUPS";
	$l[]="master_version=0.0";
	$l[]="service_disabled=$cgroupsEnabled";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	$l[]="service_cmd=cgroups";
	 
	if($cgroupsEnabled==0){$l[]="";return implode("\n",$l);return;}
	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($cgrulesengd);
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_CGROUPS","cgroups");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;
	 



}


function openemm(){
	if(!$GLOBALS["CLASS_USERS"]->OPENEMM_INSTALLED){return;}
	$OpenEMMEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenEMMEnable");
	if(!is_numeric($OpenEMMEnable)){$OpenEMMEnable=1;}


	$l[]="[APP_OPENEMM]";
	$l[]="service_name=APP_OPENEMM";
	$l[]="master_version=".$GLOBALS["CLASS_USERS"]->OPENEMM_VERSION;
	$l[]="service_cmd=spamd";
	$l[]="service_disabled=$OpenEMMEnable";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=smtp";
	 
	if($OpenEMMEnable==0){$l[]="";return implode("\n",$l);return;}
	 
	$grep=$GLOBALS["CLASS_UNIX"]->find_program("grep");
	$ps=$GLOBALS["CLASS_UNIX"]->find_program("ps");
	$awk=$GLOBALS["CLASS_UNIX"]->find_program("awk");
	$cmd="$ps -eo pid,command|$grep -E \"\/home\/openemm.*?org\.apache\.catalina\"|$grep -v grep|$awk '{print $1}' 2>&1";
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	exec($cmd,$results);
	$master_pid=trim(@implode("", $results));
	 
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_OPENEMM","openemm");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
function openemm_sendmail(){
	if(!$GLOBALS["CLASS_USERS"]->OPENEMM_INSTALLED){return;}
	$OpenEMMEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenEMMEnable");
	if(!is_numeric($OpenEMMEnable)){$OpenEMMEnable=1;}
	if(!is_file("/home/openemm/sendmail/sbin/sendmail")){return;}


	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/home/openemm/sendmail/run/sendmail.pid");

	$l[]="[APP_OPENEMM_SENDMAIL]";
	$l[]="service_name=APP_OPENEMM_SENDMAIL";
	$l[]="master_version=".$GLOBALS["CLASS_USERS"]->OPENEMM_SENDMAIL_VERSION;
	$l[]="service_cmd=smtp";
	$l[]="service_disabled=$OpenEMMEnable";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=smtp";
	 
	if($OpenEMMEnable==0){$l[]="";return implode("\n",$l);return;}
	 
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_OPENEMM_SENDMAIL","openemm-sendmail");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}



function iscsi_pid_path(){
	if(is_file("/var/run/ietd.pid")){return "/var/run/ietd.pid";}
	if(is_file("/var/run/iscsi_trgt.pid")){return "/var/run/iscsi_trgt.pid";}

}
 
function iscsi(){
	if(!$GLOBALS["CLASS_USERS"]->ISCSI_INSTALLED){return;}
	$EnableISCSI=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableISCSI");
	if($EnableISCSI==null){$EnableISCSI=0;}
	$pid_path=iscsi_pid_path();
	$master_pid=trim(@file_get_contents($pid_path));
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("ietd");

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin_path);
	}



	$l[]="[APP_IETD]";
	$l[]="service_name=APP_IETD";
	$l[]="master_version=".GetVersionOf("ietd");
	$l[]="service_cmd=iscsi";
	$l[]="service_disabled=$EnableISCSI";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	if($EnableISCSI==0){$l[]="";return implode("\n",$l);return;}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_IETD","iscsi");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;


}
function smartd_version(){
	if(isset($GLOBALS["smartd_version"])){return $GLOBALS["smartd_version"];}
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("smartd");
	exec("$bin_path -V 2>&1",$results);
	if(preg_match("#release\s+([0-9\.]+)#",@implode("",$results),$re)){$GLOBALS["smartd_version"]=$re[1];return $re[1];}
}

function smartd(){
	if(!$GLOBALS["CLASS_USERS"]->SMARTMONTOOLS_INSTALLED){return;}
	$EnableSMARTDisk=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSMARTDisk");
	if(!is_numeric($EnableSMARTDisk)){$EnableSMARTDisk=1;}
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("smartd");
	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin_path);




	$l[]="[SMARTD]";
	$l[]="service_name=APP_SMARTMONTOOLS";
	$l[]="master_version=".smartd_version();
	$l[]="service_cmd=iscsi";
	$l[]="service_disabled=$EnableSMARTDisk";
	$l[]="pid_path=none";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	if($EnableSMARTDisk==0){$l[]="";return implode("\n",$l);return;}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_IETD","iscsi");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}

function postfwd2(){

	if(!$GLOBALS["CLASS_USERS"]->POSTFIX_INSTALLED){return;}
	if(is_file("/etc/artica-postfix/KASPERSKY_WEB_APPLIANCE")){return;}
	exec($GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.postfwd2.php --all-status",$results);
	return @implode("\n",$results);
}
function opendkim_version(){
	if(isset($GLOBALS["OPENDKIM_VERSION"])){return $GLOBALS["OPENDKIM_VERSION"];}

	$opendkim=$GLOBALS["CLASS_UNIX"]->find_program("opendkim");

	exec("$opendkim -V 2>&1",$results);
	while (list ($index, $line) = each ($results) ){
		if(!preg_match("#OpenDKIM Filter v([0-9\.]+)#", $line,$re)){
			if($GLOBALS["VERBOSE"]){echo "VERSION NO MATCH: \"$line\"\n";}
			continue;}
		$GLOBALS["OPENDKIM_VERSION"]=$re[1];
		return $GLOBALS["OPENDKIM_VERSION"];

	}

}


function watchdog_yorel(){
	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
	$kill=$GLOBALS["CLASS_UNIX"]->find_program("kill");
	if(!is_file($pgrep)){
		if($GLOBALS["VERBOSE"]){echo "pgrep, no such file\n";}
		return;
	}

	$cmd="$pgrep -f \"/usr/bin/perl /usr/share/artica-postfix/bin/install/rrd/yorel-create\" 2>&1";
	exec($cmd,$results_yorel);
	if($GLOBALS["VERBOSE"]){echo "$cmd ". count($results_yorel)." processes\n";}
	while (list ($num, $ligne) = each ($results_yorel) ){
		if(!preg_match("#^([0-9]+)#",$ligne,$re)){
			if($GLOBALS["VERBOSE"]){echo "$ligne no match #^([0-9]+)\s+#\n";}
			continue;
		}
		$time=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($re[1]);
		if($GLOBALS["VERBOSE"]){echo "PID:{$re[1]} -> $time minutes TTL\n";}
		if($time>3){
			events("Killing process {$re[1]}: $time minutes TTL",__FUNCTION__,__FILE__);
			$GLOBALS["CLASS_UNIX"]->KILL_PROCESS($re[1],9);
			
			if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
			exec($cmd,$kill_results);
				
		}
		if(isset($kill_results)){
			if(count($kill_results)>0){
				if($GLOBALS["VERBOSE"]){
					while (list ($num, $ligne) = each ($kill_results) ){
						echo $ligne."\n";
					}
				}
			}
		}

	}


	$cmd="$pgrep -l -f rrd/yorel-upd 2>&1";
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	$max=0;
	exec("$cmd",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#^([0-9]+)\s+#",$ligne,$re)){
			if($GLOBALS["VERBOSE"]){echo "PID:{$re[1]}\n";}
			if($GLOBALS["CLASS_UNIX"]->process_exists($re[1])){
				$time=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($re[1]);
				if($GLOBALS["VERBOSE"]){echo "PID:{$re[1]} -> $time minutes TTL\n";}

				if($time>3){
					events("Killing process {$re[1]}: $time minutes TTL",__FUNCTION__,__FILE__);
					shell_exec2("/bin/rm -rf /opt/artica/var/rrd/yorel/*");
					shell_exec2("/bin/rm -rf /opt/artica/share/www/system/rrd/*");
					$GLOBALS["CLASS_UNIX"]->KILL_PROCESS($re[1],9);
					
					continue;
				}else{
					if($GLOBALS["VERBOSE"]){echo "PID:{$re[1]} -> $time minutes TTL -> results=keep\n";}
				}


				if($max>1){
					events("No more than one process allowed",__FUNCTION__,__FILE__);
					$GLOBALS["CLASS_UNIX"]->KILL_PROCESS($re[1],9);
					
					shell_exec2("/bin/rm -rf /opt/artica/var/rrd/yorel/*");
					shell_exec2("/bin/rm -rf /opt/artica/share/www/system/rrd/*");
					continue;
				}
				$max++;
				events("Found process {$re[1]}: $time minutes TTL Process number $max",__FUNCTION__,__FILE__);

			}
		}else{
			if($GLOBALS["VERBOSE"]){echo "$ligne no match\n";}
		}
	}
}
//========================================================================================================================================================

function milter_dkim(){




	if(!$GLOBALS["CLASS_USERS"]->MILTER_DKIM_INSTALLED){return;}
	$EnableDKFilter=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDkimMilter");
	if(!is_numeric($EnableDKFilter)){$EnableDKFilter=0;}
	$DisconnectDKFilter=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisconnectDKFilter");
	if(!is_numeric($DisconnectDKFilter)){$DisconnectDKFilter=0;}


	$pid_path="/var/run/dkim-milter/dkim-milter.pid";
	$master_pid=trim(@file_get_contents($pid_path));


	$l[]="[APP_MILTER_DKIM]";
	$l[]="service_name=APP_MILTER_DKIM";
	$l[]="master_version=".GetVersionOf("milterdkim");
	$l[]="service_cmd=dkim-milter";
	$l[]="service_disabled=$EnableDKFilter";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=postfix";
	 
	if($EnableDKFilter==0){$l[]="";return implode("\n",$l);return;}
	 
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$binpath=$GLOBALS["CLASS_UNIX"]->find_program("dkim-filter");
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
	}
		
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){	
		if($DisconnectDKFilter==0){WATCHDOG("APP_MILTER_DKIM","dkim-milter");}
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================

function dropbox(){
	if(!$GLOBALS["CLASS_USERS"]->DROPBOX_INSTALLED){return;}
	$EnableDropBox=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDropBox");
	if($EnableDropBox==null){$EnableDropBox=0;}


	$pid_path="/root/.dropbox/dropbox.pid";
	$master_pid=trim(@file_get_contents($pid_path));


	$l[]="[APP_DROPBOX]";
	$l[]="service_name=APP_DROPBOX";
	$l[]="master_version=".GetVersionOf("dropbox");
	$l[]="service_cmd=dropbox";
	$l[]="service_disabled=$EnableDropBox";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=storage";
	 
	if($EnableDropBox==0){$l[]="";return implode("\n",$l);return;}
	 
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_DROPBOX","dropbox");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================
function arkeiad_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$line=exec("/opt/arkeia/bin/arktrans --version 2>&1");
	if(preg_match("#Backup\s+([0-9\.]+)#", $line,$re)){
		$GLOBALS[__FUNCTION__]=$re[1];
		return $GLOBALS[__FUNCTION__];
	}
}
//========================================================================================================================================================

//========================================================================================================================================================

function arkeiad(){
	if(!$GLOBALS["CLASS_USERS"]->APP_ARKEIA_INSTALLED){return;}
	$EnableArkeia=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArkeia");
	if($EnableArkeia==null){$EnableArkeia=0;}


	$pid_path="/opt/arkeia/arkeiad/arkeiad.pid";
	$master_pid=trim(@file_get_contents($pid_path));


	$l[]="[APP_ARKEIAD]";
	$l[]="service_name=APP_ARKEIAD";
	$l[]="master_version=".arkeiad_version();
	$l[]="service_cmd=arkeia";
	$l[]="service_disabled=$EnableArkeia";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=storage";
	 
	if($EnableArkeia==0){$l[]="";return implode("\n",$l);return;}
	 
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_ARKEIAD","arkeia");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================

function haproxy_version(){
	
	if(isset($GLOBALS["haproxy_version"])){return $GLOBALS["haproxy_version"];}
	$xr=$GLOBALS["CLASS_UNIX"]->find_program("haproxy");
	if(!is_file($xr)){return;}
	exec("$xr -v 2>&1",$results);
	while (list ($index, $line) = each ($results)){
		if(preg_match("#HA-Proxy version\s+([0-9\.\-a-z]+)\s+#", $line,$re)){$GLOBALS["haproxy_version"]=$re[1];break;}
	}
	return $GLOBALS["haproxy_version"];
}


function lms_version(){
	if(isset($GLOBALS["lms_version"])){return $GLOBALS["lms_version"];}
	$results=file("/var/opt/kaspersky/apps/1463");
	while (list ($index, $line) = each ($results)){
		if(preg_match("#version=.*?([0-9\.]+)#", $line,$re)){$GLOBALS["lms_version"]=$re[1];return $GLOBALS["lms_version"];}
	}
	
}

function klms_milter(){
	
	
	
	if(!$GLOBALS["CLASS_USERS"]->KLMS_INSTALLED){if($GLOBALS["VERBOSE"]){echo " Not installed...\n";}return;}
	
	$pid_path="/var/run/klms/klms-milter.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	$EnableKlms=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKlms");
	if(!is_numeric($EnableKlms)){$EnableKlms=1;}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$master_pid=trim($GLOBALS["CLASS_UNIX"]->PIDOF("/opt/kaspersky/klms/libexec/klms-milter"));
	}
	
	$l[]="[APP_KLMS_MILTER]";
	$l[]="service_name=APP_KLMS_MILTER";
	$l[]="master_version=".lms_version();
	$l[]="service_cmd=klms";
	$l[]="service_disabled=$EnableKlms";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=smtp";
	$mem=$GLOBALS["CLASS_UNIX"]->TOTAL_MEMORY_MB();
	if($mem<1500){
		$EnableKlms=0;
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableKlms","0");
	}
	 
	if($EnableKlms==0){
		$l[]="";return implode("\n",$l);
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/klms stop >/dev/null 2>&1 &");
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.klms.php --watchdog >/dev/null 2>&1 &");
		}
		return;
	}	
	
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$l[]="";
		return implode("\n",$l);
		
	}
		
	
	$l[]=$GLOBALS["CLASS_UNIX"]->GetMemoriesOf($master_pid,true);
	$l[]="";

	return implode("\n",$l);return;	
	
}

function klms_status(){
	if(!$GLOBALS["CLASS_USERS"]->KLMS_INSTALLED){if($GLOBALS["VERBOSE"]){echo " Not installed...\n";}return;}
	
	$pid_path="/var/run/klms/klms.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	$EnableKlms=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKlms");
	if(!is_numeric($EnableKlms)){$EnableKlms=1;}
	
	
	$l[]="[APP_KLMSS]";
	$l[]="service_name=APP_KLMSS";
	$l[]="master_version=".lms_version();
	$l[]="service_cmd=klms";
	$l[]="service_disabled=$EnableKlms";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=smtp";
	 
	$mem=$GLOBALS["CLASS_UNIX"]->TOTAL_MEMORY_MB();
	if($mem<1500){
		$EnableKlms=0;
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableKlms","0");
	}
	
	if($EnableKlms==0){
		$l[]="";
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/klms stop >/dev/null 2>&1 &");
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.klms.php --watchdog >/dev/null 2>&1 &");
		}
		return implode("\n",$l);
	}
	
	
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_KLMSS","klms");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;
}
function klmsdb_status(){
	if(!$GLOBALS["CLASS_USERS"]->KLMS_INSTALLED){return;}
	
	$pid_path="/var/opt/kaspersky/klms/postgresql/postmaster.pid";
	$f=file($pid_path);
	$EnableKlms=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKlms");
	if(!is_numeric($EnableKlms)){$EnableKlms=1;}
	$master_pid=$f[0];
	
	$l[]="[APP_KLMSDB]";
	$l[]="service_name=APP_KLMSDB";
	$l[]="master_version=".lms_version();
	$l[]="service_cmd=klmsdb";
	$l[]="service_disabled=$EnableKlms";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=smtp";
	 
	$mem=$GLOBALS["CLASS_UNIX"]->TOTAL_MEMORY_MB();
	if($mem<1500){
		$EnableKlms=0;
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableKlms","0");
	}
	
	if($EnableKlms==0){
		$l[]="";
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/klmsdb stop >/dev/null 2>&1 &");
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.klms.php --watchdog >/dev/null 2>&1 &");
		}
					
		return implode("\n",$l);
	}
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_KLMSDB","klmsdb");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;
}
//========================================================================================================================================================
function ftp_proxy(){
	$unix=new unix();
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("ftp-proxy");
	if(!is_file($bin)){return;}

	$master_pid=@file_get_contents('/var/run/ftp-proxy.pid');
	$EnableFTPProxy=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFTPProxy");
	if(!is_numeric($EnableFTPProxy)){$EnableFTPProxy=0;}


	$l[]="[APP_FTP_PROXY]";
	$l[]="service_name=APP_FTP_PROXY";
	$l[]="service_cmd=/etc/init.d/ftp-proxy";
	$l[]="master_version=".ftp_proxy_version();
	$l[]="service_disabled=$EnableFTPProxy";
	$l[]="pid_path=/var/run/ftp-proxy.pid";
	$l[]="watchdog_features=1";
	$l[]="family=network";

	if($EnableFTPProxy==0){$l[]="";return implode("\n",$l);return;}




	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin);
	}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.ftpproxy.php --start >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;
}

//========================================================================================================================================================
function ftp_proxy_version(){
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("ftp-proxy");
	if(!is_file($bin)){if($GLOBALS['VERBOSE']){echo "ftp-proxy -> no such file\n";}return;}
	exec("$bin -V 2>&1",$array);
	while (list ($pid, $line) = each ($array) ){
		if(preg_match("#version\s+([0-9\.\-]+)#i", $line,$re)){return $re[1];}
		if($GLOBALS['VERBOSE']){echo "ftp_proxy_version(), $line, not found \n";}
	}
}

function vde_all(){
	$files=$GLOBALS["CLASS_UNIX"]->DirFiles("/etc/init.d","virtualswitch");
	while (list ($num, $ligne) = each ($files) ){
		if(preg_match("#virtualswitch-(.+)#", $ligne,$re)){
			$f[]=vde_uniq($re[1])."\n";
		}
	}
	
	return @implode("\n", $f);
}

function vde_uniq($switch){
	$unix=new unix();
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("vde_switch");
	if(!is_file($bin)){return;}
	
	$switch_init="/etc/init.d/virtualswitch-$switch";
	if(!is_file($switch_init)){return;}
	$switch_pid="/var/run/switch-$switch.pid";
	$VirtualSwitchEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("VirtualSwitchEnabled{$switch}");
	if(!is_numeric($VirtualSwitchEnabled)){$VirtualSwitchEnabled=1;}
	
	$master_pid=@file_get_contents($switch_pid);
	
	
	$l[]="[VDE_$switch]";
	$l[]="service_name=virtual_switch";
	$l[]="service_cmd=$switch_init";
	$l[]="master_version=".vde_version();
	$l[]="service_disabled=$VirtualSwitchEnabled";
	$l[]="pid_path=$switch_pid";
	$l[]="watchdog_features=1";
	$l[]="family=network";
	if($VirtualSwitchEnabled==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.vde.php --stop-switch $switch >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		return implode("\n",$l).vde_hook_uniq($switch);
	}
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.vde.php --start-switch $switch >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		$l[]="";
		return implode("\n",$l).vde_hook_uniq($switch);
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	
	return implode("\n",$l).vde_hook_uniq($switch);
	
	
}
function vde_hook_uniq($switch){
	$unix=new unix();
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("vde_switch");
	if(!is_file($bin)){return;}

	$switch_init="/etc/init.d/virtualhook-$switch";
	if(!is_file($switch_init)){return;}
	$switch_pid="/var/run/switch{$switch}p.pid";
	$VirtualSwitchEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("VirtualSwitchEnabled{$switch}");
	if(!is_numeric($VirtualSwitchEnabled)){$VirtualSwitchEnabled=1;}

	$master_pid=@file_get_contents($switch_pid);


	$l[]="[VDHOOK_$switch]";
	$l[]="service_name=virtual_hook";
	$l[]="service_cmd=$switch_init";
	$l[]="master_version=".vde_version();
	$l[]="service_disabled=$VirtualSwitchEnabled";
	$l[]="pid_path=$switch_pid";
	$l[]="watchdog_features=1";
	$l[]="family=network";
	if($VirtualSwitchEnabled==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.vde.php --pcapplug-stop $switch >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		return implode("\n",$l);
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.vde.php --pcapplug-start $switch >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;


}
function vde_version(){
	if(isset($GLOBALS["vde_version"])){return $GLOBALS["vde_version"];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("vde_switch");
	if(!is_file($bin)){if($GLOBALS['VERBOSE']){echo "vde_switch -> no such file\n";}return;}
	exec("$bin -v 2>&1",$array);
	while (list ($pid, $line) = each ($array) ){
		if(preg_match("#VDE\s+([0-9\.]+)#i", $line,$re)){$GLOBALS["vde_version"]=$re[1];return $GLOBALS["vde_version"];}
		if($GLOBALS['VERBOSE']){echo "vde_switch(),  \"$line\", not found \n";}
	}
}
//========================================================================================================================================================





function haproxy(){
	if(!$GLOBALS["CLASS_USERS"]->HAPROXY_INSTALLED){return;}
	include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
	if(!class_exists("mysql")){return ;}
	$enabled=0;
	if(is_file("/etc/haproxy/haproxy.cfg")){
		$sql="SELECT COUNT(*) as tcount FROM haproxy WHERE enabled=1";
		$q=new mysql();
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));	
		if($ligne["tcount"]>0){$enabled=1;}
	}
	$pid_path="/var/run/haproxy.pid";
	

	
	$l[]="[APP_HAPROXY]";
	$l[]="service_name=APP_HAPROXY";
	$l[]="master_version=".haproxy_version();
	$l[]="service_cmd=/etc/init.d/haproxy";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=network";
	 
	if($enabled==0){$l[]="";return implode("\n",$l);return;}	
	
	
	
	if(is_file($pid_path)){
		$pids=explode("\n",@file_get_contents($pid_path));
		while (list ($index, $line) = each ($pids)){
			$line=str_replace("\r", "", $line);
			$line=str_replace("\n", "", $line);
			if(!is_numeric(trim($line))){continue;}
			if($GLOBALS["VERBOSE"]){echo "$pid_path = $line\n";}
			if($GLOBALS["CLASS_UNIX"]->process_exists($line)){
				$PPID=$GLOBALS["CLASS_UNIX"]->PPID_OF($line);
				if($GLOBALS["VERBOSE"]){echo "$line ->running PPID:$PPID\n";}
				$PIDX[trim($line)]=$line;
			}
		}
	}	
	
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($PPID)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.haproxy.php --start >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		$l[]="";
		return implode("\n",$l);
		
	}
	$l[]=GetMemoriesOf($PPID);
	$l[]="";

	return implode("\n",$l);return;
}

//========================================================================================================================================================

function php_fpm_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$bin=$GLOBALS["CLASS_UNIX"]->APACHE_LOCATE_PHP_FPM();
	if(!is_file($bin)){
		if($GLOBALS['VERBOSE']){echo "APACHE_LOCATE_PHP_FPM -> no such file\n";}
		return;
	}
	$array=array();
	if(is_file("/etc/artica-postfix/phpfpm_version.db")){
		$array=unserialize(@file_get_contents("/etc/artica-postfix/phpfpm_version.db"));
	}	
	$binMD5=md5_file($bin);
	if($binMD5<>$array["binMD5"]){
		$array["binMD5"]=$binMD5;
		exec("$bin -v 2>&1",$array);
		while (list ($pid, $line) = each ($array) ){
			if(preg_match("#^PHP\s+([0-9\.\-]+)#i", $line,$re)){
				$GLOBALS[__FUNCTION__]=$re[1];
				$array["binversion"]=$re[1];
				syslog_status("php5-FPM: v{$array["binversion"]} - $binMD5", "artica-status");
				@file_put_contents("/etc/artica-postfix/phpfpm_version.db", serialize($array));
				return $re[1];
			}
			if($GLOBALS['VERBOSE']){echo "php_fpm_version(), $line, not found \n";}
		}
	}
	
	$GLOBALS[__FUNCTION__]=$array["binversion"];
	return $GLOBALS[__FUNCTION__];
	
}
function spwanfcgi_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("spawn-fcgi");
	if(!is_file($bin)){
		if($GLOBALS['VERBOSE']){echo "spwanfcgi_version -> no such file\n";}
		return;}
		exec("$bin -h 2>&1",$array);
		while (list ($pid, $line) = each ($array) ){
			if(preg_match("#spawn-fcgi v([0-9\.\-]+)#i", $line,$re)){
					$GLOBALS[__FUNCTION__]=$re[1];
					return $re[1];
			}
			if($GLOBALS['VERBOSE']){echo "spwanfcgi_version(), $line, not found \n";}
		}
}
function FPM_PID(){
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file('/var/run/php5-fpm.pid');
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	$bin=$GLOBALS["CLASS_UNIX"]->APACHE_LOCATE_PHP_FPM();
	return $GLOBALS["CLASS_UNIX"]->PIDOF($bin);
}
//========================================================================================================================================================
function php_fpm(){
	$unix=new unix();
	$bin=$GLOBALS["CLASS_UNIX"]->APACHE_LOCATE_PHP_FPM();
	if(!is_file($bin)){
		if(!is_file("/etc/debian_version")){return;}
		syslog_status("php5-FPM: Not installed , installing php5-fpm", "artica-status");
		$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.apt-get.php --phpfpm-daemon >/dev/null 2>&1 &");
		shell_exec2($cmd);
		return;
	}
	
	$master_pid=FPM_PID();
	$EnablePHPFPM=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePHPFPM");
	$ZarafaApachePHPFPMEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaApachePHPFPMEnable");
	if(!is_numeric($ZarafaApachePHPFPMEnable)){$ZarafaApachePHPFPMEnable=0;}
	
	
	$EnableArticaApachePHPFPM=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaApachePHPFPM");
	if(!is_numeric($EnableArticaApachePHPFPM)){$EnableArticaApachePHPFPM=0;}
	
	$EnablePHPFPMFreeWeb=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePHPFPMFreeWeb");
	if(!is_numeric($EnablePHPFPMFreeWeb)){$EnablePHPFPMFreeWeb=0;}
	
	$EnablePHPFPMFrameWork=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePHPFPMFrameWork");
	if(!is_numeric($EnablePHPFPMFrameWork)){$EnablePHPFPMFrameWork=0;}
	
	
	if($EnableArticaApachePHPFPM==1){$EnablePHPFPM=1;}
	if($ZarafaApachePHPFPMEnable==1){$EnablePHPFPM=1;}
	if($EnablePHPFPMFreeWeb==1){$EnablePHPFPM=1;}
	if($EnablePHPFPMFrameWork==1){$EnablePHPFPM=1;}
	if($ZarafaApachePHPFPMEnable==1){$EnablePHPFPM=1;}
	if($EnableArticaApachePHPFPM==1){$EnablePHPFPM=1;}
	if(!is_numeric($EnablePHPFPM)){$EnablePHPFPM=0;}
	

	$l[]="[APP_PHPFPM]";
	$l[]="service_name=APP_PHPFPM";
	$l[]="master_version=".php_fpm_version();
	$l[]="service_disabled=$EnablePHPFPM";
	$l[]="pid_path=/var/run/php5-fpm.pid";
	$l[]="service_cmd=/etc/init.d/php5-fpm";
	$l[]="watchdog_features=1";
	$l[]="family=network";

	if($EnablePHPFPM==0){
		if(is_file("/etc/monit/conf.d/phpfpm.monitrc")){
			@unlink("/etc/monit/conf.d/phpfpm.monitrc");
			$GLOBALS["CLASS_UNIX"]->MONIT_RELOAD();
		}
		$l[]="";return implode("\n",$l);
		return;
	}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin);}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			syslog_status("php5-FPM: Not running starting php5-fpm", "artica-status");
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.php-fpm.php --start >/dev/null 2>&1 &");
			shell_exec2($cmd);
			}
			$l[]="";
			return implode("\n",$l);
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	
	if($EnableArticaApachePHPFPM==1){
		if(!$GLOBALS["CLASS_UNIX"]->is_socket("/var/run/php-fpm.sock")){
			syslog_status("/var/run/php-fpm.sock: no such file, restarting php5-FPM", "artica-status");
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.php-fpm.php --restart >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
	}
	
	if($EnablePHPFPMFreeWeb==1){
		if(!$GLOBALS["CLASS_UNIX"]->is_socket("/var/run/php-fpm-apache2.sock")){
			syslog_status("/var/run/php-fpm-apache2.sock: no such file, restarting php5-FPM", "artica-status");
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.php-fpm.php --restart >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
	}

	$zarafabin=$GLOBALS["CLASS_UNIX"]->find_program("zarafa-server");
	if(is_file($zarafabin)){
		if($ZarafaApachePHPFPMEnable==1){
			if(!$GLOBALS["CLASS_UNIX"]->is_socket("/var/run/php-fpm-zarafa.sock")){
				syslog_status("/var/run/php-fpm-zarafa.sock: no such file, restarting php5-FPM", "artica-status");
				$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.php-fpm.php --restart >/dev/null 2>&1 &");
				shell_exec2($cmd);
			}
		}
	}
	
	
	return implode("\n",$l);return;
}

//========================================================================================================================================================
function syslog_status($text){$file="artica-status";if(!function_exists('syslog')){return null;}openlog($file, LOG_PID | LOG_PERROR, LOG_LOCAL0);syslog(LOG_INFO, $text);closelog();}
//========================================================================================================================================================
function php_fcgi(){
	$unix=new unix();
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("spawn-fcgi");
	if(!is_file($bin)){
		if(!is_file("/etc/debian_version")){return;}
			$cmd="DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::=\"--force-confnew\" --force-yes --yes install spawn-fcgi";
			$cmd=trim("{$GLOBALS["NICE"]} $cmd >/dev/null 2>&1 &");
			shell_exec2($cmd);
			return;
	}

	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/spawn-fcgi.pid");
	$EnableSPAWNFCGI=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSPAWNFCGI");
	if(!is_numeric($EnableSPAWNFCGI)){$EnableSPAWNFCGI=1;}
	
	
	$EnablePHPFPM=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePHPFPM"));
	$EnablePHPFPMFrameWork=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePHPFPMFrameWork");
	$EnableArticaApachePHPFPM=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaApachePHPFPM");
	$EnablePHPFPMFreeWeb=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePHPFPMFreeWeb");
	$EnableFreeWeb=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFreeWeb");
	if(!is_numeric($EnablePHPFPMFrameWork)){$EnablePHPFPMFrameWork=0;}
	if(!is_numeric($EnableArticaApachePHPFPM)){$EnableArticaApachePHPFPM=0;}
	if(!is_numeric($EnablePHPFPMFreeWeb)){$EnablePHPFPMFreeWeb=0;}
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}
	if($EnableFreeWeb==0){$EnablePHPFPMFreeWeb=1;}
	
	if($EnablePHPFPM==1){
		if($EnablePHPFPMFrameWork==1){
			if($EnableArticaApachePHPFPM==1){
				if($EnablePHPFPMFreeWeb==1){
					$EnableSPAWNFCGI=0;
				}
			}
		}
	}

	if($EnableSPAWNFCGI==0){
		if($GLOBALS["CLASS_UNIX"]->is_socket("/var/run/php-fcgi.sock")){
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.php5-fcgi.php --stop >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
	}

		$l[]="[APP_SPWANFCGI]";
		$l[]="service_name=APP_SPWANFCGI";
		$l[]="master_version=".spwanfcgi_version();
		$l[]="service_disabled=$EnableSPAWNFCGI";
		$l[]="pid_path=/var/run/spawn-fcgi.pid";
		$l[]="watchdog_features=1";
		$l[]="family=network";

		if($EnableSPAWNFCGI==0){$l[]="";return implode("\n",$l);return;}
		if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin);}
		if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){if(!$GLOBALS["DISABLE_WATCHDOG"]){$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.php5-fcgi.php --start >/dev/null 2>&1 &");shell_exec2($cmd);}$l[]="";return implode("\n",$l);}
		$l[]=GetMemoriesOf($master_pid);
		$l[]="";

		if(!$GLOBALS["CLASS_UNIX"]->is_socket("/var/run/php-fcgi.sock")){
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.php5-fcgi.php --restart >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}

		return implode("\n",$l);return;
}

//========================================================================================================================================================





function arkwsd(){
	if(!$GLOBALS["CLASS_USERS"]->APP_ARKEIA_INSTALLED){return;}
	$EnableArkeia=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArkeia");
	if($EnableArkeia==null){$EnableArkeia=0;}


	$pid_path="/opt/arkeia/arkeiad/arkwsd.pid";
	$master_pid=trim(@file_get_contents($pid_path));


	$l[]="[APP_ARKWSD]";
	$l[]="service_name=APP_ARKWSD";
	$l[]="master_version=".arkeiad_version();
	$l[]="service_cmd=arkeia";
	$l[]="service_disabled=$EnableArkeia";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=storage";
	 
	if($EnableArkeia==0){$l[]="";return implode("\n",$l);return;}
	 
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_ARKWSD","arkeia");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}

function iptables_tasks(){
	
	$dirname=dirname(__FILE__);
	$EnableSpamhausDROPList=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSpamhausDROPList");
	if(!is_numeric($EnableSpamhausDROPList)){$EnableSpamhausDROPList=0;}
	
	
	$iptables=$GLOBALS["CLASS_UNIX"]->find_program("iptables");
	$cachefile="/etc/artica-postfix/IPTABLES_INPUT";
	$unix=new unix();
	$TimeFile=$GLOBALS["CLASS_UNIX"]->file_time_min($cachefile);
	if($TimeFile>30){
		@unlink($cachefile);
		shell_exec2("{$GLOBALS["nohup"]} $iptables -L --line-numbers -n >$cachefile 2>&1 &");
	}
	
	if($EnableSpamhausDROPList==1){
		$TimePath="/etc/artica-postfix/pids/exec.spamhausdrop.php.update.time";
		$TimeFile=$GLOBALS["CLASS_UNIX"]->file_time_min($TimePath);
		if($TimeFile>30){
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} $dirname/exec.spamhausdrop.php 2>&1 &");
		}
	}
	
	$TimePath="/etc/artica-postfix/settings/Daemons/SystemTotalSize";
	$TimeFile=$GLOBALS["CLASS_UNIX"]->file_time_min($TimePath);
	if($TimeFile>30){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} $dirname/exec.system-move.php --totalsize 2>&1 &");
	}
	
}


//========================================================================================================================================================
function virtualbox_webserv(){
	if(!$GLOBALS["CLASS_USERS"]->VIRTUALBOX_INSTALLED){return;}
	$EnableVirtualBox=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableVirtualBox");
	if($EnableVirtualBox==null){$EnableVirtualBox=1;}


	$pid_path="/var/run/virtualbox/vboxwebsrv.pid";
	$master_pid=trim(@file_get_contents($pid_path));


	$l[]="[APP_VIRTUALBOX_WEBSERVICE]";
	$l[]="service_name=APP_VIRTUALBOX_WEBSERVICE";
	$l[]="master_version=".GetVersionOf("virtualbox");
	$l[]="service_cmd=virtualbox-web";
	$l[]="service_disabled=$EnableVirtualBox";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=virtual";
	 
	if($EnableVirtualBox==0){$l[]="";return implode("\n",$l);return;}
	 
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_VIRTUALBOX_WEBSERVICE","virtualbox-web");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================
function crossroads(){
	if(!$GLOBALS["CLASS_USERS"]->crossroads_installed){return;}
	$EnableCrossRoads=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCrossRoads");
	if($EnableCrossRoads==null){$EnableCrossRoads=0;}
	
	shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.loadbalance.php --status --watchdog >/dev/null 2>&1 &");
	
	$MAIN=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CrossRoadsParams")));
	if(!is_array($MAIN["BACKENDS"])){$EnableCrossRoads=0;}
	$master_pid=trim($GLOBALS["CLASS_UNIX"]->PIDOF($GLOBALS["CLASS_UNIX"]->find_program("xr")));


	$l[]="[APP_CROSSROADS]";
	$l[]="service_name=APP_CROSSROADS";
	$l[]="master_version=".GetVersionOf("crossroads");
	$l[]="service_cmd=crossroads";
	$l[]="service_disabled=$EnableCrossRoads";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=network";
	 
	if($EnableCrossRoads==0){$l[]="";return implode("\n",$l);return;}
	 
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_CROSSROADS","crossroads");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================

function cron_pid(){
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/crond.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
		
	$cron=$GLOBALS["CLASS_UNIX"]->find_program("cron");
	return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN($cron);
}

function cron(){
	
	$master_pid=cron_pid();


	$l[]="[APP_CRON]";
	$l[]="service_name=APP_CRON";
	$l[]="master_version=1.0";
	$l[]="service_cmd=/etc/init.d/cron";
	$l[]="service_disabled=1";
	
	$l[]="watchdog_features=1";
	$l[]="family=system";

	


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			ToSyslog("Cron is not started -> run it");
			shell_exec("/etc/init.d/cron start");
		}
		$l[]="";
		return implode("\n",$l);
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================



function pptpd(){
	if(!$GLOBALS["CLASS_USERS"]->PPTPD_INSTALLED){return;}
	$EnablePPTPDVPN=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePPTPDVPN");
	if($EnablePPTPDVPN==null){$EnablePPTPDVPN=0;}
	$pid_path="/var/run/pptpd.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="[APP_PPTPD]";
	$l[]="service_name=APP_PPTPD";
	$l[]="master_version=".GetVersionOf("pptpd");
	$l[]="service_cmd=pptpd";
	$l[]="service_disabled=$EnablePPTPDVPN";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=network";
	if($EnablePPTPDVPN==0){$l[]="";return implode("\n",$l);return;}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_PPTPD","pptpd");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}
//========================================================================================================================================================
function apt_mirror(){
	if(!$GLOBALS["CLASS_USERS"]->APT_MIRROR_INSTALLED){return;}
	$EnableAptMirror=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAptMirror");
	if($EnableAptMirror==null){$EnableAptMirror=0;}
	$master_pid=trim($GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN($GLOBALS["CLASS_UNIX"]->find_program("apt-mirror")));

	$l[]="[APP_APT_MIRROR]";
	$l[]="service_name=APP_APT_MIRROR";
	$l[]="master_version=".GetVersionOf("apt-mirror");
	$l[]="service_cmd=apt-mirror";
	$l[]="service_disabled=$EnableAptMirror";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=0";
	$l[]="family=network";
	if($EnableAptMirror==0){$l[]="";return implode("\n",$l);return;}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}
//========================================================================================================================================================

function ddclient(){
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program("ddclient");
	if(!is_file($binpath)){return;}
	$EnableDDClient=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDDClient");
	if($EnableDDClient==null){$EnableDDClient=0;}
	$pid_path="/var/run/ddclient.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="[APP_DDCLIENT]";
	$l[]="service_name=APP_DDCLIENT";
	$l[]="master_version=".GetVersionOf("ddclient");
	$l[]="service_cmd=apt-mirror";
	$l[]="service_disabled=$EnableDDClient";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=network";
	if($EnableDDClient==0){$l[]="";return implode("\n",$l);return;}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_DDCLIENT","ddclient");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}
//========================================================================================================================================================

function APACHE_PID(){
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	$APACHE_PID_PATH=$GLOBALS["CLASS_UNIX"]->APACHE_PID_PATH();
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($APACHE_PID_PATH);
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}


	$LOCATE_APACHE_CONF_PATH=$GLOBALS["CLASS_UNIX"]->LOCATE_APACHE_CONF_PATH();
	$apache=$GLOBALS["CLASS_UNIX"]->APACHE_BIN_PATH();
	$pattern="$apache.*?-f $LOCATE_APACHE_CONF_PATH";
	$unix=new unix();
	$pgrep=$unix->find_program("pgrep");
	exec("$pgrep -l -f \"$pattern\" 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#pgrep#", $ligne)){continue;}
		if(!preg_match("#^([0-9]+)#", $ligne,$re)){continue;}
		$ppid=$GLOBALS["CLASS_UNIX"]->PPID_OF($re[1]);
		if($ppid==$re[1]){return $re[1];}
		return $ppid;
	}

}
//========================================================================================================================================================
function apachesrc(){
	$EnableFreeWeb=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFreeWeb");
	if(!is_numeric($EnableFreeWeb)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableFreeWeb",0);
		$EnableFreeWeb=0;
	}
	$pid_path=$GLOBALS["CLASS_UNIX"]->LOCATE_APACHE_PID_PATH();
	$binpath=$GLOBALS["CLASS_UNIX"]->LOCATE_APACHE_BIN_PATH();
	if(!is_file($pid_path)){$pid_path="/var/run/httpd/httpd.pid";}
	if(strlen($binpath)<5){return;}
	$EnableRemoteStatisticsAppliance=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	$master_pid=APACHE_PID();
	$TOTAL_MEMORY_MB=$GLOBALS["CLASS_UNIX"]->TOTAL_MEMORY_MB();
	$MonitConfig=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ApacheWatchdogMonitConfig")));
	if(!is_numeric($MonitConfig["watchdog"])){$MonitConfig["watchdog"]=1;}
	if(!is_numeric($MonitConfig["watchdogCPU"])){$MonitConfig["watchdogCPU"]=95;}
	if(!is_numeric($MonitConfig["watchdogMEM"])){$MonitConfig["watchdogMEM"]=1500;}
	if(!is_numeric($MonitConfig["watchdogTTL"])){$MonitConfig["watchdogTTL"]=1440;}

	$l[]="[APP_APACHE_SRC]";
	$l[]="service_name=APP_APACHE_SRC";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->LOCATE_APACHE_VERSION();
	$l[]="service_cmd=/etc/init.d/apache2";
	$l[]="service_disabled=$EnableFreeWeb";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=www";
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
	}

	if($TOTAL_MEMORY_MB<550){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableFreeWeb",0);
		$EnableFreeWeb=0;
	}
	
	if($EnableFreeWeb==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			shell_exec2("/etc/init.d/apache2 stop");
		}
		$l[]="";
		return implode("\n",$l);return;
	}



	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			apache_admin_mysql(0, "Apache Web service is not running [action=start]", null,__FILE__,__LINE__);
			shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.initslapd.php --apache >/dev/null 2>&1");
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/apache2 start >/dev/null 2>&1 &");
		}
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	
	
	if(!$GLOBALS["DISABLE_WATCHDOG"]){
		if($MonitConfig["watchdog"]==1){
			if($MonitConfig["watchdogTTL"]>5){
				$TTL=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($master_pid);
				if($TTL>$MonitConfig["watchdogTTL"]){
					apache_admin_mysql(1, "Apache Web service TTL {$TTL}Mn exceed {$MonitConfig["watchdogTTL"]}Mn [action=restart]", null,__FILE__,__LINE__);
					shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.freeweb.php --restart-maintenance >/dev/null 2>&1 &");
					shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.nginx.php --reload >/dev/null 2>&1 &");
				}
			}
		}
	}
	if(!$GLOBALS["DISABLE_WATCHDOG"]){
		$timefile="/etc/artica-postfix/pids/tests.ScanSize.time";
		$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min($timefile);
		if($time_file>15){
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.freeweb.php --ScanSize >/dev/null 2>&1 &");
		}
	}
	
	return implode("\n",$l);return;

}
//========================================================================================================================================================



function cluebringer(){
	if(!$GLOBALS["CLASS_USERS"]->CLUEBRINGER_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." CLUEBRINGER_INSTALLED = FALSE\n";}
		return;
	}
	$EnableCluebringer=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCluebringer");
	if($EnableCluebringer==null){$EnableCluebringer=0;}
	
	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	if($DisableMessaging==1){$EnableCluebringer=0;}
	
	$pid_path="/var/run/cbpolicyd.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="[APP_CLUEBRINGER]";
	$l[]="service_name=APP_CLUEBRINGER";
	$l[]="master_version=".GetVersionOf("cluebringer");
	$l[]="service_cmd=cluebringer";
	$l[]="service_disabled=$EnableCluebringer";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=postfix";
	if($EnableCluebringer==0){$l[]="";return implode("\n",$l);return;}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_CLUEBRINGER","cluebringer");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}
//========================================================================================================================================================
function sabnzbdplus(){

	if(!$GLOBALS["CLASS_USERS"]->APP_SABNZBDPLUS_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." APP_SABNZBDPLUS_INSTALLED = FALSE\n";}
		return;
	}
	$EnableSabnZbdPlus=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSabnZbdPlus");
	if($EnableSabnZbdPlus==null){$EnableSabnZbdPlus=0;}

	if($GLOBALS["VERBOSE"]){echo __FUNCTION__." EnableSabnZbdPlus = $EnableSabnZbdPlus\n";}
	if(is_file("/usr/share/sabnzbdplus/SABnzbd.py")){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("SABnzbd.py");
		$binary="SABnzbd.py";
	}else{
		$binary=$GLOBALS["CLASS_UNIX"]->find_program("sabnzbdplus");
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN($binary);
	}

	$l[]="[APP_SABNZBDPLUS]";
	$l[]="service_name=APP_SABNZBDPLUS";
	$l[]="master_version=".GetVersionOf("sabnzbdplus");
	$l[]="service_cmd=sabnzbdplus";
	$l[]="service_disabled=$EnableSabnZbdPlus";
	$l[]="pid_path=pidof $binary";
	$l[]="watchdog_features=1";
	$l[]="family=samba";
	if($EnableSabnZbdPlus==0){$l[]="";return implode("\n",$l);return;}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_SABNZBDPLUS","sabnzbdplus");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================
function greensql_version(){
	if(isset($GLOBALS["greensql_version"])){return $GLOBALS["greensql_version"];}
	$f=explode("\n", @file_get_contents("/usr/share/greensql-console/config.php"));
	while (list ($num, $ligne) = each ($f) ){
		if(preg_match("#version.+?([0-9\.]+)#", $ligne,$re)){
			$GLOBALS["greensql_version"]=$re[1];
			return $GLOBALS["greensql_version"];
		}else{
			if($GLOBALS["VERBOSE"]){echo "\"$ligne\" ->NO MATCH\n";}
		}
	}

}
//========================================================================================================================================================
function nscd(){
	if(!$GLOBALS["CLASS_USERS"]->NSCD_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." NSCD_INSTALLED = FALSE\n";}
		return;
	}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("nscd");
	$EnableNSCD=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNSCD");
	if(!is_numeric($EnableNSCD)){$EnableNSCD=0;}
	$pid_path="/var/run/nscd/nscd.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	$version=nscd_version($bin);
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin);}
	if($EnableNSCD==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){shell_exec2("/etc/init.d/nscd stop");}
	}


	$l[]="[APP_NSCD]";
	$l[]="service_name=APP_NSCD";
	$l[]="master_version=$version";

	$l[]="service_disabled=$EnableNSCD";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	if($EnableNSCD==0){$l[]="";return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.initslapd.php nscd");
		shell_exec2("/etc/init.d/nscd start");
		$l[]="";
		return implode("\n",$l);
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;


}
//========================================================================================================================================================
function exim4(){
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("exim4");
	if(!is_file($bin)){return;}
	
	
	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin);
	
	
	
	if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$cmd="{$GLOBALS["KILLBIN"]} -9 $master_pid >/dev/null 2>&1";
		echo " *****  *****  KILLING EXIM **** **** $cmd\n";
		shell_exec2($cmd);
	
		
	}
	return;


}
//========================================================================================================================================================
//========================================================================================================================================================
function conntrackd_version(){
	if(isset($GLOBALS["conntrackd_version"])){return $GLOBALS["conntrackd_version"];}
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("conntrackd");
	exec("$bin_path -v 2>&1",$results);
	while (list ($pid, $line) = each ($results) ){
		if(preg_match("#v([0-9\.]+)#", $line,$re)){
			$GLOBALS["conntrackd_version"]=$re[1];
			return $GLOBALS["conntrackd_version"];
		}
	}
}
//========================================================================================================================================================
function conntrackd(){
	if(!is_file("/etc/init.d/artica-postfix")){return;}
	if(!$GLOBALS["CLASS_USERS"]->ARPD_INSTALLED){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." ARPD_INSTALLED = FALSE\n";}return;}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("conntrackd");
	$EnableConntrackd=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableConntrackd");
	if(!is_numeric($EnableConntrackd)){$EnableConntrackd=0;}
	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin,true);
	if($EnableConntrackd==0){if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){shell_exec2("/etc/init.d/conntrackd stop");}}
	
	$l[]="[APP_CONNTRACKD]";
	$l[]="service_name=APP_CONNTRACKD";
	$l[]="master_version=".conntrackd_version();;
	$l[]="service_disabled=$EnableConntrackd";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	$l[]="service_cmd=/etc/init.d/conntrackd";
	if($EnableConntrackd==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			$cmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}/etc/init.d/conntrackd stop >/dev/null 2>&1 &";
			events("$cmd",__FUNCTION__,__LINE__);
			shell_exec2($cmd);
				
		}
		$l[]="";return implode("\n",$l);
		return;
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.conntrackd.php --start >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		return implode("\n",$l);
	}else{
		if($EnableConntrackd==0){
			shell_exec2("{$GLOBALS["KILLBIN"]} -9 $master_pid >/dev/null 2>&1");
		}
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);
}
//========================================================================================================================================================

function arpd(){
	if(!is_file("/etc/init.d/artica-postfix")){return;}
	if(!$GLOBALS["CLASS_USERS"]->ARPD_INSTALLED){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." ARPD_INSTALLED = FALSE\n";}return;}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("arpd");
	$EnableArpDaemon=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArpDaemon");	
	if(!is_numeric($EnableArpDaemon)){$EnableArpDaemon=1;}
	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin,true);
	if($EnableArpDaemon==0){if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){shell_exec2("/etc/init.d/arpd stop");}}	
	if($GLOBALS["CLASS_USERS"]->LIGHT_INSTALL){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableArpDaemon",0);$EnableArpDaemon=0;}
	if($GLOBALS["CLASS_UNIX"]->MEM_TOTAL_INSTALLEE()<624288){$EnableArpDaemon=0;}
	
	$l[]="[APP_ARPD]";
	$l[]="service_name=APP_ARPD";
	$l[]="master_version=No";
	$l[]="service_disabled=$EnableArpDaemon";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	if($EnableArpDaemon==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			$cmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}/etc/init.d/arpd stop >/dev/null 2>&1 &"; 
			events("$cmd",__FUNCTION__,__LINE__);
			shell_exec2($cmd);
			
		}
		$l[]="";return implode("\n",$l);
		return;
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_ARPD","arpd");
		$l[]="";
		return implode("\n",$l);
	}else{
		if($EnableArpDaemon==0){
			shell_exec2("{$GLOBALS["KILLBIN"]} -9 $master_pid >/dev/null 2>&1");
		}
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);
}
//========================================================================================================================================================
function netatalk_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("afpd");
	exec("$bin -V 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#afpd\s+([0-9\.]+)#", $line)){$GLOBALS[__FUNCTION__]=$re[1];return $re[1];}
	}
}
//========================================================================================================================================================
function avahi_daemon_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("avahi-daemon");
	exec("$bin -V 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#avahi-daemon\s+([0-9\.]+)#", $line)){$GLOBALS[__FUNCTION__]=$re[1];return $re[1];}
	}
}
//========================================================================================================================================================

function udevd_daemon_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("udevd");
	exec("$bin --version 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#^([0-9\.]+)#", $line)){$GLOBALS[__FUNCTION__]=$re[1];return $re[1];}
	}	
	
}
//========================================================================================================================================================
function dbus_daemon_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("dbus-daemon");
	exec("$bin --version 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#Bus Daemon\s+([0-9\.]+)#", $line)){$GLOBALS[__FUNCTION__]=$re[1];return $re[1];}
	}	
	
}
//========================================================================================================================================================


function memcached_daemon_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("memcached");
	exec("$bin -h 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#memcached\s+([0-9\.]+)#", $line,$re)){$GLOBALS[__FUNCTION__]=$re[1];return $re[1];}
	}
}
//========================================================================================================================================================
function monit_daemon_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("monit");
	if($GLOBALS["VERBOSE"]){echo "monit_daemon_version():: Monit binary: $bin\n";}
	exec("$bin -V 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#monit version\s+([0-9\.]+)#", $line,$re)){$GLOBALS[__FUNCTION__]=$re[1];return $re[1];}
	}
}
//========================================================================================================================================================


function netatalk(){
	if(!$GLOBALS["CLASS_USERS"]->NETATALK_INSTALLED){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." ARPD_INSTALLED = FALSE\n";}return;}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("afpd");
	$EnableArpDaemon=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetatalkEnabled");	
	if(!is_numeric($NetatalkEnabled)){$NetatalkEnabled=1;}
	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin,true);
	if($NetatalkEnabled==0){if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){shell_exec2("/etc/init.d/netatalk stop");}}	
	
	$l[]="[APP_NETATALK]";
	$l[]="service_name=APP_NETATALK";
	$l[]="master_version=".netatalk_version();
	$l[]="service_disabled=$NetatalkEnabled";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	if($NetatalkEnabled==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			$cmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}/etc/init.d/artica-postfix stop netatalk >/dev/null 2>&1 &"; 
			events("$cmd",__FUNCTION__,__LINE__);
			shell_exec2($cmd);
			
		}
		$l[]="";return implode("\n",$l);
		return;
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_NETATALK","netatalk");
		$l[]="";
		return implode("\n",$l);
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);
}
//========================================================================================================================================================
function avahi_daemon(){
	
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("avahi-daemon");
	$NetatalkEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetatalkEnabled");	
	if(!is_numeric($NetatalkEnabled)){$NetatalkEnabled=1;}
	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin,true);
	if($NetatalkEnabled==0){if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){shell_exec2("/etc/init.d/netatalk stop");}}	
	if(!$GLOBALS["CLASS_USERS"]->NETATALK_INSTALLED){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." ARPD_INSTALLED = FALSE\n";}$NetatalkEnabled=0;}
	
	$l[]="[APP_AVAHI]";
	$l[]="service_name=APP_AVAHI";
	$l[]="master_version=".avahi_daemon_version();
	$l[]="service_disabled=$NetatalkEnabled";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if($NetatalkEnabled==0){
			
			$kill=$GLOBALS["CLASS_UNIX"]->find_program("kill");
			if($GLOBALS["VERBOSE"]){echo "avahi_daemon:: Killing PID $master_pid\n";}
			$GLOBALS["CLASS_UNIX"]->KILL_PROCESS($master_pid,9);
			
		}
	}
	
	if($NetatalkEnabled==0){$l[]="";return implode("\n",$l);return;}
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_AVAHI","netatalk");
		$l[]="";
		return implode("\n",$l);
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);
}
//========================================================================================================================================================
function udevd_daemon(){
	
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("udevd");
	if(!is_file($bin)){return;}
	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin,true);
		
	
	$l[]="[APP_UDEVD]";
	$l[]="service_name=APP_UDEVD";
	$l[]="master_version=".udevd_daemon_version();
	$l[]="service_disabled=1";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$l[]="";
		return implode("\n",$l);
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);
}
//========================================================================================================================================================
function dbus_daemon(){
	
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("dbus-daemon");
	if(!is_file($bin)){return;}
	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin,true);
		
	
	$l[]="[APP_DBUS]";
	$l[]="service_name=APP_DBUS";
	$l[]="master_version=".dbus_daemon_version();
	$l[]="service_disabled=1";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$l[]="";
		return implode("\n",$l);
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);
}
//========================================================================================================================================================




function memcached(){
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("memcached");
	if(!is_file($bin)){
		events("memcached not installed",__FUNCTION__,__LINE__);
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." MEMCACHED_INSTALLED = FALSE\n";}
		return;
	}
	
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("memcached");
	$EnableMemcached=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMemcached");	
	if(!is_numeric($EnableMemcached)){$EnableMemcached=1;}
	
	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/memcached.pid");
	events("master pid = $master_pid",__FUNCTION__,__LINE__);
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin,true);
		events("master pid = $master_pid after pidof($bin)",__FUNCTION__,__LINE__);
	}
	
	
	
	if($EnableMemcached==0){if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		events("Stopping memcached...",__FUNCTION__,__LINE__);
		shell_exec2("/etc/init.d/artica-memcache stop");
		}
	}	
	
	$l[]="[APP_MEMCACHED]";
	$l[]="service_name=APP_MEMCACHED";
	$l[]="master_version=".memcached_daemon_version();
	$l[]="service_disabled=$EnableMemcached";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	$l[]="service_cmd=/etc/init.d/artica-memcache";
	
	if($EnableMemcached==0){
		$l[]="";return implode("\n",$l);
		return;
	}
	

	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		events("Not running pid `$master_pid`...",__FUNCTION__,__LINE__);
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} /etc/init.d/artica-memcache start");
		WATCHDOG("APP_MEMCACHED","memcached");
		$l[]="";
		return implode("\n",$l);
	}
	
	
	if(!$GLOBALS["CLASS_UNIX"]->is_socket("/var/run/memcached.sock")){
		ToSyslog("\"/var/run/memcached.sock\" no such socket",__FUNCTION__,__LINE__);
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} /etc/init.d/artica-memcache restart");
	}else{
		@chmod(0777,"/var/run/memcached.sock");
	}
	
	
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);
}
//========================================================================================================================================================
function monit(){
	if(!$GLOBALS["CLASS_USERS"]->MONIT_INSTALLED){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." MEMCACHED_INSTALLED = FALSE\n";}return;}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("monit");
	$EnableDaemon=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMonit");	
	if(!is_numeric($EnableDaemon)){$EnableDaemon=1;}
	$unix=new unix();
	$master_pid=$unix->get_pid_from_file("/var/run/monit/monit.pid");
	$cache_file="/usr/share/artica-postfix/ressources/logs/web/monit.status.all";
	
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin,true);
	}
	
	if($EnableDaemon==0){if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		shell_exec2("/etc/init.d/monit stop");}}	
	
	$l[]="[APP_MONIT]";
	$l[]="service_name=APP_MONIT";
	$l[]="master_version=".monit_daemon_version();
	$l[]="service_disabled=$EnableDaemon";
	$l[]="watchdog_features=1";
	$l[]="service_cmd=/etc/init.d/monit";
	$l[]="family=system";
	
	if($EnableDaemon==0){$l[]="";return implode("\n",$l);return;}
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if($GLOBALS["VERBOSE"]){echo " **** NO RUNNING ****\n";}
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("/etc/init.d/monit start");
		}
		
		$l[]="";
		return implode("\n",$l);
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	$monit=new monit_unix();
	$monit->WAKEUP();
	$time=$GLOBALS["CLASS_UNIX"]->file_time_min($cache_file);
	if($GLOBALS["VERBOSE"]){echo "$cache_file = {$time}mn DISABLE_WATCHDOG = {$GLOBALS["DISABLE_WATCHDOG"]}\n";}
	if($time>2){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.monit.php --status >/dev/null 2>&1 &");
		}
	}
	return implode("\n",$l);
}
//========================================================================================================================================================




function yaffas(){
	if(!$GLOBALS["CLASS_USERS"]->YAFFAS_INSTALLED){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." YAFFAS_INSTALLED = FALSE\n";}return;}
	$bin="/opt/yaffas/webmin/miniserv.pl";
	$EnableYaffas=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableYaffas");	
	if(!is_numeric($EnableYaffas)){$EnableYaffas=1;}
	$master_pid=trim(@file_get_contents("/opt/yaffas/var/miniserv.pid"));
	if($EnableYaffas==0){if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){shell_exec2("/etc/init.d/artica-postfix stop yaffas");}}	
	
	$l[]="[APP_YAFFAS]";
	$l[]="service_name=APP_YAFFAS";
	$l[]="master_version=".yaffas_version();
	$l[]="service_disabled=$EnableYaffas";
	$l[]="watchdog_features=1";
	$l[]="family=mailbox";
	if($EnableYaffas==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			$cmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} /etc/init.d/artica-postfix stop yaffas >/dev/null 2>&1 &"; 
			events("$cmd",__FUNCTION__,__LINE__);
			shell_exec2($cmd);
			
		}
		$l[]="";return implode("\n",$l);
		return;
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_YAFFAS","yaffas");
		$l[]="";
		return implode("\n",$l);
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);
}
//========================================================================================================================================================
function nscd_version($bin){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	exec("$bin -V 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#nscd.+?([0-9\.]+)#", $line)){$GLOBALS[__FUNCTION__]=$re[1];return $re[1];}
	}

}
//========================================================================================================================================================
function yaffas_version($bin){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	
	$results=explode("\n",@file_get_contents("/opt/yaffas/etc/installed-products"));
	
	while (list ($num, $line) = each ($results)){
		if(preg_match("#framework=yaffas v([0-9\.]+)#", $line)){$GLOBALS[__FUNCTION__]=$re[1];return $re[1];}
	}

}
function greensql(){

	if(!$GLOBALS["CLASS_USERS"]->APP_GREENSQL_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." APP_GREENSQL_INSTALLED = FALSE\n";}
		return;
	}
	$EnableGreenSQL=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGreenSQL");
	if(!is_numeric($EnableGreenSQL)){$EnableGreenSQL=1;}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("greensql-fw");
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__." EnableGreenSQL = $EnableGreenSQL\n";}


	$l[]="[APP_GREENSQL]";
	$l[]="service_name=APP_GREENSQL";
	$l[]="master_version=".greensql_version();
	$l[]="service_cmd=greensql";
	$l[]="service_disabled=$EnableGreenSQL";
	$l[]="watchdog_features=1";
	$l[]="family=samba";
	if($EnableGreenSQL==0){$l[]="";return implode("\n",$l);return;}

	$master_pid=@file_get_contents("/var/run/greensql-fw.pid");
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__." /var/run/greensql-fw.pid = $master_pid\n";}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin,true);
	}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_GREENSQL","greensql");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================

function stunnel(){

	if(!$GLOBALS["CLASS_USERS"]->stunnel4_installed){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." stunnel4_installed = FALSE\n";}
		return;
	}
	$sTunnel4enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("sTunnel4enabled");
	if($sTunnel4enabled==null){$sTunnel4enabled=0;}
	
	$unix=new unix();
	
	
	$binary=$GLOBALS["CLASS_UNIX"]->LOCATE_STUNNEL_BIN();
	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/stunnel/stunnel4.pid");

	if($GLOBALS["VERBOSE"]){echo "binary............: $binary\n";}
	if($GLOBALS["VERBOSE"]){echo "PID...............: $master_pid\n";}

	$l[]="[STUNNEL]";
	$l[]="service_name=APP_STUNNEL";
	$l[]="master_version=".stunnel_version();
	$l[]="service_cmd=".$GLOBALS["CLASS_UNIX"]->LOCATE_STUNNEL_INIT();
	$l[]="service_disabled=$sTunnel4enabled";
	$l[]="pid_path=pidof $binary";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	if($sTunnel4enabled==0){$l[]="";return implode("\n",$l);return;}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
				shell_exec2("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.stunnel.php --start &");
		}
		
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}


function stunnel_version(){
	if(isset($GLOBALS["stunnel_version"])){return $GLOBALS["stunnel_version"];}
	$unix=new unix();
	$stunnel=$unix->LOCATE_STUNNEL();
	exec("$stunnel -version 2>&1",$f);
	while (list ($pid, $line) = each ($f) ){
		if(preg_match("#stunnel\s+([0-9\.]+)#", $line,$re)){$GLOBALS["stunnel_version"]=$re[1];return $re[1];}
	}

}

//========================================================================================================================================================


function pptp_clients(){
	if(!$GLOBALS["CLASS_USERS"]->PPTP_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." PPTP_INSTALLED = FALSE\n";}
		return;
	}
	$version=GetVersionOf("pptpd");
	$array=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PPTPVpnClients")));
	if(!is_array($array)){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not an array PPTPVpnClients\n";}
		return;
	}
	if(count($array)==0){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." PPTPVpnClients\n";}
		return;
	}
	$reload=false;
	while (list ($connexionname, $PPTPDConfig) = each ($array) ){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." $connexionname...:{$PPTPDConfig["ENABLED"]}\n";}
		if($PPTPDConfig["ENABLED"]<>1){continue;}
		$arrayPIDS=pptp_client_is_active($connexionname);
		$l[]="[PPTPDCLIENT_$connexionname]";
		$l[]="service_name=$connexionname";
		$l[]="master_version=$version";
		$l[]="service_cmd=pptpd-clients";
		$l[]="service_disabled=1";
		$l[]="pid_path=";
		$l[]="watchdog_features=1";
		$l[]="family=network";

		if(!is_array($arrayPIDS)){$reload=true;}else{
			$l[]=GetMemoriesOf($arrayPIDS[0]);
			$l[]="";
		}
	}

	$l[]="";
	if(!$GLOBALS["DISABLE_WATCHDOG"]){
		if($reload){
			$cmd="{$GLOBALS["PHP5"]} ". dirname(__FILE__)."/exec.pptpd.php --clients-start &";
			events("START PPTP Clients -> $cmd",__FUNCTION__,__LINE__);
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET($cmd);
		}}

		return implode("\n",$l);return;

}
//========================================================================================================================================================
function pptp_client_is_active($connexionname){
	if($GLOBALS["PGREP"]==null){
		$unix=new unix();
		$GLOBALS["PGREP"]=$unix->find_program("pgrep");
	}

	$cmd="{$GLOBALS["PGREP"]} -l -f \"pptp.+?call $connexionname\" 2>&1";
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__." ->$cmd\n";}
	exec($cmd,$results);

	while (list ($num, $line) = each ($results) ){
		if(preg_match("#^([0-9]+).+?pptp#",$line,$re)){
			if($GLOBALS["VERBOSE"]){echo __FUNCTION__." ->PID: {$re[1]}\n";}
			if($unix->PID_IS_CHROOTED($re[1])){continue;}
			$arr[]=$re[1];
		}else{
			if($GLOBALS["VERBOSE"]){echo __FUNCTION__." NO MATCH \"$line\"\n";}
		}

	}

	return $arr;


}



function tftpd(){
	if(!$GLOBALS["CLASS_USERS"]->TFTPD_INSTALLED){return;}
	$EnableTFTPD=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableTFTPD");
	if($EnableTFTPD==null){$EnableTFTPD=1;}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("inetd");
	if(!is_file($bin)){
		$bin=$GLOBALS["CLASS_UNIX"]->find_program("xinetd");
		if(is_file("/var/run/xinetd.pid")){
			$master_pid=trim(@file_get_contents("/var/run/xinetd.pid"));
		}
	}
	if(!is_numeric($master_pid)){$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin);}


	$l[]="[APP_TFTPD]";
	$l[]="service_name=APP_TFTPD";
	$l[]="master_version=".GetVersionOf("tftpd");
	$l[]="service_cmd=tftpd";
	$l[]="service_disabled=$EnableTFTPD";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=0";
	$l[]="family=storage";
	if($EnableTFTPD==0){$l[]="";return implode("\n",$l);return;}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
function postfix_version(){
	
	return $GLOBALS["CLASS_UNIX"]->POSTCONF_GET("mail_version");
}


//========================================================================================================================================================
function postfix_multi_status(){
	if(!is_array($GLOBALS["MULTI-INSTANCES-LIST"])){$calc=true;}
	if($GLOBALS["MULTI-INSTANCES-TIME"]==null){$cacl=true;}
	if(calc_time_min($GLOBALS["MULTI-INSTANCES-TIME"])>5){$cacl=true;}
	if($GLOBALS["VERBOSE"]){echo "GetVersionOf(postfix) line:".__LINE__."\n";}
	$version=postfix_version();

	if($GLOBALS["VERBOSE"]){echo "calc=\"$cacl\" postfix v$version\n";}

	if($calc){
		if($GLOBALS["VERBOSE"]){echo "POSTFIX_MULTI_INSTANCES_LIST() line:".__LINE__."\n";}
		$GLOBALS["MULTI-INSTANCES-LIST"]=$GLOBALS["CLASS_UNIX"]->POSTFIX_MULTI_INSTANCES_LIST();
		$GLOBALS["MULTI-INSTANCES-TIME"]=time();
	}
	if(is_array($GLOBALS["MULTI-INSTANCES-LIST"])){
		while (list ($num, $instance) = each ($GLOBALS["MULTI-INSTANCES-LIST"]) ){
			if($instance==null){continue;}
			$l[]="[POSTFIX-MULTI-$instance]";
			$l[]="service_name=$instance";
			$l[]="master_version=".GetVersionOf("postfix");
			$l[]="service_cmd=postfix-multi";
			$l[]="service_disabled=1";
			$l[]="remove_cmd=--postfix-remove";
			$l[]="family=postfix";
			$master_pid=$GLOBALS["CLASS_UNIX"]->POSTFIX_MULTI_PID($instance);
			if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
				$l[]="";return implode("\n",$l);return;
			}

			$l[]=GetMemoriesOf($master_pid);
			$l[]="";
		}
	}
	if(is_array($l)){return implode("\n",$l);}



}




function postfix(){
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("postconf");
	if($bin_path==null){return null;}
	if(is_file("/etc/artica-postfix/DO_NOT_DETECT_POSTFIX")){return;}
	$EnablePostfixMultiInstance=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfixMultiInstance");
	$EnableStopPostfix=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStopPostfix");
	if($GLOBALS["VERBOSE"]){echo "EnablePostfixMultiInstance=\"$EnablePostfixMultiInstance\"\n";}
	
	if(!is_numeric($EnableStopPostfix)){$EnableStopPostfix=0;}
	if(!is_numeric($EnablePostfixMultiInstance)){$EnablePostfixMultiInstance=0;}

	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	if($DisableMessaging==1){$EnableStopPostfix=1;$EnablePostfixMultiInstance=0;}
	
	if($EnablePostfixMultiInstance==1){$l[]=postfix_multi_status();}
	
	if($EnableStopPostfix==1){
		events("watchdog-postfix:$EnableStopPostfix is enabled, stopping postfix",__FUNCTION__,__LINE__);
		$cmd="{$GLOBALS["nohup"]} /etc/init.d/postfix stop >/dev/null 2>&1 &";
		events("watchdog-postfix:$EnableStopPostfix is enabled, stopping postfix -> $cmd" ,__FUNCTION__,__LINE__);
		shell_exec2($cmd);
		}
	
	if($EnableStopPostfix==0){
		$sendmail_pid_path=$GLOBALS["CLASS_UNIX"]->LOCATE_SENDMAIL_PID_PATH();
		if(strlen($sendmail_pid_path)>3){
			$sendmail_pid=file_get_contents($sendmail_pid_path);
			if(is_numeric($sendmail_pid)){
				events("watchdog-postfix:Sendmail pid detected $sendmail_pid_path ($sendmail_pid)",__FUNCTION__,__LINE__);
				if($GLOBALS["CLASS_UNIX"]->process_exists($sendmail_pid)){
					$kill=$GLOBALS["CLASS_UNIX"]->find_program("kill");
					$postfix=$GLOBALS["CLASS_UNIX"]->find_program("postfix");
					$GLOBALS["CLASS_UNIX"]->KILL_PROCESS($sendmail_pid,9);
					@unlink($sendmail_pid);
					$GLOBALS["CLASS_UNIX"]->send_email_events("SendMail (pid $sendmail_pid) is running, kill it !!","This action has been performed to avoid ports conflicts","smtp");
					shell_exec2("$postfix start  >/dev/null 2>&1 &");
				}
			}
		}
	}

	$postfix_path=$GLOBALS["CLASS_UNIX"]->find_program("postfix");
	$master_pid=$GLOBALS["CLASS_UNIX"]->POSTFIX_PID();
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		exec("$postfix_path status 2>&1",$status_results);
		while (list ($num, $line) = each ($status_results) ){
			if(preg_match("#PID:.+?([0-9]+)#", $line,$re)){
				$GLOBALS["DEBUG_LOGS"][]="postfix status: $line";
				$master_pid=$re[1];
			}
		}
			
	}
		

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_bin_path=$GLOBALS["CLASS_UNIX"]->POSTFIX_MASTER_BIN_PATH();
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($master_bin_path,true);
		events("watchdog-postfix:PIDOF($master_bin_path,true) -> $master_pid" ,__FUNCTION__,__LINE__);
			
	}

	$l[]="[POSTFIX]";
	$l[]="service_name=APP_POSTFIX";
	$l[]="master_version=".postfix_version();
	$l[]="service_cmd=/etc/init.d/postfix";
	$l[]="service_disabled=1";
	$l[]="remove_cmd=--postfix-remove";
	$l[]="family=postfix";
	$l[]="watchdog_features=1";
	if($GLOBALS["ArticaWatchDogList"]["APP_POSTFIX"]==null){$GLOBALS["ArticaWatchDogList"]["APP_POSTFIX"]=1;}
	if($EnableStopPostfix==0){
		if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			if(!$GLOBALS["DISABLE_WATCHDOG"]){
				$GLOBALS["DEBUG_LOGS"][]="$master_pid does not exists";
				if($GLOBALS["ArticaWatchDogList"]["APP_POSTFIX"]==1){
					$postfix_path=$GLOBALS["CLASS_UNIX"]->find_program("postfix");
					$GLOBALS["DEBUG_LOGS"][]="Postfix bin = $postfix_path";
					exec("$postfix_path start -v 2>&1",$pstfix_start);
					$GLOBALS["CLASS_UNIX"]->send_email_events("APP_POSTFIX stopped (watchdog)",
						"Artica will try to start it\n".@implode("\n",$pstfix_start)."\n".@implode("\n", $GLOBALS["DEBUG_LOGS"]),"postfix");
					unset($GLOBALS["DEBUG_LOGS"]);
		
				}
			
			}
			$l[]="";return implode("\n",$l);return;
	
		}
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	$cmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.postfix.iptables.php --export-drop >/dev/null 2>&1 &";
	shell_exec2($cmd);
	$cmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.smtp-senderadv.php >/dev/null 2>&1 &";
	shell_exec2($cmd);
	$cmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.awstats.php --postfix >/dev/null 2>&1 &";
	shell_exec2($cmd);
	$cmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.postqueue.watchdog.php >/dev/null 2>&1 &";
	shell_exec2($cmd);
	
	
	$timefile="/etc/artica-postfix/pids/postqueue.clean.time";
	$exTime=$GLOBALS["CLASS_UNIX"]->file_time_min($timefile);
	if($exTime>5){
		$cmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.postfix-logger.php --postqueue-clean >/dev/null 2>&1 &";
		shell_exec2($cmd);
		$cmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/postfix.index.php >/dev/null 2>&1 &";
		
		shell_exec2($cmd);
	}
	
	$timefile="/etc/artica-postfix/pids/postqueue.cnx-errors.time";
	$exTime=$GLOBALS["CLASS_UNIX"]->file_time_min($timefile);
	if($exTime>7){
		$cmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.postfix-logger.php --cnx-errors >/dev/null 2>&1 &";
		shell_exec2($cmd);
	}	
	
	$timefile="/etc/artica-postfix/pids/postqueue.cnx-only.time";
	$exTime=$GLOBALS["CLASS_UNIX"]->file_time_min($timefile);
	if($exTime>8){
		$cmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.postfix-logger.php --cnx-only >/dev/null 2>&1 &";
		shell_exec2($cmd);
	}	
	
	$timefile="/etc/artica-postfix/pids/exec.postfix.stats.hours.php.time";
	$exTime=$GLOBALS["CLASS_UNIX"]->file_time_min($timefile);
	if($exTime>60){
		$cmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.postfix.stats.hours.php >/dev/null 2>&1 &";
		shell_exec2($cmd);
	}
	
	
	
	return implode("\n",$l);return;

}

function artica_policy(){
	return;
	if(!is_file("/usr/share/artica-postfix/exec.artica-filter-daemon.php")){return;}
	$pid_path="/etc/artica-postfix/exec.artica-filter-daemon.php.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	$EnableArticaPolicyFilter=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaPolicyFilter");
	if($EnableArticaPolicyFilter==null){$EnableArticaPolicyFilter=0;}
	$l[]="[APP_ARTICA_POLICY]";
	$l[]="service_name=APP_ARTICA_POLICY";
	$l[]="master_version=".GetVersionOf("artica");
	$l[]="service_cmd=artica-policy";
	$l[]="service_disabled=$EnableArticaPolicyFilter";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=postfix";
	$l[]="installed=1";
	if($EnableArticaPolicyFilter<>1){
		$l[]="";$l[]="";
		return implode("\n",$l);
		return;
	}

	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_ARTICA_POLICY","artica-policy");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================
function artica_status(){
	if(!is_file("/usr/share/artica-postfix/bin/artica.status.php")){return;}
	if($GLOBALS["TOTAL_MEMORY_MB"]<400){return;}
	$pid_path="/etc/artica-postfix/exec.status.php.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	$EnableArticaStatus=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaStatus");
	if($EnableArticaStatus==null){$EnableArticaStatus=1;}
	$l[]="[APP_ARTICA_STATUS]";
	$l[]="service_name=APP_ARTICA_STATUS";
	$l[]="master_version=".GetVersionOf("artica");
	$l[]="service_cmd=artica-status";
	$l[]="service_disabled=$EnableArticaStatus";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	$l[]="installed=1";
	if($EnableArticaStatus<>1){
		$l[]="";$l[]="";
		return implode("\n",$l);
		return;
	}

	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_ARTICA_STATUS","artica-status");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}


function artica_executor(){
	if(!is_file("/usr/share/artica-postfix/exec.executor.php")){return;}
	if($GLOBALS["TOTAL_MEMORY_MB"]<400){return;}
	$MEMORY=$GLOBALS["CLASS_UNIX"]->MEM_TOTAL_INSTALLEE();
	if($MEMORY<624288){return;}
	$pid_path="/etc/artica-postfix/exec.executor.php.daemon.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	$EnableArticaExecutor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaExecutor");
	if(!is_numeric($EnableArticaExecutor)){$EnableArticaExecutor=1;}
	$l[]="[APP_ARTICA_EXECUTOR]";
	$l[]="service_name=APP_ARTICA_EXECUTOR";
	$l[]="master_version=".GetVersionOf("artica");
	$l[]="service_cmd=artica-exec";
	$l[]="service_disabled=$EnableArticaExecutor";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	$l[]="installed=1";
	if($EnableArticaExecutor<>1){
		$l[]="";$l[]="";
		return implode("\n",$l);
		return;
	}

	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		//$bin=$GLOBALS["CLASS_UNIX"]->find_program("inetd");
		exec($GLOBALS["PHP5"]." ".dirname(__FILE__)."/exec.executor.php --all --verbose 2>&1",$results);
		$GLOBALS["CLASS_UNIX"]->send_email_events("Artica Executor report",
		"This is the debug report when executing artica-executor
		Is disabled ?:$EnableArticaExecutor
		pid:$pid_path
		Pid found:$master_pid
		------------------------------------
		".@implode("\n", $results),"system");

		WATCHDOG("APP_ARTICA_EXECUTOR","artica-exec");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}





function mailman(){
	if(!$GLOBALS["CLASS_USERS"]->MAILMAN_INSTALLED){return null;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MailManEnabled");
	if($enabled==null){$enabled=0;}
	$pid_path=trim(GetVersionOf("mailman-pid"));
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="[MAILMAN]";
	$l[]="service_name=APP_MAILMAN";
	$l[]="master_version=".GetVersionOf("mailman");
	$l[]="service_cmd=mailman";
	$l[]="service_disabled=$enabled";
	$l[]="family=postfix";
	$l[]="pid_path=$pid_path";
	//$l[]="remove_cmd=--milter-grelist-remove";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);return;}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================

function kas3_milter(){



	if(!is_file("/usr/local/ap-mailfilter3/bin/kas-milter")){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}
		return null;
	}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KasxFilterEnabled");
	if($enabled==null){$enabled=0;}
	$pid_path="/usr/local/ap-mailfilter3/run/kas-milter.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="[KAS_MILTER]";
	$l[]="service_name=APP_KAS3_MILTER";
	$l[]="master_version=".GetVersionOf("kas3");
	$l[]="service_cmd=kas3";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="remove_cmd=--kas3-remove";
	$l[]="family=postfix";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);return;}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}
//========================================================================================================================================================
function rsync_version(){
	$rsync=$GLOBALS["CLASS_UNIX"]->find_program("rsync");
	if(isset($GLOBALS["rsync-VER"])){return $GLOBALS["rsync-VER"];}
	exec("$rsync --version 2>&1",$results);
	
	while (list ($num, $val) = each ($results)){
		if(!preg_match("#rsync\s+version\s+([0-9\.]+)#",$val,$re)){continue;}
		$GLOBALS["rsync-VER"]=$re[1];
		return $re[1];
	
	}
	
}
function rsync_debian_mirror(){

	$rsync=$GLOBALS["CLASS_UNIX"]->find_program("rsync");

	if(!is_file($rsync)){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}
		return null;
	}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MirrorEnableDebian");
	if($enabled==null){$enabled=0;}
	
	$MirrorDebianDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MirrorDebianDir");
	if($MirrorDebianDir==null){$MirrorDebianDir="/home/mirrors/Debian";}
	
	
	
	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("rsync.+?$MirrorDebianDir");

	$l[]="[APP_RSYNC_DEBIAN]";
	$l[]="service_name=APP_RSYNC_DEBIAN";
	$l[]="master_version=".rsync_version();
	$l[]="service_disabled=$enabled";
	$l[]="remove_cmd=--kas3-remove";
	$l[]="service_cmd=/etc/init.d/debian-artmirror";
	$l[]="family=proxy";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);return;}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}




function kas3_ap(){



	if(!is_file("/usr/local/ap-mailfilter3/bin/kas-milter")){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}
		return null;
	}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KasxFilterEnabled");
	if($enabled==null){$enabled=0;}
	$pid_path="/usr/local/ap-mailfilter3/run/ap-process-server.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="[KAS3]";
	$l[]="service_name=APP_KAS3";
	$l[]="master_version=".GetVersionOf("kas3");
	$l[]="service_cmd=kas3";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="remove_cmd=--kas3-remove";
	$l[]="family=postfix";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);return;}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================
function smbd(){
	$smbd_bin=$GLOBALS["CLASS_UNIX"]->find_program("smbd");
	if($smbd_bin==null){
		if(is_file("/home/artica/samba.tar.gz.old")){
			$tar=$GLOBALS["CLASS_UNIX"]->find_program("tar");
			shell_exec2("$tar xf /home/artica/samba.tar.gz.old -C /");
			
		
			$smbd_bin=$GLOBALS["CLASS_UNIX"]->find_program("smbd");
			if($smbd_bin==null){
				shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/compile-samba.php >/dev/null 2>&1 &");
			}
		}
		return;
	}

	if(!$GLOBALS["CLASS_USERS"]->SAMBA_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}
		return null;
	}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaEnabled");
	if($enabled==null){$enabled=1;}

	if($enabled==1){
		$pid_path=$GLOBALS["CLASS_UNIX"]->LOCATE_SMBD_PID();
		if($pid_path==null){$pid_path="/var/run/samba/smbd.pid";}
		$master_pid=trim(@file_get_contents($pid_path));
		if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			if($GLOBALS["VERBOSE"]){echo "pid path \"$pid_path\" no pid found\n";}
			$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($smbd_bin);
			if($GLOBALS["VERBOSE"]){echo "pid:$master_pid after pidof\n";}
			if(is_file($pid_path)){
				if($GLOBALS["VERBOSE"]){echo "write $master_pid in \"$pid_path\"\n";}
				if($master_pid>1){@file_put_contents($pid_path,$master_pid);}
			}
		}	
	}

		$l[]="[SAMBA_SMBD]";
		$l[]="service_name=APP_SAMBA_SMBD";
		$l[]="master_version=".samba_version();
		$l[]="service_cmd=samba";
		$l[]="service_disabled=$enabled";
		$l[]="pid_path=$pid_path";
		$l[]="remove_cmd=--samba-remove";
		$l[]="family=storage";
		if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			if($enabled==1){
				shell_exec2("{$GLOBALS["NICE"]} /etc/init.d/artica-postfix start samba >/dev/null 2>&1 &");
			}
			$l[]="running=0\ninstalled=1";
			$l[]="";
			return implode("\n",$l);
		}
		$l[]="running=1";
		$l[]=GetMemoriesOf($master_pid);
		$l[]="";
		
		if(is_dir("/var/run/samba/winbindd_privileged")){
			$chmod=$GLOBALS["CLASS_UNIX"]->find_program("chmod");
			chmod("/var/run/samba/winbindd_privileged",0750);
			shell_exec2("$chmod 0750 /var/run/samba/winbindd_privileged >/dev/null 2>&1");
		}
		
		return implode("\n",$l);return;
}
//========================================================================================================================================================
function nmbd(){



	if(!$GLOBALS["CLASS_USERS"]->SAMBA_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}
		return null;
	}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaEnabled");
	if($enabled==null){$enabled=1;}
	$pid_path=$GLOBALS["CLASS_UNIX"]->LOCATE_NMBD_PID();
	$nmbd_bin=$GLOBALS["CLASS_UNIX"]->find_program("nmbd");
	if($GLOBALS["VERBOSE"]){"NMBD: PID: $pid_path\n";}
	$master_pid=trim(@file_get_contents($pid_path));
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if($GLOBALS["VERBOSE"]){echo "NMBD: pid path \"$pid_path\" no pid found\n";}
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($nmbd_bin);
		if($GLOBALS["VERBOSE"]){echo "NMBD: pid:$master_pid after pidof `$nmbd_bin`\n";}
		if(is_file($pid_path)){
			if($GLOBALS["VERBOSE"]){echo "NMBD: write $master_pid in \"$pid_path\"\n";}
			if($master_pid>1){@file_put_contents($pid_path,$master_pid);}
		}
	}

	$l[]="[SAMBA_NMBD]";
	$l[]="service_name=APP_SAMBA_NMBD";
	$l[]="master_version=".samba_version();
	$l[]="service_cmd=samba";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="remove_cmd=--samba-remove";
	$l[]="family=storage";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if($enabled==1){shell_exec2("{$GLOBALS["NICE"]} /etc/init.d/artica-postfix start samba >/dev/null 2>&1 &");}		
		
		$l[]="running=0\ninstalled=1";
		$l[]="";
		return implode("\n",$l);
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================
function samba_version(){
	if(isset($GLOBALS["SMBD-VER"])){return $GLOBALS["SMBD-VER"];}
	$smbd_bin=$GLOBALS["CLASS_UNIX"]->find_program("smbd");
	if($smbd_bin==null){return;}	
	exec("$smbd_bin -V 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#Version\s+(.+)#", $val,$re)){
			$GLOBALS["SMBD-VER"]=$re[1];
			return $GLOBALS["SMBD-VER"];
		}	
	}
	
}

function winbindd_ping(){
	$smbcontrol=$GLOBALS["CLASS_UNIX"]->find_program('smbcontrol');
	
	shell_exec2("{$GLOBALS["CHMOD"]} 0750 /var/lib/samba/winbindd_privileged >/dev/null 2>&1");
	
	
	$results=exec("$smbcontrol winbindd ping 2>&1");
	
	if(preg_match("#No replies received#i", $results)){
		Winbindd_events("Winbindd service ping failed","winbindd_ping","winbindd_ping",__LINE__);
		$GLOBALS["CLASS_UNIX"]->send_email_events("Winbindd service ping failed","Winbindd failed to answer with error: $results\nArtica will try to restart it","samba");
		
		shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/winbind restart --force >/dev/null 2>&1 &");	
	}
}


function Winbindd_events($text,$sourcefunction=null,$sourceline=null){
	$GLOBALS["CLASS_UNIX"]->events("exec.status.php::$text","/var/log/squid.watchdog.log",false,$sourcefunction,$sourceline);
	
}

function winbind_pid(){
	
	$pidfile=$GLOBALS["CLASS_UNIX"]->LOCATE_WINBINDD_PID();
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pidfile);
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	$winbindbin=$GLOBALS["CLASS_UNIX"]->find_program("winbindd");
	$pid=$GLOBALS["CLASS_UNIX"]->PIDOF($winbindbin);
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){
		@file_put_contents($pidfile, $pid);
		return $pid;
	}

	return null;

}

function winbindd(){

	if(!$GLOBALS["CLASS_USERS"]->SAMBA_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}
		return null;
	}

	if(!$GLOBALS["CLASS_USERS"]->WINBINDD_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}
		return null;
	}
	
	

	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaEnabled");
	$DisableWinbindd=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableWinbindd");
	$DisableSambaFileSharing=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableSambaFileSharing");
	$EnableSambaActiveDirectory=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSambaActiveDirectory");
	
	
	$squid=$GLOBALS["CLASS_UNIX"]->LOCATE_SQUID_BIN();
	if(is_file($squid)){
		$EnableKerbAuth=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth");
		$EnableKerberosAuthentication=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerberosAuthentication");
		if(!is_numeric("$EnableKerberosAuthentication")){$EnableKerberosAuthentication=0;}
		if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
		if($EnableKerbAuth==1){$DisableWinbindd=0;}
	}
	
	
	
	if(!is_numeric($EnableSambaActiveDirectory)){$EnableSambaActiveDirectory=0;}
	if(!is_numeric($DisableWinbindd)){$DisableWinbindd=0;}
	if(!is_numeric($enabled)){$enabled=1;}
	if($GLOBALS["VERBOSE"]){
		echo "enabled.................................: $enabled\n";
		echo "DisableWinbindd.........................: $DisableWinbindd\n";
		echo "EnableSambaActiveDirectory..............: $EnableSambaActiveDirectory\n";
		
	}

	if(!$GLOBALS["DISABLE_WATCHDOG"]){
		shell_exec2("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.samba.php --ping-ads &");
	}
	if($EnableSambaActiveDirectory==1){$DisableWinbindd=0;}
	$smbcontrol=$GLOBALS["CLASS_UNIX"]->find_program('smbcontrol');

	if($DisableWinbindd==1){$enabled=0;}
	
	if($DisableSambaFileSharing==1){if($EnableSambaActiveDirectory==0){$enabled=0;}}

	$pid_path=$GLOBALS["CLASS_UNIX"]->LOCATE_WINBINDD_PID();
	$master_pid=winbind_pid();
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$nmbd_bin=$GLOBALS["CLASS_UNIX"]->find_program("winbindd");
		if($GLOBALS["VERBOSE"]){echo "pid path \"$pid_path\" no pid found\n";}
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($nmbd_bin);
		if($GLOBALS["VERBOSE"]){echo "pid:$master_pid after pidof\n";}
		if(is_file($pid_path)){
			if(!$GLOBALS["DISABLE_WATCHDOG"]){
				if($GLOBALS["VERBOSE"]){echo "write $master_pid in \"$pid_path\"\n";}
				if($master_pid>1){@file_put_contents($pid_path,$master_pid);}
			}
		}
	}

	if($GLOBALS["VERBOSE"]){
		echo "enabled.................................: $enabled\n";
		echo "DisableWinbindd.........................: $DisableWinbindd\n";
		echo "EnableSambaActiveDirectory..............: $EnableSambaActiveDirectory\n";
		
	}	

	$l[]="[SAMBA_WINBIND]";
	$l[]="service_name=APP_SAMBA_WINBIND";
	$l[]="master_version=".samba_version();
	$l[]="service_cmd=samba";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="remove_cmd=--samba-remove";
	$l[]="family=storage";
	
	
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			if($enabled==1){
				Winbindd_events("Winbindd service Failed -> start it",__FUNCTION__,__LINE__);
				if(!$GLOBALS["DISABLE_WATCHDOG"]){
					shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.winbindd.php --start");
				}
			}
			$l[]="running=0\ninstalled=1";$l[]="";
			return implode("\n",$l);
			return;
	}
	if(!$GLOBALS["DISABLE_WATCHDOG"]){
		if($enabled==1){if(strlen($smbcontrol)>3){winbindd_ping();}}
	}
	
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	
	shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} ".dirname( __FILE__)."/exec.adusers.php --computers schedule-id=0 >/dev/null 2>&1 &");
	
	return implode("\n",$l);return;
}
//========================================================================================================================================================
function scanned_only(){
	if(!is_file("/usr/share/artica-postfix/bin/artica-install")){return;}
	if(!$GLOBALS["CLASS_USERS"]->SAMBA_INSTALLED){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}return null;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableScannedOnly");
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program('scannedonlyd_clamav');
	if(strlen($binpath)<strlen("scannedonlyd_clamav")){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}
		return null;
	}
	
	
	if(!is_numeric($enabled)){$enabled=1;}
	
	$pid_path="/var/run/scannedonly.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="[SAMBA_SCANNEDONLY]";
	$l[]="service_name=APP_SCANNED_ONLY";
	$l[]="master_version=unknown";
	$l[]="service_cmd=samba";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	//$l[]="remove_cmd=--samba-remove";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($binpath);}
	
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);return;}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================
function roundcube(){
	if(!$GLOBALS["CLASS_USERS"]->roundcube_installed){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}
		return null;
	}

	$users=new settings_inc();
	if(!$GLOBALS["CLASS_USERS"]->POSTFIX_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." postfix not installed\n";}
		return null;
	}

	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RoundCubeHTTPEngineEnabled");
	if(!is_numeric($enabled)){$enabled=0;}
	$pid_path="/var/run/lighttpd/lighttpd-roundcube.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
		exec("$pgrep -l -f \"lighttpd -f /etc/artica-postfix/lighttpd-roundcube.conf\"",$results);
		while (list ($num, $line) = each ($results)){
			if(preg_match("#^([0-9]+).+?lighttpd#", $line,$re)){
				if(!preg_match("#pgrep#", $line)){$master_pid=$re[1];if($GLOBALS["VERBOSE"]){echo "found PID '$master_pid' $line \n";};break;}
			}
		}
	}


	$l[]="[ROUNDCUBE]";
	$l[]="service_name=APP_ROUNDCUBE";
	$l[]="master_version=".GetVersionOf("roundcube");
	$l[]="service_cmd=/etc/init.d/roundcube";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="family=mailbox";
	//$l[]="remove_cmd=--samba-remove";
	shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.roundcube.php >/dev/null 2>&1 &");
	
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if($GLOBALS["VERBOSE"]){echo "PID {$re[1]} Not exists\n";}
		$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);return;}
		$l[]="running=1";
		$l[]=GetMemoriesOf($master_pid);
		$l[]="";
		return implode("\n",$l);return;
}
//========================================================================================================================================================
function cups(){



	if(!$GLOBALS["CLASS_USERS"]->CUPS_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}
		return null;
	}
	$enabled=1;
	if($enabled==null){$enabled=0;}
	$pid_path="/var/run/cups/cupsd.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="[CUPS]";
	$l[]="service_name=APP_CUPS";
	$l[]="master_version=".GetVersionOf("cups");
	$l[]="service_cmd=cups";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="family=storage";
	//$l[]="remove_cmd=--samba-remove";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);return;}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================

function apache_groupware(){



	if(!$GLOBALS["CLASS_USERS"]->APACHE_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}
		return null;
	}
	$ApacheGroupware=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ApacheGroupware");
	$DisableFollowServiceHigerThan1G=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableFollowServiceHigerThan1G");
	if(!is_numeric($ApacheGroupware)){$ApacheGroupware=0;}
	if($DisableFollowServiceHigerThan1G==null){$DisableFollowServiceHigerThan1G=0;}

	if($DisableFollowServiceHigerThan1G==0){
		if(is_file("/etc/artica-postfix/MEMORY_INSTALLED")){
			$MEMORY_INSTALLED=@file_get_contents("/etc/artica-postfix/MEMORY_INSTALLED");
			if($MEMORY_INSTALLED>0){if($MEMORY_INSTALLED<526300){$ApacheGroupware=0;}}
		}
	}

	$pid_path="/var/run/apache-groupware/httpd.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="[APP_GROUPWARE_APACHE]";
	$l[]="service_name=APP_GROUPWARE_APACHE";
	$l[]="master_version=".GetVersionOf("apache");
	$l[]="service_cmd=apache-groupware";
	$l[]="service_disabled=$ApacheGroupware";
	$l[]="pid_path=$pid_path";
	$l[]="family=www";
	$l[]="watchdog_features=1";
	//$l[]="remove_cmd=--samba-remove";

	if($ApacheGroupware==0){return implode("\n",$l);return;}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_GROUPWARE_APACHE","apache-groupware");
		$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);return;}
		$l[]="running=1";
		$l[]=GetMemoriesOf($master_pid);
		$l[]="";
		return implode("\n",$l);return;
}
//========================================================================================================================================================
function apache_ocsweb(){



	if(!$GLOBALS["CLASS_USERS"]->APACHE_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." apache not installed\n";}
		return null;
	}

	if(!is_file("/usr/share/ocsinventory-reports/ocsreports/dbconfig.inc.php")){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}
		return null;
	}

	$OCSNGEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OCSNGEnabled");
	if($OCSNGEnabled==null){$OCSNGEnabled=1;}
	$pid_path="/var/run/apache-ocs/httpd.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="[APP_OCSI]";
	$l[]="service_name=APP_OCSI";
	$l[]="master_version=".GetVersionOf("ocsi");
	$l[]="service_cmd=ocsweb";
	$l[]="service_disabled=$OCSNGEnabled";
	$l[]="pid_path=$pid_path";
	$l[]="family=computers";
	$l[]="watchdog_features=1";
	//$l[]="remove_cmd=--samba-remove";

	if($OCSNGEnabled==0){return implode("\n",$l);return;}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_OCSI","ocsweb");
		$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);return;}
		$l[]="running=1";
		$l[]=GetMemoriesOf($master_pid);
		$l[]="";
		return implode("\n",$l);return;
}
//========================================================================================================================================================
function apache_ocsweb_download(){



	if(!$GLOBALS["CLASS_USERS"]->APACHE_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." apache not installed\n";}
		return null;
	}

	if(!is_file("/usr/share/ocsinventory-reports/ocsreports/dbconfig.inc.php")){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}
		return null;
	}
	$UseFusionInventoryAgents=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseFusionInventoryAgents");
	$OCSNGEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OCSNGEnabled");
	if($OCSNGEnabled==null){$OCSNGEnabled=1;}
	if($UseFusionInventoryAgents==null){$UseFusionInventoryAgents=1;}
	$pid_path="/var/run/apache-ocs/httpd-download.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$f[]='cacert.pem';
	$f[]='server.crt';
	$f[]='server.key';
	 
	while (list ($num, $file) = each ($f) ){
		if(!is_file("/etc/ocs/cert/$file")){
			$OCSNGEnabled=0;
		}
	}
	if($UseFusionInventoryAgents==1){$OCSNGEnabled=0;}

	$l[]="[APP_OCSI_DOWNLOAD]";
	$l[]="service_name=APP_OCSI_DOWNLOAD";
	$l[]="master_version=".GetVersionOf("ocsi");
	$l[]="service_cmd=ocsweb";
	$l[]="service_disabled=$OCSNGEnabled";
	$l[]="pid_path=$pid_path";
	$l[]="family=computers";
	$l[]="watchdog_features=1";
	//$l[]="remove_cmd=--samba-remove";

	if($OCSNGEnabled==0){return implode("\n",$l);return;}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_OCSI_DOWNLOAD","ocsweb");
		$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);return;}
		$l[]="running=1";
		$l[]=GetMemoriesOf($master_pid);
		$l[]="";
		return implode("\n",$l);return;
}
//========================================================================================================================================================
function ocs_agent(){
	if(!$GLOBALS["CLASS_USERS"]->OCS_LNX_AGENT_INSTALLED){return null;}
	$OCSNGEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOCSAgent");
	if($OCSNGEnabled==null){$OCSNGEnabled=1;}
	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("/usr/local/bin/ocsinventory-agent");


	 

	$l[]="[APP_OCSI_LINUX_CLIENT]";
	$l[]="service_name=APP_OCSI_LINUX_CLIENT";
	$l[]="master_version=".GetVersionOf("ocsagent");
	$l[]="service_cmd=ocsagent";
	$l[]="service_disabled=$OCSNGEnabled";
	$l[]="pid_path=$pid_path";
	$l[]="family=computers";
	$l[]="watchdog_features=1";
	//$l[]="remove_cmd=--samba-remove";

	if($OCSNGEnabled==0){return implode("\n",$l);return;}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_OCSI_LINUX_CLIENT","ocsagent");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}

//========================================================================================================================================================

function openssh_version(){
	if(isset($GLOBALS["OPENSSH-VER"])){return $GLOBALS["OPENSSH-VER"];}
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program('sshd');
	if($bin_path==null){return;}
	exec("$bin_path -h 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#OpenSSH_(.+?),#", $val,$re)){
			$GLOBALS["OPENSSH-VER"]=$re[1];
			return $GLOBALS["OPENSSH-VER"];
		}
	}
	
}
//========================================================================================================================================================

function openssh(){



	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program('sshd');
	if($bin_path==null){return;}
	$pid_path=$GLOBALS["CLASS_UNIX"]->LOCATE_SSHD_PID_PATH();
	$master_pid=trim(@file_get_contents($pid_path));
	$EnableSSHD=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSSHD");
	if($EnableSSHD==null){$EnableSSHD=1;}
	
	
	
	$l[]="[APP_OPENSSH]";
	$l[]="service_name=APP_OPENSSH";
	$l[]="master_version=".openssh_version();
	$l[]="service_cmd=/etc/init.d/artica-ssh";
	$l[]="service_disabled=1";
	$l[]="pid_path=$pid_path";
	$l[]="family=network";
	$l[]="watchdog_features=1";
	if($EnableSSHD==0){return implode("\n",$l);return;}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin_path);
	}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		system_admin_events("OpenSSH server is not running, start it",__FUNCTION__,__FILE__,__LINE__);
		shell_exec("/etc/init.d/ssh start >/dev/null 2>&1 &");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		
	}
	
	$ttl=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($master_pid);
	
	
	
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}



//========================================================================================================================================================
function gdm(){



	$gdm_path=$GLOBALS["CLASS_UNIX"]->find_program('gdm');
	if($gdm_path==null){return;}
	$pid_path="/var/run/gdm.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="[GDM]";
	$l[]="service_name=APP_GDM";
	$l[]="master_version=".GetVersionOf("gdm");
	//$l[]="service_cmd=apache-groupware";
	$l[]="service_disabled=1";
	$l[]="pid_path=$pid_path";
	$l[]="family=system";
	//$l[]="remove_cmd=--samba-remove";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);return;}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================
function consolekit(){
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program('console-kit-daemon');
	if($binpath==null){return;}

	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
	if($master_pid==null){return;}
	$l[]="[CONSOLEKIT]";
	$l[]="service_name=APP_CONSOLEKIT";
	$l[]="master_version=0.00";
	$l[]="binpath=$binpath";
	//$l[]="service_cmd=apache-groupware";
	$l[]="service_disabled=1";
	$l[]="family=system";
	$l[]="pid_path=$pid_path";
	//$l[]="remove_cmd=--samba-remove";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);return;}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================
function xfce(){
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program('xfdesktop');
	if($binpath==null){return;}

	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($binpath);

	$l[]="[XFCE]";
	$l[]="service_name=APP_XFCE";
	$l[]="master_version=".GetVersionOf("xfce");
	$l[]="family=system";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$l[]="running=0";
		$l[]="installed=1";
		$l[]="service_disabled=0";
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]="running=1";
	$l[]="service_disabled=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================
function XMail(){
	$binpath="/var/lib/xmail/bin/XMail";
	if($binpath==null){return;}

	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){return;}
	shell_exec2("{$GLOBALS["KILLBIN"]} -9 $master_pid");
}
//========================================================================================================================================================







function _zarafa_checkExtension($name, $version=""){
	$result = true;
	$help_msg=null;
	if (extension_loaded($name)){
		if (version_compare(phpversion($name), $version) == -1){
			$GLOBALS["ZARAFA_ERROR"]=_zarafa_error_version("PHP ".$name." extension",phpversion($name), $version, $help_msg);
			$result = false;
		}
	}else{
			
		$GLOBALS["ZARAFA_ERROR"]=_zarafa_error_notfound("PHP ".$name." extension", $help_msg);
		$result = false;
	}
	return $result;
}


function zarafa_mapi(){
	if(!_zarafa_checkExtension("mapi", "5.0-4688", "Mapi error, please contact Artica support team.")){
		if($GLOBALS["VERBOSE"]){echo "Warning Zarafa mapi php extension error {$GLOBALS["ZARAFA_ERROR"]}\n";}
		$GLOBALS["CLASS_UNIX"]->send_email_events("Warning Zarafa mapi php extension error",$GLOBALS["ZARAFA_ERROR"],"mailbox");

	}
}

function _zarafa_error_version($name, $needed, $found, $help){return sprintf("Version error: %s %s found, but %s needed.\n",$name, $needed, $found);}
function _zarafa_error_notfound($name, $help){return sprintf("Not Found: %s not found", $name);}

function zarafa_web_pid(){
	$unix=new unix();
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/zarafa-web/httpd.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	$apachebin=$GLOBALS["CLASS_UNIX"]->LOCATE_APACHE_BIN_PATH();
	return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("$apachebin.*?/etc/zarafa/httpd.conf");

}

function zarafa_web(){



	if(!$GLOBALS["CLASS_USERS"]->APACHE_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}
		return null;
	}

	if(!$GLOBALS["CLASS_USERS"]->ZARAFA_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}
		return null;
	}

	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaApacheEnable");
	if(!is_numeric($enabled)){$enabled=1;}	
	$pid_path="/var/run/zarafa-web/httpd.pid";
	$master_pid=zarafa_web_pid();

	$l[]="[APP_ZARAFA_WEB]";
	$l[]="service_name=APP_ZARAFA_WEB";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->ZARAFA_VERSION();
	$l[]="service_cmd=/etc/init.d/zarafa-web";
	$l[]="service_disabled=$enabled";
	$l[]="family=mailbox";
	$l[]="pid_path=$pid_path";
	$l[]="remove_cmd=--zarafa-remove";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);return;}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["nohup"]} /etc/init.d/zarafa-web start >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================
function zarafa_ical(){





	if(!$GLOBALS["CLASS_USERS"]->ZARAFA_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}
		return null;
	}

	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaiCalEnable");
	if($enabled==null){$enabled=0;}
	$pid_path="/var/run/zarafa-ical.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="[APP_ZARAFA_ICAL]";
	$l[]="service_name=APP_ZARAFA_ICAL";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->ZARAFA_VERSION();
	$l[]="service_cmd=/etc/init.d/zarafa-ical";
	$l[]="service_disabled=$enabled";
	$l[]="family=mailbox";
	$l[]="pid_path=$pid_path";
	$l[]="remove_cmd=--zarafa-remove";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
	if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/zarafa-ical start >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================
function vps_servers(){
	$xr=$GLOBALS["CLASS_UNIX"]->find_program("lxc-version");
	if(strlen($xr)<4){return;}
	if(is_file("/etc/artica-postfix/KASPERSKY_WEB_APPLIANCE")){return;}
	if(is_file("/etc/artica-postfix/WEBSTATS_APPLIANCE")){return;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LXCEnabled");
	if(!is_numeric($enabled)){$enabled=0;}
	if($enabled==0){return;}
	if($GLOBALS["VERBOSE"]){$verbs=" --verbose";}
	exec("{$GLOBALS["PHP5"]} ". dirname(__FILE__)."/exec.vservers.php --status$verbs 2>&1",$results);
	return implode("\n",$results);return;


}

function crossroads_multiple(){
	$xr=$GLOBALS["CLASS_UNIX"]->find_program("xr");
	if(strlen($xr)<4){return;}
	if(!is_file( dirname(__FILE__)."/exec.crossroads.php")){return;}
	if($GLOBALS["VERBOSE"]){$verbs=" --verbose";}
	exec("{$GLOBALS["PHP5"]} ". dirname(__FILE__)."/exec.crossroads.php --multiples-status$verbs 2>&1",$results);
	return implode("\n",$results);return;
}


function zarafa_dagent(){
	if(!$GLOBALS["CLASS_USERS"]->ZARAFA_INSTALLED){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}return null;}

	$enabled=1;
	if($enabled==null){$enabled=0;}
	$pid_path="/var/run/zarafa-dagent.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="[APP_ZARAFA_DAGENT]";
	$l[]="service_name=APP_ZARAFA_DAGENT";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->ZARAFA_VERSION();
	$l[]="service_cmd=/etc/init.d/zarafa-dagent";
	$l[]="service_disabled=$enabled";
	$l[]="family=mailbox";
	$l[]="pid_path=$pid_path";
	$l[]="remove_cmd=--zarafa-remove";
	$l[]="watchdog_features=1";

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/zarafa-dagent start >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================
function zarafa_monitor(){
	if(!$GLOBALS["CLASS_USERS"]->ZARAFA_INSTALLED){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}return null;}

	$enabled=1;
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaEnableServer");
	if(!is_numeric($enabled)){$enabled=1;}
	$pid_path="/var/run/zarafa-monitor.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="[APP_ZARAFA_MONITOR]";
	$l[]="service_name=APP_ZARAFA_MONITOR";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->ZARAFA_VERSION();
	$l[]="service_cmd=/etc/init.d/zarafa-monitor";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="family=mailbox";
	$l[]="remove_cmd=--zarafa-remove";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);return;}	
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/zarafa-monitor start >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}


	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================
function zarafa_search(){
	if(!$GLOBALS["CLASS_USERS"]->ZARAFA_SEARCH_INSTALLED){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}return null;}

	$enabled=1;
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableZarafaSearch");
	if(!is_numeric($enabled)){$enabled=1;}
	$pid_path="/var/run/zarafa-search.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="[APP_ZARAFA_SEARCH]";
	$l[]="service_name=APP_ZARAFA_SEARCH";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->ZARAFA_VERSION();
	$l[]="service_cmd=/etc/init.d/zarafa-search";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="family=mailbox";
	$l[]="remove_cmd=--zarafa-remove";
	$l[]="watchdog_features=1";
	


	if($enabled==0){return implode("\n",$l);return;}	
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/zarafa-search start >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}


	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================


function zarafa_gateway(){
	if(!$GLOBALS["CLASS_USERS"]->ZARAFA_INSTALLED){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}return null;}


	$ZarafaPop3Enable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaPop3Enable");
	$ZarafaPop3sEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaPop3sEnable");
	$ZarafaIMAPEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaIMAPEnable");
	$ZarafaIMAPsEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaIMAPsEnable");
	
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaEnableServer");
	if(!is_numeric($enabled)){$enabled=1;}	

	if(!is_numeric($ZarafaPop3Enable)){$ZarafaPop3Enable=1;}
	if(!is_numeric($ZarafaPop3sEnable)){$ZarafaPop3sEnable=0;}
	if(!is_numeric($ZarafaIMAPEnable)){$ZarafaIMAPEnable=1;}
	if(!is_numeric($ZarafaIMAPsEnable)){$ZarafaIMAPsEnable=0;}
	$ZarafaPop3Enable=intval($ZarafaPop3Enable);
	$ZarafaPop3sEnable=intval($ZarafaPop3sEnable);
	$ZarafaIMAPEnable=intval($ZarafaIMAPEnable);
	$ZarafaIMAPsEnable=intval($ZarafaIMAPsEnable);

	$total=$ZarafaIMAPsEnable+$ZarafaPop3Enable+$ZarafaPop3sEnable+$ZarafaIMAPEnable;
	if($total==0){$enabled=0;}
	$pid_path="/var/run/zarafa-gateway.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="[APP_ZARAFA_GATEWAY]";
	$l[]="service_name=APP_ZARAFA_GATEWAY";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->ZARAFA_VERSION();
	$l[]="service_cmd=/etc/init.d/zarafa-gateway";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="remove_cmd=--zarafa-remove";
	$l[]="watchdog_features=1";
	$l[]="family=mailbox";
	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		shell_exec2("/etc/init.d/zarafa-gateway start");
		$master_pid=trim(@file_get_contents($pid_path));
	}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/zarafa-gateway start >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================
function zarafa_spooler(){
	if(!$GLOBALS["CLASS_USERS"]->ZARAFA_INSTALLED){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}return null;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaEnableServer");
	if(!is_numeric($enabled)){$enabled=1;}	
	$pid_path="/var/run/zarafa-spooler.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	

	$l[]="[APP_ZARAFA_SPOOLER]";
	$l[]="service_name=APP_ZARAFA_SPOOLER";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->ZARAFA_VERSION();
	$l[]="service_cmd=/etc/init.d/zarafa-spooler";
	$l[]="service_disabled=$enabled";
	$l[]="family=mailbox";
	$l[]="pid_path=$pid_path";
	$l[]="remove_cmd=--zarafa-remove";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);return;}
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/zarafa-spooler start >/dev/null 2>&1 &");
		}
		
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}


	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}

function ZARAFA_DB_VERSION(){
	if(isset($GLOBALS["ZARAFA_DB_VERSION"])){return $GLOBALS["ZARAFA_DB_VERSION"];}
	exec("/opt/zarafa-db/bin/mysqld --version 2>&1",$results);
	while (list ($i, $line) = each ($results)){
		if(preg_match("#Ver\s+([0-9\.]+)#", $line,$re)){
			$GLOBALS["ZARAFA_DB_VERSION"]=$re[1];
			return $re[1];
		}
	}
	
	return "0.0.0";
}
function _MYSQL_VERSION(){
	if(isset($GLOBALS["SQUID_DB_VERSION"])){return $GLOBALS["SQUID_DB_VERSION"];}
	$mysqld=$GLOBALS["CLASS_UNIX"]->find_program("mysqld");
	
	exec("$mysqld --version 2>&1",$results);
	while (list ($i, $line) = each ($results)){
		if(preg_match("#Ver\s+([0-9\.]+)#", $line,$re)){
			$GLOBALS["SQUID_DB_VERSION"]=$re[1];
			return $re[1];
		}
	}

	return "0.0.0";
}
//========================================================================================================================================================
function zarafa_db(){
	
	$ZarafaMySQLServiceType=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaMySQLServiceType");
	if(!is_numeric($ZarafaMySQLServiceType)){$ZarafaMySQLServiceType=1;}
	$ZarafaDedicateMySQLServer=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaDedicateMySQLServer");
	if(!is_numeric($ZarafaDedicateMySQLServer)){$ZarafaDedicateMySQLServer=0;}
	if($ZarafaDedicateMySQLServer==0){return;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaDBEnabled");
	if(!is_numeric($enabled)){$enabled=1;}
	$pid_path="/var/run/zarafa-db.pid";
	$master_pid=trim(@file_get_contents($pid_path));


	$l[]="[APP_ZARAFA_DB]";
	$l[]="service_name=APP_ZARAFA_DB";
	$l[]="master_version=".mysqld_version();
	$l[]="service_cmd=/etc/init.d/zarafa-db";
	$l[]="service_disabled=$enabled";
	$l[]="family=mailbox";
	$l[]="pid_path=$pid_path";
	$l[]="remove_cmd=--zarafa-remove";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);return;}
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$mysqld=$GLOBALS["CLASS_UNIX"]->find_program("mysqld");
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("$mysqld.*?--pid-file=/var/run/zarafa-db.pid");
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.zarafa-db.php --start >/dev/null 2>&1");
			shell_exec2($cmd);
			
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}


	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.zarafa-db.php --databasesize >/dev/null 2>&1 &");
	return implode("\n",$l);return;
}
//========================================================================================================================================================
function roundcube_db(){

	$RoundCubeMySQLServiceType=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RoundCubeMySQLServiceType");
	if(!is_numeric($RoundCubeMySQLServiceType)){$RoundCubeMySQLServiceType=1;}
	$RoundCubeDedicateMySQLServer=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RoundCubeDedicateMySQLServer");
	if(!is_numeric($RoundCubeDedicateMySQLServer)){$RoundCubeDedicateMySQLServer=0;}
	if($RoundCubeDedicateMySQLServer==0){return;}

	$pid_path="/var/run/roundcube-db.pid";
	$master_pid=trim(@file_get_contents($pid_path));


	$l[]="[APP_ROUNDCUBE_DB]";
	$l[]="service_name=APP_ROUNDCUBE_DB";
	$l[]="master_version=".mysqld_version();
	$l[]="service_cmd=/etc/init.d/roundcube-db";
	$l[]="service_disabled=$RoundCubeDedicateMySQLServer";
	$l[]="family=mailbox";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";

	if($RoundCubeDedicateMySQLServer==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$mysqld=$GLOBALS["CLASS_UNIX"]->find_program("mysqld");
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("$mysqld.*?--pid-file=$pid_path");
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.roundcube-db.php --start >/dev/null 2>&1");
			shell_exec2($cmd);
				
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}


	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.roundcube-db.php --databasesize >/dev/null 2>&1 &");
	return implode("\n",$l);return;
}
//========================================================================================================================================================



function ntopng_version(){
	
	if(isset($GLOBALS["ntopng_version"])){return $GLOBALS["ntopng_version"];}
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("ntopng");
	if(!is_file($masterbin)){return "0.0.0";}
	exec("$masterbin -h 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#ntopng.*?v\.([0-9\.]+)#", $val,$re)){
			$GLOBALS["ntopng_version"]=trim($re[1]);
			return $GLOBALS["ntopng_version"];
		}
	}
}

function ntopng_pid(){
	
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("ntopng");
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file('/var/run/ntopng/ntopng.pid');
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF($masterbin);
}
function redis_pid(){
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("redis-server");
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file('/var/run/redis/redis-server.pid');
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN($masterbin." -f /etc/redis/redis.conf");
}
function redis_version(){
	
	if(isset($GLOBALS["redis_version"])){return $GLOBALS["redis_version"];}
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("redis-server");
	if(!is_file($masterbin)){return "0.0.0";}
	exec("$masterbin -v 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#Redis server version\s+([0-9\.]+)#", $val,$re)){
			$GLOBALS["redis_version"]=trim($re[1]);
			return $GLOBALS["redis_version"];
		}
	}
}


function ntopng(){
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("ntopng");
	if(!is_file($masterbin)){return;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablentopng");
	if(!is_numeric($enabled)){$enabled=1;}
	$l[]="[APP_NTOPNG]";
	$l[]="service_name=APP_NTOPNG";
	$l[]="master_version=".ntopng_version();
	$l[]="service_cmd=/etc/init.d/ntopng";
	$l[]="service_disabled=$enabled";
	$l[]="family=proxy";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);return;}

	$master_pid=ntopng_pid();

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/ntopng start >/dev/null 2>&1 &");
			
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;
}
function redis_server(){
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("ntopng");
	if(!is_file($masterbin)){return;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("redis-server");
	if(!is_numeric($enabled)){$enabled=1;}
	$l[]="[APP_REDIS_SERVER]";
	$l[]="service_name=APP_REDIS_SERVER";
	$l[]="master_version=".redis_version();
	$l[]="service_cmd=/etc/init.d/redis-server";
	$l[]="service_disabled=$enabled";
	$l[]="family=proxy";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);return;}

	$master_pid=redis_pid();

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
				
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/redis-server start >/dev/null 2>&1 &");
				
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;
}










//========================================================================================================================================================
function rdpproxy_pid(){
	$unix=new unix();
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/redemption/rdpproxy.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	$Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("rdpproxy");
	return $GLOBALS["CLASS_UNIX"]->PIDOF($Masterbin);

}
//========================================================================================================================================================
function rdpproxy_authhook_pid(){
	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
	exec("$pgrep -l -f \"authhook.py\" 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#pgrep#", $ligne)){continue;}
		if(!preg_match("#^([0-9]+)\s+#", $ligne,$re)){continue;}
		return $re[1];
	}

}
//========================================================================================================================================================
function rdpproxy_version(){
	if(isset($GLOBALS["rdpproxy_version"])){return $GLOBALS["rdpproxy_version"];}
	$Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("rdpproxy");
	exec("$Masterbin --version 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(!preg_match("#Version\s+([0-9\.]+)#i", $ligne,$re)){continue;}
		$GLOBALS["rdpproxy_version"]= $re[1];
		return $GLOBALS["rdpproxy_version"];
	}
}
//========================================================================================================================================================
function rdpproxy(){
	$Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("rdpproxy");
	if(!is_file($Masterbin)){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}return null;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRDPProxy");
	if(!is_numeric($enabled)){$enabled=0;}
	$master_pid=rdpproxy_pid();
	

	$l[]="[APP_RDPPROXY]";
	$l[]="service_name=APP_RDPPROXY";
	$l[]="master_version=".rdpproxy_version();
	$l[]="service_cmd=/etc/init.d/rdpproxy";
	$l[]="service_disabled=$enabled";
	$l[]="family=proxy";
	$l[]="pid_path=";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.rdpproxy.php --start");
			shell_exec2($cmd);
			
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}else{
		if($enabled==0){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/rdpproxy stop >/dev/null 2>&1 &");
		}
	}

	@file_put_contents("/var/run/redemption/rdpproxy.pid", $master_pid);
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================
function rdpproxy_authhook(){
	$Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("rdpproxy");
	if(!is_file($Masterbin)){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}return null;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRDPProxy");
	if(!is_numeric($enabled)){$enabled=0;}
	$master_pid=rdpproxy_authhook_pid();
	

	$l[]="[APP_RDPPROXY_AUTHHOOK]";
	$l[]="service_name=APP_RDPPROXY_AUTHHOOK";
	$l[]="master_version=".rdpproxy_version();
	$l[]="service_cmd=/etc/init.d/rdpproxy-authhook";
	$l[]="service_disabled=$enabled";
	$l[]="family=proxy";
	$l[]="pid_path=";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.rdpproxy.php --authhook-start");
			shell_exec2($cmd);
				
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}else{
		if($enabled==0){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/rdpproxy-authhook stop >/dev/null 2>&1 &");
		}
	}


	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================

//========================================================================================================================================================
function dnsmasq_version($binpath=null){
	$key=md5(__FUNCTION__.$binpath);
	if(isset($GLOBALS[$key])){return $GLOBALS[$key];}
	if($binpath==null){$binpath=$GLOBALS["CLASS_UNIX"]->find_program("dnsmasq");}
	if(!is_file($binpath)){return 0;}

	exec("$binpath --version 2>&1",$array);
	while (list ($pid, $line) = each ($array) ){
		if(preg_match("#version\s+([0-9a-z\.]+)\s+Copyright#i", $line,$re)){
			$GLOBALS[$key]=$re[1];
			return $re[1];}
			if($GLOBALS['VERBOSE']){echo "dnsmasq_version(), $line, not found \n";}
	}

}
//========================================================================================================================================================



//========================================================================================================================================================
function haarp(){
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("haarp");
	if(!is_file($bin)){return;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableHaarp");
	if(!is_numeric($enabled)){$enabled=0;}
	$pid_path="/var/run/haarp.pid";
	$master_pid=trim(@file_get_contents($pid_path));


	$l[]="[APP_HAARP]";
	$l[]="service_name=APP_HAARP";
	$l[]="master_version=".haarp_version();
	$l[]="service_cmd=/etc/init.d/haarp";
	$l[]="service_disabled=$enabled";
	$l[]="family=proxy";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";

	
	
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin);
	}
	
	
	if($enabled==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			ToSyslog("Shutdown Haarp daemon EnableHaarp == 0");
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.haarp.php --stop >/dev/null 2>&1 &");
			
			
		}
		return implode("\n",$l);
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.initslapd.php --haarp >/dev/null 2>&1");
			shell_exec2($cmd);
			squid_admin_mysql(0, "HAARP not running, start it...","Bin: $bin, PID: $pid_path, master_pid:$master_pid");
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.haarp.php --start >/dev/null 2>&1 &");
			shell_exec2($cmd);
				
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}


	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.haarp.php --status >/dev/null 2>&1 &");
	shell_exec2($cmd);

	return implode("\n",$l);return;
}
//========================================================================================================================================================
function haarp_version(){
	return "1.1";
	if(isset($GLOBALS["haarp_version"])){return $GLOBALS["haarp_version"];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("haarp");
	if(!is_file($bin)){return 0;}

	exec("$bin -h 2>&1",$array);
	while (list ($pid, $line) = each ($array) ){
		if(preg_match("#Haarp Version\s+([0-9\.\-]+)#i", $line,$re)){
			$GLOBALS["haarp_version"]=$re[1];
			return $re[1];}
			if($GLOBALS['VERBOSE']){echo "haarp_version(), $line, not found \n";}
	}

}

//========================================================================================================================================================
function nginx(){
	
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("nginx");
	if(!is_file($bin)){$GLOBALS["CLASS_USERS"]->NGINX_INSTALLED=false;}
	
	$MEMORY=$GLOBALS["CLASS_UNIX"]->MEM_TOTAL_INSTALLEE();
	if($MEMORY<624288){return;}
	
	if(!$GLOBALS["CLASS_USERS"]->NGINX_INSTALLED){
		$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.nginx.php --install-nginx >/dev/null 2>&1 &");
		shell_exec2($cmd);
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}
		return null;
	}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx");
	if(!is_numeric($enabled)){$enabled=1;}
	$pid_path="/var/run/nginx.pid";
	$master_pid=trim(@file_get_contents($pid_path));


	$l[]="[APP_NGINX]";
	$l[]="service_name=APP_NGINX";
	$l[]="master_version=".nginx_version();
	$l[]="service_cmd=/etc/init.d/nginx";
	$l[]="service_disabled=$enabled";
	$l[]="family=proxy";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);return;}
	
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin);
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			apache_admin_mysql(0, "Nginx Web service was stopped [action=start]", null,__FILE__,__LINE__);
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.nginx.php --start >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}


	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	$prefixcmd="{$GLOBALS["NICE"]} {$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} ";
	
	$timeFile=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.nginx.stats.hours.php.tables_hours.time");
	if($timeFile>60){
		$cmd=trim($prefixcmd.dirname(__FILE__)."/exec.nginx.stats.hours.php >/dev/null 2>&1 &");
		shell_exec2($cmd);
	}
	
	if(!$GLOBALS["CLASS_UNIX"]->is_socket("/var/run/nginx-authenticator.sock")){
		$cmd=trim($prefixcmd.dirname(__FILE__)."/exec.nginx.php --authenticator >/dev/null 2>&1 &");
	}
	
	$timeFile=$GLOBALS["CLASS_UNIX"]->file_time_min("/usr/share/artica-postfix/ressources/logs/web/nginx.status.acl");
	if($timeFile>5){
		$cmd=trim($prefixcmd.dirname(__FILE__)."/exec.nginx.php --status >/dev/null 2>&1 &");
		shell_exec2($cmd);	
	}
	
	$timeFile=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.nginx.php.test_sources.time");
	if($timeFile>15){
		$cmd=trim($prefixcmd.dirname(__FILE__)."/exec.nginx.php --tests-sources >/dev/null 2>&1 &");
		shell_exec2($cmd);
	}
	return implode("\n",$l);return;
}
//========================================================================================================================================================
function nginx_version(){
	if(isset($GLOBALS["nginx_version"])){return $GLOBALS["nginx_version"];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("nginx");
	if(!is_file($bin)){return 0;}
	
	exec("$bin -v 2>&1",$array);
	while (list ($pid, $line) = each ($array) ){
			if(preg_match("#\/([0-9\.\-]+)#i", $line,$re)){
				$GLOBALS["nginx_version"]=$re[1];
				return $re[1];}
			if($GLOBALS['VERBOSE']){echo "nginx_version(), $line, not found \n";}
		}	
	
}
//========================================================================================================================================================
function syslog_db(){
	
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSyslogDB");
	if(!is_numeric($enabled)){$enabled=0;}
	if($enabled==0){
		if($GLOBALS["VERBOSE"]){echo "Failed: EnableSyslogDB = $enabled\n";}
		return;}
	$MySQLSyslogType=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLSyslogType");
	if(!is_numeric($MySQLSyslogType)){$MySQLSyslogType=1;}
	if($GLOBALS["VERBOSE"]){echo "MySQLSyslogType  = $MySQLSyslogType\n";}
	if($MySQLSyslogType<>1){return;}
	
	$pid_path="/var/run/syslogdb.pid";
	$master_pid=trim(@file_get_contents($pid_path));


	$l[]="[APP_SYSLOG_DB]";
	$l[]="service_name=APP_SYSLOG_DB";
	$l[]="master_version="._MYSQL_VERSION();
	$l[]="service_cmd=/etc/init.d/syslog-db";
	$l[]="service_disabled=$enabled";
	$l[]="family=proxy";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$unix=new unix();
		$mysqld=$unix->LOCATE_mysqld_bin();
		$master_pid=$unix->PIDOF_PATTERN("$mysqld.*?syslogdb.sock");
	}
		
		
		
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){	
		if($GLOBALS["VERBOSE"]){echo "Not running !!!\n";}
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.logs-db.php --init");
			shell_exec2($cmd);
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/syslog-db restart >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}
	
	
	if(!$GLOBALS["CLASS_UNIX"]->is_socket("/var/run/syslogdb.sock")){
		if($GLOBALS["VERBOSE"]){echo "/var/run/syslogdb.sock no such socket !!!\n";}
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.logs-db.php --init");
			shell_exec2($cmd);
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/syslog-db restart >/dev/null 2>&1 &");
		}
	}else{
		if($GLOBALS["VERBOSE"]){echo "/var/run/syslogdb.sock socket OK !!!\n";}
	}


	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.logs-db.php --databasesize >/dev/null 2>&1 &");
	//shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid-db.php --statistics >/dev/null 2>&1 &");
	return implode("\n",$l);return;
}
//========================================================================================================================================================
function nginx_db(){

	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginxStats");
	if(!is_numeric($enabled)){$enabled=0;}
	if($enabled==0){return;}
	$MySQLSyslogType=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLNgnixType");
	if(!is_numeric($MySQLSyslogType)){$MySQLSyslogType=1;}
	if($MySQLSyslogType<>1){return;}

	$pid_path="/var/run/nginxdb.pid";
	$master_pid=trim(@file_get_contents($pid_path));


	$l[]="[APP_NGINXDB]";
	$l[]="service_name=APP_NGINXDB";
	$l[]="master_version="._MYSQL_VERSION();
	$l[]="service_cmd=/etc/init.d/nginx-db";
	$l[]="service_disabled=$enabled";
	$l[]="family=proxy";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.nginx-db.php --init");
			shell_exec2($cmd);
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/nginx-db restart >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}


	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.nginx-db.php --databasesize >/dev/null 2>&1 &");
	return implode("\n",$l);return;
}




function zarafa_licensed(){

	if(!$GLOBALS["CLASS_USERS"]->ZARAFA_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}
		return null;
	}

	$enabled=1;
	if(!is_file("/etc/zarafa/license/base")){$enabled=0;}
	$pid_path="/var/run/zarafa-licensed.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	
	if($enabled==1){
			$enabledGLobal=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaEnableServer");
			if(is_numeric($enabledGLobal)){if($enabledGLobal==0){$enabled=0;}}
	}

	$l[]="[APP_ZARAFA_LICENSED]";
	$l[]="service_name=APP_ZARAFA_LICENSED";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->ZARAFA_VERSION();
	$l[]="service_cmd=/etc/init.d/zarafa-licensed";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="remove_cmd=--zarafa-remove";
	$l[]="watchdog_features=1";
	$l[]="family=mailbox";
	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
	if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/zarafa-licensed start >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";$l[]="";

	}else{
		$l[]="running=1";
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================
function zarafa_indexer(){

	if(!$GLOBALS["CLASS_USERS"]->ZARAFA_INDEXER_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}
		return null;
	}
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program("zarafa-indexer");
	if(!is_file($binpath)){return;}
	
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableZarafaIndexer");
	if(!is_numeric($enabled)){$enabled=0;}
	$pid_path="/var/run/zarafa-indexer.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	
	if($enabled==1){
		$enabledGlobal=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaEnableServer");
		if(is_numeric($enabledGlobal)){if($enabledGlobal==0){$enabled=0;}}
	}

	$l[]="[APP_ZARAFA_INDEXER]";
	$l[]="service_name=APP_ZARAFA_INDEXER";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->ZARAFA_VERSION();
	$l[]="service_cmd=/etc/init.d/zarafa-indexer";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="remove_cmd=--zarafa-remove";
	$l[]="watchdog_features=1";
	$l[]="family=mailbox";
	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
	if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/zarafa-indexer start >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";$l[]="";

	}else{
		$l[]="running=1";
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================



function zarafa_watchdog(){
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaEnableServer");
	if(!is_numeric($enabled)){$enabled=1;}
	
	if($enabled==0){return;}
	$pid_path="/var/run/zarafa-server.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	$text[]="This is watchdog the report for Zarafa server ";
	$text[]="Pid: $master_pid";


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$text[]="Running No : -> running watchdog";
		$text[]="Process report: ".zarafa_server();
	}else{
		$text[]="Running Yes :";
	}

	$text[]="Mysql server status:\n---------------\n".mysql_server();

	$GLOBALS["CLASS_UNIX"]->send_email_events("Zarafa watchdog report",@implode("\n",$text),"mailbox");

}

function freeradius_version(){
	$unix=new unix();
	$freeradius=$unix->find_program("freeradius");
	exec("$freeradius -v 2>&1",$results);
	while (list ($dir, $val) = each ($results) ){
		if(!preg_match("#Version ([0-9\.]+)#", $val,$re)){continue;}
		return $re[1];
	}

}

function freeradius(){

	if(!$GLOBALS["CLASS_USERS"]->FREERADIUS_INSTALLED){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}return null;}

	$enabled=1;
	$pid_path="/var/run/freeradius/freeradius.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	$freeradius=$GLOBALS["CLASS_UNIX"]->find_program("freeradius");
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFreeRadius");
	if(!is_numeric($enabled)){$enabled=0;}

	$l[]="[APP_FREERADIUS]";
	$l[]="service_name=APP_FREERADIUS";
	$l[]="master_version=".freeradius_version();
	$l[]="service_cmd=freeradius";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=system";

	if($enabled==0){return implode("\n",$l);return;}
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($freeradius);
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_FREERADIUS","freeradius");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;



}

function zarafa_server2(){

	if(!$GLOBALS["CLASS_USERS"]->ZARAFA_INSTALLED){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}return null;}

	$enabled=1;
	$pid_path="/var/run/zarafa-server2.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaDBEnable2Instance");
	if(!is_numeric($enabled)){$enabled=0;}

	$l[]="[APP_ZARAFA_SERVER2]";
	$l[]="service_name=APP_ZARAFA_SERVER2";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->ZARAFA_VERSION();
	$l[]="service_cmd=zarafa2";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="remove_cmd=--zarafa-remove";
	$l[]="watchdog_features=1";
	$l[]="family=mailbox";

	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_ZARAFA_SERVER2","zarafa2");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);return;
	}
	
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;	
	
	
	
}

function zarafa_server(){

	if(!$GLOBALS["CLASS_USERS"]->ZARAFA_INSTALLED){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}return null;}
	
	if(is_file("/etc/artica-postfix/ZARFA_FIRST_INSTALL")){
		shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.zarafa.build.stores.php --remove-database >/dev/null 2>&1 &");
	}
	
	$enabled=1;
	$pid_path="/var/run/zarafa-server.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaEnableServer");
	if(!is_numeric($enabled)){$enabled=1;}
	$master_version=$GLOBALS["CLASS_UNIX"]->ZARAFA_VERSION();
	$l[]="[APP_ZARAFA_SERVER]";
	$l[]="service_name=APP_ZARAFA_SERVER";
	$l[]="master_version=$master_version";
	$l[]="service_cmd=/etc/init.d/zarafa-server";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="remove_cmd=--zarafa-remove";
	$l[]="watchdog_features=1";
	$l[]="family=mailbox";
	
	if(is_dir("/usr/share/zarafa-webaccess")){
		if(!is_file("/usr/share/zarafa-webaccess/VERSION")){
			@file_put_contents("/usr/share/zarafa-webaccess/VERSION", $master_version);
		}
	}
	
	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_ZARAFA","zarafa");
		$l[]="running=0\ninstalled=1";$l[]="";

	}else{
		$l[]="running=1";
	}



	$meme=GetMemoriesOf($master_pid);
	$l[]=$meme;
	$l[]="";
	$l[]="[APP_ZARAFA]";
	$l[]="service_name=APP_ZARAFA";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->ZARAFA_VERSION();
	$l[]="family=mailbox";
	$l[]="service_cmd=/etc/init.d/zarafa-server";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="remove_cmd=--zarafa-remove";
	$l[]="watchdog_features=1";

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		shell_exec("/etc/init.d/zarafa-server start");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}
	if(!$GLOBALS["DISABLE_WATCHDOG"]){
		$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup")." ";
		if($GLOBALS["PHP5"]==null){$GLOBALS["PHP5"]=LOCATE_PHP5_BIN2();}
		$cmd=trim($nohup."{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.zarafa.build.stores.php --export-hash >/dev/null 2>&1 &");
		events("running $cmd",__FUNCTION__,__LINE__);
		shell_exec2($cmd);
	}
	
	zarafa_mapi();
	$l[]="running=1";
	$l[]=$meme;
	$l[]="";
	

	
	return implode("\n",$l);;
}
//========================================================================================================================================================

function zarafa_multi(){
	if(!is_file("/usr/share/artica-postfix/exec.zarafa-multi.php")){return;}
	if($GLOBALS["DISABLE_WATCHDOG"]){$add=" --nowtachdog";}
	$EnableZarafaMulti=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableZarafaMulti");
	if(!is_numeric($EnableZarafaMulti)){$EnableZarafaMulti=0;}
	$php5=$GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN();
	$cmd="$php5 /usr/share/artica-postfix/exec.zarafa-multi.php --status$add 2>&1";
	exec($cmd,$results);
	return @implode("\n", $results);	
	
}

function vmtools_pid(){
	if(is_file("/var/run/vmware-guestd.pid")){return "/var/run/vmware-guestd.pid";}
	if(is_file("/var/run/vmtoolsd.pid")){return "/var/run/vmtoolsd.pid";}
	
}

function vmtools_version_text(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	if(!is_file("/etc/vmware-tools/manifest.txt.shipped")){return;}
	$f=file("/etc/vmware-tools/manifest.txt.shipped");
	while (list ($i, $line) = each ($f)){
		if(preg_match("#guestd\.version.+?([0-9\.]+)#",$line,$re)){
			$GLOBALS[__FUNCTION__]=$re[1];
			return $GLOBALS[__FUNCTION__];
		}
	}
	if(is_file("/usr/bin/vmware-toolbox-cmd")){	
		exec("/usr/bin/vmware-toolbox-cmd -v 2>&1",$results);
		$GLOBALS[__FUNCTION__]=trim(@implode("", $results));
		if(preg_match("#(.+?)\s+#", $GLOBALS[__FUNCTION__],$re)){$GLOBALS[__FUNCTION__]=$re[1];}
		return $GLOBALS[__FUNCTION__];
	}
	
}

function vmtools(){
	$binpath=_vmtools_bin_path();
	if($binpath==null){return null;}
	$enabled=1;
	$pid_path=vmtools_pid();
	$master_pid=trim(@file_get_contents($pid_path));
	if($master_pid==null){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
	}

	$l[]="[APP_VMTOOLS]";
	$l[]="service_name=APP_VMTOOLS";
	$l[]="master_version=".vmtools_version_text();
	$l[]="service_cmd=vmtools";
	$l[]="service_disabled=$enabled";

	$l[]="family=system";
	$l[]="pid_path=$pid_path";
	$l[]="binpath=$binpath";
	//$l[]="remove_cmd=--zarafa-remove";

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_VMTOOLS","vmtools");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}

function _vmtools_bin_path(){
	if(is_file("/usr/sbin/vmtoolsd")){return "/usr/sbin/vmtoolsd";}
	if(is_file("/usr/sbin/vmware-guestd")){return "/usr/sbin/vmware-guestd";}
	if(is_file("/usr/lib/vmware-tools/bin32/vmware-user-loader")){return "/usr/lib/vmware-tools/bin32/vmware-user-loader";}
}

//========================================================================================================================================================
function hamachi_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	exec("/usr/bin/hamachi 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#version.+?([0-9\.]+)#", $ligne,$re)){
			$GLOBALS[__FUNCTION__]=$re[1];
			return $GLOBALS[__FUNCTION__];
		}
	}
}

//========================================================================================================================================================


function hamachi(){
	if(!is_file("/opt/logmein-hamachi/bin/hamachid")){return null;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableHamachi");
	if(!is_numeric($enabled)){$enabled=1;}
	$pid_path="/var/run/logmein-hamachi/hamachid.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF("/opt/logmein-hamachi/bin/hamachid");
	}

	$l[]="[APP_AMACHI]";
	$l[]="service_name=APP_AMACHI";
	$l[]="master_version=".hamachi_version();
	$l[]="service_cmd=amachi";
	$l[]="family=network";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";

	if($enabled==0){return implode("\n",$l);return;}
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_HAMACHI","hamachi");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}	
	
	
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================
function ejabberd_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program("ejabberdctl");
	exec("$binpath status 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#ejabberd\s+([0-9\.]+)\s+#", $ligne,$re)){
			$GLOBALS[__FUNCTION__]=$re[1];
			return $GLOBALS[__FUNCTION__];
		}
	}
}
function ejabberd_bin(){
	
	if(is_file("/usr/lib/erlang/erts-5.8/bin/beam")){return "/usr/lib/erlang/erts-5.8/bin/beam";}
	
}
//========================================================================================================================================================
function ejabberd(){
	if(!$GLOBALS["CLASS_USERS"]->EJABBERD_INSTALLED){return null;}
	
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ejabberdEnabled");
	if(!is_numeric($enabled)){$enabled=1;}
	$pid_path="/var/run/ejabberd/ejabberd.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$binpath=ejabberd_bin();
		if($binpath<>null){
			$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
		}
	}
	$version=ejabberd_version();
	@file_put_contents("/etc/artica-postfix/ejabberd_version", $version);
	$l[]="[APP_EJABBERD]";
	$l[]="service_name=APP_EJABBERD";
	$l[]="master_version=$version";
	$l[]="service_cmd=ejabberd";
	$l[]="family=network";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";

	if($enabled==0){return implode("\n",$l);return;}
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_EJABBERD","ejabberd");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}	else{
		if($enabled==0){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/artica-postfix stop ejabberd >/dev/null 2>&1 &");
		}
	}
	
	
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

	
}
//========================================================================================================================================================
function pymsnt_pgrep(){
	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
	exec("$pgrep -l -f \"/usr/share/pymsnt/PyMSNt.py\" 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#pgrep#", $ligne)){continue;}
		if(preg_match("#^([0-9]+)#", $ligne,$re)){return $re[1];}
	}
	
}
//========================================================================================================================================================
function pymsnt_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$binpath="/usr/share/pymsnt/src/legacy/glue.py";
	if(!is_file($binpath)){return "0.0";}
	$results=file($binpath);

	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#version.*?=.*?([0-9\.]+)#", $ligne,$re)){
			$GLOBALS[__FUNCTION__]=$re[1];
			return $GLOBALS[__FUNCTION__];
		}
	}
}
//========================================================================================================================================================
function pymsnt(){
	if(!$GLOBALS["CLASS_USERS"]->EJABBERD_INSTALLED){return null;}
	if(!$GLOBALS["CLASS_USERS"]->PYMSNT_INSTALLED){return null;}
	
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ejabberdEnabled");
	if(!is_numeric($enabled)){$enabled=1;}
	$pid_path="/var/run/pymsnt/pymsnt.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$master_pid=pymsnt_pgrep();}
	
	
	$version=pymsnt_version();
	@file_put_contents("/etc/artica-postfix/pymsnt_version", $version);
	$l[]="[APP_PYMSNT]";
	$l[]="service_name=APP_PYMSNT";
	$l[]="master_version=$version";
	$l[]="service_cmd=pymsnt";
	$l[]="family=network";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";

	if($enabled==0){return implode("\n",$l);return;}
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_PYMSNT","pymsnt");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}else{
		if($enabled==0){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/artica-postfix stop pymsnt >/dev/null 2>&1 &");
		}
	}	
	
	
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

	
}
//========================================================================================================================================================



function artica_notifier(){


	$binpath=$GLOBALS["CLASS_UNIX"]->find_program('emailrelay');
	if($binpath==null){return;}


	$l[]="[APP_ARTICA_NOTIFIER]";
	$l[]="service_name=APP_ARTICA_NOTIFIER";
	$l[]="service_cmd=artica-notifier";
	$l[]="master_version=".GetVersionOf("emailrelay");

	if(!is_file("/etc/artica-postfix/smtpnotif.conf")){
		$l[]="service_disabled=0";
		return implode("\n",$l);
		return;
	}

	$ini=new Bs_IniHandler("/etc/artica-postfix/smtpnotif.conf");
	if($ini->_params["SMTP"]["enabled"]<>1){
		$l[]="service_disabled=0";
		return implode("\n",$l);
		return;
	}

	$l[]="service_disabled=1";
	$pid_path="/var/run/artica-notifier.pid";
	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);
	$l[]="service_cmd=artica-notifier";
	$l[]="service_disabled=1";
	$l[]="family=system";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_ARTICA_NOTIFIER","artica-notifier");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	//$l[]="remove_cmd=--zarafa-remove";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);return;
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================

function autofs(){
	if(!$GLOBALS["CLASS_USERS"]->autofs_installed){
		if($GLOBALS["VERBOSE"]){echo "autofs_installed FALSE\n";}
		return;
	}
	if(!is_file('/etc/init.d/autofs')){
		if($GLOBALS["VERBOSE"]){echo "/etc/init.d/autofs no such file.\n";}
		return;
	}
	
	if(!is_dir("/automounts")){
		@mkdir("/automounts",0755,true);
		shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/artica-postfix restart autofs >/dev/null 2>&1 &");
	}
	
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program('automount');
	if($binpath==null){
		if($GLOBALS["VERBOSE"]){echo "automount no such binary.\n";}
		return;
	}
	if(is_file("/var/run/autofs-running")){$pid_path="/var/run/autofs-running";}
	if($pid_path==null){if(is_file("/var/run/automount.pid")){$pid_path="/var/run/automount.pid";}}

	$Enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("AutoFSEnabled");
	if(!is_numeric($Enabled)){$Enabled=1;}
	$AutoFSCountDirs=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("AutoFSCountDirs");
	if(!is_numeric($AutoFSCountDirs)){$AutoFSCountDirs=0;}
	if($AutoFSCountDirs==0){$Enabled=0;}


	if($pid_path<>null){$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);}
	if(!is_numeric($master_pid)){$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($binpath);}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
	}
	$l[]="[APP_AUTOFS]";
	$l[]="service_name=APP_AUTOFS";
	$l[]="service_cmd=autofs";
	$l[]="master_version=".GetVersionOf("autofs");
	$l[]="service_disabled=$Enabled";
	$l[]="family=network";
	$l[]="watchdog_features=1";

	if($Enabled==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/autofs stop >/dev/null 2>&1 &");
		}
		return implode("\n",$l);}
	
	

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_AUTOFS","autofs");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}




function greyhole(){

	if(!$GLOBALS["CLASS_USERS"]->GREYHOLE_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo "GREYHOLE_INSTALLED FALSE\n";}
		return;
	}

	$EnableGreyhole=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGreyhole");
	if(!is_numeric($EnableGreyhole)){$EnableGreyhole=1;}

	$binpath=$GLOBALS["CLASS_UNIX"]->find_program('greyhole');
	if($binpath==null){
		if($GLOBALS["VERBOSE"]){echo "automount no such binary.\n";}
		return;
	}
	if(is_file("/var/run/greyhole.pid")){$pid_path="/var/run/greyhole.pid";}



	if($pid_path<>null){$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);}
	if(!is_numeric($master_pid)){$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN($binpath);}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
	}

	if(!is_file("/etc/greyhole.conf")){$EnableGreyhole=0;}

	$l[]="[APP_GREYHOLE]";
	$l[]="service_name=APP_GREYHOLE";
	$l[]="service_cmd=greyhole";
	$l[]="master_version=".GetVersionOf("greyhole");
	$l[]="service_disabled=$EnableGreyhole";
	$l[]="family=network";
	$l[]="watchdog_features=1";
	if($EnableGreyhole==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_GREYHOLE","greyhole");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}


function greyhole_watchdog(){

	$greyhole=$GLOBALS["CLASS_UNIX"]->find_program('greyhole');
	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program('pgrep');
	if(!is_file($greyhole)){
		events("greyhole is not installed",__FUNCTION__,__LINE__);
		return;
	}
	$kill=$GLOBALS["CLASS_UNIX"]->find_program('kill');
	events("$pgrep -l -f \"$greyhole --fsck\" 2>&1",__FUNCTION__,__LINE__);
	exec("$pgrep -l -f \"$greyhole --fsck\"",$results);
	if(count($results)==0){return;}
	while (list ($key, $value) = each ($results) ){
		events("$value",__FUNCTION__,__LINE__);
		if(!preg_match("#^([0-9]+)\s+#",$value,$re)){continue;}
		$pid=$re[1];
		if($GLOBALS["CLASS_UNIX"]->PID_IS_CHROOTED($pid)){continue;}
		$time=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($pid);
		events("Found pid $pid, $time minutes",__FUNCTION__,__LINE__);
		if(!is_file("/etc/greyhole.conf")){
			events("/etc/greyhole.conf no such file, kill process",__FUNCTION__,__LINE__);
			$GLOBALS["CLASS_UNIX"]->KILL_PROCESS($pid,9);
			
			continue;
		}
		if($time>120){
			events("killing PID $pid",__FUNCTION__,__LINE__);
			$GLOBALS["CLASS_UNIX"]->KILL_PROCESS($pid,9);
			$GLOBALS["CLASS_UNIX"]->send_email_events("greyhole process $pid was killed after {$time}Mn execution",
			"It reach max execution time : 120Mn ","system"
			);
		}

	}
}

function snort(){
	if(!$GLOBALS["CLASS_USERS"]->SNORT_INSTALLED){if($GLOBALS["VERBOSE"]){echo "SNORT_INSTALLED FALSE\n";}return;}

	$EnableSnort=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSnort");
	if($GLOBALS["VERBOSE"]){echo "EnableSnort = $EnableSnort\n";}
	if(!is_numeric($EnableSnort)){$EnableSnort=0;}
	$snortInterfaces=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SnortNics")));
	if(count($snortInterfaces)==0){$EnableSnort=0;}

	$binpath=$GLOBALS["CLASS_UNIX"]->find_program('snort');
	if($binpath==null){if($GLOBALS["VERBOSE"]){echo "snort no such binary.\n";}return;}
	if($GLOBALS["VERBOSE"]){echo "EnableSnort = $EnableSnort\n";}	
	
	
	if($EnableSnort==0){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cz=0;
			$kill=$GLOBALS["CLASS_UNIX"]->find_program('kill');
			if($GLOBALS["VERBOSE"]){echo "$binpath = PID?\n";}
			$pid=$GLOBALS["CLASS_UNIX"]->PIDOF($binpath,true);
			while ($pid>50) {
				$cz++;
				system_admin_events("Snort pid $pid was killed, it is not enabled", __FUNCTION__, __FILE__, __LINE__, "watchdog");
				unix_system_kill_force($pid);
				$pid=$GLOBALS["CLASS_UNIX"]->PIDOF($binpath,true);
				if($cz>10){system_admin_events("Break loop after 10 attempts...", __FUNCTION__, __FILE__, __LINE__, "watchdog");break;}
				sleep(1);
			}
		}
		
		$l[]="[APP_SNORT]";
		$l[]="service_name=APP_SNORT";
		$l[]="service_cmd=snort";
		$l[]="master_version="._snort_version();
		$l[]="service_disabled=$EnableSnort";
		$l[]="family=network";
		$l[]="watchdog_features=1";
		return implode("\n",$l);
	}



	while (list ($eth, $ligne) = each ($snortInterfaces) ){

		$l[]="[APP_SNORT:$eth]";
		$l[]="service_name=APP_SNORT";
		$l[]="service_cmd=snort";
		$l[]="master_version="._snort_version();
		$l[]="service_disabled=$EnableSnort";
		$l[]="family=network";
		$l[]="watchdog_features=1";


		$pidpath="/var/run/snort_$eth.pid";
		$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pidpath);
		if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			WATCHDOG("APP_SNORT (Nic:$eth)","snort");
			$l[]="running=0\ninstalled=1";$l[]="";
		}else{
			$l[]="running=1";
			$l[]=GetMemoriesOf($master_pid);
			$l[]="";
				
		}
	}

	return implode("\n",$l);return;


}

function _snort_pid(){
	if(is_file("/var/run/snort_eth0.pid")){return "/var/run/snort_eth0.pid";}
}
function _snort_version(){
	if(!isset($GLOBALS["SNORT_PATH"])){$GLOBALS["CLASS_UNIX"]=new unix();$GLOBALS["SNORT_PATH"]=$GLOBALS["CLASS_UNIX"]->find_program("snort");}
	exec("{$GLOBALS["SNORT_PATH"]} -V 2>&1",$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#Version\s+([0-9\.]+)#",$line,$re)){return $re[1];}

	}
	return 0;
}


function snmpd_pid(){
	$pid_path="/var/run/snmpd.pid";
	$pid=trim(@file_get_contents($pid_path));
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF($GLOBALS["CLASS_UNIX"]->find_program("snmpd"));
}
function snmpd_version(){
	if(isset($GLOBALS["SNMPD_VERSION"])){return $GLOBALS["SNMPD_VERSION"];}
	$snmpd=$GLOBALS["CLASS_UNIX"]->find_program("snmpd");
	exec("$snmpd -v 2>&1",$results);
	while (list ($i, $line) = each ($results)){
		if(preg_match("#NET-SNMP version:\s+([0-9\.]+)#i", $line,$re)){
			$GLOBALS["SNMPD_VERSION"]=$re[1];
			return $re[1];
		}
	}

}
function snmpd(){
	$snmpd=$GLOBALS["CLASS_UNIX"]->find_program("snmpd");
	if(!is_file($snmpd)){return;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSNMPD");
	if(!is_numeric($enabled)){$enabled=0;}
	$pid_path="/var/run/snmpd.pid";
	$master_pid=snmpd_pid();

	$l[]="[APP_SNMPD]";
	$l[]="service_name=APP_SNMPD";
	$l[]="master_version=".snmpd_version();
	$l[]="service_cmd=/etc/init.d/snmpd";
	$l[]="service_disabled=$enabled";
	$l[]="family=system";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.snmpd.php --start");
			shell_exec2($cmd);
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}else{
		if($enabled==0){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/snmpd stop >/dev/null 2>&1 &");
		}
	}


	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}


function vsftpd_pid(){

	
	$Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("vsftpd");
	$pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("^vsftpd$");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN($Masterbin);

}



function vsftpd(){
	$snmpd=$GLOBALS["CLASS_UNIX"]->find_program("vsftpd");
	if(!is_file($snmpd)){return;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableVSFTPDDaemon");
	if(!is_numeric($enabled)){$enabled=0;}
	
$master_pid=vsftpd_pid();
	$l[]="[APP_VSFTPD]";
	$l[]="service_name=APP_VSFTPD";
	$l[]="master_version=2.3.5";
	$l[]="service_cmd=/etc/init.d/vsftpd";
	$l[]="service_disabled=$enabled";
	$l[]="family=system";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.vsftpd.php --start");
			shell_exec2($cmd);
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}else{
		if($enabled==0){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/vsftpd stop >/dev/null 2>&1 &");
		}
	}


	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}






function dnsmasq(){
	
	if(!$GLOBALS["CLASS_USERS"]->dnsmasq_installed){
		if($GLOBALS["VERBOSE"]){echo "dnsmasq_installed FALSE\n";}
		return;
	}
	
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program('dnsmasq');
	if($binpath==null){if($GLOBALS["VERBOSE"]){echo "dnsmasq no such binary.\n";}return;}	
	$EnableDNSMASQ=$GLOBALS["CLASS_SOCKETS"]->dnsmasq_enabled();
	$NoStopBind9=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NoStopBind9");
	
	if(!is_numeric($NoStopBind9)){$NoStopBind9=0;}

	

	if($GLOBALS["CLASS_USERS"]->BIND9_INSTALLED){
		if($NoStopBind9==0){
			$namedbin=$GLOBALS["CLASS_UNIX"]->find_program("named");
			$namedpid=$GLOBALS["CLASS_UNIX"]->PIDOF("$namedbin");
			
			if($GLOBALS["CLASS_UNIX"]->process_exists($namedpid)){
				$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
				$kill=$GLOBALS["CLASS_UNIX"]->find_program("kill");
				$GLOBALS["CLASS_UNIX"]->KILL_PROCESS($namedpid,9);
				$cmd="$nohup {$GLOBALS["NICE"]}/etc/init.d/dnsmasq restart >/dev/null 2>&1 &";
				shell_exec2($cmd);
				$GLOBALS["CLASS_UNIX"]->send_email_events("Stopping bind9 Pid $namedpid","Artica has stopped bind9 process\nthis to prevent port conflicts with DnsMasq or PowerDNS.\nIf you did not want to Artica perform this operation do this operation:\necho \"1\" >/etc/artica-postfix/settings/Daemons/NoStopBind9\n","system");
			}
		}
	}

	$master_pid=_dnsmasq_pid();
	
	


	$l[]="[DNSMASQ]";
	$l[]="service_name=APP_DNSMASQ";
	$l[]="service_cmd=/etc/init.d/dnsmasq";
	$l[]="master_version=".dnsmasq_version();
	$l[]="service_disabled=$EnableDNSMASQ";
	$l[]="family=network";
	$l[]="watchdog_features=1";
	if($EnableDNSMASQ==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
			shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.initslapd.php --dnsmasq >/dev/null 2>&1 &");
			shell_exec2("$nohup {$GLOBALS["NICE"]} /etc/init.d/dnsmasq start >/dev/null 2>&1 &");
				
		}

		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.dnsmasq.php --varrun >/dev/null 2>&1 &");
	return implode("\n",$l);return;

}

function _dnsmasq_pid(){
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/dnsmasq.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	$Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("dnsmasq");
	return $GLOBALS["CLASS_UNIX"]->PIDOF($Masterbin);

}

function dhcpd_version() {
	if(isset($GLOBALS["DHCPD_VERSION"])){return $GLOBALS["DHCPD_VERSION"];}
	if($GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/DHCPD_VER")<60){
		return trim(@file_get_contents("/etc/artica-postfix/DHCPD_VER"));
	}
	
	$dhcpd_server=$GLOBALS["CLASS_UNIX"]->find_program("dhcpd");
	if(!is_file($dhcpd_server)){$dhcpd_server=$GLOBALS["CLASS_UNIX"]->find_program("dhcpd3");}
	
	exec("$dhcpd_server -V 2>&1",$results);
	
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#DHCP Server\s+([0-9\.])#", $ligne,$re)){
			$version=$re[1];
			@file_put_contents("/etc/artica-postfix/DHCPD_VER", $version);
			$GLOBALS["DHCPD_VERSION"]=$re[1];
			return $GLOBALS["DHCPD_VERSION"];
		}
	
	}
	
}


function dhcpd_server(){
	if(!$GLOBALS["CLASS_USERS"]->dhcp_installed){return;}
	$EnableDHCPServer=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDHCPServer");
	if(!is_numeric($EnableDHCPServer)){$EnableDHCPServer=0;}
	if($EnableDHCPServer==null){$EnableDHCPServer=0;}
	$pid_path=$GLOBALS["CLASS_UNIX"]->LOCATE_DHCPD_PID_PATH();
	$EnableChilli=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableChilli");
	if(!is_numeric($EnableChilli)){$EnableChilli=0;}
	if($EnableChilli==1){$EnableDHCPServer=0;}
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program("dhcpd");
	if(!is_file($binpath)){$binpath=$GLOBALS["CLASS_UNIX"]->find_program("dhcpd3");}
	$l[]="[DHCPD]";
	$l[]="service_name=APP_DHCP";
	$l[]="service_cmd=/etc/init.d/isc-dhcp-server";
	$l[]="master_version=".dhcpd_version();
	$l[]="service_disabled=$EnableDHCPServer";
	$l[]="family=network";
	$l[]="watchdog_features=1";
	if($EnableDHCPServer==0){$l[]="";return implode("\n",$l);return;}
	
	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);
	$l[]="watchdog_features=1";
	
	
	if($GLOBALS["VERBOSE"]){echo "PID PATH: $pid_path\n";}
	if($GLOBALS["VERBOSE"]){echo "BIN PATH: $binpath\n";}
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(is_file($binpath)){
			$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF("$binpath");
		
		}
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
			shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.initslapd.php --dhcpd >/dev/null 2>&1 &");
			shell_exec2("$nohup {$GLOBALS["NICE"]} /etc/init.d/isc-dhcp-server start >/dev/null 2>&1 &");
			
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);return;
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	
	$timefile="/etc/artica-postfix/dhcpd.leases.dmp";
	$exTime=$GLOBALS["CLASS_UNIX"]->file_time_min($timefile);
	if($exTime>30){
		$cmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.dhcpd-leases.php >/dev/null 2>&1 &";
		shell_exec2($cmd);

	}	
	return implode("\n",$l);return;

}

function ntpd_version(){
	if(isset($GLOBALS["NTPDVERSION"])){return $GLOBALS["NTPDVERSION"];}
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("ntpd");
	exec("$bin_path -v 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#Ver\.\s+([0-9\.a-z]+)#", $ligne,$re)){
			$GLOBALS["NTPDVERSION"]=$re[1];
			return $re[1];
		}
	}
}

function ntpd_server(){
	if(!$GLOBALS["CLASS_USERS"]->NTPD_INSTALLED){return;}
	$NTPDEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDEnabled");
	if(!is_numeric($NTPDEnabled)){$NTPDEnabled=0;}
	$pid_path="/var/run/ntpd.pid";
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("ntpd");

	$l[]="[NTPD]";
	$l[]="service_name=APP_NTPD";
	$l[]="service_cmd=dhcp";
	$l[]="master_version=".ntpd_version();
	$l[]="service_disabled=$NTPDEnabled";
	$l[]="family=network";
	$l[]="watchdog_features=1";
	if($NTPDEnabled==0){$l[]="";return implode("\n",$l);return;}

	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin_path);
	}

	$l[]="watchdog_features=1";

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_NTPD","ntpd");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}


function openvpn(){

	if(!$GLOBALS["CLASS_USERS"]->OPENVPN_INSTALLED){return;}

	$clientsDir=$GLOBALS["CLASS_UNIX"]->dirdir("/etc/artica-postfix/openvpn/clients");
	writelogs(count($clientsDir)." openvpn client session(s)",__FUNCTION__,__FILE__,__LINE__);
	if(count($clientsDir)>0){
		$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
		$cmd="$nohup {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.openvpn.php --wakeup-clients >/dev/null 2>&1 &";
		shell_exec2(trim($cmd));
	}

	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("openvpn");
	$EnableOPenVPNServerMode=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOPenVPNServerMode");
	if($EnableOPenVPNServerMode==null){$EnableOPenVPNServerMode=0;}
	$pid_path="/var/run/openvpn/openvpn-server.pid";

	$l[]="[OPENVPN_SERVER]";
	$l[]="service_name=APP_OPENVPN";
	$l[]="service_cmd=openvpn";
	$l[]="master_version=".GetVersionOf("openvpn");
	$l[]="service_disabled=$EnableOPenVPNServerMode";
	//$l[]="remove_cmd=--pureftpd-remove";
	$l[]="family=vpn";
	$l[]="watchdog_features=1";
	if($EnableOPenVPNServerMode==0){return implode("\n",$l);return;}


	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin_path);
	}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_OPENVPN","openvpn");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
	$cmd="$nohup {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.openvpn.php --wakeup-server >/dev/null 2>&1 &";
	shell_exec2(trim($cmd));

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;


}

function vnstat(){
	if(!$GLOBALS["CLASS_USERS"]->APP_VNSTAT_INSTALLED){return;}
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("vnstatd");
	if(!is_file($bin_path)){return;}
	$EnableVnStat=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableVnStat");
	if(!is_numeric($EnableVnStat)){$EnableVnStat=0;}
	if($GLOBALS["VERBOSE"]){echo "EnableVnStat = $EnableVnStat\n";}
	
	if($GLOBALS["CLASS_USERS"]->LIGHT_INSTALL){
		if($EnableVnStat==1){
			$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableVnStat",0);
			$EnableVnStat=0;
		}
	}
	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);
	
	$pid_path="/var/run/vnstat.pid";

	$l[]="[APP_VNSTAT]";
	$l[]="service_name=APP_VNSTAT";
	$l[]="service_cmd=vnstat";
	$l[]="master_version=".GetVersionOf("vnstat");
	$l[]="service_disabled=$EnableVnStat";
	//$l[]="remove_cmd=--pureftpd-remove";
	$l[]="family=network";
	$l[]="watchdog_features=1";
	
	
	if($EnableVnStat==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			shell_exec2("{$GLOBALS["KILLBIN"]} -9 $master_pid >/dev/null 2>&1");
		}
	
		return implode("\n",$l);
	}
	

	

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin_path);
	}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_VNSTAT","vnstat");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}
function munin(){
	if(!$GLOBALS["CLASS_USERS"]->MUNIN_CLIENT_INSTALLED){return;}
	$enabled=1;
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("munin-node");
	$MuninDisabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MuninDisabled");
	if($MuninDisabled==null){$MuninDisabled=0;}
	$pid_path="/var/run/munin/munin-node.pid";
	if($MuninDisabled==1){$enabled=0;}
	$l[]="[APP_MUNIN]";
	$l[]="service_name=APP_MUNIN";
	$l[]="service_cmd=munin";
	$l[]="master_version=".GetVersionOf("munin");
	$l[]="service_disabled=$enabled";
	$l[]="family=network";
	$l[]="watchdog_features=1";
	if($enabled==0){return implode("\n",$l);return;}

	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin_path);
	}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_MUNIN","munin");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}


function vboxguest(){
	if(!$GLOBALS["CLASS_USERS"]->APP_VBOXADDINTION_INSTALLED){return;}
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("VBoxService");
	if(!is_file($bin_path)){return;}
	$pid_path=$GLOBALS["CLASS_UNIX"]->LOCATE_VBOX_ADDITIONS_PID();

	$l[]="[APP_VBOXADDITIONS]";
	$l[]="service_name=APP_VBOXADDITIONS";
	$l[]="service_cmd=vboxguest";
	$l[]="master_version=".GetVersionOf("vboxguest");
	$l[]="service_disabled=1";
	$l[]="pid_path=$pid_path";
	//$l[]="remove_cmd=--pureftpd-remove";
	$l[]="family=system";
	$l[]="watchdog_features=1";


	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin_path);
	}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_VBOXADDITIONS","vboxguest");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;


}
//========================================================================================================================================================


function pure_ftpd(){



	if(!$GLOBALS["CLASS_USERS"]->PUREFTP_INSTALLED){return;}

	$PureFtpdEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PureFtpdEnabled");
	if($PureFtpdEnabled==null){$PureFtpdEnabled=0;}
	$pid_path=$GLOBALS["CLASS_UNIX"]->LOCATE_PURE_FTPD_PID_PATH();
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("pure-ftpd");

	$l[]="[PUREFTPD]";
	$l[]="service_name=APP_PUREFTPD";
	$l[]="service_cmd=ftp";
	$l[]="master_version=".GetVersionOf("pure-ftpd");
	$l[]="service_disabled=$PureFtpdEnabled";
	$l[]="remove_cmd=--pureftpd-remove";
	$l[]="family=storage";
	$l[]="watchdog_features=1";
	if($PureFtpdEnabled==0){return implode("\n",$l);return;}

	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);
	$l[]="watchdog_features=1";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin_path);
	}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_PUREFTPD","ftp");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}
//========================================================================================================================================================
function mldonkey(){



	if(!$GLOBALS["CLASS_USERS"]->MLDONKEY_INSTALLED){return;}

	$EnableMLDonKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMLDonKey"));
	if($EnableMLDonKey==null){$EnableMLDonKey=1;}
	$pid_path="/var/run/mlnet.pid";
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("mlnet");

	$l[]="[APP_MLDONKEY]";
	$l[]="service_name=APP_MLDONKEY";
	$l[]="service_cmd=mldonkey";
	$l[]="family=storage";
	$l[]="master_version=".GetVersionOf("mldonkey");
	$l[]="service_disabled=$EnableMLDonKey";
	//$l[]="remove_cmd=--pureftpd-remove";

	if($EnableMLDonKey==0){
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);
	$l[]="watchdog_features=1";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin_path);
	}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_MLDONKEY","mldonkey");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}
//========================================================================================================================================================
function policyd_weight(){
	if(!is_file("/usr/share/artica-postfix/bin/artica-install")){return;}
	$EnablePolicydWeight=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePolicydWeight"));
	if($EnablePolicydWeight==null){$EnablePolicydWeight=0;}

	$l[]="[POLICYD_WEIGHT]";
	$l[]="service_name=APP_POLICYD_WEIGHT";
	$l[]="service_cmd=policydw";
	$l[]="family=postfix";
	$l[]="master_version=".GetVersionOf("policydw");
	$l[]="service_disabled=$EnablePolicydWeight";
	$l[]="watchdog_features=1";
	//$l[]="remove_cmd=--pureftpd-remove";

	if($EnablePolicydWeight==0){
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$pid_path=$GLOBALS["CLASS_UNIX"]->POLICYD_WEIGHT_GET("PIDFILE");
	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);

	$l[]="watchdog_features=1";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF('policyd-weight');
	}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("POLICYD_WEIGHT","policydw");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}
//========================================================================================================================================================
function kav4fs(){



	if(!$GLOBALS["CLASS_USERS"]->KAV4FS_INSTALLED){return null;}

	$EnableKav4FS=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKav4FS"));
	if($EnableKav4FS==null){$EnableKav4FS=1;}

	$l[]="[APP_KAV4FS]";
	$l[]="service_name=APP_KAV4FS";
	$l[]="family=system";
	$l[]="service_cmd=kav4fs";
	$l[]="master_version=".GetVersionOf("kav4fs");
	$l[]="service_disabled=$EnableKav4FS";
	$l[]="watchdog_features=1";
	//$l[]="remove_cmd=--pureftpd-remove";

	if($EnableKav4FS==0){
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$pid_path="/var/run/kav4fs/supervisor.pid";
	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);

	$l[]="watchdog_features=1";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF('kav4fs-supervisor');
	}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_KAV4FS","kav4fs");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}
//========================================================================================================================================================
function gluster_directories_number(){
	
	$f=file("/etc/artica-cluster/glusterfs-server.vol");
	$c=0;
	while (list ($index, $line) = each ($f) ){
		if(preg_match("#option directory\s+(.+)#",$line)){$c++;}
	}
	return $c;
}


function gluster(){
	if(!$GLOBALS["CLASS_USERS"]->GLUSTER_INSTALLED){return null;}
	$EnableGluster=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGluster"));
	if(!is_numeric($EnableGluster)){$EnableGluster=0;}
	
	$l[]="[GLUSTER]";
	$l[]="service_name=APP_GLUSTER";
	$l[]="service_cmd=gluster";
	$l[]="family=storage";
	$l[]="master_version=".GetVersionOf("gluster");
	$l[]="service_disabled=$EnableGluster";
	$l[]="watchdog_features=1";
	//$l[]="remove_cmd=--pureftpd-remove";

	if($EnableGluster==0){
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$pid_path="/var/run/glusterd.pid";
	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_GLUSTER","gluster");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}
//========================================================================================================================================================
function auditd(){



	if(!$GLOBALS["CLASS_USERS"]->APP_AUDITD_INSTALLED){return null;}


	$EnableAuditd=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAuditd");
	if($EnableAuditd==null){$EnableAuditd=1;}


	$l[]="[APP_AUDITD]";
	$l[]="service_name=APP_AUDITD";
	$l[]="service_cmd=auditd";
	$l[]="master_version=".GetVersionOf("auditd");
	$l[]="service_disabled=$EnableAuditd";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	

	if($EnableAuditd==0){
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$pid_path="/var/run/auditd.pid";
	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_AUDITD","auditd");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}
//========================================================================================================================================================

function kav4fsavs(){



	if(!$GLOBALS["CLASS_USERS"]->KAV4FS_INSTALLED){return null;}

	$EnableKav4FS=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKav4FS"));
	if($EnableKav4FS==null){$EnableKav4FS=1;}

	$l[]="[APP_KAV4FS_AVS]";
	$l[]="service_name=APP_KAV4FS_AVS";
	$l[]="service_cmd=kav4fs";
	$l[]="master_version=".GetVersionOf("kav4fs");
	$l[]="service_disabled=$EnableKav4FS";
	$l[]="family=system";


	if($EnableKav4FS==0){
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF('/opt/kaspersky/kav4fs/libexec/avs');

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;


}


function backuppc(){



	$binpath="/usr/share/backuppc/bin/BackupPC";
	if(!is_file("/usr/share/backuppc/bin/BackupPC")){return;}
	if(is_file("/etc/artica-postfix/KASPERSKY_WEB_APPLIANCE")){return;}
	$EnableBackupPc=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableBackupPc"));
	if(!is_numeric($EnableBackupPc)){$EnableBackupPc=0;}


	$l[]="[APP_BACKUPPC]";
	$l[]="service_name=APP_BACKUPPC";
	$l[]="service_cmd=backuppc";
	$l[]="master_version=".GetVersionOf("backuppc");
	$l[]="service_disabled=$EnableBackupPc";
	$l[]="family=storage";


	if($EnableBackupPc==0){
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$pid_path="/var/run/backuppc/BackupPC.pid";
	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);

	$l[]="watchdog_features=1";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		writelogs("$master_pid, process not exists",__FUNCTION__,__FILE__,__LINE__);
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN($binpath);
		writelogs("first, process not exists pidof return $master_pid",__FUNCTION__,__FILE__,__LINE__);
	}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_BACKUPPC","backuppc");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}


function GetMemoriesOf($pid){

	return $GLOBALS["CLASS_UNIX"]->GetMemoriesOf($pid);

}

function CheckCallable(){
	include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
	$methodVariable=array($GLOBALS["CLASS_UNIX"], 'GetVersionOf');
	if(!is_callable($methodVariable, true, $callable_name)){
		ToSyslog("Loading unix class");
		$GLOBALS["CLASS_UNIX"]=new unix();
	}

	$methodVariable=array($GLOBALS["CLASS_UNIX"], 'find_program');
	if(!is_callable($methodVariable, true, $callable_name)){
		events("Loading unix class");
		$GLOBALS["CLASS_UNIX"]=new unix();
	}
	$methodVariable=array($GLOBALS["CLASS_SOCKETS"], 'GET_INFO');
	if(!is_callable($methodVariable, true, $callable_name)){
		ToSyslog("Loading socket class");
		$GLOBALS["CLASS_SOCKETS"]=new sockets();
	}


	$methodVariable=array($GLOBALS["CLASS_USERS"], 'BuildLeftMenus');
	if(!is_callable($methodVariable, true, $callable_name)){
		ToSyslog("Loading usersMenus class");
		$GLOBALS["CLASS_USERS"]=new settings_inc();
	}
	
	
	$GLOBALS["OS_SYSTEM"]=new os_system();
	$GLOBALS["MEMORY_INSTALLED"]=$GLOBALS["OS_SYSTEM"]->memory();
	$GLOBALS["AMAVIS_WATCHDOG"]=unserialize(@file_get_contents("/etc/artica-postfix/amavis.watchdog.cache"));
	$GLOBALS["TIME_CLASS"]=time();
	$GLOBALS["ArticaWatchDogList"]=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWatchDogList")));
	$GLOBALS["AMAVIS_WATCHDOG"]=unserialize(@file_get_contents("/etc/artica-postfix/amavis.watchdog.cache"));
	


}


function meta_checks(){
	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
	$kill=$GLOBALS["CLASS_UNIX"]->find_program("kill");
	if(!is_file($pgrep)){
		events("pgrep no such file",__FUNCTION__,__LINE__);
		return;
	}
	events("$pgrep -f \"exec.artica.meta.users.php\" 2>&1",__FUNCTION__,__LINE__);
	exec("$pgrep -f \"exec.artica.meta.users.php\" 2>&1",$results);

	while (list ($index, $line) = each ($results) ){
		if(preg_match("#([0-9]+)#",$line,$re)){
			events("checking process time of {$re[1]}",__FUNCTION__,__LINE__);
			if($GLOBALS["CLASS_UNIX"]->PID_IS_CHROOTED($re[1])){continue;}
			$time=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($re[1]);
			events("checking pid {$re[1]} {$time}Mn",__FUNCTION__,__LINE__);
			if($time>30){
				events("Killing pid {$re[1]} {$time}Mn",__FUNCTION__,__LINE__);
				$GLOBALS["CLASS_UNIX"]->KILL_PROCESS($re[1],9);
				
			}
		}
	}

}


function getmem(){
	include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
	$os=new os_system();
	$GLOBALS["MEMORY_INSTALLED"]=$os->memory();
	$os=null;
	print_r($GLOBALS["MEMORY_INSTALLED"]);

}

function CheckCurl(){
	$results=array();
	$pidof=$GLOBALS["CLASS_UNIX"]->find_program("pidof");
	if($pidof==null){
		events("pidof no such file",__FUNCTION__,__LINE__);
		return;
	}
	$curl=$GLOBALS["CLASS_UNIX"]->find_program("curl");
	if($curl==null){
		events("curl binary no such file",__FUNCTION__,__LINE__);
		return;
	}

	exec("$pidof $curl 2>&1",$results);
	if(count($results)==0){
		events("no curl instance in memory",__FUNCTION__,__LINE__);
		return;
	}

	while (list ($index, $pid) = each ($results) ){
		$pid=trim($pid);
		if(!is_numeric($pid)){continue;}
		if($pid<5){continue;}
		if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){
			$time=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($pid);
			events("$curl: $pid {$time}Mn",__FUNCTION__,__LINE__);
			if($time>60){
				events("$curl: too long time for $pid, kill it",__FUNCTION__,__LINE__);
				$GLOBALS["CLASS_UNIX"]->KILL_PROCESS($pid,9);
				
			}
		}
	}

}

function GetVersionOf($name){
	if(isset($GLOBALS["GetVersionOf"][$name])){return $GLOBALS["GetVersionOf"][$name];}
	CheckCallable();
	$GLOBALS["GetVersionOf"][$name]=$GLOBALS["CLASS_UNIX"]->GetVersionOf($name);
	return $GLOBALS["GetVersionOf"][$name];
}
function events($text,$function=null,$line=0){
	if($GLOBALS["VERBOSE"]){
		echo "$function:: $text (L.$line)\n";
		return;
	}
	$filename=basename(__FILE__);
	$classunix=dirname(__FILE__)."/framework/class.unix.inc";
	if(!isset($GLOBALS["CLASS_UNIX"])){
		if(!is_file($classunix)){$classunix="/opt/artica-agent/usr/share/artica-agent/ressources/class.unix.inc";}
		include_once($classunix);
		$GLOBALS["CLASS_UNIX"]=new unix();
	}
	$GLOBALS["CLASS_UNIX"]->events("$filename $function:: $text (L.$line)","/usr/share/artica-postfix/ressources/logs/launch.status.task");
	$GLOBALS["CLASS_UNIX"]->events("$filename $function:: $text (L.$line)","/var/log/artica-status.log");
}
function events_syslog($text=null){
	if($GLOBALS["VERBOSE"]){echo "$text\n";}
	if(!function_exists("syslog")){return;}
	$file="artica-watchdog";
	$LOG_SEV=LOG_INFO;
	openlog($file, LOG_PID , LOG_SYSLOG);
	syslog($LOG_SEV, $text);
	closelog();
}


function events_Loadavg($text,$function=null,$line=0){
	$filename=basename(__FILE__);
	if(!isset($GLOBALS["CLASS_UNIX"])){include_once(dirname(__FILE__)."/framework/class.unix.inc");$GLOBALS["CLASS_UNIX"]=new unix();}
	$GLOBALS["CLASS_UNIX"]->events("$filename $function:: $text (L.$line)","/var/log/artica-postfix/xLoadAvg.debug");
}

function bandwith(){
	return;
}

function phpmyadmin_perms(){
	if(is_file("/usr/share/artica-postfix/mysql/config.inc.php")){@chmod("/usr/share/artica-postfix/mysql/config.inc.php",0600);}
}

function ps_mem(){
	include_once(dirname(__FILE__)."/ressources/class.artica.status.bin.inc");
	$s=new periodics_status();
	$s->ps_mem();
	
}

function ifconfig_network(){
	$unix=new unix();
	$ifconfigs=$GLOBALS["CLASS_UNIX"]->ifconfig_all_ips();
	$DisableWatchDogNetwork=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableWatchDogNetwork");
	if(!is_numeric($DisableWatchDogNetwork)){$DisableWatchDogNetwork=0;}
	if($DisableWatchDogNetwork==1){return;}
	unset($ifconfigs["127.0.0.1"]);
	events(count($ifconfigs). " Ip addresses",__FUNCTION__,__LINE__);
	if(count($ifconfigs)==0){
		$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
		$timmin=$GLOBALS["CLASS_UNIX"]->file_time_min($timefile);
		if($timmin>10){
			$ifconfigbin=$GLOBALS["CLASS_UNIX"]->find_program("ifconfig");
			if(is_file($ifconfigbin)){
				exec("$ifconfigbin -a 2>&1",$ifconfigbinDump);	
				$GLOBALS["CLASS_UNIX"]->send_email_events("No Network detected !, rebuild network configuration","Artica has no detected network the network interface will be rebuilded\nHere it is the Network dump\n".@implode("\n", $ifconfigbinDump)."\nIf you did not want this watchdog, do the following command on this console server:\n# echo 1 >/etc/artica-postfix/settings/Daemons/DisableWatchDogNetwork\n# /etc/init.d/artica-status reload","system");
				@unlink($timefile);
				@file_put_contents($timefile, time());
				@unlink("/etc/artica-postfix/MEM_INTERFACES");
				$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.virtuals-ip.php >/dev/null 2>&1 &");
				shell_exec2($cmd);
			}		
		}
	}

}

function reboot(){
	$unix=new unix();
	$reboot=$unix->find_program("reboot");
	system_admin_events("Ask to reboot the system...\nExecuting $reboot", __FUNCTION__, __FILE__, __LINE__, "system");
	shell_exec2("$reboot");
	
	
}
function CheckNetInterfaces(){
	$unix=new unix();
	$Prefix=__FUNCTION__;
	$arrayTCP=$unix->NETWORK_ALL_INTERFACES(true);
	unset($arrayTCP["127.0.0.1"]);
	events_syslog("$Prefix: ". count($arrayTCP)." Interface(s)");
	if(count($arrayTCP)==0){
		events_syslog("kernel: [  Artica-Net] Start Network [artica-ifup] (".basename(__FILE__)."/".__LINE__.")" );
		events_syslog("$Prefix: Running artica-ifup in order to relink interface(s)");
		shell_exec2("/etc/init.d/artica-ifup start");
	}

}



function shell_exec_time($cmdlineNophp5,$mintime=5){
	if(!is_numeric($mintime)){$mintime=5;}
	if($mintime<5){$mintime=5;}
	$md5=md5($cmdlineNophp5);
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".$md5.time";
	$TimeExec=$GLOBALS["CLASS_UNIX"]->file_time_min($timefile);
	if($TimeExec<$mintime){return;}
	@unlink($timefile);
	@file_put_contents($timefile, time());
	shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ". basename(__FILE__)."/$cmdlineNophp5 >/dev/null 2>&1 &");
}

function shell_exec2($cmdline){
	if(!isset($GLOBALS["shell_exec2"])){$GLOBALS["shell_exec2"]=array();}
	if(!is_array($GLOBALS["shell_exec2"])){$GLOBALS["shell_exec2"]=array();}
	$md5=md5($cmdline);
	$time=date("YmdHi");
	if(isset($GLOBALS["shell_exec2"][$time][$md5])){
		if($GLOBALS["VERBOSE"]){echo "ERROR ALREADY EXECUTED $cmdline\n";}
		return;
	}
	if(count($GLOBALS["shell_exec2"])>5){$GLOBALS["shell_exec2"]=array();}
	$GLOBALS["shell_exec2"][$time][$md5]=true;
	
	
	if(!preg_match("#\/nohup\s+#",$cmdline)){
		$cmdline="{$GLOBALS["nohup"]} $cmdline";
	}
	if(!preg_match("#\s+>\/.*?2>\&1#",$cmdline)){
		if(!preg_match("#\&$#",$cmdline)){
			$cmdline="$cmdline >/dev/null 2>&1 &";
		}
	}
	
	if($GLOBALS["VERBOSE"]){echo "******************* EXEC ********************************\n$cmdline\n********************************\n";}
	if(!$GLOBALS["VERBOSE"]){events("Execute: $cmdline",__FUNCTION__,__LINE__);}
	shell_exec($cmdline);
	
}
?>