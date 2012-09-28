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
	
	if(isset($_POST["newtemplate"])){TEMPLATE_ADD_SAVE();exit;}
	if(isset($_POST["template_body"])){ZOOM_SAVE();exit;}
	if(isset($_POST["TEMPLATE_DATA"])){TEMPLATE_SAVE();}
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
	$zmd5=$_GET["Zoom-js"];
	$page=CurrentPageName();
	$title=base64_decode($_GET["subject"]);
	$title=str_replace("'","`", $title);
	$html="YahooWinBrowse(820,'$page?Headers-popup=yes&zmd5=$zmd5','$title')";
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
	$html="
	$('#SquidTemplateErrorsTable').remove();
	$yahoo('774','$page?popup=yes&choose-acl={$_GET["choose-acl"]}','$title');";
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
	$newheader=trim($ligne["template_header"]);
	if($newheader==null){$newheader=@file_get_contents("ressources/databases/squid.default.header.db");}
	$newheader=str_replace("{TITLE}", $ligne["template_title"], $newheader);
	$templateDatas="$newheader{$ligne["template_body"]}</body></html>";
	echo $templateDatas;
}
	
function HEADERS_POPUP(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$t=time();
	$sql="SELECT template_body,template_title FROM squidtpls WHERE `zmd5`='{$_GET["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$page=CurrentPageName();
	$tpl=new templates();
	if($ligne["template_header"]==null){$ligne["template_header"]=@file_get_contents("ressources/databases/squid.default.header.db");}
	$html="
	
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
	
function ZOOM_POPUP(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$t=time();
	$sql="SELECT template_body,template_title FROM squidtpls WHERE `zmd5`='{$_GET["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$page=CurrentPageName();
	$tpl=new templates();	
	$ligne["template_body"]=trim($ligne["template_body"]);	
	if($ligne["template_body"]==null){
		$ligne["template_body"]="<table class=\"w100 h100\"><tr><td class=\"c m\"><table style=\"margin:0 auto;border:solid 1px #560000\"><tr><td class=\"l\" style=\"padding:1px\"><div style=\"width:346px;background:#E33630\"><div style=\"padding:3px\"><div style=\"background:#BF0A0A;padding:8px;border:solid 1px #FFF;color:#FFF\"><div style=\"background:#BF0A0A;padding:8px;border:solid 1px #FFF;color:#FFF\"><h1>ERROR: The requested URL could not be retrieved</h1></div><div class=\"c\" style=\"font:bold 13px arial;text-transform:uppercase;color:#FFF;padding:8px 0\">Proxy Error</div><div style=\"background:#F7F7F7;padding:20px 28px 36px\"> <div id=\"titles\"> <h1>ERROR</h1> <h2>The requested URL could not be retrieved</h2> </div> <hr>  <div id=\"content\"> <p>The following error was encountered while trying to retrieve the URL: <a href=\"%U\">%U</a></p>  <blockquote id=\"error\"> <p><b>Access Denied.</b></p> </blockquote>  <p>Access control configuration prevents your request from being allowed at this time. Please contact your service provider if you feel this is incorrect.</p>  <p>Your cache administrator is <a href=\"mailto:%w%W\">%w</a>.</p> <br> </div>  <hr> <div id=\"footer\"> <p>Generated %T by %h (%s)</p> <!-- %c --> </div> </div></div></div></td></tr></table></td></tr></table>";
	}
	$html="
	<table style='width:99%' class=form>
	<tr>
	<td width=99%>&nbsp;</td>
	<td nowrap width=1%><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$page?headers-js=yes&zmd5={$_GET["zmd5"]}');\"
		style='font-size:14px;font-weight:bold;text-decoration:underline'>{headers}</a>
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
	$sql="SELECT template_title FROM squidtpls WHERE `zmd5`='{$_POST["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	echo $ligne["template_title"];
}


function TEMPLATE_ADD(){
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
	$field=Field_array_Hash($lang, "lang1-$t",$tpl->language,null,null,0,"font-size:16px");
	$html="
		<id id='$t'></div>
		<table style='width:100%;margin-top:20px;margin-bottom:8px' class=form>
		<tbody>
			<tr>
				<td class=legend style='font-size:16px'>{template_name}:</td>
				<td>". Field_text("tplname-$t",null,"font-size:16px;width:95%")."</td>
			</tr>	
			<tr>
				<td class=legend style='font-size:16px'>{subject}:</td>
				<td>". Field_text("template_title-$t",null,"font-size:16px;width:95%")."</td>
			</tr>	
			
			<tr>
				<td class=legend style='font-size:16px'>{language}:</td>
				<td>$field</td>
			</tr>
			<tr>
				<td colspan=2 align='right'><hr>". button("{add}","SaveNewTemplate$t()",16)."</td>*
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
				var lang=document.getElementById('lang1-$t').value;
				if(lang.length==0){alert('Please select a language..');return;}
				var tplname=document.getElementById('tplname-$t').value;
				if(tplname.length==0){alert('Please select a template name..');return;}
				
				var tplTitle=document.getElementById('template_title-$t').value;
				if(tplTitle.length==0){alert('Please select a template subject..');return;}				
				
		    	var XHR = new XHRConnection();
		    	XHR.appendData('newtemplate',tplname);
		      	XHR.appendData('template_name',tplname);
		      	XHR.appendData('template_title',tplTitle);
		      	XHR.appendData('lang',lang);
		      	AnimateDiv('$t');
		      	XHR.sendAndLoad('$page', 'POST',x_SaveNewTemplate$t);   				
				
				
			}
		</script>
		";
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function TEMPLATE_ADD_SAVE(){
	
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
	
	$sql="INSERT IGNORE INTO squidtpls (zmd5,template_name,template_title,lang,template_body) VALUES ('$md5','{$_POST["template_name"]}','{$_POST["template_title"]}','{$_POST["lang"]}','{$_POST["template_body"]}')";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql,"artica_backup");			
	if(!$q->ok){echo $q->mysql_error;return;}
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
	
		<table style='width:100%;margin-top:50px;margin-bottom:8px'>
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
	$new_template=$tpl->_ENGINE_parse_body("{new_template}");
	$ask_remove_template=$tpl->javascript_parse_text("{ask_remove_template}");
	$t=time();
	$backToDefault=$tpl->_ENGINE_parse_body("{backToDefault}");
	$ERROR_SQUID_REBUILD_TPLS=$tpl->javascript_parse_text("{ERROR_SQUID_REBUILD_TPLS}");
	$q=new mysql_squid_builder();	
	if($q->COUNT_ROWS("squidtpls")==0){$sock=new sockets();$sock->getFrameWork("squid.php?build-default-tpls=yes");}
	$back="		{name: '$backToDefault', bclass: 'Reconf', onpress : RebuidSquidTplDefault},";
	$template_title_size=449;
	if($_GET["choose-acl"]>0){
		$chooseacl_column="{display: '&nbsp;', name : 'select', width : 31, sortable : false, align: 'center'},";
		$template_title_size=407;
	}
	
	
	$html="
	<div style='margin-left:-10px'>
	<table class='SquidTemplateErrorsTable' style='display: none' id='SquidTemplateErrorsTable' style='width:99%'></table>
	</div>
<script>
var mem$t='';
$(document).ready(function(){
$('#SquidTemplateErrorsTable').flexigrid({
	url: '$page?view-table=yes&lang=$tpl->language&choose-acl={$_GET["choose-acl"]}',
	dataType: 'json',
	colModel : [
		{display: '$lang', name : 'lang', width :32, sortable : true, align: 'left'},
		{display: '$template_name', name : 'template_name', width :190, sortable : true, align: 'left'},
		{display: '$title', name : 'template_title', width : $template_title_size, sortable : false, align: 'left'},
		$chooseacl_column
		{display: '&nbsp;', name : 'delete', width : 31, sortable : false, align: 'center'},
	],
	
	buttons : [
		{name: '$new_template', bclass: 'add', onpress : NewTemplate},
		{name: '$lang', bclass: 'Search', onpress : SearchLanguage},
		{separator: true},

		],
	
	searchitems : [
		{display: '$template_name', name : 'template_name'},
		{display: '$title', name : 'template_title'}
		],
	sortname: 'template_name',
	sortorder: 'asc',
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

	function SearchLanguage(){
		YahooWin5(250,'$page?Select-lang=yes&choose-acl={$_GET["choose-acl"]}','$lang');
	}
	function NewTemplate(){
		YahooWin5('550','$page?new-template=yes&t=$t','$new_template');
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
	
	
	if($q->COUNT_ROWS($table)==0){json_error_show("NO data in $table",2);}
	
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
	$span="<span style='font-size:12px'>";
	while ($ligne = mysql_fetch_assoc($results)) {
		
		$title=base64_encode($ligne["template_title"]);
		$linkZoom="<a href=\"javascript:blur()\" OnClick=\"javascript:Loadjs('$Mypage?Zoom-js={$ligne['zmd5']}&subject=$title');\" style='font-size:12px;text-decoration:underline'>";
	
		$cell=array();
		$delete=imgsimple("delete-24.png",null,"TemplateDelete('{$ligne['zmd5']}')");
		$cell[]="$span.$linkZoom{$ligne['lang']}</a></span>";
		$cell[]=$span.$linkZoom.$ligne['template_name']."</a></span>";
		$cell[]="$span.$linkZoom{$ligne["template_title"]}</a></span>";
		if($_GET["choose-acl"]>0){
			$cell[]=imgsimple("arrow-right-24.png",null,"ChooseAclsTplSquid('{$_GET["choose-acl"]}','{$ligne['zmd5']}')");
			
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

function TEMPLATE_SAVE(){
	$sql="UPDATE squid_templates SET `TEMPLATE_DATA`='{$_POST["TEMPLATE_DATA"]}' WHERE `TEMPLATE_NAME`='{$_POST["TEMPLATE_NAME"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?squid-templates=yes");
	
}


?>