<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.cron.inc');
	include_once('ressources/class.backup.inc');

	


	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["users-list"])){users_list();exit;}
	if(isset($_GET["var-export-js"])){var_export_js();exit;}
	if(isset($_GET["var-export-popup"])){var_export_popup();exit;}
	if(isset($_GET["var-export-tabs"])){var_export_tabs();exit;}
	if(isset($_GET["var-dump-ad-group"])){var_export_popup();exit;}
	if(isset($_GET["var-dump-ad-members"])){var_export_members();exit;}
	if(isset($_POST["field"])){prepare_connection();exit;}
	if(isset($_POST["dnenc"])){add();exit;}
	if(isset($_POST["delete"])){delete();exit;}
	
	
	js();	

function js(){
	$tt=$_GET["t"];
	$tpl=new templates();
	$mdkey=$_GET["mdkey"];
	header("content-type: application/x-javascript");
	if($mdkey==null){
		$error=$tpl->javascript_parse_text("{you_need_to_save_the_form_first}");
		echo "alert('$error')";
		return;
	}
	
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{members}");
	$html="YahooWinBrowse('677','$page?popup=yes&mdkey=$mdkey','$title');";
	
	echo $html;
	
}



function add(){
	$dn=base64_decode($_POST["dnenc"]);
	$uid=trim(base64_decode($_POST["uidenc"]));
	if($uid==null){echo "No such uid...";return;}
	$mdkey=$_POST["mdkey"];
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT servername,params from freeweb_webdav WHERE mdkey='$mdkey'","artica_backup"));
	$servername=$ligne["servername"];
	$Array=unserialize(base64_decode($ligne["params"]));
	$Array["MEMBERS"][$dn]=$uid;
	$NewArray=mysql_escape_string(base64_encode(serialize($Array)));
	$q->QUERY_SQL("UPDATE freeweb_webdav SET `params`='$NewArray' WHERE mdkey='$mdkey'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?freeweb-website=yes&servername=$servername");
}

function delete(){
	$dn=base64_decode(url_decode_special_tool($_POST["delete"]));
	$mdkey=$_POST["mdkey"];
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT servername,params from freeweb_webdav WHERE mdkey='$mdkey'","artica_backup"));
	$servername=$ligne["servername"];
	$Array=unserialize(base64_decode($ligne["params"]));
	unset($Array["MEMBERS"][$dn]);
	$NewArray=mysql_escape_string(base64_encode(serialize($Array)));
	$q->QUERY_SQL("UPDATE freeweb_webdav SET `params`='$NewArray' WHERE mdkey='$mdkey'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?freeweb-website=yes&servername=$servername");
	
}


function popup(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$members=$tpl->javascript_parse_text("{members}");
	
	$q=new mysql();
	$mdkey=$_GET["mdkey"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT params from freeweb_webdav WHERE mdkey='$mdkey'","artica_backup"));
	$Array=unserialize(base64_decode($ligne["params"]));
	unset($Array["MEMBERS"]);
	$ArrayEnc=urlencode(base64_encode(serialize($Array)));
	$LDAP_SUFFIX=$tpl->javascript_parse_text($Array["LDAP_SUFFIX"]);
	$add_member=$tpl->javascript_parse_text("{add_member}");
	$memberssearch="{display: '$members', name : 'members'},";
	
	
	
	$buttons="
	buttons : [
	{name: '<b>$add_member</b>', bclass: 'add', onpress : AddMemberXY$t},
	
	],";	
	
	$html="
	
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?users-list=yes&t=$t&mdkey=$mdkey',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'zDate', width :31, sortable : false, align: 'left'},
		{display: '$members', name : 'members', width : 528, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 31, sortable : false, align: 'left'},
		],
		$buttons
		
	searchitems : [
		
		$memberssearch
		
		],
	sortname: 'members',
	sortorder: 'desc',
	usepager: true,
	useRp: true,
	title: '{$members}:$LDAP_SUFFIX',
	rp: 50,
	showTableToggleBtn: false,
	width: 652,
	height: 420,
	singleSelect: true,
	rpOptions: [50,100,200,500,1000]
	
	});   
});

	function AddMemberXY$t(){
		Loadjs('BrowseActiveDirectoryGeneric.php?ConnectionEnc=$ArrayEnc&CallBack=XAddMember$t&OnlyUsers=1&OnlyGroups=0');
	}
	
	
	var YAddMember$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		$('#flexRT$t').flexReload();
	}
	
	function XAddMember$t(dnenc,uidenc){
			var XHR = new XHRConnection();
			if(dnenc.length==0){alert('No crypted DN!');return;}
			if(uidenc.length==0){alert('No crypted Uid!');return;}
			XHR.appendData('dnenc',dnenc);
			XHR.appendData('uidenc', uidenc);
			XHR.appendData('mdkey', '{$_GET["mdkey"]}');
			XHR.sendAndLoad('$page', 'POST',YAddMember$t);	
	
	}
	function zDelete$t(dnenc){
		var XHR = new XHRConnection();
		XHR.appendData('delete', encodeURIComponent(dnenc));
		XHR.appendData('mdkey', '{$_GET["mdkey"]}');
		XHR.sendAndLoad('$page', 'POST',YAddMember$t);
	}	
	
	
</script>
	
	
	";
	
	echo $html;
	

	
	
}

function users_list(){
	$tpl=new templates();
	$CurPage=CurrentPageName();
	$search=$_POST["query"];
	
	$t=$_GET["t"];


	$icon="user7-32.png";
	
	$mdkey=$_GET["mdkey"];
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT params from freeweb_webdav WHERE mdkey='$mdkey'","artica_backup"));
	$Array=unserialize(base64_decode($ligne["params"]));	
	
	
	
	if(count($Array["MEMBERS"])==0){json_error_show("No item",1);}
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($Array);
	$data['rows'] = array();	
	$members=$tpl->_ENGINE_parse_body("{members}");
	
	$search=string_to_flexregex();
	$c=0;
	while (list ($dn, $itemname) = each ($Array["MEMBERS"]) ){
		if($dn==null){continue;}
		$GroupxSourceName=$itemname;
		$GroupxName=$tpl->javascript_parse_text($GroupxSourceName);
		$link="<span style='font-size:14px;'>";
		$dn_enc=base64_encode($dn);
		$image=imgsimple($icon);
		$delete=imgsimple("delete-32.png",null,"zDelete$t('$dn_enc')");
		$md5=md5($dn);
		if($search<>null){
			if(!preg_match("#$search#", $GroupxName)){continue;}
		}
		$c++;
		$dnT=$tpl->javascript_parse_text($dn);

		$data['rows'][] = array(
			'id' => $md5,
			'cell' => array(
				$image,
				"<span style='font-size:14px;font-weight:bold'>$GroupxName</span><div><i>$dnT</i>",
				$delete )
			);		
	}
	
	$data['total'] = $c;
	echo json_encode($data);	
}


//BrowseActiveDirectory.php