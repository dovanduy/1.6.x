<?php

class array_category_health{
	
	
	
	function return_array($cleanedkey=false){
		

		
		
		$array=array('zyzy.org',"healthcentral","sanofi-aventis","sanofi","valtaf","lgds","apigem"
				,"curie","doctuo","katalog-stomatologu");
		
		
		$MAIN["osomd.com"]=True;
		$MAIN["chirurgiens-dentistes.fr"]=True;
		$MAIN["med.br"]=True;
		$MAIN["itocd.net"]=True;
		$MAIN["nhs.uk"]=True;
		$MAIN["ytgangya.com"]=True;
		$MAIN["valtaf.be"]=True;
		

		
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
