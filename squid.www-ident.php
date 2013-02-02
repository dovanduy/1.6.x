<?php

	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["title_array"]["size"]="{downloaded_flow}";
	$GLOBALS["title_array"]["req"]="{requests}";	
	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	include_once('ressources/class.rtmm.tools.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die();}
	if(isset($_GET["startpoint"])){popup();exit;}
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["thumbnail"])){GetThumbs();die();}
	if(isset($_GET["rethumbnail"])){rethumbnail();die();}
	page();
	
function page(){
	$page=CurrentPageName();
	$md=md5($_GET["www"]);
	$html="<div id='startpoint-$md'></div>
	<script>LoadAjax('startpoint-$md','$page?startpoint=yes&www={$_GET["www"]}&xtime={$_GET["xtime"]}&week={$_GET["week"]}&year={$_GET["year"]}&month={$_GET["month"]}');</script>
	";
	echo $html;
	
}

function popup(){
	$www=$_GET["www"];
	$md=md5($www);
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM visited_sites WHERE sitename='$www'"));
	$time=time();
	$year=$_GET["year"];
	if(!is_numeric($year)){$year=date('Y');}
	if(is_numeric($_GET["week"])){
		$tablename="$year{$_GET["week"]}_week";
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(hits) as HitsNumber,SUM(size) as Querysize FROM $tablename WHERE sitename='$www'"));
		if(!$q->ok){echo $q->mysql_error;}
	}
	
	
	$requests=numberFormat($ligne["HitsNumber"],0,""," ");
	$totalsize=FormatBytes($ligne["Querysize"]/1024);
	$country=$ligne["country"];
	if($country<>null){$img_country=GetFlags($country);}else{$img_country="flags/name.png";}
	
	$html="
	<table style='width:100%;margin-bottom:10px'>
	<tr>
		<td style='width:0.5%'><img src='img/$img_country'></td>
	<td>
		<div style='font-size:18px' class=legend>$www ($country)</div>
	</td>
	</tr>
	</table>
	
	
	
	<table style='width:99%' class=form>
	<tr>
		<td width=400px valign='top'>
			<div id='thumbs-$md' class=BodyContent>".
				RoundedLightWhite("<img src='squid.statistics.php?thumbnail=$www&t=$time' class='rounded'>")."
				</div>
			
			</div>
			
		</td>
		<td valign='top'>
		<div  class=BodyContent>
			<table style='width:100%'>
			<tr>
				<td class=legend style='font-size:14px'>{requests}:</td>
				<td style='font-size:14px;font-weight:bold'>$requests</td>
			</tr>
			<tr>
				<td class=legend style='font-size:14px'>{size}:</td>
				<td style='font-size:14px;font-weight:bold'>$totalsize</td>
			</tr>	
			<tr>
				<td class=legend style='font-size:14px' valign='top'>{categories}:</td>
				<td style='font-size:14px;font-weight:bold'><div id='catz-$md'></div></td>
			</tr>					
			</table>
			<div id='siteinfos-$md'></div>
		</div>
		</td>
	</tr>
	</table>
	
	
	
	
	<script>
		function RefreshAllSiteZoom(){
			CornPictures();
			LoadAjaxTiny('catz-$md','squid.search.statistics.php?search-stats-categories=$www');
			LoadAjaxTiny('siteinfos-$md','squid.search.statistics.php?site-infos=$www&idcallback=siteinfos-$md&disposition=1column&gen-thumbnail=yes');
			}
		
		function GenerateThumbs(){
			LoadAjax('siteinfos-$md','$page?thumbnail=$www');
		
		}
		
		function ReGenerateThumbs(){
			LoadAjax('siteinfos-$md','$page?rethumbnail=$www');
		}
		
		GenerateThumbs();
		
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function GetThumbs(){
	echo "<script>RefreshAllSiteZoom()</script>";
	
}
function rethumbnail(){
	$sock=new sockets();
	$page=CurrentPageName();
	$md=md5($_GET["rethumbnail"]);
	$sock->getFrameWork("squid.php?rethumbnail={$_GET["rethumbnail"]}");
	echo "<script>LoadAjax('startpoint-$md','$page?startpoint=yes&www={$_GET["rethumbnail"]}');</script>";
	
}