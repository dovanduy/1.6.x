#!/usr/bin/php -q
<?php
$GLOBALS["DEBUG"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;echo "VERBOSED !!! \n";}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if($GLOBALS["VERBOSE"]){$GLOBALS["DEBUG"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once("/usr/share/artica-postfix/ressources/class.mysql.squid.builder.php");
$GLOBALS["UFDBGCLIENT"]=ufdbguardConstruct();
$GLOBALS["SplashScreenURI"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/SplashScreenURI");
$GLOBALS["PdnsHotSpot"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/PdnsHotSpot");
$GLOBALS["PDSNInUfdb"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/PDSNInUfdb");
if(!is_numeric($GLOBALS["PDSNInUfdb"])){$GLOBALS["PDSNInUfdb"]=0;}
if(!is_numeric($GLOBALS["PdnsHotSpot"])){$GLOBALS["PdnsHotSpot"]=0;}
if($GLOBALS["SplashScreenURI"]==null){$GLOBALS["PdnsHotSpot"]=0;}

if($argv[1]=="--resolve"){
	HotSpotRequests($argv[2],$argv[3],0,0,0);
	die();
}


if($GLOBALS["PDSNInUfdb"]==1){
	$descriptorspec = array(0 => array("pipe", "r"),1 => array("pipe", "w"),2 => array("file", "/var/log/squid/pipe-error-output.txt", "a"));
	$process = proc_open ($GLOBALS["UFDBGCLIENT"], $descriptorspec, $pipes);
	if (!is_resource($process)) {WriteToSyslog("Running pipe /usr/bin/ufdbgclient  failed");die();}
	if(is_resource($process)){WriteToSyslog("PIPE:$process: Running pipe on /usr/bin/ufdbgclient success");}
}

if($GLOBALS["VERBOSE"]){
	$textTosend="http://{$argv[1]} {$argv[2]}/ - GET\n";
	echo "Sending $textTosend\n";
	$full=ufdbgclient($textTosend,$pipes);
	echo "Receive:\n$full\nClosing\n";
	closepipe($process,$pipes);
	die();
}

$q=new mysql_squid_builder();

while ( $szTmp = @fgets(STDIN) ) {
		$line = trim($szTmp);
		$ay=array();
		$szDom=null;
		$szIp=null;
		$ll=array();
		$re=array();
		if(strlen($line)<3){ print "FAIL\n";continue;}
		
		if($GLOBALS["DEBUG"]){WriteToSyslog("Split query ...".str_replace("\t", " ", $line));}
		$re = split("\t", $line);
		$TYPE	=	$re[0];
		$szDom 	= 	$re[1];
		$CLASS	=	$re[2];
		$QTYPE	=	$re[3];
		$ID		=	$re[4];
		$szIp 	= 	$re[5];
		if(!is_numeric($ID)){$ID=1;}
		while (list ($index, $prefix) = each ($re) ){
			$ll[]="$index: `$prefix`";
			
		}
		if($GLOBALS["DEBUG"]){
			WriteToSyslog("TYPE: $TYPE DOMAIN:$szDom CLASS:$CLASS QTYPE:$QTYPE ID:$ID Ip:$szIp");
		}
		
		if(trim($TYPE)=="HELO"){
			echo "OK\tArtica DNS Filter is alive\nEND\n";
			continue;
		}
			
		
		
		
		if(strpos(" $szDom", ".")>0){
			if(strlen($szDom)>4){
				if(strpos(" $szDom", "*")==0){
					if(!preg_match("#\.arpa$#", $szDom)){
						if($GLOBALS["DEBUG"]){WriteToSyslog("PDSNInUfdb:{$GLOBALS["PDSNInUfdb"]} PdnsHotSpot:{$GLOBALS["PdnsHotSpot"]} Check query $szDom for $szIp");}
						$sends=null;
						//**********************************************************************
						if($GLOBALS["PDSNInUfdb"]==1){						
							$sends=UfdbRequests($szDom,$szIp,$pipes,$CLASS,$ID);
							if(strlen($sends)>0){echo $sends;}
								
						}
						//**********************************************************************
						if(strlen($sends)==0){
							if($GLOBALS["PdnsHotSpot"]==1){
								$array["LOGIN"]=null;
								$array["IPADDR"]=$szIp;
								$array["MAC"]=null;
								$array["HOST"]=null;
								$uid=$q->Hotspot_SessionActive($array);
								if($uid==null){
									if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $GLOBALS["SplashScreenURI"])){
										$sends="DATA\t$szDom\t$CLASS\tA\t0\t$ID\t{$GLOBALS["SplashScreenURI"]}\n";
									}else{
										$sends="DATA\t$szDom\t$CLASS\tCNAME\t0\t$ID\t{$GLOBALS["SplashScreenURI"]}\n";
									}
								}								
								if(strlen($sends)>0){echo $sends;}
							}
						}
						//**********************************************************************						
						
					}
				}
			}
		}
		
		
		//echo "LOG\tValue: ".$szDom.", ".$szIp."\n";
		echo "END\n";
		if($GLOBALS["DEBUG"]){WriteToSyslog("finishing query...");}
	}

closepipe($process,$pipes);
die();

function HotSpotRequests($szDom,$szIp,$pipes,$CLASS,$ID){
	if($szDom==$GLOBALS["SplashScreenURI"]){return null;}
	if($szIp=="127.0.0.1"){return null;}
	$time=time();
	if($GLOBALS["DEBUG"]){WriteToSyslog("HotSpotRequests($szDom,$szIp...)");}
	$q=new mysql_squid_builder();
	$sql="SELECT md5,finaltime FROM hotspot_sessions WHERE ipaddr='$szIp' AND finaltime>$time";
	if($GLOBALS["DEBUG"]){WriteToSyslog("HotSpotRequests:: $sql");}
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){
		if(preg_match("#Unknown column.*?ipaddr#", $q->mysql_error)){
			$q->QUERY_SQL("ALTER TABLE `hotspot_sessions` ADD `ipaddr` VARCHAR( 128 ) ,ADD INDEX ( `ipaddr` )");
			WriteToSyslog("HotSpotRequests FATAL !! $q->mysql_error check DB");
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		}
	}
	
	
	if(!$q->ok){	
		WriteToSyslog("HotSpotRequests FATAL !! $q->mysql_error");
		return;
	}
	
	$finaltime=$ligne["finaltime"];
	$md5_session=$ligne["md5"];
	
	if($GLOBALS["DEBUG"]){WriteToSyslog("$finaltime/$md5_session");}

	
	if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $GLOBALS["SplashScreenURI"])){
		$sends="DATA\t$szDom\t$CLASS\tA\t0\t$ID\t{$GLOBALS["SplashScreenURI"]}\n";
	}else{
		$sends="DATA\t$szDom\t$CLASS\tCNAME\t0\t$ID\t{$GLOBALS["SplashScreenURI"]}\n";
	}	
	
	if($md5_session==null){
		WriteToSyslog("HotSpotRequests: no session set for $szIp return {$GLOBALS["SplashScreenURI"]}");
		return $sends;
	}

	$reste=PdnsPIpedistanceOfTimeInWords($finaltime,$time);
	if($GLOBALS["DEBUG"]){WriteToSyslog("HotSpotRequests session OK: $reste");}
	
	
	
}

function UfdbRequests($szDom,$szIp,$pipes,$CLASS,$ID){
	$sends=null;
	if($szIp=="127.0.0.1"){return null;}
	$full=trim(ufdbgclient("http://$szDom $szIp/ - GET\n",$pipes));
	if($GLOBALS["DEBUG"]){WriteToSyslog("ufdbgclient:".strlen($full)." bytes...");}	
	$t=time();
	if(strlen($full)>3){
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $GLOBALS["PDSNInUfdbWebsite"])){
			$sends="DATA\t$szDom\t$CLASS\tA\t10\t$ID\t{$GLOBALS["PDSNInUfdbWebsite"]}\n";
		}else{
			$sends="DATA\t$szDom\t$CLASS\tCNAME\t10\t$ID\t{$GLOBALS["PDSNInUfdbWebsite"]}\n";
		}		
		
	}
	return $sends;
}


function ufdbgclient($full,$pipes=null){
	//http://192.168.1.245/Inotify.php?_=1355916677559 192.168.1.158/- - GET myip=192.168.1.238 myport=3128
	$fullORG=$full;
	$KEY=md5($full);
	$full=trim($full);
	//if(IsInCache($KEY)){return $GLOBALS["CACHE"][$KEY]["URI"];}
	if($GLOBALS["DEBUG"]){WriteToSyslog("ufdbgclient:: PIPE(): Write \"$fullORG\"");}
	fwrite($pipes[0], $fullORG);
	$output=array();
	$get= fgets($pipes[1], 1024);
	if($GLOBALS["DEBUG"]){WriteToSyslog("ufdbgclient:: PIPE(): receive \"$get");}
	return $get;
	
	$cmd="echo \"$full\"|{$GLOBALS["UFDBGCLIENT"]} 2>&1";
	if($GLOBALS["DEBUG"]){WriteToSyslog("ufdbgclient::`$cmd`");}
	exec($cmd,$results);
	if($GLOBALS["DEBUG"]){WriteToSyslog("ufdbgclient:: {$results[0]}");}
	SetCache($KEY,$results[0]);
	return $results[0];

}
function PdnsPIpedistanceOfTimeInWords($fromTime, $toTime = 0, $showLessThanAMinute = true) {
	$distanceInSeconds = round(abs($toTime - $fromTime));
	$distanceInMinutes = round($distanceInSeconds / 60);

	if ( $distanceInMinutes <= 1 ) {
		if ( !$showLessThanAMinute ) {
			return ($distanceInMinutes == 0) ? 'less than a minute' : '1 minute';
		} else {
			if ( $distanceInSeconds < 5 ) {
				return 'less than 5 seconds ('.$distanceInSeconds.'s)';
			}
			if ( $distanceInSeconds < 10 ) {
				return 'less than 10 seconds ('.$distanceInSeconds.'s)';
			}
			if ( $distanceInSeconds < 20 ) {
				return 'less than 20 seconds ('.$distanceInSeconds.'s) ';
			}
			if ( $distanceInSeconds < 40 ) {
				return 'about half a minute ('.$distanceInSeconds.'s)';
			}
			if ( $distanceInSeconds < 60 ) {
				return 'less than a minute';
			}

			return '1 minute';
		}
	}
	if ( $distanceInMinutes < 45 ) {
		return $distanceInMinutes . ' minutes';
	}
	if ( $distanceInMinutes < 90 ) {
		return 'about 1 hour';
	}
	if ( $distanceInMinutes < 1440 ) {
		return 'about ' . round(floatval($distanceInMinutes) / 60.0) . ' hours';
	}
	if ( $distanceInMinutes < 2880 ) {
		return '1 day';
	}
	if ( $distanceInMinutes < 43200 ) {
		return 'about ' . round(floatval($distanceInMinutes) / 1440) . ' days';
	}
	if ( $distanceInMinutes < 86400 ) {
		return 'about 1 month';
	}
	if ( $distanceInMinutes < 525600 ) {
		return round(floatval($distanceInMinutes) / 43200) . ' months';
	}
	if ( $distanceInMinutes < 1051199 ) {
		return 'about 1 year';
	}

	return 'over ' . round(floatval($distanceInMinutes) / 525600) . ' years';
}

function SetCache($KEY,$UriDest){
	if(trim($KEY)==null){return;}
	$t=time();
	$GLOBALS["CACHE"][$KEY]["URI"]=$UriDest;
	$GLOBALS["CACHE"][$KEY]["time"]=time();
	if($GLOBALS["DEBUG"]){WriteToSyslog("SetCache() $KEY = $t");}

}

function closepipe($process,$pipes){
		if(is_resource($process)){
			WriteToSyslog("PIPE:$process: Close pipes...");
			@fclose($pipes[0]);
			@fclose($pipes[1]);
			@fclose($pipes[2]);
			WriteToSyslog("PIPE:$process: Close Process...$process");
			@proc_close($process);
		}else{
			WriteToSyslog("\$process is not a ressource...");
		}
	}	



function IsInCache($KEY){
	if(!isset($GLOBALS["StreamCacheTTLUri"])){$GLOBALS["StreamCacheTTLUri"]=30;}
	if(!is_numeric($GLOBALS["StreamCacheTTLUri"])){$GLOBALS["StreamCacheTTLUri"]=30;}
	if(isset($GLOBALS["CACHE"][$KEY]["URI"])){if(trim($GLOBALS["CACHE"][$KEY]["URI"])==null){unset($GLOBALS["CACHE"][$KEY]["URI"]);}}
	if(!isset($GLOBALS["CACHE"][$KEY]["time"])){if($GLOBALS["DEBUG"]){WriteToSyslog("IsInCache() $KEY = FALSE");}return false;}
	$data1 = $GLOBALS["CACHE"][$KEY]["time"];
	$data2 = time();
	$difference = ($data2 - $data1);
	if($GLOBALS["DEBUG"]){WriteToSyslog("IsInCache() $KEY = {$difference}s/{$GLOBALS["StreamCacheTTLUri"]}s");}
	if($difference>$GLOBALS["StreamCacheTTLUri"]){
		if($GLOBALS["DEBUG"]){WriteToSyslog("IsInCache() $KEY = FALSE");}
		unset($GLOBALS["CACHE"][$KEY]);return false;}
		if($GLOBALS["DEBUG"]){WriteToSyslog("IsInCache() $KEY = TRUE");}
		return true;


}

function ufdbguardConstruct(){
	$binary="/usr/bin/ufdbgclient";
	$moinsC=null;
	$moinsd=null;
	$log="-l /var/log/pdns";
	if($GLOBALS["VERBOSE"]){$moinsd=" -d ";}
	@mkdir("/var/log/pdns",0755,true);
	$GLOBALS["PDSNInUfdbWebsite"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/PDSNInUfdbWebsite");
	if($GLOBALS["PDSNInUfdbWebsite"]==null){$GLOBALS["PDSNInUfdbWebsite"]="www.google.com";}
	$datas=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/ufdbguardConfig")));
	if(!isset($datas["UseRemoteUfdbguardService"])){$datas["UseRemoteUfdbguardService"]=0;}
	if(!isset($datas["remote_port"])){$datas["remote_port"]=3977;}
	if(!isset($datas["remote_server"])){$datas["remote_server"]=null;}
	if(!isset($datas["listen_addr"])){$datas["listen_addr"]="127.0.0.1";}
	if(!isset($datas["listen_port"])){$datas["listen_port"]="3977";}
	if(!isset($datas["tcpsockets"])){$datas["tcpsockets"]=1;}
	$EnableRemoteStatisticsAppliance=@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	
	if($EnableRemoteStatisticsAppliance==1){
		$RemoteStatisticsApplianceSettings=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/RemoteStatisticsApplianceSettings")));
		$datas["remote_server"]=$RemoteStatisticsApplianceSettings["SERVER"];
		$datas["UseRemoteUfdbguardService"]=1;
		$datas["remote_port"]=$datas["listen_port"];
	}

	if($datas["UseRemoteUfdbguardService"]==1){
		if(trim($datas["remote_server"]==null)){$datas["remote_server"]="127.0.0.1";}
		$address="-S {$datas["remote_server"]} -p {$datas["remote_port"]} ";
		WriteToSyslog("Use remote ufdbguard service: {$datas["remote_server"]}:{$datas["remote_port"]}");
		return "$binary $moinsd$moinsC$address $log";
	}

	if($datas["remote_port"]==null){$datas["UseRemoteUfdbguardService"]=0;}
	if($datas["listen_addr"]==null){$datas["listen_addr"]="127.0.0.1";}
	if($datas["listen_addr"]=="all"){$datas["listen_addr"]="127.0.0.1";}
		
	$effective_port=ufdbguard_value("port");
	WriteToSyslog("ufdbguardd: Effective port:`$effective_port`");
	if(is_numeric($effective_port)){$datas["tcpsockets"]=1;}

	if($datas["tcpsockets"]==1){
		if(trim($datas["listen_addr"]==null)){$datas["listen_addr"]="127.0.0.1";}
		WriteToSyslog("ufdbguardd: Use remote ufdbguard service: {$datas["listen_addr"]}:{$datas["remote_port"]}");
		$address="-S {$datas["listen_addr"]} -p {$datas["listen_port"]} ";
		return "$binary $moinsd$moinsC$address $log";
	}
	
	
	WriteToSyslog("ufdbguardd: Use remote ufdbguard service: $binary $moinsC$log");
	return "$binary $moinsd$moinsC$log";	
	
	
}

 function ufdbguard_value($key){
	if(!is_file("/etc/squid3/ufdbGuard.conf")){return null;}
	if(isset($GLOBALS[__FUNCTION__][$key])){return $GLOBALS[__FUNCTION__][$key];}
	if(!isset($GLOBALS["UFDGUARDDATAFILE"])){$GLOBALS["UFDGUARDDATAFILE"]=file("/etc/squid3/ufdbGuard.conf");}
	if(!is_array($GLOBALS["UFDGUARDDATAFILE"])){$GLOBALS["UFDGUARDDATAFILE"]=file("/etc/squid3/ufdbGuard.conf");}
	while (list ($num, $ligne) = each ($GLOBALS["UFDGUARDDATAFILE"]) ){
		if(preg_match("#^$key\s+(.*)#", $ligne,$re)){
			$GLOBALS[__FUNCTION__][$key]=$re[1];
			return $re[1];}
	}

}


function WriteToSyslog($text){
	if($GLOBALS["VERBOSE"]){echo "$text\n";}
	if(!function_exists("syslog")){return;}
	$file=basename(__FILE__);
	$LOG_SEV=LOG_INFO;
	openlog($file, LOG_PID , LOG_SYSLOG);
	syslog($LOG_SEV, $text);
	closelog();
}