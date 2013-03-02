<?php
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.artica.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.ini.inc');
include_once('ressources/class.cyrus.inc');
include_once('ressources/class.cron.inc');

$users=new usersMenus();
if(!$users->AsPostfixAdministrator){
	$tpl=new templates();
	$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	echo "alert('$error')";
	die();
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["tasks-list"])){task_list();exit;}
if(isset($_POST["KillTHREAD"])){task_kill();exit;}

js();


function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{processes_list}");
	$html="YahooWin3('997.6','$page?popup=yes','$title')";
	echo $html;

}


function popup(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$time=$tpl->_ENGINE_parse_body("{time}");
	$task=$tpl->_ENGINE_parse_body("{task}");
	
	$buttons="
	buttons : [
	{name: '<b>$new_member</b>', bclass: 'Add', onpress : NewMemberOU},
	{name: '<b>$manage_groups</b>', bclass: 'Groups', onpress : ManageGroupsOU},$bt_enable
	],";
	$buttons=null;
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	
	
	<script>
	row_id='';
	$(document).ready(function(){
	$('#flexRT$t').flexigrid({
	url: '$page?tasks-list=yes&t=$t',
	dataType: 'json',
	colModel : [
	{display: 'ID', name : 'ID', width : 31, sortable : false, align: 'center'},
	{display: 'USER', name : 'USER', width : 43, sortable : false, align: 'left'},
	{display: 'HOST', name : 'HOST', width : 72, sortable : true, align: 'left'},
	{display: 'COMMAND', name : 'COMMAND', width : 116, sortable : false, align: 'left'},
	{display: 'TIME', name : 'TIME', width : 100, sortable : false, align: 'left'},
	{display: 'STATE', name : 'STATE', width : 100, sortable : false, align: 'left'},
	{display: 'INFO', name : 'INFO', width : 338, sortable : false, align: 'left'},
	{display: 'kill', name : 'kill', width : 31, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: 'COMMAND', name : 'COMMAND'},
	{display: 'USER', name : 'USER'},
	{display: 'HOST', name : 'HOST'},
	{display: 'STATE', name : 'STATE'},
	{display: 'INFO', name : 'INFO'},
	],
	sortname: 'uid',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 958,
	height: 480,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});
	});
	
	var x_sslBumbAddwl$t=function(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	//$('#flexRT$t').flexReload();
	}
	
	function NewMemberOU(){
		Loadjs('domains.add.user.php?ou=$ou&flexRT=$t')
	}
	
	function ManageGroupsOU(){
		Loadjs('domains.edit.group.php?ou=$ou_encoded&js=yes')
	}
	function xStart1$t(){
	if(!RTMMailOpen()){return;}
	if(!document.getElementById('flexRT$t')){return;}
		$('#flexRT$t').flexReload();
		setTimeout('xStart1$t()',5000);
	}
	
	function KillTHREAD(pid){
		if(confirm('Kill PID:'+pid+' ?')){
			var XHR = new XHRConnection();
			XHR.appendData('KillTHREAD',pid);
			XHR.sendAndLoad('$page', 'POST',x_sslBumbAddwl$t);
	
		}
	}
	
	
					setTimeout('xStart1$t()',5000);
	
					</script>
	
					";
	
					echo $html;	
	
}

function task_kill(){
	$sock=new sockets();
	$sock->getFrameWork("zarafa.php?zarafadb-killthread={$_POST["KillTHREAD"]}");
	
}

function task_list(){
	$tpl=new templates();
	$sock=new sockets();
	$ARRAY=unserialize(base64_decode($sock->getFrameWork("zarafa.php?zarafadb-processlist=yes")));

	$data = array();
	$data['page'] = 1;
	$data['total'] = 0;
	$data['rows'] = array();
	if($_POST["query"]<>null){
		$tofind=$_POST["query"];
		$tofind=str_replace(".", "\.", $tofind);
		$tofind=str_replace("[", "\[", $tofind);
		$tofind=str_replace("]", "\]", $tofind);
		$tofind=str_replace("*", ".*?", $tofind);
	}



	
		$c=0;
		$seconds=$tpl->javascript_parse_text("{seconds}");
		while (list ($ID, $ligne) = each ($ARRAY) ){
			$color="black";
			if($tofind<>null){if(!preg_match("#$tofind#", $ligne[$_POST["qtype"]])){continue;}}
			$c++;
			$kill=imgsimple("delete-24.png",null,"KillTHREAD('$ID')");

			$data['rows'][] = array(
					'id' => md5(serialize($ligne)),
					'cell' => array(
							"<span style='font-size:14px;color:$color'>$ID</span>",
							"<span style='font-size:14px;color:$color'>{$ligne["USER"]}</span>",
							"<span style='font-size:14px;color:$color'>{$ligne["HOST"]}</span>",
							"<span style='font-size:14px;color:$color'>{$ligne["COMMAND"]}</span>",
							"<span style='font-size:14px;color:$color'>{$ligne["TIME"]}&nbsp;$seconds</span>",
							"<span style='font-size:14px;color:$color'>{$ligne["STATE"]}</span>",
							"<span style='font-size:14px;color:$color'>{$ligne["INFO"]}</span>",
							"<span style='font-size:14px;color:$color'>$kill</span>",

					)
			);


		}


	
	$data['total'] = $c;
	echo json_encode($data);
}

//