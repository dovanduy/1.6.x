<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die();}
	
	if(isset($_GET["cache-params-js"])){cache_params_js();exit;}
	if(isset($_GET["cache-params-popup"])){cache_params_popup();exit;}
	
	if(isset($_GET["white-params-js"])){white_params_js();exit;}
	if(isset($_GET["white-params-popup"])){white_params_popup();exit;}
	if(isset($_POST["white-sitename"])){white_params_save();exit;}
	
	
	if(isset($_POST["DELETE"])){cache_params_delete();exit;}
	if(isset($_POST["MAX_AGE"])){cache_params_save();exit;}
page();	

function cache_params_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{cache_parameters}::{$_GET["sitename"]}");
	$html="YahooWin5('550','$page?cache-params-popup=yes&sitename={$_GET["sitename"]}&t={$_GET["t"]}&table-t={$_GET["table-t"]}&TasksCallBacks={$_GET["TasksCallBacks"]}','$title')";
	echo $html;	
	
}

function white_params_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{white_wwww}::{$_GET["sitename"]}");
	$html="YahooWin5('480','$page?white-params-popup=yes&sitename={$_GET["sitename"]}&t={$_GET["t"]}&table-t={$_GET["table-t"]}&TasksCallBacks={$_GET["TasksCallBacks"]}','$title')";
	echo $html;		
	
}

function white_params_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	if(preg_match("#^www\.(.+)#", $_GET["sitename"],$re)){$_GET["sitename"]=$re[1];}
	$_GET["sitename"]=trim(strtolower($_GET["sitename"]));	
	$enabled=0;
	$sql="SELECT pattern FROM `webfilters_blkwhlts` WHERE PatternType=0 and blockType=2 AND pattern='{$_GET["sitename"]}'";
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if($ligne["pattern"]<>null){$enabled=1;}
	$text="{howto_www_white}";
	$p=Paragraphe_switch_img("{white_wwww}::{$_GET["sitename"]}", $text,"whitewww",$enabled,null,450);
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	if($_GET["TasksCallBacks"]<>null){$TasksCallBacks="{$_GET["TasksCallBacks"]}();";}
	
	$html="<div style='width:95%' class=form>
	$p
	<div style='text-align:right'><hr>". button("{apply}","Savewww$t()",16)."</div>
	<script>
		var X_Savewww$t= function (obj) {
			var tableT='{$_GET["table-t"]}';
			var results=obj.responseText;
			if(results.length>3){alert(results);}
			if(document.getElementById('animate-$t')){document.getElementById('animate-$t').innerHTML='';}
			if(tableT.length>0){RechargeTableauDesSitesCaches();}
			YahooWin5Hide();
			$TasksCallBacks
			}
	
	
		function Savewww$t(){
			var XHR = new XHRConnection();
			XHR.appendData('white-sitename','{$_GET["sitename"]}');
			XHR.appendData('value',document.getElementById('whitewww').value);
			AnimateDiv('animate-$t');
			XHR.sendAndLoad('$page', 'POST',X_Savewww$t);
			
		}
	</script>";
	
	
	echo $tpl->_ENGINE_parse_body($html);
}
function white_params_save(){
	$q=new mysql_squid_builder();
	if($_POST["value"]==0){
		$q->QUERY_SQL("DELETE FROM `webfilters_blkwhlts` WHERE PatternType=0 and blockType=2 AND pattern='{$_POST["white-sitename"]}'");
	}else{
		$sql="INSERT IGNORE INTO webfilters_blkwhlts (description,enabled,PatternType,blockType,pattern) VALUES('{white_wwww}::{$_POST["white-sitename"]}',1,0,2,'{$_POST["white-sitename"]}')";
		$q->QUERY_SQL($sql);
	}
	
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?squid-rebuild=yes");	
}

function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$sitename=$q->GetFamilySites($_GET["sitename"]);
	$sql="SELECT * FROM websites_caches_params WHERE sitename='$sitename'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if($ligne["sitename"]<>null){$enabled="&nbsp;<span style='font-size:10px'>[{enabled}]</span>";}	
	
	$sql="SELECT pattern FROM `webfilters_blkwhlts` WHERE PatternType=0 and blockType=2 AND pattern='{$_GET["sitename"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if($ligne["pattern"]<>null){$enabledW="&nbsp;<span style='font-size:10px'>[{enabled}]</span>";}
	
	if($_GET["disposition"]=="1column"){
		$trtr="</tr><tr>";
		
	}
	
	if($_GET["gen-thumbnail"]=="yes"){
		
		$genthumbnail="$trtr
		<td width=1%><img src='img/arrow-right-16.png'></td>
		<td><a href=\"javascript:blur();\"
		OnClick=\"javascript:ReGenerateThumbs();\"
		style='font-size:13px;text-decoration:underline;font-weight:bold'>{regenerate_thumbnail}</a></td>";
		
	}
	
	$html="
	<table style='width:99%' class=form>
	<tr>
		<td width=1%><img src='img/arrow-right-16.png'></td>
		<td><a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('$page?cache-params-js=yes&sitename={$_GET["sitename"]}&t={$_GET["t"]}&table-t={$_GET["table-t"]}&TasksCallBacks={$_GET["TasksCallBacks"]}');\"
		style='font-size:13px;text-decoration:underline;font-weight:bold'>{cache_parameters}$enabled</a></td>
		$trtr
		<td width=1%><img src='img/arrow-right-16.png'></td>
		<td><a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('$page?white-params-js=yes&sitename={$_GET["sitename"]}&t={$_GET["t"]}&table-t={$_GET["table-t"]}&TasksCallBacks={$_GET["TasksCallBacks"]}');\"
		style='font-size:13px;text-decoration:underline;font-weight:bold'>{whitelist_this_website}$enabledW</a></td>
	</tr>	
	$genthumbnail
	</table>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function cache_params_popup(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();	
	if(!$q->TABLE_EXISTS("websites_caches_params")){$q->CheckTables();}
	$enabled=0;
	
	$_GET["sitename"]=$q->GetFamilySites($_GET["sitename"]);
	$sql="SELECT * FROM websites_caches_params WHERE sitename='{$_GET["sitename"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if($ligne["sitename"]<>null){$enabled=1;}	
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$MIN_AGES[60]="1 {hour}";
	$MIN_AGES[120]="2 {hours}";
	$MIN_AGES[360]="6 {hours}";
	$MIN_AGES[720]="12 {hours}";
	$MIN_AGES[1440]="1 {day}";
	$MIN_AGES[2880]="2 {days}";
	$MIN_AGES[10080]="1 {week}";
	$MIN_AGES[20160]="2 {weeks}";
	$MIN_AGES[43200]="1 {month}";
	$MIN_AGES[525600]="1 {year}";
	
	if(!is_numeric($ligne["MIN_AGE"])){$ligne["MIN_AGE"]=1440;}
	if(!is_numeric($ligne["PERCENT"])){$ligne["PERCENT"]=70;}
	if(!is_numeric($ligne["options"])){$ligne["options"]=1;}
	if(!is_numeric($ligne["MAX_AGE"])){$ligne["MAX_AGE"]=10080;}
	
	$PERCENTS[9999]="{no_refresh}";
	$PERCENTS[95]="{very_low}";
	$PERCENTS[70]="{low}";
	$PERCENTS[50]="{medium}";
	
	$PERCENTS[20]="{high}";
	$PERCENTS[10]="{very_high}";
	$PERCENTS[0]="{all_times}";
	
	$options[0]="{webserver_override_cache}";
	$options[1]="{cache_override_webserver_medium}";
	$options[2]="{cache_override_webserver_strong}";
if($_GET["TasksCallBacks"]<>null){$TasksCallBacks="{$_GET["TasksCallBacks"]}();";}
	
	// 1 -> ignore-no-cache ignore-no-store ignore-private refresh-ims
	// 2 -> override-expire ignore-no-cache ignore-no-store ignore-private override-lastmod ignore-auth ignore-reload
	

	
	// see http://archive09.linux.com/feature/153221.html
	$html="
	<div id='animate-$t'></div>
	<div style='font-size:18px;font-weight:bold'>{$_GET["sitename"]} {cache_parameters}</div>
	<table style='width:99%' class=form>
	
	
	<tr>
		<td class=legend style='font-size:14px'>{enable}:</td>
		<td>". Field_checkbox("enabled-$t", 1,$enabled,"CheckCacheEnable$t()")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{refresh_website_each}:</td>
		<td>". Field_array_Hash($MIN_AGES, "MIN_AGE-$t",$ligne["MIN_AGE"],"style:font-size:14px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{refresh_rate}:</td>
		<td>". Field_array_Hash($PERCENTS,"PERCENT-$t",$ligne["PERCENT"], "style:font-size:14px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{reload_website_each}:</td>
		<td>". Field_array_Hash($MIN_AGES,"MAX_AGE-$t",$ligne["MAX_AGE"], "style:font-size:14px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{cache_priorities}:</td>
		<td>". Field_array_Hash($options,"options-$t",$ligne["options"], "style:font-size:14px")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'>". button("{apply}","SaveCacheWebParams$t()",16)."</td>
	</tr>
	</table>

	<script>
		var X_SaveCacheWebParams$t= function (obj) {
			var tableT='{$_GET["table-t"]}';
			var results=obj.responseText;
			if(results.length>3){alert(results);}	
			if(document.getElementById('animate-$t')){document.getElementById('animate-$t').innerHTML='';}
			if(tableT.length>0){RechargeTableauDesSitesCaches();}
			YahooWin5Hide();
			$TasksCallBacks
			}
	
	
		function SaveCacheWebParams$t(){
			var XHR = new XHRConnection();
			XHR.appendData('sitename','{$_GET["sitename"]}');
			XHR.appendData('MIN_AGE',document.getElementById('MIN_AGE-$t').value);
			XHR.appendData('MAX_AGE',document.getElementById('MAX_AGE-$t').value);
			XHR.appendData('PERCENT',document.getElementById('PERCENT-$t').value);
			XHR.appendData('options',document.getElementById('options-$t').value);
			if(!document.getElementById('enabled-$t').checked){
				XHR.appendData('DELETE','{$_GET["sitename"]}');
			}
			AnimateDiv('animate-$t');
			XHR.sendAndLoad('$page', 'POST',X_SaveCacheWebParams$t);
			
		}
		
		function CheckCacheEnable$t(){
			document.getElementById('MIN_AGE-$t').disabled=true;
			document.getElementById('MAX_AGE-$t').disabled=true;
			document.getElementById('PERCENT-$t').disabled=true;
			document.getElementById('options-$t').disabled=true;
			if(document.getElementById('enabled-$t').checked){
				document.getElementById('MIN_AGE-$t').disabled=false;
				document.getElementById('MAX_AGE-$t').disabled=false;
				document.getElementById('PERCENT-$t').disabled=false;
				document.getElementById('options-$t').disabled=false;			
			}
		}
	
	CheckCacheEnable$t();
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function cache_params_save(){
	if(trim($_POST["sitename"])==null){return;}
	if($_POST["MAX_AGE"]<$_POST["MIN_AGE"]){$_POST["MAX_AGE"]=$_POST["MIN_AGE"]+60;}
	
	$q=new mysql_squid_builder();
	$sql="SELECT sitename FROM websites_caches_params WHERE sitename='{$_POST["sitename"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if($ligne["sitename"]==null){
		$sql="INSERT IGNORE INTO websites_caches_params (sitename,MIN_AGE,MAX_AGE,PERCENT,options) 
		VALUES('{$_POST["sitename"]}','{$_POST["MIN_AGE"]}','{$_POST["MAX_AGE"]}','{$_POST["PERCENT"]}','{$_POST["options"]}')";
	}else{
		$sql="UPDATE websites_caches_params SET 
		MIN_AGE='{$_POST["MIN_AGE"]}', 
		MAX_AGE='{$_POST["MAX_AGE"]}',
		PERCENT='{$_POST["PERCENT"]}',
		options='{$_POST["options"]}'
		WHERE sitename='{$_POST["sitename"]}'";
	}
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?squidnewbee=yes");	
	
}
function cache_params_delete(){
	$q=new mysql_squid_builder();
	$sql="DELETE FROM websites_caches_params WHERE sitename='{$_POST["DELETE"]}'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?squidnewbee=yes");	
}
