<?php
	include_once(dirname(__FILE__) . '/ressources/class.templates.inc');
	include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
	include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
	include_once(dirname(__FILE__) . '/ressources/class.artica.inc');
	include_once(dirname(__FILE__) . '/ressources/class.rtmm.tools.inc');
	include_once(dirname(__FILE__) . '/ressources/class.squid.inc');
	include_once(dirname(__FILE__) . '/ressources/class.dansguardian.inc');
	include_once(dirname(__FILE__) . '/ressources/class.ccurl.inc');
	include_once(dirname(__FILE__) . '/framework/class.unix.inc');
	include_once(dirname(__FILE__) . '/framework/frame.class.inc');	
	include_once(dirname(__FILE__) . "/ressources/class.categorize.externals.inc");
	
	
	if($argv[1]="--scan"){GetCategory($argv[2],$argv[3]);exit;}
	
	
$array["casinos"]="gamble";
$array["chaines-tv-internet"]="webtv";
$array["telecharger-films"]="movies";
$array["arnaque-escroquerie-par-email"]="mailing";
$array["escroquerie-faux-anti-virus"]="malware";
$array["tabac"]="tobacco";
$array["contrefacon"]="suspicious";


	
function GetCategory($source,$dest){
	
	
	for($i=1;$i<13;$i++){	
		echo "Checking page number $i\n";
		$curl=new ccurl("http://cacaweb.com/category/$source/page/$i");
		$curl->NoHTTP_POST=true;
		$curl->UserAgent="Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:10.0) Gecko/20100101 Firefox/10.0";
		$curl->FollowLocation=true;
		if(!$curl->get()){echo $curl->error."\n";return;}
		CheckWebsites($curl->data,$dest);
	}


}






function CheckWebsites($data,$category){


	$founds=array();
	if(!preg_match_all("#Domain:(.*?)<#s",$data,$founds)){echo "NO MATCH scan 1.....\n";}
	$count=count($founds[1]);
	if($count==0){
		if(!preg_match_all("#Name:(.*?)<#s",$data,$founds)){echo "NO MATCH scan 2.....\n";}
	}
	
	
	$count=count($founds[1]);
	if($count==0){return;}
	echo strlen($curl->data)." bytes lenth...found: $count items\n";
	$q=new mysql_squid_builder();
		if($count>0){
			while (list ($id, $www) = each ($founds[1]) ){
				$www=trim($www);
				$www=str_replace("\t", "", $www);
				$www=str_replace(chr(194),"",$www);
				$www=str_replace(chr(32),"",$www);
				$www=str_replace(chr(160),"",$www);		
				if(!preg_match("#([a-z0-9\-_\.]+)\.([a-z]+)$#i",$www,$re)){echo "$www skiped L.".__LINE__."\n";continue;}
				if(strpos($www, ",")>0){;echo "$www skiped L.".__LINE__."\n";continue;}
				if(strpos($www, " ")>0){echo "$www skiped L.".__LINE__."\n";continue;}
				if(strpos($www, ":")>0){echo "$www skiped L.".__LINE__."\n";continue;}
				if(strpos($www, "%")>0){echo "$www skiped L.".__LINE__."\n";continue;}	
				if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}
				$articacats=trim($q->GET_CATEGORIES($www,true,true));
				if($articacats<>null){echo "\"$www\" already in $articacats\n";}
				$newsWeb[]=$www;
				
			}
		}

	while (list ($id, $www) = each ($newsWeb) ){
		echo "Adding \"$www\" -> $category\n";
		
		$q->ADD_CATEGORYZED_WEBSITE($www, $category);
	}

}


?>