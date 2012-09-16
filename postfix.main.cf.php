<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	
	

	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["show"])){main_cf_page();exit;}
	if(isset($_GET["show-search"])){main_cf_page_search();exit;}
	
	
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["postfinger"])){postfinger();exit;}

js();

function js(){
	if($_GET["hostname"]==null){$_GET["hostname"]="master";}
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{main.cf}");
	$page=CurrentPageName();
	$html="
		function MainCfShowConfig(){
			RTMMail(800,'$page?tabs=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}','$title::{$_GET["hostname"]}');
		}
		MainCfShowConfig();
	
	";
		
	echo $html;
	
	
}

function tabs(){
	
	$page=CurrentPageName();
	$array["show"]="main.cf";
	$array["postfinger"]='postfinger';
	if($_GET["hostname"]<>null){unset($array["postfinger"]);}
	
	while (list ($num, $ligne) = each ($array) ){
		
		$html[]="<li><a href=\"$page?$num=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}\"><span style='font-size:14px'>$ligne</span></a></li>\n";
			
		}	
	
	$tab="<div id=main_popup_sasl_auth style='width:100%;height:600px;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_popup_sasl_auth').tabs({
				    load: function(event, ui) {
				        $('a', ui.panel).click(function() {
				            $(ui.panel).load(this.href);
				            return false;
				        });
				    }
				});
			
			
			});
		</script>";		
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($tab);	
	
}

function main_cf_page(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	
	$html="
	<center>
	<table style='width:70%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{search}:</td>
		<td>". Field_text("maincfsearch",null,"font-size:18px;width:320px",null,null,null,false,"PostFixMaincfSearchCK(event)")."</td>
	</tr>
	</table>
	</center>
	<div id='searchPostfixMainCf' style='width:98%;height:450px;overflow:auto' class=form></div>
	<script>
		function PostFixMaincfSearchCK(e){
			if(checkEnter(e)){PostFixMaincfSearch();}
		}
	
	
		function PostFixMaincfSearch(){
			var se=document.getElementById('maincfsearch').value;
			LoadAjax('searchPostfixMainCf','$page?show-search='+se+'&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}');
		}
		
		PostFixMaincfSearch();
	</script>
		
	";
	
	

	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}
function main_cf_page_search(){
	
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->getFrameWork("cmd.php?get-main-cf=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}")));
	if($_GET["show-search"]<>null){
		$search=$_GET["show-search"];
		$search=str_replace(".", "\.", $search);
		$search=str_replace("*", ".*?", $search);
	}
	$html="<table >";
	while (list ($index, $line) = each ($datas) ){
		if($search<>null){if(!preg_match("#$search#", $line)){continue;}}
		$ligne=htmlspecialchars($line);
		if(preg_match("#(.+?)=(.+)#", $line,$re)){$line="<strong style='color:#760505'>{$re[1]}</strong>={$re[2]}";}
		$html=$html."
		<tr>
			<td><code style='font-size:11px'>". $line."</code></td>
		</tr>
		
		";
	}
	$html=$html."</table>";
	
	echo "$html";
	
	
}


	function postfinger(){
	
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->getFrameWork('cmd.php?postfix-postfinger=yes')));
	$html="<table>";
	while (list ($index, $line) = each ($datas) ){
		$line=htmlspecialchars($line);
		if(preg_match("#--.+?--#",$line)){$line="<H2>$line</H2>";}
		$line=str_replace("\t","&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$line);
		$line=str_replace("  ","&nbsp;&nbsp;",$line);
		
		$html=$html."
		<tr>
			<td><code style='font-size:11px'>$line</code></td>
		</tr>
		
		";
	}
	$html=$html."</table>";
	
	echo "$html";
	
	
}	
?>	

