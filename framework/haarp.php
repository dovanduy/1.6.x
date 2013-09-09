<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["status"])){status();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["sync-freewebs"])){sync_freewebs();exit;}
if(isset($_GET["access-events"])){access_events();exit;}



while (list ($num, $line) = each ($_GET)){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die();




function restart(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php5 /usr/share/artica-postfix/exec.initslapd.php --haarp");
	squid_admin_mysql(0, "Framework order to restart Haarp","");
	shell_exec("$nohup /etc/init.d/haarp restart >/dev/null 2>&1 &");
	shell_exec("$nohup /etc/init.d/artica-status restart >/dev/null 2>&1 &");
	
}

function status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();	
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.status.php --haarp --nowachdog 2>&1");
	exec($cmd,$results);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	echo "<articadatascgi>". base64_encode(@implode("\n", $results))."</articadatascgi>";	
}

function pattern(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.haarp.php --squid-pattern >/dev/null 2>&1");	
	
}
function access_events(){
	
	$filename="/var/log/squid/haarp.access.log";
	$search=$_GET["access-events"];
	$unix=new unix();
	$search=$unix->StringToGrep($search);
	$grep=$unix->find_program("grep");
	$tail=$unix->find_program("tail");
	$refixcmd="$tail -n 2500 $filename";
	if($search<>null){
		$refixcmd=$refixcmd."|$grep -i -E '$search'|$tail -n 500";
	}else{
		$refixcmd="$tail -n 500 $filename";
	}
	
	
	exec($refixcmd." 2>&1",$results);
	writelogs_framework($refixcmd." (".count($results).")",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
	
}
