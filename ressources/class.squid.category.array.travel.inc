<?php

class array_category_travel{
	
	
function return_array($cleanedkey=false){
		
	$MAIN["bstatic.com"]=true;
	
	$europ["eu"]=true;
	$europ["pl"]=true;
	$europ["es"]=true;
	$europ["be"]=true;
	$europ["fr"]=true;
	$europ["it"]=true;
	$europ["de"]=true;
	$europ["ch"]=true;
	$europ["co.uk"]=true;
	$europ["com"]=true;
	
	while (list ($www, $cat) = each ($europ) ){
		$MAIN["voyages-sncf.".$www]=true;
		$MAIN["expedia.".$www]=true;
		$MAIN["voyage-prive.".$www]=true;
	}
	
	

$array=array("secure-reservation","expedia","chatel","trivago","stationverte.com","isango","aeroport","hotels","ihg",
		"makemytrip","opodo","cityway","airfrance","gho","hoteles","ihacom","tourinsoft","geo-web-mail","geo"
		,"service-voyages.com","leyu365.com","partirpascher","aeromexico.com","ruskompas","cote.azur.fr","absoluthongkong.com","reztripmobile.com","top-hotely.cz","japanrailpassmap.com","faire-savoir.com","theworldrace.org","clemetparks.com","clevelandmetroparks.com","hostelsystem","croisitour.com","novotel.com","jedecouvrelafrance.com","resawin","hotels-de-charme","campings","michelin.fr","trivago","audierne.oscim.org","viesumajas.lv","zafirotours");

		
	
		
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
