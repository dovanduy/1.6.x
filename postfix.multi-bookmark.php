<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once('ressources/class.status.inc');
	if(isset($_GET["org"])){$_GET["ou"]=$_GET["org"];}
	
	if(!PostFixMultiVerifyRights()){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	
js();


function js(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$title="{$_GET["hostname"]}:{bookmark_item}";
	$title=$tpl->_ENGINE_parse_body($title);
	$html="RTMMail('374','$page?popup=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}','$title');
	
	if(document.getElementById('left-menus-services')){
				var content=document.getElementById('left-menus-services').innerHTML;
				if(content.length<50){
					LoadAjaxWhite('left-menus-services','admin.index.status-infos.php?left-menus-services=yes');
				}
			}
	
	
	";
	echo $html;
	
	
	
	
}


function popup(){
	$tpl=new templates();
	$page=CurrentPageName();	
	
	$INSTANCEBKM=unserialize(stripslashes($_COOKIE["INSTANCEBKM"]));
	
	
	
	if($INSTANCEBKM[$_GET["hostname"]]["enabled"]==1){$value=1;}
	
	unset($INSTANCEBKM[$_GET["hostname"]]);
	$bookoff=serialize($INSTANCEBKM);
	
	$INSTANCEBKM[$_GET["hostname"]]["enabled"]=1;
	$INSTANCEBKM[$_GET["hostname"]]["ou"]=$_GET["ou"];
	
	$bookon=serialize($INSTANCEBKM);
	$t=time;
	
	$p=Paragraphe_switch_img("{bookmark_item}", "<strong>{$_GET["hostname"]}</strong><hr>{bookmark_item_text}","BookMarkItem-$t",$value,null,350);
	
	$html="
	<div id='$t'>
	$p
	<hr>
	<input type='hidden' id='bookon-$t' value='$bookon'>
	<input type='hidden' id='bookoff-$t' value='$bookoff'>
	<div style='width:100%;text-align:right'>". button("{apply}", "MultipleInstanceBookOffOn()",16)."</div>
	</div>
	<script>
		
		function MultipleInstanceBookOffOn(){
			var value=document.getElementById('BookMarkItem-$t').value;
			var cook='';
			if(value==1){cook=document.getElementById('bookon-$t').value;}else{cook=document.getElementById('bookoff-$t').value;}
			AnimateDiv('$t');
			Set_Cookie('INSTANCEBKM', cook, '3600', '/', '', '');
			Loadjs('$page?hostname={$_GET["hostname"]}&ou={$_GET["ou"]}');
			if(document.getElementById('left-menus-services')){
				var content=document.getElementById('left-menus-services').innerHTML;
				if(content.length<50){
					LoadAjaxWhite('left-menus-services','admin.index.status-infos.php?left-menus-services=yes');
				}
			}
		}
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);	
	
}
