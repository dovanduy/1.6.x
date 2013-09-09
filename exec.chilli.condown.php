#!/usr/bin/php
<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["RELOAD"]=false;
$GLOBALS["TITLENAME"]="Chilli";
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.nginx.inc');
include_once(dirname(__FILE__).'/ressources/class.freeweb.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/ressources/class.squid.reverse.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');

$sqlCR="CREATE TABLE IF NOT EXISTS `hotspot_ident` (
				`ipaddr` VARCHAR( 90 ) NOT NULL PRIMARY KEY ,
				 `username` VARCHAR(128) NOT NULL,
				  `MAC` VARCHAR(128) NOT NULL,
				  zDate datetime NOT NULL,
				  KEY `username` (`username`),
				  UNIQUE KEY `MAC` (`MAC`),
				  KEY `zDate` (`zDate`)
				)  ENGINE = MYISAM;";

$USER_NAME=mysql_escape_string2($_ENV["USER_NAME"]);
$FRAMED_IP_ADDRESS=$_ENV["FRAMED_IP_ADDRESS"];
$MAC=$_ENV["CALLING_STATION_ID"];
$MAC=strtolower($MAC);
$MAC=str_replace("-", ":", $MAC);

$zDate=date("Y-m-d H:i:s");
$q=new mysql_squid_builder();
$q->QUERY_SQL("DELETE FROM hotspot_ident WHERE `ipaddr`='$FRAMED_IP_ADDRESS'");
$q->QUERY_SQL("DELETE FROM hotspot_ident WHERE `MAC`='$MAC'");
if(!$q->ok){events($q->mysql_error);}


function events($text){

	$LOG_SEV=LOG_INFO;
	openlog("coova-chilli", LOG_PID , LOG_SYSLOG);
	syslog($LOG_SEV, $text);
	closelog();

}