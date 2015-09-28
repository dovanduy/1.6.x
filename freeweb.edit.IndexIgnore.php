<?php
	session_start();
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.pure-ftpd.inc');
	include_once('ressources/class.apache.inc');
	include_once('ressources/class.freeweb.inc');
	include_once('ressources/class.user.inc');
	include_once('ressources/class.system.network.inc');
	$user=new usersMenus();
	if($user->AsWebMaster==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	
		
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["query"])){Query();exit;}
	if(isset($_POST["IndexIgnore-add"])){IndexIgnore_add();exit;}
	if(isset($_POST["IndexIgnore-del"])){IndexIgnore_del();exit;}
	
	if(isset($_POST["authip-del"])){authip_del();exit;}
	if(isset($_POST["authip-list"])){authip_list();exit;}
	if(isset($_POST["LimitByIp"])){authip_enable();exit;}	
	
	
	js();
	


	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{$_GET["servername"]}:{hide_browsing_items}");	
	$html="YahooWin2('550','$page?popup=yes&servername={$_GET["servername"]}','$title');";
	echo $html;
}	



function popup(){
	$sql="SELECT * FROM freeweb WHERE servername='{$_GET["servername"]}'";
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$sock=new sockets();
	$FreeWebsEnableModSecurity=$sock->GET_INFO("FreeWebsEnableModSecurity");
	$IndexIgnore_explain=$tpl->_ENGINE_parse_body("{IndexIgnore_explain}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$IndexIgnore_howtoadd=$tpl->javascript_parse_text("{IndexIgnore_howtoadd}");
	$t=time();


	$add=$tpl->_ENGINE_parse_body("{add}");
	
	
	

		
		
$buttons="buttons : [
	{name: '$add', bclass: 'Add', onpress : IndexIgnoreAdd},
		],	";


	
echo "
<div class=explain>$IndexIgnore_explain</div>
<table class='$t' style='display: none' id='$t' style='width:99%'></table>
<script>
var MEMMDAUTHIX='';
$(document).ready(function(){
$('#$t').flexigrid({
	url: '$page?query=yes&servername={$_GET["servername"]}',
	dataType: 'json',
	colModel : [
			{display: '$items', name : 'zDate', width : 452, sortable : false, align: 'left'},	
			{display: '&nbsp;', name : 'none2', width : 40, sortable : false, align: 'left'},
		
	],
$buttons
	searchitems : [
		{display: '$items', name : 'address'}
		],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 530,
	height: 300,
	singleSelect: true
	
	});   
});
	

	
		function RefreshIndexIgnore(){
			$('#$t').flexReload();
		}
		
		function enable_ip_authentication_save$t(){
			var XHR = new XHRConnection();
			if(document.getElementById('LimitByIp').checked){XHR.appendData('LimitByIp',1);}else{XHR.appendData('LimitByIp',0);}
			XHR.appendData('servername','{$_GET["servername"]}');
    		XHR.sendAndLoad('$page', 'POST',x_AuthIpAdd$t);
		}
		
		var x_IndexIgnoreAdd$t=function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			RefreshIndexIgnore();			
		}

		var x_IndexIgnoreDel=function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);return;}
			$('#row'+MEMMDAUTHIX).remove();		
		}		
		
		function IndexIgnoreAdd(){
			var ip=prompt('$IndexIgnore_howtoadd');
			if(ip){
				var XHR = new XHRConnection();
				XHR.appendData('IndexIgnore-add',ip);
				XHR.appendData('servername','{$_GET["servername"]}');
				XHR.sendAndLoad('$page', 'POST',x_IndexIgnoreAdd$t);
			}
		}
		
		function IndexIgnoreDel(ip,id){
				MEMMDAUTHIX=id;
				var XHR = new XHRConnection();
				XHR.appendData('IndexIgnore-del',ip);
				XHR.appendData('servername','{$_GET["servername"]}');
				XHR.sendAndLoad('$page', 'POST',x_IndexIgnoreDel);
			
		}			

	</script>
	
	
	
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	}
	
	
function IndexIgnore_add(){
	$freeweb=new freeweb($_POST["servername"]);
	if(trim($_POST["IndexIgnore-add"])==null){return;}
	$freeweb->Params["IndexIgnores"][$_POST["IndexIgnore-add"]]=true;
	$freeweb->SaveParams();
}

function  IndexIgnore_del(){
	$freeweb=new freeweb($_POST["servername"]);
	if(trim($_POST["IndexIgnore-del"])==null){return;}
	unset($freeweb->Params["IndexIgnores"][$_POST["IndexIgnore-del"]]);
	$freeweb->SaveParams();
}

function authip_del(){
	$freeweb=new freeweb($_POST["servername"]);
	$freeweb->LimitByIp_del($_POST["authip-del"]);	
}

function authip_enable(){
	$freeweb=new freeweb($_POST["servername"]);
	$freeweb->Params["LimitByIp"]["enabled"]=$_POST["LimitByIp"];
	$freeweb->SaveParams();
	
}


function Query(){
	$MyPage=CurrentPageName();
	$freeweb=new freeweb($_GET["servername"]);
	$page=CurrentPageName();
	$tpl=new templates();
	$hash=$freeweb->Params["IndexIgnores"];

	
	if($_POST["query"]<>null){
		$_POST["query"]="*{$_POST["query"]}*";
		$_POST["query"]=str_replace(".", "\.", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", ".*?", $_POST["query"]);	
		
	}
	$page=1;
	$COUNT_ROWS=count($hash);
	if($COUNT_ROWS==0){$data['page'] = $page;$data['total'] = 0;$data['rows'] = array();echo json_encode($data);return ;}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	$_POST["query"]=trim($_POST["query"]);
	$total = $COUNT_ROWS;
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	
	while (list ($IndexIgnore, $ligne) = each ($hash) ){
		if($ligne==null){continue;}
		if($_POST["query"]<>null){if(!preg_match("#{$_POST["query"]}#", $IndexIgnore)){continue;}}
		
		
		$added=null;
		$id=md5($IndexIgnore);
		$delete=imgtootltip("delete-24.png","{delete}","IndexIgnoreDel('$IndexIgnore','$id')");
		
	
		
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array(
		"<span style='font-size:18px'>$IndexIgnore</a></span>",
		$delete)
		);
	}
	
	
echo json_encode($data);	
}	
