<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.mysql.dump.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
cpulimit();
$_GET["LOGFILE"]="/var/log/artica-postfix/dansguardian-logger.debug";
$GLOBALS["MAXDAYS"]=0;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#maxdays=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["MAXDAYS"]=$re[1];}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}

purge();

function purge(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=@file_get_contents($pidfile);
	if($oldpid<100){$oldpid=null;}
	
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$timepid=$unix->PROCCESS_TIME_MIN($oldpid);
		ufdbguard_admin_events("Already executed pid $oldpid since {$timepid}",__FUNCTION__,__FILE__,__LINE__,"reports");
		if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	$sock=new sockets();
	$users=new usersMenus();
	$LICENSE=0;
	$mysqldump=$unix->find_program("mysqldump");
	$tar=$unix->find_program("tar");
	$EnableSquidRemoteMySQL=$sock->GET_INFO("EnableSquidRemoteMySQL");
	if(!is_numeric($EnableSquidRemoteMySQL)){$EnableSquidRemoteMySQL=0;}
	if($EnableSquidRemoteMySQL==1){return ;}
	
	if(!is_file($mysqldump)){
		echo "mysqldump, no such binary\n";
		ufdbguard_admin_events("mysqldump, no such binary",__FUNCTION__,__FILE__,__LINE__,"backup");
		return;
	}
	
	if(!is_file($tar)){
		echo "tar, no such binary\n";
		ufdbguard_admin_events("tar, no such binary",__FUNCTION__,__FILE__,__LINE__,"backup");
		return;
	}	
	
	$flic=@file_get_contents(base64_decode("L3Vzci9sb2NhbC9zaGFyZS9hcnRpY2EvLmxpYw=="));
	if(preg_match("#TRUE#is", $flic)){$LICENSE=1;}
	$ArticaProxyStatisticsBackupFolder=$sock->GET_INFO("ArticaProxyStatisticsBackupFolder");
	$ArticaProxyStatisticsBackupDays=$sock->GET_INFO("ArticaProxyStatisticsBackupDays");
	if($ArticaProxyStatisticsBackupFolder==null){$ArticaProxyStatisticsBackupFolder="/home/artica/squid/backup-statistics";}
	
	if(!is_numeric($ArticaProxyStatisticsBackupDays)){$ArticaProxyStatisticsBackupDays=90;}
	if($GLOBALS["MAXDAYS"]>0){$ArticaProxyStatisticsBackupDays=$GLOBALS["MAXDAYS"];}
	if($LICENSE==0){$ArticaProxyStatisticsBackupDays=5;}
	if(!ScanDays()){if($GLOBALS["VERBOSE"]){echo "Failed...\n";}return;}
	
	if($GLOBALS["VERBOSE"]){"Max Day: $ArticaProxyStatisticsBackupDays; folder:$ArticaProxyStatisticsBackupFolder\n";}
	$q=new mysql_squid_builder(true);
	
	$sql="SELECT tablename,zDate FROM tables_day WHERE zDate<DATE_SUB(NOW(),INTERVAL $ArticaProxyStatisticsBackupDays DAY)";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		ufdbguard_admin_events("Fatal Error: $this->mysql_error",__FUNCTION__,__FILE__,__LINE__,"backup");
		return;
	}
	
	ufdbguard_admin_events("Items: ".mysql_num_rows($results),__FUNCTION__,__FILE__,__LINE__,"backup");
	if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
	
	
	@mkdir("$ArticaProxyStatisticsBackupFolder",0755,true);
	if(!is_dir($ArticaProxyStatisticsBackupFolder)){
		if($GLOBALS["VERBOSE"]){echo "$ArticaProxyStatisticsBackupFolder permission denied\n";}
		ufdbguard_admin_events("Fatal Error: $ArticaProxyStatisticsBackupFolder permission denied",__FUNCTION__,__FILE__,__LINE__,"backup");
		return;
	}
	
	$t=time();
	if(!@file_put_contents("$ArticaProxyStatisticsBackupFolder/$t", time())){
		if($GLOBALS["VERBOSE"]){echo "$ArticaProxyStatisticsBackupFolder write error\n";}
		ufdbguard_admin_events("Fatal Error: $ArticaProxyStatisticsBackupFolder write error..",__FUNCTION__,__FILE__,__LINE__,"backup");
		return;		
	}
	
	if(!is_file("$ArticaProxyStatisticsBackupFolder/$t")){
		if($GLOBALS["VERBOSE"]){echo "$ArticaProxyStatisticsBackupFolder permission denied\n";}
		ufdbguard_admin_events("Fatal Error: $ArticaProxyStatisticsBackupFolder permission denied",__FUNCTION__,__FILE__,__LINE__,"backup");
		return;		
	}
	
	@unlink("$ArticaProxyStatisticsBackupFolder/$t");
	$DeleteTables=0;
	$TotalSize=0;
	
	
	$mysqldump_prefix="$mysqldump $q->MYSQL_CMDLINES --skip-add-locks --insert-ignore --quote-names --skip-add-drop-table --verbose --force $q->database ";
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$tablename=$ligne["tablename"];
		$TableKey=$tablename;
		$day=$ligne["zDate"];
		$DayTime=strtotime("$day 00:00:00");
		echo "To backup $tablename ($day)\n";
		
		$container="$ArticaProxyStatisticsBackupFolder/squidlogs.$day.".time().".sql";
		if(is_file($container)){sleep(1);}
		$container="$ArticaProxyStatisticsBackupFolder/squidlogs.$day.".time().".sql";
		
		if(!@file_put_contents($container, time())){
			if($GLOBALS["VERBOSE"]){echo "$container permission denied\n";}
			ufdbguard_admin_events("Fatal Error: $container permission denied",__FUNCTION__,__FILE__,__LINE__,"backup");
			return;			
		}
		
		@unlink($container);
		
		$tablesB=array();
		
		if($q->TABLE_EXISTS($tablename)){$tablesB[$tablename]=true;}else{if($GLOBALS["VERBOSE"]){echo "$tablename no such table, continue\n";}}
		$tableTMP=date("Ymd",$DayTime)."_hour";
		if($q->TABLE_EXISTS($tableTMP)){$tablesB[$tableTMP]=true;}else{if($GLOBALS["VERBOSE"]){echo "$tableTMP no such table, continue\n";}}
		
		$tableTMP=date("Ymd",$DayTime)."_members";
		if($q->TABLE_EXISTS($tableTMP)){$tablesB[$tableTMP]=true;}else{if($GLOBALS["VERBOSE"]){echo "$tableTMP no such table, continue\n";}}

		$tableTMP=date("Ymd",$DayTime)."_visited";
		if($q->TABLE_EXISTS($tableTMP)){$tablesB[$tableTMP]=true;}else{if($GLOBALS["VERBOSE"]){echo "$tableTMP no such table, continue\n";}}

		$tableTMP=date("Ymd",$DayTime)."_blocked";
		if($q->TABLE_EXISTS($tableTMP)){$tablesB[$tableTMP]=true;}else{if($GLOBALS["VERBOSE"]){echo "$tableTMP no such table, continue\n";}}		
		
		$tableTMP="searchwordsD_".date("Ymd",$DayTime)."";
		if($q->TABLE_EXISTS($tableTMP)){$tablesB[$tableTMP]=true;}else{if($GLOBALS["VERBOSE"]){echo "$tableTMP no such table, continue\n";}}		

		$tableTMP="UserSizeD_".date("Ymd",$DayTime)."";
		if($q->TABLE_EXISTS($tableTMP)){$tablesB[$tableTMP]=true;}else{if($GLOBALS["VERBOSE"]){echo "$tableTMP no such table, continue\n";}}				
		
		$tableTMP="youtubeday_".date("Ymd",$DayTime)."";
		if($q->TABLE_EXISTS($tableTMP)){$tablesB[$tableTMP]=true;}else{if($GLOBALS["VERBOSE"]){echo "$tableTMP no such table, continue\n";}}	
		
		
		$c=array();
		while (list ($a, $b) = each ($tablesB)){$c[]=$a;}
		reset($tablesB);
			
		echo "Backup tables: ".@implode(", ", $c)."\n";
		
		
		
		if(count($tablesB)>0){
			
			
			$cmdline="$mysqldump_prefix".@implode(" ", $c)." >$container";
			if($GLOBALS["VERBOSE"]){echo "\n*******\n$cmdline\n*******\n";}
			$resultsZ=array();
			exec($cmdline,$resultsZ);
			
			while (list ($a, $b) = each ($resultsZ)){
				if(preg_match("#Got error:#i", $b)){
					if($GLOBALS["VERBOSE"]){echo "Dump failed $day $b,...\n";}
					ufdbguard_admin_events("Fatal Error: day: Dump failed $day $b",__FUNCTION__,__FILE__,__LINE__,"backup");
					@unlink($container);
					return;					
					
				}
			}
			
			
			
			if(!is_file($container)){
				if($GLOBALS["VERBOSE"]){echo "Dump failed $day $container, no such file ...\n";}
				ufdbguard_admin_events("Fatal Error: day: Dump failed $day $container, no such file",__FUNCTION__,__FILE__,__LINE__,"backup");
				return;
									
			}
			
			$size=@filesize($container);
			
			if($size<100){
				if($GLOBALS["VERBOSE"]){echo "Dump failed $day size too low ( $size bytes) ...\n";}
				ufdbguard_admin_events("Fatal Error: day: Dump failed $day size too low ( $size bytes) ... ",__FUNCTION__,__FILE__,__LINE__,"backup");
				@unlink($container);
				return;			
			}
			chdir($ArticaProxyStatisticsBackupFolder);
			
			$cmdline="$tar cfz $container.tar.gz $container 2>&1";
			$resultsZ=array();
			exec($cmdline,$resultsZ);
			while (list ($a, $b) = each ($resultsZ)){
				echo "Compress: `$b`\n";
			}
			
			if(!$unix->TARGZ_TEST_CONTAINER("$container.tar.gz")){
				ufdbguard_admin_events("Fatal Error: tar $container failed",__FUNCTION__,__FILE__,__LINE__,"backup");
				@unlink($container);
				@unlink("$container.tar.gz");
				return;
			}			
			
			
			$TotalSize=$TotalSize+$size;
			@unlink($container);
			
			
			reset($tablesB);
			while (list ($tablename, $line) = each ($tablesB)){
				if($GLOBALS["VERBOSE"]){echo "Delete table `$tablename`\n";}
				if(!$q->DELETE_TABLE($tablename)){
					if($GLOBALS["VERBOSE"]){echo "Delete $tablename failed $q->mysql_error ...\n";}
					ufdbguard_admin_events("Fatal Error: Delete $tablename failed $q->mysql_error ",__FUNCTION__,__FILE__,__LINE__,"backup");
					return;				
				}
				
				$DeleteTables++;
				
			}
			
		}
		if($GLOBALS["VERBOSE"]){echo "Delete table `$TableKey` from tables_day\n";}
		$q->QUERY_SQL("DELETE FROM tables_day WHERE tablename='$TableKey'");
		
		
	}
	
	
	
	$container="$ArticaProxyStatisticsBackupFolder/squidlogs.FULL.sql";
	$cmd="$mysqldump_prefix >$container";
	$resultsZ=array();
	exec($cmdline,$resultsZ);
	chdir($ArticaProxyStatisticsBackupFolder);
	$cmdline="$tar cfz $container.tar.gz $container 2>&1";
	exec($cmdline);
	if(!$unix->TARGZ_TEST_CONTAINER("$container.tar.gz")){
		ufdbguard_admin_events("Error $container.tar.gz, not a valid compressed file",__FUNCTION__,__FILE__,__LINE__,"backup");
		@unlink("$container.tar.gz");
	}else{
		$size=@filesize($container);
		$TotalSize=$TotalSize+$size;
		@unlink("$container");
	}
	
	
	
	if($DeleteTables>0){
		$TotalSize=FormatBytes($TotalSize/1024);
		$took=$unix->distanceOfTimeInWords($t,time(),true);
		ufdbguard_admin_events("Success backup and purge $DeleteTables table(s) ($TotalSize) took:$took",__FUNCTION__,__FILE__,__LINE__,"backup");
		
	}
	
	
	
}






function ScanDays(){
	
	$q=new mysql_squid_builder(true);
	$ARRAY_DAYS=array();
	$tables=$q->LIST_TABLES_dansguardian_events();
	while (list ($tablename, $line) = each ($tables)){
		$dayTime=$q->TIME_FROM_DANSGUARDIAN_EVENTS_TABLE($tablename);
		$day=date("Y-m-d",$dayTime);
		$ARRAY_DAYS[$day]=$dayTime;
		
	}
	
	$tables=$q->LIST_TABLES_HOURS();
	while (list ($tablename, $line) = each ($tables)){
		$dayTime=$q->TIME_FROM_HOUR_TABLE($tablename);
		$day=date("Y-m-d",$dayTime);
		$ARRAY_DAYS[$day]=$dayTime;
	
	}	
	$tables=$q->LIST_TABLES_YOUTUBE_DAYS(); //youtubeday_
	while (list ($tablename, $line) = each ($tables)){
		$dayTime=$q->TIME_FROM_YOUTUBE_DAY_TABLE($tablename);
		$day=date("Y-m-d",$dayTime);
		$ARRAY_DAYS[$day]=$dayTime;
	
	}	
	$tables=$q->LIST_TABLES_USERSIZED(); //youtubeday_
	while (list ($tablename, $line) = each ($tables)){
		$dayTime=$q->TIME_FROM_USERSIZED_TABLE($tablename);
		$day=date("Y-m-d",$dayTime);
		$ARRAY_DAYS[$day]=$dayTime;
	
	}	
	
	
	
	
	$prefix="INSERT IGNORE INTO tables_day (tablename,zDate) VALUES ";
	while (list ($day, $dayTime) = each ($ARRAY_DAYS)){
		$tablename="dansguardian_events_".date("Ymd",$dayTime);
		$f[]="('$tablename','$day')";
		
	}
	if(count($f)>0){
		$q->QUERY_SQL($prefix.@implode(",", $f));
		if(!$q->ok){
			if($GLOBALS["VERBOSE"]){echo "Fatal $q->mysql_error\n";}
			ufdbguard_admin_events("Fatal $q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "backup");
			return false;
		}
	}
	
	
	return true;
}