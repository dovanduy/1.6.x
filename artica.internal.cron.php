<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.os.system.inc');
	
	$users=new usersMenus();
	if(!$users->AsArticaAdministrator){die();}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["cron-list"])){cron_list();exit;}
js();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{internal_scheduler}");
	$html="YahooWin4('880','$page?popup=yes','$title')";
	echo $html;
}




function popup(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$tasks=$tpl->_ENGINE_parse_body("{tasks}");
	$type=$tpl->_ENGINE_parse_body("{type}");
	
$html="
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?cron-list=yes',
	dataType: 'json',
	colModel : [
		{display: '$type', name : 'tasks', width :34, sortable : false, align: 'left'},
		{display: '$tasks', name : 'tasks', width :790, sortable : false, align: 'left'},
		],
	searchitems : [
		{display: '$tasks', name : 'tasks'},
		],	

	sortname: 'tasks',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: false,
	rp: 50,
	showTableToggleBtn: false,
	width: 868,
	height: 418,
	singleSelect: true
	
	});   
});
</script>

";	
	echo $tpl->_ENGINE_parse_body($html);

}

function cron_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$sock=new sockets();
	$page=1;
	
	$sock=new sockets();
	$table=unserialize(base64_decode($sock->getFrameWork("services.php?artica-cron-tasks=yes")));
	
	while (list ($num, $ligne) = each ($table["system"]) ){
		$t[]=array("CMD"=>$ligne,"TASK"=>"system");
	}
	while (list ($num, $ligne) = each ($table["watchdog"]) ){
		$t[]=array("CMD"=>$ligne,"TASK"=>"watchdog");
	}	
	
	//print_r($table);
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace(".", "\.", $_POST["query"]);
		$_POST["query"]=str_replace("/", "\/", $_POST["query"]);
		
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", ".*", $_POST["query"]);
		$search=$_POST["query"];
		
		
	}else{
		$total = count($t);
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = count($t);
	$data['rows'] = array();
	
	
	
	$c=0;
	while (list ($num, $ligne) = each ($t) ){
		$md5=md5(@implode(" ", $ligne));
		
		if($search<>null){
			if(!preg_match("#$search#", $ligne["CMD"])){
				continue;
			}
		}
		$c++;
	$data['rows'][] = array(
		'id' => $md5,
		'cell' => array(
		"<span style='font-size:11px'>$linkwebsite{$ligne["TASK"]}</a></span>"
		,"<span style='font-size:11px'>$linkfamily{$ligne["CMD"]}</a></span>",
		)
		);
	}
	$data['total'] = $c;
	
echo json_encode($data);		

}
	

