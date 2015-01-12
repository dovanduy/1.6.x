<?php
include_once(dirname(__FILE__)."/class.squid.familysites.inc");
class array_category_sslsites{
	
	private function _ext($MAIN,$www){
		$f=new squid_familysite();
		return $f->filldomains($MAIN, $www);
		
		
	}
	
	
	function return_array($cleanedkey=false){
		
		$MAIN["globalsign.com"]=true;
		$MAIN["thawte.com"]=true;
		$MAIN["digicert.com"]=true;
		$MAIN["verisign.com"]=true;
		$MAIN["verisign.net"]=true;
		$MAIN["verisign.com.ng"]=true;
		$MAIN["geotrust.com"]=true;
		$MAIN["entrust.net"]=true;
		$MAIN["startssl.com"]=true;
		$MAIN["symcb.com"]=true;
		$MAIN["truste.com"]=true;
		
		$MAIN=$this->_ext($MAIN, "verisign");
		
		while (list ($www, $cat) = each ($MAIN) ){
			$array[]=$www;
				
		}
		

		
		if(!$cleanedkey){return $array;}
		while (list ($index, $www) = each ($array) ){
			if(strpos(" $www", ".")==0){continue;}
			if(strpos(" $www", "*")>0){continue;}
			$XRETURN[$www]=true;
		}
		
		return $XRETURN;
	}
	
	
}
