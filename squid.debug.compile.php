<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsSquidAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["download"])){download();exit;}
js();


function js(){
	$sock=new sockets();
	$page=CurrentPageName();
	
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{compile_in_debug}");
	echo "YahooWinBrowse('650','$page?popup=yes','$title')";
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$html="<div id='squid-debug-land'><center><img src='img/wait_verybig_mini_red.gif'></center></div>
	
	<script>
		function Refresh$t(){LoadAjax('squid-debug-land','$page?table=yes');}
		setTimeout('Refresh$t()',300);
	</script>
	
	
	";
	echo $html;
}

function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$sock=new sockets();
	$sock->getFrameWork("squid.php?recompile-debug=yes&MyCURLTIMEOUT=600");	
	if(!is_file("ressources/logs/web/squid.indebug.log")){
		echo "<center><img src='img/wait_verybig_mini_red.gif'></center>	
		<script>
			function Refresh$t(){LoadAjax('squid-debug-land','$page?table=yes');}
			setTimeout('Refresh$t()',5000);
		</script>
		";
		return;
		
	}
	
	$rows=$tpl->_ENGINE_parse_body("{rows}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$context=$tpl->_ENGINE_parse_body("{context}");	
	$download=$tpl->_ENGINE_parse_body("{downloadz}");	
	$search=$tpl->_ENGINE_parse_body("{search}");	
	$service=$tpl->_ENGINE_parse_body("{servicew}");
		$TB_WIDTH=872;
		$TB2_WIDTH=610;
	
	$t=time();
	
	$buttons="
	buttons : [
	{name: '$download', bclass: 'Down', onpress : Download$t},
	
		],	";
	
	
	
	$html="
	<table class='events-table-$t' style='display: none' id='events-table-$t' style='width:99%'></table>
<script>
$(document).ready(function(){
$('#events-table-$t').flexigrid({
	url: '$page?search=yes&prepend={$_GET["prepend"]}',
	dataType: 'json',
	colModel : [
		{display: '$rows', name : 'rows', width :602, sortable : true, align: 'left'},

	],
	$buttons

	searchitems : [
		{display: '$search', name : 'search'},
		],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 633,
	height: 600,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});	
	
function Download$t(){
	s_PopUp('$page?download=yes',20,20);
}


</script>
";
	
echo $html;	

}

function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	


	if($_POST["query"]<>null){
		$search=string_to_regex($_POST["query"]);
	}
	
	$array=file("ressources/logs/web/squid.indebug.log");
	ksort($f);
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = count($array);
	$data['rows'] = array();
	
	if($_POST["sortname"]<>null){
		if($_POST["sortorder"]=="desc"){krsort($array);}else{ksort($array);}
	}
	
	$c=0;
	while (list ($key, $line) = each ($array) ){
		if(trim($line)==null){continue;}
			if($search<>null){if(!preg_match("#$search#i", $line)){continue;}}
			$md=md5($line);
		
		$c++;
		$data['rows'][] = array('id' => $md,'cell' => array($line ));
		

	}

	
echo json_encode($data);		

}

function download(){
	$path="/usr/share/artica-postfix/ressources/logs/web/squid.indebug.log";
	$file=basename($path);
	$sock=new sockets();
	
	$content_type=base64_decode($sock->getFrameWork("cmd.php?mime-type=".base64_encode($path)));
	header('Content-type: '.$content_type);
	header('Content-Transfer-Encoding: binary');
	header("Content-Disposition: attachment; filename=\"$file\"");	
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé	
	$fsize = filesize($path); 
	header("Content-Length: ".$fsize); 
	ob_clean();
	flush();
	readfile($path);	
	
}


