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
	if(!$users->AsWebStatisticsAdministrator){die("no rights");}	
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["panel"])){panel();exit;}
	if(isset($_GET["graph"])){graphs();exit;}
	if(isset($_GET["graphs-hours"])){graph_hours_table();exit;}
	if(isset($_GET["graphs-hours-list"])){graph_hours_list();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$titleadd=":";
	if($_GET["filterby"]<>null){
		$titleadd=":{$_GET["filterby"]}:{$_GET["filterdata"]}:";
	}
	$title="{hours}$titleadd{$_GET["familysite"]}:{$_GET["day"]}";
	$title=$tpl->_ENGINE_parse_body($title);
	$html="YahooWin4('990','$page?tabs=yes&filterby={$_GET["filterby"]}&filterdata={$_GET["filterdata"]}&familysite={$_GET["familysite"]}&sitename={$_GET["sitename"]}&day={$_GET["day"]}&urisize=753','$title');";
	echo $html;
}
function tabs(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();	
	if(!isset($_GET["day"])){$_GET["day"]=$q->HIER();}	
	$t=time();
	
	
	$tpl=new templates();
	$array["panel"]='{panel}';
	$array["graph"]='{graphs}';

	while (list ($num, $ligne) = each ($array) ){
		$ligne=$tpl->_ENGINE_parse_body("$ligne");
		$html[]= "<li><a href=\"$page?familysite={$_GET["familysite"]}&filterby={$_GET["filterby"]}&filterdata={$_GET["filterdata"]}&sitename={$_GET["sitename"]}&$num=yes&day={$_GET["day"]}&t=$t&urisize={$_GET["urisize"]}\"><span>$ligne</span></a></li>\n";
	}
	
	$t=time();
	echo $tpl->_ENGINE_parse_body( "
	<div id=$t style='width:97%;font-size:14px;margin-left:10px;margin-right:-15px;margin-top:-5px'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#$t').tabs();
			
			
			});
		</script>");		
}

function panel(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$familysite=$_GET["familysite"];

	for($i=0;$i<24;$i++){
		if($i<10){$txt="0".$i;}else{$txt=$i;}
		$HH[$i]=$txt;
		
	}
	
	
	$html="
		<table style='width:99%' class=form>
			<tr>
				<td class=legend style='font-size:14px'>{from_time}:</td>
				<td>". Field_array_Hash($HH,"from-$t",0,"style:font-size:14px")."</td>
				<td class=legend style='font-size:14px'>{to_time}:</td>
				<td>". Field_array_Hash($HH,"to-$t",23,"style:font-size:14px")."</td>
				<td>". button("{statistics}","PerformHourStats$t()",16)."</td>
			</tr>
			</table>
		
		
		<div id='graphs-$t' style='width:100%'></div>
	
	
		<script>
		function PerformHourStats$t(){
			fromtime=document.getElementById('from-$t').value;
			totime=document.getElementById('to-$t').value;
			LoadAjax('graphs-$t','$page?graphs-hours=yes&filterby={$_GET["filterby"]}&filterdata={$_GET["filterdata"]}&familysite={$_GET["familysite"]}&day={$_GET["day"]}&t=$t&from='+fromtime+'&to='+totime+'&urisize={$_GET["urisize"]}');
		
		}
		 PerformHourStats$t();
		</script>
		";
	

	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}
function graph_hours_table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$familysite=$_GET["familysite"];	
	$hour=$tpl->_ENGINE_parse_body("{hour}");
	$uri=$tpl->_ENGINE_parse_body("{url}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$timeT=strtotime("{$_GET["day"]} 00:00:00");
	$titleadd="&nbsp;&raquo;";
	$urisize=448;
	if(isset($_GET["urisize"])){$urisize=$_GET["urisize"];}
	$display="{display: '$member', name : 'uid', width : 82, sortable : false, align: 'left'},
		{display: 'MAC', name : 'MAC', width : 97, sortable : false, align: 'left'},
		{display: '$ipaddr', name : 'CLIENT', width : 90, sortable : false, align: 'left'},";
	
	if($_GET["filterby"]<>null){
		$titleadd="&nbsp;&raquo;{$_GET["filterby"]}&nbsp;&raquo;{$_GET["filterdata"]}&nbsp;&raquo;";
		$display=null;
		$urisize=753;
	}
	
	$titleTable=date('{l} {F} d',$timeT)."$titleadd$familysite&nbsp;&raquo{from} {$_GET["from"]}H {to} {$_GET["to"]}H";
	if($tpl->language=="fr"){
		
		$titleTable=date('{l} d {F}',$timeT)."$titleadd$familysite&nbsp;&raquo{from} {$_GET["from"]}H {to} {$_GET["to"]}H";
	}
	
	$titleTable=$tpl->_ENGINE_parse_body("$titleTable");
$html="
$title$image
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?graphs-hours-list=yes&familysite={$_GET["familysite"]}&filterby={$_GET["filterby"]}&filterdata={$_GET["filterdata"]}&day={$_GET["day"]}&t=$t&from={$_GET["from"]}&to={$_GET["to"]}',
	dataType: 'json',
	colModel : [
		{display: '$hour', name : 'thour', width :39, sortable : true, align: 'left'},
		{display: '$uri', name : 'uri', width:$urisize, sortable : true, align: 'left'},
		{display: '$size', name : 'QuerySize', width : 55, sortable : false, align: 'left'},

		

		],
	searchitems : [
		{display: '$uri', name : 'uri'},
	],		

	sortname: 'thour',
	sortorder: 'desc',
	usepager: true,
	title: '$titleTable',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 904,
	height: 400,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});
</script>

";	

	
	echo $tpl->_ENGINE_parse_body($html);

}

function graph_hours_list(){
	$Mypage=CurrentPageName();
	$tpl=new templates();		
	$q=new mysql_squid_builder();	
	$t=time();
	$fontsize=14;
	$type=$_GET["type"];
	$field_query="size";
	$field_query2="SUM(size)";	
	$table_field="{size}";
	$category=$tpl->_ENGINE_parse_body("{category}");
	$hour_table=date('Ymd',strtotime($_GET["day"]))."_hour";
	$member=$tpl->_ENGINE_parse_body("{member}");
	$sitename=$tpl->_ENGINE_parse_body("{website}");
	$table="dansguardian_events_".date('Ymd',strtotime($_GET["day"]));
	
	if($_GET["filterby"]<>null){
		$OP="=";
		if($_GET["filterby"]=="ipaddr"){$_GET["filterby"]="CLIENT";}
		$titleadd="&nbsp;&raquo;{$_GET["filterby"]}&nbsp;&raquo;{$_GET["filterdata"]}&nbsp;&raquo;";
		if(strpos(" {$_GET["filterdata"]}", "*")>0){$OP=" LIKE ";$_GET["filterdata"]=str_replace("*", "%", $_GET["filterdata"]);}
		$display=null;
		$AND=" AND {$_GET["filterby"]}$OP'{$_GET["filterdata"]}'";
	}
	
	$hourfrom=$_GET["from"];
	$hourto=$_GET["to"];
	$page=1;
	$FORCE_FILTER="HOUR(zDate)>=$hourfrom AND  HOUR(zDate)<=$hourto AND sitename LIKE '%{$_GET["familysite"]}'$AND";
	
	if($q->COUNT_ROWS($table)==0){
		writelogs("$table, no row",__FILE__,__FUNCTION__,__FILE__,__LINE__);
		$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();
		echo json_encode($data);
		return ;
	}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	$sql="SELECT *,HOUR(zDate)as thour,DATE_FORMAT(zDate,'%H') as thour2  FROM `$table` WHERE $FORCE_FILTER $searchstring  $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){
		$data['rows'][] = array('id' => $ligne[time()+1],'cell' => array($q->mysql_error,"", "",""));
		$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));
		echo json_encode($data);
		return;
	}	
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		
		
	if($ligne["QuerySize"]>1024){
				$ligne["QuerySize"]=FormatBytes($ligne["QuerySize"]/1024);
			}else{
				$ligne["QuerySize"]="{$ligne["QuerySize"]} Bytes";
			}
			
			$ligne["uri"]=str_replace("http://","",$ligne["uri"]);
			$ligne["uri"]=str_replace("https://","",$ligne["uri"]);
			$ligne["uri"]=str_replace("www.","",$ligne["uri"]);

		$data['rows'][] = array(
			'id' => $id,
			'cell' => array(
			"<span $style>{$ligne["thour2"]}h</span>",
			"<span $style>{$ligne["uri"]} ({$ligne["hits"]})</span>",
			"<span $style>{$ligne["QuerySize"]}</span>",
			"<span $style>{$ligne["uid"]}</span>",
			"<span $style>{$ligne["MAC"]}</span>",
			"<span $style>{$ligne["CLIENT"]}</span>",
			)
			);		
		
		
	}

echo json_encode($data);	
}

function graphs(){
	include_once('ressources/class.artica.graphs.inc');
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
	$graph1=null;
	$graph2=null;
	$day=$_GET["day"];
	$ttime=strtotime("$day 00:00:00");	
	$table=date("Ymd",$ttime)."_hour";
	$familysite=$_GET["familysite"];
	if($familysite<>null){
		$familysite2=$q->GetFamilySites($familysite);
	}
	$F=array();
	if($_GET["filterby"]<>null){
		$OP="=";
		$field_site0="`{$_GET["filterby"]}`,";
		if($_GET["filterby"]=="ipaddr"){$_GET["filterby"]="client";$field_site0="`client`,";}
		$titleadd="&nbsp;&raquo;{$_GET["filterby"]}&nbsp;&raquo;{$_GET["filterdata"]}&nbsp;&raquo;";
		if(strpos(" {$_GET["filterdata"]}", "*")>0){$OP=" LIKE ";$_GET["filterdata"]=str_replace("*", "%", $_GET["filterdata"]);}
		$display=null;
		$F[]="AND {$_GET["filterby"]}$OP'{$_GET["filterdata"]}'";
	}	
	
	if($familysite<>null){
	if($familysite2==$familysite){
		$field_site="familysite,";
		$F[]="AND familysite ='$familysite'";
	}else{
		$field_site="sitename,";
		$F[]="AND sitename ='$familysite'";
	}
	
	}
	
	if(count($F)>0){
		$HAVING="HAVING thits>0 ".@implode(" ", $F);
	}
	
	$sql="SELECT SUM(hits) as thits, $field_site$field_site0 `hour` FROM `$table` GROUP BY $field_site$field_site0,`hour` $HAVING ORDER BY `hour`";
	$sql=str_replace(", ,", ",", $sql);
	$sql=str_replace(",,", ",", $sql);
	$unknown=$tpl->_ENGINE_parse_body("{unknown}");
	$c=0;
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}	
	if(mysql_num_rows($results)>1){
	

		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$ydata[]=$ligne["thits"];
			$xdata[]=$ligne["hour"];
			$c++;
		}
			$t=time();
			$targetedfile="ressources/logs/".md5(basename(__FILE__).".".__FUNCTION__.".$sql.1$tablename").".png";
			$gp=new artica_graphs();
			$gp->width=900;
			$gp->height=350;
			$gp->filename="$targetedfile";
			$gp->xdata=$xdata;
			$gp->ydata=$ydata;
			$gp->y_title=null;
			$gp->x_title=$tpl->_ENGINE_parse_body("{hits}/{hours}");
			$gp->title=null;
			$gp->margin0=true;
			$gp->Fillcolor="blue@0.9";
			$gp->color="146497";
			$gp->line_green();
			
			
			if(is_file("$targetedfile")){$graph1="
				<center style='font-size:18px;margin:10px'>{$gp->x_title}</center>
				<img src='$targetedfile'>";
			}else{
				$graph1="$targetedfile no such file<hr>$sql<hr>";
			}
	
	}else{
		$graph1=$tpl->_ENGINE_parse_body("{only_one_value_no_graph}<hr><span style='font-size:11px'>$sql</span>");
	}
	$xdata=array();$ydata=array();

	$sql="SELECT SUM(size) as thits, $field_site$field_site0 `hour` FROM `$table` GROUP BY $field_site$field_site0,`hour` $HAVING ORDER BY `hour`";
	$sql=str_replace(", ,", ",", $sql);
	$sql=str_replace(",,", ",", $sql);
	$unknown=$tpl->_ENGINE_parse_body("{unknown}");
	$c=0;
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}	
	if(mysql_num_rows($results)>1){
	

		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			
			$ydata[]=$ligne["thits"];
			$xdata[]=$ligne["hour"];
			$c++;
		}
			$t=time();
			$targetedfile="ressources/logs/".md5(basename(__FILE__).".".__FUNCTION__.".$sql.2$tablename").".png";
			$gp=new artica_graphs();
			$gp->width=900;
			$gp->height=350;
			$gp->filename="$targetedfile";
			$gp->xdata=$xdata;
			$gp->ydata=$ydata;
			$gp->y_title=null;
			$gp->x_title=$tpl->_ENGINE_parse_body("{size}/{hours}");
			$gp->title=null;
			$gp->margin0=true;
			$gp->Fillcolor="blue@0.9";
			$gp->color="146497";
			$gp->line_green();
		if(is_file("$targetedfile")){$graph2="
		<center style='font-size:18px;margin:10px'>{$gp->x_title}</center>
		<img src='$targetedfile'>";}
	
	}else{
		$graph2=$tpl->_ENGINE_parse_body("{only_one_value_no_graph}<hr><span style='font-size:11px'>$sql</span>");
	}
	
	$html="<table style='width:100%'>
	<tr>
		<td width=50% style='font-size:16px'>$graph1</td>
	</tr>
	<tr>
		<td width=50% style='font-size:16px'>$graph2</td>
	</tr>
	</table>";
	echo $html;	
	
	
}


