<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["VERBOSE"]=false;$GLOBALS["BYCRON"]=false;$GLOBALS["FORCE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--bycron#",implode(" ",$argv))){$GLOBALS["BYCRON"]=true;}
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
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');



	if(system_is_overloaded(basename(__FILE__))){
		$ldao=getSystemLoad();
		ufdbguard_admin_events("Processing task database aborted System is overloaded ($ldao), the processing will be aborted and restart in next cycle
		Task stopped line $c/$count rows\n",__FUNCTION__,__FILE__,__LINE__,"update");
		die();
	}	


if($argv[1]=="--view"){DumpDb($argv[2]);die();}
if($argv[1]=="--fishTank"){fishTank();die();}
if($argv[1]=="--CleanDB"){CleanDBZ();die();}



if(!ifMustBeExecuted()){WriteMyLogs("Die() ifMustBeExecuted -> FALSE",__FUNCTION__,__FILE__,__LINE__);die();}
WriteMyLogs("-> ExecuteMD5()...","MAIN",__FILE__,__LINE__);
ExecuteMD5();
	
	
function DumpDb($num){
	$unix=new unix();
	$BASE_URI="http://www.artica.fr/instant-blks";
	$indexuri="http://www.artica.fr/webfilters-instant.php";	
	$data_temp_file=$unix->FILE_TEMP();
	$url_temp_file="$BASE_URI/$num.dat";	
	$curl=new ccurl($url_temp_file);
	if(!$curl->GetFile($data_temp_file)){echo "Fatal error downloading $data_temp_file\n";ufdbguard_admin_events("Fatal: unable to download data file $data_temp_file",__FUNCTION__,__FILE__,__LINE__,"update");die();}
	$prefix="INSERT IGNORE INTO $table (zmd5,zDate,category,pattern,uuid,sended) VALUES ";
	$array=unserialize(base64_decode(@file_get_contents($data_temp_file)));
	while (list ($table, $TableDatas) = each ($array["BLKS"]) ){
		if($table==null){echo "!! corrupted table is null \n";return;}
		if($table=="category_teans"){$table="category_teens";}	
		while (list ($index, $ligne) = each ($TableDatas) ){
		$suffixR[]="$table= ('{$ligne["zmd5"]}','{$ligne["zDate"]}','{$ligne["category"]}','{$ligne["pattern"]}','{$ligne["uuid"]}',1)";
		if(count($suffixR)>1000){
			echo $prefix.@implode(",\n", $suffixR);
			return;
		}
	}
	
}

}

function getSystemLoad(){
	$array_load=sys_getloadavg();
	return $array_load[0];
	
}

function ExecuteMD5_getpattern($serial){}
function ExecuteMD5_Del($data_temp_file){}
function ExecuteMD5_inject($data_temp_file){}

function xaFormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){ 
	$tmp1 = round((float) $number, $decimals);
  while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
    $tmp1 = $tmp2;
  return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
} 


function CleanOldDatabase(){
	$sock=new sockets();
	$APIKEY=$sock->GET_INFO("ArticaProxyApiKey");
	if($APIKEY<>null){return;}
	if(is_file("/etc/artica-postfix/urldbcleaned")){return;}
	$q=new mysql_squid_builder();
	$array=$q->TransArray();
	while (list ($table, $cat) = each ($array) ){
		if(!$q->TABLE_EXISTS($table)){continue;}
		$q->QUERY_SQL("TRUNCATE TABLE `$table`");
	}
	if($q->TABLE_EXISTS("category_radio")){$q->QUERY_SQL("DROP TABLE `category_radio`");}
	if($q->TABLE_EXISTS("category_radiotv")){$q->QUERY_SQL("DROP TABLE `category_radiotv`");}
	@file_put_contents("/etc/artica-postfix/urldbcleaned", time());
}


function ExecuteMD5(){
	CleanOldDatabase();
	WriteMyLogs("-> CleanDB()...",__FUNCTION__,__FILE__,__LINE__);
	CleanDBZ();
	WriteMyLogs("-> fishTank()...",__FUNCTION__,__FILE__,__LINE__);
	fishTank();		
	Execute();
	
	}

	
	
function Execute(){
	$unix=new unix();
	$sock=new sockets();
	$APIKEY=trim($sock->GET_INFO("ArticaProxyApiKey"));
	if($APIKEY==null){return;}
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	if($DisableArticaProxyStatistics==1){WriteMyLogs("DisableArticaProxyStatistics=$DisableArticaProxyStatistics abort...",__FUNCTION__,__FILE__,__LINE__);return;}	
	WriteMyLogs("Execute()...",__FUNCTION__,__FILE__,__LINE__);

}


function inject($data_temp_file){
	WriteMyLogs("inject($data_temp_file)",__FUNCTION__,__FILE__,__LINE__);
	$unix=new unix();
	$q=new mysql_squid_builder();
	$array=unserialize(base64_decode(@file_get_contents($data_temp_file)));
	if(!is_array($array)){
		ufdbguard_admin_events("Fatal: $data_temp_file not an array.\n".@implode("\n", $GLOBALS["_LOGS"]),__FUNCTION__,__FILE__,__LINE__,"update");
		return false;
	}
	$t1=time();
	$t=0;
	while (list ($table, $TableDatas) = each ($array["BLKS"]) ){
		if($table==null){echo "!! corrupted table is null \n";return;}
		if($table=="category_teans"){$table="category_teens";}
		if(!$q->TABLE_EXISTS("$table")){$q->CreateCategoryTable(null,$table);}
		$prefix="INSERT IGNORE INTO $table (zmd5,zDate,category,pattern,uuid,sended) VALUES ";
		
		
		while (list ($index, $ligne) = each ($TableDatas) ){
			if($ligne["zmd5"]==null){echo "!! corrupted\n". print_r($ligne);return;}
			$t++;
			$categoriesTable[$ligne["category"]]=$ligne["category"];
			$suffixR[$table][]="('{$ligne["zmd5"]}','{$ligne["zDate"]}','{$ligne["category"]}','{$ligne["pattern"]}','{$ligne["uuid"]}',1)";
			
			if(count($suffixR[$table])>500){
				$sqlTMP="INSERT IGNORE INTO $table (zmd5,zDate,category,pattern,uuid,sended) VALUES ".@implode(",", $suffixR[$table]);
				$q->QUERY_SQL($sqlTMP);
				if(!$q->ok){ufdbguard_admin_events("Fatal: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"update");return;}
				unset($suffixR[$table]);
			}
		}
	}
	
	while (list ($table, $rows) = each ($suffixR) ){
		if(count($rows)>0){
			if($table=="category_forum"){$table="category_forums";}
			$sql="INSERT IGNORE INTO $table (zmd5,zDate,category,pattern,uuid,sended) VALUES ".@implode(",", $rows);
			$q->QUERY_SQL($sql);
			if(!$q->ok){ufdbguard_admin_events("Fatal: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"update");return;}
		}
	}
	
	$GLOBALS["INJECTED_ELEMENTS"]=$t;
	echo "Success: adding $t websites\n";	
	ufdbguard_admin_events("Success: adding $t websites in ".$unix->distanceOfTimeInWords($t1,time()),__FUNCTION__,__FILE__,__LINE__,"update");		
	if(count($array["DELETE"])==0){return true;}
	
	$CategoriesToCompile=array();
	while (list ($index, $ligne) = each ($array["DELETE"]) ){
		$category=$ligne["category"];
		$CategoriesToCompile[$category]=$category;
		$categoriesTable=$q->category_transform_name($category);
		$category_table="category_$categoriesTable";
		$sql="DELETE FROM $category_table WHERE `pattern`='{$ligne["sitename"]}'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){ufdbguard_admin_events("Fatal: Deleting row $q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"update");}
	}
	
	return true;
	

}

function ifMustBeExecuted(){
	if(system_is_overloaded(basename(__FILE__))){writelogs("Overloaded system...",__FUNCTION__,__FILE__,__LINE__);return false;}
	$users=new usersMenus();
	$sock=new sockets();
	$update=true;
	if(is_file("/etc/artica-postfix/WEBSTATS_APPLIANCE")){return true;}
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$CategoriesRepositoryEnable=$sock->GET_INFO("CategoriesRepositoryEnable");
	if(!is_numeric($CategoriesRepositoryEnable)){$CategoriesRepositoryEnable=0;}
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if($EnableRemoteStatisticsAppliance==1){WriteMyLogs("EnableRemoteStatisticsAppliance ACTIVE ,ABORTING TASK",__FUNCTION__,__FILE__,__LINE__);die();}	
	
	if($EnableWebProxyStatsAppliance==1){return true;}	
	$CategoriesRepositoryEnable=$sock->GET_INFO("CategoriesRepositoryEnable");
	if($CategoriesRepositoryEnable==1){return true;}
	if(!$users->SQUID_INSTALLED){$update=false;}
	return $update;
}

function fishTank(){
	
	if(system_is_overloaded(basename(__FILE__))){
		ufdbguard_admin_events("Task aborted, system is overloaded ({$GLOBALS["SYSTEM_INTERNAL_LOAD"]})",__FUNCTION__,__FILE__,__LINE__,"update");
		return;
	}		
	
	@mkdir("/var/lib/squidguard/phishtank",0755,true);
	$unix=new unix();
	$gunzip=$unix->find_program("gunzip");
	$chown=$unix->find_program("chown");

	
	
	if(!is_file($gunzip)){
		if($GLOBALS["VERBOSE"]){echo "Fatal: `gunzip` no such binary\n";}
		WriteMyLogs("Fatal: `gunzip` no such binary",__FUNCTION__,__FILE__,__LINE__);
		ufdbguard_admin_events("Fatal: `gunzip` no such binary",__FUNCTION__,__FILE__,__LINE__,"update");
		return;
	}
	$cache_temp="/etc/artica-postfix/data.phishtank.com.csv.gz";
	$cacheTimeStamp=$unix->file_time_min($cache_temp);
	if($cacheTimeStamp<120){
		WriteMyLogs("Fatal: Fatal: Need 120mn currently {$cacheTimeStamp}Mn",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	@unlink($cache_temp);
	@unlink("/etc/artica-postfix/data.phishtank.com.csv");
	$indexuri="http://www.artica.fr/download/shalla/online-valid.csv.gz";
	WriteMyLogs("http://www.artica.fr/shalla-orders.php?PhishTank=yes",__FUNCTION__,__FILE__,__LINE__);
	$curl=new ccurl("http://www.artica.fr/shalla-orders.php?PhishTank=yes");
	$curl->GetFile("/tmp/none.txt");
	@unlink("/tmp/none.txt");
	
	
	if(!is_file($cache_temp)){
		$curl=new ccurl($indexuri);
		echo "Downloading $indexuri\n";
		if(!$curl->GetFile($cache_temp)){
			WriteMyLogs("Fatal error downloading data.phishtank.com.csv.gz database $curl->error",__FUNCTION__,__FILE__,__LINE__);
			ufdbguard_admin_events("Fatal: unable to download index file $indexuri `$curl->error`",__FUNCTION__,__FILE__,__LINE__,"update");
			return;	
		}
	}else{
		WriteMyLogs("/etc/artica-postfix/data.phishtank.com.csv.gz exists...",__FUNCTION__,__FILE__,__LINE__);
		if($GLOBALS["VERBOSE"]){echo "/etc/artica-postfix/data.phishtank.com.csv.gz exists...\n";}
	}
	
	$size=@filesize($cache_temp);
	WriteMyLogs("$cache_temp $size bytes",__FUNCTION__,__FILE__,__LINE__);
	$cmdUncompress="$gunzip -cd $cache_temp >/etc/artica-postfix/data.phishtank.com.csv";
	
	
	if(!is_file("/etc/artica-postfix/data.phishtank.com.csv")){
		WriteMyLogs("Uncompress $cache_temp... `$cmdUncompress`",__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmdUncompress);
	}else{
		$size=@filesize("/etc/artica-postfix/data.phishtank.com.csv");
		echo "data.phishtank.com.csv $size\n";
		if($size==0){
			@unlink("/etc/artica-postfix/data.phishtank.com.csv");
			WriteMyLogs("Uncompress `$cmdUncompress`",__FUNCTION__,__FILE__,__LINE__);
			shell_exec($cmdUncompress);
				
		}
	}
	
	if(!is_file("/etc/artica-postfix/data.phishtank.com.csv")){
		WriteMyLogs("/etc/artica-postfix/data.phishtank.com.csv no such file",__FUNCTION__,__FILE__,__LINE__);
		@unlink("/etc/artica-postfix/data.phishtank.com.csv.gz");
		ufdbguard_admin_events("Fatal: unable to download online-valid.csv.gz",__FUNCTION__,__FILE__,__LINE__,"update");
	}
	
	$size=@filesize("/etc/artica-postfix/data.phishtank.com.csv");
	echo "data.phishtank.com.csv $size\n";	
	if($size==0){
		if($GLOBALS["VERBOSE"]){echo "Uncompress...\n";}
		@unlink("Fatal: unable to download online-valid.csv.gz");
		ufdbguard_admin_events("Fatal: unable to download online-valid.csv.gz",__FUNCTION__,__FILE__,__LINE__,"update");		
	}
if(is_file("/var/lib/squidguard/phishtank/urls")){@unlink("/var/lib/squidguard/phishtank/urls");}	
$file = fopen("/etc/artica-postfix/data.phishtank.com.csv", "r");
$file2 = fopen("/var/lib/squidguard/phishtank/urls", "a");
if(!$file){
	ufdbguard_admin_events("Fatal: unable to open /etc/artica-postfix/data.phishtank.com.csv",__FUNCTION__,__FILE__,__LINE__,"update");
	return;
}
if(!$file2){
	ufdbguard_admin_events("Fatal: unable to open /var/lib/squidguard/phishtank/urls permission denied",__FUNCTION__,__FILE__,__LINE__,"update");
	fclose($file);
	return;
}


$c=0;
while(!feof($file)){
  $line=fgets($file);
  $tb=explode(",", $line);
  if(!is_numeric($tb[0])){continue;}
  if(preg_match("#^.?:\/\/(.+)#", $tb[1],$re)){$tb[1]=$re[1];}
  $tb[1]=str_replace("http://", "", $tb[1]);
  $tb[1]=str_replace("https://", "", $tb[1]);
  $tb[1]=str_replace("ftp://", "", $tb[1]);
  $tb[1]=str_replace("ftps://", "", $tb[1]);
  if(preg_match("#^www.(.+)#", trim($tb[1]),$re)){$tb[1]=trim($re[1]);}
  if(strpos($tb[1], "/")==0){$t[]=$tb[1];continue;}
 $c++;
  @fwrite($file2, "{$tb[1]}\n");
  if(count($t)<50){$t[]=md5($line).".com";}
 }
 fclose($file2);	
 fclose($file);	

 
 @file_put_contents("/var/lib/squidguard/phishtank/domains", @implode("\n", $t));
 ufdbguard_admin_events("Success update dabatase with $c phistank urls",__FUNCTION__,__FILE__,__LINE__,"update");	
 shell_exec("$chown squid:squid /var/lib/squidguard/phishtank/domains");
 shell_exec("$chown squid:squid /var/lib/squidguard/phishtank");
 shell_exec("$chown squid:squid /var/lib/squidguard/phishtank/urls");
		
$php5=$unix->LOCATE_PHP5_BIN();
shell_exec("$php5 /usr/share/artica-postfix/exec.squidguard.php --ufdbguard-compile /var/lib/squidguard/phishtank/domains");
	
}

function CleanDBZ(){
	if($GLOBALS["VERBOSE"]){echo "CleanDBZ...\n";}
	$q=new mysql_squid_builder();
	if($q->TABLE_EXISTS("category_art")){
		$sql="SELECT zDate,category,pattern,uuid FROM category_art";
		$results=$q->QUERY_SQL($sql);
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$www=$ligne["pattern"];
				$md5=md5($www."hobby/arts");
				$f[]="('$md5','{$ligne["zDate"]}','hobby/arts','$www',1,'{$ligne["uuid"]}',1)";
			}
			
			if(count($f)>0){
				$q->QUERY_SQL("INSERT IGNORE INTO category_hobby_arts (zmd5,zDate,category,pattern,enabled,uuid,sended) VALUES " .@implode(",", $f));
				$q->QUERY_SQL("DROP TABLE category_art");
				ufdbguard_admin_events("Success: move table category_art to category_hobby_arts",__FUNCTION__,__FILE__,__LINE__,"update");
			}
	}else{
		if($GLOBALS["VERBOSE"]){echo "category_art no such table\n";}
	}
	
	
	
	
}



function WriteMyLogs($text,$function,$file,$line){
	$GLOBALS["_LOGS"][]="$line: $text";
	$mem=round(((memory_get_usage()/1024)/1000),2);
	if(!isset($GLOBALS["MYPID"])){$GLOBALS["MYPID"]=getmypid();}
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
	@fwrite($f, "$date [{$GLOBALS["MYPID"]}][{$mem}MB]: [$function::$line] $text\n");
	@fclose($f);
}