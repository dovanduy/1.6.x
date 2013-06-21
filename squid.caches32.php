<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.squid.inc');
	
	$users=new usersMenus();
	if(!$users->AsSquidAdministrator){die("NO PRIVS");}
	if(isset($_GET["smp-js"])){smp_js();exit;}
	if(isset($_POST["DEFAULT_CACHE_SAVE_TRUE"])){squid_cache_save_default();exit;} //toujours en premier !
	if(isset($_GET["squid-caches-status"])){squid_cache_status();exit;}
	if(isset($_POST["cachesDirectory"])){squid_cache_save();exit;}
	if(isset($_POST["rebuild-caches"])){rebuild_caches();exit;}
	if(isset($_POST["reindex-caches"])){reindex_caches();exit;}
	if(isset($_POST["cache_directory"])){add_new_disk_save();exit;}
	
	if(isset($_POST["verify-caches"])){verify_caches();exit;}
	if(isset($_GET["add-new-disk-popup"])){add_new_disk_popup();exit;}
	if(isset($_GET["button-mode"])){button_mode();exit;}
	if(isset($_GET["add-new-disk-js"])){add_new_disk_js();exit;}
	if(isset($_GET["verify-caches-logs"])){verfiy_caches_logs();exit;}
	if(isset($_GET["verify-cache-events"])){verfiy_caches_events();exit;}
	if(isset($_POST["delete-cache"])){delete_cache();exit;}
	if(isset($_POST["DisableAnyCache"])){DisableAnyCache();exit;}
	if(isset($_GET["license-explain"])){license_explain();exit;}
	if(isset($_POST["XDisableSquidSNMPMode"])){XDisableSquidSNMPMode();exit;}
	page();



function page(){
		$page=CurrentPageName();
		$squid=new squidbee();
		$tpl=new templates();
		$q=new mysql_squid_builder();
		$sock=new sockets();
		$users=new usersMenus();
		$DisableSquidSNMPMode=$sock->GET_INFO("DisableSquidSNMPMode");
		$DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
		if(!is_numeric($DisableAnyCache)){$DisableAnyCache=0;}
		if(!is_numeric($DisableSquidSNMPMode)){$DisableSquidSNMPMode=1;}
		if($users->CORP_LICENSE){
			if($DisableSquidSNMPMode==0){
				$t=time();
				$html="<div id='$t'></div>
				<script>
					LoadAjax('$t','squid.caches.smp.php?uuid={$_GET["uuid"]}');
				</script>
				";
				echo $html;return;
				
			}
			
		}
		
		
		$sql="SELECT * FROM cacheconfig WHERE `uuid`='{$_GET["uuid"]}'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$CPUS=$ligne["workers"];	
		$cachesDirectory=$ligne["cachesDirectory"];
		$globalCachesize=$ligne["globalCachesize"];	
		if(!is_numeric($globalCachesize)){$globalCachesize=5000;}
		if($cachesDirectory==null){$cachesDirectory="/var/cache";}
		$warning_rebuild_squid_caches=$tpl->javascript_parse_text("{warning_rebuild_squid_caches}");
		$globalCachesizeTOT=(($globalCachesize*1000)*$CPUS);
		$globalCachesize_text=FormatBytes($globalCachesizeTOT);
		
		$sock=new sockets();
		$CPU_NUMBER=$sock->getFrameWork("services.php?CPU-NUMBER=yes");
		if(!is_numeric($CPUS)){$CPUS=$CPU_NUMBER;}
		
		$reindex_caches_warn=$tpl->javascript_parse_text("{reindex_caches_warn}");
		$verify_caches=$tpl->javascript_parse_text("{verify_caches}");
		$delete_cache=$tpl->javascript_parse_text("{delete_cache}");
		$WARN_OPE_RESTART_SQUID_ASK=$tpl->javascript_parse_text("{WARN_OPE_RESTART_SQUID_ASK}");
		$warn_disable_squid_cany_cache=$tpl->javascript_parse_text("{warn_disable_squid_cany_cache}");
		$warning_change_interface_squid=$tpl->javascript_parse_text("{warning_change_interface_squid}");
		$t=time();
		
		$DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
		$CORP=0;
		if($users->CORP_LICENSE){$CORP=1;}
		
		$toolbox="
		<table style='width:99%' class=form>
		<tr>
			<td width=1%><img src='img/reconstruct-database-32.png'></td>
			<td width=99%><a href=\"javascript:blur();\" 
			OnClick=\"javascript:Loadjs('squid.rebuild.caches.php?uuid={$_GET["uuid"]}');\" 
			style='font-size:13px;text-decoration:underline'>{rebuild_caches}</a></td>
		</tr>
		<tr>
			<td width=1%><img src='img/database-error-32.png'></td>
			<td width=99%><a href=\"javascript:blur();\" 
			OnClick=\"javascript:ReindexAllCaches();\" 
			style='font-size:13px;text-decoration:underline'>{reindex_caches}</a></td>
		</tr>		
		<tr>
			<td width=1%><img src='img/service-check-32.png'></td>
			<td width=99%><a href=\"javascript:blur();\" 
			OnClick=\"javascript:VerifyCaches();\" 
			style='font-size:13px;text-decoration:underline'>{verify_caches}</a></td>
		</tr>			
		</table>
		<div class=explain style='margin-top:10px'>{squid32_caches_explain}</div>";
		
		if($DisableAnyCache==1){$toolbox=null;}
		
	$html="
	<div id='$t-license'></div>
	<div id='section_squidcache32'>
	<table style='width:100%'>
	<tbody>
	<tr>
		<td valign='top' width=50%>
	
	
	<div id='caches-32-div'>
		<table style='width:99%' class=form>
		<tr>
			<td class=legend>{DisableSquidSNMPMode}:</td>
			<td>". Field_checkbox("DisableSquidSNMPMode",1,$DisableSquidSNMPMode,"CheckSNMPMode()")."</td>
		</tr>		
		
		<tr>
			<td class=legend>{DisableAnyCache}:</td>
			<td>". Field_checkbox("DisableAnyCache",1,$DisableAnyCache,"CheckDisableAnyCache()")."</td>
		</tr>		
		
		
		<tr>
			<td class=legend>{cache_directory}:</td>
			<td>". Field_text("cachesDirectory",$cachesDirectory,"font-size:12.5px;width:180px")."</td>
		</tr>
		<tr>
			<td colspan=2 align=right><hr>". button("{apply}", "SaveSquid32Caches()",16)."</td>
		</tr>
		</table>
		</div>
		$toolbox

		</div>		
		
	</td>
	<td valign='top'>
		<div id='squid-caches-add-button'></div>
		<div id='squid-caches-status' style='min-height:450px'></div>
		
	</td>
	</tr>
	</tbody>
	</table>
	</div>
	<script>
		ifFnExistsCallIt('SaveSquid32CachesStatus');
		
	var x_SaveSquid32Caches= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);}
			RefreshTab('squid_main_caches_new');
		}	

		function SaveSquid32CachesStatus(){
			LoadAjax('squid-caches-status','$page?squid-caches-status=yes&uuid={$_GET["uuid"]}');
		}
		
		function SaveSquid32Caches(){
			var cachesDirectoryorg='$cachesDirectory';
			var XHR = new XHRConnection();
			var DisableSquidSNMPMode=1;
			var DisableSquidSNMPModeOrg=$DisableSquidSNMPMode;
			if(!document.getElementById('DisableSquidSNMPMode').checked){DisableSquidSNMPMode=0;}
			if( DisableSquidSNMPMode!==DisableSquidSNMPModeOrg ){
				alert('$warning_change_interface_squid');
			}
				
			XHR.appendData('uuid','$squid->uuid');
			XHR.appendData('DisableSquidSNMPMode',DisableSquidSNMPMode);
				
			if(DisableSquidSNMPMode==1){
				if(cachesDirectoryorg!==document.getElementById('cachesDirectory').value){
					if(!confirm('$warning_rebuild_squid_caches')){
						return;
					}
					XHR.appendData('RebuildCachesSave','yes');
				}
			}
				
				
			XHR.appendData('cachesDirectory',document.getElementById('cachesDirectory').value);
			AnimateDiv('caches-32-div');		
			XHR.sendAndLoad('$page', 'POST',x_SaveSquid32Caches);
		}
		
	var x_CheckDisableAnyCache= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);}
			RefreshTab('squid_main_caches_new');
			RefreshTab('main_squid_quicklinks_tabs');
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
		
		

		
	var x_VerifyCaches= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);return;}
			YahooWin3('650','$page?verify-caches-logs=yes','$verify_caches');
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
		
		function checkButtonMode(){
			LoadAjax('squid-caches-add-button','$page?button-mode=yes');
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
			
			
			document.getElementById('cachesDirectory').disabled=true;
			
			var CORP=$CORP;
			if(CORP==0){
				document.getElementById('DisableSquidSNMPMode').checked=true;
				document.getElementById('DisableSquidSNMPMode').disabled=true;
				LoadAjaxTiny('$t-license','$page?license-explain=yes');
			}
			
			
			if(!document.getElementById('DisableSquidSNMPMode').checked){
				document.getElementById('cachesDirectory').disabled=false;
							
			}
			checkButtonMode();
		}
		

		
		function Squid32RefreshCacheStatusAuto1(){
			if(document.getElementById('squid-caches-status')){
				LoadAjax('squid-caches-status','$page?squid-caches-status=yes&uuid={$_GET["uuid"]}');
				
			}		
		}
		
		CheckSNMPMode();
		setTimeout('Squid32RefreshCacheStatusAuto1()',10000);
	</script>
	
	
	";

	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
	
}

function smp_js(){
	header("content-type: application/x-javascript");
	$users=new usersMenus();
	$sock=new sockets();	
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$CPU_NUMBER=$sock->getFrameWork("services.php?CPU-NUMBER=yes");
	$squid_worker_license_explain=$tpl->javascript_parse_text("{squid_worker_license_explain}");
	$squid_worker_activate_explain=$tpl->javascript_parse_text("{squid_worker_activate_explain}");
	if(!$users->CORP_LICENSE){
		$squid_worker_license_explain=$tpl->javascript_parse_text("{squid_worker_license_explain}");
		$squid_worker_license_explain=str_replace("%s","$CPU_NUMBER",$squid_worker_license_explain);
		echo "alert('$squid_worker_license_explain');";
		return;
	}
	
	$DisableSquidSNMPMode=$sock->GET_INFO("DisableSquidSNMPMode");
	if(!is_numeric($DisableSquidSNMPMode)){$DisableSquidSNMPMode=1;}
	
	$uuid=$_GET["uuid"];
	$t=time();
	
	if(!$users->CORP_LICENSE){
		$squid_worker_license_explain=$tpl->javascript_parse_text("{squid_worker_license_explain}");
		$squid_worker_license_explain=str_replace("%s","$CPU_NUMBER",$squid_worker_license_explain);
		echo "alert('$squid_worker_license_explain')";
		die();
	}
	
	if($DisableSquidSNMPMode==1){
		echo "
		var x_EnableWorker$t= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);}
			if(document.getElementById('squid-status')){
				LoadAjax('squid-services','squid.main.quicklinks.php?squid-services=yes');
			}
			Loadjs('squid.compile.progress.php');
			Loadjs('$page?smp-js=yes&uuid={$_GET["uuid"]}');
		}			
		
		
		function EnableWorker$t(){
			if(!confirm('$squid_worker_activate_explain')){return;}
			var XHR = new XHRConnection();
			var DisableSquidSNMPMode=0;
			XHR.appendData('XDisableSquidSNMPMode',DisableSquidSNMPMode);
			XHR.appendData('uuid','$uuid');
			if(document.getElementById('squid-status')){AnimateDiv('squid-status');}
			XHR.sendAndLoad('$page', 'POST',x_EnableWorker$t);
		}


		EnableWorker$t();";
		return;
	}
	
	
	echo "YahooWin3('892','squid.caches.php?byQuicklinks=yes&uuid=$uuid','SMP (multiple-processors)')";
	
}


function license_explain(){
	$users=new usersMenus();
	if($users->CORP_LICENSE){die();}
	$sock=new sockets();
	$CPU_NUMBER=$sock->getFrameWork("services.php?CPU-NUMBER=yes");
	
	
	if($CPU_NUMBER<2){return;}
	
	$tpl=new templates();
	$squid_worker_license_explain=$tpl->_ENGINE_parse_body("{squid_worker_license_explain}");
	$squid_worker_explain=$tpl->_ENGINE_parse_body("{squid_worker_explain}");
	$squid_worker_license_explain=str_replace("%s","$CPU_NUMBER",$squid_worker_license_explain);
	$html="
	<div style='width:95%' class=form>
	<table style='width:99%'>
	<tr>
		<td valign='top' width=1%><img src='img/64-key.png' style='margin:5px'></td>
		<td valign='top' style='font-size:14px;' width=99%>$squid_worker_license_explain
		<br>$squid_worker_explain
		
		</td>
	</tr>
	<tr>
		<td style='font-size:14px' colspan=2 align='right'><a href=\"javascript:Loadjs('artica.license.php');\" 
		style='font-size:14px;text-decoration:underline;font-weight:bold'>&laquo;&nbsp;Artica license&nbsp;&raquo;</a></td>
	</tr>
	</table>	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}


function verfiy_caches_logs(){
	$t=time();
	$page=CurrentPageName();
	$html="<div id='$t' style='min-height:350px;width:95%;overflow:auto' class=form></div>
	
	<script>
		LoadAjax('$t','$page?verify-cache-events=yes&t=$t');
	</script>
	";
	
	echo $html;
}
function verfiy_caches_events(){
	$t=$_GET["t"];
	$page=CurrentPageName();
	if(!is_file("ressources/logs/web/squid.rebuild.infos")){
		echo "<script>
			if(YahooWin3Open()){
				LoadAjax('$t','$page?verify-cache-events=yes&t=$t');
			}
			</script>";
		return;
	}
	
	
	$f=file("ressources/logs/web/squid.rebuild.infos");
	krsort($f);
	while (list ($index, $line) = each ($f) ){
		echo "<div style='font-size:12px'>$line</div>";
		
	}
	
	
	echo "
	<script>
		function ReRefresh$t(){
			LoadAjax('$t','$page?verify-cache-events=yes&t=$t');
		}
		
		setTimeout('ReRefresh$t()',10000);
	</script>
	
	";
	
}


function verify_caches(){
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?squid-build-caches-output=yes");
}

function add_new_disk_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	if(!$_SESSION["CORP"]){
		$tpl=new templates();
		$onlycorpavailable=$tpl->javascript_parse_text("{onlycorpavailable}");
		$content="alert('$onlycorpavailable')";
		echo $content;	
		return;
	}	
		
	
	$title=$tpl->_ENGINE_parse_body("{add_new_cache_container}");
	if(isset($_GET["chdef"])){
		$title=$tpl->_ENGINE_parse_body("{default_cache}");
		$chdef="&chdef=yes";}
	
	
	$html="YahooWin3('818.6','$page?add-new-disk-popup=yes$chdef','$title')";
	echo $html;
}


function button_mode(){
		$page=CurrentPageName();
		$tpl=new templates();
		$sock=new sockets();
		$DisableSquidSNMPMode=$sock->GET_INFO("DisableSquidSNMPMode");
		$DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
		if(!is_numeric($DisableSquidSNMPMode)){$DisableSquidSNMPMode=1;}
		if(!is_numeric($DisableAnyCache)){$DisableAnyCache=0;}
		if($DisableSquidSNMPMode==0){return null;}
		if($DisableAnyCache==1){return null;}
		
		$js="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$page?add-new-disk-js=yes');\" style='font-size:14px;font-weight:bold;text-decoration:underline'>";
		
$html="
			<table style='width:99%' class=form>
			<tbody>
				<tr>
					<td width=1%>".imgtootltip("disk-add-64.png","{add_new_cache_container}","Loadjs('$page?add-new-disk-js=yes');")."</td>
					<td valign='top'>
						<table style='width:100%'>
						<tbody>
						<tr>
							<td valign=top>$js{add_new_cache_container}</strong></a></td>
						</tr>
						<tr>
							<td valign='top'><strong style='font-size:12px'>{add_new_cache_container_text}</td>
						</tr>
						<tr>
							<td valign='top'>&nbsp;</td>
						</tr>
						</tbody>
						</table>
					</td>
			</tr>
			</tbody>
			</table>";
		echo $tpl->_ENGINE_parse_body($html);
		
}


function rebuild_caches(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("TRUNCATE table cachestatus");
	$sock=new sockets();
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{operation_launched_in_background}");	
	$sock->getFrameWork("squid.php?rebuild-caches=yes");
}
function reindex_caches(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("TRUNCATE table cachestatus");
	$sock=new sockets();
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{operation_launched_in_background}");	
	$sock->getFrameWork("squid.php?reindex-caches=yes");	
	
}


function squid_cache_status(){

		$sock=new sockets();
		$DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
		if(!is_numeric($DisableAnyCache)){$DisableAnyCache=0;}	
		if($DisableAnyCache==1){return;}	
		$page=CurrentPageName();
		$squid=new squidbee();
		$tpl=new templates();		
		$t=time();
		$q=new mysql_squid_builder();
		
		$exec_squid_rebuild_cache_mem=unserialize(base64_decode($sock->getFrameWork("squid.php?exec_squid_rebuild_cache_mem=yes")));
		if(isset($exec_squid_rebuild_cache_mem["PID"])){
			if($exec_squid_rebuild_cache_mem["PID"]>0){
				$datas=@file_get_contents("ressources/logs/web/rebuild-cache.txt");
				
				$tt=time();
				$html="
				<div style='width:100%;text-align:right'>".imgtootltip("refresh-32.png","{refresh}","LoadAjax('squid-caches-status','$page?squid-caches-status=yes&uuid={$_GET["uuid"]}');")."</div>
				<table style='width:99%' class=form>
				<tr>
					<td width=1%><img src='img/wait_verybig_mini_red-48.gif'></td>
					<td style='font-size:16px'>{exec_squid_rebuild_cache_mem}</div>
						<div style='font-size:14px;font-weight:bold'>{pid}:{$exec_squid_rebuild_cache_mem["PID"]}, {since} {$exec_squid_rebuild_cache_mem["TIME"]}</div>
					</td>
				</tr>
				</table>	
					
				";
				$tb=array();
				$tb=explode("\n",$datas);
				krsort($tb);
				if(strlen($datas)>100){$html=$html."<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;overflow:auto;font-size:11.5px'
	id='textarea$t'>".@implode("\n", $tb)."</textarea>";
				}
				echo $tpl->_ENGINE_parse_body($html);
				return;
				
				
			}
		}
		
		$sql="SELECT * FROM cachestatus WHERE uuid='{$_GET["uuid"]}'";
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){echo "<H3>Error: $this->mysql_error</H3>";return;}
		
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
			$strong="<strong style='font-size:14px'>";
			if(basename($ligne["cachedir"])=="cache_booster"){
				$strong="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.booster.php')\" style='font-size:14px;text-decoration:underline;font-weight:bold'>";
			}
			
			$delete=imgtootltip("disk-64-delete.png","{delete_cache}","squid32DeleteCache('".base64_encode($ligne["cachedir"])."')");
			
			$NICKEL[$ligne["cachedir"]]=true;
			if($ligne["cachedir"]==$squid->CACHE_PATH){
				$cache_type=$squid->CACHE_TYPE;
				$delete=imgtootltip("disk-64-config.png","{edit}","Loadjs('$page?add-new-disk-js=yes&chdef=yes')");
			}else{
				$cache_type=$squid->cache_list[$ligne["cachedir"]]["cache_type"];
				
				
			}
			
			if($ligne["cachedir"]<>$squid->CACHE_PATH){
				if(!isset($squid->cache_list[$ligne["cachedir"]])){
					$delete="<img src='img/disk-64-hide.png'>";
				}
			}
			
			$html=$html."
			
			<table style='width:99%' class=form>
			<tbody>
			<tr>
				<td width=1%>$delete</td>
				<td valign='top'>
					<table style='width:100%'>
					<tbody>
					<tr>
						<td valign=top>$strong". basename($ligne["cachedir"])."&nbsp;($cache_type)</strong></a></td>
					</tr>
					<tr>
						<td valign='top'><strong style='font-size:14px'>". FormatBytes($ligne["currentsize"])."/". FormatBytes($ligne["maxsize"])."</strong><div>{$ligne["cachedir"]}</div></td>
					</tr>
					<tr>
						<td valign='top'>". pourcentage($ligne["pourc"])."</td>
					</tr>
					
					</tbody>
					</table>
				</td>
			</tr>
			</tbody>
			</table>";
	}
	
	while (list ($path, $array) = each ($squid->cache_list) ){
			if(isset($NICKEL[$path])){continue;}
			$unit="&nbsp;MB";
			$maxcachesize=null;
			if($array["cache_type"]=="rock"){$maxcachesize="&nbsp;({max_objects_size} {$array["cache_maxsize"]}$unit)";}
			if(is_numeric($array["cache_size"])){if($array["cache_size"]>1000){$array["cache_size"]=$array["cache_size"]/1000;$unit="&nbsp;GB";}}
			if($array["cache_type"]=="rock"){continue;}
			$html=$html."
			<table style='width:99%' class=form>
			<tbody>
			<tr>
				<td width=1%><img src='img/disk-64-hide.png'></td>
				<td valign='top'>
					<table style='width:100%'>
					<tbody>
					<tr>
						<td valign=top>$strong". basename($path)." ({$array["cache_type"]})</strong></a></td>
					</tr>
					<tr>
						<td valign='top'><strong style='font-size:14px'>{$array["cache_size"]}$unit/$maxcachesize</strong><div>$path</div></td>
					</tr>
					<tr>
						<td valign='top'>&nbsp;</td>
					</tr>
					</tbody>
					</table>
				</td>
			</tr>
			</tbody>
			</table>";			
	}
	

	$html=$html."
	<div style='width:100%;text-align:right'>".imgtootltip("refresh-32.png","{refresh}","LoadAjax('squid-caches-status','$page?squid-caches-status=yes&uuid={$_GET["uuid"]}');")."</div>
	
	<script>
		function Squid32RefreshCacheStatusAuto$t(){
			if(document.getElementById('squid-caches-status')){
				LoadAjax('squid-caches-status','$page?squid-caches-status=yes&uuid={$_GET["uuid"]}');
			}		
		}
		
		setTimeout('Squid32RefreshCacheStatusAuto$t',10000);
		
	</script>
	

		
	
	";
	$sock=new sockets();
	$sock->getFrameWork("squid.php?refresh-caches-infos=yes");
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function squid_cache_save(){
	$uuid=$_POST["uuid"];
	$sock=new sockets();
	$sock->SET_INFO("DisableSquidSNMPMode", $_POST["DisableSquidSNMPMode"]);
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM cachestatus WHERE uuid='$uuid'");

	$sql="UPDATE cacheconfig SET cachesDirectory='{$_POST["cachesDirectory"]}' WHERE uuid='$uuid'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	
	if($_POST["DisableSquidSNMPMode"]==0){
		if(isset($_GET["RebuildCachesSave"])){
			$sock->getFrameWork("squid.php?rebuild-caches=yes");
		}
	}
}

function XDisableSquidSNMPMode(){
	$sock=new sockets();
	$sock->SET_INFO("DisableSquidSNMPMode", 0);	
	$q=new mysql_squid_builder();
	$uuid=$_POST["uuid"];
	$q->QUERY_SQL("DELETE FROM cachestatus WHERE uuid='$uuid'");	
	$sock->getFrameWork("squid.php?refresh-caches-infos=yes");
}

function add_new_disk_popup(){
	$t=time();
	$page=CurrentPageName();
	$squid=new squidbee();
	$tpl=new templates();
	$sock=new sockets();
	$caches_types=unserialize(base64_decode($sock->getFrameWork("squid.php?caches-types=yes")));
	$caches_types[null]='{select}';
	unset($caches_types["rock"]);
	$type=$tpl->_ENGINE_parse_body(Field_array_Hash($caches_types,"cache_type-$t",
	"aufs","CheckCachesTypes()",null,0,"font-size:16px;padding:3px"));
	$WARN_OPE_RESTART_SQUID_ASK=$tpl->javascript_parse_text("{WARN_OPE_RESTART_SQUID_ASK}");
	$currentsize=Calculate_maxcachessize();
	$maxCacheSizeInt=(250*1000)-$currentsize;
	$maxCacheSize=$maxCacheSizeInt/1000;
	
	$DefaultmaxCacheSize=round($maxCacheSize/4,1);
	$NextCache=count($squid->cache_list)+1;
	$defaultCachedir="/home/squid/cache/squid0{$NextCache}";
	
	$cachedirtext="
		<tr>
		<td class=legend style='font-size:16px' nowrap>{directory}:</td>
		<td>" . Field_text("cache_directory-$t",$defaultCachedir,"width:270px;font-size:16px;padding:3px")."</td>
		<td>". button("{browse}...", "Loadjs('SambaBrowse.php?no-shares=yes&field=cache_directory-$t')",12)."</td>
		</tr>";
	
	$SliderDef=10;
	$cache_dir_level1_def=16;
	$LockOthers=0;
	if(isset($_GET["chdef"])){
		$cachedirtext="<tr>
		<td class=legend style='font-size:16px' nowrap>{directory}:</td>
		<td><strong style='font-size:14px'>$squid->CACHE_PATH</strong>". Field_hidden("cache_directory-$t", $squid->CACHE_PATH)."</td>
		<td>&nbsp;</td>
		</tr>";
		
		if($squid->CACHE_TYPE==null){$squid->CACHE_TYPE="diskd";}
		
		$type=$tpl->_ENGINE_parse_body(Field_array_Hash($caches_types,"cache_type-$t",
		$squid->CACHE_TYPE,"CheckCachesTypes()",null,0,"font-size:16px;padding:3px"));
		$SliderDef=round($squid->CACHE_SIZE/1000);
		$DefaultmaxCacheSize=$squid->CACHE_SIZE/1000;
		$XHRADD="XHR.appendData('DEFAULT_CACHE_SAVE_TRUE','OK');";
		$LockOthers=1;
		
	}
	
	$html="	<div id='waitcache-$t'></div>
	<input type='hidden' name='squid-cache-size-$t' id='squid-cache-size-$t' value='10'>
	<div style='width:95%' class=form>
	<table style='width:99%'>
		$cachedirtext
		<tr>
			<td class=legend style='font-size:16px' nowrap>{type}:</td>
			<td>$type</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td class=legend style='font-size:16px' nowrap>{cache_size}:</td>
			<td style='font-size:16px'><div id='slider$t'></div></td>
			<td>&nbsp;<strong style='font-size:16px' id='$t-value'>{$DefaultmaxCacheSize}G/{$maxCacheSize}G</strong><input type='hidden' id='$t-mem' value='$SquidBoosterMem'></td>
			<td>" . help_icon('{cache_size_text}',false,'squid.index.php')."</td>
		</tr>
		<tr>
		<td colspan=4><div class=explain>{warn_calculate_nothdsize}</div></td>		

		<tr>
			<td class=legend nowrap style='font-size:16px'>{cache_dir_level1}:</td>
			<td>" . Field_text("cache_dir_level1-$t",16,'width:50px;font-size:16px;padding:3px')."</td>
			<td>&nbsp;</td>
			<td>" . help_icon('{cache_dir_level1_text}',false,'squid.index.php')."</td>
		</tr>			
		<tr>
			<td class=legend nowrap style='font-size:16px'>{cache_dir_level2}:</td>
			<td>" . Field_text("cache_dir_level2-$t",256,'width:50px;font-size:16px;padding:3px')."</td>
			<td>&nbsp;</td>
			<td>" . help_icon('{cache_dir_level2_text}',false,'squid.index.php')."</td>
		</tr>
		<tr>
			<td class=legend nowrap style='font-size:16px'>{max_objects_size}:</td>
			<td  style='font-size:16px'>" . Field_text("cache_maxsize-$t",$s->cache_list[$cache]["cache_maxsize"],'width:50px;font-size:16px;padding:3px',null,"calculateSize()",null,false,null)."&nbsp;Mbytes&nbsp;<span id='squid-maxsize-vals'></span></td>
			<td>&nbsp;</td>
			<td>" . help_icon('{squid_rock_maxsize}',false,'squid.index.php')."</td>
		</tr>
		
		<tr>
		<td align='right' colspan=4><hr>". button('{apply}',"AddNewCacheSave$t()",14)."</td>
		</tr>
	</table>
	</div>
<script>
		$(document).ready(function(){
			$('#slider$t').slider({ max: $maxCacheSize,step:2,value:$SliderDef,slide: function(e, ui) {ChangeSlideField$t(ui.value)},change: function(e, ui) {ChangeSlideField$t(ui.value);} });
		});
		
		function ChangeSlideField$t(val){
			var disabled='';
			if(val==0){disabled='&nbsp;$disabled';}
			document.getElementById('$t-value').innerHTML=val+'G/{$maxCacheSize}G'+disabled;
			document.getElementById('squid-cache-size-$t').value=val;
		}		


		function CheckCachesTypes(){
			cachetypes=document.getElementById('cache_type-$t').value;
			var LockOthers=$LockOthers;
			if(LockOthers==1){
				document.getElementById('cache_dir_level2-$t').disabled=true;
				document.getElementById('cache_dir_level1-$t').disabled=true;
				document.getElementById('cache_maxsize-$t').disabled=true;
			}
		}
		
		
	var x_AddNewCacheSave$t= function (obj) {
			var results=obj.responseText;
			if(results.length>0){
				alert(results);
				document.getElementById('waitcache').innerHTML='';
				}
			YahooWin3Hide();
			SaveSquid32CachesStatus();
			ExecuteByClassName('SearchFunction');
		}		
	
	function AddNewCacheSave$t(){
		if(confirm('$WARN_OPE_RESTART_SQUID_ASK')){
			var XHR = new XHRConnection();
			$XHRADD
			XHR.appendData('cache_directory',document.getElementById('cache_directory-$t').value);
			XHR.appendData('cache_type',document.getElementById('cache_type-$t').value);
			XHR.appendData('size',document.getElementById('squid-cache-size-$t').value);
			XHR.appendData('cache_dir_level1',document.getElementById('cache_dir_level1-$t').value);
			XHR.appendData('cache_dir_level2',document.getElementById('cache_dir_level2-$t').value);
			XHR.appendData('cache_maxsize',document.getElementById('cache_maxsize-$t').value);
			AnimateDiv('waitcache-$t');
			XHR.sendAndLoad('$page', 'POST',x_AddNewCacheSave$t);
			}
		}		
		CheckCachesTypes();
</script>";
echo $tpl->_ENGINE_parse_body($html);	
	
}

function squid_cache_save_default(){
	$squid=new squidbee();
	$squid->CACHE_SIZE=$_POST["size"]*1000;
	$squid->CACHE_TYPE=$_POST["cache_type"];
	$sock=new sockets();
	$squid->SaveToLdap(true);
	$squid->SaveToServer(true);		
	$sock->getFrameWork("squid.php?squid-build-default-caches=yes");
}


function add_new_disk_save(){
	
	if(!$_SESSION["CORP"]){
		$tpl=new templates();
		$onlycorpavailable=$tpl->javascript_parse_text("{onlycorpavailable}");
		$content="alert('$onlycorpavailable')";
		echo $onlycorpavailable;	
		return;
	}		
	
	if($_POST["cache_directory"]==null){echo "False:cache directory is null\n";exit;}
	$squid=new squidbee();
	if(isset($_GET["main-is-cache"])){
		$squid->CACHE_PATH=$_POST["cache_directory"];
		$squid->CACHE_SIZE=$_POST["size"]*1000;
		$squid->CACHE_TYPE=$_POST["cache_type"];
	
		
	}else{
		$squid->cache_list[$_POST["cache_directory"]]=array(
		"cache_type"=>$_POST["cache_type"],
		"cache_dir_level1"=>$_POST["cache_dir_level1"],
		"cache_dir_level2"=>$_POST["cache_dir_level2"],
		"cache_size"=>$_POST["size"]*1000,
		"cache_maxsize"=>$_POST["cache_maxsize"]
		);
	}
	$sock=new sockets();
	$squid->SaveToLdap(true);
	$squid->SaveToServer(true);
	$sock->getFrameWork("cmd.php?squid-build-caches=yes");

}

function delete_cache(){
	$cachedir=base64_decode($_POST["delete-cache"]);
	$squid=new squidbee();
	unset($squid->cache_list[$cachedir]);
	
	$sock=new sockets();
	$squid->SaveToLdap(true);
	$squid->SaveToServer(true);
	$sock->getFrameWork("squid.php?remove-cache={$_POST["delete-cache"]}");	
}



function Calculate_maxcachessize(){
	$squid=new squidbee();
	$c=0;
	while (list ($path, $array) = each ($squid->cache_list) ){
		if(is_numeric($array["cache_size"])){$c=$c+$array["cache_size"];}
	}
	
	return $c;
	
}

function DisableAnyCache(){
	$sock=new sockets();
	$sock->SET_INFO('DisableAnyCache',$_POST["DisableAnyCache"]);
}
?>