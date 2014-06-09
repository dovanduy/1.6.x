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
include_once(dirname(__FILE__).'/ressources/class.squid.stats.tools.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");

if($argv[1]=="--stats-days"){StatsDaysTables();exit;}
if($argv[1]=="--gencaches"){gencaches_start();exit;}

scan_stats();


function scan_stats(){
	$unix=new unix();
	$sock=new sockets();
	
	$ARRAY=unserialize(base64_decode($sock->GET_INFO("SquidDynamicCaches")));
	if(!is_numeric($ARRAY["MAX_WWW"])){$ARRAY["MAX_WWW"]=100;}
	if(!is_numeric($ARRAY["LEVEL"])){$ARRAY["LEVEL"]=5;}
	if(!is_numeric($ARRAY["INTERVAL"])){$ARRAY["INTERVAL"]=420;}
	if(!is_numeric($ARRAY["MAX_TTL"])){$ARRAY["MAX_TTL"]=15;}
	
	$OnlyImages=intval($ARRAY["OnlyImages"]);
	$OnlyeDoc=intval($ARRAY["OnlyeDoc"]);
	$OnlyMultimedia=intval($ARRAY["OnlyMultimedia"]);
	$OnlyFiles=intval($ARRAY["OnlyFiles"]);
	

	
	
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	if($GLOBALS["VERBOSE"]){echo "Time File: $timefile\n";}
	$pid=@file_get_contents($pidfile);
	if($GLOBALS["FORCE"]){ToSyslog("StatsDaysTables(): Executed in --force mode");}
	
	if(!$GLOBALS["FORCE"]){
		if($pid<100){$pid=null;}
		$unix=new unix();
		if($unix->process_exists($pid,basename(__FILE__))){
			if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}
			ToSyslog("StatsDaysTables(): already executed pid $pid");
			return;
		}
		$timeexec=$unix->file_time_min($timefile);
		if($timeexec<$ARRAY["INTERVAL"]){return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}
	
	@unlink($timefile);
	@file_put_contents($timefile, time());
	
	$q=new mysql_squid_builder();
	$DoNotCache["s497977761.onlinehome.fr"]=true;
	$DoNotCache["articatech.com"]=true;
	$DoNotCache["unveiltech.com"]=true;
	$DoNotCache["artica.fr"]=true;
	$DoNotCache["articatech.net"]=true;
	
	$sql="SELECT pattern FROM webfilters_blkwhlts WHERE blockType=4";
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$DoNotCache[$ligne["pattern"]]=true;
	}
	
	while (list ($www, $ligne) = each ($DoNotCache) ){
		$q->QUERY_SQL("DELETE FROM main_cache_dyn WHERE familysite='$www'");
	}
		
	
	

	$StartItems=$q->COUNT_ROWS("main_cache_dyn");
	
	$sql="SELECT familysite FROM main_cache_dyn WHERE zDate< DATE_SUB(NOW(),INTERVAL {$ARRAY["MAX_TTL"]} DAY)";
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$familysite=$ligne["familysite"];
		$removed[]=$familysite;
		if($GLOBALS["VERBOSE"]){echo "Remove $familysite\n";}
		$q->QUERY_SQL("DELETE FROM main_cache_dyn WHERE familysite='$familysite'");
	}
	if(count($removed)>0){
		squid_admin_mysql(1, "Optimize cache: ".count($removed)." removed websites.", @implode("\n", $removed));
	}
	
	if($q->COUNT_ROWS(main_cache_dyn)>=$ARRAY["MAX_WWW"]){return;}
	
	
	
	$currentTable=date("Ymd")."_gcache";
	$sql="SELECT SUM(size) as size,cached,familysite FROM `$currentTable` 
	GROUP BY familysite,cached HAVING cached=0 
	ORDER BY size DESC LIMIT 0,1000;";
	
	$q->QUERY_SQL($sql);

	$LEVELS[1]="1440\t60%\t1440";
	$LEVELS[2]="1440\t50%\t".(1440*2);
	$LEVELS[3]=(1440*1)."\t40%\t".(1440*4);
	$LEVELS[4]=(1440*2)."\t30%\t".(1440*6);
	$LEVELS[5]=(1440*3)."\t20%\t".(1440*7);
	$LEVELS[6]=(1440*4)."\t15%\t".(1440*9);
	$LEVELS[7]=(1440*5)."\t10%\t".(1440*10);
	$LEVELS[8]=(1440*6)."\t5%\t".(1440*14);
	$LEVELS[9]=(1440*7)."\t3%\t".(1440*20);
	$LEVELS[10]=(1440*8)."\t2%\t".(1440*30);
	
	
	$results=$q->QUERY_SQL($sql);
	$Count=mysql_num_rows($results);
	if($GLOBALS["VERBOSE"]){echo $sql."\n$Count items \n";}
	if($Count==0){return ;}
	$c=0;
	$added=array();
	$date=date("Y-m-d H:i:s");
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(isset($DoNotCache[$ligne["familysite"]])){continue;}
		if(WebSitesIsadded($ligne["familysite"])){
			if($GLOBALS["VERBOSE"]){echo "Already added {$ligne["familysite"]}\n";}
			continue;}
		$c++;
		
		$sql="INSERT IGNORE INTO main_cache_dyn (`familysite`,`enabled`,`level`,`zDate`,`OnlyImages`,`OnlyeDoc`,`OnlyMultimedia`,`OnlyFiles`) 
		VALUES ('{$ligne["familysite"]}','1','{$ARRAY["LEVEL"]}','$date',$OnlyImages,$OnlyeDoc,$OnlyMultimedia,$OnlyFiles)";
		$q->QUERY_SQL($sql);
		
		if(!$q->ok){return false;}
		if($GLOBALS["VERBOSE"]){echo "Adding {$ligne["familysite"]}\n";}
		$added[]=$ligne["familysite"];
		if($c>=10){
			if($GLOBALS["VERBOSE"]){echo "Break...\n";}
			break;}
	}
	
	
	
	if($c>0){
		squid_admin_mysql(1, "Optimize cache: ".count($added)." added websites.", @implode("\n", $added));
		$php=$unix->LOCATE_PHP5_BIN();
		shell_exec("$php /usr/share/artica-postfix/exec.squid.php --dyn-caches --reload");
		return;
	}
	
	$StopItems=$q->COUNT_ROWS("main_cache_dyn");
	
	if($StopItems<>$StartItems){
		squid_admin_mysql(1, "Optimize cache: ".count($added)." added websites.", @implode("\n", $added));
		$php=$unix->LOCATE_PHP5_BIN();
		shell_exec("$php /usr/share/artica-postfix/exec.squid.php --dyn-caches --reload");
		return;
	}
	
}

function WebSitesIsadded($familysite){
	$q=new mysql_squid_builder();
	
	$sql="SELECT level FROM main_cache_dyn WHERE familysite='$familysite'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	
	if(!$q->ok){
		if($GLOBALS["VERBOSE"]){echo $q->mysql_error."\n";}
		return false;
	}
	
	if($ligne["level"]>0){return true;}
	return false;
}

function StatsDaysTables(){
	$unix=new unix();
	$min=1440;
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if($GLOBALS["VERBOSE"]){echo "Time File: $timefile\n";}
	$pid=@file_get_contents($pidfile);
	if($GLOBALS["FORCE"]){ToSyslog("StatsDaysTables(): Executed in --force mode");}
	
	if(!$GLOBALS["FORCE"]){
		if($pid<100){$pid=null;}
		$unix=new unix();
		if($unix->process_exists($pid,basename(__FILE__))){
			if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}
			ToSyslog("StatsDaysTables(): already executed pid $pid");
			return;
		}
		$timeexec=$unix->file_time_min($timefile);
		if($timeexec<$min){return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}
	
	@unlink($timefile);
	@file_put_contents($timefile, time());
	
	
	$q=new squid_stats_tools();
	$q->check_cachedays();
}


function ToSyslog($text){

	$LOG_SEV=LOG_INFO;
	if(function_exists("openlog")){openlog("danguardian-injector", LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
	if(function_exists("closelog")){closelog();}
}

function gencaches_start(){
	$unix=new unix();
	$min=10;
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if($GLOBALS["VERBOSE"]){echo "Time File: $timefile\n";}
	$pid=@file_get_contents($pidfile);
	if($GLOBALS["FORCE"]){ToSyslog("gencaches_start(): Executed in --force mode");}
	
	if(!$GLOBALS["FORCE"]){
		if($pid<100){$pid=null;}
		$unix=new unix();
		if($unix->process_exists($pid,basename(__FILE__))){
			if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}
			ToSyslog("gencaches_start(): already executed pid $pid");
			return;
		}
		$timeexec=$unix->file_time_min($timefile);
		if($timeexec<$min){return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}
	
	@unlink($timefile);
	@file_put_contents($timefile, time());	
	
	gencaches();
	gencache_day();
	gencache_hier();
	gencache_TOP();
}

function gencaches(){
	$file="/usr/share/artica-postfix/ressources/logs/web/CACHED_HOUR.db";
	$file2="/usr/share/artica-postfix/ressources/logs/web/NOT_CACHED_HOUR.db";
	$current_table="cachehour_".date("YmdH");
	@unlink($file);
	@unlink($file2);
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS($current_table)){
		if($GLOBALS["VERBOSE"]){echo "$current_table no such table...\n";}
		return;}
		
	$sql="SELECT MINUTE(zDate) as min ,cached,SUM(size) as size FROM $current_table GROUP BY cached,min ORDER BY min";
	$results=$q->QUERY_SQL($sql);
	$Count=mysql_num_rows($results);
	if($GLOBALS["VERBOSE"]){echo $sql."\n$Count items \n";}
	if($Count<2){return ;}	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$cached=$ligne["cached"];
		if($cached==1){
			$ydataC[]=round($ligne["size"]/1024);
			$xdataC[]=$ligne["min"];
			continue;
		}
		
		$ydata[]=round($ligne["size"]/1024);
		$xdata[]=$ligne["min"];
	}
	
	
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/CACHED_HOUR.db", 
			serialize(array($xdataC, $ydataC  )));
	
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/NOT_CACHED_HOUR.db",
			serialize(array($xdata, $ydata  )));	
	
	@chmod("/usr/share/artica-postfix/ressources/logs/web/CACHED_HOUR.db", 0755);
	@chmod("/usr/share/artica-postfix/ressources/logs/web/NOT_CACHED_HOUR.db", 0755);
}

function gencache_day(){
	$file1="/usr/share/artica-postfix/ressources/logs/web/CACHED_DAY.db";
	$file2="/usr/share/artica-postfix/ressources/logs/web/NOT_CACHED_DAY.db";	
	@unlink($file1);
	@unlink($file2);
	$current_table=date("Ymd")."_gcache";
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS($current_table)){
		if($GLOBALS["VERBOSE"]){echo "$current_table no such table...\n";}
		return;}
		
		$sql="SELECT `hour` as hour ,cached,SUM(size) as size FROM 
		$current_table GROUP BY cached,hour ORDER BY  `hour`";
		
		
		$results=$q->QUERY_SQL($sql);
		$Count=mysql_num_rows($results);
		if($GLOBALS["VERBOSE"]){echo $sql."\n$Count items \n";}
		if($Count<2){return ;}
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$cached=$ligne["cached"];
			if($cached==1){
				$ydataC[]=round($ligne["size"]/1024);
				$xdataC[]=$ligne["hour"];
				continue;
			}
		
			$ydata[]=round($ligne["size"]/1024);
			$xdata[]=$ligne["hour"];
		}
		
		
		@file_put_contents($file1,
				serialize(array($xdataC, $ydataC  )));
		
		@file_put_contents($file2,
				serialize(array($xdata, $ydata  )));
		
		@chmod($file2, 0755);
		@chmod($file1, 0755);	
	
}
function gencache_hier(){
	$file1="/usr/share/artica-postfix/ressources/logs/web/CACHED_HIER_DAY.db";
	$file2="/usr/share/artica-postfix/ressources/logs/web/NOT_CACHED_HIER_DAY.db";
	@unlink($file1);
	@unlink($file2);
	$q=new mysql_squid_builder();
	$xtime=strtotime($q->HIER()." 00:00:00");
	$current_table=date("Ymd",$xtime)."_gcache";
	
	if(!$q->TABLE_EXISTS($current_table)){
		if($GLOBALS["VERBOSE"]){echo "$current_table no such table...\n";}
		return;}

		$sql="SELECT `hour` as hour ,cached,SUM(size) as size FROM
		$current_table GROUP BY cached,hour ORDER BY  `hour`";


		$results=$q->QUERY_SQL($sql);
		$Count=mysql_num_rows($results);
		if($GLOBALS["VERBOSE"]){echo $sql."\n$Count items \n";}
		if($Count<2){return ;}

		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$cached=$ligne["cached"];
			if($cached==1){
				$ydataC[]=round($ligne["size"]/1024);
				$xdataC[]=$ligne["hour"];
				continue;
			}

			$ydata[]=round($ligne["size"]/1024);
			$xdata[]=$ligne["hour"];
		}


		@file_put_contents($file1,
				serialize(array($xdataC, $ydataC  )));

		@file_put_contents($file2,
				serialize(array($xdata, $ydata  )));

		@chmod($file2, 0755);
		@chmod($file1, 0755);

}
function gencache_TOP(){
	$file1="/usr/share/artica-postfix/ressources/logs/web/TOP_CACHED.db";
	$file2="/usr/share/artica-postfix/ressources/logs/web/TOP_NOT_CACHED.db";
	@unlink($file1);
	@unlink($file2);
	$q=new mysql_squid_builder();
	
	$current_table=date("Ymd")."_gcache";
	if(!$q->TABLE_EXISTS($current_table)){
		$xtime=strtotime($q->HIER()." 00:00:00");
		$current_table=date("Ymd",$xtime)."_gcache";
	}

	if(!$q->TABLE_EXISTS($current_table)){ if($GLOBALS["VERBOSE"]){echo "$current_table no such table...\n";} return;}

		$sql="SELECT familysite,cached,SUM(size) as size FROM
		$current_table GROUP BY cached,familysite HAVING cached=1 ORDER BY `size` DESC LIMIT 0,10";
		$results=$q->QUERY_SQL($sql);
		$Count=mysql_num_rows($results);
		if($GLOBALS["VERBOSE"]){echo $sql."\n$Count items \n";}
		if($Count<2){return ;}

		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$size=$ligne["size"];
			$size=$size/1024;
			$size=$size/1024;
			$size=round($size);
			$ARRAY[$ligne["familysite"]]=$size;
		}
		@file_put_contents($file1,serialize($ARRAY));
		@chmod($file1, 0755);
		$ARRAY=array();
		
		
		$sql="SELECT familysite,cached,SUM(size) as size FROM
		$current_table GROUP BY cached,familysite HAVING cached=0 ORDER BY `size` DESC LIMIT 0,10";
		$results=$q->QUERY_SQL($sql);
		$Count=mysql_num_rows($results);
		if($GLOBALS["VERBOSE"]){echo $sql."\n$Count items \n";}
		if($Count<2){return ;}
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$size=$ligne["size"];
			$size=$size/1024;
			$size=$size/1024;
			$size=round($size);
			$ARRAY[$ligne["familysite"]]=$size;
		}
		@file_put_contents($file2,serialize($ARRAY));
		@chmod($file2, 0755);
		$ARRAY=array();

		

}