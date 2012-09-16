<?php
$GLOBALS["AS_ROOT"]=true;

$GLOBALS["COMMANDLINE"]=implode(" ",$argv);
if(strpos($GLOBALS["COMMANDLINE"],"--verbose")>0){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;$GLOBALS["DEBUG"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.dnsmasq.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/framework/class.settings.inc");
include_once(dirname(__FILE__)."/ressources/class.haproxy.inc");
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}


if($argv[1]=="--build"){build();die();}
if($argv[1]=="--reload"){reload();die();}
if($argv[1]=="--iptables-remove"){iptables_delete_all();die();}




function reload(){
	build();
	
	if(!isRunning()){shell_exec("/etc/init.d/artica-postfix restart haproxy");return;}
	$unix=new unix();
	$HAPROXY=$unix->find_program("haproxy");
	$CONFIG="/etc/haproxy/haproxy.cfg";
	$PIDFILE="/var/run/haproxy.pid";
	$EXTRAOPTS=null;
	$pids=@implode(" ", pidsarr());
	
	$cmd="$HAPROXY -f \"$CONFIG\" -p $PIDFILE -D $EXTRAOPTS -sf $pids 2>&1";
	exec($cmd,$results);
	while (list ($num, $ligne) = each ($results) ){
		echo "Starting......: HAProxy $ligne\n";
	}
}

function isRunning(){
	$running=false;
	$unix=new unix();
	$f=pidsarr();
	while (list ($num, $pid) = each ($f) ){
		if($unix->process_exists($pid)){
			return true;
		}
	}
	
	return false;
}

function pidsarr(){
	$R=array();
	$f=file("/var/run/haproxy.pid");
	while (list ($num, $ligne) = each ($f) ){
		$ligne=trim($ligne);
		if(!is_numeric($ligne)){continue;}
		$R[]=$ligne;
	}	
	return $R;
}



function build(){
	
	$q=new mysql();
	if(!$q->TestingConnection()){
		echo "Starting......: HAProxy building configuration failed (MySQL service not available).\n";
		return;
	}
	
	$hap=new haproxy();
	$conf=$hap->buildconf();
	if(trim($conf)==null){return;}
	@mkdir("/etc/haproxy",0755,true);
	@file_put_contents("/etc/haproxy/haproxy.cfg", $conf);
	Transparents_modes();
	rsyslog_conf();
	
	
}

function Transparents_modes(){
	iptables_delete_all();
	$unix=new unix();
	$iptables=$unix->find_program("iptables");	
	$sysctl=$unix->find_program("sysctl");	
	$sql="SELECT * FROM haproxy WHERE enabled=1 AND transparent=1";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){if($GLOBALS["AS_ROOT"]){echo "Starting......: HAProxy building configuration failed $q->mysql_error\n";return;}}
	if(mysql_num_rows($results)==0){
		echo "Starting......: HAProxy building configuration no transparent configurations...\n";
		return;
	}
	shell_exec("$sysctl -w net.ipv4.ip_forward=1 2>&1");
	shell_exec("$sysctl -w net.ipv4.conf.default.send_redirects=0 2>&1");
	shell_exec("$sysctl -w net.ipv4.conf.all.send_redirects=0 2>&1");
	shell_exec("$sysctl -w net.ipv4.conf.eth0.send_redirects=0 2>&1");		
	shell_exec("$iptables -P FORWARD ACCEPT");
	
	return;
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$listen_add="127.0.0.1";
		$next_port=$ligne["listen_port"];
		$listen_ip=$ligne["listen_ip"];
		$transparent_port=$ligne["transparentsrcport"];
		if($transparent_port<1){continue;}
		echo "Starting......: HAProxy building configuration transparent request from $listen_ip:$transparent_port and redirect to $listen_add:$next_port\n";

		shell_exec2("$iptables -t nat -A PREROUTING -i eth0 -p tcp --dport $transparent_port -j ACCEPT -m comment --comment \"ArticaHAProxy\"");
		shell_exec2("$iptables -t nat -A PREROUTING -p tcp --dport $transparent_port -j REDIRECT --to-ports $next_port -m comment --comment \"ArticaHAProxy\"");
		shell_exec2("$iptables -t nat -A POSTROUTING -j MASQUERADE -m comment --comment \"ArticaHAProxy\"");
		shell_exec2("$iptables -t mangle -A PREROUTING -p tcp --dport $next_port -j DROP -m comment --comment \"ArticaHAProxy\"");
	}	
	
}

function shell_exec2($cmd){
	echo "Starting......: HAProxy $cmd\n";
	shell_exec($cmd);
	
}

function rsyslog_conf(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.syslog-engine.php --rsylogd >/dev/null 2>&1 &");
	
	
	
}

function iptables_delete_all(){
	$unix=new unix();
	$iptables_save=$unix->find_program("iptables-save");
	$iptables_restore=$unix->find_program("iptables-restore");
	system("$iptables_save > /etc/artica-postfix/iptables.conf");
	$data=file_get_contents("/etc/artica-postfix/iptables.conf");
	$datas=explode("\n",$data);
	$pattern="#.+?ArticaHAProxy#";	
	while (list ($num, $ligne) = each ($datas) ){
			if($ligne==null){continue;}
			if(preg_match($pattern,$ligne)){continue;}
			$conf=$conf . $ligne."\n";
			}
	
	file_put_contents("/etc/artica-postfix/iptables.new.conf",$conf);
	system("$iptables_restore < /etc/artica-postfix/iptables.new.conf");
}

