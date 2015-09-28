<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["NOPROGRESS"]=false;

if(preg_match("#--verbose#",implode(" ",$argv))){
		$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
//$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);

include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.squidguard.inc');


if($argv[1]=="--checklic"){HyperCache_create_license();exit;}
if($argv[1]=="--wizard"){$GLOBALS["NOPROGRESS"]=true;}


checkcaches();



function build_progress($text,$pourc){
	if($GLOBALS["NOPROGRESS"]){return;}
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/squid.rock.progress", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/web/squid.rock.progress",0755);
	sleep(1);

}


function disable_rock(){
	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	$removed=false;
	
	build_progress("{remove_configuration}",50);
	
	while (list ($num, $val) = each ($results)){
		if(preg_match("#cache_dir\s+rock\s+#",$val,$re)){$removed=true;echo "Remove: $val\n";continue;}
		$results[]=$val;
	}
	
	if($removed){
		build_progress("{reload_proxy}",90);
		system("/etc/init.d/proxy reload --force --script=".basename(__FILE__));
	}
	
}

function checkcaches(){
	$unix=new unix();
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM squid_caches_center WHERE cache_type='rock' LIMIT 0,1","artica_backup"));
	
	if(!$q->ok){build_progress($q->mysql_error,110);return;}
	
	$cache_size=$ligne["cache_size"];
	$cache_directory=$ligne["cache_dir"];
	build_progress("{checking} Rock {$cache_size}M",10);
	build_progress("{checking_current_configuration}",15);
	
	
	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	while (list ($num, $val) = each ($results)){
		if(preg_match("#cache_dir\s+rock\s+(.+?)\s+([0-9+])#",$val,$re)){
			$current_cache_size=$re[1];
			$current_directory=$re[2];
		}
		
	}
	$sock=new sockets();
	$EnableRockCache=intval($sock->GET_INFO("EnableRockCache"));
	
	echo "EnableRockCache = $EnableRockCache\n";
	echo "Current Directory = $current_directory\n";
	echo "Current Size = {$current_cache_size}M\n";
	echo "Defined Directory = $cache_directory\n";
	echo "Defined size = $cache_size\n";
	build_progress("{checking_current_configuration}",20);
	
	if($EnableRockCache==0){
		disable_rock();
		build_progress("{success}",100);
		return;
	}
	
	if($current_directory<>$cache_directory){$REBUILD=true;}
	if($current_cache_size<>$cache_size){$REBUILD=true;}
	
	if($REBUILD==false){
		build_progress("{nothing_to_do}",100);
		return;
	}
	
	if(is_dir($current_directory)){
		build_progress("{removing_old_cache}",40);
		$rm=$unix->find_program("rm");
		shell_exec("$rm -rvf $current_directory");
	}
	
	build_progress("{build_new_cache}",50);
	@mkdir($cache_directory,0755,true);
	@chown($cache_directory,"squid");
	@chgrp($cache_directory, "squid");
	$filetmp=$unix->FILE_TEMP()."conf";
	$f=array();
	$f[]="cache_effective_user squid";
	$f[]="pid_filename	/var/run/squid-temp.pid";
	$f[]="http_port 65478";
	$f[]="cache_dir rock $cache_directory $cache_size min-size=2048 max-size=32768";
	$f[]="";
	
	@file_put_contents("$filetmp", @implode("\n", $f));
	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	echo "$squidbin -f $filetmp -z\n";
	shell_exec("$squidbin -f $filetmp -z");
	@unlink($filetmp);
	build_progress("{reconfiguring_proxy_service}",80);
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php /usr/share/artica-postfix/exec.squid.php --build --force --script=".basename(__FILE__));
	build_progress("{restarting_proxy_service}",90);
	shell_exec("$php /usr/share/artica-postfix/exec.squid.watchdog.php --restart --script=".basename(__FILE__));
	build_progress("{done}",100);
	
}
