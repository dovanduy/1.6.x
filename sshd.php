<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.openssh.inc');
	include_once('ressources/class.user.inc');

$user=new usersMenus();
	if($user->AsSystemAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["SSHD_STATUS"])){SSHD_STATUS();exit;}
	if(isset($_POST["UnixUser"])){add_system_user_save();exit;}
	if(isset($_GET["events-js"])){events_js();exit;}
	if(isset($_POST['upload']) ){SSHD_KEYS_SERVER_UPLOAD();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["parameters"])){parameters();exit;}
	if(isset($_GET["ListenAddress-list"])){listen_address_list();exit;}
	if(isset($_GET["LoginGraceTime"])){saveconfig();exit;}
	if(isset($_GET["ListenAddressSSHDADD"])){ListenAddressADD();exit;}
	if(isset($_GET["ListenAddressSSHDDelete"])){ListenAddressDEL();exit;}
	if(isset($_GET["keys"])){popup_keys();exit;}
	if(isset($_GET["GenerateSSHDKeyPair"])){GenerateSSHDKeyPair();exit;}
	if(isset($_GET["GetSSHDFingerprint"])){GetSSHDFingerprint();exit;}
	if(isset($_GET["download-key-pub"])){SSHDKeyPair_download();exit;}
	if(isset($_GET["SSHD_KEYS_SERVER"])){SSHD_KEYS_SERVER_FORM();exit;}
	if(isset($_GET["add-system-user-popup"])){add_system_user_popup();exit;}
	
	if(isset($_GET["events"])){events();exit;}
	if(isset($_GET["sshd-events"])){events_list();exit;}
	
	if(isset($_GET["banner-js"])){banner_js();exit;}
	if(isset($_GET["banner"])){banner_popup();exit;}
	if(isset($_POST["banner"])){banner_save();exit;}
	if(isset($_GET["reload-js"])){reload_js();exit;}
	if(isset($_POST["RELOAD"])){RELOAD();exit;}
	
js();	

function events_js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	echo "$('#BodyContent').load('$page?events=yes');";
	
}

function banner_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{banner}");
	echo "YahooWin3('997','$page?banner=yes','$title')";
	
}

function reload_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$ask=$tpl->javascript_parse_text("{reload_ssh_service_ask}");
	$t=time();
echo "	var xReload$t= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			
		 }		
		
		
		
		function Reload$t(){
			if(!confirm('$ask')){return;}
			var XHR = new XHRConnection();
			XHR.appendData('RELOAD','yes');
			XHR.sendAndLoad('$page', 'POST',xReload$t);
		}

Reload$t();";	
	
}

function RELOAD(){
	$sock=new sockets();
	echo @implode("\n", unserialize(base64_decode($sock->getFrameWork("services.php?reload-sshd=yes"))));
	
}

function banner_popup(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$SSHDBanner=$sock->GET_INFO("SSHDBanner");
	if(strlen($SSHDBanner)<5){
		$SSHDBanner="|--------------------------------------------------------------------------------------|\n| This system is for the use of authorized users only.                                 |\n| Individuals using this computer system without authority, or in                      |\n| excess of their authority, are subject to having all of their                        |\n| activities on this system monitored and recorded by system personnel.                |\n|                                                                                      |\n|                                                                                      |\n| In the course of monitoring individuals improperly using this                        |\n| system, or in the course of system maintenance, the activities                       |\n| of authorized users may also be monitored.                                           |\n|                                                                                      |\n|                                                                                      |\n| Anyone using this system expressly consents to such monitoring                       |\n| and is advised that if such monitoring reveals possible                              |\n| evidence of criminal activity, system personnel may provide the                      |\n| evidence of such monitoring to law enforcement officials.                            |\n|--------------------------------------------------------------------------------------|";
	}
	
	$t=time();
	$html="
	<div id='$t'>
	<textarea id='txt-$t' style='margin-top:5px;font-family:Courier New;font-weight:bold;width:100%;height:350px;
	border:5px solid #8E8E8E;overflow:auto;font-size:16px'>$SSHDBanner</textarea>
	<center>". button("{apply}","Save$t()",18)."</center>
	<script>
	var x_Save$t= function (obj) {
			var tempvalue=obj.responseText;
			document.getElementById('$t').innerHTML='';
			if(tempvalue.length>3){alert(tempvalue)};
			YahooWin3Hide();
			
		 }		
		
		
	function Save$t(){
			var XHR = new XHRConnection();
			XHR.appendData('banner',document.getElementById('txt-$t').value);
			AnimateDiv('$t');
			XHR.sendAndLoad('$page', 'POST',x_Save$t);
		}	
	</script>	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function banner_save(){
	$sock=new sockets();
	$_POST["banner"]=url_decode_special_tool(trim($_POST["banner"]));
	$sock->SET_INFO("SSHDBanner", $_POST["banner"]."\n");
	$sshd=new openssh();
	$sshd->save();
		
}


function js(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{APP_OPENSSH}");
	$start="OPENSSH_LOAD();";
	
	$sock=new sockets();
	$tpl=new templates();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		
		
	if(isset($_GET["tabsize"])){$tabsize="&tabsize={$_GET["tabsize"]}";}
	if(isset($_GET["in-front-ajax"])){
		$start="OPENSSH_LOAD2();";
	}
	
	$html="
	<script>
	function OPENSSH_LOAD(){
			YahooWin2('600','$page?popup=yes$tabsize','$title');
		}
		
		function OPENSSH_LOAD2(){
			$('#system-sshd').load('$page?popup=yes$tabsize');
		}		
	
		function BACKUP_TASKS_LISTS(){
			//$('#table-backup-tasks').flexReload();
			LoadAjax('taskslists','$page?BACKUP_TASKS_LISTS=yes');
		}
		
		function BACKUP_TASKS_SOURCE(ID){
			YahooWin3('500','$page?backup-sources=yes&ID='+ID,'$sources');
		}
		
		function TASK_EVENTS_DETAILS(ID){
			YahooWin3('700','$page?TASK_EVENTS_DETAILS='+ID,ID+'::$events');
		}
		
		function TASK_EVENTS_DETAILS_INFOS(ID){
			YahooWin4('700','$page?TASK_EVENTS_DETAILS_INFOS='+ID,ID+'::$events');
		}
		
		function BACKUP_TASK_MODIFY_RESSOURCES(ID){
			YahooWin3('500','$page?BACKUP_TASK_MODIFY_RESSOURCES='+ID,ID+'::$resources');
		}
		
		
var x_DeleteBackupTask= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		BACKUP_TASKS_LOAD();
		if(document.getElementById('wizard-backup-intro')){
			WizardBackupLoad();
		}
	 }	

var x_DELETE_BACKUP_SOURCES= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		BACKUP_TASKS_LOAD();
		YahooWin3Hide();
	 }		 
		
		function DeleteBackupTask(ID){
			if(confirm('$BACKUP_TASK_CONFIRM_DELETE')){
				var XHR = new XHRConnection();
				XHR.appendData('DeleteBackupTask',ID);
				XHR.sendAndLoad('$page', 'GET',x_DeleteBackupTask);
			}
		}
		
		function DELETE_BACKUP_SOURCES(ID,INDEX){
			if(confirm('$BACKUP_TASK_CONFIRM_DELETE_SOURCE')){
				var XHR = new XHRConnection();
				XHR.appendData('DeleteBackupSource','yes');
				XHR.appendData('ID',ID);
				XHR.appendData('INDEX',INDEX);
				XHR.sendAndLoad('$page', 'GET',x_DELETE_BACKUP_SOURCES);
			}
		}
		
		
	var x_BACKUP_SOURCES_SAVE_OPTIONS= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			BACKUP_TASKS_SOURCE(mem_taskid);
			
		 }		
		
		
	function BACKUP_SOURCES_SAVE_OPTIONS(taskid){
		mem_taskid=taskid;
		var XHR = new XHRConnection();
		if(document.getElementById('backup_stop_imap').checked){
		XHR.appendData('backup_stop_imap',1);}else{
		XHR.appendData('backup_stop_imap',0);}
		XHR.appendData('taskid',taskid);
		document.getElementById('BACKUP_SOURCES_OPTIONS').innerHTML='<center><img src=img/wait_verybig.gif></center>';	
		XHR.sendAndLoad('$page', 'GET',x_BACKUP_SOURCES_SAVE_OPTIONS);
		}	

	function BACKUP_TASK_TEST(ID){
			YahooWin3('500','$page?backup-tests=yes&ID='+ID,'$tests');
		}
		
	var x_BACKUP_TASK_RUN= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			alert('$apply_upgrade_help');
			BACKUP_TASKS_LOAD();
		 }		
		
		
		
		function BACKUP_TASK_RUN(ID){
			if(confirm('$backupTaskRunAsk')){
				var XHR = new XHRConnection();
				XHR.appendData('BACKUP_TASK_RUN',ID);
				XHR.sendAndLoad('$page', 'GET',x_BACKUP_TASK_RUN);
			}
		}
		
	
	$start
	</script>";
	
	
	echo $html;
}


function popup(){
	
	
	$tpl=new templates();
	$array["status"]='{status}';
	$array["parameters"]='{parameters}';
	$array["limit_access"]='{limit_access}';
	$array["keys"]='{automatic_login}';
	$array["antihack"]='Anti-hack';
	$array["events"]='{events}';
	$page=CurrentPageName();
	if($_GET["tabsize"]==14){$_GET["tabsize"]=16;}
	if(isset($_GET["tabsize"])){$tabsize="style='font-size:22px'";}
	
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="antihack"){
			$html[]= $tpl->_ENGINE_parse_body("<li $tabsize><a href=\"postfix.iptables.php?tab-iptables-rules=yes&sshd=yes\"><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="limit_access"){
			$html[]= $tpl->_ENGINE_parse_body("<li $tabsize><a href=\"sshd.AllowUsers.php\"><span>$ligne</span></a></li>\n");
			continue;
			
		}
		
		$html[]= $tpl->_ENGINE_parse_body("<li $tabsize><a href=\"$page?$num=yes\"><span>$ligne</span></a></li>\n");
	}
	
	
	echo build_artica_tabs($html, "main_config_openssh");
	
}

function events(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$t=time();
	$country=$tpl->_ENGINE_parse_body("{country}");
	$date=$tpl->_ENGINE_parse_body("{date}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$title=$tpl->_ENGINE_parse_body("{sshd_events_explain}");

	$html="
	
	<table class='table-$t' style='display: none' id='table-$t' style='width:100%;margin:-10px'></table>
<script>
FreeWebIDMEM='';
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?sshd-events=yes',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'success', width : 46, sortable : true, align: 'center'},
		{display: '$country', name : 'Country', width :74, sortable : true, align: 'center'},
		{display: '$date', name : 'zDate', width :200, sortable : true, align: 'left'},
		{display: '$member', name : 'uid', width : 116, sortable : true, align: 'left'},
		{display: '$hostname', name : 'hostname', width : 367, sortable : true, align: 'left'},
		{display: '$ipaddr', name : 'ipaddr', width : 189, sortable : true, align: 'left'},
	],
	$buttons

	searchitems : [
		{display: '$hostname', name : 'hostname'},
		{display: '$ipaddr', name : 'ipaddr'},
		{display: '$country', name : 'Country'},
		{display: '$member', name : 'uid'},
		
		
		],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true
	
	});   
});		
</script>";
echo $html;
}

function events_list(){
	include_once(dirname(__FILE__) . '/ressources/class.rtmm.tools.inc');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$search='%';
	$table="auth_events";
	$page=1;
	$ORDER="ORDER BY zDate DESC";
	
	$total=0;
	if($q->COUNT_ROWS($table,"artica_events")==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$total = $q->COUNT_ROWS($table, "artica_events");
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){
		
	}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){
		json_error_show($q->mysql_error);
	}	
	
	if(mysql_num_rows($results)==0){json_error_show("no data");}
	$span="<span style='font-size:18px;font-weight:normal'>";
	
	
	while ($ligne = mysql_fetch_assoc($results)) {

	if($ligne["success"]==1){$img='fleche-20-right.png';}else{$img='fleche-20-red-right.png';}
	$flag=imgsimple(GetFlags($ligne["Country"]),$ligne["Country"],null);

		
		
	$data['rows'][] = array(
		'id' => $ligne['ID'],
	
		'cell' => array(
		"<img src=img/$img>",
		$flag,
		"$span{$ligne["zDate"]}</span>",
		"$span{$ligne["uid"]}</span>",
		"$span{$ligne["hostname"]}</span>",
		"$span{$ligne["ipaddr"]}</span>",
		)
		);
	}
	
	
echo json_encode($data);		

}


function SSHD_STATUS(){
	$sock=new sockets();
	$tpl=new templates();
	$ini=new Bs_IniHandler();
	$page=CurrentPageName();
	$ini->loadString(base64_decode($sock->getFrameWork('cmd.php?openssh-ini-status=yes')));
	$status=DAEMON_STATUS_ROUND("APP_OPENSSH",$ini);
	echo $tpl->_ENGINE_parse_body($status.
			"<div style='text-align:right'>". imgtootltip("refresh-32.png",null,"LoadAjax('SSHD_STATUS','$page?SSHD_STATUS=yes')")."</div>"
			);
	
}

function status(){
	$page=CurrentPageName();

	
	$html="
	<table style='width:100%'>
	<tr>
	<td style='vertical-align:top;width:240px' nowrap><div id='SSHD_STATUS'></div></td>
	<td style='vertical-align:top;width:99%'>
		<div style='font-size:32px;font-weight:bold;margin-bottom:50px'>{APP_OPENSSH}</div>
		<div style='font-size:18px'>{OPENSSH_EXPLAIN}</div>
		<hr>
	</td>
	</tr>
	</table>
	<script>
		LoadAjax('SSHD_STATUS','$page?SSHD_STATUS=yes');
	</script>
	
	
	";
	
	$tpl=new templates();
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function parameters(){
	
	$sshd=new openssh();
	$page=CurrentPageName();
	$users=new usersMenus();
	if(preg_match("#([0-9]+)\.([0-9]+)#",$users->OPENSSH_VER,$re)){$opensshver="{$re[1]}{$re[2]}";}
	
	
	if(is_array($sshd->HostKey)){
		while (list ($num, $line) = each ($sshd->HostKey)){
			$hostkey=$hostkey."<div><code>$line</code>&nbsp;</div>";
		}
	}
	
	
	if($opensshver<50){
		$disable_js="DisableMaxSessions();";
	}
	
	$html="
	<div id='sshdconfigid'>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td valign='top' class=legend style='font-size:18px'>{listen_port}:</td>
		<td style='font-size:18px'>". Field_text("Port",$sshd->main_array["Port"],"font-size:18px;padding:3x;width:60px")."</td>
		<td width=1%>&nbsp;</td>
	</tr>
	<tr>
		<td valign='top' class=legend style='font-size:18px'>{StrictModes}:</td>
		<td style='font-size:18px'>". Field_checkbox_design("StrictModes","yes",$sshd->main_array["StrictModes"])."</td>
		<td width=1%>". help_icon("{StrictModes_text}")."</td>
	</tr>		
	<tr>
		<td valign='top' class=legend style='font-size:18px'>{PermitRootLogin}:</td>
		<td style='font-size:18px'>". Field_checkbox_design("PermitRootLogin","yes",$sshd->main_array["PermitRootLogin"])."</td>
		<td width=1%>". help_icon("{PermitRootLogin_text}")."</td>
	</tr>
	<tr>
		<td valign='top' class=legend style='font-size:18px'>{AllowOnlyGroups}:</td>
		<td style='font-size:18px' colspan=2>". Field_text("AllowGroups",$sshd->main_array["AllowGroups"],
				"font-size:18px;padding:3x;width:360px")."</td>
		
	</tr>				
				
				
	<tr>
		<td valign='top' class=legend style='font-size:18px'>{UseBanner}:</td>
		<td style='font-size:18px'>". Field_checkbox_design("Banner",1,$sshd->main_array["Banner"])."</td>
		<td width=1%><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$page?banner-js=yes');\" 
		style='font-size:18px;text-decoration:underline'>{banner}</a></td>
	</tr>	
	
	
	<tr>
		<td valign='top' class=legend style='font-size:18px'>{UsePAM}:</td>
		<td style='font-size:18px'>". Field_checkbox_design("UsePAM","yes",$sshd->main_array["UsePAM"])."</td>
		<td width=1%>". help_icon("{UsePAM_TEXT}")."</td>
	</tr>	
	<tr>
		<td valign='top' class=legend style='font-size:18px'>{ChallengeResponseAuthentication}:</td>
		<td style='font-size:18px'>". Field_checkbox_design("ChallengeResponseAuthentication","yes",$sshd->main_array["ChallengeResponseAuthentication"])."</td>
		<td width=1%>". help_icon("{ChallengeResponseAuthentication_text}")."</td>
	</tr>
	<tr>
		<td valign='top' class=legend style='font-size:18px'>{PasswordAuthentication}:</td>
		<td style='font-size:18px'>". Field_checkbox_design("PasswordAuthentication","yes",$sshd->main_array["PasswordAuthentication"])."</td>
		<td width=1%>". help_icon("{PasswordAuthentication_text}")."</td>
	</tr>
	<tr>
		<td valign='top' class=legend style='font-size:18px'>{PubkeyAuthentication}:</td>
		<td style='font-size:18px'>". Field_checkbox_design("PubkeyAuthentication","yes",$sshd->main_array["PubkeyAuthentication"])."</td>
		<td width=1%>". help_icon("{PubkeyAuthentication_text}")."</td>
	</tr>
			
	
	
	
	<tr>
		<td valign='top' class=legend style='font-size:18px'>{PermitTunnel}:</td>
		<td style='font-size:18px'>". Field_checkbox_design("PermitTunnel","yes",$sshd->main_array["PermitTunnel"])."</td>
		<td width=1%>". help_icon("{PermitTunnel_text}")."</td>
	</tr>	
	<tr>
		<td valign='top' class=legend style='font-size:18px'>{UseDNS}:</td>
		<td style='font-size:18px'>". Field_checkbox_design("UseDNS","yes",$sshd->main_array["UseDNS"])."</td>
		<td width=1%>". help_icon("{UseDNS_sshd_text}")."</td>
	</tr>		
	
	
	
	
	
	
	<tr>
		<td valign='top' class=legend style='font-size:18px'>{LoginGraceTime}:</td>
		<td style='font-size:18px'>". Field_text("LoginGraceTime",$sshd->main_array["LoginGraceTime"],"font-size:18px;padding:3x;;width:60px")."&nbsp;{seconds}</td>
		<td width=1%>". help_icon("{LoginGraceTime_text}")."</td>
	</tr>
	<tr>
		<td valign='top' class=legend style='font-size:18px'>{MaxSessions}:</td>
		<td style='font-size:18px'>". Field_text("MaxSessions",$sshd->main_array["MaxSessions"],"font-size:18px;padding:3x;width:60px")."&nbsp;{sessions}</td>
		<td width=1%>". help_icon("{MaxSessions_text}")."</td>
	</tr>	
	<tr>
		<td valign='top' class=legend style='font-size:18px'>{MaxAuthTries}:</td>
		<td style='font-size:18px'>". Field_text("MaxAuthTries",$sshd->main_array["MaxAuthTries"],"font-size:18px;padding:3x;width:60px")."&nbsp;</td>
		<td width=1%>". help_icon("{MaxAuthTries_text}")."</td>
	</tr>	
	<tr>
		<td valign='top' class=legend style='font-size:18px'>{HostKey}:</td>
		<td style='font-size:18px'>$hostkey</td>
		<td width=1%>". help_icon("{HostKey_text}")."</td>
	</tr>	
		<td valign='top' class=legend style='font-size:18px'>{AuthorizedKeysFile}:</td>
		<td style='font-size:18px'>{$sshd->main_array["AuthorizedKeysFile"]}</td>
		<td width=1%>". help_icon("{AuthorizedKeysFile_text}")."</td>
	</tr>	
	
	
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","SaveSSHDConfig()",26)."</td>
	</tr>
	</table>
	</div>
	<div style='width:100%;heigth:250px;overflow:auto' id='sshd_nets'></div>
	</div>
	
	
	<script>
	
		var x_SaveSSHDConfig= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			RefreshTab('main_config_openssh');
			
		 }		
	
	
		function SaveSSHDConfig(){
			var XHR = new XHRConnection();
			XHR.appendData('Port',document.getElementById('Port').value);
			XHR.appendData('LoginGraceTime',document.getElementById('LoginGraceTime').value);
			XHR.appendData('MaxSessions',document.getElementById('MaxSessions').value);
			XHR.appendData('MaxAuthTries',document.getElementById('MaxAuthTries').value);
			XHR.appendData('AllowGroups',document.getElementById('AllowGroups').value);
			
			
			
			if(document.getElementById('PermitRootLogin').checked){XHR.appendData('PermitRootLogin','yes');}else{XHR.appendData('PermitRootLogin','no');}
			if(document.getElementById('PermitTunnel').checked){XHR.appendData('PermitTunnel','yes');}else{XHR.appendData('PermitTunnel','no');}
			if(document.getElementById('UseDNS').checked){XHR.appendData('UseDNS','yes');}else{XHR.appendData('UseDNS','no');}
			if(document.getElementById('UsePAM').checked){XHR.appendData('UsePAM','yes');}else{XHR.appendData('UsePAM','no');}
			if(document.getElementById('ChallengeResponseAuthentication').checked){XHR.appendData('ChallengeResponseAuthentication','yes');}else{XHR.appendData('ChallengeResponseAuthentication','no');}
			if(document.getElementById('PasswordAuthentication').checked){XHR.appendData('PasswordAuthentication','yes');}else{XHR.appendData('PasswordAuthentication','no');}
			if(document.getElementById('PubkeyAuthentication').checked){XHR.appendData('PubkeyAuthentication','yes');}else{XHR.appendData('PubkeyAuthentication','no');}
			if(document.getElementById('StrictModes').checked){XHR.appendData('StrictModes','yes');}else{XHR.appendData('StrictModes','no');}
			if(document.getElementById('Banner').checked){XHR.appendData('Banner','1');}else{XHR.appendData('Banner','0');}
			
			
			
			document.getElementById('sshdconfigid').innerHTML='<center><img src=img/wait_verybig.gif></center>';		
			XHR.sendAndLoad('$page', 'GET',x_SaveSSHDConfig);
		
		
		}
		
		function DisableMaxSessions(){
			document.getElementById('MaxSessions').disabled=true;
		}
	
	
		function RefreshListenAddress(){
			LoadAjax('sshd_nets','$page?ListenAddress-list=yes');
		}
	
	RefreshListenAddress();
	$disable_js	
	</script>";
	
	$tpl=new templates();
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function listen_address_list(){
	$sshd=new openssh();
	$page=CurrentPageName();
	$tcp=new networking();
	$arrayip=$tcp->ALL_IPS_GET_ARRAY();
	$arrayip["0.0.0.0"]="{all}";
	
	unset($arrayip[null]);
	unset($arrayip["127.0.0.1"]);
	$field=Field_array_Hash($arrayip,"ListenAddressSSHDADD","0.0.0.0",null,null,0,"font-size:13px;padding:3px");
	
	$html="
	<center>
	<table class='tableView' style='width:250px'>
	<tr>
		
		<td>$field</td>
		<td style='font-size:13px;' width=1%>:</td>
		<td style='font-size:13px;'>".Field_text("ListenAddressSSHDPort",22,"font-size:13px;padding:3px;width:40px")."</td>
		<td width=1%>". button("{add}","AddSSHDNet()",14)."</td>
		</tr>
	
	</table>
	
	<table class='tableView' style='width:240px'>
		<thead class='thead'>
		<tr>
		<th>&nbsp;</th>
		<th colspan=2>{listen_ip}</th>
		</tr>
		
		</thead>";
	if(is_array($sshd->ListenAddress)){
	while (list ($num, $line) = each ($sshd->ListenAddress)){
	if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$html=$html."
		<tr class=$classtr>
			<td width=1%><img src='img/fw_bold.gif'></td>
			<td><strong style='font-size:18px'>$line</strong></td>
			<td width=1%>". imgtootltip("ed_delete.gif","{delete}","ListenAddressSSHDDelete($num)")."</td>
		</tr>
		";
		
	}}
	
$html=$html."</table></center>
<script>
		var x_AddSSHDNet= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			RefreshListenAddress();
			
		 }		
	
	
		function AddSSHDNet(){
			var XHR = new XHRConnection();
			XHR.appendData('ListenAddressSSHDADD',document.getElementById('ListenAddressSSHDADD').value);
			XHR.appendData('ListenAddressSSHDPort',document.getElementById('ListenAddressSSHDPort').value);
			document.getElementById('sshd_nets').innerHTML='<center><img src=img/wait_verybig.gif></center>';		
			XHR.sendAndLoad('$page', 'GET',x_AddSSHDNet);
		}
		
		function ListenAddressSSHDDelete(INDEX){
			var XHR = new XHRConnection();
			XHR.appendData('ListenAddressSSHDDelete',INDEX);
			document.getElementById('sshd_nets').innerHTML='<center><img src=img/wait_verybig.gif></center>';		
			XHR.sendAndLoad('$page', 'GET',x_AddSSHDNet);
		}
		
		
</script>		

";	
	
	$tpl=new templates();
	
	echo $tpl->_ENGINE_parse_body($html);	
}

function ListenAddressDEL(){
	$sock=new sockets();
	$tpl=new templates();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		
	if($DisableNetworksManagement==1){echo $ERROR_NO_PRIVS;return;}	
		
	$sshd=new openssh();
	unset($sshd->ListenAddress[$_GET["ListenAddressSSHDDelete"]]);
	$sshd->save();
}

function ListenAddressADD(){
	
	$sock=new sockets();
	$tpl=new templates();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		
	if($DisableNetworksManagement==1){echo $ERROR_NO_PRIVS;return;}		
	
	$sshd=new openssh();
	$sshd->ListenAddress[]=$_GET["ListenAddressSSHDADD"].":".$_GET["ListenAddressSSHDPort"];
	$sshd->save();
}

function add_system_user_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	
	$html="
	
	<div id='$t'></div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{username}:</td>
		<td>". Field_text("username-$t",null,"font-size:16px;width:220px",null,null,null,false,"AddUserchk$t(event)")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{password}:</td>
		<td>". Field_password("password-$t",null,"font-size:16px;width:220px",null,null,null,false,"AddUserchk$t(event)")."</td>
	</tr>		
	<tr>
		<td colspan=2 align='right'>". button("{add_user}","AddUser$t()",16)."</td>
	</tr>
	</table>	
	<script>
		var x_AddUser$t= function (obj) {
				var tempvalue=obj.responseText;
				document.getElementById('$t').innerHTML='';
				if(tempvalue.length>3){alert(tempvalue);}
				YahooWin2Hide();
				if(document.getElementById('main_config_openssh')){	RefreshTab('main_config_openssh'); }
				if(document.getElementById('$t-table-list')){ $('#$t-table-list').flexReload(); }
		 }			

		function AddUserchk$t(e){
			if(checkEnter(e)){AddUser$t();}
		}
			
		function AddUser$t(){
			var XHR = new XHRConnection();
			var password=encodeURIComponent(document.getElementById('password-$t').value);
			XHR.appendData('UnixUser',document.getElementById('username-$t').value);
			XHR.appendData('password',password);
			AnimateDiv('$t');
			XHR.sendAndLoad('$page', 'POST',x_AddUser$t);
		}
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function add_system_user_save(){
	$_POST["password"]=url_decode_special_tool($_POST["password"]);
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->getFrameWork("services.php?AddUnixUser={$_POST["UnixUser"]}&password=".base64_encode($_POST["password"]))));
	echo "User:{$_POST["UnixUser"]}\n";
	echo @implode("\n", $datas);
	
}

function saveconfig(){
	$sock=new sockets();
	$tpl=new templates();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		
	if($DisableNetworksManagement==1){echo $ERROR_NO_PRIVS;return;}		
	
	$sshd=new openssh();
	while (list ($num, $val) = each ($_GET)){
		$sshd->main_array[$num]=$val;
	}
	
	$sshd->save();
	
}

function popup_keys(){
	$sock=new sockets();
	$tpl=new templates();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		
	if($DisableNetworksManagement==1){echo $ERROR_NO_PRIVS;return;}		
	$add_user=$tpl->_ENGINE_parse_body("{add_user}");
	$page=CurrentPageName();
	$sock=new sockets();
	$ldap=new clladp();
	$hash=$ldap->Hash_GetALLUsers();
	$users=unserialize(base64_decode($sock->getFrameWork("cmd.php?unixLocalUsers=yes")));
	
	
	while (list ($uid, $mail) = each ($hash) ){
		if(strpos($uid,"$")>0){continue;}
		$users[$uid]=$uid;
	}
	$users[null]="{select}";
	ksort($users);
	
	$userF=Field_array_Hash($users,"user_key","root","GetSSHDFingerprint()",null,0,"font-size:18px;padding:3px");
	
	
	$html="
	<div class=explain id='idtofill' style='font-size:18px'>{SSH_KEYS_WHY}</div>
	
	<div style='font-size:18px'>{SSH_KEYS_CLIENT}</div>
	<div class=explain style='font-size:18px'>{SSH_KEYS_CLIENT_EXPLAIN}</div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:18px' nowrap style'=font-size:18px'>{ArticaProxyServerUsername}:</td>
		<td>$userF</td>
		<td width=1% nowrap>". button("{fingerprint}","GetSSHDFingerprint()",14)."</td>
		<td width=1% nowrap>". button("{generate_key}","GenerateSSHDKeyPair()",14)."</td>
		<td width=99%>". button("{add_user}","SSHAddSystemUser()",14)."</td>
		
	</tr>
	<tr>
		<td colspan=5><div id='fingerprint'></div></td>
	</tr>
	</table>
	
	<hr>
	<div style='font-size:18px'>{SSHD_KEYS_SERVER}</div>
	<div class=explain style='font-size:18px'>{SSHD_KEYS_SERVER_TEXT}</div>
	
	<iframe style='width:100%;height:250px;border:0px' src='$page?SSHD_KEYS_SERVER=yes'></iframe>
	
	
	<script>
		var x_GenerateSSHDKeyPair= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			GetSSHDFingerprint();
			
		 }		
		 
		var x_GetSSHDFingerprint= function (obj) {
			var tempvalue=obj.responseText;
			document.getElementById('fingerprint').innerHTML='';
			if(tempvalue.length>3){
				document.getElementById('fingerprint').innerHTML=tempvalue;
			}
		 }			 
			
		function GenerateSSHDKeyPair(){
			var XHR = new XHRConnection();
			XHR.appendData('GenerateSSHDKeyPair',document.getElementById('user_key').value);
			document.getElementById('fingerprint').innerHTML='<center><img src=img/wait_verybig.gif></center>';		
			XHR.sendAndLoad('$page', 'GET',x_GenerateSSHDKeyPair);
		}
		
		function GetSSHDFingerprint(){
			var XHR = new XHRConnection();
			XHR.appendData('GetSSHDFingerprint',document.getElementById('user_key').value);
			document.getElementById('fingerprint').innerHTML='<center><img src=img/wait_verybig.gif></center>';		
			XHR.sendAndLoad('$page', 'GET',x_GetSSHDFingerprint);		
		}
		
		function SSHAddSystemUser(){
			YahooWin2('550','$page?add-system-user-popup=yes','$add_user');
		
		}
		
		
	GetSSHDFingerprint();
	</script>
	
	
	";
	
	$tpl=new templates();
	
	echo $tpl->_ENGINE_parse_body($html);	
}

function GenerateSSHDKeyPair(){
	
	$sock=new sockets();
	$tpl=new templates();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		
	if($DisableNetworksManagement==1){echo $ERROR_NO_PRIVS;return;}		
	
	$uid=$_GET["GenerateSSHDKeyPair"];
	$sock=new sockets();
	$usersUNix=unserialize(base64_decode($sock->getFrameWork("cmd.php?unixLocalUsers=yes")));
	if($uid=="root"){
		$homepath="/root/.ssh";
	}else{
		if($usersUNix[$uid]<>null){$homepath="/home/$uid/.ssh";}
		if($homepath==null){
			$user=new user($uid);
			if($user->homeDirectory<>null){$homepath=$user->homeDirectory."/.ssh";}else{$homepath="/home/$uid/.ssh";}
			
		}
	}
	
	$sock=new sockets();
	$homepath_encoded=base64_encode($homepath);
	$datas=base64_decode($sock->getFrameWork("cmd.php?ssh-keygen=$homepath_encoded&uid=$uid"));
	echo $datas;
	
}

function GetSSHDFingerprint(){
	$uid=$_GET["GetSSHDFingerprint"];
	$page=CurrentPageName();
	$sock=new sockets();
	$usersUNix=unserialize(base64_decode($sock->getFrameWork("cmd.php?unixLocalUsers=yes")));
	if($uid=="root"){
		$homepath="/root/.ssh";
	}else{
		if($usersUNix[$uid]<>null){$homepath="/home/$uid/.ssh";}
		if($homepath==null){
			$user=new user($uid);
			if($user->homeDirectory<>null){$homepath=$user->homeDirectory."/.ssh";}else{$homepath="/home/$uid/.ssh";}
			
		}
	}	
	
	$tpl=new templates();
	$homepath_encoded=base64_encode($homepath);
	$datas=base64_decode($sock->getFrameWork("cmd.php?ssh-keygen-fingerprint=$homepath_encoded&uid=$uid"));
	
	if(trim($datas)==null){
		echo $tpl->_ENGINE_parse_body("<div class=explain>{SSHD_NOFINGER_NEED_GENERATE}</div>");return ;
	}
	
	echo $tpl->_ENGINE_parse_body("
	<table style='width:99%' class=form>
	<tr>
		<td>
			<div style='float:right'>".imgtootltip("32-key.png","{download}","document.location='$page?download-key-pub=$homepath_encoded'")."</div>
			<span style='font-size:16px'>{fingerprint}</span><hr>
			<code style='font-size:16px;font-weight:bold'>$datas</code>
	</td>
	</tr>
	<tr>
		<td align='right'>
			". button("{test_connection}","Loadjs('sshd.tests.php?uid=$uid')",14)."</td>
	</tr>
	
	
	</table>
	");
}

function SSHDKeyPair_download(){
	
	$sock=new sockets();
	$tpl=new templates();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		
	if($DisableNetworksManagement==1){echo $ERROR_NO_PRIVS;return;}		
	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?ssh-keygen-download=".$_GET["download-key-pub"]);
	$content=@file_get_contents("ressources/logs/web/id_rsa.pub");
	$size = filesize("ressources/logs/web/id_rsa.pub");
	header("Content-Type: application/force-download; name=\"id_rsa.pub\"");
	header("Content-Transfer-Encoding: binary");
	header("Content-Length: $size");
	header("Content-Disposition: attachment; filename=\"id_rsa.pub\"");
	header("Expires: 0");
	header("Cache-Control: no-cache, must-revalidate");
	header("Pragma: no-cache");
	echo $content; 	
	
}

function SSHD_KEYS_SERVER_FORM($error=null){
	$sock=new sockets();
	$ldap=new clladp();
	$hash=$ldap->Hash_GetALLUsers();
	$users=unserialize(base64_decode($sock->getFrameWork("cmd.php?unixLocalUsers=yes")));
	
	$page=CurrentPageName();
	while (list ($uid, $mail) = each ($hash) ){
		if(strpos($uid,"$")>0){continue;}
		$users[$uid]=$uid;
	}
	$users[null]="{select}";
	ksort($users);	
		$userF=Field_array_Hash($users,"uid",$_POST["uid"],null,null,0,"font-size:16px;padding:3px");
	$html="
	<div style='color:#d32d2d;font-size:18px;font-weight:bold'>$error</div>
	<form method=\"post\" enctype=\"multipart/form-data\" action=\"$page\">
	<table style='width:98%' class=form>
	<tr>
		<td class=legend style='font-size:16px' nowrap>{ArticaProxyServerUsername}:</td>
		<td>$userF</td>	
	</tr>	
	<tr>
	<tr>
		<td class=legend style='font-size:16px' nowrap><input type=\"file\" name=\"id_rsa\" size=\"30\"></td>
		<td><input type='submit' name='upload' value='{upload_a_file}&nbsp;&raquo;' style='width:190px;font-size:16px'></td>	
	</tr>	
	</table>
	$hidden
	<p>
	
	
	</p>
	</form>
	
	";
	$tpl=new templates();
	echo iframe($tpl->_ENGINE_parse_body($html),0,0);
	
}

function SSHD_KEYS_SERVER_UPLOAD(){
	
	$sock=new sockets();
	$tpl=new templates();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		
	if($DisableNetworksManagement==1){echo $ERROR_NO_PRIVS;return;}		
	
	$sock=new sockets();
	$page=CurrentPageName();
	if(!isset($_POST["uid"])){SSHD_KEYS_SERVER_FORM('{ArticaProxyServerUsername} not set');exit;}
	
	$uid=$_POST["uid"];
	$tmp_file = $_FILES['id_rsa']['tmp_name'];
	$content_dir=dirname(__FILE__)."/ressources/conf/upload";
	if(!is_dir($content_dir)){@mkdir($content_dir);}
	if( !@is_uploaded_file($tmp_file) ){
		SSHD_KEYS_SERVER_FORM('{error_unable_to_upload_file} '.$tmp_file);
		exit;
	}
	$name_file = $_FILES['id_rsa']['name'];

if(file_exists( $content_dir . "/" .$name_file)){@unlink( $content_dir . "/" .$name_file);}
 if( !move_uploaded_file($tmp_file, $content_dir . "/" .$name_file) ){
 	SSHD_KEYS_SERVER_FORM("{error_unable_to_move_file} : ". $content_dir . "/" .$name_file);
 	exit();
 	}
     
    $file=$content_dir . "/" .$name_file;
	$usersUNix=unserialize(base64_decode($sock->getFrameWork("cmd.php?unixLocalUsers=yes")));
	
	if($uid=="root"){
		$homepath="/root/.ssh";
	}else{
		if($usersUNix[$uid]<>null){$homepath="/home/$uid/.ssh";}
		if($homepath==null){
			$user=new user($uid);
			if($user->homeDirectory<>null){$homepath=$user->homeDirectory."/.ssh";}else{$homepath="/home/$uid/.ssh";}
			
		}
	}	
	writelogs("home=$homepath, source=$file",__FUNCTION__,__FILE__,__LINE__);
	$tpl=new templates();
	$homepath_encoded=base64_encode($homepath);
    $source_file_encoded=base64_encode($file);
    
    $datas=base64_decode($sock->getFrameWork("cmd.php?sshd-authorized-keys=yes&rsa=$source_file_encoded&home=$homepath_encoded&uid=$uid"));
	SSHD_KEYS_SERVER_FORM("$datas");

   
	
}






?>