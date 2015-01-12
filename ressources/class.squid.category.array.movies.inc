<?php

class array_category_movies{
	
	
	
	function return_array($cleanedkey=false){
		

	
		$MAIN["hd.sohu.com"]=True;
		$MAIN["hd.sohu.com.cn"]=True;
		$MAIN["movies.yahoo.com"]=True;
		$MAIN["npostreaming.nl"]=True;
		$MAIN["movies.mxn.com"]=True;
		$MAIN["livestream.com"]=True;
		$MAIN["streamtheworld.com"]=True;
		$MAIN["movies-online.squrrel.com"]=True;
		$MAIN["akamaistream.net"]=True;
		$MAIN["ustream.tv"]=True;
		$MAIN["hizliizlefilm.com"]=True;
		$MAIN["warnerbros.com"]=True;
		$MAIN["click-network.com"]=True;
		$MAIN["www.clickcabletv.com"]=True;
		$MAIN["allocine.fr"]=True;
		$MAIN["movieseach.com"]=True;
		$MAIN["moviedost.com"]=True;
		$array=array("cine-news-mail","wiki-videos","filmografie","vimple.ru","kenhhd.tv","xemphimtot.com","madevid.com","serverfilm","filmboxextra","ztreaming.com","static-maxdome.de","maxdome.de","igru-film","tushkan.net","watchever.de","stream-up.com","eaglepictures","crackle.com","vidcache.net","purevid.com");
		
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
