<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.user.inc');
	include_once('ressources/class.cron.inc');
	
	$users=new usersMenus();
	if(!$users->AsPostfixAdministrator){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}	

	if(isset($_GET["js"])){js();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["ConvertInnoDB"])){ConvertInnoDB();exit;}
	
	
	
	
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$title=$tpl->javascript_parse_text("{convertto_innodb_file_per_table}");
	$html="YahooWin5('650','$page?popup=yes','$title');";
	echo $html;
}

function popup(){
	$page=CurrentPageName();	
	$tpl=new templates();
	
	
	$q=new mysql();
	$databases=$q->DATABASE_LIST();
	
	while (list ($database, $line) = each ($databases) ){
		$tbs=$tbs+$line[0];
		$tx=trim($line[1]);
		
		$re=explode("&nbsp;", $tx);
		$tsize=$re[0];
		$unit=$re[1];
		if($unit=="KB"){$size=$size+$tsize;}
		if($unit=="MB"){$tsize=intval($tsize)*1024;$size=$size+$tsize;}
		if($unit=="GB"){$tsize=intval($tsize)*1024;$tsize=$tsize*1024;$size=$size+$tsize;}
	}

	$text=$tpl->javascript_parse_text("{convertto_innodb_file_per_tableask}");
	$text=str_replace("%free", "$size", $text);
	$text=str_replace("%tablesnum", "$tbs", $text);
	
	$t=time();
	$html="
	<center id='$t-div'></center>
	<div class=text-info style='font-size:14px'>{convertto_innodb_file_per_table_text}</div>
	<div style='text-align:right;text-decoration:underline'><a href=\"javascript:blur();\" OnClick=\"javascript:s_PopUpFull('http://www.mail-appliance.org/index.php?cID=278','1024','900');\" style='font-size:16px;'>{online_help}</a></div>
	<table style='width:99%' class=form>
	<tr>
		<td align='center' style='padding:10px'>". button("{convertto_innodb_file_per_table}","ConvertInnodb$t()","18px")."</td>
	</tr>
	</table>
	<script>
		var x_ConvertInnodb$t= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			document.location.href='admin.index.php';
			
		}
	
		function ConvertInnodb$t(){
			if(confirm('$text')){
				var XHR = new XHRConnection();
				XHR.appendData('ConvertInnoDB','yes');
				AnimateDiv('$t-div');
				XHR.sendAndLoad('$page', 'POST',x_ConvertInnodb$t);	
			}		
		
		}	
		
</script>";
	
	echo $tpl->_ENGINE_parse_body($html);	
		

}
function ConvertInnoDB(){
	$sock=new sockets();
	$sock->SET_INFO("InnoDBFilePerTableAsk", 1);
	$sock->getFrameWork("mysql.php?convert-innodb-file-persize=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{convert_background_warn}",1);	
	
}

?>