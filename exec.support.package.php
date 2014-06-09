<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["NOPID"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;
$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');

if($argv[1]=="--step1"){support_step1();die();}
if($argv[1]=="--step2"){support_step2();die();}
if($argv[1]=="--step3"){support_step3();die();}

build();


function build(){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();
	$sock=new sockets();
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){die();}
	$php=$unix->LOCATE_PHP5_BIN();
	@file_put_contents($pidfile, getmypid());
	progress("{get_system_informations}",30);
	support_step1();
	progress("{APP_UFDBGUARD}",40);
	$EnableUfdbGuard=intval($sock->EnableUfdbGuard());
	
	if($EnableUfdbGuard==1){
		$ufdbguardd=$unix->find_program("ufdbguardd");
		if(is_file($ufdbguardd)){
			shell_exec("$php /usr/share/artica-postfix/exec.squidguard.php --build --force --verbose >/usr/share/artica-postfix/ressources/support/build-ufdbguard.log 2>&1");
		}
	}
	
	progress("{get_all_logs}",50);
	support_step2();
	progress("{get_all_logs}",70);
	export_tables();
	progress("{compressing_package}",90);
	support_step3();
	progress("{success}",100);
}

function progress($title,$perc){
	$array=array($title,$perc);
	@file_put_contents("/usr/share/artica-postfix/ressources/support/support.progress",serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/support/support.progress", 0755);
}

function support_step1(){
	$unix=new unix();
	$ps=$unix->find_program("ps");
	$df=$unix->find_program("df");
	$du=$unix->find_program("du");
	$files[]="/etc/hostname";
	$files[]="/etc/resolv.conf";
	$files[]="/usr/share/artica-postfix/ressources/settings.inc";
	$files[]="/usr/share/artica-postfix/ressources/logs/global.status.ini";
	$files[]="/usr/share/artica-postfix/ressources/logs/global.versions.conf";
	$files[]="/var/log/lighttpd/squidguard-lighttpd-error.log";
	$files[]="/var/log/lighttpd/squidguard-lighttpd.start";
	$files[]="/etc/init.d/tproxy";
	$files[]="/etc/init.d/artica-ifup";
	$files[]="/var/log/net-start.log";

	if(is_dir("/usr/share/artica-postfix/ressources/support")){
		shell_exec("/bin/rm -rf /usr/share/artica-postfix/ressources/support");
	}

	@mkdir("/usr/share/artica-postfix/ressources/support",0755,true);
	while (list ($a, $b) = each ($files) ){
		$destfile=basename($b);
		@copy($b, "/usr/share/artica-postfix/ressources/support/$destfile");
	}

	shell_exec("$ps aux >/usr/share/artica-postfix/ressources/support/ps.txt 2>&1");
	shell_exec("$df -h >/usr/share/artica-postfix/ressources/support/dfh.txt 2>&1");

	progress("{scanning} /var/log {partition}",35);
	shell_exec("$du -h --max-dep=1 >/usr/share/artica-postfix/ressources/support/var-log-sizes.txt 2>&1");
	
	
	$report=$unix->NETWORK_REPORT();
	@file_put_contents("/usr/share/artica-postfix/ressources/support/NETWORK_REPORT.txt", $report);
}

function export_tables(){
	$q=new mysql();
	$unix=new unix();
	
	$tmppath=$unix->TEMP_DIR();
	$sql="SELECT *  FROM `squid_admin_mysql` ORDER BY zDate DESC";
	$results = $q->QUERY_SQL($sql,"artica_events");
	while ($ligne = mysql_fetch_assoc($results)) {
		$f[]="{$ligne["zDate"]}:{$ligne["filename"]} {function}:{$ligne["function"]}, {line}:{$ligne["line"]}";
		$f[]="{$ligne["subject"]}";
		$f[]="{$ligne["content"]}";
		$f[]="************************************************************************************************************";
		$f[]="";
	}
	progress("{get_all_logs}",75);
	@file_put_contents("$tmppath/squid_admin_mysql.log", @implode("\n", $f));
	$unix->compress("$tmppath/squid_admin_mysql.log", "/usr/share/artica-postfix/ressources/support/squid_admin_mysql.log.gz");
	@unlink("$tmppath/squid_admin_mysql.log");
	$f=array();
	progress("{get_all_logs}",80);
	$sql="SELECT *  FROM `artica_update_task` ORDER BY zDate DESC";
	$results = $q->QUERY_SQL($sql,"artica_events");
	while ($ligne = mysql_fetch_assoc($results)) {
		$f[]="{$ligne["zDate"]}:{$ligne["filename"]} {function}:{$ligne["function"]}, {line}:{$ligne["line"]}";
		$f[]="{$ligne["subject"]}";
		$f[]="{$ligne["content"]}";
		$f[]="************************************************************************************************************";
		$f[]="";
	}
	
	@file_put_contents("$tmppath/artica_update_task.log", @implode("\n", $f));
	$unix->compress("$tmppath/artica_update_task.log", "/usr/share/artica-postfix/ressources/support/artica_update_task.log.gz");
	@unlink("$tmppath/artica_update_task.log");
	progress("{get_all_logs}",85);
	
	
	
}


function support_step2(){

	$files[]="/var/log/squid/cache.log";
	$files[]="/var/log/syslog";
	$files[]="/var/log/messages";
	$files[]="/var/log/auth.log";
	$files[]="/var/log/squid/access.log";
	$files[]="/var/log/squid/external-acl.log";
	$files[]="/var/log/squid/logfile_daemon.debug";
	$files[]="/var/log/php.log";
	$files[]="/var/log/mail.log";
	$files[]="/var/log/squid/ufdbguardd.log";
	$files[]="/var/log/samba/log.winbindd";
	$files[]="/etc/samba/smb.conf";
	$files[]="/var/log/samba/log.nmbd";
	$files[]="/var/log/samba/log.smbd";
	$files[]="/var/run/mysqld/mysqld.err";
	$files[]="/etc/init.d/artica-ifup";
	
	
	

	$unix=new unix();
	$cp=$unix->find_program("cp");

	$dmesg=$unix->find_program("dmesg");
	@mkdir("/usr/share/artica-postfix/ressources/support",0755,true);
	shell_exec("$dmesg >/usr/share/artica-postfix/ressources/support/dmesg.txt");

	
	progress("{get_all_logs}",45);
	if(is_dir("/etc/squid3")){
		@mkdir("/usr/share/artica-postfix/ressources/support/etc-squid3",0755,true);
		$cmd="/bin/cp -rf /etc/squid3/* /usr/share/artica-postfix/ressources/support/etc-squid3/";
		shell_exec("$cmd");
	}
	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	
	progress("{get_all_logs}",46);
	if(is_file("/tmp/squid.conf")){
		if(is_file($squidbin)){
			shell_exec("$squidbin -f /tmp/squid.conf -k parse >/etc-squid3/tmp.squid.conf.log 2>&1");
		}
		@copy("/tmp/squid.conf", "/usr/share/artica-postfix/ressources/support/etc-squid3/tmp.squid.conf");
	}
	
	progress("{get_all_logs}",47);
	if(is_dir("/etc/postfix")){
		@mkdir("/usr/share/artica-postfix/ressources/support/etc-postfix",0755,true);
		$cmd="/bin/cp -rf /etc/postfix/* /usr/share/artica-postfix/ressources/support/etc-postfix/";
		shell_exec("$cmd");
	}

	progress("{get_all_logs}",48);
	while (list ($a, $b) = each ($files) ){
		if(is_file($b)){
			$destfile=basename("$b.gz");
			$unix->compress($b, "/usr/share/artica-postfix/ressources/support/$destfile");
			
		}
	}

	progress("{get_all_logs}",49);
	$lshw=$unix->find_program("lshw");
	exec("$lshw -class network 2>&1",$results);
	
	progress("{get_all_logs}",50);
	$ifconfig=$unix->find_program("ifconfig");
	exec("$ifconfig -a 2>&1",$results);
	$results[]="\n\t***************\n";
	$ip=$unix->find_program("ip");
	exec("$ip link show 2>&1",$results);
	$results[]="\n\t***************\n";
	exec("$ip route 2>&1",$results);
	$results[]="\n\t***************\n";

	$f=explode("\n",@file_get_contents("/etc/iproute2/rt_tables"));
	while (list ($a, $line) = each ($f) ){
		if(!preg_match("#^([0-9]+)\s+(.+)#", $line,$re)){continue;}
		$table_num=$re[1];
		$tablename=$re[2];
		if($table_num==0){continue;}
		if($table_num>252){continue;}
		$results[]="\n\t***** Table route $table_num named $tablename *****\n";
		exec("$ip route show table $table_num 2>&1",$results);
		$results[]="\n\t***************\n";
	}

	progress("{get_all_logs}",51);
	$unix=new unix();
	$uname=$unix->find_program("uname");
	$results[]="$uname -a:";
	exec("$uname -a 2>&1",$results);
	$results[]="\n";
	$results[]="/bin/bash --version:";
	exec("/bin/bash --version 2>&1",$results);

	$results[]="\n";

	progress("{get_all_logs}",52);
	$gdb=$unix->find_program("gdb");
	if(is_file($gdb)){
		$results[]="$gdb --version:";
		exec("$gdb --version 2>&1",$results);
	}else{
		$results[]="gdb no such binary....";
	}
	$results[]="\n";
	$smbd=$unix->find_program("smbd");
	if(is_file($smbd)){
		$results[]="$smbd -V:";
		exec("$smbd -V 2>&1",$results);
	}else{
		$results[]="smbd no such binary....";
	}

	$results[]="\n";
	
	progress("{get_all_logs}",53);
	if(is_file($squidbin)){
		$results[]="$squidbin -v:";
		exec("$squidbin -v 2>&1",$results);
		squid_watchdog_events("Reconfiguring Proxy parameters...");
		exec("/etc/init.d/squid reload --script=".basename(__FILE__)." 2>&1",$results);
		squid_admin_mysql(2, "Framework executed to reconfigure squid-cache", @implode("\n", $results));
	}else{
		$results[]="squid no such binary....";
	}
	$results[]="\n";
	
	progress("{get_all_logs}",54);
	if(is_file($squidbin)){
		$results[]="$squidbin -v:";
		exec("$squidbin -v 2>&1",$results);
		squid_watchdog_events("Reconfiguring Proxy parameters...");
		exec("/etc/init.d/squid reload --script=".basename(__FILE__)." 2>&1",$results);
		squid_admin_mysql(2, "Framework executed to reconfigure squid-cache", @implode("\n", $results));
		
		shell_exec("$squidbin -f /etc/squid3/squid.conf -k check -X >/usr/share/artica-postfix/ressources/support/squid-conf-check.txt");
		if(is_file("/tmp/squid.conf")){
			shell_exec("$squidbin -f /tmp/squid.conf -k check -X >/usr/share/artica-postfix/ressources/support/squid-temp-check.txt");
		}
		
		
	}else{
		$results[]="squid3 no such binary....";
	}
	
	progress("{get_all_logs}",55);
	$results[]="\n";
	$df=$unix->find_program("df");
	if(is_file($df)){
		$results[]="$df -h:";
		exec("$df -h 2>&1",$results);
	}else{
		$results[]="$df no such binary....";
	}
	
	progress("{get_all_logs}",56);
	@file_put_contents("/usr/share/artica-postfix/ressources/support/generated.versions.txt", @implode("\n", $results));
	
}

function support_step3(){
	$unix=new unix();
	
	$tar=$unix->find_program("tar");
	$filename="support.tar.gz";
	chdir("/usr/share/artica-postfix/ressources/support");
	$cmd="$tar -cvzf /usr/share/artica-postfix/ressources/support/$filename * 2>&1";
	exec($cmd,$results);
	@chmod("/usr/share/artica-postfix/ressources/support/$filename", 0755);
	
}

function squid_watchdog_events($text){
	$unix=new unix();
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefile=basename($trace[1]["file"]);$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];}}
	$unix->events($text,"/var/log/squid.watchdog.log",false,$sourcefunction,$sourceline);
}