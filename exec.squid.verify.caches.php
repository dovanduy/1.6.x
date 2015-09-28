<?php
$GLOBALS["ARGVS"]=implode(" ",$argv);
$GLOBALS["OUTPUT"]=false;
$GLOBALS["BYWIZARD"]=false;
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.caches.inc');
if(preg_match("#--bywizard#",implode(" ",$argv))){$GLOBALS["BYWIZARD"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
	if(preg_match("#--report#",implode(" ",$argv))){$GLOBALS["REPORT"]=true;}
	if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
	
}

if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}



verifycaches();

function build_progress($text,$pourc){
	if($GLOBALS["BYWIZARD"]){if($pourc<90){$pourc=90;}if($pourc>90){$pourc=90;} build_progress_wizard($text,$pourc); }
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/squid.caches.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
function build_progress_wizard($text,$pourc){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/squid.newcache.center.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}

function verifycaches(){
	$logFile="/usr/share/artica-postfix/ressources/logs/web/rebuild-cache.txt";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	$unix=new unix();
	$sock=new sockets();
	if($unix->process_exists($pid,basename(__FILE__))){
		ouputz("Already process exists $pid, aborting", __LINE__);
		build_progress("Already process exists $pid, aborting",110);
		die();
	}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);
	@unlink($logFile);
	build_progress("Listing caches....",10);
	$cache=new SquidCacheCenter();
	$mv=$unix->find_program("mv");
	$rm=$unix->find_program("rm");
	$php5=$unix->LOCATE_PHP5_BIN();
	$caches=$cache->build();
	
	$http_port=rand(55000, 65000);
	$f=array();
	$f[]="cache_effective_user squid";
	$f[]="pid_filename	/var/run/squid-temp.pid";
	$f[]="http_port 127.0.0.1:$http_port";
	$f[]="$caches";
	$f[]="";
	
	$squidconf="/etc/squid3/squid.caches.conf";
	@file_put_contents($squidconf, @implode("\n", $f));
	build_progress("Generating caches {please_wait}",25);
	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	$su=$unix->find_program("su");
	$results=array();
	
	$cmd="$su -c \"$squidbin -f $squidconf -z\" squid";
	ouputz("Building new caches $cmd",__LINE__);
	system($cmd);
	@unlink($squidconf);
	build_progress("{reconfigure}",50);
	
	system("$php5 /usr/share/artica-postfix/exec.squid.php --build --force");
	
	
	build_progress("{restarting}",60);
	system("/etc/init.d/squid restart");
	
	for($i=0;$i<30;$i++){
		$array=$unix->squid_get_cache_infos();
		if(count($array)>0){break;}
		build_progress("{waiting_proxy_status} $i/29",50);
		writelogs("Waiting 1s to squid be ready...",__FUNCTION__,__FILE__,__LINE__);
		sleep(1);
	}
	
	build_progress("{waiting_proxy_status} $i/29",60);
	system("$php5 /usr/share/artica-postfix/exec.squid.php --cache-infos --force");
	
	$q=new mysql();
	$sql="SELECT * FROM squid_caches_center WHERE remove=1";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		$cache_type=$ligne["cache_type"];
		$cache_size=$ligne["cache_size"];
		$cachename=$ligne["cachename"];
		$ID=$ligne["ID"];
		if($cache_type=="Cachenull"){continue;}
		if($cache_type=="tmpfs"){$ligne["cache_dir"]="/home/squid/cache/MemBooster$ID";}
		$Directory=$ligne["cache_dir"];
		build_progress("{remove} $Directory",80);
		if(is_dir($Directory)){system("$rm -rf $Directory");}
		$q->QUERY_SQL("DELETE FROM squid_caches_center WHERE ID='$ID'","artica_backup");
	}
	
	
	
	build_progress("{done}",100);
	
	
	
	
}
function squid_watchdog_events($text){
	$unix=new unix();
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefile=basename($trace[1]["file"]);$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];}}
	$unix->events($text,"/var/log/squid.watchdog.log",false,$sourcefunction,$sourceline);
}

function IfDirIsACache($directory){
	if(is_file("$directory/swap.state")){return true;}
	
	for ($i=0;$i<10;$i++){
		if(is_dir("$directory/0$i/0$i")){return true;}
	}
	
	return false;
}

function clean_old_caches(){

}



function ListCaches(){
	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	while (list ($num, $ligne) = each ($f) ){
		if(preg_match("#^cache_dir\s+(.+?)\s+(.+?)\s+#", $ligne,$re)){
			if($re[1]=="null"){continue;}
			
			$array[trim($re[2])]=$re[1];
		}
	}
	return $array;
}
function ouputz($text,$line){
	
	
	
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	echo "$text\n";
	$pid=@getmypid();
	$date=@date("H:i:s");

	$logFile="/usr/share/artica-postfix/ressources/logs/web/rebuild-cache.txt";
	if($GLOBALS["VERBOSE"]){echo "$text\n";}
	if(is_file($logFile)){
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
	}
	$f = fopen($logFile, 'a');
	fwrite($f, "$date [$pid][$line]: $text\n");
	fclose($f);
	chmod($logFile, 0777);
}


