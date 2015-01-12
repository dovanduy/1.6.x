<?php

class array_category_publicite{
	
	
	
	function return_array($cleanedkey=false){
		$array=array("adnexus.net","open-system","ptp22.com","ptp33.com","adsmogo","rtbads.com","ic-live","intellitxt.com","erasercash.com","suntimes.com","criteo.com","llnwd.net",
				"aug.me","ad6media.fr","perfoweb.fr","cp-proximity","paycount.com","tubemogul.com","hypermart.net",
				"aroundmeapi.com","toppic555","inteletrack.com","gestionpub","bidvertiser","banners-service","agency-siam.ru","servedbyopenx.com",
				"easypush","ovoadv","clickbank.net" ,"adhood","betrad","deskrgas.net","smaato","cibleclick","madvertise","fmpub","plugrush","madnet.ru","client-privilege.com",
				"jumpsavehuge.com","criteo.com","mopub.com","amazon-adsystem.com","comclick.com","iadctest.qwapi.com","heyzap.com","adzerk.net","skimresources.com","weborama.fr","googlesyndication.com","affilizr.com","adzerk.net","scorecardresearch.com","doubleclick.net","googleadservices.com","syn-api.com","nativeads.de","monetizheat.com","adxcore.com","tadssrv.info","doubleclick.net","advertising.com","admaster.com.cn","savills-vx.com","promoz.com.ua","exhost1.me","exhost2.me","promoz.com","11clicks.de","alclick.com","bases-prisma.com","luxup.ru","promolike.com","mydas.mobi","affiliatedupdate.com","eloqua.com","nuggad.net","displayadfeed.com","officeeoeo.com","networksad.com","mobeo.eu","googlsyndications.com","adlux.com","adpub.eu","addmob.info","ocito.com","paneaserver.net","adzmedia","cdndm5.com","wen9.net","frr.com","wvproxy.com","paristream.com","croiseo.com","croiseo.fr","rmmads.com","adsofts.cn","placedesleads.com","gamigoads.com","mobadsrv.com","graphidom972.fr","luxup.ru","prisasd.com","bbelements.com","ad-saver.com","otooxo","clickinvideo","oneclickad","adsmarket","wem.fr","nm17.fr","publimaestro","pubmatic","promo-onweb.com","network-media","network-medianet","mediagra.com",'ivwbox.de',"pubmatic.com","movad.de",
		);
		

		
		
		if(!$cleanedkey){return $array;}
		while (list ($index, $www) = each ($array) ){
			if(strpos(" $www", ".")==0){continue;}
			if(strpos(" $www", "*")>0){continue;}
			$XRETURN[$www]=true;
		}
		
		return $XRETURN;
	}
	
	
}
