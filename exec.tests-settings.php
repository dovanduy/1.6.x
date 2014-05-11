<?php
ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
if(!is_file("/usr/share/artica-postfix/ressources/settings.new.inc")){die();}

@mkdir("/etc/artica-postfix/pids",0755,true);
$cachefile="/etc/artica-postfix/pids/".basename(__FILE__).".time";
if(is_file($cachefile)){$time=_file_time_min($cachefile); if($time<2){die();} }
@unlink($cachefile);@file_put_contents($cachefile, time());

include("/usr/share/artica-postfix/ressources/settings.new.inc");

if(!isset($_GLOBAL["ldap_admin"])){
	if(!is_file("/etc/init.d/artica-process1")){die();}
	shell_exec("/etc/init.d/artica-process1 start");
	die();
}


$t=@file_get_contents("/usr/share/artica-postfix/ressources/settings.new.inc");
if(preg_match("#<\?php(.+?)\?>#is", $t,$re)){
	@copy("/usr/share/artica-postfix/ressources/settings.new.inc", "/usr/share/artica-postfix/ressources/settings.inc");
	@unlink("/usr/share/artica-postfix/ressources/settings.new.inc");
}


function _file_time_min($path){
	$last_modified=0;

	if(is_dir($path)){return 10000;}
	if(!is_file($path)){return 100000;}
	$size=@filesize($path);
	if(strpos($path, "artica-postfix/")>0){
		if($size<15){
			$xtime=trim(@file_get_contents($path));
			if(is_numeric($xtime)){
				if($xtime>1000000000){$last_modified=$xtime;}
			}
		}
	}

	if($last_modified==0){$last_modified = filemtime($path);}
	$data1 = $last_modified;
	$data2 = time();
	$difference = ($data2 - $data1);
	$results=intval(round($difference/60));
	if($results<0){$results=1;}
	return $results;
}