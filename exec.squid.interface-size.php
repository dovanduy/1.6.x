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
$date=date("YW");
// --meta \"$TEMP_DIR/squidqsize.$uuid.db\" $uuid
if($argv[1]=="--meta"){parse_meta($argv[2],$argv[3]);exit;}
if($argv[1]=="--size"){parse_size_cache("/var/log/squid/{$date}_size.db");exit;}
if($argv[1]=="--stats-app"){parse_stats();exit;}
if($argv[1]=="--month"){ThisMonthInterface();exit;}
if($argv[1]=="--cached"){Cached_websites();exit;}



parse();
function parse(){
	$TimeFile="/etc/artica-postfix/pids/exec.squid.interface-size.php.time";
	$pidfile="/etc/artica-postfix/pids/exec.squid.interface-size.php.pid";
	$unix=new unix();
	
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["VERBOSE"]){echo "$pid already executed since {$timepid}Mn\n";}
		if($timepid<14){return;}
		$kill=$unix->find_program("kill");
		unix_system_kill_force($pid);
	}
	
	@file_put_contents($pidfile, getmypid());
	if(!$GLOBALS["VERBOSE"]){
		$time=$unix->file_time_min($TimeFile);
		if($time<14){
			echo "Current {$time}Mn, require at least 14mn\n";
			return;
		}
	}
	
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	$sock=new sockets();
	ThisMonthInterface();
	Cached_websites();
	$EnableSquidRemoteMySQL=intval($sock->GET_INFO("EnableSquidRemoteMySQL"));
	$date=date("YW");
	$path="/var/log/squid/{$date}_QUOTASIZE.db";
	
	if(!PUSH_STATS_FILE($path)){ ParseDB_FILE($path); }
	if(!PUSH_STATS_FILE("/var/log/squid/UserAuthDB.db")){ parse_userauthdb("/var/log/squid/UserAuthDB.db"); }
	if(!PUSH_STATS_FILE("/var/log/squid/{$date}_size.db")){ parse_size_cache("/var/log/squid/{$date}_size.db"); }
	parse_stats();
}

function parse_stats(){
	
	if(!is_dir("/usr/share/artica-postfix/ressources/conf/upload/BEREKLEY")){return;}
	if (!$handle = opendir("/usr/share/artica-postfix/ressources/conf/upload/BEREKLEY")) {return;}
	
		while (false !== ($fileZ = readdir($handle))) {
			if($fileZ=="."){continue;}
			if($fileZ==".."){continue;}
			if($GLOBALS["VERBOSE"]){echo "Scanning upload/BEREKLEY/$fileZ\n";}
			$path="/usr/share/artica-postfix/ressources/conf/upload/BEREKLEY/$fileZ";
			
			if(preg_match("#^(.+?)-UserAuthDB\.db#", $fileZ,$re)){
				parse_userauthdb($path,$re[1]);
				@unlink($path);
				continue;
				
			}
			if(preg_match("#^(.+?)-[0-9]+_size\.db#", $fileZ,$re)){
				parse_size_cache($path,$re[1]);
				@unlink($path);
				continue;
			
			}			
			if(preg_match("#^(.+?)-[0-9]+_QUOTASIZE\.db#", $fileZ,$re)){
				ParseDB_FILE($path,$re[1]);
				@unlink($path);
				continue;
			}	

		}
}


function PUSH_STATS_FILE($filepath){
	$sock=new sockets();
	$unix=new unix();
	$EnableSquidRemoteMySQL=intval($sock->GET_INFO("EnableSquidRemoteMySQL"));
	if($EnableSquidRemoteMySQL==0){return false;}
	$WizardStatsAppliance=unserialize(base64_decode($sock->GET_INFO("WizardStatsAppliance")));
	$proto="http";
	if($WizardStatsAppliance["SSL"]==1){$proto="https";}
	$uri="$proto://{$WizardStatsAppliance["SERVER"]}:{$WizardStatsAppliance["PORT"]}/nodes.listener.php";
	
	$size=@filesize($filepath);
	$filename=basename($filepath);
	$array=array(
			"SQUID_BEREKLEY"=>true,
			"UUID"=>$unix->GetUniqueID(),
			"HOSTNAME"=>$unix->hostname_g(),"SIZE"=>$size,"FILENAME"=>$filename);
	
	
	$curl=new ccurl($uri,false,null,true);
	$curl->x_www_form_urlencoded=false;
	
	if(!$curl->postFile(basename($filepath),$filepath,$array )){
		return false;
	}
	return true;
	
	
}

function parse_meta($path,$uuid){
	$md_path=md5($path);
	$TimeFile="/etc/artica-postfix/pids/exec.squid.interface-size.php.$uuid.$md_path.time";
	$pidfile="/etc/artica-postfix/pids/exec.squid.interface-size.php.$uuid.$md_path.pid";
	$unix=new unix();
	
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["VERBOSE"]){echo "$pid already executed since {$timepid}Mn\n";}
		if($timepid<10){
			xmeta_events("$pid already executed since {$timepid}Mn",__FUNCTION__,__FILE__,__LINE__);
			return;}
		$kill=$unix->find_program("kill");
		unix_system_kill_force($pid);
	}
	@file_put_contents($pidfile, getmypid());
	$time=$unix->file_time_min($TimeFile);
	if(!$GLOBALS["VERBOSE"]){
		if($time<10){
			xmeta_events("{$time}Mn require at least $time",__FUNCTION__,__FILE__,__LINE__);
			@unlink($path);
			return;
		}
	}
	
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	
	if($GLOBALS["VERBOSE"]){echo "ParseDB_FILE($path,$uuid,true)\n";}
	xmeta_events("Parsing $path",__FUNCTION__,__FILE__,__LINE__);
	ParseDB_FILE($path,$uuid,true);
	
	if($GLOBALS["VERBOSE"]){echo "Remove $path\n";}
	@unlink($path);
}

function parse_size_cache($path,$uuid=null,$asmeta=false){
	$unix=new unix();
	
	if($GLOBALS["VERBOSE"]){echo "Parsing $path\n";}
	if(!is_file($path)){
		if($GLOBALS["VERBOSE"]){echo "$path no such file\n";}
		return;
	}
	
	$db_con = dba_open($path, "r","db4");
	if(!$db_con){
	if($asmeta){meta_admin_mysql(1, "DB open failed $path", null,__FILE__,__LINE__);}
	echo "DB open failed\n"; die(); }
	
	$CURRENT=intval(dba_fetch("TOTALS_CACHED",$db_con));
	if($GLOBALS["VERBOSE"]){echo "*** CACHED: TOTALS_CACHED = $CURRENT\n";}
	
	$TOTALS_CACHED=dba_fetch("TOTALS_CACHED",$db_con);
	$TOTALS_NOT_CACHED=dba_fetch("TOTALS_NOT_CACHED",$db_con);
	
	$arrayT["TOTALS_NOT_CACHED"]=$TOTALS_NOT_CACHED;
	$arrayT["TOTALS_CACHED"]=$TOTALS_CACHED;
	
	
	$mainkey=dba_firstkey($db_con);
	
	while($mainkey !=false){
		$val=0;
		
		
		if($mainkey=="TOTALS_CACHED"){
			$mainkey=dba_nextkey($db_con);
			continue;
			
		}
		
		if($mainkey=="TOTALS_NOT_CACHED"){
			$mainkey=dba_nextkey($db_con);
			continue;
				
		}		
		
		$data=dba_fetch($mainkey,$db_con);
		echo " **** $mainkey ***** $data\n";
		$mainkey=dba_nextkey($db_con);
	
	}	
	
	dba_close($db_con);
	
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/TOTAL_CACHED", serialize($arrayT));
	@chmod("/usr/share/artica-postfix/ressources/logs/web/TOTAL_CACHED",0777);
	
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

function xmeta_events($text,$function,$file,$line){
	$unix=new unix();
	$unix->events($text,"/var/log/artica-meta.log",false,$function,$line,$file);
	
}


function Cached_websites(){
	$date=date("YW")."_wwwcached.db";
	$dbfile="/var/log/squid/$date";
	if(!is_file($dbfile)){return;}
	
	$wberk=new parse_berekley_dbs();
	$q=new mysql_squid_builder();
	
	$sql=$wberk->SQUID_CACHED_SITES_TABLE_STRING("CACHED_SITES","MEMORY");
	$q->QUERY_SQL($sql);
	if(!$q->ok){return;}
	$array=$wberk->SQUID_CACHED_SITES($dbfile);
	if(count($array)==0){return;}
	
	$prefix=$wberk->SQUID_CACHED_SITES_TABLE_PREFIX("CACHED_SITES");
	$q->QUERY_SQL($prefix.@implode(",", $array));
	if(!$q->ok){echo $q->mysql_error;}
	
	
	
}

function ThisMonthInterface(){
	
	$cache_file="/usr/share/artica-postfix/ressources/logs/web/SQUID_MQUOTAZIE.db";
	$unix=new unix();
	
	if($unix->file_time_min($cache_file)<320){return;}
	
	$q=new mysql_squid_builder();
	$tablename_month=date("Ym")."_MQUOTASIZE";
	if(!$q->TABLE_EXISTS($tablename_month)){
		if($GLOBALS["VERBOSE"]){echo "$tablename_month no such table\n";}
		@unlink($cache_file);
		return;
	}
	
	
	$sql="SELECT SUM(size) as size,`day` FROM $tablename_month GROUP BY `day`ORDER BY `day`";
	if($GLOBALS["VERBOSE"]){echo "$sql\n";}
	$results=$q->QUERY_SQL("SELECT SUM(size) as size,`day` FROM $tablename_month GROUP BY `day` ORDER BY `day`");
	if(!$q->ok){echo $q->mysql_error;}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($GLOBALS["VERBOSE"]){echo "{$ligne["day"]} - > {$ligne["size"]}\n";}
		$array[$ligne["day"]]=$ligne["size"];
	}
	
	@unlink($cache_file);
	@file_put_contents($cache_file, serialize($array));
	@chmod($cache_file,0755);
	
}


