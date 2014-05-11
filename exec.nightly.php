<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/refresh.index.progress";
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');
$GLOBALS["SINGLE_DEBUG"]=false;
$GLOBALS["NOT_FORCE_PROXY"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["BYCRON"]=false;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["CHANGED"]=false;
$GLOBALS["FORCE_NIGHTLY"]=false;
$GLOBALS["MasterIndexFile"]="/usr/share/artica-postfix/ressources/index.ini";
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--force-nightly#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;$GLOBALS["FORCE"]=true;$GLOBALS["FORCE_NIGHTLY"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;

	$GLOBALS["VERBOSE"]=true;
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string'," Fatal..:");
	ini_set('error_append_string',"\n");
}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--bycron#",implode(" ",$argv))){$GLOBALS["BYCRON"]=true;}
if($argv[1]=="--refresh"){RefreshIndex(true);exit;}

nightly();

function RefreshIndex(){
	$unix=new unix();
	$nice=EXEC_NICE();
	$sock=new sockets();
	$users=new usersMenus();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$wget=$unix->find_program("wget");
	$SYSTEMID=$unix->GetUniqueID();
	if($SYSTEMID==null){
		build_progress("System ID is Null !!!",5);
		return;
	}
	
	build_progress("Register server...",10);
	shell_exec("$nohup $nice $php /usr/share/artica-postfix/exec.web-community-filter.php --register-lic >/dev/null 2>&1 &");
	
	if($SYSTEMID==null){
		build_progress("No system ID, force",15);
		shell_exec("$nice /usr/share/artica-postfix/bin/artica-update -refresh-index --force >/dev/null 2>&1");
		return;
	}
	$xMEM_TOTAL_INSTALLEE=$users->MEM_TOTAL_INSTALLEE;
	$CPU_NUMBER=$users->CPU_NUMBER;
	$LinuxDistributionFullName=$users->LinuxDistriFullName;
	if($LinuxDistributionFullName==null){$LinuxDistributionFullName="Linux Default";}
	$ARTICA_VERSION=GetCurrentVersion();
	$hostname=$unix->hostname_g();
	$CheckUserCount=CheckUserCount();
	$dmidecode=@file_get_contents("/etc/artica-postfix/dmidecode.cache.url");
	$uriplus="$SYSTEMID;$xMEM_TOTAL_INSTALLEE;$CPU_NUMBER;$LinuxDistributionFullName;$ARTICA_VERSION;$hostname;$CheckUserCount;$dmidecode";
	$uriplus=urlencode($uriplus);
	@unlink($GLOBALS["MasterIndexFile"]);
	$tarballs_file="/usr/share/artica-postfix/ressources/logs/web/tarballs.cache";
	echo "Starting......: ".date("H:i:s")." CPU NUMBER: $CPU_NUMBER\n";
	echo "Starting......: ".date("H:i:s")." Hostname..: $hostname\n";
	echo "Starting......: ".date("H:i:s")." Artica ver: $ARTICA_VERSION\n";
	echo "Starting......: ".date("H:i:s")." Users.....: $CheckUserCount\n";
	build_progress("Configuration done",15);
	
	$ini=new iniFrameWork();
	$ini->loadFile('/etc/artica-postfix/artica-update.conf');
	if(trim($ini->_params["AUTOUPDATE"]["uri"])==null){$ini->_params["AUTOUPDATE"]["uri"]="http://www.articatech.net/auto.update.php";}
	if(!isset($ini->_params["AUTOUPDATE"]["enabled"])){$ini->_params["AUTOUPDATE"]["enabled"]="yes";}
	if($ini->_params["AUTOUPDATE"]["enabled"]==null){$ini->_params["AUTOUPDATE"]["enabled"]="yes";}
	if(!is_numeric(trim($ini->_params["AUTOUPDATE"]["CheckEveryMinutes"]))){$ini->_params["AUTOUPDATE"]["CheckEveryMinutes"]=60;}


	$uri=$ini->_params["AUTOUPDATE"]["uri"];
	$arrayURI=parse_url($uri);
	build_progress("Check repositories",20);
	$MAIN_URI=$unix->MAIN_URI();
	echo "Starting......: ".date("H:i:s")." Main URI..: $MAIN_URI\n";
	$md5string=@md5_file($GLOBALS["MasterIndexFile"]);
	
	build_progress("Get TarBalls...",25);
	echo "Starting......: ".date("H:i:s")." Update tarballs..\n";
	$curl=new ccurl("$MAIN_URI/tarballs.php?time=".time());
	@unlink($tarballs_file);
	$curl->NoHTTP_POST=true;
	if(!$curl->GetFile($tarballs_file)){
		build_progress("Get TarBalls !! FAILED !!",30);
		_artica_update_event(0,"Unable to download tarballs file with error $curl->error_num, $curl->error",null,__FILE__,__LINE__);
		@unlink($tarballs_file);
	}
	build_progress("Ping repositories...",35);
	@chmod($tarballs_file,0755);
	echo "Starting......: ".date("H:i:s")." dmidecode = ". strlen($dmidecode)." bytes\n";
	echo "Starting......: ".date("H:i:s")." Updating repository information..\n";
	
	
	
	$curl=new ccurl("$MAIN_URI/routers.inject.php?time=".time());
	$curl->NoHTTP_POST=true;
	$curl->get();
	
	build_progress("Retreive index from repository",40);
	$curl=new ccurl("$uri?time=".time()."&datas=$uriplus");
	$curl->NoHTTP_POST=true;
	$curl->forceCache=true;
	
	
	
	
	echo "Starting......: ".date("H:i:s")." Downloading collection using Interface: `$curl->interface`\n";
	echo "Starting......: ".date("H:i:s")." Using \"{$GLOBALS["MasterIndexFile"]}\"\n";
	echo "Starting......: ".date("H:i:s")." Timeout set to \"{$curl->Timeout}s\"\n";
	if(!$curl->GetFile($GLOBALS["MasterIndexFile"])){
		build_progress("Retreive index from repository !! FAILED !!",100);
		if(!$GLOBALS["NOT_FORCE_PROXY"]){
			echo "Starting......: ".date("H:i:s")." FATAL: Unable to download index file, try in direct mode\n";
			$GLOBALS["NOT_FORCE_PROXY"]=true;
			return RefreshIndex();
		}
		echo "Starting......: ".date("H:i:s")." FATAL: {$GLOBALS["MasterIndexFile"]} ".@filesize($GLOBALS["MasterIndexFile"])." bytes\n";
		echo "Starting......: ".date("H:i:s")." FATAL: Unable to download index file with error $curl->error_num, $curl->error\n";
		_artica_update_event(0,"Unable to download index file with error $curl->error_num, $curl->error",null,__FILE__,__LINE__);
		exec("$nice /usr/share/artica-postfix/bin/artica-update -refresh-index 2>&1",$results);
		while (list ($num, $ligne) = each ($dirs) ){echo "Starting......: ".date("H:i:s")." $ligne\n";}
		return;
	}	
	build_progress("Retreive index from repository !! Success !!",100);
	$md5string2=md5_file($GLOBALS["MasterIndexFile"]);
	echo "Starting......: ".date("H:i:s")." source: `$md5string` new: `$md5string2`\n";
	echo "Starting......: ".date("H:i:s")." Success...\n";
	return true;
	
}

function _artica_update_event($severity,$subject,$text,$file=null,$line=0){
	if(!function_exists("artica_update_event")){return;}
	artica_update_event($severity,$subject,$text,$file,$line);
}
function build_progress($text,$pourc){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);

}

function CheckUserCount(){
	$unix=new unix();
	$cachefile="/etc/artica-postfix/UsersNumber";
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$nice=EXEC_NICE();
	if(!is_file($cachefile)){
			shell_exec("$nohup $nice $php /usr/share/artica-postfix/exec.samba.php --users >/dev/null 2>&1 &");
			return 0;
	}
	
	$usersN=@file_get_contents($cachefile);
	if($unix->file_time_min($cachefile)>3600){
		@unlink($cachefile);
		shell_exec("$nohup $nice $php /usr/share/artica-postfix/exec.samba.php --users >/dev/null 2>&1 &");
		return $usersN;		
	}
	return $usersN;
}
//#############################################################################

function update_release(){
	$sock=new sockets();
	$unix=new unix();
	$autoinstall=true;
	$ini=new iniFrameWork();
	
	
	$tmpdir=$unix->TEMP_DIR();
	if(!master_index()){return false;}
	$RebootAfterArticaUpgrade=$sock->GET_INFO("RebootAfterArticaUpgrade");
	if(!is_numeric($RebootAfterArticaUpgrade)){$RebootAfterArticaUpgrade=0;}
	$MyCurrentVersion=GetCurrentVersion();
	$ini->loadFile('/etc/artica-postfix/artica-update.conf');
	
	$ini->_params["AUTOUPDATE"]["autoinstall"]=trim(strtolower($ini->_params["AUTOUPDATE"]["autoinstall"]));
	$ini->_params["AUTOUPDATE"]["enabled"]=trim(strtolower($ini->_params["AUTOUPDATE"]["enabled"]));
	
	if(trim($ini->_params["AUTOUPDATE"]["autoinstall"])==null){$ini->_params["AUTOUPDATE"]["autoinstall"]="yes";}
	if(trim($ini->_params["AUTOUPDATE"]["autoinstall"])==1){$ini->_params["AUTOUPDATE"]["autoinstall"]="yes";}
	if(trim($ini->_params["AUTOUPDATE"]["enabled"])==1){$ini->_params["AUTOUPDATE"]["enabled"]="yes";}
	
	
	
	if($ini->_params["AUTOUPDATE"]["autoinstall"]<>"yes"){$autoinstall=false;}
	$uri=$ini->_params["AUTOUPDATE"]["uri"];
	$arrayURI=parse_url($uri);
	$MAIN_URI="{$arrayURI["scheme"]}://{$arrayURI["host"]}";
	echo "Starting......: ".date("H:i:s")." Source:$uri\n";
	
	if(!$GLOBALS["FORCE"]){
		if($ini->_params["AUTOUPDATE"]["enabled"]<>'yes'){
			echo "Starting......: ".date("H:i:s")." Update feature is disabled AUTOUPDATE/enabled = `{$ini->_params["AUTOUPDATE"]["enabled"]}`\n";
			echo "Starting......: ".date("H:i:s")." Add --force to bypass or enable the update feature trough Artica Web console.\n";
			return;
		}
	}
	
	
	$CheckEveryMinutes=$ini->_params["AUTOUPDATE"]["CheckEveryMinutes"];
	if(!is_numeric($CheckEveryMinutes)){$CheckEveryMinutes=60;}
	
	
	if($GLOBALS["FORCE"]){
		if(is_file("/root/artica-latest.tgz")){
			_artica_update_event(1,"Installing old downloaded package /root/artica-latest.tgz",null,__FILE__,__LINE__);
			echo "Starting......: ".date("H:i:s")." Installing old downloaded package\n";
			if(install_package("/root/artica-latest.tgz",null)){return;}
		}
	}
	
	RefreshIndex();
	$ini=new iniFrameWork();
	$ini->loadFile($GLOBALS["MasterIndexFile"]);
	
	if(!isset($ini->_params["NEXT"])){
		echo "Starting......: ".date("H:i:s")." Corrupted Index: {$GLOBALS["MasterIndexFile"]}..\n";
		return;
	}
	
	$Lastest=trim(strtolower($ini->_params["NEXT"]["artica"]));
	$nightly=trim(strtolower($ini->_params["NEXT"]["artica-nightly"]));
	
	$GLOBALS["lastest-nightly"]=$nightly;
	
	if($RebootAfterArticaUpgrade==1){
		echo "Starting......: ".date("H:i:s")." Reboot after upgrade is enabled\n";
	}
	
	$buildtime=trim(strtolower($ini->_params["NEXT"]["buildtime"]));
	if(!is_numeric($buildtime)){
		_artica_update_event(1,"There is an issue on the index file (no build time)",null,__FILE__,__LINE__);
	}else{
		echo "Starting......: ".date("H:i:s")." Index file refreshed on : ". date("Y-m-d H:i:s",$buildtime)."\n";
	}
	
	echo "Starting......: ".date("H:i:s")." Last Official release: \"$Lastest\"\n";
	echo "Starting......: ".date("H:i:s")." Last Nightly release:. \"$nightly\"\n";
	$MyNextVersion=intval(str_replace(".", "", $Lastest));
	echo "Starting......: ".date("H:i:s")." Official release Cur:$MyCurrentVersion, Next:$MyNextVersion\n";
	if($MyNextVersion==$MyCurrentVersion){echo "Starting......: ".date("H:i:s")." Official release $MyCurrentVersion/$MyNextVersion \"Up to date\"\n";return;}
	if($MyCurrentVersion>$MyNextVersion){echo "Starting......: ".date("H:i:s")." Official release $MyCurrentVersion/$MyNextVersion \"Up to date\"\n";return;}

	
	$t1=time();
	_artica_update_event(1,"New official release available version $Lastest",null,__FILE__,__LINE__);
	echo "Starting......: ".date("H:i:s")." Official release Downloading new version $Lastest, please wait\n";
	events("Downloading new version $Lastest");
	
	$uri="$MAIN_URI/download/artica-$Lastest.tgz";
	$ArticaFileTemp="$tmpdir/$Lastest/$Lastest.tgz";
	@mkdir("$tmpdir/$Lastest",0755,true);
	$curl=new ccurl($uri);
	$curl->Timeout=2400;
	$curl->WriteProgress=true;
	$curl->ProgressFunction="nightly_progress";
	$t=time();
	echo "Starting......: ".date("H:i:s")." Official release Downloading $uri\n";
	
	if(!$curl->GetFile($ArticaFileTemp)){
		_artica_update_event(0,"Error: Official release Unable to download latest build with error $curl->error",null,__FILE__,__LINE__);
		events("Unable to download latest build with error $curl->error");
		system_admin_events("Unable to download latest build with error $curl->error", __FUNCTION__, __FILE__, __LINE__, "artica-update");
		@unlink($ArticaFileTemp);
		return;
	}
	
	$size=@filesize($ArticaFileTemp)/1024;
	echo "Starting......: ".date("H:i:s")." Official release size:{$size}KB\n";
	
	$took=$unix->distanceOfTimeInWords($t,time(),true);
	_artica_update_event(2,"artica-$Lastest.tgz downloaded, took $took",null,__FILE__,__LINE__);
	system_admin_events("artica-$Lastest.tgz downloaded, took $took", __FUNCTION__, __FILE__, __LINE__, "artica-update");
	events("artica-$Lastest.tgz downloaded, took $took");
	
	if(!$GLOBALS["FORCE"]){
		if($autoinstall==false){
			_artica_update_event(2,"artica-latest.tgz will be stored in /root",null,__FILE__,__LINE__);
			@copy("$ArticaFileTemp", "/root/artica-latest.tgz");
			@unlink($ArticaFileTemp);
			_artica_update_event(1,"New Artica v.$Lastest waiting administrator order",null,__FILE__,__LINE__);
			system_admin_events("New Artica update v.$Lastest waiting your order", __FUNCTION__, __FILE__, __LINE__, "artica-update");
			return;
		}
	}
	
	
	
	echo "Starting......: ".date("H:i:s")." Official release took $took\n";
	if(install_package($ArticaFileTemp,$Lastest)){return;}
	events("New Artica update v.$Lastest");
	_artica_update_event(1,"Nightly build: Artica v.$Lastest",null,__FILE__,__LINE__);
	system_admin_events("New Artica update v.$Lastest", __FUNCTION__, __FILE__, __LINE__, "artica-update");
	
}


function master_index(){
	
	@unlink($GLOBALS["MasterIndexFile"]);
	
	$ini=new iniFrameWork();
	$ini->loadFile('/etc/artica-postfix/artica-update.conf');
	if(trim($ini->_params["AUTOUPDATE"]["autoinstall"])==null){$ini->_params["AUTOUPDATE"]["autoinstall"]="yes";}
	if($ini->_params["AUTOUPDATE"]["autoinstall"]<>"yes"){$autoinstall=false;}
	$uri=$ini->_params["AUTOUPDATE"]["uri"];
	$arrayURI=parse_url($uri);
	$MAIN_URI="{$arrayURI["scheme"]}://{$arrayURI["host"]}";
	
	echo "Starting......: ".date("H:i:s")." Refreshing index file...\n";
	
	$curl=new ccurl("$uri?time=".time());
	if(!$curl->GetFile($GLOBALS["MasterIndexFile"])){
	_artica_update_event(2,"Error $curl->error",null,__FILE__,__LINE__);
	echo "Starting......: ".date("H:i:s")." Error $curl->error_num;$curl->error, Try direct\n";
	
	if(!$GLOBALS["NOT_FORCE_PROXY"]){
		echo "Starting......: ".date("H:i:s")." FATAL: Unable to download index file, try in direct mode\n";
		$GLOBALS["NOT_FORCE_PROXY"]=true;
		return master_index();
	}
	
	
	if($curl->error=="{CURLE_COULDNT_RESOLVE_HOST}"){
			if($arrayURI["host"]=="www.artica.fr"){
				if(!$GLOBALS["CHANGED"]){
					echo "Starting......: ".date("H:i:s")." trying www.articatech.net\n";
					$ini->_params["AUTOUPDATE"]["uri"]="http://www.articatech.net/auto.update.php";
					$ini->saveFile("/etc/artica-postfix/artica-update.conf");
					$GLOBALS["CHANGED"]=true;
					return master_index();
				}
			}
		}
		return;
	}
	
	
	if(!is_file($GLOBALS["MasterIndexFile"])){
		echo "Starting......: ".date("H:i:s")." {$GLOBALS["MasterIndexFile"]} no such file...\n";
		return;
	
	}
	
	return true;
}


function nightly(){
	@mkdir("/var/log/artica-postfix",0755,true);
	$GLOBALS["MasterIndexFile"]="/usr/share/artica-postfix/ressources/index.ini";
	$unix=new unix();
	$sock=new sockets();
	$autoinstall=true;
	$timefile="/etc/artica-postfix/croned.1/nightly";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$oldpid=@file_get_contents($pidfile);
	$kill=$unix->find_program("kill");
	$tmpdir=$unix->TEMP_DIR();
	
	
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		echo "Starting......: ".date("H:i:s")." nightly build already executed PID: $oldpid since {$time}Mn\n";
		system_admin_events("nightly build already executed PID: $oldpid since {$time}Mn", __FUNCTION__, __FILE__, __LINE__, "artica-update");
		if($time<120){if(!$GLOBALS["FORCE"]){die();}}
		shell_exec("$kill -9 $oldpid");
	}
	
	$mypid=getmypid();
	@file_put_contents($pidfile, $mypid);	
	
	
	$EnableScheduleUpdates=$sock->GET_INFO("EnableScheduleUpdates");
	if(!is_numeric($EnableScheduleUpdates)){$EnableScheduleUpdates=0;}
	
	if($GLOBALS["FORCE"]){
		_artica_update_event(1,"Update task pid $mypid is forced by an human.",null,__FILE__,__LINE__);
	}
	
	if($EnableScheduleUpdates==1){
		if(!$GLOBALS["FORCE"]){
			if(!$GLOBALS["BYCRON"]){
				_artica_update_event(2,"Operation must be only executed by scheduler ( use --force to by pass)",null,__FILE__,__LINE__);
				@file_put_contents("/usr/share/artica-postfix/download_progress", 100);
				return;
			}
		}
	}
	
	$ini=new iniFrameWork();
	$ini->loadFile('/etc/artica-postfix/artica-update.conf');
	if(!isset($ini->_params["AUTOUPDATE"]["enabled"])){$ini->_params["AUTOUPDATE"]["enabled"]="yes";}
	if($ini->_params["AUTOUPDATE"]["enabled"]==null){$ini->_params["AUTOUPDATE"]["enabled"]="yes";}
	if(trim($ini->_params["AUTOUPDATE"]["uri"])==null){$ini->_params["AUTOUPDATE"]["uri"]="http://www.articatech.net/auto.update.php";}
	if(trim($ini->_params["AUTOUPDATE"]["enabled"])==null){$ini->_params["AUTOUPDATE"]["enabled"]="yes";}
	if(!is_numeric(trim($ini->_params["AUTOUPDATE"]["CheckEveryMinutes"]))){$ini->_params["AUTOUPDATE"]["CheckEveryMinutes"]=60;}
	if($ini->_params["AUTOUPDATE"]["enabled"]<>'yes'){echo "Starting......: ".date("H:i:s")." Update feature is disabled\n";return;}
	$CheckEveryMinutes=$ini->_params["AUTOUPDATE"]["CheckEveryMinutes"];
	
	$uri=$ini->_params["AUTOUPDATE"]["uri"];
	$arrayURI=parse_url($uri);
	$MAIN_URI="{$arrayURI["scheme"]}://{$arrayURI["host"]}";
	
	
	if(!$GLOBALS["FORCE"]){
		if($EnableScheduleUpdates==0){
			if($unix->file_time_min($timefile)<$CheckEveryMinutes){
				echo "Starting......: ".date("H:i:s")." update feature (too short time, require {$CheckEveryMinutes}mn)\n";
				@file_put_contents("/usr/share/artica-postfix/download_progress", 100);
				return;
			}
		}
	}
	
	@unlink($timefile);
	@file_put_contents($timefile, time());
	
	
	if($GLOBALS["FORCE"]){
		if(is_file("/root/artica-latest.tgz")){
			echo "Starting......: ".date("H:i:s")." Installing old downloaded package\n";
			if(install_package("/root/artica-latest.tgz")){return;}
		}
	}
	
	echo "Starting......: ".date("H:i:s")." Nightly builds checking an official release first\n";
	if(update_release()){return;}
	
	$nightly=trim(strtolower($ini->_params["AUTOUPDATE"]["nightlybuild"]));
	if($nightly==1){$nightly="yes";}
	if($GLOBALS["FORCE"]){$nightly="yes";}
	if($GLOBALS["FORCE_NIGHTLY"]){$nightly="yes";}
	
	
	if($nightly<>'yes'){
		echo "Starting......: ".date("H:i:s")." Nightly builds feature is disabled [$nightly]\n";
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/download_progress", 100);
		return;
	}
	if($ini->_params["AUTOUPDATE"]["autoinstall"]==1){$ini->_params["AUTOUPDATE"]["autoinstall"]="yes";}
	if(trim($ini->_params["AUTOUPDATE"]["autoinstall"])==null){$ini->_params["AUTOUPDATE"]["autoinstall"]="yes";}
	if($ini->_params["AUTOUPDATE"]["autoinstall"]<>"yes"){$autoinstall=false;}

	
	@file_put_contents("/usr/share/artica-postfix/download_progress", 0);
	
		
	$MyCurrentVersion=GetCurrentVersion();
	echo "Starting......: ".date("H:i:s")." Current version: $MyCurrentVersion\n";
	$Lastest=trim(strtolower($GLOBALS["lastest-nightly"])); 
	echo "Starting......: ".date("H:i:s")." Nightly builds version \"$Lastest\" on repository\n";
	$MyNextVersion=intval(str_replace(".", "", $Lastest));
	echo "Starting......: ".date("H:i:s")." nightly builds Cur:$MyCurrentVersion, Next:$MyNextVersion\n";
	if($MyNextVersion==$MyCurrentVersion){
		echo "Starting......: ".date("H:i:s")." nightly builds $MyCurrentVersion/$MyNextVersion \"Up to date - Same version\"\n";
		@file_put_contents("/usr/share/artica-postfix/download_progress", 100);
		return;
	}
	if($MyCurrentVersion>$MyNextVersion){
		echo "Starting......: ".date("H:i:s")." nightly builds $MyCurrentVersion/$MyNextVersion \"Up to date - Most updated\"\n";
		@file_put_contents("/usr/share/artica-postfix/download_progress", 100);
		return;
	}
	
	$t1=time();
	_artica_update_event(2,"nightly builds Downloading new version $Lastest",null,__FILE__,__LINE__);
	echo "Starting......: ".date("H:i:s")." nightly builds Downloading new version $Lastest, please wait\n";
	events("Downloading new version $Lastest");
	
	$uri="$MAIN_URI/nightbuilds/artica-$Lastest.tgz";
	$ArticaFileTemp="$tmpdir/$Lastest/$Lastest.tgz";    
	@mkdir("$tmpdir/$Lastest",0755,true);
	$curl=new ccurl($uri);
	$curl->Timeout=2400;
	$curl->WriteProgress=true;
	$curl->ProgressFunction="nightly_progress";
	$t=time();
	if(!$curl->GetFile($ArticaFileTemp)){
		_artica_update_event(0,"nightly builds Unable to download latest nightly build with error $curl->error",null,__FILE__,__LINE__);
		events("Unable to download latest nightly build with error $curl->error");
		system_admin_events("Unable to download latest nightly build with error $curl->error", __FUNCTION__, __FILE__, __LINE__, "artica-update");
		@unlink($ArticaFileTemp);
		return;
	}
	$took=$unix->distanceOfTimeInWords($t,time(),true);
	_artica_update_event(2,"artica-$Lastest.tgz download, took $took",null,__FILE__,__LINE__);
	system_admin_events("artica-$Lastest.tgz download, took $took", __FUNCTION__, __FILE__, __LINE__, "artica-update");
	events("artica-$Lastest.tgz download, took $took");

	echo "Starting......: ".date("H:i:s")." nightly builds took $took\n";
	if(!$GLOBALS["FORCE"]){
		if($autoinstall==false){
			_artica_update_event(2,"artica-latest.tgz will be stored in /root",null,__FILE__,__LINE__);
			@copy("$ArticaFileTemp", "/root/artica-latest.tgz");
			@unlink($ArticaFileTemp);
			_artica_update_event(1,"nightly builds New Artica update v.$Lastest waiting order",null,__FILE__,__LINE__);
			system_admin_events("New Artica update v.$Lastest waiting your order", __FUNCTION__, __FILE__, __LINE__, "artica-update");
			return;
		}else{
			
		}
	}


	events("Now, installing the newest version in $ArticaFileTemp package...");
	if(!install_package($ArticaFileTemp,$Lastest)){
		events("Install package Failed...");
		return false;}
	events("New Artica update v.$Lastest");
	_artica_update_event(1,"nightly builds New Artica update v.$Lastest",null,__FILE__,__LINE__);
	system_admin_events("New Artica update v.$Lastest", __FUNCTION__, __FILE__, __LINE__, "artica-update");

}

function install_package($filename,$expected=null){
	$unix=new unix();
	$sock=new sockets();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$RebootAfterArticaUpgrade=$sock->GET_INFO("RebootAfterArticaUpgrade");
	if(!is_numeric($RebootAfterArticaUpgrade)){$RebootAfterArticaUpgrade=0;}
	events("Starting......: ".date("H:i:s")." install_package() Extracting package $filename, please wait... ");
	echo "Starting......: ".date("H:i:s")." install_package() Extracting package $filename, please wait... \n";

	
	
	$tarbin=$unix->find_program("tar");
	$killall=$unix->find_program("killall");
	echo "Starting......: ".date("H:i:s")." tar: $tarbin\n";
	echo "Starting......: ".date("H:i:s")." killall: $killall\n";
	
	@file_put_contents("/usr/share/artica-postfix/download_progress", 10);
	events("Starting......: ".date("H:i:s")." install_package() Testing Package");
	echo "Starting......: ".date("H:i:s")." Testing Package ".basename($filename)."\n";
	
	if(!$unix->TARGZ_TEST_CONTAINER($filename)){
		echo "Starting......: ".date("H:i:s")." Testing Package ".basename($filename)." failed\n";
		_artica_update_event(0,"Compressed package seems corrupted",null,__FILE__,__LINE__);
		events("Fatal, Compressed package seems corrupted");
		events($GLOBALS["TARGZ_TEST_CONTAINER_ERROR"]);
		@unlink($filename);
		@file_put_contents("/usr/share/artica-postfix/download_progress", 100);
		return false;
	}
	events("Starting......: ".date("H:i:s")." Extracting...");
	@file_put_contents("/usr/share/artica-postfix/download_progress", 40);
	exec("$tarbin xf $filename -C /usr/share/ 2>&1",$results);
	if(is_file("$killall")){shell_exec("$killall artica-install >/dev/null 2>&1");}
	@unlink($filename);
	shell_exec("$nohup $php ". dirname(__FILE__)."/exec.checkfolder-permissions.php --force >/dev/null 2>&1 &");
		
	$MyCurrentVersion=GetCurrentVersionString();
	if($expected<>null){
		if($MyCurrentVersion<>$expected){
			_artica_update_event(1,"install_package(): Expected version:$expected does not match $MyCurrentVersion",$results,__FILE__,__LINE__);
			return;
		}
	}
	_artica_update_event(2,"install_package(): Success updating to a new version v$MyCurrentVersion",$results,__FILE__,__LINE__);
	
	if($RebootAfterArticaUpgrade==1){
		@file_put_contents("/usr/share/artica-postfix/download_progress", 100);
		_artica_update_event(1,"install_package() Reboot the server in 10s...",null,__FILE__,__LINE__);
		events("Reboot the server in 10s...");
		system_admin_events("Reboot the server in 10s...", __FUNCTION__, __FILE__, __LINE__, "artica-update");
		$shutdown=$unix->find_program("shutdown");
		shell_exec("shutdown -r -t 10");
		return true;
	}	
	
	_artica_update_event(2,"install_package(): restart dedicated services...",null,__FILE__,__LINE__);
	RestartDedicatedServices();
	_artica_update_event(2,"install_package(): finish",null,__FILE__,__LINE__);
	return true;
	
	
}

function RestartDedicatedServices(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	$unix->THREAD_COMMAND_SET("$php /usr/share/artica-postfix/exec.web-community-filter.php --register --force");
	events("Starting artica");
	echo "Starting......: ".date("H:i:s")." nightly builds starting artica...\n";
	@file_put_contents("/usr/share/artica-postfix/download_progress", 45);
	system("/etc/init.d/artica-postfix start");
	echo "Starting......: ".date("H:i:s")." nightly builds building init scripts\n";
	@file_put_contents("/usr/share/artica-postfix/download_progress", 50);
	system("$php /usr/share/artica-postfix/exec.initslapd.php --force >/dev/null 2>&1");
	echo "Starting......: ".date("H:i:s")." nightly builds updating network\n";
	@file_put_contents("/usr/share/artica-postfix/download_progress", 55);
	system("$php /usr/share/artica-postfix/exec.virtuals-ip.php >/dev/null 2>&1");
	system("$php /usr/share/artica-postfix/exec.monit.php --build >/dev/null 2>&1");
	echo "Starting......: ".date("H:i:s")." nightly builds purge and clean....\n";
	@file_put_contents("/usr/share/artica-postfix/download_progress", 60);
	shell_exec("$nohup /etc/init.d/slapd start >/dev/null 2>&1 &");
	shell_exec("$nohup /etc/init.d/artica-webconsole start >/dev/null 2>&1 &");
	if(is_file("/etc/init.d/nginx")){shell_exec("$nohup /etc/init.d/nginx reload >/dev/null 2>&1 &");}
	shell_exec("$nohup /etc/init.d/auth-tail restart >/dev/null 2>&1 &");
	shell_exec("$nohup /etc/init.d/artica-framework restart >/dev/null 2>&1 &");
	shell_exec("$nohup /usr/share/artica-postfix/bin/process1 -perm >/dev/null 2>&1 &");
	shell_exec("$nohup /usr/share/artica-postfix/bin/artica-make --empty-cache >/dev/null 2>&1 &");
	shell_exec("$nohup /etc/init.d/monit restart >/dev/null 2>&1 &");
	shell_exec("$nohup /etc/init.d/artica-status restart --force >/dev/null 2>&1 &");
	shell_exec("$nohup $php /usr/share/artica-postfix/exec.squid.php --build-schedules >/dev/null 2>&1 &");
	shell_exec("$nohup $php /usr/share/artica-postfix/exec.schedules.php --defaults >/dev/null 2>&1 &");
	
	if(is_file($squidbin)){
		squid_admin_mysql(1, "Reconfiguring proxy service",null,__FILE__,__LINE__);
		$cmd="/etc/init.d/squid reload --script=".basename(__FILE__)." >/dev/null 2>&1 &";
		shell_exec($cmd);
	
	}
	events("done");
	@file_put_contents("/usr/share/artica-postfix/download_progress", 100);
	echo "Starting......: ".date("H:i:s")." Done you can close the screen....\n";	
	_artica_update_event(2,"RestartDedicatedServices(): finish",null,__FILE__,__LINE__);
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
    	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/download_progress", $progress);
    	@chmod("/usr/share/artica-postfix/ressources/logs/web/download_progress", 0777);
    	$GLOBALS["previousProgress"]=$progress;
    }
}
function GetCurrentVersionString(){
	
	return trim(@file_get_contents("/usr/share/artica-postfix/VERSION"));
	
}

function GetCurrentVersion(){
   $result=0;
   $tmpstr=GetCurrentVersionString();
   $tmpstr=str_replace(".", "", $tmpstr);
   $result=intval($tmpstr);
   return $result;
}

function events($text=null){
	$dd=date("Y-m-d-H");
	$da=date("Y-m-d H:i:s");
	if($GLOBALS["OUTPUT"]){echo "$da: $text\n";}
	$file="/var/log/artica-postfix/artica-update-$dd.debug";
	@mkdir(dirname($file));
	$logFile=$file;
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
	$pid=getmypid();
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$da $pid $text\n");
	@fclose($f);
}
