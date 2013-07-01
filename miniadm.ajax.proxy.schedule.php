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

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["settings"])){settings();exit;}
if(isset($_GET["events"])){events();exit;}
if(isset($_GET["schedule"])){schedule_params();exit;}
if(isset($_POST["TimeText"])){TimeText_save();exit;}
if(isset($_GET["SearchLogs"])){SearchLogs();exit;}
if(isset($_POST["TimeDescription"])){TimeDescription_save();exit;}

page();
function page(){
	header("content-type: application/x-javascript");
	$ID=$_GET["ID"];
	$tpl=new templates();
	$page=CurrentPageName();
	$YahooWin=2;
	$title="{new_schedule}";
	if(isset($_GET["YahooWin"])){$YahooWin=$_GET["YahooWin"];$YahooWinet="&YahooWin={$_GET["YahooWin"]}";};
	if($ID>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_schedules WHERE ID=$ID"));
		$title="{schedule}::$ID::{$ligne["TaskType"]}";
	}
	
	$title=$tpl->_ENGINE_parse_body($title);
	echo "YahooWin{$YahooWin}('750','$page?tabs=yes&ID=$ID','$title')";	
}
function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$ID=$_GET["ID"];
	$boot=new boostrap_form();
	$array["{parameters}"]="$page?settings=yes&ID=$ID";
	$array["{schedule}"]="$page?schedule=yes&ID=$ID";
	$array["{events}"]="$page?events=yes&ID=$ID";
	echo $boot->build_tab($array);
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
}

function settings(){
	$schedules=new system_tasks();
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$buttontext="{apply}";
	$ID=$_GET["ID"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_schedules WHERE ID=$ID"));
	$ligne["TimeDescription"]=utf8_encode($ligne["TimeDescription"]);
	$TimeText=$tpl->_ENGINE_parse_body($schedules->PatternToHuman($ligne["TimeText"]));
	$TimeText=str_replace("<br>", "", $TimeText);
	$explain=$tpl->_ENGINE_parse_body($q->tasks_explain_array[$ligne["TaskType"]]);
	$boot=new boostrap_form();
	$boot->set_hidden("ID", $ID);
	$boot->set_checkbox("enabled", "{enabled}", $ligne["enabled"]);
	$boot->set_field("TimeDescription", "{description}", $ligne["TimeDescription"],array("ENCODE"=>TRUE));
	$boot->set_button("{apply}");
	$boot->set_formtitle("{task} {$ligne["ID"]}");
	
	$runtask=$tpl->_ENGINE_parse_body("<div style='text-align:right'><i class='icon-play'></i> <a href=\"javascript:Blurz();\" OnClick=\"javascript:Loadjs('miniadm.ajax.squid.schedules.php?schedule-run-js=yes&ID=$ID');\">{run} {task}</div>");
	
	$boot->set_formdescription("$explain<br>$TimeText$runtask");
	$boot->set_RefreshSearchs();
	echo $boot->Compile();
	
	
}
function schedule_params(){
	$tpl=new templates();
	$t=time();
	$ID=$_GET["ID"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_schedules WHERE ID=$ID"));
	$page=CurrentPageName();
	for($i=0;$i<60;$i++){$def[]=$i;}
	for($i=0;$i<24;$i++){$def1[]=$i;}
	
	if(trim($ligne["TimeText"])<>null){
		$tbl=explode(" ",$ligne["TimeText"]);
		if($tbl[4]=='*'){$tbl[4]="0,1,2,3,4,5,6";}
		$defaults_days=explode(",",$tbl[4]);
		while (list ($num, $line) = each ($defaults_days)){
			$value_default_day[$line]=1;
		}
			
		if($tbl[0]=="*"){$tbl[0]=implode(",",$def);}
		if($tbl[1]=="*"){$tbl[1]=implode(",",$def1);}
	
		$defaults_min=explode(",",$tbl[0]);
		while (list ($num, $line) = each ($defaults_min)){
			$value_default_min[$line]=1;
		}
	
		$defaults_hour=explode(",",$tbl[1]);
		while (list ($num, $line) = each ($defaults_hour)){
			$value_default_hour[$line]=1;
		}
			
	
	}
	
	$array_days=array("sunday","monday","tuesday","wednesday","thursday","friday","saturday");

	$count=0;
	for($i=0;$i<60;$i++){
		if($i<10){$min_text="0$i";}else{$min_text=$i;}
		$checked=null;$iconCK=null;
		if($value_default_min[$i]==1){$checked=" checked";$iconCK="<i class='icon-ok'></i>";}
		$mins[]="
		<label class='checkbox'>
		<input type=\"checkbox\" id='min_{$i}'
		name=\"min_{$i}\" value=\"1\" OnClick=\"javascript:IconCz(this);\" $checked>&nbsp;$min_text <span id='min_{$i}-c'>$iconCK</span>
		</label>";
		
		
		//Field_checkbox("min_{$i}",1,$value_default_min[$i]);
		$scripts[]="if(document.getElementById('min_{$i}').checked){XHR.appendData('min_{$i}',1);}else{XHR.appendData('min_{$i}',0);}";
		$UnselectAllMins[]="document.getElementById('min_{$i}').checked=false;";
		$selectAllMins[]="document.getElementById('min_{$i}').checked=true;";
		$count=$count+1;
	}
	for($i=0;$i<24;$i++){
		if($i<10){$hour_text="0$i";}else{$hour_text=$i;}
		
	
		$scripts[]="if(document.getElementById('hour_{$i}').checked){XHR.appendData('hour_{$i}',1);}else{XHR.appendData('hour_{$i}',0);}";
		$UnselectAllHours[]="document.getElementById('hour_{$i}').checked=false;";
		$selectAllHours[]="document.getElementById('hour_{$i}').checked=true;";		
		$checked=null;$iconCK=null;
		if($value_default_hour[$i]==1){$checked=" checked";$iconCK="<i class='icon-ok'></i>";}		
		$hours[]="
		<label class='checkbox'>
		<input type=\"checkbox\" id='hour_{$i}'
		name=\"hour_{$i}\" value=\"1\" OnClick=\"javascript:IconCz(this);\"  $checked>&nbsp;$hour_text <span id='hour_{$i}-c'>$iconCK</span>
		</label>";	
		
		}
		
		while (list ($num, $line) = each ($array_days)){
			$checked=null;$iconCK=null;
			$line=$tpl->_ENGINE_parse_body("{{$line}}");
			if(intval($value_default_day[$num])==1){$checked=" checked";$iconCK="<i class='icon-ok'></i>";}		
			$dayz[]="
		<label class='checkbox'>
		<input type=\"checkbox\" id='day_{$num}'
		name=\"day_{$num}\" value=\"1\" OnClick=\"javascript:IconCz(this);\"  $checked>&nbsp;{$line} <span id='day_{$num}-c'>$iconCK</span>
		</label>";	
			
		$UnselectAlljs[]="document.getElementById('day_{$num}').checked=false;";
		$scripts[]="if(document.getElementById('day_{$num}').checked){XHR.appendData('day_{$num}',1);}else{XHR.appendData('day_{$num}',0);}";
		
		}		
	
	$minutes=CompileTrGen($mins,$tpl->_ENGINE_parse_body("<p>&nbsp;</p><strong style='font-size:16px;margin-top:5px'>{minutes}</strong><hr style='margin-top:1px'>"),10);
	$hoursC=CompileTrGen($hours,$tpl->_ENGINE_parse_body("<p>&nbsp;</p><strong style='font-size:16px;margin-top:5px'>{hours}</strong><hr style='margin-top:1px'>"),10);
	$DaysC=CompileTrGen($dayz,$tpl->_ENGINE_parse_body("<p>&nbsp;</p><strong style='font-size:16px;margin-top:5px'>{days}</strong><hr style='margin-top:1px'>"),5);
	
	
	echo "<div id='$t'></div>".$DaysC.$hoursC.$minutes."
	
	<div style='text-align:right'><hr>". button("{apply}", "SaveCronInfos$t()",18)."</div>
			
	<script>
		function IconCz(doc){
			document.getElementById(doc.id+'-c').innerHTML='';
			if(doc.checked){
				document.getElementById(doc.id+'-c').innerHTML='<i class=\"icon-ok\"></i>';
			}
			
		}
		
var xSaveCronInfos$t = function (obj) {
	var results=obj.responseText;
	document.getElementById('$t').innerHTML='';
	if(results.length>2){
		alert(results);
	}
	
	ExecuteByClassName('SearchFunction');
}


			
var x_save_cron= function (obj) {
	var results=obj.responseText;
	if(results.length>2){
		var XHR = new XHRConnection();
		XHR.appendData('TimeText',results);
		XHR.appendData('ID','$ID');
		XHR.sendAndLoad('$page', 'GET',xSaveCronInfos$t);
	}
}			
			
	function SaveCronInfos$t(){
		AnimateDiv('$t');
		var XHR = new XHRConnection();
		". @implode("\n", $scripts)."
		XHR.sendAndLoad('cron.php', 'GET',x_save_cron);
	
	}			
			
	</script>";
}
function TimeText_save(){
	$sql="UPDATE webfilters_schedules SET TimeText='{$_POST["TimeText"]}' WHERE ID={$_POST["ID"]}";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){$q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-schedules=yes");	
	
}
function TimeDescription_save(){
	$TimeDescription=url_decode_special_tool($_POST["TimeDescription"]);
	$sql="UPDATE webfilters_schedules SET TimeDescription='{$_POST["TimeDescription"]}' enabled='{$_POST["enabled"]}' WHERE ID={$_POST["ID"]}";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){$q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-schedules=yes");	
	
	
}
function events(){
	$ID=$_GET["ID"];
	$boot=new boostrap_form();
	echo $boot->SearchFormGen("description","SearchLogs","&ID=$ID");	
	
}
function SearchLogs(){
	$tpl=new templates();
	$q=new mysql();
	$tablename="TaskSq{$_GET['ID']}";
	// $prefix="INSERT IGNORE INTO ufdbguard_admin_events (`zDate`,`function`,`filename`,`line`,`description`,`category`,`TASKID`) VALUES ";
	
	$searchstring=string_to_flexquery("SearchLogs");
	$sql="SELECT * FROM $tablename WHERE 1 $searchstring ORDER BY zDate DESC LIMIT 0,250";
	$results=$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){$tr[]="<tr class=error><td colspan=2>$q->mysql_error</td></tr>";}
	while ($ligne = mysql_fetch_assoc($results)) {
		$class=null;
		$description=$ligne["description"];
		$class=LineToClass($description);
		$description=nl2br($description);
		$description=$tpl->_ENGINE_parse_body($description);
		$tr[]="
		<tr class='$class'>
			<td width=1% style='font-size:11px' nowrap>{$ligne['zDate']}</td>
			<td style='font-size:11px'>$description</td>
		</tr>
		";
		
	}
	echo $tpl->_ENGINE_parse_body("<table class='table table-bordered'>
	
			<thead>
				<tr>
					<th width=1%>{date}</th>
					<th width=98%>{events}</th>
					
			
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>
	
			</table>";
}
