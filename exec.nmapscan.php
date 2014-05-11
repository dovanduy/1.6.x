<?php

if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");

if(is_file("/etc/artica-postfix/AS_KIMSUFFI")){echo "AS_KIMSUFFI!\n";die();}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if($argv[1]=='--parse'){parsefile("/etc/artica-postfix/{$argv[2]}.map",$argv[3]);die();}
if($argv[1]=="--scan-nets"){scannetworks();exit;}
if($argv[1]=="--scan-results"){nmap_scan_results();exit;}
if($argv[1]=="--scan-period"){nmap_scan_period();exit;}
if($argv[1]=="--scan-squid"){nmap_scan_squid();exit;}
if($argv[1]=="--scan-single"){nmap_scan_single($argv[2],$argv[3]);exit;}

$GLOBALS["COMPUTER"]=$argv[1];
$GLOBALS["COMPUTER"]=str_replace('$',"",$GLOBALS["COMPUTER"]);
if($GLOBALS["COMPUTER"]==null){echo "no computer name set {$argv[1]}!\n";die();}

$users=new usersMenus();
$sock=new sockets();
$ComputersAllowNmap=$sock->GET_INFO("ComputersAllowNmap");
if($ComputersAllowNmap==null){$ComputersAllowNmap=1;}
if($ComputersAllowNmap==0){die();}

$unix=new unix();

$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".time";

$oldpid=$unix->get_pid_from_file($pidfile);
if($unix->process_exists($oldpid)){if($GLOBALS["VERBOSE"]){echo "Already $oldpid running, aborting...\n";}return;}

@file_put_contents($pidfile, getmypid());
@file_put_contents($pidtime, time());


if(!is_file($users->NMAP_PATH)){echo "Unable to stat nmap binary file...\n";exit;}
$computer=new computers($GLOBALS["COMPUTER"].'$');
echo "Scanning \"{$GLOBALS["COMPUTER"]}\":[$computer->ComputerIP] (".__LINE__.")\n";
if($computer->ComputerIP=="0.0.0.0"){$computer->ComputerIP=null;}
if($computer->ComputerIP==null){$computer->ComputerIP=gethostbyname($GLOBALS["COMPUTER"]);}
if($computer->ComputerIP<>null){$cdir=$computer->ComputerIP;}else{$cdir=$GLOBALS["COMPUTER"];}
echo "Scanning $cdir and save results to /etc/artica-postfix/$cdir.map (".__LINE__.")\n";
$cmd=$users->NMAP_PATH." -v -F -PE -PN -O $cdir -oG --system-dns --version-light 2>&1";
echo "Executing $cmd (".__LINE__.")\n";
exec($cmd,$results);
@file_put_contents("/etc/artica-postfix/$cdir.map", @implode("\n", $results));

echo "Parsing results for $cdir (".__LINE__.")\n";
if(!is_file("/etc/artica-postfix/$cdir.map")){echo "Unable to stat /etc/artica-postfix/$cdir.map (".__LINE__.")\n";exit;}

parsefile("/etc/artica-postfix/$cdir.map",$GLOBALS["COMPUTER"]);   


function parsefile($filename,$uid,$perc=0){
	if($perc==0){$perc=10;}
	if($GLOBALS["VERBOSE"]){echo __LINE__."] Parsing file $filename\n";}
	$datas=file_get_contents($filename);
	$tbl=explode("\n",$datas);
	if(!is_array($tbl)){return null;}
	$ComputerMacAddress=null;
	$ComputerRunning=null;
	$ComputerMachineType=null;
	$ComputerOS=null;
	$cpid=null;
	
	while (list ($num, $ligne) = each ($tbl) ){
		if(trim($ligne)==null){continue;}
		if(preg_match("#([0-9]+).+?open\s+(.+)#",$ligne,$re)){
			$array[$re[1]]=$re[2];
			continue;
		}
		
		if(preg_match("#^Running:(.+)#",$ligne,$re)){
			if($GLOBALS["VERBOSE"]){echo __LINE__."] Running: {$re[1]}\n";}
			$ComputerRunning=$re[1];
			continue;
		}
		
		if(preg_match("#^OS details:(.+)#",$ligne,$re)){
			if($GLOBALS["VERBOSE"]){echo __LINE__."] OS details: {$re[1]}\n";}
			$ComputerOS=$re[1];
			continue;
		}	
	if(preg_match("#^MAC Address:(.+).+?\((.+?)\)#",$ligne,$re)){
			if($GLOBALS["VERBOSE"]){echo __LINE__."] MAC Address: {$re[1]}\n";}
			$ComputerMacAddress=$re[1];
			$ComputerMachineType=$re[2];
			continue;
		}				
		
		
		
		if(preg_match("#^MAC Address:(.+)#",$ligne,$re)){
			if($GLOBALS["VERBOSE"]){echo __LINE__."] MAC Address: {$re[1]}\n";}
			$ComputerMacAddress=$re[1];
			continue;
		}

		if(preg_match("#^MAC Address:\s+(.+?)\s+#",$ligne,$re)){
			if($GLOBALS["VERBOSE"]){echo __LINE__."] MAC Address: {$re[1]}\n";}
			$ComputerMacAddress=$re[1];
			continue;
		}
		
		if(preg_match("#^Aggressive OS guesses:\s+(.+)#",$ligne,$re)){
			if($GLOBALS["VERBOSE"]){echo __LINE__."] ******* Aggressive OS guesses: {$re[1]}\n";}
			$OSD=explode("-",$re[1]);
			
			while (list ($num, $xline) = each ($OSD) ){
				if($GLOBALS["VERBOSE"]){echo __LINE__."] $xline\n";}
				if(preg_match("#Apple iOS#", $xline)){$ComputerOS="Apple Mac OS";break;}
				if(preg_match("#Apple iPhone#", $xline)){$ComputerOS="Apple iPhone";break;}
				if(preg_match("#Apple Mac OS#", $xline)){$ComputerOS="Apple Mac OS";break;}
				
			}
			continue;
		}
		
		 
		if($GLOBALS["VERBOSE"]){echo __LINE__."] \"$ligne\" Not parsed...\n";}
		
	}
	
	
	if($ComputerMacAddress<>null){
		$computer=new computers();
		$cpid=$computer->ComputerIDFromMAC($ComputerMacAddress);
		build_progress("Analyze $ComputerMacAddress ",$perc+5);
		
	}
	
	if($cpid==null){$cpid=$uid;}
	echo "Save infos for $cpid (".__LINE__.")\n";
	echo "ComputerMacAddress: $ComputerMacAddress (".__LINE__.")\n";
	echo "ComputerOS: $ComputerOS (".__LINE__.")\n";
	
	build_progress("Adding {$cpid}$ ",$perc+5);
	$computer=new computers($cpid."$");
	if($ComputerMacAddress<>null){$computer->ComputerMacAddress=$ComputerMacAddress;}
	if($ComputerOS<>null){$computer->ComputerOS=$ComputerOS;}
	if($ComputerRunning<>null){$computer->ComputerRunning=$ComputerRunning;}
	if($ComputerMachineType<>null){$computer->ComputerMachineType=$ComputerMachineType;}
	if(is_array($array)){
		$computer->ComputerOpenPorts=base64_encode(serialize($array));
	}
	echo "Update it has $cpid with MAC $ComputerMacAddress (".__LINE__.")\n";
	if(!$computer->Edit()){
		echo "Failed to save infos for $cpid (".__LINE__.")\n";
	}
	build_progress("Done...",$perc+5);
	echo $datas;
	
}

function scannetworks(){
	
	if(system_is_overloaded(basename(__FILE__))){
		writelogs("Overloaded system, aborting",__FUNCTION__,__FILE__,__LINE__);
		return;
	}	
	
	$unix=new unix();
	$sock=new sockets();
	$nmap=$unix->find_program("nmap");
	$cdir=array();
	if(!is_file($nmap)){return false;}
	$ComputersAllowNmap=$sock->GET_INFO('ComputersAllowNmap');
	$NmapRotateMinutes=$sock->GET_INFO("NmapRotateMinutes");
	if(!is_numeric($ComputersAllowNmap)){$ComputersAllowNmap=1;}
	if(!is_numeric($NmapRotateMinutes)){$NmapRotateMinutes=60;}
	if($NmapRotateMinutes<5){$NmapRotateMinutes=5;}
	if($ComputersAllowNmap==0){return;}
	if(!$GLOBALS["VERBOSE"]){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
		$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".time";
		
		if($unix->file_time_min($pidtime)<$NmapRotateMinutes){
			if($GLOBALS["VERBOSE"]){echo "No time to be executed\n";}
			return;
		}
		
		
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid)){
			if($GLOBALS["VERBOSE"]){echo "Already $oldpid running, aborting...\n";}
			return;
		}
		
		@file_put_contents($pidfile, getmypid());
		@file_put_contents($pidtime, time());
	}
	
	$net=new networkscanner();
	while (list ($num, $maks) = each ($net->networklist)){if(trim($maks)==null){continue;}$hash[$maks]=$maks;}	
	while (list ($num, $maks) = each ($hash)){if(!$net->Networks_disabled[$maks]){if($GLOBALS["VERBOSE"]){echo "Network: $maks OK\n";}$cdir[]=$maks;}}
	if(count($cdir)==0){if($GLOBALS["VERBOSE"]){echo "No network, aborting...";}return;}
	$cmd=$unix->NMAP_CMDLINE(trim(@implode(" ", $cdir)), "/etc/artica-postfix/nmap.map")." 2>&1";

	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	exec($cmd,$results);
	
	while (list ($index, $ligne) = each ($results) ){
		if(preg_match("#\(([0-9]+).+?hosts.+?scanned in(.+)#", $ligne,$re)){
			$hosts=$re[1];
			$time=trim($re[2]);
			nmap_logs("$hosts scanned in $time",@implode("\n", $results));
			break;
		}
	}
	
	nmap_scan_results();
	
}

function nmap_scan_results(){
	if(!is_file("/etc/artica-postfix/nmap.map")){return;}
	$f=explode("\n", @file_get_contents("/etc/artica-postfix/nmap.map"));
	$ipaddr=null;
	$computer=array();
	while (list ($index, $ligne) = each ($f) ){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		if($ligne=="PORT  STATE  SERVICE"){continue;}
		if(strpos("    $ligne", "Network Distance:")>0){continue;}
		if(strpos("    $ligne", "tcp closed tcpmux")>0){continue;}
		if(strpos("    $ligne", "Too many fingerprints match")>0){continue;}
		if(strpos("    $ligne", "OS detection performed. Please report")>0){continue;}
		if(strpos("    $ligne", "OSScan results may be unreliable")>0){continue;}
		if(strpos("    $ligne", "/tcp filtered")>0){continue;}
		
		
		if(preg_match("#Nmap scan report for\s+(.+?)\s+\(([0-9\.]+)#", $ligne,$re)){
			$ipaddr=$re[2];
			$computer[$ipaddr]["IPADDR"]=$re[2];
			$computer[$ipaddr]["HOSTNAME"]=trim($re[1]);
			if($GLOBALS["VERBOSE"]){echo "Found IP:$ipaddr hostname=`{$re[1]}` in `$ligne`\n";}
			$LOGS[]="Found $ipaddr hostname= {$re[1]}";
			continue;
		}
		
		if(preg_match("#Interesting ports on (.*?)\s+\(([0-9\.]+)\)#", $ligne,$re)){
			$ipaddr=$re[2];
			$computer[$ipaddr]["IPADDR"]=$re[2];
			$computer[$ipaddr]["HOSTNAME"]=trim($re[1]);
			if($GLOBALS["VERBOSE"]){echo "Found IP:$ipaddr hostname=`{$re[1]}` in `$ligne`\n";}
			$LOGS[]="Found $ipaddr hostname= {$re[1]}";
			continue;
		}
		
		if(preg_match("#Interesting ports on ([0-9\.]+):#", $ligne,$re)){
			$ipaddr=$re[1];
			$computer[$ipaddr]["IPADDR"]=$re[1];
			if($GLOBALS["VERBOSE"]){echo "Found IP:$ipaddr only in `$ligne`\n";}
			$LOGS[]="Found $ipaddr only";
			continue;
		}
		
		
		if(preg_match("#Nmap scan report for ([0-9\.]+)$#", trim($ligne),$re)){
			$ipaddr=$re[1];
			$computer[$ipaddr]["IPADDR"]=$re[1];
			if($GLOBALS["VERBOSE"]){echo "[$ipaddr]: Found IP address `$ipaddr` without computername in `$ligne`\n";}
			$LOGS[]="Found $ipaddr without computername ";
			continue;
		}
		
		if(preg_match("#^MAC Address:\s+([0-9A-Z:]+)$#",trim($ligne),$re)){
			if(trim($ipaddr)==null){continue;}
			if(isset($MACSSCAN[trim($re[1])])){continue;}
			$computer[$ipaddr]["MAC"]=trim($re[1]);
			$LOGS[]="Found $ipaddr with mac {$re[1]} ";
			if($GLOBALS["VERBOSE"]){echo "[$ipaddr]: Found mac {$re[1]} in `$ligne`\n";}
			$MACSSCAN[trim($re[1])]=true;
			continue;
		}
		
		if(preg_match("#^MAC Address:(.+).+?\((.+?)\)#",$ligne,$re)){
			if(trim($ipaddr)==null){continue;}
			if(isset($MACSSCAN[trim($re[1])])){continue;}
			$MACSSCAN[trim($re[1])]=true;
			$computer[$ipaddr]["MAC"]=trim($re[1]);
			$computer[$ipaddr]["MACHINE_TYPE"]=trim($re[2]);
			if($GLOBALS["VERBOSE"]){echo "[$ipaddr]: Found mac {$re[1]} and machine type {$re[2]} in `$ligne`\n";}
			$LOGS[]="Found $ipaddr with mac {$re[1]} and machine type {$re[2]}";
			continue;
		}

		if(preg_match("#^Running:(.+)#",$ligne,$re)){
			if(trim($ipaddr)==null){continue;}
			if($GLOBALS["VERBOSE"]){echo "Found running in `$line`\n";}
			$computer[$ipaddr]["RUNNING"]=trim($re[1]);
			continue;
		}
		
		if(preg_match("#^OS details:(.+)#",$ligne,$re)){
			if($GLOBALS["VERBOSE"]){echo "[$ipaddr]: Found OS {$re[1]} in `$ligne`\n";}
			$LOGS[]="Found $ipaddr with OS {$re[1]}";
			$computer[$ipaddr]["OS"]=trim($re[1]);
			continue;
		}	

		if($GLOBALS["VERBOSE"]){echo "[$ipaddr]: Not understood in `$ligne`\n";}
		
		
	}
	nmap_logs(count($f). " analyzed lines",@implode("\n", $LOGS));
	
	
	$c=0;

	
	$prefix_sql="INSERT IGNORE INTO computers_lastscan (`MAC`, `zDate`,`ipaddr`,`hostname`,`Info`) VALUES ";
	
	while (list ($ipaddr, $array) = each ($computer) ){
		if(!isset($array["MAC"])){continue;}
		$mac=trim($array["MAC"]);
		if(isset($already[$mac])){continue;}
		if($mac==null){continue;}
		$c++;
		$already[$mac]=true;
		
		$ldap_ipaddr=null;
		$ComputerRealName=null;
		$uid=null;
		$RAISON=array();
		if(!isset($array["HOSTNAME"])){$array["HOSTNAME"]=null;}
		if(!isset($array["OS"])){$array["OS"]=null;}
		if(!isset($array["RUNNING"])){$array["RUNNING"]=null;}
		if(!isset($array["MACHINE_TYPE"])){$array["MACHINE_TYPE"]=null;}
		$date=date('Y-m-d H:i:s');
		
		$infos=addslashes($array["OS"]. " Type:{$array["MACHINE_TYPE"]} ");
		
		$SQLAD[]="('$mac','$date','$ipaddr','{$array["HOSTNAME"]}','$infos')";
	
		$cmp=new computers(null);
		$uid=$cmp->ComputerIDFromMAC($mac);
		if($uid<>null){
			if($GLOBALS["VERBOSE"]){echo "$mac = $uid\n";}
			$cmp=new computers($uid);
			
			$ldap_ipaddr=$cmp->ComputerIP;
			$ComputerRealName=$cmp->ComputerRealName;
			if($GLOBALS["VERBOSE"]){echo "$mac = $uid\nLDAP:$ldap_ipaddr<>NMAP:$ipaddr\nLDAP CMP:$ComputerRealName<>NMAP:{$array["HOSTNAME"]}";}
			if($array["HOSTNAME"]<>null){
				$EXPECTED_UID=strtoupper($array["HOSTNAME"])."$";
				if($EXPECTED_UID<>$uid){
					$RAISON[]="UID: $uid is different from $EXPECTED_UID";
					nmap_logs("EDIT UID: $mac:[{$array["HOSTNAME"]}] ($ipaddr)",@implode("\n", $array)."\n".@implode("\n", $RAISON),$uid);
					$cmp->update_uid($EXPECTED_UID);
				}
			}
			if($ldap_ipaddr<>$ipaddr){
				writelogs("Change $ldap_ipaddr -> to $ipaddr for  $cmp->uid",__FUNCTION__,__FILE__,__LINE__);
				$RAISON[]="LDAP IP ADDR: $ldap_ipaddr is different from $ipaddr";
				$RAISON[]="DN: $cmp->dn";
				$RAISON[]="UID: $cmp->uid";
				$RAISON[]="MAC: $cmp->ComputerMacAddress";
				if(!$cmp->update_ipaddr($ipaddr)){$RAISON[]="ERROR:$cmp->ldap_last_error";}
				nmap_logs("EDIT IP: $mac:[{$array["HOSTNAME"]}] ($ipaddr)",@implode("\n", $array)."\n".@implode("\n", $RAISON),$uid);
				
			}
			if($array["OS"]<>null){
				if(strtolower($cmp->ComputerOS=="Unknown")){$cmp->ComputerOS=null;}
				if($cmp->ComputerOS==null){
					$RAISON[]="LDAP OS: $cmp->ComputerOS is different from {$array["OS"]}";
					nmap_logs("EDIT OS: $mac:[{$array["HOSTNAME"]}] ($ipaddr)",@implode("\n", $array)."\n".@implode("\n", $RAISON),$uid);
					$cmp->update_OS($array["OS"]);
				}
			}
			
			
			
		}else{
			if($array["HOSTNAME"]<>null){$uid="{$array["HOSTNAME"]}$";}else{continue;}
			nmap_logs("ADD NEW: $mac:[{$array["HOSTNAME"]}] ($ipaddr)",@implode("\n", $array)."\n".@implode("\n", $RAISON),"$uid");
			$cmp=new computers();
			$cmp->ComputerIP=$ipaddr;
			$cmp->ComputerMacAddress=$mac;
			$cmp->uid="$uid";
			$cmp->ComputerOS=$array["OS"];
			$cmp->ComputerRunning=$array["RUNNING"];
			$cmp->ComputerMachineType=$array["MACHINE_TYPE"];
			$cmp->Add();
		}
		
		
		
		
		
	}
	
	if($GLOBALS["VERBOSE"]){echo "*** ".count($SQLAD). " MYsql queries...***\n";}
	system_admin_events("$c hosts analyzed in networks",__FUNCTION__,__FILE__,__LINE__,"nmap");
	nmap_logs("$c hosts analyzed in networks",@file_get_contents("/etc/artica-postfix/nmap.map"),null);
	if(count($SQLAD)>0){
		
		$q=new mysql();
		$q->QUERY_SQL("DROP TABLE computers_lastscan","artica_backup");
		$q->check_storage_table(true);
		$final=$prefix_sql.@implode(",", $SQLAD);
		if($GLOBALS["VERBOSE"]){echo "*** $final ***\n";}
		$q->QUERY_SQL($prefix_sql.@implode(",", $SQLAD),"artica_backup");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	
	@unlink("/etc/artica-postfix/nmap.map");
	//print_r($computer);
	
}



function nmap_logs($subject,$text,$uid=null){
	$subject=addslashes($subject);
	$text=addslashes($text);
	if($GLOBALS["VERBOSE"]){echo $subject."\n";}
	$sql="INSERT INTO nmap_events (subject,text,uid) VALUES ('$subject','$text','$uid');";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_events");
}
function nmap_scan_period(){
	if(system_is_overloaded(basename(__FILE__))){
		
		writelogs("Overloaded system, aborting",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$oldpid=@file_get_contents($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){die();}
	
	@unlink($pidfile);
	@file_put_contents($pidfile, getmypid());
	
	$sock=new sockets();
	$EnableScanComputersNet=$sock->GET_INFO("EnableScanComputersNet");
	if(!is_numeric($EnableScanComputersNet)){$EnableScanComputersNet=0;}
	if($EnableScanComputersNet==0){die();}
	
	
	$EnableScanComputersNetSchedule=$sock->GET_INFO("EnableScanComputersNetSchedule");
	if(!is_numeric($EnableScanComputersNetSchedule)){$EnableScanComputersNetSchedule=15;}
	if($EnableScanComputersNetSchedule<5){$EnableScanComputersNetSchedule=5;}	
	
	$time=$unix->file_time_min($pidtime);
	if($time<$EnableScanComputersNetSchedule){die();}
	@unlink($pidtime);@file_put_contents($pidtime, time());
	
	
	$sql="SELECT MACADDR,IPADDRESS FROM networks";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"ocsweb");
	$computer=new computers();
	if(!$q->ok){if(preg_match("#Unknown database#", $q->mysql_error)){$sock=new sockets();$sock->getFrameWork("services.php?mysql-ocs=yes");$results=$q->QUERY_SQL($sql,"ocsweb");}return;}
	if(!$q->FIELD_EXISTS("networks", "isActive", "ocsweb")){$q->QUERY_SQL("ALTER TABLE `networks` ADD `isActive` SMALLINT( 1 ) NOT NULL DEFAULT '0',ADD INDEX ( `isActive` ) ","ocsweb");}
	$users=new usersMenus();
	if(!is_file("$users->NMAP_PATH")){return null;}
	
	$cmp=new computers();
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$MACADDR=$ligne["MACADDR"];
		$IPADDRESS=$ligne["IPADDRESS"];
		$cmd=$users->NMAP_PATH." -v -F -PE -PN -O $IPADDRESS  --system-dns --version-light 2>&1";
		$resultsScan=array();
		exec($cmd,$resultsScan);
		$PORTS=array();
		$osDetails=null;
		$uid=null;
		$UpTime=null;
		$LIVE=false;
		$MACSSCAN=null;
		while (list ($index, $line) = each ($resultsScan) ){
			if(preg_match("#Nmap scan report for.+?host down#", $line)){
				if($GLOBALS["VERBOSE"]){echo "$MACADDR ($IPADDRESS) DOWN\n";}
				nmap_scan_period_save($IPADDRESS,$MACADDR,0);
				break;
			}
			
			
			if(preg_match("#([0-9]+).+?open\s+(.+)#",$line,$re)){
				$PORTS[$re[1]]=$re[2];
				continue;
			}

			if(preg_match("#^OS details:(.+)#",$line,$re)){				
				$osDetails=trim($re[1]);
				if(preg_match("#Microsoft.+?Windows.+?7#i",$osDetails)){$osDetails="Windows 7";}	
				continue;
			}

			if(preg_match("#^Uptime guess:\s+(.+)#",$line,$re)){
				$UpTime=$re[1];
				continue;
			}
			
			if(preg_match("#^MAC Address:\s+([0-9A-Z:]+)$#",trim($line),$re)){
				$MACSSCAN=trim($re[1]);
				continue;
			}
		
			if(preg_match("#^MAC Address:(.+).+?\((.+?)\)#",$line,$re)){
				$MACSSCAN=trim($re[1]);
				continue;
			}			
			
			
		}
		
		
		
		if(count($PORTS)>0){
			if(is_array($PORTS)){
				$uid=$cmp->ComputerIDFromMAC($MACADDR);
				$cmp=new computers($uid);
				$portser=serialize($PORTS);
				$cmp->UpdateComputerOpenPorts(base64_encode($portser));
				$PORTS=array();
				$LIVE=true;
			}
			
		}
		
		if($MACADDR=="unknown"){if($MACSSCAN<>null){$MACADDR=$MACSSCAN;}}
		
		if($osDetails<>null){if($uid==null){$uid=$cmp->ComputerIDFromMAC($MACADDR);$cmp=new computers($uid);}if($cmp->ComputerOS<>$osDetails){$cmp->update_OS($osDetails);}$LIVE=true;}
		if($UpTime<>null){if($uid==null){$uid=$cmp->ComputerIDFromMAC($MACADDR);$cmp=new computers($uid);}$cmp->UpdateComputerUpTime($UpTime);$LIVE=true;}
		if($LIVE){
			if($GLOBALS["VERBOSE"]){echo "$IPADDRESS/$MACADDR ".count($PORTS)." ports ($osDetails) TTL:$UpTime\n";}
			nmap_scan_period_save($IPADDRESS,$MACADDR,1);$LIVE=false;continue;
		}
		if($GLOBALS["VERBOSE"]){echo "$IPADDRESS/$MACADDR DOWN\n";}
	
	}	
}

function nmap_scan_period_save($ipaddr,$mac,$status){
	$date=date('Y-m-d H:i:s');
	$q=new mysql();
	if($status==1){
		$sql="INSERT IGNORE INTO computers_available (zDate,ipaddr,MAC,live) VALUES ('$date','$ipaddr','$mac','$status')";
		$q->QUERY_SQL($sql,"artica_events");
	}
	$sql="UPDATE networks SET isActive='$status' WHERE MACADDR='$mac'";
	$q->QUERY_SQL($sql,"ocsweb");
	
}

function nmap_scan_single($mac,$ipaddrZ=null){
	$unix=new unix();
	$users=new usersMenus();
	if(!is_file($users->NMAP_PATH)){ build_progress("{failed} err.".__LINE__,110); return;}
	if($mac=="00:00:00:00:00:00"){$mac=null;}
	
	if($mac==null){
		if($ipaddrZ==null){
			build_progress("{failed} err.".__LINE__,110); 
			return;
		}
	}
	
	build_progress("Determine IP addresses",5);
	
	if($ipaddrZ<>null){
		$ipaddr[$ipaddrZ]=true;
	}
	
	if($mac<>null){
		
		
		$computer =new computers();
		$uid=$computer->ComputerIDFromMAC($mac);
		if($uid<>null){
			$computer =new computers($uid);
			$ipaddr[$computer->ComputerIP]=true;
			
		}
		
		$q=new mysql_squid_builder();
		$results=$q->QUERY_SQL("SELECT ipaddr,MAC FROM UserAutDB GROUP BY ipaddr,MAC HAVING MAC='$mac' AND LENGTH(ipaddr)>0");
		$count=mysql_num_rows($results);
		if($count>0){
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$ipaddr[$ligne["ipaddr"]]=true;
			}
		}
	}
	
	if(count($ipaddr)==0){
		build_progress("{failed} no ip found err.".__LINE__,110);
		return;
	}
	
	build_progress("Scanning ".count($ipaddr)." nodes",10);
	
	$i=10;
	$NICE=EXEC_NICE();
	while (list ($IPADDRESS, $line) = each ($ipaddr) ){
		$i=$i+5;
		build_progress("Scanning $IPADDRESS",$i);
		if(!$unix->PingHostCMD($IPADDRESS)){continue;}
		$cmd=trim($NICE." ".$users->NMAP_PATH." -v -F -PE -PN -O $IPADDRESS  --system-dns --version-light 2>&1");
		build_progress("Scanning $IPADDRESS done...",$i);
		$resultsScan=array();
		exec($cmd,$resultsScan);
		$tmpfile=$unix->TEMP_DIR()."/nmap.$IPADDRESS.log";
		@file_put_contents($tmpfile, @implode("\n", $resultsScan));
		echo @implode("\n", $resultsScan);
		$array=ExecArrayToArray($resultsScan);
		if(!is_array($array)){continue;}
		if($array["MAC"]<>$mac){continue;}
		build_progress("$mac:-> $IPADDRESS OK",$i+5);
		echo "$mac:-> $IPADDRESS OK\n";
		$data=base64_encode(serialize($array));
		$sql="UPDATE webfilters_nodes SET nmap=1,nmapreport='$data' WHERE MAC='$mac'";
		$q->QUERY_SQL($sql);
		build_progress("Analyze scan...",$i+5);
		parsefile($tmpfile,null,$i);
		
		
	}
	build_progress("Done...",100);
	
		
	
}
function build_progress($text,$pourc){
	$cachefile="/usr/share/artica-postfix/ressources/logs/nmap.single.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	
}

function nmap_scan_squid(){
	
	
	$users=new usersMenus();
	if(!is_file($users->NMAP_PATH)){return;}
	
	$unix=new unix();
	$pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".time";
	

	if(system_is_overloaded(basename(__FILE__))){
		writelogs("Overloaded system, aborting",__FUNCTION__,__FILE__,__LINE__);
		return;
	}	
	
	$TimeF=$unix->file_time_min($pidTime);
	if($TimeF<10){return;}
	
	$oldpid=$unix->get_pid_from_file($pidpath);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		writelogs(basename(__FILE__).":Already executed.. PID: $oldpid aborting the process",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	
	
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	@file_put_contents($pidpath, getmypid());
	
	
	
	
	include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
	$q=new mysql_squid_builder();
	$sql="SELECT MAC FROM webfilters_nodes WHERE nmap=0";
	$results=$q->QUERY_SQL($sql);
	if(mysql_num_rows($results)==0){return;}
	
	$c=0;$d=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
		$c++;
		$d++;
		$mac=$ligne["MAC"];
		nmap_scan_squid_mac($mac);
		if($c>10){
			if(system_is_overloaded(basename(__FILE__))){
				writelogs("Overloaded system, aborting after $d scans",__FUNCTION__,__FILE__,__LINE__);
				return;
			}
			$c=0;
		}
		
	}
	
}

function nmap_scan_squid_mac($mac){
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL("SELECT ipaddr,MAC FROM UserAutDB GROUP BY ipaddr,MAC HAVING MAC='$mac' AND LENGTH(ipaddr)>0");
	if(!$q->ok){echo $q->mysql_error;return;}
	$count=mysql_num_rows($results);
	if($count==0){return;}
	$unix=new unix();
	$users=new usersMenus();
	$NICE=EXEC_NICE();
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
		
		$IPADDRESS=$ligne["ipaddr"];
		if(!$unix->PingHostCMD($IPADDRESS)){continue;}
		
		
		$cmd=trim($NICE." ".$users->NMAP_PATH." -v -F -PE -PN -O $IPADDRESS  --system-dns --version-light 2>&1");
		$resultsScan=array();
		exec($cmd,$resultsScan);
		$array=ExecArrayToArray($resultsScan);
		if(!is_array($array)){continue;}
		if($array["MAC"]<>$mac){continue;}
		echo "$mac:-> $IPADDRESS OK\n";
		$data=base64_encode(serialize($array));
		$sql="UPDATE webfilters_nodes SET nmap=1,nmapreport='$data' WHERE MAC='$mac'";
		$q->QUERY_SQL($sql);
	}
	
}

function ExecArrayToArray($array){
	$osDetails=null;
	$UpTime=null;
	$MACSSCAN=null;
	$PORTS=array();
	if(count($array)<2){return;}
	while (list ($index, $line) = each ($array) ){
	
			if(preg_match("#Nmap scan report for.+?host down#", $line)){if($GLOBALS["VERBOSE"]){echo "DOWN\n";}return null;}
			
			
			if(preg_match("#([0-9]+).+?open\s+(.+)#",$line,$re)){
				$PORTS[$re[1]]=$re[2];
				continue;
			}

			if(preg_match("#^OS details:(.+)#",$line,$re)){				
				$osDetails=trim($re[1]);
				if(preg_match("#Microsoft.+?Windows.+?7#i",$osDetails)){$osDetails="Windows 7";}	
				continue;
			}

			if(preg_match("#^Uptime guess:\s+(.+)#",$line,$re)){
				$UpTime=$re[1];
				continue;
			}
			
			if(preg_match("#^MAC Address:\s+([0-9A-Z:]+)$#",trim($line),$re)){
				$MACSSCAN=trim(strtolower($re[1]));
				continue;
			}
		
			if(preg_match("#^MAC Address:(.+).+?\((.+?)\)#",$line,$re)){
				$MACSSCAN=trim(strtolower($re[1]));
				continue;
			}

			if(preg_match("#OS.+?i686-pc-linux-gnu#",$line,$re)){
				if($osDetails==null){$osDetails="Linux i686";}
			}
			
				
	}
	
	
	$array=array(
		"OS"=>$osDetails,"MAC"=>$MACSSCAN,"UPTIME"=>$UpTime,"PORTS"=>$PORTS);
	
	return $array;
	

}
	
	
?>