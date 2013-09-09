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


if(system_is_overloaded()){die();}

tables_hours();


function tables_hours(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	if($GLOBALS["VERBOSE"]){echo "timefile=$timefile\n";}
	
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
	
	
	$GLOBALS["Q"]=new mysql_squid_builder();
	$prefix=date("YmdH");
	
	$currenttable="squidhour_$prefix";
	
	if($GLOBALS["VERBOSE"]){echo "Current Table: $currenttable\n";}
	
	$tablesBrutes=$GLOBALS["Q"]->LIST_TABLES_WORKSHOURS();
	
	
	while (list ($tablename, $none) = each ($tablesBrutes) ){
		if($tablename==$currenttable){
			if($GLOBALS["VERBOSE"]){echo "Skip table: $tablename\n";}
			continue;
		}
		$t=time();
		
		if($GLOBALS["VERBOSE"]){echo "_table_hours_perform($tablename)\n";}
		if(_table_hours_perform($tablename)){
			$took=$unix->distanceOfTimeInWords($t,time());
			if($GLOBALS["VERBOSE"]){echo "Remove table: $tablename\n";}
			$GLOBALS["Q"]->QUERY_SQL("DROP TABLE `$tablename`");
			
				
			if(systemMaxOverloaded()){
				ufdbguard_admin_events("Fatal: VERY Overloaded system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]} aborting function",__FUNCTION__,__FILE__,__LINE__,"stats");
				return true;
			}
				
			if(system_is_overloaded()){
				ufdbguard_admin_events("Fatal: Overloaded system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]} sleeping 10s",__FUNCTION__,__FILE__,__LINE__,"stats");
				sleep(10);
			}
				
			if(system_is_overloaded()){
				ufdbguard_admin_events("Fatal: Overloaded system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]} sleeping stopping function",__FUNCTION__,__FILE__,__LINE__,"stats");
				return true;
			}
		}
	}
	


}
function _table_hours_perform($tablename){
	if(!isset($GLOBALS["Q"])){$GLOBALS["Q"]=new mysql_squid_builder();}
	if(!preg_match("#squidhour_([0-9]+)#",$tablename,$re)){return;}
	$hour=$re[1];
	$year=substr($hour,0,4);
	$month=substr($hour,4,2);
	$day=substr($hour,6,2);
	$compressed=false;
	$f=array();
	$dansguardian_table="dansguardian_events_$year$month$day";
	$accounts=$GLOBALS["Q"]->ACCOUNTS_ISP();
	
	if(!$GLOBALS["Q"]->Check_dansguardian_events_table($dansguardian_table)){return false;}
	$sql="SELECT COUNT(ID) as hits,SUM(QuerySize) as QuerySize,DATE_FORMAT(zDate,'%Y-%m-%d %H:00:00') as zDate,sitename,uri,TYPE,REASON,CLIENT,uid,remote_ip,country,cached,MAC,hostname FROM $tablename GROUP BY sitename,uri,TYPE,REASON,CLIENT,uid,remote_ip,country,cached,MAC,zDate,hostname";


	if($GLOBALS["VERBOSE"]){echo $sql."\n";}
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);

	
	if(!$GLOBALS["Q"]->ok){
		writelogs_squid("Fatal: {$GLOBALS["Q"]->mysql_error} on `$tablename`\n".@implode("\n",$GLOBALS["REPAIR_MYSQL_TABLE"]),__FUNCTION__,__FILE__,__LINE__,"stats");
		if(strpos(" {$GLOBALS["Q"]->mysql_error}", "is marked as crashed and should be repaired")>0){
			$q1=new mysql();
			writelogs_squid("try to repair table `$tablename`",__FUNCTION__,__FILE__,__LINE__,"stats");
			$q1->REPAIR_TABLE("squidlogs",$tablename);
			writelogs_squid(@implode("\n",$GLOBALS["REPAIR_MYSQL_TABLE"]),__FUNCTION__,__FILE__,__LINE__,"stats");
		}

		return false;
	}




	$prefix="INSERT IGNORE INTO $dansguardian_table (sitename,uri,TYPE,REASON,CLIENT,MAC,zDate,zMD5,uid,remote_ip,country,QuerySize,hits,cached,hostname,account) VALUES ";


	$d=0;

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$zmd=array();
		while (list ($key, $value) = each ($ligne) ){$ligne[$key]=mysql_escape_string2($value);$zmd[]=$value;}

		$zMD5=md5(@implode("",$zmd));
		$accountclient=null;
		if(isset($accounts[$ligne["CLIENT"]])){$accountclient=$accounts[$ligne["CLIENT"]];}
		$d++;
		
		$uid=$ligne["uid"];
		if($uid==null){$uid=$GLOBALS["Q"]->MacToUid($ligne["MAC"]);}
		if($uid==null){$uid=$GLOBALS["Q"]->IpToUid($ligne["CLIENT"]);}	
		$uid=mysql_escape_string2($uid);
		
		$hostname=$ligne["hostname"];
		if($hostname==null){$hostname=$GLOBALS["Q"]->MacToHost($ligne["MAC"]);}
		if($hostname==null){$hostname=$GLOBALS["Q"]->IpToHost($ligne["CLIENT"]);}
		$hostname=mysql_escape_string2($hostname);
		
		$f[]="('{$ligne["sitename"]}','{$ligne["uri"]}','{$ligne["TYPE"]}','{$ligne["REASON"]}','{$ligne["CLIENT"]}','{$ligne["MAC"]}','{$ligne["zDate"]}','$zMD5','$uid','{$ligne["remote_ip"]}','{$ligne["country"]}','{$ligne["QuerySize"]}','{$ligne["hits"]}','{$ligne["cached"]}','$hostname','$accountclient')";
		if(count($f)>500){
			$GLOBALS["Q"]->UncompressTable($dansguardian_table);
			$GLOBALS["Q"]->QUERY_SQL($prefix.@implode(",", $f));
			$f=array();
			if(!$GLOBALS["Q"]->ok){writelogs_squid("Fatal: {$GLOBALS["Q"]->mysql_error} on `$dansguardian_table`",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
		}

	}

	if(count($f)>0){
		$GLOBALS["Q"]->UncompressTable($dansguardian_table);
		$GLOBALS["Q"]->QUERY_SQL($prefix.@implode(",", $f));
		if(!$GLOBALS["Q"]->ok){writelogs_squid("Fatal: {$GLOBALS["Q"]->mysql_error} on `$dansguardian_table`",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
	}


	return true;

}
?>