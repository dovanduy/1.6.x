<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");



if(isset($_GET["restart-progress"])){restart_progress();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["reload-hosts"])){reload_hosts();exit;}
if(isset($_GET["reload-hosts"])){reload_hosts();exit;}
if(isset($_GET["save-dhcp-role"])){save_dhcp_role();exit;}
if(isset($_GET["delete-dhcp-role"])){remove_dhcp_role();exit;}




writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);	


function restart_progress(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/dnsmasq.restart.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/dnsmasq.restart.progress.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.dnsmasq.php --restart-progress --output >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

	
}

function restart(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$unix->ToSyslog("Artica Framework: Order framework to restart service");
	$cmd=trim($nohup." /etc/init.d/dnsmasq restart >/dev/null 2>&1 &");
	shell_exec($cmd);	
}

function reload_hosts(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$unix->ToSyslog("Artica Framework: Order framework to reload service");
	shell_exec("$nohup /etc/init.d/dnsmasq restart >/dev/null 2>&1 &");
	
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
