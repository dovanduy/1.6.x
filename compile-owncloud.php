<?php
ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/ressources/class.ccurl.inc');

if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

Get_owncloud();


function Get_owncloud(){
	$unix=new unix();
	
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".time";
	$oldpid=@file_get_contents($pidfile);
	
	$unix=new unix();
	
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid since {$time}mn\n";}
		die();
	}	
	
	
	$uri=download();
	if($uri==null){return;}
	$curl=new ccurl($uri);
	$curl->NoHTTP_POST=true;
	$cp=$unix->find_program("cp");
	$rm=$unix->find_program("rm");
	progress("Downloading Owncloud package...",25);
	if(!$curl->GetFile("/root/owncloud.tar.gz")){
		progress("Failed download owncloud package",110);
		return;
	}
	
	@mkdir("/usr/share/owncloud",0755,true);
	if(!is_dir("/usr/share/owncloud")){
		progress("/usr/share/owncloud permission denied",110);
		@unlink("/root/owncloud.tar.gz");
		return;
	}
	
	$tar=$unix->find_program("tar");
	progress("Extracting package...",35);
	shell_exec("$tar xf /root/owncloud.tar.gz -C /usr/share/owncloud/");
	@unlink("/root/owncloud.tar.gz");
	if(is_dir("/usr/share/owncloud/owncloud")){
		shell_exec("$cp -rf /usr/share/owncloud/owncloud/* /usr/share/owncloud/");
		shell_exec("$rm -rf /usr/share/owncloud/owncloud");
	}
	
	if(is_file("/usr/share/owncloud/settings/settings.php")){
		progress("Success...",100);
		shell_exec("/usr/share/artica-postfix/bin/process1 --force --verbose --".time()." >/dev/null 2>&1");
		return;
	}
	progress("Failed...",110);
}



function download(){
	
	progress("Downloading index file",10);
	$curl=new ccurl("http://www.artica.fr/auto.update.php");
	$curl->NoHTTP_POST=true;
	if(!$curl->get()){
		progress("Failed download index file",110);
		return null;
	}
	
	$ini=new Bs_IniHandler();
	$ini->loadString($curl->data);
	$owncloudversion=$ini->_params["NEXT"]["owncloud"];
	if($owncloudversion==null){
		progress("Failed corrupted index file",110);
		return null;
	}
	progress("Owncloud version $owncloudversion",15);
	return "http://www.artica.fr/download/owncloud-$owncloudversion.tar.gz";
	
	
	
}

function progress($text,$prc){
	if($GLOBALS["VERBOSE"]){echo "{$prc}% $text\n";}
	$array=array($text,$prc);
	@file_put_contents("/usr/share/artica-postfix/logs/web/owncloud-setup.db", serialize($array));
}

