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
				'al'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'ar' => array('gob' => true,"com"=>true,"org"=>true,"gov"=>true,"uc"=>true,"ua"=>true,"co"=>true,"org"=>true),
				'as'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true,"ac"=>true),
				'at'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true,"ac"=>true),
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
				'fo'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'fr' => array('gouv' => true,'net'=>true),
				'ec' => array('com' => true,"gov"=>true,"uc"=>true,"ua"=>true,"gov"=>true,"co"=>true,"org"=>true),
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
				'ua' => array('dn' => true,"dp"=>true,"od"=>true,"gov"=>true,"org"=>true,"com"=>true),
				'au' => array('net' => true,"com"=>true,"gov"=>true,"org"=>true),
				
				'pt' => array('com' => true,"gov"=>true,"uc"=>true,"ua"=>true,"gov"=>true,"co"=>true,"org"=>true),
				'gr' => array('com' => true,"gov"=>true,"uc"=>true,"ua"=>true,"gov"=>true,"co"=>true,"org"=>true),
				'lu' => array('com' => true,"gov"=>true,"uc"=>true,"ua"=>true,"gov"=>true,"co"=>true,"org"=>true),
				'np' => array('com' => true,"gov"=>true,"uc"=>true,"ua"=>true,"gov"=>true,"co"=>true,"org"=>true),
				'jo' => array('com' => true,"gov"=>true,"uc"=>true,"ua"=>true,"gov"=>true,"co"=>true,"org"=>true),
				'ph'=> array('com'=>true,"gov"=>true),
				'tw' => array('co' => true,"go"=>true,"in"=>true,"gov"=>true,'ac'=>true,"com"=>true,"net"=>true,"org"=>true),
				'th' =>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'tr' =>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'to' =>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'co' => array('gov' => true,"za"=>true,"gov"=>true,"com"=>true,"org"=>true),
				'cc' => array('gov' => true,"za"=>true,"gov"=>true,"com"=>true,"org"=>true),
				'gi' =>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'gov' => true,
				'la'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'ca'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'ch'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'cl'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'cn'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'cz'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'cu'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'ee'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'eu'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'gl'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'ge'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'hk'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'hu'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'hr'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'il'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'ir'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'it'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'im'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'in'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true,"ind"=>true),
				'id'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'io'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'is'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'pl'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'info'=>array('com' => true,"net"=>true,"org"=>true),
				'lb'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'li'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				
				
				'ly'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'lv'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'lt'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'jp'=>array('ne'=>true,'org' => true,"co"=>true,"gov"=>true,"ac"=>true),
				'na'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'ni'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'nl'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'net'=>array('fr'=>true,'it'=>true),
				'jobs'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'invalid'=>true,
				'no'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'nu'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'bz'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'nz'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'br'=>array('com' => true,"org"=>true,"gov"=>true,"tv"=>true),
				'ec'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'eg'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'fi'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				
				'fm'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'pm'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'pg'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),

				'md'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'local'=>true,
				'team'=>true,
				'travel'=>true,
				'aero'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true,"ac"=>true),
				'ma'=>array('co'=>true,'cu'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'ms'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'me'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'mobi'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'gbl'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'mx'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true,"tv"=>true),
				'my'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'fr'=>array('gouv' => true,"gov"=>true,"org"=>true),
				'pe'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'pk'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'ua'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true,"dn"=>true),
				'uy'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'kz'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'kr'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true,"or"=>true),
				've'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'vn'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'za'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'rs'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'ro'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'sa'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'se'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'sg'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'sh'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'si'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'sk'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'st'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'su'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'tl'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'tc'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'uz'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'uy'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'vc'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				'vu'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
				've'=>array('co'=>true,'com'=>true,"gov"=>true,"org"=>true,"net"=>true,"edu"=>true,"ac"=>true),
					
	
	
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