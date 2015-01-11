<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.cron.inc');
	include_once('ressources/class.system.network.inc');
	
	$users=new usersMenus();
	if(!$users->AsPostfixAdministrator){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}	
	
	
	
	
	if(isset($_POST["EnableZarafaSearch"])){ZarafaSave();exit;}	
	if(isset($_GET["popup"])){popup();exit;}
	
	
js();
	
function js(){
	$usersmenus=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();		
	
	$title=$tpl->_ENGINE_parse_body("{delivery_agent}");
	echo "YahooWin3('590','$page?popup=yes','Zarafa Search')";
	
}	
	



function popup(){
	$sock=new sockets();
	$page=CurrentPageName();
	$EnableZarafaSearch=$sock->GET_INFO("EnableZarafaSearch");
	$EnableZarafaSearchAttach=$sock->GET_INFO("EnableZarafaSearchAttach");
	
	$ZarafaIndexPath=$sock->GET_INFO("ZarafaIndexPath");
	if($ZarafaIndexPath==null){$ZarafaIndexPath="/var/lib/zarafa/index";}
	if(!is_numeric($EnableZarafaSearch)){$EnableZarafaSearch=1;}
	$t=time();

	$html="
	<div id='div-$t'></div>
	<div class=text-info style='font-size:16px'>{zarafa_search_explain}</div>
		<table style='width:99%' class=form>
			<tr>	
				<td class=legend style='font-size:16px'>{enable_zarafa_search}:</td>
				<td style='font-size:16px'>". Field_checkbox("EnableZarafaSearch-$t", $EnableZarafaSearch,1,"EnableZarafaSearchCk()")."</td>
			</tr>		
			<tr>	
				<td class=legend style='font-size:16px'>{index_in_attachments}:</td>
				<td style='font-size:16px'>". Field_checkbox("EnableZarafaSearchAttach-$t", $EnableZarafaSearchAttach,1)."</td>
			</tr>				
			<tr>	
				<td class=legend style='font-size:16px'>{directory}:</td>
				<td style='font-size:16px'>". Field_text("ZarafaIndexPath", "$ZarafaIndexPath","font-size:16px;width:220px")."</td>
			</tr>				
			<tr>
				<td align='right' colspan=2><hr>". button("{apply}","Zarafa$t()","18px")."</td>
			</tr>		
		</table>
	<script>
	var x_Zarafa$t= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		document.getElementById('div-$t').innerHTML='';
		
	}	
		
	
	function Zarafa$t(){
		var XHR = new XHRConnection();
		var EnableZarafaSearch=0;
		var EnableZarafaSearchAttach=0;
		if(document.getElementById('EnableZarafaSearch-$t').checked){EnableZarafaSearch=1;}
		if(document.getElementById('EnableZarafaSearchAttach-$t').checked){EnableZarafaSearchAttach=1;}
		XHR.appendData('EnableZarafaSearch',EnableZarafaSearch);
		XHR.appendData('EnableZarafaSearchAttach',EnableZarafaSearchAttach);
		XHR.appendData('ZarafaIndexPath',document.getElementById('ZarafaIndexPath').value);
		AnimateDiv('div-$t');
		XHR.sendAndLoad('$page', 'POST',x_Zarafa$t);
		}
		
	function EnableZarafaSearchCk(){
		var EnableZarafaSearch=0;
		document.getElementById('EnableZarafaSearchAttach-$t').disabled=true;
		if(document.getElementById('EnableZarafaSearch-$t').checked){
			document.getElementById('EnableZarafaSearchAttach-$t').disabled=false;
		}
	}
		
	</script>	
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}


function ZarafaSave(){
	$sock=new sockets();
	$sock->SET_INFO("EnableZarafaSearch", $_POST["EnableZarafaSearch"]);
	$sock->SET_INFO("EnableZarafaSearchAttach", $_POST["EnableZarafaSearchAttach"]);
	$sock->SET_INFO("ZarafaIndexPath", $_POST["ZarafaIndexPath"]);
	$sock->getFrameWork("zarafa.php?restart-search=yes");
}

