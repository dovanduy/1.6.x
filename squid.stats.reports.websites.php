<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.status.inc');
include_once('ressources/class.artica.graphs.inc');
$users=new usersMenus();
if(!$users->AsWebStatisticsAdministrator){die();}


if(isset($_GET["delete-js"])){categorykey_delete_js();exit;}
if(isset($_POST["delete"])){categorykey_delete();exit;}
if($_POST["familysite"]){familysite_save();exit;}
if(isset($_GET["search"])){search();exit;}


table();

//squid_reports_websites

function table(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$t=time();
	$websites=$tpl->_ENGINE_parse_body("{websites}");
	$add_website=$tpl->_ENGINE_parse_body("{add_website}");

	$delete=$tpl->javascript_parse_text("{delete}");
	$aliases=$tpl->javascript_parse_text("{aliases}");
	$about2=$tpl->_ENGINE_parse_body("{about2}");
	$new_report=$tpl->javascript_parse_text("{new_report}");
	$report=$tpl->javascript_parse_text("{report}");
	$title=$tpl->javascript_parse_text("{statistics}:: {websites}");
	$progress=$tpl->javascript_parse_text("{progress}");
	$run=$tpl->javascript_parse_text("{run}");
	$explain=$tpl->javascript_parse_text("{explain}");
	$q=new mysql_squid_builder();
	


	$buttons="
	buttons : [
	{name: '<strong style=font-size:16px >$add_website</strong>', bclass: 'add', onpress : NewReport$t},
	],";

	
	$html="
	<table class='SQUID_MAIN_REPORTS_WEBZ' style='display: none' id='SQUID_MAIN_REPORTS_WEBZ' style='width:100%'></table>
	<script>
	$(document).ready(function(){
	$('#SQUID_MAIN_REPORTS_WEBZ').flexigrid({
	url: '$page?search=yes&ID={$_GET["ID"]}',
	dataType: 'json',
	colModel : [
	{display: '$websites', name : 'familysite', width : 600, sortable : true, align: 'left'},
	{display: '$delete;', name : 'explain', width : 70, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$websites', name : 'familysite'},
	
	],
	sortname: 'familysite',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:18px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: '350',
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
});


function NewReport$t(){
	Loadjs('squid.browse-familysites.php?callback=Addcategory$t');
}

var xAddcategory$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#SQUID_MAIN_REPORTS').flexReload();
	$('#SQUID_MAIN_REPORTS_WEBZ').flexReload();
}

function Addcategory$t(familysite){
	var XHR = new XHRConnection();
	XHR.appendData('ID','{$_GET["ID"]}');
	XHR.appendData('familysite',familysite);
	XHR.sendAndLoad('$page', 'POST',xAddcategory$t);
}
</script>
	";

	echo $tpl->_ENGINE_parse_body($html);


}

function categorykey_delete_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	
	$delete=$tpl->javascript_parse_text("{delete} {$_GET["category"]}");
	$t=time();
	
	$html="
var xFunction$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#SQUID_MAIN_REPORTS').flexReload();
	$('#SQUID_MAIN_REPORTS_WEBZ').flexReload();
}
	
function Function$t(){
	var alias=confirm('$delete ?');
	if(alias){
	var XHR = new XHRConnection();
		XHR.appendData('delete','{$_GET["md5"]}');
		XHR.sendAndLoad('$page', 'POST',xFunction$t);
	}
}
	
	Function$t();
	";
	echo $html;
	}

function familysite_save(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `squid_reports_websites` (
				   `zmd5` VARCHAR( 90 ) NOT NULL,
					familysite VARCHAR(128) NOT NULL,
					report_id  INT( 10 ) NOT NULL,
					UNIQUE KEY `zmd5` (`zmd5`),
					KEY `report_id` (`report_id`),
					KEY `familysite` (`familysite`)
				)  ENGINE = MYISAM;");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$ID=$_POST["ID"];
	$familysite=$_POST["familysite"];
	$zmd5=md5("$ID$category");

	$q->QUERY_SQL("INSERT IGNORE INTO squid_reports_websites (zmd5,familysite,report_id) VALUES
			('$zmd5','$familysite','$ID')");
	
	if(!$q->ok){echo $q->mysql_error;}
	
	
}

function categorykey_delete(){
	$q=new mysql_squid_builder();
	$q->QUERY_PDO("DELETE FROM squid_reports_websites WHERE `zmd5`='{$_POST["delete"]}'");
	if(!$q->ok){echo $q->mysql_error;}
}


function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$table="squid_reports_websites";
	$q=new mysql_squid_builder();
	$FORCE="report_id={$_GET["ID"]}";
	$t=$_GET["t"];


	$total=0;
	if($q->COUNT_ROWS($table,"artica_backup")==0){json_error_show("no data [".__LINE__."]",0);}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}


	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];

	}else{
		if(strlen($FORCE)>2){
			$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
			$total = $ligne["TCOUNT"];
		}else{
			$total = $q->COUNT_ROWS($table, "artica_events");
		}
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=100;}


	$pageStart = ($page-1)*$rp;
	if($pageStart<0){$pageStart=0;}
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT *  FROM `$table` WHERE $FORCE $searchstring $ORDER $limitSql";
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
	
	$report=$tpl->javascript_parse_text("{report}");
	$category=$tpl->javascript_parse_text("{category}");
	$from_the_last_time=$tpl->javascript_parse_text("{from_the_last_time}");
	$report_not_categorized_text=$tpl->javascript_parse_text("{report_not_categorized}");
	$error_engine_categorization=$tpl->javascript_parse_text("{error_engine_categorization}");

	$span="<span style='font-size:{$fontsize}px'>";

	

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$zmd5=$ligne["zmd5"];
		$category=$ligne["familysite"];
		$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?delete-js=yes&md5=$zmd5&category=$category')");
		
		$data['rows'][] = array(
				'id' => $zmd5,
				'cell' => array(
						"$span$category</a></span>",
						"$delete",
						
				)
		);

	}
	echo json_encode($data);

}