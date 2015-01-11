<?php
	session_start();
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');


	$user=new usersMenus();
	if($user->AsWebMaster==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["tab"])){tabs();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["EnableWebDavPerUser"])){SaveMainParams();exit;}
	if(isset($_GET["users"])){UsersTable();exit;}
	if(isset($_GET["users-search"])){UsersSearch();exit;}
	
	
	js();

	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{WebDavPerUser}");
	$html="YahooWin3('650','$page?tab=yes','$title');";
	echo $html;
}


function tabs(){
	$tpl=new templates();	
	$page=CurrentPageName();
	$users=new usersMenus();
	$array["popup"]='{status}';
	$array["users"]='{members}';
	$fontsize="style='font-size:14px'";
	
	while (list ($num, $ligne) = each ($array) ){
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes$force_groupware\" $fontsize><span $fontsize>$ligne</span></a></li>\n");
	}
	
	
	echo "
	<div id=main_config_webdavperuser style='width:100%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
		  $(document).ready(function() {
			$(\"#main_config_webdavperuser\").tabs();});
		</script>";		
	
}

function popup(){
	$error=array();
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$EnableWebDavPerUser=$sock->GET_INFO("EnableWebDavPerUser");
	$WebDavPerUserSets=unserialize(base64_decode($sock->GET_INFO("WebDavPerUserSets")));
	$users=new usersMenus();
	if(!$users->MPM_ITK_MODULE){$error[]="MPM itk module";}
	if(!$users->APACHE_MODE_WEBDAV){$error[]="WebDav module";}
	
	if(count($error)>0){
		$html="<table style='width:80%' class=form><tbody><tr><td width=1%><img src='img/error-64.png'><td width=99%>< div style='font-size:16px;color:#9D0000'>{missing_module}!:<br>".@implode("<br>", $error)."</div></td></tr></tbody></table>";
		echo $tpl->_ENGINE_parse_body($html);
		return;
	}
	
	$p=Paragraphe_switch_img("{enable_webdav_per_user}", "{enable_webdav_per_user_explain}","EnableWebDavPerUser",$EnableWebDavPerUser,null,450);
	$t=time();
	$html="
	<div id='$t'>
	$p
	<hr>
	<div class=text-info style='font-size:14px'>{webdav_suffix_domain_explain}</div>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend style='font-size:14px'>{web_suffix_domain}:</td>
		<td>". Field_text("WebDavSuffix",$WebDavPerUserSets["WebDavSuffix"],"font-size:16px;width:350px")."</td>
	</tr>
	<tr>
		<td class=legend>{ssl}:</td>
		<td style='font-size:14px'>". Field_checkbox("EnableSSL", 1,$WebDavPerUserSets["EnableSSL"])."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","EnableWebDavPerUserSave()")."</td>
	</tr>
	</tbody>
	</table>
	</div>
	<script>
	var x_EnableWebDavPerUserSave=function (obj) {
		    RefreshTab('main_config_webdavperuser');
		    if(document.getElementById('main_config_freeweb')){RefreshTab('main_config_freeweb');}
		}	
	
	
	function EnableWebDavPerUserSave(){
		var XHR = new XHRConnection();
		XHR.appendData('EnableWebDavPerUser',document.getElementById('EnableWebDavPerUser').value);
		XHR.appendData('WebDavSuffix',document.getElementById('WebDavSuffix').value);
		if(document.getElementById('EnableSSL').checked){XHR.appendData('EnableSSL',1);}else{XHR.appendData('EnableSSL',0);}
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_EnableWebDavPerUserSave);
	}		
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function SaveMainParams(){
	$sock=new sockets();
	$sock->SET_INFO("EnableWebDavPerUser", $_POST["EnableWebDavPerUser"]);
	$sock->SaveConfigFile(base64_encode(serialize($_POST)), "WebDavPerUserSets");
	$sock->getFrameWork("freeweb.php?users-webdav=yes");	
}


function UsersTable(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$pattern=$tpl->_ENGINE_parse_body("{pattern}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$website=$tpl->_ENGINE_parse_body("{website}");
	$title="{black_ip_group}";
	if($_GET["blk"]==1){$title="{white_ip_group}";}	
	$AddMAC=$tpl->_ENGINE_parse_body("{ComputerMacAddress}");
	$AddWWW=$tpl->_ENGINE_parse_body("{add_website}");
	$squidGroup=$tpl->_ENGINE_parse_body("{SquidGroup}");
	$title=$tpl->_ENGINE_parse_body($title);
	$no_acl_arp_text=$tpl->javascript_parse_text("{no_acl_arp_text}");
	$users=new usersMenus();
	$ComputerMacAddress=$tpl->javascript_parse_text("{ComputerMacAddress}");
	$addr=$tpl->javascript_parse_text("{addr}");
	$squid_ask_domain=$tpl->javascript_parse_text("{squid_ask_domain}");
	$acl_src_text=$tpl->javascript_parse_text("{acl_src_text}");
	
	$buttons="
	buttons : [
	{name: '$AddMAC', bclass: 'add', onpress : AddByMac},
	{name: '$addr', bclass: 'add', onpress : AddByIPAdr},
	{name: '$squidGroup', bclass: 'add', onpress : AddBySquidGroup},
	],";		
		
	if(($_GET["blk"]==2) OR ($_GET["blk"]==3)){
		$buttons="
		buttons : [
		{name: '$AddWWW', bclass: 'add', onpress : AddByWebsite},
		{name: '$squidGroup', bclass: 'add', onpress : AddBySquidGroupWWW},
		],";
	}	
	
$html="
<span id='WebDavTableUsersFindPopupDiv'></span>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?users-search=yes',
	dataType: 'json',
	colModel : [
		{display: '$member', name : 'uid', width : 158, sortable : false, align: 'left'},	
		{display: '$website', name : 'pattern', width :365, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 32, sortable : false, align: 'left'},
		],
	
	searchitems : [
		{display: '$member', name : 'uid'}
		],
	sortname: 'uid',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 600,
	height: 350,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

	var x_AddByMac= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		FlexReloadblk();
		if(document.getElementById('rules-toolbox')){RulesToolBox();}
	}

	function WebDavTableUsersFindPopupDivRefresh(){
		$('#flexRT$t').flexReload();
	}



function AddBySquidGroup(){
	YahooWin5('550','$page?squid-groups=yes&blk={$_GET["blk"]}','$squidGroup');

}






function BlksProxyDelete(pattern){
		var XHR = new XHRConnection();
		XHR.appendData('delete-pattern',pattern);
		XHR.sendAndLoad('$page', 'POST',x_AddByMac);
}

function BlksProxyEnable(pattern,id){
		var XHR = new XHRConnection();
		if(document.getElementById(id).checked){XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
		XHR.appendData('enable-pattern',pattern);
		XHR.sendAndLoad('$page', 'POST');
}

</script>

";	
	echo $html;
	
}
function UsersSearch(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$sock=new sockets();
	$EnableWebDavPerUser=$sock->GET_INFO("EnableWebDavPerUser");
	$WebDavPerUserSets=unserialize(base64_decode($sock->GET_INFO("WebDavPerUserSets")));
	if(!is_numeric($EnableWebDavPerUser)){$EnableWebDavPerUser=0;}
	$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}
	if($EnableFreeWeb==0){if($GLOBALS["VERBOSE"]){echo "FreeWebs is not enabled\n";}return;}
	if($EnableWebDavPerUser==0){if($GLOBALS["VERBOSE"]){echo "EnableWebDavPerUser is not enabled\n";}return;}
	$WebDavSuffix=$WebDavPerUserSets["WebDavSuffix"];
	if($WebDavSuffix==null){if($GLOBALS["VERBOSE"]){echo "WebDavSuffix is not set\n";}return;}
	
	
	$FreeWebListen=$sock->GET_INFO("FreeWebListen");
	$FreeWebListenPort=$sock->GET_INFO("FreeWebListenPort");
	$FreeWebListenSSLPort=$sock->GET_INFO("FreeWebListenSSLPort");
	$FreeWebsDisableSSLv2=$sock->GET_INFO("FreeWebsDisableSSLv2");
	if($FreeWebListen==null){$FreeWebListen="*";}
	if($FreeWebListen<>"*"){$FreeWebListenApache="$FreeWebListen";}	
	if($FreeWebListenSSLPort==null){$FreeWebListenSSLPort=443;}
	if(!is_numeric($FreeWebListenSSLPort)){$FreeWebListenSSLPort=443;}
	if(!is_numeric($FreeWebListenPort)){$FreeWebListenPort=80;}
	if(!is_numeric($FreeWebsDisableSSLv2)){$FreeWebsDisableSSLv2=0;}		
	$port=$FreeWebListen;	
	$SSL=$WebDavPerUserSets["EnableSSL"];
	$prefix="http://";
	if($SSL==1){$prefix="https://";$port=$FreeWebListenSSLPort;};
	
	
	$search='%';
	$table="webdavusers";
	$page=1;
	
	
	if($q->COUNT_ROWS($table,"artica_backup")==0){
		writelogs("$table, no row",__FILE__,__FUNCTION__,__FILE__,__LINE__);
		$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();
		echo json_encode($data);
		return ;
	}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_backup");
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){
		$data['rows'][] = array('id' => $ligne[time()+1],'cell' => array($q->mysql_error,"", "",""));
		$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));
		echo json_encode($data);
		return;
	}	
	
	//if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$id=md5($ligne["uid"]);
		$delete=imgtootltip("delete-24.png","{delete} {$ligne["pattern"]}","BlksProxyDelete('{$ligne["pattern"]}')");
		$uri="$prefix{$ligne["uid"]}.$WebDavSuffix:$port";
		$js=MEMBER_JS($ligne["uid"],1);
		$delete="&nbsp;";
		
	$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => array("<a href=\"javascript:blur();\" OnClick=\"$js\" style='font-size:14px;font-weight:bold;text-decoration:underline'>{$ligne["uid"]}</a>"
		,"<span style='font-size:14px'>$uri</span>",
		$delete )
		);
	}
	
	
echo json_encode($data);		

}