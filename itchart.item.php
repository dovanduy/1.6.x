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
	include_once("ressources/class.compile.ufdbguard.expressions.inc");
	
	$user=new usersMenus();
	if($user->AsDansGuardianAdministrator==false){
		$tpl=new templates();
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		die();
	}
	
	if(isset($_GET["charter-settings"])){charter_settings();exit;}
	if(isset($_GET["charter-content"])){charter_content();exit;}
	if(isset($_GET["charter-tabs"])){charter_tabs();exit;}
	if(isset($_GET["charter-headers"])){charter_headers();exit;}
	if(isset($_GET["charter-pdf"])){charter_pdf();exit;}
	if(isset($_POST["ID"])){charter_save();exit;}
	
charter_js();	
function charter_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	if(!is_numeric($ID)){$ID=0;}
	$title="{new_itchart}";
	$title=$tpl->javascript_parse_text($title);
	
	
	if($ID>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `title` FROM itcharters WHERE ID='$ID'"));
		$title=$ligne["title"];
	}
	
	echo "YahooWin2(990,'$page?charter-tabs=yes&ID=$ID','$title')";
	
}	
function charter_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	$array["charter-settings"]="{parameters}";
	if($ID>0){
		$array["charter-content"]="{content}";
		$array["charter-headers"]="{headers}";
		$array["charter-pdf"]="PDF";
	}
	
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:22px'><a href=\"$page?$num=yes&ID=$ID\"><span>
		$ligne</span></a></li>\n");
	}
	
	echo build_artica_tabs($html, "itchart_tabs");
}

function charter_settings(){
	$ID=intval($_GET["ID"]);
	$users=new usersMenus();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$title="Acceptable Use Policy";
	$btname="{add}";
	$t=time();
	
	
	if($ID>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT TextIntro,TextButton,`title` FROM itcharters WHERE ID='$ID'"));
		
		if(!$q->ok){echo "<p class=text-error>$q->mysql_error</p>";}
		$title_page=$ligne["title"];
		$btname="{apply}";
		
	}

	if($ligne["TextIntro"]==null){
		$ligne["TextIntro"]="Please read the IT chart before accessing trough Internet";
	}
	if($ligne["TextButton"]==null){
		$ligne["TextButton"]="I accept the terms and conditions of this agreement";

	}
	$title_page=utf8_encode($title_page);
	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}

	$html="
	<div style='font-size:26px;margin-bottom:20px'>$ID) $title</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td valign='top' class=legend style='font-size:22px'>{enabled}</td>
		<td>". Field_checkbox_design("enabled-$t",1,$ligne["enabled"],"Check$t()")."</td>
	</tr>	
	<tr>
		<td valign='top' class=legend style='font-size:22px'>{page_title}</td>
		<td>". Field_text("title-$t",$title_page,"font-size:22px;width:99%")."</td>
	</tr>
	<tr>
		<td valign='top' class=legend style='font-size:22px'>{text_button}</td>
		<td>". Field_text("TextButton-$t",$ligne["TextButton"],"font-size:22px;width:99%")."</td>
	</tr>				
	<tr>
		<td valign='top' class=legend style='font-size:22px'>{introduction_text}</td>
		<td><textarea 
		style='width:99%;height:350px;overflow:auto;border:5px solid #CCCCCC;font-size:18px !important;
		font-weight:bold;padding:3px;font-family:Courier New;'
		id='TextIntro-$t'>{$ligne["TextIntro"]}</textarea>
		</td>
	</tr>	
	<tr>
	<td colspan=2 align='right'>". button($btname,"Save$t()",32)."</td>
	</tr>
	</table>
</div>		
<script>
var xSave$t= function (obj) {
	var ID=$ID;
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#IT_CHART_TABLE').flexReload();
	RefreshTab('itchart_tabs');
	if(ID==0){YahooWin2Hide();}
}
	
function Save$t(){
	var XHR = new XHRConnection();
	var enabled=1;
	if( document.getElementById('enabled-$t').checked){enabled=1;}
	XHR.appendData('TextIntro', encodeURIComponent(document.getElementById('TextIntro-$t').value));
	XHR.appendData('TextButton',encodeURIComponent(document.getElementById('TextButton-$t').value));
	XHR.appendData('title',encodeURIComponent(document.getElementById('title-$t').value));
	XHR.appendData('enabled',enabled);
	XHR.appendData('ID','$ID');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}	

function Check$t(){
	document.getElementById('TextIntro-$t').disabled=true;
	document.getElementById('TextButton-$t').disabled=true;
	document.getElementById('title-$t').disabled=true;
	if( !document.getElementById('enabled-$t').checked){return;}
	document.getElementById('TextIntro-$t').disabled=false;
	document.getElementById('TextButton-$t').disabled=false;
	document.getElementById('title-$t').disabled=false;	
}
Check$t();
</script>			
";
echo $tpl->_ENGINE_parse_body($html);
}

function charter_save(){
	$q=new mysql_squid_builder();
	$q->CheckTables();
	$ID=$_POST["ID"];
	unset($_POST["ID"]);
	
	if(!$q->FIELD_EXISTS("itcharters", "PdfContent")){
		$q->QUERY_SQL("ALTER TABLE `itcharters` ADD `PdfContent` LONGBLOB NULL");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	
	if(!$q->FIELD_EXISTS("itcharters", "enablepdf")){
		$q->QUERY_SQL("ALTER TABLE `itcharters` ADD `enablepdf` smallint(1) NOT NULL DEFAULT '0',ADD INDEX ( `enablepdf` )");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	
	if(isset($_POST["title"])){
		$_POST["title"]=url_decode_special_tool($_POST["title"]);
		$_POST["title"]=stripslashes($_POST["title"]);
	}
	

	if(isset($_POST["ChartContent"])){
		$_POST["ChartContent"]=url_decode_special_tool($_POST["ChartContent"]);
		$_POST["ChartContent"]=stripslashes($_POST["ChartContent"]);
	}

	if(isset($_POST["ChartHeaders"])){
		$_POST["ChartHeaders"]=url_decode_special_tool($_POST["ChartHeaders"]);
		$_POST["ChartHeaders"]=stripslashes($_POST["ChartHeaders"]);
	}
	if(isset($_POST["TextIntro"])){
		$_POST["TextIntro"]=url_decode_special_tool($_POST["TextIntro"]);
		$_POST["TextIntro"]=stripslashes($_POST["TextIntro"]);
	}
	if(isset($_POST["TextButton"])){
		$_POST["TextButton"]=url_decode_special_tool($_POST["TextButton"]);
		$_POST["TextButton"]=stripslashes($_POST["TextButton"]);
	}

	while (list ($key, $value) = each ($_POST) ){
		$fields[]="`$key`";
		$values[]="'".mysql_escape_string2($value)."'";
		$edit[]="`$key`='".mysql_escape_string2($value)."'";

	}

	if($ID>0){
		$sql="UPDATE itcharters SET ".@implode(",", $edit)." WHERE ID='$ID'";
	}else{

		$sql="INSERT IGNORE INTO itcharters (".@implode(",", $fields).") VALUES (".@implode(",", $values).")";
	}
	
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	

}
function charter_content(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$ID=$_GET["ID"];
	$t=time();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ChartContent FROM itcharters WHERE ID='$ID'"));
	$ChartContent=trim($ligne["ChartContent"]);


	if(strlen($ChartContent)<10){
		$ChartContent=@file_get_contents("ressources/databases/DefaultAcceptableUsePolicy.html");
	}


	$button=$tpl->_ENGINE_parse_body(button("{apply}", "Save$t()",32));
	$button2=$tpl->_ENGINE_parse_body(button("{apply}", "Save2$t()",32));


	$tiny=TinyMce("ChartContent-$t",$ChartContent,true);

	$html="
<div id='$t'></div>
<center>
	<div style='text-align:center;width:100%;background-color:white;margin-bottom:10px;padding:5px;'>$button<br></div>
	<div style='width:750px;height:auto'>$tiny</div>
	<div style='text-align:center;width:100%;background-color:white;margin-top:10px'>$button2</div>
</center>
<script>
var xSave$t= function (obj) {
	var res=obj.responseText;
	document.getElementById('$t').innerHTML='';
	if(res.length>3){alert(res);return;}
}
function Save2$t(){ Save$t();}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ID', '$ID');
	XHR.appendData('ChartContent', encodeURIComponent(tinymce.get('ChartContent-$t').getContent()));
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>";

	echo $html;

}
function charter_headers(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$ID=$_GET["ID"];
	$t=time();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ChartHeaders FROM itcharters WHERE ID='$ID'"));
	$ChartHeaders=trim($ligne["ChartHeaders"]);



	if(strlen($ChartHeaders)<10){
		$ChartHeaders=@file_get_contents("ressources/databases/DefaultAcceptableUsePolicyH.html");
	}


	$button=$tpl->_ENGINE_parse_body(button("{apply}", "Save$t()",18));
	$button2=$tpl->_ENGINE_parse_body(button("{apply}", "Save2$t()",18));



	$html="
	<div id='$t'></div>
	<center>
	<div style='text-align:center;width:100%;background-color:white;margin-bottom:10px;padding:5px;'>$button<br></div>
	<textarea
	style='width:95%;height:550px;overflow:auto;border:5px solid #CCCCCC;font-size:14px;font-weight:bold;padding:3px'
	id='content-$t'>$ChartHeaders</textarea>
	<div style='text-align:center;width:100%;background-color:white;margin-top:10px'>$button2</div>
	</center>
	<script>
	var xSave$t= function (obj) {
	var res=obj.responseText;
	document.getElementById('$t').innerHTML='';
	if(res.length>3){alert(res);return;}

}
function Save2$t(){ Save$t();}

function Save$t(){
var XHR = new XHRConnection();
XHR.appendData('ID', '$ID');
AnimateDiv('$t');
XHR.appendData('ChartHeaders', encodeURIComponent(document.getElementById('content-$t').value));
XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>";

	echo $html;


}

function charter_pdf(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$ID=$_GET["ID"];
	$users=new usersMenus();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$title="PDF";
	$btname="{add}";
	
	if(!$q->FIELD_EXISTS("itcharters", "enablepdf")){
		$q->QUERY_SQL("ALTER TABLE `itcharters` ADD `enablepdf` smallint(1) NOT NULL DEFAULT '0',ADD INDEX ( `enablepdf` )");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	
	if(!$q->FIELD_EXISTS("itcharters", "PdfFileName")){
		$q->QUERY_SQL("ALTER TABLE `itcharters` ADD `PdfFileName` VARCHAR(128) NULL");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	
	if(!$q->FIELD_EXISTS("itcharters", "PdfFileSize")){
		$q->QUERY_SQL("ALTER TABLE `itcharters` ADD `PdfFileSize` INT UNSIGNED NULL");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}	
	
	$t=time();
	if($ID>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT enablepdf,PdfFileSize,PdfFileName FROM itcharters WHERE ID='$ID'"));
		if(!$q->ok){echo "<p class=text-error>$q->mysql_error</p>";}
	
		$title=$ligne["title"];
		$btname="{apply}";
	}
	
	
	$title="PDF";
	if(intval($ligne["PdfFileSize"])>0){
		$title="PDF {$ligne["PdfFileName"]} (".FormatBytes(intval($ligne["PdfFileSize"])/1024).")";
	}
	
	$html="
<div style='font-size:26px;margin-bottom:20px'>$title</div>
<div style='font-size:18px' class=explain>{charter_pdf_explain}</div>
<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td valign='top' class=legend style='font-size:22px'>{enabled}</td>
		<td>". Field_checkbox_design("enablepdf-$t",1,$ligne["enablepdf"],"Save$t()")."</td>
	</tr>
				
	<tr>
		<td colspan=2 align='center'>". button("{upload}","Loadjs('itchart.pdf.upload.php?ID=$ID')",22)."</td>
	</tr>
								
	<tr>
		<td colspan=2 align='right'>". button($btname,"Save$t()",32)."</td>
	</tr>
	</table>
</div>
<script>
var xSave$t= function (obj) {
	var ID=$ID;
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#IT_CHART_TABLE').flexReload();
	RefreshTab('itchart_tabs');
	if(ID==0){YahooWin2Hide();}
}
	
function Save$t(){
	var XHR = new XHRConnection();
	var enabled=0;
	if( document.getElementById('enablepdf-$t').checked){enabled=1;}
	XHR.appendData('enablepdf',enabled);
	XHR.appendData('ID','$ID');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>";
	
echo $tpl->_ENGINE_parse_body($html);
	
}


