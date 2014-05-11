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

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["ID"])){Save();exit;}

js();

function GetPrivs(){
	$NGNIX_PRIVS=$_SESSION["NGNIX_PRIVS"];
	$users=new usersMenus();
	if($users->AsSystemWebMaster){return true;}
	if($users->AsSquidAdministrator){return true;}
	if(count($_SESSION["NGNIX_PRIVS"])>0){return true;}

	return false;

}

function js(){
	
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$title="{new_page}";
	if($ID>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT subject FROM reverse_pages_content WHERE ID='$ID'"));
		$title=$ligne["subject"];
	}
	
	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin6(850,'$page?popup=yes&ID=$ID','$title')";
}


function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$title="{new_page}";
	$buttonname="{add}";
	if($ID>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_pages_content WHERE ID='$ID'"));
		$title=$ligne["subject"];
		$buttonname="{apply}";
	}	
	
	if($ligne["content"]==null){$ligne["content"]="<html>\n<head>\n<title>It's works</title>\n</head>\n<body>\n\t<H1>It works!</H1>\n</body>\n</html>";}
	
	
	
	$boot=new boostrap_form();
	$boot->set_hidden("ID", $ID);
	$boot->set_formtitle($title);
	if(!is_numeric($ligne["cachemin"])){$ligne["cachemin"]=5;}
	
	$boot->set_list("cachemin", "{cache}", $ligne["cachemin"],$q->CACHE_AGES);
	$boot->set_field("subject", "{subject}", $ligne["subject"]);
	$boot->set_textarea("content", "{content}", $ligne["content"],array("ENCODE"=>true,"HEIGHT"=>350));
	$boot->set_button($buttonname);
	$boot->set_RefreshSearchs();
	if($ID==0){$boot->set_CloseYahoo("YahooWin6");}
	echo $boot->Compile();
	
	
}
function Save(){
	$ID=$_POST["ID"];
	$subject=url_decode_special_tool($_POST["subject"]);
	$content=url_decode_special_tool($_POST["content"]);
	$zDate=date("Y-m-d H:i:s");
	if($ID==0){
		$content=mysql_escape_string2($content);
		$subject=mysql_escape_string2($subject);
		
		$sql="INSERT IGNORE INTO reverse_pages_content (`zDate`,`subject`,`content`,`cachemin`)
				VALUES('$zDate','$subject','$content','{$_POST["cachemin"]}')";
		
		
	}else{
		$sql="UPDATE reverse_pages_content SET 
			`subject`='$subject',`content`='$content',`zDate`='$zDate',`cachemin`='{$_POST["cachemin"]}' WHERE ID=$ID";
	}
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
	
	
}