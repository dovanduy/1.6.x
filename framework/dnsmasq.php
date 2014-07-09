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
	shell_exec("$nohup $php /usr/share/artica-postfix/exec.dnsmasq.php --restart");
	
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
