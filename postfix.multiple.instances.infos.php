<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.maincf.multi.inc');
	
	if(posix_getuid()==0){die();}
	
	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["advisp"])){getlist_adv();exit;}
	if(isset($_GET["popup-list"])){getlist();exit;}
	if(isset($_GET["delete"])){DELETE();exit;}
	if(isset($_GET["popup-start"])){popup_start();exit;}
	
js();



function js(){
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{POSTFIX_MULTI_INSTANCE_INFOS}");
	$ask_perform_operation_delete_item=$tpl->_ENGINE_parse_body("{ask_perform_operation_delete_item}");
	$page=CurrentPageName();
	$start="POSTFIX_MULTI_INSTANCE_INFOS_START()";
	if(isset($_GET["newfrontend"])){$newfrontend="&newfrontend=yes";}
	if(isset($_GET["iniline"])){
		$t=time();
			$start="POSTFIX_MULTI_INSTANCE_INFOS_START_TAB()";
			echo "
			<div id='$t'></div>
			<script>";
	}
	
	$html="
	function POSTFIX_MULTI_INSTANCE_INFOS_START(){
		$('#multiples-instances-list-start').remove();
		YahooWin('550','$page?popup=yes','$title');
	}
	
	function POSTFIX_MULTI_INSTANCE_INFOS_START_TAB(){
		$('#multiples-instances-list-start').remove();
		LoadAjax('$t','$page?popup=yes&$newfrontend');
	}	
	
	function POSTFIX_MULTI_INSTANCE_INFOS_LIST(){
		RefreshTableMultiples();
	}
	
	var X_POSTFIX_MULTI_INSTANCE_INFOS_DEL= function (obj) {
	 var results=obj.responseText;
	 if(results.length>1){alert(results);}
	 RefreshTableMultiples();
	}	
	
	function POSTFIX_MULTI_INSTANCE_INFOS_DEL(ou,ip){
		if(confirm('$ask_perform_operation_delete_item\\n'+ou+'('+ip+')')){
				var XHR = new XHRConnection();
				XHR.appendData('delete','yes');
				XHR.appendData('ou',ou);
				XHR.appendData('ip',ip);
				document.getElementById('multiples-instances-list').innerHTML='<center><img src=img/wait_verybig.gif></center>';
				XHR.sendAndLoad('$page', 'GET',X_POSTFIX_MULTI_INSTANCE_INFOS_DEL);	
		}
	
	}
	
	$start;
	";
	
	echo $html;
	if(isset($_GET["iniline"])){
		
			echo "</script>";
	}
	
	
}

function DELETE(){
	$sql="DELETE FROM postfix_multi WHERE ou='{$_GET["ou"]}' AND ip_address='{$_GET["ip"]}'";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");	
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-multi-configure-ou={$_GET["ou"]}");
}


function popup(){
	if(isset($_GET["newfrontend"])){$newfrontend="&newfrontend=yes";}
	$page=CurrentPageName();
	$html="
	<div id='multiples-instances-list-start'></div>
	<script>
		function RefreshTableMultiples(){
			 LoadAjax('multiples-instances-list-start','$page?popup-start=yes$newfrontend');
			 $('#table-postfix-multiples-instances').flexReload();
		
		}
		RefreshTableMultiples();
	</script>
	
	";
	
	echo $html;
	
}
function popup_start(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$servername=$tpl->_ENGINE_parse_body("{hostname}");
	$organization=$tpl->_ENGINE_parse_body("{organization}");
	$ip_address=$tpl->_ENGINE_parse_body("{ip_address}");
	$category=$tpl->_ENGINE_parse_body("{category}");	
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$add=$tpl->_ENGINE_parse_body("{add}");
	$instances=$tpl->_ENGINE_parse_body("{instances}");
	$queue=$tpl->_ENGINE_parse_body("{queue}");
	$POSTFIX_MULTI_INSTANCE_INFOS=$tpl->_ENGINE_parse_body("{POSTFIX_MULTI_INSTANCE_INFOS}");
	$POSTFIX_MULTI_INSTANCE_INFOS_TEXT=$tpl->_ENGINE_parse_body("{POSTFIX_MULTI_INSTANCE_INFOS_TEXT}");
	$advanced_ISP_routing=$tpl->_ENGINE_parse_body("{advanced_ISP_routing}");
	$advanced_options=$tpl->_ENGINE_parse_body("{advanced_options}");
	$instances=$tpl->_ENGINE_parse_body("{instances}");
	$q=new mysql();
	$mysqlerror=null;
	$sql="SELECT COUNT(*) as TCOUNT FROM `postfix_multi` WHERE `key` = 'myhostname'";
	if($GLOBALS["VERBOSE"]){echo "\n<br><strong>$sql</strong>";}
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!$q->ok){
		$mysqlerror="<table style='width:99% class=legend>
		<tbody>
		<tr>
		<td width=1%><img src='img/warning-panneau-32.png'></td>
		<td><strong style='color:red'>{mysql_error}:$q->mysql_error</strong></td>
		</tr>
		</tbody>
		</table>
		
		";
	}
	$total = $ligne["TCOUNT"];	
	
	
	$TB_WIDTH=534;
	
	if(isset($_GET["newfrontend"])){
		$TB_WIDTH=654;
		
	}
	
	$html="
		
		$mysqlerror
	<table class='multiples-instances-list' style='display: none' id='multiples-instances-list' style='width:99%'></table>
	
<script>
$(document).ready(function(){
$('#multiples-instances-list').flexigrid({
	url: '$page?popup-list=yes',
	dataType: 'json',
	colModel : [
		{display: '$servername', name : 'servername', width :197, sortable : true, align: 'left'},
		{display: '$organization', name : 'ou', width : 115, sortable : true, align: 'left'},
		{display: '$ip_address', name : 'ip_address', width : 90, sortable : true, align: 'left'},
		{display: '$queue', name : 'queue', width : 131, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 41, sortable : false, align: 'center'},
	],
buttons : [
		{name: '$add', bclass: 'add', onpress : AddNewInstance},
		{separator: true},
		{name: '$advanced_ISP_routing', bclass: 'Search', onpress : AdvISPRoutingSelect},
		{name: '$instances', bclass: 'Search', onpress : AdvInstancesSelect},
		{separator: true},
		{name: '$advanced_options', bclass: 'Reconf', onpress : Reconfadvanced_options},
		
		],	
	searchitems : [
		{display: '$servername', name : 'servername'},
		{display: '$organization', name : 'ou'},
		{display: '$ip_address', name : 'ip_address'}
		],
	sortname: 'ip_address',
	sortorder: 'desc',
	usepager: true,
	title: '$POSTFIX_MULTI_INSTANCE_INFOS - $total $instances',
	useRp: true,
	rp: 15,
	showTableToggleBtn: true,
	width: $TB_WIDTH,
	height: 300,
	singleSelect: true
	
	});   
});
function AddNewInstance() {
	Loadjs('postfix.multiple.instances.wizard.php');
	
}

function AdvISPRoutingSelect(){
	//$('#multiples-instances-list').flexOptions({buttons : [{name: '$add', bclass: 'add', onpress : AddNewInstance},{separator: true},{name: '$instances', bclass: 'Search', onpress : InstancesSelect}]}); 
	$('#multiples-instances-list').flexOptions({url: '$page?popup-list=yes&advisp=yes'}).flexReload(); 
}

function AdvInstancesSelect(){
	$('#multiples-instances-list').flexOptions({url: '$page?popup-list=yes'}).flexReload();  
}

function Reconfadvanced_options(){
	Loadjs('postfix.multiple.adv.php');
}

</script>
	
	
	";
	echo $html;
}

function getlist(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	
	
	$search='%';
	$table="postfix_multi";
	$page=1;
	$ORDER="ORDER BY ip_address DESC";
	
	
	if(isset($_GET["advisp"])){
		$table="postfix_smtp_advrt";
	}
	
	$mails=$tpl->_ENGINE_parse_body("{mails}");
	$sql="SELECT SUM(`size`) as tsize,COUNT(msgid) as tcount,instance FROM postqueue GROUP BY instance";	
	$results = $q->QUERY_SQL($sql,"artica_events");
	while ($ligne = mysql_fetch_assoc($results)) {
		$queues[$ligne["instance"]]=FormatBytes($ligne["tsize"]/1024)." {$ligne["tcount"]} $mails";
	}
	
	
	if($q->COUNT_ROWS($table,"artica_backup")==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			
			if($_POST["sortname"]=="servername"){$_POST["sortname"]="value";}
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		if($_POST["qtype"]=="servername"){$searchstring="AND (`value` LIKE '$search')";}
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring AND (`key` = 'myhostname')";
		
		
		
		
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE `key` = 'myhostname'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring AND `key` = 'myhostname' $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$results = $q->QUERY_SQL($sql,"artica_backup");
	$divstart="<span style='font-size:14px;font-weight:bold'>";
	$divstop="</div>";
	$noneTXT=$tpl->_ENGINE_parse_body("{none}");
	while ($ligne = mysql_fetch_assoc($results)) {

			$maincf=new maincf_multi($ligne["value"]);
			$enabled=1;
			$fontcolor="black";
			if($maincf->GET("DisabledInstance")==1){$enabled=0;$fontcolor="#B3B3B3";}
		
				if(trim($ligne['mac'])==null){$ligne['mac']="<img src='img/status_warning.png'>";}
				$delete=$tpl->_ENGINE_parse_body(imgsimple("delete-24.png","{delete}","POSTFIX_MULTI_INSTANCE_INFOS_DEL('{$ligne["ou"]}','{$ligne["ip_address"]}')"));
				$edit="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?add-arp-js={$ligne['ID']}');\" style='font-size:14px;font-weight:bold;text-decoration:underline;color:$fontcolor'>";
				
				
				
				
				
				$js="javascript:YahooWin('650','domains.postfix.multi.config.php?ou={$ligne['ou']}&hostname={$ligne['value']}','{$ligne['value']}');";
				$js="<a href=\"$js\" style='text-decoration:underline;font-size:14px;color:$fontcolor'>";
				if(trim($ligne['ou'])==null){$ligne['ou']=$noneTXT;}
				if($queues[$ligne["value"]]==null){$queues[$ligne["value"]]="0 KB 0 $mails";}
				$data['rows'][] = array(
					'id' => $ligne['ip_address'],
					'cell' => array("$js{$ligne['value']}</a>", "<span style=';color:$fontcolor'>{$ligne['ou']}</span>",
					 $divstart."<span style=';color:$fontcolor'>{$ligne["ip_address"]}</span>".$divstop,
					 "<span style='font-size:12px;color:$fontcolor'>{$queues[$ligne["value"]]}</span>",
					 $delete)
					);
	}
	
	
echo json_encode($data);			
}
function getlist_adv(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	
	
	$search='%';
	$table="postfix_smtp_advrt";
	$page=1;
	
	$mails=$tpl->_ENGINE_parse_body("{mails}");
	$sql="SELECT SUM(`size`) as tsize,COUNT(msgid) as tcount,instance FROM postqueue GROUP BY instance";	
	$results = $q->QUERY_SQL($sql,"artica_events");
	while ($ligne = mysql_fetch_assoc($results)) {
		$queues[$ligne["instance"]]=FormatBytes($ligne["tsize"]/1024)." {$ligne["tcount"]} $mails";
	}
	
	
	if($q->COUNT_ROWS($table,"artica_backup")==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			
			if($_POST["sortname"]=="servername"){$_POST["sortname"]="hostname";}
			if($_POST["sortname"]=="ip_address"){$_POST["sortname"]="hostname";}
			
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="HAVING (`{$_POST["qtype"]}` LIKE '$search')";
		
		$sql="SELECT COUNT(*)as TCOUNT,hostname as servername  FROM `$table` GROUP BY  servername $searchstring";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		$total = mysql_num_rows($results);
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT,hostname FROM `$table` GROUP BY hostname";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		$total = mysql_num_rows($results);
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT `$table`.hostname  FROM `$table` GROUP BY hostname $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$results = $q->QUERY_SQL($sql,"artica_backup");
	$divstart="<span style='font-size:14px;font-weight:bold'>";
	$divstop="</div>";
	$noneTXT=$tpl->_ENGINE_parse_body("{none}");
	while ($ligne = mysql_fetch_assoc($results)) {
		
	
	$delete="&nbsp;";
	
	
	$ff=new maincf_multi($ligne['hostname']);
	$js="javascript:Loadjs('postfix.isp-routing.php?ou=$ff->ou&hostname={$ligne['hostname']}');";
	$js="<a href=\"$js\" style='text-decoration:underline;font-size:14px'>";
	if(trim($ligne['ou'])==null){$ligne['ou']=$noneTXT;}
	if($queues[$ligne['hostname']]==null){$queues[$ligne['hostname']]="0 KB 0 $mails";}
	$data['rows'][] = array(
		'id' => $ff->ip_addr,
		'cell' => array("$js{$ligne['hostname']}</a>", $ff->ou,
		 $divstart.$ff->ip_addr.$divstop,
		 "<span style='font-size:12px'>{$queues[$ligne['hostname']]}</span>",
		 $delete)
		);
	}
	
	
echo json_encode($data);			
}	
	




?>