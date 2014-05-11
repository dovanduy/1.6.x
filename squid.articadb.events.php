<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.dansguardian.inc');
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	$user=new usersMenus();
	if(!$user->AsSquidAdministrator){
		$tpl=new templates();
		echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}").");";
		exit;
		
	}
	
	if(isset($_GET["search"])){search();exit;}

	table();
	
function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$description=$tpl->_ENGINE_parse_body("{description}");
	$select=$tpl->javascript_parse_text("{select}");
	$date=$tpl->_ENGINE_parse_body("{date}");
	$empty=$tpl->javascript_parse_text("{empty}");
	$t=time();
	$tablesize=629;
	$descriptionsize=465;
	$bts=array();
	
	if(is_numeric($_GET["tablesize"])){$tablesize=$_GET["tablesize"];}
	if(is_numeric($_GET["descriptionsize"])){$descriptionsize=$_GET["descriptionsize"];}
	if($_GET["table"]<>null){
		if($_GET["taskid"]==0){
			$bts[]="{name: '$select', bclass: 'Search', onpress : SelectFields$t},";
		}
	}
	
	$tablejs="ufdbguard_admin_events";
	if($_GET["table"]<>null){$tablejs=$_GET["table"];}
	
	if($_GET["taskid"]>0){
		$bts[]="{name: '$empty', bclass: 'Delz', onpress : EmptyTask$t},";
	}
	if(count($bts)>0){
	$buttons="buttons : [".@implode("\n", $bts)." ],";
	}
	
	$q=new mysql();
	if($q->COUNT_ROWS("ufdbguard_admin_events", "artica_events")>0){$q->QUERY_SQL("TRUNCATE TABLE ufdbguard_admin_events","artica_events");}
	if($q->COUNT_ROWS("system_admin_events", "artica_events")>0){$q->QUERY_SQL("TRUNCATE TABLE system_admin_events","artica_events");}
	$CountEvents=$q->COUNT_ROWS($tablejs, "artica_events");
	$CountEvents=numberFormat($CountEvents, 0 , '.' , ' ');
				$title=$tpl->_ENGINE_parse_body("$CountEvents {events}");
	
	$html="
	<div style='margin-left:5px'>
	<table class='update-events-$t' style='display: none' id='update-events-$t' style='width:99%'></table>
	</div>
	<script>
	$(document).ready(function(){
	$('#update-events-$t').flexigrid({
	url: '$page?search=yes&filename={$_GET["filename"]}&taskid={$_GET["taskid"]}&category={$_GET["category"]}&table={$_GET["table"]}',
	dataType: 'json',
	colModel : [
	{display: '$date', name : 'zDate', width : 120, sortable : true, align: 'left'},
	{display: '$description', name : 'description', width : 767, sortable : false, align: 'left'},
	],$buttons
	searchitems : [
	{display: '$description', name : 'description'},
	],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 25,
	showTableToggleBtn: false,
	width: '99%',
	height: 350,
	singleSelect: true
	
	});
	});
	
	function SelectFields$t(){
	YahooWin2('550','$page?Select-fields=yes&table={$_GET["table"]}&t=$t&taskid={$_GET["taskid"]}','$select');
	
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
		
	
	// webfilter_updateev
	
}

function search(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$table="webfilter_updateev";
	
	
	
	$search='%';
	$page=1;

	if($_GET["category"]<>null){
		$WHERE="category = '{$_GET["category"]}'";
	}

	if($_GET["filename"]<>null){$ADD2=" AND filename='{$_GET["filename"]}'";}
	if(!is_numeric($_GET["taskid"])){$_GET["taskid"]=0;}

	if($_GET["taskid"]>0){
		if(!preg_match("#Task.*?[0-9]+#", $table)){
			$ADD2=$ADD2." AND TASKID='{$_GET["taskid"]}'";
			$WHERE=1;
		}
	}

	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}

	if (isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();

	if($searchstring<>null){

		$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["tcount"];

	}else{
		$total = $q->COUNT_ROWS($table);
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql";

	$line=$tpl->_ENGINE_parse_body("{line}");
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){
		json_error_show($q->mysql_error,1);
	}


	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("No data");}


	while ($ligne = mysql_fetch_assoc($results)) {

		$description=$ligne["description"];
		$description=$tpl->_ENGINE_parse_body($description);
		$description=str_replace("\n", "<br>", $description);
		$description=wordwrap($description,75,"<br>");
		$description=str_replace("<br><br>","<br>",$description);
		$ttim=strtotime($ligne['zDate']);
		$dateD=date('Y-m-d',$ttim);
		$color="black";
		if(preg_match("#(error|fatal|overloaded|aborting)#i", $description)){
			$color="#BA0000";
		}

		$function="<div style='margin-top:-4px;margin-left:-5px'><i style='font-size:11px'>{$ligne["filename"]}:{$ligne["function"]}() $line {$ligne["line"]}</i></div>";
		if(preg_match("#(.+?)\s+thumbnail#", $description,$re)){
			$description=str_replace($re[1], "<a href=\"javascript:blur();\"
					OnClick=\"javascript:Loadjs('squid.traffic.statistics.days.php?today-zoom=yes&type=req&familysite={$re[1]}&day=$dateD');\" style='text-decoration:underline'>{$re[1]}</a>", $description);
		}



		$data['rows'][] = array(
				'id' => $ligne['zDate'],
				'cell' => array(
						"<strong style='font-size:13px;color:$color'>{$ligne["zDate"]}</strong>",
						"<div style='font-size:13px;font-weight:normal;color:$color'>$description$function</div>",
				)
		);
	}


	echo json_encode($data);

}

?>