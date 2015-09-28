<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
if(isset($_GET["VERBOSE"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.artica.graphs.inc');
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die();
	die();	
}

if(isset($_POST["CategoriesDatabasesUpdatesAllTimes"])){Save();exit;}
if(isset($_GET["ufdb-categories-status"])){ufdb_categories_status();exit;}
if(isset($_GET["ufdb-update-available-version"])){ufdb_available_version();exit;}
if(isset($_GET["toulouse-update-available-version"])){ufdb_toulouse_version();exit;}
if(isset($_GET["ufdb-update-settings"])){parameters();exit;}
page();



function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	
	
	$html="
	<div style='margin-bottom:20px;font-size:30px'>{webfiltering_databases}</div>
	<div id='ufdb-update-available-version'></div>
	<div id='toulouse-update-available-version'></div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
			<td valign='top' style='width:500px'><div id='ufdb-update-settings'></div></td>
			<td valign='top' style='width:990px'><div id='ufdb-categories-status'></div></td>
		</tr>
		
		
		
	</table>
	</div>		
	<script>
		LoadAjaxSilent('ufdb-update-available-version','$page?ufdb-update-available-version=yes');
		LoadAjaxSilent('toulouse-update-available-version','$page?toulouse-update-available-version=yes');
		LoadAjaxRound('ufdb-categories-status','$page?ufdb-categories-status=yes');
		LoadAjaxRound('ufdb-update-settings','$page?ufdb-update-settings=yes');
			
	</script>
		
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}


function ufdb_categories_status(){
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	$ArticaDbCloud=unserialize(base64_decode($sock->GET_INFO("ArticaDbCloud")));
	$TLSEDbCloud=unserialize(base64_decode($sock->GET_INFO("TLSEDbCloud")));
	
	
	$CurrentArticaDbCloud=unserialize($sock->GET_INFO("CurrentArticaDbCloud"));
	$CurrentTLSEDbCloud=unserialize($sock->GET_INFO("CurrentTLSEDbCloud"));
	
	
	
	$ProductName="Artica";
	$ProductNamef=dirname(__FILE__) . "/ressources/templates/{$_COOKIE["artica-template"]}/ProducName.conf";
	if(is_file($ProductNamef)){
		$ProductName=trim(@file_get_contents($ProductNamef));
	}
	
	
	$TIME=0;
	while (list ($table,$MAIN) = each ($ArticaDbCloud) ){
		$xTIME=$MAIN["TIME"];
		if($xTIME>$TIME){$TIME=$xTIME;}
	}
	
	$CURRENT_TIME=0;
	while (list ($table,$MAIN) = each ($CurrentArticaDbCloud) ){
		$xTIME=$MAIN["TIME"];
		if($xTIME>$CURRENT_TIME){$CURRENT_TIME=$xTIME;}
	}
	
	
	$TIME_TLSE=0;
	while (list ($table,$MAIN) = each ($TLSEDbCloud) ){
		$xTIME=$MAIN["TIME"];
		if($xTIME>$TIME){$TIME_TLSE=$xTIME;}
	}
	
	$CURRENT_TIME_TLSE=0;
	while (list ($table,$MAIN) = each ($CurrentTLSEDbCloud) ){
		$xTIME=$MAIN["TIME"];
		if($xTIME>$CURRENT_TIME_TLSE){$CURRENT_TIME_TLSE=$xTIME;}
	}	

	
	$ARTICA_OFFICIAL_DB=$tpl->time_to_date($TIME,true);
	$ARTICA_CURRENT_DB=$tpl->time_to_date($CURRENT_TIME,true);
	$TLSE_OFFICIAL_DB=$tpl->time_to_date($TIME_TLSE,true);
	$TLSE_CURRENT_DB=$tpl->time_to_date($CURRENT_TIME_TLSE,true);
	
	reset($ArticaDbCloud);
	
	$q=new mysql_squid_builder();
	
	
	$tablex[]="
	<table style='widh:100%'>
	<tr>
	<th style='font-size:18px'>{category}</th>
	<th style='font-size:18px'>{items} ($ProductName)</th>
	<th style='font-size:18px'>{status}</th>
	<th style='font-size:28px' nowrap>&nbsp;&nbsp;&nbsp;&nbsp;</th>
	<th style='font-size:18px'>{items} ({free_databases})</th>
	<th style='font-size:18px'>{status}</th>
	</tr>		
	";
	$color="";
	while (list ($table,$MAIN) = each ($ArticaDbCloud) ){
		$category=$q->tablename_tocat($table);
		if($color==null){$color="#F2F0F1";}else{$color=null;}
		
		$ROWS=FormatNumber($MAIN["ROWS"]);
		$ROWS_TLSE=FormatNumber($CurrentTLSEDbCloud[$category]["ROWS"]);
		$MD5SRC=$MAIN["MD5SRC"];
		$TIME_OFF=$MAIN["TIME"];
		$TIME_CUR=$CurrentArticaDbCloud[$table]["TIME"];
		
		$TIME_OFF_TLSE=$TLSEDbCloud[$category]["TIME"];
		$TIME_CUR_TLSE=$CurrentTLSEDbCloud[$category]["TIME"];
		
		$icon="ok32.png";
		$icon_tlse="ok32.png";
		$errors=array();
		$errors_text=null;
		$errors_tlse=array();
		$errors_tlse_text=null;
		
		
		
		
		$TIME_UPDATED=intval($CurrentArticaDbCloud[$table]["UPDATED"]);
		$TIME_TLS_UPDATED=intval($CurrentTLSEDbCloud[$category]["UPDATED"]);
		
		
		
		if($TIME_UPDATED==0){
			$icon="warning32.png";$errors[]="<span style='color:#d32d2d'>{must_be_updated}</span>";
		}

		if($TIME_TLS_UPDATED==0){
			$icon_tlse="warning32.png";$errors_tlse[]="<span style='color:#d32d2d'>{must_be_updated}</span>";
		}
		
		
		
		$TIME_TLS_UPDATED_text=$tpl->time_to_date($TIME_TLS_UPDATED,true);
		$TIME_UPDATED_text=$tpl->time_to_date($TIME_UPDATED,true);
		
		
		$SUCCESS=$CurrentArticaDbCloud[$table]["SUCCESS"];
		$SUCCESS_TLSE=$CurrentTLSEDbCloud[$category]["SUCCESS"];
		$CUR_MD5SRC=$CurrentArticaDbCloud[$table]["MD5SRC"];
		if(!$SUCCESS){
			$icon="warning32.png";
			$errors[]="<span style='color:#d32d2d'>{update_error}</span>";
		}
		
		
		if(!$SUCCESS_TLSE){
			$icon_tlse="warning32.png";
			$errors_tlse[]="<span style='color:#d32d2d'>{update_error}</span>";
		}
		
		
		$TIME_CLOUD=$tpl->time_to_date($TIME_OFF,true);
		
		if($TIME_OFF>$TIME_CUR){
			$icon="warning-32-yellow.png";
			$errors[]="<span style='color:#46a346'>{can_be_updated}: $TIME_CLOUD</span>";
		}
		
		if($TIME_OFF_TLSE>$TIME_CUR_TLSE){
			$TIME_CLOUD_TLSE=$tpl->time_to_date($TIME_OFF_TLSE,true);
			$icon_tlse="warning32.png";
			$errors_tlse[]="<span style='color:#46a346'>{can_be_updated}: $TIME_CLOUD_TLSE</span>";
		}
		
		if(!$users->CORP_LICENSE){
			$icon="warning-32-yellow.png";
			$errors[]="<span style='color:#46a346'>{license_error}</span>";
		}
		
		
		if(count($errors)>0){
			$errors_text="<div style='font-size:14px'><i>".@implode("<br>", $errors)."</i></div>";
		}
		
		if(count($errors_tlse)>0){
			$errors_tlse_text="<div style='font-size:14px'><i>".@implode("<br>", $errors_tlse)."</i></div>";
			
		}
		
		
		
		$TIME_TLS_UPDATED_text_text="<div style='font-size:14px'>{free_databases}&nbsp;{updated_on}:&nbsp;$TIME_TLS_UPDATED_text</div>";
		
		if(!isset($CurrentTLSEDbCloud[$category])){
			$ROWS_TLSE="&nbsp;-&nbsp;";
			$errors_tlse_text=null;
			$icon_tlse="ok32-grey.png";
			$TIME_TLS_UPDATED_text_text=null;
		}
		
		
		$tablex[]="<tr style='height:60px;background-color:$color'>
		<td style='font-size:16px;padding:5px' nowrap><span style='font-weight:bold;font-size:18px'>$category</span>
		<div style='font-size:14px'>{updated_on}:&nbsp;$TIME_UPDATED_text$errors_text</div>
		$TIME_TLS_UPDATED_text_text
		</td>
		<td style='font-size:16px;text-align:right'>$ROWS</td>
		<td style='font-size:16px;text-align:right'><center><img src=img/$icon></center></td>
		<td style='font-size:16px;text-align:right'>&nbsp;</td>
		<td style='font-size:16px;text-align:right'>$ROWS_TLSE$errors_tlse_text</td>
		
		<td style='font-size:16px;text-align:right'><center><img src=img/$icon_tlse></center></td>
		
		</tr>";
		
		
	}
	
	$PhishTankLastDate=$sock->GET_INFO("PhishTankLastDate");
	if($PhishTankLastDate==null){$PhishTankLastDate="-";}
	$PhishTankLastCount=FormatNumber(intval($sock->GET_INFO("PhishTankLastCount")));
	
	$tablex[]="</table>";
	$html="
			
	<table style='width:100%'>
	<tr>
		<td style='width:50%'>
	<table style='width:100%'>
		<tr>
			<td  style='font-size:24px;font-weight:bold' colspan=2>{webfiltering_database} ($ProductName)</td>
		</tr>
		<tr>
			<td style='font-size:18px;' class=legend>{available}:</td>
			<td style='font-size:18px;font-weight:bold'>$ARTICA_OFFICIAL_DB</td>
		</tr>
		<tr>
			<td style='font-size:18px;' class=legend>{current}:</td>
			<td style='font-size:18px;font-weight:bold'>$ARTICA_CURRENT_DB</td>
		</tr>
		<tr>
			<td style='font-size:18px;' class=legend>{categories}:</td>
			<td style='font-size:18px;font-weight:bold'>". count($ArticaDbCloud)."</td>
		</tr>
	</table>
	</td>
		<td style='width:50%'>
	<table style='width:100%'>
	
		<tr>
			<td  style='font-size:24px;font-weight:bold' colspan=2>{free_databases}</td>
		</tr>
		<tr>
			<td style='font-size:18px;' class=legend>{available}:</td>
			<td style='font-size:18px;font-weight:bold'>$TLSE_OFFICIAL_DB</td>
		</tr>
		<tr>
			<td style='font-size:18px;' class=legend>{current}:</td>
			<td style='font-size:18px;font-weight:bold'>$TLSE_CURRENT_DB</td>
		</tr>
		<tr>
			<td style='font-size:18px;' class=legend>{categories}:</td>
			<td style='font-size:18px;font-weight:bold'>". count($TLSEDbCloud)."</td>
		</tr>
		<tr><td colspan=2><hr></td></tr>
		<tr>
			<td style='font-size:18px;' class=legend>PhishTank: {version}:</td>
			<td style='font-size:18px;font-weight:bold'>$PhishTankLastDate</td>
		</tr>	
		<tr>
			<td style='font-size:18px;' class=legend>PhishTank: {items}:</td>
			<td style='font-size:18px;font-weight:bold'>$PhishTankLastCount</td>
		</tr>
	</table>
	</td>
</tr>
</table>											
	<p>&nbsp;</p>
		
	".@implode("\n", $tablex);
	
	echo $tpl->_ENGINE_parse_body($html);
	
		
	
	
}

function ufdb_toulouse_version(){
	$sock=new sockets();
	$tpl=new templates();
	$ArticaDbCloud=unserialize(base64_decode($sock->GET_INFO("TLSEDbCloud")));
	$CurrentArticaDbCloud=unserialize($sock->GET_INFO("CurrentTLSEDbCloud"));
	$TIME=0;
	while (list ($table,$MAIN) = each ($ArticaDbCloud) ){
		$xTIME=$MAIN["TIME"];
		if($xTIME>$TIME){$TIME=$xTIME;}
	}

	$CURRENT_TIME=0;
	while (list ($table,$MAIN) = each ($CurrentArticaDbCloud) ){
		$xTIME=$MAIN["TIME"];
		if($xTIME>$CURRENT_TIME){$CURRENT_TIME=$xTIME;}
	}


	if($TIME==0){

		$info="
		<div style='width:98%;margin-bottom:30px;background-color:white;padding:5px;border:1px solid #CCCCCC'>
		<table style='width:100%'>
		<tr>
		<td valign='top' style='width:65px'><img src=img/download-64.png></td>
		<td valign='top' style='width:99%;padding-left:20px'>
		<div style='font-size:20px'>{webfiltering_toulouse_databases_available}</div>
		<div style='font-size:20px;text-align:right;margin-top:30px;text-align:right'>
		<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('dansguardian2.articadb-progress.php')\"
		style='text-decoration:underline;font-weight:bold'>{update_webfiltering_toulouse_databases_not_updated}</a>
		</div>
		</td>
		</tr>
		</table>
		</div>
		";
		echo $tpl->_ENGINE_parse_body($info);
		return;
	}


	if($CURRENT_TIME==0){

		$info="
		<div style='width:98%;margin-bottom:30px;background-color:white;padding:5px;border:1px solid #CCCCCC'>
		<table style='width:100%'>
		<tr>
		<td valign='top' style='width:65px'><img src=img/download-64.png></td>
		<td valign='top' style='width:99%;padding-left:20px'>
		<div style='font-size:20px'>{webfiltering_toulouse_databases_available}</div>
		<div style='font-size:20px;text-align:right;margin-top:30px;text-align:right'>
		<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('dansguardian2.articadb-progress.php')\"
		style='text-decoration:underline;font-weight:bold'>{update_webfiltering_toulouse_databases_not_updated}</a>
		</div>
		</td>
		</tr>
		</table>
		</div>
		";
		echo $tpl->_ENGINE_parse_body($info);
		return;
	}

	if($CURRENT_TIME>=$TIME){return;}

	$info="
		<div style='width:98%;margin-bottom:30px;background-color:white;padding:5px;border:1px solid #CCCCCC'>
		<table style='width:100%'>
		<tr>
		<td valign='top' style='width:65px'><img src=img/download-64.png></td>
		<td valign='top' style='width:99%;padding-left:20px'>
		<div style='font-size:20px'>{webfiltering_toulouse_databases_available}</div>
		<div style='font-size:20px;text-align:right;margin-top:30px;text-align:right'>
		<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('dansguardian2.articadb-progress.php')\"
		style='text-decoration:underline;font-weight:bold'>{webfiltering_artica_databases_available_explain}</a>
		</div>
		</td>
		</tr>
		</table>
		</div>
		";
	echo $tpl->_ENGINE_parse_body($info);

}

function ufdb_available_version(){
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){return ;}
	$ArticaDbCloud=unserialize(base64_decode($sock->GET_INFO("ArticaDbCloud")));
	$CurrentArticaDbCloud=unserialize($sock->GET_INFO("CurrentArticaDbCloud"));
	$TIME=0;
	while (list ($table,$MAIN) = each ($ArticaDbCloud) ){
		$xTIME=$MAIN["TIME"];
		if($xTIME>$TIME){$TIME=$xTIME;}
	}
	
	$CURRENT_TIME=0;
	while (list ($table,$MAIN) = each ($CurrentArticaDbCloud) ){
		$xTIME=$MAIN["TIME"];
		if($xTIME>$CURRENT_TIME){$CURRENT_TIME=$xTIME;}
	}	
	
	
	if($TIME==0){

		$info="
		<div style='width:98%;margin-bottom:30px;background-color:white;padding:5px;border:1px solid #CCCCCC'>
		<table style='width:100%'>
		<tr>
		<td valign='top' style='width:65px'><img src=img/download-64.png></td>
		<td valign='top' style='width:99%;padding-left:20px'>
		<div style='font-size:20px'>{update_webfiltering_artica_databases_not_updated}</div>
		<div style='font-size:20px;text-align:right;margin-top:30px;text-align:right'>
		<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('dansguardian2.articadb-progress.php')\"
		style='text-decoration:underline;font-weight:bold'>{update_webfiltering_artica_databases}</a>
		</div>
		</td>
		</tr>
		</table>
		</div>
		";
		echo $tpl->_ENGINE_parse_body($info);
		return;
	}
	
	
	if($CURRENT_TIME==0){
	
		$info="
		<div style='width:98%;margin-bottom:30px;background-color:white;padding:5px;border:1px solid #CCCCCC'>
		<table style='width:100%'>
		<tr>
		<td valign='top' style='width:65px'><img src=img/download-64.png></td>
		<td valign='top' style='width:99%;padding-left:20px'>
		<div style='font-size:20px'>{update_webfiltering_artica_databases_not_updated}</div>
		<div style='font-size:20px;text-align:right;margin-top:30px;text-align:right'>
		<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('dansguardian2.articadb-progress.php')\"
		style='text-decoration:underline;font-weight:bold'>{update_webfiltering_artica_databases}</a>
		</div>
		</td>
		</tr>
		</table>
		</div>
		";
		echo $tpl->_ENGINE_parse_body($info);
		return;
	}	
	
	if($CURRENT_TIME>=$TIME){return;}
	
	$info="
		<div style='width:98%;margin-bottom:30px;background-color:white;padding:5px;border:1px solid #CCCCCC'>
		<table style='width:100%'>
		<tr>
		<td valign='top' style='width:65px'><img src=img/download-64.png></td>
		<td valign='top' style='width:99%;padding-left:20px'>
		<div style='font-size:20px'>{webfiltering_artica_databases_available}</div>
		<div style='font-size:20px;text-align:right;margin-top:30px;text-align:right'>
		<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('dansguardian2.articadb-progress.php')\"
		style='text-decoration:underline;font-weight:bold'>{webfiltering_artica_databases_available_explain}</a>
		</div>
		</td>
		</tr>
		</table>
		</div>
		";
	echo $tpl->_ENGINE_parse_body($info);	
	
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}


function parameters(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$DisableCategoriesDatabasesUpdates=intval($sock->GET_INFO("DisableCategoriesDatabasesUpdates"));
	$CategoriesDatabasesUpdatesAllTimes=intval($sock->GET_INFO("CategoriesDatabasesUpdatesAllTimes"));
	$CategoriesDatabasesByCron=intval($sock->GET_INFO("CategoriesDatabasesByCron"));
	if(!is_numeric($CategoriesDatabasesByCron)){$CategoriesDatabasesByCron=1;}
	
	$CategoriesDatabasesShowIndex=$sock->GET_INFO("CategoriesDatabasesShowIndex");
	if(!is_numeric($CategoriesDatabasesShowIndex)){$CategoriesDatabasesShowIndex=1;}
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	
	$WebFUpdateH=$sock->GET_INFO("WebFUpdateH");
	$WebFUpdateM=$sock->GET_INFO("WebFUpdateM");
	$t=time();
	
	
	for($i=0;$i<24;$i++){
		$H=$i;
		if($i<10){$H="0$i";}
		$Hours[$i]=$H;
	}
	
	for($i=0;$i<60;$i++){
		$M=$i;
		if($i<10){$M="0$i";}
		$Mins[$i]=$M;
	}	
	
	
	$button_update=button("{update_now}", "Loadjs('dansguardian2.articadb-progress.php')",24,450);
	$button_delete_db=button("{delete_databases}", "Loadjs('dansguardian2.databases.delete.progress.php')",24,450);
	
	
	$html="
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>". texttooltip("{disable_udpates}","{disable_udpates_explain}")."</td>
		<td>". Field_checkbox_design("DisableCategoriesDatabasesUpdates", 1,$DisableCategoriesDatabasesUpdates,"DisableCategoriesDatabasesUpdatesCheck()")."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:18px'>". texttooltip("{free_update_during_the_day}","{free_update_during_the_day_explain}")."</td>
		<td>". Field_checkbox_design("CategoriesDatabasesUpdatesAllTimes", 1,$CategoriesDatabasesUpdatesAllTimes)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>". texttooltip("{update_only_by_schedule}","{articadb_update_only_by_schedule}")."</td>
		<td>". Field_checkbox_design("CategoriesDatabasesByCron-$t", 1,$CategoriesDatabasesByCron,"CategoriesDatabasesByCronCheck()")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{schedule}:</td>
		<td style='font-size:16px' colspan=2>
				<table style='width:135px'>
				<tr>
					<td style='font-size:18px'>".Field_array_Hash($Hours, "WebFUpdateH",$WebFUpdateH,"style:font-size:18px")."</td>
					<td style='font-size:18px'>:</td>
					<td style='font-size:18px'>".Field_array_Hash($Mins, "WebFUpdateM",$WebFUpdateM,"style:font-size:18px")."</td>
				</tr>
				</table>
		</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","Save$t();",24)."</td>
	</tr>			
				
	</table>
<center style='margin-top:115px'>$button_update</center>
<center style='margin-top:15px'>$button_delete_db</center>					
							
							
							
<script>

var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>1){alert(results);}
}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('WebFUpdateH',document.getElementById('WebFUpdateH').value);
	XHR.appendData('WebFUpdateM',document.getElementById('WebFUpdateM').value);
	XHR.appendData('CategoriesDatabasesUpdatesAllTimes',document.getElementById('CategoriesDatabasesUpdatesAllTimes').value);
	if(document.getElementById('CategoriesDatabasesByCron-$t').checked){XHR.appendData('CategoriesDatabasesByCron','1');}else{XHR.appendData('CategoriesDatabasesByCron','0');}
	if(document.getElementById('CategoriesDatabasesUpdatesAllTimes').checked){XHR.appendData('CategoriesDatabasesUpdatesAllTimes','1');}else{XHR.appendData('CategoriesDatabasesUpdatesAllTimes','0');}
	if(document.getElementById('DisableCategoriesDatabasesUpdates').checked){XHR.appendData('DisableCategoriesDatabasesUpdates','1');}else{XHR.appendData('DisableCategoriesDatabasesUpdates','0');}
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
							
function DisableCategoriesDatabasesUpdatesCheck(){
	document.getElementById('CategoriesDatabasesUpdatesAllTimes').disabled=true;
	document.getElementById('CategoriesDatabasesByCron-$t').disabled=true;
	document.getElementById('WebFUpdateH').disabled=true;
	document.getElementById('WebFUpdateM').disabled=true;
	if(document.getElementById('DisableCategoriesDatabasesUpdates').checked){return;}	
	
	document.getElementById('CategoriesDatabasesByCron-$t').disabled=false;
	CategoriesDatabasesByCronCheck();
							
}
							
function CategoriesDatabasesByCronCheck(){
	if(document.getElementById('DisableCategoriesDatabasesUpdates').checked){return;}	
	document.getElementById('WebFUpdateH').disabled=true;
	document.getElementById('WebFUpdateM').disabled=true;
	document.getElementById('CategoriesDatabasesUpdatesAllTimes').disabled=false;
	
	if(!document.getElementById('CategoriesDatabasesByCron-$t').checked){return;}
	document.getElementById('WebFUpdateH').disabled=false;
	document.getElementById('WebFUpdateM').disabled=false;
	document.getElementById('CategoriesDatabasesUpdatesAllTimes').disabled=true;
}

DisableCategoriesDatabasesUpdatesCheck();							
CategoriesDatabasesByCronCheck();							
</script>
							

	";

	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function Save(){
	$sock=new sockets();
	while (list ($key,$value) = each ($_POST) ){
		$sock->SET_INFO($key, $value);
	}
	$sock->getFrameWork("squid2.php?ufdb-update-settings=yes");
}


//ArticaDbCloud
