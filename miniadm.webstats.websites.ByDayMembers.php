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
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
if(!$_SESSION["AsWebStatisticsAdministrator"]){header("location:miniadm.index.php");die();}
	

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["messaging-right"])){messaging_right();exit;}
if(isset($_GET["webstats-middle"])){webstats_middle();exit;}
if(isset($_GET["graph"])){generate_graph();exit;}
if(isset($_GET["webstats_middle_table"])){webstats_middle_table();exit;}
if(isset($_GET["items"])){webstats_middle_table_items();exit;}
if(isset($_GET["generate-graph-final"])){generate_graph_final();exit;}
if(isset($_POST["NoCategorizedAnalyze"])){NoCategorizedAnalyze();exit;}
if(isset($_GET['tabs'])){tabs();exit;}

main_page();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&tablename={$_GET["tablename"]}&xtime={$_GET["xtime"]}')</script>", $content);
	echo $content;	
}

function tabs(){
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$tpl=new templates();
	if(!is_numeric($_GET["xtime"])){
		$_GET["xtime"]=strtotime("{$_GET["year"]}-{$_GET["month"]}-{$_GET["day"]} 00:00:00");
		
	}
	
	$dateT=time_to_date($_GET["xtime"]);
	
	if(isset($_GET["xtime"])){
		$_GET["year"]=date("Y",$_GET["xtime"]);
		$_GET["month"]=date("m",$_GET["xtime"]);
		$_GET["day"]=date("d",$_GET["xtime"]);
		$_GET["tablename"]=date("Ymd",$_GET["xtime"])."_members";
	}	
	
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS($_GET["tablename"])){
		
		senderror("{table_does_not_exists} {$_GET["tablename"]} {use_the_tools_section}");
		
	}
	
	
	$t=$_GET["t"];
	$display_members_for_this_day=$tpl->javascript_parse_text("$dateT: {display_members_for_this_day}");
	
	$subtitle="<a href=\"javascript:blur();\" OnClick=\"Loadjs(\'miniadm.webstats.php?calendar-js=yes&div=tab-$t&prefix=tabs=yes&t=$t&source-page=$page\')\">$display_members_for_this_day</a>";
	
	
	$title="<script>
			document.getElementById('MembersSubtitlePage').innerHTML='$subtitle';
		</script>";
	
	if(isset($_GET["title"])){
		$title="<H3>$display_members_for_this_day</H3>";
	}
	
	$array["{uid}"]="$page?webstats-middle=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&tablename={$_GET["tablename"]}&xtime={$_GET["xtime"]}&FILTER=uid');";
	$array["{ipaddr}"]="$page?webstats-middle=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&tablename={$_GET["tablename"]}&xtime={$_GET["xtime"]}&FILTER=client');";
	$array["{MAC}"]="$page?webstats-middle=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&tablename={$_GET["tablename"]}&xtime={$_GET["xtime"]}&FILTER=MAC');";
	$array["{hostname}"]="$page?webstats-middle=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&tablename={$_GET["tablename"]}&xtime={$_GET["xtime"]}&FILTER=hostname');";
	echo $title.$boot->build_tab($array);
	
	
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
	$H1[]="{statistics} &laquo;{members}&raquo;";	
	
		$dateT=date("{l} {F} d",$_GET["xtime"]);
		if($tpl->language=="fr"){
			$dateT=date("{l} d {F} ",$_GET["xtime"]);
		}
		
	if(isset($_GET["xtime"])){
		$_GET["year"]=date("Y",$_GET["xtime"]);
		$_GET["month"]=date("m",$_GET["xtime"]);
		$_GET["day"]=date("d",$_GET["xtime"]);	
	}
	

	$html="
	<div class=BodyContent>
		<div style='font-size:14px'>
			<a href=\"miniadm.index.php\">{myaccount}</a>
			&nbsp;&raquo;&nbsp;<a href=\"miniadm.webstats.php?t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}\">{web_statistics}</a>
		</div>
		<H1>". @implode(", ", $H1)."</H1>
		<h2 style='font-size:16px' id='MembersSubtitlePage'></h2>
	</div>
	<div style='font-size:16px;' class=BodyContent id='tab-$ff'></div>

	
	
	
	<script>
		LoadAjax('tab-$ff','$page?tabs=yes&t=$ff&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&tablename={$_GET["tablename"]}&xtime={$_GET["xtime"]}');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function suffix2(){
	$t=$_GET["t"];	
	return "&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&tablename={$_GET["tablename"]}&xtime={$_GET["xtime"]}&FILTER={$_GET["FILTER"]}";
}

function webstats_middle(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
	$suffix=suffix2();
	$html="
	<div class=BodyContent id='graph-$ff'></div>
	<div class=BodyContent id='table-$ff'></div>
	
	
	<script>
		LoadAjax('graph-$ff','$page?graph=yes$suffix&container=graph-$ff');
		LoadAjax('table-$ff','$page?webstats_middle_table=yes$suffix');
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
	
	
	$sql="SELECT COUNT(client) as tcount FROM $tablename WHERE LENGTH(ipaddr)>0";
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
	$boot=new boostrap_form();
	$tablename_members=date("Ymd",$_GET["xtime"])."_members";
	if($_GET["FILTER"]==null){$_GET["FILTER"]=which_filter($tablename_members);}
	$SearchQuery=$boot->SearchFormGen("{$_GET["FILTER"]}","items","&t={$_GET["t"]}&uid=$uid&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&tablename={$_GET["tablename"]}&xtime={$_GET["xtime"]}&FILTER={$_GET["FILTER"]}");
	echo $SearchQuery;
	
	
	
	
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
	$tablename_members=date("Ymd",$_GET["xtime"])."_members";
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
	
	$searchstring=string_to_flexquery("items");
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	$boot=new boostrap_form();
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER ORDER BY size DESC LIMIT 0,150";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
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
	$urljs="Loadjs('squid.members.zoom.php?table=$dansguardian_events&field={$_GET["FILTER"]}&value={$ligne["{$_GET["FILTER"]}"]}')";	
	$link=$boot->trswitch($urljs);
	$tr[]="
	<tr id='$id'>
	<td $link><i class='icon-user'></i> {$ligne["name"]}</a>{$ligne["{$_GET["FILTER"]}"]}</td>
	<td $link><i class='icon-globe'></i> {$ligne["hits"]}</td>
	<td $link><i class='icon-globe'></i> {$ligne["size"]}</td>
	</tr>";
	

	}
	
	
echo $tpl->_ENGINE_parse_body("
		
		<table class='table table-bordered table-hover'>
		
			<thead>
				<tr>
					<th>{{$_GET["FILTER"]}}</th>
					<th>{hits}</th>
					<th>{size}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>
		</table>";		
	
	
}
function generate_graph(){
	
	$page=CurrentPageName();
	$t=time();
	$html="<div style='width:920px;height:400px' id='$t'></div>
	<script>
		Loadjs('$page?generate-graph-final=yes&xtime={$_GET["xtime"]}&FILTER={$_GET["FILTER"]}&container=$t');
	</script>
	";
	echo $html;
	
}


function generate_graph_final(){
	include_once('ressources/class.artica.graphs.inc');
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
	$xtime=$_GET["xtime"];
	$tablename=date("Ymd",$_GET["xtime"])."_members";
	if($_GET["FILTER"]==null){$_GET["FILTER"]=which_filter($tablename);}
	$FILTER=$tpl->_ENGINE_parse_body("{{$_GET["FILTER"]}}");	
	$sql="SELECT COUNT({$_GET["FILTER"]}) as tcount, hour FROM $tablename GROUP BY hour ORDER BY hour";
	switch ($_GET["FILTER"]) {
		case "client":$subtitle="{ipaddr}";break;
		case "uid":$subtitle="{member}";break;
		default:$subtitle="{{$_GET["FILTER"]}}";
			;
		break;
	}
	

	$c=0;
	$results=$q->QUERY_SQL($sql);
	if(mysql_num_rows($results)>0){
		$nb_events=mysql_num_rows($results);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$xdata[]=$ligne["hour"];
			$ydata[]=$ligne["tcount"];
			$c++;
		}
	}	
				
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title="{statistics} ". $tpl->_ENGINE_parse_body("$subtitle/{hours}");
	$highcharts->yAxisTtitle="{members}";
	$highcharts->xAxisTtitle="{hours}";
	$highcharts->datas=array("{members}"=>$ydata);
	echo $highcharts->BuildChart();
			
	
	
}

