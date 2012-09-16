<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.samba.inc');
	include_once('ressources/class.user.inc');
	include_once('ressources/class.drdb.inc');
	
	
	if(isset($_GET["volumes-list"])){volumes_list();exit;}
	if(isset($_POST["delete-volume"])){volume_delete();exit;}
	
	
	volumes();
	


	
function volumes(){	
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=450;
	$TB_WIDTH=550;
	$TB2_WIDTH=400;
	$TB_WIDTH=845;
	$TB2_WIDTH=610;
	$build_parameters=$tpl->_ENGINE_parse_body("{build_parameters}");
	$t=time();
	$volumes=$tpl->_ENGINE_parse_body("{volumes}");
	$new_volume=$tpl->_ENGINE_parse_body("{new_volume}");
	$volume_type=$tpl->_ENGINE_parse_body("{type}");
	$bricks=$tpl->_ENGINE_parse_body("{bricks}");
	$state=$tpl->_ENGINE_parse_body("status");
	
	$buttons="
	buttons : [
	{name: '$new_volume', bclass: 'Add', onpress : NewVolume$t},
	{name: '$build_parameters', bclass: 'Reconf', onpress : ClusterSetConfig$t},
	
	],	";
	$buttons=null;
	
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?volumes-list=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$volumes', name : 'volume_name', width :253, sortable : true, align: 'left'},
		{display: '$volume_type', name : 'volume_type', width : 151, sortable : false, align: 'left'},
		{display: '$bricks', name : 'briks', width : 256, sortable : false, align: 'left'},
		{display: '$state', name : 'state', width : 60, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'delete', width : 60, sortable : false, align: 'center'},
	],
	$buttons

	searchitems : [
		{display: '$volumes', name : 'volume_name'},
		
		
		],
	sortname: 'volume_name',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

	var x_VolumeDelete$t=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}	
		$('#flexRT$t').flexReload();
	}

function VolumeDelete$t(volenc,id){
	mem$t=id;
	var XHR = new XHRConnection();
	XHR.appendData('delete-volume',volenc);
    XHR.sendAndLoad('$page', 'POST',x_VolumeDelete$t);	
	}

	
</script>";
	
	echo $html;	
	
}

function volume_delete(){
	$sock=new sockets();
	$sock->getFrameWork("gluster.php?delete-volume={$_POST["delete-volume"]}");
	
	
}

function volumes_list(){
	
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$sock=new sockets();
	$t=$_GET["t"];
	$datas=unserialize(base64_decode($sock->getFrameWork("gluster.php?volume-info=yes")));
	$t=$_GET["t"];
	$data = array();
	$data['page'] = $page;
	$data['total'] = count($datas);
	$data['rows'] = array();

	$sock=new sockets();
	
	while (list ($volume_name, $ligne) = each ($datas) ){
		$id=$ligne["ID"];
		$bricks=array();
		while (list ($a, $b) = each ($ligne["BRICKS"]) ){
			$bricks[]="<div style='padding-left:10px;font-size:14px'>$b</div>";
		}
		
		$volume_nameenc=base64_encode($volume_name);
		$delete=imgsimple("delete-24.png",null,"VolumeDelete$t('$volume_nameenc','$id')");
		
		
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array(
			"<span style='font-size:16px;'>$volume_name</span>",
			"<span style='font-size:16px;'>{$ligne["TYPE"]}</span>",
			"<span style='font-size:16px;'>".@implode(" ", $bricks)."</span>",
			"<span style='font-size:16px;'>{$ligne["STATUS"]}</span>",
			$delete
		  	

		 )
		);
	}
	
	
echo json_encode($data);	
	
}
