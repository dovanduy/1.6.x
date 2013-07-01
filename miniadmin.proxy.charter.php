<?php
session_start();

ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
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

tabs();


function tabs(){
	$page=CurrentPageName();
	$sock=new sockets();

	$mini=new boostrap_form();
	$array["{parameters}"]="$page?settings=yes";
	$array["{it_charters}"]="$page?itcharters-section=yes";
	echo $mini->build_tab($array);
}


function settings(){
	$page=CurrentPageName();
	$sock=new sockets();
	$boot=new boostrap_form();	
	$sock=new sockets();
	$EnableITChart=$sock->GET_INFO("EnableITChart");
	$ItChartFreeWeb=$sock->GET_INFO("ItChartFreeWeb");
	if(!is_numeric($EnableITChart)){$EnableITChart=0;}
	$boot->set_formtitle("{IT_charter}");
	$boot->set_formdescription("{IT_charter_explain}");
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

function EnableITChart(){
	$sock=new sockets();
	$sock->SET_INFO("EnableITChart", $_POST["EnableITChart"]);
	$sock->SET_INFO("ItChartFreeWeb", $_POST["ItChartFreeWeb"]);
	
}
function charters_section(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	$AdminPrivs=AdminPrivs();
	if($AdminPrivs){
		$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{new_itchart}", "Loadjs('$page?js-source=yes&source-id=0')"));
	}
	echo $boot->SearchFormGen("title","itcharters-search",null,$EXPLAIN);	
	
	
}

function charters_search(){
	
	
}


