<?php

class array_category_remotecontrol{
	
	
	
	function return_array($cleanedkey=false){
		

		
		
		
		$MAIN["webex.com"]=True;
		$MAIN["logmein.com"]=True;
		$MAIN["islonline.net"]=True;
		$MAIN["teamviewer.com"]=True;
				
		
		$array=array("teamviewer","webex","islonline","ammyy.com","soluto.com","mysoluto.com");
		
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
