<?php
session_start();
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");
$PRIV=GetPrivs();if(!$PRIV){senderror("no priv");}


if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["import"])){import();exit;}


popup();


function popup(){
	$filename="/usr/share/artica-postfix/ressources/logs/web/nginx.importbulk";
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$q=new mysql_squid_builder();
	$sql=" SELECT * FROM authenticator_rules WHERE enabled=1 ORDER BY rulename";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){senderrors($q->mysql_error."<br>$sql");}
	$authrules[null]="{none}";
	while ($ligne = mysql_fetch_assoc($results)) {
		$authrules[$ligne["ID"]]=$ligne["rulename"];
	}
	
	$CONF=unserialize(@file_get_contents($filename));
	
	$html="

	<div style='font-size:40px;margin-bottom:20px;margin-top:10px'>{bulk_import}</div>
	<p class=text-info style='font-size:18px'>{nginx_bulk_import_explain}</p>
	<div style='width:98%' class=form>
	
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{remove_old_imports}:</td>
		<td>". Field_checkbox("RemoveOldImports", 1,$CONF["RemoveOldImports"])."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{websites}:</td>
		<td>". Field_text("RandomText", $CONF["RandomText"],"explain={nginx_import_random_explain};font-size:18px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{authentication}:</td>
		<td>". Field_array_Hash($authrules,"authentication", $CONF["authentication"],"style:font-size:18px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px;text-align:left' colspan=2>{destinations}:</td>
	</tr>
	<tr>
	<td colspan=2>
	<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:95%;height:450px;border:5px solid #8E8E8E;overflow:auto;font-size:14px !important'
	id='textToParseCats$t'>{$CONF["import"]}</textarea>
	</td>
	</tr>
	<tr>
		<td colspan=2 align='right'>
	<hr>
	<div style='text-align:right'>
	". button("{submit}", "Save$t()",26).
			"</div>
	</td>
	</tr>
	</table>
</div>
<script>
	var xSave$t=function (obj) {
	var results=obj.responseText;
	UnlockPage();
	if (results.length>3){
		document.getElementById('textToParseCats$t').value=results;
	}
	ExecuteByClassName('SearchFunction');
}


function Save$t(){
	var XHR = new XHRConnection();
	LockPage();
	XHR.appendData('import',encodeURIComponent(document.getElementById('textToParseCats$t').value));
	if(document.getElementById('RemoveOldImports').checked){
		XHR.appendData('RemoveOldImports',1);
	}else{
		XHR.appendData('RemoveOldImports',0);
	
	}
	XHR.appendData('RandomText',document.getElementById('RandomText').value);
	XHR.appendData('authentication',document.getElementById('authentication').value);
	document.getElementById('textToParseCats$t').value='Please wait....'
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

</script>
";
echo $tpl->_ENGINE_parse_body($html);




}

function import(){

	$_POST["import"]=url_decode_special_tool($_POST["import"]);
	$filename="/usr/share/artica-postfix/ressources/logs/web/nginx.importbulk";
	@unlink($filename);
	@file_put_contents($filename, serialize($_POST));
	if(!is_file($filename)){echo "Fatal, permission denied\n";return;}
	$sock=new sockets();
	$sock->getFrameWork("nginx.php?import-bulk=yes");
	echo @file_get_contents("/usr/share/artica-postfix/ressources/logs/web/nginx.import-bulk.results");

}


function GetPrivs(){
	$NGNIX_PRIVS=$_SESSION["NGNIX_PRIVS"];
	$users=new usersMenus();
	if($users->AsSystemWebMaster){return true;}
	if($users->AsSquidAdministrator){return true;}
	if(count($_SESSION["NGNIX_PRIVS"])>0){return true;}

	return false;

}