<?php

class array_category_forums{
	
	
	
	function return_array($cleanedkey=false){
		
		
		
		
		$MAIN["designermediacdn"]=True;
		
		
		$array=array("bababian.com","servimg.com","imgur.com",
				"nmimg.org","bousedezebu.com","frz.io","imgwykop.pl","edgefcs",
				"picturepush.com","imagevenue.com","mzsimg.com","web-album.org",
				"t26","tinypic.com","stylesfire.com",
				"fotolog.com");
				

		
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
