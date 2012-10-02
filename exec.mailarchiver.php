<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.demime.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.archive.builder.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if($argv[1]=="--date"){echo date('d M Y H:i:s')."\n";die();}
if($argv[1]=="--transfert"){transfert();die();}
if($argv[1]=="--scan-size"){ScanSize();die();}



if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}

if(strlen($argv[1])>0){die("Could not understand {$argv[1]}\n");}

work();
function work(){

	$unix=new unix();
	$pidfile="/etc/artica-postfix/".basename(__FILE__).".pid";
	$oldpid=@file_get_contents($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$timepid=$unix->PROCCESS_TIME_MIN($oldpid);
		system_admin_events("Other pid $oldpid running since {$timepid}mn", __FUNCTION__, __FILE__, __LINE__, "archive");
		die();
	}

	$pid=getmypid();
	file_put_contents($pidfile,$pid);
	$countDeFiles=0;
	$t=time();
	if (!$handle = opendir("/var/spool/mail-rtt-backup")) {@mkdir("/var/spool/mail-rtt-backup",0755,true);die();}
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="/var/spool/mail-rtt-backup/$filename";
		
		if($GLOBALS["VERBOSE"]){echo "Processing $targetFile\n";}
		if(archive_process($targetFile)){@unlink($targetFile);}
		$countDeFiles++;
		if(system_is_overloaded(basename(__FILE__))){
			$took=$unix->distanceOfTimeInWords($t,time());
			system_admin_events("Fatal: Overloaded system {$GLOBALS["SYSTEM_INTERNAL_LOAD"]} after $took execution time processed $countDeFiles files ->  aborting task","MAIN",__FILE__,__LINE__,"archive");
			die();
		}			
		
	}
	
ScanSize();

}


function events($text){
		$pid=getmypid();
		$date=date('Y-m-d H:i:s');
		$logFile="/var/log/artica-postfix/artica-mailarchive.debug";
		$size=filesize($logFile);
		if($size>5000000){unlink($logFile);}
		$f = @fopen($logFile, 'a');
		if($GLOBALS["VERBOSE"]){echo "$date mailarchive[$pid]:[BACKUP] $text\n";}
		@fwrite($f, "$date mailarchive[$pid]:[BACKUP] $text\n");
		@fclose($f);	
		}
		


function archive_process($file){
	$timeMessage=filemtime($file);
	$fullmessagesdir="/opt/artica/share/www/original_messages";
	$target_file=$file;
	$filename=basename($target_file);
	
	if(!isset($GLOBALS["GREP"])){
	$unix=new unix();
	$grep=$unix->find_program("grep");
	$GLOBALS["GREP"]=$grep;
	}else{$grep=$GLOBALS["GREP"];}
	
	exec("$grep X-REAL- $file 2>&1",$resultsgrep);
	while (list ($num, $line) = each ($resultsgrep) ){
		if(preg_match("#X-REAL-MAILFROM:\s+<(.*?)>#", $line,$re)){$realmailfrom=$re[1];}
		if(preg_match("#X-REAL-RCPTTO:\s+<(.*?)>#", $line,$re)){$realmailto=$re[1];}
	}
	if($GLOBALS["VERBOSE"]){echo "X-REAL-MAILFROM: $realmailfrom X-REAL-RCPTTO: $realmailto\n";}
	$ldap=new clladp();
	$q=new mysql_mailarchive_builder();

	events("Unpack $target_file");
	$mm=new demime($target_file);
	if(!$mm->unpack()){
		events("Failed unpack with error \"$mm->error\"");
		if($mm->MustkillMail){@unlink($target_file);}
		return false;
	}
	
	
	$message_html=$mm->ExportToHtml($target_file);
	
	if(strlen($message_html)==0){
		system_admin_events("$target_file: HTML FAILED...", __FUNCTION__, __FILE__, __LINE__, "archive");
		return false;
	}
	
	
	if(count($mm->mailto_array)==0){
		if($realmailto<>null){$mm->mailto_array[]=$realmailto;}
	}
	if(count($mm->mailto_array)==0){
		system_admin_events("$target_file: Fatal No recipients Aborting", __FUNCTION__, __FILE__, __LINE__, "archive");
		return true;
	}
	
	
	$filesize=@filesize($target_file);
	events("Message with ".count($mm->mailto_array)." recipients html file:".strlen($message_html)." bytes");
	if($realmailfrom<>null){$mm->mailfrom=$realmailfrom;}
	
	
	if(preg_match("#(.+?)@(.+)#",$mm->mailfrom,$re)){$domain_from=$re[2];}
	$message_html=addslashes($message_html);
	
	
	$mm->message_date=date("Y-m-d H:i:s",$timeMessage);
	$tableDest=date("Ymd",$timeMessage);
	if(!$q->BuildDayTable($tableDest)){
		system_admin_events("Fatal unable to create $tableDest date...", __FUNCTION__, __FILE__, __LINE__, "archive");
		return false;
	}
	$SubjectMysql=addslashes(mime_decode($mm->subject));
	
	while (list ($num, $recipient) = each ($mm->mailto_array) ){
		if(preg_match("#(.+?)@(.+)#",$recipient,$re)){$recipient_domain=$re[2];}
			$ou=$mm->GetOuFromEmail($recipient);
			$sql_source_file=$target_file;
			events("(New message)time=$mm->message_date message-id=<$mm->message_id> from=<$mm->mailfrom> to=<$recipient> size=$filesize");
			$newmessageid=md5($mm->message_id.$recipient);
			
			$sqlfilesize=@filesize($target_file);
			$BinMessg = addslashes(fread(fopen($target_file, "r"), $sqlfilesize));
			
			$sql="INSERT IGNORE INTO `$tableDest` (
				MessageID,
				zDate,
				mailfrom,
				mailfrom_domain,
				subject,
				MessageBody,
				organization,
				mailto,
				file_path,
				original_messageid,
				message_size,
				BinMessg,filename,filesize
				)
			VALUES(
				'$newmessageid',
				'$mm->message_date',
				'$mm->mailfrom',
				'$domain_from',
				'$SubjectMysql',
				'$message_html',
				'$ou',
				'$recipient',
				'$sql_source_file',
				'$mm->message_id',
				'$filesize','$BinMessg','$filename','$sqlfilesize')";
				
				if(!$q->QUERY_SQL($sql)){
					system_admin_events("Fatal $q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "archive");
					return false;
				}
			
		}
		
		events("Analyze sender $mm->mailfrom...");
		$ou=$mm->GetOuFromEmail($mm->mailfrom);
		if($ou==null){events("Not organization found for $mm->mailfrom...");return true;}
		$recipients=$mm->mailto_array;
		$impled_rctp=implode(";",$recipients);
		
		
		$sql="INSERT IGNORE INTO `$tableDest` (
				MessageID,
				zDate,
				mailfrom,
				mailfrom_domain,
				subject,
				MessageBody,
				organization,
				mailto,
				file_path,
				original_messageid,
				message_size,BinMessg,filename,filesize
				)
			VALUES(
				'$newmessageid',
				'$mm->message_date',
				'$mm->mailfrom',
				'$domain_from',
				'$SubjectMysql',
				'$message_html',
				'$ou',
				'$impled_rctp',
				'$sql_source_file',
				'$mm->message_id',
				'$filesize','$BinMessg','$filename','$sqlfilesize')";
				
				$q->QUERY_SQL($sql);
				if(!$q->ok){system_admin_events("Fatal $q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "archive");return false;}		
	
		WriteToSyslogMail("$mm->message_id: <$mm->mailfrom> to: <$impled_rctp> size=$filesize bytes (saved into backup area)",__FILE__);		
		events("time=$mm->message_date message-id=<$mm->message_id> from=<$mm->mailfrom> to=<$impled_rctp> size=$filesize");
		return true;
		
		
	}
	
	
	
function ForceDirectories($dir){
	if(is_dir($dir)){return true;}
	@mkdir($dir,null,true);
	if(is_dir($dir)){return true;}
	}
	
	function transfert(){

	$sql="SELECT file_path,MessageID FROM storage WHERE filesize=0";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		if(trim($ligne["file_path"])==null){
			continue;
		}
		
		if(!is_file($ligne["file_path"])){
			echo "Unable to find \"{$ligne["file_path"]}\"";
			DeleteLine($msgid);
		}
		$filename=basename($ligne["file_path"]);
		$sqlfilesize=@filesize($ligne["file_path"]);
		$BinMessg = addslashes(fread(fopen($ligne["file_path"], "r"), $sqlfilesize));
		$sql="UPDATE storage SET filesize=$sqlfilesize, filename='$filename',BinMessg='$BinMessg' WHERE MessageID='{$ligne["MessageID"]}'";
		$q=new mysql();
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){
			echo "failed {$ligne["MessageID"]}\n";	
			continue;	
		}
		
		echo "success {$ligne["MessageID"]} $sqlfilesize bytes message \n";	
		@unlink($ligne["file_path"]);
	}
	
	
	
}

function DeleteLine($msgid){
	echo "Deleting message $msgid\n";
	$sql="DELETE FROM storage WHERE MessageID='$msgid'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	
}

function DeleteMysqlError(){
foreach (glob("/var/log/artica-postfix/mysql-error.*.err") as $filename) {if(file_time_min($filename)>5){@unlink($filename);}}
}

function ScanSize(){
	$q=new mysql_mailarchive_builder();
	if(!$q->BuildSummaryTable()){
		system_admin_events("Fatal unable to create summary table...", __FUNCTION__, __FILE__, __LINE__, "archive");
		return;
	}
	$q->QUERY_SQL("TRUNCATE TABLE indextables");
	$LIST_BACKUP_TABLES=$q->LIST_BACKUP_TABLES();
	if(count($LIST_BACKUP_TABLES)==0){return;}
	$f=array();
	
	$prefix="INSERT IGNORE INTO indextables (tablename,xday,rowsnum,size) VALUES ";
	while (list ($tablename, $daySQL) = each ($LIST_BACKUP_TABLES) ){
		$size=$q->TABLE_SIZE($tablename);
		$rows=$q->COUNT_ROWS($tablename);
		if($GLOBALS["VERBOSE"]){echo "$tablename size:$size, rows:$rows\n";}
		$f[]="('$tablename','$daySQL','$rows','$size')";
		
	}
	
	if(count($f)>0){
		$q->QUERY_SQL($prefix.@implode(",", $f));
		if(!$q->ok){
			system_admin_events("Fatal $q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "archive");
			return false;
		}	
	}
	
}
function mime_decode($s) {
 if(!preg_match("#^=\?#", $s)){return $s;}
 if(!function_exists("imap_mime_header_decode")){return $s;}
  $elements = imap_mime_header_decode($s);
  for($i = 0;$i < count($elements);$i++) {
    $charset = $elements[$i]->charset;
    $text =$elements[$i]->text;
    if(!strcasecmp($charset, "utf-8") ||
       !strcasecmp($charset, "utf-7"))
    {
      $text = iconv($charset, "EUC-KR", $text);
    }
    $decoded = $decoded . $text;
  }
  return utf8_encode($decoded);
}



?>