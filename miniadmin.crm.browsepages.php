<?php
session_start();

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class='text-error'>");
ini_set('error_append_string',"</p>");
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}


include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");
$PRIV=GetPrivs();if(!$PRIV){senderror("no priv");}

if(isset($_GET["section"])){section();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_GET["select-js"])){select_js();exit;}
if(isset($_POST["delete"])){delete();exit;}


js();

function js(){
	
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$source_id=0;
	$title="{webpages}";
	if($source_id>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT servername FROM reverse_sources WHERE ID='$source_id'"));
		$title=$ligne["servername"];
	}
	
	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin4(750,'$page?section=yes&field-id={$_GET["field-id"]}','$title')";
}

function select_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$field=$_GET["field-id"];
	if($ID>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT subject FROM reverse_pages_content WHERE ID='$ID'"));
		$title=$ligne["subject"];
	}
	
	$t=time();
	$title=$tpl->javascript_parse_text("{select} `$title` ?");
	$html="
function Select$t(){
	if(!document.getElementById('$field')){alert('$field, no such id !');return;}
	if( !confirm('$title') ){return;}
	document.getElementById('$field').value=$ID;
	YahooWin4Hide();
}
Select$t();";
echo $html;	
}



function delete_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$title="{webpages}";
	if($ID>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT subject FROM reverse_pages_content WHERE ID='$ID'"));
		$title=$ligne["subject"];
	}
	$t=time();
	$title=$tpl->javascript_parse_text("{delete} `$title` ?");
	$html="
	var xDelete$t=function (obj) {
	var results=obj.responseText;
	if(results.length>10){alert(results);return;}
	ExecuteByClassName('SearchFunction');
	}
	
	function Delete$t(){
	if( !confirm('$title') ){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete','$ID');
	XHR.sendAndLoad('$page', 'POST',xDelete$t);
	}
		
	Delete$t();";
	echo $html;
	
}

function delete(){
	$ID=$_POST["delete"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("UPDATE reverse_dirs SET webpageid=0 WHERE webpageid=$ID");
	if(!$q->ok){echo $q->mysql_error;}
	$q->QUERY_SQL("DELETE FROM reverse_pages_content WHERE ID=$ID");
	if(!$q->ok){echo $q->mysql_error;}
}

function section(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	$error=null;
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{new_page}", "Loadjs('miniadmin.crm.pages.php?ID=0')"));
	echo $boot->SearchFormGen("subject","search","&field-id={$_GET["field-id"]}",$EXPLAIN);	
	
}

function search(){
$page=CurrentPageName();
$boot=new boostrap_form();
$tpl=new templates();
$searchstring=string_to_flexquery("search");
$ORDER=$boot->TableOrder(array("subject"=>"ASC"));
$limitSql="LIMIT 0,250";
$sql="SELECT * FROM reverse_pages_content WHERE 1 $searchstring ORDER BY $ORDER $limitSql";
$q=new mysql_squid_builder();
$results = $q->QUERY_SQL($sql);
if(!$q->ok){echo $q->mysql_error_html();}
	
while ($ligne = mysql_fetch_assoc($results)) {
	$ID=$ligne["ID"];$jsselect=null;$select=null;
	$js=$boot->trswitch("Loadjs('miniadmin.crm.pages.php?ID=$ID')");
	if($_GET["field-id"]<>null){
		$select="Loadjs('$page?select-js=yes&ID=$ID&field-id={$_GET["field-id"]}')";
		$jsselect=$boot->trswitch($select);
		$select_img=imgsimple("arrow-right-24.png",null,$select);
	}
	
	$delete_img=imgsimple("delete-24.png",null,"Loadjs('$page?delete-js=yes&ID=$ID')");
	$tr[]="
	<tr id='{$ligne["ID"]}'>
	<td width='99%' nowrap $js><i class='icon-tags'></i> {$ligne["subject"]}</a></td>
	<td width='45px' nowrap style='vertical-align:middle;text-align:center'>$select_img</td>
	<td width='45px' nowrap style='vertical-align:middle;text-align:center'>$delete_img</td>
	</tr>";
}
	
$html=$boot->TableCompile(
	array("subject"=>"{subject}","ID"=>"select","delete"=>"{delete}"
	),$tr
);
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