<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["rebuild-database"])){rebuild_database();exit;}
if(isset($_GET["replic"])){replic_artica_servers();exit;}
if(isset($_GET["digg"])){digg();exit;}
if(isset($_GET["repair-tables"])){repair_tables();exit;}


writelogs_framework("Unable to understand the query ".@implode(" ",$_GET),__FUNCTION__,__FILE__,__LINE__);	




function rebuild_database(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.pdns.php --rebuild-database 2>&1 &");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);
}

function repair_tables(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.pdns.php --mysql --verbose 2>&1");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	exec($cmd,$results);	
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
	
}

function replic_artica_servers(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.pdns.php --replic-artica 2>&1 &");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);	
	
}
function digg(){
	$unix=new unix();
	$digg=$unix->find_program("dig");
	if(!is_file($digg)){
		echo "<articadatascgi>".base64_encode(serialize(array("dig, nos such binary")))."</articadatascgi>";
		return;
	}
	
	$hostname=$_GET["hostname"];
	$interface=$_GET["interface"];
	if($interface==null){$interface="127.0.0.1";}
	if($hostname==null){$hostname="www.google.com";}
	$cmd="$digg @$interface $hostname 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	exec($cmd,$results);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
	
}
