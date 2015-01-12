<?php

class array_category_industry{
	
	
	
	function return_array($cleanedkey=false){
		$array=array("jlg","mindvalley","bunchball","ccmbenchmark","edf.com","adenweb.com","fastcompany","chat2deal",
		"nachtmann","whopper","burgerking","burgerkingfrance","pagespro-orange.fr",
		"schneider-electric.com","sony-europe.com","afacerist","hubb","telenav","choosit","lyonnaise-des-eaux","adp","cat",
		"danfoss","batiactu","schunk","advertory","fe-metal.com","konecranes.com","coop.br","exleam.fr",
		"clariant.com","bnlparts.nl","samsonite","madeira.","cristal.com","allomarie.fr","personal-wissen.net",
		"etiuslugi.ru","schneider","allyes.com","yingtong-ltd.com","dyxly.com",
				"yammer.com","kompass.com","o2.fr","prom.ua","datazug.ch","made-in-china.com","198ic.com","gesep.com","notaires.fr","nswtc.com","nxgln.com","av24h.com","gmzhw.com","samsung","datazug","surinternet","sandhills",
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
