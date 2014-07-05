<?php
session_start();

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class='text-error'>");
ini_set('error_append_string',"</p>");
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}


include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
$PRIV=GetPrivs();if(!$PRIV){header("location:miniadm.index.php");die();}


if(isset($_GET["content"])){content();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["websites-section"])){websites_section();exit;}
if(isset($_GET["websites-search"])){websites_search();exit;}
if(isset($_GET["report-js"])){report_js();exit;}
if(isset($_GET["report-tab"])){report_tab();exit;}
if(isset($_GET["report-popup"])){report_popup();exit;}
if(isset($_GET["report-options"])){report_options();exit;}
if(isset($_POST["report"])){report_save();exit;}
if(isset($_POST["run"])){report_run();exit;}
if(isset($_POST["csv"])){save_options_save();exit;}
if(isset($_GET["csv"])){csv_download();exit;}

if(isset($_GET["webcopy-tabs"])){webcopy_tabs();exit;}
if(isset($_GET["webcopy-section"])){webcopy_section();exit;}
if(isset($_GET["webcopy-search"])){webcopy_search();exit;}


main_page();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	
	/*if(!$_SESSION["CORP"]){
		$tpl=new templates();
		$onlycorpavailable=$tpl->javascript_parse_text("{onlycorpavailable}");
		$content=str_replace("{SCRIPT}", "<script>alert('$onlycorpavailable');document.location.href='miniadm.webstats-start.php';</script>", $content);
		echo $content;	
		return;
	}	
	*/
	
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;	
}
function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
	$users=new usersMenus();
	if($users->SQUID_INSTALLED){
		$sock=new sockets();
		$SquidActHasReverse=$sock->GET_INFO("SquidActHasReverse");
		if(!is_numeric($SquidActHasReverse)){$SquidActHasReverse=0;}
		if($SquidActHasReverse==1){
			$explainSquidActHasReverse="<div class=explain>{explain_freewebs_reverse}</div>";
		}
	}
	
	$squid=new squidbee();
	if($squid->isNGnx()){$SquidActHasReverse=1;}

	$html="
	<div class=BodyContent>
	<div style='font-size:14px'>
	<a href=\"miniadm.index.php\">{myaccount}</a>
	&nbsp;&raquo;&nbsp;<a href=\"$page\">FreeWebs</a>
	</div>
	<H1>FreeWebs</H1>
	<p>{enable_freeweb_text}</p>$explainSquidActHasReverse
	</div>
	<div id='webstats-middle-$ff' class=BodyContent></div>

	<script>
	LoadAjax('webstats-middle-$ff','$page?tabs=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function tabs(){
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$array["{websites}"]="$page?websites-section=yes";
	$array["WebCopy"]="$page?webcopy-tabs=yes";
	echo $boot->build_tab($array);
	
	
}
function websites_section(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{new_server}", "Loadjs('freeweb.edit.php?hostname=')"));
	echo $boot->SearchFormGen("servername","websites-search",null,$EXPLAIN);

}
function webcopy_tabs(){
	$boot=new boostrap_form();
	$page=CurrentPageName();	
	$array["WebCopy"]="$page?webcopy-section=yes";
	$array["{events}"]="miniadm.system.artica-events.php?section=yes&context=webcopy";
	echo $boot->build_tab($array);
	
}

function webcopy_section(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{new_website}", "YahooWin5('690','freewebs.HTTrack.php?item-id=0&t=0','WebCopy::{new_website}');"));
	echo $boot->SearchFormGen("sitename","webcopy-search",null,$EXPLAIN);

}
function GetPrivs(){
		$users=new usersMenus();
		if($users->AsSystemWebMaster){return true;}
}
function websites_search(){
	include_once(dirname(__FILE__).'/ressources/class.apache.inc');
	$vhosts=new vhosts();
	$GLOBALS["IMG_ARRAY_64"]=$vhosts->IMG_ARRAY_64;
	$searchstring=string_to_flexquery("websites-search");
	$DNS_INSTALLED=false;
	$q=new mysql();
	$sql="SELECT * FROM freeweb WHERE 1 $searchstring LIMIT 0,250";
	$results=$q->QUERY_SQL($sql,'artica_backup');
	$tpl=new templates();
	$GLOBALS["CLASS_TPL"]=$tpl;
	$boot=new boostrap_form();
	
	if($users->dnsmasq_installed){$DNS_INSTALLED=true;}
	if($users->POWER_DNS_INSTALLED){$DNS_INSTALLED=true;}
	$pdns=new pdns();
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		if($ligne["useSSL"]==1){$ssl="check-32.png";}else{$ssl="check-32-grey.png";}
		$DirectorySize=FormatBytes($ligne["DirectorySize"]/1024);
		$WebCopyID=$ligne["WebCopyID"];
		$statistics="&nbsp;";
		$exec_statistics="&nbsp;";
		$Members=null;
		$groupware=null;
		$forward_text=null;
		$error_text=null;
		$checkDNS="<img src='img/check-48.png'>";
		$checkMember="<img src='img/check-48-grey.png'>";
		$JSDNS=0;
		if($DNS_INSTALLED){
			$ip=$pdns->GetIpDN($ligne["servername"]);
			if($ip<>null){
				$checkDNS="<img src='img/check-48.png'>";
				$JSDNS=1;
			}
		}
		$ServerAlias=null;
		$Params=@unserialize(base64_decode($ligne["Params"]));
		$f=array();
		if(isset($Params["ServerAlias"])){
			while (list ($host,$num) = each ($Params["ServerAlias"]) ){
				$f[]=$host;
			}
			$ServerAlias=div_groupware("<a href=\"javascript:blur();\"
					OnClick=\"javascript:Loadjs('freeweb.edit.ServerAlias.php?servername={$ligne["servername"]}')\"
					style='text-decoration:underline'><i>".@implode(", ", $f)."</i>");
		}
	
	
	
		if($ligne["uid"]<>null){$checkMember="<img src='img/20-check.png'>";}
	
		$added_port=null;
		$icon=build_icon($ligne,$ligne["servername"]);
	

		$ServerPort=$ligne["ServerPort"];
		if($ServerPort>0){$added_port=":$ServerPort";}
		if($ligne["groupware"]<>null){$groupware=div_groupware("({{$vhosts->TEXT_ARRAY[$ligne["groupware"]]["TITLE"]}})",$ligne["enabled"]);}
	
		if($ligne["Forwarder"]==1){$forward_text=div_groupware("{www_forward} <b>{$ligne["ForwardTo"]}</b>",$ligne["enabled"]);}
		$js_edit="Loadjs('freeweb.edit.php?hostname={$ligne["servername"]}&t={$_GET["t"]}')";
	
	
		$servername_text=$ligne["servername"];
		if($servername_text=="_default_"){
			$servername_text="{all}";
			$groupware=div_groupware("({default_website})",$ligne["enabled"]);
		}else{
			
			if(!preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $ligne["servername"])){
				$checkResolv="<img src='img/20-check.png'>";
				
				if(trim($ligne["resolved_ipaddr"])==null){
					$error_text=$tpl->_ENGINE_parse_body("
					<p class=text-error style='font-size:12px;margin-top:10px'>
						{could_not_find_iphost}
					</p>");
					$checkResolv="<img src='img/20-check-grey.png'>";
				}
		
		  }	
		}
		$colorhref=null;
		if($ligne["enabled"]==0){$colorhref="color:#8C8C8C";}
	
		$href="<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('freeweb.edit.php?hostname={$ligne["servername"]}&t={$_GET["t"]}')\"
		style='font-size:13px;text-decoration:underline;font-weight:bold;$colorhref'>";
		$color="black";
				$md5S=md5($ligne["servername"]);
				$delete=icon_href("delete-48.png","FreeWebDelete('{$ligne["servername"]}',$JSDNS,'$md5S')");
	
				$sql="SELECT ID FROM drupal_queue_orders WHERE `ORDER`='DELETE_FREEWEB' AND `servername`='{$ligne["servername"]}'";
				$ligneDrup=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
				if($ligne["ID"]>0){
				$edit=imgtootltip("folder-tasks-32.png","{delete}");
				$color="#8a8a8a";
						$delete=imgtootltip("delete-48-grey.png","{delete} {scheduled}");
							
				}
				$sql="SELECT ID FROM drupal_queue_orders WHERE `ORDER`='INSTALL_GROUPWARE' AND `servername`='{$ligne["servername"]}'";
				if($ligne["ID"]>0){
				$edit=icon_href("folder-tasks-32.png","Loadjs('freeweb.edit.php?hostname={$ligne["servername"]}')");
				$color="#8a8a8a";
				$delete=icon_href("delete-48-grey.png");
				$groupware=div_groupware("({installing} {{$vhosts->TEXT_ARRAY[$ligne["groupware"]]["TITLE"]}})",$ligne["enabled"]);
					
	}
	
	
	
	
	$Params=@unserialize(base64_decode($ligne["Params"]));
	$IsAuthen=false;
	if($Params["LDAP"]["enabled"]==1){$IsAuthen=true;}
	if($Params["NTLM"]["enabled"]==1){$IsAuthen=true;}
	
	$color_orange="#B64B13";
	if($ligne["enabled"]==0){$color_orange="#8C8C8C";}
	
	if($IsAuthen){
		$Members="<span style='font-size:14px;font-weight:bold;color:$color_orange;'>&nbsp;&laquo;<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('freeweb.edit.ldap.users.php?servername={$ligne["servername"]}');\"
		style='font-size:14px;font-weight:bold;color:$color_orange;text-decoration:underline;font-style:italic'>$members_text</a>
		&nbsp;&raquo;</span>";
	}
	
	$memory="-";$requests_second="-";$traffic_second="-";$uptime=null;
	$table_name_stats="apache_stats_".date('Ym');
	$sql="SELECT * FROM $table_name_stats WHERE servername='{$ligne["servername"]}' ORDER by zDate DESC LIMIT 0,1";
	$ligneStats=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		if($ligneStats["total_memory"]>0){
			$memory=FormatBytes($ligneStats["total_memory"]/1024);
			$requests_second="{$ligneStats["requests_second"]}/s";
			$traffic_second=FormatBytes($ligneStats["traffic_second"]/1024)."/s";
						$uptime=div_groupware("{uptime}:{$ligneStats["UPTIME"]}",$ligne["enabled"]);
							
		}
	
			$groupware=$tpl->_ENGINE_parse_body($groupware);
			$forward_text=$tpl->_ENGINE_parse_body($forward_text);
			$servername_text=$tpl->_ENGINE_parse_body($servername_text);
			$ServerAlias=$tpl->_ENGINE_parse_body($ServerAlias);
			$uptime=$tpl->_ENGINE_parse_body($uptime);
			$memory=$tpl->_ENGINE_parse_body($memory);
			$requests_second=$tpl->_ENGINE_parse_body("$requests_second");
			$traffic_second=$tpl->_ENGINE_parse_body($traffic_second);
			$checkResolv=$tpl->_ENGINE_parse_body($checkResolv);
			$checkDNS=$tpl->_ENGINE_parse_body($checkDNS);
			$checkMember=$tpl->_ENGINE_parse_body($checkMember);
			$delete=$tpl->_ENGINE_parse_body($delete);
			if($WebCopyID>0){
			$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT sitename FROM httrack_sites WHERE ID=$WebCopyID","artica_backup"));
			$groupware=div_groupware("WebCopy: {$ligne2["sitename"]}",$ligne["enabled"]);
			}
	
		if($ligne["groupware"]=="UPDATEUTILITY"){
			$iconPlus="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('UpdateUtility.php?js=yes');\"><img src='img/settings-15.png' align='left'></a>";
		}
	
		$color_span="#5F5656";
		if($ligne["enabled"]==0){$color_span="#8C8C8C";}
		$compile=imgsimple("refresh-32.png",null,"FreeWebsRebuildvHostsTable('{$ligne["servername"]}')");
		$enable=Field_checkbox("enable_$md5S", 1,$ligne["enabled"],"FreeWebsEnableSite('{$ligne["servername"]}')");
	
		if($ligne["enabled"]==0){
			$requests_second="-";
				$traffic_second="-";
				$memory="-";
				$color="#8C8C8C";$color_span=$color;$icon="status_disabled.gif";$compile="&nbsp;";}
	
				$jsedit=$boot->trswitch($js_edit);
				
				$tr[]="
				<tr style='color:$color' id='row$md5S'>
					<td width=1% nowrap $jsedit><img src='img/$icon'></td>
					<td width=80% $jsedit><span style='font-size:18px;font-weight:bold'>$servername_text</span>$iconPlus$groupware$forward_text$added_port$Members$sizevg$ServerAlias$uptime$error_text</td>
					<td width=1% nowrap>$compile</td>
					<td width=1% nowrap>$enable</td>
					<td width=1% nowrap>$DirectorySize</td>
					<td width=1% nowrap>$memory</td>
					<td width=1% nowrap>$requests_second&nbsp;|&nbsp;$traffic_second</td>				
					<td width=1% nowrap><img src='img/$ssl'></td>
					<td width=1% nowrap>$delete</td>
				</tr>
				";

	
	
	}
	$t=time();
	$freeweb_compile_background=$tpl->javascript_parse_text("{freeweb_compile_background}");
	$reset_admin_password=$tpl->javascript_parse_text("{reset_admin_password}");
	$delete_freeweb_text=$tpl->javascript_parse_text("{delete_freeweb_text}");
	$delete_freeweb_dnstext=$tpl->javascript_parse_text("{delete_freeweb_dnstext}");
	echo $tpl->_ENGINE_parse_body("
	
		<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th colspan=2>{website}</th>
					<th>&nbsp;</th>
					<th>{enable}</th>
					<th>{size}</th>
					<th>{memory}</th>
					<th>Rq/s</th>
					<th>SSL</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			 <tbody>").@implode("", $tr)."</tbody></table>
<script>
var FreeWebIDMEM$t='';

	function HelpSection(){
		LoadHelp('freewebs_explain','',false);
	}

	function AddNewFreeWebServer(){
		 Loadjs('freeweb.edit.php?hostname=&force-groupware={$_GET["force-groupware"]}&t=$t')
	}
	
	function AddNewFreeWebServerZarafa(){
		YahooWin('650','freeweb.servers.php?freeweb-zarafa-choose=yes&t=$t','$choose_your_zarafa_webserver_type');
	}
	
	
	function ApacheAllstatus(){
		Loadjs('freeweb.status.php');
	}
	
	
	function FreeWebWebDavPerUsers(){
		Loadjs('freeweb.webdavusr.php?t=$t')
	}
	
	function RestoreSite(){
		Loadjs('freeweb.restoresite.php?t=$t')
	}
	
	function FreeWebsRefreshWebServersList(){
		ExecuteByClassName('SearchFunction');
	}
	
	
	var x_EmptyEvents= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		ExecuteByClassName('SearchFunction');

		
	}	
	
	var x_FreeWebsRebuildvHostsTable= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		alert('$freeweb_compile_background');
		ExecuteByClassName('SearchFunction');
		}

	
	var x_klmsresetwebpassword$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		ExecuteByClassName('SearchFunction');
	}	
	
	var x_FreeWebDelete=function (obj) {
			var results=obj.responseText;
			if(results.length>10){alert(results);return;}	
			$('#row'+FreeWebIDMEM$t).remove();
			if(document.getElementById('container-www-tabs')){	RefreshTab('container-www-tabs');}
		}	
		
		function FreeWebDelete(server,dns,md){
			FreeWebIDMEM$t=md;
			if(confirm('$delete_freeweb_text')){
				var XHR = new XHRConnection();
				if(dns==1){if(confirm('$delete_freeweb_dnstext')){XHR.appendData('delete-dns',1);}else{XHR.appendData('delete-dns',0);}}
				XHR.appendData('delete-servername',server);
    			XHR.sendAndLoad('freeweb.php', 'GET',x_FreeWebDelete);
			}
		}

	var x_FreeWebRefresh=function (obj) {
			var results=obj.responseText;
			if(results.length>10){alert(results);return;}	
			ExecuteByClassName('SearchFunction');
		}		
		
		function FreeWebAddDefaultVirtualHost(){
			var XHR = new XHRConnection();
			XHR.appendData('AddDefaultOne','yes');
    		XHR.sendAndLoad('freeweb.php', 'POST',x_FreeWebRefresh);		
		}
		
		function FreeWeCheckVirtualHost(){
			var XHR = new XHRConnection();
			XHR.appendData('CheckAVailable','yes');
    		XHR.sendAndLoad('freeweb.php', 'POST',x_FreeWebDelete);			
		}
		
		var x_RebuildFreeweb$t=function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}			
			ExecuteByClassName('SearchFunction');
		}			
		
		function RebuildFreeweb(){
			var XHR = new XHRConnection();
			XHR.appendData('rebuild-items','yes');
    		XHR.sendAndLoad('freeweb.php', 'GET',x_RebuildFreeweb$t);
		
		}

		function klmsresetwebpassword(){
		  if(confirm('$reset_admin_password ?')){
				var XHR = new XHRConnection();
				XHR.appendData('klms-reset-password','yes');
    			XHR.sendAndLoad('klms.php', 'POST',x_klmsresetwebpassword$t);
    		}		
		}
		
	function FreeWebsRebuildvHostsTable(servername){
		var XHR = new XHRConnection();
		XHR.appendData('FreeWebsRebuildvHosts',servername);
		XHR.sendAndLoad('freeweb.edit.php', 'POST',x_FreeWebsRebuildvHostsTable);
	}

</script>			 					 				 		
";	
	
	
	
}
function div_groupware($text,$enabled=1){
	$color_orange="#B64B13";
	if($enabled==0){$color_orange="#8C8C8C";}

	return $GLOBALS["CLASS_TPL"]->_ENGINE_parse_body("<div style=\"font-size:14px;font-weight:bold;font-style:italic;color:$color_orange;margin:0px;padding:0px\">$text</div>");
}

function build_icon($ligne,$servername=null){
	$icon="domain-main-64.png";
	if($ligne["groupware"]<>null){
		if(isset($GLOBALS["IMG_ARRAY_64"])){
			$icon=$GLOBALS["IMG_ARRAY_64"][$ligne["groupware"]];
		}
	}
	if(trim($ligne["resolved_ipaddr"])==null){
		if(!preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $servername)){$icon="domain-main-64-grey.png";}
	}
	return $icon;

}


function webcopy_search(){
	$tpl=new templates();
	$q=new mysql();
	$boot=new boostrap_form();
	$table="httrack_sites";
	$database='artica_backup';
	$searchstring=string_to_flexquery("webcopy-search");
	$sock=new sockets();
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring ORDER BY `sitename` LIMIT 0,250";
	$t=time();
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){senderror($q->mysql_error,1);}
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$id=$ligne["ID"];
		$articasrv=null;
		$delete=imgsimple("delete-24.png",null,"ItemDelete$t('$id')");
		if($ligne["depth"]==0){$ligne["depth"]=$tpl->_ENGINE_parse_body("{unlimited}");}
		$ligne["maxsitesize"]=FormatBytes($ligne["maxsitesize"]);
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
		$enabled=Field_checkbox("enable-$id", 1,$ligne["enabled"],"ItemEnable$t($id)");
		$run=imgsimple("64-run.png",null,"ItemRun$t($id,'imgW-$id')",null,"imgW-$id");

		$jsedit=$boot->trswitch("YahooWin5('680','freewebs.HTTrack.php?item-id=$id','WebCopy:$id');");
		
		$tr[]="
		<tr id='row$id'>
			<td width=1% nowrap $jsedit><img src='img/64-webscanner.png'></td>
			<td width=80% $jsedit><span style='font-size:18px;'>{$ligne["sitename"]}</span><div>{$ligne["workingdir"]} {$ligne["minrate"]}Kb/s MAX:{$ligne["maxsitesize"]}</div></td>
			<td width=1% nowrap $jsedit><span style='font-size:18px;'>{$ligne["size"]}</span></td>
			<td width=1% nowrap>$run</td>
			<td width=1% nowrap>$enabled</td>
			<td width=1% nowrap>$delete</td>
		</tr>
		";		
		

		
	}
	
	echo $tpl->_ENGINE_parse_body("
	
		<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th colspan=2>{websites}</th>
					<th>{size}</th>
					<th>{run}</th>
					<th>&nbsp;</th>
					<th>&nbsp;</th>			
				</tr>
			</thead>
			 <tbody>").@implode("", $tr)."</tbody></table>
				 <script>
var mem$t='';
function ItemShow$t(id){
	YahooWin5('670','freewebs.HTTrack.php?item-id='+id+'&t=$t','WebCopy:'+id);
}

function ItemEvents$t(){
	Loadjs('squid.update.events.php?table=system_admin_events&category=webcopy');
}
function ItemHelp$t(){
	s_PopUpFull('http://mail-appliance.org/index.php?cID=263','1024','900');
}

var x_ItemDelete$t=function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}	
	$('#row'+mem$t).remove();
}

function ItemDelete$t(id){
	mem$t=id;
	var XHR = new XHRConnection();
	XHR.appendData('delete-item',id);
    XHR.sendAndLoad('freewebs.HTTrack.php', 'POST',x_ItemDelete$t);	
	}

var x_ItemExec$t=function (obj) {
	var results=obj.responseText;
	if (results.length>3){alert(results);return;}
	ExecuteByClassName('SearchFunction');
}
var x_ItemExec2$t=function (obj) {
	var results=obj.responseText;
	if (results.length>3){alert(results);}
	ExecuteByClassName('SearchFunction');
}
var x_ItemSilent$t=function (obj) {
	var results=obj.responseText;
	if (results.length>3){alert(results);return;}
	
}
function ItemExec$t(){
	var XHR = new XHRConnection();
	XHR.appendData('exec','yes');
    XHR.sendAndLoad('freewebs.HTTrack.php', 'POST',x_ItemExec2$t);	
}
function ItemRun$t(ID,imgid){
	mem$t=imgid;
	var XHR = new XHRConnection();
	XHR.appendData('item-run',ID);
	if(document.getElementById(imgid)){
		document.getElementById(imgid).src='/ajax-menus-loader.gif';
	}
    XHR.sendAndLoad('freewebs.HTTrack.php', 'POST',x_ItemExec2$t);	
}
function ItemEnable$t(id){
	var value=0;
	if(document.getElementById('enable-'+id).checked){value=1;}
	var XHR = new XHRConnection();
	XHR.appendData('item-enable',id);
	XHR.appendData('value',value);
    XHR.sendAndLoad('freewebs.HTTrack.php', 'POST',x_ItemSilent$t);
}			 		
</script>";	
}
