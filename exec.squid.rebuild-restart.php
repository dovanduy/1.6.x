<?php
ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
$GLOBALS["SCHEDULE_ID"]=0;
$GLOBALS["AD_PROGRESS"]=0;
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["ARGVS"]=implode(" ",$argv);
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if(preg_match("#--progress-activedirectory=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["AD_PROGRESS"]=$re[1];}

if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.templates.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.remote-stats-appliance.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.ini.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.squid.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::framework/class.unix.inc\n";}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::frame.class.inc\n";}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.acls.inc');
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}


startx();

function build_progress($text,$pourc){
	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/squid.build.progress";

	if(is_file($cachefile)){
		$textAR=unserialize(@file_get_contents($cachefile));
		if($textAR["POURC"]>100){die();}
	}
	
	
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	


}

function startx(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$tar=$unix->find_program("tar");
	build_progress("{backup_parameters}....",5);
	chdir("/etc/squid3");
	system("cd /etc/squid3");
	system("$tar -czf /root/backup.squid.tar.gz *");
	build_progress("{reconfiguring}....",10);
	system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
	build_progress("{stopping_service}....",50);
	system("/etc/init.d/squid stop");
	build_progress("{starting_service}....",80);
	system("/etc/init.d/squid start");
	
	
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/squid.start.progress";
	if(is_file($cachefile)){
		$textAR=unserialize(@file_get_contents($cachefile));
		if($textAR["POURC"]>100){
			build_progress("{restore_parameters}....",90);
			shell_exec("$tar -xf /root/backup.squid.tar.gz -C /etc/squid3/");
			build_progress("{starting_service}....",90);
			system("/etc/init.d/squid start");
		}
	}
	@unlink("/root/backup.squid.tar.gz");
	build_progress("{starting_service} {success}",100);
	chdir("/root");
	
}
