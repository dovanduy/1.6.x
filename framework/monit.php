<?php
if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["status"])){status_info();exit;}
if(isset($_GET["chock-status"])){chock_status();exit;}
if(isset($_GET["monit-status"])){status();exit;}
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
	$php5=$unix->LOCATE_PHP5_BIN();
	exec("$php5 /usr/share/artica-postfix/exec.status.php --monit --nowachdog 2>&1",$results);
	echo "<articadatascgi>".base64_encode(@implode("\n", $results))."</articadatascgi>";

}
function sync_freewebs(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php /usr/share/artica-postfix/exec.freeweb.php --sync-squid");
	
}
function status(){
	$unix=new unix();
	$cache_file="/usr/share/artica-postfix/ressources/logs/web/monit.status.all";
	$array=unserialize(@file_get_contents($cache_file));
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
}
function chock_status(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	writelogs_framework("nohup = $nohup",__FUNCTION__,__FILE__,__LINE__);
	$nice=$unix->EXEC_NICE();
	writelogs_framework("nice = $nice",__FUNCTION__,__FILE__,__LINE__);
	$php5=$unix->LOCATE_PHP5_BIN();
	$cache_file="/usr/share/artica-postfix/ressources/logs/web/monit.status.all";
	if(is_file($cache_file)){writelogs_framework("$cache_file exists",__FUNCTION__,__FILE__,__LINE__);@chmod($cache_file,0755);}else{
		writelogs_framework("$cache_file does not exists",__FUNCTION__,__FILE__,__LINE__);
	}
	$cmd="{$nohup} $nice $php5 /usr/share/artica-postfix/exec.monit.php --status >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}


function restart(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");

	$cmd="$nohup /etc/init.d/nginx restart >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
	if(isset($_GET["enabled"])){
		if($_GET["enabled"]==0){
			$cmd="$nohup /etc/init.d/apache2 restart >/dev/null 2>&1 &";
			shell_exec($cmd);
			$cmd="$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &";
			shell_exec($cmd);		
			$cmd="$nohup /etc/init.d/artica-webconsole restart >/dev/null 2>&1 &";
			shell_exec($cmd);				
		}
	}
	
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