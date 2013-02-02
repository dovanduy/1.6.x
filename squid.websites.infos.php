<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.rtmm.tools.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.artica.graphs.inc');
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	$user=new usersMenus();
	if(!$user->AsWebStatisticsAdministrator){
		$tpl=new templates();
		echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		exit;
		
	}
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["visited"])){visited();exit;}
	if(isset($_GET["visited-list"])){visited_list();exit;}
	
	
	if(isset($_GET["events"])){logsuris();exit;}
	if(isset($_GET["events-day"])){logsuris_day();exit;}
	if(isset($_GET["events-week"])){logsuris_week();exit;}
	
js();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{website}:{$_GET["www"]}");
	$html="YahooWin6('650','$page?tabs=yes&www={$_GET["www"]}&day={$_GET["day"]}&year={$_GET["year"]}&week={$_GET["week"]}','$title');";
	echo $html;
	
}
function tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	if(!is_numeric($_GET["year"])){$_GET["year"]=date("Y");}
	if($_GET["day"]<>null){
		$array["events-day"]="{events}:{day} ({$_GET["day"]})";
	}
	
	if(is_numeric($_GET["week"])){
		$array["events-week"]='{events}:{week}';
	}		
	$array["visited"]='{visited_websites}';

	

	
	while (list ($num, $ligne) = each ($array) ){
				
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&day={$_GET["day"]}&year={$_GET["year"]}&week={$_GET["week"]}&www={$_GET["www"]}\"><span>$ligne</span></a></li>\n");
	}
	
	
	echo "
	<div id=main_config_loopewebs style='width:100%;height:550px;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_loopewebs').tabs();
			
			
			});
		</script>";			
}

function visited(){
	$tpl=new templates();
	$TB_WIDTH=600;
	$page=CurrentPageName();
	
	
	$website=$tpl->_ENGINE_parse_body("{website}");
	$category=$tpl->_ENGINE_parse_body("{category}");	
	$delete=$tpl->_ENGINE_parse_body("{delete}");	
	
	$add=$tpl->_ENGINE_parse_body("{add}:{extension}");
	$addDef=$tpl->_ENGINE_parse_body("{add}:{default}");
	$t=time();
echo "
<span id='FlexReloadWebsiteInfosTablePointer'></span>
	<table class='$t' style='display: none' id='$t' style='width:99%'></table>
<script>

$(document).ready(function(){
$('#$t').flexigrid({
	url: '$page?visited-list=yes&www={$_GET["www"]}&bykav={$_GET["bykav"]}&day={$_GET["day"]}&group={$_GET["group"]}',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'none1', width :48, sortable : false, align: 'left'},
		{display: '$website', name : 'none', width :213, sortable : false, align: 'left'},
		{display: '$category', name : 'category', width : 228, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'description', width : 50, sortable : false, align: 'left'},
		
		
	],
	
	sortname: 'sitename',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: 350,
	singleSelect: true
	
	});   
});

	function FlexReloadWebsiteInfosTable(){
		$('#flexRT$t').flexReload();
	}


</script>
";		

	
}

function visited_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$family=$q->GetFamilySites($_GET["www"]);
	
	$search='%';
	$table="visited_sites";
	$page=1;
	$COUNT_ROWS=$q->COUNT_ROWS($table);
	$FORCE_FILTER="familysite='$family'";
	if($COUNT_ROWS==0){json_error_show("No data...");}
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	$_POST["query"]=trim($_POST["query"]);
	if($_POST["query"]<>null){
		$_POST["query"]="*{$_POST["query"]}*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE_FILTER $searchstring";
		writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE_FILTER";
		writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];		
		
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		
		$categories=$q->GET_CATEGORIES($ligne['sitename']);
		$FamilySite=$q->GetFamilySites($ligne['sitename']);
		$categorize=imgtootltip("add-database-32.png",$ligne['sitename'],"javascript:Loadjs('squid.categorize.php?www={$ligne['sitename']}&day={$_GET["day"]}&week={$_GET["week"]}&month={$_GET["month"]}');");
		$thumbs=$q->GET_THUMBNAIL($ligne['sitename'], 48);
		$ahref="Loadjs('squid.traffic.statistics.days.php?today-zoom=yes&type=req&familysite=$FamilySite&day={$_GET["day"]}')";
		
	$data['rows'][] = array(
		'id' => $ligne['sitename'],
		'cell' => array(
		$thumbs,
		"<div style='margin-top:10px'><a href=\"javascript:blur();\" OnClick=\"javascript:$ahref\" style='font-size:14px;text-decoration:underline'>{$ligne['sitename']}</a></div>",
		"<div style='font-size:14px;margin-top:10px'>$categories</span>",$categorize)
		);
	}
	
	
echo json_encode($data);
	
	
	
}

function logsuris_week(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$q=new mysql_squid_builder();	
	$week=$_GET["week"];
	$year=$_GET["year"];
	if(!is_numeric($year)){$year=date('Y');}
	$table="{$year}{$week}_week";
	
	$title=$q->WEEK_TITLE($week, $year);
	$time=$q->WEEK_TIME_FROM_TABLENAME($table);
	
	$month=date("m",$time);
	
	$sql="SELECT sitename,`day` ,SUM(hits) AS hits,SUM(size) as size FROM 	$table 
	GROUP BY sitename,`day` HAVING sitename='{$_GET["www"]}' ORDER BY `day`";
	$results=$q->QUERY_SQL($sql);
	if(mysql_num_rows($results)>1){
		if(!$q->ok){echo $q->mysql_error;}
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$xdata[]=$ligne["day"];
			$ydata[]=$ligne["hits"];
		}	
		
		$targetedfile="ressources/logs/".md5(basename(__FILE__).".".__FUNCTION__).".{$_GET["www"]}.hits.png";
		$gp=new artica_graphs();
		$gp->width=550;
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
		
		$image="<center style='margin-top:5px'><img src='$targetedfile'></center>";	
		
	}else{
		$ligne=mysql_fetch_array($results);	
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
		if(strlen($ligne["day"])==1){$ligne["day"]="0{$ligne["day"]}";}
		$image="<div style='font-size:14px;margin:5px'>{$ligne["day"]}, {$ligne["hits"]} {requests} {$ligne["size"]}</div>";
	}
	
	
	
	
	$html="
	
$image
<div style='font-size:14px'>$title</div>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
	<th>{date}&nbsp;</th>
	<th>{client}</th>
	<th>{sitename}</th>
	<th>{hits}</th>
	<th>{size}</th>
	</tr>
</thead>
<tbody class='tbody'>";	
	$sql="SELECT client,hostname,MAC,uid,sitename,`day` as zDate,`size` as QuerySize,hits  FROM $table WHERE sitename='{$_GET["www"]}' ORDER BY `day` asc";
	$results2=$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo "<hr>$q->mysql_error<hr>";
	}
	
	$HASH_DAYS=$q->WEEK_TOTIMEHASH_FROM_TABLENAME($table);
	while($ligne2=mysql_fetch_array($results2,MYSQL_ASSOC)){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$QuerySize=$ligne2["QuerySize"]/1024;
		$QuerySize=FormatBytes($QuerySize);
		$timeQ=$HASH_DAYS[$ligne2["zDate"]];
		$table_zoom="dansguardian_events_". date("Ymd",$timeQ);		
		$dateT=date("{l} {F} d",$timeQ);
		if($tpl->language=="fr"){$dateT=date("{l} d {F} ",$timeQ);}
		$user=array();
		if($q->TABLE_EXISTS("$table_zoom")){
			
			$urlDEF="squid.dansguardian_events.php?table=$table_zoom&sitename={$ligne2["sitename"]}";
		
			if($ligne2["MAC"]<>null){
				$url="squid.dansguardian_events.php?table=$table_zoom&field=MAC&value={$ligne2["MAC"]}&sitename={$ligne2["sitename"]}";
				$user[]="<a href=\"javascript:blur();\" Onclick=\"javascript:Loadjs('$url');\" style='font-size:12px;text-decoration:underline'>{$ligne2["MAC"]}</a>";
			}
	
			if($ligne2["client"]<>null){
				$url="squid.dansguardian_events.php?table=$table_zoom&field=CLIENT&value={$ligne2["client"]}&sitename={$ligne2["sitename"]}";
				$user[]="<a href=\"javascript:blur();\" Onclick=\"javascript:Loadjs('$url');\" style='font-size:12px;text-decoration:underline'>{$ligne2["client"]}</a>";
			}
	
			if($ligne2["uid"]<>null){
				$url="squid.dansguardian_events.php?table=$table_zoom&field=uid&value={$ligne2["uid"]}&sitename={$ligne2["sitename"]}";
				$user[]="<a href=\"javascript:blur();\" Onclick=\"javascript:Loadjs('$url');\" style='font-size:12px;text-decoration:underline'>{$ligne2["uid"]}</a>";
			}
	
			if($ligne2["hostname"]<>null){
				$url="squid.dansguardian_events.php?table=$table_zoom&field=hostname&value={$ligne2["hostname"]}&sitename={$ligne2["sitename"]}";
				$user[]="<a href=\"javascript:blur();\" Onclick=\"javascript:Loadjs('$url');\" style='font-size:12px;text-decoration:underline'>{$ligne2["hostname"]}</a>";
			}		
		
		}else{
			$user[]="$table_zoom no such table";
		}
		
		$html=$html."
			<tr class=$classtr>
				
				<td width=1% nowrap valign='top'><strong style='font-size:12px'>$dateT</td>
				<td width=1% valign='top'><strong style='font-size:12px'>". @implode("<br>", $user)."</td>
				<td width=99% valign='top' align='left nowrap'>
				
				<a href=\"javascript:blur();\" Onclick=\"javascript:Loadjs('$urlDEF');\" 
				style='font-size:12px;text-decoration:underline;font-weight:bold'>{$ligne2["sitename"]}</a></td>
				<td width=1% valign='top'><strong style='font-size:12px'>{$ligne2["hits"]}</td>
				<td width=1% valign='top'><strong style='font-size:12px'>$QuerySize</td>
			</tr>
			";		
				
			}
			

	$html=$html."</tbody></table>";
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}



function logsuris_day(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$q=new mysql_squid_builder();	
	$time=strtotime("{$_GET["day"]} 00:00:00");
	$table="dansguardian_events_".date('Ymd',$time);
	$table_hour=date('Ymd',$time)."_hour";
	
	$sql="SELECT sitename,hour ,SUM(hits) AS hits,SUM(size) as size FROM $table_hour GROUP BY sitename,hour HAVING sitename='{$_GET["www"]}' ORDER BY Hour";
	$results=$q->QUERY_SQL($sql);
	if(mysql_num_rows($results)>1){
		if(!$q->ok){echo $q->mysql_error;}
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$xdata[]=$ligne["hour"];
			$ydata[]=$ligne["hits"];
		}	
		
		$targetedfile="ressources/logs/".md5(basename(__FILE__).".".__FUNCTION__).".{$_GET["www"]}.hits.png";
		$gp=new artica_graphs();
		$gp->width=550;
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
		
		$image="<center style='margin-top:5px'><img src='$targetedfile'></center>";	
		
	}else{
		$ligne=mysql_fetch_array($results);	
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
		if(strlen($ligne["hour"])==1){$ligne["hour"]="0{$ligne["hour"]}";}
		$image="<div style='font-size:14px;margin:5px'>{$ligne["hour"]}h, {$ligne["hits"]} {requests} {$ligne["size"]}</div>";
	}
	
	
	
	
	$html="
	
$image
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
	
	<th>{date}&nbsp;</th>
	<th>{client}</th>
	<th>{url}</th>
	<th>{size}</th>
	</tr>
</thead>
<tbody class='tbody'>";	
	$sql="SELECT CLIENT,hostname,uid,uri,DATE_FORMAT(zDate,'%Hh%i') as zDate,QuerySize  FROM $table WHERE sitename='{$_GET["www"]}' ORDER BY zDate DESC LIMIT 0,50";
	$results2=$q->QUERY_SQL($sql);
	while($ligne2=mysql_fetch_array($results2,MYSQL_ASSOC)){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$QuerySize=$ligne2["QuerySize"]/1024;
		$QuerySize=FormatBytes($QuerySize);
		$html=$html."
			<tr class=$classtr>
				<td width=1% nowrap><strong style='font-size:12px'>{$ligne2["zDate"]}</td>
				<td width=1%><strong style='font-size:12px'>{$ligne2["CLIENT"]}</td>
				<td width=99% valign='middle' align='left nowrap'><strong style='font-size:12px'>{$ligne2["uri"]}</strong></td>
				<td width=1%><strong style='font-size:12px'>$QuerySize</td>
			</tr>
			";		
				
			}
			

	$html=$html."</tbody></table>";
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function logsuris(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$q=new mysql_squid_builder();	
	$time=strtotime("{$_GET["day"]} 00:00:00");
	$week=date('W',$time);
	$sql="SELECT tablename, zDate FROM tables_day WHERE WEEK(zDate)='$week' ORDER BY zDate DESC";
	
	$html="
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
	<th>{date}&nbsp;</th>
	<th>{client}</th>
	<th>{url}</th>
	<th>{hits}</th>
	</tr>
</thead>
<tbody class='tbody'>";	

	$results=$q->QUERY_SQL($sql);
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			
			$sql="SELECT CLIENT,hostname,uid,uri,DATE_FORMAT(zDate,'%d-%m %Hh') as zDate,hits FROM {$ligne["tablename"]} WHERE sitename='{$_GET["www"]}' ORDER BY hits DESC LIMIT 0,50";
			$results2=$q->QUERY_SQL($sql);
			while($ligne2=mysql_fetch_array($results2,MYSQL_ASSOC)){
				if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
				$html=$html."
				<tr class=$classtr>
				<td width=1% nowrap><strong style='font-size:12px'>{$ligne2["zDate"]}</td>
				<td width=1%><strong style='font-size:12px'>{$ligne2["CLIENT"]}</td>
				<td width=99% valign='middle' align='left nowrap'><strong style='font-size:12px'>{$ligne2["uri"]}</strong></td>
				<td width=1%><strong style='font-size:12px'>{$ligne2["hits"]}</td>
			</tr>
			";		
				
			}
			
	}
	$html=$html."</tbody></table>";
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}


