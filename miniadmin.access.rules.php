<?php
session_start();

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class=error-text>");
ini_set('error_append_string',"</p>\n");

if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");

if(isset($_POST["bubble-edit"])){edit_port_save();exit;}
if(isset($_GET["content"])){content();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["aclrules"])){acl_search();exit;}
if(isset($_GET["search-rule"])){acl_list();exit;}
if(isset($_GET["new-port"])){new_port();exit;}
if(isset($_POST["bubble-add"])){save_new_port();exit;}
if(isset($_GET["js-port"])){edit_port_js();exit;}
if(isset($_GET["port-edit"])){edit_port();exit;}


$users=new usersMenus();
if(!$users->AsDansGuardianAdministrator){die();}

main_page();

function main_page(){
	
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes&startup={$_GET["startup"]}&title={$_GET["title"]}')</script>", $content);
	echo $content;	
}



function edit_port_js(){
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT portname FROM webfilters_sqaclsports WHERE aclport='{$_GET["js-port"]}'"));
	$title=utf8_encode($ligne["portname"]);	
	echo "YahooWin3('600','$page?port-edit={$_GET["js-port"]}&t={$_GET["t"]}','$title')";
	
}


function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();

	$title="{access_rules}";
	if($_GET["title"]<>null){$title="{{$_GET["title"]}}";}
	
	$start="LoadAjax('left-$t','$page?left=yes');";
	$head=null;


	if($_GET["title"]<>null){$title="{{$_GET["title"]}}";}
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a>$head
	</div>
	<H1>$title Proxy</H1>
	<p>{access_rules_text}</p>
	</div>	
	<div id='center-$t' class=BodyContent></div>
	<script>
		LoadAjax('center-$t','$page?tabs=yes&t=$t');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$squid=new squidbee();
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	if(!is_numeric($squid->second_listen_port)){$squid->second_listen_port=0;}
	$Listen_port="{default}: $squid->listen_port";
	if($squid->second_listen_port>0){
		$Listen_port="$Listen_port {and} $squid->second_listen_port";
	}
	$sock=new sockets();
	$SquidBubbleMode=$sock->GET_INFO("SquidBubbleMode");
	if(!is_numeric($SquidBubbleMode)){$SquidBubbleMode=0;}
	
	if(isset($_GET["title"])){
		$title=$tpl->_ENGINE_parse_body("<H4>{access_rules}</H4><p>{access_rules_text}</p>");
	}
	
	$array[$Listen_port]="$page?aclrules=yes&listen-port=0&t=$t";
	if($SquidBubbleMode==1){
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM webfilters_sqaclsports ORDER BY aclport";
		$results = $q->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {
			$array["{$ligne["portname"]}:{$ligne["aclport"]}"]="$page?aclrules=yes&listen-port={$ligne["aclport"]}&t=$t";
			
		}
		
		$array["{new_port}"]="$page?new-port=yes&t=$t";
	}
	echo $title.$boot->build_tab($array);	
	
}

function edit_port(){
	$t=$_GET["t"];
	$port=$_GET["port-edit"];
	include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();	
	$ip=new networking();
	$ips=$ip->ALL_IPS_GET_ARRAY();
	$ipz["0.0.0.0"]="{all}";
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_sqaclsports WHERE aclport='{$_GET["port-edit"]}'"));	
	
	while (list ($ip, $line) = each ($ips) ){
		$ipz[$ip]=$ip;
	}
	$boot->set_field("portname", "{rulename}", $ligne["portname"],array("ENCODE"=>true));
	$boot->set_hidden("aclport", "{$_GET["port-edit"]}");
	$boot->set_checkbox("enabled", "{enabled}", $ligne["enabled"]);
	$boot->set_list("interface", "{listen_address}", $ipz,$ligne["interface"]);
	
	
	$boot->set_button("{apply}");
	$boot->set_hidden("aclport", "{$_GET["port-edit"]}");
	$boot->set_hidden("bubble-edit", "yes");
	$boot->set_formtitle("{port}: {$_GET["port-edit"]}");
	$boot->set_CallBack("LoadAjax('center-$t','$page?tabs=yes&t=$t');");
	$form=$boot->Compile();
	echo $tpl->_ENGINE_parse_body($form);	
	
	
}

function edit_port_save(){
	$port=$_POST["aclport"];
	if(!is_numeric($port)){return;}
	$q=new mysql_squid_builder();
	$_POST["portname"]=mysql_escape_string(url_decode_special_tool($_POST["portname"]));
	$sql="UPDATE webfilters_sqaclsports SET 
		portname='{$_POST["portname"]}',
		interface='{$_POST["interface"]}',
		enabled='{$_POST["enabled"]}'
		WHERE aclport=$port
			
	";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
}

function new_port(){
	$t=$_GET["t"];
	include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	
	$ip=new networking();
	$ips=$ip->ALL_IPS_GET_ARRAY();
	$ipz["0.0.0.0"]="{all}";
	while (list ($ip, $line) = each ($ips) ){
		$ipz[$ip]=$ip;
	}
	$boot->set_field("portname", "{rulename}", "MyNew port",array("ENCODE"=>true));
	$boot->set_field("aclport", "{listen_port}", "9090");
	$boot->set_list("interface", "{listen_address}", $ipz,"0.0.0.0");
	
	
	$boot->set_button("{add}");
	$boot->set_hidden("bubble-add", "yes");
	$boot->set_formtitle("{new_port}");
	$boot->set_CallBack("LoadAjax('center-$t','$page?tabs=yes&t=$t');");
	$form=$boot->Compile();
	echo $tpl->_ENGINE_parse_body($form);
}
function save_new_port(){
	$q=new mysql_squid_builder();
	
	$_POST["portname"]=mysql_escape_string(url_decode_special_tool($_POST["portname"]));
	$sql="INSERT IGNORE INTO webfilters_sqaclsports (portname,aclport,interface,enabled) 
	VALUES ('{$_POST["portname"]}','{$_POST["aclport"]}','{$_POST["interface"]}',1)";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
}

function acl_search(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();	
	$boot=new boostrap_form();
	$button=button("{new_rule}","Loadjs('squid.acls-rules.php?Addacl-js=yes&ID=-1&listen-port={$_GET["listen-port"]}&t=$t');",16);
	$apply_params=$tpl->_ENGINE_parse_body("{apply}");
	$button_edit=null;
	$button_groups=button("{proxy_objects}","Loadjs('squid.acls.groups.php?js=yes&toexplainorg=table-$t');",16);
	$apply_params=button("{apply}","Loadjs('squid.compile.php');",16);
	
	$new_group=button("{new_group}","Loadjs('squid.acls-rules.php?Addacl-group=yes&ID=-1&t=$t');",16);
	$SearchQuery=$boot->SearchFormGen("aclname","search-rule","&listen-port={$_GET["listen-port"]}");	
	
	if($_GET["listen-port"]>0){
		$button_edit=" ".button("{bubble_rule}","Loadjs('$page?js-port={$_GET["listen-port"]}');",16);
	}
	
	$html="
	<table style='width:100%'>
	<tr>
	<td>$button $new_group $button_groups$button_edit $apply_params</td>
	<td></td>
	</tr>
	</table>
	$SearchQuery
	<script>
	ExecuteByClassName('SearchFunction');
	</script>
	";
		
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function acl_list(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$boot=new boostrap_form();
	$RULEID=$_GET["RULEID"];
	$t=$_GET["t"];
	$search='%';
	$table="webfilters_sqacls";
	$page=1;
	$data = array();
	$data['rows'] = array();
	$sock=new sockets();
	$gliff="<i class='icon-ok'></i>";
	$EnableSquidPortsRestrictions=$sock->GET_INFO("EnableSquidPortsRestrictions");
	if(!is_numeric($EnableSquidPortsRestrictions)){$EnableSquidPortsRestrictions=0;}
	$ORDER="ORDER BY xORDER ASC";
	if(!is_numeric($_GET["t"])){$_GET["t"]=time();}
	$searchstring=string_to_flexquery("search-rule");
	$default=$tpl->_ENGINE_parse_body("{default}");
	$ports_restrictions=$tpl->_ENGINE_parse_body("{ports_restrictions}");
	$http_safe_ports=$tpl->_ENGINE_parse_body("{http_safe_ports}");
	$deny_ports_expect=$tpl->_ENGINE_parse_body("{deny_ports_expect}");
	$q2=new mysql();
	$items=$q2->COUNT_ROWS("urlrewriteaccessdeny", "artica_backup");
	$explain=$tpl->_ENGINE_parse_body("{urlrewriteaccessdeny_explain} <br><strong>$items {items}</strong>");
	$delete_rule_ask=$tpl->javascript_parse_text("{delete_rule_ask}");
	$WHERE="`aclport`=0  AND aclgpid=0";
	if(!is_numeric($_GET["listen-port"])){$_GET["listen-port"]=0;}
	if($_GET["listen-port"]>0){
		$q=new mysql_squid_builder();
		$WHERE="`aclport`={$_GET["listen-port"]}";
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT enabled FROM webfilters_sqaclsports WHERE aclport='{$_GET["listen-port"]}'"));
		if(!$q->ok){if(preg_match("#Unknown column#", $q->mysql_error)){$q->CheckTables();$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT enabled FROM webfilters_sqaclsports WHERE aclport='{$_GET["listen-port"]}'"));}}
		if(!$q->ok){$error_explain="<p class=text-error>$q->mysql_error</p>";}
		
		if($ligne["enabled"]==0){
			$error_explain="<p class=text-error>{this_rule_is_disabled}</p>";
		}
	}
	
	if($searchstring==null){
		if($_GET["listen-port"]==0){
			$link=$boot->trswitch("Loadjs('squid.urlrewriteaccessdeny.php?t={$_GET["t"]}')");
			$tr[]="
			<tr id='aclNone1'>
				<td $link>$gliff $default</td>
				<td $link><i class='icon-info-sign'></i> $explain</td>
				<td $link>&nbsp;</td>
				<td $link>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
			</tr>";		
		
	
			$ports=unserialize(base64_decode($sock->GET_INFO("SquidSafePortsSSLList")));
			if(is_array($ports)){while (list ($port, $explain) = each ($ports) ){$bbcSSL[]=$port;}}
			$ports=unserialize(base64_decode($sock->GET_INFO("SquidSafePortsList")));
			if(is_array($ports)){while (list ($port, $explain) = each ($ports) ){$bbcHTTP[]=$port;}}
			
			$color="black";
			$colored="#A71A05";
			if($EnableSquidPortsRestrictions==0){$color="#9C9C9C";$colored=$color;}
			$sslp="$deny_ports_expect: $http_safe_ports SSL: ".@implode(", ", $bbcSSL);
			$http="$deny_ports_expect: $http_safe_ports: ".@implode(", ", $bbcHTTP);
			$enableSSL=Field_checkbox("EnableSquidPortsRestrictions", 1,$EnableSquidPortsRestrictions,"EnableSquidPortsRestrictionsCK()");
			$link=$boot->trswitch("Loadjs('squid.advParameters.php?t={$_GET["t"]}&OnLyPorts=yes');");
			$tr[]="
			<tr id='aclNone2'>
				<td $link>$gliff <span style='color:$color'>$default</span></td>
				<td $link><i class='icon-info-sign'></i> <span style='color:$color'> $ports_restrictions
				<span style='color:$colored;font-weight:bold'><div>$sslp</div><div>$http</div></span>
				</td>
				<td $link>&nbsp;</td>
				<td $link>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
			</tr>";
	
			
			
		}
	}
	$rp=50;

	
	$sql="SELECT *  FROM `$table` WHERE $WHERE $searchstring $ORDER";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show("$q->mysql_error");}
	
	$acls=new squid_acls_groups();
	$order=$tpl->_ENGINE_parse_body("{order}:");
	while ($ligne = mysql_fetch_assoc($results)) {
		$gliff="<i class='icon-ok'></i>";
		$val=0;
		$color="black";
		$disable=Field_checkbox("aclid_{$ligne['ID']}", 1,$ligne["enabled"],"EnableDisableAclRule$t('{$ligne['ID']}')");
		$ligne['aclname']=utf8_encode($ligne['aclname']);
		$delete=imgsimple("delete-24.png",null,"DeleteSquidAclRule('{$ligne['ID']}')");
		if($ligne["enabled"]==0){$color="#9C9C9C";$gliff=null;}
		
		$explain=$tpl->_ENGINE_parse_body($acls->ACL_MULTIPLE_EXPLAIN($ligne['ID'],$ligne["enabled"],$ligne["aclgroup"]));
		
		$up=imgsimple("arrow-up-16.png","","");
		$down=imgsimple("arrow-down-18.png","","");
		$export=imgsimple("24-export.png","","Loadjs('squid.acls.export.php?single-id={$ligne['ID']}')");
		
		$link=$boot->trswitch("Loadjs('squid.acls-rules.php?Addacl-js=yes&ID={$ligne['ID']}&t={$_GET["t"]}');");
		$tr[]="
		<tr id='acl{$ligne['ID']}'>
		<td $link nowrap>$gliff <span style='color:$color'>{$ligne['aclname']}</span></td>
		<td $link><i class='icon-info-sign'></i> <span style='color:$color'> $explain</td>
		<td width=1% ". $boot->trswitch("AclUpDown('{$ligne['ID']}',1)").">$up</td>
		<td width=1% ". $boot->trswitch("AclUpDown('{$ligne['ID']}',0)").">$down</td>
		<td width=1% align='center' style='text-align:center'>$disable</td>
		<td width=1% align='center' style='text-align:center'>$export</td>
		<td width=1% align='center' style='text-align:center'>$delete</td>
		</tr>";		
		

	}
	echo $tpl->_ENGINE_parse_body("
	$error_explain
		<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th>{rule}</th>
					<th>{description}</th>
					<th colspan=2>{order}</th>
					<th>&nbsp;</th>
					<th>&nbsp;</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>
				</table>
<script>
var DeleteSquidAclGroupTemp='';

	var x_EnableDisableAclRule$t= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		ExecuteByClassName('SearchFunction');
	}
	
	var x_DeleteSquidAclRule$t= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#acl'+DeleteSquidAclGroupTemp).remove();
	}	
	
	
	function DeleteSquidAclRule(ID){
		DeleteSquidAclGroupTemp=ID;
		if(confirm('$delete_rule_ask :'+ID)){
			var XHR = new XHRConnection();
			XHR.appendData('acl-rule-delete', ID);
			XHR.sendAndLoad('squid.acls-rules.php', 'POST',x_DeleteSquidAclRule$t);
		}  		
	}

	function AclUpDown(ID,dir){
			var XHR = new XHRConnection();
			XHR.appendData('acl-rule-move', ID);
			XHR.appendData('acl-rule-dir', dir);
			XHR.sendAndLoad('squid.acls-rules.php', 'POST',x_EnableDisableAclRule$t);  	
		}	

	function EnableDisableAclRule$t(ID){
		var XHR = new XHRConnection();
		XHR.appendData('acl-rule-enable', ID);
		if(document.getElementById('aclid_'+ID).checked){XHR.appendData('enable', '1');}else{XHR.appendData('enable', '0');}
		XHR.sendAndLoad('squid.acls-rules.php', 'POST',x_EnableDisableAclRule$t);  		
	}	

</script>";
}
