<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.tcpip.inc');
	include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
	include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
	include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
	include_once(dirname(__FILE__) . "/ressources/class.pdns.inc");
	include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
	include_once(dirname(__FILE__) . '/ressources/class.squid.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_POST["cache_directory"])){save();exit;}
	
page();


function page(){
	$page=CurrentPageName();
	$q=new mysql();
	$tpl=new templates();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM squid_caches_center WHERE cache_type='rock' LIMIT 0,1","artica_backup"));
	$sock=new sockets();
	$EnableRockCache=intval($sock->GET_INFO("EnableRockCache"));
	
	$DisableAnyCache=intval($sock->GET_INFO("DisableAnyCache"));
	
	if($DisableAnyCache==1){
		echo FATAL_ERROR_SHOW_128("{DisableAnyCache_enabled_warning}");
		return;
	}
	
	
	
	$DEV_SHM=intval($sock->getFrameWork("squid.php?devshmsize=yes"))*0.7;
	
	$max_rock_size_kb=$DEV_SHM*32;
	
	$max_rock_size=($DEV_SHM*32);
	
	$DEV_SHM=FormatBytes($DEV_SHM);
	$max_rock_size=FormatBytes($max_rock_size);
	$squid_cache_rock_warning=$tpl->javascript_parse_text("{squid_cache_rock_warning}");
	
	$array[320]="320M";
	$array[512]="512M";
	$array[640]="640M";
	$array[2000]="2GB";
	$array[3200]="3.2GB";
	$array[5120]="5.1GB";
	$array[6400]="6.4GB";
	$array[32000]="32GB";
	$array[51200]="51GB";
	$array[64000]="64GB";
	$array[128000]="128GB";
	
	$t=time();
	$cache_current=$ligne["cache_size"]*1024;
	$cache_current=FormatBytes($cache_current);
	$cache_directory=$ligne["cache_dir"];
	
	$type=$tpl->_ENGINE_parse_body(Field_array_Hash($array,"cache_size-$t",$ligne["cache_size"],
			"blur()",null,0,"font-size:28px;padding:3px"));
	
	$browse=button("{browse}...", "Loadjs('SambaBrowse.php?no-shares=yes&field=cache_directory-$t')",22);
	$p=Paragraphe_switch_img("{rock_store}", "{cache_rock_explain}<br>{SQUID_ROCK_STORE_EXPLAIN}",
			"EnableRockCache",$EnableRockCache,null,1140,"CacheRockDisable()");
	
	$html="<div style='width:98%' class=form>
	$p
	<p>&nbsp;</p>
	<div style='font-size:28px;margin-bottom:20px'>{available_memory}: $DEV_SHM</div>
	<div style='font-size:28px;margin-bottom:20px'>{max_allowed_size_of_rock_store}: $max_rock_size</div>
	<p>&nbsp;</p>
	<table style='width:100%'>
	<tr>
	<td class=legend style='font-size:22px' nowrap>{size}:</td>
	<td>$type</td>
	<td>&nbsp;</td>
	

	<tr>
	<td class=legend style='font-size:22px' nowrap>{directory}:</td>
	<td>" . Field_text("cache_directory-$t",$cache_directory,"width:99%;font-size:28px;padding:3px")."</td>
	<td>$browse</td>
	
	</tr>
		<tr>
		<td align='right' colspan=3 style='padding-top:30px'><hr>". button("{apply}","AddNewCacheSave$t()",42)."</td>
	</tr>
	
	</table>			
<script>
var x_AddNewCacheSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){ alert(results); return; }
	Loadjs('squid.rock.progress.php');
	
}
	
function AddNewCacheSave$t(){
	if(!confirm('$squid_cache_rock_warning')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('EnableRockCache', document.getElementById('EnableRockCache').value);
	XHR.appendData('cache_directory',encodeURIComponent(document.getElementById('cache_directory-$t').value));
	XHR.appendData('cache_size',document.getElementById('cache_size-$t').value);
	XHR.sendAndLoad('$page', 'POST',x_AddNewCacheSave$t);
}

function CacheRockDisable(){
	document.getElementById('cache_directory-$t').disabled=true;
	document.getElementById('cache_size-$t').disabled=true;
	var EnableRockCache=document.getElementById('EnableRockCache').value;
	if( EnableRockCache == 1){
			document.getElementById('cache_directory-$t').disabled=false;
			document.getElementById('cache_size-$t').disabled=false;
	}
}
CacheRockDisable();
</script>							
</div>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function save(){
	$tpl=new templates();
	$sock=new sockets();
	$cache_size=$_POST["cache_size"]*1024;
	
	$DEV_SHM=intval($sock->getFrameWork("squid.php?devshmsize=yes"))*0.7;
	$max_rock_size_kb=$DEV_SHM*32;
	

	
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){
		echo $tpl->javascript_parse_text("{this_feature_is_disabled_corp_license}");
		return;
	}
	
	if($cache_size>$max_rock_size_kb){
	
		echo $tpl->javascript_parse_text("{your_cache_size_exceed_max_allowed_value}");
		return;
	}	
	
	$cache_directory=url_decode_special_tool($_POST["cache_directory"]);
	$sock->SET_INFO("EnableRockCache", $_POST["EnableRockCache"]);
	
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM squid_caches_center WHERE `cache_type`='rock'","artica_backup");
	$q->QUERY_SQL("INSERT IGNORE INTO squid_caches_center
			(cachename,cpu,cache_dir,cache_type,cache_size,cache_dir_level1,cache_dir_level2,enabled,percentcache,usedcache,zOrder,min_size,max_size)
			VALUES('Rock Cache',1,'$cache_directory','rock','{$_POST["cache_size"]}','0','0',1,0,0,1,0,0)","artica_backup");
	
	
}
