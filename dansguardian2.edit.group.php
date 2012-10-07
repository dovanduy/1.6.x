<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_GET["tabs"])){tabs2();exit;}
if(isset($_GET["group"])){group_edit();exit;}
if(isset($_GET["members"])){members();exit;}
if(isset($_GET["members-search"])){members_search();exit;}
if(isset($_POST["groupname"])){group_edit_save();exit;}

if(isset($_GET["member-edit"])){members_edit();exit;}
if(isset($_GET["member-type-field"])){members_type_field();exit;}

if(isset($_GET["blacklist"])){blacklist();exit;}
if(isset($_GET["whitelist"])){whitelist();exit;}

if(isset($_POST["pattern"])){member_edit_save();exit;}
if(isset($_POST["member-delete"])){member_edit_del();exit;}
if(isset($_GET["explain-group-type"])){group_explain_type();exit;}
if(isset($_GET["explain-group-button"])){group_display_button();exit;}

tabs();


function tabs(){
	$t=time();
	$page=CurrentPageName();
	
	$html="
	<div id='$t'></div>
	
	<script>
		$('#main_filter_rule_edit_group').remove();
		LoadAjax('$t','$page?tabs=yes&ID={$_GET["ID"]}&t={$_GET["t"]}&yahoo={$_GET["yahoo"]}');
	</script>
	
	";echo $html;
	
}



function tabs2(){
	$t=$_GET["t"];
	$tpl=new templates();
	$page=CurrentPageName();
	$array["group"]='{group}';
	if($_GET["ID"]>-1){
		$q=new mysql_squid_builder();
		$sql="SELECT localldap,gpid,dn FROM webfilter_group WHERE ID={$_GET["ID"]}";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){echo "<H1>$q->mysql_error</H1>";}
		$dn=$ligne["dn"];
		$array["members"]='{members}';
		$array["blacklist"]='{blacklists}';
		$array["whitelist"]='{whitelist}';
		if($ligne["localldap"]==2){unset($array["members"]);$array["members-ad"]='{members}';}
		
	}

	$gpid=$ligne["gpid"];
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="members-ad"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"dansguardian2.group.membersad.php?dn=$dn&yahoo={$_GET["yahoo"]}\"><span style='font-size:14px'>$ligne</span></a></li>\n");
			continue;
			
		}
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&ID={$_GET["ID"]}&t=$t&yahoo={$_GET["yahoo"]}\"><span style='font-size:14px'>$ligne</span></a></li>\n");
	}
	
	
	
	
	echo"<div id=main_filter_rule_edit_group style='width:100%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_filter_rule_edit_group').tabs();
			
			
			});
		</script>";		
	
	
}


function group_edit(){
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
	if($q2->COUNT_ROWS("adusers", "artica_backup")>0){$adgroup=true;}
	
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
	
	$localldap[0]="{ldap_group}";
	$localldap[1]="{virtual_group}";
	if($EnableKerbAuth==1){
		$localldap[2]="{active_directory_group}";
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
	
	$bt_bt=button($button_name,"SaveDansGUardianGroupRule()",16);
	$bt_browse="<input type='button' value='{browse}...' OnClick=\"javascript:MemberBrowsePopup();\" style='font-size:13px'>";
	if($ID>1){if($ligne["localldap"]==2){$bt_bt=null;$bt_browse=null;}}
	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
	$t=time();
	$html="
	<div id='dansguardinMainGroupDiv'>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend>$ID)&nbsp;{groupname}:</td>
		<td>". Field_text("groupname-$t",$ligne["groupname"],"font-size:14px;")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend>{groupmode}:</td>
		<td>". Field_array_Hash($localldap,"localldap",$ligne["localldap"],"Checklocalldap()",null,0,"font-size:14px")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend>{groupid}:</td>
		<td>". Field_text("gpid",$ligne["gpid"],"font-size:14px;width:65px")."</td>
		<td><span id='button-$t'>$bt_browse</span></td>
	</tr>		
	
	<tr>
		<td class=legend>{enabled}:</td>
		<td>". Field_checkbox("enabled",1,$ligne["enabled"])."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend>{description}:</td>
		<td><textarea name='description' id='description' style='width:100%;height:50px;overflow:auto;font-size:14px'>". $ligne["description"]."</textarea></td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan=3><span id='explain-$t'></span></td>
	</tr>		
	<tr>
		<td colspan=3 align='right'><hr>$bt_bt</td>
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
		var localldap=document.getElementById('localldap').value;
		if(ID>-1){document.getElementById('localldap').disabled=true;}
		LoadAjax('explain-$t','$page?explain-group-type=yes&type='+localldap+'&t=$t&ID=$ID');
		
		
		
		
	}
	
	
	function MemberBrowsePopup(){
		var Selected=document.getElementById('localldap').value;
		if(Selected==2){
			Loadjs('BrowseActiveDirectory.php?field-user=gpid&OnlyGroups=1&OnlyAD=1&OnlyGUID=1');
			return;
		}
		Loadjs('MembersBrowse.php?field-user=gpid&OnlyGroups=1&OnlyGUID=1');
	
	}
	
	
	var x_SaveDansGUardianGroupRule= function (obj) {
		var res=obj.responseText;
		var ID='$ID';
		var t=$t;
		if (res.length>3){alert(res);}
		if(ID<0){ $closeYahoo;}else{RefreshTab('main_filter_rule_edit_group');}
		if(t>0){
			$('#flexRT$t').flexReload(); 
			GroupsDansSearch();
		}else{
			if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
			}
		
		
	}
	
		function SaveDansGUardianGroupRule(){
		      var XHR = new XHRConnection();
		      XHR.appendData('groupname', document.getElementById('groupname-$t').value);
		      XHR.appendData('localldap', document.getElementById('localldap').value);
		      XHR.appendData('description', document.getElementById('description').value);
		      XHR.appendData('gpid', document.getElementById('gpid').value);
		      if(document.getElementById('enabled').checked){ XHR.appendData('enabled',1);}else{ XHR.appendData('enabled',0);}
		      XHR.appendData('ID','$ID');
		      AnimateDiv('dansguardinMainGroupDiv');
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
	$page=CurrentPageName();
	$html="<div class=explain style='font-size:14px'>{group_explain_proxy_acls_type_{$type}}</div>
	<script>
		LoadAjax('button-$t','$page?explain-group-button=yes&ID=$ID&type=$type');
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
	
	$bt_browse="<input type='button' value='{browse}...' OnClick=\"javascript:MemberBrowsePopup();\" style='font-size:13px'>";
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
		if($_POST["groupname"]==null){
			echo $tpl->javascript_parse_text("{unable_to_resolve}:".base64_decode($dnEnc));
			return;
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
	$memberssearch="{display: '$members', name : 'members'},";
	if($OnlyGroups==1){$memberssearch=null;}
	
	$buttons="
	buttons : [
	{name: '<b>$new_item</b>', bclass: 'Add', onpress : DansGuardianNewMember},
	
		],";		
	
	$html="
	<div class=explain>{dansguardian2_addedit_members_explain}</div>
	<div style='margin-right:-10px;margin-left:-15px'>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	</div>	
	<script>
var tmp$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?members-search=yes&group_id=$group_id',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'zDate', width :31, sortable : false, align: 'left'},
		{display: '$members', name : 'pattern', width : 556, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'select', width : 31, sortable : false, align: 'left'},
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
	width: 685,
	height: 300,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});
	function DansGuardianNewMember(){
		DansGuardianEditMember(-1);
	}
	

	function DansGuardianEditMember(ID,rname){
		YahooWin5('435','$page?member-edit='+ID+'&ID='+ID+'&group_id=$group_id&t=$t','$group_text::'+ID+'::'+rname);
	}
		
	var x_DansGuardianDeleteMember= function (obj) {
		var res=obj.responseText;
		var ID='$ID';
		if (res.length>3){alert(res);}
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
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
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
	
	//if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	
	$sock=new sockets();
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$CountDeMembers=0;
		$md=md5(serialize($ligne));
		$select=imgsimple("32-parameters.png",null,"DansGuardianEditMember('{$ligne["ID"]}','{$ligne["pattern"]}')");
		$delete=imgsimple("delete-32.png",null,"DansGuardianDeleteMember('{$ligne["ID"]}','$md')");
		$color="black";
		if($ligne["enabled"]==0){$color="#CCCCCC";}

		
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array(
			"<span style='font-size:12px;'>$select</span>",
			"<span style='font-size:16px;font-weight:bold;color:$color'>{$ligne["pattern"]}</a></span>",
			"<span style='font-size:12px;'>$delete</a></span>",
			)
		);
	}
	
	
echo json_encode($data);		

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
	
	if($ID>-1){
		$sql="SELECT * FROM webfilter_members WHERE ID=$ID";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		
	}
	
	$users=new usersMenus();
	if(!$users->DANSGUARDIAN_INSTALLED){$DISABLE_DANS_FIELDS=1;}
	
	
	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}	
	
	$html="
	<div id='members-edit-group'>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend>{enabled}:</td>
		<td>". Field_checkbox("member_enabled",1,$ligne["enabled"])."</td>
	</tr>	
	<tr>
		<td class=legend>{member_type}:</td>
		<td>". field_array_Hash($membertype,"membertype",$ligne["membertype"],"membertypeSwitch()",null,0,"font-size:14px")."</td>
	</tr>
	<tr>
		<td class=legend>{member}:</td>
		<td><span id='member-type-div'></span>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("$button_name","SaveMemberType()",16)."</td>
	</tr>
	</table>
	
	<script>
	var x_SaveMemberType= function (obj) {
		var res=obj.responseText;
		var ID='$ID';
		if (res.length>3){alert(res);}
		YahooWin5Hide();
		if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
		$('#flexRT$t').flexReload(); 
		
		
	}
	
		function SaveMemberTypeCheck(e){
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
	// $name,$value=null,$style=null,$class=null,$OnChange=null,$help=null,$helpInside=false,$jsPressKey=null,$DISABLED=false,$OnClick=null
	if($_GET["member-type-field"]==0){echo field_ipv4("pattern", $_GET["default"],"font-size:14px");}
	if($_GET["member-type-field"]==1){echo Field_text("pattern", $_GET["default"],"font-size:14px",null,null,null,false,"SaveMemberTypeCheck(event)");}
	if($_GET["member-type-field"]==2){echo field_ipv4_cdir("pattern", $_GET["default"],"font-size:14px",null,null,null,false,"SaveMemberTypeCheck(event)");}
	
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