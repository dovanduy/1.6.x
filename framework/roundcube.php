<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");



if(isset($_GET["mysqldb-restart"])){mysql_db_restart();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["version"])){version();exit;}





while (list ($num, $line) = each ($_GET)){$f[]="$num=$line";}

writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die();



function mysql_db_restart(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.roundcube-db.php --init >/dev/null 2>&1");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
	$cmd=trim("$nohup /etc/init.d/roundcube-db restart >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	
	$cmd=trim("$nohup /etc/init.d/artica-status restart >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	
	
}

function status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.status.php --roundcube 2>&1");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	exec($cmd,$results);
	writelogs_framework("$cmd ".count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";
}

function version(){
	$data=@file_get_contents("/usr/share/roundcube/program/include/iniset.php");
	$t=explode("\n",$data);
	
	while (list ($num, $line) = each ($t)){
		if(preg_match('#RCMAIL_VERSION.*?([0-9\.]+)#',$line,$re)){
			$ver=trim($re[1]);
			break;
		}
	}
	echo "<articadatascgi>$ver</articadatascgi>";
	
	
	
}

