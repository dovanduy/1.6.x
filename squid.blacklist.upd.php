<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	
	
	$user=new usersMenus();
	if($user->AsWebStatisticsAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["summarize"])){summarize();exit;}
	if(isset($_GET["progress"])){progress();exit;}
js();

function js(){
	$t=time();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{category_database_update}");
	$page=CurrentPageName();
	
	$html="
		function start$t(){
			YahooWinS('550','$page?popup=yes&t=$t','$title');
		
		}
		
		
	start$t();";
	
	echo $html;
}


function popup(){
	$tt=time();
	$t=$_GET["t"];
	$sock=new sockets();
	$executed=false;
	$tpl=new templates();
	$page=CurrentPageName();
	$datas=base64_decode($sock->getFrameWork("squid.php?articadb-checkversion=yes"));
	$LOCAL_VERSION=$sock->getFrameWork("squid.php?articadb-version=yes");
	$PROGRESS=unserialize(base64_decode($sock->getFrameWork("squid.php?articadb-progress=yes")));
	$array=unserialize(base64_decode($sock->getFrameWork("squid.php?articadb-nextversion=yes")));
	$inf=trim($sock->getFrameWork("squid.php?isInjectrunning=yes") );
	if($GLOBALS["VERBOSE"]){echo "inf -> $inf\n<br>";}
	if($inf<>null){$executed=true;}
	$REMOTE_VERSION=$array["ARTICATECH"]["VERSION"];
	$REMOTE_MD5=$array["ARTICATECH"]["MD5"];
	$REMOTE_SIZE=$array["ARTICATECH"]["SIZE"];	
	if(!is_numeric($PROGRESS["POURC"])){$PROGRESS["POURC"]=0;}
	
	$REMOTE_SIZE=FormatBytes($REMOTE_SIZE/1024);
	if($REMOTE_VERSION>$LOCAL_VERSION){	
		if(!$executed){
			$sock->getFrameWork("squid.php?articadb-launch=yes");
			}
		}
		
	
	$category_database_update=$tpl->_ENGINE_parse_body("{category_database_update}");
	$html="
	<div style='font-size:16px;margin-bottom:15px'>$category_database_update:$REMOTE_VERSION ( $REMOTE_SIZE )</div>
	<div id='progress-$t'></div>
	<br>
	<div id='progressD-$t'></div>
	<div style='text-align:right;font-size:12px;font-weight:bold' id='D-$t'></div>
	<br>
	<div id='infos-$t' style='font-size:16px'>Please wait...</div>
	<div id='$t'></div>
	<script>
		function Check$tt(){
			LoadAjaxVerySilent('$t','$page?progress=yes&t=$t&tt=$tt');
			
		}
		setTimeout('Check$tt()',2000);
	</script>
	";
	echo $html;
	
}

function progress(){
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	$sock=new sockets();
	$executed=false;
	$tpl=new templates();
	$page=CurrentPageName();
	$datas=base64_decode($sock->getFrameWork("squid.php?articadb-checkversion=yes"));
	$LOCAL_VERSION=$sock->getFrameWork("squid.php?articadb-version=yes");
	$PROGRESS=unserialize(base64_decode($sock->getFrameWork("squid.php?articadb-progress=yes")));
	$array=unserialize(base64_decode($sock->getFrameWork("squid.php?articadb-nextversion=yes")));
	$inf=trim($sock->getFrameWork("squid.php?isInjectrunning=yes") );
	if($GLOBALS["VERBOSE"]){echo "inf -> $inf\n<br>";}
	if($inf<>null){$executed=true;}
	$REMOTE_VERSION=$array["ARTICATECH"]["VERSION"];
	$REMOTE_MD5=$array["ARTICATECH"]["MD5"];
	$REMOTE_SIZE=$array["ARTICATECH"]["SIZE"];	
	if(!is_numeric($PROGRESS["POURC"])){$PROGRESS["POURC"]=0;}
	$PROGRESS["TEXT"]=$tpl->javascript_parse_text($PROGRESS["TEXT"]);
	if(!is_numeric($PROGRESS["DOWN"])){$PROGRESS["DOWN"]=0;}
	if($executed){$PROGRESS["TEXT"]=$tpl->javascript_parse_text("{running}:")." ".$PROGRESS["TEXT"];}
	$html="
	<script>
		var genprog={$PROGRESS["POURC"]};
		if(genprog<100){
			if(YahooWinSOpen()){
				if(document.getElementById('infos-$t')){document.getElementById('infos-$t').innerHTML='{$PROGRESS["TEXT"]}';}
				$('#progress-{$t}').progressbar({ value: {$PROGRESS["POURC"]}});
				$('#progressD-{$t}').progressbar({ value: {$PROGRESS["DOWN"]}});
				if(document.getElementById('D-$t')){document.getElementById('D-$t').innerHTML='{$PROGRESS["DOWN"]}%';}
				if(document.getElementById('infos-$t')){setTimeout('Check$tt()',5000);}
			}
		}else{
			if(document.getElementById('admin_perso_tabs')){RefreshTab('admin_perso_tabs');}
			$('#progress-{$t}').progressbar({ value: 100});
				
		}
	</script>
	";
	
	echo $html;
	
}
