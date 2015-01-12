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

$imploded=implode(" ",$argv);
if(preg_match("#--force#",$imploded)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--verbose#",$imploded)){$GLOBALS["VERBOSE"]=true;ini_set_verbosed();
}


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

function ufdb_smtp(){
	
	$unix=new unix();
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS("ufdb_smtp")){return;}
	if($q->COUNT_ROWS("ufdb_smtp")==0){return;}
	
	
	$q->QUERY_SQL("DELETE FROM ufdb_smtp WHERE `retrytime`>4");
	$sql="SELECT * FROM ufdb_smtp";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){return;}
	
	$sock=new sockets();
	$SquidGuardWebSMTP=unserialize(base64_decode($sock->GET_INFO("SquidGuardWebSMTP")));
	
	
	//`zDate`,`Subject`,`content`,`sender`,`URL`,`REASONGIVEN`,`retrytime`) VALUES
	
	while ($ligne = mysql_fetch_assoc($results)) {
		
		$zmd5=$ligne["zmd5"];
		$Subject=$ligne["Subject"];
		$smtp_sender=$ligne["sender"];
		$recipient=$SquidGuardWebSMTP["smtp_recipient"];
		$smtp_senderTR=explode("@",$recipient);
		$instance=$smtp_senderTR[1];
		

		$body[]="Return-Path: <$smtp_sender>";
		$body[]="Date: ". date("D, d M Y H:i:s"). " +0100 (CET)";
		$body[]="From: $smtp_sender";
		$body[]="Subject: $Subject";
		$body[]="To: $recipient";
		$body[]="";
		$body[]="";
		$body[]="Request time: {$ligne["zDate"]}";
		$body[]="URL.........: {$ligne["URL"]}";
		$body[]="Reason......: {$ligne["REASONGIVEN"]}";
		$body[]="SMTP retry..: {$ligne["retrytime"]}";
		$body[]=$ligne["content"];
		
		
		$body[]="";
		$body[]="";
		$finalbody=@implode("\r\n", $body);
		
		
		if($SquidGuardWebSMTP["smtp_auth_user"]<>null){
			$params["auth"]=true;
			$params["user"]=$SquidGuardWebSMTP["smtp_auth_user"];
			$params["pass"]=$SquidGuardWebSMTP["smtp_auth_passwd"];
		}
		$params["host"]=$SquidGuardWebSMTP["smtp_server_name"];
		$params["port"]=$SquidGuardWebSMTP["smtp_server_port"];
		
		$retrytime=$ligne["retrytime"]+1;
		
		
		$smtp=new smtp();
		if(!$smtp->connect($params)){
			writelogs("parseTemplate_sendemail_perform:{$smtp_sender} -> {error} $smtp->error_numbe",__FUNCTION__,__FILE__,__LINE__);
			$q->QUERY_SQL("UPDATE ufdb_smtp SET `retrytime`='$retrytime' WHERE `zmd5`='$zmd5'");
			continue;
			
		}
		
		
		if(!$smtp->send(array("from"=>$smtp_sender,"recipients"=>$recipient,"body"=>$finalbody,"headers"=>null))){
			$smtp->quit();
			writelogs("parseTemplate_sendemail_perform:{$smtp_sender} -> {error} $smtp->error_numbe",__FUNCTION__,__FILE__,__LINE__);
			$q->QUERY_SQL("UPDATE ufdb_smtp SET `retrytime`='$retrytime' WHERE `zmd5`='$zmd5'");
			continue;
		}
		
		$smtp->quit();
		$q->QUERY_SQL("DELETE FROM ufdb_smtp WHERE `zmd5`='$zmd5'");
		
		
		
	}
	
	if($q->COUNT_ROWS("ufdb_smtp")==0){
		$q->QUERY_SQL("DROP TABLE ufdb_smtp");
	}
	
	
	
	
}