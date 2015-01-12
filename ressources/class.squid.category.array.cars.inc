<?php
include_once(dirname(__FILE__)."/class.squid.familysites.inc");
class array_category_cars{
	
	private function _ext($MAIN,$www){
		$f=new squid_familysite();
		return $f->filldomains($MAIN, $www);
		
		
	}
	
	
	function return_array($cleanedkey=false){
		
		$MAIN["auto-occasion.fr"]=true;
		$MAIN["autoindustries.com"]=true;
		$MAIN["drom.ru"]=true;
		$MAIN["trophee-roses-des-sables.org"]=True;
		$MAIN["infocar.ua"]=True;
		$MAIN["nf500.com"]=True;
		$MAIN["sohu.com"]=True;
		$MAIN["auto-f.fr"]=True;
		$MAIN["auto.ru"]=True;
		$MAIN["supersitio.net"]=True;
		$MAIN["czechmat.com"]=True;
		$MAIN["ford.com"]=True;
		$MAIN["mercedes-benz.com"]=True;
		$MAIN["autoportal.ua"]=True;
		$MAIN["vrum.com.br"]=True;
		$MAIN["bitauto.com"]=True;
		$MAIN["toyota.co.uk"]=True;
		$MAIN["peugeot.com"]=True;
		$MAIN["seat.de"]=True;
		$MAIN["ebizautos.com"]=True;
		$MAIN["securitest.fr"]=True;
		$MAIN["mercedes.fr"]=True;
		$MAIN["auto26.com"]=True;
		$MAIN["daimler.com"]=True;
		$MAIN["coches.net"]=True;
		$MAIN["bmw.com"]=True;
		$MAIN["autotrader.co.uk"]=True;
		$MAIN["renault.fr"]=True;
		
		$MAIN["peugeot.fr"]=True;
		$MAIN["auto.pl"]=True;
		$MAIN["autoplaza.com.mx"]=True;
		$MAIN["cn2che.com"]=True;
		$MAIN["auto.cz"]=True;
		$MAIN["autoteam.pl"]=True;
		$MAIN["bmw.de"]=True;
		$MAIN["pcauto.com.cn"]=True;
		$MAIN["sytjjt.com"]=True;
		$MAIN["autohome.com.cn"]=True;
		$MAIN["psa-peugeot-citroen.com"]=True;
		$MAIN["dealer.com"]=True;
		$MAIN=$this->_ext($MAIN,"volkswagen");
		
		$array=array("rdw","otomoto","automotive","ebizautos","cooldrive","feuvert","stemco","texasdirectauto","ironhorse","opelreseau",
				"mercedes-benz","webmotors","renault","digitaldealer.com.au","auto-occasion","codeclic.com","renault-egypt","carrental","myrenault","hertz","audi","avis","europcar","auto","autotrader","car.gr","carcdn.gr",
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
