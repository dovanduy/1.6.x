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


js();

function js(){
	header("content-type: application/x-javascript");
	$t=$_GET["t"];
	$time=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$servername=urlencode($_GET["servername"]);
	$servername2=$_GET["servername"];
	$title=$tpl->javascript_parse_text("{replace_rules}::{import}");
	echo "YahooWin2('990','$page?popup=yes&servername=$servername','$servername2:$title')";



}


function popup(){
	$servername=$_GET["servername"];
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$q=new mysql_squid_builder();
	
	$html="

	<div style='font-size:40px;margin-bottom:20px;margin-top:10px'>{replace_rules}::{import}</div>
	<p class=explain style='font-size:18px'>{nginx_bulk_import_replace_explain}</p>
	<div style='width:98%' class=form>

	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{remove_before_import}:</td>
		<td>". Field_checkbox("Remove-$t", 1,0)."</td>
	</tr>	
		<tr>
		<td class=legend style='font-size:18px;text-align:left' colspan=2>{patterns}:</td>
		</tr>
		<tr>
		<td colspan=2>
		<textarea style='margin-top:5px;font-family:Courier New;
		font-weight:bold;width:95%;height:450px;border:5px solid #8E8E8E;overflow:auto;font-size:14px !important'
		id='textToParseCats$t'></textarea>
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
	
		if(document.getElementById('Remove-$t').checked){
		XHR.appendData('remove',1);
	}else{
		XHR.appendData('remove',0);
	
	}
	XHR.appendData('import',encodeURIComponent(document.getElementById('textToParseCats$t').value));
	XHR.appendData('servername','$servername');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

</script>
";
	echo $tpl->_ENGINE_parse_body($html);




}


function GetPrivs(){
	$NGNIX_PRIVS=$_SESSION["NGNIX_PRIVS"];
	$users=new usersMenus();
	if($users->AsSystemWebMaster){return true;}
	if($users->AsSquidAdministrator){return true;}
	if(count($_SESSION["NGNIX_PRIVS"])>0){return true;}

	return false;

}


function import(){
	$tpl=new templates();
	$servername=$_POST["servername"];
	$q=new mysql_squid_builder();
	$replacebyText=$tpl->javascript_parse_text("{replaceby}");
	$T=array();
	if($_POST["remove"]==1){
		$q->QUERY_SQL("DELETE FROM nginx_replace_www WHERE servername='$servername'");
		
	}
	
	$_POST["import"]=url_decode_special_tool($_POST["import"]);
	
	$f=explode("\n",$_POST["import"]);
	
	$prefix="INSERT IGNORE INTO nginx_replace_www
			(`rulename`,`stringtosearch`,`replaceby`,`tokens`,`servername`,`zorder`) VALUES ";
	
	$c=0;
	while (list ($index, $line) = each ($f)){
		$line=trim($line);
		$c++;
		if($line==null){continue;}
		$exp=explode(";",$line);
		if(count($exp)<2){continue;}
		$stringtosearch=mysql_escape_string2($exp[1]);
		$replaceby=mysql_escape_string2($exp[0]);
		$rulename="$stringtosearch $replacebyText $replaceby";
		$T[]="('$rulename','$stringtosearch','$replaceby','g','$servername','$c')";
	}

	if(count($T)>0){
		
		$q->QUERY_SQL("$prefix ".@implode(",", $T));
		if(!$q->ok){echo $q->mysql_error;}
	}
	
	
	
	
}

