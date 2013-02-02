<?php
session_start();

include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");


if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
BuildSessionAuth();
if($_SESSION["uid"]=="-100"){die("<H1>The SuperMaster cannot login</H1>");}

if(isset($_GET["upload-pic-js"])){upload_pic_js();exit;}
if(isset($_GET["upload-pic-popup"])){upload_pic_popup();exit;}
if( isset($_GET['TargetpathUploaded']) ){upload_form_perform();exit();}

if(isset($_GET["content"])){content_start();exit;}
if(isset($_GET["headNav"])){headNav();exit;}
if(isset($_GET["right-top-menus"])){right();exit;}
if(isset($_POST["GetMyTitle"])){GetMyTitle();exit;}
if(isset($_GET["left-content-id"])){left();exit;}

main_page();
exit;


if(isset($_GET["accordion"])){accordion();exit;}
if(isset($_GET["tab-acc"])){tab_accordion();exit;}
if($_GET["accordion-content"]=="members"){accordion_content_members();exit;}
if($_GET["accordion-content"]=="myaccount"){accordion_content_myaccount();exit;}
if($_GET["accordion-content"]=="messaging"){accordion_content_messaging();exit;}
if($_GET["accordion-content"]=="mymessaging"){accordion_content_Mymessaging();exit;}
if(isset($_GET["BodyToolbox"])){BodyToolbox();exit;}
if(isset($_GET["choose-language"])){choose_language();exit;}
if(isset($_POST["miniconfig-POST-lang"])){choose_language_save();exit();}


function main_page(){
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','miniadm.index.php?content=yes')</script>", $content);
	echo $content;
	
	
}

function headNav(){
	if(isset($_SESSION[__FILE__][__FUNCTION__])){echo $_SESSION[__FILE__][__FUNCTION__];return;}
	$page=CurrentPageName();
	$ct=new user($_SESSION["uid"]);
	if($ct->DisplayName==null){$ct->DisplayName=$_SESSION["uid"];}	
	$users=new usersMenus();
	$picture="/img/defaultFbProfileUser50x50.jpg";
	if(preg_match("#\/thumbnail-96-(.+?)$#", $ct->ThumbnailPath,$re)){
		if(is_file("ressources/profiles/icons/thumbnail-50-{$re[1]}")){
			$picture="ressources/profiles/icons/thumbnail-50-{$re[1]}";
		}
	}

	if($users->POSTFIX_INSTALLED){
	
	$messaging="<li class=\"navItem middleItem\">
				<a class=\"navLink bigPadding\" href=\"miniadm.messaging.php\" id=\"findFriendsNav\">{messaging}</a>
			</li>";
	}
	
	
$html="
	<div class=\"rfloat\">
		<ul id=\"pageNav\" class=\"clearfix\">
			<li class=\"navItem firstItem ThumbAccount\">
				<a href=\"miniadm.profile.php\" title=\"Profil\">
					<img class=\"ThumbAccountPhoto\" src=\"$picture\" />
						<span class=\"ThumbAccountName\">$ct->DisplayName</span></a>
				</li>
				$messaging
				<li class=\"navItem middleItem\" id=\"navHome\">
					<a class=\"navLink bigPadding\" href=\"miniadm.index.php\" accesskey=\"1\">{home}</a>
				</li>
		</ul>
	</div>
<script>
	
	
	
function x_ChangeHTMLTitle(obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>0){
		document.title=tempvalue;
    }else{
    	document.title=\"!!! Error !!!\";
    }
}
function ChangeHTMLTitleEndUsersPerform(){
	var XHR = new XHRConnection();
	XHR.appendData('GetMyTitle','yes');
	XHR.sendAndLoad(\"$page\", 'POST',x_ChangeHTMLTitle);	
}	
LoadAjax('right-top-menus','$page?right-top-menus=yes');
setTimeout('ChangeHTMLTitleEndUsersPerform()',500);
</script>	
";	

$tpl=new templates();
$html=$tpl->_ENGINE_parse_body($html);
$_SESSION[__FILE__][__FUNCTION__]=$html;
echo $html;
	
	
}

function left(){
	if(!$GLOBALS["VERBOSE"]){
		if(isset($_SESSION[__FILE__][__FUNCTION__])){echo $_SESSION[__FILE__][__FUNCTION__];return;}
	}
	$users=new usersMenus();
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	$tpl=new templates();
	if(is_array($_SESSION["privs"])){
		$r=$_SESSION["privs"];
		while (list ($key, $val) = each ($r) ){
			$_SESSION[$key]=$val;
		}
	}	
	
	
	if($users->AsOrgAdmin){
		$tr[]=SIMPLE_PARAGRAPHE_BLUE_ARROWTR("users_and_groups",
		"manage_users_and_groups_ou_explain",
		"miniadmin.members.php");
	}
	
	if($users->AsHotSpotManager){
		$tr[]=SIMPLE_PARAGRAPHE_BLUE_ARROWTR("hostpot_members",
		"hostpot_members_text",
		"miniadmin.hotspot.php");		
		
	}
	$mini=new miniadm();
	if($mini->IFItsProxy()){
		if($users->AsSquidAdministrator){
			$tr[]=SIMPLE_PARAGRAPHE_BLUE_ARROWTR("APP_PROXY",
					"APP_PROXY_TEXT",
					"miniadmin.proxy.php");		
		}
		
		
		$mini->squid_load_dynamic_acls();
		if(count($_SESSION["SQUID_DYNAMIC_ACLS"])>0){
			$dynamic_acls_newbee_explain=str_replace("%s", count($_SESSION["SQUID_DYNAMIC_ACLS"]), $tpl->_ENGINE_parse_body("{dynamic_acls_newbee_explain}"));
			$tr[]=SIMPLE_PARAGRAPHE_BLUE_ARROWTR("dynamic_acls_newbee",
					"noacc:$dynamic_acls_newbee_explain",
					"miniadmin.proxy.dynamic.acls.php");
			
			
		}	
		
		
	}
	
	
	
	
	
	if($_SESSION["AsWebStatisticsAdministrator"]){
		if($EnableRemoteStatisticsAppliance==0){
			$tr[]=SIMPLE_PARAGRAPHE_BLUE_ARROWTR("web_statistics",
			"web_statistics_member_text",
			"miniadm.webstats-start.php");			
		}
	}
	

	if(($_SESSION["ASDCHPAdmin"]) OR ($_SESSION["AsOrgDNSAdmin"]) OR ($_SESSION["AllowChangeDomains"])  ) {
		$tr[]=SIMPLE_PARAGRAPHE_BLUE_ARROWTR("network_services",
				"network_services_text","miniadm.network.php");
	}	

	

	if($users->SAMBA_INSTALLED){
		$EnableSambaVirtualsServers=$sock->GET_INFO("EnableSambaVirtualsServers");
		if(!is_numeric($EnableSambaVirtualsServers)){$EnableSambaVirtualsServers=0;}
	}
	
	if($EnableSambaVirtualsServers==1){
		if(count($_SESSION["VIRTUALS_SERVERS"])>0){	
			if(count($_SESSION["VIRTUALS_SERVERS"])>1){
				$tr[]=SIMPLE_PARAGRAPHE_BLUE_ARROWTR("file_sharing_services",
						"file_sharing_services_text","miniadm.samba-multiple.php");				
				
			}
			
		}
	}
	
	
	
	if(count($tr)==0){return;}
	
	$html="<div class=\"BodyContent\"><table style='widh:100%'> ".@implode("", $tr)."</table></div>";
	$html=$tpl->_ENGINE_parse_body($html);
	
	$_SESSION[__FILE__][__FUNCTION__]=$html;
	echo $html;
		
	
}


function content_start(){
	$page=CurrentPageName();
	$uid=$_SESSION["uid"];
	$ct=new user($_SESSION["uid"]);
	$base="ressources/profiles/icons";
	if($ct->DisplayName==null){$ct->DisplayName=$_SESSION["uid"];}
	$pictureBG="/img/fbkenduser-bg.jpg";
	$picture="/img/defaultFbProfileUser.jpg";
	if(preg_match("#\/thumbnail-96-(.+?)$#", $ct->ThumbnailPath,$re)){
		if(is_file("$base/thumbnail-160-{$re[1]}")){
			$picture="$base/thumbnail-160-{$re[1]}";
		}
	}
	
	if(is_file("$base/background-{$uid}.loc")){
		$pictureBGPath=@file_get_contents("$base/background-{$uid}.loc");
		if(is_file($pictureBGPath)){
			$pictureBG=$pictureBGPath;
		}else{
			writelogs("$pictureBGPath no such file...",__FUNCTION__,__FILE__,__LINE__);
		}
	}else{
		writelogs("$base/background-{$uid}.loc no such file...",__FUNCTION__,__FILE__,__LINE__);
	}
	
	if($_SESSION["ou"]==null){BuildSession($_SESSION["uid"]);}
	
	
	
	$html="
	<div id=\"contentArea\">
	<div>
		<div>
			<div class=\"TopSectionBase\">
				<div class=\"MainTopHeader ytred TopSection\" style='padding-bottom:10px'>
					<div class=\"drop_elem AccountCoverLarge\" id=\"AccountCover\">
						<div data-collapse=\"148\" style=\"margin-top: 0px;\" class=\"cover\">
							<div>
								<a href=\"javascript:blur()\" OnClick=\"javascript:Loadjs('$page?upload-pic-js=yes');\" class=\"coverWrap\">
								<img style=\"top:0px;width:100%;\" src=\"$pictureBG\" class=\"coverPhotoImg photo img\">
								<div class=\"coverBorder\"></div>
									<div class=\"AccountIMGNotch\">
										<div class=\"notchInner\">
									</div>
								</div>
								</a>
							</div>
						</div>
	<div class=\"clearfix\" id=\"TitleBarr\">
		<div class=\"actions\"></div>
		<div class=\"name\">
		<div class=\"photoContainer\">
		<div class=\"drop_elem\">
			<a href=\"javascript:blur()\" OnClick=\"javascript:Loadjs('miniadm.profile.php?upload-pic-js=yes');\" 
			class=\"AccountIMGThumb AccountIMGThumbLarge\">
			<div class=\"uiScaledImageContainer AccountIMG\">
				<img width=\"160\" height=\"240\" 
				src=\"$picture\" 
				class=\"scaledImageFitWidth img\">
			</div>
			</a>
		<div class=\"uiSelector AccountPictureSelection uiSelectorNormal\">
		<div class=\"wrap\"></div>
		</div>
		</div>
			
		</div>
			<h2><a href=\"miniadm.profile.php\">{$_SESSION["ou"]}::$ct->DisplayName</a></h2>
		</div>
	</div>
</div>
</div>

</div>
</div>
</div>
</div>
<table style='width:851px !important;'>
<tr>
	<td width=60% valign='top'>
		<div class=\"BodyContent\">
		<H1>{about_this_section}</H1>
		<p>{enduser_explain_section}</p>
		</div>
		<div id='left-content-id'></div>
	</td>
	<td width=50% valign='top'>
		
		<div id='right-top-menus' class=\"BodyContent\"></div>
	</td>
</tr>
</table>
	

";
	//$user=new user($_SESSION["uid"]);
	$tpl=new templates();
	$html=$tpl->_ENGINE_parse_body($html);
	$html=str_replace("%ORGA ", $_SESSION["ou"], $html);
	echo $html;
}





build();

function accordion_content_messaging($return=false){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$ouencoded=urlencode(base64_encode($_SESSION["ou"]));
	
		$transport=Paragraphe_miniadm("folder-transport-48.png",
		"{localdomains}","{localdomains_text}",
		"Loadjs('domains.edit.domains.php?js=yes&ou=$ouencoded&encoded=yes&in-front-ajax=yes')");
		
		$quarantine_admin=Paragraphe_miniadm("folder-quarantine-extrainfos-48.png",
		"{quarantine_manager}",
		"{quarantine_manager_text}","javascript:LoadAjax('BodyContent','domains.quarantine.php?js={$_SESSION["ou"]}&inline=yes')");		
	
		if(!$users->AllowChangeDomains){
			$transport=Paragraphe_miniadm("folder-transport-48-grey.png",
			"{localdomains}","{localdomains_text}",
			"");			
		}

		if(!$users->AsQuarantineAdministrator){
			$quarantine_admin=Paragraphe_miniadm("folder-quarantine-extrainfos-48-grey.png",
			"{quarantine_manager}",
			"{quarantine_manager_text}","");
		}
		

		
		
	$html=$transport.$quarantine_admin;
	$html=$tpl->_ENGINE_parse_body($html);
	if($return){return $html;}
	echo $html;
}

function build(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	echo "
	</div>
	<script>
		LoadAjax('left-menus','$page?accordion=yes');
		YahooWinHide();
	</script>
	
	
	";
}

function BodyToolbox(){
	include_once(dirname(__FILE__)."/ressources/class.html.tools.inc");
	$page=CurrentPageName();
	$tpl=new templates();
	$html=new htmltools_inc();
	$lang=$html->LanguageArray();		
	$u=new user($_SESSION["uid"]);
		$connected="{connected_has}:&nbsp;$u->uid <a href=\"javascript:blur();\" 
			OnClick=\"javascript:Minilogoff();\"
			style='text-decoration:underline;font-size:12px'
			>{disconnect}</a>";
		
		$empty="<a href=\"javascript:blur();\" 
			OnClick=\"javascript:CacheOff();\"
			style='text-decoration:underline;font-size:12px'
			>";
	echo $tpl->_ENGINE_parse_body("
		<a href=\"javascript:blur();\" OnClick=\"javascript:RefreshCenterPanel()\">
		<H1 style='margin-bottom:3px'>{organization}:{$_SESSION["ou"]}</H1></a><div style='font-size:12px'>($connected)
		&nbsp;|&nbsp;$empty{empty_cache}</a><div id='tool-map'></div></div>
		
		<script>
			var uid='{$_SESSION["uid"]}';
			function Minilogoff(){
				MyHref('/miniadm.logoff.php');
			}
			
			function RefreshCenterPanel(){
				LoadAjax('BodyContent','$page?center-panel=yes');
			
			}
			if(uid=='-100'){Minilogoff();}
			document.title='Artica ({$lang[$_COOKIE["artica-language"]]}) :: {organization}:{$_SESSION["ou"]} :: $u->uid'; 
			RefreshCenterPanel();
		</script>
		");
	
}

function accordion(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	
	$array["myaccount"]="{myaccount}";
	$content["myaccount"]=accordion_content_myaccount(true);
	
	
	if($users->POSTFIX_INSTALLED){
		$array["mymessaging"]="{mymessaging}";
		$content["mymessaging"]=accordion_content_messaging(true);
	}
	
	if($users->AsOrgAdmin){$array["members"]="{members}";}
	if($users->POSTFIX_INSTALLED){
		if($users->AsMessagingOrg){$array["messaging"]="{messaging_org}";}
	}
	
	
	
	
	
	
	$cc=0;
	while (list ($num, $val) = each ($array) ){
		
		$jsBlockHide[]="document.getElementById('accordion-div-$num').style.display = 'none'";
		
		$cc++;
		$tr[]="
		<h3 class=\"ui-accordion-header ui-helper-reset ui-state-default ui-state-active ui-corner-top\">
		<span class=\"ui-icon ui-icon-triangle-1-e\"></span>
			<a href=\"javascript:blur();\" OnClick=\"javascript:MyAccordionSwitch('$num')\">$val</a>
		</h3>
		
	
			<div id='accordion-div-$num' class=\"ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom ui-accordion-content-active\" style='display:none'>
				<input type='hidden' id='accordion-$cc' value='$num'>
				<div style='height:auto;margin-left:-20px;margin-right:-20px' id='accordion-content-$cc'>{$content["$num"]}</div>
			</div>
		";
		

		
	}
	
	
	$html="
	<div id='accordion' style='overflow-x: hidden' class='ui-accordion ui-widget ui-helper-reset'>". @implode("\n",$tr)."

</div>	

 <script>
	function LoadMyAccordion(){
		MyAccordionSwitch('myaccount');
  	}

	function MyAccordionSwitch(key){
		". @implode("\n", $jsBlockHide)."
		document.getElementById('accordion-div-'+key).style.display = 'block'; 
		
	}
  
  
  LoadAjax('BodyToolbox','$page?BodyToolbox=yes');
  LoadMyAccordion();
  
  
  </script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function tab_accordion(){
	
	
}

function accordion_content(){
	
	
}

function accordion_content_members(){
	
	$usersmenus=new usersMenus();
	$ou_encoded=base64_encode($_SESSION["ou"]);
	$find_members=Paragraphe_miniadm('find-members-48.png','{find_members}','{find_members_text}',"LoadAjax('BodyContent','domains.manage.org.index.php?org_section=users&SwitchOrgTabs=$ou_encoded&ou=$ou_encoded&mem=yes');");		
	if(($usersmenus->AllowAddUsers) OR ($usersmenus->AsOrgAdmin) OR ($usersmenus->AsMessagingOrg)){	
		$adduser=Paragraphe_miniadm("folder-useradd-48.png","{add_user}","{add_user_text}","Loadjs('domains.add.user.php?ou={$_SESSION["ou"]}')");
		$groups=Paragraphe_miniadm('folder-group-48.png','{manage_groups}','{manage_groups_text}',"Loadjs('domains.edit.group.php?ou=$ou_encoded&js=yes')");
		
	}
	
	echo $adduser.$groups.$find_members."
	<script>
		LoadAjax('BodyContent','domains.manage.org.index.php?org_section=users&SwitchOrgTabs=$ou_encoded&ou=$ou_encoded&mem=yes');
	</script>
	
	";
}

function accordion_content_Mymessaging(){
	$events=Paragraphe_miniadm("48-mailevents.png",
	"{messaging_events}","{messaging_events_text}",
	"LoadAjax('BodyContent','miniamd.user.rtmm.php')");	
	
	//48-spam-grey.png
	
	
	echo $events;
}

function accordion_content_myaccount($return=false){
	include_once(dirname(__FILE__)."/ressources/class.user.inc");
	include_once(dirname(__FILE__)."/ressources/class.html.tools.inc");
	$tpl=new templates();
	$u=new user("{$_SESSION["uid"]}");
	$page=CurrentPageName();
	$dn=urlencode(base64_encode($u->dn));
	$ou_encoded=base64_encode($_SESSION["ou"]);
	$htmltools=new htmltools_inc();
	$sock=new sockets();
	$lang=$htmltools->LanguageArray();	
	$current=$lang[$_COOKIE["artica-language"]];
	$langage=Paragraphe_miniadm("language-48.png",
	"{language}","{change_the_webconsole_language_text}<br><strong>$current</strong>",
	"YahooWin2('320','$page?choose-language=yes','{language}:$current')");
	
	
	$myaccount=Paragraphe_miniadm("identity-48.png",
	"{myaccount}","{myaccount_text}",
	"LoadAjax('BodyContent','domains.edit.user.php?userid={$_SESSION["uid"]}&ajaxmode=yes&dn=$dn')");	
	
	
	$adressebook=Paragraphe_miniadm("48-addressbook.png",
	"{my_address_book}","{my_address_book_text}",
	"LoadAjax('BodyContent','my.addressbook.php')");	
	
	$users=new usersMenus();
	if($users->OPENVPN_INSTALLED){
		$show=false;
		if($users->AllowOpenVPN){$show=true;}
		if($sock->GET_INFO("EnableOpenVPNEndUserPage")==1){$show=true;}
		if($show){
			$openvpn_client=Paragraphe_miniadm("42-openvpn.png",
			"{my_vpn_cnx}","{my_vpn_cnx_text}",
			"LoadAjax('BodyContent','miniadm.openvpn.client.php')");	
		}
		
	}
	
	//
	
	
	$html=$langage.$myaccount.$adressebook.$openvpn_client;
	$html=$tpl->_ENGINE_parse_body($html);
	if($return){return "$html";}
	echo "
	$html
	<script>
			LoadAjax('BodyContent','domains.edit.user.php?userid={$_SESSION["uid"]}&ajaxmode=yes&dn=$dn');
	</script>
	";
}

function choose_language(){
	include_once(dirname(__FILE__)."/ressources/class.html.tools.inc");
	$tpl=new templates();
	$htmltools=new htmltools_inc();
	$lang=$htmltools->LanguageArray();	
	$page=CurrentPageName();
	
	$lang[null]="{select}";
	$html="<table style='width:99%' class=form>
	<tr>
		<td valign='top'>{language}: ($tpl->language)</td>
		<td>". Field_array_Hash($lang,"miniconfig-select-lang",$tpl->language,"style:font-size:16px;")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'>". button("{apply}","ChangeLang()")."</td>
	</tr>
	</table>
	
	<script>
	
	var x_ChangeLang= function (obj) {
		var response=obj.responseText;
		location.reload();
	}	
	
	function ChangeLang(){
		var lang=document.getElementById('miniconfig-select-lang').value;
		Set_Cookie('artica-language', lang, '3600', '/', '', '');
		var XHR = new XHRConnection();
		XHR.appendData('miniconfig-POST-lang',lang);
		XHR.sendAndLoad('$page', 'POST',x_ChangeLang);		
		location.reload();
	}
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function choose_language_save(){
	session_start();
	$_SESSION["detected_lang"]=$_POST["miniconfig-POST-lang"];
	writelogs("Unset array of ".count($_SESSION["translation"]),__FUNCTION__,__FILE__,__LINE__);
	unset($_SESSION["translation"]);
	writelogs("-> remove cached",__FUNCTION__,__FILE__,__LINE__);
	REMOVE_CACHED(null);
	writelogs("-> setcookie",__FUNCTION__,__FILE__,__LINE__);
	setcookie("artica-language", $_POST["miniconfig-POST-lang"], time()+172800);
	$tpl=new templates();	
}

function right(){
	if(!$GLOBALS["VERBOSE"]){
		if(isset($_SESSION[__FILE__][__FUNCTION__])){echo $_SESSION[__FILE__][__FUNCTION__];return;}
	}
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$u=new user($_SESSION["uid"]);
	
	$mydn=base64_encode($u->dn);
	
	$p1=Paragraphe32("myaccount", "myaccount_text", "MyHref('miniadm.profile.php')", "identity-32.png");
	$p2=Paragraphe32("logoff", "logoff_text", "MyHref('miniadm.logoff.php')", "shutdown-computer-32.png");
	
	if($users->AsOrgAdmin){
		$tt[]=SIMPLE_PARAGRAPHE_BLUE_ARROWTR("new_member","new_member_explain_in_ou",
		"javascript:Loadjs('create-user.php')","plus-24.png");
		
	}
	
	$tt[]=SIMPLE_PARAGRAPHE_BLUE_ARROWTR("myaccount","myaccount_text","miniadm.profile.php");
	$tt[]=SIMPLE_PARAGRAPHE_BLUE_ARROWTR("logoff","logoff_text","miniadm.logoff.php","shutdown-computer-24.png");
			
			
		
	
	
	$html="
	<H1>{what_to_do}</H1>
	<table style='width:100%'>
	".@implode("", $tt)."
	</table>
	<script>LoadAjax('left-content-id','$page?left-content-id=yes');</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	return;
	
	
	$info_right[]="	
	<table style='width:98%' class=form>
	<tr>
		<td valign='top' width=1%><img src='img/webservices-128.png'></td>
		<td valign='top'><H3 style='font-weight:bold'>{myWebServices}</H3>
			<ul>
			<li><a href=\"javascript:blur()\" 
				OnClick=\"javascript:Loadjs('miniadm.www.services.php');\" 
				style='font-size:13px;font-weight:normal'>{myWebServices_text}</a>
			</li>
			</ul>	
			
		
		</td>
	</tr>
	</table>";
	
	$info_left[]="	<table style='width:98%' class=form>
	<tr>
		<td valign='top' width=1%><img src='img/identity-128.png'></td>
		<td valign='top'><H3 style='font-weight:bold'>{myaccount}</H3>
			<ul>
			<li><a href=\"javascript:blur()\" 
				OnClick=\"javascript:LoadAjax('BodyContent','domains.edit.user.php?userid={$_SESSION["uid"]}&ajaxmode=yes&dn=$mydn');\" 
				style='font-size:13px;font-weight:normal'>{myaccount_text}</a>
			</li>
			</ul>	
			
		
		</td>
	</tr>
	</table>";	
	
	if($users->AllowAddUsers){
		$info_left[]=info_organization();
	}
	if($users->AllowChangeDomains){
		$info_right[]=info_messaging();
	}
	
	if(($users->AsDansGuardianAdministrator) OR ($users->AsWebFilterRepository)){
		$info_left[]=info_Dansguardian();
		
	}
	
	
	
	
	//www-128.png
	
	$html="
	<table style='width:100%'>
	<tr>
		<td valign='top' width=50%>".@implode("<br>",$info_left)."</td>
		<td valign='top' width=50%>".@implode("<br>",$info_right)."</td>
	</tr>
	</table>
	<script>
	LoadAjax('tool-map','miniadm.toolbox.php?script=center-panel');
	
	</script>
	
	";
	$html=$tpl->_ENGINE_parse_body($html);
	$_SESSION[__FILE__][__FUNCTION__]=$html;
	echo $html;
}

function info_messaging(){
	$ldap=new clladp();
	$users=new usersMenus();
	$usersNB=$ldap->CountDeDomainsOU($_SESSION["ou"]);
	$ouencoded=base64_encode($_SESSION["ou"]);
return "
	<table style='width:98%' class=form>
	<tr>
		<td valign='top' width=1%><img src='img/128-catch-all.png' OnClick=\"javascript:Loadjs('domains.edit.domains.php?js=yes&ou=$ouencoded&encoded=yes&in-front-ajax=yes');></td>
		<td valign='top'><H3 style='font-weight:bold'>{messaging}: $usersNB {domains}</H3>
			<ul>
			<li><a href=\"javascript:blur()\" 
				OnClick=\"javascript:Loadjs('domains.edit.domains.php?js=yes&ou=$ouencoded&encoded=yes&in-front-ajax=yes');\" 
				style='font-size:13px;font-weight:normal'>{localdomains_text}</a>
			</li>
			</ul>	
			
		
		</td>
	</tr>
	</table>
	";	
	
	
}

function info_Dansguardian(){

return "
	<table style='width:98%' class=form>
	<tr>
		<td valign='top' width=1%><img src='img/www-web-secure-128.png' OnClick=\"javascript:Loadjs('miniadm.webfiltering.index.php');\" ></td>
		<td valign='top'><H3 style='font-weight:bold'>{WEB_FILTERING}</H3>
			<ul>
			<li><a href=\"javascript:blur()\" 
				OnClick=\"javascript:Loadjs('miniadm.webfiltering.index.php');\" 
				style='font-size:13px;font-weight:normal'>{miniadm_web_filtering_text}</a>
			</li>
			</ul>	
			
		
		</td>
	</tr>
	</table>
	";		
	
}

function info_organization(){
	$ldap=new clladp();
	$usersNB=$ldap->CountDeUSerOu($_SESSION["ou"]);
	
	return "
	<table style='width:98%' class=form>
	<tr>
		<td valign='top' width=1%><img src='img/users-info-128.png' OnClick=\"javascript:Loadjs('domains.add.user.php?ou={$_SESSION["ou"]}');\" ></td>
		<td valign='top'><H3 style='font-weight:bold'>{$_SESSION["ou"]}: $usersNB {members}</H3>
			<ul>
			<li><a href=\"javascript:blur()\" 
				OnClick=\"javascript:Loadjs('domains.add.user.php?ou={$_SESSION["ou"]}');\" 
				style='font-size:13px;font-weight:normal'>{add_user}</a>
			</li>
			</ul>	
			
		
		</td>
	</tr>
	</table>
	";
	
	
	
}

function GetMyTitle(){
	
	echo $_SERVER["SERVER_NAME"]." >> {$_SESSION["ou"]} >> {$_SESSION["uid"]}";
	
}

function upload_pic_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{change_background_image}");
	$html="YahooWin2('550','$page?upload-pic-popup=yes','$title')";
	echo $html;
}
function upload_pic_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$UploadAFile=$tpl->javascript_parse_text("{upload_your_picture} 851x315");
	$allowedExtensions="'jpg','jpeg','png'"; 
	
	
	
	
	if($allowedExtensions<>null){
		$allowedExtensions="allowedExtensions: [$allowedExtensions],"; 
	}
	$UploadAFile=str_replace(" ", "&nbsp;", $UploadAFile);
	$html="
	<div id='file-uploader-demo1' style='width:100%;text-align:center'>		
		<noscript>			
			<!-- or put a simple form for upload here -->
		</noscript>         
	</div>	
 <script>        
        function createUploader(){            
            var uploader = new qq.FileUploader({
                element: document.getElementById('file-uploader-demo1'),
                action: '$page',$allowedExtensions
                template: '<div class=\"qq-uploader\">' + 
                '<div class=\"qq-upload-drop-area\"><span>Drop files here to upload</span></div>' +
                '<div class=\"qq-upload-button\" style=\"width:100%\">&nbsp;&laquo;&nbsp;$UploadAFile&nbsp;&raquo;&nbsp;</div>' +
                '<ul class=\"qq-upload-list\"></ul>' + 
             	'</div>',
                debug: false,
				params: {
				        TargetpathUploaded: '{$_GET["upload-file"]}',
				        //select-file: '{$_GET["select-file"]}'
				    },
				onComplete: function(id, fileName){
					document.location.reload();
				}
            });           
        }
        
       createUploader();   
    </script>    	
	";
	
	//$html="<iframe style='width:100%;height:250px;border:1px' src='$page?form-upload={$_GET["upload-file"]}&select-file={$_GET["select-file"]}'></iframe>";
	echo $html;	
	
}
function upload_form_perform(){
	usleep(300);
	writelogs("upload_form_perform() -> OK {$_GET['qqfile']}",__FUNCTION__,__FILE__,__LINE__);
	$sock=new sockets();
	$sock->getFrameWork("services.php?lighttpd-own=yes");

if (isset($_GET['qqfile'])){
    $fileName = $_GET['qqfile'];
    if(function_exists("apache_request_headers")){	
		$headers = apache_request_headers();
		if ((int)$headers['Content-Length'] == 0){
			writelogs("content length is zero",__FUNCTION__,__FILE__,__LINE__);
			die ('{error: "content length is zero"}');
		}
    }else{
    	writelogs("apache_request_headers() no such function",__FUNCTION__,__FILE__,__LINE__);
    }
} elseif (isset($_FILES['qqfile'])){
    $fileName = basename($_FILES['qqfile']['name']);
    writelogs("_FILES['qqfile']['name'] = $fileName",__FUNCTION__,__FILE__,__LINE__);
	if ($_FILES['qqfile']['size'] == 0){
		writelogs("file size is zero",__FUNCTION__,__FILE__,__LINE__);
		die ('{error: "file size is zero"}');
	}
} else {
	writelogs("file not passed",__FUNCTION__,__FILE__,__LINE__);
	die ('{error: "file not passed"}');
}

writelogs("upload_form_perform() -> OK {$_GET['qqfile']}",__FUNCTION__,__FILE__,__LINE__);

if (count($_GET)){
	$datas=json_encode(array_merge($_GET, array('fileName'=>$fileName)));	
	writelogs($datas,__FUNCTION__,__FILE__,__LINE__);

} else {
	writelogs("query params not passed",__FUNCTION__,__FILE__,__LINE__);
	die ('{error: "query params not passed"}');
}
writelogs("upload_form_perform() -> OK {$_GET['qqfile']} upload_max_filesize=".ini_get('upload_max_filesize')." post_max_size:".ini_get('post_max_size'),__FUNCTION__,__FILE__,__LINE__);
include_once(dirname(__FILE__)."/ressources/class.file.upload.inc");
$allowedExtensions = array();
$sizeLimit = qqFileUploader::toBytes(ini_get('upload_max_filesize'));
$sizeLimit2 = qqFileUploader::toBytes(ini_get('post_max_size'));

if($sizeLimit2<$sizeLimit){$sizeLimit=$sizeLimit2;}

$content_dir=dirname(__FILE__)."/ressources/conf/upload/";
$uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
$result = $uploader->handleUpload($content_dir);

writelogs("upload_form_perform() -> OK $resultTXT",__FUNCTION__,__FILE__,__LINE__);

$TargetpathUploaded=base64_decode($_GET["TargetpathUploaded"]);

if(!is_file("$content_dir$fileName")){
	die ("{error: \"$content_dir$fileName no such file \"}");
}
  	include_once(dirname(__FILE__).'/ressources/class.images.inc');
	$uid=$_SESSION["uid"];
	$base="ressources/profiles/icons";
	$user=new user($_SESSION["uid"]);
	$jpeg_filename="$content_dir$fileName";
	$jpegPhoto_datas=file_get_contents("$content_dir$fileName"); 
	$image=new images($jpeg_filename);
	$jpeg_dimensions=@getimagesize($jpeg_filename);
	$img_type=array(1=>"gif",2=>'jpg',3=>'png',4=>'swf',5=>'psd',6=>'bmp',7=>'tiff',8=>'tiff',9=>'jpc',10=>'jp2',11=>'jpx');
	$extension="{$img_type[$jpeg_dimensions[2]]}";
	$thumbnail_path="$base/background-{$uid}.$extension";
	if(!$image->thumbnail(851,315,$thumbnail_path)){
		die ("{error: \"Create image error\"}");
	}
	
	@file_put_contents("$base/background-{$uid}.loc", $thumbnail_path);
	
	
	echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
	return;
		
}





?>