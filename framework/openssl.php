<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["generate-key"])){generate_key();exit;}
if(isset($_GET["generate-x509"])){generate_x509();exit;}
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
function generate_x509(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$servername=$_GET["generate-x509"];
	$servername=str_replace("*", "_ALL_", $servername);
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.openssl.php --x509 $servername 2>&1");
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