<?php 
include_once(dirname(__FILE__)."/class.tcpip.inc");

class squid_familysite{
	
	
	public function GetFamilySites($sitename){
		if(!isset($GLOBALS["TCP_CLASS"])){$GLOBALS["TCP_CLASS"]=new IP();}
		if(!isset($GLOBALS["DEBUG_FAMILIY"])){$GLOBALS["DEBUG_FAMILIY"]=false;}
		$sitename=str_replace("'", "", $sitename);
		if(isset($GLOBALS["GetFamilySites"][$sitename])){return $GLOBALS["GetFamilySites"][$sitename];}
		if(strpos(" $sitename", ".")==0){return $sitename;}
		if($GLOBALS["TCP_CLASS"]->isIPAddress($sitename)){return $sitename;}
	
	
		if(function_exists("idn_to_ascii")){
			$sitename = @idn_to_ascii($sitename, "UTF-8");
		}
	
		$tmp=trim($this->getRegisteredDomain($sitename));
		if($GLOBALS["DEBUG_FAMILIY"]){echo "getRegisteredDomain($sitename) -> $tmp\n";}
	
		$tt=$this->GetFamilySitestt(null,true);
		if($tmp<>null){
			if(isset($tt[$tmp])){
				if($GLOBALS["DEBUG_FAMILIY"]){echo "{$tt[$tmp]} tt[$tmp] -> $sitename\n";}
				$GLOBALS["GetFamilySites"][$sitename]=$sitename;
				return $sitename;
			}
				
			$exp=explode(".",$tmp);
			if(count($exp)>1){
				$tmp=null;
			}else{
				$GLOBALS["GetFamilySites"][$sitename]=$tmp;
				return $tmp;
			}
	
				
				
		}
	
		
	
		$tmp=$this->GetFamilySitestt($sitename);
		if(strpos($tmp, ".")>0){
			$GLOBALS["GetFamilySites"][$sitename]=$tmp;
			return $tmp;
		}
	
		
		if($GLOBALS["VERBOSE"]){echo "GetFamilySitestt -> ?? $sitename\n";}
		$bits = explode('.', $sitename);
		$idz=count($bits);
		$idz-=3;
		
		if( $idz+2 == -1){
			if($GLOBALS["VERBOSE"]){echo "?? $sitename -> $idz +2 = -1\n";}
		}
		
		if (strlen($bits[($idz+2)])==2) {
			$url=$bits[$idz].'.'.$bits[($idz+1)].'.'.$bits[($idz+2)];
		} else if (strlen($bits[($idz+2)])==0) {
			$url=$bits[($idz)].'.'.$bits[($idz+1)];
		} else {
			$url=$bits[($idz+1)].'.'.$bits[($idz+2)];
		}
		if(substr($url, 0,1)=="."){$url=substr($url, 1,strlen($url));}
		$GLOBALS["GetFamilySites"][$sitename]=$url;
		return $url;
	}
	
	private function getRegisteredDomain($signingDomain) {
		if(!isset($GLOBALS["tldTree"])){
			$GLOBALS["tldTree"]=file_get_contents(dirname(__FILE__)."/tldree.db");
		}
	
		$signingDomainParts = explode('.', $signingDomain);
		$tldTree=unserialize($GLOBALS["tldTree"]);
		$result = $this->findRegisteredDomain($signingDomainParts, $tldTree);
	
		if ($result===NULL || $result=="") {
			// this is an invalid domain name
			return NULL;
		}
	
		// assure there is at least 1 TLD in the stripped signing domain
		if (!strpos($result, '.')) {
			$cnt = count($signingDomainParts);
			if ($cnt==1 || $signingDomainParts[$cnt-2]=="") return NULL;
			return $signingDomainParts[$cnt-2].'.'.$signingDomainParts[$cnt-1];
		}
		return $result;
	}

	private function findRegisteredDomain($remainingSigningDomainParts, &$treeNode) {
	
		$sub = array_pop($remainingSigningDomainParts);
	
		$result = NULL;
		if (isset($treeNode['!'])) {
			return '#';
		} else if (is_array($treeNode) && array_key_exists($sub, $treeNode)) {
			$result = $this->findRegisteredDomain($remainingSigningDomainParts, $treeNode[$sub]);
		} else if (is_array($treeNode) && array_key_exists('*', $treeNode)) {
			$result = $this->findRegisteredDomain($remainingSigningDomainParts, $treeNode['*']);
		} else {
			return $sub;
		}
	
		// this is a hack 'cause PHP interpretes '' as NULL
		if ($result == '#') {
			return $sub;
		} else if (strlen($result)>0) {
			return $result.'.'.$sub;
		}
		return NULL;
	}

	
	public function GetFamilySitestt($domain,$getpartOnly=false){
		$tlds = array(
				'com'=>array('ya'=>true,"com"=>true,"gr"=>true,"org"=>true),
				'ac'=>array('gob' => true,'edu'=>true,"com"=>true,"gov"=>true,"org"=>true,"ac"=>true,"co"=>true),
				'ar' => array('gob' => true,"org"=>true,"gov"=>true,"uc"=>true,"ua"=>true,"co"=>true,"org"=>true),
				'as'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true,"ac"=>true),
				'az'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true,"ac"=>true),
				'name'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true,"ac"=>true),
				'bg'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true,"ac"=>true),
				'ba'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true,"ac"=>true),
				'by'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true,"ac"=>true),
				'ffr'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true,"ac"=>true),
				're'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true,"ac"=>true),
				'ru'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true,"ac"=>true),
				'ws'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true,"ac"=>true),
				'tv'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true,"ac"=>true),
				'us'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true,"ac"=>true),
				'org'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true,"ac"=>true),
				'net'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true,"ac"=>true),
				'de'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true,"ac"=>true),
				'dk'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true,"ac"=>true),
				'edu'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true,"ac"=>true),
				'arpa'=>array("in-addr"=>true),
				'fr' => array('gouv' => true,'net'=>true),
				'es'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true),
				'cn'=>array('com'=>true,'net'=>true,"org"=>true),
				'cl'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true,"ac"=>true),
				'am'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true,"ac"=>true),
				'ae'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true,"ac"=>true),
				'at'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true,"ac"=>true),
				'be'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true,"ac"=>true),
				'bh'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true,"ac"=>true),
				'bo'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true,"ac"=>true),
				'ci'=>array('gouv'=>true,"com"=>true,"org"=>true),
				'cd'=>array('gouv'=>true,"com"=>true,"org"=>true),
				'biz'=>array("com"=>true),
				'uk' => array('co' => true,"ac"=>true,"gov"=>true,"org"=>true,"me"=>true,"gov"=>true,"org"=>true),
				'ie' => array('net' => true,"web"=>true,"ac"=>true,"co"=>true,"or"=>true,"gov"=>true,"org"=>true),
				'id' => array('net' => true,"web"=>true,"ac"=>true,"co"=>true,"or"=>true,"gov"=>true,"org"=>true),
				'ua' => array('dn' => true,"dp"=>true,"od"=>true,"gov"=>true,"org"=>true),
				'au' => array('net' => true,"com"=>true,"gov"=>true,"org"=>true),
				
				'pt' => array('com' => true,"gov"=>true,"uc"=>true,"ua"=>true,"gov"=>true,"co"=>true,"org"=>true),
				'gr' => array('com' => true,"gov"=>true,"uc"=>true,"ua"=>true,"gov"=>true,"co"=>true,"org"=>true),
				'lu' => array('com' => true,"gov"=>true,"uc"=>true,"ua"=>true,"gov"=>true,"co"=>true,"org"=>true),
				'np' => array('com' => true,"gov"=>true,"uc"=>true,"ua"=>true,"gov"=>true,"co"=>true,"org"=>true),
				'jo' => array('com' => true,"gov"=>true,"uc"=>true,"ua"=>true,"gov"=>true,"co"=>true,"org"=>true),
				'ph'=> array('com'=>true,"gov"=>true),
				'tw' => array('co' => true,"go"=>true,"in"=>true,"gov"=>true,'ac'=>true,"com"=>true,"net"=>true,"org"=>true),
				'th' => array('co' => true,"go"=>true,"in"=>true,"gov"=>true,'ac'=>true,"org"=>true),
				'tr' => array('com' => true,"org"=>true,"co"=>true,"gov"=>true,"org"=>true),
				'to' => array('com' => true,"org"=>true,"co"=>true,"gov"=>true,"org"=>true),
				'co' => array('gov' => true,"za"=>true,"gov"=>true,"com"=>true,"org"=>true),
				'cc' => array('gov' => true,"za"=>true,"gov"=>true,"com"=>true,"org"=>true),
				'gi' => array('gov' => true,"org"=>true),
				'gov' => true,
				'la'=>array('com' => true,"net"=>true,"org"=>true),
				'ca' => array('qc' => true,"gov"=>true,"org"=>true),
				'ch' =>array('com' => true,"net"=>true,"org"=>true),
				'cn' => array('com' => true,"gov"=>true,"org"=>true),
				'cz' =>array('com' => true,"net"=>true,"org"=>true),
				'ee' =>array('com' => true,"net"=>true,"org"=>true),
				'eu'=>array('com'=>true,'net'=>true,"org"=>true),
				'gl'=>array('com'=>true,'net'=>true,"gov"=>true,"co"=>true,"ac"=>true,"org"=>true),
				'ge'=>array('com'=>true,'net'=>true,"gov"=>true,"co"=>true,"ac"=>true,"org"=>true),
				'hk'=> array('co' => true,"org"=>true),
				'hu'=> array('co' => true,"org"=>true),
				'hr'=> array('co' => true,"org"=>true),
				'il' => array('co' => true,"org"=>true),
				'ir' => array('co' => true,"org"=>true),
				'it'=>array('gov' => true,'net'=>true),
				'im'=>array('gov' => true,'net'=>true),
				'in' => array('co' => true,"ind"=>true,"org"=>true),
				'id' => array('com' => true,"net"=>true,"org"=>true),
				'io' => array('com' => true,"net"=>true,"org"=>true),
				'is' => array('com' => true,"net"=>true,"org"=>true),
				'pl' =>array('com' => true,"net"=>true,"org"=>true),
				'info'=>array('com' => true,"net"=>true,"org"=>true),
				'lb'=>array('com' => true,"net"=>true,"org"=>true),
				'li'=>array('com' => true,"net"=>true,"org"=>true),
				
				
				'ly'=>array('com' => true,"net"=>true,"org"=>true),
				'lv'=>array('com' => true,"net"=>true,"org"=>true),
				'lt'=>array('com' => true,"net"=>true,"org"=>true),
				'jp'=>array('ne'=>true,'org' => true,"co"=>true,"gov"=>true,"ac"=>true),
				'na'=>array('com'=>true),
				'ni'=>array('com'=>true),
				'nl'=>array('com' => true,"net"=>true,"org"=>true),
				'net'=>array('fr'=>true,'it'=>true),
				'jobs'=>array('com' => true,"net"=>true,"org"=>true),
				'invalid'=>true,
				'no'=>array('com' => true,"net"=>true,"org"=>true),
				'nu'=>array('com' => true,"net"=>true,"org"=>true),
				'bz'=>array('com' => true,"net"=>true,"org"=>true),
				'nz' => array('org' => true,"co"=>true,"gov"=>true,"org"=>true),
				'br' => array('com' => true,"org"=>true,"gov"=>true,"tv"=>true),
				'ec' => array('com' => true,"gov"=>true,"tv"=>true,"org"=>true),
				'eg' => array('gov' => true,'com'=>true,"gov"=>true,"org"=>true),
				'fi'=>array('com' => true,"net"=>true,"org"=>true),
				'sa'=>array('com' => true,"net"=>true,"org"=>true),
				'se'=>array('com' => true,"net"=>true,"org"=>true),
				'fm'=>array('com' => true,"net"=>true,"org"=>true),
				'pm'=>array('com' => true,"net"=>true,"org"=>true),
				

				'md'=>array('com' => true,"net"=>true,"org"=>true),
				'local'=>true,
				'team'=>true,
				'travel'=>true,
				'aero'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true,"ac"=>true),
				'ms'=>array('com' => true,"net"=>true,"org"=>true),
				'me'=>array('com' => true,"net"=>true,"org"=>true),
				'mobi'=>array('com' => true,"net"=>true,"org"=>true),
				'gbl'=>array('com' => true,"net"=>true,"org"=>true),
				'mx'=> array('com' => true,"gov"=>true,"tv"=>true,"org"=>true),
				'my'=> array('com' => true,"gov"=>true,"org"=>true),
				'fr'=>array('gouv' => true,"gov"=>true,"org"=>true),
				'pe'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true),
				'pk'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true),
				'ua'=>array('net'=>true,"com"=>true,"gov"=>true,"org"=>true),
				'uy'=>array('net'=>true,"com"=>true,"gov"=>true,"org"=>true),
				'kz'=>array('co'=>true,'com'=>true,"gov"=>true,"co"=>true,"org"=>true),
				'kr'=>array('or'=>true,"gov"=>true,"org"=>true),
				've'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true),
				'vn'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true),
				'za'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true),
				'rs'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true),
				'ro'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true),
				'sh'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true),
				'si'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true),
				'sk'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true),
				'st'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true),
				'su'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true),
				'tl'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true),
				'tc'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true),
				'uz'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true),
				'vc'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true),
					
	
	
		);
		if($getpartOnly){
			foreach ($tlds as $key => $part) {
				$doms[$key]=true;
				if(is_array($part)){
					while (list ($a, $b) = each ($part) ){
						$doms["$a.$key"]=true;
					}
					continue;
				}
			}
			return $doms;
		}
	
		$parts = explode('.', $domain);
		$tmp = $tlds;
		// travers the tree in reverse order, from right to left
		foreach (array_reverse($parts) as $key => $part) {
			if (isset($tmp[$part])) {
				$tmp = $tmp[$part];
			} else {
				break;
			}
		}
		if($getpartOnly){return $tmp;}
	
		$get=implode('.', array_slice($parts, - $key - 1));
		if(substr($get, 0,1)=="."){$get=substr($get, 1,strlen($get)); }
		return $get;
	}	
	
}

?>