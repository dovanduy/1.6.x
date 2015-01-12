<?php
if(isset($_GET["verbose"])){echo __LINE__." verbose OK<br>\n";$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	include_once('ressources/class.calendar.inc');
	include_once('ressources/class.tcpip.inc');
	$users=new usersMenus();
	if(!$users->AsDansGuardianAdministrator){die();}	
	if(isset($_GET["events"])){popup_list();exit;}
	if(isset($_POST["unlock"])){unlock();exit;}
	if(isset($_POST["biglock"])){biglock();exit;}
	if(isset($_GET["js"])){FULL_JS();exit;}
	if(isset($_GET["calendar"])){calendar();exit;}
	if(isset($_GET["title-zday"])){calendar_title();exit;}
	if(isset($_GET["build-calendar"])){calendar_build();exit;}
	if(isset($_POST["reload-unlock"])){reload_unlock();exit;}
	if(isset($_GET["full-js"])){FULL_JS();exit;}
	if(isset($_GET["tabs"])){tabs();exit;}
	
	if(isset($_GET["week"])){week_table();exit;}
	if(isset($_GET["week-events"])){week_events();exit;}
BlockedSites2();	


function FULL_JS(){
	header("content-type: application/x-javascript");
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{blocked_requests}");
	echo "YahooWinBrowse('1013','$page?tabs=yes&t=$t&noreduce=yes','$title')";	
}

function tabs(){
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$q=new mysql_squid_builder();


	if($q->TABLE_EXISTS($q->WEEK_TABLE_BLOCKED_CURRENT())){
		$array["week"]="{this_week}";
	}
	$zday=date('Ymd');
	$table=$zday."_blocked";
	if($q->TABLE_EXISTS($table)){
		$array["table-".date("Y-m-d")]="{today}";
	}
	
	$sql="SELECT tablename,DATE_FORMAT( zDate, '%Y%m%d' ) 
		  AS tablesource,DATE_FORMAT( zDate, '%Y-%m-%d' ) as zday
		  FROM tables_day  WHERE WEEK( zDate )=WEEK(NOW()) 
		AND  YEAR( zDate ) = YEAR(NOW()) ORDER BY zDate";
	$results=$q->QUERY_SQL($sql);
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$dt=strtotime("{$ligne["zday"]} 00:00:00");
		$MyDay=date("{l}",$dt);
		$table=$ligne["tablesource"]."_blocked";
		if(!$q->TABLE_EXISTS($table)){continue;}
		$array["table-{$ligne["zday"]}"]=$MyDay;
	}
	
	
	
	$style="style='font-size:16px'";
	$array["week"]="{this_week} $table_week";
	while (list ($num, $ligne) = each ($array) ){
		
		
		if(preg_match("#^table-(.+)#", $num,$re)){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?zday={$re[1]}\"><span $style>$ligne</span></a></li>\n");
			continue;
		}
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span $style>$ligne</span></a></li>\n");
	}
	echo build_artica_tabs($html, "blocked_requests_tabs");	
	
	
	
}

function week_table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$webservers=$tpl->_ENGINE_parse_body("{webservers}");
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$time=$tpl->_ENGINE_parse_body("{day}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$country=$tpl->_ENGINE_parse_body("{country}");
	$url=$tpl->_ENGINE_parse_body("{url}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$rule=$tpl->_ENGINE_parse_body("{rule}");
	$title=$tpl->_ENGINE_parse_body(date("{l} d {F}")." {blocked_requests}");
	$unblock=$tpl->javascript_parse_text("{unblock}");
	$UnBlockWebSiteExplain=$tpl->javascript_parse_text("{UnBlockWebSiteExplain}");
	$smtp_notifications=$tpl->javascript_parse_text("{smtp_notifications}");
	$UnBlockWebSiteExplainReload=$tpl->javascript_parse_text("{need_to_proxy_reload_ask}");
	$divstart="<div style='margin:-10px;margin-left:-15px;margin-right:-15px'>";
	$divend="</div>";
	if(isset($_GET["noreduce"])){$divstart=null;$divend=null;}
	$hostname=$tpl->javascript_parse_text("{hostname}");
	$days=$tpl->javascript_parse_text("{days}");
	$t=md5("currentWeek$t");
	$this_week=$tpl->_ENGINE_parse_body("{this_week}");
	$move=$tpl->_ENGINE_parse_body("{move}");
	 // zMD5                             
	 // | day | hits | client         | hostname      | MAC | uid | account | website                   | category | rulename | event | why            | explain | blocktype
	$buttons="
	buttons : [
	{name: '<b>$days</b>', bclass: 'Calendar', onpress : ChooseDays$t},
	
	],";
	$buttons=null;
	
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'>
	<tr><td align=center><img src='img/wait_verybig_mini_red.gif'></td></tr>
	</table>
	
	<script>
function flexigridStart$t(){
	$('#flexRT$t').flexigrid({
	url: '$page?week-events=yes&t=$t',
	dataType: 'json',
	colModel : [
	{display: '$time', name : 'day', width :32, sortable : true, align: 'center'},
	{display: '$member', name : 'client', width : 105, sortable : true, align: 'left'},
	{display: '$webservers', name : 'website', width : 194, sortable : true, align: 'left'},
	{display: '$hits', name : 'hits', width : 50, sortable : true, align: 'left'},
	
	{display: '$category', name : 'category', width : 89, sortable : true, align: 'left'},
	{display: '$rule', name : 'rulename', width : 89, sortable : true, align: 'left'},
	{display: '$move', name : 'xmove', width : 155, sortable : false, align: 'center'},
	{display: '$move', name : 'xmove1', width : 60, sortable : false, align: 'center'},
	{display: '$unblock', name : 'move', width : 70, sortable : false, align: 'center'},
	
	],
	$buttons
	searchitems : [
	{display: '$member', name : 'uid'},
	{display: '$ipaddr', name : 'client'},
	{display: '$hostname', name : 'hostname'},
	{display: '$webservers', name : 'website'},
	{display: '$category', name : 'category'},
	{display: '$rule', name : 'rulename'},
	],
	
	sortname: 'day',
	sortorder: 'desc',
	usepager: true,
	useRp: true,
	title: '<span style=\"font-size:14px\" id=\"title-$t\">$this_week</span>',
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 500,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500,1000,1500]
	
	});
	}
	
	var x_UnBlockWebSite2$t=function(obj){
	var tempvalue=obj.responseText;
	$('#flexRT$t').flexReload();
	}
	
var x_UnBlockWebSite$t=function(obj){
	var tempvalue=obj.responseText;
	if(!confirm(tempvalue+'$UnBlockWebSiteExplainReload')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('reload-unlock','yes');
	XHR.sendAndLoad('$page', 'POST',x_UnBlockWebSite2$t);
	$('#flexRT$t').flexReload();
}
	
function UnBlockWebSite$t(domain){
	if(confirm('$UnBlockWebSiteExplain:'+domain+' ?')){
		var XHR = new XHRConnection();
		XHR.appendData('unlock',domain);
		XHR.sendAndLoad('$page', 'POST',x_UnBlockWebSite$t);
		}
	}
	
function ChooseDays$t(){
	YahooWin5('500','$page?calendar=yes&t=$t','Calendar...');
}
	
function NotifsParams$t(){
	Loadjs('ufdbguard.smtp.notif.php?js=yes');
}
setTimeout('flexigridStart$t()',800);
</script>
	
	
			";
			echo $html;	
	
	
}



function js(){
	header("content-type: application/x-javascript");
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{blocked_requests}");
	echo "YahooWin4('705','$page?popup=yes&t=$t&noreduce=yes','$title')";

}

function calendar_title() {
	$dt=strtotime("{$_GET["title-zday"]} 00:00:00");
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body(date("{l} d {F}",$dt)." {blocked_requests}");
}

function BlockedSites2(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$webservers=$tpl->_ENGINE_parse_body("{webservers}");
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$time=$tpl->_ENGINE_parse_body("{time}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$country=$tpl->_ENGINE_parse_body("{country}");
	$url=$tpl->_ENGINE_parse_body("{url}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$rule=$tpl->_ENGINE_parse_body("{rule}");
	$title=$tpl->_ENGINE_parse_body(date("{l} d {F}")." {blocked_requests}");
	$unblock=$tpl->javascript_parse_text("{unblock}");
	$UnBlockWebSiteExplain=$tpl->javascript_parse_text("{UnBlockWebSiteExplain}");
	$smtp_notifications=$tpl->javascript_parse_text("{smtp_notifications}");
	$UnBlockWebSiteExplainReload=$tpl->javascript_parse_text("{need_to_proxy_reload_ask}");
	$divstart="<div style='margin:-10px;margin-left:-15px;margin-right:-15px'>";
	$divend="</div>";
	if(isset($_GET["noreduce"])){$divstart=null;$divend=null;}
	$hostname=$tpl->javascript_parse_text("{hostname}");
	$move=$tpl->javascript_parse_text("{move}");
	$days=$tpl->javascript_parse_text("{days}");
	if(!isset($_GET["zday"])){$_GET["zday"]=date("Y-m-d");}
	
	$t=time();
	
	if(isset($_GET["zday"])){
		$title=$tpl->_ENGINE_parse_body(date("{l} d {F}",strtotime($_GET["zday"]." 00:00:00"))." {blocked_requests}");
		$addon="&zday={$_GET["zday"]}";
	}
	
	$buttons="
	buttons : [
	{name: '<b>$days</b>', bclass: 'Calendar', onpress : ChooseDays$t},
	{name: '<b>$smtp_notifications</b>', bclass: 'eMail', onpress : NotifsParams$t},
	
	],";	
	
	
	$html="
	<center id='Animate-$t'></center>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
<script>
function flexigridStart$t(){
$('#flexRT$t').flexigrid({
	url: '$page?events=yes&t=$t$addon',
	dataType: 'json',
	colModel : [
		{display: '$time', name : 'zDate', width :119, sortable : true, align: 'left'},
		{display: '$member', name : 'client', width : 139, sortable : true, align: 'left'},
		{display: '$webservers', name : 'website', width : 234, sortable : true, align: 'left'},
		{display: '$category', name : 'category', width : 89, sortable : true, align: 'left'},
		{display: '$rule', name : 'rulename', width : 200, sortable : true, align: 'left'},
		{display: '$move', name : 'unblock1', width : 155, sortable : false, align: 'left'},
		{display: '$move', name : 'unblock2', width : 31, sortable : false, align: 'center'},
		{display: '$unblock', name : 'unblock3', width : 70, sortable : false, align: 'center'},
		
		],
		$buttons
	searchitems : [
		{display: '$member', name : 'uid'},
		{display: '$ipaddr', name : 'client'},
		{display: '$hostname', name : 'hostname'},
		{display: '$webservers', name : 'website'},
		{display: '$category', name : 'category'},
		{display: '$rule', name : 'rulename'},
		],			
		
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	useRp: true,
	title: '<span style=\"font-size:14px\" id=\"title-$t\">$title</span>',
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 600,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500,1000,1500]
	
	}); 
	document.getElementById('Animate-$t').innerHTML='';
	
}

	var x_UnBlockWebSite2$t=function(obj){
	      var tempvalue=obj.responseText;
	      $('#flexRT$t').flexReload();
	      }

	var x_UnBlockWebSite$t=function(obj){
	    var tempvalue=obj.responseText;
	    
		if(confirm(tempvalue+'$UnBlockWebSiteExplainReload')){
			var XHR = new XHRConnection();
			XHR.appendData('reload-unlock','yes');
			XHR.sendAndLoad('$page', 'POST',x_UnBlockWebSite2$t);
			return;
		}	      
	      
	      
	      $('#flexRT$t').flexReload();
	}	

function UnBlockWebSite$t(domain){
	if(confirm('$UnBlockWebSiteExplain:'+domain+' ?')){
		var XHR = new XHRConnection();
		XHR.appendData('unlock',domain);
		XHR.sendAndLoad('$page', 'POST',x_UnBlockWebSite$t);
	}

}

function ChooseDays$t(){
	YahooWin5('500','$page?calendar=yes&t=$t','Calendar...');

}

function NotifsParams$t(){
	Loadjs('ufdbguard.smtp.notif.php?js=yes');
}

	AnimateDiv('Animate-$t');
	setTimeout('flexigridStart$t()',900);
</script>
	
	
	";
echo $html;	

}

function week_events(){
	$ID=$_GET["taskid"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$t=$_GET["t"];
	$q=new mysql_squid_builder();
	
	
	$CATEGORIES=$q->LIST_ALL_CATEGORIES();
	
	
	
	$table=$q->WEEK_TABLE_BLOCKED_CURRENT();
	$search='%';

	
	
	$page=1;
	$FORCE_FILTER="";
	if(!$q->TABLE_EXISTS("$table")){json_error_show("$table No such table");}
	if($q->COUNT_ROWS("$table",'artica_events')==0){json_error_show("$table No data");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	$q2=new mysql();
	
	
	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
	
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,'artica_events'));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,'artica_events');
	if(!$q->ok){json_error_show("$table: $q->mysql_error",2);}
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$today=date('Y-m-d');
	if(!$q->ok){json_error_show($q->mysql_error,2);}
	
		if(mysql_num_rows($results)==0){
		json_error_show($sql,2);}
	
	
		while ($ligne = mysql_fetch_assoc($results)) {
		$ligne["zDate"]=str_replace($today,"{today}",$ligne["zDate"]);
		if(preg_match("#plus-(.+?)-artica#",$ligne["category"],$re)){$ligne["category"]=$re[1];}
		$ligne["zDate"]=$tpl->_ENGINE_parse_body("{$ligne["zDate"]}");
		$id=md5(serialize($ligne));
		$blocktype=null;
	
		$member=$ligne["client"];
		if($ligne["hostname"]<>null){$member=$ligne["hostname"];}
		if($ligne["uid"]<>null){$member=$ligne["uid"];}
		$unblock=imgsimple("whitelist-24.png",null,"UnBlockWebSite$t('{$ligne["website"]}')");
	
		$ligne3=mysql_fetch_array($q2->QUERY_SQL("SELECT items FROM urlrewriteaccessdeny WHERE items='{$ligne["website"]}'","artica_backup"));
		if(!$q->ok){ $unblock="<img src='img/icon_err.gif'><br>$q->mysql_error"; }
		else{
			if($ligne3["items"]<>null){
				$unblock=imgsimple("20-check.png",null,null);
			}
		}
		
		
	if($ligne["blocktype"]<>null){
		$blocktype="<br><i style='font-size:10px'>{$ligne["blocktype"]}</i>";
	}
	
	$field=Field_array_Hash($CATEGORIES, "Move$id",trim($ligne["category"]),"blur()");
	
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array(
			"<span style='font-size:14px;'>{$ligne["day"]}</span>",
			"<strong style='font-size:14px;'>$member</a></strong>",
			"<span style='font-size:14px;'>
				<a href=\"javascript:blur();\" 
				OnClick=\"javascript:Loadjs('squid.categories.php?category={$ligne["category"]}&website={$ligne["website"]}')\"
				style='font-weight:bold;text-decoration:underline;font-size:14px'>{$ligne["website"]}</a></span>$blocktype",
			"<span style='font-size:14px;'>{$ligne["hits"]}</a></span>",
			"<span style='font-size:14px;'>{$ligne["category"]}</a></span>",
			"<span style='font-size:14px;'>{$ligne["rulename"]}</a></span>",
			"$field",
			"<a href=\"javascript:Loadjs('squid.categories.php?move-js=yes&domain={$ligne["website"]}
			&Tofield=Move$id&removerow=$id&fromwhat={$ligne["category"]}');\"><img src='img/arrow-blue-left-24.png'></a>",
			$unblock
			)
		);
		}
echo json_encode($data);	
}

function popup_list(){
	$ID=$_GET["taskid"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$t=$_GET["t"];
	$zday=date('Ymd');
	if(isset($_GET["zday"])){
		$dt=strtotime("{$_GET["zday"]} 00:00:00");
		$zday=date('Ymd',$dt);
	}
	$search='%';
	$table=$zday."_blocked";	
	$page=1;
	$FORCE_FILTER="";
	if(!$q->TABLE_EXISTS("$table")){json_error_show("$table No such table");}
	if($q->COUNT_ROWS("$table",'artica_events')==0){json_error_show("No data");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	$q2=new mysql();

	
	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
		
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,'artica_events'));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,'artica_events');
	if(!$q->ok){json_error_show($q->mysql_error,2);}
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$today=date('Y-m-d');
	if(!$q->ok){json_error_show($q->mysql_error,2);}	
	
	if(mysql_num_rows($results)==0){
		json_error_show($sql,2);}
	
	$CATEGORIES=$q->LIST_ALL_CATEGORIES();
	while ($ligne = mysql_fetch_assoc($results)) {
	$ligne["zDate"]=str_replace($today,"{today}",$ligne["zDate"]);
	if(preg_match("#plus-(.+?)-artica#",$ligne["category"],$re)){$ligne["category"]=$re[1];}
	$ligne["zDate"]=$tpl->_ENGINE_parse_body("{$ligne["zDate"]}");
	$id=md5(serialize($ligne));
	$blocktype=null;
	
	$member=$ligne["client"];
	if($ligne["hostname"]<>null){$member=$ligne["hostname"];}
	if($ligne["uid"]<>null){$member=$ligne["uid"];}
	$websiteenc=urlencode($ligne["website"]);
	$unblock=imgsimple("whitelist-24.png",null,"Loadjs('squid.unblock.php?www=$websiteenc')");
	
	$ligne3=mysql_fetch_array($q2->QUERY_SQL("SELECT items FROM urlrewriteaccessdeny WHERE items='{$ligne["website"]}'","artica_backup"));
	if(!$q->ok){
		$unblock="<img src='img/icon_err.gif'><br>$q->mysql_error";
	}else{
		if($ligne3["items"]<>null){
			$unblock=imgsimple("20-check.png",null,null);
		}
	}
	
	if($ligne["blocktype"]<>null){
		$blocktype="<br><i style='font-size:10px'>{$ligne["blocktype"]}</i>";
	}
	
	
	$field=Field_array_Hash($CATEGORIES, "Move$id",trim($ligne["category"]),"blur()");
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array(
			"<span style='font-size:12px;'>{$ligne["zDate"]}</span>",
			"<strong style='font-size:12px;'>$member</a></strong>",
			"<span style='font-size:13px;'><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.categories.php?category={$ligne["category"]}&website={$ligne["website"]}')\" 
			style='font-weight:bold;text-decoration:underline;font-size:13px'>{$ligne["website"]}</a></span>$blocktype",
			"<span style='font-size:13px;font-weight:bold'>{$ligne["category"]}</a></span>",
			"<span style='font-size:13px;'>{$ligne["rulename"]}</a></span>",
			"$field",
			"<a href=\"javascript:Loadjs('squid.categories.php?move-js=yes&domain={$ligne["website"]}&Tofield=Move$id&removerow=$id&fromwhat={$ligne["category"]}');\"><img src='img/arrow-blue-left-24.png'></a>",
			$unblock
			)
		);
	}
	
	
echo json_encode($data);		


}

function unlock(){
	$table="urlrewriteaccessdeny";
	$q=new mysql();
	$q1=new mysql_squid_builder();
	$acl=new squid_acls();
	$IP=new IP();
	if(strpos($_POST["unlock"], ",")>0){
		$tr=explode(",",$_POST["unlock"]);
	}else{
		$tr[]=$_POST["unlock"];
	}
	
	while (list ($none,$www ) = each ($tr) ){
		$www=$acl->dstdomain_parse($www);
		if($www==null){continue;}
		$q->QUERY_SQL("INSERT IGNORE INTO urlrewriteaccessdeny (items) VALUES ('{$_POST["unlock"]}')","artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	if(isset($_POST["noreload"])){return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-whitelist=yes");
}




function biglock(){
	$table="deny_websites";
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$acl=new squid_acls();
	
	if(strpos($_POST["biglock"], ",")>0){
		$tr=explode(",",$_POST["biglock"]);
	}else{
		$tr[]=$_POST["biglock"];
	}
	
	$sql="CREATE TABLE IF NOT EXISTS `deny_websites` ( `items` VARCHAR( 255 ) NOT NULL PRIMARY KEY ) ENGINE=MYISAM;";
	$q->QUERY_SQL($sql);
	
	
	while (list ($none,$www ) = each ($tr) ){
		$www=$acl->dstdomain_parse($www);
		if($www==null){continue;}
		$q->QUERY_SQL("INSERT IGNORE INTO deny_websites (items) VALUES ('{$_POST["biglock"]}')");
		if(!$q->ok){echo $q->mysql_error;return;}
		
	}
	echo $tpl->javascript_parse_text("{blacklist}\n".@implode("\n", $tr)."\n{success}.",1);
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-blacklist=yes");	
	
}

function calendar(){
	$t=time();
	$tt=$_GET["t"];
	$page=CurrentPageName();
	$html="<div id='calendar$t'></div>
	<script>
		
				
		function NavCalendar$t(year,month){
			LoadAjax('calendar$t','$page?build-calendar=yes&year='+year+'&month='+month+'&t=$t');
		
		}
		
		function NavCalendarJ$t(year,month,day){
			var zday=year+'-'+month+'-'+day;
			LoadAjaxTiny('title-$tt','$page?title-zday='+zday);
			
			$('#flexRT$tt').flexOptions({url: '$page?events=yes&t=$tt&zday='+zday}).flexReload(); 
		}
		
		NavCalendar$t(".date("Y").",".date("m").")
	</script>";
	
	echo $html;
	
	
}

function calendar_build(){
	$t=$_GET["t"];
	$obj_cal = new classe_calendrier("calendar-$t");
	$obj_cal->USLink=true;
	$obj_cal->afficheMois();
	$obj_cal->afficheSemaines(false);
	$obj_cal->afficheJours(true);
	$obj_cal->afficheNavigMois(true);
	$obj_cal->activeJoursPasses();
	$obj_cal->activeJourPresent();
	
	
	$obj_cal->activeJoursEvenements();	
	
	$obj_cal->setFormatLienMois("javascript:Blurz();\" OnClick=\"javascript:NavCalendar$t('%s','%s');");
	$obj_cal->setFormatLienJours("javascript:Blurz();\" OnClick=\"javascript:NavCalendarJ$t('%s','%s','%s');");

	
	
	
	
	$q=new mysql_squid_builder();
	if(isset($_SESSION["LIST_TABLES_BLOCKED"])){$hash=$_SESSION["LIST_TABLES_BLOCKED"];}else{
	$hash=$q->LIST_TABLES_BLOCKED();
	$_SESSION["LIST_TABLES_BLOCKED"]=$q->LIST_TABLES_BLOCKED();
	}
	
	if(!isset($_SESSION["LIST_TABLES_BLOCKED_EV"])){
	while (list ($tablename,$none ) = each ($hash) ){
		$ct=$q->COUNT_ROWS($tablename);
		if($ct==0){continue;}
		if(!preg_match("#^([0-9]+)_blocked$#", $tablename,$re)){continue;}
		
		$intval=$re[1];
		$Cyear=substr($intval, 0,4);
		$CMonth=substr($intval,4,2);
		$CDay=substr($intval,6,2);
		$CDay=str_replace("_", "", $CDay);
		$time=strtotime("$Cyear-$CMonth-$CDay 00:00:00");		
		$year=date("Y",$time);
		$month=date("m",$time);
		$day=date("d",$time);
		$_SESSION["LIST_TABLES_BLOCKED_EV"]["$year-$month-$day"]="$ct Hits";
		
		$obj_cal->ajouteEvenement("$year-$month-$day","$ct Hits");
	}
	}else{
		$hash=$_SESSION["LIST_TABLES_BLOCKED_EV"];
		while (list ($d,$h ) = each ($hash) ){
			$obj_cal->ajouteEvenement($d,$h);
		}
	}
	
	$calendar=$obj_cal->makeCalendrier($_GET["year"],$_GET["month"]);
	echo $calendar;
	
}

function  reload_unlock(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?reload_unlock=yes");
}
