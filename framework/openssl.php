<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["generate-key"])){generate_key();exit;}
if(isset($_GET["generate-x509"])){generate_x509();exit;}
if(isset($_GET["generate-x509-client"])){generate_x509_client();exit;}

if(isset($_GET["tomysql"])){tomysql();exit;}





while (list ($num, $line) = each ($_GET)){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die();


function generate_key(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$servername=$_GET["generate-key"];
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.openssl.php --buildkey $servername >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	
}

function generate_x509_client(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/openssl.x509.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/openssl.x509.log";
	
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);
	
	
	$servername=$_GET["generate-x509-client"];
	$servername=str_replace("*", "_ALL_", $servername);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.openssl.php --client-server \"$servername\" --output >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	exec($cmd,$results);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";	
	
	
}


function generate_x509(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/openssl.x509.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/openssl.x509.log";

	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);
	
	
	$servername=$_GET["generate-x509"];
	$servername=str_replace("*", "_ALL_", $servername);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.openssl.php --x509 $servername --output >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	exec($cmd,$results);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";	
	
}
function tomysql(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$servername=$_GET["tomysql"];
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.openssl.php --mysql $servername 2>&1");
	exec($cmd,$results);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(trim(@implode("\n",$results)))."</articadatascgi>";	
	
}