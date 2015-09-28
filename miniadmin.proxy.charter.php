<?php
session_start();
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");

$users=new usersMenus();
if(isset($_GET["settings"])){settings();exit;}
if(isset($_GET["itcharters-section"])){charters_section();exit;}
if(isset($_GET["itcharters-search"])){charters_search();exit;}
if(isset($_POST["EnableITChart"])){EnableITChart();exit;}
if(isset($_GET["charter-js"])){charter_js();exit;}
if(isset($_GET["charter-tabs"])){charter_tabs();exit;}
if(isset($_GET["charter-settings"])){charter_settings();exit;}
if(isset($_GET["charter-content"])){charter_content();exit;}
if(isset($_GET["charter-headers"])){charter_headers();exit;}
if(isset($_GET["itcharters-events"])){charter_events_section();exit;}
if(isset($_GET["itcharters-events-search"])){charter_events_search();exit;}
if(isset($_POST["itcharters-events-delete"])){charter_events_delete();exit;}
if(isset($_POST["itcharters-delete"])){charter_delete();exit;}


if(isset($_POST["ID"])){charter_save();exit;}

tabs();


function tabs(){
	$page=CurrentPageName();
	$sock=new sockets();

	$mini=new boostrap_form();
	$array["{parameters}"]="$page?settings=yes";
	$array["{it_charters}"]="$page?itcharters-section=yes";
	$array["{events}"]="$page?itcharters-events=yes";
	echo $mini->build_tab($array);
}
function charter_tabs(){
	$page=CurrentPageName();
	$mini=new boostrap_form();
	$ID=$_GET["ID"];
	$array["{parameters}"]="$page?charter-settings=yes&ID=$ID";
	if($ID>0){
		$array["{content}"]="$page?charter-content=yes&ID=$ID";
		$array["{headers}"]="$page?charter-headers=yes&ID=$ID";
	}
	echo $mini->build_tab($array);
}

function settings(){
	$page=CurrentPageName();
	$sock=new sockets();
	$boot=new boostrap_form();	
	$sock=new sockets();
	$tpl=new templates();
	$EnableITChart=$sock->GET_INFO("EnableITChart");
	$ItChartFreeWeb=$sock->GET_INFO("ItChartFreeWeb");
	if(!is_numeric($EnableITChart)){$EnableITChart=0;}
	
	$q=new mysql_squid_builder();
	$q->CheckTables();
	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(*) as tcount FROM itcharters"));
	if($ligne["tcount"]==0){
		echo "<p class=text-error>".$tpl->_ENGINE_parse_body("{ERROR_NO_ITCHART_CREATED}")."</p>";
	}
	
	$boot->set_formtitle("{IT_charter}");
	$boot->set_formdescription("{IT_charter_explain}<br>{IT_charter_explain2}");
	$boot->set_checkbox("EnableITChart", "{enable_it_charter}", $EnableITChart);
	
	$sql="SELECT servername,UseSSL FROM freeweb WHERE groupware='ERRSQUID'";
	
	$me=$_SERVER["SERVER_ADDR"].":".$_SERVER["SERVER_PORT"];
	
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "<p class=text-error>$q->mysql_error</p>";}
	
	$hash[$me]=$me;
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$servername=$ligne["servername"];
		if($ligne["UseSSL"]==1){$servername=$servername.":443";}
		$hash[$servername]=$servername;
	
	}	
	
	if($ItChartFreeWeb==null){$sock->SET_INFO("ItChartFreeWeb", $me);}
	$boot->set_list("ItChartFreeWeb", "{webserver}", $hash,$ItChartFreeWeb);
	
	$users=new usersMenus();
	if(!$users->AsDansGuardianAdministrator){$boot->set_form_locked();}
	echo $boot->Compile();
	
	
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

function charter_content(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$ID=$_GET["ID"];
	$t=time();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM itcharters WHERE ID='$ID'"));
	$ChartContent=trim($ligne["ChartContent"]);
	
	
	if(strlen($ChartContent)<10){
		$ChartContent=@file_get_contents("ressources/databases/DefaultAcceptableUsePolicy.html");
	}
	
	
	$button=$tpl->_ENGINE_parse_body(button("{apply}", "Save$t()",18));
	$button2=$tpl->_ENGINE_parse_body(button("{apply}", "Save2$t()",18));
	
	
	$tiny=TinyMce('ChartContent',$ChartContent,true);
	
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
	AnimateDiv('$t');
	XHR.appendData('ChartContent', encodeURIComponent(tinymce.get('ChartContent').getContent()));
	XHR.sendAndLoad('$page', 'POST',xSave$t);		
}
</script>";

echo $html;
	
}

function charter_save(){
	$q=new mysql_squid_builder();
	$q->CheckTables();
	$ID=$_POST["ID"];
	unset($_POST["ID"]);
	
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
	$sock=new sockets();
	$sock->getFrameWork("squid.php?squid-k-reconfigure=yes");
	
}

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
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT title FROM itcharters WHERE ID='$ID'"));
		$title=$ligne["title"];
	}
	
	echo "YahooWin2(990,'$page?charter-tabs=yes&ID=$ID','$title')";

}
function charter_settings(){
	$ID=$_GET["ID"];
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	$boot=new boostrap_form();
	$users=new usersMenus();
	$title="Acceptable Use Policy";
	$btname="{add}";
	if($ID>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT TextIntro,TextButton,title FROM itcharters WHERE ID='$ID'"));
		if(!$q->ok){echo "<p class=text-error>$q->mysql_error</p>";}
		
		$title=$ligne["title"];
		$btname="{apply}";
	}	
	
	if($ligne["TextIntro"]==null){
		$ligne["TextIntro"]="<p style='font-size:18px'>Please read the IT chart before accessing trough Internet</p>";
	}
	if($ligne["TextButton"]==null){
		$ligne["TextButton"]="I accept the terms and conditions of this agreement";
		
	}
	
	
	
	$boot->set_formtitle($title);
	$boot->set_hidden("ID", $ID);
	$boot->set_field("title", "{page_title}", $title);
	$boot->set_textarea("TextIntro", "{introduction_text}", $ligne["TextIntro"],array("ENCODE"=>true));
	$boot->set_field("TextButton", "{text_button}", $ligne["TextButton"],array("ENCODE"=>true));
	
	
	if(!$users->AsDansGuardianAdministrator){$boot->set_form_locked();}
	$boot->set_button($btname);
	if($ID==0){$boot->set_CloseYahoo("YahooWin2");}
	$boot->set_RefreshSearchs();
	echo $boot->Compile();
}


function EnableITChart(){
	$sock=new sockets();
	$sock->SET_INFO("EnableITChart", $_POST["EnableITChart"]);
	$sock->SET_INFO("ItChartFreeWeb", $_POST["ItChartFreeWeb"]);
	$sock->getFrameWork("squid.php?squid-reconfigure=yes");
	
}
function charters_section(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{new_itchart}", "Loadjs('$page?charter-js=yes&ID=0')"));
	echo $boot->SearchFormGen("title","itcharters-search",null,$EXPLAIN);	
}
function charter_events_section(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	echo $boot->SearchFormGen("uid,ipaddr,MAC","itcharters-events-search",null);	
	
}

function charter_events_search(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	$searchstring=string_to_flexquery("itcharters-events-search");
	$q=new mysql_squid_builder();
	$t=time();
	$ORDER=$boot->TableOrder(array("zDate"=>"DESC"));
	$table="itchartlog";
	$sql="SELECT *  FROM $table WHERE 1 $searchstring ORDER BY $ORDER LIMIT 0,150";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo "<p class=text-error>$q->mysql_error<hr><code>$sql</code></p>";}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$md=md5(serialize($ligne));
		
		
		$delete=imgtootltip("delete-24.png",null,"Delete$t({$ligne["ID"]},'$md')");
		$chartid=$ligne["chartid"];
		$link=$boot->trswitch("Loadjs('$page?charter-js=yes&ID=$chartid')");
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT TextIntro,title FROM itcharters WHERE ID='$chartid'"));
		$tr[]="
		<tr id='$md'>
		<td nowrap width=1%><i class='icon-time'></i> {$ligne["zDate"]}</td>
		<td $link><i class='icon-info'></i> {$ligne2["title"]}</td>
		<td nowrap width=1%><i class='icon-user'></i> {$ligne["uid"]}</td>
		<td nowrap width=1%><i class='icon-user'></i> {$ligne["ipaddr"]}</td>
		<td nowrap width=1%><i class='icon-user'></i> {$ligne["MAC"]}</td>
		<td nowrap width=1%>$delete</td>
		</tr>";
	
	
	}
	
	$delete_text=$tpl->javascript_parse_text("{delete_this_event}");
	
	echo $boot->TableCompile(array("zDate"=>"{date}",
			"chartid"=>" {it_charters}",
			"uid"=>"{member}",
			"ipaddr"=>"{ipaddr}",
			"MAC"=>"{MAC}",
			"delete"=>null,
			
			
			),$tr)."
					
<script>
var mem$t='';
var xDelete$t=function(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	$('#'+mem$t).remove();
}
function Delete$t(ID,mem){
	mem$t=mem;
	if(confirm('$delete_text '+ID+'?')){
		mem$t=mem;
		var XHR = new XHRConnection();
		XHR.appendData('itcharters-events-delete',ID);
		XHR.sendAndLoad('$page', 'POST',xDelete$t);
		}
	}
</script>					
";
}

function charters_search(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();	
	$searchstring=string_to_flexquery("itcharters-search");
	$q=new mysql_squid_builder();
	$ORDER=$boot->TableOrder(array("title"=>"ASC"));
	$table="itcharters";
	$t=time();
	$sql="SELECT ID,title,TextIntro,TextButton  FROM $table WHERE 1 $searchstring ORDER BY $ORDER LIMIT 0,150";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo "<p class=text-error>$q->mysql_error<hr><code>$sql</code></p>";}
	
	while ($ligne = mysql_fetch_assoc($results)) {
	$md=md5(serialize($ligne));
	$link=$boot->trswitch("Loadjs('$page?charter-js=yes&ID={$ligne["ID"]}')");
	$delete=imgtootltip("delete-24.png",null,"Delete$t({$ligne["ID"]},'$md')");
	
	$tr[]="
	<tr id='$md'>
	<td $link><i class='icon-globe'></i> {$ligne["title"]}</td>
	<td width=1% nowrap>$delete</td>
	</tr>";
	
	
	}
	
	
	$delete_text=$tpl->javascript_parse_text("{delete_this_itchart}");
	echo $boot->TableCompile(array("title"=>"{it_charters}","delete"=>null),$tr)."
<script>
var mem$t='';
var xDelete$t=function(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	$('#'+mem$t).remove();
}
function Delete$t(ID,mem){
	mem$t=mem;
	if(confirm('$delete_text '+ID+'?')){
		mem$t=mem;
		var XHR = new XHRConnection();
		XHR.appendData('itcharters-delete',ID);
		XHR.sendAndLoad('$page', 'POST',xDelete$t);
		}
	}
</script>					
";
}
function  charter_delete(){
	$ID=$_POST["itcharters-delete"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM itchartlog WHERE chartid='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM itcharters WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}	
	$sock=new sockets();
	$sock->getFrameWork("squid.php?squid-k-reconfigure=yes");	
}

function charter_events_delete(){
	$ID=$_POST["itcharters-events-delete"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM itchartlog WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?squid-k-reconfigure=yes");
}
