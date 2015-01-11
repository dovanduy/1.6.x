<?php
session_start();

ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.tasks.inc");


if(isset($_GET["search-tasks"])){tasks_search();exit;}
if(isset($_GET["schedule-js"])){tasks_js();exit;}
if(isset($_GET["schedule-tabs"])){tasks_tabs();exit;}
if(isset($_GET["schedule-popup"])){tasks_popup();exit;}
if(isset($_POST["TaskType"])){tasks_save();exit;}
if(isset($_POST["task-delete"])){tasks_delete();exit;}
if(isset($_POST["task-run"])){tasks_run();exit;}
if(isset($_GET["schedule-events-section"])){events_section();exit;}
if(isset($_GET["events-search"])){events_search();exit;}
tasks_section();


function tasks_section(){
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$explain=null;
	$tpl=new templates();
	$suffix=suffix();
	if(!is_numeric($_GET["task-section"])){$_GET["task-section"]=0;}
	$new_schedule=$tpl->_ENGINE_parse_body("{new_schedule}");
	$OPTIONS["BUTTONS"][]=button($new_schedule,"Loadjs('$page?schedule-js=yes&ID=0$suffix')",16);
	if($_GET["task-section"]>0){
		$tasks=new system_tasks();
		$explain="<div class=text-info>".$tpl->_ENGINE_parse_body($tasks->tasks_explain_array[$_GET["task-section"]])."</div>";
	}
	echo $explain.$boot->SearchFormGen("TimeDescription,TimeText,TaskType","search-tasks",$suffix,$OPTIONS);
}

function events_section(){
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$explain=null;
	$tpl=new templates();
	$suffix=suffix();	
	echo $boot->SearchFormGen("description,filename,function","events-search","&ID={$_GET["ID"]}");
	
}

function suffix(){
	if(isset($_GET["t"])){$t=$_GET["t"];}else{$t=time();}
	if(!isset($_GET["YahooWin"])){$_GET["YahooWin"]=2;}
	return "&task-section={$_GET["task-section"]}&YahooWin={$_GET["YahooWin"]}&t=$t";
	
}
function tasks_js(){
	header("content-type: application/x-javascript");
	$suffix=suffix();
	$ID=$_GET["ID"];
	$tpl=new templates();
	$page=CurrentPageName();
	$YahooWin=2;
	$title="{new_schedule}";
	if($_GET["YahooWin"]>0){$YahooWin=$_GET["YahooWin"];};
	if($ID>0){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM system_schedules WHERE ID=$ID","artica_backup"));
		$title="{schedule}::$ID::{$ligne["TaskType"]}";
	}
	
	$title=$tpl->_ENGINE_parse_body($title);
	echo "YahooWin{$YahooWin}('700','$page?schedule-tabs=yes&ID=$ID$suffix','$title')";	
	
}

function tasks_tabs(){
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	if(!is_numeric($ID)){$ID=0;}
	$suffix=suffix();
	$array["{parameters}"]="$page?schedule-popup=yes&ID=$ID$suffix";
	if($ID>0){
		$array["{events}"]="$page?schedule-events-section=yes&ID=$ID$suffix";
	}
		

	$mini=new boostrap_form();
	echo $mini->build_tab($array);
}

function tasks_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$tasks=new system_tasks();
	$PatternToHuman=null;
	$boot=new boostrap_form();
	$q=new mysql();
	$no_schedule_set=$tpl->javascript_parse_text("{no_schedule_set}");
	$buttontext="{add}";
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	$title="{new_schedule}";
	$task_type=$tasks->tasks_array;
	
	if($ID>0){
		$buttontext="{apply}";
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM system_schedules WHERE ID=$ID","artica_backup"));
		$ligne["TimeDescription"]=utf8_encode($ligne["TimeDescription"]);
		$title=$task_type[$ligne["TaskType"]];
		$PatternToHuman="<br>".$tasks->PatternToHuman($ligne["TimeText"],true);
	}
	
	if(!is_numeric($ligne["TaskType"])){$ligne["TaskType"]=0;}
	if(!is_numeric($ID)){$ID=0;}
	
	
	if(!$users->KAV4PROXY_INSTALLED){unset($task_type[5]);unset($task_type[12]);}
	
	if(!$users->UPDATE_UTILITYV2_INSTALLED){unset($task_type[13]);}
	$task_type=$tasks->tasks_array;
	while (list ($TaskType, $content) = each ($task_type) ){$taskz[$TaskType]="[{$TaskType}] ".$tpl->_ENGINE_parse_body($content);}
	$YahooWinHide="YahooWin{$_GET["YahooWin"]}";
	
	
	$boot->set_formtitle($title);
	$boot->set_hidden("ID", $ID);
	if($ID==0){
		if($_GET["task-section"]>0){
			$boot->set_hidden("TaskType", $_GET["task-section"]);
			$boot->set_formdescription($tasks->tasks_explain_array[$_GET["task-section"]].$PatternToHuman);
			$ligne["TimeDescription"]=$tpl->javascript_parse_text($tasks->tasks_array[$_GET["task-section"]]);
		}else{
			$boot->set_list("TaskType", "{type}", $taskz,null);
		}
		
		$ligne["enabled"]=1;
	}else{
		$boot->set_hidden("TaskType", $ligne["TaskType"]);
		$boot->set_formdescription($tasks->tasks_explain_array[$ligne["TaskType"]]);
	}
	
	
	
	$boot->set_checkbox("enabled", "{enabled}", $ligne["enabled"]);
	
	$boot->set_textarea("TimeDescription", "{description}", $ligne["TimeDescription"],array("ENCODE"=>true));
	$boot->set_field("TimeText", "{schedule}", $ligne["TimeText"],array("SCHEDULE"=>true,"MANDATORY"=>true,"DISABLED"=>true));
	
	if($ID==0){$boot->set_CloseYahoo($YahooWinHide);}
	//
	$boot->set_button($buttontext);
	$boot->set_RefreshSearchs();
	echo $boot->Compile();
	
	
}


function tasks_save(){
	
	$users=new usersMenus();
	$q=new mysql();
	$tpl=new templates();
	$task=new system_tasks();
	$task_type=$task->tasks_array;
	
	
	$_POST["TimeDescription"]=url_decode_special_tool($_POST["TimeDescription"]);
	$info=$tpl->javascript_parse_text($task_type[$_POST["TaskType"]]);
	$defaultdesc=replace_accents($info);
	if($_POST["TimeDescription"]==null){$_POST["TimeDescription"]=$defaultdesc ." : {$_POST["TimeText"]}";}
	
	$_POST["TimeDescription"]=mysql_escape_string2($_POST["TimeDescription"]);
	
	$sql="INSERT IGNORE INTO system_schedules (TimeDescription,TimeText,TaskType,enabled)
	VALUES('{$_POST["TimeDescription"]}','{$_POST["TimeText"]}','{$_POST["TaskType"]}','{$_POST["enabled"]}')";
	
	if($_POST["ID"]>0){
	$sql="UPDATE system_schedules SET
	TimeDescription='{$_POST["TimeDescription"]}',
			TimeText='{$_POST["TimeText"]}',
			TaskType='{$_POST["TaskType"]}',
			`enabled`='{$_POST["enabled"]}' 
			WHERE ID={$_POST["ID"]}
				";
	
	}
	
	
	if(!$q->TABLE_EXISTS("system_schedules","artica_backup")){$q->BuildTables();}
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("services.php?build-schedules=yes");	
	
}

function tasks_delete(){
	$sql="DELETE FROM system_schedules WHERE ID={$_POST["task-delete"]}";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$tablename="Taskev{$_POST["task-delete"]}";
	if($q->TABLE_EXISTS($tablename, "artica_events")){
		$q->QUERY_SQL("DROP TABLE $tablename", "artica_events");
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	$sock=new sockets();
	$sock->getFrameWork("services.php?build-schedules=yes");	
	
}

function events_search(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$q=new mysql();
	$t=time();
	if(isset($_GET["t"])){$t=$_GET["t"];}
	$page=CurrentPageName();
	$table="Taskev{$_GET['ID']}";
	$searchstring=string_to_flexquery("events-search");
	$ORDER=$boot->TableOrder(array("zDate"=>"DESC"));
	if($q->COUNT_ROWS($table,"artica_events")==0){senderrors("no data");}	
	
	
	$sql="SELECT * FROM $table WHERE 1 $searchstring ORDER BY $ORDER LIMIT 0,250";
	$results = $q->QUERY_SQL($sql,"artica_events");
	
	if(!$q->ok){senderrors($q->mysql_error."<br>$sql");}
	$line=$tpl->_ENGINE_parse_body("{line}");
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$description=$tpl->_ENGINE_parse_body($ligne["description"]);
		$class=LineToClass($description);
		$description=str_replace("\n", "<br>", $description);
		$description=wordwrap($description,150,"<br>");
		$description=str_replace("<br><br>","<br>",$description);
		$ttim=strtotime($ligne['zDate']);
		$dateD=date('Y-m-d',$ttim);
		
		$function="
		<div style='margin-top:-4px;margin-left:-5px'>
			<i style='font-size:11px'>{$ligne["filename"]}:{$ligne["function"]}() $line {$ligne["line"]}</i>
		</div>";
		$tr[]="
		<tr id='$md' class='$class'>
		<td style='font-size:12px' width=1% nowrap>{$ligne["zDate"]}</td>
		<td style='font-size:12px' width=70% >$description$function</td>
		</tr>
		";	
	}
	
	echo $boot->TableCompile(array("zDate"=>"{date}","description"=>"{description}",
			
	),$tr);
	
}

function tasks_search(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$q=new mysql();
	$t=time();
	if(isset($_GET["t"])){$t=$_GET["t"];}
	$page=CurrentPageName();
	$table="system_schedules";
	$searchstring=string_to_flexquery("search-tasks");
	$ORDER=$boot->TableOrder(array("ID"=>"DESC"));
	if($q->COUNT_ROWS($table,"artica_backup")==0){senderrors("{no_task}");}
	
	if($_GET["task-section"]>0){$table="( SELECT * FROM $table WHERE `TaskType`={$_GET["task-section"]} ) as T";}
	
	$sql="SELECT * FROM $table WHERE 1 $searchstring ORDER BY $ORDER LIMIT 0,250";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	
	if(!$q->ok){senderrors($q->mysql_error."<br>$sql");}
	$schedules=new system_tasks();
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$events="&nbsp;";
		$md=md5(serialize($ligne));
		$TaskType=$ligne["TaskType"];
		$jstaskexplain=$tpl->javascript_parse_text($schedules->tasks_array[$ligne["TaskType"]]);
		$ligne["TaskType"]=$tpl->_ENGINE_parse_body($schedules->tasks_array[$ligne["TaskType"]]);
		$TimeDescription=$ligne["TimeDescription"];
		$delete=imgtootltip("delete-32.png","{delete} {$ligne['ID']}","Delete$t('{$ligne['ID']}','$md')");
		$run=$tpl->_ENGINE_parse_body(imgtootltip("24-run.png","{run} {$ligne['ID']}","SystemTaskRun('{$ligne['ID']}','$jstaskexplain')"));;
		
		
		
		if($ligne["enabled"]==0){$color="#A0A0A0";}
		$tablename="Taskev{$ligne['ID']}";
		
		if(!$q->TABLE_EXISTS($tablename, "artica_events")){
			$events=null;
		}else{
			$evs=$q->COUNT_ROWS($tablename,  "artica_events");
			if($evs>0){
				$events=imgsimple("events-32.png");
			}
		
		}
		
		$explainTXT=$tpl->_ENGINE_parse_body($schedules->tasks_explain_array[$TaskType]);
		
		$TimeText=$tpl->_ENGINE_parse_body($schedules->PatternToHuman($ligne["TimeText"]));
		$TimeText=str_replace("<br>", "", $TimeText);
		if(preg_match("#(.+?)\s+(.+?)\s+(.+?)\s+(.+?)\s+(.+?)#", $TimeDescription,$re)){$TimeDescription=$TimeText;$TimeText=null;}
		
		
		$js="Loadjs('$page?schedule-js=yes&ID={$ligne['ID']}')";

		$link=$boot->trswitch($js);
		
		
		$ligne["TimeDescription"]=utf8_encode($ligne["TimeDescription"]);
		$ligne["TaskType"]=utf8_encode($ligne["TaskType"]);
		
		
		
		$tr[]="
		<tr id='$md'>
		<td style='font-size:16px' width=1% nowrap $link>{$ligne['ID']}</td>
		<td style='font-size:16px' width=25% nowrap $link>{$ligne["TaskType"]}</td>
		<td style='font-size:16px' width=70% $link>$TimeDescription<br>$explainTXT</td>
		<td style='font-size:16px' width=1% nowrap >$run</td>
		<td style='font-size:16px' width=1% nowrap $link>$events</td>
		<td style='font-size:12px' width=1%>$delete</td>
		</tr>
		";
	}
	$run_this_task_now=$tpl->javascript_parse_text("{run_this_task_now}");
	$delete_text=$tpl->javascript_parse_text("{delete_this_task}");
	echo $boot->TableCompile(array("ID"=>"ID","TaskType"=>"{type}",
			"TimeText"=>"{explain}",
			"run:no"=>"{run}",
			"event:no"=>"{events}",
			"delete:no"=>null,
	),$tr)."
<script>
var mem$t='';
var xDelete$t=function(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	$('#'+mem$t).remove();
}
function Delete$t(ID,mem){
	mem$t=mem;
	if(confirm('$delete_text ID: '+ID+'?')){
		mem$t=mem;
		var XHR = new XHRConnection();
		XHR.appendData('task-delete',ID);
		XHR.sendAndLoad('$page', 'POST',xDelete$t);
	}
}
var xSystemTaskEnable$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	ExecuteByClassName('SearchFunction');		
}

function SystemTaskRun$t(ID){
	if(confirm('$run_this_task_now :`'+ID+'`')){
		var XHR = new XHRConnection();
		XHR.appendData('task-run',ID);
		XHR.sendAndLoad('$page', 'POST',xSystemTaskEnable$t);		
	}
}
			
</script>
			
";
	
}
function tasks_run(){
	$sock=new sockets();
	$sock->getFrameWork("services.php?run-scheduled-task={$_POST["task-run"]}");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{apply_upgrade_help}");	
	
}