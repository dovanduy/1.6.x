<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["RELOAD"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;
$GLOBALS["debug"]=true;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}




$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.nginx.inc');
include_once(dirname(__FILE__).'/ressources/class.freeweb.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.reverse.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.resolv.conf.inc');



	$GLOBALS["ARGVS"]=implode(" ",$argv);
	if($argv[1]=="--reconfigure"){$GLOBALS["OUTPUT"]=true;configure_single_website($argv[2]);die();}
	if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
	if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
	if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
	if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();die();}
	if($argv[1]=="--force-restart"){$GLOBALS["OUTPUT"]=true;force_restart();die();}
	if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;$GLOBALS["RECONFIGURE"]=true;build();die();}
	if($argv[1]=="--artica-web"){articaweb();exit;}
	if($argv[1]=="--install-nginx"){install_nginx();exit;}
	if($argv[1]=="--status"){status();exit;}
	if($argv[1]=="--rotate"){rotate();exit;}
	if($argv[1]=="--awstats"){awstats();exit;}
	if($argv[1]=="--caches-status"){caches_status();exit;}
	if($argv[1]=="--framework"){framework();exit;}
	if($argv[1]=="--tests-sources"){test_sources();exit;}
	if($argv[1]=="--reconfigure-all"){$GLOBALS["OUTPUT"]=true;build_localhosts();exit;}
	if($argv[1]=="--authenticator"){$GLOBALS["OUTPUT"]=true;authenticator(true);exit;}
	if($argv[1]=="--purge-cache"){$GLOBALS["OUTPUT"]=true;purge_cache($argv[2]);exit;}
	if($argv[1]=="--purge-all-caches"){$GLOBALS["OUTPUT"]=true;purge_all_caches();exit;}
	
	
	
	
	if($argv[1]=="--build-default"){$GLOBALS["OUTPUT"]=true;$GLOBALS["RELOAD"]=true;build_default();exit;}
	
	echo "Unable to understand this command\n";
	echo "Should be:\n";
	echo "--framework...........: Build framework\n";
	echo "--caches-status.......: Build caches status\n";
	echo "--build-default.......: Build default website\n";

function build($OnlySingle=false){
	if(isset($GLOBALS[__FILE__.__FUNCTION__])){return;}
	$GLOBALS[__FILE__.__FUNCTION__]=true;
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	
	shell_exec("/etc/init.d/mysql start");
	
	if($unix->SQUID_GET_LISTEN_PORT()==80){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Squid listen 80, ports conflicts, change it\n";}
		shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --build --force");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Restarting Squid-cache..\n";}
		shell_exec("/etc/init.d/squid restart --script=".basename(__FILE__));
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: done...\n";}
	}
	
	if($unix->SQUID_GET_LISTEN_SSL_PORT()==443){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Squid listen 443, ports conflicts, change it\n";}
		shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --build --force");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Restarting Squid-cache..\n";}
		shell_exec("/etc/init.d/squid restart --script=".basename(__FILE__));	
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: done...\n";}
	}
	
	$reconfigured=false;
	if($unix->APACHE_GET_LISTEN_PORT()==80){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Apache listen 80, ports conflicts, change it\n";}
		shell_exec("$php5 /usr/share/artica-postfix/exec.freeweb.php --build --force");
		shell_exec("$php5 /usr/share/artica-postfix/exec.freeweb.php --stop --force");
		shell_exec("$php5 /usr/share/artica-postfix/exec.freeweb.php --start --force");
		$reconfigured=true;
	}
	
	if(!$reconfigured){
		if($unix->APACHE_GET_LISTEN_PORT()==443){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Apache listen 443, ports conflicts, change it\n";}
			shell_exec("$php5 /usr/share/artica-postfix/exec.freeweb.php --build --force");
		}	
	}
	
	$APACHE_USER=$unix->APACHE_SRC_ACCOUNT();
	$APACHE_SRC_GROUP=$unix->APACHE_SRC_GROUP();
	$NginxProxyStorePath="/home/nginx";
	@mkdir("/etc/nginx/sites-enabled",0755,true);
	@mkdir($NginxProxyStorePath,0755,true);
	@mkdir($NginxProxyStorePath."/tmp",0755,true);
	@mkdir($NginxProxyStorePath."/disk",0755,true);
	@mkdir("/var/lib/nginx/fastcgi",0755,true);
	@mkdir("/home/nginx/tmp",0755,true);
	
	$Tempdir=$unix->TEMP_DIR()."/nginx";
	@mkdir($Tempdir,0755,true);
	$unix->chown_func($APACHE_USER,$APACHE_SRC_GROUP, $NginxProxyStorePath);
	$unix->chown_func($APACHE_USER,$APACHE_SRC_GROUP, "/etc/nginx/sites-enabled");
	$unix->chown_func($APACHE_USER,$APACHE_SRC_GROUP, $NginxProxyStorePath."/tmp");
	$unix->chown_func($APACHE_USER,$APACHE_SRC_GROUP, $NginxProxyStorePath."/disk");
	$unix->chown_func($APACHE_USER,$APACHE_SRC_GROUP, "/var/lib/nginx/fastcgi");
	$unix->chown_func($APACHE_USER,$APACHE_SRC_GROUP, $Tempdir);
	nginx_ulimit();
	$workers=$unix->CPU_NUMBER();

	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Running $APACHE_USER:$APACHE_SRC_GROUP..\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Running $workers worker(s)..\n";}
	

	
	if(is_file("/etc/nginx/sites-enabled/default")){@unlink("/etc/nginx/sites-enabled/default");}
	if(is_link("/etc/nginx/sites-enabled/default")){@unlink("/etc/nginx/sites-enabled/default");}
	if(is_link("/etc/nginx/conf.d/example_ssl.conf")){@unlink("/etc/nginx/conf.d/example_ssl.conf");}
	
	
	
	$limit=4096*$workers;
	if($limit>65535){$limit=65535;}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Running limit of $limit open files\n";}
	
	$L=explode("\n",@file_get_contents("/etc/security/limits.conf"));
	$FOUNDL=false;
	$T=array();
	while (list ($index, $line) = each ($L)){
		$line=trim($line);
		if(trim($line)==null){continue;}
		if(substr($line, 0,1)=="#"){continue;}
		if(preg_match("#^$APACHE_USER#", $line)){continue;}
		$T[]=$line;
	}
	
	if(!$FOUNDL){
		$T[]="$APACHE_USER       soft    nofile   $limit";
		$T[]="$APACHE_USER       hard    nofile   $limit";
	}
	
	@file_put_contents("/etc/security/limits.conf", @implode("\n", $T)."\n");
	$L=array();
	$T=array();
	
	
	$f[]="user   $APACHE_USER;";
	$f[]="worker_processes  $workers;";
	$f[]="worker_rlimit_nofile 2048;";
	$f[]="timer_resolution 1ms;";
	$f[]="";
	$f[]="error_log  /var/log/nginx/error.log warn;";
	$f[]="pid        /var/run/nginx.pid;";
	$f[]="";
	$f[]="";
	$f[]="events {";
	$f[]="    worker_connections  4096;";
	$f[]="    multi_accept  on;";
	$f[]="    use epoll;";
	$f[]="	  accept_mutex_delay 1ms;";
	$f[]="}";
	
	$upstream=new nginx_upstream();
	$upstreams_servers=$upstream->build();
	
	
	

	$f[]="";
	$f[]="";
	$f[]="http {";
	$f[]="\tinclude /etc/nginx/mime.types;";
	$f[]="\tlimit_conn_zone \$binary_remote_addr zone=LimitCnx:10m;";
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL("SELECT LimitReqs,servername FROM reverse_www WHERE LimitReqs > 0");
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$servername=$ligne["servername"];
		$ZoneName=str_replace(".", "", $servername);
		$ZoneName=str_replace("-", "", $servername);
		$ZoneName=str_replace("_", "", $servername);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, limit $servername/$servername {$ligne["LimitReqs"]}r/s\n";}
		$f[]="\tlimit_req_zone  \$binary_remote_addr  zone=$ZoneName:10m   rate={$ligne["LimitReqs"]}r/s;";
	}
	
	$nginxClass=new nginx();
	if($nginxClass->IsSubstitutions()){
		$f[]="\tsubs_filter_types text/html text/css text/xml;";
	}
	
	
	@mkdir($Tempdir,0775,true);
	$f[]="\tlimit_conn_log_level info;";
	$f[]="\tclient_body_temp_path $Tempdir 1 2;";
	$f[]="\tclient_header_timeout 5s;";
	$f[]="\tclient_body_timeout 5s;";
	$f[]="\tsend_timeout 10m;";
	$f[]="\tconnection_pool_size 128k;";
	$f[]="\tclient_header_buffer_size 16k;";
	$f[]="\tlarge_client_header_buffers 1024 128k;";
	$f[]="\trequest_pool_size 128k;";
	$f[]="\tkeepalive_requests 1000;";
	$f[]="\tkeepalive_timeout 10;";
	$f[]="\tclient_max_body_size 10g;";
	$f[]="\tclient_body_buffer_size 1m;";
	$f[]="\tclient_body_in_single_buffer on;";
	$f[]="\topen_file_cache max=10000 inactive=300s;";
	$f[]="\treset_timedout_connection on;";
	$f[]="\ttypes_hash_max_size 8192;";
	$f[]="\tserver_names_hash_bucket_size 128;";
	$f[]="\tserver_names_hash_max_size 512;";
	$f[]="\tvariables_hash_max_size 512;";
	$f[]="\tvariables_hash_bucket_size 128;";
	
	$f[]="\tfastcgi_buffers 8 16k;";
	$f[]="\tfastcgi_buffer_size 32k;";
	$f[]="\tfastcgi_connect_timeout 300;";
	$f[]="\tfastcgi_send_timeout 300;";
	$f[]="\tfastcgi_read_timeout 300;";
	
	$f[]="map \$scheme \$server_https {";
	$f[]="default off;";
	$f[]="https on;";
	$f[]="}	";	

	$f[]="\tgzip on;";
	$f[]="\tgzip_disable msie6;";
	$f[]="\tgzip_static on;";
	$f[]="\tgzip_min_length 1100;";
	$f[]="\tgzip_buffers 16 8k;";
	$f[]="\tgzip_comp_level 9;";
	$f[]="\tgzip_types text/plain text/css application/json application/x-javascript text/xml application/xml application/xml+rss text/javascript;";
	$f[]="\tgzip_vary on;";
	$f[]="\tgzip_proxied any;";
	
	$f[]="\toutput_buffers 1000 128k;";
	$f[]="\tpostpone_output 1460;";
	$f[]="\tsendfile on;";
	$f[]="\tsendfile_max_chunk 256k;";
	$f[]="\ttcp_nopush on;";
	$f[]="\ttcp_nodelay on;";
	$f[]="\tserver_tokens off;";
	
	$dns=new resolv_conf();
	$sock=new sockets();
	
	if($sock->dnsmasq_enabled()){
		$resolver[]="127.0.0.1";
	}
	
	if($dns->MainArray["DNS1"]<>null){$resolver[]=$dns->MainArray["DNS1"];}
	if($dns->MainArray["DNS2"]<>null){$resolver[]=$dns->MainArray["DNS2"];}
	if($dns->MainArray["DNS3"]<>null){$resolver[]=$dns->MainArray["DNS3"];}
;
	
	$f[]="\tresolver ". @implode(" ", $resolver).";";
	$f[]="\tignore_invalid_headers on;";
	$f[]="\tindex index.html;";
	$f[]="\tadd_header X-CDN \"Served by myself\";";
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM nginx_caches  ORDER BY directory";
	$results=$q->QUERY_SQL($sql);
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$directory=$ligne["directory"];
		@mkdir($directory,0755,true);
		$unix->chown_func("www-data","www-data", $directory);
		$f[]="\tproxy_cache_path $directory levels={$ligne["levels"]} keys_zone={$ligne["keys_zone"]}:{$ligne["keys_zone_size"]}m max_size={$ligne["max_size"]}G  inactive={$ligne["inactive"]} loader_files={$ligne["loader_files"]} loader_sleep={$ligne["loader_sleep"]} loader_threshold={$ligne["loader_threshold"]};";
		
		
	}
		
	
	
	$f[]="\tproxy_temp_path $NginxProxyStorePath/tmp/ 1 2;";
	$f[]="\tproxy_cache_valid 404 10m;";
	$f[]="\tproxy_cache_valid 400 501 502 503 504 1m;";
	$f[]="\tproxy_cache_valid any 4320m;";
	$f[]="\tproxy_cache_use_stale updating invalid_header error timeout http_404 http_500 http_502 http_503 http_504;";
	$f[]="\tproxy_next_upstream error timeout invalid_header http_404 http_500 http_502 http_503 http_504;";
	$f[]="\tproxy_redirect off;";
	$f[]="\tproxy_set_header Host \$http_host;";
	$f[]="\tproxy_set_header Server Apache;";
	$f[]="\tproxy_set_header Connection Close;";
	$f[]="\tproxy_set_header X-Real-IP \$remote_addr;";
	$f[]="\tproxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;";
	$f[]="\tproxy_pass_header Set-Cookie;";
	$f[]="\tproxy_pass_header User-Agent;";
	$f[]="\tproxy_set_header X-Accel-Buffering on;";
	$f[]="\tproxy_hide_header X-CDN;";
	$f[]="\tproxy_hide_header X-Server;";
	$f[]="\tproxy_intercept_errors off;";
	$f[]="\tproxy_ignore_client_abort on;";
	$f[]="\tproxy_connect_timeout 60s;";
	$f[]="\tproxy_send_timeout 60s;";
	$f[]="\tproxy_read_timeout 150s;";
	$f[]="\tproxy_buffer_size 128k;";
	$f[]="\tproxy_buffers 16384 128k;";
	$f[]="\tproxy_busy_buffers_size 256k;";
	$f[]="\tproxy_temp_file_write_size 128k;";
	$f[]="\tproxy_headers_hash_bucket_size 128;";
	$f[]="\tproxy_cache_min_uses 0;";
	$f[]="";
	$f[]="$upstreams_servers";
	
	$f[]="\tlog_format  aws_log";
	$f[]="\t\t'\$remote_addr - \$remote_user [\$time_local] \$request '";
	$f[]="\t\t'\"\$status\" \$body_bytes_sent \"\$http_referer\" '";
	$f[]="\t\t'\"\$http_user_agent\" \"\$http_x_forwarded_for\"';";
	$f[]="";	
	
	$f[]="\tinclude /etc/nginx/conf.d/*.conf;";
	$f[]="\tinclude /etc/nginx/sites-enabled/*.conf;";
	$f[]="\t}";
	$f[]="";	
	@copy("/etc/nginx/nginx.conf","/etc/nginx/nginx.bak");
	@file_put_contents("/etc/nginx/nginx.conf", @implode("\n", $f));
	if(!$OnlySingle){
		build_default(true);
		build_localhosts();
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, Only single defined\n";}
	}
	
	if($GLOBALS["RECONFIGURE"]){
		$pid=PID_NUM();
		if(is_numeric($pid)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, reload pid $pid\n";}
			$kill=$unix->find_program("kill");
			shell_exec("$kill -HUP $pid");
		}else{
			start(true);
		}
	}
	
}

function configure_single_freeweb($servername){
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * from freeweb WHERE servername='$servername'","artica_backup"));
	$free=new freeweb($servername);
	$NginxFrontEnd=$free->NginxFrontEnd;	
	$groupware=$free->groupware;
	$NOPROXY["SARG"]=true;
	$NOPROXY["ARTICA_MINIADM"]=true;
	
	$q2=new mysql_squid_builder();
	$ligne2=mysql_fetch_array($q2->QUERY_SQL("SELECT cacheid FROM reverse_www WHERE servername='{$ligne["servername"]}'"));
	
	
	$host=new nginx($servername);

	
	if(isset($NOPROXY[$groupware])){
		$free->CheckWorkingDirectory();
		$host->set_proxy_disabled();
		$host->set_DocumentRoot($free->WORKING_DIRECTORY);
		if($groupware=="SARG"){$host->SargDir();}
	}else{
		$host->set_freeweb();
		$host->set_storeid($ligne2["cacheid"]);
		$host->set_proxy_destination("127.0.0.1");
	}
	if($free->groupware=="Z-PUSH"){$host->NoErrorPages=true;}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $servername FreeWeb SSL:{$ligne["useSSL"]}\n";}
	if($ligne["useSSL"]==0){
		if(!isset($NOPROXY[$groupware])){
			$host->set_proxy_port(82);
		}
	}

	
	
	if($ligne["useSSL"]==1){
		$host->set_ssl();
		$host->set_ssl_certificate($ligne["sslcertificate"]);
		
		if(!isset($NOPROXY[$groupware])){
			$host->set_proxy_port(447);
			
		}
	}
	

	$host->set_servers_aliases($free->Params["ServerAlias"]);
	
	if($groupware=="ZARAFA"){
		if($free->NginxFrontEnd==1){
			$host->groupware_zarafa_Frontend();
			configure_single_website_rebuild();
			configure_single_website_reload();
			return;
		}
	}
	
	$host->build_proxy();
	configure_single_website_rebuild();
	configure_single_website_reload();
	
}


function configure_single_website_rebuild(){
	LoadConfigs();
	build(true);
}


function configure_single_website($servername){
	$unix=new unix();
	$sock=new sockets();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx Already Artica task running PID $oldpid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}	
	if($EnableFreeWeb==1){
		$q=new mysql();
		$sql="SELECT servername from freeweb WHERE servername='$servername'";
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if($ligne["servername"]<>null){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $servername is a freeweb\n";}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, *** NOTICE *** $servername is a freeweb\n";}
			configure_single_freeweb($servername);
			return;
		}
	}
	
	
	$q=new mysql_squid_builder();

	$sql="SELECT * FROM `reverse_www` WHERE `enabled`=1 AND servername='$servername'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx,[".__LINE__."] $servername $q->mysql_error\n";}return;}
	configure_single_website_rebuild();
	BuildReverse($ligne,true);
	configure_single_website_reload();
	
	
}
function configure_single_website_reload(){
	$unix=new unix();
	$pid=PID_NUM();
	if(is_numeric($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, reload pid $pid\n";}
		$nginx=$unix->find_program("nginx");
		shell_exec("$nginx -c /etc/nginx/nginx.conf -s reload >/dev/null 2>&1");
		if($unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, Success service reloaded pid:$pid...\n";}
		}
	
	
	}else{
		start(true);
	}

}


function LoadConfigs(){
	if(isset($GLOBALS["LoadConfigs"])){return;}
	$GLOBALS["REMOVE_LOCAL_ADDR"]=false;
	$unix=new unix();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(*) as tcount FROM reverse_www WHERE default_server=0"));
	if(!$q->ok){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, *** FATAL ** $q->mysql_error\n";}return;}
	if($ligne["tcount"]>0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx *** NOTICE *** Defaults websites as been defined, no IP addresses are allowed\n";}
		$EnableArticaFrontEndToNGninx=0;$GLOBALS["REMOVE_LOCAL_ADDR"]=true;
	}
	
	if($GLOBALS["REMOVE_LOCAL_ADDR"]){$GLOBALS["IPADDRS"]=$unix->NETWORK_ALL_INTERFACES(true);unset($GLOBALS["IPADDRS"]["127.0.0.1"]);}
	$GLOBALS["LoadConfigs"]=true;	
}


function authenticator($alone=false){
	
	if($alone){
		$unix=new unix();
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx Already Artica task running PID $oldpid since {$time}mn\n";}
			return;
		}
	}	
	@file_put_contents($pidfile, getmypid());
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, Authenticate port /var/run/nginx-authenticator.sock\n";}
	$host=new nginx("unix:/var/run/nginx-authenticator.sock");
	$host->set_proxy_disabled();
	$host->set_DocumentRoot("/usr/share/artica-postfix");
	$host->set_index_file("authenticator.php");
	$host->build_proxy();
	if($alone){
		stop(true);
		start(true);
	}
	
}

function build_localhosts(){
	if($GLOBALS["VERBOSE"]){echo "\n############################################################\n\n".__FUNCTION__.".".__LINE__.":Start...\n";}
	$squidR=new squidbee();
	$rev=new squid_reverse();
	$sock=new sockets();
	$unix=new unix();
	LoadConfigs();
	$EnableArticaFrontEndToNGninx=$sock->GET_INFO("EnableArticaFrontEndToNGninx");
	$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
	if(!is_numeric($EnableArticaFrontEndToNGninx)){$EnableArticaFrontEndToNGninx=0;}
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}
	
	
	$NginxAuthPort=$sock->GET_INFO("NginxAuthPort");
	if($NginxAuthPort==null){
		$NginxAuthPort="unix:/var/run/nginx-authenticator.sock";
		$sock->SET_INFO("NginxAuthPort",$NginxAuthPort);	
	}
	
	
	

	
	if($EnableArticaFrontEndToNGninx==1){
		
		shell_exec("/etc/init.d/artica-webconsole stop >/dev/null 2>&1 &");
		$ArticaHttpsPort=$sock->GET_INFO("ArticaHttpsPort");
		$ArticaHttpUseSSL=$sock->GET_INFO("ArticaHttpUseSSL");
		if(!is_numeric($ArticaHttpUseSSL)){$ArticaHttpUseSSL=1;}
		if(!is_numeric($ArticaHttpsPort)){$ArticaHttpsPort=9000;}
		$LighttpdArticaListenIP=$sock->GET_INFO('LighttpdArticaListenIP');
		$host=new nginx($ArticaHttpsPort);
		$host->set_ssl();
		$host->set_listen_ip($LighttpdArticaListenIP);
		$host->set_proxy_disabled();
		$host->set_DocumentRoot("/usr/share/artica-postfix");
		$host->set_index_file("admin.index.php");
		$host->build_proxy();
	}
	
	$q=new mysql();
	
	$sql="SELECT * FROM freeweb WHERE `enabled`=1";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	$q2=new mysql_squid_builder();
	if(!$q->ok){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, Fatal $q->mysql_error\n";}
		return;
	}
	
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [DEBUG]: $sql -> ".mysql_num_rows($results)." items\n";}
	
	foreach (glob("/etc/nginx/sites-enabled-backuped/*") as $filename) {@unlink($filename);}
	
	@mkdir("/etc/nginx/sites-enabled-backuped",0755,true);
	
	$q=new mysql_squid_builder();
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, scanning /etc/nginx/sites-enabled\n";}
	
	foreach (glob("/etc/nginx/sites-enabled/*") as $filename) {
		
		$file=basename($filename);
		if(is_numeric($file)){@unlink($filename);continue;}
		$filedetect=$file;
		if(!preg_match("#^freewebs-#", $file,$re)){continue;}
		if(preg_match("#_default_#", $file)){@unlink($filename);continue;}
			
		
		$filedetect=str_replace("freewebs-ssl-", "", $filedetect);
		$filedetect=str_replace("freewebs-", "", $filedetect);
		$filedetect=str_replace("freewebs-unix-", "", $filedetect);
		
		
		if(preg_match("#(.+?)\.[0-9]+\.conf$#",$filedetect,$re)){
			$sitename=$re[1];
			$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT DenyConf FROM reverse_www WHERE servername='$sitename'"));
			if($ligne["DenyConf"]==1){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, [$sitename], locked\n";}
				continue;
			}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, [$sitename] backup/remove $filename\n";}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, backup \"$file\"\n";}
			@copy($filename, "/etc/nginx/sites-enabled-backuped/$file");
			@unlink($filename);
			continue;
		}
			
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, backup \"$file\"\n";}
		@copy($filename, "/etc/nginx/sites-enabled-backuped/$file");
		@unlink($filename);
	}
	

	$NOPROXY["SARG"]=true;
	$NOPROXY["ARTICA_MINIADM"]=true;
	
	
	
	$CountDeserver=mysql_num_rows($results);
	$c=0;
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$c++;
		if($EnableFreeWeb==0){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx,[".__LINE__."] ************* {$ligne["servername"]} FreeWeb is disabled ************* \n";}
			continue;
		}
		$DenyConf=$ligne["DenyConf"];
		$groupware=$ligne["groupware"];
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx,[".__LINE__."] $c/$CountDeserver\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx,[".__LINE__."] ************* {$ligne["servername"]} / $DenyConf / $groupware /{$ligne["UseSSL"]} ************* \n";}
		
		
		$ligne["servername"]=trim($ligne["servername"]);
		
		if($ligne["servername"]=="_default_"){continue;}
		
		if($GLOBALS["REMOVE_LOCAL_ADDR"]){
			if(isset($IPADDRS[$ligne["servername"]])){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx,[".__LINE__."] {$ligne["servername"]} *** SKIPPED ***\n";}
				continue;
			}
		}
		
		
		
		
		if($DenyConf==1){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx,[".__LINE__."] Local web site `{$ligne["servername"]}`, DenyConf = 1,skipped\n";}
			continue;
		}
		
		if($GLOBALS["VERBOSE"]){echo "\n\n********************************************\n".__FUNCTION__.".".__LINE__.":Start...\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx,[".__LINE__."] Local web site `{$ligne["servername"]}`, continue\n";}
		$ALREADYSET[$ligne["servername"]]=true;
		$ligne2=mysql_fetch_array($q2->QUERY_SQL("SELECT cacheid FROM reverse_www WHERE servername='{$ligne["servername"]}'"));
		
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx,[".__LINE__."] Local web site `{$ligne["servername"]}` Groupware:[$groupware]; SSL:{$ligne["UseSSL"]}\n";}
		
		$free=new freeweb($ligne["servername"]);
		$NginxFrontEnd=$free->NginxFrontEnd;
		
		
		
		if($ligne["useSSL"]==0){
			if($GLOBALS["VERBOSE"]){echo __FUNCTION__.".".__LINE__.":Start...\n";}
			$host=new nginx($ligne["servername"]);
			
			if($groupware=="ZARAFA"){
				if($free->NginxFrontEnd==1){
					$host->groupware_zarafa_Frontend();
					continue;
				}
			}
			
			
			if(isset($NOPROXY[$groupware])){
				$free->CheckWorkingDirectory();
				$host->set_proxy_disabled();
				$host->set_DocumentRoot($free->WORKING_DIRECTORY);
				if($groupware=="SARG"){$host->SargDir();}
			}else{
				$host->set_freeweb();
				$host->set_storeid($ligne2["cacheid"]);
				$host->set_proxy_destination("127.0.0.1");
				$host->set_proxy_port(82);				
				if($free->groupware=="Z-PUSH"){$host->NoErrorPages=true;}
				
			}
			

			
			if($GLOBALS["VERBOSE"]){echo __FUNCTION__.".".__LINE__.":Done...\n";}
			
			
			$host->set_servers_aliases($free->Params["ServerAlias"]);
			if($GLOBALS["VERBOSE"]){echo __FUNCTION__.".".__LINE__.":Start...\n";}
			$host->build_proxy();
			if($GLOBALS["VERBOSE"]){echo __FUNCTION__.".".__LINE__.":Done...\n";}
		}
		
	if($ligne["useSSL"]==1){
			$host=new nginx($ligne["servername"]);
			$host->set_ssl();
			$host->set_ssl_certificate($ligne["sslcertificate"]);
			$host->set_servers_aliases($free->Params["ServerAlias"]);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, Local web site `{$ligne["servername"]}` SSL / $groupware / NginxFrontEnd:$free->NginxFrontEnd\n";}
			if($groupware=="ZARAFA"){
				if($free->NginxFrontEnd==1){
			
					$host->groupware_zarafa_Frontend();
					continue;
				}
			}
			
			
			if(isset($NOPROXY[$groupware])){
				$free->CheckWorkingDirectory();
				$host->set_proxy_disabled();
				$host->set_DocumentRoot($free->WORKING_DIRECTORY);
				if($groupware=="SARG"){$host->SargDir();}	
				
			}else{
				$host->set_freeweb();
				$host->set_storeid($ligne2["storeid"]);
				$host->set_proxy_destination("127.0.0.1");
				$host->set_proxy_port(447);
				if($free->groupware=="Z-PUSH"){$host->NoErrorPages=true;}
			}
			
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx,[".__LINE__."]  protect local SSL web site `{$ligne["servername"]}`\n";}
			$host->build_proxy();
			
		}
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx,[".__LINE__."] ******************************************\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx,[".__LINE__."] \n";}
		
	}
	
	
// *********************************************************************************************************************************************************
	$sql="SELECT * FROM `reverse_www` WHERE `enabled`=1";
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL($sql);
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [DEBUG]: $sql -> ".mysql_num_rows($results)." items\n";}
	if(!$q->ok){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx,[".__LINE__."]  $q->mysql_error\n";}return;}
	$countDeServer=mysql_num_rows($results);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx,[".__LINE__."]  $countDeServer remote websites to protect\n";}
	
	$c=0;
	while ($ligne = mysql_fetch_assoc($results)) {
			if($ligne["servername"]=="_default_"){continue;}
			$c++;
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx ****************************************************\n";}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx,[".__LINE__."] $c/$countDeServer BuildReverse({$ligne["servername"]})\n";}
			BuildReverse($ligne);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx ****************************************************\n";}
	}
			
			
	if($EnableArticaFrontEndToNGninx==1){
		$sock=new sockets();
		$SargOutputDir=$sock->GET_INFO("SargOutputDir");
		if($SargOutputDir==null){$SargOutputDir="/var/www/html/squid-reports";}
		
		if(!is_dir($SargOutputDir)){@mkdir($SargOutputDir,0755,true);}
		if(!is_file("$SargOutputDir/logo.gif")){@copy("/usr/share/artica-postfix/css/images/logo.gif", "$SargOutputDir/logo.gif");}
		if(!is_file("$SargOutputDir/pattern.png")){@copy("/usr/share/artica-postfix/css/images/pattern.png", "$SargOutputDir/pattern.png");}
		$phpfpm=$unix->APACHE_LOCATE_PHP_FPM();
		$EnablePHPFPM=$sock->GET_INFO("EnablePHPFPM");
		$EnableArticaApachePHPFPM=$sock->GET_INFO("EnableArticaApachePHPFPM");
		if(!is_numeric($EnableArticaApachePHPFPM)){$EnableArticaApachePHPFPM=0;}
		if($EnableArticaApachePHPFPM==0){$EnablePHPFPM=0;}
		
		
		$EnableSargGenerator=$sock->GET_INFO("EnableSargGenerator");
		if(!is_numeric($EnableSargGenerator)){$EnableSargGenerator=1;}
		if(!is_numeric($EnablePHPFPM)){$EnablePHPFPM=0;}
		if(!is_file($phpfpm)){$EnablePHPFPM=0;}
		if($EnablePHPFPM==1){
			ToSyslog("Restarting PHP5-FPM");
			shell_exec("/etc/init.d/php5-fpm restart >/dev/null 2>&1");
		}
		
		$host=new nginx(9000);
		$host->set_ssl();
		$host->set_proxy_disabled();
		$host->set_DocumentRoot("/usr/share/artica-postfix");
		
		
		$host->set_index_file("admin.index.php");
		if($EnableSargGenerator==1){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, SARG is enabled...\n";}
			$host->SargDir();}
		$host->build_proxy();
		
		$lighttpdbin=$unix->find_program("lighttpd");
		if(!is_file($lighttpdbin)){
			if(is_file("/etc/php5/fpm/pool.d/framework.conf")){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, building framework...\n";}
				$host=new nginx(47980);
				$host->set_proxy_disabled();
				$host->set_DocumentRoot("/usr/share/artica-postfix/framework");
				$host->set_framework();
				$host->set_listen_ip("127.0.0.1");
				$host->set_servers_aliases(array("127.0.0.1"));
				$host->build_proxy();
			}
		}
	}


	authenticator();
	
	
	$host=new nginx();
	if(!$host->TestTheWholeConfig()){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, testing configuration failed, return to old config...\n";}
		@copy("/etc/nginx/nginx.bak","/etc/nginx/nginx.conf");
		foreach (glob("/etc/nginx/sites-enabled/*") as $filename) {
			$file=basename($filename);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, remove new $file file\n";}
			if(preg_match("#^freewebs-#", $file)){@unlink($filename);}
		}
		
		foreach (glob("/etc/nginx/sites-enabled-backuped/*") as $filename) {
			$file=basename($filename);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, restore old $file file\n";}
			@copy($filename, "/etc/nginx/sites-enabled/$file");
		}
		
	}
	
	
	if($GLOBALS["VERBOSE"]){echo "\n##################### - - END - - ##############################\n\n".__FUNCTION__.".".__LINE__.":Start...\n";}	
}

function BuildReverse($ligne,$backupBefore=false){
	$q=new mysql_squid_builder();
	$ligne["servername"]=trim($ligne["servername"]);
	$IPADDRS=$GLOBALS["IPADDRS"];
	$DenyConf=$ligne["DenyConf"];
	$ligne["servername"]=trim($ligne["servername"]);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx,[".__LINE__."]  ************* {$ligne["servername"]}:{$ligne["port"]} / $DenyConf ************* \n";}
		
	if($ligne["port"]==82){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx,[".__LINE__."] 82 port is an apache port, SKIP\n";}
		return;
	}
	
	if($GLOBALS["REMOVE_LOCAL_ADDR"]){
		if(isset($IPADDRS[$ligne["servername"]])){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx,[".__LINE__."]  {$ligne["servername"]} *** SKIPPED ***\n";}
			continue;
		}
	}
		
		
	if($DenyConf==1){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx,[".__LINE__."]  Local web site `{$ligne["servername"]}`, DenyConf = 1,skipped\n";}
		continue;
	}
		
	if(isset($ALREADYSET[$ligne["servername"]])){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx,[".__LINE__."]  `{$ligne["servername"]}` Already defined, abort\n";}
		continue;
	}
	$ListenPort=$ligne["port"];
	$SSL=$ligne["ssl"];
		
	$certificate=$ligne["certificate"];
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, protect remote web site `{$ligne["servername"]}:$ListenPort [SSL:$SSL]`\n";}
	if($ligne["servername"]==null){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, skip it...\n";}
		continue;
	}
	$cache_peer_id=$ligne["cache_peer_id"];
	if($cache_peer_id>0){
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM `reverse_sources` WHERE `ID`='$cache_peer_id'"));
	}
		
	$host=new nginx($ligne["servername"]);
		
	if($ListenPort==80 && $SSL==1){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, HTTP/HTTPS Enabled...\n";}
		$host->set_RedirectQueries($ligne["RedirectQueries"]);
		$host->set_forceddomain($ligne2["forceddomain"]);
		$host->set_proxy_destination($ligne2["ipaddr"]);
		$host->set_ssl(0);
		$host->set_proxy_port($ligne2["port"]);
		$host->set_listen_port(80);
		$host->set_poolid($ligne["poolid"]);
		$host->set_owa($ligne["owa"]);
		$host->set_storeid($ligne["cacheid"]);
		$host->set_cache_peer_id($cache_peer_id);
		$host->BackupBefore=$backupBefore;
		$host->build_proxy();
	
		$host=new nginx($ligne["servername"]);
		$host->set_ssl_certificate($certificate);
		$host->set_ssl_certificate($ligne2["ssl_commname"]);
		$host->set_forceddomain($ligne2["forceddomain"]);
		$host->set_proxy_destination($ligne2["ipaddr"]);
		$host->BackupBefore=$backupBefore;
		$host->set_ssl(1);
		$host->set_proxy_port($ligne2["port"]);
		$host->set_listen_port(443);
		$host->set_poolid($ligne["poolid"]);
		$host->set_owa($ligne["owa"]);
		$host->set_storeid($ligne["cacheid"]);
		$host->set_cache_peer_id($cache_peer_id);
		$host->build_proxy();
		
	}
		
	if($ligne["ssl"]==1){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, SSL Enabled...\n";}
		$ligne2["ssl"]=1;
	}
		
	if($ligne["port"]==443){
		$ligne2["ssl"]=1;
	}
	$host->BackupBefore=$backupBefore;
	$host->set_RedirectQueries($ligne["RedirectQueries"]);
	$host->set_ssl_certificate($certificate);
	$host->set_ssl_certificate($ligne2["ssl_commname"]);
	$host->set_forceddomain($ligne2["forceddomain"]);
	$host->set_proxy_destination($ligne2["ipaddr"]);
	$host->set_ssl($ligne2["ssl"]);
	$host->set_proxy_port($ligne2["port"]);
	$host->set_listen_port($ligne["port"]);
	$host->set_poolid($ligne["poolid"]);
	$host->set_owa($ligne["owa"]);
	$host->set_storeid($ligne["cacheid"]);
	$host->set_cache_peer_id($cache_peer_id);
	$host->build_proxy();	
	
}


function ToSyslog($text){

	$LOG_SEV=LOG_INFO;
	if(function_exists("openlog")){openlog(basename(__FILE__), LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
	if(function_exists("closelog")){closelog();}
}

function rotate(){
	$unix=new unix();
	
	
	
	$pidTime="/etc/artica-postfix/pids/". basename(__FILE__).".".__FUNCTION__.".time";
	if($unix->file_time_min($pidTime)<55){return;}
	@unlink($pidTime);
	@file_put_contents($pidTime, time());	
	
	$sock=new sockets();
	$kill=$unix->find_program("kill");
	$NginxWorkLogsDir=$sock->GET_INFO("NginxWorkLogsDir");
	if($NginxWorkLogsDir==null){$NginxWorkLogsDir="/home/nginx/logsWork";}
	
	@mkdir("$NginxWorkLogsDir",0755,true);
	$directories=$unix->dirdir("/var/log/apache2");
	while (list ($directory, $line) = each ($directories)){
		$sitename=basename($directory);
		$date=date("Y-m-d-H");
		$nginx_source_logs="$directory/nginx.access.log";
		$nginx_dest_logs="$NginxWorkLogsDir/$sitename-$date.log";
		if(is_file("$nginx_dest_logs")){
			echo "$nginx_dest_logs no such file\n";
			continue;}
		if(!is_file($nginx_source_logs)){continue;}
		if(!@copy($nginx_source_logs, $nginx_dest_logs)){
			echo "Failed to copy $nginx_dest_logs\n";
			continue;
		}
		
		@unlink($nginx_source_logs);
		
	}
	
	$pid=PID_NUM();
	shell_exec("$kill -USR1 $pid");
	
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$sock=new sockets();
	
	
	$EnableNginxStats=$sock->GET_INFO("EnableNginxStats");
	if(!is_numeric($EnableNginxStats)){$EnableNginxStats=0;}
	
	if($EnableNginxStats==0){
		shell_exec("$nohup $php5 ".__FILE__." --awstats >/dev/null 2>&1 &");
		return;
	}else{
		shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.nginx-stats.php --parse >/dev/null 2>&1 &");	
	}
	
	
	
}


function build_default($aspid=false){
	
	$sock=new sockets();
	$unix=new unix();
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, Already Artica task running PID $oldpid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$unix=new unix();
	$hostname=$unix->hostname_g();
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Hostname $hostname\n";}
	$EnableArticaInNGINX=$sock->GET_INFO("EnableArticaInNGINX");
	$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
	
	@unlink("/etc/nginx/conf.d/default.conf");
	
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}
	if(!is_numeric($EnableArticaInNGINX)){$EnableArticaInNGINX=0;}
	
	if($EnableArticaInNGINX==1){
		build_default_asArtica();
		return;
	}
	
	if($EnableFreeWeb==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Hostname $hostname FreeWeb is disabled\n";}
		return;
	}
	
	
	$f[]="server {";
	$f[]="\tlisten       80;";
	$f[]="\tserver_name  ".$unix->hostname_g().";";
	$f[]="\tproxy_cache_key \$scheme://\$host\$uri;";
	$f[]="\tproxy_set_header Host \$host;";
	$f[]="\tproxy_set_header	X-Forwarded-For	\$proxy_add_x_forwarded_for;";
	$f[]="\tproxy_set_header	X-Real-IP	\$remote_addr;";
	$f[]="\tlocation /nginx_status {";
	$f[]="\tstub_status on;";
	$f[]="\taccess_log   off;";
	$f[]="\tallow 127.0.0.1;";
	$f[]="\tdeny all;";
	$f[]="}";

	
	$squidR=new squidbee();
	$nginx=new nginx();
	
	$f[]="\tlocation / {";
	$f[]="\t\tproxy_pass http://127.0.0.1:82;";
	$f[]="\t}";
	$f[]="}\n";
	
	$f[]="server {";
	$f[]="\tlisten       443;";
	$f[]="\tkeepalive_timeout   70;";
	
	$f[]="\tssl on;";
	$f[]="\t".$squidR->SaveCertificate($unix->hostname_g(),false,true);
	$f[]="\tssl_session_timeout  5m;";
	$f[]="\tssl_protocols  SSLv3 TLSv1;";
	$f[]="\tssl_ciphers HIGH:!aNULL:!MD5;";
	$f[]="\tssl_prefer_server_ciphers   on;";
	$f[]="\tserver_name  ".$unix->hostname_g().";";
	$f[]="\tproxy_cache_key \$scheme://\$host\$uri;";
	$f[]="\tproxy_set_header Host \$host;";
	$f[]="\tproxy_set_header	X-Forwarded-For	\$proxy_add_x_forwarded_for;";
	$f[]="\tproxy_set_header	X-Real-IP	\$remote_addr;";
	$f[]="\tlocation /nginx_status {";
	$f[]="\tstub_status on;";
	$f[]="\taccess_log   off;";
	$f[]="\tallow 127.0.0.1;";
	$f[]="\tdeny all;";
	$f[]="}";
	
	
	
	$nginx=new nginx();
	$f[]=$nginx->webdav_containers();
	$f[]="\tlocation / {";
	$f[]="\t\tproxy_pass http://127.0.0.1:82;";
	$f[]="\t}";
	$f[]="}\n";	
	
	
	@file_put_contents("/etc/nginx/conf.d/default.conf", @implode("\n", $f));
	if($GLOBALS["RELOAD"]){reload(true);}
}



function build_default_asArtica(){
	$nginx=new nginx();
	$unix=new unix();
	$squidR=new squidbee();
	
	$f[]="server {";
	$f[]="\tlisten       80;";
	$f[]="\tserver_name  ".$unix->hostname_g().";";
	$f[]="\tindex     logon.php;";
	$f[]="\tlocation /nginx_status {";
	$f[]="\tstub_status on;";
	$f[]="\terror_log  /var/log/nginx/default.error.log warn;";
	$f[]="\taccess_log   /var/log/nginx/default.access.log;";
	$f[]="\tallow all;";
	$f[]="\t}";
	$f[]="\tlocation / {";
	$f[]="\t\troot\t/usr/share/artica-postfix;";
	$f[]="\t}";
	$f[]=$nginx->php_fpm("logon.php","/usr/share/artica-postfix",1);
	$f[]="}";
	
	$f[]="server {";
	$f[]="\tlisten       443;";
	$f[]="\tindex     logon.php;";
	$f[]="\tkeepalive_timeout   70;";
	$f[]="\terror_log  /var/log/nginx/default.error.log warn;";
	$f[]="\taccess_log   /var/log/nginx/default.access.log;";
	$f[]="\tssl on;";
	$f[]="\t".$squidR->SaveCertificate($unix->hostname_g(),false,true);
	$f[]="\tssl_session_timeout  5m;";
	$f[]="\tssl_protocols  SSLv3 TLSv1;";
	$f[]="\tssl_ciphers HIGH:!aNULL:!MD5;";
	$f[]="\tssl_prefer_server_ciphers   on;";
	$f[]="\tserver_name  ".$unix->hostname_g().";";
	$f[]="\tlocation / {";
	$f[]="\t\troot\t/usr/share/artica-postfix;";
	$f[]="\t}";
	$f[]=$nginx->php_fpm("logon.php","/usr/share/artica-postfix",1);
	$f[]="}";	
	@file_put_contents("/etc/nginx/conf.d/default.conf", @implode("\n", $f));
	if($GLOBALS["RELOAD"]){reload(true);}
}



function PID_NUM(){
	$filename=PID_PATH();
	$pid=trim(@file_get_contents($filename));
	$unix=new unix();
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF($unix->find_program("nginx"));
}

function GHOSTS_PID(){
	$unix=new unix();
	$f=array();
	$pgrep=$unix->find_program("pgrep");
	exec("$pgrep -l -f \"nginx:\s+\"",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#pgrep#", $line)){continue;}
		if(!preg_match("#^([0-9]+)\s+#", $line,$re)){continue;}
		$f[]=$re[1];
		
	}
	if(count($f)==0){return;}
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Nginx service Shutdown ". count($f)." processes...\n";}
	$kill=$unix->find_program("kill");
	while (list ($num, $pid) = each ($f)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Nginx kill PID:$pid\n";}
		shell_exec("$kill -9 $pid >/dev/null 2>&1");
		
	}
	
}

//##############################################################################
function PID_PATH(){
	return '/var/run/nginx.pid';
}
//##############################################################################
function nginx_ulimit(){
	$setup=true;
	
	$unix=new unix();
	$ulimit=$unix->find_program("ulimit");
	if(is_file($ulimit)){shell_exec("$ulimit -n 65535 >/dev/null 2>&1");}

	
	$f=explode("\n",@file_get_contents("/etc/security/limits.conf"));
	while (list ($num, $line) = each ($f)){
		if(preg_match("#^www-data\s+-\s+65535#", $line)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, ulimit true\n";}
			return;
		}
		
	}
	
	$f[]="www-data\t-\tnofile\t65535\n";
	@file_put_contents("/etc/security/limits.conf", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, ulimit setup done\n";}
	
}

function reload($aspid=false){
	$unix=new unix();
	
	$nginx=$unix->find_program("nginx");
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx Already Artica task running PID $oldpid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());	
	}
	
	$pid=PID_NUM();
	
	
	
	
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx reloading PID $pid\n";}
		$kill=$unix->find_program("kill");
		shell_exec("$nginx -c /etc/nginx/nginx.conf -s reload");
		$pid=PID_NUM();
		if($unix->process_exists($oldpid,basename(__FILE__))){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx Success reloading PID $pid\n";}
		}
		
		return;
	}
	
	$sock=new sockets();
	$EnableNginx=$sock->GET_INFO("EnableNginx");
	if(!is_numeric($EnableNginx)){$EnableNginx=1;}
	if($EnableNginx==1){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx not enabled ( see EnableNginx )\n";}
		return;
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx starting daemon\n";}
	start(true);
	
}

function force_restart(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx Already Artica task running PID $oldpid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	stop(true);
	start(true);
		
	
}

function restart(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx Already Artica task running PID $oldpid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());	
	stop(true);
	build(false);
	start(true);
	
}


function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$nginx=$unix->find_program("nginx");
	if(!is_file($nginx)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, not installed\n";}
		return;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx Already Artica task running PID $oldpid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	
	$MEMORY=$unix->MEM_TOTAL_INSTALLEE();
	if($MEMORY<624288){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx {$MEMORY}K is not enough, aborting...\n";}
		return;
	}
	
	$pid=PID_NUM();
	
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx Service already started $pid since {$timepid}Mn...\n";}
		return;
	}
	
	$EnableNginx=$sock->GET_INFO("EnableNginx");
	if(!is_numeric($EnableNginx)){$EnableNginx=1;}
	if($EnableNginx==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx service disabled\n";}
		return;
	}
	GHOSTS_PID();
	@mkdir("/var/log/nginx",0755,true);
	$nohup=$unix->find_program("nohup");
	$fuser=$unix->find_program("fuser");
	$kill=$unix->find_program("kill");
	$results=array();
	$FUSERS=array();
	
	
	exec("$fuser 80/tcp 2>&1",$results);
	while (list ($key, $line) = each ($results) ){
			if($GLOBALS["VERBOSE"]){echo "fuser: ->\"$line\"\n";}
			if(preg_match("#tcp:\s+(.+)#", $line,$re)){$FUSERS=explode(" ",$re[1]);}
	}
	
	
	
	if(count($FUSERS)>0){
		while (list ($key, $pid) = each ($FUSERS) ){
			$pid=trim($pid);
			if(!is_numeric($pid)){continue;}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: killing $pid PID that listens 80\n";}
			shell_exec("$kill -9 $pid");
		}
		
	}
	
	exec("$fuser 443/tcp 2>&1",$results);
	while (list ($key, $line) = each ($results) ){
		if($GLOBALS["VERBOSE"]){echo "fuser: ->\"$line\"\n";}
		if(preg_match("#tcp:\s+(.+)#", $line,$re)){$FUSERS=explode(" ",$re[1]);}
	}
	
	if(count($FUSERS)>0){
		while (list ($key, $pid) = each ($FUSERS) ){
			$pid=trim($pid);
			if(!is_numeric($pid)){continue;}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: killing $pid PID that listens 443\n";}
			shell_exec("$kill -9 $pid");
		}
	
	}	
	
	$php5=$unix->LOCATE_PHP5_BIN();
	
	
	
	
	if($unix->is_socket("/var/run/nginx-authenticator.sock")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Remove authenticator socket\n";}
		@unlink("/var/run/nginx-authenticator.sock");
	}
	
	if(is_file("/var/run/nginx-authenticator.sock")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Remove authenticator socket\n";}
		@unlink("/var/run/nginx-authenticator.sock");
	}	
	
	nginx_mime_types();
	
	$EnableArticaInNGINX=$sock->GET_INFO("EnableArticaInNGINX");
	if(!is_numeric($EnableArticaInNGINX)){$EnableArticaInNGINX=0;}
	@unlink("/etc/nginx/conf.d/default.conf");
	if($EnableArticaInNGINX==1){build_default_asArtica();}
		
	
	$cmd="$nginx -c /etc/nginx/nginx.conf";
	
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	shell_exec($cmd);

	for($i=0;$i<6;$i++){
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx service waiting $i/6...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx service Success service started pid:$pid...\n";}
		$php5=$unix->LOCATE_PHP5_BIN();
		shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.php-fpm.php --start >/dev/null 2>&1 &");
		return;
	}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx service failed...\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $cmd\n";}
	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.web-community-filter.php --register-lic >/dev/null 2>&1 &";
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	
}

function nginx_mime_types(){
$f[]="\ntypes {";
$f[]="    text/html                             html htm shtml;";
$f[]="    text/css                              css;";
$f[]="    text/xml                              xml;";

$f[]="    application/atom+xml                  atom;";
$f[]="    application/rss+xml                   rss;";
$f[]="";
$f[]="    text/mathml                           mml;";
$f[]="    text/plain                            txt;";
$f[]="    text/plain                            version;";
$f[]="    text/vnd.sun.j2me.app-descriptor      jad;";
$f[]="    text/vnd.wap.wml                      wml;";
$f[]="    text/x-component                      htc;";
$f[]="";
$f[]="    image/gif                             gif;";
$f[]="    image/jpeg                            jpeg jpg;";
$f[]="    image/png                             png;";
$f[]="    image/tiff                            tif tiff;";
$f[]="    image/vnd.wap.wbmp                    wbmp;";
$f[]="    image/x-icon                          ico;";
$f[]="    image/x-jng                           jng;";
$f[]="    image/x-ms-bmp                        bmp;";
$f[]="    image/svg+xml                         svg svgz;";
$f[]="    image/webp                            webp;";
$f[]="";
$f[]="    application/java-archive              jar war ear;";
$f[]="    application/mac-binhex40              hqx;";
$f[]="    application/msword                    doc;";
$f[]="    application/pdf                       pdf;";
$f[]="    application/x-tar						tar;";
$f[]="    application/x-bzip2					bz2;";
$f[]="    application/x-deb						deb;";
$f[]="    application/x-javascript				js;";
$f[]="    application/x-gzip					gz;";
$f[]="    application/postscript                ps eps ai;";
$f[]="    application/rtf                       rtf;";
$f[]="    application/vnd.ms-excel              xls;";
$f[]="    application/vnd.ms-powerpoint         ppt;";
$f[]="    application/vnd.wap.wmlc              wmlc;";
$f[]="    application/vnd.google-earth.kml+xml  kml;";
$f[]="    application/vnd.google-earth.kmz      kmz;";
$f[]="    application/x-7z-compressed           7z;";
$f[]="    application/x-cocoa                   cco;";
$f[]="    application/x-java-archive-diff       jardiff;";
$f[]="    application/x-java-jnlp-file          jnlp;";
$f[]="    application/x-makeself                run;";
$f[]="    application/x-perl                    pl pm;";
$f[]="    application/x-pilot                   prc pdb;";
$f[]="    application/x-rar-compressed          rar;";
$f[]="    application/x-redhat-package-manager  rpm;";
$f[]="    application/x-sea                     sea;";
$f[]="    application/x-shockwave-flash         swf;";
$f[]="    application/x-stuffit                 sit;";
$f[]="    application/x-tcl                     tcl tk;";
$f[]="    application/x-x509-ca-cert            der pem crt;";
$f[]="    application/x-xpinstall               xpi;";
$f[]="    application/xhtml+xml                 xhtml;";
$f[]="    application/zip                       zip;";
$f[]="";
$f[]="    application/binary					bin;";
$f[]="    application/octet-stream              exe dll;";
$f[]="    application/octet-stream              dmg;";
$f[]="    application/octet-stream              eot;";
$f[]="    application/octet-stream              iso img;";
$f[]="    application/octet-stream              msi msp msm;";
$f[]="";
$f[]="    audio/midi                            mid midi kar;";
$f[]="    audio/mpeg                            mp3;";
$f[]="    audio/ogg                             ogg;";
$f[]="    audio/x-m4a                           m4a;";
$f[]="    audio/x-realaudio                     ra;";
$f[]="";
$f[]="    video/3gpp                            3gpp 3gp;";
$f[]="    video/mp4                             mp4;";
$f[]="    video/mpeg                            mpeg mpg;";
$f[]="    video/quicktime                       mov;";
$f[]="    video/webm                            webm;";
$f[]="    video/x-flv                           flv;";
$f[]="    video/x-m4v                           m4v;";
$f[]="    video/x-mng                           mng;";
$f[]="    video/x-ms-asf                        asx asf;";
$f[]="    video/x-ms-wmv                        wmv;";
$f[]="    video/x-msvideo                       avi;";
$f[]="}\n";
@file_put_contents("/etc/nginx/mime.types", @implode("\n", $f));
if(is_file("/etc/nginx/mime.types.default")){@unlink("/etc/nginx/mime.types.default");}
}


function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx service Already Artica task running PID $oldpid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Nginx service already stopped...\n";}
		GHOSTS_PID();
		return;
	}
	
	
	
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$lighttpd_bin=$unix->find_program("lighttpd");
	$kill=$unix->find_program("kill");
	$nginx=$unix->find_program("nginx");

	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Nginx service Shutdown pid $pid...\n";}
	shell_exec("$nginx -c /etc/nginx/nginx.conf -s stop >/dev/null 2>&1");
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Nginx service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Nginx service shutdown - force - pid $pid...\n";}
	shell_exec("$kill -9 $pid >/dev/null 2>&1");
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Nginx service success...\n";}
		GHOSTS_PID();
		return;
	}
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Nginx service failed...\n";}
	GHOSTS_PID();
}

function install_nginx($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [install_nginx]: nginx Already Artica task running PID $oldpid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}	
	
	
	$nginx=$unix->find_program("nginx");
	if(is_file($nginx)){echo "Already installed\n";return;}
	$aptget=$unix->find_program("apt-get");
	if(!is_file($aptget)){echo "apt-get, no such binary...\n";die();}	
	$php=$unix->LOCATE_PHP5_BIN();
	echo "Check debian repository...\n";
	shell_exec("$php /usr/share/artica-postfix/exec.apt-get.php --nginx");
	echo "installing nginx\n";
	$cmd="DEBIAN_FRONTEND=noninteractive $aptget -o Dpkg::Options::=\"--force-confnew\" --force-yes -y install nginx 2>&1";
	system($cmd);
	$nginx=$unix->find_program("nginx");
	if(!is_file($nginx)){echo "Failed\n";return;}
	shell_exec("$php /usr/share/artica-postfix/exec.initslapd.php");
	shell_exec("$php /usr/share/artica-postfix/exec.freeweb.php --build");
	system("/etc/init.d/nginx restart");
}

function articaweb(){
	echo "************ \n\n** Installing nginx ** \n\n************\n";
	install_nginx(true);
	$unix=new unix();
	
	$php=$unix->LOCATE_PHP5_BIN();
	
	$nginx=$unix->find_program("nginx");
	if(!is_file($nginx)){echo "nginx not installed cannot find binary `nginx`\n";die();}
	$sock=new sockets();
	echo "Transfert Artica front-end to nginx\n";
	$sock->SET_INFO("EnableArticaFrontEndToNGninx", 1);
	echo "Stopping lighttpd\n";
	shell_exec("/etc/init.d/artica-webconsole stop");
	echo "Set starting script\n";
	shell_exec("$php /usr/share/artica-postfix/exec.initslapd.php");
	echo "Restarting nginx...\n";
	system("/etc/init.d/nginx restart");

}

function status(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/usr/share/artica-postfix/ressources/logs/web/nginx.status.acl";
	
	if(!$GLOBALS["FORCE"]){
		if($unix->file_time_min($pidTime)<5){return;}
	}
	
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		return;
	}
	
	@file_put_contents($pidfile, getmypid());	

	
	
	$maindir="/etc/nginx/sites-enabled";
	foreach (glob("/etc/nginx/sites-enabled/*") as $filename) {
		$t=explode("\n",@file_get_contents($filename));
		while (list ($key, $line) = each ($t) ){
			if(preg_match("#listen\s+(.+?);#", $line,$re)){
				
				$array[basename($filename)]["LISTEN"]=$re[1];
				continue;
			}
			if(preg_match("#server_name\s+(.+?);#", $line,$re)){
				$re[1]=trim($re[1]);
				if(preg_match("#^(.+?)\s+#", $re[1],$ri)){$re[1]=$ri[1];}
				$array[basename($filename)]["host"]=$re[1];
				continue;
			}			
			if(preg_match("#ssl\s+on;#", $line,$re)){
				$array[basename($filename)]["SSL"]=true;
				continue;
			}
			 
			
		}
		
		
	}	
	
	
	$curl=$unix->find_program("curl");
	
	while (list ($key, $BIG) = each ($array) ){
		$f=array();
		if($GLOBALS["VERBOSE"]){echo "{$BIG["host"]}\n";}
		if(preg_match("#unix:#", $BIG["LISTEN"])){continue;}
		$proto="http";
		$f[]="$curl";
		$f[]="--header 'Host: {$BIG["host"]}'";
		if(isset($BIG["SSL"])){$f[]="--insecure";$proto="https";}
		$f[]="$proto://127.0.0.1:{$BIG["LISTEN"]}/nginx_status 2>&1";
		$cmdline=@implode(" ", $f);
		if($GLOBALS["VERBOSE"]){echo "$cmdline\n";}
		$results=array();
		
		exec("$cmdline",$results);
		while (list ($index, $line) = each ($results) ){
			if(preg_match("#Active connections:\s+([0-9]+)#", $line,$re)){$FINAL[$BIG["host"]]["AC"]=$re[1];continue;}
			if(preg_match("#([0-9]+)\s+([0-9]+)\s+([0-9]+)#", $line,$re)){
					$FINAL[$BIG["host"]]["ACCP"]=$re[1];
					$FINAL[$BIG["host"]]["ACHDL"]=$re[2];
					$FINAL[$BIG["host"]]["ACRAQS"]=$re[3];
					continue;}
					
			if(preg_match("#Reading: ([0-9]+) Writing: ([0-9]+) Waiting: ([0-9]+)#", $line,$re)){
				$FINAL[$BIG["host"]]["reading"]=$re[1];
				$FINAL[$BIG["host"]]["writing"]=$re[2];
				$FINAL[$BIG["host"]]["waiting"]=$re[3];
			continue;}
			
			
		}

		
		
		
	}
	if($GLOBALS["VERBOSE"]){print_r($FINAL)."\n";}
	caches_status();
	
	
	
	@unlink($pidTime);
	@mkdir("/usr/share/artica-postfix/ressources/logs/web",0777,true);
	@file_put_contents($pidTime, serialize($FINAL));
	@chmod($pidTime,0777);
	rotate();
	
}


function caches_status(){
	$unix=new unix();
	$q=new mysql_squid_builder();
	$sql="SELECT directory,ID FROM nginx_caches";
	
	if(!$q->FIELD_EXISTS("nginx_caches", "CurrentSize")){
		$q->QUERY_SQL("ALTER TABLE `nginx_caches` ADD `CurrentSize` BIGINT UNSIGNED DEFAULT '0', ADD INDEX ( `CurrentSize` )");
	
	}
	
	$results=$q->QUERY_SQL($sql,'artica_backup');
	
	if($GLOBALS["VERBOSE"]){echo mysql_num_rows($results)." caches..\n";}
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$directorySize=$unix->DIRSIZE_BYTES($ligne["directory"]);
		if($GLOBALS["VERBOSE"]){echo "{$ligne["directory"]} $directorySize..\n";}
		$q->QUERY_SQL("UPDATE nginx_caches SET CurrentSize='$directorySize' WHERE ID='{$ligne["ID"]}'");
	}	
	
}

function awstats(){
	
	$sock=new sockets();
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	if($unix->file_time_min($pidTime)<60){return;}
	
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		return;
	}
	
	@file_put_contents($pidfile, getmypid());
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	
	$sock=new sockets();
	$EnableNginxStats=$sock->GET_INFO("EnableNginxStats");
	if(!is_numeric($EnableNginxStats)){$EnableNginxStats=0;}
	if($EnableNginxStats==1){return;}
	
	include_once(dirname(__FILE__)."/ressources/class.awstats.inc");
	include_once(dirname(__FILE__)."/ressources/class.mysql.syslogs.inc");
	
	$awstats_bin=$unix->LOCATE_AWSTATS_BIN();
	$nice=EXEC_NICE();
	$perl=$unix->find_program("perl");
	$awstats_buildstaticpages=$unix->LOCATE_AWSTATS_BUILDSTATICPAGES_BIN();
	if($GLOBALS["VERBOSE"]){
		echo "awstats......: $awstats_bin\n";
		echo "statics Pages: $awstats_buildstaticpages\n";
		echo "Nice.........: $nice\n";
		echo "perl.........: $perl\n";
	}
	
	if(!is_file($awstats_buildstaticpages)){
		echo "buildstaticpages no such binary...\n";
		return;
	}
	
	$sock=new sockets();
	$kill=$unix->find_program("kill");
	$NginxWorkLogsDir=$sock->GET_INFO("NginxWorkLogsDir");
	if($NginxWorkLogsDir==null){$NginxWorkLogsDir="/home/nginx/logsWork";}
	$sys=new mysql_storelogs();
	$files=$unix->DirFiles($NginxWorkLogsDir,"-([0-9\-]+)\.log");
	while (list ($filename, $line) = each ($files) ){
		
		if(!preg_match("#^(.+?)-[0-9]+-[0-9]+-[0-9]+-[0-9]+\.log$#", $filename,$re)){
			if($GLOBALS["VERBOSE"]){echo "$filename, skip\n";}
			continue;
		}
		if($GLOBALS["VERBOSE"]){echo "$filename, domain:{$re[1]}\n";}
		$servername=$re[1];
		$GLOBALS["nice"]=$nice;
		$aw=new awstats($servername);
		$aw->set_LogFile("$NginxWorkLogsDir/$filename");
		$aw->set_LogType("W");
		$aw->set_LogFormat(1);
		$config=$aw->buildconf();
		$SOURCE_FILE_PATH="$NginxWorkLogsDir/$filename";
		
		
		$configlength=strlen($config);
		if($configlength<10){
			if($GLOBALS["VERBOSE"]){echo "configuration file lenght failed $configlength bytes, aborting $servername\n";}
			return;
		}
		
		@file_put_contents("/etc/awstats/awstats.$servername.conf",$config);
		@chmod("/etc/awstats/awstats.$servername.conf",644);
		$Lang=$aw->GET("Lang");
		if($Lang==null){$Lang="auto";}
		@mkdir("/var/tmp/awstats/$servername",666,true);		
		$t1=time();
		$cmd="$nice$perl $awstats_buildstaticpages -config=$servername -update -lang=$Lang -awstatsprog=$awstats_bin -dir=/var/tmp/awstats/$servername -LogFile=\"$SOURCE_FILE_PATH\" 2>&1";
		if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
		shell_exec($cmd);	
		$filedate=date('Y-m-d H:i:s',filemtime($SOURCE_FILE_PATH));
		if(!awstats_import_sql($servername)){continue;}
		$sys->ROTATE_TOMYSQL($SOURCE_FILE_PATH, $filedate);
		
		
		
	}
}

function awstats_import_sql($servername){
	$q=new mysql();
	$unix=new unix();


	$sql="DELETE FROM awstats_files WHERE `servername`='$servername'";
	$q->QUERY_SQL($sql,"artica_backup");

	foreach (glob("/var/tmp/awstats/$servername/awstats.*") as $filename) {
			
		if(basename($filename)=="awstats.$servername.html"){
			$awstats_filename="index";
		}else{
			if(preg_match("#awstats\.(.+)\.([a-z0-9]+)\.html#",$filename,$re)){$awstats_filename=$re[2];}
		}
		if($GLOBALS["VERBOSE"]){echo "$servername: $awstats_filename\n";}
		if($awstats_filename<>null){
			$content=addslashes(@file_get_contents("$filename"));
			$results[]="Importing $filename";
			@unlink($filename);
			$sql="INSERT INTO awstats_files (`servername`,`awstats_file`,`content`)
			VALUES('$servername','$awstats_filename','$content')";
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){
				if($GLOBALS["VERBOSE"]){echo "$q->mysql_error\n";}
				$unix->send_email_events("awstats for $servername failed database error",$q->mysql_error,"system");
				return false;
			}
		}
		$q->ok;
	}
	
	return true;

}

function framework(){
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx Already Artica task running PID $oldpid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());	
	
	
	$lighttpdbin=$unix->find_program("lighttpd");
	if(is_file($lighttpdbin)){return;}
	
	if(!is_file("/etc/php5/fpm/pool.d/framework.conf")){
		$php=$unix->LOCATE_PHP5_BIN();
		shell_exec("$php /usr/share/artica-postfix/exec.php-fpm.php --build");
	}
	
	if(!is_file("/etc/php5/fpm/pool.d/framework.conf")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, Unable to stat framework settings\n";}
		return;
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, building framework...\n";}
	$host=new nginx(47980);
	$host->set_proxy_disabled();
	$host->set_DocumentRoot("/usr/share/artica-postfix/framework");
	$host->set_framework();
	$host->set_listen_ip("127.0.0.1");
	$host->set_servers_aliases(array("127.0.0.1"));
	$host->build_proxy();

	$PID=PID_NUM();
	if(!$unix->process_exists($PID)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, not started, start it...\n";}
		start(true);
	}
	
	$kill=$unix->find_program("kill");
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, reloading PID $PID\n";}
	shell_exec("$kill -HUP $PID >/dev/null 2>&1");
	
}

function test_sources(){
	$unix=new unix();
	
	if(!$GLOBALS["FORCE"]){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
		if($GLOBALS["VERBOSE"]){echo "pidTime: $pidTime\n";} 
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx Already Artica task running PID $oldpid since {$time}mn\n";}
			return;
		}
		
		$pidTimeEx=$unix->file_time_min($pidTime);
		if($pidTime<15){return;}
		@file_put_contents($pidfile, getmypid());
		@unlink($pidTime);
		@file_put_contents($pidTime, time());
	}
	
	$echo=$unix->find_program("echo");
	$nc=$unix->find_program("nc");
	
	$q=new mysql_squid_builder();
	
	if(!$q->FIELD_EXISTS("reverse_sources", "isSuccess")){
		$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `isSuccess` smallint(1) NOT NULL DEFAULT '1', ADD INDEX ( `isSuccess`)");
	}
	
	if(!$q->FIELD_EXISTS("reverse_sources", "isSuccesstxt")){
		$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `isSuccesstxt` TEXT");
	}

	if(!$q->FIELD_EXISTS("reverse_sources", "isSuccessTime")){
		$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `isSuccessTime` datetime");
	}	
	
	$sql="SELECT * FROM reverse_sources";
	$results=$q->QUERY_SQL($sql);
	
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$ipaddr=$ligne["ipaddr"];
		$ID=$ligne["ID"];
		$port=$ligne["port"];
		$IsSuccess=1;
		$linesrows=array();
		$cmdline="$echo -e -n \"GET / HTTP/1.1\\r\\n\" | $nc -q 2 -v  $ipaddr $port 2>&1";
		if($GLOBALS["VERBOSE"]){echo "$ipaddr: $cmdline\n";}
		exec($cmdline,$linesrows);
		while (list ($a, $b) = each ($linesrows) ){
			if($GLOBALS["VERBOSE"]){echo "$ipaddr: $b\n";}
			if(preg_match("#failed#", $b)){$IsSuccess=0;}}
		reset($linesrows);
		$linesrowsText=mysql_escape_string2(base64_encode(serialize($linesrows)));
		$date=date("Y-m-d H:i:s");
		$q->QUERY_SQL("UPDATE reverse_sources SET isSuccess=$IsSuccess,isSuccesstxt='$linesrowsText',isSuccessTime='$date' WHERE ID=$ID");
		
	}
}

function purge_all_caches(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [PURGE]: Nginx Already Artica task running PID $oldpid since {$time}mn\n";}
		return;
	}
	
	
	
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL("SELECT directory FROM nginx_caches");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$f[]=$ligne["directory"];
	}
	$f[]="/home/nginx/tmp";
	$rm=$unix->find_program("rm");
	while (list ($index, $value) = each ($f) ){
		if(!is_dir($value)){continue;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Removing $value\n";}
		shell_exec("$rm -rf /home/nginx/tmp/*");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Removing $value OK\n";}
	}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Reloading service\n";}
	reload(true);
	
}




function purge_cache($ID){
	if(!is_numeric($ID)){return;}
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [PURGE]: Nginx Already Artica task running PID $oldpid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT directory FROM nginx_caches WHERE ID='$ID'"));
	$directory=$ligne["directory"];
	if(!is_dir($directory)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [PURGE]: `$directory` no such directory\n";}
	}
	$rm=$unix->find_program("rm");
	shell_exec("$rm -rf \"$directory\"");
	@mkdir($directory,true,0755);
	reload(true);
	caches_status();
	
}


?>