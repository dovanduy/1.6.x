<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.cyrus.inc');
	include_once('ressources/class.cron.inc');
	
	$users=new usersMenus();
	if(!$users->AsMailBoxAdministrator){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["popup-status"])){popup_status();exit;}
	if(isset($_GET["services-status"])){services_status();exit;}
	if(isset($_GET["popup-www"])){popup_www();exit;}
	if(isset($_GET["popup-mailbox"])){popup_mailbox_tabs();exit;}
	if(isset($_GET["popup-mailbox-section"])){popup_mailbox();exit;}
	if(isset($_GET["mailboxes"])){mailbox_list();exit;}
	if(isset($_GET["mailboxes-list"])){mailbox_items();exit;}
	if(isset($_POST["mailboxes-reload"])){mailbox_items_reload();exit;}
	if(isset($_GET["popup-license"])){popup_license();exit;}
	if(isset($_POST["ZarafaHashRebuild"])){popup_mailbox_rebuild();exit;}
	if(isset($_POST["zlicense"])){save_license();exit;}
	if(isset($_GET["zarafa-box"])){ZarafaBox();exit;}
js();
function js(){
	$page=CurrentPageName();
	if(isset($_GET["font-size"])){$fontsize="&font-size={$_GET["font-size"]}";}
	echo "$('#BodyContent').load('$page?popup=yes$fontsize');";
	
	
	
}

function popup_www(){
	
	$html="
	<div id='zarafa-inline-config'></div>
	<script>
		Loadjs('zarafa.web.php?in-line=yes');
	</script>
	";
	
	echo $html;
	
	
}

function popup(){
	$q=new mysql();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$EnableZarafaMulti=$sock->GET_INFO("EnableZarafaMulti");
	$ZarafaDedicateMySQLServer=$sock->GET_INFO("ZarafaDedicateMySQLServer");
	if(!is_numeric($ZarafaDedicateMySQLServer)){$ZarafaDedicateMySQLServer=0;}	
	$users=new usersMenus();
	if(!is_numeric($EnableZarafaMulti)){$EnableZarafaMulti=0;}
	
	if($EnableZarafaMulti>0){
		$array["popup-multi"]="{multiple_zarafa_instances}";
	}
	
	$array["popup-status"]="{status}";
	$array["popup-www"]="{parameters}";
	
	//if($users->APP_ZARAFADB_INSTALLED){
	if($ZarafaDedicateMySQLServer==1){
		$array["popup-zarafadb"]="{database}";
	}
	
	if($q->COUNT_ROWS("zarafa_orphaned", "artica_backup")>0){
		$array["popup-orphans"]="{orphans}";
	}
	
	if(isset($_GET["font-size"])){$fontsize="font-size:{$_GET["font-size"]}px;";$adduri="&font-size={$_GET["font-size"]}";$adduri2="?font-size={$_GET["font-size"]}";}
	
	//$array["popup-instances"]="{multiple_webmail}";
	$array["popup-mailbox"]="{mailboxes}";
	$array["popup-license"]="{zarafa_license}";
	$array["tools"]="{tools}";
	$array["backup"]="{backup}";
	
	if(count($array)>7){$fontsize="font-size:12px"; }
	
	while (list ($num, $ligne) = each ($array) ){
		
		
		if($num=="popup-zarafadb"){
			$html[]="<li><a href=\"zarafa.database.php$adduri2\"><span>$ligne</span></a></li>\n";
			continue;
		}		
		
	
		if($num=="popup-multi"){
			$html[]="<li><a href=\"zarafa.multi.php$adduri2\"><span>$ligne</span></a></li>\n";
			continue;
		}		
		
		if($num=="popup-mysql"){
			$html[]="<li><a href=\"zarafa.mysql.php$adduri2\"><span>$ligne</span></a></li>\n";
			continue;
		}

		if($num=="tools"){
			$html[]="<li><a href=\"zarafa.tools.php$adduri2\"><span>$ligne</span></a></li>\n";
			continue;
		}	

		if($num=="popup-orphans"){
			$html[]="<li><a href=\"zarafa.orphans.php$adduri2\"><span>$ligne</span></a></li>\n";
			continue;
		}

		if($num=="popup-instances"){
			$html[]="<li><a href=\"zarafa.freewebs.php$adduri2\"><span>$ligne</span></a></li>\n";
			continue;
		}	

		if($num=="backup"){
			$html[]="<li><a href=\"zarafa.backup.php$adduri2\"><span>$ligne</span></a></li>\n";
			continue;
		}			
		
		$html[]="<li><a href=\"$page?$num=yes$adduri\"><span>$ligne</span></a></li>\n";
			
		}	
	$tabwidth=759;
	if(is_numeric($_GET["tabwith"])){$tabwitdh=$_GET["tabwith"];}
	$tab="<div id=main_config_zarafa style='width:{$tabwitdh}px;$fontsize'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_zarafa').tabs();
			
			
			});
			QuickLinkShow('quicklinks-APP_ZARAFA');
		</script>";		
	
	
	echo $tpl->_ENGINE_parse_body($tab);
	
}

function popup_status(){
	
	if(GET_CACHED(__FILE__, __FUNCTION__)){return;}
	
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$datas=base64_decode($sock->getFrameWork('cmd.php?Global-Applications-Status=yes'));
	
	$ini=new Bs_IniHandler();
	$ini->loadString($datas);
	$users=new usersMenus();
	if($users->YAFFAS_INSTALLED){
		$yaffas="<div class=explain>{APP_YAFFAS_TEXT}</div>";
	}
	if($ini->_params["APP_ZARAFA"]["master_version"]==null){unset($ini->_params["APP_ZARAFA"]["master_version"]);}
	
	if(!isset($ini->_params["APP_ZARAFA"]["master_version"])){
		
		$datas=base64_decode($sock->getFrameWork('cmd.php?zarafa-status=yes'));
		$ini->loadString($datas);
	}
	

	
	
	$html="
	<table style='width:100%'>
	<tr>
		<td valign='top' width=1%><span id='zarafa-box'></span></td>
		<td valign='top' width=99%>
			<H3 style='font-size:22px;font-weight:bold'>{APP_ZARAFA} v{$ini->_params["APP_ZARAFA"]["master_version"]}</H3>
			<div id='zarafa-error' style='color:#FB0808;font-weight:bold;font-size:14px'></div>
			<div class=explain>{APP_ZARAFA_TEXT}</div>$yaffas
			<table style='width:100%'>
			<tr>
				<td width=1%><img src='img/arrow-right-24.png'></td>
				<td nowrap>
					<a href=\"javascript:blur();\" 
					OnClick=\"javascript:Loadjs('postfix.events.new.php?js-zarafa=yes');\" 
					style='font-size:13px;text-decoration:underline'>{APP_ZARAFA}:{events}</a>
				</td>
			</tr>
			<tr>
				<td width=1%><img src='img/arrow-right-24.png'></td>
				<td nowrap><a href=\"javascript:blur();\" 
				OnClick=\"javascript:Loadjs('zarafa.audit.logs.php');\" 
				style='font-size:13px;text-decoration:underline'>{APP_ZARAFA}:{audit}</a></td>
			</tr>	
			<tr>
				<td width=1%><span id='mysql-dedie-img'></span></td>
				<td nowrap><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('ZarafaDB.wizard.php')\" id='mysql-dedie-text' style='font-size:13px;text-decoration:underline'></a></td>
			</tr>				
		</table>
		</td>
	</tr>
	</table>
	<div id='zarafa-services-status' style='width:100%;'></div>
	
	
	<script>
		LoadAjax('zarafa-services-status','$page?services-status=yes');
		LoadAjaxTiny('zarafa-box','$page?zarafa-box=yes');
	</script>
	";
	$html=$tpl->_ENGINE_parse_body($html);
	SET_CACHED(__FILE__, __FUNCTION__, null, $html);
	echo $html;
}

function ZarafaBox(){
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	$mysqldimg="ok24-grey.png";
	$mysqltext=$mysqltext. "{APP_ZARAFA_DB} {not_installed}";
	$ZarafaRemoteMySQLServer=$sock->GET_INFO("ZarafaMySQLServiceType");
	$ZarafaDedicateMySQLServer=$sock->GET_INFO("ZarafaDedicateMySQLServer");
	if(!is_numeric($ZarafaDedicateMySQLServer)){$ZarafaDedicateMySQLServer=0;}
	
	
	if($ZarafaDedicateMySQLServer==1){
		$mysqldimg="ok24.png";
		$mysqltext="{APP_ZARAFADB} {installed}";
		if($ZarafaRemoteMySQLServer<>3){
			$mysqldimg="warning24.png";
			$mysqltext=$mysqltext. "({not_used})";
		}
		
	}
	
	
	$mysqltext=$tpl->javascript_parse_text($mysqltext);
	
	
	$zarafabox="zarafa-box-256.png";
	
	$USERSARR=unserialize(base64_decode($sock->getFrameWork("zarafa.php?users-count=yes")));
	if(!$USERSARR["STATUS"]){
		$zarafabox="zarafa-box-red-256.png";
		$zarafaerror=$tpl->javascript_parse_text($USERSARR["ERROR"]);
		$zarafaerror=" document.getElementById('zarafa-error').innerHTML='$zarafaerror';";
	}else{
		$zarafaerror=$tpl->javascript_parse_text("{license2}: {$USERSARR["ACTIVE"]["USED"]}/{$USERSARR["ACTIVE"]["ALLOWED"]}");
		$zarafaerror=" document.getElementById('zarafa-error').innerHTML='<span style=\"color:black\">$zarafaerror</span>';";
		
	}
	
	echo "<img src='img/$zarafabox'>
	<script>
	document.getElementById('mysql-dedie-img').innerHTML='<img src=img/$mysqldimg>';
	document.getElementById('mysql-dedie-text').innerHTML='$mysqltext';
		 $zarafaerror
	</script>
	";
	
}

function services_status(){
	$page=CurrentPageName();
	$tpl=new templates();		
	$sock=new sockets();
	$users=new usersMenus();
	
	$array[]="APP_ZARAFA";
	$array[]="APP_ZARAFA_SERVER2";
	$array[]="APP_ZARAFA_DB";
	$array[]="APP_ZARAFA_GATEWAY";
	$array[]="APP_ZARAFA_SPOOLER";
	$array[]="APP_ZARAFA_WEB";
	$array[]="APP_ZARAFA_MONITOR";
	$array[]="APP_ZARAFA_DAGENT";
	$array[]="APP_ZARAFA_ICAL";
	if($users->ZARAFA_INDEXER_INSTALLED){
		$APP_ZARAFA_INDEXER=$sock->GET_INFO("EnableZarafaIndexer");
		if(!is_numeric($APP_ZARAFA_INDEXER)){$APP_ZARAFA_INDEXER=0;}		
		if($APP_ZARAFA_INDEXER==1){$array[]="APP_ZARAFA_INDEXER";}
		
	}
	$array[]="APP_ZARAFA_LICENSED";
	if($users->ZARAFA_SEARCH_INSTALLED){
		$APP_ZARAFA_SEARCH=$sock->GET_INFO("EnableZarafaSearch");
		if(!is_numeric($APP_ZARAFA_SEARCH)){$APP_ZARAFA_SEARCH=0;}
		if($APP_ZARAFA_SEARCH==1){$array[]="APP_ZARAFA_SEARCH";}
		
	}
	$array[]="APP_YAFFAS";

	
	$ini=new Bs_IniHandler();
	$datas=base64_decode($sock->getFrameWork('cmd.php?zarafa-status=yes'));
	$ini->loadString($datas);
	
	while (list ($num, $ligne) = each ($array) ){
		$tr[]=DAEMON_STATUS_ROUND($ligne,$ini,null,1);
		
	}
$tables[]="<div style='width:95%' class=form>";	
if(isset($_GET["miniadm"])){
	$tables[]=CompileTr4($tr,true);
	$et="&miniadm=yes";
	
}else{
	$tables[]=CompileTr2($tr,true);
}
$tables[]="
<div style='width:100%;text-align:right'>". 
imgtootltip("32-refresh.png","{refresh}","LoadAjax('zarafa-services-status','$page?services-status=yes$et');")."</div>";


$html=implode("\n",$tables);	
echo $tpl->_ENGINE_parse_body($html);		

	
	
}

function popup_mailbox_tabs(){
	$q=new mysql();
	$page=CurrentPageName();
	$tpl=new templates();
	$array["popup-mailbox-section"]="{production_mailboxes}";
	$array["popup-orphans"]="{orphans}";
	
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="popup-orphans"){
			$html[]="<li><a href=\"zarafa.orphans.php$adduri2\"><span style='font-size:14px'>$ligne</span></a></li>\n";
			continue;
		}

	
		
		$html[]="<li><a href=\"$page?$num=yes$adduri\"><span style='font-size:14px'>$ligne</span></a></li>\n";
			
		}	
	
	$tab="<div id=main_config_zarafaMBX style='width:100%;height:100%;overflow:auto;$fontsize'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_zarafaMBX').tabs();
			
			
			});
		</script>";		
	
	
	echo $tpl->_ENGINE_parse_body($tab);
		
	
	
}


function popup_mailbox(){
	$page=CurrentPageName();
	$html="
	<div id='zarafa-inline-mailbox'></div>
	<script>
		LoadAjax('zarafa-inline-mailbox','$page?mailboxes=yes');
	</script>
	";
	
	echo $html;	
	
}

function popup_mailbox_rebuild(){
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?zarafa-hash=yes&rebuild=yes");
	
}


function mailbox_list(){
$page=CurrentPageName();
	$tpl=new templates();
	$member=$tpl->_ENGINE_parse_body("{member}");
	$email=$tpl->_ENGINE_parse_body("{mail}");
	$ou=$tpl->_ENGINE_parse_body("{organization}");
	$license=$tpl->_ENGINE_parse_body("{license}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$user=$tpl->_ENGINE_parse_body("{user}");
	$mailbox_size=$tpl->_ENGINE_parse_body("{mailbox_size}");
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$delete_rule=$tpl->javascript_parse_text("{delete_rule}");
	$refresh=$tpl->_ENGINE_parse_body("{refresh}");
	$deleteAll=$tpl->_ENGINE_parse_body("{delete_all}");
	$apply=$tpl->_ENGINE_parse_body("{apply_parameters}");
	$t=time();
	
	$q=new mysql();
	$sql="SELECT COUNT(zmd5) as tcount FROM zarafauserss WHERE license=1";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));	
	$Licensed=$ligne["tcount"];

	$sql="SELECT COUNT(zmd5) as tcount FROM zarafauserss WHERE license=0";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));	
	$NoTLicensed=$ligne["tcount"];	
	
	$sum=$Licensed+$NoTLicensed;
	
	$title=$tpl->_ENGINE_parse_body("$sum {members} $Licensed {licensed_mailboxes}");
	
	$import=$tpl->_ENGINE_parse_body("{import}");
	
	
	$buttons="
	buttons : [
	{name: '$refresh', bclass: 'Reload', onpress : Reload$t},
	],	";		
	
	
	$html="
	<center id='anim-$t' style='margin-bottom:10px'></center>
	<div>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	</div>
	
<script>
var fetchid=0;
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?mailboxes-list=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$member', name : 'uid', width :146, sortable : true, align: 'left'},
		{display: '$email', name : 'mail', width :169, sortable : true, align: 'left'},
		{display: '$ou', name : 'ou', width : 78, sortable : true, align: 'center'},
		{display: '$license', name : 'license', width : 31, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'NONACTIVETYPE', width : 82, sortable : true, align: 'left'},
		{display: '$mailbox_size', name : 'storesize', width : 90, sortable : true, align: 'left'},
		],$buttons
	
	searchitems : [
		{display: '$member', name : 'uid'},
		{display: '$email', name : 'mail'},
		{display: '$ou', name : 'ou'},
		{display: '$license', name : 'NONACTIVETYPE'},
		],
	sortname: 'uid',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 690,
	height: 400,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,300,500]
	
	});   
});
      

  
	var x_Reload$t= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
		$('#flexRT$t').flexReload();
		document.getElementById('anim-$t').innerHTML='';
	}    


  
  function Reload$t(){
  	 var XHR = new XHRConnection();
  	 XHR.appendData('mailboxes-reload','yes');
  	 document.getElementById('anim-$t').innerHTML='<img src=\"img/loader-big.gif\">';
  	 XHR.sendAndLoad('$page', 'POST',x_Reload$t);    	
  }
  


</script>";
	
	echo $html;	
	
	
}


function mailbox_list_old(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$db=unserialize(base64_decode($sock->getFrameWork("cmd.php?zarafa-hash=yes")));
	$ZarafaHashRebuild=$tpl->javascript_parse_text("{ZarafaHashRebuild}");
	
		$html="
	<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
	<th>". imgtootltip("refresh-24.png","{refresh}","ZarafaHashRebuild()")."</th>
	<th>{email}</th>
	<th>{mailbox_size}</th>
	</tr>
</thead>
<tbody class='tbody'>";	
	
		while (list ($domain, $array) = each ($db) ){	
			
			while (list ($uid, $infos) = each ($array["USERS"]) ){	
				if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
				$js=MEMBER_JS($uid,1,1);
				if($infos["CURRENT_STORE_SIZE"]==null){$infos["CURRENT_STORE_SIZE"]=0;}
				$html=$html."
				<tr class=$classtr>
				<td width=1%>". imgtootltip("user-32.png","{view}",$js)."</td>
				<td><strong style='font-size:13px'>{$infos["EMAILADDRESS"]}</strong></td>
				<td align=center width=1%><strong style='font-size:13px'>{$infos["CURRENT_STORE_SIZE"]}</strong></td>
				</tr>
				
				";
				
			}
			
			
		}
	
	$html=$html."</table>
	<script>
	var x_ZarafaHashRebuild= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
		LoadAjax('zarafa-inline-mailbox','$page?mailboxes=yes');
	}
			
	
	
	function ZarafaHashRebuild(){
		if(confirm('$ZarafaHashRebuild')){
			var XHR = new XHRConnection();
			XHR.appendData('ZarafaHashRebuild','yes');
			AnimateDiv('zarafa-inline-mailbox');
			XHR.sendAndLoad('$page', 'POST',x_ZarafaHashRebuild);
		}
	}
		
	</script>
	
	
	";
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function popup_license(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$license=base64_decode($sock->getFrameWork("cmd.php?zarafa-read-license=yes"));
	
	if($license==null){
		$license_info="{ZARAFA_LICENSE_USE_FREE}";
	}else{
		$license_info=trim($sock->GET_INFO("ZarafaLicenseInfos"));
		if(preg_match("#([0-9]+)\s+total#",$license_info,$re)){$license_info=$re[1]." {users}";}
	}
	
	$html="
	<div style='font-size:14px;font-weight:bolder'>{license_info}: $license_info</div>
	<br>
	<center>
	<table class=form>
	<tr>
		<td class=legend>{serial_number}:</td>
		<td><code style='font-size:16px;font-weight:bold'>$license</td>
	</tr>
	</table>
	</center>
	<hr>
	
	<div class=explain>{ZARAFA_UPDATE_SERIAL_EXPLAIN}</div>
	<center>
	<div id='zarafa-license-form'>
	<table class=form>
	<tr>
		<td class=legend>{update_serial_number}:</td>
		<td>". Field_text("serial_number",null,"font-size:16px;padding:5px")."</td>
		<td width=1%>". button("{apply}","ZarafaUpdateLicense()")."</td>
	</tr>
	</table>
	</center>
	</div>
	<script>
		
	var x_ZarafaUpdateLicense= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
		RefreshTab('main_config_zarafa');
		}
		
	function ZarafaUpdateLicense(){
			var XHR = new XHRConnection();
			XHR.appendData('zlicense',document.getElementById('serial_number').value);
			AnimateDiv('zarafa-license-form');
			XHR.sendAndLoad('$page', 'POST',x_ZarafaUpdateLicense);
			
			
		}


</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);	
		
	
	
}

function mailbox_items_reload(){
	$sock=new sockets();
	$sock->getFrameWork("zarafa.php?reload-mailboxes-force=yes&MyCURLTIMEOUT=300");
	
}

function mailbox_items(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	
	$t=$_GET["t"];
	$search='%';
	$table="zarafauserss";
	$database="artica_events";
	$page=1;
	$ORDER="";
	
	$total=0;
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("$table, no such table");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show("$q->mysql_error");}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	
	while ($ligne = mysql_fetch_assoc($results)) {
		$uid=$ligne["uid"];
		$md5=$ligne["zmd5"];
		$color="black";
		$imglicense="22-key.png";
		if($ligne["license"]==0){$imglicense="ed_delete_grey.gif";}
		$js=MEMBER_JS($uid,1,1);
		$license=imgsimple($imglicense,"{delete}");
		$href="<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:14px;text-decoration:underline'>";
		$span="<span style='font-size:14px'>";
		$ligne["storesize"]=FormatBytes($ligne["storesize"]/1024);
		$data['rows'][] = array(
		'id' => $md5,
		'cell' => array(
			$span.$href.$uid."</a></span>",
			$span.$href.$ligne["mail"]."</a></span>",
			$span.$href.$ligne["ou"]."</a></span>",
			$span.$license."</span>",
			$span.$href.$ligne["NONACTIVETYPE"]."</a></span>",
			$span.$ligne["storesize"]."</a></span>",
			)
		);
	}
	
	
echo json_encode($data);			
	
}


function save_license(){
	$zlicense=base64_encode($_POST["zlicense"]);
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?zarafa-write-license=yes&license=$zlicense");
	
}
//zarafa-stats : http://forums.zarafa.com/viewtopic.php?f=9&t=2913
?>

