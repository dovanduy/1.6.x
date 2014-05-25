<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");



if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["reload-hosts"])){reload_hosts();exit;}
if(isset($_GET["reload-hosts"])){reload_hosts();exit;}
if(isset($_GET["save-dhcp-role"])){save_dhcp_role();exit;}
if(isset($_GET["delete-dhcp-role"])){remove_dhcp_role();exit;}




writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);	



function restart(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	WriteToSyslog("Artica Framework: Order framework to restart service","dnsmasq");
	
	shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.initslapd.php --dnsmasq");
	$cmd=trim($nohup." /etc/init.d/dnsmasq restart >/dev/null 2>&1 &");
	shell_exec($cmd);	
}

function reload_hosts(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	WriteToSyslog("Artica Framework: Order framework to reload service","dnsmasq");
	shell_exec($nohup." ".LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.dnsmasq.php --build-hosts");
	
}

function save_dhcp_role(){
	$eth=$_GET["eth"];
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php /usr/share/artica-postfix/exec.dnsmasq.php --install-service $eth");
	
}
function remove_dhcp_role(){
	$eth=$_GET["eth"];
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php /usr/share/artica-postfix/exec.dnsmasq.php --remove-service $eth");

}

function WriteToSyslog($text,$file,$error=false){
	$file=basename($file);
	if(!$error){$LOG_SEV=LOG_INFO;}else{$LOG_SEV=LOG_ERR;}
	if(function_exists("openlog")){openlog($file, LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
	if(function_exists("closelog")){closelog();}
}

function LOCATE_PHP5_BIN2(){
	if(!isset($GLOBALS["CLASS_UNIX"])){ include_once(dirname(__FILE__)."/class.unix.inc");$GLOBALS["CLASS_UNIX"]=new unix();}
	if(!is_object($GLOBALS["CLASS_UNIX"])){include_once(dirname(__FILE__)."/class.unix.inc");$GLOBALS["CLASS_UNIX"]=new unix();}
	return $GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN();
}