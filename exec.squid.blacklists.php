<?php
$GLOBALS["FULL"]=false;
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.squidguard.inc');
$GLOBALS["working_directory"]="/opt/artica/proxy";
$GLOBALS["MAILLOG"]=array();
$GLOBALS["CHECKTIME"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["BYCRON"]=false;
$GLOBALS["MYPID"]=getmypid();
$GLOBALS["CMDLINE"]=@implode(" ", $argv);
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;$GLOBALS["CHECKTIME"]=false;}
if(preg_match("#--checktime#",implode(" ",$argv))){$GLOBALS["CHECKTIME"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--bycron#",implode(" ",$argv))){$GLOBALS["BYCRON"]=true;$GLOBALS["CHECKTIME"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}

if($argv[1]=="--support"){RegisterSupport();exit;}



WriteMyLogs("Executed: {$GLOBALS["CMDLINE"]} task:{$GLOBALS["SCHEDULE_ID"]}",__FUNCTION__,__FILE__,__LINE__);


	$sock=new sockets();
	
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	
	if($EnableRemoteStatisticsAppliance==1){
		writelogs("EnableRemoteStatisticsAppliance ACTIVE ,ABORTING TASK",__FUNCTION__,__FILE__,__LINE__);
		WriteMyLogs("EnableRemoteStatisticsAppliance ACTIVE ,ABORTING TASK",__FUNCTION__,__FILE__,__LINE__);
		die();
	}

	
	if(system_is_overloaded(basename(__FILE__))){
		$ldao=getSystemLoad();
		ufdbguard_admin_events("Execute this script is stopped, system overloaded ($ldao)",__FUNCTION__,__FILE__,__LINE__,"update");
		die();
	}		


if($argv[1]=="--export"){export_table($argv[2]);die();}
if($argv[1]=="--export-all"){export_all_tables();die();}
if($argv[1]=="--merge-table"){merge_table($argv[2],$argv[3]);die();}
if(!ifMustBeExecuted2()){die("Not a squid service....") ;}
if($argv[1]=="--update"){updatev2();die();}
if($argv[1]=="--downloads"){updatev2_checktables(true);die();}
if($argv[1]=="--inject"){updatev2_checktables(true);die();}
if($argv[1]=="--reprocess-database"){updatev2($argv[2]);die();}
if($argv[1]=="--fullupdate"){updatev2();die();}
if($argv[1]=="--schedule-maintenance"){schedulemaintenance();die();}
if($argv[1]=="--categorize-delete"){categorize_delete();die();}
if($argv[1]=="--v2"){updatev2();die();}
if($argv[1]=="--v2-index"){updatev2_index();die();}
if($argv[1]=="--ufdb"){ufdbtables();die();}
if($argv[1]=="--ufdb-first"){ufdbFirst();die();}
if($argv[1]=="--scan-db"){scan_artica_databases();die();}
if($argv[1]=="--repair"){updatev2_checktables_repair();die();}




updatev2();

function ufdbFirst(){
	if(!is_file("/etc/artica-postfix/ufdbfirst")){
		@file_put_contents("/etc/artica-postfix/ufdbfirst", time());
		ufdbtables();
	}
}


function ufdbtables($nopid=false){
	$unix=new unix();
	$sock=new sockets();
	$CACHE_FILE="/etc/artica-postfix/ufdb.tables.db";
	$URIBASE="http://www.artica.fr/ufdb";
	$WORKDIR="/var/lib/ufdbartica";
	if(@file_get_contents("/usr/local/share/artica/.lic")<>"TRUE"){
		ufdbguard_admin_events("UFDB::Warning: only corporate license is allowed to be updated...",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
		return;
	}
	if(!$nopid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$nohup=$unix->find_program("nohup");
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid,__FILE__)){
			$timepid=$unix->PROCCESS_TIME_MIN($pid);
			ufdbguard_admin_events("UFDB::Warning: Task already executed PID: $pid since {$timepid}Mn",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
			return;
		}		
		@file_put_contents($pidfile, getmypid());
	}
		
	
	$curl=new ccurl("$URIBASE/index.txt");
	
	if(!$curl->GetFile("/tmp/index.txt")){
		ufdbguard_admin_events("UFDB::Fatal: Unable to download blacklist index file $curl->error",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
		echo "UFDB: Failed to retreive $URIBASE/index.txt ($curl->error)\n";
		return;
	}

	$LOCAL_CACHE=unserialize(base64_decode(@file_get_contents($CACHE_FILE)));
	$REMOTE_CACHE=unserialize(base64_decode(@file_get_contents("/tmp/index.txt")));
	$BigSize=0;
	$c=0;
	while (list ($tablename, $size) = each ($REMOTE_CACHE) ){	
		if($size<>$LOCAL_CACHE[$tablename]){
			$c++;
			$OriginalSize=$size;
			echo "UFDB: downloading $tablename remote size:$size, local size:{$LOCAL_CACHE[$tablename]}\n";
			$curl=new ccurl("$URIBASE/$tablename.gz");
			$curl->Timeout=380;
			if(!$curl->GetFile("/tmp/$tablename.gz")){
				ufdbguard_admin_events("UFDB::Fatal: unable to download blacklist $tablename.gz file $curl->error",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
				continue;
			}
			
			@mkdir("$WORKDIR/$tablename",0755,true);
			if(!ufdbtables_uncompress("/tmp/$tablename.gz","$WORKDIR/$tablename/domains.ufdb")){
				ufdbguard_admin_memory("UFDB::Fatal: unable to extract blacklist $tablename.gz file",__FUNCTION__,__FILE__,__LINE__,
				"ufbd-artica");
				continue;
			}
			@chown("$WORKDIR/$tablename/domains.ufdb", "squid");
			@chgrp("$WORKDIR/$tablename/domains.ufdb", "squid");
			$size=$unix->file_size("$WORKDIR/$tablename/domains.ufdb");
			$size=round(($size/1024),2);
			$BigSize=$BigSize+$size;
			@chown("$WORKDIR/$tablename", "squid");
			@chgrp("$WORKDIR/$tablename", "squid");	
			$LOCAL_CACHE[$tablename]=$OriginalSize;	
			ufdbguard_admin_memory("UFDB::Success update $tablename category $size Ko",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");			
		}
		
	}
	
	@file_put_contents($CACHE_FILE, base64_encode(serialize($LOCAL_CACHE)));
	$ufdbguard_admin_memory=@implode("\n", $GLOBALS["ufdbguard_admin_memory"]);	
	if($c>0){
		
		ufdbguard_admin_events("UFDB::Success update $c categories $BigSize extracted\n$ufdbguard_admin_memory",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
		$sock->TOP_NOTIFY("Success update $c blacklists categories $BigSize Ko extracted","info");
		
	}else{
		if($GLOBALS["FORCE"]){
			ufdbguard_admin_events("No update available\n$ufdbguard_admin_memory",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
		}
	}
	
		
		
	@chown("$WORKDIR", "squid");
	@chgrp("$WORKDIR", "squid");	
	scan_artica_databases();
	return true;
	
	
}
function ufdbtables_uncompress($srcName, $dstName) {
    $sfp = gzopen($srcName, "rb");
    $fp = fopen($dstName, "w");
	if(!$sfp){return false;}
	if(!$fp){return false;}
    while ($string = gzread($sfp, 4096)) {
        fwrite($fp, $string, strlen($string));
    }
    gzclose($sfp);
    fclose($fp);
	return true;
} 


function updatev2_index(){
	
	$sock=new sockets();
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	if($DisableArticaProxyStatistics==1){die();}	
	
	$curl=new ccurl("http://www.artica.fr/catz/index.txt");
	if(!$curl->GetFile("/tmp/index.txt")){
		ufdbguard_admin_events("Fatal: unable to download blacklist index file $curl->error",__FUNCTION__,__FILE__,__LINE__,"update");
		echo "BLACKLISTS: Failed to retreive http://www.artica.fr/catz/index.txt ($curl->error)\n";
		return;
	}

	$f=unserialize(base64_decode(@file_get_contents("/tmp/index.txt")));
	if(!is_array($f)){ufdbguard_admin_events("Fatal: index file, no such array",__FUNCTION__,__FILE__,__LINE__,"update");return;}	
	return $f;
	
	
}

function RegisterSupport(){
	$curl=new ccurl("http://www.artica.fr/support/cron/index.php?/parser/ParserMinute/POP3IMAP");
	if(!$curl->GetFile("/tmp/POP3IMAP")){
		echo $curl->error."\n";
		return;
	}	
	
	@unlink("/tmp/POP3IMAP");
	//echo @file_get_contents("/tmp/POP3IMAP");
	
	
}

function scan_artica_databases(){
	
	$curl=new ccurl("http://www.artica.fr/catz/index.txt");
	if(!$curl->GetFile("/tmp/index.txt")){
		ufdbguard_admin_events("Fatal: unable to download blacklist index file $curl->error",__FUNCTION__,__FILE__,__LINE__,"update");
		echo "BLACKLISTS: Failed to retreive http://www.artica.fr/catz/index.txt ($curl->error)\n";
		return;
	}	
	$fIndex=unserialize(base64_decode(@file_get_contents("/tmp/index.txt")));
	
	//print_r($fIndex);
	$time=$fIndex["TIME"];
	
	
	$q=new mysql_squid_builder();
	//webfilters_databases_disk
	$prefix="INSERT IGNORE INTO webfilters_databases_disk (`filename`,`size`,`category`,`filtime`) VALUES ";
	$unix=new unix();
	$dirs=$unix->dirdir("/var/lib/ufdbartica");
	if($GLOBALS["VERBOSE"]){echo "Scanning ". count($dirs)." files last pattern was ". date("Y-m-d H:i:s",$time)."\n";}
	$sizz=0;
	while (list ($path, $path2) = each ($dirs) ){	
		$size=$unix->file_size("$path2/domains.ufdb");
		$category=basename($path);
		$sizz=$sizz+$size;
		if($GLOBALS["VERBOSE"]){echo "$category `$path2/domains.ufdb` = ".($size/1024)." Kb\n";}
		$category=$q->filaname_tocat("$path2/domains.ufdb");
		$filtime=filemtime("$path2/domains.ufdb");
		$f[]="('$path2/domains.ufdb','$size','$category','$filtime')";
	}
if($GLOBALS["VERBOSE"]){echo "scanned ". count($f)." files\n";}
	if(count($f)>0){
		$sql=$prefix.@implode(",", $f);
		if(!$q->TABLE_EXISTS("webfilters_databases_disk")){$q->CheckTables();}
		$q->QUERY_SQL("TRUNCATE TABLE webfilters_databases_disk");
		$q->CheckTables();
		$q->QUERY_SQL($sql);
		if(!$q->ok){ufdbguard_admin_events("$q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "update");}
	}
	ufdbguard_admin_events("Artica database store ". FormatBytes($sizz/1024)." databases in disk", __FUNCTION__, __FILE__, __LINE__, "update");
	
}



function updatev2(){
	$sock=new sockets();
	$unix=new unix();
	
	$timeFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	RegisterSupport();
	
	$t=time();
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."[".__LINE__."] starting...\n";}
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	if($DisableArticaProxyStatistics==1){
		ufdbguard_admin_events("Warning: DisableArticaProxyStatistics is enabled, aborting",__FUNCTION__,__FILE__,__LINE__,"update");
		die();
	}
	
	$unix=new unix();
	if(is_dir("/opt/articatech/data/catz")){
		if(!$GLOBALS["FORCE"]){	
			if($GLOBALS["CHECKTIME"]){
				$CHECKTIME=$unix->file_time_min($timeFile);
				if($CHECKTIME<2880){ufdbguard_admin_events("Warning: last update since {$CHECKTIME}Mn, require minimal 2880Mn (48H)",__FUNCTION__,__FILE__,__LINE__,"update");return;}
				@unlink($timeFile);
				@file_put_contents($timeFile, time());
			}
		}
	}
	
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,__FILE__)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($time<7200){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__."[".__LINE__."] Warning: Already running pid $pid\n";}
		if($GLOBALS["SCHEDULE_ID"]>0){ufdbguard_admin_events("Warning: Already running pid $pid since {$time}Mn",__FUNCTION__,__FILE__,__LINE__,"update");}
		return;
		}
		else{
			$kill=$unix->find_program("kill");
			shell_exec("$kill -9 $pid");
			if($GLOBALS["SCHEDULE_ID"]>0){ufdbguard_admin_events("Warning: Old task pid $pid since {$time}Mn wille be killed, (reach 7200mn)",__FUNCTION__,__FILE__,__LINE__,"update");}			
		}
	}
	
	
	@file_put_contents($pidfile, getmypid());	
	$LOCAL_VERSION=@file_get_contents("/opt/articatech/VERSION");
	$curl=new ccurl("http://www.artica.fr/ufdb/articatechdb.version");
	$curl->GetFile("/tmp/articatechdb.version");
	$array=unserialize(base64_decode(@file_get_contents("/tmp/articatechdb.version")));
	$REMOTE_VERSION=$array["ARTICATECH"]["VERSION"];
	$REMOTE_MD5=$array["ARTICATECH"]["MD5"];
	$REMOTE_SIZE=$array["ARTICATECH"]["SIZE"];
	
	if($GLOBALS["VERBOSE"]){echo "Local: $LOCAL_VERSION, remote $REMOTE_VERSION (".(($REMOTE_SIZE/1024)/1000)." MB)\n";}
	
	if($LOCAL_VERSION==$REMOTE_VERSION){
		if($GLOBALS["VERBOSE"]){echo "Noting to do : $LOCAL_VERSION\n";}
		ufdbtables(true); 
		schedulemaintenance();
		EXECUTE_BLACK_INSTANCE();
		return;		
	}
	
	@mkdir("/home/articadb",0755,true);
	$curl=new ccurl("http://www.artica.fr/ufdb/articadb.tar.gz");
	$curl->Timeout=7200;
	if(!$curl->GetFile("/home/articadb/articadb.tar.gz")){
		$took=$unix->distanceOfTimeInWords($t,time());
		ufdbguard_admin_events("Fatal : $curl->error after $took",__FUNCTION__,__FILE__,__LINE__,"update");
		@unlink("/home/articadb/articadb.tar.gz");
		ufdbtables(true); 
		schedulemaintenance();
		EXECUTE_BLACK_INSTANCE();
		return;		
	}
	
	$LOCAL_MD5=md5_file("/home/articadb/articadb.tar.gz");
	if($LOCAL_MD5<>$REMOTE_MD5){
		$took=$unix->distanceOfTimeInWords($t,time());
		ufdbguard_admin_events("Fatal : $LOCAL_MD5 <> $REMOTE_MD5, corrupted download after $took",__FUNCTION__,__FILE__,__LINE__,"update");
		@unlink("/home/articadb/articadb.tar.gz");
		ufdbtables(true); 
		schedulemaintenance();
		EXECUTE_BLACK_INSTANCE();
		return;				
	}
	
	$tar=$unix->find_program("tar");
	if($GLOBALS["VERBOSE"]){echo "uncompressing /home/articadb/articadb.tar.gz\n";}
	@mkdir("/opt/articatech");
	shell_exec("/etc/init.d/artica-postfix stop articadb");
	shell_exec("$tar -xf /home/articadb/articadb.tar.gz -C /opt/articatech/");
	@unlink("/home/articadb/articadb.tar.gz");
	if($GLOBALS["VERBOSE"]){echo "starting Articadb\n";}
	shell_exec("/etc/init.d/artica-postfix start articadb");
	
	
	$q=new mysql();
	if(!$q->DATABASE_EXISTS("catz")){
		ufdbguard_admin_events("Removing old database catz",__FUNCTION__,__FILE__,__LINE__,"update");
		$q->DELETE_DATABASE("catz");
	}
	$took=$unix->distanceOfTimeInWords($t,time());
	$REMOTE_SIZE=FormatBytes($REMOTE_SIZE/1024);
	ufdbguard_admin_events("New Artica Database statistics $REMOTE_VERSION ($REMOTE_SIZE) updated took:$took.",__FUNCTION__,__FILE__,__LINE__,"update");
	
}


function updatev2_checktables($npid=false){

}

function updatev2_checktables_repair(){
	
}

function updatev2_download($tablename){
	
	
}

function updatev2_inject($tablename){

}



function updatev2_currentdate(){

}



function schedulemaintenance(){
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__." in verbose mode\n";}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		WriteMyLogs("Warning: Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);
		return;
	}	
	
	if(!$GLOBALS["VERBOSE"]){
		$cachetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
		$time=$unix->file_time_min($cachetime);
		if($time<20){WriteMyLogs("$cachetime: {$time}Mn need 20Mn",__FUNCTION__,__FILE__,__LINE__);	return;}
		@unlink($cachetime);
		@file_put_contents($cachetime, time());
		
	}
	
	
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if($EnableRemoteStatisticsAppliance==1){writelogsBLKS("EnableRemoteStatisticsAppliance ACTIVE ,ABORTING TASK",__FUNCTION__,__FILE__,__LINE__);return;}	
	
	$t1=time();
	
	$q=new mysql_squid_builder();
	$badDomains["com"]=true;
	$badDomains["fr"]=true;
	$badDomains["de"]=true;
	$badDomains["nl"]=true;
	$badDomains["org"]=true;
	$badDomains["co"]=true;
	$badDomains["cz"]=true;
	$badDomains["de"]=true;
	$badDomains["net"]=true;
	$badDomains["us"]=true;
	$badDomains["name"]=true;
		
	$tables=$q->LIST_TABLES_CATEGORIES();
	while (list ($table,$none0) = each ($tables) ){
		if($table==null){continue;}
		reset($badDomains);
		while (list ($extensions,$none) = each ($badDomains) ){
		$q->QUERY_SQL("DELETE FROM $table WHERE pattern='$extensions'");	
		}
	}	
}

function EXECUTE_BLACK_INSTANCE(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");	
	WriteMyLogs("$nohup $php5 /usr/share/artica-postfix/exec.update.blacklist.instant.php >/dev/null 2>&1 &",__FUNCTION__,__FILE__,__LINE__);
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.update.blacklist.instant.php >/dev/null 2>&1 &");	
}






function categorize_delete(){
	if(!is_file("/tmp/categorize_delete.sql")){
	$curl=new ccurl("http://www.artica.fr/blacklist/categorize_delete.gz");
	if(!$curl->GetFile("/tmp/categorize_delete.gz")){
		ufdbguard_admin_events("Fatal: unable to download categorize_delete.gz file $curl->error",__FUNCTION__,__FILE__,__LINE__,"update");
		return;
	}

	if(!extractGZ("/tmp/categorize_delete.gz","/tmp/categorize_delete.sql")){
			ufdbguard_admin_events("Fatal: unable to extract /tmp/categorize_delete.gz",__FUNCTION__,__FILE__,__LINE__,"update");
			return;
		}
		
	}
	$q=new mysql_squid_builder();
	$datas=explode("\n",@file_get_contents("/tmp/categorize_delete.sql"));
	while (list ($index, $row) = each ($datas) ){
		if(trim($row)==null){continue;}
		$ligne=unserialize($row);
		$category=$ligne["category"];
		$pattern=$ligne["sitename"];
		$tablename="category_".$q->category_transform_name($category);
		if(!$q->TABLE_EXISTS($tablename)){$q->CreateCategoryTable($category);}
		$q->QUERY_SQL("UPDATE $tablename SET enabled=0 WHERE `pattern`='$pattern'");
		if(!$q->ok){
			echo $q->mysql_error."\n";
		}
	}
	
	ufdbguard_admin_events("Success updating deleted ". count($datas)." websites from categories",__FUNCTION__,__FILE__,__LINE__,"update");
	@unlink("/tmp/categorize_delete.sql");
}

function GetLastUpdateDate(){
	$q=new mysql();
	$sql="SELECT zDate FROM updates_categories WHERE categories='settings'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	return $ligne["zDate"];
}



function ifMustBeExecuted2(){
	$users=new usersMenus();
	$sock=new sockets();
	$update=true;
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$CategoriesRepositoryEnable=$sock->GET_INFO("CategoriesRepositoryEnable");
	if(!is_numeric($CategoriesRepositoryEnable)){$CategoriesRepositoryEnable=0;}
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($EnableWebProxyStatsAppliance==1){return true;}	
	if($CategoriesRepositoryEnable==1){return true;}
	if(!$users->SQUID_INSTALLED){$update=false;}
	return $update;
}


function CheckTargetFile($filename,$requiredsize){
	if(!is_file($filename)){return false;}
	$size=filesize($filename);
	if($size<>$requiredsize){return false;}
	return true;
}

function writelogsBLKS($text,$function,$file,$line){
	WriteMyLogs($text,$function,$file,$line);
	ufdbguard_admin_events($text,$function,basename(__FILE__),$line);
	}



function extractGZ($srcName, $dstName){
    $sfp = gzopen($srcName, "rb");
    $fp = fopen($dstName, "w");

    while ($string = gzread($sfp, 4096)) {
        fwrite($fp, $string, strlen($string));
    }
    gzclose($sfp);
    fclose($fp);
    $size=@filesize($dstName);
    if($size>0){
    	WriteMyLogs("TASK:{$GLOBALS["SCHEDULE_ID"]} -> extractGZ($srcName, $dstName) = $size bytes OK",__FUNCTION__,__FILE__,__LINE__);
    	return true;
    }
    WriteMyLogs("TASK:{$GLOBALS["SCHEDULE_ID"]} -> extractGZ($srcName, $dstName) = $size bytes FAILED",__FUNCTION__,__FILE__,__LINE__);
    return false;
}

function getSystemLoad(){
	$array_load=sys_getloadavg();
	return $array_load[0];
	
}



function CategoriesCountCache(){return;}

function export_table($tablename){
	if($GLOBALS["VERBOSE"]){echo "Exporting $tablename\n";}
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM $tablename";
	$results=$q->QUERY_SQL($sql);
	
	$fh = fopen("/tmp/$tablename.sql", 'w+');
	
	$c=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if($ligne["category"]==null){continue;}
			if($ligne["pattern"]==null){continue;}
			if($ligne["zmd5"]==null){continue;}
			$c++;
			$line="('{$ligne["zmd5"]}','{$ligne["zDate"]}','{$ligne["category"]}','{$ligne["pattern"]}','{$ligne["uuid"]}',1,1)";
			fwrite($fh, $line."\n");
		}
		
		echo "close /tmp/$tablename.sql $c rows\n";
		fwrite($fh, @implode(",",$f));
		fclose($fh);	
	
	
}

function export_all_tables(){
	$q=new mysql_squid_builder();
	$tables=$q->LIST_TABLES_CATEGORIES();
	while (list ($table, $row) = each ($tables) ){
		export_table($table);
	}
}

function merge_table($fromtable,$totable){
	
	$sock=new sockets();
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	if($DisableArticaProxyStatistics==1){die();}	
	
	$prefix="INSERT IGNORE INTO $totable (zmd5,zDate,category,pattern,uuid,sended,enabled) VALUES ";	
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM $fromtable";
	$results=$q->QUERY_SQL($sql);
	$c=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$line="('{$ligne["zmd5"]}','{$ligne["zDate"]}','{$ligne["category"]}','{$ligne["pattern"]}','{$ligne["uuid"]}',1,1)";
		$f[]=$line;
		if(count($f)>500){
			$c=$c+count($f);
			echo "Inserted $c elements\n";
			$sql="$prefix".@implode(",",$f);
			$f=array();
			$q->QUERY_SQL($sql);
			if(!$q->ok){echo $q->mysql_error."\n";return;}
		}
	}
	
		if(count($f)>0){
			$c=$c+count($f);
			echo "Inserted $c elements\n";
			$sql="$prefix".@implode(",",$f);
			$f=array();
			$q->QUERY_SQL($sql);
			if(!$q->ok){echo $q->mysql_error."\n";return;}
		}	
		
	echo "Finish\n";
	$sql="DROP TABLE $fromtable";
	$q->QUERY_SQL($sql);
	
}

function __GetMemory(){
	$mem=round(((memory_get_usage()/1024)/1000),2);
	return $mem;
}

function WriteMyLogs($text,$function,$file,$line){
	$GLOBALS["MAILLOG"][]=$line.") $text";
	$mem=__GetMemory();
	writelogs("Task:{$GLOBALS["SCHEDULE_ID"]}::$text",$function,__FILE__,$line);
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
	@fwrite($f, "$date [{$GLOBALS["MYPID"]}][{$mem}MB]: [$function::$line] $text\n");
	@fclose($f);
}



function ufdbguard_admin_memory($text,$function,$file,$line,$none){
	if($GLOBALS["VERBOSE"]){echo "$function:: $text in line: $line\n";}
	$GLOBALS["ufdbguard_admin_memory"][]="$function:: $text in line: $line";
	
}

function ufdbguard_admin_compile(){
	return @implode("\n", $GLOBALS["ufdbguard_admin_memory"]);
	$GLOBALS["ufdbguard_admin_memory"]=array();
}
				