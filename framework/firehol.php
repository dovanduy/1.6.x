<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["is-installed"])){is_installed();exit;}
if(isset($_GET["reconfigure-progress"])){reconfigure_progress();exit;}
if(isset($_GET["install-progress"])){install_progress();exit;}
if(isset($_GET["firehol-version"])){firehol_version();exit;}
if(isset($_GET["disable-progress"])){disable_progress();exit;}
if(isset($_GET["enable-progress"])){enable_progress();exit;}




while (list ($num, $line) = each ($_GET)){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die();


function firehol_version(){

	exec("/usr/local/sbin/firehol 2>&1",$f);
	while (list ($num, $filename) = each ($f)){
		if(preg_match("#FireHOL\s+([0-9\.]+)#", $filename,$re)){
			echo "<articadatascgi>{$re[1]}</articadatascgi>";
			return;
		}
	}
}

function install_progress(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/firehol.reconfigure.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/firehol.reconfigure.progress.txt";
	
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"],0777);
	@chmod($GLOBALS["LOG_FILE"],0777);
	
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.firehol.php --install-progress >{$GLOBALS["LOG_FILE"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
	
}

function enable_progress(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/firehol.reconfigure.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/firehol.reconfigure.progress.txt";
	
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"],0777);
	@chmod($GLOBALS["LOG_FILE"],0777);
	
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.firehol.php --enable-progress >{$GLOBALS["LOG_FILE"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
	
}

function disable_progress(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/firehol.reconfigure.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/firehol.reconfigure.progress.txt";
	
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"],0777);
	@chmod($GLOBALS["LOG_FILE"],0777);
	
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.firehol.php --disable-progress >{$GLOBALS["LOG_FILE"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}

function is_installed(){
	
	if(is_file("/usr/local/sbin/firehol")){
		echo "<articadatascgi>1</articadatascgi>";
	}else{
		echo "<articadatascgi>0</articadatascgi>";
	}
}

function reconfigure_progress(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/firehol.reconfigure.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/firehol.reconfigure.progress.txt";

	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"],0777);
	@chmod($GLOBALS["LOG_FILE"],0777);
	
	$comand="--reconfigure-progress";
	
	if($_GET["comand"]<>null){
		if($_GET["comand"]=="stop"){
			$comand="--stop";
		}
		if($_GET["comand"]=="start"){
			$comand="--start";
		}		
	}

	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.firehol.php {$comand} >{$GLOBALS["LOG_FILE"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function save_client_config(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.amanda.php --comps >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function save_client_server(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.amanda.php --backup-server >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

