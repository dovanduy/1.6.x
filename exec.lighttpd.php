<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');



	$GLOBALS["ARGVS"]=implode(" ",$argv);
	if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
	if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
	if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
	if($argv[1]=="--status"){$GLOBALS["OUTPUT"]=true;status();die();}
	if($argv[1]=="--phpmyadmin"){$GLOBALS["OUTPUT"]=true;PHP_MYADMIN();die();}
	
	


function restart(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Already Artica task running PID $oldpid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	stop(true);
	start(true);	
}	


	
function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Already Artica task running PID $oldpid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$pid=LIGHTTPD_PID();
	
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: Artica Web service already stopped...\n";}
		return;
	}	
	$pid=LIGHTTPD_PID();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$lighttpd_bin=$unix->find_program("lighttpd");
	$kill=$unix->find_program("kill");
	
	
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: Artica Web service Shutdown pid $pid...\n";}
	shell_exec("$kill $pid >/dev/null 2>&1");
	for($i=0;$i<5;$i++){
		$pid=LIGHTTPD_PID();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}	
	
	$pid=LIGHTTPD_PID();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: Artica Web service success...\n";}
		killallphpcgi();
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: Artica Web service shutdown - force - pid $pid...\n";}
	shell_exec("$kill -9 $pid >/dev/null 2>&1");
	for($i=0;$i<5;$i++){
		$pid=LIGHTTPD_PID();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}	
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: Artica Web service success...\n";}
		killallphpcgi();
		return;
	}else{
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: Artica Web service failed...\n";}
	}	
}

function killallphpcgi(){
	
	$unix=new unix();
	$phpcgi=$unix->LIGHTTPD_PHP5_CGI_BIN_PATH();
	$kill=$unix->find_program("kill");
	$array=$unix->PIDOF_PATTERN_ALL($phpcgi);
	$userp=LIGHTTPD_GET_USER();
	
	if(preg_match("#^(.+?):#", $userp,$re)){$user=strtolower(trim($re[1]));}
	
	if(count($array)==0){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: Artica Web service No ghost processes...\n";}
		return;
	}
	$c=0;
	while (list ($pid, $line) = each ($array) ){
		$username=trim(strtolower($unix->PROCESS_GET_USER($pid)));
		if($username==null){continue;}
		if($username<>$user){continue;}
		$c++;
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: Artica Web service Stopping ghots processes $pid\n";}
		shell_exec("$kill -9 $pid 2>&1");
	}
	
	if($c==0){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: Artica Web service No ghost processes...\n";}
	}
	
}

function status(){
	$unix=new unix();
	$phpcgi=$unix->LIGHTTPD_PHP5_CGI_BIN_PATH();	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$oldpid=$unix->get_pid_from_file($pidfile);
	$nohup=$unix->find_program("nohup");
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service Already Artica task running PID $oldpid since {$time}mn\n";}
		return;
	}
	
	if(!$GLOBALS["VERBOSE"]){
		$timeExec=$unix->file_time_min($pidtime);
		if($timeExec<15){return;}
	}
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	@file_put_contents($pidfile, getmypid());	
	
	$pid=LIGHTTPD_PID();
	$unix=new unix();
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service running $pid since {$timepid}Mn...\n";}
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service stopped...\n";}
		start();
		return;
	}
	$MAIN_PID=$pid;
	$phpcgi=$unix->LIGHTTPD_PHP5_CGI_BIN_PATH();
	$kill=$unix->find_program("kill");
	$array=$unix->PIDOF_PATTERN_ALL($phpcgi);
	if(count($array)==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service no php-cgi processes...\n";}
		shell_exec("$nohup /etc/init.d/php5-fpm restart >/dev/null 2>&1 &");
		return;
	}
	while (list ($pid, $line) = each ($array) ){
		$username=$unix->PROCESS_GET_USER($pid);
		if($username==null){continue;}
		if($username<>"root"){continue;}
		$time=$unix->PROCCESS_TIME_MIN($pid);
		$arrayPIDS[$pid]=$time;
		$ppid=$unix->PPID_OF($pid);
		if($time>20){
			if($ppid<>$MAIN_PID){
				if($GLOBALS["VERBOSE"]){echo "killing $pid {$time}mn ppid:$ppid/$MAIN_PID\n";}
				shell_exec("$kill -9 $pid 2>&1");
			}
		}
	}

	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service ".count($arrayPIDS)." php-cgi processes...\n";}
	
}



function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service Already Artica task running PID $oldpid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}	
	
	$EnableArticaFrontEndToNGninx=$sock->GET_INFO("EnableArticaFrontEndToNGninx");
	if(!is_numeric($EnableArticaFrontEndToNGninx)){$EnableArticaFrontEndToNGninx=0;}
	$pid=LIGHTTPD_PID();
	if($EnableArticaFrontEndToNGninx==1){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service transfered to nginx..\n";}
		if($unix->process_exists($pid)){stop(true);}
		shell_exec("/etc/init.d/nginx start");
		return;
	}
	
	
	
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service Artica Web service already started $pid since {$timepid}Mn...\n";}
		return;
	}
		
	
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$lighttpd_bin=$unix->find_program("lighttpd");
	$LIGHTTPD_CONF_PATH=LIGHTTPD_CONF_PATH();
	
	@mkdir("/var/run/lighttpd",0755,true);
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.web-community-filter.php --register-lic >/dev/null 2>&1 &";
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	
	
	
	
	shell_exec($cmd);
	buildConfig();
	$cmd="$lighttpd_bin -f $LIGHTTPD_CONF_PATH";
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	shell_exec($cmd);
	
	for($i=0;$i<6;$i++){
		$pid=LIGHTTPD_PID();
		if($unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service waiting $i/6...\n";}
		sleep(1);
	}
	
	$pid=LIGHTTPD_PID();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service Success service started pid:$pid...\n";}
		shell_exec("$php5 /usr/share/artica-postfix/exec.apc.compile.php");
		if(!is_file("/usr/share/artica-postfix/ressources/settings.inc")){shell_exec("$nohup /usr/share/artica-postfix/bin/process1 --web-settings >/dev/null 2>&1 &");}
		if(!is_file('/etc/init.d/artica-memcache')){shell_exec("$php5 /usr/share/artica-postfix/exec.initslapd.php --memcache");}
		shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.initslapd.php --phppfm-restart-back >/dev/null 2>&1 &");
		shell_exec("$nohup /etc/init.d/artica-memcached start >/dev/null 2>&1 &");
		
		
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service failed...\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: $cmd\n";}
	}

}
//##############################################################################
function LIGHTTPD_PID(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file('/var/run/lighttpd/lighttpd.pid');
	if($unix->process_exists($pid)){return $pid;}
	$lighttpd_bin=$unix->find_program("lighttpd");
	$LIGHTTPD_CONF_PATH=LIGHTTPD_CONF_PATH();
	return $unix->PIDOF_PATTERN($lighttpd_bin." -f $LIGHTTPD_CONF_PATH");
}
//##############################################################################
function  LIGHTTPD_CONF_PATH(){
	if(isset($GLOBALS["LIGHTTPD_CONF_PATH"])){return $GLOBALS["LIGHTTPD_CONF_PATH"];}
	$f[]="/etc/lighttpd/lighttpd.conf";
 	$f[]="/etc/lighttpd/lighttpd.conf";
  	$f[]="/opt/artica/conf/lighttpd.conf";
 	$f[]="/usr/local/etc/lighttpd.conf";
 	while (list ($pid, $line) = each ($f) ){
 		if(is_file($line)){
 			$GLOBALS["LIGHTTPD_CONF_PATH"]=$line;
 			return $line;}
 	}
 	$GLOBALS["LIGHTTPD_CONF_PATH"]="/etc/lighttpd/lighttpd.conf";
 	return $GLOBALS["LIGHTTPD_CONF_PATH"];
}
//##############################################################################
function LIGHTTPD_MODULES_PATH(){
	$f[]="/usr/lib64/lighttpd/mod_alias.so";
	$f[]="/usr/local/lib64/lighttpd/mod_alias.so";
	$f[]="/usr/lib/lighttpd/mod_alias.so";
	$f[]="/usr/local/lib/lighttpd/mod_alias.so";
	while (list ($pid, $line) = each ($f) ){
		if(is_file($line)){return dirname($line);}
	}	
}
//##############################################################################
function AWSTATS_www_root(){
	$f[]="/usr/local/awstats/wwwroot/icon";
	$f[]="/usr/share/awstats/icon";
	$f[]="/var/www/awstats/icon";
	$f[]="/usr/share/awstats/wwwroot/icon";
	while (list ($pid, $line) = each ($f) ){
		if(is_dir($line)){return $line;}
	}
}
//##############################################################################
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
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service Available module.....: \"{$re[1]}\"\n";}
			$re[1]=trim(strtolower($re[1]));
			$GLOBALS["LIGHTTPDMODS"][$re[1]]=true;
			continue;
		}
			
	}
}	
//##############################################################################
function LIGHTTPD_GET_USER(){
	$sock=new sockets();
	$user=trim($sock->GET_INFO('LighttpdUserAndGroup'));
	
	if($user<>null){
		$user=str_replace("lighttpd:lighttpd:lighttpd", "lighttpd:lighttpd", $user);
		$user=str_replace("www-data:www-data:www-data", "www-data:www-data", $user);
		return $user;
	}
	
	$LIGHTTPD_CONF_PATH=LIGHTTPD_CONF_PATH();
	$f=explode("\n",@file_get_contents($LIGHTTPD_CONF_PATH));
	$mem=null;
	$gp=null;
	while (list ($pid, $line) = each ($f) ){
		if(preg_match('#^server\.username.+?"(.+?)"#', $line,$re)){$mem=$re[1];continue;}
		if(preg_match('#^server\.groupname.+?"(.+?)"#', $line,$re)){$gp=$re[1];continue;}	
		if( ($mem<>null) && ($gp<>null) ){
			$sock->SET_INFO("LighttpdUserAndGroup", "$mem:$gp");
			return "$mem:$gp";}
		}
		
		return "www-data:www-data";

}
//##############################################################################

function buildConfig(){
	$unix=new unix();
	$sock=new sockets();
	$phpcgi=$unix->LIGHTTPD_PHP5_CGI_BIN_PATH();
	$chown=$unix->find_program("chown");
	$perlbin=$unix->find_program("perl");
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$PHP_STANDARD_MODE=true;
	
	$phpfpm=$unix->find_program('php5-fpm')  ;
	if(!is_file($phpfpm)){$phpfpm=$unix->find_program('php-fpm');}
	
	@mkdir("/usr/share/artica-postfix/framework",0755,true);
	@mkdir("/usr/share/artica-postfix/ressources/sock",0755,true);

	$LighttpdRunAsminimal=$sock->GET_INFO("LighttpdRunAsminimal");
	$LighttpdArticaMaxProcs=$sock->GET_INFO("LighttpdArticaMaxProcs");
	$LighttpdArticaMaxChildren=$sock->GET_INFO("LighttpdArticaMaxChildren");
	$PHP_FCGI_MAX_REQUESTS=$sock->GET_INFO("PHP_FCGI_MAX_REQUESTS");
	$SessionPathInMemory=$sock->GET_INFO("SessionPathInMemory");
	if(!is_numeric($LighttpdRunAsminimal)){$LighttpdRunAsminimal=0;}
	if(!is_numeric($LighttpdArticaMaxProcs)){$LighttpdArticaMaxProcs=0;}
	if(!is_numeric($LighttpdArticaMaxChildren)){$LighttpdArticaMaxChildren=0;}
	if(!is_numeric($PHP_FCGI_MAX_REQUESTS)){$PHP_FCGI_MAX_REQUESTS=200;}
	if(!is_numeric($SessionPathInMemory)){$SessionPathInMemory=0;}
	
	
	if(!is_file("/opt/artica/ssl/certs/lighttpd.pem")){
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service creating SSL certificate..\n";}
		exec("/usr/share/artica-postfix/bin/artica-install -lighttpd-cert 2>&1",$results);
		while (list ($pid, $line) = each ($results) ){
			$line=trim($line);
			if($line==null){continue;}
			if(preg_match("#Starting.*?lighttpd(.+)#", $line,$re)){$line=$re[1];}
			$line=str_replace(": ", "", $line);
			if($GLOBALS["OUTPUT"]){echo "Starting......: [ARTI]: Artica Web service $line\n";}
		}
		
	}
	$results=array();
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service creating PHP configuration..\n";}
	exec("/usr/share/artica-postfix/bin/artica-install --php-ini 2>&1",$results);
	while (list ($pid, $line) = each ($results) ){
				$line=trim($line);
			if($line==null){continue;}
			if(preg_match("#Starting.*?lighttpd(.+)#", $line,$re)){$line=$re[1];}
			$line=str_replace(": ", "", $line);
			if($GLOBALS["OUTPUT"]){echo "Starting......: [ARTI]: Artica Web service $line\n";}
	}	
	PHP_MYADMIN();
	
	
	$PHP_FCGI_CHILDREN=3;
	$max_procs=3;
	
	
	if($LighttpdArticaMaxProcs>0){$max_procs=$LighttpdArticaMaxProcs;}
	if($LighttpdArticaMaxChildren>0){$PHP_FCGI_CHILDREN=$LighttpdArticaMaxChildren;}
	
	if(!$unix->ISMemoryHiger1G()){
		$PHP_FCGI_CHILDREN=2;
		$max_procs=1;
	}
	
	if($LighttpdRunAsminimal==1){
		$max_procs=1;
		$PHP_FCGI_CHILDREN=2;
		$PHP_FCGI_MAX_REQUESTS=500;
	}
	

	
	$phpfpm=$unix->APACHE_LOCATE_PHP_FPM();
	$EnablePHPFPM=$sock->GET_INFO("EnablePHPFPM");
	if(!is_numeric($EnablePHPFPM)){$EnablePHPFPM=1;}
	if(!is_file($phpfpm)){$EnablePHPFPM=0;}
	
	if($EnablePHPFPM==1){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service Using PHP-FPM........: Yes\n";}
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service Using PHP-FPM........: No\n";}
	}
	
	$ArticaHttpsPort=9000;
	$NoLDAPInLighttpdd=0;
	$ArticaHttpUseSSL=1;
	
	$ArticaHttpsPort=$sock->GET_INFO("ArticaHttpsPort");
	$ArticaHttpUseSSL=$sock->GET_INFO("ArticaHttpUseSSL");
	if(!is_numeric($ArticaHttpUseSSL)){$ArticaHttpUseSSL=1;}
	if(!is_numeric($ArticaHttpsPort)){$ArticaHttpsPort="9000";}
	
	$ArticaHttpUseSSL=$sock->GET_INFO('ArticaHttpUseSSL');
	if(!is_numeric($ArticaHttpUseSSL)){$ArticaHttpUseSSL=1;}
	
	$NoLDAPInLighttpdd=$sock->GET_INFO('NoLDAPInLighttpdd');
	if(!is_numeric($NoLDAPInLighttpdd)){$NoLDAPInLighttpdd=0;} 

	$LighttpdUseUnixSocket=$sock->GET_INFO('LighttpdUseUnixSocket');
	if(!is_numeric($LighttpdUseUnixSocket)){$LighttpdUseUnixSocket=0;}

	$lighttpdPhpPort=$sock->GET_INFO('lighttpdPhpPort');
	if(!is_numeric($lighttpdPhpPort)){$lighttpdPhpPort=1808;}
	 
	$DenyMiniWebFromStandardPort=$sock->GET_INFO('DenyMiniWebFromStandardPort');
	if(!is_numeric($DenyMiniWebFromStandardPort)){$DenyMiniWebFromStandardPort=0;}
		 
	$LighttpdArticaDisableSSLv2=$sock->GET_INFO('LighttpdArticaDisableSSLv2');
	if(!is_numeric($LighttpdArticaDisableSSLv2)){$LighttpdArticaDisableSSLv2=1;}
 
	$LighttpdArticaMaxProcs=$sock->GET_INFO('LighttpdArticaMaxProcs');
	if(!is_numeric($LighttpdArticaMaxProcs)){$LighttpdArticaMaxProcs=0;}
	
	$LighttpdArticaMaxChildren=$sock->GET_INFO('LighttpdArticaMaxChildren');
	if(!is_numeric($LighttpdArticaMaxChildren)){$LighttpdArticaMaxChildren=0;}
	
	
	$LighttpdRunAsminimal=$sock->GET_INFO('LighttpdRunAsminimal');
	if(!is_numeric($LighttpdRunAsminimal)){$LighttpdRunAsminimal=0;}
	
	$PHP_FCGI_MAX_REQUESTS=$sock->GET_INFO('PHP_FCGI_MAX_REQUESTS');
	if(!is_numeric($PHP_FCGI_MAX_REQUESTS)){$PHP_FCGI_MAX_REQUESTS=200;}
	
	
	$EnablePHPFPM=$sock->GET_INFO('EnablePHPFPM');
	if(!is_numeric($EnablePHPFPM)){$EnablePHPFPM=1;}
	$EnablePHPFPM=1;
	$PHP_STANDARD_MODE=true;
	$LighttpdArticaListenIP=$sock->GET_INFO('LighttpdArticaListenIP');	
	$phpcgi_path=$unix->LIGHTTPD_PHP5_CGI_BIN_PATH();
	$LIGHTTPD_GET_USER=LIGHTTPD_GET_USER();
	$LIGHTTPD_CONF_PATH=LIGHTTPD_CONF_PATH();
	
	if(preg_match("#^(.+?):(.+)#", $LIGHTTPD_GET_USER,$re)){
		$LIGHTTPD_USER=$re[1];
		$LIGHTTPD_GROUP=$re[1];
	}
	
	$PHP_FCGI_CHILDREN=1;
	$max_procs=2;

	
	if($LighttpdArticaMaxProcs>0){$max_procs=$LighttpdArticaMaxProcs;}
	if($LighttpdArticaMaxChildren>0){$HP_FCGI_CHILDREN=$LighttpdArticaMaxChildren;}	
	
	if($LighttpdRunAsminimal==1){
		$max_procs=2;
		$PHP_FCGI_CHILDREN=2;
	}
	$mod_auth=isModule('mod_auth');  
	
	if(is_file('/proc/user_beancounters')){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service VPS mode enabled, swith to socket mode for PHP\n";}
		$LighttpdUseUnixSocket=1;
	}
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service MAX Procs............: $max_procs\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service Php5 processes.......: $PHP_FCGI_CHILDREN\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service Max cnx/processes....: $PHP_FCGI_MAX_REQUESTS\n";}	
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service php-cgi path.........: $phpcgi_path\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service chown path...........: $chown\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service php path.............: $php\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service php FPM Path.........: $phpfpm\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service Perl Path............: $perlbin\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service Conf Path............: $LIGHTTPD_CONF_PATH\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service Pid Path.............: /var/run/lighttpd/lighttpd.pid\n";}
	
	
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service SSL enabled..........: $ArticaHttpUseSSL\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service Disable SSLv2........: $LighttpdArticaDisableSSLv2\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service Listen Port..........: $ArticaHttpsPort\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service Run as...............: $LIGHTTPD_USER / $LIGHTTPD_GROUP\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service No LDAP in Lighttpd..: $NoLDAPInLighttpdd\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service Mod auth installed...: $mod_auth\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service Use Unix socket......: $LighttpdUseUnixSocket\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service Sessions in Memory...: {$SessionPathInMemory}MB\n";}
	
	
	
	
	$MakeDirs[]="/opt/artica/ssl/certs";
	$MakeDirs[]="/var/lib/php/session";
	$MakeDirs[]="/var/lighttpd/upload";
	$MakeDirs[]="/var/run/lighttpd";
	$MakeDirs[]="/var/log/lighttpd";
	$MakeDirs[]="/opt/artica/share/www/jpegPhoto";
	$MakeDirs[]=dirname($LIGHTTPD_CONF_PATH);

	
	while (list ($pid, $dir) = each ($MakeDirs) ){
		@mkdir($dir,0755,true);
		shell_exec("$chown -R $LIGHTTPD_GET_USER $dir");
		
	}
	
	$f[]='#artica-postfix saved by artica lighttpd.conf (Artica Install binary) v3.0';
	$f[]='';
	$f[]='server.modules = (';
	$f[]='        "mod_alias",';
	$f[]='        "mod_access",';
	$f[]='        "mod_accesslog",';
	$f[]='        "mod_compress",';
	$f[]='        "mod_fastcgi",';
	$f[]='        "mod_cgi",';
	$f[]='	       "mod_status",';
	if($NoLDAPInLighttpdd==1){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service LDAP Mode is disabled\n";}
	}
	if($mod_auth){$f[]='	       "mod_auth"';}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service mod_auth module does not exists (should be a security issue !!!)\n";}
		
	}
	$f[]=')';
	$f[]='';
	$f[]='server.document-root        = "/usr/share/artica-postfix"';
	$f[]='server.username = "'.$LIGHTTPD_USER.'"';
	$f[]='server.groupname = "'.$LIGHTTPD_GROUP.'"';
	$f[]='server.errorlog-use-syslog = "enable"';
	//$f[]='server.errorlog             = "/var/log/lighttpd/error.log"';
	$f[]='index-file.names            = ( "index.php","index.cgi")';
	$f[]='';
	$f[]='mimetype.assign             = (';
	$f[]='  ".pdf"          =>      "application/pdf",';
	$f[]='  ".sig"          =>      "application/pgp-signature",';
	$f[]='  ".spl"          =>      "application/futuresplash",';
	$f[]='  ".class"        =>      "application/octet-stream",';
	$f[]='  ".ps"           =>      "application/postscript",';
	$f[]='  ".torrent"      =>      "application/x-bittorrent",';
	$f[]='  ".dvi"          =>      "application/x-dvi",';
	$f[]='  ".gz"           =>      "application/x-gzip",';
	$f[]='  ".pac"          =>      "application/x-ns-proxy-autoconfig",';
	$f[]='  ".swf"          =>      "application/x-shockwave-flash",';
	$f[]='  ".tar.gz"       =>      "application/x-tgz",';
	$f[]='  ".tgz"          =>      "application/x-tgz",';
	$f[]='  ".tar"          =>      "application/x-tar",';
	$f[]='  ".zip"          =>      "application/zip",';
	$f[]='  ".mp3"          =>      "audio/mpeg",';
	$f[]='  ".m3u"          =>      "audio/x-mpegurl",';
	$f[]='  ".wma"          =>      "audio/x-ms-wma",';
	$f[]='  ".wax"          =>      "audio/x-ms-wax",';
	$f[]='  ".ogg"          =>      "application/ogg",';
	$f[]='  ".wav"          =>      "audio/x-wav",';
	$f[]='  ".gif"          =>      "image/gif",';
	$f[]='  ".jar"          =>      "application/x-java-archive",';
	$f[]='  ".jpg"          =>      "image/jpeg",';
	$f[]='  ".jpeg"         =>      "image/jpeg",';
	$f[]='  ".png"          =>      "image/png",';
	$f[]='  ".xbm"          =>      "image/x-xbitmap",';
	$f[]='  ".xpm"          =>      "image/x-xpixmap",';
	$f[]='  ".xwd"          =>      "image/x-xwindowdump",';
	$f[]='  ".css"          =>      "text/css",';
	$f[]='  ".html"         =>      "text/html",';
	$f[]='  ".htm"          =>      "text/html",';
	$f[]='  ".js"           =>      "text/javascript",';
	$f[]='  ".asc"          =>      "text/plain",';
	$f[]='  ".c"            =>      "text/plain",';
	$f[]='  ".cpp"          =>      "text/plain",';
	$f[]='  ".log"          =>      "text/plain",';
	$f[]='  ".conf"         =>      "text/plain",';
	$f[]='  ".text"         =>      "text/plain",';
	$f[]='  ".txt"          =>      "text/plain",';
	$f[]='  ".dtd"          =>      "text/xml",';
	$f[]='  ".xml"          =>      "text/xml",';
	$f[]='  ".mpeg"         =>      "video/mpeg",';
	$f[]='  ".mpg"          =>      "video/mpeg",';
	$f[]='  ".mov"          =>      "video/quicktime",';
	$f[]='  ".qt"           =>      "video/quicktime",';
	$f[]='  ".avi"          =>      "video/x-msvideo",';
	$f[]='  ".asf"          =>      "video/x-ms-asf",';
	$f[]='  ".asx"          =>      "video/x-ms-asf",';
	$f[]='  ".wmv"          =>      "video/x-ms-wmv",';
	$f[]='  ".bz2"          =>      "application/x-bzip",';
	$f[]='  ".tbz"          =>      "application/x-bzip-compressed-tar",';
	$f[]='  ".tar.bz2"      =>      "application/x-bzip-compressed-tar",';
	$f[]='  ""              =>      "application/octet-stream",';
	$f[]=' )';
	$f[]='';
	$f[]='';
	$f[]='accesslog.filename          = "/var/log/lighttpd/access.log"';
	$f[]='url.access-deny             = ( "~", ".inc",".log",".ini" )';
	$f[]='';
	$f[]='static-file.exclude-extensions = ( ".php", ".pl", ".fcgi" )';
	$f[]='server.port                 = '.$ArticaHttpsPort;
	if(strlen($LighttpdArticaListenIP)>3){$f[]='server.bind                = "'.$LighttpdArticaListenIP.'"';}
	$f[]='server.pid-file             = "/var/run/lighttpd/lighttpd.pid"';
	$f[]='server.max-fds 		   = 2048';
	$f[]='server.max-connections      = 512';
	$f[]='server.network-backend      = "write"';

	
	shell_exec("$php /usr/share/artica-postfix/exec.lighttpd.nets.php");
	shell_exec("$php /usr/share/artica-postfix/exec.lighttpd.nets.php --phpmyadmin");
	
	if(is_file('/etc/artica-postfix/lighttpd_nets')){$f[]=@file_get_contents("/etc/artica-postfix/lighttpd_nets");}
	
	$f[]='';
	if(is_file($phpfpm)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service PHP-FPM is installed\n";}
		if($EnablePHPFPM==1){
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service PHP-FPM is enabled\n";}
			$PHP_STANDARD_MODE=false;
			$f[]='fastcgi.server = ( ".php" =>((';
			$f[]='         "socket" => "/var/run/php-fpm.sock",';
		}
	}
	
	
	
	if ($PHP_STANDARD_MODE){
		$f[]='fastcgi.server = ( ".php" =>((';
		$f[]='         "bin-path" => "/usr/bin/php-cgi",';
		if($LighttpdUseUnixSocket==1){
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service Fast-cgi server unix socket mode\n";}
			$f[]='         "socket" => "/var/run/lighttpd/php.socket" + var.PID,';
		}else{
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service Fast-cgi server socket 127.0.0.1:$lighttpdPhpPort\n";}
			$f[]='         "host" => "127.0.0.1","port" =>'.$lighttpdPhpPort.',';
		}
	}
	
	$f[]='         "max-procs" => '.$max_procs.',';
	$f[]='         "idle-timeout" => 10,';
	$f[]='         "bin-environment" => (';
	$f[]='             "PHP_FCGI_CHILDREN" => "'.$PHP_FCGI_CHILDREN.'",';
	$f[]='             "PHP_FCGI_MAX_REQUESTS" => "'.$PHP_FCGI_MAX_REQUESTS.'"';
	$f[]='          ),';
	$f[]='          "bin-copy-environment" => (';
	$f[]='            "PATH", "SHELL", "USER"';
	$f[]='           ),';
	$f[]='          "broken-scriptfilename" => "enable"';
	$f[]='        ))';
	$f[]=')';
	
	
	if($ArticaHttpUseSSL==1){
		$f[]='ssl.engine                 = "enable"';
		$f[]='ssl.pemfile                = "/opt/artica/ssl/certs/lighttpd.pem"';
	}
	
	if($LighttpdArticaDisableSSLv2==1){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service Disable SSLv2 and weak ssl cipher\n";}
		$f[]='ssl.use-sslv2              = "disable"';
		$f[]='ssl.cipher-list            = "TLSv1+HIGH !SSLv2 RC4+MEDIUM !aNULL !eNULL !3DES @STRENGTH"';
	}else{
		$f[]='ssl.use-sslv2              = "enable"';
		$f[]='ssl.cipher-list            = "TLSv1+HIGH RC4+MEDIUM !SSLv2 !3DES !aNULL @STRENGTH"';
	}
	
	
	if($NoLDAPInLighttpdd==0){
		if($mod_auth){
			$f[]='status.status-url          = "/server-status"';
			$f[]='status.config-url          = "/server-config"';
		}
	}
	
	$f[]='server.upload-dirs         = ( "/var/lighttpd/upload" )';
	
	if(is_file('/etc/artica-postfix/lighttpd.phpmyadmin')){
		$f[]='';
		$f[]=@file_get_contents('/etc/artica-postfix/lighttpd.phpmyadmin');
	}
	
	$f[]='	server.follow-symlink = "enable"';
	$f[]='alias.url +=("/monitorix"  => "/var/www/monitorix/")';
	$f[]='alias.url += ("/blocked_attachments"=> "/var/spool/artica-filter/bightml")';
	$f[]='alias.url += ("/squid-rrd"=> "/opt/artica/share/www/squid/rrd")';
	$f[]='alias.url += ("/artica-agent"=> "/usr/share/artica-postfix/ressources/artica-agent")';
	
	if($DenyMiniWebFromStandardPort==1){
		$f[]='$HTTP["url"] =~ "^/miniadm.*|/computers|/user-backup" { url.access-deny = ( "" )}';
	}
	
	$AWSTATS_www_root=AWSTATS_www_root();
	
	$f[]='$HTTP["url"] =~ "^/prxy.*\.php" { url.access-deny = ( "" )}';
	if( is_dir( $AWSTATS_www_root ) ){ $f[]='alias.url += ( "/awstats" => "'.$AWSTATS_www_root.'" )';}
	if(is_file('/usr/share/poweradmin/index.php')){
		$f[]='alias.url += ( "/powerdns" => "/usr/share/poweradmin" )';
		shell_exec("$php  /usr/share/artica-postfix/exec.pdns.php --poweradmin");
	}

	//$perlbin
	$f[]='alias.url += ( "/cgi-bin/" => "/usr/lib/cgi-bin/" )';


	
	$f[]='';
	$f[]='cgi.assign= (';
	$f[]='	".pl"  => "'.$perlbin.'",';
	$f[]='	".php" => "/usr/bin/php-cgi",';
	$f[]='	".py"  => "/usr/bin/python",';
	$f[]='	".cgi"  => "'.$perlbin.'",';
	

	
	if(is_file("/usr/lib/mailman/bin/mailmanctl")){
		$f[]='"/admin" => "",';
		$f[]='"/admindb" => "",';
		$f[]='"/confirm" => "",';
		$f[]='"/create" => "",';
		$f[]='"/edithtml" => "",';
		$f[]='"/listinfo" => "",';
		$f[]='"/options" => "",';
		$f[]='"/private" => "",';
		$f[]='"/rmlist" => "",';
		$f[]='"/roster" => "",';
		$f[]='"/subscribe" => ""';
	}
	$f[]=')';
	$f[]='';
	if($mod_auth){
		$f[]='auth.debug = 2';
		$f[]='$HTTP["url"] =~ "^/cgi-bin/" {';
		$f[]='auth.backend = "plain"';
		$f[]='auth.backend.plain.userfile = "/etc/lighttpd/.lighttpdpassword" ';
		$f[]='auth.require = ("/cgi-bin/" => (';
		$f[]='     "method"  => "basic",';
		$f[]='     "realm"   => "awstats Statistics",';
		$f[]='     "require" => "valid-user"';
		$f[]='  ))';
		$f[]='}';
		$f[]='';

	
		$f[]='$HTTP["url"] =~ "^/server-status" {';
		$f[]='auth.backend = "plain"';
		$f[]='auth.backend.plain.userfile = "/etc/lighttpd/.lighttpdpassword" ';
		$f[]='auth.require = ("/server-status" => (';
		$f[]='     "method"  => "basic",';
		$f[]='     "realm"   => "Lighttpd config - status",';
		$f[]='     "require" => "valid-user"';
		$f[]='  ))';
		$f[]='}';
		$f[]='';
		
		$f[]='$HTTP["url"] =~ "^/server-config" {';
		$f[]='auth.backend = "plain"';
		$f[]='auth.backend.plain.userfile = "/etc/lighttpd/.lighttpdpassword" ';
		$f[]='auth.require = ("/server-config" => (';
		$f[]='     "method"  => "basic",';
		$f[]='     "realm"   => "Lighttpd config - status",';
		$f[]='     "require" => "valid-user"';
		$f[]='  ))';
		$f[]='}';
		$f[]='';
		
		$f[]='$HTTP["url"] =~ "^/squid/" {';
		$f[]='auth.backend = "plain"';
		$f[]='auth.debug = 2';
		$f[]='auth.backend.plain.userfile = "/etc/lighttpd/squid-users.passwd" ';
		$f[]='auth.require = ("/squid/" => (';
		$f[]='     "method"  => "basic",';
		$f[]='     "realm"   => "Squid Statistics",';
		$f[]='     "require" => "valid-user"';
		$f[]='  ))';
		$f[]='}';
		$f[]='';
		
		$f[]='$HTTP["url"] =~ "^/cluebringer/" {';
		$f[]='auth.backend = "plain"';
		$f[]='auth.debug = 2';
		$f[]='auth.backend.plain.userfile = "/etc/lighttpd/cluebringer.passwd" ';
		$f[]='auth.require = ("/cluebringer/" => (';
		$f[]='     "method"  => "basic",';
		$f[]='     "realm"   => "ClueBringer (Policyd V2) administration",';
		$f[]='     "require" => "valid-user"';
		$f[]='  ))';
		$f[]='}';
		$f[]='';
	}	
	
	
	@file_put_contents($LIGHTTPD_CONF_PATH, @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service $LIGHTTPD_CONF_PATH done\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service Check sessions...\n";}
	
	shell_exec("$nohup $php /usr/share/artica-postfix/exec.shm.php --SessionMem >/dev/null 2>&1 &");
	shell_exec("$nohup $php /usr/share/artica-postfix/exec.shm.php --service-up >/dev/null 2>&1 &");
	
	
}
function PHP_MYADMIN(){
	$sock=new sockets();
	$phpmyadminAllowNoPassword=$sock->GET_INFO("phpmyadminAllowNoPassword");
	if(!is_numeric($phpmyadminAllowNoPassword)){$phpmyadminAllowNoPassword=0;}
	if(!is_file('/usr/share/phpmyadmin/index.php')){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service PhpMyAdmin: /usr/share/phpmyadmin/index.php no such file\n";}
		return;
	}
	include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
	
	if(is_file('/etc/artica-postfix/phpmyadmin_config.txt')){$phpmyadmin_config_add=@file_get_contents('/etc/artica-postfix/phpmyadmin_config.txt');}
	@mkdir("/usr/share/phpmyadmin/config",0755,true);
	
	
	$database_password=@file_get_contents("/etc/artica-postfix/settings/Mysql/database_password");
	if(database_password=='!nil'){$database_password=null;}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service PhpMyAdmin: AllowNoPassword=$phpmyadminAllowNoPassword\n";}
	//$phpmyadminAllowNoPassword
	
	$q=new mysql();
	$f[]='<?php';
	$f[]='/* Servers configuration */';
	$f[]='$i = 0;';
	$f[]='';
	$f[]='/* Server: Artica Mysql [1] */';
	$f[]='$i++;';
	$f[]='$cfg["Servers"][$i]["verbose"] = "Artica Mysql";';
	$f[]='$cfg["Servers"][$i]["host"] = "'.$q->mysql_server.'";';
	$f[]='$cfg["Servers"][$i]["port"] = '.$q->mysql_port.';';
	$f[]='$cfg["Servers"][$i]["socket"] = "'.$q->SocketName.'";';
	$f[]='$cfg["Servers"][$i]["connect_type"] = "tcp";';
	$f[]='$cfg["Servers"][$i]["extension"] = "mysql";';
	$f[]='$cfg["Servers"][$i]["auth_type"] = "cookie";';
	$f[]='$cfg["Servers"][$i]["user"] = "'.$q->mysql_admin.'";';
	$f[]='$cfg["Servers"][$i]["password"] = "'.$q->mysql_password.'";';
	if($phpmyadminAllowNoPassword==1){  $f[]='$cfg["Servers"][$i]["AllowNoPassword"] = True;';}
	if(is_file('/opt/squidsql/bin/mysqld')){
		$f[]='$i++;';
		$f[]='$cfg["Servers"][$i]["verbose"] = "Squid Mysql";';
		$f[]='$cfg["Servers"][$i]["socket"] = "/var/run/mysqld/squid-db.sock";';
		$f[]='$cfg["Servers"][$i]["connect_type"] = "socket";';
		$f[]='$cfg["Servers"][$i]["extension"] = "mysql";';
		$f[]='$cfg["Servers"][$i]["auth_type"] = "cookie";';
		$f[]='$cfg["Servers"][$i]["user"] = "root";';
		$f[]='$cfg["Servers"][$i]["password"] = "";';
		$f[]='$cfg["Servers"][$i]["AllowNoPassword"] = True;';
	}
	if(is_file('/opt/amavisdb/data/amavis/db.opt')){
		$f[]='$i++;';
		$f[]='$cfg["Servers"][$i]["verbose"] = "Amavis Mysql";';
		$f[]='$cfg["Servers"][$i]["socket"] = "/var/run/mysqld/amavis-db.sock";';
		$f[]='$cfg["Servers"][$i]["connect_type"] = "socket";';
		$f[]='$cfg["Servers"][$i]["extension"] = "mysql";';
		$f[]='$cfg["Servers"][$i]["auth_type"] = "cookie";';
		$f[]='$cfg["Servers"][$i]["user"] = "root";';
		$f[]='$cfg["Servers"][$i]["password"] = "";';
		$f[]='$cfg["Servers"][$i]["AllowNoPassword"] = True;';
	}
	
	
	$f[]='';
	if($phpmyadmin_config_add<>null){$f[]=$phpmyadmin_config_add;}
	$f[]='/* End of servers configuration */';
	$f[]='';
	$f[]='$cfg["blowfish_secret"] = "4bf112360c9db0.66618545";';
	$f[]='$cfg["DefaultLang"] = "en-utf-8";';
	$f[]='$cfg["ServerDefault"] = 1;';
	$f[]='$cfg["UploadDir"] = "";';
	$f[]='$cfg["SaveDir"] = "";';
	$f[]='?>';
	@file_put_contents('/usr/share/phpmyadmin/config.inc.php',@implode("\n", $f));

	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Artica Web service PhpMyAdmin: Success writing phpmyadmin configuration\n";}
	IF(is_dir('/usr/share/phpmyadmin/setup')){shell_exec('/bin/rm -rf /usr/share/phpmyadmin/setup');}
	IF(is_dir('/usr/share/phpmyadmin/config')){shell_exec('/bin/rm -rf /usr/share/phpmyadmin/config');}
	
}