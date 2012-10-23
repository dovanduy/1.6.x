<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	if(isset($_GET["VERBOSE"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	if(isset($_POST["VERBOSE"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.blackboxes.inc');
	include_once('ressources/class.mysql.squid.builder.php');		
	if(isset($_FILES["SETTINGS_INC"])){SETTINGS_INC();exit;}
	if(isset($_POST["SQUIDCONF"])){SQUIDCONF();exit;}
	if(isset($_POST["REGISTER"])){REGISTER();exit;}
	if(isset($_POST["LATEST_ARTICA_VERSION"])){LATEST_ARTICA_VERSION();exit;}
	if(isset($_POST["LATEST_SQUID_VERSION"])){LATEST_SQUID_VERSION();exit;}
	if(isset($_POST["orderid"])){ORDER_DELETE();exit;}
	if(isset($_POST["PING-ORDERS"])){PARSE_ORDERS();exit;}
	
	
	
	print_r($_POST);
	
	
	
function SETTINGS_INC(){
	$ME=$_SERVER["SERVER_ADDR"];
	$q=new mysql_blackbox();
	reset($_FILES['SETTINGS_INC']);
	$error=$_FILES['SETTINGS_INC']['error'];
	$tmp_file = $_FILES['SETTINGS_INC']['tmp_name'];
	$hostname=$_POST["HOSTNAME"];
	$nodeid=$_POST["nodeid"];
	$hostid=$_POST["hostid"];
	$content_dir=dirname(__FILE__)."/ressources/conf/upload/$hostname-$nodeid";
	if(!is_dir($content_dir)){mkdir($content_dir,0755,true);}
	if( !is_uploaded_file($tmp_file) ){while (list ($num, $val) = each ($_FILES['SETTINGS_INC']) ){$error[]="$num:$val";}writelogs("ERROR:: ".@implode("\n", $error),__FUNCTION__,__FILE__,__LINE__);exit();}
	 
	$type_file = $_FILES['SETTINGS_INC']['type'];
	$name_file = $_FILES['SETTINGS_INC']['name'];
	writelogs("$hostname ($nodeid):: receive name_file=$name_file; type_file=$type_file",__FUNCTION__,__FILE__,__LINE__);
	if(file_exists( $content_dir . "/" .$name_file)){@unlink( $content_dir . "/" .$name_file);}
 	if( !move_uploaded_file($tmp_file, $content_dir . "/" .$name_file) ){writelogs("$hostname ($nodeid) Error Unable to Move File : ". $content_dir . "/" .$name_file,__FUNCTION__,__FILE__,__LINE__);exit();}
    $moved_file=$content_dir . "/" .$name_file;	
    zuncompress($moved_file,"$moved_file.txt");
    
    $curlparms=unserialize(base64_decode(@file_get_contents("$moved_file.txt")));
    @unlink("$moved_file.txt");
	if(!is_array($curlparms)){
		writelogs("blackboxes::$hostname ($nodeid) Error $moved_file.txt : Not an array...",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	if(isset($curlparms["VERBOSE"])){
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
	if($ligne["hostid"]==null){
		$sql="INSERT INTO nodes (`hostname`,`ipaddress`,`port`,`hostid`,`BigArtica`,`ssl`) 
		VALUES ('$hostname','{$_SERVER["REMOTE_ADDR"]}','$MYSSLPORT','$hostid','$ISARTICA','$ssl')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo "<ERROR>$ME: Statisics appliance: $q->mysql_error:\n$sql\n line:".__LINE__."</ERROR>\n";return;}	
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
	$back->VERSION=$curlparms["VERSION"];
	$back->hostname=$hostname;
	if($GLOBALS["VERBOSE"]){echo "Statistics Appliance:: $hostname ($nodeid) v.{$curlparms["VERSION"]}\n";}
	writelogs("$hostname ($nodeid) v.{$curlparms["VERSION"]}",__FUNCTION__,__FILE__,__LINE__);
	$back->SaveSettingsInc($settings,$perfs,$softs,$prodstatus,$curlparms["ISARTICA"]);
	$back->SaveDisks($curlparms["disks_list"]);
	
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
	if(isset($curlparms["CACHE_LOGS"])){$back->squid_save_cachelogs($curlparms["CACHE_LOGS"]);}	
	if(isset($curlparms["ETC_SQUID_CONF"])){$back->squid_save_etcconf($curlparms["ETC_SQUID_CONF"]);}
	if(isset($curlparms["UFDBCLIENT_LOGS"])){$back->squid_ufdbclientlog($curlparms["UFDBCLIENT_LOGS"]);}
	
	
	
	writelogs("blackboxes::$hostname ($nodeid):: Full squid version {$curlparms["SQUIDVER"]}",__FUNCTION__,__FILE__,__LINE__);
	
	if(isset($curlparms["SQUIDVER"])){$back->squid_save_squidver($curlparms["SQUIDVER"]);}
	if(isset($curlparms["ARCH"])){$back->SetArch($curlparms["ARCH"]);}
	if(isset($curlparms["PARMS"])){$back->DaemonsSettings($curlparms["PARMS"]);}		
	
	
	writelogs("blackboxes::$hostname ($nodeid): check orders...",__FUNCTION__,__FILE__,__LINE__);
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
	writelogs("DEL ORDER \"{$_POST["orderid"]}\"",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
	$q=new mysql_blackbox();
	if(!$q->TABLE_EXISTS("poolorders")){$q->CheckTables();}
	$sql="DELETE FROM poolorders WHERE orderid='{$_POST["orderid"]}'";
	$q->QUERY_SQL($sql);
	_udfbguard_admin_events("orderid {$_POST["roder_text"]} ({$_POST["orderid"]}) as been executed by remote host $blk->hostname", __FUNCTION__, __FILE__, __LINE__, "communicate");	
	if(!$q->ok){writelogs($q->mysql_error,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}	
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