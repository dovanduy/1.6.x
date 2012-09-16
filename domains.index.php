<?php
	$GLOBALS["ICON_FAMILY"]="organizations";
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.active.directory.inc');
	
	

	if(isset($_GET["ShowOrganizations"])){ ShowOrganizations();exit;}
	if(isset($_GET["ajaxmenu"])){echo "<div id='orgs'>".ShowOrganizations()."</div>";exit;}
	if(isset($_GET["butadm"])){echo butadm();exit;}
	if(isset($_GET["LoadOrgPopup"])){echo LoadOrgPopup();exit;}
	if(isset($_GET["js"])){js();exit;}
	if(isset($_GET["js-pop"])){popup();exit;}
	if(isset($_GET["countdeusers"])){COUNT_DE_USERS();exit;}
	if(isset($_GET["inside-tab"])){popup_inside_tabs();exit;}
	
function js(){
	if(GET_CACHED(__FILE__,__FUNCTION__,"js")){return;}
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{organizations}");
	$page=CurrentPageName();
	$html="
	var timeout=0;
	
	function LoadOrg(){
		$('#BodyContent').load('$page?js-pop=yes');
	}
	
	function OrgfillpageButton(){
	var content=document.getElementById('orgs').innerHTML;
	if(content.length<90){
		setTimeout('OrgfillpageButton()',900);
		return;
	}
	
	LoadAjax('butadm','$page?butadm=yes');
	
	}
	
	LoadOrg();
	";
	
	SET_CACHED(__FILE__,__FUNCTION__,"js",$html);
	echo $html;
	
}


function popup_inside_tabs(){
	$page=CurrentPageName();
	$html="<div id='BodyContentInsideTabs'></div>
	
	<script>
	function LoadOrg2(){
		$('#BodyContentInsideTabs').load('$page?js-pop=yes');
	}
		
	function OrgfillpageButton(){
	var content=document.getElementById('orgs').innerHTML;
	if(content.length<90){
		setTimeout('OrgfillpageButton()',900);
		return;
	}
	
	LoadAjax('butadm','$page?butadm=yes');
	
	}
	
	LoadOrg2();	
	</script>
	";
	echo $html;
	
}

function popup(){
	if(GET_CACHED(__FILE__, __FUNCTION__,__FUNCTION__)){return;}
	$users=new usersMenus();	
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	
	$ZarafaField="{display: '&nbsp;', name : 'Zarafa', width :31, sortable : false, align: 'center'},";
	
	if($users->ZARAFA_INSTALLED){
			$ZarafaEnableServer=$sock->GET_INFO("ZarafaEnableServer");
			if(!is_numeric($ZarafaEnableServer)){$ZarafaEnableServer=1;}
				if($ZarafaEnableServer==1){
					if($users->AsMailBoxAdministrator){
						$ZarafaField="{display: 'Zarafa', name : 'Zarafa', width :31, sortable : false, align: 'center'},";
						$ZarafaUri="&zarafaF=1";
					}
				}
			}
			
	
	$EnableManageUsersTroughActiveDirectory=$sock->GET_INFO("EnableManageUsersTroughActiveDirectory");
	if(!is_numeric($EnableManageUsersTroughActiveDirectory)){$EnableManageUsersTroughActiveDirectory=0;}	
	$users=new usersMenus();
	
	if($EnableManageUsersTroughActiveDirectory==1){
		$ldap=new ldapAD();
		$usersnumber=$ldap->COUNT_DE_USERS();
	}else{
		$ldap=new clladp();	
		$usersnumber=$ldap->COUNT_DE_USERS();
		$ldap->ldap_close();
	}
	
	$Totalusers=$tpl->_ENGINE_parse_body("{my_organizations}::<i>{this_server_store}:&nbsp;<strong>$usersnumber</strong>&nbsp;{users}</i>");			
	$organizations_parameters=$tpl->_ENGINE_parse_body("{organizations_parameters}");
	$add_new_organisation=$tpl->_ENGINE_parse_body("{add_new_organisation}");
	$organizations=$tpl->_ENGINE_parse_body("{organizations}");
	$users=$tpl->_ENGINE_parse_body("{users}");
	$groupsF=$tpl->_ENGINE_parse_body("{groupsF}");	
	$domains=$tpl->_ENGINE_parse_body("{domains}");	
	$actions=$tpl->_ENGINE_parse_body("{actions}");	
	$add_new_organisation_text=$tpl->javascript_parse_text("{add_new_organisation_text}");
	$t=time();
	if($users->AsArticaAdministrator){$parametersBT="{name: '<b>$organizations_parameters</b>', bclass: 'Reconf', onpress : organizations_parameters},";}
	if(butadm()<>null){
		
		$jsadd="TreeAddNewOrganisation$t";
	}else{
		$jsadd="nothingtodo";
	}
	
	
	$bb="<input type='hidden' name='add_new_organisation_text' id='add_new_organisation_text' value='". $tpl->javascript_parse_text("{add_new_organisation_text}")."'>";
	if(isset($_GET["ajaxmenu"])){$bc="&ajaxmenu=yes";}
	
	
	$buttons="
	buttons : [
	{name: '<b>$add_new_organisation</b>', bclass: 'add', onpress : $jsadd},$parametersBT
		],";
	$html="
	$bb
	<input type='hidden' name='MAIN_PAGE_ORGANIZATION_LIST' id='MAIN_PAGE_ORGANIZATION_LIST' value='$t'>
	<table class='table-$t' style='display: none' id='table-$t' style='width:100%;margin:-10px'></table>
<script>
OUIDMEM='';
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?ShowOrganizations=yes&t=$t$ZarafaUri$bc',
	dataType: 'json',
	colModel : [
		
		{display: '$organizations', name : 'ou', width :237, sortable : false, align: 'left'},
		$ZarafaField
		{display: '$users', name : 'users', width :57, sortable : false, align: 'center'},
		{display: '$groupsF', name : 'groups', width : 57, sortable : false, align: 'center'},
		{display: '$domains', name : 'domains', width : 57, sortable : false, align: 'center'},		
		{display: '$actions', name : 'actions', width : 72, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'none1', width : 31, sortable : false, align: 'left'},
	],
	$buttons

	searchitems : [
		{display: '$organizations', name : 'ou'},
		],
	sortname: 'ou',
	sortorder: 'desc',
	usepager: true,
	title: '$Totalusers',
	useRp: false,
	rp: 50,
	showTableToggleBtn: false,
	width: 650,
	height: 320,
	singleSelect: true
	
	});   
});

	var x_TreeAddNewOrganisation$t= function (obj) {
		var response=obj.responseText;
		if(response){alert(response);}
		$('#table-$t').flexReload();
	}
	
	function TreeAddNewOrganisation$t(){
		var texte='$add_new_organisation_text'
		var org=prompt(texte,'');
		if(org){
			var XHR = new XHRConnection();
			XHR.appendData('TreeAddNewOrganisation',org);
			XHR.sendAndLoad('domains.php', 'GET',x_TreeAddNewOrganisation$t);
			}
	}

		function organizations_parameters(){
			Loadjs('domains.organizations.parameters.php');
			
		}
		
		function  nothingtodo(){
			alert('$ERROR_NO_PRIVS');
		}

</script>
". $tpl->_ENGINE_parse_body("<div class=explain>{about_organization}</div>");


$tpl=new templates();
$html=$tpl->_ENGINE_parse_body($html);
SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$html);
echo $html;
}

function ShowOrganizations(){
	$usersmenus=new usersMenus();
	if($usersmenus->AsArticaAdministrator==true){ORGANISATIONS_LIST();}else{
		if($usersmenus->AllowAddGroup==true && $usersmenus->AsArticaAdministrator==false){ORGANISTATION_FROM_USER();}
	}
	
	
}

function butadm(){
	$usersmenus=new usersMenus();
	$tpl=new templates();
	$sock=new sockets();
	if($usersmenus->EnableManageUsersTroughActiveDirectory){return null;}	
	if($usersmenus->ARTICA_META_ENABLED){if($sock->GET_INFO("AllowArticaMetaAddUsers")<>1){return null;}}
	if($usersmenus->AsArticaAdministrator==true){return 'ok';}
	return null;
}


function ORGANISTATION_FROM_USER(){
	$ldap=new clladp();
	$hash=$ldap->Hash_Get_ou_from_users($_SESSION["uid"],1);
	$ldap->ldap_close();
	if(!is_array($hash)){return null;}
	return Paragraphe('folder-org-64.jpg',"{manage} &laquo;{$hash[0]}&raquo;","<strong>{$hash[0]}:<br></strong>{manage_organisations_text}",'domains.manage.org.index.php?ou='.$hash[0])."	
	<script>
	OrgfillpageButton();
	</script>";
	
	
}

function ORGANISATIONS_LIST(){
	$Mypage=CurrentPageName();	
	$users=new usersMenus();
	$sock=new sockets();
	$EnableManageUsersTroughActiveDirectory=$sock->GET_INFO("EnableManageUsersTroughActiveDirectory");
	if(!is_numeric($EnableManageUsersTroughActiveDirectory)){$EnableManageUsersTroughActiveDirectory=0;}	
	$AllowInternetUsersCreateOrg=$sock->GET_INFO("AllowInternetUsersCreateOrg");
	if($EnableManageUsersTroughActiveDirectory==1){
		$ldap = new ldapAD();
		$hash=$ldap->hash_get_ou(true);
		
	}else{
		$ldap=new clladp();
		$hash=$ldap->hash_get_ou(true);
	}
	if(!is_array($hash)){json_error_show("No data...");}
	ksort($hash);
	
	if($EnableManageUsersTroughActiveDirectory==0){
		if(!$ldap->BuildOrganizationBranch()){json_error_show("{GENERIC_LDAP_ERROR}<br>$ldap->ldap_last_error");}
	}

	if($_POST["query"]<>null){
		$search=$_POST["query"];
		$search=str_replace(".", "\.", $search);
		$search=str_replace("*", ".*?", $search);
	}

	if(isset($_GET["ajaxmenu"])){$ajax=true;}
	$pic="32-environement.png";
	$style="style='font-size:16px;'";
	$c=0;
	while (list ($num, $ligne) = each ($hash) ){
		$ou=$ligne;
		$ou_encoded=base64_encode($ou);
		if(!preg_match("#$search#i", $ligne)){writelogs("'$ligne' NO MATCH $search",__FUNCTION__,__FILE__,__LINE__);continue;}
		$md=md5(serialize($hash).time());
		$md5S=$md;
		$uri="javascript:Loadjs('domains.manage.org.index.php?js=yes&ou=$ligne');";
		if($ajax){$uri="javascript:Loadjs('$page?LoadOrgPopup=$ligne');";}
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		Paragraphe($img,"{manage} $ligne","<strong>$ligne:$usersNB<br></strong>{manage_organisations_text}",$uri,null);

		if($EnableManageUsersTroughActiveDirectory==0){
			$img=$ldap->get_organization_picture($ligne,32);
			$usersNB=$ldap->CountDeUSerOu($ligne);
			$usersNB="$usersNB";			
		}else{
			$img=$pic;
			$usersNB=$ldap->CountDeUSerOu($ligne);
			$usersNB="$usersNB";
		}
		
		$delete=imgtootltip("delete-32-grey.png","<b>{delete_ou} $ligne</b><br><i>{delete_ou_text}</i>");	
		if($users->AsArticaAdministrator){
			$delete=Paragraphe('64-cancel.png',"{delete_ou} $ligne",'{delete_ou_text}',"javascript:Loadjs('domains.delete.org.php?ou=$ligne');",null,210,100,0,true);
			$delete=imgsimple("delete-32.png","<b>{delete_ou} $ligne</b><br><i>{delete_ou_text}</i>","javascript:Loadjs('domains.delete.org.php?ou=$ligne&t=$t&id-table=$md5S');");
		
		}

		
		
		$DomainsNB=$ldap->CountDeDomainsOU($ligne);
		$GroupsNB=$ldap->CountDeGroups($ou);
		Paragraphe('folder-useradd-64.png','{create_user}','{create_user_text}',"javascript:Loadjs('domains.add.user.php?ou=$ou')",null,210,null,0,true);
		Paragraphe('64-folder-group-add.png','{create_user}','{create_user_text}',"javascript:Loadjs('domains.add.user.php?ou=$ou')",null,210,null,0,true);
		Paragraphe("64-folder-group-add.png","$ou:{add_group}","{add_a_new_group_in_this_org}:<b>$ou</b>","javascript:Loadjs('domains.edit.group.php?popup-add-group=yes&ou=$ou&t=$t')");
		
		
		$select=imgsimple("domain-32.png","{manage_organisations_text}",$uri);
		$adduser=imgsimple("folder-useradd-32.png","$ou<hr><b>{create_user}</b><br><i>{create_user_text}</i>","Loadjs('domains.add.user.php?ou=$ou_encoded&encoded=yes');");
		$addgroup=imgsimple("32-folder-group-add.png","$ou<hr><b>{add_group}</b><br><i>{add_a_new_group_in_this_org}</i>","Loadjs('domains.edit.group.php?popup-add-group=yes&ou=$ou&t=$t');");
		$SearchUser=imgsimple("loupe-32.png","$ou<hr><b>{search}</b>:<i>{members}</i>","Loadjs('domains.find.user.php?ou=$ou_encoded&encoded=yes');");
		$SearchGroup=imgsimple("loupe-32.png","$ou<hr><b>{search}</b>:<i>{groups}</i>","Loadjs('domains.find.groups.php?ou=$ou_encoded&encoded=yes');");
		$searchDomain=imgsimple("loupe-32.png",
		"$ou<hr><b>{localdomains}</b>:<i>{localdomains_text}</i>",
		"Loadjs('domains.edit.domains.php?js=yes&ou=$ou');");

	
				
		$actions="<table style=width:100%;border:0px;><tbody><tr style=background:transparent>
		<td width=1% style=border:0px>$adduser</td><td width=1% style='border:0px'>$addgroup</td></tr></tbody></table>";
		$array=array();
		$array[]="<a href=\"javascript:blur();\" OnClick=\"$uri\" style='font-size:16px;font-weight:bolder;text-transform:capitalize;text-decoration:underline'>$ligne</strong></a>";
		
		if($_GET["zarafaF"]==1){
			$info=$ldap->OUDatas($ou);
			$zarafaEnabled="zarafa-logo-32.png";			
			if(!$info["objectClass"]["zarafa-company"]){$zarafaEnabled="zarafa-logo-32-grey.png";}	
			$array[]=imgsimple($zarafaEnabled,"<b>$ou:{APP_ZARAFA}</b><br>{ZARAFA_OU_ICON_TEXT}","Loadjs('domains.edit.zarafa.php?ou=$ou_encoded')");
		}else{
			$array[]="&nbsp;";
			
		}			
		
		
		$usersNB="<table style=width:100%;border:0px;><tbody><tr style=background:transparent>
		<td width=1% style=border:0px>$usersNB</td><td width=1% style=border:0px>$SearchUser</td></tr></tbody></table>";
		
		$GroupsNB="<table style=width:100%;border:0px;><tbody><tr style=background:transparent>
		<td width=1% style=border:0px>$GroupsNB</td><td width=1% style=border:0px>$SearchGroup</td></tr></tbody></table>";

		$DomainsNB="<table style=width:100%;border:0px;><tbody><tr style=background:transparent>
		<td width=1% style=border:0px>$DomainsNB</td><td width=1% style=border:0px>$searchDomain</td></tr></tbody></table>";		
		
		$array[]="<strong style='font-size:16px'>$usersNB</strong>";
		$array[]="<strong style='font-size:16px'>$GroupsNB</strong>";
		$array[]="<strong style='font-size:16px'>$DomainsNB</strong>";
		$array[]="<strong style='font-size:16px'>$actions</strong>";
		$array[]="<strong style='font-size:16px'>$delete</strong>";
		$c++;
		$data['rows'][] = array('id' => $md5S,'cell' => $array);			
		
		
	}
	
	
	$total =$c;
	$data['page'] = 1;
	$data['total'] = $total;		
echo json_encode($data);	
}

function LoadOrgPopup(){
	echo "
	Loadjs('js/artica_organizations.js');
	Loadjs('js/artica_domains.js');
	YahooWin(750,'domains.manage.org.index.php?org_section=0&SwitchOrgTabs={$_COOKIE["SwitchOrgTabs"]}&ou={$_GET["LoadOrgPopup"]}&ajaxmenu=yes','ORG::{$_GET["LoadOrgPopup"]}');	
	";
}


	