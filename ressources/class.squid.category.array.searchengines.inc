<?php
include_once(dirname(__FILE__)."/class.squid.familysites.inc");
class array_category_searchengines{
	
	private function _ext($MAIN,$www){
		$f=new squid_familysite();
		return $f->filldomains($MAIN, $www);
		
		
	}
	
	
	function return_array($cleanedkey=false){
		
		
		$MAIN["msedge.net"]=true;
		$MAIN["yahoodns.net"]=true;
		$MAIN["1e100.net"]=true;
		
		$MAIN["list.ru"]=true;
		$MAIN["msn.com"]=true;
		$MAIN["s-msn.com"]=true;
		$MAIN["ya.ru"]=true;
		$MAIN["yandex.net"]=true;
		$MAIN["yandex.ru"]=True;
		$MAIN["yahoo.co.jp"]=true;
		$MAIN["yahoo.com"]=true;
		
		
		$MAIN["cylex-france.fr"]=true;
		$MAIN["gstatic.com"]=true;
		$MAIN["startpagina.nl"]=True;
		$MAIN["google.com"]=True;
		$MAIN["googleusercontent.com"]=True;
		$MAIN["paginegialle.it"]=True;
		$MAIN["telephone-adresse.com"]=True;

		
		$array=array("vulka.es","atver","yandex","contacteo.com","gexperiments2.com","gexperiments1.com","webmarchand","yahoodns.net","dastelefonbuch.de","annuairefrancais.fr",
				"repertoire-web","towar.ru","cylex-france.fr","bdstatic.com");
		
		
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
