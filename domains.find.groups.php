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
	$title=$tpl->_ENGINE_parse_body("{groups}&nbsp;&raquo;$ou");
$html="
	function {$prefix}Load(){
		YahooWin(570,'$page?popup=yes&ou=$ou_encrypted','$title');
	
	}
	
	{$prefix}Load();
	";
	echo $html;
}

function popup(){
$tpl=new templates();
$page=CurrentPageName();
$group=$tpl->_ENGINE_parse_body("{group}");
$members=$tpl->_ENGINE_parse_body("{members}");
$description=$tpl->_ENGINE_parse_body("{description}");
$t=time();
$html="
<span id='DomainsGroupFindPopupDiv'></span>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?search=yes&ou={$_GET["ou"]}',
	dataType: 'json',
	colModel : [
		{display: '$group', name : 'member', width : 183, sortable : false, align: 'left'},	
		{display: '$members', name : 'email', width :71, sortable : false, align: 'center'},
		{display: '$description', name : 'desc', width : 203, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'del', width : 31, sortable : true, align: 'center'},
		
		],
	$buttons
	searchitems : [
		{display: '$group', name : 'find-member'},
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
	function DomainsGroupFindPopupDivRefresh(){
		$('#flexRT$t').flexReload();
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
	$tofind=str_replace(".",'\.',$tofind);
	$tofind=str_replace("*",'.*?',$tofind);
	
	
	writelogs("FIND $tofind IN OU \"$ou\"",__FUNCTION__,__FILE__,__LINE__);

	if($EnableManageUsersTroughActiveDirectory==1){
			$GLOBALS["NOUSERSCOUNT"]=true;
			$ldap=new ldapAD();
			writelogs("[$tofind]: ->hash_get_groups_from_ou_mysql($ou,$tofind) ",__FUNCTION__,__FILE__);
			$hash=$ldap->hash_get_groups_from_ou_mysql($ou,$tofind,true);
	}else{
		$ldap=new clladp();
		$hash=$ldap->hash_groups($ou,1);
		
	}	
	
	$number=count($hash);
	$data = array();
	$data['page'] = 0;
	$data['total'] = $number;
	$data['rows'] = array();		
	
	$styla="style='font-size:14px;text-decoration:underline;font-weight:bold'";
	$styleNum="style='font-size:16px;font-weight:bold'";
if(is_array($hash)){
			while (list ($num, $line) = each ($hash)){
				if(strtolower($line)=='default_group'){continue;}
				if(strlen($search)>2){if(!preg_match("#$search#",$line)){continue;}}
				
				$text=null;
				$js="javascript:Loadjs('domains.edit.group.php?js=yes&group-id=$num&ou={$_GET["ou"]}&encoded=yes')";
				$delete=imgtootltip("delete-24.png","{delete} $num","Loadjs('domains.delete.group.php?gpid=$num')");
				if(!$GLOBALS["NOUSERSCOUNT"]){
					$delete="&nbsp;";
					$gp=new groups($num);
					$members=count($gp->members_array);	
					if($gp->description<>null){$text=$gp->description;}
					$data['rows'][] =array(
					'id' => $line,
					'cell' => array("<a href=\"javascript:blur();\" OnClick=\"$js\" $styla>$line</a>"
					,"<span $styleNum>$members</span>",
					"<span style='font-size:14px'>$text</span>",$delete )
					);					
					
					
				}else{
					
					
					if(is_array($line)){
						if($line["description"]<>null){$text=$line["description"];}	
						
					$data['rows'][] =array(
						'id' => $line,
						'cell' => array("<a href=\"javascript:blur();\" OnClick=\"$js\" $styla>$line</a>"
						,"<span $styleNum>{$line["UsersCount"]}</span>",
						"<span style='font-size:14px'>$text</span>",$delete )
						);							
						
						
						
					}else{
						
						$data['rows'][] =array(
						'id' => $line,
						'cell' => array("<a href=\"javascript:blur();\" OnClick=\"$js\" $styla>$line</a>"
						,"<span $styleNum>?</span>",
						"<span style='font-size:14px'></span>",$delete )
						);	
						
					}
				}
			}
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