<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.dansguardian.inc');
	
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	$user=new usersMenus();
	if(!$user->AsDansGuardianAdministrator){
		$tpl=new templates();
		echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}").");";
		exit;
		
	}
	if(isset($_GET["enable-js"])){skin_enable_js();exit;}
	if(isset($_GET["delete-rule-js"])){skin_delete_js();exit;}
	if(isset($_GET["picture"])){send_picture();exit;}
	if(isset($_GET["skin-js"])){skin_js();exit;}
	if(isset($_GET["list"])){list_items();exit();}
	if(isset($_GET["skin-tabs"])){skin_tabs();exit;}
	if(isset($_GET["TEMPLATE_SETTINGS"])){skin_parameters();exit;}
	if(isset($_GET["TEMPLATE_CONTENT"])){skin_design();exit;}
	if(isset($_GET["TEMPLATE_LOGO"])){skin_logo();exit;}
	
	if(isset($_POST["zmd5-params"])){skin_parameters_save();exit;}
	if(isset($_POST["UFDBGUARD_TITLE_1"])){skin_design_save();exit;}
	if(isset($_POST["SquidHTTPTemplateLogoEnable"])){skin_logo_save();exit;}
	if(isset($_POST["delete-rule"])){skin_delete();exit;}
	if(isset($_POST["enable-rule"])){skin_enable();exit;}
page();

function skin_js(){
	
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$zmd5=$_GET["zmd5"];
	$t=time();
	$TEMPLATE_TITLE=$tpl->javascript_parse_text("{new_skin}");
	$default_text=$tpl->javascript_parse_text("{default}");
	if($zmd5<>null){
		$q=new mysql_squid_builder();
		$sql="SELECT ruleid,category FROM ufdb_design WHERE zmd5='{$_GET["zmd5"]}'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM webfilter_rules WHERE ID='{$ligne["ruleid"]}'"));
		$groupname=$ligne2["groupname"];
		if($ligne["ruleid"]==0){$groupname=$default_text;}
		$TEMPLATE_TITLE="$groupname/{$ligne["category"]}";
	}
	
	echo "YahooWin3(990,'$page?skin-tabs=yes&zmd5=$zmd5','$TEMPLATE_TITLE')";
	
	
}

function skin_delete(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM ufdb_design WHERE zmd5='{$_POST["delete-rule"]}'");
	if(!$q->ok){echo $q->mysql_error;}
	
}

function skin_enable(){
	$q=new mysql_squid_builder();
	$sql="SELECT enabled FROM ufdb_design WHERE zmd5='{$_POST["enable-rule"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if($ligne["enabled"]==1){
		$q->QUERY_SQL("UPDATE ufdb_design SET `enabled`=0 WHERE zmd5='{$_POST["enable-rule"]}'");
		if(!$q->ok){echo $q->mysql_error;}
		return;
	}
	$q->QUERY_SQL("UPDATE ufdb_design SET `enabled`=1 WHERE zmd5='{$_POST["enable-rule"]}'");
	if(!$q->ok){echo $q->mysql_error;}
}

function skin_enable_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$zmd5=$_GET["enable-js"];

	$t=time();
	echo "
var xSave$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#UFDB_SKIN_RULES').flexReload();
}
	
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('enable-rule','{$_GET["zmd5"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
Save$t();";
	
	}

function skin_delete_js(){

	
header("content-type: application/x-javascript");
$page=CurrentPageName();
$tpl=new templates();
$sock=new sockets();
$zmd5=$_GET["delete-rule-js"];
$delete=$tpl->javascript_parse_text("{delete}");
$t=time();
echo "
var xSave$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#UFDB_SKIN_RULES').flexReload();
}
	
	
function Save$t(){
	if(!confirm('$delete ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-rule','$zmd5');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
Save$t();";
	
}

function skin_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	
	$array["TEMPLATE_SETTINGS"]='{settings}';
	if($_GET["zmd5"]<>null){
		$array["TEMPLATE_CONTENT"]='{skin}';
		$array["TEMPLATE_LOGO"]='{logo}';
	}
	$fontsize=22;
	while (list ($num, $ligne) = each ($array) ){
	
		$tab[]="<li><a href=\"$page?$num=yes&zmd5={$_GET["zmd5"]}\">
		<span style='font-size:{$fontsize}px'>$ligne</span></a>
		</li>\n";
			
	}
	$html=build_artica_tabs($tab,"ERROR_PAGE_SKIN_TAB");
	echo $html;
	
	
}


function skin_parameters(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$sql="SELECT ID,groupname FROM webfilter_rules WHERE enabled=1";
	$results = $q->QUERY_SQL($sql);
	$RULES["0"]="{default}";
	$btname="{add}";
	$t=time();
	while ($ligne = mysql_fetch_assoc($results)) {$RULES[$ligne["ID"]]="{$ligne["groupname"]}";}
	
	$dans=new dansguardian_rules();
	$cats=$dans->LoadBlackListes();
	$newcat["*"]="{all}";
	while (list ($num, $ligne) = each ($cats) ){$newcat[$num]=$num;}
	$newcat["safebrowsing"]="Google Safe Browsing";
	$newcat["blacklist"]="{blacklist}";
	$newcat["restricted_time"]="{restricted_access}";
	
	$ligne["enabled"]=1;
	
	if($_GET["zmd5"]<>null){
		$sql="SELECT * FROM ufdb_design WHERE zmd5='{$_GET["zmd5"]}'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){echo $q->mysql_error_html();}
		$btname="{apply}";
		
	}
	
	
	$html="
	<div style='width:98%' class=form>
			<table style='width:100%'>
		". Field_list_table("category-$t", "{category}", $ligne["category"],26,$newcat).
			Field_list_table("ruleid-$t", "{rule}", $ligne["ruleid"],26,$RULES).
		Field_checkbox_table("enabled-$t","{enabled}", $ligne["enabled"],26).
		Field_spacer_table(50).
		Field_button_table_autonome("$btname", "Save$t",34).
		"</table>
	</div>
<script>
	var xSave$t= function (obj) {
		var zmd5='{$_GET["zmd5"]}';
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#UFDB_SKIN_RULES').flexReload();
		if(zmd5.length==0){YahooWin3Hide(); }
	}	

	
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('zmd5-params','{$_GET["zmd5"]}');
		XHR.appendData('category',document.getElementById('category-$t').value);
		XHR.appendData('ruleid',document.getElementById('ruleid-$t').value);
		if(document.getElementById('enabled-$t').checked){XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
		XHR.sendAndLoad('$page', 'POST',xSave$t);	
	}

	function check$t(){
		var zmd5='{$_GET["zmd5"]}';
		if(zmd5.length>5){
			document.getElementById('category-$t').disabled=true;
			document.getElementById('ruleid-$t').disabled=true;
		
		}
	
	}
check$t();				
</script>				
";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function skin_logo(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	$error=null;
	$t=time();
	$q=new mysql_squid_builder();
	$error=null;
	
	$button="<hr>".button("{apply}", "Save$t()",34);

	
	$sql="SELECT * FROM ufdb_design WHERE zmd5='{$_GET["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo $q->mysql_error_html();}
	
	
	$SquidHTTPTemplateLogoPath=$ligne["SquidHTTPTemplateLogoPath"];
	$SquidHTTPTemplateLogoEnable=intval($ligne["SquidHTTPTemplateLogoEnable"]);
	$SquidHTTPTemplateLogoPositionH=$ligne["SquidHTTPTemplateLogoPositionH"];
	$SquidHTTPTemplateLogoPositionL=$ligne["SquidHTTPTemplateLogoPositionL"];
	$SquidHTTPTemplateSmiley=$ligne["SquidHTTPTemplateSmiley"];
	$picturealign=$ligne["picturealign"];
	
	$picturemode=$ligne["picturemode"];
	if($picturemode==null){$picturemode="absolute"; }

	if($SquidHTTPTemplateLogoPositionH==null){$SquidHTTPTemplateLogoPositionH="10";}
	if($SquidHTTPTemplateLogoPositionL==null){$SquidHTTPTemplateLogoPositionL="10";}
	if(!is_numeric($SquidHTTPTemplateSmiley)){$SquidHTTPTemplateSmiley=2639;}

	if(!$users->CORP_LICENSE){
		$error="<p class=text-error>{MOD_TEMPLATE_ERROR_LICENSE}</p>";
		$button=null;
	}
	$picturemode_Hash["float"]="{float}";
	$picturemode_Hash["absolute"]="{absolute}";
	$picturemode_Hash["fixed"]="{fixed}";
	
	$picturealign_Hash[null]="{default}";
	$picturealign_Hash["left"]="{left}";
	$picturealign_Hash["right"]="{right}";
	$picturealign_Hash["center"]="{center}";
	
	if($ligne["picturename"]<>null){$logoPic="<center style='margin:20px'>
	<img src='$page?picture={$ligne["zmd5"]}&t=$t&name={$ligne["picturename"]}'></center>";
	}

	$html="<div style='width:98%' class=form>
	$logoPic
	<center style='margin:10px'>". button("{upload_a_picture}","Loadjs('squidguardweb.skins.uploadlogo.php?zmd5={$_GET["zmd5"]}')",32)."</center>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:26px'>{enable_logo_image}:</td>
		<td>". Field_checkbox_design("SquidHTTPTemplateLogoEnable-$t",1,$SquidHTTPTemplateLogoEnable)."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:26px'>Smiley:</td>
		<td>". Field_text("SquidHTTPTemplateSmiley-$t",$SquidHTTPTemplateSmiley,"width:120px;font-size:26px")."</td>
	</tr>

	<tr>
		<td class=legend style='font-size:26px'>{position} TOP:</td>
		<td style='font-size:26px'>". Field_text("SquidHTTPTemplateLogoPositionH-$t",$SquidHTTPTemplateLogoPositionH,"font-size:26px;width:150px")."%</td>
	</tr>
	<tr>
		<td class=legend style='font-size:26px'>{position} LEFT:</td>
		<td style='font-size:26px'>". Field_text("SquidHTTPTemplateLogoPositionL-$t",$SquidHTTPTemplateLogoPositionL,"font-size:26px;width:150px")."%</td>
	</tr>
	<tr>
		<td class=legend style='font-size:26px'>{type}:</td>
		<td>". Field_array_Hash($picturemode_Hash, "picturemode-$t",$picturemode,"style:font-size:26px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:26px'>{align}:</td>
		<td>". Field_array_Hash($picturealign_Hash, "picturealign-$t",$picturealign,"style:font-size:26px")."</td>
	</tr>	
	
		<tr>
		<td colspan=2 align='right'>$button</td>
		</tr>
</table>
<script>
var xSave$t=function(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue)};
	RefreshTab('ERROR_PAGE_SKIN_TAB');
}

function Save$t(){
	var XHR = new XHRConnection();
	var SquidHTTPTemplateLogoEnable=0;
	if(document.getElementById('SquidHTTPTemplateLogoEnable-$t').checked){SquidHTTPTemplateLogoEnable=1;}
	XHR.appendData('zmd5','{$_GET["zmd5"]}');
	XHR.appendData('picturemode',document.getElementById('picturemode-$t').value);
	XHR.appendData('picturealign',document.getElementById('picturealign-$t').value);
	
	XHR.appendData('SquidHTTPTemplateLogoEnable',SquidHTTPTemplateLogoEnable);
	XHR.appendData('SquidHTTPTemplateSmiley',document.getElementById('SquidHTTPTemplateSmiley-$t').value);
	XHR.appendData('SquidHTTPTemplateLogoPositionH',document.getElementById('SquidHTTPTemplateLogoPositionH-$t').value);
	XHR.appendData('SquidHTTPTemplateLogoPositionL',document.getElementById('SquidHTTPTemplateLogoPositionL-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
</div>";

echo $tpl->_ENGINE_parse_body($html);



}

function skin_logo_save(){
	$sql="UPDATE ufdb_design SET `SquidHTTPTemplateLogoEnable`='{$_POST["SquidHTTPTemplateLogoEnable"]}',
	`SquidHTTPTemplateSmiley`='{$_POST["SquidHTTPTemplateSmiley"]}',
	`SquidHTTPTemplateLogoPositionH`='{$_POST["SquidHTTPTemplateLogoPositionH"]}',
	`SquidHTTPTemplateLogoPositionL`='{$_POST["SquidHTTPTemplateLogoPositionL"]}',
	`picturemode`='{$_POST["picturemode"]}',
	`picturealign`='{$_POST["picturealign"]}'
	WHERE `zmd5`='{$_POST["zmd5"]}'";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
}

function skin_parameters_save(){
	$q=new mysql_squid_builder();
	$zmd5=$_POST["zmd5-params"];
	if($zmd5==null){
		$zmd5=md5($_POST["category"].$_POST["ruleid"]);
		$sql="INSERT IGNORE INTO ufdb_design (zmd5,category,ruleid,enabled) VALUES ('$zmd5','{$_POST["category"]}','{$_POST["ruleid"]}',1)";
		
	}else{
		$sql="UPDATE ufdb_design SET `enabled`='{$_POST["enabled"]}' WHERE `zmd5`='{$_POST["zmd5"]}'";
	}
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
}

function skin_design(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	$t=time();
	$error=null;

	if($_GET["zmd5"]==null){echo FATAL_ERROR_SHOW_128("MD5 key is null!");return;}
	
	
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM ufdb_design WHERE `zmd5`='{$_GET["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo $q->mysql_error_html();}
		
	
	$UfdbGuardHTTPNoVersion=$ligne["UfdbGuardHTTPNoVersion"];
	$UfdbGuardHTTPBackgroundColor=$ligne["UfdbGuardHTTPBackgroundColor"];
	$UfdbGuardHTTPFamily=$ligne["UfdbGuardHTTPFamily"];
	$UfdbGuardHTTPFontColor=$ligne["UfdbGuardHTTPFontColor"];
	$UFDBGUARD_TITLE_1=$ligne["UFDBGUARD_TITLE_1"];
	$UFDBGUARD_PARA1=$ligne["UFDBGUARD_PARA1"];
	$UFDBGUARD_TITLE_2=$ligne["UFDBGUARD_TITLE_2"];
	$UFDBGUARD_PARA2=$ligne["UFDBGUARD_PARA2"];
	
	$UfdbGuardHTTPEnablePostmaster=$ligne["UfdbGuardHTTPEnablePostmaster"];
	$UfdbGuardHTTPBackgroundColorBLK=$ligne["UfdbGuardHTTPBackgroundColorBLK"];
	$UfdbGuardHTTPBackgroundColorBLKBT=$ligne["UfdbGuardHTTPBackgroundColorBLKBT"];
	$UfdbGuardHTTPDisableHostname=intval($ligne["UfdbGuardHTTPDisableHostname"]);
	
	if($UFDBGUARD_TITLE_1==null){$UFDBGUARD_TITLE_1="{UFDBGUARD_TITLE_1}";}
	if($UFDBGUARD_PARA1==null){$UFDBGUARD_PARA1="{UFDBGUARD_PARA1}";}
	if($UFDBGUARD_PARA2==null){$UFDBGUARD_PARA2="{UFDBGUARD_PARA2}";}
	if($UFDBGUARD_TITLE_2==null){$UFDBGUARD_TITLE_2="{UFDBGUARD_TITLE_2}";}
	

	if(!is_numeric($UfdbGuardHTTPEnablePostmaster)){$UfdbGuardHTTPEnablePostmaster=1;}
	if($UfdbGuardHTTPBackgroundColor==null){$UfdbGuardHTTPBackgroundColor="#8c1919";}
	if($UfdbGuardHTTPBackgroundColorBLK==null){$UfdbGuardHTTPBackgroundColorBLK="#0300AC";}
	if($UfdbGuardHTTPBackgroundColorBLKBT==null){$UfdbGuardHTTPBackgroundColorBLKBT="#625FFD";}
	if($UfdbGuardHTTPFontColor==null){$UfdbGuardHTTPFontColor="#FFFFFF";}
	
	
	
	if($UfdbGuardHTTPFamily==null){$UfdbGuardHTTPFamily="Calibri, Candara, Segoe, \"Segoe UI\", Optima, Arial, sans-serif";}

	
	$button="<hr>".button("{apply}", "Save$t()",32);

	if(!$users->CORP_LICENSE){
		$error="<p class=text-error>{MOD_TEMPLATE_ERROR_LICENSE}</p>";
		$button=null;
	}

$html="$error<div style='width:98%' class=form>
<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px' width=1% nowrap>{remove_proxy_hostname}:</td>
		<td width=99%>". Field_checkbox_design("UfdbGuardHTTPDisableHostname-$t",1,$UfdbGuardHTTPDisableHostname)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' width=1% nowrap>{remove_artica_version}:</td>
		<td width=99%>". Field_checkbox_design("UfdbGuardHTTPNoVersion-$t",1,$UfdbGuardHTTPNoVersion)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{add_webmaster}:</td>
		<td>". Field_checkbox_design("UfdbGuardHTTPEnablePostmaster-$t",1,$UfdbGuardHTTPEnablePostmaster)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{background_color}:</td>
		<td>".Field_ColorPicker("UfdbGuardHTTPBackgroundColor-$t",$UfdbGuardHTTPBackgroundColor,"font-size:22px;width:150px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{background_color} Unlock Page:</td>
		<td>".Field_ColorPicker("UfdbGuardHTTPBackgroundColorBLK-$t",$UfdbGuardHTTPBackgroundColorBLK,"font-size:22px;width:150px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{background_color} Button - Unlock Page:</td>
		<td>".Field_ColorPicker("UfdbGuardHTTPBackgroundColorBLKBT-$t",$UfdbGuardHTTPBackgroundColorBLKBT,"font-size:22px;width:150px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{font_family}:</td>
		<td><textarea
				style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
				Courier,monospace;background-color:white;color:black' id='UfdbGuardHTTPFamily-$t'>$UfdbGuardHTTPFamily</textarea>
		</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{font_color}:</td>
		<td>".Field_ColorPicker("UfdbGuardHTTPFontColor-$t",$UfdbGuardHTTPFontColor,"font-size:22px;width:150px")."</td>
	</tr>

	<tr>
		<td class=legend style='font-size:22px'>{titletext} 1:</td>
		<td><textarea
				style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
				Courier,monospace;background-color:white;color:black' id='UFDBGUARD_TITLE_1-$t'>$UFDBGUARD_TITLE_1</textarea>
		</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{parapgraph} 1:</td>
		<td><textarea
				style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
				Courier,monospace;background-color:white;color:black' id='UFDBGUARD_PARA1-$t'>$UFDBGUARD_PARA1</textarea>
		</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{titletext} 2:</td>
		<td><textarea
				style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
				Courier,monospace;background-color:white;color:black' id='UFDBGUARD_TITLE_2-$t'>$UFDBGUARD_TITLE_2</textarea>
		</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{parapgraph} 2:</td>
		<td><textarea
			style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='UFDBGUARD_PARA2-$t'>$UFDBGUARD_PARA2</textarea>
		</td>
	</tr>
	<tr>
		<td colspan=2 align='right'>$button</td>
	</tr>
	<script>
		var xSave$t=function(obj){
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue);return;};
			RefreshTab('ERROR_PAGE_SKIN_TAB');
		}

		function Save$t(){
			var XHR = new XHRConnection();
			XHR.appendData('zmd5','{$_GET["zmd5"]}');
			XHR.appendData('UFDBGUARD_TITLE_1',encodeURIComponent(document.getElementById('UFDBGUARD_TITLE_1-$t').value));
			XHR.appendData('UFDBGUARD_TITLE_2',encodeURIComponent(document.getElementById('UFDBGUARD_TITLE_2-$t').value));
			XHR.appendData('UFDBGUARD_PARA1',encodeURIComponent(document.getElementById('UFDBGUARD_PARA1-$t').value));
			XHR.appendData('UFDBGUARD_PARA2',encodeURIComponent(document.getElementById('UFDBGUARD_PARA2-$t').value));
			
			XHR.appendData('UfdbGuardHTTPFamily',document.getElementById('UfdbGuardHTTPFamily-$t').value);
			XHR.appendData('UfdbGuardHTTPBackgroundColor',document.getElementById('UfdbGuardHTTPBackgroundColor-$t').value);
			XHR.appendData('UfdbGuardHTTPBackgroundColorBLK',document.getElementById('UfdbGuardHTTPBackgroundColorBLK-$t').value);
			XHR.appendData('UfdbGuardHTTPBackgroundColorBLKBT',document.getElementById('UfdbGuardHTTPBackgroundColorBLKBT-$t').value);
			XHR.appendData('UfdbGuardHTTPDisableHostname',document.getElementById('UfdbGuardHTTPDisableHostname-$t').value);
			XHR.appendData('UfdbGuardHTTPFontColor',document.getElementById('UfdbGuardHTTPFontColor-$t').value);
			if(document.getElementById('UfdbGuardHTTPNoVersion-$t').checked){XHR.appendData('UfdbGuardHTTPNoVersion',1);}else{XHR.appendData('UfdbGuardHTTPNoVersion',0);}
			if(document.getElementById('UfdbGuardHTTPEnablePostmaster-$t').checked){XHR.appendData('UfdbGuardHTTPEnablePostmaster',1);}else{XHR.appendData('UfdbGuardHTTPEnablePostmaster',0);}
     		XHR.sendAndLoad('$page', 'POST',xSave$t);

	}
</script>
";

echo $tpl->_ENGINE_parse_body($html);

}

function skin_design_save(){
	
	
	while (list ($num, $ligne) = each ($_POST) ){
		$_POST[$num]=mysql_escape_string2(url_decode_special_tool($ligne));
		
	}
	
	$sql="UPDATE ufdb_design SET
			UFDBGUARD_TITLE_1='{$_POST["UFDBGUARD_TITLE_1"]}',
			UFDBGUARD_TITLE_2='{$_POST["UFDBGUARD_TITLE_2"]}',
			UFDBGUARD_PARA1='{$_POST["UFDBGUARD_PARA1"]}',
			UFDBGUARD_PARA2='{$_POST["UFDBGUARD_PARA2"]}',
			UfdbGuardHTTPFamily='{$_POST["UfdbGuardHTTPFamily"]}',
			UfdbGuardHTTPBackgroundColor='{$_POST["UfdbGuardHTTPBackgroundColor"]}',
			UfdbGuardHTTPBackgroundColorBLK='{$_POST["UfdbGuardHTTPBackgroundColorBLK"]}',
			UfdbGuardHTTPBackgroundColorBLKBT='{$_POST["UfdbGuardHTTPBackgroundColorBLKBT"]}',
			UfdbGuardHTTPBackgroundColorBLKBT='{$_POST["UfdbGuardHTTPBackgroundColorBLKBT"]}',
			UfdbGuardHTTPDisableHostname='{$_POST["UfdbGuardHTTPDisableHostname"]}',
			UfdbGuardHTTPFontColor='{$_POST["UfdbGuardHTTPFontColor"]}',
			UfdbGuardHTTPNoVersion='{$_POST["UfdbGuardHTTPNoVersion"]}',
			UfdbGuardHTTPEnablePostmaster='{$_POST["UfdbGuardHTTPEnablePostmaster"]}'
			WHERE `zmd5`='{$_POST["zmd5"]}'";
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
	
	
}


function page(){


	$sock=new sockets();
	
	$EnableSquidGuardHTTPService=$sock->GET_INFO("EnableSquidGuardHTTPService");
	if(!is_numeric($EnableSquidGuardHTTPService)){$EnableSquidGuardHTTPService=1;}
	if($EnableSquidGuardHTTPService==0){
		echo FATAL_ERROR_SHOW_128("{web_page_service_is_disabled}");
		die();
	
	}
	
	$q=new mysql_squid_builder();
	
	
	
	if(!$q->TABLE_EXISTS("ufdb_design")){
		$sql="CREATE TABLE IF NOT EXISTS `ufdb_design` (
			`zmd5` varchar(90) NOT NULL,
			`category` varchar(90) NOT NULL,
			`ruleid` INT(1) NOT NULL,
			`enabled` smallint(1) NOT NULL,
			`UfdbGuardHTTPNoVersion` smallint(1) NOT NULL,
			`UfdbGuardHTTPEnablePostmaster` smallint(1) NOT NULL,
			`UfdbGuardHTTPBackgroundColor` varchar(20),
			`UfdbGuardHTTPBackgroundColorBLK` varchar(20),
			`UfdbGuardHTTPBackgroundColorBLKBT` varchar(20),
			`UfdbGuardHTTPDisableHostname` smallint(1),
			`UfdbGuardHTTPFontColor`  varchar(20),
			`UfdbGuardHTTPFamily` varchar(128),
			`SquidHTTPTemplateSmiley` INT(10),
			`SquidHTTPTemplateLogoEnable` smallint(1),
			`SquidHTTPTemplateLogoPositionH` INT(10),
			`SquidHTTPTemplateLogoPositionL` INT(10),
			`UFDBGUARD_TITLE_1` TEXT,
			`UFDBGUARD_TITLE_2` TEXT,
			`UFDBGUARD_PARA1` TEXT,
			`UFDBGUARD_PARA2` TEXT,
			`picturename` varchar(128),
			`picture` MEDIUMBLOB,
			`picturemode` varchar(15),
			`picturealign` varchar(15),
			PRIMARY KEY (`zmd5`),
			KEY `category` (`category`),
			KEY `ruleid` (`ruleid`),
			KEY `enabled` (`enabled`)
			) ENGINE=MYISAM;";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo FATAL_ERROR_SHOW_128($q->mysql_error_html());}
		return;
	}
	
	$page=CurrentPageName();
	$tpl=new templates();
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$context=$tpl->_ENGINE_parse_body("{context}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$destination=$tpl->_ENGINE_parse_body("{destination}");
	$website=$tpl->_ENGINE_parse_body("{website}");
	$settings=$tpl->javascript_parse_text("{watchdog_squid_settings}");
	$rulename=$tpl->javascript_parse_text("{rulename}");
	$skins=$tpl->javascript_parse_text("{skins}");
	$allow=$tpl->javascript_parse_text("{allow}");
	$category=$tpl->javascript_parse_text("{category}");
	$new_skin=$tpl->javascript_parse_text("{new_skin}");
	$banned_page_webservice=$tpl->javascript_parse_text("{banned_page_webservice}:");
	$enabled=$tpl->javascript_parse_text("{enabled}");
	$t=time();
	
	$buttons="
	buttons : [
		{name: '$new_skin', bclass: 'apply', onpress :  NewSkin$t},
	],";
	
	
	$html="
<table class='UFDB_SKIN_RULES' style='display: none' id='UFDB_SKIN_RULES' style='width:99%'></table>
<script>
$('#UFDB_SKIN_RULES').flexigrid({
	url: '$page?list=yes',
	dataType: 'json',
	colModel : [
	{display: '$rulename', name : 'ruleid', width :505, sortable : true, align: 'left'},
	{display: '$category', name : 'category', width :250, sortable : true, align: 'center'},
	{display: '$enabled', name : 'enabled', width :95, sortable : true, align: 'left'},
	{display: '&nbsp;', name : 'delete', width :70, sortable : false, align: 'center'},
	],
	$buttons
	
	searchitems : [
	
	{display: '$category', name : 'category'},
	],
	sortname: 'category',
	sortorder: 'asc',
	usepager: true,
	title: '<strong style=font-size:18px>$banned_page_webservice >> $skins</strong>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});
	
	
function NewSkin$t(){
	Loadjs('$page?skin-js=')
}
function purge_caches$t(){
	Loadjs('system.services.cmd.php?APPNAME=APP_NGINX&action=purge&cmd=%2Fetc%2Finit.d%2Fnginx&appcode=APP_NGINX');
}
function import_export$t(){
	Loadjs('miniadmin.proxy.reverse.import.php');
}
	
function New$t(){
	Loadjs('nginx.new.php?peer-id={$_GET["ID"]}');
}

</script>";
	echo $html;
}
function list_items(){
	$MyPage=CurrentPageName();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$table="ufdb_design";
	$searchstring=string_to_flexquery();
	$page=1;


	$table="(SELECT ufdb_design.*,webfilter_rules.groupname FROM ufdb_design,webfilter_rules WHERE 
			webfilter_rules.ID=ufdb_design.ruleid) as t";
	$table="ufdb_design";
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){ $ORDER="ORDER BY `{$_POST["sortname"]}` {$_POST["sortorder"]}"; }}
	if (isset($_POST['page'])) {$page = $_POST['page'];}


	if($searchstring<>null){
		$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: <br>$q->mysql_error.<br>$sql",1);}
		$total = $ligne["tcount"];

	}else{
		$total = $q->COUNT_ROWS($table);
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}


	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";



	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql ";
	$results = $q->QUERY_SQL($sql);

	if(!$q->ok){if($q->mysql_error<>null){json_error_show(date("H:i:s")."<br>SORT:{$_POST["sortname"]}:<br>Mysql Error [L.".__LINE__."]: $q->mysql_error<br>$sql",1);}}


	


	$data = array();
	$data['page'] = $page;
	$data['total'] = $total+1;
	$data['rows'] = array();

	$fontsize="22";
	$style=" style='font-size:{$fontsize}px'";
	$styleHref=" style='font-size:{$fontsize}px;text-decoration:underline'";
	$free_text=$tpl->javascript_parse_text("{free}");
	$computers=$tpl->javascript_parse_text("{computers}");
	$overloaded_text=$tpl->javascript_parse_text("{overloaded}");
	$orders_text=$tpl->javascript_parse_text("{orders}");
	$directories_monitor=$tpl->javascript_parse_text("{directories_monitor}");
	
	$cell=array();
	
	$urijs="Loadjs('$MyPage?skin-js=yes&zmd5=$zmd5')";
	$link="<a href=\"javascript:blur();\" OnClick=\"javascript:$urijs\" $styleHref>";
	
	$default_text=$tpl->javascript_parse_text("{default}");

	if(mysql_num_rows($results)==0){json_error_show("no rule");}

	while ($ligne = mysql_fetch_assoc($results)) {
		$LOGSWHY=array();
		$overloaded=null;
		$loadcolor="black";
		$StatHourColor="black";
		
		$zmd5=$ligne["zmd5"];
		$ColorTime="black";
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM webfilter_rules WHERE ID='{$ligne["ruleid"]}'"));
		$groupname=$ligne2["groupname"];
		if($ligne["ruleid"]==0){$groupname=$default_text;}
		$category=$ligne["category"];
		$enabled=Field_checkbox_design($zmd5, 1,$ligne["enabled"],"Loadjs('$MyPage?enable-js=yes&zmd5=$zmd5')");

		$icon_warning_32="warning32.png";
		$icon_red_32="32-red.png";
		$icon="ok-32.png";


		$urijs="Loadjs('$MyPage?skin-js=yes&zmd5=$zmd5')";
		$link="<a href=\"javascript:blur();\" OnClick=\"javascript:$urijs\" $styleHref>";
		$delete=imgtootltip("delete-32.png",null,"Loadjs('$MyPage?delete-rule-js=$zmd5')");

		$cell=array();
	
		$cell[]="<span $style>$link$groupname</a></span>";
		$cell[]="<span $style>$link$category</a></span>";
		$cell[]="<span $style>$enabled</a></span>";
		$cell[]="<span $style>$delete</a></span>";

		$data['rows'][] = array(
				'id' => $zmd5,
				'cell' => $cell
		);
	}


	echo json_encode($data);
}
function send_picture(){
	
	if($GLOBALS["VERBOSE"]){echo "{$_GET["picture"]}<br>\n";}
	
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM ufdb_design WHERE zmd5='{$_GET["picture"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){
		if($GLOBALS["VERBOSE"]){echo $q->mysql_error;}
	}
	
	$path_parts = pathinfo($ligne["picturename"]);
	$ext=$path_parts['extension'];
	
	if($GLOBALS["VERBOSE"]){echo "{$ligne["picturename"]} - > $ext<br>\n";}
	
	switch ($ext) {
	case "gif":$ctype="image/gif";break;
	case "png": $ctype="image/png"; break;
	case "jpeg": $ctype="image/jpg";break;
	case "jpg": $ctype="image/jpg";;break;
	}
	
	
	$fsize=strlen($ligne["picture"]);
	if($GLOBALS["VERBOSE"]){echo "{$ligne["picturename"]} - > {$fsize}Bytes<br>\n";}
	
	if($GLOBALS["VERBOSE"]){return;}
	header("Content-Type: $ctype");
	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	echo $ligne["picture"];
	return true;
}