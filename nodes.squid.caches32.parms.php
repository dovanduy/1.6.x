<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.blackboxes.inc');
	include_once('ressources/class.squid.inc');
	
	$users=new usersMenus();
	if(!$users->AsSquidAdministrator){die("NO PRIVS");}
	if(isset($_POST["DisableSquidSNMPMode"])){Save();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["booster"])){popupBooster();exit;}
	if(isset($_POST["SquidBoosterMem"])){SaveBooster();exit;}
	if(isset($_GET["cachemem"])){cachemem();exit;}
	if(isset($_POST["cache_mem"])){cachemem_save();exit;}
	if(isset($_GET["tab"])){tabs();exit;}
	
	js();
	
	
function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	
	$array["popup"]='{caches_behavior}';
	$array["booster"]='{squid_booster}';
	$array["cachemem"]='{memory}';
	
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:14px'><a href=\"$page?$num=yes&hostid={$_GET["hostid"]}\"><span>$ligne</span></a></li>\n");
	}
	
	
	echo "
	<div id=hostid_cache_settings style='width:99%;height:auto;'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#hostid_cache_settings').tabs();
		
		
			});
		</script>";
	
	
	}	
	
function cachemem_save(){
	$blackboxes=new blackboxes($_POST["uuid"]);
	$blackboxes->SET_SQUID_INFO("CacheMemCentral", $_POST["cache_mem"]);
	$blackboxes->reconfigure_squid();	
}
	
function cachemem(){
		$tpl=new templates();
		$page=CurrentPageName();
		$squid=new squidbee();
		$cache_mem=$squid->global_conf_array["cache_mem"];
		if(preg_match("#([0-9]+)\s+#", $cache_mem,$re)){$cache_mem=$re[1];}
		$blackboxes=new blackboxes($_GET["hostid"]);
		
		$CacheMemCentral=$blackboxes->GET_SQUID_INFO("CacheMemCentral");	
		if(!is_numeric($CacheMemCentral)){$CacheMemCentral=$cache_mem;}
		
		
		$t=time();
		$html="
		<div id='$t'>
		<table style='width:99%' class=form>
		<tbody>
		<tr>
		<td class=legend style='font-size:16px'>{memory}:</td>
		<td style='font-size:16px'>". Field_text("cache_mem-$t",$CacheMemCentral,"font-size:18px;width:95px")."&nbsp;MB<td>
		<td style='font-size:16px' width=1%>". help_icon("{cache_mem_text}")."<td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","SaveCacheMem$t()",16)."</td>
	</tr>
	</tbody>
	</table>
		</div>
	<script>
		var x_SaveCacheMem=function (obj) {
			var tempvalue=obj.responseText;
			RefreshTab('hostid_cache_settings');
		}
	
		function SaveCacheMem$t(){
			var XHR = new XHRConnection();
			XHR.appendData('uuid','{$_GET["hostid"]}');
			XHR.appendData('cache_mem',document.getElementById('cache_mem-$t').value);
			AnimateDiv('$t');
			XHR.sendAndLoad('$page', 'POST',x_SaveCacheMem);
		}
	</script>
	";
		echo $tpl->_ENGINE_parse_body($html);
	}	

function js(){
	$page=CurrentPageName();
	$blackboxes=new blackboxes($_GET["hostid"]);
	$hostname=$blackboxes->hostname;
	$title="$hostname:: {caches_parameters}";
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("$title");
	echo "YahooWin3('700','$page?tab=yes&hostid={$_GET["hostid"]}','$title');";
}

function popup(){
	$page=CurrentPageName();
	$uuid=$_GET["hostid"];
	$blackboxes=new blackboxes($_GET["hostid"]);	
	$squid_version=$blackboxes->squid_version;
	
	$DisableAnyCache=$blackboxes->GET_SQUID_INFO("DisableAnyCache");
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$users=new usersMenus();
	$CORP=0;
	if($blackboxes->settings_inc["CORP_LICENSE"]){$CORP=1;}
	$SQUID32=0;
	if(preg_match("#^3\.2#", $squid_version)){$SQUID32=1;}
	if(preg_match("#^3\.3#", $squid_version)){$SQUID32=1;}
	
	$sql="SELECT * FROM cacheconfig WHERE `uuid`='{$_GET["hostid"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$CPUS=$ligne["workers"];
	if(!is_numeric($CPUS)){
		$CPUS=$blackboxes->settings_inc["CPU_NUMBER"];
	}
	
	
	$cachesDirectory=$ligne["cachesDirectory"];
	$globalCachesize=$ligne["globalCachesize"];
	if(!is_numeric($globalCachesize)){$globalCachesize=5000;}
	if($cachesDirectory==null){$cachesDirectory="/var/cache";}
	
	$globalCachesizeTOT=(($globalCachesize*1000)*$CPUS);
	$globalCachesize_text=FormatBytes($globalCachesizeTOT);
	$rebuild_caches_warn=$tpl->javascript_parse_text("{rebuild_caches_warn}");
	$reindex_caches_warn=$tpl->javascript_parse_text("{reindex_caches_warn}");
	$verify_caches=$tpl->javascript_parse_text("{verify_caches}");
	$delete_cache=$tpl->javascript_parse_text("{delete_cache}");
	$WARN_OPE_RESTART_SQUID_ASK=$tpl->javascript_parse_text("{WARN_OPE_RESTART_SQUID_ASK}");
	$warn_disable_squid_cany_cache=$tpl->javascript_parse_text("{warn_disable_squid_cany_cache}");	
	$warning_rebuild_squid_caches=$tpl->javascript_parse_text("{warning_rebuild_squid_caches}");
	
	
	if(!is_numeric($DisableAnyCache)){$DisableAnyCache=0;}
	$LICENSE=0;
	if($users->CORP_LICENSE){$LICENSE=1;}
	$this_feature_is_disabled_corp_license=$tpl->javascript_parse_text("{this_feature_is_disabled_corp_license}");
	
	$t=time();
	$html="
	<div id='$t-license'></div>
	<div id='caches-32-div'></div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend>{DisableAnyCache}:</td>
		<td>". Field_checkbox("DisableAnyCache",1,$DisableAnyCache,"CheckDisableAnyCache()")."</td>
		</tr>
		<tr>
			<td colspan=2 align=right><hr>". button("{apply}", "CheckDisableAnyCache()",16)."</td>
		</tr>
		</table>
		</div>

	
			
<script>
	var x_SaveSquid32Caches= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		document.getElementById('caches-32-div').innerHTML='';
	}
	

	function SaveSquid32Caches(){
	if(confirm('$warning_rebuild_squid_caches')){
		var XHR = new XHRConnection();
		var LICENSE=$LICENSE;
		if(LICENSE==0){alert('$this_feature_is_disabled_corp_license');return;}
		XHR.appendData('uuid','$uuid');
		XHR.sendAndLoad('$page', 'POST',x_SaveSquid32Caches);
		}
	
	}
	
	var x_CheckDisableAnyCache= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		Loadjs('squid.compile.progress.php');
	}
	
	
	
	function CheckDisableAnyCache(){
		DisableAnyCache=0;
		if(document.getElementById('DisableAnyCache').checked){DisableAnyCache=1;}
		if(DisableAnyCache==1){
		if(!confirm('$warn_disable_squid_cany_cache')){
				document.getElementById('DisableAnyCache').checked=false;
				return;
			}
		}
		var XHR = new XHRConnection();
		XHR.appendData('DisableAnyCache',DisableAnyCache);
		AnimateDiv('caches-32-div');
		XHR.sendAndLoad('$page', 'POST',x_CheckDisableAnyCache);
	}
	
	
	function RebuildAllCaches(){
		if(confirm('$rebuild_caches_warn')){
			var XHR = new XHRConnection();
			XHR.appendData('rebuild-caches','yes');
			AnimateDiv('caches-32-div');
			XHR.sendAndLoad('$page', 'POST',x_SaveSquid32Caches);
		}
	}
	
	var x_VerifyCaches= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		YahooWin3('650','$page?verify-caches-logs=yes','$verify_caches');
		RefreshTab('main_cache_rules_main_tabs');
		RefreshTab('squid_main_caches_new');
	}
	
	function VerifyCaches(){
		var XHR = new XHRConnection();
		XHR.appendData('verify-caches','yes');
		AnimateDiv('caches-32-div');
		XHR.sendAndLoad('$page', 'POST',x_VerifyCaches);
	}
	
	function ReindexAllCaches(){
		if(confirm('$reindex_caches_warn')){
			var XHR = new XHRConnection();
			XHR.appendData('reindex-caches','yes');
			AnimateDiv('caches-32-div');
			XHR.sendAndLoad('$page', 'POST',x_SaveSquid32Caches);
			}
	}

	
	function squid32DeleteCache(encoded){
		if(confirm('$delete_cache ?\\n$WARN_OPE_RESTART_SQUID_ASK')){
			var XHR = new XHRConnection();
			XHR.appendData('delete-cache',encoded);
			AnimateDiv('caches-32-div');
			XHR.sendAndLoad('$page', 'POST',x_SaveSquid32Caches);
		}
	}
	
	
	function CheckSNMPMode(){
		var CORP=$CORP;
		if(CORP==0){
			LoadAjaxTiny('$t-license','squid.caches32.php?license-explain=yes');
		}
					
				
}
	
	function CheckSquidVer(){
		var SQUID32=$SQUID32;
		if(SQUID32==0){
			document.getElementById('DisableAnyCache').disabled=true;
			

		}
		
	}
	
	CheckSNMPMode();
	CheckSquidVer();
	</script>
	
	
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
}

function Save(){
	$uuid=$_POST["uuid"];

	$blackboxes=new blackboxes($uuid);
	$blackboxes->SET_SQUID_POST_INFO($_POST);
	

	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM cachestatus WHERE uuid='$uuid'");
	$q->QUERY_SQL($sql);

	if(!$q->ok){echo $q->mysql_error;return;}
	$blackboxes->reconfigure_squid();
}


function popupBooster(){
	$tpl=new templates();
	$page=CurrentPageName();
	
	$uuid=$_GET["hostid"];
	$blackboxes=new blackboxes($uuid);
	
	
	$SquidBoosterMem=$blackboxes->GET_SQUID_INFO("SquidBoosterMem");
	$SquidBoosterMemK=$blackboxes->GET_SQUID_INFO("SquidBoosterMemK");
	$SquidBoosterOnly=$blackboxes->GET_SQUID_INFO("SquidBoosterOnly");
	if(!is_numeric($SquidBoosterMem)){$SquidBoosterMem=0;}
	if(!is_numeric($SquidBoosterMemK)){$SquidBoosterMemK=50;}
	if(!is_numeric($SquidBoosterOnly)){$SquidBoosterOnly=0;}
	$disabled=$tpl->javascript_parse_text("{disabled}");
	if($SquidBoosterMem==0){$SquidBoosterMemText="&nbsp;$disabled";}
	$warn_squid_restart=$tpl->javascript_parse_text("{warn_squid_restart}");
	
	

	$t=time();
	$maxMem=500;
	$CPUS=0;
	$currentMem=intval($blackboxes->TotalMemoryMB);

	if($currentMem>0){
		$maxMem=$currentMem-500;
	}

	$q=new mysql_squid_builder();
	$sql="SELECT * FROM cacheconfig WHERE `uuid`='{$_GET["hostid"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$CPUS=$ligne["workers"];
	if(!is_numeric($CPUS)){$CPUS=$blackboxes->settings_inc["CPU_NUMBER"];}

	

	$html="

	<div class=explain style='font-size:14px;' id='$t-div'>{squid_booster_text}</div>
	<div style='font-size:16px;font-weight:bold;text-align:center;color:#E71010' id='$t-multi'></div>

	<table style='width:99%' class=form>
	<tr>
	<td class=legend style='font-size:16px' widht=1%>{memory}:</td>
	<td width=99%><strong style='font-size:16px' id='$t-value'>{$SquidBoosterMem}M/{$currentMem}M$SquidBoosterMemText</strong><input type='hidden' id='$t-mem' value='$SquidBoosterMem'></td>
	</tr>
	<tr>
	<td colspan=2><div id='slider$t'></div></td>
	</tr>
	</table>



	<table style='width:99%' class=form>
	<tr>
	<td class=legend style='font-size:16px' widht=1% nowrap>{max_objects_size}:</td>
	<td width=99%><strong style='font-size:16px' id='$t-value2'>{$SquidBoosterMemK}K</strong>
	<input type='hidden' id='$t-ko' value='$SquidBoosterMemK'></td>
	</tr>
	<tr>
	<td colspan=2><div id='slider2$t'></div></td>
	</tr>
	<td class=legend style='font-size:16px' widht=1% nowrap>{UseOnlyBooster}:</td>
	<td align=left'>". Field_checkbox("$t-only", 1,$SquidBoosterOnly)."</td>
	</table>
	<div style='margin-top:8px;text-align:right'>". button("{apply}","SaveBooster$t()",18)."</div>



	<script>
	$(document).ready(function(){
	$('#slider$t').slider({ max: $maxMem,step:5,
	value:$SquidBoosterMem,
	slide: function(e, ui) {
	ChangeSlideField$t(ui.value)
},
change: function(e, ui) {
ChangeSlideField$t(ui.value);
}
});

$('#slider2$t').slider({ max: 1000,step:5,
value:$SquidBoosterMemK,
slide: function(e, ui) {
ChangeSlideFieldK$t(ui.value)
},
change: function(e, ui) {
ChangeSlideFieldK$t(ui.value);
}
});


});

	function ChangeSlideField$t(val){
		var disabled='';
		var cpus=$CPUS;
		
		if(val==0){disabled='&nbsp;$disabled';}
		document.getElementById('$t-value').innerHTML=val+'M/{$currentMem}M'+disabled;
		document.getElementById('$t-mem').value=val;
		if(cpus>1){
			var selected_mem=val;
			var newval=selected_mem/cpus;
			newval=newval-10;
			if(newval>0){document.getElementById('$t-multi').innerHTML=newval-10+'M / CPU';}
			
		}
		
	}
	function ChangeSlideFieldK$t(val){
		if(val<10){
		$('#slider2$t').slider( 'option', 'value', 10 );
		val=10;
		}
		document.getElementById('$t-value2').innerHTML=val+'K';
		document.getElementById('$t-ko').value=val
	}

	var x_SaveBooster$t=function(obj){
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
		RefreshTab('hostid_cache_settings');
	}

	function SaveBooster$t(){
		if(confirm('$warn_squid_restart')){
			var XHR = new XHRConnection();
			XHR.appendData('uuid','$uuid');
			XHR.appendData('SquidBoosterMem',document.getElementById('$t-mem').value);
			XHR.appendData('SquidBoosterMemK',document.getElementById('$t-ko').value);
			if(document.getElementById('$t-only').checked){XHR.appendData('SquidBoosterOnly',1);}else{XHR.appendData('SquidBoosterOnly',0);}
			AnimateDiv('$t-div');
			XHR.sendAndLoad('$page', 'POST',x_SaveBooster$t);
		}
	}
ChangeSlideField$t($SquidBoosterMem);
</script>
";
echo $tpl->_ENGINE_parse_body($html);


}

function SaveBooster(){
	
	$blackboxes=new blackboxes($_POST["uuid"]);
	$blackboxes->SET_SQUID_POST_INFO($_POST);
	$blackboxes->reconfigure_squid();
	

}