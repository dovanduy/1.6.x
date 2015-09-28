<?php
$GLOBALS["SCHEDULE_ID"]=0;
$GLOBALS["AD_PROGRESS"]=0;
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["ARGVS"]=implode(" ",$argv);
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if(preg_match("#--progress-activedirectory=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["AD_PROGRESS"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');




xstart();

function build_progress($text,$pourc){
	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/squid.ports.80.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}

function xstart(){
	$sock=new sockets();
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$SquidAllow80Port=intval($sock->GET_INFO("SquidAllow80Port"));
	build_progress("{starting} {allow_80443_port}",15);
	
	if($SquidAllow80Port==1){
		build_progress("{stopping} {web_service}",20);
		system("/etc/init.d/apache2 stop");
		build_progress("{stopping} Reverse Proxy",30);
		system("/etc/init.d/nginx stop");		
		
	}else{
		build_progress("{remove} 80/443 ports",20);
		$q=new mysql_squid_builder();
		$q->QUERY_SQL("DELETE FROM proxy_ports WHERE `port`='80'");
		build_progress("{remove} 80/443 ports",25);
		$q->QUERY_SQL("DELETE FROM proxy_ports WHERE `port`='443'");
		build_progress("{reconfigure_proxy_service}",30);
		$php=$unix->LOCATE_PHP5_BIN();
		shell_exec("$php /usr/share/artica-postfix/exec.squid.php --build --force");
		
	}
	
	
	build_progress("{restarting_artica_status}",80);
	system("/etc/init.d/artica-status restart --force");
	build_progress("{done}",100);
	
	
	
}



