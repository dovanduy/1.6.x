<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.samba.inc');
	include_once('ressources/class.user.inc');
	include_once('ressources/class.kav4samba.inc');
	include_once('ressources/class.os.system.inc');
	
	$users=new usersMenus();
	if(!$users->AsSambaAdministrator){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("alert('{ERROR_NO_PRIVS}')");exit;die();
	}	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["usblist"])){echo autmount_list();exit;}
	if(isset($_GET["ShareDevice"])){ShareDevice();exit;}
	if(isset($_GET["DeleteUsbShare"])){DeleteUsbShare();exit;}
	

	
	
	//usb-share-128.png
	
	js();
	
	

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{SAMBA_USB_SHARE}');
	$prefix=str_replace('.','',$page);
	$samba_js=file_get_contents(dirname(__FILE__).'/js/samba.js');
$html="
	var {$prefix}timeout=0;
	var {$prefix}timerID  = null;
	var {$prefix}tant=0;
	var {$prefix}reste=0;	

	$samba_js
	
	function {$prefix}LoadPage(){
		YahooWin2(650,'$page?popup=yes&t={$_GET["t"]}','$title');
		
		}
	
	function {$prefix}FillPage(){	
		LoadAjax('usb-list','$page?usblist=yes');
	}
	
	
	
	
	{$prefix}LoadPage();";	
	
echo $html;
}

function popup(){
	$t=$_GET["t"];
	
	$page=CurrentPageName();
	$tpl=new templates();
	$path=$tpl->_ENGINE_parse_body("{path}");
	$info=$tpl->_ENGINE_parse_body("{info}");
	$add_a_shared_folder=$tpl->_ENGINE_parse_body("{add_a_shared_folder}");
	$default_settings=$tpl->_ENGINE_parse_body("{default_settings}");
	$folder=$tpl->_ENGINE_parse_body("{folders}");
	$trash=$tpl->_ENGINE_parse_body("{trash}");
	$Transitive=$tpl->_ENGINE_parse_body("{transitive}");
	$APP_DEVICE_CONTROL=$tpl->_ENGINE_parse_body("{APP_DEVICE_CONTROL}");
	$usb_share_explain=$tpl->_ENGINE_parse_body("{usb_share_explain}");
	$TABLE_WIDTH=873;
	$pATH_WITH=415;
	$tTable=time();	
$buttons="
		buttons : [
		{name: '$APP_DEVICE_CONTROL', bclass: 'add', onpress : APP_DEVICE_CONTROL$t},
		
		
		],";	

$html="
<div class=text-info style='font-size:14px'>$usb_share_explain</div>
<table class='flexRT$tTable' style='display: none' id='flexRT$tTable' style='width:100%'></table>

	
<script>
var IDTMP=0;
$(document).ready(function(){
$('#flexRT$tTable').flexigrid({
	url: '$page?usblist=yes&t=$tTable',
	dataType: 'json',
	colModel : [
		{display: '$path', name : 'path', width :191, sortable : false, align: 'left'},
		{display: '$info', name : 'flags', width :350, sortable : false, align: 'left'},
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
	width: 630,
	height: 300,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

function APP_DEVICE_CONTROL$t(){
	Loadjs('usb.browse.php?t=$t&t2=$tTable');

}

	var X_ShareDevice= function (obj) {
		var results=obj.responseText;
		$('#flexRT{$_GET["t"]}').flexReload();
		$('#flexRT{$tTable}').flexReload();
		$('#SAMBA_TABLE_SHARED_LIST').flexReload();
		if(results.length>3){alert(results);}
		}	
	
	function ShareDevice(path){
		var XHR = new XHRConnection();
		XHR.appendData('ShareDevice',path);
		XHR.sendAndLoad('$page', 'GET',X_ShareDevice);	
		}
		
	function DeleteUsbShare(path){
		var XHR = new XHRConnection();
		XHR.appendData('DeleteUsbShare',path);
		XHR.sendAndLoad('$page', 'GET',X_ShareDevice);	
		}

</script>

";

	
	echo $html;
	
}


function autmount_list(){
	$samba=new samba();
	$ldap=new clladp();
	$dn="ou=auto.automounts,ou=mounts,$ldap->suffix";
	
	$filter="(&(ObjectClass=automount)(automountInformation=*))";
	$attrs=array("automountInformation","cn");
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();	
	$c=0;
$sr =@ldap_search($ldap->ldap_connection,$dn,$filter,$attrs);
		if($sr){
			$hash=ldap_get_entries($ldap->ldap_connection,$sr);
			if($hash["count"]>0){
				for($i=0;$i<$hash["count"];$i++){
					$path=$hash[$i]["cn"][0];
					$automountInformation=$hash[$i][strtolower("automountInformation")][0];
					$delete=imgsimple("plus-24.png",null,"ShareDevice('$path');");
					$c++;
					
					
					
					if(is_array($samba->main_array[$path])){
						$delete=imgsimple('delete-24.png','{delete}',"DeleteUsbShare('$path')");
						$js="FolderProp('$path')";
					}	
					
					
					$data['rows'][] = array(
					'id' => $md,
					'cell' => array(
					 	"<a href=\"javascript:blur();\" OnClick=\"$js;\" style='font-size:16px;text-decoration:underline'>$path</span>",
						"<span style='font-size:16px'>$automountInformation</span>",
						$delete
						)
						);					
				}
			}	
		}
	$data['total'] = $c;
	echo json_encode($data);	
	
		
}

function ShareDevice(){
	$samba=new samba();
	$samba->main_array[$_GET["ShareDevice"]]["path"]="/automounts/{$_GET["ShareDevice"]}";
	$samba->main_array[$_GET["ShareDevice"]]["create mask"]="0660";
	$samba->main_array[$_GET["ShareDevice"]]["directory mask"]="0770";
	$samba->main_array[$_GET["ShareDevice"]]["force user"]="root";
	$samba->main_array[$_GET["ShareDevice"]]["force group"]="root";
	$samba->main_array[$_GET["ShareDevice"]]["browsable"]="yes";
	$samba->main_array[$_GET["ShareDevice"]]["writable"]="yes";
	$samba->SaveToLdap();
	}
function DeleteUsbShare(){
	$samba=new samba();
	unset($samba->main_array[$_GET["DeleteUsbShare"]]);
	$samba->SaveToLdap();
	
}

	
	
	


?>	