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
	if(isset($_GET["graphs-hours"])){graph_hours_table();exit;}
	if(isset($_GET["graphs-hours-list"])){graph_hours_list();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title="{hours}:{$_GET["familysite"]}:{$_GET["day"]}";
	$title=$tpl->_ENGINE_parse_body($title);
	$html="YahooWin4('766','$page?tabs=yes&familysite={$_GET["familysite"]}&day={$_GET["day"]}','$title');";
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

	while (list ($num, $ligne) = each ($array) ){
		$ligne=$tpl->_ENGINE_parse_body("$ligne");
		$html[]= "<li><a href=\"$page?familysite={$_GET["familysite"]}&$num=yes&day={$_GET["day"]}&t=$t\"><span>$ligne</span></a></li>\n";
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
			LoadAjax('graphs-$t','$page?graphs-hours=yes&familysite={$_GET["familysite"]}&day={$_GET["day"]}&t=$t&from='+fromtime+'&to='+totime);
		
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
	
$html="
$title$image
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?graphs-hours-list=yes&familysite={$_GET["familysite"]}&day={$_GET["day"]}&t=$t&from={$_GET["from"]}&to={$_GET["to"]}',
	dataType: 'json',
	colModel : [
		{display: '$hour', name : 'thour', width :39, sortable : true, align: 'left'},
		{display: '$uri', name : 'uri', width :230, sortable : true, align: 'left'},
		{display: '$size', name : 'QuerySize', width : 55, sortable : false, align: 'left'},
		{display: '$member', name : 'uid', width : 82, sortable : false, align: 'left'},
		{display: 'MAC', name : 'MAC', width : 97, sortable : false, align: 'left'},
		{display: '$ipaddr', name : 'CLIENT', width : 90, sortable : false, align: 'left'},
		

		],

	sortname: 'thour',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: false,
	rp: 50,
	showTableToggleBtn: false,
	width: 683,
	height: 280,
	singleSelect: true
	
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
	
	$hourfrom=$_GET["from"];
	$hourto=$_GET["to"];
	$page=1;
	$FORCE_FILTER="HOUR(zDate)>=$hourfrom AND  HOUR(zDate)<=$hourto AND sitename LIKE '%{$_GET["familysite"]}'";
	
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


