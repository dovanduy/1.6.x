<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["db-size"])){db_size();exit;}
if(isset($_GET["recompile"])){recompile();exit;}
if(isset($_GET["recompile-all"])){recompile_all();exit;}
if(isset($_GET["db-status"])){db_status();exit;}
if(isset($_GET["recompile-dbs"])){recompile_all();exit;}
if(isset($_GET["service-cmds"])){service_cmds();exit;}






while (list ($num, $line) = each ($_GET)){$f[]="$num=$line";}

writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die();


function db_size(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php /usr/share/artica-postfix/exec.squidguard.php --ufdbguard-status");
}

function recompile(){
	@mkdir("/etc/artica-postfix/ufdbguard.recompile-queue",644,true);
	$db=$_GET["recompile"];
	@file_put_contents("/etc/artica-postfix/ufdbguard.recompile-queue/".md5($db)."db",$db);
	
}

function recompile_all(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squidguard.php --ufdbguard-recompile-dbs >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}

function db_status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.squidguard.php --databases-status >/dev/null 2>&1");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
}

function service_cmds(){
	$action=$_GET["service-cmds"];
	
	if($action=="reconfigure"){
		$php5=$unix->LOCATE_PHP5_BIN();
		exec("$php5 /usr/share/artica-postfix/exec.squidguard.php --build --verbose 2>&1",$results);
		echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
		return;
		
	}
	
	$results[]="/etc/init.d/ufdb $action 2>&1";
	exec("/etc/init.d/ufdb $action 2>&1",$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}