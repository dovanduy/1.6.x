<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	if(isset($_GET["caches-control"])){cache_control();exit;}
	if(isset($_GET["AddCachedSitelist-js"])){AddCachedSitelist_js();exit;}
	if(isset($_GET["AddCachedSitelist-popup"])){AddCachedSitelist_popup();exit;}
	if(isset($_POST["refresh_pattern_site"])){AddCachedSitelist_save();exit;}
	if(isset($_GET["AddCachedSitelist-delete"])){AddCachedSitelist_js_delete();exit;}
	if(isset($_GET["sites-list"])){WEBSITES_LIST();exit;}
	if(isset($_GET["websites-search"])){WEBSITES_SEARCH();exit;}
	if(isset($_POST["add_default_settings"])){WEBSITES_DEFAULTS();exit;}
	if(isset($_POST["delete_all"])){WEBSITES_DELETE_ALL();exit;}
	if(isset($_GET["delete-id"])){AddCachedSitelist_delete();exit;}
	if(isset($_GET["js"])){js();exit;}
	
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$title=$tpl->_ENGINE_parse_body("{cache}::{cache_control}");
	$html="YahooWin5('700','$page?caches-control=yes&byjs=yes','$title');";
	echo $html;
	
}

function cache_control(){
	$page=CurrentPageName();
	echo "<div id='cached_sites_infos' style='width:100%;height:650px;overflow:auto'><center><img src=\"img/wait_verybig.gif\"></center></div>
<script>LoadAjax('cached_sites_infos','$page?sites-list=yes');</script>";
	
}

	
function AddCachedSitelist_js(){
	$tpl=new templates();
	$add_new_cached_web_site=$tpl->_ENGINE_parse_body('{add_new_cached_web_site}');
	
	if(!$_SESSION["CORP"]){
		$tpl=new templates();
		$onlycorpavailable=$tpl->javascript_parse_text("{onlycorpavailable}");
		$content="alert('$onlycorpavailable')";
		echo $content;	
		return;
	}		
	
	
	$page=CurrentPageName();
	$html="
		function AddCachedSitelistStart(){
			YahooWin3('650','$page?AddCachedSitelist-popup=yes&id={$_GET["id"]}&t={$_GET["t"]}','$add_new_cached_web_site');
			
		}
		

		
	
	AddCachedSitelistStart();";
	echo $html;
}

function AddCachedSitelist_js_delete(){
	$page=CurrentPageName();
	$t=$_GET["t"];
	$IDROW=$_GET["IDROW"];
	$html="	
		var x_AddCachedSitelist_js_delete= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);return;}
			
			if(document.getElementById('row{$IDROW}')){
				$('#row{$IDROW}').remove();
			}
			
		}		

		function AddCachedSitelist_js_delete(){
			var XHR = new XHRConnection();
			XHR.appendData('delete-id','{$_GET["AddCachedSitelist-delete"]}');
			XHR.sendAndLoad('$page', 'GET',x_AddCachedSitelist_js_delete);	
			
		}


AddCachedSitelist_js_delete()";
echo $html;
}

function AddCachedSitelist_delete(){
	
	$sql="DELETE FROM squid_speed WHERE ID={$_GET["delete-id"]}";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "$sql\n$q->mysql_error";return;}
}

function WEBSITES_DELETE_ALL(){
	$q=new mysql();
	$sql="TRUNCATE TABLE `squid_speed`";
	$q->QUERY_SQL($sql,"artica_backup");
}	



function AddCachedSitelist_save(){
	
	if(!$_SESSION["CORP"]){
		$tpl=new templates();
		$onlycorpavailable=$tpl->javascript_parse_text("{onlycorpavailable}");
		$content="alert('$onlycorpavailable')";
		echo $onlycorpavailable;	
		return;
	}		
	
	$_POST["refresh_pattern_site"]=url_decode_special_tool($_POST["refresh_pattern_site"]);
	$pattern=$_GET["refresh_pattern_site"];
	$pattern=str_replace(".","\.",$pattern);
	$pattern=str_replace("*",".*?",$pattern);
	$pattern=mysql_escape_string($pattern);
	$id=$_POST["id"];
	
	if($_POST["refresh_pattern_min"]<5){$_POST["refresh_pattern_min"]=5;}
	if($_POST["refresh_pattern_max"]<$_POST["refresh_pattern_min"]){
		$_POST["refresh_pattern_max"]=$_POST["refresh_pattern_min"]+60;
	}

	$sql="INSERT IGNORE INTO squid_speed (domain,refresh_pattern_min,refresh_pattern_perc,refresh_pattern_max,refresh_pattern_options)
	VALUES('{$_POST["refresh_pattern_site"]}','{$_POST["refresh_pattern_min"]}','{$_POST["refresh_pattern_pourc"]}',
	'{$_POST["refresh_pattern_max"]}',
	'{$_POST["refresh_pattern_option"]}'
	);";	
	
	
	
	
	
	
	if($id>0){
		$sql="UPDATE squid_speed SET 
			domain='{$_POST["refresh_pattern_site"]}',
			refresh_pattern_min='{$_POST["refresh_pattern_min"]}',
			refresh_pattern_perc='{$_POST["refresh_pattern_pourc"]}',
			refresh_pattern_max='{$_POST["refresh_pattern_max"]}',
			refresh_pattern_options='{$_POST["refresh_pattern_option"]}'
			WHERE ID='{$id}'
			";
	}
	
	
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "$sql\n$q->mysql_error";return;}
	

	
}

function AddCachedSitelist_popup(){
	$page=CurrentPageName();
	$t=$_GET["t"];
	$option[null]="---------";
	$option["override-lastmod"]="override-lastmod";
	$option["override-expire"]="override-expire";
	$option["reload-into-ims"]="reload-into-ims";
	$option["override-expire ignore-no-cache ignore-no-store ignore-private"]="{ignore_all}";
	$option["ignore-reload"]="ignore-reload";
	$option["reload-into-ims ignore-no-cache"]="reload-into-ims+ignore-no-cache";
	$button="{add}";
	if(!is_numeric($_GET["id"])){$_GET["id"]=0;}
	if($_GET["id"]>0){
		$sql="SELECT * FROM squid_speed WHERE ID={$_GET["id"]}";
		$q=new mysql();
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$domain=$ligne["domain"];
		$pourc=$ligne["refresh_pattern_perc"];
		$refresh_pattern_min=$ligne["refresh_pattern_min"];
		$refresh_pattern_max=$ligne["refresh_pattern_max"];
		$refresh_pattern_option=$ligne["refresh_pattern_options"];
		$button="{apply}";
		}
		
		$refresh_pattern_opt=Field_array_Hash($option,"refresh_pattern_option-$t",$refresh_pattern_option,null,null,0,"font-size:14px;padding:3px");
	
	$html="
	<div id='AddCachedSitelistDiv-$t'></div>
	<table style='width:99%' class=form>
	<tr>
	<td valign='top'>
	
	". Field_hidden("id","{$_GET["id"]}")."
	<div style='font-size:14px;padding:5px' class=explain>{squid_refresh_pattern_explain}</div>
	<table style='width:99%'>
	<tr>
		<td class=legend style='font-size:14px'>{pattern}:</td>
		<td style='font-size:14px'>". Field_text("refresh_pattern_site-$t",$domain,'font-size:16px;padding:3px',null,null,null,false,"AddCachedSiteListCheckEnter(event)")."</td>
		<td width=1%>". help_icon("{refresh_pattern_site}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{minimal_time}:</td>
		<td style='font-size:14px'>". Field_text("refresh_pattern_min-$t",$refresh_pattern_min,'width:50px;font-size:14px;padding:3px',null,null,null,false,"AddCachedSiteListCheckEnter(event)")."&nbsp;Mn</td>
		<td width=1%>". help_icon("{refresh_pattern_min}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{percentage}:</td>
		<td style='font-size:14px'>". Field_text("refresh_pattern_pourc-$t",$pourc,'width:50px;font-size:14px;padding:3px',null,null,null,false,"AddCachedSiteListCheckEnter(event)")."&nbsp;%</td>
		<td width=1%>". help_icon("{refresh_pattern_pourc}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{maximal_time}:</td>
		<td style='font-size:14px'>". Field_text("refresh_pattern_max-$t",$refresh_pattern_max,'width:50px;font-size:14px;padding:3px',null,null,null,false,"AddCachedSiteListCheckEnter(event)")."&nbsp;Mn</td>
		<td width=1%>". help_icon("{refresh_pattern_max}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{option}:</td>
		<td style='font-size:14px'>$refresh_pattern_opt</td>
		<td width=1%>". help_icon("{refresh_pattern_option}")."</td>
	</tr>	
	<tr>
		<td colspan=3 align='right'><hr>
			". button($button,"AddCachedSitelistSave$t()",16).
		"</td>
	</tr>
	</table>		
	
	</td>
	</tr>
	</table>
	<center style='margin-top:10px'>
	<div style='width:95%' class=form>
	<center><img src='img/refresh_pattern_graph.gif' style='border:3px solid #CCCCCC'></center>
	</div>
	</center>
	
	<script>
		var x_AddCachedSitelistSave$t= function (obj) {
			var results=obj.responseText;
			document.getElementById('AddCachedSitelistDiv-$t').innerHTML='';
			if(results.length>0){alert(results);}
			if(document.getElementById('flexRT{$_GET["t"]}')){
				$('#flexRT{$_GET["t"]}').flexReload();	
			}
		
			YahooWin3Hide();
		}			
		
		function AddCachedSitelistSave$t(){
			var XHR = new XHRConnection();
			XHR.appendData('id','{$_GET["id"]}');
			var pp=encodeURIComponent(document.getElementById('refresh_pattern_site-$t').value);
			
			
			
			XHR.appendData('refresh_pattern_site',pp);
			XHR.appendData('refresh_pattern_min',document.getElementById('refresh_pattern_min-$t').value);
			XHR.appendData('refresh_pattern_pourc',document.getElementById('refresh_pattern_pourc-$t').value);
			XHR.appendData('refresh_pattern_max',document.getElementById('refresh_pattern_max-$t').value);
			XHR.appendData('refresh_pattern_option',document.getElementById('refresh_pattern_option-$t').value);
			AnimateDiv('AddCachedSitelistDiv-$t');
			XHR.sendAndLoad('$page', 'POST',x_AddCachedSitelistSave$t);			
		
		}
		

		
		function AddCachedSiteListCheckEnter(e){
			if(checkEnter(e)){AddCachedSitelistSave$t();}
		}
	</script>	
	
	
	
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}

function WEBSITES_LIST(){
$t=time();
$page=CurrentPageName();
$tpl=new templates();
$sock=new sockets();
$users=new usersMenus();
$CORP=0;
$website=$tpl->_ENGINE_parse_body("{website}");
$expire_time=$tpl->_ENGINE_parse_body("{expire_time}");
$limit=$tpl->_ENGINE_parse_body("{limit}");
$add_new_cached_web_site=$tpl->_ENGINE_parse_body("{add_new_cached_web_site}");
$add_default_settings=$tpl->_ENGINE_parse_body("{add_default_settings}");
$refresh_pattern_intro=$tpl->_ENGINE_parse_body("{refresh_pattern_intro}");
$delete_all=$tpl->javascript_parse_text("{delete_all}");	
$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
if($_SESSION["CORP"]){$CORP=1;}
$onlycorpavailable=$tpl->javascript_parse_text("{onlycorpavailable}");
$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}
if($UnlockWebStats==1){$EnableRemoteStatisticsAppliance=0;}
$apply_params=$tpl->_ENGINE_parse_body("{apply}");
$options=$tpl->javascript_parse_text("{options}");
$restart=$tpl->javascript_parse_text("{restart}");


$buttons="
{name: '$add_new_cached_web_site', bclass: 'add', onpress : AddNewCachedWebsite},
		{name: '$add_default_settings', bclass: 'add', onpress : add_default_settings},
		{name: '$delete_all', bclass: 'Delz', onpress : delete_all},
	{separator: true},
	{name: '$options', bclass: 'Settings', onpress : CacheOptions$t},		
	{separator: true},
	{name: '$apply_params', bclass: 'Reload', onpress : SquidBuildNow$t},
	{name: '$restart', bclass: 'Reload', onpress : SquidRestartNow$t},		
";

if($EnableRemoteStatisticsAppliance==1){$buttons=null;}


$html="
<div class=explain>$refresh_pattern_intro</div>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?websites-search=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'setz', width : 31, sortable : false, align: 'center'},	
		{display: '$website', name : 'domain', width : 368, sortable : true, align: 'left'},	
		{display: '$expire_time', name : 'refresh_pattern_min', width : 151, sortable : true, align: 'left'},
		{display: '%', name : 'refresh_pattern_perc', width : 38, sortable : true, align: 'center'},
		{display: '$limit', name : 'refresh_pattern_max', width : 106, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 38, sortable : false, align: 'center'},
	],
	
	
buttons : [
		$buttons
		
		{separator: true}
		],

	searchitems : [
		{display: '$website', name : 'domain'}
		],		
	
	sortname: 'domain',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 100,
	showTableToggleBtn: false,
	width: 826,
	height: 400,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

	function SquidBuildNow$t(){
		Loadjs('squid.compile.php');
	}
	
	function SquidRestartNow$t(){
		Loadjs('squid.restart.php&onlySquid=yes&ApplyConfToo=yes');
	}

function AddNewCachedWebsite(){
	var CORP=$CORP;
	if(CORP==0){alert('$onlycorpavailable');return;}	
	Loadjs('$page?AddCachedSitelist-js=yes&t=$t')
}


function CacheOptions$t(){
	Loadjs('squid.cache_replacement_policy.php');
}


		var x_add_default_settings= function (obj) {	
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			$('#flexRT$t').flexReload();			
				
		}		
		
		function add_default_settings(){
			var CORP=$CORP;
			if(CORP==0){alert('$onlycorpavailable');return;}
		 	var XHR = new XHRConnection();
			XHR.appendData('add_default_settings','yes');
			XHR.sendAndLoad('$page', 'POST',x_add_default_settings);
		}
	
		function delete_all(){
			if(confirm('$delete_all ?')){
		 		var XHR = new XHRConnection();
				XHR.appendData('delete_all','yes');
				XHR.sendAndLoad('$page', 'POST',x_add_default_settings);
			}
		}
</script>";

echo $html;
	
}

function WEBSITES_SEARCH(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$database="artica_backup";
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
	if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}
	if($UnlockWebStats==1){$EnableRemoteStatisticsAppliance=0;}	
	$DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
	if(!is_numeric($DisableAnyCache)){$DisableAnyCache=0;}	
	$search='%';
	$table="squid_speed";
	$page=1;
	$FORCE_FILTER=null;
	
	if($q->COUNT_ROWS($table,$database)==0){
		writelogs("$table, no row",__FILE__,__FUNCTION__,__LINE__);
		$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();
		echo json_encode($data);
		return ;
	}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){
		$data['rows'][] = array('id' => $ligne[time()+1],'cell' => array($q->mysql_error,"", "",""));
		$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));
		echo json_encode($data);
		return;
	}	
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=md5($ligne["domain"].$ligne["ID"]);
		$color="black";
		if($DisableAnyCache==1){$color="#9C9C9C";}
		$delete=imgtootltip("delete-24.png","{delete}","Loadjs('$MyPage?AddCachedSitelist-delete={$ligne["ID"]}&t={$_GET["t"]}&IDROW={$ID}')");
		$select="Loadjs('$MyPage?AddCachedSitelist-js=yes&id={$ligne["ID"]}&t={$_GET["t"]}');";
		
		$ligne["refresh_pattern_min"]=$ligne["refresh_pattern_min"];
		$ligne["refresh_pattern_min"]=distanceOfTimeInWords(time(),mktime()+($ligne["refresh_pattern_min"]*60),true);
		$ligne["refresh_pattern_min"]=$tpl->javascript_parse_text($ligne["refresh_pattern_min"]);
		
		$ligne["refresh_pattern_max"]=$ligne["refresh_pattern_max"];
		$ligne["refresh_pattern_max"]=distanceOfTimeInWords(time(),mktime()+($ligne["refresh_pattern_max"]*60),true);
		$ligne["refresh_pattern_max"]=$tpl->javascript_parse_text($ligne["refresh_pattern_max"]);
		
		$link="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:$select\" 
		style='font-size:12px;text-decoration:underline;color:$color'>";
		if(trim($ligne["domain"])=='.'){$ligne["domain"]=$tpl->_ENGINE_parse_body("{all}");}
		
		$set=imgsimple("24-parameters.png",null,$select);
		if($EnableRemoteStatisticsAppliance==1){$delete="&nbsp;";}
	
		
	$data['rows'][] = array(
		'id' => $ID,
		'cell' => array(
		$set,
		"<span style='font-size:14px;color:$color'>$link{$ligne["domain"]}</a></span>"
		,"<span style='font-size:12px;color:$color'>{$ligne["refresh_pattern_min"]}</a></span>",
		"<span style='font-size:12px;color:$color'>{$ligne["refresh_pattern_perc"]}%</a></span>",
		"<span style='font-size:12px;color:$color'>{$ligne["refresh_pattern_max"]}</a></span>",$delete )
		);
	}
	
	
echo json_encode($data);		

}

	
function WEBSITES_LIST_OLD(){
	$q=new mysql();
	if(isset($_GET["remove-all"])){
		$sql="TRUNCATE TABLE `squid_speed`";
		$q->QUERY_SQL($sql,"artica_backup");
	}
	
	if(isset($_GET["defaults"])){WEBSITES_DEFAULTS();}
	
    $page=CurrentPageName();
	$sql="SELECT * FROM `squid_speed` WHERE `domain` IS NOT NULL";
	
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "$q->mysql_error";}
	$html="
	
	<hr>
	<div class=explain>{refresh_pattern_intro}</div>
	<div style='text-align:right'>
	<table style='width:99%'>
	<tr>
	<td width=99%>&nbsp;</td>
	
	<td align='right' width=1%>". imgtootltip("proxy-delete-32.png","{delete_all} & {add_default_settings}","LoadAjax('cached_sites_infos','squid.cached.sitesinfos.php?sites-list=yes&defaults=yes&remove-all=yes');")."</td>
	<td align='right' width=1%>". imgtootltip("filter-add-32.png","{add_default_settings}","LoadAjax('cached_sites_infos','squid.cached.sitesinfos.php?sites-list=yes&defaults=yes');")."</td>
	<td align='right' width=1%>". imgtootltip("website-add-32.png","{add_new_cached_web_site}","Loadjs('$page?AddCachedSitelist-js=yes')")."</td>
	</tr>
	</div>
	<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
	<thead class='thead'>
	<tr>
		<th>{website}</th>
		<th>{expire_time}</th>
		<th>%</th>
		<th>{limit}</th>
		<th>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>	";
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$select="Loadjs('$page?AddCachedSitelist-js=yes&id={$ligne["ID"]}');";
		
		$ligne["refresh_pattern_min"]=$ligne["refresh_pattern_min"];
		$ligne["refresh_pattern_min"]=distanceOfTimeInWords(0,$ligne["refresh_pattern_min"],true);
		$ligne["refresh_pattern_min"]=str_replace("about","",$ligne["refresh_pattern_min"]);
		
		$ligne["refresh_pattern_max"]=$ligne["refresh_pattern_max"];
		$ligne["refresh_pattern_max"]=distanceOfTimeInWords(0,$ligne["refresh_pattern_max"],true);
		$ligne["refresh_pattern_max"]=str_replace("about","",$ligne["refresh_pattern_max"]);		
		$link="<a href=\"javascript:blur();\" OnClick=\"javascript:$select\" style='font-size:12px;font-weight:bold;text-decoration:underline'>";
		if(trim($ligne["domain"])=='.'){$ligne["domain"]="{all}";}
			$html=$html. "
			<tr class=$classtr>
				<td align='left' >$link{$ligne["domain"]}</a></td>
				<td width=1% nowrap>$link{$ligne["refresh_pattern_min"]}</a></td>
				<td width=1%  align='right'>$link{$ligne["refresh_pattern_perc"]}%</a></td>
				<td width=1%  nowrap>$link{$ligne["refresh_pattern_max"]}</a></td>
				<td width=1%>". imgtootltip("delete-32.png","{delete}","Loadjs('$page?AddCachedSitelist-delete={$ligne["ID"]}')")."</td>
			</tr>
			";
		
		
	}
	
	$html=$html. "</table>
	
	
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}

function WEBSITES_DEFAULTS(){
	

$t[]="INSERT IGNORE INTO squid_speed (domain,refresh_pattern_min,refresh_pattern_perc,refresh_pattern_max,refresh_pattern_options)
	VALUES('-i .(gif|png|jpg|jpeg|ico)$','10080',90,43200,'override-expire ignore-no-cache ignore-no-store ignore-private');";


$t[]="INSERT IGNORE INTO squid_speed (domain,refresh_pattern_min,refresh_pattern_perc,refresh_pattern_max,refresh_pattern_options)
	VALUES('-i .(iso|avi|wav|mp3|mp4|mpeg|swf|flv|x-flv)$', 0, 90, 260009, 'override-expire ignore-no-cache ignore-no-store ignore-private');";

$t[]="INSERT IGNORE INTO squid_speed (domain,refresh_pattern_min,refresh_pattern_perc,refresh_pattern_max,refresh_pattern_options)
	VALUES('-i .(deb|rpm|exe|zip|tar|tgz|ram|rar|bin|ppt|doc|tiff|bz2|gz)$',0, 90, 260009, 'override-expire ignore-no-cache ignore-no-store ignore-private');";

$t[]="INSERT IGNORE INTO squid_speed (domain,refresh_pattern_min,refresh_pattern_perc,refresh_pattern_max,refresh_pattern_options)
	VALUES('-i .(html|htm|css|js|xml)$', 1440, 40, 40320,'');";

$t[]="INSERT IGNORE INTO squid_speed (domain,refresh_pattern_min,refresh_pattern_perc,refresh_pattern_max,refresh_pattern_options)
	VALUES('-i .kaspersky-labs.com/*.(diff|exe|klz|zip)$',1440,100,28800,'reload-into-ims ignore-no-cache');";

$t[]="INSERT IGNORE INTO squid_speed (domain,refresh_pattern_min,refresh_pattern_perc,refresh_pattern_max,refresh_pattern_options)
	VALUES('-i .avast.com/*.(exe|vpu)$',1440, 100, 28800, 'reload-into-ims ignore-no-cache');";

$t[]="INSERT IGNORE INTO squid_speed (domain,refresh_pattern_min,refresh_pattern_perc,refresh_pattern_max,refresh_pattern_options)
	VALUES('-i .avira-update.com/*.gz$', 1440, 100, 28800, 'reload-into-ims ignore-no-cache');";

$t[]="INSERT IGNORE INTO squid_speed (domain,refresh_pattern_min,refresh_pattern_perc,refresh_pattern_max,refresh_pattern_options)
	VALUES('-i global-download.acer.com/*/Driver/*zip', 1440, 100, 260009,'reload-into-ims ignore-no-cache');";

$t[]="INSERT IGNORE INTO squid_speed (domain,refresh_pattern_min,refresh_pattern_perc,refresh_pattern_max,refresh_pattern_options)
	VALUES('-i .windowsupdate.com/*.(cab|exe|dll|msi|psf)' ,0 ,80, 43200, 'reload-into-ims ignore-no-cache');";

$t[]="INSERT IGNORE INTO squid_speed (domain,refresh_pattern_min,refresh_pattern_perc,refresh_pattern_max,refresh_pattern_options)
	VALUES('http://*.windowsupdate.microsoft.com/' , 1440 ,80,20160,'reload-into-ims');";
$t[]="INSERT IGNORE INTO squid_speed (domain,refresh_pattern_min,refresh_pattern_perc,refresh_pattern_max,refresh_pattern_options)
	VALUES('http://*.update.microsoft.com/' , 1440 ,80, 20160,' reload-into-ims');";

$t[]="INSERT IGNORE INTO squid_speed (domain,refresh_pattern_min,refresh_pattern_perc,refresh_pattern_max,refresh_pattern_options)
	VALUES('http://download.microsoft.com/' , 1440 ,80, 20160,' reload-into-ims');";

$t[]="INSERT IGNORE INTO squid_speed (domain,refresh_pattern_min,refresh_pattern_perc,refresh_pattern_max,refresh_pattern_options)
	VALUES('http://windowsupdate.microsoft.com/' , 1440 ,80, 20160,' reload-into-ims');";

$t[]="INSERT IGNORE INTO squid_speed (domain,refresh_pattern_min,refresh_pattern_perc,refresh_pattern_max,refresh_pattern_options)
	VALUES('http://office.microsoft.com/' , 1440 ,80, 20160,' reload-into-ims');";

$t[]="INSERT IGNORE INTO squid_speed (domain,refresh_pattern_min,refresh_pattern_perc,refresh_pattern_max,refresh_pattern_options)
	VALUES('http://w?xpsp[0-9].microsoft.com/' , 1440, 80, 20160,' reload-into-ims');";

$t[]="INSERT IGNORE INTO squid_speed (domain,refresh_pattern_min,refresh_pattern_perc,refresh_pattern_max,refresh_pattern_options)
	VALUES('http://w2ksp[0-9].microsoft.com/' , 1440 ,80, 20160,' reload-into-ims');";

$t[]="INSERT IGNORE INTO squid_speed (domain,refresh_pattern_min,refresh_pattern_perc,refresh_pattern_max,refresh_pattern_options)
	VALUES('http://*.archive.ubuntu.com/' , 1440 ,80, 20160,' reload-into-ims');";

$t[]="INSERT IGNORE INTO squid_speed (domain,refresh_pattern_min,refresh_pattern_perc,refresh_pattern_max,refresh_pattern_options)
	VALUES('*.debian.org/' 1440, 80, 20160' , reload-into-ims');";

$t[]="INSERT IGNORE INTO squid_speed (domain,refresh_pattern_min,refresh_pattern_perc,refresh_pattern_max,refresh_pattern_options)
	VALUES('-i .microsoft.com/*.(cab|exe|dll|msi)',10080,100,43200,'reload-into-ims ignore-no-cache');";

$t[]="INSERT IGNORE INTO squid_speed (domain,refresh_pattern_min,refresh_pattern_perc,refresh_pattern_max,refresh_pattern_options)
VALUES('-i ^http://*.gmail*/*',720 ,100,4320,'')";

$t[]="INSERT IGNORE INTO squid_speed (domain,refresh_pattern_min,refresh_pattern_perc,refresh_pattern_max,refresh_pattern_options)
VALUES('-i ^http://*.googlesyndication*/*',1440 ,100,4320,'')";

$t[]="INSERT IGNORE INTO squid_speed (domain,refresh_pattern_min,refresh_pattern_perc,refresh_pattern_max,refresh_pattern_options)
VALUES('-i ^http://notify*dropbox.com',1440 ,100,2880,'reload-into-ims ignore-no-cache')";

$t[]="INSERT IGNORE INTO squid_speed (domain,refresh_pattern_min,refresh_pattern_perc,refresh_pattern_max,refresh_pattern_options)
VALUES('-i ^http://safebrowsing-cache.google.com/*',1440 ,100,2880,'reload-into-ims ignore-no-cache')";

$t[]="INSERT IGNORE INTO squid_speed (domain,refresh_pattern_min,refresh_pattern_perc,refresh_pattern_max,refresh_pattern_options)
VALUES('-i ^http://*gmodules.com/*',1440 ,100,2880,'reload-into-ims ignore-no-cache')";



http://safebrowsing-cache.google.com/


$t[]="INSERT IGNORE INTO squid_speed (domain,refresh_pattern_min,refresh_pattern_perc,refresh_pattern_max,refresh_pattern_options)
VALUES('-i ^http://*google.*/*',2880 ,100,4320,'')";

$t[]="INSERT IGNORE INTO squid_speed (domain,refresh_pattern_min,refresh_pattern_perc,refresh_pattern_max,refresh_pattern_options)
VALUES('-i ^http://*.ubuntu.*/*',2880 ,100,4320,'')";



// Youtube";
$refresh_pattern[]="(get_video\?|videoplayback\?|videodownload\?|\.flv?)    43200 999999% 43200 ignore-no-cache ignore-no-store ignore-private override-expire override-lastmod reload-into-ims store-stale";
$refresh_pattern[]="(get_video\?|videoplayback\?id|videoplayback.*id|videodownload\?|\.flv?)    43200 999999% 43200 ignore-no-cache ignore-no-store ignore-private override-expire override-lastmod reload-into-ims store-stale";

//Speedtest";
$refresh_pattern[]="speedtest.*\.(jp(e?g|e|2)|tiff?|bmp|gif|png|swf|txt|js) 0 50% 180 store-stale";
//Kaspersky
$refresh_pattern[]="kaspersky       0 50% 180 store-stale";

$refresh_pattern[]="\.(ico|video-stats) 43200 9999% 43200 override-expire ignore-reload ignore-no-cache ignore-no-store ignore-private ignore-auth override-lastmod ignore-must-revalidate store-stale";
$refresh_pattern[]="\.etology\?                                     43200 9999% 43200 override-expire ignore-reload ignore-no-cache store-stale";
$refresh_pattern[]="galleries\.video(\?|sz)                         43200 9999% 43200 override-expire ignore-reload ignore-no-cache store-stale";
$refresh_pattern[]="brazzers\?                                      43200 9999% 43200 override-expire ignore-reload ignore-no-cache store-stale";
$refresh_pattern[]="\.adtology\?                                    43200 9999% 43200 override-expire ignore-reload ignore-no-cache store-stale";
$refresh_pattern[]="^.*safebrowsing.*google  43200 9999% 43200 override-expire ignore-reload ignore-no-cache ignore-private ignore-auth ignore-must-revalidate store-stale";
$refresh_pattern[]="^http://((cbk|mt|khm|mlt)[0-9]?)\.google\.co(m|\.uk)    43200 9999% 43200 override-expire ignore-reload ignore-private store-stale";
$refresh_pattern[]="ytimg\.com.*\.jpg                                       43200 9999% 43200 override-expire ignore-reload store-stale";
$refresh_pattern[]="images\.friendster\.com.*\.(png|gif)                    43200 9999% 43200 override-expire ignore-reload store-stale";
$refresh_pattern[]="garena\.com                                             43200 9999% 43200 override-expire reload-into-ims store-stale";
$refresh_pattern[]="photobucket.*\.(jp(e?g|e|2)|tiff?|bmp|gif|png)          43200 9999% 43200 override-expire ignore-reload store-stale";
$refresh_pattern[]="vid\.akm\.dailymotion\.com.*\.on2\?                     43200 9999% 43200 ignore-no-cache override-expire override-lastmod store-stale";
$refresh_pattern[]="mediafire.com\/images.*\.(jp(e?g|e|2)|tiff?|bmp|gif|png)    43200 9999% 43200 reload-into-ims override-expire ignore-private    store-stale";
$refresh_pattern[]="^http:\/\/images|pics|thumbs[0-9]\.                     43200 9999% 43200 reload-into-ims ignore-no-cache ignore-no-store ignore-reload override-expire store-stale";
$refresh_pattern[]="^http:\/\/www.onemanga.com.*\/                          43200 9999% 43200 reload-into-ims ignore-no-cache ignore-no-store ignore-reload override-expire store-stale";
$refresh_pattern[]="^http://v\.okezone\.com/get_video\/([a-zA-Z0-9]) 		43200 99999999% 43200 override-expire ignore-reload ignore-no-cache ignore-no-store ignore-private ignore-auth override-lastmod ignore-must-revalidate store-stale";

$refresh_pattern[]="guru.avg.com/.*\.(bin)                                  1440 9999% 10080 ignore-no-cache ignore-no-store ignore-reload  reload-into-ims store-stale";
$refresh_pattern[]="(avgate|avira).*(idx|gz)$                               1440 9999% 10080 ignore-no-cache ignore-no-store ignore-reload  reload-into-ims store-stale";
$refresh_pattern[]="kaspersky.*\.avc$                                       1440 9999% 10080 ignore-no-cache ignore-no-store ignore-reload  reload-into-ims store-stale";
$refresh_pattern[]="kaspersky                                               1440 9999% 10080 ignore-no-cache ignore-no-store ignore-reload  reload-into-ims store-stale";
$refresh_pattern[]="update.nai.com/.*\.(gem|zip|mcs)                        1440 9999% 10080 ignore-no-cache ignore-no-store ignore-reload  reload-into-ims store-stale";
$refresh_pattern[]="^http:\/\/liveupdate.symantecliveupdate.com.*\(zip)     1440 9999% 10080 ignore-no-cache ignore-no-store ignore-reload  reload-into-ims store-stale";

$refresh_pattern[]="windowsupdate.com/.*\.(cab|exe)                 10080  9999%  43200 ignore-no-cache ignore-no-store ignore-reload  reload-into-ims store-stale";
$refresh_pattern[]="update.microsoft.com/.*\.(cab|exe)              10080  9999%  43200 ignore-no-cache ignore-no-store ignore-reload  reload-into-ims store-stale";
$refresh_pattern[]="download.microsoft.com/.*\.(cab|exe)            10080  9999%  43200 ignore-no-cache ignore-no-store ignore-reload  reload-into-ims store-stale";

//#images facebook";
$refresh_pattern[]="-i \.facebook.com.*\.(jpg|png|gif)                      129600 9999% 129600 ignore-reload override-expire ignore-no-cache ignore-no-store store-stale";
$refresh_pattern[]="-i \.fbcdn.net.*\.(jpg|gif|png|swf|mp3)                 129600 9999% 129600 ignore-reload override-expire ignore-no-cache ignore-no-store store-stale";
$refresh_pattern[]=" static\.ak\.fbcdn\.net*\.(jpg|gif|png)                 129600 9999% 129600 ignore-reload override-expire ignore-no-cache ignore-no-store store-stale";
$refresh_pattern[]="^http:\/\/profile\.ak\.fbcdn.net*\.(jpg|gif|png)        129600 9999% 129600 ignore-reload override-expire ignore-no-cache ignore-no-store store-stale";

//# games facebook";
$refresh_pattern[]="^http:\/\/apps.facebook.com.*\/	10080 9999% 43200 ignore-reload override-expire ignore-no-cache ignore-no-store store-stale";
$refresh_pattern[]="-i \.zynga.com.*\/      10080 9999% 43200 ignore-reload override-expire ignore-no-cache ignore-no-store ignore-must-revalidate store-stale";
$refresh_pattern[]="-i \.farmville.com.*\/  10080 9999% 43200 ignore-reload override-expire ignore-no-cache ignore-no-store ignore-must-revalidate store-stale";
$refresh_pattern[]="-i \.ninjasaga.com.*\/  10080 9999% 43200 ignore-reload override-expire ignore-no-cache ignore-no-store ignore-must-revalidate store-stale";
$refresh_pattern[]="-i \.mafiawars.com.*\/  10080 9999% 43200 ignore-reload override-expire ignore-no-cache ignore-no-store ignore-must-revalidate store-stale";
$refresh_pattern[]="-i \.crowdstar.com.*\/  10080 9999% 43200 ignore-reload override-expire ignore-no-cache ignore-no-store ignore-must-revalidate store-stale";
$refresh_pattern[]="-i \.popcap.com.*\/    	10080 9999% 43200 ignore-reload override-expire ignore-no-cache ignore-no-store ignore-must-revalidate store-stale";

//banner IIX";
$refresh_pattern[]="^http:\/\/openx.*\.(jp(e?g|e|2)|gif|pn[pg]|swf|ico|css|tiff?) 129600 9999% 129600 reload-into-ims  ignore-reload override-expire ignore-no-cache ignore-no-store  store-stale";
$refresh_pattern[]="^http:\/\/ads(1|2|3).kompas.com.*\/             43200 9999% 129600 reload-into-ims  ignore-reload override-expire ignore-no-cache ignore-no-store  store-stale";
$refresh_pattern[]="^http:\/\/img.ads.kompas.com.*\/                43200 9999% 129600 reload-into-ims  ignore-reload override-expire ignore-no-cache ignore-no-store  store-stale";
$refresh_pattern[]=".kompasimages.com.*\.(jpg|gif|png|swf)          43200 9999% 129600 reload-into-ims  ignore-reload override-expire ignore-no-cache ignore-no-store  store-stale";
$refresh_pattern[]="^http:\/\/openx.kompas.com.*\/                  43200 9999% 129600 reload-into-ims  ignore-reload override-expire ignore-no-cache ignore-no-store  store-stale";
$refresh_pattern[]="kaskus.\us.*\.(jp(e?g|e|2)|gif|png|swf)         43200 9999% 129600 reload-into-ims  ignore-reload override-expire ignore-no-cache ignore-no-store  store-stale";
$refresh_pattern[]="^http:\/\/img.kaskus.us.*\.(jpg|gif|png|swf)    43200 9999% 129600 reload-into-ims  ignore-reload override-expire ignore-no-cache ignore-no-store  store-stale";
//IIX DOWNLOAD";
$refresh_pattern[]="^http:\/\/\.www[0-9][0-9]\.indowebster\.com\/(.*)(mp3|rar|zip|flv|wmv|3gp|mp(4|3)|exe|msi|zip) 43200 99% 129600 reload-into-ims  ignore-reload override-expire ignore-no-cache ignore-no-store  store-stale ignore-auth";

//All File";
$refresh_pattern[]="-i \.(3gp|7z|ace|asx|bin|deb|divx|dvr-ms|ram|rpm|exe|inc|cab|qt)       43200 99% 43200 ignore-no-cache ignore-no-store ignore-must-revalidate override-expire override-lastmod reload-into-ims store-stale";
$refresh_pattern[]="-i \.(rar|jar|gz|tgz|bz2|iso|m1v|m2(v|p)|mo(d|v)|arj|lha|lzh|zip|tar)  43200 99% 43200 ignore-no-cache ignore-no-store ignore-must-revalidate override-expire override-lastmod reload-into-ims store-stale";
$refresh_pattern[]="-i \.(jp(e?g|e|2)|gif|pn[pg]|bm?|tiff?|ico|swf|dat|ad|txt|dll)         43200 99% 43200 ignore-no-cache ignore-no-store ignore-must-revalidate override-expire override-lastmod reload-into-ims store-stale";
$refresh_pattern[]="-i \.(avi|ac4|mp(e?g|a|e|1|2|3|4)|mk(a|v)|ms(i|u|p)|og(x|v|a|g)|rm|r(a|p)m|snd|vob) 43200 99% 43200 ignore-no-cache ignore-no-store ignore-must-revalidate override-expire override-lastmod reload-into-ims store-stale";
$refresh_pattern[]="-i \.(pp(t?x)|s|t)|pdf|rtf|wax|wm(a|v)|wmx|wpl|cb(r|z|t)|xl(s?x)|do(c?x)|flv|x-flv) 43200 99% 43200 ignore-no-cache ignore-no-store ignore-must-revalidate override-expire override-lastmod reload-into-ims store-stale";

while (list ($num, $val) = each ($refresh_pattern)){
	if(!preg_match("#(.+?)\s+([0-9]+)\s+([0-9]+)%\s+([0-9]+)\s+(.*)#",$val,$re)){ continue;}
	
	$t[]="INSERT IGNORE INTO squid_speed (domain,refresh_pattern_min,refresh_pattern_perc,refresh_pattern_max,refresh_pattern_options)
	VALUES('{$re[1]}','{$re[2]}',{$re[3]},{$re[4]},'{$re[5]}');";
}

$t[]="INSERT IGNORE INTO squid_speed (domain,refresh_pattern_min,refresh_pattern_perc,refresh_pattern_max,refresh_pattern_options)
	VALUES('.','0',100,43200,'reload-into-ims override-lastmod');";
$q=new mysql();
while (list ($num, $val) = each ($t)){
	$q->QUERY_SQL($val,"artica_backup");
}

}

?>