<?php
if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_POST["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.tasks.inc');
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
if(isset($_GET["build-config"])){build_config_start();exit;}
if(isset($_GET["build-config-start"])){build_config_perform();exit;}
if(isset($_GET["schedules-params"])){schedules_params_js();exit;}
if(isset($_GET["schedules-params-popup"])){schedules_params_popup();exit;}
if(isset($_POST["max_load_avg5"])){schedules_params_save();exit;}

if(isset($_POST["DisableSquidDefaultSchedule"])){DisableSquidDefaultSchedule();exit;}
page();

function AddNewSchedule_js(){
	$ID=$_GET["ID"];
	$tpl=new templates();
	$page=CurrentPageName();
	
	$title="{new_schedule}";
	
	if($ID>0){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM system_schedules WHERE ID=$ID"));
		$title="{schedule}::$ID::{$ligne["TaskType"]}";
	}
	
	$title=$tpl->_ENGINE_parse_body($title);
	echo "YahooWin2('650','$page?AddNewSchedule-popup=yes&ID=$ID','$title')";
	
}

function schedules_params_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{schedule}::{parameters}");
	echo "YahooWin5('550','$page?schedules-params-popup=yes','$title')";
}

function schedules_params_popup(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$settings=unserialize(base64_decode($sock->GET_INFO("FcronSchedulesParams")));
	if(!is_numeric($settings["max_load_avg5"])){$settings["max_load_avg5"]="2.5";}
	if(!is_numeric($settings["max_load_wait"])){$settings["max_load_wait"]="10";}
	if(!is_numeric($settings["max_nice"])){$settings["max_nice"]="19";}
	if(!is_numeric($settings["max_events"])){$settings["max_events"]="50000";}
	
	
	
	$html="
	<div id='div-$t'></div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{max_load_to_run} 5Mn:</td>
		<td>". Field_text("max_load_avg5",$settings["max_load_avg5"],"font-size:16px;width:90px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{max_waiting_minutes_onload}:</td>
		<td style='font-size:16px'>". Field_text("max_load_wait",$settings["max_load_wait"],"font-size:16px;width:90px")."&nbsp;{minutes}</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{max_nice}:</td>
		<td style='font-size:16px'>". Field_text("max_nice",$settings["max_nice"],"font-size:16px;width:90px")."&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{max_events_in_database}:</td>
		<td style='font-size:16px'>". Field_text("max_events",$settings["max_events"],"font-size:16px;width:90px")."&nbsp;</td>
	</tr>			
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}", "SaveCronSets()",16)."</td>
	</tr>	
	</table>
	
	<script>
	var x_SaveCronSets=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}		
		YahooWin5Hide();
		
	}	


	function SaveCronSets(){
		var XHR = new XHRConnection();
	  	XHR.appendData('max_load_avg5',document.getElementById('max_load_avg5').value);
	  	XHR.appendData('max_load_wait',document.getElementById('max_load_wait').value);
	  	XHR.appendData('max_nice',document.getElementById('max_nice').value);
	  	XHR.appendData('max_events',document.getElementById('max_events').value);
	  	AnimateDiv('div-$t');
	  	XHR.sendAndLoad('$page', 'POST',x_SaveCronSets);
	}	
	</script>		
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}


function schedules_params_save(){
	$sock=new sockets();
	$sock->SaveConfigFile(base64_encode(serialize($_POST)), "FcronSchedulesParams");
	$sock->getFrameWork("services.php?build-schedules=yes");	
	$sock->getFrameWork("squid.php?build-schedules=yes");	
	
}

function AddNewSchedule_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$tasks=new system_tasks();
	$q=new mysql();
	$no_schedule_set=$tpl->javascript_parse_text("{no_schedule_set}");
	$buttontext="{add}";
	$ID=$_GET["ID"];
		if($ID>0){
			$buttontext="{apply}";
			$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM system_schedules WHERE ID=$ID","artica_backup"));
			$ligne["TimeDescription"]=utf8_encode($ligne["TimeDescription"]);
		}
		
		if(!is_numeric($ligne["TaskType"])){$ligne["TaskType"]=0;}
		if(!is_numeric($ID)){$ID=0;}
		
	$task_type=$tasks->tasks_array;
	if(!$users->KAV4PROXY_INSTALLED){
		unset($task_type[5]);
		unset($task_type[12]);
	}
	
	if(!$users->UPDATE_UTILITYV2_INSTALLED){
		unset($task_type[13]);
	}

	$task_type=$tasks->tasks_array;
	while (list ($TaskType, $content) = each ($task_type) ){
		$taskz[$TaskType]="[{$TaskType}] ".$tpl->_ENGINE_parse_body($content);
		
	}
	
	$t=time();
	
	$html="
	<div id='div-$t'>
	<table style='width:99%' class='form'>
	<tr>
		<td class=legend style='font-size:14px' nowrap>{task_type}:</td>
		<td>". Field_array_Hash($taskz, "TaskType-$t",$ligne["TaskType"],"ExplainTaskType()",null,0,"font-size:14px")."</td>
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
		<td colspan=2 align='right'><hr>". button($buttontext,"SaveTaskSystem$t()",16)."</td>
	</tr>
	</table>
	</div>
	<script>
		function ExplainTaskType(){
			LoadAjax('$t-explain','$page?explainthis='+document.getElementById('TaskType-$t').value);
		
		}
		
	var x_SaveTaskSystem$t=function (obj) {
		var ID='{$_GET["ID"]}';
		var results=obj.responseText;
		if(results.length>0){alert(results);}		
		YahooWin2Hide();
		SystemCrontaskUpdateTable();
	}	


	function SaveTaskSystem$t(){
		
		var tt=document.getElementById('TimeText-$t').value;
		if(tt.length<4){
			alert('$no_schedule_set `'+tt+'`');
			return;
		}
	  	var XHR = new XHRConnection();
	  	XHR.appendData('TimeDescription',document.getElementById('TimeDescription').value);
	  	XHR.appendData('TimeText',document.getElementById('TimeText-$t').value);
		XHR.appendData('ID','{$_GET["ID"]}');
		XHR.appendData('VERBOSE','{$_GET["ID"]}');
	  	XHR.appendData('TaskType',document.getElementById('TaskType-$t').value);
	  	AnimateDiv('div-$t');
	  	XHR.sendAndLoad('$page', 'POST',x_SaveTaskSystem$t);
	}		

		
	ExplainTaskType();	
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function DisableSquidDefaultSchedule(){
	$sock=new sockets();
	$sock->SET_INFO("DisableSquidDefaultSchedule", $_POST["DisableSquidDefaultSchedule"]);
	$sock->getFrameWork("services.php?build-schedules=yes");
}

function build_config_start(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	
	$html="
	<div style='width:95%;height:550;overflow:auto' class=form id='$t'></div>
	<script>
		LoadAjax('$t','$page?build-config-start=yes&t=$t');
	</script>
	";
	echo $html;
	
}

function build_config_perform(){
	$page=CurrentPageName();
	$sourcet=$_GET["t"];
	$tpl=new templates();
	if(!isset($_GET["no-check"])){
		$sock=new sockets();
		$datas=unserialize(base64_decode($sock->getFrameWork("services.php?build-system-tasks=yes")));
	$t=time();
	$pleasewait=$tpl->_ENGINE_parse_body("{please_wait}");
	echo "
	<center><H2>$pleasewait</H2></center>
	<script>
		function Restart$t(){
			LoadAjax('$sourcet','$page?build-config-start=yes&t=$sourcet&no-check=yes');
		}
		setTimeout('Restart$t()',5000);
	</script>
	
	";
	return;		
		
	}

	if(!is_file("ressources/logs/web/tasks.compile.txt")){
	$t=time();
	$pleasewait=$tpl->_ENGINE_parse_body("{please_wait}");
	echo "
	<center><H2>$pleasewait</H2></center>
	<script>
		function Restart$t(){
			LoadAjax('$sourcet','$page?build-config-start=yes&t=$sourcet&no-check=yes');
		}
		setTimeout('Restart$t()',5000);
	</script>
	
	";
	return;			
		
	}
	echo "<div style='width:100%;text-align:right'>". imgtootltip("refresh-32","{refresh}","LoadAjax('$sourcet','$page?build-config-start=yes&t=$sourcet&no-check=yes');")."</div>";
	$datas=file("ressources/logs/web/tasks.compile.txt");
	while (list ($index, $line) = each ($datas) ){
		echo "<div style='width:100%'><code style='font-size:11.5px'>$line</code></div>";
		
		
	}
	
		
	
}




function AddNewSchedule_save(){
	$users=new usersMenus();
	$q=new mysql();
	$tpl=new templates();
	$task=new system_tasks();
	$task_type=$task->tasks_array;

	
	
	$info=$tpl->javascript_parse_text($task_type[$_POST["TaskType"]]);
	$defaultdesc=replace_accents($info);
	if($_POST["TimeDescription"]==null){$_POST["TimeDescription"]=$defaultdesc ." : {$_POST["TimeText"]}";}
	
	$_POST["TimeDescription"]=mysql_escape_string($_POST["TimeDescription"]);
	
	$sql="INSERT IGNORE INTO system_schedules (TimeDescription,TimeText,TaskType,enabled) 
	VALUES('{$_POST["TimeDescription"]}','{$_POST["TimeText"]}','{$_POST["TaskType"]}',1)";
	
	if($_POST["ID"]>0){
		$sql="UPDATE system_schedules SET 
			TimeDescription='{$_POST["TimeDescription"]}',
			TimeText='{$_POST["TimeText"]}',
			TaskType='{$_POST["TaskType"]}' WHERE ID={$_POST["ID"]}
			";
		
	}
	
	
	if(!$q->TABLE_EXISTS("system_schedules","artica_backup")){$q->BuildTables();}
	$q->QUERY_SQL($sql,"artica_backup"); 
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("services.php?build-schedules=yes");	
	
}


function AddNewSchedule_delete(){
	$sql="DELETE FROM system_schedules WHERE ID={$_POST["ID"]}";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
	$sock=new sockets();
	$sock->getFrameWork("services.php?build-schedules=yes");	
	
}

function AddNewSchedule_run(){
	$sock=new sockets();
	$sock->getFrameWork("services.php?run-scheduled-task={$_POST["ID"]}");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{apply_upgrade_help}");
	
	
	
}


function AddNewSchedule_explain(){
	if($_GET["explainthis"]==0){return;}
	$q=new mysql();
	$tasks=new system_tasks();
	if(!isset($tasks->tasks_explain_array[$_GET["explainthis"]])){return;}
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("<div class=explain style='font-size:14px'>{$tasks->tasks_explain_array[$_GET["explainthis"]]}</div>");
}

function AddNewSchedule_enable(){
	
	$sql="UPDATE system_schedules SET enabled={$_POST["value"]} WHERE ID={$_POST["ID"]}";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("services.php?build-schedules=yes");	
	
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
	$run=$tpl->_ENGINE_parse_body("{run}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$run_this_task_now=$tpl->javascript_parse_text("{run_this_task_now} ?");
	$all_events=$tpl->_ENGINE_parse_body("{events}");
	$parameters=$tpl->_ENGINE_parse_body("{parameters}");
	$internal_scheduler=$tpl->_ENGINE_parse_body("{internal_scheduler}");
	$build_config=$tpl->_ENGINE_parse_body("{WIZARD_COMPILE}");
	
	
	$q=new mysql();
	$tasks=$tpl->_ENGINE_parse_body("{tasks}");
	$CountTasks=$q->COUNT_ROWS("system_schedules", "artica_backup");
	$CountEvents=$q->COUNT_ROWS("system_admin_events", "artica_events");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$t=time();
	$html="
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
	{name: '$all_events', bclass: 'Search', onpress : AllEvents$t},
	{name: '$internal_scheduler', bclass: 'Script', onpress : internal_scheduler$t},
	{name: '$build_config', bclass: 'Restore', onpress : build_config$t},
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
	width: 892,
	height: 450,
	singleSelect: true
	
	});   
});	
	function AllEvents$t(){
		Loadjs('squid.update.events.php?table=system_admin_events')
	
	}
	
	function Parmaeters$t(){
		Loadjs('$page?schedules-params=yes');
	}
	
	function internal_scheduler$t(){
		Loadjs('artica.internal.cron.php');
	}


	function AddNewSchedule(category){
			Loadjs('$page?AddNewSchedule-js=yes&ID=0');
	}
	
	function SystemCrontaskUpdateTable(){
		$('#$t').flexReload();
	 }
	
	var x_SystemTaskEnable=function (obj) {
		var ID='{$_GET["ID"]}';
		var results=obj.responseText;
		if(results.length>0){alert(results);}		
	}

	var x_DisableSquidDefaultScheduleCheck=function (obj) {
		var ID='{$_GET["ID"]}';
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		$('#$t').flexReload();		
	}

	function build_config$t(){
		YahooWinBrowse('550','$page?build-config=yes','$build_config');
	}


	function SystemTaskEnable(md,id){
	  	var XHR = new XHRConnection();
	  	if(document.getElementById(md).checked){XHR.appendData('value',1);}else{XHR.appendData('value',0);}
		XHR.appendData('ID',id);
	  	XHR.appendData('schedule-enable','yes');
	  	XHR.sendAndLoad('$page', 'POST',x_SystemTaskEnable);
	}

	function DisableSquidDefaultScheduleCheck(){
	  	var XHR = new XHRConnection();
	  	if(document.getElementById('DisableSquidDefaultSchedule').checked){XHR.appendData('DisableSquidDefaultSchedule',1);}
	  	else{XHR.appendData('DisableSquidDefaultSchedule',0);}
	  	XHR.sendAndLoad('$page', 'POST',x_DisableSquidDefaultScheduleCheck);	
	}
	
	
	function SystemTaskRun(ID,explain){
		if(confirm('$run_this_task_now `'+explain+'`')){
			var XHR = new XHRConnection();
			XHR.appendData('ID',ID);
	  		XHR.appendData('schedule-run','yes');
	  		XHR.sendAndLoad('$page', 'POST',x_SystemTaskEnable);		
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
	$q=new mysql();
	$search='%';
	$table="system_schedules";
	$page=1;
	$ORDER="ORDER BY ID DESC";
	$sock=new sockets();
	$DisableSquidDefaultSchedule=$sock->GET_INFO("DisableSquidDefaultSchedule");
	if(!is_numeric($DisableSquidDefaultSchedule)){$DisableSquidDefaultSchedule=0;}	
	$schedules=new system_tasks();
	$total=0;
	if($q->COUNT_ROWS($table,"artica_backup")==0){
		json_error_show("No data",1);
	}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND ((`{$_POST["qtype"]}` LIKE '$search') OR (`TimeDescription` LIKE '$search'))";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_backup");
	
	
	
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
		$jstaskexplain=$tpl->javascript_parse_text($schedules->tasks_array[$ligne["TaskType"]]);
		$ligne["TaskType"]=$tpl->_ENGINE_parse_body($schedules->tasks_array[$ligne["TaskType"]]);
		
		
		$enable=Field_checkbox($md5, 1,$ligne["enabled"],"SystemTaskEnable('$md5',{$ligne['ID']})");
		$delete=imgtootltip("delete-24.png","{delete} {$ligne['ID']}","SquidTaskDelete('{$ligne['ID']}')");
		$run=$tpl->_ENGINE_parse_body(imgtootltip("24-run.png","{run} {$ligne['ID']}","SystemTaskRun('{$ligne['ID']}','$jstaskexplain')"));;
		
		
		
		if($ligne["enabled"]==0){$color="#A0A0A0";}
		
		
		$ligne2=mysql_fetch_array($q2->QUERY_SQL("SELECT COUNT(TASKID) as tcount FROM 
		system_admin_events WHERE TASKID='{$ligne['ID']}'","artica_events"));
		
		
		if($ligne2["tcount"]>0){
			$events=imgtootltip("events-24.png","{events} {$ligne['ID']}","Loadjs('squid.update.events.php?taskid={$ligne['ID']}&table=system_admin_events')");
		}
		
		$explainTXT=$tpl->_ENGINE_parse_body($schedules->tasks_explain_array[$TaskType]);
		
		
		
		
		$span="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?AddNewSchedule-js=yes&ID={$ligne['ID']}');\"
		 style='font-size:16px;font-weight:bold;color:$color;text-decoration:underline'>";
		
		
		$ligne["TimeDescription"]=utf8_encode($ligne["TimeDescription"]);
		$ligne["TaskType"]=utf8_encode($ligne["TaskType"]);
		//rowSquidTask
	$data['rows'][] = array(
		'id' => "SquidTask".$ligne['ID'],
		'cell' => array("$span{$ligne['ID']}</a>",
		"$span{$ligne["TaskType"]}</a>","$span{$ligne["TimeDescription"]}</a>
		<div style='font-size:11px'><i>$explainTXT</i></div>",$run,$events,
		
		"<div style='margin-top:5px'>$enable</div>",$delete )
		);
	}
	
	
echo json_encode($data);		

}

