<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.ssl.certificate.inc');
	if(posix_getuid()==0){die();}
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "<script>alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');</script>";
		die();exit();
	}
	if(isset($_GET["whitelist-list"])){zlist();exit;}
	if(isset($_GET["website_ssl_wl"])){whitelist_add();exit;}
	if(isset($_GET["website_ssl_eble"])){whitelist_enabled();exit;}
	if(isset($_GET["website_ssl_del"])){whitelist_del();exit;}
	
	table();	

function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$new_webiste=$tpl->_ENGINE_parse_body("{new_website}");
	$email=$tpl->_ENGINE_parse_body("{email}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$delete_this_member_ask=$tpl->javascript_parse_text("{delete_this_member_ask}");
	$SSL_BUMP_WL=$tpl->_ENGINE_parse_body("{SSL_BUMP_WL}");
	$website_ssl_wl_help=$tpl->javascript_parse_text("{website_ssl_wl_help}");
	$parameters=$tpl->javascript_parse_text("{parameters}");
	$website_name=$tpl->javascript_parse_text("{websites}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$decrypted_ssl_websites=$tpl->javascript_parse_text("{decrypted_ssl_websites}");
	$squid=new squidbee();
	if($squid->hasProxyTransparent==1){
		$explain=$tpl->_ENGINE_parse_body("<div style='font-weight:bold;color:#BD0000'>{sslbum_wl_not_supported_transp}</div>");
	}

	//$q=new mysql_squid_builder();
	//$q->QUERY_SQL("ALTER TABLE `usersisp` ADD UNIQUE (`email`)");

	$buttons="
	buttons : [
	{name: '<b>$new_webiste</b>', bclass: 'Add', onpress : sslBumbAddwl},
	{name: '<b>$apply</b>', bclass: 'Apply', onpress : Apply$t}
	],";

	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>


	<script>
	row_id='';
	$(document).ready(function(){
	$('#flexRT$t').flexigrid({
	url: '$page?whitelist-list=yes&t=$t',
	dataType: 'json',
	colModel : [
	{display: '$website_name', name : 'website_name', width : 606, sortable : false, align: 'left'},
	{display: '$enabled', name : 'enabled', width : 68, sortable : true, align: 'center'},
	{display: '&nbsp;', name : 'delete', width : 68, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$website_name', name : 'website_name'},
	],
	sortname: 'website_name',
	sortorder: 'desc',
	usepager: true,
	title: '<strong style=font-size:18px>$decrypted_ssl_websites</strong>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '95%',
	height: 310,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
});

var x_sslBumbAddwl$t=function(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	$('#flexRT$t').flexReload();
}

function sslBumbAddwlCheck(e){
	if(checkEnter(e)){sslBumbAddwl();}
}

function sslBumbAddwl(){
var www=prompt('$website_ssl_wl_help');
if(www){
	var XHR = new XHRConnection();
	XHR.appendData('website_ssl_wl',www);
	XHR.sendAndLoad('$page', 'GET',x_sslBumbAddwl$t);
	}
}

var x_sslbumpEnableW=function(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	if(row_id.length>0){ $('#row'+row_id).remove();}
}

function sslbumpEnableW(idname){
	var XHR = new XHRConnection();
	if(document.getElementById(idname).checked){XHR.appendData('enable',1);}else{XHR.appendData('enable',0);}
	XHR.appendData('website_ssl_eble',idname);
	XHR.sendAndLoad('$page', 'GET',x_sslbumpEnableW);
}

function Apply$t(){
	Loadjs('squid.compile.progress.php');
}

function sslbumpAllowSquidSSLDropBox(){
var XHR = new XHRConnection();
if(document.getElementById('AllowSquidSSLDropBox').checked){XHR.appendData('AllowSquidSSLDropBox',1);}else{XHR.appendData('AllowSquidSSLDropBox',0);}
XHR.sendAndLoad('$page', 'POST',x_sslBumbAddwl$t);

}

function sslbumpAllowSquidSSLSkype(){
var XHR = new XHRConnection();
if(document.getElementById('AllowSquidSSLSkype').checked){XHR.appendData('AllowSquidSSLSkype',1);}else{XHR.appendData('AllowSquidSSLSkype',0);}
XHR.sendAndLoad('$page', 'POST',x_sslBumbAddwl$t);
}

function sslBumSettings(){
YahooWin3('550','$page?add-params=yes','$parameters');
}


function sslbumpDeleteW(ID,rowid){
row_id=rowid;
var XHR = new XHRConnection();
XHR.appendData('website_ssl_del',ID);
XHR.sendAndLoad('$page', 'GET',x_sslBumbAddwl$t);
}


</script>

";

	echo $html;
}

function whitelist_del(){
	$sql="DELETE FROM squid_ssl WHERE ID={$_GET["website_ssl_del"]}";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
}

function zlist(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$database="artica_backup";
	$search='%';
	$table="squid_ssl";
	$page=1;
	$sock=new sockets();
	$FORCE_FILTER="AND `type`='ssl-bump-enc'";
	$squid=new squidbee();



	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";


	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,$database);

	

	$data = array();
	$data['page'] = 1;
	$data['total'] = 2;

	$color="black";
	if(!$q->ok){json_error_show($q->mysql_error);}
	
	if(mysql_num_rows($results)==0){json_error_show("no data");}

	
		$data = array();
		$data['page'] = $page;
		$data['total'] = $total;
		while ($ligne = mysql_fetch_assoc($results)) {
			$id=md5(serialize($ligne));
			$color="black";
			$delete="<a href=\"javascript:blur()\" OnClick=\"javascript:sslbumpDeleteW('{$ligne["ID"]}','$id');\"><img src='img/delete-24.png'></a>";
			$enable=Field_checkbox("ENABLE_{$ligne["ID"]}",1,$ligne["enabled"],"sslbumpEnableW('ENABLE_{$ligne["ID"]}')");
			if($ligne["enabled"]==0){$color="#AFAFAF";}
			

				

			$data['rows'][] = array(
					'id' => $id,
					'cell' => array("<span style='font-size:16px;color:$color'>{$ligne["website_name"]}</span>"
					,$enable,$delete )
			);
		}

	


	echo json_encode($data);

}

function whitelist_enabled(){
	if(preg_match("#ENABLE_([0-9]+)#",$_GET["website_ssl_eble"],$re)){
		$sql="UPDATE squid_ssl SET enabled={$_GET["enable"]} WHERE ID={$re[1]}";
		$q=new mysql();
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
	}
}

function whitelist_add(){
	$_GET["website_ssl_wl"]=str_replace("https://","",$_GET["website_ssl_wl"]);
	if(preg_match("#^www\.(.+)#", $_GET["website_ssl_wl"],$re)){$_GET["website_ssl_wl"]=".".$re[1];}
	if(substr($_GET["website_ssl_wl"], 0,1)<>"."){$_GET["website_ssl_wl"]=".".$_GET["website_ssl_wl"];}
	$sql="INSERT INTO squid_ssl(website_name,enabled,`type`) VALUES('{$_GET["website_ssl_wl"]}',1,'ssl-bump-enc');";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	
}