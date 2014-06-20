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
$GLOBALS["NOTIME"]=false;
$GLOBALS["BYCRON"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["BYCRON"]=false;
$GLOBALS["NOCHECKTIME"]=false;
$GLOBALS["NOLOGS"]=false;
$GLOBALS["NOISO"]=false;
$GLOBALS["NODELETE"]=false;
$GLOBALS["MYPID"]=getmypid();
$GLOBALS["CMDLINE"]=@implode(" ", $argv);
if(preg_match("#--notime#",implode(" ",$argv))){$GLOBALS["NOTIME"]=true;}
if(preg_match("#--nodelete#",implode(" ",$argv))){$GLOBALS["NODELETE"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--checktime#",implode(" ",$argv))){$GLOBALS["CHECKTIME"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--nologs#",implode(" ",$argv))){$GLOBALS["NOLOGS"]=true;}
if(preg_match("#--noiso#",implode(" ",$argv))){$GLOBALS["NOISO"]=true;}
if(preg_match("#--bycron#",implode(" ",$argv))){$GLOBALS["BYCRON"]=true;$GLOBALS["CHECKTIME"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}

if($argv[1]=="--support"){RegisterSupport();exit;}
if($argv[1]=="--tests-pub"){tests_pub($argv[2]);exit;}

WriteMyLogs("Executed: {$GLOBALS["CMDLINE"]} task:{$GLOBALS["SCHEDULE_ID"]}",__FUNCTION__,__FILE__,__LINE__);
$sock=new sockets();
$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	
if($EnableRemoteStatisticsAppliance==1){
	writelogs("EnableRemoteStatisticsAppliance ACTIVE ,ABORTING TASK",__FUNCTION__,__FILE__,__LINE__);
	WriteMyLogs("EnableRemoteStatisticsAppliance ACTIVE ,ABORTING TASK",__FUNCTION__,__FILE__,__LINE__);
	die();
}

if(is_file("/etc/artica-postfix/PROXYTINY_APPLIANCE")){die();}
if($GLOBALS["FORCE"]){$GLOBALS["BYCRON"]=true;}

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
if($argv[1]=="--cicap"){C_ICAP_TABLES();die();}
if($argv[1]=="--ufdb-first"){ufdbFirst();die();}
if($argv[1]=="--scan-db"){scan_artica_databases();die();}
if($argv[1]=="--repair"){updatev2_checktables_repair();die();}
if($argv[1]=="--get-version"){updatev2_checkversion();die();}
if($argv[1]=="--adblock"){updatev2_adblock();die();}
if($argv[1]=="--cicap-dbs"){cicap_artica();die();}




updatev2();


function ufdbFirst(){
	if(!is_file("/etc/artica-postfix/ufdbfirst")){
		@file_put_contents("/etc/artica-postfix/ufdbfirst", time());
		ufdbtables();
		cicap_artica(true);
		C_ICAP_TABLES(true);
	}
}


function C_ICAP_TABLES($nopid=false){
	$unix=new unix();
	$sock=new sockets();
	@mkdir("/etc/artica-postfix/CICAP_DB",0755,true);
	$CACHE_FILE="/etc/artica-postfix/CICAP_DB/CICAP_ARTICA_VERSION.txt";
	$tmpdir=$unix->TEMP_DIR();
	$WORKDIR=$sock->GET_INFO("CicapDbPath");
	if($WORKDIR==null){ $WORKDIR="/home/c-icap/blacklists"; }
	@mkdir($WORKDIR,0755,true);
	
	if($GLOBALS["MIRROR"]==null){ updatev2_checkversion();}
	if($GLOBALS["MIRROR"]==null){ufdbguard_admin_events("Unable to find a suitable mirror...",__FUNCTION__,__FILE__,__LINE__,"cicap-artica");return;}
	$URIBASE=$GLOBALS["MIRROR"];
	$CURRENTVERSION=intval(@file_get_contents($CACHE_FILE));
	$CurrentTable=unserialize(@file_get_contents("/etc/artica-postfix/CICAP_DB/CICAP_ARTICA.txt"));
	
	$curl=new ccurl("$URIBASE/ufdb/CICAP_ARTICA_VERSION.txt");
	
	if(!$curl->GetFile("$tmpdir/CICAP_ARTICA_VERSION.txt")){
		squid_admin_mysql(0,"Unable to download blacklist index file $curl->error",null,__FUNCTION__,__LINE__);
		ufdbguard_admin_events("C-ICAP::Fatal: Unable to download blacklist index file $curl->error",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
		echo "UFDB: Failed to retreive $URIBASE/ufdb/CICAP_ARTICA_VERSION.txt ($curl->error)\n";
		return;
	}
	

	
	$NETXVERSION=intval(@file_get_contents("$tmpdir/CICAP_ARTICA_VERSION.txt"));
	
	if($GLOBALS["VERBOSE"]){echo "Current.....: $CURRENTVERSION\n";}
	if($GLOBALS["VERBOSE"]){echo "Next........: $NETXVERSION\n";}
	
	if(count($CurrentTable)>0){
		while (list ($tablename, $size) = each ($CurrentTable) ){
			if(!is_file("/home/artica/categories_databases/$tablename.db")){
				echo "/home/artica/categories_databases/$tablename.db ( no such database )\n";
				$CURRENTVERSION=0;
			}
		}
	}
	
	
	
	if($NETXVERSION==$CURRENTVERSION){if($GLOBALS["VERBOSE"]){echo "No changes...\n";} return; }
	if($NETXVERSION<$CURRENTVERSION){if($GLOBALS["VERBOSE"]){echo "Updated\n";} return; }
	
	$curl=new ccurl("$URIBASE/ufdb/CICAP_ARTICA.txt");
	if(!$curl->GetFile("$tmpdir/CICAP_ARTICA.txt")){
		if($GLOBALS["VERBOSE"]){echo "FAILED: $URIBASE/CICAP_ARTICA.txt $curl->error\n";}
		squid_admin_mysql(0,"Unable to download blacklist index file $curl->error",null,__FUNCTION__,__LINE__);
		ufdbguard_admin_events("C-ICAP::Fatal: Unable to download blacklist index file $curl->error",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
		echo "UFDB: Failed to retreive $URIBASE/CICAP_ARTICA.txt ($curl->error)\n";
		return;
	}	
	
	
	$tables=unserialize(base64_decode(@file_get_contents("$tmpdir/CICAP_ARTICA.txt")));
	if($GLOBALS["VERBOSE"]){echo "LOADING $tmpdir/CICAP_ARTICA.txt ". count($tables)." tables.\n";}
	$PATHS=array();
	while (list ($tablename, $size) = each ($tables) ){
		$CurrentSize=intval($CurrentTable[$tablename]);
		if(!is_file("$WORKDIR/$tablename/domains.db")){$CurrentSize=0;}
		
		
		@mkdir("$WORKDIR/$tablename",0755,true);
		
		if($GLOBALS["VERBOSE"]){echo "$tablename Next size = $size, Current = $CurrentSize\n";}
		if($CurrentSize==$size){continue;}
		if($GLOBALS["VERBOSE"]){echo "Downloading $tablename\n";}
		
		if(!compile_cicap_download($tablename)){continue;}
		
		$PATHS["$WORKDIR/$tablename"]="$WORKDIR/$tablename";
				

		
	}
	
	$size=$GLOBALS["DOWNLOADS"]/1024;
	
	ufdbguard_admin_events( "New ".count($PATHS)." C-ICAP webfilter databases downloaded v$NETXVERSION ".FormatBytes($size), __FUNCTION__,__FILE__,__LINE__);
	@file_put_contents($CACHE_FILE, $NETXVERSION);
	@file_put_contents("/etc/artica-postfix/CICAP_DB/CICAP_ARTICA.txt", serialize($tables));
	
	
}

function cicap_artica($aspid=false){
	$unix=new unix();
	$GLOBALS["EVENTS"]=array();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pids";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pid=@file_get_contents($pidfile);
	echo "aspid = $aspid\n";
	echo "pidTime = $pidTime\n";
	
	if(!$aspid){
		echo "PID = $pid\n";
		if($unix->process_exists($pid,__FILE__)){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($time<240){
				if($GLOBALS["VERBOSE"]){echo __FUNCTION__."[".__LINE__."] Warning: Already running pid $pid since {$time}Mn\n";}
				if($GLOBALS["SCHEDULE_ID"]>0){ufdbguard_admin_events("Warning: Already running pid $pid since {$time}Mn",__FUNCTION__,__FILE__,__LINE__,"catz");}
				return;
			}
			else{
				$kill=$unix->find_program("kill");
				unix_system_kill_force($pid);
				if($GLOBALS["SCHEDULE_ID"]>0){ufdbguard_admin_events("Warning: Old task pid $pid since {$time}Mn wille be killed, (reach 7200mn)",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");}
			}
		}
		
		if(!$GLOBALS["NOTIME"]){
			$Time=$unix->file_time_min($pidTime);
			if($Time<240){return;}
		}
		
	}
	
	$pids=$unix->PIDOF_PATTERN_ALL(basename(__FILE__).".*?--cicap-dbs");
	if(count($pids)>1){
		if(count($pids)>2){ $kill=$unix->find_program("kill"); 
		shell_exec("$kill -9 ".implode(" ", $pids)); }
		echo "Processes ".implode(" ", $pids)." already exists...\n";
		artica_update_event(1, "Processes ".implode(" ", $pids)." already exists...", null,__FILE__,__LINE__);
		return;
	}
	
	
	@unlink($pidTime);
	@file_put_contents($pidfile, getmypid());
	@file_put_contents($pidTime, time());
	
	
	
	
	$sock=new sockets();
	$GLOBALS["DOWNLOADS"]=0;
	if($GLOBALS["MIRROR"]==null){ updatev2_checkversion();}
	if($GLOBALS["MIRROR"]==null){
		updatev2_progress(100,"Unable to find a suitable mirror");
		artica_update_event(0, "Unable to find a suitable mirror...", null,__FILE__,__LINE__);
		return;
	}
	
	
	$URIBASE=$GLOBALS["MIRROR"];
	$WORKDIR="/home/artica/categories_databases";
	@mkdir($WORKDIR,0755,true);
	@chmod($WORKDIR, 0755);
	$tmpdir=$unix->TEMP_DIR();	
	$CATZ_ARRAY=unserialize(@file_get_contents("$WORKDIR/CATZ_ARRAY"));
	$myVersion=intval($CATZ_ARRAY["TIME"]);
	updatev2_progress(10,"Downloading index file");
	echo "Downloading index file\n";
	
	$curl=new ccurl("$URIBASE/ufdb/CATZ_ARRAY");
	$curl->WriteProgress=true;
	$curl->ProgressFile="/usr/share/artica-postfix/ressources/logs/web/cache/articatechdb.download";
	if(is_file("$tmpdir/CATZ_ARRAY")){@unlink("$tmpdir/CATZ_ARRAY");}
	if(!$curl->GetFile("$tmpdir/CATZ_ARRAY")){
		updatev2_progress(100,"Failed $curl->error");
		artica_update_event(0, "Failed Downloading $URIBASE/ufdb/CATZ_ARRAY", null,__FILE__,__LINE__);
		ufdbguard_admin_events( "Failed Downloading $URIBASE/ufdb/CATZ_ARRAY", __FUNCTION__,__FILE__,__LINE__);
		if($GLOBALS["VERBOSE"]){echo "Failed Downloading $URIBASE/ufdb/CATZ_ARRAY\n";}
		return false;
	}
	$NEW_CATZ_ARRAY=unserialize(base64_decode(@file_get_contents("$tmpdir/CATZ_ARRAY")));
	
	$curl=new ccurl("$URIBASE/ufdb/CATZ_COUNT");
	$curl->WriteProgress=true;
	$curl->ProgressFile="/usr/share/artica-postfix/ressources/logs/web/cache/articatechdb.download";
	if(is_file("$tmpdir/CATZ_COUNT")){@unlink("$tmpdir/CATZ_COUNT");}
	$curl->GetFile("$tmpdir/CATZ_COUNT");
	
	
	
	@unlink("$tmpdir/CATZ_ARRAY");
	$Remote_version=$NEW_CATZ_ARRAY["TIME"];
	echo "Current............: $myVersion\n";
	echo "Available..........: $Remote_version\n";
	if(!is_array($NEW_CATZ_ARRAY)){return;}
	$CountDeTables=count($NEW_CATZ_ARRAY);
	$DBS=0;
	$z=0;
	$cA=0;
	$ERRORDB=0;
	while (list ($tablename, $items) = each ($NEW_CATZ_ARRAY) ){
		if($tablename=="TIME"){continue;}
		$z++;
		$purc=$z/$CountDeTables;
		$purc=$purc*100;
		$purc=round($purc);
		if($GLOBALS["VERBOSE"]){echo "{$purc}%\n";}
		updatev2_progress($purc,"Downloading $tablename");
		$Items=intval($NEW_CATZ_ARRAY[$tablename]);
		$OldItems=intval($CATZ_ARRAY[$tablename]);
		echo "$tablename Available............: $Items\n";
		echo "$tablename Current..............: $OldItems\n";
		if(!is_file("/home/artica/categories_databases/$tablename.db")){
			if($GLOBALS["VERBOSE"]){echo "/home/artica/categories_databases/$tablename.db no such file...\n";}
			$OldItems=0;
		}
		if($Items==$OldItems){
			$cA++;
			if($GLOBALS["VERBOSE"]){echo "{$purc}%\n";}
			continue;}
		
		$curl=new ccurl("$URIBASE/ufdb/$tablename.artica.db.gz");
		$curl->WriteProgress=true;
		$curl->ProgressFile="/usr/share/artica-postfix/ressources/logs/web/cache/articatechdb.download";
		$STATUS["LAST_DOWNLOAD"]["TIME"]=time();
		
		if(is_file("$tmpdir/$tablename.artica.db.gz")){@unlink("$tmpdir/$tablename.artica.db.gz");}
		if(!$curl->GetFile("$tmpdir/$tablename.artica.db.gz")){
			$ERRORDB++;
			$GLOBALS["EVENTS"][]="Failed Downloading $tablename with error $curl->error";
			if($GLOBALS["VERBOSE"]){echo "Failed Downloading $tablename $curl->error !!!!\n";}
			
			unset($NEW_CATZ_ARRAY[$tablename]);
			@unlink("$tmpdir/$tablename.artica.db.gz");
			continue;
		}
		$GLOBALS["DOWNLOADS"]=$GLOBALS["DOWNLOADS"]+@filesize("$tmpdir/$tablename.artica.db.gz");
		
		$STATUS["LAST_DOWNLOAD"]["CATEGORY"]=$tablename;
		$STATUS["LAST_DOWNLOAD"]["SIZE"]=($GLOBALS["DOWNLOADS"]/1024);
		
		
		
		if($GLOBALS["VERBOSE"]){echo "Uncompress $tmpdir/$tablename.artica.db.gz\n";}
		if(is_file("/home/artica/categories_databases/$tablename.db")){@unlink("/home/artica/categories_databases/$tablename.db");}
	
		if($GLOBALS["VERBOSE"]){echo "extract $tmpdir/$tablename.artica.db.gz to /home/artica/categories_databases/$tablename.db\n";}
		$unix->uncompress("$tmpdir/$tablename.artica.db.gz", "/home/artica/categories_databases/$tablename.db");
		@unlink("$tmpdir/$tablename.artica.db.gz");
		
		if(!is_file("/home/artica/categories_databases/$tablename.db")){
			$ERRORDB++;
			$GLOBALS["EVENTS"][]="Fatal suspected failed task uncompressing $tablename.artica.db.gz";
			continue;
		}
		
		@chmod("/home/artica/categories_databases/$tablename.db", 0755);
		$tablename_size=@filesize("/home/artica/categories_databases/$tablename.db");
		$tablename_size=$tablename_size/1024;
		$id = dba_open("/home/artica/categories_databases/$tablename.db", "r","db4");
		if(!$id){
			$ERRORDB++;
			$GLOBALS["EVENTS"][]="dba_open(): $tablename.db ( $tablename_size KB) failed";
			if($GLOBALS["VERBOSE"]){echo "dba_open();/home/artica/categories_databases/$tablename.db failed...\n";}
			dba_close($id);
			@unlink("/home/artica/categories_databases/$tablename.db");
			unset($NEW_CATZ_ARRAY[$tablename]);
			continue;
		}
		$GLOBALS["EVENTS"][]="SUCCESS $tablename ( $tablename_size KB )";
		if($GLOBALS["VERBOSE"]){echo " **** SUCCESS $tablename *****\n";}
		$CATZ_ARRAY[$tablename]=$NEW_CATZ_ARRAY[$tablename];
		@file_put_contents("$WORKDIR/CATZ_ARRAY", serialize($CATZ_ARRAY));
		if($GLOBALS["VERBOSE"]){echo " **** $WORKDIR/CATZ_ARRAY done *****\n";}
		dba_close($id);
		$DBS++;
		$STATUS["LAST_DOWNLOAD"]["FAILED"]=$ERRORDB;
		@file_put_contents("/etc/artica-postfix/ARTICAUFDB_LAST_DOWNLOAD", serialize($STATUS));
	}
			
	
	
	
	$size=$GLOBALS["DOWNLOADS"]/1024;
	@unlink("/etc/artica-postfix/CATZ_COUNT");
	@copy("$tmpdir/CATZ_COUNT","/etc/artica-postfix/CATZ_COUNT");
	@unlink("$tmpdir/CATZ_COUNT");

	
	if($ERRORDB>0){
		if($DBS==0){
			artica_update_event(1, "$ERRORDB Error(s) while downloading Categories databases", @implode("\n", $GLOBALS["EVENTS"]),__FILE__,__LINE__);
			squid_admin_mysql(1, "$ERRORDB Error(s) while downloading Categories databases", @implode("\n", $GLOBALS["EVENTS"]),__FILE__,__LINE__);
			ufdbguard_admin_events( "$ERRORDB Error(s) while downloading Categories databases\n".FormatBytes($size)."\n".@implode("\n", $GLOBALS["EVENTS"]), __FUNCTION__,__FILE__,__LINE__);
			updatev2_progress(110,"$ERRORDB Errors while downloading Categories databases");
		}
	}
	
	if($DBS>0){
		$Minsize=FormatBytes($size);
		artica_update_event(2, "New $DBS Categorie(s) databases downloaded ($Minsize) v$Remote_version", @implode("\n", $GLOBALS["EVENTS"]),__FILE__,__LINE__);
		ufdbguard_admin_events( "New $DBS Categorie(s) databases downloaded ($Minsize) v$Remote_version\n".@implode("\n", $GLOBALS["EVENTS"]), __FUNCTION__,__FILE__,__LINE__);
		updatev2_progress(100,"New $DBS Categorie(s) databases downloaded ($Minsize) v$Remote_version");
	}
	
}



function compile_cicap_download_v2($tablename){
	$URIBASE=$GLOBALS["MIRROR"];
	$tmpdir=$GLOBALS["CICAP_TMPDIR"];
	$curl=new ccurl("$URIBASE{$GLOBALS["CICAP_SUFFIX_URI"]}/$tablename.db.gz");
	if(is_file("$tmpdir/$tablename.db.gz")){@unlink("$tmpdir/$tablename.db.gz");}
	if($curl->GetFile("$tmpdir/$tablename.db.gz")){return true;}
	if($GLOBALS["CICAP_SUFFIX_URI"]<>null){return false;}
	$GLOBALS["CICAP_SUFFIX_URI"]="/ufdb";
	if($GLOBALS["VERBOSE"]){echo "Trying with suffix /ufdb...\n";}
	return compile_cicap_download_v2($tablename);
	
	
}

function compile_cicap_download($tablename){
	$unix=new unix();
	$sock=new sockets();
	if($GLOBALS["MIRROR"]==null){ updatev2_checkversion();}
	if($GLOBALS["MIRROR"]==null){ufdbguard_admin_events("Unable to find a suitable mirror...",__FUNCTION__,__FILE__,__LINE__,"cicap-artica");return;}
	$URIBASE=$GLOBALS["MIRROR"];
	if(!isset($GLOBALS["CICAP_WORKDIR"])){$GLOBALS["CICAP_WORKDIR"]=$sock->GET_INFO("CicapDbPath");}
	if($GLOBALS["CICAP_WORKDIR"]==null){$GLOBALS["CICAP_WORKDIR"]="/home/c-icap/blacklists";}
	if(!isset($GLOBALS["CICAP_SUFFIX_URI"])){$GLOBALS["CICAP_SUFFIX_URI"]=null;}
	
	$WORKDIR=$GLOBALS["CICAP_WORKDIR"];
	@mkdir("/home/artica/categories_databases",0755,true);
	@mkdir("$WORKDIR/$tablename",0755,true);
	if(!isset($GLOBALS["CICAP_TMPDIR"])){$GLOBALS["CICAP_TMPDIR"]=$unix->TEMP_DIR();}
	$tmpdir=$GLOBALS["CICAP_TMPDIR"];
	
	if(!compile_cicap_download_v2($tablename)){
		if($GLOBALS["VERBOSE"]){echo "Failed Downloading compile_cicap_download_v2($tablename)\n";}
		return false;
	}
		
	$GLOBALS["DOWNLOADS"]=$GLOBALS["DOWNLOADS"]+@filesize("$tmpdir/$tablename.db.gz");
		
	if($GLOBALS["VERBOSE"]){echo "Uncompress $tmpdir/$tablename.db.gz -> $WORKDIR/$tablename/domains.db\n";}
	$unix->uncompress("$tmpdir/$tablename.db.gz", "$WORKDIR/$tablename/domains.db");
	@unlink("$tmpdir/$tablename.db.gz");
	if(!is_file("$WORKDIR/$tablename/domains.db")){
		if($GLOBALS["VERBOSE"]){echo "Failed Uncompressing $tablename.db.gz\n";}
		return false;
	}
	
	$curl=new ccurl("$URIBASE{$GLOBALS["CICAP_SUFFIX_URI"]}/$tablename.url.gz");
	if($curl->GetFile("$tmpdir/$tablename.url.gz")){ 
		$GLOBALS["DOWNLOADS"]=$GLOBALS["DOWNLOADS"]+@filesize("$tmpdir/$tablename.url.db.gz");
		if($GLOBALS["VERBOSE"]){echo "Uncompress $tmpdir/$tablename.url.gz\n";}
		$unix->uncompress("$tmpdir/$tablename.url.gz", "$WORKDIR/$tablename/urls.db");
		@unlink("$tmpdir/$tablename.url.gz");
	}
	
	return true;
	
}

function ufdbtables($nopid=false){
	$unix=new unix();
	$sock=new sockets();
	$GLOBALS["EVENTS"]=array();
	$CACHE_FILE="/etc/artica-postfix/ufdb.tables.db";
	
	if($GLOBALS["VERBOSE"]){echo "CACHE_FILE = $CACHE_FILE\n";}
	
	if($sock->EnableUfdbGuard()==0){return;}
	$UseRemoteUfdbguardService=$sock->GET_INFO('UseRemoteUfdbguardService');
	if(!is_numeric($UseRemoteUfdbguardService)){$UseRemoteUfdbguardService=0;}
	if($UseRemoteUfdbguardService==1){return;}
	if($GLOBALS["MIRROR"]==null){ updatev2_checkversion();}
	$tmpdir=$unix->TEMP_DIR();
	if($GLOBALS["MIRROR"]==null){ufdbguard_admin_events("UFDB::Warning: Unable to find a suitable mirror",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica"); return; }
	$URIBASE=$GLOBALS["MIRROR"];
	$WORKDIR="/var/lib/ufdbartica";
	if(is_link($WORKDIR)){$WORKDIR=readlink($WORKDIR);}
	
	if(@file_get_contents("/usr/local/share/artica/.lic")<>"TRUE"){
		updatev2_progress2(0,"{license_error}");
		if(!$GLOBALS["NOLOGS"]){
			ufdbguard_admin_events("UFDB::Warning: only corporate license is allowed to be updated...",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
		}
		return;
	}
	
	$CategoriesDatabasesByCron=$sock->GET_INFO("CategoriesDatabaseByCron");
	if(!is_numeric($CategoriesDatabasesByCron)){$CategoriesDatabasesByCron=0;}
	
	if(!$GLOBALS["FORCE"]){
		if($CategoriesDatabasesByCron==1){
			if(!$GLOBALS["BYCRON"]){return;}
		}
	}
	
	if(!$nopid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$nohup=$unix->find_program("nohup");
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid,__FILE__)){
			$timepid=$unix->PROCCESS_TIME_MIN($pid);
			if(!$GLOBALS["NOLOGS"]){
				ufdbguard_admin_events("UFDB::Warning: Task already executed PID: $pid since {$timepid}Mn",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
				return;
			}
		}		
		@file_put_contents($pidfile, getmypid());
	}
	
	
	$curl=new ccurl("$URIBASE/index.txt");
	if(!$curl->GetHeads()){
		if($GLOBALS["VERBOSE"]){echo "Fatal ! $URIBASE/index.txt ERROR NUMBER $curl->CURLINFO_HTTP_CODE\n";}
		if( ($curl->CURLINFO_HTTP_CODE==404 ) OR ($curl->CURLINFO_HTTP_CODE==300 )){
			if(!preg_match("#\/ufdb#", $URIBASE)){$URIBASE="$URIBASE/ufdb";}
			$curl=new ccurl("$URIBASE/index.txt");
			if(!$curl->GetHeads()){
				$GLOBALS["EVENTS"][]="$URIBASE/index.txt";
				$GLOBALS["EVENTS"][]="Failed with error $curl->error";
				while (list ($a, $b) = each ($GLOBALS["CURLDEBUG"]) ){$GLOBALS["EVENTS"][]=$b;}
				squid_admin_mysql(0,"Unable to download blacklist index file `$curl->error`",@implode("\n", $GLOBALS["EVENTS"]),__FUNCTION__,__LINE__);
				artica_update_event(0,"Unable to download Artica blacklist index file `$curl->error`",@implode("\n", $GLOBALS["EVENTS"]),__FUNCTION__,__LINE__);
				ufdbguard_admin_events("UFDB::Fatal: Unable to download blacklist index file $curl->error",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
				echo "UFDB: Failed to retreive $URIBASE/index.txt ($curl->error)\n";
				updatev2_adblock();
				return;				
			}
		}
	}
	
	
	$source_filetime=$curl->CURL_ALL_INFOS["filetime"];
	if($GLOBALS["VERBOSE"]){echo "$URIBASE/index.txt filetime: $source_filetime ". date("Y-m-d H:i:s",$source_filetime)."\n";}
	$GLOBALS["EVENTS"][]="$URIBASE/index.txt";
	$GLOBALS["EVENTS"][]="filetime: $source_filetime ". date("Y-m-d H:i:s",$source_filetime);
	$UFDBGUARD_LAST_INDEX_TIME="/etc/artica-postfix/UFDBGUARD_LAST_INDEX_TIME";
	
	$old_time=intval(@file_get_contents("$UFDBGUARD_LAST_INDEX_TIME"));
	$GLOBALS["EVENTS"][]="Old filetime: $old_time ". date("Y-m-d H:i:s",$old_time);
	
	if($source_filetime==$old_time){
		$GLOBALS["EVENTS"][]="No new updates";
		return true;
	}
	if($source_filetime<$old_time){
		$GLOBALS["EVENTS"][]="No new updates";
		return true;
	}	
		
	
	$curl=new ccurl("$URIBASE/index.txt");
	if(!$curl->GetFile("/etc/artica-postfix/artica-webfilter-db-index.txt")){
		$GLOBALS["EVENTS"][]="$URIBASE/index.txt";
		$GLOBALS["EVENTS"][]="Failed with error $curl->error";
		while (list ($a, $b) = each ($GLOBALS["CURLDEBUG"]) ){$GLOBALS["EVENTS"][]=$b;}
		squid_admin_mysql(0,"Unable to download blacklist index file `$curl->error`",@implode("\n", $GLOBALS["EVENTS"]),__FUNCTION__,__LINE__);
		artica_update_event(0,"Unable to download Artica blacklist index file `$curl->error`",@implode("\n", $GLOBALS["EVENTS"]),__FUNCTION__,__LINE__);
		ufdbguard_admin_events("UFDB::Fatal: Unable to download blacklist index file $curl->error",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
		echo "UFDB: Failed to retreive $URIBASE/index.txt ($curl->error)\n";
		updatev2_progress2(100,"Unable to download blacklist index file");
		updatev2_adblock();
		return;
	}

	
	$LOCAL_CACHE=unserialize(base64_decode(@file_get_contents($CACHE_FILE)));
	$REMOTE_CACHE=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/artica-webfilter-db-index.txt")));
	
	
	$MAx=count($REMOTE_CACHE);
	$BigSize=0;
	$c=0;
	while (list ($tablename, $size) = each ($REMOTE_CACHE) ){	
		if($size<>$LOCAL_CACHE[$tablename]){
			$c++;
			
			$OriginalSize=$size;

			echo "UFDB: downloading $tablename remote size:$size, local size:{$LOCAL_CACHE[$tablename]}\n";
			$GLOBALS["EVENTS"][]="downloading $tablename remote size:$size, local size:{$LOCAL_CACHE[$tablename]}";
			$curl=new ccurl("$URIBASE/$tablename.gz");
			$curl->Timeout=380;
			if(!$curl->GetFile("$tmpdir/$tablename.gz")){
				squid_admin_mysql(1,"Unable to download blacklist $tablename.gz file $curl->error",@implode("\n", $GLOBALS["EVENTS"]),__FUNCTION__,__LINE__);
				ufdbguard_admin_events("UFDB::Fatal: unable to download blacklist $tablename.gz file $curl->error\n".@implode("\n", $GLOBALS["EVENTS"]),__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
				continue;
			}
			$prc=($c/$MAx)*100;
			updatev2_progress2($prc,"$tablename ok");
			$GLOBALS["UFDB_SIZE"]=$GLOBALS["UFDB_SIZE"]+@filesize("$tmpdir/$tablename.gz");
			
			@mkdir("$WORKDIR/$tablename",0755,true);
			if(!ufdbtables_uncompress("$tmpdir/$tablename.gz","$WORKDIR/$tablename/domains.ufdb")){
				squid_admin_mysql(0,"Unable to extract blacklist $tablename.gz",null,__FUNCTION__,__LINE__);
				artica_update_event(0,"Unable to extract blacklist $tablename.gz",null,__FUNCTION__,__LINE__);
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
			$GLOBALS["EVENTS"][]="Success updating category `$tablename` with $size Ko";
						
		}
		
	}
	
	@file_put_contents($CACHE_FILE, base64_encode(serialize($LOCAL_CACHE)));
	updatev2_progress2(100,"DONE ok");
	$ufdbguard_admin_memory=@implode("\n", $GLOBALS["ufdbguard_admin_memory"]);	
	if($c>0){
		$BigSizeMB=round($BigSize/1024,2);
		squid_admin_mysql(2, "Artica Web filtering Databases Success updated $c categories {$BigSizeMB}MB extracted on disk","$ufdbguard_admin_memory".@implode("\n", $GLOBALS["EVENTS"]),__FUNCTION__,__LINE__);
		artica_update_event(2, "Artica Web filtering Databases Success updated $c categories {$BigSizeMB}MB extracted on disk","$ufdbguard_admin_memory".@implode("\n", $GLOBALS["EVENTS"]),__FUNCTION__,__LINE__);
		ufdbguard_admin_events("UFDB::Success update $c categories $BigSize extracted\n$ufdbguard_admin_memory",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
		@file_put_contents($UFDBGUARD_LAST_INDEX_TIME, $source_filetime);
		
	}else{
		if($GLOBALS["FORCE"]){
			echo "No update available\n$ufdbguard_admin_memory\n";
		}
	}
	
		
		
	@chown("$WORKDIR", "squid");
	@chgrp("$WORKDIR", "squid");
	updatev2_adblock();
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
	
	$unix=new unix();
	$sock=new sockets();
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	if(is_file("/etc/artica-postfix/PROXYTINY_APPLIANCE")){
		$DisableArticaProxyStatistics=1;
		$sock->SET_INFO("DisableArticaProxyStatistics",1);
		die();
	}	
	if($DisableArticaProxyStatistics==1){die();}	
	$URIBASE=$unix->MAIN_URI();
	$tmpdir=$unix->TEMP_DIR();
	$curl=new ccurl("$URIBASE/catz/index.txt");
	if(!$curl->GetFile("$tmpdir/index.txt")){
		ufdbguard_admin_events("Fatal: unable to download blacklist index file $curl->error",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
		echo "BLACKLISTS: Failed to retreive $URIBASE/catz/index.txt ($curl->error)\n";
		return;
	}

	$f=unserialize(base64_decode(@file_get_contents("$tmpdir/index.txt")));
	if(!is_array($f)){ufdbguard_admin_events("Fatal: index file, no such array",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");return;}	
	return $f;
	
	
}

function RegisterSupport(){

}

function scan_artica_databases(){
	$unix=new unix();
	$URIBASE=$unix->MAIN_URI();	
	$tmpdir=$unix->TEMP_DIR();
	$curl=new ccurl("$URIBASE/catz/index.txt");
	if(!$curl->GetFile("$tmpdir/index.txt")){
		ufdbguard_admin_events("Fatal: unable to download blacklist index file $curl->error",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
		echo "BLACKLISTS: Failed to retreive $URIBASE/catz/index.txt ($curl->error)\n";
		return;
	}	
	$fIndex=unserialize(base64_decode(@file_get_contents("$tmpdir/index.txt")));
	
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
		if(!$q->ok){ufdbguard_admin_events("$q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "ufbd-artica");}
	}
	
	
}



function updatev2_checkversion(){
	$GLOBALS["MIRROR"]=null;
	$unix=new unix();
	$tmpdir=$unix->TEMP_DIR();
	$tmpfile="/$tmpdir/articatechdb.version.".time();
	$unix=new unix();
	$URIBASE=$unix->MAIN_URI();
	$sock=new sockets();
	$ArticaDbReplicate=$sock->GET_INFO("ArticaDbReplicate");
	if(!is_numeric($ArticaDbReplicate)){$ArticaDbReplicate=0;}
	
	$MirrorsA[]="http://s497977761.onlinehome.fr";
	$MirrorsA[]="http://artica.fr";
	$MirrorsA[]="http://93.88.245.88";
	$MirrorsA[]="$URIBASE/ufdb";
	
	if($ArticaDbReplicate==0){
		shuffle($MirrorsA);
		while (list ($num, $uri) = each ($MirrorsA) ){
			$Mirrors[]=$uri;
		}

	}else{
		$Mirrors[]="$URIBASE/ufdb";
	}
	
	$destinationfile="/usr/share/artica-postfix/ressources/logs/web/cache/CATZ_ARRAY";
	@mkdir("/usr/share/artica-postfix/ressources/logs/web/cache",0755);
	
	$unix=new unix();
	
	
	
	while (list ($num, $uri) = each ($Mirrors) ){
		$GLOBALS["EVENTS"][]="Checking Repository: $uri";
		events("Try $uri");
		$NewUri="$uri/ufdb/CATZ_ARRAY";
		$curl=new ccurl($NewUri,false,null,true);
		$curl->Timeout=10;
		if(!$curl->GetFile($tmpfile)){
			$GLOBALS["EVENTS"][]="$uri: Failed with error $curl->error";
			continue;
		}
		if(!is_file($tmpfile)){
			$GLOBALS["EVENTS"][]="$uri: Failed with error No such file";
			continue;
		}
		$array=unserialize(base64_decode(@file_get_contents($tmpfile)));
		if(!is_array($array)){
			$GLOBALS["EVENTS"][]="$uri: Failed with error No Array";
			continue;
		}
		if(!isset($array["TIME"])){
			$GLOBALS["EVENTS"][]="$uri: Failed with error No TIME CODE";
			continue;
		}
		$xdate=date("l d F Y H:i:s",$array["TIME"]);
		$GLOBALS["EVENTS"][]="$uri: Available version: $xdate - {$array["TIME"]}/". date("Y-m-d H:i:s",$array["TIME"]);
		echo "[".__LINE__."]: Available version: $xdate - {$array["TIME"]}/". date("Y-m-d H:i:s",$array["TIME"])."\n";
		$GLOBALS["MIRROR"]=$uri;
		@unlink($destinationfile);
		@copy($tmpfile, $destinationfile);
		@chmod($destinationfile,0755);
		break;
					
		}
	
	
	@unlink($tmpfile);
	
	if($GLOBALS["MIRROR"]==null){
		events("error, unable to find a suitable mirror");
		ufdbguard_admin_events("Error, unable to find a suitable mirror\n".@implode("\n", $GLOBALS["EVENTS"]), __FUNCTION__, __FILE__, __LINE__, "ufbd-artica");
		return null;
	}

	if($ArticaDbReplicate){
		@file_put_contents($tmpfile, "/home/articatechdb.version");
	}
	
}

function updatev2_progress($num,$text){
	$array["POURC"]=$num;
	$array["TEXT"]=$text." ".date("Y-m-d H:i:s");
	if($GLOBALS["VERBOSE"]){echo "{$num}% $text\n";}
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/cache/articatechdb.progress", serialize($array));
}
function updatev2_progress2($num,$text){
	$array["POURC"]=$num;
	$array["TEXT"]=$text." ".date("Y-m-d H:i:s");
	if($GLOBALS["VERBOSE"]){echo "{$num}% $text\n";}
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/cache/webfilter-artica.progress", serialize($array));
}
function tests_pub($pattern){
	$main_artica_path="/var/lib/ufdbartica";
	$pubfinal="$main_artica_path/category_publicite/expressions";
	$f=explode("\n",@file_get_contents($pubfinal));
	while (list ($category_table, $num) = each ($f) ){
		if(preg_match("#$num#", $pattern)){
			echo $num." matches\n";
			break;
		}
		
	}
	
}

function updatev2_adblock(){
	return;
	if(isset($GLOBALS[__FUNCTION__])){return;}
	$GLOBALS[__FUNCTION__]=true;
	$timeFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$main_artica_path="/var/lib/ufdbartica";
	$unix=new unix();
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){
		if($GLOBALS["VERBOSE"]){echo "License error...\n";}
		return;
	}
	
	if(!$GLOBALS["FORCE"]){
		$TimeMn=$unix->file_time_min($timeFile);
		if($TimeMn<60){
			if($GLOBALS["VERBOSE"]){echo "{$TimeMn}Mn require 60mn minimal (use --force if necessary)\n";}
			return;
		}
	}
	
	@unlink($timeFile);
	@file_put_contents($timeFile, time());
	
	updatev2_checkversion();
	$reload=false;
	
	$trackergzip="$main_artica_path/category_tracker/tracker_expressions.gz";
	$trackerfinal="$main_artica_path/category_tracker/expressions";
	
	$malwaregzip="$main_artica_path/category_malware/categoryuris_malware.gz";
	$malwarecsv="$main_artica_path/category_malware/categoryuris_malware.csv";
	
	
	$pubgzip="$main_artica_path/category_publicite/publicite_expressions.gz";
	$pubfinal="$main_artica_path/category_publicite/expressions";
	
	$phishgzip="$main_artica_path/category_phishing/categoryuris_phishing.gz";
	$phishcsv="$main_artica_path/category_phishing/categoryuris_phishing.csv";
	
	if($GLOBALS["MIRROR"]==null){return;}

	@unlink("$pubgzip");
	$curl=new ccurl("{$GLOBALS["MIRROR"]}/publicite_expressions.gz");
	if(!$curl->GetFile($pubgzip)){
		if($GLOBALS["VERBOSE"]){echo "$pubgzip failed to download $curl->error\n";}
		ufdbguard_admin_events("UFDB::Fatal: $pubgzip failed to download $curl->error",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
		@unlink("$pubgzip");
		
	}
	
	@unlink($trackergzip);
	$curl=new ccurl("{$GLOBALS["MIRROR"]}/tracker_expressions.gz");
	if(!$curl->GetFile($trackergzip)){
		if($GLOBALS["VERBOSE"]){echo "$trackergzip failed to download $curl->error\n";}
		ufdbguard_admin_events("UFDB::Fatal: $trackergzip failed to download $curl->error",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
		@unlink($trackergzip);
	}

	@unlink($malwaregzip);
	$curl=new ccurl("{$GLOBALS["MIRROR"]}/categoryuris_malware.gz");
	if(!$curl->GetFile($malwaregzip)){
		ufdbguard_admin_events("UFDB::Fatal: $malwaregzip failed to download $curl->error",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
		if($GLOBALS["VERBOSE"]){echo "$malwaregzip failed to download $curl->error\n";}
		@unlink($malwaregzip);
	}	
	
	@unlink($phishgzip);
	$curl=new ccurl("{$GLOBALS["MIRROR"]}/categoryuris_phishing.gz");
	if(!$curl->GetFile($phishgzip)){
		ufdbguard_admin_events("UFDB::Fatal: $phishgzip failed to download $curl->error",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
		if($GLOBALS["VERBOSE"]){echo "$phishgzip failed to download $curl->error\n";}
		@unlink($phishgzip);
	}	
	
	
	
	

	
	$mdfile1=md5_file($pubfinal);
	if($GLOBALS["VERBOSE"]){echo "$pubfinal($mdfile1)\n";}
	
	if(is_file($pubgzip)){
		$unix->uncompress($pubgzip, $pubfinal);
		$mdfile2=md5_file($pubfinal);
		if($GLOBALS["VERBOSE"]){echo "$pubfinal($mdfile2)\n";}
		if($mdfile2<>$mdfile1){$reload=true;}
	}else{
		if($GLOBALS["VERBOSE"]){echo "$pubgzip no such file\n";}
	}
	
	$mdfile1=md5_file($trackerfinal);
	if($GLOBALS["VERBOSE"]){echo "$trackerfinal -1- ($mdfile1)\n";}
	
	if(is_file($trackergzip)){
		$unix->uncompress($trackergzip, $trackerfinal);
		$mdfile2=md5_file($trackerfinal);
		if($GLOBALS["VERBOSE"]){echo "$trackerfinal -2- ($mdfile2)\n";}
		if($mdfile1<>$mdfile2){$reload=true;}
	}else{
		if($GLOBALS["VERBOSE"]){echo "$trackergzip no such file\n";}
	}

	
	if(is_file($malwaregzip)){
		$uris=array();
		$q=new mysql_squid_builder();
		$unix->uncompress($malwaregzip, $malwarecsv);
		$handle = @fopen($malwarecsv, "r");
		$q->CreateCategoryUrisTable("malware");
		if ($handle) {
			$line=@fgets($handle);
			$line=trim($line);
			if($line==null){continue;}
			$md5=md5($line);
			$date=date("Y-m-d H:i:s");
			$url=mysql_escape_string2($line);
			$uris[]="('$md5','$date','$url',1)";
		}
		
		if(count($uris)>0){
			$sql="INSERT IGNORE INTO categoryuris_malware
			(zmd5,zDate,pattern,enabled) VALUES ".@implode(",", $uris);
			$q->QUERY_SQL($sql);
		}
		
	}
	
	if(is_file($phishgzip)){
		$uris=array();
		$q=new mysql_squid_builder();
		$unix->uncompress($phishgzip, $phishcsv);
		$handle = @fopen($phishcsv, "r");
		$q->CreateCategoryUrisTable("phishing");
		if ($handle) {
			$line=@fgets($handle);
			$line=trim($line);
			if($line==null){continue;}
			$md5=md5($line);
			$date=date("Y-m-d H:i:s");
			$url=mysql_escape_string2($line);
			$uris[]="('$md5','$date','$url',1)";
		}
	
		if(count($uris)>0){
			$sql="INSERT IGNORE INTO categoryuris_phishing
			(zmd5,zDate,pattern,enabled) VALUES ".@implode(",", $uris);
			$q->QUERY_SQL($sql);
		}
	
	}	
	
	if($reload){
		
		squid_admin_mysql(2, "Ask to reload the Web filtering service","");
		shell_exec("/etc/init.d/ufdb reload");}
	
}

function updatev2_NAS(){
	$sock=new sockets();
	$unix=new unix();
	$t=time();
	$GLOBALS["TEMP_PATH"]=$unix->TEMP_DIR();
	$mount_point="{$GLOBALS["TEMP_PATH"]}/$t";
	$ArticaDBPath=$sock->GET_INFO("ArticaDBPath");
	if($ArticaDBPath==null){$ArticaDBPath="/opt/articatech";}
	$ArticaDBNasUpdt=unserialize(base64_decode($sock->GET_INFO("ArticaDBNasUpdt")));
	include_once(dirname(__FILE__)."/ressources/class.mount.inc");
	$mount=new mount();
	updatev2_progress(30,"{mouting} {$ArticaDBNasUpdt["hostname"]}...");
	$umount=$unix->find_program("umount");
	if(!$mount->smb_mount($mount_point, $ArticaDBNasUpdt["hostname"], $ArticaDBNasUpdt["username"], $ArticaDBNasUpdt["password"], $ArticaDBNasUpdt["folder"])){
		updatev2_progress(100,"{failed} to update Artica categories database trough NAS");
		squid_admin_mysql(1, "Unable to mount on {$ArticaDBNasUpdt["hostname"]}", @implode("\n", $GLOBALS["MOUNT_EVENTS"]));
		return false;
	}
	
	$filename=$ArticaDBNasUpdt["filename"];
	if(!is_file("$mount_point/$filename")){
		updatev2_progress(100,"{failed} {$ArticaDBNasUpdt["hostname"]}/{$ArticaDBNasUpdt["folder"]}/$filename no such file");
		squid_admin_mysql(1, "{failed} to update Artica categories database trough NAS","{$ArticaDBNasUpdt["hostname"]}/{$ArticaDBNasUpdt["folder"]}/$filename no such file");
		shell_exec("$umount -l $mount_point");
		return false;
	}
	
	$tar=$unix->find_program("tar");
	updatev2_progress(40,"{installing}...");
	if($GLOBALS["VERBOSE"]){echo "uncompressing $mount_point/$filename\n";}
	@mkdir($ArticaDBPath,0755,true);	
	updatev2_progress(50,"{stopping_service}...");
	shell_exec("/etc/init.d/artica-postfix stop articadb");
	updatev2_progress(60,"{extracting_package}...");
	shell_exec("$tar -xf  $mount_point/$filename -C $ArticaDBPath/");
	updatev2_progress(70,"{cleaning}...");
	$sock->SET_INFO("ManualArticaDBPathNAS", "0");
	shell_exec("$umount -l $mount_point");
	updatev2_progress(75,"{starting_service}...");
	if($GLOBALS["VERBOSE"]){echo "starting Articadb\n";}
	shell_exec("/etc/init.d/artica-postfix start articadb");
	updatev2_progress(80,"{checking}");
	$q=new mysql();
	if(!$q->DATABASE_EXISTS("catz")){
		updatev2_progress(85,"Removing old database catz");
		$q->DELETE_DATABASE("catz");
	}
	
	updatev2_progress(90,"{finish}");
	$took=$unix->distanceOfTimeInWords($t,time());
	$LOCAL_VERSION=@file_get_contents("$ArticaDBPath/VERSION");
	squid_admin_mysql(2, "New Artica Database statistics $LOCAL_VERSION updated took:$took","");
	_artica_update_event(2,"New Artica Database statistics $LOCAL_VERSION updated took:$took",null,__FILE__,__LINE__,"ufbd-artica");
	ufdbguard_admin_events("New Artica Database statistics $LOCAL_VERSION updated took:$took.",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
	updatev2_progress(100,"{done}");
	$q->QUERY_SQL("TRUNCATE TABLE `catztemp`");
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.visited.sites.php --schedule-id={$GLOBALS["SCHEDULE_ID"]} >/dev/null 2>&1 &");
	shell_exec($cmd);
	
	return true;
	
}


function updatev2_manu(){
	$sock=new sockets();
	$unix=new unix();
	$t=time();
	$GLOBALS["TEMP_PATH"]=$unix->TEMP_DIR();	
	$ManualArticaDBPath=$sock->GET_INFO("ManualArticaDBPath");
	$ArticaDbReplicate=$sock->GET_INFO("ArticaDbReplicate");
	if($ManualArticaDBPath==null){$ManualArticaDBPath="/home/manualupdate/articadb.tar.gz";}
	$ArticaDBPath=$sock->GET_INFO("ArticaDBPath");
	if($ArticaDBPath==null){$ArticaDBPath="/opt/articatech";}
	if(!is_numeric($ArticaDbReplicate)){$ArticaDbReplicate=0;}
	
	$tar=$unix->find_program("tar");
	updatev2_progress(80,"{installing}...");
	if($GLOBALS["VERBOSE"]){echo "uncompressing $ManualArticaDBPath\n";}
	@mkdir($ArticaDBPath,0755,true);
	updatev2_progress(85,"{stopping_service}...");
	shell_exec("/etc/init.d/artica-postfix stop articadb");
	updatev2_progress(95,"{extracting_package}...");
	shell_exec("$tar -xf $ManualArticaDBPath -C $ArticaDBPath/");
	updatev2_progress(96,"{cleaning}...");
	if($ArticaDbReplicate==1){
		@copy("/usr/share/artica-postfix/ressources/logs/web/cache/CATZ_ARRAY","/home/articatechdb.version");
		@copy("$ManualArticaDBPath", "/home/articadb.tar.gz");
		@chmod("/home/articadb.tar.gz",0755);
		@chmod("/home/articatechdb.version",0755);
	}
	@unlink($ManualArticaDBPath);
	updatev2_progress(89,"{starting_service}...");
	if($GLOBALS["VERBOSE"]){echo "starting Articadb\n";}
	
	shell_exec("/etc/init.d/artica-postfix start articadb");
	updatev2_progress(90,"{checking}");
	
	$q=new mysql();
	if(!$q->DATABASE_EXISTS("catz")){
		updatev2_progress(95,"Removing old database catz");
		$q->DELETE_DATABASE("catz");
	}
	
	updatev2_progress(99,"{finish}");
	$took=$unix->distanceOfTimeInWords($t,time());
	$LOCAL_VERSION=@file_get_contents("$ArticaDBPath/VERSION");
	squid_admin_mysql(2, "New Artica Database statistics $LOCAL_VERSION updated took:$took","");
	ufdbguard_admin_events("New Artica Database statistics $LOCAL_VERSION updated took:$took.",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
	updatev2_progress(100,"{done}");
	$q->QUERY_SQL("TRUNCATE TABLE `catztemp`");
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.visited.sites.php --schedule-id={$GLOBALS["SCHEDULE_ID"]} >/dev/null 2>&1 &");
	shell_exec($cmd);
	
	return true;
}



function updatev2(){
	$sock=new sockets();
	$unix=new unix();
	$GLOBALS["TEMP_PATH"]=$unix->TEMP_DIR();
	updatev2_progress(10,"{checking}");
	$timeFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$ArticaDbReplicate=$sock->GET_INFO("ArticaDbReplicate");
	$CategoriesDatabasesByCron=$sock->GET_INFO("CategoriesDatabaseByCron");
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	$ArticaDBPath=$sock->GET_INFO("ArticaDBPath");
	if($ArticaDBPath==null){$ArticaDBPath="/opt/articatech";}
	$ManualArticaDBPath=$sock->GET_INFO("ManualArticaDBPath");
	if($ManualArticaDBPath==null){$ManualArticaDBPath="/home/manualupdate/articadb.tar.gz";}
	$ManualArticaDBPathNAS=$sock->GET_INFO("ManualArticaDBPathNAS");
	
	$datas=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
	
	
	if(!is_numeric($ManualArticaDBPathNAS)){$ManualArticaDBPathNAS=0;}
	if(!is_numeric($CategoriesDatabasesByCron)){$CategoriesDatabasesByCron=0;}
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	if(!is_numeric($ArticaDbReplicate)){$ArticaDbReplicate=0;}
	$WizardStatsAppliance=unserialize(base64_decode($sock->GET_INFO("WizardStatsAppliance")));
	if(!isset($WizardStatsAppliance["SERVER"])){$WizardStatsAppliance["SERVER"]=null;}
	
	if($DisableArticaProxyStatistics==1){
		updatev2_progress(100,"Error: Artica statistics are disabled");
	}
	
	if($datas["UseRemoteUfdbguardService"]==1){
		updatev2_progress(100,"Error: - UseRemoteUfdbguardService -  Only used by {$WizardStatsAppliance["SERVER"]}");
		return;
	}
	

	
	
	if($CategoriesDatabasesByCron==1){
		if(!$GLOBALS["BYCRON"]){
			updatev2_progress(100,"Error: Only downloaded by schedule...");
			return;
		}
	}
	
	
	$arrayinfos=$unix->DIR_STATUS($ArticaDBPath);
	$REQUIRE=round(1753076/1024);
	
	if(is_numeric($arrayinfos["SIZE"])){
		$SIZE=round($arrayinfos["SIZE"]/1024);
		if($SIZE<$REQUIRE){
			artica_update_event(0, "Error: no space left on $ArticaDBPath require `{$REQUIRE}MB` Current `{$SIZE}MB`", null,__FILE__,__LINE__);
			ufdbguard_admin_events("Error: no space left on $ArticaDBPath require `{$REQUIRE}MB` Current `{$SIZE}MB`",__FUNCTION__,__FILE__,__LINE__,"catz");
			updatev2_progress(100,"Error: not engough space on $ArticaDBPath require `{$REQUIRE}MB` Current `{$SIZE}MB`");
			updatev2_adblock();
			return;
		}
	}
	
	
	if(!$GLOBALS["BYCRON"]){
		$StandardTime=2880;
		$LOCAL_VERSION=@file_get_contents("$ArticaDBPath/VERSION");
		if(!is_numeric($LOCAL_VERSION)){$LOCAL_VERSION=0;}
		if($LOCAL_VERSION>0){
			if(!$GLOBALS["NOISO"]){
				if(is_file("/etc/artica-postfix/FROM_ISO")){
					$CHECKTIME=$unix->file_time_min("/etc/artica-postfix/FROM_ISO");
					if($CHECKTIME<$StandardTime){
						if($GLOBALS["VERBOSE"]){echo "LOCAL_VERSION=$LOCAL_VERSION FROM_ISO last update since {$CHECKTIME}Mn, require minimal{$StandardTime}Mn - use --noiso";}
						artica_update_event(0, "LOCAL_VERSION=$LOCAL_VERSION Error: FROM_ISO last update since {$CHECKTIME}Mn, require minimal {$StandardTime}Mn", null,__FILE__,__LINE__);
						updatev2_progress(100,"LOCAL_VERSION=$LOCAL_VERSION Error: FROM_ISO last update since {$CHECKTIME}Mn, require minimal {$StandardTime}Mn");
						updatev2_adblock();
						
						return;
					}
				}
			}
		}
	}
	
	
	
	$t=time();
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."[".__LINE__."] Starting...\n";}
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	if(!$GLOBALS["FORCE"]){
		if($DisableArticaProxyStatistics==1){
			if($GLOBALS["VERBOSE"]){{echo __FUNCTION__."[".__LINE__."] Statistics are disabled, aborting use --force to bypass\n";}
			artica_update_event(2, "Statistics are disabled, aborting use --force to bypass", null,__FILE__,__LINE__);
			updatev2_progress(100,"Error: {finish} statistics are disabled");die();
			}
		}
	}
	
	if($GLOBALS["FORCE"]){events("Force enabled");}
	if(!$GLOBALS["CHECKTIME"]){events("CHECKTIME disabled");}
	
	
	$CHECKTIME=$unix->file_time_min($timeFile);
	
	
	if($GLOBALS["VERBOSE"]){ echo __FUNCTION__."[".__LINE__."] LOCAL_VERSION=$LOCAL_VERSION {$CHECKTIME}Mn for $timeFile\n";}
	events("LOCAL_VERSION=$LOCAL_VERSION {$CHECKTIME}Mn for $timeFile");
	
	
if(!$GLOBALS["BYCRON"]){	
	if(!$GLOBALS["FORCE"]){
			if($LOCAL_VERSION>10){
				if($CHECKTIME<2880){
					if($GLOBALS["VERBOSE"]){{echo __FUNCTION__."[".__LINE__."] Error: last update since {$CHECKTIME}Mn, require minimal 2880Mn use --force to bypass\n";}
					updatev2_progress(100,"Error: last update since {$CHECKTIME}Mn, require minimal 2880Mn");
					updatev2_adblock();
					return;
				}
			}
		}
	}
}	
	
	
	$pid=@file_get_contents($pidfile);
	
	if($unix->process_exists($pid,__FILE__)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($time<10200){
			updatev2_progress(100,"Error: already running pid $pid since {$time}Mn");
			if($GLOBALS["VERBOSE"]){echo __FUNCTION__."[".__LINE__."] Warning: Already running pid $pid since {$time}Mn\n";}
			return;
		}
		else{
			$kill=$unix->find_program("kill");
			unix_system_kill_force($pid);
			if($GLOBALS["SCHEDULE_ID"]>0){
				artica_update_event(1, "Warning: Old task pid $pid since {$time}Mn has been killed, (reach 7200mn)", null,__FILE__,__LINE__);
				ufdbguard_admin_events("Warning: Old task pid $pid since {$time}Mn has been killed, (reach 7200mn)",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");}			
		}
	}
	
	@unlink($timeFile);
	@file_put_contents($timeFile, time());	
	@file_put_contents($pidfile, getmypid());
	
	updatev2_checkversion();
	cicap_artica(true);
	ufdbtables(true);
	C_ICAP_TABLES(true);
	schedulemaintenance();
	EXECUTE_BLACK_INSTANCE();
	
}

function events($text){
	$pid=@getmypid();
	$filename=basename(__FILE__);
	$date=@date("H:i:s");
	$logFile="/var/log/artica-postfix/updatev2.debug";
	$size=@filesize($logFile);
	if($size>1000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$pid [$date] ".basename(__FILE__)." $text\n");
	@fclose($f);

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
	$schedule=null;
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	if(isset($GLOBALS["SCHEDULE_ID"])){
		if($GLOBALS["SCHEDULE_ID"]>0){
			$schedule="--schedule-id={$GLOBALS["SCHEDULE_ID"]} ";
		}
	}
	
	
	
	if($GLOBALS["BYCRON"]){
		$schedule=$schedule."--bycron ";
	}
	
	
	WriteMyLogs("$nohup $php5 /usr/share/artica-postfix/exec.update.blacklist.instant.php $schedule>/dev/null 2>&1 &",__FUNCTION__,__FILE__,__LINE__);
	
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.update.blacklist.instant.php $schedule>/dev/null 2>&1 &");
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.update.squid.tlse.php $schedule>/dev/null 2>&1 &");
}






function categorize_delete(){
	$unix=new unix();
	$URIBASE=$unix->MAIN_URI();
	$tmpdir=$unix->TEMP_DIR();
	if(!is_file("$tmpdir/categorize_delete.sql")){
	$curl=new ccurl("$URIBASE/blacklist/categorize_delete.gz");
	if(!$curl->GetFile("$tmpdir/categorize_delete.gz")){
		ufdbguard_admin_events("Fatal: unable to download categorize_delete.gz file $curl->error",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
		return;
	}

	if(!extractGZ("$tmpdir/categorize_delete.gz","$tmpdir/categorize_delete.sql")){
			ufdbguard_admin_events("Fatal: unable to extract $tmpdir/categorize_delete.gz",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
			return;
		}
		
	}
	$q=new mysql_squid_builder();
	$datas=explode("\n",@file_get_contents("$tmpdir/categorize_delete.sql"));
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
	
	ufdbguard_admin_events("Success updating deleted ". count($datas)." websites from categories",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
	@unlink("$tmpdir/categorize_delete.sql");
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
	$unix=new unix();
	$tmpdir=$unix->TEMP_DIR();
	$fh = fopen("$tmpdir/$tablename.sql", 'w+');
	
	$c=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if($ligne["category"]==null){continue;}
			if($ligne["pattern"]==null){continue;}
			if($ligne["zmd5"]==null){continue;}
			$c++;
			$line="('{$ligne["zmd5"]}','{$ligne["zDate"]}','{$ligne["category"]}','{$ligne["pattern"]}','{$ligne["uuid"]}',1,1)";
			fwrite($fh, $line."\n");
		}
		
		echo "close $tmpdir/$tablename.sql $c rows\n";
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
				