<?php
	$GLOBALS["title_array"]["size"]="{downloaded_flow}";
	$GLOBALS["title_array"]["req"]="{requests}";	
	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die();}	

	
	if(!isset($_GET["day"])){$_GET["day"]=date("Y-m-d");}
	if(!isset($_GET["type"])){$_GET["type"]="size";}
	if($_GET["type"]==null){$_GET["type"]="size";}
	if($_GET["day"]==null){$q=new mysql_squid_builder();$_GET["day"]=date("Y-m-d");}		
	
	if(isset($_GET["week-right-infos"])){right();die();}
	if(isset($_GET["week-left-menus"])){left();die();}
	if(isset($_GET["today-zoom"])){today_zoom_js();exit;}
	if(isset($_GET["today-zoom-popup"])){today_zoom_popup();exit;}
	if(isset($_GET["statistics-week-left-status"])){left_status();exit;}
	if(isset($_GET["view-table"])){view_table();exit;}
	if(isset($_GET["Zoom-js"])){ZOOM_JS();exit;}
	if(isset($_GET["block-zoom-popup"])){ZOOM_POPUP();exit;}
page();

function ZOOM_JS(){
	$ID=$_GET["Zoom-js"];
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM {$_GET["table"]} WHERE ID=$ID";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$page=CurrentPageName();
	$tpl=new templates();	
	$title="$ID: {$ligne["zDate"]} - {$ligne["website"]}";		
	$html="YahooWin2(650,'$page?block-zoom-popup=yes&ID=$ID&table={$_GET["table"]}','$title')";
	echo $html;	
	
}

function ZOOM_POPUP(){
	$ID=$_GET["ID"];
	$q=new mysql_squid_builder();
	$tpl=new templates();	
	$sql="SELECT * FROM {$_GET["table"]} WHERE ID=$ID";	
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(isset($ligne["website"])){$website=$ligne["website"];}
	$html="
	<div id='getCategoriesof'></div>
	
	
	<table style='width:99%' class=form>
	<tbody>
	
	";
	while (list ($index, $line) = each ($ligne)){
		if(is_numeric($index)){continue;}
		if(trim($line)==null){continue;}
		$ligne["website"]=texttooltip($ligne["website"],
		$ligne["uri"],"s_PopUpScroll('{$ligne["uri"]}',800,600,'{$ligne["blocktype"]} {$ligne["category"]}')",null,0,"text-decoration:underline");
		unset($ligne["uri"]);
		$html=$html."
		<tr>
		<td class=legend>{{$index}}:</td>
		<td style='font-size:13px'>$line</td>
		</tr>";
		
		
	}
	$html=$html."</tbody>
	</table>
	<script>
		LoadAjaxTiny('getCategoriesof','squid.search.statistics.php?search-stats-categories=$website');
	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function today_zoom_js(){
	$page=CurrentPageName();
	$tpl=new templates();			
	$html="YahooWin2(710,'$page?today-zoom-popup=yes&category={$_GET["category"]}&day={$_GET["day"]}','{$_GET["category"]}')";
	echo $html;
}

function today_zoom_popup(){
	$page=CurrentPageName();
	$tpl=new templates();		
	$q=new mysql_squid_builder();	
	

	$type=$_GET["type"];
	$field_query="size";
	$field_query2="SUM(size)";	
	$table_field="{size}";
	$time=strtotime("{$_GET["day"]} 00:00:00");
	$SourceTable=date("YW",$time)."_blocked_week";	
	
	$_GET["week"]=date("W",$time);
	$_GET["year"]=date("Y",$time);
	$tt=getDaysInWeek($_GET["week"],$_GET["year"]);
	foreach ($tt as $dayTime) {$f[]=date('{l} d {F}', $dayTime);}	
	$subtitle="{week}:&nbsp;{from}&nbsp;{$f[0]}&nbsp;{to}&nbsp;{$f[6]}";	
	
	$title="<div style='font-size:16px;width:100%;font-weight:bold'>{$_GET["category"]}:&nbsp;$subtitle</div>";
	if(!$q->TABLE_EXISTS($SourceTable)){echo $tpl->_ENGINE_parse_body("
	$title
	<center style='margin:50px'>
		<H2>{$_GET["day"]} table:$SourceTable</H2>
		<H2>{error_no_datas}$SourceTable</H2>
	</center>");
	return;}
	
	
	if($type=="req"){
		$field_query="hits";
		$field_query2="COUNT(zMD5)";
		$table_field="{hits}";	
	}
	
	$sql="SELECT hits,`day` as tdate,category FROM $SourceTable WHERE category='{$_GET["category"]}' ORDER BY `day`";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}	
	if(mysql_num_rows($results)==0){echo $tpl->_ENGINE_parse_body("$title<center style='margin:50px'><H2>{error_no_datas}</H2>$sql</center>");return;}
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$xdata[]=$ligne["tdate"];
		$ydata[]=$ligne["hits"];
	}	
	
	$targetedfile="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".day.$hour_table.{$_GET["familysite"]}.$type.png";
	$gp=new artica_graphs();
	$gp->width=550;
	$gp->height=220;
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
	
	$image="<center style='margin-top:10px'><img src='$targetedfile'></center>";
	if(!is_file($targetedfile)){writelogs("Fatal \"$targetedfile\" no such file!",__FUNCTION__,__FILE__,__LINE__);$image=null;}
	
	$table="<center>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:350px'>
<thead class='thead'>
	<tr>
	<th>{hits}</th>
	<th>{website}</th>
	</tr>
</thead>
<tbody>";	
	
	$sql="SELECT SUM(hits) AS hits,website,category FROM $SourceTable GROUP BY website,category HAVING category='{$_GET["category"]}' ORDER BY hits DESC";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}	
	if(mysql_num_rows($results)==0){echo $tpl->_ENGINE_parse_body("$title<center style='margin:50px'><H2>{error_no_datas}</H2></center>");return;}
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		
		$href="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.categories.php?category={$ligne["category"]}&website={$ligne["website"]}')\" 
		style='font-weight:bold;text-decoration:underline;font-size:14px'>";
		
		
		$table=$table.
				"
				<tr class=$classtr>
					<td width=1%  style='font-size:14px' nowrap><strong>{$ligne["hits"]}</strong></td>
					<td width=1%  style='font-size:14px' nowrap><strong>$href{$ligne["website"]}</a></strong></td>
				</tr>
				";		
				
			}	
		$table=$table."</tbody></table>";
		
		
		
			

	$html="$title$image<p>&nbsp;</p><div style='height:250px;overflow:auto'>$table</div>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
		
	
	//SELECT SUM(size) as totalsize,count(zMD5) as hits ,familysite,client,uid FROM 20100503_hour GROUP BY familysite,client,uid ORDER BY totalsize,hits DESC 
	
	
}



function left(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_squid_builder();	
	
	
	
	$sql="SELECT DATE_FORMAT(zDate,'%Y-%m-%d') as tdate FROM tables_day ORDER BY zDate LIMIT 0,1";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$mindate=$ligne["tdate"];

	$sql="SELECT DATE_FORMAT(zDate,'%Y-%m-%d') as tdate FROM tables_day ORDER BY zDate DESC LIMIT 0,1";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$maxdate=date('Y-m-d');
	$cooky=__FILE__.__FUNCTION__;
	if(!is_numeric($_COOKIE[$cooky])){$_COOKIE[$cooky]=0;}
	
	$html="
	<input type='hidden' id='ShowTableBlockedWeek' value='{$_COOKIE[$cooky]}'>
	<table style='width:99%' class=form>
		<tbody>
		<tr>
			<td class=legend nowrap>{from_date}:</td>
			<td>". field_date('sdate',$_GET["day"],"font-size:16px;padding:3px;width:95px","mindate:$mindate;maxdate:$maxdate")."</td>	
			<td>". button("{go}","SquidFlowDaySizeQuery()")."</td>
		</tr>
		</tbody>
	</table>
	
	<div id='statistics-week-left-status'></div>
	<table style='width:99%' class=form>
		<tbody>
		<tr>
			<td width=1%>". imgtootltip("table-show-32.png","{show_table}","ShowTableBlockedSite(1)")."</td>
			<td><a href=\"javascript:blur();\" OnClick=\"javascript:ShowTableBlockedSite(1)\" style='text-decoration:unerline;font-size:14px'>{show_table}</td>	
		</tr>
		<tr>
			<td width=1%>". imgtootltip("graphs-32.png","{show_graphs}","ShowTableBlockedSite(0)")."</td>
			<td><a href=\"javascript:blur();\" OnClick=\"javascript:ShowTableBlockedSite(0)\" style='text-decoration:unerline;font-size:14px'>{show_graphs}</td>	
		</tr>		
		
		</tbody>
	</table>	
	
	
	
	
<script>
		function ShowTableBlockedSite(val){
			document.getElementById('ShowTableBlockedWeek').value=val;
			Set_Cookie('$cooky', val, '3600', '/', '', '');
			SquidFlowDaySizeQuery();
		}
	

		function SquidFlowDaySizeQuery(type){
			if(!type){
				if(document.getElementById('squid-stats-week-hide-type')){type=document.getElementById('squid-stats-week-hide-type').value;}
			}
			if(!type){type='size';}
			
			var sdate=document.getElementById('sdate').value;
			var ShowTable=document.getElementById('ShowTableBlockedWeek').value;
			LoadAjax('week-right-infos','$page?week-right-infos=yes&day='+sdate+'&type='+type+'&ShowTable='+ShowTable);
		}
		SquidFlowDaySizeQuery();
</script>
";
	echo $tpl->_ENGINE_parse_body($html);	
}


function page(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$html="<table style='width:100%'>
	<tbody>
	<tr>
		<td valign='top' width=1%>
			<div style='font-size:16px;margin-bottom:10px;border-bottom:1px solid black'>{blocked_websites}</div>
			<div id='week-left-menus'></div>
		</td>
		<td valign='top' width=99%>
			<div id='week-right-infos'></div>
		</td>
	</tr>
	</tbody>
	</table>
	
	<script>
		LoadAjax('week-left-menus','$page?week-left-menus=yes');
		
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
	
}

function right_showtable(){
	$page=CurrentPageName();
	$tpl=new templates();	
	if(!isset($_GET["day"])){$_GET["day"]=$q->HIER();}
	$webservers=$tpl->_ENGINE_parse_body("{webservers} - {$_GET["familysite-show"]}");
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$day=$tpl->_ENGINE_parse_body("{day}");
	$rule=$tpl->_ENGINE_parse_body("{rule}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$url=$tpl->_ENGINE_parse_body("{url}");
	$blocked_websites=$tpl->_ENGINE_parse_body("{blocked_websites}");
	$T=$tpl->_ENGINE_parse_body(strtolower(date('{l} d {F} Y',strtotime($_GET["day"]))));
	$html="
	<table class='flex2BlockedW' style='display: none' id='flex2BlockedW' style='width:99%'></table>
	
<script>
$(document).ready(function(){
$('#flex2BlockedW').flexigrid({
	url: '$page?view-table={$_GET["day"]}',
	dataType: 'json',
	colModel : [
		{display: '$day', name : 'day', width :35, sortable : true, align: 'left'},
		{display: '$hits', name : 'hits', width :40, sortable : true, align: 'left'},
		{display: '$webservers', name : 'website', width :194, sortable : true, align: 'left'},
		{display: '$member', name : 'client', width : 89, sortable : false, align: 'left'},
		{display: '$category', name : 'category', width : 76, sortable : true, align: 'left'},
		{display: '$rule', name : 'rulename', width : 52, sortable : true, align: 'left'},
		

		],
	searchitems : [
		{display: '$webservers', name : 'website'},
		{display: '$url', name : 'uri'},
		{display: '$category', name : 'category'}
		],
	sortname: 'day',
	sortorder: 'desc',
	usepager: true,
	title: '$blocked_websites $T ',
	useRp: true,
	rp: 50,
	showTableToggleBtn: true,
	width: 590,
	height: 400,
	singleSelect: true
	
	});   
});
function SearchLeftInfos() {
	LoadAjax('search-stats-forms','$page?site-infos={$_GET["familysite-show"]}');
}

";
	
	
	echo $html;	
	
	
}

function getDaysInWeek ($weekNumber, $year) {

  $time = strtotime($year . '0104 +' . ($weekNumber - 1)
                    . ' weeks');

  $mondayTime = strtotime('-' . (date('w', $time) - 1) . ' days',
                          $time);
 
  $dayTimes = array ();
  for ($i = 0; $i < 7; ++$i) {
    $dayTimes[] = strtotime('+' . $i . ' days', $mondayTime);
  }

  return $dayTimes;
}

function right(){
	if($_GET["ShowTable"]==1){right_showtable();exit;}
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();	
	if(!isset($_GET["day"])){$_GET["day"]=$q->HIER();}
	if(!isset($_GET["type"])){$_GET["type"]="size";}
	if($_GET["type"]==null){$_GET["type"]="size";}
	$type=$_GET["type"];
	$field_query="size";
	
	$time=strtotime("{$_GET["day"]} 00:00:00");
	$SourceTable=date("YW",$time)."_blocked_week";	
	
	$_GET["week"]=date("W",$time);
	$_GET["year"]=date("Y",$time);
	$tt=getDaysInWeek($_GET["week"],$_GET["year"]);
	foreach ($tt as $dayTime) {$f[]=date('{l} d {F}', $dayTime);}	
	$subtitle="{week}:&nbsp;{from}&nbsp;{$f[0]}&nbsp;{to}&nbsp;{$f[6]}";	
	$title="<div style='font-size:16px;width:100%;font-weight:bold'>{statistics}:&nbsp;$subtitle ({hits})</div>";
	if(!$q->TABLE_EXISTS($SourceTable)){
		echo $tpl->_ENGINE_parse_body("
		<input type='hidden' id='squid-stats-week-hide-type' value='{$_GET["type"]}'>
		$title
		<center style='margin:50px'><H2>{error_no_datas}</H2><i>$SourceTable no such table</center>");
		return;
	}
	
	
	if($type=="req"){$field_query="hits";}
	
	$sql="SELECT SUM(hits) as tcount,`day` as tdate FROM $SourceTable GROUP BY `day`  ORDER BY `day` ";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}	
	if(mysql_num_rows($results)==0){
		echo $tpl->_ENGINE_parse_body("$title<center style='margin:50px'>
			<H2>{error_no_datas}</H2>
		<i>&laquo;$SourceTable&raquo; (no datas)</i>
		</center>");
	}
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$xdata[]=$ligne["tdate"];
		$ydata[]=$ligne["tcount"];
	}	
	
	$targetedfile="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".day.$SourceTable.$type.png";
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
	if(!is_file($targetedfile)){
		writelogs("Fatal \"$targetedfile\" no such file!",__FUNCTION__,__FILE__,__LINE__);
		$targetedfile="/img/kas-graph-no-datas.png";
	}	
	
	
	$sql="SELECT SUM(hits) as tcount,category FROM $SourceTable GROUP BY category ORDER BY tcount DESC LIMIT 0,10";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}	
	if(mysql_num_rows($results)==0){
		echo $tpl->_ENGINE_parse_body("$title<center style='margin:50px'><H2>{error_no_datas}</H2>
		<i>$SourceTable (no datas)</i>
		<br><code>sql:$sql</code>
		</center>");
	}	
	
	$table="
	<input type='hidden' id='squid-stats-week-hide-type' value='{$_GET["type"]}'>
	<center>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:350px'>
<thead class='thead'>
	<tr>
	<th width=1%>{hits}</th>
	<th>{category}</th>
	</tr>
</thead>
<tbody>";
	$xdata=array();
	$ydata=array();
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(trim($ligne["category"])==null){continue;}
		$ydata[]=$ligne["category"];
		$xdata[]=$ligne["tcount"];
		
	if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
$table=$table.
		"
		<tr class=$classtr>
			
			<td width=1%  style='font-size:14px' nowrap><strong>{$ligne["tcount"]}</strong></td>
			<td  style='font-size:14px' nowrap width=99%>
				<strong><a href=\"javascript:blur();\" 
				OnClick=\"javascript:Loadjs('$page?today-zoom=yes&category={$ligne["category"]}&day={$_GET["day"]}')\" 
				style='font-size:14px;font-weight:bold;text-decoration:underline'>{$ligne["category"]}</a></strong></td>
		</tr>
		";		
		
	}	
$table=$table."</tbody></table>";
	$targetedfile2="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".day.top.10.websites.$SourceTable.$type.png";
	$gp=new artica_graphs($targetedfile2);	
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;	
	$gp->width=550;
	$gp->height=550;
	$gp->ViewValues=true;
	$gp->x_title=$tpl->_ENGINE_parse_body("{top_categories}");
	$gp->pie();		
	
	$html="
	<input type='hidden' id='squid-stats-week-hide-type' value='$type'>
	$title
	<center style='margin:10px'><img src='$targetedfile'></center>
	<center style='margin:10px'><img src='$targetedfile2'></center>
	$table
	<script>
		LoadAjax('statistics-week-left-status','squid.traffic.statistics.week.php?left-status=yes&day={$_GET["day"]}');
	</script>
	
";
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
}

function view_table(){
	$q=new mysql_squid_builder();
	$Mypage=CurrentPageName();
	$tpl=new templates();	
	
	if(!isset($_GET["view-table"])){$_GET["view-table"]=$q->HIER();}	
	$search='%';
	$time=strtotime("{$_GET["day"]} 00:00:00");
	$table=date("YW",$time)."_blocked_week";	
	$page=1;
	$ORDER="ORDER BY zDate DESC";
	
	
	if($q->COUNT_ROWS($table)==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="WHERE (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(ID) as TCOUNT FROM `$table` $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$total = $q->COUNT_ROWS($table);
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT *  FROM `$table` $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){$data['rows'][] = array('id' => "ERROR",'cell' => array($q->mysql_error, $sql, '', '','',''));		}
	while ($ligne = mysql_fetch_assoc($results)) {
		
		$linkZoom="<a href=\"javascript:Loadjs('squid.blocked.statistics.days.php?Zoom-js={$ligne['zMD5']}&ID={$ligne['zMD5']}&table=$table&key=zMD5');\" style='font-size:12px;text-decoration:underline'>";
		
		$data['rows'][] = array(
		'id' => $ligne['zMD5'],
		'cell' => array($ligne['day'],$ligne['hits'], $linkZoom.$ligne['website']."</a>", $ligne["client"], $ligne['category'],$ligne['rulename'],$ligne['uri'])
		);
	}
echo json_encode($data);	
	
}


