<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["RELOAD"]=false;
$GLOBALS["pidStampReload"]="/etc/artica-postfix/pids/".basename(__FILE__).".Stamp.reload.time";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;
$GLOBALS["debug"]=true;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}



$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.nginx.inc');
include_once(dirname(__FILE__).'/ressources/class.freeweb.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.reverse.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.resolv.conf.inc');

if($argv[1]=="--verif"){verif();exit;}

startx();

function build_progress($text,$pourc){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/nginx-enable.progress";
	echo "[{$pourc}%] $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);
	sleep(1);

}

function verif(){
	$unix=new unix();
	$sock=new sockets();
	$sock=new sockets();
	$t=time();
	$EnableNginx=intval($sock->GET_INFO("EnableNginx"));
	$EnableFreeWeb=intval($sock->GET_INFO("EnableFreeWeb"));
	$EnableNginxMail=intval($sock->GET_INFO("EnableNginxMail"));
	$SQUIDEnable=trim($sock->GET_INFO("SQUIDEnable"));
	$php=$unix->LOCATE_PHP5_BIN();
	
	build_progress("Restart Artica status",5);
	system('/etc/init.d/artica-status restart --force');
	
	if($EnableNginx==1){
		build_progress("Reverse Proxy is enabled",10);
		build_progress("{starting} reverse Proxy service",15);
		system("/etc/init.d/nginx start");
	}else{
		build_progress("Reverse Proxy is disabled",10);
		build_progress("{stopping} reverse Proxy service",15);
		system("/etc/init.d/nginx stop");		
		
	}
	
	
	if($EnableFreeWeb==1){
		build_progress("Web server is enabled",20);
		build_progress("{starting} Web service",30);
		system("/etc/init.d/apache2 start");
	}else{
		build_progress("Web server is disabled",20);
		build_progress("{stopping} Web service",30);
		system("/etc/init.d/apache2 stop");		
	}
	
	if($SQUIDEnable==1){
		build_progress("Proxy service is enabled",50);
		build_progress("{starting} Proxy service",60);
		system("/etc/init.d/squid start");
	}else{
		build_progress("Proxy service is disabled",50);
		build_progress("{stopping} Proxy service",60);
		system("/etc/init.d/squid stop");
	}	
	
	build_progress("{done}",100);
	
}

function startx(){
	$unix=new unix();
	$sock=new sockets();

	
	
	
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress("Set true for service...",10);
	$sock->SET_INFO("EnableNginx", 1);
	build_progress("Restart Artica status",20);
	system('/etc/init.d/artica-status restart');
	build_progress("{reconfigure} Web server service",20);
	system("$php /usr/share/artica-postfix/exec.freeweb.php --build --force");
	build_progress("Restart Web server service",30);
	system("/etc/init.d/apache2 restart");
	build_progress("Restart Reverse Proxy service",50);
	system("/etc/init.d/nginx restart");
	build_progress("{done}",100);
	
}
