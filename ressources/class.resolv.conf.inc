<?php
include_once(dirname(__FILE__)."/class.tcpip.inc");
class resolv_conf{
	
	var $MainArray=array();
	var $arrayNameServers=array();
	var $lockServ1=0;
	
	function resolv_conf(){
		
		$this->buildConf();
		
	}
	
	private function buildConf(){
		$sock=new sockets();
		$this->MainArray=unserialize(base64_decode($sock->GET_INFO("resolvConf")));
		if(!is_array($this->MainArray)){
			$this->buildDefault();
		}
		$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
		if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
		if(!isset($this->MainArray["TIMEOUT"])){$this->MainArray["TIMEOUT"]=5;}
		if(!isset($this->MainArray["ATTEMPTS"])){$this->MainArray["ATTEMPTS"]=5;}
		if(!isset($this->MainArray["USEROTATION"])){$this->MainArray["USEROTATION"]=0;}
		if(!isset($this->MainArray["DOMAINS1"])){$this->MainArray["DOMAINS1"]=$this->SearchPrinciPalDomain();}
		
		if(!is_numeric($this->MainArray["TIMEOUT"])){$this->MainArray["TIMEOUT"]=5;}
		if(!is_numeric($this->MainArray["ATTEMPTS"])){$this->MainArray["ATTEMPTS"]=5;}
		if(!is_numeric($this->MainArray["USEROTATION"])){$this->MainArray["USEROTATION"]=0;}
		if($this->MainArray["DOMAINS1"]==null){$this->MainArray["DOMAINS1"]=$this->SearchPrinciPalDomain();}
		if(!$this->isValidDomain($this->MainArray["DOMAINS1"])){$this->MainArray["DOMAINS1"]=null;}
		$this->MainArray["DOMAINS1"]=trim(strtolower($this->MainArray["DOMAINS1"]));
		if($EnableKerbAuth==1){
			$UseADAsNameServer=$sock->GET_INFO("UseADAsNameServer");
			if(!is_numeric($UseADAsNameServer)){$UseADAsNameServer=0;}
			if($UseADAsNameServer==1){
				$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
				if(preg_match("#[0-9\.]+#", $array["ADNETIPADDR"])){
					$this->MainArray["DNS1"]=$array["ADNETIPADDR"];
					$this->lockServ1=1;
				}
				
			}
		}
		
		
	}
	
	private function buildDefault(){
		$this->LoadNameServers();
		$this->MainArray["DNS1"]=$this->arrayNameServers[0];
		$this->MainArray["DNS2"]=$this->arrayNameServers[1];
		$this->MainArray["DNS3"]=$this->arrayNameServers[2];
		$ip=new IP();
		if(!$ip->isValid($this->MainArray["DNS1"])){unset($this->MainArray["DNS1"]);}
		if(!$ip->isValid($this->MainArray["DNS2"])){unset($this->MainArray["DNS2"]);}
		if(!$ip->isValid($this->MainArray["DNS3"])){unset($this->MainArray["DNS3"]);}
		
		
	}
	
	public function isValidDomain($domain){
		$domain=trim($domain);
		if(strpos($domain, " ")>0){return false;}
		if(strpos($domain, "[")>0){return false;}
		if(strpos($domain, ".")==0){return false;}
		return true;
	}
	
	private function SearchPrinciPalDomain(){
		exec("/bin/hostname -A 2>&1",$results );
		$ff=@implode("", $results);
		if(preg_match("#([\s+\(\)\[\]]+)#", $ff)){
			$ff=null;
		}
		
		$pos=strpos($ff, ".");
		
		
		if(!$this->isValidDomain($ff)){return "localhost.local";}
		return trim(substr($ff, $pos,strlen($ff)));
		
	}
	
	public function save(){
		$sock=new sockets();
		$sock->SaveConfigFile(base64_encode(serialize($this->MainArray)), "resolvConf");
		
	}
	
	public function build(){
		$rotation=null;
		if($this->MainArray["DOMAINS1"]<>null){$tt[]=$this->MainArray["DOMAINS1"];}
		if($this->MainArray["DOMAINS2"]<>null){$tt[]=$this->MainArray["DOMAINS2"];}
		if($this->MainArray["DOMAINS3"]<>null){$tt[]=$this->MainArray["DOMAINS3"];}
		if($this->MainArray["DOMAINS1"]<>null){$f[]="domain {$this->MainArray["DOMAINS1"]}";}
		
		if(count($tt)>0){
			$f[]="search ".@implode(" ", $tt);
		}
		
		
		if($this->MainArray["USEROTATION"]==1){$rotation=" rotate";}
		$f[]="options attempts:{$this->MainArray["ATTEMPTS"]} timeout:{$this->MainArray["TIMEOUT"]}$rotation";
		
		if($this->MainArray["DNS3"]=="___.___.___.___"){$this->MainArray["DNS3"]=null;}
		
		
		if($this->MainArray["DNS1"]<>null){$f[]="nameserver {$this->MainArray["DNS1"]}";}
		if($this->MainArray["DNS2"]<>null){$f[]="nameserver {$this->MainArray["DNS2"]}";}
		if($this->MainArray["DNS3"]<>null){$f[]="nameserver {$this->MainArray["DNS3"]}";}
		return @implode("\n", $f)."\n";
	}
	
	private function DNSLIST_AS_ROOT(){
		exec("/usr/share/artica-postfix/bin/artica-install --local-dns 2>&1",$results);
		return implode("",$results);
	}
	
	
	private function LoadNameServers(){
		if(!class_exists("IP")){include_once(dirname(__FILE__)."/class.tcpip.inc");}
		include_once(dirname(__FILE__) . '/class.sockets.inc');
		if(isset($GLOBALS["CLASS_SOCKETS"])){$sock=$GLOBALS["CLASS_SOCKETS"];}else{$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}
		if($GLOBALS["AS_ROOT"]){$dns=$this->DNSLIST_AS_ROOT();}else{
			$dns=$sock->getFrameWork('cmd.php?dnslist=yes');
			if($GLOBALS["VERBOSE"]){echo "cmd.php?dnslist=yes -> $dns in ".__CLASS__.'/'.__FUNCTION__. " Line:".__LINE__."\n";}
		}
		$dnslist=explode(";",$dns);
		$ip=new IP();
		if(is_array($dnslist)){
			while (list ($num, $val) = each ($dnslist) ){
				$val=trim(strtolower($val));
				if($val<>null){
					//Check if is a valid tcp/ip address.
					if($ip->isValid($val)){
						$arr[$val]=trim($val);
					}
					
				}}
			}
			
			if(!isset($arr)){return;}			
		    if(is_array($arr)){
				while (list ($num, $val) = each ($arr) ){
					if($ip->isValid($val)){
						$this->arrayNameServers[]=$val;
					}
				}
		   	}
		}	
	
	private static function isValid( $ip ) {
		define( 'RE_IP_BYTE', '(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|0?[0-9]?[0-9])');
		define( 'RE_IP_ADD' , RE_IP_BYTE . '\.' . RE_IP_BYTE . '\.' . RE_IP_BYTE . '\.' . RE_IP_BYTE );
		define( 'RE_IP_PREFIX', '(3[0-2]|[12]?\d)');
		define( 'RE_IP_BLOCK', RE_IP_ADD . '\/' . RE_IP_PREFIX);
		define( 'RE_IPV6_WORD', '([0-9A-Fa-f]{1,4})' );
		define( 'RE_IPV6_GAP', ':(?:0+:)*(?::(?:0+:)*)?' );
		define( 'RE_IPV6_V4_PREFIX', '0*' . RE_IPV6_GAP . '(?:ffff:)?' );
		define( 'RE_IPV6_PREFIX', '(12[0-8]|1[01][0-9]|[1-9]?\d)');
		define( 'RE_IPV6_ADD', '(:(:' . RE_IPV6_WORD . '){1,7}|' . RE_IPV6_WORD . '(:{1,2}' . RE_IPV6_WORD . '|::$){1,7})' );
		define( 'RE_IPV6_BLOCK', RE_IPV6_ADD . '\/' . RE_IPV6_PREFIX );
		define( 'IP_ADDRESS_STRING','(?:' .RE_IP_ADD . '(\/' . RE_IP_PREFIX . '|)' .'|' .RE_IPV6_ADD . '(\/' . RE_IPV6_PREFIX . '|)' .')' );
		return ( preg_match( '/^' . RE_IP_ADD . '$/', $ip) || preg_match( '/^' . RE_IPV6_ADD . '$/', $ip) );
	}	
	
}