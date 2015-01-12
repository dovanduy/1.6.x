<?php

class array_category_webplugins{
	
	
	function return_array($cleanedkey=false){
		
	
		$MAIN["onicon.ru"]=True;
		$MAIN["wufoo.com"]=True;
		$MAIN["googleapis.com"]=True;
		
		
		$array=array("onicon","jquerytools","readspeaker","talkinglayers","xmlrequest","googleapis","linkwithin");
	
		
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
