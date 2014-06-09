<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }
$GLOBALS["LOGFILE"]="{$GLOBALS["ARTICALOGDIR"]}/dansguardian-logger.debug";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}



$unix=new unix();
$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
$pid=$unix->get_pid_from_file($pidfile);
if($unix->process_exists($pid,basename(__FILE__))){
	system_admin_events("Already PID $pid executed, aborting", __FUNCTION__, __FILE__, __LINE__, "watchdog");
}

$sock=new sockets();
$LighttpdArticaListenIP=$sock->GET_INFO("LighttpdArticaListenIP");
$ArticaHttpsPort=$sock->GET_INFO("ArticaHttpsPort");
$ArticaHttpUseSSL=$sock->GET_INFO("ArticaHttpUseSSL");
if(!is_numeric($ArticaHttpUseSSL)){$ArticaHttpUseSSL=1;}
if($ArticaHttpsPort==null){$ArticaHttpsPort="9000";}
if($LighttpdArticaListenIP==null){$LighttpdArticaListenIP="localhost";}
$NoDryReboot=$sock->GET_INFO("NoDryReboot");
if(!is_numeric($NoDryReboot)){$NoDryReboot=0;}

$proto="http";
if($ArticaHttpUseSSL==1){$proto="https";}
$uri=
$curl=new ccurl("$proto://$LighttpdArticaListenIP:$ArticaHttpsPort/logon.php");
$curl->NoHTTP_POST=true;
$curl->noproxyload=true;
if(!$curl->get()){
	if($GLOBALS["VERBOSE"]){echo "Error Data returned error $curl->error\n";}
	if($curl->error==500){
		exec("/etc/init.d/artica-postfix restart apache 2>&1",$results);
		system_admin_events("Error 500 detected on the Artica Web interface, reboot web server".@implode("\n", $results), __FUNCTION__, __FILE__, __LINE__, "watchdog");
		$curl=new ccurl("$proto://$LighttpdArticaListenIP:$ArticaHttpsPort/logon.php");
		$curl->NoHTTP_POST=true;
		$curl->noproxyload=true;
		if(!$curl->get()){
			system_admin_events("Error 500 detected on the Artica Web interface, after reboot web server".@implode("\n", $results), __FUNCTION__, __FILE__, __LINE__, "watchdog");
			if($NoDryReboot==0){
				system_admin_events("Error 500 detected: reboot the server...".@implode("\n", $results), __FUNCTION__, __FILE__, __LINE__, "watchdog");
				$unix->send_email_events("Web Error 500 as been detected, reboot the server", "Web service still stay in 500 status after rebooting the web service, artica will reboot the server",
				 "system");
				$reboot=$unix->find_program("reboot");
				shell_exec($reboot);
			}
		}
	}
	
}

?>