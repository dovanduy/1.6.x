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
	$user=new usersMenus();
	if($user->AsWebMaster==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	
	if(isset($_GET["popup"])){alias_start();exit;}
	if(isset($_GET["freeweb-aliases-list"])){alias_list();exit;}
	if(isset($_POST["Alias"])){alias_save();exit;}
	if(isset($_POST["DelAlias"])){alias_del();exit;}
	if(isset($_POST["AddAlias"])){alias_add();exit;}
	if(isset($_GET["aliases-list"])){aliases_list();exit;}
	
	
	js();	
	
	
function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$server=$_GET["servername"];
	$title=$tpl->_ENGINE_parse_body("{aliases}");
	echo "YahooWin3('650','$page?popup=yes&servername=$server','$server::$title')";
	
	
}

function alias_start(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$html="
	<div id='freeweb-aliasesserver-list' style='width:100%;heigth:350px;overflow:auto'></div>
	<script>
		function FreeWebAliasList(){
			LoadAjax('freeweb-aliasesserver-list','$page?freeweb-aliases-list=yes&servername={$_GET["servername"]}');
		}
	FreeWebAliasList();
	</script>	
	";
	echo $tpl->_ENGINE_parse_body($html);
}
	
	


function alias_del(){
	$free=new freeweb($_POST["servername"]);
	unset($free->Params["ServerAlias"][$_POST["DelAlias"]]);
	$free->SaveParams();	
	
}
function alias_add(){
	$free=new freeweb($_POST["servername"]);
	writelogs("Add ServerAlias {$_POST["AddAlias"]} -> {$_POST["servername"]}",__FUNCTION__,__FILE__,__LINE__);
	$free->Params["ServerAlias"][$_POST["AddAlias"]]=true;
	$free->SaveParams();
}
//{freeweb_aliasserver_explain}

function alias_list(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$t=time();
	$alias=$tpl->_ENGINE_parse_body("{aliases}");
	$new_alias=$tpl->_ENGINE_parse_body("{new_alias}");
	$txt=$tpl->javascript_parse_text("{add_serveralias_ask}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$aliases=$tpl->javascript_parse_text("{aliases}");
	$about2=$tpl->_ENGINE_parse_body("{about2}");
	$about_text=$tpl->javascript_parse_text("{freeweb_aliasserver_explain}");
	$servernameenc=urlencode($_GET["servername"]);
	$buttons="
	buttons : [
	{name: '$new_alias', bclass: 'add', onpress : FreeWebAddServerAlias$t},
	{name: '$about2', bclass: 'help', onpress : About$t},
	],";

	$explain=$tpl->_ENGINE_parse_body("{postfix_transport_senders_explain}");
	$html="
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
<script>
$(document).ready(function(){
	$('#flexRT$t').flexigrid({
	url: '$page?aliases-list=yes&t=$t&servername=$servernameenc',
	dataType: 'json',
	colModel : [
	{display: '$aliases', name : 'domain', width : 507, sortable : true, align: 'left'},
	{display: '$delete;', name : 'delete', width : 70, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$aliases', name : 'domain'},
	],
	sortname: 'domain',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: '350',
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
});

function About$t(){
	alert('$about_text');
}

var x_FreeWebAddServerAlias$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}	
	if(document.getElementById('main_config_freeweb')){RefreshTab('main_config_freeweb');}
	$('#flexRT$t').flexReload();
	WebServerAliasesRefresh();
}	

function FreeWebAddServerAlias$t(){
	var newserv=prompt('$txt');
	if(newserv){
		if(newserv.length<2){return;}
		var XHR = new XHRConnection();
		XHR.appendData('AddAlias',newserv);
		XHR.appendData('servername','{$_GET["servername"]}');
		XHR.sendAndLoad('$page', 'POST',x_FreeWebAddServerAlias$t);
    }			
}	

function FreeWebDelServerAlias$t(id){
	var XHR = new XHRConnection();
	XHR.appendData('DelAlias',id);
	XHR.appendData('servername','{$_GET["servername"]}');
	XHR.sendAndLoad('$page', 'POST',x_FreeWebAddServerAlias$t);			
}

function sender_routing_ruleED$t(domainName){
YahooWin3(552,'postfix.routing.table.php?SenderTable=yes&domainName='+domainName+'&t=$t','$sender_dependent_relayhost_maps_title::'+domainName);
}


function SenderTableDelete$t(domain){
Loadjs('$page?SenderTableDelete-js=yes&domain='+domain+'&t=$t');

}

</script>
";

echo $html;


}

function aliases_list(){
	$tpl=new templates();
	$free=new freeweb($_GET["servername"]);
	$page=CurrentPageName();	
	$t=$_GET["t"];
	if($_POST["query"]<>null){$search=str_replace("*", ".*?", $_POST["query"]);}
	$c=0;
	while (list ($host, $num) = each ($free->Params["ServerAlias"]) ){
		if($search<>null){if(!preg_match("#$search#", $host)){continue;}}
		$c++;
		$delete=imgsimple("delete-48.png","{delete}","FreeWebDelServerAlias$t('{$host}')");
		
		$m5=md5($host);
		$data['rows'][] = array(
				'id' => "dom$m5",
				'cell' => array("
						<span style='font-size:22px;font-weight:bold;'>$host</span>",
						$delete) 
		);
	
		if($c>$_POST["rp"]){break;}
	
	}
	
	if($c==0){json_error_show("no data");}
	$data['page'] = 1;
	$data['total'] = $c;
	echo json_encode($data);
	
}

function alias_list_old(){
	$tpl=new templates();	
	$free=new freeweb($_GET["servername"]);
	$page=CurrentPageName();
	$txt=$tpl->javascript_parse_text("{add_serveralias_ask}");
	$html="<center>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th width=1%>". imgtootltip("plus-24.png","{add}","FreeWebAddServerAlias()")."</th>
		<th width=99%>{alias}</th>
		<th width=1%>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>";	
	
while (list ($host,$num) = each ($free->Params["ServerAlias"]) ){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$delete=imgtootltip("delete-32.png","{delete}","FreeWebDelServerAlias('{$host}')");
		$html=$html."<tr class=$classtr>
		<td width=1%><img src='img/alias-32.gif'></td>
		<td style='font-size:16px;' width=99%>{$host}</td>
		<td width=1%>$delete</td>
		</tr>
		";
	}
	$html=$html."</table>
	<script>
		var x_FreeWebAddServerAlias=function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}	
			if(document.getElementById('main_config_freeweb')){RefreshTab('main_config_freeweb');}
			FreeWebAliasList();	
			WebServerAliasesRefresh();
		}	

		function WebServerAliasesRefresh(){
			if(document.getElementById('main_config_freeweb')){LoadAjaxTiny('webserver-aliases','freeweb.edit.php?webserver-aliases=yes&servername={$_GET["servername"]}');}
		}		
	
		function FreeWebDelServerAlias(id){
			var XHR = new XHRConnection();
			XHR.appendData('DelAlias',id);
			XHR.appendData('servername','{$_GET["servername"]}');
			AnimateDiv('freeweb-aliasesserver-list');
    		XHR.sendAndLoad('$page', 'POST',x_FreeWebAddServerAlias);			
		}

		function FreeWebAddServerAlias(){
			var newserv=prompt('$txt');
			if(newserv){
				if(newserv.length<2){return;}
				var XHR = new XHRConnection();
				XHR.appendData('AddAlias',newserv);
				XHR.appendData('servername','{$_GET["servername"]}');
				AnimateDiv('freeweb-aliasesserver-list');
    			XHR.sendAndLoad('$page', 'POST',x_FreeWebAddServerAlias);
    		}			
		}			
	
	
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}
