<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
session_start();
include_once("ressources/class.templates.inc");
include_once("ressources/class.ldap.inc");
include_once('ressources/class.mysql.builder.inc');

$users=new usersMenus();
if(!$users->AsPostfixAdministrator){die();}
if(isset($_GET["tls-js"])){tls_server_js();exit;}
if(isset($_GET["tls-popup"])){tls_server_popup();exit;}
if(isset($_GET["list"])){tls_server_list();exit;}


smtp_tls_policy_maps_table();

function smtp_tls_policy_maps_table(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$q=new mysql();
	if(!$q->TABLE_EXISTS("smtp_tls_policy_maps", "artica_backup")){
		$q=new mysql_builder();
		if(!$q->CheckTablePostfixTls()){
			echo FATAL_ERROR_SHOW_128($q->mysql_error_html());
			return;
		}
	}
	$t=time();
	$hostname=$_GET["hostname"];
	$hostname_enc=urlencode($hostname);
	$title=$tpl->javascript_parse_text("{tls_table_explain}");
	$about=$tpl->javascript_parse_text("{about2}");
	$about_text=$tpl->javascript_parse_text("{tls_table_explain}");
	$add_tls_smtp_server=$tpl->javascript_parse_text("{add_tls_smtp_server}");
	$servername=$tpl->javascript_parse_text("{servername2}");
	$option=$tpl->javascript_parse_text("{option}");
	$delete=$tpl->javascript_parse_text("{delete}");
	
	$add="{name: '$add_tls_smtp_server', bclass: 'add', onpress : add_tls_smtp_server$t},";
	
	$aboutButton="{name: '$about', bclass: 'Help', onpress : About$t},";
	$buttons="
		buttons : [
		$add
		$aboutButton
		],";
	
		$explain=$tpl->javascript_parse_text("{postfix_transport_table_explain}");
	
$html="
<table class='POSTFIX_TLS_TABLE' style='display: none' id='POSTFIX_TLS_TABLE' style='width:100%'></table>
<script>
$(document).ready(function(){
		$('#POSTFIX_TLS_TABLE').flexigrid({
		url: '$page?list=yes&hostname=$hostname_enc&t=$t',
		dataType: 'json',
		colModel : [
		{display: '$servername', name : 'servername', width : 546, sortable : true, align: 'left'},
		{display: '$option', name : 'tls_option', width :309, sortable : true, align: 'left'},
		{display: '$delete', name : 'delete', width : 77, sortable : false, align: 'center'},
		],
		$buttons
		searchitems : [
		{display: '$servername', name : 'servername'},
		],
		sortname: 'servername',
		sortorder: 'asc',
		usepager: true,
		title: '<span style=font-size:18px>$title</span>',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: '99%',
		height: 450,
		singleSelect: true,
		rpOptions: [10, 20, 30, 50,100,200]
	
	});
});
	
function About$t(){
	alert('$about_text');
}
	
function add_tls_smtp_server$t(){
	Loadjs('$page?tls-js=yes&ID=0&t=$t&hostname=$hostname_enc');
}
	
</script>
	";
	
	echo $html;
}

function tls_server_js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$ID=$_GET["ID"];
	$hostname=$_GET["hostname"];
	$hostname_enc=urlencode($hostname);
	$title=$tpl->javascript_parse_text("{add_tls_smtp_server}");
	if($ID>0){
		
		
	}
	echo "YahooWin2('850','$page?tls-popup=yes&ID=$ID&hostname=$hostname_enc','$title');";
	
	
}

function tls_server_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	$bttext="{add}";
	$title=$tpl->_ENGINE_parse_body("{add_tls_smtp_server}");
	$q=new mysql();
	$t=time();
	if($ID>0){
	
	
	}
	

	

	$options["none"]="{none}";
	$options["may"]="{tls_may}";
	$options["encrypt"]="{Mandatory_TLS_encryption}";
	$options["dane"]="DANE";
	$options["dane-only"]="DANE Only";
	$options["fingerprint"]="{fingerprint}";
	$options["verify"]="{fingerprint} {verify}";
	$options["secure"]="{Secure_channel_TLS}";
	
if($ligne["tls_option"]==null){$ligne["tls_option"]="may";}
if($ligne["protocols"]==null){$ligne["protocols"]="ALL:!SSLv2:-RC4:RC4-SHA:RC4:+SEED:!IDEA:!3DES:!MD5:!aDSS:!aDH:!PSK:!SRP:@STRENGTH";}
	
	$html="<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:18px'>{servername2}:</td>
			<td>". Field_text("servername-$t",$ligne["servername"],"font-size:18px;width:90%")."</td>		
		</tr>	
		<tr>
			<td class=legend style='font-size:18px'>{port}:</td>
			<td>". Field_text("port-$t",$ligne["port"],"font-size:18px;width:110px")."</td>		
		</tr>
		<tr>
			<td class=legend style='font-size:18px'>{MX_lookups}:</td>
			<td>". Field_checkbox("MX_lookups-$t",1,$ligne["MX_lookups"])."</td>		
		</tr>	
		<tr>
			<td class=legend style='font-size:18px'>{tls_security_level}:</td>
			<td>". Field_array_Hash($options,"tls_option-$t", $ligne["tls_option"],"style:font-size:18px")."</td>		
		</tr>
		<tr>
			<td class=legend style='font-size:18px'>{protocols}:</td>
			<td>". Field_text("protocols-$t",$ligne["protocols"],"font-size:18px;width:90%")."</td>		
		</tr>	
		<tr>
			<td class=legend style='font-size:18px'>{ssl_ciphers}:</td>
			<td>". Field_text("ciphers-$t",$ligne["ciphers"],"font-size:18px;width:90%")."</td>		
		</tr>	
		<tr>
			<td class=legend style='font-size:18px'>{ssl_ciphers} (match):</td>
			<td>". Field_text("tls_match-$t",$ligne["ssl_ciphers"],"font-size:18px;width:90%")."</td>		
		</tr>
		<tr>
			<td class=legend style='font-size:18px'>{fingerprint}:</td>
			<td>". field_textarea("fingerprint-$t",$ligne["fingerprint"],"18","100%","150")."</td>		
		</tr>					
		
		<tr>
			<td colspan=2 align='right'><hr>". button($bttext,"Save$t()",26)."</td>
		</tr>
	</table>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#POSTFIX_TLS_TABLE').flexReload();
	var ID={$_GET["ID"]}
	if(ID==0){ YahooWin2Hide();}
	UnlockPage();
	
}
function Save$t(){
	LockPage();
	var XHR = new XHRConnection();
	XHR.appendData('servername',document.getElementById('servername-$t').value);
	XHR.appendData('port',document.getElementById('port-$t').value);
	if(document.getElementById('servername-$t').checked){ XHR.appendData('MX_lookups',1); }else{ XHR.appendData('MX_lookups',0); }
	XHR.appendData('tls_option',document.getElementById('tls_option-$t').value);
	XHR.appendData('protocols',document.getElementById('protocols-$t').value);
	XHR.appendData('ciphers',document.getElementById('ciphers-$t').value);
	XHR.appendData('tls_match',document.getElementById('tls_match-$t').value);
	XHR.appendData('fingerprint',document.getElementById('fingerprint-$t').value);
	XHR.appendData('hostname','{$_POST["hostname"]}');
	XHR.appendData('ID','{$_GET["ID"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>					
";
	
echo $tpl->_ENGINE_parse_body($html);	
	
}

function tls_server_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$t=$_GET["t"];
	$search='%';
	$table="smtp_tls_policy_maps";
	$page=1;
	$FORCE_FILTER=" AND hostname='{$_GET["hostname"]}'";
	$total=0;
	
	if(!$q->TABLE_EXISTS("smtp_tls_policy_maps", "artica_backup")){
		json_error_show("$table no such table");
	}
	

	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql, "artica_backup"));
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql, "artica_backup"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}
	
	
	$pageStart = ($page-1)*$rp;
	if(is_numeric($rp)){$limitSql = "LIMIT $pageStart, $rp";}
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql, "artica_backup");
	
	$no_rule=$tpl->_ENGINE_parse_body("{no data}");
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){	json_error_show($q->mysql_error."<br>$sql");}
	if(mysql_num_rows($results)==0){json_error_show("no data");}
	
	
	
	$options["none"]="{none}";
	$options["may"]="{tls_may}";
	$options["encrypt"]="{Mandatory_TLS_encryption}";
	$options["dane"]="DANE";
	$options["dane-only"]="DANE Only";
	$options["fingerprint"]="{fingerprint}";
	$options["verify"]="{fingerprint} {verify}";
	$options["secure"]="{Secure_channel_TLS}";
	
	$fontsize=22;
	while ($ligne = mysql_fetch_assoc($results)) {
	
		$delete=imgtootltip('delete-24.png','{delete}',"DnsDelete$t('{$ligne["dnsserver"]}')");
		$up=imgsimple("arrow-up-32.png","","SquidDNSUpDown('{$ligne['ID']}',1)");
		$down=imgsimple("arrow-down-32.png","","SquidDNSUpDown('{$ligne['ID']}',0)");
		$servername=$ligne["servername"];
		$port=$ligne["port"];
		$tls_option=$ligne["tls_option"];
	
		$data['rows'][] = array(
				'id' => "squid-dns-{$ligne["ID"]}",
				'cell' => array(
						"<span style='font-size:{$fontsize}px'>$servername:$port</span>",
						"<span style='font-size:{$fontsize}px'>{$options[$tls_option]}</span>",
						"<span style='font-size:12.5px'>$delete</span>",
				)
		);
	
	}
	
	echo json_encode($data);
	}




