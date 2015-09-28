<?php

$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dansguardian.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.squidguard.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/ressources/class.compile.ufdbguard.inc");
include_once(dirname(__FILE__)."/ressources/class.compile.dansguardian.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.ufdbguard-tools.inc');
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__).'/ressources/smtp/smtp.php');
include_once(dirname(__FILE__).'/ressources/class.squidguard-msmtp.inc');
ini_set('display_errors', 1);
ini_set("log_errors", 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
ini_set("error_log", "/var/log/ufdb-smtp.log");


$imploded=implode(" ",$argv);
if(preg_match("#--force#",$imploded)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--verbose#",$imploded)){$GLOBALS["VERBOSE"]=true;ini_set_verbosed();}


if($argv[1]=="--smtp"){ufdb_all_smtp();die();}


function ufdb_all_smtp(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/exec.squidguard.smtp.php.ufdb_all_smtp.pid";
	$TimeFile="/etc/artica-postfix/pids/exec.squidguard.smtp.php.ufdb_all_smtp.time";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){echo "Already running pid $pid\n";return;}	
	
	if(!$GLOBALS["FORCE"]){
		$time=$unix->file_time_min($TimeFile);
		if($time<15){return;}
		
	}
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	
	ufdb_smtp();
	
}

function ufdb_smtp_logs($text,$function,$line){
	ini_set("error_log", "/var/log/ufdb-smtp.log");
	$size=filesize("/var/log/ufdb-smtp.log");
	if($size>1000000){@unlink("/var/log/ufdb-smtp.log");}
	$lineToSave=date('H:i:s')." $text function $function line $line";
	error_log($lineToSave);
}

function ufdb_smtp(){
	
	$unix=new unix();
	$q=new mysql_squid_builder();
	$sock=new sockets();
	if(!$q->TABLE_EXISTS("ufdb_smtp")){return;}
	if($q->COUNT_ROWS("ufdb_smtp")==0){return;}
	$SquidGuardWebSMTP=unserialize(base64_decode($sock->GET_INFO("SquidGuardWebSMTP")));
	if(!isset($SquidGuardWebSMTP["MaxError"])){$SquidGuardWebSMTP["MaxError"]=5;}
	if($SquidGuardWebSMTP["MaxError"]==0){$SquidGuardWebSMTP["MaxError"]=5;}
	
	
	$sql="SELECT * FROM ufdb_smtp";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){return;}
	
	$sock=new sockets();
	$SquidGuardWebSMTP=unserialize(base64_decode($sock->GET_INFO("SquidGuardWebSMTP")));
	if($SquidGuardWebSMTP["smtp_server_name"]==null){return;}
	
	//`zDate`,`Subject`,`content`,`sender`,`URL`,`REASONGIVEN`,`retrytime`) VALUES
	
	while ($ligne = mysql_fetch_assoc($results)) {
		
		$zmd5=$ligne["zmd5"];
		$Subject=$ligne["Subject"];
		$smtp_sender=$ligne["sender"];
		$recipient=$SquidGuardWebSMTP["smtp_recipient"];
		$smtp_senderTR=explode("@",$recipient);
		$instance=$smtp_senderTR[1];
		$SquidGuardIPWeb=$ligne["SquidGuardIPWeb"];
		$ticket=$ligne["ticket"];
		$main_array=urlencode($ligne["main_array"]);
		if($smtp_sender==null){$smtp_sender=$SquidGuardWebSMTP["smtp_sender"];}
		
		$body=array();

		$body[]="Return-Path: <$smtp_sender>";
		$body[]="Date: ". date("D, d M Y H:i:s"). " +0100 (CET)";
		$body[]="From: $smtp_sender";
		$body[]="Subject: $Subject";
		$body[]="To: $recipient";
		$body[]="";
		$body[]="";
		$body[]="Request time: {$ligne["zDate"]}";
		$body[]="URL: {$ligne["URL"]}";
		$body[]="Reason: {$ligne["REASONGIVEN"]}";
		$body[]="SMTP retry: {$ligne["retrytime"]}";
		
		if($ticket==1){
			$body[]="";
			$body[]="****************** RELEASE THIS WEBSITE ******************";
			$body[]="";
			$body[]="If your are agree to release this website, click on the link bellow in order to create the rule.";
			$body[]="$SquidGuardIPWeb?release-ticket=yes&serialize=$main_array";
			$body[]="";
			$body[]="***********************************************************";
			$body[]="";
		}
		
		
		$body[]=$ligne["content"];
		$body[]="";
		$body[]="";
		$finalbody=@implode("\r\n", $body);
		
		ufdb_smtp_logs("Send to $smtp_sender",__FUNCTION__,__LINE__);
		$msmtp=new squidguard_msmtp($smtp_sender,$finalbody);
		
		$MaxError=$msmtp->MaxError;
		
		if($msmtp->Send()){
			ufdb_smtp_logs("Send Success, delete $zmd5",__FUNCTION__,__LINE__);
			$q->QUERY_SQL("DELETE FROM ufdb_smtp WHERE `zmd5`='$zmd5'");
			if(!$q->ok){ufdb_smtp_logs("$q->mysql_error",__FUNCTION__,__LINE__);}
			if($q->COUNT_ROWS("ufdb_smtp")==0){break;}
			continue;
		}
		
		$retrytime=$ligne["retrytime"]+1;
		ufdb_smtp_logs("$zmd5: Retry +1 = $retrytime Max:$MaxError",__FUNCTION__,__LINE__);
		
		if($retrytime>=$MaxError){
			squid_admin_mysql(1, "Timed out $Subject to {$SquidGuardWebSMTP["smtp_server_name"]} retry($retrytime/$MaxError)", $msmtp->logs,__FILE__,__LINE__);
			$q->QUERY_SQL("DELETE FROM ufdb_smtp WHERE `zmd5`='$zmd5'");
			continue;
		}
		
		squid_admin_mysql(1, "Unable to send $Subject to {$SquidGuardWebSMTP["smtp_server_name"]} retry($retrytime/$MaxError)", $msmtp->logs,__FILE__,__LINE__);
		$q->QUERY_SQL("UPDATE ufdb_smtp SET `retrytime`='$retrytime' WHERE `zmd5`='$zmd5'");
		if(!$q->ok){ufdb_smtp_logs("$q->mysql_error",__FUNCTION__,__LINE__);}
		
		
		
	}
	$q->QUERY_SQL("DELETE FROM ufdb_smtp WHERE `retrytime`=$MaxError");
	$q->QUERY_SQL("DELETE FROM ufdb_smtp WHERE `retrytime`>".$MaxError);
		
}