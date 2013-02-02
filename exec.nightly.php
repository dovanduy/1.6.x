<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
$GLOBALS["SINGLE_DEBUG"]=false;
$GLOBALS["FORCE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}

nightly();



function nightly(){
	$MasterIndexFile="/usr/share/artica-postfix/ressources/index.ini";
	$unix=new unix();
	$sock=new sockets();
	$timefile="/etc/artica-postfix/croned.1/nightly";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$oldpid=@file_get_contents($pidfile);
	$kill=$unix->find_program("kill");
	
	
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		echo "Starting......: nightly build already executed PID: $oldpid since {$time}Mn\n";
		system_admin_events("nightly build already executed PID: $oldpid since {$time}Mn", __FUNCTION__, __FILE__, __LINE__, "artica-update");
		if($time<120){
			if(!$GLOBALS["FORCE"]){die();}
		}
		
		shell_exec("$kill -9 $oldpid");
	}
	@file_put_contents($pidfile, getmypid());	
	
	$ini=new iniFrameWork();
	$ini->loadFile('/etc/artica-postfix/artica-update.conf');
	$nightly=trim(strtolower($ini->_params["AUTOUPDATE"]["nightlybuild"]));
	if($GLOBALS["FORCE"]){$nightly="yes";}
	if($nightly<>'yes'){
		echo "Starting......: nightly builds feature is disabled\n";
		return;
	}
	if(!$GLOBALS["FORCE"]){
		if($unix->file_time_min($timefile)<120){
			echo "Starting......: nightly builds feature (too short time, require 120mn)\n";
			return;
		}
	}
$RebootAfterArticaUpgrade=$sock->GET_INFO("RebootAfterArticaUpgrade");
if(!is_numeric($RebootAfterArticaUpgrade)){$RebootAfterArticaUpgrade=0;}	
$MyCurrentVersion=GetCurrentVersion();
@unlink("$MasterIndexFile");
echo "Starting......: nightly builds refresh index reboot after ? = $RebootAfterArticaUpgrade...  \n";
shell_exec("/usr/share/artica-postfix/bin/artica-update -refresh-index");
if(!is_file($MasterIndexFile)){
	echo "Starting......: nightly builds $MasterIndexFile no such file, try myself...\n";
	$curl=new ccurl("http://www.artica.fr/auto.update.php");
	if(!$curl->GetFile($MasterIndexFile)){
		echo "Starting......: nightly builds error $curl->error\n";
		return;
	}
}

if(!is_file($MasterIndexFile)){
	echo "Starting......: nightly builds $MasterIndexFile no such file...\n";
	return;
	
}

$ini=new iniFrameWork();
$ini->loadFile($MasterIndexFile);
$Lastest=trim(strtolower($ini->_params["NEXT"]["artica-nightly"])); 
echo "Starting......: nightly builds \"$Lastest\"\n";
$MyNextVersion=intval(str_replace(".", "", $Lastest));
echo "Starting......: nightly builds Cur:$MyCurrentVersion, Next:$MyNextVersion\n";
if($MyNextVersion==$MyCurrentVersion){
	echo "Starting......: nightly builds $MyCurrentVersion/$MyNextVersion \"Up to date\"\n";
	return;
}
if($MyCurrentVersion>$MyNextVersion){
	echo "Starting......: nightly builds $MyCurrentVersion/$MyNextVersion \"Up to date\"\n";
	return;
}
echo "Starting......: nightly builds Downloading new version $Lastest, please wait\n";
$uri="http://www.artica.fr/nightbuilds/artica-$Lastest.tgz";
$ArticaFileTemp="/tmp/$Lastest/$Lastest.tgz";    
@mkdir("/tmp/$Lastest",0755,true);
$curl=new ccurl($uri);
$curl->Timeout=2400;
$curl->WriteProgress=true;
$curl->ProgressFunction="nightly_progress";
$t=time();
if(!$curl->GetFile($ArticaFileTemp)){
	system_admin_events("Unable to download latest nightly build with error $curl->error", __FUNCTION__, __FILE__, __LINE__, "artica-update");
	@unlink($ArticaFileTemp);
	return;
}
$took=$unix->distanceOfTimeInWords($t,time(),true);
system_admin_events("artica-$Lastest.tgz download, took $took", __FUNCTION__, __FILE__, __LINE__, "artica-update");
echo "Starting......: nightly builds took $took, now stopping Artica...\n";
shell_exec("/etc/init.d/artica-postfix stop >/dev/null 2>&1");
shell_exec("/etc/init.d/artica-postfix stop >/dev/null 2>&1");
echo "Starting......: nightly builds Extracting package $ArticaFileTemp, please wait... \n";
$tarbin=$unix->find_program("tar");
$killall=$unix->find_program("killall");
shell_exec("$tarbin xf $ArticaFileTemp -C /usr/share/");
if(is_file("$killall")){
	shell_exec("$killall artica-install >/dev/null 2>&1");
}
system_admin_events("New Artica update v.$Lastest", __FUNCTION__, __FILE__, __LINE__, "artica-update");
@unlink($ArticaFileTemp);


if($RebootAfterArticaUpgrade==1){
	system_admin_events("Reboot the server...", __FUNCTION__, __FILE__, __LINE__, "artica-update");
	$reboot=$unix->find_program("reboot");
	shell_exec("$reboot");
	return;
} 
$nohup=$unix->find_program("nohup");
$php=$unix->LOCATE_PHP5_BIN();
echo "Starting......: nightly builds starting artica...\n";
shell_exec("/etc/init.d/artica-postfix start");
echo "Starting......: nightly builds building init scripts\n";
shell_exec("$php /usr/share/artica-postfix/exec.initslapd.php >/dev/null 2>&1");
echo "Starting......: nightly builds purge and clean....\n";
shell_exec("$nohup /etc/init.d/artica-postfix start ldap >/dev/null 2>&1 &");
shell_exec("$nohup /etc/init.d/artica-postfix restart artica-status >/dev/null 2>&1 &");
shell_exec("$nohup /usr/share/artica-postfix/bin/process1 -perm >/dev/null 2>&1 &");
shell_exec("$nohup /usr/share/artica-postfix/bin/artica-make --empty-cache >/dev/null 2>&1 &");
echo "Starting......: nightly builds done....\n";	
}

function nightly_progress( $download_size, $downloaded_size, $upload_size, $uploaded_size ){
	if(!isset($GLOBALS["previousProgress"])){$GLOBALS["previousProgress"]= 0;}
    
    if ( $download_size == 0 ){
        $progress = 0;
    }else{
        $progress = round( $downloaded_size * 100 / $download_size );
    }
       
    if ( $progress > $GLOBALS["previousProgress"]){
    	echo "Downloading: ". $progress."%, please wait...\n";
    	$GLOBALS["previousProgress"]=$progress;
    }
}

function GetCurrentVersion(){

   $result=0;
   $tmpstr=trim(@file_get_contents("/usr/share/artica-postfix/VERSION"));
   $tmpstr=str_replace(".", "", $tmpstr);
   $result=intval($tmpstr);
  return $result;
}
