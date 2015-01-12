<?php

class array_category_arts{
	
	
	function return_array($cleanedkey=false){
		
	
	$MAIN["art.pl"]=True;
	$MAIN["art.br"]=True;
	$MAIN["hujuli.com"]=True;
	$MAIN["deviantart.com"]=True;

	
		
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
