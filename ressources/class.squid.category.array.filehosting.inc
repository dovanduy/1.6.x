<?php

class array_category_filehosting{
	
	
	
	function return_array($cleanedkey=false){
		
	
		
		$MAIN["dropbox.com"]=True;
		$MAIN["storage.msn.com"]=True;
		$MAIN["chomikuj.pl"]=True;
		$MAIN["livefilestore.com"]=True;
		
		$MAIN["4shared.com"]=True;
		$MAIN["bb.sky.com"]=True;
		$MAIN["smcloud.net"]=True;
		$MAIN["babidou.com"]=True;
		$MAIN["filesonic.com"]=True;
		$MAIN["uploaded.net"]=True;
		$MAIN["cloudfiles.rackspacecloud.com"]=True;
		$MAIN["ryushare.com"]=True;
		$MAIN["depositfiles.com"]=True;
		$MAIN["docs.google.com"]=True;
		$MAIN["1fichier.com"]=True;
		$MAIN["storage.live.com"]=True;
		$MAIN["skydrive.live.com"]=True;
		$MAIN["photos.live.com"]=True;
		$MAIN["ex.ua"]=True;
		$MAIN["minus.com"]=True;
		$MAIN["depositfiles.org"]=True;
		$MAIN["dfiles.eu"]=True;
		$MAIN["googledrive.com"]=True;
		$MAIN["firedrive.com"]=True;
		
		$MAIN["mediafire.com"]=True;
		$MAIN["rusfolder.com"]=True;
		
		$MAIN["daum.net"]=True;
		$MAIN["wetransfer.com"]=True;
		$MAIN["putlocker.com"]=True;
		$MAIN["letitbit.net"]=True;
		$MAIN["akamai.net"]=True;
		$MAIN["mega.co.nz"]=True;
		$MAIN["archive.org"]=True;
		$MAIN["hotfile.com"]=True;
		$MAIN["theuploadhelp.com"]=True;
		$MAIN["uploadhero.com"]=True;
		$MAIN["fotopages.com"]=True;
		$MAIN["cachefly.net"]=True;
		$MAIN["webshots.com"]=True;
		$MAIN["msecnd.net"]=True;
		$MAIN["yousendit.com"]=True;
		$MAIN["uploadhero.co"]=True;
		$MAIN["fileserve.com"]=True;
		$MAIN["aimini.net"]=True;
		$MAIN["wuala.com"]=True;
		$MAIN["usrfiles.com"]=True;
		$MAIN["fplive.net"]=True;
		$MAIN["wdfiles.com"]=True;
		
		
		
		$array=array("ge.tt","filewatcher","cloudfront","1drv.com","edgesuite","amazonaws","staztic.com","nowdownload.eu","smcloud","mediafire","dnsmex",
				"akamai","dropbox","dropboxusercontent","yousendit","scene7","depositfiles",
				"zingload","pinterest","e-loader","zcache.fr","storage.msn.com","wuala","wetransfer","lumfile.se","cherchelefile","whatyoushare","seewebstorage.it","fiberupload"
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
