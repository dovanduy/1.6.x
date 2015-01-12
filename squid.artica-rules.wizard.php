<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.tcpip.inc');
include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
include_once(dirname(__FILE__) . "/ressources/class.pdns.inc");
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.squid.inc');


$user=new usersMenus();
if($user->AsSquidAdministrator==false){
	$tpl=new templates();
	echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
	die();
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["step1"])){step1();exit;}
if(isset($_GET["step2"])){step2();exit;}
if(isset($_POST["rulename"])){Save();exit;}
if(isset($_POST["html"])){SaveMysql();exit;}



js();

function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("Hyper Cache: {new_rule}");
	

	echo "
	function Start$t(){
	RTMMail('800','$page?popup=yes','$title');
}
Start$t();";


}

function popup(){
	$page=CurrentPageName();
	$html="<div id='HYPERCACHE_NEW_RULE_WIZARDID'></div>
	<script>LoadAjax('HYPERCACHE_NEW_RULE_WIZARDID','$page?step1=yes');</script>
	";
	echo $html;
	
	
}


function step1(){
	$tpl=new templates();
	$page=CurrentPageName();
	$artica_cache_rule_explain_sitename=stripslashes($tpl->_ENGINE_parse_body("{artica_cache_rule_explain_sitename}"));
	$t=time();
	$html="<div style='font-size:22px;margin-bottom:20px'>{create_a_new_hypercache_rule}</div>

	<div style='width:98%' class=form>
	<table 	style='width:100%'>
		<tr>
			<td class=legend style='font-size:26px'>{rulename}:</td>
			<td>". Field_text("rulename-$t",null,"font-size:26px;",null,null,null,false,"SaveCheck$t(event)")."</td>
		</tr>	
	<tr>
		<td colspan=2>	<div class=explain style='font-size:18px;margin-bottom:20px'>
	$artica_cache_rule_explain_sitename
	</div></td></tr>
		
		<tr>
			<td class=legend style='font-size:26px'>{pattern}:</td>
			<td>". Field_text("sitename-$t",null,"font-size:26px;",null,null,null,false,"SaveCheck$t(event)")."</td>
		</tr>
		<tr>
			<td colspan=2 align='right'><hr>".button("{next}","Save$t()",32)."</td>
		</tr>
	</table>
	</div>
	<script>
var xSave$t=function (obj) {
	var tempvalue=obj.responseText;
	LoadAjax('HYPERCACHE_NEW_RULE_WIZARDID','$page?step2=yes');
	
}
	
function Save$t(){
	var XHR = new XHRConnection();
	var rule=document.getElementById('rulename-$t').value;
	if(rule.length<1){return;}
	XHR.appendData('rulename',encodeURIComponent(document.getElementById('rulename-$t').value));
	XHR.appendData('sitename',encodeURIComponent(document.getElementById('sitename-$t').value));
	XHR.sendAndLoad('$page', 'POST',xSave$t);						
}	

function SaveCheck$t(e){
	if(!checkEnter(e)){return;}
	Save$t();
}
</script>";	
	echo $tpl->_ENGINE_parse_body($html);
	

}
function step2(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$html="<div style='font-size:26px;margin-bottom:5px'>{$_SESSION["HYPERCACHE_NEW_RULE_WIZARDID"]["rulename"]}</div>
	<div style='font-size:18px;margin-bottom:20px'><i>{$_SESSION["HYPERCACHE_NEW_RULE_WIZARDID"]["sitename"]}</i></div>
	<div class=explain style='font-size:18px;margin-bottom:20px'>{artica_cache_rule_explain_mime}</div>
	</div>
	<div style='width:98%' class=form>
	<table 	style='width:100%'>
		<tr>
			<td class=legend style='font-size:26px'>{webpages_and_cssjs}:</td>
			<td>". Field_checkbox("html-$t",1,1)."</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:26px'>{images}:</td>
			<td>". Field_checkbox("images-$t",1,1)."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:26px'>{filesandcompressedfiles}:</td>
			<td>". Field_checkbox("files-$t",1,1)."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:26px'>{musicandvideos}:</td>
			<td>". Field_checkbox("videos-$t",1,1)."</td>
		</tr>					
		<tr>
		<td colspan=2 align='right'><hr>".button("{create_rule}","Save$t()",32)."</td>
		</tr>
	</table>
</div>
<script>
var xSave$t=function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	$('#squid_enforce_rules_table').flexReload();
}

function Save$t(){
	var XHR = new XHRConnection();
	if(document.getElementById('html-$t').checked){
		XHR.appendData('html',1);
	}else{
		XHR.appendData('html',0);
	}
	if(document.getElementById('images-$t').checked){
		XHR.appendData('images',1);
	}else{
		XHR.appendData('images',0);
	}	
	if(document.getElementById('files-$t').checked){
		XHR.appendData('files',1);
	}else{
		XHR.appendData('files',0);
	}
	if(document.getElementById('videos-$t').checked){
		XHR.appendData('videos',1);
	}else{
		XHR.appendData('videos',0);
	}		
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

function SaveCheck$t(e){
if(!checkEnter(e)){return;}
Save$t();
}
</script>";

echo $tpl->_ENGINE_parse_body($html);

}

function Save(){
	
	while (list ($num, $ligne) = each ($_POST) ){
		$_SESSION["HYPERCACHE_NEW_RULE_WIZARDID"][$num]=url_decode_special_tool($ligne);
	}
	
}

function SaveMysql(){
	

	

	if($_POST["files"]==1){
		$FileTypes["application/octet-stream"]=1;
		$FileTypes["application/x-gsp"]=1;
		$FileTypes["application/x-gss"]=1;
		$FileTypes["application/x-gtar"]=1;
		$FileTypes["application/x-compressed"]=1;
		$FileTypes["application/x-gzip"]=1;
		$FileTypes["multipart/x-gzip"]=1;
		$FileTypes["multipart/x-zip"]=true;
		
		$FileTypes["application/binhex"]=1;
		$FileTypes["application/binhex4"]=1;
		$FileTypes["application/mac-binhex"]=1;
		$FileTypes["application/mac-binhex40"]=1;
		$FileTypes["application/x-binhex40"]==1;
		$FileTypes["application/x-mac-binhex40"]=1;
		$FileTypes["application/msword"]=1;
		
		$FileTypes["application/x-bcpio"]=1;
		$FileTypes["application/mac-binary"]=1;
		$FileTypes["application/x-compress"]=true;
		$FileTypes["application/x-compressed"]=true;
		
		$FileTypes["application/x-zip-compressed"]=1;
		$FileTypes["application/zip"]=1;
		$FileTypes["application/arj"]=1;
		$FileTypes["application/book"]=1;
		
		
		$FileTypes["application/octet-stream"]=1;
		$FileTypes["application/x-binary"]=1;
		$FileTypes["application/macbinary"]=1;
		$FileTypes["application/x-macbinary"]=1;
		$FileTypes["application/mspowerpoint"]=1;
		$FileTypes["application/vnd.ms-powerpoint"]=1;
		$FileTypes["application/powerpoint"]=1;
		$FileTypes["application/excel"]=1;
		$FileTypes["application/vnd.ms-excel"]=1;
		$FileTypes["application/x-excel"]=1;
		$FileTypes["application/x-msexcel"]=1;
		
		
		
		
		$FileTypes["application/java"]=1;
		$FileTypes["application/java-byte-code"]=1;
		$FileTypes["application/x-java-class"]=1;
		$FileTypes["text/x-asm"]=1;
		$FileTypes["text/asp"]=1;
		$FileTypes["text/plain"]=1;
		$FileTypes["text/css"]=1;
		$FileTypes["text/x-c"]=1;
		$FileTypes["text/html"]=1;
		$FileTypes["text/x-fortran"]=1;
		$FileTypes["text/x-java-source"]=1;
		$FileTypes["application/x-java-commerce"]=1;
		$FileTypes["application/x-javascript"]=1;
		$FileTypes["application/javascript"]=1;
		$FileTypes["application/ecmascript"]=1;
		$FileTypes["text/javascript"]=1;
		$FileTypes["text/ecmascript"]=1;
		
	
	}
	
	if($_POST["images"]==1){
		$FileTypes["image/jpeg"]=1;
		$FileTypes["image/gif"]=1;
		$FileTypes["image/x-icon"]=1;
		$FileTypes["image/pjpeg"]=1;
		$FileTypes["image/bmp"]=1;
		$FileTypes["image/png"]=1;
		$FileTypes["image/x-jps"]=1;
		$FileTypes["image/vnd.fpx"]=1;
		$FileTypes["image/vnd.net-fpx"]=1;
		$FileTypes["image/florian"]=1;
		$FileTypes["image/fif"]=1;
		$FileTypes["image/vnd.dwg"]=1;

		$FileTypes["image/x-windows-bmp"]=1;
		$FileTypes["image/x-dwg"]=1;
		$FileTypes["image/vnd.dwg"]=1;
		$FileTypes["image/x-dwg"]=1;
		$FileTypes["image/g3fax"]=1;
		$FileTypes["image/gif"]=1;
		$FileTypes["image/x-icon"]=1;
		$FileTypes["image/ief"]=1;
		$FileTypes["image/jutvision"]=1;
		$FileTypes["image/vasa"]=1;
		$FileTypes["image/naplps"]=1;
		
		
	}
	
	if($_POST["videos"]){
		$FileTypes["application/x-mplayer2"]=1;
		$FileTypes["application/x-troff-msvideo"]=1;
		$FileTypes["application/x-dvi"]=1;
		
		
		$FileTypes["video/fli"]=1;
		$FileTypes["video/x-fli"]=1;
		$FileTypes["video/x-atomic3d-feature"]=1;
		$FileTypes["video/x-ms-asf"]=1;
		$FileTypes["video/x-ms-asf-plugin"]=1;
		$FileTypes["video/avi"]=1;
		$FileTypes["video/msvideo"]=1;
		$FileTypes["video/x-msvideo"]=1;
		$FileTypes["video/x-sgi-movie"]=1;
		$FileTypes["video/avs-video"]=1;
		$FileTypes["video/dl"]=1;
		$FileTypes["video/x-dl"]=1;
		$FileTypes["video/x-dv"]=1;
		$FileTypes["video/gl"]=1;
		$FileTypes["video/x-gl"]=1;
		$FileTypes["video/x-isvideo"]=1;
		$FileTypes["video/mpeg"]=1;
		$FileTypes["video/x-dv"]=1;
		$FileTypes["video/quicktime"]=1;
		$FileTypes["video/x-sgi-movie"]=1;
		$FileTypes["video/mpeg"]=1;
		$FileTypes["video/x-mpeg"]=1;
		$FileTypes["video/x-mpeq2a"]=1;
		$FileTypes["video/x-motion-jpeg"]=1;
		$FileTypes["video/vnd.rn-realvideo"]=1;
		
		
		$FileTypes["application/x-vnd.audioexplosion.mzz"]=1;
		$FileTypes["audio/x-gsm"]=1;
		$FileTypes["audio/make"]=1;
		$FileTypes["audio/it"]=1;
		$FileTypes["audio/x-jam"]=1;
		$FileTypes["audio/basic"]=1;
		$FileTypes["audio/x-au"]=1;
		$FileTypes["audio/aiff"]=1;
		$FileTypes["audio/x-aiff"]=1;
		$FileTypes["audio/make"]=1;
		$FileTypes["audio/mpeg"]=1;
		$FileTypes["audio/x-mpeg"]=1;
		$FileTypes["audio/mpeg3"]=1;
		$FileTypes["audio/x-mpeg-3"]=1;
		$FileTypes["audio/nspaudio"]=1;
		$FileTypes["audio/x-nspaudio"]=1;
		$FileTypes["audio/x-liveaudio"]=1;
		$FileTypes["audio/midi"]=1;
		$FileTypes["audio/nspaudio"]=1;
		$FileTypes["audio/x-nspaudio"]=1;
		$FileTypes["music/x-karaoke"]=1;
		$FileTypes["audio/mod"]=1;
		$FileTypes["audio/x-mod"]=1;
		$FileTypes["application/x-midi"]=1;
		$FileTypes["audio/x-mid"]=1;
		$FileTypes["audio/x-midi"]=1;
		$FileTypes["music/crescendo"]=1;
		$FileTypes["x-music/x-midi"]=1;
		$FileTypes["music/crescendo"]=1;
		
		$FileTypes["audio/x-vnd.audioexplosion.mjuicemediafile"]=1;
		
		
		
	}
	
if($_POST["html"]==1){
	$FileTypes["text/html"]=1;
	$FileTypes["text/javascript"]=1;
	$FileTypes["text/css"]=1;
	$FileTypes["application/x-javascript"]=1;
	$FileTypes["application/javascript"]=1;
	$FileTypes["application/java"]=1;
	$FileTypes["application/java-byte-code"]=1;
	$FileTypes["application/x-java-class"]=1;
	$FileTypes["text/x-asm"]=1;
	$FileTypes["text/asp"]=1;
	$FileTypes["text/plain"]=1;
	$FileTypes["text/css"]=1;
	$FileTypes["text/x-c"]=1;
	$FileTypes["text/html"]=1;
	$FileTypes["text/x-fortran"]=1;
	$FileTypes["text/x-java-source"]=1;
	$FileTypes["application/x-java-commerce"]=1;
	$FileTypes["application/x-javascript"]=1;
	$FileTypes["application/javascript"]=1;
	$FileTypes["application/ecmascript"]=1;
	$FileTypes["text/javascript"]=1;
	$FileTypes["text/ecmascript"]=1;
	
}

	$FileTypes_enc=mysql_escape_string2(serialize($FileTypes));
	$rulename=mysql_escape_string2($_SESSION["HYPERCACHE_NEW_RULE_WIZARDID"]["rulename"]);
	$sitename=mysql_escape_string2($_SESSION["HYPERCACHE_NEW_RULE_WIZARDID"]["sitename"]);
	
	$q=new mysql_squid_builder();
	if(!$q->FIELD_EXISTS("artica_caches","FileTypes","artica_backup")){
		$sql="ALTER TABLE `artica_caches` ADD `FileTypes` TEXT";
		$q->QUERY_SQL($sql,"artica_backup");
	}
	
	if(!$q->FIELD_EXISTS("artica_caches","OtherDomains","artica_backup")){
		$sql="ALTER TABLE `artica_caches` ADD `OtherDomains` TEXT";
		$q->QUERY_SQL($sql,"artica_backup");
	}
	
	
	$sql="INSERT IGNORE INTO `artica_caches` (`sitename`,`rulename`,`enabled`,`FileTypes`) VALUES ('$sitename','$rulename','1','$FileTypes_enc')";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}

}

