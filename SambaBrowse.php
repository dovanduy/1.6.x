<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.samba.inc');
	include_once('ressources/class.nfs.inc');
	include_once("ressources/class.harddrive.inc");
	
	$users=new usersMenus();
	if(!IsPriv()){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}
	
	if(isset($_GET["jdisk"])){echo jdisk();exit;}
	if(isset($_GET["main_disks_discover"])){echo main_disks_discover();exit;}
	if(isset($_GET["browsedisk_start"])){echo browsedisk_start();exit;}
	if(isset($_POST["dir"])){json_root();exit;}
	if(isset($_GET["TreeRightInfos"])){TreeRightInfos();exit;}
	if(isset($_GET["rmdirp"])){rmdirp();exit;}
	if(isset($_GET["null"])){exit;}
	if(isset($_GET["hidden-add"])){hidden_disk_js();exit;}
	if(isset($_GET["hidden-disk-popup"])){hidden_disk_popup();exit;}
	if(isset($_GET["add-disk-save"])){hidden_disk_save();exit;}
	if(isset($_GET["del-disk-save"])){hidden_disk_delete();exit;}
	$page=CurrentPageName();
	$tpl=new templates();

	$start="Browse();";
	if(isset($_GET["in-front-ajax"])){$start="Browse2();";}	

	$title=$tpl->_ENGINE_parse_body("{browse}"."... {folders}");
	
$html="
var x_DeleteHiddenDisk= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	Loadjs('SambaBrowse.php');
	}	

	
	function DeleteHiddenDisk(disk){
		var XHR = new XHRConnection();
		XHR.appendData('del-disk-save',disk);
		XHR.sendAndLoad('$page', 'GET',x_DeleteHiddenDisk);
	}

	function Browse(){
		LoadWinORG(776,'$page?main_disks_discover=yes&t={$_GET["t"]}&homeDirectory={$_GET["homeDirectory"]}&no-shares={$_GET["no-shares"]}&field={$_GET["field"]}&protocol={$_GET["protocol"]}&no-hidden={$_GET["no-hidden"]}','$title');

	}
	
	function Browse2(){
		$('#BodyContent').load('$page?main_disks_discover=yes&t={$_GET["t"]}&homeDirectory={$_GET["homeDirectory"]}&no-shares={$_GET["no-shares"]}&field={$_GET["field"]}&protocol={$_GET["protocol"]}&no-hidden={$_GET["no-hidden"]}');
	}	
$start
";
	
	
echo $html;	
	
function IsPriv(){
	$users=new usersMenus();
	if($users->AsArticaAdministrator){return true;}
	if($users->AsSambaAdministrator){return true;}
	if($users->AsSystemAdministrator){return true;}
	if($users->AsOrgStorageAdministrator){return true;}
	return false;
	}


function Get_mounted_path($dev,$array){
$regex_pattern="#\/dev\/$dev#";
if(is_array($array)){
while (list ($num, $val) = each ($array) ){
		if(preg_match($regex_pattern,$val["PATH"])){
			return $val["mounted"];
			break;
		}
	}	
	
}}

	
function main_disks_discover(){
	$users=new usersMenus();
	$Disks=$users->disks_size;
	$page=CurrentPageName();
	$sock=new sockets();
	$dd=new harddrive();
	
	$arrayDisks=$dd->getDiskList();
	$html="<tr>";
	if(is_array($arrayDisks)){
	$count=0;$tr=null;
	while (list ($disk, $ARRAY_FINAL) = each ($arrayDisks) ){
					$content=null;		
					
					$path=$ARRAY_FINAL["MOUNTED"];
					if($path=="/boot"){continue;}
					if($path=="/opt/articatech"){continue;}
					if($path=="/usr/share/artica-postfix"){continue;}
					
					if(isset($already[$path])){continue;}
					if($path==null){continue;}
					$already[$path]=true;
					$size=$ARRAY_FINAL["SIZE"];
					$label=$ARRAY_FINAL["LABEL"];
					if($size==null){continue;}
					$pourc=$ARRAY_FINAL["POURC"];
					$js="Loadjs('SambaBrowse.php?jdisk=$disk&mounted=$path&t={$_GET["t"]}&homeDirectory={$_GET["homeDirectory"]}&no-shares={$_GET["no-shares"]}&field={$_GET["field"]}&protocol={$_GET["protocol"]}&no-hidden={$_GET["no-hidden"]}')";
					$disk_name=$disk;
					if(preg_match("#mapper\/.+?\-(.+)#",$disk_name,$re)){
						$disk_name=$re[1];
					}
					
					if(preg_match("#([0-9]+)\s+MB#", $size,$re)){
						$size=$size*1000;
						
						$size=FormatBytes($size);
						$size=str_replace(" ", "&nbsp;", $size);
					}
					
					$dirname=basename($path);
					$bandwith_color="#5DD13D";
					if($pourc>70){$bandwith_color="#F59C44";}
					if($pourc>95){$bandwith_color="#D32D2D";}
					
					$count=$count+1;
					if($count==2){
						$tr="</tr><tr>";
						$count=0;
					}else{
						$tr=null;
					}
					
					$content="($size - $pourc% {used})<br><strong>$path</strong><br><strong>$label</strong>
					<br><div style='margin-top:-10px'>". pourcentage_basic($pourc, $bandwith_color, $size)."</div>";
					
					
					$FINALDISKS[]=Paragraphe32("noacco:$disk_name","$content",$js,"48-hd.png");

	}}
	
	if(is_array($added_disks_array)){
		while (list ($disk, $path) = each ($added_disks_array) ){
			$js="Loadjs('SambaBrowse.php?jdisk=$disk&mounted=$path&t={$_GET["t"]}&homeDirectory={$_GET["homeDirectory"]}&no-shares={$_GET["no-shares"]}&field={$_GET["field"]}&protocol={$_GET["protocol"]}&no-hidden={$_GET["no-hidden"]}')";
			$delete=imgtootltip("ed_delete.gif","{delete} $disk...","DeleteHiddenDisk('$disk')");
			$FINALDISKS[]=Paragraphe32("noacco:$disk","$disk<br>",$js,"48-hd.png",150);
	
	
		}
	}
		
	
	$finalfinal=CompileTr3($FINALDISKS);

	$add_disk=Paragraphe("64-hd-plus.png","{invisible_disk}","{add_invisible_disk_text}","javascript:Loadjs('$page?hidden-add=yes')");
	
	$html="
	<table style='width:100%'>
	<tr>
		<td style='width:100%'><div style='font-size:18px'>{select_disk}</div></td>
		<td>
	<table style='width:5%'>
	<tr>
		<td width=1%>". imgtootltip("32-hd-plus.png","{add_invisible_disk_text}","Loadjs('$page?hidden-add=yes')")."</td>
		<td width=99% nowrap><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$page?hidden-add=yes')\" style='font-size:14px;text-decoration:underline'>{invisible_disk}</a></td>
	</tr>
	</table>
	</td>
	</tr>
	</table>
$finalfinal
	
	
	";
	$tpl=new templates();
	return $tpl->_ENGINE_parse_body($html,'fileshares.index.php');
	
	
}

function hidden_disk_delete(){
	$disk=$_GET["del-disk-save"];
	$sock=new sockets();
	$contents=$sock->GET_INFO('HiddenDisksList');
	$tbl=explode("\n",$contents);
	if(is_array($tbl)){
		while (list ($num, $line) = each ($tbl) ){
			if($line==null){continue;}
			$added_array=explode(";",$line);
			$added_disks_array[$added_array[0]]=$added_array[1];
		}
	}
		
	unset($added_disks_array[$disk]);
	if(is_array($added_disks_array)){
		while (list ($num, $line) = each ($added_disks_array) ){
			$html=$html."$num;$line\n";
		}
		
	}
	$sock->SaveConfigFile($html,"HiddenDisksList");
	
}

function hidden_disk_save(){
	
	$sock=new sockets();
	$contents=$sock->GET_INFO('HiddenDisksList');
	$tbl=explode("\n",$contents);
	if(is_array($tbl)){
		while (list ($num, $line) = each ($tbl) ){
			if($line==null){continue;}
			$arry[$line]=$line;
		}
	}
	
	$arry["{$_GET["add-disk-save"]};{$_GET["add-disk-path"]}"]="{$_GET["add-disk-save"]};{$_GET["add-disk-path"]}";
	
	while (list ($num, $line) = each ($arry) ){
		$fin[]=$line;
	}	
	
	$sock->SaveConfigFile(implode("\n",$fin),"HiddenDisksList");
	
	
}

function hidden_disk_popup(){
	
	
	$form="<table style='width:100%'>
	<tr>
		<td class=legend>{disk_name}:</td>
		<td>". Field_text("disk_name",null,'width:120px')."</td>
	</tr>
	<tr>
		<td class=legend>{path}:</td>
		<td>". Field_text("disk_path","/",'width:120px')."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><input type='button' OnClick=\"javascript:AddDIskButton();\" value='{add}&nbsp;&raquo;'></td>
	</tr>
	</table>	
	";
	
	$html="<h1>{invisible_disk}</H1>
	<div class=explain>{add_invisible_disk_text}</div>
	<table style='width:100%'>
	<tr>
		<td valign='top'><img src='img/64-hd-plus.png'></td>
		<td valign='top'><div id='hidden_disk_popup'>$form</div></td>
	</tr>
	</table>
	
	
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}


function hidden_disk_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{invisible_disk}');
	
	$html="
	YahooWin(400,'$page?hidden-disk-popup=yes','$title');
	
var x_AddDIskButton= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	Loadjs('SambaBrowse.php');
	YahooWinHide();
	}	
	
	function AddDIskButton(){
		var XHR = new XHRConnection();
			XHR.appendData('add-disk-save',document.getElementById('disk_name').value);
			XHR.appendData('add-disk-path',document.getElementById('disk_path').value);
			document.getElementById('hidden_disk_popup').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>';	
			XHR.sendAndLoad('$page', 'GET',x_AddDIskButton);
	
	}
	
	";
	
	echo $html;
}


function jdisk(){
	$page=CurrentPageName();
	$mounted=$_GET["mounted"];
	
	if($_GET["without-start"]=='yes'){
		$loadyahoo="LoadWinORG(650,'$page?null=yes');";
	}
	
	if($_GET["t"]<>null){
		$return_f=$_GET["t"];
	}else{
		$return_f="TreeSelectedFolder";
	}
	
	$js_add=file_get_contents("js/samba.js");
	
	$html="

function initTree(){
			$('#folderTree').fileTree({ 
					root: '$mounted', 
					script: '$page?mounted=$mounted&t={$_GET["t"]}&homeDirectory={$_GET["homeDirectory"]}&no-shares={$_GET["no-shares"]}&field={$_GET["field"]}&protocol={$_GET["protocol"]}&no-hidden={$_GET["no-hidden"]}', 
					folderEvent: 'click', 
					expandSpeed: 750, 
					collapseSpeed: 750, 
					expandEasing: 'easeOutBounce', 
					collapseEasing: 'easeOutBounce' ,
					multiFolder: false}, function(file) { 
					TreeClick(file);
				});
	}
	
var x_TreeFolders= function (obj) {
       document.getElementById('WinORG').innerHTML=obj.responseText;
       if(!document.getElementById('folderTree')){
       		alert('unable to stat document.folderTree item !');
		}
       
	initTree();
		
}

function TreeClick(branch){
     var branch_id=branch;
     if(document.getElementById('TreeRightInfos')){
        LoadAjax('TreeRightInfos','$page?TreeRightInfos='+branch_id+'&t={$_GET["t"]}&homeDirectory={$_GET["homeDirectory"]}&no-shares={$_GET["no-shares"]}&field={$_GET["field"]}&protocol={$_GET["protocol"]}&no-hidden={$_GET["no-hidden"]}');
     }else{
    	alert('Unable to stat document.TreeRightInfos'); 
	}
        
     return true;   
}

function SelectPath(p){

  if(document.getElementById('restore_path')){
  	document.getElementById('restore_path').value=p;
  	YAHOO.example.container.dialog4.hide();
  	return false;
  }

  if(document.getElementById('$return_f')){
  	document.getElementById('$return_f').value=p;
  	YAHOO.example.container.dialog4.hide();
  }

}

function SmbAddSubFolder(){
      page=CurrentPageName();
      var text=document.getElementById('give_folder_name').value;
      var base=document.getElementById('YahooBranch').value;
      mem_branch_id=document.getElementById('BranchID').value;
      var newfolder=prompt(text + '\"'+base+'\"','New folder');
      if(newfolder){
 		 AnimateDiv('TreeRightInfos');
        var XHR = new XHRConnection();
        mem_item=base + '/'+newfolder;
        XHR.appendData('mkdirp',base + '/'+newfolder);
        XHR.sendAndLoad('samba.index.php', 'GET',x_SmbShare);
        }       
}


var x_SmbDelSubFolder= function (obj) {
    initTree();
    document.getElementById('TreeRightInfos').innerHTML='';
    
}

function SmbDelSubFolder(){
      
      var base=document.getElementById('YahooBranch').value;
      var text=document.getElementById('del_folder_name').value+'\\n'+base;
      mem_branch_id=document.getElementById('BranchID').value;
      if(confirm(text)){
        var XHR = new XHRConnection();
        mem_item=base;
         AnimateDiv('TreeRightInfos');
        
        XHR.appendData('rmdirp',base);
        XHR.sendAndLoad('$page', 'GET',x_SmbDelSubFolder);
        }              
        
}

var x_SmbShare= function (obj) {
 	text=obj.responseText;
 	if(text.length>0){
 		alert(text);
	}
 	initTree();
 	TreeClick(mem_item);
 	document.getElementById('TreeRightInfos').innerHTML='';
 	$('#SAMBA_TABLE_SHARED_LIST').flexReload();
 	
 	
	}
	
	
function SmbShare(){
	  var base=document.getElementById('YahooBranch').value;
      var text=document.getElementById('share_this').value+'\\n'+base;
      mem_item=base;
 	if(confirm(text)){
	 		 AnimateDiv('TreeRightInfos');
	        var XHR = new XHRConnection();
	        XHR.appendData('AddTreeFolders',base);
	        XHR.sendAndLoad('samba.index.php', 'GET',x_SmbShare);
        }          
 }
 
 
 function NFSShare(){
 	  Loadjs('nfs.index.php?share-dir='+document.getElementById('YahooBranch').value);
 
}

function UnShare(head){
	  var base=document.getElementById('YahooBranch').value;
      mem_branch_id=document.getElementById('BranchID').value;
      var text=document.getElementById('unshare_this').value;
 	if(confirm(text)){
        var XHR = new XHRConnection();
        mem_item=base;
        XHR.appendData('FolderDelete',head);
        AnimateDiv('TreeRightInfos');
        XHR.sendAndLoad('samba.index.php', 'GET',x_SmbShare);
        }          
 }
 
 
 function SelectThisFolder(path){
 
 	if(document.getElementById('homeDirectory')){
 		document.getElementById('homeDirectory').value=path;
 		WinORGHide();
	
 	}
 
 }
 
 function SelectThisFolderByField(path,field){
 	if(document.getElementById(field)){
 		
 		document.getElementById(field).value=path;
 		WinORGHide();
 	}else{
 		alert(field+':missing');
 	}
 }


 



	$loadyahoo
	if(!WinORGOpen()){LoadWinORG(650,'$page?null=yes');}
	var XHR = new XHRConnection();
	XHR.appendData('browsedisk_start','yes');
	XHR.sendAndLoad('$page', 'GET',x_TreeFolders); 

	
	$js_add	";
	
echo $html;	
	
}

function browsedisk_start(){
	$html="
	<table style='width:100%'>
	<tr>
	<td valign='top'>
		<div style='overflow:auto;height:500px;width:100%'>
			<div id='folderTree' class=form style='width:95%'></div>
		</div>
	</td>
	<td valign='top'>
	
	<div id='TreeRightInfos'></div></td>
	</tr>
	</table>
	";
	$tpl=new templates();
	return  $tpl->_ENGINE_parse_body($html);
	
}

function json_root($path=null){
	
	$samba=new samba();
	$nfs=new nfs();
	$tpl=new templates();
	$settings=html_entity_decode($tpl->_ENGINE_parse_body('{select_this_item}'));
	$directory=html_entity_decode($tpl->_ENGINE_parse_body('{directory}'));
	$datas=null;
	$path=$_POST["dir"];
	echo "<ul class=\"jqueryFileTree\" style=\"display: none;\">";
	$page=CurrentPageName();
	
	$sock=new sockets();
	if($path==null){
		$datas=$sock->getFrameWork("system.php?dirdir=".base64_encode("/"));}
	else{
		$datas=$sock->getFrameWork("system.php?dirdir=".base64_encode($path));
		
	}
	$tbl=unserialize(base64_decode($datas));
	if(!is_array($tbl)){return null;}
	echo "<li class=\"file ext_settings\"><a href=\"#\" rel=\"" . htmlentities($_POST['dir']) . "\">". htmlentities("$directory ".basename($_POST['dir'])." - $settings")."</a></li>";
	while (list($num,$val)=each($tbl)){
		if(trim($val)==null){continue;}
			$val=basename($val);
			$newpath="$path/$val";
			$newpathsmb=str_replace('//','/',$newpath);
			if(trim($_GET["no-hidden"])<>'yes'){if(Folders_interdis($newpathsmb)){continue;}}
			
			if($samba->main_shared_folders[$newpathsmb]<>null){
				writelogs("samba ? $newpathsmb:{$samba->main_shared_folders[$newpathsmb]}==folder-shared.gif",__FUNCTION__,__FILE__);
				echo "<li class=\"directory collapsed directorys\"><a href=\"#\" rel=\"" . htmlentities($_POST['dir'] . "/$val") . "/\">" . htmlentities($val) . "</a></li>";
				continue;
			}
			
			if(is_array($nfs->main_array[$newpathsmb])){
				echo "<li class=\"directory collapsed directorys\"><a href=\"#\" rel=\"" . htmlentities($_POST['dir'] . "/$val") . "/\">" . htmlentities($val) . "</a></li>";
				continue;
			}
			
			echo "<li class=\"directory collapsed\"><a href=\"#\" rel=\"" . htmlentities($_POST['dir'] . '/'.$val) . "/\">" . $val . "</a></li>";
			
			
		}
		
}

function TreeRightInfos(){
	$path=$_GET["TreeRightInfos"];
	$path=str_replace('//','/',$path);
	$f=basename($path);
	if(substr($path,strlen($path)-1,1)=='/'){$path=substr($path,0,strlen($path)-1);}
	
	

	if($_GET["homeDirectory"]<>"yes"){
	$users=new usersMenus();
	if($users->NFS_SERVER_INSTALLED){
			$nfs="
			<tr><td colspan=2><hr></td></tr>
			<tr ".CellRollOver('NFSShare()').">
				<td width=1% valign='top'>" . imgtootltip('folder-32-share.png','{share_this_NFS}',"")."</td>
				<td style='font-size:16px'>{share_this_NFS}</td>	
			</tr>";
		}		
	
	
	if($users->SAMBA_INSTALLED){
		$shareit="
			<tr><td colspan=2><hr></td></tr><tr ".CellRollOver('SmbShare()').">
				<td width=1%>" . imgtootltip('folder-32-share.png','{share_this}',"")."</td>
				<td style='font-size:16px'>&nbsp;{share_this}</td>	
			</tr>";
		
		$smb=new samba();
		if($smb->main_shared_folders[$path]<>null){
			$txt="<tr><td colspan=2><div class=explain><b>$path</b>&nbsp;({$smb->main_shared_folders[$path]})&nbsp;:{FOLDER_IS_SHARED}</div></td></tr>";
			$unshare="<tr><td colspan=2><hr></td></tr>$txt<tr ".CellRollOver("UnShare('{$smb->main_shared_folders[$path]}')").">
					<td width=1% valign='top'>" . imgtootltip('folder-32-share-delete.png','{delete_share}',"")."</td>
					<td style='font-size:16px'>{delete_share}</td>	
				</tr>";	
			
			
			$shared_priv="<tr ".CellRollOver("FolderProp('{$smb->main_shared_folders[$path]}')").">
					<td width=1% valign='top'>" . imgtootltip('folder-user2-32.png','{privileges_settings}',"")."</td>
					<td style='font-size:16px'>{privileges}</td>	
				</tr>";
	
			$shareit=$unshare;
			}
		}
	}
	
	if($_GET["homeDirectory"]=='yes'){
		
		$select="<tr ".CellRollOver("SelectThisFolder('$path')").">
				<td width=1% valign='top'>" . imgtootltip('arrow-right-32.png','{select_this_folder}',"")."</td>
				<td style='font-size:16px' nowrap>{select_this_folder}</td>	
			</tr>";
		
	}
	

	if($_GET["no-shares"]<>null){
		$shareit=null;
		$nfs=null;
		if($_GET["protocol"]=="yes"){$path="dir:$path";}
		$select="<tr ".CellRollOver("SelectThisFolderByField('$path','{$_GET["field"]}')").">
				<td width=1% valign='top'>" . imgtootltip('arrow-right-32.png','{select_this_folder}',"")."</td>
				<td style='font-size:16px'>{select_this_folder}</td>	
			</tr>";
		
		
	}
	
	
	$len=strlen($f);
	$h=5;
	if($len>30){$h="6";}
	$html="

	<input type='hidden' id='protocol' value='{$_GET["protocol"]}'>
	<input type='hidden' id='BranchID' value='{$_GET["TreeRightInfos"]}'>
	<input type='hidden' id='YahooBranch' value='$path'>
	<input type='hidden' id='give_folder_name' value='{give_folder_name}'>
	<input type='hidden' id='del_folder_name' value='{del_folder_name}'>
	<input type='hidden' id='share_this' value='{share_this}'>
	<input type='hidden' id='unshare_this' value='{unshare_this}'>
	<div style='width:240px'>
	<div style='font-size:16px;font-weight:bold'>&laquo;$f&raquo;</div>

	<table style='width:99%' class=form>
		<tr ".CellRollOver('SmbAddSubFolder()').">
		<td width=1% valign='top'>
		" . imgtootltip('folder-32-add.png','{add_sub_folder}',"")."</td>
		<td style='font-size:16px' nowrap>{add_sub_folder}</td>
		</tr>
		<tr ".CellRollOver('SmbDelSubFolder()').">
		<td width=1% valign='top'>
		" . imgtootltip('folder-delete-32.png','{del_sub_folder}',"")."</td>
		<td style='font-size:16px'>{del_sub_folder}</td>	
		</tr>
		$select
		$shareit		
		$shared_priv
		$nfs
	</table>
	</div>
	<center style='margin-top:14px;padding-top:5px;padding-bottom:5px;border-top:1px solid #CCCCCC;border-bottom:1px solid #CCCCCC'><code style='font-size:14px'>$path</code></center>
	";
	
$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("$html",'fileshares.index.php');	
}

function rmdirp(){
	$user=new usersMenus();
	if(!$user->AsSambaAdministrator){return null;}
	if(Folder_to_not_remove($_GET["rmdirp"])){return null;}
	$sock=new sockets();
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body(base64_decode($sock->getFrameWork("cmd.php?folder-remove=".base64_encode($_GET["rmdirp"]))));
	}
	
function Folders_interdis($folder){
	if(!isset($_SESSION[__FUNCTION__])){
		$disk=new harddrive();
		$array=$disk->Folders_interdis();
		$_SESSION[__FUNCTION__]=$array;
	}else{
		$array=$_SESSION[__FUNCTION__];
	}

	
	if(!$array[$folder]){return false;}else{return true;}
	
	
}

function Folder_to_not_remove($folder){
	if(Folders_interdis($folder)){return true;}
	
	$l["/home"]=true;
	if(!$l[$folder]){return false;}else{return true;}
}	

	
	


?>