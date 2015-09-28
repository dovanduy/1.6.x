<?php
//if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}

$GLOBALS["KAV4PROXY_NOSESSION"]=true;

include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.status.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.artica.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.influx.inc");
//server-syncronize-64.png

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
events("command-lines=".implode(" ;",$argv),__FUNCTION__,__FILE__,__LINE__);

	$action=$argv[1];
	$type=$argv[2];
	$InfectedPath=$argv[3];
	$ComputerName=$argv[4];
	$VirusName=$argv[5];
	$TaskName="HTTP Scan";
	$unix=new unix();
	$zmd5=md5(implode("-",$argv));
	
	$sql="INSERT INTO antivirus_events (zDate,TaskName,VirusName,InfectedPath,ComputerName,zmd5)
	VALUES(NOW(),'$TaskName','$VirusName','$InfectedPath','$ComputerName','$zmd5')";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){events($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);}
	events("InfectedPath=$InfectedPath",__FUNCTION__,__FILE__,__LINE__);
	
	
	$URLAR=parse_url($InfectedPath);
	$InfectedFileName=basename($URLAR["path"]);
	if(isset($URLAR["host"])){$sitename=$URLAR["host"];}
	if($sitename==null){if(preg_match("#^(?:[^/]+://)?([^/:]+)#",$InfectedPath,$re)){$sitename=$re[1];}}
	

	if($sitename<>null){
		$www=$sitename;
		if(preg_match("#(.+?):[0-9]+#", $www,$re)){$www=$re[1];}
		if(strpos($www,"/")>0){$tb=explode("/",$www);$www=$tb[0];}
		if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}	
		
		
		if($unix->isIPAddress($ComputerName)){
			$ipaddr=$ComputerName;
			$ComputerName=$unix->IpToHostname($ipaddr);
		}else{
			$ipaddr=gethostbyname($ComputerName);
		}
		
		if(trim($InfectedFileName)==null){$InfectedFileName=$InfectedPath;}
		$MAC=$unix->IpToMac($ipaddr);
		$public_ip=$unix->IpToHostname($www);

		$user=$ipaddr;
		
		$ipaddr=gethostbyaddr($ipaddr);
		$time=time();
		$q=new influx();
		$line="$time:::$user:::Infected:::Kaspersky-Antivirus:::$public_ip:::Security issue:::THREAT $VirusName DETECTED:::$Clienthostname:::$www:::$local_ip";
		$q->insert_ufdb($line);

		
		
		
}else{
		events("$InfectedPath -> no match",__FUNCTION__,__FILE__,__LINE__);
}

$sock=new sockets();
$sock->getFrameWork("system.php?parse-blocked=yes");
$SquidAutoblock=$sock->GET_INFO("SquidAutoblock");
events("SquidAutoblock=$SquidAutoblock",__FUNCTION__,__FILE__,__LINE__);
if($sock->GET_INFO("SquidAutoblock")==1){
	$InfectedPath=str_replace(basename($InfectedPath),"",$InfectedPath);
	$sql="INSERT INTO squid_block(uri,task_type,zDate)
	VALUES('$InfectedPath','autoblock $VirusName',NOW());";
	$q->QUERY_SQL($sql,"artica_backup");
	$sock->getFrameWork("cmd.php?squidnewbee=yes");
}


function events($text,$function,$file,$line){
	writelogs($text,$function,$file,$line);
	$pid=@getmypid();
	$date=@date("H:i:s");
	$logFile="/var/log/kaspersky/kav4proxy/kav4proxy.threats.log";
	$size=@filesize($logFile);
	if($size>1000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$date [$pid]:: ".basename(__FILE__)."::$line:: $text\n");
	@fclose($f);
}



?>