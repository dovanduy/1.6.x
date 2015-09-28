<?php
$GLOBALS["COMMANDLINE"]=implode(" ",$argv);
if(strpos($GLOBALS["COMMANDLINE"],"--verbose")>0){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;$GLOBALS["DEBUG"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/framework/class.settings.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.syslog.inc");
include_once(dirname(__FILE__)."/ressources/class.familysites.inc");
include_once(dirname(__FILE__).'/ressources/class.mount.inc');
$GLOBALS["FORCE"]=false;
$GLOBALS["EXECUTED_AS_ROOT"]=true;
$GLOBALS["RUN_AS_DAEMON"]=false;
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["DISABLE_WATCHDOG"]=false;
if(preg_match("#--nowachdog#",$GLOBALS["COMMANDLINE"])){$GLOBALS["DISABLE_WATCHDOG"]=true;}
if(preg_match("#--force#",$GLOBALS["COMMANDLINE"])){$GLOBALS["FORCE"]=true;}
if(preg_match("#--verbose#",$GLOBALS["COMMANDLINE"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

if($argv[1]=="--connect"){connect_from($argv[2]);exit;}
if($argv[1]=="--pfl"){pflogsumm($argv[2]);exit;}


$targetfile="/home/postfix/logrotate/".date("Y-m-d").".log";
$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
if(isset($argv[1])){
	if(!is_file($argv[1])){
		echo "Unable to understand {$argv[1]}\n";die();
	}
	$targetfile=$argv[1];
}

@mkdir("/home/postfix/logrotate",0755,true);



$q=new mysql();
$hier=$q->HIER();
$targetcompressed="/home/postfix/logrotate/$hier.gz";
$unix=new unix();


if(is_file($targetfile)){
	if(!connect_from($targetfile)){
		postfix_admin_mysql(0, "FATAL! $targetfile connect_from() failed", null,__FILE__,__LINE__);
		return;
	}
	if(!pflogsumm($targetfile)){
		postfix_admin_mysql(0, "FATAL! $targetfile pflogsumm() failed", null,__FILE__,__LINE__);
		return;
	}
	if(!$unix->compress($targetfile, $targetcompressed)){
		@unlink($targetcompressed);
		return;
	}
	@unlink($targetfile);
	
	
}



if(is_file($targetcompressed)){
	echo "$targetcompressed exists, abort\n";
	die();
}



if(!@copy("/var/log/mail.log", $targetfile)){
	postfix_admin_mysql(0, "FATAL! unable to rotate mail.log", null,__FILE__,__LINE__);
	die();
}

$echo=$unix->find_program("echo");
shell_exec("$echo \"\" >/var/log/mail.log");
shell_exec("/etc/init.d/rsyslog restart");
$php=$unix->LOCATE_PHP5_BIN();
$nohup=$unix->find_program("nohup");
shell_exec("$php $nohup ".__FILE__." >/dev/null 2>&1 &");


function connect_from($logpath){
	$unix=new unix();
	
	$q=new mysql();
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `smtpstats_day` (
	`zmd5` VARCHAR(90) NOT NULL PRIMARY KEY,
	`zDate` DATETIME,
	`domain` VARCHAR(128),
	`GREY` BIGINT UNSIGNED,
	`BLACK` BIGINT UNSIGNED,
	`CNX` BIGINT UNSIGNED,
	`HOSTS` BIGINT UNSIGNED,
	`IPS` BIGINT UNSIGNED,
	`INFOS` TINYTEXT,
	KEY `zDate` (`zDate`),
	KEY `domain` (`domain`),
	KEY `GREY` (`GREY`),
	KEY `BLACK` (`BLACK`),
	KEY `CNX` (`CNX`),
	KEY `IPS` (`IPS`),
	KEY `HOSTS` (`HOSTS`)
	) ENGINE=MYISAM;","artica_events" );
	if(!$q->ok){echo $q->mysql_error."\n";return;}
	
	
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `smtpcdir_day` (
	`zmd5` VARCHAR(90) NOT NULL PRIMARY KEY,
	`zDate` DATETIME,
	`CDIR` VARCHAR(90),
	`GREY` BIGINT UNSIGNED,
	`BLACK` BIGINT UNSIGNED,
	`CNX` BIGINT UNSIGNED,
	`HOSTS` BIGINT UNSIGNED,
	`DOMAINS` BIGINT UNSIGNED,
	`INFOS` TINYTEXT,
	KEY `zDate` (`zDate`),
	KEY `DOMAINS` (`DOMAINS`),
	KEY `GREY` (`GREY`),
	KEY `BLACK` (`BLACK`),
	KEY `CNX` (`CNX`),
	KEY `CDIR` (`CDIR`),
	KEY `HOSTS` (`HOSTS`)
	) ENGINE=MYISAM;","artica_events" );
	if(!$q->ok){echo $q->mysql_error."\n";return;}	
	
	
	$grep=$unix->find_program("grep");
	$tmpfile=$unix->FILE_TEMP();	
	shell_exec("$grep -e \"smtpd.*: connect from\" $logpath >$tmpfile");
	
	$fp = @fopen($tmpfile, "r");
	if(!$fp){return false;}
	$t=array();
	
	$fam=new familysite();
	
	while(!feof($fp)){
		$line = trim(fgets($fp, 4096));
		$line=str_replace("\r\n", "", $line);
		$line=str_replace("\n", "", $line);
		$line=str_replace("\r", "", $line);
		$line=trim($line);
		if(!preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+)\s+.*?\[[0-9]+\]:\s+connect from\s+(.+?)\[([0-9\.]+)\]#", $line,$re)){continue;}
		$date=strtotime("{$re[1]} {$re[2]} {$re[3]}");
		$ipaddr=$re[5];
		$day=date("Y-m-d",$date);
		$NETZ=explode(".",$ipaddr);
		$network="{$NETZ[0]}.{$NETZ[1]}.{$NETZ[2]}.0/24";
		
		
		$hostname=$re[4];
		$familysite=$fam->GetFamilySites($hostname);
		
		if(!isset($MAINNETS[$day][$network]["CNX"])){
			$MAINNETS[$day][$network]["CNX"]=1;
		}else{
			$MAINNETS[$day][$network]["CNX"]=$MAINNETS[$day][$network]["CNX"]+1;
		}
		
		if(!isset($MAINNETS[$day][$network]["FAM"][$familysite])){
			$MAINNETS[$day][$network]["FAM"][$familysite]=1;
		}else{
			$MAINNETS[$day][$network]["FAM"][$familysite]=$MAINNETS[$day][$network]["FAM"][$familysite]+1;
		}
	
		
		
		if(!isset($MAIN[$day][$familysite]["IPS"][$ipaddr])){
			$MAIN[$day][$familysite]["IPS"][$ipaddr]=1;
		}else{
			$MAIN[$day][$familysite]["IPS"][$ipaddr]=$MAIN[$day][$familysite]["IPS"][$ipaddr]+1;
		}
		
		if(!isset($MAIN[$day][$familysite]["COUNT"])){
			$MAIN[$day][$familysite]["COUNT"]=1;
		}else{
			$MAIN[$day][$familysite]["COUNT"]=$MAIN[$day][$familysite]["COUNT"]+1;
		}
		
		if(!isset($MAIN[$day][$familysite]["HOSTS"][$hostname])){
			$MAIN[$day][$familysite]["HOSTS"][$hostname]=1;
		}else{
			$MAIN[$day][$familysite]["HOSTS"][$hostname]=$MAIN[$day][$familysite]["HOSTS"][$hostname]+1;
		}
		
		//echo date("Y-m-d")." $hostname $ipaddr\n";
	}
	
	@fclose($fp);
	@unlink($tmpfile);
	
	shell_exec("$grep -e \"NOQUEUE: milter-reject: RCPT from\" $logpath >$tmpfile");
	$fp = @fopen($tmpfile, "r");
	if(!$fp){return false;}
	while(!feof($fp)){
		$line = trim(fgets($fp, 4096));
		$line=str_replace("\r\n", "", $line);
		$line=str_replace("\n", "", $line);
		$line=str_replace("\r", "", $line);
		$line=trim($line);
		
		if(!preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+)\s+.*?\[[0-9]+\]:\s+NOQUEUE: milter-reject: RCPT from\s+(.*?)\[([0-9\.]+)\]:\s+([0-9]+)\s+#", $line,$re)){
			echo "NO MATCH $line\n";
			continue;}
		$date=strtotime("{$re[1]} {$re[2]} {$re[3]}");
		$hostname=$re[4];
		$ipaddr=$re[5];
		$CODE=$re[6];
		$day=date("Y-m-d",$date);
		$familysite=$fam->GetFamilySites($hostname);
		
		$NETZ=explode(".",$ipaddr);
		$network="{$NETZ[0]}.{$NETZ[1]}.{$NETZ[2]}.0/24";
		
		if(!isset($MAINNETS[$day][$network]["FAM"][$familysite])){
			$MAINNETS[$day][$network]["FAM"][$familysite]=1;
		}else{
			$MAINNETS[$day][$network]["FAM"][$familysite]=$MAINNETS[$day][$network]["FAM"][$familysite]+1;
		}
		
		
		if($CODE==451){
			
			if(!isset($MAINNETS[$day][$network]["GREY"])){
				$MAINNETS[$day][$network]["GREY"]=1;
			}else{
				$MAINNETS[$day][$network]["GREY"]=$MAINNETS[$day][$network]["GREY"]+1;
			}
			
			if(!isset($MAIN[$day][$familysite]["GREY"])){
				$MAIN[$day][$familysite]["GREY"]=1;
			}else{
				$MAIN[$day][$familysite]["GREY"]=$MAIN[$day][$familysite]["GREY"]+1;
			}
		}
		if($CODE==551){
			if(!isset($MAIN[$day][$familysite]["BLACK"])){
				$MAIN[$day][$familysite]["BLACK"]=1;
			}else{
				$MAIN[$day][$familysite]["BLACK"]=$MAIN[$day][$familysite]["BLACK"]+1;
			}
			
			if(!isset($MAINNETS[$day][$network]["BLACK"])){
				$MAINNETS[$day][$network]["BLACK"]=1;
			}else{
				$MAINNETS[$day][$network]["BLACK"]=$MAINNETS[$day][$network]["BLACK"]+1;
			}
			
		}		
		
	}
	
	@fclose($fp);
	@unlink($tmpfile);
	
	shell_exec("$grep -e \"NOQUEUE: reject: RCPT from\" $logpath >$tmpfile");
	$fp = @fopen($tmpfile, "r");
	if(!$fp){return false;}
	while(!feof($fp)){
		$line = trim(fgets($fp, 4096));
		$line=str_replace("\r\n", "", $line);
		$line=str_replace("\n", "", $line);
		$line=str_replace("\r", "", $line);
		$line=trim($line);
		

		if(!preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+)\s+.*?\[[0-9]+\]:\s+NOQUEUE: reject: RCPT from\s+(.*?)\[([0-9\.]+)\]:\s+([0-9]+)\s+#", $line,$re)){
			echo "NO MATCH $line\n";
			continue;}
			$date=strtotime("{$re[1]} {$re[2]} {$re[3]}");
			$hostname=$re[4];
			$ipaddr=$re[5];
			$CODE=$re[6];
			$day=date("Y-m-d",$date);
			$familysite=$fam->GetFamilySites($hostname);
			$NETZ=explode(".",$ipaddr);
			$network="{$NETZ[0]}.{$NETZ[1]}.{$NETZ[2]}.0/24";
		
			if(($CODE==551) OR ($CODE==554)){
				if(!isset($MAIN[$day][$familysite]["BLACK"])){
					$MAIN[$day][$familysite]["BLACK"]=1;
				}else{
					$MAIN[$day][$familysite]["BLACK"]=$MAIN[$day][$familysite]["BLACK"]+1;
				}
				
				if(!isset($MAINNETS[$day][$network]["BLACK"])){
					$MAINNETS[$day][$network]["BLACK"]=1;
				}else{
					$MAINNETS[$day][$network]["BLACK"]=$MAINNETS[$day][$network]["BLACK"]+1;
				}
				
			}
		
	}
	@fclose($fp);
	@unlink($tmpfile);
	
	
	
	
	$prefix="INSERT IGNORE INTO smtpstats_day (`zmd5`,`zDate`,`domain`,`GREY`,`BLACK`,`CNX`,`HOSTS`,`IPS`,`INFOS`) VALUES ";
	
	while (list ($zDate, $ARRAY) = each ($MAIN) ){
		while (list ($domain, $INFOS) = each ($ARRAY) ){
			$GREY=0;
			if(!isset($INFOS["BLACK"])){$INFOS["BLACK"]=0;}
			if(!isset($INFOS["GREY"])){$INFOS["GREY"]=0;}
			$HOSTS=count($INFOS["HOSTS"]);
			$IPS=count($INFOS["IPS"]);
			$BLACK=intval($INFOS["BLACK"]);
			$CNX=intval($INFOS["COUNT"]);
			$INFO["IPS"]=$INFOS["IPS"];
			$INFO["HOSTS"]=$INFOS["HOSTS"];
			$infotext=mysql_escape_string2(serialize($INFO));
			
			if($GLOBALS["VERBOSE"]){echo "$zDate: $domain hosts:$HOSTS ips:$IPS blacklisted:$BLACK greylisted:$GREY cnx:$CNX $infotext\n";}
			$md5=md5("$zDate$domain$HOSTS$IPS$BLACK$GREY$CNX$infotext");
			
			$f[]="('$md5','$zDate','$domain','$GREY','$BLACK','$CNX','$HOSTS','$IPS','$infotext')";
			if(count($f)>500){
				$q->QUERY_SQL($prefix.@implode(",", $f),"artica_events");
				if(!$q->ok){echo $q->mysql_error."\n";return;}
				$f=array();
			}
			
		}
		
		
	}
	
	if(count($f)>0){
		$q->QUERY_SQL($prefix.@implode(",", $f),"artica_events");
		if(!$q->ok){echo $q->mysql_error."\n";return;}
		$f=array();
	}
	
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `smtpcdir_day` (
	`zmd5` VARCHAR(90) NOT NULL PRIMARY KEY,
	`zDate` DATETIME,
	`CDIR` VARCHAR(90),
	`GREY` BIGINT UNSIGNED,
	`BLACK` BIGINT UNSIGNED,
	`CNX` BIGINT UNSIGNED,
	`HOSTS` BIGINT UNSIGNED,
	`DOMAINS` BIGINT UNSIGNED,
	`INFOS` TINYTEXT,
	KEY `zDate` (`zDate`),
	KEY `DOMAINS` (`DOMAINS`),
	KEY `GREY` (`GREY`),
	KEY `BLACK` (`BLACK`),
	KEY `CNX` (`CNX`),
	KEY `CDIR` (`CDIR`),
	KEY `HOSTS` (`HOSTS`)
	) ENGINE=MYISAM;","artica_events" );
	if(!$q->ok){echo $q->mysql_error."\n";return;}
	
	$prefix="INSERT IGNORE INTO `smtpcdir_day` (`zmd5`,`zDate`,`CDIR`,`GREY`,`BLACK`,`CNX`,`DOMAINS`,`INFOS`) VALUES ";
	
	
	
	
	while (list ($zDate, $ARRAY) = each ($MAINNETS) ){
		while (list ($CDIR, $INFOS) = each ($ARRAY) ){
			if(!isset($INFOS["BLACK"])){$INFOS["BLACK"]=0;}
			if(!isset($INFOS["GREY"])){$INFOS["GREY"]=0;}
			$CNX=intval($INFOS["CNX"]);
			$GREY=intval($INFOS["GREY"]);
			$BLACK=intval($INFOS["BLACK"]);
			$DOMAINS=intval($INFOS["FAM"]);
			$infotext=mysql_escape_string2(serialize($INFOS["FAM"]));
			echo "$zDate $CDIR cnx:$CNX greylisted:$GREY blacklisted:$BLACK domains:$DOMAINS\n";
			$md5=md5("$zDate$CDIR$DOMAINS$BLACK$GREY$CNX$infotext");
			$f[]="('$md5','$zDate','$CDIR','$GREY','$BLACK','$CNX','$DOMAINS','$infotext')";
			
			if(count($f)>500){
				$q->QUERY_SQL($prefix.@implode(",", $f),"artica_events");
				if(!$q->ok){echo $q->mysql_error."\n";return;}
				$f=array();
			}
		}
		
		
		
	}
	
	if(count($f)>0){
		$q->QUERY_SQL($prefix.@implode(",", $f),"artica_events");
		if(!$q->ok){echo $q->mysql_error."\n";return;}
		$f=array();
	}
	
	return true;
	
	//print_r($MAINNETS);
	
}

function pflogsumm($filename){
	$unix=new unix();
	$tmpfile=$unix->FILE_TEMP();
	$binary="/usr/share/artica-postfix/bin/pflogsumm.pl";
	@chmod("$binary",0755);
	echo "$binary $filename >$tmpfile\n";
	system("$binary $filename >$tmpfile");
	if(ParseReport($tmpfile)){
		@unlink($tmpfile);
		return true;
	}
}
	
function ParseReport($filepath){
	
	
		$f=explode("\n",@file_get_contents($filepath));
	
		$GrandTotals=false;
		while (list ($key, $value) = each ($f) ){
	
			if(preg_match("#Grand Totals#", $value)){$GrandTotals=true;}
			if($GrandTotals==false){continue;}
			if(preg_match("#([0-9]+)\s+received#", $value,$re)){
				$received=$re[1];
				continue;
			}
			if(preg_match("#([0-9]+)\s+delivered#", $value,$re)){
				$delivered=$re[1];
				continue;
			}
			if(preg_match("#([0-9]+)\s+forwarded#", $value,$re)){
				$forwarded=$re[1];
				continue;
			}
			if(preg_match("#([0-9]+)\s+deferred#", $value,$re)){
				$deferred=$re[1];
				continue;
			}
			if(preg_match("#([0-9]+)\s+bounced#", $value,$re)){
				$bounced=$re[1];
				continue;
			}
			if(preg_match("#([0-9]+)\s+rejected#", $value,$re)){
				$rejected=$re[1];
				continue;
			}
			if(preg_match("#([0-9]+)\s+senders#", $value,$re)){
				$senders=$re[1];
				continue;
			}
			if(preg_match("#([0-9]+)\s+recipients#", $value,$re)){
				$recipients=$re[1];
				continue;
			}
	
	
			if(preg_match("#Per-Hour Traffic Summary#", $value)){break;}
	
	
		}

	$q=new mysql();
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `smtpsum_day` (
	`zmd5` VARCHAR(90) NOT NULL PRIMARY KEY,
	`zDate` DATETIME,
	`recipients` BIGINT UNSIGNED,
	`rejected` BIGINT UNSIGNED,
	`bounced` BIGINT UNSIGNED,
	`deferred` BIGINT UNSIGNED,
	`forwarded` BIGINT UNSIGNED,
	`delivered`  BIGINT UNSIGNED,
	`received` BIGINT UNSIGNED,
	KEY `zDate` (`zDate`),
	KEY `recipients` (`recipients`),
	KEY `rejected` (`rejected`),
	KEY `bounced` (`bounced`),
	KEY `deferred` (`deferred`),
	KEY `forwarded` (`forwarded`),
	KEY `delivered` (`delivered`),
	KEY `received` (`received`)
	) ENGINE=MYISAM;","artica_events" );
	if(!$q->ok){echo $q->mysql_error."\n";return;}
	
	
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `smtpgraph_day` (
	`zmd5` VARCHAR(90) NOT NULL PRIMARY KEY,
	`zDate` DATETIME,
	`range` VARCHAR(40),
	`RECEIVED` BIGINT UNSIGNED,
	`DELIVERED` BIGINT UNSIGNED,
	`DEFERRED` BIGINT UNSIGNED,
	`BOUNCED` BIGINT UNSIGNED,
	`REJECTED`  BIGINT UNSIGNED,
	KEY `zDate` (`zDate`),
	KEY `range` (`range`),
	KEY `RECEIVED` (`RECEIVED`),
	KEY `DELIVERED` (`DELIVERED`),
	KEY `DEFERRED` (`DEFERRED`),
	KEY `BOUNCED` (`BOUNCED`),
	KEY `REJECTED` (`REJECTED`)
	) ENGINE=MYISAM;","artica_events" );
	if(!$q->ok){echo $q->mysql_error."\n";return;}
	
	

	$HIER=$q->HIER();
	$md5=md5("$HIER$received$delivered$forwarded$deferred$bounced$rejected$senders$recipients");
	
	$sql="INSERT IGNORE INTO smtpsum_day (`zmd5`,`zDate`,`recipients`,`rejected`,
			`bounced`,`deferred`,`forwarded`,`delivered`,`received`)
		VALUES ('$md5','$HIER','$recipients','$rejected','$bounced','$deferred','$forwarded','$delivered','$received')";
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){return false;}
	
	
	reset($f);
	$MAIN=array();
	$GrandTotals=false;
	while (list ($key, $value) = each ($f) ){
		if(preg_match("#Per-Hour Traffic Summary#", $value)){$GrandTotals=true;}
		if($GrandTotals==false){continue;}
	
		if(preg_match("#([0-9]+)-([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)#", $value,$re)){
				
			$range="{$re[1]}-{$re[2]}";
			$RECEIVED="{$re[3]}";
			$DELIVERED="{$re[4]}";
			$DEFERRED="{$re[5]}";
			$BOUNCED="{$re[6]}";
			$REJECTED=$re[7];
			
			$md5=md5("$HIER$value");
			
			$sql="INSERT IGNORE INTO smtpgraph_day (`zmd5`,`zDate`,`range`,`RECEIVED`,`DELIVERED`,`DEFERRED`,`BOUNCED`,`REJECTED`)
			VALUES('$md5','$HIER','$range','$RECEIVED','$DELIVERED','$DEFERRED','$BOUNCED','$REJECTED')";
			$q->QUERY_SQL($sql,"artica_events");
			if(!$q->ok){return false;}
			continue;
		}
	
	
		if(preg_match("#Host\/Domain Summary#", $value)){break;}
	
	}
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `smtpdeliver_day` (
	`zmd5` VARCHAR(90) NOT NULL PRIMARY KEY,
	`zDate` DATETIME,
	`DOMAIN` VARCHAR(128),
	`SIZE` BIGINT UNSIGNED,
	`RQS` BIGINT UNSIGNED,
	KEY `DOMAIN` (`DOMAIN`),
	KEY `SIZE` (`SIZE`),
	KEY `zDate` (`zDate`),
	KEY `RQS` (`RQS`)
	
	) ENGINE=MYISAM;","artica_events" );
	if(!$q->ok){echo $q->mysql_error."\n";}
	
	reset($f);
	$MAIN=array();
	$GrandTotals=false;
	while (list ($key, $value) = each ($f) ){
		if(preg_match("#Host\/Domain Summary: Message Delivery#", $value)){$GrandTotals=true;}
		if($GrandTotals==false){continue;}
		if(preg_match("#Host\/Domain Summary: Messages Received#", $value)){break;}
		if(!preg_match("#([0-9]+)\s+([0-9km]+)\s+[0-9\.]+\s+[0-9\.]+\s+[a-z]\s+[0-9\.]+\s+[a-z]\s+(.+)#", $value,$re)){continue;}
		$size=0;
		$msg=$re[1];
		if(preg_match("#([0-9]+)k#", $re[2],$kr)){
			$size=$kr[1]*1024;
		}
		if(preg_match("#([0-9]+)m#", $re[2],$kr)){
			$size=$kr[1]*1024;
			$size=$size*1024;
		}
			
		if($size==0){$size=$re[2];}
		$domain=trim($re[3]);
		$md5=md5("$HIER$domain$msg$size");
		echo "('$domain','$msg','$size')\n";
		$TR[]="('$md5','$HIER','$domain','$msg','$size')";
		if(count($TR)>500){
			$q->QUERY_SQL("INSERT IGNORE INTO smtpdeliver_day (`zmd5`,`zDate`,DOMAIN,RQS,SIZE) VALUES ".@implode(",", $TR),"artica_events");
			$TR=array();
			if(!$q->ok){echo $q->mysql_error."\n";}
		}
	
	}
	
	
	if(count($TR)>0){
		$q->QUERY_SQL("INSERT IGNORE INTO smtpdeliver_day (`zmd5`,`zDate`,DOMAIN,RQS,SIZE) VALUES ".@implode(",", $TR),"artica_events");
		$TR=array();
		if(!$q->ok){echo $q->mysql_error."\n";}
	}	

	$q=new mysql();
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `smtpsenders_day` (
	`zmd5` VARCHAR(90) NOT NULL PRIMARY KEY,
	`zDate` DATETIME,
	`email` VARCHAR(128), `RQS` BIGINT UNSIGNED,
	KEY `email` (`email`), KEY `zDate` (`zDate`),KEY `RQS` (`RQS`) ) ENGINE=MYISAM;","artica_events" );
	if(!$q->ok){echo $q->mysql_error."\n";}
	
	
	reset($f);
	$TR=array();
	$MAIN=array();
	$GrandTotals=false;
	while (list ($key, $value) = each ($f) ){
		if(preg_match("#Senders by message count#", $value)){$GrandTotals=true;}
		if($GrandTotals==false){continue;}
		if(preg_match("#Recipients by message count#", $value)){break;}
		if(!preg_match("#([0-9]+)\s+(.+)#", $value,$re)){continue;}
		$email=mysql_escape_string2(trim(strtolower($re[2])));
		$msg=$re[1];
		if($email=="from=<>"){$email="Postmaster";}
		$md5=md5("$HIER$email$msg");
		echo "('$md5','$HIER','$email','$msg')\n";
		$TR[]="('$md5','$HIER','$email','$msg')";
			
		if(count($TR)>500){
			$q->QUERY_SQL("INSERT IGNORE INTO smtpsenders_day (`zmd5`,`zDate`,email,RQS) VALUES ".
			@implode(",", $TR),"artica_events");
			if(!$q->ok){echo $q->mysql_error."\n";}
		}
	
	}
	
	
	if(count($TR)>0){
		$q->QUERY_SQL("INSERT IGNORE INTO smtpsenders_day (`zmd5`,`zDate`,email,RQS) VALUES ".
		@implode(",", $TR),"artica_events");
	}
	
	
	
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `smtprecipients_day` (
	`zmd5` VARCHAR(90) NOT NULL PRIMARY KEY,
	`zDate` DATETIME,
	`email` VARCHAR(128), `RQS` BIGINT UNSIGNED,
	KEY `email` (`email`), KEY `zDate` (`zDate`),KEY `RQS` (`RQS`) ) ENGINE=MYISAM;","artica_events" );
	if(!$q->ok){echo $q->mysql_error."\n";}
	
	reset($f);
	$TR=array();
	$MAIN=array();
	$GrandTotals=false;
	while (list ($key, $value) = each ($f) ){
	if(preg_match("#Recipients by message count#", $value)){$GrandTotals=true;}
	if($GrandTotals==false){continue;}
	if(preg_match("#Senders by message size#", $value)){break;}
		if(!preg_match("#([0-9]+)\s+(.+)#", $value,$re)){continue;}
			$email=mysql_escape_string2(trim(strtolower($re[2])));
			$msg=$re[1];
			if($email=="from=<>"){$email="Postmaster";}
			$md5=md5("$HIER$email$msg");
			echo "('$md5','$HIER','$email','$msg')\n";
			$TR[]="('$md5','$HIER','$email','$msg')";
				
			if(count($TR)>500){
				$q->QUERY_SQL("INSERT IGNORE INTO smtprecipients_day (`zmd5`,`zDate`,email,RQS) VALUES ".
				@implode(",", $TR),"artica_events");
				if(!$q->ok){echo $q->mysql_error."\n";}
			}
	
	}	
	
	if(count($TR)>0){
		$q->QUERY_SQL("INSERT IGNORE INTO smtprecipients_day (`zmd5`,`zDate`,email,RQS) VALUES ".
				@implode(",", $TR),"artica_events");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}	

	return true;
}

?>





