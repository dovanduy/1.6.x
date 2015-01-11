<?php
include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
include_once(dirname(__FILE__) . "/ressources/class.mysql.inc");

	$user=new usersMenus();
	if($user->AsSystemAdministrator==false){header('location:users.index.php');exit();}



if(isset($_GET["mysqlstatus"])){echo mysql_status();exit;}
if(isset($_GET["main"])){echo mysql_main_switch();exit;}
if(isset($_GET["mysqlenable"])){echo mysql_enable();exit;}
if(isset($_GET["changemysqlenable"])){mysql_action_enable_change();exit;}
if(isset($_POST["mysqlroot"])){testsMysql();exit;}
if($_GET["script"]=="mysql_enabled"){echo js_mysql_enabled();exit;}
if($_GET["script"]=="mysql_save_account"){echo js_mysql_save_account();exit;}
if(isset($_GET["databases_status"])){Database_Status();exit;}
if(isset($_GET["repair-databases"])){repair_database();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["mysql-settings-popup"])){mysql_settings_js();exit;}
if(isset($_GET["mysql-settings-popup-show"])){echo mysql_settings(true);exit;}
if(isset($_POST["PHPDefaultMysqlserver"])){mysql_php_save();exit;}
	js();
	
	
function js(){

	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{APP_MYSQL_ARTICA}");
	$prefix=str_replace(".","_",$page);
	
	if(isset($_GET["account"])){
		$ajax="LoadAjax('main_mysql_config','mysql.index.php?main=settings&hostname=')";
	}else{
		$ajax="LoadAjax('main_mysql_config','mysql.index.php?main=&hostname=')";
	}
	
	
	$html="
var {$prefix}timerID  = null;
var {$prefix}timerID1  = null;
var {$prefix}tant=0;
var {$prefix}reste=0;

function {$prefix}demarre(){
{$prefix}tant = {$prefix}tant+1;
{$prefix}reste=5-{$prefix}tant;
	if ({$prefix}tant < 5 ) {                           
{$prefix}timerID = setTimeout(\"{$prefix}demarre()\",3000);
      } else {
		{$prefix}tant = 0;
		{$prefix}ChargeLogs();
		{$prefix}demarre();                                //la boucle demarre !
   }
}

function mystatus(){
	if(document.getElementById('mystatus')){
		LoadAjax('mystatus','$page?databases_status=yes');
	}
}

function {$prefix}ChargeLogs(){
	mystatus();
	LoadAjax('mysql_status','$page?mysqlstatus=yes');
	
	}
		
function mystatus(){
	if(document.getElementById('mystatus')){
		LoadAjax('mystatus','$page?databases_status=yes');
	}
}
	
	

	function {$prefix}SartMysql(){	
		YahooWin3(700,'$page?popup=yes','$title');
		setTimeout('Loadall()',900);
	}
	
	function Loadall(){
		{$prefix}demarre();
		{$prefix}ChargeLogs();
		mystatus();
		$ajax
		LoadAjax('mysql_status','$page?mysqlstatus=yes');
		LoadAjax('mysqlenable','$page?mysqlenable=yes');	
		}
	
	{$prefix}SartMysql()
	";
	
	echo $html;
}

function popup(){
	$page=CurrentPageName();
$html="
	<span id='scripts'><script type=\"text/javascript\" src=\"$page?script=load_functions\"></script></span>
	<table style='width:100%'>
	<tr>
	<td width=1% valign='top'>".RoundedLightWhite("<img src='img/bg_mysql.png'style='margin-right:30px;margin-bottom:5px'>")."</td>
	<td valign='top'>
		<div id='mysql_status'></div>
	</td>
	</tr>
	<tr>
		<td colspan=2 valign='top'>
			<br>
			".RoundedLightWhite("
			<table style='width:100%'>	
			<tr>
			<td valign='top'>
				<div id='main_mysql_config'></div>
			</td>
			<td valign='top'>
				<div id='mysqlenable'></div>
			</td>
			</tr>
			</table>")."
			
		</td>
	</tr>
	</table>
"	;

$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);	
}
	


function mysql_tabs(){
	$page=CurrentPageName();
	$users=new usersMenus();
	$array["settings"]='{settings}';
	$array["performances"]='{performances}';
	
	while (list ($num, $ligne) = each ($array) ){
		if($_GET["main"]==$num){$class="id=tab_current";}else{$class=null;}
		$html=$html . "<li><a href=\"javascript:LoadAjax('main_mysql_config','$page?main=$num&hostname={$_GET["hostname"]}')\" $class>$ligne</a></li>\n";
			
		}
	$tpl=new templates();
	return $tpl->_ENGINE_parse_body("<div id=tablist>$html</div>");		
}



function mysql_status(){
	$tpl=new templates();
	$ini=new Bs_IniHandler();
	$sock=new sockets();
	$ini->loadString($sock->getfile('mysqlstatus'));
	$status=DAEMON_STATUS_ROUND("ARTICA_MYSQL",$ini,null);
	echo $tpl->_ENGINE_parse_body($status);
	}
function mysql_main_switch(){
	$tab=mysql_tabs();
	
	switch ($_GET["main"]) {
		case "settings":echo $tab.mysql_settings();break;
		case "performances":echo $tab.mysql_performances();break;
	
		default:echo $tab.mysql_performances();break;
	}
	
	
}

function mysql_performances(){
	$html="
	<table style='width:100%'>
		<tr>
			<td class=legend>
				{change_mysql_power}:
			</td>
			<td>
				<input type=button value='{mysql_performance_level}&nbsp;&raquo;' OnClick=\"javascript:YahooWin(400,'artica.performances.php?main_config_mysql=yes');\">
			</td>
			</tr>
			<tr>
			<td class=legend>
				{mysql_repair}:
			</td>			
			<td>
				<input type=button value='{mysql_repair}&nbsp;&raquo;' OnClick=\"javascript:YahooWin(400,'mysql.index.php?repair-databases=yes','{waiting}...');\">
			</td>			
		</tr>
	</table>
	<div id='mystatus'></div>
	";
	
$tpl=new templates();
return $tpl->_ENGINE_parse_body($html);
	
}

function mysql_enable(){
$artica=new artica_general();
$page=CurrentPageName();
$icon=Paragraphe_switch_img('{enable_mysql}',"{enable_mysql_text}","enable_mysql",$artica->EnableMysqlFeatures);
$html="

<div>$icon
	<div style='text-align:right;margin-top:5px'><input type='button' OnClick=\"javascript:Loadjs('$page?script=mysql_enabled')\" value='{apply}&nbsp;&raquo;'></div>
</div>

";
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);
}
function js_mysql_enabled(){
	$page=CurrentPageName();
	$html="var enable=document.getElementById('enable_mysql').value;
	YahooWin(400,'$page?changemysqlenable='+enable);
	LoadAjax('mysqlenable','$page?mysqlenable=yes');
	";
	echo $html;
}

function mysql_action_enable_change(){
	$enable=$_GET["changemysqlenable"];
	$artica=new artica_general();
	$artica->EnableMysqlFeatures=$enable;
	
	if($enable==0){
		
		$main=new main_cf();
		$main->save_conf();
		$main->save_conf_to_server();
	}
	
	$artica->SaveMysqlSettings();
	$sock=new sockets();
	$datas=$sock->getfile('restartmysql');
	$datas=htmlentities($datas);
	$tbl=explode("\n",$datas);
	$datas='';
	while (list ($num, $val) = each ($tbl) ){
		$datas=$datas."<div>$val</div>";
		
	}
	echo "<div style='width:100%;height:500px;overflow:auto'>$datas</div>";
	
	
}

function mysql_settings_js(){
	
	$user=new usersMenus();
	if(!$user->AsSystemAdministrator) {
		$tpl=new templates();
		$text=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
		$text=replace_accents(html_entity_decode($text));
		echo "alert('$text');";
		exit;
	}	
	
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{mysql_account}',"artica.settings.php");
	$prefix=str_replace(".","_",$page);	
	
	$page=CurrentPageName();
	$js="
	function {$prefix}LoadMainRI(){
		YahooWin3('550','$page?mysql-settings-popup-show=yes','$title');
		}	
		
		
	{$prefix}LoadMainRI();		
	
	
	";
	echo $js;
	
}


function mysql_settings($notitle=false){
	$page=CurrentPageName();
	$user=new usersMenus();
	if(!$user->AsArticaAdministrator){
		if(!$user->AsSystemAdministrator){
			$tpl=new templates();
			$text=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS} !AsSystemAdministrator');
			$text=replace_accents(html_entity_decode($text));
			echo "alert('$text');";
			exit;
	}}
	$mysql=new mysql();
	$rootm=$mysql->mysql_admin;
	$pwd=$mysql->mysql_password;
	$servername=$mysql->mysql_server;
		
		$sock=new sockets();
		$UseSamePHPMysqlCredentials=$sock->GET_INFO("UseSamePHPMysqlCredentials");
		if(!is_numeric($UseSamePHPMysqlCredentials)){$UseSamePHPMysqlCredentials=1;}
		
		$t=time();
	$html="
	<div id='animate-$t'></div>
	<table style='width:99%' class=form>
	
		<tr>
			<td align='right' nowrap class=legend style='font-size:16px'>{mysqlserver}:</strong></td>
			<td align='left'>" . Field_text('mysqlserver',$servername,'width:85%;padding:3px;font-size:16px',null,null,'')."</td>
		</tr>	
		<tr>
			<td align='right' nowrap class=legend style='font-size:16px'>{mysqlroot}:</strong></td>
			<td align='left'>" . Field_text('mysqlroot',$rootm,'width:85%;padding:3px;font-size:16px',null,null,'{mysqlroot_text}')."</td>
		</tr>
		<tr>
			<td align='right' nowrap class=legend style='font-size:16px'>{mysqlpass}:</strong></td>
			<td align='left'>" . Field_password("mysqlpass",$pwd,'width:85%;padding:3px;font-size:16px')."</td>
		</tr>
		<tr>
			<td colspan=2 align='right'>
					
				<hr>". button("{apply}","Loadjs('$page?script=mysql_save_account&animate=animate-$t')",18)."
			</td>
		</tr>	
	</table>
	
	<div class=text-info style='font-size:14px'>{mysqldefault_php_text}</div>
<table style='width:99%' class=form>	
		<tr>
			<td align='right' nowrap class=legend>{UseSamePHPMysqlCredentials}:</strong></td>
			<td align='left'>" . Field_checkbox('UseSamePHPMysqlCredentials',1,$UseSamePHPMysqlCredentials,"MysqlDefaultCredCheck()")."</td>
		</tr>		
		<tr>
			<td align='right' nowrap class=legend>{mysqlserver}:</strong></td>
			<td align='left'>" . Field_text('PHPDefaultMysqlserver',$PHPDefaultMysqlserver,'width:110px;padding:3px;font-size:14px',null,null,'')."</td>
		</tr>
		<tr>
			<td align='right' nowrap class=legend>{listen_port}:</strong></td>
			<td align='left'>" . Field_text('PHPDefaultMysqlserverPort',$PHPDefaultMysqlserverPort,'width:110px;padding:3px;font-size:14px',null,null,'')."</td>
		</tr>				
		<tr>
			<td align='right' nowrap class=legend>{mysqlroot}:</strong></td>
			<td align='left'>" . Field_text('PHPDefaultMysqlRoot',$PHPDefaultMysqlRoot,'width:110px;padding:3px;font-size:14px',null,null)."</td>
		</tr>
		<tr>
			<td align='right' nowrap class=legend>{mysqlpass}:</strong></td>
			<td align='left'>" . Field_password("PHPDefaultMysqlPass",$PHPDefaultMysqlPass,"width:110px;padding:3px;font-size:14px")."</td>
		</tr>
		<tr>
			<td colspan=2 align='right'>
				<hr>". button("{apply}","SavePhpCredentials()")."
			</td>
		</tr>	
	</table>
	
	<script>
	function MysqlDefaultCredCheck(){
		document.getElementById('PHPDefaultMysqlserver').disabled=false;
		document.getElementById('PHPDefaultMysqlserverPort').disabled=false;
		document.getElementById('PHPDefaultMysqlRoot').disabled=false;
		document.getElementById('PHPDefaultMysqlPass').disabled=false;
	
	
		if(document.getElementById('UseSamePHPMysqlCredentials').checked){
			document.getElementById('PHPDefaultMysqlserver').disabled=true;
			document.getElementById('PHPDefaultMysqlserverPort').disabled=true;
			document.getElementById('PHPDefaultMysqlRoot').disabled=true;
			document.getElementById('PHPDefaultMysqlPass').disabled=true;		
		
		}
	}
	
var x_SavePhpCredentials=function(obj){
		Loadjs('$page?mysql-settings-popup=yes');

      }	      
		
	function SavePhpCredentials(){
			
			var XHR = new XHRConnection();
			if(document.getElementById('UseSamePHPMysqlCredentials').checked){XHR.appendData('UseSamePHPMysqlCredentials',1);}else{XHR.appendData('UseSamePHPMysqlCredentials',0);}
    		XHR.appendData('PHPDefaultMysqlserver',document.getElementById('PHPDefaultMysqlserver').value);
    		XHR.appendData('PHPDefaultMysqlserverPort',document.getElementById('PHPDefaultMysqlserverPort').value);
    		XHR.appendData('PHPDefaultMysqlRoot',document.getElementById('PHPDefaultMysqlRoot').value);
    		XHR.appendData('PHPDefaultMysqlPass',document.getElementById('PHPDefaultMysqlPass').value);
    		XHR.sendAndLoad('$page','POST',x_SavePhpCredentials);
		
		
	}	

	
	
	MysqlDefaultCredCheck();
	</script>
	
	";	
	
$tpl=new templates();
return $tpl->_ENGINE_parse_body($html,"artica.settings.php");	
}

function mysql_php_save(){
	$sock=new sockets();
	$sock->SET_INFO("UseSamePHPMysqlCredentials", $_POST["UseSamePHPMysqlCredentials"]);
	$sock->SET_INFO("PHPDefaultMysqlserver", $_POST["PHPDefaultMysqlserver"]);
	$sock->SET_INFO("PHPDefaultMysqlserverPort", $_POST["PHPDefaultMysqlserverPort"]);
	$sock->SET_INFO("PHPDefaultMysqlRoot", $_POST["PHPDefaultMysqlRoot"]);
	$sock->SET_INFO("PHPDefaultMysqlPass", $_POST["PHPDefaultMysqlPass"]);
	$sock->getFrameWork("services.php?php-ini-set=yes");
	
}

function js_mysql_save_account(){
	$page=CurrentPageName();
	$t=time();
	$animate=$_GET["animate-$t"];
	$html="
			
	var x_TestsMySQL$t= function (obj) {
		if(document.getElementById('$animate')){document.getElementById('$animate').innerHTML='';}
		var results=obj.responseText;
		if(results.length>1){alert(results);}	
		
		}	
			
	function TestsMySQL$t(){
		var mysqlserver=encodeURIComponent(document.getElementById('mysqlserver').value);
		var mysqlroot=encodeURIComponent(document.getElementById('mysqlroot').value);
		var mysqlpass=encodeURIComponent(document.getElementById('mysqlpass').value);		
	
		var XHR = new XHRConnection();
		XHR.appendData('mysqlserver',mysqlserver);
		XHR.appendData('mysqlroot',mysqlroot);
		XHR.appendData('mysqlpass',mysqlpass);
		AnimateDiv('$animate');
		XHR.sendAndLoad('$page', 'POST',x_TestsMySQL$t);	
	}
	
	TestsMySQL$t();
	
	";
	echo $html;
}


function testsMysql(){
	$sock=new sockets();
	while (list ($num, $ligne) = each ($_POST) ){
		
		$_POST[$num]=url_decode_special_tool($ligne);
	}
	
	
	$method="";
	writelogs("testing {$_POST["mysqlserver"]}:3306 with user {$_POST["mysqlroot"]} and password \"{$_POST["mysqlpass"]}\"",__FUNCTION__,__FILE__,__LINE__);
	
	//$bd=@mysql_connect("{$_GET["mysqlserver"]}:3306",$_GET["mysql_account"],$_GET["mysqlpass"]);
	
		if(($_POST["mysqlserver"]=="localhost") OR ($_POST["mysqlserver"]=="127.0.0.1")){
			$method=":/var/run/mysqld/mysqld.sock";
			$bd=@mysql_connect(":/var/run/mysqld/mysqld.sock",$_POST["mysqlroot"],$_POST["mysqlpass"]);
		}else{
			$method="{$_GET["mysqlserver"]}:3306";
			$bd=@mysql_connect("{$_POST["mysqlserver"]}:3306",$_POST["mysqlroot"],$_POST["mysqlpass"]);
		}	
	
	
	
	
	$database=md5('Y-m-d H:i:s');
	$tpl=new templates();
	if(!$bd){
			$errnum=mysql_errno();
    		$des=mysql_error();
    		echo "ERR N.$errnum\n$des\n$method";
    		exit;
			}
			
	$results=@mysql_query("CREATE DATABASE $database");
	if(!$bd){
			$errnum=mysql_errno();
    		$des=mysql_error();
			echo "CREATE DATABASE $database\nERR N.$errnum\n$des\n$method";
    		exit;
	}
	$results=@mysql_query("DROP DATABASE $database");
	
	$arrayMysqlinfos=array("USER"=>$_POST["mysqlroot"],"PASSWORD"=>$_POST["mysqlpass"],"SERVER"=>$_POST["mysqlserver"]);
	$cmd=base64_encode(serialize($arrayMysqlinfos));
	$sock->getFrameWork("cmd.php?change-mysql-params=$cmd");	
	
	
	unset($_SESSION["MYSQL_PARAMETERS"]);
	unset($GLOBALS["MYSQL_PARAMETERS"]);
	$mysql=new mysql();
	$mysql->mysql_server=$_POST["mysqlserver"];
	$mysql->mysql_admin=$_POST["mysql_account"];
	$mysql->mysql_password=$_POST["mysqlpass"];
	$mysql->hostname=$_POST["mysqlserver"];
	$mysql->BuildTables();
	
	
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{success}: {apply} {mysql_account}");

	
	
	
	
}


function Database_Status(){
	$my=new mysql();
	
	$artica_back=$my->DATABASE_STATUS("artica_backup");
	$artica_events=$my->DATABASE_STATUS("artica_events");
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("<H5>{databases_status}</H5>$artica_back.$artica_events");
	
	
}

function repair_database(){
	$sock=new sockets();
	$datas=$sock->getfile('services.php?mysql-repair-dabatase=yes');
	$tb=explode("\n",$datas);
	
while (list ($num, $ligne) = each ($tb) ){
			if(trim($ligne)==null){continue;}
			$ligne=htmlentities($ligne);
			$dd=$dd."<div><strong style='font-size:12px;color:black'><code>$ligne</code></strong></div>";
		}
	$dd=RoundedLightWhite($dd);
	$html="
	<H1>{mysql_repair}</H1>
	<div style='width:100%;height:300px;overflow:auto'>$dd</div>
	
	
	";
	
	
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);	
	
	
	
}


?>
