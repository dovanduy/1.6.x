<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["status-infos"])){status_info();exit;}
if(isset($_GET["delete-cache"])){delete_cache();exit;}
if(isset($_GET["sync-freewebs"])){sync_freewebs();exit;}
if(isset($_GET["www-events"])){www_events();exit;}
if(isset($_GET["mysqldb-restart"])){mysqldb_restart();exit;}
if(isset($_GET["restart"])){restart();exit;}


while (list ($num, $line) = each ($_GET)){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die();

function status_info(){
	$unix=new unix();
	$nginx=$unix->find_program("nginx");
	if(!is_file($nginx)){return;}
	$php5=$unix->LOCATE_PHP5_BIN();
	$ARRAY=$unix->NGINX_COMPILE_PARAMS();
	
	
	exec("$php5 /usr/share/artica-postfix/exec.status.php --nginx --nowachdog 2>&1",$results);
	$ARRAY["STATUS"]=@implode("\n", $results);
	
	echo "<articadatascgi>".base64_encode(serialize($ARRAY))."</articadatascgi>";

}
function sync_freewebs(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php /usr/share/artica-postfix/exec.freeweb.php --sync-squid");
	
}

function restart(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup /etc/init.d/nginx restart >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function delete_cache(){
	
	$directory=base64_decode($_GET["delete-cache"]);
	if(trim($directory)==null){return;}
	if(!is_dir($directory)){return;}
	$unix=new unix();
	if($unix->IsProtectedDirectory($directory,true)){return;}
	$rm=$unix->find_program("rm");
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $rm -rf \"$directory\" >/dev/null 2>&1 &");
}
function www_events(){
	$servername=$_GET["servername"];
	$port=$_GET["port"];	
	$type=$_GET["type"];
	$filename="/var/log/apache2/$servername/nginx.access.log";
	if($type==2){
		$filename="/var/log/apache2/$servername/nginx.error.log";
	}
	$search=$_GET["search"];
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
function mysqldb_restart(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$php5 /usr/share/artica-postfix/exec.nginx-db.php --init");
	shell_exec("$nohup /etc/init.d/nginx-db restart >/dev/null 2>&1");
}