<?php
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.groups.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.ActiveDirectory.inc');
//aciennement ufdbguard.databases.php?scripts=config-file

$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();
}
if(isset($_POST["DenyUfdbWriteConf"])){DenyUfdbWriteConf();exit;}
if(isset($_POST["UFDB_CONTENT"])){UFDB_CONTENT();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["databases"])){databases();exit;}
if(isset($_GET["database-items"])){databases_items();exit;}
if(isset($_GET["conf"])){conf();exit;}
if(isset($_GET["groups"])){groups();exit;}
if(isset($_GET["debug-groups"])){debug_groups();exit;}
js();


function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{config_status}");
	echo "YahooWin3('700','$page?tabs=yes','$title',true)";
	
}
function conf(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$DenyUfdbWriteConf=$sock->GET_INFO("DenyUfdbWriteConf");
	if(!is_numeric($DenyUfdbWriteConf)){$DenyUfdbWriteConf=0;}
	$datas=unserialize(base64_decode($sock->getFrameWork("squid.php?ufdbguardconf=yes")));
	$html="<div id='$t'></div>
	<table>
	<tr>
	<td class=legend style='font-size:14px'>". $tpl->_ENGINE_parse_body("{deny_artica_to_write_config}")."</td>
	<td>". Field_checkbox("DenyUfdbWriteConf", 1,$DenyUfdbWriteConf,"DenySquidWriteConfSave$t()")."</td>
	</tr>
	</table><textarea 
		style='width:95%;height:550px;overflow:auto;border:5px solid #CCCCCC;font-size:14px;font-weight:bold;padding:3px'
		id='SQUID_CONTENT-$t'>".@implode("\n", $datas)."</textarea>
		
	<center><hr>". $tpl->_ENGINE_parse_body(button("{apply}", "SaveUserConfFile$t()",22))."</center>

	<script>
		var x_DenySquidWriteConfSave$t= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);return;}
		}
	
	function DenySquidWriteConfSave$t(){
		var XHR = new XHRConnection();
		var DenyUfdbWriteConf=0;
		if(document.getElementById('DenyUfdbWriteConf').checked){
			DenyUfdbWriteConf=1;
		}
		XHR.appendData('DenyUfdbWriteConf', DenyUfdbWriteConf);
		XHR.sendAndLoad('$page', 'POST',x_DenySquidWriteConfSave$t);
	}
	
	var x_SaveUserConfFile$t= function (obj) {
			var results=obj.responseText;
			document.getElementById('$t').innerHTML='';
			if(results.length>3){alert(results);return;}
		}
	
	function SaveUserConfFile$t(){
		var XHR = new XHRConnection();
		XHR.appendData('UFDB_CONTENT', encodeURIComponent(document.getElementById('SQUID_CONTENT-$t').value));
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_SaveUserConfFile$t);
	}	
</script>	
	";
echo $html;
	
}


function tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$array["databases"]='{used_databases}';
	$array["conf"]='{config_file_tiny}';
	$array["groups"]='{groups}';
	$time=time();
	
	$fontsize=14;
	
	while (list ($num, $ligne) = each ($array) ){
			$tab[]="<li><a href=\"$page?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
		
	}
	
	echo build_artica_tabs($tab, "main_ufbd_config");
	
}
function databases(){
$page=CurrentPageName();
$tpl=new templates();
$tt=time();
$t=$_GET["t"];
$type=$tpl->javascript_parse_text("{type}");
$zone=$tpl->_ENGINE_parse_body("{zone}");
$new_text=$tpl->javascript_parse_text("{link_interface}");
$database=$tpl->javascript_parse_text("{database}");
$category=$tpl->javascript_parse_text("{category}");
$rewrite_rules_fdb_explain=$tpl->javascript_parse_text("{rewrite_rules_fdb_explain}");
$rebuild_tables=$tpl->javascript_parse_text("{rebuild_tables}");
$size=$tpl->javascript_parse_text("{size}");
$maintitle=$tpl->javascript_parse_text("{used_databases}");
$apply=$tpl->javascript_parse_text("{apply}");
$explain=$tpl->_ENGINE_parse_body("{ufdb_used_db_explain}");
$buttons="
		buttons : [
		{name: '$new_text', bclass: 'add', onpress : NewRule$tt},
		{name: '$apply', bclass: 'Reconf', onpress : Apply$tt},
		],";
$buttons=null;
		$html="
		<div class=explain style='font-size:14px'>$explain</div>
		<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:100%'></table>
		<script>
		function Start$tt(){
		$('#flexRT$tt').flexigrid({
		url: '$page?database-items=yes&t=$tt&tt=$tt&ruleid={$_GET["ID"]}',
		dataType: 'json',
		colModel : [
		{display: '&nbsp;', name : 'delete', width : 32, sortable : false, align: 'center'},
		{display: '$category', name : 'comment', width : 176, sortable : false, align: 'left'},
		{display: '$database', name : 'eth', width :252, sortable : false, align: 'left'},
		{display: '$size', name : 'delete', width : 116, sortable : false, align: 'left'},
		],
		$buttons
		
		sortname: 'eth',
		sortorder: 'asc',
		usepager: true,
		title: '$maintitle',
		useRp: false,
		rp: 50,
		showTableToggleBtn: false,
		width: '99%',
		height: 450,
		singleSelect: true,
		rpOptions: [10, 20, 30, 50,100,200]
	
	});
	}
	
	var xNewRule$tt= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#flexRT$t').flexReload();
	$('#flexRT$tt').flexReload();
	}
	
	
	function NewRule$tt(){
	Loadjs('$page?interface-js=yes&ID=&t=$tt','$new_text');
	}
	function Delete$tt(zmd5){
	if(confirm('$delete')){
	var XHR = new XHRConnection();
	XHR.appendData('interface-delete', zmd5);
	XHR.sendAndLoad('$page', 'POST',xNewRule$tt);
	}
	}
	
	function Apply$tt(){
	Loadjs('shorewall.php?apply-js=yes',true);
	}
	
	
	var xRuleEnable$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#flexRT$t').flexReload();
	$('#flexRT$tt').flexReload();
	}
	
	
	function RuleEnable$tt(ID,md5){
	var XHR = new XHRConnection();
	XHR.appendData('rule-enable', ID);
	if(document.getElementById(md5).checked){XHR.appendData('enable', 1);}else{XHR.appendData('enable', 0);}
	XHR.sendAndLoad('$page', 'POST',xRuleEnable$tt);
	}
	var x_LinkAclRuleGpid$tt= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#table-$t').flexReload();
	$('#flexRT$tt').flexReload();
	ExecuteByClassName('SearchFunction');
	}
	function FlexReloadRulesRewrite(){
	$('#flexRT$t').flexReload();
	}
	
	function MoveRuleDestination$tt(mkey,direction){
	var XHR = new XHRConnection();
	XHR.appendData('rules-destination-move', mkey);
	XHR.appendData('direction', direction);
	XHR.sendAndLoad('$page', 'POST',x_LinkAclRuleGpid$tt);
	}
	
	function MoveRuleDestinationAsk$tt(mkey,def){
	var zorder=prompt('Order',def);
	if(!zorder){return;}
	var XHR = new XHRConnection();
	XHR.appendData('rules-destination-move', mkey);
	XHR.appendData('rules-destination-zorder', zorder);
	XHR.sendAndLoad('$page', 'POST',x_LinkAclRuleGpid$tt);
	}
	Start$tt();
	
	</script>
	";
	echo $html;
	
	}
function databases_items(){
	include_once("ressources/class.dansguardian.inc");
	$sock=new sockets();
	$dataZ=unserialize(base64_decode($sock->getFrameWork("ufdbguard.php?used-db=yes")));
	if(!is_array($dataZ)){$dataZ=array();}
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($data);
	$data['rows'] = array();
	
	$dans=new dansguardian_rules();
	$DBZ["ART"]="Artica Database";
	$DBZ["UNIV"]="Toulouse University";
	$DBZ["PERS"]="Personal Database";
	
	if(count($dataZ)==0){json_error_show("no data",1);}
	
	$fontsize="16";
	$q=new mysql_squid_builder();
	
	
	while (list ($num, $DB) = each ($dataZ) ){
		$color="black";
		
		
		$dbname=$DBZ[$DB["DB"]];
		$size=$DB["SIZE"];
		if($size<120){$color="#AFAFAF";}
		$size=FormatBytes($size/1024);
		$category=$q->filaname_tocat($DB["DIR"]);
		$pic=$dans->array_pics[$category];
		if($pic==null){$pic="20-categories-personnal.png";}
		
		$data['rows'][] = array(
				'id' => md5(serialize($DB)),
				'cell' => array(
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'><img src='img/$pic'></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>{$category}</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>{$dbname}</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$size</span>",)
				);
	}
	
	
	echo json_encode($data);
	
}	
function DenyUfdbWriteConf(){
	$sock=new sockets();
	$sock->SET_INFO("DenyUfdbWriteConf", $_POST["DenyUfdbWriteConf"]);

}
function UFDB_CONTENT(){
	$_POST["UFDB_CONTENT"]=url_decode_special_tool($_POST["UFDB_CONTENT"]);
	$content=urlencode(base64_encode($_POST["UFDB_CONTENT"]));
	$sock=new sockets();
	$datas=trim(base64_decode($sock->getFrameWork("ufdbguard.php?saveconf=$content")));
	echo $datas;
}
function groups(){
	
	$t=time();
	$page=CurrentPageName();
	echo "<div id='$t'></div>
	<script>LoadAjax('$t','$page?debug-groups=yes')</script>";
	
	
	
	
	
}


function debug_groups(){
	
	$sock=new sockets();
	$datas=trim(base64_decode($sock->getFrameWork("ufdbguard.php?debug-groups=yes")));	
	echo "<textarea 
		style='width:95%;height:550px;overflow:auto;border:5px solid #CCCCCC;font-size:14px;font-weight:bold;padding:3px'
		id='SQUID_CONTENT-$t'>$datas</textarea>";
}

