<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.active.directory.inc');
	include_once("ressources/class.harddrive.inc");
	include_once("ressources/class.ldap-extern.inc");
	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["search-groups"])){popup_search();exit;}
	
	
	js();

function js(){
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$t=time();
	$title=$_GET["ou"];
	$_GET["ou"]=urlencode($_GET["ou"]);
	if(!is_numeric($_GET["ADID"])){$_GET["ADID"]=0;}
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{browse_remote_ldap_server}");
	$DN=urlencode($_GET["DN"]);
	$ldap=new ldap_extern();
	$hash=$ldap->DNInfos($_GET["DN"]);
	if(isset($hash[0]["cn"])){
		$adgroup=$hash[0]["cn"][0];
	}
	
	
	echo "YahooWinBrowse(577,'$page?popup=yes&DN=$DN&MainFunction={$_GET["MainFunction"]}&field-user={$_GET["field-user"]}&field-type={$_GET["field-type"]}&function={$_GET["function"]}&t=$t&CallBack2={$_GET["CallBack2"]}','Browse::$adgroup::$title');";
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	if($_GET["prepend"]==null){$_GET["prepend"]=0;}
	if($_GET["prepend-guid"]==null){$_GET["prepend-guid"]=0;}
	$OnlyGUID=$_GET["OnlyGUID"];
	$OnlyAD=$_GET["OnlyAD"];
	if(!is_numeric($OnlyGUID)){$OnlyGUID=0;}
	if(!is_numeric($OnlyAD)){$OnlyAD=0;}
	if($_GET["callback"]<>null){$callback="{$_GET["callback"]}(id,prependText,guid);WinORGHide();return;";}
	$GroupName=$tpl->_ENGINE_parse_body("{members}");
	$Members=$tpl->_ENGINE_parse_body("{members}");
	$item_add=$tpl->javascript_parse_text("{item_added}");
	$Select=$tpl->javascript_parse_text("{select}");
	$title=$tpl->javascript_parse_text("{browse_remote_ldap_server}");
	$t=time();
	$CallBack2=null;
	$DN=urlencode($_GET["DN"]);

	if($_GET["CallBack2"]<>null){
		$CallBack2="{$_GET["CallBack2"]}(base64,Name);";
	}
	$ldap=new ldap_extern();
	$hash=$ldap->DNInfos($_GET["DN"]);
	if(isset($hash[0]["cn"])){
		$adgroup=$hash[0]["cn"][0];
	}

	$html="
	<table class='table$t' style='display: none' id='table$t' style='width:99%'></table>
	<script>
	$(document).ready(function(){
	$('#table$t').flexigrid({
	url: '$page?search-groups=yes&t=$t&DN={$DN}&MainFunction={$_GET["MainFunction"]}&field-user={$_GET["field-user"]}&field-type={$_GET["field-type"]}&function={$_GET["function"]}&t=$t&CallBack2={$_GET["CallBack2"]}',
	dataType: 'json',
	colModel : [
	{display: '&nbsp;', name : 'select', width : 42, sortable : false, align: 'center'},
	{display: '$GroupName', name : 'groupname', width : 372, sortable : true, align: 'left'},
	{display: '$Select', name : 'none', width : 79, sortable : false, align: 'center'},
	],

	searchitems : [
	{display: '$GroupName', name : 'groupname'},
	],
	sortname: 'groupname',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:18px>$adgroup</span>',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true

});
});
function BrowseFindUserGroupClick(e){
if(checkEnter(e)){BrowseFindUserGroup();}
}

var x_BrowseFindUserGroup=function (obj) {
tempvalue=obj.responseText;
document.getElementById('finduserandgroupsidBrwse').innerHTML=tempvalue;
}


function BrowseFindUserGroup(){
LoadAjax('finduserandgroupsidBrwse','$page?query='+escape(document.getElementById('BrowseUserQuery').value)+'&prepend={$_GET["prepend"]}&field-user={$_GET["field-user"]}&prepend-guid={$_GET["prepend-guid"]}&OnlyUsers={$_GET["OnlyUsers"]}&OnlyGUID={$_GET["OnlyGUID"]}&organization={$_GET["organization"]}&OnlyGroups={$_GET["OnlyGroups"]}&callback={$_GET["callback"]}&NOComputers={$_GET["NOComputers"]}&Zarafa={$_GET["Zarafa"]}&OnlyAD=$OnlyAD');

}


function BrowseSelect$t(id,prependText,guid){
$callback
	var prepend={$_GET["prepend"]};
	var prepend_gid={$_GET["prepend-guid"]};
	var OnlyGUID=$OnlyGUID;
	if(document.getElementById('{$_GET["field-user"]}')){
	var selected=id;
	if(OnlyGUID==1){
	document.getElementById('{$_GET["field-user"]}').value=guid;
	WinORGHide();
	return;
}

if(prepend==1){selected=prependText+id;}
if(prepend_gid==1){
if(guid>1){
selected=prependText+id+':'+guid;
}
}
document.getElementById('{$_GET["field-user"]}').value=selected;
WinORGHide();
	}
}

function EditField$t(base64,Name){
	var fieldtype='{$_GET["field-type"]}';
	var ADID='{$_GET["ADID"]}';
	if(document.getElementById('{$_GET["field-user"]}')){
		if(fieldtype==2){
			document.getElementById('{$_GET["field-user"]}').value='AD:'+ADID+':'+base64;
			alert('$item_add mode:'+fieldtype+' - '+'`'+Name+'`');
			$CallBack2
			return;
		}
		
		if(fieldtype==3){
			document.getElementById('{$_GET["field-user"]}').value=Name;
			alert('$item_add `'+Name+' - '+'`'+Name+'`');
			$CallBack2
			return;
		}
		
		if(fieldtype==4){
			document.getElementById('{$_GET["field-user"]}').value='ExtLDAP:'+Name+':'+base64;
			document.getElementById('{$_GET["field-user"]}').disabled=true;
			alert('$item_add `'+Name+' - '+'`'+Name+'`');
			$CallBack2
			return;
		}		
		
		document.getElementById('{$_GET["field-user"]}').value=base64;
		alert('$item_add mode:'+fieldtype+' - '+'`'+Name+'`');
		$CallBack2
	}
}
</script>
";
echo $html;
}

function popup_search(){

	$icon="user-32.png";
	$ldap=new ldap_extern();
	

	if(strpos(" {$_POST["query"]}", "*")==0){$_POST["query"]="*{$_POST["query"]}*";}
	$_POST["query"]=str_replace("**", "*", $_POST["query"]);
	$_POST["query"]=str_replace("**", "*", $_POST["query"]);
	if(!is_numeric($_POST["rp"])){$_POST["rp"]=50;}
	$hash=$ldap->DNInfos($_GET["DN"]);
	if(!$ldap->ok){json_error_show($ldap->ldap_error,1);}
	if($hash[0][$ldap->ldap_filter_group_attribute]["count"]==0){json_error_show("No item",1);}

	
	$data = array();
	$data['page'] = 1;
	$data['total'] = $hash[0][$ldap->ldap_filter_group_attribute]["count"];
	$data['rows'] = array();

	
	if($_POST["rp"]>$hash[0][$ldap->ldap_filter_group_attribute]["count"]){
		$_POST["rp"]=$hash[0][$ldap->ldap_filter_group_attribute]["count"];
	}
	$attr=$ldap->ldap_filter_group_attribute;
	$searchstring=string_to_flexregex();
	$tz=0;
	for($i=0;$i<$_POST["rp"];$i++){
		
		$member=$hash[0][$attr][$i];
		if(preg_match("#^uid=(.+?),#", $member,$re)){$member=$re[1];}
		if($searchstring<>null){if(!preg_match("#$searchstring#", $member)){continue;}}
		
		
		
		$tz++;

		$js="EditField{$_GET["t"]}('$DN_base64','$GroupxSourceName');";
		if($_GET["MainFunction"]<>null){
			$js="{$_GET["MainFunction"]}('$dn');YahooWinBrowseHide();";
		}


		$image=imgsimple($icon,null,$js);
		$select=imgsimple("arrow-right-32.png",null,$js);


		$md5=md5($dn);
		$data['rows'][] = array(
				'id' => $md5,
				'cell' => array(
						"<center>$image</center>",
						"<span style='font-size:20px;'>$member</a></span>",
						"<center></center>" )
		);
	}
	$data['total'] =$tz;

	echo json_encode($data);

}