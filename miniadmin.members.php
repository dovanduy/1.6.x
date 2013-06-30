<?php
session_start();
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__).'ressources/class.templates.inc');
include_once(dirname(__FILE__).'ressources/class.ldap.inc');
include_once(dirname(__FILE__).'ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'ressources/class.artica.inc');
include_once(dirname(__FILE__).'ressources/class.mimedefang.inc');
include_once(dirname(__FILE__).'ressources/class.apache.inc');
include_once(dirname(__FILE__).'ressources/class.lvm.org.inc');
include_once(dirname(__FILE__).'ressources/class.user.inc');


if(!checkRights()){header("location:miniadm.messaging.php");die();}
if(isset($_GET["content"])){content();exit;}
if(isset($_GET["section-search"])){section_search();exit;}
if(isset($_GET["search-users"])){users_search();exit;}

if(isset($_GET["add-user-js"])){add_user_js();exit;}
if(isset($_GET["add-user-popup"])){add_user_popup();exit;}

main_page();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;	
}

function section_search(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	
	$ou=$_SESSION["ou"];
	$ou_encoded=base64_encode($ou);
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{new_member}", "Loadjs('$page?add-user-js=yes&ou=".urlencode($ou)."')"));
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{groups2}", "Loadjs('domains.edit.group.php?ou=$ou_encoded&js=yes')"));
	$ou=urlencode($ou);
	
	echo $boot->SearchFormGen(null,"search-users","&ou=$ou",$EXPLAIN);
	
}

function add_user_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$widownsize=725;
	$t=$_GET["t"];
	$ou=urlencode($_GET["ou"]);
	$title=$tpl->_ENGINE_parse_body("{new_member}");
	$ou=urlencode($_GET["ou"]);
	$html="YahooWin2('700','$page?add-user-popup=yes&ou=$ou','$title');";
	echo $html;	
	
}

function add_user_popup(){
	$ldap=new clladp();
	if($_GET["ou"]==null){
		senderror("{ERROR_NO_ORGANISATION_SELECTED}");
		
	}
	
	$hash=$ldap->hash_groups($_GET["ou"],1);
	$domains=$ldap->hash_get_domains_ou($_GET["ou"]);
	
	$boot=new boostrap_form();
	$boot->set_hidden("ou", $_GET["ou"]);
	$boot->set_hidden("encpass", 1);
	$boot->set_field("new_userid", "{name_the_new_account_title}", null,array("MANDATORY"=>true));
	$boot->set_field("password", "{password}", null,array("MANDATORY"=>true));
	$boot->set_field("email", "{email}", null,array("MANDATORY"=>true));
	$boot->set_field("password", "{password}", null,array("MANDATORY"=>true,"ENCODE"=>true));
	$boot->set_list("group_id", "{group}", $hash);
	$boot->set_button("{add}");
	$boot->set_CloseYahoo("YahooWin2");
	$boot->set_RefreshSearchs();
	$boot->setAjaxPage("domains.edit.user.php");
	$boot->set_formtitle("{$_GET["ou"]}:: {new_member}");
	echo $boot->Compile();
	
	
}

function users_search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$ldap=new clladp();
	if($ldap->IsKerbAuth()){users_search_directory();return;}
	$page=1;
	$t=$_GET["t"];
	$sock=new sockets();
	$EnableManageUsersTroughActiveDirectory=$sock->GET_INFO("EnableManageUsersTroughActiveDirectory");
	if(!is_numeric($EnableManageUsersTroughActiveDirectory)){$EnableManageUsersTroughActiveDirectory=0;}
	if(is_base64_encoded($_GET["ou"])){$ou_encoded=$_GET["ou"];$ou=base64_decode($_GET["ou"]);}else{$ou=$_GET["ou"];$ou_encoded=base64_encode($_GET["ou"]);}
	if($_SESSION["uid"]<>-100){$ou=$_SESSION["ou"];}
	
	if($_POST["query"]<>null){$tofind=$_POST["query"];}
	
	if($tofind==null){$tofind='*';}else{$tofind="*$tofind*";}
	$filter="(&(objectClass=userAccount)(|(cn=$tofind)(mail=$tofind)(displayName=$tofind)(uid=$tofind) (givenname=$tofind)))";
	$attrs=array("displayName","uid","mail","givenname","telephoneNumber","title","sn","mozillaSecondEmail","employeeNumber","sAMAccountName");
	
	if(!$ldap->IsOUUnderActiveDirectory($ou)){
		if($EnableManageUsersTroughActiveDirectory==1){
			$cc=new ldapAD();
			$hash=$cc->find_users($ou,$tofind);
		}else{
			$ldap=new clladp();
			$dn="ou=$ou,dc=organizations,$ldap->suffix";
			$hash=$ldap->Ldap_search($dn,$filter,$attrs,150);
		}
	}else{
		$EnableManageUsersTroughActiveDirectory=1;
		include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
		$ad=new external_ad_search();
		$hash=$ad->find_users($ou, $tofind);
	}
	
	$boot=new boostrap_form();
	$users=new user();
	
	$number=$hash["count"];
	if(!is_numeric($number)){$number=0;}
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = $number;
	$data['rows'] = array();
	
	
	$styleTD=" style='font-size:16px'";
	
	for($i=0;$i<$number;$i++){
		$userARR=$hash[$i];
	
		$uid=$userARR["uid"][0];
		if($EnableManageUsersTroughActiveDirectory==1){$uid=$userARR["samaccountname"][0];}
	
		if($uid=="squidinternalauth"){continue;}
		$js=MEMBER_JS($uid,1,1);
	
		if(($userARR["sn"][0]==null) && ($userARR["givenname"][0]==null)){$userARR["sn"][0]=$uid;}
	
		$sn=texttooltip($userARR["sn"][0],"{display}:$uid",$js,null,0,"font-size:13px");
		$givenname=texttooltip($userARR["givenname"][0],"{display}:$uid",$js,null,0,"font-size:13px");
		$title=texttooltip($userARR["title"][0],"{display}:$uid",$js,null,0,"font-size:13px");
		$mail=texttooltip($userARR["mail"][0],"{display}:$uid",$js,null,0,"font-size:13px");
		$telephonenumber=texttooltip($userARR["telephonenumber"][0],"{display}:$uid",$js,null,0,"font-size:13px");
		if($userARR["telephonenumber"][0]==null){$userARR["telephonenumber"][0]="&nbsp;";}
		if($userARR["mail"][0]==null){$userARR["mail"][0]="&nbsp;";}
	
		
		
		$dele=imgsimple("delete-24.png",null,"Loadjs('domains.delete.user.php?uid=$uid&flexRT=$t');");
		
		$link=$boot->trswitch($js);
		
		$tr[]="
		<tr id='$id'>
		<td $styleTD width=99% nowrap $link><i class='icon-user'></i>&nbsp;{$userARR["sn"][0]} {$userARR["givenname"][0]}<div><i>{$userARR["title"][0]}</i></td>
		<td $styleTD width=99% nowrap $link>{$userARR["telephonenumber"][0]}</td>
		<td $styleTD width=99% nowrap $link>{$userARR["mail"][0]}</td>
		<td width=35px align='center' nowrap>$dele</td>
		</tr>";

	
	}
	
	echo $tpl->_ENGINE_parse_body("
	
		<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th>{member}</th>
					<th>{phone}</th>
					<th>{email}</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>
				</table>";	
	
	
}


function users_search_directory(){
	$database="artica_backup";
	$search='%';
	$table="squid_ssl";
	$page=1;
	$FORCE_FILTER="AND `type`='ssl-bump-wl'";
	$t=$_GET["t"];
	$dn=urldecode($_GET["dn"]);
	$sock=new sockets();


	if($_POST["query"]<>null){$tofind=$_POST["query"];}

	if($tofind==null){$tofind='*';}else{$tofind="*$tofind*";}

	if(strpos($dn, ",")>0){$ou=$dn;}

	include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
	$ad=new external_ad_search();
	$hash=$ad->find_users($ou, $tofind,$_POST['rp']);
	$number=$hash["count"];
	if(!is_numeric($number)){$number=0;}

	$data = array();
	$data['page'] = 1;
	$data['total'] = $number;
	$data['rows'] = array();


	for($i=0;$i<$number;$i++){
		$userARR=$hash[$i];
		$dn=null;
		$uid=$userARR["uid"][0];

		if(isset($userARR["samaccountname"][0])){
			$uid=$userARR["samaccountname"][0];
		}


		if(isset($userARR["distinguishedname"][0])){
			$dn=$userARR["distinguishedname"][0];
		}

		if($uid=="squidinternalauth"){continue;}
		$js=MEMBER_JS($uid,1,1,$dn);

		if(($userARR["sn"][0]==null) && ($userARR["givenname"][0]==null)){$userARR["sn"][0]=$uid;}

		$sn=$userARR["sn"][0];
		$givenname=$userARR["givenname"][0];
		$title=$userARR["title"][0];
		$mail=$userARR["mail"][0];
		$telephonenumber=$userARR["telephonenumber"][0];
		if($userARR["telephonenumber"][0]==null){$userARR["telephonenumber"][0]="&nbsp;";}
		if($userARR["mail"][0]==null){$userARR["mail"][0]="&nbsp;";}

		$img=imgsimple("contact-24.png",null,$js);
		$href="<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='text-decoration:underline'>";


		$dele="&nbsp;";

		$data['rows'][] = array(
				'id' => $uid,
				'cell' => array(
						$img,
						"<span style='font-size:14px;color:$color'>$href{$userARR["sn"][0]} {$userARR["givenname"][0]}</a><div><i>{$userARR["title"][0]}</i></span>",
						"<span style='font-size:14px;color:$color'>{$userARR["telephonenumber"][0]}</span>",
						"<span style='font-size:14px;color:$color'>$href{$userARR["mail"][0]}</a></span>",
						$dele

				)
		);


	}



	echo json_encode($data);


}

function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$ct=new user($_SESSION["uid"]);
	$t=time();
	
	$ouencoded=base64_encode($_SESSION["ou"]);
	
	$html="
	<div class=BodyContent>
		<table style='width:100%'>
		<tr>
		<td valign='top'>$picture</td>
		<td valign='top'>
		<H1>{my_members} {organization} {$_SESSION["ou"]}</H1>
		<p>{manage_users_and_groups_ou_explain}</p>
		</td>
		</tr>
		</table>
	</div>
	<div class=BodyContent>
		<div id='anim-$t'></div>
	</div>
	
<script>
	LoadAjax('anim-$t','$page?section-search&ou=$ouencoded&end-user-interface=yes');
</script>	
	
	
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
}

function checkRights(){
	$users=new usersMenus();
	if($users->AsOrgAdmin){return true;}
	if($users->AsMessagingOrg){return true;}
}