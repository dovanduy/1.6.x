<?php
$GLOBALS["AS_ROOT"]=false;
if(function_exists("posix_getuid")){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.html.pages.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');

if($argv[1]=="update-white-32-tr"){update_white_32_tr();exit;}
if(isset($_GET["update-white-32-tr"])){update_white_32_tr();exit;}
if(GET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__)){return null;}
$page=CurrentPageName();
$users=new usersMenus();
	if(!$users->AsArticaAdministrator){
					$menus["{account}"]="javascript:Loadjs(\"users.account.php?js=yes\")";
					//if($this->AllowEditAsWbl){$menus["{white list} & {black list}"]="users.aswb.php";}
	}
				
				
				if($users->AsSystemAdministrator){
					$tr[]=BuildIcons("explorer-32-white-tr.png","explorer-32-white.png","{explorer}","Loadjs('tree.php')");
					$tr[]=BuildIcons("top-48-mycomp-tr.png","top-48-mycomp.png","{manage_your_server}","ConfigureYourserver()");
					$tr[]=BuildIcons("32-settings-white-tr.png","32-settings-white.png","{advanced_options}","Loadjs('admin.left.php?old-menu=yes')");
					$tr[]=BuildIcons("32-cd-scan-white-tr.png","32-cd-scan-white.png","{install_upgrade_new_softwares}","Loadjs('setup.index.php?js=yes')");
					$tr[]=BuildIcons("services-32-white-tr.png","services-32-white.png","{display_running_services}","Loadjs('admin.index.services.status.php?js=yes')");
				}
				
				if($users->POSTFIX_INSTALLED){
					if($users->AsPostfixAdministrator){
						$tr[]=BuildIcons("fleche-32-white-tr.png","fleche-32-white.png","{compile_postfix}","Loadjs('postfix.compile.php')");
						
					}
				}
				
				if($users->AsSystemAdministrator){
					$tr[]=BuildIcons("network-32-white-tr.png","network-32-white.png","{network}","LoadAjax('BodyContent','quicklinks.network.php?newinterface=yes');");		
				}
				
				$tr[]="<div id='update-white-32-tr'></div>";
				$tr[]=BuildIcons("close-white-32-tr.png","close-white-32.png","{empty_console_cache}","CacheOff()");

//32-settings-white.png
//close-white-32.png

$html[]="
<center>
<table style='width:50%'>
<tbody>
<tr>
";
while (list ($num, $line) = each ($tr)){
	$html[]= "<td align='center'>". $line."</td>\n";
}
$html[]= "

</tr>
</tbody>
</table>
</center>
<script>
	initMessagesTop();
	AjaxTopMenu('update-white-32-tr','$page?update-white-32-tr=yes');
</script>

";
$datas=@implode("\n", $html);
SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$datas);
echo $datas;

function update_white_32_tr(){
	
	$tpl=new templates();
	$sock=new sockets();
	if(!$GLOBALS["AS_ROOT"]){
		
		if($_SESSION["uid"]<>-100){if(is_numeric($_SESSION["uid"])){return null;}}
		if(is_file("/usr/share/artica-postfix/ressources/logs/web/admin.index.notify.html")){
			$data=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/admin.index.notify.html");
			if(strlen($data)>45){
				echo $tpl->_ENGINE_parse_body($data);
				return;	
				}
			}
		}
	
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$packagesNumber=$q->COUNT_ROWS("syspackages_updt", "artica_backup");	
	if($packagesNumber>0){
		$f=BuildIcons("update-white-tr-w32.png","update-white-32.png","$packagesNumber {system_packages_can_be_upgraded}","Loadjs('artica.update.php?js=yes')");
	}else{
		$f=BuildIcons("update-white-32-tr.png","update-white-32.png","{update}","Loadjs('artica.update.php?js=yes')");
	}
	
	$datas=unserialize(base64_decode($sock->getFrameWork("services.php?is-dpkg-running=yes")));
	if(count($datas)>0){
		$f=BuildIcons("ajax-top-menu-loader.gif","ajax-top-menu-loader.gif","{update} {running}","Loadjs('artica.update.php?js=yes')");
	}
	$datas=unserialize(base64_decode($sock->getFrameWork("services.php?ARTICA-MAKE=yes")));
	if(count($datas)>0){
		
		
		while (list ($num, $line) = each ($datas)){
			$t[]="<b>{{$num}}</b> {since} $line<br>";
		}
		$f=BuildIcons("ajax-top-menu-loader.gif","ajax-top-menu-loader.gif","{install} {running}<br>".@implode($t, ""),"Loadjs('artica.update.php?js=yes')");
	}

	$sock=new sockets();
	$notifyScript=false;
	$scheduledAR=unserialize(base64_decode($sock->getFrameWork("squid.php?schedule-import-exec=yes")));
	if($scheduledAR["RUNNING"]){
		$db_import=$tpl->_ENGINE_parse_body("<i style='font-size:16px;color:#BA0000'>{update_currently_running_since} {$scheduledAR["TIME"]}Mn</i>");	
		$f=$f."<script>MessagesTopshowMessage(\"$db_import\")</script>";
		$notifyScript=true;
	}
	
	if(!$notifyScript){
		$color="black";
		$notify=unserialize(base64_decode($sock->GET_INFO("TOP_NOTIFY")));
		if(!is_array($notify)){$notify=array();}
		if(count($notify)>0){
			@krsort($notify);
			while (list ($index, $array) = each ($notify) ){
			if(is_numeric($array["TIME"])){
				$took=distanceOfTimeInWords($array["TIME"],time());
				$array["CONTENT"]=$tpl->_ENGINE_parse_body($array["CONTENT"]);	
				if($array["PRIO"]=="info"){$color="white";}
				$f=$f."<script>MessagesTopshowMessage(\"<span style=font-size:18px;color:$color>". $tpl->_ENGINE_parse_body("{since}:$took, {$array["CONTENT"]}")."</span>\",'{$array["PRIO"]}' )</script>";
				$notifyScript=true;
				unset($notify[$index]);
				break;
			}
			unset($notify[$index]);
			continue;
		}
		$newArray=base64_encode(serialize($notify));	
		$sock->SaveConfigFile($newArray, "TOP_NOTIFY");
		}
	}
	
	echo $tpl->_ENGINE_parse_body($f);
	
}




function BuildIcons($imageoff,$imageon,$help,$js){
	
			$tpl=new templates();	
			$help=str_replace("[br][br]","[br]",$help);
			$help=str_replace("\n","",$help);
			$help=str_replace("\r\n","",$help);
			$help=str_replace("\r","",$help);
			$help=str_replace('"',"`",$help);		
			$help=$tpl->_ENGINE_parse_body($help,$additional_langfile);
			$help=htmlentities($help);
			$help=str_replace("\n","",$help);
			$help=str_replace("\r\n","",$help);
			$help=str_replace("\r","",$help);	
			
	$md5=md5($imageoff);
	
	$bullon="AffBulle('$help');this.style.cursor='pointer';";
	$bulloff="HideBulle();this.style.cursor='default';";
	$html="<div 
	OnMouseOver=\"javascript:document.getElementById('$md5').src='img/$imageon';$bullon\"
	OnMouseOut=\"javscript:document.getElementById('$md5').src='img/$imageoff';$bulloff\"
	OnClick=\"javascript:$js\"
	style='width:45px'
	><center><img src='img/$imageoff' id='$md5'></center>";
	return $html;
	
	
}
