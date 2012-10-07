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
if(isset($_GET["Settings"])){Settings();exit;}
if(isset($_GET["search_function_from_filename"])){search_function_from_filename();exit;}
if(isset($_POST["EnableBackup"])){SaveBackupSettings();exit;}

popup();


function popup(){
	
	
	
	$page=CurrentPageName();
	$tpl=new templates();	
	
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){
		$html=FATAL_ERROR_SHOW_128("{this_feature_is_disabled_corp_license}");
		echo $tpl->_ENGINE_parse_body($html);
		return;
	}
		
	
	$tablename=$tpl->_ENGINE_parse_body("{tablename}");
	$filepath=$tpl->javascript_parse_text("{filepath}");
	$filesize=$tpl->_ENGINE_parse_body("{filesize}");
	$parameters=$tpl->_ENGINE_parse_body("{parameters}");
	$t=time();
	$tablesize=830;
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	$bts=array();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(*) as tcount, SUM(filesize) as tsize FROM webstats_backup","artica_events"));
	$title=$tpl->_ENGINE_parse_body("{backup}:: {$ligne["tcount"]} {containers} (").FormatBytes($ligne["tsize"]/1024).")";

	
	
	
	$bts[]="{name: '$parameters', bclass: 'Settings', onpress : Settings$t},";
	$bts[]="{name: '$parameters', bclass: 'Help', onpress : help$t},";
	$buttons="buttons : [".@implode("\n", $bts)." ],";
	$html="
	<div style='margin-left:5px'>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	</div>
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?search=yes',
	dataType: 'json',
	colModel : [
		{display: '$tablename', name : 'tablename', width : 235, sortable : true, align: 'left'},
		{display: '$filesize', name : 'filesize', width : 120, sortable : true, align: 'left'},
		{display: '$filepath', name : 'filepath', width : 418, sortable : true, align: 'left'},
	],$buttons
	searchitems : [
		{display: '$tablename', name : 'tablename'},
		{display: '$filesize', name : 'filesize'},
		{display: '$filepath', name : 'filepath'},
		],
	sortname: 'tablename',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 25,
	showTableToggleBtn: false,
	width: $tablesize,
	height: 500,
	singleSelect: true
	
	});   
});

function Settings$t(){
	YahooWin2('714','$page?Settings=yes','$parameters');

}
function help$t(){
	s_PopUpFull('http://www.proxy-appliance.org/index.php?cID=249','1024','900');
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

function Settings(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$SquidBackupStats=unserialize(base64_decode($sock->GET_INFO("SquidBackupStats")));
	$t=time();
	
	
	$DaysbackupOlder=$SquidBackupStats["DaysbackupOlder"];
	$MonthsbackupOlder=$SquidBackupStats["MonthsbackupOlder"];
	$WeekBackupOlder=$SquidBackupStats["WeekBackupOlder"];
	$EnableBackup=$SquidBackupStats["EnableBackup"];
	if(!is_numeric($DaysbackupOlder)){$DaysbackupOlder="180";}
	if(!is_numeric($MonthsbackupOlder)){$MonthsbackupOlder="6";}
	if(!is_numeric($WeekBackupOlder)){$WeekBackupOlder="24";}
	if($SquidBackupStats["workdir"]==null){$SquidBackupStats["workdir"]="/home/artica/backup-squid-stats";}
	$bbutton=button("{browse}...&nbsp;", "Loadjs('SambaBrowse.php?no-shares=yes&field=workdir-$t');",14);
	$html="
	<div id='div$t'></div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{enable_database_backup}:</td>
		<td style='font-size:16px'>". Field_checkbox("EnableBackup-$t",1,$EnableBackup,"CheckEnableBackup$t()")."&nbsp;</td>
		<td width=1%>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{directory}:</td>
		<td style='font-size:16px'>". Field_text("workdir-$t",$SquidBackupStats["workdir"],"font-size:16px;width:300px")."</td>
		<td width=1%>$bbutton</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{backup_old_than}:</td>
		<td style='font-size:16px'>". Field_text("DaysbackupOlder",$DaysbackupOlder,"font-size:16px;width:90px")."&nbsp;{days}</td>
		<td width=1%>&nbsp;</td>
	</tr>		
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","SaveF$t()",16)."</td>
	</tr>
	</table>
	<script>
	function CheckEnableBackup$t(){
		if(!document.getElementById('EnableBackup-$t').checked){
			document.getElementById('DaysbackupOlder').disabled=true;
			document.getElementById('workdir-$t').disabled=true;
		}else{
			document.getElementById('workdir-$t').disabled=false;
			document.getElementById('DaysbackupOlder').disabled=false;
		
		}
		
	}
	var x_SaveF$t=function (obj) {
		var results=obj.responseText;
		document.getElementById('div$t').innerHTML='';
		if(results.length>0){alert(results);return;}
		YahooWin2Hide();
	}
	
	
	function SaveF$t(){
		var XHR = new XHRConnection();
		AnimateDiv('div$t');
		if(document.getElementById('EnableBackup-$t').checked){ XHR.appendData('EnableBackup',1);}else{XHR.appendData('EnableBackup',0);}
		XHR.appendData('DaysbackupOlder',document.getElementById('DaysbackupOlder').value);
		XHR.appendData('workdir',document.getElementById('workdir-$t').value);
		XHR.sendAndLoad('$page', 'POST',x_SaveF$t);		
		
	}
	CheckEnableBackup$t();
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function SaveBackupSettings(){
	$sock=new sockets();
	$datas=base64_encode(serialize($_POST));
	$sock->SaveConfigFile($datas, "SquidBackupStats");
	
}


function search(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$table="webstats_backup";
	$search='%';
	$page=1;
	$WHERE=1;
	if(!$q->TABLE_EXISTS($table)){$q->BuildTables();}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$search=string_to_sql_search($_POST["query"]);
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE $WHERE $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		if(!$q->ok){json_error_show($q->mysql_error);}
		$total = $ligne["tcount"];
		
	}else{
		$total = $q->COUNT_ROWS($table);
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT * FROM $table WHERE $WHERE $searchstring $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){json_error_show($q->mysql_error);}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("No data...",1);}
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
	$subtext=null;
	$ligne["filesize"]=FormatBytes($ligne["filesize"]/1024);
	
	if(preg_match("#^dansguardian_events_([0-9]+)#", $ligne["tablename"],$re)){
		$timestr=$re[1];
		$year=substr($timestr, 0,4);
		$month=substr($timestr, 4,2);
		$day=substr($timestr, 6,2);
		$dateToStr=strtotime("$year-$month-$day 00:00:00");
		$subtext="{day_events_of} ".date('{l} d {F} Y',$dateToStr);
		$subtext=$tpl->_ENGINE_parse_body($subtext);
		$subtext="<div style='font-size:11px'><i>$subtext</i></div>";
	}
	
	if(preg_match("#^([0-9]+)_([a-z]+)#", $ligne["tablename"],$re)){
		$timestr=$re[1];
		$length=strlen($timestr);
		if($length>7){
			$year=substr($timestr, 0,4);
			$month=substr($timestr, 4,2);
			$day=substr($timestr, 6,2);
			$dateToStr=strtotime("$year-$month-$day 00:00:00");
			$subtext="{$re[2]} ".date('{l} d {F} Y',$dateToStr);
		}
		if($length==6){
			$year=substr($timestr, 0,4);
			$month=substr($timestr, 4,2);
			$day="01";
			$dateToStr=strtotime("$year-$month-$day 00:00:00");
			$subtext="{month}/{$re[2]} ".date('{F} Y',$dateToStr);
		}
		
		$subtext=$tpl->_ENGINE_parse_body($subtext);
		$subtext="<div style='font-size:11px'><i>$subtext</i></div>";		
		
	}
				
	$data['rows'][] = array(
		'id' => $ligne['zDate'],
		'cell' => array(
		"<strong style='font-size:13px'>{$ligne["tablename"]}</strong>",
		"<strong style='font-size:13px'>{$ligne["filesize"]}</strong>",
		"<strong style='font-size:13px'>{$ligne["filepath"]}$subtext</strong>",
		)
		);
	}
	
	
echo json_encode($data);	
	
}



//update