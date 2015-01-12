<?php

class array_category_spyware{
	
	
	
	function return_array($cleanedkey=false){
		

	
		
		
		
		$MAIN["maxonline.com.sg"]=True;
		$MAIN["yournailed.net"]=True;
		$MAIN["aenaethi.cc"]=True;
		$MAIN["vkvkvkvkvk.ru"]=True;
		
		

		
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
