<?php
$GLOBALS["BYPASS"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["DEBUG_MEM"]=false;
$GLOBALS["NODHCP"]=true;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
}
if($GLOBALS["VERBOSE"]){
	ini_set('display_errors', 1);
	ini_set('html_errors',0);
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
}

if($GLOBALS["VERBOSE"]){"echo Loading...\n";}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.artica-meta.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.stats-appliance.inc');
include_once(dirname(__FILE__).'/ressources/class.parse.berekley.inc');
include_once(dirname(__FILE__)."/ressources/class.realtime-buildsql.inc");


xstart();


function xstart(){
	
	$Directory="/home/artica-postfix/squid/StatsApplicance/BEREKLEY";
	
	if(!is_dir($Directory)){return;}
	if (!$handle = opendir($Directory)) {return;}
	
	while (false !== ($fileZ = readdir($handle))) {
		if($fileZ=="."){continue;}
		if($fileZ==".."){continue;}
		$path="$Directory/$fileZ";
		$lockfile="$Directory/$fileZ.LCK";
		events("parse_stats(): Scanning $path");
		$t1=microtime_float();
		
		if(preg_match("#^(.+?)-UserAuthDB\.db#", $fileZ,$re)){
			if(is_file($lockfile)){continue;}
			
			@file_put_contents($lockfile, time());
			parse_userauthdb($path,$re[1],true);
			events("$fileZ ".microtime_ms($t1));
			@unlink($path);
			@unlink($lockfile);
			continue;
			
		}
		
	
		
	
		if(preg_match("#^(.+?)-[0-9]+_QUOTASIZE\.db#", $fileZ,$re)){
			if(is_file($lockfile)){continue;}
			@file_put_contents($lockfile, time());
			ParseDB_FILE($path,$re[1],true);
			events("$fileZ ".microtime_ms($t1));
			@unlink($lockfile);
			@unlink($path);
			continue;
		}
	}	
	
	
}


function ParseDB_FILE($path,$uuid=null,$asmeta=false){
	$unix=new unix();
	if(!is_file($path)){return;}





	echo "Open $path\n";
	$db_con = dba_open($path, "r","db4");
	if(!$db_con){
		if($asmeta){meta_admin_mysql(1, "DB open failed $path", null,__FILE__,__LINE__);}
		echo "DB open failed\n";
		die();
	}





	$mainkey=dba_firstkey($db_con);

	while($mainkey !=false){
		$val=0;

		$data=unserialize(dba_fetch($mainkey,$db_con));
		$mainkey=dba_nextkey($db_con);
		if(!is_array($data)){continue;}

		$q=new mysql_squid_builder();
		$qCommon=new mysql_squid_builder();
		if($uuid<>null){$q=new mysql_stats($uuid);}
		if($asmeta){$q=new mysql_meta();}

		if(!isset($data["HOURLY"])){continue;}
		if(!isset($data["WWW"])){continue;}
		$category=null;
		$ipaddr=mysql_escape_string2($data["IPADDR"]);
		if(isset($data["MAC"])){$mac=mysql_escape_string2($data["MAC"]);}
		$uid=mysql_escape_string2($data["UID"]);
		$familysite=mysql_escape_string2($data["WWW"]);
		if(isset($data["category"])){$category=mysql_escape_string2($data["category"]);}

		if($uid==null){$uid=$qCommon->UID_FROM_MAC($data["MAC"]);}
		if($uid==null){$uid=$qCommon->UID_FROM_IP($data["IPADDR"]);}
		$uid=mysql_escape_string2($uid);

		$length=strlen($ipaddr)+strlen($mac)+strlen($uid)+strlen($familysite);
		if($length==0){continue;}

		while (list ($day, $array) = each ($data["HOURLY"]) ){
			while (list ($hour, $size) = each ($array) ){
				$md5=md5("'$ipaddr','$mac','$uid','$familysite','$day','$hour','$size','$category'");
				$wwwUH[]="('$md5','$ipaddr','$mac','$uid','$familysite','$day','$hour','$size','$category')";
				if($GLOBALS["VERBOSE"]){echo "('$md5','$ipaddr','$mac','$uid','$familysite','$day','$hour','$size','$category')\n";}
					
			}

		}

			


	}

	dba_close($db_con);
	$TABLE_WEEK_RTTH="WEEK_RTTH";
	$ENGINE="MEMORY";

	if($asmeta){
		$TABLE_WEEK_RTTH="{$uuid}_WEEK_RTTH";
		$ENGINE="MYISAM";
	}

	if($asmeta){xmeta_events("DROP TABLE `$TABLE_WEEK_RTTH`",__FUNCTION__,__FILE__,__LINE__);}
	$q->QUERY_SQL("DROP TABLE `$TABLE_WEEK_RTTH`");


	if($asmeta){xmeta_events("CREATE TABLE `$TABLE_WEEK_RTTH`",__FUNCTION__,__FILE__,__LINE__);}
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `$TABLE_WEEK_RTTH` (
			`zmd5` varchar(90) NOT NULL,
			`familysite` varchar(128) NOT NULL,
			`ipaddr` varchar(50) NOT NULL DEFAULT '',
			`day` smallint(2) NOT NULL,
			`hour` smallint(2) NOT NULL,
			`uid` varchar(128) NOT NULL,
			`MAC` varchar(20) NOT NULL,
			`size` BIGINT UNSIGNED NOT NULL,
			`category` varchar(90) NOT NULL,
			PRIMARY KEY `zmd5` (`zmd5`),
			KEY `familysite` (`familysite`),
			KEY `ipaddr` (`ipaddr`),
			KEY `uid` (`uid`),
			KEY `category` (`category`),
			KEY `hour` (`hour`),
			KEY `day` (`day`),
			KEY `MAC` (`MAC`)
	) ENGINE=$ENGINE;");

	if(!$q->ok){
		if($asmeta){meta_admin_mysql(1, "MySQL error", $q->mysql_error,__FILE__,__LINE__);}
		echo $q->mysql_error;
		return;
	}


	$q->QUERY_SQL("INSERT IGNORE INTO `$TABLE_WEEK_RTTH` ( `zmd5`,`ipaddr`,`MAC`,`uid`,familysite,`day`,`hour`,`size`,`category`) VALUES ".@implode(",", $wwwUH));
	if(!$q->ok){
		if($asmeta){meta_admin_mysql(1, "MySQL error", $q->mysql_error,__FILE__,__LINE__);}
		echo $q->mysql_error;
		return;
	}



	if($asmeta){
		xmeta_events("Success parsing $path adding ".count($wwwUH)." elements",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	$sock=new sockets();
	$EnableArticaMetaClient=intval($sock->GET_INFO("EnableArticaMetaClient"));
	$EnableSquidRemoteMySQL=intval($sock->GET_INFO("EnableSquidRemoteMySQL"));
	if($EnableSquidRemoteMySQL==1){return;}
	if($EnableArticaMetaClient==0){return;}

	$DIR_TEMP=$unix->TEMP_DIR();
	if(!$unix->compress($path, "$DIR_TEMP/SQUID_QUOTASIZE.gz")){
		meta_admin_mysql(1, "Unable to compress $path", null,__FILE__,__LINE__);
		@unlink("$DIR_TEMP/SQUID_QUOTASIZE.gz");
		return;
	}
	$artica_meta=new artica_meta();
	if(!$artica_meta->SendFile("$DIR_TEMP/SQUID_QUOTASIZE.gz","SQUID_QUOTASIZE")){
		meta_admin_mysql(1, "Unable to updload $DIR_TEMP/SQUID_QUOTASIZE.gz", null,__FILE__,__LINE__);

	}
	@unlink("$DIR_TEMP/SQUID_QUOTASIZE.gz");
}

function events($text){
	
	$file=basename(__FILE__);
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$function=$trace[1]["function"];
			$line=$trace[1]["line"];
		}
	}
	
	$pid=getmypid();
	$date=date("H:i:s");
	$logFile="/var/log/StatsAppliance.debug";
	$size=filesize($logFile);
	if($size>1000000){unlink($logFile);}
	$f = @fopen($logFile, 'a');
	
	$line="$date {$filename}[{$pid}] $text\n";
	if($GLOBALS["VERBOSE"]){echo "$line";}
	if($_GET["DEBUG"]){echo $line;}
	@fwrite($f,$line);
	@fclose($f);	
	
}

function parse_userauthdb($path,$uuid=null,$asmeta=false){
	$unix=new unix();

	$f=array();
	if($GLOBALS["VERBOSE"]){echo "Parsing $path\n";}
	if(!is_file($path)){
		if($GLOBALS["VERBOSE"]){echo "$path no such file\n";}
		return;
	}

	$db_con = dba_open($path, "r","db4");
	if(!$db_con){
		if($asmeta){meta_admin_mysql(1, "DB open failed $path", null,__FILE__,__LINE__);}
		echo "DB open failed\n";
		die();
	}

	$mainkey=dba_firstkey($db_con);

	while($mainkey !=false){
		$val=0;

		$array=unserialize(dba_fetch($mainkey,$db_con));
		$mac=$array["MAC"];
		if(strlen($mac)>17){$mac=clean_mac($mac);}
		$ipaddr=$array["IPADDR"];
		$uid=$array["uid"];
		$hostname=$array["hostname"];
		$UserAgent=$array["UserAgent"];
		$UserAgent=mysql_escape_string2($UserAgent);
		$uid=mysql_escape_string2($uid);
		$ipaddr=mysql_escape_string2($ipaddr);
		$mac=mysql_escape_string2($mac);
		$f[]="('$mainkey','$mac','$ipaddr','$uid','$hostname','$UserAgent')";
		$mainkey=dba_nextkey($db_con);
	}
	dba_close($db_con);

	if(count($f)>0){
		if($GLOBALS["VERBOSE"]){echo "UserAutDB: INSERTING ".count($f)." elements\n";}
		$q=new mysql_squid_builder();
		if($uuid<>null){$q=new mysql_stats($uuid);}
		$sql="INSERT IGNORE INTO UserAutDB (zmd5,MAC,ipaddr,uid,hostname,UserAgent) VALUES ".@implode(",", $f);
		$q->QUERY_SQL("TRUNCATE TABLE `UserAutDB`");
		$q->QUERY_SQL($sql);
	}

}

function clean_mac($MAC){
	$f=explode(":",$MAC);
	while (list ($index, $line) = each ($f) ){

		if(strlen($line)>2){
			$line=substr($line, strlen($line)-2,2);
			$f[$index]=$line;
			continue;
		}
	}


	return @implode(":", $MAC);
}

function microtime_ms($start){
	return  round(microtime_float() - $start,3);
}


function microtime_float(){
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}