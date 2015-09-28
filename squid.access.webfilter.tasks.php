<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	$usersmenus=new usersMenus();
	if(!$usersmenus->AsDansGuardianAdministrator){
		$tpl=new templates();
		$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
		echo "<H2>$alert</H2>";
		die();	
	}
	
	if(isset($_POST["add-to-cat"])){add_to_cat();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	
	
js();	
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	header("content-type: application/x-javascript");
	$familysite=$_GET["familysite"];
	$familysiteEnc=urlencode($familysite);
	echo "YahooWinBrowse('850','$page?popup=yes&familysite=$familysiteEnc','$familysite')";
	
	
}	


function popup(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$familysite=$_GET["familysite"];
	$q=new mysql_squid_builder();
	$sock=new sockets();
	$ldap=new clladp();
	if($ldap->IsKerbAuth()){
		
		$whitelist_auth="	<center style='width:98%' class=form>
		<center>". button("{do_not_authenticate_this_website}","WhiteNTLMThis$t()",30)."</center>
			<center style='font-size:16px;margin-top:15px;margin-bottom:20px'>{do_not_authenticate_this_website_explain}
			<br>&laquo;&nbsp;<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.urlrewriteaccessdeny.php?add-ntlm-js=yes')\"
					style='text-decoration:underline'>{authentication_whitelist}</a>&nbsp;&raquo;
			
			</center>
		</center>
		<p>&nbsp;</p>";
		
		
	}
	$EnableRangeOffset=intval($sock->GET_INFO("EnableRangeOffset"));
	if($EnableRangeOffset==1){
		$rangeoffset="	<center style='width:98%' class=form>
			<center>". button("{enforce_partial_content}","RangeOffsetLimit$t()",30)."</center>
				<center style='font-size:16px;margin-top:15px;margin-bottom:20px'>{enforce_partial_content_explain}
				<br>&laquo;&nbsp;<a href=\"javascript:blur();\" 
					OnClick=\"javascript:Loadjs('squid.urlrewriteaccessdeny.php?add-rangeoffsetlimit-js=yes')\"
					style='text-decoration:underline'>{partial_content_list}</a>&nbsp;&raquo;
			
				</center>
			</center>
			<p>&nbsp;</p>";	
	}
	
	
	
	if($sock->EnableUfdbGuard()){
		$results=$q->QUERY_SQL("SELECT * FROM personal_categories");
		while ($ligne = mysql_fetch_assoc($results)) {
			$PERSO[$ligne["category"]]=true;
		}
		
		$results=$q->QUERY_SQL("SELECT category FROM webfilter_blks WHERE modeblk=1");
		$WHITECATS[null]="{select}";
		while ($ligne = mysql_fetch_assoc($results)) {
			if(!isset($PERSO[$ligne["category"]])){continue;}
			$WHITECATS[$ligne["category"]]=$ligne["category"];
		}
	
		
			$whitelist_ufdb="	<center style='width:98%' class=form>
			<center>". button("{whitelist_this_website}","WhiteThis$t()",30)."</center>
				<center style='font-size:16px;margin-top:15px;margin-bottom:20px'>{whitelist_this_website_explain}
				<br>&laquo;&nbsp;<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.urlrewriteaccessdeny.php?add-www-js=yes')\"
						style='text-decoration:underline'>{global_whitelists}</a>&nbsp;&raquo;		
						
				</center>
			</center>
			<p>&nbsp;</p>";
		
		
		$blacklist_ufdb="	<center style='width:98%' class=form>
		<center>". button("{blacklist_this_website}","BlackUFDBThis$t()",30)."</center>
			<center style='font-size:16px;margin-top:15px;margin-bottom:20px'>{blacklist_this_website_explain}
			<br>&laquo;&nbsp;<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.urlrewriteaccessdeny.php?add-black-js=yes')\"
					style='text-decoration:underline'>{global_blacklist}</a>&nbsp;&raquo;
			
			</center>
		</center>
		<p>&nbsp;</p>";		
		
		
		$white_category="	<p>&nbsp;</p>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td style='font-size:22px' class=legend>{save_into_a_whitelisted_category}:</td>
		<td>". Field_array_Hash($WHITECATS, "category-$t",null,"style:font-size:22px")."</td>
	</tr>
	<tr>
	<td colspan=2 align='right'><hr>". button("{add}","CatzThis$t()",30)."</td>
	</tr>
	</table>";
	
	}
	
	
	$html="<div style='font-size:35px;margin-bottom:20px'>&laquo;&nbsp;$familysite&nbsp;&raquo;</div>
	$whitelist_auth
	$whitelist_ufdb
	$rangeoffset
		<center style='width:98%' class=form>	
		<center>". button("{do_not_cache}","NocacheThis$t()",30)."</center>
			<center style='font-size:16px;margin-top:15px;margin-bottom:20px'>{do_not_cache_this_web_site_explain}
			<br>&laquo;&nbsp;<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.urlrewriteaccessdeny.php?add-nocache-js=yes')\"
					style='text-decoration:underline'>{global_deny_cache_list}</a>&nbsp;&raquo;		
					
			</center>
		</center>	
	$blacklist_ufdb			
			<p>&nbsp;</p>		
				
			$white_category
	</div>
<script>

var CallBack$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	var category=document.getElementById('category-$t').value;
	YahooWinBrowseHide();
	Loadjs('ufdbguard.compile.category.php?category='+category);
}	
var CallBackNocacheThis$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	YahooWinBrowseHide();
	Loadjs('squid.global.wl.center.progress.php');
}
function CatzThis$t(){
	var XHR = new XHRConnection();
	XHR.appendData('add-to-cat', '$familysite');
	XHR.appendData('category', document.getElementById('category-$t').value);	      
	XHR.sendAndLoad('$page', 'POST',CallBack$t);  			
}

function NocacheThis$t(){
	var XHR = new XHRConnection();
	XHR.appendData('nocache_single', '$familysite');    
	XHR.sendAndLoad('squid.urlrewriteaccessdeny.php', 'POST',CallBackNocacheThis$t); 

}

function RangeOffsetLimit$t(){
	var XHR = new XHRConnection();
	XHR.appendData('rangeoffsetlimit_single', '$familysite');    
	XHR.sendAndLoad('squid.urlrewriteaccessdeny.php', 'POST',CallBackNocacheThis$t); 
}

function WhiteNTLMThis$t(){
	var XHR = new XHRConnection();
	XHR.appendData('nonntlm_single', '$familysite');    
	XHR.sendAndLoad('squid.urlrewriteaccessdeny.php', 'POST',CallBackNocacheThis$t); 

}

var CallBack2$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	var category=document.getElementById('category-$t').value;
	YahooWinBrowseHide();
	Loadjs('squid.compile.whiteblack.progress.php');
}
	
// 
function WhiteThis$t(){
	var XHR = new XHRConnection();
	XHR.appendData('whitelist-single', '$familysite');		
	XHR.sendAndLoad('squid.urlrewriteaccessdeny.php', 'POST',CallBack2$t); 

	}
function BlackUFDBThis$t(){
	var XHR = new XHRConnection();
	XHR.appendData('blacklist-single', '$familysite');		
	XHR.sendAndLoad('squid.urlrewriteaccessdeny.php', 'POST',CallBack2$t); 

	}	

</script>			
			
			
";
					
echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
	
	
}

function add_to_cat(){
	$q=new mysql_squid_builder();
	$q->categorize($_POST["add-to-cat"], $_POST["category"],true);
	}
