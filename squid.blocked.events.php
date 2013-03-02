<?php

	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	include_once('ressources/class.calendar.inc');
	$users=new usersMenus();
	if(!$users->AsSquidAdministrator){die();}	
	if(isset($_GET["events"])){popup_list();exit;}
	if(isset($_POST["unlock"])){unlock();exit;}
	if(isset($_GET["js"])){js();exit;}
	if(isset($_GET["calendar"])){calendar();exit;}
	if(isset($_GET["title-zday"])){calendar_title();exit;}
	if(isset($_GET["build-calendar"])){calendar_build();exit;}
BlockedSites2();	


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
	
	$divstart="<div style='margin:-10px;margin-left:-15px;margin-right:-15px'>";
	$divend="</div>";
	if(isset($_GET["noreduce"])){$divstart=null;$divend=null;}
	$days=$tpl->javascript_parse_text("{days}");
	$t=time();
	
	$buttons="
	buttons : [
	{name: '<b>$days</b>', bclass: 'Calendar', onpress : ChooseDays$t},
	{name: '<b>$smtp_notifications</b>', bclass: 'eMail', onpress : NotifsParams$t},
	
	],";	
	
	
	$html="
	$divstart
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	$divend
	
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?events=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$time', name : 'zDate', width :94, sortable : true, align: 'left'},
		{display: '$member', name : 'client', width : 92, sortable : true, align: 'left'},
		{display: '$webservers', name : 'website', width : 200, sortable : true, align: 'left'},
		{display: '$category', name : 'category', width : 89, sortable : true, align: 'left'},
		{display: '$rule', name : 'rulename', width : 89, sortable : true, align: 'left'},
		{display: '$unblock', name : 'unblock', width : 31, sortable : true, align: 'center'},
		
		],
		$buttons
	searchitems : [
		{display: '$member', name : 'client'},
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
	width: 689,
	height: 600,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500,1000,1500]
	
	});   
});

	var x_UnBlockWebSite$t=function(obj){
	      var tempvalue=obj.responseText;
	      if(tempvalue.length>3){alert(tempvalue);}
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

</script>
	
	
	";
echo $html;	

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

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
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
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$today=date('Y-m-d');
	if(!$q->ok){json_error_show($q->mysql_error);}	

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
	if(!$q->ok){
		$unblock="<img src='img/icon_err.gif'><br>$q->mysql_error";
	}else{
		if($ligne3["items"]<>null){
		$unblock=imgsimple("20-check.png",null,null);
		}
	}
	if($ligne["blocktype"]<>null){
	$blocktype="<div><i style='font-size:10px'>{$ligne["blocktype"]}</i></div>";
	}
	
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array(
			"<span style='font-size:12px;'>{$ligne["zDate"]}</span>",
			"<span style='font-size:12px;'>$member</a></span>",
			"<span style='font-size:12px;'><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.categories.php?category={$ligne["category"]}&website={$ligne["website"]}')\" 
			style='font-weight:bold;text-decoration:underline;font-size:13px'>{$ligne["website"]}</a></span>$blocktype",
			"<span style='font-size:12px;'>{$ligne["category"]}</a></span>",
			"<span style='font-size:12px;'>{$ligne["rulename"]}</a></span>",
			$unblock
			)
		);
	}
	
	
echo json_encode($data);		


}

function unlock(){
	
	$table="urlrewriteaccessdeny";
	$q=new mysql();
	$q->QUERY_SQL("INSERT IGNORE INTO urlrewriteaccessdeny (items) VALUES ('{$_POST["unlock"]}')","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	if(isset($_POST["noreload"])){return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-whitelist=yes");
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


