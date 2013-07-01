<?php
$GLOBALS["BYPASS"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;

if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){
		$GLOBALS["VERBOSE"]=true;
		//$GLOBALS["DEBUG_MEM"]=true;
		ini_set('display_errors', 1);
		ini_set('html_errors',0);
		ini_set('display_errors', 1);
		ini_set('error_reporting', E_ALL);		
}
if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}


if($GLOBALS["VERBOSE"]){"******* echo Loading... *******\n";}

include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");

$GLOBALS["Q"]=new mysql_squid_builder();

if($GLOBALS["VERBOSE"]){"echo Parsing arguments...\n";}

$sock=new sockets();
$DisableLocalStatisticsTasks=$sock->GET_INFO("DisableLocalStatisticsTasks");
if(!is_numeric($DisableLocalStatisticsTasks)){$DisableLocalStatisticsTasks=0;}
if($DisableLocalStatisticsTasks==1){die();}

if($argv[1]=="--table"){_xprocess_table($argv[2]);exit;}
if($argv[1]=="--all"){process_all_tables();exit;}
if($argv[1]=="--xtime"){process_xtable($argv[2]);exit;}
if($argv[1]=="--repair-tables"){repair_tables();exit;}



function process_xtable($xtime){
	$GLOBALS["Q"]=new mysql_squid_builder();
	$table=date("Ymd",$xtime)."_hour";
	events_tail("process_xtable:: Processing $table");
	_xprocess_table($table);
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $php /usr/share/artica-postfix/exec.squid.stats.totals.php --xtime $xtime >/dev/null 2>&1 &");
	
}

function process_all_tables(){
	
	if($GLOBALS["VERBOSE"]){echo "Loading...\n";}
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
		
	if($GLOBALS["VERBOSE"]){"echo Loading done...\n";}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$oldpid=@file_get_contents($pidfile);
	if(!$GLOBALS["FORCE"]){
		if($oldpid<100){$oldpid=null;}
		$unix=new unix();
		if($unix->process_exists($oldpid,basename(__FILE__))){
				if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid\n";}
				return;
		}
		$timeexec=$unix->file_time_min($timefile);
		if($timeexec<720){
			if($GLOBALS["VERBOSE"]){echo "{$timeexec} <>720...\n";}
			return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}
	@file_put_contents($timefile, time());
	$q=new mysql_squid_builder();
	
	$tables=$q->LIST_TABLES_HOURS();
	while (list ($tablename, $ligne) = each ($tables)){
		_xprocess_table($tablename);
		$xtime=$q->TIME_FROM_DAY_TABLE($tablename);
		shell_exec("$nohup $php /usr/share/artica-postfix/exec.squid.stats.totals.php --xtime $xtime >/dev/null 2>&1 &");

		if(system_is_overloaded(__FILE__)){
			writelogs_squid("Overloaded system {$GLOBALS["SYSTEM_INTERNAL_LOAD"]} aborting task..");
			return;
		}
		
	}
	
	
}

function _xprocess_table($tablename,$nopid=false){
	if($GLOBALS["VERBOSE"]){echo "Loading...\n";}
	$unix=new unix();
	
	if($GLOBALS["VERBOSE"]){echo "Loading done...\n";}
	if(!$nopid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".$tablename.pid";
		$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".$tablename.time";
		$oldpid=@file_get_contents($pidfile);
		if(!$GLOBALS["FORCE"]){
			if($oldpid<100){$oldpid=null;}
			$unix=new unix();
			if($unix->process_exists($oldpid,basename(__FILE__))){
				events_tail("$tablename:: Already executed pid $oldpid");
				if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid\n";}
				return;
			}
		
			$timeexec=$unix->file_time_min($timefile);
			if($timeexec<120){
				categorize_tables_events("$tablename:: {$timeexec}mn, need 120mn...","pid: ".getmypid(),$tablename);
				events_tail("$tablename:: {$timeexec}mn, need 120mn...");
				return;
			}
			$mypid=getmypid();
			@file_put_contents($pidfile,$mypid);
		}	
	}
	
	$q=new mysql_squid_builder();
	
	$sql="SELECT COUNT(`sitename`) as tcount FROM $hourtable WHERE LENGTH(`category`)=0";
	if($GLOBALS["VERBOSE"]){echo $sql."\n";}
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$max=$ligne["tcount"];
	
	
	$filePath="/usr/share/artica-postfix/ressources/logs/categorize-tables/$tablename";
	$ARRAY=unserialize(@file_get_contents($filePath));
	
	if($max>10000){
		$LIMIT_MAX=3000;
		$LIMIT_MIN=0;
		if(isset($ARRAY["CURRENT"])){
			if($ARRAY["CURRENT"]<$max-3000){
				$LIMIT_MIN=$ARRAY["CURRENT"];
			}
		}
		$LIMIT_SQL=" LIMIT $LIMIT_MIN,$LIMIT_MAX";
	}
	
	$sql="SELECT `sitename`,`familysite` FROM $tablename WHERE LENGTH(`category`)=0 ORDER BY familysite $LIMIT_SQL";
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		events_tail("$tablename:: MySQL error","$q->mysql_error");
		categorize_tables_events("MySQL error","$q->mysql_error<br>$sql",$tablename);
		return;
	}
	
	$catz=0;
	
	if(mysql_num_rows($results)>0){categorize_tables_events("$tablename: Processing $max ($LIMIT_SQL) not categorized websites","pid: ".getmypid(),$tablename);}
	
	if(mysql_num_rows($results)==0){
		if($GLOBALS["VERBOSE"]){echo "O, nothing, return...\n";}
		@unlink($filePath);
		return;
	}
	
	$c=0;$d=0;
	$t=time();
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$sitename=$ligne["sitename"];
		$sitenameToScan=$sitename;
		
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $sitenameToScan)){
			$sitename=gethostbyaddr($sitename);
			if($GLOBALS["VERBOSE"]){echo "IP:$sitenameToScan -> `$sitename`\n";}
			if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $sitename)){
				$GLOBALS[$tablename][$sitename]="ipaddr";
			}
		}
		
		
		$c++;
		if(!isset($GLOBALS[$tablename][$sitename])){
			$GLOBALS[$tablename][$sitename]=mysql_escape_string($GLOBALS["Q"]->GET_CATEGORIES($sitename));
		}
		$category=$GLOBALS[$tablename][$sitename];
		if($GLOBALS["VERBOSE"]){echo "$sitename -> `$category`\n";}
		$c++;
		$d++;
		if($category==null){continue;}
		if(!isset($UPDATED[$sitenameToScan])){
			$sqltmp="UPDATE `$tablename` SET `category`='$category' WHERE `sitename`='$sitenameToScan'";
			$q->QUERY_SQL($sqltmp);
			if(!$q->ok){
				categorize_tables_events("MySQL error (after $d rows)","$q->mysql_error<br>$sqltmp",$tablename);
				return;
			}
		}
		
		$UPDATED[$sitenameToScan]=true;
		
		if($c>1000){
			WriteStatus($d,$max,$tablename);
			if(system_is_overloaded(__FILE__)){categorize_tables_events("Overloaded system {$GLOBALS["SYSTEM_INTERNAL_LOAD"]} die() task..",null,$tablename);die();}
			$c=0;
		}
		
	}
		
		
	
	if($catz>0){
		$took=$unix->distanceOfTimeInWords($t,time());
		events_tail("$catz/$d/$max websites categorized for $tablename, took: $took");
		categorize_tables_events("$catz/$d websites categorized<br>took: $took",null,$tablename,1);
	}
	
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
	if($GLOBALS["VERBOSE"]){echo "$sourcefunction:: $text (in line $sourceline)\n";}
	writelogs_squid($text,$sourcefunction,__FILE__,$sourceline,"stats",true);
	
}
function WriteStatus($d,$max,$tablename,$ORDER){
	$mypid=getmypid();
	$ARRAY["PID"]=$mypid;
	$ARRAY["CURRENT"]=$d;
	$ARRAY["MAX"]=$max;
	$ARRAY["ORDER"]=$ORDER;
	@mkdir("/usr/share/artica-postfix/ressources/logs/categorize-tables",0777,true);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/categorize-tables/$tablename", serialize($ARRAY));
	@chmod("/usr/share/artica-postfix/ressources/logs/categorize-tables/$tablename",0777);
}
function repair_tables(){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$oldpid=@file_get_contents($pidfile);
	$unix=new unix();
	
	if($unix->process_exists($oldpid,basename(__FILE__))){
		events_tail("Already executed pid $oldpid");
		if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid\n";}
		return;
	}
	
	$files=$unix->DirFiles("/usr/share/artica-postfix/ressources/logs/categorize-tables");
	$php5=$unix->LOCATE_PHP5_BIN();
	
	
	while (list ($none, $tablename) = each ($files)){
			$filePath="/usr/share/artica-postfix/ressources/logs/categorize-tables/$tablename";
			if(!is_file($filePath)){
				@unlink($filePath);
				continue;
			}

			$ARRAY=unserialize(@file_get_contents($filePath));
			if(!is_array($ARRAY)){@unlink($filePath);continue;}
			$PID=$ARRAY["PID"];
			$CUR=$ARRAY["CURRENT"];
			$MAX=$ARRAY["MAX"];
			
			if($CUR==$MAX){@unlink($filePath);continue;}
			if($unix->process_exists($PID)){continue;}
			categorize_tables_events("Ask to schedule table Current:$CUR/$MAX",null,$tablename,1);
			$unix->THREAD_COMMAND_SET("$php5 ".__FILE__." --table $tablename");
			
	}

	
	
}

?>