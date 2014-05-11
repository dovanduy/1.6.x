<?php
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.user.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.zarafadb.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');


zarafa_table_versions();

function zarafa_table_versions(){
	$q=new mysql_zarafadb();
	
	$array=$q->TABLES_LIST();
	
	while (list ($tablename, $ar) = each ($array) ){
		echo "Starting......: ".date("H:i:s")." [INIT]: zarafa.$tablename OK\n";
		
	}
	
	return;
	
	if($q->TABLE_EXISTS("versions")){
		echo "Starting......: ".date("H:i:s")." [INIT]: zarafa.versions OK\n";
		return;
	}
	
	$Zarafa_server_version=Zarafa_server_version();
	
	echo "Starting......: ".date("H:i:s")." [INIT]: zarafa.versions -> Create a new one ". @implode(",", $Zarafa_server_version)."\n";
	
	$sql="CREATE TABLE `versions` (
	`major` int(11) unsigned NOT NULL DEFAULT '0',
	`minor` int(11) unsigned NOT NULL DEFAULT '0',
	`revision` int(11) unsigned NOT NULL DEFAULT '0',
	`databaserevision` int(11) unsigned NOT NULL DEFAULT '0',
	`updatetime` datetime NOT NULL,
	PRIMARY KEY (`major`,`minor`,`revision`,`databaserevision`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo "Starting......: ".date("H:i:s")." [INIT]: zarafa.versions FAILED $q->mysql_error\n";
		if($GLOBALS["VERBOSE"]){echo $q->mysql_error."\n";return;}
	}
	
	echo "Starting......: ".date("H:i:s")." [INIT]: zarafa.versions building default values...\n";
	$sql="INSERT INTO `versions` VALUES ({$Zarafa_server_version[0]},{$Zarafa_server_version[2]},{$Zarafa_server_version[3]},63,'2013-02-19 10:02:34');";
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo "Starting......: ".date("H:i:s")." [INIT]: zarafa.versions FAILED $q->mysql_error\n";
		if($GLOBALS["VERBOSE"]){echo $q->mysql_error."\n";return;}
		}
	
	
	
	
}

function Zarafa_server_version(){
	$unix=new unix();
	$zarafa_server=$unix->find_program("zarafa-server");
	exec("$zarafa_server -V 2>&1",$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#Product version:\s+([0-9]+),([0-9]+),([0-9]+),([0-9]+)#", $line,$re)){
			return array($re[1],$re[2],$re[4]);
		}
	}
	
}