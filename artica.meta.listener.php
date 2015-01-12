<?php
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(isset($_SERVER["REMOTE_ADDR"])){$IPADDR=$_SERVER["REMOTE_ADDR"];}
if(isset($_SERVER["HTTP_X_REAL_IP"])){$IPADDR=$_SERVER["HTTP_X_REAL_IP"];}
if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){$IPADDR=$_SERVER["HTTP_X_FORWARDED_FOR"];}
$GLOBALS["CLIENT_META_IP"]=$IPADDR;
$GLOBALS["HOSTS_PATH"]="/usr/share/artica-postfix/ressources/conf/meta/hosts";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;}

include_once('ressources/class.templates.inc');
include_once('ressources/class.mysql-meta.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.system.nics.inc');

if(isset($_GET["test-local-ident"])){TEST_LOCAL_IDENT();exit;}
if(isset($_GET["chuuid"])){CHANGE_UUID();exit;}
if(isset($_GET["registerby"])){REGISTER_BY();exit;}
if(isset($_GET["GetYourUUID"])){GetYourUUID();exit;}




if(isset($_GET["wakeup"])){wakeup();exit;}

if(!isset($_GET["ident"])){die();}
if(!ident()){writelogs_meta("Identification failed",__FUNCTION__,__FILE__,__LINE__);die();}

if(isset($_GET["snapshot"])){receive_to_download_snapshot();exit;}
if(isset($_POST["PUSH_FILE_CMD"])){receive_generic_file();exit;}
if(isset($_GET["removeorder"])){receive_removeorder();exit;}

if(isset($_GET["ping"])){receive_ping();exit;}
if(isset($_GET["policy"])){receive_policy();exit;}
if(isset($_GET["policy-remove"])){receive_policy_remove();exit;}



if(isset($_FILES["status_tgz"])){receive_status_tgz();exit;}



while (list ($num, $line) = each ($_REQUEST) ){writelogs_meta("Unable to understand query _REQUEST: $num = $line",__FUNCTION__,__FILE__,__LINE__);}
while (list ($num, $line) = each ($_FILES) ){writelogs_meta("Unable to understand query _FILES: $num = $line",__FUNCTION__,__FILE__,__LINE__); }
echo __LINE__." ** Unable to understand query\n";

function receive_status_tgz(){
	if($GLOBALS["UUID"]<>null){$uuid=$GLOBALS["UUID"];}
	
	$type_file = $_FILES['status_tgz']['type'];
	$name_file = $_FILES['status_tgz']['name'];
	$tmp_file  = $_FILES['status_tgz']['tmp_name'];
	writelogs_meta("$uuid: Receive name_file=$name_file on $tmp_file; type_file=$type_file",__FUNCTION__,__FILE__,__LINE__);
	
	@mkdir("{$GLOBALS["HOSTS_PATH"]}/uploaded/$uuid",0755,true);
	$target_file="{$GLOBALS["HOSTS_PATH"]}/uploaded/$uuid/$name_file";
	if(file_exists( $target_file)){@unlink( $target_file);}
	
	
	
	
	if( !move_uploaded_file($tmp_file, $target_file) ){
		$sock=new sockets();
		$sock->getFrameWork("services.php?folders-security=yes&force=true");
		if( !move_uploaded_file($tmp_file, $target_file) ){
			writelogs_meta("$uuid: Error Unable to Move File : $target_file",__FUNCTION__,__FILE__,__LINE__);
			return;
		}
	}
	
	$sock=new sockets();
	writelogs_meta("$uuid: -> meta-status-uuid=yes&uuid=$uuid",__FUNCTION__,__FILE__,__LINE__);
	$sock->getFrameWork("artica.php?meta-status-uuid=yes&uuid=$uuid");

}

function receive_ping(){
	echo __LINE__." ** receive_ping()\n";
	writelogs_meta("receive_ping()",__FUNCTION__,__FILE__,__LINE__);
	$sock=new sockets();
	$q=new mysql_meta();
	$uuid=null;
	if($GLOBALS["UUID"]<>null){$uuid=$GLOBALS["UUID"];}
	
	$ArticaMetaPooling=intval($sock->GET_INFO("ArticaMetaPooling"));
	if($ArticaMetaPooling==0){$ArticaMetaPooling=15;}
	$array["ArticaMetaPooling"]=$ArticaMetaPooling;
	if($uuid<>null){
		if(is_file("/usr/share/artica-postfix/ressources/conf/meta/hosts/$uuid.orders")){
				$array["ORDERS"]=@file_get_contents("/usr/share/artica-postfix/ressources/conf/meta/hosts/$uuid.orders");
		}
	}
	
	$results=$q->QUERY_SQL("SELECT zmd5 FROM `policies_storage` WHERE `uuid`='$uuid'");
	$CountOfPolicies=mysql_num_rows($results);
	if($CountOfPolicies>1){
		while ($ligne = mysql_fetch_assoc($results)) {
			$array["POLICIES"][$ligne["zmd5"]]=true;
		}
		
	}
	
	
	$data=base64_encode(serialize($array));
	
	echo "<ARTICA_META>$data</ARTICA_META>";
	
	
}

function receive_policy(){
	writelogs_meta("receive_policy()",__FUNCTION__,__FILE__,__LINE__);
	if($GLOBALS["UUID"]<>null){$uuid=$GLOBALS["UUID"];}
	if($uuid==null){
		writelogs_meta("receive_policy() failed, unable to get uuid",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	
	$policy=$_GET["policy"];
	
	
	$q=new mysql_meta();
	
	$sql="SELECT `policy_content` FROM policies_storage WHERE zmd5='$policy'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$policy_content=$ligne["policy_content"];
	if(strlen($policy_content)==0){
		writelogs_meta("receive_policy() $uuid no content",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	
	echo "<ARTICA_META>$policy_content</ARTICA_META>";
	
	
	
}
function receive_policy_remove(){
	writelogs_meta("receive_policy()",__FUNCTION__,__FILE__,__LINE__);
	if($GLOBALS["UUID"]<>null){$uuid=$GLOBALS["UUID"];}
	if($uuid==null){
		writelogs_meta("receive_policy_remove() failed, unable to get uuid",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	
	$policy=$_GET["policy-remove"];
	$q=new mysql_meta();
	$q->QUERY_SQL("DELETE FROM policies_storage WHERE zmd5='$policy'");
	if($q->ok){
		writelogs_meta("receive_policy() $uuid Deleted Policy $policy",__FUNCTION__,__FILE__,__LINE__);
		echo "<ARTICA_META> - OK - </ARTICA_META>";
	}
}

function receive_to_download_snapshot(){
	$snapshot=$_GET["snapshot"];
	$meta=new mysql_meta();
	
	$sql="SELECT `content` FROM snapshots WHERE zmd5='$snapshot'";
	$ligne=mysql_fetch_array($meta->QUERY_SQL($sql));
	if(!$meta->ok){
		writelogs_meta("MySQL error $meta->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		header('HTTP/1.0 404 Not Found', true, 404);
		die();
	}
	$bytes=strlen($ligne["content"]);
	writelogs_meta("Send {$bytes}Bytes to client",__FUNCTION__,__FILE__,__LINE__);
	
	header('Content-type: application/x-gzip');
	header('Content-Transfer-Encoding: binary');
	header("Content-Disposition: attachment; filename=\"snapshot.tar.gz\"");
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
	
	$fsize = $bytes;
	header("Content-Length: ".$fsize);
	ob_clean();
	flush();	
	echo $ligne["content"];
	
	
}


function receive_generic_file(){
	if($GLOBALS["UUID"]<>null){$uuid=$GLOBALS["UUID"];}
	if($uuid==null){die();}
	$meta=new mysql_meta();
	$sock=new sockets();
	
	writelogs_meta("$uuid: Receive PUSH_FILE_CMD: {$_POST["PUSH_FILE_CMD"]} ",__FUNCTION__,__FILE__,__LINE__);
//******************************************************************************************************************	
	if($_POST["PUSH_FILE_CMD"]=="SYSLOG"){
		$UploadedDir="{$GLOBALS["HOSTS_PATH"]}/uploaded/$uuid/syslog";
		@mkdir($UploadedDir,0755,true);
		if(!is_dir($UploadedDir)){return false;}
		
		while (list ($key, $arrayF) = each ($_FILES) ){
			$type_file = $arrayF['type'];
			$name_file = $arrayF['name'];
			$tmp_file  = $arrayF['tmp_name'];
			$name_file=$meta->uuid_to_host($uuid).".$name_file";
			writelogs_meta("$uuid: Receive name_file=$name_file on $tmp_file; type_file=$type_file",__FUNCTION__,__FILE__,__LINE__);
			
			
			
			$target_file="$UploadedDir/$name_file";
			if(file_exists( $target_file)){@unlink( $target_file);}
			if( !move_uploaded_file($tmp_file, $target_file) ){
				$sock=new sockets();
				$sock->getFrameWork("services.php?folders-security=yes&force=true");
				if( !move_uploaded_file($tmp_file, $target_file) ){
					echo "$uuid: Error Unable to Move File : $target_file\n";
					writelogs_meta("$uuid: Error Unable to Move File : $target_file",__FUNCTION__,__FILE__,__LINE__);
					return;
				}
			}
			
		}
		
		$sock->getFrameWork("artica.php?meta-syslog-uuid=yes&uuid=$uuid");
		echo "\n<ARTICAMETA>OK</ARTICAMETA>\n";
		return;
		
	}
//******************************************************************************************************************	
	if($_POST["PUSH_FILE_CMD"]=="PSAUX"){
		$UploadedDir="{$GLOBALS["HOSTS_PATH"]}/uploaded/$uuid";
		@mkdir($UploadedDir,0755,true);
		if(!is_dir($UploadedDir)){return false;}
	
		while (list ($key, $arrayF) = each ($_FILES) ){
			$type_file = $arrayF['type'];
			$name_file = $arrayF['name'];
			$tmp_file  = $arrayF['tmp_name'];

			writelogs_meta("$uuid: Receive name_file=$name_file on $tmp_file; type_file=$type_file",__FUNCTION__,__FILE__,__LINE__);
				
			$target_file="$UploadedDir/$name_file";
			if(file_exists( $target_file)){@unlink( $target_file);}
			if( !move_uploaded_file($tmp_file, $target_file) ){
				$sock=new sockets();
				$sock->getFrameWork("services.php?folders-security=yes&force=true");
				if( !move_uploaded_file($tmp_file, $target_file) ){
					echo "$uuid: Error Unable to Move File : $target_file\n";
					writelogs_meta("$uuid: Error Unable to Move File : $target_file",__FUNCTION__,__FILE__,__LINE__);
					return;
				}
				}
					
		}
	
		$sock->getFrameWork("artica.php?meta-psaux-uuid=yes&uuid=$uuid");
		echo "\n<ARTICAMETA>OK</ARTICAMETA>\n";
		return;
	
	}	
//******************************************************************************************************************	
	if($_POST["PUSH_FILE_CMD"]=="PHILESIGHT"){
		$UploadedDir="{$GLOBALS["HOSTS_PATH"]}/uploaded/$uuid";
		@mkdir($UploadedDir,0755,true);
		if(!is_dir($UploadedDir)){return false;}
		while (list ($key, $arrayF) = each ($_FILES) ){
			$type_file = $arrayF['type'];
			$name_file = $arrayF['name'];
			$tmp_file  = $arrayF['tmp_name'];
		
			writelogs_meta("$uuid: Receive name_file=$name_file on $tmp_file; type_file=$type_file",__FUNCTION__,__FILE__,__LINE__);
		
			$target_file="$UploadedDir/$name_file";
			if(file_exists( $target_file)){@unlink( $target_file);}
			if( !move_uploaded_file($tmp_file, $target_file) ){
				$sock=new sockets();
				$sock->getFrameWork("services.php?folders-security=yes&force=true");
				if( !move_uploaded_file($tmp_file, $target_file) ){
					echo "$uuid: Error Unable to Move File : $target_file\n";
					writelogs_meta("$uuid: Error Unable to Move File : $target_file",__FUNCTION__,__FILE__,__LINE__);
					return;
				}
			}
				
		}
		
		$sock->getFrameWork("artica.php?meta-philesight-uuid=yes&uuid=$uuid");
		echo "\n<ARTICAMETA>OK</ARTICAMETA>\n";
	}
	
//******************************************************************************************************************	
	if($_POST["PUSH_FILE_CMD"]=="META_EVENTS"){
		$UploadedDir="{$GLOBALS["HOSTS_PATH"]}/uploaded/$uuid/META_EVENTS";
		@mkdir($UploadedDir,0755,true);
		if(!is_dir($UploadedDir)){return false;}
		while (list ($key, $arrayF) = each ($_FILES) ){
			$type_file = $arrayF['type'];
			$name_file = $arrayF['name'];
			$tmp_file  = $arrayF['tmp_name'];
		
			writelogs_meta("$uuid: Receive name_file=$name_file on $tmp_file; type_file=$type_file",__FUNCTION__,__FILE__,__LINE__);
			$t=time();
			$target_file="$UploadedDir/$t-$name_file";
			if(file_exists( $target_file)){@unlink( $target_file);}
			if( !move_uploaded_file($tmp_file, $target_file) ){
				$sock=new sockets();
				$sock->getFrameWork("services.php?folders-security=yes&force=true");
				if( !move_uploaded_file($tmp_file, $target_file) ){
					echo "$uuid: Error Unable to Move File : $target_file\n";
					writelogs_meta("$uuid: Error Unable to Move File : $target_file",__FUNCTION__,__FILE__,__LINE__);
					return;
				}
			}
				
		}
		
		$sock->getFrameWork("artica.php?meta-metaevents-uuid=yes&uuid=$uuid");
		echo "\n<ARTICAMETA>OK</ARTICAMETA>\n";
	}
	
//******************************************************************************************************************
	if($_POST["PUSH_FILE_CMD"]=="SYS_ALERTS"){
		$UploadedDir="{$GLOBALS["HOSTS_PATH"]}/uploaded/$uuid/SYS_ALERTS";
		@mkdir($UploadedDir,0755,true);
		if(!is_dir($UploadedDir)){return false;}
		while (list ($key, $arrayF) = each ($_FILES) ){
			$type_file = $arrayF['type'];
			$name_file = $arrayF['name'];
			$tmp_file  = $arrayF['tmp_name'];
	
			writelogs_meta("$uuid: Receive name_file=$name_file on $tmp_file; type_file=$type_file",__FUNCTION__,__FILE__,__LINE__);
			$t=time();
			$target_file="$UploadedDir/$t-$name_file";
			if(file_exists( $target_file)){@unlink( $target_file);}
			if( !move_uploaded_file($tmp_file, $target_file) ){
				$sock=new sockets();
				$sock->getFrameWork("services.php?folders-security=yes&force=true");
				if( !move_uploaded_file($tmp_file, $target_file) ){
					echo "$uuid: Error Unable to Move File : $target_file\n";
					writelogs_meta("$uuid: Error Unable to Move File : $target_file",__FUNCTION__,__FILE__,__LINE__);
					return;
				}
				}
	
			}
	
			$sock->getFrameWork("artica.php?meta-sysalerts-uuid=yes&uuid=$uuid");
			echo "\n<ARTICAMETA>OK</ARTICAMETA>\n";
	}
//******************************************************************************************************************
	if($_POST["PUSH_FILE_CMD"]=="SMTP_NOTIF"){
		$UploadedDir="{$GLOBALS["HOSTS_PATH"]}/uploaded/$uuid/SMTP_NOTIF";
		@mkdir($UploadedDir,0755,true);
		if(!is_dir($UploadedDir)){return false;}
		while (list ($key, $arrayF) = each ($_FILES) ){
			$type_file = $arrayF['type'];
			$name_file = $arrayF['name'];
			$tmp_file  = $arrayF['tmp_name'];
	
			writelogs_meta("$uuid: Receive name_file=$name_file on $tmp_file; type_file=$type_file",__FUNCTION__,__FILE__,__LINE__);
			$t=time();
			$target_file="$UploadedDir/$t-$name_file";
			if(file_exists( $target_file)){@unlink( $target_file);}
			if( !move_uploaded_file($tmp_file, $target_file) ){
				$sock=new sockets();
				$sock->getFrameWork("services.php?folders-security=yes&force=true");
				if( !move_uploaded_file($tmp_file, $target_file) ){
					echo "$uuid: Error Unable to Move File : $target_file\n";
					writelogs_meta("$uuid: Error Unable to Move File : $target_file",__FUNCTION__,__FILE__,__LINE__);
					return;
				}
				}
	
			}
	
			$sock->getFrameWork("artica.php?meta-smtp-uuid=yes&uuid=$uuid");
			echo "\n<ARTICAMETA>OK</ARTICAMETA>\n";
	}
//******************************************************************************************************************
	if($_POST["PUSH_FILE_CMD"]=="SNAPSHOT"){
		$UploadedDir="{$GLOBALS["HOSTS_PATH"]}/uploaded/$uuid/SNAPSHOT";
		@mkdir($UploadedDir,0755,true);
		if(!is_dir($UploadedDir)){return false;}
		while (list ($key, $arrayF) = each ($_FILES) ){
			$type_file = $arrayF['type'];
			$name_file = $arrayF['name'];
			$tmp_file  = $arrayF['tmp_name'];
	
			writelogs_meta("$uuid: Receive name_file=$name_file on $tmp_file; type_file=$type_file",__FUNCTION__,__FILE__,__LINE__);
			$t=time();
			$target_file="$UploadedDir/$t-$name_file";
			if(file_exists( $target_file)){@unlink( $target_file);}
			if( !move_uploaded_file($tmp_file, $target_file) ){
				$sock=new sockets();
				$sock->getFrameWork("services.php?folders-security=yes&force=true");
				if( !move_uploaded_file($tmp_file, $target_file) ){
					echo "$uuid: Error Unable to Move File : $target_file\n";
					writelogs_meta("$uuid: Error Unable to Move File : $target_file",__FUNCTION__,__FILE__,__LINE__);
					return;
				}
				}
	
			}
	
			$sock->getFrameWork("artica.php?meta-snapshot-uuid=yes&uuid=$uuid");
					echo "\n<ARTICAMETA>OK</ARTICAMETA>\n";
	}
//******************************************************************************************************************
	if($_POST["PUSH_FILE_CMD"]=="ARTICA_DAEMONS"){
		$UploadedDir="{$GLOBALS["HOSTS_PATH"]}/uploaded/$uuid/ARTICA_DAEMONS";
		@mkdir($UploadedDir,0755,true);
		if(!is_dir($UploadedDir)){return false;}
		while (list ($key, $arrayF) = each ($_FILES) ){
			$type_file = $arrayF['type'];
			$name_file = $arrayF['name'];
			$tmp_file  = $arrayF['tmp_name'];
	
			writelogs_meta("$uuid: Receive name_file=$name_file on $tmp_file; type_file=$type_file",__FUNCTION__,__FILE__,__LINE__);
			$t=time();
			$target_file="$UploadedDir/$t-$name_file";
			if(file_exists( $target_file)){@unlink( $target_file);}
			if( !move_uploaded_file($tmp_file, $target_file) ){
				$sock=new sockets();
				$sock->getFrameWork("services.php?folders-security=yes&force=true");
				if( !move_uploaded_file($tmp_file, $target_file) ){
					echo "$uuid: Error Unable to Move File : $target_file\n";
					writelogs_meta("$uuid: Error Unable to Move File : $target_file",__FUNCTION__,__FILE__,__LINE__);
					return;
				}
				}
	
			}
	
			$sock->getFrameWork("artica.php?meta-articadaemons-uuid=yes&uuid=$uuid");
			echo "\n<ARTICAMETA>OK</ARTICAMETA>\n";
	}
//******************************************************************************************************************
	if($_POST["PUSH_FILE_CMD"]=="CLIENT_META_EVENTS"){
		$UploadedDir="{$GLOBALS["HOSTS_PATH"]}/uploaded/$uuid/CLIENT_META_EVENTS";
		@mkdir($UploadedDir,0755,true);
		if(!is_dir($UploadedDir)){return false;}
		while (list ($key, $arrayF) = each ($_FILES) ){
			$type_file = $arrayF['type'];
			$name_file = $arrayF['name'];
			$tmp_file  = $arrayF['tmp_name'];
	
			writelogs_meta("$uuid: Receive name_file=$name_file on $tmp_file; type_file=$type_file",__FUNCTION__,__FILE__,__LINE__);
			$t=time();
			$target_file="$UploadedDir/$t-$name_file";
			if(file_exists( $target_file)){@unlink( $target_file);}
			if( !move_uploaded_file($tmp_file, $target_file) ){
				$sock=new sockets();
				$sock->getFrameWork("services.php?folders-security=yes&force=true");
				if( !move_uploaded_file($tmp_file, $target_file) ){
					echo "$uuid: Error Unable to Move File : $target_file\n";
					writelogs_meta("$uuid: Error Unable to Move File : $target_file",__FUNCTION__,__FILE__,__LINE__);
					return;
				}
				}
	
			}
	
			$sock->getFrameWork("artica.php?meta-metaclientevents-uuid=yes&uuid=$uuid");
					echo "\n<ARTICAMETA>OK</ARTICAMETA>\n";
	}
//******************************************************************************************************************
	if($_POST["PUSH_FILE_CMD"]=="SQUID_QUOTASIZE"){
		$UploadedDir="{$GLOBALS["HOSTS_PATH"]}/uploaded/$uuid/SQUID_QUOTASIZE";
		@mkdir($UploadedDir,0755,true);
		if(!is_dir($UploadedDir)){return false;}
		while (list ($key, $arrayF) = each ($_FILES) ){
			$type_file = $arrayF['type'];
			$name_file = $arrayF['name'];
			$tmp_file  = $arrayF['tmp_name'];
	
			writelogs_meta("$uuid: Receive name_file=$name_file on $tmp_file; type_file=$type_file",__FUNCTION__,__FILE__,__LINE__);
			$t=time();
			$target_file="$UploadedDir/$t-$name_file";
			if(file_exists( $target_file)){@unlink( $target_file);}
			if( !move_uploaded_file($tmp_file, $target_file) ){
				$sock=new sockets();
				$sock->getFrameWork("services.php?folders-security=yes&force=true");
				if( !move_uploaded_file($tmp_file, $target_file) ){
					echo "$uuid: Error Unable to Move File : $target_file\n";
					writelogs_meta("$uuid: Error Unable to Move File : $target_file",__FUNCTION__,__FILE__,__LINE__);
					return;
				}
				}
	
			}
	
			$sock->getFrameWork("artica.php?meta-metaclientquotasize-uuid=yes&uuid=$uuid");
					echo "\n<ARTICAMETA>OK</ARTICAMETA>\n";
	}
//******************************************************************************************************************
	
	
	
	
	
	
	
	
	
}

function receive_removeorder(){
	echo __LINE__." ** REMOVE ORDER {$_GET["removeorder"]}\n";
	$removeorder=$_GET["removeorder"];
	$q=new mysql_meta();
	if($q->RemoveOrder($GLOBALS["UUID"],$removeorder)){
		echo "<ARTICA_META>SUCCESS</ARTICA_META>";
	}
	
}

function wakeup(){
	$sock=new sockets();
	$sock->getFrameWork("artica.php?meta-client-wakeup=yes");
	echo "<ARTICA_META>SUCCESS</ARTICA_META>";
}



function ident(){
	
	
	
	if(isset($_SERVER["REMOTE_ADDR"])){$IPADDR=$_SERVER["REMOTE_ADDR"];}
	if(isset($_SERVER["HTTP_X_REAL_IP"])){$IPADDR=$_SERVER["HTTP_X_REAL_IP"];}
	if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){$IPADDR=$_SERVER["HTTP_X_FORWARDED_FOR"];}
	$GLOBALS["CLIENT_META_IP"]=$IPADDR;
	
	
	
	$sock=new sockets();
	$ARRAY=unserialize(base64_decode($_GET["ident"]));
	$EnableArticaMetaServer=intval($sock->GET_INFO("EnableArticaMetaServer"));
	$ArticaMetaServerUsername=trim(strtolower($sock->GET_INFO("ArticaMetaServerUsername")));
	$ArticaMetaServerPassword=$sock->GET_INFO("ArticaMetaServerPassword");
	
	if($EnableArticaMetaServer==0){die();}
	$UUID=$ARRAY["uuid"];
	$GLOBALS["UUID"]=$UUID;
	$hostname=$ARRAY["hostname"];
	$version=$ARRAY["version"];
	$username=trim(strtolower($ARRAY["username"]));
	$password=$ARRAY["password"];
	if($ArticaMetaServerUsername<>$username){
		writelogs_meta("Checking identification FROM $IPADDR failed, wrong username or password",__FUNCTION__,__FILE__,__LINE__);
		die();}
	if($ArticaMetaServerPassword<>$password){
		writelogs_meta("Checking identification FROM $IPADDR failed, wrong username or password",__FUNCTION__,__FILE__,__LINE__);
		die();}
	
	$q=new mysql_meta();
	
	if(!$q->TABLE_EXISTS("metahosts")){
		if(!$q->CheckTables()){
			echo $q->mysql_error;
		}
	}
	
	if(isset($ARRAY["ARTICA_META_EVENTS"])){
		echo __LINE__." ** ARTICA_META_EVENTS:" . strlen($ARRAY["ARTICA_META_EVENTS"])." bytes\n";
		$qev=new mysql();
		$qev->QUERY_SQL($ARRAY["ARTICA_META_EVENTS"],"artica_events");
		if(!$qev->ok){echo "*********************************\n$$qev->mysql_error\n*********************************\n";}
	}else{
		echo __LINE__." ** ARTICA_META_EVENTS: NONE\n";
	}
	
	
	$CPU=$ARRAY["CPU"];
	$load=$ARRAY["load"];
	
	$pourc_mem=$ARRAY["memory"]["ram"]["percent"];
	$ram_used=$ARRAY["memory"]["ram"]["used"];
	$ram_total=$ARRAY["memory"]["ram"]["total"];
	$ALL_DISKS_STATUS=mysql_escape_string2($ARRAY["ALL_DISKS_STATUS"]);
	echo "ALL_DISKS_STATUS:$ALL_DISKS_STATUS\n";
	$sql="SELECT hostname FROM metahosts WHERE uuid='$UUID'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$currentDate=date("Y-m-d H:i:s");
	
	$squid=0;
	$squidver=null;
	if(isset($ARRAY["SQUID"])){$squid=$ARRAY["SQUID"];}
	if(isset($ARRAY["squidver"])){$squidver=$ARRAY["squidver"];}
	

	if(trim($ligne["hostname"])==null){
		$q->QUERY_SQL("INSERT IGNORE INTO `metahosts` (uuid,hostname,public_ip,updated,blacklisted,`version`,`CPU_NUMBER`
				,`load`,`mem_perc`,`mem_used`,mem_total,disks,PROXY,squidver) 
				VALUES('$UUID','$hostname','$IPADDR','$currentDate',0,'$version','$CPU'
				,'$load','$pourc_mem','$ram_used','$ram_total','$ALL_DISKS_STATUS','$squid','$squidver')");
		if(!$q->ok){echo $q->mysql_error;return false;}
		
		
	}else{
		$q->QUERY_SQL("UPDATE `metahosts` SET public_ip='$IPADDR', `hostname`='$hostname',
				`updated`='$currentDate',`version`='$version',
				`CPU_NUMBER`='$CPU',
				`load`='$load',
				`mem_perc`='$pourc_mem',
				`mem_used`='$ram_used',
				mem_total='$ram_total',
				`disks`='$ALL_DISKS_STATUS',
				PROXY='$squid',
				squidver='$squidver'
				WHERE uuid='$UUID'");
		
		if(!$q->ok){if(preg_match("#Unknown column#", $q->mysql_error)){$q->CheckTables();}}
		if(!$q->ok){echo $q->mysql_error;return false;}
		
		
	}

	return true;
	
}
function TEST_LOCAL_IDENT(){
	$ldap=new clladp();
	if(isset($_GET["test-local-ident"])){
		$array=unserialize(base64_decode($_GET["test-local-ident"]));
	}
	if(isset($_GET["local-ident"])){
		$array=unserialize(base64_decode($_GET["local-ident"]));
	}
	
	$ArticaMetaUsername=$array["username"];
	$ArticaMetaPassword=$array["password"];
	if(strtolower(trim($ArticaMetaUsername))==strtolower(trim($ldap->ldap_admin))){
		if(trim($ArticaMetaPassword)==trim($ldap->ldap_password)){
			if(isset($_GET["test-local-ident"])){echo "<ARTICA_META>SUCCESS</ARTICA_META>";}
			return true;
		}
	}
	if(isset($_GET["test-local-ident"])){echo "<ARTICA_META>FAILED</ARTICA_META>";}
	return false;
	
}

function CHANGE_UUID(){
	if(!TEST_LOCAL_IDENT()){
		echo "<ARTICA_META>FAILED</ARTICA_META>";
		return;
	}
	
	$sock=new sockets();
	$SYSTEMID1=$sock->GET_INFO("SYSTEMID");
	$sock->getFrameWork("system.php?change-new-uuid=yes");
	$SYSTEMID2=$sock->GET_INFO("SYSTEMID");
	if($SYSTEMID1<>$SYSTEMID2){
		echo "<ARTICA_META>SUCCESS:$SYSTEMID2</ARTICA_META>";
		return;
	}
	echo "<ARTICA_META>FAILED:$SYSTEMID1</ARTICA_META>";
	
}

function GetYourUUID(){
	if(!TEST_LOCAL_IDENT()){
		echo "<ARTICA_META>FAILED:NONE</ARTICA_META>";
		return;
	}	
	$sock=new sockets();
	$SYSTEMID1=$sock->GET_INFO("SYSTEMID");
	echo "<ARTICA_META>SUCCESS:$SYSTEMID1</ARTICA_META>";
	return;
}

function REGISTER_BY(){
	if(!TEST_LOCAL_IDENT()){
		echo "<ARTICA_META>FAILED:NONE</ARTICA_META>";
		return;
	}

	
	if(strlen($_GET["registerby"])<10){
		echo "<ARTICA_META>FAILED:No data sent</ARTICA_META>";
		return;
	}
	
	if($GLOBALS["VERBOSE"]){echo "{$_GET["registerby"]}\n";}
	
	$ArticaMetaAddNewServ=unserialize(base64_decode($_GET["registerby"]));
	$ArticaMetaServHost=$ArticaMetaAddNewServ["ArticaMetaServHost"];
	$ArticaMetaServPort=$ArticaMetaAddNewServ["ArticaMetaServPort"];
	$ArticaMetaUsername=$ArticaMetaAddNewServ["ArticaMetaUsername"];
	$ArticaMetaPassword=$ArticaMetaAddNewServ["ArticaMetaPassword"];
	
	if($GLOBALS["VERBOSE"]){print_r($ArticaMetaAddNewServ);}
	
	
	if(!is_array($ArticaMetaAddNewServ)){
		echo "<ARTICA_META>FAILED:No array</ARTICA_META>";
		return;
	}
	
	
	
	if($ArticaMetaUsername==null){
		echo "<ARTICA_META>FAILED:No ident Username</ARTICA_META>";
		return;
	}
	
	if($ArticaMetaPassword==null){
		echo "<ARTICA_META>FAILED:No ident password</ARTICA_META>";
		return;
	}	
	
	$sock=new sockets();
	$sock->SET_INFO("ArticaMetaUsername",$ArticaMetaUsername);
	$sock->SET_INFO("ArticaMetaPassword",$ArticaMetaPassword);
	$sock->SET_INFO("ArticaMetaHost",$ArticaMetaServHost);
	$sock->SET_INFO("ArticaMetaPort",$ArticaMetaServPort);
	$sock->SET_INFO("EnableArticaMetaClient", 1);
	
	$sock->getFrameWork("system.php?artica-status-restart=yes");
	echo "<ARTICA_META>SUCCESS:".$sock->getFrameWork("artica.php?meta-client-register=yes")."</ARTICA_META>";
	
}
