<?php

class array_category_music{
	
	
	
	function return_array($cleanedkey=false){
		

	
		
		
		
		$MAIN["musicblogg.se"]=True;
		$MAIN["musicblog.fr"]=True;
		$MAIN["musicblog.com"]=True;
		$MAIN["musicblog.be"]=True;
		$MAIN["musicblog.com.br"]=True;
		$MAIN["deezer.com"]=True;
		$MAIN["spotify.com"]=True;
		$MAIN["spotilocal.com"]=True;
		$MAIN["sonymusic.fr"]=True;
		$MAIN["sonymusic.es"]=True;
		$MAIN["sonymusic.com"]=True;
		$MAIN["sonymusic.de"]=True;
		$MAIN["sonicomusica.com"]=True;
		$MAIN["music-bazaar.com"]=True;
		$MAIN["grooveshark.com"]=True;
		
		$array=array("easyzic.com","musicblog","sheetmusicengine","bandcamp.com","mp3slash.net","pitchfork","kazaa","universalmusic","sonymusic","hulkshare","projectplaylist");
		
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
