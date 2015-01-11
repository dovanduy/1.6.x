<?php
$GLOBALS["FULL"]=false;
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.squidguard.inc');
include_once(dirname(__FILE__).'/ressources/class.artica-meta.inc');
$GLOBALS["working_directory"]="/opt/artica/proxy";
$GLOBALS["WORKDIR_LOCAL"]="/var/lib/ufdbartica";
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
$GLOBALS["OUTPUT"]=false;
$GLOBALS["MYPID"]=getmypid();
$GLOBALS["CMDLINE"]=@implode(" ", $argv);
if(preg_match("#--notime#",implode(" ",$argv))){$GLOBALS["NOTIME"]=true;}
if(preg_match("#--nodelete#",implode(" ",$argv))){$GLOBALS["NODELETE"]=true;}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
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
if($argv[1]=="--meta"){die();exit;}


if($argv[1]=="--ufdb-check"){ufdbversions();die();}
if($argv[1]=="--ufdb"){ufdbtables();die();}
if($argv[1]=="--ufdbsum"){calculate_categorized_websites();die();}
if($argv[1]=="--ufdbmaster"){
	$GLOBALS["FORCE"]=true;
	updatev2();
	META_MASTER_UFDBTABLES();
	die();
}



WriteMyLogs("Executed: {$GLOBALS["CMDLINE"]} task:{$GLOBALS["SCHEDULE_ID"]}",__FUNCTION__,__FILE__,__LINE__);
$sock=new sockets();
$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
$DisableCategoriesDatabasesUpdates=intval($sock->GET_INFO("DisableCategoriesDatabasesUpdates"));

if($DisableCategoriesDatabasesUpdates==1){
	ufdbevents("DisableCategoriesDatabasesUpdates IS ACTIVE ,ABORTING TASK",__FUNCTION__,__FILE__,__LINE__);
	die();	
	
}
	
if($EnableRemoteStatisticsAppliance==1){
	ufdbevents("EnableRemoteStatisticsAppliance IS ACTIVE ,ABORTING TASK",__FUNCTION__,__FILE__,__LINE__);
	WriteMyLogs("EnableRemoteStatisticsAppliance ACTIVE ,ABORTING TASK",__FUNCTION__,__FILE__,__LINE__);
	die();
}

if(is_file("/etc/artica-postfix/PROXYTINY_APPLIANCE")){
	ufdbevents("PROXYTINY_APPLIANCE IS ACTIVE ,ABORTING TASK",__FUNCTION__,__FILE__,__LINE__);
	die();
}

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

if($argv[1]=="--cicap"){C_ICAP_TABLES();die();}
if($argv[1]=="--ufdb-first"){ufdbFirst();die();}
if($argv[1]=="--scan-db"){scan_artica_databases();die();}
if($argv[1]=="--repair"){updatev2_checktables_repair();die();}
if($argv[1]=="--get-version"){updatev2_checkversion();die();}
if($argv[1]=="--adblock"){updatev2_adblock();die();}
if($argv[1]=="--cicap-dbs"){die();}
if($argv[1]=="--ufdbmeta"){ufdbtables_artica_meta();die();}




updatev2();


function ufdbFirst(){
	if(!is_file("/etc/artica-postfix/ufdbfirst")){
		@file_put_contents("/etc/artica-postfix/ufdbfirst", time());
		ufdbtables();
		
		C_ICAP_TABLES(true);
	}
}


function C_ICAP_TABLES($nopid=false){
	$unix=new unix();
	$size=$unix->DIRSIZE_BYTES("/home/artica/categories_databases");
	if($size>2000){
		$rm=$unix->find_program("rm");
		shell_exec("$rm -rf  /home/artica/categories_databases/*");
	}
	
	return;

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
	return true;
}

function ufdbtables_artica_meta(){
	$unix=new unix();
	$WORKDIR=$GLOBALS["WORKDIR_LOCAL"];
	@mkdir($WORKDIR,0755,true);
	@chmod($WORKDIR, 0755);
	$tmpdir=$unix->TEMP_DIR();
	$myVersion=intval(@file_get_contents("/etc/artica-postfix/ufdbartica.txt"));
	$meta=new artica_meta();

	$curl=$meta->buildCurl("/meta-updates/webfiltering/ufdbartica.txt");
	if(!$curl->GetFile("$tmpdir/ufdbartica.txt")){
		artica_update_event(0, "Failed Downloading $meta->LOG_URI", @implode("\n",$curl->errors),__FILE__,__LINE__);
		meta_admin_mysql(0, "Failed Downloading $meta->LOG_URI", @implode("\n",$curl->errors),__FILE__,__LINE__);
		return false;
	}
	
	
	if(!is_file("/etc/artica-postfix/artica-webfilter-db-index.txt")){
		$curl=$meta->buildCurl("/meta-updates/webfiltering/index.txt");
		
		if(!$curl->GetFile("/etc/artica-postfix/artica-webfilter-db-index.txt")){
			artica_update_event(0, "Failed Downloading webfiltering/index.txt", @implode("\n",$curl->errors),__FILE__,__LINE__);
			meta_admin_mysql(0, "Failed Downloading webfiltering/index.txt", @implode("\n",$curl->errors),__FILE__,__LINE__);
		}
	}
	if(!is_file("/etc/artica-postfix/ufdbcounts.txt")){
		$curl=$meta->buildCurl("/meta-updates/webfiltering/ufdbcounts.txt");
	
		if(!$curl->GetFile("/etc/artica-postfix/ufdbcounts.txt")){
			artica_update_event(0, "Failed Downloading webfiltering/ufdbcounts.txt", @implode("\n",$curl->errors),__FILE__,__LINE__);
			meta_admin_mysql(0, "Failed Downloading webfiltering/ufdbcounts.txt", @implode("\n",$curl->errors),__FILE__,__LINE__);
		}
	}	
	
	
	
	
	$Remote_version=intval(@file_get_contents("$tmpdir/ufdbartica.txt"));
	echo "Current............: $myVersion\n";
	echo "Available..........: $Remote_version\n";
	$Remote_versionTime=date("Y-m-d H:i:s",$Remote_version);
	$rm=$unix->find_program("rm");
	$cat=$unix->find_program("cat");
	$tar=$unix->find_program("tar");
	
	if($myVersion==$Remote_version){echo "My version $myVersion is the same than $Remote_version $Remote_versionTime, aborting\n";return;}
	if($myVersion>$Remote_version){echo "My version $myVersion is newest than $Remote_version $Remote_versionTime, aborting\n";return;}
	
	$curl=$meta->buildCurl("/meta-updates/webfiltering/ufdbartica/ufdbartica.txt");
	
//***************************************************************************************************************	
	
	if($curl->GetFile("$tmpdir/ufdbartica.txt")){
		$ufdbartica_tmp="$tmpdir/ufdbartica_tmp";
		@mkdir($ufdbartica_tmp,0755,true);
		$splitted=unserialize(@file_get_contents("$tmpdir/ufdbartica.txt"));
		if(is_array($splitted)){
			if(count($splitted)>2){
				while (list ($targetFile, $md5file) = each ($ARRAY) ){
					$BaseName=basename($targetFile);
					$HTTP_LINK="/meta-updates/webfiltering/ufdbartica/$BaseName";
					$LOCAL_FILE="$ufdbartica_tmp/$BaseName";
					writelogs_meta("Checking $LOCAL_FILE", __FUNCTION__,__FILE__,__LINE__);
					if(is_file($LOCAL_FILE)){
						$md5Local=md5_file($LOCAL_FILE);
						if($md5Local==$md5file){ continue; }
						writelogs_meta("$LOCAL_FILE corrupted...", __FUNCTION__,__FILE__,__LINE__);
						@unlink($LOCAL_FILE);
					}
						
					writelogs_meta("Downloading $HTTP_LINK", __FUNCTION__,__FILE__,__LINE__);
					$curl=$meta->buildCurl($HTTP_LINK);
					if(!$curl->GetFile($LOCAL_FILE)){
						writelogs_meta("Unable to download $HTTP_LINK $curl->error\n".@implode("\n", $curl->errors), __FUNCTION__,__FILE__,__LINE__);
						return true;
					}
					$md5Local=md5_file($LOCAL_FILE);
					if($md5Local==$md5file){
						writelogs_meta("$HTTP_LINK success...", __FUNCTION__,__FILE__,__LINE__);
						continue;
					}
			}
			

			system("$cat $ufdbartica_tmp/*.tgz.* >$tmpdir/ufdbartica.tgz");
			
		}
	 }
		
	}
//***************************************************************************************************************	
	@unlink("$tmpdir/ufdbartica.txt");
	if(!is_file("$tmpdir/ufdbartica.tgz")){
		$curl=$meta->buildCurl("/meta-updates/webfiltering/ufdbartica.tgz");
	
		if(!$curl->GetFile("$tmpdir/ufdbartica.tgz")){
			artica_update_event(0, "Failed Downloading webfiltering/ufdbartica.tgz", @implode("\n",$curl->errors),__FILE__,__LINE__);
			meta_admin_mysql(0, "Failed Downloading webfiltering/ufdbartica.tgz", @implode("\n",$curl->errors),__FILE__,__LINE__);
			@unlink("$tmpdir/ufdbartica.tgz");	
			return false;
		}
	}
	
	
	$curl=$meta->buildCurl("/meta-updates/webfiltering/index.txt");
	
	if(!$curl->GetFile("/etc/artica-postfix/artica-webfilter-db-index.txt")){
		artica_update_event(0, "Failed Downloading webfiltering/index.txt", @implode("\n",$curl->errors),__FILE__,__LINE__);
		meta_admin_mysql(0, "Failed Downloading webfiltering/index.txt", @implode("\n",$curl->errors),__FILE__,__LINE__);
	}
	
	$curl=$meta->buildCurl("/meta-updates/webfiltering/ARTICAUFDB_LAST_DOWNLOAD");
	$curl->GetFile("/etc/artica-postfix/ARTICAUFDB_LAST_DOWNLOAD");
	$STATUS=unserialize(@file_get_contents("/etc/artica-postfix/ARTICAUFDB_LAST_DOWNLOAD"));
	$STATUS["LAST_DOWNLOAD"]["LAST_CHECK"]=time();
	@file_put_contents("/etc/artica-postfix/ARTICAUFDB_LAST_DOWNLOAD",serialize($STATUS));
	
	
	if(!$unix->TARGZ_TEST_CONTAINER("$tmpdir/ufdbartica.tgz")){
		artica_update_event(0, "Failed $tmpdir/ufdbartica.tgz corrupted package", @implode("\n",$curl->errors),__FILE__,__LINE__);
		meta_admin_mysql(0, "Failed $tmpdir/ufdbartica.tgz corrupted package", @implode("\n",$curl->errors),__FILE__,__LINE__);
		@unlink("$tmpdir/ufdbartica.tgz");
		return false;
	}
	@file_put_contents("/etc/artica-postfix/ufdbartica.txt", $Remote_version);
	$tar=$unix->find_program("tar");
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$tar -xf $tmpdir/ufdbartica.tgz -C $WORKDIR/");
	@unlink("$tmpdir/ufdbartica.tgz");
	if(!is_file("/opt/ufdbcat/bin/ufdbcatdd")){
		system("$php5 /usr/share/artica-postfix/exec.ufdbcat.php --install --noupdate");
	}
	shell_exec("/etc/init.d/ufdbcat reload");
	artica_update_event(0, "Success Artica Webfiltering databases v.$Remote_version", @implode("\n",$curl->errors),__FILE__,__LINE__);
	meta_admin_mysql(0, "Success Artica Webfiltering databases v.$Remote_version", @implode("\n",$curl->errors),__FILE__,__LINE__);
	updatev2_progress(100,"{done} [".__LINE__."]");
	
}


function META_MASTER_UFDBTABLES_ufdbartica_txt(){
	$sock=new sockets();
	$unix=new unix();
	$EnableArticaMetaServer=intval($sock->GET_INFO("EnableArticaMetaServer"));
	if($EnableArticaMetaServer==0){return;}
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	$destfile="$ArticaMetaStorage/webfiltering/ufdbartica.tgz";
	$destDir="$ArticaMetaStorage/webfiltering/ufdbartica";
	if(!is_file($destfile)){
		echo "$destfile no such file\n";
		return;
	}
	
	
	
	
	$filename="$ArticaMetaStorage/webfiltering/ufdbartica.txt";
	if(is_file($filename)){
		if($GLOBALS["VERBOSE"]){echo "$filename OK\n";}
		return;
	}	
	$filemtime=filemtime($destfile);
	if($GLOBALS["VERBOSE"]){echo "$destfile = $filemtime\n";}
	file_put_contents("$ArticaMetaStorage/webfiltering/ufdbartica.txt",$filemtime);
	if(is_file("/etc/artica-postfix/artica-webfilter-db-index.txt")){
		@unlink("$ArticaMetaStorage/webfiltering/index.txt");
		@copy("/etc/artica-postfix/artica-webfilter-db-index.txt","$ArticaMetaStorage/webfiltering/index.txt");
	}
}

function META_MASTER_UFDBTABLES($force=false){
	$sock=new sockets();
	$unix=new unix();
	$EnableArticaMetaServer=intval($sock->GET_INFO("EnableArticaMetaServer"));
	if($EnableArticaMetaServer==0){return;}
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	@mkdir("$ArticaMetaStorage/nightlys",0755,true);
	@mkdir("$ArticaMetaStorage/releases",0755,true);
	@mkdir("$ArticaMetaStorage/webfiltering",0755,true);
	$srcdir=$GLOBALS["WORKDIR_LOCAL"];
	$destfile="$ArticaMetaStorage/webfiltering/ufdbartica.tgz";
	$destdir="$ArticaMetaStorage/webfiltering/ufdbartica";
	META_MASTER_UFDBTABLES_ufdbartica_txt();
	$rm=$unix->find_program("rm");
	
	if(is_file($destfile)){
		$timeFile="/etc/artica-postfix/pids/exec.artica-meta-server.php.checkufdb.time";
		$time=$unix->file_time_min($timeFile);
		if($time<1440){return;}
	}
	$tar=$unix->find_program("tar");
	$split=$unix->find_program("split");
	@unlink($destfile);
	chdir($srcdir);
	shell_exec("$tar czf $destfile *");
	if(is_dir($destdir)){shell_exec("$rm -rf $destdir"); }
	
	@mkdir("$destdir",0755,true);
	chdir("$destdir");
	system("cd $destdir");
	@copy($destfile, "$destdir/ufdbartica.tgz");
	echo "Split...ufdbartica.tgz\n";
	shell_exec("$split -b 1m -d ufdbartica.tgz ufdbartica.tgz.");
	@unlink("$destdir/ufdbartica.tgz");
	
	$files=$unix->DirFiles("$destdir");
	while (list ($num, $ligne) = each ($files) ){
		$Splited_md5=md5_file("$destdir/$num");
		$ARRAY["$num"]=$Splited_md5;
	}
	@file_put_contents("$destdir/ufdbartica.txt", serialize($ARRAY));
	
	
	@unlink("$ArticaMetaStorage/webfiltering/ufdbartica.txt");
	@unlink("$ArticaMetaStorage/webfiltering/ARTICAUFDB_LAST_DOWNLOAD");
	@copy("/etc/artica-postfix/ARTICAUFDB_LAST_DOWNLOAD","$ArticaMetaStorage/webfiltering/ARTICAUFDB_LAST_DOWNLOAD");
	@file_get_contents("$ArticaMetaStorage/webfiltering/ufdbartica.txt",time());
	
	if(is_file("/etc/artica-postfix/artica-webfilter-db-index.txt")){
		@unlink("$ArticaMetaStorage/webfiltering/index.txt");
		@copy("/etc/artica-postfix/artica-webfilter-db-index.txt","$ArticaMetaStorage/webfiltering/index.txt");
	}
	
	if(is_file("/etc/artica-postfix/ufdbcounts.txt")){
		@unlink("$ArticaMetaStorage/webfiltering/ufdbcounts.txt");
		@copy("/etc/artica-postfix/ufdbcounts.txt","$ArticaMetaStorage/webfiltering/ufdbcounts.txt");
	}
	
	calculate_categorized_websites(true);
	artica_update_event(2, "Artica Webfiltering databases: Success update Artica Meta webfiltering repository", @implode("\n", $GLOBALS["EVENTS"]),__FILE__,__LINE__);
	meta_admin_mysql(2, "Success update webfiltering repository with Webfiltering databases", null,__FILE__,__LINE__);

}

function calculate_categorized_websites($nopid=false){
	if(!$GLOBALS["FORCE"]){
	if(!$nopid){
		$unix=new unix();
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
		if($GLOBALS["VERBOSE"]){echo "pidTime $pidTime\n";}
		$nohup=$unix->find_program("nohup");
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid,__FILE__)){
			$timepid=$unix->PROCCESS_TIME_MIN($pid);
			if(!$GLOBALS["NOLOGS"]){
				// ufdbguard_admin_events("UFDB::Warning: Task already executed PID: $pid since {$timepid}Mn",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
				return;
			}
		}
		@file_put_contents($pidfile, getmypid());
		
		$pidTimeEx=$unix->file_time_min($pidTime);
		if($pidTimeEx<60){return;}
		
	}
	}
	
	if(!is_file("/etc/artica-postfix/ufdbcounts.txt")){
		echo "/etc/artica-postfix/ufdbcounts.txt No such file\n";
	}
	$arrayCount=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/ufdbcounts.txt")));
	$array=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/artica-webfilter-db-index.txt")));
	
	$WORKDIR=$GLOBALS["WORKDIR_LOCAL"];
	if(is_link($WORKDIR)){$WORKDIR=readlink($WORKDIR);}
	
	$c=0;
	$d=0;
	while (list ($path, $count) = each ($array) ){
		if(!is_file("$WORKDIR/$path/domains.ufdb")){
			
			if($GLOBALS["VERBOSE"]){echo "$WORKDIR/$path/domains.ufdb no such file\n";}
			continue;
		}
		$c=$c+intval($count);
		if($GLOBALS["VERBOSE"]){echo "$WORKDIR/$path - ".wFormatNumber($count) ."= ".wFormatNumber($c)."\n";}
		$UFDB_ARTICA_CATZ[$path]=@filesize("$WORKDIR/$path/domains.ufdb");
		$UFDB_ARTICA_COUNTZ[$path]=$arrayCount[$path];
		$d++;
	}
	
	if($GLOBALS["VERBOSE"]){echo "------------------------- ".wFormatNumber($c)."\n";}
	@file_put_contents("/usr/share/artica-postfix/ressources/UFDB_ARTICA_COUNT", $c);
	@file_put_contents("/usr/share/artica-postfix/ressources/UFDB_ARTICA_DBS", $d);
	@file_put_contents("/usr/share/artica-postfix/ressources/UFDB_ARTICA_CATZ", serialize($UFDB_ARTICA_CATZ));
	@file_put_contents("/usr/share/artica-postfix/ressources/UFDB_ARTICA_COUNTZ", serialize($UFDB_ARTICA_COUNTZ));
	
	
}

function wFormatNumber($number, $decimals = 0, $thousand_separator = ' ', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}

function ufdbversions(){
	$unix=new unix();
	if($GLOBALS["MIRROR"]==null){ updatev2_checkversion();}
	$URIBASE=$GLOBALS["MIRROR"];
	$CACHE_FILE="/etc/artica-postfix/ufdb.tables.db";
	$tmpdir=$unix->TEMP_DIR();
	$WORKDIR=$GLOBALS["WORKDIR_LOCAL"];
	if(is_link($WORKDIR)){$WORKDIR=readlink($WORKDIR);}
	echo "Mirror.......: $URIBASE\n";
	echo "Cache File...: $CACHE_FILE\n";
	echo "Work dir.....: $WORKDIR\n";
	
	echo "Get heads of Index file $URIBASE/index.txt\n";
	
	$UFDBGUARD_LAST_INDEX_TIME="/etc/artica-postfix/UFDBGUARD_LAST_INDEX_TIME";
	$old_time=intval(@file_get_contents("$UFDBGUARD_LAST_INDEX_TIME"));
	
	
	$curl=new ccurl("$URIBASE/index.txt");
	if(!$curl->GetHeads()){
		$curl=new ccurl("$URIBASE/ufdb/index.txt");
		if(!$curl->GetHeads()){
			echo "Get Heads failed of Index file $URIBASE/index.txt\n";
			return;
		}
	}
	$source_filetime=$curl->CURL_ALL_INFOS["filetime"];
	echo "filetime.......:  $source_filetime ". date("Y-m-d H:i:s",$source_filetime)."\n";
	echo "MyTime.........:  $old_time ". date("Y-m-d H:i:s",$old_time)."\n";
}

function ufdbtables_md5($array,$URIBASE){
	
	$STATUS=unserialize(@file_get_contents("/etc/artica-postfix/ARTICAUFDB_LAST_DOWNLOAD"));
	
	if(isset($GLOBALS["ufdbtables_md5_exec"])){return true;}
	$GLOBALS["ufdbtables_md5_exec"]=true;
	
	if(!is_array($array)){
		ufdbevents("ufdbtables_md5: Not an array !");
		return false;}
	if(count($array)<100){
		ufdbevents("ufdbtables_md5: Count < 100 !");
		return false;}
	$WORKDIR=$GLOBALS["WORKDIR_LOCAL"];
	if(is_link($WORKDIR)){$WORKDIR=readlink($WORKDIR);}
	
	while (list ($tablename, $md5) = each ($array) ){
		if(trim($md5)==null){continue;}
		$md5string=null;
		if(is_file("$WORKDIR/$tablename/domains.ufdb")){
			$md5string=md5_file("$WORKDIR/$tablename/domains.ufdb");
			if($md5string==$md5){
				echo "$tablename OK\n";
				ufdbevents("$tablename [OK]");
				continue;
			}
		}
		ufdbevents("$tablename Not correct $md5string <> $md5");
		echo "$tablename Not correct $md5string <> $md5\n";
		updatev2_progress(25,"{downloading} $tablename [".__LINE__."]");
		$STATUS["LAST_DOWNLOAD"]["CATEGORY"]=$tablename;
		$ERRORDB=0;
		if(!ufdbtables_DownloadInstall($URIBASE,$tablename,0)){
			$ERRORDB++;
			$STATUS["LAST_DOWNLOAD"]["TIME"]=time();
			$STATUS["LAST_DOWNLOAD"]["SIZE"]=($GLOBALS["UFDB_SIZE"]/1024);
			$STATUS["LAST_DOWNLOAD"]["FAILED"]=$ERRORDB;
			@file_put_contents("/etc/artica-postfix/ARTICAUFDB_LAST_DOWNLOAD", serialize($STATUS));
			continue;
		}
		
		$STATUS["LAST_DOWNLOAD"]["TIME"]=time();
		$STATUS["LAST_DOWNLOAD"]["SIZE"]=($GLOBALS["UFDB_SIZE"]/1024);
		@file_put_contents("/etc/artica-postfix/ARTICAUFDB_LAST_DOWNLOAD", serialize($STATUS));
		
	}
	updatev2_progress(99,"{done} [".__LINE__."]");
	return true;
	
}

function ufdbevents($text=null){
	
	$unix=new unix();
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
	
		if(isset($trace[0])){
			$file=basename($trace[0]["file"]);
			$function=$trace[0]["function"];
			$line=$trace[0]["line"];
		}
	
		
	
	
	}
	
	if($GLOBALS["OUPUT"]){echo "$text [$line]\n";}
	
	$unix->events($text,"/var/log/artica-ufdb.log",false,$function,$line,$file);
}

function ufdbtables($nopid=false){
	$unix=new unix();
	$sock=new sockets();
	
	if(!$nopid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$nohup=$unix->find_program("nohup");
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid,__FILE__)){
			$timepid=$unix->PROCCESS_TIME_MIN($pid);
			updatev2_progress(110,"Error, $timepid already running [".__LINE__."]");
			if(!$GLOBALS["NOLOGS"]){
				// ufdbguard_admin_events("UFDB::Warning: Task already executed PID: $pid since {$timepid}Mn",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
				return;
			}
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$GLOBALS["EVENTS"]=array();
	$CACHE_FILE="/etc/artica-postfix/ufdb.tables.db";
	
	updatev2_progress(10,"CACHE_FILE = $CACHE_FILE [".__LINE__."]");
	
	$sock=new sockets();
	$EnableArticaMetaClient=intval($sock->GET_INFO("EnableArticaMetaClient"));
	if($EnableArticaMetaClient==1){
		updatev2_progress(10,"Using Artica Meta console [".__LINE__."]");
		return ufdbtables_artica_meta();
	}

	$UseRemoteUfdbguardService=$sock->GET_INFO('UseRemoteUfdbguardService');
	if(!is_numeric($UseRemoteUfdbguardService)){$UseRemoteUfdbguardService=0;}
	
	if($UseRemoteUfdbguardService==1){
		updatev2_progress(10,"UseRemoteUfdbguardService = TRUE - aborting [".__LINE__."]");
		return;
	}
	
	if($GLOBALS["MIRROR"]==null){ 
		updatev2_progress(10,"MIRROR is null #2 [".__LINE__."]");
		unset($GLOBALS["updatev2_checkversion"]);
		updatev2_checkversion();
	}
	
	if($GLOBALS["MIRROR"]==null){
		updatev2_progress(110,"MIRROR is null #1 [".__LINE__."]");
		return;
	}
	
	
	$tmpdir=$unix->TEMP_DIR();
	$URIBASE=$GLOBALS["MIRROR"];
	$WORKDIR=$GLOBALS["WORKDIR_LOCAL"];
	if(is_link($WORKDIR)){$WORKDIR=readlink($WORKDIR);}
	

	
	$CategoriesDatabasesByCron=$sock->GET_INFO("CategoriesDatabaseByCron");
	if(!is_numeric($CategoriesDatabasesByCron)){$CategoriesDatabasesByCron=1;}
	
	if(!$GLOBALS["FORCE"]){
		if($CategoriesDatabasesByCron==1){
			if(!$GLOBALS["BYCRON"]){
				updatev2_progress(110,"{done} CategoriesDatabasesByCron [".__LINE__."]");
				return;}
		}
	}
	updatev2_progress(15,"$URIBASE/index.txt [".__LINE__."]");
	$curl=new ccurl("$URIBASE/index.txt");
	$STATUS=unserialize(@file_get_contents("/etc/artica-postfix/ARTICAUFDB_LAST_DOWNLOAD"));
	$STATUS["LAST_DOWNLOAD"]["LAST_CHECK"]=time();
	@file_put_contents("/etc/artica-postfix/ARTICAUFDB_LAST_DOWNLOAD",serialize($STATUS));
	
	if(!$curl->GetHeads()){
		if($GLOBALS["VERBOSE"]){echo "Fatal ! $URIBASE/index.txt ERROR NUMBER $curl->CURLINFO_HTTP_CODE\n";}
		if( ($curl->CURLINFO_HTTP_CODE==404 ) OR ($curl->CURLINFO_HTTP_CODE==300 )){
			if(!preg_match("#\/ufdb#", $URIBASE)){$URIBASE="$URIBASE/ufdb";}
			$curl=new ccurl("$URIBASE/index.txt");
			if(!$curl->GetHeads()){
				$GLOBALS["EVENTS"][]="$URIBASE/index.txt";
				$GLOBALS["EVENTS"][]="Failed with error $curl->error";
				while (list ($a, $b) = each ($GLOBALS["CURLDEBUG"]) ){$GLOBALS["EVENTS"][]=$b;}
				squid_admin_mysql(0,"Unable to download blacklist index file with error: `$curl->error`",@implode("\n", $GLOBALS["EVENTS"]),__FUNCTION__,__LINE__);
				artica_update_event(0,"Unable to download Artica blacklist index file `$curl->error`",@implode("\n", $GLOBALS["EVENTS"]),__FUNCTION__,__LINE__);
				// ufdbguard_admin_events("UFDB::Fatal: Unable to download blacklist index file $curl->error",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
				echo "UFDB: Failed to retreive $URIBASE/index.txt ($curl->error)\n";
				updatev2_progress(110,"Failed to retreive $URIBASE/index.txt [".__LINE__."]");
				updatev2_adblock();
				return;				
			}
		}
	}
	
	
	updatev2_progress(20,"Downloading MD5Strings.txt [".__LINE__."]");
	$curl=new ccurl("$URIBASE/MD5Strings.txt");
	
	if(!$curl->GetFile("$tmpdir/MD5Strings.txt")){
		artica_update_event(2,"$URIBASE: Unable to download Artica blacklist MD5 table `$curl->error`",@implode("\n", $GLOBALS["EVENTS"]),__FUNCTION__,__LINE__);
		@unlink("$tmpdir/MD5Strings.txt");
		
	}
	
	
	$MD5_strings=unserialize(@file_get_contents("$tmpdir/MD5Strings.txt"));
	
	
	$source_filetime=$curl->CURL_ALL_INFOS["filetime"];
	if($GLOBALS["VERBOSE"]){echo "$URIBASE/index.txt filetime: $source_filetime ". date("Y-m-d H:i:s",$source_filetime)."\n";}
	$GLOBALS["EVENTS"][]="$URIBASE/index.txt";
	$GLOBALS["EVENTS"][]="filetime: $source_filetime ". date("Y-m-d H:i:s",$source_filetime);
	$UFDBGUARD_LAST_INDEX_TIME="/etc/artica-postfix/UFDBGUARD_LAST_INDEX_TIME";
	
	
	$old_time=intval(@file_get_contents("$UFDBGUARD_LAST_INDEX_TIME"));
	$GLOBALS["EVENTS"][]="Old filetime: $old_time ". date("Y-m-d H:i:s",$old_time);
	
	if(is_file("/etc/artica-postfix/ufdbcounts.txt")){
			
		if($source_filetime==$old_time){
			ufdbtables_md5($MD5_strings,$URIBASE);
			$GLOBALS["EVENTS"][]="No new updates";
			updatev2_progress(100,"{up-to-date} [".__LINE__."]");
			return true;
		}
		if($source_filetime<$old_time){
			ufdbtables_md5($MD5_strings,$URIBASE);
			$GLOBALS["EVENTS"][]="No new updates";
			updatev2_progress(100,"{up-to-date} [".__LINE__."]");
			return true;
		}	
	
	}
	
	updatev2_progress(99,"$URIBASE/ufdbcounts.txt [".__LINE__."]");
	$curl=new ccurl("$URIBASE/ufdbcounts.txt");
	if(!$curl->GetFile("/etc/artica-postfix/ufdbcounts.txt")){
		$GLOBALS["EVENTS"][]="$URIBASE/ufdbcounts.txt";
		$GLOBALS["EVENTS"][]="Failed with error $curl->error";
		while (list ($a, $b) = each ($GLOBALS["CURLDEBUG"]) ){$GLOBALS["EVENTS"][]=$b;}
		squid_admin_mysql(0,"Unable to download $URIBASE/ufdbcounts.txt index file `$curl->error`",@implode("\n", $GLOBALS["EVENTS"]),__FUNCTION__,__LINE__);
		artica_update_event(0,"Unable to download Artica $URIBASE/ufdbcounts.txt index file `$curl->error`",@implode("\n", $GLOBALS["EVENTS"]),__FUNCTION__,__LINE__);
		// ufdbguard_admin_events("UFDB::Fatal: Unable to download $URIBASE/ufdbcounts.txt index file $curl->error",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
		
	}
	
	
		
	updatev2_progress(99,"$URIBASE/index.txt [".__LINE__."]");
	$curl=new ccurl("$URIBASE/index.txt");
	if(!$curl->GetFile("/etc/artica-postfix/artica-webfilter-db-index.txt")){
		$GLOBALS["EVENTS"][]="$URIBASE/index.txt";
		$GLOBALS["EVENTS"][]="Failed with error $curl->error";
		while (list ($a, $b) = each ($GLOBALS["CURLDEBUG"]) ){$GLOBALS["EVENTS"][]=$b;}
		squid_admin_mysql(0,"Unable to download blacklist index file `$curl->error`",@implode("\n", $GLOBALS["EVENTS"]),__FUNCTION__,__LINE__);
		artica_update_event(0,"Unable to download Artica blacklist index file `$curl->error`",@implode("\n", $GLOBALS["EVENTS"]),__FUNCTION__,__LINE__);
		// ufdbguard_admin_events("UFDB::Fatal: Unable to download blacklist index file $curl->error",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
		echo "UFDB: Failed to retreive $URIBASE/index.txt ($curl->error)\n";
		updatev2_progress2(110,"Unable to download blacklist index file");
		updatev2_adblock();
		return;
	}
	
	if(ufdbtables_md5($MD5_strings,$URIBASE)){
		updatev2_progress(100,"{done} [".__LINE__."]");
		return;
	}

	
	$LOCAL_CACHE=unserialize(base64_decode(@file_get_contents($CACHE_FILE)));
	$REMOTE_CACHE=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/artica-webfilter-db-index.txt")));
	$CALCULATED_SIZE=0;
	
	$MAx=count($REMOTE_CACHE);
	$BigSize=0;
	$c=0;
	$ERRORDB=0;
	while (list ($tablename, $size) = each ($REMOTE_CACHE) ){
		$OriginalSize=$size;
		if(blacklisted_tables($tablename)){continue;}
		
		$STATUS["LAST_DOWNLOAD"]["CATEGORY"]=$tablename;
		
		if($size<>$LOCAL_CACHE[$tablename]){
			ufdbevents("$tablename  $size <> {$LOCAL_CACHE[$tablename]}");
			$c++;
			
			if(!ufdbtables_DownloadInstall($URIBASE,$tablename,$size,null)){
				$ERRORDB++;
				$STATUS["LAST_DOWNLOAD"]["TIME"]=time();
				$STATUS["LAST_DOWNLOAD"]["SIZE"]=($GLOBALS["CURL_LAST_SIZE_DOWNLOAD"]/1024);
				$STATUS["LAST_DOWNLOAD"]["FAILED"]=$ERRORDB;
				@file_put_contents("/etc/artica-postfix/ARTICAUFDB_LAST_DOWNLOAD", serialize($STATUS));
				continue;
			}
			

			
			
			$prc=($c/$MAx)*100;
			updatev2_progress2($prc,"$tablename ok");
			$GLOBALS["UFDB_SIZE"]=$GLOBALS["CALCULATED_SIZE"];
			

			$size=$unix->file_size("$WORKDIR/$tablename/domains.ufdb");
			$size=round(($size/1024),2);
			$BigSize=$BigSize+$size;
			@chown("$WORKDIR/$tablename", "squid");
			@chgrp("$WORKDIR/$tablename", "squid");	
			$LOCAL_CACHE[$tablename]=$OriginalSize;	
			$GLOBALS["EVENTS"][]="Success updating category `$tablename` with $size Ko";
			
			$STATUS["LAST_DOWNLOAD"]["TIME"]=time();
			$STATUS["LAST_DOWNLOAD"]["SIZE"]=($GLOBALS["CURL_LAST_SIZE_DOWNLOAD"]/1024);
			$STATUS["LAST_DOWNLOAD"]["FAILED"]=$ERRORDB;
			@file_put_contents("/etc/artica-postfix/ARTICAUFDB_LAST_DOWNLOAD", serialize($STATUS));						
		}
		
	}
	
	
	@file_put_contents($CACHE_FILE, base64_encode(serialize($LOCAL_CACHE)));
	updatev2_progress2(100,"DONE ok");
	$ufdbguard_admin_memory=@implode("\n", $GLOBALS["ufdbguard_admin_memory"]);	
	$php5=$unix->LOCATE_PHP5_BIN();
	if(!is_file("/opt/ufdbcat/bin/ufdbcatdd")){
		system("$php5 /usr/share/artica-postfix/exec.ufdbcat.php --install --noupdate");
	}
	
	
	if($c>0){
		$BigSizeMB=round($BigSize/1024,2);
		shell_exec("/etc/init.d/ufdbcat reload");
		squid_admin_mysql(2, "Artica Web filtering Databases Success updated $c categories {$BigSizeMB}MB extracted on disk","$ufdbguard_admin_memory".@implode("\n", $GLOBALS["EVENTS"]),__FUNCTION__,__LINE__);
		artica_update_event(2, "Artica Web filtering Databases Success updated $c categories {$BigSizeMB}MB extracted on disk","$ufdbguard_admin_memory".@implode("\n", $GLOBALS["EVENTS"]),__FUNCTION__,__LINE__);
		// ufdbguard_admin_events("UFDB::Success update $c categories $BigSize extracted\n$ufdbguard_admin_memory",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
		@file_put_contents($UFDBGUARD_LAST_INDEX_TIME, $source_filetime);
		shell_exec("/etc/init.d/ufdbcat reload");
		META_MASTER_UFDBTABLES(true);
		calculate_categorized_websites(true);
		
	}else{
		if($GLOBALS["FORCE"]){
			echo "No update available\n$ufdbguard_admin_memory\n";
		}
	}
	
		
	
	@chown("$WORKDIR", "squid");
	@chgrp("$WORKDIR", "squid");
	updatev2_adblock();
	scan_artica_databases();
	updatev2_progress(100,"{done} [".__LINE__."]");
	return true;
	
	
}

function ufdbtables_DownloadInstall($URIBASE,$tablename,$OriginalSize=0){
	if(isset($GLOBALS["ufdbtables_DownloadInstall"][$tablename])){return;}
	$GLOBALS["ufdbtables_DownloadInstall"][$tablename]=true;
	$CACHE_FILE="/etc/artica-postfix/ufdb.tables.db";
	$WORKDIR=$GLOBALS["WORKDIR_LOCAL"];
	$unix=new unix();
	$tmpdir=$unix->TEMP_DIR()."/ufdb-temp";
	@mkdir($tmpdir,0755,true);
	$LOCAL_CACHE=unserialize(base64_decode(@file_get_contents($CACHE_FILE)));
	$STATUS=unserialize(@file_get_contents("/etc/artica-postfix/ARTICAUFDB_LAST_DOWNLOAD"));
	
	
	
	
	if($OriginalSize>0){
		echo "UFDB: downloading $tablename remote size:$OriginalSize, local size:{$LOCAL_CACHE[$tablename]}\n";
		$GLOBALS["EVENTS"][]="downloading $tablename remote size:$OriginalSize, local size:{$LOCAL_CACHE[$tablename]}";
	}
	updatev2_progress(20,"Downloading $tablename.gz [".__LINE__."]");
	$curl=new ccurl("$URIBASE/$tablename.gz");
	ufdbevents("Downloading $URIBASE/$tablename.gz to $tmpdir/$tablename.gz");
	$curl->Timeout=380;
	if(!$curl->GetFile("$tmpdir/$tablename.gz")){
		ufdbevents("Unable to download blacklist $tablename.gz");
		ufdbevents(@implode("\n", $GLOBALS["EVENTS"]));
		@unlink("$tmpdir/$tablename.gz");
		artica_update_event(0,"Unable to download blacklist $tablename.gz file $curl->error",@implode("\n", $GLOBALS["EVENTS"]),__FUNCTION__,__LINE__);
		squid_admin_mysql(1,"Unable to download blacklist $tablename.gz file $curl->error",@implode("\n", $GLOBALS["EVENTS"]),__FUNCTION__,__LINE__);
		return;
	}
	
	$GLOBALS["UFDB_SIZE"]=$GLOBALS["CALCULATED_SIZE"];
	$GLOBALS["CALCULATED_SIZE"]=$GLOBALS["CALCULATED_SIZE"] + intval(@filesize("$tmpdir/$tablename.gz"));


	@mkdir("$WORKDIR/$tablename",0755,true);
	if(!ufdbtables_uncompress("$tmpdir/$tablename.gz","$WORKDIR/$tablename/domains.ufdb")){
		ufdbevents("Unable to extract blacklist $tablename.gz");
		@unlink("$tmpdir/$tablename.gz");
		squid_admin_mysql(0,"Unable to extract blacklist $tablename.gz",null,__FUNCTION__,__LINE__);
		artica_update_event(0,"Unable to extract blacklist $tablename.gz",null,__FUNCTION__,__LINE__);
		ufdbguard_admin_memory("UFDB::Fatal: unable to extract blacklist $tablename.gz file",__FUNCTION__,__FILE__,__LINE__,
		"ufbd-artica");
		return;
	}
	@unlink("$tmpdir/$tablename.gz");
	@chown("$WORKDIR/$tablename/domains.ufdb", "squid");
	@chgrp("$WORKDIR/$tablename/domains.ufdb", "squid");
	$Md5=md5_file("$WORKDIR/$tablename/domains.ufdb");
	ufdbevents("$WORKDIR/$tablename/domains.ufdb = $Md5");
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
    ufdbevents("Uncompress $srcName to $dstName Done");
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
		// ufdbguard_admin_events("Fatal: unable to download blacklist index file $curl->error",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
		echo "BLACKLISTS: Failed to retreive $URIBASE/catz/index.txt ($curl->error)\n";
		return;
	}

	$f=unserialize(base64_decode(@file_get_contents("$tmpdir/index.txt")));
	if(!is_array($f)){
		squid_admin_mysql(0,"$tmpdir/index.txt file, no such array",null,__FUNCTION__,__LINE__);
		artica_update_event(0,"$tmpdir/index.txt file, no such array",null,__FUNCTION__,__LINE__);
		return;
	}	
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
		squid_admin_mysql(0,"BLACKLISTS: Failed to retreive $URIBASE/catz/index.txt ",$curl->error,__FUNCTION__,__LINE__);
		artica_update_event(0,"BLACKLISTS: Failed to retreive $URIBASE/catz/index.txt ",$curl->error,__FUNCTION__,__LINE__);
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
	$dirs=$unix->dirdir($GLOBALS["WORKDIR_LOCAL"]);
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
		if(!$q->ok){
			squid_admin_mysql(0,"$q->mysql_error",null,__FUNCTION__,__LINE__);
			artica_update_event(0,"$q->mysql_error",null,__FUNCTION__,__LINE__);
		}
	}
}
	
	




function updatev2_checkversion(){
	if(isset($GLOBALS["updatev2_checkversion"])){return null;}
	$GLOBALS["updatev2_checkversion"]=true;
	$GLOBALS["MIRROR"]=null;
	$unix=new unix();
	$tmpdir=$unix->TEMP_DIR();
	
	$unix=new unix();
	$URIBASE=$unix->MAIN_URI();
	$sock=new sockets();
	$ArticaDbReplicate=$sock->GET_INFO("ArticaDbReplicate");
	if(!is_numeric($ArticaDbReplicate)){$ArticaDbReplicate=0;}
	
	
	$MirrorsA[]="http://mirror.articatech.net/ufdb";
	//$MirrorsA[]="$URIBASE/ufdb";
	$MirrorsA[]="http://s497977761.onlinehome.fr";
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__." Line [".__LINE__."] ArticaDbReplicate = $ArticaDbReplicate\n";}
	
	
	
	if($ArticaDbReplicate==0){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." Line [".__LINE__."] shuffle()\n";}
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
	
	$ZORDERS=array();
	updatev2_progress(10,"{checking} {repositories} [".__LINE__."]");
	while (list ($num, $uri) = each ($Mirrors) ){
		$GLOBALS["EVENTS"][]="Checking Repository: $uri";
		updatev2_progress(10,"{checking} $uri [".__LINE__."]");
		
		$NewUri="$uri/ufdb/CATZ_ARRAY";
		$tmpfile="/$tmpdir/articatechdb.".md5($NewUri).".tmp";
		$curl=new ccurl($NewUri,false,null,true);
		$curl->Timeout=60;
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
			@unlink($tmpfile);
			continue;
		}
		if(!isset($array["TIME"])){
			$GLOBALS["EVENTS"][]="$uri: Failed with error No TIME CODE";
			@unlink($tmpfile);
			continue;
		}
		
		
		$xdate=date("l d F Y H:i:s",$array["TIME"]);
		updatev2_progress(10,"$uri: Available version: $xdate - {$array["TIME"]} [".__LINE__."]");
		ufdbevents("$uri: Available version: $xdate - {$array["TIME"]}/". date("Y-m-d H:i:s",$array["TIME"]));
		$GLOBALS["EVENTS"][]="$uri: Available version: $xdate - {$array["TIME"]}/". date("Y-m-d H:i:s",$array["TIME"]);
		echo "[".__LINE__."]: Available version: $xdate - {$array["TIME"]}/". date("Y-m-d H:i:s",$array["TIME"])."\n";
		
		$ZORDERS[$array["TIME"]]["URI"]=$uri;
		$ZORDERS[$array["TIME"]]["TMPFILE"]=$tmpfile;

		}
	
	
	if(count($ZORDERS)==0){
		updatev2_progress(110,"Unable to find a suitable mirror [".__LINE__."]");
		events("Unable to find a suitable mirror");
		return ;
		
	}
	
	
	krsort($ZORDERS);
	
	while (list ($num, $MAIN) = each ($ZORDERS) ){
		$uri=$MAIN["URI"];
		$tmpfile=$MAIN["TMPFILE"];
		ufdbevents("* * * * $uri: Best mirror: $num $uri [".__LINE__."] * * * *");
		updatev2_progress(10,"$uri: Best mirror: $num [".__LINE__."]");
		@unlink($destinationfile);
		@copy($tmpfile, $destinationfile);
		@chmod($destinationfile,0755);
		$GLOBALS["MIRROR"]=$uri;
		break;
		
	}

	reset($ZORDERS);
	while (list ($num, $MAIN) = each ($ZORDERS) ){	
		ufdbevents("Removing $tmpfile [".__LINE__."]");
		$tmpfile=$MAIN["TMPFILE"];
		@unlink($tmpfile);
		
	}
					
		
		
	
	if($GLOBALS["MIRROR"]==null){
		updatev2_progress(110,"Error, unable to find a suitable {repositories} [".__LINE__."]");
		ufdbevents("error, unable to find a suitable mirror");
		squid_admin_mysql(0,"Unable to find a suitable mirror",null,__FUNCTION__,__LINE__);
		artica_update_event(0,"Unable to find a suitable mirror",null,__FUNCTION__,__LINE__);
		return null;
	}

	if($ArticaDbReplicate){
		@file_put_contents($tmpfile, "/home/articatechdb.version");
	}
	updatev2_progress(10,"{done} [".__LINE__."]");
	
}

function updatev2_progress($num,$text){
	build_progress($text, $num);
	$array["POURC"]=$num;
	$array["TEXT"]=$text." ".date("Y-m-d H:i:s");
	if($GLOBALS["VERBOSE"]){echo "{$num}% $text\n";}
	$unix=new unix();
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
	
		if(isset($trace[0])){
			$file=basename($trace[0]["file"]);
			$function=$trace[0]["function"];
			$line=$trace[0]["line"];
		}
	
		if(isset($trace[1])){
			$file=basename($trace[1]["file"]);
			$function=$trace[1]["function"];
			$line=$trace[1]["line"];
		}
	
	
	
	}
	
	
	
	$unix->events($text,"/var/log/artica-ufdb.log",false,$function,$line,$file);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/cache/articatechdb.progress", serialize($array));
}
function updatev2_progress2($num,$text){
	$array["POURC"]=$num;
	$array["TEXT"]=$text." ".date("Y-m-d H:i:s");
	if($GLOBALS["VERBOSE"]){echo "{$num}% $text\n";}
	$unix=new unix();
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
	
		if(isset($trace[0])){
			$file=basename($trace[0]["file"]);
			$function=$trace[0]["function"];
			$line=$trace[0]["line"];
		}
	
		if(isset($trace[1])){
			$file=basename($trace[1]["file"]);
			$function=$trace[1]["function"];
			$line=$trace[1]["line"];
		}
	
	
	
	}
	
	$unix->events($text,"/var/log/artica-ufdb.log",false,$function,$line,$file);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/cache/webfilter-artica.progress", serialize($array));
}
function tests_pub($pattern){
	$main_artica_path=$GLOBALS["WORKDIR_LOCAL"];
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
	$main_artica_path=$GLOBALS["WORKDIR_LOCAL"];
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
		// ufdbguard_admin_events("UFDB::Fatal: $pubgzip failed to download $curl->error",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
		@unlink("$pubgzip");
		
	}
	
	@unlink($trackergzip);
	$curl=new ccurl("{$GLOBALS["MIRROR"]}/tracker_expressions.gz");
	if(!$curl->GetFile($trackergzip)){
		if($GLOBALS["VERBOSE"]){echo "$trackergzip failed to download $curl->error\n";}
		// ufdbguard_admin_events("UFDB::Fatal: $trackergzip failed to download $curl->error",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
		@unlink($trackergzip);
	}

	@unlink($malwaregzip);
	$curl=new ccurl("{$GLOBALS["MIRROR"]}/categoryuris_malware.gz");
	if(!$curl->GetFile($malwaregzip)){
		// ufdbguard_admin_events("UFDB::Fatal: $malwaregzip failed to download $curl->error",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
		if($GLOBALS["VERBOSE"]){echo "$malwaregzip failed to download $curl->error\n";}
		@unlink($malwaregzip);
	}	
	
	@unlink($phishgzip);
	$curl=new ccurl("{$GLOBALS["MIRROR"]}/categoryuris_phishing.gz");
	if(!$curl->GetFile($phishgzip)){
		// ufdbguard_admin_events("UFDB::Fatal: $phishgzip failed to download $curl->error",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
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
	artica_update_event(2,"New Artica Database statistics $LOCAL_VERSION updated took:$took",null,__FILE__,__LINE__,"ufbd-artica");
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
	// ufdbguard_admin_events("New Artica Database statistics $LOCAL_VERSION updated took:$took.",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
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
	updatev2_progress(10,"{checking} [".__LINE__."]");
	
	$timeFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$ArticaDbReplicate=$sock->GET_INFO("ArticaDbReplicate");
	$CategoriesDatabasesByCron=$sock->GET_INFO("CategoriesDatabaseByCron");
	if(!is_numeric($CategoriesDatabasesByCron)){$CategoriesDatabasesByCron=1;}
	
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	$ArticaDBPath=$sock->GET_INFO("ArticaDBPath");
	if($ArticaDBPath==null){$ArticaDBPath="/opt/articatech";}
	$ManualArticaDBPath=$sock->GET_INFO("ManualArticaDBPath");
	if($ManualArticaDBPath==null){$ManualArticaDBPath="/home/manualupdate/articadb.tar.gz";}
	$ManualArticaDBPathNAS=$sock->GET_INFO("ManualArticaDBPathNAS");
	$datas=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
	updatev2_progress(10,"{checking} [".__LINE__."]");
	if(!is_numeric($ManualArticaDBPathNAS)){$ManualArticaDBPathNAS=0;}
	
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	if(!is_numeric($ArticaDbReplicate)){$ArticaDbReplicate=0;}
	$WizardStatsAppliance=unserialize(base64_decode($sock->GET_INFO("WizardStatsAppliance")));
	if(!isset($WizardStatsAppliance["SERVER"])){$WizardStatsAppliance["SERVER"]=null;}
	
	if($DisableArticaProxyStatistics==1){
		updatev2_progress(110,"Error: Artica statistics are disabled");
	}
	
	if($datas["UseRemoteUfdbguardService"]==1){
		updatev2_progress(110,"Error: - UseRemoteUfdbguardService -  Only used by {$WizardStatsAppliance["SERVER"]}");
		return;
	}
	
	
	
	if(!$GLOBALS["FORCE"]){
		if($CategoriesDatabasesByCron==1){
			if(!$GLOBALS["BYCRON"]){
				updatev2_progress(110,"Error: Only executed by schedule...");
				return;
			}
		}
	}
	

	
	
	if($GLOBALS["FORCE"]){
			ufdbevents("***** Force enabled ***** ");
			ufdbevents("*****");
			ufdbevents("*****");
			ufdbevents("Executed as {$GLOBALS["CMDLINE"]}");
			ufdbevents("*****");
			ufdbevents("*****");
	
	}
	if(!$GLOBALS["CHECKTIME"]){ufdbevents("***** CHECKTIME disabled ***** ");}
	$CHECKTIME=$unix->file_time_min($timeFile);
	ufdbevents(" **");
	ufdbevents(" **");
	ufdbevents("$timeFile = {$CHECKTIME}Mn");
	ufdbevents(" **");
	ufdbevents(" **");
	if(!$GLOBALS["FORCE"]){
		if($CHECKTIME<240){
			updatev2_progress(110,"STOP: current {$CHECKTIME}Mn, require 240mn");
			return;
		}
	}
	
	
	
	
	
	updatev2_progress(10,"{checking} [".__LINE__."]");
	$pid=@file_get_contents($pidfile);
	
		if($unix->process_exists($pid,__FILE__)){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($time<10200){
				updatev2_progress(110,"Error: already running pid $pid since {$time}Mn");
				return;
			}
			else{
				$kill=$unix->find_program("kill");
				unix_system_kill_force($pid);
				if($GLOBALS["SCHEDULE_ID"]>0){
					artica_update_event(1, "Warning: Old task pid $pid since {$time}Mn has been killed, (reach 7200mn)", null,__FILE__,__LINE__);
				}
		}
	}
	
	updatev2_progress(10,"{checking} [".__LINE__."]");
	ufdbevents("Stamp $timeFile");
	@unlink($timeFile);
	$tlse_force_token=null;
	@file_put_contents($timeFile, time());	
	@file_put_contents($pidfile, getmypid());
	$tlse_token=null;
	if($GLOBALS["BYCRON"]){$tlse_token==" --bycron --force";}
	if($GLOBALS["FORCE"]){$tlse_force_token=" --force";}
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	ufdbevents("Running  exec.update.squid.tlse.php");
	shell_exec("$nohup $php /usr/share/artica-postfix/exec.update.squid.tlse.php --schedule-id={$GLOBALS["SCHEDULE_ID"]}$tlse_force_token$tlse_token >/dev/null 2>&1 &");
	
	updatev2_progress(10,"{checking} [".__LINE__."]");
	updatev2_checkversion();
	updatev2_progress(12,"{runing} [".__LINE__."]");
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
	$badDomains["il"]=true;
		
	$tables=$q->LIST_TABLES_CATEGORIES();
	while (list ($table,$none0) = each ($tables) ){
		if($table==null){continue;}
		if(strpos($table, ",")>0){$q->QUERY_SQL("DROP table `$table`"); continue;}
		if(blacklisted_tables($table)){continue;}
		if(!$q->TABLE_EXISTS($table)){continue;}
		
		reset($badDomains);
		while (list ($extensions,$none) = each ($badDomains) ){
		$q->QUERY_SQL("DELETE FROM $table WHERE pattern='$extensions'");	
		}
	}	
}


function blacklisted_tables($tablename){
	$array["category_stockexchnage"]=true;
	$array["category_association"]=true;
	$array["category_smalladds"]=true;
	if(strpos($tablename, ",")>0){return true;}
	if(isset($array[$tablename])){return true;}
	
}
function build_progress($text,$pourc){
	WriteMyLogs("{$pourc}% $text",__FUNCTION__,__FILE__,__LINE__);
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/artica-webfilterdb.progress";
	echo "[{$pourc}%] $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
	if($GLOBALS["OUTPUT"]){sleep(2);}


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
	
	
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.update.squid.tlse.php $schedule>/dev/null 2>&1 &");
}






function categorize_delete(){
	$unix=new unix();
	$URIBASE=$unix->MAIN_URI();
	$tmpdir=$unix->TEMP_DIR();
	if(!is_file("$tmpdir/categorize_delete.sql")){
	$curl=new ccurl("$URIBASE/blacklist/categorize_delete.gz");
	if(!$curl->GetFile("$tmpdir/categorize_delete.gz")){
		// ufdbguard_admin_events("Fatal: unable to download categorize_delete.gz file $curl->error",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
		return;
	}

	if(!extractGZ("$tmpdir/categorize_delete.gz","$tmpdir/categorize_delete.sql")){
			// ufdbguard_admin_events("Fatal: unable to extract $tmpdir/categorize_delete.gz",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
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
	
	// ufdbguard_admin_events("Success updating deleted ". count($datas)." websites from categories",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
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
	$AsCategoriesAppliance=intval($sock->GET_INFO("AsCategoriesAppliance"));
	
	if(!is_numeric($CategoriesRepositoryEnable)){$CategoriesRepositoryEnable=0;}
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	
	
	if($GLOBALS["VERBOSE"]){echo "AsCategoriesAppliance.......: $AsCategoriesAppliance\n";}
	if($GLOBALS["VERBOSE"]){echo "CategoriesRepositoryEnable..: $CategoriesRepositoryEnable\n";}
	if($GLOBALS["VERBOSE"]){echo "EnableWebProxyStatsAppliance: $EnableWebProxyStatsAppliance\n";}
	if($GLOBALS["VERBOSE"]){echo "SQUID_INSTALLED.............: $users->SQUID_INSTALLED\n";}
	
	if($AsCategoriesAppliance==1){return true;}

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
	// ufdbguard_admin_events($text,$function,basename(__FILE__),$line);
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
	$logFile="/var/log/webfiltering-update.log";
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
?>				