<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.maincf.multi.inc');
	
	
$users=new usersMenus();
if(!PostFixMultiVerifyRights()){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	

	if(isset($_POST["remove-all-queues"])){remove();exit;}
	
	
js();



function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$explain=$tpl->javascript_parse_text("{warning_purge_all_queue}");
	$html="
	
		var x_CleanQueuesInstances= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			
		}	
	
	function CleanQueuesInstances(){
		if(confirm('$explain')){
				var XHR = new XHRConnection();
				XHR.appendData('ou','{$_GET["ou"]}');
				XHR.appendData('hostname','{$_GET["hostname"]}');
				XHR.appendData('remove-all-queues','yes');
				XHR.sendAndLoad('$page', 'POST',x_CleanQueuesInstances);	
		
		}
		
	}
	CleanQueuesInstances()";
	
	echo $html;
	
	
	}
	
	
function remove(){
	$sock=new sockets();
	$sock->getFrameWork("postfix.php?postsuper-remove-all=yes&hostname={$_POST["hostname"]}");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{task_has_been_scheduled_in_background_mode}");
	
}
	
