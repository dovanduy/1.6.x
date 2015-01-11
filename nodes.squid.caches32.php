<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
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
	if(isset($_GET["squid-caches-status"])){squid_cache_status();exit;}
	if(isset($_POST["cachesDirectory"])){squid_cache_save();exit;}
	if(isset($_POST["rebuild-caches"])){rebuild_caches();exit;}
	
	
	page();



function page(){
		$page=CurrentPageName();
		$squid=new squidnodes($_GET["nodeid"]);
		$tpl=new templates();
		
		
		
		$CPUS=$squid->cpu_number;	
		$cachesDirectory=$squid->GET("cachesDirectory");
		$globalCachesize=$squid->GET("globalCachesize");	
		if(!is_numeric($globalCachesize)){$globalCachesize=5000;}
		if($cachesDirectory==null){$cachesDirectory="/var/cache";}
		$warning_rebuild_squid_caches=$tpl->javascript_parse_text("{warning_rebuild_squid_caches}");
		$globalCachesizeTOT=(($globalCachesize*1000)*$CPUS);
		$globalCachesize_text=FormatBytes($globalCachesizeTOT);
		$rebuild_caches_warn=$tpl->javascript_parse_text("{rebuild_caches_warn}");
		$t=time();
	$html="
	<div style='font-size:18px'>{$squid->visible_hostname}::Caches</div>
	<div class=text-info style='font-size:14px'>{squid32_caches_explain}</div>
	<div id='squid-caches-status$t'></div>
	<script>
		LoadAjax('squid-caches-status$t','$page?squid-caches-status=yes&nodeid={$_GET["nodeid"]}&hostid={$_GET["hostid"]}&t=$t');
		
	var x_SaveSquid32Caches$t= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);}
			RefreshTab('squid_main_caches_new');
		}		
		
		function SaveSquid32Caches$t(){
			if(confirm('$warning_rebuild_squid_caches')){
				var XHR = new XHRConnection();
				
				XHR.appendData('nodeid','{$_GET["nodeid"]}');
				XHR.appendData('cachesDirectory',document.getElementById('cachesDirectory').value);
				XHR.appendData('workers',document.getElementById('workers').value);
				XHR.appendData('globalCachesize',document.getElementById('globalCachesize').value);
				AnimateDiv('caches-32-div');		
				XHR.sendAndLoad('$page', 'POST',x_SaveSquid32Caches);
			}
		
		}
		
		function RebuildAllCaches$t(){
			if(confirm('$rebuild_caches_warn')){
				var XHR = new XHRConnection();
				XHR.appendData('rebuild-caches','yes');
				XHR.appendData('nodeid','{$_GET["nodeid"]}');
				AnimateDiv('caches-32-div');		
				XHR.sendAndLoad('$page', 'POST',x_SaveSquid32Caches$t);
			}
		
		}
		
		
	</script>
	
	
	";

	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
	
}

function rebuild_caches(){
	$q=new mysql_blackbox();
	$q->QUERY_SQL("DELETE FROM cachestatus WHERE nodeid={$_POST["nodeid"]}");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{operation_launched_in_background}");	
	$q=new squidnodes($_POST["nodeid"]);
	$q->RebuildCaches();
	
	
}


function squid_cache_status(){
		$page=CurrentPageName();
		$squid=new squidnodes($_GET["nodeid"]);
		$tpl=new templates();
		$q=new mysql_blackbox();
		$t=$_GET["t"];
		$sql="SELECT * FROM cachestatus WHERE nodeid='{$_GET["nodeid"]}'";
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){echo "<H3>Error: $q->mysql_error</H3>";return;}
		//$squid->
		
		$tr[]="
			<table style='width:99%' class=form>
			<tbody>
			<tr>
				<td width=1%><img src='img/parameters2-64.png'></td>
				<td valign='top'>
					<table style='width:100%'>
					<tbody>
					<tr>
						<td valign=top><strong style='font-size:14px'>
							<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('nodes.squid.caches32.parms.php?hostid={$_GET["hostid"]}&nodeid={$_GET["nodeid"]}');\"
							style='font-size:14px;text-decoration:underline;font-weight:bold'>
							{caches_parameters}</a></td>
					</tr>
					<tr>
						<td valign='top'><span style='font-size:14px'>{cache_parameters_node_explain}</span></td>
					</tr>
					</tbody>
					</table>
				</td>
			</tr>
			</tbody>
			</table>";		
		
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
			$tr[]="
			<table style='width:99%' class=form>
			<tbody>
			<tr>
				<td width=1%><img src='img/disk-64.png'></td>
				<td valign='top'>
					<table style='width:100%'>
					<tbody>
					<tr>
						<td valign=top><strong style='font-size:14px'>". basename($ligne["cachedir"])."</td>
					</tr>
					<tr>
						<td valign='top'><strong style='font-size:14px'>". FormatBytes($ligne["currentsize"])."/". FormatBytes($ligne["maxsize"])."</td>
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

	$html=CompileTr2($tr);
	$html=$html."<div style='width:100%;text-align:right'>".imgtootltip("refresh-32.png","{refresh}","LoadAjax('squid-caches-status$t','$page?squid-caches-status=yes&nodeid={$_GET["nodeid"]}');")."</div>";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function squid_cache_save(){
	$cachesDirectory=$squid->GET("cachesDirectory");
	$globalCachesize=$squid->GET("globalCachesize");
	$q=new squidnodes($_POST["nodeid"]);
	$q->SET("cachesDirectory", $_POST["cachesDirectory"]);
	$q->SET("globalCachesize", $_POST["globalCachesize"]);
	$q->SaveToLdap();
	$q->RebuildCaches();

}