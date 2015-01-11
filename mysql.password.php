<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.httpd.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.ini.inc');
	if(isset($_GET["username"])){ChangeMysqlPassword();exit;}
	if(isset($_GET["viewlogs"])){viewlogs();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["mysql-connect-status"])){MySQLConnectStatus();exit;}
	js();
	
function js(){
	
$usersmenus=new usersMenus();
if($usersmenus->AsArticaAdministrator==false){echo "alert('no privileges');";exit;}	
$t=time();

$tpl=new templates();
$title=$tpl->_ENGINE_parse_body('{MYSQL_PASSWORD_USER}','mysql.index.php');
if(isset($_GET["root"])){
	$etroot="&root=yes";
	$title=$tpl->_ENGINE_parse_body('{chgroot_password}','mysql.index.php');
	
}
$page=CurrentPageName();
$prefix=str_replace('.','_',$page);
$html="
	function {$prefix}STart(){
		YahooWin3(500,'$page?popup=yes$etroot&t=$t','$title');
		}
		
var x_ChangeMysqlPassword= function (obj) {
		var results=obj.responseText;
		if(results.length>0){document.getElementById('mysqldivForLogs').innerHTML=results;return}
		LoadAjax('mysqldivForLogs','$page?viewlogs=yes');
	}	

	
	function ChangeMysqlPassword(){
		var username=document.getElementById('username-$t').value;
		var password=encodeURIComponent(document.getElementById('password-$t').value);
		var XHR = new XHRConnection();	
		XHR.appendData('username',username);
		XHR.appendData('password',password);
		AnimateDiv('mysqldivForLogs');
		XHR.sendAndLoad('$page', 'GET',x_ChangeMysqlPassword);			
	
	}
	
	
	
	{$prefix}STart();
	
";
SET_CACHED(__FILE__,__FUNCTION__,null,$html);	
echo $html;
	
	
}

function popup(){
	$t=$_GET["t"];
	$title="MYSQL_PASSWORD_USER_TEXT";
	$username=Field_text("username-$t",null,"font-size:14px;padding:3px");
	$page=CurrentPageName();
	
	if(isset($_GET["root"])){
		
		$title="change_root_password_text";
		$username="<input type='hidden' id='username-$t' name='username-$t' value='root'>
		<span style='font-size:14px;font-weight:bold'>root</span>";
	}
	$html="
	
	<table style='width:100%'>
	<tr>
		<td valign='top'>
			<img src='img/change-mysql-128.png'>
		</td>
		<td valign='top'><div class=text-info style='font-size:14px'>{{$title}}</div>
			<table style='width:99.5%' class=form>
				<tr>
					<td valign='top' class=legend nowrap style='font-size:14px'>{username}:</td>
					<td valign='top'>$username</td>
				</tr>
				<tr>
					<td valign='top' class=legend style='font-size:14px'>{password}:</td>
					<td valign='top'>". Field_password("password-$t",null,"font-size:14px;padding:3px;width:120px")."</td>
				</tr>
				<tr>
					<td colspan=2 align='right'>
						<hr>". button("{apply}","ChangeMysqlPassword()",16)."
						
					</td>
				</tr>
			</table>		
		</td>
	</tr>
	</table>
	<div id='mysql-connect-status' style='min-height:50px'></div>
	
	
	<div id='mysqldivForLogs' style='height:250px;overflow:auto;min-height:250px'></div>
	
	<script>
		function MySQLConnectStatus(){
			if(YahooWin3Open()){
				LoadAjaxTiny('mysql-connect-status','$page?mysql-connect-status=yes');
			}
		}
		
		setTimeout('MySQLConnectStatus()', 3000);
	</script>
	
	";
	
	$tpl=new templates();
	$html=$tpl->_ENGINE_parse_body($html,"mysql.index.php");
	SET_CACHED(__FILE__,__FUNCTION__,null,$html);
	echo $html;
	
}

function ChangeMysqlPassword(){
	$tpl=new templates();
	$localserver=false;
	
	$users=new usersMenus();
	if(!$usersmenus->AsArticaAdministrator==false){echo $tpl->_ENGINE_parse_body('<strong style=color:red>{ERROR_NO_PRIVS}</strong>');exit;	}
	
	$q=new mysql();
	if($q->mysql_server=="localhost"){$localserver=true;}
	if($q->mysql_server=="127.0.0.1"){$localserver=true;}
	if(!$localserver){echo $tpl->_ENGINE_parse_body('<strong style=color:red>{ERR_MYSQL_IS_REMOTE}</strong>');exit;}

	
	if($_GET["username"]==null){echo $tpl->_ENGINE_parse_body('<strong style=color:red>{ERR_NO_USERNAME}</strong>');exit;}
	if($_GET["password"]==null){echo $tpl->_ENGINE_parse_body('<strong style=color:red>{ERR_NO_PASS}</strong>');exit;}
	$tpl=new templates();
	$sock=new sockets();
	$_GET["password"]=url_decode_special_tool($_GET["password"]);
	$password=base64_encode($_GET["password"]);
	$sock->getFrameWork("cmd.php?ChangeMysqlLocalRoot={$_GET["username"]}&password=$password&encoded=yes");
}

function viewlogs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	if(!is_file("ressources/logs/ChangeMysqlLocalRoot")){echo $tpl->_ENGINE_parse_body('{waiting}...');exit;}
	$tbl=explode("\n",@file_get_contents("ressources/logs/ChangeMysqlLocalRoot"));
	echo "<div style='background-color:white'>";
	while (list ($num, $ligne) = each ($tbl) ){
		if($ligne==null){continue;}
		echo "<div><code style='font-size:11px'>".htmlspecialchars($tpl->_ENGINE_parse_body($ligne))."</code></div>\n";
		
	}
	
	echo "</div>
	<script>
		function RefreshLogDiv$t(){
			if(YahooWin3Open()){
				LoadAjax('mysqldivForLogs','$page?viewlogs=yes');
			}
		}
	
		setTimeout('RefreshLogDiv$t()', 5000);
	</script>
	
	";
	
}

function MySQLConnectStatus(){
	$sock=new sockets();
	$page=CurrentPageName();
	$MySQLInfos=unserialize(base64_decode($sock->getFrameWork("services.php?mysqlinfos=yes")));
	$username=$MySQLInfos["username"];
	$password=$MySQLInfos["password"];
	$q=new mysql();
	if(($q->mysql_server=="localhost") OR ($q->mysql_server=="127.0.0.1")){
			$serverLog=":/var/run/mysqld/mysqld.sock";
			if($password<>null){
				$bd=@mysql_connect(":/var/run/mysqld/mysqld.sock",$username,$password);
			}else{
				ini_set("mysql.default_password", null);
				$bd=@mysql_connect(":/var/run/mysqld/mysqld.sock",$username,null);
			}
		}else{
			$serverLog="$q->mysql_server:$q->mysql_port";
			if($password<>null){
				$bd=@mysql_connect("$q->mysql_server:$q->mysql_port",$username,$password);
			}else{
				ini_set("mysql.default_password", null);
				$bd=@mysql_connect("$q->mysql_server:$q->mysql_port",$username,null);
			}
		}
		
		
		
		
		
		
		if(!$bd){
			$des=mysql_error();
			$errnum=mysql_errno();	
			echo "<table style='width:99%' class=form>
			<tr>
				<td width=1%><img src='img/error-64.png'></td>
				<td><strong style='font-size:14px'>Err: $errnum<br>$des</strong></td>
				</tr>
			</table>";
				
		}
		
		echo "<script>setTimeout('MySQLConnectStatus()', 5000);</script>";
			
	
}


?>