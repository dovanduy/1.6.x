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
	$dn=urlencode($_GET["dn"]);
	$find=$tpl->_ENGINE_parse_body("{find}");
	if($_GET["encoded"]=="yes"){$_GET["ou"]=base64_decode($_GET["ou"]);}
	
	$ou=$_GET["ou"];
	$ou_encrypted=base64_encode($ou);
	$title=$tpl->_ENGINE_parse_body("{groups}&nbsp;&raquo;$ou");
$html="
	function {$prefix}Load(){
		YahooWin(570,'$page?popup=yes&ou=$ou_encrypted&t={$_GET["t"]}&dn=$dn','$title');
	
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
$new_group=$tpl->_ENGINE_parse_body("{new_group}");
$tt=$_GET["t"];
if(!is_numeric($tt)){$tt=time();}
$t=time();
$ldap=new clladp();
$ou=$_GET["ou"];
if(is_base64_encoded($ou)){$ou=base64_decode($ou);}
$dn=urlencode($_GET["dn"]);
$buttons="
buttons : [
{name: '<b>$new_group</b>', bclass: 'add', onpress : CreateGroup$t},
],";
if($ldap->IsOUUnderActiveDirectory($ou)){$buttons=null;}

$html="
<span id='DomainsGroupFindPopupDiv'></span>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?search=yes&ou={$_GET["ou"]}&t=$t&tt=$tt&dn=$dn',
	dataType: 'json',
	colModel : [
		{display: '$group', name : 'member', width : 183, sortable : false, align: 'left'},	
		{display: '$members', name : 'email', width :71, sortable : false, align: 'center'},
		{display: '$description', name : 'desc', width : 199, sortable : true, align: 'left'},
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
	
	function CreateGroup$t(){
		Loadjs('domains.edit.group.php?popup-add-group=yes&ou={$_GET["ou"]}&t=$t&tt=$tt');
	}


</script>

";	
	echo $html;
	

}

function find_member_active_directory(){
if($_POST["query"]<>null){$search=$_POST["query"];}
	$GLOBALS["NOUSERSCOUNT"]=false;
	$ou=base64_decode($_GET["ou"]);
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	if($_POST["qtype"]=="group"){$_POST["qtype"]="groupname";}
	if($_POST["qtype"]=="find-member"){$_POST["qtype"]="groupname";}
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	
	
	if($_POST["sortname"]=="pattern"){$_POST["sortname"]="groupname";}
	$error="No dn";
	if(strlen($_GET["dn"])>0){
		$table="activedirectory_groupsNames";
		$database="artica_backup";
		$_GET["dn"]=urldecode($_GET["dn"]);
		$FORCE_FILTER="AND oudn='{$_GET["dn"]}'";
		$error=null;
	}
	
	$styla="style='font-size:14px;text-decoration:underline;font-weight:bold'";
	$styleNum="style='font-size:16px;font-weight:bold'";
	
	$q=new mysql();
	
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("$table: No item $error",1);}
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($search<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);	
	if(!$q->ok){json_error_show("$q->mysql_error<br>\n$sql",1);}
	if(mysql_num_rows($results)==0){
		json_error_show("No item: $sql",1);
	}
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = $total;
	$data['rows'] = array();
	
	
	
	$_GET["dn"]=urlencode($_GET["dn"]);
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$text=utf8_encode($ligne["description"]);
		$dn=urlencode($ligne["dn"]);
			$js="javascript:Loadjs('domains.edit.group.php?js=yes&group-id=$dn&ou={$_GET["ou"]}&dn={$_GET["dn"]}&encoded=yes&tt=$t&ttt=$tt')";
					$data['rows'][] =array(
						'id' => md5($ligne["groupname"]),
						'cell' => array("<a href=\"javascript:blur();\" OnClick=\"$js\" $styla>{$ligne["groupname"]}</a>"
						,"<span $styleNum>{$ligne["UsersCount"]}</span>",
						"<span style='font-size:14px'>$text</span>","&nbsp;" )
						);		
	

	}
	
	
	
	echo json_encode($data);	
	
	
}


function find_member(){
	
	$ldap=new clladp();
	if($ldap->IsKerbAuth()){
		
		find_member_active_directory();
		return;
	}
	
	if($_POST["qtype"]=="find-member"){
		$tofind=$_POST["query"];
	}
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	
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
	
	$ldap=new clladp();
	writelogs("FIND $tofind IN OU \"$ou\"",__FUNCTION__,__FILE__,__LINE__);
	if(!$ldap->IsOUUnderActiveDirectory($ou)){
		if($EnableManageUsersTroughActiveDirectory==1){
				$GLOBALS["NOUSERSCOUNT"]=true;
				$ldap=new ldapAD();
				writelogs("[$tofind]: ->hash_get_groups_from_ou_mysql($ou,$tofind) ",__FUNCTION__,__FILE__);
				$hash=$ldap->hash_get_groups_from_ou_mysql($ou,$tofind,true);
		}else{
			$ldap=new clladp();
			$hash=$ldap->hash_groups($ou,1);
			
		}
	}else{
		$hash=find_member_active_directory();
		$ldap->EnableManageUsersTroughActiveDirectory=true;
		$GLOBALS["NOUSERSCOUNT"]=true;
	}	
	
	$number=count($hash);
	$data = array();
	$data['page'] = 0;
	$data['total'] = $number;
	$data['rows'] = array();		
	
	$styla="style='font-size:14px;text-decoration:underline;font-weight:bold'";
	$styleNum="style='font-size:16px;font-weight:bold'";
	$search=string_to_flexregex();
if(is_array($hash)){
			while (list ($num, $line) = each ($hash)){
				if(strtolower($line)=='default_group'){continue;}
				if(strlen($search)>2){if(!preg_match("#$search#",$line)){continue;}}
				
				$text=null;
				$js="javascript:Loadjs('domains.edit.group.php?js=yes&group-id=$num&ou={$_GET["ou"]}&encoded=yes&tt=$t&ttt=$tt')";
				$delete=imgsimple("delete-24.png","{delete} $num","Loadjs('domains.delete.group.php?gpid=$num')");
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
						if(strlen($search)>2){if(!preg_match("#$search#",$line["groupname"])){continue;}}
						if(!is_numeric($line["gid"])){
							$delete=imgsimple("delete-24-grey.png");
						}
						
						$js="javascript:Loadjs('domains.edit.group.php?js=yes&group-id={$line["gid"]}&ou={$_GET["ou"]}&encoded=yes&tt=$t&ttt=$tt')";
					$data['rows'][] =array(
						'id' => md5($line["groupname"]),
						'cell' => array("<a href=\"javascript:blur();\" OnClick=\"$js\" $styla>{$line["groupname"]}</a>"
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