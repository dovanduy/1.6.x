<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["FORCE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.kav4proxy.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');

if($argv[1]=="--reload"){BuilAndReload();die();}
if($argv[1]=="--umount"){umountfs();die();}
if($argv[1]=="--license"){license_infos();die();}
if($argv[1]=="--templates"){templates();die();}



build();
function build(){
	$kav=new Kav4Proxy();
	$conf=$kav->build_config();
	echo "Starting......: ".date("H:i:s")." Kav4proxy building configuration done\n";
	@file_put_contents("/etc/opt/kaspersky/kav4proxy.conf",$conf);
	shell_exec("/bin/chown -R kluser /etc/opt/kaspersky");
	shell_exec("/bin/chown -R kluser /var/log/kaspersky/kav4proxy");
	@mkdir("/tmp/Kav4proxy",0777);
	@chmod("/tmp/Kav4proxy", 0777);
	@chown("/tmp/Kav4Proxy", "kluser");
	@chgrp("/tmp/Kav4Proxy", "kluser");
	@mkdir("/var/log/artica-postfix/ufdbguard-blocks",0777,true);
	@chmod("/var/log/artica-postfix/ufdbguard-blocks", 0777);
	templates();
	}
	
	
function BuilAndReload(){
	build();
	shell_exec("/etc/init.d/kav4proxy reload");
	
}

function umountfs(){
	$unix=new unix();
	$mount=$unix->find_program("mount");
	$umount=$unix->find_program("umount");	
	$kav=new Kav4Proxy();
	if($kav->is_tmpfs_mounted()){
		echo "Starting......: ".date("H:i:s")." Kav4proxy unmounting filesystem\n";
		shell_exec("$umount -f /tmp/Kav4proxy");
		shell_exec("/bin/rm -rf /tmp/Kav4proxy");
	}
}

function license_infos($nopid=false){
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".time";
	$unix=new unix();
	if(!$nopid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			writelogs(basename(__FILE__).":Already executed pid $pid.. aborting the process",basename(__FILE__),__FILE__,__LINE__);
			return;
		}
	}
	
	@file_put_contents($pidfile, getmypid());	
	if($GLOBALS["VERBOSE"]){echo "TimeFile: $pidTime\n";}
	$TimeFile=$unix->file_time_min($pidTime);
	if(!$GLOBALS["FORCE"]){
		if($TimeFile<240){return;}
		
	}
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	
	$q=new mysql();
	
		$sql="CREATE TABLE IF NOT EXISTS `kav4proxy_license` (
				`serial` varchar(90) PRIMARY KEY,
  				`keyfile` varchar(128) NOT NULL,
				`productname` varchar(255) NOT NULL,
				`creationdate` date NOT NULL,
				`expiredate` date NOT NULL,
				`count` INT(10) NOT NULL,
			 	`lifespan` INT(5)
			 )";	
	
	$q->QUERY_SQL($sql,"artica_backup");	
	$time=$unix->file_time_min("/etc/artica-postfix/KAV4PROXY_LICENSE_INFO");
	if($GLOBALS["FORCE"]){$time=100000;}
	if($time>2880){
		shell_exec("/opt/kaspersky/kav4proxy/bin/kav4proxy-licensemanager -s -c /etc/opt/kaspersky/kav4proxy.conf >/etc/artica-postfix/KAV4PROXY_LICENSE_INFO 2>&1");
	}
	
	
	$results=explode("\n", @file_get_contents("/etc/artica-postfix/KAV4PROXY_LICENSE_INFO"));
	
	while (list ($num, $line) = each ($results) ){
		$line=trim($line);
		if($line==null){continue;}
		if(preg_match("#Key file:\s+(.*?)$#i",$line,$re)){$keyfile=$re[1];continue;}
		if(preg_match("#Install date:\s+(.*?)$#i",$line,$re)){$installdate=$re[1];continue;}
		if(preg_match("#Product name:\s+(.*?)$#i",$line,$re)){$productname=$re[1];continue;}
		if(preg_match("#Creation date:\s+(.*?)$#i",$line,$re)){$creationdate=strtotime($re[1]);continue;}
		if(preg_match("#Expiration date:\s+(.*?)$#i",$line,$re)){$expiredate=strtotime($re[1]);continue;}
		if(preg_match("#Serial:\s+(.*?)$#i",$line,$re)){
			$serial=$re[1];
			if($GLOBALS["VERBOSE"]){echo "Serial `$serial`\n";}
			continue;}
		if(preg_match("#Type:\s+(.*?)$#i",$line,$re)){$type=$re[1];continue;}
		if(preg_match("#Count:\s+(.*?)$#i",$line,$re)){$count=$re[1];continue;}
		if(preg_match("#Lifespan:\s+(.*?)$#i",$line,$re)){$lifespan=$re[1];continue;}	
		if(preg_match("#Objs:#i",$line)){
			$productname=addslashes($productname);
			$creationdate1=date('Y-m-d',$creationdate);
			$expiredate1=date('Y-m-d',$expiredate);
			$f[]="('$serial','$keyfile','$productname','$creationdate1','$expiredate1','$count','$lifespan')";
			$upd[]="UDPATE kav4proxy_license SET `lifespan`=$lifespan WHERE `serial`='$serial'";
			continue;
		}
		if($GLOBALS["VERBOSE"]){echo "No match `$line`\n";}
		
	}
	
	if(count($f)>0){
		$prefix="INSERT IGNORE INTO kav4proxy_license (`serial`,`keyfile`,`productname`,`creationdate`,`expiredate`,`count`,`lifespan`) VALUES ";
		$q->QUERY_SQL($prefix.@implode(",", $f),"artica_backup");
	}
	while (list ($num, $line) = each ($results) ){
		$q->QUERY_SQL($line,"artica_backup");
		
	}
	
	
}

function templates(){
	$kav=new Kav4Proxy();
	while (list ($templateName, $val) = each ($kav->templates_data) ){
		@file_put_contents("/opt/kaspersky/kav4proxy/share/notify/$templateName", $val);
		@chmod("/opt/kaspersky/kav4proxy/share/notify/$templateName",0755);
		@chown("/opt/kaspersky/kav4proxy/share/notify/$templateName","kluser");
	}
	
}

?>