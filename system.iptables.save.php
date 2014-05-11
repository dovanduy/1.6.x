<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	
	
	
	$usersmenus=new usersMenus();
	if($usersmenus->AsSystemAdministrator==false){exit();}	

	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["items-rules"])){item_rules();exit;}
	if(isset($_POST["delete-rule"])){item_rules_delete();exit;}
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
	$delete_rule=$tpl->javascript_parse_text("{delete_rule}");
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
		{display: '$rules', name : 'mailfrom', width :645, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'del', width :31, sortable : false, align: 'center'},
	

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
	width: '98%',
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [200,500,1000]
	
	});   
});

var xDelete$t= function (obj) {	
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	UnlockPage();
	$('#flexRT$t').flexReload();			
}		
		
function Delete$t(index){
	if(!confirm('$delete_rule ?')){return;}
	mem$t=index;
	LockPage();
	var XHR = new XHRConnection();
	XHR.appendData('delete-rule',index);
	XHR.sendAndLoad('$page', 'POST',xDelete$t);
}
";	
	echo $html;
}


function item_rules(){
	$t=$_GET["t"];
	$sock=new sockets();
	if($_POST["query"]<>null){
		$search="&search=".base64_encode(string_to_regex($_POST["query"]));
	}
	$datas=unserialize(base64_decode($sock->getFrameWork("services.php?iptables-dump=yes&rp={$_POST["rp"]}$search")));
	if(count($datas)==0){json_error_show("No data");}
	$tpl=new templates();
	$data = array();
	$data['page'] = 1;
	$data['total'] = 0;
	$data['rows'] = array();	
			$c=0;
	while (list($num,$val)=each($datas)){
		if(substr($val, 0,1)=="#"){continue;}
		if(!preg_match("#(^[0-9]+)\s+(.+?)\s+(.+?)\s+(.+?)\s+(.+?)\s+(.+)#", $val,$re)){continue;}
		$index=$re[1];
		$target=$re[2];
		$protocol=$re[3];
		$opt=$re[4];
		$src=$re[5];
		$dest=$re[6];
		$c++;
		if(strpos($dest, "/")>0){
			$tt=explode("/",$dest);
			$dest=$tt[0];
		}
		if(preg_match("#(.+?)\s+tcp(.+)#", $dest,$re)){
			$dest=$re[1];
			$protocol=$re[2];
		}
		
		$protocol=str_replace("dpt:ssh","SSH (22)",$protocol);
		$dest=str_replace("anywhere","{anywhere}",$dest);
		$line=$tpl->_ENGINE_parse_body("$target {from} <strong>$src</strong> {to} $dest {protocol} $protocol");
		$del=imgsimple("delete-24.png",null,"Delete$t('$index')");
	$data['rows'][] = array(

		'id' => "$num",
		'cell' => array(
			"<span style='font-size:12px;color:$color'>[$index]: $line</a></span>",
			"<span style='font-size:12px;color:$color'>$del</a></span>",
			
			)
		);
		
		
	}
	$data['total'] = $c;
	echo json_encode($data);	
	
	
	
	
}

function item_rules_delete(){
	$index=$_POST["delete-rule"];
	$sock=new sockets();
	$sock->getFrameWork("services.php?iptables-delete=$index");
}