<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.samba.inc');
	include_once('ressources/class.acls.inc');
	
	
	$user=new usersMenus();
	if($user->AsSambaAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["sharedlist"])){shared_folders_list();exit;}
	if(isset($_GET["acldisks"])){acldisks();exit;}
	if(isset($_GET["acldisks-list"])){acldisks_list();exit;}
	
	
	if(isset($_GET["aclline"])){aclsave();exit;}
	if(isset($_GET["quotaline"])){quotasave();exit;}
	if(isset($_GET["acls-folders-list"])){aclfolders();exit;}
	if(isset($_GET["AclsFoldersRebuild"])){aclfolders_rebuild();exit;}
	if(isset($_GET["DeleteAclFolder"])){aclfolders_delete();exit;}
	
js();
//fstablist

function js(){
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{shared_folders}","samba.index.php");
	$page=CurrentPageName();
	$html="
		function shared_folders_start(){
			YahooWin5('600','$page?popup=yes','$title');
		}
	
	
	shared_folders_start();";
	
echo $html;	
	
}

function popup(){
	
	$array["sharedlist"]="{shared_folders}";
	$array["acldisks"]="{acl_disks}";
	$tpl=new templates();
	$page=CurrentPageName();
	while (list ($num, $ligne) = each ($array) ){
		$ligne=$tpl->_ENGINE_parse_body($ligne);
		$ligne_text= html_entity_decode($ligne,ENT_QUOTES,"UTF-8");
		if(strlen($ligne_text)>17){
			$ligne_text=substr($ligne_text,0,14);
			$ligne_text=htmlspecialchars($ligne_text)."...";
			$ligne_text=texttooltip($ligne_text,$ligne,null,null,1);
			}
		//$html=$html . "<li><a href=\"javascript:ChangeSetupTab('$num')\" $class>$ligne</a></li>\n";
		
		$html[]= "<li><a href=\"$page?$num=yes\"><span>$ligne_text</span></a></li>\n";
			
		}
	$tpl=new templates();
	
	echo "
	<div id=main_samba_shared_folders style='width:100%;height:550px;overflow:auto;background-color:white;'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_samba_shared_folders').tabs({
				    load: function(event, ui) {
				        $('a', ui.panel).click(function() {
				            $(ui.panel).load(this.href);
				            return false;
				        });
				    }
				});
			

			});
		</script>";			
}

function shared_folders_list(){
	
	
	
	
	$samba=new samba();
	$folders=$samba->main_folders;
	if(!is_array($folders)){return null;}
	
	
	$html="
	<input type='hidden' id='del_folder_name' value='{del_folder_name}'>
	<table style='width:100%'>
	<tr>
	<th>&nbsp;</th>
	<th>{name}</th>
	<th>{path}</th>
	<th>&nbsp;</th>
	</tr>";
	
	
	while (list ($FOLDER, $ligne) = each ($folders) ){
		if($FOLDER=="netlogon"){continue;}
		if($FOLDER=="homes"){continue;}
		if($FOLDER=="printers"){continue;}
		if($FOLDER=="print$"){continue;}
		$properties="FolderProp('$FOLDER')";
		$delete=imgtootltip('ed_delete.gif','{delete}',"FolderDelete('$FOLDER')");
		if($samba->main_array[$FOLDER]["path"]=="/home/netlogon"){continue;}
		if($samba->main_array[$FOLDER]["path"]=="/home/export/profile"){continue;}

					
		
		
		
	$html=$html . "
	<tr " . CellRollOver($properties) . ">
	<td width=1%><img src='img/shared20x20.png'></td>
	<td><strong style='font-size:12px' width=1% nowrap>$FOLDER</td>
	<td><strong style='font-size:12px' width=99%>{$samba->main_array[$FOLDER]["path"]}</td>
	<td width=1%>$delete</td>
	</tr>
	";
	}
	
	$html=$html ."</table>";
	
	$html="<div style='width:99%;height:250px;overflow:auto'>$html</div>";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
}

function acldisks(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$SAMBA_HAVE_POSIX_ACLS=base64_decode($sock->getFrameWork("samba.php?SAMBA-HAVE-POSIX-ACLS=yes"));
	$disk=$tpl->_ENGINE_parse_body("{disks}");
	$about=$tpl->_ENGINE_parse_body("{about_item}");
	$mounted=$tpl->_ENGINE_parse_body("{mounted}");
	$acl_enabled=$tpl->_ENGINE_parse_body("{acl_enabled}");
	$folders=$tpl->_ENGINE_parse_body("{folders}");
	$quota_disk=$tpl->_ENGINE_parse_body("{quota_disk}");
	$rebuild_acls=$tpl->_ENGINE_parse_body("{rebuild_acls}");
	$acls_folders_rebuild_text=$tpl->javascript_parse_text("{acls_folders_rebuild_text}");
	$t=time();
	if($SAMBA_HAVE_POSIX_ACLS<>"TRUE"){
		$acl_samba_not="{name: 'Samba!', bclass: 'Warn', onpress : SambaWarnACL},";
	}
	
	$TABLE_WIDTH=707;
	$DISK_WITH=319;
	$MONTED_WIDTH=172;
	if(isset($_GET["quicklinks"])){
		$TABLE_WIDTH=871;
		$DISK_WITH=398;
		$MONTED_WIDTH=255;
	}
	
	if($users->APACHE_APPLIANCE){$acl_samba_not=null;}

	
		$buttons="
		buttons : [
		{name: '$disk', bclass: 'Hd', onpress : AclsDisksSwitch},
		
		{name: '$folders', bclass: 'Folder', onpress : AclsFoldersSwitch},
		{name: '$rebuild_acls', bclass: 'Reconf', onpress : AclsFoldersRebuild},
		$acl_samba_not
		{name: '$about', bclass: 'Help', onpress : AclAbout},
		],";	
		
$html="
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
var IDTMP=0;
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?acldisks-list=yes',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'none', width :31, sortable : false, align: 'center'},
		{display: '$disk', name : 'disk', width :$DISK_WITH, sortable : false, align: 'left'},
		{display: '$mounted', name : 'mounted', width : $MONTED_WIDTH, sortable : false, align: 'left'},
		{display: '$acl_enabled', name : '53', width : 53, sortable : false, align: 'center'},
		{display: '$quota_disk', name : '54', width : 53, sortable : false, align: 'center'},
		
		],
	$buttons
	searchitems : [
		{display: '$disk', name : 'pattern'},
		],
	sortname: 'ID',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TABLE_WIDTH,
	height: 300,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

function AclAbout(){
	LoadHelp('". urlencode(base64_encode($tpl->_ENGINE_parse_body("{acl_feature_about}")))."','',false);
	}
function SambaWarnACL(){
	LoadHelp('". urlencode(base64_encode($tpl->_ENGINE_parse_body("<strong style=color:red>{acl_samba_not}</strong>")))."','',false);
	}
	
function AclsFoldersSwitch(){
	$('#flexRT$t').flexOptions({url: '$page?acls-folders-list=yes'}).flexReload(); 
}

function AclsDisksSwitch(){
	$('#flexRT$t').flexOptions({url: '$page?acldisks-list=yes'}).flexReload(); 
}

	var x_FdiskEnableAcl=function (obj) {
			tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue);}
			$('#flexRT$t').flexReload();		
			
	    }
	
		function FdiskEnableAcl(id,dev){
			var XHR = new XHRConnection();
			XHR.appendData('aclline',dev);
			if(document.getElementById(id).checked){XHR.appendData('acl','1');}else{XHR.appendData('acl','0');}
			XHR.sendAndLoad('$page', 'GET',x_FdiskEnableAcl);
		}
		
		function FdiskEnableQuota(id,dev){
			var XHR = new XHRConnection();
			XHR.appendData('quotaline',dev);
			if(document.getElementById(id).checked){XHR.appendData('quota','1');}else{XHR.appendData('quota','0');}
			XHR.sendAndLoad('$page', 'GET',x_FdiskEnableAcl);
		}	
		
		function AclsFoldersRebuild(){
			if(confirm('$acls_folders_rebuild_text')){
				var XHR = new XHRConnection();
				XHR.appendData('AclsFoldersRebuild','yes');
				XHR.sendAndLoad('$page', 'GET');
			
			}
		
		}
		
	   var x_DeleteAclFolder=function (obj) {
			results=obj.responseText;
			if(results.length>0){alert(results);}
			$('#flexRT$t').flexReload();
	    }			
		
		function DeleteAclFolder(path){
			var XHR = new XHRConnection();
			XHR.appendData('DeleteAclFolder',path);
			XHR.sendAndLoad('$page', 'GET',x_DeleteAclFolder);		
		}		
	
</script>";	
	
	echo $html;
	
}

function acldisks_list(){
	$sock=new sockets();
	$users=new usersMenus();	
	$fstab=unserialize(base64_decode($sock->getFrameWork("cmd.php?fstablist=yes")));
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = 0;
	$data['rows'] = array();	
	
	$search=$_POST["query"];
	if($search<>null){
		$search=str_replace("/", "\/", $search);
		$search=str_replace(".", "\.", $search);
		$search=str_replace("*", ".*?", $search);
		
	}
	
	$WRONGFS["btrfs"]=true;
	
while (list ($num, $ligne) = each ($fstab) ){
	
		if(substr($ligne,0,1)=="#"){continue;}
		if(preg_match("#(.+?)\s+(.+?)\s+(.*?)\s+(.*?)\s+#",$ligne,$re)){
			$enableacl=0;
			$quota=0;
			if($re[1]=="proc"){continue;}
			if($re[2]=="none"){continue;}
			if(preg_match("#cdrom#",$re[2])){continue;}
			if(preg_match("#floppy#",$re[2])){continue;}
			if(preg_match("#\/boot$#",$re[2])){continue;}
			if($re[3]=="tmpfs"){continue;}
			if($search<>null){
				if(!preg_match("#$search#i", $re[1])){
					if(!preg_match("#$search#i", $re[2])){continue;}
				}
			}
			
			if(preg_match("#acl#",$re[4])){$acl=1;}else{$acl=0;}
			if(preg_match("#usrjquota#",$re[4])){$quota=1;}else{$quota=0;}
			
			
			
			$dev=base64_encode(trim($re[1]));
			$enableacl=Field_checkbox("acl_$num",1,$acl,"FdiskEnableAcl('acl_$num','$dev');");
			$enablequeota=Field_checkbox("quota_$num",1,$quota,"FdiskEnableQuota('quota_$num','$dev');");
			$re[1]=$re[1]." ({$re[3]})";
			
			if($WRONGFS[$re[3]]){
				$enableacl="&nbsp;";
				$enablequeota="&nbsp;";
			}
			
			$c++;
			$data['rows'][] = array(
			'id' => $ligne['ID'],
			'cell' => array("<img src='img/disk-32.png'>"
			,"<span style='font-size:14px'>{$re[1]}</span>"
			,"<span style='font-size:14px'>{$re[2]}</span>"
			,$enableacl
			,$enablequeota
			 )
			);			

		}
	}

	$data['total'] = $c;
	
	
	echo json_encode($data);
	
}

function aclsave(){
	$dev=$_GET["aclline"];
	$acl=$_GET["acl"];
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?fstab-acl=yes&acl=$acl&dev=$dev");
}
function quotasave(){
	$dev=$_GET["quotaline"];
	$acl=$_GET["quota"];
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?fstab-quota=yes&quota=$acl&dev=$dev");	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("{need_reboot}");
}

function aclfolders(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$search='%';
	$table="acl_directories";
	$page=1;
	
	
	if($q->COUNT_ROWS($table,"artica_backup")==0){json_error_show("No data...",1);}	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
	
		$searchstring=" AND (`directory` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table`";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	$sql="SELECT *  FROM `$table` WHERE $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_backup");
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show("$q->mysql_error",1);}	
	$q2=new mysql();
	$banned=$tpl->_ENGINE_parse_body("{banned_files}");
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$path=base64_encode($ligne["directory"]);
		$info="&nbsp;";
		$color="black";
		$js="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('samba.acls.php?path=$path');\" style='font-size:14px;text-decoration:underline;color:black'>";
		$delete=imgsimple("delete-24.png","{delete_permissions}","DeleteAclFolder('". base64_encode("{$ligne["directory"]}")."')");
		$aclsTests=unserialize(base64_decode($sock->getFrameWork("cmd.php?path-acls=$path&justdirectoryTests=yes")));
		$info=imgsimple("32-parameters.png","<b>{$ligne["directory"]}</b><hr>{parameters}","Loadjs('samba.acls.php?path=$path');");
		
		
		if($aclsTests[0]=="NO_SUCH_DIR"){
			$info=imgsimple("warning-panneau-32.png");
			$color="#CCCCCC";
			$js=null;
		}

		$fBanned=array();$banned_explain=null;
		$md5=md5(trim($ligne["directory"]));
		$sql="SELECT `files` FROM samba_veto_files WHERE md5path='$md5'";
		
		$results2 = $q2->QUERY_SQL($sql,"artica_backup");
		while ($ligne2 = mysql_fetch_assoc($results2)) {$pattern=trim($ligne2["files"]);if($pattern==null){continue;}if(isset($alredy[$pattern])){continue;}$fBanned[]=$pattern;}
		if(count($fBanned)>0){
			$banned_explain="<div style='font-size:10px'>$banned: ".@implode(", " , $fBanned)."</div>";
		}		
		
		
			$data['rows'][] = array(
			'id' => $ligne['ID'],
			'cell' => array("$info"
			,"<span style='font-size:14px'>$js<code style='font-size:14px;color:$color'>{$ligne["directory"]}</a></code></span>$banned_explain"
			,"<span style='font-size:14px'>&nbsp;-&nbsp;</span>"
			,$delete
			,
			 )
			);			
		
		

	}
	
	
echo json_encode($data);		
}

function aclfolders_delete(){
	$path=base64_decode($_GET["DeleteAclFolder"]);
	$acls=new aclsdirs($path);
	$acls->DeleteAllacls();
}

function aclfolders_rebuild(){
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?acls-rebuild=yes");
	
}


?>