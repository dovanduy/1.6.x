<?php
include_once(dirname(__FILE__)."/class.squid.familysites.inc");
class array_category_pictureslib{
	
	private function _ext($MAIN,$www){
		$f=new squid_familysite();
		return $f->filldomains($MAIN, $www);
		
		
	}
	
	
	function return_array($cleanedkey=false){
		
		
		$array=array("mycanvas.com","ggpht.com","gamewallpapers.com","cargocollective.com",
				"viewbook.com","portfoliobox.fr");
		$MAIN["deviantart.com"]=True;
		$MAIN["hotlinkimage.com"]=True;
		$MAIN["gulfup.com"]=True;
		$MAIN["picturepush.com"]=True;
		$MAIN["canstockphoto.com"]=True;
		$MAIN["istockphoto.com"]=True;
		$MAIN["imagehaven.net"]=True;
		$MAIN["fotolia.com"]=True;
		$MAIN["cargocollective.com"]=True;
		$MAIN["pbsrc.com"]=True;
		$MAIN["wallpapersus.com"]=True;
		$MAIN["photoshelter.com"]=True;
		$MAIN["img-up.net"]=True;
		$MAIN["fotosik.pl"]=True;
		$MAIN["photofile.ru"]=True;
		$MAIN["photosight.ru"]=True;
		$MAIN["scribol.com"]=True;
		$MAIN["picoodle.com"]=True;
		$MAIN["imagebam.com"]=True;
		$MAIN["picsart.com"]=True;
		$MAIN["fastpic.ru"]=True;
		$MAIN["photozou.jp"]=True;
		$MAIN["35photo.ru"]=True;
		$MAIN["kalooga.com"]=True;
		$MAIN["snimka.bg"]=True;
		$MAIN["kanimg.com"]=True;
		$MAIN["shutterstock.com"]=True;
		
		$MAIN=$this->_ext($MAIN, "photoshelter");
		$MAIN=$this->_ext($MAIN, "fliphotos");
		
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
