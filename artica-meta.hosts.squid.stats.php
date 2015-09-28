<?php
include_once('ressources/class.templates.inc');
include_once('ressources/class.mysql-meta.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.system.nics.inc');
include_once('ressources/class.meta_uuid.inc');


$users=new usersMenus();
if(!$users->AsArticaMetaAdmin){
	$tpl=new templates();
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die();

}

if(isset($_GET["tabs"])){tabs();exit;}

js();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$q=new mysql_meta();
	$title=$tpl->_ENGINE_parse_body("{SQUID_STATS1}: ".$q->uuid_to_host($_GET["uuid"]));
	$html="YahooWin4('990','$page?tabs=yes&uuid={$_GET["uuid"]}','$title');";
	echo $html;
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$q=new mysql_uuid_meta($_GET["uuid"]);
	$CurrentTable="squid_hourly_".date("YmdH");
	$CurrentDay="squid_daily_".date("Ymd");
	$CurrentMonth="squid_monthly_".date("Ym");
	
	if($q->TABLE_EXISTS($CurrentTable)){
		$array["RTT"]="{requests}: {this_hour}";
		
	}
	if($q->TABLE_EXISTS($CurrentDay)){
		$array["RTD"]="{requests}: {this_day}";
	
	}	
	if($q->TABLE_EXISTS($CurrentMonth)){
		$array["RTM"]="{requests}: {this_month}";
	
	}
	$textsize="22px";
	
	if(count($array)==0){
		echo FATAL_ERROR_SHOW_128("No statistics can be extracted<br>$CurrentTable<br>$CurrentDay");
		return;
		
	}
	

	$t=time();
	while (list ($num, $ligne) = each ($array) ){



		if($num=="RTT"){
			$link="artica-meta.hosts.squid.stats.hour.php?uuid={$_GET["uuid"]}";
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:$textsize'><a href=\"$link\"><span>$ligne</span></a></li>\n");
			continue;
		}
		if($num=="RTD"){
			$link="artica-meta.hosts.squid.stats.day.php?uuid={$_GET["uuid"]}";
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:$textsize'><a href=\"$link\"><span>$ligne</span></a></li>\n");
			continue;
		}
		if($num=="RTM"){
			$link="artica-meta.hosts.squid.stats.day.php?uuid={$_GET["uuid"]}&month=yes";
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:$textsize'><a href=\"$link\"><span>$ligne</span></a></li>\n");
			continue;
		}		

		if($num=="node-infos-RULES"){
			//$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:$textsize'><a href=\"squid.nodes.accessrules.php?MAC={$_GET["MAC"]}&ipaddr={$_GET["ipaddr"]}\"><span>$ligne</span></a></li>\n");
			continue;
		}


		$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:$textsize'><a href=\"$page?$num=yes&MAC={$_GET["MAC"]}&ipaddr={$_GET["ipaddr"]}\"><span>$ligne</span></a></li>\n");
	}


	echo build_artica_tabs($html, "squid{$_GET["uuid"]}");



}