<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
if(isset($_GET["VERBOSE"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
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
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

page();

function page(){
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();
	
	$ARTICA_DBS_STATUS=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/ARTICA_DBS_STATUS.db"));
	
	$array=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/cache/articatechdb.progress"));
	$text=$array["TEXT"];
	$purc=intval($array["POURC"]);
	
	$array=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/cache/toulouse.progress"));
	$text1=$array["TEXT"];
	$purc1=intval($array["POURC"]);
	
	$array=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/cache/webfilter-artica.progress"));
	$text2=$array["TEXT"];
	$purc2=intval($array["POURC"]);	
	
	$CATZ_ARRAY=unserialize(@file_get_contents("/home/artica/categories_databases/CATZ_ARRAY"));
	$date=$CATZ_ARRAY["TIME"];
	$LOCAL_VERSION=$CATZ_ARRAY["TIME"];
	$LOCAL_VERSION_TEXT=$tpl->time_to_date($date);
	
	
	unset($CATZ_ARRAY["TIME"]);
	$CountDecategories=0;
	while (list ($table, $items) = each ($CATZ_ARRAY) ){
		$items=intval($items);
	
		$CountDecategories=$CountDecategories+$items;
		if($GLOBALS["VERBOSE"]){echo "<li>$table - $items = $CountDecategories</li>";}
	}
	
	if(!is_numeric($CountDecategories)){$CountDecategories=0;}
	$CountDecategories=numberFormat($CountDecategories,0,""," ");
	
	
	
	$CAT_STATS_PERCENT=intval($ARTICA_DBS_STATUS["CAT_STATS_PERCENT"]);
	$CAT_STATS_SIZE=intval($ARTICA_DBS_STATUS["CAT_STATS_SIZE"]);
	
	$CAT_STATS_COUNT=intval($ARTICA_DBS_STATUS["CAT_STATS_COUNT"]);
	
	$TLSE_PRC=intval($ARTICA_DBS_STATUS["TLSE_PRC"]);
	$TLSE_STAT_SIZE=intval($ARTICA_DBS_STATUS["TLSE_STAT_SIZE"]);
	$TLSE_COUNT=intval($ARTICA_DBS_STATUS["TLSE_COUNT"]);
	$TLSE_STAT_ITEMS=numberFormat(intval($ARTICA_DBS_STATUS["TLSE_STAT_ITEMS"]),0,""," ");
	$TLSE_LAST_SINCE=$ARTICA_DBS_STATUS["TLSE_LAST_SINCE"];
	$TLSE_LAST_CAT=$ARTICA_DBS_STATUS["TLSE_LAST_CAT"];
	$TLSE_LAST_SIZE=$ARTICA_DBS_STATUS["TLSE_LAST_SIZE"];
	
	if($TLSE_LAST_SINCE==null){$TLSE_LAST_SINCE="-";}
	if($TLSE_LAST_CAT==null){$TLSE_LAST_CAT="-";}
	if(!is_numeric($TLSE_LAST_SIZE)){$TLSE_LAST_SIZE=0;}
	
	
	
	$CAT_ARTICA_PRC=intval($ARTICA_DBS_STATUS["CAT_ARTICA_PRC"]);
	$CAT_ARTICA_SIZE=intval($ARTICA_DBS_STATUS["CAT_ARTICA_SIZE"]);
	$CAT_ARTICA_COUNT=intval($ARTICA_DBS_STATUS["CAT_ARTICA_COUNT"]);
	$CAT_ARTICA_SINCE=$ARTICA_DBS_STATUS["CAT_ARTICA_SINCE"];
	$CAT_ARTICA_LAST_CAT=$ARTICA_DBS_STATUS["CAT_ARTICA_LAST_CAT"];
	$CAT_ARTICA_LAST_SIZE=$ARTICA_DBS_STATUS["CAT_ARTICA_LAST_SIZE"];
	$CAT_ARTICA_LAST_ERROR=$ARTICA_DBS_STATUS["CAT_ARTICA_LAST_ERROR"];

	if($CAT_ARTICA_SINCE==null){$CAT_ARTICA_SINCE="-";}
	if($CAT_ARTICA_LAST_CAT==null){$CAT_ARTICA_LAST_CAT="-";}
	if(!is_numeric($CAT_ARTICA_LAST_SIZE)){$CAT_ARTICA_LAST_SIZE=0;}
	if(!is_numeric($CAT_ARTICA_LAST_ERROR)){$CAT_ARTICA_LAST_ERROR=0;}
	
	
	
	
	
	$html="
	<div style='margin-top:15px;font-size:26px;margin-bottom:30px'>{categories_databases}</div>
	
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>&nbsp;</td>
		<td style='font-size:18px;width:70%'>$text</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px;width:30%' nowrap>{download_progress}:</td>
		<td><div id='progress-$t' style='height:25px'></div></td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{database_status}:</td>
		<td><div id='progressdb-$t' style='height:25px'></div></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{directory_size}:</td>
		<td style='font-size:18px'>". FormatBytes($CAT_STATS_SIZE)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{databases_number}:</td>
		<td style='font-size:18px'>$CAT_STATS_COUNT</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{websites}:</td>
		<td style='font-size:18px'>$CountDecategories</td>
	</tr>
	<tr>
		<td colspan=2 align='right'>". button("{update_now}", "Loadjs('squid.blacklist.upd.php')",16)."</td>
	</tr>			
	</table>
	
	
	
	
	<p>&nbsp;</p>	
	<div style='margin-top:15px;font-size:26px'>{webfiltering_database} (Toulouse University)</div>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>&nbsp;</td>
		<td style='font-size:18px;width:70%'>$text1</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px;width:30%' nowrap>{download_progress}:</td>
		<td style='font-size:18px;width:70%'>
			<div id='progress1-$t' style='height:25px'></div>
		</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{database_status}:</td>
		<td><div id='progressdb1-$t' style='height:25px'></div></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{directory_size}:</td>
		<td style='font-size:18px'>". FormatBytes($TLSE_STAT_SIZE)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{databases_number}:</td>
		<td style='font-size:18px'>$TLSE_COUNT</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{websites}:</td>
		<td style='font-size:18px'>$TLSE_STAT_ITEMS</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{last_download}:</td>
		<td style='font-size:18px'>$TLSE_LAST_SINCE</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{last_category}:</td>
		<td style='font-size:18px'>$TLSE_LAST_CAT</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{last_downloaded_size}:</td>
		<td style='font-size:18px'>". FormatBytes($TLSE_LAST_SIZE)."</td>
	</tr>	
	
	<tr>
		<td colspan=2 align='right'>". button("{update_now}", "ToulouseDBUpdateNow();",16)."</td>
	</tr>	
	
	</table>	
	

<p>&nbsp;</p>
	<div style='margin-top:15px;font-size:26px'>{webfiltering_database} (Artica)</div>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>&nbsp;</td>
		<td style='font-size:18px;width:70%'>$text2</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px;width:30%' nowrap>{download_progress}:</td>
		<td style='font-size:18px;width:70%'>
			<div id='progress2-$t' style='height:25px'></div>
		</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{database_status}:</td>
		<td><div id='progressdb2-$t' style='height:25px'></div></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{directory_size}:</td>
		<td style='font-size:18px'>". FormatBytes($CAT_ARTICA_SIZE)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{databases_number}:</td>
		<td style='font-size:18px'>$CAT_ARTICA_COUNT</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{websites}:</td>
		<td style='font-size:18px'>$CountDecategories</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{last_download}:</td>
		<td style='font-size:18px'>$CAT_ARTICA_SINCE</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{last_category}:</td>
		<td style='font-size:18px'>$CAT_ARTICA_LAST_CAT</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{last_downloaded_size}:</td>
		<td style='font-size:18px'>". FormatBytes($CAT_ARTICA_LAST_SIZE)."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{errors}:</td>
		<td style='font-size:18px'>". FormatBytes($CAT_ARTICA_LAST_ERROR)."</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'>". button("{update_now}", "ArticaDBDBUpdateNow()",16)."</td>
	</tr>	
	</table>	
	
	<div id='progress2-$t' style='height:50px'></div>		
	<script>
		$('#progress-$t').progressbar({ value: $purc });
		$('#progressdb-$t').progressbar({ value: $CAT_STATS_PERCENT });
		
		$('#progress1-$t').progressbar({ value: $purc1 });
		$('#progressdb1-$t').progressbar({ value: $TLSE_PRC });
		
		
		
		
		$('#progress2-$t').progressbar({ value: $purc2 });
		$('#progressdb2-$t').progressbar({ value: $CAT_ARTICA_PRC });
		
		
	
	var xToulouseDBUpdateNow= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
	    	
		}	

		function ToulouseDBUpdateNow(){
			var XHR = new XHRConnection();
			XHR.appendData('global-toulouse-status-update','yes');
			XHR.sendAndLoad('dansguardian2.databases.compiled.php', 'POST',xToulouseDBUpdateNow);
		}

		function ArticaDBDBUpdateNow(){
			var XHR = new XHRConnection();
			XHR.appendData('global-artica-filtersdb-update','yes');
			XHR.sendAndLoad('dansguardian2.databases.compiled.php', 'POST',xToulouseDBUpdateNow);
		}			

	</script>
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
