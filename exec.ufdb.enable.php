<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["WATCHDOG"]=false;
$GLOBALS["MONIT"]=false;
$GLOBALS["UFDBTAIL"]=false;
$GLOBALS["TITLENAME"]="Webfilter Daemon";
$GLOBALS["AFTER-FATAL-ERROR"]=false;
$GLOBALS["BYSCHEDULE"]=false;
$GLOBALS["HUMAN"]=false;
$GLOBALS["PID_PATH"]="/var/run/urlfilterdb/ufdbguardd.pid";
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--from-schedule#",implode(" ",$argv),$re)){$GLOBALS["BYSCHEDULE"]=true;}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--monit#",implode(" ",$argv),$re)){$GLOBALS["MONIT"]=true;}
if(preg_match("#--watchdog#",implode(" ",$argv),$re)){$GLOBALS["WATCHDOG"]=true;$GLOBALS["FORCE"]=true;}
if(preg_match("#--ufdbtail#",implode(" ",$argv),$re)){$GLOBALS["UFDBTAIL"]=true;$GLOBALS["FORCE"]=true;}
if(preg_match("#--fatal-error#",implode(" ",$argv),$re)){$GLOBALS["AFTER-FATAL-ERROR"]=true;$GLOBALS["FORCE"]=true;}
if(preg_match("#--human#",implode(" ",$argv),$re)){$GLOBALS["HUMAN"]=true;$GLOBALS["FORCE"]=true;}



$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.compile.ufdbguard.inc');

$unix=new unix();
$php=$unix->LOCATE_PHP5_BIN();
build_progress("{reconfiguring} {proxy_service}",10);
system("$php /usr/share/artica-postfix/exec.squid.php --build --force --output");
build_progress("{reconfiguring} {webfiltering_service}",30);
system("$php /usr/share/artica-postfix/exec.squidguard.php --build --force --output");
build_progress("{restarting} {webfiltering_service}",50);
system("/etc/init.d/ufdb restart --force");
build_progress("{restarting} {error_page_service}",70);
system("/etc/init.d/squidguard-http restart --force");
build_progress("{restarting_artica_status}",80);
system("/etc/init.d/artica-status restart --force");
build_progress("{done}",100);

function build_progress($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/ufdb.enable.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
	sleep(1);

}