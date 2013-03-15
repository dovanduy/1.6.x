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
	include_once('ressources/class.blackboxes.inc');
	
	$users=new usersMenus();
	if(!$users->AsSquidAdministrator){die("NO PRIVS");}
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
	
	if(isset($_GET["cache-items"])){cache_items();exit;}
	
	table();

	
	
function table(){
	
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=400;
	$TB_WIDTH=830;
	$uid=$_GET["uid"];
		
	$t=time();
	$cache=$tpl->javascript_parse_text("{cache}");
	$type=$tpl->_ENGINE_parse_body("{type}");
//	$title=$tpl->_ENGINE_parse_body("$attachments_storage {items}:&nbsp;&laquo;$size&raquo;");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$action_delete_rule=$tpl->javascript_parse_text("{action_delete_rule}");
	$enable=$tpl->_ENGINE_parse_body("{enable}");
	$compile_rules=$tpl->_ENGINE_parse_body("{compile_rules}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$error_want_operation=$tpl->javascript_parse_text("{error_want_operation}");
	$events=$tpl->javascript_parse_text("{events}");
	$category=$tpl->javascript_parse_text("{category}");
	$title=$tpl->javascript_parse_text("{video_title}");
	$size=$tpl->javascript_parse_text("{size}");
	$duration=$tpl->javascript_parse_text("{duration}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$new_cache=$tpl->javascript_parse_text("{add_new_cache_container}");
	$title=$tpl->javascript_parse_text("{cache_central_for_all_nodes}");
	$WARN_OPE_RESTART_SQUID_ASK=$tpl->javascript_parse_text("{WARN_OPE_RESTART_SQUID_ASK}");
	$delete_cache=$tpl->javascript_parse_text("{delete_cache}");
	$verify_caches=$tpl->javascript_parse_text("{verify_caches}");
	$warning_rebuild_squid_caches=$tpl->javascript_parse_text("{warning_rebuild_squid_caches}");
	$reindex_caches_warn=$tpl->javascript_parse_text("{reindex_caches_warn}");
	$rebuild_caches_warn=$tpl->javascript_parse_text("{rebuild_caches_warn}");
	$reindex_caches=$tpl->javascript_parse_text("{reindex_caches}");
	$rebuild_caches=$tpl->javascript_parse_text("{rebuild_caches}");
	$buttons="
	buttons : [
		{name: '$new_cache', bclass: 'Add', onpress : CreateCache$t},
		{name: '$verify_caches', bclass: 'Reconf', onpress : VerifyCaches$t},
		{name: '$reindex_caches', bclass: 'Reconf', onpress : ReindexAllCaches$t},
		{name: '$rebuild_caches', bclass: 'Reconf', onpress : RebuildAllCaches$t},
	],	";
	$html="
	
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?cache-items=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'delete', width :35, sortable : true, align: 'center'},
		{display: '$cache', name : 'cache', width :470, sortable : true, align: 'left'},
		{display: '$type', name : 'type', width :78, sortable : true, align: 'left'},	
		{display: '$size', name : 'size', width :107, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'delete', width :35, sortable : true, align: 'center'},
		
	
	],
	$buttons

	searchitems : [
		{display: '$cache', name : 'cache'},
	],
	sortname: 'size',
	sortorder: 'desc',
	usepager: true,
	title: '<span id=\"title-$t\">$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

function CreateCache$t(){
	Loadjs('$page?add-new-disk-js=yes&t=$t');
}
	var x_VerifyCaches$t= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);return;}
		}		
		
	function VerifyCaches$t(){
		if(confirm('$WARN_OPE_RESTART_SQUID_ASK')){
			var XHR = new XHRConnection();
			XHR.appendData('verify-caches','yes');	
			XHR.sendAndLoad('$page', 'POST',x_VerifyCaches$t);
		}
		
	}
	
	function ReindexAllCaches$t(){
		if(confirm('$reindex_caches_warn')){
			var XHR = new XHRConnection();
			XHR.appendData('reindex-caches','yes');
			XHR.sendAndLoad('$page', 'POST',x_VerifyCaches$t);
		}		
	}

	function RebuildAllCaches$t(){
		if(confirm('$rebuild_caches_warn')){
			var XHR = new XHRConnection();
			XHR.appendData('rebuild-caches','yes');
			XHR.sendAndLoad('$page', 'POST',x_VerifyCaches$t);
		}
	}	

	var x_DeleteCache$t= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		$('#row'+mem$t).remove();
	}

function DeleteCache$t(path,md){
	mem$t=md;
	if(confirm('$delete_cache ?')){
		var XHR = new XHRConnection();
		XHR.appendData('delete-cache',path);
		XHR.sendAndLoad('$page', 'POST',x_DeleteCache$t);			
	}
}

</script>";
	
	echo $html;	
	
	
	
}

function cache_items(){
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$users=new usersMenus();
	$sock=new sockets();
	$squid=new squidbee();
	
	
	$page=1;
	$FORCE_FILTER=null;
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	

	$c=0;
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	

	if($squid->CACHE_SIZE>1000){
		$squid->CACHE_SIZE=$squid->CACHE_SIZE/1000;
		$unit="&nbsp;GB";
	}		
	
	$zmd5=md5($squid->CACHE_PATH);
	
	$c++;
	$data['rows'][] = array(
		'id' => "$zmd5",
		'cell' => array(
			"<img src='img/disk-32.png'>",
			"<span style='font-size:16px;color:$color'><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?add-new-disk-js=yes&chdef=yes&t=$t')\"
			style='text-decoration:underline;font-size:16px;color:$color'>{$squid->CACHE_PATH}</a></span>",
			"<span style='font-size:16px;color:$color'>{$squid->CACHE_TYPE}</a></span>",
			"<span style='font-size:16px;color:$color'>$squid->CACHE_SIZE$unit</span>",
			"&nbsp;"
			)
		);
	

	while (list ($path, $array) = each ($squid->cache_list) ){
		$zmd5=md5($path);
		$unit="&nbsp;MB";
		$maxcachesize=null;
		if($array["cache_type"]=="rock"){
			$maxcachesize="&nbsp;({max_objects_size} {$array["cache_maxsize"]}$unit)";
		}
			
			
			
		if(is_numeric($array["cache_size"])){
			if($array["cache_size"]>1000){
				$array["cache_size"]=$array["cache_size"]/1000;
				$unit="&nbsp;GB";
			}
		}
							
		$js="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?add-new-disk-js=yes&chdef=yes&t=$t')\"
			style='text-decoration:underline;font-size:16px;color:$color'>";
		
		$color="black";
		$pathenc=base64_encode($path);
		$delete=imgsimple("delete-32.png","{delete}","DeleteCache$t('$pathenc','$zmd5')");
		$c++;
		$data['rows'][] = array(
			'id' => "$zmd5",
			'cell' => array(
				"<img src='img/disk-32.png'>",
				"<span style='font-size:16px;color:$color'>$path</a></span>",
				"<span style='font-size:16px;color:$color'>{$array["cache_type"]}</a></span>",
				"<span style='font-size:16px;color:$color'>{$array["cache_size"]}$unit</span>",
				"$delete"
				)
			);
	}
	
	$data['total'] = $c;
echo json_encode($data);	
	
}



function page(){
		$page=CurrentPageName();
		$squid=new squidbee();
		$tpl=new templates();
		$q=new mysql_squid_builder();
		$sock=new sockets();
		$users=new usersMenus();
		$DisableSquidSNMPMode=$sock->GET_INFO("DisableSquidSNMPMode");
		$DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
		if(!is_numeric($DisableSquidSNMPMode)){$DisableSquidSNMPMode=1;}
		if(!is_numeric($DisableAnyCache)){$DisableAnyCache=0;}
		$sql="SELECT * FROM cacheconfig WHERE `uuid`='{$_GET["uuid"]}'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$CPUS=$ligne["workers"];	
		$cachesDirectory=$ligne["cachesDirectory"];
		$globalCachesize=$ligne["globalCachesize"];	
		if(!is_numeric($globalCachesize)){$globalCachesize=5000;}
		if($cachesDirectory==null){$cachesDirectory="/var/cache";}
		
		$globalCachesizeTOT=(($globalCachesize*1000)*$CPUS);
		$globalCachesize_text=FormatBytes($globalCachesizeTOT);
		
		
		
		$delete_cache=$tpl->javascript_parse_text("{delete_cache}");
		$WARN_OPE_RESTART_SQUID_ASK=$tpl->javascript_parse_text("{WARN_OPE_RESTART_SQUID_ASK}");
		$warn_disable_squid_cany_cache=$tpl->javascript_parse_text("{warn_disable_squid_cany_cache}");
		
		$DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
		$CORP=0;
		if($users->CORP_LICENSE){$CORP=1;}
		
		$toolbox="
		<table style='width:99%' class=form>
		<tr>
			<td width=1%><img src='img/reconstruct-database-32.png'></td>
			<td width=99%><a href=\"javascript:blur();\" 
			OnClick=\"javascript:RebuildAllCaches();\" 
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
			<td class=legend>{number_of_daemons}:</td>
			<td>". Field_text("workers",$CPUS,"font-size:16px;width:60px")."</td>
		</tr>	
		<tr>
			<td class=legend>{cache_size_by_daemon}:</td>
			<td style='font-size:16px;'>". Field_text("globalCachesize",$globalCachesize,"font-size:16px;width:60px")."&nbsp;MB&nbsp;($globalCachesize_text)</td>
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
		SaveSquid32CachesStatus();
		
	var x_SaveSquid32Caches= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);}
			RefreshTab('squid_main_caches_new');
		}	

		function SaveSquid32CachesStatus(){
			LoadAjax('squid-caches-status','$page?squid-caches-status=yes&uuid={$_GET["uuid"]}');
		}
		
		function SaveSquid32Caches(){
			if(confirm('$warning_rebuild_squid_caches')){
				var XHR = new XHRConnection();
				if(document.getElementById('DisableSquidSNMPMode').checked){XHR.appendData('DisableSquidSNMPMode','1');}else{XHR.appendData('DisableSquidSNMPMode','0');}
				XHR.appendData('uuid','$squid->uuid');
				XHR.appendData('cachesDirectory',document.getElementById('cachesDirectory').value);
				XHR.appendData('workers',document.getElementById('workers').value);
				XHR.appendData('globalCachesize',document.getElementById('globalCachesize').value);
				AnimateDiv('caches-32-div');		
				XHR.sendAndLoad('$page', 'POST',x_SaveSquid32Caches);
			}
		
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
			
			document.getElementById('workers').disabled=true;
			document.getElementById('cachesDirectory').disabled=true;
			document.getElementById('globalCachesize').disabled=true;
			var CORP=$CORP;
			if(CORP==0){document.getElementById('DisableSquidSNMPMode').checked=true;document.getElementById('DisableSquidSNMPMode').disabled=true;}
			
			
			if(!document.getElementById('DisableSquidSNMPMode').checked){
				document.getElementById('workers').disabled=false;
				document.getElementById('cachesDirectory').disabled=false;
				document.getElementById('globalCachesize').disabled=false;				
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
	
	
	$html="YahooWin3('818.6','$page?add-new-disk-popup=yes$chdef&t={$_GET["t"]}','$title')";
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
	$blk=new blackboxes();
	$blk->NotifyAll("REBUILD_CACHE");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{all_nodes_has_been_notified}");
}
function reindex_caches(){
	$blk=new blackboxes();
	$blk->NotifyAll("REINDEX_CACHE");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{all_nodes_has_been_notified}");
	//VERIFY_CACHE
}
function verify_caches(){
	$blk=new blackboxes();
	$blk->NotifyAll("VERIFY_CACHE");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{all_nodes_has_been_notified}");
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

	$sql="UPDATE cacheconfig SET `workers`='{$_POST["workers"]}',
	cachesDirectory='{$_POST["cachesDirectory"]}',
	globalCachesize='{$_POST["globalCachesize"]}' WHERE uuid='$uuid'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	
}

function add_new_disk_popup(){
	$t=$_GET["t"];
	$page=CurrentPageName();
	$squid=new squidbee();
	$tpl=new templates();
	$sock=new sockets();
	
	$caches_types[null]='{select}';
	$caches_types["aufs"]="aufs";
	$caches_types["diskd"]="diskd";
	unset($caches_types["rock"]);
	$type=$tpl->_ENGINE_parse_body(Field_array_Hash($caches_types,"cache_type-$t",
	"aufs","CheckCachesTypes()",null,0,"font-size:16px;padding:3px"));
	$WARN_OPE_RESTART_SQUID_ASK=$tpl->javascript_parse_text("{WARN_OPE_RESTART_SQUID_ASK}");
	$currentsize=Calculate_maxcachessize();
	$maxCacheSizeInt=(250*1000)-$currentsize;
	$maxCacheSize=50;
	
	
	
	
	$DefaultmaxCacheSize=round($maxCacheSize/4,1);
	$NextCache=count($squid->cache_list)+1;
	$defaultCachedir="/home/squid/cache/squid0{$NextCache}";
	
	$cachedirtext="
		<tr>
		<td class=legend style='font-size:16px' nowrap>{directory}:</td>
		<td>" . Field_text("cache_directory-$t",$defaultCachedir,"width:270px;font-size:16px;padding:3px")."</td>
		<td></td>
		</tr>";
	
	$btname="{add}";
	$SliderDef=10;
	$cache_dir_level1_def=16;
	$LockOthers=0;
	if(isset($_GET["chdef"])){
		$cachedirtext="<tr>
		<td class=legend style='font-size:16px' nowrap>{directory}:</td>
		<td><strong style='font-size:14px'>$squid->CACHE_PATH</strong>". Field_hidden("cache_directory-$t", $squid->CACHE_PATH)."</td>
		<td>&nbsp;</td>
		</tr>";
		$btname="{apply}";
		$type=$tpl->_ENGINE_parse_body(Field_array_Hash($caches_types,"cache_type-$t",
		$squid->CACHE_TYPE,"CheckCachesTypes()",null,0,"font-size:16px;padding:3px"));
		$SliderDef=round($squid->CACHE_SIZE/1000);
		$DefaultmaxCacheSize=$squid->CACHE_SIZE/1000;
		$XHRADD="XHR.appendData('DEFAULT_CACHE_SAVE_TRUE','OK');";
		$LockOthers=1;
		
	}
	
	$html="	<div id='waitcache-$t'></div>
	<input type='hidden' name='squid-cache-size-$t' id='squid-cache-size-$t' value='10'>
	<table style='width:99%' class=form>
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
		<td colspan=4><strong>{warn_calculate_nothdsize}</strong></td>		

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
		<td align='right' colspan=4><hr>". button($btname,"AddNewCacheSave$t()",14)."</td>
		</tr>
	</table>
	
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
			$('flexRT$t').flexReload();
			RefreshTab('squid_main_caches_new');
		}		
	
	function AddNewCacheSave$t(){
		
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
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
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
	

}

function delete_cache(){
	$cachedir=base64_decode($_POST["delete-cache"]);
	$squid=new squidbee();
	unset($squid->cache_list[$cachedir]);
	$sock=new sockets();
	$squid->SaveToLdap(true);
	$squid->SaveToServer(true);
		
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