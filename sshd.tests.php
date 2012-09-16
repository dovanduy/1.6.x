<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.openssh.inc');
	include_once('ressources/class.user.inc');

	$user=new usersMenus();
	if($user->AsSystemAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["ssh-connect"])){ssh_connect_list();exit;}
	if(isset($_POST["hostname"])){ssh_connect_prepare();exit;}
	
js();	

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{test_connection}");
	$uid=$_GET["uid"];
	echo "YahooWin3('570','$page?popup=yes&uid=$uid','$title&raquo;$uid')";
	}

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$t=time();
	$uid=$_GET["uid"];
	$events=$tpl->_ENGINE_parse_body("{events}");
	$connect=$tpl->_ENGINE_parse_body("{connect}");
	$title=$tpl->_ENGINE_parse_body("{test_connection}&raquo;$uid");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$buttons="
	buttons : [
	{name: '$connect', bclass: 'Copy', onpress : Connect$t},
	
		],	";	
	
	$html="
	
	<table class='table-$t' style='display: none' id='table-$t' style='width:100%;margin:-10px'></table>
<script>
FreeWebIDMEM='';
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?ssh-connect=yes&uid=$uid',
	dataType: 'json',
	colModel : [
		{display: 'events', name : 'success', width : 506, sortable : true, align: 'left'},
		
	],
	$buttons

	searchitems : [
		{display: '$events', name : 'events'},
		
		],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: false,
	rp: 50,
	showTableToggleBtn: false,
	width: 540,
	height: 350,
	singleSelect: true
	
	});   
});		


		var x_Connect$t= function (obj) {
			var tempvalue=obj.responseText;
			$('#table-$t').flexReload();
		 }		
	
	
		function Connect$t(){
			var XHR = new XHRConnection();
			var server=prompt('$hostname ?');
			if(server){
				XHR.appendData('uid','$uid');
				XHR.appendData('hostname',server);
				XHR.sendAndLoad('$page', 'POST',x_Connect$t);
			}
		}
</script>";
echo $html;
}	
	
function ssh_connect_list(){
	$uid=$_GET["uid"];
	if(!is_file("ressources/logs/web/$uid.ssh")){json_error_show("No lines...");}
	
	$f=file("ressources/logs/web/$uid.ssh");
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = 0;
	$data['rows'] = array();

	if($_POST["sortorder"]=="desc"){krsort($f);}
	if($_POST["query"]<>null){
		$search=$_POST["query"];
		$search=str_replace(".", "\.",$search);
		$search=str_replace("*", ".*?",$search);
	}
	$c=0;
	while (list ($num, $ligne) = each ($f) ){
		$c++;
		if(!preg_match("#$search#", $ligne)){continue;}
		if(preg_match("#Bytes per second#",$ligne)){ $ligne="<strong style='color:blue'>$ligne</strong>";}
		if(preg_match("#Transferred: sent#",$ligne)){ $ligne="<strong style='color:blue'>$ligne</strong>";}
		$data['rows'][] = array(
		'id' => md5($ligne),
		'cell' => array(
		"$ligne",
		
		)
		);
	}
	$data['total']=$c;
	echo json_encode($data);

}

function ssh_connect_prepare(){
	$sock=new sockets();
	$sock->getFrameWork("services.php?ssh-test={$_POST["hostname"]}&uid={$_POST["uid"]}");
	
}
