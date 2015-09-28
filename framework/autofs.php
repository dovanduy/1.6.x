<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");



if(isset($_GET["autofs-ini-status"])){AUTOFS_STATUS();exit;}
if(isset($_GET["autofs-restart"])){AUTOFS_RESTART();exit;}
if(isset($_GET["autofs-reload"])){AUTOFS_RELOAD();exit;}

if(isset($_GET["service-cmds"])){service_cmds();exit;}
if(isset($_GET["restart-progress"])){AUTOFS_RESTART_PROGRESS();exit;}


writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);	

function AUTOFS_STATUS(){
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --autofs --nowachdog",$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";
}



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

function AUTOFS_RESTART_PROGRESS(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/autofs.restart.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/autofs.restart.progress.txt";
	
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.AutoFS.php --restart-progress >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
	
}


function AUTOFS_RESTART(){
	$unix=new unix();
	
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$rm=$unix->find_program("rm");
	$tempfile=$unix->FILE_TEMP().".sh";

	$sh[]="#!/bin/sh";
	$sh[]="$php /usr/share/artica-postfix/exec.AutoFS.php --checks >/dev/null";
	$sh[]="/etc/init.d/autofs restart >/dev/null";
	$sh[]="$rm $tempfile";
	$sh[]="";
	@file_put_contents("$tempfile", @implode("\n", $sh));
	@chmod($tempfile,0777);
	$cmd="$nohup $tempfile >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}

function AUTOFS_RELOAD(){
	$unix=new unix();
	
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$rm=$unix->find_program("rm");
	$tempfile=$unix->FILE_TEMP().".sh";
	
	$sh[]="#!/bin/sh";
	$sh[]="$php /usr/share/artica-postfix/exec.AutoFS.php --checks >/dev/null";
	$sh[]="/etc/init.d/autofs reload >/dev/null";
	$sh[]="$rm $tempfile";
	$sh[]="";
	@file_put_contents("$tempfile", @implode("\n", $sh));
	
	$cmd="$nohup $tempfile >/dev/null 2>&1 &";
	@chmod($tempfile,0777);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	

}




?>