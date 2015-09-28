<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	include_once('ressources/class.squid.inc');
	
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}
if(isset($_GET["ID-js"])){js_edit_group();exit;}
if(isset($_GET["tabs"])){tabs2();exit;}
if(isset($_GET["group"])){group_edit();exit;}

if(isset($_GET["members-js"])){members_js();exit;}
if(isset($_GET["members"])){members();exit;}
if(isset($_GET["members-search"])){members_search();exit;}
if(isset($_POST["SaveDansGUardianGroupRuleMinim"])){group_edit_save_minimal();exit;}
if(isset($_POST["groupname"])){group_edit_save();exit;}

if(isset($_GET["member-edit"])){members_edit();exit;}
if(isset($_GET["member-type-field"])){members_type_field();exit;}

if(isset($_GET["blacklist"])){blacklist();exit;}
if(isset($_GET["whitelist"])){whitelist();exit;}

if(isset($_POST["pattern"])){member_edit_save();exit;}
if(isset($_POST["member-delete"])){member_edit_del();exit;}
if(isset($_GET["explain-group-type"])){group_explain_type();exit;}
if(isset($_GET["explain-group-button"])){group_display_button();exit;}
if(isset($_GET["member-check-already-exists"])){member_check_already_exists();exit;}

tabs();


function js_edit_group(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$group=$tpl->javascript_parse_text("{group2}");
	$t=$_GET["t"];
	echo "LoadWinORG('950','dansguardian2.edit.group.php?ID={$_GET["ID-js"]}&t={$_GET["t"]}&tt={$_GET["tt"]}&yahoo={$_GET["yahoo"]}','$group::{$_GET["ID-js"]}::');";
}
function members_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$group=$tpl->javascript_parse_text("{group2}");
	$t=$_GET["t"];
	echo "LoadWinORG('950','$page?members=yes&ID={$_GET["ID"]}&t={$_GET["t"]}','$group::{$_GET["groupname"]}');";	
	
}


function tabs(){
	$tt=$_GET["tt"];
	$t=$_GET["t"];
	
	$ttt=time();
	$page=CurrentPageName();
	
	$html="
	<div id='$ttt'></div>
	
	<script>
		$('#main_filter_rule_edit_group').remove();
		LoadAjax('$ttt','$page?tabs=yes&ID={$_GET["ID"]}&t={$_GET["t"]}&tt=$tt&yahoo={$_GET["yahoo"]}');
	</script>
	
	";echo $html;
	
}



function tabs2(){
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	
	$tpl=new templates();
	$page=CurrentPageName();
	$array["group"]='{group}';
	if($_GET["ID"]>-1){
		$q=new mysql_squid_builder();
		$sql="SELECT localldap,gpid,dn FROM webfilter_group WHERE ID={$_GET["ID"]}";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){echo "<H1>$q->mysql_error</H1>";}
		$dn=$ligne["dn"];
		$array["members"]='{items}';
		$array["blacklist"]='{blacklists}';
		$array["whitelist"]='{whitelist}';
		if($ligne["localldap"]==2){unset($array["members"]);$array["members-ad"]='{members}';}
		if($ligne["localldap"]==0){
			if(preg_match("#^ExtLdap#", $dn)){
				unset($array["members"]);
			}
		}
		
		if($ligne["localldap"]==3){unset($array["members"]);}
		
	}

	$gpid=$ligne["gpid"];
	
	if(!is_numeric($t)){$t=time();}
	if(!is_numeric($tt)){$tt=time();}
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="members-ad"){
			$html[]= $tpl->_ENGINE_parse_body("<li>
					<a href=\"dansguardian2.group.membersad.php?dn=$dn&yahoo={$_GET["yahoo"]}&t=$t&tt=$tt\">
			<span style='font-size:24px'>$ligne</span></a></li>\n");
			continue;
			
		}
		
		$html[]= $tpl->_ENGINE_parse_body("<li>
				<a href=\"$page?$num=yes&ID={$_GET["ID"]}&t=$t&tt=$tt&yahoo={$_GET["yahoo"]}\">
					<span style='font-size:24px'>$ligne</span></a></li>\n");
	}
	
	
	echo build_artica_tabs($html, "main_filter_rule_edit_group");
		
	
	
}


function group_edit(){
	$explain2=null;
	$ID=$_GET["ID"];
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();	
	$sock=new sockets();
	$DISABLE_DANS_FIELDS=0;
	$button_name="{apply}";
	$adgroup=false;
	$q2=new mysql();
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	if($q2->COUNT_ROWS("adusers", "artica_backup")>0){$adgroup=true;}
	$squid=new squidbee();
	
	$Yahoo=$_GET["yahoo"];
	if($Yahoo==null){$closeYahoo="YahooWin3Hide()";}
	if($Yahoo=="LoadWinORG"){$closeYahoo="WinORGHide()";}
	
	
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	
	if($GLOBALS["VERBOSE"]){echo "<H2>EnableKerbAuth:$EnableKerbAuth LINE:".__LINE__."</H2>";}
	
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	

	if($GLOBALS["VERBOSE"]){echo "<H2>EnableKerbAuth:$EnableKerbAuth LINE:".__LINE__."</H2>";}	
	
	if(!is_numeric($t)){$t=0;}
	
	if($ID<0){$button_name="{add}";}
	
	
	if($ID>-1){
		$sql="SELECT * FROM webfilter_group WHERE ID=$ID";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		
	}
	
	$LDAP_EXTERNAL_AUTH=$squid->LDAP_EXTERNAL_AUTH;
	if(!is_numeric($LDAP_EXTERNAL_AUTH)){$LDAP_EXTERNAL_AUTH=0;}
	
	
	
	$localldap[0]="{ldap_group}";
	$localldap[1]="{virtual_group}";
	if($EnableKerbAuth==1){
		$localldap[2]="{active_directory_group}";
	}
	
	if($LDAP_EXTERNAL_AUTH==1){
		$localldap[3]="{remote_ladp_group}";
	}
	
	
	if(!is_numeric($ligne["localldap"])){$ligne["localldap"]=1;}
	$users=new usersMenus();
	
	if($ligne["localldap"]==2){
		if(preg_match("#AD:(.*?):(.+)#", $ligne["webfilter_group_dn"],$re)){
				$dnEnc=$re[2];
				$LDAPID=$re[1];
				$ad=new ActiveDirectory($LDAPID);
				$tty=$ad->ObjectProperty(base64_decode($dnEnc));
				$ligne["groupname"]=$tty["cn"];
			}
	}
	
	
	
	if(preg_match("#^ExtLdap#", $ligne["dn"])){
		$ligne["gpid"]=$ligne["dn"];
	}
	
	
	
	$bt_bt=button($button_name,"SaveDansGUardianGroupRule()",32);
	$bt_bt2=button("{apply}","SaveDansGUardianGroupRuleMinim()",32);
	$LaunchBTBrowse=1;
	$bt_browse=button("{browse}...","MemberBrowsePopup();");
	if($ID>1){if($ligne["localldap"]==2){$bt_bt=$bt_bt2;$bt_browse=null;$LaunchBTBrowse=0;}}
	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
	

	if($ID>0){
		if($ligne["localldap"]==3){
			if(preg_match("#ExtLDAP:(.+?):(.+)#", $ligne["groupname"],$re)){
				$DN=base64_decode($re[2]);
				$explain2="<div style='font-size:22px;margin-bottom:10px'>{$re[1]}
				<div style='text-align:right;font-size:16px;float:right'>$DN</div></div>";
			}
		}
	}
	
	$html="
	<div id='dansguardinMainGroupDiv' style='width:98%' class=form>
	$explain2
	<table style='width:100%' >
	<tbody>
	<tr>
		<td class=legend style='font-size:26px' nowrap>{groupname}:</td>
		<td >". Field_text("groupname-$t",$ligne["groupname"],"font-size:26px;width:95%")."</td>
		
	</tr>
	<tr>
		<td colspan=2 align='right'><span id='button-$t'>$bt_browse</span></td>
	</tr>
	<tr>
		<td class=legend style='font-size:26px'>{groupmode}:</td>
		<td>". Field_array_Hash($localldap,"localldap",$ligne["localldap"],"Checklocalldap()",null,0,
				"font-size:26px")."
		</td>

	</tr>	
	<tr>
		<td class=legend style='font-size:26px'>{groupid}:</td>
		<td>". Field_text("gpid",$ligne["gpid"],"font-size:26px;width:65px")."</td>
	</tr>		
	
	<tr>
		<td class=legend style='font-size:26px'>{enabled}:</td>
		<td>". Field_checkbox_design("enabled",1,$ligne["enabled"],"CheckEnabled$t()")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:26px'>{description}:</td>
		<td>". Field_text("description",$ligne["description"],"font-size:26px;width:95%")."</td>
	</tr>
	<tr>
		<td colspan=2><span id='explain-$t'></span></td>
	</tr>		
	<tr>
		<td colspan=2 align='right'><hr>$bt_bt</td>
	</tr>
	</tbody>
	</table>
	</div>
	<script>
	
	function Checklocalldap(){
		var ID=$ID;
		var v=document.getElementById('localldap').value;
		document.getElementById('gpid').disabled=true;
		document.getElementById('groupname-$t').disabled=false;
		if(v==0){document.getElementById('gpid').disabled=false;}
		if(v==2){
			document.getElementById('groupname-$t').disabled=true;
			var tt=document.getElementById('groupname-$t').value;
			var t2=document.getElementById('description').value;
			if(ID==-1){
				document.getElementById('groupname-$t').value='';
				if(tt.length>0){if(t2.length==0){document.getElementById('description').value=tt;}}
			}
		}
		
		if(v==3){
			document.getElementById('groupname-$t').disabled=true;
		
		}
		
		
		
		var localldap=document.getElementById('localldap').value;
		if(ID>-1){document.getElementById('localldap').disabled=true;}
		LoadAjax('explain-$t','$page?explain-group-type=yes&type='+localldap+'&t=$t&ID=$ID&LaunchBTBrowse=$LaunchBTBrowse');
		
		
		
		
	}
	
	function FillExtLdap(dn,prepend,displayname){
		document.getElementById('groupname-$t').value=displayname;
		document.getElementById('gpid').value=prepend+dn;
		YahooUserHide();
	}
	
	
	function MemberBrowsePopup(){
		var Selected=document.getElementById('localldap').value;
		var LDAP_EXTERNAL_AUTH=$LDAP_EXTERNAL_AUTH;
		
		if(Selected==2){
			Loadjs('browse-ad-groups.php?field-user=gpid&field-type=2');
			return;
		}
		
		if( Selected == 3){
			Loadjs('browse-extldap-groups.php?field-user=groupname-$t&field-type=4');
			return;
		}
		
		Loadjs('MembersBrowse.php?field-user=gpid&OnlyGroups=1&OnlyGUID=1');
	
	}
	
	
	var x_SaveDansGUardianGroupRule= function (obj) {
		var res=obj.responseText;
		var ID='$ID';
		var t=$t;
		RefreshMainFilterTable();
		$('#flexRT$t').flexReload(); 
		$('#flexRT$tt').flexReload(); 
		
		if( document.getElementById('DANSGUARDIAN_EDIT_GROUP_LIST') ){
			$('#'+document.getElementById('DANSGUARDIAN_EDIT_GROUP_LIST').value).flexReload();
		}
		if( document.getElementById('MAIN_TABLE_UFDB_GROUPS_ALL') ){
			$('#'+document.getElementById('MAIN_TABLE_UFDB_GROUPS_ALL').value).flexReload();
		}
		
	
		
		if (res.length>3){alert(res);}
		if(ID<0){ $closeYahoo;}else{RefreshTab('main_filter_rule_edit_group');}
		if(t>0){
			$('#flexRT$t').flexReload(); 
			if(IsFunctionExists('GroupsDansSearch')){GroupsDansSearch();}
		}else{
			if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
			}
		
		
	}
	
function CheckEnabled$t(){
	var ID='$ID';
	if(ID>0){SaveDansGUardianGroupRule();}
}
	
		function SaveDansGUardianGroupRule(){
		      var XHR = new XHRConnection();
		      XHR.appendData('groupname', document.getElementById('groupname-$t').value);
		      XHR.appendData('localldap', document.getElementById('localldap').value);
		      XHR.appendData('description', document.getElementById('description').value);
		      XHR.appendData('gpid', document.getElementById('gpid').value);
		      if(document.getElementById('enabled').checked){ XHR.appendData('enabled',1);}else{ XHR.appendData('enabled',0);}
		      XHR.appendData('ID','$ID');
		      XHR.sendAndLoad('$page', 'POST',x_SaveDansGUardianGroupRule);  		
		}
		
		function SaveDansGUardianGroupRuleMinim(){
		 	var XHR = new XHRConnection();
		 	XHR.appendData('ID','$ID');
			XHR.appendData('SaveDansGUardianGroupRuleMinim', document.getElementById('gpid').value);
			XHR.appendData('description', document.getElementById('description').value);
			XHR.appendData('gpid', document.getElementById('gpid').value);
			if(document.getElementById('enabled').checked){ XHR.appendData('enabled',1);}else{ XHR.appendData('enabled',0);}
		    
		    XHR.sendAndLoad('$page', 'POST',x_SaveDansGUardianGroupRule);  			
		
		} 
		
		function CheckFieldsWhenStarts(){
			var ID=$ID;
			if(ID<0){return;}
			if(document.getElementById('localldap').value==2){
				document.getElementById('localldap').disabled=true;
				document.getElementById('groupname-$t').disabled=true;
				document.getElementById('gpid').disabled=true;
			}
		
		}
		
		function CheckFields(){
			
		}
	Checklocalldap();
	CheckFieldsWhenStarts();
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function group_explain_type(){
	$t=$_GET["t"];
	$type=$_GET["type"];
	$ID=$_GET["ID"];
	$LaunchBTBrowse=$_GET["LaunchBTBrowse"];
	$page=CurrentPageName();
	$html="<div class=explain style='font-size:18px'>
			{group_explain_proxy_acls_type_{$type}}
		</div>
	<script>
		function LaunchBTBrowse$t(){
			var LaunchBTBrowse=$LaunchBTBrowse;
			if(LaunchBTBrowse==0){return;}
			LoadAjax('button-$t','$page?explain-group-button=yes&ID=$ID&type=$type');
			}
			LaunchBTBrowse$t();
	</script>
	
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}

function group_display_button(){
	
	
	$t=$_GET["t"];
	$ID=$_GET["ID"];
	$type=$_GET["type"];
	if($type==1){return;}
	
	
	if($type==3){
		echo "<div style='margin-top:10px;margin-rigth:5px'>";
		$bt_browse=button("{browse_remote_ldap_server}","MemberBrowsePopup()",18);
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body($bt_browse);
		echo "</div>";
		return;
	}
	echo "<div style='margin-top:10px;margin-rigth:5px'>";
	$bt_browse=button("{browse}...","MemberBrowsePopup()",18);
	echo "</div>";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($bt_browse);
	
}



function group_edit_save(){
	
	$ID=$_POST["ID"];
	$tpl=new templates();
	
	
	unset($_POST["ID"]);
	
	
	if($_POST["groupname"]==null){
		if($_POST["localldap"]==2){
			$dndata=$_POST["gpid"];
			if(preg_match("#AD:(.*?):(.+)#", $_POST["gpid"],$re)){
				$dnEnc=$re[2];
				$LDAPID=$re[1];
			}
			
			$_POST["gpid"]=0;
			$_POST["dn"]=$dndata;
			$ACtiveDir=new ActiveDirectory($LDAPID);
			$array=$ACtiveDir->ObjectProperty(base64_decode($dnEnc));
			$_POST["groupname"]=$array["cn"];
		}
		
		if($_POST["localldap"]==0){
			
			if($_POST["groupname"]==null){
				$gp=new groups($_POST["gpid"]);
				if($gp->groupName==null){echo $tpl->javascript_parse_text("{unable_to_resolve}:Group ID:{$_POST["gpid"]}");return;}
				$_POST["groupname"]=$gp->groupName;
			}
			
		}
		
		
		if($_POST["groupname"]==null){
			echo $tpl->javascript_parse_text("{unable_to_resolve}:".base64_decode($dnEnc));
			return;
		}
		
	}
	
	if($_POST["localldap"]==0){
		if(preg_match("#ExtLdap:(.+)#", $_POST["gpid"],$re)){
			echo "match\n";
			$_POST["dn"]=$_POST["gpid"];
			$_POST["gpid"]=0;
		}
		
	}
	
	
	
	$q=new mysql_squid_builder();	
	while (list ($num, $ligne) = each ($_POST) ){
		$fieldsAddA[]="`$num`";
		$fieldsAddB[]="'".addslashes(utf8_encode($ligne))."'";
		$fieldsEDIT[]="`$num`='".addslashes(utf8_encode($ligne))."'";
		
	}
	
	$sql_edit="UPDATE webfilter_group SET ".@implode(",", $fieldsEDIT)." WHERE ID=$ID";
	$sql_add="INSERT IGNORE INTO webfilter_group (".@implode(",", $fieldsAddA).") VALUES (".@implode(",", $fieldsAddB).")";
	
	if($ID<0){$s=$sql_add;}else{$s=$sql_edit;}
	writelogs($s,__FUNCTION__,__FILE__,__LINE__);
	$q->QUERY_SQL($s);
	 
	if(!$q->ok){echo $q->mysql_error."\n$s\n";return;}
	
	
}


function group_edit_save_minimal(){
	$ID=$_POST["ID"];
	$q=new mysql_squid_builder();
	$_POST["description"]=mysql_escape_string2($_POST["description"]);
	$sql_edit="UPDATE webfilter_group SET description='{$_POST["description"]}' 
	,enabled='{$_POST["enabled"]}' WHERE ID=$ID";
	$q->QUERY_SQL($sql_edit);
	
	if(!$q->ok){echo $q->mysql_error."\n$sql_edit\n";return;}
	
}

function members(){
	$group_id=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$OnlyGroups=$_GET["OnlyGroups"];
	$groups=$tpl->_ENGINE_parse_body("{adgroups}");
	$members=$tpl->_ENGINE_parse_body("{members}");
	$new_member=$tpl->_ENGINE_parse_body("{new_member}");
	$new_item=$tpl->_ENGINE_parse_body("{new_item}");
	$import=$tpl->javascript_parse_text("{import}");
	$memberssearch="{display: '$members', name : 'members'},";
	if($OnlyGroups==1){$memberssearch=null;}
	
	$q=new mysql_squid_builder();
	$sql="SELECT groupname FROM webfilter_group WHERE ID={$_GET["ID"]}";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$group_text=$ligne["groupname"];
	$group_textenc=urlencode($group_text);
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$new_item</strong>', bclass: 'Add', onpress : DansGuardianNewMember},
	{name: '<strong style=font-size:18px>$import</strong>', bclass: 'Down', onpress : DansGuardianImportMember},
	
		],";		
	
	$html="
	
	
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	<script>
var tmp$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?members-search=yes&group_id=$group_id',
	dataType: 'json',
	colModel : [
		{display: '<span style=font-size:20px>$members</span>', name : 'pattern', width : 722, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'select', width : 65, sortable : false, align: 'left'},
		],$buttons
	
	searchitems : [
		{display: '$members', name : 'pattern'}
		
		
		],
	sortname: 'pattern',
	sortorder: 'desc',
	usepager: true,
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 300,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});
	function DansGuardianNewMember(){
		DansGuardianEditMember(-1);
	}
	
	function DansGuardianImportMember(){
		Loadjs('dansguardian2.import.items.php?group_id=$group_id&group_text=$group_textenc&tableid=flexRT$t')
	
	}
	

	function DansGuardianEditMember(ID,rname){
		YahooWin5('435','$page?member-edit='+ID+'&ID='+ID+'&group_id=$group_id&t=$t','$group_text::'+ID+'::'+rname);
	}
		
	var x_DansGuardianDeleteMember= function (obj) {
		var res=obj.responseText;
		var ID='$ID';
		if (res.length>3){alert(res);}
		RefreshMainFilterTable();
		if(document.getElementById('row'+tmp$t)){ $('#row'+tmp$t).remove();}else{ $('#flexRT$t').flexReload(); }
		if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
	}
	
	function DansGuardianDeleteMember(ID,md){
		      var XHR = new XHRConnection();
		      XHR.appendData('member-delete', ID);
		      AnimateDiv('dansguardian2-members-list');
		      XHR.sendAndLoad('$page', 'POST',x_DansGuardianDeleteMember);  		
		}
</script>";
	echo $tpl->_ENGINE_parse_body($html);	

	
	
}
function members_search(){
	$group_id=$_GET["group_id"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();	
	$search='%';
	$table="webfilter_members";
	$page=1;
	$FORCE_FILTER=" AND groupid=$group_id";
	
	if($q->COUNT_ROWS($table,'artica_events')==0){json_error_show("No data");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,'artica_events'));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,'artica_events');
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);}	
	if(mysql_num_rows($results)==0){
		json_error_show("no data");
	}
	
	//if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	
	$sock=new sockets();
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$CountDeMembers=0;
		$md=md5(serialize($ligne));
		$select=imgsimple("32-parameters.png",null,"DansGuardianEditMember('{$ligne["ID"]}','{$ligne["pattern"]}')");
		$delete=imgsimple("delete-32.png",null,"DansGuardianDeleteMember('{$ligne["ID"]}','$md')");
		$color="black";
		if($ligne["enabled"]==0){$color="#8a8a8a";}

		
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array(
			
			"<a href=\"javascript:blur();\" OnClick=\"javascript:DansGuardianEditMember('{$ligne["ID"]}','{$ligne["pattern"]}')\"
			style='font-size:20px;color:$color'>{$ligne["pattern"]}</a></span>",
			"<center style='font-size:12px;'>$delete</a></span>",
			)
		);
	}
	
	
echo json_encode($data);		

}

function member_check_already_exists(){
	$tpl=new templates();
	$pattern=$_GET["member-check-already-exists"];
	$q=new mysql_squid_builder();
	$pattern=str_replace("_","%",$pattern);
	echo "<div style='font-size:16px'>$pattern</div>";
	$sql="SELECT groupid FROM webfilter_members WHERE `pattern` LIKE '%$pattern%'";
	$results=$q->QUERY_SQL($sql);

	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		if(isset($AL[$ligne["groupid"]])){continue;}
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilter_group WHERE ID={$ligne["groupid"]}"));
		echo $tpl->_ENGINE_parse_body("<li style='font-size:16px'>{alreadyexists}: {$ligne2["groupname"]}</li>");
		$AL[$ligne["groupid"]]=true;
	}
	
	
}



function members_edit(){
	$ID=$_GET["member-edit"];
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();		
	$membertype[0]="{ipaddr}";
	$membertype[2]="{cdir}";
	$membertype[1]="{username}";
	$button_name="{apply}";
	if($ID<0){$button_name="{add}";}
	$t=$_GET["t"];
	$tt=time();
	if($ID>-1){
		$sql="SELECT * FROM webfilter_members WHERE ID=$ID";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		
	}
	
	$users=new usersMenus();
	if(!$users->DANSGUARDIAN_INSTALLED){$DISABLE_DANS_FIELDS=1;}
	
	
	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}	
	
	$html="
	<div id='members-edit-group'  style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{enabled}:</td>
		<td>". Field_checkbox_design("member_enabled",1,$ligne["enabled"])."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{member_type}:</td>
		<td>". field_array_Hash($membertype,"membertype",$ligne["membertype"],"membertypeSwitch()",null,0,
				"font-size:22px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{member}:</td>
		<td><span id='member-type-div'></span>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("$button_name","SaveMemberType()",18)."</td>
	</tr>
	</table>
	<div id='$tt'></div>
	</div>
	<script>
	var x_SaveMemberType= function (obj) {
		var res=obj.responseText;
		var ID='$ID';
		if (res.length>3){alert(res);}
		YahooWin5Hide();
		if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
		RefreshMainFilterTable();
		$('#flexRT$t').flexReload(); 
		
		
	}
	
		function SaveMemberTypeCheck(e){
			var pp= document.getElementById('pattern').value;
			LoadAjaxTiny('$tt','$page?member-check-already-exists='+pp);
			if(checkEnter(e)){
				SaveMemberType();
			}
		}
	
		function SaveMemberType(){
		      var XHR = new XHRConnection();
		      XHR.appendData('pattern', document.getElementById('pattern').value);
		      XHR.appendData('membertype', document.getElementById('membertype').value);
		      XHR.appendData('groupid', '{$_GET["group_id"]}');	      
		      if(document.getElementById('member_enabled').checked){ XHR.appendData('enabled',1);}else{ XHR.appendData('enabled',0);}
		      XHR.appendData('ID','$ID');
		      AnimateDiv('members-edit-group');
		      XHR.sendAndLoad('$page', 'POST',x_SaveMemberType);  		
		}
		
		
	function membertypeSwitch(){
		membertype=document.getElementById('membertype').value;
		var def=escape('{$ligne["pattern"]}');
		LoadAjaxTiny('member-type-div','$page?member-type-field='+membertype+'&default='+def);
		
	}
	
	membertypeSwitch();
	</script>	
	
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function member_edit_save(){
	$ID=$_POST["ID"];
	$q=new mysql_squid_builder();
	$q->CheckTables();
	unset($_POST["ID"]);
	if($_POST["pattern"]==null){return;}
	
	while (list ($num, $ligne) = each ($_POST) ){
		$fieldsAddA[]="`$num`";
		$fieldsAddB[]="'".addslashes(utf8_encode($ligne))."'";
		$fieldsEDIT[]="`$num`='".addslashes(utf8_encode($ligne))."'";
		
	}
	
	$sql_edit="UPDATE webfilter_members SET ".@implode(",", $fieldsEDIT)." WHERE ID=$ID";
	$sql_add="INSERT IGNORE INTO webfilter_members (".@implode(",", $fieldsAddA).") VALUES (".@implode(",", $fieldsAddB).")";
	
	if($ID<0){$s=$sql_add;}else{$s=$sql_edit;}
	$q->QUERY_SQL($s);
	 
	if(!$q->ok){echo $q->mysql_error."\n$s\n";return;}
	$sock=new sockets();
	$sock->getFrameWork("webfilter.php?compile-rules=yes");
	
	
}

function member_edit_del(){
	$ID=$_POST["member-delete"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM webfilter_members WHERE ID='$ID'");	
	if(!$q->ok){echo $q->mysql_error."\n$s\n";return;}
	$sock=new sockets();
	$sock->getFrameWork("webfilter.php?compile-rules=yes");
}

function members_type_field(){
	$tpl=new templates();
	
	
	$script="<script>document.getElementById('pattern').focus();</script>";
	
	// $name,$value=null,$style=null,$class=null,$OnChange=null,$help=null,$helpInside=false,$jsPressKey=null,$DISABLED=false,$OnClick=null
	if($_GET["member-type-field"]==0){echo field_ipv4("pattern", $_GET["default"],"font-size:22px",false,"OnKeyPress=\"javascript:SaveMemberTypeCheck(event)\"").$script;}
	if($_GET["member-type-field"]==1){echo Field_text("pattern", $_GET["default"],"font-size:22px",null,null,null,false,"SaveMemberTypeCheck(event)").$script;}
	if($_GET["member-type-field"]==2){echo field_ipv4_cdir("pattern", $_GET["default"],"font-size:22px",false,"OnKeyPress=\"javascript:SaveMemberTypeCheck(event)\"").$script;}
	
}

function blacklist(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$dans=new dansguardian_rules();

	$sql="SELECT webfilter_blks.category FROM webfilter_assoc_groups,webfilter_blks WHERE 
		webfilter_blks.modeblk=0 
		AND webfilter_blks.webfilter_id=webfilter_assoc_groups.webfilter_id 
		AND webfilter_assoc_groups.group_id=$ID
		";
	
$html="<center>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th width=99% align='center'>{blacklist}</th>
	</tr>
</thead>
<tbody class='tbody'>";
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><code style='font-size:11px'>$sql</code>";}
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		if(isset($already[$ligne["category"]])){continue;}
		$CountDeMembers=0;
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$color="black";
		
	
		$html=$html."
		<tr class=$classtr>
			<td style='font-size:14px;font-weight:bold;color:$color'>{$ligne["category"]}<div style='font-size:11px'>{$dans->array_blacksites[$ligne["category"]]}</div></td>
		</tr>
		";
		$already[$ligne["category"]]=true;
	}
	
	$html=$html."</tbody></table>";
	

	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function whitelist(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$dans=new dansguardian_rules();

	$sql="SELECT webfilter_blks.category FROM webfilter_assoc_groups,webfilter_blks WHERE 
		webfilter_blks.modeblk=1 
		AND webfilter_blks.webfilter_id=webfilter_assoc_groups.webfilter_id 
		AND webfilter_assoc_groups.group_id=$ID
		";
	
$html="<center>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th width=99% align='center'>{whitelist}</th>
	</tr>
</thead>
<tbody class='tbody'>";
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><code style='font-size:11px'>$sql</code>";}
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		if(isset($already[$ligne["category"]])){continue;}
		$CountDeMembers=0;
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$color="black";
		
	
		$html=$html."
		<tr class=$classtr>
			<td style='font-size:14px;font-weight:bold;color:$color'>{$ligne["category"]}<div style='font-size:11px'>{$dans->array_blacksites[$ligne["category"]]}</div></td>
		</tr>
		";
		$already[$ligne["category"]]=true;
	}
	
	$html=$html."</tbody></table>";

	echo $tpl->_ENGINE_parse_body($html);
	
	
}