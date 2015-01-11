<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	if(posix_getuid()==0){die();}
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["auth_param_ntlm_children"])){Save();exit;}
	
	
	js();
	
	
function js() {

	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{squid_plugins}");
	$page=CurrentPageName();
	$html="YahooWin6('600','$page?popup=yes','$title')";
	echo $html;	
	
}

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$sock=new sockets();
	$SquidClientParams=unserialize(base64_decode($sock->GET_INFO("SquidClientParams")));
	$warn_squid_restart=$tpl->javascript_parse_text("{warn_squid_restart}");

	
	
	$t=time();
	$maxMem=500;
	$CPUS=0;
	$currentMem=intval($sock->getFrameWork("cmd.php?GetTotalMemMB=yes"));
	
	if($currentMem>0){
		$maxMem=$currentMem-500;
	}
	
		$users=new usersMenus();
		$CPUS=$users->CPU_NUMBER;
		$CPUS_TEXT=" (X $CPUS)";

		
	if(!is_numeric($SquidClientParams["auth_param_ntlm_children"])){$SquidClientParams["auth_param_ntlm_children"]=20;}
	if(!is_numeric($SquidClientParams["auth_param_ntlm_startup"])){$SquidClientParams["auth_param_ntlm_startup"]=0;}
	if(!is_numeric($SquidClientParams["auth_param_ntlm_idle"])){$SquidClientParams["auth_param_ntlm_idle"]=1;}
	
	if(!is_numeric($SquidClientParams["auth_param_basic_children"])){$SquidClientParams["auth_param_basic_children"]=3;}
	if(!is_numeric($SquidClientParams["auth_param_basic_startup"])){$SquidClientParams["auth_param_basic_startup"]=2;}
	if(!is_numeric($SquidClientParams["auth_param_basic_idle"])){$SquidClientParams["auth_param_basic_idle"]=1;}

	if(!is_numeric($SquidClientParams["url_rewrite_children"])){$SquidClientParams["url_rewrite_children"]=10;}
	if(!is_numeric($SquidClientParams["url_rewrite_startup"])){$SquidClientParams["url_rewrite_startup"]=0;}
	if(!is_numeric($SquidClientParams["url_rewrite_idle"])){$SquidClientParams["url_rewrite_idle"]=1;}
	
	if(!is_numeric($SquidClientParams["external_acl_children"])){$SquidClientParams["external_acl_children"]=5;}
	if(!is_numeric($SquidClientParams["external_acl_startup"])){$SquidClientParams["external_acl_startup"]=0;}
	if(!is_numeric($SquidClientParams["external_acl_idle"])){$SquidClientParams["external_acl_idle"]=1;}
	

	
		
	
		
	
	$html="
	<div id='$t-div'></div>
	<div class=text-info style='font-size:18px;'>{SquidClientParams_text}</div>
	<div style='font-size:16px;font-weight:bold;text-align:center;color:#E71010' id='$t-multi'></div>
	<div style='width:98%' class=form>
	<table style='width:99%'>
	
	<tr><td colspan=2 style='font-size:32px'>{authentication_modules}$CPUS_TEXT</td></tr>
	
	
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>NTLM {max_processes}:</td>
		<td width=99%>". Field_text("auth_param_ntlm_children-$t",$SquidClientParams["auth_param_ntlm_children"],"font-size:22px;width:90px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>NTLM {preload_processes}:</td>
		<td width=99%>". Field_text("auth_param_ntlm_startup-$t",$SquidClientParams["auth_param_ntlm_startup"],"font-size:22px;width:90px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>NTLM {prepare_processes}:</td>
		<td width=99%>". Field_text("auth_param_ntlm_idle-$t",$SquidClientParams["auth_param_ntlm_idle"],"font-size:22px;width:90px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>Basic/LDAP {max_processes}:</td>
		<td width=99%>". Field_text("auth_param_basic_children-$t",$SquidClientParams["auth_param_basic_children"],"font-size:22px;width:90px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>Basic/LDAP {preload_processes}:</td>
		<td width=99%>". Field_text("auth_param_basic_startup-$t",$SquidClientParams["auth_param_basic_startup"],"font-size:22px;width:90px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>Basic/LDAP {prepare_processes}:</td>
		<td width=99%>". Field_text("auth_param_basic_idle-$t",$SquidClientParams["auth_param_basic_idle"],"font-size:22px;width:90px")."</td>
	</tr>	
				
				
	<tr><td colspan=2 style='font-size:32px;margin-top:15px'>{filtering_modules}$CPUS_TEXT</td></tr>
	
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>{web_filtering} {max_processes}:</td>
		<td width=99%>". Field_text("url_rewrite_children-$t",$SquidClientParams["url_rewrite_children"],"font-size:22px;width:90px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>{web_filtering} {preload_processes}:</td>
		<td width=99%>". Field_text("url_rewrite_startup-$t",$SquidClientParams["url_rewrite_startup"],"font-size:22px;width:90px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>{web_filtering} {prepare_processes}:</td>
		<td width=99%>". Field_text("url_rewrite_idle-$t",$SquidClientParams["url_rewrite_idle"],"font-size:22px;width:90px")."</td>
	</tr>
				

	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>{acls_modules} {max_processes}:</td>
		<td width=99%>". Field_text("external_acl_children-$t",$SquidClientParams["external_acl_children"],"font-size:22px;width:90px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>{acls_modules} {preload_processes}:</td>
		<td width=99%>". Field_text("external_acl_startup-$t",$SquidClientParams["external_acl_startup"],"font-size:22px;width:90px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>{acls_modules} {prepare_processes}:</td>
		<td width=99%>". Field_text("external_acl_idle-$t",$SquidClientParams["external_acl_idle"],"font-size:22px;width:90px")."</td>
	</tr>				
				
	<tr>
		<td colspan=2 style='font-size:18px' align='right'>".button("{apply}","Save$t()",32)."</td></tr>
	<tr>	
	</table>
<script>
	var xSave$t=function(obj){
     	var tempvalue=obj.responseText;
      	if(tempvalue.length>3){alert(tempvalue);}
      	YahooWin3Hide();
     	}	

	function Save$t(){
		if(confirm('$warn_squid_restart')){
			var XHR = new XHRConnection();
			XHR.appendData('auth_param_ntlm_children',document.getElementById('auth_param_ntlm_children-$t').value);
			XHR.appendData('auth_param_ntlm_startup',document.getElementById('auth_param_ntlm_startup-$t').value);
			XHR.appendData('auth_param_ntlm_idle',document.getElementById('auth_param_ntlm_idle-$t').value);
			
			XHR.appendData('auth_param_basic_children',document.getElementById('auth_param_basic_children-$t').value);
			XHR.appendData('auth_param_basic_startup',document.getElementById('auth_param_basic_startup-$t').value);
			XHR.appendData('auth_param_basic_idle',document.getElementById('auth_param_basic_idle-$t').value);
			
			XHR.appendData('url_rewrite_children',document.getElementById('url_rewrite_children-$t').value);
			XHR.appendData('url_rewrite_startup',document.getElementById('url_rewrite_startup-$t').value);
			XHR.appendData('url_rewrite_idle',document.getElementById('url_rewrite_idle-$t').value);
						
			XHR.appendData('external_acl_children',document.getElementById('external_acl_children-$t').value);
			XHR.appendData('external_acl_startup',document.getElementById('external_acl_startup-$t').value);
			XHR.appendData('external_acl_idle',document.getElementById('external_acl_idle-$t').value);
						
			
			XHR.sendAndLoad('$page', 'POST',xSave$t);		
		}
	
	}		
	
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function Save(){
	$sock=new sockets();
	$SquidClientParams=unserialize(base64_decode($sock->GET_INFO("SquidClientParams")));
	while (list ($num, $ligne) = each ($_POST) ){
		$SquidClientParams[$num]=$ligne;
		
	}
	
	$sock->SaveConfigFile(base64_encode(serialize($SquidClientParams)), "SquidClientParams");
	$sock->getFrameWork("cmd.php?squid-restart=yes");
	
}

