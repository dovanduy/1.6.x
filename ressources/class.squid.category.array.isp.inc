<?php

class array_category_isp{
	
	
	function return_array($cleanedkey=false){
		$MAIN["clara.ne.jp"]=True;
		$MAIN["clara.net"]=True;
		$MAIN["calaranet.com"]=True;
		$MAIN["hosting-test.net"]=True;
		$MAIN["ldk-hosting.de"]=True;
		$MAIN["antagus.de"]=True;
		$MAIN["tophoster.de"]=True;
		$MAIN["webhod.de"]=True;
		$MAIN["demon.net"]=True;
		$MAIN["ampnetzwerk.de"]=True;
		$MAIN["strato-hosting.eu"]=True;
		$MAIN["1und1.de"]=True;
		$MAIN["server4you.de"]=True;
		$MAIN["freenet.de"]=True;
		$MAIN["ocn.ne.jp"]=True;
		$MAIN["freenet.de"]=True;
		$MAIN["sakura.ne.jp"]=True;
		$MAIN["cdnplanet.com"]=True;
		$MAIN["scene7.com"]=True;
		$MAIN["bouyguestelecom.fr"]=True;
		$MAIN["infomaniak.ch"]=True;
		$MAIN["ovh.net"]=True;
		$MAIN["fornex.org"]=True;
		$MAIN["and1.com"]=True;
		$MAIN["routers.proxad.net"]=True;
		$MAIN["cdn-cachefront.net"]=True;
		$MAIN["serverloft.com"]=True;
		$MAIN["vectranet.pl"]=True;
		$MAIN["1and1.pl"]=True;
		$MAIN["ddns.net"]=True;
		$MAIN["persiangig.com"]=True;
		$MAIN["bluehost.com"]=True;
		$MAIN["tomsk.ru"]=True;
		$MAIN["kimsufi.com"]=True;
		$MAIN["orange-business.com"]=True;
		$MAIN["domainserver.ne.jp"]=True;
		$MAIN["xserver.jp"]=True;
		$MAIN["panda-world.ne.jp"]=True;
		$MAIN["freeserve.co.uk"]=True;
		$MAIN["freeservers.com"]=True;
		$MAIN["bizland.com"]=True;
		$MAIN["gridserver.com"]=True;
		$MAIN["comvps.com"]=True;
		$MAIN["freehostia.com"]=True;
		$MAIN["com4.com.br"]=True;
		$MAIN["ispvds.com"]=True;
		$MAIN["24-7webhosting.com"]=True;
		$MAIN["aquesthosting.com"]=True;
		$MAIN["compuserve.com"]=True;
		$MAIN["uolhost.com.br"]=True;
		$MAIN["edgesuite.net"]=True;
		$MAIN["componentheaven.com"]=True;
		$MAIN["proxad.net"]=True;
		
		$MAIN["sinaapp.com"]=True;
		$MAIN["your-server.de"]=True;
		$MAIN["hosteurope.de"]=True;
		$MAIN["untergang.de.tf"]=True;
		$MAIN["firma5.com"]=True;
		$MAIN["justhost.com"]=True;
		$MAIN["o2switch.net"]=True;
		$MAIN["contact-sfr.com"]=True;
		$MAIN["tellcom.com.tr"]=True;
		$MAIN["ic.cz"]=True;
		$MAIN["1and1.com"]=True;
		$MAIN["nm.org.nz"]=True;
		$MAIN["16mb.com"]=true;
		$MAIN["110mb.com"]=true;
		$MAIN["smilestart.ne.jp"]=true;
		$MAIN["smileserver.ne.jp"]=true;
		$MAIN["digitalink.ne.jp"]=true;
		$MAIN["servehttp.com"]=true;
		$MAIN["cloudfront.net"]=true;
		$MAIN["homeip.net"]=true;
		$MAIN["dreamhosters.com"]=true;
		$MAIN["hypermart.net"]=true;
		$MAIN["hostmonster.com"]=true;
		$MAIN["awardspace.com"]=true;
		$MAIN["awardspace.info"]=true;
		$MAIN["ladmedia.fr"]=true;
		$MAIN["zp.ua"]=true;
		$MAIN["ua-hosting.com.ua"]=true;
		$MAIN["mailclub.fr"]=True;
		$MAIN["mailclub.info"]=True;
		$MAIN["mailclub.tel"]=True;
		$MAIN["sascdn.com"]=True;
		$MAIN["one.com"]=True;
		$MAIN["netdna-cdn.com"]=True;
		$MAIN["netdna-ssl.com"]=True;
		$MAIN["rackcdn.com"]=True;
		$MAIN["css.1c-bitrix-cdn.ru"]=True;
		$MAIN["js.1c-bitrix-cdn.ru"]=True;
		$MAIN["akamaitechnologies.com"]=True;
		$MAIN["axf8.net"]=True;
		$MAIN["nexcesscdn.net"]=True;
		$MAIN["redcdn.pl"]=True;
		$MAIN["ydstatic.com"]=True;
		$MAIN["e3.pe"]=True;
		$MAIN["voxcdn.com"]=True;
		$MAIN["msecnd.net"]=True;
		$MAIN["hwcdn.net"]=True;
		$MAIN["cookielesscdn.com"]=True;
		$MAIN["amazonaws.com"]=True;
		$MAIN["wdfiles.com"]=True;
		$MAIN["bell.ca"]=True;
		$MAIN["mzstatic.com"]=True;
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
