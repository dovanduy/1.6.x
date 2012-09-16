<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.squidguard.inc');
include_once(dirname(__FILE__).'/ressources/class.compile.ufdbguard.inc');

$GLOBALS["MYPID"]=getmypid();
if($argv[1]=="--ufdbcheck"){CoherenceRepertoiresUfdb();die();}
if($argv[1]=="--mysqlcheck"){CoherenceBase();die();}

Execute();

function Execute(){
	if(!ifMustBeExecuted()){
		ufdbguard_admin_events("No make sense to execute this script...",__FUNCTION__,__FILE__,__LINE__,"update");
		WriteMyLogs("No make sense to execute this script...",__FUNCTION__,__FILE__,__LINE__);
		if($GLOBALS["VERBOSE"]){echo "No make sense to execute this script...\n";}die();
	}
	$unix=new unix();
	$myFile=basename(__FILE__);
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$cachetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".{$GLOBALS["SCHEDULE_ID"]}.time";
	$unix=new unix();	
	$ufdbGenTable=$unix->find_program("ufdbGenTable");
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,$myFile)){WriteMyLogs("Already executed PID:$pid, die()",__FUNCTION__,__FILE__,__LINE__);die();}
	$getmypid=$GLOBALS["MYPID"];
	@file_put_contents($pidfile,$getmypid);
	
	WriteMyLogs("Executed pid $getmypid",__FUNCTION__,__FILE__,__LINE__);
	WriteMyLogs("ufdbGenTable:$ufdbGenTable",__FUNCTION__,__FILE__,__LINE__);
	$sock=new sockets();
	$SquidDatabasesUtlseEnable=$sock->GET_INFO("SquidDatabasesUtlseEnable");
	if(!is_numeric($SquidDatabasesUtlseEnable)){$SquidDatabasesUtlseEnable=1;}	
	if($SquidDatabasesUtlseEnable==0){WriteMyLogs("Toulouse university is disabled",__FUNCTION__,__FILE__,__LINE__);die();}

	$time=$unix->file_time_min($cachetime);
	if($time<120){
		ufdbguard_admin_events("$cachetime: {$time}Mn need 120Mn",__FUNCTION__,__FILE__,__LINE__,"update");
		WriteMyLogs("$cachetime: {$time}Mn need 120Mn",__FUNCTION__,__FILE__,__LINE__);	die();
	}
	@unlink($cachetime);
	@file_put_contents($cachetime, time());
	
	
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL("SELECT * FROM ftpunivtlse1fr");
	if(!$q->ok){
		if(strpos($q->mysql_error, "doesn't exist")>0){$q->CheckTables();$results=$q->QUERY_SQL("SELECT * FROM ftpunivtlse1fr");}
	}
	
	
	if(!$q->ok){
		ufdbguard_admin_events("Fatal: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"update");
		WriteMyLogs("Fatal: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		die();
	}
	
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		WriteMyLogs("ftpunivtlse1fr: {$ligne["filename"]} -> {$ligne["zmd5"]}",__FUNCTION__,__FILE__,__LINE__);
		$ARRAYSUM_LOCALE[$ligne["filename"]]=$ligne["zmd5"];
		
	}
	
	
	
	$BASE_URI="ftp://ftp.univ-tlse1.fr/pub/reseau/cache/squidguard_contrib";
	
	$indexuri="$BASE_URI/MD5SUM.LST";
	$cache_temp=$unix->FILE_TEMP();	
	$curl=new ccurl($indexuri);
	WriteMyLogs("Downloading $indexuri",__FUNCTION__,__FILE__,__LINE__);
	if(!$curl->GetFile($cache_temp)){
		WriteMyLogs("Fatal error downloading $indexuri $curl->error",__FUNCTION__,__FILE__,__LINE__);
		ufdbguard_admin_events("Fatal: unable to download index file $indexuri `$curl->error`",__FUNCTION__,__FILE__,__LINE__,"update");
		die();
	}
	$f=explode("\n",@file_get_contents($cache_temp));
	while (list ($index, $line) = each ($f) ){
		if(trim($line)==null){continue;}
		if(!preg_match("#^([a-z0-9]+)\s+([a-z0-9\.]+)$#", $line,$re)){continue;}
		$md5=$re[1];
		$filename=$re[2];
		if($filename=="blacklists.tar.gz"){continue;}
		if($filename=="domains.tar.gz"){continue;}
		if($filename=="MD5SUM.LST"){continue;}
		$ARRAYSUM_REMOTE[$filename]=$md5;
	}
	while (list ($filename,$md5) = each ($ARRAYSUM_REMOTE) ){
		if(!isset($ARRAYSUM_LOCALE[$filename])){$ARRAYSUM_LOCALE[$filename]=null;}
		if($ARRAYSUM_LOCALE[$filename]<>$md5){update_remote_file($BASE_URI,$filename,$md5);}
		
	}
	
	if(is_dir("/var/lib/ftpunivtlse1fr")){
		$chown=$unix->find_program("chown");
		shell_exec("$chown squid:squid /var/lib/ftpunivtlse1fr");
		shell_exec("$chown -R squid:squid /var/lib/ftpunivtlse1fr/");
		
	}
	
	CoherenceRepertoiresUfdb();
	
	
	
	
}


function update_remote_file($BASE_URI,$filename,$md5){
	WriteMyLogs("update_remote_file($BASE_URI,$filename,$md5)",__FUNCTION__,__FILE__,__LINE__);
	$indexuri="$BASE_URI/$filename";
	$unix=new unix();
	$q=new mysql_squid_builder();
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$ufdbGenTable=$unix->find_program("ufdbGenTable");
	$ufdb=new compile_ufdbguard();
	$curl=new ccurl($indexuri);
	echo "Downloading $indexuri\n";
	$cache_temp="/tmp/$filename";
	if(!$curl->GetFile($cache_temp)){echo "Fatal error downloading $indexuri $curl->error\n";
		ufdbguard_admin_events("Fatal: unable to download index file $indexuri `$curl->error`",__FUNCTION__,__FILE__,__LINE__,"update");
		return;
	}
	
	@mkdir("/var/lib/ftpunivtlse1fr",755,true);
	$categoryname=str_replace(".tar.gz", "", $filename);
	ufdbguard_admin_events("Extracting $filename for category $categoryname",__FUNCTION__,__FILE__,__LINE__,"update");
	if(is_dir("/var/lib/ftpunivtlse1fr/$categoryname")){shell_exec("$rm -rf /var/lib/ftpunivtlse1fr/$categoryname");}
	@mkdir("/var/lib/ftpunivtlse1fr/$categoryname",755,true);
	shell_exec("$tar -xf $cache_temp -C /var/lib/ftpunivtlse1fr/");
	if(!is_file("/var/lib/ftpunivtlse1fr/$categoryname/domains")){
		ufdbguard_admin_events("/var/lib/ftpunivtlse1fr/$categoryname/domains no such file",__FUNCTION__,__FILE__,__LINE__,"update");
		return;
	}
	$CountDeSitesFile=CountDeSitesFile("/var/lib/ftpunivtlse1fr/$categoryname/domains");
	if($GLOBALS["VERBOSE"]){echo "/var/lib/ftpunivtlse1fr/$categoryname/domains -> $CountDeSitesFile websites\n";}
	if($CountDeSitesFile==0){
		ufdbguard_admin_events("/var/lib/ftpunivtlse1fr/$categoryname/domains corrupted, no website",__FUNCTION__,__FILE__,__LINE__,"update");
		return;		
	}
	WriteMyLogs("DELETE FROM ftpunivtlse1fr WHERE filename='$filename'",__FUNCTION__,__FILE__,__LINE__);
	$q->QUERY_SQL("DELETE FROM ftpunivtlse1fr WHERE filename='$filename'");
	if(!$q->ok){ufdbguard_admin_events("Fatal: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"update");return;}
	$q->QUERY_SQL("INSERT INTO ftpunivtlse1fr (`filename`,`zmd5`,`websitesnum`) VALUES ('$filename','$md5','$CountDeSitesFile')");
	if(!$q->ok){ufdbguard_admin_events("Fatal: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"update");return;}
	ufdbguard_admin_events("Success updating category `$categoryname` with $CountDeSitesFile websites",__FUNCTION__,__FILE__,__LINE__,"update");
	if($GLOBALS["VERBOSE"]){echo "ufdbGenTable=$ufdbGenTable\n";}
	if(is_file($ufdbGenTable)){
		$t=time();
		
		$ufdb->UfdbGenTable("/var/lib/ftpunivtlse1fr/$categoryname",$categoryname);
	}
	
	
}

function CoherenceRepertoiresUfdb(){
	$unix=new unix();
	$ufdbGenTable=$unix->find_program("ufdbGenTable");
	if(!is_file($ufdbGenTable)){return;}
	$ufdb=new compile_ufdbguard();
	$rm=$unix->find_program("rm");
	$dirs=$unix->dirdir("/var/lib/ftpunivtlse1fr");
	while (list ($directory, $line) = each ($dirs) ){
		if(!is_file("$directory/domains")){echo "$directory has no domains\n";shell_exec("$rm -rf $directory");continue;}
		if(is_file("$directory/domains.ufdb")){
			if($GLOBALS["VERBOSE"]){echo "$directory/domains.ufdb OK\n";}
			continue;}
		$ufdb->UfdbGenTable("$directory",basename($directory));	
	}
	
	
}

function CoherenceBase(){
	$unix=new unix();
	$myFile=basename(__FILE__);
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$cachetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$unix=new unix();	
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,$myFile)){WriteMyLogs("Already executed PID:$pid, die()",__FUNCTION__,__FILE__,__LINE__);die();}	
	
	$q=new mysql_squid_builder();
	$unix=new unix();
	$results=$q->QUERY_SQL("SELECT * FROM ftpunivtlse1fr");
	if(!$q->ok){if(strpos($q->mysql_error, "doesn't exist")>0){$q->CheckTables();$results=$q->QUERY_SQL("SELECT * FROM ftpunivtlse1fr");}}
	if(!$q->ok){ufdbguard_admin_events("Fatal: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"update");die();}
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){if($GLOBALS["VERBOSE"]){echo "ftpunivtlse1fr: {$ligne["filename"]} -> {$ligne["zmd5"]}\n";}$ARRAYSUM_LOCALE[$ligne["filename"]]=$ligne["zmd5"];}
	
	$dirs=$unix->dirdir("/var/lib/ftpunivtlse1fr");
	while (list ($directory, $line) = each ($dirs) ){
		if(!is_file("$directory/domains")){echo "$directory has no domains\n";shell_exec("$rm -rf $directory");continue;}
		$virtualFilename=basename($directory).".tar.gz";
		if(!isset($ARRAYSUM_LOCALE[$virtualFilename])){
			$CountDeSitesFile=CountDeSitesFile("$directory/domains");
			$md5=md5($virtualFilename);
			echo "Add virtual filename $virtualFilename with $CountDeSitesFile domains";
			$q->QUERY_SQL("INSERT INTO ftpunivtlse1fr (`filename`,`zmd5`,`websitesnum`) VALUES ('$virtualFilename','$md5','$CountDeSitesFile')");
		}else{
			if($GLOBALS["VERBOSE"]){echo "LOCAL: $virtualFilename -> $directory OK\n";}
		}
	}
}



function CountDeSitesFile($filename){
	$unix=new unix();
	$wc=$unix->find_program("wc");
	exec("$wc $filename 2>&1",$results);
	$txt=trim(@implode("", $results));
	if(preg_match("#^([0-9]+)#", $txt,$re)){return $re[1];}
	if($GLOBALS["VERBOSE"]){echo "$wc $filename 2>&1 -> `$txt` no match\n";}
	return 0;
}




function ifMustBeExecuted(){
	$users=new usersMenus();
	$sock=new sockets();
	$update=true;
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($CategoriesRepositoryEnable)){$CategoriesRepositoryEnable=0;}
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if($EnableRemoteStatisticsAppliance==1){writelogs("EnableRemoteStatisticsAppliance ACTIVE ,ABORTING TASK",__FUNCTION__,__FILE__,__LINE__);die();}	
	if($EnableWebProxyStatsAppliance==1){return true;}	
	$CategoriesRepositoryEnable=$sock->GET_INFO("CategoriesRepositoryEnable");
	if($CategoriesRepositoryEnable==1){return true;}
	if(!$users->SQUID_INSTALLED){$update=false;}
	return $update;
}
function WriteMyLogs($text,$function,$file,$line){
	if(!isset($GLOBALS["MYPID"])){$GLOBALS["MYPID"]=getmypid();}
	$mem=round(((memory_get_usage()/1024)/1000),2);
	writelogs($text,$function,__FILE__,$line);
	$logFile="/var/log/artica-postfix/".basename(__FILE__).".log";
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
   	if (is_file($logFile)) { 
   		$size=filesize($logFile);
   		if($size>9000000){unlink($logFile);}
   	}
   	$date=date('m-d H:i:s');
	$logFile=str_replace("//","/",$logFile);
	$f = @fopen($logFile, 'a');
	if($GLOBALS["VERBOSE"]){echo "$date [{$GLOBALS["MYPID"]}][{$mem}MB]: [$function::$line] $text\n";}
	@fwrite($f, "$date [{$GLOBALS["MYPID"]}][{$mem}MB][Task:{$GLOBALS["SCHEDULE_ID"]}]: [$function::$line] $text\n");
	@fclose($f);
}