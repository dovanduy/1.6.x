<?php
	if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
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
if(isset($_GET["compile-list"])){squid_compile_list();exit;}
js();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$title=$tpl->_ENGINE_parse_body("{compilation_status}");
	$html="YahooWin4('550','$page?popup=yes','$title');";
	echo $html;
}

function popup(){
	header('Content-Type: text/html; charset=utf-8');
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$token=$tpl->_ENGINE_parse_body("{token}");
	$value=$tpl->_ENGINE_parse_body("{value}");
	$tablewidth=530;
	$servername_size=409;
	$t=time();
	
	$html="
	<table class='squid-table-$t' style='display: none' id='squid-table-$t' style='width:100%;margin:-10px'></table>
<script>
FreeWebIDJBB='';
$(document).ready(function(){
$('#squid-table-$t').flexigrid({
	url: '$page?compile-list=yes&t=$t',
	dataType: 'json',
	colModel : [
		
		{display: '$token', name : 'token', width :248, sortable : true, align: 'left'},
		{display: '$value', name : 'value', width :237, sortable : true, align: 'left'},
		
	],
	$buttons

	searchitems : [
		{display: '$token', name : 'token'},
		
		],
	sortname: 'hostname',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: false,
	rp: 50,
	showTableToggleBtn: false,
	width: $tablewidth,
	height: 420,
	singleSelect: true
	
	});   
});
";
	
echo $html;

}




function squid_compile_list(){
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->getFrameWork("squid.php?compile-list=yes")));
	
	
	if(count($array)==0){json_error_js("Compilation list is not an array");}
	
	

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace(".", "\.", $_POST["query"]);
		$_POST["query"]=str_replace("*", ".*?", $_POST["query"]);
		$search=$_POST["query"];
	}
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = $total;
	$data['rows'] = array();
	
	$c=0;
	while (list ($num, $val) = each ($array)){
		$searchR=true;
		if($search<>null){
			if(!preg_match("#$search#i", $num)){$searchR=false;}
			if(preg_match("#$search#i", $val)){$searchR=true;}
		}
		if(!$searchR){continue;}
		$c++;
		$md5S=md5($num);
		if($val==null){$val="&nbsp;";}
		
			$data['rows'][] = array(
				'id' => $md5S,
				'cell' => array(
					"<span style='font-size:15px'>$num</span>","<span style='font-size:15px'>$val</span>"
					)
				);		
		}
	
		
		$data['total'] = $c;
	echo json_encode($data);		
}	
?>


