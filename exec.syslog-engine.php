<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["DEBUG"]=false;;
$GLOBALS["FORCE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.sockets.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/ressources/class.iptables-chains.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.haproxy.builder.php');
include_once(dirname(__FILE__) . "/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__) . "/ressources/class.mysql.builder.inc");


if(preg_match("#--norestart#",implode(" ",$argv))){$GLOBALS["NORESTART"]=true;}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}

if($argv[1]=='--updtev'){udfbguard_update_events();die();}
if($argv[1]=='--rsylogd'){rsyslog_check_includes();die();}
if($argv[1]=='--sysev'){sysev();die();}
if($argv[1]=='--admin-evs'){system_admin_events_checks(true);die();}



if(!$GLOBALS["FORCE"]){
	if(system_is_overloaded(basename(__FILE__))){
		system_admin_events("OVERLOADED system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting",__FUNCTION__,__FILE__,__LINE__,"system");
		system_admin_events("OVERLOADED system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting",__FUNCTION__,__FILE__,__LINE__,"system");
		die();
	}
}

if(!isset($argv[1])){$argv[1]="--auth-logs";}

if($argv[1]=='--restart-syslog'){restart_syslog();die();}
if($argv[1]=='--build-server'){build_server_mode();die();}
if($argv[1]=='--build-client'){build_client_mode();die();}
if($argv[1]=='--haproxy'){haproxy_events();die();}
if($argv[1]=='--squid-notifs'){squid_admin_notifs_check();die();}




if($argv[1]=='--auth-logs'){
		authlogs();
		if(system_is_overloaded(basename(__FILE__))){system_admin_events("OVERLOADED system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting",__FUNCTION__,__FILE__,__LINE__,"system");die();}
		sessions_logs();
		if(system_is_overloaded(basename(__FILE__))){system_admin_events("OVERLOADED system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting",__FUNCTION__,__FILE__,__LINE__,"system");die();}
		ipblocks();
		if(system_is_overloaded(basename(__FILE__))){system_admin_events("OVERLOADED system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting",__FUNCTION__,__FILE__,__LINE__,"system");die();}
		clamd_mem();
		if(system_is_overloaded(basename(__FILE__))){system_admin_events("OVERLOADED system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting",__FUNCTION__,__FILE__,__LINE__,"system");die();}
		admin_logs();
		if(system_is_overloaded(basename(__FILE__))){system_admin_events("OVERLOADED system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting",__FUNCTION__,__FILE__,__LINE__,"system");die();}
		crossroads();
		if(system_is_overloaded(basename(__FILE__))){system_admin_events("OVERLOADED system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting",__FUNCTION__,__FILE__,__LINE__,"system");die();}
		udfbguard_admin_events();
		if(system_is_overloaded(basename(__FILE__))){system_admin_events("OVERLOADED system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting",__FUNCTION__,__FILE__,__LINE__,"system");die();}
		dhcpd_logs();
		if(system_is_overloaded(basename(__FILE__))){system_admin_events("OVERLOADED system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting",__FUNCTION__,__FILE__,__LINE__,"system");die();}
		system_admin_events_checks();
		squid_admin_notifs_check();
		if(system_is_overloaded(basename(__FILE__))){system_admin_events("OVERLOADED system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting",__FUNCTION__,__FILE__,__LINE__,"system");die();}
		haproxy_events();
		die();
}
if($argv[1]=='--authfw'){authfw();sessions_logs();die();ipblocks();system_admin_events_checks();}
if($argv[1]=='--authfw-compile'){compile_sshd_rules();sessions_logs();ipblocks();system_admin_events_checks();die();}
if($argv[1]=='--snort'){snort_logs();sessions_logs();ipblocks();clamd_mem();crossroads();udfbguard_admin_events();system_admin_events_checks();die();}
if($argv[1]=='--sessions'){sessions_logs();system_admin_events();die();}
if($argv[1]=='--loadavg'){loadavg_logs();clamd_mem();crossroads();system_admin_events_checks();die();}
if($argv[1]=='--ipblocks'){ipblocks();system_admin_events_checks();die();}
if($argv[1]=='--adminlogs'){admin_logs();crossroads();udfbguard_admin_events();dhcpd_logs();die();}
if($argv[1]=='--psmem'){ps_mem(true);crossroads();dhcpd_logs();die();}
if($argv[1]=='--squid-tasks'){die();}
if($argv[1]=='--update-events-check'){update_events_check(true);system_admin_events_checks();squid_admin_notifs_check();die();}
if($argv[1]=='--squidsys'){build_local6_squid();die();}
if($argv[1]=='--localx'){build_localx_servers();die();}

$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".time";
$unix=new unix();
$oldpid=$unix->get_pid_from_file($pidfile);
if($unix->process_exists($oldpid,basename(__FILE__))){
	ssh_events("Already PID $oldpid exists, aborting" , "MAIN", __FILE__, __LINE__);
	die();
}

$time=$unix->file_time_min($timefile);
if($time<5){die();}

@file_put_contents($pidfile, getmypid());
@unlink($timefile);
@file_put_contents($timefile, time());


udfbguard_admin_events();
admin_logs();
if(system_is_overloaded(basename(__FILE__))){system_admin_events("OVERLOADED system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting",__FUNCTION__,__FILE__,__LINE__,"system");die();}
ps_mem(true);
authlogs();
if(system_is_overloaded(basename(__FILE__))){system_admin_events("OVERLOADED system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting",__FUNCTION__,__FILE__,__LINE__,"system");die();}
sessions_logs();
ipblocks();
if(system_is_overloaded(basename(__FILE__))){system_admin_events("OVERLOADED system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting",__FUNCTION__,__FILE__,__LINE__,"system");die();}
clamd_mem();
crossroads();
dhcpd_logs();
die();


function sysev(){
	
		$unix=new unix();
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}	
		$t=0;		
		@file_put_contents($pidfile, getmypid());
		system_admin_events_checks();
		udfbguard_admin_events();
	
}

function build_server_mode(){
	$sock=new sockets();
	$ActAsASyslogServer=$sock->GET_INFO("ActAsASyslogServer");
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(is_file("/etc/artica-postfix/WEBSTATS_APPLIANCE")){$ActAsASyslogServer=1;$EnableWebProxyStatsAppliance=1;$sock->SET_INFO("ActAsASyslogServer", 1);$sock->SET_INFO("EnableWebProxyStatsAppliance", 1);}
	
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($EnableWebProxyStatsAppliance==1){
		$ActAsASyslogServer=1;
		$sock->SET_INFO("ActAsASyslogServer", 1);
	}
	
	if(!is_numeric($ActAsASyslogServer)){
		echo "Starting......: syslog server parameters not defined, aborting tasks\n";
		return;
	}
	
	
	
	if(is_file("/etc/default/syslogd")){
		echo "Starting......: syslog old syslog mode\n";
		build_server_mode_debian();
		if($GLOBALS["NORESTART"]){return;}
		shell_exec("/etc/init.d/artica-postfix restart auth-logger");
		return;
	}
	
	if(is_dir("/etc/rsyslog.d")){
		echo "Starting......: syslog rsyslog mode\n";
		build_server_mode_ubuntu();
		if($GLOBALS["NORESTART"]){return;}
		shell_exec("/etc/init.d/artica-postfix restart auth-logger");
	}
}

function build_local6_squid(){
		$sock=new sockets();
		$array=unserialize(base64_decode($sock->GET_INFO("SquidSyslogAdd")));
		if(!isset($array["ENABLE"])){return;}
		if(!is_numeric($array["ENABLE"])){$array["ENABLE"]==0;}
		if($array["ENABLE"]==0){
			if(is_file("/etc/rsyslog.d/artica-client-local6.conf")){
				@unlink("/etc/rsyslog.d/artica-client-local6.conf");
				restart_syslog();
			}
			
			return;
		}
		if($array["SERVER"]==null){return;}	
		
	if(is_file("/etc/rsyslog.conf")){
		echo "Starting......: syslog client rsyslog mode\n";
		rsyslog_check_includes();
		build_local6_rsyslogd($array["SERVER"]);
		restart_syslog();
		return;
	}	

	echo "Starting......: syslog client /etc/rsyslog.conf no such file !!\n";

}

function rsyslog_check_includes(){
	if(!is_file("/etc/rsyslog.conf")){return;}
	$imudp="#";
	$unix=new unix();
	
	$HAPROXY=$unix->find_program("haproxy");
	if(is_file($HAPROXY)){$imudp=null;}
	
	$f[]="#  /etc/rsyslog.conf	Configuration file for rsyslog.";
	$f[]="#";
	$f[]="#			Written by Artica " .date("Y-m-d H:i:s");
	$f[]="#			See ".__FILE__." function ". __FUNCTION__."() line ". __LINE__;
	$f[]="#			For more information see";
	$f[]="#			/usr/share/doc/rsyslog-doc/html/rsyslog_conf.html";
	$f[]="";
	$f[]="";
	$f[]="#################";
	$f[]="#### MODULES ####";
	$f[]="#################";
	$f[]="";
	$f[]="\$ModLoad imuxsock # provides support for local system logging";
	$f[]="\$ModLoad imklog   # provides kernel logging support (previously done by rklogd)";
	$f[]="#\$ModLoad immark  # provides --MARK-- message capability";
	$f[]="";
	$f[]="# provides UDP syslog reception";
	$f[]="$imudp\$ModLoad imudp";
	$f[]="$imudp\$UDPServerRun 514";
	$f[]="";
	$f[]="# provides TCP syslog reception";
	$f[]="#\$ModLoad imtcp";
	$f[]="#\$InputTCPServerRun 514";
	$f[]="";
	$f[]="";
	$f[]="###########################";
	$f[]="#### GLOBAL DIRECTIVES ####";
	$f[]="###########################";
	$f[]="";
	$f[]="#";
	$f[]="# Use traditional timestamp format.";
	$f[]="# To enable high precision timestamps, comment out the following line.";
	$f[]="#";
	$f[]="\$ActionFileDefaultTemplate RSYSLOG_TraditionalFileFormat";
	$f[]="";
	$f[]="#";
	$f[]="# Set the default permissions for all log files.";
	$f[]="#";
	$f[]="\$FileOwner root";
	$f[]="\$FileGroup adm";
	$f[]="\$FileCreateMode 0640";
	$f[]="\$DirCreateMode 0755";
	$f[]="\$Umask 0022";
	$f[]="";
	$f[]="#";
	$f[]="# Include all config files in /etc/rsyslog.d/";
	$f[]="#";
	$f[]="\$IncludeConfig /etc/rsyslog.d/*.conf";
	$f[]="";
	$f[]="";
	$f[]="###############";
	$f[]="#### RULES ####";
	$f[]="###############";
	$f[]="";
	$f[]="#";
	$f[]="# First some standard log files.  Log by facility.";
	$f[]="#";
	$f[]="auth,authpriv.*			/var/log/auth.log";
	$f[]="*.*;auth,authpriv.none		-/var/log/syslog";
	$f[]="#cron.*				/var/log/cron.log";
	$f[]="daemon.*			-/var/log/daemon.log";
	$f[]="kern.*				-/var/log/kern.log";
	$f[]="lpr.*				-/var/log/lpr.log";
	$f[]="mail.*				-/var/log/mail.log";
	$f[]="user.*				-/var/log/user.log";
	$f[]="";
	$f[]="#";
	$f[]="# Logging for the mail system.  Split it up so that";
	$f[]="# it is easy to write scripts to parse these files.";
	$f[]="#";
	$f[]="mail.info			-/var/log/mail.info";
	$f[]="mail.warn			-/var/log/mail.warn";
	$f[]="mail.err			/var/log/mail.err";
	$f[]="";
	$f[]="#";
	$f[]="# Logging for INN news system.";
	$f[]="#";
	$f[]="news.crit			/var/log/news/news.crit";
	$f[]="news.err			/var/log/news/news.err";
	$f[]="news.notice			-/var/log/news/news.notice";
	$f[]="";
	$f[]="#";
	$f[]="# Some \"catch-all\" log files.";
	$f[]="#";
	$f[]="*.=debug;\ ";
	$f[]="	auth,authpriv.none;\ ";
	$f[]="	news.none;mail.none	-/var/log/debug";
	$f[]="*.=info;*.=notice;*.=warn;\ ";
	$f[]="	auth,authpriv.none;\ ";
	$f[]="	cron,daemon.none;\ ";
	$f[]="	mail,news.none		-/var/log/messages";
	$f[]="";
	$f[]="#";
	$f[]="# Emergencies are sent to everybody logged in.";
	$f[]="#";
	$f[]="*.emerg				*";
	$f[]="";
	$f[]="#";
	$f[]="# I like to have messages displayed on the console, but only on a virtual";
	$f[]="# console I usually leave idle.";
	$f[]="#";
	$f[]="#daemon,mail.*;\ ";
	$f[]="#	news.=crit;news.=err;news.=notice;\ ";
	$f[]="#	*.=debug;*.=info;\ ";
	$f[]="#	*.=notice;*.=warn	/dev/tty8";
	$f[]="";
	$f[]="# The named pipe /dev/xconsole is for the `xconsole' utility.  To use it,";
	$f[]="# you must invoke `xconsole' with the `-file' option:";
	$f[]="# ";
	$f[]="#    \$ xconsole -file /dev/xconsole [...]";
	$f[]="#";
	$f[]="# NOTE: adjust the list below, or you'll go crazy if you have a reasonably";
	$f[]="#      busy site..";
	$f[]="#";
	$f[]="daemon.*;mail.*;\ ";
	$f[]="	news.err;\ ";
	$f[]="	*.=debug;*.=info;\ ";
	$f[]="	*.=notice;*.=warn	|/dev/xconsole";
	$f[]="";
	@file_put_contents("/etc/rsyslog.conf", @implode("\n", $f)."\n");
	echo "Building /etc/rsyslog.conf done\n";
	echo "You can restart syslog by typing `".$unix->LOCATE_SYSLOG_INITD()." restart`\n";
	
	
	
}


function build_client_mode(){
	$sock=new sockets();
	$ActAsASyslogClient=$sock->GET_INFO("ActAsASyslogClient");
	$ActAsASyslogSMTPClient=$sock->GET_INFO("ActAsASyslogSMTPClient");
	if(!is_numeric($ActAsASyslogSMTPClient)){$ActAsASyslogSMTPClient=0;}
	if(!is_numeric($ActAsASyslogClient)){$ActAsASyslogClient=0;}
	$EnableRemoteSyslogStatsAppliance=$sock->GET_INFO("EnableRemoteSyslogStatsAppliance");
	if(!is_numeric($EnableRemoteSyslogStatsAppliance)){$EnableRemoteSyslogStatsAppliance=0;}
	if($EnableRemoteSyslogStatsAppliance==1){$ActAsASyslogClient=1;}
	
	if(($ActAsASyslogClient==0) OR ($ActAsASyslogSMTPClient==0)){
		echo "Starting......: syslog client parameters not defined, aborting tasks\n";
	}
	
	if(is_file("/etc/default/syslogd")){
		echo "Starting......: syslog client old syslog mode\n";
		build_client_mode_debian();
		shell_exec("/etc/init.d/artica-postfix restart auth-logger");
		return;
	}
	
	if(is_dir("/etc/rsyslog.d")){
		echo "Starting......: syslog client rsyslog mode\n";
		build_client_mode_ubuntu();
		shell_exec("/etc/init.d/artica-postfix restart auth-logger");
	}
}

function build_client_mode_ubuntu(){
	
	$sock=new sockets();
	$ActAsASyslogServer=$sock->GET_INFO("ActAsASyslogServer");
	$ActAsASyslogClient=$sock->GET_INFO("ActAsASyslogClient");
	$EnableRemoteSyslogStatsAppliance=$sock->GET_INFO("EnableRemoteSyslogStatsAppliance");
	$ActAsASyslogSMTPClient=$sock->GET_INFO("ActAsASyslogSMTPClient");
	if(!is_numeric($EnableRemoteSyslogStatsAppliance)){$EnableRemoteSyslogStatsAppliance=0;}	
	if(!is_numeric($ActAsASyslogClient)){$ActAsASyslogClient=0;}
	if(!is_numeric($ActAsASyslogSMTPClient)){$ActAsASyslogSMTPClient=0;}	
	if($EnableRemoteSyslogStatsAppliance==1){$ActAsASyslogClient=1;}
	
	
	
	$serversList=unserialize(base64_decode($sock->GET_INFO("ActAsASyslogClientServersList")));
	@unlink("/etc/rsyslog.d/artica-client.conf");
	$g[]="";

if($EnableRemoteSyslogStatsAppliance==1){
	$RemoteStatisticsApplianceSettings=unserialize(base64_decode($sock->GET_INFO("RemoteStatisticsApplianceSettings")));
	$s[]="authpriv.info\t@{$RemoteStatisticsApplianceSettings["SERVER"]}";
}

if($ActAsASyslogClient==1){
	if(count($serversList)>0){
		while (list ($num, $server) = each ($serversList) ){
			if($server==null){continue;}
			if(preg_match("#(.+?):([0-9]+)#",$server,$re)){$server=$re[1];}
			echo "Starting......: syslog client $server (forced to 514 port)\n";
			$s[]="*.*\t@$server";
		}
	}
}

if($ActAsASyslogSMTPClient==1){
	$serversList=unserialize(base64_decode($sock->GET_INFO("ActAsASyslogClientSMTPList")));
	if(count($serversList)>0){
		while (list ($num, $server) = each ($serversList) ){
			if($server==null){continue;}
			if(preg_match("#(.+?):([0-9]+)#",$server,$re)){$server=$re[1];}
			echo "Starting......: syslog mail.* client $server (forced to 514 port)\n";
			$s[]="mail.*\t@$server";
		}
	}	
}


$g[]="";

if(is_array($s)){
	$final=@implode("\n",$s)."\n".@implode("\n",$g);
}else{
	$final=@implode("\n",$g);
}

@file_put_contents("/etc/rsyslog.d/artica-client.conf",$final);
echo "Starting......: syslog client /etc/rsyslog.d/artica-client.conf done\n";

@unlink("/etc/rsyslog.d/artica-authpriv.conf");
if(!ParseDirAuthpriv("/etc/rsyslog.d")){
	@file_put_contents("/etc/rsyslog.d/artica-authpriv.conf","authpriv.*			/var/log/auth.log");
}

restart_syslog();	
	
	
}

function ParseDirAuthpriv($dirname){
	foreach (glob("$dirname/*") as $filename) {
		if(CheckAuthAuthpriv($filename)){return true;}
	}
	return false;
	
}


function CheckAuthAuthpriv($filename){
	$f=explode("\n", @file_get_contents($filename));
	while (list ($num, $line) = each ($f) ){
		if(preg_match("#authpriv#", $line)){echo "Starting......: syslog client $filename has Authpriv\n";return true;}
	}
	return false;
}

function build_local6_rsyslogd($hostname){
	@unlink("/etc/rsyslog.d/artica-client-local6.conf");
	$f[]="local6.*\t@$hostname\n";
	@file_put_contents("/etc/rsyslog.d/artica-client-local6.conf", @implode("\n", $f));
	
	
}



function build_localx_servers(){
	
	for($i=0;$i<8;$i++){
		@unlink("/etc/rsyslog.d/artica-server-local$i.conf");
		
	}
	
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->GET_INFO("SyslogLocals")));
	while (list ($local, $path) = each ($datas) ){
		if(!preg_match("#\.[a-z]+$#", $path)){
			$path=$path."/$local.log";
		}
		@file_put_contents("/etc/rsyslog.d/artica-server-$local.conf", "$local.*\t-$path\n");
	}
	rsyslog_check_includes();
	restart_syslog();
	
}


function build_client_mode_debian(){
	
	$sock=new sockets();
	$ActAsASyslogServer=$sock->GET_INFO("ActAsASyslogServer");
	$ActAsASyslogClient=$sock->GET_INFO("ActAsASyslogClient");
	$ActAsASyslogSMTPClient=$sock->GET_INFO("ActAsASyslogSMTPClient");
		
	$EnableRemoteSyslogStatsAppliance=$sock->GET_INFO("EnableRemoteSyslogStatsAppliance");
	if(!is_numeric($EnableRemoteSyslogStatsAppliance)){$EnableRemoteSyslogStatsAppliance=0;}	
	if(!is_numeric($ActAsASyslogClient)){$ActAsASyslogClient=0;}
	if(!is_numeric($ActAsASyslogServer)){$ActAsASyslogServer=0;}	
	if(!is_numeric($ActAsASyslogSMTPClient)){$ActAsASyslogSMTPClient=0;}
	if($EnableRemoteSyslogStatsAppliance==1){$ActAsASyslogClient=1;}
	
	
	$serversList=unserialize(base64_decode($sock->GET_INFO("ActAsASyslogClientServersList")));
	$f=explode("\n",@file_get_contents("/etc/syslog.conf"));
	while (list ($num, $line) = each ($f) ){
		if(preg_match("#\.+?\.\.+?\s+@#",$line,$re)){
			$f[$num]=null;
			echo "Starting......: syslog client removing $line\n";
		}
	}
	
	reset($f);
	while (list ($num, $line) = each ($f) ){
		if(trim($line)==null){continue;}
		$g[]=$line;
	}
$g[]="";

if($EnableRemoteSyslogStatsAppliance==1){
	$RemoteStatisticsApplianceSettings=unserialize(base64_decode($sock->GET_INFO("RemoteStatisticsApplianceSettings")));
	$s[]="authpriv.info\t@{$RemoteStatisticsApplianceSettings["SERVER"]}";
}

if($ActAsASyslogClient==1){
	if(count($serversList)>0){
		while (list ($num, $server) = each ($serversList) ){
			if($server==null){continue;}
			if(preg_match("#(.+?):([0-9]+)#",$server,$re)){$server=$re[1];}
			echo "Starting......: syslog client $server (forced to 514 port)\n";
			$s[]="*.*\t@$server";
		}
	}
}

if($ActAsASyslogSMTPClient==1){
	$serversList=unserialize(base64_decode($sock->GET_INFO("ActAsASyslogClientSMTPList")));
	if(count($serversList)>0){
		while (list ($num, $server) = each ($serversList) ){
			if($server==null){continue;}
			if(preg_match("#(.+?):([0-9]+)#",$server,$re)){$server=$re[1];}
			echo "Starting......: syslog mail.* client $server (forced to 514 port)\n";
			$s[]="mail.*\t@$server";
		}
	}	
}




$g[]="";

if(is_array($s)){
	$final=@implode("\n",$s)."\n".@implode("\n",$g);
}else{
	$final=@implode("\n",$g);
}

@file_put_contents("/etc/syslog.conf",$final);
echo "Starting......: syslog client /etc/syslog.conf done\n";
restart_syslog();	
	
	
}

function build_server_mode_debian(){
	$sock=new sockets();
	$ActAsASyslogServer=$sock->GET_INFO("ActAsASyslogServer");
	if(is_file("/etc/artica-postfix/WEBSTATS_APPLIANCE")){$ActAsASyslogServer=1;}
		
	
	
	$moinsr=null;
	if($ActAsASyslogServer==1){
		echo "Starting......: syslog turn to master syslog server\n";
		$moinsr="-r";
	}
	
	$f[]="";
	$f[]="SYSLOGD=\"$moinsr\"";
	$f[]="";
	@file_put_contents("/etc/default/syslogd",@implode("\n",$f));
	restart_syslog();
}

function build_server_mode_ubuntu(){
	

	
	if(!is_dir("/etc/rsyslog.d")){
		echo "Starting......: syslog /etc/rsyslog.d no such directory\n";
		return;
	}
	$sock=new sockets();
	$ActAsASyslogServer=$sock->GET_INFO("ActAsASyslogServer");
	$ActAsASyslogClient=$sock->GET_INFO("ActAsASyslogClient");
	if(!is_numeric($ActAsASyslogClient)){$ActAsASyslogClient=0;}
	if(!is_numeric($ActAsASyslogServer)){$ActAsASyslogServer=0;}
	if(is_file("/etc/artica-postfix/WEBSTATS_APPLIANCE")){$ActAsASyslogServer=1;}	
	$serversList=array();
	
	if(($ActAsASyslogServer==0) && ($ActAsASyslogClient==0)){
		echo "Starting......: syslog Client or server are disabled\n";
		@unlink("/etc/rsyslog.d/artica.conf");
		return;
	}	
	
	$libdir=locate_rsyslog_lib();
	echo "Starting......: syslog libdir: $libdir\n";
	if(!is_file("$libdir/imudp.so")){
		echo "Starting......: syslog $libdir/imudp.so no such file\n";
		return; 
	}

if($ActAsASyslogServer==1){
	echo "Starting......: syslog master mode enabled\n";
}
if($ActAsASyslogClient==1){
	echo "Starting......: syslog client mode enabled\n";
	$serversList=unserialize(base64_decode($sock->GET_INFO("ActAsASyslogClientServersList")));
}

if(($ActAsASyslogServer==1) OR ($ActAsASyslogClient=1)){
	echo "Starting......: syslog define communications settings\n";
	$f[]="\$WorkDirectory /var/spool/rsyslog # where to place spool files";
	$f[]="\$ActionQueueFileName uniqName # unique name prefix for spool files";
	$f[]="\$ActionQueueMaxDiskSpace 1g   # 1gb space limit (use as much as possible)";
	$f[]="\$ActionQueueSaveOnShutdown on # save messages to disk on shutdown";
	$f[]="\$ActionQueueType LinkedList   # run asynchronously";
	$f[]="\$ActionResumeRetryCount -1    # infinite retries if host is down";
	$f[]="\$ModLoad imudp.so  # provides UDP syslog reception";
	$f[]="";
}


if($ActAsASyslogClient==1){
	if(count($serversList)>0){
		while (list ($num, $server) = each ($serversList) ){
			if($server==null){continue;}
			$f[]="*.*\t@$server";
		}
	}
}

$f[]="";
$f[]="#\$ModLoad imtcp.so  # load module";

if(is_file("$libdir/imklog.so")){
	echo "Starting......: syslog set imklog module\n";
	$f[]="\$ModLoad imklog.so  # load module";
}
if(is_file("$libdir/immark.so")){
	echo "Starting......: syslog set immark module\n";
	$f[]="\$ModLoad immark.so  # load module";
}
   

if($ActAsASyslogServer==1){
	$f[]="\$UDPServerRun 514 # start a UDP syslog server at standard port 514";
}

//http://www.rsyslog.com/doc/rsyslog_tls.html
$f[]="#\$DefaultNetstreamDriver gtls";
$f[]="#\$DefaultNetstreamDriverCAFile /etc/rsyslog.d/ca.pem";
$f[]="#\$DefaultNetstreamDriverCertFile /etc/rsyslog.d/server_cert.pem";
$f[]="#\$DefaultNetstreamDriverKeyFile /etc/rsyslog.d/server_key.pem";
$f[]="#\$ModLoad imtcp # load TCP listener";
$f[]="#\$InputTCPServerStreamDriverMode 1 # run driver in TLS-only mode";
$f[]="#\$InputTCPServerStreamDriverAuthMode anon # client is NOT authenticated";
$f[]="#\$InputTCPServerRun 10514 # start up listener at port 10514";
$f[]="#\$DefaultNetstreamDriverCAFile /etc/rsyslog.d/ca.pem";
$f[]="#\$DefaultNetstreamDriver gtls # use gtls netstream driver";
$f[]="#\$ActionSendStreamDriverMode 1 # require TLS for the connection";
$f[]="#\$ActionSendStreamDriverAuthMode anon # server is NOT authenticated";
$f[]="#*.* @@(o)server.example.net:10514 # send (all) messages";
$f[]="";

@file_put_contents("/etc/rsyslog.d/artica.conf",@implode("\n",$f));
@file_put_contents("/etc/rsyslog.d/artica-authpriv.conf","auth,authpriv.*			/var/log/auth.log");
restart_syslog();	
}


function restart_syslog(){
	if($GLOBALS["NORESTART"]){return;}
	echo "Starting......: syslog restart daemon\n";
	$unix=new unix();
	$sysloginit=$unix->LOCATE_SYSLOG_INITD();
	if(!is_file($sysloginit)){echo "Starting......: syslog init.d/*? no such file\n";return;}
	exec("$sysloginit restart 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		if(trim($line)==null){continue;}
		echo "Starting......: syslog $line\n";
	}
		
}

function locate_rsyslog_lib(){
	if(is_file("/usr/lib/rsyslog/imudp.so")){return "/usr/lib/rsyslog";}
	if(is_file("/usr/lib64/rsyslog/imudp.so")){return "/usr/lib64/rsyslog";}
	if(is_file("/lib/rsyslog/imudp.so")){return "/lib/rsyslog";}
	if(is_file("/lib64/rsyslog/imudp.so")){return "/lib64/rsyslog";}
	
}


function admin_logs(){
	
	if(system_is_overloaded()){return;}
	
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}	
	$t=0;
	include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
	$q=new mysql();
	foreach (glob("/var/log/artica-postfix/adminevents/*") as $filename) {
		$sql=@file_get_contents($filename);
		if(trim($sql)==null){@unlink($filename);}
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){writelogs("Fatal, $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
			if(strpos($q->mysql_error,"Column count doesn't match value count")>0){@unlink($filename);}
			if(strpos($q->mysql_error,"nknown column")>0){writelogs("Fatal -> DROP TABLE ",__FUNCTION__,__FILE__,__LINE__);$q->QUERY_SQL("DROP TABLE adminevents","artica_events");$q->BuildTables();}
		continue;}
		@unlink($filename);
	}
	
	ps_mem();
		
}

function squid_admin_notifs_check($nopid=false){
	$f=array();
	if($nopid){
		$unix=new unix();
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}
		$t=0;

	}

	$sock=new sockets();
	$users=new usersMenus();
	$UfdbguardSMTPNotifs=unserialize(base64_decode($sock->GET_INFO("UfdbguardSMTPNotifs")));
	if(!isset($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"])){$UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]=0;}
	if(!is_numeric($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"])){$UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]=0;}
	
	// removed : foreach (glob("/var/log/artica-postfix/system_admin_events/*") as $filename) {
	$BaseWorkDir="/var/log/artica-postfix/squid_admin_notifs";
	
	if (!$handle = opendir($BaseWorkDir)) {
		echo "Failed open $BaseWorkDir\n";
		return;
	}

	
	include_once(dirname(__FILE__) . '/ressources/class.mail.inc');
	include_once(dirname(__FILE__)."/ressources/smtp/class.phpmailer.inc");
	
	
	$smtp_dest=$UfdbguardSMTPNotifs["smtp_dest"];
	$smtp_sender=$UfdbguardSMTPNotifs["smtp_sender"];
	if($smtp_dest==null){return;}
	if($smtp_sender==null){$smtp_sender="root@artica.localhost.localdomain";}	

	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		$array=unserialize(@file_get_contents($targetFile));
		@unlink($targetFile);
		if(!is_array($array)){continue;}
		if($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]==0){continue;}
		
		
		if(!is_numeric($array["TASKID"])){$array["TASKID"]=0;}

		
		$content=$array["text"];
		$content_array=explode("\n",$content);
		if(count($content_array)>0){
			for($i=0;$i<count($content_array);$i++){
				if(trim($content_array[$i])==null){continue;}
				if($GLOBALS["VERBOSE"]){echo "Strip `{$content_array[$i]}` line ".__LINE__."\n";}
				$subject=substr($content_array[$i],0,75)."...";
				break;
			}
			$content=@implode("\r\n", $content_array);
		}else{
			if($GLOBALS["VERBOSE"]){echo "Strip `{$content}`\n";}
			$subject=substr($content,0,75)."...";
		}
		
		unset($array["text"]);
		$content=$content."\r\n------------------------------------------\r\n";
		while (list ($key, $value) = each ($array) ){
			$content=$content."$key.....: $value\r\n";
			
		}
		
		
		$subject="[$users->hostname]: $subject";
		$mail = new PHPMailer(true);
		$mail->IsSMTP();
		$mail->AddAddress($smtp_dest,$smtp_dest);
		$mail->AddReplyTo($smtp_sender,$smtp_sender);
		$mail->From=$smtp_sender;
		$mail->Subject=$subject;
		$mail->Body=$content;
		$mail->Host=$UfdbguardSMTPNotifs["smtp_server_name"];
		$mail->Port=$UfdbguardSMTPNotifs["smtp_server_port"];
		
		if(($UfdbguardSMTPNotifs["smtp_auth_user"]<>null) && ($UfdbguardSMTPNotifs["smtp_auth_passwd"]<>null)){
			$mail->SMTPAuth=true;
			$mail->Username=$UfdbguardSMTPNotifs["smtp_auth_user"];
			$mail->Password=$UfdbguardSMTPNotifs["smtp_auth_passwd"];
			if($UfdbguardSMTPNotifs["tls_enabled"]==1){$mail->SMTPSecure = 'tls';}
			if($UfdbguardSMTPNotifs["ssl_enabled"]==1){$mail->SMTPSecure = 'ssl';}
		}
		
		if(!$mail->Send()){system_admin_events("Unable to send notification $mail->ErrorInfo\nsubject:\n$subject\nBody:\n$content",__FUNCTION__, __FILE__, __LINE__, "notifications");}		
		WriteMyLogs("squid_admin_notifs_check:{$array["function"]}/{$array["file"]}: Task  `{$array["TASKID"]}` ". strlen("{$array["text"]}")."bytes",__FUNCTION__,__FILE__,__LINE__);
		
	}

	

}

function system_admin_events_checks($nopid=false){
	$f=array();
	if($nopid){
		$unix=new unix();
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}	
		$t=0;		
		@file_put_contents($pidfile, getmypid());
	}	
	
	// removed : foreach (glob("/var/log/artica-postfix/system_admin_events/*") as $filename) {
	$BaseWorkDir="/var/log/artica-postfix/system_admin_events";
	if (!$handle = opendir($BaseWorkDir)) {
		echo "Failed open $BaseWorkDir\n";
		return;
	}
	
	$q=new mysql();	
	
	$prefix="INSERT IGNORE INTO system_admin_events (`zDate`,`function`,`filename`,`line`,`description`,`category`,`TASKID`) VALUES ";
	while (false !== ($filename = readdir($handle))) {
			if($filename=="."){continue;}
			if($filename==".."){continue;}
			$targetFile="$BaseWorkDir/$filename";
			$array=unserialize(@file_get_contents($targetFile));
			if(!is_array($array)){
				$array["text"]=basename($filename)." is not an array, skip event \n".@file_get_contents($targetFile);
				$array["zdate"]=date('Y-m-d H:i:s');
				$array["pid"]=getmypid();
				$array["function"]=__FUNCTION__;
				$array["category"]="parser";
				$array["file"]=basename(__FILE__);
				$array["line"]=__LINE__;
			}			
			
			
		if(!is_numeric($array["TASKID"])){
			$array["TASKID"]=0;
		}		
		
		$tableName="Taskev{$array["TASKID"]}";
		$chkTables[$tableName]=true;
		
		$array["text"]=mysql_escape_string($array["text"]);
		$array["text"]=str_replace("'", "`", $array["text"]);
		WriteMyLogs(substr($array["text"],0,128),__FUNCTION__,__FILE__,__LINE__);
		WriteMyLogs("system_admin_events:{$array["function"]}/{$array["file"]}: Task  `{$array["TASKID"]}` ". strlen("{$array["text"]}")." bytes",__FUNCTION__,__FILE__,__LINE__);
		$f[$tableName][]="('{$array["zdate"]}','{$array["function"]}','{$array["file"]}','{$array["line"]}','{$array["text"]}','{$array["category"]}')";
		if(count($f[$tableName])>1500){
			system_admin_events_inject($f,true);
			$f=array();
		}
		if(count($f)>10){
			system_admin_events_inject($f,true);
			$f=array();
		}
		
		@unlink($targetFile);
	}
	
	system_admin_events_inject($f);
	
}

function system_admin_events_inject($f,$nooptimize=false){
	if(count($f)==0){return;}	
	$tq=new mysql_builder();
	$sock=new sockets();
	$q=new mysql();	
	
		while (list ($tablename, $rows) = each ($f) ){
			if(!$tq->CheckTableTaskEvents($tablename)){
				WriteMyLogs("system_admin_events:: $tablename: `CheckTableTaskEvents failed`",__FUNCTION__,__FILE__,__LINE__);
				continue;
			}
			$chkTables[$tablename]=true;
			$prefix="INSERT IGNORE INTO `$tablename` (`zDate`,`function`,`filename`,`line`,`description`,`category`) VALUES ";
			$sql=$prefix.@implode(",", $rows);
			$q->QUERY_SQL($sql,"artica_events");
			if(!$q->ok){
				WriteMyLogs("system_admin_events_inject:: $tablename: `$q->mysql_error`",__FUNCTION__,__FILE__,__LINE__);
				writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
				if(preg_match("#Can't find file#", "$q->mysql_error")){
					$q->QUERY_SQL("DROP TABLE $tablename","artica_events");
					$tq->CheckTableTaskEvents($tablename);
					$q->QUERY_SQL($sql,"artica_events");
				}
			}else{
				if($GLOBALS["VERBOSE"]){echo "$tablename (".count($rows)." rows)\n";}
			}			
			
		}
	
	

	$settings=unserialize(base64_decode($sock->GET_INFO("FcronSchedulesParams")));
	if(!is_numeric($settings["max_events"])){$settings["max_events"]="10000";}
	if(count($chkTables)==0){return;}	
	
		while (list ($tablename, $rows) = each ($chkTables) ){
			$NumRows=$q->COUNT_ROWS("$tablename", "artica_events");
			if($NumRows>$settings["max_events"]){
				$toDelete=$NumRows-$settings["max_events"];
				$q->QUERY_SQL("DELETE FROM `$tablename` ORDER BY zDate LIMIT $toDelete","artica_events");
				if(!$q->ok){
				 if(preg_match("#Got error 134 from storage engine#i", $q->mysql_error)){
					$q->QUERY_SQL("REPAIR TABLE `$tablename` QUICK","artica_events");
					$q->QUERY_SQL("DELETE FROM `$tablename` ORDER BY zDate LIMIT $toDelete","artica_events");
					}
				if(!$q->ok){writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
			}else{
				if(!$nooptimize){$q->QUERY_SQL("OPTIMIZE TABLE `$tablename`","artica_events");}
				}
			}	
		}
		
	
}


function udfbguard_admin_events($nopid=false){
	$f=array();
	if($nopid){
		$unix=new unix();
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}	
		$t=0;		
		
	}	
	
	$q=new mysql();	
	$q->BuildTables();
	if(!$q->TABLE_EXISTS('ufdbguard_admin_events','artica_events',true)){return;mysql_admin_events_check();}
	$prefix="INSERT IGNORE INTO ufdbguard_admin_events (`zDate`,`function`,`filename`,`line`,`description`,`category`,`TASKID`) VALUES ";
	foreach (glob("/var/log/artica-postfix/ufdbguard_admin_events/*") as $filename) {
		$array=unserialize(@file_get_contents($filename));
		if(!is_array($array)){
			$array["text"]=basename($filename)." is not an array, skip event ".@file_get_contents($filename);
			$array["zdate"]=date('Y-m-d H:i:s');
			$array["pid"]=getmypid();
			$array["function"]=__FUNCTION__;
			$array["category"]="parser";
			$array["file"]=basename(__FILE__);
			$array["line"]=__LINE__;
			
		}			
			
		if($array["category"]=="ufdbguard-service"){udfbguard_admin_events_smtp($array);}
			
		if(!is_numeric($array["TASKID"])){$array["TASKID"]=0;}
		$tableName="TaskSq{$array["TASKID"]}";
		
		
		$array["text"]=mysql_escape_string($array["text"]);
		WriteMyLogs("ufdbguard_admin_events:{$array["function"]}/{$array["file"]}: Task  `{$array["TASKID"]}` ". strlen("{$array["text"]}")."bytes",__FUNCTION__,__FILE__,__LINE__);
		$f[$tableName][]="('{$array["zdate"]}','{$array["function"]}','{$array["file"]}','{$array["line"]}','{$array["text"]}','{$array["category"]}')";
		@unlink($filename);
	}
	
	system_admin_events_inject($f);
	udfbguard_update_events();
	
}
function udfbguard_update_events($nopid=false){
	if($GLOBALS["VERBOSE"]){echo "udfbguard_update_events\n";}
	$f=array();
	if($nopid){
		$unix=new unix();
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}
		$t=0;

	}

	$q=new mysql_squid_builder();
	$q->CheckTables();
	if(!$q->TABLE_EXISTS('webfilter_updateev','artica_events',true)){$q->CheckTables();}
	$prefix="INSERT IGNORE INTO webfilter_updateev (`zDate`,`function`,`filename`,`line`,`description`,`category`,`TASKID`) VALUES ";
	foreach (glob("/var/log/artica-postfix/ufdbguard_update_events/*") as $filename) {
		$array=unserialize(@file_get_contents($filename));
		if($GLOBALS["VERBOSE"]){echo "$filename\n";}
		if(!is_array($array)){
			if($GLOBALS["VERBOSE"]){echo "$filename not an array\n";}
			@unlink($filename);continue;}

		
		if(!is_numeric($array["TASKID"])){$array["TASKID"]=0;}
		$array["text"]=mysql_escape_string($array["text"]);
		WriteMyLogs("udfbguard_update_events:{$array["function"]}/{$array["file"]}: Task  `{$array["TASKID"]}` ". strlen("{$array["text"]}")."bytes",__FUNCTION__,__FILE__,__LINE__);
		$f[]="('{$array["zdate"]}','{$array["function"]}','{$array["file"]}','{$array["line"]}','{$array["text"]}','{$array["category"]}','{$array["TASKID"]}')";
		@unlink($filename);
	}
	
	if(count($f)>0){
		$q->QUERY_SQL($prefix.@implode(",", $f));
		if($GLOBALS["VERBOSE"]){echo $prefix.@implode(",", $f)."\n";}
		if(!$q->ok){writelogs($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);}
	}
	

	


}
function udfbguard_admin_events_smtp($array){
	include_once(dirname(__FILE__) . '/ressources/class.mail.inc');
	include_once(dirname(__FILE__)."/ressources/smtp/class.phpmailer.inc");
	
	if(count($array)==0){return;}
	
	$users=new usersMenus();
	$sock=new sockets();
	$UfdbguardSMTPNotifs=unserialize(base64_decode($sock->GET_INFO("UfdbguardSMTPNotifs")));
	if(!isset($UfdbguardSMTPNotifs["ENABLED"])){return ;}
	if($UfdbguardSMTPNotifs["ENABLED"]==0){return ;}
	$smtp_dest=$UfdbguardSMTPNotifs["smtp_dest"];
	$smtp_sender=$UfdbguardSMTPNotifs["smtp_sender"];
	if($smtp_dest==null){return;}
	if($smtp_sender==null){$smtp_sender="root@artica.localhost.localdomain";}
	
	while (list ($a, $b) = each ($array)){$TEXTZ[]="$a = $b";}
	
	$subject="[$users->hostname]: Web filtering service notification";
	$text=@implode("\r\n", $TEXTZ);
	$mail = new PHPMailer(true);
	$mail->IsSMTP();
	$mail->AddAddress($smtp_dest,$smtp_dest);
	$mail->AddReplyTo($smtp_sender,$smtp_sender);
	$mail->From=$smtp_sender;
	$mail->Subject=$subject;
	$mail->Body=$text;
	$mail->Host=$UfdbguardSMTPNotifs["smtp_server_name"];
	$mail->Port=$UfdbguardSMTPNotifs["smtp_server_port"];
	
	if(($UfdbguardSMTPNotifs["smtp_auth_user"]<>null) && ($UfdbguardSMTPNotifs["smtp_auth_passwd"]<>null)){
		$mail->SMTPAuth=true;
		$mail->Username=$UfdbguardSMTPNotifs["smtp_auth_user"];
		$mail->Password=$UfdbguardSMTPNotifs["smtp_auth_passwd"];
		if($UfdbguardSMTPNotifs["tls_enabled"]==1){$mail->SMTPSecure = 'tls';}
		if($UfdbguardSMTPNotifs["ssl_enabled"]==1){$mail->SMTPSecure = 'ssl';}
	
	
	}
	
	$mail->Send();	
	
	
}


function mysql_admin_events_check($nopid=false){
	$f=array();
	if($nopid){
		$unix=new unix();
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}	
		$t=0;		
		
	}	
	
$q=new mysql();	
	if(!$q->TABLE_EXISTS('mysql_events','artica_events')){$q->BuildTables();}
	if(!$q->TABLE_EXISTS('mysql_events','artica_events',true)){return;}
	$users=new usersMenus();
	$hostname=$users->hostname;
	$prefix="INSERT IGNORE INTO mysql_events (`zDate`,`function`,`process`,`line`,`description`,`category`,`servername`) VALUES ";
	foreach (glob("/var/log/artica-postfix/mysql_admin_events/*") as $filename) {
		$array=unserialize(@file_get_contents($filename));
		if(!is_array($array)){
			$array["text"]=basename($filename)." is not an array, skip event ".@file_get_contents($filename);
			$array["date"]=date('Y-m-d H:i:s');
			$array["pid"]=getmypid();
			$array["function"]=__FUNCTION__;
			$array["category"]="parser";
			$array["file"]=basename(__FILE__);
			$array["line"]=__LINE__;
		}			
			
			
			
		$array["text"]=addslashes($array["text"]);
		$f[]="('{$array["zdate"]}','{$array["function"]}','{$array["file"]}','{$array["line"]}','{$array["text"]}','{$array["category"]}','$hostname')";
		@unlink($filename);
	}
	
	if(count($f)>0){$sql=$prefix.@implode(",", $f);
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){
			writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		}
	
	}
	
	$sock=new sockets();
	$settings=unserialize(base64_decode($sock->GET_INFO("FcronSchedulesParams")));
	if(!is_numeric($settings["max_events"])){$settings["max_events"]="10000";}
	$NumRows=$q->COUNT_ROWS("mysql_events", "artica_events");
	if($NumRows>$settings["max_events"]){
		$toDelete=$NumRows-$settings["max_events"];
		$q->QUERY_SQL("DELETE FROM mysql_events ORDER BY zDate LIMIT $toDelete","artica_events");
	}
	update_events_check();
	
	
}

function update_events_check($nopid=false){
	$f=array();
	if($nopid){
		$unix=new unix();
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}	
		$t=0;		
		
	}	
	
	$q=new mysql();	
	if(!$q->TABLE_EXISTS('update_events','artica_events')){$q->BuildTables();}
	if(!$q->TABLE_EXISTS('update_events','artica_events',true)){return;}
	$users=new usersMenus();
	$hostname=$users->hostname;
	$prefix="INSERT IGNORE INTO update_events (`zDate`,`function`,`process`,`line`,`description`,`category`,`servername`) VALUES ";
	foreach (glob("/var/log/artica-postfix/update_admin_events/*") as $filename) {
		$array=unserialize(@file_get_contents($filename));
		if(!is_array($array)){
			$array["text"]=basename($filename)." is not an array, skip event ".@file_get_contents($filename);
			$array["date"]=date('Y-m-d H:i:s');
			$array["pid"]=getmypid();
			$array["function"]=__FUNCTION__;
			$array["category"]="sys-update";
			$array["file"]=basename(__FILE__);
			$array["line"]=__LINE__;
		}			
			
			
			
		$array["text"]=addslashes($array["text"]);
		$f[]="('{$array["zdate"]}','{$array["function"]}','{$array["file"]}','{$array["line"]}','{$array["text"]}','{$array["category"]}','$hostname')";
		@unlink($filename);
	}
	
	if(count($f)>0){$sql=$prefix.@implode(",", $f);
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){
			writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		}
	
	}
	
	$f=array();
	
	foreach (glob("/var/log/artica-postfix/artica-update-*.debug") as $filename) {
		$file=basename($filename);
		$unix=new unix();
		$last_modified = filemtime($filename);
		$array["zdate"]=date('Y-m-d H:i:s',$last_modified);
		$array["pid"]=0;
		$array["function"]="update-deamon";
		$array["category"]="firmware-update";
		$array["file"]="artica-update";
		$array["line"]=0;
		$array["text"]=addslashes(@file_get_contents($filename));
		$f[]="('{$array["zdate"]}','{$array["function"]}','{$array["file"]}','{$array["line"]}','{$array["text"]}','{$array["category"]}','$hostname')";
		@unlink($filename);
		
		
	}
	
	
if(count($f)>0){$sql=$prefix.@implode(",", $f);
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){
			writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		}
	
	}	
	
	$sock=new sockets();
	$settings=unserialize(base64_decode($sock->GET_INFO("FcronSchedulesParams")));
	if(!is_numeric($settings["max_events"])){$settings["max_events"]="10000";}
	$NumRows=$q->COUNT_ROWS("update_events", "artica_events");
	if($NumRows>$settings["max_events"]){
		$toDelete=$NumRows-$settings["max_events"];
		$q->QUERY_SQL("DELETE FROM update_events ORDER BY zDate LIMIT $toDelete","artica_events");
	}	


	
	
}


function dhcpd_logs($nopid=false){
	$f=array();
	if($nopid){
		$unix=new unix();
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}	
		$t=0;		
		
	}
	$q=new mysql();	
	if(!$q->TABLE_EXISTS('dhcpd_logs','artica_events')){$q->BuildTables();}
	if(!$q->TABLE_EXISTS('dhcpd_logs','artica_events',true)){return;}
	$prefix="INSERT IGNORE INTO dhcpd_logs (`zDate`,`description`) VALUES ";
	foreach (glob("/var/log/artica-postfix/dhcpd/*") as $filename) {
		$sqlcontent=@file_get_contents($filename);
		if(trim($sqlcontent)==null){@unlink($filename);continue;}
		
		$f[]=$sqlcontent;
		@unlink($filename);
	}
	
	if(count($f)>0){$sql=$prefix.@implode(",", $f);
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){
			writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		}
	
	}
	
	
	
	
}



function crossroads($nopid=false){
	$f=array();
	if($nopid){
		$unix=new unix();
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}	
		$t=0;		
		
	}
	$q=new mysql();	
	if(!$q->TABLE_EXISTS('crossroads_events','artica_events')){$q->BuildTables();}
	if(!$q->TABLE_EXISTS('crossroads_events','artica_events',true)){return;}
	$prefix="INSERT IGNORE INTO crossroads_events (`zDate`,`instance_id`,`function`,`line`,`description`) VALUES ";
	foreach (glob("/var/log/artica-postfix/crossroads/*") as $filename) {
		$array=unserialize(@file_get_contents($filename));
		if(!is_array($array)){@unlink($filename);continue;}		
		$array["TEXT"]=addslashes($array["TEXT"]);
		$f[]="('{$array["TIME"]}','{$array["ID"]}','{$array["FUNCTION"]}','{$array["LINE"]}','{$array["TEXT"]}')";
		@unlink($filename);
	}
	
	if(count($f)>0){$sql=$prefix.@implode(",", $f);
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){
			writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		}
	
	}
	

	
}




function ps_mem($nopid=false){
	
	if($nopid){
		$unix=new unix();
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}	
		$t=0;		
		@unlink($pidfile);
		@file_put_contents($pidfile, getmypid());
	}
	
	$timefile=$unix->file_time_min($pidfile);
	if($timefile<5){
		writelogs("Minimal time = 5mn, current = $timefile",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	
	
	
	
	$q=new mysql();
	$prefix="INSERT IGNORE INTO ps_mem (zmd5,zDate,process,memory) VALUES ";
	if($GLOBALS["VERBOSE"]){writelogs("Starting glob()...",__FUNCTION__,__FILE__,__LINE__);}
	foreach (glob("/var/log/artica-postfix/ps-mem/*") as $filename) {
		$array=unserialize(@file_get_contents($filename));
		if(!is_array($array)){@unlink($filename);continue;}
		$md5=md5(serialize($array));
		$f[]="('$md5','{$array["time"]}','{$array["process"]}','{$array["mem"]}')";
		if(count($f)>500){
			$sql=$prefix.@implode(",", $f);
			$f=array();
			$q->QUERY_SQL($sql,"artica_events");
			if(!$q->ok){writelogs("Fatal, $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
		}
		@unlink($filename);
		
	}
	
	if(count($f)>0){
			$sql=$prefix.@implode(",", $f);
			$f=array();
			$q->QUERY_SQL($sql,"artica_events");
			if(!$q->ok){writelogs("Fatal, $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
		}	
		
// -------------------------------------------------------------------------

		
	$prefix="INSERT IGNORE INTO ps_mem_tot (zDate,mem) VALUES ";
	foreach (glob("/var/log/artica-postfix/ps-mem-tot/*") as $filename) {	
		$array=unserialize(@file_get_contents($filename));
		if(!is_array($array)){@unlink($filename);continue;}
		$md5=md5(serialize($array));
		$f[]="('{$array["time"]}','{$array["mem"]}')";
		if(count($f)>500){
			$sql=$prefix.@implode(",", $f);
			$f=array();
			$q->QUERY_SQL($sql,"artica_events");
			if(!$q->ok){writelogs("Fatal, $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
		}
		@unlink($filename);
		
	}
	
	if(count($f)>0){
			$sql=$prefix.@implode(",", $f);
			$f=array();
			$q->QUERY_SQL($sql,"artica_events");
			if(!$q->ok){writelogs("Fatal, $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
		}

	$unix=new unix();
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if($unix->file_time_min($timefile)>440){
		$q->QUERY_SQL("DELETE FROM ps_mem_tot WHERE zDate<DATE_SUB(NOW(),INTERVAL 35 DAY)","artica_events");
		$q->QUERY_SQL("DELETE FROM ps_mem WHERE zDate<DATE_SUB(NOW(),INTERVAL 35 DAY)","artica_events");
		@unlink($timefile);
		@file_put_contents($timefile, time());
	}
	
}

		
function snort_logs(){
	
	if(system_is_overloaded()){return;}
	
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){
		echo "Already running pid $pid\n";
		return;
	}	
	$t=0;
	include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
	$q=new mysql();
	foreach (glob("/var/log/artica-postfix/snort-queue/*") as $filename) {
		$base=basename($filename);
		if(!preg_match("#([0-9]+)\..+?\.snort#",$base,$re)){@unlink($filename);continue;}
		$zDate=date("Y-m-d H:i:s",$re[1]);
		echo $zDate." -> {$re[1]}\n";
		$array=unserialize(@file_get_contents($filename));
		if(!is_array($array)){@unlink($filename);continue;}
		$local_ipaddr=$array[7];
		$port=$array[8];
		$ipaddr=$array[5];
		if(!isset($GLOBALS["RESOLV"][$ipaddr])){
			$hostname=gethostbyaddr($ipaddr);
			$GLOBALS["RESOLV"][$ipaddr]=$hostname;
		}else{
			$hostname=$GLOBALS["RESOLV"][$ipaddr]=$hostname;
		}
		if(!isset($GLOBALS["GEO"][$ipaddr])){
			if(function_exists("geoip_record_by_name")){
				$record = geoip_record_by_name($ipaddr);
				if ($record) {
					$country=$record["country_name"];
					$GLOBALS["GEO"][$ipaddr]=$country;
				}
			}
		}else{
			$country=$GLOBALS["GEO"][$ipaddr];
		}	
		$infos=$array[1];
		$classification=$array[2];
		if(preg_match("#SCAN.+?Port.+?attempt#",$infos)){$unix->send_email_events("$infos FROM $ipaddr","Country:$country\nHostname:$hostname\nclassification:$classification","security");}

		$proto=$array[4];
		$priority=$array[3];
		if($GLOBALS["VERBOSE"]){echo "$hostname\n";}
		
		
		
		$sql="INSERT IGNORE INTO `snort`
		(`zDate`,`hostname`,`ipaddr` ,`local_ipaddr`,`port`,`infos`,`classification`,`priority`,`proto` ,`country`)
  VALUES('$zDate','$hostname','$ipaddr' ,'$local_ipaddr','$port','$infos','$classification','$priority','$proto' ,'$country')";

		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){
			if($GLOBALS["VERBOSE"]){echo $q->mysql_error."\n";}
			continue;
		}
		$t++;
		@unlink($filename);
	}
	
	if($t>0){
		writelogs("Adding $t entries",__FUNCTION__,__FILE__,__LINE__);
	}
	

	
}


function authlogs(){
	include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
	include_once(dirname(__FILE__) . '/ressources/class.auth.tail.inc');
	include_once(dirname(__FILE__) . '/ressources/class.iptables-chains.inc');
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){echo "Already running pid $pid\n";return;}
	
	$q=new mysql();
	foreach (glob("/var/log/artica-postfix/sshd-failed/*") as $filename) {
		events("Open $filename",__FUNCTION__,__FILE__,__LINE__);
		$array=unserialize(@file_get_contents($filename));
		$zdate=date("Y-m-d H:i:s",basename($filename));
		if(is_array($array)){
			while (list ($ip, $uid) = each ($array)){
				$hostname=gethostbyaddr($ip);
				if(function_exists("geoip_record_by_name")){
					$record = geoip_record_by_name($ip);
					if (!$record) {ssh_events("Unable to detect country for $ip",__FUNCTION__,__FILE__,__LINE__);}else{
						$Country=$record["country_name"];
					}
				}
				$Country=addslashes($Country);
				$hostname=addslashes($hostname);
				$uid=addslashes($uid);
				ssh_events("SSH Failed $ip $hostname ($Country)",__FUNCTION__,__FILE__,__LINE__);
				$sql="INSERT IGNORE INTO auth_events (ipaddr,hostname,success,uid,zDate,Country) VALUES ('$ip','$hostname','0','$uid','$zdate','$Country')";
				$q->QUERY_SQL($sql,"artica_events");
				if(!$q->ok){ssh_events($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);}else{@unlink($filename);}
			}
		}
	}
	
	foreach (glob("/var/log/artica-postfix/sshd-success/*") as $filename) {
		$array=unserialize(@file_get_contents($filename));
		$zdate=date("Y-m-d H:i:s",basename($filename));
		while (list ($ip, $uid) = each ($array)){
			if(!isset($GLOBALS["HOSTNAME"][$ip])){$GLOBALS["HOSTNAME"][$ip]=gethostbyaddr($ip);}
			$hostname=$GLOBALS["HOSTNAME"][$ip];
			
			if(function_exists("geoip_record_by_name")){
					$record = geoip_record_by_name($ip);
					if (!$record) {ssh_events("Unable to detect country for $ip",__FUNCTION__,__FILE__,__LINE__);}else{
						$Country=$record["country_name"];
					}
				}	
			$Country=addslashes($Country);
			$hostname=addslashes($hostname);
			$uid=addslashes($uid);					
			$sql="INSERT IGNORE INTO auth_events (ipaddr,hostname,success,uid,zDate,Country) VALUES ('$ip','$hostname','1','$uid','$zdate','$Country')";
			ssh_events("SSH Success $ip $hostname ($Country) `$sql`",__FUNCTION__,__FILE__,__LINE__);
			$q->QUERY_SQL($sql,"artica_events");
			if(!$q->ok){ssh_events($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);}else{@unlink($filename);}}
		}

		authfw();
		snort_logs();
		loadavg_logs();
		clamd_mem();
}

function clamd_mem(){
	include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
	$f=array();
	$unix=new unix();
	$q=new mysql();
	if(!$q->TABLE_EXISTS("clamd_mem", "artica_events")){
		$q->QUERY_SQL("CREATE TABLE `artica_events`.`clamd_mem` (`zDate` TIMESTAMP NOT NULL ,`rss` INT( 10 ) NOT NULL ,`vm` INT( 10 ) NOT NULL ,PRIMARY KEY ( `zDate` ))","artica_events");
	}
	$prefix="INSERT IGNORE INTO clamd_mem (zDate,rss,vm) VALUES ";
	
	
	foreach (glob("/var/log/artica-postfix/clamd-mem/*") as $filename) {
		events("Open $filename",__FUNCTION__,__FILE__,__LINE__);
		$content=trim(@file_get_contents($filename));
		@unlink($filename);
		if($content==null){continue;}
		$f[]=$content;
		if(count($f)>100){
			$sql=$prefix.@implode(",", $f);
			$f=array();
			$q->QUERY_SQL($sql,"artica_events");
		}
		
	}
	
		if(count($f)>0){
			$sql=$prefix.@implode(",", $f);
			$f=array();
			$q->QUERY_SQL($sql,"artica_events");
		}	
	

	
}

function sessions_logs(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	
	if(system_is_overloaded()){return;}
	$q=new mysql();
	foreach (glob("/usr/share/artica-postfix/ressources/logs/web/queue/sessions/*") as $filename) {
		$base=basename($filename);
		if(!preg_match("#([0-9]+)\.(.+)#",$base,$re)){@unlink($filename);continue;}
		$array=unserialize(@file_get_contents($filename));
		@unlink($filename);
		if(!is_array($array)){
			writelogs("Not an array... $base",__FUNCTION__,__FILE__,__LINE__);
			continue;
		}
		if(strlen($array["SESSION_ID"])<3){
			writelogs("SESSION_ID is null...({$array["SESSION_ID"]}) $base",__FUNCTION__,__FILE__,__LINE__);
			continue;
		}
		$sql="DELETE FROM admin_cnx WHERE session_id='{$array["SESSION_ID"]}'";
		$connected=date('Y-m-d H:i:s',$re[1]);
		$q->QUERY_SQL($sql,"artica_events");
		if(!isset($GLOBALS["HOSTNAME"][$array["ipaddr"]])){$GLOBALS["HOSTNAME"][$array["ipaddr"]]=gethostbyaddr($array["ipaddr"]);}
		$hostname=$GLOBALS["HOSTNAME"][$array["ipaddr"]];
		$sql="INSERT IGNORE INTO admin_cnx(connected,session_id,ipaddr,InterfaceType,webserver,hostname,uid) VALUES
		('$connected','{$array["SESSION_ID"]}','{$array["ipaddr"]}','{$array["interface"]}','{$array["myname"]}',
		'$hostname','{$array["uid"]}')";
		
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
		
	}
	
	$sql="DELETE FROM admin_cnx WHERE connected<DATE_SUB(NOW(), INTERVAL 3600 SECOND)";
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}	
	loadavg_logs();
}




function authfw(){
	if($GLOBALS["VERBOSE"]){echo "authfw()\n";}
	$unix=new unix();
	$iptablesClass=new iptables_chains();
	$iptables=$unix->find_program("iptables");
	$GLOBALS["IPTABLES_WHITELISTED"]=$iptablesClass->LoadWhiteLists();
	$sock=new sockets();
	$GlobalIptablesEnabled=$sock->GET_INFO("GlobalIptablesEnabled");
	if(!is_numeric($GlobalIptablesEnabled)){$GlobalIptablesEnabled=1;}	
			
	
	$c=0;
	foreach (glob("/etc/artica-postfix/sshd-fw/*") as $filename) {
		if($GlobalIptablesEnabled<>1){@unlink($filename);continue;}
		$array=unserialize(@file_get_contents($filename));
		$zdate=date("Y-m-d H:i:s",basename($filename));	
		while (list ($IP, $server_name) = each ($array)){
		if($iptablesClass->isWhiteListed($IP)<>null){@unlink($filename);continue;}
		
		
		
		$cmd="$iptables -A INPUT -s $IP -p tcp --destination-port 22 -j DROP -m comment --comment \"ArticaInstantSSH\"";
		$iptablesClass=new iptables_chains();
		$iptablesClass->serverip=$IP;
		$iptablesClass->servername=$server_name;
		$iptablesClass->rule_string=$cmd;
		$iptablesClass->EventsToAdd="Max SSHD connexions";
		if($iptablesClass->addSSHD_chain()){
			$unix->send_email_events("SSHD Hack!: $server_name [$IP] has been banned to your SSH",
			"Artica anti-hack SSH has banned this ip address","system");
			$c++;
			ssh_events("Add IP:Addr=<$IP>, servername=<{$server_name}> to mysql",__FUNCTION__,__FILE__,__LINE__);
			if($GLOBALS["VERBOSE"]){echo "Add IP:Addr=<$IP>, servername=<{$server_name}> to mysql\n";}
			@unlink($filename);
			}

		}
	}
	
	if($c>0){compile_sshd_rules();}
	loadavg_logs();
}

	function ssh_events($text,$function,$file,$line){
		writelogs($text,$function,$file,$line);
		$pid=@getmypid();
		$filename=basename(__FILE__);
		$date=@date("h:i:s");
		$logFile="/var/log/artica-postfix/auth-tail.debug";
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		@fwrite($f, "$pid ".basename(__FILE__)." $text\n");
		@fclose($f);	
		$logFile="/var/log/artica-postfix/syslogger.debug";
		if(!isset($GLOBALS["CLASS_UNIX"])){
			include_once(dirname(__FILE__)."/framework/class.unix.inc");
			$GLOBALS["CLASS_UNIX"]=new unix();
		}
		$GLOBALS["CLASS_UNIX"]->events("$filename $text",$logFile);
		}



function compile_sshd_rules(){
	$sock=new sockets();
	$GlobalIptablesEnabled=$sock->GET_INFO("GlobalIptablesEnabled");
	if(!is_numeric($GlobalIptablesEnabled)){$GlobalIptablesEnabled=1;}	
	if($GlobalIptablesEnabled<>1){iptables_delete_all();return;}
	include_once(dirname(__FILE__)."/ressources/class.openssh.inc");
	$q=new mysql();	
	$iptablesClass=new iptables_chains();
	$unix=new unix();
	$openssh=new openssh();
	$SSHDPort=$openssh->main_array["Port"];
	if(!is_numeric($SSHDPort)){$SSHDPort=22;}
	$iptables=$unix->find_program("iptables");
	$GLOBALS["IPTABLES_WHITELISTED"]=$iptablesClass->LoadWhiteLists();	
	$sql="SELECT * FROM iptables WHERE disable=0 AND flux='INPUT' AND local_port=22";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	iptables_delete_all();
		
	
	
	if($GLOBALS["VERBOSE"]){echo "OpenSSH port is $SSHDPort\n";}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$ip=$ligne["serverip"];
		if($iptablesClass->isWhiteListed($ip)){continue;}
		if($GLOBALS["VERBOSE"]){events("ADD REJECT {$ligne["serverip"]} INBOUND PORT 22");}
		if($GLOBALS["VERBOSE"]){ssh_events("ADD REJECT {$ligne["serverip"]} INBOUND PORT 22",__FUNCTION__,__FILE__,__LINE__);}

		/*if($InstantIptablesEventAll==1){
			if($GLOBALS["VERBOSE"]){echo "$ip -> LOG\n";}
			$cmd="$iptables -A INPUT -s $ip -p tcp --destination-port 25 -j LOG --log-prefix \"SMTP DROP: \" -m comment --comment \"ArticaInstantPostfix\"";
			$commands[]=$cmd;
		}*/
		
		$cmd="$iptables -A INPUT -s $ip -p tcp --destination-port $SSHDPort -j DROP -m comment --comment \"ArticaInstantSSH\"";
		$commands[]=$cmd;
	}
	
	if($GLOBALS["VERBOSE"]){echo count($commands)." should be performed\n";}
	
	if(is_array($commands)){
		while (list ($index, $line) = each ($commands) ){
			writelogs($line,__FUNCTION__,__FILE__,__LINE__);
			if($GLOBALS["VERBOSE"]){echo $line."\n";}
			shell_exec($line);
		}
		
		$unix->send_email_events("SSHD Hack ".count($commands)." rules(s) added",null,"system");
		
	}	

	
	
	
}

function iptables_delete_all(){
$unix=new unix();
$iptables_restore=$unix->find_program("iptables-restore");
$iptables_save=$unix->find_program("iptables-save");	
events("Exporting datas iptables-save > /etc/artica-postfix/iptables.conf");
system("$iptables_save > /etc/artica-postfix/iptables.conf");
$data=file_get_contents("/etc/artica-postfix/iptables.conf");
$datas=explode("\n",$data);
$pattern="#.+?ArticaInstantSSH#";	
while (list ($num, $ligne) = each ($datas) ){
		if($ligne==null){continue;}
		if(preg_match($pattern,$ligne)){continue;}
		events("skip rule $ligne from deletion");
		$conf=$conf . $ligne."\n";
		}

events("restoring datas $iptables_restore < /etc/artica-postfix/iptables.new.conf");
file_put_contents("/etc/artica-postfix/iptables.new.conf",$conf);
system("$iptables_restore < /etc/artica-postfix/iptables.new.conf");


}

function events($text){
		$pid=@getmypid();
		$filename=basename(__FILE__);
		$date=@date("h:i:s");
		$logFile="/var/log/artica-postfix/auth-tail.debug";
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		@fwrite($f, "$pid ".basename(__FILE__)." $text\n");
		@fclose($f);	
		$logFile="/var/log/artica-postfix/syslogger.debug";
		if(!isset($GLOBALS["CLASS_UNIX"])){include_once(dirname(__FILE__)."/framework/class.unix.inc");$GLOBALS["CLASS_UNIX"]=new unix();}
		$GLOBALS["CLASS_UNIX"]->events("$filename $text",$logFile);
}

function loadavg_logs(){
	include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){echo "Already running pid $pid\n";return;}	
	$q=new mysql();
	if(!$q->DATABASE_EXISTS("artica_events")){
		if($GLOBALS["VERBOSE"]){echo "Stop !\n";}
		events_Loadavg("loadavg_logs:: artica_events database does not exists... try to build one".__LINE__);
		$q->BuildTables();
	}
	
	if(!$q->DATABASE_EXISTS("artica_events")){
		if($GLOBALS["VERBOSE"]){echo "Stop !\n";}
		events_Loadavg("loadavg_logs:: artica_events database cannot continue".__LINE__);
		return;
	}	
	
if($GLOBALS["VERBOSE"]){echo "Scan /var/log/artica-postfix/loadavg/*\n";}
	foreach (glob("/var/log/artica-postfix/loadavg/*") as $filename) {
		$time=basename($filename);
		$load=@file_get_contents($filename);
		$date=date('Y-m-d H:i:s',$time);
		$sql="INSERT IGNORE INTO loadavg (`stime`,`load`) VALUES ('$date','$load');";
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){events_Loadavg("loadavg_logs:: $q->mysql_error line:".__LINE__);continue;}
		events_Loadavg("loadavg_logs:: success $filename".__LINE__);
		@unlink($filename);
	}
	
	$file_time="/etc/artica-postfix/pids/". basename(__FILE__).".".__FUNCTION__.".time";
	if($unix->file_time_min($file_time)>300){
		$sql="DELETE FROM loadavg WHERE stime < DATE_SUB( NOW( ) , INTERVAL 7 DAY )";
		$q->QUERY_SQL($sql,"artica_events");
		@unlink($file_time);
		@file_put_contents($file_time, time());
	}
	
	
}

function events_Loadavg($text,$function=null,$line=0){
		$filename=basename(__FILE__);
		if(!isset($GLOBALS["CLASS_UNIX"])){
			include_once(dirname(__FILE__)."/framework/class.unix.inc");
			$GLOBALS["CLASS_UNIX"]=new unix();
		}
		$GLOBALS["CLASS_UNIX"]->events("$filename $function:: $text (L.$line)","/var/log/artica-postfix/xLoadAvg.debug");	
		}	

function ipblocks(){
	if(system_is_overloaded()){return;}
	include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nogup=$unix->find_program("nohup");
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){echo "Already running pid $pid\n";return;}	
	$q=new mysql();
	if(!$q->TABLE_EXISTS('ipblocks_db','artica_backup')){$q->BuildTables();}
	if(!is_file($pidtime)){
		$count=$q->COUNT_ROWS("ipblocks_db", "artica_backup");
		if($count==0){shell_exec(trim("$nogup /usr/share/artica-postfix/bin/artica-update --ipblocks >/dev/null 2>&1 &"));}
		sleep(5);
		@file_put_contents($pidtime, time());
	}
	
	if($unix->file_time_min($pidtime)>480){
		shell_exec(trim("$nogup /usr/share/artica-postfix/bin/artica-update --ipblocks >/dev/null 2>&1 &"));
		sleep(5);
		@unlink($pidtime);
		@file_put_contents($pidtime, time());
		$unix->THREAD_COMMAND_SET("$php /usr/share/artica-postfix/exec.postfix.iptables.php --ipdeny");
	}
	
	@file_put_contents($pidfile, getmypid());
	
	foreach (glob("/var/log/artica-postfix/ipblocks/*.zone") as $filename) {
		$basename=basename($filename);
		if(!preg_match("#(.+?)\.zone#", $basename,$re)){continue;}
		$country=$re[1];
		$datas=explode("\n", @file_get_contents($filename));
		$f=true;
		
		while (list ($index, $line) = each ($datas) ){
			$line=trim($line);if($line==null){continue;}if($country==null){continue;}
			$sql="INSERT IGNORE INTO ipblocks_db (cdir,country) VALUES('$line','$country')";
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){events("ipblocks:: $q->mysql_error line:".__LINE__);$f=false;break;}
		}
		if(!$f){continue;}
		@unlink($filename);
	}
	
	$file_time="/etc/artica-postfix/pids/". basename(__FILE__).".".__FUNCTION__.".time";
	if($unix->file_time_min($file_time)>300){
		$sql="DELETE FROM loadavg WHERE stime < DATE_SUB( NOW( ) , INTERVAL 7 DAY )";
		$q->QUERY_SQL($sql,"artica_events");
		@unlink($file_time);
		@file_put_contents($file_time, time());
	}
	
	
}

function haproxy_events(){
	$qs=new mysql_squid_builder();
	$q=new mysql_haproxy_builder();
	if (!$handle = opendir("/var/log/artica-postfix/haproxy-rtm")) {@mkdir("/var/log/artica-postfix/haproxy-rtm",0755,true);return;}
	
	$prefixMid=" (sitename,uri,td,http_code,client,hostname,familysite,service,backend,zDate,size,MAC,zMD5,statuslb)";
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
			$targetFile="/var/log/artica-postfix/haproxy-rtm/$filename";
			$countDeFiles++;
			$ARRAY=unserialize(@file_get_contents($targetFile));
			while (list ($key, $value) = each ($ARRAY) ){$ARRAY[$key]=trim(addslashes($value));}
			$ARRAY["MAC"]=GetMacFromIP($ARRAY["SOURCE"]);
			$hostname=GetComputerName($ARRAY["SOURCE"]);
			$dayhour=date("YmdH",$ARRAY["TIME"]);
			$time=date("H:i:s",$ARRAY["TIME"]);
			$fulldate=date('Y-m-d H:i:s',$ARRAY["TIME"]);
			$table="hour_$dayhour";
			if(preg_match("#(.+?)\s+(.*?)#", $ARRAY["SERVICE"],$ri)){$ARRAY["SERVICE"]=$ri[1];}
			if(preg_match("#(.+?)\s+(.*?)#", $ARRAY["BACKEND"],$ri)){$ARRAY["BACKEND"]=$ri[1];}
			$uri=$ARRAY["URI"];
			$md5=md5(serialize($array));
			if(preg_match("#^(?:[^/]+://)?([^/:]+)#",$uri,$re)){
				$sitename=$re[1];
				if(preg_match("#^www\.(.+)#",$sitename,$ri)){$sitename=$ri[1];}
				$familysite=$qs->GetFamilySites($sitename);
			}
			
		  $linsql="('$sitename','$uri','{$ARRAY["TD"]}','{$ARRAY["HTTP_CODE"]}','{$ARRAY["SOURCE"]}','$hostname','$familysite','{$ARRAY["SERVICE"]}','{$ARRAY["BACKEND"]}','$fulldate','{$ARRAY["BYTES"]}','{$ARRAY["MAC"]}','$md5','{$ARRAY["STATUSLB"]}')";
		  $hash[$table][]=$linsql;
		  if($GLOBALS["VERBOSE"]){echo "Remove: $targetFile\n";}
  		  @unlink($targetFile);
		  if(system_is_overloaded()){break;}
		}
		
		
		while (list ($table, $tr) = each ($hash)){
			if(trim($table)==null){continue;}
			if(!$q->create_TableHour($table)){
				@mkdir("/var/log/artica-postfix/haproxy-errors",0755,true);
				@file_put_contents("/var/log/artica-postfix/haproxy-errors/".md5(serialize($hash)), serialize($hash));
				return;
			}
			
			$sql="INSERT IGNORE INTO $table $prefixMid VALUES ".@implode(",", $tr);
			$q->QUERY_SQL($sql);
			if(!$q->ok){
				WriteMyLogs($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
				@mkdir("/var/log/artica-postfix/haproxy-errors",0755,true);
				@file_put_contents("/var/log/artica-postfix/haproxy-errors/".md5(serialize($hash)), serialize($hash));
				return;				
			}
			
			
		}
		
	haproxy_errors();
	
}

function haproxy_errors(){
	$q=new mysql_haproxy_builder();
	$prefixMid=" (sitename,uri,td,http_code,client,hostname,familysite,service,backend,zDate,size,MAC,zMD5,statuslb)";
	foreach (glob("/var/log/artica-postfix/haproxy-errors/*") as $filename) {
		$hash=unserialize(@file_get_contents($filename));
		while (list ($table, $tr) = each ($hash)){
			if(!$q->create_TableHour($table)){continue;}
			$sql="INSERT IGNORE INTO $table $prefixMid VALUES ".@implode(",", $tr);
			$q->QUERY_SQL($sql);
			if(!$q->ok){
				WriteMyLogs($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
				continue;}
			@unlink($filename);
			if(system_is_overloaded()){return;}
		}		
		
		
	}
	
	
}



function GetComputerName($ip){
		if($GLOBALS["resvip"][$ip]<>null){return $GLOBALS["resvip"][$ip];}
		$name=gethostbyaddr($ip);
		$GLOBALS["resvip"]=$name;
		return $name;
		}

function GetMacFromIP($ipaddr){
		$ipaddr=trim($ipaddr);
		$ttl=date('YmdH');
		if(count($GLOBALS["CACHEARP"])>3){unset($GLOBALS["CACHEARP"]);}
		if(isset($GLOBALS["CACHEARP"][$ttl][$ipaddr])){return $GLOBALS["CACHEARP"][$ttl][$ipaddr];}
		
		if(!isset($GLOBALS["SBIN_ARP"])){$unix=new unix();$GLOBALS["SBIN_ARP"]=$unix->find_program("arp");}
		if(strlen($GLOBALS["SBIN_ARP"])<4){return;}
		
		if(!isset($GLOBALS["SBIN_PING"])){$unix=new unix();$GLOBALS["SBIN_PING"]=$unix->find_program("ping");}
		if(!isset($GLOBALS["SBIN_NOHUP"])){$unix=new unix();$GLOBALS["SBIN_NOHUP"]=$unix->find_program("nohup");}
		
		$cmd="{$GLOBALS["SBIN_ARP"]} -n \"$ipaddr\" 2>&1";
		events($cmd);
		exec("{$GLOBALS["SBIN_ARP"]} -n \"$ipaddr\" 2>&1",$results);
		while (list ($num, $line) = each ($results)){
			if(preg_match("#^[0-9\.]+\s+.+?\s+([0-9a-z\:]+)#", $line,$re)){
				if($re[1]=="no"){continue;}
				$GLOBALS["CACHEARP"][$ttl][$ipaddr]=$re[1];
				return $GLOBALS["CACHEARP"][$ttl][$ipaddr];
			}
			
		}
		events("$ipaddr not found (".__LINE__.")");
		if(!isset($GLOBALS["PINGEDHOSTS"][$ipaddr])){
			shell_exec("{$GLOBALS["SBIN_NOHUP"]} {$GLOBALS["SBIN_PING"]} $ipaddr -c 3 >/dev/null 2>&1 &");
			$GLOBALS["PINGEDHOSTS"][$ipaddr]=true;
		}
			
		
	}
function WriteMyLogs($text,$function,$file,$line){
	$mem=round(((memory_get_usage()/1024)/1000),2);
	if(!isset($GLOBALS["MYPID"])){$GLOBALS["MYPID"]=getmypid();}
	writelogs($text,$function,__FILE__,$line);
	$logFile="/var/log/artica-postfix/".basename(__FILE__).".log";
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
   	if (is_file($logFile)) { 
   		$size=filesize($logFile);
   		if($size>9000000){unlink($logFile);}
   	}
   	$date=date('m-d H:i:s');
	$logFile=str_replace("//","/",$logFile);
	$f = @fopen($logFile, 'a');
	if($GLOBALS["VERBOSE"]){echo "$date [{$GLOBALS["MYPID"]}][{$mem}MB]: [$function::$line] $text\n";}
	@fwrite($f, "$date [{$GLOBALS["MYPID"]}][{$mem}MB][Task:{$GLOBALS["SCHEDULE_ID"]}]: [$function::$line] $text\n");
	@fclose($f);
}

?>