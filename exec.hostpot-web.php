<?php
if(is_file("/etc/artica-postfix/FROM_ISO")){if(is_file("/etc/init.d/artica-cd")){print "Starting......: ".date("H:i:s")." artica-". basename(__FILE__)." Waiting Artica-CD to finish\n";die();}}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["SERVICE_NAME"]="HotSpot Web service";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');



	$GLOBALS["ARGVS"]=implode(" ",$argv);
	if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;apache_stop();die();}
	if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;apache_start();die();}
	if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
	
	


function restart($nopid=false){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if(!$nopid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
	}
	@file_put_contents($pidfile, getmypid());
	apache_stop(true);
	apache_config();
	apache_start(true);	
}	

function apache_stop(){
	
	$unix=new unix();
	$pid=apache_pid();
	$sock=new sockets();
	
	$EnableArticaHotSpot=$sock->GET_INFO("EnableArticaHotSpot");
	$SquidHotSpotPort=$sock->GET_INFO("SquidHotSpotPort");
	$ArticaHotSpotPort=$sock->GET_INFO("ArticaHotSpotPort");
	$ArticaSSLHotSpotPort=$sock->GET_INFO("ArticaSSLHotSpotPort");
	$ArticaSplashHotSpotPort=$sock->GET_INFO("ArticaSplashHotSpotPort");
	$ArticaSplashHotSpotPortSSL=$sock->GET_INFO("ArticaSplashHotSpotPortSSL");
	if(!is_numeric($ArticaHotSpotPort)){$ArticaHotSpotPort=0;}
	if(!is_numeric($ArticaSplashHotSpotPort)){$ArticaSplashHotSpotPort=16080;}
	if(!is_numeric($ArticaSplashHotSpotPortSSL)){$ArticaSplashHotSpotPortSSL=16443;}
	

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} already stopped...\n";}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} testing $ArticaHttpsPort port...\n";}
		fuser_port($ArticaSplashHotSpotPort);
		fuser_port($ArticaSplashHotSpotPortSSL);
		return;
	}
	
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	$apache2ctl=$unix->LOCATE_APACHE_CTL();
	


	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Shutdown pid $pid...\n";}
	shell_exec("$apache2ctl -f /etc/artica-postfix/hotspot-httpd.conf -k stop");
	for($i=0;$i<5;$i++){
		$pid=apache_pid();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=apache_pid();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=apache_pid();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";}
		sleep(1);
	}
	

	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} testing $ArticaSplashHotSpotPortSSL port...\n";}
	fuser_port($ArticaSplashHotSpotPortSSL);
	fuser_port($ArticaSplashHotSpotPort);
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success...\n";}
		return;
	}else{
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} failed...\n";}
	}
}

function fuser_port($port){
	$unix=new unix();
	$kill=$unix->find_program("kill");
	$PIDS=$unix->PIDOF_BY_PORT($port);
	if(count($PIDS)==0){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} 0 PID listens $port...\n";}
		
		return;}
	while (list ($pid, $b) = each ($PIDS) ){
		if($unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} killing PID $pid that listens $port\n";}
			unix_system_kill_force($pid);
		}
	}
}


function ToSyslog($text){

	$LOG_SEV=LOG_INFO;
	if(function_exists("openlog")){openlog(basename(__FILE__), LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
	if(function_exists("closelog")){closelog();}
}

function isModule($modulename){
	$LOAD_MODULES=LOAD_MODULES();
	$modulename=trim(strtolower($modulename));
	if(isset($LOAD_MODULES[$modulename])){return true;}
	$libdir=LIGHTTPD_MODULES_PATH();
	if(is_file("$libdir/$modulename.so")){return true;}
	return false;
}
//##############################################################################   
function LOAD_MODULES(){
	
	if(isset($GLOBALS["LIGHTTPDMODS"])){return $GLOBALS["LIGHTTPDMODS"];}
	$unix=new unix();
	$lighttpd=$unix->find_program("lighttpd");
	if(!is_file($lighttpd)){return;}
	exec("$lighttpd -V 2>&1",$results);
	while (list ($pid, $line) = each ($results) ){
		if(preg_match('#\+\s+(.+?)\s+support#',$line,$re)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Available module.....: \"{$re[1]}\"\n";}
			$re[1]=trim(strtolower($re[1]));
			$GLOBALS["LIGHTTPDMODS"][$re[1]]=true;
			continue;
		}
			
	}
}	

function apache_pid(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file('/var/run/artica-apache/hotspot-apache.pid');
	if($unix->process_exists($pid)){return $pid;}
	$apache2ctl=$unix->LOCATE_APACHE_CTL();
	return $unix->PIDOF_PATTERN($apache2ctl." -f /etc/artica-postfix/hotspot-httpd.conf");
}


function apache_start(){
	$unix=new unix();
	$apachebin=$unix->LOCATE_APACHE_BIN_PATH();
	$sock=new sockets();
	
	
	$pid=apache_pid();
	
	$EnableArticaHotSpot=$sock->GET_INFO("EnableArticaHotSpot");
	$SquidHotSpotPort=$sock->GET_INFO("SquidHotSpotPort");
	$ArticaHotSpotPort=$sock->GET_INFO("ArticaHotSpotPort");
	$ArticaSSLHotSpotPort=$sock->GET_INFO("ArticaSSLHotSpotPort");
	$ArticaSplashHotSpotPort=$sock->GET_INFO("ArticaSplashHotSpotPort");
	$ArticaSplashHotSpotPortSSL=$sock->GET_INFO("ArticaSplashHotSpotPortSSL");
	if(!is_numeric($ArticaHotSpotPort)){$ArticaHotSpotPort=0;}
	if(!is_numeric($ArticaSplashHotSpotPort)){$ArticaSplashHotSpotPort=16080;}
	if(!is_numeric($ArticaSplashHotSpotPortSSL)){$ArticaSplashHotSpotPortSSL=16443;}
	
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} already started $pid since {$timepid}Mn...\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Listen HTTP on $ArticaSplashHotSpotPort SSL on $ArticaSplashHotSpotPortSSL\n";}
		return;
	}

	
	if($EnableArticaHotSpot==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} disabled (see EnableArticaHotSpot)\n";}
		apache_stop(true);
		return;
	}
	
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$apache2ctl=$unix->LOCATE_APACHE_BIN_PATH();
	
	
	
	apache_config();
		
	$cmd="$apache2ctl -f /etc/artica-postfix/hotspot-httpd.conf -k start";
	shell_exec($cmd);
	
	
	
	
	for($i=0;$i<6;$i++){
		$pid=apache_pid();
		if($unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting $i/6...\n";}
		sleep(1);
	}
	
	
	$pid=apache_pid();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Success service started pid:$pid...\n";}
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} failed...\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $cmd\n";}
	}		
	
	
}

function apache_LOCATE_MIME_TYPES(){
	if(is_file("/etc/mime.types")){return "/etc/mime.types";}
	if(is_file("/etc/apache2/mime.types")){return "/etc/apache2/mime.types";}
	if(is_file("/etc/httpd/mime.types")){return "/etc/httpd/mime.types";}
}


function apache_config(){
	$sock=new sockets();
	$unix=new unix();
	$EnablePHPFPM=0;
	@mkdir("/var/run/apache2",0755,true);
	@mkdir("/var/run/artica-apache",0755,true);
	$APACHE_SRC_ACCOUNT=$unix->APACHE_SRC_ACCOUNT();
	$APACHE_SRC_GROUP=$unix->APACHE_SRC_GROUP();
	$APACHE_MODULES_PATH=$unix->APACHE_MODULES_PATH();

	
	$EnableArticaHotSpot=$sock->GET_INFO("EnableArticaHotSpot");
	$SquidHotSpotPort=$sock->GET_INFO("SquidHotSpotPort");
	$ArticaHotSpotPort=$sock->GET_INFO("ArticaHotSpotPort");
	$ArticaSSLHotSpotPort=$sock->GET_INFO("ArticaSSLHotSpotPort");
	$ArticaSplashHotSpotPort=$sock->GET_INFO("ArticaSplashHotSpotPort");
	$ArticaSplashHotSpotPortSSL=$sock->GET_INFO("ArticaSplashHotSpotPortSSL");
	if(!is_numeric($ArticaHotSpotPort)){$ArticaHotSpotPort=0;}
	if(!is_numeric($ArticaSplashHotSpotPort)){$ArticaSplashHotSpotPort=16080;}
	if(!is_numeric($ArticaSplashHotSpotPortSSL)){$ArticaSplashHotSpotPortSSL=16443;}
	$ArticaHotSpotInterface=$sock->GET_INFO("ArticaHotSpotInterface");
	
	$unix=new unix();
	$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
	$ipaddr=$NETWORK_ALL_INTERFACES[$ArticaHotSpotInterface]["IPADDR"];
	
	
	if($ipaddr=="0.0.0.0"){$ipaddr="*";}
	if($ipaddr==null){$ipaddr="*";}
	
	$GLOBALS["HOSTPOT_WEB_INTERFACE"]=$ipaddr;
	
	$phpfpm=$unix->APACHE_LOCATE_PHP_FPM();
	$php=$unix->LOCATE_PHP5_BIN();
	$EnableArticaApachePHPFPM=$sock->GET_INFO("EnableArticaApachePHPFPM");
	if(!is_numeric($EnableArticaApachePHPFPM)){$EnableArticaApachePHPFPM=0;}
	if(!is_file($phpfpm)){$EnableArticaApachePHPFPM=0;}	
	
	$unix->chown_func($APACHE_SRC_ACCOUNT, $APACHE_SRC_GROUP,"/var/run/artica-apache");
	$apache_LOCATE_MIME_TYPES=apache_LOCATE_MIME_TYPES();
	
	if($EnableArticaApachePHPFPM==1){
		if(!is_file("$APACHE_MODULES_PATH/mod_fastcgi.so")){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} mod_fastcgi.so is required to use PHP5-FPM\n";}
			$EnableArticaApachePHPFPM=0;
		}
	}
	
	if($APACHE_SRC_ACCOUNT==null){
		$APACHE_SRC_ACCOUNT="www-data";
		$APACHE_SRC_GROUP="www-data";
		$unix->CreateUnixUser($APACHE_SRC_ACCOUNT,$APACHE_SRC_GROUP,"Apache username");
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Run as $APACHE_SRC_ACCOUNT:$APACHE_SRC_GROUP\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} HTTP Port: $ArticaSplashHotSpotPort SSL Port: $ArticaSplashHotSpotPortSSL\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} PHP-FPM: $EnablePHPFPM\n";}
	$f[]="LockFile /var/run/apache2/hotspot-artica-accept.lock";
	$f[]="PidFile /var/run/artica-apache/hotspot-apache.pid";
	$f[]="AcceptMutex flock";
	$f[]="SSLRandomSeed startup file:/dev/urandom  256";
	$f[]="SSLRandomSeed connect builtin";
	$f[]="SSLSessionCache        shmcb:/var/run/apache2/ssl_scache-hotspot(512000)";
	$f[]="SSLSessionCacheTimeout  300";
	$f[]="SSLSessionCacheTimeout  300";	
	$f[]="DocumentRoot /usr/share/artica-postfix";
	$f[]="DirectoryIndex hotspot.php index.html";
	$f[]="ErrorDocument 400 /hotspot.php";
	$f[]="ErrorDocument 401 /hotspot.php";
	$f[]="ErrorDocument 403 /hotspot.php";
	$f[]="ErrorDocument 404 /hotspot.php";
	$f[]="ErrorDocument 500 /hotspot.php";
	$f[]="NameVirtualHost $ipaddr:$ArticaSplashHotSpotPort";
	$f[]="NameVirtualHost $ipaddr:$ArticaSplashHotSpotPortSSL";
	$f[]="Listen $ipaddr:$ArticaSplashHotSpotPort";
	$f[]="Listen $ipaddr:$ArticaSplashHotSpotPortSSL";

$f[]="<VirtualHost $ipaddr:$ArticaSplashHotSpotPort>";
$f[]="\tServerName $ipaddr";
$f[]="\tDocumentRoot /usr/share/artica-postfix";
$f[]="</VirtualHost>";

$squid=new squidbee();
$ArticaSplashHotSpotCertificate=$sock->GET_INFO("ArticaSplashHotSpotCertificate");
$data=$squid->SaveCertificate($ArticaSplashHotSpotCertificate,false,true,false);

if(preg_match("#ssl_certificate\s+(.+?);\s+ssl_certificate_key\s+(.+?);#is", $data,$re)){
	$cert=$re[1];
	$key=$re[2];
}

$f[]="<VirtualHost $ipaddr:$ArticaSplashHotSpotPortSSL>";
$f[]="\tServerName $ipaddr";
$f[]="\tDocumentRoot /usr/share/artica-postfix";
	$f[]="\tSSLEngine on";
	$f[]="\tSSLCertificateFile \"$cert\"";
	$f[]="\tSSLCertificateKeyFile \"$key\"";
	$f[]="\tSSLVerifyClient none";
	$f[]="\tServerSignature Off";	

	
	
$f[]="</VirtualHost>";
	

	
	$f[]="<IfModule mpm_prefork_module>";
	$f[]="</IfModule>";
	$f[]="<IfModule mpm_worker_module>";
	$f[]="\tMinSpareThreads      25";
	$f[]="\tMaxSpareThreads      75 ";
	$f[]="\tThreadLimit          64";
	$f[]="\tThreadsPerChild      25";
	$f[]="</IfModule>";
	$f[]="<IfModule mpm_event_module>";
	$f[]="\tMinSpareThreads      25";
	$f[]="\tMaxSpareThreads      75 ";
	$f[]="\tThreadLimit          64";
	$f[]="\tThreadsPerChild      25";
	$f[]="</IfModule>";
	$f[]="AccessFileName .htaccess";
	$f[]="<Files ~ \"^\.ht\">";
	$f[]="\tOrder allow,deny";
	$f[]="\tDeny from all";
	$f[]="\tSatisfy all";
	$f[]="</Files>";
	$f[]="DefaultType text/plain";
	$f[]="HostnameLookups Off";
	$f[]="User				   $APACHE_SRC_ACCOUNT";
	$f[]="Group				   $APACHE_SRC_GROUP";
	$f[]="Timeout              300";
	$f[]="KeepAlive            Off";
	$f[]="KeepAliveTimeout     15";
	$f[]="StartServers         1";
	$f[]="MaxClients           50";
	$f[]="MinSpareServers      2";
	$f[]="MaxSpareServers      5";
	$f[]="MaxRequestsPerChild  5000";
	$f[]="MaxKeepAliveRequests 100";
	$f[]="ServerName ".$unix->hostname_g();
	

	
	$f[]="<IfModule mod_ssl.c>";

	$f[]="\tSSLRandomSeed connect builtin";
	$f[]="\tSSLRandomSeed connect file:/dev/urandom 512";
	$f[]="\tAddType application/x-x509-ca-cert .crt";
	$f[]="\tAddType application/x-pkcs7-crl    .crl";
	$f[]="\tSSLPassPhraseDialog  builtin";
	$f[]="\tSSLSessionCache        shmcb:/var/run/apache2/ssl_scache-articahtp(512000)";
	$f[]="\tSSLSessionCacheTimeout  300";
	$f[]="\tSSLSessionCacheTimeout  300";
	$f[]="\tSSLMutex  sem";
	$f[]="\tSSLCipherSuite HIGH:MEDIUM:!ADH";
	$f[]="\tSSLProtocol all -SSLv2";
	
	$f[]="</IfModule>";		
	$f[]="";


	
	

	
	$f[]="AddType application/x-httpd-php .php";
	$f[]="php_value error_log \"/var/log/lighttpd/apache-hotspot-php.log\"";
	
	$f[]="<IfModule mod_fcgid.c>";
	$f[]="	PHP_Fix_Pathinfo_Enable 1";
	$f[]="</IfModule>";
	
	$f[]="<IfModule mod_php5.c>";
	$f[]="    <FilesMatch \"\.ph(p3?|tml)$\">";
	$f[]="	SetHandler application/x-httpd-php";
	$f[]="    </FilesMatch>";
	$f[]="    <FilesMatch \"\.phps$\">";
	$f[]="	SetHandler application/x-httpd-php-source";
	$f[]="    </FilesMatch>";
	$f[]="    <IfModule mod_userdir.c>";
	$f[]="        <Directory /home/*/public_html>";
	$f[]="            php_admin_value engine Off";
	$f[]="        </Directory>";
	$f[]="    </IfModule>";
	$f[]="</IfModule>";	

	$f[]="<IfModule mod_mime.c>";
	$f[]="\tTypesConfig /etc/mime.types";
	$f[]="\tAddType application/x-compress .Z";
	$f[]="\tAddType application/x-gzip .gz .tgz";
	$f[]="\tAddType application/x-bzip2 .bz2";
	$f[]="\tAddType application/x-httpd-php .php .phtml";
	$f[]="\tAddType application/x-httpd-php-source .phps";
	$f[]="\tAddLanguage ca .ca";
	$f[]="\tAddLanguage cs .cz .cs";
	$f[]="\tAddLanguage da .dk";
	$f[]="\tAddLanguage de .de";
	$f[]="\tAddLanguage el .el";
	$f[]="\tAddLanguage en .en";
	$f[]="\tAddLanguage eo .eo";
	$f[]="\tRemoveType  es";
	$f[]="\tAddLanguage es .es";
	$f[]="\tAddLanguage et .et";
	$f[]="\tAddLanguage fr .fr";
	$f[]="\tAddLanguage he .he";
	$f[]="\tAddLanguage hr .hr";
	$f[]="\tAddLanguage it .it";
	$f[]="\tAddLanguage ja .ja";
	$f[]="\tAddLanguage ko .ko";
	$f[]="\tAddLanguage ltz .ltz";
	$f[]="\tAddLanguage nl .nl";
	$f[]="\tAddLanguage nn .nn";
	$f[]="\tAddLanguage no .no";
	$f[]="\tAddLanguage pl .po";
	$f[]="\tAddLanguage pt .pt";
	$f[]="\tAddLanguage pt-BR .pt-br";
	$f[]="\tAddLanguage ru .ru";
	$f[]="\tAddLanguage sv .sv";
	$f[]="\tRemoveType  tr";
	$f[]="\tAddLanguage tr .tr";
	$f[]="\tAddLanguage zh-CN .zh-cn";
	$f[]="\tAddLanguage zh-TW .zh-tw";
	$f[]="\tAddCharset us-ascii    .ascii .us-ascii";
	$f[]="\tAddCharset ISO-8859-1  .iso8859-1  .latin1";
	$f[]="\tAddCharset ISO-8859-2  .iso8859-2  .latin2 .cen";
	$f[]="\tAddCharset ISO-8859-3  .iso8859-3  .latin3";
	$f[]="\tAddCharset ISO-8859-4  .iso8859-4  .latin4";
	$f[]="\tAddCharset ISO-8859-5  .iso8859-5  .cyr .iso-ru";
	$f[]="\tAddCharset ISO-8859-6  .iso8859-6  .arb .arabic";
	$f[]="\tAddCharset ISO-8859-7  .iso8859-7  .grk .greek";
	$f[]="\tAddCharset ISO-8859-8  .iso8859-8  .heb .hebrew";
	$f[]="\tAddCharset ISO-8859-9  .iso8859-9  .latin5 .trk";
	$f[]="\tAddCharset ISO-8859-10  .iso8859-10  .latin6";
	$f[]="\tAddCharset ISO-8859-13  .iso8859-13";
	$f[]="\tAddCharset ISO-8859-14  .iso8859-14  .latin8";
	$f[]="\tAddCharset ISO-8859-15  .iso8859-15  .latin9";
	$f[]="\tAddCharset ISO-8859-16  .iso8859-16  .latin10";
	$f[]="\tAddCharset ISO-2022-JP .iso2022-jp .jis";
	$f[]="\tAddCharset ISO-2022-KR .iso2022-kr .kis";
	$f[]="\tAddCharset ISO-2022-CN .iso2022-cn .cis";
	$f[]="\tAddCharset Big5        .Big5       .big5 .b5";
	$f[]="\tAddCharset cn-Big5     .cn-big5";
	$f[]="\t# For russian, more than one charset is used (depends on client, mostly):";
	$f[]="\tAddCharset WINDOWS-1251 .cp-1251   .win-1251";
	$f[]="\tAddCharset CP866       .cp866";
	$f[]="\tAddCharset KOI8      .koi8";
	$f[]="\tAddCharset KOI8-E      .koi8-e";
	$f[]="\tAddCharset KOI8-r      .koi8-r .koi8-ru";
	$f[]="\tAddCharset KOI8-U      .koi8-u";
	$f[]="\tAddCharset KOI8-ru     .koi8-uk .ua";
	$f[]="\tAddCharset ISO-10646-UCS-2 .ucs2";
	$f[]="\tAddCharset ISO-10646-UCS-4 .ucs4";
	$f[]="\tAddCharset UTF-7       .utf7";
	$f[]="\tAddCharset UTF-8       .utf8";
	$f[]="\tAddCharset UTF-16      .utf16";
	$f[]="\tAddCharset UTF-16BE    .utf16be";
	$f[]="\tAddCharset UTF-16LE    .utf16le";
	$f[]="\tAddCharset UTF-32      .utf32";
	$f[]="\tAddCharset UTF-32BE    .utf32be";
	$f[]="\tAddCharset UTF-32LE    .utf32le";
	$f[]="\tAddCharset euc-cn      .euc-cn";
	$f[]="\tAddCharset euc-gb      .euc-gb";
	$f[]="\tAddCharset euc-jp      .euc-jp";
	$f[]="\tAddCharset euc-kr      .euc-kr";
	$f[]="\tAddCharset EUC-TW      .euc-tw";
	$f[]="\tAddCharset gb2312      .gb2312 .gb";
	$f[]="\tAddCharset iso-10646-ucs-2 .ucs-2 .iso-10646-ucs-2";
	$f[]="\tAddCharset iso-10646-ucs-4 .ucs-4 .iso-10646-ucs-4";
	$f[]="\tAddCharset shift_jis   .shift_jis .sjis";
	$f[]="\tAddType text/html .shtml";
	$f[]="\tAddOutputFilter INCLUDES .shtml";
	$f[]="</IfModule>";

	
	
	$f[]="<Directory \"/usr/share/artica-postfix\">";
	$f[]="\tDirectorySlash On";
	$f[]="\tDirectoryIndex hostpot.php";
	$f[]="\t\t<Files \"hostpot.php\">";
	$f[]="\t\t\tOrder allow,deny";
	$f[]="\t\t\tallow from all";
	$f[]="\t\t</Files>";
	
	$f[]="\tErrorDocument 400 /hotspot.php";
	$f[]="\tErrorDocument 401 /hotspot.php";
	$f[]="\tErrorDocument 403 /hotspot.php";
	$f[]="\tErrorDocument 404 /hotspot.php";
	$f[]="\tErrorDocument 500 /hotspot.php";
	$f[]="\tOptions -Indexes";
	$f[]=ParseArticaDirectory();
	
	$f[]="\tSSLOptions +StdEnvVars";
	$f[]="\tAllowOverride All";
	$f[]="\tOrder allow,deny";
	$f[]="\tAllow from all";
	
	$f[]="\tRewriteEngine On";
	$f[]="\tRewriteCond %{REQUEST_URI} /img/(.*)";
	$f[]="\tRewriteRule ^ /hotspot.php?imgload=%1 [L]";
	
	$f[]="\tRewriteCond %{REQUEST_URI} /ressources/templates/endusers/(.*)";
	$f[]="\tRewriteRule ^ /hotspot.php?endusers=%1 [L]";
	
	
	
	
	$f[]="\tRewriteCond %{REQUEST_URI} !/hotspot.php.*";
	$f[]="\tRewriteRule ^ /hotspot.php?uri=%{REQUEST_URI} [R=301]";
	
	$f[]="</Directory>";	
	
	if($EnableArticaApachePHPFPM==1){	
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Activate PHP5-FPM\n";}
		shell_exec("$php /usr/share/artica-postfix/exec.initslapd.php --phppfm");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Restarting PHP5-FPM\n";}
		shell_exec("/etc/init.d/php5-fpm restart");
		$f[]="\tAlias /php5.fastcgi /var/run/artica-apache/php5.fastcgi";
		$f[]="\tAddHandler php-script .php";
		$f[]="\tFastCGIExternalServer /var/run/artica-apache/php5.fastcgi -socket /var/run/php-fpm.sock -idle-timeout 610";
		$f[]="\tAction php-script /php5.fastcgi virtual";
		$f[]="\t<Directory /var/run/artica-apache>";
		$f[]="\t\t<Files php5.fastcgi>";
		$f[]="\t\tOrder deny,allow";
		$f[]="\t\tAllow from all";
		$f[]="\t\t</Files>";
		$f[]="\t</Directory>";
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} PHP5-FPM is disabled\n";}
	}	
	
	
	$f[]="Loglevel debug";
	$f[]="ErrorLog /var/log/lighttpd/apache-hotspot-error.log";
	$f[]="LogFormat \"%h %l %u %t \\\"%r\\\" %<s %b\" common";
	$f[]="CustomLog /var/log/lighttpd/apache-hotspot-access.log common";
	
	if($EnableArticaApachePHPFPM==0){$array["php5_module"]="libphp5.so";}
	
	
	$array["actions_module"]="mod_actions.so";
	$array["expires_module"]="mod_expires.so";
	$array["rewrite_module"]="mod_rewrite.so";
	$array["dir_module"]="mod_dir.so";
	$array["mime_module"]="mod_mime.so";
	$array["alias_module"]="mod_alias.so";
	$array["auth_basic_module"]="mod_auth_basic.so";
	$array["authz_host_module"]="mod_authz_host.so";
	$array["autoindex_module"]="mod_autoindex.so";
	$array["negotiation_module"]="mod_negotiation.so";
	$array["ssl_module"]="mod_ssl.so";
	$array["headers_module"]="mod_headers.so";
	$array["ldap_module"]="mod_ldap.so";
	
	if($EnableArticaApachePHPFPM==1){$array["fastcgi_module"]="mod_fastcgi.so";}
	
	if(is_dir("/etc/apache2")){
		if(!is_file("/etc/apache2/mime.types")){
			if($apache_LOCATE_MIME_TYPES<>"/etc/apache2/mime.types"){
				@copy($apache_LOCATE_MIME_TYPES, "/etc/apache2/mime.types");
			}
		}
		
	}
	
	
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Mime types path.......: $apache_LOCATE_MIME_TYPES\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Modules path..........: $APACHE_MODULES_PATH\n";}
	
	while (list ($module, $lib) = each ($array) ){
		
		if(is_file("$APACHE_MODULES_PATH/$lib")){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} include module \"$module\"\n";}
			$f[]="LoadModule $module $APACHE_MODULES_PATH/$lib";
		}else{
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} skip module \"$module\"\n";}
		}
	
	}
	
	
	@file_put_contents("/etc/artica-postfix/hotspot-httpd.conf", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} /etc/artica-postfix/hotspot-httpd.conf done\n";}
	
	
}

function FrmToSyslog($text){

	$LOG_SEV=LOG_INFO;
	if(function_exists("openlog")){openlog(basename(__FILE__), LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
	if(function_exists("closelog")){closelog();}
}
function DetectError($results,$type){
	while (list ($a, $b) = each ($results) ){
		if($GLOBALS["VERBOSE"]){echo "$a \"$b\"\n";}
		if(preg_match("#HTTP.+?200 OK#", $b)){
			if($GLOBALS['VERBOSE']){echo "$type: 200 OK Nothing to do...\n";}
			return false;
		}
	
		IF(preg_match("#HTTP.*?502 Bad Gateway#", $b)){
			$GLOBALS["DetectError"]="$b";
			if($GLOBALS['VERBOSE']){echo "$b detected\n";}
			system_admin_events("$type: $b detected ",__FUNCTION__,__FILE__,__LINE__);
			return true;
		}
			
		IF(preg_match("#HTTP.*?500.*?Error#", $b)){
			$GLOBALS["DetectError"]="$b";
			if($GLOBALS['VERBOSE']){echo "$b detected\n";}
			system_admin_events("$type: $b detected",__FUNCTION__,__FILE__,__LINE__);
			return true;
		}
		
		IF(preg_match("#HTTP.*?500.*?Internal#", $b)){
			$GLOBALS["DetectError"]="$b";
			if($GLOBALS['VERBOSE']){echo "$b detected\n";}
			system_admin_events("$type: $b detected",__FUNCTION__,__FILE__,__LINE__);
			return true;
		}
		
		IF(preg_match("#HTTP.*?503.*?Service Not Available#i", $b)){
			$GLOBALS["DetectError"]="$b";
			if($GLOBALS['VERBOSE']){echo "$b detected\n";}
			system_admin_events("$type: $b detected",__FUNCTION__,__FILE__,__LINE__);
			return true;		
		}		
		
			
	}	
	
	
}

function ParseArticaDirectory(){
$unix=new unix();
	foreach (glob("/usr/share/artica-postfix/*") as $filename) {
		$file=basename($filename);
		if(preg_match("#js#", $file)){continue;}
		if(preg_match("#css#", $file)){continue;}
		if(preg_match("#Inotify\.php#", $file)){continue;}
		$array[$file]=$file;
	}
	$dirs=$unix->dirdir("/usr/share/artica-postfix");
	
	while (list ($num, $file) = each ($dirs) ){
		$dir=basename($file);
		$array[$dir]=$dir;

		
	}
		
	
	unset($array["hotspot.php"]);
	while (list ($num, $file) = each ($array) ){
		$f[]="\t\t<Files \"$num\">";
		$f[]="\t\t\tOrder allow,deny";
		$f[]="\t\t\tDeny from all";
		$f[]="\t\t</Files>";
	}
	

	
	
	return @implode("\n", $f)."\n".@implode("\n", $d);
}

