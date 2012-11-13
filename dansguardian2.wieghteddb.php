<?php
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_GET["search"])){search();exit;}
if(isset($_GET["WeightedPhraseEdit-js"])){WeightedPhraseEdit_js();exit;}
if(isset($_GET["WeightedPhraseEdit-popup"])){WeightedPhraseEdit_popup();exit;}
if(isset($_GET["WeightedPhraseEdit-search"])){WeightedPhraseEdit_search();exit;}
if(isset($_GET["WeightedPhraseEdit-edit"])){WeightedPhraseEdit_edit();exit;}
if(isset($_POST["WeightedPhraseEdit-save"])){WeightedPhraseEdit_save();exit;}
if(isset($_POST["WeightedPhraseEdit-delete"])){WeightedPhraseEdit_delete();exit;}
if(isset($_GET["WeightedPhraseEdit-jsjs"])){WeightedPhraseEdit_jsjs();exit;}




table();


function WeightedPhraseEdit_js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$title="{$_GET["language"]}:{$_GET["category"]}";
	$html="YahooWin4('650','$page?WeightedPhraseEdit-popup=yes&language={$_GET["language"]}&category={$_GET["category"]}','$title')";
	echo $html;
}


function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();	
	$q=new mysql_squid_builder();
	
	$sql="SELECT language FROM phraselists_weigthed GROUP BY language ORDER BY language";
	$results = $q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		$a[$ligne["language"]]=$ligne["language"];
	}
	$a[null]="{select}";	
	$html="
	
	<center>
	<table style='width:55%' class=form>
	<tbody>
		<tr><td class=legend>{categories}:</td>
		<td>". Field_text("$t-search",null,"font-size:16px;width:220px",null,null,null,false,"Category{$t}SearchCheck(event)")."</td>
		<td>". Field_array_Hash($a, "$t-language",null,null,null,0,"font-size:14px")."</td>
		<td width=1%>". button("{search}","Category{$t}Search()")."</td>
		</tr>
	</tbody>
	</table>
	
	
	<div id='dansguardian2-{$t}-list' style='width:100%;height:350px;overlow:auto'></div>
	
	<script>
		function Category{$t}SearchCheck(e){
			if(checkEnter(e)){Category{$t}Search();}
		}
		
		function Category{$t}Search(){
			var se=escape(document.getElementById('{$t}-search').value);
			var lang=escape(document.getElementById('{$t}-language').value);
			LoadAjax('dansguardian2-{$t}-list','$page?search='+se+'&lang='+lang+'&t=$t');
		
		}
		
		Category{$t}Search();
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);		
}

function search(){

	$search=$_GET["search"];
	$search="*$search*";
	$search=str_replace("**", "*", $search);
	$search=str_replace("**", "*", $search);
	$search=str_replace("*", "%", $search);	
	
	$language=$_GET["lang"];
	
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();	
	
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}

	if($language<>null){$language_q=" AND language='$language'";}
	$sql="SELECT COUNT(zmd5) as tcount,category,language FROM phraselists_weigthed GROUP BY category,language 
	HAVING category LIKE '$search' $language_q LIMIT 0,100";
	
	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results=$q->QUERY_SQL($sql);
	$add=imgtootltip("plus-24.png","{add} {category}","Loadjs('dansguardian2.wieghteddb.php?WeightedPhraseEdit-jsjs=yes&t={$_GET["t"]}');");
	$compile_all=imgtootltip("compile-distri-32.png","{saveToDisk} {all}","Loadjs('$page?compile-all-dbs-js=yes')");
	if(!$q->ok){echo "<H2>Fatal Error: $q->mysql_error</H2>";}
	
		
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$select=imgsimple("32-parameters.png","{edit}","WeightedPhraseEdit('{$ligne["language"]}','{$ligne["category"]}')");
		$delete=imgsimple("delete-32.png","{delete}","DansGuardianDeleteMember('{$ligne["ID"]}')");
		$compile=imgsimple("compile-distri-32.png","{saveToDisk}","DansGuardianCompileDB('$categoryname')");
		$color="black";

		$TOTAL_ITEMS=$TOTAL_ITEMS+$ligne["tcount"];
		
		
	
	
		$html=$html."
		<tr class=$classtr>
			<td width=1%>$select</td>
			<td style='font-size:14px;font-weight:bold;color:$color' width=1% nowrap align='left'>{$ligne["language"]}</td>
			<td style='font-size:14px;font-weight:bold;color:$color' width=1% nowrap align='left'>{$ligne["category"]}</td>
			<td style='font-size:14px;font-weight:bold;color:$color' width=99%>{{$ligne["category"]}}</td>
			<td style='font-size:14px;font-weight:bold;color:$color' width=1% nowrap align='left'>{$ligne["tcount"]}</td>
			<td width=1%>$delete</td>
		</tr>
		";
	}
	
	$TOTAL_ITEMS=numberFormat($TOTAL_ITEMS,0,""," ");	
	$header="<center>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th width=1%>$add</th>
		<th width=1%>{language}</th>
		<th width=99% colspan=2>{category}</th>
		<th width=1%>$TOTAL_ITEMS {items}</th>
		<th width=1%>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>";		
	

	
	$html=$header.$html."</table>
	</center>
	
	<script>
		function DansGuardianCompileDB(category){
			Loadjs('ufdbguard.compile.category.php?category='+category);
		}
		
		function CheckStatsApplianceC(){
			LoadAjax('CheckStatsAppliance','$page?CheckStatsAppliance=yes');
		}
		
		function WeightedPhraseEdit(lang,cat){
			Loadjs('$page?WeightedPhraseEdit-js=yes&language='+lang+'&category='+cat);
		
		}
		
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function WeightedPhraseEdit_jsjs(){
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{add} {category}");
	$page=CurrentPageName();
	echo "YahooWin5('600','$page?WeightedPhraseEdit-edit=yes&zmd5=&language=&category=&t={$_GET["t"]}','$title');";
	
}

function add_category_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{add}::{personal_category}");
	if($_GET["cat"]<>null){$title=$tpl->_ENGINE_parse_body("{$_GET["cat"]}::{personal_category}");}
	$html="YahooWin4('505','$page?add-perso-cat-popup=yes&cat={$_GET["cat"]}','$title');";
	echo $html;
}

function WeightedPhraseEdit_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();	
	$q=new mysql_squid_builder();
	
	
	$html="
	
	<center>
	<table style='width:55%' class=form>
	<tbody>
		<tr><td class=legend>{words}:</td>
		<td>". Field_text("$t-search",null,"font-size:16px;width:220px",null,null,null,false,"Category{$t}SearchCheck(event)")."</td>
		<td width=1%>". button("{search}","Category{$t}Search()")."</td>
		</tr>
	</tbody>
	</table>
	
	
	<div id='dansguardian2-{$t}-list' style='width:100%;height:350px;overlow:auto'></div>
	
	<script>
		function Category{$t}SearchCheck(e){
			if(checkEnter(e)){Category{$t}Search();}
		}
		
		function Category{$t}Search(){
			var se=escape(document.getElementById('{$t}-search').value);
			LoadAjax('dansguardian2-{$t}-list','$page?WeightedPhraseEdit-search='+se+'&language={$_GET["language"]}&category={$_GET["category"]}&t=$t');
		
		}
		
		Category{$t}Search();
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function WeightedPhraseEdit_search(){
	$search=$_GET["search"];
	$search="*$search*";
	$search=str_replace("**", "*", $search);
	$search=str_replace("**", "*", $search);
	$search=str_replace("*", "%", $search);	
	
	$language=$_GET["language"];
	$category=$_GET["category"];
	
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	
	
	if($language<>null){$language_q=" AND language='$language'";}
	$sql="SELECT zmd5,category,language,pattern,score FROM phraselists_weigthed WHERE category='$category' 
	AND language='$language' AND pattern LIKE '$search' ORDER BY zDate DESC LIMIT 0,50";
	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>Fatal Error: $q->mysql_error</H2>";}
	
		
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$select=imgsimple("32-parameters.png","{edit}","WeightedPhraseEdit('{$ligne["language"]}','{$ligne["category"]}')");
		$delete=imgsimple("delete-32.png","{delete}","DeleteWords('{$ligne["zmd5"]}')");
		$compile=imgsimple("compile-distri-32.png","{saveToDisk}","DansGuardianCompileDB('$categoryname')");
		$color="black";

		if(trim($ligne["pattern"]==null)){continue;}
		
		
	$ligne["pattern"]=htmlentities($ligne["pattern"]);
		$js="<a href=\"javascript:blur();\" OnClick=\"javascript:EditWords('{$ligne["zmd5"]}','{$ligne["language"]}','{$ligne["category"]}')\" style='font-size:14px;font-weight:bold;color:$color;font-family:Courier New;text-decoration:underline'>";
		$html=$html."
		<tr class=$classtr id='{$ligne["zmd5"]}'>
			<td style='font-size:14px;font-weight:bold;color:$color;font-family:Courier New' width=99% colspan=2>$js{$ligne["pattern"]}</a></td>
			<td style='font-size:14px;font-weight:bold;color:$color;font-family:Courier New' width=1%>{$ligne["score"]}</td>
			<td style='font-size:14px;font-weight:bold;color:$color' width=1% nowrap align='left'>$delete</td>
		</tr>
		";
	}
	
	$TOTAL_ITEMS=numberFormat($TOTAL_ITEMS,0,""," ");	
	$add=imgtootltip("plus-24.png","{add} {words}","EditWords('','{$_GET["language"]}','{$_GET["category"]}')");
	$header="<center>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th width=1%>$add</th>
		<th width=99%>{items}</th>
		<th width=1%>{score}</th>
		<th width=1%>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>";		
	

	
	$html=$header.$html."</table>
	</center>
	
	<script>
		var xzmd5='';
		function EditWords(zmd5,language,category){
			YahooWin5('600','$page?WeightedPhraseEdit-edit=yes&zmd5='+zmd5+'&language='+language+'&category='+category+'&t={$_GET["t"]}',zmd5);
		}
		
	var x_DeleteWords=function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		$('#'+xzmd5).remove();
		
	}	
		function DeleteWords(zmd5){
		var XHR = new XHRConnection();
		XHR.appendData('WeightedPhraseEdit-delete','yes');
		XHR.appendData('zmd5',zmd5);
		xzmd5=zmd5;
		XHR.sendAndLoad('$page', 'POST',x_DeleteWords);	
	}	
		
		function CheckStatsApplianceC(){
			LoadAjax('CheckStatsAppliance','$page?CheckStatsAppliance=yes');
		}
		
		function WeightedPhraseEdit(lang,cat){
			Loadjs('$page?WeightedPhraseEdit-js=yes&language='+lang+'&category='+cat);
		
		}
		
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);		
	
}

function WeightedPhraseEdit_edit(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();		
	$language=$_GET["language"];
	$category=$_GET["category"];
	$XR_LANG="XHR.appendData('language','$language');";
	$XR_CAT="XHR.appendData('category','$category');";
	$t=time();
	
	if($category==null){
		$XR_CAT="XHR.appendData('category',document.getElementById('$t-category').value);";
		$category_field="<tr>
		<td class=legend>{category}:</td>
		<td>". Field_text("$t-category",null,"font-size:16px;width:150px")."</td>
		</tr>";
	}
	
if($language==null){
		$XR_LANG="XHR.appendData('language',document.getElementById('$t-language').value);";
		$language_field="<tr>
		<td class=legend>{language}:</td>
		<td>". Field_text("$t-language",null,"font-size:16px;width:150px")."</td>
		</tr>";
	}	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT *  FROM phraselists_weigthed WHERE zmd5='{$_GET["zmd5"]}'"));
	$html="
	<div id='$t'>
	<div class=explain>{weighted_dans_explain}</div>
	<textarea id='$t-words' name='$t-words' style='height:50px;width:100%;border:2px solid #CCCCCC;font-size:16px;font-weight:bold;font-family:Courier New'>{$ligne["pattern"]}</textarea>
	<div style='widh:100%;text-align:right;margin-top:10px'>
		<table>
		<tbody>
		<tr>
		<td class=legend>{score}:</td>
		<td>". Field_text("$t-score",$ligne["score"],"font-size:16px;width:90px")."</td>
		</tr>
		$category_field
		$language_field
	</tbody>
	</table>
	</div>
	<div style='widh:100%;text-align:right'><hr>".button("{apply}","SaveWords()")."</div>
	
	<script>
	var x_SaveWords=function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		Category{$_GET["t"]}Search();
		YahooWin5Hide();
	}			
	
	
	function SaveWords(){
		var XHR = new XHRConnection();
		XHR.appendData('WeightedPhraseEdit-save','yes');
		XHR.appendData('zmd5','{$_GET["zmd5"]}');
		$XR_LANG
		$XR_CAT
		XHR.appendData('words',document.getElementById('$t-words').value);
		XHR.appendData('score',document.getElementById('$t-score').value);
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_SaveWords);	
	}	
	
	</script>";
	
	
	$html=$tpl->_ENGINE_parse_body($html);
	$html=str_replace(";&amp;lt;","&lt;",$html);
	$html=str_replace(";&amp;gt;","&gt;",$html);
	$html=str_replace("&amp;lt;","&lt;",$html);
	$html=str_replace("&amp;gt;","&gt;",$html);
	$html=str_replace(";&amp;gt","&gt;",$html);		
	
	echo $html;
}
function WeightedPhraseEdit_delete(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$sql="DELETE FROM phraselists_weigthed WHERE `zmd5`='{$_POST["zmd5"]}'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n$sql\n";return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?rebuild-filters=yes");		
}

function WeightedPhraseEdit_save(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();			
	$sql="UPDATE phraselists_weigthed SET 
	`score`='{$_POST["score"]}',
	`pattern`='{$_POST["words"]}' WHERE `zmd5`='{$_POST["zmd5"]}'";
	
	if($_POST["zmd5"]==null){
		$date=date('Y-m-d H:i:s');
		$t=time();
		$_POST["zmd5"]=md5("{$_POST["words"]}{$_POST["language"]}{$_POST["category"]}");
		$sql="INSERT IGNORE INTO phraselists_weigthed (zmd5,zDate,category,pattern,language,score,enabled,uuid)
		VALUES('{$_POST["zmd5"]}','$date','{$_POST["category"]}','{$_POST["words"]}','{$_POST["language"]}','{$_POST["score"]}',1,'$t')";
		
		
	}
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	
$sock=new sockets();
$sock->getFrameWork("squid.php?rebuild-filters=yes");	
	
	
}