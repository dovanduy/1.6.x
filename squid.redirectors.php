<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	
	$user=new usersMenus();
	
	if(!$user->AsSquidAdministrator){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["url_rewrite_children"])){save();exit;}
	if(isset($_GET["status"])){status_table();exit;}
	if(isset($_GET["status-search"])){status_search();exit;}
	
	
js();


function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{squid_redirectors}");
	$html="YahooWin2('990','$page?tabs=yes','$title')";
	echo $html;
}

function tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$array["status"]='{status}';
	$array["popup"]='{squid_redirectors}';
	
	

	while (list ($num, $ligne) = each ($array) ){

		
		
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span style='font-size:22px'>$ligne</span></a></li>\n");
	}

	echo build_artica_tabs($html, "main_config_redirectors");
}


function popup(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$sock=new sockets();
	$squid=new squidbee();
	$RedirectorsArray=unserialize(base64_decode($sock->GET_INFO("SquidRedirectorsOptions")));
	if(!is_numeric($RedirectorsArray["url_rewrite_children"])){$RedirectorsArray["url_rewrite_children"]=20;}
	if(!is_numeric($RedirectorsArray["url_rewrite_startup"])){$RedirectorsArray["url_rewrite_startup"]=5;}
	if(!is_numeric($RedirectorsArray["url_rewrite_idle"])){$RedirectorsArray["url_rewrite_idle"]=1;}
	if(!is_numeric($RedirectorsArray["url_rewrite_concurrency"])){$RedirectorsArray["url_rewrite_concurrency"]=0;}
	$t=time();
	$enable_UfdbGuard=0;
	if($squid->enable_UfdbGuard==1){$enable_UfdbGuard=1;}
	
	$html="
	<div class=explain style='font-size:18px'>{squid_redirectors_howto}</div>
	<div id='$t' style='width:98%' class=form>
	<table style='width:100%' >
	<tbody>
	<tr>
		<td class=legend style='font-size:22px'>{url_rewrite_children}:</td>
		<td>". Field_text("url_rewrite_children",$RedirectorsArray["url_rewrite_children"],"font-size:22px;width:60px")."</td>
		<td width=1%>". help_icon("{url_rewrite_children_text}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{url_rewrite_startup}:</td>
		<td>". Field_text("url_rewrite_startup",$RedirectorsArray["url_rewrite_startup"],"font-size:22px;width:110px")."</td>
		<td width=1%>". help_icon("{url_rewrite_startup_text}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{url_rewrite_idle}:</td>
		<td>". Field_text("url_rewrite_idle",$RedirectorsArray["url_rewrite_idle"],"font-size:22px;width:110px")."</td>
		<td width=1%>". help_icon("{url_rewrite_idle_text}")."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:22px'>{url_rewrite_concurrency}:</td>
		<td>". Field_text("url_rewrite_concurrency",$RedirectorsArray["url_rewrite_concurrency"],"font-size:22px;width:110px")."</td>
		<td width=1%>". help_icon("{url_rewrite_concurrency_text}")."</td>
	</tr>	
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","UrlReWriteSave()",32)."</td>
	</tr>
	
	</table>
	</div>
	<script>
	var x_UrlReWriteSave= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		RefreshTab('main_config_redirectors');
		
	}		

	function UrlReWriteSave(){
		var XHR = new XHRConnection();
		XHR.appendData('url_rewrite_children',document.getElementById('url_rewrite_children').value);
		XHR.appendData('url_rewrite_startup',document.getElementById('url_rewrite_startup').value);
		XHR.appendData('url_rewrite_idle',document.getElementById('url_rewrite_idle').value);
		XHR.appendData('url_rewrite_concurrency',document.getElementById('url_rewrite_concurrency').value);
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_UrlReWriteSave);		
		}
		
	function CheckConcurrency(){
		var enable_UfdbGuard=$enable_UfdbGuard;
		if(enable_UfdbGuard==1){
			document.getElementById('url_rewrite_concurrency').value=0;
			document.getElementById('url_rewrite_concurrency').disabled=true;
		}
	
	}
	
	CheckConcurrency();
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
}

function save(){
	$sock=new sockets();
	$datas=base64_encode(serialize($_POST));
	$sock->SaveConfigFile($datas, "SquidRedirectorsOptions");
	$sock->getFrameWork("squid.php?build-smooth=yes");	
}


	
function status_table(){
		$page=CurrentPageName();
		$tpl=new templates();
		$sock=new sockets();
		$sock->getFrameWork("squid.php?redirectors-refresh=yes");
		$date=$tpl->_ENGINE_parse_body("{zDate}");
		$requests=$tpl->_ENGINE_parse_body("{requests}");
		$replies=$tpl->_ENGINE_parse_body("{replies}");
		$events=$tpl->_ENGINE_parse_body("{events}");
		$status=$tpl->_ENGINE_parse_body("{status}");
		$time=$tpl->_ENGINE_parse_body("{exec_time}");
		$uri=$tpl->javascript_parse_text("{uri2}");
		
		$TB_HEIGHT=450;
		$TB_WIDTH=927;
		$TB2_WIDTH=551;
		$all=$tpl->_ENGINE_parse_body("{all}");
		
		$sock=new sockets();
		$squid=new squidbee();
		$RedirectorsArray=unserialize(base64_decode($sock->GET_INFO("SquidRedirectorsOptions")));
		if(!is_numeric($RedirectorsArray["url_rewrite_children"])){$RedirectorsArray["url_rewrite_children"]=20;}
		$tempfile="/usr/share/artica-postfix/ressources/logs/web/squid_redirectors_status.db";
		$array=unserialize(@file_get_contents($tempfile));
		
		$title=$tpl->javascript_parse_text("{redirectors}: {status} ".count($array)."/{$RedirectorsArray["url_rewrite_children"]}");
		
		$t=time();
	
		$buttons="
		buttons : [
		
		],	";
		$html="
		<table class='events-table-$t' style='display: none' id='events-table-$t' style='width:99%'></table>
		<script>
	
		function BuildTable$t(){
		$('#events-table-$t').flexigrid({
		url: '$page?status-search=yes&text-filter={$_GET["text-filter"]}',
		dataType: 'json',
		colModel : [
		{display: '$status', name : 'severity', width :40, sortable : true, align: 'center'},
		{display: 'PID', name : 'severity', width :78, sortable : true, align: 'center'},
		{display: '$requests', name : 'zDate', width :110, sortable : true, align: 'right'},
		{display: '$replies', name : 'subject', width : 110, sortable : false, align: 'right'},
		{display: '$time', name : 'filename', width :110, sortable : true, align: 'right'},
		{display: '$uri', name : 'filename', width :393, sortable : true, align: 'left'},
		],
		$buttons
	
		searchitems : [
		{display: '$events', name : 'subject'},
		],
		sortname: 'zDate',
		sortorder: 'desc',
		usepager: true,
		title: '<span style=font-size:18px>$title</span>',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: '99%',
		height: $TB_HEIGHT,
		singleSelect: true,
		rpOptions: [10, 20, 30, 50,100,200,500]
	
	});
	}
	
	function articaShowEvent(ID){
	YahooWin6('750','$page?ShowID='+ID,'$title::'+ID);
	}
	
	var x_EmptyEvents= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#events-table-$t').flexReload();
	//$('#grid_list').flexOptions({url: 'newurl/'}).flexReload();
	// $('#fgAllPatients').flexOptions({ query: 'blah=qweqweqwe' }).flexReload();
	
	}
	
	function Warn$t(){
	$('#events-table-$t').flexOptions({url: '$page?realtime-table=yes&critical=1'}).flexReload();
	}
			function info$t(){
			$('#events-table-$t').flexOptions({url: '$page?realtime-table=yes&critical=2'}).flexReload();
	}
			function Err$t(){
			$('#events-table-$t').flexOptions({url: '$page?realtime-table=yes&critical=0'}).flexReload();
	}
	function All$t(){
	$('#events-table-$t').flexOptions({url: '$page?realtime-table=yes'}).flexReload();
	}
			function Params$t(){
			Loadjs('squid.proxy.watchdog.php');
	}
	
	function EmptyEvents(){
	if(!confirm('$empty_events_text_ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('empty-table','yes');
	XHR.sendAndLoad('$page', 'POST',x_EmptyEvents);
	}
	setTimeout(\" BuildTable$t()\",800);
	</script>";
	
	echo $html;

}	
function status_search(){
	$tempfile="/usr/share/artica-postfix/ressources/logs/web/squid_redirectors_status.db";
	$sock=new sockets();
	$tpl=new templates();
	$rp=50;

	if(isset($_GET["critical"])){
		if($_GET["critical"]<2){
			$_POST['rp']=2000;
		}
	}

	$rp = $_POST['rp'];
	$sock->getFrameWork("squid.php?redirectors-refresh=yes");
	$tempfile="/usr/share/artica-postfix/ressources/logs/web/squid_redirectors_status.db";
	$array=unserialize(@file_get_contents($tempfile));
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($array);
	$data['rows'] = array();

	if(count($array)==0){json_error_show("no data array");}


	$f=0;
	$c=0;
	while (list ($num, $ligne) = each ($array) ){
		
		
		$c++;
		$color="black";
		$STATES[null]="ok32.png";
		$STATES["B"]="okdanger32.png";
		$STATES["W"]="warning32.png";
		$STATES["C"]="warning32.png";
		$STATES["S"]="ok32-grey.png";
		$STATES["BS"]="32-stop.png";
		$icon=$STATES[trim($ligne["STATE"])];
		
		$ligne["REQ"]=FormatNumber($ligne["REQ"]);
		$ligne["REP"]=FormatNumber($ligne["REP"]);

		

		$data['rows'][] = array(
				'id' => md5($ligne),
				'cell' => array(
						"<span style='color:$color'><img src='img/$icon'></span>",
						"<span style='color:$color;font-size:18px'>{$ligne["PID"]}</span>",
						"<span style='color:$color;font-size:18px'>{$ligne["REQ"]}</span>",
						"<span style='color:$color;font-size:18px'>{$ligne["REP"]}</span>",
						"<span style='color:$color;font-size:18px'>{$ligne["TIME"]}</span>",
						"<span style='color:$color;font-size:18px'>{$ligne["URI"]}</span>",
								)
				);
	}

	if($c==0){json_error_show("no data - $f");}
	$data['total'] = $c;
	echo json_encode($data);





}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}