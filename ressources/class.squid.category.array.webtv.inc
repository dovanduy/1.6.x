<?php
include_once(dirname(__FILE__)."/class.squid.familysites.inc");
class array_category_webtv{
	
	private function _ext($MAIN,$www){
		$f=new squid_familysite();
		return $f->filldomains($MAIN, $www);
		
		
	}
	
	
	function return_array($cleanedkey=false){
		$MAIN["tv.sohu.com.cn"]=True;
		$MAIN["crystalreality.com"]=True;
		$MAIN["crystalplayer.com"]=True;
		$MAIN["sky.com"]=True;
		$MAIN["rtl.lu"]=True;
		$MAIN["20min-tv.ch"]=True;
		$MAIN["programme-tv.net"]=True;
		$MAIN["france3.fr"]=True;
		$MAIN["natgeotv.com"]=True;
		$MAIN["crystal-tv.com"]=True;
		$MAIN["extratv.com"]=True;
		$MAIN["justin.tv"]=True;
		$MAIN["arcoiris.tv"]=True;
		$MAIN["letv.com"]=True;
		$MAIN["letvimg.com"]=True;
		$MAIN["m6web.fr"]=True;
		$MAIN["tv8montblanc.com"]=True;
		$MAIN["canal-plus.net"]=True;
		$MAIN["canal13.cl"]=True;
		$MAIN["volia.net"]=True;
		$MAIN["cgates.lt"]=True;
		$MAIN["spiegel.tv"]=True;
		$MAIN["bfmtv.com"]=True;
		$MAIN["worldnow.com"]=True;
		$MAIN["ustream.tv"]=True;
		$MAIN["freshmilk.tv"]=True;
		$MAIN["justin.tv"]=True;
		$MAIN["fullrate.dk"]=True;
		$MAIN["blip.tv"]=True;
		$MAIN["francetv.fr"]=True;
		$MAIN["arte.tv"]=True;
		$MAIN["tvb.com"]=True;
		$MAIN["programme-tv.net"]=True;
		$MAIN["france3.fr"]=True;
		$MAIN["tf1.fr"]=True;
		$MAIN["m6web.fr"]=True;
		$MAIN["tv2.dk"]=True;
		$MAIN["france2.fr"]=True;
		$MAIN["rtl.lu"]=True;
		$MAIN["canal-plus.com"]=True;
		$MAIN["mtv.com"]=True;
		$MAIN["canalplus.fr"]=True;
		$MAIN["letv.com"]=True;
		$MAIN["orange.fr"]=True;
		$MAIN["flumotion.com"]=True;
		$MAIN["francetelevisions.tv"]=True;
		$MAIN["premiumtv.co.uk"]=True;
		$MAIN["wat.tv"]=True;
		$MAIN["hypnoweb.net"]=True;
		$MAIN["netflix.com"]=True;
		$MAIN["mtv.it"]=True;
		$MAIN["ceskatelevize.cz"]=True;
		$MAIN["novy.tv"]=True;
		$MAIN["mediasetpremium.it"]=True;
		$MAIN["maxupdates.tv"]=True;
		$MAIN["telequebec.tv"]=True;
		$MAIN["itv.com"]=True;
		$MAIN["dctp.tv"]=True;
		$MAIN["web.tv"]=True;
		$MAIN["onlinegratis.tv"]=True;
		$MAIN["francetvinfo.fr"]=True;
		$MAIN["luxnet.ua"]=True;
		$MAIN["stv.tv"]=True;
		$MAIN["intv.ru"]=True;
		$MAIN["snacktv.de"]=True;
		$MAIN["net3-tv.net"]=True;
		$MAIN["tvnet.lv"]=True;
		$MAIN["cztv.com"]=True;
		$MAIN["20min-tv.ch"]=True;
		$MAIN["tv.com"]=True;
		$MAIN["vcdn.biz"]=True;
		$MAIN["n-tv.de"]=True;
		$MAIN["onlinetvrecorder.com"]=True;
		$MAIN["best-tv.com"]=True;
		$MAIN["hunantv.com"]=True;
		$MAIN["sf.tv"]=True;
		$MAIN["worldnow.com"]=True;
		$MAIN["snacktv.de"]=True;
		$MAIN["sf.tv"]=True;
		$MAIN["reise-tv.com"]=True;
		$MAIN["mtv.uol.com.br"]=True;
		$MAIN["hypnoweb.net"]=True;

		
		$array=array("moiseriali","blip","ustream","zap2it","simplestream.com","tvrage","arte","canalplus","mtv","diziizle","m6web",
				"bfmtv","france2","france3","24tv.ua","francetv.fr","veberod.com","plitv.tv","spiegel.tv","ytv.com","televisionfanatic.com","markiza.sk","luxnet.ua","hypnoweb.net","fullrate.dk","entertv.gr","tidaltv","francetelevisions","mediasetpremium.it","tvnet","jcntv.jp","webtv-corp.ru","wmaker.tv","leclubcanal","premiumtv","partner-tvmag.net");
		

		
		
		$MAIN=$this->_ext($MAIN, "tv.yahoo");
		$MAIN=$this->_ext($MAIN, "tv.search");
		
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
