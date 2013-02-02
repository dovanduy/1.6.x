<?php
$GLOBALS["DEBUG_INCLUDES"]=false;
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
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

if($argv[1]=="--update"){StartUpdate();die();}
if($argv[1]=="--buildconf"){buildConf();die();}
if($argv[1]=="--update-utility-httpd"){UpdateUtilityHttpd();die();}
if($argv[1]=="--UpdateUtility"){UpdateUtility();die();}


function buildConf(){
	
	$updaterbin="/opt/kaspersky/kav4proxy/bin/kav4proxy-keepup2date";
	if(!is_file($updaterbin)){return;}
	$t=time();
	$unix=new unix();
	$sock=new sockets();
	@mkdir("/var/run/Kav4Proxy",0777,true);
	$chmod=$unix->find_program("chmod");
	shell_exec("$chmod 777 /var/run/Kav4Proxy");
	
	$pidFile="/var/run/Kav4Proxy/keepup2date.pid";
	$UseProxy="no";
	$ProxyAddress=null;
	$datas=$sock->GET_INFO("ArticaProxySettings");
	$ArticaProxyServerEnabled="no";
	
	
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
		
	
	$UpdateServerUrl=null;
	$UseUpdateServerUrl="no";

	if(is_file("/var/db/kaspersky/databases/Updates/index/u0607g.xml")){
		$UpdateServerUrl="/var/db/kaspersky/databases/Updates";
		$UseUpdateServerUrl="yes";
		$UseProxy="no";
		$ProxyAddress=null;
	}else{
		if($GLOBALS["VERBOSE"]){echo "/var/db/kaspersky/databases/Updates/index/u0607g.xml no such file\n";}
	}
	
	$DateTime=date("Y-m-d_H-i-s");
	$logfile="/var/log/artica-postfix/kaspersky/kav4proxy/$DateTime"; 
	
	@mkdir("/opt/tmp",0755,true);
	@mkdir("/var/db/kav/databases",0755,true);
	@mkdir("/var/log/artica-postfix/kaspersky/kav4proxy",0755,true);
	shell_exec("$chmod 777 /opt/tmp");
	shell_exec("$chmod 777 /var/db/kav/databases");
	
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
	ufdbguard_admin_events("updating Kaspersky For Proxy server finish took $timehuman", __FUNCTION__, __FILE__, __LINE__, "update");
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
	if(!is_numeric($UpdateUtilityEnableHTTP)){$UpdateUtilityEnableHTTP=0;}
	if(!is_numeric($UpdateUtilityHTTPPort)){$UpdateUtilityHTTPPort=9222;}
	@mkdir("/var/run/UpdateUtility",0755,true);
	@mkdir("/var/log/UpdateUtility",0755,true);
	@mkdir("/var/db/kaspersky/databases/Updates",0755,true);
	@mkdir("/etc/UpdateUtility",0755,true);
	
	$f[]="server.modules = (\"mod_alias\",\"mod_access\",\"mod_accesslog\",\"mod_compress\")";
	$f[]="dir-listing.activate		  = \"enable\"";
	$f[]="server.document-root        = \"/var/db/kaspersky/databases/Updates\"";
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
	$unix=new unix();
	$sock=new sockets();
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/".basename(__FILE__).".pid";
	$pidtime="/etc/artica-postfix/".basename(__FILE__).".time";
	if($unix->file_time_min($pidtime)<3){die();}
	$oldpid=@file_get_contents($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$ProcessTime=$unix->PROCCESS_TIME_MIN($oldpid);
		writelogs("Process $oldpid  already in memory since $ProcessTime minutes","MAIN",__FILE__,__LINE__);
		die();
	}
	
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	@file_put_contents($pidfile, getmypid());	
	
	$UpdateUtility_Console=$unix->find_program("UpdateUtility-Console");
	if(!is_file($UpdateUtility_Console)){
		ufdbguard_admin_events("UpdateUtility-Console no such binary", __FUNCTION__, __FILE__, __LINE__, "update");
	}
	
	@copy($UpdateUtility_Console, "/etc/UpdateUtility/UpdateUtility-Console");
	$UpdateUtilityAllProducts=$sock->GET_INFO("UpdateUtilityAllProducts");
	if(!is_numeric($UpdateUtilityAllProducts)){$UpdateUtilityAllProducts=1;}
	$updateutility=new updateutilityv2();
	if($UpdateUtilityAllProducts==1){
		while (list ($key, $line) = each ($updateutility->ALL_PKEYS) ){
			$updateutility->MAIN_ARRAY["ComponentSettings"][$key]="true";
		}
	}
	
	$t=time();
	$ini=new Bs_IniHandler();
	$ini->_params=$updateutility->MAIN_ARRAY;
	$ini->saveFile("/etc/UpdateUtility/updater.ini");
	chdir("/etc/UpdateUtility");
	$tmp=$unix->FILE_TEMP();
	$cmd="./UpdateUtility-Console -u -o /etc/UpdateUtility/updater.ini -r >$tmp 2>&1";
	if(is_file("/etc/UpdateUtility/report.txt")){@unlink("/etc/UpdateUtility/report.txt");}
	writelogs("Running `$cmd`",__FUNCTION__,__FILE__,__LINE__);
	shell_exec("$cmd");
	$f=file($tmp);
	while (list ($key, $line) = each ($f) ){
		writelogs("$line",__FUNCTION__,__FILE__,__LINE__);
	}
	$t2=time();
	$timehuman=$unix->distanceOfTimeInWords($t,$t2);
	$text=@file_get_contents("/etc/UpdateUtility/report.txt");
	ufdbguard_admin_events("Executing UpdateUtility Success took $timehuman\n$text", __FUNCTION__, __FILE__, __LINE__, "update");
	@unlink("/etc/UpdateUtility/report.txt");
	
}


