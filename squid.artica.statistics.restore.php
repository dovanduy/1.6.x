<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["title"])){tables_title();exit;}
	if(isset($_GET["schedules"])){schedules();exit;}
	if(isset($_POST["ArticaProxyStatisticsRestoreFolder"])){Save();exit;}
	if(isset($_GET["restored"])){restored_table();exit;}
	if(isset($_GET["restored-list"])){restored_items();exit;}
	if(isset($_GET["zoom-restored"])){zoom_restored();exit;}
	if(isset($_POST["RecoverDelete"])){RecoverDelete();exit;}
	if(isset($_GET["form-restore"])){form_restore();exit;}
	if(isset($_POST["RestoreSingle"])){RestoreSingle();exit;}
	if(isset($_POST["RecoverAll"])){RecoverAll();exit;}
	if(isset($_POST["DeleteAll"])){DeleteAll();exit;}
	
	
	
js();

function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{purge_statistics_database}");
	$html="YahooWin4('821','$page?tabs=yes','$title');";
	echo $html;	
	
}

function tabs(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	
	$array["popup"]='{parameters}';
	$array["restored"]='{restored}';

	while (list ($num, $ligne) = each ($array) ){

		$html[]= "<li><a href=\"$page?$num=yes\"><span>$ligne</span></a></li>\n";
	}

	$t=time();
	echo $tpl->_ENGINE_parse_body( "
			<div id=$t style='width:97%;font-size:14px;margin-left:10px;margin-right:-15px;margin-top:-5px'>
			<ul>". implode("\n",$html)."</ul>
			</div>
			<script>
			$(document).ready(function(){
			$('#$t').tabs();
				
				
});
</script>");
}

function tables_title(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$array=$q->COUNT_ALL_TABLES();
	echo $tpl->_ENGINE_parse_body("
	<div style='float:right'>". imgtootltip("refresh-24.png","{refresh}","RefreshTableTitle{$_GET["t"]}()")."</div>		
	<div style='font-size:18px'>{current}: {$array[0]} Tables (".FormatBytes($array[1]/1024).")</div>");
	
}

function popup(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	if($users->CORP_LICENSE){$LICENSE=1;}else{$LICENSE=0;}
	$ArticaProxyStatisticsRestoreFolder=$sock->GET_INFO("ArticaProxyStatisticsRestoreFolder");
	if($ArticaProxyStatisticsRestoreFolder==null){$ArticaProxyStatisticsRestoreFolder="/home/artica/squid/backup-statistics-restore";}
	$q=new mysql_squid_builder();
	$new_schedule=$tpl->javascript_parse_text("{new_schedule}");
	$t=time();
	$html="
	
	<div id='$t'></div>
	<div id='title-$t'></div>
	<div style='font-size:14px;' class=text-info>{restore_statistics_database_explain2}</div>	

	<table style='width:99%' class=form>
		<tr>
			<td class=legend style='font-size:16px'>{restore_folder}:</td>
			<td>". Field_text("ArticaProxyStatisticsRestoreFolder-$t",$ArticaProxyStatisticsRestoreFolder,"font-size:16px;width:350px")."</td>
			<td width=1%>". button("{browse}..","Loadjs('SambaBrowse.php?no-shares=yes&field=ArticaProxyStatisticsRestoreFolder-$t')",12)."</td>
		</tr>
		<tr>
			<td colspan=3 align='right'><hr>". button("{apply}", "Save$t()",18)."</td>
		</tr>
		<tr>
		<td colspan=3 align='left'>
			<table style='width:50%'>
			<td width=1%><img src='img/arrow-blue-left-24.png'></td>
						
						<td width=99% nowrap>
							<a href=\"javascript:blur();\" 
							OnClick=\"javascript:YahooWin3('650','squid.databases.schedules.php?AddNewSchedule-popup=yes&ID=0&t=$t&ForceType=48&YahooWin=3&jsback=ReloadSchedules$t','$new_schedule');\"
					 		style=\"font-size:14px;text-decoration:underline\">$new_schedule</a>
						</td>
					</tr>	
			</table>
		</td>
	</tr>	
	</table>
	
	<div id='schedules-$t'></div>
	
<script>
	var x_Save$t= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		document.getElementById('$t').innerHTML='';
	}

	function Save$t(){
			var LICENSE=$LICENSE;
			var XHR = new XHRConnection();	
			XHR.appendData('ArticaProxyStatisticsRestoreFolder',document.getElementById('ArticaProxyStatisticsRestoreFolder-$t').value);
			AnimateDiv('$t');
			XHR.sendAndLoad('$page', 'POST',x_Save$t);
			}
			
	function ReloadSchedules$t(){
		LoadAjax('schedules-$t','$page?schedules=yes');
		}
		
	function RefreshTableTitle$t(){
		LoadAjaxTiny('title-$t','$page?title=yes&t=$t');
	}
		RefreshTableTitle$t();
		ReloadSchedules$t();
</script>											
	";
echo $tpl->_ENGINE_parse_body($html);	
	
}

function DeleteAll(){
	$q=new mysql();
	$q->QUERY_SQL("DROP TABLE squidlogs_restores","artica_events");
	$q=new mysql();
	$q->BuildTables();
	
}

function restored_table(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$ArticaProxyStatisticsRestoreFolder=$sock->GET_INFO("ArticaProxyStatisticsRestoreFolder");
	if($ArticaProxyStatisticsRestoreFolder==null){$ArticaProxyStatisticsRestoreFolder="/home/artica/squid/backup-statistics-restore";}
	$ArticaProxyStatisticsRestoreFolder=$tpl->javascript_parse_text("$ArticaProxyStatisticsRestoreFolder");
	$date=$tpl->javascript_parse_text("{date}");
	$backup_container=$tpl->javascript_parse_text("{containers}");
	$restored_containers=$tpl->javascript_parse_text("{restored_containers}");
	$restore_all_containers=$tpl->javascript_parse_text("{restore_all_containers}");
	$restore_a_backup=$tpl->javascript_parse_text("{restore_a_backup}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$delete_all=$tpl->javascript_parse_text("{delete_all}");
	$t=time();
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	<script>
	var mem$t='';
	$(document).ready(function(){
		$('#flexRT$t').flexigrid({
			url: '$page?restored-list=yes&t=$t',
			dataType: 'json',
			colModel : [
			{display: '$date', name : 'zDate', width :147, sortable : true, align: 'left'},
			{display: '$backup_container', name : 'fullpath', width : 414, sortable : false, align: 'left'},
			{display: '$delete', name : 'delete', width : 31, sortable : false, align: 'left'},
	
	
			],
	
buttons : [
	{name: '$restore_a_backup', bclass: 'Down', onpress : Restore$t},
	{name: '$restore_all_containers', bclass: 'Down', onpress : RestoreAll$t},
	{name: '$delete_all', bclass: 'Delz', onpress : DeleteAll$t},
		],	
	searchitems : [
		{display: '$backup_container', name : 'fullpath'},
		{display: '$date', name : 'zDate'},
		],	
			sortname: 'zDate',
			sortorder: 'desc',
			usepager: true,
			title: '<span style=font-size:14px>$restored_containers</span>',
			useRp: false,
			rp: 50,
			showTableToggleBtn: false,
			width: 650,
			height: 480,
			singleSelect: true
	
		});
	});
	
	function Restore$t(){
		YahooWin6('645','$page?form-restore=yes','$restore_a_backup')
	
	}
	
	function Zoom$t(filenc){
		YahooWin6('650','$page?zoom-restored='+filenc,'Zoom');
	
	}
	var xRecoverDeleteContain$t= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#row'+mem$t).remove();

	}	
	var xRestoreAll$t= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
	}	
	
	var xDeleteAll$t= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#flexRT$t').flexReload();
	}	
		
	
	function RecoverDeleteContain$t(enc,id){
		if(!confirm('$delete ?')){return;}
			mem$t=id;
			var XHR = new XHRConnection();
			XHR.appendData('RecoverDelete', encodeURIComponent(enc));
			XHR.sendAndLoad('$page', 'POST',xRecoverDeleteContain$t);		
	}
	
	function DeleteAll$t(){
		if(!confirm('$delete_all ?')){return;}
			var XHR = new XHRConnection();
			XHR.appendData('DeleteAll', 'yes');
			XHR.sendAndLoad('$page', 'POST',xDeleteAll$t);		
	}
	
	function RestoreAll$t(){
		var dir='$ArticaProxyStatisticsRestoreFolder';
		if(!confirm('$restore_all_containers\\nFrom: $ArticaProxyStatisticsRestoreFolder ?')){return;}
			var XHR = new XHRConnection();
			XHR.appendData('RecoverAll', 'yes');
			XHR.sendAndLoad('$page', 'POST',xRestoreAll$t);		
	}
	
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function restored_items(){
	$Mypage=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$t=time();
	$fontsize=14;
	$table="squidlogs_restores";
	$database="artica_events";
	$q=new mysql();
	$t=$_GET["t"];
	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";	
	

	$data = array();
	$data['page'] = 0;
	$data['total'] = $total;
	$data['rows'] = array();

	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	
	if(!$q->COUNT_ROWS($table, $database)){json_error_show("Empty table",1);}
	
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show("$q->mysql_error",1);}
	if(mysql_num_rows($results)==0){json_error_show("No data",1);}

	$data['total'] = mysql_num_rows($results);

	$style="style='font-size:{$fontsize}px'";

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$zDate=$ligne["zDate"];
		$fullpath=$ligne["fullpath"];
		$fullpathenc=urlencode(base64_encode($fullpath));
		$filename=basename($fullpath);
		$md=md5(serialize($ligne));
		$delete=imgsimple("delete-32.png",null,"RecoverDeleteContain$t('$fullpathenc','$md')");
		$data['rows'][] = array(
		'id' => $md,
		'cell' => array(
				"<span $style>$zDate</span>",
				"<span $style><a href=\"javascript:blur();\" OnClick=\"javascript:Zoom$t('$fullpathenc');\" style='font-size:{$fontsize}px;text-decoration:underline'>$filename</a><div style='font-size:11px'><i>{$ligne["fullpath"]}</i></div></span>",
				"<span $style>$delete</span>",
				)
			);


	}

	echo json_encode($data);
					
}


function schedules(){
	include_once(dirname(__FILE__)."/ressources/class.tasks.inc");
	$page=CurrentPageName();
	$tpl=new templates();
	$task=new system_tasks();
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM webfilters_schedules WHERE TaskType='48'";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "<H2>$q->mysql_error</H2>";return;}
	while ($ligne = mysql_fetch_assoc($results)) {
		$TimeDescription=$ligne["TimeDescription"];
		$TimeText=$task->PatternToHuman($ligne["TimeText"],true);
		if(preg_match("#(.+?)\s+(.+?)\s+(.+?)\s+(.+?)\s+(.+?)#", $TimeDescription,$re)){$TimeDescription=$TimeText;$TimeText=null;}
		$ID=$ligne["ID"];
		$tr[]="
		<tr>
		<td width=1%><img src=\"img/arrow-right-24.png\"></td>
		<td width=99% nowrap>
		<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.databases.schedules.php?AddNewSchedule-js=yes&ID=$ID&YahooWin=3');\" 
		style=\"font-size:16px;text-decoration:underline\">$TimeDescription</a>
		<div style='font-size:10px'><i>$TimeText</div></div>
		</td>
		<td width=1%>".imgtootltip("32-run.png","{run}","Loadjs('squid.databases.schedules.php?schedule-run-js=yes&ID=$ID');")."</td>
		</tr>
		";
	
	}
	
	$html=$html."
	<div style=\"font-size:18px;margin-top:10px\">{schedules}:</div>
			<table style=\"width:99%\" class=\"form\">".@implode("\n", $tr)."</table>";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function Save(){
	$sock=new sockets();
	$users=new usersMenus();
	$tpl=new templates();
	$sock->SET_INFO("ArticaProxyStatisticsRestoreFolder", $_POST["ArticaProxyStatisticsRestoreFolder"]);
}
function zoom_restored(){
	$tpl=new templates();
	$zoom_restored=base64_decode($_GET["zoom-restored"]);
	$q=new mysql();
	$sql="SELECT `results` FROM squidlogs_restores WHERE fullpath='$zoom_restored'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	echo $tpl->_ENGINE_parse_body("<textarea style='width:100%;height:275px;font-size:12px;border:4px solid #CCCCCC;
	font-family:\"Courier New\",
	Courier,monospace;color:black' id='subtitle'>{$ligne["results"]}</textarea>");
}
function RecoverDelete(){
	$tpl=new templates();
	$filename=base64_decode(url_decode_special_tool($_POST["RecoverDelete"]));
	if(strpos("\n", $filename)>0){$aa=explode("\n",$filename);$filename=$aa[0];}
	$q=new mysql();
	$sql="DELETE FROM squidlogs_restores WHERE `fullpath`='$filename'";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){echo $q->mysql_error;writelogs($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);}
}
function form_restore(){
	$page=CurrentPageName();
	$tpl=new templates();
	$restore_a_backup=$tpl->javascript_parse_text("{restore_a_backup}");
	$t=time();
	$html="<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{file}:</td>
		<td>". Field_text("restoref$t",null,"font-size:16px;width:400px")."</td>
		<td>". button("{browse}...", "Loadjs('tree.php?target-form=restoref$t')",11)."</td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button("{restore}", "Restore$t()",16)."</td>
	</tr>
	</table>		
<script>
	var xRestore$t= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
	}	
	
	
	function Restore$t(){
		var path=encodeURIComponent(document.getElementById('restoref$t').value);
		if(!confirm('$restore_a_backup  path ?')){return;}
			var XHR = new XHRConnection();
			XHR.appendData('RestoreSingle', path);
			XHR.sendAndLoad('$page', 'POST',xRestore$t);		
	}
</script>							
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
function RestoreSingle(){
	$path=urlencode(base64_encode(url_decode_special_tool($_POST["RestoreSingle"])));
	$sock=new sockets();
	$sock->getFrameWork("squidstats.php?backup-stats-restore=$path");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{task_executed_in_background}");
}
function RecoverAll(){
	$sock=new sockets();
	$sock->getFrameWork("squidstats.php?backup-stats-restore-all=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{task_executed_in_background}");	
	
}