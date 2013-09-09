<?php
$GLOBALS["BYPASS"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/whois/whois.main.php');


start();
function start($xtime=0){


	if($GLOBALS["VERBOSE"]){"echo Loading...\n";}
	$unix=new unix();
	if($GLOBALS["VERBOSE"]){"echo Loading done...\n";}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$oldpid=@file_get_contents($pidfile);
	if(!$GLOBALS["FORCE"]){
		if($oldpid<100){$oldpid=null;}
		$unix=new unix();
		if($unix->process_exists($oldpid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid\n";}return;}
		$timeexec=$unix->file_time_min($timefile);
		if($timeexec<60){return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}
	
	@unlink($timefile);
	@file_put_contents($timefile, time());
	
	$table="quotahours_".date('YmdH');
	if($GLOBALS["VERBOSE"]){echo "Current table: $table\n";}
	$q=new mysql_squid_builder();
	$LIST_TABLES_QUOTA_HOURS=$q->LIST_TABLES_QUOTA_HOURS();
	
	while (list ($tableWork,$none) = each ($LIST_TABLES_QUOTA_HOURS) ){
		if($tableWork==$table){if($GLOBALS["VERBOSE"]){echo "Skip Current table: $table\n";}continue;}
		$xtime=$q->TIME_FROM_QUOTAHOUR_TABLE($tableWork);
		$Day=date("Y-m-d",$xtime);
		if($GLOBALS["VERBOSE"]){echo "Analyze table: $tableWork ($Day)\n";}
		if(!compile_table($tableWork,$xtime)){continue;}
		if($GLOBALS["VERBOSE"]){echo "Remove table: $tableWork ($Day)\n";}
		$q->QUERY_SQL("DROP TABLE $tableWork");
	}
	
	
	
	
}
function compile_table($tablesource,$xtime){
	
	
	$q=new mysql_squid_builder();
	$sql="SELECT SUM(size) as size,hour,ipaddr,uid,MAC,familysite,servername FROM $tablesource
	GROUP BY hour,ipaddr,uid,MAC,familysite,servername HAVING size>0";
	$results=$q->QUERY_SQL($sql);
	$count=mysql_num_rows($results);
	$OUS=array();
	if($count==0){return true;}
	
	$nexttable="quotaday_".date("Ymd",$xtime);
	if(!$q->check_quota_day(date("Ymd",$xtime))){return false;}
	
	if(!$q->FIELD_EXISTS("$nexttable", "ou")){$q->QUERY_SQL("ALTER IGNORE TABLE `$nexttable` ADD `ou`VARCHAR( 128 ) NOT NULL ,ADD INDEX( `ou` )");}
	
	if(is_file("/etc/artica-postfix/activedirectory-ou.db")){
		$OUS=unserialize(@file_get_contents("/etc/artica-postfix/activedirectory-ou.db"));
	}
	
	$prefix="INSERT IGNORE INTO $nexttable (keyr,size,hour,ipaddr,uid,ou,MAC,familysite,servername) VALUES ";
	$f=array();
	if($GLOBALS["VERBOSE"]){echo "$tablesource $count rows\n";}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$md5=md5(serialize($ligne));
		$ou=null;
		$uid=$ligne["uid"];
		if($uid<>null){if(isset($OUS[$uid])){$ou=mysql_escape_string2($OUS[$uid]);}}
		$uid=mysql_escape_string2($ligne["uid"]);
		$servername=mysql_escape_string2($ligne["servername"]);
		$familysite=mysql_escape_string2($ligne["familysite"]);
		$ipaddr=mysql_escape_string2($ligne["ipaddr"]);
		$MAC=mysql_escape_string2($ligne["MAC"]);
		
		$hour=$ligne["hour"];
		$size=$ligne["size"];
		$f[]="('$md5','$size','$hour','$ipaddr','$uid','$ou','$MAC','$familysite','$servername')";
		if(count($f)>500){
			$q->QUERY_SQL($prefix.@implode(",", $f));
			if(!$q->ok){return false;}
			$f=array();
		}
	}
	
	if(count($f)>0){
		$q->QUERY_SQL($prefix.@implode(",", $f));
		if(!$q->ok){return false;}
		$f=array();
	}
	return true;	
}