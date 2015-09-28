<?php
if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.mysql-meta.inc');
	
	$users=new usersMenus();
	if(!$users->AsDansGuardianAdministrator){die();}	
	if(isset($_GET["events"])){popup_list();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["domains"])){save();exit;}
	if(isset($_GET["add-www-js"])){add_www_js();exit;}
	if(isset($_GET["add-black-js"])){add_black_js();exit;}
	js();

	
function js(){
	header("content-type: application/x-javascript");
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{whitelist} (Meta)");
	echo "YahooWin4('650','$page?popup=yes&t=$t','$title')";
	
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_meta();
	$q->CheckTables();
	
	$sock=new sockets();
	$EnableArticaMetaServer=intval($sock->GET_INFO("EnableArticaMetaServer"));
	
	
	$results=$q->QUERY_SQL("SELECT * FROM squid_whitelists ORDER BY `pattern`");
	while ($ligne = mysql_fetch_assoc($results)) {
		$tr[]=$ligne["pattern"];
	}
	
	
	$t=time();
	$html="<div style='font-size:22px'>{whitelist} (Meta)</div>
	<div class=explain style='font-size:18px'>
			{squid_whitelist_meta_explain}
	</div>	
	<textarea style='margin-top:5px;font-family:Courier New;
		font-weight:bold;width:95%;height:350px;border:5px solid #8E8E8E;overflow:auto;font-size:16px !important'
		id='form$t'>".@implode("\n", $tr)."</textarea>
	<div style='text-align:right'><hr>". button("{apply}","Save$t()",36)."</div>
	<script>
var xSave$t=function(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	var EnableArticaMetaServer=$EnableArticaMetaServer;
	if(EnableArticaMetaServer==0){
		Loadjs('squid.compile.whiteblack.progress.php?ask=yes');
	}
}


function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('domains',document.getElementById('form$t').value);
		XHR.sendAndLoad('$page', 'POST',xSave$t);	
}	
	
</script>
";

	echo $tpl->_ENGINE_parse_body($html);
	
}


function save(){
	
	$q=new mysql_meta();
	$f=array();
	$f=explode("\n",$_POST["domains"]);
	
	while (list ($index, $line) = each ($f) ){
		$line=trim(strtolower($line));
		if($line==null){continue;}
		$line=mysql_escape_string2($line);
		$md5=md5($line);
		$n[]="('$md5','$line')";
		
	}
	$q->CheckTables();
	$q->QUERY_SQL("TRUNCATE TABLE `squid_whitelists`");
	if(count($n)>0){
		$q->QUERY_SQL("INSERT IGNORE INTO `squid_whitelists` (`zMD5`,`pattern`) VALUES ".@implode(",", $n));
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	$sock=new sockets();
	$EnableArticaMetaServer=intval($sock->GET_INFO("EnableArticaMetaServer"));
	if($EnableArticaMetaServer==0){return;}
	$sock->getFrameWork("artica.php?meta-proxy-config=yes");
	
	
}

