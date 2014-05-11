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
	if(!$users->AsWebStatisticsAdministrator){die("no rights");}	

	
	if(isset($_GET["js"])){js();exit;}
	
	if(!isset($_GET["day"])){$_GET["day"]=date("Y-m-d");}
	if(!isset($_GET["type"])){$_GET["type"]="size";}
	if($_GET["type"]==null){$_GET["type"]="size";}
	if($_GET["day"]==null){$q=new mysql_squid_builder();$_GET["day"]=date("Y-m-d");}		
	
	if(isset($_GET["day-right-infos"])){right();die();}
	if(isset($_GET["day-right-tabs"])){right_tabs();exit;}
	if(isset($_GET["day-right-category"])){right_category();exit;}
	if(isset($_GET["day-right-users"])){right_users();exit;}
	
	
	
	if(isset($_GET["days-left-menus"])){left();die();}
	if(isset($_GET["today-zoom"])){today_zoom_js();exit;}
	if(isset($_GET["today-zoom-popup"])){today_zoom_popup();exit;}
	if(isset($_GET["today-zoom-popup-history"])){today_zoom_popup_history();exit;}
	if(isset($_GET["today-zoom-popup-history-list"])){today_zoom_popup_history_list();exit;}
	if(isset($_GET["today-zoom-popup-members"])){today_zoom_popup_members();exit;}
	if(isset($_GET["today-zoom-popup-member-list"])){today_zoom_popup_members_list();exit;}
	
	
	
	if(isset($_GET["statistics-days-left-status"])){left_status();exit;}
	if($GLOBALS["VERBOSE"]){echo "->PAGE()<br>\n";}
	
	
	
	
page_de_garde();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$size=950;
	if(isset($_GET["with-purge"])){$purge="&with-purge=yes";$size=985;}
	
	$title=$tpl->_ENGINE_parse_body("{internet_access_per_day}");
	$html="YahooWin('$size','$page?byjs=yes$purge','$title')";
	echo $html;
}

function right_tabs(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();	
	if(!isset($_GET["day"])){$_GET["day"]=$q->HIER();}	
	if($_GET["day"]==date('Y-m-d')){$_GET["day"]=$q->HIER();}	
	
	$tablenameVisited=date('Ymd',strtotime("{$_GET["day"]} 00:00:00"))."_visited";
	
	
	$tpl=new templates();
	

	
	$array["day-right-infos"]='{panel}';
	$array["day-right-category"]='{categories}';
	$array["day-right-users"]='{members}';
	if($q->TABLE_EXISTS($tablenameVisited)){
		$array["day-right-websites"]='{websites}';
	}
	
	
	
	

while (list ($num, $ligne) = each ($array) ){
	



		if($num=="day-right-websites"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.statistics.visited.day.php?table=$tablenameVisited&day={$_GET["day"]}\"><span>$ligne</span></a></li>\n");
			continue;
		}
			

		if($num=="find"){
			$html[]=$tpl->_ENGINE_parse_body( "<li><a href=\"squid.search.statistics.php?$num\"><span>$ligne</span></a></li>\n");
			continue;
		}		
		
		if($num=="not_categorized"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.not-categorized.statistics.php\"><span>$ligne</span></a></li>\n");
			continue;
		}		
	
	
		$html[]=$tpl->_ENGINE_parse_body( "<li><a href=\"$page?$num=yes&day={$_GET["day"]}\"><span>$ligne</span></a></li>\n");
	}

	$t=time();
	echo "
	<div id='right-tabs-$t' style='width:97%;font-size:14px;margin-left:10px;margin-right:-15px;margin-top:-5px'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
			//ReloadjQuery();		
			$(document).ready(function(){
				$('#right-tabs-$t').tabs();
			});
		</script>";	
}

function today_zoom_js(){
	$page=CurrentPageName();
	$tpl=new templates();			
	$q=new mysql_squid_builder();
	if($_GET["day"]==date('Y-m-d')){$_GET["day"]=$q->HIER();}
	$html="YahooWin2(1073,'$page?today-zoom-popup=yes&type={$_GET["type"]}&familysite={$_GET["familysite"]}&day={$_GET["day"]}','{$_GET["familysite"]}')";
	echo $html;
}


function right_users(){
	$time=strtotime($_GET["day"]."00:00:00");
	$buttonRepair=button("{rescan_database}","Loadjs('squid.stats.repair.day.php?time=$time')",18);

	
	$page=CurrentPageName();
	$tpl=new templates();		
	$q=new mysql_squid_builder();	
	
	$t=time();
	$html="

	<div id='graph-$t' style='width:650px;height:350px'><center style='margin:50px'><img src='img/wait-clock.gif'></center></div>
	<div id='table-websites-members-days'></div>
	
	<script>
		Loadjs('squid.traffic.statistics.days.graphs.php?getmembers=yes&container=graph-$t&day={$_GET["day"]}&type={$_GET["type"]}');
		LoadAjax('table-websites-members-days','squid.traffic.table.days.php?day={$_GET["day"]}&table=members');
	</script>
	";
	echo $html;
	
	
}


function right_category(){
	$page=CurrentPageName();
	$tpl=new templates();		
	$q=new mysql_squid_builder();	
	$time=strtotime($_GET["day"]."00:00:00");
	$buttonRepair=button("{rescan_database}","Loadjs('squid.stats.repair.day.php?time=$time')",18);	
	$hour_table=date('Ymd',strtotime($_GET["day"]))."_hour";
	$sql="SELECT COUNT( sitename ) as thits , category FROM $hour_table GROUP BY category ORDER BY thits DESC LIMIT 0 , 10";
	$t=time();
	
	$html="
	
	<div id='graph-$t' style='width:650px;height:350px'><center style='margin:50px'><img src='img/wait-clock.gif'></center></div>
	<div id='table-websites-categories-days'></div>
	
	<script>
		Loadjs('squid.traffic.statistics.days.graphs.php?getcategory=yes&container=graph-$t&day={$_GET["day"]}&type={$_GET["type"]}');
		LoadAjax('table-websites-categories-days','squid.traffic.table.days.php?day={$_GET["day"]}');
	</script>
	";
	echo $html;
	
}


function today_zoom_popup(){
	
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$tpl=new templates();	
	if(!isset($_GET["day"])){$_GET["day"]=$q->HIER();}	
	if($_GET["day"]==date('Y-m-d')){$_GET["day"]=$q->HIER();}
	$t=time();
	$today="{today}";
	if($_GET["day"]<>date("Y-m-d")){
		$time=strtotime("{$_GET["day"]} 00:00:00");
		$today=date("{l} d {F}",$time);
	}
	
	$tpl=new templates();
	$array["website-zoom"]='{website}';
	$array["website-catz"]='{categories}';
	$array["today-zoom-popup-history"]="{history}:$today";
	$array["today-zoom-popup-members"]="{members}:$today";

	while (list ($num, $ligne) = each ($array) ){
		
				
		if($num=="website-zoom"){
			$html[]= "<li><a href=\"squid.website-zoom.php?sitename={$_GET["familysite"]}&day={$_GET["day"]}\"><span>$ligne</span></a></li>\n";
			continue;
		}

		if($num=="website-catz"){
			$html[]= "<li><a href=\"squid.categorize.php?popup=yes&www={$_GET["familysite"]}&bykav=&day={$_GET["day"]}&group=&table-size=993&row-explain=764\"><span>$ligne</span></a></li>\n";
			continue;
		}			
		
		
		
		
		$html[]= "<li><a href=\"$page?$num=yes&day={$_GET["day"]}&type={$_GET["type"]}&familysite={$_GET["familysite"]}\"><span style='font-size:14px'>$ligne</span></a></li>\n";
	}
	
	$t=time();
	echo build_artica_tabs($html, $t);
			
	
	
	
}

function today_zoom_popup_members(){
	$page=CurrentPageName();
	$tpl=new templates();		
	$q=new mysql_squid_builder();	
	$t=time();
	if($_GET["day"]==date('Y-m-d')){$_GET["day"]=$q->HIER();}
	$type=$_GET["type"];
	$field_query="size";
	$field_query2="SUM(size)";	
	$table_field=$tpl->javascript_parse_text("{size}");
	$hour_table=date('Ymd',strtotime($_GET["day"]))."_hour";
	$member=$tpl->_ENGINE_parse_body("{members}");
	$sitename=$tpl->_ENGINE_parse_body("{website}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$title=$tpl->javascript_parse_text("{size}/$member {for} {$_GET["familysite"]} ({$_GET["type"]})");
	
$html="
<div id='graph-$t' style='width:930px;height:350px'><center style='margin:50px'><img src='img/wait-clock.gif'></center></div>

<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>

Loadjs('squid.traffic.statistics.days.graphs.php?getwebsite-members=yes&type={$_GET["type"]}&day={$_GET["day"]}&familysite={$_GET["familysite"]}&container=graph-$t');

$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?today-zoom-popup-member-list=yes&day={$_GET["day"]}&type={$_GET["type"]}&familysite={$_GET["familysite"]}',
	dataType: 'json',
	colModel : [
		{display: '$member', name : 'uid', width :394, sortable : true, align: 'left'},
		{display: 'MAC', name : 'MAC', width : 165, sortable : true, align: 'left'},
		{display: 'IP', name : 'client', width : 115, sortable : true, align: 'left'},
		{display: '$table_field', name : 'thits', width : 75, sortable : true, align: 'left'},
		

		],
	searchitems : [
		{display: '$member', name : 'uid'},
		{display: 'MAC', name : 'MAC'},
		{display: 'IP', name : 'client'},
		
		],	

	sortname: 'thits',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: false,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 280,
	singleSelect: true
	
	});   
});
</script>

";	

	
	echo $tpl->_ENGINE_parse_body($html);

}


function today_zoom_popup_history(){
	
	
	
	$page=CurrentPageName();
	$tpl=new templates();		
	$q=new mysql_squid_builder();	
	$t=time();
	if($_GET["day"]==date('Y-m-d')){$_GET["day"]=$q->HIER();}
	$type=$_GET["type"];
	$field_query="size";
	$field_query2="SUM(size)";	
	$table_field=$tpl->_ENGINE_parse_body("{size}");
	$hour_table=date('Ymd',strtotime($_GET["day"]))."_hour";
	$member=$tpl->_ENGINE_parse_body("{member}");
	$sitename=$tpl->_ENGINE_parse_body("{website}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	
	$timex=strtotime("{$_GET["day"]} 00:00:00");
	$Title=$tpl->javascript_parse_text(date("Y {F} d",$timex));
	
$html="
<div id='graph-$t' style='width:930px;height:350px'><center style='margin:50px'><img src='img/wait-clock.gif'></center></div>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
Loadjs('squid.traffic.statistics.days.graphs.php?getwebsite=yes&type={$_GET["type"]}&day={$_GET["day"]}&familysite={$_GET["familysite"]}&container=graph-$t');

$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?today-zoom-popup-history-list=yes&day={$_GET["day"]}&type={$_GET["type"]}&familysite={$_GET["familysite"]}',
	dataType: 'json',
	colModel : [
		{display: '$member', name : 'uid', width :86, sortable : true, align: 'left'},
		{display: 'MAC', name : 'MAC', width : 107, sortable : false, align: 'left'},
		{display: 'IP', name : 'client', width : 85, sortable : false, align: 'left'},
		{display: '$sitename', name : 'sitename', width : 326, sortable : false, align: 'left'},
		{display: '$table_field', name : 'thits', width : 67, sortable : false, align: 'left'},
		{display: '$category', name : 'category', width : 206, sortable : false, align: 'left'},
		

		],
	
	searchitems : [
		{display: '$member', name : 'uid'},
		{display: 'MAC', name : 'MAC'},
		{display: 'IP', name : 'client'},
		{display: '$sitename', name : 'sitename'},
		
		],	
		
	sortname: 'thits',
	sortorder: 'desc',
	usepager: true,
	title: '$Title',
	useRp: false,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true
	
	});   
});



</script>

";	

	
	echo $tpl->_ENGINE_parse_body($html);

}

function today_zoom_popup_members_list(){
	$Mypage=CurrentPageName();
	$tpl=new templates();		
	$q=new mysql_squid_builder();	
	$t=time();
	$fontsize=14;
	if($_GET["day"]==date('Y-m-d')){$_GET["day"]=$q->HIER();}
	$type=$_GET["type"];
	$field_query="size";
	$field_query2="SUM(size)";	
	$table_field="{size}";
	$category=$tpl->_ENGINE_parse_body("{category}");
	$hour_table=date('Ymd',strtotime($_GET["day"]))."_hour";
	$member=$tpl->_ENGINE_parse_body("{member}");
	$sitename=$tpl->_ENGINE_parse_body("{website}");
	$page=1;
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";	
	
	if($type=="req"){
		$field_query="hits";
		$field_query2="SUM(hits)";
		$table_field="{hits}";	
	}	
		
	
	$table="(SELECT $field_query2 as thits, uid,client,MAC,familysite FROM $hour_table 
	GROUP BY uid,client,MAC,familysite HAVING familysite='{$_GET["familysite"]}') as t";
	
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
	
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"]+1;
	}
	
	
	
	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql";
	
	
	$results=$q->QUERY_SQL($sql);
	
	$data = array();
	$data['page'] = 0;
	$data['total'] =$total;
	$data['rows'] = array();	
	
	
	if(!$q->ok){json_error_show($q->mysql_error);}	
	if(mysql_num_rows($results)==0){json_error_show("No item");}
	
	$data['total'] = mysql_num_rows($results);
	
	$style="style='font-size:{$fontsize}px'";
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		
		if($type<>"req"){$ligne["thits"]=FormatBytes($ligne["thits"]/1024);}
		
		$categorize="Loadjs('squid.categorize.php?www={$ligne["sitename"]}')";
		if(trim($ligne["category"])==null){$ligne["category"]="<span style='color:#D70707'>{categorize_this_website}</span>";}
	
						
		$id=md5(@implode("", $ligne));
		
		if(trim($ligne["uid"])=="-"){$ligne["uid"]=null;}
		if(trim($ligne["uid"])==null){$ligne["uid"]=$q->UID_FROM_MAC($ligne["MAC"]);}
		
		$categorize="<a href=\"javascript:blur()\" OnClick=\"javascript:$categorize\" style='font-size:{$fontsize}px;text-decoration:underline'>";
		
		$zoomByuid="<a href=\"javascript:blur()\" 
		OnClick=\"Loadjs('squid.traffic.statistics.days.memberAndWebsite.php?field=uid&value=". urlencode($ligne["uid"])."&familysite={$_GET["familysite"]}&day={$_GET["day"]}')\"
		style='font-size:{$fontsize}px;text-decoration:underline'>";

		
		$zoomByuid="<a href=\"javascript:blur()\"
		OnClick=\"Loadjs('squid.traffic.statistics.days.memberAndWebsite.php?field=uid&value=". urlencode($ligne["uid"])."&familysite={$_GET["familysite"]}&day={$_GET["day"]}')\"
		style='font-size:{$fontsize}px;text-decoration:underline'>";

		$zoomByclient="<a href=\"javascript:blur()\"
		OnClick=\"Loadjs('squid.traffic.statistics.days.memberAndWebsite.php?field=client&value=". urlencode($ligne["client"])."&familysite={$_GET["familysite"]}&day={$_GET["day"]}')\"
		style='font-size:{$fontsize}px;text-decoration:underline'>";

		$zoomByMac="<a href=\"javascript:blur()\"
		OnClick=\"Loadjs('squid.traffic.statistics.days.memberAndWebsite.php?field=MAC&value=". urlencode($ligne["MAC"])."&familysite={$_GET["familysite"]}&day={$_GET["day"]}')\"
		style='font-size:{$fontsize}px;text-decoration:underline'>";		
		
		
		
		$data['rows'][] = array(
			'id' => $id,
			'cell' => array(
			"<span $style>$zoomByuid{$ligne["uid"]}</a></span>",
			"<span $style>$zoomByMac{$ligne["MAC"]}</a></span>",
			"<span $style>$zoomByclient{$ligne["client"]}</a></span>",
			"<span $style>{$ligne["thits"]}</span>",
			)
			);		
		
		
	}

echo json_encode($data);	
			
}

function today_zoom_popup_history_list(){
	$Mypage=CurrentPageName();
	$tpl=new templates();		
	$q=new mysql_squid_builder();	
	$t=time();
	$fontsize=13;
	$type=$_GET["type"];
	$field_query="size";
	$field_query2="SUM(size)";	
	$table_field="{size}";
	$category=$tpl->_ENGINE_parse_body("{category}");
	if($_GET["day"]==date('Y-m-d')){$_GET["day"]=$q->HIER();}
	$hour_table=date('Ymd',strtotime($_GET["day"]))."_hour";
	$member=$tpl->_ENGINE_parse_body("{member}");
	$sitename=$tpl->_ENGINE_parse_body("{website}");
	
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	if($type=="req"){
		$field_query="hits";
		$field_query2="SUM(hits)";
		$table_field="{hits}";	
	}	
		
	
	$table="(SELECT $field_query2 as thits, uid,client,MAC,sitename,category,familysite FROM $hour_table 
	GROUP BY uid,client,sitename,MAC,category,familysite HAVING familysite='{$_GET["familysite"]}' ) as t";
	
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
	
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"]+1;
	}
	
	
	
	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql";	
	
	
	$results=$q->QUERY_SQL($sql);
	
	$data = array();
	$data['page'] = 0;
	$data['total'] = $total;
	$data['rows'] = array();	
	
	
	if(!$q->ok){json_error_show($q->mysql_error);};	
	if(mysql_num_rows($results)==0){json_error_show("No data");}
	
	$data['total'] = mysql_num_rows($results);
	
	$style="style='font-size:{$fontsize}px'";
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		
		if($type<>"req"){$ligne["thits"]=FormatBytes($ligne["thits"]/1024);}
		
		$categorize="Loadjs('squid.categorize.php?www={$ligne["sitename"]}')";
		if(trim($ligne["category"])==null){$ligne["category"]="<span style='color:#D70707'>{categorize_this_website}</span>";}
	
						
		$id=md5(@implode("", $ligne));
		
		if(trim($ligne["uid"])=="-"){$ligne["uid"]=null;}
		if(trim($ligne["uid"])==null){$ligne["uid"]=$q->UID_FROM_MAC($ligne["MAC"]);}
		
		$categorize="<a href=\"javascript:blur()\" 
		OnClick=\"javascript:$categorize\" 
		style='font-size:{$fontsize}px;text-decoration:underline'>";
		
		
		$familysite=$q->GetFamilySites($ligne["sitename"]);
		$TrafficHour="<a href=\"javascript:blur()\" 
		OnClick=\"javascript:Loadjs('squid.traffic.statistics.hours.php?familysite={$ligne["sitename"]}&day={$_GET["day"]}')\" 
		style='font-size:{$fontsize}px;text-decoration:underline'>";
		
		
		$data['rows'][] = array(
			'id' => $id,
			'cell' => array(
			"<span $style>{$ligne["uid"]}</span>",
			"<span $style>{$ligne["MAC"]}</span>",
			"<span $style>{$ligne["client"]}</span>",
			"<span $style>$TrafficHour{$ligne["sitename"]}</a></span>",
			"<span $style>{$ligne["thits"]}</span>",
			"<span $style>$categorize{$ligne["category"]}</a></span>"
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
	
	$old="<tr>
		<td class=legend nowrap>{select}</td>
		<td valign='top' style='font-size:14px' nowrap><a href=\"javascript:blur();\" OnClick=\"javascript:SquidFlowDaySizeQuery('size')\"$stylehref>{downloaded_flow}</a></td>
	</tr>
	<tr>
		<td class=legend nowrap>{select}</td>
		<td valign='top' style='font-size:14px' nowrap><a href=\"javascript:blur();\" OnClick=\"javascript:SquidFlowDaySizeQuery('req')\"$stylehref>{requests}</a></td>
	</tr>		";
	
	$html="
		<table style='width:97%' class=form>
	<tbody>
	<tr>
		<td class=legend nowrap>{day}:</td>
		<td>". field_date('sdate',$_GET["day"],"font-size:14px;padding:3px;width:85px","mindate:$mindate;maxdate:$maxdate")."</td>	
		<td>". button("{go}","SquidFlowDaySizeQuery()")."</td>
	</tr>
	
	</tbody>
	</table>
	
	<div id='statistics-days-left-status'></div>
	
<script>
		function SquidFlowDaySizeQuery(type){
			if(!type){
				if(document.getElementById('squid-stats-day-hide-type')){type=document.getElementById('squid-stats-day-hide-type').value;}
			}
			if(!type){type='size';}
			
			var sdate=document.getElementById('sdate').value;
			LoadAjax('days-right-infos','$page?day-right-tabs=yes&day='+sdate+'&type='+type);
		}
</script>
";
	echo $tpl->_ENGINE_parse_body($html);	
}

function page_de_garde_purge(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	
		$array["purge"]='{database}';
		$array["stats"]='{statistics_by_day}';
	




	while (list ($num, $ligne) = each ($array) ){

		if($num=="purge"){
			$html[]=$tpl->_ENGINE_parse_body( "<li><a href=\"squid.artica.statistics.purge.php?popup=yes\"><span>$ligne</span></a></li>\n");
			continue;
		}

		if($num=="stats"){
			$html[]=$tpl->_ENGINE_parse_body( "<li><a href=\"$page?byjs=yes\"><span>$ligne</span></a></li>\n");
			continue;
		}
			

		

		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&day={$_GET["day"]}\"><span>$ligne</span></a></li>\n");
	}

	$t=time();
	echo "
		<div id=purge_stats style='width:97%;font-size:14px;margin-left:10px;margin-right:-15px;margin-top:-5px'>
			<ul>". implode("\n",$html)."</ul>
			</div>
			<script>
			$(document).ready(function(){
				$('#purge_stats').tabs();
			});
</script>";
}


function page_de_garde(){
	if(isset($_GET["with-purge"])){page_de_garde_purge();return;}
	$page=CurrentPageName();
	$tpl=new templates();	
	$styletable="style='width:105%;margin-left:-15px;padding-right:-15px;margin-right:-15px'";
	if(isset($_GET["byjs"])){$styletable=null;}
		
		
	
	$html="<table $styletable>
	<tbody>
	<tr>
		<td valign='top' width=1% style='vertical-align:top;'>
			
			<div id='days-left-menus' style='width:220px'></div>
		</td>
		<td valign='top' width=105% style='padding-right:-15px'><div id='days-right-infos'></div></td>
	</tr>
	</tbody>
	</table>
	
	<script>
		LoadAjax('days-left-menus','$page?days-left-menus=yes');
		LoadAjax('days-right-infos','$page?day-right-tabs=yes');
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
	
}

function right(){
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."<br>\n";}
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();	
	if(!isset($_GET["day"])){$_GET["day"]=$q->HIER();}
	if(!isset($_GET["type"])){$_GET["type"]="size";}
	if($_GET["type"]==null){$_GET["type"]="size";}
	$type=$_GET["type"];
	$field_query="size";
	$today=date("Y-m-d");
	if($_GET["day"]==$today){$_GET["day"]=$q->HIER();}
	
	$time=strtotime($_GET["day"]."00:00:00");
	$buttonRepair=button("{rescan_database}","Loadjs('squid.stats.repair.day.php?time=$time')",18);
	
	$hour_table=date('Ymd',strtotime($_GET["day"]))."_hour";
	$sourcetable="dansguardian_events_".date('Ymd',strtotime($_GET["day"]));
	
	if($GLOBALS["VERBOSE"]){echo "Table=$hour_table<br>\n";}
	
	
	if(!$q->TABLE_EXISTS($hour_table)){
		if($q->TABLE_EXISTS($sourcetable)){
			$button="<hr>".button("{repair}","RepairTableDay('$sourcetable')",16);
		}else{
			$button="<div style='font-size:10px'>$sourcetable no such table...$buttonRepair</div>";
		}
		echo $tpl->_ENGINE_parse_body("<input type='hidden' id='squid-stats-day-hide-type' 
				value='{$_GET["type"]}'>$title<center style='margin:50px'><H2>{error_no_datas}</H2>
				<div style='font-size:10px'>$hour_table no such table..</div>$button</center><script>LoadAjax('statistics-days-left-status','$page?statistics-days-left-status=yes&day={$_GET["day"]}');</script>");
		return;
	}
	
	
	if($type=="req"){$field_query="hits";}
	
	$tG=time();
	$t=time();
	
	$html="
	<input type='hidden' id='squid-stats-day-hide-type' value='$type'>
	<div id='graph-$tG' style='width:650px;height:350px'><center style='margin:50px'><img src='img/wait-clock.gif'></center></div>
	<div id='graph2-$tG' style='width:650px;height:350px'><center style='margin:50px'><img src='img/wait-clock.gif'></center></div>
	<div id='$t'></div>
	<script>
		function WaitAndLaunch$t(){
			Loadjs('squid.traffic.statistics.days.graphs.php?getsize=yes&container=graph-$tG&day={$_GET["day"]}&type={$_GET["type"]}');
			Loadjs('squid.traffic.statistics.days.graphs.php?getwebsites=yes&container=graph2-$tG&day={$_GET["day"]}&type={$_GET["type"]}');
			LoadAjax('$t','squid.traffic.statistics.days.table.php?field_query=$field_query&hour_table=$hour_table');
		
		}
		
		LoadAjax('statistics-days-left-status','$page?statistics-days-left-status=yes&day={$_GET["day"]}');
		setTimeout(\"WaitAndLaunch$t()\",1000);
		
		
		
	</script>
	
";
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
}



function squid_status_stats(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_squid_builder();
	$websitesnums=$q->COUNT_ROWS("dansguardian_sitesinfos","artica_backup");
	$websitesnums=numberFormat($websitesnums,0,""," ");	
	


	$q=new mysql_squid_builder();
	$requests=$q->EVENTS_SUM();
	$requests=numberFormat($requests,0,""," ");	
	
	$DAYSNumbers=$q->COUNT_ROWS("tables_day");
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(totalsize) as tsize FROM tables_day"));
	$totalsize=FormatBytes($ligne["tsize"]/1024);
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT AVG(cache_perfs) as pourc FROM tables_day"));
	$pref=round($ligne["pourc"]);	

$html="
<table style='width:100%'>
	<tbody>
	<tr>
		<td valign='top' style='font-size:14px'><b>$DAYSNumbers</b> {daysOfStatistics}</td>
	</tr>
	<tr>
		<td valign='top' style='font-size:14px'><b>$requests</b> {requests}</td>
	</tr>		
	<tr>
		<td valign='top' style='font-size:14px'><b>$totalsize</b> {downloaded_flow}</td>
	</tr>
	<tr>
		<td valign='top' style='font-size:14px'><b>$pref%</b> {cache_performance}</td>
	</tr>	
	</tbody>
	</table>";

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

function left_status(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_squid_builder();
	$day=$_GET["day"];
	$time=strtotime("$day 00:00:00");
	$table=date("Ymd",$time)."_hour";
	
	if($q->TABLE_EXISTS($table)){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(sitename) as tcount FROM `$table` WHERE LENGTH(category)=0"));
		$notcategorized=$ligne["tcount"];
		if(!$q->ok){$err1=icon_mysql_error($q->mysql_error);}
		
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(sitename) as tcount FROM `$table` WHERE LENGTH(category)>2"));
		$categorized=$ligne["tcount"];	
		if(!$q->ok){$err2=icon_mysql_error($q->mysql_error);}
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(sitename) as tcount FROM `$table`"));
		$visited=$ligne["tcount"];	
		if(!$q->ok){$err3=icon_mysql_error($q->mysql_error);}		
		$notcategorized=texthref($notcategorized, "Loadjs('squid.visited.php?day=$day&onlyNot=yes')");
		$html="
		<table style='width:97%' class=form>
		<tbody>
		<tr>
			<td class=legend>{not_categorized}:</td>
			<td style='font-size:14px;font-weight:bold'>$notcategorized</td>
			<td width=1%>$err1</td>
		</tr>
		<tr>
			<td class=legend>{categorized}:</td>
			<td style='font-size:14px;font-weight:bold'>$categorized</td>
			<td width=1%>$err2</td>
		</tr>	
		<tr>
			<td class=legend>{visited}:</td>
			<td style='font-size:14px;font-weight:bold'>$visited</td>
			<td width=1%>$err2</td>
		</tr>


		
		</tbody>
		</table>";
		
	}
		
		$html=$html."<table style='width:97%' class=form>
		<tbody>
			<tr>
				<td width=1%><img src='img/arrow-right-16.png'></td>
				<td style='font-size:14px;font-weight:bold'>
						<a href=\"javascript:blur();\" 
						OnClick=\"javascript:Loadjs('squid.traffic.statistics.days.calendar.php');\" 
						style='font-size:14px;font-weight:bold;text-decoration:underline'>{calendar}</a>
				</td>
			</tr>		
			<tr>
				<td width=1%><img src='img/arrow-right-16.png'></td>
				<td style='font-size:14px;font-weight:bold'>
						<a href=\"javascript:blur();\" 
						OnClick=\"javascript:Loadjs('squid.visited.php?recategorize-day-js={$_GET["day"]}');\" 
						style='font-size:14px;font-weight:bold;text-decoration:underline'>{recategorize_schedule}</a>
				</td>
			</tr>		
			
			<tr><td colspan=2><hr></td></tr>
			<tr>
				<td width=1%><img src='img/arrow-right-16.png'></td>
				<td style='font-size:14px;font-weight:bold'>
						<a href=\"javascript:blur();\" 
						OnClick=\"javascript:Loadjs('squid.stats.repair.day.php?time=$time');\" 
						style='font-size:14px;font-weight:bold;text-decoration:underline'>{rescan_database}</a>
				</td>
			</tr>
		</tbody>
		</table>
		
		";
		
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

