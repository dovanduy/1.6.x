<?php
session_start();

ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.calendar.inc");
if(!$_SESSION["AsWebStatisticsAdministrator"]){header("location:miniadm.index.php");die();}
	

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["messaging-right"])){messaging_right();exit;}
if(isset($_GET["webstats-middle"])){webstats_middle();exit;}
if(isset($_GET["graph"])){generate_graph();exit;}
if(isset($_GET["webstats_middle_table"])){webstats_middle_table();exit;}
if(isset($_GET["items"])){webstats_middle_table_items();exit;}

if(isset($_POST["NoCategorizedAnalyze"])){NoCategorizedAnalyze();exit;}

main_page();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&tablename={$_GET["tablename"]}&xtime={$_GET["xtime"]}')</script>", $content);
	echo $content;	
}
function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$t=$_GET["t"];
	$ff=time();
	$tablename=$_GET["tablename"];
	$xtime=$_GET["xtime"];
	$q=new mysql_squid_builder();
	$dansguardian_events="dansguardian_events_".date("Ymd",$xtime);
	
	$sql="SELECT totalBlocked,MembersCount,requests,totalsize,not_categorized,YouTubeHits FROM tables_day WHERE tablename='$dansguardian_events'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));	
	$NotCategorized=$ligne["not_categorized"];
	$SumSize=$ligne["totalsize"];
	$SumHits=$ligne["requests"];
	$MembersCount=$ligne["MembersCount"];
	$YouTubeHits=$ligne["YouTubeHits"];
	$SumSize=FormatBytes($SumSize/1024);
	$SumHits=numberFormat($SumHits,0," "," ");
	$H1[]="$MembersCount {members} {$SumSize}/$SumHits {hits}";	
	
		$dateT=date("{l} {F} d",$_GET["xtime"]);
		if($tpl->language=="fr"){
			$dateT=date("{l} d {F} ",$_GET["xtime"]);
		}
		
	if(isset($_GET["xtime"])){
		$_GET["year"]=date("Y",$_GET["xtime"]);
		$_GET["month"]=date("m",$_GET["xtime"]);
		$_GET["day"]=date("d",$_GET["xtime"]);	
	}
	
	$js_uid="LoadAjax('webstats-middle-$ff','$page?webstats-middle=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&tablename={$_GET["tablename"]}&xtime={$_GET["xtime"]}&FILTER=uid');";
	$js_ipaddr="LoadAjax('webstats-middle-$ff','$page?webstats-middle=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&tablename={$_GET["tablename"]}&xtime={$_GET["xtime"]}&FILTER=ipaddr');";
	$js_MAC="LoadAjax('webstats-middle-$ff','$page?webstats-middle=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&tablename={$_GET["tablename"]}&xtime={$_GET["xtime"]}&FILTER=MAC');";
	$js_hostname="LoadAjax('webstats-middle-$ff','$page?webstats-middle=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&tablename={$_GET["tablename"]}&xtime={$_GET["xtime"]}&FILTER=hostname');";
	
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'>
			<a href=\"miniadm.index.php\">{myaccount}</a>
			&nbsp;&raquo;&nbsp;<a href=\"miniadm.webstats.php?t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}\">{web_statistics}</a>
		</div>
		<H1>". @implode(", ", $H1)."</H1>
		<p>$dateT: {display_members_for_this_day}</p>
	</div>
	<div style='font-size:16px;' class=BodyContent>
	<center>
		<a href=\"javascript:$js_uid\" style='font-size:16px;text-decoration:underline'>{members}</a>&nbsp;|&nbsp;
		<a href=\"javascript:$js_ipaddr\" style='font-size:16px;text-decoration:underline'>{ipaddr}</a>&nbsp;|&nbsp;
		<a href=\"javascript:$js_MAC\" style='font-size:16px;text-decoration:underline'>{MAC}</a>&nbsp;|&nbsp;
		<a href=\"javascript:$js_hostname\" style='font-size:16px;text-decoration:underline'>{hostname}</a>
	</center>
	</div>	
	<div id='webstats-middle-$ff'></div>
	
	<script>
		LoadAjax('webstats-middle-$ff','$page?webstats-middle=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&tablename={$_GET["tablename"]}&xtime={$_GET["xtime"]}');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function webstats_middle(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
	
	$html="
	<div class=BodyContent id='graph-$ff'></div>
	<div class=BodyContent id='table-$ff'></div>
	
	
	<script>
		LoadAjax('graph-$ff','$page?graph=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&tablename={$_GET["tablename"]}&xtime={$_GET["xtime"]}&FILTER={$_GET["FILTER"]}');
		LoadAjax('table-$ff','$page?webstats_middle_table=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&tablename={$_GET["tablename"]}&xtime={$_GET["xtime"]}&FILTER={$_GET["FILTER"]}');
	</script>
	";
	
	echo $html;
	
	
}


function which_filter($tablename){
	$q=new mysql_squid_builder();
	$sql="SELECT uid FROM `$tablename` GROUP BY uid HAVING LENGTH(uid)>0";
	$results=$q->QUERY_SQL($sql);
	$count=mysql_num_rows($results);
	if($count>1){return "uid";}
	
	$sql="SELECT MAC FROM `$tablename` GROUP BY MAC HAVING LENGTH(MAC)>0";
	$results=$q->QUERY_SQL($sql);
	$count=mysql_num_rows($results);
	if($count>1){return "MAC";}
	
	
	$sql="SELECT COUNT(ipaddr) as tcount FROM $tablename WHERE LENGTH(ipaddr)>0";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$count=mysql_num_rows($results);
	if($count>1){return "ipaddr";}
	
	$sql="SELECT COUNT(hostname) as tcount FROM $tablename WHERE LENGTH(hostname)>0";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$count=mysql_num_rows($results);
	if($count>1){return "hostname";}
	
}


function webstats_middle_table(){
	
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=500;
	$TB_WIDTH=910;
	$uid=$_GET["uid"];
		
	$t=time();
	$sitename=$tpl->javascript_parse_text("{sitename}");
	$client=$tpl->_ENGINE_parse_body("{ipaddr}");
	$account=$tpl->_ENGINE_parse_body("{account}");
//	$title=$tpl->_ENGINE_parse_body("$attachments_storage {items}:&nbsp;&laquo;$size&raquo;");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$action_delete_rule=$tpl->javascript_parse_text("{action_delete_rule}");
	$enable=$tpl->_ENGINE_parse_body("{enable}");
	$compile_rules=$tpl->_ENGINE_parse_body("{compile_rules}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$error_want_operation=$tpl->javascript_parse_text("{error_want_operation}");
	$events=$tpl->javascript_parse_text("{events}");
	$category=$tpl->javascript_parse_text("{category}");
	$title=$tpl->javascript_parse_text("{video_title}");
	$size=$tpl->javascript_parse_text("{size}");
	$duration=$tpl->javascript_parse_text("{duration}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$familysite=$tpl->javascript_parse_text("{familysite}");
	$MAC=$tpl->_ENGINE_parse_body("{MAC}");
	
	$tablename_members="UserSizeD_".date("Ymd",$_GET["xtime"]);
	if($_GET["FILTER"]==null){$_GET["FILTER"]=which_filter($tablename_members);}	
	$FILTER=$tpl->_ENGINE_parse_body("{{$_GET["FILTER"]}}");
	
	$buttons="
	buttons : [
	
	{name: '$online_help', bclass: 'Help', onpress : ItemHelp$t},
	
	],	";
	$html="
	
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?items=yes&t=$t&uid=$uid&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&tablename={$_GET["tablename"]}&xtime={$_GET["xtime"]}&FILTER={$_GET["FILTER"]}',
	dataType: 'json',
	colModel : [
		{display: '$FILTER', name : '{$_GET["FILTER"]}', width :538, sortable : true, align: 'left'},
		{display: '$hits', name : 'hits', width :149, sortable : true, align: 'right'},
		{display: '$size', name : 'size', width :149, sortable : true, align: 'right'},
		
		
	
	],
	$buttons

	searchitems : [
		{display: '$FILTER', name : '{$_GET["FILTER"]}'},

		

	],
	sortname: 'size',
	sortorder: 'desc',
	usepager: true,
	title: '<span id=\"title-$t\"></span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

function ItemHelp$t(){
	//s_PopUpFull('http://www.mail-appliance.org/index.php?cID=339','1024','900');
}


</script>";
	
	echo $html;
	
	
	
}	
	
function webstats_middle_table_items(){
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$users=new usersMenus();
	$sock=new sockets();
	$xtime=$_GET["xtime"];
	$database="squidlogs";
	$tablename_members="UserSizeD_".date("Ymd",$xtime);
	if($_GET["FILTER"]==null){$_GET["FILTER"]=which_filter($tablename_members);}
	
	
	$subtable="( SELECT {$_GET["FILTER"]},SUM(size) as size, SUM(hits) as hits FROM `$tablename_members` 
	GROUP BY {$_GET["FILTER"]} HAVING LENGTH({$_GET["FILTER"]})>0) as t";
	$search='%';
	$table=$subtable;
	
	$page=1;
	$FORCE_FILTER=null;
	$dansguardian_events=date("Ymd",$xtime)."_hour";
	
	if(!$q->TABLE_EXISTS($tablename_members, $database)){json_error_show("$table doesn't exists...");}
	if($q->COUNT_ROWS($tablename_members, $database)==0){json_error_show("No data");}

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);}	

	while ($ligne = mysql_fetch_assoc($results)) {
	$zmd5=md5(serialize($ligne));
	$color="black";
	
	
	
	//familysite 	size 	hits
	$colorsize="black";
	$sizeorg=$ligne["size"];
	
	$ligne["hits"]=numberFormat($ligne["hits"],0,""," ");
	$ligne["size"]=FormatBytes($ligne["size"]/1024);
	
	if($sizeorg>102400000){
		$colorsize="#4E0000";
	}
	
	if($sizeorg>512000000){
		$colorsize="#9A0000";
	}
	

	
	// https://192.168.1.106:9000/
	$urljs="<a href=\"javascript:blur();\" 
	OnClick=\"javascript:Loadjs('squid.members.zoom.php?table=$dansguardian_events&field={$_GET["FILTER"]}&value={$ligne["{$_GET["FILTER"]}"]}')\"
	style='font-size:18px;text-decoration:underline;color:$color'>";		
	
	
	
	$data['rows'][] = array(
		'id' => "$zmd5",
		'cell' => array(
			"<span style='font-size:18px;color:$color'>$urljs{$ligne["{$_GET["FILTER"]}"]}</a></span>",
			"<span style='font-size:18px;color:$color'>{$ligne["hits"]}</span>",
			"<span style='font-size:18px;color:$colorsize'>{$ligne["size"]}</span>",
			)
		);
	}
	
	
echo json_encode($data);		
	
	
}



function generate_graph(){
	include_once('ressources/class.artica.graphs.inc');
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
	$xtime=$_GET["xtime"];
	$tablename="UserSizeD_".date("Ymd",$_GET["xtime"]);
	if($_GET["FILTER"]==null){$_GET["FILTER"]=which_filter($tablename);}
	$FILTER=$tpl->_ENGINE_parse_body("{{$_GET["FILTER"]}}");	
	
	
	
	
	$sql="SELECT COUNT({$_GET["FILTER"]}) as tcount, hour FROM $tablename GROUP BY hour ORDER BY hour";
	
	

	$c=0;
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}	
	if(mysql_num_rows($results)>0){
	
			$nb_events=mysql_num_rows($results);
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$xdata[]=$ligne["hour"];
				$ydata[]=$ligne["tcount"];
				
			$c++;
		
	}	
				
				
				
			$t=time();
			$targetedfile="ressources/logs/".md5(basename(__FILE__).".".__FUNCTION__.".day.$tablename").".png";
			$gp=new artica_graphs();
			$gp->width=920;
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
			
		if(is_file($targetedfile)){
			echo "<center>
			<div style='font-size:18px;margin-bottom:10px'>".$tpl->_ENGINE_parse_body("$FILTER/{hours}")."</div>
			<img src='$targetedfile'></center>";
		}
	
	}
	
}

