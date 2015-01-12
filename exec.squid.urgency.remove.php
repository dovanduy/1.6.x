<?php
$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.urgency.disable.progress";
$GLOBALS["ARGVS"]=implode(" ",$argv);
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if(preg_match("#--progress-activedirectory=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["AD_PROGRESS"]=$re[1];}


include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');


xstart();



function build_progress($text,$pourc){



	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	sleep(1);

}


function xstart(){
	$unix=new unix();
	build_progress("Stamp emerency to off",20);
	@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidUrgency", 0);
	@chmod("/etc/artica-postfix/settings/Daemons/SquidUrgency",0755);
	build_progress("{reconfiguring}",30);
	$php=$unix->LOCATE_PHP5_BIN();
	system("$php /usr/share/artica-postfix/exec.squid.php --build --noreload");
	build_progress("{restarting} {APP_SQUID}",50);
	system("$php /usr/share/artica-postfix/exec.squid.watchdog.php --restart --force --urgency");
	
	build_progress("{starting} {webfiltering}",60);
	system("/etc/init.d/ufdb start");
	build_progress("{restarting} Status service",70);
	system("/etc/init.d/artica-status restart");
	@unlink("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS");
	@unlink("/usr/share/artica-postfix/ressources/logs/web/ufdb.rules_toolbox_left.html");
	build_progress("{done} {APP_SQUID}",100);
}