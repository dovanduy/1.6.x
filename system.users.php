<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.main_cf.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.cron.inc');
	include_once('ressources/class.backup.inc');

$user=new usersMenus();
	if($user->AsSystemAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["popup-list"])){popup_list();exit;}
	if(isset($_POST["UnixMemberDel"])){UnixMemberDel();exit;}
js();


function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sources=$tpl->_ENGINE_parse_body("{system_users}");	
	$html="YahooWin('666','$page?popup=yees','$sources');";
	echo $html;
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$member=$tpl->_ENGINE_parse_body("{member}");
	$advanced_options=$tpl->_ENGINE_parse_body("{advanced_options}");
	$mysql_instance=$tpl->_ENGINE_parse_body("{mysql_instance}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$new_user=$tpl->_ENGINE_parse_body("{add_user}");
	$delete_user=$tpl->javascript_parse_text("{delete_user}");
		
	$t=time();

$buttons="buttons : [
		{name: '$new_user', bclass: 'add', onpress : add_user$t},
		{separator: true},
		
		
		],	";	
	
	$html="
	<table class='$t-table-list' style='display: none' id='$t-table-list' style='width:99%'></table>
	
<script>
var BackupSRCMem$t='';
$(document).ready(function(){
$('#$t-table-list').flexigrid({
	url: '$page?popup-list=yes&ID=$ID&t=$t',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'none', width : 31, sortable : false, align: 'center'},
		{display: '$member', name : 'none', width : 405, sortable : false, align: 'left'},
		{display: 'uid', name : 'source', width :51, sortable : true, align: 'left'},
		{display: 'uid', name : 'source', width :51, sortable : true, align: 'left'},
		{display: '$delete', name : 'source', width :31, sortable : true, align: 'center'},
		
	],
$buttons
	searchitems : [
		{display: '$member', name : 'member'},		
		],
	sortname: 'ip_address',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: false,
	rp: 15,
	showTableToggleBtn: true,
	width: 650,
	height: 300,
	singleSelect: true
	
	});   
});

function add_user$t(){
	YahooWin2('550','sshd.php?add-system-user-popup=yes&t=$t','$new_user');
}

var x_UnixMemberDel= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		if(document.getElementById('row'+BackupSRCMem$t)){
			$('#row'+BackupSRCMem$t).remove();
		}else{
			$('#$t-table-list').flexReload();
		}
	 }	
 
		
		function UnixMemberDel(member){
			BackupSRCMem$t=member
			if(confirm('$delete_user ? '+member)){
				var XHR = new XHRConnection();
				XHR.appendData('UnixMemberDel',member);
				XHR.sendAndLoad('$page', 'POST',x_UnixMemberDel);
			}
		}


</script>
	
	
	";
	echo $html;
}

function popup_list(){
	include_once('ressources/class.freeweb.inc');
	$MyPage=CurrentPageName();
	$tpl=new templates();		
	$data = array();
	$data['page'] = 1;
	$data['total'] = 0;
	$data['rows'] = array();
	$t=$_GET["t"];	
	
	$sock=new sockets();
	$ressources=unserialize(base64_decode($sock->getFrameWork("services.php?system-users=yes")));
	
	if($_POST["query"]<>null){
		$search=$_POST["query"];
		$search=str_replace(".", "\.", $search);
		$search=str_replace("*", ".*?", $search);
		
	}
	
	
	$c=0;
	while (list ($member, $array) = each ($ressources) ){
			if(trim($member)==null){continue;}
			if($search<>null){if(!preg_match("#$search#", $member)){continue;}}
			$uid=$array["UID"];
			$gid=$array["GID"];
			$desc=$array["DESC"];
			
			$delete=imgsimple("delete-24.png","","UnixMemberDel('$member')");
			if($member=="root"){$delete="&nbsp;";}
			
			$c++;
			$data['rows'][] = array(
					'id' => $member,
					'cell' => array(
					"<img src='img/user-24.png' style='margin-top:5px'>",
					"<STRONG style='font-size:14px'>$member</STRONG><div><i style='font-size:12px'>$desc</i></div>",
					"<code style='font-size:14px;font-weight:bold'>$uid</code>",
					"<code style='font-size:14px;font-weight:bold'>$gid</code>",
					"<div style='font-size:14px;font-weight:bold;margin-top:5px'>$delete</div>",
					)
					);			
			
			
			
			
		}
	
	
	$data['total'] = $c;
	echo json_encode($data);	
	
}

function UnixMemberDel(){
	
	$sock=new sockets();
	$sock->getFrameWork("services.php?delete-system-user={$_POST["UnixMemberDel"]}");
	
}
	
	
	
