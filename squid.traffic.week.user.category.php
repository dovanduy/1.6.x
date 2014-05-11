<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["title_array"]["size"]="{downloaded_flow}";
	$GLOBALS["title_array"]["req"]="{requests}";	
	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die();}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["display-synthesis"])){synthesis();exit;}
	
	if(isset($_GET["visited"])){visited();exit;}
	if(isset($_GET["visited-search"])){visited_search();exit;}
	
	if(isset($_GET["graph1"])){graph1();exit;}
	if(isset($_GET["graph2"])){graph2();exit;}
	
	
	
	
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{week}&raquo;&raquo;{member}&raquo;&raquo;{$_GET["user"]}");
	
	if(preg_match("#[0-9]+_hour#", $_GET["table"])){
		$title=$tpl->_ENGINE_parse_body("{day}&raquo;&raquo;{member}&raquo;&raquo;{$_GET["user"]}");
	}
	$html="YahooWin5('890','$page?tabs=yes&user={$_GET["user"]}&field={$_GET["field"]}&table={$_GET["table"]}&category=". urlencode($_GET["category"])."','$title')";
	echo $html;
	
}
function tabs(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$id=md5(serialize($_GET));
	$array["popup"]=$tpl->_ENGINE_parse_body('{status}');
	$array["visited"]=$tpl->_ENGINE_parse_body('{visited_websites}');
	
	while (list ($num, $ligne) = each ($array) ){
		$html[]= "<li><a href=\"$page?$num=yes&user={$_GET["user"]}&field={$_GET["field"]}&table={$_GET["table"]}&category=". urlencode($_GET["category"])."\"><span style='font-size:14px'>$ligne</span></a></li>\n";
		
		
			
		}
	echo "<div id='$id' style='width:100%;'>
				<ul>". implode("\n",$html)."</ul>
		</div>
		<script>
				$(document).ready(function(){
					$('#$id').tabs();
				});
		</script>"	;
	
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$table=$_GET["table"];
	$user=$_GET["user"];
	$field=$_GET["field"];
	$category=$_GET["category"];
	$t=$_GET["divkey"];

	
	$html="
			<div style='width:750px;height:350px' id='container-$t-1'></div>
			<div style='width:750px;height:350px' id='container-$t-2'></div>
	<script>
		Loadjs('$page?graph1=yes&container=container-$t-1&user={$_GET["user"]}&field={$_GET["field"]}&table={$_GET["table"]}&category=". urlencode($_GET["category"])."');
		Loadjs('$page?graph2=yes&container=container-$t-2&user={$_GET["user"]}&field={$_GET["field"]}&table={$_GET["table"]}&category=". urlencode($_GET["category"])."');	
	</script>
	";
	
echo $html;
	
	
	
}

function graph1(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$table=$_GET["table"];
	$user=$_GET["user"];
	$field=$_GET["field"];
	$category=$_GET["category"];
	$t=$_GET["divkey"];
	$titleW=$q->WEEK_TITLE_FROM_TABLENAME($table);
	$weeksd=array(1 => "Sunday", 2 => "Monday",3=>"Tuesday",4=>"Wednesday",5=>"Thursday",6=>"Friday",7=>"Saturday");
	
	
	
	$sql="SELECT SUM(hits) as thits,SUM(size) as tsize,sitename,
	category,$field FROM $table GROUP BY sitename,category,$field 
	HAVING category='$category' AND $field='$user' ORDER BY thits DESC LIMIT 0,10";
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){
		echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."')";
		return;
	}	
	
	if(mysql_num_rows($results)<2){return;}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		
		$PieData[$ligne["sitename"]]=round(($ligne["tsize"]/1024));
		
	
	}	
	
	
	$category=str_replace(",", "<br>", $category);
	$title=$tpl->_ENGINE_parse_body();
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{websites}";
	$highcharts->Title="{top_visited_websites} {for} $user {in} $category ({size} KB)";
	echo $highcharts->BuildChart();	
}

function graph2(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$table=$_GET["table"];
	$user=trim($_GET["user"]);
	if($user==null){return;}
	$field=$_GET["field"];
	$category=$_GET["category"];
	$t=$_GET["divkey"];
	$titleW=$q->WEEK_TITLE_FROM_TABLENAME($table);
	$weeksd=array(1 => "Sunday", 2 => "Monday",3=>"Tuesday",4=>"Wednesday",5=>"Thursday",6=>"Friday",7=>"Saturday");
	$xAxisTtitle="{days}";
	$sql="SELECT SUM(hits) as thits,SUM(size) as tsize,`day`,category,$field FROM $table GROUP BY `day`,category,$field
	HAVING category='$category' AND $field='$user' ORDER BY `day`";	
	
	if(preg_match("#[0-9]+_hour#", $table)){
		$xAxisTtitle="{hours}";
		$sql="SELECT SUM(hits) as thits,SUM(size) as tsize,`hour` as `day`,
		category,$field FROM $table GROUP BY `day`,category,$field
		HAVING category='$category' AND $field='$user' ORDER BY `hour`";		
	}
	

	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){
		echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."')";
		return;
	}
	
	if(mysql_num_rows($results)<2){return;}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$ligne["tsize"]=FormatBytes($ligne["tsize"]/1024);
		$title=$tpl->_ENGINE_parse_body("{{$weeksd[$ligne["day"]]}}");
		
		if(preg_match("#[0-9]+_hour#", $table)){$title="{$ligne["day"]}h";}
		$xdata[]=$title;
		$ydata[]=$ligne["thits"];
		
	
	}		
	$category=str_replace(",", "<br>", $category);
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title="{hits} $xAxisTtitle {for} $user {in} $category";
	$highcharts->yAxisTtitle="{hits}";
	$highcharts->datas=array("{hits}"=>$ydata);
	$highcharts->xAxisTtitle=$xAxisTtitle;
	echo $highcharts->BuildChart();	
}


function popup1(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_squid_builder();	
	$table=$_GET["table"];
	$user=$_GET["user"];
	$field=$_GET["field"];
	$category=$_GET["category"];
	$t=$_GET["divkey"];
	$titleW=$q->WEEK_TITLE_FROM_TABLENAME($table);	
	$weeksd=array(1 => "Sunday", 2 => "Monday",3=>"Tuesday",4=>"Wednesday",5=>"Thursday",6=>"Friday",7=>"Saturday");
	

	
	$sql="SELECT SUM(hits) as thits,SUM(size) as tsize,familysite,
	category,$field FROM $table GROUP BY familysite,category,$field HAVING category='$category' AND $field='$user' ORDER BY thits DESC LIMIT 0,10";
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$ligne["tsize"]=FormatBytes($ligne["tsize"]/1024);
		$ydata[]="{$ligne["familysite"]}";
		$xdata[]=$ligne["thits"];
	
	}
	
	$targetedfile="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".".time().".png";
	$gp=new artica_graphs($targetedfile);	
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;	
	$gp->width=250;
	$gp->height=400;
	$gp->ViewValues=true;
	//$gp->PieLegendHide=true;
	$gp->x_title=$tpl->_ENGINE_parse_body("{cache}");
	$gp->pie();	
	
	$xdata=array();
	$ydata=array();
	
	
	$sql="SELECT SUM(hits) as thits,SUM(size) as tsize,`day`,category,$field FROM $table GROUP BY `day`,category,$field 
	HAVING category='$category' AND $field='$user' ORDER BY `day`";
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$ligne["tsize"]=FormatBytes($ligne["tsize"]/1024);
		$xdata[]=$tpl->_ENGINE_parse_body("{{$weeksd[$ligne["day"]]}}");
		$ydata[]=$ligne["thits"];
		$tr2[]="
		<tr>
			<td width=1%><img src='img/calendar.gif'></td>
			<td style='font-size:12px;font-weight:bold' width=1%>".$tpl->_ENGINE_parse_body("{{$weeksd[$ligne["day"]]}}")."</td>
			<td style='font-size:12px;font-weight:bold' width=99% nowrap>{$ligne["thits"]} {hits} - {$ligne["tsize"]}</td>
		</tr>
			
		";		
		
	}

	$targetedfile2="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".2.".time().".png";
	$gp=new artica_graphs();
	$gp->width=270;
	$gp->height=150;
	$gp->filename="$targetedfile2";
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;
	$gp->y_title=null;
	$gp->x_title=$tpl->_ENGINE_parse_body("{days}");
	$gp->title=null;
	$gp->margin0=true;
	$gp->Fillcolor="blue@0.9";
	$gp->color="146497";
	$gp->line_green();	
	
	
	if(!is_file($targetedfile2)){$targetedfile2="img/nograph-000.png";}
	

	$html="<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td width=1%><img src='img/postmaster-48.png'></td>
		<td><div style='width:100%;font-size:16px;font-weight:bold'>
			$user ({{$field}}) <br>$titleW<br>{category}:$category
		</div></td>
	</tr>
	</tbody>
	</table>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
	<td valign='top' width=50%>
		<div style='font-size:13px;font-weight:bold;color: rgb(207, 23, 23);margin-top:15px'>$category:{top_visited_websites}:</div>
		<p>&nbsp;</p>
		<center><img src='$targetedfile'></center>
	</td>
	<td valign='top' width=50%>
		<div style='font-size:13px;font-weight:bold;color: rgb(207, 23, 23);margin-top:15px'>$category:{hits} {byday}:</div>
		<p>&nbsp;</p>
		<center><img src='$targetedfile2'>
		<table style='width:50%;border:1px solid #CCCCCC'><tbody>". @implode("\n", $tr2)."</tbody></table>
		</center>
	</td>
	</tr>
	</tbody>
	</table>
	
	";	
	echo $tpl->_ENGINE_parse_body($html);
}

function visited(){
$tpl=new templates();	
$t=time();
$page=CurrentPageName();
$hits=$tpl->_ENGINE_parse_body("{hits}");
$size=$tpl->_ENGINE_parse_body("{size}");
$websites=$tpl->_ENGINE_parse_body("{websites}");
	$title=$tpl->_ENGINE_parse_body("{websites} {for} {$_GET["user"]} {in} {$_GET["category"]}");
$html="
<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>

$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?visited-search=yes&user={$_GET["user"]}&field={$_GET["field"]}&table={$_GET["table"]}&category={$_GET["category"]}',
	dataType: 'json',
	colModel : [
		{display: '$hits', name : 'thits', width :71, sortable : true, align: 'left'},
		{display: '$size', name : 'tsize', width :71, sortable : true, align: 'left'},
		{display: '$websites', name : 'sitename', width :633, sortable : true, align: 'left'},
		
	],

	searchitems : [
		{display: '$websites', name : 'sitename'},
		
		
		],
	sortname: 'hits',
	sortorder: 'desc',
	usepager: true,
	title: '<strong>$title</strong>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 833,
	height: 520,
	singleSelect: true
	
	});   
});

</script>";	
	echo $html;
	
}
function visited_search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	
	$search='%';
	$table=$_GET["table"];
	$page=1;
	$table=$_GET["table"];
	$user=$_GET["user"];
	$field=$_GET["field"];
	$category=$_GET["category"];
	
	if($q->COUNT_ROWS($table)==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT SUM(hits) as thits,SUM(size) as tsize,`$field`,`category`,`sitename`
		FROM  $table GROUP BY `$field`,`category`,`sitename` HAVING `$field`='$user'AND `category`='$category' $searchstring";

		$results=$q->QUERY_SQL($sql,"artica_events");
		$total = mysql_num_rows($results);
		
	}else{
		$sql="SELECT SUM(hits) as thits,SUM(size) as tsize,`$field`,`category`,`sitename`
		FROM  $table GROUP BY `$field`,`category`,`sitename` HAVING `$field`='$user'AND `category`='$category'";
		$results=$q->QUERY_SQL($sql,"artica_events");
		$total = mysql_num_rows($ligne);
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
		$sql="SELECT SUM(hits) as thits,SUM(size) as tsize,`$field`,`category`,`sitename`
		FROM  $table GROUP BY `$field`,`category`,`sitename` 
		HAVING `$field`='$user' AND `category`='$category' $searchstring  $ORDER $limitSql";
	
	
	
		
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){
		$q->mysql_error=wordwrap($q->mysql_error,80,"<br>");
		$sql=wordwrap($sql,80,"<br>");
		$data['rows'][] = array('id' => $ligne[time()+1],'cell' => array($q->mysql_error,"", "",""));
		$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));
		echo json_encode($data);
		return;
	}	
	
	if(mysql_num_rows($results)==0){
		$sql=wordwrap($sql,80,"<br>");
		$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$ligne["tsize"]=FormatBytes($ligne["tsize"]/1024);
	$data['rows'][] = array(
		'id' => $ligne['sitename'],
		'cell' => array("<span style='font-size:16px'>{$ligne["thits"]}</span>",
		"<span style='font-size:16px'>{$ligne["tsize"]}</span>",
		"<span style='font-size:16px'>{$ligne["sitename"]}</span>")
		);
	}
	
	
echo json_encode($data);		

}

