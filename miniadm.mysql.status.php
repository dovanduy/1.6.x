<?php
session_start();
$_SESSION["MINIADM"]=true;
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");


if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!isset($_SESSION["uid"])){
	writelogs("Redirecto to miniadm.logon.php...","NULL",__FILE__,__LINE__);
	header("location:miniadm.logon.php");}
BuildSessionAuth();
if($_SESSION["uid"]=="-100"){
	writelogs("Redirecto to location:admin.index.php...","NULL",__FILE__,__LINE__);
	header("location:admin.index.php");
	die();
	
}
if(isset($_POST["CleanMySQLLogs"])){CleanMySQLLogs();exit;}
if(isset($_GET["table"])){table();exit;}


$page=CurrentPageName();
$t=time();
echo "<div id='$t'></div>
<script>LoadAjax('$t','$page?table=$t');</script>";
return;



function table(){
$tpl=new templates();
$page=CurrentPageName();
$cachefile="/usr/share/artica-postfix/ressources/logs/web/MYSQLDB_STATUS";
$tbl=unserialize(base64_decode(@file_get_contents($cachefile)));
while (list ($dbname, $array) = each ($tbl) ){
	$t=$_GET["table"];
	$size=FormatBytes($array["size"]/1024);
	$DEV=$array["INFO"]["DEV"];
	$TOTAL=FormatBytes($array["INFO"]["TOT"]/1024);
	$USED=FormatBytes($array["INFO"]["USED"]/1024);
	$AIV=FormatBytes($array["INFO"]["AIV"]/1024);
	$POURC=$array["INFO"]["POURC"];
	$MOUNTED=$array["INFO"]["MOUNT"];
	$CleanMySQLLogs=$tpl->javascript_parse_text("{CleanMySQLLogs}");
	$dbnameTXT=$tpl->_ENGINE_parse_body("{{$dbname}}");
	$action=null;
	if($dbname=="APP_MYSQL_ARTICA"){
		$action=imgtootltip("database-disconnect-64.png",null,"CleanMySQLLogs()");
	}
	
	$tr[]="
	<tr>
		<td width=1% nowrap><img src='img/database-connect-settings-64.png'></td>
		<td style='font-size:18px'>$dbnameTXT</td>
		<td style='font-size:18px'>$size</td>
		<td style='font-size:18px'>$MOUNTED</td>	
		<td style='font-size:18px'>$USED/$TOTAL ($POURC%)</td>
		<td style='font-size:18px' width=1% nowrap>$action</td>
	</tr>
	";		
}			
	echo $tpl->_ENGINE_parse_body("
	
			<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th colspan=2>{service}</th>
					<th >{size}</th>
					<th >{disk}</th>
					<th >{use}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("", $tr)."</tbody></table>
					
<script>

var xCleanMySQLLogs=function (obj) {
	var results=obj.responseText;
	if(results.length>10){alert(results);}
	LoadAjax('$t','$page?table=$t');
	}

	function CleanMySQLLogs(){
		if(!confirm('$CleanMySQLLogs')){return;}
		var XHR = new XHRConnection();
		XHR.appendData('CleanMySQLLogs','yes');
		XHR.sendAndLoad('$page', 'POST',xCleanMySQLLogs);		
		
		
					
	}
</script>
";	
}	
	

function CleanMySQLLogs(){
	$sock=new sockets();
	$sock->getFrameWork("mysql.php?clean=yes");
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("{mysql_task_background}");
}



