<?php
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["FORCE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.templates.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.ini.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.squid.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::framework/class.unix.inc\n";}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::frame.class.inc\n";}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.updateutility2.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

if($argv[1]=="--update"){StartUpdate();die();}
if($argv[1]=="--buildconf"){buildConf();die();}
if($argv[1]=="--update-utility-httpd"){UpdateUtilityHttpd();die();}
if($argv[1]=="--UpdateUtility"){UpdateUtility();die();}
if($argv[1]=="--UpdateUtility-logs"){ScanUpdateUtilityLogs($GLOBALS["FORCE"]);die();}
if($argv[1]=="--UpdateUtility-size"){UpdateUtilitySize($GLOBALS["FORCE"]);die();}

echo "--update............: Perform Kaspersky Update\n";
echo "--buildconf.........: Perform Kaspersky For Proxy server Update configuration file\n";
echo "--UpdateUtility.....: Execute UpdateUtility\n";
echo "--UpdateUtility-logs: Execute UpdateUtility logs parser\n";
echo "--UpdateUtility-size: Calculate UpdateUtility storage size\n";


function buildConf(){
	
	$updaterbin="/opt/kaspersky/kav4proxy/bin/kav4proxy-keepup2date";
	if(!is_file($updaterbin)){return;}
	$t=time();
	$unix=new unix();
	$sock=new sockets();
	@mkdir("/var/run/Kav4Proxy",0777,true);
	@mkdir("/opt/tmp",0777,true);
	$chmod=$unix->find_program("chmod");
	shell_exec("$chmod 777 /var/run/Kav4Proxy");
	
	$pidFile="/var/run/Kav4Proxy/keepup2date.pid";
	$UseProxy="no";
	$ProxyAddress=null;
	$datas=$sock->GET_INFO("ArticaProxySettings");
	$ArticaProxyServerEnabled="no";
	$rm=$unix->find_program("rm");
	$ln=$unix->find_program("ln");
	
	if(trim($datas)<>null){
			$ini=new Bs_IniHandler();
			$ini->loadString($datas);
			$ArticaProxyServerEnabled=$ini->_params["PROXY"]["ArticaProxyServerEnabled"];
			$ArticaProxyServerName=$ini->_params["PROXY"]["ArticaProxyServerName"];
			$ArticaProxyServerPort=$ini->_params["PROXY"]["ArticaProxyServerPort"];
			$ArticaProxyServerUsername=$ini->_params["PROXY"]["ArticaProxyServerUsername"];
			$ArticaProxyServerUserPassword=$ini->_params["PROXY"]["ArticaProxyServerUserPassword"];
			$ArticaCompiledProxyUri=$ini->_params["PROXY"]["ArticaCompiledProxyUri"];
		}

		if($ArticaProxyServerEnabled=="yes"){
			if($ArticaProxyServerUsername){$auth="$ArticaProxyServerUsername:$ArticaProxyServerUserPassword@";}
			$ProxyAddress="http://$auth$ArticaProxyServerName:$ArticaProxyServerPort";
			$UseProxy="yes";
		}
		
	$UpdateUtilityStorePath=$sock->GET_INFO("UpdateUtilityStorePath");
	if($UpdateUtilityStorePath==null){$UpdateUtilityStorePath="/home/kaspersky/UpdateUtility";}
	$UpdateServerUrl=null;
	$UseUpdateServerUrl="no";

	if(is_file("$UpdateUtilityStorePath/databases/Updates/index/u0607g.xml")){
		$UpdateServerUrl="$UpdateUtilityStorePath/databases/Updates";
		$UseUpdateServerUrl="yes";
		$UseProxy="no";
		$ProxyAddress=null;
	}else{
		if($GLOBALS["VERBOSE"]){echo "$UpdateUtilityStorePath/databases/Updates/index/u0607g.xml no such file\n";}
	}
	
	$DateTime=date("Y-m-d_H-i-s");
	$logfile="/var/log/artica-postfix/kaspersky/kav4proxy/$DateTime"; 
	
	@mkdir("/opt/tmp",0755,true);
	@mkdir("/var/db/kav/databases",0755,true);
	@mkdir("/var/log/artica-postfix/kaspersky/kav4proxy",0755,true);
	shell_exec("$chmod 777 /opt/tmp");
	shell_exec("$chmod 777 /var/db/kav/databases");
	
	shell_exec("$rm -rf /var/opt/kaspersky/kav4proxy/bases");
	shell_exec("$ln -s /var/db/kav/databases /var/opt/kaspersky/kav4proxy/bases");
	
	
	$f[]="[path]";
	$f[]="BasesPath=/var/db/kav/databases";
	$f[]="LicensePath=/var/opt/kaspersky/kav4proxy/licenses";
	$f[]="TempPath=/opt/tmp/";
	$f[]="[updater.path]";
	$f[]="BackUpPath=/var/opt/kaspersky/kav4proxy/bases.backup";
	$f[]="#AVBasesTestPath=/opt/kaspersky/kav4proxy/lib/bin/avbasestest";
	$f[]="[updater.options]";
	$f[]="KeepSilent=no";
	$f[]="UpdateServerUrl=$UpdateServerUrl";
	$f[]="UseUpdateServerUrl=$UseUpdateServerUrl";
	$f[]="UseUpdateServerUrlOnly=no";
	$f[]="PostUpdateCmd=/etc/init.d/kav4proxy reload_avbase";
	$f[]="RegionSettings=Europe";
	$f[]="ConnectTimeout=30";
	$f[]="ProxyAddress=$ProxyAddress";
	$f[]="UseProxy=$UseProxy";
	$f[]="PassiveFtp=no";
	$f[]="[updater.report]";
	$f[]="ReportFileName=$logfile";
	$f[]="ReportLevel=4";
	$f[]="Append=true";
	$f[]="";	
	
	$tmpFileName="/etc/artica-postfix/kav4proxy-keepup2date.conf";
	@file_put_contents($tmpFileName, @implode("\n", $f));	
	
	
}


function StartUpdate(){
	$updaterbin="/opt/kaspersky/kav4proxy/bin/kav4proxy-keepup2date";
	if(!is_file($updaterbin)){return;}
	$t=time();
	ufdbguard_admin_events("Starting updating Kaspersky For Proxy server", __FUNCTION__, __FILE__, __LINE__, "update");
	$unix=new unix();
	$sock=new sockets();
	@mkdir("/var/run/Kav4Proxy",0777,true);
	$chmod=$unix->find_program("chmod");
	shell_exec("$chmod 777 /var/run/Kav4Proxy");
	
	$pidFile="/var/run/Kav4Proxy/keepup2date.pid";
	$oldpid=$unix->get_pid_from_file($pidFile);
	if($unix->process_exists($oldpid)){
		ufdbguard_admin_events("Other instance $oldpid running, aborting task", __FUNCTION__, __FILE__, __LINE__, "update");
		return;
	}
	
	
	@mkdir("/opt/tmp",0755,true);
	@mkdir("/var/db/kav/databases",0755,true);
	@mkdir("/var/log/artica-postfix/kaspersky/kav4proxy",0755,true);
	shell_exec("$chmod 777 /opt/tmp");
	shell_exec("$chmod 777 /var/db/kav/databases");
	buildConf();
	$logfile="/var/log/artica-postfix/kaspersky/kav4proxy/".date("Y-m-d_H-i-s");
	$tmpFileName="/etc/artica-postfix/kav4proxy-keepup2date.conf";
	$nice=EXEC_NICE();
	$cmd="$nice$updaterbin -d $pidFile -c $tmpFileName -l $logfile 2>&1";
	ufdbguard_admin_events("$cmd", __FUNCTION__, __FILE__, __LINE__, "update");
	shell_exec($cmd);
	$t2=time();
	
	$timehuman=$unix->distanceOfTimeInWords($t,$t2);
	ufdbguard_admin_events("Updating Kaspersky For Proxy server finish took $timehuman", __FUNCTION__, __FILE__, __LINE__, "update");
	ufdbguard_admin_events(@file_get_contents($logfile), __FUNCTION__, __FILE__, __LINE__, "update");
	$t=file($logfile);
	while (list ($index, $line) = each ($t) ){
		if(preg_match("#^\[.*?F\]\s+(.+)#", $line,$re)){
			ufdbguard_admin_events("Failed: {$re[1]}", __FUNCTION__, __FILE__, __LINE__, "update");
		}
	}
	if($GLOBALS["VERBOSE"]){$verb=" --verbose";}
	shell_exec($unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.kaspersky-update-logs.php --force$verb");
	
	
}

function UpdateUtilityHttpd(){
	
	$sock=new sockets();
	$UpdateUtilityEnableHTTP=$sock->GET_INFO("UpdateUtilityEnableHTTP");
	$UpdateUtilityHTTPPort=$sock->GET_INFO("UpdateUtilityHTTPPort");
	$UpdateUtilityHTTPIP=$sock->GET_INFO("UpdateUtilityHTTPIP");
	$UpdateUtilityUseLoop=$sock->GET_INFO("UpdateUtilityUseLoop");
	
	if(!is_numeric($UpdateUtilityEnableHTTP)){$UpdateUtilityEnableHTTP=0;}
	if(!is_numeric($UpdateUtilityUseLoop)){$UpdateUtilityUseLoop=0;}
	if(!is_numeric($UpdateUtilityHTTPPort)){$UpdateUtilityHTTPPort=9222;}
	$UpdateUtilityStorePath=$sock->GET_INFO("UpdateUtilityStorePath");
	if($UpdateUtilityStorePath==null){$UpdateUtilityStorePath="/home/kaspersky/UpdateUtility";}	
	
	if($UpdateUtilityUseLoop==1){$UpdateUtilityStorePath="/automounts/UpdateUtility";}
	
	
	@mkdir("/var/run/UpdateUtility",0755,true);
	@mkdir("/var/log/UpdateUtility",0755,true);
	@mkdir("$UpdateUtilityStorePath/databases/Updates",0755,true);
	
	if(!is_dir($UpdateUtilityStorePath)){
		@file_put_contents("/var/log/artica-postfix/UpdateUtility-". time().".log", "Report finished at " .date("Y-m-d H:i:s")."\n$UpdateUtilityStorePath/databases/Updates permission denied!\n");
		return;
	}
	
	@mkdir("/etc/UpdateUtility",0755,true);
	
	$f[]="server.modules = (\"mod_alias\",\"mod_access\",\"mod_accesslog\",\"mod_compress\")";
	$f[]="dir-listing.activate		  = \"enable\"";
	$f[]="server.document-root        = \"$UpdateUtilityStorePath/databases/Updates\"";
	$f[]="server.errorlog             = \"/var/log/UpdateUtility/error.log\"";
	$f[]="index-file.names            = ( \"index.php\",\"index.html\",\"index.htm\")";
	$f[]="";
	$f[]="mimetype.assign             = (";
	$f[]="  \".pdf\"          =>      \"application/pdf\",";
	$f[]="  \".sig\"          =>      \"application/pgp-signature\",";
	$f[]="  \".spl\"          =>      \"application/futuresplash\",";
	$f[]="  \".class\"        =>      \"application/octet-stream\",";
	$f[]="  \".ps\"           =>      \"application/postscript\",";
	$f[]="  \".torrent\"      =>      \"application/x-bittorrent\",";
	$f[]="  \".dvi\"          =>      \"application/x-dvi\",";
	$f[]="  \".gz\"           =>      \"application/x-gzip\",";
	$f[]="  \".pac\"          =>      \"application/x-ns-proxy-autoconfig\",";
	$f[]="  \".swf\"          =>      \"application/x-shockwave-flash\",";
	$f[]="  \".tar.gz\"       =>      \"application/x-tgz\",";
	$f[]="  \".tgz\"          =>      \"application/x-tgz\",";
	$f[]="  \".tar\"          =>      \"application/x-tar\",";
	$f[]="  \".zip\"          =>      \"application/zip\",";
	$f[]="  \".mp3\"          =>      \"audio/mpeg\",";
	$f[]="  \".m3u\"          =>      \"audio/x-mpegurl\",";
	$f[]="  \".wma\"          =>      \"audio/x-ms-wma\",";
	$f[]="  \".wax\"          =>      \"audio/x-ms-wax\",";
	$f[]="  \".ogg\"          =>      \"application/ogg\",";
	$f[]="  \".wav\"          =>      \"audio/x-wav\",";
	$f[]="  \".gif\"          =>      \"image/gif\",";
	$f[]="  \".jar\"          =>      \"application/x-java-archive\",";
	$f[]="  \".jpg\"          =>      \"image/jpeg\",";
	$f[]="  \".jpeg\"         =>      \"image/jpeg\",";
	$f[]="  \".png\"          =>      \"image/png\",";
	$f[]="  \".xbm\"          =>      \"image/x-xbitmap\",";
	$f[]="  \".xpm\"          =>      \"image/x-xpixmap\",";
	$f[]="  \".xwd\"          =>      \"image/x-xwindowdump\",";
	$f[]="  \".css\"          =>      \"text/css\",";
	$f[]="  \".html\"         =>      \"text/html\",";
	$f[]="  \".htm\"          =>      \"text/html\",";
	$f[]="  \".js\"           =>      \"text/javascript\",";
	$f[]="  \".asc\"          =>      \"text/plain\",";
	$f[]="  \".c\"            =>      \"text/plain\",";
	$f[]="  \".cpp\"          =>      \"text/plain\",";
	$f[]="  \".log\"          =>      \"text/plain\",";
	$f[]="  \".conf\"         =>      \"text/plain\",";
	$f[]="  \".text\"         =>      \"text/plain\",";
	$f[]="  \".txt\"          =>      \"text/plain\",";
	$f[]="  \".dtd\"          =>      \"text/xml\",";
	$f[]="  \".xml\"          =>      \"text/xml\",";
	$f[]="  \".mpeg\"         =>      \"video/mpeg\",";
	$f[]="  \".mpg\"          =>      \"video/mpeg\",";
	$f[]="  \".mov\"          =>      \"video/quicktime\",";
	$f[]="  \".qt\"           =>      \"video/quicktime\",";
	$f[]="  \".avi\"          =>      \"video/x-msvideo\",";
	$f[]="  \".asf\"          =>      \"video/x-ms-asf\",";
	$f[]="  \".asx\"          =>      \"video/x-ms-asf\",";
	$f[]="  \".wmv\"          =>      \"video/x-ms-wmv\",";
	$f[]="  \".bz2\"          =>      \"application/x-bzip\",";
	$f[]="  \".tbz\"          =>      \"application/x-bzip-compressed-tar\",";
	$f[]="  \".tar.bz2\"      =>      \"application/x-bzip-compressed-tar\",";
	$f[]="  \"\"              =>      \"application/octet-stream\",";
	$f[]=" )";
	$f[]="";
	$f[]="";
	$f[]="accesslog.filename          = \"/var/log/UpdateUtility/access.log\"";
	$f[]="#url.access-deny             = ( \"~\", \".inc\" )";
	$f[]="#static-file.exclude-extensions = ( \".php\", \".pl\", \".fcgi\" )";
	$f[]="server.port                 = $UpdateUtilityHTTPPort";
	if($UpdateUtilityHTTPIP<>null){
		$f[]="server.bind                = \"$UpdateUtilityHTTPIP\"";
	}
	$f[]="#server.error-handler-404   = \"/error-handler.html\"";
	$f[]="#server.error-handler-404   = \"/error-handler.php\"";
	$f[]="server.pid-file             = \"/var/run/UpdateUtility/lighttpd.pid\"";
	$f[]="server.max-keep-alive-requests = 0";
	$f[]="server.max-keep-alive-idle = 4";
	$f[]="server.stat-cache-engine = \"simple\"";
	$f[]="server.max-fds 		   = 2048";
	$f[]="server.network-backend      = \"writev\"";
	$f[]="ssl.engine                 = \"disable\"";
	$f[]="#alias.url += ( \"/cgi-bin/\" => \"/usr/lib/cgi-bin/\" )";
	$f[]="#alias.url += ( \"/css/\" => \"/usr/share/artica-postfix/css/\" )";
	$f[]="#alias.url += ( \"/img/\" => \"/usr/share/artica-postfix/img/\" )";
	$f[]="#alias.url += ( \"/js/\" => \"/usr/share/artica-postfix/js/\" )";
	$f[]="";
	$f[]="#cgi.assign= (";
	$f[]="#	\".pl\"  => \"/usr/bin/perl\",";
	$f[]="#	\".php\" => \"/usr/bin/php-cgi\",";
	$f[]="#	\".py\"  => \"/usr/bin/python\",";
	$f[]="#	\".cgi\"  => \"/usr/bin/perl\",";
	$f[]="#)";

	@file_put_contents("/etc/UpdateUtility/lighttpd.conf", @implode("\n", $f));
	
}

function UpdateUtility(){
	if($GLOBALS["VERBOSE"]){echo "Line: ".__LINE__.":: ".__FUNCTION__."\n";}
	$unix=new unix();
	$sock=new sockets();
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/".basename(__FILE__).".pid";
	$pidtime="/etc/artica-postfix/".basename(__FILE__).".time";
	$pidtimeT=$unix->file_time_min($pidtime);
	if($pidtimeT<3){
		if(!$GLOBALS["FORCE"]){
			if($GLOBALS["VERBOSE"]){echo "Line: ".__LINE__.":: last execution time $pidtimeT (require 3mn) or set --force ".__FUNCTION__."\n";}
			die();
		}
	
	}
	$oldpid=@file_get_contents($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$ProcessTime=$unix->PROCCESS_TIME_MIN($oldpid);
		if($GLOBALS["VERBOSE"]){echo "Line: ".__LINE__.":: Process $oldpid  already in memory since $ProcessTime minutes - ".__FUNCTION__."\n";}
		system_admin_events("Process $oldpid  already in memory since $ProcessTime minutes","MAIN",__FILE__,__LINE__,"updateutility");
		die();
	}
	
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	@file_put_contents($pidfile, getmypid());	
	
	$UpdateUtility_Console=$unix->find_program("UpdateUtility-Console");
	if(!is_file($UpdateUtility_Console)){
		if($GLOBALS["VERBOSE"]){echo "Line: ".__LINE__.":: UpdateUtility-Console no such binary - ".__FUNCTION__."\n";}
		system_admin_events("UpdateUtility-Console no such binary", __FUNCTION__, __FILE__, __LINE__, "update");
	}
	
	
	if($GLOBALS["VERBOSE"]){echo "Line: ".__LINE__.":: UpdateUtility_Console - $UpdateUtility_Console".__FUNCTION__."\n";}
	@copy($UpdateUtility_Console, "/etc/UpdateUtility/UpdateUtility-Console");
	$UpdateUtilityAllProducts=$sock->GET_INFO("UpdateUtilityAllProducts");
	$UpdateUtilityUseLoop=$sock->GET_INFO("UpdateUtilityUseLoop");
	if(!is_numeric($UpdateUtilityAllProducts)){$UpdateUtilityAllProducts=1;}
	$UpdateUtilityStorePath=$sock->GET_INFO("UpdateUtilityStorePath");
	if($UpdateUtilityStorePath==null){$UpdateUtilityStorePath="/home/kaspersky/UpdateUtility";}
	if($UpdateUtilityUseLoop==1){
		$UpdateUtilityStorePath="/automounts/UpdateUtility";
		$dev=$unix->MOUNTED_DIR($UpdateUtilityStorePath);
		if($dev==null){
			if($GLOBALS["VERBOSE"]){echo "Line: ".__LINE__.":: $UpdateUtilityStorePath -> NOT MOUNTED ".__FUNCTION__."\n";}
			@file_put_contents("/var/log/artica-postfix/UpdateUtility-report-". time().".log", "Report finished at " .date("Y-m-d H:i:s")."\n$UpdateUtilityStorePath not mounted!\n");
			return;			
		}
		if($GLOBALS["VERBOSE"]){echo "Line: ".__LINE__.":: $UpdateUtilityStorePath -> $dev OK".__FUNCTION__."\n";}
	}
		
		
		
	@mkdir("$UpdateUtilityStorePath/databases/Updates",0755,true);
	
	if(!is_dir($UpdateUtilityStorePath)){
		@file_put_contents("/var/log/artica-postfix/UpdateUtility-report-". time().".log", "Report finished at " .date("Y-m-d H:i:s")."\n$UpdateUtilityStorePath/databases/Updates permission denied!\n");
		return;
	}	
	
	$updateutility=new updateutilityv2();
	if($UpdateUtilityAllProducts==1){
		system_admin_events("All products as been set...", __FUNCTION__, __FILE__, __LINE__, "update");
		while (list ($key, $line) = each ($updateutility->ALL_PKEYS) ){$updateutility->MAIN_ARRAY["ComponentSettings"][$key]="true";}
		if(!isset($updateutility->MAIN_ARRAY["ComponentSettings"]["DownloadAllDatabases"])){$updateutility->MAIN_ARRAY["ComponentSettings"]["DownloadAllDatabases"]="true";}
		if(!isset($updateutility->MAIN_ARRAY["ComponentSettings"]["KasperskyAdministrationKit_8_0_2048_2090"])){$updateutility->MAIN_ARRAY["ComponentSettings"]["KasperskyAdministrationKit_8_0_2048_2090"]="true";}
		if(!isset($updateutility->MAIN_ARRAY["ComponentSettings"]["KasperskySecurityCenter_9"])){$updateutility->MAIN_ARRAY["ComponentSettings"]["KasperskySecurityCenter_9"]="true";}		
	}
	
	$t=time();
	$ini=new Bs_IniHandler();
	$ini2=new Bs_IniHandler();
	$ini->_params=$updateutility->MAIN_ARRAY;
	$ini->_params["DirectoriesSettings"]["UpdatesFolder"]="$UpdateUtilityStorePath/databases";
	$ini->_params["DirectoriesSettings"]["TempFolder"]="$UpdateUtilityStorePath/TempFolder";
	
	@mkdir("$UpdateUtilityStorePath/databases",0755,true);
	@mkdir("$UpdateUtilityStorePath/TempFolder",0755,true);
	
	
	
	
	$report_file="/var/log/artica-postfix/UpdateUtility-report-".time().".log";
	$ini->_params["ConnectionSettings"]["UseSpecifiedProxyServerSettings"]="false";
	$ini->_params["ConnectionSettings"]["UseAuthenticationProxyServer"]="false";
	$ini->_params["ReportSettings"]["ReportFileName"]="$report_file";
	
		
	
	
	$datas=$sock->GET_INFO("ArticaProxySettings");
	if(trim($datas)<>null){
		$ini2->loadString($datas);
		$ArticaProxyServerEnabled=$ini->_params["PROXY"]["ArticaProxyServerEnabled"];
		$ArticaProxyServerName=$ini->_params["PROXY"]["ArticaProxyServerName"];
		$ArticaProxyServerPort=$ini->_params["PROXY"]["ArticaProxyServerPort"];
		$ArticaProxyServerUsername=trim($ini->_params["PROXY"]["ArticaProxyServerUsername"]);
		$ArticaProxyServerUserPassword=$ini->_params["PROXY"]["ArticaProxyServerUserPassword"];
		if($ArticaProxyServerEnabled=="yes"){
			$ini->_params["ConnectionSettings"]["UseSpecifiedProxyServerSettings"]="true";
			$ini->_params["ConnectionSettings"]["AddressProxyServer"]=$ArticaProxyServerName;
			$ini->_params["ConnectionSettings"]["PortProxyServer"]=$ArticaProxyServerPort;
			if($ArticaProxyServerUsername<>null){
				$ini->_params["ConnectionSettings"]["UseAuthenticationProxyServer"]="true";
				$ini->_params["ConnectionSettings"]["UserNameProxyServer"]="$ArticaProxyServerUsername";
				$ini->_params["ConnectionSettings"]["PasswordProxyServer"]="$ArticaProxyServerUserPassword";
			}
			
		}
	}

	
	
	$ini->saveFile("/etc/UpdateUtility/updater.ini");
	chdir("/etc/UpdateUtility");
	@chmod("/etc/UpdateUtility/UpdateUtility-Console", 0755);
	$cmd="./UpdateUtility-Console -u -o /etc/UpdateUtility/updater.ini -r 2>&1";
	
	writelogs("Running `$cmd`",__FUNCTION__,__FILE__,__LINE__);
	exec("$cmd",$results);
	
	while (list ($key, $line) = each ($results) ){
		writelogs("$line",__FUNCTION__,__FILE__,__LINE__);
	}
	$t2=time();
	$timehuman=$unix->distanceOfTimeInWords($t,$t2);
	$text=@implode("\n", $results);
	system_admin_events("Executing UpdateUtility Success took $timehuman\n$text", __FUNCTION__, __FILE__, __LINE__, "update");
	
	
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$nohup $php ".dirname(__FILE__)."/exec.freeweb.php --reconfigure-updateutility >/dev/null 2>&1 &");
	ScanUpdateUtilityLogs(true);
	UpdateUtilitySize(true);
}

function ScanUpdateUtilityLogs($force=false){
	$unix=new unix();
	$sock=new sockets();
	
	
	$unix=new unix();
	$timefile="/etc/artica-postfix/pids/ScanUpdateUtilityLogs.time";
	$pidfile="/etc/artica-postfix/pids/ScanUpdateUtilityLogs.pid";
	
	
	if(!$force){
	
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["VERBOSE"]){echo "Already process exists $oldpid\n";}
			return;
		}
	
		@file_put_contents($pidfile, getmypid());
		$time=$unix->file_time_min($timefile);
		if($timefile<10){
			if($GLOBALS["VERBOSE"]){echo "Only each 10mn\n";}
			return;}
	}	
	
	@unlink($timefile);
	@file_put_contents($timefile, time());
	
	
	$UpdateUtilityStorePath=$sock->GET_INFO("UpdateUtilityStorePath");
	if($UpdateUtilityStorePath==null){$UpdateUtilityStorePath="/home/kaspersky/UpdateUtility";}

	$UpdateUtilityUseLoop=$sock->GET_INFO("UpdateUtilityUseLoop");
	if(!is_numeric($UpdateUtilityUseLoop)){$UpdateUtilityUseLoop=0;}
	if($UpdateUtilityUseLoop==1){$UpdateUtilityStorePath="/automounts/UpdateUtility";}	
	
	if($GLOBALS["VERBOSE"]){echo "Scanning /var/log/artica-postfix/UpdateUtility-*.log...\n";}
	
	foreach (glob("/var/log/artica-postfix/UpdateUtility-*.log") as $filename) {
		$timefile=$unix->file_time_min($filename);
		$time=filemtime($filename);
		$details= @file_get_contents($filename);
		$f=explode("\n", $details);
		$isSuccess=1;
		$files=0;
		$size=0;
		$rp_finish=false;
		if($timefile>1440){$rp_finish=true;}
		
		
		while (list ($key, $line) = each ($f) ){
			if(preg_match("#New file installed '(.*?)'#", $line,$re)){
				$nextFile="$UpdateUtilityStorePath/databases/Updates/{$re[1]}";
				if(!is_file($nextFile)){
					if($GLOBALS["VERBOSE"]){
						echo "$nextFile, no such file\n";
					}
					
					$nextFile="$UpdateUtilityStorePath/TempFolder/temporaryFolder/{$re[1]}";
				}
				if(!is_file($nextFile)){
					if($GLOBALS["VERBOSE"]){
						echo "$nextFile, no such file\n";
					}
					continue;}
				$files++;
				$size=$size+$unix->file_size($nextFile);
				continue;
			}
			
			if(preg_match("#Report finished at#", $line,$re)){$rp_finish=true;continue;}
			if(preg_match("#Insufficient disk space#i", $line,$re)){$isSuccess=0;continue;}
			if(preg_match("#Failed to#i", $line,$re)){$isSuccess=0;continue;}	
			if(preg_match("#not retranslated#i", $line,$re)){$isSuccess=0;continue;}	
			if(preg_match("#Retranslation operation result 'Success'#i", $line,$re)){$isSuccess=1;continue;}
		}
	
		if(!$rp_finish){
			if($GLOBALS["VERBOSE"]){echo "Not finished $filename\n";}
			continue;
		}
		
		$date=date("Y-m-d H:i:s",$time);
		if(preg_match("#UpdateUtility-.*?([0-9]+)\.log$#",basename($filename),$re)){
			$date=date("Y-m-d H:i:s",$re[1]);
		}
		
		echo "$date $files downloaded $size bytes\n";
		$q=new mysql();
		$details=mysql_escape_string($details);
		$q->QUERY_SQL("INSERT INTO updateutilityev (`zDate`,`filesize`,`filesnum`,`details`,`isSuccess`) 
				VALUES ('$date','$files','$size','$details','$isSuccess')","artica_events");
		if(!$q->ok){continue;}
		@unlink($filename);
		continue;
		
	}	
}
function UpdateUtilitySize($force=false){


	$unix=new unix();
	$arrayfile="/usr/share/artica-postfix/ressources/logs/web/UpdateUtilitySize.size.db";
	$pidfile="/etc/artica-postfix/pids/UpdateUtilitySize.pid";


	if(!$force){
		
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			return;
		}

		@file_put_contents($pidfile, getmypid());
		$time=$unix->file_time_min($arrayfile);
		if($arrayfile<20){return;}
	}

	$sock=new sockets();
	$dir=$sock->GET_INFO("UpdateUtilityStorePath");
	if($dir==null){$dir="/home/kaspersky/UpdateUtility";}
	
	$UpdateUtilityUseLoop=$sock->GET_INFO("UpdateUtilityUseLoop");
	if(!is_numeric($UpdateUtilityUseLoop)){$UpdateUtilityUseLoop=0;}
	if($UpdateUtilityUseLoop==1){$dir="/automounts/UpdateUtility";}	
	
	if(is_link($dir)){$dir=readlink($dir);}
	$unix=new unix();
	$sizbytes=$unix->DIRSIZE_BYTES($dir);
	$dir=$unix->shellEscapeChars($dir);
	$df=$unix->find_program("df");
	$array["DBSIZE"]=$sizbytes/1024;
	exec("$df -B K $dir 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#^.*?\s+([0-9A-Z\.]+)K\s+([0-9A-Z\.]+)K\s+([0-9A-Z\.]+)K\s+([0-9\.]+)%\s+(.+)#", $ligne,$re)){
			$array["SIZE"]=$re[1];
			$array["USED"]=$re[2];
			$array["AIVA"]=$re[3];
			$array["POURC"]=$re[4];
			$array["MOUNTED"]=$re[5];
			break;
		}
	}
	
	$results=array();
	exec("$df -i $dir 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#^.*?\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9\.]+)%\s+(.+)#", $ligne,$re)){
			$array["ISIZE"]=$re[1];
			$array["IUSED"]=$re[2];
			$array["IAIVA"]=$re[3];
			$array["IPOURC"]=$re[4];
			break;
		}
	}	

	if($GLOBALS["VERBOSE"]) {print_r($array);}

	@unlink($arrayfile);
	@file_put_contents($arrayfile, serialize($array));
	if($GLOBALS["VERBOSE"]) {echo "Saving $arrayfile...\n";}

	@chmod($arrayfile, 0755);

}

