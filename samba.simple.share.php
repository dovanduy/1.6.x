<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.samba.inc');
	include_once('ressources/class.user.inc');
	include_once('ressources/class.computers.inc');
	include_once('ressources/class.pdns.inc');
	
	

	
	if(!CheckSambaRights()){die();}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["SimpleShareSearch"])){SimpleShareSearch();exit;}
	if(isset($_GET["SimpleShareAddCompForm"])){SimpleShareAddCompForm();exit;}
	if(isset($_GET["computername_add"])){SimpleShareAddComputerLDAP();exit;}
	if(isset($_GET["SharedList"])){SharedList();exit;}
	if(isset($_POST["add-uid"])){SimpleShareAddCompToPath();exit;}
	if(isset($_POST["del-uid"])){SimpleShareDelCompToPath();exit;}
	if(isset($_POST["all-comp"])){SimpleShareAddAllComp();exit;}
	js();
	
function js(){
	$tpl=new templates();
	$simple_share=$tpl->_ENGINE_parse_body("{simple_share}");
	$page=CurrentPageName();
	$html="
	
	YahooWin2('535','$page?popup=yes&path={$_GET["path"]}','$simple_share');
	
	function x_SimpleSearchAddComputerPath(obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){
			alert(tempvalue);
			SimpleSharePathList();
			return;
		}	
		
		if(document.getElementById('main_config_folder_properties')){
			RefreshTab('main_config_folder_properties');
		}
		
		SimpleSharePathList();
	}	
	

	

	";
	
	echo $html;
	
	
}

function popup(){
	$page=CurrentPageName();
	
	$tpl=new templates();	
	$sock=new sockets();
	$purge_catagories_database_explain=$tpl->javascript_parse_text("{purge_catagories_database_explain}");
	$purge_catagories_table_explain=$tpl->javascript_parse_text("{purge_catagories_table_explain}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$computers=$tpl->_ENGINE_parse_body("{computers}");
	$addCat=$tpl->_ENGINE_parse_body("{add} {category}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$task=$tpl->_ENGINE_parse_body("{task}");
	$link_computer=$tpl->_ENGINE_parse_body("{link_computer}");
	$run=$tpl->_ENGINE_parse_body("{run}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$run_this_task_now=$tpl->javascript_parse_text("{run_this_task_now} ?");
	$all_events=$tpl->_ENGINE_parse_body("{events}");
	$parameters=$tpl->_ENGINE_parse_body("{parameters}");
	$ip_address=$tpl->_ENGINE_parse_body("{ip_address}");
	$simple_share_explain=$tpl->_ENGINE_parse_body("{simple_share_explain}");
	$all_computers=$tpl->_ENGINE_parse_body("{all_computers}");
	$t=time();
	$html="
	<div style='margin-left:-10px'>
		<div class=explain style='font-size:14px'>$simple_share_explain</div>
		<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	</div>
<script>
var rowMem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?SharedList=yes&t=$t&path={$_GET["path"]}',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'ID', width : 32, sortable : true, align: 'center'},
		{display: '$computers', name : 'computers', width : 402, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 32, sortable : false, align: 'center'}
	],
buttons : [
	{name: '$link_computer', bclass: 'add', onpress : LinkComputer$t},
	{name: '$all_computers', bclass: 'add', onpress : AllComp$t},
	
	
		],	
	searchitems : [
		{display: '$computers', name : 'computers'},
		],
	sortname: 'ID',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: 530,
	height: 300,
	singleSelect: true
	
	});   
});

	function x_SimpleSearchAddComputer$t(obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}	
		$('#flexRT$t').flexReload();
	}		
	
	function SimpleSearchAddComputer$t(realuid,mac,ip){
		var XHR = new XHRConnection();
		XHR.appendData('add-uid',realuid);
		XHR.appendData('path','{$_GET["path"]}');
		XHR.sendAndLoad('$page', 'POST',x_SimpleSearchAddComputer$t);				
	
	}

	
	function x_SimpleSearchDeleteComputerPath$t(obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}	
		$('#row'+rowMem$t).remove();
	}	
	
	
	function SimpleSearchDeleteComputerPath(value,id){
		rowMem$t=id;
		var XHR = new XHRConnection();
		XHR.appendData('path','{$_GET["path"]}');
		XHR.appendData('del-uid',value);
		XHR.sendAndLoad('$page', 'POST',x_SimpleSearchDeleteComputerPath$t);		
	}

	function AllComp$t(){
		var XHR = new XHRConnection();
		XHR.appendData('all-comp','yes');
		XHR.appendData('path','{$_GET["path"]}');
		XHR.sendAndLoad('$page', 'POST',x_SimpleSearchAddComputer$t);	
	}
	
	function LinkComputer$t(){
		Loadjs('computer-browse.php?callback=SimpleSearchAddComputer$t&mode=selection&show-title=yes');
	}


</script>
	
	";
	echo $html;
	
	
}




function SharedList(){
	
	$samba=new samba();
	$keypath=$samba->GetShareName(base64_decode($_GET["path"]));
	$hosts=explode(" ",$samba->main_array[$keypath]["hosts allow"]);
	
	
	
	
	if(is_array($hosts)){
	while (list ($index, $host) = each ($hosts) ){
		if($host==null){continue;}
		$hote[$host]=$host;
		
	}}
	$data = array();$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();	
	if(!is_array($hote)){json_error_show("No computer added to {$_GET["path"]}",1);}
	


	while (list ($index, $host) = each ($hote) ){
			$id=md5($host);
			$delete=imgsimple("delete-24.png","{add}","SimpleSearchDeleteComputerPath('$host','$id')");	
			$data['rows'][] = array(
				'id' => $id,
				'cell' => array(
				"<img src='img/computer-32.png'>",
				"<span style='font-size:16px'>$host</span>",
				$delete
				)
			);
}
	
	
echo json_encode($data);
	
	
	
}

function SimpleShareAddCompToPath(){
	$uid=$_POST["add-uid"];
	

	
	$samba=new samba();
	$keypath=$samba->GetShareName(base64_decode($_POST["path"]));
	$hosts=explode(" ",$samba->main_array[$keypath]["hosts allow"]);
	if(is_array($hosts)){
	while (list ($index, $host) = each ($hosts) ){
		if($host==null){continue;}
		$hote[$host]=$host;
		
	}}	
	
	$comp=new computers($uid);
	$pdns=new pdns();
	$array=$pdns->IpToHosts($comp->ComputerIP);	
	if(is_array($array)){
		while (list ($index, $val) = each ($array) ){
			$hote[$val]=$val;
		}
	}else{
		$hote[$comp->ComputerIP]=$comp->ComputerIP;
	}
	
	$hote[$comp->ComputerRealName]=$comp->ComputerRealName;
	
	if(is_array($hote)){
	while (list ($index, $host) = each ($hote) ){
			if(strpos($host,'$')>0){continue;}
			$final[]=$host;
	}}
	
	if(count($final)>0){
		$samba->main_array[$keypath]["hosts allow"]=@implode(" ",$final);
		$samba->main_array[$keypath]["hosts deny"]="ALL";
		$samba->main_array[$keypath]["public"]="yes";
		$samba->main_array[$keypath]["force user"]="root";
		$samba->main_array[$keypath]["guest ok"]="yes";
		$samba->main_array[$keypath]["read only"]="no";
		$samba->main_array[$keypath]["browseable"]="yes";
		$samba->main_array["global"]["guest account"]="nobody";
		$samba->main_array["global"]["map to guest"]="Bad Password";				
		unset($samba->main_array[$keypath]["write list"]);
		unset($samba->main_array[$keypath]["valid users"]);
		unset($samba->main_array[$keypath]["read list"]);		
	}else{
		unset($samba->main_array[$keypath]["force user"]);
		unset($samba->main_array[$keypath]["public"]);
		unset($samba->main_array[$keypath]["guest ok"]);
		unset($samba->main_array[$keypath]["read only"]);
		unset($samba->main_array[$keypath]["hosts deny"]);
		unset($samba->main_array[$keypath]["hosts allow"]);
	}
	
	
	
	$samba->SaveToLdap();
}

function SimpleShareAddAllComp(){
		$samba=new samba();
		$keypath=$samba->GetShareName(base64_decode($_POST["path"]));
	
		$samba->main_array[$keypath]["hosts allow"]="ALL";
		unset($samba->main_array[$keypath]["hosts deny"]);
		$samba->main_array[$keypath]["public"]="yes";
		$samba->main_array[$keypath]["force user"]="root";
		$samba->main_array[$keypath]["guest ok"]="yes";
		$samba->main_array[$keypath]["read only"]="no";
		$samba->main_array[$keypath]["browseable"]="yes";
		$samba->main_array["global"]["guest account"]="nobody";
		$samba->main_array["global"]["map to guest"]="Bad Password";				
		unset($samba->main_array[$keypath]["write list"]);
		unset($samba->main_array[$keypath]["valid users"]);
		unset($samba->main_array[$keypath]["read list"]);		
		$samba->SaveToLdap();	
	
}

function SimpleShareDelCompToPath(){
	$uid=$_POST["del-uid"];
	$samba=new samba();
	$keypath=$samba->GetShareName(base64_decode($_POST["path"]));
	$hosts=explode(" ",$samba->main_array[$keypath]["hosts allow"]);
	if(is_array($hosts)){
	while (list ($index, $host) = each ($hosts) ){
		if($host==null){continue;}
		$hote[$host]=$host;
		
	}}	

	unset($hote[$uid]);
	
	
	
	if(is_array($hote)){
	while (list ($index, $host) = each ($hote) ){
			$final[]=$host;
	}}	

	
if(count($final)>0){
		$samba->main_array[$keypath]["hosts allow"]=@implode(" ",$final);
		$samba->main_array[$keypath]["hosts deny"]="0.0.0.0/0";
		$samba->main_array[$keypath]["public"]="yes";
		$samba->main_array[$keypath]["force user"]="root";
		$samba->main_array[$keypath]["guest ok"]="yes";
		$samba->main_array[$keypath]["read only"]="no";
		$samba->main_array[$keypath]["browseable"]="yes";
		$samba->main_array["global"]["guest account"]="nobody";
		$samba->main_array["global"]["map to guest"]="Bad Password";				
		unset($samba->main_array[$keypath]["write list"]);
		unset($samba->main_array[$keypath]["valid users"]);
		unset($samba->main_array[$keypath]["read list"]);		
	}else{
		unset($samba->main_array[$keypath]["force user"]);
		unset($samba->main_array[$keypath]["public"]);
		unset($samba->main_array[$keypath]["guest ok"]);
		unset($samba->main_array[$keypath]["read only"]);
		unset($samba->main_array[$keypath]["hosts deny"]);
		unset($samba->main_array[$keypath]["hosts allow"]);		
	}	
	
	$samba->SaveToLdap();
	
}
	

?>