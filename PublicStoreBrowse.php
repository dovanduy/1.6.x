<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mapi-zarafa.inc');
	

	
	
	$user=new usersMenus();
	if($user->AsSambaAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	if(isset($_GET["SambaAclBrowseFilter"])){SambaAclBrowseFilter();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["query"])){query();exit;}
	
	
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	if(!isset($_GET["OnlyUsers"])){$_GET["OnlyUsers"]=0;}
	if(!isset($_GET["OnlyGroups"])){$_GET["OnlyGroups"]=0;}
	if(!isset($_GET["OnlyGUID"])){$_GET["OnlyGUID"]=0;}
	if(!isset($_GET["NOComputers"])){$_GET["NOComputers"]=0;}
	if(!isset($_GET["Zarafa"])){$_GET["Zarafa"]=0;}
	if(!isset($_GET["OnlyAD"])){$_GET["OnlyAD"]=0;}
	if(isset($_GET["security"])){$_GET["security"]=null;}
	if(!isset($_GET["OnlyName"])){$_GET["OnlyName"]=0;}
	if(!isset($_GET["OnlyCheckAD"])){$_GET["OnlyCheckAD"]=0;}
	if(!isset($_GET["OnlyLDAP"])){$_GET["OnlyLDAP"]=0;}
	
	
	
	$title=$tpl->_ENGINE_parse_body("{browse}::{public_stores}::{$_GET["uid"]}");
	echo "YahooUserHide();YahooUser('534','$page?popup=yes&uid={$_GET["uid"]}&callback={$_GET["callback"]}','$title');";	
	
	
	
}
function popup(){
	if($_GET["callback"]<>null){$callback="{$_GET["callback"]}(id,prependText,guid);YahooUserHide();return;";}	
	$sock=new sockets();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric("$EnableKerbAuth")){$EnableKerbAuth=0;}	
	$page=CurrentPageName();
	$tpl=new templates();	
	
	$t=time();
	$group=$tpl->_ENGINE_parse_body("{group}");
	$type=$tpl->_ENGINE_parse_body("{type}");
	$folders=$tpl->_ENGINE_parse_body("{folders}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$title=null;
	
	$buttons="
	buttons : [
	{name: '$filter', bclass: 'Search', onpress : SambaAclBrowseFilter$t},
	],";

	$buttons=null;
	
$html="
<div style='margin-left:-10px'>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
</div>
<script>
var rowid=0;
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?query=yes&uid={$_GET["uid"]}&callback={$_GET["callback"]}',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'groupname', width : 31, sortable : true, align: 'center'},	
		{display: '$folders', name : 'folders', width :405, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'members', width :31, sortable : false, align: 'left'},
		
		],
	$buttons
	searchitems : [
		{display: '$folders', name : 'folders'},
		
		],
	sortname: 'groupname',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 524,
	height: 350,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});
</script>";	
	
	echo $html;
	
	
}



function query(){
	if($_GET["OnlyUsers"]=="yes"){$_GET["OnlyUsers"]=1;}
	$users=new user();
	$query=$_POST["query"];
	
	if($_POST["qtype"]=="groups"){
		query_group();
		return;
		
	}
	
	$nogetent=false;	
	$uid=$_GET["uid"];
	$OnlyUsers=$_GET["OnlyUsers"];
	$OnlyGroups=$_GET["OnlyGroups"];
	$OnlyGUID=$_GET["OnlyGUID"];
	$OnlyName=$_GET["OnlyName"];
	$OnlyCheckAD=$_GET["OnlyCheckAD"];
	$OnlyLDAP=$_GET["OnlyLDAP"];
	$Zarafa=$_GET["Zarafa"];
	
	if(!is_numeric($OnlyGUID)){$OnlyGUID=0;}
	if(!is_numeric($OnlyUsers)){$OnlyUsers=0;}
	if(!is_numeric($OnlyName)){$OnlyName=0;}
	if(!is_numeric($OnlyCheckAD)){$OnlyCheckAD=0;}	
	if(!is_numeric($OnlyLDAP)){$OnlyLDAP=0;}
	
	if($OnlyLDAP==1){$_GET["OnlyAD"]=0;}
	$ObjectZarafa=false;
	
	if($Zarafa==1){$nogetent=true;$ObjectZarafa=true;}
	$hash=array();
	if(!isset($_GET["prepend"])){$_GET["prepend"]=0;}else{if($_GET["prepend"]=='yes'){$_GET["prepend"]=1;}if($_GET["prepend"]=='no'){$_GET["prepend"]=0;}}
	$WORKGROUP=null;

	$mapi=new mapizarafa();
	
	if(!$mapi->list_folders($_GET["uid"],true)){json_error_show($mapi->error,1);}
	$hash=$mapi->Folders;
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($hash);
	$data['rows'] = array();	
	$c=0;
	
	if($GLOBALS["VERBOSE"]){echo "query():: hash = ".count($hash)." entries...<br\n";}
	
	while (list ($folder, $array) = each ($hash) ){
		$ID=$array["ID"];
		$type=$array["TYPE"];
		$img="icon_mailfolder.gif";
		if($type=="SYNC_FOLDER_TYPE_USER_APPOINTMENT"){$img="icon_calendar.gif";}
		if($type=="SYNC_FOLDER_TYPE_USER_CONTACT"){$img="icon_contact.gif";}
				
		if($_GET["callback"]<>null){$js="{$_GET["callback"]}('$folder','$ID','$uid','$type')";}

		$c++;
		if($c>$_POST["rp"]){break;}
		
		$data['rows'][] = array(
		'id' => md5($folder),
		'cell' => array(
			"<img src='img/$img'>",
			"<span style='font-size:14px;font-weight:bolder'>$folder</span>",
			"<span style='font-size:14px'>".imgsimple("arrow-right-24.png","{add}",$js)."</span>",
			)
		);		
		
	
	}
	$data['total'] = $c;
	echo json_encode($data);	

	
}



