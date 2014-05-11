<?php

if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
$_GET["LOGFILE"]="/var/log/artica-postfix/dansguardian-logger.debug";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--simulate#",implode(" ",$argv))){$GLOBALS["SIMULATE"]=true;}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if($argv[1]=="--import"){include_tpl_file($argv[2],$argv[3]);die();}
if($argv[1]=="--sites-infos"){ParseSitesInfos();die();}
if($argv[1]=="--streamget"){streamget();die();}
if($argv[1]=="--notifs"){ufdguard_send_notifications();die();}
if($argv[1]=="--blocked"){PaseUdfdbGuardnew();die();}
if($argv[1]=="--errors"){ufdbguard_blocks_errors();die();}




$pid=getmypid();
$pidfile="/etc/artica-postfix/".basename(__FILE__).".pid";
$oldpid=@file_get_contents($pidfile);
$unix=new unix();
$GLOBALS["CLASS_UNIX"]=$unix;
if($unix->process_exists($oldpid)){
	if($pid<>$oldpid){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		events(basename(__FILE__).": Already executed $oldpid (since {$time}Mn).. aborting the process (line:  Line: ".__LINE__.")");
		events_tail("Already executed $oldpid (since {$time}Mn). aborting the process (line:  Line: ".__LINE__.")");
		if($time>120){
			events(basename(__FILE__).": killing $oldpid  (line:  Line: ".__LINE__.")");
			shell_exec("/bin/kill -9 $oldpid");
		}else{	
			die();
		}
	}
}

$t1=time();
file_put_contents($pidfile,$pid);
events(basename(__FILE__).": running $pid");
events_tail("running $pid");	
$nohup=$unix->find_program("nohup");
if(!is_dir("/var/log/artica-postfix/dansguardian-stats4")){@mkdir("/var/log/artica-postfix/dansguardian-stats4",660,true);}
if(!is_dir("/var/log/artica-postfix/dansguardian-stats4-failed")){@mkdir("/var/log/artica-postfix/dansguardian-stats4-failed",660,true);}

$cmdline=$nohup." ".$unix->LOCATE_PHP5_BIN()." ".dirname(__FILE__)."/exec.squid-tail-injector.php --brut >/dev/null 2>&1 &";
events_tail($cmdline);
shell_exec($cmdline);
ParseLogs();
ParseLogsNew();
ParseSitesInfos();
PaseUdfdbGuard();
PaseUdfdbGuardnew();
ParseUdfdbGuard_failed();
ufdbguard_blocks_errors();
$t2=time();
$distanceOfTimeInWords=distanceOfTimeInWords($t1,$t2);

events(basename(__FILE__).": finish in $distanceOfTimeInWords");
$mem=round(((memory_get_usage()/1024)/1000),2);
events_tail("finish in $distanceOfTimeInWords {$mem}MB");
die();	

function ParseLogs(){
	$count=0;
	events_tail("dansguardian-stats:: parsing /var/log/artica-postfix/dansguardian-stats");
	foreach (glob("/var/log/artica-postfix/dansguardian-stats/*.sql") as $file) {
		$q=new mysql_squid_builder();
		usleep(20000);
		$count=$count+1;
		$sql=@file_get_contents($file);
		if(trim($sql)==null){@unlink("$file");continue;}
		$q->QUERY_SQL($sql,"artica_events");
		if($q->ok){
			events_tail("success Parse $file sql file","MAIN",__FILE__,__LINE__);
			@unlink("$file");
		}else{
			events_tail("Failed Parse $file sql file $count");
			writelogs("Failed Parse $file sql file $count","MAIN",__FILE__,__LINE__);
			writelogs("$q->mysql_error","MAIN",__FILE__,__LINE__);
			writelogs("SQL[\"$sql\"]","MAIN",__FILE__,__LINE__);
		}
		
		$q->ok=true;
		
	}
	events_tail("dansguardian-stats:: Deleted $count mysql files for proxy events");

}


function events($text){
		$date=@date("H:i:s");
		$pid=getmypid();
		$logFile=$_GET["LOGFILE"];
		$size=filesize($logFile);
		if($size>1000000){unlink($logFile);}
		$f = @fopen($logFile, 'a');
		if($GLOBALS["debug"]){echo "$pid $text\n";}
		@fwrite($f, "$pid ".basename(__FILE__)." $date $text\n");
		@fclose($f);	
		}
		
function GetRuleName($filename){
	$tb=explode("\n", $filename);
	while (list ($index, $ligne) = each ($tb) ){
		if(preg_match("#^groupname.+?'(.+?)'#", $ligne,$re)){return $re[1];}
	}
}

function streamget(){
	
	$sock=new sockets();
	$unix=new unix();
	$SquidGuardStorageDir=$sock->GET_INFO("SquidGuardStorageDir");	
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	$hostname=$unix->FULL_HOSTNAME();
	$PREFIX="INSERT IGNORE INTO `youtubecache`(`filename`,`filesize`,`urlsrc`,`zDate`,`zMD5`,`proxyname`) VALUES ";
	$q=new mysql_squid_builder();
	if (!$handle = opendir($SquidGuardStorageDir)) {
		events_tail("streamget:: -> glob failed $SquidGuardStorageDir in Line: ".__LINE__);
		return;
	}
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}		
		$fullFileName="$SquidGuardStorageDir/$filename";
		$time=null;
		if(strpos($filename, ".url")>0){continue;}
		if(strpos($filename, ".log")>0){continue;}
		$filesize=$unix->file_size($fullFileName);
		$time=filemtime($fullFileName);
		$zdate=date("Y-m-d H:i:s",$time);
		$url=null;
		if(is_file($fullFileName.".url")){$url=@file_get_contents($fullFileName.".url");}
		if($GLOBALS["VERBOSE"]){echo "\n\nFile:$fullFileName\nSize:$filesize\ndate:$zdate\nurl:$url\n";}
		$md5=md5($filename.$hostname);
		$f[]="('$fullFileName','$filesize','$url','$zdate','$md5','$hostname')";
	}
	
	if(count($f)>0){
		$sql=$PREFIX." ".@implode(",",$f);
		if($EnableRemoteStatisticsAppliance==0){
			$q->QUERY_SQL($sql);
				if(!$q->ok){
				events_tail("streamget:: Fatal $q->mysql_error");	
			}
		}else{
			if($GLOBALS["VERBOSE"]){echo "streamget_send_remote() with hostname $hostname\n";}
			streamget_send_remote($sql,$hostname);
		}
	}	
	
	
}

function _LoadStatisticsSettings(){
	if(isset($GLOBALS["REMOTE_SSERVER"])){return;}
	$sock=new sockets();
	$RemoteStatisticsApplianceSettings=unserialize(base64_decode($sock->GET_INFO("RemoteStatisticsApplianceSettings")));
	if(!is_numeric($RemoteStatisticsApplianceSettings["SSL"])){$RemoteStatisticsApplianceSettings["SSL"]=1;}
	if(!is_numeric($RemoteStatisticsApplianceSettings["PORT"])){$RemoteStatisticsApplianceSettings["PORT"]=9000;}
	$GLOBALS["REMOTE_SSERVER"]=$RemoteStatisticsApplianceSettings["SERVER"];
	$GLOBALS["REMOTE_SPORT"]=$RemoteStatisticsApplianceSettings["PORT"];
	$GLOBALS["REMOTE_SSL"]=$RemoteStatisticsApplianceSettings["SSL"];		
}


function ParseLogsNew(){
	_LoadStatisticsSettings();
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
	if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}
	if($UnlockWebStats==1){$EnableRemoteStatisticsAppliance=0;}	
	
	$GLOBALS["EnableRemoteStatisticsAppliance"]=$EnableRemoteStatisticsAppliance;

	if($EnableRemoteStatisticsAppliance==1){
		
		writelogs("WARNING YOU MUST DEV THAT !!! ",__FUNCTION__,__FILE__,__LINE__);
		return;
		
	}
	foreach (glob("/etc/dansguardian/dansguardianf*.conf") as $file) {
		$basename=basename($file);
		preg_match("#dansguardianf([0-9]+)\.#", $basename,$re);
		if($re[1]==1){$RULESD[1]="default";continue;}
		$RULESD[$re[1]]=GetRuleName($file);
		
	}
	
	
	
	$failedir="/var/log/artica-postfix/dansguardian-stats4-failed";
	events_tail("dansguardian-stats4:: parsing /var/log/artica-postfix/dansguardian-stats4 Line: ".__LINE__);
	if (!$handle = opendir("/var/log/artica-postfix/dansguardian-stats4")) {
		events_tail("dansguardian-stats2:: -> glob failed in Line: /var/log/artica-postfix/dansguardian-stats4 ".__LINE__);
		return ;
	}		
	
	$c=0;
	$t=0;
	$q=new mysql_squid_builder();
	$q->CheckTables();
	if($q->MysqlFailed){events_tail("dansguardian-stats2:: Mysql connection failed, aborting.... Line: ".__LINE__);return;}
	
	$tables=array();
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		
		$targetFile="/var/log/artica-postfix/dansguardian-stats4/$filename";
		if(!is_file($targetFile)){events_tail("dansguardian-stats4:: $c -> $filename is not an sql file  Line: ".__LINE__);continue;}
		$t++;
		$array=unserialize(@file_get_contents($targetFile));
		@unlink($targetFile);
		if(!is_array($array)){events_tail("dansguardian-stats2:: $filename is not an array line:" .__LINE__);continue;}
		$userid=$array["userid"];
		if(trim($userid)=="-"){$userid=null;}
		$ipaddr=$array["ipaddr"];
		$uri=$array["uri"];
		if(preg_match("#^(?:[^/]+://)?([^/:]+)#",$uri,$re)){$sitename=$re[1];if(preg_match("#^www\.(.+)#",$sitename,$ri)){$sitename=$ri[1];}}
		if(!isset($GLOBALS["CATEGORIZED"][$sitename])){$GLOBALS["CATEGORIZED"][$sitename]=$q->GET_CATEGORIES($sitename);}
		$EVENT=$array["EVENT"];
		$WHY=$array["WHY"];
		$EXPLAIN=$array["EXPLAIN"];
		$BLOCKTYPE=$array["BLOCKTYPE"];
		$RULEID=$array["RULEID"];
		$TIME=$array["TIME"];;
		$mtime=strtotime($TIME);
		if($userid<>null){$ipaddr=$userid;}
		$category=addslashes($GLOBALS["CATEGORIZED"][$sitename]);
		if(!isset($RULESD[$RULEID])){events_tail("dansguardian-stats4:: Unable to find rule name for RuleID:`$RULEID` Line:".__LINE__);continue;}
		$rulename=addslashes($RULESD[$RULEID]);
		$uri=addslashes($uri);
		$EVENT=addslashes($EVENT);
		$WHY=addslashes($WHY);
		$EXPLAIN=addslashes($EXPLAIN);
		$BLOCKTYPE=addslashes($BLOCKTYPE);
		$tableblock=date('Ymd',$mtime)."_blocked";
		$tables[$tableblock][]="('$TIME','$ipaddr','$sitename','$category','$rulename','','$uri','$EVENT','$WHY','$EXPLAIN','$BLOCKTYPE')";
		
		
	}
	if($t==0){return;}
	events_tail("dansguardian-stats4:: Parsed $t files Line: ".__LINE__);
	if(count($tables)==0){events_tail("dansguardian-stats4:: tables is not an array Line: ".__LINE__);return;}
	
	
	while (list ($tablename, $queries) = each ($tables) ){
		events_tail("dansguardian-stats4:: $tablename -> " .count($queries). " queries Line: ".__LINE__);
		$sql="INSERT IGNORE INTO $tablename (`zDate`, `client` , `website`, `category` , `rulename` , `public_ip` , `uri` , `event` , `why` , `explain` , `blocktype`)
		VALUES ".@implode(",", $queries);
		$q->QUERY_SQL($sql);
		$data=array("ERROR"=>$q->mysql_error,"SQL"=>$sql);
		if(!$q->ok){
			events_tail("dansguardian-stats4:: $tablename -> $q->mysql_error Line: ".__LINE__);
			@file_put_contents($failedir."/". md5($sql), serialize($data));}
		
		
	}
	
  	
}


function ufdbguard_blocks_errors($nopid=false){
	$unix=new unix();
	if($nopid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}
		$t=0;

	}

	
	if(!is_dir("/var/log/artica-postfix/ufdbguard-blocks-errors")){return;}
	
	if (!$handle = opendir("/var/log/artica-postfix/ufdbguard-blocks-errors")) {
		events_tail("ufdbguard_blocks_errors:: -> glob failed in Line: /var/log/artica-postfix/ufdbguard-blocks-errors ".__LINE__);
		return ;
	}
	
	$q=new mysql_squid_builder();
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="/var/log/artica-postfix/ufdbguard-blocks-errors/$filename";
		$queries=unserialize(@file_get_contents($targetFile));
		if(!is_array($queries)){
			events_tail("PaseUdfdbGuard:: $targetFile, not an array....");
			@unlink($targetFile);
			continue;
		}
		
		if(!preg_match("#(.+?)\.([0-9]+)#", $filename,$re)){
			events_tail("PaseUdfdbGuard:: $targetFile, No compatible file");
			@unlink($targetFile);
			continue;
		}
		
		$tablename=$re[1];
		$q->CheckTablesBlocked_day(0,$tablename);
		$prefix="INSERT IGNORE INTO `$tablename` (`zmd5`,`client`,`website`,`category`,`rulename`,`public_ip`,`why`,`blocktype`,`hostname`,`uid`,`MAC`,`uri`,`zDate`) VALUES ";
		if(!$q->QUERY_SQL($prefix.@implode(",", $queries))){
			ToSyslog("Fatal line ".__LINE__." ".$q->mysql_error);
			$time=$unix->file_time_min($targetFile);
			if($time>5760){ @unlink($targetFile); }
			continue;
		}
		@unlink($targetFile);
		
	}


}

function ToSyslog($text){

	$LOG_SEV=LOG_INFO;
	if(function_exists("openlog")){openlog("danguardian-injector", LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
	if(function_exists("closelog")){closelog();}
}

function PaseUdfdbGuardnew(){
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	@mkdir("/var/log/artica-postfix/ufdbguard-blocks",0777,true);
	@mkdir("/var/log/artica-postfix/ufdbguard-blocks-errors",0777,true);
	$GLOBALS["EnableRemoteStatisticsAppliance"]=$EnableRemoteStatisticsAppliance;
	
	
	$RemoteStatisticsApplianceSettings=unserialize(base64_decode($sock->GET_INFO("RemoteStatisticsApplianceSettings")));
	if(!isset($RemoteStatisticsApplianceSettings["PORT"])){$RemoteStatisticsApplianceSettings["PORT"]=null;}
	if(!isset($RemoteStatisticsApplianceSettings["SSL"])){$RemoteStatisticsApplianceSettings["SSL"]=null;}
	if(!isset($RemoteStatisticsApplianceSettings["SERVER"])){$RemoteStatisticsApplianceSettings["SERVER"]=null;}
	
	if(!is_numeric($RemoteStatisticsApplianceSettings["SSL"])){$RemoteStatisticsApplianceSettings["SSL"]=1;}
	if(!is_numeric($RemoteStatisticsApplianceSettings["PORT"])){$RemoteStatisticsApplianceSettings["PORT"]=9000;}
	$GLOBALS["REMOTE_SSERVER"]=$RemoteStatisticsApplianceSettings["SERVER"];
	$GLOBALS["REMOTE_SPORT"]=$RemoteStatisticsApplianceSettings["PORT"];
	$GLOBALS["REMOTE_SSL"]=$RemoteStatisticsApplianceSettings["SSL"];
	$unix=new unix();
	$hostname=$unix->hostname_g();	
	$BIGARRAY=array();
	if($EnableRemoteStatisticsAppliance==0){
		$q=new mysql_squid_builder();
		$q->CheckTables();
	}	

	
	if(!is_dir("/var/log/artica-postfix/ufdbguard-blocks")){return;}
	
	if (!$handle = opendir("/var/log/artica-postfix/ufdbguard-blocks")) {
		events_tail("PaseUdfdbGuardnew:: -> glob failed in Line: /var/log/artica-postfix/ufdbguard-blocks ".__LINE__);
		return ;
	}
	$smtp_notifications_body=array();
	//$sql="INSERT INTO `$table` (`client`,`website`,`category`,`rulename`,`public_ip`,`why`,`blocktype`,`hostname`,`uid`,`MAC`) VALUES";
	
	$c=0;
	events_tail("PaseUdfdbGuardnew:: parsing  /var/log/artica-postfix/ufdbguard-blocks directory...");
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="/var/log/artica-postfix/ufdbguard-blocks/$filename";	
		$array=unserialize(@file_get_contents($targetFile));
		if(!is_array($array)){
			events_tail("PaseUdfdbGuard:: $targetFile, not an array....");
			@unlink($targetFile);
			continue;
		}
		
		
		while (list ($key, $line) = each ($array) ){
			$line=trim($line);
			if(is_numeric($line)){
				events_tail("$filename: $line = `numeric`");
				continue;}
			$line=mysql_escape_string2($line);
			$line=str_replace("'", "`", $line);
			$array[$key]=$line;
			events_tail("$filename: array[$key] = `$line`");
		}
		
		$uid=$array["uid"];
		$MAC=$array["MAC"];
		$time=$array["TIME"];
		$category=$array["category"];
		$rulename=$array["rulename"];
		$public_ip=$array["public_ip"];
		$blocktype=$array["blocktype"];
		$uri=addslashes($array["uri"]);
		$why=$array["why"];
		$Clienthostname=$array["hostname"];
		$www=$array["website"];
		$local_ip=$array["client"];		
		$table=date('Ymd',$time)."_blocked";
		if($www==null){
			events_tail("$www is null");
			@unlink($targetFile);
			continue;
		}
		
		$textBody="\r\n***************************************\r\n";
		if($www<>null){$textBody=$textBody."Blocked on: ".date("Y-m-d H:i:s",$time)."\r\n";}
		if($www<>null){$textBody=$textBody."Web site name: $www [$public_ip]\r\n";}
		$textBody=$textBody."Rule: $rulename - $blocktype ($why) \r\n";
		if($category<>null){$textBody=$textBody."Category: $category\r\n";}
		if($uri<>null){$textBody=$textBody."URL: $uri\r\n";}
		if($uid<>null){$textBody=$textBody."User: $uid\r\n";}
		if($Clienthostname<>null){$textBody=$textBody."Hostname: $Clienthostname\r\n";}
		if($MAC<>null){$textBody=$textBody."MAC Address: $MAC\r\n";}
		$smtp_notifications_body[]=$textBody;
		$zDate=date("Y-m-d H:i:s",$time);
		
		$zmd5=md5("$zDate$local_ip$uri$category$rulename$public_ip");
		$sql="('$zmd5','$local_ip','$www','$category','$rulename','$public_ip','$why','$blocktype','$Clienthostname','$uid','$MAC','$uri','$zDate')";
		events_tail($sql);
		
		if(!isset($checked[$table])){
			if(!$q->CheckTablesBlocked_day(0,$table)){
				events_tail("PaseUdfdbGuard:: Fatal CheckTablesBlocked_day($table)...");
				$smtp_notifications_body[]="Notice: An error as been occured $q->mysql_error while adding the event in $table table\r\n";
				ufdguard_send_notifications($smtp_notifications_body);
				return;
			}
		}
		

		
		$BIGARRAY[$table][]=$sql;
		@unlink($targetFile);
		$c++;
		if($c>500){break;}
	}
	events_tail("PaseUdfdbGuardnew:: BIGARRAY = ".count($BIGARRAY)."...");
	if(count($BIGARRAY)==0){return;}
	$q=new mysql_squid_builder();
	$ev=0;
	while (list ($tablename, $queries) = each ($BIGARRAY) ){
		$q->CheckTablesBlocked_day(0,$tablename);
		$prefix="INSERT IGNORE INTO `$tablename` (`zmd5`,`client`,`website`,`category`,`rulename`,`public_ip`,`why`,`blocktype`,`hostname`,`uid`,`MAC`,`uri`,`zDate`) VALUES ";
		if(!$q->QUERY_SQL($prefix.@implode(",", $queries))){
			events_tail("PaseUdfdbGuardnew:: Fatal $q->mysql_error");
			@file_put_contents("/var/log/artica-postfix/ufdbguard-blocks-errors/$tablename.".time(), serialize($queries));
			ufdbguard_admin_events("$q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "injector");
		}
		$ev=$ev+count($queries);
	}
	
	if(count($smtp_notifications_body)>0){
		ufdguard_send_notifications($smtp_notifications_body);
	}
	events_tail("PaseUdfdbGuardnew:: $ev events done");
}

function ufdguard_send_notifications($array=array()){
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."\n";}
	if(count($array)==0){$array[]="This is a test notification from UfdBguard parser...\r\n";}
	$sock=new sockets();
	
	$UfdbguardSMTPNotifs=unserialize(base64_decode($sock->GET_INFO("UfdbguardSMTPNotifs")));
	if(!is_numeric($UfdbguardSMTPNotifs["ENABLED"])){$UfdbguardSMTPNotifs["ENABLED"]=0;}
	if($UfdbguardSMTPNotifs["ENABLED"]==0){return;}
	include_once(dirname(__FILE__) . '/ressources/class.mail.inc');
	include_once(dirname(__FILE__)."/ressources/smtp/class.phpmailer.inc");	
	$users=new usersMenus();
	$smtp_dest=$UfdbguardSMTPNotifs["smtp_dest"];
	$smtp_sender=$UfdbguardSMTPNotifs["smtp_sender"];
	if($smtp_dest==null){return;}
	if($smtp_sender==null){$smtp_sender="root@artica.localhost.localdomain";}
	
	$subject="[$users->hostname]: Web filtering ". count($array)." event(s)";
	$text=@implode("\r\n", $array);
	$mail = new PHPMailer(true);
	$mail->IsSMTP();
	$mail->AddAddress($smtp_dest,$smtp_dest);
	$mail->AddReplyTo($smtp_sender,$smtp_sender);
	$mail->From=$smtp_sender;
	$mail->Subject=$subject;
	$mail->Body=$text;
	$mail->Host=$UfdbguardSMTPNotifs["smtp_server_name"];
	$mail->Port=$UfdbguardSMTPNotifs["smtp_server_port"];
	
	if(($UfdbguardSMTPNotifs["smtp_auth_user"]<>null) && ($UfdbguardSMTPNotifs["smtp_auth_passwd"]<>null)){
		$mail->SMTPAuth=true;
		$mail->Username=$UfdbguardSMTPNotifs["smtp_auth_user"];
		$mail->Password=$UfdbguardSMTPNotifs["smtp_auth_passwd"];
		if($UfdbguardSMTPNotifs["tls_enabled"]==1){$mail->SMTPSecure = 'tls';}
		if($UfdbguardSMTPNotifs["ssl_enabled"]==1){$mail->SMTPSecure = 'ssl';}
		
		
	}
	
	$mail->Send();
	
	
}


function PaseUdfdbGuard(){
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
	if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}
	if($UnlockWebStats==1){$EnableRemoteStatisticsAppliance=0;}	
	
	@mkdir("/var/log/artica-postfix/ufdbguard-queue",0777,true);
	shell_exec("/bin/chmod 777 /var/log/artica-postfix/ufdbguard-queue");
	
	$GLOBALS["EnableRemoteStatisticsAppliance"]=$EnableRemoteStatisticsAppliance;
	$RemoteStatisticsApplianceSettings=unserialize(base64_decode($sock->GET_INFO("RemoteStatisticsApplianceSettings")));
	if(!isset($RemoteStatisticsApplianceSettings["PORT"])){$RemoteStatisticsApplianceSettings["PORT"]=null;}
	if(!isset($RemoteStatisticsApplianceSettings["SSL"])){$RemoteStatisticsApplianceSettings["SSL"]=null;}
	if(!isset($RemoteStatisticsApplianceSettings["SERVER"])){$RemoteStatisticsApplianceSettings["SERVER"]=null;}	
	if(!is_numeric($RemoteStatisticsApplianceSettings["SSL"])){$RemoteStatisticsApplianceSettings["SSL"]=1;}
	if(!is_numeric($RemoteStatisticsApplianceSettings["PORT"])){$RemoteStatisticsApplianceSettings["PORT"]=9000;}
	$GLOBALS["REMOTE_SSERVER"]=$RemoteStatisticsApplianceSettings["SERVER"];
	$GLOBALS["REMOTE_SPORT"]=$RemoteStatisticsApplianceSettings["PORT"];
	$GLOBALS["REMOTE_SSL"]=$RemoteStatisticsApplianceSettings["SSL"];
	$unix=new unix();
	$hostname=$unix->hostname_g();	
	
	if($EnableRemoteStatisticsAppliance==0){
		$q=new mysql_squid_builder();
		$q->CheckTables();
	}
	
	$count=0;
	$total=0;
	$tableblock=date('Ymd')."_blocked";
	$f=array();
	$PREFIX="INSERT INTO `$tableblock` (client,website,category,rulename,public_ip,`why`,`blocktype`,`hostname`) VALUES";
	events_tail("PaseUdfdbGuard:: parsing /var/log/artica-postfix/ufdbguard-queue Line: ".__LINE__);
	foreach (glob("/var/log/artica-postfix/ufdbguard-queue/*.sql") as $filename) {
		events_tail("dansguardian-stats:: parsing $filename Line: ".__LINE__);
		$content=@file_get_contents($filename);
		if($content==null){events_tail("PaseUdfdbGuard:: Fatal $filename is empty !");@unlink($filename);}
		$f[]=$content;
		@unlink($filename);
		$count++;
		$total++;
		if($count>500){
			events_tail("PaseUdfdbGuard:: $count -> send to mysql");
			$count=0;
			$sql=$PREFIX." ".@implode(",",$f);
			$f=array();
			if($EnableRemoteStatisticsAppliance==1){PaseUdfdbGuard_send_remote($sql);continue;}
			$q->QUERY_SQL($sql);
			if(!$q->ok){
				@file_put_contents("/var/log/artica-postfix/ufdbguard-queue/".md5($sql).".error",$sql);
				events_tail("PaseUdfdbGuard:: Fatal $q->mysql_error");
				writelogs($q->mysql_error."\n",$sql,__FILE__,__LINE__);
			}
			
			$sql=null;
			continue;
		}
	}
	
	if(count($f)>0){
		$sql=$PREFIX." ".@implode(",",$f);
		if($EnableRemoteStatisticsAppliance==0){
			$q->QUERY_SQL($sql);
				if(!$q->ok){
				@file_put_contents("/var/log/artica-postfix/ufdbguard-queue/".md5($sql).".error",$sql);
				events_tail("PaseUdfdbGuard:: Fatal $q->mysql_error");	
			}
			$sql=null;
		}else{
			PaseUdfdbGuard_send_remote($sql);
		}
	}
	
	events_tail("PaseUdfdbGuard:: $total files.");
	
	
}

function ParseUdfdbGuard_failed(){
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
	if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}
	if($UnlockWebStats==1){$EnableRemoteStatisticsAppliance=0;}	
	$GLOBALS["EnableRemoteStatisticsAppliance"]=$EnableRemoteStatisticsAppliance;
	$RemoteStatisticsApplianceSettings=unserialize(base64_decode($sock->GET_INFO("RemoteStatisticsApplianceSettings")));
	if(!is_numeric($RemoteStatisticsApplianceSettings["SSL"])){$RemoteStatisticsApplianceSettings["SSL"]=1;}
	if(!is_numeric($RemoteStatisticsApplianceSettings["PORT"])){$RemoteStatisticsApplianceSettings["PORT"]=9000;}
	$GLOBALS["REMOTE_SSERVER"]=$RemoteStatisticsApplianceSettings["SERVER"];
	$GLOBALS["REMOTE_SPORT"]=$RemoteStatisticsApplianceSettings["PORT"];
	$GLOBALS["REMOTE_SSL"]=$RemoteStatisticsApplianceSettings["SSL"];	
	foreach (glob("/var/log/artica-postfix/ufdbguard-queue-failed/*") as $filename) {
		$sql=@file_get_contents($filename);
		if($sql==null){events_tail("ParseUdfdbGuard_failed:: Fatal $filename is empty !");@unlink($filename);}
		@unlink($filename);
		if($EnableRemoteStatisticsAppliance==1){PaseUdfdbGuard_send_remote($sql);continue;}
		$q=new mysql_squid_builder();
		$q->QUERY_SQL($sql);
		if(!$q->ok){PaseUdfdbGuard_send_failed($sql);continue;}
	}
	
}
function streamget_send_remote($sql,$hostname){
	_LoadStatisticsSettings();
	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
	if($GLOBALS["REMOTE_SSL"]==1){$refix="https";}else{$refix="http";}
	$uri="$refix://{$GLOBALS["REMOTE_SSERVER"]}:{$GLOBALS["REMOTE_SPORT"]}/squid.blocks.listener.php";
	$curl=new ccurl($uri,true);
	$f=base64_encode($sql);
	$curl->parms["STREAM_LINE"]=$f;
	$curl->parms["HOSTNAME"]=$hostname;
	events_tail("streamget_send_remote:: send ".strlen($sql)." bytes to `$uri`");
	if(!$curl->get()){events_tail("FAILED ".$curl->error);return;}
	if(preg_match("#<ANSWER>OK</ANSWER>#is",$curl->data)){events_tail("streamget_send_remote():: SUCCESS...");
	return true;}	
	events_tail("streamget_send_remote():: FAILED ".$curl->data."...");
	

}

function PaseUdfdbGuard_send_remote($sql){
	events_tail("PaseUdfdbGuard:: send ".strlen($sql)." bytes...");
	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
	if($GLOBALS["REMOTE_SSL"]==1){$refix="https";}else{$refix="http";}
	$uri="$refix://{$GLOBALS["REMOTE_SSERVER"]}:{$GLOBALS["REMOTE_SPORT"]}/squid.blocks.listener.php";
	$curl=new ccurl($uri,true);
	$f=base64_encode($sql);
	$curl->parms["STATS_LINE"]=$f;
	if(!$curl->get()){PaseUdfdbGuard_send_failed($sql);echo "FAILED ".$curl->error."\n";return;}
	if(preg_match("#<ANSWER>OK</ANSWER>#is",$curl->data)){events_tail("PaseUdfdbGuard:: SUCCESS...");return true;}	
	events_tail("PaseUdfdbGuard:: FAILED ".$curl->data."...");
	PaseUdfdbGuard_send_failed($sql);	

}
function PaseUdfdbGuard_send_failed($sql){
	if(!is_dir("/var/log/artica-postfix/ufdbguard-queue-failed")){@mkdir("/var/log/artica-postfix/ufdbguard-queue-failed");}
	@file_put_contents("/var/log/artica-postfix/ufdbguard-queue-failed/".md5($sql)."sql",$sql);
}



function ParseSitesInfos(){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.'.pid';
	$pid=@file_get_contents($pidfile);
	$unix=new unix();
	if($unix->process_exists($pid)){return null;}
	@file_put_contents($pidfile,getmypid());	
	
	$count=0;
	events_tail("dansguardian-stats:: parsing /var/log/artica-postfix/dansguardian-stats3");
	foreach (glob("/var/log/artica-postfix/dansguardian-stats3/*") as $filename) {
		if($GLOBALS["VERBOSE"]){echo "$filename\n";}
		events_tail("dansguardian-stats:: parsing $filename Line: ".__LINE__);
		$datas=unserialize(@file_get_contents("$filename"));
		if(!is_array($datas)){events_tail(basename($filename))." is not an array";@unlink($filename);continue;}
		usleep(20000);
		@unlink($filename);
		$count++;
	}
	events_tail("dansguardian-stats3:: $count analyzed files.");
	
}

function include_tpl_file($path,$category){
	$sock=new sockets();
	$unix=new unix();
	$uuid=$unix->GetUniqueID();
	if($uuid==null){echo "UUID=NULL; Aborting";return;}
	if($category==null){echo "CATEGORY=NULL; Aborting";return;}				
	if(!is_file($path)){echo "$path no such file\n";return;}
	
	$q=new mysql_squid_builder();
	$q->CreateCategoryTable($category);
	$TableDest="category_".$q->category_transform_name($category);	
	$array=array();
	$f=@explode("\n",@file_get_contents($path));
	$count_websites=count($f);
	$i=0;$d=0;$group=0;
	$prefix="INSERT IGNORE INTO $TableDest (zmd5,zDate,category,pattern,uuid) VALUES";
	while (list ($index, $website) = each ($f) ){
		$i++;$d++;
		if($d>1000){$group=$group+$d;events_tail("include_tpl_file($category):: importing $group sites...");$d=0;}
		if($website==null){return;}
		$www=trim(strtolower($website));
		if(preg_match("#www\.(.+?)$#i",$www,$re)){$www=$re[1];}
		$md5=md5($www.$category);	
		if($array[$md5]){echo "$www already exists\n";continue;}
		$enabled=1;
		$sql_add[]="('$md5',NOW(),'$category','$www','$uuid')";		
		$array[$md5]=true;
		if($GLOBALS["SIMULATE"]){echo "$i/$count_websites: $sql_add\n";continue;}
		if(count($sql_add)>500){
			$sql=$prefix.@implode(",",$sql_add);
			$q->QUERY_SQL($sql);
			if(!$q->ok){echo "$i/$count_websites Failed: $www\n";}else{echo "$i/$count_websites Success: $www\n";}
			$sql_add=array();
		}
	}
	
if(count($sql_add)>0){
			$sql=$prefix.@implode(",",$sql_add);
			$q->QUERY_SQL($sql);
			if(!$q->ok){echo "$i/$count_websites Failed: $www\n";}else{echo "$i/$count_websites Success: $www\n";}
			$sql_add=array();
		}	
	
	
echo " -------------------------------------------------\n";	
echo count($array)." websites done\n";
echo " -------------------------------------------------\n";	
}



function events_tail($text){
		if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
		//if($GLOBALS["VERBOSE"]){echo "$text\n";}
		$pid=@getmypid();
		$date=@date("H:i:s");
		$logFile="/var/log/artica-postfix/auth-tail.debug";
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		$GLOBALS["CLASS_UNIX"]->events(basename(__FILE__)." $date $text");
		@fwrite($f, "$pid ".basename(__FILE__)." $date $text\n");
		@fclose($f);	
		}




		
		
		
?>