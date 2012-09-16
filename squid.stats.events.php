<?php
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.mysql.squid.builder.php');
	
	$usersmenus=new usersMenus();
	if(!$usersmenus->AsSystemAdministrator){
		if(!$usersmenus->AsDansGuardianAdministrator){
			$tpl=new templates();
			$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
			echo "alert('$alert');";
			die();	
		}
	}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["Select-fields"])){select_fields();exit;}
if(isset($_GET["search_function_from_filename"])){search_function_from_filename();exit;}
if(isset($_POST["EmptyTask"])){EmptyTask();exit;}

popup();


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$description=$tpl->_ENGINE_parse_body("{description}");
	$select=$tpl->javascript_parse_text("{select}");
	$date=$tpl->_ENGINE_parse_body("{date}");
	$empty=$tpl->javascript_parse_text("{empty}");
	$t=time();
	$tablesize=868;
	$descriptionsize=705;
	$bts=array();
	
	if(is_numeric($_GET["tablesize"])){$tablesize=$_GET["tablesize"];}
	if(is_numeric($_GET["descriptionsize"])){$descriptionsize=$_GET["descriptionsize"];}
	$bts[]="{name: '$select', bclass: 'Search', onpress : SelectFields$t},";


		

	
	$tablejs="ufdbguard_admin_events";
	if($_GET["table"]<>null){$tablejs=$_GET["table"];}
	
	if($_GET["taskid"]>0){
		$bts[]="{name: '$empty', bclass: 'Delz', onpress : EmptyTask$t},";	
	}	
	if(count($bts)>0){
		$buttons="buttons : [".@implode("\n", $bts)." ],";
	}
	
	$html="
	<div style='margin-left:5px'>
	<table class='ufdbguard-events-$t' style='display: none' id='ufdbguard-events-$t' style='width:99%'></table>
	</div>
<script>
$(document).ready(function(){
$('#ufdbguard-events-$t').flexigrid({
	url: '$page?search=yes&filename={$_GET["filename"]}&taskid={$_GET["taskid"]}&category=stats&table={$_GET["table"]}',
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
	height: 500,
	singleSelect: true
	
	});   
});

function SelectFields$t(){
	YahooWin2('550','$page?Select-fields=yes&table=ufdbguard_admin_events&t=$t&taskid={$_GET["taskid"]}','$select');

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

function EmptyTask(){
	$q=new mysql();
	if($_POST["Table"]==null){$_POST["Table"]="ufdbguard_admin_events";}
	$q->QUERY_SQL("DELETE FROM `{$_POST["Table"]}` WHERE `TASKID`='{$_POST["EmptyTask"]}'","artica_events");
	
	writelogs("DELETE FROM `{$_POST["Table"]}` WHERE `TASKID`='{$_POST["EmptyTask"]}' = $q->affected_rows",__FUNCTION__,__FILE__,__LINE__);
	if(!$q->ok){echo $q->mysql_error;}
	
}

function select_fields(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql();
	$t=$_GET["t"];
	$sql="SELECT filename,category FROM ufdbguard_admin_events 
		GROUP BY category,filename HAVING category='stats' ORDER BY filename ";
	$results = $q->QUERY_SQL($sql,"artica_events");
	$catz[null]="{select}";
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$catz[$ligne["filename"]]=$ligne["filename"];
		
	}
	
	

	$html="<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:18px'>{filename}:</td>
		<td>". Field_array_Hash($catz, "filename-$t",null,"Changzctz$t()",null,0,"font-size:18px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{function}:</td>
		<td><div id='catz-$t'></div></td>
	</tr>	
	<tr>
		<td colspan=2 align='right'><hr>". button("{search}","ChangeTableF$t()",16)."</td>
	</tr>
	</table>
	<script>
	function Changzctz$t(){
		var catz=document.getElementById('filename-$t').value;
		LoadAjaxTiny('catz-$t','$page?search_function_from_filename='+catz+'&t=$t');
		
	}
	
	
	function ChangeTableF$t(){
		var filename=document.getElementById('filename-$t').value;
		if(document.getElementById('function-$t')){
			zfunction=document.getElementById('function-$t').value;
		}
	  	$('#ufdbguard-events-$t').flexOptions({url: '$page?search=yes&filename='+filename+'&taskid={$_GET["taskid"]}&category=stats&function='+zfunction+'&table=ufdbguard_admin_events'}).flexReload(); 
	}
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function search_function_from_filename(){
	$q=new mysql();
	$tpl=new templates();
	$t=$_GET["t"];
	$sql="SELECT function,filename FROM ufdbguard_admin_events GROUP BY function,filename HAVING filename='{$_GET["search_function_from_filename"]}' ORDER BY function";
	$results = $q->QUERY_SQL($sql,"artica_events");
	$filze[null]="{select}";
	while ($ligne = mysql_fetch_assoc($results)) {
		$filze[$ligne["function"]]=$ligne["function"];
		
	}
	echo $tpl->_ENGINE_parse_body(field_array_Hash($filze, "function-$t",null,"style:font-size:18px"));
}


function search(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql();	
	$WHERE="category = 'update'";
	$table="ufdbguard_admin_events";
	if($_GET["table"]<>null){$table=$_GET["table"];$WHERE=1;}
	$search='%';
	$page=1;
	
	
	if($_GET["category"]<>null){
		$WHERE="category = '{$_GET["category"]}'";
	}
	
	if($_GET["function"]<>null){
		$ADD3= " AND `function`='{$_GET["function"]}'";
	}
	
	if($_GET["filename"]<>null){$ADD2=" AND filename='{$_GET["filename"]}'";}
	if(!is_numeric($_GET["taskid"])){$_GET["taskid"]=0;}
	
	if($_GET["taskid"]>0){$ADD2=$ADD2." AND TASKID='{$_GET["taskid"]}'";$WHERE=1;}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*{$_POST["query"]}*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE $WHERE $ADD2$ADD3$searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["tcount"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE $WHERE $ADD2$ADD3";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	
	$sql="SELECT * FROM $table WHERE $WHERE $ADD2$ADD3$searchstring $ORDER $limitSql";
	
	$line=$tpl->_ENGINE_parse_body("{line}");
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	
	
	
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



//update