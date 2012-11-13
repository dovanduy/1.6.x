<?php
class squid_catz_games{
	
	function parse($www){
		if(strpos($www, ".rockyou.com")>0){return "games";}	
		if(strpos($www, ".playtomic.com")>0){return "games";}	
		if(strpos($www, "addictinggames.com")>0){return "games";}
		if(strpos($www, "gamespot.com")>0){return "games";}
		if(strpos($www, ".videogamingmonster.com")>0){return "games";}
		if(preg_match("#^gameplay\.#", $www)){return "games";}
		if(preg_match("#games.[a-z]+#", $www)){return "games";}
		if(preg_match("#^games\.#", $www)){return "games";}
		if(preg_match("#gamedownload4u\.com$#", $www)){return "games";}
		if(strpos($www, "playstation")>0){return "games";}
		if(strpos($www, "xbox360")>0){return "games";}
		if(strpos($www, "freeonlinegames.")>0){return "games";}
		if(strpos($www, "gameplaylabs.")>0){return "games";}	
		if(strpos($www, ".battlefield.com")>0){return "games";}
		if(strpos($www, ".grepolis.com")>0){return "games";}
		if(strpos($www, ".bubblebox.com")>0){return "games";}
		if(strpos($www, ".allsoluces.com")>0){return "games";}
		if(preg_match("#\.ea\.com$#", $www)){return "games";}
		if(preg_match("#\.farmville\.com$#", $www)){return "games";}
		if(preg_match("#\.rivalgaming\.com$#", $www)){return "games";}
		if(preg_match("#\.gangofgamers\.com$#", $www)){return "games";}
		if(preg_match("#\.yepi-girl\.com$#", $www)){return "games";}
		if(preg_match("#\.travian\..+#", $www)){return "games";}
		if(preg_match("#\.ujmappa\.hu$#", $www)){return "games";}
		if(preg_match("#\.digitalchocolate\.com$#", $www)){return "games";}
		if(preg_match("#\.friv4\.us$#", $www)){return "games";}
		if(preg_match("#\.geewa\.ws$#", $www)){return "games";}
		if(preg_match("#\.geewa\.com$#", $www)){return "games";}
		if(preg_match("#\.xbox\.com$#", $www)){return "games";}
		if(preg_match("#\.arkadium\.com$#", $www)){return "games";}
		if(preg_match("#\.nexon\.net$#", $www)){return "games";}
		if(preg_match("#\.6waves\.com$#", $www)){return "games";}
		if(preg_match("#\.playtika\.com$#", $www)){return "games";}
		if(preg_match("#\.wooga\.com$#", $www)){return "games";}
		if(preg_match("#\.socialgamenet\.com$#", $www)){return "games";}
		if(preg_match("#\.playrix\.com$#", $www)){return "games";}
		if(preg_match("#\.gameflycdn\.com$#", $www)){return "games";}
		if(preg_match("#\.logic-sunrise\.com$#", $www)){return "games";}
		if(preg_match("#\.flash-gear\.com$#", $www)){return "games";}
		if(preg_match("#\.atlanticpayments\.com$#", $www)){return "games";}
		if(preg_match("#\.koramgame\.com$#", $www)){return "games";}
		if(preg_match("#\.game\.[0-9]+\.com$#", $www)){return "games";}
		if(preg_match("#\.farmville\.zgncdn\.com$#", $www)){return "games";}
		if(preg_match("#\.ubi.com$#", $www)){return "games";}
		if(preg_match("#\.alaplaya-assets\.net$#", $www)){return "games";}
		if(preg_match("#\.alaplaya\.net$#", $www)){return "games";}
		if(preg_match("#\.imvu\.com$#", $www)){return "games";}
		if(preg_match("#\.neoseeker\.[a-z]+$#", $www)){return "games";}
		if(preg_match("#\.wonaruto\.com$#", $www)){return "games";}
		if(preg_match("#\.greentube\.com$#", $www)){return "games";}
		if(preg_match("#\.judgehype\.com$#", $www)){return "games";}
		if(preg_match("#\.gamevil\.com$#", $www)){return "games";}
		if(preg_match("#\.bpcdn\.net$#", $www)){return "games";}
		if(preg_match("#\.crowdstar\.com$#", $www)){return "games";}
		if(preg_match("#(game|jeux).*?\.(blogspot|altervista).*?\.[a-z]+$#", $www)>0){return "games";}
		if(preg_match("#\.gametrailers\.com$#", $www)){return "games";}
		if(preg_match("#\.prizee\.com$#", $www)){return "games";}
		if(preg_match("#\.raptrcdn\.com$#", $www)){return "games";}
		if(preg_match("#\.ala\.bz$#", $www)){return "games";}
		if(preg_match("#\.zgncdn\.com$#", $www)){return "games";}
		if(preg_match("#\.zynga\.com$#", $www)){return "games";}
		if(preg_match("#\.koreus\.com$#", $www)){return "games";}
		if(preg_match("#\.supercarburant\.fr$#", $www)){return "games";}
		if(preg_match("#\.webkinz\.com$#", $www)){return "games";}
		if(preg_match("#\.gamepost\.com$#", $www)){return "games";}
		if(preg_match("#\.notdoppler\.com$#", $www)){return "games";}
		if(preg_match("#\.storm8\.com$#", $www)){return "games";}
	}
	
}