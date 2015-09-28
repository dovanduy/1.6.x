<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.maincf.multi.inc');
	
	
$users=new usersMenus();
if(!PostFixVerifyRights()){
	$tpl=new templates();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	echo "alert('$ERROR_NO_PRIVS');";
	die();
	
}


	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["smtp"])){smtp();exit;}
	
	if(isset($_GET["smtp-instance-delete-js"])){smtp_instance_delete_js();exit;}
	if(isset($_GET["smtp-instance-tab-js"])){smtp_instance_tab_js();exit;}
	if(isset($_GET["smtp-instance-tab"])){smtp_instance_tab();exit;}
	if(isset($_GET["smtp-instance-list"])){smtp_instance_list();exit;}
	if(isset($_GET["smtp-instance-search"])){smtp_instance_search();exit;}
	if(isset($_GET["smtp-instance-add"])){smtp_instance_add();exit;}
	if(isset($_GET["smtp-instance-delete"])){smtp_instance_delete();exit;}
	if(isset($_GET["smtp-instance-edit"])){smtp_instance_edit();exit;}
	if(isset($_POST["smtp-instance-save"])){smtp_instance_save();exit;}
	
	if(isset($_GET["smtp-instance-cache-destinations"])){smtp_instance_cache_destinations();exit;}
	if(isset($_GET["smtp-instance-cache-destinations-list"])){smtp_instance_cache_destinations_list();exit;}
	if(isset($_GET["smtp-instance-cache-destinations-add"])){smtp_instance_cache_destinations_add();exit;}
	if(isset($_POST["smtp-instance-cache-destinations-new"])){smtp_instance_cache_destinations_save();exit;}
	if(isset($_POST["smtp-instance-cache-destinations-delete"])){smtp_instance_cache_destinations_del();exit;}
	
	
	
	
	if(isset($_GET["domains"])){domains_popup();exit;}
	if(isset($_GET["domain-popup-add-js"])){domains_popup_add_js();exit;}
	if(isset($_GET["domain-popup-add"])){domains_popup_add();exit;}
	if(isset($_POST["domains-add"])){domains_add();exit;}
	if(isset($_GET["domains-list"])){domains_list();exit;}
	if(isset($_GET["domain-delete"])){domains_delete();exit;}
	if(isset($_GET["domain-delete-js"])){domains_delete_js();exit;}

	js();



function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title="{domain_throttle}::{$_GET["hostname"]}/{$_GET["ou"]}";
	$title=$tpl->_ENGINE_parse_body($title);
	echo "YahooWin3(990,'$page?popup=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}','$title');";
	}
	
function smtp_instance_tab_js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	echo "YahooWin5(850,'$page?smtp-instance-tab={$_GET["smtp-instance-tab-js"]}&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}&t={$_GET["t"]}','{$_GET["title"]}')";
}
function smtp_instance_delete_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	header("content-type: application/x-javascript");	
	
	echo "
var x_DeleteSMTPSenderInstance$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue)};
	$('#flexRT{$_GET["t"]}').flexReload();
}	
		
function DeleteSMTPSenderInstance$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ou','{$_GET["ou"]}');
	XHR.appendData('hostname','{$_GET["hostname"]}');
	XHR.appendData('smtp-instance-delete','{$_GET["uuid"]}');
	XHR.sendAndLoad(\"$page\", 'GET',x_DeleteSMTPSenderInstance$t);
}	
	
DeleteSMTPSenderInstance$t();";
	
}
function domains_popup_add_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text('{new_domain}');
	header("content-type: application/x-javascript");
	echo "YahooWin5(850,'$page?domain-popup-add=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}&t={$_GET["t"]}','$title')";
}
function domains_delete_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	header("content-type: application/x-javascript");
	echo "
var x_DeleteSMTPDomainInstance$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue)};
	$('#flexRT{$_GET["t"]}').flexReload();
}	
		
function DeleteSMTPDomainInstance$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ou','{$_GET["ou"]}');
	XHR.appendData('hostname','{$_GET["hostname"]}');
	XHR.appendData('smtp-daemon-uuid','{$_GET["uuid"]}');
	XHR.appendData('domain-delete','{$_GET["domain"]}');
	XHR.sendAndLoad(\"$page\", 'GET',x_DeleteSMTPDomainInstance$t);
}

DeleteSMTPDomainInstance$t();
";
}
	
	
function domains_add(){
	$page=CurrentPageName();
	$tpl=new templates();
	$main=new maincf_multi($_POST["hostname"],$_POST["ou"]);	
	$array=unserialize(base64_decode($main->GET_BIGDATA("domain_throttle_daemons_list")));	
	$uuid=$_POST["smtp-daemon-uuid"];
	$array[$uuid]["DOMAINS"][$_POST["domains-add"]]=true;
	if(!$main->SET_BIGDATA("domain_throttle_daemons_list",base64_encode(serialize($array)))){writelogs("{$_POST["hostname"]}/{$_POST["ou"]}: error...");echo "ERROR";return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-throttle=yes&instance={$_POST["hostname"]}");	
}

function domains_delete(){
	$page=CurrentPageName();
	$tpl=new templates();
	$main=new maincf_multi($_GET["hostname"],$_GET["ou"]);	
	$array=unserialize(base64_decode($main->GET_BIGDATA("domain_throttle_daemons_list")));	
	$uuid=$_GET["smtp-daemon-uuid"];
	unset($array[$uuid]["DOMAINS"][$_GET["domain-delete"]]);
	if(!$main->SET_BIGDATA("domain_throttle_daemons_list",base64_encode(serialize($array)))){writelogs("{$_GET["hostname"]}/{$_GET["ou"]}: error...");echo "ERROR";return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-throttle=yes&instance={$_GET["hostname"]}");		
}


function domains_popup(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$daemon=$tpl->javascript_parse_text("{daemon}");
	$domain=$tpl->javascript_parse_text("{domain}");
	$destination_limit=$tpl->javascript_parse_text("{destination_limit}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$rate_delay=$tpl->javascript_parse_text("{rate_delay}");
	$new=$tpl->javascript_parse_text("{new_domain}");
	$about=$tpl->javascript_parse_text("{about2}");
	$help=$tpl->javascript_parse_text("{domain_throttle_domain_explain}");
	$title=$tpl->javascript_parse_text("{domain_throttle}");
	$buttons="
	buttons : [
	{name: '$new', bclass: 'add', onpress : Add$t},
	{name: '$about', bclass: 'Help', onpress : Help$t},
	
	],";
	
	
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	<script>
	var memid='';
	$('#flexRT$t').flexigrid({
	url: '$page?domains-list=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}&t=$t',
	dataType: 'json',
	colModel : [
	{display: '$domain', name : 'daemon', width : 400, sortable : true, align: 'left'},
	{display: '$daemon', name : 'daemon', width : 400, sortable : true, align: 'left'},
	{display: '$delete;', name : 'delete', width : 82, sortable : 111, align: 'center'},
	],
	$buttons
	
	sortname: 'domain',
		sortorder: 'asc',
		usepager: true,
		title: '<span style=font-size:20px>$title</span>',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: '99%',
		height: 350,
		singleSelect: true,
	});
	
	
function Help$t(){
	alert('$help');
}
	
var x_smtp_daemon_add= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue)};
	$('#flexRT$t').flexReload();
}
	
	
function Add$t(){
	Loadjs('$page?domain-popup-add-js=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}&t=$t');
}
</script>";
	
	echo $html;
	
	
}
	
	
function domains_popup_add(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$main=new maincf_multi($_GET["hostname"],$_GET["ou"]);	
	$array=unserialize(base64_decode($main->GET_BIGDATA("domain_throttle_daemons_list")));
	if(is_array($array)){
		while (list ($uuid, $array_conf) = each ($array) ){
			$instances_list[$uuid]=$array_conf["INSTANCE_NAME"];
			
		}
		
	}
	
	$field_instances=Field_array_Hash($instances_list,"smtp-daemon-uuid-$t",null,
			"style:font-size:18px;padding:3px");
	
	
	$html="
	<div class=explain style='font-size:16px'>{domain_throttle_domain_explain}</div>
	<center style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{smtp_daemon_name}:</td>
		<td>$field_instances</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{domain}:</td>
		<td>". Field_text("smtp_domainadd-$t",null,"font-size:18px;padding:3px;","script:smtp_domainaddfunc_check(event)")."</td>
	</tr>
	<tr>
			<td colspan=2 align=right><hR>". button("{add}","smtp_domainaddfunc$t()",26)."</td>
	</tr>
	</table>
	</center>
	
	
	<script>
var x_smtp_domainaddfunc= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;};
	YahooWin5Hide();
	$('#flexRT{$_GET["t"]}').flexReload();
}	
		
function smtp_domainaddfunc_check(e){
	if(checkEnter(e)){smtp_domainaddfunc$t();}
}
		
function smtp_domainaddfunc$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ou','{$_GET["ou"]}');
	XHR.appendData('hostname','{$_GET["hostname"]}');
	XHR.appendData('domains-add',document.getElementById('smtp_domainadd-$t').value);
	XHR.appendData('smtp-daemon-uuid',document.getElementById('smtp-daemon-uuid-$t').value);
	XHR.sendAndLoad(\"$page\", 'POST',x_smtp_domainaddfunc);
}
</script>	
	
	";
	echo $tpl->_ENGINE_parse_body($html);			
	
}
	
	
function smtp(){
	smtp_instance_list();
}

function smtp_instance_list(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$daemon=$tpl->javascript_parse_text("{daemon}");
	$destination_limit=$tpl->javascript_parse_text("{destination_limit}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$rate_delay=$tpl->javascript_parse_text("{rate_delay}");
	$new=$tpl->javascript_parse_text("{new_rule}");
	$about=$tpl->javascript_parse_text("{about2}");
	$help=$tpl->javascript_parse_text("{domain_throttle_domain_explain}");
	$title=$tpl->javascript_parse_text("{domain_throttle}");
	$buttons="
	buttons : [
	{name: '$new', bclass: 'add', onpress : Add$t},
	{name: '$about', bclass: 'Help', onpress : Help$t},
	
	],";	
	
	
$html="
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
<script>
	var memid='';
$('#flexRT$t').flexigrid({
	url: '$page?smtp-instance-search=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}&t=$t',
	dataType: 'json',
	colModel : [
	{display: '$daemon', name : 'daemon', width : 558, sortable : true, align: 'left'},
	{display: '$destination_limit', name : 'destination_limit', width :111, sortable : true, align: 'center'},
	{display: '$rate_delay', name : 'rate_delay', width :111, sortable : true, align: 'center'},
	{display: '$delete;', name : 'delete', width : 86, sortable : 111, align: 'center'},
	],
	$buttons
	
	sortname: 'domain',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:20px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 350,
	singleSelect: true,
});


function Help$t(){
	alert('$help');
}

var x_smtp_daemon_add= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue)};
	$('#flexRT$t').flexReload();
}	

		
function Add$t(){
	var instance=prompt('instance');
	if(!instance){return;}
	var XHR = new XHRConnection();
	XHR.appendData('ou','{$_GET["ou"]}');
	XHR.appendData('hostname','{$_GET["hostname"]}');
	XHR.appendData('smtp-instance-add',instance);
	XHR.sendAndLoad(\"$page\", 'GET',x_smtp_daemon_add);
}
	
</script>";
	
echo $html;	
	
}

function smtp_instance_search(){
	
	$MyPage=CurrentPageName();
	$tpl=new templates();		
	$main=new maincf_multi($_GET["hostname"],$_GET["ou"]);	
	$array=unserialize(base64_decode($main->GET_BIGDATA("domain_throttle_daemons_list")));
	
	if(count($array)==0){
		json_error_show("no data");
	}
	
		
$c=0;
while (list ($uuid, $array_conf) = each ($array) ){
	$color="#909090";
	$m5=md5($array_conf["INSTANCE_NAME"]);
	$c++;
	$MyPage=CurrentPageName();
	if($array_conf["ENABLED"]==1){$color="black";}
	
	$instancename=urlencode($array_conf["INSTANCE_NAME"]);
	$js="javascript:Loadjs('$MyPage?smtp-instance-tab-js=$uuid&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}&t={$_GET["t"]}&title=$instancename')";
	
	
	
	
	$link="<a href=\"javascript:blur();\"
					OnClick=\"$js\" style='font-size:20px;font-weight:bold;text-decoration:underline'>";
	
	$data['rows'][] = array(
			'id' => "dom$m5",
			'cell' => array(
					"$link{$array_conf["INSTANCE_NAME"]}</a>",
					"<center>$link{$array_conf["transport_destination_concurrency_limit"]}</a></center>",
					"<center>$link{$array_conf["transport_destination_rate_delay"]}</a></center>",
					"<center>".imgsimple('delete-32.png','{delete}',
					"Loadjs('$MyPage?smtp-instance-delete-js=yes&uuid=$uuid&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}&t={$_GET["t"]}')")."<center>" 
			)
	);
}


if($c==0){
	json_error_show("no data");
}
	
	$data['page'] = 1;
	$data['total'] = $c;
	echo json_encode($data);

	
	echo $tpl->_ENGINE_parse_body($html);		
	
}



function domains_list(){
	$MyPage=CurrentPageName();
	$tpl=new templates();		
	$main=new maincf_multi($_GET["hostname"],$_GET["ou"]);	
	$array=unserialize(base64_decode($main->GET_BIGDATA("domain_throttle_daemons_list")));
	
	if(count($array)==0){
		json_error_show("no data");
	}
	$c=0;
	while (list ($uuid, $array_conf) = each ($array) ){
		$color="#909090";
		if($array_conf["ENABLED"]==1){$color="black";}		

			$js="<a href=\"javascript:blur();\" 
			style='font-size:14px;text-decoration:underline;color:$color' 
			OnClick=\"javascript:YahooWin4(650,'$page?smtp-instance-edit=$uuid&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}','{$array_conf["INSTANCE_NAME"]}')\">";
		
		while (list ($domain, $none) = each ($array_conf["DOMAINS"]) ){    
			
			$c++;
			$data['rows'][] = array(
					'id' => "dom$m5",
					'cell' => array(
							"<span style='font-size:22px'>$domain</a>",
							"<span style='font-size:22px'>{$array_conf["INSTANCE_NAME"]}</a></span>",
							"<center>".imgsimple('delete-32.png','{delete}',
									"Loadjs('$MyPage?domain-delete-js=yes&uuid=$uuid&domain=$domain&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}&t={$_GET["t"]}')")."<center>"
											)
			);
		}
	}
	if($c==0){
		json_error_show("no data");
	}
	
	$data['page'] = 1;
	$data['total'] = $c;
	echo json_encode($data);
	
}

function smtp_instance_save(){
	$instance=$_POST["smtp-instance-save"];
	$main=new maincf_multi($_POST["hostname"],$_POST["ou"]);	
	$array=unserialize(base64_decode($main->GET_BIGDATA("domain_throttle_daemons_list")));	
	while (list ($key, $val) = each ($_POST) ){
		if(preg_match("#^Text_#", $key)){continue;}
		$array[$instance][$key]=$val;
	}
	if(!$main->SET_BIGDATA("domain_throttle_daemons_list",base64_encode(serialize($array)))){writelogs("{$_POST["hostname"]}/{$_POST["ou"]}: error...");echo "ERROR";return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-throttle=yes&instance={$_POST["hostname"]}");	
}

function smtp_instance_add(){
	
	$instance=$_GET["smtp-instance-add"];
	if(trim($instance)==null){$instance=time();}
	$main=new maincf_multi($_GET["hostname"],$_GET["ou"]);	
	$array=unserialize(base64_decode($main->GET_BIGDATA("domain_throttle_daemons_list")));	
	if(!is_array($array)){$array=array();}
	$uuid=time();
	$array[$uuid]["INSTANCE_NAME"]=$instance;
	$array[$uuid]["transport_destination_concurrency_limit"]="20";
	$array[$uuid]["transport_destination_rate_delay"]="0s";
	$array[$uuid]["ENABLED"]="1";
	if(!$main->SET_BIGDATA("domain_throttle_daemons_list",base64_encode(serialize($array)))){writelogs("{$_GET["hostname"]}/{$_GET["ou"]}: error...");echo "ERROR";return;}	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-throttle=yes&instance={$_GET["hostname"]}");	
	
}

function smtp_instance_delete(){
	$instance=$_GET["smtp-instance-delete"];
	$main=new maincf_multi($_GET["hostname"],$_GET["ou"]);	
	$array=unserialize(base64_decode($main->GET_BIGDATA("domain_throttle_daemons_list")));	
	unset($array[$instance]);
	if(!$main->SET_BIGDATA("domain_throttle_daemons_list",base64_encode(serialize($array)))){writelogs("{$_GET["hostname"]}/{$_GET["ou"]}: error...");echo "ERROR";return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-throttle=yes&instance={$_GET["hostname"]}");
	
	
}

function smtp_instance_edit(){
		
	$page=CurrentPageName();
	$tpl=new templates();		
	$uuid=$_GET["smtp-instance-edit"];
	$main=new maincf_multi($_GET["hostname"],$_GET["ou"]);	
	$array=unserialize(base64_decode($main->GET_BIGDATA("domain_throttle_daemons_list")));
	$conf=$array[$uuid];
	
	if($conf["transport_destination_concurrency_failed_cohort_limit"]==null){$conf["transport_destination_concurrency_failed_cohort_limit"]=1;}
	if($conf["transport_delivery_slot_loan"]==null){$conf["transport_delivery_slot_loan"]=3;}
	if($conf["transport_delivery_slot_discount"]==null){$conf["transport_delivery_slot_discount"]=50;}
	if($conf["transport_delivery_slot_cost"]==null){$conf["transport_delivery_slot_cost"]=5;}
	if($conf["transport_extra_recipient_limit"]==null){$conf["transport_extra_recipient_limit"]=1000;}
	if($conf["transport_initial_destination_concurrency"]==null){$conf["transport_initial_destination_concurrency"]=5;}
	if($conf["transport_destination_recipient_limit"]==null){$conf["transport_destination_recipient_limit"]=50;}
	if($conf["transport_destination_rate_delay"]==null){$conf["transport_destination_rate_delay"]="0s";}
	if($conf["transport_destination_concurrency_positive_feedback"]==null){$conf["transport_destination_concurrency_positive_feedback"]="1/5";}
	if($conf["transport_destination_concurrency_negative_feedback"]==null){$conf["transport_destination_concurrency_negative_feedback"]="1/5";}
	if(!is_numeric($conf["default_process_limit"])){$conf["default_process_limit"]=100;}
	
	if($conf["smtp_connection_cache_on_demand"]==null){$conf["smtp_connection_cache_on_demand"]="1";}
	if($conf["smtp_connection_cache_time_limit"]==null){$conf["smtp_connection_cache_time_limit"]="2s";}
	if($conf["smtp_connection_reuse_time_limit"]==null){$conf["smtp_connection_reuse_time_limit"]="300s";}
	
	//smtp_connection_cache_destinations
	

	$html="
	<div class=explain style='font-size:18px;margin-bottom:20px'>{domain_throttle_explain_edit}</div>
	<div id='id-$uuid' style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{smtp_daemon_name}:<td>
		<td>". Field_text("INSTANCE_NAME",$conf["INSTANCE_NAME"],"width:290px;font-size:18px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{default_process_limit}:<td>
		<td>". Field_text("default_process_limit",$conf["default_process_limit"],"width:60px;font-size:18px")."</td>
		<td>". help_icon("{default_process_limit_text}")."</td>
	</tr>	

	<tr>
		<td class=legend style='font-size:18px'>{enabled}:<td>
		<td>". Field_checkbox("ENABLED",1,$conf["ENABLED"],"CheckEnabledInstance()")."</td>
		<td>&nbsp;</td>
	<tr>
	<tr>
		<td class=legend style='font-size:18px'>{smtp_connection_cache_on_demand}:<td>
		<td>". Field_checkbox("smtp_connection_cache_on_demand",1,$conf["smtp_connection_cache_on_demand"],"CheckConnexionCache()")."</td>
		<td>". help_icon("{smtp_connection_cache_on_demand_text}")."</td>
	<tr>	
	
	<tr>
		<td class=legend style='font-size:18px'>{smtp_connection_cache_time_limit}:<td>
		<td>". Field_text("smtp_connection_cache_time_limit",$conf["smtp_connection_cache_time_limit"],"width:60px;font-size:18px")."</td>
		<td>". help_icon("{smtp_connection_cache_time_limit_text}")."</td>
	<tr>
	<tr>
		<td class=legend style='font-size:18px'>{smtp_connection_reuse_time_limit}:<td>
		<td>". Field_text("smtp_connection_reuse_time_limit",$conf["smtp_connection_reuse_time_limit"],"width:60px;font-size:18px")."</td>
		<td>". help_icon("{smtp_connection_reuse_time_limit_text}")."</td>
	<tr>			
	<tr>
		<td class=legend style='font-size:18px'>{default_destination_concurrency_limit}:<td>
		<td>". Field_text("transport_destination_concurrency_limit",$conf["transport_destination_concurrency_limit"],"width:60px;font-size:18px")."</td>
		<td width=1%>". help_icon("{default_destination_concurrency_limit_text}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{default_destination_rate_delay}:<td>
		<td>". Field_text("transport_destination_rate_delay",$conf["transport_destination_rate_delay"],"width:60px;font-size:18px")."</td>
		<td width=1%>". help_icon("{default_destination_rate_delay_text}")."</td>
	</tr>
	
	<tr>
		<td class=legend style='font-size:18px'>{initial_destination_concurrency}:<td>
		<td>". Field_text("transport_initial_destination_concurrency",$conf["transport_initial_destination_concurrency"],"width:60px;font-size:18px")."</td>
		<td width=1%>". help_icon("{initial_destination_concurrency_text}")."</td>		
	</tr>	
	
	<tr>
		<td class=legend style='font-size:18px'>{default_destination_concurrency_failed_cohort_limit}:<td>
		<td>". Field_text("transport_destination_concurrency_failed_cohort_limit",$conf["transport_destination_concurrency_failed_cohort_limit"],"width:60px;font-size:18px")."</td>
		<td width=1%>". help_icon("{default_destination_concurrency_failed_cohort_limit_text}")."</td>		
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{default_destination_concurrency_positive_feedback}:<td>
		<td>". Field_text("transport_destination_concurrency_positive_feedback",$conf["transport_destination_concurrency_positive_feedback"],"width:60px;font-size:18px")."</td>
		<td width=1%>". help_icon("{default_destination_concurrency_positive_feedback_text}")."</td>		
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{default_destination_concurrency_negative_feedback}:<td>
		<td>". Field_text("transport_destination_concurrency_negative_feedback",$conf["transport_destination_concurrency_negative_feedback"],"width:60px;font-size:18px")."</td>
		<td width=1%>". help_icon("{default_destination_concurrency_negative_feedback_text}")."</td>		
	</tr>		
	<tr>
		<td class=legend style='font-size:18px'>{default_destination_recipient_limit}:<td>
		<td>". Field_text("transport_destination_recipient_limit",$conf["transport_destination_recipient_limit"],"width:60px;font-size:18px")."</td>
		<td width=1%>". help_icon("{default_destination_recipient_limit_text}")."</td>		
	</tr>		
	
	<tr>
		<td class=legend style='font-size:18px'>{default_extra_recipient_limit}:<td>
		<td>". Field_text("transport_extra_recipient_limit",$conf["transport_extra_recipient_limit"],"width:60px;font-size:18px")."</td>
		<td width=1%>". help_icon("{default_extra_recipient_limit_text}")."</td>		
	</tr>	

	<tr>
		<td class=legend style='font-size:18px'>{default_delivery_slot_loan}:<td>
		<td>". Field_text("transport_delivery_slot_loan",$conf["transport_delivery_slot_loan"],"width:60px;font-size:18px")."</td>
		<td width=1%>". help_icon("{default_delivery_slot_loan_text}")."</td>
	</tr>	
		
	<tr>
		<td class=legend style='font-size:18px'>{default_delivery_slot_cost}:<td>
		<td>". Field_text("transport_delivery_slot_cost",$conf["transport_delivery_slot_cost"],"width:60px;font-size:18px")."</td>
		<td width=1%>". help_icon("{default_delivery_slot_cost_text}")."</td>
	</tr>		
	
	<tr>
		<td class=legend style='font-size:18px'>{default_delivery_slot_discount}:<td>
		<td>". Field_text("transport_delivery_slot_discount",$conf["transport_delivery_slot_discount"],"width:60px;font-size:18px")."</td>
		<td width=1%>". help_icon("{default_delivery_slot_discount_text}")."</td>
	</tr>	
	
	<tr>
		<td align=right colspan=3><hr>". button("{apply}","SaveSMTPInstanceParams()",22)."</td>
	</tr>
	
	</table>
	</div>
	<script>
		function CheckEnabledInstance(){
			DisableFieldsFromId('id-$uuid');
			document.getElementById('ENABLED').disabled=false;
			document.getElementById('INSTANCE_NAME').disabled=false;
			if(!document.getElementById('ENABLED').checked){return;}
			EnableFieldsFromId('id-$uuid');
			
		}
		
		function CheckConnexionCache(){
			if(!document.getElementById('ENABLED').checked){return;}
			document.getElementById('smtp_connection_cache_time_limit').disabled=true;
			document.getElementById('smtp_connection_reuse_time_limit').disabled=true;
			if(!document.getElementById('smtp_connection_cache_on_demand').checked){return;}
			document.getElementById('smtp_connection_cache_time_limit').disabled=false;
			document.getElementById('smtp_connection_reuse_time_limit').disabled=false;			
			  
		
		}
	
	
		var x_SaveSMTPInstanceParams= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			
			RefreshTab('main_smtp_instance_edit_tab');
			$('#flexRT{$_GET["t"]}').flexReload();
			
		}	
		
		function SaveSMTPInstanceParams(){
			var XHR = XHRParseElements('id-$uuid');
			XHR.appendData('ou','{$_GET["ou"]}');
			XHR.appendData('hostname','{$_GET["hostname"]}');
			XHR.appendData('smtp-instance-save','$uuid');
			XHR.sendAndLoad(\"$page\", 'POST',x_SaveSMTPInstanceParams);
		}	
	CheckEnabledInstance();
	CheckConnexionCache();
	</script>	
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function smtp_instance_cache_destinations(){
	$page=CurrentPageName();
	$tpl=new templates();		
	$uuid=$_GET["smtp-instance-cache-destinations"];
	$add_server_domain=$tpl->_ENGINE_parse_body("{add_server_domain}");	
	
	$html="
	
	<div id='ServerCacheList-$uuid'></div>
	
	<script>
		function CacheReloadList(){
			LoadAjax('ServerCacheList-$uuid','$page?smtp-instance-cache-destinations-list=yes&hostname={$_GET["hostname"]}&uuid=$uuid&ou={$_GET["ou"]}');
	}	
	CacheReloadList();
	
	function PostFixAddServerCache(){
		YahooWin6(550,'$page?smtp-instance-cache-destinations-add=yes&hostname={$_GET["hostname"]}&uuid=$uuid&ou={$_GET["ou"]}','$add_server_domain');
	}	
	
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function smtp_instance_cache_destinations_list(){
	$page=CurrentPageName();
	$tpl=new templates();		
	$uuid=$_GET["uuid"];
	$main=new maincf_multi($_GET["hostname"],$_GET["ou"]);	
	$array=unserialize(base64_decode($main->GET_BIGDATA("domain_throttle_daemons_list")));
	$smtp_connection_cache_destinations=$array[$uuid]["smtp-instance-cache-destinations"];
		
	$add=imgtootltip("plus-24.png","{add_server_domain}","PostFixAddServerCache()");
	$html="<center>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th width=1%>$add</th>
		<th>{hostname}</th>
		<th>". help_icon("{smtp_connection_cache_destinations_text}")."</th>
	</tr>
</thead>
<tbody class='tbody'>";
	while (list ($num, $ligne) = each ($smtp_connection_cache_destinations) ){
	if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$html=$html . "<tr class=$classtr>
			<td colspan=2><strong style='font-size:14px'>$num</strong></td>
			<td width=1%>" . imgtootltip('delete-32.png','{delete}',"PostFixDeleteServerCache('$num')") . "</td>
			</tr>
			";
		
	}
	
	echo $tpl->_ENGINE_parse_body($html . "</tbody></table>
	<script>
	var x_PostFixDeleteServerCache=function(obj){
    	var tempvalue=trim(obj.responseText);
	  	if(tempvalue.length>3){alert(tempvalue);}
		CacheReloadList();
		}	
	
		function PostFixDeleteServerCache(value){
			var XHR = new XHRConnection();	
			XHR.appendData('smtp-instance-cache-destinations-delete',value);
			XHR.appendData('hostname','{$_GET["hostname"]}');
			XHR.appendData('uuid','$uuid');
			XHR.appendData('ou','{$_GET["ou"]}');
			AnimateDiv('ServerCacheList-$uuid');
			XHR.sendAndLoad('$page', 'POST',x_PostFixDeleteServerCache);				
		
		}
	
	</script>
	");
	
	

}

function smtp_instance_cache_destinations_add(){
	$tpl=new templates();
	$page=CurrentPageName();
	
	$html="<div id='PostFixAddServerCacheDiv'></div>
	<input type='hidden' name='PostFixAddServerCacheSave' value='yes'>
	<table style='width:99%' class=form>
	<tr>
	<td class=legend nowrap><strong>{domain}:</strong></td>
	<td>" . Field_text('domain',$domainName,"font-size:14px;padding:3px;width:220px") . "</td>
	</tr>
	<td class=legend nowrap nowrap><strong>{or} {relay_address}:</strong></td>
	<td>" . Field_text('relay_address',$relay_address,"font-size:14px;padding:3px;width:220px") . "</td>	
	</tr>
	</tr>
	<td class=legend nowrap nowrap><strong>{smtp_port}:</strong></td>
	<td>" . Field_text('relay_port',25,"font-size:14px;padding:3px;width:40px") . "</td>	
	</tr>	
	<tr>
	<td class=legend style='font-size:18px'>{MX_lookups}</td>	
	<td>" . Field_checkbox('MX_lookups','1',0)."</td>
	</tr>

	<tr>
	<td colspan=2 align='right'><hr>". button("{add}","PostFixSaveServerCache()")."</td>
	</tr>		
	<tr>
	<td align='left' colspan=2><strong{MX_lookups}</strong><br><div class=explain>{MX_lookups_text}</div></td>
	</tr>			
		
	</table>
	<script>
	
	var x_PostFixSaveServerCache=function(obj){
    	var tempvalue=trim(obj.responseText);
	  	if(tempvalue.length>3){alert(tempvalue);}
		document.getElementById('PostFixAddServerCacheDiv').innerHTML='';
		CacheReloadList();
		}	
	
		function PostFixSaveServerCache(){
		var XHR = new XHRConnection();	
			if(document.getElementById('MX_lookups').checked){XHR.appendData('MX_lookups','yes');}else{XHR.appendData('MX_lookups','no');}
			XHR.appendData('domain',document.getElementById('domain').value);
			XHR.appendData('relay_address',document.getElementById('relay_address').value);
			XHR.appendData('relay_port',document.getElementById('relay_port').value);
			XHR.appendData('smtp-instance-cache-destinations-new','yes');
			XHR.appendData('hostname','{$_GET["hostname"]}');
			XHR.appendData('uuid','{$_GET["uuid"]}');
			XHR.appendData('ou','{$_GET["ou"]}');
			AnimateDiv('PostFixAddServerCacheDiv');
			XHR.sendAndLoad('$page', 'POST',x_PostFixSaveServerCache);				
		
		}
	</script>
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function smtp_instance_cache_destinations_save(){
	$tool=new DomainsTools();
	$tpl=new templates();
	$relay_address=$_POST["relay_address"];
	$relay_port=$_POST["relay_port"];
	$MX_lookups=$_GET["MX_lookups"];
	$domain=$_POST["domain"];
	$uuid=$_POST["uuid"];
	
	if($domain<>null && $relay_address<>null){echo $tpl->javascript_parse_text('{error_give_server_or_domain}');exit();}
	
	
	if($relay_address<>null){
		$line=$tool->transport_maps_implode($relay_address,$relay_port,null,$MX_lookups);
		$line=str_replace('smtp:','',$line);
	}else{$line=$domain;}
	
	$main=new maincf_multi($_POST["hostname"],$_POST["ou"]);	
	$array=unserialize(base64_decode($main->GET_BIGDATA("domain_throttle_daemons_list")));
	$array[$uuid]["smtp-instance-cache-destinations"][$line]="OK";
	$smtp_connection_cache_destinations_new=base64_encode(serialize($array));
	if(!$main->SET_BIGDATA("domain_throttle_daemons_list", addslashes($smtp_connection_cache_destinations_new))){echo $main->$q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-throttle=yes&instance={$_GET["hostname"]}");
}

function smtp_instance_cache_destinations_del(){
	$uuid=$_POST["uuid"];
	$main=new maincf_multi($_POST["hostname"],$_POST["ou"]);	
	$array=unserialize(base64_decode($main->GET_BIGDATA("domain_throttle_daemons_list")));
	unset($array[$uuid]["smtp-instance-cache-destinations"][$_POST["smtp-instance-cache-destinations-delete"]]);
	$smtp_connection_cache_destinations_new=base64_encode(serialize($array));
	if(!$main->SET_BIGDATA("domain_throttle_daemons_list", addslashes($smtp_connection_cache_destinations_new))){echo $main->$q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-throttle=yes&instance={$_GET["hostname"]}");	
}

function smtp_instance_tab(){
	$page=CurrentPageName();
	$tpl=new templates();
	$array["smtp-instance-edit"]='{parameters}';
	$array["smtp-instance-cache-destinations"]="{smtp_connection_cache_destinations_field}";

	
	while (list ($num, $ligne) = each ($array) ){
		$tab[]="<li>
		<a href=\"$page?$num={$_GET["smtp-instance-tab"]}&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}&t={$_GET["t"]}\">
			<span style='font-size:18px'>$ligne</span></a></li>\n";
			
	}

	
	echo build_artica_tabs($tab, "main_smtp_instance_edit_tab");
}
	

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$array["smtp"]='{smtp_senders}';
	$array["domains"]="{routing_domains}";

	
	while (list ($num, $ligne) = each ($array) ){
		$tab[]="<li><a href=\"$page?$num=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}\">
		<span style='font-size:18px'>$ligne</span></a></li>\n";
			
	}
	
	
	echo build_artica_tabs($tab, "main_ecluse_config");

		
	
		
}
function PostFixVerifyRights(){
	$usersmenus=new usersMenus();
	if($usersmenus->AsPostfixAdministrator){return true;}
	if($usersmenus->AsMessagingOrg){return true;}
	}	
?>