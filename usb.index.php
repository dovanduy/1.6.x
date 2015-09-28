<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.os.system.inc');
	include_once('ressources/class.autofs.inc');
	include_once('ressources/class.samba.inc');

	
	$user=new usersMenus();
	if($user->blkid_installed==false){header('location:users.index.php');die();}
	if(($user->AsSystemAdministrator==false) OR ($user->AsSambaAdministrator==false)) {die();}
	
	
	if(isset($_GET["uuid-infos"])){uuid_js();exit;}
	if(isset($_GET["uuid-popup"])){uuid_popup();exit;}
	
	
	if(isset($_GET["js"])){js();exit;}
	if(isset($_GET["usb_infos"])){main_usb_infos();exit;}
	if(isset($_GET["main"])){main_switch();exit;}
	if(isset($_GET["script"])){main_switch_scripts();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["format-index"])){format_js();exit;}
	if(isset($_GET["format-index-popup"])){format_popup();exit;}
	if(isset($_GET["format_type"])){format_operation();exit;}
	if(isset($_GET["change-label-js"])){change_label_js();exit;}
	if(isset($_GET["change-label-popup"])){change_label_popup();exit;}
	if(isset($_GET["change-label-perform"])){change_label_perform();exit;}
	
	//umount=yes&uuid=$UUID
	if(isset($_GET["umount"])){umount_js();exit;}
	if(isset($_GET["umount-index-popup"])){umount_popup();exit;}
	if(isset($_GET["umount-mounted"])){umount_mounted();exit;}
	
	//mount=yes&uuid=$UUID&mounted=$path&type=$TYPE
	if(isset($_GET["mount"])){mount_js();exit;}
	if(isset($_GET["mount-index-popup"])){mount_popup();exit;}
	if(isset($_GET["mount-mounted"])){mount_mounted();exit;}
	
	if(isset($_GET["automount-js"])){automount_js();exit;}
	if(isset($_GET["automount-popup"])){automount_popup();exit;}
	if(isset($_GET["automount-add"])){automount_add();exit;}
	if(isset($_GET["automount-del"])){automount_del();exit;}
	if(isset($_GET["automount-table"])){automount_list();exit;}
	if(isset($_GET["automount-list"])){automount_table_list();exit;}
	
	
	
		main_page();
		
		
		
function change_label_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{change_label}');
	
	$html="
	
function LoadMainLabel(){
	YahooWin6('320','$page?change-label-popup={$_GET["uuid"]}','$title {$_GET["uuid"]}');
	}	
	
var x_ChangeUsbLabelEdit=function (obj) {
	var results=obj.responseText;
	if (results.length>0){
		alert(results);
		
	}
	LoadMainLabel();
	if(document.getElementById('usblistbrowse')){
		LoadAjax('usblistbrowse','usb.browse.php?usblist=yes');
	}	
	if(document.getElementById('NewusbForm2009')){
		Loadjs('usb.index.php?uuid-infos={$_GET["uuid"]}');
	}		
}
	

function ChangeUsbLabelEdit(){
		var XHR = new XHRConnection();
		XHR.appendData('change-label-perform','{$_GET["uuid"]}');
		XHR.appendData('uuid',document.getElementById('uusb_label').value);
		document.getElementById('change_label_popup').innerHTML='<center style=\"width:100%\"><img src=img/wait_verybig.gif></center>';
		XHR.sendAndLoad('$page', 'GET',x_ChangeUsbLabelEdit);
	}

	LoadMainLabel();
	
";

echo $html;	
	
	
}
function automount_js(){
	//s&uuid=$uuid&dev=$usb->path&type=$usb->TYPE
	$usersmenus=new usersMenus();
	
	if(!$usersmenus->autofs_installed){
			$tpl=new templates();
			$error="{ERROR_NO_AUTOFS}";
			echo $tpl->_ENGINE_parse_body("alert('$error')");
			die();	
	}	
	
	$uuid=$_GET["uuid"];
	$page=CurrentPageName();
	$prefix=str_replace('.','_',$page."automount".$uuid);
	$prefix=str_replace('-','',$prefix);
	
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{automount}");
	
	$html="
		function {$prefix}LoadPage(){
			YahooWin6(550,'$page?automount-popup=$uuid&t={$_GET["t"]}&t2={$_GET["t2"]}&t3={$_GET["t3"]}','$title');
		
		}
		

		
		
	{$prefix}LoadPage();
	";
	
	echo $html;		
	}
	
function automount_popup(){
	$uuid=$_GET["automount-popup"];
	$usb=new usb($uuid);
	$autfs=new autofs($uuid);
	$md=md5($uuid);
	$page=CurrentPageName();
	$md5=md5($uuid.time());
	$html="
	<div class=explain>{automount_explain_text}</div>
	<div id='autofs_div'>
	<table style='width:99%' class=form>
	<tr>
		<td valign='top' colspan=3>".automount_status()."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{auto_folder}:</td>
		<td valign='top'>". Field_text("autofolder-$md","","font-size:14px;width:210px",null,null,null,false,"AddAutoFSCheck$md(event)")."</td>
		<td align='right'>". button("{add}","AddAutoFS$md()","16px")."</td>
	</tr>
	</table>
	<br>
	<div style='width:100%;' id='$md5'></div>
	</div>
	<script>
		function AddAutoFSCheck$md(e){
			if(checkEnter(e)){AddAutoFS$md();}
		}
	
		
		var x_AddAutoFS$md=function (obj) {
			var results=obj.responseText;
			if (results.length>0){alert(results);}	
			if(document.getElementById('flexRT{$_GET["t"]}')){ $('#flexRT{$_GET["t"]}').flexReload(); }
			if(document.getElementById('flexRT{$_GET["t2"]}')){ $('#flexRT{$_GET["t2"]}').flexReload(); }
			if(document.getElementById('flexRT{$_GET["t3"]}')){ $('#flexRT{$_GET["t3"]}').flexReload(); }
			$('#flexRT$md').flexReload();
		}
		
		
		function AddAutoFS$md(){
			var XHR = new XHRConnection();
			XHR.appendData('automount-add','$uuid');
			XHR.appendData('automount-folder',document.getElementById('autofolder-$md').value);
			XHR.sendAndLoad('$page', 'GET',x_AddAutoFS$md);
		
		}
		
		function DelAutoFS$md(folder,uuid){
			var XHR = new XHRConnection();
			XHR.appendData('automount-del',uuid);
			XHR.appendData('automount-folder',folder);
			XHR.sendAndLoad('$page', 'GET',x_AddAutoFS$md);		
		}		
		
	
		LoadAjaxTiny('$md5','$page?automount-table=yes&uuid=$uuid');
	</script>
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}

function automount_status(){
	$sock=new sockets();
	$datas=$sock->getfile('autofsStatus');
	$ini=new Bs_IniHandler();
	$ini->loadString($datas);
	return DAEMON_STATUS_ROUND("APP_AUTOFS",$ini);
}

function automount_list(){
	$uuid=$_GET["uuid"];
	$tpl=new templates();
	$page=CurrentPageName();
	$usb=new usb($uuid);
	$title=$tpl->_ENGINE_parse_body("{automount} $usb->LABEL ID: $usb->uuid");
	$md=md5($uuid);
	$html="
	
<table class='flexRT$md' style='display: none' id='flexRT$md' style='width:100%'></table>
<script>
var IDTMP=0;
$(document).ready(function(){
$('#flexRT$md').flexigrid({
	url: '$page?automount-list=yes&uuid=$uuid',
	dataType: 'json',
	colModel : [
		{display: '$path', name : 'path', width :115, sortable : false, align: 'left'},
		{display: '$info', name : 'flags', width :321, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'Out', width : 31, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
		{display: '$path', name : 'path'},
		],
	sortname: 'ID',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 520,
	height: 180,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});
</script>
";
	echo $html;

	
	
}
function automount_table_list(){
	$uuid=$_GET["uuid"];
	$mdmd=md5($uuid);
	$autfs=new autofs($uuid);
	$array=$autfs->list_byuuid($uuid);	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();	
	$c=0;
	while (list ($num, $line) = each ($array) ){
		$md=md5($line);
		$delete=imgsimple("delete-24.png","","DelAutoFS$mdmd('$num','$uuid')");
		$c++;
		$data['rows'][] = array(
			'id' => $md,
			'cell' => array(
			"<span style='font-size:14px;'>$num</span>",
			"<span style='font-size:14px'>$line</span>",
			$delete
		)
		);			
		
	}
	$data['total'] = $c;
	echo json_encode($data);
	
}



function automount_add(){
	$uuid=$_GET["automount-add"];
	$mount=basename($_GET["automount-folder"]);
	$usb=new usb($uuid);
	$autfs=new autofs($uuid);
	$autfs->by_uuid_addmedia($mount,$usb->ID_FS_TYPE);
	}
function automount_del(){
	$uuid=$_GET["automount-del"];
	$folder=$_GET["automount-folder"];
	$autfs=new autofs($uuid);
	$autfs->by_uuid_removemedia($folder,$uuid);
	
}


function uuid_js(){
	$uuid=$_GET["uuid-infos"];
	$page=CurrentPageName();
	$prefix=str_replace('.','_',$page.$uuid);
	$prefix=str_replace('-','',$prefix);
	$title=$uuid;
	
	$html="
		function {$prefix}LoadPage(){
			YahooLogWatcher(750,'$page?uuid-popup=$uuid&t={$_GET["t"]}&t2={$_GET["t2"]}&t3={$_GET["t3"]}','$title');
		
		}
		
		function UUIDINDEXPOPREFRESH(){
			YahooLogWatcher(750,'$page?uuid-popup=$uuid&t={$_GET["t"]}&t2={$_GET["t2"]}&t3={$_GET["t3"]}','$title');
		}
		
		
	{$prefix}LoadPage();
	";
	
	echo $html;	
}

function uuid_popup(){
	$uuid=$_GET["uuid-popup"];
	$usb=new usb($uuid);

	
$format=Paragraphe("format-64.png","{format_device}","{format_device_explain}",
"javascript:Loadjs('usb.index.php?format-index=yes&dev=$usb->path')");
$rename=Paragraphe("rename-disk-64.png","{change_label}","{change_label_explain}","javascript:Loadjs('usb.index.php?change-label-js=yes&uuid=$uuid')");
$mount=Paragraphe("usb-mount-64.png","{mount}","{mount_explain}","javascript:Loadjs('usb.index.php?mount=yes&uuid=$uuid&mounted=$usb->path&type=$usb->TYPE')");

$browse=Paragraphe("browse-64-grey.png","{browse}","{browse_usb_device}","");
$share=Paragraphe("usb-share-64-grey.png","{usb_share}","{share_this_device_text}","");



$users=new usersMenus();
if($users->autofs_installed){
	$automount=Paragraphe("usb-automount-64.png","{automount}",
	"{automount_explain}","javascript:Loadjs('usb.index.php?automount-js=yes&uuid=$uuid&dev=$usb->path&type=$usb->TYPE&t={$_GET["t"]}&t2={$_GET["t2"]}&t3={$_GET["t3"]}')");
	
}

	if($usb->mounted<>null){
			$mount=Paragraphe("usb-umount-64.png","{umount}","{umount_explain}","javascript:Loadjs('usb.index.php?umount=yes&uuid=$uuid&mounted=$usb->mounted&t={$_GET["t"]}&t2={$_GET["t2"]}&t3={$_GET["t3"]}')");
			$js_brows="Loadjs('SambaBrowse.php?jdisk=disk&mounted=$usb->mounted&t=&homeDirectory=&no-shares=yes&field={$uuid}_stick_folder&without-start=yes&t={$_GET["t"]}&t2={$_GET["t2"]}&t3={$_GET["t3"]}')";
			$js_brows="Loadjs('tree.php?mount-point=$usb->mounted&t={$_GET["t"]}&t2={$_GET["t2"]}&t3={$_GET["t3"]}')";
			$browse=Paragraphe("browse-64.png","{browse}","{browse_usb_device}","javascript:$js_brows");
			
	}
			
	if($users->SAMBA_INSTALLED){
		$samba=new samba();
		$share=Paragraphe("usb-share-64.png","{usb_share}","{share_this_device_text}","javascript:Loadjs('usb.share.php?uuid=$uuid')");
		$folder_name=$samba->GetShareName("/media/$uuid");
		if($folder_name<>null){
			$share=Paragraphe("disk_share_enable-64.png","{smb_infos}","{folder_properties}","javascript:FolderProp('$folder_name');");	
		}
		
		
	}
	
	
	
	$html="
				<input type='hidden' id='{$uuid}_stick_folder' value=''>
				<input type='hidden' id='NewusbForm2009' value='$uuid'>
				<input type='hidden' id='{$uuid}_stick_mounted' value='$usb->mounted'>	
	<center>
	<table style='width:99%' class=form>
	<tr>
	<td>
	<table style='width:100%'>
	<tr>
			<td class=legend style='font-size:13px'>{label}:</td>
			<td><strong style='font-size:13px'>$usb->LABEL</strong></td>
			<td><strong style='font-size:13px'>|</strong></td>
			<td class=legend style='font-size:13px'>{manufacturer}:</td>
			<td><strong style='font-size:13px'>$usb->vendor</strong></td>			
			<td><strong style='font-size:13px'>|</strong></td>
			<td class=legend style='font-size:13px'>{path}:</td>
			<td><strong style='font-size:13px'>$usb->path ($usb->ID_FS_TYPE)</strong></td>
			<td><strong style='font-size:13px'>|</strong></td>
			<td class=legend  style='font-size:13px'>{size}:</td>
			<td><strong  style='font-size:13px'>$usb->size ($usb->pourc%)</strong></td>
			</tr>
	</table>
	<table style='width:100%'>		
			<tr>
				<td class=legend style='font-size:13px'>{mounted}:</td>
				<td><strong style='font-size:13px'>$usb->mounted</strong></td>
				<td><strong style='font-size:13px'>|</strong></td>
			
				<td class=legend style='font-size:13px'>{model}:</td>
				<td><strong style='font-size:13px'>$usb->model</strong></td>
				<td><strong style='font-size:13px'>|</strong></td>
			
				<td class=legend tyle='font-size:13px'>{product}:</td>
				<td><strong tyle='font-size:13px'>$usb->product</strong></td>
			</tr>	
	</table>
	</td>
	</tr>
	</table>
	</center>
<center style='margin-top:10px'>
	<table style='width:99%' class=form>
	<tr>
		<td valign='top'>$mount</td>
		<td valign='top'>$automount</td>
		<td valign='top'>$rename</td>
	</tr>
	<tr>
		<td valign='top'>$browse</td>
		<td valign='top'>$share</td>
		<td valign='top'>$format</td>
		
	</table>
</center>	
	";
	
		
	
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}


function change_label_perform(){
	$uuid=$_GET["change-label-perform"];
	$label=$_GET["uuid"];
	$label=substr($label,0,16);
	$label=str_replace(' ','_',$label);
	$sock=new sockets();
	$sock->getfile("ChangeUSBLabel:$uuid;$label");
	
}

function change_label_popup(){
$sock=new sockets();
	$sock->getFrameWork("cmd.php?usb-scan-write=yes");
	$uuid=$_GET["change-label-popup"];
	if(!file_exists('ressources/usb.scan.inc')){die('ressources/usb.scan.inc !!');}
	include_once('ressources/usb.scan.inc');
	if(!is_array($_GLOBAL["usb_list"])){die('ARRAY! false');}	
	$label=$_GLOBAL["usb_list"][$uuid]["LABEL"];
	$html="<h1>{change_label}</H1>
	<div id='change_label_popup'>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend>{device}:</td>
		<td>$uuid</td>
	</tr>	
	<tr>
		<td class=legend>{label}:</td>
		<td>" . Field_text('uusb_label',$label,"width:90px")."</td>
	</tr>
	<td colspan=2 align='right'>
		<input type='button' OnClick=\"javascript:ChangeUsbLabelEdit();\" value='{apply}&nbsp;&raquo;'>
	</td>
	</tr>
	</table>
	</div>
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
	
}
		
function format_js(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{formatdevice}');
	$warning=$tpl->_ENGINE_parse_body('{format_warning}');
	$warning=str_replace("\n",'\n',$warning);
	$html="
	
function LoadMainFormat(){
	YahooWin6('550','$page?format-index-popup={$_GET["dev"]}','$title {$_GET["dev"]}');
	}	
	
var x_LauchUsbFormat=function (obj) {
	var results=obj.responseText;
	if (results.length>0){
		alert(results);
		
	}
	LoadMainFormat();
	if(document.getElementById('usblistbrowse')){
		LoadAjax('usblistbrowse','usb.browse.php?usblist=yes');
	}	
	if(document.getElementById('NewusbForm2009')){
		Loadjs('usb.index.php?uuid-infos='+document.getElementById('NewusbForm2009').value);
	}			
}
	

function LauchUsbFormat(){
	var ext=document.getElementById('format_type').value;
	var dev=document.getElementById('dev').value;
	if(confirm('$warning\\n'+dev+' -> '+ext)){
		var XHR = new XHRConnection();
		XHR.appendData('format_type',ext);
		XHR.appendData('format_dev',dev);
		document.getElementById('formatdiv').innerHTML='<center style=\"width:100%\"><img src=img/wait_verybig.gif></center>';
		XHR.sendAndLoad('$page', 'GET',x_LauchUsbFormat);
	}
	

}
	
	LoadMainFormat();
	
";

echo $html;
}

function umount_mounted(){
	$sock=new sockets();
	$datas=$sock->getfile("usb_umount:{$_GET["umount-mounted"]}");
	$tb=explode("\n",$datas);
	if(is_array($tb)){
		while (list ($num, $line) = each ($tb) ){
			if(trim($line)==null){continue;}
			$echo=$echo .$line."\n";
		}
	}
echo $echo;
}

function mount_mounted(){
	$sock=new sockets();
	$datas=$sock->getfile("usb_mount:{$_GET["mount-mounted"]};{$_GET["type"]}");
	$tb=explode("\n",$datas);
	if(is_array($tb)){
		while (list ($num, $line) = each ($tb) ){
			if(trim($line)==null){continue;}
			$echo=$echo .$line."\n";
		}
	}
echo $echo;	
}

function umount_popup(){
	
	$html="<H1>{umount}</H1>
	<center style=\"width:100%\">
		<div id='umountdiv'>
		<H2>{waiting}: {umount}<br> {$_GET["mounted"]}...</H2>
		<img src=img/wait_verybig.gif>
		</div>
	</center>
	
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}

function mount_popup(){
	$html="<H1>{mount}</H1>
	<center style=\"width:100%\">
		<div id='mountdiv'>
		<H2>{waiting}: {mount}<br> {$_GET["mounted"]}...</H2>
		<img src=img/wait_verybig.gif>
		</div>
	</center>
	
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
}

function mount_js(){
	////mount=yes&uuid=$UUID&mounted=$path&type=$TYPE
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{mount}');
	$uidd=$_GET["uuid"];
	
	$html="
	var timeout=0;
	
function LoadMainmount(){
	YahooWin6('550','$page?mount-index-popup=$uidd&mounted={$_GET["mounted"]}&type={$_GET["type"]}','$title {$_GET["mounted"]}');
	setTimeout(\"mount_test_load()\",900);
	}	
	
var x_LaunchUsbmount=function (obj) {
	var results=obj.responseText;
	if (results.length>0){
		alert(results);
		
	}
	YahooWin6Hide();
	if(document.getElementById('usblistp')){
		LoadAjax('usblistp','browse.usb.php?external-storage-usb-list=yes');
	}
	
	if(document.getElementById('usblistbrowse')){
		LoadAjax('usblistbrowse','usb.browse.php?usblist=yes');
	}	
	if(document.getElementById('NewusbForm2009')){
		Loadjs('usb.index.php?uuid-infos=$uidd');
	}
	
	
	
}

function mount_test_load(){
	timeout=timeout+1;
	if(timeout>5){
		YahooWin6Hide();
		return false;
	}
	if(!document.getElementById('mountdiv')){
		setTimeout(\"mount_test_load()\",900);
		return false;
	}
	
	LaunchUsbmount();
	
}
	

function LaunchUsbmount(){
		var XHR = new XHRConnection();
		XHR.appendData('mount-mounted','{$_GET["mounted"]}');
		XHR.appendData('type','{$_GET["type"]}');
		XHR.appendData('uuid','{$_GET["uuid"]}');
		XHR.sendAndLoad('$page', 'GET',x_LaunchUsbmount);
	}
	

LoadMainmount();
	
";

echo $html;	
	
}


function umount_js(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{umount}');
	$uidd=$_GET["uuid"];
	
	$html="
	var timeout=0;
	
function LoadMainUmount(){
	YahooWin6('550','$page?umount-index-popup=$uidd&mounted={$_GET["mounted"]}','$title {$_GET["uidd"]}');
	setTimeout(\"umount_test_load()\",900);
	}	
	
var x_LaunchUsbUmount=function (obj) {
	var results=obj.responseText;
	if (results.length>0){
		alert(results);
		
	}
	YahooWin6Hide();
	if(document.getElementById('usblistp')){
		LoadAjax('usblistp','browse.usb.php?external-storage-usb-list=yes');
	}
	
	
	if(document.getElementById('usblistbrowse')){
		LoadAjax('usblistbrowse','usb.browse.php?usblist=yes');
	}	
	if(document.getElementById('NewusbForm2009')){
		Loadjs('usb.index.php?uuid-infos=$uidd');
	}
	

	
}

function umount_test_load(){
	timeout=timeout+1;
	if(timeout>5){
		YahooWin6Hide();
		return false;
	}
	if(!document.getElementById('umountdiv')){
		setTimeout(\"umount_test_load()\",900);
		return false;
	}
	
	LaunchUsbUmount();
	
}
	

function LaunchUsbUmount(){
		var XHR = new XHRConnection();
		XHR.appendData('umount-mounted','{$_GET["mounted"]}');
		XHR.sendAndLoad('$page', 'GET',x_LaunchUsbUmount);
	}
	

LoadMainUmount();
	
";

echo $html;	
	
}

function format_operation(){
	$dev=$_GET["format_dev"];
	$type=$_GET["format_type"];
	$array=ArrayUsbBYDev($dev);
	if($line["mounted"]=='/'){
		echo "$dev -> {not_applicable}";
		return null;
	}
	
	$sock=new sockets();
	$datas=$sock->getfile("FormatDevice:$dev;$type");
	$sock->getFrameWork("cmd.php?usb-scan-write=yes");	
	echo $datas;
	
}


function format_popup(){
	include_once("ressources/class.os.system.tools.inc");
	$array=ArrayUsbBYDev($_GET["format-index-popup"]);
	$od=new os_system();
	$table=$od->usb_parse_array($array);
	
	$format_table=array(
		"ext2"=>"ext2 (linux)",
		"ext3"=>"ext3 (linux)",
		"vfat"=>"fat32 (linux & Windows)",
	
	);
	
	$field=Field_array_Hash($format_table,'format_type','ext2');
	
	$form="
	<table class=form>
	<tr>
		<td class=legend>{file_system}:</td>
		<td>$field</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><input type='button' OnClick=\"javascript:LauchUsbFormat();\" value='{formatdevice}&nbsp;&raquo;'></td>
	</tr>
	</table>
	
	";
	
	$html="<H1>{formatdevice} {$_GET["format-index-popup"]}</H1>
	<div id='formatdiv'>
	<p class=caption>{format_device_explain}</p>
	<form name='ffmformat'>
	<input type='hidden' value='{$_GET["format-index-popup"]}' id='dev' name='dev'>
	<table style='width:99%' class=form>
	<tr>
		<td valign='top'>$table</td>
		<td valign='top'>$form</td>
	</tr>
	</table>
	</div>
	
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}
		
function js(){
	
$page=CurrentPageName();
$tpl=new templates();
$title=$tpl->_ENGINE_parse_body('{APP_USB}');
$otherjs=main_script_load();
$html="
var usb_timerID  = null;
var usb_timerID1  = null;
var usb_tant=0;
var usb_reste=0;

function usb_demarre(){
   if(!YahooWinOpen()){return false;}
   usb_tant = usb_tant+1;
   usb_reste=5-usb_tant;
	if (usb_tant < 5 ) {                           
      usb_timerID = setTimeout(\"usb_demarre()\",3000);
      } else {
               usb_tant = 0;
               usb_ChargeLogs();
               usb_demarre();                                //la boucle demarre !
   }
}


function usb_ChargeLogs(){
	LoadAjax('main_usb_scan','$page?main=yes');
	
	}
$otherjs

function LoadMainPage(){
	YahooWin('700','$page?popup=yes','$title');
	setTimeout(\"usb_ChargeLogs()\",1000);
	setTimeout(\"usb_demarre()\",1000);

}


LoadMainPage();
";

echo $html;
}

function popup(){
$html="
<H1>{APP_USB}</H1>
<p class=caption>{about_usb}</p>
<div id='main_usb_scan' style='width:99%;height:300px;overflow:auto'></div>


";	

$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);
	
}
	
function main_page(){
	
$page=CurrentPageName();
	if($_GET["hostname"]==null){
		$user=new usersMenus();
		$_GET["hostname"]=$user->hostname;}
	
	$html=
"<script language=\"JavaScript\">       
var timerID  = null;
var timerID1  = null;
var tant=0;
var reste=0;

function demarre(){
   tant = tant+1;
   reste=5-tant;
	if (tant < 5 ) {                           
      timerID = setTimeout(\"demarre()\",3000);
      } else {
               tant = 0;
               ChargeLogs();
               demarre();                                //la boucle demarre !
   }
}


function ChargeLogs(){
	LoadAjax('main_config','$page?main=yes');
	}
</script>		
	
	<table style='width:100%'>
	<tr>
	<td width=1% valign='top'><img src='img/bg_usb.png'></td>
	<td valign='top'>
	" . RoundedLightBlue('{about_usb}')."</td>
	</tr>
	<tr>
		<td colspan=2 valign='top'>
			<div id='main_config'></div>
		</td>
	</tr>
	</table>
	<script>demarre();ChargeLogs();Loadjs('$page?script=load_functions')</script>
	
	";
	
	
	
	
	
	
	
	$tpl=new template_users('{APP_USB}',$html,0,0,0,0,$cfg);
	
	echo $tpl->web_page;
	
	
	
}


function main_script_load(){
	$page=CurrentPageName();
	$tpl=new templates();
	$warnautoback=$tpl->_ENGINE_parse_body('{warnautoback}');
	$warnautobackremove=$tpl->_ENGINE_parse_body('{warnautobackremove}');
	$html="
	
	var warnautoback='$warnautoback';
	var warnautobackremove='$warnautobackremove';
	
	function usb_show_details(uuid){
		YahooWin2(375,'$page?usb_infos='+uuid,uuid);
	
	}
	
	function usb_dismount(uuid,mount_point,tt){
		YahooWin2(375,'$page?usb_infos='+uuid+'&dismount=yes&mntp='+mount_point,uuid);
	
	}
	
	function usb_mount(uuid,dev,tt){
		YahooWin2(375,'$page?usb_infos='+uuid+'&mount=yes&dev='+dev+'&tt='+tt,uuid);
	
	}	
	
	function usb_add_autoback(uuid){
		var a;
		
		a=confirm(warnautoback);
		if(a){YahooWin2(375,'$page?usb_infos='+uuid+'&autoback=yes',uuid);}
	}
	
	function usb_remove_autoback(uuid){
		var a;
		a=confirm(warnautobackremove);
		if(a){YahooWin2(375,'$page?usb_infos='+uuid+'&removeautoback=yes',uuid);}
			
		}
	";
	
	
	return  $tpl->_ENGINE_parse_body($html);
	
	
}

function main_switch(){
	
	switch ($_GET["main"]) {
		case "yes":main_config();exit;break;
	
		default:
			break;
	}
	
	
}


function main_switch_scripts(){
	switch ($_GET["script"]) {
		case "load_functions":echo main_script_load();exit;break;
	
		default:
			break;
	}
	
}


function ArrayUsbBYDev($dev){
$artica=new artica_general();
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?usb-scan-write=yes");
	if(!file_exists('ressources/usb.scan.inc')){return null;}
	include_once('ressources/usb.scan.inc');
	if(!is_array($_GLOBAL["usb_list"])){return null;}	
	while (list ($num, $line) = each ($_GLOBAL["usb_list"])){
		$path=$line["PATH"];
		if($path==$dev){return $line;}
		
	}
	
}

function main_config(){
	$artica=new artica_general();
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?usb-scan-write=yes");
	if(!file_exists('ressources/usb.scan.inc')){return null;}
	include_once('ressources/usb.scan.inc');
	if(!is_array($_GLOBAL["usb_list"])){return null;}
	

	
	while (list ($num, $line) = each ($_GLOBAL["usb_list"])){
		$uid=$num;
		$path=$line["PATH"];
		$LABEL=trim($line["LABEL"]);
		$TYPE=$line["TYPE"];
		$SEC_TYPE=$line["SEC_TYPE"];
		$line["AA"]=$artica->ArticaUsbBackupKeyID;
		$model=$line["ID_MODEL"];
		$vendor=$line["ID_VENDOR"];
		$mounted=$line["mounted"];
		
	$tbl=explode(";",$line["model"]);
		if(is_array($tbl)){
			$product=$tbl[2];
			$manufacturer=$tbl[3];
			$id=$tbl[4];
			$speed=$tbl[5];
		}	
		
		$tbl=explode(";",$line["SIZE"]);
		if(is_array($tbl)){
			$size=$tbl[0];
			$used=$tbl[1];
			$free=$tbl[2];
			$pouc_occ=$tbl[3];
		}
		
		
		
		$img="usb-64-red.png";
		$js="usb_show_details('$num');";
		
		if(strlen($mounted)>0){
			$img="usb-64-green.png";
			$tips="click_to_edit";
		}
		
	if($TYPE==null){
		$TYPE=$array["ID_FS_TYPE"];
	}		
		
		if($LABEL==null){$LABEL="$model";}
		if(($mounted=="/") or ($mounted=="/swap") or ($TYPE=='swap')){
			$img="usb-64-grey.png";
			$tips="not_applicable";
			$js=null;
		}else{
			$img=usb_image($line);
		}
		$mounted_text=$mounted;
		if(strlen($mounted_text)>13){$mounted_text=texttooltip(substr($mounted_text,0,10).'...',$mounted_text,null,null,1);}
		
		if(strlen($model)>13){$model=texttooltip(substr($model,0,10).'...',$model,null,null,1);}
		$text="
		<table>
		<tr>
		<td valign='top' width=1%>
			" . imgtootltip($img,"{{$tips}}",$js)."</td>
		<td valign='top'>
				<table style='width:99%'>
			<tr>
					<td class=legend>{file_system}:</strong></td>
					<td align='left'>$TYPE</td>
				</tr>				
				<tr>
					<td class=legend>{vendor}:</strong></td>
					<td align='left'>$vendor</td>
				</tr>
				<tr>
					<td class=legend>{model}:</strong></td>
					<td align='left' nowrap>$model</td>
				</tr>	
				<tr>
					<td align='right' class=legend nowrap>{mounted_on}:</strong></td>
					<td align='left' nowrap>$mounted_text</td>
				</tr>
				<tr>
					<td align='right' class=legend nowrap>{size}:</strong></td>
					<td align='left' nowrap>$size</td>
				</tr>	
				<tr>
					<td align='right' class=legend nowrap>{free}:</strong></td>
					<td align='left' nowrap>$free</td>
				</tr>							
				</table>
			</td>
		</tr>
		</table>";	
		
		
		$js=str_replace('"',"'",$js);
		$html=$html . "
<div style='float:left;margin:4px;width:240px;background-color:white;border:1px solid #CCCCCC'
		OnMouseOver=\";this.style.cursor='pointer';\"
		OnMouseOut=\";this.style.cursor='default';\"
		OnClick=\"javascript:$js\"
		>
		$text
</div>";
		
	}
	
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("$html");
	
	
}

function usb_image($array){
		$mounted=$array["mounted"];
		$TYPE=$array["TYPE"];
		$VENDOR=$array["ID_VENDOR"];
		if(strtoupper($VENDOR)=="TANDBERG"){return "tandberg-64.png";}
		
		if($mounted=="/"){return "disk-64.png";}
		if($TYPE=="swap"){return "disk-64.png";}
		
		
		
		if($array["AA"]==$array["UUID"]){$img_r="ck";}		
		$img="usb$img_r-64-red.png";
		if(strlen($mounted)>0){$img="usb$img_r-64-green.png";}
		
		

		
		
	return $img;
}
function usb_isConnected($array){
		$mounted=$array["mounted"];
		$TYPE=$array["TYPE"];
		$res=" {connected}";
		if(strlen($mounted)>0){$res="{plugged}";}
		if(($TYPE=="swap")){$res="{system}";}	
	return $res;
}

function main_usb_infos(){
	
	$artica=new artica_general();
	if(isset($_GET["autoback"])){
		writelogs("Save auto back for id {$_GET["usb_infos"]}",__FUNCTION__,__FILE__);
		$artica->ArticaUsbBackupKeyID=$_GET["usb_infos"];
		$artica->Save();
	}	
	
	if(isset($_GET["removeautoback"])){
		writelogs("Save auto back for id NONE",__FUNCTION__,__FILE__);
		$artica->ArticaUsbBackupKeyID="NONE";
		$artica->Save();
	}
	
	
if(isset($_GET["dismount"])){
		$sock=new sockets();
		$results=$sock->getfile("usb_umount:{$_GET["mntp"]}");
		}
		
if(isset($_GET["mount"])){
		$sock=new sockets();
		$results=$sock->getfile("usb_mount:{$_GET["dev"]};{$_GET["tt"]}");
		}				
	
	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?usb-scan-write=yes");
	if(!file_exists('ressources/usb.scan.inc')){return null;}
	include_once('ressources/usb.scan.inc');
	if(!is_array($_GLOBAL["usb_list"])){return null;}
	$array=$_GLOBAL["usb_list"][$_GET["usb_infos"]];
	

	$array["AA"]=$artica->ArticaUsbBackupKeyID;
	
	$results=htmlentities($results);
		$path=$array["PATH"];
		$LABEL=trim($array["LABEL"]);
		$TYPE=$array["TYPE"];
		$SEC_TYPE=$array["SEC_TYPE"];
		$mounted=$array["mounted"];
		
	$tbl=explode(";",$array["model"]);
		if(is_array($tbl)){
			$vendor=$tbl[0];
			$model=$tbl[1];
			$product=$tbl[2];
			$manufacturer=$tbl[3];
			$id=$tbl[4];
			$speed=$tbl[5];
		}	
		
		
		if($array["AA"]==$_GET["usb_infos"]){
			$button="<input type='button' value=\"{umake_autobackup}&nbsp;&raquo;\" OnClick=\"javascript:usb_remove_autoback('{$_GET["usb_infos"]}');\">";
			
		}else{
			$button="<input type='button' value=\"{make_autobackup}&nbsp;&raquo;\" OnClick=\"javascript:usb_add_autoback('{$_GET["usb_infos"]}');\">";
		}
	
		
$tbl=explode(";",$array["SIZE"]);
		if(is_array($tbl)){
			$size=$tbl[0];
			$used=$tbl[1];
			$free=$tbl[2];
			$pouc_occ=$tbl[3];
		}
		
		if(strlen($results)>0){
			$ta=explode("\n",$results);
			$results='
			<div style="width:100%;height:100px;overflow:auto;">
			<table style="width:99%" class=form>';
			while (list ($num, $line) = each ($ta)){
				if(trim($line)<>null){
				$results=$results . "<tr>
					<td valign='top'>$line</td>
				</tr>";
				}
			}
			
			$results=$results. "</table>
			</div>";
			
		}
		if($TYPE==null){$TYPE=$array["ID_FS_TYPE"];}
		
		if(strlen($mounted)>0){
			$mountf=imgtootltip('usb-48-red.png','{dismount_text}',"usb_dismount('{$_GET["usb_infos"]}','$mounted')");
		}else{
			$mountf=imgtootltip('usb-48-green.png','{mount_text}',"usb_mount('{$_GET["usb_infos"]}','$path','$TYPE')");
		}
		
		
		$format=ICON_USB_FORMAT($path);
		$mounted_text=$mounted;
		if(strlen($mounted_text)>20){$mounted_text=texttooltip(substr($mounted_text,0,17).'...',$mounted_text,null,null,1);}
		
	
	$html="
	<table style='width:100%'>
	<tr>
		<td width=1% valign='top'>
			<center style='margin:3px;font-weight:bold;background-color:white;padding:3px;border:1px solid #CCCCCC'>
			<img src='img/" . usb_image($array) . "'>
			<br>".usb_isConnected($array)."<hr>$mountf<hr>$format</center>
		</td>
		<td valign='top'>
			<table style='width:99%' class=form>
		<tr>
			<td class=legend>{type}:</strong></td>
			<td align='left'>$TYPE/$SEC_TYPE</td>
		</tr>			
		<tr>
			<td class=legend>{vendor}:</strong></td>
			<td align='left'>$vendor</td>
		</tr>
		<tr>
			<td class=legend>{model}:</strong></td>
			<td align='left' nowrap>$model</td>
		</tr>
		<tr>
			<td class=legend>{manufacturer}:</strong></td>
			<td align='left' nowrap>$manufacturer</td>
		</tr>		
		<tr>
			<td class=legend>{product}:</strong></td>
			<td align='left' nowrap>$product</td>
		</tr>
		<tr>
			<td class=legend>{speed}:</strong></td>
			<td align='left' nowrap>$speed</td>
		</tr>										
		<tr>
			<td align='right' class=legend nowrap>{mounted_on}:</strong></td>
			<td align='left' nowrap>$mounted_text</td>
		</tr>
		<tr>
			<td align='right' class=legend nowrap>{local}:</strong></td>
			<td align='left' nowrap>$path</td>
		</tr>		
		
		<tr>
			<td align='right' class=legend nowrap>{size}:</strong></td>
			<td align='left' nowrap>$size</td>
		</tr>	
		<tr>
			<td align='right' class=legend nowrap>{free}:</strong></td>
			<td align='left' nowrap>$free</td>
		</tr>	
		<tr><td colspan=2 align='right'>$button</td></tr>
		</table>
	</td>
</tr>
</table>
$results
";
	
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);
	
}
	
	
?>	