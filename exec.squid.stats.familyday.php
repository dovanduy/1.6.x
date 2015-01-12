<?php
$GLOBALS["BYPASS"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["SCHEDULED"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];$GLOBALS["SCHEDULED"]=true;}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
	if(preg_match("#--scheduled#",implode(" ",$argv))){$GLOBALS["SCHEDULED"]=true;}
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

if($argv[1]=="--condensed"){CONDENSED();exit;}



start();

function build_progress($text,$pourc){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;

	if(is_numeric($text)){
		$array["POURC"]=$text;
		$array["TEXT"]=$pourc;
	}
	if($GLOBALS["VERBOSE"]){echo "{$pourc}% $text\n";}
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.browse-familysites.progress.php.log";
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);

}

function start($xtime=0){


	
	$unix=new unix();
	if($GLOBALS["VERBOSE"]){"echo Loading done...\n";}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if($GLOBALS["VERBOSE"]){echo "Timefile = $timefile\n";}
	$pid=@file_get_contents($pidfile);
	if(!$GLOBALS["FORCE"]){
		if($pid<100){$pid=null;}
		$unix=new unix();
		if($unix->process_exists($pid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}return;}
		$timeexec=$unix->file_time_min($timefile);
		if(!$GLOBALS["SCHEDULED"]){
			if($timeexec<2880){return;}
		}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}
	
	@unlink($timefile);
	@file_put_contents($timefile, time());
	build_progress("Starting refreshing...",10);
	
	$sql="SELECT tablename, familyday,DATE_FORMAT(zDate,'%Y%m%d') AS `suffix` FROM tables_day 
			WHERE familyday=0 AND DAY(zDate)<DAY(NOW()) AND YEAR(zDate) = YEAR(NOW()) AND MONTH(zDate) = MONTH(NOW())";
	
	$q=new mysql_squid_builder();
	$q->CheckTables();
	
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){echo $q->mysql_error;return;}
	
	if($GLOBALS["VERBOSE"]){echo mysql_num_rows($results)." rows\n";}
	$c=0;
	$Max=mysql_num_rows($results);
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$worktable="{$ligne["suffix"]}_visited";
		$SourceTable="{$ligne["suffix"]}_hour";
		$desttable="{$ligne["suffix"]}_family";
		$c++;
		$prc=($c/$Max)*100;
		$prc=round($prc);
		if($prc>80){$prc=80;}
		build_progress("Checking ...$worktable $desttable",$prc);
		
		if(!$q->TABLE_EXISTS($worktable)){
			if(!$q->TABLE_EXISTS($SourceTable)){
				$q->QUERY_SQL("UPDATE tables_day SET familyday=1 WHERE tablename='{$ligne["tablename"]}'");
				continue;
			}
			
			if(!repair_visited_from_sources_table($SourceTable,$worktable)){continue;}
		
		}
		
		
		if(!perform($worktable, $desttable)){continue;}
		$q->QUERY_SQL("UPDATE tables_day SET familyday=1 WHERE tablename='{$ligne["tablename"]}'");
	}
	build_progress("Calculating master table",80);
	CONDENSED();
	build_progress("Done",100);
	
}
function CONDENSED(){
	
	$q=new mysql_squid_builder();
	if(!$q->CreateFamilyCondensed()){return false;}
	if(!$q->CreateFamilyMainTable()){return false;}
	$q->QUERY_SQL("TRUNCATE TABLE FamilyCondensed");
	$LIST_TABLES_FAMILY=$q->LIST_TABLES_FAMILY();
	while (list ($tablename, $ligne) = each ($LIST_TABLES_FAMILY)){
		build_progress("Calculating master table on $tablename",80);
		CONDENSED_perform($tablename);
	}
	$q->QUERY_SQL("TRUNCATE TABLE main_websites");
	$sql="SELECT SUM(size) as size,SUM(hits) AS `hits`, familysite FROM FamilyCondensed GROUP BY familysite";
	$results=$q->QUERY_SQL($sql);
	
	$prefix="INSERT IGNORE INTO main_websites (size,hits,familysite) VALUES ";
	
	build_progress("Builing  main_websites table on",90);
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$familysite=mysql_escape_string2($ligne["familysite"]);
		$zmd5=md5(serialize($ligne));
		$size=$ligne["size"];
		$hits=$ligne["hits"];
		echo "'$size','$hits','$familysite'\n";
		$f[]="('$size','$hits','$familysite')";
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

function CONDENSED_perform($sourcetable){
	$q=new mysql_squid_builder();
	$xtime=$q->TIME_FROM_DAY_TABLE($sourcetable);
	$zDate=date("Y-m-d",$xtime);
	echo "$sourcetable = $zDate\n";
	
	
	$sql="SELECT SUM(size) as size,SUM(hits) AS `hits`, familysite FROM $sourcetable GROUP BY familysite";
	$results=$q->QUERY_SQL($sql);
	
	$prefix="INSERT IGNORE INTO FamilyCondensed (zMD5,zDate,size,hits,familysite) VALUES ";
	$f=array();
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$md5=md5(serialize($ligne).$zDate);
		$familysite=mysql_escape_string2($ligne["familysite"]);
		$size=$ligne["size"];
		$hits=$ligne["hits"];
		$f[]="('$md5','$zDate','$size','$hits','$familysite')";
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


function perform($worktable,$desttable){


	$q=new mysql_squid_builder();
	
	if(!$q->CreateFamilyDayTable($desttable)){return false;}
	
	
	
	
	$sql="SELECT SUM(size) as size,SUM(hits) AS `hits`, familysite FROM $worktable GROUP BY familysite";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return false;}
	$count=mysql_num_rows($results);


	$OUS=array();
	if($count==0){return true;}


	$prefix="INSERT IGNORE INTO $desttable (zMD5,size,hits,familysite) VALUES ";
	$f=array();
	if($GLOBALS["VERBOSE"]){echo "$worktable $count rows\n";}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$md5=md5(serialize($ligne));
		$familysite=mysql_escape_string2($ligne["familysite"]);
		$size=$ligne["size"];
		$hits=$ligne["hits"];
		$f[]="('$md5','$size','$hits','$familysite')";
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

function repair_visited_from_sources_table($sourcetable,$daytable){
	
	//zMD5                             | sitename                   | familysite        | client        | hostname | account | hour | remote_ip     | MAC | country | size  | hits | uid           | category                      | cached
	$f=array();
	$sql="SELECT  sitename,familysite,SUM(size) as size, SUM(hits) as hits FROM $sourcetable GROUP BY `sitename`,familysite";
	$q=new mysql_squid_builder();
	
	
	$prefix="INSERT IGNORE INTO $daytable (`sitename`,`familysite`,`size`,`hits`) VALUES";
	$q=new mysql_squid_builder();
	if(!$q->CreateVisitedDayTable($daytable)){
		return false;
	}

	
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$zMD5=md5(serialize($ligne));
		while (list ($key,$val) = each ($ligne) ){$ligne[$key]=mysql_escape_string2($val); }
		$familysite=$ligne["familysite"];
		$sitename=$ligne["sitename"];
		$f[]="('{$ligne["sitename"]}','$familysite','{$ligne["size"]}','{$ligne["hits"]}')";
	
		if(count($f)>500){
			$q->QUERY_SQL($prefix.@implode(",", $f));
			if(!$q->ok){echo $q->mysql_error;return false;}
			$f=array();
		}
		
		
	}
		
	if(count($f)>0){
		$q->QUERY_SQL($prefix.@implode(",", $f));
		if(!$q->ok){echo $q->mysql_error;return false;}
	}

	return true;
}

function events_tail($text){

	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}

	}
	if($GLOBALS["VERBOSE"]){echo "$text ($sourceline)\n";}
	writelogs_squid($text,"",__FILE__,$sourceline,"stats",true);

}