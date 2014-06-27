<?php


class external_acl_squid_ldap{
	public $Errors=array();
	
	function external_acl_squid_ldap(){
		
		
		
	}
	
	private function diff_time_min($time){
		$data1 = $time;
		$data2 = time();
		$difference = ($data2 - $data1);
		$results=intval(round($difference/60));
		if($results<0){$results=1;}
		return $results;
	}
	
	
	public function GetGroupsFromMember($member){
		$GLOBALS["TIMELOG"]++;
		if(isset($GLOBALS["QUERYTIME"])){
			$timeEx=$this->diff_time_min($GLOBALS["QUERYTIME"]);
			if($timeEx>15){
				$this->WLOG("[PURGE]: ".count($GLOBALS["MEMBERS_GROUPS"])." item(s) are purged from Cache array after {$timeEx}mn");
				unset($GLOBALS["QUERYTIME"]);unset($GLOBALS["MEMBERS_GROUPS"]);}
		}
		
		if(isset($GLOBALS["MEMBERS_GROUPS"][$member])){
			if(is_array($GLOBALS["MEMBERS_GROUPS"][$member])){
				if(count($GLOBALS["MEMBERS_GROUPS"][$member])>0){
					return $GLOBALS["MEMBERS_GROUPS"][$member];
				}
			}
		}
		
		$this->ConnectToLDAP();
		if(!isset($GLOBALS["QUERYTIME"])){$GLOBALS["QUERYTIME"]=time();}
		
		
	
		if(!$GLOBALS["BIND"]){
			$this->WLOG("[QUERY]: <strong style='color:red'>Error: BIND is broken -> reconnect</strong>");
			ConnectToLDAP();
			if(!$GLOBALS["BIND"]){WLOG("[QUERY]: <strong style='color:red'>Error: BIND pointer is false</strong>");return false;}
		}
	
		if(!$GLOBALS["CONNECTION"]){
			$this->WLOG("[QUERY]: <strong style='color:red'>Error: CONNECTION is broken -> reconnect twice</strong>");
			ConnectToLDAP();
		}
	
		if(!$GLOBALS["CONNECTION"]){
			$this->WLOG("[QUERY]: <strong style='color:red'>Error: CONNECTION is definitively broken aborting !!!...</strong>");
			return false;
		}
	
		$array=array();
		//$link_identifier, $base_dn, $filter, array $attributes = null, $attrsonly = null, $sizelimit = null, $timelimit = null, $deref = null
		$filter=array("memberOf");
		@ldap_set_option($GLOBALS["CONNECTION"], LDAP_OPT_REFERRALS, 0);
		$filter="(&(objectCategory=Person)(objectClass=user)(sAMAccountName=$member))";
	
	
	
		$GLOBALS["QUERIES_NUMBER"]++;
		$link_identifier=$GLOBALS["CONNECTION"];
		$base_dn=$GLOBALS["SUFFIX"];
		$attributes=array();
		$attrsonly=null;
		$sizelimit=null;
		$timelimit= $GLOBALS["LDAP_TIME_LIMIT"];
		if($GLOBALS["VERBOSE"]){$this->WLOG("[QUERY]::$filter -> $filter in $base_dn");}
	
	
	
		$sr =@ldap_search($link_identifier,$base_dn,$filter,$attributes,$attrsonly, $sizelimit, $timelimit);
		if (!$sr) {
			if(is_numeric(ldap_errno($GLOBALS["CONNECTION"]))){
				$error=ldap_errno($GLOBALS["CONNECTION"]);
				$errstr=@ldap_err2str($error);
					
				if($error==-1){
					if(!isset($GLOBALS["RETRY_AFTER_ERROR"])){
						$this->WLOG("[QUERY]: <strong style='color:red'>Error:`$error` ($errstr)</strong> re-connect and retry query...");
						$GLOBALS["RETRY_AFTER_ERROR"]=true;
						return GetGroupsFromMember($member);
					}else{
						$this->WLOG("[QUERY]: <strong style='color:red'>Error:`$error` ($errstr)</strong> Connection lost definitively");
						return false;
					}
	
				}
					
				$this->WLOG("[QUERY]: <strong style='color:red'>Error:`$error` ($errstr)</strong> suffix:{$GLOBALS["SUFFIX"]} $filter, return no user");
				return false;
			}else{
				$this->WLOG("[QUERY]: <strong style='color:red'>Error: unknown Error (ldap_errno not a numeric) suffix:{$GLOBALS["SUFFIX"]} $filter, return no user");
			}
		}
	
	
	
	
		$hash=ldap_get_entries($GLOBALS["CONNECTION"],$sr);
		if(!is_array($hash)){
			$this->WLOG("[QUERY]: <strong style='color:red'>Error: undefined, hash is not an array or did not find user</strong>...");
			return false;
		}
	
	
		unset($GLOBALS["RETRY_AFTER_ERROR"]);
		if(isset($hash[0]["memberof"])){
			for($i=0;$i<$hash[0]["memberof"]["count"];$i++){
				if(preg_match("#^CN=(.+?),#i", $hash[0]["memberof"][$i],$re)){
					$re[1]=trim(strtolower($re[1]));
					if($GLOBALS["DEBUG"] == 1){  $this->WLOG("$member = \"{$re[1]}\""); }
					$array[$re[1]]=true;
				}
					
			}
		}
		if($GLOBALS["DEBUG"] == 1){  $this->WLOG("Return array of ".count($array)." items"); }
		
		$GLOBALS["MEMBERS_GROUPS"][$member]=$array;
		$this->WLOG("[CACHE]: $member -> ".count($GLOBALS["MEMBERS_GROUPS"][$member])." item(s) in cache array");
		if(count($GLOBALS["MEMBERS_GROUPS"][$member])<10){
			if(count($GLOBALS["MEMBERS_GROUPS"][$member])>0){
				$this->WLOG("[CACHE]: $member -> ".@implode(", ", $GLOBALS["MEMBERS_GROUPS"][$member]));
			}
		}
		return $array;
	
	}	
	
	
private function BuildDefault(){
	if(!isset($GLOBALS["KerbAuthInfos"]["LDAP_SERVER"])){
		WLOG("KerbAuthInfos -> Try to find LDAP_SERVER");
		$GLOBALS["KerbAuthInfos"]["LDAP_SERVER"]=$this->BuildDefault_ldap_server();
		WLOG("KerbAuthInfos -> {$GLOBALS["KerbAuthInfos"]["LDAP_SERVER"]}");
	}
	
	if(!isset($GLOBALS["KerbAuthInfos"]["LDAP_PORT"])){$GLOBALS["KerbAuthInfos"]["LDAP_PORT"]=389;}
	if(!isset($GLOBALS["KerbAuthInfos"]["LDAP_SUFFIX"])){
		$SMB=SAMBA_GetNetAdsInfos();
		$GLOBALS["KerbAuthInfos"]["LDAP_SUFFIX"]=$SMB["Bind Path"];
	}
	
	
}	

private function SAMBA_GetNetAdsInfos(){
	if(isset($GLOBALS["CACHE_NET"])){return $GLOBALS["CACHE_NET"];}
	
	if(is_file("/etc/squid3/NET_ADS_INFOS")){
		$array=unserialize(@file_get_contents("/etc/squid3/NET_ADS_INFOS"));
		if(count($array)>5){
			$GLOBALS["CACHE_NET"]=$array;
			return $array;
		}
	}

	$net=LOCATE_NET_BIN_PATH();
	if(!is_file($net)){return array();}
	exec("$net ads info 2>&1",$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#^(.+?):(.+)#",trim($line),$re)){
			$array[trim($re[1])]=trim($re[2]);
		}
	}

	if(!isset($array["KDC server"])){$array["KDC server"]=null;}
	WLOG("$net ads info 2>&1 return ".count($array)." items");
	$GLOBALS["CACHE_NET"]=$array;
	@file_put_contents("/etc/squid3/NET_ADS_INFOS", serialize($array));
	return $array;
}
private function LOCATE_NET_BIN_PATH(){
	$net=$this->internal_find_program("net");
	if(is_file($net)){return $net;}
	$net=$this->internal_find_program("net.samba3");
	if(is_file($net)){return $net;}
}

private function internal_find_program($strProgram){
	global $addpaths;
	$arrPath = array('/bin', '/sbin', '/usr/bin', '/usr/sbin', '/usr/local/bin','/usr/local/sbin','/usr/kerberos/bin');
	if (function_exists("is_executable")) {foreach($arrPath as $strPath) {$strProgrammpath = $strPath . "/" . $strProgram;if (is_executable($strProgrammpath)) {return $strProgrammpath;}}} else {return strpos($strProgram, '.exe');}
}


private function BuildDefault_ldap_server(){
	if(isset($GLOBALS["KerbAuthInfos"]["ADNETIPADDR"])){return $GLOBALS["KerbAuthInfos"]["ADNETIPADDR"];}
	if(isset($GLOBALS["KerbAuthInfos"]["WINDOWS_SERVER_NETBIOSNAME"])){return $GLOBALS["KerbAuthInfos"]["WINDOWS_SERVER_NETBIOSNAME"].".".$GLOBALS["KerbAuthInfos"]["WINDOWS_DNS_SUFFIX"];}
	$SMB=$this->SAMBA_GetNetAdsInfos();
	return $SMB["LDAP server"];
	$this->WLOG("[START]: BuildDefault_ldap_server did not find any LDAP server...");
}

private function WLOG($text=null){
	if(count($this->Errors)>500){$this->Errors=array();}
	if(!isset($GLOBALS["F"])){$GLOBALS["F"] = @fopen("/var/log/squid/external-acl.log", 'a');}
	$trace=@debug_backtrace();
	if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
	$date=@date("Y-m-d H:i:s");
	if (is_file("/var/log/squid/external-acl.log")) {
		$size=@filesize("/var/log/squid/external-acl.log");
		if($size>1000000){
			@fclose($GLOBALS["F"]);
			unlink("/var/log/squid/external-acl.log");
			$GLOBALS["F"] = @fopen("/var/log/squid/external-acl.log", 'a');
		}
		 
		 
	}

	$FinalText="$date ".basename(__FILE__)."[{$GLOBALS["PID"]}]: $text $called";
	$this->Errors[]=$FinalText;
	@fwrite($GLOBALS["F"], $FinalText);
}
	
	
	
private function ConnectToLDAP(){
	$this->BuildDefault();
	$array=$GLOBALS["KerbAuthInfos"];
	if(!is_array($array)){
		$this->WLOG("KerbAuthInfos not an array");
		return false;
	}	
	
	if(!isset($array["LDAP_SERVER"])){WLOG("LDAP_SERVER not set");return;}
	if(!isset($array["LDAP_SUFFIX"])){WLOG("LDAP_SUFFIX not set");return;}
	
	$GLOBALS["SUFFIX"]=$array["LDAP_SUFFIX"];
	$GLOBALS["CONNECTION"]=@ldap_connect($array["LDAP_SERVER"],$array["LDAP_PORT"]);
	//WLOG("[LDAP]: Connecting to LDAP server `{$array["LDAP_SERVER"]}:{$array["LDAP_PORT"]}`");
	if(!$GLOBALS["CONNECTION"]){
		$this->WLOG("[LDAP]: <strong style='color:red'>Fatal: ldap_connect({$array["LDAP_SERVER"]},{$array["LDAP_PORT"]} )");
		@ldap_close();
		return false;
	}	
	
	//WLOG("[LDAP]: Connecting to LDAP server {$array["LDAP_SERVER"]} <span style='font-weight:bold;color:#00B218'>success</span> with suffix:&laquo;{$GLOBALS["SUFFIX"]}&raquo;");
	@ldap_set_option($GLOBALS["CONNECTION"], LDAP_OPT_PROTOCOL_VERSION, 3);
	@ldap_set_option($GLOBALS["CONNECTION"], LDAP_OPT_REFERRALS, 0);	
	@ldap_set_option($GLOBALS["CONNECTION"], LDAP_OPT_PROTOCOL_VERSION, 3); // on passe le LDAP en version 3, necessaire pour travailler avec le AD
	@ldap_set_option($GLOBALS["CONNECTION"], LDAP_OPT_REFERRALS, 0);
	
	if(preg_match("#^(.+?)\/(.+?)$#", $array["WINDOWS_SERVER_ADMIN"],$re)){$array["WINDOWS_SERVER_ADMIN"]=$re[1];}
	if(preg_match("#^(.+?)\\\\(.+?)$#", $array["WINDOWS_SERVER_ADMIN"],$re)){$array["WINDOWS_SERVER_ADMIN"]=$re[1];}
	
	$GLOBALS["BIND"]=@ldap_bind($GLOBALS["CONNECTION"], "{$array["WINDOWS_SERVER_ADMIN"]}@{$array["WINDOWS_DNS_SUFFIX"]}", $array["WINDOWS_SERVER_PASS"]);
	if(!$GLOBALS["BIND"]){
		
		if (@ldap_get_option($GLOBALS["CONNECTION"], LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
			$error=$error."<br>$extended_error";
		}
		
		switch (ldap_errno($GLOBALS["CONNECTION"])) {
			case 0x31:
				$error=$error . "<br>Bad username or password. Please try again.";
				break;
			case 0x32:
				$error=$error . "<br>Insufficient access rights.";
				break;
			case 81:
				$error=$error . "<br>Unable to connect to the LDAP server<br>
				{$array["LDAP_SERVER"]}<br>please,<br>verify if ldap daemon is running<br> or the ldap server address";
				break;
			case -1:
					
				break;
			default:
				$error=$error . "<br>Could not bind to the LDAP server." ."<br>". @ldap_err2str($GLOBALS["CONNECTION"]);
		}
		$this->WLOG("[LDAP]:".__LINE__." Connecting to LDAP server {$array["LDAP_SERVER"]} failed<br>$error");
		return false;
	}
	//WLOG("[LDAP]: Binding to LDAP server {$array["LDAP_SERVER"]} <span style='font-weight:bold;color:#00B218'>success</span>.");
	return true;
}


}