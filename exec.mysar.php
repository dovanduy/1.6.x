<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.acls.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.mysql.syslogs.inc");
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
writelogs("Task::{$GLOBALS["SCHEDULE_ID"]}:: Executed with ".@implode(" ", $argv)." ","MAIN",__FILE__,__LINE__);

build();



function build(){
	
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	
	
	$unix=new unix();
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid)){echo "Already PID $pid is running\n";die();}
	@file_put_contents($pidfile, getmypid());
	
	$mysar=$unix->find_program("mysar");
	if(!is_file($mysar)){echo "mysar, no such binary...\n";return;}
	
	if($GLOBALS["VERBOSE"]){echo "TimeFile: $timefile\n";}
	
	if(!$GLOBALS["VERBOSE"]){
		$time=$unix->file_time_min($timefile);
		if($time<60){echo "Only each 60mn\n";die();}
		@unlink($timefile);
		@file_put_contents($timefile, time());
	}
	
	
	$BaseWorkDir="/var/log/squid";
	
	if (!$handle = opendir($BaseWorkDir)) {return;}
	
	
	
	while (false !== ($fileZ = readdir($handle))) {
		if($fileZ=="."){continue;}
		if($fileZ==".."){continue;}
		$filename="$BaseWorkDir/$fileZ";
		if(is_dir($filename)){continue;}
		$time=$unix->file_time_min($filename);
		$filedate=date('Y-m-d H:i:s',filemtime($filename));
	
		if(preg_match("#access\.log\.[0-9]+$#", $filename)){
			@mkdir("/home/squid/access_logs");
			if(@copy($filename, "/home/squid/access_logs/".basename($filename).".".filemtime($filename))){
				@unlink($filename);
			}
				
			continue;
		}
	
		if(preg_match("#sarg\.log\.[0-9]+$#", $filename)){
			@mkdir("/home/squid/sarg_logs");
			if(@copy($filename, "/home/squid/sarg_logs/".basename($filename).".".filemtime($filename))){
				@unlink($filename);
			}
	
			continue;
		}

	}
	

	

	$q=new mysql_squid_builder();
	
	echo "Build config Use remote MySQL server = $q->EnableSquidRemoteMySQL\n";
	
	$f[]="username=$q->mysql_admin";
	$f[]="password=$q->mysql_password";
	$f[]="database=squidlogs";
	if($q->EnableSquidRemoteMySQL==1){
		echo "Build config Use remote MySQL server = $q->mysql_admin@$q->mysql_server:$q->mysql_password\n";
		$f[]="server={$q->mysql_server}:$q->mysql_port";
		
	}else{
		echo "Build config Use Local MySQL server = $q->SocketName\n";
		$f[]="server=127.0.0.1";
	}
	
	$f[]="pidfile=/var/run/mysar.pid";
	//$f[]="logfile=/var/log/squid/access.log";
	$syslog=new mysql_storelogs();
	//$syslog->checkTables();
	@file_put_contents("/etc/mysar.conf", @implode("\n", $f));
	CheckSql();
	echo "Done...\n";
	$NICE=EXEC_NICE();
	@mkdir("/home/squid/access_logs",0755,true);
	if ($handle = opendir("/home/squid/access_logs")) {
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				$path="/home/squid/access_logs/$file";
				$cmdline="$NICE $mysar --logfile $path --stats --offline 2>&1";
				$results=array();
				exec($cmdline,$results);
				while (list ($index, $line) = each ($results) ){if(preg_match("#Total runtime#", $line)){squid_admin_mysql(2, "MySar: $line", "Filename: ".basename($path));}}
				$syslog->ROTATE_ACCESS_TOMYSQL($path, null);
			}
			
		}
	}
}

function CheckSql(){
	echo "Checks MySQL tables...\n";	
$q=new mysql_squid_builder();

if(!$q->TABLE_EXISTS("config")){
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS config ( name varchar(255) NOT NULL default '', `value` varchar(255) NOT NULL default '', UNIQUE KEY name (name));");
	$queries3[]="INSERT IGNORE INTO `config` VALUES ('lastTimestamp', '0');";
	$queries3[]="INSERT IGNORE INTO `config` VALUES ('lastCleanUp', '0000-00-00');";
	$queries3[]="INSERT IGNORE INTO `config` VALUES ('defaultindexOrderBy', 'date');";
	$queries3[]="INSERT IGNORE INTO `config` VALUES ('defaultindexOrderMethod', 'DESC');";
	$queries3[]="INSERT IGNORE INTO `config` VALUES ('lastImportedRecordsNumber', '0');";
	$queries3[]="INSERT IGNORE INTO `config` VALUES ('defaultDateTimeOrderBy', 'time');";
	$queries3[]="INSERT IGNORE INTO `config` VALUES ('defaultindexByteUnit', 'M');";
	$queries3[]="INSERT IGNORE INTO `config` VALUES ('defaultIPSummaryOrderBy', 'cachePercent');";
	$queries3[]="INSERT IGNORE INTO `config` VALUES ('defaultIPSummaryOrderMethod', 'DESC');";
	$queries3[]="INSERT IGNORE INTO `config` VALUES ('defaultIPSummaryByteUnit', 'M');";
	$queries3[]="INSERT IGNORE INTO `config` VALUES ('defaultIPSitesSummaryOrderBy', 'bytes');";
	$queries3[]="INSERT IGNORE INTO `config` VALUES ('defaultIPSitesSummaryOrderMethod', 'DESC');";
	$queries3[]="INSERT IGNORE INTO `config` VALUES ('defaultIPSitesSummaryByteUnit', 'M');";
	$queries3[]="INSERT IGNORE INTO `config` VALUES ('defaultDateTimeOrderMethod', 'DESC');";
	$queries3[]="INSERT IGNORE INTO `config` VALUES ('defaultAllSitesOrderBy', 'cachePercent');";
	$queries3[]="INSERT IGNORE INTO `config` VALUES ('defaultAllSitesOrderMethod', 'DESC');";
	$queries3[]="INSERT IGNORE INTO `config` VALUES ('defaultAllSitesByteUnit', 'M');";
	$queries3[]="INSERT IGNORE INTO `config` VALUES ('defaultDateTimeByteUnit', 'K');";
	$queries3[]="INSERT IGNORE INTO `config` VALUES ('defaultSiteUsersOrderBy', 'bytes');";
	$queries3[]="INSERT IGNORE INTO `config` VALUES ('defaultSiteUsersOrderMethod', 'DESC');";
	$queries3[]="INSERT IGNORE INTO `config` VALUES ('defaultSiteUsersByteUnit', 'M');";
	$queries3[]="INSERT IGNORE INTO `config` VALUES ('keepHistoryDays', '32');";
	$queries3[]="INSERT IGNORE INTO `config` VALUES ('squidLogPath', '/var/log/squid/access.log');";
	$queries3[]="INSERT IGNORE INTO `config` VALUES ('schemaVersion', '3');";
	$queries3[]="INSERT IGNORE INTO `config` VALUES ('resolveClients', 'enabled');";
	$queries3[]="INSERT IGNORE INTO `config` VALUES ('mysarImporter', 'enabled');";
	$queries3[]="INSERT IGNORE INTO `config` VALUES ('topGrouping', 'Daily');";	
	while (list ($num, $sql) = each ($queries3)){$q->QUERY_SQL($sql);}
	
}	
	
	$queries2[]="
CREATE TABLE IF NOT EXISTS hostnames (
  id bigint(20) unsigned NOT NULL auto_increment,
  ip int(10) unsigned NOT NULL default '0',
  description varchar(50) NOT NULL default '',
  isResolved tinyint(3) unsigned NOT NULL default '0',
  hostname varchar(255) NOT NULL default '',
  PRIMARY KEY  (id),
  KEY isResolved (isResolved),
  KEY ip (ip)
) ENGINE=MYISAM;";
	$queries2[]="
CREATE TABLE IF NOT EXISTS trafficSummaries (
  id bigint(20) unsigned NOT NULL auto_increment,
  `date` date NOT NULL default '0000-00-00',
  ip int(10) unsigned NOT NULL default '0',
  usersID bigint(20) unsigned NOT NULL default '0',
  inCache bigint(20) unsigned NOT NULL default '0',
  outCache bigint(20) unsigned NOT NULL default '0',
  sitesID bigint(20) unsigned NOT NULL default '0',
  summaryTime tinyint(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  UNIQUE KEY date_ip_usersID_sitesID_summaryTime (`date`,ip,usersID,sitesID,summaryTime)
)ENGINE=MYISAM;
";
	$queries2[]="
CREATE TABLE IF NOT EXISTS traffic (
  id bigint(20) unsigned NOT NULL auto_increment,
  `date` date NOT NULL default '0000-00-00',
  `time` time NOT NULL default '00:00:00',
  ip int(10) unsigned NOT NULL default '0',
  resultCode varchar(50) NOT NULL default '',
  bytes bigint(20) unsigned NOT NULL default '0',
  url text NOT NULL default '',
  authuser varchar(30) NOT NULL default '',
  sitesID bigint(20) unsigned NOT NULL default '0',
  usersID bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY date_ip_sitesID_usersID (`date`,ip,sitesID,usersID)
)ENGINE=MYISAM;
";
	$queries2[]="
CREATE TABLE IF NOT EXISTS users (
  id bigint(20) unsigned NOT NULL auto_increment,
  authuser varchar(50) NOT NULL default '',
  `date` date NOT NULL default '0000-00-00',
  PRIMARY KEY  (id),
  UNIQUE KEY date_authuser (`date`,authuser),
  KEY authuser (authuser)
)ENGINE=MYISAM;
";
	$queries2[]="
CREATE TABLE IF NOT EXISTS sites (
  id bigint(20) unsigned NOT NULL auto_increment,
  `date` date NOT NULL default '0000-00-00',
  site varchar(255) NOT NULL default '',
  PRIMARY KEY  (id),
  UNIQUE KEY date_site (`date`,site)
) ENGINE=MYISAM;
";
	while (list ($num, $sql) = each ($queries2)){$q->QUERY_SQL($sql);}

	$queries6[]="ALTER TABLE `config` DROP INDEX `name`,ADD UNIQUE `name`( `name` );";
	
	
}