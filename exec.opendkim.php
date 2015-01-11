<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
$GLOBALS["TITLENAME"]="OpenDKIM service";
$GLOBALS["PID_FILE"]="/var/run/opendkim/opendkim.pid";
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;}
if(preg_match("#--simule#",implode(" ",$argv))){$GLOBALS["SIMULE"]=true;$GLOBALS["SIMULE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;$GLOBALS["FORCE"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--build"){build();die();}
if($argv[1]=="--whitelist"){WhitelistHosts();die();}
if($argv[1]=="--networks"){MyNetworks();die();}
if($argv[1]=="--buildKeyView"){buildKeyView();die();}
if($argv[1]=="--TESTKeyView"){TESTKeyView();die();}
if($argv[1]=="--keyTable"){keyTable();die();}
if($argv[1]=="--perms"){SetPermissions();die();}






function build(){
	$sock=new sockets();
	$EnableDKFilter=$sock->GET_INFO("EnableDKFilter");
	$conf=unserialize(base64_decode($sock->GET_INFO("OpenDKIMConfig")));
	if($EnableDKFilter==null){$EnableDKFilter=0;}
	$DisconnectDKFilter=$sock->GET_INFO("DisconnectDKFilter");
	if(!is_numeric($DisconnectDKFilter)){$DisconnectDKFilter=0;}
	if($DisconnectDKFilter==1){return;}
	
	if($conf["On-BadSignature"]==null){$conf["On-BadSignature"]="accept";}
	if($conf["On-NoSignature"]==null){$conf["On-NoSignature"]="accept";}
	if($conf["On-DNSError"]==null){$conf["On-DNSError"]="tempfail";}
	if($conf["On-InternalError"]==null){$conf["On-InternalError"]="accept";}

	if($conf["On-Security"]==null){$conf["On-Security"]="tempfail";}
	if($conf["On-Default"]==null){$conf["On-Default"]="accept";}
	if($conf["ADSPDiscard"]==null){$conf["ADSPDiscard"]="1";}
	if($conf["ADSPNoSuchDomain"]==null){$conf["ADSPNoSuchDomain"]="1";}	
	if($conf["DomainKeysCompat"]==null){$conf["DomainKeysCompat"]="0";}
	if($conf["OpenDKIMTrustInternalNetworks"]==null){$conf["OpenDKIMTrustInternalNetworks"]="1";}
	
	
	
	
if($conf["DomainKeysCompat"]==1){$f[]="DomainKeysCompat		  {$conf["DomainKeysCompat"]}";}
$f[]="ADSPNoSuchDomain        {$conf["ADSPNoSuchDomain"]}";
//$f[]="ADSPDiscard        	  {$conf["ADSPDiscard"]}";
$f[]="AutoRestart             1";
$f[]="AutoRestartRate         10/1h";
$f[]="Canonicalization        simple/simple";
$f[]="ExemptDomains			  refile:/etc/mail/dkim/trusted-domains";
$f[]="ExternalIgnoreList      refile:/etc/mail/dkim/trusted-hosts";
$f[]="InternalHosts           refile:/etc/mail/dkim/internal-hosts";
$f[]="KeyTable                file:/etc/mail/dkim/keyTable";
$f[]="SigningTable            refile:/etc/mail/dkim/signingTable";
$f[]="LogWhy                  Yes";
$f[]="On-Default              {$conf["On-Default"]}";
$f[]="On-BadSignature         {$conf["On-BadSignature"]}";
$f[]="On-DNSError             {$conf["On-DNSError"]}";
$f[]="On-InternalError        {$conf["On-InternalError"]}";
$f[]="On-NoSignature          {$conf["On-NoSignature"]}";
$f[]="On-Security             {$conf["On-Security"]}";
$f[]="PidFile                 {$GLOBALS["PID_FILE"]}";
$f[]="SignatureAlgorithm      rsa-sha256";
$f[]="Socket                  local:/var/run/opendkim/opendkim.sock";
$f[]="Syslog                  Yes";
$f[]="SyslogSuccess           Yes";
$f[]="TemporaryDirectory      /var/tmp";
$f[]="UMask                   022";
$f[]="UserID                  postfix:postfix";
$f[]="X-Header                Yes";	

@file_put_contents("/etc/opendkim.conf",@implode("\n",$f));

keyTable();
WhitelistDomains();
WhitelistHosts();
MyNetworks($conf["OpenDKIMTrustInternalNetworks"]);
SetPermissions();
	
}

function SetPermissions(){
	$unix=new unix();
	$chmod=$unix->find_program("chmod");
	$chown=$unix->find_program("chown");
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, opendkim Apply permissions...\n";}
	@mkdir("/var/run/opendkim",0755,true);
	@mkdir(dirname($GLOBALS["PID_FILE"]),0755,true);
	shell_exec("$chown -R postfix:postfix /etc/mail/dkim >/dev/null 2>&1");
	shell_exec("$chown -R postfix:postfix /etc/mail/dkim/keys >/dev/null 2>&1");
	shell_exec("$chown -R postfix:postfix /var/run/opendkim >/dev/null 2>&1");
	shell_exec("$chmod 755 /etc/mail/dkim >/dev/null 2>&1");
	shell_exec("$chmod 0770 /etc/mail/dkim/keys >/dev/null 2>&1");
	shell_exec("$chmod 0770 /etc/mail/dkim/keys/* >/dev/null 2>&1");
	shell_exec("$chmod 0770 /etc/mail/dkim/keys/*/* >/dev/null 2>&1");
	shell_exec("$chown -R postfix:postfix /etc/mail/dkim >/dev/null 2>&1");
	shell_exec("$chown -R postfix:postfix /etc/mail/dkim/keys >/dev/null 2>&1");
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, opendkim Apply permissions done...\n";}
}

function keyTable(){
$unix=new unix();
$opendkim_genkey=$unix->find_program("opendkim-genkey");

if(!is_file($opendkim_genkey)){
	$opendkim_genkey=$unix->find_program("opendkim-genkey.sh");
}

if(!is_file($opendkim_genkey)){
	echo "Starting......: ".date("H:i:s")." opendkim \"opendkim-genkey.sh\" no such binary found !\n";
	return;
}	
$chown=$unix->find_program("chown");
$file="/etc/mail/dkim/keyTable";
@mkdir(dirname($file),null,true);

$ldap=new clladp();
$domainsH=$ldap->AllDomains();
if(is_array($domainsH)){
	while (list ($num, $DOMAIN) = each ($domainsH) ){
		$dir="/etc/mail/dkim/keys/$DOMAIN";
		if(!is_dir($dir)){
			echo "Starting......: ".date("H:i:s")." OpenDKIM Creating directory /etc/mail/dkim/keys/$DOMAIN\n";
			@mkdir("/etc/mail/dkim/keys/$DOMAIN",null,true);
		}	
		if(!keyTableVerifyFiles($dir)){
			echo "Starting......: ".date("H:i:s")." OpenDKIM generating TXT and private for $DOMAIN\n";
			$cmd="$opendkim_genkey -D $dir/ -d $DOMAIN -s default";
			system($cmd);
			shell_exec("/bin/cp $dir/default.private $dir/default");
		}else{
			echo "Starting......: ".date("H:i:s")." opendkim TXT and private for $DOMAIN OK\n";
		}
		
		shell_exec("$chown -R postfix:postfix $dir >/dev/null 2>&1");
		$keyTable[]="default._domainkey.$DOMAIN	$DOMAIN:default:/etc/mail/dkim/keys/$DOMAIN/default";
		$signingTable[]="*@$DOMAIN default._domainkey.$DOMAIN";
		
	}
}else{
	echo "Starting......: ".date("H:i:s")." opendkim generating No domains set\n";
}
	
	if(@file_put_contents("/etc/mail/dkim/keyTable",@implode("\n",$keyTable))){
			echo "Starting......: ".date("H:i:s")." opendkim generating keyTable done...\n";
	}else{
		echo "Starting......: ".date("H:i:s")." opendkim FAILED generating keyTable done...\n";
	}
	
	if(@file_put_contents("/etc/mail/dkim/signingTable",@implode("\n",$signingTable))){
		echo "Starting......: ".date("H:i:s")." opendkim generating signingTable done...\n";
	}else{
		echo "Starting......: ".date("H:i:s")." opendkim FAILED generating signingTable done...\n";	
	}
	
}

function WhitelistDomains(){
	
	$sql="SELECT * FROM spamassassin_dkim_wl ORDER BY ID DESC";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");


	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
	$f[]=$ligne["domain"];
	}
	
	@file_put_contents("/etc/mail/dkim/trusted-domains",@implode("\n",$f));
	echo "Starting......: ".date("H:i:s")." opendkim generating trusted domains ". count($f)." entries done...\n";
}

function keyTableVerifyFiles($dir){
	if(!is_file("$dir/default.private")){return false;}
	if(!is_file("$dir/default.txt")){return false;}
	if(!is_file("$dir/default")){return false;}
	return true;
}

function WhitelistHosts(){  
		
$unix=new unix();
$php5=$unix->LOCATE_PHP5_BIN();
$dirname=dirname(__FILE__);
shell_exec("$php5 $dirname/exec.dkim-milter.php --whitelist");
}

function MyNetworks($trust=0){
	if($trust==1){
		$ldap=new clladp();
		$nets=$ldap->load_mynetworks();
	}
	$nets[]="127.0.0.0/8";
	while (list ($num, $network) = each ($nets) ){$cleaned[$network]=$network;}
	unset($nets);
	while (list ($network, $network2) = each ($cleaned) ){$nets[]=$network;}	
	echo "Starting......: ".date("H:i:s")." opendkim generating internal hosts ". count($nets)." entries done...\n";
	$nets[]="";
	@file_put_contents("/etc/mail/dkim/internal-hosts",@implode("\n",$nets));
}

function buildKeyView(){
$ldap=new clladp();
$domainsH=$ldap->AllDomains();
if(is_array($domainsH)){
	while (list ($num, $DOMAIN) = each ($domainsH) ){
		$file="/etc/mail/dkim/keys/$DOMAIN/default.txt";
		if(is_file($file)){
			$array[$DOMAIN]=@file_get_contents($file);	
		}
	
}
}

@file_put_contents("/etc/mail/dkim.domains.key",base64_encode(serialize($array)));


}
function TESTKeyView(){
	$unix=new unix();
	$opendkim=$unix->find_program("opendkim-testkey");
	$dig=$unix->find_program("dig");
	$chmod=$unix->find_program("chmod");
	if(!is_file($opendkim)){return ;}
$ldap=new clladp();
$domainsH=$ldap->AllDomains();
if(is_array($domainsH)){
	while (list ($num, $DOMAIN) = each ($domainsH) ){
		unset($results);
		
		shell_exec("$chmod -R 0770 /etc/mail/dkim/keys/$DOMAIN");
		$results[]="\n\n$dig TXT +short default._domainkey.$DOMAIN :\n-------------------------------\n";
		exec("$dig TXT +short default._domainkey.$DOMAIN 2>&1",$results);
		$results[]="\n\n";
		exec("$opendkim -d $DOMAIN -s default -k /etc/mail/dkim/keys/$DOMAIN/default 2>&1",$results);
		$array[$DOMAIN]=@implode("\n",$results);
	}
}

@file_put_contents("/etc/mail/dkim.domains.tests.key",base64_encode(serialize($array)));


}


function PID_NUM(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/opendkim/opendkim.pid");
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("opendkim");
	return $unix->PIDOF($Masterbin);
}

function restart() {
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());

	
	stop(true);
	build();
	sleep(1);
	start(true);
	SetPermissions();

}

function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("opendkim");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, opendkim not installed\n";}
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
	
	$EnableDKFilter=intval($sock->GET_INFO("EnableDKFilter"));



	if($EnableDKFilter==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableDKFilter)\n";}
		stop();
		return;
	}

	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	$kill=$unix->find_program("kill");
	$chown=$unix->find_program("chown");
	
	@unlink("/var/run/opendkim/opendkim.pid");
	$f[]=$Masterbin;
	$f[]="-p //var/run/opendkim/opendkim.sock";
	$f[]="-x /etc/opendkim.conf";
	$f[]="-u postfix";
	$f[]="-P {$GLOBALS["PID_FILE"]}";
	
	@unlink("/var/run/opendkim/opendkim.sock");
	@mkdir("/var/run/opendkim",0755,true);
	$unix->chown_func("postfix", "postfix","/var/run/opendkim");
	

	$cmd=@implode(" ", $f);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	shell_exec($cmd);

	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		$unix->chown_func("postfix", "postfix","/var/run/opendkim/opendkim.sock");
		shell_exec("$chown -R postfix:postfix /etc/mail/dkim >/dev/null 2>&1");
		shell_exec("$chown -R postfix:postfix /etc/mail/dkim/keys >/dev/null 2>&1");
		shell_exec("$chown -R postfix:postfix /var/run/opendkim >/dev/null 2>&1");

	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	}


}
function stop($aspid=false){
	if($GLOBALS["MONIT"]){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} runned by Monit, abort\n";}
		return;}
		$unix=new unix();
		if(!$aspid){
			$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
			$pid=$unix->get_pid_from_file($pidfile);
			if($unix->process_exists($pid,basename(__FILE__))){
				$time=$unix->PROCCESS_TIME_MIN($pid);
				if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Artica script already running PID $pid since {$time}mn\n";}
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
		@unlink("/var/run/opendkim/opendkim.sock");
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		
} 

?>