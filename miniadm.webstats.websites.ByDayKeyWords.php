<?php
session_start();

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class=text-error>");
ini_set('error_append_string',"</p>");
if(!isset($_SESSION["uid"])){die();}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.calendar.inc");
if(!$_SESSION["AsWebStatisticsAdministrator"]){die();}

if(isset($_GET["graph1-1"])){graph1_1();exit;}
if(isset($_GET["graph1"])){graph1();exit;}
if(isset($_GET["graph2"])){graph2();exit;}
if(isset($_GET["graph3"])){graph3();exit;}
if(isset($_GET["graph4"])){graph4();exit;}
if(isset($_GET["graph5"])){graph5();exit;}

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["www-graĥs"])){section_graphs();exit;}
if(isset($_GET["www-graĥs-members"])){section_graphs_members();exit;}

if(isset($_GET["www-table"])){section_table_sites();exit;}
if(isset($_GET["words-search"])){words_search();exit;}



tabs();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$dateT=time_to_date($_GET["xtime"]);
	$category=urlencode($_GET["category"]);
	if($tpl->language=="fr"){$dateT=date("{l} d {F} ",$_GET["xtime"]);}	
	$title="{category} {$_GET["category"]} $dateT";
	$title=$tpl->javascript_parse_text("$title");
	$html="YahooWin3('900','$page?tabs=yes&category=$category&xtime={$_GET["xtime"]}','$title')";
	echo $html;
	
}

function tabs(){
	//$table=date("Ymd",$_GET["xtime"])."_hour";
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$date=time_to_date($_GET["xtime"]);
	$title=$tpl->_ENGINE_parse_body("$date {keywords}");
	$category=urlencode($_GET["category"]);
	$array["{graphs} {keywords}"]="$page?www-graĥs=yes&category=$category&xtime={$_GET["xtime"]}";
	$array["{keywords}"]="$page?www-table=yes&category=$category&xtime={$_GET["xtime"]}";
	echo "<H3>".$title."</H3>".$boot->build_tab($array);	
	
}
function suffix(){
	$t=$_GET["t"];
	$category=urlencode($_GET["category"]);
	$suffix="&t=$t&xtime={$_GET["xtime"]}";
	return $suffix;
}


function section_graphs(){
	$suffix=suffix();
	$t=time();
	$page=CurrentPageName();
	$html="
	<div class=BodyContent id='graph-$t'></div>
	<div class=BodyContent id='graph1-$t'></div>
	<div class=BodyContent id='graph2-$t'></div>

	
	<script>
		AnimateDiv('graph-$t');
		AnimateDiv('graph1-$t');
		AnimateDiv('graph2-$t');
		Loadjs('$page?graph1=yes&container=graph-$t$suffix');
		Loadjs('$page?graph1-1=yes&container=graph1-$t$suffix');
		Loadjs('$page?graph2=yes&container=graph2-$t$suffix');

	</script>
	";
	
	echo $html;
	
}
function section_graphs_members(){
	$suffix=suffix();
	$t=time();
	$page=CurrentPageName();
	$html="
	<div class=BodyContent id='graph-$t'></div>
	<div class=BodyContent id='graph2-$t'></div>
	
	
	
	<script>
	AnimateDiv('graph-$t');
	AnimateDiv('graph2-$t');
	
	Loadjs('$page?graph4=yes&container=graph-$t$suffix');
	Loadjs('$page?graph5=yes&container=graph2-$t$suffix');
	</script>
	";
	
	echo $html;	
	
}

function section_table_sites(){
	$suffix=suffix();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$form=$boot->SearchFormGen("sitename,words","words-search",$suffix);
	echo $form;	
	
	
}
function section_members_sites(){
	$suffix=suffix();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$form=$boot->SearchFormGen("uid","uid-search",$suffix);
	echo $form;	
	
	
}
function uid_search(){
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
	$ORDER="ORDER BY size DESC";
	
	if(!$q->TABLE_EXISTS($table, $database)){senderror("$table doesn't exists...");}
	if($q->COUNT_ROWS($table, $database)==0){senderror("No data");}
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$search=string_to_flexquery("uid-search");
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	$category=mysql_escape_string2($_GET["category"]);
	$sql="SELECT SUM(size) as size, sum(hits) as hits,uid,category FROM $table
	GROUP BY uid,category HAVING `category`='$category' $search $ORDER LIMIT 0,500";
	
	
	
	$results = $q->QUERY_SQL($sql,$database);
	
	
	if(!$q->ok){senderror($q->mysql_error."<br>$sql");}
	
	while ($ligne = mysql_fetch_assoc($results)) {
	
		$color="black";
		$ligne["hits"]=numberFormat($ligne["hits"],0,""," ");
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
	
	
		$link=$boot->trswitch($jslink);
		$tr[]="
		<tr id='$id'>
		<td $link><i class='icon-user'></i>&nbsp;{$ligne["uid"]}</a></td>
		<td $link><i class='icon-info-sign'></i>&nbsp;{$ligne["size"]}</td>
		<td $link><i class='icon-info-sign'></i>&nbsp;{$ligne["hits"]}</td>
		</tr>";
	}
	
		echo $tpl->_ENGINE_parse_body("
	
				<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th>{member}</th>
					<th>{size}</th>
					<th>{hits}</th>
				</tr>
			</thead>
			 <tbody>
				").@implode("", $tr)."</tbody></table>";
	
	
}



function words_search(){
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$users=new usersMenus();
	$sock=new sockets();
	$boot=new boostrap_form();
	$database="squidlogs";
	$search='%';
	$table="searchwordsD_".date("Ymd",$_GET["xtime"]);
	
	$rp=250;
	$page=1;
	$FORCE_FILTER=null;
	$ORDER="ORDER BY hits DESC";
	
	if(!$q->TABLE_EXISTS($table, $database)){senderror("$table doesn't exists...");}
	if($q->COUNT_ROWS($table, $database)==0){senderror("No data");}
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$search=string_to_flexquery("words-search");
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	$sql="SELECT sum(hits) as hits,familysite,words,uid FROM $table
	GROUP BY familysite,words,uid HAVING 1 $search $ORDER LIMIT 0,500";
	

	
	$results = $q->QUERY_SQL($sql,$database);
	
	
	if(!$q->ok){senderror($q->mysql_error."<br>$sql");}
	
	while ($ligne = mysql_fetch_assoc($results)) {
	
		$color="black";
		$ligne["hits"]=numberFormat($ligne["hits"],0,""," ");
		
		if(strlen($ligne["words"])>128){
			$ligne["words"]=substr($ligne["words"],0, 128)."...";
			$ligne["words"]=utf8_encode($ligne["words"]);
		}	
	
			//$jsSitename=$boot->trswitch("Loadjs('miniadm.webstats.websites.ByDayBySiteName.php?sitename={$ligne["sitename"]}&xtime={$_GET["xtime"]}')");
			$tr[]="
				<tr id='$id'>
					<td $jsSitename><i class='icon-globe'></i>&nbsp;{$ligne["familysite"]}</a></td>
					<td><i class='icon-info-sign'></i>&nbsp;{$ligne["words"]}</td>
					<td><i class='icon-info-sign'></i>&nbsp;{$ligne["uid"]}</td>
					<td><i class='icon-info-sign'></i>&nbsp;{$ligne["hits"]}</td>
				</tr>";
	}
	
	echo $tpl->_ENGINE_parse_body("
	
		<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th>{website}</th>
					<th>{words}</th>
					<th>{members}</th>
					<th>{hits}</th>
				</tr>
			</thead>
			 <tbody>
				").@implode("", $tr)."</tbody></table>";	
	
	
}


function graph1(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
	
	$tablename="searchwordsD_".date("Ymd",$_GET["xtime"]);
	$category=mysql_escape_string2($_GET["category"]);	
	
	
	if($_GET["category"]=="unknown"){$_GET["category"]=null;}
	
	$sql="SELECT SUM(hits) as thits, `hour` FROM $tablename GROUP BY `hour`
	ORDER BY `hour`";



	$c=0;
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);}
	if(mysql_num_rows($results)>0){

		$nb_events=mysql_num_rows($results);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$xdata[]=$ligne["hour"];
			$ydata[]=$ligne["thits"];
			$c++;
		}
	}

	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title="{searchs}/{hour}";
	$highcharts->yAxisTtitle="{hits}";
	$highcharts->xAxisTtitle="{hours}";
	$highcharts->datas=array("{hits}"=>$ydata);
	echo $highcharts->BuildChart();


}



function graph1_1(){

	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();

	$tablename="searchwordsD_".date("Ymd",$_GET["xtime"]);
	
	$sql="SELECT SUM(hits) as thits, familysite FROM `$tablename` 
	GROUP BY familysite
	ORDER BY thits DESC LIMIT 0,10";
	$unknown=$tpl->_ENGINE_parse_body("{unknown}");
	$c=0;
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(trim($ligne["familysite"])==null){$ligne["sitename"]=$unknown;}
		$PieData[$ligne["familysite"]]=$ligne["thits"];
		$c++;
	}


	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{hits}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_searchengines_by_hits}");
	echo $highcharts->BuildChart();

}

function graph2(){

	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();

	$tablename="searchwordsD_".date("Ymd",$_GET["xtime"]);

	$sql="SELECT SUM(hits) as thits, words FROM `$tablename`
	GROUP BY words
	ORDER BY thits DESC LIMIT 0,10";
	$unknown=$tpl->_ENGINE_parse_body("{unknown}");
	$c=0;
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
	
	$ligne["thits"]=$ligne["thits"];
	$PieData[$ligne["words"]]=$ligne["thits"];
	$c++;
	}


	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{hits}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_words_by_hits}");
	echo $highcharts->BuildChart();

}
