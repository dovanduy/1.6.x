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
	
	
	$ReplaceEntry=null;
	$sock=new sockets();
	$DisableGoogleSSL=intval($sock->GET_INFO("DisableGoogleSSL"));
	$EnableGoogleSafeSearch=$sock->GET_INFO("EnableGoogleSafeSearch");
	
	echo "Starting......: ".date("H:i:s")." Squid : EnableGoogleSafeSearch = '$EnableGoogleSafeSearch'\n";
	
	if(!is_numeric($EnableGoogleSafeSearch)){$EnableGoogleSafeSearch=1;}
	
	echo "Starting......: ".date("H:i:s")." Squid : DisableGoogleSSL = $DisableGoogleSSL\n";
	echo "Starting......: ".date("H:i:s")." Squid : EnableGoogleSafeSearch = $EnableGoogleSafeSearch\n";
	
	if($DisableGoogleSSL==0){
		if($EnableGoogleSafeSearch==0){
			echo "Starting......: ".date("H:i:s")." Squid : change google.com DNS (disabled)\n";
			remove();
			build_progress("{disabled}",100);
			return;
		}
		
	}
	
	if($DisableGoogleSSL==1){
		$ReplaceEntry="nosslsearch.google.com";
	}
	
	if($EnableGoogleSafeSearch==1){
		$ReplaceEntry="forcesafesearch.google.com";
	}
	
	if($ReplaceEntry==null){
		remove();
		build_progress("{disabled}",100);
		return;
	}
	
	echo "Starting......: ".date("H:i:s")." Squid : $ReplaceEntry (enabled)\n";
	build_progress("{enabled}",5);
	addDNSGOOGLE($ReplaceEntry);
	
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

function build_progress($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.google.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
	if(!isset($GLOBALS["PROGRESS"])){$GLOBALS["PROGRESS"]=false;}
	if($GLOBALS["PROGRESS"]){sleep(1);}

}




function addDNSGOOGLE($addrName="nosslsearch.google.com"){
	
	if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: $addrName -> ?\n";}
	$ipaddr=gethostbyname($addrName);
	$ip=new IP();
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$OK=true;
	if(!$ip->isIPv4($ipaddr)){$OK=false;}
	if(!$OK){
		if($ip->isIPv6($ipaddr)){$OK=true;}
	}
	if(!$OK){
		echo "Starting......: ".date("H:i:s")." Squid : failed, nosslsearch.google.com `$ipaddr` not an IP address...!!!\n";
		build_progress("$addrName {failed}",110);
		return;
	}	
	$q=new mysql();
	
	build_progress("$addrName {checking}",5);
	
	$results=$q->QUERY_SQL("SELECT ipaddr FROM net_hosts WHERE `hostname` = 'www.google.com'","artica_backup");
	if(mysql_num_rows($results)==1){
		while ($ligne = mysql_fetch_assoc($results)) {
			$entry=$ligne["ipaddr"];
		}
	}
	
	echo "Starting......: ".date("H:i:s")." Squid : Resolved $ipaddr in DB: $entry\n";
	if($entry==$ipaddr){
		echo "Starting......: ".date("H:i:s")." Squid : $addrName no changes...\n";
		if($GLOBALS["OUTPUT"]){
			build_progress("$addrName {no_changes}",50);
			sleep(3);
			build_progress("Patching host file",95);
			shell_exec("$php5 /usr/share/artica-postfix/exec.virtuals-ip.php --hosts");
			reload_pdns();
			sleep(5);
			build_progress("{success}",100);
			return;
		}
		
		reload_pdns();
		return; 
	}
	
	if($entry<>null){
		echo "Starting......: ".date("H:i:s")." Squid : $addrName [$entry]...\n";
	}
	build_progress("$addrName [$entry]",5);
	
	
	$array=GetWebsitesList();
	
	$max=count($array);
	$c=0;
	while (list ($table, $fff) = each ($array) ){
		$c++;
		$prc=$c/$max;
		$prc=$prc*100;
		if($prc>5){
			if($prc<90){
				build_progress("$fff [$ipaddr]",$prc);
			}
		}
		$md5=md5("$ipaddr$fff");
		$f[]="('$md5','$ipaddr','$fff')";
		
	}
	if(count($f)>0){
		$q->QUERY_SQL("DELETE FROM net_hosts WHERE `hostname` LIKE '%google\.%'" ,"artica_backup");
		$q->QUERY_SQL("INSERT IGNORE INTO net_hosts (`zmd5`,`ipaddr`,`hostname`) VALUES ".@implode(",\n", $f),"artica_backup");
		if(!$q->ok){
			build_progress("Table net_hosts failed",110);
			return;
		}
		
		build_progress("Patching host file",95);
		echo "Starting......: ".date("H:i:s")." Squid : adding ".count($f)." google servers [$ipaddr] from /etc/hosts\n";
		shell_exec("$php5 /usr/share/artica-postfix/exec.virtuals-ip.php --hosts");
		build_progress("Reloading proxy service",95);
		shell_exec("$php5 /etc/init.d/squid reload --script=".basename(__FILE__));
		shell_exec("/etc/init.d/dnsmasq restart");
		reload_pdns();
		sleep(5);
		build_progress("{success}",100);
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
		echo "Starting......: ".date("H:i:s")." Squid : reloading PowerDNS PID $master_pid\n";
		shell_exec("$kill -HUP $master_pid >/dev/null 2>&1");
	}
	
	$pid_path="/var/run/pdns/pdns_recursor.pid";
	$master_pid=trim(@file_get_contents($pid_path));	
	if($unix->process_exists($master_pid)){
		echo "Starting......: ".date("H:i:s")." Squid : reloading PowerDNS Recursor PID $master_pid\n";
		shell_exec("$kill -HUP $master_pid >/dev/null 2>&1");
	}	
	
	
}

function remove(){
	$unix=new unix();
	$newf=array();
	$add=0;
	$f=explode("\n",@file_get_contents("/etc/hosts"));
	while (list ($index, $line) = each ($f) ){
		if(preg_match("#google\.#", $line)){$add++;continue;}
		$newf[]=$line;
		
	}
	if($add>0){
		$q=new mysql();
		$q->QUERY_SQL("DELETE FROM net_hosts WHERE `hostname` LIKE '%google%'" ,"artica_backup");
		@file_put_contents("/etc/hosts", @implode("\n", $newf));
		echo "Starting......: ".date("H:i:s")." Squid : removing $add google servers from /etc/hosts\n";
		shell_exec("/etc/init.d/dnsmasq restart");
		reload_pdns();
	}
	
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM net_hosts WHERE `hostname` LIKE '%google%'" ,"artica_backup");
	
}

function remove_entry($val){

	
	
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

