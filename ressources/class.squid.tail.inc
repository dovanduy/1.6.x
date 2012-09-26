<?php


class squid_tail{
	function squid_tail(){
		if(!isset($GLOBALS["USERSDB"])){$this->LOADUSERDB();}else{$ttltime=filemtime($path);if($GLOBALS["USERSDBT"]<>$ttltime){$this->LOADUSERDB();}}
		
		
	}
	
	
	private function LOADUSERDB(){
		$GLOBALS["USERSDBT"]=filemtime($path);
		$usersMacsDB=@file_get_contents("/etc/squid3/usersMacs.db");
		$GLOBALS["USERSDB"]=unserialize($usersMacsDB);
	}
	
	
	public function parse_tail($buffer){
			$buffer=trim($buffer);
			
			
			if(preg_match("#GET cache_object#",$buffer)){return true;}
			
			if(preg_match("#^.+?squid\[[0-9]+\]:#", $buffer,$re)){
				$buffer=str_replace($re[0], "",$buffer);
			}
			
			
			if(preg_match('#MAC:(.+?)\s+(.+?)\s+.+?\s+(.*?)\s+\[.+?:(.+?)\s+.+?\]\s+"(GET|POST|CONNECT|NONE)\s+(.+?)\s+.+?"\s+([0-9]+)\s+([0-9]+)\s+([A-Z_]+)#',$buffer,$re)){
				    $cached=0;
				    $mac=trim($re[1]);
					$ip=$re[2];
					$user=$re[3];
					$time=date('H:i:s');
					$uri=$re[6];
					$code_error=$re[7];
					$size=$re[8];
					$SquidCode=$re[9];
					if($ip=="127.0.0.1"){return;}
					if($this->CACHEDORNOT($SquidCode)){$cached=1;}
					if($mac=="00:00:00:00:00:00"){$mac==null;}
					$this->Builsql($ip,$user,$uri,$code_error,$size,$time,$cached,$mac);
					if(preg_match('#UserAgent:"(.+?)"#', $buffer,$re)){$UserAgent=$re[1];}
					if(preg_match('#Forwarded:"(.*?)"#', $buffer,$re)){$Forwarded=trim($re[1]);}
					if($Forwarded=="-"){$Forwarded=null;}	
					if(strlen($Forwarded)>4){$ip=$Forwarded;}									
					$this->InjectUserAuthDb($user,$ip,$mac,$UserAgent);
					
					return true;
						
			}	
			
			
			if(preg_match('#(.+?)\s+.+?\s+(.*?)\s+\[.+?:(.+?)\s+.+?\]\s+"(GET|POST|CONNECT|NONE)\s+(.+?)\s+.+?"\s+([0-9]+)\s+([0-9]+)\s+([A-Z_]+)#',$buffer,$re)){
				    $cached=0;
					$ip=$re[1];
					$user=$re[2];
					$time=date('H:i:s');
					$uri=$re[5];
					$code_error=$re[6];
					$size=$re[7];
					$SquidCode=$re[8];
					if($ip=="127.0.0.1"){return;}
					if($this->CACHEDORNOT($SquidCode)){$cached=1;}
					$this->Builsql($ip,$user,$uri,$code_error,$size,$time,$cached,null);
					if(preg_match('#UserAgent:"(.+?)"#', $buffer,$re)){$UserAgent=$re[1];}
					if(preg_match('#Forwarded:"(.*?)"#', $buffer,$re)){$Forwarded=trim($re[1]);}
					if($Forwarded=="-"){$Forwarded=null;}
					if(strlen($Forwarded)>4){$ip=$Forwarded;}	
					$this->InjectUserAuthDb($user,$ip,$mac,$UserAgent);
					return true;
						
			}	
			
			
			$this->events("[SQUID]: Not filtered: $buffer");
	
	}

private function CACHEDORNOT($SquidCode){
	
                switch ($SquidCode) {

                               case "TCP_HIT":

                               case "TCP_REFRESH_UNMODIFIED":

                               case "TCP_REFRESH_HIT":

                               case "TCP_REFRESH_FAIL_HIT":

                               case "TCP_REFRESH_MISS":

                               case "TCP_IMS_HIT":

                               case "TCP_MEM_HIT":

                               case "TCP_DENIED":
                               	
                               case "TCP_IMS_MISS":

                               case "TCP_OFFLINE_HIT":

                               case "TCP_STALE_HIT":

                               case "TCP_ASYNC_HIT":

                               case "UDP_HIT":

                               case "UDP_DENIED":

                               case "UDP_INVALID":

                                               return TRUE;

                                               break;

                               default:

                                               return FALSE;

                                               break;

                }

}	
	private function InjectUserAgent($userAgent){
		$md5=md5($userAgent);
		if(is_file("/var/log/artica-postfix/squid-userAgent/$md5.sql")){return;}
		@file_put_contents("/var/log/artica-postfix/squid-userAgent/$md5.sql", $userAgent);
	}

	
	private function InjectUserAuthDb($user,$ip,$mac,$userAgent){
		if($GLOBALS["DisableArticaProxyStatistics"]==1){return;}
		if($ip=="127.0.0.1"){return;}
		if($mac==null){$mac=$this->GetMacFromIP($ip);}
		$md5=md5("$user$ip$mac$userAgent");
		if(is_file("/var/log/artica-postfix/squid-users/$md5.sql")){return;}
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+#", $ip)){$computername=$this->GetComputerName($ip);}else{$computername=$ip;}
		$array=array(
			"USER"=>$user,
			"IP"=>$ip,
			"MAC"=>$mac,
			"USERAGENT"=>$userAgent,
			"HOSTNAME"=>$computername,
			"MD5"=>$md5
		
		);
		
		@file_put_contents("/var/log/artica-postfix/squid-users/$md5.sql", serialize($array));
		
		
	}
	
	


	private function Builsql($CLIENT,$username=null,$uri,$code_error,$size=0,$time,$cached,$mac=null){
			if($GLOBALS["DisableArticaProxyStatistics"]==1){return;}
			$squid_error["100"]="Continue";
			$squid_error["101"]="Switching Protocols";
			$squid_error["102"]="Processing";
			$squid_error["200"]="Pass";
			$squid_error["201"]="Created";
			$squid_error["202"]="Accepted";
			$squid_error["203"]="Non-Authoritative Information";
			$squid_error["204"]="No Content";
			$squid_error["205"]="Reset Content";
			$squid_error["206"]="Partial Content";
			$squid_error["207"]="Multi Status";
			$squid_error["300"]="Multiple Choices";
			$squid_error["301"]="Moved Permanently";
			$squid_error["302"]="Moved Temporarily";
			$squid_error["303"]="See Other";
			$squid_error["304"]="Not Modified";
			$squid_error["305"]="Use Proxy";
			$squid_error["307"]="Temporary Redirect";
			$squid_error["400"]="Bad Request";
			$squid_error["401"]="Unauthorized";
			$squid_error["402"]="Payment Required";
			$squid_error["403"]="Forbidden";
			$squid_error["404"]="Not Found";
			$squid_error["405"]="Method Not Allowed";
			$squid_error["406"]="Not Acceptable";
			$squid_error["407"]="Proxy Authentication Required";
			$squid_error["408"]="Request Timeout";
			$squid_error["409"]="Conflict";
			$squid_error["410"]="Gone";
			$squid_error["411"]="Length Required";
			$squid_error["412"]="Precondition Failed";
			$squid_error["413"]="Request Entity Too Large";
			$squid_error["414"]="Request URI Too Large";
			$squid_error["415"]="Unsupported Media Type";
			$squid_error["416"]="Request Range Not Satisfiable";
			$squid_error["417"]="Expectation Failed";
			$squid_error["424"]="Locked";
			$squid_error["424"]="Failed Dependency";
			$squid_error["433"]="Unprocessable Entity";
			$squid_error["500"]="Internal Server Error";
			$squid_error["501"]="Not Implemented";
			$squid_error["502"]="Bad Gateway";
			$squid_error["503"]="Service Unavailable";
			$squid_error["504"]="Gateway Timeout";
			$squid_error["505"]="HTTP Version Not Supported";
			$squid_error["507"]="Insufficient Storage";
			$squid_error["600"]="Squid header parsing error";	
	
	
	
	$URLAR=parse_url($uri);
	if(isset($URLAR["host"])){$sitename=$URLAR["host"];}
	
	if($sitename==null){
		if(preg_match("#^(?:[^/]+://)?([^/:]+)#",$uri,$re)){$sitename=$re[1];}
	}
	if($sitename==null){
		$this->events("[SQUID]: Unable to extract domain name from $uri");
		return;
	}	
	
	if(preg_match("#^(.+?):[0-9]+#", $sitename,$ri)){$sitename=$ri[1];}
	if(preg_match("#^www\.(.+)#",$sitename,$ri)){$sitename=$ri[1];}


	
	$TYPE=$squid_error[$code_error];
	$REASON=$TYPE;
	$CLIENT=trim($CLIENT);
	$date=date('Y-m-d')." ". $time;
	if($username==null){$username=$this->GetComputerName($CLIENT);}
	if($size==null){$size=0;}
	
	
	
	if(trim($GLOBALS["IPs"][$sitename])==null){
		$site_IP=trim(gethostbyname($sitename));
		$GLOBALS["IPs"][$sitename]=$site_IP;
	}else{
		$site_IP=$GLOBALS["IPs"][$sitename];
	}
	
	if(count($_GET["IPs"])>5000){unset($_GET["IPs"]);}
	if(count($_GET["COUNTRIES"])>5000){unset($_GET["COUNTRIES"]);}
	
	
	if(trim($GLOBALS["COUNTRIES"][$site_IP])==null){
		if(function_exists("geoip_record_by_name")){
			if($site_IP==null){$site_IP=$sitename;}
			$record = @geoip_record_by_name($site_IP);
			if ($record) {
				$Country=$record["country_name"];
				$GLOBALS["COUNTRIES"][$site_IP]=$Country;
			}
		}else{
			$geoerror="geoip_record_by_name no such function...";
		}
	}else{
		$Country=$GLOBALS["COUNTRIES"][$site_IP];
	}
	
	
	
	
	$zMD5=md5("$uri$date$CLIENT$username$TYPE$Country$site_IP");
	$hostname=null;
	if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $CLIENT)){
		if(!isset($GLOBALS["RESOLVER"][$CLIENT])){
			$hostname=gethostbyaddr($CLIENT);
			if($hostname<>$CLIENT){$GLOBALS["RESOLVER"][$CLIENT]=$hostname;}
		}else{
			$hostname=$GLOBALS["RESOLVER"][$CLIENT];
		}
	}
	
	
	if(!is_dir("/var/log/artica-postfix/pagepeeker")){@mkdir("/var/log/artica-postfix/pagepeeker",600,true);}
	if(!is_dir("/var/log/artica-postfix/dansguardian-stats2")){@mkdir("/var/log/artica-postfix/dansguardian-stats2",600,true);}
	
	$this->events("$CLIENT: Mac:`$mac` (".__LINE__.")");
	if(trim($mac)==null){
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+#", $CLIENT)){
			$mac=$this->GetMacFromIP($CLIENT);
		}
	}
	
	if(trim($mac)<>null){
		$this->events("$CLIENT: `$mac` user:`$username`->{$GLOBALS["USERSDB"]["MACS"][$mac]["UID"]} (".__LINE__.")");
		if((trim($username)==null) OR (trim($username)=="-")){
			$username=trim($GLOBALS["USERSDB"]["MACS"][$mac]["UID"]);
		}
	}
	
	if(preg_match("#youtube.com\/watch\?v=(.+?)\&#", $uri,$re)){
			$youtube=array(
				"VIDEOID"=>$re[1],
				"clientip"=>$CLIENT,
				"username"=>$username,
				"time"=>$date,
				"mac"=>$mac,
				"hostname"=>$hostname
				);
		$serialized=serialize($youtube);
		@file_put_contents("/var/log/artica-postfix/youtube/".md5($serialized).".ytube", $serialized);
		$serialized=null;
		$youtube=array();
		//https://gdata.youtube.com/feeds/api/videos/EPldrIzZMkg?v=2&alt=jsonc
		}	

	if(count($GLOBALS["SINGLE_SITE"])>1500){unset($GLOBALS["SINGLE_SITE"]);}
	$uri=addslashes($uri);
	$Country=addslashes($Country);
	$username=addslashes($username);
	


	if(!is_file("/var/log/artica-postfix/pagepeeker/".md5($sitename))){
		@file_put_contents("/var/log/artica-postfix/pagepeeker/".md5($sitename), $sitename);
	}	
	
	if($mac=="00:00:00:00:00:00"){$mac=null;}
	if(strpos($mac, ":")==0){$mac=null;}
	$sql="('$sitename','$uri','$TYPE','$REASON','$CLIENT','$date','$zMD5','$site_IP','$Country','$size','$username','$cached','$mac','$hostname')";
	$this->events("[SQUID]: dansguardian-stats2: ".$sql."  (".__LINE__.")" );
	@file_put_contents("/var/log/artica-postfix/dansguardian-stats2/$zMD5.sql",$sql);	
	if(count($GLOBALS["RTIME"])>500){unset($GLOBALS["RTIME"]);}
	$GLOBALS["RTIME"][]=array($sitename,$uri,$TYPE,$REASON,$CLIENT,$date,$zMD5,$site_IP,$Country,$size,$username,$mac);
	@file_put_contents("/etc/artica-postfix/squid-realtime.cache",base64_encode(serialize($GLOBALS["RTIME"])));
	
	
  
}

	private function GetMacFromIP($ipaddr){
		$ipaddr=trim($ipaddr);
		$ttl=date('YmdH');
		if(count($GLOBALS["CACHEARP"])>3){unset($GLOBALS["CACHEARP"]);}
		if(isset($GLOBALS["CACHEARP"][$ttl][$ipaddr])){return $GLOBALS["CACHEARP"][$ttl][$ipaddr];}
		
		if(!isset($GLOBALS["SBIN_ARP"])){$unix=new unix();$GLOBALS["SBIN_ARP"]=$unix->find_program("arp");}
		if(strlen($GLOBALS["SBIN_ARP"])<4){return;}
		
		if(!isset($GLOBALS["SBIN_PING"])){$unix=new unix();$GLOBALS["SBIN_PING"]=$unix->find_program("ping");}
		if(!isset($GLOBALS["SBIN_NOHUP"])){$unix=new unix();$GLOBALS["SBIN_NOHUP"]=$unix->find_program("nohup");}
		
		$cmd="{$GLOBALS["SBIN_ARP"]} -n \"$ipaddr\" 2>&1";
		$this->events($cmd);
		exec("{$GLOBALS["SBIN_ARP"]} -n \"$ipaddr\" 2>&1",$results);
		while (list ($num, $line) = each ($results)){
			if(preg_match("#^[0-9\.]+\s+.+?\s+([0-9a-z\:]+)#", $line,$re)){
				if($re[1]=="no"){continue;}
				$GLOBALS["CACHEARP"][$ttl][$ipaddr]=$re[1];
				return $GLOBALS["CACHEARP"][$ttl][$ipaddr];
			}
			
		}
		$this->events("$ipaddr not found (".__LINE__.")");
		if(!isset($GLOBALS["PINGEDHOSTS"][$ipaddr])){
			shell_exec("{$GLOBALS["SBIN_NOHUP"]} {$GLOBALS["SBIN_PING"]} $ipaddr -c 3 >/dev/null 2>&1 &");
			$GLOBALS["PINGEDHOSTS"][$ipaddr]=true;
		}
			
		
	}



	private function events($text){
			$pid=@getmypid();
			$date=@date("h:i:s");
			$logFile="/var/log/artica-postfix/auth-tail.debug";
			$size=@filesize($logFile);
			if($size>1000000){@unlink($logFile);}
			$f = @fopen($logFile, 'a');
			@fwrite($f, "$pid ".basename(__FILE__)." $text\n");
			@fclose($f);	
			}
			
	private function GetComputerName($ip){
		if($GLOBALS["resvip"][$ip]<>null){return $GLOBALS["resvip"][$ip];}
		$name=gethostbyaddr($ip);
		$GLOBALS["resvip"]=$name;
		return $name;
		}
	
	private function GeoIPavailable(){
		if(!function_exists("geoip_db_filename")){
			$this->events('geoip_db_filename not available...');
			return;
		
		}
	
		$cst = array(
		             'GEOIP_COUNTRY_EDITION' => GEOIP_COUNTRY_EDITION,
		             'GEOIP_REGION_EDITION_REV0' => GEOIP_REGION_EDITION_REV0,
		             'GEOIP_CITY_EDITION_REV0' => GEOIP_CITY_EDITION_REV0,
		             'GEOIP_ORG_EDITION' => GEOIP_ORG_EDITION,
		             'GEOIP_ISP_EDITION' => GEOIP_ISP_EDITION,
		             'GEOIP_CITY_EDITION_REV1' => GEOIP_CITY_EDITION_REV1,
		             'GEOIP_REGION_EDITION_REV1' => GEOIP_REGION_EDITION_REV1,
		             'GEOIP_PROXY_EDITION' => GEOIP_PROXY_EDITION,
		             'GEOIP_ASNUM_EDITION' => GEOIP_ASNUM_EDITION,
		             'GEOIP_NETSPEED_EDITION' => GEOIP_NETSPEED_EDITION,
		             'GEOIP_DOMAIN_EDITION' => GEOIP_DOMAIN_EDITION,
		             );
		
		foreach ($cst as $k=>$v) {
		    events($k.': '.geoip_db_filename($v).'  '.(geoip_db_avail($v) ? 'Available':''));
		}	
	}		
	
}

