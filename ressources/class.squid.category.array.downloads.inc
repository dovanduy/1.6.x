<?php

class array_category_downloads{
	
	
	
	function return_array($cleanedkey=false){
		
		$MAIN["pandonetworks.com"]=True;
		$MAIN["downloadpipe.com"]=True;
		$MAIN["brothersoft.com"]=True;
		$MAIN["winsite.com"]=True;
		$MAIN["soft112.com"]=True;
		$MAIN["softpile.com"]=True;
		$MAIN["zbigz.com"]=True;
		$MAIN["downv.com"]=True;
		$MAIN["softpedia-static.com"]=True;
		$MAIN["rapidgator.net"]=True;
		$MAIN["rapidshare.com"]=True;
		$MAIN["softonic.com"]=True;
		$MAIN["softonic.fr"]=True;
		$MAIN["softonic.com.br"]=True;
		$MAIN["softonic.it"]=True;
		$MAIN["softonic.de"]=True;
		$MAIN["softonic.nl"]=True;
		$MAIN["softonic.pl"]=True;
		$MAIN["softonic.cn"]=True;
		$MAIN["softonic.net"]=True;
		$MAIN["softonic.es"]=True;
		$MAIN["softonic.jp"]=True;
		$MAIN["softonicdownloads.com"]=True;
		$MAIN["uptodown.com"]=True;
		$MAIN["downloadastro.com"]=True;
		$MAIN["programas-gratis.net"]=True;
		$MAIN["telecharger360.com"]=True;
		$MAIN["telecharger-ici.com"]=True;
		$MAIN["download.cnet.com"]=True;
		$array=array("fileice","rapidshare","soft32.fr","malavida.com","fileparade.com","rapidgator","updatestar.com","softonic","sftcdn","torrentino",
			"yourfiledownloader","rutracker","alphacoders","altools","utorrent","bittorrent","vip-file","softsalad"
			,"software.informer.com","brothersoft","hdttp.com","uptodown.net","soft32.com","mvmfd.net","trulydownload.com","download-server.net","zbigz","otherdownload","uptodown","malavida","soft112","winds10","telecharsoft"
		);
		
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
