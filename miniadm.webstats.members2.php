<?php
session_start();
//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
//ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
if(isset($_GET["content"])){content();exit;}
if(isset($_GET["master-content"])){master_content();exit;}
if(isset($_GET["graph1"])){graph1();exit;}
if(isset($_GET["graph2"])){graph2();exit;}
if(isset($_GET["graph3"])){graph3();exit;}
if(isset($_GET["search-members"])){search_members();exit;}
$users=new usersMenus();
if(!$users->AsWebStatisticsAdministrator){header("location:miniadm.logon.php");die();}
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
	<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a>
	&nbsp;&raquo;&nbsp;<a href=\"miniadm.webstats.php\">{web_statistics}</a>
	</div>
	<H1>{members}</H1>
	<p>{member_www_stats_text}</p>
	</div>
	<div id='master-content'></div>

	<script>
	LoadAjax('master-content','$page?master-content=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function master_content(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=time();
	
	$boot=new boostrap_form();
	$SearchQuery=$boot->SearchFormGen("uid","search-members");	
	$html="<table style='width:100%'>
		<tr>
			<td valign='top'><div id='$t-1' style='width:495px;height:350'></div></td>
			<td valign='top'><div id='$t-2' style='width:495px;height:350'></div></td>
			<td valign='top'><div id='$t-3' style='width:495px;height:350'></div></td>		
		</tr>
		</table>
		$SearchQuery	
	<script>
			AnimateDiv('$t-1');
			AnimateDiv('$t-2');
			AnimateDiv('$t-3');
			Loadjs('$page?graph1=yes&container=$t-1');
			Loadjs('$page?graph2=yes&container=$t-2');
			Loadjs('$page?graph3=yes&container=$t-3');	
	</script>	
	";
echo $html;	
}
function graph1(){
	$q=new mysql_squid_builder();
	$sql="SELECT COUNT( uid ) AS uid, zDate 
	FROM `members_uid` GROUP BY zDate ORDER BY zDate";
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["uid"];
		$date=strtotime($ligne["zDate"]."00:00:00");
		$xdata[]=date("m-d",$date);
		$ydata[]=$size;

	}
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title="{members_by_day}";
	$highcharts->yAxisTtitle="{members}";
	$highcharts->xAxisTtitle="{days}";
	$highcharts->datas=array("{members}"=>$ydata);
	echo $highcharts->BuildChart();

}
function graph2(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$sql="SELECT SUM( size ) AS size, uid
FROM `members_uid`
GROUP BY uid ORDER BY size DESC LIMIT 0,10";
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=round(($ligne["size"]/1024)/1000);
		$PieData[$ligne["uid"]]=$size;


	}

	if(!$q->ok){
		$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);
	}


	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{size}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_members}/{size} (MB)");
	echo $highcharts->BuildChart();


}
function graph3(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$sql="SELECT SUM( hits ) AS size, uid
FROM `members_uid`
GROUP BY uid ORDER BY size DESC LIMIT 0,10";
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["size"];
		$PieData[$ligne["uid"]]=$size;


	}

	if(!$q->ok){
		$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);
	}


	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{hits}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_members}/{hits}");
	echo $highcharts->BuildChart();


}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}

function search_members(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$searchstring=string_to_flexquery("search-members");
	$Params=url_decode_special_tool($_GET["Params"]);
	
	if(!$_SESSION["CORP"]){
		$tpl=new templates();
		$onlycorpavailable=$tpl->_ENGINE_parse_body("{onlycorpavailable}");
		$content="<p class=text-error>$onlycorpavailable</p>";
		echo $content;
		return;
	}
	$boot=new boostrap_form();
	$ORDER=$boot->TableOrder(array("uid"=>"ASC"));
	

	
	$table="(SELECT SUM(size) as size,SUM(hits) as hits,uid FROM members_uid GROUP BY uid) as t";
	$sql="SELECT *  FROM $table WHERE 1 $searchstring ORDER BY $ORDER LIMIT 0,150";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);

	
	if(!$q->ok){
		echo "<p class=text-error>$q->mysql_error<hr><code>$sql</code></p>";
	}
	
	while ($ligne = mysql_fetch_assoc($results)) {
	
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
		$ligne["hits"]=FormatNumber($ligne["hits"]);
	$link=$boot->trswitch("document.location.href='miniadm.webstats.ByMember.php?by=uid&member-value=".urlencode($ligne["uid"])."';");
	$tr[]="
	<tr id='$id'>
	<td $link><i class='icon-user'></i> {$ligne["uid"]}</a></td>
	<td $link><i class='icon-globe'></i> {$ligne["size"]}</td>
	<td $link><i class='icon-star'></i> {$ligne["hits"]}</td>
	</tr>";
	
	
	}

	
echo $boot->TableCompile(
			array("uid"=>"{member}","size"=>"{size}","hits"=>"{hits}"),
			$tr
			);
	
	
}