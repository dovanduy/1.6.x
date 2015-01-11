<?php
if($_GET["verbose"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
	
	$user=new usersMenus();
		
	if($user->SQUID_INSTALLED==false){$tpl=new templates();echo "<H2>". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."</H2>";die();exit();}
	if($user->AsSquidAdministrator==false){$tpl=new templates();echo "<H2>". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."</H2>";die();exit();}
	
	
	
	if(isset($_GET["adduri"])){adduri();exit;}
	if(isset($_GET["list"])){table_list();exit;}
	if(isset($_POST["EnableDNSBL"])){EnableDNSBL();exit;}
	
popup();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{CICAP_DNSBL}");
	
	$html="
	function cicap_dnsbl_load(){
			YahooWin(600,'$page?popup=yes','$title');
		}
		
	 var x_cicap_dnsbl_enable= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			
		}		
		
	function cicap_dnsbl_enable(md,uri){
		var XHR = new XHRConnection();
		XHR.appendData('adduri',uri);
		if(document.getElementById(md).checked){XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
		XHR.sendAndLoad('$page', 'GET',x_cicap_dnsbl_enable);
	}
		

	cicap_dnsbl_load();";
	echo $html;
	
}





function popup(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	if(!$q->TABLE_EXISTS("webfilter_dnsbl")){$q->CheckTables();}
	$tpl=new templates();
	$add=$tpl->_ENGINE_parse_body("{add}");
	$dsnbl_service=$tpl->_ENGINE_parse_body("{dnsbl_service}");
	$service_name=$tpl->_ENGINE_parse_body("{description}");
	$CICAP_DNSBL=$tpl->_ENGINE_parse_body("{CICAP_DNSBL}");
	$DNSBL_WHY=$tpl->_ENGINE_parse_body("{DNSBL_WHY}");
	$p=Paragraphe_switch_img("{enable_dnsbl_service}", $DNSBL_WHY);
	
	$buttons="buttons : [
		{name: '$add', bclass: 'add', onpress : AddDnsblService}
		
		],";
	$buttons=null;
	
	$html="<div class=text-info style='font-size:13px'>$DNSBL_WHY</div>
	<table class='dnsbl-table' style='display: none' id='dnsbl-table' style='width:99%'></table>
<script>
	$(document).ready(function(){
$('#dnsbl-table').flexigrid({
	url: '$page?list=yes',
	dataType: 'json',
	colModel : [
		{display: '$dsnbl_service', name : 'dnsbl', width :233, sortable : true, align: 'left'},
		{display: '$service_name', name : 'name', width : 541, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'none3', width : 24, sortable : false, align: 'left'},
	],
$buttons	
	searchitems : [
		{display: '$extension', name : 'ext'},
		{display: '$description', name : 'description'},
		],
	sortname: 'name',
	sortorder: 'asc',
	usepager: true,
	title: '$CICAP_DNSBL',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: 854,
	height: 350,
	singleSelect: true
	
	});   
});

function AddDnsblService(){

}






var x_EnableDisableCiCapDNSBL= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		
	}			
	
	
	function EnableDisableCiCapDNSBL(md5,serv){
		var XHR = new XHRConnection();
		XHR.appendData('EnableDNSBL',serv);
		if(document.getElementById(md5).checked){
		XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
		XHR.sendAndLoad('$page', 'POST',x_EnableDisableCiCapDNSBL);	
	}

</script>";
echo $html;
}
function EnableDNSBL(){
	$q=new mysql_squid_builder();
	$sql="UPDATE webfilter_dnsbl SET enabled={$_POST["enabled"]} WHERE dnsbl='{$_POST["EnableDNSBL"]}'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?cicap_reconfigure=yes");
	
	
}

function table_list(){

	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	
	$search='%';
	$table="webfilter_dnsbl";
	
	
	
	
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*{$_POST["query"]}*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($q->mysql_error,"", "",""));json_encode($data);return;}
		$total = $ligne["TCOUNT"];
		writelogs("$sql = $total rows",__FUNCTION__,__FILE__,__LINE__);
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($q->mysql_error,"", "",""));json_encode($data);return;}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";	
	
	$results = $q->QUERY_SQL($sql);
	writelogs("$sql = ".mysql_num_rows($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	if(mysql_num_rows($results)==0){$q->CheckTables();$results = $q->QUERY_SQL($sql);}
	
	if(!$q->ok){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($q->mysql_error,"", "",""));json_encode($data);return;}
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	$md5=md5($ligne["dnsbl"]);
	while ($ligne = mysql_fetch_assoc($results)) {
		$disable=Field_checkbox($md5, 1,$ligne["enabled"],"EnableDisableCiCapDNSBL('$md5','{$ligne['dnsbl']}');");
		$js="<a href=\"javascript:blur();\" OnClick=\"javascript:s_PopUp('{$ligne['uri']}',650,480,'');\"
		style='font-size:12px;text-decoration:underline'>";
		if($ligne['uri']==null){$js=null;}
		
	$data['rows'][] = array(
		'id' => $ligne['dnsbl'],
		'cell' => array("<span style='font-size:14px;font-weight:bolder'>{$ligne["dnsbl"]}</span>","<span style='font-size:14px'>{$ligne['name']}</span></a>
		<div style='font-size:11px'>$js{$ligne['uri']}</a></div>",$disable)
		);
	}
	
	
echo json_encode($data);	
	
	
}

function adduri(){
$sock=new sockets();
$datas=explode("\n",$sock->GET_INFO("CicapDNSBL"));
while (list ($num, $line) = each ($datas)){
	if(strlen($line)<4){continue;}
	$array[$line]=$line;
}

if($_GET["enabled"]==1){
	$array[$_GET["adduri"]]=$_GET["adduri"];
}else{
	unset($array[$_GET["adduri"]]);
}
while (list ($num, $line) = each ($array)){
	$new[]=$line;
}

$sock->SaveConfigFile(implode("\n",$new),"CicapDNSBL");
$sock->getFrameWork("cmd.php?cicap_reconfigure=yes");
}

?>