<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["service-cmds"])){service_cmds();exit;}


writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);	
function service_cmds(){
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
//	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.mimedefang.php 2>&1");
//	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
//	exec($cmd,$results);
	$cmds=$_GET["service-cmds"];
	$results[]="Postition: $cmds";
	exec("/etc/init.d/artica-postfix $cmds autofs 2>&1",$results);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
}

function reload_tenir(){
	writelogs_framework("Reloading mimedefang...",__FUNCTION__,__FILE__,__LINE__);	
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	exec("$php5 /usr/share/artica-postfix/exec.mimedefang.php 2>&1",$results);
	exec("/etc/init.d/mimedefang reload 2>&1",$results);
	writelogs_framework("Reloading mimedefang done",__FUNCTION__,__FILE__,__LINE__);	
	echo "<articadatascgi>".base64_encode(@implode("\n",$results))."</articadatascgi>";
	
}

function restart(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.mimedefang.php 2>&1");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	exec($cmd,$results);
	$cmds=$_GET["service-cmds"];
	$results[]="Postition: $cmds";
	exec("/etc/init.d/mimedefang restart 2>&1",$results);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";	
	
}
?>