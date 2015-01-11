<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.iptables-chains.inc');
	
	
	$usersmenus=new usersMenus();
	if($usersmenus->AsSystemAdministrator==false){exit();}	
	
	
	if(isset($_GET["add-range"])){firewall_range_form();exit;}
	if(isset($_GET["iptables_rules"])){firewall_rules();exit();}
	if(isset($_GET["edit_rule"])){firewall_rule_form();exit;}
	
	if(isset($_POST["source_address"])){firewall_rule_save();exit;}
	if(isset($_POST["sources_addresses"])){firewall_rule_save_multiples();exit;}
	
	
	if(isset($_POST["DeleteIptableRule"])){firewall_rule_delete();exit;}
	if(isset($_POST["EnableFwRule"])){firewall_rule_enable();exit;}
	if(isset($_POST["EnableLog"])){firewall_rule_log();exit;}
	if(isset($_GET["add-multiple-rules"])){firewall_multiple_form();exit;}
	
	if(isset($_POST["range-from"])){firewall_range_save();exit;}
	if(isset($_POST["EmptyAll"])){firewall_empty();exit;}
	if(isset($_GET["options"])){options_js();exit;}
	if(isset($_GET["options-popup"])){options_popup();exit;}
	if(isset($_POST["EnableIptablesDNS"])){options_save();exit;}
	
	firewall_popup();
	
	
function options_js(){
header("content-type: application/x-javascript");
$page=CurrentPageName();
$tpl=new templates();
$title=$tpl->_ENGINE_parse_body("{options}");
echo "YahooWin5(600,'$page?options-popup=yes&table={$_GET["table"]}','$title',true)";
}	

function options_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$t=time();
	$EnableIptablesDNS=$sock->GET_INFO("EnableIptablesDNS");
	$EnableSpamhausDROPList=$sock->GET_INFO("EnableSpamhausDROPList");
	if(!is_numeric($EnableIptablesDNS)){$EnableIptablesDNS=1;}
	if(!is_numeric($EnableSpamhausDROPList)){$EnableSpamhausDROPList=0;}
	
	
	$html="
	<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:16px'>{enable_iptablesdns}</td>
			<td>". Field_checkbox("EnableIptablesDNS", 1,$EnableIptablesDNS)."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:16px'>{EnableSpamhausDROPList}</td>
			<td>". Field_checkbox("EnableSpamhausDROPList", 1,$EnableSpamhausDROPList)."</td>
		</tr>					
		<tr>
			<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",18)."</td>
		</tr>
	</table>
	</div>	
	
	
<script>
	var xSave$t= function (obj) {
		var tempvalue=obj.responseText;
		UnlockPage();
		if(tempvalue.length>3){alert(tempvalue)};
		YahooWin5Hide();
		$('#{$_GET["table"]}').flexReload();
		
	}		

	function Save$t(){
		var XHR = new XHRConnection();
		if(document.getElementById('EnableIptablesDNS').checked){XHR.appendData('EnableIptablesDNS',1);}else{XHR.appendData('EnableIptablesDNS',0);}
		if(document.getElementById('EnableSpamhausDROPList').checked){XHR.appendData('EnableSpamhausDROPList',1);}else{XHR.appendData('EnableSpamhausDROPList',0);}
		LockPage();
		XHR.sendAndLoad('$page', 'POST',xSave$t);		
		}
</script>								
			
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function options_save(){
	$sock=new sockets();
	$sock->SET_INFO("EnableIptablesDNS", $_POST["EnableIptablesDNS"]);
	$sock->SET_INFO("EnableSpamhausDROPList", $_POST["EnableSpamhausDROPList"]);
	if($_POST["EnableSpamhausDROPList"]==1){
		$q=new mysql();
		$q->QUERY_SQL("DELETE FROM iptables WHERE `service`='SpamHaus' AND `allow`=0","artica_backup");
	}
	$sock->getFrameWork("network.php?fw-inbound-rules=yes");
	
	if($_POST["EnableSpamhausDROPList"]==1){
		$sock->getFrameWork("network.php?fw-spamhaus-rules=yes");
	}
	
}
	
	
function firewall_popup(){
	unset($_SESSION["postfix_firewall_rules"]);
	$users=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();
	$rule=$tpl->_ENGINE_parse_body("{rule}");
	if(!$users->AsSystemAdministrator){
		$error=$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
		echo "<H3>$error<H3>";
		die();
	}	
	
	$q=new mysql();
	$results=$q->QUERY_SQL("SELECT service FROM iptables GROUP BY service ORDER BY service","artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		if(trim($ligne["service"])==null){continue;}
		$searchitem[]="{display: '{$ligne["service"]}', name : 'service={$ligne["service"]}'},";
	}
	
	$t=time();
	$server=$tpl->_ENGINE_parse_body("{server}");
	$port=$tpl->_ENGINE_parse_body("{port}");
	$enable=$tpl->_ENGINE_parse_body("{enable}");
	$log=$tpl->_ENGINE_parse_body("{LOG}");
	$saved_date=$tpl->_ENGINE_parse_body("{zDate}");
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$empty=$tpl->_ENGINE_parse_body("{empty}");
	$allow_rules=$tpl->_ENGINE_parse_body("{allow_rules}");
	$banned_rules=$tpl->_ENGINE_parse_body("{banned_rules}");
	$empty_all_firewall_rules=$tpl->javascript_parse_text("{empty_all_firewall_rules}");
	$block_countries=$tpl->_ENGINE_parse_body("{block_countries}");
	$current_rules=$tpl->_ENGINE_parse_body("{current_rules}");
	$options=$tpl->_ENGINE_parse_body("{options}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$ERROR_IPSET_NOT_INSTALLED=$tpl->javascript_parse_text("{ERROR_IPSET_NOT_INSTALLED}");
	$IPSET_INSTALLED=0;
	if($users->IPSET_INSTALLED){$IPSET_INSTALLED=1;}
	
	$TB_HEIGHT=450;
	$TABLE_WIDTH=920;
	$TB2_WIDTH=400;
	$ROW1_WIDTH=629;
	$ROW2_WIDTH=163;
	
	$t=time();
	
	$buttons="
	buttons : [
	{name: '$empty', bclass: 'Delz', onpress : EmptyRules},
	{name: '$new_rule', bclass: 'Add', onpress : NewIptableRule},
	{name: '$allow_rules', bclass: 'Search', onpress : AllowRules},
	{name: '$banned_rules', bclass: 'Search', onpress : BannedRules},
	{name: '$block_countries', bclass: 'Catz', onpress : block_countries},
	{name: '$current_rules', bclass: 'Search', onpress : current_rules},
	{name: '$options', bclass: 'Settings', onpress : options$t},
	
	
		],	";
	$html="
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
var IptableRow='';
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?iptables_rules=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'delete', width :45, sortable : false, align: 'center'},
		{display: '$server', name : 'servername', width :588, sortable : false, align: 'left'},
		{display: '$enable', name : 'disable', width :75, sortable : true, align: 'center'},
		{display: 'Log', name : 'log', width :75, sortable : true, align: 'center'},
		{display: 'Del', name : 'delete', width :75, sortable : false, align: 'center'},
		
	],
	$buttons
	
	searchitems : [
		{display: '$server/$ipaddr', name : 'servername'},
		
		
		{display: '$port', name : 'port'},
		{display: '$saved_date', name : 'saved_date'},".@implode("\n",$searchitem)."	
		],	
	
	sortname: 'saved_date',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: $TB_HEIGHT,
	singleSelect: true
	
	});   
});

	function block_countries(){
		var IPSET_INSTALLED=$IPSET_INSTALLED;
		if(IPSET_INSTALLED==0){alert('$ERROR_IPSET_NOT_INSTALLED');return;}
		Loadjs('system.ipblock.php')
	}
	
	function current_rules(){
		Loadjs('system.iptables.save.php');
	}

	var x_EmptyRules= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
		IpTablesInboundRuleResfresh();
	}	

	function EmptyRules(){
		if(confirm('$empty_all_firewall_rules ?')){
			var XHR = new XHRConnection();
			XHR.appendData('EmptyAll','yes');
			XHR.sendAndLoad('$page', 'POST',x_EmptyRules);
		}
	}

	function NewIptableRule(){
		iptables_edit_rules('');
	}

	function IpTablesInboundRuleResfresh(){
		$('#table-$t').flexReload();
	}
	
	function AllowRules(){
		$('#table-$t').flexOptions({ url: '$page?iptables_rules=yes&t=$t&allow=1' }).flexReload();
	}
	function BannedRules(){
		$('#table-$t').flexOptions({ url: '$page?iptables_rules=yes&t=$t&allow=0' }).flexReload();
	}	
	
	var x_IptableDelete= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
		$('#row'+IptableRow).remove();	
		
	}	
	
	function options$t(){
		Loadjs('$page?options=yes&table=table-$t',true);
	}
	
	function IptableDelete(key){
		IptableRow=key;
		var XHR = new XHRConnection();
		XHR.appendData('DeleteIptableRule',key);
		XHR.sendAndLoad('$page', 'POST',x_IptableDelete);
		}
		
	var x_FirewallDisableRUle= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
	}

	function iptables_edit_rules(num){
		YahooWin5('800','$page?edit_rule=yes&t=$t&rulemd5='+num,'$rule');
	
	}	
		

	function FirewallDisableRUle(ID){
		var XHR = new XHRConnection();
		XHR.appendData('ID',ID);
		if(document.getElementById('enabled_'+ID).checked){XHR.appendData('EnableFwRule',0);}else{XHR.appendData('EnableFwRule',1);}
		XHR.sendAndLoad('$page', 'POST',x_FirewallDisableRUle);
	}

	function EnableLog(ID){
		var XHR = new XHRConnection();
		XHR.appendData('ID',ID);
		if(document.getElementById('enabled_'+ID).checked){XHR.appendData('EnableLog',1);}else{XHR.appendData('EnableLog',0);}
		XHR.sendAndLoad('$page', 'POST',x_FirewallDisableRUle);	
	
	}	
	
</script>";
	
echo $html;
}	

function firewall_rules(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$database="artica_backup";
	$search='%';
	$table="iptables";
	$page=1;
	$ORDER=null;
	$FORCE="flux='INPUT'";
	$allow=null;
	
	$total=0;
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("no data");;}
	
	if($_POST["query"]<>null){
		$search="%{$_POST["query"]}%";
		$search=str_replace("*", "%", $search);
		$search=str_replace("%%", "%", $search);
		$search=str_replace("%%", "%", $search);
	}else{
		$search="%";
	}
	if(is_numeric($_GET["allow"])){$allow="AND allow={$_GET["allow"]}";}
	
	
	if(preg_match("#service=(.+)#", $_POST["qtype"],$re)){
		$servcie="AND `service`='{$re[1]}'";
		
	}
	$FORCE="(flux='INPUT' $allow $servcie  AND `servername` LIKE '$search') OR (flux='INPUT' $allow $servcie AND `serverip` LIKE '$search')";
	
	
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	
	
	
	$table="(SELECT * FROM iptables WHERE ($FORCE)) as t";
	$sql="SELECT COUNT(*) as TCOUNT FROM $table";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
	$total = $ligne["TCOUNT"];
		
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT * FROM $table $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(mysql_num_rows($results)==0){json_error_show("no data $sql");}
		
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);}	
	$updated_from_community=$tpl->_ENGINE_parse_body("{updated_from_community}");
	$ports=$tpl->_ENGINE_parse_body("{ports}");
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$mouse="OnMouseOver=\"this.style.cursor='pointer'\" OnMouseOut=\"this.style.cursor='default'\"";
		$linkstyle="style='text-decoration:underline'";
		$id=$ligne["rulemd5"];
		if($ligne["servername"]==null){$ligne["servername"]=$ligne["serverip"];}
		$link="iptables_edit_rules('{$ligne["rulemd5"]}');";
		$disable=Field_checkbox("enabled_{$ligne["ID"]}",0,$ligne["disable"],"FirewallDisableRUle('{$ligne["ID"]}')");
		$log=Field_checkbox("log_{$ligne["ID"]}",1,$ligne["log"],"EnableLog('{$ligne["ID"]}')");
		$delete=imgtootltip("delete-32.png","{delete}","IptableDelete('{$ligne["rulemd5"]}')");
		$tooltip_add=null;
		if($ligne["events_block"]<>null){$ligne["events_block"]="<div style=font-size:12px> ".nl2br($ligne["events_block"])."</div>";}
		$icon="32-black-computer.png";
		if($ligne["community"]==1){
			$icon="32-black-computer-grey.png";
			$delete="<img src='img/delete-32-grey.png'>";
			$tooltip_add="<strong style=font-size:12px> $updated_from_community</strong><br>";
			$link="blur();";
			$mouse=null;
			$linkstyle=null;
		}
		
		$css2="style='border:0px;padding:0px;margin:0px;background-color:transparent;border-bottom:0px;vertical-align:middle' $mouse";
		
		if($ligne["service"]==null){$icon="32-black-computer.png";$link=null;}
		
		$subtext=$tpl->_ENGINE_parse_body("<div><i><span style='color:#660002;font-weight:bold'>{$ligne["serverip"]}</span> {added_on} {$ligne["saved_date"]}</i></div>");
		$port=$ligne["local_port"];
		if($ligne["multiples_ports"]<>null){$port=$ligne["multiples_ports"];}
		if($port==0){$port="{all}";}
		if(preg_match("#Range:(.+)#", $ligne["serverip"],$re)){
			$icon="datasource-32-grey.png";
			$link=null;
			$ligne["serverip"]=$re[1];
			$ligne["servername"]=$re[1];
		}
		
		$textAllow=$tpl->_ENGINE_parse_body("<span style='color:#660002;'>{deny}:</span>");
		if($ligne["allow"]==1){
			$icon="32-white-computer.png";
			$textAllow=$tpl->_ENGINE_parse_body("<span style='color:#4DA14C'>{allow}:</span>");
		}
		
		$port=$tpl->_ENGINE_parse_body($port);
		

		$final="<div $mouse OnClick=\"javascript:$link\"><strong style='font-size:14px'><code $linkstyle>$textAllow{$ligne["servername"]}</code></strong></div>$subtext<div><code>$ports:$port</code></div>{$ligne["events_block"]}$tooltip_add</div>";
		
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array(
			"<img src='img/$icon' valign='middle'>",
			"<span style='font-size:12px'>$final</span>",
			"<div style='font-size:13px;padding-top:10px'>$disable</span>",
			"<div style='font-size:13px;padding-top:10px'>$log</span>",
			"<span style='font-size:13px'>$delete</span>",
		 
	
		)
		);
	}
	
	
echo json_encode($data);		

}


	



function firewall_rule_form_tabs(){
$tpl=new templates();
	$page=CurrentPageName();
	$array["edit_rule"]='{single_entry}';
	$array["edit_rules"]='{multiple_entries}';
	$array["range"]='{range}';
	$t=$_GET["t"];
	
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="edit_rule"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?edit_rule=yes&t=$t&rulemd5={$_GET["rulemd5"]}&tabs=yes\"><span style='font-size:18px'>$ligne</span></a></li>\n");
			continue;
		}
	
	if($num=="edit_rules"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?add-multiple-rules=yes&t=$t\"><span style='font-size:18px'>$ligne</span></a></li>\n");
			continue;
		}
		
	if($num=="range"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?add-range=yes&t=$t\"><span style='font-size:18px'>$ligne</span></a></li>\n");
			continue;
		}		

	}
	
	echo build_artica_tabs($html, "firewall-in-tabs");
	
}

function firewall_range_form(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$t=$_GET["t"];
	$html="
	<div id='div$t' style='width:98%' class=form >
	<table style='width:99%'>
	<tr>
		<td class=legend style='font-size:22px'>{from}:</td>
		<td>". field_ipv4("range-from",null,"font-size:22px;padding:3px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{top}:</td>
		<td>". field_ipv4("range-to",null,"font-size:22px;padding:3px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{allow}:</td>
		<td>". Field_checkbox("allow-1-$t",1,0)."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{multiples_ports}:</td>
		<td>". Field_text("multiples_ports-$t",$ligne["multiples_ports"],"font-size:22px;padding:3px",null,null,null,false,"SaveIptableRangeRuleCheck(event)")."</td>
		<td>". help_icon("{fw_multiples_ports_explain}")."</td>
	</tr>
		
	<tr>
		<td colspan=3 align='right'><hr>". button("{add}","SaveIptableRangeRule()",26)."</td>
	</tr>
	</table>
	</div>
	<script>
	
	var x_SaveIptableRangeRule= function (obj) {
		var tempvalue=obj.responseText;
		UnlockPage();
		if(tempvalue.length>3){alert(tempvalue)};
		IpTablesInboundRuleResfresh();
		YahooWin5Hide();
		
		
		
	}		

	function SaveIptableRangeRuleCheck(e){
		if(checkEnter(e)){SaveIptableRangeRule();}
	}
	
		
	function SaveIptableRangeRule(){
	
		var XHR = new XHRConnection();
		XHR.appendData('range-from',document.getElementById('range-from').value);
		XHR.appendData('range-to',document.getElementById('range-to').value);
		XHR.appendData('multiples_ports',document.getElementById('multiples_ports-$t').value);
		if(document.getElementById('allow-1-$t').checked){XHR.appendData('allow',1);}else{XHR.appendData('allow',0);}
		AnimateDiv('div$t');
		LockPage();
		XHR.sendAndLoad('$page', 'POST',x_SaveIptableRangeRule);		
		}
		
	</script>
	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}


function firewall_rule_form(){
	
	if($_GET["rulemd5"]==0){
		if(!isset($_GET["tabs"])){
			firewall_rule_form_tabs();return;
		}
	}
	
	$q=new mysql();
	$tpl=new templates();
	$page=CurrentPageName();	
	$rulemd5=$_GET["rulemd5"];
	$button="{apply}";
	$sql="SELECT * FROM iptables WHERE rulemd5='$rulemd5'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(strlen($rulemd5)<5){$button="{add}";}
	$t=$_GET["t"];
	$html="
	<div id='div$t' style='width:98%' class=form>
	<table  style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{source_address}:</td>
		<td>". field_ipv4("serverip",$ligne["serverip"],"font-size:22px;padding:3px")."</td>
		<td>". help_icon("{fw_sourceaddr_explain}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{multiples_ports}:</td>
		<td>". Field_text("multiples_ports",$ligne["multiples_ports"],"font-size:22px;width:350px;padding:3px",null,null,null,false,"SaveIptableRuleCheck(event)")."</td>
		<td>". help_icon("{fw_multiples_ports_explain}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{allow}:</td>
		<td>". Field_checkbox("allow-2-$t",1,$ligne["allow"])."</td>
		<td>&nbsp;</td>
	</tr>
		
	<tr>
		<td colspan=3 align='right'><hr>". button("$button","SaveIptableRule()",28)."</td>
	</tr>
	</table>
	<div>
	<script>
	
	var x_SaveIptableRule= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		IpTablesInboundRuleResfresh();
		YahooWin5Hide();
		
	}		

	function SaveIptableRuleCheck(e){
		if(checkEnter(e)){SaveIptableRule();}
	}
	
		
	function SaveIptableRule(){
		var XHR = new XHRConnection();
		XHR.appendData('source_address',document.getElementById('serverip').value);
		XHR.appendData('multiples_ports',document.getElementById('multiples_ports').value);
		if(document.getElementById('allow-2-$t').checked){XHR.appendData('allow',1);}else{XHR.appendData('allow',0);}
		XHR.appendData('rulemd5','$rulemd5');
		AnimateDiv('div$t');
		XHR.sendAndLoad('$page', 'POST',x_SaveIptableRule);		
		}
		
	</script>
	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function firewall_multiple_form(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=$_GET["t"];
	$html="
	<div id='div$t'>
	<div style='font-size:13px' class=text-info>{firewall_multiple_form_add_text}</div>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend>{allow}:". Field_checkbox("allow-3-$t",1,0)."</td>
	</tr>	
		
	<tr>
		<td><textarea style='width:100%;height:250px;overflow:auto;border:1px solid #CCCCCC;font-size:14px' id='multiple-sources-fw'></textarea></td>
	</tr>
	<tr>
		<td style='font-size:14px' align=center>{ports}:". Field_text("multiple-ports-fw",null,"font-size:14px;width:220px")."</td>
	</tr>	

	<tr>
		<td align=right><hr>". button("{add}","AddMultiplesINFW()",16)."</td>
	</tR>
	</tbody>
	</table>
	</div>
	
	<script>
	
	var x_AddMultiplesINFW= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		IpTablesInboundRuleResfresh();
		YahooWin5Hide();
	}		

	function AddMultiplesINFW(){
		var XHR = new XHRConnection();
		var ports=document.getElementById('multiple-ports-fw').value;
		var ips=document.getElementById('multiple-sources-fw').value;
		if(ports.length==0){return;}
		if(ips.length==0){return;}
		XHR.appendData('sources_addresses',document.getElementById('multiple-sources-fw').value);
		XHR.appendData('multiple-ports-fw',document.getElementById('multiple-ports-fw').value);
		if(document.getElementById('allow-3-$t').checked){XHR.appendData('allow',1);}else{XHR.appendData('allow',0);}
		AnimateDiv('div$t');
		XHR.sendAndLoad('$page', 'POST',x_AddMultiplesINFW);		
		}
		
	</script>	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function firewall_rule_save(){
	$tpl=new templates();
	$iptables=new iptables_chains();
	$iptables->localport=$_POST["multiples_ports"];
	$iptables->serverip=$_POST["source_address"];
	$iptables->rulemd5=$_POST["rulemd5"];
	$iptables->allow=$_POST["allow"];
	if(!$iptables->add_chain()){echo $tpl->javascript_parse_text("\n{failed}\n");return;}
	$sock=new sockets();
	$sock->getFrameWork("network.php?fw-inbound-rules=yes");	
	
}

function firewall_rule_save_multiples(){
	$tpl=new templates();
	
	$tb=explode("\n", $_POST["sources_addresses"]);
	writelogs("sources_addresses: ".count($tb)." entries",__FUNCTION__,__FILE__,__LINE__);
	
	while (list ($num, $ipaddr) = each ($tb) ){
		$iptables=new iptables_chains();
		$iptables->localport=$_POST["multiple-ports-fw"];
		$iptables->serverip=trim($ipaddr);
		$iptables->rulemd5=null;
		$iptables->allow=$_POST["allow"];
		writelogs("Adding $ipaddr {$_POST["multiple-ports-fw"]}",__FUNCTION__,__FILE__,__LINE__);
		if(!$iptables->add_chain()){echo $tpl->javascript_parse_text("\n{failed}\n");return;}		
		
	}
	$sock=new sockets();
	$sock->getFrameWork("network.php?fw-inbound-rules=yes");	
	
	
}

function firewall_range_save(){
	$iptables=new iptables_chains();
	$iptables->localport=$_POST["multiples_ports"];
	$iptables->serverip="Range:{$_POST["range-from"]}-{$_POST["range-to"]}";
	$iptables->rulemd5=null;
	$iptables->allow=$_POST["allow"];
	writelogs("Adding $ipaddr {$_POST["multiple-ports-fw"]}",__FUNCTION__,__FILE__,__LINE__);
	if(!$iptables->add_chain()){echo $tpl->javascript_parse_text("\n{failed}\n");return;}		
		
	
	$sock=new sockets();
	$sock->getFrameWork("network.php?fw-inbound-rules=yes");	
}


function firewall_rule_delete(){
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM iptables WHERE rulemd5='{$_POST["DeleteIptableRule"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("network.php?fw-inbound-rules=yes");	
	
}

function firewall_empty(){
	$q=new mysql();
	$q->QUERY_SQL("TRUNCATE TABLE iptables","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("network.php?fw-inbound-rules=yes");		
}





function firewall_rule_enable(){
	$q=new mysql();
	$sql="UPDATE iptables SET disable='{$_POST["EnableFwRule"]}' WHERE ID='{$_POST["ID"]}'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
	$sock=new sockets();
	$sock->getFrameWork("network.php?fw-inbound-rules=yes");	
}
function firewall_rule_log(){
	$q=new mysql();
	
	$sql="SELECT log FROM iptables WHERE ID='{$_POST["ID"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
	if($ligne["log"]==1){$_POST["EnableLog"]=0;}else{$_POST["EnableLog"]=1;}
	$sql="UPDATE iptables SET log='{$_POST["EnableLog"]}' WHERE ID='{$_POST["ID"]}'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("network.php?fw-inbound-rules=yes");	
}