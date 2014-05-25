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

Compile();

function Compile(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$oldpid=@file_get_contents($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid\n";}
		return;
	}	
	$t=time();
	
	
	$q=new mysql_squid_builder();
	echo "**** LIST_TABLES_CATEGORIES_PERSO *****\n";
	$tablescat=$q->LIST_TABLES_CATEGORIES_PERSO();
	$source_dir="/home/artica/categories_perso";
	
	if(count($tablescat)==0){
		echo "tablescat = 0\n";
		return;
	}
	$i=0;
	while (list ($tablename, $ligne) = each ($tablescat) ){

		if(preg_match("#^categoryuris#", $tablename)){continue;}
		$COUNT_ROWS=$q->COUNT_ROWS($tablename);
		if($COUNT_ROWS==0){continue;}
		
		echo " **** $tablename $COUNT_ROWS ITEMS *****\n";
		$Dir="$source_dir/$tablename";
		@mkdir("$Dir",0777,true);
		echo "$tablename: Building $Dir/domains";
		@unlink("$Dir/domains");
		@chmod("$Dir/domains",0777);
		$sql="SELECT pattern FROM $tablename WHERE enabled=1 ORDER BY pattern INTO OUTFILE '$Dir/domains' LINES TERMINATED BY '\n';";
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			echo "$tablename: $q->mysql_error\n";
			continue;
		}
		$handle = @fopen("$Dir/domains", "r");
		if (!$handle) {echo "Failed to open file $Dir/domains\n";continue;}
		$DestDB="$Dir/domains.db";
		@unlink($DestDB);
		$db_desttmp = dba_open($DestDB, "n","db4");
		if(!$db_desttmp){echo "Unable to Create $DestDB\n";continue;}
		dba_close($db_desttmp);
		$db_dest = dba_open($DestDB, "w","db4");
		@chmod($DestDB, 0777);
		if(!$db_dest){echo "Unable to open for `writing` \"$DestDB\"\n";continue;}
		while (!feof($handle)){
			$www =trim(fgets($handle, 4096));
			$www=trim(str_replace('"', "", $www));
			if($www==null){continue;}
			$www=strtolower($www);
			if(!dba_insert("$www","yes",$db_dest)){
				echo "dba_insert($www,yes... false\n";
				continue;
			}
					
		}
		$i++;
		dba_close($db_dest);
		@fclose($handle);
		@unlink("$Dir/domains");
	}
	

	stats_admin_events(2,"1%) $i Personal tables compiled took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
	
	
}

