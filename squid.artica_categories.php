<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.ccurl.inc');
	
	
	$user=new usersMenus();
	if($user->AsDansGuardianAdministrator==false){
		$tpl=new templates();
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		die();
	}	
	
	if(isset($_POST["CategoriesHelperChildrenMax"])){Save();exit;}

	page();
function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$SquidClientParams=unserialize(base64_decode($sock->GET_INFO("SquidClientParams")));
	$CategoriesHelperConcurrency=intval($sock->GET_INFO("CategoriesHelperConcurrency"));
	$CategoriesHelperChildrenMax=intval($sock->GET_INFO("CategoriesHelperChildrenMax"));
	$CategoriesHelperHidden=intval($sock->GET_INFO("CategoriesHelperHidden"));
	$CategoriesHelperStartup=intval($sock->GET_INFO("CategoriesHelperStartup"));
	$CategoriesHelperPostitiveTTL=intval($sock->GET_INFO("CategoriesHelperPostitiveTTL"));
	$CategoriesHelperNegativeTTL=intval($sock->GET_INFO("CategoriesHelperNetgativeTTL"));


	if($CategoriesHelperNegativeTTL==0){$CategoriesHelperNegativeTTL=360;}
	if($CategoriesHelperPostitiveTTL==0){$CategoriesHelperPostitiveTTL=360;}
	if($CategoriesHelperChildrenMax==0){$CategoriesHelperChildrenMax=5;}
	if($CategoriesHelperStartup==0){$CategoriesHelperStartup=2;}
	if($CategoriesHelperHidden==0){$CategoriesHelperHidden=1;}

	for($i=0;$i<100;$i++){
		$url_rewrite_children_startup[$i]=" $i ";

	}

	$url_rewrite_children_concurrency[0]=" 0 " ;
	$url_rewrite_children_concurrency[2]=" 2 ";
	$url_rewrite_children_concurrency[3]=" 3 ";
	$url_rewrite_children_concurrency[4]=" 4 ";


	

	
	$html="
	<div id='anim-$t' style='font-size:30px;margin-bottom:30px'>{categories_helper_performance}</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
	<tr>
	<td class=legend style='font-size:22px'>{CHILDREN_MAX}:</td>
	<td style='font-size:22px'>".Field_array_Hash($url_rewrite_children_startup,
			"CategoriesHelperChildrenMax",
	$CategoriesHelperChildrenMax,null,null,0,"font-size:22px;width:90px;")."&nbsp;{processes}</td>
	</tr>
	<tr>
		<tr>
		<td class=legend style='font-size:22px'>{CHILDREN_STARTUP}:</td>
		<td style='font-size:22px'>".Field_array_Hash($url_rewrite_children_startup,
				"CategoriesHelperStartup",
	$CategoriesHelperStartup,null,null,0,"font-size:22px;width:90px;")."&nbsp;{processes}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{CHILDREN_IDLE}:</td>
		<td style='font-size:22px'>". Field_array_Hash($url_rewrite_children_startup,
				"CategoriesHelperHidden",
	$CategoriesHelperHidden,null,null,0,"font-size:22px;width:90px;")."&nbsp;{processes}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{CHILDREN_CONCURRENCY}:</td>
		<td style='font-size:22px'>". Field_array_Hash($url_rewrite_children_concurrency,
				"CategoriesHelperConcurrency",
	$CategoriesHelperConcurrency,null,null,0,"font-size:22px;width:90px;")."&nbsp;{processes}</td>
	</tr>
			
	<tr>
		<td class=legend style='font-size:22px'>{POSITIVE_CACHE_TTL}:</td>
		<td style='font-size:22px'>". Field_text("CategoriesHelperPostitiveTTL",
				"$CategoriesHelperPostitiveTTL","font-size:22px;width:120px;")."&nbsp;{seconds}</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:22px'>{NEGATIVE_CACHE_TTL}:</td>
		<td style='font-size:22px'>". Field_text("CategoriesHelperNegativeTTL",
				"$CategoriesHelperNegativeTTL","font-size:22px;width:120px;")."&nbsp;{seconds}</td>
	</tr>			
	<tr>
<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",32)."</td>
</tr>
</table>
</div>
<script>
var xSave$t=function (obj) {
	Loadjs('squid.compile.php');
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('CategoriesHelperChildrenMax',document.getElementById('CategoriesHelperChildrenMax').value);
	XHR.appendData('CategoriesHelperStartup',document.getElementById('CategoriesHelperStartup').value);
	XHR.appendData('CategoriesHelperHidden',document.getElementById('CategoriesHelperHidden').value);
	XHR.appendData('CategoriesHelperConcurrency',document.getElementById('CategoriesHelperConcurrency').value);
	XHR.appendData('CategoriesHelperPostitiveTTL',document.getElementById('CategoriesHelperPostitiveTTL').value);
	XHR.appendData('CategoriesHelperNegativeTTL',document.getElementById('CategoriesHelperNegativeTTL').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
";
echo $tpl->_ENGINE_parse_body($html);

}

function Save(){
	$sock=new sockets();
	
	while (list ($a, $b) = each ($_POST)){
		$sock->SET_INFO($a, $b);
		
	}
	
	
}
