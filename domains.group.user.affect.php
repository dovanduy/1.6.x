<?php
session_start ();
include_once ('ressources/class.templates.inc');
include_once ('ressources/class.ldap.inc');
include_once ('ressources/class.users.menus.inc');
include_once ('ressources/class.user.inc');
include_once ('ressources/class.computers.inc');

$change_aliases = GetRights_aliases();

if($change_aliases<>1){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["group-list"])){popup_list();exit;}
if(isset($_POST["AddMemberGroup"])){AddMemberGroup();exit;}
if(isset($_GET["AddNewComputerGroup"])){AddNewComputerGroup();exit;}
//domains.group.user.affect.php

js();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$add_group=$tpl->_ENGINE_parse_body(base64_decode($_GET["ou"])."::{link_group}::{$_GET["uid"]}");
	$html="
		YahooWin4('545','$page?popup=yes&ou={$_GET["ou"]}&uid={$_GET["uid"]}&t={$_GET["t"]}','$add_group');
	";
		
	echo $html;
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$userid=$_GET["userid"];
	$u=new user($_GET["userid"]);
	$new_entry=$tpl->_ENGINE_parse_body("{new_item}");
	$t=$_GET["t"];
	$tt=time();
	$group2=$tpl->_ENGINE_parse_body("{groups2}");
	$new_volume=$tpl->_ENGINE_parse_body("{new_volume}");
	$workgroup=$tpl->_ENGINE_parse_body("{workgroup}");
	$privileges=$tpl->_ENGINE_parse_body("{privileges}");
	$virtual_servers=$tpl->_ENGINE_parse_body("{virtual_servers}");
	$ou=$tpl->_ENGINE_parse_body("{organization}");
	$link_group=$tpl->_ENGINE_parse_body("{new_group}");
	$sure_delete_smb_vrt=$tpl->javascript_parse_text("{sure_delete_smb_vrt}");
	$parameters=$tpl->_ENGINE_parse_body("{parameters}");
	$ou_encoded=base64_encode($u->ou);
	$title=$tpl->_ENGINE_parse_body("{$_GET["uid"]}::{link_group}");
	$ADD_USER_GROUP_ASK=$tpl->javascript_parse_text("{ADD_USER_GROUP_ASK}");
	$groupname=$tpl->javascript_parse_text("{groupname}");
	
	$buttons="
	buttons : [
	{name: '$link_group', bclass: 'Add', onpress : AddMemberGroup$t},
	],	";
	
	$buttons=null;
	//$('#flexRT$t').flexReload();
	$html="
	<div id='$tt-div'></div>
	<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:99%'></table>
	<script>
	var mem$t='';
	$(document).ready(function(){
		$('#flexRT$tt').flexigrid({
			url: '$page?group-list=yes&tt=$tt&ou={$_GET["ou"]}&uid={$_GET["uid"]}&t={$_GET["t"]}',
			dataType: 'json',
				colModel : [
				{display: '&nbsp;', name : 'hostname', width :53, sortable : false, align: 'center'},
				{display: '$group2', name : 'workgroup', width :431, sortable : true, align: 'left'},
				
				],
				$buttons
			
				searchitems : [
				{display: '$group2', name : 'hostname'},
				],
				sortname: 'hostname',
				sortorder: 'asc',
				usepager: true,
				title: '$title',
				useRp: true,
				rp: 50,
				showTableToggleBtn: false,
				width: 530,
				height: 350,
				singleSelect: true,
				rpOptions: [10, 20, 30, 50,100,200,500]
			
		});
	});
	
	var x_AddPopUpGroupAjaxMode$tt= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		document.getElementById('$tt-div').innerHTML='';
		$('#flexRT$t').flexReload();
	}	
	
	function AddAjaxPopUpGroupV2$tt(ou,uid,gid,gidname){
		var text='$ADD_USER_GROUP_ASK\\n\\nuid:{$_GET["uid"]}\\nGroup:'+gid+'\\nName:'+gidname;
		if(confirm(text)){
				group=gid;
				var XHR = new XHRConnection();
				XHR.appendData('user','{$_GET["uid"]}');
				XHR.appendData('userid','{$_GET["uid"]}');
				XHR.appendData('AddMemberGroup',group);
				AnimateDiv('$tt-div');
				XHR.sendAndLoad('$page', 'POST',x_AddPopUpGroupAjaxMode$tt);
			}
	}
	
		var x_AddNewGroupComp= function (obj) {
			var results=trim(obj.responseText);
			if(results.length>0){alert(results);}
			RefreshGroup();
	
		}

	function AddNewGroupComp(){
		var groupname=prompt('$groupname');
		if(groupname){
			groupname=escape(groupname);
			var XHR = new XHRConnection();
			XHR.appendData('uid','{$_GET["uid"]}');
			XHR.appendData('AddNewComputerGroup',groupname);
			if(document.getElementById('POPUP_MEMBER_GROUP_ID')){document.getElementById('POPUP_MEMBER_GROUP_ID').innerHTML='<center><img src=img/wait_verybig.gif></center>';}
			XHR.sendAndLoad('$page', 'GET',x_AddNewGroupComp);
			
		}
	}
</script>	
";
	echo $tpl->_ENGINE_parse_body($html);	
	
	
	
}


function popup_old() {
	$page=CurrentPageName();
	$tpl=new templates();
	
	
	if(strpos($_GET["uid"],'$')>0){
		$add="
		<div style='text-align:right;margin:8px'>". imgtootltip("32-group-icon-add.png","{add_group}","AddNewGroupComp()")."</div>
		
		";
		
	}
	
	$html = "$add
	<div style='width:100%;height:300px;overflow:auto' id='AddGroupAffectDiv'></div>
	
	
	<script>
		
		


	function RefreshGroup(){
		LoadAjax('AddGroupAffectDiv','$page?popup-list=yes&ou={$_GET["ou"]}&uid={$_GET["uid"]}');
	}
	RefreshGroup();	
	</script>
	
	";
	$tpl = new Templates ( );
	echo $tpl->_ENGINE_parse_body ( $html );

}


function popup_list(){
	$ou=$_GET["ou"];
	$group = new groups ( );
	$ou_con = base64_decode ( $_GET ["ou"] );
	if ($ou_con != null) {$_GET ["ou"] = $ou_con;}
	$hash_group = $group->list_of_groups ( $_GET ["ou"], 1 );
	$hash_group [null] = "{no_group}";
	$uid = $_GET ["uid"];	
	$t=$_GET["tt"];
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($hash_group);
	$data['rows'] = array();
	$search=string_to_flexregex();
		
			

	$c=0;
	while ( list ( $num, $ligne ) = each ( $hash_group ) ) {
		$md5=md5($ligne);
		if($search<>null){if(!preg_match("#$search#i", $ligne)){continue;}}
		$c++;
		$data['rows'][] = array(
				'id' => $md5,
				'cell' => array(
						imgsimple("group-32.png",null,"AddAjaxPopUpGroupV2$t('$ou','$uid',$num,'$ligne')"),
						"<span style='font-size:16px;'>
						<a href=\"javascript:blur();\"
						OnClick=\"javascript:AddAjaxPopUpGroupV2$t('$ou','$uid',$num,'$ligne');\"
						style='font-size:18px;font-weight:bold;text-decoration:underline'>$ligne ($num)</a></span>",

				)
		);
	
	}
	
	$data['total'] = $c;
	echo json_encode($data);	
	
}

function AddMemberGroup() {
	$usr = new usersMenus ( );
	$tpl = new templates ( );
	
	writelogs ( "Adding user {$_POST["user"]} to group {$_POST["AddMemberGroup"]}", __FUNCTION__, __FILE__, __LINE__ );
	
	if ($usr->AllowAddGroup == false) {
		writelogs ( "The administrator have no provileges to execute this operation....", __FUNCTION__, __FILE__, __LINE__ );
		echo $tpl->javascript_parse_text( '{no_privileges}' );
		return;
	}
	
	if (trim ( $_POST["AddMemberGroup"] == null )) {
		echo "Error:AddMemberGroup -> NO value...";
		return;
	}
	$ldap = new clladp ( );
	if(!$ldap->AddUserToGroup ( $_POST ["AddMemberGroup"], $_POST ["user"] )){
		echo "Error:{$_POST["AddMemberGroup"]}->{$_POST["user"]}\n$ldap->ldap_last_error";
		return;
	}

	$tpl = new templates ( );
	echo $tpl->javascript_parse_text ( "{success}: {$_POST["user"]} to group {$_POST["AddMemberGroup"]}" );
	writelogs ( "Adding user {$_POST["user"]} to group {$_POST["AddMemberGroup"]} => SUCCESS", __FUNCTION__, __FILE__, __LINE__ );
	
}


function AddNewComputerGroup(){
	$group = new groups ( );
	if(!$group->add_new_group($_GET["AddNewComputerGroup"])){
		echo $group->ldap_error;
		return;
	}
	
	$gpid=$group->GroupIDFromName(null,$_GET["AddNewComputerGroup"]);
	$group=new groups($gpid);
	$group->TransformGroupToSmbGroup();
	
	
}


?>