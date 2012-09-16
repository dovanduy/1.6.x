<?php
include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
include_once(dirname(__FILE__) . "/ressources/class.mysql.inc");

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["statusof"])){statusof();exit;}
if(isset($_GET["logsof"])){logsof();exit;}
if(isset($_GET["launch"])){install_app();exit;}



	$user=new usersMenus();
	if(!$user->AsSystemAdministrator){
		$tpl=new templates();
		echo $tpl->javascript_parse_text("alert('{ERROR_NO_PRIVS}');");
		exit();
	}


js();
	
	

function js(){
	
	
	$page=CurrentPageName();
	$prefix=str_replace('.','_',$page);
	
	$product=$_GET["product"];
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{{$product}}");
	$WANT_INSTALL=$tpl->javascript_parse_text("{WANT_INSTALL}");
	
if(isset($_GET["start-install"])){
		$jsplus="{$prefix}LaunchInstall();";
	}	
	
$html= "
var {$prefix}timerID  = null;
var {$prefix}tant=0;
var {$prefix}reste=0;
var {$prefix}timeout=0;
	function {$prefix}Load(){
		YahooWin2('550','$page?popup=yes&product=$product','$title');
		setTimeout(\"{$prefix}Launch()\",800);
	}

	function {$prefix}Launch(){
		{$prefix}timeout={$prefix}timeout+1;
		if({$prefix}timeout>10){
			alert('timeout!');
			return;
		}
		
		if(!document.getElementById('progression_install')){
			setTimeout(\"{$prefix}Launch()\",800);
			return;
		}
		
		{$prefix}timeout=0;
		{$prefix}ChargeLogs();
		$jsplus
		setTimeout(\"{$prefix}demarre()\",1500);
	}

	function {$prefix}demarre(){
		if(!YahooWin2Open()){return false;}
		if(!document.getElementById('install_textlogs')){return;}
		{$prefix}tant = {$prefix}tant+1;
		if ({$prefix}tant <9 ) {                           
			setTimeout(\"{$prefix}demarre()\",300);
			return;
		}
		{$prefix}tant = 0;
		{$prefix}ChargeLogs();
		{$prefix}demarre();
	}
	
	
var x_{$prefix}ChargeLogs2=function (obj) {
		var tempvalue=obj.responseText;
		document.getElementById('install_textlogs').innerHTML=tempvalue;
	}	
	
var x_{$prefix}ChargeLogs=function (obj) {
		var tempvalue=obj.responseText;
		document.getElementById('progression_install').innerHTML=tempvalue;
		var XHR = new XHRConnection();
		XHR.appendData('logsof','$product');
		XHR.sendAndLoad('$page', 'GET',x_{$prefix}ChargeLogs2);			
		
	}	
	
var x_{$prefix}LaunchInstall=function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
		
	}		

function {$prefix}LaunchInstall(){
	if(confirm('$WANT_INSTALL')){
			var XHR = new XHRConnection();
			XHR.appendData('launch','$product');
			XHR.appendData('product','$product');
			XHR.sendAndLoad('$page', 'GET',x_{$prefix}LaunchInstall);
	}else{
		YahooWin2Hide();
	}
}
	
	function {$prefix}ChargeLogs(){
		if(document.getElementById('squid-install-status')){squid_install_status();}
		var XHR = new XHRConnection();
		XHR.appendData('statusof','$product');
		XHR.sendAndLoad('$page', 'GET',x_{$prefix}ChargeLogs);	
	}
	
	{$prefix}Load();";	
	
	
echo $html;	
}


function popup(){
	$pourc=0;
	$tpl=new templates();
	$html="
	<div style='font-size:14px;font-weight:bold;margin:4px'>{install_upgrade} {{$_GET["product"]}}...</div>
	<table style='width:100%'>
	<tr>
		<td width=1%>
		</td>
		<td width=99%>
			<table style='width:100%'>
			<tr>
			<td>
				<div style='width:100%;background-color:white;padding-left:0px;border:1px solid $color'>
					<div id='progression_install'>
						<div style='width:{$pourc}%;text-align:center;color:white;padding-top:3px;padding-bottom:3px;background-color:$color'>
							<strong style='color:#BCF3D6;font-size:12px;font-weight:bold'>{$pourc}%</strong></center>
						</div>
					</div>
				</div>
			</td>
			</tr>
			</table>		
		</td>
	</tr>
	</table>
	<div id='install_textlogs' class=form></div>";
	
	echo $tpl->_ENGINE_parse_body($html,"setup.index.php");
	
}
function statusof(){
	$appname=$_GET["statusof"];
	$file=dirname(__FILE__). "/ressources/install/$appname.ini";
	$ini=new Bs_IniHandler();
	if(file_exists($file)){
	    $data=file_get_contents($file);
		$ini->loadString($data);
		$pourc=$ini->_params["INSTALL"]["STATUS"];
		$text_info=$ini->_params["INSTALL"]["INFO"];
		if(strlen($text_info)>0){$text_info="<span style='color:white;font-size:10px'>$text_info...</span>";}
		
	}else{
		$pourc=0;
	}
	
$color="#5DD13D";
if($pourc=="110"){
	$color="#CC4B1C";
	$pourc="100";
}
$html="
	<input type='hidden' id='int-progress' value='$pourc'>
	<div style='width:{$pourc}%;text-align:center;color:white;padding-top:3px;padding-bottom:3px;background-color:$color'>
		<strong style='color:#BCF3D6;font-size:12px;font-weight:bold'>{$pourc}%&nbsp;$text_info</strong></center>
	</div>
";	

$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);
	
}

function install_app(){
	$sock=new sockets();
	$tpl=new templates();
	$q=new mysql();
	$sql="SELECT upgrade FROM setup_center WHERE CODE_NAME='{$_GET["product"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if($ligne["upgrade"]==1){
		$echo=$tpl->javascript_parse_text("{installapp_already_scheduled}",1);
		return;
	}
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT CODE_NAME FROM setup_center WHERE CODE_NAME='{$_GET["product"]}'","artica_backup"));
	if($ligne["CODE_NAME"]==null){
		$q->QUERY_SQL("INSERT IGNORE INTO setup_center (upgrade,progress,progress_text,CODE_NAME) VALUES(1,5,'{scheduled}','{$_GET["product"]}')","artica_backup");
		
	}else{
		$q->QUERY_SQL("UPDATE setup_center SET upgrade=1,progress=5,progress_text='{scheduled}',events='' WHERE CODE_NAME='{$_GET["product"]}'","artica_backup");
	}
	
	if(!$q->ok){echo $q->mysql_error;return;}
	
	
	$echo="{{$_GET["product"]}}\n{installation_lauched}";
	$echo=$tpl->javascript_parse_text($echo,1);
	echo $echo;
	
	
}

function logsof(){
	$appname=$_GET["logsof"];
	$sock=new sockets();
	$logs_datas=$sock->getfile("AppliCenterTailDebugInfos:$appname");
	$sock->DeleteCache();
	$tb=explode("\n",$logs_datas);
	
	$count_s=count($tb);
	

	for($i=0;$i<count($tb)+1;$i++){
		if($tb[$i]==null){continue;}
		if(preg_match("#[0-9]+\.[0-9]+%#", $tb[$i])){continue;}
		if(preg_match("#[0-9]+,[0-9]+%#", $tb[$i])){continue;}
		
		echo "<div><code style='font-size:12px;'>".htmlspecialchars($tb[$i])."</code></div>";
		
	}
	
}



?>