<?php

class array_category_audiovideo{
	
	
	
	function return_array($cleanedkey=false){
		$MAIN["screen.yahoo.com"]=True;
		$MAIN["video.yahoo.com"]=True;
		$MAIN["nowvideo.ch"]=True;
		$MAIN["brightcove.com"]=True;
		$MAIN["bcove.me"]=True;
		$MAIN["llnwd.net"]=True;
		$MAIN["edgefcs.net"]=True;
		$MAIN["edgesuite.net"]=True;
		$MAIN["edgecastcdn.net"]=True;
		$MAIN["data.vod.itc.cn"]=True;
		$MAIN["googlevideo.com"]=True;
		$MAIN["akamaihd.net"]=True;
		$MAIN["youtube.com"]=True;
		$MAIN["dailymotion.com"]=True;
		$MAIN["real.com"]=True;
		$MAIN["video.msn.com"]=True;
		$MAIN["video.xin.msn.com"]=True;
		$MAIN["novamov.com"]=True;
		$MAIN["octoshape.net"]=True;
		$MAIN["myvi.ru"]=True;
		$MAIN["videoweed.es"]=True;
		$MAIN["video.query.yahoo.com"]=True;
		$MAIN["video.search.yahoo.com"]=True;
		$MAIN["xunlei.com"]=True;
		$MAIN["vimeocdn.com"]=True;
		$MAIN["loadup.ru"]=True;
		$MAIN["youku.com"]=True;
		$MAIN["podbean.com"]=True;
		$MAIN["56.com"]=True;
		$MAIN["pptv.com"]=True;
		$MAIN["videohub.com"]=True;
		$MAIN["5mn.com"]=True;
		$MAIN["vidyotv.com"]=True;
		$MAIN["popscreen.com"]=True;
		$MAIN["novamov.com"]=True;
		$MAIN["akamaistream.net"]=True;
		$MAIN["videoweed.es"]=True;
		$MAIN["wrzuta.pl"]=True;
		$MAIN["ipercast.net"]=True;
		$MAIN["longtailvideo.com"]=True;
		$MAIN["playstream.com"]=True;
		$MAIN["brainsonic.com"]=True;
		$MAIN["uloz.to"]=True;
		$MAIN["nflxvideo.net"]=True;
		$MAIN["ivi.ru"]=True;
		$MAIN["googlevideo.com"]=True;
		$MAIN["nowvideo.eu"]=True;
		$MAIN["video-klipleri.org"]=True;
		$MAIN["springboardplatform.com"]=True;
		$MAIN["qbrick.com"]=True;
		$MAIN["libsyn.com"]=True;
		$MAIN["video.qq.com"]=True;
		$MAIN["vidtomp3.com"]=True;
		$MAIN["viddler.com"]=True;
		$MAIN["videomore.ru"]=True;
		$MAIN["cntv.cn"]=True;
		$MAIN["coolcdn.ch"]=True;
		$MAIN["magnify.net"]=True;
		$MAIN["mediaworksit.net"]=True;
		$MAIN["npostreaming.nl"]=True;
		$MAIN["youku.com"]=True;
		$MAIN["livebox.cz"]=True;
		$MAIN["bitgravity.com"]=True;
		$MAIN["travideos.es"]=True;
		
$array=array("googlevideo.com","tune.pk","streamuk.com","exashare.com","video.sibnet.ru","vk.com","tunefiles.com","23video.com","minoto-video.com","wikiblue.com",
				"youtube","piximedia","vuclip.com","mporatrons.com","viostream.com","ubistream.tv",
				"atmcdn","samplage.com","vidspot.net","streamprovider.net",
				"gamaniak","popscreen.com","dailymotion","vidzur.com",
				"listentoyoutube","gis.bredband2.com","googlevideo.com",
				"video.msn.com","shockya","brightcove","videostep.com","qbrick.com",
				"castup","cdnvideo","ustream.tv","movideo.com","mvpitsolutions.com",
				"rocktube","vimeocdn","freemake","windowsmedia","tare","livestream","nowvideo","vimeo","real",
				"dropshots","allmyvideos","pcvideo","loadup","videobam","popscreencdn","ultimedia",
				"firststreaming","videoegg","lkimg","flvstorage","topclip","dailymotion","trilulilu","videoplaza","video-loader");
		
		
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
