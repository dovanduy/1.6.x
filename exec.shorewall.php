<?php
include_once(dirname(__FILE__)."/ressources/class.mysql.shorewall.inc");
include_once(dirname(__FILE__)."/ressources/class.shorewall.stats.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
$GLOBALS["OUTPUT"]=null;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
$GLOBALS["TITLENAME"]="Firewall configurator";


if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;build();exit;}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit;}
if($argv[1]=="--hourly"){$GLOBALS["OUTPUT"]=true;hourly();exit;}

function build(){
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, building configuration.\n";}
	@mkdir("/var/lib/shorewall",0755,true);
	$unix=new unix();
	if(!isset($GLOBALS["INTERFACES"])){$GLOBALS["INTERFACES"]=$unix->NETWORK_ALL_INTERFACES();}
	
	
	
	shorewall_conf();
	build_providers();
	build_zones();
	build_interfaces();
	build_policies();
	build_rules();
	build_rtrules();
	build_masq();
	
	CheckConf();
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, BUILD DONE.\n";}
}

function restart(){
	$unix=new unix();
	$shorewall=$unix->find_program("shorewall");
	if(!is_file($shorewall)){return;}	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, Already task running PID $oldpid since {$time}mn\n";}
		return;
	}
	stop(true);
	start(true);
	if(is_file("/etc/init.d/ssh")){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, restarting SSH service.\n";}
		system("/etc/init.d/ssh restart");
	}
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, ACTION DONE.\n";}
	
}

function isRunning(){
	$unix=new unix();
	$shorewall=$unix->find_program("shorewall");
	if(!is_file($shorewall)){return;}
	exec("$shorewall status 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#State:Started#i", $ligne)){return true;}
		if(preg_match("#State:Stopped#i", $ligne)){return false;}
		
	}
	
}

function stop($aspid=false){
	
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, Already task running PID $oldpid since {$time}mn\n";}
			return;
		}
	}
	$shorewall=$unix->find_program("shorewall");
	if(!is_file($shorewall)){return;}
	
	if(!isRunning()){
		if($GLOBALS["OUTPUT"]){echo "Stopping......:[INIT]: {$GLOBALS["TITLENAME"]}, already stopped...\n";}
		return;
	}	
	
	
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, Stopping smoothly...\n";}
	$cmd="$shorewall stop 2>&1";
	exec($cmd,$results);

	while (list ($key, $line) = each ($results) ){
		$line=trim($line);
		if($line==null){continue;}
	
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, $line\n";}
	}
	
	if(!isRunning()){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, success...\n";}
		return;
	}	
	
}
function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("shorewall");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, shorewall not installed\n";}
		return;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $oldpid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	

	if(isRunning()){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started...\n";}
		return;
	}
	
	$cmd="$Masterbin start 2>&1";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}

	exec($cmd,$results);
	
	while (list ($key, $line) = each ($results) ){
		$line=trim($line);
		if($line==null){continue;}
	
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, $line\n";}
	}
	
	if(isRunning()){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, success...\n";}
		return;
	}
	
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, Failed\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, $cmd\n";}
	


}

function shorewall_conf(){
	
	$f[]="#";
	$f[]="#  Shorewall Version 4 -- /etc/shorewall/shorewall.conf";
	$f[]="#";
	$f[]="#  For information about the settings in this file, type \"man shorewall.conf\"";
	$f[]="#";
	$f[]="#  Manpage also online at http://www.shorewall.net/manpages/shorewall.conf.html";
	$f[]="###############################################################################";
	$f[]="#		       S T A R T U P   E N A B L E D";
	$f[]="###############################################################################";
	$f[]="";
	$f[]="STARTUP_ENABLED=Yes";
	$f[]="";
	$f[]="###############################################################################";
	$f[]="#		              V E R B O S I T Y";
	$f[]="###############################################################################";
	$f[]="";
	$f[]="VERBOSITY=1";
	$f[]="";
	$f[]="###############################################################################";
	$f[]="#		                L O G G I N G";
	$f[]="###############################################################################";
	$f[]="";
	$f[]="BLACKLIST_LOGLEVEL=";
	$f[]="LOG_MARTIANS=Yes";
	$f[]="LOG_VERBOSITY=2";
	$f[]="LOGALLNEW=";
	$f[]="LOGFILE=/var/log/messages";
	$f[]="LOGFORMAT=\"Shorewall:%s:%s:\"";
	$f[]="LOGTAGONLY=No";
	$f[]="LOGLIMIT=";
	$f[]="MACLIST_LOG_LEVEL=info";
	$f[]="RELATED_LOG_LEVEL=";
	$f[]="SFILTER_LOG_LEVEL=info";
	$f[]="SMURF_LOG_LEVEL=info";
	$f[]="STARTUP_LOG=/var/log/shorewall-init.log";
	$f[]="TCP_FLAGS_LOG_LEVEL=info";
	$f[]="";
	$f[]="###############################################################################";
	$f[]="#	L O C A T I O N	  O F	F I L E S   A N D   D I R E C T O R I E S";
	$f[]="###############################################################################";
	$f[]="";
	$f[]="CONFIG_PATH=\"\${CONFDIR}/shorewall:\${SHAREDIR}/shorewall\"";
	$f[]="GEOIPDIR=/usr/share/xt_geoip/LE";
	$f[]="IPTABLES=";
	$f[]="IP=";
	$f[]="IPSET=";
	$f[]="LOCKFILE=";
	$f[]="MODULESDIR=";
	$f[]="PATH=\"/sbin:/bin:/usr/sbin:/usr/bin:/usr/local/bin:/usr/local/sbin\"";
	$f[]="PERL=/usr/bin/perl";
	$f[]="RESTOREFILE=restore";
	$f[]="SHOREWALL_SHELL=/bin/sh";
	$f[]="SUBSYSLOCK=\"\"";
	$f[]="TC=";
	$f[]="";
	$f[]="###############################################################################";
	$f[]="#		D E F A U L T   A C T I O N S / M A C R O S";
	$f[]="###############################################################################";
	$f[]="";
	$f[]="ACCEPT_DEFAULT=none";
	$f[]="DROP_DEFAULT=Drop";
	$f[]="NFQUEUE_DEFAULT=none";
	$f[]="QUEUE_DEFAULT=none";
	$f[]="REJECT_DEFAULT=Reject";
	$f[]="";
	$f[]="###############################################################################";
	$f[]="#                        R S H / R C P  C O M M A N D S";
	$f[]="###############################################################################";
	$f[]="";
	$f[]="RCP_COMMAND='scp \${files} \${root}@\${system}:\${destination}'";
	$f[]="RSH_COMMAND='ssh \${root}@\${system} \${command}'";
	$f[]="";
	$f[]="###############################################################################";
	$f[]="#			F I R E W A L L	  O P T I O N S";
	$f[]="###############################################################################";
	$f[]="";
	$f[]="ACCOUNTING=Yes";
	$f[]="ACCOUNTING_TABLE=filter";
	$f[]="ADD_IP_ALIASES=Yes";
	$f[]="ADD_SNAT_ALIASES=No";
	$f[]="ADMINISABSENTMINDED=Yes";
	$f[]="AUTO_COMMENT=Yes";
	$f[]="AUTOMAKE=Yes";
	$f[]="BLACKLISTNEWONLY=Yes";
	$f[]="NAT_ENABLED=Yes";
	$f[]="MANGLE_ENABLED=Yes";
	$f[]="CLAMPMSS=No";
	$f[]="CLEAR_TC=Yes";
	$f[]="COMPLETE=No";
	$f[]="DELETE_THEN_ADD=Yes";
	$f[]="DETECT_DNAT_IPADDRS=No";
	$f[]="DISABLE_IPV6=No";
	$f[]="DONT_LOAD=";
	$f[]="DYNAMIC_BLACKLIST=Yes";
	$f[]="EXPAND_POLICIES=Yes";
	$f[]="EXPORTMODULES=Yes";
	$f[]="FASTACCEPT=No";
	$f[]="FORWARD_CLEAR_MARK=";
	$f[]="IMPLICIT_CONTINUE=No";
	$f[]="IPSET_WARNINGS=Yes";
	$f[]="IP_FORWARDING=Yes";
	$f[]="KEEP_RT_TABLES=No";
	$f[]="LEGACY_FASTSTART=Yes";
	$f[]="LOAD_HELPERS_ONLY=No";
	$f[]="MACLIST_TABLE=filter";
	$f[]="MACLIST_TTL=";
	$f[]="MAPOLDACTIONS=No";
	$f[]="MARK_IN_FORWARD_CHAIN=No";
	$f[]="MODULE_SUFFIX=ko";
	$f[]="MULTICAST=No";
	$f[]="MUTEX_TIMEOUT=60";
	$f[]="NULL_ROUTE_RFC1918=No";
	$f[]="OPTIMIZE=0";
	$f[]="OPTIMIZE_ACCOUNTING=No";
	$f[]="REQUIRE_INTERFACE=No";
	$f[]="RESTORE_DEFAULT_ROUTE=No";
	$f[]="RETAIN_ALIASES=No";
	$f[]="ROUTE_FILTER=Yes";
	$f[]="SAVE_IPSETS=No";
	$f[]="TC_ENABLED=Internal";
	$f[]="TC_EXPERT=No";
	$f[]="TC_PRIOMAP=\"2 3 3 3 2 3 1 1 2 2 2 2 2 2 2 2\"";
	$f[]="TRACK_PROVIDERS=No";
	$f[]="USE_DEFAULT_RT=No";
	$f[]="USE_PHYSICAL_NAMES=No";
	$f[]="ZONE2ZONE=2";
	$f[]="";
	$f[]="###############################################################################";
	$f[]="#			P A C K E T   D I S P O S I T I O N";
	$f[]="###############################################################################";
	$f[]="";
	$f[]="BLACKLIST_DISPOSITION=DROP";
	$f[]="MACLIST_DISPOSITION=REJECT";
	$f[]="RELATED_DISPOSITION=ACCEPT";
	$f[]="SMURF_DISPOSITION=DROP";
	$f[]="SFILTER_DISPOSITION=DROP";
	$f[]="TCP_FLAGS_DISPOSITION=DROP";
	$f[]="";
	$f[]="################################################################################";
	$f[]="#			P A C K E T  M A R K  L A Y O U T";
	$f[]="################################################################################";
	$f[]="";
	$f[]="TC_BITS=";
	$f[]="PROVIDER_BITS=";
	$f[]="PROVIDER_OFFSET=";
	$f[]="MASK_BITS=";
	$f[]="ZONE_BITS=0";
	$f[]="";
	$f[]="################################################################################";
	$f[]="#                            L E G A C Y  O P T I O N";
	$f[]="#                      D O  N O T  D E L E T E  O R  A L T E R";
	$f[]="################################################################################";
	$f[]="IPSECFILE=zones";
	$f[]="";
	@mkdir("/etc/shorewall",0755,true);
	@file_put_contents("/etc/shorewall/shorewall.conf", @implode("\n", $f));
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, /etc/shorewall/shorewall.conf done\n";}
	
}

function CheckConf(){
	$unix=new unix();
	$shorewall=$unix->find_program("shorewall");
	if(!is_file($shorewall)){return;}
	
	exec("$shorewall check 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, $ligne\n";}
		if(preg_match("#ERROR:\s+#", $ligne)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, FATAL $ligne\n";}
			return false;
		}
	}
	
	return true;
	
}

function build_masq(){
	$f[]="#";
	$f[]="# Shorewall version 4 - Masq file - " .date("Y-m-d H:i:s")." by Artica";
	$f[]="#";
	$f[]="# For information about entries in this file, type \"man shorewall-masq\"";
	$f[]="#";
	$f[]="# The manpage is also online at";
	$f[]="# http://www.shorewall.net/manpages/shorewall-masq.html";
	$f[]="#";
	$f[]="######################################################################################################";
	$f[]="#INTERFACE:DEST		SOURCE		ADDRESS		PROTO	PORT(S)	IPSEC	MARK	USER/	SWITCH";
	$f[]="#";
	
	$q=new mysql_shorewall();
	$sql="SELECT * FROM `fw_masq`";
	$results=$q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		$eth=$ligne["eth"];
		$INTERFACE=$ligne["INTERFACE"];
		$SOURCE=$ligne["SOURCE"];
		$ADDRESS=$ligne["ADDRESS"];
		$PROTO=$ligne["PROTO"];
		$PORT=$ligne["PORT"];
		$f[]="$INTERFACE\t$SOURCE\t$ADDRESS\t$PROTO\t$PORT";
	}
		$f[]="";
		$f[]="#LAST LINE - ADD YOUR ENTRIES ABOVE THIS ONE - DO NOT REMOVE";
		@file_put_contents("/etc/shorewall/masq", @implode("\n", $f));
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, /etc/shorewall/masq done\n";}
			
}


function build_interfaces(){
	$unix=new unix();
	$f[]="#";
	$f[]="# Shorewall version 4 - Interfaces File - " .date("Y-m-d H:i:s")." by Artica";
	$f[]="#";
	$f[]="# For information about entries in this file, type \man shorewall-interfaces\"";
	$f[]="#";
	$f[]="# The manpage is also online at";
	$f[]="# http://www.shorewall.net/manpages/shorewall-interfaces.html";
	$f[]="#";
	$f[]="###############################################################################";
	$f[]="#FORMAT 2";
	$f[]="###############################################################################";
	$f[]="#ZONE		INTERFACE		OPTIONS";
	$f[]="-            lo               -                 -";

	
	$q=new mysql_shorewall();
	$sql="SELECT * FROM `fw_interfaces`";
	$INTERFACE_OPT=$q->INTERFACE_OPT;
	$results=$q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		$eth=$ligne["eth"];
		if(!$unix->is_interface_available($eth)){continue;}
		$broadcats="detect";
		$OPTIONS=null;
		$ligne_zone=mysql_fetch_array($q->QUERY_SQL("SELECT zone FROM fw_zones WHERE eth='$eth'"));
		$int=new system_nic($eth);
		$ZONE=$int->netzone;
		if($ZONE==null){$ZONE="-";}
		reset($INTERFACE_OPT);
		$fa=array();
		if(is_role($eth,"DHCP")){$ligne["dhcp"]=1;}
		
		
		while (list ($key, $value) = each ($INTERFACE_OPT) ){
			
			if(!isset($ligne[$key])){continue;}
			if($ligne[$key]==1){$fa[]=$key;}
		}
		if(count($fa)>0){$OPTIONS=@implode(",", $fa);}
		$f[]="$ZONE\t$eth\tdetect\t$OPTIONS";
	}
	
	$f[]="";
	$f[]="#LAST LINE - ADD YOUR ENTRIES ABOVE THIS ONE - DO NOT REMOVE";
	@file_put_contents("/etc/shorewall/interfaces", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, /etc/shorewall/interfaces done\n";}
	
}

function is_role($nic,$role){
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT zmd5 FROM nics_roles WHERE nic='$nic' AND role='$role'","artica_backup"));
	if($ligne["zmd5"]<>null){return true;}
	return false;
	
}

function build_zones(){
	$unix=new unix();
	$q=new mysql_shorewall();
	$f[]="# Shorewall version 4 - Zones File " .date("Y-m-d H:i:s")." by Artica";
	$f[]="#";
	$f[]="# For information about this file, type \"man shorewall-zones\"";
	$f[]="#";
	$f[]="# The manpage is also online at";
	$f[]="# http://www.shorewall.net/manpages/shorewall-zones.html";
	$f[]="#";
	$f[]="###############################################################################";
	$f[]="#ZONE	TYPE		OPTIONS		IN			OUT";
	$f[]="#";	
	$f[]="fw\tfirewall";
	$table="fw_zones";
	
	$sql="SELECT * FROM `fw_interfaces`";
	
	$results=$q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		$eth=$ligne["eth"];
		if(!$unix->is_interface_available($eth)){continue;}
		if($GLOBALS['VERBOSE']){echo "shorewall-zone:: LOADING ETH $eth\n###########################\n";}
		$nic=new system_nic($eth);
		$ZONE=$nic->netzone;
		if($ZONE=="fw"){continue;}
		if(isset($q->ZONES_RESERVED_WORDS[$ZONE])){if($q->ZONES_RESERVED_WORDS[$ZONE]){continue;}}
		if(isset($ZONEs[$ZONE])){continue;}
		$f[]="$ZONE\tipv4\t";
		$ZONEs[$ZONE]=true;
	}
	
	$sql="SELECT *  FROM fw_zones ORDER BY zOrder";
	
	$results=$q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		$OPTIONS=null;
		if($GLOBALS['VERBOSE']){echo "shorewall-zone:: LOADING ETH {$ligne["eth"]}\n###########################\n";}
		if(!$unix->is_interface_available($ligne["eth"])){continue;}
		
		$nic=new system_nic($ligne["eth"]);
		if($nic->netzone<>null){$ligne["zone"]=$nic->netzone;}
		$ZONE=$ligne["zone"];
		if(isset($q->ZONES_RESERVED_WORDS[$ZONE])){
			if($q->ZONES_RESERVED_WORDS[$ZONE]){continue;}	
		}
		
		
		if($ZONE=="fw"){continue;}
		if(isset($ZONEs[$ZONE])){continue;}
		$TYPE=$ligne["type"];
		$f[]="$ZONE\t$TYPE\t";
		$ZONEs[$ZONE]=true;
	}
	
	

	
	$f[]="";
	$f[]="#LAST LINE - ADD YOUR ENTRIES ABOVE THIS ONE - DO NOT REMOVE";
	@file_put_contents("/etc/shorewall/zones", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, /etc/shorewall/zones done\n";}
	
	
	
}

function build_policies(){
	$unix=new unix();
	$FW="\$FW";
	$f[]="# Shorewall version 4 - Policy File " .date("Y-m-d H:i:s")." by Artica";
	$f[]="#";
	$f[]="# For information about entries in this file, type \"man shorewall-policy\"";
	$f[]="#";
	$f[]="# The manpage is also online at";
	$f[]="# http://www.shorewall.net/manpages/shorewall-policy.html";
	$f[]="#";
	$f[]="###############################################################################";
	$f[]="#SOURCE	DEST	POLICY		LOG	LIMIT:		CONNLIMIT:";
	$f[]="#				LEVEL	BURST		MASK";
	

	
	
	$q=new mysql_shorewall();
	$table="( SELECT fw_zones1.eth as zone_from,fw_zones1.ID as ID1,
			fw_zones2.eth as zone_to,fw_zones2.ID as ID2, zones_policies.*
			FROM fw_zones as fw_zones1,fw_zones as fw_zones2,zones_policies
			WHERE fw_zones1.ID=zones_policies.zone_id_from AND fw_zones2.ID=zones_policies.zone_id_to ) as t";
		
	$sql="SELECT *  FROM $table ORDER BY zOrder";
	$results=$q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		$OPTIONS=null;
		$ETHSOURCE=$ligne["zone_from"];
		$mark=null;
		if(!$unix->is_interface_available($ETHSOURCE)){$mark="# ";}
		
		if($GLOBALS['VERBOSE']){echo "shorewall-policy:: LOADING ETH SOURCE:$ETHSOURCE\n###########################\n";}
		
		
		$int=new system_nic($ETHSOURCE);
		$SOURCE=$int->netzone;
		$ETHDEST=$ligne["zone_to"];
		
		if($GLOBALS['VERBOSE']){echo "shorewall-policy:: LOADING ETH DEST:$ETHDEST\n###########################\n";}
		$int=new system_nic($ETHDEST);
		$DEST=$int->netzone;
		
		$f[]="";
		$f[]="###############################################################################";
		$f[]="# Zone Policy for $ETHSOURCE [ $SOURCE ] -> $ETHDEST [ $DEST ]";
		$f[]="###############################################################################";
		
		if($DEST==null){$DEST="all";}
		if($SOURCE==null){$SOURCE="all";}
		$POLICY=$ligne["policy"];
		$f[]="$mark$SOURCE\t$DEST\t$POLICY";
	}
	$f[]="all\tall\tREJECT\tinfo";
	$f[]="";
	$f[]="#LAST LINE - ADD YOUR ENTRIES ABOVE THIS ONE - DO NOT REMOVE";
	@file_put_contents("/etc/shorewall/policy", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, /etc/shorewall/policy done\n";}	
	
}
function build_rtrules(){

	$f[]="#SOURCE     DEST      PROVIDER        PRIORITY";
	
	$f[]="#LAST LINE - ADD YOUR ENTRIES ABOVE THIS ONE - DO NOT REMOVE\n";
	@file_put_contents("/etc/shorewall/rtrules", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, /etc/shorewall/rules done\n";}
		
}


function build_rules_services($DATAS,$FW,$eth=null){
	if($FW==null){return;}
	$sock= new sockets();
	$ACCEPT_PING=$DATAS["ACCEPT_PING"];
	$ACCEPT_SMTP=$DATAS["ACCEPT_SMTP"];
	$ACCEPT_ARTICA=$DATAS["ACCEPT_ARTICA"];
	$ACCEPT_WWWW=$DATAS["ACCEPT_WWWW"];
	$ACCEPT_LDAP=$DATAS["ACCEPT_LDAP"];
	$ACCEPT_MYSQL=$DATAS["ACCEPT_MYSQL"];
	$ACCEPT_PROXY=$DATAS["ACCEPT_PROXY"];
	$ACCEPT_IMAP=$DATAS["ACCEPT_IMAP"];
	$ACCEPT_DNS=$DATAS["ACCEPT_DNS"];
	$ACCEPT_SSH=$DATAS["ACCEPT_DNS"];
	
	if($ACCEPT_SSH==null){$ACCEPT_SSH="all+";}
	if($ACCEPT_PING==null){$ACCEPT_PING="all+";}
	if($ACCEPT_SMTP==null){$ACCEPT_SMTP="all+";}
	if($ACCEPT_ARTICA==null){$ACCEPT_ARTICA="all+";}
	if($ACCEPT_WWWW==null){$ACCEPT_WWWW="all+";}
	if($ACCEPT_LDAP==null){$ACCEPT_LDAP="all+";}
	if($ACCEPT_MYSQL==null){$ACCEPT_MYSQL="all+";}
	if($ACCEPT_IMAP==null){$ACCEPT_IMAP="all+";}
	if($ACCEPT_DNS==null){$ACCEPT_DNS="all+";}
	if($ACCEPT_PING==null){$ACCEPT_PING="all+";}
	$f[]="";
	$f[]="############################################################";
	$f[]="# FireWall services for $eth Interface, $FW Zone";
	$f[]="############################################################";
	$f[]="";
	
	
	if(is_role($eth,"DHCP")){
		$f[]="# Accept DHCP";
		$f[]="ACCEPT\tall+\t$FW\tudp\t67:68\t67:68";
		$f[]="ACCEPT\t$FW\tall+\tudp\t67:68\t67:68";
	}
	
	if($ACCEPT_SSH<>"NONE"){
		$f[]="# Accept SSH";
		$f[]="ACCEPT\t$ACCEPT_SSH\t$FW\ttcp\t22";
	}
	if($ACCEPT_LDAP<>"NONE"){
		$f[]="# Accept LDAP";
		$f[]="ACCEPT\t$ACCEPT_SSH\t$FW\ttcp\t389";
	}
	if($ACCEPT_PING<>"NONE"){
		$f[]="# Accept ping";
		$f[]="ACCEPT\t$ACCEPT_PING\t$FW\ticmp\t8";
	}
	
	if($ACCEPT_SMTP<>"NONE"){
		$f[]="# Accept SMTP/SMTPS";
		$f[]="ACCEPT\t$ACCEPT_SMTP\t$FW\ttcp\t25";
		$f[]="ACCEPT\t$ACCEPT_SMTP\t$FW\ttcp\t465";
		$f[]="ACCEPT\t$ACCEPT_SMTP\t$FW\ttcp\t587";
	
	}
	
	if($ACCEPT_IMAP<>"NONE"){
		$f[]="# Accept IMAP/POP3/IMAPS";
		$f[]="ACCEPT\t$ACCEPT_IMAP\t$FW\ttcp\t143";
		$f[]="ACCEPT\t$ACCEPT_IMAP\t$FW\ttcp\t993";
		$f[]="ACCEPT\t$ACCEPT_IMAP\t$FW\ttcp\t110";
	
	}
	
	if($ACCEPT_WWWW<>"NONE"){
		$f[]="# Accept WWWW";
		$f[]="ACCEPT\t$ACCEPT_WWWW\t$FW\ttcp\t80";
		$f[]="ACCEPT\t$ACCEPT_WWWW\t$FW\ttcp\t443";
	
	}
	if($ACCEPT_DNS<>"NONE"){
		$f[]="# Accept DNS";
		$f[]="ACCEPT\t$ACCEPT_WWWW\t$FW\ttcp\t53";
		$f[]="ACCEPT\t$ACCEPT_WWWW\t$FW\tudp\t53";
	
	}
	if($ACCEPT_MYSQL<>"NONE"){
		$SquidDBTuningParameters=unserialize(base64_decode($sock->GET_INFO("SquidDBTuningParameters")));
		$ListenPort=$SquidDBTuningParameters["ListenPort"];
	
		$f[]="# Accept MySQL";
		$f[]="ACCEPT\t$ACCEPT_WWWW\t$FW\ttcp\t3306";
		if(is_numeric($ListenPort)){
			$f[]="ACCEPT\t$ACCEPT_WWWW\t$FW\ttcp\t$ListenPort";
		}
		$MySQLSyslogParams=unserialize(base64_decode($sock->GET_INFO("MySQLSyslogParams")));
		$ListenPort=$MySQLSyslogParams["ListenPort"];
		if(is_numeric($ListenPort)){
			$f[]="ACCEPT\t$ACCEPT_WWWW\t$FW\ttcp\t$ListenPort";
		}
		$AmavisDBMysqlParams=unserialize(base64_decode($sock->GET_INFO("AmavisDBMysqlParams")));
		$ListenPort=$AmavisDBMysqlParams["ListenPort"];
		if(is_numeric($ListenPort)){
			$f[]="ACCEPT\t$ACCEPT_WWWW\t$FW\ttcp\t$ListenPort";
		}
	
	
	}
	
	if($ACCEPT_PROXY<>"NONE"){
		$f[]="# Accept Proxy";
		$f[]="ACCEPT\t$ACCEPT_WWWW\t$FW\ttcp\t3128";
		$f[]="ACCEPT\t$ACCEPT_WWWW\t$FW\ttcp\t8080";
	
	}
	if($ACCEPT_ARTICA<>"NONE"){
		$f[]="# Web Interfaces Artica/Webmin";
		$ListenPort=$sock->GET_INFO("ArticaHttpsPort");
		if(!is_numeric($ListenPort)){$ListenPort=9000;}
		$f[]="ACCEPT\t$ACCEPT_WWWW\t$FW\ttcp\t$ListenPort";
		if(is_file("/etc/webmin/start")){
			$miniserv=miniserv_port();
			if(is_numeric($miniserv)){
				$f[]="ACCEPT\t$ACCEPT_WWWW\t$FW\ttcp\t$miniserv";
			}
				
		}
	}

	return @implode("\n", $f);
	
}


function build_rules(){
	$FW="\$FW";
	$sock=new sockets();
	$unix=new unix();
	$DATAS=unserialize(base64_decode($sock->GET_INFO("ShorewallTOFW")));
	

	
	$f[]="#";
	$f[]="# Shorewall version 4 - Rules File " .date("Y-m-d H:i:s")." by Artica";
	$f[]="#";
	$f[]="# For information on the settings in this file, type \"man shorewall-rules\"";
	$f[]="#";
	$f[]="# The manpage is also online at";
	$f[]="# http://www.shorewall.net/manpages/shorewall-rules.html";
	$f[]="#";
	$f[]="######################################################################################################################################################################################";
	$f[]="#ACTION		SOURCE		DEST		PROTO	DEST	SOURCE		ORIGINAL	RATE		USER/	MARK	CONNLIMIT	TIME         HEADERS         SWITCH";
	$f[]="#							PORT	PORT(S)		DEST		LIMIT		GROUP";
	$f[]="#SECTION ALL";
	$f[]="#SECTION ESTABLISHED";
	$f[]="#SECTION RELATED";
	$f[]="ACCEPT\t$FW\tall+\ticmp\t8";
	$f[]="ACCEPT\t$FW\t$FW\ttcp\t47980";
	$f[]="ACCEPT\t$FW\tall+\ttcp\t389";
	$f[]="ACCEPT\t$FW\tall+\ttcp\t143";
	$f[]="ACCEPT\t$FW\tall+\ttcp\t53";
	$f[]="ACCEPT\t$FW\tall+\tudp\t53";
	$f[]="ACCEPT\t$FW\tall+\ttcp\t80";
	$f[]="ACCEPT\t$FW\tall+\ttcp\t443";
	
	$f[]=build_rules_services($DATAS,$FW);
	
	$q=new mysql_shorewall();
	$sql="SELECT * FROM `fw_interfaces`";
	
	$results=$q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		$eth=$ligne["eth"];
		if(!$unix->is_interface_available($eth)){
			$f[]="\n#\n#                !! $eth - not available !!\n#\n";
			continue;
		}
		if($GLOBALS['VERBOSE']){echo "shorewall-rules:: LOADING ETH $eth\n###########################\n";}
		$nic=new system_nic($eth);
		$f[]=build_rules_services($nic->ShoreWallServices,$nic->netzone,$eth);
	}
	
	
	
	$q=new mysql_shorewall();
	$sql="SELECT * FROM fw_rules ORDER BY zOrder";
	$results = $q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		
		
		$mark=null;
		$ACTION=$ligne["ACTION"];
		$ID=$ligne["ID"];
		$PROTO=$ligne["PROTO"];
		$zone_id_to=$ligne["zone_id_to"];
		$zone_id_from=$ligne["zone_id_from"];
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT eth FROM fw_zones WHERE ID='$zone_id_from'"));
		if(!$unix->is_interface_available($ligne2["eth"])){$mark="#(no such interface {$ligne2["eth"]}) ";}
		$a=new system_nic($ligne2["eth"]);
		$zone_from=$a->netzone;
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT eth FROM fw_zones WHERE ID='$zone_id_to'"));
		if(!$unix->is_interface_available($ligne2["eth"])){$mark="#(no such interface {$ligne2["eth"]}) ";}
		$a=new system_nic($ligne2["eth"]);
		$zone_to=$a->netzone;
		$ITEMS_SOURCE=array();
		$ITEMS_DEST=array();
		$zone_sources=null;
		$zone_dest=null;
		
		
		
		
		
		
		$DEST_PORT=build_rules_groups(1,$ID);
		$SOURCE_PORT=build_rules_groups(0,$ID);
		
		$MACS_DEST=build_rules_groups(1,$ID,"mac");
		$MACS_SOURCE=build_rules_groups(0,$ID,"mac");
		
		$TCP_DEST=build_rules_groups(1,$ID,"net");
		$TCP_SOURCE=build_rules_groups(0,$ID,"net");
		
		if($MACS_SOURCE<>null){$ITEMS_SOURCE[]=$MACS_SOURCE;}
		if($MACS_DEST<>null){$ITEMS_DEST[]=$MACS_DEST;}
		
		if($TCP_SOURCE<>null){$ITEMS_SOURCE[]=$TCP_SOURCE;}
		if($TCP_DEST<>null){$ITEMS_DEST[]=$TCP_DEST;}		
		
		if(count($ITEMS_SOURCE)>0){$zone_sources=":".@implode(",", $ITEMS_SOURCE);}
		if(count($ITEMS_DEST)>0){$zone_dest=":".@implode(",", $ITEMS_DEST);}
		$connections_rate=connections_rate($ligne);
		
		$f[]="$mark$ACTION\t$zone_from$zone_sources\t$zone_to$zone_dest\t$PROTO\t$DEST_PORT\t$SOURCE_PORT\t-\t$connections_rate";
		
	}
	
	
	$f[]="#LAST LINE - ADD YOUR ENTRIES ABOVE THIS ONE - DO NOT REMOVE\n";
	@file_put_contents("/etc/shorewall/rules", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, /etc/shorewall/rules done\n";}
	
}

function connections_rate($ligne){
	if($ligne["RATELIM"]<>1){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, no connection rate limit\n";}
		return null;}
	if(!preg_match("#^(s|d|a):([0-9]+)\/(.+?):([0-9]+)#", $ligne["RATELIMIT"],$re)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, limit {$ligne["RATELIMIT"]} no match\n";}
		return null;}
	$LIMIT_D=$re[1];
	$connections=$re[2];
	$LIMIT_F=$re[3];
	$BURST=$re[4];
	
	$LIMIT_TEXT="$LIMIT_D:";
	if($LIMIT_D=="a"){$LIMIT_TEXT=null;}
	return "$LIMIT_TEXT$connections/$LIMIT_F:$BURST";
	
}



function build_rules_groups($IN=0,$ruleid,$type="port"){
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, Checks IN=$IN,$ruleid [$type]\n";}
	
	$sep=",";
	if($type=="mac"){$sep="-";}
	$sql="SELECT 
	fw_objects.grouptype,
	fw_objects_lnk.`ruleid`,
	fw_objects_lnk.`reverse`,
	`fw_objects_lnk`.`groupid`,
	fw_objects_lnk.`INOUT` 
	FROM `fw_objects_lnk`,`fw_objects`
	WHERE
	`fw_objects_lnk`.`groupid`=`fw_objects`.`ID`
	AND `fw_objects`.`grouptype`='$type'
	AND `fw_objects_lnk`.`INOUT`=$IN
	AND `fw_objects_lnk`.`ruleid`=$ruleid";
	$q=new mysql_shorewall();
	if(!$q->ok){echo "<strong style='color:red'>$q->mysql_error</strong>\n";}
	$results = $q->QUERY_SQL($sql);
	
	if(mysql_num_rows($results)==0){
		if($type=="port"){
			return "-";
		}
	}
	while ($ligne = mysql_fetch_assoc($results)) {
		$a=build_rules_items($ligne["groupid"],$type);
		if($a<>null){$f[]=$a;}
	}
	$res=@implode("$sep", $f);
	$res=str_replace("$sep$sep", "$sep", $res);
	return $res;
}

function build_rules_items($groupid,$type){
	$q=new mysql_shorewall();
	$sep=",";
	$prefix=null;
	if($type=="mac"){$sep="-";}
	if($type=="mac"){$prefix="~";}
	$sql="SELECT item FROM fw_items WHERE groupid=$groupid";
	$results = $q->QUERY_SQL($sql);
	if(mysql_num_rows($results)==0){return null;}
	while ($ligne = mysql_fetch_assoc($results)) {
		if($type=="mac"){$ligne["item"]=str_replace(":", "-", $ligne["item"]);}
		if($type=="mac"){$ligne["item"]=strtoupper($ligne["item"]);}
		$f[]=$ligne["item"];
	}
	$final="$prefix". @implode($sep, $f);
	$final=str_replace("$sep$sep", "$sep", $final);
	return $final;
}

function miniserv_port(){
	$t=explode("\n", @file_get_contents("/etc/webmin/miniserv.conf"));
	while (list ($key, $value) = each ($t) ){
		if(preg_match("#port.*?=.*?([0-9]+)#", $value,$re)){return $re[1];}
	}
	return 10000;
}

function hourly(){
	$stats=new shorewall_stats();
	$stats->ComprimHours();
	
}
function build_providers(){
	$unix=new unix();
	
	$filename="/etc/shorewall/providers";
	@unlink($filename);
	return;
	$sql="SELECT * FROM `fw_providers` ORDER BY NUMBER";
	$q=new mysql_shorewall();
	$results = $q->QUERY_SQL($sql);
	$f[]="#";
	$f[]="# Shorewall version 4 - Providers File " .date("Y-m-d H:i:s")." by Artica";
	$f[]="#";
	$f[]="# For information about entries in this file, type \"man shorewall-providers\"";
	$f[]="#";
	$f[]="# For additional information, see http://shorewall.net/MultiISP.html";
	$f[]="#";
	$f[]="############################################################################################";
	$f[]="#NAME	NUMBER	MARK	DUPLICATE	INTERFACE	GATEWAY		OPTIONS		COPY";
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$NAME=$ligne["NAME"];
		$NUMBER=$ligne["NUMBER"];
		$MARK=$ligne["MARK"];
		$DUPLICATE=$ligne["DUPLICATE"];
		$INTERFACE=$ligne["INTERFACE"];
		$GATEWAY=$ligne["GATEWAY"];
		$prefix=null;
		
		if(!$unix->is_interface_available($INTERFACE)){$prefix="# ( Interface $INTERFACE not available ) ";}
		
		if(!is_numeric($MARK)){$MARK="-";}
		if($DUPLICATE==null){$DUPLICATE="-";}
		if($INTERFACE==null){$INTERFACE="-";}
		if($GATEWAY==null){$GATEWAY="-";}
		$OPTION="-";
		$options=array();
		if($ligne["track"]==1){$options[]="track";}
		if($ligne["tproxy"]==1){$options[]="tproxy";}
		
		if($ligne["fallback"]>-1){
			if($ligne["fallback"]>0){
				$options[]="fallback={$ligne["fallback"]}";}
			else{
				$options[]="fallback";
			}
		}
		
		if($ligne["balance"]>-1){
			if($ligne["balance"]>0){
				$options[]="balance={$ligne["balance"]}";}
			else{
				$options[]="balance";
			}
		}			

		if(count($options)>0){$OPTION=@implode(",", $options);}
		$f[]="$prefix$NAME\t$NUMBER\t$MARK\t$DUPLICATE\t$INTERFACE\t$GATEWAY\t$OPTION";
		
	}
	
	$f[]="#LAST LINE - ADD YOUR ENTRIES ABOVE THIS ONE - DO NOT REMOVE\n";
	@file_put_contents($filename, @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, $filename done\n";}
}
