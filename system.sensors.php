<?php
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');


$usersmenus=new usersMenus();
if(!$usersmenus->AsArticaAdministrator){
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	die();
}
if(isset($_POST["LMSensorsEnable"])){LMSensorsEnable();exit;}
if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_GET["lm-sensors-status"])){status();exit;}
tabs();


function tabs(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$array["parameters"]='{parameters}';
	
	$usersmenus=new usersMenus();
	if(!$usersmenus->SENSORS_INSTALLED){
		echo FATAL_ERROR_SHOW_128("{MODULES_NOT_INSTALLED}");
		die();
	}
	$tabsize="style='font-size:24px'";
	
	while (list ($num, $ligne) = each ($array) ){
		if($num=="greensql"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"greensql.php\"><span $tabsize>$ligne</span></a></li>\n");
			continue;
		}
	
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span $tabsize>$ligne</span></a></li>\n");
	}
	
	
	echo build_artica_tabs($html, "main_config_sensors",1050)."<script>LeftDesign('temperature-256-white.png');</script>";	
	
}
function parameters(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	
	$LMSensorsEnable=intval($sock->GET_INFO("LMSensorsEnable"));
	
	$html="<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td valign='top' style='width:240px'>
			<div id='lm-sensors-status' style='width:98%'></div>
		</td>
		<td valign='top' style='width:98%'>
			".Paragraphe_switch_img("{LMSensorsEnable}", "{LMSensorsEnable_text}","LMSensorsEnable",
					$LMSensorsEnable,null,600)."
					
		<div style='text-align:right;margin-top:50px'><hr>". button("{apply}","Save$t()",36)."</div>
		
		</td>
	</tr>
	</table>
			
	</div>
<script>
var xSave$t=function (obj) {
	var results=obj.responseText;
	if (results.length>3){alert(results);return;}
	Loadjs('system.sensors.progress.php');
	}			
		
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('LMSensorsEnable',document.getElementById('LMSensorsEnable').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}	

LoadAjax('lm-sensors-status','$page?lm-sensors-status=yes');

</script>										
					
					
";
	
echo $tpl->_ENGINE_parse_body($html);
	
	
}


function status(){
	$dd=null;
	$tpl=new templates();
	if(!is_file("/usr/share/artica-postfix/ressources/logs/web/sensors.array")){return;}
	$TEMPA=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/sensors.array"));
	while (list ($DEVICE, $TEMPB) = each ($TEMPA) ){
			while (list ($KEY, $TEMP) = each ($TEMPB) ){
				// temperature-30-green.png
				$icon="temperature-30-green.png";
				$pourc=$TEMP["PERC"];
				$tempS=$TEMP["TEMP"];
				$CritS=$TEMP["CRIT"];
				$color="#5DD13D";
				if($pourc>70){$color="#F59C44";$icon="temperature-30-F59C44.png";}
				if($pourc>90){$color="#D32D2D";$icon="temperature-30-D32D2D.png";}
	
				$dd=$dd."
				<tr>
					<td nowrap align=right width=90px valign='middle'>
						<table style='width:100%'>
							<tbody>
							<tr>
								<td width=1%>" . imgtootltip($icon,"$DEVICE $KEY","")."</td>
									<td width=99% align='right'>
									<a href=\"javascript:blur();\"
									OnClick=\"javascript:$jsview\"
									style='font-weight:bold;text-decoration:underline'>
									$KEY:
									</a>
									</td>
									</tr>
									</tbody>
									</table>
									</td>
									<td>
									<div style='width:100px;background-color:white;padding-left:0px;border:1px solid $color'>
									<div style='width:{$pourc}px;text-align:center;color:white;padding-top:3px;padding-bottom:3px;background-color:$color'>
									<strong>{$pourc}%</strong>
									</div>
									</div>
									</td>
									<td width=1% nowrap style='font-weight:bold'>
									<div style='margin-top:10px;font-weight:bold'>{$tempS}°C/{$CritS}°C&nbsp;($DEVICE)</div></strong>
									</td>
				</tr>";
				
				}
			}

	echo $tpl->_ENGINE_parse_body("<table style='width:100%'>$dd</table>");
	
}
function LMSensorsEnable(){
	$sock=new sockets();
	$sock->SET_INFO("LMSensorsEnable", $_POST["LMSensorsEnable"]);
	
}