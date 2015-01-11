<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["now-search"])){popup_list();exit;}
if(isset($_POST["pattern"])){SaveBlks();exit;}
if(isset($_POST["delete-pattern"])){delete();exit;}
if(isset($_POST["enable-pattern"])){enable();exit;}
if(isset($_POST["AddDefaultMimeType-white"])){AddDefaultMimeType_white();exit;}


if(isset($_GET["squid-groups"])){squid_groups_popup();exit;}
if(isset($_GET["squid-groups-popup-list"])){squid_groups_popup_list();exit;}
if(isset($_GET["squid-groups-popup-selected"])){squid_groups_popup_selected();exit;}

if(isset($_GET["squid-UserAgent"])){squid_useragent();exit;}


js();


function js(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$title="{black_ip_group}";
	if($_GET["blk"]==1){$title="{white_ip_group}";}
	if($_GET["blk"]==2){$title="{white_wwww}";}
	if($_GET["blk"]==4){$title="{notcaching_websites}";}
	if($_GET["blk"]==5){$title="{whitelist}::{browser}";}
	if($_GET["blk"]==6){$title="{white_mime_type}";}		

	
	$title=$tpl->_ENGINE_parse_body($title);
	$title_table=urlencode($title);
	$html="YahooWin4('650','$page?popup=yes&blk={$_GET["blk"]}&table-title=$title_table','$title')";
	echo $html;
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$pattern=$tpl->_ENGINE_parse_body("{pattern}");
	$type=$tpl->_ENGINE_parse_body("{sourcetype}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$title="{black_ip_group}";
	if($_GET["blk"]==1){$title="{white_ip_group}";}	
	$AddMAC=$tpl->_ENGINE_parse_body("{ComputerMacAddress}");
	$AddWWW=$tpl->_ENGINE_parse_body("{add_website}");
	$squidGroup=$tpl->_ENGINE_parse_body("{SquidGroup}");
	$title=$tpl->_ENGINE_parse_body($title);
	$no_acl_arp_text=$tpl->javascript_parse_text("{no_acl_arp_text}");
	$users=new usersMenus();
	$SQUID_ARP_ACL_ENABLED=1;
	if(!$users->SQUID_ARP_ACL_ENABLED){$SQUID_ARP_ACL_ENABLED=0;}
	$ComputerMacAddress=$tpl->javascript_parse_text("{ComputerMacAddress}");
	$addr=$tpl->javascript_parse_text("{addr}");
	$squid_ask_domain=$tpl->javascript_parse_text("{squid_ask_domain}");
	$acl_src_text=$tpl->javascript_parse_text("{acl_src_text}");
	$add_squid_uderagent_explain=$tpl->javascript_parse_text("{add_squid_uderagent_explain}");
	$AddUserAgent=$tpl->_ENGINE_parse_body("{new_useragent_pattern}");
	$new_mime_type=$tpl->_ENGINE_parse_body("{new_mime_type}");
	if($SQUID_ARP_ACL_ENABLED==1){if($users->DANSGUARDIAN_INSTALLED){$squid=new squidbee();if($squid->enable_dansguardian==1){$no_acl_arp_text=$tpl->javascript_parse_text("{no_arp_acl_dansguardian}");$SQUID_ARP_ACL_ENABLED=0;}}}
	$add_mime_type_explain=$tpl->javascript_parse_text("{add_mime_type_white_explain}");
	$add_default_mimetypes=$tpl->_ENGINE_parse_body("{default_rules}");
	$apply=$tpl->javascript_parse_text("{apply}");
	
	$buttons="
	buttons : [
	{name: '$AddMAC', bclass: 'add', onpress : AddByMac},
	{name: '$addr', bclass: 'add', onpress : AddByIPAdr},
	{name: '$squidGroup', bclass: 'add', onpress : AddBySquidGroup},
	{name: '$apply', bclass: 'apply', onpress : SquidBuildNow$t},
	],";		
		
	if(($_GET["blk"]==2) OR ($_GET["blk"]==3)){
		$buttons="
		buttons : [
		{name: '$AddWWW', bclass: 'add', onpress : AddByWebsite},
		{name: '$squidGroup', bclass: 'add', onpress : AddBySquidGroupWWW},
		{name: '$apply', bclass: 'apply', onpress : SquidBuildNow$t},
		],";
	}

if($_GET["blk"]==4){		
	$buttons="
		buttons : [
		{name: '$AddWWW', bclass: 'add', onpress : AddByWebsite},
		{name: '$squidGroup', bclass: 'add', onpress : AddBySquidGroupWWW},
		{name: '$apply', bclass: 'apply', onpress : SquidBuildNow$t},
		],";
	}

if($_GET["blk"]==5){
	$explain=$tpl->_ENGINE_parse_body("{ban_browsers_explain2}");		
	$buttons="
		buttons : [
		{name: '$AddUserAgent', bclass: 'add', onpress : AddByUserAgent},
		{name: '$apply', bclass: 'apply', onpress : SquidBuildNow$t},
		],";
	}	
	
if($_GET["blk"]==6){
	$explain=$tpl->_ENGINE_parse_body("{exlude_mimetype_explain}");		
	$buttons="
		buttons : [
		{name: '$new_mime_type', bclass: 'add', onpress : AddByMimeType},
		{name: '$add_default_mimetypes', bclass: 'add', onpress : AddDefaultMimeType},
		{name: '$apply', bclass: 'apply', onpress : SquidBuildNow$t},
		],";
	}	
	
if($explain<>null){$explain="<div class=text-info style='font-size:16px'>$explain</div>";}	
$html="
$explain
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?now-search=yes&blk={$_GET["blk"]}',
	dataType: 'json',
	colModel : [
		{display: '$type', name : 'PatternType', width : 136, sortable : false, align: 'left'},	
		{display: '$pattern', name : 'pattern', width :150, sortable : true, align: 'left'},
		{display: '$description', name : 'description', width :210, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'enabled', width : 25, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'delete', width : 44, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
		{display: '$pattern', name : 'pattern'},
		{display: '$description', name : 'description'}
		],
	sortname: 'pattern',
	sortorder: 'asc',
	usepager: true,
	title: '{$_GET["table-title"]}',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 350,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

	var x_AddByMac= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		FlexReloadblk();
		if(document.getElementById('rules-toolbox')){RulesToolBox();}
	}

	function FlexReloadblk(){
		$('#flexRT$t').flexReload();
	}
	
function SquidBuildNow$t(){
	Loadjs('squid.compile.php');
}	

function AddByMac(){
	var SQUID_ARP_ACL_ENABLED=$SQUID_ARP_ACL_ENABLED;
	if(SQUID_ARP_ACL_ENABLED==0){alert('$no_acl_arp_text');return;}
	var mac=prompt('$ComputerMacAddress:');
	if(mac){
		var XHR = new XHRConnection();
		XHR.appendData('pattern',mac);
		XHR.appendData('PatternType',1);
		XHR.appendData('blk',{$_GET["blk"]});
		XHR.sendAndLoad('$page', 'POST',x_AddByMac);		
	}
	
}

function AddByMimeType(){
	var mac=prompt('$add_mime_type_explain');
	if(mac){
		var XHR = new XHRConnection();
		XHR.appendData('pattern',mac);
		XHR.appendData('PatternType',1);
		XHR.appendData('blk',{$_GET["blk"]});
		XHR.sendAndLoad('$page', 'POST',x_AddByMac);		
	}
}

function AddDefaultMimeType(){
		var XHR = new XHRConnection();
		XHR.appendData('AddDefaultMimeType-white','yes');
		XHR.sendAndLoad('$page', 'POST',x_AddByMac);	
}

function AddByUserAgent(){
	YahooWin5('550','$page?squid-UserAgent=yes&blk={$_GET["blk"]}','$AddUserAgent')
}

function AddBySquidGroup(){
	YahooWin5('550','$page?squid-groups=yes&blk={$_GET["blk"]}','$squidGroup');

}

function AddBySquidGroupWWW(){
	YahooWin5('550','$page?squid-groups=yes&blk={$_GET["blk"]}','$squidGroup');
}


function AddByIPAdr(){
	var mac=prompt('$addr:$acl_src_text');
	if(mac){
		var XHR = new XHRConnection();
		XHR.appendData('pattern',mac);
		XHR.appendData('PatternType',0);
		XHR.appendData('blk',{$_GET["blk"]});
		XHR.sendAndLoad('$page', 'POST',x_AddByMac);		
	}
}
function AddByWebsite(){
	var mac=prompt('$AddWWW:$squid_ask_domain');
	if(mac){
		var XHR = new XHRConnection();
		XHR.appendData('pattern',mac);
		XHR.appendData('PatternType',0);
		XHR.appendData('blk',{$_GET["blk"]});
		XHR.sendAndLoad('$page', 'POST',x_AddByMac);		
	}
	
}


function BlksProxyDelete(pattern){
		var XHR = new XHRConnection();
		XHR.appendData('delete-pattern',pattern);
		XHR.setLockOff();
		XHR.sendAndLoad('$page', 'POST',x_AddByMac);
}

function BlksProxyEnable(pattern,id){
		var XHR = new XHRConnection();
		if(document.getElementById(id).checked){XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
		XHR.appendData('enable-pattern',pattern);
		XHR.setLockOff();
		XHR.sendAndLoad('$page', 'POST');
}

</script>

";	
	echo $html;
	
}

function delete(){
	$q=new mysql_squid_builder();
	$sql="DELETE FROM webfilters_blkwhlts WHERE zmd5='{$_POST["delete-pattern"]}'";
	writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}		
	$sock=new sockets();
	$sock->getFrameWork("squid.php?quick-ban=yes");	
}

function squid_useragent(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();	
	$html="
	<div style='font-size:14px' class=text-info>{add_squid_uderagent_explain}</div>
	
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{pattern}:</td>
		<td>". Field_text("squid_useragent",null,"font-size:16px;padding:3px")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr><br>". button("{add}","SaveAddUserAgent()",16)."</td>
	</tr>
	</table>
	<script>
	var x_SaveAddUserAgent= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		FlexReloadblk();
		YahooWin5Hide();
		if(document.getElementById('rules-toolbox')){RulesToolBox();}
	}	
	
	
function SaveAddUserAgent(){
		var XHR = new XHRConnection();
		var pp=encodeURIComponent(document.getElementById('squid_useragent').value);	
		XHR.appendData('pattern',pp);
		XHR.appendData('EncodeUri',1);
		XHR.appendData('PatternType',3);
		XHR.appendData('blk',{$_GET["blk"]});
		XHR.sendAndLoad('$page', 'POST',x_SaveAddUserAgent);		
	
}	
</script>";
	echo $tpl->_ENGINE_parse_body($html);
	
}


function enable(){
	$q=new mysql_squid_builder();
	$sql="UPDATE webfilters_blkwhlts SET enabled={$_POST["enabled"]} WHERE zmd5='{$_POST["enable-pattern"]}'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}	
	$sock=new sockets();
	$sock->getFrameWork("squid.php?quick-ban=yes");	
		
	
}

function squid_groups_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
		
	$html="
	<div id='formulaire-choix-groupe-proxy'></div>
	
	
	<script>
		function RefreshFormulaireChoixGroupeProxy(){
			LoadAjax('formulaire-choix-groupe-proxy','$page?squid-groups-popup-list=yes&blk={$_GET["blk"]}');
		}
		
		RefreshFormulaireChoixGroupeProxy();
	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function squid_groups_popup_list(){

	$tpl=new templates();
	$page=CurrentPageName();
	$GLOBALS["GroupType"]["src"]="{addr}";
	$GLOBALS["GroupType"]["arp"]="{ComputerMacAddress}";
	$GLOBALS["GroupType"]["dstdomain"]="{dstdomain}";
	$GLOBALS["GroupType"]["proxy_auth"]="{members}";	
	$t=time();
	$sql="SELECT * FROM webfilters_sqgroups WHERE enabled=1 ORDER BY GroupName";
	$q=new mysql_squid_builder();
	$results = $q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		$H[$ligne["ID"]]=$ligne["GroupName"]." (".$tpl->_ENGINE_parse_body($GLOBALS["GroupType"][$ligne["GroupType"]]).")";
	}
	$H[null]="{select}";
	$html="<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend>{SquidGroup}:</td>
		<td>". Field_array_Hash($H, "SquidGroup",null,"ProxyGroupSelected()","style:font-size:14px")."</td>
		<td width=1%>". imgtootltip("plus-24.png","{add} {SquidGroup}","Loadjs('squid.acls.groups.php?AddGroup-js=yes')")."</td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button("{add}","SaveSquidGroupAdd()",14)."<div id='proxy-group-select-$t'></div></td>
	</tr>
	</tbody>
	</table>
	
	
	
	
	<script>
function SaveSquidGroupAdd(){
		var XHR = new XHRConnection();
		XHR.appendData('pattern',document.getElementById('SquidGroup').value);
		XHR.appendData('PatternType',2);
		XHR.appendData('blk',{$_GET["blk"]});
		XHR.sendAndLoad('$page', 'POST',x_SaveSquidGroupAdd);		
	}
	
function ProxyGroupSelected(){
	var groupid=document.getElementById('SquidGroup').value;
	LoadAjaxTiny('proxy-group-select-$t','$page?squid-groups-popup-selected='+groupid);

}
	
	var x_SaveSquidGroupAdd= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		FlexReloadblk();
		YahooWin5Hide();
		if(document.getElementById('rules-toolbox')){RulesToolBox();}
	}
</script>	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function squid_groups_popup_selected(){
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_sqgroups WHERE ID='{$_GET["squid-groups-popup-selected"]}'"));
	$GroupName=utf8_encode($ligne["GroupName"]);
	$html="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.acls.groups.php?AddGroup-js=yes&ID={$_GET["squid-groups-popup-selected"]}')\"
	style='font-size:14px;text-decoration:underline'>{apply}: $GroupName</a>";
	echo $tpl->_ENGINE_parse_body($html);
}


function SaveBlks(){
	$tpl=new templates();
	$restartfilters=false;
	$blk="{blacklist}";
	if($_POST["blk"]==1){$blk="{whitelist}";$restartfilters=true;}
	if($_POST["blk"]==2){$blk="{whitelist}";$restartfilters=true;}
	if($_POST["blk"]==4){$blk="{not_caching}";}
	if($_POST["blk"]==5){$blk="{whitelist}";}
	if($_POST["blk"]==6){$blk="{whitelist}";}
	
	if($_POST["EncodeUri"]==1){
		$_POST["pattern"]=url_decode_special_tool($_POST["pattern"]);
	}
	
	
	if($_POST["PatternType"]==1){$description="$blk {ComputerMacAddress} {$_POST["pattern"]}";}
	if($_POST["PatternType"]==0){$description="$blk {addr} {$_POST["pattern"]}";}
	if($_POST["PatternType"]==3){$description="$blk {browser} {$_POST["pattern"]}";}
	if($description==null){if($_POST["blk"]>1){$description="$blk {website} {$_POST["pattern"]}";}}
	if($_POST["blk"]==6){$description="{whitelist}:{BannedMimetype}";}
	
	if($_POST["PatternType"]==0){
		if(($_POST["blk"]==2) OR ($_POST["blk"]==4)){
			if(preg_match("#^www\.(.+)#", $_POST["pattern"],$re)){$_POST["pattern"]=$re[1];}
			$_POST["pattern"]=trim(strtolower($_POST["pattern"]));
		}
	}
	
	$zmd5=md5(serialize($_POST));
	$description=mysql_escape_string2($description);
	$_POST["pattern"]=mysql_escape_string2(trim($_POST["pattern"]));
	
	
	$sql="INSERT IGNORE INTO webfilters_blkwhlts (zmd5,description,enabled,PatternType,blockType,pattern)
	VALUES('$zmd5','$description',1,{$_POST["PatternType"]},{$_POST["blk"]},'{$_POST["pattern"]}')";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){if(strpos($q->mysql_error, "doesn't exist")>0){$q->BuildTables();}}
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	
	
	$sock=new sockets();
	$sock->getFrameWork("squid.php?quick-ban=yes");	
	
	
	
}

function AddDefaultMimeType_white(){
	$f["application/vnd.ms.wms-hdr.asfv1"]=true;
	$f["application/vnd.rn-realmedia"]=true;
	$f["application/x-mms-framed"]=true;
	$f["application/x-shockwave-flash"]=true;
	$f["application/x-mms-framed"]=true;
	$f["application/sdp"]=true;
	$f["application/x-rtsp-packetpair"]=true;
	$f["application/x-wms-Logconnectstats"]=true;
	$f["application/x-mif"]=true;
	$f["application/x-lisp"]=true;
	$f["video/x-shockwave-flash"]=true;
	$f["video/x-mpeg"]=true;
	$f["audio/x-wav"]=true;
	$f["audio/wav"]=true;
	$f["audio/microsoft-wave"]=true;
	$f["audio/x-mpeg"]=true;
	$f["audio/mpeg"]=true;
	$f["audio/mpeg3"]=true;
	$f["audio/mid"]=true;
	$f["x-music/x-midi"]=true;
	$f["video/x-flv"]=true;
	$f["video/mpeg"]=true;
	$f["video/quicktime"]=true;
	$f["video/x-msvideo"]=true;
	$f["video/avi"]=true;
	$f["video/x-ms-asf"]=true;
	$f["video/x-ms-wmv"]=true;
	$f["image/x-icon"]=true;
	$f["image/jpeg"]=true;
	$f["image/gif"]=true;
	$f["image/x-xbitmap"]=true;
	$f["image/png"]=true;
	$f["image/vnd.microsoft.icon"]=true;	
		
	
	$prefix="INSERT IGNORE INTO webfilters_blkwhlts (description,enabled,PatternType,blockType,pattern) VALUES ";
	
	while (list ($num, $val) = each ($f) ){	
		$tt[]="('{whitelist}:{BannedMimetype}',1,1,6,'$num')";
	}


	$sql=$prefix.@implode(",",$tt);
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){if(strpos($q->mysql_error, "doesn't exist")>0){$q->BuildTables();}}
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$sock=new sockets();
	$users=new usersMenus();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}	
	
	if($EnableWebProxyStatsAppliance==1){
		$sock->getFrameWork("squid.php?notify-remote-proxy=yes");
		return;
	}	
	
	
}


function popup_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	
	$search='%';
	$table="webfilters_blkwhlts";
	$page=1;
	$FORCE_FILTER="AND blockType={$_GET["blk"]}";
	
	if($q->COUNT_ROWS($table)==0){
		writelogs("$table, no row",__FILE__,__FUNCTION__,__FILE__,__LINE__);
		$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();
		echo json_encode($data);
		return ;
	}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){ json_error_show($q->mysql_error); }
	if(mysql_num_rows($results)==0){json_error_show("no data"); }
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	
	
	//if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	
	$PatternTypeH[1]="{ComputerMacAddress}";
	$PatternTypeH[0]="{addr}";
	$PatternTypeH[2]="{SquidGroup}";
	$PatternTypeH[3]="{browser}";
	$PatternTypeH[6]="{BannedMimetype}";
	
	
	
	
	$GLOBALS["GroupType"]["src"]="{addr}";
	$GLOBALS["GroupType"]["arp"]="{ComputerMacAddress}";
	$GLOBALS["GroupType"]["dstdomain"]="{dstdomain}";
	$GLOBALS["GroupType"]["proxy_auth"]="{members}";
	$GLOBALS["GroupType"]["browser"]="{browser}";		
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$id=md5($ligne["pattern"]);
		$PatternTypeInt=$ligne["PatternType"];
		$PatternType=$tpl->_ENGINE_parse_body($PatternTypeH[$ligne["PatternType"]]);
		if($ligne["PatternType"]==0){$PatternType=$tpl->_ENGINE_parse_body("{addr}");}
		if($PatternType==null){
			if($_GET["blk"]>1){$PatternType=$tpl->_ENGINE_parse_body("{website}");}
		}
		
		if($PatternTypeInt==0){
			if($_GET["blk"]==2){$PatternType=$tpl->_ENGINE_parse_body("{website}");}
		}
		
		if($PatternTypeInt==1){
			if($_GET["blk"]==6){$PatternType=$tpl->_ENGINE_parse_body("{BannedMimetype}");}
		}		
		
		$PatternAffiche=$ligne["pattern"];
		$description=$tpl->_ENGINE_parse_body($ligne["description"]);
		
		
		if($ligne["PatternType"]==2){
			$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT GroupName,GroupType FROM webfilters_sqgroups WHERE ID='{$ligne["pattern"]}'"));
			$description=$tpl->_ENGINE_parse_body($GLOBALS["GroupType"][$ligne2["GroupType"]]);
			$PatternAffiche=$ligne2["GroupName"];
		}
		
		if($ligne["zmd5"]==null){$q->QUERY_SQL("UPDATE webfilters_blkwhlts SET zmd5='$id' WHERE pattern='". mysql_escape_string2($ligne["pattern"])."'");$ligne["zmd5"]=$id;}
		$md5=$ligne["zmd5"];
		
		$delete=imgtootltip("delete-32.png","{delete} {$ligne["pattern"]}","BlksProxyDelete('$md5')");
		$enable=Field_checkbox($id,1,$ligne["enabled"],"BlksProxyEnable('$md5','$id')");	
		
	$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => array("<span style='font-size:14px'>$PatternType</span>"
		,"<span style='font-size:14px'>$PatternAffiche</span>",
		"<span style='font-size:14px'>$description</span>",$enable,$delete )
		);
	}
	
	
echo json_encode($data);		

}
