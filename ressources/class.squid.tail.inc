<?php


class squid_tail{
	public $error=null;
	public $ToRemove=false;
	
	
	function squid_tail(){
		if(!isset($GLOBALS["USERSDB"])){
				$this->LOADUSERDB();}
			else{
				$ttltime=filemtime("/etc/squid3/usersMacs.db");
				if($GLOBALS["USERSDBT"]<>$ttltime){
					$this->LOADUSERDB();
				}
		}
		
		if(!isset($GLOBALS["EnableRemoteStatisticsAppliance"])){
			$GLOBALS["EnableRemoteStatisticsAppliance"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableRemoteStatisticsAppliance");
			if(!is_numeric($GLOBALS["EnableRemoteStatisticsAppliance"])){$GLOBALS["EnableRemoteStatisticsAppliance"]=0;}
		}
		
	}
	
	
	private function LOADUSERDB(){
		$GLOBALS["USERSDBT"]=filemtime("/etc/squid3/usersMacs.db");
		$usersMacsDB=@file_get_contents("/etc/squid3/usersMacs.db");
		$GLOBALS["USERSDB"]=unserialize($usersMacsDB);
	}
	
	private function InjectSize($user,$ip,$mac,$UserAgent,$size,$xtime=0){
		if($GLOBALS["EnableRemoteStatisticsAppliance"]==1){return;}

				
	}
	
	
	public function parse_tail($buffer,$xtime=0){
			$this->ToRemove=false;
			if($GLOBALS["EnableRemoteStatisticsAppliance"]==1){
				$this->error="EnableRemoteStatisticsAppliance =1";
				return;}
			
			$buffer=trim($buffer);
			if(preg_match("#GET cache_object#",$buffer)){$this->events("[SQUID]: cache_object abort".__LINE__);return true;}
			if(preg_match("#^.+?squid\[[0-9]+\]:#", $buffer,$re)){$buffer=str_replace($re[0], "",$buffer);}
			
			
			
			if(preg_match('#MAC:(.+?)\s+(.+?)\s+.+?\s+(.*?)\s+\[.+?:(.+?)\s+.+?\]\s+"(PROPFIND|GET|POST|LOCK|PUT|CONNECT|NONE|HEAD|OPTIONS)\s+(.+?)\s+.+?"\s+([0-9]+)\s+([0-9]+)\s+([A-Z_]+)#',$buffer,$re)){
				    $cached=0;
				    $mac=trim($re[1]);
					$ip=trim($re[2]);
					$user=$re[3];
					$time=date('H:i:s');
					if($xtime>0){$time=date('H:i:s',$xtime);}
					$uri=$re[6];
					$code_error=$re[7];
					$size=$re[8];
					$SquidCode=$re[9];
					if($ip=="127.0.0.1"){
						$this->error="IP = 127.0.0.1  - $uri line:".__LINE__;
						$this->ToRemove=true;
						return;}
					
					if($ip=="::"){
						$this->error="IP = $ip - $uri line:".__LINE__;
						$this->ToRemove=true;
						return;}
					if($this->CACHEDORNOT($SquidCode)){$cached=1;}
					if($mac=="00:00:00:00:00:00"){$mac==null;}
					
					if(preg_match('#UserAgent:"(.+?)"#', $buffer,$re)){$UserAgent=$re[1];}
					if(preg_match('#Forwarded:"(.*?)"#', $buffer,$re)){$Forwarded=trim($re[1]);}
					if($Forwarded=="-"){$Forwarded=null;}
					if($Forwarded=="0.0.0.0"){$Forwarded=null;}
					if($Forwarded=="255.255.255.255"){$Forwarded=null;}
					if(strlen($Forwarded)>4){
						if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $Forwarded)){$ip=$Forwarded;$mac=null;}
					}	
					
					$this->Builsql($ip,$user,$uri,$code_error,$size,$time,$cached,$mac,$xtime);								
					$this->InjectUserAuthDb($user,$ip,$mac,$UserAgent,$xtime);
					
					return true;
						
			}	

			
			
			if(preg_match('#(.+?)\s+.+?\s+(.*?)\s+\[.+?:(.+?)\s+.+?\]\s+"(PROPFIND|GET|POST|CONNECT|PUT|LOCK|NONE|HEAD|OPTIONS)\s+(.+?)\s+.+?"\s+([0-9]+)\s+([0-9]+)\s+([A-Z_]+)#',$buffer,$re)){
				    $cached=0;
					$ip=$re[1];
					$user=$re[2];
					if($user=="-"){if(isset($re[3])){$user=$re[3];}}
					$size=$re[7];
					$time=date('H:i:s');
					if($xtime>0){$time=date('H:i:s',$xtime);}
					$SquidCode=$re[8];
					if($ip=="127.0.0.1"){
						$this->error="IP = 127.0.0.1 - $uri line:".__LINE__;
						$this->ToRemove=true;
						return;}
						
					if($ip=="::"){
						$this->error="IP = $ip - $uri line:".__LINE__;
						$this->ToRemove=true;
						return;
					}						
						
					if($this->CACHEDORNOT($SquidCode)){$cached=1;}
					
					if(preg_match('#UserAgent:"(.+?)"#', $buffer,$re)){$UserAgent=$re[1];}
					if(preg_match('#Forwarded:"(.*?)"#', $buffer,$re)){$Forwarded=trim($re[1]);}
					if($Forwarded=="-"){$Forwarded=null;}
					if($Forwarded=="0.0.0.0"){$Forwarded=null;}
					if($Forwarded=="255.255.255.255"){$Forwarded=null;}
					if(strlen($Forwarded)>4){
						if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $Forwarded)){$ip=$Forwarded;}
					}	
					$this->Builsql($ip,$user,$uri,$code_error,$size,$time,$cached,null,$xtime);
					$this->InjectUserAuthDb($user,$ip,$mac,$UserAgent,$xtime);
					
					return true;
						
			}

			if(preg_match('#MAC:(.+?)\s+(.+?)\s+.+?\s+(.*?)\s+\[.+?:(.+?)\s+.+?\]\s+"(PROPFIND|GET|POST|PUT|LOCK|CONNECT|NONE|HEAD|OPTIONS)\s+(.+?)$#',$buffer,$re)){
					$cached=0;
					$ip=$re[1];
					$user=$re[2];
					if($user=="-"){if(isset($re[3])){$user=$re[3];}}
					$time=date('H:i:s');
					if($xtime>0){$time=date('H:i:s',$xtime);}
					
					if($ip=="127.0.0.1"){
						$this->error="IP = 127.0.0.1 - $uri line:".__LINE__;
						$this->ToRemove=true;
						return;}
						
					if($ip=="::"){
						$this->error="IP = $ip - $uri line:".__LINE__;
						$this->ToRemove=true;
						return;
					}						
					$size=0;
					if(preg_match('#UserAgent:"(.+?)"#', $buffer,$re)){$UserAgent=$re[1];}
					if(preg_match('#Forwarded:"(.*?)"#', $buffer,$re)){$Forwarded=trim($re[1]);}
					if($Forwarded=="-"){$Forwarded=null;}
					if($Forwarded=="0.0.0.0"){$Forwarded=null;}
					if($Forwarded=="255.255.255.255"){$Forwarded=null;}
					if(strlen($Forwarded)>4){
						if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $Forwarded)){$ip=$Forwarded;}
					}	
					$this->Builsql($ip,$user,$uri,$code_error,$size,$time,$cached,null,$xtime);
					$this->InjectUserAuthDb($user,$ip,$mac,$UserAgent,$xtime);
					
					return true;				
			}
			
			$this->error="Unable to detect $buffer in line:".__LINE__." class ".__CLASS__;
			$this->events("[SQUID]: Not filtered: $buffer line:".__LINE__);
			return false;
	
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
		if($GLOBALS["EnableRemoteStatisticsAppliance"]==1){return;}
		if($GLOBALS["DisableArticaProxyStatistics"]==1){return;}
		$md5=md5($userAgent);
		if(is_file("/var/log/artica-postfix/squid-userAgent/$md5.sql")){return;}
		@file_put_contents("/var/log/artica-postfix/squid-userAgent/$md5.sql", $userAgent);
	}

	
	private function InjectUserAuthDb($user,$ip,$mac,$userAgent){
		if($GLOBALS["EnableRemoteStatisticsAppliance"]==1){return;}
		if($GLOBALS["DisableArticaProxyStatistics"]==1){return;}
		if($ip=="127.0.0.1"){return;}
		if($mac=="00:00:00:00:00:00"){$mac=null;}
		if($mac==null){$mac=$this->GetMacFromIP($ip);}
		if($user=="-"){$user=null;}
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
	
	


	private function Builsql($CLIENT,$username=null,$uri,$code_error,$size=0,$time,$cached,$mac=null,$xtime=0){
			if($GLOBALS["EnableRemoteStatisticsAppliance"]==1){return;}
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
			$searchWords=array();
	
	if($mac=="00:00:00:00:00:00"){$mac=null;}
	
	$URLAR=parse_url($uri);
	if(isset($URLAR["host"])){$sitename=$URLAR["host"];}
	
	if($sitename==null){if(preg_match("#^(?:[^/]+://)?([^/:]+)#",$uri,$re)){$sitename=$re[1];}}
	if(preg_match("#^www\.(.*)#", $sitename,$rz)){$sitename=$rz[1];}
	
	if(preg_match("#google\.[a-z\.]+\/search.+?q=(.+?)&#i",$uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
	if(preg_match("#yahoo\..*?p=(.*?)&#i",$uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}	
	if(preg_match("#bing\..*?search\?q=(.*?)&#i",$uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
	if(preg_match("#baidu\..*?\/s\?wd=(.*?)&#i",$uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
	if(preg_match("#yandsearch\?text=(.*?)&#",$uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
	if(preg_match("#amazon.*?field-keywords=(.*?)&#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;		}
	if(preg_match("#ebay.*?_nkw=(.*?)&#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
	if(preg_match("#wikipedia\..*?search=(.*?)&#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
	if(preg_match("#duckduckgo.*?q=(.*?)($|&)#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
	if(preg_match("#ask\..*?q=(.*?)($|&)#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
	if(preg_match("#clusty\..*?query=(.*?)($|&)#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
	if(preg_match("#mahalo\..*?q=(.*?)($|&)#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
	if(preg_match("#youtube\..*?query=(.*?)($|&)#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
	if(preg_match("#deezer.*?\/search\/(.*?)($|&|\/)#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
	if(preg_match("#priceminister.+?\/s\/(.*?)($|&|\/)#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
	if(preg_match("#leboncoin.+?q=(.*?)($|&|\/)#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
	
	if(!isset($searchWords["WORDS"])){
		if(preg_match("#\..*?\?(q|queryString|query|search|recherche|find|searchstring|keywords)=(.*?)($|&)#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[2]);$searchWords["SITENAME"]=$sitename;}
	}
	if(isset($searchWords["WORDS"])){
		if(trim($searchWords["WORDS"]=="query")){unset($searchWords["WORDS"]);}
		if(is_numeric($searchWords["WORDS"])){return;}
		$searchWords["WORDS"]=str_replace('"', "", $searchWords["WORDS"]);
		$searchWords["WORDS"]=str_replace("'", "", $searchWords["WORDS"]);
	}
	
	if($sitename==null){
		$this->events(__FUNCTION__."::".__LINE__." Unable to extract domain name from `$uri`");
		return;
	}	
	
	if(preg_match("#^(.+?):[0-9]+#", $sitename,$ri)){$sitename=$ri[1];}
	$Country=$this->GetCountry($sitename);
	$site_IP=$this->GetIpFromName($sitename);
	
	if(preg_match("#^www\.(.+)#",$sitename,$ri)){$sitename=$ri[1];}


	
	$TYPE=$squid_error[$code_error];
	$REASON=$TYPE;
	$CLIENT=trim($CLIENT);
	$date=date('Y-m-d')." ". $time;
	if($xtime>0){$date=date('Y-m-d H:i:s',$xtime);}
	if($size==null){$size=0;}
	
	$zMD5=md5("$uri$date$CLIENT$username$TYPE$Country$site_IP");
	$hostname=null;
	
	if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $sitename)){$sitename=gethostbyaddr($sitename);}	
	if(!is_dir("/var/log/artica-postfix/pagepeeker")){@mkdir("/var/log/artica-postfix/pagepeeker",600,true);}
	if(!is_dir("/var/log/artica-postfix/dansguardian-stats2")){@mkdir("/var/log/artica-postfix/dansguardian-stats2",600,true);}
	
		
	if(trim($mac)<>null){
		if(!isset($GLOBALS["USERSDB"]["MACS"][$mac]["UID"])){$GLOBALS["USERSDB"]["MACS"][$mac]["UID"]=null;}
		if($GLOBALS["USERSDB"]["MACS"][$mac]["UID"]<>null){
			$this->events("Builsql(): client:$CLIENT: MAC:`$mac` user:`$username`-> {$GLOBALS["USERSDB"]["MACS"][$mac]["UID"]} (".__LINE__.")");
		}
		
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
		if($GLOBALS["VERBOSE"]){echo "Youtube:$serialized\n";}
		@file_put_contents("/var/log/artica-postfix/youtube/".md5($serialized).".ytube", $serialized);
		$serialized=null;
		$youtube=array();
		//https://gdata.youtube.com/feeds/api/videos/EPldrIzZMkg?v=2&alt=jsonc
		}	

	
	$uri=addslashes($uri);
	$Country=addslashes($Country);
	$username=addslashes($username);
	


	if(!is_file("/var/log/artica-postfix/pagepeeker/".md5($sitename))){
		
		@file_put_contents("/var/log/artica-postfix/pagepeeker/".md5($sitename), $sitename);
	}	
	
	if($mac=="00:00:00:00:00:00"){$mac=null;}
	if(strpos($mac, ":")==0){$mac=null;}
	
	$FINAL_ARRAY["sitename"]=$sitename;
	$FINAL_ARRAY["uri"]=$uri;
	$FINAL_ARRAY["TYPE"]=$TYPE;
	$FINAL_ARRAY["REASON"]=$REASON;
	$FINAL_ARRAY["CLIENT"]=$CLIENT;
	$FINAL_ARRAY["date"]=$date;
	$FINAL_ARRAY["zMD5"]=$zMD5;
	$FINAL_ARRAY["site_IP"]=$site_IP;
	$FINAL_ARRAY["Country"]=$Country;
	$FINAL_ARRAY["size"]=$size;
	$FINAL_ARRAY["username"]=$username;
	$FINAL_ARRAY["cached"]=$cached;
	$FINAL_ARRAY["mac"]=$mac;
	$FINAL_ARRAY["hostname"]=$hostname;
	$FINAL_ARRAY["xtime"]=$xtime;
	
	
	if(trim($username)=="-"){$username=null;}
	// Realtime size.....
	//-------------------------------------------------------------
	$RTTSIZEPATH="/var/log/artica-postfix/squid-RTTSize/".date("YmdH",$xtime);
	$RTTSIZEARRAY=array();
	$RTTSIZEARRAY=unserialize(@file_get_contents($RTTSIZEPATH));
	$RTTSIZEARRAY["IP"][$CLIENT]["SIZE"]=$RTTSIZEARRAY["IP"][$CLIENT]["SIZE"]+$size;
	$RTTSIZEARRAY["IP"][$CLIENT]["HITS"]=$RTTSIZEARRAY["IP"][$CLIENT]["HITS"]+1;
	if($mac<>null){
		$RTTSIZEARRAY["MAC"][$mac]["SIZE"]=$RTTSIZEARRAY["MAC"][$mac]["SIZE"]+$size;
		$RTTSIZEARRAY["MAC"][$mac]["HITS"]=$RTTSIZEARRAY["MAC"][$mac]["HITS"]+1;
	}
	
	if($username<>null){
		$RTTSIZEARRAY["UID"][$username]["SIZE"]=$RTTSIZEARRAY["UID"][$username]["SIZE"]+$size;
		$RTTSIZEARRAY["UID"][$username]["HITS"]=$RTTSIZEARRAY["UID"][$username]["HITS"]+1;
	}	

	
	@file_put_contents($RTTSIZEPATH, serialize($RTTSIZEARRAY));
	//-------------------------------------------------------------
	if(isset($searchWords["WORDS"])){
		if(preg_match("#gstatic\.[a-z\.]+$#",$searchWords["SITENAME"])){if(preg_match("#^tbn:.*?#", $searchWords["WORDS"])){unset($searchWords["WORDS"]);}}
	}
	
	if(isset($searchWords["WORDS"])){
		$searchWords["ipaddr"]=$CLIENT;
		if($xtime>0){$searchWords["date"]=date("Y-m-d H:i:s",$xtime);}else{$searchWords["date"]=date("Y-m-d H:i:s");}
		$searchWords["uid"]=$username;
		$searchWords["mac"]=$mac;
		$searchWords["hostname"]=$hostname;
		$searchWordsSer=serialize($searchWords);
		if(!is_dir("/var/log/artica-postfix/searchwords")){@mkdir("/var/log/artica-postfix/searchwords",0755);}
		@file_put_contents("/var/log/artica-postfix/searchwords/".md5($searchWordsSer),$searchWordsSer);
	}
	
	
	if($GLOBALS["VERBOSE"]){echo " /var/log/artica-postfix/dansguardian-stats2/$zMD5.sql ";}
	
	//$this->events("[SQUID]: dansguardian-stats2: ".$sql."  (".__LINE__.")" );
	@file_put_contents("/var/log/artica-postfix/dansguardian-stats2/$zMD5.sql",serialize($FINAL_ARRAY));	
	if(count($GLOBALS["RTIME"])>500){unset($GLOBALS["RTIME"]);}
	$GLOBALS["RTIME"][]=array($sitename,$uri,$TYPE,$REASON,$CLIENT,$date,$zMD5,$site_IP,$Country,$size,$username,$mac);
	@file_put_contents("/etc/artica-postfix/squid-realtime.cache",base64_encode(serialize($GLOBALS["RTIME"])));
	
	
  
}

public function GetIpFromName($sitename){
	if(isset($GLOBALS["IPs"][$sitename])){return $GLOBALS["IPs"][$sitename];}
	$GLOBALS["IPs"][$sitename]=trim(gethostbyname($sitename));
	return $GLOBALS["IPs"][$sitename];
}

public function GetCountry($sitename){
		$Country=null;
		
		if(!is_file("/usr/share/GeoIP/GeoIPCity.dat")){
			if(is_file("/usr/share/GeoIP/GeoLiteCity.dat")){
				@mkdir("/usr/share/GeoIP",0755,true);
				system("/bin/ln -s /usr/share/GeoIP/GeoLiteCity.dat /usr/share/GeoIP/GeoIPCity.dat >/dev/null 2>&1");
			}else{
				return null;
			}
		}		
		
		
		$site_IP=$this->GetIpFromName($sitename);
		if(!isset($_GET["IPs"])){$_GET["IPs"]=array();}
		if(!isset($_GET["COUNTRIES"])){$_GET["COUNTRIES"]=array();}
		if(count($_GET["IPs"])>5000){unset($_GET["IPs"]);}
		if(count($_GET["COUNTRIES"])>5000){unset($_GET["COUNTRIES"]);}
		if(isset($GLOBALS["COUNTRIES"][$site_IP])){return $GLOBALS["COUNTRIES"][$site_IP];}
		if(!function_exists("geoip_record_by_name")){$GLOBALS["COUNTRIES"][$site_IP]=null;return null;}
		if($site_IP==null){$site_IP=$sitename;}

		
		
		
		$record = @geoip_record_by_name($site_IP);
		
		if ($record) {
			$GLOBALS["COUNTRIES"][$site_IP]=$record["country_name"];;
		}else{
			$GLOBALS["COUNTRIES"][$site_IP]=null;
		}
		
	return $GLOBALS["COUNTRIES"][$site_IP];
	
}

	private function GetMacFromIP($ipaddr){
		return null;
		$ipaddr=trim($ipaddr);
		$ttl=date('YmdH');
		if(count($GLOBALS["CACHEARP"])>3){unset($GLOBALS["CACHEARP"]);}
		if(isset($GLOBALS["CACHEARP"][$ttl][$ipaddr])){return $GLOBALS["CACHEARP"][$ttl][$ipaddr];}
		
		if(!isset($GLOBALS["SBIN_ARP"])){$unix=new unix();$GLOBALS["SBIN_ARP"]=$unix->find_program("arp");}
		if(!isset($GLOBALS["SBIN_ARPING"])){$unix=new unix();$GLOBALS["SBIN_ARPING"]=$unix->find_program("arping");}
		
		if(strlen($GLOBALS["SBIN_ARPING"])>3){
			$cmd="{$GLOBALS["SBIN_ARPING"]} $ipaddr -c 1 -r 2>&1";
			exec($cmd,$results);
			while (list ($num, $line) = each ($results)){
				if(preg_match("#^([0-9a-zA-Z\:]+)#", $line,$re)){
					$GLOBALS["CACHEARP"][$ttl][$ipaddr]=$re[1];
					return $GLOBALS["CACHEARP"][$ttl][$ipaddr];
				}
			}
		}
		
		
		$results=array();
			
		if(strlen($GLOBALS["SBIN_ARP"])<4){return;}
		if(!isset($GLOBALS["SBIN_PING"])){$unix=new unix();$GLOBALS["SBIN_PING"]=$unix->find_program("ping");}
		if(!isset($GLOBALS["SBIN_NOHUP"])){$unix=new unix();$GLOBALS["SBIN_NOHUP"]=$unix->find_program("nohup");}
		
		
		
		
		$cmd="{$GLOBALS["SBIN_ARP"]} -n \"$ipaddr\" 2>&1";
		$this->events($cmd);
		exec($cmd,$results);
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
		
		$GLOBALS["CACHEARP"][$ttl][$ipaddr]=null;
			
		
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
			
	public function GetComputerName($ip){
		if($GLOBALS["resvip"][$ip]<>null){return $GLOBALS["resvip"][$ip];}
		$name=gethostbyaddr($ip);
		$GLOBALS["resvip"][$ip]=$name;
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
		    $this->events($k.': '.geoip_db_filename($v).'  '.(geoip_db_avail($v) ? 'Available':''));
		}	
	}		
	
}

