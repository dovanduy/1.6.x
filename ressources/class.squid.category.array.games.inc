<?php

class array_category_games{
	
	
	
	function return_array($cleanedkey=false){
		$array=array("jeux-ios","majorleaguegaming","horizoncraft","wartune.com","doctorgame.it","infernogame.ru","unity3d.com","gaijinent","steampowered","101xp","gamer-info","gamefaqs","king.com",
				"gamingfacts","midasplayer","king","happyoz","playstation.net","boardgamearena","didi*games","y8freegames","kaisergames",
				"playdidi","fupa.com","la.net.ua","battle.net","playtomic.com","np75.com","j784.com","hb32.com","g131.com","kzgww.com",
				"torn.com","3d-game.com","girly.im","cttzb.com","playstation.com","ubi.com","game.tw","jeuxvideo.com",
				 "kaococo.com","playgem.co","defplay.com","machine9.net","get4mobile.net","eamythic.net","wowraider",
				 "shopto.net","playercom.ru","sg-static","microjogos","gamedonga.co.kr","wow-europe.com","blizzard.com",
				"steampowered.com","asmodee.com","forum2jeux.com","scoreloop.com","cpugamerz.com","jeuxvideo.com","himediads.com","wordofgame.com","servegame.com","playstation.net","play-asia.com","gameloft.com","ps4france.com","gameforge.com","zynga.com","rockstargames.com");
		

		
		
		if(!$cleanedkey){return $array;}
		while (list ($index, $www) = each ($array) ){
			if(strpos(" $www", ".")==0){continue;}
			if(strpos(" $www", "*")>0){continue;}
			$XRETURN[$www]=true;
		}
		
		return $XRETURN;
	}
	
	
}
