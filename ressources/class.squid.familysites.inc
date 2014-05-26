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
				'com'=>array('ya'=>true,"com"=>true,"gr"=>true),
				'ac'=>true,
				'as'=>true,
				'az'=>true,
				'name'=>true,
				'bg'=>true,
				'ba'=>true,
				'by'=>true,
				'ffr'=>true,
				're'=>true,
				'ru'=>true,
				'ws'=>true,
				'tv'=>true,
				'us'=>true,
				'org'=>true,
				'net'=>true,
				'de'=>true,
				'dk'=>true,
				'edu'=>true,
				'arpa'=>array("in-addr"=>true),
				'es'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true),
				'cn'=>array('com'=>true,'net'=>true),
				'cl'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true),
				'ar'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true),
				'am'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true),
				'ae'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true),
				'at'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true),
				'be'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true,"ac"=>true),
				'bh'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true,"ac"=>true),
				'bo'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true,"ac"=>true),
				'ci'=>array('gouv'=>true,"com"=>true),
				'cd'=>array('gouv'=>true,"com"=>true),
				'biz'=>array("com"=>true),
				'uk' => array('co' => true,"ac"=>true,"gov"=>true,"org"=>true,"me"=>true,"gov"=>true),
				'ie' => array('net' => true,"web"=>true,"ac"=>true,"co"=>true,"or"=>true,"gov"=>true),
				'id' => array('net' => true,"web"=>true,"ac"=>true,"co"=>true,"or"=>true,"gov"=>true),
				'ua' => array('dn' => true,"dp"=>true,"od"=>true,"gov"=>true),
				'au' => array('net' => true,"com"=>true,"gov"=>true),
				'ar' => array('gob' => true),
				'pt' => array('com' => true,"gov"=>true,"uc"=>true,"ua"=>true,"gov"=>true,"co"=>true),
				'gr' => array('com' => true,"gov"=>true,"uc"=>true,"ua"=>true,"gov"=>true,"co"=>true),
				'lu' => array('com' => true,"gov"=>true,"uc"=>true,"ua"=>true,"gov"=>true,"co"=>true),
				'np' => array('com' => true,"gov"=>true,"uc"=>true,"ua"=>true,"gov"=>true,"co"=>true),
				'jo' => array('com' => true,"gov"=>true,"uc"=>true,"ua"=>true,"gov"=>true,"co"=>true),
				'ph'=> array('com'=>true,"gov"=>true),
				'tw' => array('co' => true,"go"=>true,"in"=>true,"gov"=>true,'ac'=>true,"com"=>true,"net"=>true),
				'th' => array('co' => true,"go"=>true,"in"=>true,"gov"=>true,'ac'=>true),
				'tr' => array('com' => true,"org"=>true,"co"=>true,"gov"=>true),
				'to' => array('com' => true,"org"=>true,"co"=>true,"gov"=>true),
				'co' => array('gov' => true,"za"=>true,"gov"=>true,"com"=>true),
				'cc' => array('gov' => true,"za"=>true,"gov"=>true,"com"=>true),
				'gi' => array('gov' => true),
				'gov' => true,
				'la'=>true,
				'ca' => array('qc' => true,"gov"=>true),
				'ch' =>true,
				'cn' => array('com' => true,"gov"=>true),
				'cz' =>true,
				'ee' =>true,
				'eu'=>array('com'=>true,'net'=>true),
				'gl'=>array('com'=>true,'net'=>true,"gov"=>true,"co"=>true,"ac"=>true),
				'ge'=>array('com'=>true,'net'=>true,"gov"=>true,"co"=>true,"ac"=>true),
				'hk'=> array('co' => true),
				'hu'=> array('co' => true),
				'hr'=> array('co' => true),
				'il' => array('co' => true),
				'in' => array('co' => true,"ind"=>true),
				'io' => true,
				'is' => true,
				'pl' =>array('com'=>true),
				'info'=>true,
				'lb'=>true,
				'li'=>true,
				'ly'=>true,
				'lv'=>true,
				'lt'=>true,
				'jp'=>array('ne'=>true,'org' => true,"co"=>true,"gov"=>true,"ac"=>true),
				'na'=>array('com'=>true),
				'ni'=>array('com'=>true),
				'nl'=>true,
				'net'=>array('fr'=>true,'it'=>true),
				'jobs'=>true,
				'invalid'=>true,
				'no'=>true,
				'nu'=>true,
				'bz'=>true,
				'nz' => array('org' => true,"co"=>true,"gov"=>true),
				'br' => array('com' => true,"org"=>true,"gov"=>true,"tv"=>true),
				'ec' => array('com' => true,"gov"=>true,"tv"=>true),
				'eg' => array('gov' => true,'com'=>true,"gov"=>true),
				'fi'=>true,
				'se'=>true,
				'fm'=>true,
				'pm'=>true,
				'fr' => array('gouv' => true,'net'=>true),
				'it'=>array('gov' => true,'net'=>true),
				'im'=>array('gov' => true,'net'=>true),
				'md'=>true,
				'local'=>true,
				'team'=>true,
				'travel'=>true,
				'aero'=>true,
				'ms'=>true,
				'me'=>true,
				'mobi'=>true,
				'gbl'=>true,
				'mx'=> array('com' => true,"gov"=>true,"tv"=>true,"org"=>true),
				'my'=> array('com' => true,"gov"=>true),
				'fr'=>array('gouv' => true,"gov"=>true),
				'pe'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true),
				'pk'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true),
				'ua'=>array('net'=>true,"com"=>true,"gov"=>true),
				'uy'=>array('net'=>true,"com"=>true,"gov"=>true),
				'kz'=>array('co'=>true,'com'=>true,"gov"=>true,"co"=>true),
				'kr'=>array('or'=>true,"gov"=>true),
				've'=>array('co'=>true,'com'=>true,"gov"=>true),
				'vn'=>array('co'=>true,'com'=>true,"gov"=>true),
				'za'=>array('co'=>true,'com'=>true,"gov"=>true),
				'rs'=>array('co'=>true,'com'=>true,"gov"=>true),
				'ro'=>array('co'=>true,'com'=>true,"gov"=>true),
				'sh'=>array('co'=>true,'com'=>true,"gov"=>true),
				'si'=>array('co'=>true,'com'=>true,"gov"=>true),
				'sk'=>array('co'=>true,'com'=>true,"gov"=>true),
				'st'=>array('co'=>true,'com'=>true,"gov"=>true),
				'su'=>array('co'=>true,'com'=>true,"gov"=>true),
				'tl'=>array('co'=>true,'com'=>true,"gov"=>true),
				'tc'=>array('co'=>true,'com'=>true,"gov"=>true),
				'uz'=>array('co'=>true,'com'=>true,"gov"=>true),
				'vc'=>array('co'=>true,'com'=>true,"gov"=>true),
					
	
	
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