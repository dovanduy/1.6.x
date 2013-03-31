<?php
	session_start();
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.pure-ftpd.inc');
	include_once('ressources/class.apache.inc');
	include_once('ressources/class.freeweb.inc');
	include_once('ressources/class.user.inc');
	$user=new usersMenus();
	if($user->AsWebMaster==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["freeweb-aliases-list"])){alias_list();exit;}
	if(isset($_POST["max_extend_count"])){Save();exit;}
	if(isset($_POST["DelAlias"])){alias_del();exit;}
	if(isset($_GET["new-alias"])){alias_popup();exit;}
	if(isset($_GET["auth-js"])){auth_js();exit;}
	if(isset($_GET["auth-popup"])){auth_popup();exit;}
	if(isset($_GET["connection-form"])){connection_form();exit;}
	if(isset($_POST["connectiontype"])){auth_save();exit;}
	page();	
	
function page(){
$tpl=new templates();
$page=CurrentPageName();
$alias=$tpl->_ENGINE_parse_body("{alias}");
$directory=$tpl->_ENGINE_parse_body("{directory}");
$description=$tpl->_ENGINE_parse_body("{description}");
$new_alias=$tpl->_ENGINE_parse_body("{new_folder}");
$webdavfolders=$tpl->_ENGINE_parse_body("{webdav_folders}");
$free=new freeweb($_GET["servername"]);
$t=time();
$params=$free->Params["FILEZ"];
$ldap=new clladp();

if($params["SMTP_SERVER"]==null){$params["SMTP_SERVER"]="127.0.0.1";}
if($params["MAILFROM"]==null){$params["MAILFROM"]="root@mydomain.tld";}
if($params["FROMNAME"]==null){$params["FROMNAME"]="The Postmaster";}
if($params["LDAP_SERVER"]==null){$params["LDAP_SERVER"]="127.0.0.1";}
if($params["LDAP_SUFFIX"]==null){$params["LDAP_SUFFIX"]="dc=organizations,$ldap->suffix";}
if($params["LDAP_DN"]==null){$params["LDAP_DN"]="cn=$ldap->ldap_admin,$ldap->suffix";}
if($params["LDAP_PASSWORD"]==null){$params["LDAP_PASSWORD"]="$ldap->ldap_password";}
if($params["LDAP_FILTER"]==null){$params["LDAP_FILTER"]="(&(objectclass=*)(uid=%s))";}



if(!is_numeric($params["post_max_size"])){$params["post_max_size"]=750;}
if(!is_numeric($params["upload_max_filesize"])){$params["upload_max_filesize"]=750;}
if(!is_numeric($params["max_file_lifetime"])){$params["max_file_lifetime"]=20;}
if(!is_numeric($params["default_file_lifetime"])){$params["default_file_lifetime"]=10;}
if(!is_numeric($params["max_extend_count"])){$params["max_extend_count"]=7;}
if(!is_numeric($params["user_quota"])){$params["user_quota"]=2;}



$html="
<div id='$t'></div>
		<table style='width:99%' class=form>
<tbody>
<tr>
	<td class=legend style='font-size:16px'>{smtp_server}:</td>
	<td style='font-size:16px'>". Field_text("SMTP_SERVER-$t",$params["SMTP_SERVER"],"font-size:16px;width:320px")."</td>
</tr>
<tr>
	<td class=legend style='font-size:16px'>{mail_from}:</td>
	<td style='font-size:16px'>". Field_text("MAILFROM-$t",$params["MAILFROM"],"font-size:16px;width:220px")."</td>
</tr>	
<tr>
	<td class=legend style='font-size:16px'>{from_name}:</td>
	<td style='font-size:16px'>". Field_text("FROMNAME-$t",$params["FROMNAME"],"font-size:16px;width:220px")."</td>
</tr>
	<tr>
		<td class=legend style='font-size:16px'>{user_quotaz}:</td>
		<td style='font-size:14px'>". Field_text("user_quota-$t",$params["user_quota"],"font-size:16px;width:60px")."&nbsp;G</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:16px'>{post_max_size}:</td>
		<td style='font-size:14px'>". Field_text("post_max_size-$t",$params["post_max_size"],"font-size:16px;width:60px")."&nbsp;M</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{upload_max_filesize}:</td>
		<td style='font-size:14px'>". Field_text("upload_max_filesize-$t",$params["post_max_size"],"font-size:16px;width:60px")."&nbsp;M</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{max_file_lifetime}:</td>
		<td style='font-size:14px'>". Field_text("max_file_lifetime-$t",$params["max_file_lifetime"],"font-size:16px;width:90px")."&nbsp;{days}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{default_file_lifetime}:</td>
		<td style='font-size:14px'>". Field_text("default_file_lifetime-$t",$params["default_file_lifetime"],"font-size:16px;width:90px")."&nbsp;{days}</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{max_extend_count_filez}:</td>
		<td style='font-size:14px'>". Field_text("max_extend_count-$t",$params["max_extend_count"],"font-size:16px;width:90px")."&nbsp;{days}</td>
	</tr>
	<td class=legend style='font-size:16px'>{ldap_server}:</td>
	<td>". Field_text("LDAP_SERVER-$t",$params["LDAP_SERVER"],"font-size:16px;padding:3px;width:190px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{ldap_suffix}:</td>
		<td>". Field_text("LDAP_SUFFIX-$t",$params["LDAP_SUFFIX"],"font-size:14px;padding:3px;width:390px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{ldap_filter}:</td>
		<td>". Field_text("LDAP_FILTER-$t",$params["LDAP_FILTER"],"font-size:14px;padding:3px;width:390px")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:16px'>{username} ({read}):</td>
		<td>". Field_text("LDAP_DN-$t",$params["LDAP_DN"],"font-size:14px;padding:3px;width:390px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{password}:</td>
		<td>". Field_password("LDAP_PASSWORD-$t",$params["LDAP_PASSWORD"],"font-size:16px;padding:3px;width:190px")."</td>
	</tr>
<tr>
	<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",18)."</td></tr>				
				
</table>			
<script>
		var x_Save$t= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);document.getElementById('$t').innerHTML='';return;}
			document.getElementById('$t').innerHTML='';
			$('#$t').flexReload();
		}


		function Save$t(){
			var XHR = new XHRConnection();
			XHR.appendData('servername', '{$_GET["servername"]}');
			XHR.appendData('max_extend_count', document.getElementById('max_extend_count-$t').value); 
			XHR.appendData('default_file_lifetime', document.getElementById('default_file_lifetime-$t').value); 
			XHR.appendData('upload_max_filesize', document.getElementById('upload_max_filesize-$t').value); 
			XHR.appendData('post_max_size', document.getElementById('post_max_size-$t').value); 
			XHR.appendData('user_quota', document.getElementById('user_quota-$t').value); 
			XHR.appendData('FROMNAME', document.getElementById('FROMNAME-$t').value); 
			XHR.appendData('MAILFROM', document.getElementById('MAILFROM-$t').value); 
			XHR.appendData('SMTP_SERVER', document.getElementById('SMTP_SERVER-$t').value);
			
		
			XHR.appendData('LDAP_SERVER', document.getElementById('LDAP_SERVER-$t').value);
			
			XHR.appendData('LDAP_SUFFIX', document.getElementById('LDAP_SUFFIX-$t').value);
			XHR.appendData('LDAP_DN', document.getElementById('LDAP_DN-$t').value);
			XHR.appendData('LDAP_PASSWORD', encodeURIComponent(document.getElementById('LDAP_PASSWORD-$t').value));
			XHR.appendData('LDAP_FILTER', encodeURIComponent(document.getElementById('LDAP_FILTER-$t').value));
			
			AnimateDiv('$t');
			XHR.sendAndLoad('$page', 'POST',x_Save$t);
		}
</script>						
";

echo $tpl->_ENGINE_parse_body($html);



}	
function Save(){
	$free=new freeweb($_POST["servername"]);
	
	if(isset($_POST["LDAP_PASSWORD"])){$_POST["LDAP_PASSWORD"]=url_decode_special_tool($_POST["LDAP_PASSWORD"]);}
	if(isset($_POST["LDAP_FILTER"])){$_POST["LDAP_FILTER"]=url_decode_special_tool($_POST["LDAP_FILTER"]);}
	
	$t=time();
	
	
	while (list ($num, $line) = each ($_POST)){
		$free->Params["FILEZ"][$num]=$line;
	}
	$free->SaveParams();
	
}


