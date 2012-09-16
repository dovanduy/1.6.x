<?php
$GLOBALS["FULL"]=false;
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.geoip.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
$GLOBALS["working_directory"]="/opt/artica/proxy";
$GLOBALS["MAILLOG"]=array();
$GLOBALS["CHECKTIME"]=false;
$GLOBALS["BYCRON"]=false;
$GLOBALS["MYPID"]=getmypid();
$GLOBALS["CMDLINE"]=@implode(" ", $argv);
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--checktime#",implode(" ",$argv))){$GLOBALS["CHECKTIME"]=true;}
if(preg_match("#--bycron#",implode(" ",$argv))){$GLOBALS["BYCRON"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

	$unix=new unix();
	$ln=$unix->find_program("ln");
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$oldpid=@file_get_contents($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		system_admin_events("Aborting tasks, already executed pid $oldpid", __FUNCTION__, __FILE__, __LINE__, "geoip");
		die();
	}

	@file_put_contents($pidfile, getmypid());

	$uri="http://geolite.maxmind.com/download/geoip/database/";
	$database_path='/usr/local/share/GeoIP';   
	if(!is_dir($database_path)){@mkdir($database_path,0755,true);}
	$localdatabase_version=0;
	$localGeoLiteCityVersion=0;
	$locaGeoIPASNumVersion=0;


	$localdatabase_version=GetGeoIPDbVersions($database_path .'/GeoIP.dat');
	$localGeoLiteCityVersion=GetGeoIPDbVersions($database_path .'/GeoLiteCity.dat');
	$locaGeoIPASNumVersion=GetGeoIPDbVersions($database_path .'/GeoIPASNum.dat');
	
	
	if(!is_dir("/usr/share/GeoIP")){@mkdir("/usr/share/GeoIP",0755,true);}
	if(!is_dir("/var/lib/GeoIP")){@mkdir("/var/lib/GeoIP",0755,true);}

	echo "Local GeoIP.dat database version = $localdatabase_version\n";
	echo "Local GeoLiteCity.dat database version = $localGeoLiteCityVersion\n";
	echo "Local GeoIPASNum.dat database version = $locaGeoIPASNumVersion\n";
	
	$array=GetVersions($uri);
	if(isset($array["FAILED"])){
		system_admin_events("Failed to list remote web site, aborting task...", __FUNCTION__, __FILE__, __LINE__, "geoip");
		die();
	}
	
	
	if($array["GeoLiteCity.dat"]["VERSION"]>0){
		if($array["GeoLiteCity.dat"]["VERSION"]>$localGeoLiteCityVersion){
			if($GLOBALS["VERBOSE"]){echo "GeoLiteCity.dat must be updated from $localGeoLiteCityVersion to {$array["GeoLiteCity.dat"]["VERSION"]}...\n";}
			if(UpdateDB($array["GeoLiteCity.dat"]["URI"],"GeoLiteCity.dat",$database_path)){
				$v=GetGeoIPDbVersions($database_path .'/GeoLiteCity.dat');
				system_admin_events("Success update GeoLiteCity.dat with new version $v", __FUNCTION__, __FILE__, __LINE__, "geoip");
			}else{
				system_admin_events("Failed to update GeoLiteCity.dat", __FUNCTION__, __FILE__, __LINE__, "geoip");
			}
		}
	}
	
	$array=GetVersions($uri."asnum/");
	if($array["GeoIPASNum.dat"]["VERSION"]>0){
		if($array["GeoIPASNum.dat"]["VERSION"]>$locaGeoIPASNumVersion){
			if($GLOBALS["VERBOSE"]){echo "GeoIPASNum.dat must be updated to {$array["GeoIPASNum.dat"]["VERSION"]}...\n";}
			if(UpdateDB($array["GeoIPASNum.dat"]["URI"],"GeoIPASNum.dat",$database_path)){
				$v=GetGeoIPDbVersions($database_path .'/GeoIPASNum.dat');
				system_admin_events("Success update GeoIPASNum.dat with new version $v", __FUNCTION__, __FILE__, __LINE__, "geoip");
			}else{
				system_admin_events("Failed to update GeoIPASNum.dat", __FUNCTION__, __FILE__, __LINE__, "geoip");
			}
		}
	}
	
	$array=GetVersions($uri."GeoLiteCountry/");
	if($array["GeoIP.dat"]["VERSION"]>0){
		if($array["GeoIP.dat"]["VERSION"]>$localdatabase_version){
			if($GLOBALS["VERBOSE"]){echo "GeoIP.dat must be updated to {$array["GeoIP.dat"]["VERSION"]}...\n";}
			if(UpdateDB($array["GeoIP.dat"]["URI"],"GeoIP.dat",$database_path)){
				$v=GetGeoIPDbVersions($database_path .'/GeoIP.dat');
				system_admin_events("Success update GeoIP.dat with new version $v", __FUNCTION__, __FILE__, __LINE__, "geoip");
			}else{
				system_admin_events("Failed to update GeoIP.dat", __FUNCTION__, __FILE__, __LINE__, "geoip");
			}
		}
	}
	
	$DatabasesFiles[]="GeoLiteCity.dat";
	$DatabasesFiles[]="GeoIP.dat";
	$DatabasesFiles[]="GeoIPASNum.dat";
		
	while (list ($num, $filebase) = each ($DatabasesFiles) ){
		$sourcePath="$database_path/$filebase";	
		if(is_file($sourcePath)){
			if(is_file("/usr/share/GeoIP/$filebase")){
				unlink("/usr/share/GeoIP/$filebase");
			}
			
			if(is_file("/var/lib/GeoIP/$filebase")){
				unlink("/var/lib/GeoIP/$filebase");
			}

			if(!is_file("/var/lib/GeoIP/$filebase")){
				system("$ln -s $sourcePath /var/lib/GeoIP/$filebase");
			}
			
			if(!is_file("/usr/share/GeoIP/$filebase")){
				system("$ln -s $sourcePath /usr/share/GeoIP/$filebase");
			}

		}
	}	
	


function GetVersions($uri){
	$curl=new ccurl($uri);
	$curl->NoHTTP_POST=true;
	$array=array();
	if(!$curl->get()){
		if($GLOBALS["VERBOSE"]){echo "Failed to retreive directly listing from $uri with error $curl->error\n";}
		system_admin_events("Geoip Failed to retreive directly listing from $uri with error $curl->error", __FUNCTION__, __FILE__, __LINE__, "geoip");
		return array("FAILED"=>true);
	}
	
	
	$f=explode("\n", $curl->data);
	while (list ($num, $line) = each ($f) ){
		if(preg_match('#<a href="(.+?)\.gz".*?\s+([0-9]+)-(.*?)-([0-9]+)\s+([0-9]+):([0-9]+)#', $line,$re)){
			$re[1]=trim($re[1]);
			$date=strtotime("{$re[2]}-{$re[3]}-{$re[4]} {$re[5]}:{$re[6]}");
			$newdate=date("Y-m-d H:i:s",$date);
			$newdatBin=date('Ymd');
			//if($GLOBALS["VERBOSE"]){echo "Found {$re[1]} $newdate - $newdatBin\n";}
			
			$array[$re[1]]=array(
				"VERSION"=>$newdatBin,
				"URI"=>"$uri{$re[1]}.gz"
			);
			
		}else{
			if($GLOBALS["VERBOSE"]){echo "`$line` -> no such preg\n";}
		}
		
	}

	return $array;
	
}
function UpdateDB($uri,$filenameExtracted,$rootpath){
	$curl=new ccurl($uri);
	$unix=new unix(); 
	$curl->NoHTTP_POST=true;
	$h=parse_url($uri);
	$targetFileName=basename($h["path"]);
	if(!$curl->GetFile("/tmp/$targetFileName")){
		system_admin_events("Geoip Failed to retreive $targetFileName with error $curl->error", __FUNCTION__, __FILE__, __LINE__, "geoip");
		return false;
	}

	if(!$unix->uncompress("/tmp/$targetFileName", "$rootpath/$filenameExtracted")){
		system_admin_events("Geoip Failed to extract /tmp/$targetFileName", __FUNCTION__, __FILE__, __LINE__, "geoip");
		@unlink("/tmp/$targetFileName");
		return false;
	}
	
	return true;
	
}

function GetGeoIPDbVersions($databasepath){
		if(!is_file($databasepath)){
			if($GLOBALS["VERBOSE"]){echo "$databasepath no such file...\n";}
			return 0;
		}
		$infos=xGeoIP::getDatabaseInfo($databasepath);
		//echo $infos["databaseInfo"]."\n";
		if(preg_match("#\s+([0-9]+)\s+[A-Za-z]+#", $infos["databaseInfo"],$re)){
			return $re[1];
		}
		if($GLOBALS["VERBOSE"]){echo "Unable to stat databaseInfo in $databasepath\n";}
		return 0;
	
}



