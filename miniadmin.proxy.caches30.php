<?php
session_start();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;}
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");

$users=new usersMenus();
if(!$users->AsSquidAdministrator){senderrors("{ERROR_NO_PRIVS}");}


if(isset($_GET["tab-rules"])){tabs_rules();exit;}


if(isset($_GET["section-caches"])){section_caches();exit;}
if(isset($_GET["search-caches"])){search_caches();exit;}

if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_POST["CacheReplacementPolicy"])){parameters_save();exit;}
if(isset($_GET["search-rules"])){section_rules_search();exit;}

if(isset($_GET["web-rules"])){section_webrules();exit;}
if(isset($_GET["search-webrules"])){section_webrules_search();exit;}
if(isset($_GET["section_webrules_add_js"])){section_webrules_add_js();exit;}

tabs();


function tabs(){
	$page=CurrentPageName();
	$mini=new boostrap_form();
	$array["{caches}"]="$page?section-caches=yes";
	$array["{parameters}"]="$page?parameters=yes";
	echo $mini->build_tab($array);
}

function parameters(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$CacheReplacementPolicy=$sock->GET_INFO("CacheReplacementPolicy");
	$DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
	if($CacheReplacementPolicy==null){$CacheReplacementPolicy="heap_LFUDA";}
	$SquidDebugCacheProc=$sock->GET_INFO("SquidDebugCacheProc");
	$ForceWindowsUpdateCaching=$sock->GET_INFO("ForceWindowsUpdateCaching");
	if(!is_numeric($SquidDebugCacheProc)){$SquidDebugCacheProc=0;}
	if(!is_numeric($DisableAnyCache)){$DisableAnyCache=0;}
	if(!is_numeric($ForceWindowsUpdateCaching)){$ForceWindowsUpdateCaching=0;}	

	$squid=new squidbee();
	$t=time();
	$array["lru"]="{cache_lru}";
	$array["heap_GDSF"]="{heap_GDSF}";
	$array["heap_LFUDA"]="{heap_LFUDA}";
	$array["heap_LRU"]="{heap_LRU}";	
	
	if(preg_match("#([0-9]+)#",$squid->global_conf_array["maximum_object_size"],$re)){
		$maximum_object_size=$re[1];
		if(preg_match("#([A-Z]+)#",$squid->global_conf_array["maximum_object_size"],$re)){$unit=$re[1];}
		if($unit=="KB"){$maximum_object_size_in_memory=round($maximum_object_size_in_memory/1024);}
	}
	
	if(preg_match("#([0-9]+)#",$squid->global_conf_array["maximum_object_size_in_memory"],$re)){
		$maximum_object_size_in_memory=$re[1];
		if(preg_match("#([A-Z]+)#",$squid->global_conf_array["maximum_object_size_in_memory"],$re)){$unit=$re[1];}
		if($unit=="KB"){$maximum_object_size_in_memory=round($maximum_object_size_in_memory/1024);}
	}	
	
	$boot=new boostrap_form();
	
	$boot->set_formtitle("{caches}::{parameters}");
	
	$boot->set_checkbox("DisableAnyCache", "{DisableAnyCache}", $DisableAnyCache);
	$boot->set_checkbox("ForceWindowsUpdateCaching", "{ForceWindowsUpdateCaching}", $ForceWindowsUpdateCaching);
	$boot->set_checkbox("SquidDebugCacheProc", "{debug_cache_processing}", $SquidDebugCacheProc);
	$boot->set_list("CacheReplacementPolicy", "{cache_replacement_policy}",$array, $CacheReplacementPolicy,array("TOOLTIP"=>"{cache_replacement_policy_explain}"));
	
	$boot->set_field("maximum_object_size", "{maximum_object_size_in_memory} (MB)", $maximum_object_size_in_memory,array("TOOLTIP"=>"{maximum_object_size_text}"));
	$boot->set_field("maximum_object_size", "{maximum_object_size_in_memory} (MB)", 
			$maximum_object_size,array("TOOLTIP"=>"{maximum_object_size_in_memory_text}"));
	

	$boot->set_button("{apply}");
	echo $boot->Compile();
	
	
}

function parameters_save(){
	
	$sock=new sockets();
	$sock->SET_INFO("CacheReplacementPolicy", $_POST["CacheReplacementPolicy"]);
	$sock->SET_INFO("SquidDebugCacheProc", $_POST["SquidDebugCacheProc"]);
	$sock->SET_INFO("DisableAnyCache", $_POST["DisableAnyCache"]);
	$sock->SET_INFO("ForceWindowsUpdateCaching", $_POST["ForceWindowsUpdateCaching"]);
	$squid=new squidbee();
	$squid->global_conf_array["maximum_object_size"]=$_POST["maximum_object_size"]." MB";
	$squid->global_conf_array["maximum_object_size_in_memory"]=$_POST["maximum_object_size_in_memory"]." MB";
	$squid->SaveToLdap(true);
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{must_restart_proxy_settings}");	
	
}


function section_caches(){
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	$sock=new sockets();
	$t=time();
	$uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{new_cache}", "Loadjs('squid.caches32.php?add-new-disk-js=yes')"));
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{apply}", "Loadjs('squid.restart.php?onlySquid=yes&ApplyConfToo=yes');"));
	echo "<div id='$t-license'></div>".
	$boot->SearchFormGen("cachedir","search-caches","&uuid=$uuid&t=$t",$EXPLAIN);
}


function search_caches(){
	$sock=new sockets();
	$DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
	if(!is_numeric($DisableAnyCache)){$DisableAnyCache=0;}
	
	if($DisableAnyCache==1){
		senderrors("{DisableAnyCache_error_explain}");
		
	}
	
	$page=CurrentPageName();
	
	$tpl=new templates();
	$delete_cache=$tpl->javascript_parse_text("{delete_cache}");
	$WARN_OPE_RESTART_SQUID_ASK=$tpl->javascript_parse_text("{WARN_OPE_RESTART_SQUID_ASK}");
	
	$t=$_GET["t"];
	$q=new mysql_squid_builder();	
	
	$searchstring=string_to_flexquery("search-caches");
	$searchstringRGX=string_to_flexregex("search-caches");
	
	$sql="SELECT * FROM cachestatus WHERE uuid='{$_GET["uuid"]}' $searchstring";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){senderrors($q->mysql_error);}
	
	if($GLOBALS["VERBOSE"]){echo __LINE__.":: -> new squidbee()<br>\n";}
	$squid=new squidbee();
	$boot=new boostrap_form();
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$linkCache=null;
			$delete=imgtootltip("delete-64.png","{delete_cache}","squid32DeleteCache('".base64_encode($ligne["cachedir"])."')");
			if(basename($ligne["cachedir"])=="cache_booster"){$linkCache=$boot->trswitch("Loadjs('squid.booster.php')");}
			
			$NICKEL[$ligne["cachedir"]]=true;
			
			
			if($ligne["cachedir"]==$squid->CACHE_PATH){
				$cache_type=$squid->CACHE_TYPE;
				$icon="disk-64-config.png";
				$linkCache=$boot->trswitch("Loadjs('squid.caches32.php?add-new-disk-js=yes&chdef=yes')");
				$delete="&nbsp;";
			}else{
				$cache_type=$squid->cache_list[$ligne["cachedir"]]["cache_type"];
			}
				
			if($ligne["cachedir"]<>$squid->CACHE_PATH){
				if(!isset($squid->cache_list[$ligne["cachedir"]])){
					$icon="disk-64-hide.png";
					$delete="&nbsp;";
				}
			}
			
			$tr[]="
			<tr>
				<td width=1% nowrap $linkCache><img src='img/$icon'></td>
				<td style='font-size:16px' $linkCache>{$ligne["cachedir"]} ({$cache_type})</td>
				<td nowrap width=1% style='font-size:16px'>". FormatBytes($ligne["currentsize"])."/". FormatBytes($ligne["maxsize"])	."</td>
				<td width=1% nowrap>".pourcentage($ligne["pourc"])."</td>
				<td width=1% nowrap>$delete</td>
			</tr>
			";
	}


	
	while (list ($path, $array) = each ($squid->cache_list) ){
		if(isset($NICKEL[$path])){continue;}
		if($searchstringRGX<>null){if(!preg_match("#$searchstringRGX#", $path)){continue;}}
		$icon="disk-64-hide.png";
		$unit="&nbsp;MB";
		$maxcachesize=null;
		
		if($array["cache_type"]=="rock"){$maxcachesize="&nbsp;({max_objects_size} {$array["cache_maxsize"]}$unit)";}
		if(is_numeric($array["cache_size"])){if($array["cache_size"]>1000){$array["cache_size"]=$array["cache_size"]/1000;$unit="&nbsp;GB";}}
		if($array["cache_type"]=="rock"){continue;}
		$delete=imgtootltip("delete-64.png","{delete_cache}","squid32DeleteCache('".base64_encode($path)."')");
		
		$tr[]="
		<tr>
			<td width=1% nowrap><img src='img/$icon'></td>
			<td style='font-size:16px'>$path ({$array["cache_type"]})</td>
			<td nowrap width=1% style='font-size:16px'>{$array["cache_size"]}$unit/$maxcachesize</td>
			<td width=1% nowrap>&nbsp;</td>
			<td width=1% nowrap>$delete</td>
		</tr>
		";
		
		
	}
	
	echo $tpl->_ENGINE_parse_body("
			<table class='table table-bordered table-hover'>
	
			<thead>
			<tr>
				<th colspan=2>{caches}</th>
				<th colspan=2>{used}</th>
				
				<th>&nbsp;</th>
			</tr>
			</thead>
			<tbody>
			").@implode("", $tr)."</tbody></table>

	<script>
	
		LoadAjaxTiny('$t-license','squid.caches32.php?license-explain=yes');
		
		
		function squid32DeleteCache(encoded){
			if(confirm('$delete_cache ?\\n$WARN_OPE_RESTART_SQUID_ASK')){
				var XHR = new XHRConnection();
				XHR.appendData('delete-cache',encoded);
				AnimateDiv('caches-32-div');		
				XHR.sendAndLoad('squid.caches32.php', 'POST',x_SaveSquid32Caches);			
			
			}
		}

	var x_SaveSquid32Caches= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);return;}
			ExecuteByClassName('SearchFunction');
		}	

	</script>";
	
}

