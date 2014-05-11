<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.ldap.inc');
	
	$user=new usersMenus();
	if(!$user->AsSystemAdministrator){echo "alert('no privileges');";die();}
	if(isset($_POST["DirectoryFSPath"])){DirectoryFSPath();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	
js();	
function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$virtual_disks=$tpl->_ENGINE_parse_body("{move_filesystem}");
	$html="YahooWin3('650','$page?popup=yes','$virtual_disks');";
	echo $html;
}




function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$size=$sock->GET_INFO("SystemTotalSize");
	$DirectoryFSPath=$sock->GET_INFO("DirectoryFSPath");
	$move_fs_to=$tpl->javascript_parse_text("{move_fs_to}");
	$size=FormatBytes($size/1024);
	$html="
	<div style='width:98%' class=form>
		<div style='font-size:22px'>{move_filesystem}: $size</div>
		<div class=explain style='font-size:14px'>{move_filesystem_explain}</div>	
		<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:16px'>{directory}:</td>
			<td>". Field_text("DirectoryFSPath",$DirectoryFSPath,"width:220px;font-size:16px")."</td>
			<td>". button_browse("DirectoryFSPath")."</td>
		</tr>
		<tr>		
			<td colspan=3 align='right'><hr>". button("{move}","DirectoryFSPathSave()",18)."</td>
		</tr>
		</table>
	</div>		
<script>
var xDirectoryFSPathSave= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	YahooWin3Hide();
	
}
function DirectoryFSPathSave(){
	var path=document.getElementById('DirectoryFSPath').value;
	if(confirm('$move_fs_to:'+path+' ?')){
		var XHR = new XHRConnection();
		XHR.appendData('DirectoryFSPath',path);
		XHR.sendAndLoad('$page', 'POST',xDirectoryFSPathSave);
	}
}				
</script>";
	
echo $tpl->_ENGINE_parse_body($html);	
	
}

function DirectoryFSPath(){
	$sock=new sockets();
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{task_has_been_scheduled_in_background_mode}");
	$sock->SET_INFO("DirectoryFSPath",$_POST["DirectoryFSPath"]);
	$sock->getFrameWork("system.php?move-system=yes");
}

