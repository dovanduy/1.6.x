<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_SOCK"]=true;$GLOBALS["DEBUG"]=true;$_GET["debug-page"]=true;$GLOBALS["DEBUG_INCLUDES"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if($GLOBALS["VERBOSE"]){echo __LINE__."::session_start()<br>\n";}
session_start();
if($GLOBALS["VERBOSE"]){echo __LINE__."::Includes...()<br>\n";}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
if($GLOBALS["VERBOSE"]){echo __LINE__."::Includes...()<br>\n";}
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
if(isset($_GET["logoff"])){unset($_SESSION);}

if(!CheckRights()){logon();exit;}

if(isset($_GET["top-menu"])){top_menu();exit;}
if(isset($_GET["content"])){content();exit;}
if(isset($_GET["events-list"])){events_search();exit;}

startpage();

function logon(){
	if($GLOBALS["VERBOSE"]){echo __LINE__."::LOGON...()<br>\n";}
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$username=$tpl->_ENGINE_parse_body("{username}");
	$password=$tpl->_ENGINE_parse_body("{password}");
	$title=$tpl->_ENGINE_parse_body("{proxy_access_events}");
	$sign=$tpl->_ENGINE_parse_body("{please_sign_in}");
	$hostname=$users->hostname;
	$html="
	<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">
	<html lang=\"en\">
	<head>
	<meta charset=\"utf-8\">
	<title>$hostname:$title</title>
	<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
	<meta name=\"description\" content=\"\">
	<meta name=\"author\" content=\"\">
	<link rel=\"stylesheet\" type=\"text/css\" href=\"/bootstrap/css/bootstrap.css\">
	<link rel=\"stylesheet\" type=\"text/css\" href=\"/bootstrap/css/bootstrap-responsive.css\">
	
	<script type=\"text/javascript\" language=\"javascript\" src=\"/mouse.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/md5.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/XHRConnection.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/float-barr.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/TimersLogs.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/artica_confapply.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/edit.user.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/cookies.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/default.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/ressources/templates/endusers/js/jquery-1.8.0.min.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/ressources/templates/endusers/js/jquery-ui-1.8.23.custom.min.js\"></script>
	<style type=\"text/css\">
	body {
		padding-top: 40px;
		padding-bottom: 40px;
		background-color: #f5f5f5;
	}
	
	.form-signin {
		max-width: 300px;
		padding: 19px 29px 29px;
		margin: 0 auto 20px;
		background-color: #fff;
		border: 1px solid #e5e5e5;
		-webkit-border-radius: 5px;
		-moz-border-radius: 5px;
		border-radius: 5px;
		-webkit-box-shadow: 0 1px 2px rgba(0,0,0,.05);
		-moz-box-shadow: 0 1px 2px rgba(0,0,0,.05);
		box-shadow: 0 1px 2px rgba(0,0,0,.05);
	}
	.form-signin .form-signin-heading,
	.form-signin .checkbox {
		margin-bottom: 10px;
	}
	.form-signin input[type=\"text\"],
	.form-signin input[type=\"password\"] {
		font-size: 16px;
		height: auto;
		margin-bottom: 15px;
		padding: 7px 9px;
	}
	</style>
	<!--[if IE]>
	<link rel=\"stylesheet\" type=\"text/css\" href=\"/bootstrap/css/ie-only.css\" />
	<![endif]-->
	</head>
	<body>
	<input type='hidden' id='LoadAjaxPicture' name=\"LoadAjaxPicture\" value=\"/ressources/templates/endusers/ajax-loader-eu.gif\">
	<div id=\"SetupControl\" style='width:0;height:0'></div>
	<div id=\"dialogS\" style='width:0;height:0'></div>
	<div id=\"dialogT\" style='width:0;height:0'></div>
	<div id=\"dialog0\" style='width:0;height:0'></div>
	<div id=\"dialog1\" style='width:0;height:0'></div>
	<div id=\"dialog2\" style='width:0;height:0'></div>
	<div id=\"dialog3\" style='width:0;height:0'></div>
	<div id=\"dialog4\" style='width:0;height:0'></div>
	<div id=\"dialog5\" style='width:0;height:0'></div>
	<div id=\"dialog6\" style='width:0;height:0'></div>
	<div id=\"YahooUser\" style='width:0;height:0'></div>
	<div id=\"logsWatcher\" style='width:0;height:0'></div>
	<div id=\"WinORG\" style='width:0;height:0'></div>
	<div id=\"WinORG2\" style='width:0;height:0'></div>
	<div id=\"RTMMail\" style='width:0;height:0'></div>
	<div id=\"Browse\" style='width:0;height:0'></div>
	<div id=\"SearchUser\" style='width:0;height:0'></div>
	<div id=\"UnityDiv\" style='width:0;height:0'></div>
	<div id='PopUpInfos' style='position:absolute'></div>
	<div id='find' style='position:absolute'></div>
	<div class=\"info message\" id='AcaNotifyMessInfo'></div>
	<div class=\"error message\" id='AcaNotifyMessError'></div>
	<div class=\"warning message\" id='AcaNotifyMessWarn'></div>
	<div class=\"success message\" id='AcaNotifyMessSuccess'></div>
	
	<div class=\"container\">
	
	<form class=\"form-signin\">
	<h2 class=\"form-signin-heading\">$title</h2>
	<input type=\"text\" class=\"input-block-level\" placeholder=\"$username\" id=\"artica_username\">
	<input type=\"password\" class=\"input-block-level\" placeholder=\"$password\" id=\"artica_password\">
	<button class=\"btn btn-large btn-primary\" type=\"button\" id=\"signin\">$sign</button>
	</form>
	
	</div>
	
	
	<script type=\"text/javascript\">
	
	$('#signin').on('click', function (e) {
	//if(!checkEnter(e)){return;}
	$.getScript('miniadm.logon.php?js=yes&location=$page');
	
	});
	
	
	$('.input-block-level').keypress(function (e) {
	
	if (e.which == 13) {
	$.getScript('miniadm.logon.php?js=yes&location=$page');
	}
	
	});
	
	
	
	function SendLogon(event){
	if(!checkEnter(e)){return;}
	$.getScript('miniadm.logon.php?js=yes');
	
	}
	
	</script>
	
	</body>
	</html>";
	echo $html;
	
}

function CheckRights(){
	if(!isset($_SESSION["uid"])){return false;}
	$users=new usersMenus();
	if(!$users->AsProxyMonitor){return false;}
	return true;
}

function startpage(){

	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$title=$tpl->_ENGINE_parse_body("{proxy_access_events}");
	$sign=$tpl->_ENGINE_parse_body("{please_sign_in}");
	$hostname=$users->hostname;
	
	$html="<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">
	<html lang=\"en\">
	<head>
	<meta charset=\"utf-8\">
	<title>$hostname $title</title>
	
	<link rel=\"stylesheet\" type=\"text/css\" href=\"/bootstrap/css/bootstrap.css\">
	<link rel=\"stylesheet\" type=\"text/css\" href=\"/bootstrap/css/bootstrap-responsive.css\">
	<link rel=\"stylesheet\" type=\"text/css\" href=\"/bootstrap/css/docs.css\">
	<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/rounded.css\" />
	<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/flexigrid.pack.css\" />
	<link rel=\"stylesheet\" href=\"/ressources/templates/endusers/css/jquery2.css\" />
	<link rel=\"stylesheet\" href=\"/js/jqueryFileTree.css\" />
	<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/colorpicker.css\">
	<link rel=\"stylesheet\" type=\"text/css\" href=\"/ressources/templates/endusers/css/canvas.css\">
	
	 
	<script type=\"text/javascript\" language=\"javascript\" src=\"/mouse.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/md5.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/XHRConnection.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/float-barr.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/TimersLogs.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/artica_confapply.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/edit.user.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/cookies.js\"></script>
	
	<script type=\"text/javascript\" language=\"javascript\" src=\"/default.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/ressources/templates/endusers/js/jquery-1.8.0.min.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/ressources/templates/endusers/js/jquery-ui-1.8.23.custom.min.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/flexigrid.pack.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jqueryFileTree.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.easing.1.3.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/thickbox-compressed.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.simplemodal-1.3.3.min.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.jgrowl_minimized.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.cluetip.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.blockUI.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.treeview.min.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.treeview.async.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.tools.min.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.qtip.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery-ui-timepicker-addon.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/ui.selectmenu.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.cookie.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/fileuploader.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/tween-min.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/tiny_mce/tinymce.min.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/colorpicker.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.input-ip-address-control-1.0.min.js\"></script>
	
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/highcharts.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/modules/exporting.js\"></script>
	<link href=\"/js/tiny_mce/themes/advanced/skins/o2k7/ui.css\" rel=\"stylesheet\">
	<link href=\"/js/tiny_mce/themes/advanced/skins/o2k7/ui_silver.css\" rel=\"stylesheet\">
	<link href=\"/js/tiny_mce/plugins/inlinepopups/skins/clearlooks2/window.css\" rel=\"stylesheet\">
	<style type=\"text/css\">
	body {
	padding-top: 60px;
	padding-bottom: 40px;
	}
	.sidebar-nav {
	padding: 9px 0;
	}
	
	td {vertical-align:middle;}
	
	@media (max-width: 1000px) {
	/* Enable use of floated navbar text */
	.navbar-text.pull-right {
	float: none;
	padding-left: 5px;
	padding-right: 5px;
	}
	}
	
	.explain {
	background-color: #FCF8E3;
	border: 1px solid #FBEED5;
	border-radius: 4px 4px 4px 4px;
	margin-bottom: 20px;
	padding: 8px 35px 8px 14px;
	text-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);
	border-color: #BCE8F1;
	color:#37A8BF;
	font-size:14px;
	font-weight:bold;
	background: -moz-linear-gradient(right top, #D9EDF7, white) repeat scroll 0% 0% transparent;
	border-radius: 5px 5px 5px 5px;
	
	
	}
	.explainWarn {
		border: 1px solid #FBEED5;
		border-radius: 4px 4px 4px 4px;
		margin-bottom: 20px;
		padding: 8px 35px 8px 14px;
		text-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);
		background-color: #FCF8E3;
		border-color: #E5DBA9;
		color:#888888;
		font-size:14px;
	}
	
	.MiniAdmParagrapheSwitchIm{
		background-color: #F5F5F5;
		border: 1px solid #D0D0D0;
		padding: 10px;
		border-radius: 4px 4px 4px 4px;
		display: block;
		margin-left:10px;
		margin-top:0px;
		color:#333333;
	
	}
	.MiniAdmParagrapheSwitchIm div{
	color:#333333 !important;
	text-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);
	
	}
	#calendrier {
	width:99%;
	}
	#calendrier table{
	border-collapse:collapse;
	width:100%;
	}
	#calendrier th{
	border-top:1px solid;
	border-bottom:1px solid #000000;
	}
	#calendrier th.mois{
	padding:3px;
	}
	
	#calendrier th.mois a{}
	#calendrier th.semaine{border-bottom:1px solid;}
	#calendrier th.jour{}
	#calendrier td{
		text-align:center;
		border-top:1px solid #000000;
		border-bottom:1px solid #000000;
		font-weight:bold;
	}
	#calendrier td.today{
		background-color:#2682AF;
		color:white;
	}
	#calendrier td.inactif{
		font-style:italic;
		color:#999999;
	}
	#calendrier td.event{
		background-color:#1595D3;
		color:white !important;
	}
	#calendrier_events{
		background-color:#FFFF99;
	}
	#calendrier_events .event{
	margin:2px;
	padding:2px;
	}
	#calendrier td.event a {
	border-bottom: 2px solid;
	color:white !important;
	text-decoration: none;
	}
	
	table.TableRemove{
	padding:0px;
	margin:0px;
	height:auto;
	line-height:normal;
	font-size:11px;
	vertical-align:top;
	 
	}
	.TableRemove td{
	vertical-align:top;
	 
	}
	
	table.TableMarged{
	margin:5px;
	 
	}
	
	td.TableMarged{
	padding-left:5px;
	 
	}
	
	td.TableRemove{
	vertical-align:top;
	padding:0px;
	margin:0px;
	height:auto;
	line-height:normal;
	}
	
	td.BodyContent{
	vertical-align:top;
	}
	td.form{
	vertical-align:top;
	
	}
	
	
	td.legend{
	vertical-align:middle;
	font-size:16px;
	text-align:right;
	text-transform:capitalize;
	white-space:nowrap;
	padding-bottom:10px;
	padding-right:10px;
	color:#888888;
	}
	
	.form table{
	margin: 5px;
	padding: 5px;
	 
	}
	
	td.form a{
	color:black;
	}
	
	
	div .form {
	background: -moz-linear-gradient(center top , #F1F1F1 0px, #FFFFFF 45px) repeat scroll 0 0 transparent;
	border: 1px solid #DDDDDD;
	border-radius: 5px 5px 5px 5px;
	box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.6);
	margin: 5px;
	padding: 5px;
	
	}
	
	table .form {
	background: -moz-linear-gradient(center top , #F1F1F1 0px, #FFFFFF 45px) repeat scroll 0 0 transparent;
	border: 1px solid #DDDDDD;
	border-radius: 5px 5px 5px 5px;
	margin-top: 5px;
	padding: 5px;
	width: 99%;
	}
	
	.form table{
	border-radius: 5px 5px 5px 5px;
	margin-top: 5px;
	padding: 5px;
	width: 99%;
	
	}
	
	
	div .formOver {
	background: -moz-linear-gradient(center top , #FFE3E3 0px, #FFFFFF 45px) repeat scroll 0 0 transparent;
	border: 1px solid #DDDDDD;
	border-radius: 5px 5px 5px 5px;
	box-shadow: 2px 2px 8px #FFA0A1;
	margin: 5px;
	padding: 5px;
	}
	
	</style>
	<!--[if IE]>
	<link rel=\"stylesheet\" type=\"text/css\" href=\"/bootstrap/css/ie-only.css\" />
	<![endif]-->
	</head>
	<body>
	<input type='hidden' id='LoadAjaxPicture' name=\"LoadAjaxPicture\" value=\"/ressources/templates/endusers/ajax-loader-eu.gif\">
	<div id=\"SetupControl\" style='width:0;height:0'></div>
	<div id=\"dialogS\" style='width:0;height:0'></div>
	<div id=\"dialogT\" style='width:0;height:0'></div>
	<div id=\"dialog0\" style='width:0;height:0'></div>
	<div id=\"dialog1\" style='width:0;height:0'></div>
	<div id=\"dialog2\" style='width:0;height:0'></div>
	<div id=\"dialog3\" style='width:0;height:0'></div>
	<div id=\"dialog4\" style='width:0;height:0'></div>
	<div id=\"dialog5\" style='width:0;height:0'></div>
	<div id=\"dialog6\" style='width:0;height:0'></div>
	<div id=\"YahooUser\" style='width:0;height:0'></div>
	<div id=\"logsWatcher\" style='width:0;height:0'></div>
	<div id=\"WinORG\" style='width:0;height:0'></div>
	<div id=\"WinORG2\" style='width:0;height:0'></div>
	<div id=\"RTMMail\" style='width:0;height:0'></div>
	<div id=\"Browse\" style='width:0;height:0'></div>
	<div id=\"SearchUser\" style='width:0;height:0'></div>
	<div id=\"UnityDiv\" style='width:0;height:0'></div>
	<div id='PopUpInfos' style='position:absolute'></div>
	<div id='find' style='position:absolute'></div>
	<div class=\"info message\" id='AcaNotifyMessInfo'></div>
	<div class=\"error message\" id='AcaNotifyMessError'></div>
	<div class=\"warning message\" id='AcaNotifyMessWarn'></div>
	<div class=\"success message\" id='AcaNotifyMessSuccess'></div>
	
	<div class=\"navbar navbar-inverse navbar-fixed-top\">
	<div class=\"navbar-inner\">
	<div class=\"container-fluid\" id='top-menu'>
		
	
	<!--/.nav-collapse -->
	</div>
	</div>
	</div>
	
	<div class=\"container-fluid\">
		<div id=\"globalContainer\">
		<div class=\"row-fluid\"></div><!--/row-->
	</div><!--/span-->
	
	</div><!--/row-->
	
	<hr>
	
	<footer>
	
	</footer>
	
	</div>
	
	
	<script>LoadAjax('globalContainer','$page?content=yes')</script>
	<script>
	initMessagesTop();
	setTimeout('FillTopEnu()',1000);
	
	function FillTopEnu(){
		LoadAjaxTiny('top-menu',\"$page?top-menu=yes\");
	}
	</script>
	<script src=\"bootstrap/js/bootstrap-tab.js\"></script>
	<script src=\"bootstrap/js/bootstrap-tooltip.js\"></script>
	<script src=\"bootstrap/js/bootstrap-button.js\"></script>
	
	</body>
	</html>";	
	
	echo $html;
	
}
function top_menu(){
	$tpl=new templates();
	$ct=new user($_SESSION["uid"]);
	if($ct->DisplayName==null){$ct->DisplayName=$_SESSION["uid"];}
	if($ct->DisplayName=="-100"){$ct->DisplayName='Manager';}
	$page=CurrentPageName();
	
	$html="
	<button type=\"button\" class=\"btn btn-navbar\" data-toggle=\"collapse\" data-target=\".nav-collapse\">
    	<span class=\"icon-bar\"></span>
        <span class=\"icon-bar\"></span>
        <span class=\"icon-bar\"></span>
    </button>
          
     <div class=\"nav-collapse collapse\">
            <p class=\"navbar-text pull-right\">
           &nbsp;&nbsp;&nbsp;<i class='icon-user icon-white'></i> {logged_in_as} <a href=\"#\" class=\"navbar-link\">$ct->DisplayName</a>
            </p>
             <p class=\"navbar-text pull-right\">
             	<a href=\"$page?logoff=yes\" class=\"navbar-link\"><i class='icon-off icon-white'></i> {logoff}</a>
            </p>           
            
            
            <ul class=\"nav\">
			 </ul>
          </div>";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$events=$tpl->_ENGINE_parse_body("{events}");
	$zdate=$tpl->_ENGINE_parse_body("{zDate}");
	$proto=$tpl->_ENGINE_parse_body("{proto}");
	$uri=$tpl->_ENGINE_parse_body("{url}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$title=$tpl->_ENGINE_parse_body("{today}: {realtime_requests} ".date("H")."h");
	$zoom=$tpl->_ENGINE_parse_body("{zoom}");
	$button1="{name: 'Zoom', bclass: 'Search', onpress : ZoomSquidAccessLogs},";
	$stopRefresh=$tpl->javascript_parse_text("{stop_refresh}");
	$logs_container=$tpl->javascript_parse_text("{logs_container}");
	$refresh=$tpl->javascript_parse_text("{refresh}");
	
	$items=$tpl->_ENGINE_parse_body("{items}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$SaveToDisk=$tpl->_ENGINE_parse_body("{SaveToDisk}");
	$addCat=$tpl->_ENGINE_parse_body("{add} {category}");
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$task=$tpl->_ENGINE_parse_body("{task}");
	$new_schedule=$tpl->_ENGINE_parse_body("{new_rotate}");
	$explain=$tpl->_ENGINE_parse_body("{explain_squid_tasks}");
	$run=$tpl->_ENGINE_parse_body("{run}");
	$task=$tpl->_ENGINE_parse_body("{task}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$filename=$tpl->_ENGINE_parse_body("{filename}");
	$empty=$tpl->_ENGINE_parse_body("{empty}");
	$askdelete=$tpl->javascript_parse_text("{empty_store} ?");
	$files=$tpl->_ENGINE_parse_body("{files}");
	$ext=$tpl->_ENGINE_parse_body("{extension}");
	$back_to_events=$tpl->_ENGINE_parse_body("{back_to_events}");
	$Compressedsize=$tpl->_ENGINE_parse_body("{compressed_size}");
	$realsize=$tpl->_ENGINE_parse_body("{realsize}");
	$delete_file=$tpl->javascript_parse_text("{delete_file}");
	$rotate_logs=$tpl->javascript_parse_text("{rotate_logs}");
	$table_size=855;
	$url_row=555;
	$member_row=276;
	$table_height=420;
	$distance_width=230;
	$tableprc="100%";
	$margin="-10";
	$margin_left="-15";
	if(is_numeric($_GET["table-size"])){$table_size=$_GET["table-size"];}
	if(is_numeric($_GET["url-row"])){$url_row=$_GET["url-row"];}
	
	if(isset($_GET["bypopup"])){
		$table_size=1019;
		$url_row=576;
		$member_row=333;
		$distance_width=352;
		$margin=0;
		$margin_left="-5";
		$tableprc="99%";
		$button1="{name: '<strong id=refresh-$t>$stopRefresh</stong>', bclass: 'Reload', onpress : StartStopRefresh$t},";
		$table_height=590;
		$Start="StartRefresh$t()";
	}
	
	$q=new mysql_squid_builder();
	$countContainers=$q->COUNT_ROWS("squid_storelogs");
	if($countContainers>0){
		$button2="{name: '<strong id=container-log-$t>$logs_container</stong>', bclass: 'SSQL', onpress : StartLogsContainer$t},";
		$button_container="{name: '<strong id=container-log-$t>$back_to_events</stong>', bclass: 'SSQL', onpress : StartLogsSquidTable$t},";
		$button_container_delall="{name: '$empty', bclass: 'Delz', onpress : EmptyStore$t},";
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(Compressedsize) as tsize FROM squid_storelogs"));
		$title_table_storage="$logs_container $countContainers $files (".FormatBytes($ligne["tsize"]/1024).")";
	}
	
	
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$error=$tpl->javascript_parse_text("{error}");
	$sitename=$tpl->javascript_parse_text("{sitename}");
	$button3="{name: '<strong id=container-log-$t>$rotate_logs</stong>', bclass: 'Reload', onpress : SquidRotate$t},";
	
	$html="
	<div style='margin:{$margin}px;margin-left:{$margin_left}px' id='$t-main-form'>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:$tableprc'></table>
	</div>
	<input type='hidden' id='refresh$t' value='1'>
	<script>
	var mem$t='';
	function StartLogsSquidTable$t(){
	document.getElementById('$t-main-form').innerHTML='';
	document.getElementById('$t-main-form').innerHTML='<table class=\"flexRT$\" style=\"display: none\" id=\"flexRT$t\" style=\"width:$tableprc\"></table>';
	
	$('#flexRT$t').flexigrid({
	url: '$page?events-list=yes',
	dataType: 'json',
	colModel : [
	{display: '$zdate', name : 'zDate', width :52, sortable : true, align: 'left'},
	{display: '$uri', name : 'events', width : 753, sortable : false, align: 'left'},
	{display: '$size', name : 'QuerySize', width : 80, sortable : true, align: 'left'},
	{display: '$member', name : 'mmeber', width : 324, sortable : false, align: 'left'},
	],
		
	buttons : [
	
	],
		
	
	searchitems : [
	{display: '$sitename', name : 'sitename'},
	{display: '$uri', name : 'uri'},
	{display: '$member', name : 'uid'},
	{display: '$error', name : 'TYPE'},
	{display: '$ipaddr', name : 'CLIENT'},
	],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 1300,
	height: 615,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});
	
	}
	
	function StartLogsContainer$t(){
	document.getElementById('$t-main-form').innerHTML='';
	document.getElementById('$t-main-form').innerHTML='<span id=\"StopRefreshNewTable$t\"></span><table class=\"flexRT$\" style=\"display: none\" id=\"flexRT$t\" style=\"width:$tableprc\"></table>';
	$(document).ready(function(){
	$('#flexRT$t').flexigrid({
	url: '$page?container-list=yes&t=$t',
	dataType: 'json',
	colModel : [
	{display: '$zdate', name : 'filetime', width :162, sortable : true, align: 'left'},
	{display: '&nbsp;', name : 'filetime', width :$distance_width, sortable : true, align: 'left'},
	{display: '$filename', name : 'filename', width :154, sortable : false, align: 'left'},
	{display: '$ext', name : 'fileext', width :33, sortable : false, align: 'center'},
	{display: '$size', name : 'filesize', width : 92, sortable : true, align: 'left'},
	{display: '$Compressedsize', name : 'Compressedsize', width : 92, sortable : true, align: 'left'},
	{display: '$delete', name : 'delete', width : 31, sortable : false, align: 'center'},
	],
		
	buttons : [
	$button_container
	],
		
	
	searchitems : [
	{display: '$sitename', name : 'sitename'},
	{display: '$uri', name : 'uri'},
	{display: '$member', name : 'uid'},
	{display: '$error', name : 'TYPE'},
	{display: '$ipaddr', name : 'CLIENT'},
	],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '$title_table_storage',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $table_size,
	height: $table_height,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});
	});
	}
	
	function SelectGrid2(com, grid) {
	var items = $('.trSelected',grid);
	var id=items[0].id;
	id = id.substring(id.lastIndexOf('row')+3);
	if (com == 'Select') {
	LoadAjax('table-1-selected','$page?familysite-show='+id);
	}
	}
	
	$('table-1-selected').remove();
	$('flex1').remove();
	
	function ZoomSquidAccessLogs(){
	s_PopUp('squid.accesslogs.php?external=yes',1024,768);
	}
	
	function  StartStopRefresh$t(){
	var ratxt='$stopRefresh';
	var rstxt='$refresh';
	var refresh=document.getElementById('refresh$t').value;
	if(refresh==1){
	document.getElementById('refresh$t').value=0;
	document.getElementById('refresh-$t').innerHTML='$refresh';
	}else{
	document.getElementById('refresh$t').value=1;
	document.getElementById('refresh-$t').innerHTML='$stopRefresh';
	$('#flexRT$t').flexReload();
	}
	}
	
	function StartRefresh$t(){
	if(!document.getElementById('flexRT$t')){return;}
			var refresh=document.getElementById('refresh$t').value;
	
			if(refresh==1){
			if(!document.getElementById('StopRefreshNewTable$t')){
			$('#flexRT$t').flexReload();
	}
	}
	
					setTimeout('StartRefresh$t()',5000);
	
	}
	
					function LogsContainer$t(){
					StartLogsContainer$t()
	}
	
					function SquidRotate$t(){
					Loadjs('squid.perf.logrotate.php?tabs=squid_main_svc');
	}
	
			var x_LogsCsvDelte$t = function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue);return;}
			$('#row'+mem$t).remove();
	}
			var x_EmptyStore$t = function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue);return;}
			$('#flexRT$t').flexReload();
	}
			function EmptyStore$t(){
			if(confirm('$askdelete')){
			var XHR = new XHRConnection();
			XHR.appendData('empty-store','yes');
			XHR.sendAndLoad('$page', 'POST',x_EmptyStore$t);
	}
	}
	
	
			function LogsCsvDelte$t(ID,md5){
			mem$t=md5;
			if(confirm('$delete_file :'+ID)){
					var XHR = new XHRConnection();
					XHR.appendData('csv-delete',ID);
					XHR.sendAndLoad('$page', 'POST',x_LogsCsvDelte$t);
	}
	}
					setTimeout('StartLogsSquidTable$t()',800);
			$Start;
	
			</script>
	
	
			";
	
			echo $html;
	
	}
	
	function events_search(){
		$page=CurrentPageName();
		$tpl=new templates();
		$sock=new sockets();
		$q=new mysql_squid_builder();
		$GLOBALS["Q"]=$q;
		$table="squidhour_".date("YmdH");
	
	
		if(isset($_POST['page'])) {$page = $_POST['page'];}
		if(isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
		$searchstring=string_to_flexquery();
	
		if($searchstring<>null){
			$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
			$total = $ligne["TCOUNT"];
	
		}else{
	
			$total = $q->COUNT_ROWS($table);
		}
	
		if(!is_numeric($rp)){$rp=50;}
		$pageStart = ($page-1)*$rp;
		if($pageStart<0){$pageStart=0;}
		$limitSql = "LIMIT $pageStart, $rp";
		if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	
		$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";
		$results = $q->QUERY_SQL($sql);
		if(!$q->ok){json_error_show($q->mysql_error);}
	
		$data = array();
		$data['page'] = $page;
		$data['total'] = $total;
		$data['rows'] = array();
		$today=date("Y-m-d");
		$tcp=new IP();
	
		$cachedT=$tpl->_ENGINE_parse_body("{cached}");
		$c=0;
		while ($ligne = mysql_fetch_assoc($results)) {
			$color="black";
			$return_code_text=null;
			$ff=array();
			$color="black";
			$uri=$ligne["uri"];
			$date=$ligne["zDate"];
			$mac=$ligne["MAC"];
			$ip=$ligne["CLIENT"];
			$user=$ligne["uid"];
			$dom=$ligne["sitename"];
			$cached=$ligne["cached"];
			$return_code=$ligne["TYPE"];
			$size=$ligne["QuerySize"];
			$ident=array();
			$md=md5(serialize($ligne));
			$today=date("Y-m-d");
			$date=str_replace($today, "", $date);
			$ident[]="<spanstyle='color:$color'>$ip</a>";
			$spanON="<span style='color:$color'>";
			$spanOFF="</span>";
			$cached_text=null;
			if($cached==1){$cached_text=" - $cachedT";}
			$size=FormatBytes($size/1024);
			if($return_code=="Service Unavailable"){$color="#BA0000";}
			if($return_code=="Bad Gateway"){$color="#BA0000";}
			$return_code_text="<div style='color:$color;font-size:11px'><i>&laquo;$return_code&raquo;$cached_text</i></div>";
	
			if($user<>null){
				$GLOBALS["IPUSERS"][$ip]=$user;
			}else{
				if(isset($GLOBALS["IPUSERS"][$ip])){
	
					$ident[]="<i>{$GLOBALS["IPUSERS"][$ip]}</i>";
				}
			}
	
			if($user<>null){
				if($tcp->isValid($user)){
					$ident[]="<span
					style='color:$color'>$user</a>";
				}else{
					$ident[]="<span
					style='color:$color'>$user</a>";
				}
			}
	
			if($mac<>null){
				$ident[]="<span
				style='color:$color'>$mac</a>";
	
			}
	
			$identities=@implode("&nbsp;|&nbsp;", $ident);
	
			$data['rows'][] = array(
					'id' => $md,
					'cell' => array(
							"$spanON$date$spanOFF",
							"$spanON$uri$return_code_text$spanOFF",
							"$spanON$size$spanOFF",
							"$spanON$identities$spanOFF"
					)
			);
	
	
				
	
		}
	
		echo json_encode($data);
	}
	
function GetDomainFromURl($myurl){
	$raw_url = parse_url($myurl);
	$domain_only =str_replace ('www.','', $raw_url);
	return $domain_only['host'];
}
	