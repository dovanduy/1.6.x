<?php
	$GLOBALS["SCHEDULE_ID"]=0;
	if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
	include_once(dirname(__FILE__) . '/ressources/class.templates.inc');
	include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
	include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
	include_once(dirname(__FILE__) . '/ressources/class.artica.inc');
	include_once(dirname(__FILE__) . '/ressources/class.rtmm.tools.inc');
	include_once(dirname(__FILE__) . '/ressources/class.squid.inc');
	include_once(dirname(__FILE__) . '/ressources/class.dansguardian.inc');
	include_once(dirname(__FILE__) . '/framework/class.unix.inc');
	include_once(dirname(__FILE__) . '/framework/frame.class.inc');	
	include_once(dirname(__FILE__) . "/ressources/class.categorize.externals.inc");
	
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--reinstall#",implode(" ",$argv))){$GLOBALS["REINSTALL"]=true;}
	if(preg_match("#--no-httpd-conf#",implode(" ",$argv))){$GLOBALS["NO_HTTPD_CONF"]=true;}
	if(preg_match("#--noreload#",implode(" ",$argv))){$GLOBALS["NO_HTTPD_RELOAD"]=true;}
	if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
}

	
if($argv[1]=="--getcatz"){print_r(LIST_TABLES_CATEGORIES());die();}
if($argv[1]=="--build"){Buildrepo();die();}		
if($argv[1]=="--compress"){compress_categories();die();}
if($argv[1]=="--v2"){export_version2();die();}
if($argv[1]=="--ufdb"){compile_databases();die();}
if($argv[1]=="--ufdb-compress"){compile_databases_compress();die();}
if($argv[1]=="--ufdb-repo"){compile_databases_repo();die();}
if($argv[1]=="--ufdb-index"){compile_databases_index();die();}
if($argv[1]=="--backup-catz"){backup_categories();exit;}
if($argv[1]=="--backup-catz-mysql"){FillMysqlDatabase();exit;}
if($argv[1]=="--import-backuped-categories"){import_backuped_categories($argv[2]);exit;}
if($argv[1]=="--empty-perso-catz"){empty_personal_categories();exit;}



echo "Unable to understand ".@implode(" ", $argv)."\n";


function Buildrepo(){
	$cats=LIST_TABLES_CATEGORIES();
	while (list ($index, $category_table) = each ($cats) ){
		echo "<H5 style='font-size:14px;font-weight:bold;color:red'>Extracting table $category_table<H5>";
		ExportCategory($category_table);
	}	
	
}

function build_categories(){
	
	$cats=LIST_TABLES_CATEGORIES();
	$t1=time();
	while (list ($index, $category_table) = each ($cats) ){
		echo "<H5 style='font-size:14px;font-weight:bold;color:red'>Extracting table $category_table<H5>";
		ExportCategory($category_table);
	}
	
	compress_categories();
	
	$distanceOfTimeInWords=distanceOfTimeInWords($t1,time(),true);
	sendmail("[artica.fr]: blacklists: ". count($cats)." done $distanceOfTimeInWords","$category: $xtotal lines\n$countdefichiers files\n".@implode("\n",$skl_logs));
	
	
	echo "<H1> FINISH !</H1>";
}


function compress_categories(){
	$dir=dirname(__FILE__);
	$f[]="[settings]";
	$f[]="date=".date("Y-m-d H:i:s");
	$q=new mysql_squid_builder();
	$cats=LIST_TABLES_CATEGORIES();
	while (list ($index, $category_table) = each ($cats) ){
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT category FROM $category_table LIMIT 0,1"));
		$category=trim($ligne2["category"]);
		if($category<>null){
			$f[]="[$category]";
			$f[]=compress_cat($category);
		}
		
	}
	
	$datas=@implode("\n",$f);
	$fh = fopen("$dir/blacklist/update.ini", 'w+');
	fwrite($fh, $datas);
	fclose($fh);
	echo "[artica.fr]: Compressing categories done\n";
}

function compress_cat($category){
	$dir=dirname(__FILE__);
	@mkdir("$dir/blacklist/sources",0755,true);
	if(trim($category)==null){return;}
	echo "Compressing category $category ($category.*.blk)\n";
	$category=str_replace("/","-",$category);
	foreach (glob("$dir/blacklist/$category.*.blk") as $filename) {
		echo "found $filename\n";
		xflush();
		$size=filesize($filename);
		if($size==0){@unlink($filename);continue;}
		$nextFile=$filename.".gz";
		
		compress($filename,$nextFile);
		if(!is_file($nextFile)){echo "$nextFile Unable to compress...\n";continue;}
		@copy($filename, "$dir/blacklist/sources/".basename($filename));
		@unlink($filename);
		$size=filesize($nextFile);
		$nextFile=basename($nextFile);
		$f[]="blacklist/$nextFile=$size";
		
		
	}
	
	return @implode("\n",$f);
	
}

function MustDefrag(){
	$cats=LIST_TABLES_CATEGORIES();
	$t1=time();
	while (list ($index, $category_table) = each ($cats) ){
		$sql="SHOW KEYS FROM $category_table WHERE Key_name='pattern'";
		$ligne2=mysql_fetch_array(QUERY_SQL($sql,null,0,"articafr9"));
		if($GLOBALS["ARTICA_MYSQL_ERROR"]<>null){echo "<H2>{$GLOBALS["ARTICA_MYSQL_ERROR"]}<br>$sql</H2>";return;}
		if($ligne2["Non_unique"]==1){
			$rows1=articafr9_COUNT_ROWS($category_table);
			echo "<li style='font-size:13px'><a href='compile-wwwv2.php?defrag=$category_table'>$category_table ($rows1 rows)</a></li>";
		}
	}	
}

function uncompress($srcName, $dstName) {
    $sfp = gzopen($srcName, "rb");
    $fp = fopen($dstName, "w");

    while ($string = gzread($sfp, 4096)) {
        fwrite($fp, $string, strlen($string));
    }
    gzclose($sfp);
    fclose($fp);

} 
function compress($source,$dest){
    
    $mode='wb9';
    $error=false;
    if(is_file($dest)){@unlink($dest);}
    $fp_out=gzopen($dest,$mode);
    if(!$fp_out){return;}
    $fp_in=fopen($source,'rb');
    if(!$fp_in){return;}
    
    while(!feof($fp_in)){
    	gzwrite($fp_out,fread($fp_in,1024*512));
    }
    fclose($fp_in);
    gzclose($fp_out);
    echo "Compress: $dest (". filesize($dest)." bytes) done\n";
	return true;
}

function ExportTableDelete(){
	$sql="SELECT * FROM categorize_delete WHERE enabled=1";
	$results=QUERY_SQL($sql,null,0,"articafr");
	$fh = fopen("blacklist/categorize_delete.sql", 'w+');
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$line=serialize($ligne)."\n";
		fwrite($fh, $line);
	}
	@mysql_close($GLOBALS["MYSQLCONNECTIONID"]);
	fclose($fh);
	compress("blacklist/categorize_delete.sql","blacklist/categorize_delete.gz");	
	
}

function ExportCategory($category_table){
	$q=new mysql_squid_builder();
	$dir=dirname(__FILE__);
	$t1=time();
	$total=$q->COUNT_ROWS($category_table);
	$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT category FROM $category_table LIMIT 0,1",null,0,"articafr9"));
	$category=$ligne2["category"];
	echo "$category: $total lines\n";
	$pages=1;
	if($total>5000){
		$pages=round($total/5000);
		echo "<hr>";
	}
	@mkdir("$dir/blacklist",0755,true);
	$countdefichiers=0;
	for($i=0;$i<=$pages;$i++){
		$startsql=0;
		$endsql=5000;
		if($i>0){$startsql=$i*5000;}
		$sql="SELECT * FROM $category_table WHERE enabled=1 ORDER BY zDate LIMIT $startsql,5000";
		
		
		$results=$q->QUERY_SQL($sql);
		$count_rows=mysql_num_rows($results);
		$skl_logs[]="$sql ($count_rows rows)";
		echo "$category_table: $sql=$count_rows rows $i/$pages - blacklist/$category.$i.blk\n";
		xflush();
		$category=str_replace("/","-",$category);
		
		$c=0;
		$timeout=0;
		$fh = fopen("$dir/blacklist/$category.$i.blk", 'w+');
		$countdefichiers++;
		$xtotal=0;
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$line=serialize($ligne)."\n";
			$c++;
			$timeout++;
			$xtotal++;
			if($timeout>500){echo "500\n";$timeout=0;}
			fwrite($fh, $line);
		}
		fclose($fh);
		$size=filesize("$dir/blacklist/$category.$i.blk")/1024;
		echo "Saving $category.$i.blk ({$size}Ko) $c rows) done\n";
		xflush();
		
		if($count_rows<5000){break;}
		
	}
	$distanceOfTimeInWords=distanceOfTimeInWords($t1,time(),true);
	echo "[artica.fr]: blacklist: $category ($countdefichiers files) done 
	$distanceOfTimeInWords\n$category: $xtotal lines\n$countdefichiers files\n".@implode("\n",$skl_logs);
	
	
}
function sendmail($subject,$message){
	
     $to      = 'david@touzeau.eu';
     $headers = 'From: webmaster@artica.fr' . "\r\n" .
     'Reply-To: webmaster@artica.fr' . "\r\n" .
     'X-Mailer: PHP/' . phpversion();

     mail($to, $subject, $message, $headers);
     
	
}

function TABLE_EXISTS($table,$database='articafr',$nocache=false){
	if(!$nocache){if(!isset($GLOBALS["__MYSQL_TABLE_EXISTS"])){$GLOBALS["__MYSQL_TABLE_EXISTS"]=array();}if(isset($GLOBALS["__MYSQL_TABLE_EXISTS"][$database][$table])){if($GLOBALS["__MYSQL_TABLE_EXISTS"][$database][$table]==true){return true;}}}
	$sql="SHOW TABLES";
	$results=QUERY_SQL($sql,null,0,$database);
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(strtolower($table)==strtolower($ligne["Tables_in_$database"])){
		$GLOBALS["__MYSQL_TABLE_EXISTS"][$database][$table]=true;
		return true;
		}
	}
	
	return false;
		
}


function CREATE_CATEGORY_TABLE($table){
	
	$sql="CREATE TABLE `$table` (
				`zmd5` VARCHAR( 90 ) NOT NULL ,
				`zDate` DATETIME NOT NULL ,
				`category` VARCHAR( 20 ) NOT NULL ,
				`pattern` VARCHAR( 255 ) NOT NULL ,
				`enabled` INT( 1 ) NOT NULL DEFAULT '1',
				`uuid` VARCHAR( 255 ) NOT NULL ,
				`sended` INT( 1 ) NOT NULL DEFAULT '0',
				PRIMARY KEY ( `zmd5` ) ,
				KEY `zDate` (`zDate`),
	  			UNIQUE KEY `pattern` (`pattern`),
	  			KEY `enabled` (`enabled`),
	  			KEY `sended` (`sended`),
	  			KEY `category` (`category`)
			)";
			QUERY_SQL($sql,null,0,"articafr9");
			if($GLOBALS["ARTICA_MYSQL_ERROR"]<>null){return false;}	
			return true;
}

function xflush (){
    
    // check that buffer is actually set before flushing
       
        @ob_flush();
        @flush();
        @ob_end_flush();
		@ob_start();
}

function bifilesimport(){
	
	$f=new Fichiers();
    $h=$f->DirListTable('tmpf/categories',true);
    while (list ($num, $val) = each ($h) ){
    	if(preg_match("#category_(.+?)\.csv\.gz$#",$val,$re)){
    		if($re[1]=="phishtank"){continue;}
    		if($re[1]=="english_malware"){continue;}
    		if($re[1]=="forum"){$re[1]="forums";}
    		if($re[1]=="radio"){$re[1]="webradio";}
    		if($re[1]=="gambling"){$re[1]="gamble";}
    		if($re[1]=="drogue"){$re[1]="drugs";}
    		if($re[1]=="radiotv"){$re[1]="webradio";}
    		if($re[1]=="spywmare"){$re[1]="spyware";}
    		if($re[1]=="hobby_games"){$re[1]="games";}
    		if($re[1]=="press"){$re[1]="news";}
    		$catz["tmpf/categories/$val"]="category_{$re[1]}";
    		
    		
    	}
    	
    }
    
    while (list ($sourcefile, $desttable) = each ($catz) ){
    	$size=filesize($sourcefile);
    	$color="black";
    	$size=round($size/1024);
    	if($size>10000){$color="red";}
    	$error=null;
    	if(!TABLE_EXISTS($desttable,"articafr9")){
    		$error=" <span style='font-size:16px'>NO TABLE !! !</span>";
    	}
    	
    	echo "<li style='font-size:14px;color:$color'>$sourcefile ($size K) <strong><a href='compile-wwwv2.php?importcatz=$desttable&file=$sourcefile'>$desttable</a>$error</strong></li>";
    	
    }
	
	
}


function LIST_TABLES_CATEGORIES(){
		$remove["category_radio"]=true;
		$remove["category_radiotv"]=true;
		$remove["category_gambling"]=true;
		$remove["category_drogue"]=true;
		$remove["category_english_malware"]=true;
		$remove["category_forum"]=true;
		$remove["category_hobby_games"]=true;
		$remove["category_spywmare"]=true;
		$remove["category_phishtank"]=true;
	
	
		if(isset($GLOBALS["LIST_TABLES_CATEGORIES"])){if($GLOBALS["VERBOSE"]){echo "return array\n";}return $GLOBALS["LIST_TABLES_CATEGORIES"];}
		$array=array();
		$q=new mysql_squid_builder();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'category_%' ORDER BY table_name";
		
		if($GLOBALS["VERBOSE"]){echo $sql."\n";}
		
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;}
		

		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(isset($remove[$ligne["c"]])){continue;}
			$array[$ligne["c"]]=$ligne["c"];
		}
		
		$GLOBALS["LIST_TABLES_CATEGORIES"]=$array;
		return $array;
		
	}
	
function export_version2(){
	
	$t=time();
	$unix=new unix();
	$WORKDIR="/home/artica/webdbs";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	$oldpid=@file_get_contents($pidfile);
	$myfile=basename(__FILE__);
	if($unix->process_exists($oldpid,$myfile)){
		ufdbguard_admin_events("Task already running PID: $oldpid, aborting current task",__FUNCTION__,__FILE__,__LINE__,"stats");
		return;
	}
	
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);
	$chmod=$unix->find_program("chmod");
	echo "Creating directory $WORKDIR\n";
	@mkdir("$WORKDIR",0777,true);
	shell_exec("$chmod 777 $WORKDIR");	

	$f["TIME"]=$t;
	$q=new mysql_squid_builder();
	$trans=$q->TransArray();
	
	
	$cats=LIST_TABLES_CATEGORIES();
	$catsCount=count($cats);
	$t1=time();
	while (list ($index, $category_table) = each ($cats) ){
		if(!isset($trans[$category_table])){
			$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT category FROM $category_table LIMIT 0,1"));
			$category=$ligne2["category"];
		}else{
			$category=$trans[$category_table];
		}
		
		echo "Exporting $category_table for category '$category'\n";
		if(is_file("$WORKDIR/$category_table.csv")){@unlink("$WORKDIR/$category_table.csv");}
		$CountCategoryTableRows=$q->COUNT_ROWS("$category_table");
		if($CountCategoryTableRows==0){ufdbguard_admin_events("Exporting $category_table skipped, no data",__FUNCTION__,__FILE__,__LINE__,"backup");continue;}
		$sql="SELECT MD5(pattern) FROM $category_table WHERE enabled=1 
		INTO OUTFILE '$WORKDIR/$category_table.csv'
		FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\\n'";
		
		$q->QUERY_SQL($sql);
		if(!$q->ok){ufdbguard_admin_events("Exporting $category_table ($CountCategoryTableRows rows) failed $q->mysql_error task aborted",__FUNCTION__,__FILE__,__LINE__,"backup");return;}
		
		$f["TABLES"][$category]=$category_table;
		$f["TABLES_SIZE"][$category]=$CountCategoryTableRows;
		
		echo "Compressing  $WORKDIR/$category_table.csv\n";
		compress("$WORKDIR/$category_table.csv", "$WORKDIR/$category_table.gz");
		echo "Compressing  $WORKDIR/$category_table.csv done\n";
		@unlink("$WORKDIR/$category_table.csv");
		
	}
	$took=$unix->distanceOfTimeInWords($t1,time());
	@file_put_contents("$WORKDIR/index.txt", base64_encode(serialize($f)));
	ufdbguard_admin_events("Exporting $catsCount tables took:$took",__FUNCTION__,__FILE__,__LINE__,"backup");
	
	if(!is_file("/root/ftp-password")){ufdbguard_admin_events("Exporting ftp aborted /root/ftp-password no such file",__FUNCTION__,__FILE__,__LINE__,"backup");return;}
	if(!is_file("/root/catz_export_version2")){ufdbguard_admin_events("Exporting ftp aborted /root/catz_export_version2 no such file",__FUNCTION__,__FILE__,__LINE__,"backup");return;}
	
	$ftpass=@file_get_contents("/root/ftp-password");
	$ftpwww=trim(@file_get_contents("/root/catz_export_version2"));	
	
	if($ftpass==null){ufdbguard_admin_events("Exporting ftp aborted no username/password",__FUNCTION__,__FILE__,__LINE__,"backup");return;}
	if($ftpwww==null){ufdbguard_admin_events("Exporting ftp aborted no address set",__FUNCTION__,__FILE__,__LINE__,"backup");return;}
	$curl=$unix->find_program("curl");
	if(!is_file($curl)){ufdbguard_admin_events("Exporting ftp aborted curl, no such binary",__FUNCTION__,__FILE__,__LINE__,"backup");return;}

	reset($cats);
	$t1=time();
	$d=0;
	while (list ($index, $category_table) = each ($cats) ){
		if(is_file("$WORKDIR/$category_table.gz")){
			$d++;
			$t2=time();
			$resultsCurl=array();
			exec("$curl -T $WORKDIR/$category_table.gz $ftpwww/ --user $ftpass 2>&1",$resultsCurl);
			$took=$unix->distanceOfTimeInWords($t2,time());
			ufdbguard_admin_events("Exporting $ftpwww/$category_table.gz crypted table done took:$took\n".@implode("\n", $resultsCurl),__FUNCTION__,__FILE__,__LINE__,"backup");	
			@unlink("$WORKDIR/$category_table.gz");
		}
		
	}
	shell_exec("$curl -T $WORKDIR/index.txt $ftpwww/ --user $ftpass");
	$took=$unix->distanceOfTimeInWords($t1,time());
	ufdbguard_admin_events("Exporting $d crypted table to $ftpwww done took:$took",__FUNCTION__,__FILE__,__LINE__,"backup");
	
}

function compile_databases(){
	$unix=new unix();
	$ufdbGenTable=$unix->find_program("ufdbGenTable");
	if(!is_file($ufdbGenTable)){ufdbguard_admin_events("Task aborted ufdbGenTable no such binary",__FUNCTION__,__FILE__,__LINE__,"ufdbGenTable");return;}
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=@file_get_contents($pidfile);
	$myfile=basename(__FILE__);
	if($unix->process_exists($oldpid,$myfile)){
		ufdbguard_admin_events("Task already running PID: $oldpid, aborting current task",__FUNCTION__,__FILE__,__LINE__,"ufdbGenTable");
		return;
	}
	
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);	
	
	
	$q=new mysql_squid_builder();
	$workdir="/home/artica/ufdbDBs";
	@mkdir($workdir,0777,true);
	$tables=$q->TransArray();
	
		$q=new mysql_squid_builder();
		$badDomains[""]=true;
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
		$badDomains["biz"]=true;
		$badDomains["info"]=true;
		$badDomains["ee"]=true;
		
		
	
	
	while (list ($tablename, $category) = each ($tables) ){
		
		if(is_file("$workdir/$tablename/domains.ufdb")){
			echo "SKIP $tablename -> $category\n";
			continue;
		}
		
		//if($tablename=="category_shopping"){echo "skip $tablename\n";continue;}
		if($tablename=="category_translator"){echo "skip $tablename\n";continue;}
		
		//if($tablename=="category_recreation_travel"){echo "skip $tablename\n";continue;}
		
		reset($badDomains);
		while (list ($extensions,$none) = each ($badDomains) ){
			$q->QUERY_SQL("DELETE FROM $tablename WHERE pattern='$extensions'");
			if(!$q->ok){
				ufdbguard_admin_events("Compiling $tablename failed .$q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"ufdbGenTable");
				echo "$q->mysql_error !!! \n";
				break;
			}
		}
		
		echo "COMPILE $tablename -> $category\n";
		if(is_file("$workdir/$tablename/domains")){@unlink("$workdir/$tablename/domains");}
		if(is_dir("/tmp/$tablename")){shell_exec("/bin/rm -rf /tmp/$tablename");}
		@mkdir("/tmp/$tablename",0777,true);
		@chmod("/tmp/$tablename", 0777);
		@chmod("/tmp", 0777);
		if(!$q->TABLE_EXISTS($tablename)){$q->CreateCategoryTable(null,$tablename);}
		$sql="SELECT pattern FROM (SELECT pattern FROM $tablename WHERE enabled=1 ORDER BY pattern) as t INTO OUTFILE '/tmp/$tablename/domains' LINES TERMINATED BY '\n';";
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			ufdbguard_admin_events("Compiling $tablename failed .$q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"ufdbGenTable");
			echo "FATAL::".$q->mysql_error."\n";return;
		}
		if(is_dir("$workdir/$tablename")){shell_exec("/bin/rm -rf $workdir/$tablename");}
		@mkdir("$workdir/$tablename",true);
		@copy("/tmp/$tablename/domains", "$workdir/$tablename/domains");
		@unlink("/tmp/$tablename/domains");
		$categoryKey=compile_databases_categoryKey($category);
		@file_put_contents("$workdir/$tablename/urls", "\n");
		
		$u=" -u $workdir/$tablename/urls";
		$d=" -d $workdir/$tablename/domains";
		
		$cmd="$ufdbGenTable -n -q -W -t $categoryKey$d$u >/dev/null 2>&1";
		echo "$cmd\n";
		$t=time();
		shell_exec($cmd);
		$took=$unix->distanceOfTimeInWords($t,time());
		ufdbguard_admin_events("Compiling $tablename success took: $took\n$cmd",__FUNCTION__,__FILE__,__LINE__,"ufdbGenTable");
		@unlink("$workdir/$tablename/domains");
		
	}
	
	compile_databases_compress();
	compile_databases_repo();
	
}

function compile_databases_compress(){
	$unix=new unix();
	$workdir="/home/artica/ufdbDBs";
	$q=new mysql_squid_builder();
	$workdir="/home/artica/ufdbDBs";
	@mkdir($workdir,0777,true);
	$tables=$q->TransArray();	
	while (list ($tablename, $category) = each ($tables) ){
		if(is_file("$workdir/$tablename/domains.ufdb")){
			echo "Compress $tablename\n";
			if(is_file($workdir."/$tablename.gz")){@unlink($workdir."/$tablename.gz");}
			$t=time();
			compress("$workdir/$tablename/domains.ufdb", $workdir."/$tablename.gz");
			$took=$unix->distanceOfTimeInWords($t,time());
			ufdbguard_admin_events("Compressing $tablename success took: $took",__FUNCTION__,__FILE__,__LINE__,"ufdbGenTable");
			shell_exec("/bin/rm -rf $workdir/$tablename");
		}else{
			if(is_dir("$workdir/$tablename")){
				shell_exec("/bin/rm -rf $workdir/$tablename");
			}
		}
	}
}


function compile_databases_index(){
	$workdir="/home/artica/ufdbDBs";
	$unix=new unix();
	$q=new mysql_squid_builder();
	$workdir="/home/artica/ufdbDBs";
	@mkdir($workdir,0777,true);
	$tables=$q->TransArray();	
	$i=0;
	while (list ($tablename, $category) = each ($tables) ){	
		if($tablename=="category_translator"){echo "skip $tablename\n";continue;}
		if(!is_file("$workdir/$tablename.gz")){continue;}
		$size=$unix->file_size("$workdir/$tablename.gz");
		$array[$tablename]=$size;
		$sizeT=$size;
		$sizeT=$sizeT/1024;
		$sizeT=round($sizeT/1000,2);
		echo "[$i/$Max]:: $tablename ($size) - {$sizeT}M (indexing)\n";
	}
	
	@file_put_contents("$workdir/index.txt",base64_encode(serialize($array)));
}

function compile_databases_repo(){
	if(!is_file("/root/ftp-password")){return;}
	if(!is_file("/root/compile_databases_repo")){return;}
	$workdir="/home/artica/ufdbDBs";
	$unix=new unix();
	$q=new mysql_squid_builder();
	$workdir="/home/artica/ufdbDBs";
	@mkdir($workdir,0777,true);
	$tables=$q->TransArray();	
	$ftpass=@file_get_contents("/root/ftp-password");
	$ftpwww=trim(@file_get_contents("/root/compile_databases_repo"));	
	if($ftpass==null){return;}
	if($ftpwww==null){return;}
	$Max=count($tables);
	$t0=time();
	$curl=$unix->find_program("curl");
	if(!is_file($curl)){
		ufdbguard_admin_events("Exporting to repository failed curl no such binary".@implode("\n", $resultsCurl),__FUNCTION__,__FILE__,__LINE__,"ufdbGenTable");
		return;	
	}
	$i=0;
	$d=0;
	while (list ($tablename, $category) = each ($tables) ){
		
		$i++;
		if(!is_file("$workdir/$tablename.gz")){continue;}
		$d++;
		$size=$unix->file_size("$workdir/$tablename.gz");
		$array[$tablename]=$size;
		$sizeT=$size;
		$sizeT=$sizeT/1024;
		$sizeT=round($sizeT/1000,2);
		echo "[$i/$Max]:: $tablename ($size) - {$sizeT}M (uploading)\n";
		$resultsCurl=array();
		$t=time();
		exec("$curl -T $workdir/$tablename.gz $ftpwww/ --user $ftpass",$resultsCurl);
		$took=$unix->distanceOfTimeInWords($t,time());
		ufdbguard_admin_events("[$i/$Max]: uploading $ftpwww/$tablename.gz ($sizeT K) success took: $took\n".@implode("\n", $resultsCurl),__FUNCTION__,__FILE__,__LINE__,"ufdbGenTable");
		
	}
	
	
	@file_put_contents("$workdir/index.txt",base64_encode(serialize($array)));
	shell_exec("curl -T $workdir/index.txt $ftpwww/ --user $ftpass");
	
	$took=$unix->distanceOfTimeInWords($t0,time());
	ufdbguard_admin_events("Exporting $d compiled tables success took: $took\n$cmd",__FUNCTION__,__FILE__,__LINE__,"ufdbGenTable");	
	
	
}

function compile_databases_categoryKey($category){
		$category_compile=$category;
		if(strpos($category, "other")>0){$category_compile=str_replace("/", "", $category);}
		if(preg_match("#.+?\/(.+)#", $category_compile,$re)){$category_compile=$re[1];}
		if(strlen($category_compile)>15){
			$category_compile=str_replace("recreation_","recre_",$category_compile);
			$category_compile=str_replace("automobile_","auto_",$category_compile);
			$category_compile=str_replace("finance_","fin_",$category_compile);
			if(strlen($category_compile)>15){
				$category_compile=str_replace("_", "", $category_compile);
				if(strlen($category_compile)>15){$category_compile=substr($category_compile, strlen($category_compile)-15,15);}
			}
		}
		
	return $category_compile;
}

function backup_categories(){
	$unix=new unix();
	$WORKDIR="/home/squid/categories_backuped";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	$oldpid=@file_get_contents($pidfile);
	$myfile=basename(__FILE__);
	if($unix->process_exists($oldpid,$myfile)){
		ufdbguard_admin_events("Task already running PID: $oldpid, aborting current task",__FUNCTION__,__FILE__,__LINE__,"stats");
		return;
	}
	
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);
	$chmod=$unix->find_program("chmod");
	echo "Creating directory $WORKDIR\n";
	@mkdir("$WORKDIR",0777,true);
	shell_exec("$chmod 777 $WORKDIR");
	$tar=$unix->find_program("tar");	
	$t=time();
	$q=new mysql_squid_builder();
	$catArray=$q->LIST_TABLES_CATEGORIES();
	while (list ($category_table, $category_table2) = each ($catArray) ){
		$t1=time();
		$sql="SELECT pattern FROM $category_table WHERE enabled=1 
		INTO OUTFILE '$WORKDIR/$category_table.csv'
		FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\\n'";		
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			ufdbguard_admin_events("Fatal: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"backup");
			continue;
		}
		
		if(!is_file("$WORKDIR/$category_table.csv")){
			ufdbguard_admin_events("Fatal: $WORKDIR/$category_table.csv no such file",__FUNCTION__,__FILE__,__LINE__,"backup");
			continue;
		}
		
		if(is_file("$WORKDIR/$category_table.gz")){@unlink("$WORKDIR/$category_table.gz");}
		compress("$WORKDIR/$category_table.csv", "$WORKDIR/$category_table.gz");
		
		if(!is_file("$WORKDIR/$category_table.gz")){
			ufdbguard_admin_events("Fatal: $WORKDIR/$category_table.gz no such file",__FUNCTION__,__FILE__,__LINE__,"backup");
			continue;
		}
		
		$size=$unix->file_size("$WORKDIR/$category_table.gz");
		$size=round($size/1024,2);
		@unlink("$WORKDIR/$category_table.csv");
		$took=$unix->distanceOfTimeInWords($t1,time());
		ufdbguard_admin_events("Success: $category_table.gz {$size}K took $took",__FUNCTION__,__FILE__,__LINE__,"backup");
	}
	
		$took=$unix->distanceOfTimeInWords($t,time());
		
		shell_exec("$chmod 755 $WORKDIR");
		@mkdir($WORKDIR."/storage",0755,true);
		shell_exec("$chmod 644 $WORKDIR/*.gz");
		$date=date("Ymdh");
		chdir($WORKDIR);
		shell_exec("$tar -czf $WORKDIR/storage/$date-categories.tar.gz *.gz");
		ufdbguard_admin_events("Task finish took $took",__FUNCTION__,__FILE__,__LINE__,"backup");
		reset($catArray);
		while (list ($category_table, $category_table2) = each ($catArray) ){
			if($GLOBALS["VERBOSE"]){echo "Remove $WORKDIR/$category_table.gz\n";}
			if(is_file("$WORKDIR/$category_table.gz")){@unlink("$WORKDIR/$category_table.gz");}
		}
		
		FillMysqlDatabase();
	
}

function import_backuped_categories($path=null){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		ufdbguard_admin_events("An another task is running pid $oldpid aborting",__FUNCTION__,__FILE__,__LINE__,"restore");return;
	}
	@file_put_contents($pidfile, getmypid());
	
	
	$workdir="/home/squid/restore-catz";;
	
	$tar=$unix->find_program("tar");
	$q=new mysql_squid_builder();
	if($path==null){ufdbguard_admin_events("Failed: no target file specified",__FUNCTION__,__FILE__,__LINE__,"restore");return;}
	if(!is_file($path)){ufdbguard_admin_events("Failed: $path, no such file",__FUNCTION__,__FILE__,__LINE__,"restore");return;}
	@mkdir($workdir,0777,true);
	ufdbguard_admin_events("Extracting: $path",__FUNCTION__,__FILE__,__LINE__,"restore");
	shell_exec("$tar -xf $path -C $workdir/");
	
	$catArray=$q->LIST_TABLES_CATEGORIES();
	$c=0;
	while (list ($category_table, $category_table2) = each ($catArray) ){
		$filename_csv="$workdir/$category_table.csv";
		$filename_gz="$workdir/$category_table.gz";
		if(is_file($filename_csv)){
			if(!import_backuped_categories_inject($filename_csv,$category_table)){
				ufdbguard_admin_events("CSV: $category_table failed",__FUNCTION__,__FILE__,__LINE__,"restore");
				continue;
			}
			$c++;
		}
		
		if(is_file($filename_gz)){
			uncompress($filename_gz,$filename_csv);
			if(is_file($filename_csv)){
				@unlink($filename_gz);
				if(!import_backuped_categories_inject($filename_csv,$category_table)){
					ufdbguard_admin_events("CSV: $category_table failed",__FUNCTION__,__FILE__,__LINE__,"restore");
					continue;
				}
				$c++;
			}
		}
		
	}
ufdbguard_admin_events("Success : {$GLOBALS["COUNT_FINAL"]} rows in $c tables",__FUNCTION__,__FILE__,__LINE__,"restore");
	

}

function import_backuped_categories_inject($filename_csv,$category_table){
	$workdir=dirname($filename_csv);
	@chmod("$filename_csv", 0777);
	@chmod("$workdir", 0777);
	$q=new mysql_squid_builder();
	$category=$q->tablename_tocat($category_table);
	if($category==null){
		ufdbguard_admin_events("CSV: $category_table failed no such category",__FUNCTION__,__FILE__,__LINE__,"restore");
		return false;
	}
	
	if(!$q->TABLE_EXISTS($category_table)){
		echo "$category_table does not exists, check if it is an official one\n";
		$dans=new dansguardian_rules();
		if(isset($dans->array_blacksites[$category])){
			$q->CreateCategoryTable($category);
		}
		
	}
	$sock=new sockets();
	$uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));
	if($uuid==null){echo "No uuid\n";return;}
	echo "open $filename_csv\n";	
	$handle = @fopen($filename_csv, "r"); 
	if (!$handle) {echo "Failed to open file $filename_csv\n";return;}
	$q=new mysql_squid_builder();
	$countstart=$q->COUNT_ROWS($category_table);
	ufdbguard_admin_events("$category_table store $countstart items",__FUNCTION__,__FILE__,__LINE__,"restore");
	$prefix="INSERT IGNORE INTO $category_table (zmd5,zDate,category,pattern,uuid,sended) VALUES ";
	echo "$category_table: $category\n";
	$c=0;
	$CBAD=0;
	$CBADIP=0;
	$CBADNULL=0;
	$n=array();
	while (!feof($handle)){
		$c++;
		$www =trim(fgets($handle, 4096));	
		$www=str_replace('"', "", $www);
		if(strpos($www, "/")>0){continue;}
		if(strpos($www, ",")>0){continue;}
		if(strpos($www, "'")>0){continue;}
		if(trim($www)==null){continue;}
		$md5=md5($www.$category);
		$www=addslashes($www);
		$category=addslashes($category);
		$n[]="('$md5',NOW(),'$category','$www','$uuid',1)";
	
	
		if(count($n)>6000){
			$sql=$prefix.@implode(",",$n);
			$q->QUERY_SQL($sql);
			if(!$q->ok){
				ufdbguard_admin_events("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"restore");
				echo $q->mysql_error."\n";
				return false;
			}
			$countend=$q->COUNT_ROWS($category_table);
			$final=$countend-$countstart;
			$GLOBALS["COUNT_FINAL"]=$GLOBALS["COUNT_FINAL"]+$final;
			echo "$category_table: $category: $c items, $final new entries added\n";	
			$n=array();
			
		}	
		
	}
		
if(count($n)>0){
			$sql=$prefix.@implode(",",$n);
			$q->QUERY_SQL($sql);
			if(!$q->ok){
				ufdbguard_admin_events("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"restore");
				echo $q->mysql_error."\n";
				return false;
			}
			$countend=$q->COUNT_ROWS($category_table);
			$final=$countend-$countstart;
			$GLOBALS["COUNT_FINAL"]=$GLOBALS["COUNT_FINAL"]+$final;
			echo "$c items, $final new entries added\n";	
			$n=array();
			
		}

	$final=$countend-$countstart;	
	ufdbguard_admin_events("$category_table $final added items",__FUNCTION__,__FILE__,__LINE__,"restore");
	@unlink($filename_csv);	
	return true;
}

function FillMysqlDatabase(){
	$WORKDIR="/home/squid/categories_backuped/storage";
	$unix=new unix();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$SquidDBBackupCatzMaxDay=$sock->GET_INFO("SquidDBBackupCatzMaxDay");
	if(!is_numeric($SquidDBBackupCatzMaxDay)){$SquidDBBackupCatzMaxDay=15;}
	
	$sql="SELECT filepath FROM webfilters_backupeddbs WHERE zDate<DATE_SUB(NOW(),INTERVAL $SquidDBBackupCatzMaxDay DAY)";
	$results = $q->QUERY_SQL($sql,"artica_events");
	while ($ligne = mysql_fetch_assoc($results)) {
		if(is_file($ligne["filepath"])){@unlink($ligne["filepath"]);}
	}
	
	$prefix="INSERT IGNORE INTO webfilters_backupeddbs (filepath,zDate,size) VALUES ";
	
	foreach (glob("/home/squid/categories_backuped/storage/*.gz") as $filename) {
		$UnikName=basename($filename);
		if(!preg_match("#^([0-9]+)\-.+#", $UnikName,$re)){
			echo "FF:$UnikName\n";
			continue;}
		$time=filemtime($filename);
		$size=$unix->file_size($filename);
		$filedate=date('Y-m-d H:i:s',$time);
		$f[]="('$filename','$filedate','$size')";
	}
	
	$q=new mysql_squid_builder();
	$q->CheckTables();
	$q->QUERY_SQL("TRUNCATE TABLE webfilters_backupeddbs");
	
	if(count($f)>0){
		$q->QUERY_SQL($prefix.@implode(",", $f));
		
		
	}
	//2012050607-categories.tar.gz
	
}
function empty_personal_categories(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		ufdbguard_admin_events("An another task is running pid $oldpid aborting",__FUNCTION__,__FILE__,__LINE__,"remove");return;
	}
	@file_put_contents($pidfile, getmypid());

	
	$q=new mysql_squid_builder();
	$tables=$q->LIST_TABLES_CATEGORIES();
	while (list ($num, $table) = each ($tables)){
		$ttcount=$q->COUNT_ROWS($table);
		$c=$c+$ttcount;
		ufdbguard_admin_events("Removing $ttcount items in $table",__FUNCTION__,__FILE__,__LINE__,"remove");
		$q->QUERY_SQL("TRUNCATE TABLE `$table`");
		if(!$q->ok){ufdbguard_admin_events("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"remove");continue;}
		ufdbguard_admin_events("Optimize $table",__FUNCTION__,__FILE__,__LINE__,"remove");
		$q->QUERY_SQL("OPTIMIZE TABLE `$table`");
		if(!$q->ok){ufdbguard_admin_events("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"remove");}
		
	}	
	
	
}


