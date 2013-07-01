<?php
session_start();
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',"<p class=text-error>");
ini_set('error_append_string',"</p>");
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
if(isset($_GET["content"])){content();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["master-content"])){master_content();exit;}
if(isset($_GET["graph1"])){graph1();exit;}
if(isset($_GET["graph2"])){graph2();exit;}
if(isset($_GET["graph3"])){graph3();exit;}
if(isset($_GET["graph4"])){graph4();exit;}
if(isset($_GET["graph5"])){graph5();exit;}
if(isset($_GET["rqsize"])){rqsize_page();exit;}
if(isset($_GET["rqsize-graĥs"])){rqsize_graphs();exit;}
if(isset($_GET["rqsize-table"])){rqsize_table();exit;}

if(isset($_GET["rethumbnail"])){rethumbnail();exit;}
if(isset($_GET["www-graĥs"])){www_graphs();exit;}
if(isset($_GET["www-table"])){www_table();exit;}
if(isset($_GET["www-search"])){www_search();exit;}




Privileges_members_ownstats();
if(isset($_GET["whois"])){whois();exit;}

page();


function page(){
	$t=time();
	$familysite=$_GET["familysite"];
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$categories=$q->GET_CATEGORIES($familysite);
	$familysiteenc=urlencode($familysite);
	$thumbs=$q->GET_THUMBNAIL($familysite,320);
	
	if(strpos($categories, ",")>0){
		$categoriesZ=explode(",", $categories);
		$tt[]="<ul>";
		while (list ($num, $ligne) = each ($categoriesZ) ){
			$tt[]="<li>$ligne</li>";
		}
		$tt[]="</ul>";
		$categories=@implode("", $tt);
	}
	
	$t=time();
	$html="
	<table style='width:100%' class=TableRemove>
	<tr>
	<td valign='top'>
		<div id='$t-img'>
		$thumbs
		</div>
		<center style='margin-top:10px'>". button("{regenerate_thumbnail}","LoadAjax('$t-img','$page?rethumbnail=$familysiteenc');")."</center>	
	</td>
	<td valign='top' style='padding-left:20px'>		
		<H3>$familysite</H3>
		<p><strong style='font-size:16px'>{this_website_was_categorized_in}:$categories</strong></p>
		<H3>{details}</H3>
		<div id='$t'></div>
	</td>
	</tr>
	</table>
	<script>
		LoadAjax('$t','$page?whois=$familysiteenc');
	</script>
	
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
}

function whois(){
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$familysite=$_GET["whois"];
	$sql="SELECT whois FROM `visited_sites` WHERE familysite='$familysite'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$whois=unserialize($ligne["whois"]);
	if(!isset($whois["regrinfo"])){
		whoissave_perform($familysite);
		$sql="SELECT whois FROM `visited_sites` WHERE familysite='$familysite'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$whois=unserialize($ligne["whois"]);
		if(!isset($whois["regrinfo"])){return;}
	}
	
	$created=$whois["regrinfo"]["domain"]["created"];
	$sponsor=$whois["regrinfo"]["domain"]["sponsor"];
	$whois["regrinfo"]["owner"]["email"]=str_replace(";", "<br>", $whois["regrinfo"]["owner"]["email"]);
	$owner="
	<table style='width:100%'>
	<tbody>
		<tr>
			<td style='font-size:14px' class=legend valign='top'>mail:</td>
			<td style='font-size:14px'>{$whois["regrinfo"]["owner"]["email"]}</td>
		</tr>
		<tr>
			<td style='font-size:14px' class=legend valign='top'>{name}:</td>
			<td style='font-size:14px'>{$whois["regrinfo"]["owner"]["name"]}</td>
		</tr>
		<tr>
			<td style='font-size:14px' class=legend valign='top'>Tel.:</td>
			<td style='font-size:14px'>{$whois["regrinfo"]["owner"]["phone"]}</td>
		</tr>
		<tr>
			<td style='font-size:14px' class=legend valign='top'>{address}:</td>
			<td style='font-size:14px'>".@implode(" ", $whois["regrinfo"]["owner"]["address"])."</td>
		</tr>
	</tbody>
	</table>
	";
	
	
$html="
	<table style='width:99%'>
	<tbody>
	<tr>
		
	<td style='font-size:16px;height:40px;border-bottom:1px solid #CCCCCC' colspan=2><div style='font-size:11px;text-align:right'>sponsor:$sponsor</div></td>
	</tr>
	<tr>
	<td width=1% nowrap class=legend>{created_on}:</td>
	<td style='font-size:14px'>$created</td>
	</tr>
	<tr>
	<td width=1% nowrap class=legend valign='top'>{owner}:</td>
	<td style='font-size:14px'>$owner</td>
	</tr>
	</tbody>
	</table>";
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}
	
function whoissave_perform($familysite){
		$GLOBALS["Q"]=new mysql_squid_builder();
		include_once(dirname(__FILE__).'/ressources/whois/whois.main.php');
		$domain=$familysite;
		$FamilySite=$GLOBALS["Q"]->GetFamilySites($domain);
		$whois = new Whois();
	
		writelogs("->Lookup($domain)",__FUNCTION__,__FILE__,__LINE__);
		$result = $whois->Lookup($domain);
		if(!is_array($result)){
			writelogs("->Lookup($FamilySite)",__FUNCTION__,__FILE__,__LINE__);
			$result = $whois->Lookup($FamilySite);
		}
	
		if(!is_array($result)){
			writelogs("Not an array....",__FUNCTION__,__FILE__,__LINE__);
			return;
	
		}
		
		
		$whoisdatas=addslashes(serialize($result));
		writelogs("$whoisdatas",__FUNCTION__,__FILE__,__LINE__);
		$sql="SELECT familysite FROM `visited_sites` WHERE familysite='$Familysite'";
		$ligne=mysql_fetch_array($GLOBALS["Q"]->QUERY_SQL($sql));
		if($ligne["familysite"]==null){
			$sql="UPDATE visited_sites SET whois='$whoisdatas' WHERE familysite='$FamilySite'";
		}else{
			$sql="UPDATE visited_sites SET whois='$whoisdatas' WHERE sitename='$domain'";
		}
	
	
	
	
		$GLOBALS["Q"]->QUERY_SQL($sql);
		if(!$GLOBALS["Q"]->ok){
			writelogs("$sql:{$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__);
			echo "{$GLOBALS["Q"]->mysql_error}\n";return;}
	
	}	

function rethumbnail(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?rethumbnail={$_GET["rethumbnail"]}");
	$q=new mysql_squid_builder();
	echo $q->GET_THUMBNAIL($_GET["rethumbnail"],320);
	
}