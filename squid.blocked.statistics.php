<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die();}
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["squid-general-status"])){general_status();exit;}
	if(isset($_GET["squid-status-stats"])){squid_status_stats();exit;}
	if(isset($_GET["squid-status-graphs"])){general_status_graphs();exit;}
	if(isset($_GET["squid-cache-flow-performance"])){general_status_cache_graphs();exit;}
	if(isset($_GET["day-consumption"])){day_consumption();exit;}
	if(isset($_GET["now"])){now_search();exit;}
	if(isset($_GET["now-search"])){now_search_list();exit;}
	
	
	
tabs();


function tabs(){
	
	$page=CurrentPageName();
	
	$tpl=new templates();
	$array["status"]='{status}';
	$array["now"]='{now}';
	$array["day-consumption"]='{days}';
	$array["week-consumption"]='{week}';
//	$array["month-consumption"]='{month}';
	
	
	

while (list ($num, $ligne) = each ($array) ){
		if($num=="day-consumption"){
			$html[]= "<li><a href=\"squid.blocked.statistics.days.php?$num=yes\"><span>$ligne</span></a></li>\n";
			continue;
		}
		if($num=="week-consumption"){
			$html[]= "<li><a href=\"squid.blocked.statistics.week.php\"><span>$ligne</span></a></li>\n";
			continue;
		}	
	
		$html[]= "<li><a href=\"$page?$num=yes\"><span>$ligne</span></a></li>\n";
	}
	
	
	echo $tpl->_ENGINE_parse_body( "
	<div id=squid_stats_blocked style='width:100%;font-size:14px;margin-left:-15px'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#squid_stats_blocked').tabs();
			
			
			});
		</script>");		
}

function status(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$html="
	<div style='width:104.5%;margin:10px;margin-left:-15px'>
	<table style='width:100%'>
	<tbody>
	<tr>
		<td valign='top' width=1%><div id='squid-general-status'></div></td>
		<td valign='top' width=99% style='padding-left:15px'><div id='squid-status-graphs' style='width:99%'></div></td>
	</tr>
	</tbody>
	</table>
	</div>
	<script>
		LoadAjax('squid-general-status','$page?squid-general-status=yes');
	
	</script>
	";
	
	echo $html;
	
	
	
	
}

function general_status(){
	$page=CurrentPageName();
	$tpl=new templates();		

	$stylehref="style='font-size:14px;font-weight:bold;text-decoration:underline'";
	$img="img/server-256.png";
	$html="
	<div class=form>
	<center style='margin:5px'>
	<img src='$img'>
	</center>
	<div id='squid-status-stats'></div>
	
	<p>&nbsp;</p>
	
	<script>
		LoadAjax('squid-status-stats','squid.traffic.statistics.php?squid-status-stats=yes');	
		LoadAjax('squid-status-graphs','$page?squid-status-graphs=yes');
		
	</script>
	</div>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}



function general_status_graphs(){
	$page=CurrentPageName();
	$tpl=new templates();		
	$q=new mysql_squid_builder();	
	$selected_date="{last_30days}";
	$filter="zDate>DATE_SUB(NOW(),INTERVAL 30 DAY) AND zDate<DATE_SUB(NOW(),INTERVAL 1 DAY)";
	$file_prefix="default";
	$type='hits';
	$field="totalBlocked";
	$prefix_title="{blocked} ({hits})";
	$hasSize=false;
	
	if(isset($_GET["from"])){
		$filter="zDate>='{$_GET["from"]}' AND zDate<='{$_GET["to"]}'";
		$selected_date="{from_date} {$_GET["from"]} - {to_date} {$_GET["to"]}";
		$default_from_date=$_GET["from"];
		$default_to_date=$_GET["to"];
		$file_prefix="$default_from_date-$default_to_date";
	}
	
	
	
	$sql="SELECT $field,DATE_FORMAT(zDate,'%d') as tdate FROM tables_day WHERE $filter ORDER BY zDate";
	
	$results=$q->QUERY_SQL($sql);

	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$xdata[]=$ligne["tdate"];
		if($hasSize){$ydata[]=round(($ligne[$field]/1024)/1000);}else{$ydata[]=$ligne[$field];}
	}
	
	$targetedfile="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".blocked.$file_prefix.$type.png";
	$gp=new artica_graphs();
	$gp->width=540;
	$gp->height=350;
	$gp->filename="$targetedfile";
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;
	$gp->y_title=null;
	$gp->x_title=$tpl->_ENGINE_parse_body("{days}");
	$gp->title=null;
	$gp->margin0=true;
	$gp->Fillcolor="blue@0.9";
	$gp->color="146497";

	$gp->line_green();
	if(!is_file($targetedfile)){writelogs("Fatal \"$targetedfile\" no such file!",__FUNCTION__,__FILE__,__LINE__);$targetedfile="img/kas-graph-no-datas.png";}
	
	if($default_from_date==null){
		$sql="SELECT DATE_FORMAT(DATE_SUB(NOW(),INTERVAL 30 DAY),'%Y-%m-%d') as tdate";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$default_from_date=$ligne["tdate"];
	}
	
	if($default_to_date==null){
		$sql="SELECT DATE_FORMAT(DATE_SUB(NOW(),INTERVAL 1 DAY),'%Y-%m-%d') as tdate";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$default_to_date=$ligne["tdate"];
	}	
	
	$sql="SELECT DATE_FORMAT(zDate,'%Y-%m-%d') as tdate FROM tables_day ORDER BY zDate LIMIT 0,1";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$mindate=$ligne["tdate"];

	$sql="SELECT DATE_FORMAT(zDate,'%Y-%m-%d') as tdate FROM tables_day ORDER BY zDate DESC LIMIT 0,1";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$maxdate=$ligne["tdate"];		
	
	echo $tpl->_ENGINE_parse_body("<div ><h3 style='margin-bottom:10px;color:black'> $prefix_title/{days} - $selected_date</h3>
	<center>
	<img src='$targetedfile'>
	</center>
	</div>
	<table style='margin-top:10px' class=form>
	<tbody>
	<tr>
		<td class=legend nowrap>{from_date}:</td>
		<td>". field_date('from_date1',$default_from_date,"font-size:16px;padding:3px;width:95px","mindate:$mindate;maxdate:$maxdate")."</td>
		
		<td class=legend nowrap>{to_date}:</td>
		<td>". field_date('to_date1',$default_to_date,"font-size:16px;padding:3px;width:95px","mindate:$mindate;maxdate:$maxdate")."</td>
		<td width=1%>". button("{apply}","SquidFlowSizeQuery('$type')")."</td>
	</tr>
	</table>
	<p>&nbsp;</p>
	<div id='squid-cache-flow-performance'></div>
	
	<script>
		function SquidFlowSizeQuery(type){
			if(!type){type='';}
			var from=document.getElementById('from_date1').value;
			var to=document.getElementById('to_date1').value;
			LoadAjax('squid-status-graphs','$page?squid-status-graphs=yes&from='+from+'&to='+to+'&type='+type);
		
		}
		
		LoadAjax('squid-cache-flow-performance','$page?squid-cache-flow-performance=yes&from=$default_from_date&to=$default_to_date&type=$type');
		
	</script>
	
	");
	
}



function general_status_cache_graphs(){
	$page=CurrentPageName();
	$tpl=new templates();		
	$_GET["type"]="req";
	
	
	$q=new mysql_squid_builder();	
	$selected_date="{last_30days}";
	$filter="zDate>DATE_SUB(NOW(),INTERVAL 30 DAY) AND zDate<DATE_SUB(NOW(),INTERVAL 1 DAY)";
	$file_prefix="default";
	
	if($_GET["from"]<>null){
		$filter="zDate>='{$_GET["from"]}' AND zDate<='{$_GET["to"]}'";
		$selected_date="{from_date} {$_GET["from"]} - {to_date} {$_GET["to"]}";
		$default_from_date=$_GET["from"];
		$default_to_date=$_GET["to"];
		$file_prefix="$default_from_date-$default_to_date";
	}
	
	if($_GET["type"]<>null){
		if($_GET["type"]=="req"){
			$field="requests as totalsize";
			$prefix_title="{requests}";
			$hasSize=false;
		}
	}	
	
	
	$sql="SELECT totalBlocked as totalsize,DATE_FORMAT(zDate,'%d') as tdate FROM tables_day WHERE $filter ORDER BY zDate";
	
	$results=$q->QUERY_SQL($sql);

	if(!$q->ok){echo "<H2>$q->mysql_error</H2>";}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$xdata[]=$ligne["tdate"];
		$ydata[]=$ligne["totalBlocked"];
	}
	
	$targetedfile="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".cache-perf.$file_prefix.png";
	$gp=new artica_graphs();
	$gp->width=550;
	$gp->height=350;
	$gp->filename="$targetedfile";
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;
	$gp->y_title=null;
	$gp->x_title=$tpl->_ENGINE_parse_body("{days}");
	$gp->title=null;
	$gp->margin0=true;
	$gp->Fillcolor="blue@0.9";
	$gp->color="146497";

	$gp->line_green();
	if(!is_file($targetedfile)){writelogs("Fatal \"$targetedfile\" no such file!",__FUNCTION__,__FILE__,__LINE__);return;}
	echo $tpl->_ENGINE_parse_body("<div ><h3>{blocked} (requests) /{days} - $selected_date</h3>
	<center>
	<img src='$targetedfile'>
	</center>
	</div>");
	
}

function now_search(){
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
	$rulename =$tpl->_ENGINE_parse_body("{rulename}");
	$title=$tpl->_ENGINE_parse_body("{today}: {blocked} (requests)");
	$why=$tpl->_ENGINE_parse_body("{why}");
	$url=$tpl->_ENGINE_parse_body("{url}");
	$t=time();
	$html="
	<div style='margin:-10px;margin-left:-15px'>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	</div>
	
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?now-search=yes',
	dataType: 'json',
	colModel : [
		{display: '$time', name : 'zDate', width :115, sortable : true, align: 'left'},
		{display: '$webservers', name : 'website', width : 135, sortable : false, align: 'left'},
		{display: '$member', name : 'member', width : 180, sortable : false, align: 'left'},
		{display: '$rulename', name : 'rulename', width : 181, sortable : false, align: 'left'},
		{display: '$why', name : 'why', width : 165, sortable : true, align: 'left'}

		],
	
	searchitems : [
		{display: '$webservers', name : 'website'},
		{display: '$url', name : 'uri'}
		],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 855,
	height: 420,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

function SelectGrid2(com, grid) {
	var items = $('.trSelected',grid);
	var id=items[0].id;
	id = id.substring(id.lastIndexOf('row')+3);
	if (com == 'Select') {
			LoadAjax('table-1-selected','$page?familysite-show='+id);
		}
	}
	 
	$('table-1-selected').remove();
	$('flex1').remove();		 

</script>
	
	
	";
	
	echo $html;
	
}
function now_search_list(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_squid_builder();	
	$search=$_GET["search"];

	//if(CACHE_SESSION_GET(__FUNCTION__.$search,__FILE__,2)){return;}
	
	$search='%';
	$table=date("Ymd")."_blocked";
	$page=1;
	$ORDER="ORDER BY ID DESC";	
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}	
	if(isset($_POST['rp'])) {$rp = $_POST['rp'];}

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="WHERE (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$total = $q->COUNT_ROWS($table);
	}
	
		
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";	
	
		
	
	
	if($q->COUNT_ROWS($table)==0){return;}
	
	$sql="SELECT *,DATE_FORMAT(zDate,'%H:%i:%s') as ttime  FROM `$table` $QUERY $ORDER $limitSql";
	$results=$q->QUERY_SQL($sql);
	
//&nbsp;|&nbsp;{$ligne["CLIENT"]}&nbsp;|&nbsp;{$ligne["uid"]}
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$results = $q->QUERY_SQL($sql);
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	while ($ligne = mysql_fetch_assoc($results)) {
		
	if(isset($ligne["QuerySize"])){
		if($ligne["QuerySize"]>1024){
			$ligne["QuerySize"]=FormatBytes($ligne["QuerySize"]/1024);
		}else{
			$ligne["QuerySize"]="{$ligne["QuerySize"]} Bytes";
		}
	}
	
		$c++;
		$linkZoom="<a href=\"javascript:Loadjs('squid.blocked.statistics.days.php?Zoom-js={$ligne['ID']}&table=$table');\" style='font-size:12px;text-decoration:underline'>";
		$data['rows'][] = array(
			'id' => "{$ligne["ID"]}",
			'cell' => array(
			"{$ligne["zDate"]}",
			"$linkZoom{$ligne["website"]}</a>","{$ligne["client"]}&nbsp;|&nbsp;{$ligne["hostname"]}",
			$ligne["rulename"],$ligne["why"])
		);
	}
	echo json_encode($data);	
	//echo $json;
	//CACHE_SESSION_SET(__FUNCTION__.$search, __FILE__,$json);
	
}