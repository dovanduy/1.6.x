<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.samba.inc');
	include_once('ressources/class.acls.inc');
	include_once('ressources/class.mysql.inc');
	if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);$GLOBALS["VERBOSE"]=true;}

	
	
	$user=new usersMenus();
	if($user->AsSambaAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["tabs"])){popup_tabs();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["popup-acls"])){popup_acls();exit;}
	if(isset($_GET["main"])){popup_main();exit;}
	if(isset($_GET["sharedlist"])){shared_folders_list();exit;}
	if(isset($_GET["acldisks"])){acldisks();exit;}
	if(isset($_GET["aclline"])){aclsave();exit;}
	if(isset($_GET["SearchUser"])){SearchUser();exit;}
	if(isset($_GET["SearchPattern"])){list_users();exit;}
	if(isset($_GET["SambaAclBrowseFilter"])){SambaAclBrowseFilter();exit;}
	
	if(isset($_GET["DeleteAllAcls"])){DeleteAllAcls();exit;}
	
	if(isset($_GET["AddAclUser"])){AddAclUser();exit;}
	if(isset($_GET["DeleteAclUser"])){DeleteAclUser();exit;}
	if(isset($_GET["ChangeAclUser"])){ChangeAclUser();exit;}
	
	if(isset($_GET["AddAclGroup"])){AddAclGroup();exit;}
	if(isset($_GET["DeleteAclGroup"])){DeleteAclGroup();exit;}
	if(isset($_GET["ChangeAclGroup"])){ChangeAclGroup();exit;}
	if(isset($_GET["set-recursive"])){SubitemsMode();exit;}
	
	if(isset($_GET["chmod_return_only"])){chmod_return_only();exit;}
	if(isset($_GET["chmod_save"])){chmod_save();exit;}
	if(isset($_GET["config"])){dir_status();exit;}
	
	
js();
//fstablist

function js(){
	$tpl=new templates();
	$folder_decrypted=base64_decode($_GET["path"]);
	$title=$tpl->_ENGINE_parse_body("{ACLS}::$folder_decrypted","samba.index.php");
	$members=$tpl->_ENGINE_parse_body("{members}::$folder_decrypted","samba.index.php");
	$page=CurrentPageName();
	$html="
		function acls_folders_start(){
			YahooWin6('550','$page?tabs=yes&path={$_GET["path"]}','$title');
		}
		
		function AclAddUser(){
			YahooSearchUser('530','$page?SearchUser=yes','$members');
		}	
		


		
		function SearchUserPress(e){
			if(checkEnter(e)){SearchUserPerform();}
		}
		
	   var x_addacl=function (obj) {
			results=obj.responseText;
			if(results.length>0){alert(results);}
			RefreshTab('main_config_acl_dir');
	    }	

	   var x_changeacl=function (obj) {
	   		tempvalue=obj.responseText;
			document.getElementById('acls_log').innerHTML=tempvalue;
		}
		
		function DeleteAllacls(){
			var XHR = new XHRConnection();
			XHR.appendData('DeleteAllAcls','yes');
			XHR.appendData('path','{$_GET["path"]}');
			document.getElementById('MAIN_CLS_INFOS').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>'; 
			XHR.sendAndLoad('$page', 'GET',x_addacl);			
		}
		
		
		function AddAclGroup(groupname){
			var XHR = new XHRConnection();
			XHR.appendData('AddAclGroup',groupname);
			XHR.appendData('path','{$_GET["path"]}');
				
			document.getElementById('MAIN_CLS_INFOS').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>'; 
			XHR.sendAndLoad('$page', 'GET',x_addacl);
		}
		
		function AddAclUser(username){
			var XHR = new XHRConnection();
			XHR.appendData('AddAclUser',username);
			XHR.appendData('path','{$_GET["path"]}');
			if(document.getElementById('recursive')){
				if(document.getElementById('recursive').checked){XHR.appendData('recursive','1');}else{XHR.appendData('recursive','0');}
				if(document.getElementById('default').checked){XHR.appendData('default','1');}else{XHR.appendData('default','0');}
			}					
			document.getElementById('MAIN_CLS_INFOS').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>'; 
			XHR.sendAndLoad('$page', 'GET',x_addacl);
		}
		
		
		
		function DeleteAclGroup(groupname){
			var XHR = new XHRConnection();
			XHR.appendData('DeleteAclGroup',groupname);
			XHR.appendData('path','{$_GET["path"]}');
			if(document.getElementById('recursive')){
				if(document.getElementById('recursive').checked){XHR.appendData('recursive','1');}else{XHR.appendData('recursive','0');}
				if(document.getElementById('default').checked){XHR.appendData('default','1');}else{XHR.appendData('default','0');}
			}					
			document.getElementById('MAIN_CLS_INFOS').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>'; 
			XHR.sendAndLoad('$page', 'GET',x_addacl);
		}
		
		function DeleteAclUser(username){
			var XHR = new XHRConnection();
			XHR.appendData('DeleteAclUser',username);
			XHR.appendData('path','{$_GET["path"]}');
			if(document.getElementById('recursive')){
				if(document.getElementById('recursive').checked){XHR.appendData('recursive','1');}else{XHR.appendData('recursive','0');}
				if(document.getElementById('default').checked){XHR.appendData('default','1');}else{XHR.appendData('default','0');}
			}					
			document.getElementById('MAIN_CLS_INFOS').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>'; 
			XHR.sendAndLoad('$page', 'GET',x_addacl);
		}		
		
		
		function ChangeAclUser(username,mod,id){
			var XHR = new XHRConnection();
			if(document.getElementById(id).checked){XHR.appendData('value','1');}else{XHR.appendData('value','0');}
			if(document.getElementById('recursive')){
				if(document.getElementById('recursive').checked){XHR.appendData('recursive','1');}else{XHR.appendData('recursive','0');}
				if(document.getElementById('default').checked){XHR.appendData('default','1');}else{XHR.appendData('default','0');}
			}				
			XHR.appendData('mode',mod);
			XHR.appendData('ChangeAclUser',username);
			XHR.appendData('path','{$_GET["path"]}');
			XHR.sendAndLoad('$page', 'GET');			
			}	

		function AclChangeSubitems(){
			var XHR = new XHRConnection();
			XHR.appendData('path','{$_GET["path"]}');
			if(document.getElementById('acl_recursive').checked){XHR.appendData('set-recursive','1');}else{XHR.appendData('set-recursive','0');}
			if(document.getElementById('acl_default').checked){XHR.appendData('set-default','1');}else{XHR.appendData('set-default','0');}			
			XHR.sendAndLoad('$page', 'GET',x_changeacl);
		}
		
		
		function ChangeAclGroup(groupname,mod,id){
			var XHR = new XHRConnection();
			if(document.getElementById(id).checked){XHR.appendData('value','1');}else{XHR.appendData('value','0');}
			XHR.appendData('mode',mod);
			XHR.appendData('ChangeAclGroup',groupname);
			XHR.appendData('path','{$_GET["path"]}');
			XHR.sendAndLoad('$page', 'GET');
			}
		
		function RefreshAclTable(){
			LoadAjax('MAIN_CLS_INFOS','$page?main=yes&path={$_GET["path"]}');
		}
	
	acls_folders_start();";
	
echo $html;	
	
}

function SubitemsMode(){
	$acls=new aclsdirs(base64_decode($_GET["path"]));
	$acls->acls_array["recursive"]=$_GET["set-recursive"];
	$acls->acls_array["default"]=$_GET["set-default"];
	$acls->SaveAcls();	
	

}

function AddAclGroup(){
	$uid=base64_decode($_GET["AddAclGroup"]);
	$PathACL=base64_decode($_GET["path"]);
	$samba=new samba();
	$FOLDER=$samba->main_shared_folders[$PathACL];

	
	writelogs("ACLS:... Add new ACL for group \"$uid\" path=$PathACL Shared name=\"$FOLDER\"",__FUNCTION__,__FILE__,__LINE__);
	if($FOLDER<>null){
		$item=$uid;
		if(strpos($item, " ")>0){$item="@\"$item\"";}else{$item="@$item";}
		$h=$samba->hash_privileges($FOLDER);
		
		$write=$h[$item]["write list"];
		writelogs("ACLS:...  $item Write list = $write",__FUNCTION__,__FILE__,__LINE__);
		if($write<>"yes"){
			$h[$item]["write list"]='yes';
			reset($h);
			while (list ($user, $array) = each ($h) ){if(trim($user)==null){continue;}while (list ($priv, $n) = each ($array) ){$a[$priv][]=$user;}}
			if(is_array($a)){while (list ($c, $d) = each ($a) ){$samba->main_array[$FOLDER][$c]=implode(',',$d);}$samba->SaveToLdap();}
			
		}
	
	}
	

	
	$acls=new aclsdirs(base64_decode($_GET["path"]));
	if(!isset($acls->acls_array["GROUPS"][$uid])){$acls->acls_array["GROUPS"][$uid]=array();}
	$acls->SaveAcls();	
	
	
		
}
function DeleteAllAcls(){
	$acls=new aclsdirs(base64_decode($_GET["path"]));
	$acls->DeleteAllacls();
	
}
function AddAclUser(){
	$uid=base64_decode($_GET["AddAclUser"]);
	$acls=new aclsdirs(base64_decode($_GET["path"]));
	if(!isset($acls->acls_array["MEMBERS"][$uid])){$acls->acls_array["MEMBERS"][$uid]=array();}
	$acls->SaveAcls();

}
	
function DeleteAclGroup(){
	$path=base64_decode($_GET["path"]);	
	$uid=$_GET["DeleteAclGroup"];
	$acls=new aclsdirs(base64_decode($_GET["path"]));
	unset($acls->acls_array["GROUPS"][$uid]);
	$acls->SaveAcls();	
}
function DeleteAclUser(){
	$path=base64_decode($_GET["path"]);	
	$uid=$_GET["DeleteAclUser"];
	$acls=new aclsdirs(base64_decode($_GET["path"]));
	unset($acls->acls_array["MEMBERS"][$uid]);
	$acls->SaveAcls();	
	
	
		
}


function ChangeAclGroup(){
	$uid=$_GET["ChangeAclGroup"];
	$path=base64_decode($_GET["path"]);
	$acls=new aclsdirs(base64_decode($_GET["path"]));
	$acls->acls_array["GROUPS"][$uid][$_GET["mode"]]=$_GET["value"];
	$acls->SaveAcls();
	
}
function ChangeAclUser(){
	
	$uid=$_GET["ChangeAclUser"];
	$path=base64_decode($_GET["path"]);
	$acls=new aclsdirs(base64_decode($_GET["path"]));
	$acls->acls_array["MEMBERS"][$uid][$_GET["mode"]]=$_GET["value"];
	$acls->SaveAcls();	

}
	

function popup(){

	$html="
	<div id='acls_log'></div>
	<div id='MAIN_CLS_INFOS'></div>
	<script>
		RefreshAclTable();
	</script>
	";
	
	echo $html;
	
}

function popup_tabs(){
	$path=$_GET["path"];
	
	$page=CurrentPageName();
	$tpl=new templates();
	$array["popup"]='Unix {permissions}';
	$array["popup-acls"]='ACL {permissions}';
	$array["config"]='{status}';
	
		
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&path=$path\"><span style='font-size:14px'>$ligne</span></a></li>\n");
	}
	
	
	echo "
	<div id=main_config_acl_dir style='width:100%;height:550px;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_acl_dir').tabs();
				});
		</script>";		
	
	
}

function chmod_return_only(){
	
		unset($_GET["chmod_return_only"]);
		while (list ($num, $ligne) = each ($_GET) ){
			if($ligne==0){$_GET["$num"]="-";}
			
		}
		reset($_GET);
		if($_GET["chmod_owner_read"]==1){$_GET["chmod_owner_read"]="r";}
		if($_GET["chmod_owner_write"]==1){$_GET["chmod_owner_write"]="w";}
		if($_GET["chmod_owner_execute"]==1){$_GET["chmod_owner_execute"]="x";}
		
		if($_GET["chmod_group_read"]==1){$_GET["chmod_group_read"]="r";}
		if($_GET["chmod_group_write"]==1){$_GET["chmod_group_write"]="w";}	
		if($_GET["chmod_group_execute"]==1){$_GET["chmod_group_execute"]="x";}

		if($_GET["chmod_public_read"]==1){$_GET["chmod_public_read"]="r";}	
		if($_GET["chmod_public_write"]==1){$_GET["chmod_public_write"]="w";}	
		if($_GET["chmod_public_execute"]==1){$_GET["chmod_public_execute"]="x";}	

		$acl=new aclsdirs();
		
		
		$wxstr="{$_GET["chmod_owner_read"]}{$_GET["chmod_owner_write"]}{$_GET["chmod_owner_execute"]}";
		$wxstr=$wxstr."{$_GET["chmod_group_read"]}{$_GET["chmod_group_write"]}{$_GET["chmod_group_execute"]}";
		$wxstr=$wxstr."{$_GET["chmod_public_read"]}{$_GET["chmod_public_write"]}{$_GET["chmod_public_execute"]}";
		
		
		echo "$wxstr&nbsp;|&nbsp;".$acl->ModeRWX2Octal($wxstr);
	
}

function chmod_save(){
	$_GET["path"]=base64_decode($_GET["path"]);
	$acl=new aclsdirs($_GET["path"]);
	$acl->SaveAclsChmod($_GET);
	
	
		while (list ($num, $ligne) = each ($_GET) ){
		
			if($ligne==0){$_GET["$num"]="-";}
			
		}	
		
		reset($_GET);
		if($_GET["chmod_owner_read"]==1){$_GET["chmod_owner_read"]="r";}
		if($_GET["chmod_owner_write"]==1){$_GET["chmod_owner_write"]="w";}
		if($_GET["chmod_owner_execute"]==1){$_GET["chmod_owner_execute"]="x";}
		
		if($_GET["chmod_group_read"]==1){$_GET["chmod_group_read"]="r";}
		if($_GET["chmod_group_write"]==1){$_GET["chmod_group_write"]="w";}	
		if($_GET["chmod_group_execute"]==1){$_GET["chmod_group_execute"]="x";}

		if($_GET["chmod_public_read"]==1){$_GET["chmod_public_read"]="r";}	
		if($_GET["chmod_public_write"]==1){$_GET["chmod_public_write"]="w";}	
		if($_GET["chmod_public_execute"]==1){$_GET["chmod_public_execute"]="x";}
		
		$wxstr="{$_GET["chmod_owner_read"]}{$_GET["chmod_owner_write"]}{$_GET["chmod_owner_execute"]}";
		$wxstr=$wxstr."{$_GET["chmod_group_read"]}{$_GET["chmod_group_write"]}{$_GET["chmod_group_execute"]}";
		$wxstr=$wxstr."{$_GET["chmod_public_read"]}{$_GET["chmod_public_write"]}{$_GET["chmod_public_execute"]}";
	
}

function popup_acls(){
	$path=$_GET["path"];
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$aclsClass=new aclsdirs(base64_decode($path));
	$aclsTests=unserialize(base64_decode($sock->getFrameWork("cmd.php?path-acls=$path")));
	
	if($aclsTests[0]=="NO_SUCH_DIR"){
		$error_acl="<div class=explain style='font-size:14px'>{acls_no_such_dir_text}</div>";
		echo $tpl->_ENGINE_parse_body($error_acl);
		return;
		
	}
	
	if(!is_array($aclsTests)){
			$error_acl="<div class=explain style='font-size:14px'>{acls_get_error}</div>";
		
	}
//AclChangeSubitems
	$group_img="img/wingroup.png";
	$user_img="img/user-18.png";
	
	$html="
	
	<div id='MAIN_CLS_INFOS'>$error_acl</div>
<div style='font-size:14px;margin-bottom:10px'>
<center>
<table style='width:99%' class=form>
<tr>
<td style='font-size:14px;' class=legend nowrap>{acls}:</td>
		<td width=99%><code style='font-size:12px'>$aclsClass->directory</code></td>
		<td class=legend>{recursive}:</td>
		<td width=1%>". Field_checkbox("acl_recursive",1,$aclsClass->acls_array["recursive"],"AclChangeSubitems()")."</td> 
		<td class=legend>{default}:</td>
		<td width=1%>". Field_checkbox("acl_default",1,$aclsClass->acls_array["default"],"AclChangeSubitems()")."</td>		
</tr>
</table>
</center>
</div>	
<div style='width:98%' class=form>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th width=1%>". imgtootltip("plus-24.png","{add_member}","AclAddUser()")."</th>
		<th width=25%>{members}</th>
		<th width=25% align=center style='text-align:center'>{read}</th>
		<th width=25% align=center style='text-align:center'>{write}</th>
		<th width=25% align=center style='text-align:center'>{execute}</th>
		<th width=1%>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>

";	
	
	
	$group_img="img/wingroup.png";
	$user_img="img/user-18.png";	
if(is_array($aclsClass->acls_array["GROUPS"])){
	while (list ($groupname, $array) = each ($aclsClass->acls_array["GROUPS"]) ){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$delete=imgtootltip("delete-24.png","{delete}","DeleteAclGroup('$groupname')");
		$id=md5("G:$groupname");
		$html=$html."
		<tr class=$classtr>
		<td width=1%><img src='$group_img'></td>
		<td style='font-size:13px;font-weight:bold' nowrap>$groupname</td>	
		<td align=center>". Field_checkbox("$id-r",1,$array["r"],"ChangeAclGroup('$groupname','r','$id-r')")."</td>
		<td align=center>". Field_checkbox("$id-w",1,$array["w"],"ChangeAclGroup('$groupname','w','$id-w')")."</td>
		<td align=center>". Field_checkbox("$id-x",1,$array["x"],"ChangeAclGroup('$groupname','x','$id-x')")."</td>
		<td width=1%>$delete</td>
		</tr>
		
		";
		
	}
	
	
}	
if(is_array($aclsClass->acls_array["MEMBERS"])){
	while (list ($member, $array) = each ($aclsClass->acls_array["MEMBERS"]) ){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$delete=imgtootltip("delete-24.png","{delete}","DeleteAclUser('$member')");
		$id=md5("U:$member");
		$html=$html."
		<tr class=$classtr>
		<td width=1%><img src='$user_img'></td>
		<td style='font-size:13px;font-weight:bold' nowrap>$member</td>	
		<td align=center>". Field_checkbox("$id-r",1,$array["r"],"ChangeAclUser('$member','r','$id-r')")."</td>
		<td align=center>". Field_checkbox("$id-w",1,$array["w"],"ChangeAclUser('$member','w','$id-w')")."</td>
		<td align=center>". Field_checkbox("$id-x",1,$array["x"],"ChangeAclUser('$member','x','$id-x')")."</td>
		<td width=1%>$delete</td>
		</tr>
		
		";
		
	}
	
	
}	
	$html=$html."</table>
	
	<div style='text-align:right;margin-top:15px'>". imgtootltip("delete-32.png","{delete_all}","DeleteAllacls()")."<div>
	</div>
	";
echo $tpl->_ENGINE_parse_body($html);	
	
	
}


function popup_main(){	
	$path=$_GET["path"];
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$aclsClass=new aclsdirs(base64_decode($path));
	$acls=unserialize(base64_decode($sock->getFrameWork("cmd.php?path-acls=$path")));
	if(!is_array($acls)){
		
		$error_acl="<div class=explain>{acls_get_error}</div>";
		
	}

	$group_img="img/wingroup.png";
	$user_img="img/user-18.png";
	
	$html="$error_acl
<div style='font-size:14px;margin-bottom:10px'>{unix_permissions} <span id='chmod-octal' style='font-size:14px;font-weight:bold'>$aclsClass->chmod_octal&nbsp;|&nbsp;$aclsClass->chmod_strings</span><span style='font-size:14px;font-weight:bold'>&nbsp;|&nbsp;$aclsClass->chmod_owner</div>



<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th width=25%>{members}</th>
		<th width=25% align=center style='text-align:center'>{read}</th>
		<th width=25% align=center style='text-align:center'>{write}</th>
		<th width=25% align=center style='text-align:center'>{execute}</th>
	</tr>
</thead>
<tbody class='tbody'>
<tr class=oddRow>
	<td width=1% style='font-size:14px' nowrap>{owner}:</td>
	<td align=center>". Field_checkbox("chmod_owner_read",1,$aclsClass->chmod_owner_read,"ChangeOctalMOde(1)")."</td>
	<td align=center>". Field_checkbox("chmod_owner_write",1,$aclsClass->chmod_owner_write,"ChangeOctalMOde(1)")."</td>
	<td align=center>". Field_checkbox("chmod_owner_execute",1,$aclsClass->chmod_owner_execute,"ChangeOctalMOde(1)")."</td>
</tr>
<tr>
	<td width=1% style='font-size:14px'>{group}:</td>
	<td align=center>". Field_checkbox("chmod_group_read",1,$aclsClass->chmod_group_read,"ChangeOctalMOde(1)")."</td>
	<td align=center>". Field_checkbox("chmod_group_write",1,$aclsClass->chmod_group_write,"ChangeOctalMOde(1)")."</td>
	<td align=center>". Field_checkbox("chmod_group_execute",1,$aclsClass->chmod_group_execute,"ChangeOctalMOde(1)")."</td>
</tr>
<tr class=oddRow>
	<td width=1% style='font-size:14px'>{public}:</td>
	<td align=center>". Field_checkbox("chmod_public_read",1,$aclsClass->chmod_public_read,"ChangeOctalMOde(1)")."</td>
	<td align=center>". Field_checkbox("chmod_public_write",1,$aclsClass->chmod_public_write,"ChangeOctalMOde(1)")."</td>
	<td align=center>". Field_checkbox("chmod_public_execute",1,$aclsClass->chmod_public_execute,"ChangeOctalMOde(1)")."</td>
</tr>
<tr>
	<td colspan=4 align='right'>
		<table style='border:0px'>
		<tr>
			<td class=legend style='border:0px'>{recursive}:</td>
			<td width=1% style='border:0px'>". Field_checkbox("chmod_recursive",1,$aclsClass->chmod_recursive)."</td>
			<td width=1% style='border:0px'>". button("{apply}","ChangeOctalMOde(0)")."</td>
		</tr>
		</table>
	</td>
</tr>
</table>
";		
	
$script_chmod="

var x_ChmodreturnedOnly= function (obj) {
		var results=trim(obj.responseText);
		if(results.length>0){document.getElementById('chmod-octal').innerHTML=results;}
		
	}	

	
var x_SaveChmod= function (obj) {
		var results=trim(obj.responseText);
		if(results.length>0){alert(results);}
		RefreshTab('main_config_acl_dir');
	}	

	function ChangeOctalMOde(returnedOnly){
		var XHR = new XHRConnection();
		if(document.getElementById('chmod_owner_read').checked){
			XHR.appendData('chmod_owner_read',1);}else{XHR.appendData('chmod_owner_read',0);}
		
		if(document.getElementById('chmod_owner_write').checked){
			XHR.appendData('chmod_owner_write',1);}else{XHR.appendData('chmod_owner_write',0);}

		if(document.getElementById('chmod_owner_execute').checked){
			XHR.appendData('chmod_owner_execute',1);}else{XHR.appendData('chmod_owner_execute',0);}				
			
		if(document.getElementById('chmod_group_read').checked){
			XHR.appendData('chmod_group_read',1);}else{XHR.appendData('chmod_group_read',0);}				
	
		if(document.getElementById('chmod_group_write').checked){
			XHR.appendData('chmod_group_write',1);}else{XHR.appendData('chmod_group_write',0);}				
	
		if(document.getElementById('chmod_group_execute').checked){
			XHR.appendData('chmod_group_execute',1);}else{XHR.appendData('chmod_group_execute',0);}				
	
		if(document.getElementById('chmod_public_read').checked){
			XHR.appendData('chmod_public_read',1);}else{XHR.appendData('chmod_public_read',0);}				
	
						
		if(document.getElementById('chmod_public_write').checked){
			XHR.appendData('chmod_public_write',1);}else{XHR.appendData('chmod_public_write',0);}				
	
		if(document.getElementById('chmod_public_execute').checked){
			XHR.appendData('chmod_public_execute',1);}else{XHR.appendData('chmod_public_execute',0);}				
		
		if(	returnedOnly==1){
			XHR.appendData('chmod_return_only',1);
			XHR.sendAndLoad('$page', 'GET',x_ChmodreturnedOnly);
			return;
		}
		
		if(document.getElementById('chmod_recursive').checked){
			XHR.appendData('chmod_recursive',1);}else{XHR.appendData('chmod_recursive',0);}		
		
		XHR.appendData('chmod_save',1);
		document.getElementById('MAIN_CLS_INFOS').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>'; 
		XHR.appendData('path','{$_GET["path"]}');
		XHR.sendAndLoad('$page', 'GET',x_SaveChmod);
	}


";

	

		
		
	
	$html="$html
	<script>
	$script_chmod
	</script>
	
	";
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function exploderights($pattern){
	for($i=0;$i<strlen($pattern);$i++){
		$array[$pattern[$i]]=1;
	}
	
	return $array;
}


function SearchUser(){
	
	if(isset($_SESSION["SambaAclBrowseFilter"]["acls_comps"])){$_SESSION["SambaAclBrowseFilter"]["acls_comps"]=0;}
	if(isset($_SESSION["SambaAclBrowseFilter"]["acls_gps"])){$_SESSION["SambaAclBrowseFilter"]["acls_gps"]=1;}
	if(isset($_SESSION["SambaAclBrowseFilter"]["acls_users"])){$_SESSION["SambaAclBrowseFilter"]["acls_users"]=1;}	
	$_SESSION["SambaAclBrowseFilter"]["acls_onlyad"]=$_GET["acls_onlyad"];
	if(!is_numeric($_SESSION["SambaAclBrowseFilter"]["acls_onlyad"])){$_SESSION["SambaAclBrowseFilter"]["acls_onlyad"]=0;}	
	
	
	$sock=new sockets();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric("$EnableKerbAuth")){$EnableKerbAuth=0;}	
	$page=CurrentPageName();
	$tpl=new templates();	
	$dansguardian2_members_groups_explain=$tpl->_ENGINE_parse_body("{dansguardian2_members_groups_explain}");
	$t=time();
	$group=$tpl->_ENGINE_parse_body("{group}");
	$type=$tpl->_ENGINE_parse_body("{type}");
	$members=$tpl->_ENGINE_parse_body("{members}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$do_you_want_to_delete_this_group=$tpl->javascript_parse_text("{do_you_want_to_delete_this_group}");
	$new_group=$tpl->_ENGINE_parse_body("{new_group}");
	$title=$tpl->_ENGINE_parse_body("{add_member}");
	$filter=$tpl->_ENGINE_parse_body("{filter}");
	
	$buttons="
	buttons : [
	{name: '$filter', bclass: 'Search', onpress : SambaAclBrowseFilter},$BrowsAD
	],";		
	
$html="
<div style='margin-left:-10px'>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
</div>
<script>
var rowid=0;
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?SearchPattern=yes&t=$t&acls_gps={$_SESSION["SambaAclBrowseFilter"]["acls_gps"]}&acls_comps={$_SESSION["SambaAclBrowseFilter"]["acls_comps"]}&acls_users={$_SESSION["SambaAclBrowseFilter"]["acls_users"]}&acls_onlyad={$_SESSION["SambaAclBrowseFilter"]["acls_onlyad"]}',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'groupname', width : 31, sortable : true, align: 'center'},	
		{display: '$members', name : 'members', width :405, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'members', width :31, sortable : false, align: 'left'},
		
		],
	$buttons
	searchitems : [
		{display: '$members', name : 'members'},
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

function SambaAclBrowseFilter(){
	YahooUser('400','$page?SambaAclBrowseFilter=yes&t=$t','$filter');
}

</script>
";

	echo $html;
	
}

function SambaAclBrowseFilter(){
	$t=$_GET["t"];
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$EnableSambaActiveDirectory=$sock->GET_INFO("EnableSambaActiveDirectory");
	if(!is_numeric($EnableSambaActiveDirectory)){$EnableSambaActiveDirectory=0;}
	
	if(isset($_SESSION[__FUNCTION__]["acls_comps"])){$_SESSION[__FUNCTION__]["acls_comps"]=1;}
	if(isset($_SESSION[__FUNCTION__]["acls_gps"])){$_SESSION[__FUNCTION__]["acls_gps"]=1;}
	if(isset($_SESSION[__FUNCTION__]["acls_users"])){$_SESSION[__FUNCTION__]["acls_users"]=1;}
	if(isset($_SESSION[__FUNCTION__]["acls_onlyad"])){$_SESSION[__FUNCTION__]["acls_onlyad"]=0;}
	
	
	if($EnableSambaActiveDirectory==1){
		$config_activedirectory=unserialize(base64_decode($sock->GET_INFO("SambaAdInfos")));
		$WORKGROUP=strtoupper($config_activedirectory["WORKGROUP"]);
		$onlyAd="<tr>
		<td valign='top' class=form style='font-size:16px'>{only_from_activedirectory} ($WORKGROUP):</td>
		<td>". Field_checkbox("acls_onlyad", 1,$_SESSION[__FUNCTION__]["acls_onlyad"],"CheckAclsFilter()")."</td>
	</tr>";
	}
	
	$html="
	<table style='width:99%' class=form>
	<tr>
		<td valign='top' class=form style='font-size:16px'>{computers}:</td>
		<td>". Field_checkbox("acls_comps", 1,$_SESSION[__FUNCTION__]["acls_comps"],"CheckAclsFilter()")."</td>
	</tr>
	<tr>
		<td valign='top' class=form style='font-size:16px'>{groupsF}:</td>
		<td>". Field_checkbox("acls_gps", 1,$_SESSION[__FUNCTION__]["acls_gps"],"CheckAclsFilter()")."</td>
	</tr>
	<tr>
		<td valign='top' class=form style='font-size:16px'>{members}:</td>
		<td>". Field_checkbox("acls_users", 1,$_SESSION[__FUNCTION__]["acls_users"],"CheckAclsFilter()")."</td>
	</tr>
	$onlyAd		
	</table>
	<script>
		function CheckAclsFilter(){
			var comp;
			var gps;
			var usr;
			var acls_onlyad='&acls_onlyad=0';
			if(document.getElementById('acls_onlyad')){
				if(document.getElementById('acls_onlyad').checked){acls_onlyad='&acls_onlyad=1';}
			
			}
			
			if(document.getElementById('acls_comps').checked){comp='&comp=1';}else{comp='&comp=0';}
			if(document.getElementById('acls_gps').checked){gps='&acls_gps=1';}else{gps='&acls_gps=0';}
			if(document.getElementById('acls_users').checked){usr='&acls_users=1';}else{usr='&acls_users=0';}
			$('#flexRT$t').flexOptions({url: '$page?SearchPattern=yes&t=$t'+comp+gps+usr+acls_onlyad}).flexReload(); 
		}
	</script>
	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}


function list_users(){
	$sock=new sockets();
	$query=$_POST["query"];
	$_SESSION["SambaAclBrowseFilter"]["acls_comps"]=$_GET["acls_comps"];
	$_SESSION["SambaAclBrowseFilter"]["acls_gps"]=$_GET["acls_gps"];
	$_SESSION["SambaAclBrowseFilter"]["acls_users"]=$_GET["acls_users"];
	$_SESSION["SambaAclBrowseFilter"]["acls_onlyad"]=$_GET["acls_onlyad"];
	if(!is_numeric($_SESSION["SambaAclBrowseFilter"]["acls_onlyad"])){$_SESSION["SambaAclBrowseFilter"]["acls_onlyad"]=0;}
	$WORKGROUP=null;
	
	if($_SESSION["SambaAclBrowseFilter"]["acls_onlyad"]==1){
		$EnableSambaActiveDirectory=$sock->GET_INFO("EnableSambaActiveDirectory");
		if(!is_numeric($EnableSambaActiveDirectory)){$EnableSambaActiveDirectory=0;}
		if($EnableSambaActiveDirectory==1){
			$config_activedirectory=unserialize(base64_decode($sock->GET_INFO("SambaAdInfos")));
			$WORKGROUP=strtoupper($config_activedirectory["WORKGROUP"])."/";
		}
	}
	
	
	
	$tpl=new templates();
	$ldap=new clladp();
	if($_SESSION["SambaAclBrowseFilter"]["acls_onlyad"]==0){
		if($_SESSION["SambaAclBrowseFilter"]["acls_users"]==1){
				$sr =@ldap_search($ldap->ldap_connection,"dc=organizations,$ldap->suffix",
				"(&(objectClass=posixAccount)(|(uid=$query*)(cn=$query*)(displayName=$query*)))",array("displayname","uid"),0,$_POST["rp"],20);
					if($sr){
							$result = ldap_get_entries($ldap->ldap_connection, $sr);
							for($i=0;$i<$result["count"];$i++){
								
								$displayname=$result[$i]["displayname"][0];
								$uid=$result[$i]["uid"][0];
								if(substr($uid,strlen($uid)-1,1)=='$'){continue;}
								
								
								if($displayname==null){$displayname=$uid;}
								$res[$uid]=$displayname;
							}
							
					}	
			}
			
		if($_SESSION["SambaAclBrowseFilter"]["acls_gps"]==1){	
				$sr =@ldap_search($ldap->ldap_connection,"dc=organizations,$ldap->suffix",
					"(&(objectClass=posixGroup)(cn=$query*))",array("cn"),0,$_POST["rp"],20);
				if($sr){
					$result = ldap_get_entries($ldap->ldap_connection, $sr);
					for($i=0;$i<$result["count"];$i++){
						$displayname=$result[$i]["cn"][0];
						$uid=$result[$i]["cn"][0];
						if(trim($displayname)==null){$displayname=$uid;}
						$res[$uid]=array("displayname"=>"$displayname","members"=>array());
						}
					}		
		}
				
		if($_SESSION["SambaAclBrowseFilter"]["acls_users"]==1){	
				$sr =@ldap_search($ldap->ldap_connection,"dc=organizations,$ldap->suffix",
				"(&(objectClass=posixGroup)(|(cn=$query*)(memberUid=$query*)))",array("cn","memberUid"),0,$_POST["rp"],20);
				if($sr){
						$result = ldap_get_entries($ldap->ldap_connection, $sr);
						for($i=0;$i<$result["count"];$i++){
							$displayname=$result[$i]["cn"][0];
							$uid=$result[$i]["cn"][0];
							if(trim($displayname)==null){$displayname=$uid;}
							$res[$uid]=array("displayname"=>"$displayname","members"=>$result[$i]["memberuid"]);
						}
						
				}
		}
	}
		
		if(strpos(" $query","*")==0){$query=$query."*";}
		if($query=="*"){$query=null;}
		$q=new mysql();
		
	if($_SESSION["SambaAclBrowseFilter"]["acls_users"]==1){	
		if($query==null){
			$sql="SELECT uid FROM getent_users ORDER BY uid";
		}else{
			$query=str_replace("**", "*", $query);
			$query=str_replace("*", "%", $query);
			$sql="SELECT uid FROM getent_users WHERE uid LIKE '$WORKGROUP$query' ORDER BY uid";
			writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		}
				
		
		$results=$q->QUERY_SQL($sql,"artica_backup");
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$uid=$ligne["uid"];
			$res[$uid]=$uid;
		}
	}
		
	if($_SESSION["SambaAclBrowseFilter"]["acls_gps"]==1){		
		if($query==null){
			$sql="SELECT `group` FROM getent_groups ORDER BY `group`";
		}else{
			$query=str_replace("**", "*", $query);
			$query=str_replace("*", "%", $query);
			$sql="SELECT `group` FROM getent_groups WHERE `group` LIKE '$WORKGROUP$query' ORDER BY `group`";
			writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		}
		$results=$q->QUERY_SQL($sql,"artica_backup");
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$uid=$ligne["group"];
			$res[$uid]=array("displayname"=>"$uid","members"=>array($uid),"count"=>1);
		}
	}
	
	if(!is_array($res)){return null;}
	ksort($res);
	reset($res);
	$c=0;
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($res);
	$data['rows'] = array();	
	$members_t=$tpl->_ENGINE_parse_body("{members}");
	while (list ($num, $ligne) = each ($res) ){
		if($ligne["displayname"]==null){continue;}
		
		
		
		if(is_array($ligne)){
			if($_SESSION["SambaAclBrowseFilter"]["acls_gps"]==0){continue;}
			$img="wingroup.png";
			$js="AddAclGroup('".base64_encode($ligne["displayname"])."');";
			
			if(strlen($ligne["displayname"])>30){$ligne["displayname"]=substr($ligne["displayname"],0,27)."...";}
			if($ligne["members"]["count"]>10){$ligne["members"]["count"]=10;}
			if($ligne["members"]["count"]>1){
			$mm[]=" $members_t: ";
			for($i=0;$i<$ligne["members"]["count"];$i++){
				$mm[]="{$ligne["members"][$i]}";
			}
			}

			$Displayname=$ligne["displayname"].@implode(", ", $mm);
			unset($mm);
			
		}else{
			if(substr($ligne, strlen($ligne)-1,1)=='$'){
				if($_SESSION["SambaAclBrowseFilter"]["acls_comps"]==0){continue;}	
			}else{
				if($_SESSION["SambaAclBrowseFilter"]["acls_users"]==0){continue;}
			}
			
			$Displayname=$ligne;
			$js="AddAclUser('".base64_encode($Displayname)."');";
			if(strlen($Displayname)>30){$Displayname=substr($Displayname,0,27)."...";}
			$img="user-18.png";
			
		}
		
		$c++;
		if($c>$_POST["rp"]){break;}
		
		$data['rows'][] = array(
		'id' => md5(serialize($ligne["displayname"])),
		'cell' => array(
			"<img src='img/$img'>",
			"<span style='font-size:14px;font-weight:bolder'>$Displayname</span>",
			"<span style='font-size:14px'>".imgsimple("arrow-right-24.png","{add}",$js)."</span>",
			)
		);		
		
		
	
	}
	$data['total'] = $c;
	echo json_encode($data);	

}	


function dir_status(){
	$dir=base64_decode($_GET["path"]);
	$acls=new aclsdirs($dir);
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->getFrameWork("cmd.php?acls-status=".urlencode($dir))));
	while (list ($num, $ligne) = each ($datas) ){
		$ligne=htmlspecialchars($ligne);
		$ligne=str_replace("\t","&nbsp;&nbsp;&nbsp;",$ligne);
		$ligne=str_replace(" ","&nbsp;",$ligne);
		$ligne=str_replace("#HR#","<hr>",$ligne);
		
		if($ligne==null){$ligne="&nbsp;";}
		$html=$html."<div><code style='font-size:12px'>$ligne</code></div>";
		
		
	}
	$html=$html."<hr>";
	$datas=explode("\n",$acls->events);
	
	while (list ($num, $ligne) = each ($datas) ){
		$ligne=htmlspecialchars($ligne);
		$ligne=str_replace(" ","&nbsp;",$ligne);
		$html=$html."<div><code style='font-size:12px'>$ligne</code></div>";
		
		
	}	
	
	echo $html;
}



?>