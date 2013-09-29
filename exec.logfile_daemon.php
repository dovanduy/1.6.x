#!/usr/bin/php -q
<?php
$GLOBALS["ACT_AS_REVERSE"]=false;
$GLOBALS["NO_DISK"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["KAV4PROXY_NOSESSION"]=true;
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
@mkdir("/var/log/artica-postfix/squid-brut",0755,true);
@mkdir("/var/log/artica-postfix/squid-reverse",0755,true);
error_reporting(0);
$EnableRemoteSyslogStatsAppliance=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableRemoteSyslogStatsAppliance"));
$DisableArticaProxyStatistics=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/DisableArticaProxyStatistics"));
$EnableRemoteStatisticsAppliance=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableRemoteStatisticsAppliance"));
$SquidActHasReverse=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidActHasReverse"));
if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
if(!is_numeric($EnableRemoteSyslogStatsAppliance)){$EnableRemoteSyslogStatsAppliance=0;}
if(!is_numeric($SquidActHasReverse)){$SquidActHasReverse=0;}
if($argv[1]=="--no-disk"){$GLOBALS["NO_DISK"]=true;}
if($SquidActHasReverse==1){$GLOBALS["ACT_AS_REVERSE"]=true;}
$logthis=array();
$GLOBALS["Q"]=new mysql_squid_builder();
if($GLOBALS["VERBOSE"]){$logthis[]=" Verbosed...";}
if($GLOBALS["ACT_AS_REVERSE"]){$logthis[]=" Act as reverse...";}
events("Starting PID:".getmypid()." ".@implode(", ", $logthis) ." ({$argv[1]})");
$GLOBALS["USERSDB"]=unserialize(@file_get_contents("/etc/squid3/usersMacs.db"));
$DCOUNT=0;
$GLOBALS["logfileD"]=new logfile_daemon();
$pipe = fopen("php://stdin", "r");
while(!feof($pipe)){
	$buffer .= fgets($pipe, 4096);

	
	$buffer=trim($buffer);
	$F=substr($buffer, 0,1);
	if($F=="L"){
		$DCOUNT++;
		$buffer=substr($buffer, 1,strlen($buffer));
		$keydate=date("lF");
		$prefix=date("M")." ".date("d")." ".date("H:i:s")." localhost (squid-1): ";
		$subdir=date("Y-m-d-h");
		if(strpos($buffer, "TCP_DENIED:")>0){continue;}
		if(strpos($buffer, "RELEASE -1")>0){continue;}
		if(strpos($buffer, "RELEASE 00")>0){continue;}
		if(strpos($buffer, "SWAPOUT 00")>0){continue;}
		ParseSizeBuffer($buffer);
		
		if($EnableRemoteSyslogStatsAppliance==1){continue;}
		if($DisableArticaProxyStatistics==1){continue;}
		if($EnableRemoteStatisticsAppliance==1){continue;}
		
		
	}
	
	$buffer=null;
}

events("Stopping PID:".getmypid()." After $DCOUNT event(s)");


function ParseSizeBuffer($buffer){
	if(strpos("NONE error:", $buffer)>0){return; }
	if(!function_exists("mysql_connect")){return;}
	$PROTOS="PROPPATCH|MKCOL|MOVE|UNLOCK|DELETE|HTML|TEXT|PROPFIND|GET|POST|CONNECT|PUT|LOCK|NONE|HEAD|OPTIONS";
	if(preg_match("#GET cache_object#",$buffer)){return true;}
	if(strpos($buffer, "TCP_DENIED:")>0){return;}
	$hostname=null;
	if($GLOBALS["VERBOSE"]){events("\"$buffer\"");;}
	if(!preg_match('#MAC:(.+?)\s+(.+?)\s+.+?\s+(.*?)\s+\[(.+?)\]\s+"([a-z]+:|'.$PROTOS.')\s+(.+?)\s+.+?"\s+([0-9]+)\s+([0-9]+)\s+([A-Z_]+)#',$buffer,$re)){
		events("Not filtered \"$buffer\"");
		return;
	}
	$mac=trim(strtolower($re[1]));
	$ipaddr=trim($re[2]);
	$uid=$re[3];
	$zdate=$re[4];
	$xtime=strtotime($zdate);
	$proto=$re[5];
	$uri=$re[6];
	$code_error=$re[7];
	$SIZE=$re[8];
	$SquidCode=$re[9];
	$Forwarded=$re[10];
	if($Forwarded=="-"){$Forwarded=null;}
	if($Forwarded=="0.0.0.0"){$Forwarded=null;}
	if($Forwarded=="255.255.255.255"){$Forwarded=null;}
	if(strlen($Forwarded)>4){if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $Forwarded)){$ipaddr=$Forwarded;$mac=null;}}
	
	if($GLOBALS["VERBOSE"]){
		events($buffer);
		events("Size=$SIZE bytes");
		events("Uri=$uri");
		events("Squid code=$SquidCode");
	}
	
	if($uid=="-"){$uid=null;}
	$cached=0;
	
	if(($ipaddr=="127.0.0.1") OR ($ipaddr=="::")){if($uid==null){
		if($GLOBALS["VERBOSE"]){events("127.0.0.1 -> uid = null -> SKIP");}
		return;}}
	if($GLOBALS["logfileD"]->CACHEDORNOT($SquidCode)){$cached=1;}
	if($mac=="00:00:00:00:00:00"){$mac==null;}
	if($mac=="00:00:00:00:00:00"){$mac==null;}
				
			
				
	

	$URLAR=parse_url($uri);
	if(isset($URLAR["host"])){$sitename=$URLAR["host"];}
	if($sitename=="127.0.0.1"){return;}
	
	
	if(preg_match("#^www\.(.+)#", $sitename,$re)){$sitename=$re[1];}
	if(preg_match("#^(.+?):.*#", $sitename,$re)){$sitename=$re[1];}
	
	$familysite=$GLOBALS["Q"]->GetFamilySites($sitename);
	
	if($uid<>null){$key="uid";}
	if($key==null){if($mac<>null){$key="MAC";}}
	if($key==null){if($ipaddr<>null){$key="ipaddr";}}
	if($key==null){return;}
	
	$hour=date("H",$xtime);
	$date=date("Y-m-d H:i:s",$xtime);
	
	if($GLOBALS["VERBOSE"]){events("Date: $date: $familysite $uid/$ipaddr");}
	
	$keyr=md5("$hour$uid$ipaddr$mac$sitename");
	$uri=trim($uri);
	if($uri==null){return;}
	
	if($uid==null){$uid=$GLOBALS["Q"]->MacToUid($mac);}
	if($uid==null){$uid=$GLOBALS["Q"]->IpToUid($ipaddr);}	
	if($hostname==null){$hostname=$GLOBALS["Q"]->MacToHost($mac);}
	if($hostname==null){$hostname=$GLOBALS["Q"]->IpToHost($ipaddr);}
	
	

	
	if(!isset($GLOBALS["TABLE_CHECKED"][date("YmdH",$xtime)])){
		if($GLOBALS["Q"]->TablePrimaireHour(date("YmdH",$xtime))){
			$GLOBALS["TABLE_CHECKED"][date("YmdH",$xtime)]=true;
		}else{
			events($GLOBALS["Q"]->mysql_error);
		}
		
		if($GLOBALS["Q"]->check_youtube_hour(date("YmdH",$xtime))){
		}else{
			events("Youtube:".$GLOBALS["Q"]->mysql_error);
		}
		
		if($GLOBALS["Q"]->check_SearchWords_hour(date("YmdH",$xtime))){
		}else{
			events("SearchWords:".$GLOBALS["Q"]->mysql_error);
		}

		if($GLOBALS["Q"]->check_quota_hour(date("YmdH",$xtime))){
			$GLOBALS["TABLE_CHECKED"][date("YmdH",$xtime)]=true;
		}else{
			events($GLOBALS["Q"]->mysql_error);
		}		
		
		$GLOBALS["USERSDB"]=unserialize(@file_get_contents("/etc/squid3/usersMacs.db"));
			
	}
	
	

	
	
	$zMD5=md5("$buffer");
	
	$TablePrimaireHour="squidhour_".date("YmdH",$xtime);
	$tableYoutube="youtubehours_".date("YmdH",$xtime);
	$tableSearchWords="searchwords_".date("YmdH",$xtime);
	$sitename=mysql_escape_string2($sitename);
	if($familysite=="localhost"){return;}
	$uri=mysql_escape_string2($uri);
	$uriT=mysql_escape_string2($uri);
	$hostname=mysql_escape_string2($hostname);
	$TYPE=$GLOBALS["logfileD"]->codeToString($code_error);
	$REASON=$TYPE;
	if($mac=="00:00:00:00:00:00"){$mac==null;}
	if($mac=="-"){$mac==null;}
	
	
	$sql="INSERT DELAYED INTO `$TablePrimaireHour` 
	(`sitename`,`uri`,`TYPE`,`REASON`,`CLIENT`,`hostname`,`zDate`,`zMD5`,`uid`,`QuerySize`,`cached`,`MAC`) 
	VALUES('$sitename','$uriT','$TYPE','$REASON','$ipaddr','$hostname','$date','$zMD5','$uid','$SIZE','$cached','$mac')";
	$GLOBALS["Q"]->QUERY_SQL($sql);
	
	if(!$GLOBALS["Q"]->ok){
		if(strpos($GLOBALS["Q"]->mysql_error, "doesn't exist")>0){
			events(" - - > TablePrimaireHour()");
			$GLOBALS["Q"]->TablePrimaireHour(date("YmdH",$xtime));
		}
		$GLOBALS["Q"]->QUERY_SQL($sql);
	}
	
	if(!$GLOBALS["Q"]->ok){events($GLOBALS["Q"]->mysql_error);}
	
	
	if(strpos(" $uri", "youtube")>0){
		$VIDEOID=$GLOBALS["logfileD"]->GetYoutubeID($uri);
		if($VIDEOID<>null){
			events("YOUTUBE:: $date: $ipaddr $uid $mac [$VIDEOID]");
			$sql="INSERT DELAYED INTO `$tableYoutube`
			(`zDate`,`ipaddr`,`hostname`,`uid`,`MAC` ,`account`,`youtubeid`)
			VALUES ('$date','$ipaddr','','$uid','$mac','0','$VIDEOID')";
			$GLOBALS["Q"]->QUERY_SQL($sql);
			if(!$GLOBALS["Q"]->ok){events($GLOBALS["Q"]->mysql_error);}

		}
	}	
	
	$SearchWords=$GLOBALS["logfileD"]->SearchWords($uri);
	if(is_array($SearchWords)){
		$words=mysql_escape_string2($SearchWords["WORDS"]);
		$sql="INSERT DELAYED INTO `$tableSearchWords` 
		(`zmd5`,`sitename`,`zDate`,`ipaddr`,`hostname`,`uid`,`MAC`,`account`,`familysite`,`words`)
		VALUES ('$zMD5','$sitename','$date','$ipaddr','','$uid','$mac','0','$familysite','$words')";
		$GLOBALS["Q"]->QUERY_SQL($sql);
		if(!$GLOBALS["Q"]->ok){events($GLOBALS["Q"]->mysql_error);}

	}

	
	$table="quotahours_".date('YmdH',$xtime);
	$sql="SELECT `size`,`keyr` FROM `$table` WHERE `keyr`='$keyr'";
	
	
	
	$ligne=mysql_fetch_array($GLOBALS["Q"]->QUERY_SQL($sql));
	$ligne["size"]=intval($ligne["size"]);
	if(!is_numeric($ligne["size"])){$ligne["size"]=0;}
	
	

	
	if(trim($ligne["keyr"])<>null){
		$newsize=$ligne["size"]+$SIZE;
		$sql="UPDATE LOW_PRIORITY `$table` SET `size`='$newsize' WHERE `keyr`='$keyr'";
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG($sql);}
		$GLOBALS["Q"]->QUERY_SQL($sql);
		
		if($GLOBALS["VERBOSE"]){
			$UNIT="KB";
			$newsizeL=round($newsize/1024,2);
			if($newsizeL>1024){$newsizeL=round($newsizeL/1024,2);$UNIT="MB";}
			events("\"$sitename\":{$ligne["size"]} $uid/$ipaddr UPDATE = {$ligne["size"]} + [$SIZE] -> UPDATE = $newsize ({$newsizeL}$UNIT)");
		}
		
		if(!$GLOBALS["Q"]->ok){events($GLOBALS["Q"]->mysql_error);}
		return;
	}
	
	if(trim($familysite)==null){
		events("\"$sitename\": familysite=null, strange pattern: \"$buffer\"");
		return;
	}
	
	if($SIZE>0){
		$sql="INSERT DELAYED INTO `$table` (`hour`,`keyr`,`ipaddr`,`familysite`,`servername`,`uid`,`MAC`,`size`) VALUES
		('$hour','$keyr','$ipaddr','$familysite','$sitename','$uid','$mac','$SIZE')";
		//events($sql);
		$GLOBALS["Q"]->QUERY_SQL($sql);
		
		if($GLOBALS["VERBOSE"]){
			$UNIT="KB";
			$newsizeL=round($SIZE/1024,2);
			if($newsizeL>1024){$newsizeL=round($newsizeL/1024,2);$UNIT="MB";}
			events("\"$sitename\":$newsizeL $uid/$ipaddr ADD = [$SIZE] -> ADD NEW = ({$newsizeL}$UNIT)");
		}
		
		if(!$GLOBALS["Q"]->ok){events($GLOBALS["Q"]->mysql_error);}	
	}
	//events("$uid [$ipaddr] $servername $SIZE bytes");
	
}


function GetTargetFile($subdir,$md5){

	@mkdir("/var/log/artica-postfix/squid-brut/$subdir");
	if(is_dir("/var/log/artica-postfix/squid-brut/$subdir")){
		return "/var/log/artica-postfix/squid-brut/$subdir/$md5";
	}
	
	return "/var/log/artica-postfix/squid-brut/$md5";
		
	
	
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
	if($GLOBALS["NO_DISK"]){unset($GLOBALS["MEMLOGS"]);return;}
	if(!isset($GLOBALS["MEMLOGS"])){return;}
	if(!is_array($GLOBALS["MEMLOGS"])){return;}
	$middlename="access";
	if($GLOBALS["ACT_AS_REVERSE"]){$middlename="reverse";}
	
	while (list ($keydate, $rows) = each ($GLOBALS["MEMLOGS"]) ){
		if(count($rows)==0){continue;}
		$logFile="/var/log/squid/squid-$middlename-$keydate.log";
		$f = @fopen($logFile, 'a');
		@fwrite($f, @implode("\n", $rows)."\n");
		@fclose($f);
		unset($GLOBALS["MEMLOGS"][$keydate]);
	}
	
	unset($GLOBALS["MEMLOGS"]);
}

?>