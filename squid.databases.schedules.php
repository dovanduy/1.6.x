<?php
if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.artica.graphs.inc');
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_GET["search"])){search();exit;}
if(isset($_GET["AddNewSchedule-js"])){AddNewSchedule_js();exit;}
if(isset($_GET["AddNewSchedule-popup"])){AddNewSchedule_popup();exit;}
if(isset($_GET["explainthis"])){AddNewSchedule_explain();exit;}
if(isset($_POST["TimeDescription"])){AddNewSchedule_save();exit;}
if(isset($_POST["schedule-enable"])){AddNewSchedule_enable();exit;}
if(isset($_POST["schedule-delete"])){AddNewSchedule_delete();exit;}
if(isset($_POST["schedule-run"])){AddNewSchedule_run();exit;}

if(isset($_POST["DisableSquidDefaultSchedule"])){DisableSquidDefaultSchedule();exit;}
page();

function AddNewSchedule_js(){
	$ID=$_GET["ID"];
	$tpl=new templates();
	$page=CurrentPageName();
	
	$title="{new_schedule}";
	
	if($ID>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_schedules WHERE ID=$ID"));
		$title="{schedule}::$ID::{$ligne["TaskType"]}";
	}
	
	$title=$tpl->_ENGINE_parse_body($title);
	echo "YahooWin2('550','$page?AddNewSchedule-popup=yes&ID=$ID','$title')";
	
}

function AddNewSchedule_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$q=new mysql_squid_builder();
	$no_schedule_set=$tpl->javascript_parse_text("{no_schedule_set}");
	$buttontext="{add}";
	$ID=$_GET["ID"];
		if($ID>0){
			$buttontext="{apply}";
			$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_schedules WHERE ID=$ID"));
			$ligne["TimeDescription"]=utf8_encode($ligne["TimeDescription"]);
		}
		
		if(!is_numeric($ligne["TaskType"])){$ligne["TaskType"]=0;}
		if(!is_numeric($ID)){$ID=0;}
		
	$task_type=$q->tasks_array;
	if(!$users->KAV4PROXY_INSTALLED){
		unset($task_type[5]);
		unset($task_type[12]);
	}
	
	if(!$users->UPDATE_UTILITYV2_INSTALLED){
		unset($task_type[13]);
	}

	
	$t=time();
	
	$html="
	<div id='div-$t'>
	<table style='width:99%' class='form'>
	<tr>
		<td class=legend style='font-size:14px'>{task_type}:</td>
		<td>". Field_array_Hash($task_type, "TaskType-$t",$ligne["TaskType"],"ExplainTaskType()",null,0,"font-size:14px")."</td>
	</tr>
	<tr>
		<td colspan=2><div id='$t-explain'></div></td>
	</tr>
	<tr>
	<tr>
		<td class=legend style='font-size:14px'>{description}:</td>
		<td>". Field_text("TimeDescription", $ligne["TimeDescription"],"font-size:14px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{schedule}:</td>
		<td><input type='hidden' id='TimeText-$t' value='{$ligne["TimeText"]}' style='font-size:16px'>
		". button("{browse}...","Loadjs('cron.php?field=TimeText-$t')",12)."</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'><hr>". button($buttontext,"SaveTaskSquid$t()",16)."</td>
	</tr>
	</table>
	</div>
	<script>
		function ExplainTaskType(){
			LoadAjax('$t-explain','$page?explainthis='+document.getElementById('TaskType-$t').value);
		
		}
		
	var x_SaveTaskSquid$t=function (obj) {
		var ID='{$_GET["ID"]}';
		var results=obj.responseText;
		if(results.length>0){alert(results);}		
		YahooWin2Hide();
		SquidCrontaskUpdateTable();
	}	


	function SaveTaskSquid$t(){
		
		var tt=document.getElementById('TimeText-$t').value;
		if(tt.length<4){
			alert('$no_schedule_set `'+tt+'`');
			return;
		}
	  	var XHR = new XHRConnection();
	  	XHR.appendData('TimeDescription',document.getElementById('TimeDescription').value);
	  	XHR.appendData('TimeText',document.getElementById('TimeText-$t').value);
		XHR.appendData('ID','{$_GET["ID"]}');
	  	XHR.appendData('TaskType',document.getElementById('TaskType-$t').value);
	  	AnimateDiv('div-$t');
	  	XHR.sendAndLoad('$page', 'POST',x_SaveTaskSquid$t);
	}		

		
	ExplainTaskType();	
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function DisableSquidDefaultSchedule(){
	$sock=new sockets();
	$sock->SET_INFO("DisableSquidDefaultSchedule", $_POST["DisableSquidDefaultSchedule"]);
	$sock->getFrameWork("squid.php?build-schedules=yes");
}


function AddNewSchedule_save(){
	$users=new usersMenus();
	$q=new mysql_squid_builder();
	
	$task_type=$q->tasks_array;

	if(!$users->KAV4PROXY_INSTALLED){unset($task_type[5]);}
	$tpl=new templates();

	$defaultdesc=$tpl->javascript_parse_text($task_type[$_POST["TaskType"]]);
	if($_POST["TimeDescription"]==null){$_POST["TimeDescription"]=$defaultdesc ." : {$_POST["TimeText"]}";}
	
	$_POST["TimeDescription"]=mysql_escape_string($_POST["TimeDescription"]);
	
	$sql="INSERT IGNORE INTO webfilters_schedules (TimeDescription,TimeText,TaskType,enabled) 
	VALUES('{$_POST["TimeDescription"]}','{$_POST["TimeText"]}','{$_POST["TaskType"]}',1)";
	
	if($_POST["ID"]>0){
		$sql="UPDATE webfilters_schedules SET 
			TimeDescription='{$_POST["TimeDescription"]}',
			TimeText='{$_POST["TimeText"]}',
			TaskType='{$_POST["TaskType"]}' WHERE ID={$_POST["ID"]}
			";
		
	}
	
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS("webfilters_schedules")){$q->CheckTables();}
	$q->QUERY_SQL($sql); 
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-schedules=yes");	
	
}


function AddNewSchedule_delete(){
	$sql="DELETE FROM webfilters_schedules WHERE ID={$_POST["ID"]}";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}	
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-schedules=yes");	
	
}

function AddNewSchedule_run(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?run-scheduled-task={$_POST["ID"]}");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{apply_upgrade_help}");
	
	
	
}


function AddNewSchedule_explain(){
	if($_GET["explainthis"]==0){return;}
	$q=new mysql_squid_builder();
	if(!isset($q->tasks_explain_array[$_GET["explainthis"]])){return;}
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("<div class=explain style='font-size:14px'>{$q->tasks_explain_array[$_GET["explainthis"]]}</div>");
}

function AddNewSchedule_enable(){
	
	$sql="UPDATE webfilters_schedules SET enabled={$_POST["value"]} WHERE ID={$_POST["ID"]}";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-schedules=yes");	
	
}


function page(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$purge_catagories_database_explain=$tpl->javascript_parse_text("{purge_catagories_database_explain}");
	$purge_catagories_table_explain=$tpl->javascript_parse_text("{purge_catagories_table_explain}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$SaveToDisk=$tpl->_ENGINE_parse_body("{SaveToDisk}");
	$addCat=$tpl->_ENGINE_parse_body("{add} {category}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$task=$tpl->_ENGINE_parse_body("{task}");
	$new_schedule=$tpl->_ENGINE_parse_body("{new_schedule}");
	$DisableSquidDefaultSchedule=$sock->GET_INFO("DisableSquidDefaultSchedule");
	if(!is_numeric($DisableSquidDefaultSchedule)){$DisableSquidDefaultSchedule=0;}
	$explain=$tpl->_ENGINE_parse_body("{explain_squid_tasks}");
	$run=$tpl->_ENGINE_parse_body("{run}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$run_this_task_now=$tpl->javascript_parse_text("{run_this_task_now} ?");
	$parameters=$tpl->_ENGINE_parse_body("{parameters}");
	
	$qS=new mysql_squid_builder();
	$q=new mysql();
	$tasks=$tpl->_ENGINE_parse_body("{tasks}");
	$CountTasks=$qS->COUNT_ROWS("webfilters_schedules", "artica_backup");
	$CountEvents=$q->COUNT_ROWS("ufdbguard_admin_events", "artica_events");
	$events=$tpl->_ENGINE_parse_body("{events}");	
	
	$t=time();
	$html="
	<div class=explain>$explain</div>


	<div style='margin-left:-15px'>
	<table class='$t' style='display: none' id='$t' style='width:99%'></table>
	</div>
<script>
var rowSquidTask='';
$(document).ready(function(){
$('#$t').flexigrid({
	url: '$page?search=yes&minisize={$_GET["minisize"]}',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'ID', width : 32, sortable : true, align: 'center'},
		{display: '$task', name : 'TaskType', width : 217, sortable : false, align: 'left'},
		{display: '$description', name : 'TimeDescription', width : 410, sortable : false, align: 'left'},
		{display: '$run', name : 'run', width : 32, sortable : false, align: 'left'},
		{display: '$events', name : 'run1', width : 32, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'enable', width : 32, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'delete', width : 32, sortable : false, align: 'center'}
	],
buttons : [
	{name: '$new_schedule', bclass: 'add', onpress : AddNewSchedule},
	{name: '$parameters', bclass: 'Settings', onpress : Parmaeters$t},
	
		],	
	searchitems : [
		{display: '$description', name : 'TimeDescription'},
		],
	sortname: 'ID',
	sortorder: 'asc',
	usepager: true,
	title: '$CountTasks $tasks $CountEvents $events',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: 897,
	height: 350,
	singleSelect: true
	
	});   
});	

	function AddNewSchedule(category){
			Loadjs('$page?AddNewSchedule-js=yes&ID=0');
	}
	
	function SquidCrontaskUpdateTable(){
		$('#$t').flexReload();
	 }
	
	var x_SquidTaskEnable=function (obj) {
		var ID='{$_GET["ID"]}';
		var results=obj.responseText;
		if(results.length>0){alert(results);}		
	}
	
	function Parmaeters$t(){
		Loadjs('schedules.php?schedules-params=yes');
	}	

	var x_DisableSquidDefaultScheduleCheck=function (obj) {
		var ID='{$_GET["ID"]}';
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		$('#$t').flexReload();		
	}		


	function SquidTaskEnable(md,id){
	  	var XHR = new XHRConnection();
	  	if(document.getElementById(md).checked){XHR.appendData('value',1);}else{XHR.appendData('value',0);}
		XHR.appendData('ID',id);
	  	XHR.appendData('schedule-enable','yes');
	  	XHR.sendAndLoad('$page', 'POST',x_SquidTaskEnable);
	}

	function DisableSquidDefaultScheduleCheck(){
	  	var XHR = new XHRConnection();
	  	if(document.getElementById('DisableSquidDefaultSchedule').checked){XHR.appendData('DisableSquidDefaultSchedule',1);}
	  	else{XHR.appendData('DisableSquidDefaultSchedule',0);}
	  	XHR.sendAndLoad('$page', 'POST',x_DisableSquidDefaultScheduleCheck);	
	}
	
	
	function SquidTaskRun(ID,explain){
		if(confirm('$run_this_task_now `'+explain+'`')){
			var XHR = new XHRConnection();
			XHR.appendData('ID',ID);
	  		XHR.appendData('schedule-run','yes');
	  		XHR.sendAndLoad('$page', 'POST',x_SquidTaskEnable);		
		}
	
	}
	
	
	var x_SquidTaskDelete=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}
		$('#rowSquidTask'+rowSquidTask).remove();
	}	
	
	function SquidTaskDelete(ID){
		rowSquidTask=ID;
	  	var XHR = new XHRConnection();
		XHR.appendData('ID',ID);
	  	XHR.appendData('schedule-delete','yes');
	  	XHR.sendAndLoad('$page', 'POST',x_SquidTaskDelete);	
	}
	
	
	
</script>";
	
	echo $html;
	
}


function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$sock=new sockets();
	$search='%';
	$table="webfilters_schedules";
	$page=1;
	$ORDER="ORDER BY ID DESC";
	$sock=new sockets();
	$DisableSquidDefaultSchedule=$sock->GET_INFO("DisableSquidDefaultSchedule");
	if(!is_numeric($DisableSquidDefaultSchedule)){$DisableSquidDefaultSchedule=0;}	
	
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	
	$total=0;
	if($q->COUNT_ROWS($table,"artica_events")==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search') ";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	
	
	
	$data = array();$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();	
	if(!$q->ok){$data['rows'][] = array('id' => $ligne[time()+1],'cell' => array($q->mysql_error,"", "",""));$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));echo json_encode($data);return;}	
	
//######"
	//TimeText TimeDescription TaskType enabled
	
	
	$q2=new mysql();
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$events="&nbsp;";
		$md5=md5("SquidTask{$ligne['ID']}");
		$TaskType=$ligne["TaskType"];
		$jstaskexplain=$tpl->javascript_parse_text($q->tasks_array[$ligne["TaskType"]]);
		$ligne["TaskType"]=$tpl->_ENGINE_parse_body($q->tasks_array[$ligne["TaskType"]]);
		
		
		$enable=Field_checkbox($md5, 1,$ligne["enabled"],"SquidTaskEnable('$md5',{$ligne['ID']})");
		$delete=imgtootltip("delete-24.png","{delete} {$ligne['ID']}","SquidTaskDelete('{$ligne['ID']}')");
		$run=$tpl->_ENGINE_parse_body(imgtootltip("24-run.png","{run} {$ligne['ID']}","SquidTaskRun('{$ligne['ID']}','$jstaskexplain')"));;
		
		
		
		if($ligne["enabled"]==0){$color="#A0A0A0";}
		
		
		$ligne2=mysql_fetch_array($q2->QUERY_SQL("SELECT COUNT(TASKID) as tcount FROM 
		ufdbguard_admin_events WHERE TASKID='{$ligne['ID']}'","artica_events"));
		
		
		if($ligne2["tcount"]>0){
			$events=imgtootltip("events-24.png","{events} {$ligne['ID']}","Loadjs('squid.update.events.php?taskid={$ligne['ID']}')");
		}
		
		if($TaskType==21){$color="#A0A0A0";}
		if($q->tasks_disabled[$TaskType]){$color="#A0A0A0";$enable="&nbsp;";}
		if($EnableRemoteStatisticsAppliance==1){
			if($q->tasks_remote_appliance[$TaskType]){
				$color="#A0A0A0";$enable="&nbsp;";
			}
		}
		
		
		
		$span="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?AddNewSchedule-js=yes&ID={$ligne['ID']}');\"
		 style='font-size:16px;font-weight:bold;color:$color;text-decoration:underline'>";
		$ligne["TimeDescription"]=utf8_encode($ligne["TimeDescription"]);
		
		//rowSquidTask
	$data['rows'][] = array(
		'id' => "SquidTask".$ligne['ID'],
		'cell' => array("$span{$ligne['ID']}</a>",
		"$span"."[".$TaskType."]&nbsp;{$ligne["TaskType"]}</a>","$span{$ligne["TimeDescription"]}</a>",$run,$events,
		
		"<div style='margin-top:5px'>$enable</div>",$delete )
		);
	}
	
	
echo json_encode($data);		

}

