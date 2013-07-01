<?php
session_start();
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class=text-error>");
ini_set('error_append_string',"</p>");

include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");



if(isset($_GET["localdomain-section"])){localdomain_section();exit;}
if(isset($_GET["localdomain-search"])){localdomain_search();exit;}
if(isset($_GET["localdomain-new-js"])){localdomain_add_js();exit;}
if(isset($_GET["localdomain-new"])){localdomain_add();exit;}
if(isset($_POST["localdomain"])){localdomain_save();exit;}
if(isset($_POST["localdomain-remove"])){localdomain_remove();exit;}

if(isset($_GET["remotedomain-section"])){remotedomain_section();exit;}
if(isset($_GET["remotedomain-search"])){remotedomain_search();exit;}
if(isset($_GET["remotedomain-new-js"])){remotedomain_add_js();exit;}
if(isset($_GET["remotedomain-new"])){remotedomain_add();exit;}
if(isset($_POST["remotedomain"])){remotedomain_save();exit;}
if(isset($_POST["remotedomain-remove"])){remotedomain_remove();exit;}

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["buildpage"])){page();exit;}
if(isset($_GET["content"])){content();exit;}
tabs();

function page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;
		
	
}
function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	
	$html="
	<div class=BodyContent>
	<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a></div>
	<H1>{domains}</H1>
	<p>{localdomains_text}</p>
	</div>
	<div id='messaging-left'></div>
	
	<script>
	LoadAjax('messaging-left','$page?tabs=yes&notitle=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);	
	
}


function VerifyRights(){
	$usersmenus=new usersMenus();
	if($usersmenus->AllowChangeDomains){return true;}
	if($usersmenus->AsMessagingOrg){return true;}
	if(!$usersmenus->AllowChangeDomains){return false;}
}
function localdomain_add_js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{new_domain}");
	echo "YahooWin('650','$page?localdomain-new=yes','$title')";
}
function remotedomain_add_js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$domain=$_GET["domain"];
	$title=$tpl->_ENGINE_parse_body("{new_domain}");
	if($domain<>null){$title=$tpl->_ENGINE_parse_body("{domain}:$domain");}
	$domain=urlencode($domain);
	echo "YahooWin('650','$page?remotedomain-new=yes&domain=$domain','$title')";
}
function localdomain_save(){
	$usr=new usersMenus();
	$tpl=new templates();
	if($usr->AllowChangeDomains==false){echo $tpl->_ENGINE_parse_body('{no_privileges}');exit;}		
	$tpl=new templates();
	$ou=$_POST["ou"];
	$domain=trim(strtolower($_POST["localdomain"]));
	$ldap=new clladp();
	$sock=new sockets();
	$InternetDomainsAsOnlySubdomains=$sock->GET_INFO("InternetDomainsAsOnlySubdomains");
	if($InternetDomainsAsOnlySubdomains==1){
		if(!$usr->OverWriteRestrictedDomains){
			$domaintbl=explode(".",$domain);
			$subdomain=$domaintbl[0];
			unset($domaintbl[0]);
			$domainsuffix=@implode(".",$domaintbl);	
			$sql="SELECT domain FROM officials_domains WHERE domain='$domainsuffix'";
			$q=new mysql();
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
			if(!$q->ok){echo $q->mysql_error;return;}
			if($ligne["domain"]==null){
				echo $tpl->_ENGINE_parse_body("{please_choose_an_official_domain}");
				return;
			}
		}
	}
	
	$hashdoms=$ldap->hash_get_all_domains();
	writelogs("hashdoms[$domain]={$hashdoms[$domain]}",__FUNCTION__,__FILE__);
	
	if($hashdoms[$domain]<>null){
		echo $tpl->_ENGINE_parse_body('{error_domain_exists}');
		exit;
	}
	
	
	
	if(!$ldap->AddDomainEntity($ou,$domain)){
		echo $ldap->ldap_last_error;
		return;
	}
	ChockServices();
	
}

function ChockServices(){
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-transport-maps=yes");
}

function localdomain_add(){
	$boot=new boostrap_form();
	$ldap=new clladp();
	$boot->set_field("localdomain", "{domain}", null,array("MANDATORY"=>true));
	
	$users=new usersMenus();
	if($users->AsPostfixAdministrator){
		$ous=$ldap->hash_get_ou(true);
		$boot->set_list("ou","{ou}",$ous,null);
	}else{
		$boot->set_hidden("ou", $_SESSION["ou"]);
	}
	$boot->set_button("{add}");
	$boot->set_RefreshSearchs();
	$boot->set_CloseYahoo("YahooWin");
	echo $boot->Compile();
}

function remotedomain_add(){
	$ldap=new clladp();
	$ou=$_SESSION["ou"];
	$btname="{add}";
	$domain=$_GET["domain"];
	if($domain<>null){$btname="{apply}";}
	if($ou==null){$ou=$ldap->ou_by_smtp_domain($domain);}
	$HashDomains=$ldap->Hash_relay_domains();
	$tools=new DomainsTools();
	$arr=$tools->transport_maps_explode($HashDomains[$domain]);
	
	
	$dn="cn=@$domain,cn=relay_recipient_maps,ou=$ou,dc=organizations,$ldap->suffix";
	$trusted_smtp_domain=0;
	if($ldap->ExistsDN($dn)){$trusted_smtp_domain=1;}
	if(!is_numeric($arr[2])){$arr[2]=25;}
	if( $arr[3]==null){ $arr[3]=0;}else{
		if( $arr[3]=="no"){ $arr[3]=0;}else{ $arr[3]=1;}
	}
	$boot=new boostrap_form();
	$ldap=new clladp();
	if($domain<>null){
		$boot->set_field("remotedomain", "{domain}", $domain,array("MANDATORY"=>true,"DISABLED"=>true));
	}else{
		$boot->set_field("remotedomain", "{domain}", $domain,array("MANDATORY"=>true));
	}
	

	
	
	
	$users=new usersMenus();
	if($users->AsPostfixAdministrator){
		$ous=$ldap->hash_get_ou(true);
		$boot->set_list("ou","{ou}",$ous,$ou);
	}else{
		$boot->set_hidden("ou", $ou);
	}
	$boot->set_field("destination", "{destination}",$arr[1], null,array("MANDATORY"=>true));
	$boot->set_field("destination_port", "{port}",$arr[2], null,array("MANDATORY"=>true));
	$boot->set_checkbox("MX","{MX_lookups}", $arr[3],array('TOOLTIP'=>"{mx_look}"));
	$boot->set_checkbox("trusted_smtp_domain","{trusted_smtp_domain}", $trusted_smtp_domain,array('TOOLTIP'=>"{trusted_smtp_domain_text}"));
	$boot->set_button($btname);
	$boot->set_RefreshSearchs();
	$boot->set_CloseYahoo("YahooWin");
	echo $boot->Compile();	
	
}

function tabs(){
	$tpl=new templates();
	if($_SESSION["ou"]<>null){$subtitle=":{$_SESSION["ou"]}";}
	$users=new usersMenus();
	if($users->AsPostfixAdministrator){$subtitle=null;}
	if(!isset($_GET["notitle"])){
		echo $tpl->_ENGINE_parse_body("<H3>{localdomains}$subtitle</H3><p>{localdomains_text}</p>");
	}
	if(!VerifyRights()){throw new Exception("No rights",500);die();}
	
	$page=CurrentPageName();
	$users=new usersMenus();
	
	$LOCAL_MDA=false;
	if($users->cyrus_imapd_installed){$LOCAL_MDA=true;}
	if($users->ZARAFA_INSTALLED){$LOCAL_MDA=true;}
	
	
	$array["{local_domains}"]="$page?localdomain-section=yes";
	
	
	if($users->POSTFIX_INSTALLED){
		$array["{remote_domains}"]="$page?remotedomain-section=yes";
		if(!$LOCAL_MDA){unset($array["{local_domains}"]);}
	}

	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	echo $boot->build_tab($array);
}

function ORGANISTATION_FROM_USER(){
	$ldap=new clladp();
	$hash=$ldap->Hash_Get_ou_from_users($_SESSION["uid"],1);
	return $hash[0];
}

function localdomain_section(){
	$page=CurrentPageName();
	$boot=new boostrap_form();
	$button=button("{new_domain}","Loadjs('$page?localdomain-new-js=yes')",16);
	$EXPLAIN["BUTTONS"][]=$button;
	$SearchQuery=$boot->SearchFormGen("domain","localdomain-search",null,$EXPLAIN);	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($SearchQuery);
}
function remotedomain_section(){
	$page=CurrentPageName();
	$boot=new boostrap_form();
	$button=button("{new_domain}","Loadjs('$page?remotedomain-new-js=yes')",16);
	$EXPLAIN["BUTTONS"][]=$button;
	$SearchQuery=$boot->SearchFormGen("domain","remotedomain-search",null,$EXPLAIN);
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($SearchQuery);	
}

function remotedomain_search(){
	$users=new usersMenus();
	$page=CurrentPageName();
	$boot=new boostrap_form();
	$t=time();
	$tpl=new templates();
	$ldap=new clladp();
	$are_you_sure_to_delete=$tpl->javascript_parse_text("{are_you_sure_to_delete}");
	if($users->AsPostfixAdministrator){
		$HashDomains=$ldap->Hash_relay_domains();
	}else{
		$HashDomains=$ldap->Hash_relay_domains($_SESSION["ou"]);
	}
	if(!is_array($HashDomains)){$HashDomains=array();}
	$tools=new DomainsTools();
	$search=string_to_flexregex("remotedomain-search");
	while (list ($domain, $ligne) = each ($HashDomains) ){
		$id=md5($domain);
		$delete=imgsimple("delete-32.png",null,"DeleteRemoteDomain$t('$domain','$id')");
		if($search<>null){if(!preg_match("#$search#", $domain)){continue;}}
		$arr=$tools->transport_maps_explode($ligne);
		$relay="{$arr[1]}:{$arr[2]}";
		
		$js="Loadjs('$page?remotedomain-new-js=yes&domain=".urlencode($domain)."')";
		$trSwitch=$boot->trswitch($js);
		
		$tr[]="
		<tr id='$id'>
		<td style='font-size:18px' $trSwitch><i class='icon-globe'></i>&nbsp;$domain</td>
		<td style='font-size:18px' nowrap $trSwitch><i class='icon-arrow-right'></i>&nbsp;$relay</td>
		<td style='text-align:center'>$delete</td>
		</tr>";
	}
	
	echo $tpl->_ENGINE_parse_body("
	
			<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th style='width:99%'>{domains}</th>
					<th style='width:99%'>{destination}</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>
						</table>
<script>
	var xmem$t='';
var xDeleteRemoteDomain$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue); return;};
	$('#'+xmem$t).remove();
}
	
function DeleteRemoteDomain$t(domain,id){
	xmem$t=id;
	if(confirm('$are_you_sure_to_delete '+domain)){
		var XHR = new XHRConnection();
		XHR.appendData('remotedomain-remove',domain);
		XHR.appendData('ou','{$_SESSION["ou"]}');
		XHR.sendAndLoad('$page', 'POST',xDeleteRemoteDomain$t);
	}
}
</script>
";	
	
	
}

function localdomain_search(){
	$users=new usersMenus();
	$page=CurrentPageName();
	$t=time();
	$tpl=new templates();
	$ldap=new clladp();
	$are_you_sure_to_delete=$tpl->javascript_parse_text("{are_you_sure_to_delete}");
	if($users->AsPostfixAdministrator){
		$hash=$ldap->hash_get_all_local_domains();
	}else{
		$hash=$ldap->Hash_associated_domains($_SESSION["ou"]);
	}
	
	
	$search=string_to_flexregex("localdomain-search");
	while (list ($domain, $ligne) = each ($hash) ){
		$id=md5($domain);
		$delete=imgsimple("delete-32.png",null,"DeleteLocalDomain$t('$domain','$id')");
		if($search<>null){if(!preg_match("#$search#", $domain)){continue;}}
		
		$tr[]="
		<tr id='$id'>
			<td style='font-size:18px'><i class='icon-globe'></i>&nbsp;$domain</td>
			<td style='text-align:center'>$delete</td>
		</tr>";
	}
	
	echo $tpl->_ENGINE_parse_body("
	
		<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th style='width:99%'>{domains}</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>
				</table>
<script>
	var xmem$t='';
	var xDeleteLocalDomain$t= function (obj) {	
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue); return;};
		$('#'+xmem$t).remove();
	}			
				
		function DeleteLocalDomain$t(domain,id){
			xmem$t=id;
			if(confirm('$are_you_sure_to_delete '+domain)){
				var XHR = new XHRConnection();
				XHR.appendData('localdomain-remove',domain);
				XHR.appendData('ou','{$_SESSION["ou"]}');
				XHR.sendAndLoad('$page', 'POST',xDeleteLocalDomain$t);	
			}
		}
				
</script>";	
}

function localdomain_remove(){
	include_once(dirname(__FILE__)."/ressources/class.ejabberd.inc");
	include_once(dirname(__FILE__)."/ressources/class.artica.inc");
	
	$usr=new usersMenus();
	$tpl=new templates();
	if($usr->AllowChangeDomains==false){echo $tpl->_ENGINE_parse_body('{no_privileges}');exit;}
	
	$domain=$_POST["localdomain-remove"];
	$ou=$_POST["ou"];
	$tpl=new templates();
	$artica=new artica_general();
	$ldap=new clladp();
	if($artica->RelayType=="single"){$ldap->delete_VirtualDomainsMapsMTA($ou,$domain);}
	$ldap->DeleteLocadDomain($domain,$ou);
	$sql="DELETE FROM postfix_duplicate_maps WHERE pattern='$domain'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	$jb=new ejabberd($domain);
	$jb->Delete();
	ChockServices();	
}
function remotedomain_save(){
	$ou=$_POST["ou"];
	$tpl=new templates();
	$relayIP=$_POST["destination"];
	
	if($relayIP=="127.0.0.1"){
		echo $tpl->javascript_parse_text("{NO_RELAY_TO_THIS_SERVER_EXPLAIN}");
		return;
	}
	
	$tc=new networking();
	$IPSAR=$tc->ALL_IPS_GET_ARRAY();
	
	if(!preg_match("#[0-9]\.[0-9]+\.[0-9]+\.[0-9]+#",$relayIP)){
		$ip=gethostbyname($relayIP);
		while (list ($ip1, $ip2) = each ($IPSAR)){
			if($relayIP==$ip1){
				echo $tpl->javascript_parse_text("{NO_RELAY_TO_THIS_SERVER_EXPLAIN}");
				return;
			}
		}
	
	}else{
		while (list ($ip1, $ip2) = each ($IPSAR)){
			if($relayIP==$ip1){
				echo $tpl->javascript_parse_text("{NO_RELAY_TO_THIS_SERVER_EXPLAIN}");
				return;
			}
		}
	}
	
	
	
	$relayPort=$_POST["port"];
	$mx=$_POST["MX"];
	if($mx==0){$mx="no";}else{$mx="yes";}
	$trusted_smtp_domain=$_POST["trusted_smtp_domain"];
	$domain_name=trim(strtolower($_POST["remotedomain"]));
	$ldap=new clladp();
	if(!$ldap->UseLdap){
		$sqlite=new lib_sqlite();
		$sqlite->AddRelayDomain($ou,$domain_name,$relayIP,$relayPort,$mx);
		if($sqlite->ok){ChockServices();}
		return;
	}
	
	
	$tpl=new templates();
	
	
	$dn="cn=relay_domains,ou=$ou,dc=organizations,$ldap->suffix";
	$upd=array();
	if(!$ldap->ExistsDN($dn)){
		$upd['cn'][0]="relay_domains";
		$upd['objectClass'][0]='PostFixStructuralClass';
		$upd['objectClass'][1]='top';
		$ldap->ldap_add($dn,$upd);
		unset($upd);
	}
	
	$hashdoms=$ldap->hash_get_all_domains();
	if($hashdoms[$domain_name]<>null){
		remotedomain_edit();
		return;
	}
	
	
	
	
	$dn="cn=$domain_name,cn=relay_domains,ou=$ou,dc=organizations,$ldap->suffix";
	
	$upd['cn'][0]="$domain_name";
	$upd['objectClass'][0]='PostFixRelayDomains';
			$upd['objectClass'][1]='top';
			$ldap->ldap_add($dn,$upd);
	
			$dn="cn=relay_recipient_maps,ou=$ou,dc=organizations,$ldap->suffix";
			if(!$ldap->ExistsDN($dn)){
			$upd['cn'][0]="relay_recipient_maps";
			$upd['objectClass'][0]='PostFixStructuralClass';
			$upd['objectClass'][1]='top';
			$ldap->ldap_add($dn,$upd);
			unset($upd);
			}
	
			if($trusted_smtp_domain==0){
			$dn="cn=@$domain_name,cn=relay_recipient_maps,ou=$ou,dc=organizations,$ldap->suffix";
			$upd['cn'][0]="@$domain_name";
			$upd['objectClass'][0]='PostfixRelayRecipientMaps';
		$upd['objectClass'][1]='top';
		$ldap->ldap_add($dn,$upd);
			}
	
			$dn="cn=transport_map,ou=$ou,dc=organizations,$ldap->suffix";
			if(!$ldap->ExistsDN($dn)){
			$upd['cn'][0]="transport_map";
		$upd['objectClass'][0]='PostFixStructuralClass';
		$upd['objectClass'][1]='top';
		$ldap->ldap_add($dn,$upd);
			unset($upd);
			}
			if($relayIP<>null){
			if($mx=="no"){$relayIP="[$relayIP]";}
			$dn="cn=$domain_name,cn=transport_map,ou=$ou,dc=organizations,$ldap->suffix";
			$upd['cn'][0]="$domain_name";
			$upd['objectClass'][0]='transportTable';
					$upd['objectClass'][1]='top';
					$upd["transport"][]="relay:$relayIP:$relayPort";
					$ldap->ldap_add($dn,$upd);
			}
	
			ChockServices();
	
	
	
}
function remotedomain_edit(){
	$relayIP=$_POST["destination"];
	$relayPort=$_POST["port"];
	$domain_name=$_POST["remotedomain"];
	$MX=$_POST["MX"];
	$ldap=new clladp();
	$ou=$_POST["ou"];
	$trusted_smtp_domain=$_POST["trusted_smtp_domain"];
	if($MX==0){$MX="no";}else{$MX="yes";}

	writelogs("saving relay:$relayIP:$relayPort trusted_smtp_domain=$trusted_smtp_domain",__FUNCTION__,__FILE__,__LINE__);
	$dn="cn=transport_map,ou=$ou,dc=organizations,$ldap->suffix";
	if(!$ldap->ExistsDN($dn)){
		$upd=array();
		$upd['cn'][0]="transport_map";
		$upd['objectClass'][0]='PostFixStructuralClass';
		$upd['objectClass'][1]='top';
		$ldap->ldap_add($dn,$upd);
		unset($upd);
	}
	if($MX=="no"){$relayIP="[$relayIP]";}

	$dn="cn=$domain_name,cn=transport_map,ou=$ou,dc=organizations,$ldap->suffix";
	if($ldap->ExistsDN($dn)){$ldap->ldap_delete($dn);}


	writelogs("Create $dn",__FUNCTION__,__FILE__);
	$upd=array();
	$upd['cn'][0]="$domain_name";
	$upd['objectClass'][0]='transportTable';
	$upd['objectClass'][1]='top';
	$upd["transport"][]="relay:$relayIP:$relayPort";
	if(!$ldap->ldap_add($dn,$upd)){
		echo "Error\n"."Line: ".__LINE__."\n$ldap->ldap_last_error";
		return;
	}
	unset($upd);

	$dn="cn=relay_recipient_maps,ou=$ou,dc=organizations,$ldap->suffix";
	if(!$ldap->ExistsDN($dn)){
		$upd=array();
		$upd['cn'][0]="relay_recipient_maps";
		$upd['objectClass'][0]='PostFixStructuralClass';
		$upd['objectClass'][1]='top';
		if(!$ldap->ldap_add($dn,$upd)){
			echo "Error\n"."Line: ".__LINE__."\n$ldap->ldap_last_error";
			return;
		}
		unset($upd);
	}



	$dn="cn=@$domain_name,cn=relay_recipient_maps,ou=$ou,dc=organizations,$ldap->suffix";
	if($ldap->ExistsDN($dn)){$ldap->ldap_delete($dn);}
	if($trusted_smtp_domain==1){
		$upd=array();
		$upd['cn'][0]="@$domain_name";
		$upd['objectClass'][0]='PostfixRelayRecipientMaps';
		$upd['objectClass'][1]='top';
		if(!$ldap->ldap_add($dn,$upd)){
			echo "Error\n"."Line: ".__LINE__."\n$ldap->ldap_last_error";
			return;
		}
	}

ChockServices();

}
function remotedomain_remove(){
	
	$ldap=new clladp();
	$domain_name=$_POST["remotedomain-remove"];
	$ou=$_POST["ou"];
	if($ou==null){$ou=$ldap->ou_by_smtp_domain($domain_name);}
	$ldap=new clladp();
	$ldap->DeleteRemoteDomain($domain_name,$ou);
	ChockServices();	
}


