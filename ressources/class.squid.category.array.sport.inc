<?php

class array_category_sport{
	
	
	function return_array($cleanedkey=false){
	$MAIN["fifaworldcup.yahoo.com"]=true;
	$MAIN["sports.sp2.yahoo.com"]=true;
	$MAIN["sports.sp1.yahoo.com"]=true;
	$MAIN["sports.sp3.yahoo.com"]=true;
	$MAIN["sports.sp4.yahoo.com"]=true;
	$MAIN["eurosport.yahoo.com"]=true;
	$MAIN["sports.ac1.yahoo.com"]=true;
	$MAIN["sports.ac2.yahoo.com"]=true;
	$MAIN["sports.ac3.yahoo.com"]=true;
	$MAIN["sports.ac4.yahoo.com"]=true;
	$MAIN["leclubdefoot.com"]=true;
	$MAIN["nfl.com"]=true;
	$MAIN["clubeo.com"]=true;
	$MAIN["fantasysports.yahoo.com"]=true;
	$MAIN["sports.yahoo.com"]=true;
	$MAIN["intheteam.com"]=true;
	$MAIN["cstv.com"]=true;
	$MAIN["collegesports.com"]=true;
	$MAIN["play-cricket.com"]=true;
	$MAIN["footeo.com"]=true;
	$MAIN["fff.fr"]=true;
	$MAIN["eurosport.com"]=true;
	$MAIN["football.fr"]=true;
	$MAIN["sportsregions.fr"]=true;
	$MAIN["uefa.com"]=true;
	$MAIN["meltyxtrem.fr"]=true;
	$MAIN["nhl.com"]=true;
	$MAIN["gazzetta.it"]=true;
	$MAIN["futbolowo.pl"]=true;
	$MAIN["stats.com"]=True;
	$MAIN["franceolympique.com"]=True;
	$MAIN["mlb.com"]=True;
	$MAIN["fifa.com"]=True;
	$MAIN["athle.com"]=True;
	$MAIN["rivals.com"]=True;
	$MAIN["sportowefakty.pl"]=True;
	$MAIN["runnerspace.com"]=True;
	$MAIN["newschoolers.com"]=True;
	$array=array("sofoot","lesjoueurs.com","msw.ms","foot-us.fr","ayosport.com");
	
		
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
