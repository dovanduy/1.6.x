<?php
if(isset($_GET["verbose"])){$GLOBALS["DEBUG_PRIVS"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


session_start();
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class='text-error'>");
ini_set('error_append_string',"</p>");
$_SESSION["MINIADM"]=true;
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');



if(!isset($_SESSION["uid"])){writelogs("Redirecto to miniadm.logon.php...","NULL",__FILE__,__LINE__);header("location:miniadm.logon.php");}
BuildSessionAuth();
if($_SESSION["uid"]=="-100"){writelogs("Redirecto to location:admin.index.php...","NULL",__FILE__,__LINE__);header("location:admin.index.php");die();}
$users=new usersMenus();if(!$users->AsProxyMonitor){die();}


if(isset($_GET["section"])){section_start();exit;}
if(isset($_GET["session-search"])){search_session();exit;}

section_start();


function section_start(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	echo $boot->SearchFormGen("searchstring","session-search",null,null);
}



function search_session(){
	$tpl=new templates();
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->getFrameWork("squid.php?squid-sessions=yes")));
	if(count($datas)==0){senderrors("{this_request_contains_no_data}");}
	
	while (list($num,$val)=each($datas)){
		
		if($val["USER"]==null){$val["USER"]=$val["CLIENT"];}else{
			$val["USER"]=$val["USER"]."<br><i style='font-size:11px'>{$val["CLIENT"]}</i>";
		}
		
	$tr[]="
	<tr>
	
	<td width=33% $jsedit style='vertical-align:middle' nowrap>
		<span style='font-size:18px;font-weight:bold'>{$val["USER"]}</span>
	</td>
	<td width=33% $jsedit style='vertical-align:middle'>
		<span style='font-size:14px;font-weight:bold'>{$val["URI"]}</span>
	</td>	
	<td width=33% $jsedit style='vertical-align:middle'nowrap>
		<span style='font-size:18px;font-weight:bold'>{$val["SINCE"]}</span>
	</td>		
	</tr>";	
	
	
	}
	
	echo $tpl->_ENGINE_parse_body("
	
			<table class='table table-bordered '>
	
			<thead>
				<tr>
					<th >{member}</th>
					<th >{requests}</th>
					<th >{since}</th>
				</tr>
			</thead>
			 <tbody>").@implode("", $tr)."</tbody></table>";

}
?>