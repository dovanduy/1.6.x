<?php
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="DNS server DNSMasq";
$GLOBALS["COMMANDLINE"]=@implode($argv, " ");
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
if(strpos($GLOBALS["COMMANDLINE"],"--verbose")>0){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;$GLOBALS["DEBUG"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.dnsmasq.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/framework/class.settings.inc");
include_once(dirname(__FILE__)."/ressources/class.resolv.conf.inc");
include_once(dirname(__FILE__)."/ressources/class.dhcpd.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");

if($GLOBALS['VERBOSE']){echo "Parsing....{$argv[1]}\n";}
if($argv[1]=="--testresolv"){testsRESOLV();exit;}



if($argv[1]=="--varrun"){
	if($GLOBALS['VERBOSE']){echo "Running....{$argv[1]}\n";}
	varrun();
	exit;
}
if($argv[1]=="--reload"){reload_dnsmasq();die();}
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;build();die();}
if($argv[1]=="--build-hosts"){build_hosts();die();}
if($argv[1]=="--install-service"){install_service($argv[2]);die();}
if($argv[1]=="--remove-service"){remove_service($argv[2]);die();}

build();

function cache_dns_hosts(){
	if(isset($GLOBALS["cache_dns_hosts"])){return;}
	$GLOBALS["cache_dns_hosts"]=true;
	$sock=new sockets();
	$EnableLocalDNSMASQ=$sock->GET_INFO("EnableLocalDNSMASQ");
	if(!is_numeric($EnableLocalDNSMASQ)){$EnableLocalDNSMASQ=0;}
	if($EnableLocalDNSMASQ==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} EnableLocalDNSMASQ = $EnableLocalDNSMASQ abort cache_dns_hosts()\n";}
		return ;}
	$filename="/etc/dnsmasq.conf.empty";
	include_once('ressources/class.squid.inc');
	$build_hosts_array=unserialize(@file_get_contents("/etc/dnsmasq.hash.domains"));
	
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM dnsmasq_records";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){return;}
	while ($ligne = mysql_fetch_assoc($results)) {
		$ipaddr=$ligne["ipaddr"];
		$tr=explode(".", $ipaddr);
		krsort($tr);
		$reversed=@implode(".", $tr);
		$hostname=$ligne["hostname"];
		if(strpos($hostname, ".")>0){
			$build_hosts_arra[$q->GetFamilySites($hostname)]=true;
			
		}
		if($GLOBALS["VERBOSE"]){echo "$hostname -> $reversed.in-addr.arpa $ipaddr\n";}
		$f[]="ptr-record=$reversed.in-addr.arpa.,\"$hostname\"";
		$f[]="address=/$hostname/$ipaddr";
		$results2=$q->QUERY_SQL("SELECT hostname FROM dnsmasq_cname WHERE recordid={$ligne["ID"]}");
		$aliases=array();
		while ($ligne2 = mysql_fetch_assoc($results2)) {
			if(trim($ligne2["hostname"])==null){continue;}
			$aliases[]=$ligne2["hostname"];
			if($GLOBALS["VERBOSE"]){echo "$hostname -> {$ligne2["hostname"]}\n";}
		}
		if(count($aliases)>0){ $f[]=str_replace(",,", ",", "cname=".@implode(",", $aliases).",$hostname"); }
		
	}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, $filename done\n";}
	@file_put_contents($filename,"\n");
	@file_put_contents("/etc/dnsmasq.hash.domains",serialize($build_hosts_array));
}

function cachednshosts_records($g){
	if(!is_array($g)){$g=array();}
	$build_hosts_array=unserialize(@file_get_contents("/etc/dnsmasq.hash.domains"));
	
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM dnsmasq_records";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){return $g;}
	@unlink("/etc/dnsmasq.hosts.cmdline");
	while ($ligne = mysql_fetch_assoc($results)) {
		$ipaddr=trim($ligne["ipaddr"]);
		$tr=explode(".", $ipaddr);
		krsort($tr);
		$reversed=@implode(".", $tr);
		$hostname=trim($ligne["hostname"]);
		if(strpos($hostname, ".")>0){
			$build_hosts_array[$q->GetFamilySites($hostname)]=true;
				
		}
		$g[]="--address=/$hostname/$ipaddr";
		$g[]="--ptr-record=$hostname,$ipaddr";
		$results2=$q->QUERY_SQL("SELECT hostname FROM dnsmasq_cname WHERE recordid={$ligne["ID"]}");
		$aliases=array();
		while ($ligne2 = mysql_fetch_assoc($results2)) {
			if(trim($ligne2["hostname"])==null){continue;}
			$aliases[]=$ligne2["hostname"];
			$g[]="--cname={$ligne2["hostname"]},$ipaddr";
			if($GLOBALS["VERBOSE"]){echo "$hostname -> {$ligne2["hostname"]}\n";}
		}
		
	}
	if(count($g)>0){@file_put_contents("/etc/dnsmasq.hosts.cmdline",@implode(" ", $g));}
	@file_put_contents("/etc/dnsmasq.hash.domains",serialize($build_hosts_array));
	return $g;
}

function GetGoogleWebsitesList(){
	$sock=new sockets();
	
	$DisableGoogleSSL=$sock->GET_INFO("DisableGoogleSSL");
	if(!is_numeric($DisableGoogleSSL)){$DisableGoogleSSL=0;}
	if($DisableGoogleSSL==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Goolge SSL is allowed\n";}
		return;
	}
	
	$q=new mysql_squid_builder();
	$arrayDN=$q->GetFamilySitestt(null,true);
	while (list ($table, $fff) = each ($arrayDN) ){
		if(preg_match("#\.(gov|gouv|gor|org|net|web|ac)\.#", "google.$table")){continue;}
		$array[]="www.google.$table";
		$array[]="google.$table";
	}
	
	$ipaddr=gethostbyname("nosslsearch.google.com");
	$ip=new IP();
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$OK=true;
	if(!$ip->isIPv4($ipaddr)){$OK=false;}
	if(!$OK){if($ip->isIPv6($ipaddr)){$OK=true;}}
	if(!$OK){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, Unable to resolve nosslsearch.google.com\n";}
		return;
	}
	
	while (list ($a, $googlesite) = each ($array) ){
		$f[]="address=/$googlesite/$ipaddr";
		$f[]="cname=$googlesite,nosslsearch.google.com";
	}
	
	return @implode("\n", $f);

}

function check_squid_inside(){
	$unix=new unix();
	$squid=$unix->LOCATE_SQUID_BIN();
	if(!is_file($squid)){return;}
	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	
	$ipClass=new IP();
	
	while (list ($index, $line) = each ($f)){
		if(preg_match("#^dns_nameservers\s+(.+)#", $line,$re)){
			$dns_nameservers=$re[1];
			break;
		}
	
	}
	
	$DNSARRAY=explode(" ",$dns_nameservers);
	while (list ($index, $line) = each ($DNSARRAY)){
		$line=trim($line);
		if($line==null){continue;}
		if(!$ipClass->isValid($line)){continue;}
		$AR[$line]=true;
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, DNS $line for Proxy\n";}
		$tt[]=$line;
	}
	
	
	if(isset($AR["127.0.0.1"])){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, DNS already set for Proxy\n";}
		return;
	}
		
	
	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	while (list ($index, $line) = each ($f)){
		if(preg_match("#^dns_nameservers\s+#", $line,$re)){
			$replaced_line=$line;
			$f[$index]="dns_nameservers 127.0.0.1 ".@implode($AR, " ");
			break;
		}
	
	}

	$php=$unix->LOCATE_PHP5_BIN();
	@file_put_contents("/etc/squid3/squid.conf", @implode("\n", $f));
	squid_admin_mysql(1,"Reconfigure Proxy service to relink DNS service","Detected `$replaced_line` in squid.conf",__FILE__,__LINE__);
	shell_exec("$php /usr/share/artica-postfix/exec.squid.php --kreconfigure");
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, Relink DNS done\n";}
	
}

function GetDNSSservers(){
	$q=new mysql_squid_builder();
	$ipClass=new IP();
	$sock=new sockets();
	
	
	$UtDNSEnable=intval($sock->GET_INFO("UtDNSEnable"));
	if($UtDNSEnable==1){
		$UtDNSArticaUser=json_decode(base64_decode($sock->GET_INFO("UtDNSArticaUser")));
		if($UtDNSArticaUser->success){
			$f[]="--server={$UtDNSArticaUser->prim}";
			$f[]="--server={$UtDNSArticaUser->sec}";
	
		}
	}
	
	
	if(!$q->TABLE_EXISTS("dns_servers")){
		if(count($f)>0){ return " --no-resolv ".@implode(" ", $f); }
		return null;
	}
	if($q->COUNT_ROWS("dns_servers")==0){
		if(count($f)>0){ return " --no-resolv ".@implode(" ", $f); }
		return null;
	}
	
	$sql="SELECT * FROM dns_servers ORDER by zOrder";
	$results=$q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		if(!$ipClass->isValid($ligne["dnsserver"])){continue;}
		$f[]="--server={$ligne["dnsserver"]}";
	}

	
	if(count($f)>0){
		return " --no-resolv ".@implode(" ", $f);
	}
	return null;
	
}


function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("dnsmasq");
	$ipClass=new IP();
	
	
	if(!is_file($Masterbin)){
		$unix->DEBIAN_INSTALL_PACKAGE("dnmasq");
		
	}

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, dnsmasq not installed\n";}
		return;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		return;
	}
	$sock=new sockets();
	$EnableLocalDNSMASQ=$sock->GET_INFO('EnableLocalDNSMASQ');
	$EnableDNSMASQ=$sock->GET_INFO("EnableDNSMASQ");
	$DHCPDEnableCacheDNS=$sock->GET_INFO("DHCPDEnableCacheDNS");
	$EnableLocalDNSMASQ=$sock->GET_INFO("EnableLocalDNSMASQ");
	
	
	if(!is_numeric($EnableDNSMASQ)){$EnableDNSMASQ=0;}
	if(!is_numeric($DHCPDEnableCacheDNS)){$DHCPDEnableCacheDNS=0;}
	if($DHCPDEnableCacheDNS==1){$EnableDNSMASQ=1;}
	if($EnableLocalDNSMASQ==1){$EnableDNSMASQ=1;}
	if(!is_numeric($EnableDNSMASQ)){$EnableDNSMASQ=0;}
	if($EnableLocalDNSMASQ==1){$DHCPDEnableCacheDNS=0;}
	
	$EnableDNSMASQ=$sock->dnsmasq_enabled();

	if($EnableDNSMASQ==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} EnableLocalDNSMASQ ($EnableLocalDNSMASQ)\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} EnableDNSMASQ ($EnableDNSMASQ)\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} DHCPDEnableCacheDNS ($DHCPDEnableCacheDNS)\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableDNSMASQ/DHCPDEnableCacheDNS)\n";}
		return;
	}

	$php5=$unix->LOCATE_PHP5_BIN();
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	$Interfaces=$unix->NETWORK_ALL_INTERFACES();
	$MYIPS=$unix->NETWORK_ALL_INTERFACES(true);
	while (list ($Interface, $ligne) = each ($Interfaces) ){
		$TR[]=$Interface;
	}
	
	$cf=new dnsmasq();
	
	
	if($DHCPDEnableCacheDNS==0){
		build(true);
		cache_dns_hosts();
		$DNsServers=GetDNSSservers();
		$getdomains=getdomains();
		
		$G[]="$Masterbin";
		$G[]="--local-ttl=3600";
		$G[]="--conf-file=/etc/dnsmasq.conf";
		$G[]="--pid-file=/var/run/dnsmasq.pid";
		$G[]="--strict-order";
		$G[]="--expand-hosts";
		$G[]="--resolv-file=/etc/dnsmasq.resolv.cache";
		if(is_file("/etc/dnsmasq.hosts.cache")){$G[]="--addn-hosts=/etc/dnsmasq.hosts.cache"; }
		if($DNsServers<>null){ $G[]=$DNsServers; }
		if($getdomains<>null){ $G[]=$getdomains; }
		$G[]="--cache-size=10240";
		$G[]="--log-facility=DAEMON";
		if($cf->main_array["log-queries"]=="yes"){
			$G[]="--log-queries";
		}
		
		$cmdline="$Masterbin --conf-file=/etc/dnsmasq.conf --pid-file=/var/run/dnsmasq.pid{$DNsServers}";
		$cmdline=@implode(" ", $G);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} CONFIGURATOR LINE:".__LINE__."\n";}
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} DHCPDEnableCacheDNS = $DHCPDEnableCacheDNS\n";}

	if($DHCPDEnableCacheDNS==1){
		$dhcp=new dhcpd(0,1);
		$TCP=new IP();
		
		if($TCP->isIPAddress($dhcp->DNS_1)){
			if(!isset($MYIPS[$dhcp->DNS_1])){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} DNS 1:$dhcp->DNS_1\n";}
				$CACHE[]="nameserver\t$dhcp->DNS_1";
			}
		}
		
		if($TCP->isIPAddress($dhcp->DNS_2)){
			if(!isset($MYIPS[$dhcp->DNS_2])){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} DNS 2:$dhcp->DNS_2\n";}
				$CACHE[]="nameserver\t$dhcp->DNS_2";
			}
		}		
		@file_put_contents("/etc/dnsmasq.resolv.cache", @implode("\n", $CACHE));
		$G=array();
		$G[]=$Masterbin;
		$G[]="--local-ttl=3600";
		$G[]="--conf-file=/etc/dnsmasq.conf.empty";
		$G[]="--pid-file=/var/run/dnsmasq.pid";
		$G[]="--strict-order";
		$G[]="--expand-hosts";
		$G[]="--resolv-file=/etc/dnsmasq.resolv.cache";
		if(is_file("/etc/dnsmasq.hosts.cache")){
			$G[]="--addn-hosts=/etc/dnsmasq.hosts.cache";
		}
		$G[]="--cache-size=10240";
		$G[]="--filterwin2k";
		$G[]="--log-facility=DAEMON";
		if($cf->main_array["log-queries"]=="yes"){ $G[]="--log-queries"; }
		$domain=getdomains();
		if($domain<>null){$G[]="$domain";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} CONFIGURATOR LINE:".__LINE__."\n";}
		
		
	}
	
	if($EnableLocalDNSMASQ==1){
		$GA=array();
		check_squid_inside();
		$LocalDNSMASQItems=$sock->GET_INFO('LocalDNSMASQItems');
		if(!is_numeric($LocalDNSMASQItems)){$LocalDNSMASQItems=250000;}
		$G=array();
		$G[]=$Masterbin;
		$G[]="--conf-file=/etc/dnsmasq.conf.empty";
		$G[]="--resolv-file=/etc/dnsmasq.resolv.cache";
		$G[]="--pid-file=/var/run/dnsmasq.pid";
		$G[]="--strict-order";
		$G[]="--expand-hosts";
		//$G[]="--all-servers";
		$G[]="--local-ttl=3600";
		if(is_file("/etc/dnsmasq.hosts.cache")){
			$G[]="--addn-hosts=/etc/dnsmasq.hosts.cache";
		}
		$G[]="--cache-size=$LocalDNSMASQItems";
		$G[]="--log-facility=DAEMON";
		$G[]="--filterwin2k";
		if($cf->main_array["log-queries"]=="yes"){ $G[]="--log-queries"; }
		
		
		//$G[]="--log-queries";
		$squid=new squidbee();
		$DNSArray=$squid->dns_nameservers(true);
		if(count($DNSArray)>0){
			while (list ($num, $val) = each ($DNSArray) ){
				$val=trim($val);
				if(!$ipClass->isValid($val)){
					if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} DNS:`$val` Wrong value\n";}
					continue;
				}
				if(isset($MYIPS[$val])){continue;}
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} DNS:`$val`\n";}
				$GA[]="nameserver $val";
			}
		}
		@file_put_contents("/etc/dnsmasq.resolv.cache", @implode("\n", $GA));
		
		$domain=getdomains();
		if($domain<>null){$G[]="$domain";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} CONFIGURATOR LINE:".__LINE__."\n";}
		
		
		
	}
	$G=cachednshosts_records($G);
	$cmdline=@implode(" ", $G);
	
	
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} ".count($G)." token(s)\n";
	while (list ($num, $val) = each ($G) ){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} token: $val\n";}
	if(!is_file("/etc/dnsmasq.conf.empty")){@file_put_contents("/etc/dnsmasq.conf.empty", "\n");}
	if(!is_file("/etc/dnsmasq.hosts.cache")){@file_put_contents("/etc/dnsmasq.hosts.cache", "\n");}
	
	fuser_port();
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service {".__LINE__."}\n";}
	shell_exec($cmdline);

	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		shell_exec("$nohup /etc/init.d/monit reconfigure >/dev/null 2>&1 &");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}

	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmdline\n";}
	}


}

function build_hosts($aspid=false){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	if(!$aspid){
		if(!$GLOBALS["FORCE"]){
			$pid=@file_get_contents($pidfile);
			if($unix->process_exists($pid,basename(__FILE__))){
				writelogs("Already executed pid $pid, aborting...","MAIN",__FILE__,__LINE__);
				die();
			}
	
			$time=$unix->file_time_min($pidtime);
			if($time<1){
				writelogs("Current {$time}Mn Requested 1mn, schedule this task","MAIN",__FILE__,__LINE__);
				$unix->THREAD_COMMAND_SET($unix->LOCATE_PHP5_BIN()." ".__FILE__);
				die();
			}
		}
	}	
	
	
	$HOST_ARRAY=array();
	$array=array();
	$conf=new dnsmasq(true);
	$conf->ldap_addesses();
	$conf->ParseAddress();
	
	$IpClass=new IP();
	if(!is_array($conf->array_address)){
		if(count($conf->array_address)>0){
			while (list ($host, $ip) = each ($conf->array_address) ){
				if($GLOBALS["VERBOSE"]){echo "ADDING HOST: $host - $ip\n";}
				$MAIN[$host]=$ip;
				
			}
		}
	}
	
	
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM dnsmasq_records";
	$results=$q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		$ipaddr=trim($ligne["ipaddr"]);
		$hostname=trim($ligne["hostname"]);
		if($GLOBALS["VERBOSE"]){echo "ADDING HOST: $hostname - $ipaddr\n";}
		$MAIN[$hostname]=$ipaddr;
	}
	
	$q=new mysql();
	$results=$q->QUERY_SQL("SELECT * FROM dhcpd_fixed","artica_backup");
	if(!$q->ok){return;}
	$c=0;
	while ($ligne = mysql_fetch_assoc($results)) {
		$MAIN[$ligne["hostname"]]=$ligne["ipaddr"];
		$c++;
	}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $c DHCP hosts entries\n";}
	while (list ($host, $ip) = each ($MAIN) ){
		$host=trim(strtolower($host));
		if($GLOBALS["VERBOSE"]){echo "ADDING HOST: $host - $ip\n";}
		if($IpClass->isValid($host)){continue;}
		if($host==null){continue;}
		$alias=null;
		$tr=explode(".",$host);
		if(count($tr)>0){
			$alias=$tr[0];
			unset($tr[0]);
			$domain=@implode(".", $tr);
			$array[$domain]=true;
		}
		$HOST_ARRAY[]="$ip\t$host\t$alias";
		
	}
	
	
	@unlink("/etc/dnsmasq.hosts.cache");
	if(count($HOST_ARRAY)>0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} ".count($HOST_ARRAY)." hosts entries\n";}
		@file_put_contents("/etc/dnsmasq.hosts.cache", @implode("\n", $HOST_ARRAY)."\n");
	}
	
	while (list ($domain, $none) = each ($array) ){
		$domainZ[]=$domain;
	}
	
	@file_put_contents("/etc/dnsmasq.hash.domains", serialize($domainZ));
	
	if(!$aspid){
		$pid=PID_NUM();
		$kill=$unix->find_program("kill");
		shell_exec("kill -HUP $pid");
		
	}
	
}

function fuser_port(){
	$unix=new unix();
	$kill=$unix->find_program("kill");
	$PIDS=$unix->PIDOF_BY_PORT_UDP("53");
	if(count($PIDS)==0){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} 0 PID listens 53...\n";}

		return;}
		while (list ($pid, $b) = each ($PIDS) ){
			if($unix->process_exists($pid)){
				$cmdline=@file_get_contents("/proc/$pid/cmdline");
				if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} killing PID $pid that listens 53 UDP port\n";}
				if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmdline\n";}
				unix_system_kill_force($pid);
			}
		}
}

function ldap_domains(){
	$ldap=new clladp();
	$build_hosts_array=unserialize(@file_get_contents("/etc/dnsmasq.hash.domains"));
	$domains=$ldap->hash_get_all_domains();
	while (list ($num, $ligne) = each ($domains) ){
		$build_hosts_array[trim(strtolower($num))]=true;
	
	}	
	@file_put_contents("/etc/dnsmasq.hash.domains", serialize($build_hosts_array));
	
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM dnsmasq_blacklist";
	$results=$q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		$domain=$ligne["hostname"];
		$domain=trim(strtolower($domain));
		$t[$ligne["hostname"]]=true;
		
	}
	@file_put_contents("/etc/dnsmasq.hash.domains-blacklist", serialize($t));
}

function isDomainValid($domain){
	$ipClass=new IP();
	if(!isset($GLOBALS["BLACKS_DOMAINS"])){$GLOBALS["BLACKS_DOMAINS"]=unserialize(@file_get_contents("/etc/dnsmasq.hash.domains-blacklist"));}
	
	$domain=trim(strtolower($domain));
	if($domain=="artica.fr"){return null;}
	$domain=str_replace("$", "", $domain);
	if(isset($GLOBALS["BLACKS_DOMAINS"][$domain])){return null;}
	if($ipClass->isIPAddress($domain)){return null;}
	if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+$#", $domain)){return null;}
	return $domain;
}


function getdomains(){
	$array=array();
	$sock=new sockets();
	$build_hosts_array=unserialize(@file_get_contents("/etc/dnsmasq.hash.domains"));
	
	$EnableDHCPServer=$sock->GET_INFO("EnableDHCPServer");
	if(!is_numeric($EnableDHCPServer)){$EnableDHCPServer=0;}
	if($EnableDHCPServer==1){
		$dhcp=new dhcpd();
		if($dhcp->ddns_domainname<>null){
			$array[$dhcp->ddns_domainname]=true;
		}
	}
	
	$array["localdomain"]=true;
	$array["local"]=true;
	
	
	
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} local domains from hosts:".count($build_hosts_array)."\n";}
	if(count($build_hosts_array)>0){
		while (list ($domain, $ligne) = each ($build_hosts_array) ){
			$domain=isDomainValid($domain);
			if($domain==null){continue;}
			$array[$domain]=true;
		}
	}
	
	$unix=new unix();
	$resolv=new resolv_conf();
	$myhostname=$unix->hostname_g();
	
	
	$tt=explode(".",$myhostname);
	unset($tt[0]);
	$domain=@implode(".", $tt);	

	if($domain<>null){$array[trim(strtolower($domain))]=true;}
	if($resolv->MainArray["DOMAINS1"]<>null){$array[trim(strtolower($resolv->MainArray["DOMAINS1"]))]=true;}
	if($resolv->MainArray["DOMAINS2"]<>null){$array[trim(strtolower($resolv->MainArray["DOMAINS2"]))]=true;}
	if($resolv->MainArray["DOMAINS3"]<>null){$array[trim(strtolower($resolv->MainArray["DOMAINS3"]))]=true;}
	
	$G=array();
	while (list ($num, $ligne) = each ($array) ){
		if(isset($ff[$num])){continue;}
		if(is_numeric($num)){continue;}
		$num=isDomainValid($num);
		if($num==null){continue;}
		$ff[$num]=true;
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} answer to local domain: `$num`\n";}
		$G[]="--local=/$num/";
	}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} ".count($G)." local domains\n";}
	
	if(count($G)>0){return @implode(" ", $G);}
}

function PID_NUM(){

	$unix=new unix();
	
	$pid=$unix->get_pid_from_file("/var/run/dnsmasq.pid");
	if($unix->process_exists($pid)){return $pid;}
	
	$Masterbin=$unix->find_program("dnsmasq");
	return $unix->PIDOF($Masterbin);

}

function restart() {
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	stop(true);
	sleep(1);
	build(true);
	start(true);

}
function build($aspid=false){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".time";

	if(!$aspid){
		if(!$GLOBALS["FORCE"]){
			
			$pid=@file_get_contents($pidfile);
			if($unix->process_exists($pid,basename(__FILE__))){
				writelogs("Already executed pid $pid, aborting...","MAIN",__FILE__,__LINE__);
				die();
			}
			
			$time=$unix->file_time_min($pidtime);
			if($time<2){
				if($time>0){
					if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Current {$time}Mn Requested 2mn, schedule this task\n";}
					writelogs("Current {$time}Mn Requested 2mn, schedule this task","MAIN",__FILE__,__LINE__);
					$unix->THREAD_COMMAND_SET($unix->LOCATE_PHP5_BIN()." ".__FILE__);
				}
				die();
			}
		}
		
		@unlink($pidtime);
		@file_put_contents($pidtime, time());
		@file_put_contents($pidfile, getmypid());
		
		}




	$users=new settings_inc();
	if(!$users->dnsmasq_installed){
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} is not installed, aborting\n";
		writelogs("DNSMasq is not installed, aborting","MAIN",__FILE__,__LINE__);
		return;
	}

	$sock=new sockets();
	$EnableDNSMASQ=$sock->dnsmasq_enabled();
	$DHCPDEnableCacheDNS=$sock->GET_INFO("DHCPDEnableCacheDNS");
	if(!is_numeric($DHCPDEnableCacheDNS)){$DHCPDEnableCacheDNS=0;}
	if($DHCPDEnableCacheDNS==1){$EnableDNSMASQ=1;}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} DHCPDEnableCacheDNS = $DHCPDEnableCacheDNS\n";}
	cache_dns_hosts();
	build_hosts(true);
	ldap_domains();
	cachednshosts_records(array());
	if($DHCPDEnableCacheDNS==1){ echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} only as caching DNS\n"; return;}
	
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	$DNSMasqUseStatsAppliance=$sock->GET_INFO("DNSMasqUseStatsAppliance");
	if(!is_numeric($DNSMasqUseStatsAppliance)){$DNSMasqUseStatsAppliance=0;}	
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
	if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}
	if($UnlockWebStats==1){$EnableRemoteStatisticsAppliance=0;}
	if($EnableDNSMASQ==0){writelogs("DNSMasq is not enabled, aborting","MAIN",__FILE__,__LINE__);return;}

	if($EnableRemoteStatisticsAppliance==1){
		if($DNSMasqUseStatsAppliance==1){
			writelogs("DNSMasq -> use Web statistics Appliance...","MAIN",__FILE__,__LINE__);
			UseStatsAppliance();
			die();
		}
	}
	
	$t=array();
	$t[]=GetGoogleWebsitesList();
	
	
	@file_put_contents("/etc/dnsmasq.conf.empty",@implode("\n", $t));


	$dnsmasq=new dnsmasq();
	$dnsmasq->SaveConfToServer();
	$resolv=new resolv_conf();
	$resolvFile=$dnsmasq->main_array["resolv-file"];
	$resolvConfBuild=$resolv->build();
	@file_put_contents($resolvFile,$resolvConfBuild);
	@mkdir("/var/run/dnsmasq",0755,true);
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Saving /var/run/dnsmasq/resolv.conf\n";
	@file_put_contents("/var/run/dnsmasq/resolv.conf",$resolvConfBuild);
	$unix->THREAD_COMMAND_SET($unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.initslapd.php dnsmasq");
	if($EnableWebProxyStatsAppliance==1){notify_remote_proxys_dnsmasq();}
}

function UseStatsAppliance(){
	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
	$sock=new sockets();
	$unix=new unix();
	$tempdir=$unix->TEMP_DIR();
	$RemoteStatisticsApplianceSettings=unserialize(base64_decode($sock->GET_INFO("RemoteStatisticsApplianceSettings")));
	if(!is_numeric($RemoteStatisticsApplianceSettings["SSL"])){$RemoteStatisticsApplianceSettings["SSL"]=1;}
	if(!is_numeric($RemoteStatisticsApplianceSettings["PORT"])){$RemoteStatisticsApplianceSettings["PORT"]=9000;}
	$GLOBALS["REMOTE_SSERVER"]=$RemoteStatisticsApplianceSettings["SERVER"];
	$GLOBALS["REMOTE_SPORT"]=$RemoteStatisticsApplianceSettings["PORT"];
	$GLOBALS["REMOTE_SSL"]=$RemoteStatisticsApplianceSettings["SSL"];
	$unix=new unix();
	$hostname=$unix->hostname_g();	
	if($GLOBALS["REMOTE_SSL"]==1){$refix="https";}else{$refix="http";}
	$uri="$refix://{$GLOBALS["REMOTE_SSERVER"]}:{$GLOBALS["REMOTE_SPORT"]}/ressources/databases/dnsmasq.conf";
	$curl=new ccurl($uri,true);
	if(!$curl->GetFile("$tempdir/dnsmasq.conf")){ufdbguard_admin_events("Failed to download dnsmasq.conf aborting `$curl->error`",__FUNCTION__,__FILE__,__LINE__,"dns-compile");return;}		
	
	$mv=$unix->find_program("mv");
	$cp=unix-find_program("cp");
	$chmod=$unix->find_program("chmod");
	
	shell_exec("$mv $tempdir/dnsmasq.conf /etc/dnsmasq.conf");	
	shell_exec("cp /etc/dnsmasq.conf /etc/artica-postfix/settings/Daemons/DnsMasqConfigurationFile");
	$dnsmasqbin=$unix->find_program("dnsmasq");
	
	if(is_file($dnsmasqbin)){
		$pid=$unix->PIDOF($dnsmasqbin);
		if(is_numeric($pid)){
			echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} reloading PID:`$pid`\n";
			$kill=$unix->find_program("kill");
			unix_system_HUP($pid);
		}
	}	
}

function testsRESOLV(){
	$resolv=new resolv_conf();
	echo $resolvConfBuild=$resolv->build()."\n";
	
}

function reload_dnsmasq(){
	$sock=new sockets();
	$EnableDNSMASQ=$sock->GET_INFO("EnableDNSMASQ");
	if(!is_numeric($EnableDNSMASQ)){$EnableDNSMASQ=0;}
	if($EnableDNSMASQ==0){
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} unable to reload DnsMASQ (not enabled)\n";
		return ;
	}
	$unix=new unix();
	
	$EnableChilli=$sock->GET_INFO("EnableChilli");
	$chilli=$unix->find_program("chilli");
	
	
	if(!is_numeric($EnableChilli)){$EnableChilli=0;}
	if(is_file($chilli)){
		if($EnableChilli==1){
			echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} HotSpot is enabled, cannot use this instance\n";
			return;
		}	
	}
	
	$dnsmasqbin=$unix->find_program("dnsmasq");
	if(is_file(!$dnsmasqbin)){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} unable to reload DnsMASQ (not such dsnmasq binary)\n";return;}
	$pid=$unix->PIDOF($dnsmasqbin);
	if(!is_numeric($pid)){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} unable to reload DnsMASQ (not running)\n";return;}
	
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} reloading PID:`$pid`\n";
	$kill=$unix->find_program("kill");
	unix_system_HUP($pid);
}

function varrun(){
	if(!is_file("/var/run/dnsmasq/resolv.conf")){
		echo "Starting......: ".date("H:i:s")." /var/run/dnsmasq/resolv.conf no such file\n";
		ResolvConfChecks();
		return;
	}
	$f=explode("\n",@file_get_contents("/var/run/dnsmasq/resolv.conf"));
	$configured=false;
	while (list ($dir, $line) = each ($f) ){
		if(preg_match("#^nameserver.+#",$line, $re)){$configured=true;}
	}
	
	if(!$configured){
		$resolv=new resolv_conf();
		$resolvConfBuild=$resolv->build();
		echo "Starting......: ".date("H:i:s")." /var/run/dnsmasq/resolv.conf not configured, write it...\n";
		@file_put_contents("/var/run/dnsmasq/resolv.conf", $resolvConfBuild);
		reload_dnsmasq();
	}
	echo "Starting......: ".date("H:i:s")." ResolvConfChecks()\n";
	ResolvConfChecks();
}

function ResolvConfChecks(){
	$unix=new unix();
	$sock=new sockets();
	$EnableDNSMASQ=$sock->GET_INFO("EnableDNSMASQ");
	if(!is_numeric($EnableDNSMASQ)){$EnableDNSMASQ=0;}	
	$f=file("/etc/resolv.conf");
	$dnsmasqbin=$unix->find_program("dnsmasq");
	$configured=false;
	while (list ($dir, $line) = each ($f) ){
		if(preg_match("#^nameserver.+#",$line, $re)){$configured=true;}
	}
	
	
	if($configured){return;}
		
	if(file_exists($dnsmasqbin)){
		if($EnableDNSMASQ==0){
			$resolv=new resolv_conf();
			$resolvConfBuild=$resolv->build();
			echo "Starting......: ".date("H:i:s")." /etc/resolv.conf not configured, write it...\n";
			@file_put_contents("/etc/resolv.conf", $resolvConfBuild);
		}
		if($EnableDNSMASQ==1){
			reset($f);
			$f[]="nameserver 127.0.0.1";
			echo "Starting......: ".date("H:i:s")." /etc/resolv.conf not configured, write it...\n";
			@file_put_contents("/etc/resolv.conf", $resolvConfBuild);			
			reload_dnsmasq();
		}
	}else{
		$resolv=new resolv_conf();
		$resolvConfBuild=$resolv->build();
		echo "Starting......: ".date("H:i:s")." /etc/resolv.conf not configured, write it...\n";
		@file_put_contents("/etc/resolv.conf", $resolvConfBuild);
	}
	
	
}


function notify_remote_proxys_dnsmasq(){
	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM squidservers";
	$results=$q->QUERY_SQL($sql);
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$server=$ligne["ipaddr"];
		$port=$ligne["port"];
		writelogs("remote server $server:$port",__FUNCTION__,__FILE__,__LINE__);
		if(!is_numeric($port)){continue;}
		$refix="https";
		$uri="$refix://$server:$port/squid.stats.listener.php";
		$curl=new ccurl($uri,true);
		$curl->parms["CHANGE_CONFIG"]="DNSMASQ";
		if(!$curl->get()){squidstatsApplianceEvents("$server:$port","FAILED Notify change it`s configuration $curl->error for DNSMASQ");continue;}
		if(preg_match("#<ANSWER>OK</ANSWER>#is",$curl->data)){squidstatsApplianceEvents("$server:$port","SUCCESS to notify change it`s configuration for DNSMASQ");continue;}
		squidstatsApplianceEvents("$server:$port","FAILED Notify change it`s configuration $curl->data for DNSMASQ");
	}
}

function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		return;
	}
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");




	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}

}

function remove_service($eth){
	$INITD_PATH="/etc/init.d/dnsmasq-$eth";
	$INIT=basename($INITD_PATH);
	shell_exec("$INITD_PATH stop");
	
	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f $INIT remove >/dev/null 2>&1");
		@unlink($INITD_PATH);
	}
	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --del $INIT >/dev/null 2>&1");
		@unlink($INITD_PATH);

	}	
	
}


function install_service($eth){
	

	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/dnsmasq-$eth";
	$php5script=basename(__FILE__);
	$daemonbinLog="DNSMasq for $eth";
	$daemon_path=$unix->find_program("dnsmasq");
	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         dnsmasq-$eth";
	$f[]="# Required-Start:    \$local_fs \$syslog";
	$f[]="# Required-Stop:     \$local_fs \$syslog";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: $daemonbinLog";
	$f[]="# chkconfig: - 80 75";
	$f[]="# description: $daemonbinLog";
	$f[]="### END INIT INFO";
	$f[]="DAEMON=$daemon_path";
	$f[]="[ -x \"\$DAEMON\" ] || exit 0";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start-eth $eth --script \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop-eth $eth --script \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart-eth $eth --script \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	
	
	echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
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

