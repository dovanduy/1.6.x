<?php
$GLOBALS["ICON_FAMILY"]="SYSTEM";
$GLOBALS["JQUERY_UI"]="android-theme";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$GLOBALS["DEBUG_PRIVS"]=true;
include_once('ressources/class.templates.inc');
session_start();
include_once('ressources/class.html.pages.inc');
include_once('ressources/class.cyrus.inc');
include_once('ressources/class.main_cf.inc');
include_once('ressources/charts.php');
include_once('ressources/class.syslogs.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.os.system.inc');
include_once('ressources/class.mysql.inc');

page();


function page(){
	$users=new usersMenus();
	$title=$users->hostname." For Android/Tablets Login.";
	include_once(dirname(__FILE__)."/ressources/class.page.builder.inc");
	$p=new pagebuilder();
	$jsArtica=$p->jsArtica();	
	$yahoo=$p->YahooBody();
	$css=$p->headcss();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(sitename) as tcount FROM visited_sites WHERE LENGTH(category)=0"));
	$websitesnumsNot=numberFormat($ligne["tcount"],0,""," ");	
	
	
	$html="
	<!DOCTYPE html>
<html lang=\"en\">
<head>
  <meta http-equiv=\"X-UA-Compatible\" content=\"IE=9; IE=8\">
  <meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-type\" />
  <link rel=\"stylesheet\" type=\"text/css\" href=\"/css/artica-theme/jquery-ui.custom.css\" />
  <link rel=\"stylesheet\" type=\"text/css\" href=\"/css/jquery.jgrowl.css\" />
		<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/jquery.cluetip.css\" />
		<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/jquery.treeview.css\" />
		<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/thickbox.css\" media=\"screen\"/>
		<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/jquery.qtip.css\" />
		<link rel=\"stylesheet\" type=\"text/css\" href=\"/fonts.css.php\"/>
		
$css
  <title>$title</title>
$jsArtica
<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/android.css\" />
</head>

<body style=\"margin:0px;padding:0px\">
<div style=\"postition:absolute;top:0px;left:80%;width:980px;min-height:490px;overflow-y:auto\">
	
	<table style='width:980px;'>
	<tr>
	<td valign='top' width=5%>
	            <ul class=\"vertical fl\" rel=\"ver1\">
                <li><a href=\"android.index.php\">{home}</a><span>{main_page}</span></li>
                <li><a href=\"javascript:LoadAjax('mainpage','android.squidstats.members.php')\">{members}</a><span>{list_your_members}</span></li>
                <li><a href=\"#\">Under construction</a><span>to define</span></li>
                <li><a href=\"#\">Under construction</a><span>Under construction</span></li>
                <li><a href=\"javascript:LoadAjax('mainpage','android.squidstats.nocat.php')\"\">{not_categorized}</a><span><b>$websitesnumsNot</b> {websites} {not_categorized}</span></li>
                <li><a rel=\"#\">About</a><span>{parameters}</span></li>
            </ul>
   </td>
   <td valign='top' width=95%>
   	<table style='width:103.5%;margin-left:-23px;margin-top:2px' class=form>
   		<tr>
   			<td style='margin:0px;padding:0px;border:0px'>
   				<div style='margin:0px;padding:0px;border:0px;min-height:475px;height:475px;overflow:auto' id='mainpage'></div>
   			</td>
   		</tr>
   	</table>
   </tr>
	</table>
</div>
<script>
	LoadAjax('mainpage','android.index.server.php');
</script>
$yahoo
</body>
</html>
	
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}