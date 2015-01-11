<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');
	$usersmenus=new usersMenus();
	if($usersmenus->AsArticaAdministrator==false){die();}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["cmdline"])){cmdline_perform();exit;}

js();


function js(){
	$page=CurrentPageName();
	$prefix=str_replace(".","_",$page);
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{commandline}');
	echo "YahooWinS(790,'$page?popup=yes','$title');";
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=time();
	
	$html="
	
	<div style='font-size:14px' class=text-info>{commandline_explain}</div>
	<table style='width:99%' class=form>
	<tbody>
		<tr>
			<td>". Field_text("cmdline-$t","cd /home && ls -lah","font-size:16px;font-family:Courier New;padding:5px;font-weight:bold;width:100%",null,null,null,false,"CmdlineCheck(event)")."</td
		</tr>
	</tbody>
	</table>
	<center>
	<div id='$t-results' style='width:90%' class=form></div>
	</center>
	
	<script>
		function CmdlineCheck(e){
			if(checkEnter(e)){CmdlineSend();}
		}
		
		function X_CmdlineSend(obj){
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){
				document.getElementById('$t-results').innerHTML=tempvalue;
			}
		}
		
		function CmdlineSend(){
		var XHR = new XHRConnection();
		var cmdline=document.getElementById('cmdline-$t').value;
		XHR.appendData('cmdline',cmdline);
		AnimateDiv('$t-results');
		XHR.sendAndLoad('$page', 'POST',X_CmdlineSend);			
		
		}
	CmdlineSend();	
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function cmdline_perform(){
	$sock=new sockets();
	echo "<div style='width:100%;height:450px;overflow:auto'>";
	$datas=unserialize(base64_decode($sock->getFrameWork("services.php?cmdlinePerf=".base64_encode($_POST["cmdline"])."&MyCURLTIMEOUT=120")));
	while (list ($num, $ligne) = each ($datas) ){
		$ligne=htmlentities($ligne); 
		$ligne=str_replace("\t", "&nbsp;&nbsp;&nbsp;", $ligne);
		$ligne=str_replace(" ", "&nbsp;", $ligne);
		echo "<div style='font-size:12px;font-family:Courier New;text-align:left;padding:3px'>$ligne</div>";
		
	}
	echo "</div>";
	
}
