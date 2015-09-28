<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.artica.inc');
include_once('ressources/class.ini.inc');
include_once('ressources/class.squid.inc');
$users=new usersMenus();
if(!$users->AsWebStatisticsAdministrator){$tpl=new templates();echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."')";die();}


if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["list"])){table_list();exit;}

js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$report_type=$_GET["report_type"];
	$title=$tpl->javascript_parse_text("{browse_cache}:$report_type");
	echo "YahooWin6('850','$page?popup=yes&report_type=$report_type&t={$_GET["t"]}','$title')";
}


function popup(){

	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$report=$tpl->_ENGINE_parse_body("{report}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$familysite=$tpl->_ENGINE_parse_body("{familysite}");
	$title=$tpl->javascript_parse_text("{browse_cache}:{$_GET["report_type"]}");
	$html="
	<table class='BROWSE_STATISTICS_CACHES' style='display: none' id='BROWSE_STATISTICS_CACHES' style='width:99%'></table>
<script>
$(document).ready(function(){
	$('#BROWSE_STATISTICS_CACHES').flexigrid({
	url: '$page?list=yes&report_type={$_GET["report_type"]}&t={$_GET["t"]}',
	dataType: 'json',
	colModel : [
	{display: '$report', name : 'title', width :610, sortable : true, align: 'left'},
	{display: '$size', name : 'values_size', width :100, sortable : true, align: 'right'},
	{display: 'DEL', name : 'del', width :50, sortable : false, align: 'center'},
	],
	searchitems : [
	{display: '$report', name : 'title'},
	],

	sortname: 'title',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:22px>$title</span>',
	useRp: false,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 418,
	singleSelect: true

});
});
</script>

";


	echo $tpl->_ENGINE_parse_body($html);

}


function table_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	
	
	$search='%';
	$table="(SELECT title,zmd5,values_size FROM reports_cache WHERE report_type='{$_GET["report_type"]}') as t";
	$page=1;
	
	
		
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	
	$searchstring=string_to_flexquery();
	$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$total = $ligne["TCOUNT"];
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	
	if(!$q->ok){json_error_show($q->mysql_error);}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	
	while ($ligne = mysql_fetch_assoc($results)) {
		$zmd5=$ligne["zmd5"];
		$title=$tpl->javascript_parse_text($ligne["title"]);
		$values_size=$ligne["values_size"];
		if($values_size>1024){
			$values_size=FormatBytes($values_size/1024);
		}else{
			$values_size="{$values_size} Bytes";
		}
	
		$ligne["title"]=$tpl->javascript_parse_text($ligne["title"]);
		$delete=imgsimple("delete-32.png",null,"Loadjs('squid.statistics.flow.php?remove-cache-js=yes&zmd5=$zmd5')");
		
			$data['rows'][] = array(
					'id' => $zmd5,'cell' => array(
					"<span style='font-size:18px'>$linkfamily{$ligne["title"]}</a></span>",
					"<span style='font-size:18px'>$values_size</a></span>",
					$delete
					 
			)
			
			);
			
		}
	
	
		echo json_encode($data);
	
	}
	