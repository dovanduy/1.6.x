<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.syslogs.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
$GLOBALS["VERBOSE"]=true;
ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
$unix=new unix();

echo "Query...\n";
$results=QUERY_SYSLOGS("SELECT filesize,zmd5,storeid FROM files_info WHERE filename LIKE '%syslog-%'");
if(!$results){return;}
$count=mysql_num_rows($results);
$c=0;
while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
	$storeid=$ligne["storeid"];
	$zmd5=$ligne["zmd5"];
	$filesize=$ligne["filesize"];
	$filesize=$filesize/1024;
	$filesize=round($filesize/1024,2);
	$c++;
	echo "$c/$count Remove $storeid {$filesize}MB\n";
	if(!QUERY_SYSLOGS("DELETE FROM files_store WHERE ID='$storeid'")){
		continue;
	}

	QUERY_SYSLOGS("DELETE FROM files_info WHERE zmd5='$zmd5'");
	QUERY_SYSLOGS("DELETE FROM files_info WHERE storeid='$storeid'");

}


echo "Query...\n";
$results=QUERY_SYSLOGS("SELECT filesize,zmd5,storeid FROM files_info WHERE filename LIKE '%.sql.gz'");
if(!$results){return;}
$count=mysql_num_rows($results);
$c=0;
while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
	$storeid=$ligne["storeid"];
	$zmd5=$ligne["zmd5"];
	$filesize=$ligne["filesize"];
	$filesize=$filesize/1024;
	$filesize=round($filesize/1024,2);
	$c++;
	echo "$c/$count Remove $storeid ( $filesize MB)\n";
	if(!QUERY_SYSLOGS("DELETE FROM files_store WHERE ID='$storeid'")){
		continue;
	}

	QUERY_SYSLOGS("DELETE FROM files_info WHERE zmd5='$zmd5'");
	QUERY_SYSLOGS("DELETE FROM files_info WHERE storeid='$storeid'");
	
}






function QUERY_SYSLOGS($sql){
	$database="syslogs";
	$bd=@mysql_connect(":/var/run/syslogdb.sock","root",null);
	if(!$bd){echo "Connect failed\n";return false;}
	
	$ok=@mysql_select_db($database,$bd);
	if (!$ok){
		$errnum=@mysql_errno($bd);
		$des=@mysql_error($bd);
		echo "mysql_select_db [FAILED] N.$errnum DESC:$des mysql/QUERY_SQL\n";
		return false;
	}
	
	$results=mysql_query($sql,$bd);
	if(!$results){
		$errnum=@mysql_errno($bd);
		$des=@mysql_error($bd);	
		echo "mysql_select_db [FAILED] N.$errnum DESC:$des mysql/QUERY_SQL\n";
		return false;
		@mysql_close($bd);
	}
	@mysql_close($bd);
	return $results;
	
}


