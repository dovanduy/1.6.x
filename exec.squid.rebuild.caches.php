<?php
$GLOBALS["ARGVS"]=implode(" ",$argv);
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
	
	
}

if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if($argv[1]=="--reindex"){reindex_caches();die();}
if($argv[1]=="--default"){rebuild_default_cache();die();}


rebuildcaches();


function reindex_caches(){
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=@file_get_contents($pidfile);
	if($oldpid<100){$oldpid=null;}
	$unix=new unix();
	if($unix->process_exists($oldpid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid\n";}die();}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);		
	
	@unlink("/etc/artica-postfix/squid.lock");
	@file_put_contents("/etc/artica-postfix/squid.lock", time());
	
	$t=time();
	$array=ListCaches();
	$mv=$unix->find_program("mv");
	$rm=$unix->find_program("rm");
	$php5=$unix->LOCATE_PHP5_BIN();
	writelogs(count($array)." caches to re-index...",__FUNCTION__,__FILE__,__LINE__);
	if(count($array)==0){writelogs("Fatal error",__FUNCTION__,__FILE__,__LINE__);return;}
	
	$squidbin=$unix->find_program("squid");
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
	if(!is_file($squidbin)){
		writelogs("squid, no such binary file",__FUNCTION__,__FILE__,__LINE__);
		return;
	}

	writelogs("Stopping squid",__FUNCTION__,__FILE__,__LINE__);
	shell_exec("/etc/init.d/artica-postfix stop squid-cache");
	while (list ($cache_dir, $ligne) = each ($array) ){	
		if(is_file("$cache_dir/swap.state")){
			writelogs("Delete $cache_dir/swap.state",__FUNCTION__,__FILE__,__LINE__);
		}else{
			writelogs("Warning $cache_dir/swap.state no such file",__FUNCTION__,__FILE__,__LINE__);
		}
	}
	
	@unlink("/etc/artica-postfix/squid.lock");
	writelogs("starting squid",__FUNCTION__,__FILE__,__LINE__);
	shell_exec("/etc/init.d/artica-postfix start squid-cache");
	
	
	for($i=0;$i<30;$i++){
		$array=$unix->squid_get_cache_infos();
		if(count($array)>0){break;}
		writelogs("Waiting 1s to squid be ready...",__FUNCTION__,__FILE__,__LINE__);
		sleep(1);
	}
	
	
	shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --cache-infos --force");	
	
}
	
function rebuild_default_cache(){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=@file_get_contents($pidfile);
	if($oldpid<100){$oldpid=null;}
	$unix=new unix();
	$sock=new sockets();
	if($unix->process_exists($oldpid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid\n";}die();}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);		
	$t=time();
	
	$squid=new squidbee();
	shell_exec($unix->LOCATE_PHP5_BIN()." ".basename(__FILE__)."/exec.squid.php --build >/dev/null 2>&1");
	$cache_dir=$squid->CACHE_PATH;
	$mv=$unix->find_program("mv");
	$rm=$unix->find_program("rm");
	$chmod=$unix->find_program("chmod");
	$chown=$unix->find_program("chown");
	$php5=$unix->LOCATE_PHP5_BIN();
	writelogs("$cache_dir to delete...",__FUNCTION__,__FILE__,__LINE__);
	$t=time();
	@unlink("/etc/artica-postfix/squid.lock");
	@file_put_contents("/etc/artica-postfix/squid.lock", time());	
	
	$squidbin=$unix->find_program("squid");
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
	if(!is_file($squidbin)){
		writelogs("squid, no such binary file",__FUNCTION__,__FILE__,__LINE__);
		@unlink("/etc/artica-postfix/squid.lock");
		return;
	}

	writelogs("Stopping squid",__FUNCTION__,__FILE__,__LINE__);
	$sock->TOP_NOTIFY("Proxy is stopped to rebuild default cache...","info");
	shell_exec("/etc/init.d/artica-postfix stop squid-cache");	
	$cachesRename="$cache_dir-delete-$t";	
	exec("$mv $cache_dir $cachesRename 2>&1",$results);
	writelogs("re-create $cache_dir",__FUNCTION__,__FILE__,__LINE__);
	@mkdir($cache_dir,0755,true);
	@chown($cache_dir, "squid");
	@chgrp($cache_dir, "squid");	
	exec("$squidbin -z 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		writelogs("$ligne",__FUNCTION__,__FILE__,__LINE__);
		
	}	
	shell_exec("$chown -R squid:squid $cache_dir");
	shell_exec("$chown -R 0755 $cache_dir");
	
	@unlink("/etc/artica-postfix/squid.lock");
	writelogs("starting squid",__FUNCTION__,__FILE__,__LINE__);
	shell_exec("/etc/init.d/artica-postfix start squid-cache");
	$sock->TOP_NOTIFY("Proxy was restarted to rebuild default cache...","info");
	for($i=0;$i<60;$i++){
		$array=$unix->squid_get_cache_infos();
		if(count($array)>0){break;}
		writelogs("Waiting 1s to squid be ready...",__FUNCTION__,__FILE__,__LINE__);
		sleep(1);
	}
	
	
	shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --cache-infos --force");
	writelogs("Deleting  $cachesRename",__FUNCTION__,__FILE__,__LINE__);
	shell_exec("$rm -rf $cachesRename");
	
	$took=$unix->distanceOfTimeInWords($t,time());
	$sock->TOP_NOTIFY("Default Proxy cache was rebuilded took: $took","info");
		
}	


function rebuildcaches(){
	$logFile="/usr/share/artica-postfix/ressources/logs/web/rebuild-cache.txt";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=@file_get_contents($pidfile);
	if($oldpid<100){$oldpid=null;}
	$unix=new unix();
	$sock=new sockets();
	if($unix->process_exists($oldpid,basename(__FILE__))){
		ouputz("Already process exists $oldpid, aborting", __LINE__);
		die();}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);
	@unlink($logFile);
	ouputz("Please wait, rebuild caches....", __LINE__);
	$t=time();
	ouputz("Listing caches....", __LINE__);
	$array=ListCaches();
	$mv=$unix->find_program("mv");
	$rm=$unix->find_program("rm");
	$php5=$unix->LOCATE_PHP5_BIN();
	ouputz(count($array)." caches to delete...",__LINE__);
	if(count($array)==0){
		ouputz("Fatal, unable to list available caches...", __LINE__);
		@unlink("/etc/artica-postfix/squid.lock");
	die();}
	
	
	
	$t=time();
	@unlink("/etc/artica-postfix/squid.lock");
	@file_put_contents("/etc/artica-postfix/squid.lock", time());
	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	
	if(!is_file($squidbin)){
		ouputz("squid, no such binary file",__LINE__);
		@unlink("/etc/artica-postfix/squid.lock");
		return;
	}
	
	ouputz("Stopping squid, please wait...",__LINE__);
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
	squid_admin_notifs("Asking to Stop Squid for rebuilding caches\n".@implode("\n", $GLOBALS["LOGS"])."\n$executed", __FUNCTION__, __FILE__, __LINE__, "proxy");	
	shell_exec("$php5 /usr/share/artica-postfix/exec.squid.watchdog.php --stop");
	
	while (list ($cache_dir, $ligne) = each ($array) ){
		if(preg_match("#MemBooster#", $cache_dir)){
			ouputz("Removing $cache_dir content...",__LINE__);
			shell_exec("$rm -rf $cache_dir/*");
			continue;
		}
		$cachesRename[]="$cache_dir-delete-$t";
		ouputz("Moving $cache_dir to $cache_dir-delete-$t...",__LINE__);
		exec("$mv $cache_dir $cache_dir-delete-$t 2>&1",$results);
		$results=array();
		while (list ($num, $ll) = each ($results) ){ouputz("$ligne",__LINE__);}		
		ouputz("re-create $cache_dir",__LINE__);
		@mkdir($cache_dir,0755,true);
		@chown($cache_dir, "squid");
		@chgrp($cache_dir, "squid");
	}
	

	$su=$unix->find_program("su");
	$results=array();
	ouputz("Building new caches $su -c \"$squidbin -z\" squid",__LINE__);
	exec("$su -c \"$squidbin -z\" squid 2>&1",$results);
	
	while (list ($num, $ligne) = each ($results) ){ouputz("$ligne",__LINE__);}	
	
	ouputz("Remove lock file...",__LINE__);
	@unlink("/etc/artica-postfix/squid.lock");
	ouputz("Starting squid, please wait...",__LINE__);
	
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
	squid_admin_notifs("Asking to start squid after rebuilding caches...\n$executed", __FUNCTION__, __FILE__, __LINE__, "proxy");
		
	
	shell_exec("$php5 /usr/share/artica-postfix/exec.squid.watchdog.php --start");
	
	for($i=0;$i<60;$i++){
		$array=$unix->squid_get_cache_infos();
		if(count($array)>0){break;}
		ouputz("Waiting {$i}s/60 to Squid-cache be ready...",__LINE__);
		sleep(1);
	}
	
	ouputz("Done... Squid-cache seems to be ready...",__LINE__);
	ouputz("Reloading $squidbin cache",__LINE__);
	$results=array();
	squid_watchdog_events("Reconfiguring Proxy parameters...");
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$file=basename($trace[1]["file"]);$function=$trace[1]["function"];$line=$trace[1]["line"];$called="Called by $function() from line $line";}}
	squid_admin_mysql(2, "Rebuild caches: Reconfiguring squid-cache","$called");
	exec("$squidbin -k reconfigure 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){ouputz("$ligne",__LINE__);}
	
	$NICE=$unix->EXEC_NICE();
	$nohup=$unix->find_program("nohup");
	ouputz("Refresh caches information, please wait...",__LINE__);
	shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --cache-infos --force");
	reset($cachesRename);
	while (list ($index, $cache_dir) = each ($cachesRename) ){
		$cmd="$nohup $NICE $rm -rf $cache_dir >/dev/null 2>&1 &";
		ouputz("Deleting  $cache_dir $cmd",__LINE__);
		shell_exec($cmd);
	}
	
	$took=$unix->distanceOfTimeInWords($t,time());
	$sock->TOP_NOTIFY("All Proxy caches was rebuilded took: $took","info");	
	
	
}
function squid_watchdog_events($text){
	$unix=new unix();
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefile=basename($trace[1]["file"]);$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];}}
	$unix->events($text,"/var/log/squid.watchdog.log",false,$sourcefunction,$sourceline);
}





function ListCaches(){
	$f=file("/etc/squid3/squid.conf");
	while (list ($num, $ligne) = each ($f) ){
		if(preg_match("#^cache_dir\s+(.+?)\s+(.+?)\s+#", $ligne,$re)){
			$array[trim($re[2])]=true;
		}
	}
	return $array;
}
function ouputz($text,$line){
	
	if($GLOBALS["SCHEDULE_ID"]>1){
		if(function_exists("debug_backtrace")){$trace=@debug_backtrace();}		
		ufdbguard_admin_events($text, $trace[1]["function"], __FILE__, $line, "proxy");
	}
	
	
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	//if($GLOBALS["VERBOSE"]){echo "$text\n";}
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


