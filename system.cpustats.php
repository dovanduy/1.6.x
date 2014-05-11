<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');

	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["yesterday"])){yesterday();exit;}
	
	if(isset($_GET["graph1"])){graph1();exit;}
	if(isset($_GET["graph2"])){graph2();exit;}
	

js();

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$array["popup"]='{today}';
	$array["yesterday"]='{yesterday}';
	

	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=ye&t=$t\" style='font-size:16px'><span>$ligne</span></a></li>\n");
	}
	
	echo build_artica_tabs($html, "system_cpu_stats");
	
	
}


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$useragent_database=$tpl->_ENGINE_parse_body("% {cpu}");
	$html="YahooWin5('1090','$page?tabs=yes','$useragent_database');";
	echo $html;
}





function popup(){
	$page=CurrentPageName();
	$t=time();

	$html="
	<div style='float:right'>". imgtootltip("refresh-24.png","{refresh}","RefreshTab('system_cpu_stats')")."</div>		
	<div id='graph1-$t' style='width:1000px'></div>
	<span id='log-$t'></span>
	<script>
		function FTrois$t(){
			AnimateDiv('graph1-$t');
			Loadjs('$page?graph1=yes&container=graph1-$t&t=$t',true);
		} 
		function FDay$t(){
			AnimateDiv('graph2-$t');
			Loadjs('$page?graph2=yes&container=graph2-$t&t=$t',true);
		}		
		
		
	setTimeout(\"FTrois$t()\",600);
	</script>
	";
	echo $html;
	
}

function yesterday(){
	$page=CurrentPageName();
	$t=time();
	$graph="graph2";
	$html="
	<div style='float:right'>". imgtootltip("refresh-24.png","{refresh}","RefreshTab('system_cpu_stats')")."</div>
		<div id='$graph-$t' style='width:1000px'></div>
		<span id='log-$t'></span>
		<script>
	function FTrois$t(){
		AnimateDiv('$graph-$t');
		Loadjs('$page?$graph=yes&container=$graph-$t&t=$t',true);
	}
	
	setTimeout(\"FTrois$t()\",600);
	</script>
	";
	echo $html;	
}

function graph1(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$CurTime=date("YmdH");
	$CurDay=date("Ymd");
	$q=new mysql();
	$sock=new sockets();
	$hostname=$sock->getFrameWork("system.php?hostname-g=yes");

	$title="$hostname: %{cpu} {today}";
	$timetext="{hour}";
	
	$sql="SELECT DATE_FORMAT(zDate,'%Y-%m-%d') as tdate,hostname,
	HOUR(zDate) as `time`,AVG(cpu) as value FROM `cpustats` GROUP BY `time` ,tdate,hostname
	HAVING tdate=DATE_FORMAT(NOW(),'%Y-%m-%d') AND `hostname`='$hostname' ORDER BY `time`";	
	
	
	
	
	$results=$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$xdata[]=$ligne["time"];
		$ydata[]=$ligne["value"];
	}
		
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle=" {cpu} % ";
	$highcharts->xAxis_labels=true;
	$highcharts->LegendPrefix=$tpl->_ENGINE_parse_body("{cpu} ");
	$highcharts->LegendSuffix="%";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("%"=>$ydata);
	echo $highcharts->BuildChart();
	
	
	
}
function graph2(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$CurTime=date("YmdH");
	$CurDay=date("Ymd");
	$q=new mysql();
	$sock=new sockets();
	$hostname=$sock->getFrameWork("system.php?hostname-g=yes");

	$title="$hostname: %{cpu} {yesterday}";
	$timetext="{hour}";

	$sql="SELECT DATE_FORMAT(zDate,'%Y-%m-%d') as tdate,hostname,
	HOUR(zDate) as `time`,AVG(cpu) as value FROM `cpustats` GROUP BY `time` ,tdate,hostname
	HAVING tdate=DATE_FORMAT(DATE_SUB(NOW(),INTERVAL 1 DAY),'%Y-%m-%d') AND `hostname`='$hostname' ORDER BY `time`";




	$results=$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$xdata[]=$ligne["time"];
		$ydata[]=$ligne["value"];
	}

	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle=" {cpu} % ";
	$highcharts->xAxis_labels=true;
	$highcharts->LegendPrefix=$tpl->_ENGINE_parse_body("{cpu} ");
	$highcharts->LegendSuffix="%";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("{%}"=>$ydata);
	echo $highcharts->BuildChart();



}



function timejs(){
	header("content-type: application/x-javascript");
	$t=rand(50, 100);
	echo "Graph1Newtime='$t';";
}
