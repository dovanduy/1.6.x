<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once('ressources/class.main_cf_filtering.inc');
	
	if(isset($_GET["org"])){$_GET["ou"]=$_GET["org"];}
	if(isset($_POST["ou"])){$_GET["ou"]=$_POST["ou"];}
	if(isset($_POST["hostname"])){$_GET["hostname"]=$_POST["hostname"];}
	
	if(!PostFixMultiVerifyRights()){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	if(isset($_GET["list"])){servers_list();exit;}

table();

function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$ADD_WHITE_SERVER=$tpl->_ENGINE_parse_body("{ADD_WHITE_SERVER}");
	$hosts=$tpl->_ENGINE_parse_body("{hosts}");
	$addr=$tpl->_ENGINE_parse_body("{addr}");
	$ADD_BAN_SERVER=$tpl->_ENGINE_parse_body("{ADD_BAN_SERVER}");
	$interface=$tpl->_ENGINE_parse_body("{hostname}");
	$title=$tpl->_ENGINE_parse_body("{network}");
	$blockip_msg=$tpl->javascript_parse_text("{blockip_msg}");
	$buttons="
	buttons : [
	{name: '$ADD_WHITE_SERVER', bclass: 'AddServ', onpress : AddWhite$t},
	{name: '$ADD_BAN_SERVER', bclass: 'BanServ', onpress : Add$t},
	
	],";

	$html="


	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	<script>
	$(document).ready(function(){
	var md5H='';
	$('#flexRT$t').flexigrid({
	url: '$page?list=yes&ou={$_GET["ou"]}&hostname={$_GET["hostname"]}&t=$t',
	dataType: 'json',
	colModel : [
	{display: '&nbsp;', name : '&nbsp;', width : 50, sortable : false, align: 'centers'},
	{display: '$interface', name : 'hosts', width : 786, sortable : false, align: 'left'},
	{display: '&nbsp;', name : 'delete', width : 50, sortable : false, align: 'left'},
	],
	$buttons
	
searchitems : [
		{display: '$interface', name : 'host'},
		],	
	
	sortname: 'hostname',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 10,
	showTableToggleBtn: false,
	width: '99%',
	height: 500,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
});

var xAdd$t=function(obj){
	var tempvalue=trim(obj.responseText);
    if(tempvalue.length>3){alert(tempvalue);}
    $('#flexRT$t').flexReload();
}	
	
	
function Add$t(){
	var data=prompt('$blockip_msg')
	if(data){
	  	var XHR = new XHRConnection();
	    XHR.appendData('check_client_access_multi_add',data);
	    XHR.appendData('VALUE','REJECT');
	    XHR.appendData('ou','{$_GET["ou"]}');
	    XHR.appendData('hostname','{$_GET["hostname"]}');
	    XHR.sendAndLoad('domains.postfix.multi.regex.php', 'GET',xAdd$t);
	  }
}

function AddWhite$t(){
	var data=prompt('$blockip_msg');
	if(!data){return;}
	var XHR = new XHRConnection();
	XHR.appendData('check_client_access_multi_add',data);
	XHR.appendData('VALUE','OK');
	XHR.appendData('ou','{$_GET["ou"]}');
	XHR.appendData('hostname','{$_GET["hostname"]}');
	XHR.sendAndLoad('domains.postfix.multi.regex.php', 'GET',xAdd$t);
}

function DeleteServer$t(IP){
	var XHR = new XHRConnection();
	XHR.appendData('check_client_access_del',IP);
	XHR.appendData('ou','{$_GET["ou"]}');
	XHR.appendData('hostname','{$_GET["hostname"]}');
	XHR.sendAndLoad('domains.postfix.multi.regex.php', 'GET',xAdd$t);    
}
</script>

";

echo $tpl->_ENGINE_parse_body($html);
}

function servers_list(){
	$tpl=new templates();
	$main=new maincf_multi($_GET["hostname"],$_GET["ou"]);
	$hash=unserialize(base64_decode($main->GET_BIGDATA("check_client_access")));
	if( !is_array($hash) OR count($hash)==0  ){ json_error_show("no rule");}

	$page=1;

	$search=string_to_flexregex();

	$c=0;
	while (list ($ipaddr, $action) = each ($hash) ){
		if(trim($ipaddr)==null){continue;}
		if(isset($aL[$ipaddr])){continue;}
		$md5=md5("$ipaddr$action");
		if($search<>null){
			if(!preg_match("#$search#", $ipaddr)){continue;}
		}
		$aL[$md5]=true;
		$img="48-server.png";
		if($action=="REJECT"){$img='48-server-ban.png'; }
		

		$c++;
		$delete=imgsimple('delete-48.png','{delete}',"DeleteServer{$_GET["t"]}('$ipaddr');");
		$data['rows'][] = array(
				'id' => $md5,
				'cell' => array(
						"<span style='font-size:16px;font-weight:bold'><img src='img/$img'></a></span>"
						,"<span style='font-size:32px'>$ipaddr ($action)</a></span>",
						$delete )
		);
			
	}
	
	
	$data['page'] = $page;
	$data['total'] = $c;
	echo json_encode($data);
}
