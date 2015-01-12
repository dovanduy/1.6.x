<?php
include_once(dirname(__FILE__)."/class.squid.familysites.inc");
class array_category_dictionaries{
	
	private function _ext($MAIN,$www){
		$f=new squid_familysite();
		return $f->filldomains($MAIN, $www);
		
		
	}
	
	
	function return_array($cleanedkey=false){
		
		
		
		$MAIN["wikia.com"]=True;
		$MAIN["wikispaces.com"]=True;
		$MAIN["about.com"]=True;
		$MAIN["custhelp.com"]=True;
		$MAIN["comprendrechoisir.com"]=True;
		$MAIN["oxfordjournals.org"]=True;
		$MAIN["wikipedia.org"]=True;
		$MAIN["jusbrasil.com.br"]=True;
		$MAIN["wikidot.com"]=True;
		$MAIN["stadtbranchenbuch.com"]=True;
		$MAIN["answers.com"]=True;
		$MAIN["academic.ru"]=True;
		$MAIN["wikimedia.org"]=True;
		$MAIN["wikispaces.net"]=True;
		$MAIN["dict.cc"]=True;
		$MAIN["toolserver.org"]=True;
		$MAIN["reference.com"]=True;
		$MAIN["thefreedictionary.com"]=True;
		$MAIN["tpub.com"]=True;
		$MAIN["dictionary.com"]=True;
		$MAIN["wiley.com"]=True;
		$MAIN["docstoc.com"]=True;
		$MAIN["wikiquote.org"]=True;
		$MAIN["wiktionary.org"]=True;
		$MAIN["dowjones.com"]=True;
		$MAIN["wikisource.org"]=True;
		$MAIN["nocookie.net"]=True;
		$MAIN["yourdictionary.com"]=True;
		$MAIN["mapquest.com"]=True;
		$MAIN["wisegeek.com"]=True;
		$MAIN["abes.fr"]=True;
		$MAIN["svp.com"]=True;
		$MAIN["ebscohost.com"]=True;
		
		
		
		$array=array("thefreedictionary","jusbrasil.com.br","tandfonline.com","ebscohost.com","metapress.com","yourdictionary","turnikpedia","hudong.com","dbpedia.org","oxfordjournals","about","abes.fr","theses.fr","wikipedia","wikia","docstoc","wikidot","wikispaces","wikimedia","wikiquote","wikisource");
		
		
		
		
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
