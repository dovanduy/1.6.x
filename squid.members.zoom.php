<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die();}	
	
	
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["history"])){history_popup();exit;}
	if(isset($_GET["history-content"])){history_content();exit;}
	
	if(isset($_GET["where"])){where_popup();exit;}
	if(isset($_GET["where-content"])){where_search();exit;}
	if(isset($_GET["alsoknown"])){alsoknown();exit;}
	
	if(isset($_GET["what"])){what_popup();exit;}
	
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
			
			if(preg_match("#_(day)#", $tableQuery)){
				$title_add="&raquo;".$tpl->_ENGINE_parse_body($q->MONTH_TITLE_FROM_TABLENAME($_GET["table"]));
			}
			
		}
	
	}
	$html="YahooWin('750','$page?tabs=yes&field=$field&value=$value$tablejs','$title$title_add')";
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
	$value=$_GET["value"];	
	if(isset($_GET["table"])){$tablejs="&table={$_GET["table"]}";}
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

	
	$sql="SELECT SUM(hits) as hits, SUM(QuerySize) as QuerySize 
	FROM UserAuthDaysGrouped WHERE `{$_GET["field"]}`='{$_GET["value"]}'";
	
	
	if(isset($_GET["table"])){
		if($field=="ipaddr"){$field="client";}
		$sql="SELECT SUM(size) as QuerySize,SUM(hits) as hits FROM `{$_GET["table"]}`  WHERE `$field`='$value'";
		$tablejs="&table={$_GET["table"]}";
	}	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo "<center style='font-size:14px;'>$q->mysql_error<hr>$sql</center>";}
	
	
	$USER_HITS=$ligne["hits"];
	$USER_SIZE=$ligne["QuerySize"];
	
	$sq="SELECT SUM(hits) as hits, SUM(QuerySize) as QuerySize FROM UserAuthDaysGrouped";

	if(isset($_GET["table"])){
		if($field=="ipaddr"){$field="client";}
		$sql="SELECT SUM(size) as QuerySize,SUM(hits) as hits FROM `{$_GET["table"]}`";
		
	}	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo "<center style='font-size:14px;'>$q->mysql_error<hr>$sql</center>";}
	
	$SUM_HITS=$ligne["hits"];
	$SUM_SIZE=$ligne["QuerySize"];	
	
	
	$usersize=FormatBytes($USER_SIZE/1024);
	$SUM_SIZEhuman=FormatBytes($SUM_SIZE/1024);
		
	$userhits=numberFormat($USER_HITS,0,""," ");	
	$USER_POURC_SIZE=($USER_SIZE/$SUM_SIZE)*100;
	$USER_POURC_SIZE=round($USER_POURC_SIZE,2);
	
	$USER_POURC_HITS=($USER_HITS/$SUM_HITS)*100;
	$USER_POURC_HITS=round($USER_POURC_HITS,2);
	
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
				<td style='font-size:16px;font-weight:bold'>$usersize <strong>$USER_POURC_SIZE%</strong></td>
			</tr>
			<tr>
				<td valign='top' class=legend style='font-size:16px'>{access}:</td>
				<td style='font-size:16px;font-weight:bold'>$userhits <strong>$USER_POURC_HITS%</strong></td>
			</tr>	
			</table>
			<div id='alsoknown-$t'></div>
	</td>
	</tr>
	</table>
	
	<script>
		LoadAjax('alsoknown-$t','$page?alsoknown=yes&field={$_GET["field"]}&value={$_GET["value"]}$tablejs');
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function history_popup(){
	$page=CurrentPageName();
	$tpl=new templates();		
	$field=$_GET["field"];
	$value=$_GET["value"];	
	if(isset($_GET["table"])){$tablejs="&table={$_GET["table"]}";}	
$t=time();	
$html="
<table style='width:99%' class=form>
<tr>
	<td class=legend style='font-size:16px'>{last}:</td>
	<td style='font-size:16px'>". Field_text("$t-day",30,"font-size:16px;width:60px",null,null,null,false,"ChangeIntervalCheck$t(event)")."&nbsp;{days}</td>
	
</tr>
</table>
<div id='$t-content'></div>


<script>
	function ChangeIntervalCheck$t(e){
		if(checkEnter(e)){ChangeInterval$t();}
	}

function ChangeInterval$t(){
	var table='{$_GET["table"]}';
	if(table.length>0){document.getElementById('$t-day').disabled=true;}
	var days=document.getElementById('$t-day').value;
	LoadAjax('$t-content','$page?history-content=yes&field=$field&value=$value$tablejs&INTERVAL='+days);
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
	
	$sql="SELECT DAY(zDate) as tday,SUM(QuerySize) as QuerySize,SUM(hits) as hits FROM 
	`UserAuthDays`  WHERE `$field`='$value' 
	AND zDate> DATE_SUB(NOW(),INTERVAL {$_GET["INTERVAL"]} DAY) GROUP BY tday ORDER BY tday";
	
	if(isset($_GET["table"])){
		if($field=="ipaddr"){$field="client";}
		$sql="SELECT day as tday,SUM(size) as QuerySize,SUM(hits) as hits FROM 
		`{$_GET["table"]}`  WHERE `$field`='$value' GROUP BY tday ORDER BY tday";
		
	}
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo "<H3>Warning<hr>$sql<hr>$q->mysql_error</H3>";
	}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=round(($ligne["QuerySize"]/1024)/1000);
		$day=$ligne["tday"];
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
	$gp->x_title=$tpl->_ENGINE_parse_body("{days}");
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
	$gp2->x_title=$tpl->_ENGINE_parse_body("{days}");
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
				<div style='font-size:18px;margin:8px'>&laquo;$value&raquo;&nbsp;{downloaded_size_per_day} (MB)</div>
				<img src='$targetedfile'>
			</div>
			
			<div style='width:99%' class=form>
				<div style='font-size:18px;margin:8px'>&laquo;$value&raquo;&nbsp;{requests_per_day}</div>
				<img src='$targetedfile2'>
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
		OnClick=\"javascript:Loadjs('squid.members.zoom.php?field=$type&value=$a$tablejs')\"
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

function where_popup(){
	$page=CurrentPageName();
	$tpl=new templates();		
	$field=$_GET["field"];
	$value=$_GET["value"];	

	$MyTableMonth=date("Ym")."_day";
	$MyMonthText=date("{F}");
	$q=new mysql_squid_builder();
	
	if(isset($_GET["table"])){
		$MyTableMonth=$_GET["table"];
	}
	
	
	if(!$q->TABLE_EXISTS($MyTableMonth)){
		echo FATAL_ERROR_SHOW_128("&laquo;$MyTableMonth&raquo; {table_does_not_exists}");
		return;
	}	
	
	if(preg_match("#_(week|day)#", $tableQuery)){
			$MyMonthText="&raquo;".$tpl->_ENGINE_parse_body($q->WEEK_TITLE_FROM_TABLENAME($_GET["table"]));
			if(preg_match("#_(day)#", $tableQuery)){
				$MyMonthText="&raquo;".$tpl->_ENGINE_parse_body($q->MONTH_TITLE_FROM_TABLENAME($_GET["table"]));
			}
		}
	
	
	if($field=="ipaddr"){$field="client";}
	$title=$tpl->_ENGINE_parse_body("{where} ? &raquo;&raquo;{{$field}}::$value ($MyMonthText)");
	
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
	url: '$page?where-content=yes&field=$field&value=$value&table=$MyTableMonth',
	dataType: 'json',
	colModel : [
		{display: '$sitename', name : 'familysite', width : 181, sortable : true, align: 'left'},
		{display: '$category', name : 'category', width : 245, sortable : true, align: 'left'},
		{display: '$size', name : 'size', width : 109, sortable : true, align: 'left'},
		{display: '$hits', name : 'hits', width : 94, sortable : true, align: 'left'},

		
		
	],$buttons
	searchitems : [
		{display: '$sitename', name : 'familysite'},
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
	$table="(SELECT familysite,{$_GET["field"]},SUM(size) as size,SUM(hits) as hits,category FROM $tableQuery
	WHERE {$_GET["field"]}='{$_GET["value"]}' GROUP BY familysite,{$_GET["field"]}) as t";
	
	
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
		
	

	$data['rows'][] = array(
		'id' => $md5,
		'cell' => array(
			"<span style='font-size:16px'>$jsuid{$ligne["familysite"]}</a></span>",
			"<span style='font-size:16px'>{$ligne["category"]}</a></span>",
			"<span style='font-size:16px'>{$ligne["size"]}</span>",
			"<span style='font-size:16px'>{$ligne["hits"]}</span>",
	
	 	 	
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
	
	if(preg_match("#_(week|day)#", $tableQuery)){
			$MyMonthText="&raquo;".$tpl->_ENGINE_parse_body($q->WEEK_TITLE_FROM_TABLENAME($tableQuery));
			if(preg_match("#_(day)#", $tableQuery)){
				$MyMonthText="&raquo;".$tpl->_ENGINE_parse_body($q->MONTH_TITLE_FROM_TABLENAME($tableQuery));
			}
		}
	
	
	if($_GET["field"]=="ipaddr"){$_GET["field"]="client";}
	$sql="SELECT familysite,{$_GET["field"]},SUM(hits) as hits,category FROM $tableQuery
	WHERE {$_GET["field"]}='{$_GET["value"]}' GROUP BY familysite,{$_GET["field"]} ORDER BY hits DESC LIMIT 0,10";
	$results = $q->QUERY_SQL($sql);	
	if(!$q->ok){
		echo $q->mysql_error;
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
	$gp->width=650;
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
				<div style='font-size:18px;margin:8px'>&laquo;$value&raquo;&nbsp;{what} $MyMonthText</div>
				<img src='$targetedfilePie'>
			</div>
			
			
		</center>
		
		";
		
	}	
		
	echo $tpl->_ENGINE_parse_body($html);	
}


	
