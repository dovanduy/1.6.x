<?php
session_start();

ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
if(!$_SESSION["AsWebStatisticsAdministrator"]){header("location:miniadm.index.php");die();}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["master-content"])){master_content();exit;}
if(isset($_GET["items"])){items();exit;}
if(isset($_GET["thumbnail"])){thumbnail();exit;}
if(isset($_GET["categories-list"])){categories_list();exit;}
if(isset($_GET["js"])){js();exit;}

main_page();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	if(!$_SESSION["CORP"]){
		
		$onlycorpavailable=$tpl->javascript_parse_text("{onlycorpavailable}");
		echo "alert('$onlycorpavailable');";	
		return;
	}
	$q=new mysql_squid_builder();
	$youtube_objects=$q->COUNT_ROWS("youtube_objects");
	$youtube_objects=numberFormat($youtube_objects,0,""," ");
	
	$title=$tpl->_ENGINE_parse_body("$youtube_objects Youtube {objects}");
	echo "YahooWin3('926','$page?master-content=yes','$title')";
	
}

function main_page(){
	$page=CurrentPageName();
	
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	
	if(!$_SESSION["CORP"]){
		$tpl=new templates();
		$onlycorpavailable=$tpl->javascript_parse_text("{onlycorpavailable}");
		$content=str_replace("{SCRIPT}", "<script>alert('$onlycorpavailable');document.location.href='miniadm.webstats.php';</script>", $content);
		echo $content;	
		return;
	}
	
	
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;	
}


function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	if(isset($_GET["xtime"])){
		$_GET["year"]=date("Y",$_GET["xtime"]);
		$_GET["month"]=date("m",$_GET["xtime"]);
		$_GET["day"]=date("d",$_GET["xtime"]);	
	}
	
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a>
		&nbsp;&raquo;&nbsp;<a href=\"miniadm.webstats.php?t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}\">{web_statistics}</a>
		
		</div>
		<H1>Youtube {objects}</H1>
		<p>{youtube_objects_statistics_text}</p>
	</div>	
	<div id='master-content'></div>
	
	<script>
		LoadAjax('master-content','$page?master-content=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}



function master_content(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=500;
	$TB_WIDTH=910;
	$uid=$_GET["uid"];
		
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
	$buttons="
	buttons : [
	{name: '$categories', bclass: 'Catz', onpress : Categories$t},
	{name: '$online_help', bclass: 'Help', onpress : ItemHelp$t},
	
	],	";
	
	
//youtubeid 	 	title 	content 	 	hits 	 	thumbnail	

	$html="
	<div class=BodyContent>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	</div>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?items=yes&t=$t&uid=$uid',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'thumbnail', width :122, sortable : false, align: 'center'},
		{display: '$created', name : 'uploaded', width :149, sortable : true, align: 'left'},
		{display: '$title', name : 'title', width :304, sortable : true, align: 'left'},	
		{display: '$category', name : 'category', width :107, sortable : true, align: 'left'},
		{display: '$duration', name : 'duration', width :88, sortable : false, align: 'left'},
		{display: '$hits', name : 'hits', width :46, sortable : true, align: 'center'},
	
	],
	$buttons

	searchitems : [
		{display: '$title', name : 'title'},
		{display: '$category', name : 'category'},

	],
	sortname: 'uploaded',
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
	s_PopUpFull('http://www.mail-appliance.org/index.php?cID=339','1024','900');
}

function Categories$t(){
	YahooWin('450','$page?categories-list=yes&t=$t','$categories');
}

var x_Delete$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);return;}
    $('#row'+mem$t).remove();
}



var x_Enable$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);return;}
    $('#flexRT$t').flexReload();
}


function Enable$t(md){
	mem$t=md;
	var enable=0;
	if(document.getElementById('enable_'+md).checked){enable=1;}
 	var XHR = new XHRConnection();
    XHR.appendData('enable-item',md);
    XHR.appendData('value',enable);
    XHR.sendAndLoad('$page', 'POST',x_Enable$t);		
   
	}

function Delete$t(md){
	mem$t=md;
	if(confirm('$action_delete_rule')){
 		var XHR = new XHRConnection();
   	 	XHR.appendData('delete-item',md);
   	 	XHR.sendAndLoad('$page', 'POST',x_Delete$t);		
	}
}

function Run$t(md){
	mem$t=md;
	if(confirm('$error_want_operation')){
 		var XHR = new XHRConnection();
   	 	XHR.appendData('run-item',md);
   	 	XHR.sendAndLoad('$page', 'POST',x_Enable$t);		
	}

}

function Events$t(md){
	YahooWin5('505','$page?events-table=yes&zmd5='+md+'&t=$t','$events');
}

function NewGItem$t(){
	YahooWin5('600','$page?backup-rule=&uid=$uid&t=$t','$new_entry');

}
function GItem$t(md,title){
	YahooWin5('600','$page?backup-rule='+md+'&uid=$uid&t=$t',title);

}


</script>";
	
	echo $html;
	
	
	
}

function items(){
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$uid=$_GET["uid"];
	$users=new usersMenus();
	$sock=new sockets();
	if(!$users->AsMailBoxAdministrator){$uid=$_SESSION["uid"];}	
	
	$search='%';
	$table="youtube_objects";
	$database="artica_backup";
	$page=1;
	$FORCE_FILTER=null;
	if($_GET["category"]<>null){$FORCE_FILTER=" AND category='{$_GET["category"]}'";}
	
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
	
	$seconds=$tpl->_ENGINE_parse_body("{seconds}");
	$minutes=$tpl->_ENGINE_parse_body("{minutes}");
	$hours=$tpl->_ENGINE_parse_body("{hours}");
	while ($ligne = mysql_fetch_assoc($results)) {
	$youtubeid=$ligne["youtubeid"];
	$color="black";
	$delete=imgsimple("delete-24.png","","Delete$t('$zmd5')");
	$enabled=Field_checkbox("enable_$zmd5", 1,$ligne["enabled"],"Enable$t('$zmd5')");
	
	//if($ligne["enabled"]==0){$color="#B3B3B3";}
	
	$urljs="<a href=\"javascript:blur();\" 
	OnClick=\"javascript:GItem$t('$zmd5','{$ligne["imapserv"]}/{$ligne["account"]}')\"
	style='font-size:14px;text-decoration:underline;color:$color'>";
	$unit=$seconds;
	$ligne["duration"]=format_time($ligne["duration"]);
	
	$urljsSIT="<a href=\"javascript:blur();\" 
	OnClick=\"javascript:Loadjs('miniadm.webstats.youtubeid.php?youtubeid=$youtubeid');\"
	style='font-size:14px;text-decoration:underline;color:$color'>";	
	
	$data['rows'][] = array(
		'id' => "$zmd5",
		'cell' => array(
			"<span style='font-size:14px;color:$color'><a href=\"javascript:blur();\" OnClick=\"Loadjs('miniadm.webstats.youtubeid.php?youtubeid=$youtubeid');\"><img src='$MyPage?thumbnail=$youtubeid'></span></a>",
			"<span style='font-size:14px;color:$color'>$urljsSIT{$ligne["uploaded"]}</a></span>",
			"<span style='font-size:14px;color:$color'>$urljsSIT{$ligne["title"]}</span>",
			"<span style='font-size:14px;color:$color'>$urljsSIT{$ligne["category"]}</span>",
			"<span style='font-size:14px;color:$color'>{$ligne["duration"]}</span>",
			"<span style='font-size:14px;color:$color'>{$ligne["hits"]}</span>",
			)
		);
	}
	
	
echo json_encode($data);	
	
}

function thumbnail(){
	$q=new mysql_squid_builder();
	$sql="SELECT thumbnail FROM youtube_objects WHERE youtubeid='{$_GET["thumbnail"]}'";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$t=time();
	header('Content-type: image/jpeg');
	header('Content-Disposition: inline; filename="'.$t.'.jpg"');
 	print($ligne["thumbnail"]);
}

function categories_list(){
	$t=$_GET["t"];
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$sql="SELECT category FROM youtube_objects GROUP BY category ORDER BY category";
	$results = $q->QUERY_SQL($sql,$database);
	$array[null]="{select}";
	while ($ligne = mysql_fetch_assoc($results)) {
		$array[$ligne["category"]]=$ligne["category"];
	}
	$category_label=$tpl->javascript_parse_text("{category}");
	$html="
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{category}:</td>
		<td>".Field_array_Hash($array,"category-choose-$t",null,"CategoryChoosen$t()",null,0,"font-size:14px")."</td>
	</tr>
	</table>
	<script>
		function CategoryChoosen$t(){
			var category=document.getElementById('category-choose-$t').value;
			$('.ftitle').html('$category_label&raquo;&raquo;'+category);
			$('#flexRT$t').flexOptions({url: '$page?items=yes&category='+category}).flexReload();			
		
		}
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function format_time($t,$f=':') // t = seconds, f = separator 
{
  return sprintf("%02d%s%02d%s%02d%s", floor($t/3600), "h ", ($t/60)%60, "mn ", $t%60,"s");
}



