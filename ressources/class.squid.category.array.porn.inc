<?php

class array_category_porn{
	
	
	
	function return_array($cleanedkey=false){
		$array=array('ruerotic',"xxx4u","sopital","pornblogspace.com","videoxstars.com","safeimageserver.com","24video.net","publicsexchat","nageoire.com","hardsextube","red8porn","xneu","fux.com","hidrvids","freepornvs","adultgamecity","youjizz","livesexbook","sex-zone","adnlt.com");
		$MAIN_PORN["mylinea.com"]=true;
		$MAIN_PORN["sexpeb.pl"]=true;
		$MAIN_PORN["seksotuba.com"]=True;
		$MAIN_PORN["kekette.com"]=true;
		$MAIN_PORN["fondosdepantalla.com.es"]=true;
		$MAIN_PORN["sexiporno.pl"]=true;
		$MAIN_PORN["ostresexfilmy.pl"]=true;
		$MAIN_PORN["searchmole.co.uk"]=true;
		$MAIN_PORN["go4wild.com"]=true;
		$MAIN_PORN["xprv.com"]=true;
		$MAIN_PORN["zizibi.com"]=true;
		$MAIN_PORN["zolomania.com"]=true;
		$MAIN_PORN["xxxtop.biz"]=true;
		$MAIN_PORN["xvideos.com"]=true;
		$MAIN_PORN["darmoporno.pl"]=true;
		$MAIN_PORN["darmowesexs.pl"]=true;
		$MAIN_PORN["darmoseks.pl"]=true;
		$MAIN_PORN["amatorkifilmysex.pl"]=true;
		$MAIN_PORN["darmowefilmyhd.pl"]=true;
		$MAIN_PORN["pissing-peeing-pee.com"]=true;
		$MAIN_PORN["celebsempire.com"]=true;
		$MAIN_PORN["smut.com"]=true;
		$MAIN_PORN["bfdare.com"]=true;
		$MAIN_PORN["brutaltops.com"]=true;
		$MAIN_PORN["ventedvdx.net"]=true;
		$MAIN_PORN["orgasmatrix.com"]=true;
		$MAIN_PORN["xprv.com"]=true;
		$MAIN_PORN["xhcdn.com"]=true;
		$MAIN_PORN["omzbg.com"]=true;
		$MAIN_PORN["sex-tours.org"]=true;
		$MAIN_PORN["darmoweseksfilmy.pl"]=true;
		$MAIN_PORN["phncdn.com"]=true;
		$MAIN_PORN["sex-hardcore.org"]=true;
		$MAIN_PORN["youporn.com"]=true;
		$MAIN_PORN["www-youporn.com"]=true;
		$MAIN_PORN["pornmovies.tv"]=true;
		$MAIN_PORN["lordsofpornmovies.com"]=true;
		$MAIN_PORN["asianpornmovies.com"]=true;
		$MAIN_PORN["sex.com"]=true;
		$MAIN_PORN["sex.com.br"]=true;
		
		while (list ($www, $cat) = each ($MAIN_PORN) ){
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
