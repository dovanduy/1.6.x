<?php

if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
$GLOBALS["FORCE"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
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

$unix=new unix();
if(is_file("/etc/artica-postfix/FROM_ISO")){
	if($unix->file_time_min("/etc/artica-postfix/FROM_ISO")<1){return;}
}


$GLOBALS["MYPID"]=getmypid();
if($argv[1]=="--ufdbcheck"){CoherenceRepertoiresUfdb();die();}
if($argv[1]=="--mysqlcheck"){CoherenceBase();die();}
if($argv[1]=="--localcheck"){CoherenceOffiels();die();}
if($argv[1]=="--compile"){compile();die();}
if($argv[1]=="--status"){BuildDatabaseStatus();die();}



Execute();

function Execute(){
	if(!ifMustBeExecuted()){
		
		
		if($GLOBALS["VERBOSE"]){echo "No make sense to execute this script...\n";}die();
	}
	$unix=new unix();
	
	
	$BASE_URI="ftp://ftp.univ-tlse1.fr/pub/reseau/cache/squidguard_contrib";
	
	
	$myFile=basename(__FILE__);
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$cachetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".{$GLOBALS["SCHEDULE_ID"]}.time";
	$unix=new unix();	
	$ufdbGenTable=$unix->find_program("ufdbGenTable");
	$kill=$unix->find_program("kill");
	$pid=@file_get_contents($pidfile);
	$getmypid=$GLOBALS["MYPID"];
	if(!$GLOBALS["FORCE"]){
		if($unix->process_exists($pid,$myFile)){
			$timePid=$unix->PROCCESS_TIME_MIN($pid);
			if($timePid<60){

				die();
			}else{
				shell_exec("$kill -9 $pid 2>&1");
			}
		}
		
	}
	@file_put_contents($pidfile,$getmypid);
	
	if($GLOBALS["VERBOSE"]){echo "Executed pid $getmypid\n";}
	if($GLOBALS["VERBOSE"]){echo "ufdbGenTable:$ufdbGenTable\n";}
	$sock=new sockets();
	$SquidDatabasesUtlseEnable=$sock->GET_INFO("SquidDatabasesUtlseEnable");
	if(!is_numeric($SquidDatabasesUtlseEnable)){$SquidDatabasesUtlseEnable=1;}	
	
	if($SquidDatabasesUtlseEnable==0){
		echo "Toulouse university is disabled\n";
	}
	if(!$GLOBALS["FORCE"]){
		$time=$unix->file_time_min($cachetime);
		if($time<120){
			$q=new mysql_squid_builder();
			if($q->COUNT_ROWS("univtlse1fr")==0){BuildDatabaseStatus();}
			echo "$cachetime: {$time}Mn need 120Mn\n";
			die();
		}
	}
	@unlink($cachetime);
	@file_put_contents($cachetime, time());
	
	
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL("SELECT * FROM ftpunivtlse1fr");
	if(!$q->ok){
		if(strpos($q->mysql_error, "doesn't exist")>0){$q->CheckTables();
		$results=$q->QUERY_SQL("SELECT * FROM ftpunivtlse1fr");}
	}
	
	
	if(!$q->ok){
		ufdbguard_admin_events("Fatal: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"Toulouse DB");
		WriteMyLogs("Fatal: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);

	}
	
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		
		$ARRAYSUM_LOCALE[$ligne["filename"]]=$ligne["zmd5"];
		
	}
	
	$ARRAYSUM_REMOTE=GET_MD5S_REMOTE();

	while (list ($filename,$md5) = each ($ARRAYSUM_REMOTE) ){
		if(!isset($ARRAYSUM_LOCALE[$filename])){$ARRAYSUM_LOCALE[$filename]=null;}
		if($ARRAYSUM_LOCALE[$filename]<>$md5){update_remote_file($BASE_URI,$filename,$md5);}
	}
	
	if(count($GLOBALS["squid_admin_mysql"])){
		artica_update_event(2, count($GLOBALS["squid_admin_mysql"])." Webfiltering Toulouse Databases updated", @implode("\n", $GLOBALS["squid_admin_mysql"]),__FILE__,__LINE__);
		unset($GLOBALS["squid_admin_mysql"]);
	}
	
	
	CoherenceOffiels();
	CoherenceRepertoiresUfdb();
	BuildDatabaseStatus();
	remove_bad_files();
	
	
	
	
	$php5=$unix->LOCATE_PHP5_BIN();
	$ufdbConvertDB=$unix->find_program("ufdbConvertDB");
	if(is_file($ufdbConvertDB)){
		shell_exec("$ufdbConvertDB /var/lib/ftpunivtlse1fr");
	}
	
	if(is_dir("/var/lib/ftpunivtlse1fr")){
		$chown=$unix->find_program("chown");
		shell_exec("$chown squid:squid /var/lib/ftpunivtlse1fr");
		shell_exec("$chown -R squid:squid /var/lib/ftpunivtlse1fr/");
	
	}

	
	shell_exec("$php5 /usr/share/artica-postfix/exec.squidguard.php --disks");
	
	
	
}

function BuildDatabaseStatus(){
	
	if(!ifMustBeExecuted()){
		
		WriteMyLogs("No make sense to execute this script...",__FUNCTION__,__FILE__,__LINE__);
		if($GLOBALS["VERBOSE"]){echo "No make sense to execute this script...\n";}die();
	}
	
	$q=new mysql_squid_builder();
	$unix=new unix();
	$dirs=$unix->dirdir("/var/lib/ftpunivtlse1fr");
	
	$TLSE_CONVERTION=$q->TLSE_CONVERTION();
	while (list ($directory, $line) = each ($dirs) ){
		
		$catzname=$TLSE_CONVERTION[basename($directory)];
		if($catzname==null){continue;}
		if(!is_file("$directory/domains")){
			if($GLOBALS["VERBOSE"]){echo "$catzname=0\n";}
			$f[$catzname]["F"]=0;
			$f[$catzname]["D"]="0000-00-00 00:00:00";
			$f[$catzname]["FF"]="$directory/domains";
		}else{
			$f[$catzname]["F"]=$unix->COUNT_LINES_OF_FILE("$directory/domains");
			if($GLOBALS["VERBOSE"]){echo "$catzname={$f[$catzname]["F"]}\n";}
			$f[$catzname]["D"]=date("Y-m-d H:i:s",filemtime("$directory/domains"));
			$f[$catzname]["FF"]="$directory/domains";
		}
		
		
		
	}
	
	$LastC=trim(@file_get_contents("/etc/artica-postfix/ftpunivtlse1frCount"));
	$c=0;
	while (list ($cat, $array) = each ($f) ){
		$count=$array["F"];
		$date=$array["D"];
		$c=$c+$count;
		$sql[]="('$cat','$count','$date')";
	}
	
	if(count($sql)>0){
		$q->QUERY_SQL("TRUNCATE TABLE univtlse1fr");
		$q->QUERY_SQL("INSERT IGNORE INTO univtlse1fr (category,websitesnum,zDate) VALUES ".@implode(",", $sql));
		if(!$q->ok){
			artica_update_event(2,"Fatal $q->mysql_error",null,__FILE__,__LINE__);
			return;
		}
		if($c<>$LastC){
			//artica_update_event("Toulouse University status: $c items in database",__FUNCTION__,__FILE__,__LINE__);
			@file_put_contents("/etc/artica-postfix/ftpunivtlse1frCount", $c);
		}
		
	}
	//univtlse1fr
	
	
	
}



function GET_MD5S_REMOTE(){
	
	if(!ifMustBeExecuted()){
		$GLOBALS["LOGS"][]="ifMustBeExecuted():: No make sense to execute this script.";
		WriteMyLogs("ifMustBeExecuted():: No make sense to execute this script...",__FUNCTION__,__FILE__,__LINE__);
		if($GLOBALS["VERBOSE"]){echo "No make sense to execute this script...\n";}die();
	}
	
	$unix=new unix();
	$BASE_URI="ftp://ftp.univ-tlse1.fr/pub/reseau/cache/squidguard_contrib";
	$indexuri="$BASE_URI/MD5SUM.LST";
	$cache_temp=$unix->FILE_TEMP();	
	$curl=new ccurl($indexuri);
	WriteMyLogs("Downloading $indexuri",__FUNCTION__,__FILE__,__LINE__);
	$curl->Timeout=320;
	if(!$curl->GetFile($cache_temp)){
		$errorDetails=@implode("\n", $GLOBALS["CURLDEBUG"]);
		WriteMyLogs("Fatal error downloading $indexuri $curl->error\n$errorDetails",__FUNCTION__,__FILE__,__LINE__);
		artica_update_event(0, "Web filtering databases, unable to download index file", "Fatal error downloading $indexuri $curl->error\n$errorDetails",__FILE__,__LINE__);
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
	return $ARRAYSUM_REMOTE;
}


function update_remote_file($BASE_URI,$filename,$md5){
	WriteMyLogs("update_remote_file($BASE_URI,$filename,$md5)",__FUNCTION__,__FILE__,__LINE__);
	$indexuri="$BASE_URI/$filename";
	$unix=new unix();
	$q=new mysql_squid_builder();
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$ln=$unix->find_program("ln");
	$ufdbGenTable=$unix->find_program("ufdbGenTable");
	$Conversion=$q->TLSE_CONVERTION();
	$ufdb=new compile_ufdbguard();
	$curl=new ccurl($indexuri);
	$curl->Timeout=360;
	echo "Downloading $indexuri\n";
	$cache_temp="/tmp/$filename";
	if(!$curl->GetFile($cache_temp)){echo "Fatal error downloading $indexuri $curl->error\n";
		$errorDetails=@implode("\n", $GLOBALS["CURLDEBUG"]);
		artica_update_event(0, "Web filtering databases, unable to download $indexuri", "Fatal error downloading $indexuri $curl->error\n$errorDetails",__FILE__,__LINE__);
		return;
	}
	
	$filesize=$unix->file_size($cache_temp);
	
	
	
	@mkdir("/var/lib/ftpunivtlse1fr",755,true);
	$categoryname=str_replace(".tar.gz", "", $filename);
	$categoryDISK=$categoryname;
	if(isset($Conversion[$categoryname])){$categoryDISK=$Conversion[$categoryname];}
	$categoryDISK=str_replace("/", "_", $categoryDISK);
	
	if(is_link("/var/lib/ftpunivtlse1fr/$categoryname")){
		if($GLOBALS["VERBOSE"]){echo "/var/lib/ftpunivtlse1fr/$categoryname is link of ". @readlink("/var/lib/ftpunivtlse1fr/$categoryname")."\n";}
		if($GLOBALS["VERBOSE"]){echo "Removing  /var/lib/ftpunivtlse1fr/$categoryname/\n";}
		shell_exec("$rm -rf /var/lib/ftpunivtlse1fr/$categoryname");
	}
	
	
	if(is_dir("/var/lib/ftpunivtlse1fr/$categoryname")){
		if($GLOBALS["VERBOSE"]){echo "Removing  /var/lib/ftpunivtlse1fr/$categoryname/\n";}
		shell_exec("$rm -rf /var/lib/ftpunivtlse1fr/$categoryname");
	}
	if($GLOBALS["VERBOSE"]){echo "Creating  /var/lib/ftpunivtlse1fr/$categoryname/\n";}
	@mkdir("/var/lib/ftpunivtlse1fr/$categoryname",0755,true);
	
	if($GLOBALS["VERBOSE"]){echo "Extracting $cache_temp to  /var/lib/ftpunivtlse1fr/\n";}
	
	shell_exec("$tar -xf $cache_temp -C /var/lib/ftpunivtlse1fr/");
	if(!is_file("/var/lib/ftpunivtlse1fr/$categoryname/domains")){
		ufdbguard_admin_events("Fatal!!: /var/lib/ftpunivtlse1fr/$categoryname/domains no such file",__FUNCTION__,__FILE__,__LINE__,"Toulouse DB");
		return;
	}
	$CountDeSitesFile=CountDeSitesFile("/var/lib/ftpunivtlse1fr/$categoryname/domains");
	if($GLOBALS["VERBOSE"]){echo "/var/lib/ftpunivtlse1fr/$categoryname/domains -> $CountDeSitesFile websites\n";}
	if($CountDeSitesFile==0){
		ufdbguard_admin_events("Fatal!!: /var/lib/ftpunivtlse1fr/$categoryname/domains corrupted, no website",__FUNCTION__,__FILE__,__LINE__,"Toulouse DB");
		shell_exec("$rm -rf /var/lib/ftpunivtlse1fr/$categoryname");
		return;		
	}
	
	if(trim(strtolower($categoryDISK))<>trim(strtolower($categoryname))){
		if(is_dir("/var/lib/ftpunivtlse1fr/$categoryDISK")){shell_exec("$rm -rf /var/lib/ftpunivtlse1fr/$categoryDISK");}
		shell_exec("ln -sf /var/lib/ftpunivtlse1fr/$categoryDISK /var/lib/ftpunivtlse1fr/$categoryname");
	}
	
	
	$q->QUERY_SQL("DELETE FROM ftpunivtlse1fr WHERE filename='$filename'");
	if(!$q->ok){ufdbguard_admin_events("Fatal!!: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"Toulouse DB");return;}
	$q->QUERY_SQL("INSERT INTO ftpunivtlse1fr (`filename`,`zmd5`,`websitesnum`) VALUES ('$filename','$md5','$CountDeSitesFile')");
	if(!$q->ok){ufdbguard_admin_events("Fatal!!: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"Toulouse DB");return;}
	
	
	$GLOBALS["squid_admin_mysql"][]="Success updating category `$categoryname` with $CountDeSitesFile websites";
	if($GLOBALS["VERBOSE"]){echo "ufdbGenTable=$ufdbGenTable\n";}
	

	
	
	
	
	if(is_file($ufdbGenTable)){
		$t=time();
		$ufdb->UfdbGenTable("/var/lib/ftpunivtlse1fr/$categoryname",$categoryname);
	}
	
	
}

function remove_bad_files(){

	$unix=new unix();

	$dirs=$unix->dirdir("/var/lib/ftpunivtlse1fr");
	while (list ($directory, $b) = each ($dirs)){
		$dirname=basename($directory);
		if(is_link("$directory/$dirname")){
			echo "Starting......: ".date("H:i:s")." UfdBguard removing $dirname/$dirname bad file\n";
			@unlink("$directory/$dirname");
		}
	}


	echo "Starting......: ".date("H:i:s")." UfdBguard removing bad files done...\n";
}

function compile(){
	
	if(!ifMustBeExecuted()){
		
		WriteMyLogs("No make sense to execute this script...",__FUNCTION__,__FILE__,__LINE__);
		if($GLOBALS["VERBOSE"]){echo "No make sense to execute this script...\n";}die();
	}
	
	$ufdb=new compile_ufdbguard();
	$q=new mysql_squid_builder();
	$unix=new unix();
	
	$t=time();
	$myFile=basename(__FILE__);
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();	
	$ufdbGenTable=$unix->find_program("ufdbGenTable");
	if(!is_file($ufdbGenTable)){return;}
	
	
	$pid=@file_get_contents($pidfile);
	$getmypid=$GLOBALS["MYPID"];
	if(!$GLOBALS["FORCE"]){
		if($unix->process_exists($pid,$myFile)){
			$timePid=$unix->PROCCESS_TIME_MIN($pid);
			ufdbguard_admin_events("Already executed PID:$pid, since {$timePid}Mn die() ",__FUNCTION__,__FILE__,__LINE__,"Toulouse DB");
			die();
		}
	}	
	
	
	$Conversion=$q->TLSE_CONVERTION();
	$workdir="/var/lib/ftpunivtlse1fr";
	$c=0;
	while (list ($directory, $line) = each ($Conversion) ){
		$c++;
		$ufdb->UfdbGenTable("$workdir/$directory",$directory);	
		$unix->chown_func("squid", "squid","$workdir/$directory");
	}
	
	ufdbguard_admin_events("Compiling $c databases done, took:".$unix->distanceOfTimeInWords($t,time(),true),__FUNCTION__,__FILE__,__LINE__,"Toulouse DB");
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php5 /usr/share/artica-postfix/exec.squidguard.php --build schedule-id={$GLOBALS["SCHEDULE_ID"]}");
}

function CoherenceRepertoiresUfdb(){
	
	if(!ifMustBeExecuted()){
		
		WriteMyLogs("No make sense to execute this script...",__FUNCTION__,__FILE__,__LINE__);
		if($GLOBALS["VERBOSE"]){echo "No make sense to execute this script...\n";}die();
	}
	
	$unix=new unix();
	$q=new mysql_squid_builder();
	$BASE_URI="ftp://ftp.univ-tlse1.fr/pub/reseau/cache/squidguard_contrib";
	$ufdbGenTable=$unix->find_program("ufdbGenTable");
	if(!is_file($ufdbGenTable)){return;}
	$ufdb=new compile_ufdbguard();
	$rm=$unix->find_program("rm");
	$dirs=$unix->dirdir("/var/lib/ftpunivtlse1fr");
	$Conversion=$q->TLSE_CONVERTION();
	while (list ($directory, $line) = each ($dirs) ){
		$database=basename($directory);
		if(isset($Conversion[$database])){
		if(!is_file("$directory/domains")){echo "$directory has no domains\n";shell_exec("$rm -rf $directory");continue;}
		
		if(is_file("$directory/domains.ufdb")){
			if($GLOBALS["VERBOSE"]){echo "$directory/domains.ufdb OK\n";}
			continue;
		}
			$ufdb->UfdbGenTable("$directory",basename($directory));	
		}
	}
	
	
}

function CoherenceOffiels(){
	
	if(!ifMustBeExecuted()){
		WriteMyLogs("No make sense to execute this script...",__FUNCTION__,__FILE__,__LINE__);
		if($GLOBALS["VERBOSE"]){echo "No make sense to execute this script...\n";}die();
	}
	
	$workdir="/var/lib/ftpunivtlse1fr";
	$unix=new unix();
	$BASE_URI="ftp://ftp.univ-tlse1.fr/pub/reseau/cache/squidguard_contrib";
	$q=new mysql_squid_builder();
	$table=$q->TLSE_CONVERTION(true);
	$ARRAYSUM_REMOTE=GET_MD5S_REMOTE();
	while (list ($database, $articacat) = each ($table) ){
		$directory=str_replace("/", "_", $articacat);
		$targetdir=$workdir."/$database";
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":: Checking $targetdir/domains\n";}
		if(!is_file("$targetdir/domains")){
			ufdbguard_admin_events("$database is not in disk... download it..",__FUNCTION__,__FILE__,__LINE__,"Toulouse DB");
			update_remote_file($BASE_URI,"$database.tar.gz",$ARRAYSUM_REMOTE["$database.tar.gz"]);
		}
	}
	
	reset($table);
	while (list ($database, $articacat) = each ($table) ){
		$directory=str_replace("/", "_", $articacat);
		
		
		$targetdir=$workdir."/$directory";
		$sourcedir=$workdir."/$database";
		@chmod($sourcedir, 0755);
		$unix->chown_func("squid", "squid",$sourcedir);
		if(!is_dir($targetdir)){
			if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":: Checking $targetdir no such directory make symbolic to $sourcedir\n";}
			shell_exec("ln -sf $sourcedir $targetdir");
		}
	}
	
	if(count($GLOBALS["squid_admin_mysql"])){
		squid_admin_mysql(2,count($GLOBALS["squid_admin_mysql"])." Toulouse Databases updated",@implode("\n", $GLOBALS["squid_admin_mysql"]));
		unset($GLOBALS["squid_admin_mysql"]);
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
	if(!$q->ok){ufdbguard_admin_events("Fatal: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"Toulouse DB");die();}
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
	$q=new mysql_squid_builder();
	return $q->ifSquidUpdatesMustBeExecuted();
	
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