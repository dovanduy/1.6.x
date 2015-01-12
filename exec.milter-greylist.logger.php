#!/usr/bin/php -q
<?php

$line=@implode("|", $argv);
$fd = fopen("php://stdin", "r");
$buffer = ""; 
ToSyslog("Open logger");
while (!feof($fd)) {
	
	$buffer= fread($fd, 1024);
	if(trim($buffer)<>null){
		send_to_mysql(trim($buffer));
	}
}

fclose($fd);

ToSyslog("Closing logger");
function ToSyslog($text){

	$LOG_SEV=LOG_INFO;
	if(function_exists("openlog")){openlog(basename(__FILE__), LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
	if(function_exists("closelog")){closelog();}
}


function send_to_mysql($buffer){
	
	$results=explode(",",$buffer);
	$md5=md5($buffer.time());
	$instance=$results[0];
	$publicip=$results[1];
	$mailfrom=$results[2];
	$rcpt=$results[1];
	$failed=$results[6];
	$Country=$results[7];
	$HOUR=date('H');
	$date=date("Y-m-d H:i:s");
	$tablename="mgreyh_".date("YmdH");
	$mailfromZ=explode("@",$mailfrom);
	$rcptZ=explode("@",$rcpt);
	$prefix="INSERT IGNORE INTO $tablename (`zmd5`,`ztime`,`zhour`,`mailfrom`,`instancename`,`mailto`,`domainfrom`,`domainto`,`senderhost`,`failed`) VALUES ";
	$suffix="('$md5','$date','$HOUR','$mailfrom','$instance','$rcpt','{$mailfromZ[1]}','{$rcptZ[1]}','$publicip','$failed')";
	
	if(!isset($GLOBALS["mgreyh"][date("YmdH")])){

		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
		`zmd5` varchar(90) NOT NULL,
		`ztime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		`zhour` tinyint(2) NOT NULL,
		`mailfrom` varchar(255) NOT NULL,
		`instancename` varchar(255) NOT NULL,
		`mailto` varchar(255) NOT NULL,
		`domainfrom` varchar(128) NOT NULL,
		`domainto` varchar(128) NOT NULL,
		`senderhost` varchar(128) NOT NULL,
		`failed` varchar(15) NOT NULL,
		PRIMARY KEY (`zmd5`),
		KEY `ztime` (`ztime`,`zhour`),
		KEY `mailfrom` (`mailfrom`),
		KEY `mailto` (`mailto`),
		KEY `domainfrom` (`domainfrom`),
		KEY `domainto` (`domainto`),
		KEY `senderhost` (`senderhost`),
		KEY `instancename` (`instancename`),
		KEY `failed` (`failed`)
		) ENGINE=MYISAM";	
		SEND_MYSQL($sql);
	}
	
	SEND_MYSQL($prefix.$suffix);
	
}

function SEND_MYSQL($sql){

	$bd=@mysql_connect(":/var/run/mysqld/mysqld.sock","root",null);
	if(!$bd){
		$des=@mysql_error();
		$errnum=@mysql_errno();
		ToSyslog("MySQL error: $errnum $des");
		return;
	}
	$ok=@mysql_select_db("postfixlog",$bd);
	if(!$ok){
		$des=@mysql_error();
		$errnum=@mysql_errno();
		ToSyslog("MySQL error: $errnum $des");
		@mysql_close($bd);
		return;
	}
	$results=@mysql_query($sql,$bd);
	if(!$results){
		$des=@mysql_error();
		$errnum=@mysql_errno();
		ToSyslog("MySQL error: $errnum $des");
	}

	@mysql_close($bd);

}
?>