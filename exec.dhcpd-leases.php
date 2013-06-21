#!/usr/bin/php -q
<?php
$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}



include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.dhcpd.inc');
include_once(dirname(__FILE__) . '/ressources/class.computers.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');

if($argv[1]=="--parse-leases"){parseLeases();die();}

if($argv[1]=="commit"){
	update_commit($argv[2],$argv[3],$argv[4]);
	die(0);
}
	if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$unix=new unix();
	
	if($unix->process_exists(@file_get_contents($pidfile,basename(__FILE__)))){
		if($GLOBALS["VERBOSE"]){echo " --> Already executed.. ". @file_get_contents($pidfile). " aborting the process\n";}
		die();
	}
	@file_put_contents($pidfile, getmypid());






$GLOBALS["nmblookup"]=$unix->find_program("nmblookup");

if($argv[1]=="lookup"){echo "{$argv[2]}:".nmblookup($argv[2],$argv[3])."\n";die();}

if($argv[1]=='--single-computer'){die();}
if($GLOBALS["VERBOSE"]){
	echo " --> Argument={$argv[1]}\n";
	echo " --> Force={$GLOBALS["FORCE"]}\n";

}

$sock=new sockets();
$EnableDHCPServer=$sock->GET_INFO('EnableDHCPServer');
$ComputersAllowDHCPLeases=$sock->GET_INFO("ComputersAllowDHCPLeases");
if($ComputersAllowDHCPLeases==null){$ComputersAllowDHCPLeases=1;}

if($EnableDHCPServer==0){writelogs("EnableDHCPServer is disabled, aborting...","MAIN",__FILE__,__LINE__);die();}



if($ComputersAllowDHCPLeases==0){
	if($GLOBALS["VERBOSE"]){echo " -->ComputersAllowDHCPLeases is disabled -> die()\n";}
	writelogs("ComputersAllowDHCPLeases is disabled, aborting...","MAIN",__FILE__,__LINE__);
	die();
}

localsyslog("Check changed leases...");

if(!$GLOBALS["FORCE"]){if($GLOBALS["VERBOSE"]){echo " -->Changed()\n";}if(!Changed()){die();}}

$cache_file="/etc/artica-postfix/dhcpd.leases.dmp";
$datas=@file_get_contents("/var/lib/dhcp3/dhcpd.leases");
$md5Tampon=md5($datas);
$md5Local=md5(trim(@file_get_contents("/etc/artica-postfix/dhcpd.leases.dmp")));
if($GLOBALS["VERBOSE"]){echo " -->$md5Local / $md5Tampon\n";}
if(!$GLOBALS["FORCE"]){if($md5Local==$md5Tampon){if($GLOBALS["VERBOSE"]){echo " -->$md5Local == $md5Tampon, abort\n";}die();}}
if(!$GLOBALS["FORCE"]){if(file_time_min($cache_file)<10){die();}}
@unlink($cache_file);
@file_put_contents($cache_file,$md5Tampon);


writelogs("LOCAL:$md5Local !== REMOTE:$md5Tampon","MAIN",__FILE__,__LINE__);
write_syslog("integrity of dhcpd.leases has been modified ( from $md5Local to $md5Tampon), analyze the leases",basename(__FILE__));

if($GLOBALS["VERBOSE"]){echo " --> CleanFile()\n";}
CleanFile();
if($GLOBALS["VERBOSE"]){echo " --> /var/lib/dhcp3/dhcpd.leases\n";}
$datas=@file_get_contents("/var/lib/dhcp3/dhcpd.leases");

$md5=md5($datas);
if(!preg_match_all("#lease\s+(.+?)\s+{(.+?)\}#is",$datas,$re)){
	if($GLOBALS["VERBOSE"]){echo " --> Unable to preg_match\n";}
	events("Unable to preg_match","main",__LINE__);
	die();
}

$dhcp=new dhcpd();
$unix=new unix();
$GLOBALS["nmblookup"]=$unix->find_program("nmblookup");


$GLOBALS["domain"]=$dhcp->ddns_domainname;
if($GLOBALS["VERBOSE"]){echo " -->domain $dhcp->ddns_domainname\n";}
if($GLOBALS["VERBOSE"]){echo " -->Table ".count($re[1])." rows\n";}
$sql="TRUNCATE TABLE `dhcpd_leases`";
$q=new mysql();
$q->QUERY_SQL($sql,"artica_backup");
$GLOBALS["FIXIPHOST"]=false;

$c=0;
while (list ($num, $ligne) = each ($re[1]) ){
	$c++;
	$ip=$ligne;
	$HOST=null;
	$MAC=null;
	$starts="0000-00-00 00:00:00";
	$ends="0000-00-00 00:00:00";
	$tstp="0000-00-00 00:00:00";
	$atsfp="0000-00-00 00:00:00";
	$cltt="0000-00-00 00:00:00";
	if($GLOBALS["VERBOSE"]){echo "checking {$re[2][$num]}\n";}
	if(preg_match("#hardware ethernet\s+(.+?);\s+#is",$re[2][$num],$ri)){$MAC=trim($ri[1]);}
	if(preg_match("#client-hostname \"(.+?)\";#",$re[2][$num],$ri)){$HOST=trim($ri[1]);}
	if(preg_match("#starts\s+([0-9])\s+([0-9]+)\/([0-9]+)\/([0-9]+)\s+(.+?);#",$re[2][$num],$ri)){$starts="{$ri[2]}-{$ri[3]}-{$ri[4]} {$ri[5]}";}
	if(preg_match("#ends\s+([0-9])\s+([0-9]+)\/([0-9]+)\/([0-9]+)\s+(.+?);#",$re[2][$num],$ri)){$ends="{$ri[2]}-{$ri[3]}-{$ri[4]} {$ri[5]}";}
	if(preg_match("#tstp\s+([0-9])\s+([0-9]+)\/([0-9]+)\/([0-9]+)\s+(.+?);#",$re[2][$num],$ri)){$tstp="{$ri[2]}-{$ri[3]}-{$ri[4]} {$ri[5]}";}
	if(preg_match("#atsfp\s+([0-9])\s+([0-9]+)\/([0-9]+)\/([0-9]+)\s+(.+?);#",$re[2][$num],$ri)){$atsfp="{$ri[2]}-{$ri[3]}-{$ri[4]} {$ri[5]}";}
	if(preg_match("#cltt\s+([0-9])\s+([0-9]+)\/([0-9]+)\/([0-9]+)\s+(.+?);#",$re[2][$num],$ri)){$cltt="{$ri[2]}-{$ri[3]}-{$ri[4]} {$ri[5]}";}
	
	
	
	
	$MAC=trim($MAC);
	$HOST=trim($HOST);
	$ip=str_replace("lease",'',$ip);
	$ip=trim($ip);
	if($MAC<>null){
		if(isset($alredyDoneMAC[$MAC])){continue;}
		
	}
	
	
	
	$alredyDoneMAC[$MAC]=$MAC;
	$comp=new computers();
	if($GLOBALS["VERBOSE"]){echo "checking $uid comp->ComputerIDFromMAC($MAC)\n";}
	$uid=$comp->ComputerIDFromMAC($MAC);
	if($GLOBALS["VERBOSE"]){echo " LOOP --> $ip ($uid)=$HOST\n";}
	if($HOST==null){$HOST=trim($uid);}
	$dns=true;
	$ip=nmblookup($HOST,$ip);
	writelogs("$c/". count($re[1])."] ************************************************","MAIN",__FILE__,__LINE__);
	writelogs("CHECK $ip ($uid) $HOST","MAIN",__FILE__,__LINE__);
	$HOST_sql=$HOST;
	if($GLOBALS["VERBOSE"]){echo "$MAC \"{$HOST}\"[$ip] $starts -> $ends\n";}
	$HOST_sql=str_replace("$", "", $HOST_sql);
	$HOST_sql=trim(strtolower($HOST_sql));
	$sql="INSERT IGNORE INTO dhcpd_leases (`mac`,`hostname`,`starts`,`ends`,`cltt`,`tstp`,`atsfp`,`ipaddr`) VALUES
	('$MAC','$HOST_sql','$starts','$ends','$cltt','$tstp','$atsfp','$ip');";
	$q->QUERY_SQL($sql,"artica_backup");
	
	
	$sock=new sockets();
	
	$ping=$unix->PingHost($ip);
	
	if($uid==null){
		if($HOST==null){$uid=$ip.'$';}else{$uid=$HOST.'$';}
		$comp=new computers();
		$comp->ComputerRealName=$HOST;
		$comp->ComputerMacAddress=$MAC;
		if($ping){$comp->ComputerIP=$ip;}
		$comp->DnsZoneName=$GLOBALS["domain"];
		$comp->uid=$uid;
		$ComputerRealName=$HOST;
		writelogs("Adding new computer $uid",__FUNCTION__,__FILE__,__LINE__);
		$comp->Add();
	}else{
		$MustEdit=false;
		$comp=new computers($uid);
		if($ping){
			if($comp->ComputerIP<>$ip){
				writelogs("Editing $uid because $ip did not match $comp->ComputerIP",__FUNCTION__,__FILE__,__LINE__);
				$comp->ComputerIP=$ip;	
				$MustEdit=true;
			}
			
		}
		
		if(strtolower($comp->DnsZoneName)<>strtolower($GLOBALS["domain"])){
			writelogs("Editing $uid because $comp->DnsZoneName did not match {$GLOBALS["domain"]}",__FUNCTION__,__FILE__,__LINE__);
			$comp->DnsZoneName=$GLOBALS["domain"];
			$MustEdit=true;
		}
		if($MustEdit){$comp->Edit();}
		
		
		
		if($comp->ComputerRealName==null){$ComputerRealName=$uid;}else{
			if(!preg_match("#[0-9]+\.[0-9]+\.#",$comp->ComputerRealName)){
				$ComputerRealName=$comp->ComputerRealName;
			}
		}
		
		$ComputerRealName=$comp->ComputerRealName;
		
		
	}
	
	if($ping){
		$unix=new unix();
		if($GLOBALS["VERBOSE"]){echo " --> /etc/hosts $ComputerRealName -> $ip\n";}
		$unix->del_EtcHosts($ip);
		$dns=new pdns($GLOBALS["domain"]);
		writelogs("EditIPName -> ComputerRealName=`$ComputerRealName` $ip $MAC",__FUNCTION__,__FILE__,__LINE__);
		if(trim($ComputerRealName)<>null){$dns->EditIPName(strtolower($ComputerRealName),$ip,'A',$MAC);}
		$GLOBALS["FIXIPHOST"]=true;
		
	}
	
}

if($GLOBALS["FIXIPHOST"]){
	writelogs("-> exec.samba.php --fix-etc-hosts",__FUNCTION__,__FILE__,__LINE__);
	shell_exec(LOCATE_PHP5_BIN2()." ". dirname(__FILE__)."/exec.samba.php --fix-etc-hosts");	
}

events("Set content cache has $md5","main",__LINE__);
$sock->SET_INFO('DHCPLeaseMD5',$md5);



function events($text,$function,$line){
		writelogs($text,$function,__FILE__,$line);
}


function Changed(){
	if(!is_file("/var/lib/dhcp3/dhcpd.leases")){
		if($GLOBALS["VERBOSE"]){echo " --> unable to stat /var/lib/dhcp3/dhcpd.leases\n";}
		return false;
	}
	$sock=new sockets();
	$DHCPLeaseMD5=$sock->GET_INFO('DHCPLeaseMD5');
	if($DHCPLeaseMD5==null){return true;}
	$datas=@file_get_contents("/var/lib/dhcp3/dhcpd.leases");
	$md5=md5($datas);
	if($GLOBALS["VERBOSE"]){echo " --> $DHCPLeaseMD5 Current: $md5\n";}
	if(trim($DHCPLeaseMD5)==$md5){
		if($GLOBALS["VERBOSE"]){echo " --> Not changed\n";}
		return false;
	}
	return true;
}

function CleanFile(){
	$datas=@file_get_contents("/var/lib/dhcp3/dhcpd.leases");
	if($GLOBALS["VERBOSE"]){echo " --> /var/lib/dhcp3/dhcpd.leases ". strlen($datas)." bytes\n";}
	$tbl=explode("\n",$datas);
	while (list ($num, $ligne) = each ($tbl) ){
		if(preg_match("#^\##",$ligne)){
			unset($tbl[$num]);
		}
	}
	writelogs("/var/lib/dhcp3/dhcpd.leases cleaned",__FUNCTION__,__FILE__,__LINE__);
	if($GLOBALS["VERBOSE"]){echo " --> /var/lib/dhcp3/dhcpd.leases cleaned...\n";}
	@file_put_contents("/var/lib/dhcp3/dhcpd.leases",implode("\n",$tbl));
}

function update_computer($ip,$mac,$name){
	$sock=new sockets();	
	$ComputersAllowDHCPLeases=$sock->GET_INFO("ComputersAllowDHCPLeases");
	if($ComputersAllowDHCPLeases==null){$ComputersAllowDHCPLeases=1;}
	if($ComputersAllowDHCPLeases==0){localsyslog("`ComputersAllowDHCPLeases` Aborting updating the LDAP database");return;}	
	
	$mac=trim($mac);
	$name=trim(strtolower($name));
	$ip=trim($ip);
	if($ip==null){return;}
	if($mac==null){return;}
	if($name==null){return;}
	if(preg_match("^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $name)){localsyslog("`$name` is a TCP IP address, aborting updating the LDAP database");return;}
	
	
	$ip=nmblookup($name,$ip);
	$dhcp=new dhcpd();
	$GLOBALS["domain"]=$dhcp->ddns_domainname;	
	
	$comp=new computers();
	$uid=$comp->ComputerIDFromMAC($mac);
	if(preg_match("#^(.+?)\.(.+?)$#", $name,$re)){
		$name=$re[1];
		$GLOBALS["domain"]=$re[2];
	}
	
	
	
	if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $uid)){
		$comp=new computers($uid);
		localsyslog("Removing computer ($uid) $mac");
		$comp->DeleteComputer();
		$uid=null;
		$uid=$comp->ComputerIDFromMAC($mac);
	}
	localsyslog("$mac -> uid:`$uid`");
	
	if($uid==null){
		$add=true;
		$uid="$name$";
		$comp=new computers();
		$comp->ComputerRealName=$name;
		$comp->ComputerMacAddress=$mac;
		$comp->ComputerIP=$ip;
		$comp->DnsZoneName=$GLOBALS["domain"];
		$comp->uid=$uid;
		$ComputerRealName=$name;
		localsyslog("Adding new computer $name[$ip] ($uid) $mac in domain $comp->DnsZoneName");
		$comp->Add();

	}else{
		$comp=new computers($uid);
		if($comp->ComputerRealName==null){$comp->ComputerRealName=$name;}
		if(preg_match("#[0-9]+\.[0-9]+\.#",$comp->ComputerRealName)){$comp->ComputerRealName=$name;}
		$comp->ComputerIP=$ip;
		$comp->DnsZoneName=$GLOBALS["domain"];
		localsyslog("Editing computer $comp->ComputerRealName[$ip] ($uid) $mac in domain $comp->DnsZoneName");
		$comp->Edit();
		
	}
	
	
	$dns=new pdns($GLOBALS["domain"]);
	$dns->EditIPName(strtolower($name),$ip,'A',$mac);	

}


function nmblookup($hostname,$ip){
	if(trim($hostname)==null){return $ip;}
	$hostname=str_replace('$','',$hostname);
	if($GLOBALS["nmblookup"]==null){
		$unix=new unix();
		$GLOBALS["nmblookup"]=$unix->find_program("nmblookup");
	}
	
	if($GLOBALS["nmblookup"]==null){
		if($GLOBALS["VERBOSE"]){echo " nmblookup:: --> Could not found binary\n";}
		return $ip;
	}
	if(preg_match("#^[0-9]+\.[0-9]+.[0-9]+\.[0-9]+$#",$hostname)){
		if($GLOBALS["VERBOSE"]){echo " nmblookup:: --> hostname match IP string, aborting\n";}
		return $ip;
	}
	
	if(preg_match("#([0-9]+)\.([0-9]+).([0-9]+)\.([0-9]+)#",$ip,$re)){
		$broadcast="{$re[1]}.{$re[2]}.{$re[3]}.255";
	}else{
		if($GLOBALS["VERBOSE"]){echo " nmblookup:: --> $ip not match for broadcast addr\n";}
		return $ip;
	}
	
	if($GLOBALS["VERBOSE"]){echo " nmblookup:: --> broadcast=$broadcast\n";}
	$cmd="{$GLOBALS["nmblookup"]} -B $broadcast $hostname";
	if($GLOBALS["VERBOSE"]){echo " nmblookup:: --> $cmd\n";}
	exec($cmd,$results);
	
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#Got a positive name query response from\s+([0-9\.]+)#",$ligne,$re)){
			if($GLOBALS["VERBOSE"]){echo " nmblookup:: --> {$re[1]}\n";}
			return $re[1];
		}
	}
	if($GLOBALS["VERBOSE"]){echo " nmblookup:: --> NO MATCH\n";}
	return $ip;
}


function localsyslog($text){
	if(!function_exists("syslog")){return;}
	openlog(basename(__FILE__), LOG_PID , LOG_SYSLOG);
	syslog(LOG_INFO, $text);
	closelog();	
	
}

function update_commit($ip,$mac,$hostname){
	if(!preg_match("#([0-9]+)\.([0-9]+).([0-9]+)\.([0-9]+)#",$ip,$re)){
		localsyslog("Commit: IP:`$ip` invalid...");
		return;
	}
	
	
	
	$macZ=explode(":",$mac);
	while (list ($num, $ligne) = each ($macZ) ){
		if(strlen($ligne)==1){$macZ[$num]="0$ligne";}
		
	}
	$mac=@implode(":", $macZ);
	if(preg_match("#^(.+?)\.#", $hostname,$re)){$hostname=$re[1];}
	localsyslog("Commit: IP:$ip,$mac,$hostname");
	$md5=md5(time()."ip,$mac,$hostname");
	@mkdir("/var/log/artica-postfix/DHCP-LEASES");
	$array["IP"]=$ip;
	$array["MAC"]=$mac;
	$array["hostname"]=$hostname;
	@file_put_contents("/var/log/artica-postfix/DHCP-LEASES/$md5", serialize($array));
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	
	shell_exec("$nohup $php5 ".__FILE__." --parse-leases >/dev/null 2>&1 &");
	
	
	
	
	
}
function parseLeases(){
	foreach (glob("/var/log/artica-postfix/DHCP-LEASES/*") as $filename) {
		$array=unserialize(@file_get_contents($filename));
		@unlink($filename);
		if(!is_array($array)){continue;}
		$ip=$array["IP"];
		$mac=$array["MAC"];
		$hostname=$array["hostname"];
		if($ip==null){continue;}	
		update_computer($ip,$mac,$hostname);
	}
	
	
}



?>