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
include_once(dirname(__FILE__)."/ressources/class.squid.report.inc");
if(!$_SESSION["AsWebStatisticsAdministrator"]){header("location:miniadm.index.php");die();}
	

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["messaging-right"])){messaging_right();exit;}
if(isset($_GET["webstats-middle"])){webstats_middle();exit;}
if(isset($_GET["items"])){report_items();exit;}
if(isset($_GET["report-js"])){report_js();exit;}
if(isset($_GET["report-tab"])){report_tab();exit;}
if(isset($_GET["report-popup"])){report_popup();exit;}
if(isset($_GET["report-options"])){report_options();exit;}
if(isset($_POST["report"])){report_save();exit;}
if(isset($_POST["run"])){report_run();exit;}
if(isset($_POST["csv"])){save_options_save();exit;}
if(isset($_GET["csv"])){csv_download();exit;}


main_page();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	
	/*if(!$_SESSION["CORP"]){
		$tpl=new templates();
		$onlycorpavailable=$tpl->javascript_parse_text("{onlycorpavailable}");
		$content=str_replace("{SCRIPT}", "<script>alert('$onlycorpavailable');document.location.href='miniadm.webstats-start.php';</script>", $content);
		echo $content;	
		return;
	}	
	*/
	
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&tablename={$_GET["tablename"]}&xtime={$_GET["xtime"]}')</script>", $content);
	echo $content;	
}

function report_js(){
	$ID=$_GET["ID"];
	$tpl=new templates();	
	if($ID==0){$title=$tpl->_ENGINE_parse_body("{new_report}");}
	if($ID>0){
		$rp=new squid_report($ID);
		$title=$rp->report;
	}
	$page=CurrentPageName();
	$tpl=new templates();
	echo "YahooWin('750','$page?report-tab=yes&ID=$ID&t={$_GET["t"]}','$title')";
	
	
}

function report_tab(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_GET["ID"];
	
	
	$array["report-popup"]="{report}";
	if($ID>0){
		$array["report-options"]="{options}";
	}
	$textsize="13px";

	$t=time();
	while (list ($num, $ligne) = each ($array) ){
	
		$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:$textsize'><a href=\"$page?$num=yes&ID=$ID&t={$_GET["t"]}\"><span>$ligne</span></a></li>\n");
	}
	
	
	
	echo "
	<div id=main_node_infos_rps style='width:100%'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_node_infos_rps').tabs();
			
			
			});
		</script>";		
	
}

function report_run(){
	$ID=$_POST["run"];
	$sock=new sockets();
	$sock->getFrameWork("squid.php?run-report=$ID");
	sleep(1);
}

function report_options(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$bt="{apply}";
	$ID=$_GET["ID"];
	$rp=new squid_report($ID);
	$t=$_GET["t"];
	$tt=time();

	$html="<div class=BodyContent>
	<div id='anim-$t'></div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{schedule}:</td>
		<td>". Field_checkbox("schedule-$t",1,$rp->scheduled)."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{create_a_CSV_file}:</td>
		<td>". Field_checkbox("csv-$t",1,$rp->csv)."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button($bt,"rpsave$tt()","18px")."</td>
	</tr>
	</table>
	
	<script>

	var x_rpsave$tt=function (obj) {
		document.getElementById('anim-$t').innerHTML='';
		var ID=$ID;
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}
		$('#flexRT$t').flexReload();	
		RefreshTab('main_node_infos_rps');
		
	}	 
	 
	 
	 function rpsave$tt(){
	 	var csv=0;
	 	var schedule=0;
	 	if(document.getElementById('csv-$t').checked){csv=1;}
	 	if(document.getElementById('schedule-$t').checked){schedule=1;}
	 	var XHR = new XHRConnection();
	 	XHR.appendData('ID','$ID');
	 	XHR.appendData('csv',csv);
	 	XHR.appendData('schedule',schedule);
	 	AnimateDiv('anim-$t');
	 	XHR.sendAndLoad('$page', 'POST',x_rpsave$tt);		
	 
	 }
	 	 
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}
function save_options_save(){
	$ID=$_POST["ID"];
	$rp=new squid_report($ID);
	$rp->scheduled=$_POST["schedule"];
	$rp->csv=$_POST["csv"];
	$rp->Save();
}

function report_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$bt="{apply}";
	$ID=$_GET["ID"];
	$rp=new squid_report($ID);
	$t=$_GET["t"];
	if($ID==0){$bt="{add}";}
	$filters["ipaddr"]="{ipaddr}";
	$filters["hostname"]="{hostname}";
	$filters["MAC"]="{MAC}";
	$filters["uid"]="{username}";
	
	$html="<div class=BodyContent>
	<div id='anim-$t'></div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{report_name}:</td>
		<td>". Field_text("report-$t",$rp->report,"font-size:16px;width:300px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{filter}:</td>
		<td>". Field_array_Hash($filters, "userfield-$t",$rp->userfield,null,null,0,"font-size:16px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{value}:</td>
		<td>". Field_text("userdata-$t",$rp->userdata,null,0,"font-size:16px;width:300px")."</td>
		<td>". button("{browse}","BrowseUsers$t()")."</td>
	</tr>	
	
	<tr>
		<td colspan=3 align='right'><hr>". button($bt,"rpsave$t()","18px")."</td>
	</tr>
	</table>
	
	<script>
	function BrowseUsers$t(){
		var field=document.getElementById('userfield-$t').value;
		Loadjs('squid.nodes.php?filterby='+field+'&fieldname=userdata-$t',true);
	 }
	 
	var x_rpsave$t=function (obj) {
		document.getElementById('anim-$t').innerHTML='';
		var ID=$ID;
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}
		if(ID==0){YahooWinHide();}
		$('#flexRT$t').flexReload();	
		
	}	 
	 
	 
	 function rpsave$t(){
	 	var XHR = new XHRConnection();
	 	XHR.appendData('ID','$ID');
	 	XHR.appendData('report',document.getElementById('report-$t').value);
	 	XHR.appendData('userfield',document.getElementById('userfield-$t').value);
	 	XHR.appendData('userdata',document.getElementById('userdata-$t').value);
	 	AnimateDiv('anim-$t');
	 	XHR.sendAndLoad('$page', 'POST',x_rpsave$t);		
	 
	 }
	 
	 
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function report_save(){
	
	$rp=new squid_report($_POST["ID"]);
	$rp->report=$_POST["report"];
	$rp->userfield=$_POST["userfield"];
	$rp->userdata=$_POST["userdata"];
	$rp->Save();
	
}


function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
		
		
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'>
			<a href=\"miniadm.index.php\">{myaccount}</a>
			&nbsp;&raquo;&nbsp;<a href=\"miniadm.webstats-start.php?t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}\">{web_statistics}</a>
		</div>
		<H1>{member_wwwtrack}</H1>
		<p>{member_wwwtrack_text}</p>
	</div>	
	<div id='webstats-middle-$ff' class=BodyContent></div>
	
	<script>
		LoadAjax('webstats-middle-$ff','$page?webstats-middle=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&tablename={$_GET["tablename"]}&xtime={$_GET["xtime"]}');
		$jsadd
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function webstats_middle(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=500;
	$TB_WIDTH=910;
	$uid=$_GET["uid"];
		
	$t=time();
	$report=$tpl->javascript_parse_text("{report}");
	$explain=$tpl->_ENGINE_parse_body("{explain}");
	$new_report=$tpl->_ENGINE_parse_body("{new_report}");
	$execute_report_compilation_ask=$tpl->javascript_parse_text("{execute_report_compilation_ask}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	$buttons="
	buttons : [
	{name: '$new_report', bclass: 'Add', onpress : NewReport$t},
	{name: '$online_help', bclass: 'Help', onpress : ItemHelp$t},
	
	],	";
	$html="
	
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?items=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: 'ID', name : 'ID', width :38, sortable : true, align: 'center'},
		{display: '$report', name : 'report', width :299, sortable : true, align: 'left'},
		{display: '$explain', name : 'explain', width :378, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'none', width :31, sortable : true, align: 'left'},	
		{display: '&nbsp;', name : 'none', width :31, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'none', width :31, sortable : true, align: 'left'},
		
	
	],
	$buttons

	searchitems : [
		{display: '$report', name : 'report'},
	],
	sortname: 'report',
	sortorder: 'asc',
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
	s_PopUpFull('http://proxy-appliance.org/index.php?cID=332','1024','900');
}

function ReportDelete$t(ID){

}

var x_RunReport= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#flexRT$t').flexReload();
}

function RunReport(ID){
	if(confirm('$execute_report_compilation_ask')){
		var XHR = new XHRConnection();
		XHR.appendData('run',ID);
		XHR.sendAndLoad('$page', 'POST',x_RunReport);	
	
	}
}

function NewReport$t(){
	Loadjs('$page?report-js=yes&ID=0&t=$t');
}


</script>";
	
	echo $html;
	
	
	
}	
	
function report_items(){
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$users=new usersMenus();
	$sock=new sockets();
	
	
	$search='%';
	$tablemain="TrackMembers";
	
	$page=1;
	$FORCE_FILTER=null;
	$table="(SELECT ID,report,userfield,userdata,categories,
		sitename,duration,scheduled,csv,LENGTH(csvContent) as csvContentBytes FROM $tablemain) as t";
	
	if(!$q->TABLE_EXISTS($tablemain, $database)){json_error_show("$table doesn't exists...",1);}
	if($q->COUNT_ROWS($tablemain, $database)==0){json_error_show("No rules",1);}

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
	
	$rows_txt=$tpl->_ENGINE_parse_body("{rows}");
	$scheduled_text=$tpl->_ENGINE_parse_body("{scheduled}");
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$zmd5=md5(serialize($ligne));
		$color="black";
		$urljsSTAT=null;
		$delete=imgsimple("delete-24.png",null,"ReportDelete$t(ID)");
		$stats_img="statistics-32-grey.png";
		$rowsBlock_txt=null;
		$rowsFormat=null;
		$scheduled=null;
	//familysite 	size 	hits
	$ID_FIELD=$ligne["ID"];
	$report_settings_js="Loadjs('$MyPage?report-js=yes&ID={$ligne["ID"]}&t=$t');";
	$prctxt=null;
	$urljsSIT="<a href=\"javascript:blur();\" 
	OnClick=\"javascript:$report_settings_js\"
	style='font-size:14px;text-decoration:underline;color:$color'>";
	$ligne["report"]=utf8_encode($ligne["report"]);
	$rp=new squid_report($ligne["ID"]);
	
	$rows=$q->COUNT_ROWS("WebTrackMem{$ligne["ID"]}");
	$rowsBlock=$q->COUNT_ROWS("WebTrackMeB{$ligne["ID"]}");
	$explain=$tpl->_ENGINE_parse_body($rp->explain());
	
	$run=imgsimple("run-24.png","","RunReport({$ligne["ID"]})");
	
	
	if(is_file("ressources/logs/squid.report.{$ligne["ID"]}.rp")){
		$prc=trim(@file_get_contents("ressources/logs/squid.report.{$ligne["ID"]}.rp"));
		if($prc<100){
			$ID_FIELD="<img src='img/preloader.gif'>";
			$prctxt=$tpl->_ENGINE_parse_body("<br>{building_report}: <strong>{$prc}%</strong>");
			$run="<img src='img/preloader.gif'>";
		}
	}
	$rowsFormat=FormatNumber($rows);
	if($rows>0){
		$urljsSTAT="<a href=\"miniadm.MembersTrack.report.php?ID={$ligne["ID"]}\" style='font-size:14px;text-decoration:underline;color:$color'>";
		$stats_img="statistics-32.png";
	}
	
	if($rowsBlock>0){
		$rowsBlock=FormatNumber($rowsBlock);
		$rowsBlock_txt=$tpl->_ENGINE_parse_body("&nbsp;|$rowsBlock {blocked_rows}");
	}
	
	if($ligne["scheduled"]==1){
		$scheduled=" <i>($scheduled_text)</i>";
	}
	$csvContentBytes=null;
	if($ligne["csvContentBytes"]>0){
		$csvsize=FormatBytes($ligne["csvContentBytes"]/1024);
		$csvContentBytes="<br><a href=\"$MyPage?csv={$ligne["ID"]}\" style='font-size:12px;color:$color;text-decoration:underline'><strong>report{$ligne["ID"]}.csv.gz</a>&nbsp;<span style='font-size:11px'>($csvsize)</span></strong>";
	}
	
	$data['rows'][] = array(
		'id' => "{$ligne["ID"]}",
		'cell' => array(
			"<span style='font-size:14px;color:$color'>$urljsSIT$ID_FIELD</a></span>",
			"<span style='font-size:14px;color:$color'>$urljsSIT{$ligne["report"]}</a>$prctxt$scheduled$csvContentBytes</span>",
			"<span style='font-size:14px;color:$color'>$urljsSTAT$explain</span><div style='text-align:right'><i>$rowsFormat $rows_txt$rowsBlock_txt</i>$rp->error</div>",
			"<span style='font-size:14px;color:$color'>$urljsSTAT<img src='img/$stats_img'></a></span>",
			"<span style='font-size:14px;color:$color'>$run</span>",
	
			"<span style='font-size:14px;color:$color'>$delete</span>",
			)
		);
	}
	
	
echo json_encode($data);		
	
	
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){ 
	$tmp1 = round((float) $number, $decimals);
  while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
    $tmp1 = $tmp2;
  return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
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
	$tablename=$_GET["tablename"];	
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
	$tablename=$_GET["tablename"];

	$sql="SELECT SUM(hits) as thits, hour FROM $tablename GROUP BY hour ORDER BY hour";
	
	
	
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
			$gp->x_title=$tpl->_ENGINE_parse_body("{hours}");
			$gp->title=null;
			$gp->margin0=true;
			$gp->Fillcolor="blue@0.9";
			$gp->color="146497";
			$gp->line_green();
			
		if(is_file($targetedfile)){
			echo "<center>
			<div style='font-size:18px;margin-bottom:10px'>".$tpl->_ENGINE_parse_body("{hits}/{hours}")."</div>
			<img src='$targetedfile'></center>";
		}
	
	}
	
}

function csv_download(){
	$ID=$_GET["csv"];
	$rp=new squid_report($ID);
	$data=$rp->loadcsv();
	header('Content-type: application/gzip');
	header('Content-Transfer-Encoding: binary');
	header("Content-Disposition: attachment; filename=\"report{$ID}.csv.gz\"");	
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");	
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé	
	$fsize = strlen($data); 
	header("Content-Length: ".$fsize); 
	ob_clean();
	flush();
	echo($data);	
	
	
}

