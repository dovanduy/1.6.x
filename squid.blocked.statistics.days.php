<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
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
	
	if(isset($_GET["day-right-infos"])){right();die();}
	if(isset($_GET["days-left-menus"])){left();die();}
	if(isset($_GET["today-zoom"])){today_zoom_js();exit;}
	if(isset($_GET["today-zoom-popup"])){today_zoom_popup();exit;}
	if(isset($_GET["today-zoom-popup-list"])){today_zoom_popup_list();exit;}
	if(isset($_GET["statistics-days-left-status"])){left_status();exit;}
	if(isset($_GET["view-table"])){view_table();exit;}
	if(isset($_GET["Zoom-js"])){ZOOM_JS();exit;}
	if(isset($_GET["block-zoom-popup"])){ZOOM_POPUP();exit;}
	
page();

function ZOOM_JS(){
	$ID=$_GET["Zoom-js"];
	$q=new mysql_squid_builder();
	$key="ID";
	if($_GET["key"]<>null){$key="{$_GET["key"]}";}
	$sql="SELECT * FROM {$_GET["table"]} WHERE `$key`='$ID'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$page=CurrentPageName();
	$tpl=new templates();	
	if(!isset($ligne["zDate"])){if(isset($ligne["day"])){$ligne["zDate"]=$ligne["day"];}}
	
	$title="$ID: {$ligne["zDate"]} - {$ligne["website"]}";		
	$html="YahooWin2(650,'$page?block-zoom-popup=yes&ID=$ID&table={$_GET["table"]}&key={$_GET["key"]}','$title')";
	echo $html;	
	
}

function ZOOM_POPUP(){
	$ID=$_GET["ID"];
	$q=new mysql_squid_builder();
	$tpl=new templates();	
	$key="ID";
	if($_GET["key"]<>null){$key=$_GET["key"];}
	$sql="SELECT * FROM {$_GET["table"]} WHERE `$key`='$ID'";	
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(isset($ligne["website"])){$website=$ligne["website"];}
	$html="
	<div id='getCategoriesof'></div>
	
	
	<table style='width:99%' class=form>
	<tbody>
	
	";
	
	if(!isset($ligne["uri"])){if(isset($ligne["website"])){$ligne["uri"]="http://{$ligne["website"]}";}}
		if(isset($ligne["why"])){
			if($ligne["why"]<>null){
				$ligne["why"]="<strong style='color:#BF2418'>{$ligne["why"]}</strong>";
			}
		}
			
	if(isset($ligne["website"])){
		$ligne["website"]=texttooltip($ligne["website"],
		$ligne["uri"],"s_PopUpScroll('{$ligne["uri"]}',800,600,'{$ligne["blocktype"]} {$ligne["category"]}')",null,0,"text-decoration:underline");
		unset($ligne["uri"]);
	}	
	
	if(isset($ligne["category"])){
		if(substr($ligne["category"], 0,3)=="tls"){
			$ligne["category"]="Toulouse University database:&nbsp;<strong>".str_replace("tls", "", $ligne["category"])."</strong>";
		}
		
	}	
	
	while (list ($index, $line) = each ($ligne)){
		if($GLOBALS["VERBOSE"]){echo "$index = $line\n";}
		if(is_numeric($index)){continue;}
		if(trim($line)==null){continue;}

		
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
	$SourceTable=date('Ymd',strtotime($_GET["day"]))."_blocked";
	
	$title="<div style='font-size:16px;width:100%;font-weight:bold'>{$_GET["category"]}:&nbsp;". strtolower(date('{l} d {F} Y',strtotime($_GET["day"])))."</div>";
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
	
	$sql="SELECT COUNT(ID) as hits,HOUR(zDate) as tdate,category FROM $SourceTable GROUP BY tdate,category HAVING category='{$_GET["category"]}' ORDER BY tdate";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}	
	if(mysql_num_rows($results)==0){echo $tpl->_ENGINE_parse_body("$title<center style='margin:50px'><H2>{error_no_datas}</H2>$sql</center>");return;}
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$xdata[]=$ligne["tdate"];
		$ydata[]=$ligne["hits"];
	}	
	
	$targetedfile="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".day.$hour_table.{$_GET["familysite"]}.$type.png";
	$gp=new artica_graphs();
	$gp->width=687;
	$gp->height=220;
	$gp->filename="$targetedfile";
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;
	$gp->y_title=null;
	$gp->x_title=$tpl->_ENGINE_parse_body("{hours}");
	$gp->title=null;
	$gp->margin0=true;
	$gp->Fillcolor="blue@0.9";
	$gp->color="146497";
	$gp->line_green();
	$t=time();
	$image="<center style='margin-top:10px'><img src='$targetedfile'></center>";
	if(!is_file($targetedfile)){writelogs("Fatal \"$targetedfile\" no such file!",__FUNCTION__,__FILE__,__LINE__);$image=null;}
	
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$website=$tpl->_ENGINE_parse_body("{website}");
	
	$html="$title$image
<table class='$t' style='display: none' id='$t' style='width:100%'></table>
<script>
var xsite='';
$(document).ready(function(){
$('#$t').flexigrid({
	url: '$page?today-zoom-popup-list=yes=yes&sourcetable=$SourceTable&category={$_GET["category"]}',
	dataType: 'json',
	colModel : [
		{display: '$hits', name : 'hits', width : 54, sortable : true, align: 'left'},	
		{display: '$website', name : 'website', width : 587, sortable : true, align: 'left'},
		],
	$buttons
	searchitems : [
		{display: '$website', name : 'website'},
		],
	sortname: 'hits',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 687,
	height: 250,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});	
</script>	
";
	
echo $tpl->_ENGINE_parse_body($html);

}

function today_zoom_popup_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$table=$_GET["sourcetable"];
	$country_select=null;
	$search='%';
	$page=1;
	$total=0;
	
	if($q->COUNT_ROWS($table)==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".trim($_POST["query"])."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(ID) as hits,website,category FROM $table GROUP BY website,category HAVING category='{$_GET["category"]}' $searchstring";
		$results=$q->QUERY_SQL($sql);
		$total = mysql_num_rows($results);
		writelogs("$sql = `$total`",__FUNCTION__,__FILE__,__LINE__);
	}else{
		$sql="SELECT COUNT(category) FROM $table GROUP BY category HAVING category='{$_GET["category"]}'";
		$results=$q->QUERY_SQL($sql);
		$total = mysql_num_rows($results);		
		writelogs("$sql = `$total`",__FUNCTION__,__FILE__,__LINE__);
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT COUNT(ID) as hits,website,category FROM $table GROUP BY website,category HAVING category='{$_GET["category"]}' $searchstring $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){
		$q->mysql_error=wordwrap($q->mysql_error,80,"<br>");
		$sql=wordwrap($sql,80,"<br>");
		$data['rows'][] = array('id' => $ligne[time()+1],'cell' => array($q->mysql_error,"", "",""));
		$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));
		echo json_encode($data);
		return;
	}	
	
	if(mysql_num_rows($results)==0){
		$sql=wordwrap($sql,80,"<br>");
		$data['rows'][] = array('id' => time(),'cell' => array($sql,"", "",""));
	}
	
	writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
	$button=null;
		
		
		
	while ($ligne = mysql_fetch_assoc($results)) {
		$jscat="javascript:Loadjs('squid.website-zoom.php?js=yes&sitename={$ligne['website']}')";
		$uri="<a href=\"javascript:blur();\" 
		OnClick=\"$jscat\" 
		style='font-size:16px;font-weight:bold;text-decoration:underline'>";
		
		
		$data['rows'][] = array(
		'id' => md5($ligne['familysite']),
		'cell' => array(
		"<span style='font-size:16px;font-weight:bold'>{$ligne['hits']}</span>",
		"$uri{$ligne["website"]}</a></span>"
		)
		);
	}
		
echo json_encode($data);
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
	<input type='hidden' id='ShowTableBlockedDay' value='{$_COOKIE[$cooky]}'>
	<table style='width:99%' class=form>
		<tbody>
		<tr>
			<td class=legend nowrap>{from_date}:</td>
			<td>". field_date('sdate',$_GET["day"],"font-size:16px;padding:3px;width:95px","mindate:$mindate;maxdate:$maxdate")."</td>	
			<td>". button("{go}","SquidFlowDaySizeQuery()")."</td>
		</tr>
		</tbody>
	</table>
	
	<div id='statistics-days-left-status'></div>
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
			document.getElementById('ShowTableBlockedDay').value=val;
			Set_Cookie('$cooky', val, '3600', '/', '', '');
			SquidFlowDaySizeQuery();
		}
	

		function SquidFlowDaySizeQuery(type){
			if(!type){
				if(document.getElementById('squid-stats-day-hide-type')){type=document.getElementById('squid-stats-day-hide-type').value;}
			}
			if(!type){type='size';}
			
			var sdate=document.getElementById('sdate').value;
			var ShowTable=document.getElementById('ShowTableBlockedDay').value;
			LoadAjax('days-right-infos','$page?day-right-infos=yes&day='+sdate+'&type='+type+'&ShowTable='+ShowTable);
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
			<div id='days-left-menus'></div>
		</td>
		<td valign='top' width=99%>
			<div id='days-right-infos'></div>
		</td>
	</tr>
	</tbody>
	</table>
	
	<script>
		LoadAjax('days-left-menus','$page?days-left-menus=yes');
		
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
	$date=$tpl->_ENGINE_parse_body("{date}");
	$rule=$tpl->_ENGINE_parse_body("{rule}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$url=$tpl->_ENGINE_parse_body("{url}");
	$blocked_websites=$tpl->_ENGINE_parse_body("{blocked_websites}");
	$T=$tpl->_ENGINE_parse_body(strtolower(date('{l} d {F} Y',strtotime($_GET["day"]))));
	$html="
	<table class='flex2Blocked' style='display: none' id='flex2Blocked' style='width:99%'></table>
	
<script>
$(document).ready(function(){
$('#flex2Blocked').flexigrid({
	url: '$page?view-table={$_GET["day"]}',
	dataType: 'json',
	colModel : [
		{display: '$date', name : 'zDate', width :114, sortable : true, align: 'left'},
		{display: '$webservers', name : 'website', width :194, sortable : true, align: 'left'},
		{display: '$member', name : 'client', width : 89, sortable : false, align: 'left'},
		{display: '$category', name : 'category', width : 76, sortable : true, align: 'left'},
		{display: '$rule', name : 'rulename', width : 52, sortable : true, align: 'left'},
		{display: '$url', name : 'uri', width : 200, sortable : true, align: 'left'}

		],
	searchitems : [
		{display: '$webservers', name : 'website'},
		{display: '$url', name : 'uri'},
		{display: '$category', name : 'category'}
		],
	sortname: 'zDate',
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
	

	
	$SourceTable=date('Ymd',strtotime($_GET["day"]))."_blocked";
	$title="<div style='font-size:16px;width:100%;font-weight:bold'>{statistics}:&nbsp;". strtolower(date('{l} d {F} Y',strtotime($_GET["day"])))." ({hits})</div>";
	if(!$q->TABLE_EXISTS($SourceTable)){echo $tpl->_ENGINE_parse_body("<input type='hidden' id='squid-stats-day-hide-type' value='{$_GET["type"]}'>$title<center style='margin:50px'><H2>{error_no_datas}</H2></center>");return;}
	
	
	if($type=="req"){
		$field_query="hits";
	}
	
	$sql="SELECT COUNT(ID) as tcount,HOUR(zDate) as `hour` FROM $SourceTable GROUP BY HOUR(zDate)  ORDER BY HOUR(zDate) ";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}	
	if(mysql_num_rows($results)==0){
		echo $tpl->_ENGINE_parse_body("$title<center style='margin:50px'><H2>{error_no_datas}</H2></center>");
		return;
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
	$gp->x_title=$tpl->_ENGINE_parse_body("{hours}");
	$gp->title=null;
	$gp->margin0=true;
	$gp->Fillcolor="blue@0.9";
	$gp->color="146497";

	$gp->line_green();
	if(!is_file($targetedfile)){
		writelogs("Fatal \"$targetedfile\" no such file!",__FUNCTION__,__FILE__,__LINE__);
		$targetedfile="/img/kas-graph-no-datas.png";
	}	
	
	
	$sql="SELECT COUNT(ID) as tcount,category FROM $SourceTable GROUP BY category ORDER BY tcount DESC LIMIT 0,10";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}	
	if(mysql_num_rows($results)==0){echo $tpl->_ENGINE_parse_body("$title<center style='margin:50px'><H2>{error_no_datas}</H2></center>");return;}	
	
	$table="
	<input type='hidden' id='squid-stats-day-hide-type' value='{$_GET["type"]}'>
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
	<input type='hidden' id='squid-stats-day-hide-type' value='$type'>
	$title
	<center style='margin:10px'><img src='$targetedfile'></center>
	<center style='margin:10px'><img src='$targetedfile2'></center>
	$table
	<script>
		LoadAjax('statistics-days-left-status','squid.traffic.statistics.days.php?statistics-days-left-status=yes&day={$_GET["day"]}');
	</script>
	
";
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
}








function general_status_cache_graphs(){
	$page=CurrentPageName();
	$tpl=new templates();		
	
	
	
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
	
	
	$sql="SELECT size_cached as totalsize,DATE_FORMAT(zDate,'%d') as tdate FROM tables_day WHERE $filter ORDER BY zDate";
	
	$results=$q->QUERY_SQL($sql);

	if(!$q->ok){echo "<H2>$q->mysql_error</H2>";}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$xdata[]=$ligne["tdate"];
		$ydata[]=round(($ligne["totalsize"]/1024)/1000);
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
	echo $tpl->_ENGINE_parse_body("<div ><h3>{cache} (MB) /{days} - $selected_date</h3>
	<center>
	<img src='$targetedfile'>
	</center>
	</div>");
	
}

function view_table(){
	$q=new mysql_squid_builder();
	$Mypage=CurrentPageName();
	$tpl=new templates();	
	
	if(!isset($_GET["view-table"])){$_GET["view-table"]=$q->HIER();}	
	$search='%';
	$table=date('Ymd',strtotime($_GET["view-table"]))."_blocked";
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
	while ($ligne = mysql_fetch_assoc($results)) {
		
		$linkZoom="<a href=\"javascript:Loadjs('$Mypage?Zoom-js={$ligne['ID']}&table=$table');\" style='font-size:12px;text-decoration:underline'>";
		
		$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => array($ligne['zDate'], $linkZoom.$ligne['website']."</a>", $ligne["client"], $ligne['category'],$ligne['rulename'],$ligne['uri'])
		);
	}
echo json_encode($data);	
	
}


