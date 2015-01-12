<?php

class array_category_chat{
	
	
	function return_array($cleanedkey=false){
		
	
		$MAIN["messenger.live.com"]=True;
		$MAIN["messenger.yahooapis.com"]=True;
		$MAIN["olark.com"]=True;
		$MAIN["chatango.com"]=True;
		$MAIN["talkgadget.google.com"]=True;
		$MAIN["chatroll.com"]=True;
		$MAIN["whatsapp.net"]=True;
		$MAIN["ningim.com"]=True;
		$MAIN["msg.ac4.yahoo.com"]=True;
		$MAIN["msg.yahoo.com"]=True;
		$MAIN["chatroll.com"]=True;
		$MAIN["webim.ru"]=True;
		$MAIN["livetex.ru"]=True;
		$MAIN["skype.com"]=True;
		
		$MAIN["iloveim.com"]=True;
		$MAIN["chatovod.ru"]=True;
		
		
		$array=array("iadvize","livechatinc","envolve","pop800","whatsapp","chatango","freeshoutbox","chatroll","vichatter","livechat");
	
		
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
