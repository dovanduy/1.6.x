<?php
session_start();
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["messaging-tabs"])){messaging_tabs();exit;}
if(isset($_GET["messaging-left"])){messaging_left();exit;}
if(isset($_GET["messaging-stats"])){messaging_stats();exit;}
if(isset($_GET["today"])){today();exit;}
if(isset($_GET["today-calc"])){today_calc();exit;}
if(isset($_GET["today-items"])){today_items();exit;}
if(isset($_GET["greylisth"])){greylisth_table();exit;}
if(isset($_GET["greylisth-items"])){greylisth_items();exit;}


main_page();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;	
}

function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a>&nbsp;&raquo;&nbsp;<a href=\"miniadm.messaging.php\">{mymessaging}</a></div>
		<H1>{messaging_statistics}</H1>
		<p>{my_messaging_statistics_text}</p>
		<div id='statistics-$t'></div>
	</div>	
	<div id='messaging-$t'></div>
	
	<script>
		LoadAjax('messaging-$t','$page?messaging-tabs=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function messaging_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$array["today"]='{today}';
	
	$q=new mysql_postfix_builder();
	$tablegrey="mgreyh_".date('Ymdh');
	
	if($q->TABLE_EXISTS("$tablegrey")){
		$ct=new user($_SESSION["uid"]);
		$mails=$ct->HASH_ALL_MAILS;
		while (list ($index, $message) = each ($mails) ){$q1[]=" (`mailto`='$message')";}	
		$FORCE_FILTER=" AND (".@implode("OR", $q1).")";	
		$sql="SELECT COUNT(zmd5) as tcount from $tablegrey WHERE 1$FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if($ligne["tcount"]>0){
			$array["greylisth"]=date("H")."h {greylist}";
		}
	}
	
	
	while (list ($num, $ligne) = each ($array) ){
			
		$tab[]="<li><a href=\"$page?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			
		}
	
	
	

	$html="
		<div id='main_mypoststats' style='background-color:white;margin-top:10px'>
		<ul>
		". implode("\n",$tab). "
		</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_mypoststats').tabs();
			

			});
		</script>
	
	";	
	
	echo $tpl->_ENGINE_parse_body($html);			
}

function today(){
	$tpl=new templates();
	$t=time();
	$page=CurrentPageName();
	$html="<center style='margin:10px'><table><tr>";
	$time=intval(date('H'));
	if($time>2){$def=$time-1;}else{$def=$time;}
	for($i=0;$i<=$time;$i++){
		if($i<10){$iT="0$i";}else{$iT=$i;}
		$html=$html."<td><a href=\"javascript:blur();\" OnClick=\"javascript:ShowTime$t('$i')\" style='font-size:14px;text-decoration:underline'>{$iT}h</a></td>";
		
	}
	$html=$html."</tr></table></center>
	<div id='$t'></div>
	
	<script>
		function ShowTime$t(i){
			LoadAjax('$t','$page?today-calc='+i+'&t=$t');
		}
		
		ShowTime$t($def);
	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function greylisth_table(){
	$Hour=date("H");
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=400;
	$TB_WIDTH=710;
	
		$ct=new user($_SESSION["uid"]);
		$mails=$ct->HASH_ALL_MAILS;
		while (list ($index, $message) = each ($mails) ){$q1[]="`$message`";}		
	
	$new_entry=$tpl->_ENGINE_parse_body("{new_rule}");
	$filename=$tpl->_ENGINE_parse_body("{filename}");
	$date=$tpl->_ENGINE_parse_body("{time}");
	$title=$tpl->_ENGINE_parse_body("{greylisting} {received_messages} {$Hour}h")." <span style=font-size:10px>".@implode(",", $q1)."</span>";
	$from=$tpl->_ENGINE_parse_body("{sender}");
	$size=$tpl->javascript_parse_text("{size}");
	$enable=$tpl->_ENGINE_parse_body("{enable}");
	$compile_rules=$tpl->_ENGINE_parse_body("{compile_rules}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	$options=$tpl->_ENGINE_parse_body("{options}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	
	
	
	
	$buttons="
	buttons : [
	{name: '$new_entry', bclass: 'Add', onpress : NewGItem$t},
	{name: '$compile_rules', bclass: 'Reconf', onpress : MimeDefangCompileRules},
	{name: '$options', bclass: 'Settings', onpress : Options$t},
	{name: '$items', bclass: 'Db', onpress : ShowTable$t},
	{name: '$online_help', bclass: 'Help', onpress : ItemHelp$t},
	
	],	";
	
	$buttons=null;
	
//ztime 	zhour 	mailfrom 	instancename 	mailto 	domainfrom 	domainto 	senderhost 	recipienthost 	mailsize 	smtpcode 	
	$html="
	
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?greylisth-items=yes&t=$t&hour=$Hour',
	dataType: 'json',
	colModel : [
		{display: '$date', name : 'ztime', width :68, sortable : true, align: 'left'},	
		{display: '$from', name : 'mailfrom', width :699, sortable : true, align: 'left'},
		{display: 'Grey', name : 'failed', width :103, sortable : true, align: 'left'},
	],
	$buttons

	searchitems : [
		{display: '$from', name : 'mailfrom'},
		
	],
	sortname: 'ztime',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 940,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});
</script>";
	
	echo $html;	
}

function greylisth_items(){
	$Hour=$_GET["hour"];
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_postfix_builder();
	
	if(strlen($Hour)==1){$Hour="0$Hour";}
	$ct=new user($_SESSION["uid"]);
	$mails=$ct->HASH_ALL_MAILS;
	while (list ($index, $message) = each ($mails) ){
		$q1[]=" (`mailto`='$message')";
	}	
	
	$search='%';
	$table="mgreyh_".date('Ymdh');
	$database="artica_backup";
	$page=1;
	$FORCE_FILTER=" AND (".@implode("OR", $q1).")";
	if(!$q->TABLE_EXISTS($table)){
		json_error_show("$table: No such table",0,true);
	}

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
			$zmd5=md5($ligne["filename"]);
			$color="#D90505";
			$res=strtolower(trim($ligne["failed"]));
			if($res=="accept"){$color="#00922B";}	
			
			$delete=imgsimple("delete-24.png","","DeleteFileNameHosting$t('{$ligne["filename"]}','$zmd5')");
			
			
			$ztime=strtotime($ligne["ztime"]);
			$ztime=date("i:s",$ztime);
			$ligne["failed"]=$tpl->_ENGINE_parse_body("{{$ligne["failed"]}}");
			
			$data['rows'][] = array(
				'id' => "D$zmd5",
				'cell' => array(
					"<span style='font-size:14px;color:black'>$ztime</a></span>",
					"<span style='font-size:14px;color:black'>$urljs{$ligne["mailfrom"]} $res</a></span>",
					"<span style='font-size:14px;color:$color'>$urljs{$ligne["failed"]}</a></span>",
					
					)
				);
	}
	
	
echo json_encode($data);	
}

function today_calc(){
	$Hour=$_GET["today-calc"];
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=400;
	$TB_WIDTH=710;
	$new_entry=$tpl->_ENGINE_parse_body("{new_rule}");
	$filename=$tpl->_ENGINE_parse_body("{filename}");
	$date=$tpl->_ENGINE_parse_body("{date}");
	$title=$tpl->_ENGINE_parse_body("{received_messages} {$Hour}h");
	$from=$tpl->_ENGINE_parse_body("{sender}");
	$size=$tpl->javascript_parse_text("{size}");
	$enable=$tpl->_ENGINE_parse_body("{enable}");
	$compile_rules=$tpl->_ENGINE_parse_body("{compile_rules}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	$options=$tpl->_ENGINE_parse_body("{options}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	
	
	
	
	$buttons="
	buttons : [
	{name: '$new_entry', bclass: 'Add', onpress : NewGItem$t},
	{name: '$compile_rules', bclass: 'Reconf', onpress : MimeDefangCompileRules},
	{name: '$options', bclass: 'Settings', onpress : Options$t},
	{name: '$items', bclass: 'Db', onpress : ShowTable$t},
	{name: '$online_help', bclass: 'Help', onpress : ItemHelp$t},
	
	],	";
	
	$buttons=null;
	
//ztime 	zhour 	mailfrom 	instancename 	mailto 	domainfrom 	domainto 	senderhost 	recipienthost 	mailsize 	smtpcode 	
	$html="
	
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?today-items=yes&t=$t&hour=$Hour',
	dataType: 'json',
	colModel : [
		{display: '$date', name : 'ztime', width :152, sortable : true, align: 'left'},	
		{display: '$from', name : 'mailfrom', width :628, sortable : true, align: 'left'},
		{display: '$size', name : 'mailsize', width :103, sortable : true, align: 'left'},
	],
	$buttons

	searchitems : [
		{display: '$from', name : 'mailfrom'},
		
	],
	sortname: 'ztime',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 940,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});
</script>";
	
	echo $html;	
}

function today_items(){
	$Hour=$_GET["hour"];
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_postfix_builder();
	
	if(strlen($Hour)==1){$Hour="0$Hour";}
	$ct=new user($_SESSION["uid"]);
	$mails=$ct->HASH_ALL_MAILS;
	while (list ($index, $message) = each ($mails) ){
		$q1[]=" (`mailto`='$message')";
	}	
	
	$search='%';
	$table=date('Ymd').$Hour."_hour";
	$database="artica_backup";
	$page=1;
	$FORCE_FILTER=" AND (".@implode("OR", $q1).")";
	if(!$q->TABLE_EXISTS($table)){
		json_error_show("$table: No such table",0,true);
	}

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
	$zmd5=md5($ligne["filename"]);

	
	$delete=imgsimple("delete-24.png","","DeleteFileNameHosting$t('{$ligne["filename"]}','$zmd5')");
	
	$ligne["mailsize"]=FormatBytes($ligne["mailsize"]/1024);
	$ztime=strtotime($ligne["ztime"]);
	$ztime=date("H:i:s",$ztime);
	
	$data['rows'][] = array(
		'id' => "D$zmd5",
		'cell' => array(
			"<span style='font-size:14px;color:$color'>$ztime</a></span>",
			"<span style='font-size:14px;color:$color'>$urljs{$ligne["mailfrom"]}</a></span>",
			"<span style='font-size:14px;color:$color'>$urljs{$ligne["mailsize"]}</a></span>",
			
			)
		);
	}
	
	
echo json_encode($data);	
	
}