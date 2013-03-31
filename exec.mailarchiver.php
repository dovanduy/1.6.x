<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.demime.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.archive.builder.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__). "/ressources/smtp/smtp.php");
include_once(dirname(__FILE__).'/ressources/class.mime.parser.inc');
include_once(dirname(__FILE__).'/ressources/class.rfc822.addresses.inc');

if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;
$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',null);ini_set('error_append_string',null);
}
if($argv[1]=="--date"){echo date('d M Y H:i:s')."\n";die();}
if($argv[1]=="--transfert"){transfert();die();}
if($argv[1]=="--scan-size"){ScanSize();die();}
if($argv[1]=="--verbose"){unset($argv[1]);}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit;}
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit;}


if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(isset($argv)){
	if(count($argv)>0){if(strlen($argv[1])>0){die("Could not understand {$argv[1]}\n");}}
}
$sock=new sockets();
$MailArchiverEnabled=$sock->GET_INFO("MailArchiverEnabled");
$MailArchiverToMySQL=$sock->GET_INFO("MailArchiverToMySQL");
$MailArchiverToMailBox=$sock->GET_INFO("MailArchiverToMailBox");
$MailArchiverMailBox=$sock->GET_INFO("MailArchiverMailBox");
$MailArchiverToSMTP=$sock->GET_INFO("MailArchiverToSMTP");
$MailArchiverSMTP=$sock->GET_INFO("MailArchiverSMTP");
$MailArchiverSMTPINcoming=$sock->GET_INFO("MailArchiverSMTPINcoming");

if(!is_numeric($MailArchiverEnabled)){$MailArchiverEnabled=0;}
if(!is_numeric($MailArchiverToMySQL)){$MailArchiverToMySQL=1;}
if(!is_numeric($MailArchiverToSMTP)){$MailArchiverToSMTP=0;}
if(!is_numeric($MailArchiverSMTPINcoming)){$MailArchiverSMTPINcoming=1;}

$GLOBALS["MailArchiverEnabled"]=$MailArchiverEnabled;
$GLOBALS["MailArchiverToMySQL"]=$MailArchiverToMySQL;
$GLOBALS["MailArchiverToMailBox"]=$MailArchiverToMailBox;
$GLOBALS["MailArchiverMailBox"]=$MailArchiverMailBox;
$GLOBALS["MailArchiverToSMTP"]=$MailArchiverToSMTP;
$GLOBALS["MailArchiverSMTP"]=$MailArchiverSMTP;
$GLOBALS["MailArchiverSMTPINcoming"]=$MailArchiverSMTPINcoming;

work();
function work(){
	//return;
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
	
	if($GLOBALS["VERBOSE"]){echo "Processing /var/spool/mail-rtt-backup\n";}
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="/var/spool/mail-rtt-backup/$filename";
		
		if($GLOBALS["VERBOSE"]){echo "Processing $targetFile\n";}
		if(archive_process($targetFile)){
			events("removing $targetFile",__LINE__);
			@unlink($targetFile);
		}
		$countDeFiles++;
		if(system_is_overloaded(basename(__FILE__))){
			$took=$unix->distanceOfTimeInWords($t,time());
			system_admin_events("Fatal: Overloaded system {$GLOBALS["SYSTEM_INTERNAL_LOAD"]} after $took execution time processed $countDeFiles files ->  aborting task","MAIN",__FILE__,__LINE__,"archive");
			die();
		}			
		
	}
	
ScanSize();

}


function events($text,$line=0){
		$pid=getmypid();
		$date=date('Y-m-d H:i:s');
		$logFile="/var/log/artica-postfix/artica-mailarchive.debug";
		$size=filesize($logFile);
		if($size>5000000){unlink($logFile);}
		$f = @fopen($logFile, 'a');
		if($GLOBALS["VERBOSE"]){echo "$date mailarchive[$pid]:[BACKUP] $text\n";}
		@fwrite($f, "$date mailarchive[$pid]:[BACKUP] $text in line: $line\n");
		@fclose($f);	
		}
		
		
function archive_process_smtp($fullmessagesdir,$realmailfrom){
	
	$MailArchiverEnabled=$GLOBALS["MailArchiverEnabled"];
	$MailArchiverToMySQL=$GLOBALS["MailArchiverToMySQL"];
	$MailArchiverToMailBox=$GLOBALS["MailArchiverToMailBox"];
	$MailArchiverMailBox=$GLOBALS["MailArchiverMailBox"];	
	
	$MailArchiverToSMTP=$GLOBALS["MailArchiverToSMTP"];
	$MailArchiverSMTP=$GLOBALS["MailArchiverSMTP"];
	
	
	
	$basename=basename($fullmessagesdir);
	$smtp=new smtp();
	$params["host"]="127.0.0.1";
	$params["helo"]=$GLOBALS["MYHOSTNAME"];
	$params["bindto"]="127.0.0.1";
	
	if(!$smtp->connect($params)){
		events("[$basename] $realmailfrom -> Could not connect to  `127.0.0.1:25`",__LINE__);
		smtp::events("[$basename] $realmailfrom -> Could not connect to  `127.0.0.1:25`",__FUNCTION__,__FILE__,__LINE__);
		return false;
	}
	
	$size=@filesize($fullmessagesdir);
	if($size==0){
		events("[$basename] Failed from=<$realmailfrom> to=<$MailArchiverMailBox> 0 bytes",__LINE__);
		return true;
	}
	
	$MAILDATA=@file_get_contents($fullmessagesdir);
	$MAILDATA=str_replace("X-Archive-end", "X-REAL-ARCHIVED: yes", $MAILDATA);
	$MAILDATA=str_replace("X-REAL-MAILFROM", "X-REAL-ARCHIVED: yes\r\nX-REAL-MAILFROM", $MAILDATA);
	
	if(!$smtp->send(array("from"=>$realmailfrom,"recipients"=>$MailArchiverMailBox,"body"=>$MAILDATA))){
		events("[$basename] Failed from=<$realmailfrom> to=<$MailArchiverMailBox>",__LINE__);
		smtp::events("[$basename] Failed from=<$realmailfrom> to=<$MailArchiverMailBox>",__FUNCTION__,__FILE__,__LINE__);
		return false;
	}
	events("Success from=<$realmailfrom> to=<$MailArchiverMailBox> trough {$params["host"]}",__LINE__);
	if($GLOBALS["VERBOSE"]){echo "Success from=<$realmailfrom> to=<$MailArchiverMailBox> trough {$params["host"]}\n";}
	return true;
	
}

function archive_process_smtpsrv($file,$realmailfrom,$realmailto){
	$MailArchiverToSMTP=$GLOBALS["MailArchiverToSMTP"];
	$MailArchiverSMTP=$GLOBALS["MailArchiverSMTP"];
	$MailArchiverSMTPINcoming=$GLOBALS["MailArchiverSMTPINcoming"];	
	$MailArchiverSMTP_port=25;
	$SMTPSERV=true;
	$realmailto=trim(strtolower($realmailto));
	
	
	if($MailArchiverSMTP==null){$SMTPSERV=false;}
	if($MailArchiverSMTP=="localhost"){$SMTPSERV=false;}
	if($MailArchiverSMTP=="127.0.0.1"){$SMTPSERV=false;}
	
	if(!$SMTPSERV){
		events("Not from=<$realmailfrom> to=<$realmailto> bad remote SMTP server `$MailArchiverSMTP`",__LINE__);
		return true;
	}
	
	
	if(preg_match("#^(.+?)@(.+)#", $realmailto,$re)){$DomainTo=trim($re[1]);}
	
	if(!isset($GLOBALS["INBOUND_SMTP"])){
		$f=explode("\n",@file_get_contents("/etc/postfix/mydestination"));
		while (list ($num, $line) = each ($f) ){
			if(preg_match("#^(.+?)\s+#", $line,$re)){$GLOBALS["INBOUND_SMTP"][trim(strtolower($re[1]))]=true;}
		}
		$f=explode("\n",@file_get_contents("/etc/postfix/relay_domains"));
		while (list ($num, $line) = each ($f) ){
			if(preg_match("#^(.+?)\s+#", $line,$re)){$GLOBALS["INBOUND_SMTP"][trim(strtolower($re[1]))]=true;}
		}	
		$f=explode("\n",@file_get_contents("/etc/postfix/virtual"));
		while (list ($num, $line) = each ($f) ){
			if(preg_match("#^(.+?)\s+#", $line,$re)){$GLOBALS["INBOUND_SMTP"][trim(strtolower($re[1]))]=true;}
		}	
	}
	$ISINBOUND=false;
	if(isset($GLOBALS["INBOUND_SMTP"][$realmailto])){$ISINBOUND=true;}
	if(isset($GLOBALS["INBOUND_SMTP"][$DomainTo])){$ISINBOUND=true;}
	if($MailArchiverSMTPINcoming==1){
		if(!$ISINBOUND){
			events("Not from=<$realmailfrom> to=<$realmailto> not an inbound message",__LINE__);
			return true;
		}
	}
	if(preg_match("#^(.+?):([0-9]+)#", $MailArchiverSMTP,$re)){
		$MailArchiverSMTP=$re[1];
		$MailArchiverSMTP_port=$re[2];
	}
	$basename=basename($file);
	$smtp=new smtp();
	$params["host"]=$MailArchiverSMTP;
	$params["helo"]=$GLOBALS["MYHOSTNAME"];
	$params["port"]=$MailArchiverSMTP_port;
	//$params["bindto"]="127.0.0.1";
	
	if(!$smtp->connect($params)){
		events("[$basename] $realmailfrom -> Could not connect to  `{$MailArchiverSMTP}:$MailArchiverSMTP_port`",__LINE__);
		smtp::events("[$basename] $realmailfrom -> Could not connect to  `{$MailArchiverSMTP}:$MailArchiverSMTP_port`",__FUNCTION__,__FILE__,__LINE__);
		return false;
	}
	
	$size=@filesize($file);
	if($size==0){
		events("[$basename] Failed from=<$realmailfrom> to=<$realmailto> 0 bytes",__LINE__);
		return true;
	}
	
	$MAILDATA=@file_get_contents($file);
	$MAILDATA=str_replace("X-Archive-end", "X-REAL-ARCHIVED: yes", $MAILDATA);
	$MAILDATA=str_replace("X-REAL-MAILFROM", "X-REAL-ARCHIVED: yes\r\nX-REAL-MAILFROM", $MAILDATA);
	
	if(!$smtp->send(array("from"=>$realmailfrom,"recipients"=>$realmailto,"body"=>$MAILDATA))){
		events("[$basename] Failed from=<$realmailfrom> to=<$realmailto>",__LINE__);
		smtp::events("[$basename] Failed from=<$realmailfrom> to=<$realmailto>",__FUNCTION__,__FILE__,__LINE__);
		return false;
	}
	events("Success from=<$realmailfrom> to=<$realmailto> trough {$params["host"]}",__LINE__);
	if($GLOBALS["VERBOSE"]){echo "Success from=<$realmailto> to=<$realmailto> trough {$params["host"]}\n";}
	return true;	
	
}


function archive_process($file){
	$unix=new unix();
	$timeMessage=filemtime($file);
	$fullmessagesdir="/opt/artica/share/www/original_messages";
	$target_file=$file;
	$filename=basename($target_file);
	if(!isset($GLOBALS["GREP"])){$GLOBALS["GREP"]=$unix->find_program("grep");}
	if(!isset($GLOBALS["MYHOSTNAME"])){$GLOBALS["MYHOSTNAME"]=$unix->hostname_g();}
	$grep=$GLOBALS["GREP"];
	$ARCHIVED=false;
	$MailArchiverEnabled=$GLOBALS["MailArchiverEnabled"];
	$MailArchiverToMySQL=$GLOBALS["MailArchiverToMySQL"];
	$MailArchiverToMailBox=$GLOBALS["MailArchiverToMailBox"];
	$MailArchiverMailBox=$GLOBALS["MailArchiverMailBox"];	
	$MailArchiverToSMTP=$GLOBALS["MailArchiverToSMTP"];
	$MailArchiverSMTP=$GLOBALS["MailArchiverSMTP"];
	$MailArchiverSMTPINcoming=$GLOBALS["MailArchiverSMTPINcoming"];	

	exec("$grep X-REAL- $file 2>&1",$resultsgrep);
	
	while (list ($num, $line) = each ($resultsgrep) ){
		events("[$num] $line",__LINE__);
		if(preg_match("#X-REAL-MAILFROM:\s+<(.*?)>#", $line,$re)){$realmailfrom=trim($re[1]);}
		if(preg_match("#X-REAL-RCPTTO:\s+<(.*?)>#", $line,$re)){$realmailto=trim($re[1]);}
		if(preg_match("#X-REAL-ARCHIVED#", $line,$re)){
			events("$file detected as already archived...",__LINE__);
			$ARCHIVED=true;
		}
		
	}
	$realmailfrom=str_replace("<", "", $realmailfrom);
	$realmailto=str_replace("<", "", $realmailto);
	$realmailfrom=str_replace(">", "", $realmailfrom);
	$realmailto=str_replace(">", "", $realmailto);
	
	
	if($GLOBALS["VERBOSE"]){echo "X-REAL-MAILFROM: `$realmailfrom` X-REAL-RCPTTO: `$realmailto`\n";}
	
	
	if($MailArchiverToMailBox==1){
		if($GLOBALS["VERBOSE"]){echo "archive_process_smtp($fullmessagesdir,$realmailfrom)\n";}
		if(!$ARCHIVED){
			if(!archive_process_smtp($file,$realmailfrom)){return false;}
		}
	}
	
	if($MailArchiverSMTPINcoming==1){
		if(!$ARCHIVED){
			if(!archive_process_smtpsrv($file,$realmailfrom,$realmailto)){return false;}
		}		
		
	}

	if($MailArchiverToMySQL==0){events("$file return true;",__LINE__);return true;}
	
	
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

function stop($aspid=false){
	$sock=new sockets();
	$unix=new unix();
	$GLOBALS["CLASS_UNIX"]=$unix;
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Already task running PID $oldpid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}	
	
	$pid=mailarchive_pid();
	if(!$unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: Mail Archiver already stopped...\n";}
		return;
	}
	$time=$unix->PROCCESS_TIME_MIN($oldpid);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: Mail Archiver pid $pid (run since {$time}Mn)...\n";}
	$kill=$unix->find_program("kill");
	shell_exec("$kill $pid");
	
	for($i=0;$i<5;$i++){
		
		$pid=mailarchive_pid();
		if(!$unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: Mail Archiver (Perl method) stopped...\n";}
			break;
		}
		sleep(1);
	
	}
	$pid=mailarchive_pid();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: Mail Archiver (Perl method) success...\n";}
		@unlink("/var/run/maildump/maildump.socket");
	}else{
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: Mail Archiver (Perl method) failed to stop..\n";}
	}	
	
}

function CheckPerlDebian(){
	
	if(is_file("/usr/share/perl5/Sendmail/PMilter.pm")){return;}
	if(is_file("/usr/share/perl6/Sendmail/PMilter.pm")){return;}
	$unix=new unix();
	$aptget=$unix->find_program("apt-get");
	if(is_file($aptget)){
		if($GLOBALS["VERBOSE"]){echo "Installing python-lxml\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Mail Archiver please wait, installing libsendmail-pmilter-perl...\n";}
		exec("DEBIAN_FRONTEND=noninteractive $aptget -o Dpkg::Options::=\"--force-confnew\" --force-yes -fuy install libsendmail-pmilter-perl 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: $ligne\n";}
		}
		
		$run=true;
	
	}	
}


function start($aspid=false){
	$sock=new sockets();
	$unix=new unix();
	$GLOBALS["CLASS_UNIX"]=$unix;
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Already task running PID $oldpid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}


	

	$MailArchiverEnabled=$sock->GET_INFO("MailArchiverEnabled");
	$MailArchiverToMySQL=$sock->GET_INFO("MailArchiverToMySQL");
	$MailArchiverToMailBox=$sock->GET_INFO("MailArchiverToMailBox");
	$MailArchiverMailBox=$sock->GET_INFO("MailArchiverMailBox");
	$MailArchiverUsePerl=$sock->GET_INFO("MailArchiverUsePerl");
	if(!is_numeric($MailArchiverEnabled)){$MailArchiverEnabled=0;}
	if(!is_numeric($MailArchiverToMySQL)){$MailArchiverToMySQL=1;}
	if(!is_numeric($MailArchiverUsePerl)){$MailArchiverUsePerl=1;}

	
	if($MailArchiverEnabled==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Mail Archiver is disabled...\n";}
		return;
	}	
	if($MailArchiverUsePerl==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Mail Archiver (Perl method) is disabled...\n";}
		return;
	}
	
	$pid=mailarchive_pid();
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Mail Archiver (Perl method) already running pid $pid since {$time}mn...\n";}
		return;
	}

	
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Mail Archiver (Perl method)...\n";}
	
	$usersMenus=new usersMenus();
	$OS=$usersMenus->LinuxDistriCode;
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Mail Archiver (Perl method) on $OS...\n";}
	if(($OS=="DEBIAN") OR ($OS=="UBUNTU")){
		CheckPerlDebian();
	}
	
	
	
	
	
	$nohup=$unix->find_program("nohup");
	@mkdir("/var/spool/mail-rtt-backup",0755,true);
	@mkdir("/var/run/maildump",0777,true);
	@unlink("/var/run/maildump/maildump.socket");
	$cmd="$nohup /usr/share/artica-postfix/bin/milter_archiver.pl >/dev/null 2>&1 &";
	shell_exec($cmd);
	
	for($i=0;$i<5;$i++){
		sleep(1);
		$pid=mailarchive_pid();
		if($unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Mail Archiver (Perl method) running pid $pid...\n";}
			break;
		}
		
	}
	
	
	$pid=mailarchive_pid();
	if($unix->process_exists($pid)){
		@chmod("/var/run/maildump/maildump.socket", 0777);
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Mail Archiver (Perl method) failed..\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: $cmd\n";}
	}

}
function mailarchive_pid(){
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
	exec("$pgrep -l -f milter_archiver.pl 2>&1",$results);
	if(!is_array($results)){return null;}
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#pgrep#",$ligne,$re)){continue;}
		if(!preg_match("#([0-9]+)\s+(.+)#",$ligne,$re)){continue;}
		return $re[1];	
	}	
	
}




?>