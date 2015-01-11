<?php
include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
include_once(dirname(__FILE__) . "/ressources/class.os.system.inc");
include_once(dirname(__FILE__) . "/ressources/class.samba.inc");
if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);$GLOBALS["VERBOSE"]=true;}

	$user=new usersMenus();
	if(($user->AsSystemAdministrator==false) OR ($user->AsSambaAdministrator==false)) {
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("alert('{ERROR_NO_PRIVS}');");
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["usblist"])){echo usblist();exit;}

	
	
js();



function js(){
	$page=CurrentPageName();
	$prefix=str_replace('.','_',$page);
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{APP_DEVICE_CONTROL}');
	
	
$html="
	function {$prefix}LoadPage(){
		LoadWinORG(650,'$page?popup=yes&t={$_GET["t"]}&t2={$_GET["t2"]}','$title');
	}
	
	{$prefix}LoadPage();
";	

	echo $html;
	
}


function popup(){
	$t3=time();
	$html="<div class=text-info style='font-size:14px'>{APP_DEVICE_CONTROL_TEXT}</div>";	
	$t=$_GET["t"];
	$tTable=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$path=$tpl->_ENGINE_parse_body("{path}");
	$info=$tpl->_ENGINE_parse_body("{info}");
	$add_a_shared_folder=$tpl->_ENGINE_parse_body("{add_a_shared_folder}");
	$default_settings=$tpl->_ENGINE_parse_body("{default_settings}");
	$folder=$tpl->_ENGINE_parse_body("{folders}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$Transitive=$tpl->_ENGINE_parse_body("{transitive}");
	$APP_DEVICE_CONTROL=$tpl->_ENGINE_parse_body("{APP_DEVICE_CONTROL}");
	$APP_DEVICE_CONTROL_TEXT=$tpl->_ENGINE_parse_body("{APP_DEVICE_CONTROL_TEXT}");
	$TABLE_WIDTH=873;
	$pATH_WITH=415;
		
$buttons="
		buttons : [
		{name: '$APP_DEVICE_CONTROL', bclass: 'add', onpress : APP_DEVICE_CONTROL$t},
		
		
		],";	
$buttons=null;
$html="

<table class='flexRT$tTable' style='display: none' id='flexRT$tTable' style='width:100%'></table>

	
<script>
var IDTMP=0;
$(document).ready(function(){
$('#flexRT$tTable').flexigrid({
	url: '$page?usblist=yes&t={$_GET["t"]}&t2={$_GET["t2"]}&t3=$tTable',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'icon', width :31, sortable : false, align: 'center'},
		{display: '$path', name : 'path', width :83, sortable : false, align: 'left'},
		{display: '$info', name : 'flags', width :310, sortable : false, align: 'left'},
		{display: '$size', name : 'flags', width :118, sortable : false, align: 'left'},
		
		],
	$buttons
	searchitems : [
		{display: '$path', name : 'path'},
		],
	sortname: 'ID',
	sortorder: 'desc',
	usepager: true,
	title: '$APP_DEVICE_CONTROL_TEXT',
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

}";	
	
	echo $html;
	
}

function usblist(){
	$sock=new sockets();
	$tpl=new templates();
	$samba=new samba();
	$sock->getFrameWork("cmd.php?usb-scan-write=yes");
	if(!file_exists('ressources/usb.scan.inc')){
		json_error_show("<H1>{error_no_socks}</H1>",1);
		}
	include("ressources/usb.scan.inc");
	include_once("ressources/class.os.system.tools.inc");
	
	
	
	reset($_GLOBAL["disks_list"]);
	while (list ($uuid, $array) = each ($_GLOBAL["usb_list"]) ){
		if($GLOBALS["VERBOSE"]){echo "USB = &raquo; {$array["PATH"]} = &raquo; {$array["ID_USB_DRIVER"]}<br>";}
		$USBTYPES[$array["PATH"]]=$array["ID_USB_DRIVER"];
		
	}
	reset($_GLOBAL["disks_list"]);
	while (list ($dev, $array) = each ($_GLOBAL["disks_list"]) ){
		if($GLOBALS["VERBOSE"]){echo "USB = &raquo; {$dev} = &raquo; {$array["ID_USB_DRIVER"]}<br>";}
		$USBTYPES[$dev]=$array["ID_USB_DRIVER"];
		
	}	
	
	reset($_GLOBAL["disks_list"]);
	
	$os=new os_system();
	$count=0;
	$error_not_mounted=$tpl->_ENGINE_parse_body("{error_not_mounted}");
	$mounted=$tpl->_ENGINE_parse_body("{mounted}");
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();		
	
	reset($_GLOBAL["disks_list"]);
	reset($_GLOBAL["usb_list"]);
	
	
	while (list ($num, $usb_data_array) = each ($_GLOBAL["usb_list"]) ){
		$uiid=$num;
		
		$path=trim($usb_data_array["PATH"]);
		$LABEL=trim($usb_data_array["LABEL"]);
		$TYPE=trim($usb_data_array["TYPE"]);
		$SEC_TYPE=trim($usb_data_array["SEC_TYPE"]);
		$title_mounted=trim($usb_data_array["mounted"]);
		$UUID=$usb_data_array["UUID"];
		$ID_MODEL=$usb_data_array["ID_MODEL"];
		$imgs="usb-32.png";
		if($GLOBALS["VERBOSE"]){echo "PATH=$path Mounted on: $title_mounted<br>";}
		
		if($title_mounted=='/'){continue;}	

		if(!is_array($_GLOBAL["disks_list"])){
			if(is_file(dirname(__FILE__).'/usb.scan.inc')){
				include dirname(__FILE__).'/usb.scan.inc';
				if(is_array($_GLOBAL["disks_list"]["$path"])){
					$ID_MODEL=$_GLOBAL["disks_list"]["$path"]["ID_MODEL"];
				}
			}	
		}		
		
		if(preg_match("#(.+?)[0-9]+$#",$path,$ri)){
			if(is_array($_GLOBAL["disks_list"]["{$ri[1]}"])){
					if(is_array($_GLOBAL["disks_list"]["{$ri[1]}"]["PARTITIONS"]))
					$imgs="usb-disk-32.png";
				}
		}
		
		if($USBTYPES["/dev/{$_GLOBAL["DEV"]}"]=="usb-storage"){
			$imgs="usb-32.png";
		}
		
		
		$size=null;
		$pourc=null;
		
	 	if(preg_match("#(.+?);(.+?);(.+?);([0-9]+)%#",$usb_data_array["SIZE"],$re)){$size=$re[1];$pourc=" ({$re[4]}%)";}
		if($LABEL==null){if($path<>null){$title="$path";}}else{$title="$LABEL";}		
		
	 	if(($mounted==null) && ($size==null)){
		$error=true;
		if($TYPE==null){$TYPE=$array["ID_FS_TYPE"];}	
			$title_mounted=$error_not_mounted;
			$umount="
				<tr>
				<td align='right' >" . imgtootltip('fw_bold.gif','{mount}',"Loadjs('usb.index.php?mount=yes&uuid=$UUID&mounted=$path&type=$TYPE')")."</td>
				<td style='font-size:12px'>". texttooltip('{mount}','{mount_explain}',"Loadjs('usb.index.php?mount=yes&uuid=$UUID&mounted=$path&type=$TYPE')")."</td>
				</tr>";				
		
		}		
		
		
		$folder_name=$samba->GetShareName("/media/$UUID");
		if($folder_name<>null){$imgs="usb-share-32.png";}	
		$js="Loadjs('usb.browse.php?uuid=$UUID');";	
		$jsinfos="Loadjs('usb.index.php?uuid-infos=$UUID&t={$_GET["t"]}&t2={$_GET["t2"]}&t3={$_GET["t3"]}');";
		
		
		
		$count++;
		
		$data['rows'][] = array(
		'id' => $md,
		'cell' => array(
		 "<a href=\"javascript:blur();\" OnClick=\"javascript:$jsinfos;\"><img src='img/$imgs' style='margin-top:10px'></a>",
		"<span style='margin-top:10px'><a href=\"javascript:blur();\" OnClick=\"javascript:$jsinfos;\" style='font-size:16px;text-decoration:underline;margin-top:10px'>$title</a></span>",
		"<span style='font-size:16px'>$ID_MODEL</span><div style='font-size:12px'>$mounted:$title_mounted</div>",
		"<span style='font-size:16px'>$size$pourc</div>",
		
		)
		);			
		
		
	}
	
	$data['total'] = $count;
	
	
echo json_encode($data);
	
	
}



	

?>