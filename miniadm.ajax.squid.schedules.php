<?php
session_start();
$_SESSION["MINIADM"]=true;
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/ressources/class.tasks.inc");

if(isset($_GET["verbose"])){$GLOBALS["DEBUG_PRIVS"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!isset($_SESSION["uid"])){writelogs("Redirecto to miniadm.logon.php...","NULL",__FILE__,__LINE__);header("location:miniadm.logon.php");}
BuildSessionAuth();
if($_SESSION["uid"]=="-100"){writelogs("Redirecto to location:admin.index.php...","NULL",__FILE__,__LINE__);header("location:admin.index.php");die();}
$users=new usersMenus();
if(!$users->AsAnAdministratorGeneric){die("<h1>no right</h1>");}
if(isset($_GET["SearchTask"])){SearchTask();exit;}
if(isset($_GET["schedule-run-js"])){task_run_js();exit;}
if(isset($_POST["schedule-run"])){Schedule_run();exit;}
page();
function page(){
	$boot=new boostrap_form();
	
	$q=new mysql_squid_builder();
	$explain=$q->tasks_explain_array[$_GET["TaskID"]];
	$tpl=new templates();
	$explain=$tpl->_ENGINE_parse_body($explain);
	$form=$boot->SearchFormGen("TimeDescription","SearchTask","&TaskID={$_GET["TaskID"]}","<div class=explain>$explain</div>");
	echo $form;
	
	
}


function SearchTask(){
	$boot=new boostrap_form();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$CheckRunningTasks=base64_decode(unserialize($sock->getFrameWork("squid.php?CheckRunningTasks=yes")));
	$table="webfilters_schedules";
	$searchstring=string_to_flexquery("SearchTask");
	$sql="SELECT * FROM $table WHERE TaskType={$_GET["TaskID"]} $searchstring";
	$results=$q->QUERY_SQL($sql);
	$q2=new mysql();
	$MyPage=CurrentPageName();
	$schedules=new system_tasks();
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$sincerun=null;
		$tools=array();
		$delete=imgtootltip("delete-24.png","{delete} {$ligne['ID']}","SquidTaskDelete('{$ligne['ID']}')");
		$disabled=null;
		if(isset($CheckRunningTasks[$ligne['ID']])){$run_icon="preloader.gif";}
		//print_r($ligne);
		
		$TimeText=$tpl->_ENGINE_parse_body($schedules->PatternToHuman($ligne["TimeText"]));
		$TimeText=str_replace("<br>", "", $TimeText);
		
	    if(intval($ligne["enabled"])==0){
	    	$disabled=$tpl->_ENGINE_parse_body("<span class='label label-info'>{disabled}</span> ");
	    }
	    
	    
	    if(isset($CheckRunningTasks[$ligne['ID']])){$sincerun="<br><i>{$CheckRunningTasks[$ligne['ID']]}</i>";}
		
		$tablename="TaskSq{$ligne['ID']}";
		
		if($q2->TABLE_EXISTS($tablename, "artica_events")){
			$evs=$q2->COUNT_ROWS($tablename,  "artica_events");
			if($evs>0){
				$tools[]="<i class='icon-info-sign'></i> $evs {events}";
			}
		
		}
		
	
		
		$js=$boot->trswitch("Loadjs('miniadm.ajax.proxy.schedule.php?ID={$ligne['ID']}');");
		
		$ligne["TimeDescription"]=utf8_encode($ligne["TimeDescription"]);
		$delete="<td $js width=1% style='text-align:center'>$delete</td>";
		$delete=null;
		$tr[]="
		<tr class='$class'>
		<td $js width=1%>{$ligne['ID']}</td>
		<td $js >$disabled<strong style='font-size:16px;font-weight:bold'>{$ligne["TimeDescription"]}</strong>$sincerun<div>$TimeText</div><div style='font-size:11px'>". $tpl->_ENGINE_parse_body(@implode("&nbsp;|&nbsp;", $tools))."</div></td>
		
		</tr>
		";
		
		

		
		
		
	}
	
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th width=1%>{ID}</th>
					<th width=98%>{task}</th>
					
					
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>
		
			</table>
";	
	
}
function task_run_js(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$run_this_task_now=$tpl->javascript_parse_text("{run_this_task_now} ?");
	header("content-type: application/x-javascript");
	$ID=$_GET["ID"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_schedules WHERE ID=$ID"));
	$explain=$tpl->javascript_parse_text("{$q->tasks_explain_array[$ligne["TaskType"]]}");

	$html="
var x_SquidTaskRun$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
}


function SquidTaskRun$t(ID){
	if(confirm('$run_this_task_now: `$explain`')){
		var XHR = new XHRConnection();
		XHR.appendData('ID',ID);
		XHR.appendData('schedule-run','yes');
		XHR.appendData('output','yes');
		XHR.sendAndLoad('$page', 'POST',x_SquidTaskRun$t);
	}
}
SquidTaskRun$t();
";
	echo $html;

}
function Schedule_run(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?run-scheduled-task={$_POST["ID"]}");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{apply_upgrade_help}");
}