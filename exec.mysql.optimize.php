<?php

include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql-server.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");


if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$unix=new unix();



if($argv[1]=="--cron"){set_cron();die();}
if($argv[1]=="--optimize"){optimize();die();}
if($argv[1]=="--repair"){repair_all();die();}



function set_cron(){
		$targetfile="/etc/cron.d/MysqlOptimize";
		@unlink($targetfile);
		$sock=new sockets();
		$unix=new unix();
		$EnableMysqlOptimize=$sock->GET_INFO("EnableMysqlOptimize");
		if(!is_numeric($EnableMysqlOptimize)){$EnableMysqlOptimize=0;}
		if($GLOBALS["VERBOSE"]){echo "EnableMysqlOptimize = $EnableMysqlOptimize\n";}
		if($EnableMysqlOptimize==0){return;}
		$MysqlOptimizeSchedule=$sock->GET_INFO("MysqlOptimizeSchedule");
		if($GLOBALS["VERBOSE"]){echo "MysqlOptimizeSchedule = $MysqlOptimizeSchedule\n";}
		$php5=$unix->LOCATE_PHP5_BIN();
 		
 		$f[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin:/usr/share/artica-postfix/bin";
		$f[]="MAILTO=\"\"";
		$f[]="$MysqlOptimizeSchedule  root $php5 ".__FILE__." --optimize >/dev/null 2>&1";
		$f[]="";	
		if($GLOBALS["VERBOSE"]){echo " -> $targetfile\n";}
		@file_put_contents($targetfile,implode("\n",$f));
		if(!is_file($targetfile)){if($GLOBALS["VERBOSE"]){echo " -> $targetfile No such file\n";}}
		
		$chmod=$unix->find_program("chmod");
		shell_exec("$chmod 640 $targetfile");
		unset($f);	
}

function optimize($aspid=false){
		$sock=new sockets();
		$unix=new unix();
		$q=new mysql();
		$basename=basename(__FILE__);
		$unix=new unix();
		if(!$aspid){
			$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".MAIN.pid";
			$pid=@file_get_contents($pidfile);
			if($unix->process_exists($pid,$basename)){mysql_admin_events("Already running pid $pid, aborting",__FUNCTION__,__FILE__,__LINE__);return;}	
			$t=0;
			
			
			$EnableMysqlOptimize=$sock->GET_INFO("EnableMysqlOptimize");
			if(!is_numeric($EnableMysqlOptimize)){$EnableMysqlOptimize=0;}
			if($GLOBALS["VERBOSE"]){echo "EnableMysqlOptimize= $EnableMysqlOptimize \n";}
			if($EnableMysqlOptimize==0){return;}			
			
		}			
		


		$t1=time();
		$ARRAY=unserialize(base64_decode($sock->GET_INFO("MysqlOptimizeDBS")));
		if($GLOBALS["VERBOSE"]){echo "MysqlOptimizeDBS= ".count($ARRAY)." \n";}
		$ARRAY["artica_backup"]=1;
		$ARRAY["artica_events"]=1;
		$ARRAY["squidlogs"]=1;
		
		mysql_admin_events("Starting optimize ". count($ARRAY)." databases ",__FUNCTION__,__FILE__,__LINE__,"defrag");
		$c=0;
		
		while (list ($database, $enabled) = each ($ARRAY) ){
			if(!is_numeric($enabled)){continue;}
			if($database=="zarafa"){continue;}
			
			if($enabled==1){
				$c++;
				optimize_tables($database);
				if(system_is_overloaded()){ mysql_admin_events("Overloaded system, aborting task",__FUNCTION__,__FILE__,__LINE__); return; }
				
			}
			
		}
	
		$time=$unix->distanceOfTimeInWords($t1,time(),true);
		mysql_admin_events("$c Database(s) checked $time",__FUNCTION__,__FILE__,__LINE__,"defrag");	
}

function optimize_tables($database){
	$q=new mysql();
	$unix=new unix();
	mysql_admin_events("Starting optimize tables in database $database",__FUNCTION__,__FILE__,__LINE__,"defrag");
	$sql="SHOW TABLE STATUS FROM `$database`";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,$database);
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$TableName=$ligne["Name"];
		$Data_free=$ligne["Data_free"];
		if(!is_numeric($Data_free)){continue;}
		if($Data_free==0){continue;}
		$t1=time();
		$Data_free=FormatBytes($Data_free/1024);
		$Data_free=str_replace("&nbsp;", " ", $Data_free);
		mysql_admin_events("Table $database/`$TableName` need to be optimized ($Data_free Free)",__FUNCTION__,__FILE__,__LINE__,"defrag");	
		$q->QUERY_SQL("OPTIMIZE TABLE `$TableName`",$database);
		if(!$q->ok){$unix->mysql_admin_events("$database/$TableName Error $q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"defrag");	continue;}
		$time=$unix->distanceOfTimeInWords($t1,time(),true);
		mysql_admin_events("Table $database/`$TableName` optimized $time",__FUNCTION__,__FILE__,__LINE__,"defrag");
	}
}

function repair_all(){
	
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	if($GLOBALS["VERBOSE"]){echo "repair_all Loading done...\nTimeFile:$timefile\n";}
	$unix=new unix();
	
	if(system_is_overloaded()){
		mysql_admin_events("Overloaded system, aborting task",__FILE__,__LINE__);
		return;
	}
	
	
	$pid=@file_get_contents($pidfile);
	if(!$GLOBALS["FORCE"]){
		if($pid<100){$pid=null;}
	
		if($unix->process_exists($pid,basename(__FILE__))){
			if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}
			return;
		}
	
		$timeexec=$unix->file_time_min($timefile);
		if($timeexec<120){
			if($GLOBALS["VERBOSE"]){echo "{$timeexec} <> 120...\n";}
			return;
		}
	}
	
	$q=new mysql();
	
	
	$myisamchk=$unix->find_program("myisamchk");
	$sql="SHOW TABLE STATUS FROM `artica_backup`";
	$q=new mysql();
	
	$MYSQL_DATA_DIR=$unix->MYSQL_DATA_DIR();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$TableName=$ligne["Name"];
		$ligne2=mysql_fetch_array($q->QUERY_SQL("ANALYZE TABLE $TableName","artica_backup"));
		$comment=$ligne["Comment"];
		echo "$TableName: {$ligne2["Msg_type"]} - {$ligne2["Msg_text"]} ($comment)\n$myisamchk --safe-recover $MYSQL_DATA_DIR/artica_backup/$TableName.MYI\n";
		if($TableName=="squid_caches_center"){$ligne2["Msg_type"]="error";}
		
		if($ligne2["Msg_type"]=="error"){
			if(is_file("$MYSQL_DATA_DIR/artica_backup/$TableName.MYI")){
				mysql_admin_events("Repair: $MYSQL_DATA_DIR/artica_backup/$TableName.MYI",__FUNCTION__,__FILE__,__LINE__);
				shell_exec("$myisamchk --safe-recover $MYSQL_DATA_DIR/artica_backup/$TableName.MYI");
			}
			continue;
		}
		
		echo "$TableName -> $comment\n";
		if(trim($comment)==null){continue;}
		
	}
	
	$sql="SHOW TABLE STATUS FROM `artica_events`";
	$results=$q->QUERY_SQL($sql,"artica_events");
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$TableName=$ligne["Name"];
		$comment=$ligne["Comment"];
		$ligne2=mysql_fetch_array($q->QUERY_SQL("ANALYZE TABLE $TableName","artica_events"));
		$comment=$ligne["Comment"];
		echo "$TableName: {$ligne2["Msg_type"]} - {$ligne2["Msg_text"]} ($comment)\n";
		
		if($ligne2["Msg_type"]=="error"){
			if(is_file("$MYSQL_DATA_DIR/artica_events/$TableName.MYI")){
				mysql_admin_events("Repair: $MYSQL_DATA_DIR/artica_events/$TableName.MYI",__FUNCTION__,__FILE__,__LINE__);
				shell_exec("$myisamchk --safe-recover $MYSQL_DATA_DIR/artica_events/$TableName.MYI");
			}
			continue;
		}
		
		if(trim($comment)==null){continue;}
		
		if(preg_match("#Incorrect key file for table#", $comment)){
			if(is_file("$MYSQL_DATA_DIR/artica_events/$TableName.MYI")){
				mysql_admin_events("Repair: $MYSQL_DATA_DIR/artica_events/$TableName.MYI",__FUNCTION__,__FILE__,__LINE__);
				shell_exec("$myisamchk --safe-recover $MYSQL_DATA_DIR/artica_events/$TableName.MYI");
			}
			
		}
		
		
	}

	$sock=new sockets();
	$WORKDIR=$sock->GET_INFO("SquidStatsDatabasePath");
	if($WORKDIR==null){$WORKDIR="/opt/squidsql";}	
	if(is_dir($WORKDIR)){
		$q=new mysql_squid_builder();
		$sql="SHOW TABLE STATUS FROM `squidlogs`";
		$results=$q->QUERY_SQL($sql);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$TableName=trim($ligne["Name"]);
			if($TableName==null){continue;}
			$comment=$ligne["Comment"];
			$ligne2=mysql_fetch_array($q->QUERY_SQL("ANALYZE TABLE $TableName","squidlogs"));
			
			if($ligne2["Msg_type"]=="error"){
				if(is_file("$MYSQL_DATA_DIR/squidsql/$TableName.MYI")){
					mysql_admin_events("Repair: $MYSQL_DATA_DIR/squidsql/$TableName.MYI",__FUNCTION__,__FILE__,__LINE__);
					shell_exec("$myisamchk --safe-recover $MYSQL_DATA_DIR/squidsql/$TableName.MYI");
				}
				
			}
		
		
		
			if(preg_match("#Incorrect key file for table#", $comment)){
				if(is_file("$MYSQL_DATA_DIR/squidsql/$TableName.MYI")){
					mysql_admin_events("Repair: $MYSQL_DATA_DIR/squidsql/$TableName.MYI",__FUNCTION__,__FILE__,__LINE__);
					shell_exec("$myisamchk --safe-recover $MYSQL_DATA_DIR/squidsql/$TableName.MYI");
				}
			}
		}
		
		
	}else{
		echo "$WORKDIR no such dir\n";
	}
	
	optimize(true);
	
}



