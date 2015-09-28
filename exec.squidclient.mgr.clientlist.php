<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}

if(is_file("/usr/bin/cgclassify")){if(is_dir("/cgroups/blkio/php")){shell_exec("/usr/bin/cgclassify -g cpu,cpuset,blkio:php ".getmypid());}}

include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
include_once(dirname(__FILE__)."/ressources/class.resolv.conf.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.booster.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.watchdog.inc");
include_once(dirname(__FILE__)."/ressources/class.influx.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");

if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;
$GLOBALS["debug"]=true;
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
}

$unix=new unix();
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".MAIN.time";
	$time=$unix->file_time_min($pidtime);
	if(!$GLOBALS["FORCE"]){
		if($GLOBALS["VERBOSE"]){echo "Current {$time}Mn, need 5mn\n";}
		if($time<5){return;}
	}
	
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	
	
	$MAIN=array();
	$data=$unix->squidclient("client_list");
	if($data==null){return;}
	$f=explode("\n",$data);
	
	
	while (list ($index, $line) = each ($f)){
		$line=trim($line);
		if($line==null){continue;}
		
		if(preg_match("#ICP  Requests#", $line,$re)){
			
			continue;
			
		}
		
		if(preg_match("#Address:\s+([0-9\.]+)#", $line,$re)){
			$IPADDR=$re[1];
			continue;
			
		}
		
		if(preg_match("#Currently established connections:\s+([0-9]+)#",$line,$re)){
			$MAIN[$IPADDR]["CUR_CNX"]=$re[1];
			continue;
		}
		
		if(preg_match("#HTTP Requests\s+([0-9]+)#",$line,$re)){
			$MAIN[$IPADDR]["RQS"]=$re[1];
			continue;
		}
		
		
		
		if(preg_match("#TAG_NONE\s+([0-9]+)#",$line,$re)){
			$MAIN[$IPADDR]["TAG_NONE"]=$re[1];
			continue;
		}
		if(preg_match("#TCP_REDIRECT\s+([0-9]+)#",$line,$re)){
			$MAIN[$IPADDR]["TCP_REDIRECT"]=$re[1];
			continue;
		}
		
		
		
		if(preg_match("#TCP_HIT\s+([0-9]+)#",$line,$re)){
			$MAIN[$IPADDR]["TCP_HIT"]=$re[1];
			continue;
		}		
		if(preg_match("#TCP_MISS\s+([0-9]+)#",$line,$re)){
			$MAIN[$IPADDR]["TCP_MISS"]=$re[1];
			continue;
		}		
		if(preg_match("#TCP_REFRESH_UNMODIFI\s+([0-9]+)#",$line,$re)){
			if(!isset($MAIN[$IPADDR]["TCP_HIT"])){$MAIN[$IPADDR]["TCP_HIT"]=0;}
			$MAIN[$IPADDR]["TCP_HIT"]=$MAIN[$IPADDR]["TCP_HIT"]+intval($re[1]);
			continue;
		}	
		if(preg_match("#TCP_REFRESH_MODIFIED\s+([0-9]+)#",$line,$re)){
			if(!isset($MAIN[$IPADDR]["TCP_HIT"])){$MAIN[$IPADDR]["TCP_HIT"]=0;}
			$MAIN[$IPADDR]["TCP_HIT"]=$MAIN[$IPADDR]["TCP_HIT"]+intval($re[1]);
			continue;
		}
		if(preg_match("#TCP_SWAPFAIL_MISS\s+([0-9]+)#",$line,$re)){
			if(!isset($MAIN[$IPADDR]["TAG_NONE"])){$MAIN[$IPADDR]["TAG_NONE"]=0;}
			$MAIN[$IPADDR]["TAG_NONE"]=$MAIN[$IPADDR]["TAG_NONE"]+intval($re[1]);
			continue;
		}		
		
		
		
		if(preg_match("#TCP_TUNNEL\s+([0-9]+)#",$line,$re)){
			$MAIN[$IPADDR]["TCP_TUNNEL"]=$re[1];
			continue;
		}	

		if(preg_match("#Name:\s+(.+)#",$line,$re)){
			echo "Uid {$re[1]}\n";
			$MAIN[$IPADDR]["uid"]=trim($re[1]);
			continue;
		}	
		
		
		
	}
	
	if(count($MAIN)==0){return;}
	
	$q=new mysql_squid_builder();
	
	$sql="CREATE TABLE IF NOT EXISTS `mgr_client_list` (
		`zmd5` VARCHAR(90) NOT NULL PRIMARY KEY,
		`ipaddr` VARCHAR(90),
		`uid` VARCHAR(90),
		`CUR_CNX` BIGINT UNSIGNED,
		`RQS` BIGINT UNSIGNED,
		`TAG_NONE` BIGINT UNSIGNED,
		`TCP_HIT` BIGINT UNSIGNED,
		`TCP_MISS` BIGINT UNSIGNED,
		`TCP_REDIRECT`BIGINT UNSIGNED,
		`TCP_TUNNEL` BIGINT UNSIGNED,
		KEY `ipaddr` (`ipaddr`),
		KEY `uid` (`uid`),
		KEY `RQS` (`RQS`),
		KEY `TAG_NONE` (`TAG_NONE`),
		KEY `TCP_HIT` (`TCP_HIT`),
		KEY `TCP_REDIRECT` (`TCP_REDIRECT`),
		KEY `TCP_MISS` (`TCP_MISS`),
		KEY `CUR_CNX` (`CUR_CNX`)
		) ENGINE=MYISAM;";
	
	
	$q->QUERY_SQL($sql);
	$q->QUERY_SQL("TRUNCATE TABLE `mgr_client_list`");
	$prefix="INSERT IGNORE INTO `mgr_client_list` (`zmd5`,`ipaddr`,CUR_CNX,RQS,TAG_NONE,TCP_HIT,TCP_MISS,TCP_REDIRECT,TCP_TUNNEL,uid) VALUES ";
	
	while (list ($ipaddr, $array) = each ($MAIN)){
		$uid=$array["uid"];
		$md5=md5($ipaddr.$array["uid"]);
		$CUR_CNX=intval($array["CUR_CNX"]);
		$RQS=intval($array["RQS"]);
		$TAG_NONE=intval($array["TAG_NONE"]);
		$TCP_HIT=intval($array["TCP_HIT"]);
		$TCP_MISS=intval($array["TCP_MISS"]);
		$TCP_REDIRECT=intval($array["TCP_REDIRECT"]);
		$TCP_TUNNEL=intval($array["TCP_TUNNEL"]);
		$uid=mysql_escape_string2($uid);
		$line="('$md5','$ipaddr','$CUR_CNX','$RQS','$TAG_NONE','$TCP_HIT','$TCP_MISS','$TCP_REDIRECT','$TCP_TUNNEL','$uid')";
		echo $line."\n";
		$T[]=$line;
		
		
		
	}
	
	$q->QUERY_SQL($prefix.@implode(",", $T));
	
	
?>	