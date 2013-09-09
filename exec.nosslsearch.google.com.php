<?php
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["WITHOUT_RESTART"]=false;
$GLOBALS["CMDLINES"]=implode(" ",$argv);
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--no-restart#",implode(" ",$argv))){$GLOBALS["WITHOUT_RESTART"]=true;}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.tasks.inc');
include_once(dirname(__FILE__).'/ressources/class.process.inc');
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");

if($argv[1]=="--dump"){dump();die();}
if($argv[1]=="--run"){run();exit;}
if($argv[1]=="--syslog"){checksyslog();exit;}


function run(){
	$sock=new sockets();
	$DisableGoogleSSL=$sock->GET_INFO("DisableGoogleSSL");
	if(!is_numeric($DisableGoogleSSL)){$DisableGoogleSSL=0;}
	if($DisableGoogleSSL==0){
		echo "Starting......: Squid : nosslsearch.google.com (disabled)\n";
		remove();
		return;
	}
	echo "Starting......: Squid : nosslsearch.google.com (enabled)\n";
	addDNSGOOGLE();
	
}

function GetWebsitesList(){
	$q=new mysql_squid_builder();
	$arrayDN=$q->GetFamilySitestt(null,true);	
	while (list ($table, $fff) = each ($arrayDN) ){
		if(preg_match("#\.(gov|gouv|gor|org|net|web|ac)\.#", "google.$table")){continue;}
		$array[]="www.google.$table";
	}	
	
	return $array;
	
}

function addDNSGOOGLE(){
	$ipaddr=gethostbyname("nosslsearch.google.com");
	$ip=new IP();
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$OK=true;
	if(!$ip->isIPv4($ipaddr)){$OK=false;}
	if(!$OK){
		if($ip->isIPv6($ipaddr)){$OK=true;}
	}
	if(!$OK){echo "Starting......: Squid : failed, nosslsearch.google.com `$ipaddr` not an IP address...!!!\n";return;}	
	$q=new mysql();
	
	$ligne=@mysql_fetch_array($this->QUERY_SQL("SELECT ipaddr FROM net_hosts WHERE `hostname` = 'www.google.com'","artica_backup"));
	
	
	$entry=$ligne["ipaddr"];
	if($entry==$ipaddr){
		echo "Starting......: Squid : nosslsearch.google.com no changes...\n";
		reload_pdns();
		return; 
	}
	if($entry<>null){
	echo "Starting......: Squid : nosslsearch.google.com [$entry]...\n";
	}
	$array=GetWebsitesList();
	
	
	
	while (list ($table, $fff) = each ($array) ){
		$md5=md5("$ipaddr$fff");
		$f[]="('$md5','$ipaddr','$fff')";
		
	}
	if(count($f)>0){
		$q->QUERY_SQL("INSERT IGNORE INTO net_hosts (`zmd5`,`ipaddr`,`hostname`) VALUES ".@implode("\n", $f));
		echo "Starting......: Squid : adding ".count($f)." google servers [$ipaddr] from /etc/hosts\n";
		shell_exec("$php5 /usr/share/artica-postfix/exec.virtuals-ip.php --hosts");
		reload_pdns();
	}		
}

function dump(){
	$ipaddr=gethostbyname("nosslsearch.google.com");
	$ip=new IP();
	$OK=true;
	if(!$ip->isIPv4($ipaddr)){$OK=false;}
	if(!$OK){
		if($ip->isIPv6($ipaddr)){$OK=true;}
	}
	if(!$OK){echo "Failed nosslsearch.google.com `$ipaddr` not an IP address...!!!\n";return;}
	
	
	
	$array=GetWebsitesList();
	if(count($array)==0){
		echo "Failed!!! -> GetWebsitesList();\n";return;
	}
	
	while (list ($table, $fff) = each ($array) ){
		echo "$fff\t$ipaddr\n";
	}	

}

function reload_pdns(){
	$unix=new unix();
	$pdns_server=$unix->find_program("pdns_server");
	if(!is_file($pdns_server)){return;}
	$kill=$unix->find_program("kill");
	$pid_path="/var/run/pdns/pdns.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	if($unix->process_exists($master_pid)){
		echo "Starting......: Squid : reloading PowerDNS PID $master_pid\n";
		shell_exec("$kill -HUP $master_pid >/dev/null 2>&1");
	}
	
	$pid_path="/var/run/pdns/pdns_recursor.pid";
	$master_pid=trim(@file_get_contents($pid_path));	
	if($unix->process_exists($master_pid)){
		echo "Starting......: Squid : reloading PowerDNS Recursor PID $master_pid\n";
		shell_exec("$kill -HUP $master_pid >/dev/null 2>&1");
	}	
	
	
}

function remove(){
	$unix=new unix();
	$entry=$unix->get_EtcHostsByName("www.google.com");
	if($entry==null){return;}
	$array=GetWebsitesList();
	while (list ($table, $fff) = each ($array) ){
		$unix->del_EtcHostsByName($fff);
		$c++;
	}	
	if($c>0){
		echo "Starting......: Squid : removing $c google servers from /etc/hosts\n";
		reload_pdns();
	}
}

function checksyslog(){
	$unix=new unix();
	$syslogpath=$unix->LOCATE_SYSLOG_PATH();
	$size=@filesize($syslogpath);
	echo "Size:$size\n";
	if($size==0){
		$unix->RESTART_SYSLOG(true);
	}
}

