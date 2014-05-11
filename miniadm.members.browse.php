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


if(isset($_GET["section-tab"])){section_tab();exit;}
if(isset($_GET["section-search-ldap"])){section_search_ldap();exit;}
if(isset($_GET["search-users-ldap"])){users_search_ldap();exit;}

if(isset($_GET["section-search-ad"])){section_search_ad();exit;}
if(isset($_GET["search-users-ad"])){users_search_ad();exit;}




js();
function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$servername=$_GET["servername"];
	$add="popup-webserver";

	$title="{browse}::{members}";
	$title=$tpl->javascript_parse_text($title);
	$callback=urlencode($_GET["CallBack"]);
	echo "YahooWinBrowse(650,'$page?section-tab=yes&CallBack=$callback','$title')";

}

function section_tab(){
	$boot=new boostrap_form();
	$users=new usersMenus();
	$callback=urlencode($_GET["CallBack"]);
	if(!$users->AsAnAdministratorGeneric){senderror("no privs");}
	$page=CurrentPageName();
	$tpl=new templates();
	$array["{members} LDAP"]="$page?section-search-ldap=yes&CallBack=$callback";
	
	$ldap=new clladp();
	if($ldap->IsKerbAuth()){
		$array["{members} Active Directory"]="$page?section-search-ad=yes&CallBack=$callback";
	}
	
	echo $boot->build_tab($array);	
	
}

function section_search_ldap(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	$ou=$_SESSION["ou"];
	$callback=urlencode($_GET["CallBack"]);
	$ou=urlencode($ou);
	echo $boot->SearchFormGen(null,"search-users-ldap","&ou=$ou&CallBack=$callback");
	
}
function section_search_ad(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	$ou=$_SESSION["ou"];
	$callback=urlencode($_GET["CallBack"]);
	echo $boot->SearchFormGen(null,"search-users-ad","&CallBack=$callback");	
	
}
function users_search_ad(){
	include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
	$p=new external_ad_search();
	if($_GET["search-users-ad"]<>null){$tofind=$_GET["search-users-ad"];}
	if($tofind==null){$tofind='*';}else{$tofind="*$tofind*";}
	$hash=$p->find_users(null,$tofind,250);	
	$tpl=new templates();
	$MyPage=CurrentPageName();
		
	
	
	$boot=new boostrap_form();
	$users=new user();
	$number=$hash["count"];
	
	
	$styleTD=" style='font-size:16px'";
	
	for($i=0;$i<$number;$i++){
		$userARR=$hash[$i];
		$img="user-32.png";
		$uid=$userARR["samaccountname"][0];
		if(strpos($uid, "$")>0){$img="computer-32.png";}
		if($userARR["displayname"][0]==null){$userARR["displayname"][0]=$uid;}
		$js=MEMBER_JS($uid,1,1);
	
		if(($userARR["sn"][0]==null) && ($userARR["givenname"][0]==null)){$userARR["sn"][0]=$uid;}
	
		$sn=texttooltip($userARR["sn"][0],"{display}:$uid",$js,null,0,"font-size:13px");
		$givenname=texttooltip($userARR["givenname"][0],"{display}:$uid",$js,null,0,"font-size:13px");
		$title=texttooltip($userARR["title"][0],"{display}:$uid",$js,null,0,"font-size:13px");
		$mail=texttooltip($userARR["mail"][0],"{display}:$uid",$js,null,0,"font-size:13px");
		$telephonenumber=texttooltip($userARR["telephonenumber"][0],"{display}:$uid",$js,null,0,"font-size:13px");
		if($userARR["telephonenumber"][0]==null){$userARR["telephonenumber"][0]="&nbsp;";}
		if($userARR["mail"][0]==null){$userARR["mail"][0]="&nbsp;";}

		if($_GET["CallBack"]<>null){
			$link=$boot->trswitch("{$_GET["CallBack"]}('$uid')");
		}else{
			$link=$boot->trswitch($js);
		}
		
		$tr[]="
		<tr id='$id'>
		
		<td $styleTD width=1% nowrap $link><img src='img/$img'></td>
		<td $styleTD width=99% nowrap $link><i class='icon-user'></i>&nbsp;{$userARR["sn"][0]} {$userARR["givenname"][0]}<div><i>{$userARR["title"][0]}</i></td>
		<td $styleTD width=1% nowrap $link>{$userARR["telephonenumber"][0]}</td>
		<td $styleTD width=1% nowrap $link>{$userARR["mail"][0]}</td>
		</tr>";
		
		
		}
		if($tofind<>null){$tofind=" ($tofind)";}
		echo $tpl->_ENGINE_parse_body("
		
				<table class='table table-bordered table-hover'>
		
			<thead>
				<tr>
					<th colspan=2>{member}$tofind</th>
					<th>{phone}</th>
					<th>{email}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>
				</table>";
	
}




function users_search_ldap(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$ldap=new clladp();
	$ou=$_GET["ou"];
	
	$dn="ou=$ou,dc=organizations,$ldap->suffix";
	
	
	if($_GET["search-users-ldap"]<>null){$tofind=$_GET["search-users-ldap"];}
	
	if($tofind==null){$tofind='*';}else{$tofind="*$tofind*";}
	$filter="(&(objectClass=userAccount)(|(cn=$tofind)(mail=$tofind)(displayName=$tofind)(uid=$tofind) (givenname=$tofind)))";
	$attrs=array("displayName","uid","mail","givenname","telephoneNumber","title","sn","mozillaSecondEmail","employeeNumber","sAMAccountName");
	$hash=$ldap->Ldap_search($dn,$filter,$attrs,550);
	
	$boot=new boostrap_form();
	$users=new user();
	$number=$hash["count"];
	
	
	$styleTD=" style='font-size:16px'";
	
	for($i=0;$i<$number;$i++){
		$userARR=$hash[$i];
	
		$uid=$userARR["uid"][0];
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
	
		
		
		
		
		$link=$boot->trswitch("{$_GET["CallBack"]}('$uid')");
		
		$tr[]="
		<tr id='$id'>
		<td $styleTD width=99% nowrap $link><i class='icon-user'></i>&nbsp;{$userARR["sn"][0]} {$userARR["givenname"][0]}<div><i>{$userARR["title"][0]}</i></td>
		<td $styleTD width=99% nowrap $link>{$userARR["telephonenumber"][0]}</td>
		<td $styleTD width=99% nowrap $link>{$userARR["mail"][0]}</td>
		</tr>";

	
	}
	
	echo $tpl->_ENGINE_parse_body("
	
		<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th>{member}</th>
					<th>{phone}</th>
					<th>{email}</th>
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