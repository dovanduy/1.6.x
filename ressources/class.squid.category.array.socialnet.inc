<?php

class array_category_socialnet{
		
	function return_array($cleanedkey=false){
		

	
		$MAIN["disqus.com"]=True;
		$MAIN["disqus.com"]=True;
		$MAIN["fbcdn.net"]=True;
		$MAIN["feeligo.com"]=True;
		$MAIN["facebook.com"]=True;
		$MAIN["facebook.es"]=True;
		$MAIN["facebook.com.ar"]=True;
		$MAIN["facebook.fr"]=True;
		$MAIN["facebook.de"]=True;
		$MAIN["viadeo.com"]=True;
		$MAIN["viadeo.com"]=True;
		$MAIN["linkedinlabs.com"]=True;
		$array=array('tortugasocial',"fbcdn","timepad.ru","meetup.com","yfrog.com","loginradius.com","landable.net","xing.com","mylife","wamba","wambacdn","badoocdn","gfranq","gigya","badoo","twimg","pinterest","p4fb","thestudentroom","facebook","smeet","disqus","hostmonarchy","pipl","myspacecdn","myspace");
		
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
