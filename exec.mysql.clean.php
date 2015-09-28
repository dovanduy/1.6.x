<?php
if(is_file("/usr/bin/cgclassify")){if(is_dir("/cgroups/blkio/php")){shell_exec("/usr/bin/cgclassify -g cpu,cpuset,blkio:php ".getmypid());}}
$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["FLUSH"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--flush#",implode(" ",$argv))){$GLOBALS["FLUSH"]=true;}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');

	if($argv[1]=="--squid-stats"){clean_squid_stats_dbs();die();}
	if($argv[1]=="--corrupted"){repair_corrupted();die();}
	if($argv[1]=="--clean-tmd"){clean_tmd();die();}


	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".MAIN.pid";
	$pidfileTime="/etc/artica-postfix/pids/".basename(__FILE__).".MAIN.pid.time";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){system_admin_events("Already process $pid exists",__FUNCTION__,__FILE__,__LINE__,"clean");die();}
	if(system_is_overloaded()){system_admin_events("Overloaded system, aborting task",__FUNCTION__,__FILE__,__LINE__,"clean");}
	
	
	$t=time();
	system_admin_events("Starting cleaning ipband table...",__FUNCTION__,__FILE__,__LINE__,"clean");
	ipband_clean();
	system_admin_events("Starting cleaning events table...",__FUNCTION__,__FILE__,__LINE__,"clean");
	CleanEvents();
	system_admin_events("Starting cleaning maillog table...",__FUNCTION__,__FILE__,__LINE__,"clean");
	clean_maillogs();
	system_admin_events("Starting cleaning squid statistics table...",__FUNCTION__,__FILE__,__LINE__,"clean");
	clean_squid_stats_dbs();
	clean_squid_stats_no_items();
	$took=$unix->distanceOfTimeInWords($t,time(),true);
	system_admin_events("Finish, took $took",__FUNCTION__,__FILE__,__LINE__,"clean");
	

function ipband_clean(){
	$q=new mysql();
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("ipbandClean")));
	$MAX_DAY=$array["MAX_DAYS"];
	$MAX_ROWS=$array["MAX_ROWS"];
	if(!is_numeric($MAX_ROWS)){$MAX_ROWS=1000000;}
	if(!is_numeric($MAX_DAY)){$MAX_DAY=30;}
	$sql="DELETE FROM ipband WHERE zDate<DATE_SUB(NOW(), INTERVAL $MAX_DAY DAY)";
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){system_admin_events("Fatal: $q->mysql_error\n$sql", __FUNCTION__, __FILE__, __LINE__, "clean");return;}
	
	if($q->affected_rows>0){system_admin_events("$q->affected_rows deleted item in artica_events/ipband table", __FUNCTION__, __FILE__, __LINE__, "clean");}
	$rowsnumber=$q->COUNT_ROWS("ipband", "artica_events");
	if($rowsnumber>$MAX_ROWS){
		$todelete=$rowsnumber-$MAX_ROWS;
		$sql="DELETE FROM ipband ORDER BY zDate LIMIT $todelete";
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){system_admin_events("Fatal: $q->mysql_error\n$sql", __FUNCTION__, __FILE__, __LINE__, "clean");return;}
		if($q->affected_rows>0){system_admin_events("$q->affected_rows deleted item in artica_events/ipband table", __FUNCTION__, __FILE__, __LINE__, "clean");}
	}

}

function clean_tmd(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".MAIN.pid";
	$pidfileTime="/etc/artica-postfix/pids/exec.mysql.clean.php.clean_tmd.time";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){system_admin_events("Already process $pid exists",__FUNCTION__,__FILE__,__LINE__,"clean");die();}
	
	$timeExec=$unix->file_time_min($pidfileTime);
	if($timeExec<240){return;}
	
	@unlink($pidfileTime);
	@file_put_contents($pidfileTime, time());
	@file_put_contents($pidfile, getmypid());
	
	
	$SIZES=0;
	$Dirs=$unix->dirdir("/var/lib/mysql");
	while (list ($directory, $none) = each ($Dirs) ){
		
		$Files=$unix->DirFiles($directory,"\.[0-9]+\.TMD$");
		while (list ($filename, $none) = each ($Files) ){
			$fullpath="$directory/$filename";
			if($unix->file_time_min($fullpath)<240){continue;}
			$SIZES=$SIZES+@filesize($fullpath);
			@unlink($fullpath);
				
		}
		
		$Files=$unix->DirFiles($directory,"\.TMD-[0-9]+$");
		while (list ($filename, $none) = each ($Files) ){
			$fullpath="$directory/$filename";
			if($unix->file_time_min($fullpath)<240){continue;}
			$SIZES=$SIZES+@filesize($fullpath);
			@unlink($fullpath);
		
		}		

		
	}
	
	if(is_dir("/opt/squidsql/data")){
		
		$Dirs=$unix->dirdir("/opt/squidsql/data");
		while (list ($directory, $none) = each ($Dirs) ){
		
			$Files=$unix->DirFiles($directory,"\.[0-9]+\.TMD$");
			while (list ($filename, $none) = each ($Files) ){
				$fullpath="$directory/$filename";
				if($unix->file_time_min($fullpath)<240){continue;}
				$SIZES=$SIZES+@filesize($fullpath);
				@unlink($fullpath);
		
			}
		
			$Files=$unix->DirFiles($directory,"\.TMD-[0-9]+$");
			while (list ($filename, $none) = each ($Files) ){
				$fullpath="$directory/$filename";
				if($unix->file_time_min($fullpath)<240){continue;}
				$SIZES=$SIZES+@filesize($fullpath);
				@unlink($fullpath);
		
			}
		
		
		}		
		
		
	}
	
	
	
}

function clean_maillogs(){
	
	$t1=time();
	$datte_start=date('Y-m-d H:i:s');
	$unix=new unix();
	$q=new mysql();
	$sock=new sockets();
	
	$num=$q->COUNT_ROWS("smtp_logs","artica_events");
	if($GLOBALS["VERBOSE"]){echo "smtp_logs: Storing $num rows\n";}
	$MaxMailEventsLogs=$sock->GET_INFO("MaxMailEventsLogs");
	if($MaxMailEventsLogs==null){$MaxMailEventsLogs=400000;}
	if($MaxMailEventsLogs<100){$MaxMailEventsLogs=4000;}
	if($GLOBALS["VERBOSE"]){echo "MaxMailEventsLogs:$MaxMailEventsLogs max rows\n";}
	if($num>$MaxMailEventsLogs){
		$todelete=$num-$MaxMailEventsLogs;
		if($GLOBALS["VERBOSE"]){echo "smtp_logs: deleting :$todelete rows\n";}
		$sql="DELETE FROM smtp_logs ORDER BY time_connect LIMIT $todelete";
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){system_admin_events("Fatal: $q->mysql_error\n$sql", __FUNCTION__, __FILE__, __LINE__, "clean");return;}
		if($q->affected_rows>0){system_admin_events("$q->affected_rows deleted item in artica_events/smtp_logs table", __FUNCTION__, __FILE__, __LINE__, "clean");}
	}
	
	
	$num1=$q->COUNT_ROWS("mails_stats","artica_events");
	if($GLOBALS["VERBOSE"]){echo "mails_stats: Storing $num rows\n";}
	
	if($num1>$MaxMailEventsLogs){
		$todelete1=$num1-$MaxMailEventsLogs;
		if($GLOBALS["VERBOSE"]){echo "mails_stats: deleting :$todelete1 rows  \n";}
		$sql="DELETE FROM mails_stats ORDER BY zDate LIMIT $todelete1";
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){system_admin_events("Fatal: $q->mysql_error\n$sql", __FUNCTION__, __FILE__, __LINE__, "clean");return;}
		if($q->affected_rows>0){system_admin_events("$q->affected_rows deleted item in artica_events/mails_stats table", __FUNCTION__, __FILE__, __LINE__, "clean");}
	}
	
	$ini=new Bs_IniHandler();
	$ini->loadString($sock->GET_INFO("RTMMailConfig"));
	if($ini->_params["ENGINE"]["LOG_DAY_LIMIT"]==null){$ini->_params["ENGINE"]["LOG_DAY_LIMIT"]="20";}	
	$today=date('Y-m-d');
	$sql="DELETE FROM smtp_logs WHERE time_stamp < DATE_SUB( NOW(), INTERVAL -{$ini->_params["ENGINE"]["LOG_DAY_LIMIT"]} DAY )";
	$q=new mysql();
	$q->QUERY_SQL($sql,'artica_events');	
	if(!$q->ok){system_admin_events("Fatal: $q->mysql_error\n$sql", __FUNCTION__, __FILE__, __LINE__, "clean");return;}
	if($q->affected_rows>0){system_admin_events("$q->affected_rows deleted item in artica_events/mails_stats table", __FUNCTION__, __FILE__, __LINE__, "clean");}	
	
	
}


function CleanEvents(){
	$sock=new sockets();
	$q=new mysql();
	$SnortMaxMysqlEvents=$sock->GET_INFO("SnortMaxMysqlEvents");
	if(!is_numeric($SnortMaxMysqlEvents)){$SnortMaxMysqlEvents=700000;}
	
	$num=$q->COUNT_ROWS("system_admin_events","artica_events");
	if($num>4000){$q->QUERY_SQL("DELETE FROM system_admin_events ORDER BY zDate LIMIT 4000","artica_events");}
	if($q->affected_rows>0){system_admin_events("$q->affected_rows deleted item in artica_events/system_admin_events table", __FUNCTION__, __FILE__, __LINE__, "clean");}

	$num=$q->COUNT_ROWS("ufdbguard_admin_events","artica_events");
	if($num>4000){$q->QUERY_SQL("DELETE FROM ufdbguard_admin_events ORDER BY zDate LIMIT 4000","artica_events");}
	if($q->affected_rows>0){system_admin_events("$q->affected_rows deleted item in artica_events/ufdbguard_admin_events table", __FUNCTION__, __FILE__, __LINE__, "clean");}
	
	$num=$q->COUNT_ROWS("mysql_events","artica_events");
	if($num>4000){$q->QUERY_SQL("DELETE FROM mysql_events ORDER BY zDate LIMIT 4000","artica_events");}
	if($q->affected_rows>0){system_admin_events("$q->affected_rows deleted item in artica_events/mysql_events table", __FUNCTION__, __FILE__, __LINE__, "clean");}

	$num=$q->COUNT_ROWS("update_events","artica_events");
	if($num>4000){$q->QUERY_SQL("DELETE FROM update_events ORDER BY zDate LIMIT 4000","artica_events");}
	if($q->affected_rows>0){system_admin_events("$q->affected_rows deleted item in artica_events/update_events table", __FUNCTION__, __FILE__, __LINE__, "clean");}

	$num=$q->COUNT_ROWS("dhcpd_logs","artica_events");
	if($num>400000){$q->QUERY_SQL("DELETE FROM dhcpd_logs ORDER BY zDate LIMIT 400000","artica_events");}
	if($q->affected_rows>0){system_admin_events("$q->affected_rows deleted item in artica_events/dhcpd_logs table", __FUNCTION__, __FILE__, __LINE__, "clean");}
	
	$num=$q->COUNT_ROWS("crossroads_events","artica_events");
	if($num>4000){$q->QUERY_SQL("DELETE FROM crossroads_events ORDER BY zDate LIMIT 4000","artica_events");}	
	if($q->affected_rows>0){system_admin_events("$q->affected_rows deleted item in artica_events/crossroads_events table", __FUNCTION__, __FILE__, __LINE__, "clean");}
	
	$num=$q->COUNT_ROWS("events","artica_events");
	if($num>4000){$q->QUERY_SQL("DELETE FROM events ORDER BY zDate LIMIT 4000","artica_events");}
	if($q->affected_rows>0){system_admin_events("$q->affected_rows deleted item in artica_events/events table", __FUNCTION__, __FILE__, __LINE__, "clean");}
			
	
	$sql="DELETE FROM clamd_mem WHERE zDate<DATE_SUB(NOW(),INTERVAL 7 DAY) ORDER BY zDate LIMIT 4000";
	$q->QUERY_SQL($sql,"artica_events");	
	if($q->affected_rows>0){system_admin_events("$q->affected_rows deleted item in artica_events/clamd_mem table", __FUNCTION__, __FILE__, __LINE__, "clean");}	
	
	$num=$q->COUNT_ROWS("snort","artica_events");
	if($num>$SnortMaxMysqlEvents){
		$limit=$tablecount-$SnortMaxMysqlEvents;
		$q->QUERY_SQL("DELETE FROM snort ORDER BY zDate LIMIT $limit","artica_events");
		if(!$q->ok){system_admin_events("Fatal: $q->mysql_error\n$sql", __FUNCTION__, __FILE__, __LINE__, "clean");return;}
		if($q->affected_rows>0){system_admin_events("$q->affected_rows deleted item in artica_events/snort table", __FUNCTION__, __FILE__, __LINE__, "clean");}			
	}

}

function clean_squid_stats_no_items(){
	
	$sock=new sockets();
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	$CleanArticaSquidDatabases=$sock->GET_INFO("CleanArticaSquidDatabases");
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	if(!is_numeric($CleanArticaSquidDatabases)){$CleanArticaSquidDatabases=0;}	
	if($CleanArticaSquidDatabases==1){return;}
	$q=new mysql_squid_builder();
	$tables=$q->LIST_TABLES_DAYS();
	$rows=0;
	$count_tables=0;
	if(!$q->ok){return;}
	while (list ($num, $table) = each ($tables) ){
		if(!$q->ok){return;}
	
		$rows=$q->COUNT_ROWS($table);
		if($rows==0){
			if($GLOBALS["VERBOSE"]){echo " Delete table $table $rows rows \n";}
			$count_tables++;
			ufdbguard_admin_events("$table was deleted (contains no row)", __FUNCTION__, __FILE__, __LINE__, "clean-stats");
			$q->DELETE_TABLE($table);
		}
		
	}
	
	
	
	$tables=$q->LIST_TABLES_DAYS_BLOCKED();
	
	if(!$q->ok){return;}
	while (list ($num, $table) = each ($tables) ){
		$rows=$q->COUNT_ROWS($table);
		if(!$q->ok){return;}
		if($rows==0){
			if($GLOBALS["VERBOSE"]){echo " Delete table $table $rows rows \n";}
			$count_tables++;
			ufdbguard_admin_events("$table was deleted (contains no row)", __FUNCTION__, __FILE__, __LINE__, "clean-stats");
			$q->DELETE_TABLE($table);
		}
	}	
	
	
	
	$tables=$q->LIST_TABLES_MEMBERS();
	
	if(!$q->ok){return;}
	while (list ($num, $table) = each ($tables) ){
		$rows=$q->COUNT_ROWS($table);
		if(!$q->ok){return;}
		if($rows==0){
			if($GLOBALS["VERBOSE"]){echo " Delete table $table $rows rows \n";}
			$count_tables++;
			ufdbguard_admin_events("$table was deleted (contains no row)", __FUNCTION__, __FILE__, __LINE__, "clean-stats");
			$q->DELETE_TABLE($table);
		}
	}

	$tables=$q->LIST_TABLES_MONTH();
	
	if(!$q->ok){return;}
	while (list ($num, $table) = each ($tables) ){
		$rows=$q->COUNT_ROWS($table);
		if(!$q->ok){return;}
		if($rows==0){
			if($GLOBALS["VERBOSE"]){echo " Delete table $table $rows rows \n";}
			$count_tables++;
			ufdbguard_admin_events("$table was deleted (contains no row)", __FUNCTION__, __FILE__, __LINE__, "clean-stats");
			$q->DELETE_TABLE($table);
		}
		
	}

	$tables=$q->LIST_TABLES_WEEKS();
	
	if(!$q->ok){return;}
	while (list ($num, $table) = each ($tables) ){
		$rows=$q->COUNT_ROWS($table);	if(!$q->ok){return;}
		if($rows==0){
			if($GLOBALS["VERBOSE"]){echo " Delete table $table $rows rows \n";}
			$count_tables++;
			ufdbguard_admin_events("$table was deleted (contains no row)", __FUNCTION__, __FILE__, __LINE__, "clean-stats");
			$q->DELETE_TABLE($table);
		}
		
	}	
	
	
	


	


	

	if($count_tables>0){system_admin_events("$count_tables empy tables as been deleted in squid statistics database", __FUNCTION__, __FILE__, __LINE__, "clean");}
	
}


function repair_corrupted(){
	$q=new mysql();
	$unix=new unix();
	
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".md5(__FUNCTION__).".pid";
	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		return;
	}
	
	$myisamchk=$unix->find_program("myisamchk");
	$pgrep=$unix->find_program("pgrep");
	exec("$pgrep -l -f \"$myisamchk\"",$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#pgrep#", $line)){continue;}
		if(preg_match("#^[0-9]+\s+#", $line)){
			writelogs("$line already executed",@implode("\r\n", $results),__FUNCTION__,__FILE__,__LINE__);
			return;
		}
	}	

	if(!$GLOBALS["FORCE"]){
		if(!$GLOBALS['VERBOSE']){
			$timefile="/etc/artica-postfix/pids/MySQLRepairDBTime.time";
			$timex=$unix->file_time_min($timefile);
			if($timex<240){return;}
			@unlink($timefile);
			@file_put_contents($timefile, time());
		}
	}
	
	$databases=$q->DATABASE_LIST_SIMPLE();
	while (list ($database, $comment) = each ($databases) ){
		$tables=$q->TABLES_STATUS_CORRUPTED($database);
		if($GLOBALS["VERBOSE"]){echo "Checking database $database `$comment` Store ".count($tables)." suspicious tables\n";}
		if(count($tables)>0){
			while (list ($table, $why) = each ($tables) ){
				if($GLOBALS["VERBOSE"]){echo "Table `$table` is on status: `$why`\n";}
				repair_action($database,$table,$why);
				
			}
		}else{
			if($GLOBALS["VERBOSE"]){echo "Database $database is CLEAN !\n";}
		}
	
	}

	
}

function repair_action($database,$tablename,$expl){
	$unix=new unix();
	$q=new mysql();
	
	if(preg_match("#Can.*?t find file#", $expl)){
		system_admin_events("$tablename is destroyed, remove it..",__FUNCTION__,__FILE__,__LINE__);
		echo "Removing table $database/$tablename\n";
		$q->DELETE_TABLE($tablename, $database);
		return;
	}
	
	
	if(preg_match("#is marked as crashed#", $expl)){
		$results=array();
		$t=time();
		if(is_file("/var/lib/mysql/$database/$tablename.TMD")){
			@copy("/var/lib/mysql/$database/$tablename.TMD", "/var/lib/mysql/$database/$tablename.TMD-".time());
			@unlink("/var/lib/mysql/$database/$tablename.TMD");
		}
			
		$myisamchk=$unix->find_program("myisamchk");
		$cmd="$myisamchk -r /var/lib/mysql/$database/$tablename.MYI";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		exec($cmd,$results);
		$took=$unix->distanceOfTimeInWords($t,time());
		system_admin_events("$tablename repaired took: $took",@implode("\r\n", $results),__FUNCTION__,__FILE__,__LINE__);
		return;
	}	
	
	if($GLOBALS["VERBOSE"]){echo "$tablename nothing to do...\n";}
	
}




function clean_squid_stats_dbs(){
	$sock=new sockets();
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	$CleanArticaSquidDatabases=$sock->GET_INFO("CleanArticaSquidDatabases");
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	if(!is_numeric($CleanArticaSquidDatabases)){$CleanArticaSquidDatabases=0;}
	if(!$GLOBALS["FORCE"]){
	if($CleanArticaSquidDatabases==0){
		echo "Option is not activated...\n";
		return;}
	}
	$q=new mysql_squid_builder();
	$tables=$q->LIST_TABLES_DAYS();
	$rows=0;
	$count_tables=0;
	while (list ($num, $table) = each ($tables) ){
		$rows=$rows+$q->COUNT_ROWS($table);
		if($GLOBALS["VERBOSE"]){echo " Delete table $table $rows rows \n";}
		$count_tables++;
		$q->DELETE_TABLE($table);
		
	}
	
	
	
	$tables=$q->LIST_TABLES_DAYS_BLOCKED();
	
	while (list ($num, $table) = each ($tables) ){
		$rows=$rows+$q->COUNT_ROWS($table);
		if($GLOBALS["VERBOSE"]){echo " Delete table $table $rows rows \n";}
		$count_tables++;
		$q->DELETE_TABLE($table);
		
	}	
	

	
	$tables=$q->LIST_TABLES_MEMBERS();
	
	while (list ($num, $table) = each ($tables) ){
		$rows=$rows+$q->COUNT_ROWS($table);
		if($GLOBALS["VERBOSE"]){echo " Delete table $table $rows rows \n";}
		$count_tables++;
		$q->DELETE_TABLE($table);
		
	}

	$tables=$q->LIST_TABLES_MONTH();
	
	while (list ($num, $table) = each ($tables) ){
		$rows=$rows+$q->COUNT_ROWS($table);
		if($GLOBALS["VERBOSE"]){echo " Delete table $table $rows rows \n";}
		$count_tables++;
		$q->DELETE_TABLE($table);
		
	}

	$tables=$q->LIST_TABLES_WEEKS();
	
	while (list ($num, $table) = each ($tables) ){
		$rows=$rows+$q->COUNT_ROWS($table);
		if($GLOBALS["VERBOSE"]){echo " Delete table $table $rows rows \n";}
		$count_tables++;
		$q->DELETE_TABLE($table);
		
	}	
	

	
	




	

	
	
	$q=new mysql_catz();
	$tables=$q->LIST_TABLES_CATEGORIES();
	while (list ($num, $table) = each ($tables) ){
		$rows=$rows+$q->COUNT_ROWS($table);
		if($GLOBALS["VERBOSE"]){echo " Delete table $table $rows rows \n";}
		$count_tables++;
		$q->DELETE_TABLE($table);
		
	}

	$q=new mysql();
	if($q->DATABASE_EXISTS("catz")){
		$q->DELETE_DATABASE("catz");
	}
	
	if($count_tables>0){
		mysql_admin_mysql(1,"Restarting MySQL service...", null,__FILE__,__LINE__);
		shell_exec("/etc/init.d/mysql restart");
	}
   	$sock->TOP_NOTIFY("$count_tables statistics tables as been deleted with $rows rows","info");
	
	
	//print_r($tables);
	
}