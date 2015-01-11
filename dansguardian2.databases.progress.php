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
	echo FATAL_WARNING_SHOW_128("{ERROR_NO_PRIVS}");die();
	die();	
}

page();

function page(){
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	
	if($_GET["from-ufdbguard"]=="yes"){
		echo $tpl->_ENGINE_parse_body("
				<div style='margin:15px;text-align:right'>
				". button("{back_to_webfiltering}",
							"AnimateDiv('BodyContent');LoadAjax('BodyContent','dansguardian2.mainrules.php')",18)."
				</div>");
	}
	
	$ARTICA_DBS_STATUS=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/ARTICA_DBS_STATUS_FULL.db"));
	
	$array=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/cache/articatechdb.progress"));
	$text=$array["TEXT"];
	$purc=intval($array["POURC"]);
	
	$array=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/cache/toulouse.progress"));
	$text1=$array["TEXT"];
	$purc1=intval($array["POURC"]);
	
	$array=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/cache/webfilter-artica.progress"));
	$text2=$array["TEXT"];
	$purc2=intval($array["POURC"]);	
	

	$date=$ARTICA_DBS_STATUS["ARTICA_DB_TIME"];
	$LOCAL_VERSION=$date;
	$LOCAL_VERSION_TEXT=$tpl->time_to_date($date);
	
	
	
	$CountDecategories=$ARTICA_DBS_STATUS["CAT_ARTICA_ITEMS_NUM"];
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
	$TLSE_LAST_CHECK=$ARTICA_DBS_STATUS["TLSE_LAST_CHECK"];
	
	if($TLSE_LAST_SINCE==0){$TLSE_LAST_SINCE="-";}
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
	
	
	$CAT_STATS_SINCE=$ARTICA_DBS_STATUS["CAT_STATS_SINCE"];
	$CAT_STATS_LAST_CAT=$ARTICA_DBS_STATUS["CAT_STATS_LAST_CAT"];
	$CAT_STATS_LAST_SIZE=$ARTICA_DBS_STATUS["CAT_STATS_LAST_SIZE"];
	$CAT_STATS_LAST_ERROR=$ARTICA_DBS_STATUS["CAT_STATS_LAST_ERROR"];
	$CAT_ARTICA_LAST_CHECK=$ARTICA_DBS_STATUS["CAT_ARTICA_LAST_CHECK"];
	if(!is_numeric($CAT_STATS_LAST_SIZE)){$CAT_STATS_LAST_SIZE=0;}
	if(!is_numeric($CAT_STATS_LAST_ERROR)){$CAT_STATS_LAST_ERROR=0;}
	if($CAT_STATS_SINCE==null){$CAT_STATS_SINCE="-";}
	if($CAT_STATS_LAST_CAT==null){$CAT_STATS_LAST_CAT="-";}
	if($TLSE_LAST_CHECK>0){
		$TLSE_LAST_CHECK=distanceOfTimeInWords($TLSE_LAST_CHECK,time(),true);
	}else{
		$TLSE_LAST_CHECK="{never}";
	}
	if($CAT_ARTICA_LAST_CHECK>0){
		$CAT_ARTICA_LAST_CHECK=distanceOfTimeInWords($CAT_ARTICA_LAST_CHECK,time(),true);
	}else{
		$CAT_ARTICA_LAST_CHECK="{never}";
	}
	
	$ProductName="Artica";
	$ProductNamef=dirname(__FILE__) . "/ressources/templates/{$_COOKIE["artica-template"]}/ProducName.conf";
	if(is_file($ProductNamef)){
		$ProductName=trim(@file_get_contents($ProductNamef));
	}
	
	
	$button_update=button("{update_now}", "Loadjs('dansguardian2.articadb-progress.php')",32);
	$button_status=button("{databases_status}", "Loadjs('dansguardian2.databases.artica.php')",32);
	
	if(!$users->CORP_LICENSE){
		$license_error="- &laquo;{license_error}&raquo;";
		$button_update=null;
		$button_status=null;
	}
	
	$html="
	<div style='margin-top:15px;margin-bottom:15px;text-align:right'>". imgtootltip("refresh-32.png",null,"RefreshTab('main_webfiltering_updates')")."</div>

	
	
	
	<div style='margin:20px;width:98%' class=form>
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
		<td class=legend style='font-size:18px'>{last_check}:</td>
		<td style='font-size:18px'>$TLSE_LAST_CHECK</td>
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
		<td class=legend style='font-size:18px'>{events}:</td>
		<td style='font-size:18px'><a href=\"javascript:blur();\" 
				OnClick=\"javascript:Loadjs('dansguardian2.databases.progress.events.php')\"
				style='font-size:18px;text-decoration:underline'
				>{events}</td>
	</tr>		
	<tr>
		<td colspan=2 align='right'>". button("{update_now}", "Loadjs('dansguardian2.univ-toulouse-progress.php')",32)."</td>
	</tr>	
	
	</table>	
</div>	

<p>&nbsp;</p>
<div style='margin:20px;width:98%' class=form>
	<div style='margin-top:15px;font-size:26px'>{webfiltering_database} ($ProductName) <span style='color:#BF0000'>$license_error</span></div>
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
		<td class=legend style='font-size:18px'>{version}:</td>
		<td style='font-size:18px'>$LOCAL_VERSION_TEXT</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:18px'>{last_check}:</td>
		<td style='font-size:18px'>$CAT_ARTICA_LAST_CHECK</td>
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
		<td style='font-size:18px'>". $CAT_ARTICA_LAST_ERROR."</td>
	</tr>	
		<tr>
		<td class=legend style='font-size:18px'>{events}:</td>
		<td style='font-size:18px'><a href=\"javascript:blur();\" 
				OnClick=\"javascript:Loadjs('dansguardian2.databases.progress.events.php')\"
				style='font-size:18px;text-decoration:underline'
				>{events}</td>
	</tr>
	<tr>
		<td colspan=2 align='right'>
			$button_update</td>
	</tr>
	</table>	
	
	<div id='progress2-$t' style='height:50px'></div>	
</div>	
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
