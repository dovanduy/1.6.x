<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');

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
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=@file_get_contents($pidfile);
	if($oldpid<100){$oldpid=null;}
	$unix=new unix();
	$sock=new sockets();
	if($unix->process_exists($oldpid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid\n";}die();}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);	
	
	$t=time();
	$array=ListCaches();
	$mv=$unix->find_program("mv");
	$rm=$unix->find_program("rm");
	$php5=$unix->LOCATE_PHP5_BIN();
	writelogs(count($array)." caches to delete...",__FUNCTION__,__FILE__,__LINE__);
	if(count($array)==0){writelogs("Fatal error",__FUNCTION__,__FILE__,__LINE__);@unlink("/etc/artica-postfix/squid.lock");return;}
	
	
	
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
	shell_exec("/etc/init.d/artica-postfix stop squid-cache");
	while (list ($cache_dir, $ligne) = each ($array) ){
		$cachesRename[]="$cache_dir-delete-$t";
		exec("$mv $cache_dir $cache_dir-delete-$t 2>&1",$results);
		$results=array();
		while (list ($num, $ll) = each ($results) ){
			writelogs("$ligne",__FUNCTION__,__FILE__,__LINE__);
		}		
		writelogs("re-create $cache_dir",__FUNCTION__,__FILE__,__LINE__);
		@mkdir($cache_dir,0755,true);
		@chown($cache_dir, "squid");
		@chgrp($cache_dir, "squid");
	}
	

		
	$results=array();
	writelogs("Building new caches",__FUNCTION__,__FILE__,__LINE__);
	exec("$squidbin -z 2>&1",$results);
	
	while (list ($num, $ligne) = each ($results) ){
		writelogs("$ligne",__FUNCTION__,__FILE__,__LINE__);
		
	}	
	
	@unlink("/etc/artica-postfix/squid.lock");
	writelogs("starting squid",__FUNCTION__,__FILE__,__LINE__);
	shell_exec("/etc/init.d/artica-postfix start squid-cache");
	
	for($i=0;$i<60;$i++){
		$array=$unix->squid_get_cache_infos();
		if(count($array)>0){break;}
		writelogs("Waiting 1s to squid be ready...",__FUNCTION__,__FILE__,__LINE__);
		sleep(1);
	}
	
	
	shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --cache-infos --force");
	
	while (list ($index, $cache_dir) = each ($cachesRename) ){
		writelogs("Deleting  $cache_dir",__FUNCTION__,__FILE__,__LINE__);
		shell_exec("$rm -rf $cache_dir");
	}
	
	
	$took=$unix->distanceOfTimeInWords($t,time());
	$sock->TOP_NOTIFY("All Proxy caches was rebuilded took: $took","info");	
	
	
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



