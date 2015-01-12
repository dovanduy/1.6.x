<?php
include_once(dirname(__FILE__)."/class.squid.familysites.inc");
class array_category_smallads{
	
	private function _ext($MAIN,$www){
		$f=new squid_familysite();
		return $f->filldomains($MAIN, $www);
		
		
	}
	
	
	function return_array($cleanedkey=false){
		
	
$MAIN["pretachanger.fr"]=True;
$MAIN["ebay.com"]=True;
$MAIN["olx.pt"]=True;
$MAIN["craigslist.org"]=True;
$MAIN["vonna.com"]=True;
$MAIN["olx.com.ar"]=True;
$MAIN["avito.st"]=True;
$MAIN["olx.cl"]=True;
$MAIN["site-pap.fr"]=True;
$MAIN["olxtunisie.com"]=True;
$MAIN["olx.com.br"]=True;
$MAIN["olx.com.mx"]=True;
$MAIN["ebay.co.uk"]=True;
$MAIN["ebay.it"]=True;
$MAIN["auctions.yahoo.co.jp"]=True;
$MAIN["ebay.com.au"]=True;
$MAIN["ebay.in"]=True;
$MAIN["olx.pl"]=True;
$MAIN["olx.com.my"]=True;
$MAIN["vivastreet.it"]=True;
$MAIN["ebay.es"]=True;
$MAIN["ebay.at"]=True;
$MAIN["sahibinden.com"]=True;
$MAIN["olx.com.ua"]=True;
$MAIN["kijiji.ca"]=True;
$MAIN["ebayclassifieds.com"]=True;
$MAIN["tokobagus.com"]=True;	
$MAIN["olx.co.id"]=True;
$MAIN["annonsera.se"]=True;
$MAIN["kijiji.fr"]=True;
$MAIN["vivanuncios.com.mx"]=True;
$MAIN["ebay.be"]=True;
$MAIN["ebay.com.sg"]=True;
$MAIN["ebay.ca"]=True;
$MAIN["olx.com"]=True;
$MAIN["olx.co.za"]=True;
$MAIN["olx.it"]=True;
$MAIN["olx.ro"]=True;
$MAIN["olx.com.co"]=True;
$MAIN["vivastreet.com.mx"]=True;
$MAIN["sulekha.com"]=True;
$MAIN["olx.hu"]=True;
$MAIN["mes-occasions.com"]=True;
$MAIN["leboncoin.fr"]=True;
$MAIN["marocannonces.com"]=True;
$MAIN["annoncesexpress.fr"]=True;
$MAIN["anunciosgratis.com.ar"]=True;
$MAIN["anunciosgratis.pt"]=True;
$MAIN["annuncifacile.it"]=True;
$MAIN["annoncesjaunes.fr"]=True;
$MAIN["paruvendu.com"]=True;
$MAIN["anunciosx.com.br"]=True;
$MAIN["annonces-legales.fr"]=True;
$MAIN=$this->_ext($MAIN,"slando");
$MAIN=$this->_ext($MAIN,"ebay");
$MAIN=$this->_ext($MAIN,"vivastreet");
$MAIN=$this->_ext($MAIN,"olx");
$MAIN=$this->_ext($MAIN,"kijiji");
$MAIN=$this->_ext($MAIN,"craigslist");



$MAIN["annuncicdn.it"]=true;

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
