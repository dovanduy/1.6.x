<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.user.inc');
	
	//if(count($_POST)>0)
	$usersmenus=new usersMenus();
	if(!$usersmenus->AllowAddUsers){
		writelogs("Wrong account : no AllowAddUsers privileges",__FUNCTION__,__FILE__);
		if(isset($_GET["js"])){
			$tpl=new templates();
			$error="{ERROR_NO_PRIVS}";
			echo $tpl->_ENGINE_parse_body("alert('$error')");
			die();
		}
		header("location:domains.manage.org.index.php?ou={$_GET["ou"]}");
		}
		
		if(isset($_GET["popup"])){popup();exit;}
		if(isset($_GET["find-member"])){echo find_member();exit;}
		if(isset($_GET["search"])){echo find_member();exit;}
		
js();


function js(){
	
	$page=CurrentPageName();
	$prefix=str_replace('.',"_",$page);
	$tpl=new templates();
	
	$find=$tpl->_ENGINE_parse_body("{find}");
	if($_GET["encoded"]=="yes"){$_GET["ou"]=base64_decode($_GET["ou"]);}
	
	$ou=$_GET["ou"];
	$ou_encrypted=base64_encode($ou);
	$title=$tpl->_ENGINE_parse_body("{find_members}&nbsp;&raquo;$ou");
$html="
	function {$prefix}Load(){
		YahooWin(570,'$page?popup=yes&ou=$ou_encrypted','$title');
	
	}
	
var x_FIndMember= function (obj) {
				var results=obj.responseText;
				document.getElementById('search-results').innerHTML=results;
			}	
	
	function FIndMember(){
		var XHR = new XHRConnection();
		var pattern=document.getElementById('find-member').value;
		document.getElementById('search-results').innerHTML='<center><H2>$find<br>'+pattern+'</H2></center><hr><center><img src=\"img/wait_verybig.gif\"></center>';
		XHR.appendData('find-member',pattern);
		XHR.appendData('ou','$ou');
		XHR.sendAndLoad('$page', 'GET',x_FIndMember);	
	}
	
	function FindMemberPress(e){
		if(checkEnter(e)){FIndMember();}
	}
	
	{$prefix}Load();
	
	";
	echo $html;
}

function popup(){
$tpl=new templates();
$page=CurrentPageName();
$member=$tpl->_ENGINE_parse_body("{members}");
$email=$tpl->_ENGINE_parse_body("{email}");
$phone=$tpl->_ENGINE_parse_body("{phone}");	
$new_member=$tpl->_ENGINE_parse_body("{new_member}");
$t=time();


	$buttons="
	buttons : [
	{name: '$new_member', bclass: 'add', onpress : NewMember$t},
	
	
	],";	

$html="
<span id='DomainsUsersFindPopupDiv'></span>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?search=yes&ou={$_GET["ou"]}',
	dataType: 'json',
	colModel : [
		{display: '$member', name : 'member', width : 165, sortable : false, align: 'left'},	
		{display: '$email', name : 'email', width :192, sortable : false, align: 'left'},
		{display: '$phone', name : 'description', width :95, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'enabled', width : 31, sortable : true, align: 'center'},
		
		],
	$buttons
	searchitems : [
		{display: '$member', name : 'find-member'},
		],
	sortname: 'pattern',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 550,
	height: 350,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});
	function DomainsUsersFindPopupDivRefresh(){
		$('#flexRT$t').flexReload();
	}
	
	function NewMember$t(){
		Loadjs('create-user.php?&ou={$_GET["ou"]}&t=$t');
	}


</script>

";	
	echo $html;
	

}

function find_member(){
	
	if($_POST["qtype"]=="find-member"){
		$tofind=$_POST["query"];
	}
	
	
	if($_SESSION["uid"]==-100){$ou=$_GET["ou"];}else{$ou=$_SESSION["ou"];}
	
	$sock=new sockets();
	if(is_base64_encoded($ou)){$ou=base64_decode($ou);}
	if($tofind==null){$tofind='*';}else{$tofind="*$tofind*";}
	$tofind=str_replace('***','*',$tofind);
	$tofind=str_replace('**','*',$tofind);
	$tofind=str_replace('**','*',$tofind);
	$EnableManageUsersTroughActiveDirectory=$sock->GET_INFO("EnableManageUsersTroughActiveDirectory");
	if(!is_numeric($EnableManageUsersTroughActiveDirectory)){$EnableManageUsersTroughActiveDirectory=0;}	
	
	
	
	writelogs("FIND $tofind IN OU \"$ou\"",__FUNCTION__,__FILE__,__LINE__);
	

	
	if($EnableManageUsersTroughActiveDirectory==1){
		$cc=new ldapAD();
		$hash=$cc->find_users($ou,$tofind);
		
	}else{
		$ldap=new clladp();
		$filter="(&(objectClass=userAccount)(|(cn=$tofind)(mail=$tofind)(displayName=$tofind)(uid=$tofind) (givenname=$tofind) ))";
		$attrs=array("displayName","uid","mail","givenname","telephoneNumber","title","sn","mozillaSecondEmail","employeeNumber","sAMAccountName");
		$dn="ou=$ou,dc=organizations,$ldap->suffix";		
		$hash=$ldap->Ldap_search($dn,$filter,$attrs,20);
	}
	
	
	
	$users=new user();
	
	$number=$hash["count"];
	$data = array();
	$data['page'] = 0;
	$data['total'] = $number;
	$data['rows'] = array();	
	
	
	
	for($i=0;$i<$number;$i++){
		$user=$hash[$i];
		$data['rows'][] =formatUser($user,$ldap->EnableManageUsersTroughActiveDirectory); 
	}
	
	
echo json_encode($data);		

}


function formatUser($hash,$EnableManageUsersTroughActiveDirectory=false){
	
	$uid=$hash["uid"][0];
	if($EnableManageUsersTroughActiveDirectory){
		$uid=$hash["samaccountname"][0];
	}	
	
	if($hash["displayname"][0]==null){$hash["displayname"][0]=$uid;}
	
	$html="<table style='width:99%' class=form>
	<tr>
		<td colspan=2>
			<span style='font-size:14px;font-weight:bold;text-transform:capitalize'>{$hash["displayname"][0]}</span>&nbsp;-&nbsp;
			<span style='font-size:10px;font-weight:bold;text-transform:capitalize'>{$hash["sn"][0]}&nbsp;{$hash["givenname"][0]}</span>
			
			<hr style='border:1px solid #FFF;margin:3px'>
			</td>
	</tr>
	<tr>
		<td align='right'><span style='font-size:10px;font-weight:bold'>{$hash["title"][0]}</span>&nbsp;|&nbsp;{$hash["mail"][0]}&nbsp;|&nbsp;{$hash["telephonenumber"][0]}
	</table>
	
	";
	

	$js=MEMBER_JS($uid,1);
	$delete=imgtootltip("delete-24.png", "$uid<hr>{delete_this_user_text}", "Loadjs('domains.delete.user.php?uid=$uid')");
	
	return 		array(
		'id' => $uid,
		'cell' => array("<a href=\"javascript:blur();\" OnClick=\"$js\" style='font-size:14px;text-decoration:underline'>{$hash["displayname"][0]}</a>"
		,"<span style='font-size:14px'>{$hash["mail"][0]}</span>",
		"<span style='font-size:14px'>{$hash["telephonenumber"][0]}</span>",$delete )
		);
	
	
}
	
	


?>