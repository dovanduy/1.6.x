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
include_once(dirname(__FILE__)."/ressources/class.squid.report.inc");
if(!$_SESSION["AsWebStatisticsAdministrator"]){header("location:miniadm.index.php");die();}
	

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["messaging-right"])){messaging_right();exit;}
if(isset($_GET["webstats-middle"])){webstats_middle();exit;}
if(isset($_GET["graph"])){generate_graph();exit;}
if(isset($_GET["graph2"])){generate_graph2();exit;}



if(isset($_GET["webstats_middle_table"])){webstats_middle_table();exit;}
if(isset($_GET["items"])){webstats_middle_table_items();exit;}

if(isset($_POST["NoCategorizedAnalyze"])){NoCategorizedAnalyze();exit;}

main_page();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	
	if(!$_SESSION["CORP"]){
		$tpl=new templates();
		$onlycorpavailable=$tpl->javascript_parse_text("{onlycorpavailable}");
		$content=str_replace("{SCRIPT}", "<script>alert('$onlycorpavailable');document.location.href='miniadm.MembersTrack.php';</script>", $content);
		echo $content;	
		return;
	}	
	
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes&ID={$_GET["ID"]}')</script>", $content);
	echo $content;	
}
function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
	$ID=$_GET["ID"];
	if(!is_numeric($t)){$t=time();}
	$rp=new squid_report($ID);
		
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'>
			<a href=\"miniadm.index.php\">{myaccount}</a>
			&nbsp;&raquo;&nbsp;<a href=\"miniadm.webstats-start.php?t=$t\">{web_statistics}</a>
			&nbsp;&raquo;&nbsp;<a href=\"miniadm.MembersTrack.php\">{member_wwwtrack}</a>
		</div>
		<H1>$rp->report</H1>
		<p>". $rp->explain(). "</p>
	</div>	
	<div id='webstats-middle-$ff'></div>
	
	<script>
		LoadAjax('webstats-middle-$ff','$page?webstats-middle=yes&t=$t&ID=$ID');
		$jsadd
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function webstats_middle(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
	
	$html="
	<div class=BodyContent id='graph-$ff'></div>
	<div class=BodyContent id='graph2-$ff'></div>
	<div class=BodyContent id='table-$ff'></div>
	
	
	<script>
		LoadAjax('graph-$ff','$page?graph=yes&t=$t&ID=$ID');
		LoadAjax('graph2-$ff','$page?graph2=yes&t=$t&ID=$ID');
		LoadAjax('table-$ff','$page?webstats_middle_table=yes&t=$t&ID=$ID');
	</script>
	";
	
	echo $html;
	
	
}


function webstats_middle_table(){
	
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=500;
	$TB_WIDTH=910;
	$uid=$_GET["uid"];
	$ID=$_GET["ID"];
	$tablename="WebTrackMem{$ID}";		
	$t=time();
	$sitename=$tpl->javascript_parse_text("{sitename}");
	$imapserv=$tpl->_ENGINE_parse_body("{imap_server}");
	$account=$tpl->_ENGINE_parse_body("{account}");
//	$title=$tpl->_ENGINE_parse_body("$attachments_storage {items}:&nbsp;&laquo;$size&raquo;");
	$filessize=$tpl->_ENGINE_parse_body("{filesize}");
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
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	$titletable=$tpl->_ENGINE_parse_body("{visited_websites}");
	
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
	url: '$page?items=yes&t=$t&ID=$ID',
	dataType: 'json',
	colModel : [
		{display: '$sitename', name : 'sitename', width :253, sortable : true, align: 'left'},
		{display: '$familysite', name : 'familysite', width :185, sortable : true, align: 'left'},
		{display: '$category', name : 'category', width :185, sortable : true, align: 'left'},		
		{display: '$size', name : 'size', width :107, sortable : true, align: 'left'},
		{display: '$hits', name : 'hits', width :88, sortable : true, align: 'left'},
		
	
	],
	$buttons

	searchitems : [
		{display: '$sitename', name : 'sitename'},
		{display: '$familysite', name : 'familysite'},
		{display: '$category', name : 'category'},
		

	],
	sortname: 'size',
	sortorder: 'desc',
	usepager: true,
	title: '<span id=\"title-$t\">$titletable</span>',
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
	s_PopUpFull('http://proxy-appliance.org/index.php?cID=332','1024','900');
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
	$ID=$_GET["ID"];
	$tablename="WebTrackMem{$ID}";		
	
	$search='%';
	$table="(SELECT SUM(hits) as hits, SUM(size) as size,sitename,familysite,category FROM $tablename GROUP BY sitename,familysite,category) as t ";
	
	$page=1;
	$FORCE_FILTER=null;
	
	
	if(!$q->TABLE_EXISTS($tablename, $database)){json_error_show("$table doesn't exists...");}
	if($q->COUNT_ROWS($tablename, $database)==0){json_error_show("No rules");}

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
	
	$urljsSIT="<a href=\"javascript:blur();\" 
	OnClick=\"javascript:Loadjs('miniadm.MembersTrack.sitename.php?ID=$ID&sitename={$ligne["sitename"]}');\"
	style='font-size:14px;text-decoration:underline;color:$color'>";
	
	$urljsCAT="<a href=\"javascript:blur();\" 
	OnClick=\"javascript:Loadjs('miniadm.MembersTrack.category.php?ID=$ID&category=".urlencode($ligne["category"])."');\"
	style='font-size:14px;text-decoration:underline;color:$color'>";	
	
	
	
	$ligne["hits"]=numberFormat($ligne["hits"],0,""," ");
	$ligne["size"]=FormatBytes($ligne["size"]/1024);
	$ligne["familysite"]=$q->GetFamilySites($ligne["sitename"]);
	$data['rows'][] = array(
		'id' => "$zmd5",
		'cell' => array(
			"<span style='font-size:14px;color:$color'>$urljsSIT{$ligne["sitename"]}</a></span>",
			"<span style='font-size:14px;color:$color'>$urljsFAM{$ligne["familysite"]}</a></span>",
			"<span style='font-size:14px;color:$color'>$urljsCAT{$ligne["category"]}</a></span>",
			"<span style='font-size:14px;color:$color'>$urljs{$ligne["size"]}</span>",
			"<span style='font-size:14px;color:$color'>{$ligne["hits"]}</span>",
			)
		);
	}
	
	
echo json_encode($data);		
}
function generate_graph2(){
	include_once('ressources/class.artica.graphs.inc');
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
	$graph1=null;
	$graph2=null;
	$ID=$_GET["ID"];
	$tablename="WebTrackMem{$ID}";
	$sql="SELECT SUM(hits) as thits, category FROM `$tablename` GROUP BY category ORDER BY thits DESC LIMIT 0,10";
	$unknown=$tpl->_ENGINE_parse_body("{unknown}");
	$c=0;
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}	
	if(mysql_num_rows($results)>0){
	

		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(trim($ligne["category"])==null){$ligne["category"]=$unknown;}
			$ydata[]=$ligne["category"];
			$xdata[]=$ligne["thits"];
			$c++;
		}
		$targetedfile="ressources/logs/".md5(basename(__FILE__).".".__FUNCTION__.__LINE__.".".time()).".png";
		$gp=new artica_graphs($targetedfile);	
		$gp->xdata=$xdata;
		$gp->ydata=$ydata;	
		$gp->width=460;
		$gp->height=500;
		$gp->ViewValues=true;
		$gp->PieLegendHide=false;
		$gp->x_title=$tpl->_ENGINE_parse_body("{top_categories_by_hits}");
		$gp->pie(true);		
		if(is_file("$targetedfile")){$graph1="
		<center style='font-size:18px;margin:10px'>{$gp->x_title}</center>
		<img src='$targetedfile'>";}
	
	}
	$xdata=array();$ydata=array();
	$sql="SELECT SUM(size) as thits, category FROM `$tablename` GROUP BY category ORDER BY thits DESC LIMIT 0,10";
	$unknown=$tpl->_ENGINE_parse_body("{unknown}");
	$c=0;
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}	
	if(mysql_num_rows($results)>0){

		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(trim($ligne["category"])==null){$ligne["category"]=$unknown;}
			$ligne["thits"]=round($ligne["thits"]/1024)/1000;
			$ydata[]=$ligne["category"];
			$xdata[]=$ligne["thits"];
			$c++;
		}
		
		$targetedfile="ressources/logs/".md5(basename(__FILE__).".".__FUNCTION__.__LINE__.".".time()).".png";
		$gp=new artica_graphs($targetedfile);	
		$gp->xdata=$xdata;
		$gp->ydata=$ydata;	
		$gp->width=460;
		$gp->height=500;
		$gp->ViewValues=true;
		$gp->PieLegendHide=false;
		$gp->x_title=$tpl->_ENGINE_parse_body("{top_categories_by_size}");
		$gp->pie();		
		if(is_file("$targetedfile")){$graph2="
		<center style='font-size:18px;margin:10px'>{$gp->x_title}<br>MB</center>
		<img src='$targetedfile'>";}
	
	}

	
	$html="<table style='width:100%'>
	<tr>
		<td width=50%>$graph1</td>
		<td width=50%>$graph2</td>
	</tr>
	</table>";
	echo $html;
	
}


function generate_graph(){
	include_once('ressources/class.artica.graphs.inc');
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
	$ID=$_GET["ID"];
	$tablename="WebTrackMem{$ID}";
	$sql="SELECT SUM(hits) as thits, zDate FROM $tablename GROUP BY zDate ORDER BY zDate";
	
	
	
	$c=0;
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}	
	if(mysql_num_rows($results)>0){
	
			$nb_events=mysql_num_rows($results);
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$xdata[]=$ligne["hour"];
				$ydata[]=$ligne["thits"];
				
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
			$gp->x_title=$tpl->_ENGINE_parse_body("{days}");
			$gp->title=null;
			$gp->margin0=true;
			$gp->Fillcolor="blue@0.9";
			$gp->color="146497";
			$gp->line_green();
			
		if(is_file($targetedfile)){
			echo "<center>
			<div style='font-size:18px;margin-bottom:10px'>".$tpl->_ENGINE_parse_body("{hits}/{day}")."</div>
			<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('miniadm.MembersTrack.cronozoom.php?ID=$ID')\">
			<img src='$targetedfile'></a></center>";
		}
	
	}
	
}

