<?php
$GLOBALS["DEBUG_INCLUDES"]=false;$GLOBALS["RELOAD"]=false;$GLOBALS["VERBOSE"]=false;$GLOBALS["NO_USE_BIN"]=false;$GLOBALS["REBUILD"]=false;$GLOBALS["FORCE"]=false;$GLOBALS["OUTPUT"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
if(!is_file("/usr/share/artica-postfix/ressources/settings.inc")){shell_exec("/usr/share/artica-postfix/bin/process1 --force --verbose");}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#--withoutloading#",implode(" ",$argv))){$GLOBALS["NO_USE_BIN"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}


if($argv[1]=="--watchdog"){watchdog();exit;}
if($argv[1]=="--watchdog-klms8"){watchdog_klms8($argv[2]);exit;}
if($argv[1]=="--watchdog-klms8db"){watchdog_klms8db($argv[2]);exit;}
if($argv[1]=="--setup"){setup();exit;}
if($argv[1]=="--InfoToSyslog"){InfoToSyslog();exit;}
if($argv[1]=="--build"){buildConf();exit;}
if($argv[1]=="--resetpwd"){resetpwd();exit;}
if($argv[1]=="--build-restart"){build_restart();exit;}

function InfoToSyslog(){
$f[]="<root>";
$f[]="\t<facility>Mail</facility>";
$f[]="\t<logLevel>Info</logLevel>";
$f[]="</root>";
$unix=new unix();
$nohup=$unix->find_program("nohup");
$tmpf=$unix->FILE_TEMP();
@file_put_contents($tmpf, @implode("\n", $f));
shell_exec("$nohup /opt/kaspersky/klms/bin/klms-control --set-settings EventLogger -n -f $tmpf 2>&1 &");
	
}

function build_restart(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$pid=@file_get_contents("$pidfile");
	if($unix->process_exists($pid,basename(__FILE__))){system_admin_events("Already executed PID $pid",__FUNCTION__,__FILE__,__LINE__,"klms");die();}
	@file_put_contents($pidfile, getmypid());	
	buildConf();
	InfoToSyslog();
	watchdog();
	shell_exec("/etc/init.d/klms restart");
}


function ChecksPermissions(){
	
	if(!is_dir("/var/klms/tmp")){@mkdir("/var/klms/tmp",0755,true);}
	@chown("/var/klms/tmp", "kluser");
	@chgrp("/var/klms/tmp", "klusers");
	@mkdir("/tmp/klmstmp",0755,true);
	@chown("/tmp/klmstmp", "kluser");
	@chgrp("/tmp/klmstmp", "klusers");	
	
}

function buildConf(){
$f[]="[global]";
$f[]="# log file path or \"syslog\" for syslog output";
$f[]="log=syslog";
$f[]="";
$f[]="# log verbosity level: critical | warning | info | debug";
$f[]="log-verbosity=info";
$f[]="";
$f[]="# scanner socket to connect to";
$f[]="# use milter syntax: {unix|local}:/path/to/sock or {inet:port}@{ip}";
$f[]="scanner=inet:5555@127.0.0.1";
$f[]="";
$f[]="# default action for the case when product is not available: tempfail | pass";
$f[]="fallback-action=tempfail";
$f[]="";
$f[]="# socket timeout in seconds (0 - no timeout)";
$f[]="timeout=10";
$f[]="";
$f[]="# path to working directory";
$f[]="workdir=/var/klms/tmp";
$f[]="";
$f[]="# path to sendmail utility";
$f[]="sendmail-path=/usr/sbin/sendmail";
$f[]="";
$f[]="# generate special header for avoiding double mail checking: true | false";
$f[]="# used in milter, exim dlfunc, cgpro integrations";
$f[]="header-guard=false";
$f[]="";
$f[]="[milter]";
$f[]="# socket to listen on (milter syntax)";
$f[]="#milter_socket=unix:/var/run/klms/klms-milter.sock";
$f[]="socket=inet:6672@127.0.0.1";
$f[]="";
$f[]="# path to pid file";
$f[]="pid-file=/var/run/klms/klms-milter.pid";
$f[]="";
$f[]="[smtp_proxy]";
$f[]="# socket to listen on (milter syntax)";
$f[]="socket-in=inet:10025@127.0.0.1";
$f[]="";
$f[]="# socket to send to (milter syntax)";
$f[]="socket-out=inet:10026@127.0.0.1";
$f[]="";
$f[]="# max number of concurrent threads";
$f[]="threads=10";
$f[]="";
$f[]="# path to pid file";
$f[]="pid-file=/var/run/klms/klms-smtp_proxy.pid";
$f[]="";
$f[]="# filter integration mode: prequeue | afterqueue";
$f[]="integration=prequeue";
@file_put_contents("/etc/opt/kaspersky/klms/klms_filters.conf", @implode("\n", $f));	
echo "Starting......: klms8 klms_filters.conf done\n";	
ChecksPermissions();
ldap_cnx();
}


function watchdog(){
	$unix=new unix();
	$monit=$unix->find_program("monit");
	$chmod=$unix->find_program("chmod");
	if(!is_file($monit)){return;}
	$sock=new sockets();
	$config=unserialize(base64_decode($sock->GET_INFO("klms8Watchdog")));
	
	if(!isset($config["SystemWatchMemoryUsage"])){$config["SystemWatchMemoryUsage"]=350;}
	if(!isset($config["SystemWatchCPUSystem"])){$config["SystemWatchCPUSystem"]=80;}
	
	if(!isset($config["EnableWatchCPUsage"])){$config["EnableWatchCPUsage"]=1;}
	if(!isset($config["EnableWatchdog"])){$config["EnableWatchdog"]=1;}
	if(!isset($config["EnableWatchMemoryUsage"])){$config["EnableWatchMemoryUsage"]=1;}
	
	
	$SystemWatchMemoryUsage=$config["SystemWatchMemoryUsage"];
	$EnableWatchCPUsage=$config["EnableWatchCPUsage"];
	$EnableWatchMemoryUsage=$config["EnableWatchMemoryUsage"];
	$EnableWatchdog=$config["EnableWatchdog"];
	$SystemWatchCPUSystem=$config["SystemWatchCPUSystem"];
	if(!is_numeric($SystemWatchMemoryUsage)){$SystemWatchMemoryUsage=350;}
	if(!is_numeric($SystemWatchCPUUser)){$SystemWatchCPUUser=80;}
	if(!is_numeric($SystemWatchCPUSystem)){$SystemWatchCPUSystem=80;}
	if(!is_numeric($EnableWatchdog)){$EnableWatchdog=1;}
	if(!is_numeric($EnableWatchMemoryUsage)){$EnableWatchMemoryUsage=1;}
	if(!is_numeric($EnableWatchCPUsage)){$EnableWatchCPUsage=1;}
	$reloadmonit=false;
	$monit_file="/etc/monit/conf.d/klms8.monitrc";
	
	
	if($EnableWatchdog==0){
		echo "Starting......: klms8 Monit is not enabled ($EnableWatchdog)\n";
		if(is_file($monit_file)){
			@unlink($monit_file);
			@unlink("/etc/monit/conf.d/klms8db.monitrc");
			@unlink("/usr/sbin/klms8-monit-start");
			@unlink("/usr/sbin/klms8-monit-stop");
			@unlink("/usr/sbin/klms8db-monit-start");
			@unlink("/usr/sbin/klms8db-monit-stop");			
			$reloadmonit=true;
		}
	}
	
	if($EnableWatchdog==1){
		$pidfile="/var/run/klms/klms.pid";
		echo "Starting......: klms8 Monit is enabled check pid `$pidfile`\n";
		$reloadmonit=true;
		$f[]="check process klms8";
   		$f[]="with pidfile $pidfile";
   		$f[]="start program = \"/usr/sbin/klms8-monit-start\"";
   		$f[]="stop program =  \"/usr/sbin/klms8-monit-stop\"";
   		if($EnableWatchMemoryUsage==1){
  			$f[]="if totalmem > $SystemWatchMemoryUsage MB for 5 cycles then alert";
   		}
   		if($EnableWatchCPUsage==1){
   			$f[]="if cpu >$SystemWatchCPUSystem% for 5 cycles then alert";
   		}
	   $f[]="if 5 restarts within 5 cycles then timeout";
	   
	   @file_put_contents($monit_file, @implode("\n", $f));
	   
	   // ---------------------------------------------------------------------------
	   
	   $f=array();
	   $monit_file="/etc/monit/conf.d/klms8db.monitrc";
	   $f[]="#!/bin/sh";
	   $f[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin";
	   $f[]=$unix->LOCATE_PHP5_BIN()." ".__FILE__." --watchdog-klms8 start";
	   $f[]="exit 0\n";
 	   @file_put_contents("/usr/sbin/klms8-monit-start", @implode("\n", $f));
 	   shell_exec("$chmod 777 /usr/sbin/klms8-monit-start");
	   $f=array();
	   $f[]="#!/bin/sh";
	   $f[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin";
	   $f[]=$unix->LOCATE_PHP5_BIN()." ".__FILE__." --watchdog-klms8 stop";
	   $f[]="exit 0\n";
 	   @file_put_contents("/usr/sbin/klms8-monit-stop", @implode("\n", $f));
 	   shell_exec("$chmod 777 /usr/sbin/klms8-monit-stop");	  

 	    $f=array();
 	    $pidfile="/var/opt/kaspersky/klms/postgresql/postmaster.pid";
		$f[]="check process klms8db";
   		$f[]="with pidfile $pidfile";
   		$f[]="start program = \"/usr/sbin/klms8db-monit-start\"";
   		$f[]="stop program =  \"/usr/sbin/klms8db-monit-stop\"";
   		if($EnableWatchMemoryUsage==1){
  			$f[]="if totalmem > $SystemWatchMemoryUsage MB for 5 cycles then alert";
   		}
   		if($EnableWatchCPUsage==1){
   			$f[]="if cpu >$SystemWatchCPUSystem% for 5 cycles then alert";
   		}
	   $f[]="if 5 restarts within 5 cycles then timeout";
	   
	   @file_put_contents($monit_file, @implode("\n", $f));
	   
	   
	   
	 
	   $f=array();
	   $f[]="#!/bin/sh";
	   $f[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin";
	   $f[]=$unix->LOCATE_PHP5_BIN()." ".__FILE__." --watchdog-klms8db start";
	   $f[]="exit 0\n";
 	   @file_put_contents("/usr/sbin/klms8db-monit-start", @implode("\n", $f));
 	   shell_exec("$chmod 777 /usr/sbin/klms8db-monit-start");
	   $f=array();
	   $f[]="#!/bin/sh";
	   $f[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin";
	   $f[]=$unix->LOCATE_PHP5_BIN()." ".__FILE__." --watchdog-klms8db stop";
	   $f[]="exit 0\n";
 	   @file_put_contents("/usr/sbin/klms8db-monit-stop", @implode("\n", $f));
 	   shell_exec("$chmod 777 /usr/sbin/klms8db-monit-stop");	   	   
	}
	
	if($reloadmonit){$unix->THREAD_COMMAND_SET("/usr/share/artica-postfix/bin/artica-install --monit-check");}
}

function watchdog_klms8($action){
	exec("/etc/init.d/artica-postfix $action klms  2>&1",$results);
	system_admin_events("Service action $action on Kaspersky Mail security required\n".@implode("\n", $results), __FUNCTION__, __FILE__, __LINE__, "klms8");
	$unix=new unix();
	$unix->send_email_events("Service action $action on Kaspersky Mail security required", @implode("\n", $results), "klms8");
	
}
function watchdog_klms8db($action){
	exec("/etc/init.d/artica-postfix $action klmsdb 2>&1",$results);
	system_admin_events("Service action $action on Kaspersky Mail security Database required\n".@implode("\n", $results), __FUNCTION__, __FILE__, __LINE__, "klms8");
	$unix=new unix();
	$unix->send_email_events("Service action $action on Kaspersky Mail security Database required", @implode("\n", $results), "klms8");	
}


function _test_setup(){
	if(!is_file("/var/opt/kaspersky/klms/postgresql/postgresql.conf")){return false;}
	if(!is_file("/var/opt/kaspersky/klms/postgresql/PG_VERSION")){return false;}
	if(!is_file("/var/opt/kaspersky/klms/postgresql/pg_ident.conf")){return false;}	
	echo "Starting......: Kaspersky Mail security Suite: postgresql.conf,PG_VERSION,pg_ident.conf OK\n";
	
	$dirs[]="base";
	$dirs[]="global";
	
	while (list ($key, $directory) = each ($dirs) ){
		if(!is_dir("/var/opt/kaspersky/klms/postgresql/$directory")){return false;}
		echo "Starting......: Kaspersky Mail security Suite: dir:$directory OK\n";
	}
	return true;
	
}


function setup(){
	
	
	
	if(!_test_setup()){
		@unlink("/var/opt/kaspersky/klms/installer.dat");
		shell_exec("/bin/rm -rf /var/opt/kaspersky/klms/postgresql >/dev/null 2>&1");
		@mkdir("/var/opt/kaspersky/klms/postgresql");
		@chown("/var/opt/kaspersky/klms/postgresql", "kluser");
		@chgrp("/var/opt/kaspersky/klms/postgresql", "klusers");		
	}
	
	if(file_exists("/var/opt/kaspersky/klms/installer.dat")){
		echo "Starting......: Kaspersky Mail security Suite install already done...\n";
		return;
	}	
	
	$unix=new unix();
	$local_gen=$unix->find_program("locale-gen");
	echo "Starting......: Kaspersky Mail security Suite generating en_US.UTF-8\n";
	shell_exec("$local_gen en_US.UTF-8");
	
	
	echo "Starting......: Kaspersky Mail security Suite starting installation..\n";
	$chmod=$unix->find_program("chmod");
	$chown=$unix->find_program("chown");
	$su=$unix->find_program("su");
	$cp=$unix->find_program("cp");
	@mkdir("/var/opt/kaspersky/klms/postgresql",0755,true);
	shell_exec("$chown kluser:klusers /var/opt/kaspersky/klms/postgresql");
	echo "Starting......: Kaspersky Mail security Suite creating database....\n";
	if(!is_file("/var/opt/kaspersky/klms/postgresql/postgresql.conf")){@unlink("/var/opt/kaspersky/klms/postgresql/postgresql.conf");}
	$cmd="su -m -l kluser -c \"/opt/kaspersky/klms/libexec/postgresql/initdb -L /opt/kaspersky/klms/share/postgresql --pgdata=/var/opt/kaspersky/klms/postgresql --encoding=utf-8 --locale=C\"";
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	shell_exec($cmd);
	$cmd="$cp -fp /opt/kaspersky/klms/share/postgresql.conf.skel /var/opt/kaspersky/klms/postgresql/postgresql.conf";
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	shell_exec($cmd);
	echo "Starting......: Kaspersky Mail security Suite starting Database service...\n";	
	shell_exec("/etc/init.d/klmsdb start");
	
	$f[]="configurator";
	$f[]="rule_storage";
	$f[]="backup";
	$f[]="product_status";
	$f[]="notifier";
	$f[]="statistics";
	$f[]="personal_settings";
	
	while (list ($index, $table) = each ($f) ){
		echo "Starting......: Kaspersky Mail security Suite creating table \"$table\"\n";
		$cmd="su -m -l kluser -c \"/opt/kaspersky/klms/libexec/postgresql/createdb -h /var/run/klms -O kluser -E UTF8 $table\"";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		shell_exec($cmd);
	}
	echo "Starting......: Kaspersky Mail security Suite creating default password \"$table\"\n";
	@copy("/usr/share/artica-postfix/bin/install/klms.db.password","/var/opt/kaspersky/klms/db/password");
	echo "Starting......: Kaspersky Mail security Suite fixing settings\n";
	$t=exec("/opt/kaspersky/klms/libexec/generate_uuid");
	if(preg_match("#.*?:(.+)#", $t,$re)){$generate_uuid=$re[1];}
	echo "Starting......: Kaspersky Mail security Suite identifier:$generate_uuid\n";
	

	$file[]="INSTALL_DATE=".time();
	$file[]="EULA_AGREED=yes";
	$file[]="START_MILTER=1";
	$file[]="installation_id=$generate_uuid";
	$file[]="KSN_EULA_AGREED=yes";
	$file[]="POSTFIX_INTEGRATION_TYPE=milter";
	$file[]="POSTGRESQL_INSTALLED=YES";
	@file_put_contents("/var/opt/kaspersky/klms/installer.dat", @implode("\n", $file));
	echo "Starting......: Kaspersky Mail security Suite creating watchdog...\n";
	watchdog();
	echo "Starting......: Kaspersky Mail security Suite installation done...\n";
	buildConf();
	
}
function resetpwd(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		echo "Kaspersky Mail security Suite $oldpid already exists in memory\n";
		return;
	}
	
	
	if(!is_file("/usr/share/artica-postfix/bin/install/klms.db.password")){
		echo "Kaspersky Mail security Suite `klms.db.password` no such file\n";
		return;
	}
	echo "Starting......: Kaspersky Mail security Suite remove ..klms/db/password\n";
	@unlink("/var/opt/kaspersky/klms/db/password");
	echo "Starting......: Kaspersky Mail security Suite set default password\n";
	@copy("/usr/share/artica-postfix/bin/install/klms.db.password","/var/opt/kaspersky/klms/db/password");
	echo "Starting......: Kaspersky Mail security Suite set permissions\n";
	@chown("kluser","/var/opt/kaspersky/klms/db/password");
	@chgrp("klusers","/var/opt/kaspersky/klms/db/password");
	@chmod("/var/opt/kaspersky/klms/db/password",0644);
	echo "Starting......: Kaspersky Mail security Suite restart service\n";
	shell_exec("/etc/init.d/klms restart");
	echo "Starting......: Kaspersky Mail security Suite done...\n";
	ldap_cnx();
}
function ldap_cnx(){
	$users=new usersMenus();
	if(!$users->KLMS_INSTALLED){return;}
	$ldap=new clladp();
	$f[]="<root>";
	$f[]="\t<integrationType>LDAPGeneric</integrationType>";
	$f[]="\t<externalEncoding>utf-8</externalEncoding>";
	$f[]="\t<processPool>";
	$f[]="\t\t<processNumber>1</processNumber>";
	$f[]="\t\t<maxAttemptToReadCommand>5</maxAttemptToReadCommand>";
	$f[]="\t\t<communicationIoTimeoutInMilliseconds>5000</communicationIoTimeoutInMilliseconds>";
	$f[]="\t</processPool>";
	$f[]="\t<cache>";
	$f[]="\t\t<sizeInBytes>536870912</sizeInBytes>";
	$f[]="\t\t<freshTimeInSeconds>600</freshTimeInSeconds>";
	$f[]="\t</cache>";
	$f[]="\t<LDAPGeneric>";
	$f[]="\t\t<host>$ldap->ldap_host</host>";
	$f[]="\t\t<port>$ldap->ldap_port</port>";
	$f[]="\t\t<bindDn>cn=$ldap->ldap_admin,$ldap->suffix</bindDn>";
	$f[]="\t\t<password>$ldap->ldap_password</password>";
	$f[]="\t\t<searchBase>$ldap->suffix</searchBase>";
	$f[]="\t\t<filters>";
	$f[]="\t\t\t<user>(mail=%EMAIL%)</user>";
	$f[]="\t\t\t<groupList>";
	$f[]="\t\t\t\t<![CDATA[(&(member=%LOGIN%)(objectClass=posixGroup))]]>";
	$f[]="\t\t\t</groupList>";
	$f[]="\t\t\t<search>";
	$f[]="\t\t\t\t<![CDATA[(&(|(cn=*%STRING%*)(mail=*%STRING%*))(|(mail=*)(objectClass=posixGroup))):cn,mail]]>";
	$f[]="\t\t\t</search>";
	$f[]="\t\t\t<login>(uid=%LOGIN%)</login>";
	$f[]="\t\t\t<useNestedGroups>0</useNestedGroups>";
	$f[]="\t\t</filters>";
	$f[]="\t</LDAPGeneric>";
	$f[]="\t<AD>";
	$f[]="\t\t<host></host>";
	$f[]="\t\t<port>389</port>";
	$f[]="\t\t<bindDn></bindDn>";
	$f[]="\t\t<password></password>";
	$f[]="\t\t<searchBase></searchBase>";
	$f[]="\t\t<filters>";
	$f[]="\t\t\t<user>(|(mail=%EMAIL%)(proxyAddresses=smtp:%EMAIL%)):memberOf</user>";
	$f[]="\t\t\t<groupList></groupList>";
	$f[]="\t\t\t<search>";
	$f[]="\t\t\t\t<![CDATA[(&(|(cn=*%STRING%*)(mail=*%STRING%*)(proxyaddresses=smtp:*%STRING%*))(|(mail=*)(proxyaddresses=smtp:*)(objectClass=group))):cn,mail]]>";
	$f[]="\t\t\t</search>";
	$f[]="\t\t\t<login>(|(mail=%LOGIN%)(proxyaddresses=smtp:%LOGIN%))</login>";
	$f[]="\t\t\t<useNestedGroups>0</useNestedGroups>";
	$f[]="\t\t</filters>";
	$f[]="\t</AD>";
	$f[]="</root>";
	$f[]="";	
	$unix=new unix();
	$filetemp=$unix->FILE_TEMP();
	@file_put_contents($filetemp, @implode("\n", $f));
	if($GLOBALS["VERBOSE"]){echo "/opt/kaspersky/klms/bin/klms-control --set-settings 1 -f $filetemp\n";}
	shell_exec("/opt/kaspersky/klms/bin/klms-control --set-settings 1 -f $filetemp");
	
}

