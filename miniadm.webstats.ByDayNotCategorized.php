<?php
session_start();
$_SESSION["MINIADM"]=true;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.calendar.inc");
if(!$_SESSION["AsWebStatisticsAdministrator"]){header("location:miniadm.index.php");die();}

if(isset($_GET["section"])){section();exit;}
if(isset($_GET["sitename-search"])){section_rows();exit;}
if(isset($_GET["events"])){section_events();exit;}
if(isset($_GET["events-search"])){section_events_search();exit;}
if(isset($_GET["status"])){status();exit;}
tabs();


function tabs(){
	$page=CurrentPageName();
	$boot=new boostrap_form();
	$tpl=new templates();
	$title=time_to_date($_GET["xtime"]).": {websites} &laquo;{not_categorized}&raquo;";
	
	$array["{websites}"]="$page?section=yes&groupby=sitename&xtime={$_GET["xtime"]}";
	$array["{familysites}"]="$page?section=yes&groupby=familysite&xtime={$_GET["xtime"]}";
	$array["{status}"]="$page?status=yes&groupby=familysite&xtime={$_GET["xtime"]}";
	$array["{events}"]="$page?events=yes&groupby=familysite&xtime={$_GET["xtime"]}";
	$title= $tpl->_ENGINE_parse_body("<h4>$title</H4>
	<div class=explain>{not_categorized_day_explain}</div>");
	echo "
	$title
	".$boot->build_tab($array);
}

function section(){
	$suffix=suffix();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$form=$boot->SearchFormGen("{$_GET["groupby"]}","sitename-search",$suffix);
	echo $form;	
	
}
function section_events(){
	$suffix=suffix();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$form=$boot->SearchFormGen("subject","events-search",$suffix);
	echo $form;	
}

function suffix(){
	return "&groupby={$_GET["groupby"]}&xtime={$_GET["xtime"]}";
}

function status(){
	$xtime=$_GET["xtime"];
	$sock=new sockets();
	$tpl=new templates();
	$tablename=date("Ymd",$xtime)."_hour";
	$file="/usr/share/artica-postfix/ressources/logs/categorize-tables/$tablename";
	if(!is_file($file)){
		senderror("{no_information_retreived}");
	}
	
	$ARRAY=unserialize(@file_get_contents($file));
	if(!is_array($ARRAY)){
		senderror("{no_information_retreived}");
	}
	
	$PID=$ARRAY["PID"];
	$CUR=$ARRAY["CURRENT"];
	$MAX=$ARRAY["MAX"];
	
	if($CUR==$MAX){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("<div class=explain>{this_table_is_categorized}</div>");
		
	}
	
	$pourc=round($CUR/$MAX)*100;
	$PP=unserialize(base64_decode($sock->getFrameWork("cmd.php?ProcessInfo=$PID")));
	if(!isset($PP["PROCESS_TIME"])){
		$text="$CUR/$MAX - $pourc% {stopped}";
	}else{
		$text="$CUR/$MAX - $pourc% - {running} {since}: {$PP["PROCESS_TIME"]}";
	}
	echo $tpl->_ENGINE_parse_body("<div class=explain style='font-size:18px;font-weight:bold'>$text</div>");
}

function section_events_search(){
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$users=new usersMenus();
	$sock=new sockets();
	$boot=new boostrap_form();
	$database="squidlogs";
	$search='%';
	$table="notcategorized_events";
	$tablename=date("Ymd",$_GET["xtime"])."_hour";
	$rp=250;
	$page=1;
	$FORCE_FILTER=null;
	$ORDER="ORDER BY hits DESC";
	
	if(!$q->TABLE_EXISTS($table, $database)){senderror("$table doesn't exists...");}
	if($q->COUNT_ROWS($table, $database)==0){senderror("No data");}	
	
	$search=string_to_flexquery("events-search");
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	$sql="SELECT * FROM $table
	WHERE tablename='$tablename' $search ORDER BY zDate DESC LIMIT 0,500";
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){senderror($q->mysql_error."<br>$sql");}
	
	while ($ligne = mysql_fetch_assoc($results)) {
	
		$color="black";
		$ligne["description"]=$tpl->_ENGINE_parse_body($ligne["description"]);
	
		//$link=$boot->trswitch($jslink);
		$tr[]="
		<tr>
		<td><i class='icon-time'></i>&nbsp;{$ligne["zDate"]}</a></td>
		<td><i class='icon-info-sign'></i>&nbsp;{$ligne["subject"]}<div>{$ligne["description"]}</td>
		</tr>";
	}
	echo $tpl->_ENGINE_parse_body("
	<table class='table table-bordered table-hover'>
		<thead>
			<tr>
				<th>{date}</th>
				<th>{events} ($tablename)</th>
			</tr>
		</thead>
		<tbody>
			").@implode("", $tr)."</tbody></table>";	
}

function section_rows(){

$t=$_GET["t"];
$tpl=new templates();
$MyPage=CurrentPageName();
$q=new mysql_squid_builder();
$users=new usersMenus();
$sock=new sockets();
$boot=new boostrap_form();
$database="squidlogs";
$search='%';
$table=date("Ymd",$_GET["xtime"])."_hour";
$rp=250;
$page=1;
$FORCE_FILTER=null;
$ORDER="ORDER BY hits DESC";

if(!$q->TABLE_EXISTS($table, $database)){senderror("$table doesn't exists...");}
if($q->COUNT_ROWS($table, $database)==0){senderror("No data");}

if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
if(isset($_POST['page'])) {$page = $_POST['page'];}

$search=string_to_flexquery("sitename-search");
if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
$category=mysql_escape_string($_GET["category"]);
$sql="SELECT SUM(size) as size, sum(hits) as hits,`{$_GET["groupby"]}`,`category` FROM $table
GROUP BY {$_GET["groupby"]},category HAVING LENGTH(`category`)=0 $search $ORDER LIMIT 0,500";
$results = $q->QUERY_SQL($sql,$database);
if(!$q->ok){senderror($q->mysql_error."<br>$sql");}

while ($ligne = mysql_fetch_assoc($results)) {

	$color="black";
	$ligne["hits"]=numberFormat($ligne["hits"],0,""," ");
	$ligne["size"]=FormatBytes($ligne["size"]/1024);


	$link=$boot->trswitch($jslink);
	$tr[]="
	<tr>
	<td $link><i class='icon-user'></i>&nbsp;{$ligne[$_GET["groupby"]]}</a></td>
	<td $link><i class='icon-info-sign'></i>&nbsp;{$ligne["size"]}</td>
	<td $link><i class='icon-info-sign'></i>&nbsp;{$ligne["hits"]}</td>
	</tr>";
}

echo $tpl->_ENGINE_parse_body("

		<table class='table table-bordered table-hover'>

			<thead>
				<tr>
					<th>{{$_GET["groupby"]}}</th>
					<th>{size}</th>
					<th>{hits}</th>
				</tr>
			</thead>
			 <tbody>
				").@implode("", $tr)."</tbody></table>";


}