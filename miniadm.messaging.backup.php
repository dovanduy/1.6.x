<?php
session_start();
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.archive.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["byday"])){table_bydays();exit;}
if(isset($_GET["byday-items"])){table_bydays_items();exit;}
if(isset($_GET["MessageID-js"])){MessageID_js();exit;}
if(isset($_GET["MessageID"])){MessageID_content();exit;}
if(isset($_GET["byMonth"])){table_bydays();exit;}
if(isset($_GET["choose-day"])){choose_day();exit;}
if(isset($_GET["title-day"])){title_day();exit;}

main_page();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;	
}

function choose_day(){
	$t=$_GET["t"];
	$page=CurrentPageName();
	$q=new mysql_mailarchive_builder();
	$sql="SELECT DATE_FORMAT(xday,'%Y-%m-%d') as tdate FROM indextables ORDER BY xday LIMIT 0,1";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$mindate=$ligne["tdate"];

	$sql="SELECT DATE_FORMAT(xday,'%Y-%m-%d') as tdate FROM indextables ORDER BY xday DESC LIMIT 0,1";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$maxdate=date('Y-m-d');	
	
	$tt=time();
	$html="<div id='$tt' style='background-color:white'><div>
	<script>
	function StartDate$tt(){
		jQuery('#$tt').datepicker(
			{onSelect: Select$tt,
			maxDate: \"$maxdate\",
			minDate: \"$mindate\",
			showButtonPanel: true,
			dateFormat: \"yy-mm-dd\"});
	


}
function  Select$tt(dateStr) {
		$('#flexRT$t').flexOptions({url: '$page?byday-items=yes&t=$t&day='+dateStr}).flexReload();
		LoadAjaxTiny('title-$t','$page?title-day='+dateStr);
		
}

StartDate$tt();
</script>

";
	
	echo $html;
	
	
}

function title_day(){
	$tpl=new templates();
	$stime=strtotime($_GET["title-day"]." 00:00:00");
	$title=$tpl->_ENGINE_parse_body(date("{l} d {F}",$stime));
	echo $title;
}

function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a>&nbsp;&raquo;&nbsp;<a href=\"miniadm.messaging.php\">{mymessaging}</a></div>
		<H1>{my_backuped_mails}</H1>
		<p>{my_backuped_mails_text}</p>
		<div id='statistics-$t'></div>
	</div>	
	<div id='backuped-tabs-$t'></div>
	
	<script>
		LoadAjax('backuped-tabs-$t','$page?tabs=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$array["byday"]='{byday}';
	$array["byMonth"]='{byMonth}';
	while (list ($num, $ligne) = each ($array) ){
			
		$tab[]="<li><a href=\"$page?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			
		}
	
	
	

	$html="
		<div id='main_backupedmsgs' style='background-color:white;margin-top:10px'>
		<ul>
		". implode("\n",$tab). "
		</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_backupedmsgs').tabs();
			

			});
		</script>
	
	";	
	
	echo $tpl->_ENGINE_parse_body($html);			
}

function table_bydays(){
	$today=date("Y-m-d");
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=500;
	$TB_WIDTH=710;
	$from=$tpl->_ENGINE_parse_body("{sender}");
	$subject=$tpl->_ENGINE_parse_body("{subject}");
	$date=$tpl->_ENGINE_parse_body("{date}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$choose_day=$tpl->_ENGINE_parse_body("{choose_date}");
	$sent=$tpl->_ENGINE_parse_body("{sent_items}");
	$recipient=$tpl->_ENGINE_parse_body("{recipient}");
	if(isset($_GET["byMonth"])){
		$title=$tpl->_ENGINE_parse_body(date("{F}"));
		$byMonth="&byMonth={$_GET["byMonth"]}";
		$choose_day_bt=null;
			
		
	}else{
		$title=$tpl->_ENGINE_parse_body(date("{l} d {F}"));
		$choose_day_bt="{name: '$choose_day', bclass: 'Calendar', onpress : Chooseday$t},";
		
	}
	$sent_bt="{name: '$sent', bclass: 'eMail', onpress : Chooseday$t},";
	
$buttons="buttons : [
		$choose_day_bt
			],	";	
	

	
//ztime 	zhour 	mailfrom 	instancename 	mailto 	domainfrom 	domainto 	senderhost 	recipienthost 	mailsize 	smtpcode 	
	$html="
	<div id='query-explain'></div>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?byday-items=yes&t=$t&day=$today$byMonth',
	dataType: 'json',
	colModel : [
		{display: '$date', name : 'zDate', width :106, sortable : true, align: 'left'},	
		{display: '$from', name : 'mailfrom', width :152, sortable : true, align: 'left'},
		{display: '$recipient', name : 'mailto', width :152, sortable : true, align: 'left'},
		{display: '$subject', name : 'subject', width :300, sortable : true, align: 'left'},
		{display: '$size', name : 'mailsize', width :103, sortable : true, align: 'left'},
	],
	$buttons

	searchitems : [
		{display: '$from', name : 'mailfrom'},
		{display: '$recipient', name : 'mailto'},
		{display: '$subject', name : 'subject'},
		
	],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '<span id=\"title-$t\">$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 940,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

function Chooseday$t(){
	YahooWin2('260','$page?choose-day=yes&t=$t','$choose_day');

}

</script>";
	
	echo $html;	
}

function table_bydays_items(){
	$myPage=CurrentPageName();
	$day=$_GET["day"];
	$stime=strtotime("$day 00:00:00" );
	$tpl=new templates();
	$q=new mysql_mailarchive_builder();
	$table_query=date("Ymd",$stime);
	$table="`$table_query`";
	$uid=$_SESSION["uid"];
	$tm=array();
	if(isset($_GET["byMonth"])){
			if(!is_numeric($_GET["byMonth"])){
			$sql="SELECT DATE_FORMAT(xday,'%Y%m%d') as tdate FROM indextables 
			WHERE MONTH(xday)=MONTH(NOW()) AND YEAR(xday)=YEAR(NOW())
			ORDER BY xday";
			$results = $q->QUERY_SQL($sql,$database);
			while ($ligne = mysql_fetch_assoc($results)) {
				$tm[]="`{$ligne["tdate"]}`";
				
			}
			
		}
	}
	
	$ct=new user($_SESSION["uid"]);
	$mails=$ct->HASH_ALL_MAILS;
	while (list ($index, $message) = each ($mails) ){$q1[]=" (`mailto`='$message' OR `mailfrom`='$message')";}
	$search='%';
	$page=1;
	$FORCE_FILTER=" AND (".@implode("OR", $q1).")";
	
	if(strpos($table, ",")==0){
		if(!$q->TABLE_EXISTS($table)){
			json_error_show("$table: No such table",0,true);
		}
	}

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		if(count($tm)>0){
			reset($tm);
			$rz=array();
			while (list ($num, $tablez) = each ($tm) ){$rz[]="(SELECT COUNT(*) as TCOUNT FROM $tablez WHERE 1 $FORCE_FILTER $searchstring)";}
			$sql="SELECT SUM(TCOUNT) as TCOUNT FROM (".@implode(" UNION ", $rz).") as t";
		}
		
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show($q->mysql_error);}	
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		
		
		if(count($tm)>0){
			reset($tm);
			$rz=array();
			while (list ($num, $tablez) = each ($tm) ){$rz[]="(SELECT COUNT(*) as TCOUNT FROM $tablez WHERE 1 $FORCE_FILTER)";}
			$sql="SELECT SUM(TCOUNT) as TCOUNT as FROM (".@implode(" UNION ", $rz).") as t";
		}	
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show($q->mysql_error);}	
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";

		if(count($tm)>0){
			reset($tm);
			$rz=array();
			while (list ($num, $tablez) = each ($tm) ){$rz[]="(SELECT * FROM $tablez WHERE 1 $searchstring $FORCE_FILTER)";}
			$sql="SELECT *  FROM (".@implode(" UNION ", $rz).") as t $ORDER $limitSql";
		}	
	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	
	if(!$q->ok){json_error_show($q->mysql_error);}	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$MessageID=$ligne["MessageID"] ;
		$time=strtotime($ligne["zDate"]);
		$zDate=date("H:i:s",$time);
		if(count($tm)>0){$zDate=$tpl->_ENGINE_parse_body(date("{l} d H:i:s",$time));}
		$mailfrom=$ligne["mailfrom"];
		$mailto=$ligne["mailto"];
		$subject=mime_decode($ligne["subject"]);
		$message_size=FormatBytes($ligne["message_size"]/1024);
		
		$urljs="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$myPage?MessageID-js=$MessageID&table=$table_query')\"
		style='font-size:11px;text-decoration:underline'>";
		
		//$subject=mime_decode($subject);
		$data['rows'][] = array(
				'id' => $MessageID,
				'cell' => array(
					"<span style='font-size:11px;color:$color'>$zDate</a></span>",
					"<span style='font-size:11px;color:$color'>$urljs{$mailfrom}</a></span>",
					"<span style='font-size:11px;color:$color'>$urljs{$mailto}</a></span>",
					"<span style='font-size:11px;color:$color'>$urljs{$subject}</a></span>",
					"<span style='font-size:11px;color:$color'>$urljs{$message_size}</a></span>",
					
					)
				);
			}
	
	
echo json_encode($data);	
	
	
	
}

function MessageID_js(){
	$q=new mysql_mailarchive_builder();
	$sql="SELECT subject FROM `{$_GET["table"]}` WHERE MessageID='{$_GET["MessageID-js"]}'";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql));
	$subkect=mime_decode($ligne["subject"]);
	$page=CurrentPageName();
	echo "YahooWin('800','$page?MessageID={$_GET["MessageID-js"]}&table={$_GET["table"]}','$subkect');";	
}

function MessageID_content(){
	$q=new mysql_mailarchive_builder();
	$sql="SELECT MessageBody FROM `{$_GET["table"]}` WHERE MessageID='{$_GET["MessageID"]}'";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql));
	$tpl=new templates();
	$msg=$ligne["MessageBody"];
	if(preg_match("#<\!--X-Body-Begin-->(.*?)<\!--X-Body-of-Message-End-->#is",$msg,$re)){$msg=$re[1];}
	
	
	$html="
	<div class=BodyContent style='height:500px;overflow:auto'>$msg</div>
	
	";
	
	echo $html;
	
}


function mime_decode($s) {
 if(!preg_match("#^=\?#", $s)){return utf8_encode($s);}
 if(!function_exists("imap_mime_header_decode")){return utf8_encode($s);}
  $elements = imap_mime_header_decode($s);
  for($i = 0;$i < count($elements);$i++) {
    $charset = $elements[$i]->charset;
    $text =$elements[$i]->text;
    if(!strcasecmp($charset, "utf-8") ||
       !strcasecmp($charset, "utf-7"))
    {
      $text = iconv($charset, "EUC-KR", $text);
    }
    $decoded = $decoded . $text;
  }
  return utf8_encode($decoded);
}
