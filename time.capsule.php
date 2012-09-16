<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.user.inc');
	include_once('ressources/class.samba.inc');
	include_once('ressources/class.computers.inc');

	
	if(isset($_GET["debug-page"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);$GLOBALS["VERBOSE"]=true;}
	
	if(!CheckSambaRights()){
		$tpl=new templates();
		$ERROR_NO_PRIVS=$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
		echo "<H1>$ERROR_NO_PRIVS</H1>";die();
	}

	if(isset($_POST["folderprop-options"])){folderprop_options_save();exit;}
	if(isset($_GET["time-capsule-status"])){status();exit;}
	if(isset($_GET["time-capsule-shared"])){shared_popup();exit;}
	if(isset($_GET["shared-list"])){shared_list();exit;}
	if(isset($_POST["AddTreeFolders"])){shared_add();exit;}
	if(isset($_POST["shared-delete"])){shared_delete();exit;}
	if(isset($_GET["folderprop-js"])){folderprop_js();exit;}
	if(isset($_GET["folderprop-popup"])){folderprop_popup();exit;}
	if(isset($_GET["folderprop-members"])){folderprop_members();exit;}
	if(isset($_GET["folderprop-members-list"])){folderprop_members_list();exit;}
	if(isset($_GET["AddUservalue"])){folderprop_addmember_js();exit;}
	if(isset($_POST["AddUservalue"])){folderprop_addmember();exit;}
	if(isset($_POST["DeleteUservalue"])){folderprop_delmember();exit;}
	
	if(isset($_GET["folderprop-options"])){folderprop_options();exit;}
	
	
	
page();


function folderprop_js(){
	$page=CurrentPageName();
	$dir=basename(base64_decode($_GET["root"]));
	$html="YahooWin3('650','$page?folderprop-popup=yes&root={$_GET["root"]}','$dir')";
	echo $html;
	
}


function shared_popup(){
$page=CurrentPageName();
$tpl=new templates();
$t=time();
$html="
<span id='timedCapsule'></span>
<div class=explain>{TimeMachine_howto}</div>
<code style='font-size:12px'>defaults write com.apple.systempreferences TMShowUnsupportedNetworkVolumes 1</code>
<hr>	
<table style='width:100%'>
<tr>
	<td width=99% valign='middle'><span style='font-size:16px'>{shared_folders}</span></td>
	<td valign='top' nowrap width=1%>". 
		imgtootltip("folder-granted-add-48.png","{add_a_shared_folder}","Loadjs('browse-disk.php?with-capsule=1')"). "
	</td>
	<td valign='top' nowrap width=1%>". 
		imgtootltip("identity-add-48.png","{add user explain}","Loadjs('create-user.php')"). "
	</td>	
</tr>
</table>
<div style='width:100%;height:578px;overflow:auto' id='SharedFoldersList-$t'></div>


<script>
	function RefreshTimeCapsuleList(){
		LoadAjax('SharedFoldersList-$t','$page?shared-list=yes');
	}
	RefreshTimeCapsuleList();
</script>


";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);		
	
	
}


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$html="
	<div style='margin-left:-15px;margin:right:-20px'>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td valign='top' with=1%><div id='time-capsule-status'></div><div style='width:100%;text-align:right'>
		". imgtootltip("refresh-24.png","{refresh}","LoadAjax('time-capsule-status','$page?time-capsule-status=yes');")."</td>
		<td valign='top' width=99%'><div id='time-capsule-shared'></div></td>
	</tr>
	</tbody>
	</table>
	</div>
	<script>
		LoadAjax('time-capsule-status','$page?time-capsule-status=yes');
	</script>
		
	";
	
echo $html;
	
	
	
	
}

function shared_list(){
	$page=CurrentPageName();
	$tpl=new templates();
	$del_folder_name=$tpl->javascript_parse_text("{del_folder_name}");
$add=imgtootltip("plus-24.png","{add_a_shared_folder}","Loadjs('browse-disk.php?with-capsule=1');");	
$html="
<input type='hidden' id='del_folder_name' value='{del_folder_name}'>
<table cellspacing='0' cellpadding='0' border='0' class='tableView'>
<thead class='thead'>
	<tr>
	<th width=1% align='center'>$add</th>
	<th>{name}</th>
	<th>{path}</th>
	<th>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>	
";	
$q=new mysql();
$results=$q->QUERY_SQL("SELECT * FROM netatalk ORDER BY sharedname","artica_backup");
if(!$q->ok){echo "<H2>$q->mysql_error</H2>";}
while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
	if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}	
	$id=md5($ligne["directory"]);
	$delete=imgtootltip("delete-32.png","{delete}","CapsuleDelete('".base64_encode($ligne["directory"])."','$id')");
	$icon="apple-32.png";
	$propertiesjs="Loadjs('$page?folderprop-js=yes&root=".base64_encode($ligne["directory"])."');";
	$html=$html . "
	<tr class=$classtr id='$id'>
	<td width=1%>". imgtootltip("$icon","{edit}",$propertiesjs)."</td>
	<td><strong style='font-size:13px'><code style='font-size:13px'>{$ligne["sharedname"]}</a></code></td>
	<td><strong ><code style='font-size:13px'>{$ligne["directory"]}</a></code></td>
	<td width=1%>$delete</td>
	</tr>
	";
	}
	
	$html=$html ."
	<tbody>
	</table>
	<script>
	var capsid='';
	
		var x_CapsuleDelete=function (obj) {
		 	text=obj.responseText;
		 	if(text.length>2){alert(text);return;}
				$('#'+capsid).remove();
			
		}	
	
		function CapsuleDelete(sfolder,id){
			capsid=id;
			if(confirm('$del_folder_name')){
				var XHR = new XHRConnection();
        		XHR.appendData('shared-delete',sfolder);
        		XHR.sendAndLoad('$page', 'POST',x_CapsuleDelete);		
			
			}
		
		}
	
	";
	
	
echo $tpl->_ENGINE_parse_body($html);
	
}

function shared_add(){
	$folder=$_POST["AddTreeFolders"];
	$folder=addslashes($folder);
	$folder_name=basename($folder);
	
	$q=new mysql();
	$q->BuildTables();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT sharedname FROM netatalk WHERE sharedname='$folder_name'","artica_backup"));
	if($ligne["sharedname"]<>null){$folder_name=time();}
	$folder_name=addslashes($folder_name);
	$sql="INSERT IGNORE INTO netatalk (`directory`,`sharedname`) VALUES('$folder','$folder_name')";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("services.php?restart-netatalk=yes");	
}
function shared_delete(){
	$folder=base64_decode($_POST["shared-delete"]);
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM netatalk WHERE `directory`='$folder'",'artica_backup');
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("services.php?restart-netatalk=yes");	
	
}


function status(){
	
	
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	
	echo "<script>
		LoadAjax('time-capsule-shared','$page?time-capsule-shared=yes');
	</script>";
	
	$ini=new Bs_IniHandler();
	
	
	$ini->loadString(base64_decode($sock->getFrameWork("services.php?time-capsule-status=yes")));
	$status=DAEMON_STATUS_ROUND("APP_AVAHI",$ini).DAEMON_STATUS_ROUND("APP_NETATALK",$ini);
	echo $tpl->_ENGINE_parse_body($status);
}

function folderprop_popup(){
	
	
	$page=CurrentPageName();
	$array["members"]='{members}';
	$array["options"]='{options}';
	$tpl=new templates();
	
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:14px'><a href=\"$page?folderprop-$num&root={$_GET["root"]}\"><span>$ligne</span></a></li>\n");
	}
	
	$t="TimeCapsuleFolderProp";
	echo "
	<div id=$t style='width:100%;height:590px;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#$t').tabs();
			
		
			});
		</script>";	
	
	
	
	
}

function folderprop_members(){
	
	$t=time();
	$page=CurrentPageName();
	$html="<div id='$t'></div>
	<script>
		LoadAjax('$t','$page?folderprop-members-list=yes&root={$_GET["root"]}');
	</script>
	";
	
	echo $html;
	
	
}

function folderprop_members_list(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sec=$tpl->_ENGINE_parse_body("{security}");
	$root=base64_decode($_GET["root"]);
	$dirname=basename($root);
	$q=new mysql();
	$q->BuildTables();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM netatalk WHERE `directory`='$root'","artica_backup"));	
	$datas=unserialize(base64_decode($ligne["allow"]));
	
	
	
	
	$add=imgtootltip("plus-24.png","{add_a_shared_folder}","YahooWin5(600,'samba.index.php?security=$dirname&TimeCapsule={$_GET["root"]}','$sec $dirname');");	
$html="
<input type='hidden' id='del_folder_name' value='{del_folder_name}'>
<table cellspacing='0' cellpadding='0' border='0' class='tableView'>
<thead class='thead'>
	<tr>
	<th width=1% align='center'>$add</th>
	<th>{members}</th>
	<th>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>";	

writelogs("Start the array",__FUNCTION__,__FILE__,__LINE);
while (list ($uid, $ligne) = each ($datas) ){
	if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}	
	$id=md5($uid);
	$pic="user-32.png";
	$member=null;
	
	
	if(strpos($uid,"$")>0){
		$pic="computer-32.png";
		$cp=new computers($uid);
		$member=$cp->DisplayName." ($cp->ComputerIP)";
		$js=URL_COMPUTER($uid,5);
	}
	if(substr($uid,0,1)=="@"){
		$pic="group-32.png";
		$member="{group}:".substr($uid,1,strlen($uid));
		$js="blur()";
	}
	if($member==null){
		$ct=new user($uid);
		$member=$ct->DisplayName;
		$js=MEMBER_JS($uid,0,1);
	}
	
	$delete=imgtootltip("delete-32.png","{delete}","CapsuleDeleteMembers('$uid','$id')");
	$icon="apple-32.png";
	$propertiesjs="Loadjs('$page?folderprop-js=yes&root=".base64_encode($ligne["directory"])."');";
	$html=$html . "
	<tr class=$classtr id='$id'>
	<td width=1%><img src='img/$pic'></td>
	<td><a href=\"javascript:blur();\" OnClick=\"javascript:$js;\" style='font-size:16px;text-decoration:underline'>$member</a></td>
	<td width=1%>$delete</td>
	</tr>
	";	
	
	
	
}
writelogs("stop the array",__FUNCTION__,__FILE__,__LINE);
$html=$html ."
	</tbody>
	</table>
<script>
	var memberid='';

	function x_CapsuleDeleteMembers(obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
		$('#'+memberid).remove();
		
	}

	function CapsuleDeleteMembers(uid,id){
		memberid=id;
		var XHR = new XHRConnection();
		XHR.appendData('DeleteUservalue',uid);
		XHR.appendData('root','{$_GET["root"]}');
		XHR.sendAndLoad('$page', 'POST',x_CapsuleDeleteMembers);				
	
	}		
	
</script>	
	";

	echo $tpl->_ENGINE_parse_body($html);
}

function folderprop_addmember_js(){
	$member=$_GET["AddUservalue"];
	$root=base64_decode($_GET["root"]);	
	$page=CurrentPageName();
	$html="
	function x_folderprop_addmember_js(obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
		RefreshTab('TimeCapsuleFolderProp');
		
	}

	function folderprop_addmember_js(){
		var XHR = new XHRConnection();
		XHR.appendData('AddUservalue','$member');
		XHR.appendData('root','{$_GET["root"]}');
		XHR.sendAndLoad('$page', 'POST',x_folderprop_addmember_js);				
	
	}	
	
	folderprop_addmember_js();
	";
	
	echo $html;
	
	
}

function folderprop_addmember(){
	$member=$_POST["AddUservalue"];
	$root=base64_decode($_POST["root"]);
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM netatalk WHERE `directory`='$root'","artica_backup"));	
	$datas=unserialize(base64_decode($ligne["allow"]));
	$datas[$member]=$member;
	$newdatas=base64_encode(serialize($datas));
	$sql="UPDATE netatalk SET allow='$newdatas' WHERE `directory`='$root'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("services.php?restart-netatalk=yes");	
}
function folderprop_delmember(){
	$member=$_POST["DeleteUservalue"];
	$root=base64_decode($_POST["root"]);
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM netatalk WHERE `directory`='$root'","artica_backup"));	
	$datas=unserialize(base64_decode($ligne["allow"]));
	unset($datas[$member]);
	$newdatas=base64_encode(serialize($datas));
	$sql="UPDATE netatalk SET allow='$newdatas' WHERE `directory`='$root'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("services.php?restart-netatalk=yes");	
}


function folderprop_options(){
$page=CurrentPageName();
	$tpl=new templates();
	$sec=$tpl->_ENGINE_parse_body("{security}");
	$root=base64_decode($_GET["root"]);
	$dirname=basename($root);
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM netatalk WHERE `directory`='$root'","artica_backup"));	
	$readonly=$ligne["readonly"];
	
	$p=Paragraphe_switch_img("{readonly}", "{capsule_readonly}","readonly",$readonly,null,450);
	$t=time();
	$html="
	<div id='$t'>
	$p
	
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend style='font-size:14px'>{share_name}:</td>
		<td>". Field_text("sharedname",$ligne["sharedname"],"font-size:16px")."</td>
	</tr>
	</tbody>
	</table>
	
	<div style='width:100%;text-align:right'><hr>". button("{apply}","SaveCapsuleOptions()",16)."</div>
	</div>
	<script>
	function x_SaveCapsuleOptions(obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
		RefreshTab('TimeCapsuleFolderProp');
		RefreshTimeCapsuleList();
		
	}

	function SaveCapsuleOptions(){
		var XHR = new XHRConnection();
		XHR.appendData('folderprop-options','$t');
		XHR.appendData('sharedname',document.getElementById('sharedname').value);
		XHR.appendData('readonly',document.getElementById('readonly').value);
		XHR.appendData('root','{$_GET["root"]}');
		XHR.sendAndLoad('$page', 'POST',x_SaveCapsuleOptions);				
	}		
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function folderprop_options_save(){
	$q=new mysql();
	$root=base64_decode($_POST["root"]);
	$sharedname=addslashes($_POST["sharedname"]);
	$readonly=$_POST["readonly"];
	$sql="UPDATE netatalk SET sharedname='$sharedname', readonly='$readonly' WHERE `directory`='$root' ";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
	$sock=new sockets();
	$sock->getFrameWork("services.php?restart-netatalk=yes");
}
	
	
	
	
	