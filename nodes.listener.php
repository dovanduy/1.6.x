<?php

	
	/*$GLOBALS["VERBOSE"]=true;
	ini_set('html_errors',0);
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',$_SERVER["SERVER_ADDR"].":");
	ini_set('error_append_string',"");	
	*/
	if(isset($_REQUEST["VERBOSE"])){
		echo "STATISTICS APPLIANCE -> VERBOSE MODE\n";
		$GLOBALS["VERBOSE"]=true;
		ini_set('html_errors',0);
		ini_set('display_errors', 1);
		ini_set('error_reporting', E_ALL);
		ini_set('error_prepend_string',$_SERVER["SERVER_ADDR"].":");
		ini_set('error_append_string',"");
	}
	
	
	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.blackboxes.inc');
	include_once('ressources/class.mysql.squid.builder.php');		
	include_once('ressources/class.mysql.dump.inc');	
	
	
	writelogs("Request from " .$_SERVER["REMOTE_ADDR"],__FILE__,__FUNCTION__,__LINE__);
	
	if(isset($_GET["squid-table"])){export_squid_table();exit;}
	if(isset($_FILES["SETTINGS_INC"])){SETTINGS_INC();exit;}
	if(isset($_POST["DNS_LINKER"])){DNS_LINKER();exit;}
	if(isset($_POST["SQUIDCONF"])){SQUIDCONF();exit;}
	if(isset($_POST["REGISTER"])){REGISTER();exit;}
	if(isset($_POST["LATEST_ARTICA_VERSION"])){LATEST_ARTICA_VERSION();exit;}
	if(isset($_POST["LATEST_SQUID_VERSION"])){LATEST_SQUID_VERSION();exit;}
	if(isset($_POST["orderid"])){ORDER_DELETE();exit;}
	if(isset($_POST["PING-ORDERS"])){PARSE_ORDERS();exit;}
	
	while (list ($num, $val) = each ($_FILES['DNS_LINKER']) ){$error[]="\$_FILES['DNS_LINKER'][$num]:$val";}
	while (list ($num, $val) = each ($_REQUEST) ){$error[]="\$_REQUEST[$num]:$val";}	
	writelogs("Unable to understand ".@implode(",", $error),__FILE__,__FUNCTION__,__LINE__);
	
	
	
function zWriteToSyslog($text){
		if(!function_exists("syslog")){return;}
		$LOG_SEV=LOG_INFO;
		openlog("stats-appliance", LOG_PID , LOG_SYSLOG);
		syslog($LOG_SEV, $text);
		closelog();
	
	}	
	
function DNS_LINKER(){
	include_once("ressources/class.pdns.inc");
	$ME=$_SERVER["SERVER_ADDR"];
	
	$content_dir=dirname(__FILE__)."/ressources/conf/upload";
	writelogs("DNS_LINKER:: Request from " .$_SERVER["REMOTE_ADDR"]." tmp_file=$tmp_file",__FILE__,__FUNCTION__,__LINE__);
	
	
	
	writelogs("DNS_LINKER:: ->LDAP()",__FILE__,__FUNCTION__,__LINE__);
	
	$ldap=new clladp();
	if(preg_match("#^(.+?):(.+)#", $_POST["CREDS"],$re)){
		$SuperAdmin=$re[1];
		$SuperAdminPass=$re[2];
	}
	
	if($SuperAdmin<>$ldap->ldap_admin){
		writelogs("DNS_LINKER:: Invalid credential...",__FILE__,__FUNCTION__,__LINE__);
		header_status(500);
		echo "Invalid credential...\n";die("Invalid credential...");
	}
	if(md5($ldap->ldap_password)<>$SuperAdminPass){
		writelogs("DNS_LINKER:: Invalid credential...",__FILE__,__FUNCTION__,__LINE__);
		header_status(500);
		echo "Invalid credential...\n";die("Invalid credential...");
	}
	
	$TFILE=tempnam($content_dir,"dns-linker-");
	
	@file_put_contents($TFILE, base64_decode($_POST["DNS_LINKER"]));
	
	writelogs("DNS_LINKER:: zuncompress() $TFILE",__FILE__,__FUNCTION__,__LINE__);
	
	zuncompress($TFILE,"$TFILE.txt");
	@unlink($TFILE);
	$filesize=@filesize("$TFILE.txt");
	echo "$TFILE.txt -> $filesize bytes\n";
	
	$curlparms=unserialize(base64_decode(@file_get_contents("$TFILE.txt")));
	writelogs("DNS_LINKER:: Loading() $TFILE.txt -> ( ".count($curlparms)." items )",__FILE__,__FUNCTION__,__LINE__);
	
	@unlink("$TFILE.txt");
	
	
	if(!is_array($curlparms)){
		writelogs("DNS_LINKER:: Loading() curlparms no such array",__FILE__,__FUNCTION__,__LINE__);
		header_status(500);
		die();
	}
	
	$zdate=time();
	$sql="SELECT name,domain_id FROM records WHERE `content`='{$curlparms["listen_addr"]}'";
	$hostname=$curlparms["hostname"];
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"powerdns"));
	if($ligne["name"]==null){
		$tr=explode(".",$hostname);
		$netbiosname=$tr[0];
		$dnsname=str_replace("$netbiosname.", "", $hostname);
		$dns=new pdns($dnsname);
		$dns->EditIPName($netbiosname, $curlparms["listen_addr"], "A");
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"powerdns"));
	}
	if($ligne["name"]==null){
		writelogs("DNS_LINKER:: Error, unable to get name",__FILE__,__FUNCTION__,__LINE__);
		header_status(500);
		die();		
	}
	
	$domain_id=$ligne["domain_id"];
	$hostname_sql=$ligne["name"];
	
	while (list ($name, $val) = each ($curlparms["FREEWEBS_SRV"])){
		if($name==$hostname_sql){continue;}
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT name FROM records WHERE `name`='$name' AND `type`='CNAME'","powerdns"));
		writelogs("DNS_LINKER::$hostname_sql:: $name QUERY = `{$ligne["name"]}`",__FILE__,__FUNCTION__,__LINE__);
		if($ligne["name"]<>null){continue;}
		writelogs("DNS_LINKER:: $name ADD {$curlparms["listen_addr"]}",__FILE__,__FUNCTION__,__LINE__);
		$q->QUERY_SQL("INSERT INTO records (`domain_id`,`name`,`type`,`content`,`ttl`,`prio`,`change_date`)
			VALUES($domain_id,'$name','CNAME','$hostname_sql','86400','0','$zdate')","powerdns");
			header_status(500);
			if(!$q->ok){echo $q->mysql_error."\n";}
			
	}
	header_status(200);
	die();
	
	
}

function header_status($statusCode) {
	static $status_codes = null;

	if ($status_codes === null) {
		$status_codes = array (
				100 => 'Continue',
				101 => 'Switching Protocols',
				102 => 'Processing',
				200 => 'OK',
				201 => 'Created',
				202 => 'Accepted',
				203 => 'Non-Authoritative Information',
				204 => 'No Content',
				205 => 'Reset Content',
				206 => 'Partial Content',
				207 => 'Multi-Status',
				300 => 'Multiple Choices',
				301 => 'Moved Permanently',
				302 => 'Found',
				303 => 'See Other',
				304 => 'Not Modified',
				305 => 'Use Proxy',
				307 => 'Temporary Redirect',
				400 => 'Bad Request',
				401 => 'Unauthorized',
				402 => 'Payment Required',
				403 => 'Forbidden',
				404 => 'Not Found',
				405 => 'Method Not Allowed',
				406 => 'Not Acceptable',
				407 => 'Proxy Authentication Required',
				408 => 'Request Timeout',
				409 => 'Conflict',
				410 => 'Gone',
				411 => 'Length Required',
				412 => 'Precondition Failed',
				413 => 'Request Entity Too Large',
				414 => 'Request-URI Too Long',
				415 => 'Unsupported Media Type',
				416 => 'Requested Range Not Satisfiable',
				417 => 'Expectation Failed',
				422 => 'Unprocessable Entity',
				423 => 'Locked',
				424 => 'Failed Dependency',
				426 => 'Upgrade Required',
				500 => 'Internal Server Error',
				501 => 'Not Implemented',
				502 => 'Bad Gateway',
				503 => 'Service Unavailable',
				504 => 'Gateway Timeout',
				505 => 'HTTP Version Not Supported',
				506 => 'Variant Also Negotiates',
				507 => 'Insufficient Storage',
				509 => 'Bandwidth Limit Exceeded',
				510 => 'Not Extended'
		);
	}

	if ($status_codes[$statusCode] !== null) {
		$status_string = $statusCode . ' ' . $status_codes[$statusCode];
		header($_SERVER['SERVER_PROTOCOL'] . ' ' . $status_string, true, $statusCode);
	}
}

function export_squid_table(){
	$workdir=dirname(__FILE__)."/ressources/squid-export";
	$table=$_GET["squid-table"];
	$q=new mysql_squid_builder();
	$q->BD_CONNECT();
	if(is_file("$workdir/$table.gz")){@unlink("$workdir/$table.gz");}
	$dump=new phpMyDumper("squidlogs",$q->mysql_connection,"$workdir/$table.gz",true,$table);
	$dump->doDump();
	$sock=new sockets();
	$content_type=base64_decode($sock->getFrameWork("cmd.php?mime-type=".base64_encode("$workdir/$table.gz")));
	$fsize = filesize("$workdir/$table.gz");
	
	
	
	if($GLOBALS["VERBOSE"]){
		echo "Content-type: $content_type<br>\nfilesize:$fsize<br>\n";
		
		return;}
	
	header('Content-type: '.$content_type);
	
	header('Content-Transfer-Encoding: binary');
	header("Content-Disposition: attachment; filename=\"$table.gz\"");
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé

	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	readfile("$workdir/$table.gz");	
	
	
	
}
	
function SETTINGS_INC(){
	$ME=$_SERVER["SERVER_ADDR"];
	$q=new mysql_blackbox();
	$sock=new sockets();
	reset($_FILES['SETTINGS_INC']);
	$error=$_FILES['SETTINGS_INC']['error'];
	$tmp_file = $_FILES['SETTINGS_INC']['tmp_name'];
	$hostname=$_POST["HOSTNAME"];
	$nodeid=$_POST["nodeid"];
	$hostid=$_POST["hostid"];
	
	zWriteToSyslog("($hostname): Receive $nodeid/$hostid");
	
	$content_dir=dirname(__FILE__)."/ressources/conf/upload/$hostname-$nodeid";
	
	if(!is_dir($content_dir)){mkdir($content_dir,0755,true);}
	if( !is_uploaded_file($tmp_file) ){while (list ($num, $val) = each ($_FILES['DNS_LINKER']) ){$error[]="$num:$val";}writelogs("ERROR:: ".@implode("\n", $error),__FUNCTION__,__FILE__,__LINE__);exit();}
	
	
	
	$sql="SELECT hostid,nodeid FROM nodes WHERE `hostid`='$hostid'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if($GLOBALS["VERBOSE"]){
		echo "SELECT hostid,nodeid FROM nodes WHERE `hostid`='$hostid' -> {$ligne["hostid"]}\n";
	}
		
	
	
	 
	$type_file = $_FILES['SETTINGS_INC']['type'];
	$name_file = $_FILES['SETTINGS_INC']['name'];
	writelogs("$hostname ($nodeid):: receive name_file=$name_file; type_file=$type_file",__FUNCTION__,__FILE__,__LINE__);
	if(file_exists( $content_dir . "/" .$name_file)){@unlink( $content_dir . "/" .$name_file);}
	

	
	
 	if( !move_uploaded_file($tmp_file, $content_dir . "/" .$name_file) ){
 		$sock=new sockets();
 		$sock->getFrameWork("services.php?folders-security=yes&force=true");
 		if( !move_uploaded_file($tmp_file, $content_dir . "/" .$name_file) ){
 			writelogs("$hostname ($nodeid) Error Unable to Move File : ". $content_dir . "/" .$name_file,__FUNCTION__,__FILE__,__LINE__);
 			return;
 		}
 	}
    $moved_file=$content_dir . "/" .$name_file;	
    if(!is_file($moved_file)){
    	writelogs("$hostname ($nodeid) $moved_file no such file",__FUNCTION__,__FILE__,__LINE__);
    	return;
    }
    $filesize=@filesize($moved_file);
    zWriteToSyslog("($hostname): Uncompress $moved_file (".round($filesize/1024)." Kb)");
    zuncompress($moved_file,"$moved_file.txt");
    
    $curlparms=unserialize(base64_decode(@file_get_contents("$moved_file.txt")));
    @unlink("$moved_file.txt");
	if(!is_array($curlparms)){
		writelogs("blackboxes::$hostname ($nodeid) Error $moved_file.txt : Not an array...",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	if(isset($curlparms["VERBOSE"])){
		echo "STATISTICS APPLIANCE -> VERBOSE MODE\n";
		$GLOBALS["VERBOSE"]=true;
		ini_set('html_errors',0);
		ini_set('display_errors', 1);
		ini_set('error_reporting', E_ALL);
		ini_set('error_prepend_string','');
		ini_set('error_append_string','');
	}
	
	$MYSSLPORT=$curlparms["ArticaHttpsPort"];
	$ISARTICA=$curlparms["ISARTICA"];
	$ssl=$curlparms["usessl"];
	$sql="SELECT hostid,nodeid FROM nodes WHERE `hostid`='$hostid'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if($GLOBALS["VERBOSE"]){
		echo "SELECT hostid,nodeid FROM nodes WHERE `hostid`='$hostid' -> {$ligne["hostid"]}\n";
	}
	
	if(!$q->TABLE_EXISTS("nodes")){$q->CheckTables();}
	
	if($ligne["hostid"]==null){
		$sql="INSERT INTO nodes (`hostname`,`ipaddress`,`port`,`hostid`,`BigArtica`,`ssl`) 
		VALUES ('$hostname','{$_SERVER["REMOTE_ADDR"]}','$MYSSLPORT','$hostid','$ISARTICA','$ssl')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo "<ERROR>$ME: Statistics appliance: $q->mysql_error:\n$sql\n line:".__LINE__."</ERROR>\n";return;}	
		$sql="SELECT hostid,nodeid FROM nodes WHERE `hostid`='$hostid'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));	
			
	}
	$nodeid=$ligne["nodeid"];
	if($GLOBALS["VERBOSE"]){echo "Output nodeid:$nodeid\n";}
	echo "\n<NODEID>$nodeid</NODEID>\n";
	
	
	$settings=$curlparms["SETTINGS_INC"];
	$softs=$curlparms["softwares"];
	$perfs=$curlparms["perfs"];
	$prodstatus=$curlparms["prodstatus"];
	$back=new blackboxes($nodeid);
	zWriteToSyslog("($hostname): Artica version v.{$curlparms["VERSION"]}");
	$back->VERSION=$curlparms["VERSION"];
	$back->hostname=$hostname;
	if($GLOBALS["VERBOSE"]){echo "Statistics Appliance:: $hostname ($nodeid) v.{$curlparms["VERSION"]}\n";}
	writelogs("$hostname ($nodeid) v.{$curlparms["VERSION"]}",__FUNCTION__,__FILE__,__LINE__);
	$back->SaveSettingsInc($settings,$perfs,$softs,$prodstatus,$curlparms["ISARTICA"]);
	$back->SaveDisks($curlparms["disks_list"]);
	
	if(isset($curlparms["YOREL"])){
		$mepath=dirname(__FILE__);
		$srcYourelPAth="$mepath/logs/web/$hostid/yorel.tar.gz";
		ini_set('html_errors',0);
		ini_set('display_errors', 1);
		ini_set('error_reporting', E_ALL);
		ini_set('error_prepend_string',$_SERVER["SERVER_ADDR"].":");
		ini_set('error_append_string',"");
		if(is_dir($srcYourelPAth)){
			if($GLOBALS["VERBOSE"]){echo "{$_SERVER["SERVER_ADDR"]}: $srcYourelPAth is a directory ??\n";}
			$sock->getFrameWork("services.php?chown-medir=".base64_encode($srcYourelPAth));
			rmdir($srcYourelPAth);
		}
		if(!is_dir(dirname($srcYourelPAth))){mkdir(dirname($srcYourelPAth),0755,true);}
		$sock->getFrameWork("services.php?chown-medir=".base64_encode(dirname($srcYourelPAth)));
		file_put_contents($srcYourelPAth, base64_decode($curlparms["YOREL"]));
		if(is_file($srcYourelPAth)){
			unset($curlparms["YOREL"]);
			if($GLOBALS["VERBOSE"]){echo "{$_SERVER["SERVER_ADDR"]}: $srcYourelPAth ". filesize($srcYourelPAth)." bytes\n";}
			exec("/bin/tar -xvf $srcYourelPAth -C ".dirname($srcYourelPAth)."/ 2>&1",$out);
			if($GLOBALS["VERBOSE"]){while (list ($a, $aa) = each ($out) ){echo "{$_SERVER["SERVER_ADDR"]}:$aa\n";}}
			unlink($srcYourelPAth);
			$sock->getFrameWork("services.php?chowndir=".base64_encode(dirname($srcYourelPAth)));
		}else{
			if($GLOBALS["VERBOSE"]){echo "{$_SERVER["SERVER_ADDR"]}: $srcYourelPAth no such file\n";}
		}
	}
	
	zWriteToSyslog("($hostname): Squid-Cache version {$curlparms["SQUIDVER"]}");
	writelogs("blackboxes::$hostname squid version {$curlparms["SQUIDVER"]}",__FUNCTION__,__FILE__,__LINE__);
	
	if(strlen(trim($curlparms["SQUIDVER"]))>1){
		$qSQ=new mysql_squid_builder();
		if(!$qSQ->TABLE_EXISTS("squidservers")){$q->CheckTables();}
		writelogs($_SERVER["REMOTE_ADDR"] .":port:: `$MYSSLPORT` production server....",__FUNCTION__,__FILE__,__LINE__);
		$hostname=gethostbyaddr($_SERVER["REMOTE_ADDR"]);
		$time=date('Y-m-d H:i:s');
		$sql="INSERT IGNORE INTO `squidservers` (ipaddr,hostname,port,created,udpated) VALUES ('{$_SERVER["REMOTE_ADDR"]}','$hostname','$MYSSLPORT','$time','$time')";
		$ligne=mysql_fetch_array($qSQ->QUERY_SQL("SELECT ipaddr FROM squidservers WHERE ipaddr='{$_SERVER["REMOTE_ADDR"]}'"));
		if($ligne["ipaddr"]==null){$qSQ->QUERY_SQL($sql);}else{
			$qSQ->QUERY_SQL("UPDATE `squidservers` SET udpated='$time' WHERE ipaddr='{$ligne["ipaddr"]}'");
		}	
	}
	
	if(isset($curlparms["nets"])){
		writelogs("blackboxes::$hostname ($nodeid):: -> CARDS",__FUNCTION__,__FILE__,__LINE__);
		$back->SaveNets($curlparms["nets"]);
	}else{
		writelogs("blackboxes::$hostname ($nodeid):: No network cards info sended",__FUNCTION__,__FILE__,__LINE__);
	}
	if(isset($curlparms["squid_caches_info"])){$back->squid_save_cache_infos($curlparms["squid_caches_info"]);}
	if(isset($curlparms["squid_system_info"])){$back->squid_save_system_infos($curlparms["squid_system_info"]);}
	
	if(isset($curlparms["CACHE_LOGS"])){$back->squid_save_cachelogs($curlparms["CACHE_LOGS"]);}	
	if(isset($curlparms["ETC_SQUID_CONF"])){$back->squid_save_etcconf($curlparms["ETC_SQUID_CONF"]);}
	if(isset($curlparms["UFDBCLIENT_LOGS"])){$back->squid_ufdbclientlog($curlparms["UFDBCLIENT_LOGS"]);}
	if(isset($curlparms["TOTAL_MEMORY_MB"])){$back->system_update_memory($curlparms["TOTAL_MEMORY_MB"]);}
	if(isset($curlparms["SQUID_SMP_STATUS"])){$back->system_update_smtpstatus($curlparms["SQUID_SMP_STATUS"]);}
	if(isset($curlparms["BOOSTER_SMP_STATUS"])){$back->system_update_boostersmp($curlparms["BOOSTER_SMP_STATUS"]);}
	
	
	
	
	writelogs("blackboxes::$hostname ($nodeid):: Full squid version {$curlparms["SQUIDVER"]}",__FUNCTION__,__FILE__,__LINE__);
	
	if(isset($curlparms["SQUIDVER"])){$back->squid_save_squidver($curlparms["SQUIDVER"]);}
	if(isset($curlparms["ARCH"])){$back->SetArch($curlparms["ARCH"]);}
	if(isset($curlparms["PARMS"])){$back->DaemonsSettings($curlparms["PARMS"]);}		
	
	
	writelogs("blackboxes::$hostname ($nodeid): check orders...",__FUNCTION__,__FILE__,__LINE__);
	zWriteToSyslog("($hostname): Checks Orders....");
	$back->EchoOrders();
		
	
	
}

function PARSE_ORDERS(){
	$sock=new sockets();
	$sock->getFrameWork("services.php?netagent-ping=yes");
	echo "<SUCCESS>SUCCESS</SUCCESS>";
	
}

function ORDER_DELETE(){
	$hostid=$_POST["hostid"];
	$blk=new blackboxes($hostid);
	echo "DELETING ORDER {$_POST["orderid"]}\n";
	
	writelogs("DEL ORDER \"{$_POST["orderid"]}\"",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
	$q=new mysql_blackbox();
	if(!$q->TABLE_EXISTS("poolorders")){$q->CheckTables();}
	$sql="DELETE FROM poolorders WHERE orderid='{$_POST["orderid"]}'";
	echo "$sql\n";
	$q->QUERY_SQL($sql);
	_udfbguard_admin_events("orderid {$_POST["roder_text"]} ({$_POST["orderid"]}) as been executed by remote host $blk->hostname", __FUNCTION__, __FILE__, __LINE__, "communicate");	
	if(!$q->ok){
		echo $q->mysql_error."\n";
		writelogs($q->mysql_error,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}	
}

function zuncompress($srcName, $dstName) {
	$sfp = gzopen($srcName, "rb");
	$fp = fopen($dstName, "w");
	while ($string = gzread($sfp, 4096)) {fwrite($fp, $string, strlen($string));}
	gzclose($sfp);
	fclose($fp);
} 	

function REGISTER(){
	$q=new mysql_blackbox();
	if(!isset($_POST["nets"])){die("No network sended");}
	$EncodedDatas=$_POST["nets"];
	$array=unserialize(base64_decode($EncodedDatas));
	$nodeid=$_POST["nodeid"];
	$hostid=$_POST["hostid"];
	$ISARTICA=$_POST["ISARTICA"];
	$usessl=$_POST["usessl"];
	
	if(!is_numeric($ISARTICA)){$ISARTICA=0;}
	if(!is_numeric($nodeid)){$nodeid=0;}
	if(!is_array($array)){
		echo "<ERROR>No an Array</ERROR>\n";
		writelogs("Not an array... ",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	if(count($array)==0){
		echo "<ERROR>No item sended</ERROR>\n";
		writelogs("No item... ",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	
	$sql="SELECT nodeid FROM nodes WHERE `hostid`='$hostid'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$nodeid=$ligne["nodeid"];
	if(!is_numeric($nodeid)){$nodeid=0;}
	$ME=$_SERVER["SERVER_NAME"];
	
	
	$q=new mysql_blackbox();
	$q->CheckTables();
	
	if($nodeid>0){
		writelogs("item already exists",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		
		$sql="UPDATE nodes SET hostname='{$_POST["hostname"]}',
		ipaddress='{$_SERVER["REMOTE_ADDR"]}',
		port='{$_POST["port"]}',
		hostid='$hostid' WHERE nodeid='$nodeid'";
		if($GLOBALS["VERBOSE"]){echo "$ME:$sql\n";}
		$q->QUERY_SQL($sql);
		
		
		if(preg_match("#Unknown column 'hostid'#",$q->mysql_error)){
			$q->QUERY_SQL("DROP TABLE nodes");
			$q->CheckTables();
			$sql="INSERT INTO nodes (`hostname`,`ipaddress`,`port`,`hostid`,`BigArtica`,`ssl`) 
			VALUES ('{$_POST["hostname"]}','{$_SERVER["REMOTE_ADDR"]}','{$_POST["port"]}','$hostid','$ISARTICA','$usessl')";
			$q->QUERY_SQL($sql);
			if(!$q->ok){echo "<ERROR>$ME: Statisics appliance: $q->mysql_error:\n$sql\n line:".__LINE__."</ERROR>\n";return;}
			$sql="SELECT nodeid FROM nodes WHERE `hostid`='$hostid'";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
			if(!$q->ok){echo "<ERROR>$ME: Statisics appliance: $q->mysql_error:\n$sql\n line:".__LINE__."</ERROR>\n";return;}
			$nodeid=$ligne["nodeid"];			
		}		
		
		if(!$q->ok){echo "<ERROR>$ME: Statisics appliance: $q->mysql_error:\n$sql\n line:".__LINE__."</ERROR>\n";return;}	
		echo "<SUCCESS>$nodeid</SUCCESS>";
		
	}else{
		echo "Adding new item\n...";
		
		writelogs("Adding new item",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		$sql="INSERT INTO nodes (`hostname`,`ipaddress`,`port`,`hostid`,`BigArtica`,`ssl`) 
		VALUES ('{$_POST["hostname"]}','{$_SERVER["REMOTE_ADDR"]}','{$_POST["port"]}','$hostid','$ISARTICA','$usessl')";
		$q->QUERY_SQL($sql);
		if($GLOBALS["VERBOSE"]){if(!$q->ok){echo "<ERROR>$ME: Statisics appliance: $q->mysql_error: line:".__LINE__."</ERROR>\n";}}	
		if(preg_match("#Unknown column 'hostid'#",$q->mysql_error)){
			$q->QUERY_SQL("DROP TABLE nodes");
			$q->CheckTables();
			$q->QUERY_SQL($sql);
		}
		
		
		if(!$q->ok){echo "<ERROR>$ME:Statisics appliance: $q->mysql_error: line:".__LINE__."</ERROR>\n";return;}
		$sql="SELECT nodeid FROM nodes WHERE `hostid`='$hostid'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		echo "$ME:Success adding new item in the central server\n";
		echo "<SUCCESS>{$ligne["nodeid"]}</SUCCESS>";
	}

}

function SQUIDCONF(){
	$nodeid=$_POST["nodeid"];
	$workingdir=dirname(__FILE__)."/ressources/logs/web/squid/$nodeid";
	@mkdir($workingdir,0777,true);
	@mkdir($workingdir,0777,true);
	$squid=new squidnodes($nodeid);
	$blk=new blackboxes($nodeid);
	$data=$squid->build();
	@file_put_contents("$workingdir/squid-block.acl", $GLOBALS["CLASS_SQUIDBEE"]->BuildBlockedSites());
	
	$globalConfig=base64_encode(serialize($squid->DumpDatabases()));
	$DamonsSettings=base64_encode(serialize($blk->DumpSettings()));
	
	writelogs("Writing $workingdir/squid.conf",__FUNCTION__,__FILE__,__LINE__);
	@file_put_contents("$workingdir/squid.conf", $data);
	writelogs("saving $workingdir/DaemonSettings.conf",__FUNCTION__,__FILE__,__LINE__);
	@file_put_contents("$workingdir/DaemonSettings.conf", $DamonsSettings);
	if(!is_file("$workingdir/squid.conf")){writelogs("$workingdir/squid.conf no such file",__FUNCTION__,__FILE__,__LINE__);return;}
	@file_put_contents("$workingdir/squid.db", $globalConfig);
	compress("$workingdir/squid.conf","$workingdir/squid.conf.gz");
	compress("$workingdir/squid.db","$workingdir/squid.db.gz");
	compress("$workingdir/squid-block.acl","$workingdir/squid-block.acl.gz");
	compress("$workingdir/squid-block.acl","$workingdir/squid-block.acl.gz");
	compress("$workingdir/DaemonSettings.conf","$workingdir/DaemonSettings.conf.gz");
	
}

function LATEST_ARTICA_VERSION(){
	$f=new blackboxes();
	echo "<SUCCESS>".$f->last_available_version()."</SUCCESS>";
	
}

function LATEST_SQUID_VERSION(){
	$ARCH=$_POST["ARCH"];
	$f=new blackboxes();
	if($ARCH==32){
		$ver=$f->last_available_squidx32_version();
	}
	
	if($ARCH==64){
		$ver=$f->last_available_squidx64_version();
	}
	
	if($ARCH=="i386"){
		$ver=$f->last_available_squidx32_version();
	}
	
	if($ARCH=="x64"){
		$ver=$f->last_available_squidx64_version();
	}	
	writelogs("Arch:$ARCH; Version: $ver",__FUNCTION__,__FILE__,__LINE__);
	echo "<SUCCESS>".$f->last_available_squidx64_version()."</SUCCESS>";
		return;	
	
}


function compress($source,$dest){
    writelogs("Compress $source -> $dest ",__FUNCTION__,__FILE__,__LINE__);
    $mode='wb9';
    $error=false;
    $fp_out=gzopen($dest,$mode);
    if(!$fp_out){
    	writelogs("Failed to open $dest",__FUNCTION__,__FILE__,__LINE__);
    	return;
    }
    $fp_in=fopen($source,'rb');
    if(!$fp_in){
    	writelogs("Failed to open $source",__FUNCTION__,__FILE__,__LINE__);
    	return;
    }
    
    while(!feof($fp_in)){
    	gzwrite($fp_out,fread($fp_in,1024*512));
    }
    fclose($fp_in);
    gzclose($fp_out);
	return true;
}