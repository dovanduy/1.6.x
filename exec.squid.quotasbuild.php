<?php
//exec.squid.quotasbuild.php
$GLOBALS["DEBUG_INCLUDES"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.remote-stats-appliance.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

if($argv[1]=="--build"){bigbuild();exit;}
if($argv[1]=="--macuid"){MacToUid();exit;}



build();

function bigbuild(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$squidconfigured=false;
	$f=file("/etc/squid3/squid.conf");
	while (list($num,$val)=each($f)){
		if(preg_match("#external_acl_type quotas#", $val)){
			$squidconfigured=true;
		}
		
	}
	
	if(!$squidconfigured){
		echo "Starting......: ".date("H:i:s")." Squid is not set with quota..";
		system("$php5 /usr/share/artica-postfix/exec.squid.php --build");
		return;
	}
	build();
	echo "Starting......: ".date("H:i:s")." reloading squid";
	system("$php5 /usr/share/artica-postfix/exec.squid.php --reload-squid");
	
	
}


function build(){
if(!function_exists("IsPhysicalAddress")){include_once(dirname(__FILE__)."/ressources/class.templates.inc");}
$unix=new unix();
$file_duration="/etc/squid3/squid.durations.ini";
$file_quotas_day="/etc/squid3/squid.quotasD.ini";
$file_quotas_hour="/etc/squid3/squid.quotasH.ini";
$php5=$unix->LOCATE_PHP5_BIN();
$nohup=$unix->find_program("nohup");
$sql="SELECT * FROM webfilters_quotas";
$q=new mysql_squid_builder();
$results = $q->QUERY_SQL($sql);	
$array=array();
while ($ligne = mysql_fetch_assoc($results)) {
	$duration=$ligne["duration"];
	$xtype=$ligne["xtype"];
	$value=$ligne["value"];
	$array[$duration][$xtype][$value]=($ligne["maxquota"]*1024)*1000;
	if($GLOBALS["VERBOSE"]){echo "duration[$duration]: $xtype ($value) = {$array[$duration][$xtype][$value]} (bytes)\n";}	
}




if(count($array)==0){@unlink($file_duration);}else{@file_put_contents($file_duration, serialize($array));}
$table="UserSizeD_".date("Ymd");

if(!$q->TABLE_EXISTS($table)){
	$q->CreateUserSizeRTT_day($table);
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.squid.php --build-schedules >/dev/null 2>&1 &");
}

$sql="SELECT uid,ipaddr,hostname,account,MAC,SUM(size) as size FROM `$table` GROUP BY uid,ipaddr,hostname,account,MAC";
$results = $q->QUERY_SQL($sql);	
$array=array();
while ($ligne = mysql_fetch_assoc($results)) {
	$array["ipaddr"][$ligne["ipaddr"]]=$ligne["size"];
	$array["uid"][$ligne["uid"]]=$ligne["size"];
	$array["hostname"][$ligne["hostname"]]=$ligne["size"];
	$array["MAC"][$ligne["MAC"]]=$ligne["size"];
	if($GLOBALS["VERBOSE"]){
		$sizeM=($ligne["size"]/1024)/1000;
		echo date("l d").": {$ligne["MAC"]},{$ligne["uid"]},{$ligne["ipaddr"]} = {$ligne["size"]} ($sizeM M)\n";
	}
}

@file_put_contents($file_quotas_day, serialize($array));
$array=array();
$sql="SELECT DAY(zDate) as tday,HOUR(zDate) as thour,uid,ipaddr,hostname,account,MAC,SUM(size) as size FROM `UserSizeRTT` 
GROUP BY uid,ipaddr,hostname,account,MAC,tday,thour HAVING tday=DAY(NOW()) AND thour=HOUR(NOW())";
$results = $q->QUERY_SQL($sql);	
$array=array();
while ($ligne = mysql_fetch_assoc($results)) {
	if($GLOBALS["VERBOSE"]){$sizeM=($ligne["size"]/1024)/1000;echo "{$ligne["thour"]}h: {$ligne["MAC"]},{$ligne["uid"]},{$ligne["ipaddr"]} = {$ligne["size"]} ($sizeM M)\n";}
	$array["ipaddr"][$ligne["ipaddr"]]=$ligne["size"];
	$array["uid"][$ligne["uid"]]=$ligne["size"];
	$array["hostname"][$ligne["hostname"]]=$ligne["size"];
	$array["MAC"][$ligne["MAC"]]=$ligne["size"];
}
@file_put_contents($file_quotas_hour, serialize($array));
MacToUid();
}
function MacToUid(){
	
	$array=array();
	$q=new mysql_squid_builder();
	
	$sql="SELECT * FROM webfilters_nodes WHERE LENGTH(uid)>1";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		if($ligne["MAC"]=="00:00:00:00:00:00"){continue;}
		if(!IsPhysicalAddress($ligne["MAC"])){continue;}
		if($GLOBALS["VERBOSE"]){echo "{$ligne["MAC"]} = {$ligne["uid"]}\n";}
		$array[$ligne["MAC"]]=$ligne["uid"];
	}
	
	$sql="SELECT * FROM webfilters_ipaddr WHERE LENGTH(uid)>1";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		$array[$ligne["ipaddr"]]=$ligne["uid"];
	}	
	
	
	
	$q=new mysql();
	$sql="SELECT MacAddress, uid FROM hostsusers";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$mac=strtolower(trim($ligne["MacAddress"]));
		if(!IsPhysicalAddress($mac)){continue;}
		$uid=strtolower(trim($ligne["uid"]));
		$array[$mac]=$uid;
	}
	@file_put_contents("/etc/squid3/MacToUid.ini", serialize($array));
	if(count($array)>0){iFBuildMacToUid();}
	
}
function iFBuildMacToUid(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$squidconfigured=false;
	$f=file("/etc/squid3/squid.conf");
	while (list($num,$val)=each($f)){
		if(preg_match("#external_acl_type MacToUid#", $val)){
			$squidconfigured=true;
		}

	}

	if(!$squidconfigured){
		echo "Starting......: ".date("H:i:s")." Squid is not set with quota..";
		system("$php5 /usr/share/artica-postfix/exec.squid.php --build");
		return;
	}

	echo "Starting......: ".date("H:i:s")." reloading squid";
	system("$php5 /usr/share/artica-postfix/exec.squid.php --reload-squid");


}

?>