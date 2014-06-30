<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["import"])){import();exit;}
if(isset($_GET["status-infos"])){status_info();exit;}
if(isset($_GET["delete-cache"])){delete_cache();exit;}
if(isset($_GET["sync-freewebs"])){sync_freewebs();exit;}
if(isset($_GET["www-events"])){www_events();exit;}
if(isset($_GET["mysqldb-restart"])){mysqldb_restart();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["conf-view"])){conf_view();exit;}
if(isset($_GET["replic-conf"])){conf_save();exit;}
if(isset($_GET["uncompress-nginx"])){uncompress_nginx();exit;}
if(isset($_GET["reconfigure-single"])){reconfigure_single();exit;}
if(isset($_GET["purge-cache"])){purge_cache();exit;}
if(isset($_GET["import-bulk"])){import_bulk();exit;}


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

function conf_save(){
	$unix=new unix();
	$nginx=$unix->find_program("nginx");
	$servername=$_GET["replic-conf"];
	$filename=$_GET["dest"];
	$nginxconfPath="/usr/share/artica-postfix/ressources/logs/web/$servername";
	
	writelogs_framework("servername=$servername",__FUNCTION__,__FILE__,__LINE__);
	writelogs_framework("nginxconf=$nginxconfPath",__FUNCTION__,__FILE__,__LINE__);
	writelogs_framework("filename=$filename",__FUNCTION__,__FILE__,__LINE__);
	
	if(!is_file($nginxconfPath)){
		writelogs_framework("nginxconfPath=$nginxconfPath failed",__FUNCTION__,__FILE__,__LINE__);
		echo "<articadatascgi>".base64_encode("$nginxconfPath no such file\n")."</articadatascgi>";
		return;
	}
	
	$destinationPath="/etc/nginx/sites-enabled/$filename";
	
	if(!is_file($destinationPath)){
		echo "<articadatascgi>".base64_encode("$destinationPath no such file\n")."</articadatascgi>";
		return;
	}
	$OK=false;
	
	$tmp=$unix->TEMP_DIR();
	$tempfile="$tmp/".basename($filename);
	@copy($destinationPath, $tempfile);
	@copy($nginxconfPath, $destinationPath);
	
	$results[]=$destinationPath;
	
	writelogs_framework("$nginx -c /etc/nginx/nginx.conf -t 2>&1",__FUNCTION__,__FILE__,__LINE__);
	exec("$nginx -c /etc/nginx/nginx.conf -t 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		writelogs_framework("$line",__FUNCTION__,__FILE__,__LINE__);
		if(preg_match("#test is successful#", $line)){$OK=true;}
	}
	
	if(!$OK){
		@copy($tempfile, $destinationPath);
		@unlink($tempfile);
		@unlink($nginxconfPath);
		writelogs_framework("FAILED",__FUNCTION__,__FILE__,__LINE__);
		echo "<articadatascgi>".base64_encode(@implode("\n", $results))."</articadatascgi>";
		return;
	}
	@unlink($tempfile);
	@unlink($nginxconfPath);
	writelogs_framework("SUCCESS",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>".base64_encode("SUCCESS\n******************\n".@implode("\n", $results))."</articadatascgi>";
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.nginx.php --force-restart >/dev/null 2>&1 &");
	
}

function conf_view(){
	$sitename=$_GET["conf-view"];
	writelogs_framework("conf_view $sitename",__FUNCTION__,__FILE__,__LINE__);
	foreach (glob("/etc/nginx/sites-enabled/freewebs-$sitename*") as $filename) {
		writelogs_framework("Copy $filename",__FUNCTION__,__FILE__,__LINE__);
		@copy($filename, "/usr/share/artica-postfix/ressources/logs/".basename($filename));
		$array["FILENAME"]=basename($filename);
		echo "<articadatascgi>".base64_encode(serialize($array))."</articadatascgi>";
		return;
	}
	
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
	
	if(isset($_GET["enabled"])){
		if($_GET["enabled"]==0){
			$cmd="$nohup /etc/init.d/apache2 restart >/dev/null 2>&1 &";
			shell_exec($cmd);
			$cmd="$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &";
			shell_exec($cmd);		
			$cmd="$nohup /etc/init.d/artica-webconsole restart >/dev/null 2>&1 &";
			shell_exec($cmd);
			$cmd="$nohup /etc/init.d/monit restart >/dev/null 2>&1 &";
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
function uncompress_nginx(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$tar=$unix->find_program("tar");
	$filename=$_GET["uncompress-nginx"];
	$nohup=$unix->find_program("nohup");
	$FilePath="/usr/share/artica-postfix/ressources/conf/upload/$filename";

	if(!is_file($FilePath)){
		echo "<articadatascgi>".base64_encode(serialize(array("R"=>false,"T"=>"{failed}: $FilePath no such file")))."</articadatascgi>";
	}
	
	$cmd="$tar -xf $FilePath -C /";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	$VERSION=nginx_version();
	shell_exec("$nohup /etc/init.d/nginx restart >/dev/null 2>&1 &");
	echo "<articadatascgi>".base64_encode(serialize(array("R"=>true,"T"=>"{success}: v.$VERSION")))."</articadatascgi>";
}

function reconfigure_single(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$servername=$_GET["servername"];
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/nginx-$servername.log";
	@unlink($cachefile);
	@file_put_contents($cachefile, "Starting......: ".date("H:i:s")." [INIT]: Nginx, **** RECONFIGURING $servername ****\n");
	@chmod($cachefile, 0777);
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.nginx.php --reconfigure \"$servername\" >>$cachefile 2>&1 &");
}


function nginx_version(){
	$unix=new unix();
	$nginx=$unix->find_program("nginx");
	if(!is_file($nginx)){return;}
	$php5=$unix->LOCATE_PHP5_BIN();
	exec("$nginx -V 2>&1",$results);

	while (list ($key, $value) = each ($results) ){
		if(preg_match("#nginx version: .*?\/([0-9\.]+)#", $value,$re)){return $re[1];}
		if(preg_match("#TLS SNI support enabled#", $value,$re)){$ARRAY["DEF"]["TLS"]=true;continue;}
	}
}
function purge_cache(){
	$unix=new unix();
	$ID=$_GET["purge-cache"];
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.nginx.php --purge-cache $ID >/dev/null 2>&1 &");
}

function import(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$php5 /usr/share/artica-postfix/exec.nginx.php --import-file >/usr/share/artica-postfix/ressources/logs/web/nginx.import.results 2>&1");	
}

function import_bulk(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$php5 /usr/share/artica-postfix/exec.nginx.php --import-bulk >/usr/share/artica-postfix/ressources/logs/web/nginx.import-bulk.results 2>&1");
}

