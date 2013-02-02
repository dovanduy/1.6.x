<?php
$GLOBALS["BYPASS"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;}
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
include_once(dirname(__FILE__).'/ressources/class.squid.report.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/whois/whois.main.php');

if($argv[1]=="--all"){build_allreports();}
if($argv[1]=="--ID"){build_report($argv[2],true);}
if($argv[1]=="--csv"){SaveCSV($argv[2],true);}

function build_allreports(){
	$t=time();
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".$ID.pid";
	$oldpid=@file_get_contents($pidfile);
	if($oldpid<100){$oldpid=null;}
		
	if($unix->process_exists($oldpid,basename(__FILE__))){
		ufdbguard_admin_events("Already executed pid $oldpid",__FUNCTION__,__FILE__,__LINE__,"reports");
		if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid\n";}
		return;
	}
	$t=time();
	$sql="SELECT ID,report FROM TrackMembers WHERE scheduled=1";
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$report=utf8_decode($ligne["report"]);
		$c++;
		$tt=time();
		build_report($ID);
		ufdbguard_admin_events("$report builded, took ".$unix->distanceOfTimeInWords($tt,time(),true),__FUNCTION__,__FILE__,__LINE__,"reports");
		
	}
	
	
	ufdbguard_admin_events("$c reports builded, took ".$unix->distanceOfTimeInWords($t,time(),true),__FUNCTION__,__FILE__,__LINE__,"reports");	
}

function SaveCSV($ID){
	$unix=new unix();
	$chmod=$unix->find_program("chmod");
	$tablename="WebTrackMem$ID";
	@unlink("/home/squid-work/csv.txt");
	@mkdir("/home/squid-work",0777);
	$sql="SELECT * INTO OUTFILE '/home/squid-work/csv.txt' FIELDS TERMINATED BY ',' 
	OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\n' FROM $tablename;";
	shell_exec("chmod 1777 /home/squid-work");
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		ufdbguard_admin_events("CSV failed $q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"reports");	
		return;
	}
	
	$unix->compress("/home/squid-work/csv.txt", "/home/squid-work/csv.txt.gz");
	@unlink("/home/squid-work/csv.txt");
	$f=addslashes(@file_get_contents("/home/squid-work/csv.txt.gz"));
	@unlink("/home/squid-work/csv.txt.gz");
	$sql="UPDATE TrackMembers SET csvContent='$f' WHERE ID='$ID'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		ufdbguard_admin_events("CSV failed $q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"reports");	
		return;
	}	


	
}


function build_report($ID,$nopid=false){
	if(!is_numeric($ID)){
		ufdbguard_admin_events("Not a numeric ID",__FUNCTION__,__FILE__,__LINE__,"reports");
		return;
	}
	$t=time();
	$unix=new unix();
	$tablename="WebTrackMem$ID";
	$tableBlock="WebTrackMeB$ID";
	
	if(!$nopid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".$ID.pid";
		$oldpid=@file_get_contents($pidfile);
		if($oldpid<100){$oldpid=null;}
		
		if($unix->process_exists($oldpid,basename(__FILE__))){
			ufdbguard_admin_events("Already executed pid $oldpid",__FUNCTION__,__FILE__,__LINE__,"reports");
			if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid\n";}
			return;
		}
		
	}
	
	if($GLOBALS["VERBOSE"]){echo "Building report $ID\n";}
	$q=new mysql_squid_builder();
	if($q->TABLE_EXISTS($tablename)){$q->DELETE_TABLE($tablename);}
	if($q->TABLE_EXISTS($tableBlock)){$q->DELETE_TABLE($tableBlock);}
	
	
	
	if(!$q->CreateMemberReportTable($tablename)){
		ufdbguard_admin_events("could not create table $tablename",__FUNCTION__,__FILE__,__LINE__,"reports");
		return;
	}
	
	if(!$q->CreateMemberReportBlockTable($tableBlock)){
		ufdbguard_admin_events("could not create table $tablename",__FUNCTION__,__FILE__,__LINE__,"reports");
		return;
	}	
	
	$rp=new squid_report($ID);
	
	
	$LIST_TABLES_dansguardian_events=$q->LIST_TABLES_dansguardian_events();
	
	progress(10,$ID);
	$counttables=count($LIST_TABLES_dansguardian_events);

	
	$prefix="INSERT IGNORE INTO $tablename (`zMD5`,`sitename`,`familysite`,`$rp->userfield`,`zDate`,`size`,`hits`,`category`) VALUES ";
	
	while (list ($sourcetable, $ligne) = each ($LIST_TABLES_dansguardian_events)){
		$c++;
		
		$sql=$rp->BuildQuery($sourcetable);
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){
			ufdbguard_admin_events("$q->mysql_error\n$sql",__FUNCTION__,__FILE__,__LINE__,"reports");
			return;
		}		
		
		if($GLOBALS["VERBOSE"]){echo "Parsing $sourcetable \n$sql\n-> `". mysql_num_rows($results)."` rows\n";}
		
		if(mysql_num_rows($results)==0){continue;}
		$purc=round($c/$counttables,2)*100;
		progress($purc,$ID);
		$f=array();
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$md5=md5(serialize($ligne));
			$sitename=$ligne["sitename"];
			$familysite=$q->GetFamilySites($sitename);
			if(!isset($GLOBALS["CATEGORY"][$sitename])){$GLOBALS["CATEGORY"][$sitename]=$q->GET_CATEGORIES($sitename);}
			$category=$GLOBALS["CATEGORY"][$sitename];
			$source=addslashes($ligne["source"]);
			$zDate=$ligne["zDate"];
			$size=$ligne["size"];
			$hits=$ligne["hits"];
			$category=addslashes($category);
			$f[]="('$md5','$sitename','$familysite','$source','$zDate','$size','$hits','$category')";
		}
		
		if(count($f)==0){continue;}
		$q->QUERY_SQL($prefix.@implode(",", $f));
		if(!$q->ok){ufdbguard_admin_events("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"reports");return;}
		if(system_is_overloaded(__FILE__)){sleep(5);}
		
	}
	if($rp->csv==1){SaveCSV($ID);}
	$LIST_TABLES_BLOCKED=$q->LIST_TABLES_BLOCKED();
	$prefix="INSERT IGNORE INTO $tableBlock (`zMD5`,`zDate`,`hits`,`website`,`category`,`rulename`,`event`,`why`,`explain`,`blocktype`,`$rp->userfield`) VALUES ";
	while (list ($sourcetable, $ligne) = each ($LIST_TABLES_BLOCKED)){
		$c++;
		if($GLOBALS["VERBOSE"]){echo "Parsing $sourcetable\n";}
		$sql=$rp->BuildQueryBlock($sourcetable);
		if(!$q->FIELD_EXISTS("$sourcetable", "uid")){$q->QUERY_SQL("ALTER TABLE `$sourcetable` ADD `uid` VARCHAR( 128 ) NOT NULL ,ADD INDEX ( `uid` )");}
		
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){ufdbguard_admin_events("$q->mysql_error\n$sql",__FUNCTION__,__FILE__,__LINE__,"reports");return;}
		if(mysql_num_rows($results)==0){continue;}
		$purc=round($c/$counttables,2)*100;
		progress($purc,$ID);
		$f=array();
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$zMD5=md5(serialize($ligne));
			$sitename=$ligne["sitename"];
			$category=$ligne["category"];
			if($category==null){
				if(!isset($GLOBALS["CATEGORY"][$sitename])){$GLOBALS["CATEGORY"][$sitename]=$q->GET_CATEGORIES($sitename);}
				$category=$GLOBALS["CATEGORY"][$sitename];
			}
			$source=addslashes($ligne["source"]);
			$zDate=$ligne["zDate"];
			$hits=$ligne["hits"];
			$rulename=$ligne["rulename"];
			$event=$ligne["event"];
			$why=$ligne["why"];
			$explain=$ligne["explain"];
			$blocktype=$ligne["blocktype"];
			$category=addslashes($category);
			$f[]="('$zMD5','$zDate','$hits','$sitename','$category','$rulename','$event','$why','$explain','$blocktype','$source')";			
			
		}
		
		if(system_is_overloaded(__FILE__)){sleep(5);}
		
		
	}
	
	$myisamchk=$unix->find_program("myisamchk");
	$myisampack=$unix->find_program("myisampack");
	$mysql_data=$unix->MYSQL_DATA_DIR();
		echo "OPTIMIZE TABLE $tableBlock\n";
		$q->QUERY_SQL("OPTIMIZE TABLE $tableBlock");
		
		echo "OPTIMIZE TABLE $tablename\n";
		$q->QUERY_SQL("OPTIMIZE TABLE $tablename");		
		
		echo "LOCK TABLE $tablename\n";
		$q->QUERY_SQL("LOCK TABLE $tablename WRITE");
		
		echo "LOCK TABLE $tableBlock\n";
		$q->QUERY_SQL("LOCK TABLE $tableBlock WRITE");
		
		$q->QUERY_SQL("FLUSH TABLE $tableBlock");
		$q->QUERY_SQL("FLUSH TABLE $tablename");
		
		echo "myisamchk $tablename\n";
		shell_exec("$myisamchk -cFU $mysql_data/squidlogs/$tablename.MYI");
		echo "myisamchk $tableBlock\n";
		shell_exec("$myisamchk -cFU $mysql_data/squidlogs/$tableBlock.MYI");
		
		echo "myisampack $tablename\n";
		shell_exec("$myisampack -f $mysql_data/squidlogs/$tablename.MYI");	
		echo "myisampack $tableBlock\n";
		shell_exec("$myisampack -f $mysql_data/squidlogs/$tableBlock.MYI");	

		$q->QUERY_SQL("FLUSH TABLE $tablename");
		$q->QUERY_SQL("FLUSH TABLE $tableBlock");
	
	$rp->set_duration($unix->distanceOfTimeInWords($t,time(),true));
	
	progress(100,$ID);
}

function progress($purc,$ID){
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/squid.report.$ID.rp", $purc);
	
}