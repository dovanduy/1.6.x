<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.artica.inc');
include_once('ressources/class.ini.inc');
include_once('ressources/class.squid.inc');
include(dirname(__FILE__)."/ressources/class.influx.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");


	$user=new usersMenus();
	if(!$user->AsWebStatisticsAdministrator){
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		exit;
	}
	if(isset($_GET["stats-requeteur"])){stats_requeteur();exit;}
	if(isset($_GET["requeteur-popup"])){requeteur_popup();exit;}
	if(isset($_GET["requeteur-js"])){requeteur_js();exit;}	
	if(isset($_GET["query-js"])){build_query_js();exit;}
	if(isset($_GET["search"])){search();exit;}
	
table();


function table(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$t=time();
	$members=$tpl->_ENGINE_parse_body("{members}");
	$add_member=$tpl->_ENGINE_parse_body("{add_member}");

	$delete=$tpl->javascript_parse_text("{delete}");
	$aliases=$tpl->javascript_parse_text("{aliases}");
	$about2=$tpl->_ENGINE_parse_body("{about2}");
	$new_report=$tpl->javascript_parse_text("{new_report}");
	$report=$tpl->javascript_parse_text("{report}");
	$title=$tpl->javascript_parse_text("{current_members}");
	$progress=$tpl->javascript_parse_text("{progress}");
	$size=$tpl->javascript_parse_text("{size}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$computers=$tpl->javascript_parse_text("{computers}");
	$my_proxy_aliases=$tpl->javascript_parse_text("{my_proxy_aliases}");
	$q=new mysql_squid_builder();
	
//current_members
	$t=time();
	$buttons="
	buttons : [
		{name: '<strong style=font-size:22px>$my_proxy_aliases</strong>', bclass: 'link', onpress : GoToProxyAliases$t},
		{name: '<strong style=font-size:22px>$computers</strong>', bclass: 'link', onpress : GotoNetworkBrowseComputers$t},
	],";

	
	
	$html="
	<table class='SQUID_CURRENT_MEMBERS' style='display: none' id='SQUID_CURRENT_MEMBERS' style='width:100%'></table>
	<script>
	$(document).ready(function(){
	$('#SQUID_CURRENT_MEMBERS').flexigrid({
	url: '$page?search=yes&ID={$_GET["ID"]}',
	dataType: 'json',
	colModel : [
	{display: '<strong style=font-size:18px>$members</strong>', name : 'member', width : 418, sortable : true, align: 'left'},
	{display: '<strong style=font-size:18px>$hits</strong>', name : 'hits', width : 228, sortable : true, align: 'right'},
	{display: '<strong style=font-size:18px>$size</strong>', name : 'size', width : 228, sortable : false, align: 'right'},
	],
	$buttons
	searchitems : [
	{display: '$members', name : 'member'},
	
	],
	sortname: 'size',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: '500',
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
});


function NewReport$t(){
	Loadjs('squid.browse-users.php?callback=Addcategory$t');
}

function GoToProxyAliases$t(){
	GoToProxyAliases();
}

function GotoNetworkBrowseComputers$t(){
	GotoNetworkBrowseComputers();
}

var xAddcategory$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#SQUID_MAIN_REPORTS').flexReload();
	$('#SQUID_MAIN_REPORTS_USERZ').flexReload();
}

function Addcategory$t(field,value){
	var XHR = new XHRConnection();
	XHR.appendData('ID','{$_GET["ID"]}');
	XHR.appendData('field',field);
	XHR.appendData('value',value);
	XHR.sendAndLoad('$page', 'POST',xAddcategory$t);
}
</script>
	";

	echo $tpl->_ENGINE_parse_body($html);


}

function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$table="current_members";
	$q=new mysql_squid_builder();
	$t=$_GET["t"];


	$total=0;
	if($q->COUNT_ROWS($table,"artica_backup")==0){json_error_show("no data [".__LINE__."]",0);}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}


	$searchstring=string_to_flexquery();

	
	$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	$total = $ligne["TCOUNT"];

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=100;}


	$pageStart = ($page-1)*$rp;
	if($pageStart<0){$pageStart=0;}
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql",0);}

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	$CurrentPage=CurrentPageName();

	if(mysql_num_rows($results)==0){json_error_show("no data");}
	$searchstring=string_to_flexquery();


	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}
	$q1=new mysql();
	$t=time();

	$fontsize=22;



	$span="<span style='font-size:{$fontsize}px'>";
	$IPTCP=new IP();


	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$zmd5=$ligne["zmd5"];
		$member_value=trim($ligne["member"]);
		$hits=FormatNumber($ligne["hits"]);
		$size=FormatBytes($ligne["size"]/1024);
		$ahref=null;
		$member_assoc=null;
		
		
		if($IPTCP->IsvalidMAC($member_value)){
			$mac_encoded=urlencode($member_value);
			$uid=$q->MacToUid($member_value);
			if($uid<>null){$member_assoc="&nbsp; ($uid)";}
			$ahref="<a href=\"javascript:blur();\"
					OnClick=\"javascript:Loadjs('squid.nodes.php?node-infos-js=yes&MAC=$mac_encoded');\"
					style='font-size:$fontsize;text-decoration:underline'>";
			}

		$data['rows'][] = array(
				'id' => $member_value,
				'cell' => array(
						"$span$ahref$member_value</a>$member_assoc</span>",
						"$span$hits</a></span>",
						"$span$size</a></span>",

				)
		);

	}
	echo json_encode($data);

}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}