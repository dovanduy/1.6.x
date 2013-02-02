<?php
	$GLOBALS["ICON_FAMILY"]="organizations";
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',1);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mimedefang.inc');	
	include_once('ressources/class.apache.inc');
	include_once('ressources/class.lvm.org.inc');
	include_once('ressources/class.user.inc');


	
	if(!VerifyRights()){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["users-list"])){users_list();exit;}
	page();
function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ldap=new clladp();
	$t=time();
	$new_member=$tpl->_ENGINE_parse_body("{new_member}");
	$email=$tpl->_ENGINE_parse_body("{email}");
	$telephonenumber=$tpl->_ENGINE_parse_body("{phone}");
	$delete_this_member_ask=$tpl->javascript_parse_text("{delete_this_member_ask}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$website_ssl_wl_help=$tpl->javascript_parse_text("{website_ssl_wl_help}");
	$parameters=$tpl->javascript_parse_text("{parameters}");
	$manage_groups=$tpl->javascript_parse_text("{groups2}");
	//$q=new mysql_squid_builder();
	//$q->QUERY_SQL("ALTER TABLE `usersisp` ADD UNIQUE (`email`)");
	if(is_base64_encoded($_GET["ou"])){$ou_encoded=$_GET["ou"];$ou=base64_decode($_GET["ou"]);}else{$ou=$_GET["ou"];$ou_encoded=base64_encode($_GET["ou"]);}
	
		$add_user_disabled=Paragraphe('folder-useradd-64-grey.png','{create_user}','{create_user_text}');
		$add_user=Paragraphe('folder-useradd-64.png','{create_user}','{create_user_text}',"javascript:Loadjs('domains.add.user.php?ou=$ou')",null,210,null,0,true);	
		$groups=Paragraphe('folder-group-64.png','{manage_groups}','{manage_groups_text}',"javascript:Loadjs('domains.edit.group.php?ou=$ou_encoded&js=yes')",null,210,100,0,true);
		$delete_all_users=Paragraphe('member-64-delete.png','{delete_all_users}','{delete_all_users_text}',"javascript:DeleteAllusers()",null,210,100,0,true);	
	
		$bt_add="{name: '<b>$new_member</b>', bclass: 'Add', onpress : NewMemberOU},";
		if($ldap->IsKerbAuth()){$bt_add=null;}
	$buttons="
	buttons : [
	$bt_add
	{name: '<b>$manage_groups</b>', bclass: 'Groups', onpress : ManageGroupsOU},$bt_enable
	],";	
	
	$width=820;
	$height=380;
	$emailTR=252;
	if(isset($_GET["end-user-interface"])){
		$width=936;
		$height=550;
		$emailTR=350;
		$div="<div style='margin-left:-15px'>";
		$devend="</div>";
	}
	
	
	
	
$html="
$div<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>$devend

	
<script>
row_id='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?users-list=yes&ou={$_GET["ou"]}&t=$t&dn={$_GET["dn"]}',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'website_name', width : 31, sortable : false, align: 'left'},
		{display: '$member', name : 'aaa', width : 285, sortable : false, align: 'left'},	
		{display: '$telephonenumber', name : 'enabled', width : 140, sortable : true, align: 'left'},
		{display: '$email', name : 'email', width : $emailTR, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'del', width : 31, sortable : false, align: 'left'},
		],
	$buttons
	searchitems : [
		{display: '$member', name : 'search'},
		],
	sortname: 'uid',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $width,
	height: $height,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

	var x_sslBumbAddwl=function(obj){
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
	
	
	
</script>

";
	
	echo $html;
}

function users_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$ldap=new clladp();
	if($ldap->IsKerbAuth()){users_list_active_directory();return;}
	$database="artica_backup";	
	$search='%';
	$table="squid_ssl";
	$page=1;
	$FORCE_FILTER="AND `type`='ssl-bump-wl'";
	$t=$_GET["t"];
	$sock=new sockets();
	$EnableManageUsersTroughActiveDirectory=$sock->GET_INFO("EnableManageUsersTroughActiveDirectory");
	if(!is_numeric($EnableManageUsersTroughActiveDirectory)){$EnableManageUsersTroughActiveDirectory=0;}
	if(is_base64_encoded($_GET["ou"])){$ou_encoded=$_GET["ou"];$ou=base64_decode($_GET["ou"]);}else{$ou=$_GET["ou"];$ou_encoded=base64_encode($_GET["ou"]);}	
	if($_SESSION["uid"]<>-100){$ou=$_SESSION["ou"];}
	
	if($_POST["query"]<>null){$tofind=$_POST["query"];}
	
	if($tofind==null){$tofind='*';}else{$tofind="*$tofind*";}
	$filter="(&(objectClass=userAccount)(|(cn=$tofind)(mail=$tofind)(displayName=$tofind)(uid=$tofind) (givenname=$tofind)))";
	$attrs=array("displayName","uid","mail","givenname","telephoneNumber","title","sn","mozillaSecondEmail","employeeNumber","sAMAccountName");
		
	if(!$ldap->IsOUUnderActiveDirectory($ou)){
		if($EnableManageUsersTroughActiveDirectory==1){
		$cc=new ldapAD();
		$hash=$cc->find_users($ou,$tofind);
		}else{
			$ldap=new clladp();
			$dn="ou=$ou,dc=organizations,$ldap->suffix";
			$hash=$ldap->Ldap_search($dn,$filter,$attrs,150);
		}
	}else{
		$EnableManageUsersTroughActiveDirectory=1;
		include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
		$ad=new external_ad_search();
		$hash=$ad->find_users($ou, $tofind);
	}
	
	

	$users=new user();
	
	$number=$hash["count"];
	if(!is_numeric($number)){$number=0;}	
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = $number;
	$data['rows'] = array();	
	
	
	for($i=0;$i<$number;$i++){
		$userARR=$hash[$i];
		
		$uid=$userARR["uid"][0];
		if($EnableManageUsersTroughActiveDirectory==1){$uid=$userARR["samaccountname"][0];}
		
		if($uid=="squidinternalauth"){continue;}
		$js=MEMBER_JS($uid,1,1);
		
		if(($userARR["sn"][0]==null) && ($userARR["givenname"][0]==null)){$userARR["sn"][0]=$uid;}
		
		$sn=texttooltip($userARR["sn"][0],"{display}:$uid",$js,null,0,"font-size:13px");
		$givenname=texttooltip($userARR["givenname"][0],"{display}:$uid",$js,null,0,"font-size:13px");
		$title=texttooltip($userARR["title"][0],"{display}:$uid",$js,null,0,"font-size:13px");
		$mail=texttooltip($userARR["mail"][0],"{display}:$uid",$js,null,0,"font-size:13px");
		$telephonenumber=texttooltip($userARR["telephonenumber"][0],"{display}:$uid",$js,null,0,"font-size:13px");
		if($userARR["telephonenumber"][0]==null){$userARR["telephonenumber"][0]="&nbsp;";}
		if($userARR["mail"][0]==null){$userARR["mail"][0]="&nbsp;";}
	
		$img=imgsimple("contact-24.png",null,$js);
		$href="<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='text-decoration:underline'>";
		$dele=imgsimple("delete-24.png",null,"Loadjs('domains.delete.user.php?uid=$uid&flexRT=$t');");
		
		$data['rows'][] = array(
		'id' => $uid,
		'cell' => array(
		$img,
		"<span style='font-size:14px;color:$color'>$href{$userARR["sn"][0]} {$userARR["givenname"][0]}</a><div><i>{$userARR["title"][0]}</i></span>",
		"<span style='font-size:14px;color:$color'>{$userARR["telephonenumber"][0]}</span>",
		"<span style='font-size:14px;color:$color'>$href{$userARR["mail"][0]}</a></span>",
		$dele
		
		)
		);

		
	}	
	

	
echo json_encode($data);		

}

function users_list_active_directory(){
	$database="artica_backup";
	$search='%';
	$table="squid_ssl";
	$page=1;
	$FORCE_FILTER="AND `type`='ssl-bump-wl'";
	$t=$_GET["t"];
	$dn=urldecode($_GET["dn"]);
	$sock=new sockets();
	
	
	if($_POST["query"]<>null){$tofind=$_POST["query"];}
	
	if($tofind==null){$tofind='*';}else{$tofind="*$tofind*";}
	
	if(strpos($dn, ",")>0){$ou=$dn;}
	
	include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
	$ad=new external_ad_search();
	$hash=$ad->find_users($ou, $tofind,$_POST['rp']);
	$number=$hash["count"];
	if(!is_numeric($number)){$number=0;}
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = $number;
	$data['rows'] = array();
	
	
	for($i=0;$i<$number;$i++){
		$userARR=$hash[$i];
		$dn=null;
		$uid=$userARR["uid"][0];
		
		if(isset($userARR["samaccountname"][0])){
			$uid=$userARR["samaccountname"][0];
		}
		
		
		if(isset($userARR["distinguishedname"][0])){
			$dn=$userARR["distinguishedname"][0];
		}
	
		if($uid=="squidinternalauth"){continue;}
		$js=MEMBER_JS($uid,1,1,$dn);
	
		if(($userARR["sn"][0]==null) && ($userARR["givenname"][0]==null)){$userARR["sn"][0]=$uid;}
	
		$sn=$userARR["sn"][0];
		$givenname=$userARR["givenname"][0];
		$title=$userARR["title"][0];
		$mail=$userARR["mail"][0];
		$telephonenumber=$userARR["telephonenumber"][0];
		if($userARR["telephonenumber"][0]==null){$userARR["telephonenumber"][0]="&nbsp;";}
		if($userARR["mail"][0]==null){$userARR["mail"][0]="&nbsp;";}
	
		$img=imgsimple("contact-24.png",null,$js);
		$href="<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='text-decoration:underline'>";
		
		
		$dele="&nbsp;";
	
		$data['rows'][] = array(
				'id' => $uid,
				'cell' => array(
						$img,
						"<span style='font-size:14px;color:$color'>$href{$userARR["sn"][0]} {$userARR["givenname"][0]}</a><div><i>{$userARR["title"][0]}</i></span>",
						"<span style='font-size:14px;color:$color'>{$userARR["telephonenumber"][0]}</span>",
						"<span style='font-size:14px;color:$color'>$href{$userARR["mail"][0]}</a></span>",
						$dele
	
				)
		);
	
	
	}
	
	
	
	echo json_encode($data);
	
	
}

function VerifyRights(){
	$usersmenus=new usersMenus();
	if(!$usersmenus->AsSystemAdministrator){$_GET["ou"]=$_SESSION["ou"];}
	if($usersmenus->AsOrgPostfixAdministrator){return true;}
	if($usersmenus->AsMessagingOrg){return true;}
	if($usersmenus->AsOrgAdmin){return true;}
	if(!$usersmenus->AllowChangeDomains){return false;}
}