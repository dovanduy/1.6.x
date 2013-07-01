<?php
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["ARGVS"]=implode(" ",$argv);
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.templates.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.remote-stats-appliance.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.ini.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.squid.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.reverse.inc');

if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::framework/class.unix.inc\n";}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::frame.class.inc\n";}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.html.tools.inc');
$unix=new unix();
$sock=new sockets();
$GLOBALS["RELOAD"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["NO_USE_BIN"]=false;
$GLOBALS["REBUILD"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["NOCACHES"]=false;
$GLOBALS["NOAPPLY"]=false;
$GLOBALS["NORELOAD"]=false;
WriteMyLogs("commands= ".implode(" ",$argv),"MAIN",__FILE__,__LINE__);
if(!is_file("/usr/share/artica-postfix/ressources/settings.inc")){shell_exec("/usr/share/artica-postfix/bin/process1 --force --verbose");}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["NORELOAD"]=true;}
if(preg_match("#--noreload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#--withoutloading#",implode(" ",$argv))){$GLOBALS["NO_USE_BIN"]=true;}
if(preg_match("#--nocaches#",implode(" ",$argv))){$GLOBALS["NOCACHES"]=true;}
if(preg_match("#--noapply#",implode(" ",$argv))){$GLOBALS["NOCACHES"]=true;$GLOBALS["NOAPPLY"]=true;$GLOBALS["FORCE"]=true;}


if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}



$squidbin=$unix->find_program("squid3");
$php5=$unix->LOCATE_PHP5_BIN();
if(!is_file($squidbin)){$squidbin=$unix->find_program("squid");}
$GLOBALS["SQUIDBIN"]=$squidbin;
$GLOBALS["CLASS_USERS"]=new usersMenus();
if($GLOBALS["VERBOSE"]){echo "squid binary=$squidbin\n";}

build();


function build(){
	@mkdir("/var/log/artica-postfix/squid-reverse",0777,true);
	@chmod("/var/log/artica-postfix/squid-reverse", 0777);
	$sock=new sockets();
	$unix=new unix();
	$nginx=$unix->find_program("nginx");
	
	if(is_file($nginx)){
		$EnableNginx=$sock->GET_INFO("EnableNginx");
		if(!is_numeric($EnableNginx)){$EnableNginx=1;}
		if($EnableNginx==1){
			echo "Starting......: Building reverse websites with nginx...\n";
			$php5=$unix->LOCATE_PHP5_BIN();
			@file_put_contents("/etc/squid3/reverse.conf","\n");
			shell_exec("$php5 ".dirname(__FILE__)."/exec.nginx.php --build");
			return;
		}
	
	}
		
	$squid=new squid_reverse();
	echo "Starting......: Building reverse websites...\n";
	$q=new mysql_squid_builder();
	$squidR=new squidbee();
	
	$conf[]=$squid->acl_by_cache_peer();

	echo @implode("\n", $conf);
	
	
	echo "Starting......: Building /etc/squid3/reverse.conf done...\n";
	@file_put_contents("/etc/squid3/reverse.conf", @implode("\n", $conf)."\n");
	if(!$GLOBALS["NORELOAD"]){
		shell_exec($unix->LOCATE_SQUID_BIN()." -k reconfigure");
		
	}
	
}

function servername_tokey($servername){
	$html=new htmltools_inc();
	return $html->StripSpecialsChars($servername);
}



function WriteMyLogs($text,$function=null,$file=null,$line=0){
	if(!isset($GLOBALS["MYPID"])){$GLOBALS["MYPID"]=getmypid();}
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}

	}
	$file=basename(__FILE__);
	if($function==null){$function=$sourcefunction;}
	if($line==0){$line=$sourceline;}
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	$GLOBALS["CLASS_UNIX"]->events($text,"/var/log/squid.watchdog.log",false,$sourcefunction,$sourceline);
}