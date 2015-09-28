<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.firehol.inc');

	$usersmenus=new usersMenus();
	if($usersmenus->AsSystemAdministrator==false){
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		die();
	}
	
	if(isset($_GET["interfaces"])){search();exit;}
	if(isset($_GET["service-js"])){service_js();exit;}
	if(isset($_GET["delete-js"])){delete_js();exit;}
	if(isset($_GET["service-popup"])){service_popup();exit;}
	if(isset($_POST["service"])){service_save();exit;}
	if(isset($_POST["delete"])){delete();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	
js();	

function js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$ID=intval($_GET["ID"]);
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{services}");
	echo "YahooWin6('600','$page?popup=yes&CallBack={$_GET["CallBack"]}','$title')";
}
	

function popup(){
	$users=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();
	$rule=$tpl->_ENGINE_parse_body("{rule}");
	
	$t=time();
	$interfaces=$tpl->_ENGINE_parse_body("{interfaces}");
	$server_port=$tpl->_ENGINE_parse_body("{ports}");
	$service=$tpl->_ENGINE_parse_body("{services}");
	$client_port=$tpl->_ENGINE_parse_body("{client_ports}");
	$saved_date=$tpl->_ENGINE_parse_body("{zDate}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$name=$tpl->_ENGINE_parse_body("{name}");
	$allow_rules=$tpl->_ENGINE_parse_body("{allow_rules}");
	$banned_rules=$tpl->_ENGINE_parse_body("{banned_rules}");
	$empty_all_firewall_rules=$tpl->javascript_parse_text("{empty_all_firewall_rules}");
	$services=$tpl->_ENGINE_parse_body("{services}");
	$new_service=$tpl->_ENGINE_parse_body("{new_service}");
	$options=$tpl->_ENGINE_parse_body("{options}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$ERROR_IPSET_NOT_INSTALLED=$tpl->javascript_parse_text("{ERROR_IPSET_NOT_INSTALLED}");
	$IPSET_INSTALLED=0;
	if($users->IPSET_INSTALLED){$IPSET_INSTALLED=1;}

	$TB_HEIGHT=350;
	$TABLE_WIDTH=920;
	$TB2_WIDTH=400;
	$ROW1_WIDTH=629;
	$ROW2_WIDTH=163;
	
	//service,server_port,client_port,helper,enabled

	$t=time();

	$buttons="
	buttons : [
	{name: '<strong style=font-size:16px>$new_service</strong>', bclass: 'Add', onpress : NewRule$t},
	],	";
	$html="
	<table class='FIREHOLE_SERVICES_BRTABLES' style='display: none' id='FIREHOLE_SERVICES_BRTABLES' style='width:99%'></table>
	<script>
	var IptableRow='';
	$(document).ready(function(){
	$('#FIREHOLE_SERVICES_BRTABLES').flexigrid({
	url: '$page?interfaces=yes&t=$t&CallBack={$_GET["CallBack"]}',
	dataType: 'json',
	colModel : [
	{display: '$service', name : 'service', width :198, sortable : true, align: 'left'},
	{display: '$server_port', name : 'server_port', width :145, sortable : false, align: 'left'},
	{display: '$client_port', name : 'client_port', width :97, sortable : false, align: 'left'},
	{display: '&nbsp;', name : 'none2', width :70, sortable : false, align: 'center'},
	

	],
	$buttons

	searchitems : [
	{display: '$service', name : 'service'},
	{display: '$server_port', name : 'server_port'},
	{display: '$client_port', name : 'client_port'},
	],

	sortname: 'service',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:18px>$service</span>',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: $TB_HEIGHT,
	singleSelect: true

});
});

function NewRule$t(){
	Loadjs('firehole.services.php?service-js=');
}



</script>";

	echo $html;
}



function delete_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$text=$tpl->javascript_parse_text("{delete} {$_GET["delete-js"]} ?");
	$t=time();
	echo "
var xAdd$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#FIREHOLE_SERVICES_BRTABLES').flexReload();
}
function Add$t(){
	if(!confirm('$text ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete', '{$_GET["delete-js"]}');
	XHR.sendAndLoad('$page', 'POST',xAdd$t);
}
Add$t();";
	}



function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$database="artica_backup";
	$search='%';
	$table="nics";
	$page=1;
	$ORDER=null;
	$allow=null;

	$total=0;
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("no data");;}

	
	$searchstring=string_to_flexquery();
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$table="firehol_services_def";
	$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE enabled=1 $searchstring";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
	$total = $ligne["TCOUNT"];


	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}

	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT * FROM $table  WHERE enabled=1 $searchstring $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show($q->mysql_error_html(),1);}
	if(mysql_num_rows($results)==0){json_error_show("no data $sql");}

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(!$q->ok){json_error_show($q->mysql_error);}
	$fontsize=18;
	$firehole=new firehol();
	
	// 	//service,server_port,client_port,helper,enabled
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$mouse="OnMouseOver=\"this.style.cursor='pointer'\" OnMouseOut=\"this.style.cursor='default'\"";
		$linkstyle="style='text-decoration:underline'";
		$service=$ligne["service"];
		$server_port=$ligne["server_port"];
		$client_port=$ligne["client_port"];
		$enabled=$ligne["enabled"];
		$js="Loadjs('firehole.services.php?service-js=$service')";
		if($enabled==0){$color="#909090";}
		$delete=imgsimple("arrow-right-24.png",null,"{$_GET["CallBack"]}('$service')");

		$link="<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='color:$color;font-size:{$fontsize}px;text-decoration:underline'>";

		$data['rows'][] = array(
				'id' => $ligne["Interface"],
				'cell' => array(
						"<span style='font-size:{$fontsize}px;color:$color'>$link$service</a></span>",
						"<span style='font-size:{$fontsize}px;color:$color'>$link$server_port</a></span>",
						"<span style='font-size:{$fontsize}px;color:$color'>$client_port</span>",
						"<center style='font-size:{$fontsize}px;color:$color'>$delete</center>",
						
						
							

				)
		);
	}


	echo json_encode($data);

}