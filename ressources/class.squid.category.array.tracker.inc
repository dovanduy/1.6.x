<?php

class array_category_tracker{
	
	
	
	function return_array($cleanedkey=false){
		$array=array("cnzz","xiti","trackad","adn-du-jour.fr","tracktor.in","outerstats.com","adprotect.net","appmetrx.com","statcamp.net","resultspage","hstrck.com","memecounter","meetrics","effectivemeasure","irotracker","rtrk","omtrdc.net",
				"ezakus","statistik","odiso","online-metrix","cleervoyance","pandastats","estadisticasgratis",
				"lognormal","zeroredirect","quantcount","raster-xml","revolvermaps","optimizely","hcatracker","adlooxtracking.com",
				"redhelper","ceipmsn","quantserve.com","luckyorange.com","trafiz.net","applicationstat.com","himpi.com","pingjam.net","pingbutter.net","slipbutter.com","uqrzo.com","postclickmarketing.com",
				"cedexis.org","trackvoluum.com","gcm-web.net","tv2nd.net","sharethis.com",
				"emltrk.com","tlm100.net","userdail","webstatsdomain.net","wtstats.info",
				"trkag1.com","clickbank.net","coremetrics.com","tradenote.net",
				"my-super-super.com","go2cloud.org","cloud4stat.com","equipoelfa.at",
				"zeni.fr","tcopartners.com","geovisite","celebros-analytics","chartbeat",
				"feedjit","webtrekk","presencedynamicreport","directtrack.com","adlooxtracking",
				"mobileapptracking","emarketing-factory.com","estat.com","naolink-je.com","sindelclick.com","optimizely.com","xiti.com","hitslink","rtrk.nl","shinystat.com","cmos.greencompute.org","ninjastat.com","oadz.com",
		);
		
		$MAIN["liveperson.net"]=true;
		$MAIN["radarsync.com"]=true;
		$MAIN["cedexis-radar.net"]=true;
		$MAIN["cedexis.net"]=true;
		$MAIN["yahoodns.net"]=true;
		$MAIN["2mdn.net"]=true;
		$MAIN["2o7.net"]=true;
		$MAIN["esomniture.com"]=true;
		$MAIN["optimizely.com"]=true;
		$MAIN["telemetryverification.net"]=true;
		$MAIN["spylog.com"]=true;
		$MAIN["emltrk.com"]=true;
		$MAIN["weborama.fr"]=true;
		$MAIN["omtrdc.net"]=true;
		$MAIN["histats.su"]=true;
		$MAIN["apnic.net"]=true;
		$MAIN["rfihub.com"]=true;
		$MAIN["ehistats.su"]=true;
		$MAIN["mktoresp.com"]=true;
		$MAIN["bazaarvoice.com"]=true;
		$MAIN["rfihub.net"]=true;
		$MAIN["lognormal.net"]=true;
		$MAIN["clickbank.net"]=true;
		$MAIN["greencompute.org"]=true;
		$MAIN["woopra-ns.com"]=true;
		$MAIN["msecndsl.net"]=true;
		$MAIN["hittail.com"]=true;
		$MAIN["trafiz.net"]=true;
		$MAIN["xiti.com"]=true;
		$MAIN["webtrekk.net"]=true;
		$MAIN["metriweb.be"]=true;
		$MAIN["cnzz.com"]=true;
		$MAIN["cedexis.com"]=true;
		$MAIN["sitemeter.com"]=true;
		$MAIN["ask.com"]=true;
		$MAIN["pandastats.net"]=true;
		$MAIN["postaffiliatepro.com"]=true;
		$MAIN["tlm100.net"]=true;
		$MAIN["qualtrics.com"]=true;
		$MAIN["btrll.com"]=true;
		$MAIN["netmng.com"]=true;
		$MAIN["meebo.com"]=true;
		$MAIN["tnsinternet.be"]=true;
		$MAIN["sitescout.com"]=true;
		$MAIN["baynote.net"]=true;
		$MAIN["websitetestlink.com"]=True;
		$MAIN["rtrk.de"]=True;
		$MAIN["rtrk.com.au"]=True;
		$MAIN["rtrk.ca"]=True;
		$MAIN["rtrk.pl"]=True;
		$MAIN["rtrk.cz"]=True;
		$MAIN["rtrk.sg"]=True;
		$MAIN["rtrk.co.uk"]=True;
		$MAIN["rtrk.fr"]=True;
		$MAIN["rtrk.it"]=True;
		$MAIN["rtrk.es"]=True;
		$MAIN["rtrk.com"]=True;
		$MAIN["rtrk.com.br"]=True;
		
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
