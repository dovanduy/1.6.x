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
$users=new usersMenus();if(!$users->AsWebStatisticsAdministrator){header("location:miniadm.index.php");die();}
	
if(isset($_GET["graphjs"])){graph();exit;}
if(isset($_GET["content"])){content();exit;}
if(isset($_GET["messaging-right"])){messaging_right();exit;}
if(isset($_GET["webstats-middle"])){webstats_middle();exit;}
if(isset($_GET["graph"])){generate_graph();exit;}
if(isset($_POST["NoCategorizedAnalyze"])){NoCategorizedAnalyze();exit;}


main_page();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}')</script>", $content);
	echo $content;	
}
function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();

	$jsadd="LoadAjax('statistics-$t','$page?webstats-stats=yes');";
	
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'>
			<a href=\"miniadm.index.php\">{myaccount}</a>
			&nbsp;&raquo;&nbsp;<a href=\"miniadm.webstats.php?t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}\">{web_statistics}</a>
		</div>
		<H1>{unknown_websites}</H1>
		<p>{unknown_websites_miniadm_explain}</p>
	</div>	
	<div id='webstats-middle-$ff'></div>
	
	<script>
		LoadAjax('webstats-middle-$ff','$page?webstats-middle=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}');
		$jsadd
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function webstats_middle(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
	
	$html="<div class=BodyContent id='graph-$ff'></div>
	
	
	<script>
		LoadAjax('graph-$ff','$page?graph=yes');
	</script>
	";
	
	echo $html;
	
	
}

function generate_graph(){
	include_once('ressources/class.artica.graphs.inc');
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
		
	
	$sql="SELECT zDate,not_categorized FROM tables_day WHERE not_categorized>0";
	$c=0;
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}	
	if(mysql_num_rows($results)>0){
	
			$nb_events=mysql_num_rows($results);
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$xdata[]=$ligne["zDate"];
				$ydata[]=$ligne["not_categorized"];
				
			$c++;
		$table=$table."<tr>
			<td style='font-size:14px' width=99% nowrap><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.visited.php?day={$ligne["zDate"]}&onlyNot=yes');\" style='font-size:14px;text-decoration:underline'>{$ligne["zDate"]}</a></td>
			<td style='font-size:14px' width=1% nowrap><strong>{$ligne["not_categorized"]}</strong></td>
		</tr>
		";
		if($c>10){$c=0;$tr[]="<table style='width:20%' class=form><tbody>$table</tbody></table>";$table=null;}
		
	}	
		if($c>0){$tr[]="<table style='width:20%' class=form><tbody>$table</tbody></table>";}				
				
				
			$Main=array($xdata,$ydata);
			$Main=urlencode(base64_encode(serialize($Main)));
			$t=time();
			echo "<center>
			<div style='font-size:18px;margin-bottom:10px'>".$tpl->_ENGINE_parse_body("{not_categorized}/{days}")."</div>
			<div id='$ff-graph' style='width:940px;height:450px'><center><img src='img/wait_verybig_mini_red.gif'></center></div>
			</center>";
		
	
	}
	
	echo "<center>
	<div style='margin:8px;float-right;width:100%'>".$tpl->_ENGINE_parse_body(button("{analyze}", "NoCategorizedAnalyze()",18))."</div>
	<div style='width:80%'>".CompileTrGen($tr,6)."</div></center>
	
		<script>
		function NoCategorizedAnalyze(){
		
		}
		
	var x_NoCategorizedAnalyze= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
	    	
		}	

		function NoCategorizedAnalyze(){
			var XHR = new XHRConnection();
			XHR.appendData('NoCategorizedAnalyze','yes');
			XHR.sendAndLoad('$page', 'POST',x_NoCategorizedAnalyze);
		}
		
		Loadjs('$page?graphjs=yes&container=$ff-graph&data=$Main');
		
	</script>";
}
function graph(){
	
	$array=unserialize(base64_decode($_GET["data"]));
	
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$array[0];
	$highcharts->Title="{unknown_websites}";
	$highcharts->yAxisTtitle="{websites}";
	$highcharts->xAxisTtitle="{days}";
	$highcharts->datas=array("{websites}"=>$array[1]);
	
	echo $highcharts->BuildChart();
	
	
}


function NoCategorizedAnalyze(){
	$tpl=new templates();
	$sock=new sockets();
	$sock->getFrameWork("squid.php?NoCategorizedAnalyze=yes");
	echo $tpl->javascript_parse_text("{install_app}",1);
	
}
