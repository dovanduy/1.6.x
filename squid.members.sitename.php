<?php

	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die();}	
	if(!$users->CORP_LICENSE){
		$tpl=new templates();
		$onlycorpavailable=$tpl->javascript_parse_text("{onlycorpavailable}");
		echo "alert('$onlycorpavailable')";
		die();
	}
	//
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["history"])){history_content();exit;}
	if(isset($_GET["days"])){days_popup();exit;}
	if(isset($_GET["zoom-day"])){zoom_day();exit;}
	if(isset($_GET["sitenames"])){sitenames();exit;}
	if(isset($_GET["sitenames-items"])){sitenames_items();exit;}
	
	
	
	
	
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$field=$_GET["field"];
	$value=$_GET["value"];
	$title="{member}::$field - $value - {$_GET["familysite"]}";
	$q=new mysql_squid_builder();
	$month_table="quotamonth_".date("Ym");
	$month_text=date("{F}");
	if($q->COUNT_ROWS($month_table)==0){
		$month_text=date("{F}",strtotime('first day of previous month'));
		$month_table="quotamonth_".date("Ym",strtotime('first day of previous month'));
	}
	
	$title=$tpl->_ENGINE_parse_body("$month_text - $title");
	
	
	
	$html="YahooWin2('750','$page?tabs=yes&field=$field&value=$value$tablejs&familysite={$_GET["familysite"]}','$title$title_add')";
	echo $html;
}
function tabs(){
$page=CurrentPageName();
	$tpl=new templates();
	$array["history"]='{history}';
	
	
	
	
	$field=$_GET["field"];
	$value=$_GET["value"];	
	if(isset($_GET["table"])){$tablejs="&table={$_GET["table"]}";}
	while (list ($num, $ligne) = each ($array) ){
		
		$html[]= "<li><a href=\"$page?$num=yes&field=$field&value=$value$tablejs&familysite={$_GET["familysite"]}\"><span>$ligne</span></a></li>\n";
	}
	
	
	echo build_artica_tabs($html, "squid_members_stats_zoom-family");
	
}
function history_content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();		
	$xdata=array();
	$ydata=array();	
	$field=$_GET["field"];
	$value=$_GET["value"];
	$familysite=$_GET["familysite"];
	
	
	$month_table="quotamonth_".date("Ym");
	$month_text=date("{F}");
	if($q->COUNT_ROWS($month_table)==0){
		$month_text=date("{F}",strtotime('first day of previous month'));
		$month_table="quotamonth_".date("Ym",strtotime('first day of previous month'));
	}
	
		$sql="SELECT `day`,`familysite`,`$field`,SUM(size) as QuerySize FROM 
		`$month_table`  GROUP BY `day`,`familysite` ,`$field`
		HAVING `$field`='$value' AND familysite='$familysite' ORDER BY `day`";		
		
		
	
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html();}

	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=round(($ligne["QuerySize"]/1024)/1000);
		$day=$ligne["day"];
		$xdata[]=$day;
		
		$ydata[]=$size;
	
		
	}	
	
	$targetedfile="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".". md5($sql).".png";
	$targetedfile2="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".". md5($sql).".2.png";
	$gp=new artica_graphs();
	
	$gp->width=650;
	$gp->height=350;
	$gp->filename="$targetedfile";
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;
	$gp->y_title=$tpl->_ENGINE_parse_body("{size}");;
	$gp->x_title=$tpl->_ENGINE_parse_body("{days}");
	$gp->title=null;
	$gp->margin0=true;
	$gp->Fillcolor="blue@0.9";
	$gp->color="146497";
	$gp->line_green();
	
	
	
	if(!is_file($targetedfile)){
		writelogs("Fatal \"$targetedfile\" no such file!",__FUNCTION__,__FILE__,__LINE__);
	
	}else{
		$html=$html."
		<div style='font-size:18px;margin:8px'>&laquo;$value&raquo;&nbsp;|&nbsp;$familysite&nbsp;|&nbsp;$month_text</div>
		<center>
			<div style='width:99%' class=form>
				
				<img src='$targetedfile'>
			</div>
	
		</center>
		
		";
		
	}	
		
	echo $tpl->_ENGINE_parse_body($html);
}

function days_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_squid_builder();
	$t=time();
	$table=$_GET["table"];
	$field=$_GET["field"];
	$value=$_GET["value"];
	$familysite=$_GET["familysite"];	
	
	
	
$t=time();	
$html="
<div id='$t-content'></div>


<script>
	function ChangeIntervalCheck$t(e){
		if(checkEnter(e)){ChangeInterval$t();}
	}

function ChangeInterval$t(){
	var table='{$_GET["table"]}';
	LoadAjax('$t-content','$page?zoom-day=yes&field=$field&value=$value&familysite={$_GET["familysite"]}&table={$_GET["table"]}&daytime=');
	}
	ChangeInterval$t();
</script>
"	;
	
echo $tpl->_ENGINE_parse_body($html);
}


function zoom_day(){
	$page=CurrentPageName();
	$tpl=new templates();
	$field=$_GET["field"];
	$value=$_GET["value"];	
	$familysite=$_GET["familysite"];
	$daytime=$_GET["daytime"];
	$table_name=date("Ymd",$daytime)."_hour";
	$daytitle=date("{l} d {F}",$daytime);
	$q=new mysql_squid_builder();
	
	$month_table="quotamonth_".date("Ym");
	$month_text=date("{F}");
	if($q->COUNT_ROWS($month_table)==0){
		$month_text=date("{F}",strtotime('first day of previous month'));
		$month_table="quotamonth_".date("Ym",strtotime('first day of previous month'));
	}
	
	
	$sql="SELECT `hour` as thour,SUM(size) as QuerySize,SUM(hits) as hits FROM 
	`$table_name`  WHERE `$field`='$value' AND familysite='$familysite' GROUP BY thour ORDER BY thour";
		
	
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo "<H3>Warning<hr>$sql<hr>$q->mysql_error</H3>";
	}
	
	if(mysql_num_rows($results)==0){
		$reqests="{search} {requests} {from} $value {to} $familysite $title_add";
		echo FATAL_ERROR_SHOW_128("{this_request_contains_no_data}<hr>$reqests");
		return;
		
	}
	
	if(mysql_num_rows($results)<2){
		
		if(mysql_num_rows($results)==1){
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$size=FormatBytes($ligne["QuerySize"]/1024);
			$day=$ligne["thour"];
			$timstr=strtotime(date("Y-m-d",$daytime)." $day:00:00");
			$html=$html."<div style='width:99%' style='font-size:16px;' class=form>
				$field:$value&nbsp;&raquo; {size}:$size, {$ligne["hits"]} {hits} ". date('{l} d {F} H:00', $timstr)."
			
			</div>";
			}
				echo $tpl->_ENGINE_parse_body($html);
				return;
			
		}
	}	
	
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=round(($ligne["QuerySize"]/1024)/1000);
		$day=$ligne["thour"];
		$xdata[]=$day;
		$xdata2[]=$day;
		$ydata[]=$size;
		$ydata2[]=$ligne["hits"];
		
	}	
	
	$targetedfile="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".". md5($sql).".png";
	$targetedfile2="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".". md5($sql).".2.png";
	$gp=new artica_graphs();
	
	$gp->width=650;
	$gp->height=350;
	$gp->filename="$targetedfile";
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;
	$gp->y_title=$tpl->_ENGINE_parse_body("{size}");;
	$gp->x_title=$tpl->_ENGINE_parse_body("{hours}");
	$gp->title=null;
	$gp->margin0=true;
	$gp->Fillcolor="blue@0.9";
	$gp->color="146497";
	$gp->line_green();
	
	$gp2=new artica_graphs();
	$gp2->width=650;
	$gp2->height=350;
	$gp2->filename="$targetedfile2";
	$gp2->xdata=$xdata2;
	$gp2->ydata=$ydata2;
	$gp2->y_title=$tpl->_ENGINE_parse_body("{hits}");;
	$gp2->x_title=$tpl->_ENGINE_parse_body("{hours}");
	$gp2->title=null;
	$gp2->margin0=true;
	$gp2->Fillcolor="blue@0.9";
	$gp2->color="146497";	
	$gp2->line_green();
	
	if(!is_file($targetedfile)){
		writelogs("Fatal \"$targetedfile\" no such file!",__FUNCTION__,__FILE__,__LINE__);
	
	}else{
		$html=$html."
		<center>
			<div style='width:99%' class=form>
				<div style='font-size:18px;margin:8px'>&laquo;$value&raquo;$familysite&nbsp;{downloaded_size_per_hour} (MB)</div>
				<img src='$targetedfile'>
			</div>
			
			<div style='width:99%' class=form>
				<div style='font-size:18px;margin:8px'>&laquo;$value&raquo;$familysite&nbsp;{requests_per_hour}</div>
				<img src='$targetedfile2'>
			</div>			
		</center>
		
		";
		
	}	
		
	echo $tpl->_ENGINE_parse_body($html);	
}

function sitenames(){
	$page=CurrentPageName();
	$tpl=new templates();		
	$field=$_GET["field"];
	$value=$_GET["value"];	

	$MyTableMonth=date("Ym")."_day";
	$MyMonthText=date("{F}");
	$q=new mysql_squid_builder();
	$tableQuery=$_GET["table"];
	if(isset($_GET["table"])){
		$MyTableMonth=$_GET["table"];
	}
	
	
	if(!$q->TABLE_EXISTS($MyTableMonth)){
		echo FATAL_ERROR_SHOW_128("&laquo;$MyTableMonth&raquo; {table_does_not_exists}");
		return;
	}	
	
		if(preg_match("#_week#", $_GET["table"])){
			$title_add="&raquo;".$tpl->_ENGINE_parse_body($q->WEEK_TITLE_FROM_TABLENAME($_GET["table"]));
		}
			
		if(preg_match("#_day$#", $_GET["table"])){
			$title_add="&raquo;".$tpl->_ENGINE_parse_body($q->MONTH_TITLE_FROM_TABLENAME($_GET["table"]));
		}
			
		if(preg_match("#_hour$#", $_GET["table"])){
			$title_add="&raquo;".$tpl->_ENGINE_parse_body($q->DAY_TITLE_FROM_TABLENAME($_GET["table"]));
		}	
	
	
	if($field=="ipaddr"){$field="client";}
	$title=$tpl->_ENGINE_parse_body("{where} ? &raquo;&raquo;{{$field}}::$value $title_add");
	
	$t=time();	
	$sitename=$tpl->_ENGINE_parse_body("{sitename}");	
	$category=$tpl->_ENGINE_parse_body("{category}");	
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$sitename=$tpl->_ENGINE_parse_body("{sitename}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$members=$tpl->_ENGINE_parse_body("{members}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$mac=$tpl->_ENGINE_parse_body("{MAC}");
	$week=$tpl->_ENGINE_parse_body("{week}");
	$month=$tpl->_ENGINE_parse_body("{month}");
	$TB_WIDTH=550;
	$t=time();
	
	$buttons="
	buttons : [
	{name: '<b>$day</b>', bclass: 'Calendar', onpress : ChangeDay$t},
	{name: '<b>$week</b>', bclass: 'Calendar', onpress : ChangeWeek$t},
	{name: '<b>$month</b>', bclass: 'Calendar', onpress : ChangeMonth$t},
	
		],";

	$buttons=null;
	
	$html="
	<table class='$t' style='display: none' id='$t' style='width:99%'></table>

<script>

$(document).ready(function(){
$('#$t').flexigrid({
	url: '$page?sitenames-items=yes&field=$field&value=$value&table={$_GET["table"]}&familysite={$_GET["familysite"]}',
	dataType: 'json',
	colModel : [
		{display: '$sitename', name : 'sitename', width : 181, sortable : true, align: 'left'},
		{display: '$category', name : 'category', width : 245, sortable : true, align: 'left'},
		{display: '$size', name : 'size', width : 109, sortable : true, align: 'left'},
		{display: '$hits', name : 'hits', width : 94, sortable : true, align: 'left'},

		
		
	],$buttons
	searchitems : [
		{display: '$sitename', name : 'sitename'},
		{display: '$category', name : 'category'},
		],
	sortname: 'size',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: 700,
	height: 450,
	singleSelect: true
	
	});
});
</script>";
	
echo $tpl->_ENGINE_parse_body($html);		
}

function sitenames_items(){
	
	$q=new mysql_squid_builder();	
	$tableQuery=$_GET["table"];
	$tpl=new templates();
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	$MyTableMonth=date("Ym")."_day";
	$MyMonthText=date("{F}");
	if($tableQuery==null){$tableQuery=$MyTableMonth;}
	$tablejs="&table=$tableQuery";
	$table="(SELECT sitename,{$_GET["field"]},SUM(size) as size,SUM(hits) as hits,category FROM $tableQuery
	WHERE {$_GET["field"]}='{$_GET["value"]}' AND familysite='{$_GET["familysite"]}' GROUP BY sitename,{$_GET["field"]},category ) as t";
	
	
	if($q->COUNT_ROWS($tableQuery)==0){json_error_show("Table empty");}
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	$searchstring=string_to_flexquery();
		
	if($searchstring<>null){	
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		if(!$q->ok){json_error_show("$q->mysql_error");}
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show("$q->mysql_error");}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	$results = $q->QUERY_SQL($sql);	
	if(!$q->ok){json_error_show("$q->mysql_error");}
		
	
	if(mysql_num_rows($results)==0){
	json_error_show("No data, $sql");
	}
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$md5=md5(serialize($line));
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
		$ligne["hits"]=numberFormat($ligne["hits"],0,""," ");
		
		$jsuid="
		<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('squid.members.sitename.php?field={$_GET["field"]}&value={$_GET["value"]}$tablejs&familysite={$ligne["familysite"]}')\"
		style='font-size:16px;text-decoration:underline'>";
		
	$jsuid=null;

	$data['rows'][] = array(
		'id' => $md5,
		'cell' => array(
			"<span style='font-size:16px'>$jsuid{$ligne["sitename"]}</a></span>",
			"<span style='font-size:16px'>{$ligne["category"]}</a></span>",
			"<span style='font-size:16px'>{$ligne["size"]}</span>",
			"<span style='font-size:16px'>{$ligne["hits"]}</span>",
	
	 	 	
			)
		);
	}
	
	
echo json_encode($data);	
}
