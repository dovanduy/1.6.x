<?php
include_once(dirname(__FILE__)."/class.squid.familysites.inc");
class array_category_womanbrand{
	
	private function _ext($MAIN,$www){
		$f=new squid_familysite();
		return $f->filldomains($MAIN, $www);
		
		
	}
	
	
	function return_array($cleanedkey=false){
		
		
		
		$MAIN["meltyfashion.fr"]=True;
		$MAIN["aufeminin.com"]=True;
		$MAIN["meltystyle.fr"]=True;
		$MAIN["costume.dk"]=True;
		
		$MAIN["mcm.fr"]=True;
		
		$MAIN["efashionsale.com"]=True;
		$MAIN["cityfashion.be"]=True;
		
		$MAIN["fashionguide.com.tw"]=True;
		$MAIN["fashionmag.com"]=True;
		$MAIN["donnamoderna.com"]=True;
		$MAIN["oricom.ca"]=True;
		$MAIN["grazia.fr"]=True;
		$MAIN["onlylady.com"]=True;
		$MAIN["volia.net"]=True;
		$MAIN["grazia.es"]=True;
		
		$MAIN["vogue.com.cn"]=True;
		$MAIN["fibre2fashion.com"]=True;
		$MAIN["fashiongroup.com"]=True;
		$MAIN["modesektionen.dk"]=True;
		$MAIN["trends.com.cn"]=True;

		
		
				$array=array("mywoman",
				"yxlady","semuaartikel.com",
				"aufeminin",
				"prime-beaute",
				"mmasculin",
				"vogue",
				"cosmopolitan",
				"grazia",
				"gzmama","meltyfashion","mcm.fr","lockerz","onlylady","costume.dk","meltystyle.fr",
				);
		
		
		
		
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
