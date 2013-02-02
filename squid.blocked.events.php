<?php

	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsSquidAdministrator){die();}	
	if(isset($_GET["events"])){popup_list();exit;}
	if(isset($_POST["unlock"])){unlock();exit;}
	if(isset($_GET["js"])){js();exit;}
BlockedSites2();	


function js(){
	header("content-type: application/x-javascript");
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{blocked_requests}");
	echo "YahooWin4('705','$page?popup=yes&t=$t&noreduce=yes','$title')";

}

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
	$title=$tpl->_ENGINE_parse_body(date("{l} d {F}")." {blocked_requests}");
	$unblock=$tpl->javascript_parse_text("{unblock}");
	$UnBlockWebSiteExplain=$tpl->javascript_parse_text("{UnBlockWebSiteExplain}");
	
	$divstart="<div style='margin:-10px;margin-left:-15px;margin-right:-15px'>";
	$divend="</div>";
	if(isset($_GET["noreduce"])){$divstart=null;$divend=null;}
	
	$t=time();
	$html="
	$divstart
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	$divend
	
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?events=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$time', name : 'zDate', width :94, sortable : true, align: 'left'},
		{display: '$member', name : 'client', width : 92, sortable : true, align: 'left'},
		{display: '$webservers', name : 'website', width : 208, sortable : true, align: 'left'},
		{display: '$category', name : 'category', width : 89, sortable : true, align: 'left'},
		{display: '$rule', name : 'rulename', width : 89, sortable : true, align: 'left'},
		{display: '$unblock', name : 'unblock', width : 31, sortable : true, align: 'center'},
		
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
	title: '<span style=\"font-size:14px\">$title</span>',
	rp: 50,
	showTableToggleBtn: false,
	width: 689,
	height: 600,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500,1000,1500]
	
	});   
});

	var x_UnBlockWebSite$t=function(obj){
	      var tempvalue=obj.responseText;
	      if(tempvalue.length>3){alert(tempvalue);}
	      $('#flexRT$t').flexReload();
	}	

function UnBlockWebSite$t(domain){
	if(confirm('$UnBlockWebSiteExplain:'+domain+' ?')){
		var XHR = new XHRConnection();
		XHR.appendData('unlock',domain);
		XHR.sendAndLoad('$page', 'POST',x_UnBlockWebSite$t);
	}

}

</script>
	
	
	";
echo $html;	

}
function popup_list(){
	$ID=$_GET["taskid"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$t=$_GET["t"];
	
	$search='%';
	$table=date('Ymd')."_blocked";	
	$page=1;
	$FORCE_FILTER="";
	if(!$q->TABLE_EXISTS("$table")){json_error_show("$table No such table");}
	if($q->COUNT_ROWS("$table",'artica_events')==0){json_error_show("No data");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	$q2=new mysql();

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
	$id=md5(serialize($ligne));
	
	
	$member=$ligne["client"];
	if($ligne["hostname"]<>null){$member=$ligne["hostname"];}
	if($ligne["uid"]<>null){$member=$ligne["uid"];}
		
	$unblock=imgsimple("whitelist-24.png",null,"UnBlockWebSite$t('{$ligne["website"]}')");
	
	$ligne3=mysql_fetch_array($q2->QUERY_SQL("SELECT items FROM urlrewriteaccessdeny WHERE items='{$ligne["website"]}'","artica_backup"));
	if(!$q->ok){
		$unblock="<img src='img/icon_err.gif'><br>$q->mysql_error";
	}else{
		if($ligne3["items"]<>null){
		$unblock=imgsimple("20-check.png",null,null);
		}
	}
	
	
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array(
			"<span style='font-size:12px;'>{$ligne["zDate"]}</span>",
			"<span style='font-size:12px;'>$member</a></span>",
			"<span style='font-size:12px;'><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.categories.php?category={$ligne["category"]}&website={$ligne["website"]}')\" 
			style='font-weight:bold;text-decoration:underline;font-size:13px'>{$ligne["website"]}</a></span>",
			"<span style='font-size:12px;'>{$ligne["category"]}</a></span>",
			"<span style='font-size:12px;'>{$ligne["rulename"]}</a></span>",
			$unblock
			)
		);
	}
	
	
echo json_encode($data);		


}

function unlock(){
	
	$table="urlrewriteaccessdeny";
	$q=new mysql();
	$q->QUERY_SQL("INSERT IGNORE INTO urlrewriteaccessdeny (items) VALUES ('{$_POST["unlock"]}')","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	if(isset($_POST["noreload"])){return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-whitelist=yes");
}



