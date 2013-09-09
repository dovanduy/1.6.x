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
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
Privileges_members_ownstats();

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["www-graĥs"])){www_graphs();exit;}
if(isset($_GET["www-table"])){www_table();exit;}
if(isset($_GET["graph1"])){graph1();exit;}
if(isset($_GET["graph2"])){graph2();exit;}
if(isset($_GET["graph3"])){graph3();exit;}
if(isset($_GET["graph4"])){graph4();exit;}
if(isset($_GET["www-categories"])){www_categories();exit;}
if(isset($_GET["www-search"])){www_search();exit;}
if(isset($_GET["www-behavior"])){www_beahvior();exit;}
if(isset($_GET["www-behavior-search"])){www_beahvior_search();exit;}


js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$title="{$_GET["uid"]}";
	$uid=urlencode($_GET["uid"]);
	$dateT=date("{l} {F} d",$_GET["xtime"]);
	if($tpl->language=="fr"){$dateT=date("{l} d {F} ",$_GET["xtime"]);}	
	$dateT=$tpl->javascript_parse_text("$dateT {$_GET["hour"]}H");
	$sitename=$_GET["familysite"];
	$sitenameenc=urlencode($sitename);
	$html="YahooWin5('900','$page?tabs=yes&familysite=$sitenameenc','{$_GET["familysite"]}')";
	echo $html;
//Loadjs('miniadm.webstats.ByMember.website.php?familysite=$fsite&member-value={$_GET["member-value"]}&by={$_GET["by"]}	
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}
function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$q=new mysql_squid_builder();
	$boot=new boostrap_form();
	$suffix=suffix();
	$familysite=$_GET["familysite"];
	$sitenameenc=urlencode($familysite);
	$array[$familysite]="miniadm.webstats.website.infos.php?familysite=$familysite";
	$array["{statistics}"]="$page?www-graĥs=yes$suffix";
	$array["{values}"]="$page?www-table=yes$suffix";
	if($_SESSION["AsWebStatisticsAdministrator"]){
		$array["{behavior}"]="$page?www-behavior=yes$suffix";
	}
	
	
	echo "<H3>".$familysite."</H3>".$boot->build_tab($array);
}
function suffix(){
	$familysite=$_GET["familysite"];
	$sitenameenc=urlencode($familysite);
	return "&familysite=$sitenameenc";
}


function www_graphs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$suffix=suffix();
	$html="
	<div id='$t-1' style='width:780px;height:450px'></div>
	<div id='$t-2' style='width:780px;height:450px'></div>

	<script>
	AnimateDiv('$t-1');
	AnimateDiv('$t-2');
	Loadjs('$page?graph1=yes$suffix&container=$t-1');
	Loadjs('$page?graph2=yes$suffix&container=$t-2');
	</script>
	";
	echo $html;

}
function www_table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$form=$boot->SearchFormGen("zDate","www-search",suffix());
	echo $form;
}

function www_beahvior(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$form=$boot->SearchFormGen("zDate","www-behavior-search",suffix());
	echo $form;	
}

function graph1(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	
	$table="visited_sites_days";
	$familysite=$_GET["familysite"];

	$sql="SELECT SUM(hits) as hits,`zDate`,familysite FROM `visited_sites_days` GROUP BY
	familysite,`zDate` HAVING `familysite`='$familysite' ORDER BY `zDate`";
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["hits"];
		$date=strtotime($ligne["zDate"]."00:00:00");
		$xdata[]=date("m-d",$date);
		$ydata[]=$size;

	}
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title="$familysite {requests}/{day}";
	$highcharts->yAxisTtitle="{hits}";
	$highcharts->xAxisTtitle="{days}";
	$highcharts->datas=array("{requests}"=>$ydata);
	echo $highcharts->BuildChart();
}
function graph2(){
	$q=new mysql_squid_builder();
	$tpl=new templates();

	$table="visited_sites_days";
	$familysite=$_GET["familysite"];

	$sql="SELECT SUM(size) as hits,`zDate`,familysite FROM `visited_sites_days` GROUP BY
	familysite,`zDate` HAVING `familysite`='$familysite' ORDER BY `zDate`";
	
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["hits"];
		$date=strtotime($ligne["zDate"]."00:00:00");
		$xdata[]=date("m-d",$date);
		$ydata[]=round(($size/1024)/1000);

	}
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title="$familysite {size}/{day}";
	$highcharts->yAxisTtitle="{size}";
	$highcharts->xAxisTtitle="{days}";
	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();
}

function www_beahvior_search(){
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	$familysite=$_GET["familysite"];	
	
	$FAMS=$boot->SQUID_CATEGORIES_FAM;

	$current_month=date("Ym");
	$table="{$current_month}_catfam";
	$q=new mysql_squid_builder();
	
	
	$searchstring=string_to_flexquery("www-behavior-search");
	$ORDER=$boot->TableOrder(array("zDate"=>"DESC"));
	if(!$q->TABLE_EXISTS($table)){senderrors("no such table");}
	if($q->COUNT_ROWS($table)==0){senderrors("no data");}
	//zDate      | client        | uid               | hostname                | MAC               | familysite                                 | catfam | hits | size
	$table="( SELECT familysite,zDate,catfam,SUM(size) as size,SUM(hits) as hits FROM `$table` GROUP BY 
	zDate,familysite HAVING familysite='$familysite') as t";
	$sql="SELECT * FROM $table WHERE 1 $searchstring ORDER BY $ORDER LIMIT 0,250";
	$results = $q->QUERY_SQL($sql);
	
	if(!$q->ok){senderrors($q->mysql_error."<br>$sql");}
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$md=md5(serialize($ligne));
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
		$sitenameenc=urlencode($ligne["familysite"]);
		$xtime=strtotime("{$ligne["zDate"]} 00:00:00");
		
		$js="Loadjs('miniadm.webstats.fam.ByDay.php?familysite=$sitenameenc&xtime=$xtime&fam={$ligne["catfam"]}')";
		$link=$boot->trswitch($js);
		
		$tr[]="
		<tr id='$md'>
		<td style='font-size:16px' width=1% nowrap $link>{$ligne["zDate"]}</td>
		<td style='font-size:16px' width=1% nowrap $link>{$ligne["hits"]}</td>
		<td style='font-size:16px' width=1% nowrap $link>{$ligne["size"]}</td>
		<td style='font-size:16px' width=99% $link>". $tpl->_ENGINE_parse_body($FAMS[$ligne["catfam"]]["TITLE"])."</td>
		</tr>
		";
	}
	
	echo $boot->TableCompile(array("zDate"=>"{this_month}",
		
			"hits"=>"{hits}",
			"size"=>"{size}",
			"catfam"=>"{behavior}",
	),$tr);
}


function www_search(){
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	$familysite=$_GET["familysite"];

	$q=new mysql_squid_builder();
	$uidtable=$q->uid_to_tablename($_GET["member-value"]);
	if(!$q->TABLE_EXISTS("`visited_sites_days`")){
		echo "<p class=text-error>No table &laquo;`visited_sites_days`&raquo; for {$familysite}</p>";
		return;
	}
	
	$search=string_to_flexquery("www-search");
	$sql="SELECT SUM(hits) as hits,SUM(size) as size,`zDate`,familysite FROM `visited_sites_days` GROUP BY
	familysite,`zDate` HAVING `familysite`='$familysite' $search ORDER BY `zDate` DESC";
	$results=$q->QUERY_SQL($sql);


	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<p class=text-error>$q->mysql_error<br>$sql</p>";return;}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=FormatBytes($ligne["size"]/1024);
		$date=strtotime($ligne["zDate"]."00:00:00");
		$hits=FormatNumber($ligne["hits"]);
		$fsite=urlencode($ligne["familysite"]);
		$jslink="Loadjs('miniadm.webstats.websites.ByDayByFamilySite.php?familysite=$fsite&xtime=$date')";
		$zdate=time_to_date($date);
		$link=$boot->trswitch($jslink);
		$tr[]="
		<tr id='$id'>
		<td $link><i class='icon-time'></i>&nbsp;$zdate</a></td>
		<td $link><i class='icon-info-sign'></i>&nbsp;$size</td>
		<td $link><i class='icon-info-sign'></i>&nbsp;$hits</td>
		</tr>";



	}

	echo $tpl->_ENGINE_parse_body("

			<table class='table table-bordered table-hover'>

			<thead>
				<tr>
					<th>{date}</th>
					<th>{size}</th>
					<th>{hits}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("", $tr)."</tbody></table>";



}