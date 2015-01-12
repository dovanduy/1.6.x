<?php
include_once(dirname(__FILE__)."/class.squid.familysites.inc");

	

class array_category_translator{
	private function _ext($MAIN,$www){
		$f=new squid_familysite();
		return $f->filldomains($MAIN, $www);
	
	
	}
	
	function return_array($cleanedkey=false){
		
	
		$MAIN["linguee.fr"]=True;
		$MAIN["linguee.com"]=True;
		$MAIN["nciku.com"]=True;
		$MAIN["woxikon.de"]=True;
		$MAIN["proz.com"]=True;
		$MAIN["allwords.com"]=True;
		$MAIN["translate.googleusercontent.com"]=True;
		
		$MAIN=$this->_ext($MAIN,"translate.google");
		$MAIN=$this->_ext($MAIN,"babelfish.yahoo");
		$MAIN=$this->_ext($MAIN,"zaixian-fanyi");
		$MAIN=$this->_ext($MAIN,"babylon");
		$MAIN=$this->_ext($MAIN,"translator");
		
		
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
