#!/usr/bin/php -q
<?php
@mkdir("/var/log/artica-postfix/squid-brut",0755,true);
$EnableRemoteSyslogStatsAppliance=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableRemoteSyslogStatsAppliance"));
$DisableArticaProxyStatistics=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/DisableArticaProxyStatistics"));
$EnableRemoteStatisticsAppliance=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableRemoteStatisticsAppliance"));
if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
if(!is_numeric($EnableRemoteSyslogStatsAppliance)){$EnableRemoteSyslogStatsAppliance=0;}

$pipe = fopen("php://stdin", "r");
while(!feof($pipe)){
	$buffer .= fgets($pipe, 4096);
	if($EnableRemoteSyslogStatsAppliance==1){continue;}
	if($DisableArticaProxyStatistics==1){continue;}
	if($EnableRemoteStatisticsAppliance==1){continue;}
	
	$buffer=trim($buffer);
	$F=substr($buffer, 0,1);
	if($F=="L"){
		$buffer=substr($buffer, 1,strlen($buffer));
		$keydate=date("lF");
		$prefix=date("M")." ".date("d")." ".date("H:i:s")." localhost (squid-1): ";
		$subdir=date("Y-m-d-h");
		@mkdir("/var/log/artica-postfix/squid-brut/$subdir");
		if(is_dir("/var/log/artica-postfix/squid-brut/$subdir")){
			$TargetFile="/var/log/artica-postfix/squid-brut/$subdir/".md5($buffer);
		}else{
			$TargetFile="/var/log/artica-postfix/squid-brut/".md5($buffer);
		}
		
		@file_put_contents($TargetFile, $prefix.$buffer);
		if(!is_file($TargetFile)){events(dirname($TargetFile)." permission denied");}
		$GLOBALS["MEMLOGS"][$keydate][]=$prefix.$buffer;
		if(count($GLOBALS["MEMLOGS"])>2){flushlogs();}
		if(count($GLOBALS["MEMLOGS"][$keydate])){flushlogs();}
		continue;
	}
	
	$buffer=null;
}
flushlogs();

function events($text){
	$pid=@getmypid();
	$date=@date("h:i:s");
	$logFile="/var/log/squid/logfile_daemon.debug";

	$size=@filesize($logFile);
	if($size>1000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$pid `$text`\n");
	@fclose($f);
}

function flushlogs(){
	if(!isset($GLOBALS["MEMLOGS"])){return;}
	if(!is_array($GLOBALS["MEMLOGS"])){return;}
	while (list ($keydate, $rows) = each ($GLOBALS["MEMLOGS"]) ){
		if(count($rows)==0){continue;}
		$logFile="/var/log/squid/squid-access-$keydate.log";
		$f = @fopen($logFile, 'a');
		@fwrite($f, @implode("\n", $rows)."\n");
		@fclose($f);
		unset($GLOBALS["MEMLOGS"][$keydate]);
	}
	
	unset($GLOBALS["MEMLOGS"]);
}

?>