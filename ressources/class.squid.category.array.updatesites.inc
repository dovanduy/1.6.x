<?php

class array_category_updatesites{
	
	
	
	function return_array($cleanedkey=false){
		
		
		$MAIN["pack.google.com"]=True;
		$MAIN["phobos.apple.com"]=True;
		$MAIN["ls.apple.com"]=True;
		$MAIN["apple.com-a.akamaihd.net"]=True;
		$MAIN["push.apple.com"]=True;
		$MAIN["itunes.apple.com"]=True;
		$MAIN["avast.com"]=True;
		$MAIN["ipv6test.com"]=True;
		$MAIN["wns.windows.com"]=True;
		$MAIN["kaspersky-labs.com"]=True;
		$MAIN["geo.kaspersky.com"]=True;
		$MAIN["icloud.com"]=True;
		$MAIN["gdatasecurity.de"]=True;
		$MAIN["iphmx.com"]=True;
		$MAIN["archive.ubuntu.com"]=True;
		$MAIN["windowsupdate.microsoft.com"]=True;
		$MAIN["articatech.net"]=True;
		$MAIN["sophosxl.net"]=True;
		$MAIN["llnw.net"]=True;
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
