<?php
if(preg_match("#--verbose#",implode(" ",$argv))){
	echo "VERBOSED\n";
	$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;
	$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.freeweb.inc');
$GLOBALS["SERVICE_NAME"]="zarafa-web Engine";

if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}

echo "php ".__FILE__." --stop ( stop the apache-server)\n";
echo "php ".__FILE__." --start ( start the apache-server)\n";
echo "php ".__FILE__." --restart ( restart the apache-server)\n";

function PID_NUM(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/zarafa-web/httpd.pid");
	if($unix->process_exists($pid)){return $pid;}
	$apachebin=$unix->LOCATE_APACHE_BIN_PATH();
	return $unix->PIDOF_PATTERN("$apachebin.*?/etc/zarafa/httpd.conf");
	
}

//##############################################################################
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
	stop(true);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Reconfigure...\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Path: /etc/zarafa/httpd.conf...\n";}
	build();
	start(true);
}
//##############################################################################

function start($aspid=false){
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	if(!$aspid){
		$pid=$unix->get_pid_from_file($pidfile);
		$sock=new sockets();
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: zarafa-web Engine Artica Task Already running PID $pid since {$time}mn\n";}
			return;
		}
	}
	@file_put_contents($pidfile, getmypid());
	$serverbin=$unix->find_program("zarafa-server");


	if(!is_file($serverbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: zarafa-web Engine is not installed...\n";}
		return;
	}
	
	$serverbin=$unix->APACHE_BIN_PATH();
	$sock=new sockets();
	$ZarafaApacheEnable=$sock->GET_INFO("ZarafaApacheEnable");
	if(!is_numeric($ZarafaApacheEnable)){$ZarafaApacheEnable=1;}
	$ZarafaApachePort=$sock->GET_INFO("ZarafaApachePort");
	if(!is_numeric($ZarafaApachePort)){$ZarafaApachePort=9010;}
		
	if($ZarafaApacheEnable==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: zarafa-web Engine is disabled ( see ZarafaApacheEnable )...\n";}
		return;
	}	
	
	

	$pid=PID_NUM();

	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: zarafa-web Engine already running pid $pid since {$time}mn\n";}
		return;
	}




	$f[]=$serverbin;
	$f[]="-f /etc/zarafa/httpd.conf";


	$cmdline=@implode(" ", $f);

	if($GLOBALS["VERBOSE"]){echo $cmdline."\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Starting {$GLOBALS["SERVICE_NAME"]}\n";}
	shell_exec("$cmdline 2>&1");
	sleep(1);

	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if($unix->process_exists($pid)){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} started pid .$pid..\n";}break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} wait $i/5\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} failed to start\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $cmdline\n";
		}else{
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success PID $pid\n";}

		}
	}
}

function reload(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Reloading.....: [INIT]: Already task running PID $pid since {$time}mn\n";}
		return;
	}
	
	$pid=PID_NUM();
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Reloading.....: [INIT]: {$GLOBALS["SERVICE_NAME"]} stopped...\n";}
		$php5=$unix->LOCATE_PHP5_BIN();
		shell_exec("$php5 ".__FILE__." --start");
		return;
	}	
	if($GLOBALS["OUTPUT"]){echo "Reloading.....: [INIT]: {$GLOBALS["SERVICE_NAME"]} reconfigure...\n";}
	build();	
	if($GLOBALS["OUTPUT"]){echo "Reloading.....: [INIT]: {$GLOBALS["SERVICE_NAME"]} reloading PID $pid...\n";}
	$apache2ctl=$unix->LOCATE_APACHE_CTL();
	shell_exec("$apache2ctl -f /etc/zarafa/httpd.conf -k restart >/dev/null 2>&1");
	
}


function stop($aspid=false){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if(!$aspid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already task running PID $pid since {$time}mn\n";}
			return;
		}
	}
	
	@file_put_contents($pidfile, getmypid());
	$pid=PID_NUM();

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} already stopped...\n";}
		return;
	}

	
	$apache2ctl=$unix->LOCATE_APACHE_CTL();
	$time=$unix->PROCCESS_TIME_MIN($pid);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} with a ttl of {$time}mn\n";}
	$kill=$unix->find_program("kill");

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} killing smoothly PID $pid ( $apache2ctl )...\n";}
	$results=array();
	exec("$apache2ctl -f /etc/zarafa/httpd.conf -k stop 2>&1",$results);
		while (list ($index, $dir) = each ($results) ){
			if(preg_match("#[0-9]+.*?not running#",$dir)){
				if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} killing \"forced\" PID $pid...\n";}
				unix_system_kill($pid);
				break;
			}
		}
	
	sleep(1);
	

	for($i=1;$i<10;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Successfully stopped ...\n";}
			break;
		}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} wait pid $pid $i/60\n";}
		shell_exec("$apache2ctl -f /etc/zarafa/httpd.conf -k kill >/dev/null 2>&1");
		sleep(1);
	}
	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} killing \"forced\" PID $pid...\n";}
		unix_system_kill($pid);
		sleep(1);
		for($i=1;$i<10;$i++){
			$pid=PID_NUM();
			if(!$unix->process_exists($pid)){
				if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Successfully stopped ...\n";}
				break;
			}
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} wait $i/60\n";}
			sleep(1);
		}
	}


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} daemon success...\n";}
		return;
	}
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} daemon failed...\n";}
}


function build(){
	$unix=new unix();
	$sock=new sockets();
	$ZarafaApachePort=$sock->GET_INFO("ZarafaApachePort");
	$ZarafaApacheSSL=$sock->GET_INFO("ZarafaApacheSSL");
	$LighttpdArticaDisableSSLv2=$sock->GET_INFO("LighttpdArticaDisableSSLv2");
	$ZarafaWebNTLM=$sock->GET_INFO("ZarafaWebNTLM");
	$ZarafaApacheServerName=$sock->GET_INFO("ZarafaApacheServerName");
	
	if(!is_numeric($ZarafaWebNTLM)){$ZarafaWebNTLM=0;}
	if(!is_numeric($LighttpdArticaDisableSSLv2)){$LighttpdArticaDisableSSLv2=0;}
	if(!is_numeric($ZarafaApacheSSL)){$ZarafaApacheSSL=0;}
	if(!is_numeric($ZarafaApachePort)){$ZarafaApachePort=9010;}
	
	$ZarafaApachePHPFPMEnable=$sock->GET_INFO("ZarafaApachePHPFPMEnable");
	if(!is_numeric($ZarafaApachePHPFPMEnable)){$ZarafaApachePHPFPMEnable=0;}
	
	
	
	if($ZarafaApacheServerName==null){$ZarafaApacheServerName=$unix->hostname_g();}
	

	
	
	if(!is_dir('/usr/share/php/mapi')){
		if(is_dir('/usr/local/share/php/mapi')){
			@mkdir("/usr/share/php",0755,true);
			shell_exec('/bin/ln -s /usr/local/share/php/mapi /usr/share/php/mapi');
		}
	}
	
	
	
	$username=$unix->APACHE_SRC_ACCOUNT();
	$group=$unix->APACHE_SRC_GROUP();
	
	
	@mkdir('/var/run/zarafa-web',0755,true);
	@mkdir('/var/log/apache-zarafa',0755,true);
	@mkdir('/var/lib/zarafa-webaccess/tmp',0755,true);
	
	$unix->chown_func($username, $group,"/var/run/zarafa-web");
	$unix->chown_func($username, $group,"/var/log/apache-zarafa");
	$unix->chown_func($username, $group,"/var/lib/zarafa-webaccess");
	$unix->chmod_func(0777, "/var/lib/zarafa-webaccess/tmp");
	$unix->chown_func($username, $group,"/usr/share/zarafa-webaccess/plugins/*");
	
	


if($ZarafaApacheSSL==1){
	if(!is_file("/etc/ssl/certs/zarafa/apache.crt.nopass.cert")){shell_exec("/usr/share/artica-postfix/bin/artica-install --zarafa-apache-certificates");}
	$f[]="SSLEngine on";
	
	$f[]="SSLCertificateFile /etc/ssl/certs/zarafa/apache.crt.nopass.cert";
	$f[]="SSLCertificateKeyFile /etc/ssl/certs/zarafa/apache-ca.key.nopass.key";
	if($LighttpdArticaDisableSSLv2==1){
		$f[]="SSLProtocol -ALL +SSLv3 +TLSv1";
		$f[]="SSLCipherSuite ALL:!aNULL:!ADH:!eNULL:!LOW:!EXP:RC4+RSA:+HIGH:+MEDIUM";
	}

	$f[]="SSLRandomSeed connect builtin";
	$f[]="SSLRandomSeed startup file:/dev/urandom  256";
	$f[]="SSLRandomSeed connect file:/dev/urandom 256";
	$f[]="AddType application/x-x509-ca-cert .crt";
	$f[]="AddType application/x-pkcs7-crl    .crl";
	$f[]="SSLPassPhraseDialog  builtin";
	$f[]="SSLSessionCache        shmcb:/var/run/apache2/ssl_scache-zarafa(512000)";
	$f[]="SSLSessionCacheTimeout  300";
	$f[]="SSLVerifyClient none";
	$f[]="ServerSignature Off";
	
	
}	
$SET_MODULES=SET_MODULES();	


$FreeWebPerformances=unserialize(base64_decode($sock->GET_INFO("ZarafaApachePerformances")));
if(!is_numeric($FreeWebPerformances["Timeout"])){$FreeWebPerformances["Timeout"]=300;}
if(!is_numeric($FreeWebPerformances["KeepAlive"])){$FreeWebPerformances["KeepAlive"]=0;}
if(!is_numeric($FreeWebPerformances["MaxKeepAliveRequests"])){$FreeWebPerformances["MaxKeepAliveRequests"]=100;}
if(!is_numeric($FreeWebPerformances["KeepAliveTimeout"])){$FreeWebPerformances["KeepAliveTimeout"]=15;}
if(!is_numeric($FreeWebPerformances["MinSpareServers"])){$FreeWebPerformances["MinSpareServers"]=5;}
if(!is_numeric($FreeWebPerformances["MaxSpareServers"])){$FreeWebPerformances["MaxSpareServers"]=10;}
if(!is_numeric($FreeWebPerformances["StartServers"])){$FreeWebPerformances["StartServers"]=5;}
if(!is_numeric($FreeWebPerformances["MaxClients"])){$FreeWebPerformances["MaxClients"]=50;}
if(!is_numeric($FreeWebPerformances["MaxRequestsPerChild"])){$FreeWebPerformances["MaxRequestsPerChild"]=10000;}
$f[]="Timeout              {$FreeWebPerformances["Timeout"]}";
$f[]="KeepAlive            {$FreeWebPerformances["KeepAlive"]}";
$f[]="KeepAliveTimeout     {$FreeWebPerformances["KeepAliveTimeout"]}";
$f[]="StartServers         {$FreeWebPerformances["StartServers"]}";
$f[]="MaxClients           {$FreeWebPerformances["MaxClients"]}";
$f[]="MinSpareServers      {$FreeWebPerformances["MinSpareServers"]}";
$f[]="MaxSpareServers      {$FreeWebPerformances["MaxSpareServers"]}";
$f[]="MaxRequestsPerChild  {$FreeWebPerformances["MaxRequestsPerChild"]}";
$f[]="MaxKeepAliveRequests {$FreeWebPerformances["MaxKeepAliveRequests"]}";
$f[]="ServerLimit		   {$FreeWebPerformances["MaxClients"]}";
$f[]="AcceptMutex 		  flock";
$ZarafaApacheWebMailType=$sock->GET_INFO("ZarafaApacheWebMailType");
//$ZarafaApacheWebMailTypeA["APP_ZARAFA"]="{APP_ZARAFA}";
//$ZarafaApacheWebMailTypeA["APP_ZARAFA_WEBAPP"]="{APP_ZARAFA_WEBAPP}";
if($ZarafaApacheWebMailType==null){$ZarafaApacheWebMailType="APP_ZARAFA";}

$f[]=$SET_MODULES;
$f[]="<IfModule !mpm_netware_module>";
$f[]="          <IfModule !mpm_winnt_module>";
$f[]="             User $username";
$f[]="             Group $group";
$f[]="          </IfModule>";
$f[]="</IfModule>";
$f[]="ServerAdmin you@example.com";
$f[]="ServerName $ZarafaApacheServerName";

if($ZarafaApacheWebMailType=="APP_ZARAFA_WEBAPP"){
	if(!is_dir("/usr/share/zarafa-webapp")){$ZarafaApacheWebMailType="APP_ZARAFA";}
}



if($ZarafaApacheWebMailType=="APP_ZARAFA"){
	$DocumentRoot="/usr/share/zarafa-webaccess";
}

if($ZarafaApacheWebMailType=="APP_ZARAFA_WEBAPP"){
	$free=new freeweb();
	$free->InstallZarafaConfigWebAPP("/usr/share/zarafa-webapp");
	$DocumentRoot="/usr/share/zarafa-webapp";
}


if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} WebMail \"$ZarafaApacheWebMailType\"\n";}

$f[]="ServerRoot \"$DocumentRoot\"";
$f[]="Listen $ZarafaApachePort";
$f[]="User $username";
$f[]="Group $group";
$f[]="PidFile /var/run/zarafa-web/httpd.pid";

$f[]="DocumentRoot \"$DocumentRoot\"";
$f[]="<Directory $DocumentRoot/>";
if($ZarafaWebNTLM==1){
$ldap=new clladp();	
	$f[]="    AuthName \"Zarafa logon..\"";
	$f[]="    AuthType Basic";
	$f[]="    AuthLDAPURL ldap://$ldap->ldap_host:$ldap->ldap_port/dc=organizations,$ldap->suffix?uid";
	$f[]="    AuthLDAPBindDN cn=$ldap->ldap_admin,$ldap->suffix";
	$f[]="    AuthLDAPBindPassword $ldap->ldap_password";
	$f[]="    AuthLDAPGroupAttribute memberUid";
	$f[]="    AuthBasicProvider ldap";
	$f[]="    AuthzLDAPAuthoritative off";
	$f[]="    require valid-user";
}
if($ZarafaApachePHPFPMEnable==0){
	$f[]="    php_value magic_quotes_gpc off";
	$f[]="    php_flag register_globals off";
	$f[]="    php_flag magic_quotes_gpc off";
	$f[]="    php_flag magic_quotes_runtime off";
	$f[]="    php_value post_max_size 31M";
	$f[]="    php_value include_path  \".:/usr/share/php:/usr/share/php5:/usr/local/share/php\"";
	$f[]="    php_value upload_max_filesize 30M";
	$f[]="    php_flag short_open_tag on";
	$f[]="    php_flag log_errors on";
	$f[]="    php_flag short_open_tag off";
	$f[]="    php_flag safe_mode 0";
	$f[]="    php_flag log_errors on";
	$f[]="    php_value  error_log  \"/var/log/apache-zarafa/php.log\"";
}
$f[]="    DirectoryIndex index.php";
$f[]="    Options -Indexes +FollowSymLinks";
$f[]="    AllowOverride Options";
$f[]="    Order allow,deny";
$f[]="    Allow from all";
$f[]="</Directory>";

if($ZarafaApachePHPFPMEnable==1){
	$php=$unix->LOCATE_PHP5_BIN();
	if(!$unix->is_socket("/var/run/php-fpm-zarafa.sock")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: /var/run/php-fpm-zarafa.sock no such socket\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Activate PHP5-FPM\n";}
		shell_exec("$php /usr/share/artica-postfix/exec.initslapd.php --phppfm");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Restarting PHP5-FPM\n";}
		shell_exec("/etc/init.d/php5-fpm restart");
	}

	$f[]="\tAlias /php5.fastcgi /var/run/artica-apache/php5.fastcgi";
	$f[]="\tAddHandler php-script .php";
	$f[]="\tFastCGIExternalServer /var/run/artica-apache/php5.fastcgi -socket /var/run/php-fpm-zarafa.sock -idle-timeout 610";
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

$f[]="<IfModule dir_module>";
$f[]="    DirectoryIndex index.php";
$f[]="</IfModule>";
$f[]="";
$f[]="";
$f[]="<FilesMatch \"^\.ht\">";
$f[]="    Order allow,deny";
$f[]="    Deny from all";
$f[]="    Satisfy All";
$f[]="</FilesMatch>";
$f[]="<IfModule mod_php5.c>";
$f[]="    <FilesMatch \"\.ph(p3?|tml)$\">";
$f[]="	SetHandler application/x-httpd-php";
$f[]="    </FilesMatch>";
$f[]="    <FilesMatch \"\.phps$\">";
$f[]="	SetHandler application/x-httpd-php-source";
$f[]="    </FilesMatch>";
$f[]="    # To re-enable php in user directories comment the following lines";
$f[]="    # (from <IfModule ...> to </IfModule>.) Do NOT set it to On as it";
$f[]="    # prevents .htaccess files from disabling it.";
$f[]="    <IfModule mod_userdir.c>";
$f[]="        <Directory /home/*/public_html>";
$f[]="            php_admin_value engine Off";
$f[]="        </Directory>";
$f[]="    </IfModule>";
$f[]="</IfModule>";
$f[]="";
$f[]="";
$f[]="ErrorLog \"/var/log/apache-zarafa/error.log\"";
$f[]="LogLevel warn";
$f[]="";
$f[]="<IfModule log_config_module>";
$f[]="    LogFormat \"%h %l %u %t \\\"%r\\\" %>s %b \\\"%{Referer}i\\\" \\\"%{User-Agent}i\\\" %V\\\" combinedv";
$f[]="    LogFormat \"%h %l %u %t \\\"%r\\\" %>s %b\" common";
$f[]="";
$f[]="    <IfModule logio_module>";
$f[]="      LogFormat \"%h %l %u %t \\\"%r\\\" %>s %b \\\"%{Referer}i\\\" \\\"%{User-Agent}i\\\" %I %O\" combinedio";
$f[]="    </IfModule>";
$f[]="";

if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} logs access: /var/log/apache-zarafa/access.log\n";}
if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} logs error : /var/log/apache-zarafa/error.log\n";}

$f[]="    CustomLog \"/var/log/apache-zarafa/access.log\" combinedv";
$f[]="</IfModule>";
$f[]="";
$f[]="<IfModule alias_module>";
$f[]="    ScriptAlias /cgi-bin/ \"/usr/local/apache-groupware/data/cgi-bin/\"";
$f[]="    Alias /images /usr/share/obm2/resources";
$f[]="";
$f[]="</IfModule>";
$f[]="";
$f[]="<IfModule cgid_module>";
$f[]="";
$f[]="</IfModule>";
$f[]="";
$f[]="";
$f[]="<Directory \"/usr/local/apache-groupware/data/cgi-bin\">";
$f[]="    AllowOverride None";
$f[]="    Options None";
$f[]="    Order allow,deny";
$f[]="    Allow from all";
$f[]="</Directory>";
$f[]="";
$f[]="";
$f[]="DefaultType text/plain";
$f[]="";
$f[]="<IfModule mime_module>";
$f[]="   ";
$f[]="    TypesConfig /etc/mime.types";
$f[]="    #AddType application/x-gzip .tgz";
$f[]="    AddType application/x-compress .Z";
$f[]="    AddType application/x-gzip .gz .tgz";
$f[]="    AddType application/x-httpd-php .php .phtml";
$f[]="    #AddHandler cgi-script .cgi";
$f[]="    #AddHandler type-map var";
$f[]="    #AddType text/html .shtml";
$f[]="    #AddOutputFilter INCLUDES .shtml";
$f[]="</IfModule>";

@file_put_contents('/etc/zarafa/httpd.conf',@implode("\n", $f)."\n");

@mkdir("/var/run/apache2",0755,true);
@mkdir("/var/run/artica-apache",0755,true);


}

function SET_MODULES(){

	
	$unix=new unix();
	$APACHE_MODULES_PATH=$unix->APACHE_MODULES_PATH();
	$lnpath=$unix->find_program('ln');
	if(is_dir('/usr/lib/apache2')){
		if(!is_file('/usr/lib/apache2/mod_ssl.so')){
			if(is_file('/usr/lib/apache2-prefork/mod_ssl.so')){shell_exec("$lnpath -s /usr/lib/apache2-prefork/mod_ssl.so /usr/lib/apache2/mod_ssl.so");}
		}
	}


	foreach (glob("$APACHE_MODULES_PATH/*.so") as $filename) {
		$filebase=basename($filename);
		$xmod=APACHE_ADD_MODULE($filebase);
		if($xmod==null){continue;}
		$f[]=$xmod;
		
	}
	
	
	if(is_file('/usr/lib/apache2/modules/mod_authz_host.so')){
		$f[]="LoadModule authz_host_module /usr/lib/apache2/modules/mod_authz_host.so";
	}	
	
	if(is_file('/usr/lib/apache2/modules/mod_dir.so')){
		$f[]="LoadModule dir_module /usr/lib/apache2/modules/mod_dir.so";
	}
	
	
	if(is_file('/usr/lib/apache2/modules/libphp5.so')){
		$f[]="LoadModule php5_module /usr/lib/apache2/modules/libphp5.so";
	}

	if(is_file('/usr/lib/apache-extramodules/mod_php5.so')){
		$f[]="LoadModule php5_module\t/usr/lib/apache-extramodules/mod_php5.so";
	}


	return @implode("\n", $f);
}

//##############################################################################
function APACHE_ADD_MODULE($moduleso_file){
	$unix=new unix();
	if(preg_match("#^mod_proxy#", $moduleso_file)){return;}
	if($moduleso_file=="mod_proxy_balancer.so"){return;}
	if($moduleso_file=="mod_unique_id.so"){return;}
	
	
	if($moduleso_file=="mod_proxy_ftp.so"){return;}
	if($moduleso_file=="mod_proxy_html.so"){return;}
	if($moduleso_file=="mod_rpaf-2.0.so"){return;}
	$APACHE_MODULES_PATH=$unix->APACHE_MODULES_PATH();
	
	
	
	if($moduleso_file=='mod_perl.so' ){
		if(!AuthorizedModule($moduleso_file)){ if($GLOBALS["VERBOSE"]){echo "$moduleso_file: blacklisted\n";} return;}
		if($GLOBALS["VERBOSE"]){echo "$moduleso_file: perl_module OK\n";}
		return 'LoadModule perl_module'."\t$APACHE_MODULES_PATH/$moduleso_file";
	}
	
	if($moduleso_file=='mod_log_config.so' ){
		if(!AuthorizedModule($moduleso_file)){ if($GLOBALS["VERBOSE"]){echo "$moduleso_file: blacklisted\n";} return;}
		if($GLOBALS["VERBOSE"]){echo "$moduleso_file: log_config_module OK\n";}
		return 'LoadModule log_config_module'."\t$APACHE_MODULES_PATH/$moduleso_file";
	}
	
	if($moduleso_file=='mod_vhost_ldap.so' ){
		
		if(!AuthorizedModule($moduleso_file)){ if($GLOBALS["VERBOSE"]){echo "$moduleso_file: blacklisted\n";} return;}
		if($GLOBALS["VERBOSE"]){echo "$moduleso_file: vhost_ldap_module OK\n";}
		return 'LoadModule vhost_ldap_module'."\t$APACHE_MODULES_PATH/$moduleso_file";
	}
	
	
	if($moduleso_file=='mod_ldap.so' ){
		if(!AuthorizedModule($moduleso_file)){ if($GLOBALS["VERBOSE"]){echo "$moduleso_file: blacklisted\n";} return;}
		if($GLOBALS["VERBOSE"]){echo "$moduleso_file: ldap_module OK\n";}
		return 'LoadModule ldap_module'."\t$APACHE_MODULES_PATH/$moduleso_file";
	}
	
	if($moduleso_file=='mod_rewrite.so' ){
		if(!AuthorizedModule($moduleso_file)){ if($GLOBALS["VERBOSE"]){echo "$moduleso_file: blacklisted\n";} return;}
		if($GLOBALS["VERBOSE"]){echo "$moduleso_file: mod_rewrite OK\n";}
		return 'LoadModule rewrite_module'."\t$APACHE_MODULES_PATH/$moduleso_file";
	}
	
	if($moduleso_file=='mod_dav.so' ){
		if(!AuthorizedModule($moduleso_file)){ if($GLOBALS["VERBOSE"]){echo "$moduleso_file: blacklisted\n";} return;}
		if($GLOBALS["VERBOSE"]){echo "$moduleso_file: dav_module OK\n";}
		return 'LoadModule dav_module'."\t$APACHE_MODULES_PATH/$moduleso_file";
	}
	
	
	if($moduleso_file=='mod_suexec.so' ){
		if(!AuthorizedModule($moduleso_file)){ if($GLOBALS["VERBOSE"]){echo "$moduleso_file: blacklisted\n";} return;}
		if($GLOBALS["VERBOSE"]){echo "$moduleso_file: suexec_module OK\n";}
		return 'LoadModule suexec_module'."\t$APACHE_MODULES_PATH/$moduleso_file";
	}
	
	if(!AuthorizedModule($moduleso_file)){
		if($GLOBALS["VERBOSE"]){echo "$moduleso_file: blacklisted\n";}
		return null;}
	
	
	if($moduleso_file=='mod_php5.so' ){
		if(!AuthorizedModule($moduleso_file)){ if($GLOBALS["VERBOSE"]){echo "$moduleso_file: blacklisted\n";} return;}
		return 'LoadModule php5_module'."\t$APACHE_MODULES_PATH/$moduleso_file";
	}



	
	if($moduleso_file=='mod_proxy_connect.so'){if($GLOBALS["VERBOSE"]){echo "$moduleso_file: blacklisted\n";} return;}
	if($moduleso_file=='mod_dav_lock.so'){if($GLOBALS["VERBOSE"]){echo "$moduleso_file: blacklisted\n";} return;}
	if($moduleso_file=='mod_mem_cache.so'){if($GLOBALS["VERBOSE"]){echo "$moduleso_file: blacklisted\n";} return;}
	if($moduleso_file=='mod_cgid.so'){if($GLOBALS["VERBOSE"]){echo "$moduleso_file: blacklisted\n";} return;}
	if($moduleso_file=='mod_proxy.so'){if($GLOBALS["VERBOSE"]){echo "$moduleso_file: blacklisted\n";} return;}
	if($moduleso_file=='mod_proxy_http.so'){if($GLOBALS["VERBOSE"]){echo "$moduleso_file: blacklisted\n";} return;}
	if($moduleso_file=='mod_proxy_ajp.so'){if($GLOBALS["VERBOSE"]){echo "$moduleso_file: blacklisted\n";} return;}
	$module_name=null;
	$moduleso_file_pattern=str_replace('.','\.',$moduleso_file);
	if(preg_match("#^mod_(.+?)\.so#", $moduleso_file,$re)){
		$module_name=$re[1].'_module';
	}else{
		if(preg_match("#^(.+?)\.so#", $moduleso_file,$re)){
			$module_name=$re[1].'_module';
		}
	}
	if($moduleso_file=='libphp5.so'){$module_name='php5_module';}
	if($GLOBALS["VERBOSE"]){echo "$moduleso_file: $module_name OK\n";}
	return "LoadModule $module_name\t$APACHE_MODULES_PATH/$moduleso_file";
}

function AuthorizedModule($modulename){
$sock=new sockets();
$ZarafaApachePHPFPMEnable=$sock->GET_INFO("ZarafaApachePHPFPMEnable");
$ZarafaWebNTLM=intval($sock->GET_INFO("ZarafaWebNTLM"));
if($ZarafaApachePHPFPMEnable==1){$f["mod_php5.so"]=true;}

	
//$f["mod_alias.so"]=true;
$f["mod_dav_fs.so"]=true;
if($ZarafaWebNTLM==0){
	$f["mod_ldap.so"]=true;
	$f["mod_authnz_ldap.so"]=true;
}

$f["mod_log_sql.so"]=true;
$f["mod_log_sql_mysql.so"]=true;
$f["mod_log_sql_ssl.so"]=true;
$f["mod_jk.so"]=true;
$f["mod_python.so"]=true;
$f["mod_qos.so"]=true;
$f["mod_security2.so"]=true;

$f["mod_auth_basic.so"]=true;
$f["mod_authn_file.so"]=true;
$f["mod_authz_default.so"]=true;
$f["mod_authz_groupfile.so"]=true;
$f["mod_authz_host.so"]=true;
$f["mod_authz_user.so"]=true;
$f["mod_autoindex.so"]=true;
if($ZarafaApachePHPFPMEnable==0){$f["mod_cgi.so"]=true;}
$f["mod_deflate.so"]=true;
$f["mod_dir.so"]=true;
$f["mod_env.so"]=true;
//$f["mod_mime.so"]=true;
$f["mod_negotiation.so"]=true;
$f["libphp5.so"]=true;
$f["mod_php5.so"]=true;
$f["mod_setenvif.so"]=true;
$f["mod_status.so"]=true;
$f["mod_ssl.so"]=true;
$f["mod_dav.so"]=true;
$f["mod_ldap.so"]=true;
$f["mod_suexec.so"]=true;
if(isset($f[$modulename])){return false;}
return true;
}

?>