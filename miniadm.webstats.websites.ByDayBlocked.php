<?php
session_start();
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class=text-error>");
ini_set('error_append_string',"</p>");
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
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
if(isset($_GET["www-graĥs"])){section_graphs();exit;}
if(isset($_GET["graph0"])){graph0();exit;}
if(isset($_GET["graph1"])){graph1();exit;}
if(isset($_GET["graph2"])){graph2();exit;}
if(isset($_GET["graph3"])){graph3();exit;}

if(isset($_GET["www-table"])){section_table();exit;}
if(isset($_GET["www-search"])){section_search();exit;}

if(isset($_GET["www-members"])){section_members();exit;}
if(isset($_GET["members-search"])){section_members_search();exit;}



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
		$content=str_replace("{SCRIPT}", "<script>alert('$onlycorpavailable');document.location.href='miniadm.webstats.php';</script>", $content);
		echo $content;	
		return;
	}		
	
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&tablename={$_GET["tablename"]}&xtime={$_GET["xtime"]}')</script>", $content);
	echo $content;	
}
function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
	$tablename=$_GET["tablename"];
	$xtime=$_GET["xtime"];
	$tablename_members=date("Ymd",$xtime)."_blocked";
	$q=new mysql_squid_builder();
	$jsadd="LoadAjax('statistics-$t','$page?webstats-stats=yes');";

	
	$q=new mysql_squid_builder();
	$dansguardian_events="dansguardian_events_".date("Ymd",$xtime);
	$sql="SELECT totalBlocked,MembersCount,requests,totalsize,not_categorized,YouTubeHits FROM tables_day WHERE tablename='$dansguardian_events'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));	
	$NotCategorized=$ligne["not_categorized"];
	$SumSize=$ligne["totalsize"];
	$SumHits=$ligne["requests"];
	$MembersCount=$ligne["MembersCount"];
	$YouTubeHits=$ligne["YouTubeHits"];	
	$BlockedCount=$ligne["totalBlocked"];
		
	$H1[]="$BlockedCount {blocked_events}";	
	
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
		<p>$dateT: {display_blocked_events}</p>
	</div>	
	<div id='webstats-middle-$ff'></div>
	
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
$t=time();

$dateT=time_to_date($_GET["xtime"]);
if(isset($_GET["title"])){
	$title=$tpl->_ENGINE_parse_body("$dateT: {display_blocked_events}");
}

$suffix=suffix();
$boot=new boostrap_form();
$date=time_to_date($_GET["xtime"]);
$array["{graphs}"]="$page?www-graĥs=yes$suffix";
$array["{websites}"]="$page?www-table=yes$suffix";
$array["{members}"]="$page?www-members=yes$suffix";
echo "<H3>".$title."</H3>".$boot->build_tab($array);
}

function section_graphs(){
	$suffix=suffix();
	$t=time();
	$page=CurrentPageName();
	$html="
	<div class=BodyContent id='graph-$t'></div>
	<div class=BodyContent id='graph1-$t'></div>
	<div class=BodyContent id='graph2-$t'></div>
	<div class=BodyContent id='graph3-$t'></div>


	<script>
	AnimateDiv('graph0-$t');
	AnimateDiv('graph1-$t');
	AnimateDiv('graph2-$t');
	AnimateDiv('graph3-$t');
	Loadjs('$page?graph0=yes&container=graph-$t$suffix');
	Loadjs('$page?graph1=yes&container=graph1-$t$suffix');
	Loadjs('$page?graph2=yes&container=graph2-$t$suffix');
	Loadjs('$page?graph3=yes&container=graph3-$t$suffix');
	</script>
	";

	echo $html;

}


function suffix(){
	$t=$_GET["t"];
	$suffix="&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&tablename={$_GET["tablename"]}&xtime={$_GET["xtime"]}";
	return $suffix;
}

function section_table(){
	$boot=new boostrap_form();
	call_user_func(BECALL);
	echo $boot->SearchFormGen("website,category","www-search",suffix());	
	
}

function section_members(){
	$boot=new boostrap_form();
	call_user_func(BECALL);
	echo $boot->SearchFormGen("client,uid,MAC,hostname","members-search",suffix());
		
}
function section_members_search(){
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$users=new usersMenus();
	$sock=new sockets();
	$xtime=$_GET["xtime"];
	$tablename_prod=date("Ymd",$xtime)."_blocked";
	$tablename=$_GET["tablename"];
	$subtable="( SELECT COUNT(ID) as hits,client,uid,MAC,hostname FROM `$tablename_prod`
	GROUP BY client,uid,MAC,hostname ORDER BY hits DESC) as t";
	$search='%';
			$table=$subtable;
	
			$page=1;
			$FORCE_FILTER=null;
	
	
			if(!$q->TABLE_EXISTS($tablename_prod)){senderror("$table doesn't exists...");}
			if($q->COUNT_ROWS($tablename_prod)==0){senderror("No data");}
			$searchstring=string_to_flexquery($_GET["www-search"]);
	
			$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER ORDER BY hits DESC LIMIT 0,500";
			$results = $q->QUERY_SQL($sql);
			if(!$q->ok){senderror($q->mysql_error);}
	
			while ($ligne = mysql_fetch_assoc($results)) {
				$zmd5=md5(serialize($ligne));
				$color="black";
				$ligne["hits"]=numberFormat($ligne["hits"],0,""," ");
				if($ligne["uid"]=="-"){$ligne["uid"]=null;}
				if($ligne["client"]=="-"){$ligne["client"]=null;}
	
				$boot=new boostrap_form();
				$urljs=null;
	
				//$truri=$boot->trswitch($urljsSIT);
				
				$tr[]="
				<tr $truri>
				<td ><i class='icon-user'></i>&nbsp;{$ligne["uid"]}</td>
				<td ><i class='icon-user'></i>&nbsp;{$ligne["client"]}</td>
				<td ><i class='icon-user'></i>&nbsp;{$ligne["hostname"]}</td>
				<td ><i class='icon-user'></i>&nbsp;{$ligne["MAC"]}</td>
				<td width=1% align=center nowrap>{$ligne["hits"]}</td>
				</tr>
				";
	}
				echo $tpl->_ENGINE_parse_body("
			<table class='table table-bordered table-hover'>
			<thead>
			<tr>
			<th>{uid}</th>
			<th>{ipaddr}</th>
			<th>{hostname}</th>
			<th>{MAC}</th>						
			<th>{hits}</th>
			</tr>
			</thead>
			<tbody>
				").@implode("\n", $tr)."
						</tbody>
				</table>	";
	
}


function section_search(){
	
	

	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$users=new usersMenus();
	$sock=new sockets();
	$xtime=$_GET["xtime"];
	$tablename_prod=date("Ymd",$xtime)."_blocked";
	$tablename=$_GET["tablename"];
	$subtable="( SELECT COUNT(ID) as hits,website,category FROM `$tablename_prod` 
	GROUP BY website,category ORDER BY hits DESC) as t";
	$search='%';
	$table=$subtable;
	
	$page=1;
	$FORCE_FILTER=null;
	
	
	if(!$q->TABLE_EXISTS($tablename_prod)){senderror("$table doesn't exists...");}
	if($q->COUNT_ROWS($tablename_prod)==0){senderror("No data");}
	$searchstring=string_to_flexquery($_GET["www-search"]);
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){senderror($q->mysql_error);}	

	while ($ligne = mysql_fetch_assoc($results)) {
	$zmd5=md5(serialize($ligne));
	$color="black";
	$ligne["hits"]=numberFormat($ligne["hits"],0,""," ");
	if($ligne["uid"]=="-"){$ligne["uid"]=null;}
	
	$boot=new boostrap_form();
	$urljs=null;

	$truri=$boot->trswitch($urljsSIT);
	$ligne["hits"]=numberFormat($ligne["hits"],0,""," ");
	$tr[]="
	<tr $truri>
	<td ><i class='icon-globe'></i>&nbsp;{$ligne["website"]}</td>
	<td width=1% align=center nowrap>{$ligne["category"]}</td>
	<td width=1% align=center nowrap>{$ligne["hits"]}</td>
	</tr>
	";
	}
	echo $tpl->_ENGINE_parse_body("
			<table class='table table-bordered table-hover'>
			<thead>
			<tr>
			<th>{websites}</th>
			<th>{category}</th>
			<th>{hits}</th>
			</tr>
			</thead>
			<tbody>
			").@implode("\n", $tr)."
			</tbody>
			</table>";	
	
}

function graph0(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
	
	$tablename=date("Ymd",$_GET["xtime"])."_blocked";
	$sql="SELECT COUNT(ID) as thits,HOUR(zDate) as `thour` FROM $tablename GROUP BY `hour` ORDER BY `hour`";
	
	
	$c=0;
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){senderror("$q->mysql_error<br>$sql");}
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
	$highcharts->Title="{display_blocked_events}: {hits}/{hour}";
	$highcharts->yAxisTtitle="{hits}";
	$highcharts->xAxisTtitle="{hours}";
	$highcharts->datas=array("{hits}"=>$ydata);
	echo $highcharts->BuildChart();	
	
}
function graph1(){

	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();

	$tablename=date("Ymd",$_GET["xtime"])."_blocked";
	
	
	
	$sql="SELECT COUNT(ID) as thits, category FROM `$tablename`
	GROUP BY category
	ORDER BY thits DESC LIMIT 0,10";

	$c=0;
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		
		$PieData[$ligne["category"]]=$ligne["thits"];
		$c++;
	}


	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{hits}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_categories_by_hits}");
	echo $highcharts->BuildChart();

}
function graph2(){

	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();

	$tablename=date("Ymd",$_GET["xtime"])."_blocked";



	$sql="SELECT COUNT(ID) as thits, website FROM `$tablename`
	GROUP BY website
	ORDER BY thits DESC LIMIT 0,10";

	$c=0;
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){

		$PieData[$ligne["website"]]=$ligne["thits"];
		$c++;
	}


	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{hits}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_websites_by_hits}");
	echo $highcharts->BuildChart();

}
function graph3(){

	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();

	$tablename=date("Ymd",$_GET["xtime"])."_blocked";



	$sql="SELECT COUNT(ID) as thits, uid FROM `$tablename`
	GROUP BY uid
	ORDER BY thits DESC LIMIT 0,10";

	$c=0;
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){

		$PieData[$ligne["uid"]]=$ligne["thits"];
		$c++;
	}


	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{hits}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_members_by_hits} ({uid})");
	echo $highcharts->BuildChart();

}


