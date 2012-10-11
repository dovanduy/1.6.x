<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	
	
	
	$usersprivs=new usersMenus();
	if(!$usersprivs->AsSystemAdministrator){
		$tpl=new templates();
		echo "alert('".$tpl->javascript_parse_text('{ERROR_NO_PRIVS}')."');";
		die();
		
	}
	if(isset($_GET["in-front-ajax"])){js();exit;}
	if(isset($_GET["mysql-events"])){page_events();exit;}
	if(isset($_GET["flexgrid-systems"])){flexgrid_systems();exit;}
	if(isset($_GET["flexgrid-artica"])){flexgrid_artica();exit;}
	if(isset($_GET["flexgrid-artica-table"])){flexgrid_artica_table();exit;}
	if(isset($_GET["artica-events"])){flexgrid_artica_rows();exit;}
	if(isset($_GET["flexgrid-artica-catz"])){flexgrid_artica_categories();exit;}
	if(isset($_GET["search_filename_from_catz"])){search_filename_from_catz();exit;}
	
page();

function js(){
	$page=CurrentPageName();
	echo "$('#BodyContent').load('$page');";
}

function page(){
	$t=time();
	$page=CurrentPageName();
	$html="<div id='div-$t'></div>
	<script>
		$('#events-table-$t').remove();
		LoadAjax('div-$t','$page?flexgrid-systems=yes&t=$t');
	</script>
	";
	echo $html;
	
}
function flexgrid_artica(){
	$t=$_GET["t"];
	$page=CurrentPageName();
	$html="<div id='div-$t'></div>
	<script>
		$('#events-table-$t').remove();
		LoadAjax('div-$t','$page?flexgrid-artica-table=yes');
	</script>
	";
	echo $html;
	
}

function flexgrid_artica_table(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$description=$tpl->_ENGINE_parse_body("{description}");
	$select=$tpl->javascript_parse_text("{select}");
	$date=$tpl->_ENGINE_parse_body("{date}");
	$empty=$tpl->javascript_parse_text("{empty}");
	$mysql_events=$tpl->javascript_parse_text("{mysql_events}");
	$categories=$tpl->javascript_parse_text("{categories}");
	$t=$_GET["t"];
	$tablesize=871;
	$descriptionsize=700;
	$bts=array();
	
	if(is_numeric($_GET["tablesize"])){$tablesize=$_GET["tablesize"];}
	if(is_numeric($_GET["descriptionsize"])){$descriptionsize=$_GET["descriptionsize"];}
	if($_GET["table"]<>null){
		if($_GET["taskid"]==0){
			$bts[]="{name: '$select', bclass: 'Search', onpress : SelectFields$t},";
		}

		
	}
	
	$bts[]="{name: '$mysql_events', bclass: 'SSQL', onpress : MySqlSystemEvents},";
	$bts[]="{name: '$categories', bclass: 'Catz', onpress : MySqlChooseCatz},";
	
	
	
	if($_GET["taskid"]>0){
		$bts[]="{name: '$empty', bclass: 'Delz', onpress : EmptyTask$t},";	
	}	
	if(count($bts)>0){
		$buttons="buttons : [".@implode("\n", $bts)." ],";
	}
	
	$html="
	<div style='margin-left:5px'>
	<table class='events-table-$t' style='display: none' id='events-table-$t' style='width:99%'></table>
	</div>
<script>
$(document).ready(function(){
$('#events-table-$t').flexigrid({
	url: '$page?artica-events=yes&filename={$_GET["filename"]}&taskid={$_GET["taskid"]}&category={$_GET["category"]}&table={$_GET["table"]}',
	dataType: 'json',
	colModel : [
		{display: '$date', name : 'zDate', width : 120, sortable : true, align: 'left'},
		{display: '$description', name : 'description', width : $descriptionsize, sortable : false, align: 'left'},
	],$buttons
	searchitems : [
		{display: '$description', name : 'description'},
		],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 25,
	showTableToggleBtn: false,
	width: $tablesize,
	height: 350,
	singleSelect: true
	
	});   
});

function SelectFields$t(){
	YahooWin2('550','$page?Select-fields=yes&table={$_GET["table"]}&t=$t&taskid={$_GET["taskid"]}','$select');

}

	
function MySqlSystemEvents(){
		LoadAjax('div-$t','$page');
	
}

function MySqlChooseCatz(){
	YahooWin('650','$page?flexgrid-artica-catz=yes&t=$t','$categories');
}

	var x_EmptyTask$t=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}
		$('#ufdbguard-events-$t').flexReload();		
	}

function EmptyTask$t(){
	if(confirm('$empty::{$_GET["taskid"]}')){
		var XHR = new XHRConnection();
		XHR.appendData('EmptyTask','{$_GET["taskid"]}');
		XHR.appendData('Table','{$_GET["table"]}');
		XHR.sendAndLoad('$page', 'POST',x_EmptyTask$t);			
    }
}

</script>

";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
function search_filename_from_catz(){
	$q=new mysql();
	$tpl=new templates();
	$t=$_GET["t"];
	$sql="SELECT category,filename FROM system_admin_events GROUP BY category,filename HAVING category='{$_GET["search_filename_from_catz"]}' ORDER BY filename";
	$results = $q->QUERY_SQL($sql,"artica_events");
	$filze[null]="{select}";
	while ($ligne = mysql_fetch_assoc($results)) {
		$filze[$ligne["filename"]]=$ligne["filename"];
		
	}
	echo $tpl->_ENGINE_parse_body(field_array_Hash($filze, "filename-$t",null,"style:font-size:18px"));
}


function flexgrid_systems(){
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$context=$tpl->_ENGINE_parse_body("{context}");	
	$events=$tpl->_ENGINE_parse_body("{events}");	
	$artica_events=$tpl->_ENGINE_parse_body("{artica_events}");	
	$empty_events_text_ask=$tpl->javascript_parse_text("{empty_events_text_ask}");	
	$TB_HEIGHT=450;
	$TB_WIDTH=871;
	$TB2_WIDTH=834;
	if(isset($_GET["full-size"])){
		$TB_WIDTH=872;
		$TB2_WIDTH=610;
	}
	
	if(isset($_GET["full-medium"])){
		$TB_WIDTH=845;
		$TB_HEIGHT=400;
		$TB2_WIDTH=580;
		
	}
	
	
	
	$buttons="
	buttons : [
	{name: '$artica_events', bclass: 'SSQL', onpress : MySqlArticaEvents},
	
		],	";
	$html="
	<table class='events-table-$t' style='display: none' id='events-table-$t' style='width:99%'></table>
<script>
	$(document).ready(function(){
	$('#events-table-$t').flexigrid({
		url: '$page?mysql-events=yes&instance-id=$instance_id',
		dataType: 'json',
		colModel : [
			{display: '$events', name : 'events', width : $TB2_WIDTH, sortable : false, align: 'left'},
		],
		$buttons
	
		searchitems : [
			{display: '$events', name : 'text'},
			],
		sortname: 'zDate',
		sortorder: 'desc',
		usepager: true,
		title: '',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: $TB_WIDTH,
		height: $TB_HEIGHT,
		singleSelect: true,
		rpOptions: [10, 20, 30, 50,100,200,500]
		
		});   
	});



	function articaShowEvent(ID){
		 YahooWin6('750','$page?ShowID='+ID,'$title::'+ID);
	}
	
	var x_EmptyEvents= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		$('#events-table-$t').flexReload();
		//$('#grid_list').flexOptions({url: 'newurl/'}).flexReload(); 
		// $('#fgAllPatients').flexOptions({ query: 'blah=qweqweqwe' }).flexReload();
		
	}		
	
function MySqlArticaEvents(){
		LoadAjax('div-$t','$page?flexgrid-artica=yes');
	
}
	
</script>";
	
	echo $html;		

	
	
}
function flexgrid_artica_rows(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql();	
	$WHERE="category LIKE 'mysql%'";
	$table="system_admin_events";
	$search='%';
	$page=1;
	
	$category=$_GET["category"];
	if($category==null){json_error_show("Select a category first",1);}
	$WHERE="category='$category'";

	if($_GET["filename"]<>null){$ADD2=" AND filename='{$_GET["filename"]}'";}
	if(!is_numeric($_GET["taskid"])){$_GET["taskid"]=0;}
	
	if($_GET["taskid"]>0){$ADD2=$ADD2." AND TASKID='{$_GET["taskid"]}'";$WHERE=1;}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE $WHERE $ADD2$searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		if(!$q->ok){echo json_error_show($q->mysql_error,1);}
		$total = $ligne["tcount"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE $WHERE $ADD2";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		if(!$q->ok){echo json_error_show($q->mysql_error,1);}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	
	$sql="SELECT * FROM $table WHERE $WHERE $ADD2$searchstring $ORDER $limitSql";
	
	$line=$tpl->_ENGINE_parse_body("{line}");
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){echo json_error_show($q->mysql_error,1);}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array(date('Y-m-d H:i:s'),$tpl->_ENGINE_parse_body("{no_event}<br>$sql"),"", "",""));}
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$description=$ligne["description"];
		$description=str_replace("\n", "<br>", $description);
		$description=wordwrap($description,75,"<br>");
		$description=str_replace("<br><br>","<br>",$description);
		$ttim=strtotime($ligne['zDate']);
		$dateD=date('Y-m-d',$ttim);
		
		$function="<div style='margin-top:-4px;margin-left:-5px'><i style='font-size:11px'>{$ligne["filename"]}:{$ligne["function"]}() $line {$ligne["line"]}</i></div>";
		if(preg_match("#(.+?)\s+thumbnail#", $description,$re)){
			$description=str_replace($re[1], "<a href=\"javascript:blur();\" 
			OnClick=\"javascript:Loadjs('squid.traffic.statistics.days.php?today-zoom=yes&type=req&familysite={$re[1]}&day=$dateD');\" style='text-decoration:underline'>{$re[1]}</a>", $description);
		}
		
		
		
	$data['rows'][] = array(
		'id' => $ligne['zDate'],
		'cell' => array(
		"<strong style='font-size:13px'>{$ligne["zDate"]}</strong>",
		"<div style='font-size:13px;font-weight:normal'>$description$function</div>",
		)
		);
	}
	
	
echo json_encode($data);	
	
}

function flexgrid_artica_categories(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql();
	$t=$_GET["t"];
	$sql="SELECT category FROM system_admin_events GROUP BY category HAVING category LIKE 'mysql%' ORDER BY category";
	$results = $q->QUERY_SQL($sql,"artica_events");
	$catz[null]="{select}";
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$catz[$ligne["category"]]=$ligne["category"];
		
	}	
	

	$html="<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:18px'>{category}:</td>
		<td>". Field_array_Hash($catz, "category-$t",null,"Changzctz$t()",null,0,"font-size:18px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{filename}:</td>
		<td><div id='catz-$t'></div></td>
	</tr>	
	<tr>
		<td colspan=2 align='right'><hr>". button("{search}","ChangeTableF$t()",16)."</td>
	</tr>
	</table>
	<script>
	function Changzctz$t(){
		var catz=document.getElementById('category-$t').value;
		LoadAjaxTiny('catz-$t','$page?search_filename_from_catz='+catz+'&t=$t');
		
	}
	
	
	function ChangeTableF$t(){
		var ctz=document.getElementById('category-$t').value;
		var filename='';
		if(document.getElementById('filename-$t')){filename=document.getElementById('filename-$t').value;}
	  	$('#events-table-$t').flexOptions({url: '$page?artica-events=yes&filename='+filename+'&taskid={$_GET["taskid"]}&category='+ctz+'&table={$_GET["table"]}'}).flexReload(); 
	}
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
		
	
}

function page_events(){
	$page=CurrentPageName();
	$instance_id=$_GET["instance-id"];
	if(!is_numeric($instance_id)){$instance_id=0;}	
	$tpl=new templates();
	$sock=new sockets();
	$time=time();
	$datas=unserialize(base64_decode($sock->getFrameWork("services.php?mysql-events=yes&instance-id=$instance_id")));
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($datas);
	$data['rows'] = array();
	if($_POST["query"]<>null){
		$search=string_to_regex($_POST["query"]);
	}
	
	while (list ($num, $ligne) = each ($datas) ){
	if($search<>null){if(!preg_match("#$search#i", $ligne)){continue;}}
	$data['rows'][] = array(
		'id' => md5($ligne),
		'cell' => array("<code style='font-size:14px'>$ligne</code>") 
		);
	}
	
	echo json_encode($data);
}