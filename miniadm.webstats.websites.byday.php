<?php
session_start();

ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',"<p class=text-error>");
ini_set('error_append_string',"<p>");
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.calendar.inc");
if(!$_SESSION["AsWebStatisticsAdministrator"]){header("location:miniadm.index.php");die();}
	
if(isset($_GET["direct"])){content();exit;}
if(isset($_GET["content"])){content();exit;}
if(isset($_GET["messaging-right"])){messaging_right();exit;}
if(isset($_GET["webstats-middle"])){webstats_middle();exit;}
if(isset($_GET["graph0"])){graph0();exit;}
if(isset($_GET["graph1"])){graph1();exit;}
if(isset($_GET["graph2"])){graph2();exit;}
if(isset($_GET["graph3"])){graph3();exit;}
if(isset($_GET["graph4"])){graph4();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["stats"])){section_graphs();exit;}
if(isset($_GET["datas"])){section_datas();exit;}
if(isset($_GET["data-search"])){section_datas_search();exit;}
if(isset($_GET["categories"])){section_categories();exit;}
if(isset($_GET["categories-search"])){section_categories_search();exit;}
if(isset($_GET["dump-table-search"])){section_dump_table_search();exit;}
if(isset($_GET["dump-table"])){section_dump_table();exit;}


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
			
	$SumSize=FormatBytes($SumSize/1024);
	$SumHits=numberFormat($SumHits,0,""," ");
		
		
	$dateT=date("{l} {F} d",$_GET["xtime"]);
	if($tpl->language=="fr"){$dateT=date("{l} d {F} ",$_GET["xtime"]);}
		
	if(isset($_GET["xtime"])){
		$_GET["year"]=date("Y",$_GET["xtime"]);
		$_GET["month"]=date("m",$_GET["xtime"]);
		$_GET["day"]=date("d",$_GET["xtime"]);	
	}	

	if(isset($_GET["direct"])){
		echo $tpl->_ENGINE_parse_body("<H4>{display_visited_websites}</H4>
				<p>$SumSize {downloaded_size}, $SumHits {hits}</p>
				<div id='webstats-middle-$ff'></div>
				<script>
					LoadAjax('webstats-middle-$ff','$page?tabs=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&tablename={$_GET["tablename"]}&xtime={$_GET["xtime"]}');
		
				</script>
				");
		return;
	}
		
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'>
			<a href=\"miniadm.index.php\">{myaccount}</a>
			&nbsp;&raquo;&nbsp;<a href=\"miniadm.webstats.php?t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}\">{web_statistics}</a>
		</div>
		<H1>$SumSize {downloaded_size}, $SumHits {hits}</H1>
		<p>$dateT: {display_visited_websites}</p>
	</div>	
	<div id='webstats-middle-$ff'></div>
	
	<script>
		LoadAjax('webstats-middle-$ff','$page?tabs=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&tablename={$_GET["tablename"]}&xtime={$_GET["xtime"]}');
		$jsadd
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function tabs(){

	$suffix=suffix();
	$page=CurrentPageName();
	$array["{statistics}"]="$page?stats=yes$suffix";
	$array["{datas} {websites}"]="$page?datas=yes$suffix";
	$array["{datas} {categories}"]="$page?categories=yes$suffix";
	$array["{dump_table}"]="$page?dump-table=yes$suffix";
	$boot=new boostrap_form();
	echo $boot->build_tab($array);
}

function section_graphs(){
	$suffix=suffix();
	$t=time();
	$page=CurrentPageName();
	$html="
	<div class=BodyContent id='graph0-$t'></div>
	<div class=BodyContent id='graph4-$t'></div>
	<div class=BodyContent id='graph-$t'></div>
	<div class=BodyContent id='graph2-$t'></div>
	<div class=BodyContent id='graph3-$t'></div>
	
	
	<script>
		AnimateDiv('graph0-$t');
		AnimateDiv('graph4-$t');
		AnimateDiv('graph-$t');
		AnimateDiv('graph2-$t');
		AnimateDiv('graph3-$t');
		
		function Start1$t(){
			Loadjs('$page?graph0=yes&container=graph0-$t$suffix');
		}
		function Start2$t(){
			Loadjs('$page?graph4=yes&container=graph4-$t$suffix');
		}
		function Start3$t(){
			Loadjs('$page?graph1=yes&container=graph-$t$suffix');
		}
		function Start4$t(){
			Loadjs('$page?graph2=yes&container=graph2-$t$suffix');
		}		
		function Start5$t(){
			Loadjs('$page?graph3=yes&container=graph3-$t$suffix');
		}		
		
		setTimeout('Start1$t()',500);
		setTimeout('Start2$t()',1000);
		setTimeout('Start3$t()',1500);
		setTimeout('Start4$t()',2000);
		setTimeout('Start5$t()',2500);
	</script>
	";
	
	echo $html;
	
	
}

function suffix(){
	$t=$_GET["t"];
	$suffix="&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&tablename={$_GET["tablename"]}&xtime={$_GET["xtime"]}";
	return $suffix;	
}

function section_datas(){
	
	if(!$_SESSION["CORP"]){
		$tpl=new templates();
		$onlycorpavailable=$tpl->_ENGINE_parse_body("{onlycorpavailable}");
		"<p class=text-error>$onlycorpavailable</p>";
		return;
	}
	
	$suffix=suffix();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$form=$boot->SearchFormGen("sitename","data-search",$suffix);
	echo $form;	
	
}

function section_dump_table(){
	$suffix=suffix();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$q=new mysql_squid_builder();
	$table=date("Ymd",$_GET["xtime"])."_hour";
	
	$TABLE_GET_COLUMNS=$q->TABLE_GET_COLUMNS($table);
	$TABLE_GET_COLUMNS_P=@implode(",", $TABLE_GET_COLUMNS);
	$form=$boot->SearchFormGen("$TABLE_GET_COLUMNS_P","dump-table-search",$suffix);
	echo $form;	
	
}


function section_categories(){
	
	if(!$_SESSION["CORP"]){
		$tpl=new templates();
		$onlycorpavailable=$tpl->_ENGINE_parse_body("{onlycorpavailable}");
		"<p class=text-error>$onlycorpavailable</p>";
		return;
	}
	
	$suffix=suffix();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$form=$boot->SearchFormGen("category,familysite","categories-search",$suffix);
	echo $form;	
	
}
	
function section_datas_search(){
	
	if(!$_SESSION["CORP"]){
		$tpl=new templates();
		$onlycorpavailable=$tpl->_ENGINE_parse_body("{onlycorpavailable}");
		"<p class=text-error>$onlycorpavailable</p>";
		return;
	}
	
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$users=new usersMenus();
	$sock=new sockets();
	$boot=new boostrap_form();
	
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
	
	$search=string_to_flexquery("data-search");
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $search $FORCE_FILTER $ORDER $limitSql";	
	$results = $q->QUERY_SQL($sql,$database);

	
	if(!$q->ok){senderror($q->mysql_error);}	

	while ($ligne = mysql_fetch_assoc($results)) {
		
		$color="black";
		$jslink="Loadjs('squid.website-zoom.php?js=yes&sitename={$ligne["sitename"]}&xtime={$_GET["xtime"]}');";
	
		$ligne["hits"]=numberFormat($ligne["hits"],0,""," ");
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
		$ligne["familysite"]=$q->GetFamilySites($ligne["sitename"]);
		$jsFamily=$boot->trswitch("Loadjs('miniadm.webstats.websites.ByDayByFamilySite.php?familysite={$ligne["familysite"]}&xtime={$_GET["xtime"]}')");
		$jsSitename=$boot->trswitch("Loadjs('miniadm.webstats.websites.ByDayBySiteName.php?sitename={$ligne["sitename"]}&xtime={$_GET["xtime"]}')");
		$link=$boot->trswitch($jslink);
		$tr[]="
		<tr id='$id'>
		<td $jsSitename><i class='icon-globe'></i>&nbsp;{$ligne["sitename"]}</a></td>
		<td $jsFamily><i class='icon-info-sign'></i>&nbsp;{$ligne["familysite"]}</td>
		<td $link><i class='icon-info-sign'></i>&nbsp;{$ligne["size"]}</td>
		<td $link><i class='icon-info-sign'></i>&nbsp;{$ligne["hits"]}</td>
		</tr>";
	}	

	echo $tpl->_ENGINE_parse_body("
	
		<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th>{website}</th>
					<th>{familysite}</th>
					<th>{size}</th>
					<th>{hits}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("", $tr)."</tbody></table>";
}

function section_dump_table_search(){
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$users=new usersMenus();
	$sock=new sockets();
	$boot=new boostrap_form();
	$table=date("Ymd",$_GET["xtime"])."_hour";
	$searchstring=string_to_flexquery("dump-table-search");
	$ORDER=$boot->TableOrder(array("size"=>"DESC"));
	$sql="SELECT * FROM $table WHERE 1 $searchstring ORDER BY $ORDER  LIMIT 0,250";
	$results = $q->QUERY_SQL($sql);
	$TABLE_GET_COLUMNS=$q->TABLE_GET_COLUMNS($table);
	if(!$q->ok){senderrors($q->mysql_error."<br>$sql");}
	

	
	while ($ligne = mysql_fetch_assoc($results)) {
		$md=md5(serialize($ligne));
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
		$sitenameenc=urlencode($ligne["familysite"]);
		$js="Loadjs('miniadm.webstats.familysite.all.php?familysite=$sitenameenc')";
		$link=$boot->trswitch($js);
		$tr[]="<tr id='$md'>";
		reset($TABLE_GET_COLUMNS);
		while (list ($index,$field ) = each ($TABLE_GET_COLUMNS) ){
			$tr[]="<td style='font-size:11px' width=1% nowrap>{$ligne[$field]}</td>";
			
		}
		$tr[]="</tr>";
		
		
		
		
		
	}
	
	reset($TABLE_GET_COLUMNS);
	while (list ($index,$field ) = each ($TABLE_GET_COLUMNS) ){
		$array[$field]=$field;
			
	}
	
	echo $boot->TableCompile($array,$tr);
	
}

function section_categories_search(){
	
	if(!$_SESSION["CORP"]){
		$tpl=new templates();
		$onlycorpavailable=$tpl->_ENGINE_parse_body("{onlycorpavailable}");
		"<p class=text-error>$onlycorpavailable</p>";
		return;
	}
	
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$users=new usersMenus();
	$sock=new sockets();
	$boot=new boostrap_form();
	
	$search='%';
	$table=date("Ymd",$_GET["xtime"])."_hour";
	$rp=250;
	$page=1;
	$FORCE_FILTER=null;
	$database="squidlogs";
	$ORDER="ORDER BY size DESC";
	
	if(!$q->TABLE_EXISTS($table, $database)){senderror("$table doesn't exists...");}
	if($q->COUNT_ROWS($table, $database)==0){senderror("No data");}
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$search=string_to_flexquery("data-search");
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$subtable="(SELECT category,familysite, SUM(size) as size,SUM(hits) as hits FROM $table GROUP BY familysite,category) as t";
	
	$sql="SELECT *  FROM $subtable WHERE 1 $search $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,$database);
	
	
	if(!$q->ok){senderror($q->mysql_error."<br>$sql");}
	
	while ($ligne = mysql_fetch_assoc($results)) {
	
	$color="black";
		$jslink="Loadjs('squid.website-zoom.php?js=yes&sitename={$ligne["familysite"]}&xtime={$_GET["xtime"]}');";
		
		
		
		
			$ligne["hits"]=numberFormat($ligne["hits"],0,""," ");
			$ligne["size"]=FormatBytes($ligne["size"]/1024);
			$cateEnc=urlencode($ligne["category"]);
			$linkCat=$boot->trswitch("Loadjs('miniadm.webstats.websites.ByDayByCategory.php?category=$cateEnc&xtime={$_GET["xtime"]}')");
			$jsFamily=$boot->trswitch("Loadjs('miniadm.webstats.websites.ByDayByFamilySite.php?familysite={$ligne["familysite"]}&xtime={$_GET["xtime"]}')");
			
			$link=$boot->trswitch($jslink);
					$tr[]="
					<tr>
					<td $jsFamily><i class='icon-info-sign'></i>&nbsp;{$ligne["familysite"]}</td>
					<td $linkCat><i class='icon-info-sign'></i>&nbsp;{$ligne["category"]}</td>
					<td><i class='icon-info-sign'></i>&nbsp;{$ligne["size"]}</td>
					<td><i class='icon-info-sign'></i>&nbsp;{$ligne["hits"]}</td>
					</tr>";
	}
	
	echo $tpl->_ENGINE_parse_body("
	
		<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th>{familysite}</th>
					<th>{category}</th>
					<th>{size}</th>
					<th>{hits}</th>
				</tr>
			</thead>
			 <tbody>
				").@implode("", $tr)."</tbody></table>";	
	
}

function graph3(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
	$graph1=null;
	$graph2=null;
	$tablename=$_GET["tablename"];
	$sql="SELECT SUM(size) as thits, category FROM `$tablename` GROUP BY category ORDER BY thits DESC LIMIT 0,10";
	$unknown=$tpl->_ENGINE_parse_body("{unknown}");
	$c=0;
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(trim($ligne["category"])==null){$ligne["category"]=$unknown;}
		$ligne["thits"]=round($ligne["thits"]/1024)/1000;
		$PieData[$ligne["category"]]=$ligne["thits"];
		$c++;
	}
	
	
	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{size} (MB)";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_categories_by_size}");
	echo $highcharts->BuildChart();	
	
	
}

function graph2(){
	
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
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(trim($ligne["category"])==null){$ligne["category"]=$unknown;}
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
function graph0(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
	$tablename=$_GET["tablename"];
	if(is_numeric($_GET["xtime"])){
		$tablename=date("Ymd",$_GET["xtime"])."_hour";
	
	}
	$sql="SELECT SUM(size) as size,cached,`hour` FROM $tablename GROUP BY `hour`,`cached` HAVING cached=1 ORDER BY `hour`";
	
	
	
	$c=0;
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){senderror("$q->mysql_error<br>$sql");}
	if(mysql_num_rows($results)>0){
	
		$nb_events=mysql_num_rows($results);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$ligne["size"]=round(($ligne["size"]/1024)/1000);
			$xdata[]=$ligne["hour"];
			$ydata[]=$ligne["size"];
			$c++;
		}
	}
	
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title="{cached_requests}/{hour} (MB)";
	$highcharts->yAxisTtitle="{size}";
	$highcharts->xAxisTtitle="{hours}";
	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();
}
function graph4(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
	$tablename=$_GET["tablename"];
	if(is_numeric($_GET["xtime"])){
		$tablename=date("Ymd",$_GET["xtime"])."_hour";

	}
	$sql="SELECT SUM(size) as size,cached,`hour` FROM $tablename GROUP BY `hour`,`cached` HAVING cached=0 ORDER BY `hour`";



	$c=0;
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){senderror("$q->mysql_error<br>$sql");}
	if(mysql_num_rows($results)>0){

		$nb_events=mysql_num_rows($results);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$ligne["size"]=round(($ligne["size"]/1024)/1000);
			$xdata[]=$ligne["hour"];
			$ydata[]=$ligne["size"];
			$c++;
		}
	}

	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title="{used_bandwidth}/{hour} (MB)";
	$highcharts->yAxisTtitle="{size}";
	$highcharts->xAxisTtitle="{hours}";
	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();
}

function graph1(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
	$tablename=$_GET["tablename"];
	if(is_numeric($_GET["xtime"])){
		$tablename=date("Ymd",$_GET["xtime"])."_hour";
		
	}
	$sql="SELECT SUM(hits) as thits, `hour` FROM $tablename GROUP BY `hour` ORDER BY `hour`";
	
	
	
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
	$highcharts->Title="{hits}/{hour}";
	$highcharts->yAxisTtitle="{hits}";
	$highcharts->xAxisTtitle="{hours}";
	$highcharts->datas=array("{hits}"=>$ydata);
	echo $highcharts->BuildChart();

	
}

