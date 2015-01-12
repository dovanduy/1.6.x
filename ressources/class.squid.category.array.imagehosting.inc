<?php

class array_category_imagehosting{
	
	
	
	function return_array($cleanedkey=false){
		
		
		$MAIN["photobucket.com"]=True;
		$MAIN["imageshack.us"]=True;
		$MAIN["instagram.com"]=True;
		$MAIN["iofferphoto.com"]=True;
		$MAIN["smugmug.com"]=True;
		$MAIN["photo.store.qq.com"]=True;
		$MAIN["photofile.ru"]=True;
		$MAIN["images.1c-bitrix-cdn.ru"]=True;
		$MAIN["chomikuj.pl"]=True;
		$MAIN["exs.cx"]=True;
		$MAIN["imagebam.com"]=True;
		$MAIN["foto.radikal.ru"]=True;
		$MAIN["imagenar.com"]=True;
		$MAIN["photo.163.com"]=True;
		$MAIN["img.vnecdn.net"]=True;
		$MAIN["126.net"]=True;
		$MAIN["itc.cn"]=True;
		$MAIN["imgsmail.ru"]=True;
		$MAIN["imageg.net"]=True;
		$MAIN["sinaimg.cn"]=True;
		$MAIN["imagerix.com"]=True;
		$MAIN["postimage.org"]=True;
		$MAIN["bdimg.com"]=True;
		$MAIN["woopic.com"]=True;
		$MAIN["xooimage.com"]=True;
		$MAIN["imguol.com"]=True;
		$MAIN["flogbrasil.terra.com.br"]=True;
		$MAIN["imageporter.com"]=True;
		$MAIN["img.gostorego.com"]=True;
		$MAIN["static-file.com"]=True;
		$MAIN["picdn.net"]=True;
		$MAIN["fastpic.ru"]=True;
		$MAIN["thumbs.redditmedia.com"]=True;
		$MAIN["casimages.com"]=True;
		$MAIN["jc9.ru"]=True;
		$MAIN["imgur.com"]=True;
		$MAIN["yimg.jp"]=True;
		$MAIN["yimg.com"]=True;
		$MAIN["radikal.ru"]=True;
		
		$MAIN["qhimg.com"]=True;
		$MAIN["pinimg.com"]=True;
		
		$array=array("bababian.com","servimg.com","imgur.com",
				"nmimg.org","bousedezebu.com","frz.io","imgwykop.pl",
				"picturepush.com","imagevenue.com","mzsimg.com","web-album.org",
				"t26","tinypic.com","stylesfire.com",
				"fotolog.com");
				

		
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
