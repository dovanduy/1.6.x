<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.user.inc');
	
	//if(count($_POST)>0)
	$usersmenus=new usersMenus();
	if(!$usersmenus->AllowAddUsers){
		writelogs("Wrong account : no AllowAddUsers privileges",__FUNCTION__,__FILE__);
		if(isset($_GET["js"])){
			$tpl=new templates();
			$error="{ERROR_NO_PRIVS}";
			echo $tpl->_ENGINE_parse_body("alert('$error')");
			die();
		}
		header("location:domains.manage.org.index.php?ou={$_GET["ou"]}");
		}
		
		if(isset($_GET["popup"])){popup();exit;}
		if(isset($_GET["list"])){popup_list();exit;}
		if(isset($_GET["add_already_member_add"])){popup_add();exit;}
		js();
		
function js(){
	$ou_decoded=base64_decode($_GET["ou"]);
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{add_already_member}");
	$members=$tpl->_ENGINE_parse_body("{members}");
	$page=CurrentPageName();
	$html="
		function add_already_member_load(){
			YahooWin2('550','$page?popup=yes&ou={$_GET["ou"]}&gpid={$_GET["gpid"]}&t={$_GET["t"]}','$title');
		
		}
		add_already_member_load();";
		
		
		echo $html;
	
	
}

function popup_add(){
	$uid=base64_decode($_GET["add_already_member_add"]);
	$group=new groups($_GET["gpid"]);
	$group->AddUsertoThisGroup($uid);
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$title=$tpl->_ENGINE_parse_body("{add_already_member}");
	$members=$tpl->_ENGINE_parse_body("{members}");
	$tt=$_GET["t"];
	
	$html="
<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
var memuid$t='';
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?list=yes&ou={$_GET["ou"]}&gpid={$_GET["gpid"]}',
	dataType: 'json',
	colModel : [
		{display: '$members', name : 'date', width : 247, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'GroupType', width : 207, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'items', width : 31, sortable : false, align: 'center'},
		
	],
	searchitems : [
		{display: '$members', name : 'events'},
		],
	sortname: 'GroupName',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: false,
	rp: 100,
	rpOptions: [10, 20, 30, 50,100,200,500],
	showTableToggleBtn: false,
	width: 534,
	height: 300,
	singleSelect: true
	
	});   
});	

	var x_add_already_member_add= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue);return;};
			$('#table-$tt').flexReload();
			$('#rowuidS'+memuid$t).remove();
			
		}		
		
		function add_already_member_add(uid_encoded,md){
		  var XHR = new XHRConnection();
		  memuid$t=md;
	      XHR.appendData('add_already_member_add',uid_encoded);
	      XHR.appendData('gpid','{$_GET["gpid"]}');
	      XHR.appendData('ou','{$_GET["ou"]}');
		  XHR.sendAndLoad('$page', 'GET',x_add_already_member_add);	
		}
	</script>	
	";
	
	echo $html;
}

function popup_list(){
	$ou=base64_decode($_GET["ou"]);
	$tpl=new templates();
	$gpid=$_GET["gpid"];
	if($_POST["query"]<>null){$tofind=$_POST["query"];}	
	
	$groups=new groups($gpid);
	$group_members=$groups->members_array;
	
	$ldap=new clladp();
	if($tofind==null){$tofind='*';}else{$tofind="*$tofind*";}
	$tofind=str_replace("**", "*", $tofind);
	$filter="(&(objectClass=userAccount)(|(cn=$tofind)(mail=$tofind)(displayName=$tofind)(uid=$tofind) (givenname=$tofind)))";
	$attrs=array("displayName","uid","mail","givenname","telephoneNumber","title","sn","mozillaSecondEmail","employeeNumber");
	$dn="ou=$ou,dc=organizations,$ldap->suffix";
	$hash=$ldap->Ldap_search($dn,$filter,$attrs,150);
	if(strlen(trim($ldap->ldap_last_error))>5){json_error_show("Error:".$ldap->ldap_last_error);}

	$number=$hash["count"];
	
	
		
	$data = array();
	$data['page'] = 1;
	$data['total'] = $number;
	$data['rows'] = array();		
	$c=0;
	for($i=0;$i<$number;$i++){
		$exist=false;
		$userARR=$hash[$i];
		$uid=$userARR["uid"][0];
		if($uid==null){continue;}
		$md=md5($uid);
		$c++;
		if($uid=="squidinternalauth"){continue;}
		if($group_members[$uid]){$exist=true;}
		$js="add_already_member_add('".base64_encode($uid)."','$md');";
		$mail=texttooltip($userARR["mail"][0],"{add}:$uid",$js,null,0,"font-size:13px");
		
		
		$color="black";
		$add=imgsimple("arrow-right-24.png","{add}:$uid",$js);
		if($exist){$add="&nbsp;";$color="#8a8a8a";}
		
		$data['rows'][] = array(
				'id' => "uidS$md",
				'cell' => array("<span style='font-size:14px;color:$color'>$uid</span>",
				"<span style='font-size:14px;color:$color'>$mail</span>",
				"<span style='font-size:14px;color:$color'>$add</span>")
				);	
		
	}
	
	

	echo json_encode($data);		
	
	
}



?>