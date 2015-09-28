<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.ccurl.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	include_once('ressources/class.system.nics.inc');
	include_once('ressources/class.resolv.conf.inc');
	
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["database-items"])){databases_items();exit;}
	
	js();
function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{cached_Kerberos_tickets}");
	echo "YahooWin6('905','$page?popup=yes','$title')";
}	

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$tt=time();
	$t=$_GET["t"];
	$type=$tpl->javascript_parse_text("{type}");
	$zone=$tpl->_ENGINE_parse_body("{zone}");
	$new_text=$tpl->javascript_parse_text("{link_interface}");
	$database=$tpl->javascript_parse_text("{database}");
	$date=$tpl->javascript_parse_text("{date}");
	$rewrite_rules_fdb_explain=$tpl->javascript_parse_text("{rewrite_rules_fdb_explain}");
	$rebuild_tables=$tpl->javascript_parse_text("{rebuild_tables}");
	$size=$tpl->javascript_parse_text("{size}");
	$maintitle=$tpl->javascript_parse_text("{cached_Kerberos_tickets}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$ticket=$tpl->_ENGINE_parse_body("{ticket}");
	$buttons="
	buttons : [
	{name: '$new_text', bclass: 'add', onpress : NewRule$tt},
	{name: '$apply', bclass: 'Reconf', onpress : Apply$tt},
	],";
	$buttons=null;
	$html="
	<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:100%'></table>
	<script>
	function Start$tt(){
	$('#flexRT$tt').flexigrid({
	url: '$page?database-items=yes&t=$tt&tt=$tt&ruleid={$_GET["ID"]}',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:18px>KVNO</span>', name : 'comment', width : 76, sortable : false, align: 'center'},
	{display: '<span style=font-size:18px>$date</span>', name : 'eth', width :314, sortable : false, align: 'left'},
	{display: '<span style=font-size:18px>$ticket</span>', name : 'delete', width : 437, sortable : false, align: 'left'},
	],
	$buttons

	sortname: 'eth',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:22px>$maintitle</span>',
	useRp: false,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]

});
}

Start$tt();

</script>
";
echo $html;

}
function databases_items(){
	
	
	$tpl=new templates();
	$sock=new sockets();
	$sock->getFrameWork("squid2.php?cached-kerberos-tickets=yes");
	
	$dataZ=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/kerberos-tickets-squid"));
	if(!is_array($dataZ)){$dataZ=array();}

	$data = array();
	$data['page'] = 1;
	$data['total'] = count($dataZ);
	$data['rows'] = array();
	if(count($dataZ)==0){json_error_show("no data",1);}

	$fontsize="22";
	


	while (list ($num, $DB) = each ($dataZ) ){
		$color="black";


		$date=strtotime($DB["DATE"]);;
		$zdate=$tpl->time_to_date($date,true);
		$KVNO=$DB["NUM"];
		$TICKET=$DB["ticket"];
		
		if(!preg_match("#HTTP\/#", $TICKET)){$color="#AFAFAF";}
		
		$data['rows'][] = array(
				'id' => md5(serialize($DB)),
				'cell' => array(
						"<center style='font-size:{$fontsize}px;font-weight:normal;color:$color'>{$KVNO}</center>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>{$zdate}</a></span>",
						"<span style='font-size:18px;font-weight:normal;color:$color'>$TICKET</span>",)
		);
	}


	echo json_encode($data);

}
?>