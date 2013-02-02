<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');

	
	
	$user=new usersMenus();
	if($user->AsWebStatisticsAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	if(isset($_GET["template-settings-js"])){template_settings_js();exit;}
	if(isset($_POST["newtemplate"])){TEMPLATE_ADD_SAVE();exit;}
	if(isset($_POST["template_body"])){ZOOM_SAVE();exit;}
	if(isset($_POST["TEMPLATE_DATA"])){TEMPLATE_SAVE();}
	if(isset($_POST["template_header"])){TEMPLATE_HEADER_SAVE();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["popup-table"])){popup_table();exit;}
	if(isset($_GET["view-table"])){view_table();exit;}
	if(isset($_GET["TEMP"])){FormTemplate();exit;}
	if(isset($_GET["Zoom-js"])){ZOOM_JS();exit;}
	if(isset($_GET["Zoom-popup"])){ZOOM_POPUP();exit;}
	if(isset($_GET["Select-lang"])){select_lang();exit;}
	if(isset($_GET["headers-js"])){HEADER_JS();exit;}
	if(isset($_GET["Headers-popup"])){HEADERS_POPUP();exit;}
	if(isset($_GET["preview"])){ZOOM_PREVIEW();exit;}
	if(isset($_POST["tpl-remove"])){TEMPLATE_REMOVE();exit;}
	if(isset($_GET["new-template"])){TEMPLATE_ADD();exit;}
	if(isset($_POST["ChooseAclsTplSquid"])){TEMPLATE_AFFECT();exit;}
	
	if(isset($_POST["RebuidSquidTplDefault"])){RebuidSquidTplDefault();exit;}
js();

function ZOOM_JS(){
	$zmd5=$_GET["Zoom-js"];
	$page=CurrentPageName();
	$title=base64_decode($_GET["subject"]);
	$title=str_replace("'","`", $title);
	$html="
	
	YahooWin6(820,'$page?Zoom-popup=yes&zmd5=$zmd5','$title')";
	echo $html;	
	
}
function HEADER_JS(){
	$zmd5=$_GET["zmd5"];
	$page=CurrentPageName();
	$tpl=new templates();
	$title=base64_decode($_GET["subject"]);
	$title=str_replace("'","`", $title);
	$headers_text=$tpl->_ENGINE_parse_body("{headers}");
	$html="YahooWinBrowse(820,'$page?Headers-popup=yes&zmd5=$zmd5','$title:$headers_text')";
	echo $html;	
	
}

function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{squid_templates_error}");
	if(!is_numeric($_GET["choose-acl"])){$_GET["choose-acl"]=0;}
	$yahoo="YahooWin4";
	if($_GET["choose-acl"]>0){
		$yahoo="YahooWinBrowse";
	}
	if(isset($_GET["choose-generic"])){
		$yahoo="YahooWinBrowse";
		
	}
	$html="
	$('#SquidTemplateErrorsTable').remove();
	$yahoo('774','$page?popup=yes&choose-acl={$_GET["choose-acl"]}&choose-generic={$_GET["choose-generic"]}&divid={$_GET["divid"]}&yahoo=$yahoo','$title');";
	echo $html;
	}
	
function ZOOM_PREVIEW(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$t=time();
	$sql="SELECT template_body,template_title,template_header FROM squidtpls WHERE `zmd5`='{$_GET["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$page=CurrentPageName();
	$tpl=new templates();
	$newheader=trim(stripslashes($ligne["template_header"]));
	$ligne["template_body"]=stripslashes($ligne["template_body"]);
	if($newheader==null){$newheader=@file_get_contents("ressources/databases/squid.default.header.db");}
	$newheader=str_replace("{TITLE}", $ligne["template_title"], $newheader);
	$templateDatas="$newheader{$ligne["template_body"]}</body></html>";
	echo $templateDatas;
}
	
function HEADERS_POPUP(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$t=time();
	$sql="SELECT template_header FROM squidtpls WHERE `zmd5`='{$_GET["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){	$errorsql="<div style='font-size:14px;font-weight:bold;color:#B10000;margin:10px'>{$_GET["zmd5"]}::$q->mysql_error</div>";}
	$page=CurrentPageName();
	$ligne["template_header"]=stripslashes($ligne["template_header"]);
	$tpl=new templates();
	if($ligne["template_header"]==null){
		$error="<div style='font-size:14px;font-weight:bold;color:#B10000;margin:10px'>{$_GET["zmd5"]}::{default_value_is_used}</div>";
		$ligne["template_header"]=@file_get_contents("ressources/databases/squid.default.header.db");}
	$html="
	$error$errorsql
	<textarea style='width:100%;height:450px;font-family:monospace;
	overflow:auto;font-size:13px;border:4px solid #CCCCCC;background-color:transparent' id='{$_GET["zmd5"]}-header-$t'>
		{$ligne["template_header"]}
	</textarea>
	<div style='width:100%;text-align:right'><hr>". button("{apply}","SaveTemplateForm$t()",16)."</div>
	</div>
	
	<script>
	var x_SaveTemplateForm$t=function(obj){
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		YahooWinBrowseHide();
    }	    
	
	 function SaveTemplateForm$t(){
      	var XHR = new XHRConnection();
      	XHR.appendData('template_header',document.getElementById('{$_GET["zmd5"]}-header-$t').value);
      	XHR.appendData('zmd5','{$_GET["zmd5"]}');
      	XHR.sendAndLoad('$page', 'POST',x_SaveTemplateForm$t);          
      }	
     </script>	
	
	";
	echo $tpl->_ENGINE_parse_body($html);		
	
}

function template_settings_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	echo "YahooWin5('600','$page?new-template=yes&t=$t&templateid={$_GET["zmd5"]}','{$_GET["zmd5"]}');";
	
}
	
function ZOOM_POPUP(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$sql="SELECT template_body,template_title FROM squidtpls WHERE `zmd5`='{$_GET["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$page=CurrentPageName();
	$tpl=new templates();	
	$headers_text=$tpl->_ENGINE_parse_body("{headers}");
	$parameters=$tpl->_ENGINE_parse_body("{parameters}");
	$ligne["template_body"]=trim($ligne["template_body"]);	
	if($ligne["template_body"]==null){
		$ligne["template_body"]="<table class=\"w100 h100\"><tr><td class=\"c m\"><table style=\"margin:0 auto;border:solid 1px #560000\"><tr><td class=\"l\" style=\"padding:1px\"><div style=\"width:346px;background:#E33630\"><div style=\"padding:3px\"><div style=\"background:#BF0A0A;padding:8px;border:solid 1px #FFF;color:#FFF\"><div style=\"background:#BF0A0A;padding:8px;border:solid 1px #FFF;color:#FFF\"><h1>ERROR: The requested URL could not be retrieved</h1></div><div class=\"c\" style=\"font:bold 13px arial;text-transform:uppercase;color:#FFF;padding:8px 0\">Proxy Error</div><div style=\"background:#F7F7F7;padding:20px 28px 36px\"> <div id=\"titles\"> <h1>ERROR</h1> <h2>The requested URL could not be retrieved</h2> </div> <hr>  <div id=\"content\"> <p>The following error was encountered while trying to retrieve the URL: <a href=\"%U\">%U</a></p>  <blockquote id=\"error\"> <p><b>Access Denied.</b></p> </blockquote>  <p>Access control configuration prevents your request from being allowed at this time. Please contact your service provider if you feel this is incorrect.</p>  <p>Your cache administrator is <a href=\"mailto:%w%W\">%w</a>.</p> <br> </div>  <hr> <div id=\"footer\"> <p>Generated %T by %h (%s)</p> <!-- %c --> </div> </div></div></div></td></tr></table></td></tr></table>";
	}
	$html="
	<table style='width:99%' class=form>
	
	
	
	<tr>
	<td width=99%>&nbsp;</td>
		<td nowrap width=1%><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$page?template-settings-js=yes&zmd5={$_GET["zmd5"]}&t=$t');\"
		style='font-size:14px;font-weight:bold;text-decoration:underline'>$parameters</a>
	</td>
	
	<td nowrap width=1%><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$page?headers-js=yes&zmd5={$_GET["zmd5"]}');\"
		style='font-size:14px;font-weight:bold;text-decoration:underline'>$headers_text</a>
	</td>
	<td nowrap width=1%><a href=\"javascript:blur();\" OnClick=\"s_PopUp('$page?preview=yes&zmd5={$_GET["zmd5"]}',800,800);\"
		style='font-size:14px;font-weight:bold;text-decoration:underline'>{preview}</a>
	</td>	
	</tr>
	</table>
			
	<center>".Field_text("template_title-$t",$ligne["template_title"],"font-size:18px;border:4px solid #CCCCCC;width:95%")."</center>
	<div style='width:95%' class=form id='{$_GET["zmd5"]}'>

	<textarea style='width:100%;height:450px;font-family:monospace;
	overflow:auto;font-size:13px;border:4px solid #CCCCCC;background-color:transparent' id='{$_GET["zmd5"]}-text-$t'>{$ligne["template_body"]}</textarea>
	<div style='width:100%;text-align:right'><hr>". button("{apply}","SaveTemplateForm()",16)."</div>
	</div>
	
	<script>
	var x_SaveTemplateForm=function(obj){
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		YahooWin6Hide();
    }	    
	
	 function SaveTemplateForm(){
      	
      	var XHR = new XHRConnection();
      	XHR.appendData('template_title',document.getElementById('template_title-$t').value);
      	XHR.appendData('template_body',document.getElementById('{$_GET["zmd5"]}-text-$t').value);
      	XHR.appendData('zmd5','{$_GET["zmd5"]}');
      	AnimateDiv('{$_GET["zmd5"]}');
      	XHR.sendAndLoad('$page', 'POST',x_SaveTemplateForm);          
      }	
     </script>	
	
	";
	echo $tpl->_ENGINE_parse_body($html);	
}

function ZOOM_SAVE(){
	$q=new mysql_squid_builder();	
	$tplbdy=$_POST["template_body"];
	$tplbdy=stripslashes($tplbdy);
	$tplbdy=trim(str_replace("\n", "", $tplbdy));
	$tplbdy=trim(str_replace("\r", "", $tplbdy));
	$tplbdy=trim(str_replace("  ", "", $tplbdy));
	$tplbdy=addslashes($tplbdy);
	$tplbdy=utf8_encode(trim($tplbdy));
	
	$tpltitle=$_POST["template_title"];
	$tpltitle=utf8_encode(trim($tpltitle));
	$tpltitle=addslashes($tpltitle);
	
	$sql="UPDATE squidtpls 
	SET template_body='$tplbdy',
	template_title='$tpltitle'
	WHERE zmd5='{$_POST["zmd5"]}'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-templates=yes&zmd5={$_POST["zmd5"]}");		
}

function RebuidSquidTplDefault(){
	$q=new mysql_squid_builder();	
	$q->QUERY_SQL("TRUNCATE TABLE squidtpls");
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-default-tpls=yes");
	$sock->getFrameWork("squid.php?build-templates=yes");		
	
	
}

function TEMPLATE_AFFECT(){
	$ID=$_POST["ChooseAclsTplSquid"];
	$q=new mysql_squid_builder();	
	$sql="UPDATE webfilters_sqgroups SET acltpl='{$_POST["zmd5"]}' WHERE ID='$ID'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		
		if(preg_match("#Unknown column#", $q->mysql_error)){$q->CheckTables();$q->QUERY_SQL($sql);}
	}
	if(!$q->ok){
		echo $q->mysql_error;return;
	
	}
	$sql="SELECT template_name FROM squidtpls WHERE `zmd5`='{$_POST["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	echo $ligne["template_name"];
}




function TEMPLATE_ADD(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$sql="SELECT lang FROM squidtpls GROUP BY lang ORDER BY lang";
	$results=$q->QUERY_SQL($sql);
	$t=time();
	$btname="{add}";
	$lang[]="{select}";
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$txt=$ligne["lang"];
		if(is_numeric($txt)){continue;}
		if($ligne["lang"]==null){continue;}
		$lang[$ligne["lang"]]=$txt;
	}

	
	if(strlen($_GET["templateid"])>5){
		$sql="SELECT * FROM squidtpls WHERE `zmd5`='{$_GET["templateid"]}'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$btname="{apply}";
	}

	$statusCode=$q->LoadStatusCodes();
	if($ligne["lang"]==null){$ligne["lang"]=$tpl->language;}
	
	if(preg_match("#([0-9]+):(.+)#", $ligne["template_uri"],$re)){
		$ligne["template_uri"]=$re[2];
		$status_code=$re[1];
		if(strpos($ligne["template_uri"], "%FREEWEBS%")>1){
			$freewebs_compliance=1;
			$ligne["template_uri"]=str_replace("%FREEWEBS%", "", $ligne["template_uri"]);
		}
	}
	
	if(!is_numeric($status_code)){$status_code=303;}
	if(!is_numeric($freewebs_compliance)){$freewebs_compliance=0;}
	
	
	$q=new mysql();
	$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(*) as tcount FROM freeweb WHERE `groupware`='ERRSQUID'","artica_backup"));
	if($ligne2["tcount"]>0){
		$freewebsButton="<input type='button' OnClick=\"javascript:Loadjs('BrowseFreeWebs.php?groupware=ERRSQUID&field=template_uri-$t&withhttp=1');\"
		value='{browse}...'>";
	}
	
	
	$field=Field_array_Hash($lang, "lang1-$t",$ligne["lang"],null,null,0,"font-size:16px");
	$html="<div id='$t'></div>
		<table style='width:100%;margin-top:20px;margin-bottom:8px' class=form>
		<tbody>
			<tr>
				<td class=legend style='font-size:16px'>{template_name}:</td>
				<td>". Field_text("tplname-$t",$ligne["template_name"],"font-size:16px;width:95%")."</td>
				<td>&nbsp;</td>
			</tr>	
			<tr>
				<td class=legend style='font-size:16px'>{subject}:</td>
				<td>". Field_text("template_title-$t",$ligne["template_title"],"font-size:16px;width:95%")."</td>
				<td>&nbsp;</td>
			</tr>	
			<tr>
				<td class=legend style='font-size:16px'>{language}:</td>
				<td>$field</td>
				<td>&nbsp;</td>
			</tr>			
			<tr>
				<td class=legend style='font-size:16px'>{UseALink}:</td>
				<td>". Field_checkbox("template_link-$t",1,$ligne["template_link"],"UseALink$t()")."</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td class=legend style='font-size:16px'>{http_status_code}:</td>
				<td>". Field_array_Hash($statusCode, "status_code-$t",303,null,null,0,"font-size:16px;")."</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td class=legend style='font-size:16px'>{freewebs_compliance}:</td>
				<td>". Field_checkbox("freewebs_compliance-$t",1,$freewebs_compliance)."</td>
				<td>&nbsp;</td>
			</tr>								
			<tr>
				<td class=legend style='font-size:16px'>{url}:</td>
				<td>". Field_text("template_uri-$t",$ligne["template_uri"],"font-size:16px;width:95%")."</td>
				<td>$freewebsButton</td>
			</tr>
			<tr>
				<td colspan=3 align='right'><hr>". button($btname,"SaveNewTemplate$t()",16)."</td>
			</tR>
		</tbody>
		</table>
		
		
		
		<script>
			var x_SaveNewTemplate$t= function (obj) {
					var res=obj.responseText;
					document.getElementById('$t').innerHTML='';
					if (res.length>3){alert(res);return;}
					YahooWin5Hide();
					$('#SquidTemplateErrorsTable').flexReload();
			}	
		function SaveNewTemplate$t(){
				var template_link=0;
				var freewebs_compliance=0;
				var lang=document.getElementById('lang1-$t').value;
				var tplname=document.getElementById('tplname-$t').value;
				var tplTitle=document.getElementById('template_title-$t').value;
				var status_code=document.getElementById('status_code-$t').value;
				template_uri=document.getElementById('template_uri-$t').value;
				if(document.getElementById('template_link-$t').checked){template_link=1;}
				if(document.getElementById('freewebs_compliance-$t').checked){freewebs_compliance=1;}
				
				if(template_link==0){
					if(lang.length==0){alert('Please select a language..');return;}
					if(tplname.length==0){alert('Please select a template name..');return;}
					if(tplTitle.length==0){alert('Please select a template subject..');return;}
				}else{
					if(template_uri.length==0){alert('Please define an URL..');return;}
				}
				
		    	var XHR = new XHRConnection();
		    	XHR.appendData('newtemplate',tplname);
		      	XHR.appendData('template_name',tplname);
		      	XHR.appendData('template_title',tplTitle);
		      	XHR.appendData('template_link',template_link);
		      	XHR.appendData('template_uri',template_uri);
		      	XHR.appendData('status_code',status_code);
		      	XHR.appendData('freewebs_compliance',freewebs_compliance);
		      	XHR.appendData('lang',lang);
		      	XHR.appendData('templateid','{$_GET["templateid"]}');
		      	
		      	
		      	
		      	
		      	AnimateDiv('$t');
		      	XHR.sendAndLoad('$page', 'POST',x_SaveNewTemplate$t);   				
				
				
			}
			
		function UseALink$t(){}
		
			
		</script>
		";
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function TEMPLATE_ADD_SAVE(){
	
	
	if($_POST["template_uri"]<>null){
		if(!preg_match("#^http#", $_POST["template_uri"])){
			$_POST["template_uri"]="http://". $_POST["template_uri"];
		}
	}
	
	
	if($_POST["status_code"]<>null){
		if($_POST["status_code"]>0){
			$_POST["template_uri"]="{$_POST["status_code"]}:{$_POST["template_uri"]}";
		}
	}
		
	if($_POST["freewebs_compliance"]==1){
		$_POST["template_uri"]=$_POST["template_uri"]."%FREEWEBS%";
	}
	
	$tpl_name=$_POST["template_name"];
	$tpl_name=replace_accents($tpl_name);
	$tpl_name=str_replace(" ","_", $tpl_name);
	$tpl_name=str_replace("-","_", $tpl_name);
	$tpl_name=str_replace("/","_", $tpl_name);
	$tpl_name=str_replace("\\","_", $tpl_name);
	$tpl_name=str_replace(".","_", $tpl_name);
	$tpl_name=str_replace(";","_", $tpl_name);
	$tpl_name=str_replace(",","_", $tpl_name);
	$tpl_name=str_replace("?","_", $tpl_name);
	$tpl_name=str_replace("\$","_", $tpl_name);
	$tpl_name=str_replace("%","_", $tpl_name);
	$tpl_name=str_replace("{","_", $tpl_name);
	$tpl_name=str_replace("}","_", $tpl_name);
	$tpl_name=str_replace("[","_", $tpl_name);
	$tpl_name=str_replace("]","_", $tpl_name);
	$tpl_name=str_replace("(","_", $tpl_name);
	$tpl_name=str_replace(")","_", $tpl_name);
	$tpl_name=str_replace("\"","_", $tpl_name);
	$tpl_name=str_replace("'","_", $tpl_name);
	$tpl_name=str_replace("&","_", $tpl_name);
	$tpl_name=strtoupper($tpl_name);
	$tpl_name=addslashes($tpl_name);
	$md5=md5(serialize($_POST));
	$_POST["template_name"]=$tpl_name;
	$_POST["template_body"]="<table class=\"w100 h100\"><tr><td class=\"c m\"><table style=\"margin:0 auto;border:solid 1px #560000\"><tr><td class=\"l\" style=\"padding:1px\"><div style=\"width:346px;background:#E33630\"><div style=\"padding:3px\"><div style=\"background:#BF0A0A;padding:8px;border:solid 1px #FFF;color:#FFF\"><div style=\"background:#BF0A0A;padding:8px;border:solid 1px #FFF;color:#FFF\"><h1>ERROR: The requested URL could not be retrieved</h1></div><div class=\"c\" style=\"font:bold 13px arial;text-transform:uppercase;color:#FFF;padding:8px 0\">Proxy Error</div><div style=\"background:#F7F7F7;padding:20px 28px 36px\"> <div id=\"titles\"> <h1>ERROR</h1> <h2>The requested URL could not be retrieved</h2> </div> <hr>  <div id=\"content\"> <p>The following error was encountered while trying to retrieve the URL: <a href=\"%U\">%U</a></p>  <blockquote id=\"error\"> <p><b>Access Denied.</b></p> </blockquote>  <p>Access control configuration prevents your request from being allowed at this time. Please contact your service provider if you feel this is incorrect.</p>  <p>Your cache administrator is <a href=\"mailto:%w%W\">%w</a>.</p> <br> </div>  <hr> <div id=\"footer\"> <p>Generated %T by %h (%s)</p> <!-- %c --> </div> </div></div></div></td></tr></table></td></tr></table>";
	
	while (list ($num, $line) = each ($_POST)){
		$_POST[$num]=addslashes($line);
	}
	 
	$sql="INSERT IGNORE INTO squidtpls (zmd5,template_name,template_title,lang,template_body,template_link,template_uri) 
	VALUES ('$md5','{$_POST["template_name"]}','{$_POST["template_title"]}','{$_POST["lang"]}',
	'{$_POST["template_body"]}','{$_POST["template_link"]}','{$_POST["template_uri"]}')";
	
	if(strlen($_POST["templateid"])>5){
		$sql="UPDATE squidtpls SET 
				template_name='{$_POST["template_name"]}',
				template_title='{$_POST["template_title"]}',
				lang='{$_POST["lang"]}',
				template_link='{$_POST["template_link"]}',
				template_uri='{$_POST["template_uri"]}'
				WHERE zmd5='{$_POST["templateid"]}'";
		
	}
	
	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q=new mysql_squid_builder();
	$q->CheckTables();
	$q->QUERY_SQL($sql,"artica_backup");			
	if(!$q->ok){echo $q->mysql_error;	writelogs( $q->mysql_error,__FUNCTION__,__FILE__,__LINE__);return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-templates=yes&zmd5=$md5");		
	
}

function select_lang(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$sql="SELECT lang FROM squidtpls GROUP BY lang ORDER BY lang";
	$results=$q->QUERY_SQL($sql);
	$t=time();
	$lang[]="{select}";
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$txt=$ligne["lang"];
		$lang[$ligne["lang"]]=$txt;
	}		
	
	$field=Field_array_Hash($lang, "lang1-$t",null,"RefreshSquidLangTemplateErrorsTable$t()",null,0,"font-size:16px");
	$html="
	
		<table style='width:99%;margin-top:50px;margin-bottom:8px;' class=form>
		<tbody>
			<tr>
			<td class=legend style='font-size:16px'>{language}:</td>
			<td>$field</td>
		</tr>
		</tbody>
		</table>
		
		
		
		<script>
			function RefreshSquidLangTemplateErrorsTable$t(){
				var lang=document.getElementById('lang1-$t').value;
				if(lang.length==0){alert('Please select a language..');return;}
				$('#SquidTemplateErrorsTable').flexOptions({ url: '$page?view-table=yes&choose-acl={$_GET["choose-acl"]}&lang='+lang+'&xlang='+lang }).flexReload();
				YahooWin5Hide();
			}
		</script>
		";
	echo $tpl->_ENGINE_parse_body($html);
	
}


function popup(){
	$page=CurrentPageName();	
	$tpl=new templates();	
	$squid_choose_template=$tpl->_ENGINE_parse_body("{squid_choose_template}");
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$template_name=$tpl->_ENGINE_parse_body("{template_name}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$lang=$tpl->_ENGINE_parse_body("{language}");
	$rule=$tpl->_ENGINE_parse_body("{rule}");
	$title=$tpl->_ENGINE_parse_body("{subject}");
	$new_template=$tpl->javascript_parse_text("{new_template}");
	$ask_remove_template=$tpl->javascript_parse_text("{ask_remove_template}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$t=time();
	$backToDefault=$tpl->_ENGINE_parse_body("{backToDefault}");
	$ERROR_SQUID_REBUILD_TPLS=$tpl->javascript_parse_text("{ERROR_SQUID_REBUILD_TPLS}");
	$q=new mysql_squid_builder();	
	if($q->COUNT_ROWS("squidtpls")==0){$sock=new sockets();$sock->getFrameWork("squid.php?build-default-tpls=yes");}
	$back="		{name: '$backToDefault', bclass: 'Reconf', onpress : RebuidSquidTplDefault},";
	$template_title_size=325;
	if($_GET["choose-acl"]>0){
		$chooseacl_column="{display: '&nbsp;', name : 'select', width : 31, sortable : false, align: 'center'},";
		$template_title_size=283;
	}
	
	
	$html="
	<div style='margin-left:-10px'>
	<table class='SquidTemplateErrorsTable' style='display: none' id='SquidTemplateErrorsTable' style='width:99%'></table>
	</div>
<script>
var mem$t='';
$(document).ready(function(){
$('#SquidTemplateErrorsTable').flexigrid({
	url: '$page?view-table=yes&lang=&choose-acl={$_GET["choose-acl"]}&choose-generic={$_GET["choose-generic"]}&divid={$_GET["divid"]}',
	dataType: 'json',
	colModel : [
		{display: '$lang', name : 'lang', width :32, sortable : true, align: 'left'},
		{display: '$template_name', name : 'template_name', width :190, sortable : true, align: 'left'},
		{display: '$title', name : 'template_title', width : $template_title_size, sortable : false, align: 'left'},
		{display: '$date', name : 'template_time', width : 110, sortable : true, align: 'left'},
		$chooseacl_column
		{display: '&nbsp;', name : 'delete', width : 31, sortable : false, align: 'center'},
	],
	
	buttons : [
		{name: '$new_template', bclass: 'add', onpress : NewTemplateNew},
		{name: '$lang', bclass: 'Search', onpress : SearchLanguage},
		{separator: true},
		{name: '$online_help', bclass: 'Help', onpress : help$t},

		],
	
	searchitems : [
		{display: '$template_name', name : 'template_name'},
		{display: '$title', name : 'template_title'},
		{display: 'URL', name : 'template_uri'},
		
		
		],
	sortname: 'template_time',
	sortorder: 'desc',
	usepager: true,
	title: '$squid_choose_template',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 771,
	height: 400,
	singleSelect: true
	
	});   
});

function help$t(){
	s_PopUpFull('http://proxy-appliance.org/index.php?cID=385','1024','900');
}



	function SearchLanguage(){
		YahooWin5(350,'$page?Select-lang=yes&choose-acl={$_GET["choose-acl"]}','$lang');
	}
	
	function NewTemplateNew(){
		YahooWin5('600','$page?new-template=yes&t=$t','$new_template');
	}
	
	function NewTemplate(templateid){
		var title='$new_template';
		if(!templateid){templateid='';}else{title=templateid;}
		if(templateid.length<20){
			title='$new_template';
			templateid='';
		}
		
		YahooWin5('600','$page?new-template=yes&t=$t&templateid='+templateid,title);
	}
	
	var x_RebuidSquidTplDefault=function(obj){
		$('#SquidTemplateErrorsTable').flexReload();
    }
    
	var x_TemplateDelete= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);return;}
		$('#row'+mem$t).remove();
		
	}    

    function TemplateDelete(zmd5){
    	if(confirm('$ask_remove_template')){
    		mem$t=zmd5;
      		var XHR = new XHRConnection();
      		XHR.appendData('tpl-remove',zmd5);
      		XHR.sendAndLoad('$page', 'POST',x_TemplateDelete);       	
    	
    	}
    }
    
    var x_ChooseAclsTplSquid=function (obj) {
		var res=obj.responseText;
		if (res.length>3){
			if(document.getElementById('acltplTxt')){
				document.getElementById('acltplTxt').innerHTML=res;
			}
			
		}
		
		
	} 

	function ChooseGenericTemplate(tmplname){
		if(document.getElementById('{$_GET["choose-generic"]}')){
			document.getElementById('{$_GET["choose-generic"]}').value=tmplname;
		}
		if(document.getElementById('{$_GET["divid"]}')){
			document.getElementById('{$_GET["divid"]}').innerHTML=tmplname;
		}		
		{$_GET["yahoo"]}Hide();
	
	
	}
	
    
    function ChooseAclsTplSquid(acl,zmd5){
    	var XHR = new XHRConnection();
      	XHR.appendData('ChooseAclsTplSquid',acl);
      	XHR.appendData('zmd5',zmd5);
      	XHR.sendAndLoad('$page', 'POST',x_ChooseAclsTplSquid); 
    
    }
	
	 function RebuidSquidTplDefault(){
	 	if(confirm('$ERROR_SQUID_REBUILD_TPLS')){
      		var XHR = new XHRConnection();
      		XHR.appendData('RebuidSquidTplDefault','yes');
      		XHR.sendAndLoad('$page', 'POST',x_RebuidSquidTplDefault);   
      	}       
      }	

</script>
";

	echo $html;
	
	
	
}

function view_table(){
	$q=new mysql_squid_builder();
	$Mypage=CurrentPageName();
	$tpl=new templates();	
	$search='%';
	$table="squidtpls";
	$page=1;
	if($_GET["lang"]<>null){$FORCEQ=" AND lang='{$_GET["lang"]}'";}
	$choose_generic=$_GET["choose-generic"];
	if(!$q->FIELD_EXISTS($table, "template_time")){$q->CheckTables();}
	
	if(!$q->TABLE_EXISTS($table)){json_error_show("$table no such table",2);}
	if($q->COUNT_ROWS($table)==0){json_error_show("No data in $table",2);}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCEQ$searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show("$q->mysql_error<hr>$sql",2);}
		$total = $ligne["TCOUNT"];
		
	}else{
		$total = $q->COUNT_ROWS($table);
		if(!$q->ok){json_error_show("$q->mysql_error",2);}
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT *  FROM `$table` WHERE 1 $FORCEQ$searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show("$q->mysql_error",2);}
	$statuscodes=$q->LoadStatusCodes();
	$span="<span style='font-size:12px'>";
	while ($ligne = mysql_fetch_assoc($results)) {
		$zmd5=$ligne["zmd5"];
		$title=base64_encode($ligne["template_title"]);
		$linkZoom="<a href=\"javascript:blur()\" OnClick=\"javascript:Loadjs('$Mypage?Zoom-js={$ligne['zmd5']}&subject=$title');\" style='font-size:12px;text-decoration:underline'>";
	
		
		if($ligne["template_link"]==1){
			$ligne['lang']=imgsimple("24-parameters.png",null,"NewTemplate('$zmd5')");
			
			if(preg_match("#^([0-9]+):(.+)#", $ligne["template_uri"],$ri)){
				$ligne["template_uri"]=$ri[2];
				$ligne["template_uri"]=str_replace("%FREEWEBS%", "<div>+FreeWebs</div>", $ligne["template_uri"]);
				$ligne["template_uri"]=$ligne["template_uri"]."<div>".$statuscodes[$ri[1]]."</div>";
			}
			$ligne["template_title"]=$ligne["template_uri"];
			
		}
		
		$cell=array();
		$delete=imgsimple("delete-24.png",null,"TemplateDelete('{$ligne['zmd5']}')");
		$cell[]="$span$linkZoom{$ligne['lang']}</a></span>";
		$cell[]="$span$linkZoom{$ligne['template_name']}</a></span>";
		$cell[]="$span$linkZoom{$ligne["template_title"]}</a></span>";
		$cell[]="$span$linkZoom{$ligne["template_time"]}</a></span>";
		if($_GET["choose-acl"]>0){
			$cell[]=imgsimple("arrow-right-24.png",null,"ChooseAclsTplSquid('{$_GET["choose-acl"]}','{$ligne['zmd5']}')");
			
		}

		if(strlen($choose_generic)>3){
			$cell[]=imgsimple("arrow-right-24.png",null,"ChooseGenericTemplate('{$ligne['template_name']}')");
		}
		
		$cell[]=$delete;
		

		
		$data['rows'][] = array(
		'id' => $ligne['zmd5'],
		'cell' =>$cell
		);
	}
echo json_encode($data);	
	
}


function FormTemplate(){
	
	$tpl=new templates();
	$q=new mysql();
	$sql="SELECT TEMPLATE_DATA FROM squid_templates WHERE TEMPLATE_NAME='{$_GET["TEMP"]}'";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	
	
	$tpl=new templates();
	$button=$tpl->_ENGINE_parse_body("<input type='submit' value='{apply}'>");
	$tiny=TinyMce('TEMPLATE_DATA',$ligne["TEMPLATE_DATA"]);
	$html="
	<html>
	<head>
	<link href='css/styles_main.css' rel=\"styleSheet\" type='text/css' />
	<link href='css/styles_header.css' rel=\"styleSheet\" type='text/css' />
	<link href='css/styles_middle.css' rel=\"styleSheet\" type='text/css' />
	<link href='css/styles_forms.css' rel=\"styleSheet\" type='text/css' />
	<link href='css/styles_tables.css' rel=\"styleSheet\" type='text/css' />
	<script type='text/javascript' language='JavaScript' src='mouse.js'></script>
	<script type='text/javascript' language='javascript' src='XHRConnection.js'></script>
	<script type='text/javascript' language='javascript' src='default.js'></script>
		
	</head>
	<body width=100% style='background-color:#005447;margin:0px;padding:0px'> 
	<form name='FFM1' METHOD=POST style='margin:0px;padding:0px'>
	<input type='hidden' name='TEMPLATE_NAME' value='{$_GET["TEMP"]}'>
	<div style='text-align:center;width:100%;background-color:white;margin-bottom:10px;padding:10px'>$button<br></div>
	<center>
	<div style='width:750px;height:650px'>$tiny</div>
	</center>
	<div style='text-align:center;width:100%;background-color:white;margin-top:10px;padding:10px'>$button<br></div>
	
	</form>
	</body>
	
	</html>";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function TEMPLATE_REMOVE(){
	$sql="DELETE FROM squidtpls WHERE `zmd5`='{$_POST["tpl-remove"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?squid-templates=yes");	
}


function TEMPLATE_HEADER_SAVE(){
	$template_header=addslashes($_POST["template_header"]);
	if(strlen($template_header)==0){echo "template_header: no data\n";return;}
	$zmd5=$_POST["zmd5"];
	if($zmd5==null){echo "Fatal Key is null!\n";return;}
	$sql="UPDATE squidtpls SET `template_header`='$template_header' WHERE `zmd5`='$zmd5'";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql,"artica_backup");
	
	if(!$q->ok){
		echo $q->mysql_error;
		writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-templates=yes&zmd5=$zmd5");	
}

?>