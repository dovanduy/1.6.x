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
$users=new usersMenus();if(!$users->AsWebStatisticsAdministrator){die();}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["video"])){video();exit;}
if(isset($_GET["who"])){who();exit;}
if(isset($_GET["who-items"])){who_items();exit;}
js();



function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	if(!$_SESSION["CORP"]){

		$onlycorpavailable=$tpl->javascript_parse_text("{onlycorpavailable}");
		echo "alert('$onlycorpavailable')";
		return;
	}	
	
	$youtubeid=$_GET["youtubeid"];
	
	$sql="SELECT title FROM youtube_objects WHERE youtubeid='$youtubeid'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));	
	//$title=utf8_encode($ligne["title"]);
	$title=str_replace("'", "`", $ligne["title"]);
	echo "YahooWin('750','$page?tabs=yes&youtubeid=$youtubeid&xtime={$_GET["xtime"]}','$title')";
}

function tabs(){
	$youtubeid=$_GET["youtubeid"];
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$array["video"]='{video}';
	$array["who"]='{who} ?';
	
	
	while (list ($num, $ligne) = each ($array) ){

		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&youtubeid={$_GET["youtubeid"]}&xtime={$_GET["xtime"]}\"><span>$ligne</span></a></li>\n");
	}
	
	
	echo "
	<div id=main_config_youtubeid style='width:100%'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_youtubeid').tabs();
			
			
			});
		</script>";		
	
}

function who(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();

	$t=time();
	$new_entry=$tpl->javascript_parse_text("{new_backup_rule}");
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
	$created=$tpl->javascript_parse_text("{created}");
	$duration=$tpl->javascript_parse_text("{duration}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$categories=$tpl->javascript_parse_text("{categories}");
	$day=$tpl->javascript_parse_text("{day}");
	$uid=$tpl->javascript_parse_text("{uid}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$MAC=$tpl->_ENGINE_parse_body("{MAC}");
	
	$TB_HEIGHT=400;
	$TB_WIDTH=720;
	$q=new mysql_squid_builder();
	$sql="SELECT title FROM youtube_objects WHERE youtubeid='{$_GET["youtubeid"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));		
	$titleY=$ligne["title"];
	
	$buttons="
	buttons : [
	{name: '$categories', bclass: 'Catz', onpress : Categories$t},
	{name: '$online_help', bclass: 'Help', onpress : ItemHelp$t},
	
	],	";
	
	
$buttons=null;

	$html="
	<div style='margin-left:-15px'>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	</div>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?who-items=yes&t=$t&youtubeid={$_GET["youtubeid"]}',
	dataType: 'json',
	colModel : [
		{display: '$day', name : 'zDate', width :114, sortable : true, align: 'left'},
		{display: '$uid', name : 'uid', width :114, sortable : true, align: 'left'},	
		{display: '$ipaddr', name : 'ipaddr', width :107, sortable : true, align: 'left'},
		{display: '$hostname', name : 'hostname', width :107, sortable : true, align: 'left'},
		{display: '$MAC', name : 'MAC', width :107, sortable : true, align: 'left'},
		{display: '$hits', name : 'hits', width :75, sortable : true, align: 'left'},
	
	],
	$buttons

	searchitems : [
		{display: '$day', name : 'zDate'},
		{display: '$uid', name : 'uid'},
		{display: '$ipaddr', name : 'ipaddr'},
		{display: '$hostname', name : 'hostname'},
		{display: '$MAC', name : 'MAC'},

	],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '<span id=\"title-$t\">$titleY</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});


</script>";
	echo $html;
	

}

function who_items(){
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$users=new usersMenus();
	$sock=new sockets();
	
	
	$search='%';
	$table="youtube_dayz";
	$page=1;
	$FORCE_FILTER=" AND youtubeid='{$_GET["youtubeid"]}'";
	
	
	if(!$q->TABLE_EXISTS($table, $database)){json_error_show("$table doesn't exists...");}
	if($q->COUNT_ROWS($table, $database)==0){json_error_show("No rules");}

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
	OnClick=\"javascript:Loadjs('miniadm.webstats.youtubeid.php?youtubeid={$ligne["youtubeid"]}&xtime={$_GET["xtime"]}');\"
	style='font-size:12px;text-decoration:underline;color:$color'>";
	
	$ligne["hits"]=numberFormat($ligne["hits"],0,""," ");
	$urljsSIT=null;
	
	$data['rows'][] = array(
		'id' => "$zmd5",
		'cell' => array(
			"<span style='font-size:12px;color:$color'>$urljsSIT{$ligne["zDate"]}</a></span>",
			"<span style='font-size:12px;color:$color'>$urljsFAM{$ligne["uid"]}</a></span>",
			"<span style='font-size:12px;color:$color'>$urljs{$ligne["ipaddr"]}</span>",
			"<span style='font-size:12px;color:$color'>{$ligne["hostname"]}</span>",
			"<span style='font-size:12px;color:$color'>{$ligne["MAC"]}</span>",
			"<span style='font-size:12px;color:$color'>{$ligne["hits"]}</span>",
			)
		);
	}
	
	echo json_encode($data);	
}

function video(){
	$youtubeid=$_GET["youtubeid"];
	$xtime=$_GET["xtime"];
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();	
	$sql="SELECT * FROM youtube_objects WHERE youtubeid='$youtubeid'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$title=$ligne["title"];
	$category=$ligne["category"];
	$uploaded=$ligne["uploaded"];
	$duration=format_time($ligne["duration"]);
	$content=base64_decode($ligne["content"]);
	$infos=json_decode($content);
	$contentz=$infos->data->content;
	
	
	foreach ($contentz as $index => $value) {
		if(is_numeric($index)){
			$filename=basename($value);
			if(strpos($filename, "app=youtube_gdata")>0){$filename=null;}
			$links[]="<li><a href=\"javascript:blur();\" OnClick=\"javascript:s_PopUpFull('$value','1024','900');\">{link} $index :$filename</a></li>";
			
		}
	}
	
	$html="
	<div class=BodyContent>
	<table style='width:99%' class=form>
	<tr>
		<td valign='top' width=1%><img src='miniadm.webstats.youtube.php?thumbnail=$youtubeid'></td>
		<td valign='top' width=99%>
			<table style='width:100%'>
			<tr>
				<td class=legend style='font-size:14px' valign='top'>{video_title}:</td>
				<td><strong style='font-size:14px'>$title</strong>
			</tr>
			<tr>
				<td class=legend style='font-size:14px' valign='top'>{duration}:</td>
				<td><strong style='font-size:14px'>$duration</strong>
			</tr>
			<tr>
				<td class=legend style='font-size:14px' valign='top'>{uploaded}:</td>
				<td><strong style='font-size:14px'>$uploaded</strong>
			</tr>
			<tr>
				<td class=legend style='font-size:14px' valign='top'>{category}:</td>
				<td><strong style='font-size:14px'>$category</strong>
			</tr>	
			<tr>
				<td class=legend style='font-size:14px' valign='top'>{links}:</td>
				<td><div style='font-size:14px;margin-left:15px'>".@implode("", $links)."</strong>
			</tr>	
			
			</table>
		</td>
	</tr>
	</table>
	</div>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function format_time($t,$f=':') // t = seconds, f = separator 
{
  return sprintf("%02d%s%02d%s%02d%s", floor($t/3600), "h ", ($t/60)%60, "mn ", $t%60,"s");
}