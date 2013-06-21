<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	
	if(isset($_GET["websites-search"])){WEBSITES_SEARCH();exit;}
	if(isset($_POST["DELETE"])){DELETE_DOM();exit;}
	if(isset($_POST["delete_all"])){DELETE_ALL();exit;}
	if(isset($_GET["addjs-silent"])){addjs();exit;}
	
	
	
function addjs(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){
		$error_no_licence=$tpl->javascript_parse_text("{error_no_licence}");
		echo "alert('$error_no_licence');";
		return;
	}
	
	
	$website=$_GET["website"];
	$add_new_cached_web_site=$tpl->javascript_parse_text("{add_new_cached_web_site}:$website");
	$t=time();
	$html="

	function cache$t(){
		if(!confirm('$add_new_cached_web_site')){return;}
		Loadjs('squid.miniwebsite.tasks.php?cache-params-js=yes&sitename=$website&with-enable=yes');
		
		
	}
			
	cache$t();		
	";
	echo $html;
}
	
page();

function page(){
	
	if(!$_SESSION["CORP"]){
		$tpl=new templates();
		$onlycorpavailable=$tpl->_ENGINE_parse_body("{onlycorpavailable}");
		$content="<p class=text-error>$onlycorpavailable</p>";
		echo $content;
		return;
	}
	
	
$t=time();
$page=CurrentPageName();
$tpl=new templates();
$sock=new sockets();
$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}
if($UnlockWebStats==1){$EnableRemoteStatisticsAppliance=0;}
$website=$tpl->_ENGINE_parse_body("{website}");
$expire_time=$tpl->_ENGINE_parse_body("{expire_time}");
$limit=$tpl->_ENGINE_parse_body("{limit}");
$add_new_cached_web_site=$tpl->_ENGINE_parse_body("{add_new_cached_web_site}");
$add_default_settings=$tpl->_ENGINE_parse_body("{add_default_settings}");
$refresh_pattern_intro=$tpl->_ENGINE_parse_body("{refresh_pattern_intro}");
$delete_all=$tpl->javascript_parse_text("{delete_all}");	


$buttons="
		{name: '$add_new_cached_web_site', bclass: 'add', onpress : AddNewCachedWebsite$t},
		{name: '$delete_all', bclass: 'Delz', onpress : delete_all$t},

";
if($EnableRemoteStatisticsAppliance==1){$buttons=null;}
	//sitename,MIN_AGE,MAX_AGE,PERCENT,options

$html="
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var websiteMem='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?websites-search=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$website', name : 'sitename', width : 399, sortable : true, align: 'left'},	
		{display: '$expire_time', name : 'MIN_AGE', width : 167, sortable : true, align: 'left'},
		{display: '%', name : 'PERCENT', width : 38, sortable : true, align: 'center'},
		{display: '$limit', name : 'MAX_AGE', width : 106, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 38, sortable : false, align: 'center'},
	],
	
	
buttons : [
		$buttons
		{separator: true}
		],

	searchitems : [
		{display: '$website', name : 'sitename'}
		],		
	
	sortname: 'sitename',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 100,
	showTableToggleBtn: false,
	width: 826,
	height: 420,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});



		function AddNewCachedWebsite$t(){
			var sitename=prompt('$website ?');
			if(sitename){
				Loadjs('squid.miniwebsite.tasks.php?cache-params-js=yes&table-t=$t&sitename='+sitename);
			}
		}

		var x_DeleteWebsiteCached$t= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);return;}
			$('#row'+websiteMem).remove();			
				
		}	

		function DeleteWebsiteCached$t(domain,id){
			websiteMem=id;
			var XHR = new XHRConnection();
			XHR.appendData('DELETE',domain);
			XHR.sendAndLoad('$page', 'POST',x_DeleteWebsiteCached$t);
		}

		function RechargeTableauDesSitesCaches(){
			$('#flexRT$t').flexReload();
		}	

		var x_delete_all$t= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			$('#flexRT$t').flexReload();			
				
		}		
	
		function delete_all$t(){
			if(confirm('$delete_all ?')){
		 		var XHR = new XHRConnection();
				XHR.appendData('delete_all','yes');
				XHR.sendAndLoad('$page', 'POST',x_delete_all$t);
			}
		}
</script>";

echo $html;
	
}
function WEBSITES_SEARCH(){
$sock=new sockets();
$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$database="artica_backup";
	
	$search='%';
	$table="websites_caches_params";
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
	
	//sitename,MIN_AGE,MAX_AGE,PERCENT,options
	$tpl=new templates();
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=md5($ligne["sitename"]);
		$delete=imgtootltip("delete-24.png","{delete}","DeleteWebsiteCached{$_GET["t"]}('{$ligne["sitename"]}','$ID')");
		$select="Loadjs('squid.miniwebsite.tasks.php?cache-params-js=yes&sitename={$ligne["sitename"]}&table-t={$_GET["t"]}');";
		
		$ligne["MIN_AGE"]=$ligne["MIN_AGE"];
		$ligne["MIN_AGE"]=$tpl->javascript_parse_text(distanceOfTimeInWords(time(),mktime()+($ligne["MIN_AGE"]*60),true));
		
		
		$ligne["MAX_AGE"]=$ligne["MAX_AGE"];
		$ligne["MAX_AGE"]=$tpl->javascript_parse_text(distanceOfTimeInWords(time(),mktime()+($ligne["MAX_AGE"]*60),true));
			
		$link="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:$select\" 
		style='font-size:14x;text-decoration:underline'>";
		if(trim($ligne["sitename"])=='.'){$ligne["sitename"]=$tpl->_ENGINE_parse_body("{all}");}
		if($EnableRemoteStatisticsAppliance==1){$delete="&nbsp;";}
	
		
	$data['rows'][] = array(
		'id' => $ID,
		'cell' => array("
		<span style='font-size:14px'>$link{$ligne["sitename"]}</a></span>"
		,"<span style='font-size:14px'>{$ligne["MIN_AGE"]}</a></span>",
		"<span style='font-size:14px'>{$ligne["PERCENT"]}%</a></span>",
		"<span style='font-size:14px'>{$ligne["MAX_AGE"]}</a></span>",$delete )
		);
	}
	
	
echo json_encode($data);		

}
function DELETE_ALL(){
	$q=new mysql_squid_builder();
	$sql="TRUNCATE TABLE websites_caches_params";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?squidnewbee=yes");	
}
function DELETE_DOM(){
	$q=new mysql_squid_builder();
	$sql="DELETE FROM websites_caches_params WHERE sitename='{$_POST["DELETE"]}'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?squidnewbee=yes");	
}