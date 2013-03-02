<?php

	if(isset($_GET["verbose"])){echo __LINE__." verbose OK<br>\n";$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.users.menus.inc');

$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["kav4proxy-event-search"])){events_search();exit;}
	if(isset($_GET["details"])){details();exit;}
	if(isset($_REQUEST["clean-events"])){clean_events();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{APP_KAV4PROXY}::{update_events}");
	$html="YahooWin5('625','$page?popup=yes','$title');
	
	function Kav4ProxyUpdateDetails(date){
		var dates=escape(date);
		YahooWin6('550','$page?details='+dates,date);
	}
	
	";
	echo $tpl->_ENGINE_parse_body($html);

}

function popup(){
	$page=CurrentPageName();
	$t=time();
	$tpl=new templates();
	$date=$tpl->_ENGINE_parse_body("{date}");
	$type=$tpl->_ENGINE_parse_body("{xtype}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$clean=$tpl->javascript_parse_text("{clean}");
	$ask_events_clean=$tpl->javascript_parse_text("{ask_events_clean}");
	
	$buttons="
	buttons : [
	
	{name: '$clean', bclass: 'Delz', onpress : clean$t},
	
	],	";	
	
	$html="
	
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
var DeleteSquidAclGroupTemp=0;
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?kav4proxy-event-search=yes',
	dataType: 'json',
	colModel : [
		{display: '$date', name : 'zDate', width : 138, sortable : true, align: 'left'},
		{display: '$events', name : 'subject', width : 652, sortable : false, align: 'left'},
		
	],
	$buttons
	searchitems : [
		{display: '$date', name : 'zDate'},
		{display: '$events', name : 'subject'},
		],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 100,
	rpOptions: [10, 20, 30, 50,100,200,500],
	showTableToggleBtn: false,
	width: 835,
	height: 450,
	singleSelect: true
	
	});   
});	

	var x_clean$t= function (obj) {
		var results=obj.responseText;
	    if(results.length>3){  alert(results);}
	    $('#table-$t').flexReload();	
	    
	}	

function clean$t(){
	if(!confirm('$ask_events_clean')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('clean-events','yes');
	XHR.sendAndLoad('$page', 'POST',x_clean$t);		

}


	</script>
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}
function clean_events(){
	$q=new mysql();
	$q->QUERY_SQL("DROP TABLE kav4proxy_updates","artica_events");
	$q->BuildTables();
	if(!$q->ok){echo $q->mysql_error;}
}

function events_search(){
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$users=new usersMenus();
	$sock=new sockets();
	$ID=$_GET["ID"];
	$table="kav4proxy_updates";		
	$tablename=$table;
	$database="artica_events";
	$search='%';
	$page=1;
	$FORCE_FILTER=null;
	
	
	if(!$q->TABLE_EXISTS($tablename, $database)){json_error_show("$tablename doesn't exists...");}
	if($q->COUNT_ROWS($tablename, $database)==0){json_error_show("No data");}

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);}	

	while ($ligne = mysql_fetch_assoc($results)) {
	$zmd5=md5(serialize($ligne));
	$color="black";
	$color="black";
	$date=null;
	$letter=null;	
	
	
	//familysite 	size 	hits
	
	$urljsSIT="<a href=\"javascript:blur();\" 
	OnClick=\"javascript:Loadjs('miniadm.MembersTrack.sitename.php?ID=$ID&sitename={$ligne["sitename"]}');\"
	style='font-size:12px;text-decoration:underline;color:$color'>";
	
	$urljsCAT="<a href=\"javascript:blur();\" 
	OnClick=\"javascript:Loadjs('miniadm.MembersTrack.category.php?ID=$ID&category=".urlencode($ligne["category"])."');\"
	style='font-size:12px;text-decoration:underline;color:$color'>";	
	
	if(preg_match("#\[(.+?)\s+([A-Z]+)\]\s+(.+)#", $ligne["subject"],$re)){
		$ligne["zDate"]=$re[1];
		$letter=$re[2];
		$ligne["subject"]=$re[3];
	}	
	if($letter=="E"){$color="#DA1111";}
	if($letter=="F"){$color="#DA1111";}	
	
	$data['rows'][] = array(
		'id' => "$zmd5",
		'cell' => array(
			"<span style='font-size:12px;color:$color'>{$ligne["zDate"]}</a></span>",
			"<span style='font-size:12px;color:$color'>{$ligne["subject"]}</a></span>",
			)
		);
	}
	
	
echo json_encode($data);		
return;	
	
	$page=CurrentPageName();
	$tpl=new templates();		 
	if(trim($_GET["search"])<>null){
		$_GET["search"]=$_GET["search"]."*";
		$_GET["search"]=str_replace( "**", "*",$_GET["search"]);
		$_GET["search"]=str_replace( "*", "%",$_GET["search"]);
		$sql="SELECT *,match(content) against('upd') as relevance  FROM `kav4proxy_updates` ORDER BY relevance,zDate DESC LIMIT 0,50";
		
	}else{
		$sql="SELECT subject,zDate FROM `kav4proxy_updates` ORDER BY zDate DESC LIMIT 0,50";
	}
	
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){echo "<H2>$q->mysql_error</H2>";}
	
	$html="<center>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th width=1% colspan=2>{date}</th>
		<th colspan=2>{subject}</th>
	</tr>
</thead>
<tbody class='tbody'>";	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$distance=distanceOfTimeInWords(strtotime($ligne["zDate"]),time());
		
		$js="Kav4ProxyUpdateDetails('{$ligne["zDate"]}')";
		$ahref="<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:14px;text-decoration:underline'>";
		$html=$html."
		<tr class=$classtr>
			<td width=1% nowrap style='font-size:11px'>{$ligne["zDate"]}</td>
			<td width=1% nowrap style='font-size:14px'>$distance</td>
			<td style='font-size:14px'>$ahref{$ligne["subject"]}</a></td>
		</tr>
		";
		
		
	}
	
	$html=$html."</table>
	<script>
		function Kav4ProxyUpdateDetails(date){
		var dates=escape(date);
		YahooWin6('550','$page?details='+dates,date);
	}
	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function details(){
	$q=new mysql();
	$sql="SELECT content FROM kav4proxy_updates WHERE zDate='{$_GET["details"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	$content=$ligne["content"];
	$content=htmlspecialchars($content);
	$content=nl2br($content);
	echo "<div class=form style='width:95%;height:450px;overflow:auto'><code>$content</code></div>";
	
	
	
}