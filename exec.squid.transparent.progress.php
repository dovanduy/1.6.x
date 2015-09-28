<?php
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


squid_transparent_exe();


function build_progress($text,$pourc){
	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/squid.transparent.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}

function squid_transparent_exe(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
			return;
	}
	
	@file_put_contents($pidfile, getmypid());
	
	
	
	$sock=new sockets();
	$squid=new squidbee();
	$WizardProxyTransparent=unserialize($sock->GET_INFO("WizardProxyTransparent"));
	$WizardProxyTransparent=unserialize($sock->GET_INFO("WizardProxyTransparent"));
	$connected_port=intval($WizardProxyTransparent["connected_port"]);
	$transparent_port=intval($WizardProxyTransparent["transparent_port"]);
	$transparent_ssl_port=intval($WizardProxyTransparent["transparent_ssl_port"]);
	$EnableSSLBump=intval($WizardProxyTransparent["EnableSSLBump"]);
	
	if($connected_port==0){
		build_progress("Fatal connected port unconfigured",110);
		return;
	}
	if($transparent_port==0){
		build_progress("Fatal Transparent port unconfigured",110);
		return;
	}	
	
	
	echo "Connected port........: $connected_port\n";
	echo "Transparent port......: $transparent_port\n";
	echo "Transparent SSL.......: $EnableSSLBump/$transparent_ssl_port\n";
	sleep(3);
	build_progress("{reconfigure}",20);
	
	$squid=new squidbee();
	$squid->listen_port=$transparent_port;
	$squid->second_listen_port=$connected_port;
	$squid->hasProxyTransparent=1;
	

	
	if($EnableSSLBump==1){
		echo "EnableSquidSSLCRTD ----> 1\n";
		$sock->SET_INFO("EnableSquidSSLCRTD", 1);
		$squid->SSL_BUMP=1;
		$squid->ssl_port=$transparent_ssl_port;
	}
	build_progress("{saving_parameters}",20);
	sleep(3);
	$squid->SaveToLdap(true);
	echo "hasProxyTransparent -------> 1\n";
	$sock->SET_INFO("hasProxyTransparent",1);
	echo "SquidTransparentMixed -----> 1\n";
	$sock->SET_INFO("SquidTransparentMixed", 1);
	
	build_progress("{building_settings}",30);
	$php=$unix->LOCATE_PHP5_BIN();
	system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
	build_progress("{reloading_service}",50);
	system("$php /usr/share/artica-postfix/exec.squid.watchdog.php --reload --force");
	build_progress("{apply_firewall_rules}",90);
	system("/etc/init.d/firehol restart");
	build_progress("{done}",100);
}
