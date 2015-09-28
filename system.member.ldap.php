<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.user.inc');
include_once('ressources/class.contacts.inc');
include_once(dirname(__FILE__)."/ressources/class.squid.inc");

if(isset($_GET["popup"])){popup();exit;}

js();


function js(){
	$page=CurrentPageName();
	$ad=new external_ad_search();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$title=$tpl->javascript_parse_text("{member}:{$_GET["uid"]}");
	echo "YahooUserHide();YahooUser('995','$page?popup=yes&uid={$_GET["uid"]}','$title')";
	
}

function popup() {
	$users=new usersMenus();
	$ct=new user($_GET["uid"]);
	$tpl=new templates();
	$title=$ct->DisplayName;
	$GRPS=$ct->GetGroups($_GET["uid"],1);
	
	
	while (list ($num, $GroupName) = each ($ct->GroupsOf) ){
			$jsGRP="Loadjs('domains.edit.group.php?js=yes&group-id=$num',true)";
		$XTRG[]="<tr>
					<td style='width:16px'><img src='img/wingroup.png'></td>
					<td style='font-size:16px'><a href=\"javascript:blur();\" OnClick=\"javascript:$jsGRP\" style='text-decoration:underline'>$GroupName</a></td>
				</tr>";
	}

	
	$editjs=MEMBER_JS($_GET["uid"],1,1);
	
	$bouton=button("{edit_member}", $editjs,18,185);
	
	
	if($users->cyrus_imapd_installed){
		include_once(dirname(__FILE__)."/ressources/class.cyrus.inc");
		$cyr = new cyrus ( );
		$RealMailBox=$cyr->IfMailBoxExists($_GET["uid"]);
		$button2="<div style='margin-top:10px'>".button("{mailbox}",
						"Loadjs('domain.edit.user.cyrus-mailbox.php?js=yes&uid={$_GET["uid"]}')",18,185)."</div>";
		
		
		
		if (! $RealMailBox) {
			if(preg_match("#Authentication failed#i", $cyr->cyrus_infos)){
				$error="<p class=text-error>{authentication_failed_cyrus}</p>";
				
			}else{
				$error = "<p class=text-error>{user_no_mailbox} !!</p>";
				$button2="<div style='margin-top:10px'>".button("{create_mailbox2}",
						"Loadjs('domains.edit.user.php?create-mailbox-wizard=yes&uid={$_GET["uid"]}&MailBoxMaxSize=0')",18,185)."</div>";
			
			}
		
		}
	}
	
	
	$picture_link="img/impersonate-photo.png";
	$html= "
	$error
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td style='width:190px;vertical-align:top'>
		<center style='margin-top:15px'>
		<img style='border-radius: 50% 50% 50% 50%;
    		box-shadow: 0 0 5px silver;height: 180px;margin: 0 32px;width: 180px;' src='$picture_link'></a>
    <center style='font-size: 24px;line-height: 1.2;word-wrap: break-word;margin-top:30px;margin-bottom:30px'>
    $title</center>

    $bouton$button2
    </center>	
	</td>
		<td style='width:80%;vertical-aglin:top'>
			<table style='width:100%'>
					</tr>			
				<tr style='height:70px'>
				<td valign=middle style='font-size:26px' class=legend><div>{Contact_Information}:</div>
				<i style='font-size:16px'>$description</i></td>
			</tr>

			
			<tr>
				<td valign=middle style='font-size:18px' class=legend>{member}:</td>
				<td valign=middle style='font-size:18px'><strong>$ct->givenName $ct->sn</strong></td>
			</tr>			
			<tr>
				<td valign=middle style='font-size:18px' class=legend>{account}:</td>
				<td valign=middle style='font-size:18px'><strong>$ct->uid</strong></td>
			</tr>
		
			<tr>
				<td valign=middle style='font-size:18px' class=legend>{email}:</td>
				<td valign=middle style='font-size:18px'><strong>$ct->mail</strong></td>
			</tr>
			<tr>
				<td valign=middle style='font-size:18px' class=legend>{telephoneNumber}:</td>
				<td valign=middle style='font-size:18px'><strong>$ct->telephoneNumber</strong></td>
			</tr>			
			<tr>
				<td valign=middle style='font-size:18px' class=legend>{mobile}:</td>
				<td valign=middle style='font-size:18px'><strong>$ct->mobile</strong></td>
			</tr>			
				<tr style='height:70px'>
				<td valign=middle style='font-size:26px' class=legend>".count($GRPS)." {groups}:</td>
			</tr>			
			<tr>
				<td></td>
				<td><table style='width:100%'>". @implode("", $XTRG)."</table></td>
			</tr>
			</table>
		</td>
	</tr>
	</table>
	<p>&nbsp;</p>
	</div>
			
	";
	
	
	
	echo $tpl->_ENGINE_parse_body($html);

	
}