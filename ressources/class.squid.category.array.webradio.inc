<?php

class array_category_webradio{
	
	
	function return_array($cleanedkey=false){
		
	
		$MAIN["listen2myradio.com"]=True;
		$MAIN["wdmbradio.com"]=True;
		$MAIN["musicradio.com"]=True;
		$MAIN["noadradio.com"]=True;
		$MAIN["radioairplay.com"]=True;
		$MAIN["imusicaradios.com.br"]=True;
		$MAIN["qthemusic.com"]=True;
		$MAIN["radioplaymusic.es"]=True;
		$MAIN["nrj.fr"]=True;
		$MAIN["radiotuna.com"]=True;
		$MAIN["skyrock.net"]=True;
		$MAIN["radio.de"]=True;
		$MAIN["radio.fr"]=True;
		$MAIN["ask.fm"]=True;
		$MAIN["radiobeta.info"]=True;
		$MAIN["we7.com"]=True;
		$MAIN["radiobeta.com"]=True;
		$MAIN["livemixtapes.com"]=True;
	
		
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
