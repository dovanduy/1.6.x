<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);

include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.iptables-chains.inc');

$unix=new unix();
if(is_file("/etc/artica-postfix/FROM_ISO")){
	if($unix->file_time_min("/etc/artica-postfix/FROM_ISO")<1){return;}
}

update();
function update(){
	
	$unix=new unix();
	$sock=new sockets();
	$filetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if($GLOBALS["VERBOSE"]){
		echo "filetime = $filetime\n";
		echo "pidfile = $pidfile\n";
	}
	$EnableSpamhausDROPList=$sock->GET_INFO("EnableSpamhausDROPList");
	if(!is_numeric($EnableSpamhausDROPList)){$EnableSpamhausDROPList=0;}
	if(!$GLOBALS["FORCE"]){
		if(!$GLOBALS["VERBOSE"]){
			$pid=$unix->get_pid_from_file($pidfile);
			if($unix->process_exists($pid)){
				if($GLOBALS["VERBOSE"]){echo "{$pid} already running !!!\n";}
				return;}
		}
	}
	
	@file_put_contents($pidfile, getmypid());
	
	if(!$GLOBALS["FORCE"]){
		$time=$unix->file_time_min($filetime);
		if($time<1440){
			if($GLOBALS["VERBOSE"]){echo "{$time}Mn !!!\n";}
			return;}
		@unlink($filetime);
		@file_put_contents($filetime, time());
	}
	
	$iptables=new iptables_chains();
	$curl=new ccurl("http://www.spamhaus.org/drop/drop.lasso");
	$curl->NoHTTP_POST=true;
	$tmpdir=$unix->TEMP_DIR();
	$destination="$tmpdir/drop.lasso";
	
	if(!$curl->get()){
		system_admin_events("Spamhaus DROP List failed $curl->error");
		return;
	}
	$drop=explode("\n",$curl->data);
	foreach($drop as $line) {
		$line=trim($line);
		if (!empty($line) && substr($line,0,1)!==';') {
			list($cidr,$sbl)=explode(" ; ",$line);
			$cidr=trim($cidr);
			$sbl=trim($sbl);
			//echo "iptables -A input -s $cidr -d 0/0 -j REJECT\n";
			//echo "iptables -A output -s 0/0 -d $cidr -j REJECT\n";
			$array[$cidr]=$sbl;
		}
	}
	
	$q=new mysql();
	
	$prefix="INSERT IGNORE INTO iptables(
	service,
	servername,
	serverip,
	local_port,
	disable,
	events_number,
	rule_string,
	rulemd5,
	flux,
	events_block,
	date_created,
	multiples_ports,allow ) VALUES ";
	$date=date("Y-m-d H:i:s");
	while (list ($cidr, $sbl) = each ($array) ){
		$rulemd5=md5("$cidr$sbl");
		$f[]="('SpamHaus',
		'$cidr',
		'$cidr',
		'0',
		'0',
		'0',
		'iptables -A input -s $cidr -d 0/0 -j REJECT',
		'$rulemd5','INPUT',
		'Spamhaus DROP List',
		'$date',
		'0',0)";
		
	}
	
	if(count($f)>0){
		echo count($f)." rules added";
		$q->QUERY_SQL("DELETE FROM iptables WHERE `service`='SpamHaus' AND `allow`=0","artica_backup");
		$q->QUERY_SQL($prefix.@implode($f, ","),"artica_backup");
	}
	
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php /usr/share/artica-postfix/exec.postfix.iptables.php --spamhaus >/dev/null 2>&1 &";
	echo $cmd."\n";
	shell_exec($cmd);
	
}
?>