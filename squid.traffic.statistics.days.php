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



function right_tabs(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();	
	if(!isset($_GET["day"])){$_GET["day"]=$q->HIER();}	
	
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
			$html[]= "<li><a href=\"squid.statistics.visited.day.php?table=$tablenameVisited&day={$_GET["day"]}\"><span>$ligne</span></a></li>\n";
			continue;
		}
			

		if($num=="find"){
			$html[]= "<li><a href=\"squid.search.statistics.php?$num\"><span>$ligne</span></a></li>\n";
			continue;
		}		
		
		if($num=="not_categorized"){
			$html[]= "<li><a href=\"squid.not-categorized.statistics.php\"><span>$ligne</span></a></li>\n";
			continue;
		}		
	
	
		$html[]= "<li><a href=\"$page?$num=yes&day={$_GET["day"]}\"><span>$ligne</span></a></li>\n";
	}
	
	$t=time();
	echo $tpl->_ENGINE_parse_body( "
	<div id=$t style='width:97%;font-size:14px;margin-left:10px;margin-right:-15px;margin-top:-5px'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#$t').tabs();
			
			
			});
		</script>");		
}

function today_zoom_js(){
	$page=CurrentPageName();
	$tpl=new templates();			
	$html="YahooWin2(918,'$page?today-zoom-popup=yes&type={$_GET["type"]}&familysite={$_GET["familysite"]}&day={$_GET["day"]}','{$_GET["familysite"]}')";
	echo $html;
}


function right_users(){
	$page=CurrentPageName();
	$tpl=new templates();		
	$q=new mysql_squid_builder();	
	$hour_table=date('Ymd',strtotime($_GET["day"]))."_hour";
	$sql="SELECT SUM( hits ) as thits , client,uid,MAC FROM $hour_table GROUP BY client,uid,MAC ORDER BY thits DESC LIMIT 0 , 10";	
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}	
	if(mysql_num_rows($results)==0){echo $tpl->_ENGINE_parse_body("$title<center style='margin:50px'><H2>{error_no_datas}</H2>$sql</center>");return;}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(trim($ligne["hostname"])==null){$ligne["hostname"]=$ligne["client"];}
		if(trim($ligne["uid"])=="-"){$ligne["uid"]=$ligne["hostname"];}
		if(trim($ligne["uid"])==null){$ligne["uid"]=$ligne["hostname"];}
		$ydata[]=$ligne["uid"];
		$xdata[]=$ligne["thits"];
	}
		
	$title=$tpl->_ENGINE_parse_body("{top_members}/{hits}");
	$targetedfile="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".day.top.10.members.$hour_table.category.png";
	$gp=new artica_graphs($targetedfile);	
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;	
	$gp->width=550;
	$gp->height=550;
	$gp->ViewValues=true;
	$gp->x_title=$tpl->_ENGINE_parse_body("{top_members}");
	$gp->pie();		
	$t=time();
	$html="
	
	<div style='font-size:16px;margin-bottom:5px'>$title</div>
	<center style='margin:10px'><img src='$targetedfile?$t'></center>
	<div id='table-websites-members-days'></div>
	
	<script>
		LoadAjax('table-websites-members-days','squid.traffic.table.days.php?day={$_GET["day"]}&table=members');
	</script>
	";
	echo $html;
	
	
}


function right_category(){
	$page=CurrentPageName();
	$tpl=new templates();		
	$q=new mysql_squid_builder();	
	$hour_table=date('Ymd',strtotime($_GET["day"]))."_hour";
	$sql="SELECT COUNT( sitename ) as thits , category FROM $hour_table GROUP BY category ORDER BY thits DESC LIMIT 0 , 10";

	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}	
	if(mysql_num_rows($results)==0){echo $tpl->_ENGINE_parse_body("$title<center style='margin:50px'><H2>{error_no_datas}</H2>$sql</center>");return;}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($ligne["category"]==null){$ligne["category"]=$tpl->javascript_parse_text("{unknown}");}
		$ydata[]=$ligne["category"];
		$xdata[]=$ligne["thits"];
	}
		
	$title=$tpl->_ENGINE_parse_body("{top_categories}/{websites}");
	$targetedfile="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".day.top.10.websites.$hour_table.category.png";
	$gp=new artica_graphs($targetedfile);	
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;	
	$gp->width=550;
	$gp->height=550;
	$gp->ViewValues=true;
	$gp->x_title=$tpl->_ENGINE_parse_body("{top_categories}");
	$gp->pie();		
	$t=time();
	$html="
	
	<div style='font-size:16px;margin-bottom:5px'>$title</div>
	<center style='margin:10px'><img src='$targetedfile?$t'></center>
	<div id='table-websites-categories-days'></div>
	
	<script>
		LoadAjax('table-websites-categories-days','squid.traffic.table.days.php?day={$_GET["day"]}');
	</script>
	";
	echo $html;
	
}


function today_zoom_popup(){
	
	$page=CurrentPageName();
	$tpl=new templates();	
	if(!isset($_GET["day"])){$_GET["day"]=$q->HIER();}	
	$t=time();
	
	
	$tpl=new templates();
	$array["website-zoom"]='{website}';
	$array["website-catz"]='{categories}';
	$array["today-zoom-popup-history"]='{history}:{today}';
	$array["today-zoom-popup-members"]='{members}:{today}';

	while (list ($num, $ligne) = each ($array) ){
		
				
		if($num=="website-zoom"){
			$html[]= "<li><a href=\"squid.website-zoom.php?&sitename={$_GET["familysite"]}\"><span>$ligne</span></a></li>\n";
			continue;
		}

		if($num=="website-catz"){
			$html[]= "<li><a href=\"squid.categorize.php?popup=yes&www={$_GET["familysite"]}&bykav=&day={$_GET["day"]}&group=&table-size=837&row-explain=609\"><span>$ligne</span></a></li>\n";
			continue;
		}			
		
		
		
		
		$html[]= "<li><a href=\"$page?$num=yes&day={$_GET["day"]}&type={$_GET["type"]}&familysite={$_GET["familysite"]}\"><span style='font-size:14px'>$ligne</span></a></li>\n";
	}
	
	$t=time();
	echo $tpl->_ENGINE_parse_body( "
	<div id=$t style='width:97%;font-size:14px;margin-left:10px;margin-right:-15px;margin-top:-5px'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#$t').tabs();
			
			
			});
		</script>");			
	
	
	
}

function today_zoom_popup_members(){
	$page=CurrentPageName();
	$tpl=new templates();		
	$q=new mysql_squid_builder();	
	$t=time();

	$type=$_GET["type"];
	$field_query="size";
	$field_query2="SUM(size)";	
	$table_field="{size}";
	$hour_table=date('Ymd',strtotime($_GET["day"]))."_hour";
	$member=$tpl->_ENGINE_parse_body("{member}");
	$sitename=$tpl->_ENGINE_parse_body("{website}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	
	$title="<div style='font-size:16px;width:100%;font-weight:bold'>{$_GET["familysite"]}:&nbsp;". strtolower(date('{l} d {F} Y',strtotime($_GET["day"])))." ({$GLOBALS["title_array"][$type]})</div>";
	if(!$q->TABLE_EXISTS($hour_table)){echo $tpl->_ENGINE_parse_body("
	$title
	<center style='margin:50px'>
		<H2>{$_GET["day"]} table:$hour_table</H2>
		<H2>{error_no_datas}$hour_table</H2>
	</center>");
	return;}
	
	
	if($type=="req"){
		$field_query="hits";
		$field_query2="SUM(hits)";
		$table_field="{hits}";	
	}
	$table_field=$tpl->_ENGINE_parse_body($table_field);
	$sql="SELECT SUM($field_query) as totalsize,hour as tdate,uid,MAC,client,familysite 
		FROM $hour_table GROUP BY uid,MAC,client,familysite  HAVING familysite='{$_GET["familysite"]}' ORDER BY hour";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}	
	if(mysql_num_rows($results)==0){echo $tpl->_ENGINE_parse_body("$title<center style='margin:50px'><H2>{error_no_datas}</H2>$sql</center>");return;}
	
	$nb_events=mysql_num_rows($results);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$xdata[]=$ligne["tdate"];
		if($_GET["type"]=="size"){
			$ydata[]=round(($ligne["totalsize"]/1024)/1000);}else{
		$ydata[]=$ligne["totalsize"];}
	}	
	$t=time();
	$targetedfile="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".day.$hour_table.{$_GET["familysite"]}.$type.members.png";
	$gp=new artica_graphs();
	$gp->width=750;
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
	
	$image="<center style='margin-top:10px' class=form><img src='$targetedfile?$t'></center>";
	if(!is_file($targetedfile)){writelogs("Fatal \"$targetedfile\" no such file!",__FUNCTION__,__FILE__,__LINE__);$image=null;}
	
	
$html="
$title$image
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?today-zoom-popup-member-list=yes&day={$_GET["day"]}&type={$_GET["type"]}&familysite={$_GET["familysite"]}',
	dataType: 'json',
	colModel : [
		{display: '$member', name : 'uid', width :394, sortable : true, align: 'left'},
		{display: 'MAC', name : 'MAC', width : 165, sortable : false, align: 'left'},
		{display: 'IP', name : 'client', width : 115, sortable : false, align: 'left'},
		{display: '$table_field', name : 'uri', width : 75, sortable : false, align: 'left'},
		

		],
	

	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: false,
	rp: 50,
	showTableToggleBtn: false,
	width: 828,
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

	$type=$_GET["type"];
	$field_query="size";
	$field_query2="SUM(size)";	
	$table_field="{size}";
	$hour_table=date('Ymd',strtotime($_GET["day"]))."_hour";
	$member=$tpl->_ENGINE_parse_body("{member}");
	$sitename=$tpl->_ENGINE_parse_body("{website}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	
	$title="<div style='font-size:16px;width:100%;font-weight:bold'>{$_GET["familysite"]}:&nbsp;". strtolower(date('{l} d {F} Y',strtotime($_GET["day"])))." ({$GLOBALS["title_array"][$type]})</div>";
	if(!$q->TABLE_EXISTS($hour_table)){echo $tpl->_ENGINE_parse_body("
	$title
	<center style='margin:50px'>
		<H2>{$_GET["day"]} table:$hour_table</H2>
		<H2>{error_no_datas}$hour_table</H2>
	</center>");
	return;}
	
	
	if($type=="req"){
		$field_query="hits";
		$field_query2="SUM(hits)";
		$table_field="{hits}";	
	}
	$table_field=$tpl->_ENGINE_parse_body($table_field);
	$sql="SELECT SUM($field_query) as totalsize,hour as tdate,familysite FROM $hour_table GROUP BY hour,familysite HAVING familysite='{$_GET["familysite"]}' ORDER BY hour";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}	
	if(mysql_num_rows($results)==0){echo $tpl->_ENGINE_parse_body("$title<center style='margin:50px'><H2>{error_no_datas}</H2>$sql</center>");return;}
	
	$nb_events=mysql_num_rows($results);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$xdata[]=$ligne["tdate"];
		if($_GET["type"]=="size"){
			$ydata[]=round(($ligne["totalsize"]/1024)/1000);}else{
		$ydata[]=$ligne["totalsize"];}
	}	
	$t=time();
	$targetedfile="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".day.$hour_table.{$_GET["familysite"]}.$type.png";
	$gp=new artica_graphs();
	$gp->width=750;
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
	
	$image="<center style='margin-top:10px' class=form><img src='$targetedfile?$t'></center>";
	if(!is_file($targetedfile)){writelogs("Fatal \"$targetedfile\" no such file!",__FUNCTION__,__FILE__,__LINE__);$image=null;}
	
	
$html="
$title$image
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?today-zoom-popup-history-list=yes&day={$_GET["day"]}&type={$_GET["type"]}&familysite={$_GET["familysite"]}',
	dataType: 'json',
	colModel : [
		{display: '$member', name : 'uid', width :53, sortable : true, align: 'left'},
		{display: 'MAC', name : 'MAC', width : 107, sortable : false, align: 'left'},
		{display: 'IP', name : 'client', width : 85, sortable : false, align: 'left'},
		{display: '$sitename', name : 'sitename', width : 326, sortable : false, align: 'left'},
		{display: '$table_field', name : 'uri', width : 56, sortable : false, align: 'left'},
		{display: '$category', name : 'category', width : 114, sortable : false, align: 'left'},
		

		],
	

	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: false,
	rp: 50,
	showTableToggleBtn: false,
	width: 828,
	height: 280,
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
	$type=$_GET["type"];
	$field_query="size";
	$field_query2="SUM(size)";	
	$table_field="{size}";
	$category=$tpl->_ENGINE_parse_body("{category}");
	$hour_table=date('Ymd',strtotime($_GET["day"]))."_hour";
	$member=$tpl->_ENGINE_parse_body("{member}");
	$sitename=$tpl->_ENGINE_parse_body("{website}");
	
	if($type=="req"){
		$field_query="hits";
		$field_query2="SUM(hits)";
		$table_field="{hits}";	
	}	
		
	
	$sql="SELECT $field_query2 as thits, uid,client,MAC,familysite FROM $hour_table 
	GROUP BY uid,client,MAC,familysite HAVING familysite='{$_GET["familysite"]}' ORDER BY thits DESC LIMIT 0,100";
	$results=$q->QUERY_SQL($sql);
	
	$data = array();
	$data['page'] = 0;
	$data['total'] = $total;
	$data['rows'] = array();	
	
	
	if(!$q->ok){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"$q->mysql_error", "",""));echo json_encode($data);return;}	
	if(mysql_num_rows($results)==0){array('id' => $ligne[time()],'cell' => array(null,"", "",""));echo json_encode($data);return;}
	
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
		
		$data['rows'][] = array(
			'id' => $id,
			'cell' => array(
			"<span $style>{$ligne["uid"]}</span>",
			"<span $style>{$ligne["MAC"]}</span>",
			"<span $style>{$ligne["client"]}</span>",
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
	$hour_table=date('Ymd',strtotime($_GET["day"]))."_hour";
	$member=$tpl->_ENGINE_parse_body("{member}");
	$sitename=$tpl->_ENGINE_parse_body("{website}");
	
	if($type=="req"){
		$field_query="hits";
		$field_query2="SUM(hits)";
		$table_field="{hits}";	
	}	
		
	
	$sql="SELECT $field_query2 as thits, uid,client,MAC,sitename,category,familysite FROM $hour_table 
	GROUP BY uid,client,sitename,MAC,category,familysite HAVING familysite='{$_GET["familysite"]}' ORDER BY thits DESC LIMIT 0,100";
	$results=$q->QUERY_SQL($sql);
	
	$data = array();
	$data['page'] = 0;
	$data['total'] = $total;
	$data['rows'] = array();	
	
	
	if(!$q->ok){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"$q->mysql_error", "",""));echo json_encode($data);return;}	
	if(mysql_num_rows($results)==0){array('id' => $ligne[time()],'cell' => array(null,"", "",""));echo json_encode($data);return;}
	
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


function page_de_garde(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$html="<table style='width:105%;margin-left:-15px;padding-right:-15px;margin-right:-15px'>
	<tbody>
	<tr>
		<td valign='top' width=1%>
			
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
	

	
	$hour_table=date('Ymd',strtotime($_GET["day"]))."_hour";
	if($GLOBALS["VERBOSE"]){echo "Table=$hour_table<br>\n";}
	
	$title="<div style='font-size:16px;width:100%;font-weight:bold'>{statistics}:&nbsp;". strtolower(date('{l} d {F} Y',strtotime($_GET["day"])))." ({$GLOBALS["title_array"][$type]})</div>";
	if(!$q->TABLE_EXISTS($hour_table)){echo $tpl->_ENGINE_parse_body("<input type='hidden' id='squid-stats-day-hide-type' value='{$_GET["type"]}'>$title<center style='margin:50px'><H2>{error_no_datas}</H2></center>");return;}
	
	
	if($type=="req"){$field_query="hits";}
	
	$sql="SELECT SUM($field_query) as totalsize,hour FROM $hour_table GROUP BY hour ORDER BY hour";
	if($GLOBALS["VERBOSE"]){echo "$sql<br>\n";}
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}	
	if($GLOBALS["VERBOSE"]){echo mysql_num_rows($results)." rows<br>\n";}
	
	if(mysql_num_rows($results)==0){
		echo $tpl->_ENGINE_parse_body("$title<center style='margin:50px'><H2>{error_no_datas}</H2></center>");
	}else{
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$xdata[]=$ligne["tdate"];
		if($_GET["type"]=="size"){$ydata[]=round(($ligne["totalsize"]/1024)/1000);}else{$ydata[]=$ligne["totalsize"];}
	}	
	
	$targetedfile="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".day.$hour_table.$type.png";
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
	if(!is_file($targetedfile)){writelogs("Fatal \"$targetedfile\" no such file!",__FUNCTION__,__FILE__,__LINE__);}	
	
	}
	$sql="SELECT SUM($field_query) as totalsize,familysite FROM $hour_table GROUP BY familysite ORDER BY totalsize DESC LIMIT 0,10";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}	
	if(mysql_num_rows($results)==0){echo $tpl->_ENGINE_parse_body("$title<center style='margin:50px'><H2>{error_no_datas}</H2></center>");return;}	
	
	$table="
	<input type='hidden' id='squid-stats-day-hide-type' value='{$_GET["type"]}'>
	<center>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:350px'>
<thead class='thead'>
	<tr>
	<th width=1%>{size}</th>
	<th>{website}</th>
	</tr>
</thead>
<tbody>";
	$xdata=array();
	$ydata=array();
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(trim($ligne["familysite"])==null){continue;}
		$ydata[]=$ligne["familysite"];
		if($_GET["type"]=="size"){$size=round(($ligne["totalsize"]/1024));
		$size_text=FormatBytes(($ligne["totalsize"]/1024));
		}else{$size=$ligne["totalsize"];$size_text=$size;}
		if($_GET["type"]=="size"){$xdata[]=round(($ligne["totalsize"]/1024));}else{$xdata[]=$ligne["totalsize"];}
		
	if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
$table=$table.
		"
		<tr class=$classtr>
			
			<td width=1%  style='font-size:14px' nowrap><strong>$size_text</strong></td>
			<td  style='font-size:14px' nowrap width=99%><strong><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$page?today-zoom=yes&type={$_GET["type"]}&familysite={$ligne["familysite"]}&day={$_GET["day"]}')\" style='font-size:14px;font-weight:bold;text-decoration:underline'>{$ligne["familysite"]}</a></strong></td>
		</tr>
		";		
		
	}	
$table=$table."</tbody></table>";
	$targetedfile2="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".day.top.10.websites.$hour_table.$type.png";
	$gp=new artica_graphs($targetedfile2);	
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;	
	$gp->width=550;
	$gp->height=550;
	$gp->ViewValues=true;
	$gp->x_title=$tpl->_ENGINE_parse_body("{top_websites}");
	$gp->pie();		
	
	$html="
	<input type='hidden' id='squid-stats-day-hide-type' value='$type'>
	$title
	<center style='margin:10px'><img src='$targetedfile'></center>
	<center style='margin:10px'><img src='$targetedfile2'></center>
	$table
	<script>
		LoadAjax('statistics-days-left-status','$page?statistics-days-left-status=yes&day={$_GET["day"]}');
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
		</table>
		";
		
	}
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

