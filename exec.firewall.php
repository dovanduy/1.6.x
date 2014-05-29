#!/usr/bin/php
<?php
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/ressources/class.templates.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/framework/class.tcpip-parser.inc');
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.nics.inc');
include_once(dirname(__FILE__) . '/ressources/class.os.system.inc');


if($argv[1]=="--build"){build();exit;}
if($argv[1]=="--remove"){iptables_delete_all();exit;}

function iptables_delete_all(){
	$unix=new unix();
	$iptables_save=$unix->find_program("iptables-save");
	$iptables_restore=$unix->find_program("iptables-restore");
	system("$iptables_save > /etc/artica-postfix/iptables.conf");
	$data=file_get_contents("/etc/artica-postfix/iptables.conf");
	$datas=explode("\n",$data);
	$pattern="#.+?ArticaFireWall#";
	$c=0;
	while (list ($num, $ligne) = each ($datas) ){
		if($ligne==null){continue;}
		if(preg_match($pattern,$ligne)){$c++;continue;}
		$conf=$conf . $ligne."\n";
	}

	file_put_contents("/etc/artica-postfix/iptables.new.conf",$conf);
	system("$iptables_restore < /etc/artica-postfix/iptables.new.conf");
	if($c>0){echo "$c Firewall rules removed\n";}
}

function build(){
	$unix=new unix();
	$q=new mysql();
	$sock=new sockets();
	iptables_delete_all();

	
	
	if(!$q->FIELD_EXISTS("nics","isFWAcceptNet","artica_backup")){
		$sql="ALTER TABLE `nics` ADD `isFWAcceptNet` smallint( 1 ) NOT NULL DEFAULT '0'";
		$q->QUERY_SQL($sql,'artica_backup');
		if(!$q->ok){ echo "[".__LINE__."]: $q->mysql_error\n";}
	}
	
	$sql="SELECT `Interface`,`Bridged`,`BridgedTo`,`isFWAcceptNet`,`isFWLogBlocked` FROM `nics` WHERE `isFW`=1 AND `Bridged`=0";
	if($GLOBALS["VERBOSE"]){echo "[".__LINE__."] $sql\n";}
	$echo=$unix->find_program("echo");
	$php=$unix->LOCATE_PHP5_BIN();
	$SCRIPT[]="#! /bin/sh";
	$SCRIPT[]="$echo \"Removing Firewall rules...\"";
	
	$SCRIPT[]=$php." ".__FILE__." --remove || true";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	$CountDeInterface=mysql_num_rows($results);
	$SCRIPT[]="$echo \"Firewall enabled on $CountDeInterface Interface(s)\"";
	$iptables=$unix->find_program("iptables");
	$MARKLOG="-m comment --comment \"ArticaFireWall\"";
	
	$net=new networkscanner();
	while (list ($num, $maks) = each ($net->networklist)){
		if(trim($maks)==null){continue;}
		$SCRIPT[]="# Accept potential Network $maks";
		$hash[$maks]=$maks;
	}
	$ALL_RULES=0;
	if($CountDeInterface>0){
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$ALL_RULES++;
			$FINAL_LOG_DROP=null;
			$J_LOGPRX="-j LOG --log-prefix \"ARTICA-FW-DROP-{$ligne["Interface"]}-0:\"";
			$InInterface=" -i {$ligne["Interface"]} ";
			$FINAL1="$iptables -A INPUT $InInterface $MARKLOG -j REJECT || true";
			if($ligne["isFWLogBlocked"]==1){
				$FINAL_LOG_DROP="$iptables -A INPUT $InInterface $MARKLOG $J_LOGPRX || true";
			}
		
			reset($hash);
			$SCRIPT[]="$iptables -I INPUT $InInterface -s 127.0.0.1 $MARKLOG -j ACCEPT || true";
			$SCRIPT[]="$iptables -I INPUT $InInterface -d 127.0.0.1 $MARKLOG -j ACCEPT || true";
			while (list ($num, $maks) = each ($hash)){
				$SCRIPT[]="$iptables -I INPUT $InInterface -d $maks $MARKLOG -j ACCEPT || true";
				$SCRIPT[]="$iptables -I INPUT $InInterface -s $maks $MARKLOG -j ACCEPT || true";
			}
			
			
			$SCRIPT[]=BuilFWdRules($ligne["Interface"],"INPUT",$ligne["isFWLogBlocked"]);
			$SCRIPT[]=BuilFWdRules($ligne["Interface"],"OUTPUT",$ligne["isFWLogBlocked"]);
			$SCRIPT[]=BuilFWdRules_FORWARD($ligne["Interface"],$ligne["isFWLogBlocked"]);
			
		
		if($FINAL_LOG_DROP<>null){$SCRIPT_FINAL[]=$FINAL_LOG_DROP;}
		$SCRIPT_FINAL[]=$FINAL1;
		}
	}
	
	
	$sql="SELECT * FROM `nics_bridge` WHERE `isFW`=1";
	if($GLOBALS["VERBOSE"]){echo "[".__LINE__."] $sql\n";}
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		$SCRIPT[]="#".str_replace("\n", " ", $q->mysql_error);
	}
	$CountDeInterface=mysql_num_rows($results);
	$SCRIPT[]="$echo \"Firewall enabled on $CountDeInterface Bridge(s)\"";
		
	if($CountDeInterface>0){
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$ALL_RULES++;
			$J_LOGPRX="-j LOG --log-prefix \"ARTICA-FW-DROP-br{$ligne["ID"]}-0:\"";
			
			
			$SCRIPT[]="$echo \"Apply rules on bridge br{$ligne["ID"]} log block={$ligne["isFWLogBlocked"]}\"";
			$interface="br{$ligne["ID"]}";
			$InInterface=" -i $interface ";
			
			$SCRIPT[]="$iptables -I INPUT $InInterface -s 127.0.0.1 $MARKLOG -j ACCEPT || true";
			$SCRIPT[]="$iptables -I INPUT $InInterface -d 127.0.0.1 $MARKLOG -j ACCEPT || true";
			reset($hash);
			while (list ($num, $maks) = each ($hash)){
				$SCRIPT[]="$iptables -I INPUT $InInterface -d $maks $MARKLOG -j ACCEPT || true";
				$SCRIPT[]="$iptables -I INPUT $InInterface -s $maks $MARKLOG -j ACCEPT || true";
			}
			
			$SCRIPT[]=BuilFWdRules($interface,"INPUT",$ligne["isFWLogBlocked"]);
			$SCRIPT[]=BuilFWdRules($interface,"OUTPUT",$ligne["isFWLogBlocked"]);
			$SCRIPT[]=BuilFWdRules_FORWARD($interface,$ligne["isFWLogBlocked"]);
			
			if($ligne["isFWLogBlocked"]==1){
				$FINAL_LOG_DROP="$iptables -A INPUT $InInterface $MARKLOG $J_LOGPRX || true";
			}
			
			$SCRIPT_FINAL[]="$iptables -A INPUT $InInterface $MARKLOG -j REJECT || true";
			
	
	
		}

	}
	
	$SCRIPT_FINAL[]=ProtectArtica();
	
	
	$SCRIPT[]="#Final step, block necessaries connections";
	$SCRIPT[]=$FINAL_LOG_DROP;
	$SCRIPT[]=@implode("\n", $SCRIPT_FINAL);
	$SCRIPT[]="exit 0\n";
	@file_put_contents("/bin/artica-firewall.sh", @implode("\n", $SCRIPT));
	@chmod("/bin/artica-firewall.sh",0755);
	echo "[".__LINE__."]: /bin/artica-firewall.sh done...\n";
	
}

function ProtectArtica(){
	
	$sock=new sockets();
	$unix=new unix();
	$q=new mysql();
	$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES(true);
	$LighttpdArticaListenIP=$sock->GET_INFO("LighttpdArticaListenIP");
	$ArticaHttpsPort=intval($sock->GET_INFO("ArticaHttpsPort"));
	$iptables=$unix->find_program("iptables");
	if(!isset($NETWORK_ALL_INTERFACES[$LighttpdArticaListenIP])){$LighttpdArticaListenIP=null;}
	if($ArticaHttpsPort==0){$ArticaHttpsPort=9000;}

	$MARKLOG="-m comment --comment \"ArticaFireWall\"";
	
	$SCRIPT_FINAL[]="";
	$SCRIPT_FINAL[]="#Artica Web interface listens on `$LighttpdArticaListenIP` port:{$ArticaHttpsPort}";
	
	if($LighttpdArticaListenIP<>null){$LighttpdArticaListenIP=" -d $LighttpdArticaListenIP";}
	$CountOfRules=$q->COUNT_ROWS("iptables_webint","artica_backup");
	
	if($CountOfRules==0){
		$SCRIPT_FINAL[]="#This rule allow connections to the Web interface in order to allow access to Artica Web interface";
		$SCRIPT_FINAL[]="$iptables -I INPUT$LighttpdArticaListenIP -p tcp --dport $ArticaHttpsPort $MARKLOG -j ACCEPT || true";
		$SCRIPT_FINAL[]="";
		return @implode("\n", $SCRIPT_FINAL);
	}
	
	
	$SCRIPT_FINAL[]="#This rule allow connection to the Web interface for only $CountOfRules items";

	$SCRIPT_FINAL[]="$iptables -I INPUT$LighttpdArticaListenIP -p tcp --dport $ArticaHttpsPort $MARKLOG -j DROP || true";
	$SCRIPT_FINAL[]="$iptables -I INPUT$LighttpdArticaListenIP -p tcp --dport $ArticaHttpsPort $MARKLOG -j LOG --log-prefix \"ARTICA-WEB-DROP:\" || true";
	
	$results=$q->QUERY_SQL("SELECT * FROM iptables_webint","artica_backup");
	if(!$q->ok){
		$q->mysql_error=str_replace("\n", "", $q->mysql_error);
		$SCRIPT_FINAL[]="# $q->mysql_error";
		$SCRIPT_FINAL[]="#This rule allow connections to the Web interface in order to allow access to Artica Web interface";
		$SCRIPT_FINAL[]="$iptables -I INPUT$LighttpdArticaListenIP -p tcp --dport $ArticaHttpsPort $MARKLOG -j ACCEPT || true";
		$SCRIPT_FINAL[]="";
		return @implode("\n", $SCRIPT_FINAL);
	}
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$SCRIPT_FINAL[]="$iptables -I INPUT -s {$ligne["pattern"]} $LighttpdArticaListenIP -p tcp --dport $ArticaHttpsPort $MARKLOG -j ACCEPT || true";
	}
	
	$SCRIPT_FINAL[]="";
	return @implode("\n", $SCRIPT_FINAL);
	
	
	
}

function BuilFWdRules_FORWARD($interface){
	$q=new mysql();
	$unix=new unix();
	$echo=$unix->find_program("echo");
	$php=$unix->LOCATE_PHP5_BIN();
	$iptables=$unix->find_program("iptables");
	$MARKLOG="-m comment --comment \"ArticaFireWall\"";
	
	$sql="SELECT * FROM iptables_main WHERE
	iptables_main.eth='$interface' AND iptables_main.MOD='FORWARD'
	AND enabled=1
	ORDER BY zOrder";
	
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){ echo "[".__LINE__."]: $q->mysql_error\n";}
	$CountDeRules=mysql_num_rows($results);
	$SCRIPT[]="";
	$SCRIPT[]="$echo \"$interface/FORWARD -> $CountDeRules rules(s)\"";	
	$SCRIPT[]="################## FORWARD ##################";
	
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){	
		$InInterface=" -i $interface ";
		$J_LOGPRX="-j LOG --log-prefix \"ARTICA-FW-DROP-$interface-{$ligne["ID"]}:\"";
		$OutInterface=null;
		$proto=null;
		$ACTION=$ligne["accepttype"];
		$APPEND="-A";
		$J=" -j $ACTION";
		if($ligne["OverideNet"]==1){$APPEND="-I";}
		if($ligne["ForwardNIC"]<>null){$OutInterface=" -o {$ligne["ForwardNIC"]} ";}
		if($ligne["proto"]<>null){$proto=" -p {$ligne["proto"]} ";}
		$GroupTime=GroupTime($ligne);
		
		$prefix="$iptables $APPEND FORWARD $InInterface$OutInterface$proto$GroupTime ";
		$forward_prefix="$iptables $APPEND PREROUTING -t nat$InInterface$OutInterface$proto$GroupTime ";
		
		
		$sourcegroups=GroupInArray($ligne["source_group"]);
		$DestGroups=GroupInArray($ligne["dest_group"]);
		$portsGroups=GroupInArray($ligne["destport_group"]);
		$ForwardTo=GroupForward($ligne["ForwardTo"]);
		if(is_array($portsGroups)){$portsGroups=null;}	
		
		
		if( (count($sourcegroups)>0 ) AND (count($DestGroups)>0) ){
			echo "[".__LINE__."]: MODE 0:: Source(s) and Desintation(s)\n";
			while (list ($itemSRC, $b) = each ($sourcegroups) ){
				while (list ($itemDST, $b) = each ($DestGroups) ){
					$SCRIPT[]=$prefix."$itemSRC $itemDST $portsGroups $MARKLOG $J || true";
					if($ACTION=="DROP"){continue;}
					if($ForwardTo<>null){
						$SCRIPT[]=$forward_prefix."$itemSRC $itemDST $portsGroups $MARKLOG -j DNAT --to-destination $ForwardTo || true";
					}	
				}
			}
		
			continue;}
				
				
			if( (count($sourcegroups)==0 ) AND (count($DestGroups)>0) ){
				echo "[".__LINE__."]:  MODE 1:: Source = 0 and Destination >0 portsGroups = $portsGroups\n";

				
				while (list ($itemDST, $b) = each ($DestGroups) ){
					$SCRIPT[]=$prefix."$itemDST $portsGroups $MARKLOG $J || true";
					if($ACTION=="DROP"){continue;}
					if($ForwardTo<>null){
						$SCRIPT[]=$forward_prefix."$itemDST $portsGroups $MARKLOG -j DNAT --to-destination $ForwardTo || true";
					}	
				}
				continue;
			}
		
		
			if( (count($sourcegroups)>0 ) AND (count($DestGroups)==0) ){
				if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]:  MODE 2:: Source > 0 and Dests == 0 portsGroups = $portsGroups\n";}
					while (list ($itemSRC, $b) = each ($sourcegroups) ){
						$SCRIPT[]=$prefix."$itemSRC $portsGroups $MARKLOG $J || true";
						if($ACTION=="DROP"){continue;}
						if($ForwardTo<>null){
							$SCRIPT[]=$forward_prefix."$itemSRC $portsGroups $MARKLOG -j DNAT --to-destination $ForwardTo || true";
						}
				}
			continue;
			}
		
			if( (count($sourcegroups)==0 ) AND (count($DestGroups)==0) ){
				if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]:  MODE 3:: Source == 0 and Dests == 0\n";}
				$SCRIPT[]=$prefix."$portsGroups $MARKLOG $J || true";
				if($ACTION=="DROP"){continue;}
				if($ForwardTo<>null){
					$SCRIPT[]=$forward_prefix." $portsGroups $MARKLOG -j DNAT --to-destination $ForwardTo || true";
				}
				continue;
			}
		
			if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: Unknown ???\n";}
		
		}
		return @implode("\n", $SCRIPT);
	
}

function GroupForward($host){
	if($host==null){return null;}
	$port=null;
	$ip=new IP();
	if(preg_match("#(.*?):([0-9]+)#", $host,$re)){
		$host=$re[1];
		$port=":{$re[2]}";
	}
	
	if(!$ip->isValid($host)){
		$host=gethostbyname($host);
		return "$host$port";
	}
	if($port<>null){return "$host$port";}
	return $host;
}


function BuilFWdRules($interface,$TABLE,$isFWLogBlocked=0){
	$q=new mysql();
	$unix=new unix();
	$echo=$unix->find_program("echo");
	$php=$unix->LOCATE_PHP5_BIN();
	$iptables=$unix->find_program("iptables");
	$MARKLOG="-m comment --comment \"ArticaFireWall\"";
	
	$sql="SELECT * FROM iptables_main WHERE 
			iptables_main.eth='$interface' AND iptables_main.MOD='$TABLE'
			AND enabled=1
			ORDER BY zOrder";
	
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){ echo "[".__LINE__."]: $q->mysql_error\n";}
	$CountDeRules=mysql_num_rows($results);
	$SCRIPT[]="";
	$SCRIPT[]="$echo \"$interface: $TABLE -> $CountDeRules rules(s)\"";
	$SCRIPT[]="################## $TABLE ##################";
	if($CountDeRules==0){return @implode("\n", $SCRIPT);}
	
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$J_LOGPRX="-j LOG --log-prefix \"ARTICA-FW-DROP-$interface-{$ligne["ID"]}:\"";
		$rulename=$ligne["rulename"];
		$rulename=str_replace('"', "`", $rulename);
		$SCRIPT[]="$echo \"$interface: Firewall Rule $rulename\"";
		$OutInterface=null;
		$proto=null;
		$InInterface=" -i $interface ";
		$APPEND="-A";
		$J=" -j {$ligne["accepttype"]}";
		if($ligne["OverideNet"]==1){$APPEND="-I";}
		if($ligne["proto"]<>null){$proto=" -p {$ligne["proto"]} ";}
		$GroupTime=GroupTime($ligne);
		
		
		$prefix="$iptables $APPEND $TABLE $InInterface$proto";
		$sourcegroups=GroupInArray($ligne["source_group"]);
		$DestGroups=GroupInArray($ligne["dest_group"]);
		$portsGroups=GroupInArray($ligne["destport_group"]);
		if(is_array($portsGroups)){$portsGroups=null;}
	
		if( (count($sourcegroups)>0 ) AND (count($DestGroups)>0) ){
			if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: MODE 0:: Sources and Destss\n";}
			while (list ($itemSRC, $b) = each ($sourcegroups) ){
				while (list ($itemDST, $b) = each ($DestGroups) ){
					$RULE="$TABLE $InInterface $itemSRC $itemDST $proto$portsGroups$GroupTime $MARKLOG";
					if($isFWLogBlocked==1){
						if($ligne["accepttype"]=="DROP"){ $SCRIPT_LOG[]="$iptables -I $RULE $J_LOGPRX || true"; }
					}
					
					$SCRIPT[]="$iptables $APPEND $RULE $J || true";
				}
			}
				
		continue;}
			
			
		if( (count($sourcegroups)==0 ) AND (count($DestGroups)>0) ){
			if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]:  MODE 1:: Source = 0 and Destss >0 portsGroups = $portsGroups\n";}
			while (list ($itemDST, $b) = each ($DestGroups) ){
				$RULE="$TABLE $InInterface $itemDST $proto$portsGroups$GroupTime $MARKLOG";
				if($isFWLogBlocked==1){
					if($ligne["accepttype"]=="DROP"){ $SCRIPT_LOG[]="$iptables -I $RULE $J_LOGPRX || true"; }
				}
				$SCRIPT[]="$iptables $APPEND $RULE $J || true";
			}
		continue;}
		
				
		if( (count($sourcegroups)>0 ) AND (count($DestGroups)==0) ){
			if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]:  MODE 2:: Source > 0 and Dests == 0 portsGroups = $portsGroups\n";}
			while (list ($itemSRC, $b) = each ($sourcegroups) ){
				$RULE="$TABLE $InInterface $itemSRC $proto$portsGroups$GroupTime $MARKLOG";
				if($isFWLogBlocked==1){
					if($ligne["accepttype"]=="DROP"){ $SCRIPT_LOG[]="$iptables -I $RULE $J_LOGPRX || true"; }
				}
				
				$SCRIPT[]="$iptables $APPEND $RULE $J || true";
			}
		continue;}			
		
		if( (count($sourcegroups)==0 ) AND (count($DestGroups)==0) ){
			if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]:  MODE 3:: Source == 0 and Dests == 0\n";}
			$RULE="$TABLE $InInterface $proto$portsGroups$GroupTime $MARKLOG";
			if($isFWLogBlocked==1){
				if($ligne["accepttype"]=="DROP"){ $SCRIPT_LOG[]="$iptables -I $RULE $J_LOGPRX || true"; }
			}
			$SCRIPT[]="$iptables $APPEND $RULE $J || true";
			continue;
		}
		
		if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: Unknown ???\n";}

	}
	
	$SCRIPT_TXT=null;
	if(count($SCRIPT_LOG)>0){
		$SCRIPT_TXT="\n#". count($SCRIPT_LOG)." Logging rules:\n".@implode("\n", $SCRIPT_LOG);
	}
	
	return @implode("\n", $SCRIPT).$SCRIPT_TXT;
}

function GroupInLine($ID=0){
	if($ID==0){return array();}
	$q=new mysql_squid_builder();
	$sql="SELECT GroupType FROM webfilters_sqgroups WHERE ID=$ID";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){ echo "[".__LINE__."]: $q->mysql_error\n";}
	$GroupType=$ligne["GroupType"];
	if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: src_items:$ID -> $GroupType Get items.\n";}
	$IpClass=new IP();
	$sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$ID AND enabled=1";	
	
	
}


function GroupInArray($ID=0){
	
	if($ID==0){return array();}
	$q=new mysql_squid_builder();
	$sql="SELECT GroupType FROM webfilters_sqgroups WHERE ID=$ID";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){ echo "[".__LINE__."]: $q->mysql_error\n";}
	$GroupType=$ligne["GroupType"];
	if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: src_items:$ID -> $GroupType Get items.\n";}
	
	
	if($GroupType=="teamviewer"){
		include_once(dirname(__FILE__)."/ressources/class.products-ip-ranges.inc");
		$products_ip_ranges=new products_ip_ranges();
		$array=$products_ip_ranges->teamviewer_networks();
		if($GLOBALS["VERBOSE"]){echo "teamviewer_networks ->".count($array)." items [".__LINE__."]\n";}
		while (list ($a, $b) = each ($array) ){
			if(preg_match("#([0-9]+)-([0-9]+)#", $b)){
				$f["-m iprange --dst-range $b"]=true;
				continue;
			}
			$f["--dst $b"]=true;
			
		}
		
		if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: teamviewer::$ID -> ".count($f)." item(s).\n";}
		return $f;
	}
	
	
	$IpClass=new IP();
	$sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$ID AND enabled=1";
	
	
	$f=array();
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){ echo "[".__LINE__."]: $q->mysql_error\n";}
	while ($ligne = mysql_fetch_assoc($results)) {
		$pattern=trim($ligne["pattern"]);
		if($pattern==null){continue;}
		if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: src_items:$ID -> $pattern item.\n";}
		
		if($GroupType=="arp"){
			if(!$IpClass->IsvalidMAC($pattern)){
				if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: src_items:$ID -> $pattern INVALID.\n";}
				continue;
			}
			if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: src_items:$ID -> ADD -m mac --mac-source $pattern.\n";}
			$f["-m mac --mac-source $pattern"]=true;
			continue;
			}
			
			
		
		
		if($GroupType=="src"){
			if(preg_match("#[0-9\.]+-[0-9\.]+#", $pattern)){
				$f["-m iprange --src-range $pattern"]=true;
				continue;
			}
			
			$f["--source $pattern"]=true;
			continue;
		}
		
		if($GroupType=="dst"){
			if(preg_match("#[0-9\.]+-[0-9\.]+", $pattern)){
				$f["-m iprange --dst-range $pattern"]=true;
				continue;
			}
				
			$f["--dst $pattern"]=true;
			continue;
		}

		if($GroupType=="port"){
			$f[$pattern]=true;
		}
		
	}
	
	if($GroupType=="port"){
		$T=array();
		while (list ($a, $b) = each ($f) ){ $T[]=$a; }
		if(count($T)==0){return null;}
		if(count($T)>0){return "--destination-port ".@implode("", $T);}
		return "--destination-ports ".@implode(",", $T);
	}
	
	
	if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: src_items:$ID -> ".count($f)." item(s).\n";}
	return $f;
	
	
	
}
function GroupTime($ligne){
	if($ligne["enablet"]==0){return null;}
	$f=array();
	$array_days=array(
			1=>"monday",
			2=>"tuesday",
			3=>"wednesday",
			4=>"thursday",
			5=>"friday",
			6=>"saturday",
			7=>"sunday",
	);

	$TTIME=unserialize($ligne["time_restriction"]);

	$DDS=array();

	while (list ($num, $maks) = each ($array_days)){
		if($TTIME["D{$num}"]==1){$DDS[]=$num;}

	}
	
	if(count($DDS)>0){
		$f[]="--weekdays ".@implode(",", $DDS);
	}

	if( (preg_match("#^[0-9]+:[0-9]+#", $TTIME["ftime"])) AND  (preg_match("#^[0-9]+:[0-9]+#", $TTIME["ttime"]))  ){
		$f[]="--timestart {$TTIME["ftime"]} --timestop {$TTIME["ttime"]}";
	}

	if(count($f)>0){
		return " -m time ".@implode(" ", $f)." ";
	}


}

