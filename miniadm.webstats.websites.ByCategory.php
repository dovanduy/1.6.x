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
if(isset($_GET["category-search"])){table_sites_search();exit;}
if(isset($_GET["uid-search"])){uid_search();exit;}


js();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$title="{category} {$_GET["category"]}";
	$title=$tpl->javascript_parse_text("$title");
	$suffix=suffix();
	$html="YahooWin('900','$page?tabs=yes$suffix','$title')";
	echo $html;
	
}



function tabs(){
	//$table=date("Ymd",$_GET["xtime"])."_hour";
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$date=time_to_date($_GET["xtime"]);
	$suffix=suffix();
	
	$title=$tpl->_ENGINE_parse_body("{category} {$_GET["category"]}");
	$category=urlencode($_GET["category"]);
	$array["{graphs}"]="$page?www-graĥs=yes$suffix";
	$array["{days}"]="$page?www-table=yes$suffix";
	echo "<H3>".$title."</H3>".$boot->build_tab($array);	
	
}
function suffix(){
	$t=$_GET["t"];
	$category=urlencode($_GET["category"]);
	$suffix="&t=$t&category=$category";
	return $suffix;
}


function section_graphs(){
	$suffix=suffix();
	$t=time();
	$page=CurrentPageName();
	$html="
	<div class=BodyContent id='graph-$t'></div>
	<div class=BodyContent id='graph1-$t'></div>
	
	<script>
		AnimateDiv('graph-$t');
		AnimateDiv('graph1-$t');
		Loadjs('$page?graph1=yes&container=graph-$t$suffix');
		Loadjs('$page?graph1-1=yes&container=graph1-$t$suffix');
		//Loadjs('$page?graph2=yes&container=graph2-$t$suffix');
		//Loadjs('$page?graph3=yes&container=graph3-$t$suffix');
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
	$form=$boot->SearchFormGen("zDate","category-search",$suffix);
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
	$category=mysql_escape_string($_GET["category"]);
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

function table_sites_search(){
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$users=new usersMenus();
	$sock=new sockets();
	$boot=new boostrap_form();
	$database="squidlogs";
	$search='%';
	$table="generic_categories";
	$rp=250;
	$page=1;
	$FORCE_FILTER=null;
	$ORDER="ORDER BY size DESC";
	
	if(!$q->TABLE_EXISTS($table, $database)){senderror("$table doesn't exists...");}
	if($q->COUNT_ROWS($table, $database)==0){senderror("No data");}
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$search=string_to_flexquery("category-search");
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	$category=mysql_escape_string($_GET["category"]);
	$sql="SELECT SUM( size ) AS size,SUM(hits) as hits, zDate, category FROM generic_categories GROUP BY category,zDate
	HAVING category='{$_GET["category"]}' ORDER BY zDate";
	$results=$q->QUERY_SQL($sql);;
	

	
	$results = $q->QUERY_SQL($sql,$database);
	
	
	if(!$q->ok){senderror($q->mysql_error."<br>$sql");}
	
	while ($ligne = mysql_fetch_assoc($results)) {
	
		$color="black";
		$ligne["hits"]=numberFormat($ligne["hits"],0,""," ");
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
		$xtime=strtotime($ligne["zDate"]." 00:00:00");
		$dateText=time_to_date($xtime);
		$catz=urlencode($_GET["category"]);
		$jsSitename=$boot->trswitch("Loadjs('miniadm.webstats.websites.ByDayByCategory.php?category=$catz&xtime=$xtime')");

			$tr[]="
				<tr $jsSitename>
					<td><i class='icon-time'></i>&nbsp;$dateText</a></td>
					<td><i class='icon-info-sign'></i>&nbsp;{$ligne["size"]}</td>
					<td><i class='icon-info-sign'></i>&nbsp;{$ligne["hits"]}</td>
				</tr>";
	}
	
	echo $tpl->_ENGINE_parse_body("
	
		<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th>{day}</th>
					<th>{size}</th>
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
	
	$tablename=date("Ymd",$_GET["xtime"])."_hour";
	$category=mysql_escape_string($_GET["category"]);	
	
	
	if($_GET["category"]=="unknown"){$_GET["category"]=null;}
	
	$sql="SELECT SUM( size ) AS size,zDate, category FROM generic_categories GROUP BY category,zDate
	HAVING category='{$_GET["category"]}' ORDER BY zDate";
	$results=$q->QUERY_SQL($sql);



	$c=0;
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){senderror("$q->mysql_error<br>$sql");}
	if(mysql_num_rows($results)>0){

		$nb_events=mysql_num_rows($results);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$xdata[]=$ligne["zDate"];
			$ydata[]=round(($ligne["size"]/1024)/1000);
			$c++;
		}
	}

	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title="$category: {size}/{day} (MB)";
	$highcharts->yAxisTtitle="{size}";
	$highcharts->xAxisTtitle="{days}";
	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();


}
function graph1_1(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
	
	$tablename=date("Ymd",$_GET["xtime"])."_hour";
	$category=mysql_escape_string($_GET["category"]);
	
	
	if($_GET["category"]=="unknown"){$_GET["category"]=null;}
	
	$sql="SELECT SUM( hits ) AS size,zDate, category FROM generic_categories GROUP BY category,zDate
	HAVING category='{$_GET["category"]}' ORDER BY zDate";
	$results=$q->QUERY_SQL($sql);;
	
	
	
	$c=0;
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){senderror("$q->mysql_error<br>$sql");}
	if(mysql_num_rows($results)>0){
	
	$nb_events=mysql_num_rows($results);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
	$xdata[]=$ligne["zDate"];
	$ydata[]=$ligne["size"];
	$c++;
	}
	}
	
	$highcharts=new highcharts();
		$highcharts->container=$_GET["container"];
		$highcharts->xAxis=$xdata;
		$highcharts->Title="$category: {hits}/{day}";
		$highcharts->yAxisTtitle="{hits}";
		$highcharts->xAxisTtitle="{days}";
		$highcharts->datas=array("{hits}"=>$ydata);
		echo $highcharts->BuildChart();	
	
	
}



