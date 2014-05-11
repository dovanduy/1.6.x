<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["title_array"]["size"]="{downloaded_flow}";
	$GLOBALS["title_array"]["req"]="{requests}";	
	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die("no rights");}	

	
	if(isset($_GET["js"])){js();exit;}

	
	
	
	if(isset($_GET["list"])){zlist();die();}
	if(isset($_GET["today-zoom"])){today_zoom_js();exit;}
	if(isset($_GET["today-zoom-popup"])){today_zoom_popup();exit;}
	if(isset($_GET["today-zoom-popup-history"])){today_zoom_popup_history();exit;}
	if(isset($_GET["today-zoom-popup-history-list"])){today_zoom_popup_history_list();exit;}
	if(isset($_GET["today-zoom-popup-members"])){today_zoom_popup_members();exit;}
	if(isset($_GET["today-zoom-popup-member-list"])){today_zoom_popup_members_list();exit;}
	
	
	
	if(isset($_GET["statistics-days-left-status"])){left_status();exit;}
	if($GLOBALS["VERBOSE"]){echo "->PAGE()<br>\n";}
	
	
	
	
page_de_garde();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$size=950;
	if(isset($_GET["with-purge"])){$purge="&with-purge=yes";$size=985;}
	
	$title=$tpl->_ENGINE_parse_body("{internet_access_per_day}");
	$html="YahooWin('$size','$page?byjs=yes$purge','$title')";
	echo $html;
}




function today_zoom_popup(){
	
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$tpl=new templates();	
	if(!isset($_GET["day"])){$_GET["day"]=$q->HIER();}	
	$t=time();
	$today="{today}";
	if($_GET["day"]<>date("Y-m-d")){
		$time=strtotime("{$_GET["day"]} 00:00:00");
		$today=date("{l} d {F}",$time);
	}
	
	$tpl=new templates();
	$array["website-zoom"]='{website}';
	$array["website-catz"]='{categories}';
	$array["today-zoom-popup-history"]="{history}:$today";
	$array["today-zoom-popup-members"]="{members}:$today";

	while (list ($num, $ligne) = each ($array) ){
		
				
		if($num=="website-zoom"){
			$html[]= "<li><a href=\"squid.website-zoom.php?sitename={$_GET["familysite"]}&day={$_GET["day"]}\"><span>$ligne</span></a></li>\n";
			continue;
		}

		if($num=="website-catz"){
			$html[]= "<li><a href=\"squid.categorize.php?popup=yes&www={$_GET["familysite"]}&bykav=&day={$_GET["day"]}&group=&table-size=993&row-explain=764\"><span>$ligne</span></a></li>\n";
			continue;
		}			
		
		
		
		
		$html[]= "<li><a href=\"$page?$num=yes&day={$_GET["day"]}&type={$_GET["type"]}&familysite={$_GET["familysite"]}\"><span style='font-size:14px'>$ligne</span></a></li>\n";
	}
	
	$t=time();
	echo build_artica_tabs($html, $t);
			
	
	
	
}







function zlist(){
	$Mypage=CurrentPageName();
	$tpl=new templates();		
	$q=new mysql_squid_builder();	
	$t=time();
	$fontsize=13;
	$type=$_GET["type"];
	$field_query="size";
	$field_query2="SUM(size)";	
	$table_field="{size}";
	$category=$tpl->_ENGINE_parse_body("{category}");
	$hour_table="squidhour_".date('YmdH');
		
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	
	$table="(SELECT COUNt(*) as hits, SUM(QuerySize) as size,uid,CLIENT,MAC,sitename FROM $hour_table 
	GROUP BY uid,CLIENT,MAC,sitename HAVING sitename='{$_GET["sitename"]}' ) as t";
	
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
	
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"]+1;
	}
	
	
	
	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql";	
	
	
	$results=$q->QUERY_SQL($sql);
	
	$data = array();
	$data['page'] = 0;
	$data['total'] = $total;
	$data['rows'] = array();	
	
	
	if(!$q->ok){json_error_show($q->mysql_error);};	
	if(mysql_num_rows($results)==0){json_error_show("No data");}
	
	$data['total'] = mysql_num_rows($results);
	$fontsize=14;
	$style="style='font-size:{$fontsize}px'";
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
		$id=md5(@implode("", $ligne));
		
		if(trim($ligne["uid"])=="-"){$ligne["uid"]=null;}

		$ligne["hits"]=FormatNumber($ligne["hits"]);
		
		$data['rows'][] = array(
			'id' => $id,
			'cell' => array(
			"<span $style>{$ligne["uid"]}</span>",
			"<span $style>{$ligne["CLIENT"]}</span>",
			"<span $style>{$ligne["MAC"]}</span>",
			"<span $style>{$ligne["hits"]}</a></span>",
			"<span $style>{$ligne["size"]}</span>",
			)
			);		
		
		
	}

echo json_encode($data);	
		
	
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}





function page_de_garde(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$uid=$tpl->_ENGINE_parse_body("{uid}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$MAC=$tpl->_ENGINE_parse_body("{MAC}");
	$t=time();
	
	$buttons="	buttons : [
	{name: '$new_port', bclass: 'add', onpress : HTTPSafePortSSLAdd},
	
	],";
	$buttons=null;
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	<script>
	
	function Start$t(){
	$(document).ready(function(){
	$('#flexRT$t').flexigrid({
	url: '$page?list=yes&sitename={$_GET["sitename"]}',
	dataType: 'json',
	colModel : [
	{display: '$uid', name : 'uid', width : 220, sortable : true, align: 'left'},
	{display: '$MAC', name : 'MAC', width : 220, sortable : true, align: 'left'},
	{display: '$ipaddr', name : 'CLIENT', width : 201, sortable : true, align: 'left'},
	{display: '$hits', name : 'hits', width : 110, sortable : true, align: 'left'},
	{display: '$size', name : 'size', width : 110, sortable : true, align: 'left'},
	],$buttons
	
	searchitems : [
	{display: '$uid', name : 'uid'},
	{display: '$ipaddr', name : 'CLIENT'},
	],
	
	sortname: 'size',
	sortorder: 'desc',
	usepager: true,
	title: '{$_GET["sitename"]}',
	useRp: false,
	rp: 15,
	showTableToggleBtn: false,
	width: '95%',
	height: 450,
	singleSelect: true
	});
	});
	}
	
	
	function REFRESH_HTTP_SAFE_PORTS_SSL_LIST(){
	$('#$t').flexReload();
	}
	
	var x_HTTPSafePortSSLAdd=function (obj) {
	var results=obj.responseText;
	if (results.length>0){alert(results);}
	REFRESH_HTTP_SAFE_PORTS_SSL_LIST();
	}
	
	function HTTPSafePortSSLAdd(){
	var XHR = new XHRConnection();
	var explain='';
	var value=prompt('$HTTP_ADD_SAFE_PORTS_EXPLAIN');
	if(!value){return;}
	explain=prompt('$GIVE_A_NOTE','my specific web port...');
	if(value){
		XHR.appendData('http-safe-ports-ssl-add',value);
		XHR.appendData('http-safe-ports-ssl-explain',explain);
		XHR.sendAndLoad('$page', 'GET',x_HTTPSafePortSSLAdd);
	}
	}
	
	
	var x_HttpSafePortSSLDelete= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	$('#row'+rowSquidPosrt).remove();
	}
	
	function HttpSafePortSSLDelete(enc,id){
	rowSquidPosrt=id;
	var XHR = new XHRConnection();
	XHR.appendData('http-safe-ports-ssl-del',enc);
	XHR.sendAndLoad('$page', 'GET',x_HttpSafePortSSLDelete);
	}
	
	
	Start$t();
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	}







