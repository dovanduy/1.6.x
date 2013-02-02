<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}

	if(isset($_GET["edit-proxy-parent-options"])){parent_options_popup();exit;}
	if(isset($_POST["DeleteSquidOption"])){delete_options();exit;}
	if(isset($_POST["AddSquidParentOptionOrginal"])){construct_options();exit;}
	
	if(isset($_GET["popup"])){website_table();exit;}
	if(isset($_GET["SquidActHasReverse"])){Save();exit;}
	if(isset($_GET["website"])){add_website();exit;}
	if(isset($_GET["websites-list"])){websites_list();exit;}
	if(isset($_GET["AccelAddReverseSiteDelete"])){del_website();exit;}
	if(isset($_GET["website-popup-js"])){website_popup_js();exit;}
	if(isset($_GET["website-popup"])){website_popup();exit;}
	if(isset($_GET["website-tabs"])){website_tabs();exit;}
	if(isset($_GET["website-options"])){website_options();exit;}
	if(isset($_GET["website-options-list"])){website_options_items();exit;}
	
	
	if(isset($_GET["virtualhosts-js"])){virtual_host_js();exit;}
	if(isset($_GET["virtualhosts-popup"])){virtual_host_popup();exit;}
	if(isset($_GET["virtualhost-list"])){virtual_host_list();exit;}
	if(isset($_POST["virtualhost-add"])){virtual_host_add();exit;}
	if(isset($_POST["virtualhost-del"])){virtual_host_del();exit;}
	if(isset($_POST["EnableAll"])){enable_disable();exit;}
	
	
	
	
js();

function construct_options(){
	$ID=$_POST["ID"];
	$q=new mysql();
	$sql="SELECT options FROM squid_accel WHERE ID={$_POST["ID"]}";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$based=unserialize(base64_decode($ligne["options"]));
	$key=base64_decode($_POST["key"]);

	writelogs("$ID]decoded key:\"$key\"",__FUNCTION__,__FILE__,__LINE__);
	if(preg_match("#(.+?)=#",$key,$re)){
		$key=$re[1];
	}


	if(!is_array($based)){
		$based[$key]=$_POST["value"];
		writelogs("$ID]send ". serialize($based),__FUNCTION__,__FILE__,__LINE__);
		$NewOptions=base64_encode(serialize($based));
		$q->QUERY_SQL("UPDATE squid_accel SET options='$NewOptions' WHERE ID='$ID'","artica_backup");
		return;
	}

	$based[$key]=$_POST["value"];

	while (list($num,$val)=each($based)){
		if(trim($num)==null){continue;}
		$f[$num]=$val;
	}


	$NewOptions=base64_encode(serialize($f));
	$q->QUERY_SQL("UPDATE squid_accel SET options='$NewOptions' WHERE ID='$ID'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();


}

function delete_options(){
	$q=new mysql();
	$sql="SELECT options FROM squid_accel WHERE ID={$_POST["ID"]}";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$array=unserialize(base64_decode($ligne["options"]));
	$key=$_POST["DeleteSquidOption"];

	writelogs("DELETING $key FOR {$_POST["ID"]}",__FUNCTION__,__FILE__,__LINE__);

	if(!is_array($array)){
		writelogs("Not an array...",__FUNCTION__,__FILE__,__LINE__);
		echo "unable to unserialize $array\n";
		$array=array();
		return;
	}
	unset($array[$key]);
	$newarray=base64_encode(serialize($array));
	$sql="UPDATE squid_accel SET options='$newarray' WHERE ID='{$_POST["ID"]}'";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();

}

function parent_options_popup(){
	$tt=time();
	$t=$_GET["t"];
	$ttt=$_GET["table-source"];
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$array=unserialize(base64_decode($_GET["edit-proxy-parent-options"]));
	$options[null]="{select}";
	$options[base64_encode("proxy-only")]="proxy-only";
	$options[base64_encode("Weight=n")]="Weight=n";
	$options[base64_encode("ttl=n")]="ttl=n";
	$options[base64_encode("basetime=n")]="basetime=n";
	$options[base64_encode("no-query")]="no-query";
	$options[base64_encode("default")]="default";
	$options[base64_encode("round-robin")]="round-robin";
	$options[base64_encode("multicast-responder")]="multicast-responder";
	$options[base64_encode("closest-only")]="closest-only";
	$options[base64_encode("no-digest")]="no-digest";
	$options[base64_encode("no-netdb-exchange")]="no-netdb-exchange";
	$options[base64_encode("no-delay")]="no-delay";
	$options[base64_encode("login=user:password")]="login=user:password";
	$options[base64_encode("connect-timeout=nn")]="connect-timeout=nn";
	$options[base64_encode("digest-url=url")]="digest-url=url";
	$options[base64_encode("connect-fail-limit=n")]="connect-fail-limit=n";
	$options[base64_encode("loginPASSTHRU")]="login=PASSTHRU";
	$options[base64_encode("connection-auth")]="connection-auth=on|off";
	$options[base64_encode("loginPASS")]="login=PASS";
	$options[base64_encode("carp")]="carp";
	//$options[base64_encode("ssl")]="ssl";
	
	$sql="SELECT options FROM squid_accel WHERE ID=$ID";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	

	$html="
	<input type='hidden' id='SquidParentOptions' name='SquidParentOptions' value=\"{$ligne["options"]}\">
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:13px'>{squid_parent_options}:</td>
		<td>". Field_array_Hash($options,"squid_parent_options_f",base64_encode("proxy-only"),"FillSquidParentOptions$tt()",null,0,
				"font-size:16px;padding:5px")."</td>
				</tr>
				</table>
				<div id='squid_parent_options_filled'></div>
		<script>

		function FillSquidParentOptions$tt(){
			var selected=document.getElementById('squid_parent_options_f').value
			LoadAjax('squid_parent_options_filled','squid.loadbalancer.main.php?edit-proxy-parent-options-explain='+selected+'&ID=$ID&tt=$tt');
		}

		var x_AddSquidOption$tt= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			YahooWin5Hide();
			$('#parent-options-$t').flexReload();
			$('#flexRT$ttt').flexReload();
		}

		function AddSquidOption$tt(){
			var XHR = new XHRConnection();
			XHR.appendData('AddSquidParentOptionOrginal',document.getElementById('SquidParentOptions').value);
			XHR.appendData('key',document.getElementById('squid_parent_options_f').value);
			XHR.appendData('ID',$ID);
			if(document.getElementById('parent_proxy_add_value')){
				XHR.appendData('value',document.getElementById('parent_proxy_add_value').value);
			}
	
			XHR.sendAndLoad('$page', 'POST',x_AddSquidOption$tt);
		}
	FillSquidParentOptions$tt();
</script>
";

$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);
}


function js(){
	
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{squid_reverse_proxy}");
	$page=CurrentPageName();
	$html="
		function squid_reverse_websites_proxy_load(){
			YahooWin3('850','$page?popup=yes','$title');
		
		}
		squid_reverse_websites_proxy_load();";
	
	echo $html;
	
}
function virtual_host_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	
	if($_GET["ID"]>0){
		$q=new mysql();
		$sql="SELECT website_name FROM squid_accel WHERE ID='{$_GET["ID"]}'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$new_webserver="{$_GET["ID"]}::{$ligne["website_name"]}::{virtualhosts}";
	}
	$new_webserver=$tpl->javascript_parse_text($new_webserver);
	$html="YahooWin5('600','$page?virtualhosts-popup=yes&t={$_GET["t"]}&ID={$_GET["ID"]}','$new_webserver')";
	echo $html;
}

function website_popup_js(){
	$tpl=new templates();
	$entry="website-popup";
	$page=CurrentPageName();
	$new_webserver=$tpl->javascript_parse_text("{new_website}");
	if($_GET["ID"]>0){
		$q=new mysql();
		$sql="SELECT website_name FROM squid_accel WHERE ID='{$_GET["ID"]}'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$new_webserver="{$_GET["ID"]}::{$ligne["website_name"]}";
		$entry="website-tabs";
	}
	
	$html="YahooWin4('795','$page?$entry=yes&t={$_GET["t"]}&ID={$_GET["ID"]}','$new_webserver')";
	echo $html;
}

function website_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	$q=new mysql();
	$sql="SELECT website_name FROM squid_accel WHERE ID=$ID";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$md5=md5(time().$ID);
	$array["website-popup"]=$ligne["website_name"];
	$array["website-options"]='{options}';

	
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&t={$_GET["t"]}&ID={$_GET["ID"]}\" style='font-size:14px'><span>$ligne</span></a></li>\n");
	}
	echo "
	<div id='accel{$_GET["ID"]}'>
		<ul>". implode("\n",$html)."</ul>
	</div>
	<script>
		$(document).ready(function(){
			$('#accel{$_GET["ID"]}').tabs();
		});
	</script>";
}


function virtual_host_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=300;
	$TB_WIDTH=575;
	$tt=time();
		
	$t=$_GET["t"];
	$website=$tpl->javascript_parse_text("{website}");
	$ip_address=$tpl->_ENGINE_parse_body("{ipaddr}");
	$listen_port=$tpl->_ENGINE_parse_body("{listen_port}");
	$title=$tpl->_ENGINE_parse_body("{squid_accel_websites}");
	$new_webserver=$tpl->javascript_parse_text("{new_website}");
	$apply_parameters=$tpl->javascript_parse_text("{apply_parameters}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$buttons="
	buttons : [
	
	{name: '$new_webserver', bclass: 'Add', onpress : NewVritHost$t},
	
	
	],	";
	$html="
	
	<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:99%'></table>
	
<script>
var mem2$t='';
$(document).ready(function(){
$('#flexRT$tt').flexigrid({
	url: '$page?virtualhost-list=yes&t=$t&ID={$_GET["ID"]}',
	dataType: 'json',
	colModel : [
		{display: '$website', name : 'website_name', width :476, sortable : true, align: 'left'},
		{display: '$delete', name : 'del', width :56, sortable : false, align: 'center'},
		
	
	],
	$buttons

	searchitems : [
		{display: '$website', name : 'website_name'},
		{display: '$ip_address', name : 'website_ip'},
		

	],
	sortname: 'website_name',
	sortorder: 'asc',
	usepager: true,
	title: '<span id=\"title-$t\">$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});
	var x_NewVritHost$t= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}
		$('#flexRT$t').flexReload();
		$('#flexRT$tt').flexReload();
	}	

function NewVritHost$t(){
	var virt=prompt('$website');
	if(virt){
		var XHR = new XHRConnection();
		XHR.appendData('virtualhost-add',virt);
		XHR.appendData('ID','{$_GET["ID"]}');
		XHR.sendAndLoad('$page', 'POST',x_NewVritHost$t);			
	}
}

var x_VirtualHostDelete$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	$('#row'+mem2$t).remove();
	
}	

function VirtualHostDelete$t(md,www){
	mem2$t=md;
	if(confirm('$delete '+www+' ?')){
		var XHR = new XHRConnection();
		XHR.appendData('ID','{$_GET["ID"]}');
		XHR.appendData('virtualhost-del',www);
		XHR.sendAndLoad('$page', 'POST',x_VirtualHostDelete$t);	
	}			
}

</script>";
	
	echo $html;
}	
function virtual_host_add(){
	$q=new mysql();
	$sql="SELECT virtualhosts FROM squid_accel WHERE ID='{$_POST["ID"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
	$array=unserialize(base64_decode($ligne["virtualhosts"]));	
	$array[$_POST["virtualhost-add"]]=$_POST["virtualhost-add"];
	$newarray=base64_encode(serialize($array));
	$sql="UPDATE squid_accel SET virtualhosts='$newarray' WHERE ID='{$_POST["ID"]}'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
}
function virtual_host_del(){
	$q=new mysql();
	$sql="SELECT virtualhosts FROM squid_accel WHERE ID='{$_POST["ID"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
	$array=unserialize(base64_decode($ligne["virtualhosts"]));	
	unset($array[$_POST["virtualhost-del"]]);
	$newarray=base64_encode(serialize($array));
	$sql="UPDATE squid_accel SET virtualhosts='$newarray' WHERE ID='{$_POST["ID"]}'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
}
function virtual_host_list(){
	$MyPage=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$t=$_GET["t"];
	$sql="SELECT virtualhosts FROM squid_accel WHERE ID='{$_GET["ID"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
	$array=unserialize(base64_decode($ligne["virtualhosts"]));
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	

	
	while (list ($www, $none) = each ($array)){
	$zmd5=md5($www);
	$color="black";
	$delete=imgsimple("delete-32.png","{delete}","VirtualHostDelete$t('$zmd5','$www')");
	
	$c++;
	
	
	$data['rows'][] = array(
		'id' => "$zmd5",
		'cell' => array(
			"<span style='font-size:16px;color:$color'>$www</a></span>",
			"<span style='font-size:16px;color:$color'>$delete</span>",
			)
		);
	}
	
	$data['total'] = $c;
echo json_encode($data);	
}	

	



function website_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=$_GET["t"];
	$ID=$_GET["ID"];
	if(!is_numeric($ID)){$ID=0;}
	if($_GET["ID"]>0){
		$q=new mysql();
		$sql="SELECT * FROM squid_accel WHERE ID='{$_GET["ID"]}'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$new_webserver=$ligne["website"];
		$certificate=$ligne["certificate"];
	}else{
		$ligne["enabled"]=1;
	}	
	
	$sql="SELECT CommonName FROM sslcertificates ORDER BY CommonName";
	$q=new mysql();
	$sslcertificates[null]="{select}";
	$results=$q->QUERY_SQL($sql,'artica_backup');
	while($ligneZ=mysql_fetch_array($results,MYSQL_ASSOC)){
		$sslcertificates[strtolower($ligneZ["CommonName"])]=$ligneZ["CommonName"];
	}
	
	
	if(!is_numeric($ligne["website_port"])){$ligne["website_port"]=80;}
	if($ID==0){
		$btname="{add}";
	}else{
		$btname="{apply}";
		$VirtualHostLink="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('$page?virtualhosts-js=yes&ID=$ID&t=$t')\"
		style='font-size:11px;text-decoration:underline;font-weight:bold'>&laquo;&nbsp;{virtualhosts}&nbsp;&raquo;</a>";
	}
	
	if($certificate<>null){
		$CertificateLink="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('certificates.center.php?certificate-js=yes&CommonName={$ligne["certificate"]}&YahooWin=YahooWin6');\"
		style='font-size:11px;text-decoration:underline;font-weight:bold'>&laquo;&nbsp;{certificate}:{$ligne["certificate"]}&nbsp;&raquo;</a>";
	}
	
	//$sql="INSERT INTO squid_accel (website_name,website_ip,website_port,`UseSSL`,`certificate`) 
	
	$html="	
	<div id='www_accel_list-$t'></div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px' nowrap>{enabled}:</td>
		<td width=99%>". Field_checkbox("enabled-$t",1,$ligne["enabled"],"CheckEnabled$t()")."</td>
		<td class=legend></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px' nowrap>{website}:</td>
		<td width=99%>". Field_text("website-$t",$ligne["website_name"],"font-size:16px;padding:3px;width:350px;font-weight:bold")."</td>
		<td class=legend>{example}:www.mydomain.tld</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px' nowrap>{ip_address}:</td>
		<td width=99%>". Field_text("website_ip-$t",$ligne["website_ip"],"font-size:16px;padding:3px;;font-weight:bold;width:220px")."</td>
		<td class=legend>{example}:192.168.1.24</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px' nowrap>{listen_port}:</td>
		<td width=99%>". Field_text("website_port-$t",$ligne["website_port"],"font-size:14px;padding:3px;width:90px")."</td>
		<td class=legend>{example}:80</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px' nowrap>{UseVirtualHosts}:</td>
		<td width=99%>". Field_checkbox("UseVirtualHosts-$t",1,$ligne["UseVirtualHosts"])."</td>
		<td class=legend>$VirtualHostLink</td>
	</tr>	
	
	<tr>
		<td class=legend style='font-size:14px' nowrap>{UseSSL}:</td>
		<td width=99%>". Field_checkbox("UseSSL-$t",1,$ligne["UseSSL"],"UseSSLCHK$t()")."</td>
		<td class=legend></td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px' nowrap>{certificate}:</td>
		<td width=99%>". Field_array_Hash($sslcertificates, "CertID-$t",strtolower($certificate),null,null,0,"font-size:14px")."</td>
		<td>$CertificateLink</td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button($btname,"AccelAddReverseSite$t()",18)."</td>
	</tr>	
	</table>
<script>
		var x_AccelAddReverseSite$t= function (obj) {
			document.getElementById('www_accel_list-$t').innerHTML='';
			var results=obj.responseText;
			if(results.length>0){alert(results);return;}
			$('#flexRT$t').flexReload();
			YahooWin4Hide();
		}		
		
		function AccelAddReverseSite$t(){
		 	var XHR = new XHRConnection();
		 	var UseSSL=0;
		 	var UseVirtualHosts=0;
		 	var enabled=0;
		 	if(document.getElementById('UseSSL-$t').checked){UseSSL=1;}
		 	if(document.getElementById('UseVirtualHosts-$t').checked){UseVirtualHosts=1;}
		 	if(document.getElementById('enabled-$t').checked){enabled=1;}
		 	XHR.appendData('ID','$ID');
			XHR.appendData('website',document.getElementById('website-$t').value);
			XHR.appendData('website_ip',document.getElementById('website_ip-$t').value);
			XHR.appendData('website_port',document.getElementById('website_port-$t').value);
			XHR.appendData('certificate',document.getElementById('CertID-$t').value);
			XHR.appendData('UseSSL',UseSSL);
			XHR.appendData('UseVirtualHosts',UseVirtualHosts);
			XHR.appendData('enabled',enabled);
			AnimateDiv('www_accel_list-$t');	
			XHR.sendAndLoad('$page', 'GET',x_AccelAddReverseSite$t);	
		}	
		
		function UseSSLCHK$t(){
			document.getElementById('CertID-$t').disabled=true;
			if(document.getElementById('UseSSL-$t').checked){
				document.getElementById('CertID-$t').disabled=false;
			}
		
		}
		
		function CheckEnabled$t(){
			document.getElementById('website-$t').disabled=true;
			document.getElementById('website_ip-$t').disabled=true;
			document.getElementById('website_port-$t').disabled=true;
			document.getElementById('CertID-$t').disabled=true;
			document.getElementById('UseSSL-$t').disabled=true;
			document.getElementById('UseVirtualHosts-$t').disabled=true;
			if(document.getElementById('enabled-$t').checked){
				document.getElementById('website-$t').disabled=false;
				document.getElementById('website_ip-$t').disabled=false;
				document.getElementById('website_port-$t').disabled=false;
				document.getElementById('CertID-$t').disabled=false;
				document.getElementById('UseSSL-$t').disabled=false;
				document.getElementById('UseVirtualHosts-$t').disabled=false;			
			}
		}
		
	UseSSLCHK$t();
	CheckEnabled$t();
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function website_table(){
	
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=400;
	$TB_WIDTH=830;
	
		
	$t=time();
	$website=$tpl->javascript_parse_text("{website}");
	$ip_address=$tpl->_ENGINE_parse_body("{ipaddr}");
	$listen_port=$tpl->_ENGINE_parse_body("{listen_port}");
	$title=$tpl->_ENGINE_parse_body("{squid_accel_websites}");
	$new_webserver=$tpl->javascript_parse_text("{new_website}");
	$apply_parameters=$tpl->javascript_parse_text("{apply_parameters}");
	$enable_all=$tpl->javascript_parse_text("{enable_all}");
	$disable_all=$tpl->javascript_parse_text("{disable_all}");
	$buttons="
	buttons : [
	
	{name: '$new_webserver', bclass: 'Add', onpress : NewWebSite$t},
	{name: '$apply_parameters', bclass: 'Reconf', onpress : ApplyParameters$t},
	{name: '$disable_all', bclass: 'Down', onpress : DisableAll$t},
	{name: '$enable_all', bclass: 'Up', onpress : EnabledAll$t},
	{name: '$apply_parameters', bclass: 'Reconf', onpress : ApplyParameters$t},
	
	],	";
	$html="
	
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?websites-list=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'UseSSL', width :31, sortable : true, align: 'center'},
		{display: '$website', name : 'website_name', width :440, sortable : true, align: 'left'},
		{display: '$ip_address', name : 'website_ip', width :141, sortable : true, align: 'left'},	
		{display: '$listen_port', name : 'website_port', width :81, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'del', width :56, sortable : false, align: 'center'},
		
	
	],
	$buttons

	searchitems : [
		{display: '$website', name : 'website_name'},
		{display: '$ip_address', name : 'website_ip'},
		

	],
	sortname: 'website_name',
	sortorder: 'asc',
	usepager: true,
	title: '<span id=\"title-$t\">$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

function NewWebSite$t(){
	Loadjs('$page?website-popup-js=yes&t=$t');
}

function ApplyParameters$t(){
	Loadjs('squid.compile.progress.php');
	}

var x_AccelAddReverseSiteDelete$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	$('#row'+mem$t).remove();
	
}	

function AccelAddReverseSiteDelete$t(ID,md){
	mem$t=md;
	var XHR = new XHRConnection();
	XHR.appendData('AccelAddReverseSiteDelete',ID);
	XHR.sendAndLoad('$page', 'GET',x_AccelAddReverseSiteDelete$t);				
}

		var x_AccelAddReverseSite$t= function (obj) {
			document.getElementById('www_accel_list-$t').innerHTML='';
			var results=obj.responseText;
			if(results.length>0){alert(results);return;}
			$('#flexRT$t').flexReload();
			YahooWin4Hide();
		}		
		
		
	var x_enableall$t= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}
		$('#flexRT$t').flexReload();
		
	}		
	function EnabledAll$t(){
		if(confirm('$enable_all ?')){
			var XHR = new XHRConnection();
			XHR.appendData('EnableAll','1');
			XHR.sendAndLoad('$page', 'POST',x_enableall$t);
		}	
	}

	function DisableAll$t(){
	if(confirm('$disable_all ?')){
		var XHR = new XHRConnection();
		XHR.appendData('EnableAll','0');
		XHR.sendAndLoad('$page', 'POST',x_enableall$t);
		}	
	}		

</script>";
	
	echo $html;
}	



function add_website(){
	if($_GET["ID"]==0){
		$sqlSource="INSERT INTO squid_accel (website_name,website_ip,website_port,`UseSSL`,`certificate`,`UseVirtualHosts`,`enabled`) 
		VALUES('{$_GET["website"]}','{$_GET["website_ip"]}','{$_GET["website_port"]}','{$_GET["UseSSL"]}','{$_GET["certificate"]}','{$_GET["UseVirtualHosts"]}','{$_GET["enabled"]}')";
	}else{
		$sqlSource="UPDATE squid_accel SET website_name='{$_GET["website"]}',
		website_ip='{$_GET["website_ip"]}',
		website_port='{$_GET["website_port"]}',
		UseSSL='{$_GET["UseSSL"]}',
		certificate='{$_GET["certificate"]}',
		UseVirtualHosts='{$_GET["UseVirtualHosts"]}',
		enabled='{$_GET["enabled"]}'
		WHERE ID={$_GET["ID"]}";
		
	}
	$q=new mysql();
	
		if(!$q->FIELD_EXISTS("squid_accel","UseSSL","artica_backup")){
			$q->QUERY_SQL("ALTER TABLE `squid_accel` ADD `UseSSL` smallint( 1 ) NOT NULL DEFAULT '0',ADD INDEX ( `UseSSL` )","artica_backup");
			if(!$q->ok){echo "$q->mysql_error\n";}
		}
		
		if(!$q->FIELD_EXISTS("squid_accel","enabled","artica_backup")){
			$q->QUERY_SQL("ALTER TABLE `squid_accel` ADD `enabled` smallint( 1 ) NOT NULL DEFAULT '1',ADD INDEX ( `enabled` )","artica_backup");
			if(!$q->ok){echo "$q->mysql_error\n";}
		}		

		if(!$q->FIELD_EXISTS("squid_accel","certificate","artica_backup")){
			$q->QUERY_SQL("ALTER TABLE `squid_accel` ADD `certificate`VARCHAR(255) NOT NULL","artica_backup");
			if(!$q->ok){echo "$q->mysql_error\n";}
		}

		if(!$q->FIELD_EXISTS("squid_accel","UseVirtualHosts","artica_backup")){
			$sql="ALTER TABLE `squid_accel` ADD `UseVirtualHosts` smallint(1) NOT NULL,ADD INDEX ( `UseVirtualHosts` )";
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){echo "$q->mysql_error\n";}
		}
		if(!$q->FIELD_EXISTS("squid_accel","virtualhosts","artica_backup")){
			$sql="ALTER TABLE `squid_accel` ADD `virtualhosts` TEXT NOT NULL";
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){echo "$q->mysql_error\n";}	
		}			
	
	
	$q->QUERY_SQL($sqlSource,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	
}

function websites_list(){
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$users=new usersMenus();
	$sock=new sockets();
	
	
	$search='%';
	$table="squid_accel";
	$database="artica_backup";
	$page=1;
	$FORCE_FILTER=null;
	
	
	if(!$q->TABLE_EXISTS($table, $database)){json_error_show("$table doesn't exists...");}
	if($q->COUNT_ROWS($table, $database)==0){json_error_show("No Websites");}

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);}	
	$UseSSL_tran=$tpl->_ENGINE_parse_body("{UseSSL}");
	while ($ligne = mysql_fetch_assoc($results)) {
		$icon="website-32.png";
		$zmd5=md5(serialize($ligne));
		$color="black";
		if($ligne["enabled"]==0){$color="#9B9999";$icon="website-32-grey.png";}
		$delete=imgsimple("delete-32.png","{delete}","AccelAddReverseSiteDelete$t({$ligne["ID"]},'$zmd5')");
		$UseSSL=$ligne["UseSSL"];
		$UseSSL_TEXT=null;
		if($UseSSL==1){
			$icon="32-key.png";
			if($ligne["enabled"]==0){$icon="32-key-grey.png";}
			$UseSSL_TEXT="<div><i style='font-size:11px;font-weight:bold'>($UseSSL_tran)</i></div>";}
		$virtz2=array();
		$virtualhosts=null;
		if($ligne["UseVirtualHosts"]==1){
			$virtz=unserialize(base64_decode($ligne["virtualhosts"]));
			while (list ($num, $cidr) = each ($virtz)){
				$virtz2[]=" <i style='font-size:10px'>$num</i>";
			}
		}
		
		if(count($virtz2)>0){
			$virtualhosts="<div>".@implode(",", $virtz2)."</div>";
		}
		
		$js="Loadjs('$MyPage?website-popup-js=yes&ID={$ligne["ID"]}&t=$t');";
		$urljsSIT="<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:16px;color:$color;text-decoration:underline'>";
		
		$data['rows'][] = array(
			'id' => "$zmd5",
			'cell' => array(
				"<span style='font-size:16px;color:$color'><img src='img/$icon'></span>",
				"<span style='font-size:16px;color:$color'>$urljsSIT{$ligne["website_name"]}</a>&nbsp;$UseSSL_TEXT$virtualhosts</span>",
				"<span style='font-size:16px;color:$color'>$urljsFAM{$ligne["website_ip"]}</a></span>",
				"<span style='font-size:16px;color:$color'>$urljs{$ligne["website_port"]}</span>",
				"<span style='font-size:16px;color:$color'>$delete</span>",
				)
			);
	}
	
	
echo json_encode($data);	
}

function enable_disable(){
	$sql="UPDATE squid_accel SET `enabled`={$_POST["EnableAll"]}";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
}
	
function del_website(){
	$sql="DELETE FROM squid_accel WHERE ID={$_GET["AccelAddReverseSiteDelete"]}";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
}

function website_options(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	$addoptions=$tpl->javascript_parse_text("{squid_parent_options}");
	$add=$tpl->javascript_parse_text("{add}");
	
	
	$q=new mysql();
	$sql="SELECT website_name FROM squid_accel WHERE ID=$ID";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
	
	$website_name=$ligne["website_name"];
	$options=$tpl->_ENGINE_parse_body("{options}");
	
	$tt=time();
	$t=$_GET["t"];

	$html="<table class='parent-options-$tt' style='display: none' id='parent-options-$tt' style='width:100%'></table>
	<script>
	var rowmem='';
	$(document).ready(function(){
	$('#parent-options-$tt').flexigrid({
	url: '$page?website-options-list=yes&t=$tt&ID=$ID&table-source=$t',
	dataType: 'json',
	colModel : [
	{display: '&nbsp;', name : 'none', width :24, sortable : true, align: 'left'},
	{display: '$options', name : 'server_port', width : 641, sortable : false, align: 'left'},
	{display: '&nbsp;', name : 'delete', width : 31, sortable : false, align: 'left'}

	],

	buttons : [
	{name: '$add', bclass: 'add', onpress : add_a_parent_option},
	],


	sortname: 'servername',
	sortorder: 'asc',
	usepager: true,
	title: '$website_name $options',
	useRp: false,
	rp: 50,
	showTableToggleBtn: false,
	width: 746,
	height: 350,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
});
function add_a_parent_option(){
	YahooWin5('450','$page?edit-proxy-parent-options=yes&ID=$ID&t=$tt&table-source=$t','$addoptions');
}

var x_AddSquidOption$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	$('#row'+rowmem).remove();
	$('#parent-options-$tt').flexReload();
	$('#flexRT$t').flexReload();
}

function DeleteSquidOption(key,ID){
	var rowmem=ID;
	var XHR = new XHRConnection();
	XHR.appendData('DeleteSquidOption',key);
	XHR.appendData('ID',$ID);
	XHR.sendAndLoad('$page', 'POST',x_AddSquidOption$t);
}


</script>


";
echo $html;
}


function website_options_items(){
	$ID=$_GET["ID"];
	$q=new mysql();
	$sql="SELECT options FROM squid_accel WHERE ID={$_GET["ID"]}";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));

	$array=unserialize(base64_decode($ligne["options"]));
	if(!is_array($array)){json_error_show("No data");}


	$data = array();
	$data['page'] = 1;
	$data['total'] = 0;
	$data['rows'] = array();

	$c=0;
	while (list($num,$val)=each($array)){
		$c++;
		$md5=md5("PPROXY-OPTION-$ID-$num");
		$data['rows'][] = array(
				'id' =>"$md5",
				'cell' => array(
						"<img src='img/arrow-right-24.png'>",
						"<strong style='font-size:14px'>$num <i>$val</i></strong>",
						imgsimple("delete-24.png","{delete}","DeleteSquidOption('$num','$md5')") )
		);
	}



	$data['page'] = 1;
	$data['total'] = $c;

	echo json_encode($data);

}

?>