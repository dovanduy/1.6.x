<?php

	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once('ressources/class.status.inc');
	if(isset($_GET["org"])){$_GET["ou"]=$_GET["org"];}
	
	if(!PostFixMultiVerifyRights()){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["search"])){search();exit;}
	if(isset($_POST["debug_peer_list"])){debug_peer_list_add();exit;}
	if(isset($_POST["debug_peer_del"])){debug_peer_list_del();exit;}
	
	
	
js();
	
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{$_GET["hostname"]}::{POSTFIX_DEBUG}");
	$html="YahooWin5('560','$page?popup=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}','$title')";
	echo $html;
}


function popup(){
	$hostname=$_GET["hostname"];
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=time();
	
	$delete_database_ask=$tpl->_ENGINE_parse_body("{delete_database_ask}");
	$database=$tpl->_ENGINE_parse_body("{database}");
	$tables_number=$tpl->_ENGINE_parse_body("{tables_number}");
	$POSTFIX_DEBUG_TEXT=$tpl->_ENGINE_parse_body("{POSTFIX_DEBUG_TEXT}");	
	$perfrom_mysqlcheck=$tpl->javascript_parse_text("{perform_mysql_check}");
	$add=$tpl->javascript_parse_text("{add}");	
	$hostnameTitle=$tpl->javascript_parse_text("{hostname}");
	
	$bt_default_www="{name: '$add_default_www', bclass: 'add', onpress : FreeWebAddDefaultVirtualHost},";
	$bt_webdav="{name: '$WebDavPerUser', bclass: 'add', onpress : FreeWebWebDavPerUsers},";
	//$bt_rebuild="{name: '$rebuild_items', bclass: 'Reconf', onpress : RebuildFreeweb},";
	$bt_config=",{name: '$config_file', bclass: 'Search', onpress : config_file}";	
	$tables_size=$tpl->_ENGINE_parse_body("{tables_size}");
	$debug_peer_list_explain=$tpl->javascript_parse_text("{debug_peer_list_explain}");
	$title=$tpl->_ENGINE_parse_body("$mmultiTitle{browse_mysql_server_text}");
	
			

	$buttons="
	buttons : [
		{name: '<b>$add</b>', bclass: 'add', onpress : debug_peer_list_add},
		
	
		],";
	
	$html="
	<div style='margin-left:-10px'>
	<table class='mysql-table-$t' style='display: none' id='mysql-table-$t' style='width:100%;margin:-10px'></table>
	</div>
	<div class=explain style='font-size:14px'>$POSTFIX_DEBUG_TEXT</div>
<script>
sid='';
$(document).ready(function(){
$('#mysql-table-$t').flexigrid({
	url: '$page?search=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$hostnameTitle', name : 'hostname', width : 423, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'none1', width : 31, sortable : false, align: 'left'},
	],
	
	$buttons

	searchitems : [
		{display: '$database', name : 'databasename'},
		
		],
	sortname: 'dbsize',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 500,
	height: 250,
	singleSelect: true
	
	});   
});

	var x_debug_peer_list_add= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert('\"'+results+'\"');}
		$('#mysql-table-$t').flexReload();
	}	

	var x_debug_peer_list_del= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert('\"'+results+'\"');return;}
		$('#row'+sid).remove();
	}	
	
	function debug_peer_list_del(host,id){
		sid=id;
		var XHR = new XHRConnection();
		XHR.appendData('debug_peer_del',host);
		XHR.appendData('hostname','{$_GET["hostname"]}');
		XHR.appendData('ou','{$_GET["ou"]}');
		XHR.sendAndLoad('$page', 'POST',x_debug_peer_list_del);	
	}
	
	function debug_peer_list_add(){
		var ip=prompt('$debug_peer_list_explain');
		if(ip){
			var XHR = new XHRConnection();
			XHR.appendData('debug_peer_list',ip);
			XHR.appendData('hostname','{$_GET["hostname"]}');
			XHR.appendData('ou','{$_GET["ou"]}');
			XHR.sendAndLoad('$page', 'POST',x_debug_peer_list_add);			
		}
	}
</script>
";	
	
echo $html;
	
}

function search(){
	$hostname=$_GET["hostname"];
	$tpl=new templates();
	$page=CurrentPageName();
	$main=new maincf_multi($_GET["hostname"]);
	$datas=unserialize(base64_decode($main->GET_BIGDATA("debug_peer_list")));
	if(count($datas)==0){json_error_show("No data...");}
	$t=$_GET["t"];
	if($_POST["query"]<>null){
		$search=string_to_regex($_POST["query"]);
	}
	
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = $total;
	$data['rows'] = array();	
	$c=0;
	while (list ($num, $ligne) = each ($datas) ){
		if($num==null){continue;}
		if($search<>null){if(!preg_match("#$search#i", $num)){continue;}}
		
		$id=md5($num);
		$delete=imgsimple("delete-32.png","{delete}","debug_peer_list_del('$num','$id')");
		$c++;
		$data['rows'][] = array(
				'id' => $id,
				'cell' => array(
					"<strong style='font-size:18px;style='color:$color'>$href$num</a></strong>",
					$delete
					)
				);			
		

		
	}
	
	$data['total'] = $c;
	echo json_encode($data);
	
}

function debug_peer_list_add(){
	$main=new maincf_multi($_POST["hostname"]);
	$datas=unserialize(base64_decode($main->GET_BIGDATA("debug_peer_list")));
	$datas[$_POST["debug_peer_list"]]=$_POST["debug_peer_list"];
	$newdatas=base64_encode(serialize($datas));
	$main->SET_BIGDATA("debug_peer_list", $newdatas);
	$sock=new sockets();
	$sock->getFrameWork("postfix.php?postfix-debug-peer-list=yes&hostname={$_POST["hostname"]}");
	}
function debug_peer_list_del(){
	$main=new maincf_multi($_POST["hostname"]);
	$datas=unserialize(base64_decode($main->GET_BIGDATA("debug_peer_list")));
	unset($datas[$_POST["debug_peer_del"]]);	
	$newdatas=base64_encode(serialize($datas));
	$main->SET_BIGDATA("debug_peer_list", $newdatas);	
	$sock=new sockets();
	$sock->getFrameWork("postfix.php?postfix-debug-peer-list=yes&hostname={$_POST["hostname"]}");	
}