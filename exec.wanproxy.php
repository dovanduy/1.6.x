<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SERV_NAME"]="WAN Proxy compressor";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;
$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');

if($argv[1]=="--build-squid"){$GLOBALS["OUTPUT"]=true;build_squid();die();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;build();die();}
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop($argv[2]);die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start($argv[2]);die();}

function start($conffile){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/wanproxy.pid";
	$SERV_NAME=$GLOBALS["SERV_NAME"];
	$pid=$unix->get_pid_from_file($pidfile);
	$sock=new sockets();
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Starting Task Already running PID $pid since {$time}mn\n";}
		return;
	}
		
	@file_put_contents($pidfile, getmypid());
	
	

	
	
	$daemonbin=$unix->find_program("wanproxy");
	if(!is_file($daemonbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]:$SERV_NAME is not installed...\n";}
		return;
	}	
	
	$pid=GET_PID($conffile);
	
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME already running pid $pid since {$time}mn\n";}
		return;
	}	
	
	if(!is_file("/etc/$conffile")){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME /etc/$conffile no such file\n";}
		return;
		
	}
	
	
	$nohup=$unix->find_program("nohup");
	@mkdir("/var/log/wanproxy",0755,true);

	$cmdline="$nohup $daemonbin -c /etc/$conffile >/var/log/wanproxy/wanproxy.log 2>&1 &";
	
	if($GLOBALS["VERBOSE"]){echo $cmdline."\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Starting $SERV_NAME\n";}
	shell_exec("$cmdline");
	sleep(1);
	for($i=0;$i<10;$i++){
		$pid=GET_PID($conffile);
		if($unix->process_exists($pid)){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME started pid .$pid..\n";}break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME wait $i/10\n";}
		sleep(1);
	}	
	sleep(1);
	$pid=GET_PID($conffile);
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME failed to start\n";}
		$f=explode("\n",@file_get_contents($TMP));
		while (list ($num, $ligne) = each ($TMP) ){
			if(trim($ligne)==null){continue;}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $ligne\n";}
		}
	
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME success\n";}
		
		
	}
	if(!$unix->process_exists($pid)){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $cmdline\n";}}
	
}


function stop($conffile){
	

	$SERV_NAME=$GLOBALS["SERV_NAME"];
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Already task running PID $pid since {$time}mn\n";}
		return;
	}

	$pid=GET_PID($conffile);
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: $SERV_NAME already stopped...\n";}
		return;
	}	
	
	$kill=$unix->find_program("kill");
	$time=$unix->PROCCESS_TIME_MIN($pid);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Stopping $SERV_NAME with a ttl of {$time}mn\n";}
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Stopping $SERV_NAME smoothly...\n";}
	$cmd="$kill $pid >/dev/null";
	shell_exec($cmd);

	$pid=GET_PID($conffile);
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: $SERV_NAME success...\n";}
		return;
	}	
	
	
	for($i=0;$i<10;$i++){
		$pid=GET_PID($conffile);
		if($unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: $SERV_NAME kill pid $pid..\n";}
			unix_system_kill_force($pid);
		}else{
			break;
		}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME wait $i/10\n";}
		sleep(1);
	}	
	$pid=GET_PID($conffile);
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: $SERV_NAME success...\n";}
		return;
	}	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: $SERV_NAME Failed...\n";}
}

function GET_PID($conffile=null){
	$unix=new unix();
	$daemonbin=$unix->find_program("wanproxy");
	if($conffile==null){
		return $unix->PIDOF($daemonbin);
	}
	$daemonbin=basename($daemonbin);
	$conffile=str_replace(".", "\.", $conffile);
	return $unix->PIDOF_PATTERN("$daemonbin.*?-c.*?$conffile");
	
}



function restart(){
	
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php5 ".__FILE__." --stop");
	shell_exec("$php5 ".__FILE__." --start");
	
}

function build_squid(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: Already task running PID $pid since {$time}mn\n";}
		return;
	}
	
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: Build Parent compressor\n";}
	build_parent();
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: Build child compressor\n";}
	build_childs();
	
	if(is_file("/etc/init.d/wanproxy-childs")){
		if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: Reloading child compressor\n";}
		system("/etc/init.d/wanproxy-childs reload");
	}
	
	if(is_file("/etc/init.d/wanproxy-parent")){
		if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: Reloading Parent compressor\n";}
		system("/etc/init.d/wanproxy-parent reload");
	}	
	
	
}

function build(){build_squid();}


function build_childs(){
	$SERV_NAME=$GLOBALS["SERV_NAME"];;
	$sql="SELECT * FROM squid_parents WHERE enabled=1 AND server_type='wancompress' ORDER BY weight DESC";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");	
	if(!$q->ok){return;}
	if(mysql_num_rows($results)==0){remove_init_childs();return;}
	
	@mkdir("/home/squid/wanproxy",0755,true);
	$conf[]="create log-mask catch-all";
	$conf[]="set catch-all.regex \"^/\"";
	$conf[]="set catch-all.mask INFO";
	$conf[]="activate catch-all";
	$conf[]="";
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$WanProxyMemory=intval($ligne["WanProxyMemory"]);
		$WanProxyCache=intval($ligne["WanProxyCache"]);
		$port=$ligne["local_port"];
		$ID=$ligne["ID"];
		$cacheAdd=false;
		$wanport=$ligne["server_port"];
		$wanproxy=$ligne["servername"];
		
		if($WanProxyMemory>0){
			$cacheAdd=true;
			$conf[]="# A primary in-memory cache of 128MB per peer.";
			$conf[]="# A secondary disk cache of 1GB in the file wanproxy.xcache shared by all peers.";
			$conf[]="create cache memorycache{$ID}";
			$conf[]="set memorycache{$ID}.type Memory";
			$conf[]="set memorycache{$ID}.size 128MB";
			$conf[]="activate memorycache{$ID}";
			$conf[]="";
		}
	
		if($WanProxyCache>0){
			$cacheAdd=true;
			$conf[]="create cache diskcache{$ID}";
			$conf[]="set diskcache{$ID}.type Disk";
			$conf[]="set diskcache{$ID}.size 1GB";
			$conf[]="set diskcache{$ID}.path \"/home/squid/wanproxy/wanproxychilds-{$ID}.xcache\"";
			$conf[]="activate diskcache{$ID}";
			$conf[]="";
		}
	
		@mkdir("/home/squid/wanproxy-{$ID}",0755,true);
		
		if($cacheAdd){
			$conf[]="create cache cache{$ID}";
			$conf[]="set cache{$ID}.type Pair";
			if($WanProxyMemory>0){$conf[]="set cache{$ID}.primary memorycache{$ID}";}
			if($WanProxyCache>0){$conf[]="set cache{$ID}.secondary diskcache{$ID}";}
			$conf[]="activate cache{$ID}";
			
		}
		$conf[]="";
		$conf[]="# Set up codec instances.";
		$conf[]="create codec codec{$ID}";
		$conf[]="set codec{$ID}.codec XCodec";
		if($cacheAdd){
			$conf[]="set codec{$ID}.cache cache{$ID}";
		}
		$conf[]="set codec{$ID}.compressor zlib";
		$conf[]="set codec{$ID}.compressor_level 6";
		$conf[]="set codec{$ID}.track_statistics true";
		$conf[]="activate codec{$ID}";
		$conf[]="";
	
		$conf[]="create interface if{$ID}";
		$conf[]="set if{$ID}.family IPv4";
		$conf[]="set if{$ID}.host \"127.0.0.1\"";
		$conf[]="set if{$ID}.port \"$port\"";
		$conf[]="activate if{$ID}";
		$conf[]="";
	
	
		$conf[]="create peer peer{$ID}";
		$conf[]="set peer{$ID}.family IPv4";
		$conf[]="set peer{$ID}.host \"$wanproxy\"";
		$conf[]="set peer{$ID}.port \"$wanport\"";
		$conf[]="activate peer{$ID}";
		$conf[]="";
	
		$conf[]="create proxy proxy{$ID}";
		$conf[]="set proxy{$ID}.type TCP-TCP";
		$conf[]="set proxy{$ID}.interface if{$ID}";
		$conf[]="set proxy{$ID}.interface_codec None";
		$conf[]="set proxy{$ID}.peer peer{$ID}";
		$conf[]="set proxy{$ID}.peer_codec codec{$ID}";
		$conf[]="activate proxy{$ID}";
		$conf[]="";
	
	}
	$conf[]="create interface if0";
	$conf[]="set if0.family IPv4";
	$conf[]="set if0.host \"0.0.0.0\"";
	$conf[]="set if0.port \"9900\"";
	$conf[]="activate if0";
	$conf[]="";
	$conf[]="create monitor monitor0";
	$conf[]="set monitor0.interface if0";
	$conf[]="activate monitor0";
	$conf[]="";
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: $SERV_NAME /etc/wanproxy-client.conf done...\n";}
	@file_put_contents("/etc/wanproxy-client.conf", @implode("\n", $conf));
	create_init_childs();	
	
}

function build_parent(){
	
	$q=new mysql_squid_builder();
	$unix=new unix();
	
	if(!isset($GLOBALS["NETWORK_ALL_INTERFACES"])){
		$unix=new unix();
		$GLOBALS["NETWORK_ALL_INTERFACES"]=$unix->NETWORK_ALL_INTERFACES();
	}
	
	if(!isset($GLOBALS["NETWORK_ALL_NICS"])){
		$unix=new unix();
		$GLOBALS["NETWORK_ALL_NICS"]=$unix->NETWORK_ALL_INTERFACES();
	}
	
	
	$sql="SELECT * FROM proxy_ports WHERE WANPROXY=1 AND enabled=1";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){return;}
	if(mysql_num_rows($results)==0){remove_init_parent();return;}
	@mkdir("/home/squid/wanproxy",0755,true);
	
	$conf[]="create log-mask catch-all";
	$conf[]="set catch-all.regex \"^/\"";
	$conf[]="set catch-all.mask INFO";
	$conf[]="activate catch-all";
	$conf[]="";
	
	while ($ligne = mysql_fetch_assoc($results)) {
		
	$port=$ligne["port"];
	$ID=$ligne["ID"];
	$eth=$ligne["nic"];
	$wanport=$ligne["WANPROXY_PORT"];
	$WanProxyMemory=intval($ligne["WanProxyMemory"]);
	$WanProxyCache=intval($ligne["WanProxyCache"]);
	$cacheAdd=false;
	$ipaddr=null;
	if($eth<>null){
		$ipaddr=$GLOBALS["NETWORK_ALL_NICS"][$eth]["IPADDR"];
		
	}
	
	@mkdir("/home/squid/wanproxy",0755,true);
	
	if($ipaddr==null){$ipaddr="0.0.0.0";}
	
	$conf[]="# A primary in-memory cache of 128MB per peer.";
	$conf[]="# A secondary disk cache of 1GB in the file wanproxy.xcache shared by all peers.";
	
	if($WanProxyMemory>0){
		$conf[]="create cache memorycache{$ID}";
		$conf[]="set memorycache{$ID}.type Memory";
		$conf[]="set memorycache{$ID}.size {$WanProxyMemory}MB";
		$conf[]="activate memorycache{$ID}";
		$conf[]="";
		$cacheAdd=true;
	}
	if($WanProxyCache>0){
		$conf[]="create cache diskcache{$ID}";
		$conf[]="set diskcache{$ID}.type Disk";
		$conf[]="set diskcache{$ID}.size {$WanProxyCache}GB";
		$conf[]="set diskcache{$ID}.path \"/home/squid/wanproxy/wanproxyParent{$ID}.xcache\"";
		$conf[]="activate diskcache{$ID}";
		$conf[]="";
		$cacheAdd=true;
	}
	
	if($cacheAdd){
		$conf[]="create cache cache{$ID}";
		$conf[]="set cache{$ID}.type Pair";
		if($WanProxyMemory>0){$conf[]="set cache{$ID}.primary memorycache{$ID}";}
		if($WanProxyCache>0){$conf[]="set cache{$ID}.secondary diskcache{$ID}";}
		$conf[]="activate cache{$ID}";
	}
	$conf[]="";
	$conf[]="# Set up codec instances.";
	$conf[]="create codec codec{$ID}";
	$conf[]="set codec{$ID}.codec XCodec";
	if($cacheAdd){$conf[]="set codec{$ID}.cache cache{$ID}";}
	$conf[]="set codec{$ID}.compressor zlib";
	$conf[]="set codec{$ID}.compressor_level 6";
	$conf[]="set codec{$ID}.track_statistics true";
	$conf[]="activate codec{$ID}";
	$conf[]="";
	
	$conf[]="create interface if{$ID}";
	$conf[]="set if{$ID}.family IPv4";
	$conf[]="set if{$ID}.host \"$ipaddr\"";
	$conf[]="set if{$ID}.port \"$port\"";
	$conf[]="activate if{$ID}";
	$conf[]="";
	
	
	$conf[]="create peer peer{$ID}";
	$conf[]="set peer{$ID}.family IPv4";
	$conf[]="set peer{$ID}.host \"127.0.0.1\"";
	$conf[]="set peer{$ID}.port \"$wanport\"";
	
	$conf[]="activate peer{$ID}";
	$conf[]="";
	
	$conf[]="create proxy proxy{$ID}";
	$conf[]="set proxy{$ID}.type TCP-TCP";
	$conf[]="set proxy{$ID}.interface if{$ID}";
	$conf[]="set proxy{$ID}.interface_codec codec{$ID}";
	$conf[]="set proxy{$ID}.peer peer{$ID}";
	$conf[]="set proxy{$ID}.peer_codec None";
	$conf[]="activate proxy{$ID}";
	$conf[]="";
	
	}
	
	$conf[]="create interface if0";
	$conf[]="set if0.family IPv4";
	$conf[]="set if0.host \"0.0.0.0\"";
	$conf[]="set if0.port \"9900\"";
	$conf[]="activate if0";
	$conf[]="";
	$conf[]="create monitor monitor0";
	$conf[]="set monitor0.interface if0";
	$conf[]="activate monitor0";
	$conf[]="";
	@file_put_contents("/etc/wanproxy-parent.conf", @implode("\n", $conf));
	create_init_parent();
}	


function remove_init_childs(){
	$INITD_PATH="/etc/init.d/wanproxy-childs";
	$unix=new unix();
	$rm=$unix->find_program("rm");
	if(is_dir("/home/squid/wanproxy")){shell_exec("$rm -rf /home/squid/wanproxy");}
	if(is_file("/etc/wanproxy-client.conf")){@unlink("/etc/wanproxy-client.conf");}
	
	if(!is_file($INITD_PATH)){return;}
	$basename=basename($INITD_PATH);
	shell_exec("$INITD_PATH --stop --force");
	if($GLOBALS["OUTPUT"]){echo "Reconfigure...: ".date("H:i:s")." [INIT]: Remove $basename init\n";}
	
	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f $basename remove >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --del $basename >/dev/null 2>&1");
	}
	
	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}	
	
	
}

function remove_init_parent(){
	$INITD_PATH="/etc/init.d/wanproxy-parent";
	if(!is_file($INITD_PATH)){return;}
	$basename=basename($INITD_PATH);
	shell_exec("$INITD_PATH --stop --force");
	$unix=new unix();
	$rm=$unix->find_program("rm");
	if(is_file("/etc/wanproxy-parent.conf")){@unlink("/etc/wanproxy-parent.conf");}
	if(is_dir("/home/squid/wanproxy")){shell_exec("$rm -rf /home/squid/wanproxy");}
	
	if($GLOBALS["OUTPUT"]){echo "Reconfigure...: ".date("H:i:s")." [INIT]: Remove $basename init\n";}
	
		if(is_file('/usr/sbin/update-rc.d')){
			shell_exec("/usr/sbin/update-rc.d -f $basename remove >/dev/null 2>&1");
		}
	
		if(is_file('/sbin/chkconfig')){
			shell_exec("/sbin/chkconfig --del $basename >/dev/null 2>&1");
		}
	
		if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
	
	
}

function create_init_childs(){
	$unix=new unix();

	$php=$unix->LOCATE_PHP5_BIN();
	$daemonbin=$unix->find_program("wanproxy");
	$daemonbinLog=basename($daemonbin);
	$INITD_PATH="/etc/init.d/wanproxy-childs";



	$php5script=basename(__FILE__);
	if(!is_file($daemonbin)){return;}


	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         $daemonbinLog";
	$f[]="# Required-Start:    \$local_fs \$syslog \$network";
	$f[]="# Required-Stop:     \$local_fs \$syslog \$network";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: $daemonbinLog";
	$f[]="# chkconfig: - 80 75";
	$f[]="# description: $daemonbinLog";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start wanproxy-client.conf \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop wanproxy-client.conf \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop wanproxy-client.conf \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start wanproxy-client.conf \$2 \$3";
	$f[]="    ;;";

	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop wanproxy-client.conf \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start wanproxy-client.conf \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: building $INITD_PATH done...\n";}

	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}

}

function create_init_parent(){
	$unix=new unix();
	
	$php=$unix->LOCATE_PHP5_BIN();
	$daemonbin=$unix->find_program("wanproxy");
	$daemonbinLog=basename($daemonbin);
	$INITD_PATH="/etc/init.d/wanproxy-parent";
	
	
	
	$php5script=basename(__FILE__);
	if(!is_file($daemonbin)){return;}
	
	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         $daemonbinLog";
	$f[]="# Required-Start:    \$local_fs \$syslog \$network";
	$f[]="# Required-Stop:     \$local_fs \$syslog \$network";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: $daemonbinLog";
	$f[]="# chkconfig: - 80 75";
	$f[]="# description: $daemonbinLog";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start wanproxy-parent.conf \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop wanproxy-parent.conf \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop wanproxy-parent.conf $2 \$3";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start wanproxy-parent.conf \$2 \$3";
	$f[]="    ;;";
	
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop wanproxy-parent.conf \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start wanproxy-parent.conf \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: building $INITD_PATH done...\n";}
	
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);
	
	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	
}


?>