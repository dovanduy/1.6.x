<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	
	
	
	$usersmenus=new usersMenus();
	if($usersmenus->AsSystemAdministrator==false){exit();}	

	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["items-rules"])){item_rules();exit;}
js();

function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$title="Firwall&nbsp;&raquo;{current_rules}";
	$title=$tpl->_ENGINE_parse_body($title);
	echo "YahooWin3('750','$page?popup=yes','$title')";
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=450;
	$TB_WIDTH=720;
	
	
	$t=time();
	$rules=$tpl->_ENGINE_parse_body("{rules}");
	$from=$tpl->_ENGINE_parse_body("{sender}");
	$to=$tpl->_ENGINE_parse_body("{recipients}");
	$title=$tpl->_ENGINE_parse_body("{rules}:&nbsp;&laquo;{current_rules}&raquo;");
	$rules=$tpl->_ENGINE_parse_body("{rules}");
	$ask_delete_rule=$tpl->javascript_parse_text("{delete_this_rule}");
	$enable=$tpl->_ENGINE_parse_body("{enable}");
	$compile_rules=$tpl->_ENGINE_parse_body("{compile_rules}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	$buttons="
	buttons : [
	{name: '$new_entry', bclass: 'Add', onpress : NewGItem$t},
	{name: '$compile_rules', bclass: 'Reconf', onpress : AmavisCompileRules},
	{name: '$online_help', bclass: 'Help', onpress : ItemHelp$t},
	],	";
	
	$buttons=null;
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?items-rules=yes&t=$t',
	dataType: 'json',
	colModel : [	
		{display: '$rules', name : 'mailfrom', width :684, sortable : true, align: 'left'},
	

	],
	$buttons

	searchitems : [
		{display: '$rules', name : 'rules'},
		

	],
	sortname: 'mailfrom',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});";	
	echo $html;
}


function item_rules(){
	
	$sock=new sockets();
	if($_POST["query"]<>null){
		$search="&search=".base64_encode(string_to_regex($_POST["query"]));
	}
	$datas=unserialize(base64_decode($sock->getFrameWork("services.php?iptables-save=yes&rp={$_POST["rp"]}$search")));
	if(count($datas)==0){json_error_show("No data");}
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = 0;
	$data['rows'] = array();	
			$c=0;
	while (list($num,$val)=each($datas)){
		if(substr($val, 0,1)=="#"){continue;}
		
		$c++;
	$data['rows'][] = array(

		'id' => "$num",
		'cell' => array(
			"<span style='font-size:12px;color:$color'>{$val}</a></span>",
			
			)
		);
		
		
	}
	$data['total'] = $c;
	echo json_encode($data);	
	
	
	
	
}