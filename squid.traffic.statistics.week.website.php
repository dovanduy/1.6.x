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

	if(isset($_GET["hits"])){hits();exit;}
	if(isset($_GET["size"])){hits(true);exit;}
	if(isset($_GET["users"])){users();exit;}
	if(isset($_GET["users-search"])){users_search();exit;}
	if(isset($_GET["websites"])){websites();exit;}
	if(isset($_GET["websites-search"])){websites_search();exit;}
	
	
	
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{week}&raquo;&raquo;{website}&raquo;&raquo;{$_GET["www"]}");
	$html="YahooWin5('890','$page?tabs=yes&www={$_GET["www"]}&field={$_GET["field"]}&table={$_GET["table"]}','$title')";
	echo $html;
	
}


function tabs(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$id=md5("week{$_GET["www"]}");
	$array["status"]=$tpl->_ENGINE_parse_body('{status}');
	$array["hits"]=$tpl->_ENGINE_parse_body('{hits}');
	$array["size"]=$tpl->_ENGINE_parse_body('{size}');
	$array["users"]=$tpl->_ENGINE_parse_body('{users}');
	if($_GET["field"]=="familysite"){
		$array["websites"]=$tpl->_ENGINE_parse_body('{websites}');
	}
	
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="status"){
			$html[]= "<li><a href=\"squid.www-ident.php?www={$_GET["www"]}&field={$_GET["field"]}&table={$_GET["table"]}\"><span style='font-size:14px'>$ligne</span></a></li>\n";
			continue;
		}
		
		
		$html[]= "<li><a href=\"$page?$num=yes&www={$_GET["www"]}&field={$_GET["field"]}&table={$_GET["table"]}\"><span style='font-size:14px'>$ligne</span></a></li>\n";
		
		
			
		}
	echo "<div id='$id' style='width:100%;height:700px;overflow:auto;background-color:white;'>
				<ul>". implode("\n",$html)."</ul>
		</div>
		<script>
				$(document).ready(function(){
					$('#$id').tabs();
			

			});
		</script>"	;
	
}


function users(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_squid_builder();	
	$table=$_GET["table"];
	
	if(preg_match("#[0-9]+_hour$#", $table)){
		$year=substr($table, 0,4);
		$month=substr($table, 4,2);
		$day=substr($table, 6,2);
		$date="$year-$month-$day";
		$timeS=strtotime($date);
		$table=date("YW",$timeS)."_week";	
	}	
	
	$www=$_GET["www"];
	$field=$_GET["field"];
	$titleW=$q->WEEK_TITLE_FROM_TABLENAME($table);
	$weeksd=array(1 => "Sunday", 2 => "Monday",3=>"Tuesday",4=>"Wednesday",5=>"Thursday",6=>"Friday",7=>"Saturday");
	$hits="hits";
	$hits_title="{members}";
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$ComputerMacAddress=$tpl->_ENGINE_parse_body("{ComputerMacAddress}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$hitsTitl=$tpl->_ENGINE_parse_body("{hits}");
	
	$html="<div id='title' style='font-size:16px;font-weight:bold'>{website}: $www $titleW</div>
	<center style='font-size:16px;font-weight:bold;margin-top:15px;width:80%;margin:10px'>
	<div style='border-top:1px solid #CCCCCC;border-bottom:1px solid #CCCCCC;padding:20px;'>
	$hits_title {byday}
	</div></center>";
	
		 	 	
	
	$xdata=array();
	$ydata=array();	
	$sql="
	SELECT COUNT(*) as hits,`day` FROM (
	SELECT COUNT(client) as hits,`$field`,`day`,`client` FROM  $table GROUP BY `day`,client,`$field` HAVING `$field`='$www') as t
	GROUP BY `day`  ORDER BY `day`";
	
	
	$table="<table style='width:100%'>";
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$xdata[]=$tpl->_ENGINE_parse_body("{{$weeksd[$ligne["day"]]}}");
		$text=$ligne["hits"];
		$ydata[]=$ligne["hits"];
		$table=$table.
		"<tr>
			<td class=legend>{{$weeksd[$ligne["day"]]}}:</td>
			<td style='font-size:13px'>$text</td>
		</tr>
			";
		
		
	}	
	
	
	
	$table=$table."</tbody></table>";
	$targetedfile="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".". md5($sql).".png";
	$gp=new artica_graphs();
	$gp->width=650;
	$gp->height=250;
	$gp->filename="$targetedfile";
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;
	$gp->y_title=null;
	$gp->x_title=$tpl->_ENGINE_parse_body("{days}");
	$gp->title=null;
	$gp->margin0=true;
	$gp->Fillcolor="blue@0.9";
	$gp->color="146497";
$t=time();
	$gp->line_green();
	if(is_file($targetedfile)){$image="<center><img src='$targetedfile'></center>";}
		$html=$html."
		<table style='width:100%'>
		<tbody>
		<tr>
			<td valign='top'>$image</td>
			<td valign='top'>$table</td>
		</tr>
		</tbody>
		</table>
	<table class='events-table-$t' style='display: none' id='events-table-$t' style='width:99%'></table>
<script>

$(document).ready(function(){
$('#events-table-$t').flexigrid({
	url: '$page?users-search=yes&table={$_GET["table"]}&www={$_GET["www"]}&field={$_GET["field"]}',
	dataType: 'json',
	colModel : [
		{display: '$hits', name : 'hits', width :60, sortable : false, align: 'left'},
		{display: '$member', name : 'uid', width :232, sortable : true, align: 'left'},
		{display: '$ipaddr', name : 'client', width :90, sortable : true, align: 'left'},
		{display: '$hostname', name : 'hostname', width :232, sortable : true, align: 'left'},
		{display: '$ComputerMacAddress', name : 'MAC', width : 100, sortable : false, align: 'left'},
	],

	searchitems : [
		{display: '$member', name : 'uid'},
		{display: '$ipaddr', name : 'client'},
		{display: '$hostname', name : 'hostname'},
		{display: '$ComputerMacAddress', name : 'MAC'},
		
		],
	sortname: 'client',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 833,
	height: 170,
	singleSelect: true
	
	});   
});

</script>";		
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function users_search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	
	$search='%';
	$table=$_GET["table"];
	$page=1;
	$field=$_GET["field"];
	$www=$_GET["www"];
	
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
		$sql="SELECT SUM(hits) as hits,`$field`,`client`,`hostname`,`MAC`,`uid`
		 FROM  $table GROUP BY `$field`,`client`,`hostname`,`MAC`,`uid` HAVING `$field`='$www' $searchstring";

		$results=$q->QUERY_SQL($sql,"artica_events");
		$total = mysql_num_rows($results);
		
	}else{
		$sql="SELECT SUM(hits) as hits,`$field`,`client`,`hostname`,`MAC`,`uid`
		 FROM  $table GROUP BY `$field`,`client`,`hostname`,`MAC`,`uid` HAVING `$field`='$www'";
		$results=$q->QUERY_SQL($sql,"artica_events");
		$total = mysql_num_rows($ligne);
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT SUM(hits) as hits,`$field`,`client`,`hostname`,`MAC`,`uid`
	FROM  $table GROUP BY `$field`,`client`,`hostname`,`MAC`,`uid` HAVING `$field`='$www' 
	$searchstring $FORCE_FILTER $ORDER $limitSql";
	
	
		
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	
	
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

	$data['rows'][] = array(
		'id' => $ligne['client'],
		'cell' => array($ligne["hits"],$ligne["uid"],$ligne["client"],$ligne["hostname"],$ligne["MAC"] )
		);
	}
	
	
echo json_encode($data);		

}


function hits($size=false){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_squid_builder();	
	$table=$_GET["table"];
	
	if(preg_match("#[0-9]+_hour$#", $table)){
		$year=substr($table, 0,4);
		$month=substr($table, 4,2);
		$day=substr($table, 6,2);
		$date="$year-$month-$day";
		$timeS=strtotime($date);
		$table=date("YW",$timeS)."_week";	
	}
	
	$www=$_GET["www"];
	$field=$_GET["field"];
	$titleW=$q->WEEK_TITLE_FROM_TABLENAME($table);
	$weeksd=array(1 => "Sunday", 2 => "Monday",3=>"Tuesday",4=>"Wednesday",5=>"Thursday",6=>"Friday",7=>"Saturday");
	$hits="hits";
	$hits_title="{hits}";
	if($size){$hits="size";$hits_title="{size} (MB)";};
	
	
	$html="<div id='title' style='font-size:16px;font-weight:bold'>{website}: $www $titleW</div>
	<center style='font-size:16px;font-weight:bold;margin-top:15px;width:80%;margin:10px'>
	<div style='border-top:1px solid #CCCCCC;border-bottom:1px solid #CCCCCC;padding:20px;'>
	$hits_title {byday}
	</div></center>";
	
	
	
	$xdata=array();
	$ydata=array();	
	$sql="SELECT SUM($hits) as hits,`day`,`$field` FROM  $table GROUP BY `day`,`$field` HAVING `$field`='$www'";
	if($field=="familysite"){$sql="SELECT SUM($hits) as hits,`day`,familysite FROM  $table GROUP BY `day`,familysite HAVING `familysite`='$www' ORDER BY `day`";}
	
	
	
	
	$table="<table style='width:100%'>";
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$xdata[]=$tpl->_ENGINE_parse_body("{{$weeksd[$ligne["day"]]}}");
		$text=$ligne["hits"];
		if($size){
		$ligne["hits"]=round(($ligne["hits"]/1024)/1000,2);
		$text=FormatBytes($text/1024);
		}
		$ydata[]=$ligne["hits"];
		$table=$table.
		"<tr>
			<td class=legend><strong>{{$weeksd[$ligne["day"]]}}:</strong></td>
			<td style='font-size:13px'><strong>$text</strong></td>
		</tr>
			";
		
		
	}	
	$table=$table."</tbody></table>";
	$targetedfile="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".". md5($sql).".png";
	$gp=new artica_graphs();
	$gp->width=650;
	$gp->height=350;
	$gp->filename="$targetedfile";
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;
	$gp->y_title=null;
	$gp->x_title=$tpl->_ENGINE_parse_body("{days}");
	$gp->title=null;
	$gp->margin0=true;
	$gp->Fillcolor="blue@0.9";
	$gp->color="146497";

	$gp->line_green();
	if(is_file($targetedfile)){$image="<center><img src='$targetedfile'></center>";}
		$html=$html."
		<table style='width:100%'>
		<tbody>
		<tr>
			<td valign='top'>$image</td>
			<td valign='top'>$table</td>
		</tr>
		</tbody>
		</table>
		";
	
		
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function websites(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_squid_builder();	
	$table=$_GET["table"];
	
	if(preg_match("#[0-9]+_hour$#", $table)){
		$year=substr($table, 0,4);
		$month=substr($table, 4,2);
		$day=substr($table, 6,2);
		$date="$year-$month-$day";
		$timeS=strtotime($date);
		$table=date("YW",$timeS)."_week";	
	}	
	
	
	$www=$_GET["www"];
	$field=$_GET["field"];
	$titleW=$q->WEEK_TITLE_FROM_TABLENAME($table);
	$weeksd=array(1 => "Sunday", 2 => "Monday",3=>"Tuesday",4=>"Wednesday",5=>"Thursday",6=>"Friday",7=>"Saturday");
	$hits="hits";
	$hits_title="{members}";
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$ComputerMacAddress=$tpl->_ENGINE_parse_body("{ComputerMacAddress}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$websites=$tpl->_ENGINE_parse_body("{websites}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	
	$html="<div id='title' style='font-size:16px;font-weight:bold'>{website}: $www $titleW</div>
	<center style='font-size:16px;font-weight:bold;margin-top:15px;width:80%;margin:10px'>
	<div style='border-top:1px solid #CCCCCC;border-bottom:1px solid #CCCCCC;padding:20px;'>
	$www {websites}
	</div></center>
	<table class='events-table-$t' style='display: none' id='events-table-$t' style='width:99%'></table>
<script>

$(document).ready(function(){
$('#events-table-$t').flexigrid({
	url: '$page?websites-search=yes&table={$_GET["table"]}&www={$_GET["www"]}&field={$_GET["field"]}',
	dataType: 'json',
	colModel : [
		{display: '$hits', name : 'hits', width :90, sortable : false, align: 'left'},
		{display: '$size', name : 'size', width :90, sortable : false, align: 'left'},
		{display: '$websites', name : 'sitename', width :600, sortable : true, align: 'left'},
		
	],

	searchitems : [
		{display: '$websites', name : 'sitename'},
		
		
		],
	sortname: 'hits',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 833,
	height: 170,
	singleSelect: true
	
	});   
});

</script>";		
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}


function websites_search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	
	$search='%';
	$table=$_GET["table"];
	$page=1;
	$www=$_GET["www"];
	
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
		$sql="SELECT SUM(hits) as hits,`familysite`,`sitename` FROM  $table GROUP BY `familysite`,`sitename` HAVING `familysite`='$www' $searchstring";
		$results=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = mysql_num_rows($results);
		
	}else{
		$sql="SELECT SUM(hits) as hits,`familysite`,`sitename` FROM  $table GROUP BY `familysite`,`sitename` HAVING `familysite`='$www'";
		$results=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = mysql_num_rows($results);
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT SUM(hits) as hits,SUM(size) as tsize,`familysite`,`sitename` FROM  $table GROUP BY `familysite`,`sitename` HAVING `familysite`='$www' $searchstring";
	
	
		
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	
	
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

		$js="Loadjs('$MyPage?www={$ligne["sitename"]}&field=sitename&table={$_GET["table"]}')";
		$ligne["tsize"]=FormatBytes($ligne["tsize"]/1024);
		
	$data['rows'][] = array(
		'id' => $ligne['sitename'],
		'cell' => array("<span style='font-size:14px'>{$ligne["hits"]}</span>","<span style='font-size:14px'>{$ligne["tsize"]}</span>",
	
		"<a href='javascript:blur();' OnClick=\"javascript:$js;\" style='font-size:14px;text-decoration:underline'>{$ligne["sitename"]}</a>" )
		);
	}
	
	
echo json_encode($data);		
}