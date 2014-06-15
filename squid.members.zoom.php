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
	if(isset($_GET["alsoknown-query"])){alsoknown_query();exit;}
	if(isset($_GET["alsoknown-items"])){alsoknown_items();exit;}
	
	if(isset($_GET["history-month"])){history_month();exit;}
	if(isset($_GET["history-month-graphs"])){history_month_graphs();exit;}
	if(isset($_GET["history-graph1"])){history_month_graph1();exit;}
	if(isset($_GET["history-month-data"])){history_month_data_table();exit;}
	if(isset($_GET["history-data-items"])){history_month_data_items();exit;}
	
	
	if(isset($_GET["unknown"])){unknown_month_data_table();exit;}
	if(isset($_GET["unknown-items"])){unknown_month_data_items();exit;}
	
	
	
	
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
	
	
	$html="YahooWin('1000','$page?tabs=yes&field=$field&value=$value$tablejs','$title$title_add')";
	echo $html;
}

function tabs(){
$page=CurrentPageName();
	$tpl=new templates();
	$array["status"]='{status}';
	$array["history"]='{history}';

	
	$field=$_GET["field"];
	$value=urlencode($_GET["value"]);	
	if(isset($_GET["table"])){
		$array["blocked"]='{blocked} ?';
		$tablejs="&table={$_GET["table"]}";}
	while (list ($num, $ligne) = each ($array) ){
		
		$html[]= "<li><a href=\"$page?$num=yes&field=$field&value=$value$tablejs\" style='font-size:18px'><span>$ligne</span></a></li>\n";
	}
	
	echo build_artica_tabs($html, "squid_members_stats_zoom");
		
}


function status(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_squid_builder();
	$t=time();
	$field=$_GET["field"];
	$value=$_GET["value"];
	
	$sql="SELECT SUM(QuerySize) as QuerySize,`{$_GET["field"]}`
	FROM UserAuthDaysGrouped GROUP BY `{$_GET["field"]}` HAVING `{$_GET["field"]}`='{$_GET["value"]}'";
	
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo "<center style='font-size:14px;'>$q->mysql_error<hr>$sql</center>";}
	$USER_SIZE=$ligne["QuerySize"];
	$usersize=FormatBytes($USER_SIZE/1024);
	
	
	$sql="SELECT SUM(QuerySize) as size FROM UserAuthDaysGrouped";
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo "<center style='font-size:14px;'>$q->mysql_error<hr>$sql</center>";}
	$SUM_SIZE=$ligne["size"];	
	$SUM_SIZEhuman=FormatBytes($SUM_SIZE/1024);
	
	
	$field=$_GET["field"];
	$value=$_GET["value"];
	$title="{member}::$field - $value";
	$title=$tpl->_ENGINE_parse_body($title);
		
	
	$USER_POURC_SIZE=($USER_SIZE/$SUM_SIZE)*100;
	$USER_POURC_SIZE=round($USER_POURC_SIZE,2);
	
	
	$html="
	<div style='font-size:22px;font-weight:bold;margin-top:15px;margin-bottom:15px'>$title</div>
	<div style='width:98%' class=form>
	<table style='width:99%'>
		<tr>
			<td valign='top' class=legend style='font-size:22px'>{downloaded} ({global}):</td>
			<td style='font-size:22px;font-weight:bold'>$usersize/$SUM_SIZEhuman <strong>$USER_POURC_SIZE%</strong></td>
		</tr>
	</table>
	</div>
	<div style='font-size:22px;font-weight:bold;margin-top:15px;margin-bottom:15px'>{also}:</div>
	<div id='alsoknown-$t'></div>
	
	
	<script>
		LoadAjax('alsoknown-$t','$page?alsoknown=yes&field={$_GET["field"]}&value=".urlencode($_GET["value"])."$tablejs');
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function history_popup(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$sql="SELECT DATE_FORMAT(zDate,'%Y%m')  as tmonth,
			DATE_FORMAT(zDate,'%M') as tmonthZ FROM UserAuthDays 
			WHERE zDate>DATE_SUB(NOW(),INTERVAL 8 MONTH) AND `{$_GET["field"]}`='{$_GET["value"]}' 
			GROUP BY tmonth,tmonthZ";
	
	
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html();}
	if(mysql_num_rows($results)==0){echo $tpl->_ENGINE_parse_body("<p class=text-error>{no_data}</p>");return;}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$array[$ligne["tmonth"]]='{'.$ligne["tmonthZ"].'}';
		
	}
	$_GET["value"]=urlencode($_GET["value"]);
	while (list ($num, $ligne) = each ($array) ){
		$month_text=urlencode($ligne);
		$html[]= "<li><a href=\"$page?history-month=$num&table=quotamonth_$num&month-text=$month_text&field={$_GET["field"]}&value={$_GET["value"]}\" 
		style='font-size:16px'><span>$ligne</span></a></li>\n";
	}
	
	echo build_artica_tabs($html, "squid_members_stats_zoom_months");
	
	
}

function history_month(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$array["history-month-graphs"]='{graphs}';
	$array["history-month-data"]='{datas}';
	$array["where"]='{where} ?';
	$array["what"]='{what} ?';
	$array["unknown"]='{unknown} ?';
	
	$_GET["value"]=urlencode($_GET["value"]);
	$_GET["month-text"]=urlencode($_GET["month-text"]);
	
	while (list ($num, $ligne) = each ($array) ){
		$html[]= "<li><a href=\"$page?$num=yes&table={$_GET["table"]}&month-text={$_GET["month-text"]}&field={$_GET["field"]}&value={$_GET["value"]}\"
		style='font-size:16px'><span>$ligne</span></a></li>\n";
		
	}
	$t=time();
	echo build_artica_tabs($html, "squid_members_stats_zoom_months$t");
	
}


function history_month_graphs(){
	$page=CurrentPageName();
	$tpl=new templates();		
	$field=$_GET["field"];
	
	$table=$_GET["table"];
	$title=$tpl->_ENGINE_parse_body($_GET["month-text"]);	
	$_GET["value"]=urlencode($_GET["value"]);
	$_GET["month-text"]=urlencode($_GET["month-text"]);
$t=time();	
$html="
<div style='font-size:22px'>$title</div>
<div id='$t-content' style='height:450px;width:99%'></div>


<script>
	Loadjs('$page?history-graph1=yes&field={$_GET["field"]}&value={$_GET["value"]}&table={$_GET["table"]}&container=$t-content&month-text={$_GET["month-text"]}');
</script>
"	;
	
echo $tpl->_ENGINE_parse_body($html);	
	
}

function history_month_graph1(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$xtime=$_GET["xtime"];
	$month_table=$_GET["table"];
	
	
	$sql="SELECT `day` as tday,SUM(size) as QuerySize FROM
	`$month_table`  WHERE `{$_GET["field"]}`='{$_GET["value"]}'
	GROUP BY tday ORDER BY tday";
	$fieldgroup="day";
	$x_title="{days}";
	$maintitle="downloaded_size_per_day";
	$maintitle2="requests_per_day";
	
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error);}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=round(($ligne["QuerySize"]/1024)/1000);
		$xdata[]=$ligne["day"];
		$ydata[]=$size;
	
	}
	
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title="{downloaded_size_per_day} (MB)";
	$highcharts->yAxisTtitle="{size} (MB)";
	$highcharts->LegendSuffix=" MB";
	$highcharts->xAxisTtitle="{days}";
	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();	
	
}

function unknown_month_data_table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$tt=time();
	$t=$_GET["t"];
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$uid=$tpl->javascript_parse_text("{uid}");
	$MAC=$tpl->javascript_parse_text("{MAC}");
	$QuerySize=$tpl->javascript_parse_text("{size}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$maintitle=$tpl->javascript_parse_text("{familysite}");
	
	//ipaddr          | hostname      | uid               | MAC               | account | QuerySize    | hits
	

	
	$_GET["value"]=urlencode($_GET["value"]);
	$html="
	<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:100%'></table>
	<script>
	function Start$tt(){
	$('#flexRT$tt').flexigrid({
	url: '$page?unknown-items=yes&groupBy={$_GET["groupBy"]}&field={$_GET["field"]}&table={$_GET["table"]}&value={$_GET["value"]}',
	dataType: 'json',
	colModel : [
	{display: '$maintitle', name : 'zDate', width : 550, sortable : false, align: 'left'},
	{display: 'size', name : 'size', width : 120, sortable : true, align: 'left'},
	{display: 'hits', name : 'hits', width : 120, sortable : true, align: 'left'},
	
	
	],
	
	searchitems : [
	{display: '$maintitle', name : 'familysite'},
	
	
	
	],
	sortname: 'size',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:22px>$maintitle</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 300,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});
	}
	
	Start$tt();
	</script>
	";
	echo $html;
	}


function history_month_data_table(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$tt=time();
	$t=$_GET["t"];
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$uid=$tpl->javascript_parse_text("{uid}");
	$MAC=$tpl->javascript_parse_text("{MAC}");
	$QuerySize=$tpl->javascript_parse_text("{size}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$maintitle=$tpl->javascript_parse_text("{days}");
	
	//ipaddr          | hostname      | uid               | MAC               | account | QuerySize    | hits
	
	
	
	$_GET["value"]=urlencode($_GET["value"]);
	$html="
	<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:100%'></table>
	<script>
	function Start$tt(){
	$('#flexRT$tt').flexigrid({
	url: '$page?history-data-items=yes&groupBy={$_GET["groupBy"]}&field={$_GET["field"]}&table={$_GET["table"]}&value={$_GET["value"]}',
	dataType: 'json',
	colModel : [
	{display: '$maintitle', name : 'zDate', width : 550, sortable : false, align: 'left'},
	{display: 'size', name : 'size', width : 120, sortable : true, align: 'left'},
	
	
	],
	
	searchitems : [
	{display: '$maintitle', name : 'zDate'},
	
	
	
	],
	sortname: 'zDate',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:22px>$maintitle</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 300,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});
	}
	
	Start$tt();
	</script>
	";
	echo $html;
	}	
	
function unknown_month_data_items(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	
	if(preg_match("#quotamonth#", $_GET["table"])){
		$xtime=$q->TIME_FROM_QUOTAMONTH_TABLE($_GET["table"]);
		$_GET["table"]=date("Ym",$xtime)."_day";
	}
	
	
	if($_GET["field"]=="ipaddr"){$_GET["field"]="client";}
	$table="(SELECT familysite,{$_GET["field"]},SUM(hits) as hits,SUM(size) as size,category FROM {$_GET["table"]}
	WHERE {$_GET["field"]}='{$_GET["value"]}' GROUP BY familysite,{$_GET["field"]} 
	HAVING `category`='') as t";
	
	
	
	$t=$_GET["t"];
	$search='%';
	
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
			if(isset($_POST['page'])) {$page = $_POST['page'];}
	
			$searchstring=string_to_flexquery();
	
			if($searchstring<>null){
			$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
			$total = $ligne["TCOUNT"];
	
	}else{
			$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
			$total = $ligne["TCOUNT"];
	}
	
		if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
				$pageStart = ($page-1)*$rp;
				if(is_numeric($rp)){$limitSql = "LIMIT $pageStart, $rp";}
	
				$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
				$results = $q->QUERY_SQL($sql);
	
	
	
				$data = array();
				$data['page'] = $page;
				$data['total'] = $total;
				$data['rows'] = array();
	
				if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}
				if(mysql_num_rows($results)==0){json_error_show("no data");}
	
				$fontsize="16";
	
				//ipaddr          | hostname      | uid               | MAC               | account | QuerySize    | hits
	
	
				$time=$q->TIME_FROM_QUOTAMONTH_TABLE($_GET["table"]);
	
				while ($ligne = mysql_fetch_assoc($results)) {
					$color="black";
					$md=md5(serialize($ligne));
					$ligne["size"]=FormatBytes($ligne["size"]/1024);
					$ligne["hits"]=FormatNumber($ligne["hits"]);
					if(strlen($ligne["zDate"])==1){$ligne["zDate"]="0".$ligne["zDate"];}
					$xdate=date("Y",$time)."-".date("m",$time)."-".$ligne["zDate"]." 00:00:00";
	
					$xtime=strtotime($xdate);
					$ligne["zDate"]=$q->time_to_date($xtime);
	
	
					$uiduri="<a href=\"javascript:Loadjs('squid.members.zoom.php?field=uid&value=".urlencode($ligne["uid"])."')\"
					style='font-size:{$fontsize}px;color:$color;text-decoration:underline'>";
	
					$data['rows'][] = array(
							'id' => $ligne['ID'],
				'cell' => array(
							"<span style='font-size:{$fontsize}px;color:$color'>{$ligne["familysite"]}</span>",
							"<span style='font-size:{$fontsize}px;color:$color'>{$ligne["size"]}</span>",
							"<span style='font-size:{$fontsize}px;color:$color'>{$ligne["hits"]}</span>",
	
					)
	
					);
				}
	
	
				echo json_encode($data);
	
	
	
	}
	
function history_month_data_items(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	$t=$_GET["t"];
	$search='%';
	$table=	"(SELECT `day` as zDate,SUM(size) as size FROM
	`{$_GET["table"]}`  WHERE `{$_GET["field"]}`='{$_GET["value"]}'
	GROUP BY zDate) as t";
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
	$pageStart = ($page-1)*$rp;
	if(is_numeric($rp)){$limitSql = "LIMIT $pageStart, $rp";}
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql);
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}
	if(mysql_num_rows($results)==0){json_error_show("no data");}
	
	$fontsize="16";
	
	//ipaddr          | hostname      | uid               | MAC               | account | QuerySize    | hits
	
	
	$time=$q->TIME_FROM_QUOTAMONTH_TABLE($_GET["table"]);
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$md=md5(serialize($ligne));
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
		//$ligne["hits"]=FormatNumber($ligne["hits"]);
		if(strlen($ligne["zDate"])==1){$ligne["zDate"]="0".$ligne["zDate"];}
		$xdate=date("Y",$time)."-".date("m",$time)."-".$ligne["zDate"]." 00:00:00";
		
		$xtime=strtotime($xdate);
		$ligne["zDate"]=$q->time_to_date($xtime);
		
	
		$uiduri="<a href=\"javascript:Loadjs('squid.members.zoom.php?field=uid&value=".urlencode($ligne["uid"])."')\"
		style='font-size:{$fontsize}px;color:$color;text-decoration:underline'>";
	
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<span style='font-size:{$fontsize}px;color:$color'>{$ligne["zDate"]}</span>",
						"<span style='font-size:{$fontsize}px;color:$color'>{$ligne["size"]}</span>",
						
				)
	
		);
	}
	
	
	echo json_encode($data);
	
	
	
}





function alsoknown(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	
	
	$array["ipaddr"]="{ipaddr}";
	$array["hostname"]="{hostname}";
	$array["uid"]="{uid}";
	$array["MAC"]="{MAC}";
	
	$fontsize="style='font-size:16px'";
	
	$_GET["value"]=urlencode($_GET["value"]);
	
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?alsoknown-query=yes&groupBy=$num&field={$_GET["field"]}&value={$_GET["value"]}\" $fontsize><span>$ligne</span></a></li>\n");
	}
	
	$t=time();
	echo build_artica_tabs($html, "squid_main_useragents-$t");
	
	
		
	
}

function alsoknown_query(){
	$page=CurrentPageName();
	$tpl=new templates();
	$tt=time();
	$t=$_GET["t"];
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$uid=$tpl->javascript_parse_text("{uid}");
	$MAC=$tpl->javascript_parse_text("{MAC}");
	$QuerySize=$tpl->javascript_parse_text("{size}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$maintitle=$tpl->javascript_parse_text("{".$_GET["groupBy"]."}");
	
	//ipaddr          | hostname      | uid               | MAC               | account | QuerySize    | hits

	
	
	$_GET["value"]=urlencode($_GET["value"]);
	$html="
	<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:100%'></table>
	<script>
	function Start$tt(){
	$('#flexRT$tt').flexigrid({
	url: '$page?alsoknown-items=yes&groupBy={$_GET["groupBy"]}&field={$_GET["field"]}&value={$_GET["value"]}',
	dataType: 'json',
	colModel : [
	{display: '$maintitle', name : '{$_GET["groupBy"]}', width : 713, sortable : false, align: 'left'},
	
	],
	$buttons
	searchitems : [
	{display: '$maintitle', name : '{$_GET["groupBy"]}'},

	
	
	],
	sortname: '{$_GET["groupBy"]}',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:22px>$maintitle</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 300,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});
	}
	
	Start$tt();
	</script>
	";
	echo $html;
	}


function alsoknown_items(){
	$tpl=new templates();
	$field=$_GET["field"];
	$sql="(SELECT {$_GET["groupBy"]} FROM UserAuthDaysGrouped WHERE {$_GET["field"]}='{$_GET["value"]}') as t";
	
	$table="(SELECT {$_GET["groupBy"]} FROM $sql GROUP BY {$_GET["groupBy"]}) as t2";
	
	
	
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	$t=$_GET["t"];
	$search='%';
	
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
	$pageStart = ($page-1)*$rp;
	if(is_numeric($rp)){$limitSql = "LIMIT $pageStart, $rp";}
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql);
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}
	if(mysql_num_rows($results)==0){json_error_show("no data");}
	
	$fontsize="18";
	
	//ipaddr          | hostname      | uid               | MAC               | account | QuerySize    | hits
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$md=md5(serialize($ligne));
		$groupby=$_GET["groupBy"];
	
		$uiduri="<a href=\"javascript:Loadjs('squid.members.zoom.php?field=uid&value=".urlencode($ligne["uid"])."')\"
		style='font-size:{$fontsize}px;color:$color;text-decoration:underline'>";
	
		$data['rows'][] = array(
				'id' => $md,
				'cell' => array(
						"<span style='font-size:{$fontsize}px;color:$color'>{$ligne[$groupby]}</span>",

				)
	
		);
	}
	
	
	echo json_encode($data);
	
	return;	
	
	
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
	if(preg_match("#quotamonth#", $tableQuery)){
		$xtime=$q->TIME_FROM_QUOTAMONTH_TABLE($tableQuery);
		$tableQuery=date("Ym",$xtime)."_day";
	}
	
	
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


	
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}