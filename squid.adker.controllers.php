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


if(isset($_GET["table"])){table();exit;}
if(isset($_GET["controller-js"])){controller_js();exit;}
if(isset($_GET["controller-popup"])){controller_popup();exit;}
if(isset($_POST["ipaddr"])){controller_save();exit;}
if(isset($_GET["search-list"])){ search();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete();exit;}
js();


function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{controllers}");
	echo "
	YahooWin3Hide();
	YahooWin3('750','$page?table=yes','$title');";


}
function delete_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	
	$md5=$_GET["delete-js"];
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	$t=time();
	$hostname=$hostname=$array["Controllers"][$md5]["hostname"];
	$title=$tpl->javascript_parse_text("{delete} $hostname ?");
	
	echo "
	var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#SQUID_ADKER_CONTROLLERS_TABLE').flexReload();
}	
function Save$t(){
	if(!confirm('$title')){return;}
	var XHR = new XHRConnection();
	
	XHR.appendData('delete','$md5');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
	
	 Save$t();";


}
function controller_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	if($_GET["controller-js"]==null){
		$title=$tpl->javascript_parse_text("{new_controller}");
	}else{
		$md5=$_GET["controller-js"];
		$sock=new sockets();
		$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
		
		$hostname=$hostname=$array["Controllers"][$md5]["hostname"];
		$ipaddr=$array["Controllers"][$md5]["ipaddr"];
		$UseIPaddr=$array["Controllers"][$md5]["UseIPaddr"];
		$title=$hostname."/$ipaddr";
	}
	
	echo "YahooWin4Hide();YahooWin4('650','$page?controller-popup={$_GET["controller-js"]}','$title');";
}

function controller_save(){
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	if($_POST["hostname"]==null){
		if($_POST["ipaddr"]==null){return;}
		$_POST["hostname"]=$_POST["ipaddr"];
	}
	
	if($_POST["md5"]==null){$md5=md5($_POST["hostname"]);}else{$md5=$_POST["md5"];}
	$array["Controllers"][$md5]["hostname"]=$_POST["hostname"];
	$array["Controllers"][$md5]["ipaddr"]=$_POST["ipaddr"];
	$array["Controllers"][$md5]["UseIPaddr"]=$_POST["UseIPaddr"];
	$sock->SaveConfigFile(base64_encode(serialize($array)), "KerbAuthInfos");
	$sock->getFrameWork("squid.php?krb5conf=yes");
	
}

function delete(){
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	unset($array["Controllers"][$_POST["delete"]]);
	$sock->SaveConfigFile(base64_encode(serialize($array)), "KerbAuthInfos");
	$sock->getFrameWork("squid.php?krb5conf=yes");
}

function controller_popup(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();	
	$button_name="{apply}";
	$t=time();
	if($_GET["controller-popup"]==null){
		$title=$tpl->javascript_parse_text("{new_controller}");
		$button_name="{add}";
	}else{
		$md5=$_GET["controller-popup"];
		$sock=new sockets();
		$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
		
		$hostname=$hostname=$array["Controllers"][$md5]["hostname"];
		$ipaddr=$array["Controllers"][$md5]["ipaddr"];
		$UseIPaddr=$array["Controllers"][$md5]["UseIPaddr"];
		$title=$hostname;
		
	}
	
	
	$html="<div style='width:98%' class=form>
	<div style='font-size:26px;margin-bottom:20px'>$title</div>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{hostname}:</td>
		<td>". Field_text("hostname-$t",$hostname,"font-size:18px",null,null,null,false,"blur()")."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:18px'>{ipaddr}:</td>
		<td>". field_ipv4("ipaddr-$t",$ipaddr,"font-size:18px",null,null,null,false,"blur()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{use_ipaddr}:</td>
		<td>". Field_checkbox_design("UseIPaddr-$t",1,$UseIPaddr)."</td>
	</tr>
	<tr>
		<td colspan=2 align='right' style='padding-top:20px'>". button($button_name,"Save$t()",26)."</td>
	</tr>
	</table>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	var md5='$md5';
	if(results.length>3){alert(results);return;}
	$('#SQUID_ADKER_CONTROLLERS_TABLE').flexReload();
	if(md5.length==0){ YahooWin4Hide(); }
}	
function Save$t(){
	var XHR = new XHRConnection();
	if(document.getElementById('UseIPaddr-$t').checked){XHR.appendData('UseIPaddr',1);}else{XHR.appendData('UseIPaddr',0);}
	XHR.appendData('md5','$md5');
	XHR.appendData('ipaddr',document.getElementById('ipaddr-$t').value);
	XHR.appendData('hostname',document.getElementById('hostname-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);
}



function table(){

	//host -t srv _kerberos._tcp.DOMAIN.EXAMPLE.COM




	$page=CurrentPageName();
	$tpl=new templates();
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$proto=$tpl->_ENGINE_parse_body("{proto}");
	$uri=$tpl->_ENGINE_parse_body("{url}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$user=$tpl->_ENGINE_parse_body("{user}");
	$title=$tpl->_ENGINE_parse_body("{today}: {realtime_requests} ".date("H")."h");
	$new_controller=$tpl->_ENGINE_parse_body("{new_controller}");
	$delete_rule=$tpl->javascript_parse_text("{delete_rule}");
	$refresh=$tpl->_ENGINE_parse_body("{refresh}");
	$deleteAll=$tpl->_ENGINE_parse_body("{delete_all}");
	$apply=$tpl->_ENGINE_parse_body("{apply_parameters}");
	$export=$tpl->_ENGINE_parse_body("{export}");
	$t=time();
	$title=$tpl->javascript_parse_text("{controllers_in_the_same_domain}");

	


	$buttons="
	buttons : [
	{name: '$new_controller', bclass: 'Add', onpress : NewController$t},
	

	],	";


	$html="
			<table class='SQUID_ADKER_CONTROLLERS_TABLE' style='display: none' id='SQUID_ADKER_CONTROLLERS_TABLE' style='width:100%'></table>
<script>
$(document).ready(function(){
	$('#SQUID_ADKER_CONTROLLERS_TABLE').flexigrid({
	url: '$page?search-list=yes&t=$t',
	dataType: 'json',
	colModel : [
{display: '$hostname', name : 'uid', width :420, sortable : true, align: 'left'},
{display: '$ipaddr', name : 'poll', width :186, sortable : false, align: 'left'},
{display: 'DEL', name : 'DEL', width : 53, sortable : false, align: 'center'},
	],$buttons


	sortname: 'uid',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:22px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 300,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
});

function NewController$t(){
	Loadjs('$page?controller-js=');
}

function ApplyParams$t(){

}

</script>";
echo $html;
return;
}
function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$page=1;
	$rp=50;
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	
	$span="<span style='font-size:18px;color:#616161'>";
	$adhost="{$array["WINDOWS_SERVER_NETBIOSNAME"]}.{$array["WINDOWS_DNS_SUFFIX"]}";
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = count($array["Controllers"])+1;
	$data['rows'] = array();


	$data['rows'][] = array(
			'id' => "DEFAULT",
			'cell' => array(
					$span."</a>$adhost - DEFAULT</span>",
					$span.$array["ADNETIPADDR"]."</a></span>",
					$span.null."</span>",
					
			)
	);
	

	while (list ($md5, $array2) = each ($array["Controllers"]) ){
		$color="black";
		$js="Loadjs('$MyPage?controller-js=$md5');";
		$href="<a href=\"javascript:blur();\"
		OnClick=\"javascript:$js\"
		style='font-size:18px;font-weight:bold;text-decoration:underline;color:$color' >";
		
		$hostname=$array2["hostname"];
		$ipaddr=$array2["ipaddr"];
		$UseIPaddr=$array2["UseIPaddr"];
		
		

		$delete=imgsimple("delete-24.png",null,"Loadjs('$MyPage?delete-js=$md5')");
	
		$ligne["proto"]=strtoupper($ligne["proto"]);
		$data['rows'][] = array(
				'id' => $md5,
				'cell' => array(
						$span.$href.$hostname."</a></span>",
						$span.$href.$ipaddr."</a></span>",
						$delete
				)
		);
	}


	echo json_encode($data);

}