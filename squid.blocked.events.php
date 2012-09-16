<?php

	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsSquidAdministrator){die();}	
	if(isset($_GET["events"])){popup_list();exit;}
	
BlockedSites2();	

function BlockedSites2(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$webservers=$tpl->_ENGINE_parse_body("{webservers}");
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$time=$tpl->_ENGINE_parse_body("{time}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$country=$tpl->_ENGINE_parse_body("{country}");
	$url=$tpl->_ENGINE_parse_body("{url}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$rule=$tpl->_ENGINE_parse_body("{rule}");
	
	
	
	$t=time();
	$html="
	<div style='margin:-10px;margin-left:-15px;margin-right:-15px'>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	</div>
	
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?events=yes',
	dataType: 'json',
	colModel : [
		{display: '$time', name : 'zDate', width :94, sortable : true, align: 'left'},
		{display: '$member', name : 'client', width : 92, sortable : true, align: 'left'},
		{display: '$webservers', name : 'website', width : 244, sortable : true, align: 'left'},
		{display: '$category', name : 'category', width : 89, sortable : true, align: 'left'},
		{display: '$rule', name : 'rulename', width : 89, sortable : true, align: 'left'},
		
		],
		
	searchitems : [
		{display: '$member', name : 'client'},
		{display: '$webservers', name : 'website'},
		{display: '$category', name : 'category'},
		{display: '$rule', name : 'rulename'},
		],			
		
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 689,
	height: 420,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

</script>
	
	
	";
echo $html;	

}
function popup_list(){
	$ID=$_GET["taskid"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
		
	
	$search='%';
	$table=date('Ymd')."_blocked";	
	$page=1;
	$FORCE_FILTER="";
	
	if($q->COUNT_ROWS("$table",'artica_events')==0){json_error_show("No data");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,'artica_events'));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,'artica_events');
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$today=date('Y-m-d');
	if(!$q->ok){json_error_show($q->mysql_error);}	

	while ($ligne = mysql_fetch_assoc($results)) {
	$ligne["zDate"]=str_replace($today,"{today}",$ligne["zDate"]);
	if(preg_match("#plus-(.+?)-artica#",$ligne["category"],$re)){$ligne["category"]=$re[1];}
	$ligne["zDate"]=$tpl->_ENGINE_parse_body("{$ligne["zDate"]}");
		
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array(
			"<span style='font-size:12px;'>{$ligne["zDate"]}</span>",
			"<span style='font-size:12px;'>{$ligne["client"]}</a></span>",
			"<span style='font-size:12px;'><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.categories.php?category={$ligne["category"]}&website={$ligne["website"]}')\" 
			style='font-weight:bold;text-decoration:underline;font-size:13px'>{$ligne["website"]}</a></span>",
			"<span style='font-size:12px;'>{$ligne["category"]}</a></span>",
			"<span style='font-size:12px;'>{$ligne["rulename"]}</a></span>",
			)
		);
	}
	
	
echo json_encode($data);		


}



function BlockedSites(){
$page=CurrentPageName();
$tpl=new templates();		
$tableblock=date('Ymd')."_blocked";	
$q=new mysql_squid_builder();
$sql="SELECT * FROM $tableblock ORDER BY ID DESC LIMIT 0,150";



$results=$q->QUERY_SQL($sql,"artica_events");
if(!$q->ok){
	echo "<H2>$q->mysql_error</H2>";	
	
}	
	$html="<center>
<table cellspacing='0' cellpadding='0' border='0' class='tableView'>
<thead class='thead'>
	<tr>
	<th width=1%>{date}</th>
	<th>{member}</th>
	<th>{website}</th>
	<th>{category}</th>
	<th>{rule}</th>
	</tr>
</thead>
<tbody>";	


$today=date('Y-m-d');
$d+0;
while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
	if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
	$ligne["zDate"]=str_replace($today,"{today}",$ligne["zDate"]);
	if(preg_match("#plus-(.+?)-artica#",$ligne["category"],$re)){$ligne["category"]=$re[1];}
	$html=$html."
	<tr class=$classtr>
		<td style='font-size:13px' nowrap width=1%>{$ligne["zDate"]}</td>
		<td style='font-size:13px' width=1%>{$ligne["client"]}</td>
		<td style='font-size:13px' width=99%><strong><code>
		<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.categories.php?category={$ligne["category"]}&website={$ligne["website"]}')\" 
		style='font-weight:bold;text-decoration:underline;font-size:13px'>{$ligne["website"]}</a></code></strong></td>
		<td style='font-size:13px' width=1% align='center'>{$ligne["category"]}</td>
		<td style='font-size:13px' width=1% align='center'>{$ligne["rulename"]}</td>
	</tr>
	";
	
}
$html=$html."</tbody></table>";
echo $tpl->_ENGINE_parse_body($html);
}