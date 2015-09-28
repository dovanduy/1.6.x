#!/usr/bin/php -q
<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="InfluxDB Daemon";
$GLOBALS["PROGRESS"]=false;
$GLOBALS["MIGRATION"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;
$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--migration#",implode(" ",$argv),$re)){$GLOBALS["MIGRATION"]=true;}

$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');


$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--build-rules"){build_rules();exit;}
build_rules();

function build_rules(){
	
	$q=new mysql_squid_builder();
	$unix=new unix();
	$SQUID_BIN=$unix->LOCATE_SQUID_BIN();
	
	build_progress("{IT_charter}",25);
	
	$sql="SELECT ID,title FROM itcharters WHERE enabled=1";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){
		build_progress("{IT_charter} {mysql_error}",110);
		echo $q->mysql_error;
		return;
	}
	
	if(mysql_num_rows($results)==0){
		@unlink("/etc/squid3/itCharts.enabled.db");
		squid_admin_mysql(1, "Reloading Proxy service (itCharts)", null,__FILE__,__LINE__);
		build_progress("{IT_charter} {reload_proxy_service}",90);
		system("$SQUID_BIN -f /etc/squid3/squid.conf -k reconfigure");
		build_progress("{IT_charter} {done} 0 {item}",100);
		return;
	}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		build_progress("{$ligne["title"]}",50);
		echo "{$ligne["ID"]}: {$ligne["title"]}\n";
		$MAIN[$ligne["ID"]]=$ligne["title"];
		
	}
	@file_put_contents("/etc/squid3/itCharts.enabled.db", serialize($MAIN));
	
	squid_admin_mysql(1, "Reloading Proxy service (itCharts)", null,__FILE__,__LINE__);
	build_progress("{IT_charter} {reload_proxy_service}",90);
	system("$SQUID_BIN -f /etc/squid3/squid.conf -k reconfigure");
	build_progress("{IT_charter} {done} ".count($MAIN)." {items}",100);
	
	
	
}



function build_progress($text,$pourc){
	$cachefile="/usr/share/artica-postfix/ressources/logs/itchart.progress";
	echo "{$pourc}% $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}
?>