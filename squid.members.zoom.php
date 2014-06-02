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
	
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["history"])){history_popup();exit;}
	if(isset($_GET["history-content"])){history_content();exit;}
	
	if(isset($_GET["where"])){where_popup();exit;}
	if(isset($_GET["where-content"])){where_search();exit;}
	if(isset($_GET["alsoknown"])){alsoknown();exit;}
	
	if(isset($_GET["what"])){what_popup();exit;}
	if(isset($_GET["blocked"])){blocked_popup();exit;}
	if(isset($_GET["blocked-search"])){blocked_search();exit;}
	
	
	
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$field=$_GET["field"];
	$value=$_GET["value"];
	$title="{member}::$field - $value";
	$title=$tpl->_ENGINE_parse_body($title);
	if(isset($_GET["table"])){
		$q=new mysql_squid_builder();
		$tablejs="&table={$_GET["table"]}";
		if(preg_match("#_week#", $_GET["table"])){
			$title_add="&raquo;".$tpl->_ENGINE_parse_body($q->WEEK_TITLE_FROM_TABLENAME($_GET["table"]));
		}
			
		if(preg_match("#_day$#", $_GET["table"])){
			$title_add="&raquo;".$tpl->_ENGINE_parse_body($q->MONTH_TITLE_FROM_TABLENAME($_GET["table"]));
		}
			
		if(preg_match("#_hour$#", $_GET["table"])){
			$title_add="&raquo;".$tpl->_ENGINE_parse_body($q->DAY_TITLE_FROM_TABLENAME($_GET["table"]));
		}			
			
	}
	
	
	$html="YahooWin('850','$page?tabs=yes&field=$field&value=$value$tablejs','$title$title_add')";
	echo $html;
}

function tabs(){
$page=CurrentPageName();
	$tpl=new templates();
	$array["status"]='{status}';
	$array["history"]='{history}';
	$array["where"]='{where} ?';
	$array["what"]='{what} ?';
	
	$field=$_GET["field"];
	$value=urlencode($_GET["value"]);	
	if(isset($_GET["table"])){
		$array["blocked"]='{blocked} ?';
		$tablejs="&table={$_GET["table"]}";}
	while (list ($num, $ligne) = each ($array) ){
		
		$html[]= "<li><a href=\"$page?$num=yes&field=$field&value=$value$tablejs\"><span>$ligne</span></a></li>\n";
	}
	
	
	echo $tpl->_ENGINE_parse_body( "
	<div id=squid_members_stats_zoom style='width:100%;font-size:14px'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#squid_members_stats_zoom').tabs();
			
			
			});
		</script>");		
}


function status(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_squid_builder();
	$t=time();
	$field=$_GET["field"];
	$value=$_GET["value"];
	
	$month_table="quotamonth_".date("Ym");
	if($q->COUNT_ROWS($month_table)==0){
		$month_table="quotamonth_".date("Ym",strtotime('first day of previous month'));
	}

	
	$sql="SELECT SUM(size) as QuerySize,`{$_GET["field"]}`
	FROM $month_table GROUP BY `{$_GET["field"]}` HAVING `{$_GET["field"]}`='{$_GET["value"]}'";
	
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo "<center style='font-size:14px;'>$q->mysql_error<hr>$sql</center>";}
	$USER_SIZE=$ligne["QuerySize"];
	$usersize=FormatBytes($USER_SIZE/1024);
	
	
	$sql="SELECT SUM(size) as size FROM $month_table";
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo "<center style='font-size:14px;'>$q->mysql_error<hr>$sql</center>";}
	$SUM_SIZE=$ligne["size"];	
	$SUM_SIZEhuman=FormatBytes($SUM_SIZE/1024);
	
	
	
		
	
	$USER_POURC_SIZE=($USER_SIZE/$SUM_SIZE)*100;
	$USER_POURC_SIZE=round($USER_POURC_SIZE,2);
	
	
	$html="
	<table style='width:100%'>
	<tr>
		<td valign='top' width=1%>
			<img src='img/128-hand-user.png'>
		</td>
		<td valign='top' width=99%'>
			<table style='width:99%' class=form>
			<tr>
				<td valign='top' class=legend style='font-size:16px'>{downloaded}:</td>
				<td style='font-size:16px;font-weight:bold'>$usersize/$SUM_SIZEhuman <strong>$USER_POURC_SIZE%</strong></td>
			</tr>
			</table>
			<div id='alsoknown-$t'></div>
	</td>
	</tr>
	</table>
	
	<script>
		LoadAjax('alsoknown-$t','$page?alsoknown=yes&field={$_GET["field"]}&value=".urlencode($_GET["value"])."$tablejs');
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function history_popup(){
	$page=CurrentPageName();
	$tpl=new templates();		
	$field=$_GET["field"];
	$value=urlencode($_GET["value"]);	
	$month=date("{F}");
	if(isset($_GET["table"])){$tablejs="&table={$_GET["table"]}";}
	$q=new mysql_squid_builder();
	$month_table="quotamonth_".date("Ym");
	if($q->COUNT_ROWS($month_table)==0){
		$month_table="quotamonth_".date("Ym",strtotime('first day of previous month'));
		$month=date("{F}",strtotime('first day of previous month'));
		
	}
	
	$title=$tpl->_ENGINE_parse_body($month);	
$t=time();	
$html="
<div style='font-size:22px'>$title</div>
<div id='$t-content'></div>


<script>
	function ChangeIntervalCheck$t(e){
		if(checkEnter(e)){ChangeInterval$t();}
	}

function ChangeInterval$t(){
	var table='{$_GET["table"]}';
	LoadAjax('$t-content','$page?history-content=yes&field=$field&value=$value$tablejs');
	}
	ChangeInterval$t();
</script>
"	;
	
echo $tpl->_ENGINE_parse_body($html);	
	
}




function history_content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();		
	$xdata=array();
	$ydata=array();	
	$field=$_GET["field"];
	$value=$_GET["value"];
	if(!is_numeric($_GET["INTERVAL"])){	$_GET["INTERVAL"]=30;}
	
	$month_table="quotamonth_".date("Ym");
	if($q->COUNT_ROWS($month_table)==0){
		$month_table="quotamonth_".date("Ym",strtotime('first day of previous month'));
	}
	
	
	$sql="SELECT `day` as tday,SUM(size) as QuerySize FROM 
	`$month_table`  WHERE `$field`='$value' 
	GROUP BY tday ORDER BY tday";
	$fieldgroup="day";
	$x_title="{days}";	
	$maintitle="downloaded_size_per_day";
	$maintitle2="requests_per_day";	

	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo "<H3>Warning<hr>$sql<hr>$q->mysql_error</H3>";
	}
	
	if(mysql_num_rows($results)<2){
		$error=$tpl->_ENGINE_parse_body("{only_one_value_no_graph}");
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$size=round(($ligne["QuerySize"]/1024)/1000);
			$day=$ligne["tday"];
			$hits=$ligne["hits"];;
			if($fieldgroup=="hour"){$day="{$day}h00";}
		}
		echo $tpl->_ENGINE_parse_body("<div style='font-size:18px'>$error<hr>{$size}MB/$hits {events} {at} $day </div>");
		return;
		
	}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=round(($ligne["QuerySize"]/1024)/1000);
		$day=$ligne["tday"];
		$xdata[]=$day;
		$ydata[]=$size;
		
		
	}	
	
	$targetedfile="ressources/logs/".md5(basename(__FILE__).".".__FUNCTION__.".$sql").".png";
	$targetedfile2="ressources/logs/".md5(basename(__FILE__).__FUNCTION__.$sql.__LINE__).".png";
	$gp=new artica_graphs();
	
	$gp->width=750;
	$gp->height=350;
	$gp->filename="$targetedfile";
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;
	$gp->y_title=$tpl->_ENGINE_parse_body("{size}");;
	$gp->x_title=$tpl->_ENGINE_parse_body($x_title);
	$gp->title=null;
	$gp->margin0=true;
	$gp->Fillcolor="blue@0.9";
	$gp->color="146497";
	$gp->line_green();
	
	
	
	if(!is_file($targetedfile)){
		writelogs("Fatal \"$targetedfile\" no such file!",__FUNCTION__,__FILE__,__LINE__);
	
	}else{
		$html=$html."
		<center>
			<div style='width:99%' class=form>
				<div style='font-size:18px;margin:8px'>&laquo;$value&raquo;&nbsp;{{$maintitle}} (MB)</div>
				<img src='$targetedfile'>
			</div>
			
				
		</center>
		
		";
		
	}	
		
	echo $tpl->_ENGINE_parse_body($html);
}

function alsoknown(){
	$tpl=new templates();
	$field=$_GET["field"];
	$sql="SELECT ipaddr,hostname,uid,MAC,account FROM UserAuthDaysGrouped 
	WHERE {$_GET["field"]}='{$_GET["value"]}' GROUP BY ipaddr,hostname,uid,MAC,account";
	
	if(isset($_GET["table"])){
		$tablejs="&table={$_GET["table"]}";
		$field=$_GET["field"];
		if($field=="ipaddr"){$field="client";}
		$sql="SELECT client,hostname,uid,MAC,account FROM {$_GET["table"]} 
		WHERE $field='{$_GET["value"]}' GROUP BY client,hostname,uid,MAC,account";
		
	}
	
	
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo "<H3>Warning<hr>$sql<hr>$q->mysql_error</H3>";
		return;
	}
	$hash=array();
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		while (list ($key, $value) = each ($ligne) ){
			if(is_numeric($key)){continue;}
			if($key==$field){continue;}
			if(trim($value)==null){continue;}
			if(trim($value)=='-'){continue;}
			if(trim($value)=='0'){continue;}
			if(trim($value)=='no'){continue;}
			$hash[$key][$value]=true;
			
		}
	}
	$tr[]="
	
	<table style='width:99%' class=form>
	<tr>
		<td colspan=2><div style='font-size:14px;font-weight:bold'>{also}:</div></td>
	</tr>
	";
	while (list ($type, $array) = each ($hash) ){
		$tr[]="
			<tr>
				<td class=legend style='font-size:13px' valign='top'>{{$type}}:</td>
				<td valign='top'><table>";
		
			while (list ($a, $none) = each ($array) ){
				
						$jsMAC="
		<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('squid.members.zoom.php?field=$type&value=".urlencode($a)."$tablejs')\"
		style='font-size:13px;text-decoration:underline'>";	
				
				
				$tr[]="<tr>
							<td width=1%><img src='img/arrow-right-16.png'></td>
							<td style='font-size:13px'>$jsMAC$a</a></td>
					</tr>";
				
			}
			
		$tr[]="</table>
		</td>
	</tr>";
	}
	$tr[]="</table>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $tr));	
}

function blocked_popup(){
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
	
		if(preg_match("#(.+?)_week#", $_GET["table"],$re)){
			$title_add="&raquo;".$tpl->_ENGINE_parse_body($q->WEEK_TITLE_FROM_TABLENAME($_GET["table"]));
			$nexttable="{$re[1]}_blocked_week";
		}
			
		if(preg_match("#(.+?)_day$#", $_GET["table"],$re)){
			$title_add="&raquo;".$tpl->_ENGINE_parse_body($q->MONTH_TITLE_FROM_TABLENAME($_GET["table"]));
			$nexttable="{$re[1]}_blocked";
		}
			
		if(preg_match("#(.+?)_hour$#", $_GET["table"],$re)){
			$title_add="&raquo;".$tpl->_ENGINE_parse_body($q->DAY_TITLE_FROM_TABLENAME($_GET["table"]));
			$nexttable="{$re[1]}_blocked";
		}	
	
	
	if($field=="ipaddr"){$field="client";}
	$title=$tpl->_ENGINE_parse_body("{blocked} ? &raquo;&raquo;{{$field}}::$value $title_add");
	
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
	url: '$page?blocked-search=yes&field=$field&value=".urlencode($value)."&table=$nexttable',
	dataType: 'json',
	colModel : [
		{display: '$sitename', name : 'website', width : 472, sortable : true, align: 'left'},
		{display: '$category', name : 'category', width : 170, sortable : true, align: 'left'},
		{display: '$hits', name : 'hits', width : 94, sortable : true, align: 'left'},

		
		
	],$buttons
	searchitems : [
		{display: '$sitename', name : 'website'},
		{display: '$category', name : 'category'},
		],
	sortname: 'hits',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: 793,
	height: 450,
	singleSelect: true
	
	});
});
</script>";	
echo $tpl->_ENGINE_parse_body($html);		
	
}

function blocked_search(){
	$q=new mysql_squid_builder();	
	$tableQuery=$_GET["table"];
	$tpl=new templates();
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	
	$q->CheckTablesBlocked_day(0,$tableQuery);
	if(!$q->TABLE_EXISTS("$tableQuery")){json_error_show("$tableQuery no such table");}
	$tablejs="&table=$tableQuery";
	$table="(SELECT website,{$_GET["field"]},COUNT(ID) as hits,category FROM $tableQuery
	WHERE {$_GET["field"]}='{$_GET["value"]}' GROUP BY website,{$_GET["field"]},category) as t";
	
	
	if($q->COUNT_ROWS($tableQuery)==0){json_error_show("Empty table $tableQuery");}
	
	
	
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
		
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$md5=md5(serialize($line));
		$ligne["hits"]=numberFormat($ligne["hits"],0,""," ");
		
		$jsuid="
		<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('squid.members.sitename.php?field={$_GET["field"]}&value=".urlencode($_GET["value"])."$tablejs&familysite={$ligne["familysite"]}')\"
		style='font-size:16px;text-decoration:underline'>";
		$jsuid=null;
	

	$data['rows'][] = array(
		'id' => $md5,
		'cell' => array(
			"<span style='font-size:16px'>$jsuid{$ligne["website"]}</a></span>",
			"<span style='font-size:16px'>{$ligne["category"]}</a></span>",
			"<span style='font-size:16px'>{$ligne["hits"]}</span>",
	
	 	 	
			)
		);
	}
	
	
echo json_encode($data);
	
}

function where_popup(){
	$page=CurrentPageName();
	$tpl=new templates();		
	$field=$_GET["field"];
	$value=$_GET["value"];	
	$month_table="quotamonth_".date("Ym");
	$month_text=date("{F}");
	$q=new mysql_squid_builder();
	if(isset($_GET["table"])){
		
		$xtime=$q->TIME_FROM_DAY_TABLE($_GET["table"]);
		$month_table="quotamonth_".date("Ym",$xtime);
		$month_text=date("{F}",$xtime);
		
	}

	
	
	
	
	if($q->COUNT_ROWS($month_table)==0){
		$month_text=date("{F}",strtotime('first day of previous month'));
		$month_table="quotamonth_".date("Ym",strtotime('first day of previous month'));
	}
	
	
	
	
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
	url: '$page?where-content=yes&field=$field&value=".urlencode($value)."&table=$month_table',
	dataType: 'json',
	colModel : [
		{display: '$sitename', name : 'familysite', width : 181, sortable : true, align: 'left'},
		{display: '$size', name : 'size', width : 109, sortable : true, align: 'left'},

		
		
	],$buttons
	searchitems : [
		{display: '$sitename', name : 'familysite'},
		
		],
	sortname: 'size',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: 793,
	height: 450,
	singleSelect: true
	
	});
});
</script>";
	
echo $tpl->_ENGINE_parse_body($html);		
}

function where_search(){
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
	
	$q=new mysql_squid_builder();
	$month_table="quotamonth_".date("Ym");
	$month_text=date("{F}");
	
	if(isset($_GET["table"])){
		$month_table=$_GET["table"];
	}else{
	
		if($q->COUNT_ROWS($month_table)==0){
			$month_text=date("{F}",strtotime('first day of previous month'));
			$month_table="quotamonth_".date("Ym",strtotime('first day of previous month'));
		}
		
	}
	
	$table="(SELECT familysite,{$_GET["field"]},SUM(size) as size FROM $month_table
	GROUP BY familysite,{$_GET["field"]} HAVING {$_GET["field"]}='{$_GET["value"]}') as t";
	
	
	
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
		
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$md5=md5(serialize($line));
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
		
		
		$jsuid="
		<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('squid.members.sitename.php?field={$_GET["field"]}&value=".
		urlencode($_GET["value"])."$tablejs&familysite={$ligne["familysite"]}')\"
		style='font-size:16px;text-decoration:underline'>";
		
	

	$data['rows'][] = array(
		'id' => $md5,
		'cell' => array(
			"<span style='font-size:16px'>$jsuid{$ligne["familysite"]}</a></span>",
			"<span style='font-size:16px'>{$ligne["size"]}</span>",
	
	 	 	
			)
		);
	}
	
	
echo json_encode($data);
	
}

function what_popup(){
	$q=new mysql_squid_builder();	
	$tpl=new templates();
	$tableQuery=$_GET["table"];
	$tpl=new templates();
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	
	$field=$_GET["field"];
	$value=$_GET["value"];		
	$MyMonthText=date("{F}");
	if($tableQuery==null){
		$MyTableMonth=date("Ym")."_day";
		$tableQuery=$MyTableMonth;
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
	
	
	if($_GET["field"]=="ipaddr"){$_GET["field"]="client";}
	$sql="SELECT familysite,{$_GET["field"]},SUM(hits) as hits,category FROM $tableQuery
	WHERE {$_GET["field"]}='{$_GET["value"]}' GROUP BY familysite,{$_GET["field"]} ORDER BY hits DESC LIMIT 0,10";
	$results = $q->QUERY_SQL($sql);	
	if(!$q->ok){
		echo $q->mysql_error_html();
	}
	
	if($GLOBALS["VERBOSE"]){echo mysql_num_rows($results)." entries <br>";}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(strpos($ligne["category"], ",")>0){
			$tp=explode(",", $ligne["category"]);
			while (list ($index, $cat) = each ($tp) ){
			if(isset($valsz[$cat])){continue;}
			$valsz[$cat]=$ligne["hits"]	;
			}
			continue;	
		}
		$valsz[$cat]=$ligne["hits"]	;
	}
	while (list ($cat, $count) = each ($valsz) ){
		$xdata[]=$count;
		$ydata[]=$cat;
	}
	
	
	$targetedfilePie="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".". md5($sql).".pie.png";
	$gp=new artica_graphs($targetedfilePie);	
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;	
	$gp->width=750;
	$gp->height=550;
	$gp->ViewValues=true;
	$gp->x_title=$tpl->_ENGINE_parse_body("{what} ? $MyMonthText");
	$gp->pie();	
	
if(!is_file($targetedfilePie)){
		$html="<center>$targetedfilePie no such file</center>";
		writelogs("Fatal \"$targetedfilePie\" no such file!",__FUNCTION__,__FILE__,__LINE__);
	
	}else{
		$html=$html."
		<center>
			<div style='width:99%' class=form>
				<div style='font-size:18px;margin:8px'>&laquo;$value&raquo;&nbsp;{what} $title_add</div>
				<img src='$targetedfilePie'>
			</div>
			
			
		</center>
		
		";
		
	}	
		
	echo $tpl->_ENGINE_parse_body($html);	
}


	
