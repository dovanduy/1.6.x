<?php

class array_category_webapps{
	
	
	function return_array($cleanedkey=false){
		
	
		$MAIN["slide.com"]=True;
		$MAIN["docs.googleusercontent.com"]=True;
		$MAIN["apps-viewer.googleusercontent.com"]=True;
		$MAIN["symbaloo.com"]=true;
		$MAIN["ingenie.fr"]=True;
		$MAIN["dmvalet.com"]=True;
		$MAIN["fastviewer.com"]=True;
		$MAIN["salesforce.com"]=True;
		$MAIN["office.microsoft.com"]=True;
		$MAIN["force.com"]=True;
		$MAIN["infusionsoft.com"]=True;
		$MAIN["sgizmo.com"]=True;
		$MAIN["appspot.com"]=True;
		$MAIN["sharethis.com"]=True;
		$MAIN["questionmarket.com"]=True;
		$MAIN["brighttalk.[a-z]+"]=True;
		$MAIN["salesforce.[a-z]+"]=True;
		$MAIN["wimi.[a-z]+"]=True;
		$MAIN["ixbus.net+"]=True;
		$MAIN["join.me"]=True;
		$MAIN["phx.gbl"]=True;
		$MAIN["clickbook.net"]=True;
		
		
	
		
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
