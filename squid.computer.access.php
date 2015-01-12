<?php
$GLOBALS["VERBOSE"]=false;
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
session_start ();
include_once ('ressources/class.templates.inc');
include_once ('ressources/class.ldap.inc');
include_once ('ressources/class.users.menus.inc');
include_once ('ressources/class.artica.inc');
include_once ('ressources/class.user.inc');
include_once ('ressources/class.computers.inc');
include_once ('ressources/class.ocs.inc');


$usersprivs = new usersMenus ( );
$change_aliases = GetRights_aliases();


if($change_aliases==0){
	echo FATAL_WARNING_SHOW_128("{ERROR_NO_PRIVS}");
	die();
}

if(isset($_POST["MAC"])){save();exit;}

popup();
function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	if($SquidPerformance>2){
		echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{software_is_disabled_performance}"));
	}
	
	
	$q=new mysql_squid_builder();
	$_GET["mac"]=strtolower($_GET["mac"]);
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM computers_time WHERE `MAC`='{$_GET["mac"]}'","artica_backup"));
	$array["0"]="00:00";
	$array["3600"]="01:00";
	$array["7200"]="02:00";
	$array["10800"]="03:00";
	$array["14400"]="04:00";
	$array["18000"]="05:00";
	$array["21600"]="06:00";
	$array["25200"]="07:00";
	$array["28800"]="08:00";
	$array["32400"]="09:00";
	$array["36000"]="10:00";
	$array["39600"]="11:00";
	$array["43200"]="12:00";
	
	$array2=$array;
	$array2["-1"]="{select}";
	
	$t=time();
	$MONDAY_AM=explode(";",$ligne["MONDAY_AM"]);
	$TUESDAY_AM=explode(";",$ligne["TUESDAY_AM"]);
	$WEDNESDAY_AM=explode(";",$ligne["WEDNESDAY_AM"]);
	$THURSDAY_AM=explode(";", $ligne["THURSDAY_AM"]);
	$FRIDAY_AM=explode(";", $ligne["FRIDAY_AM"]);
	$SATURDAY_AM=explode(";", $ligne["SATURDAY_AM"]);
	$SUNDAY_AM=explode(";", $ligne["SUNDAY_AM"]);
	$MONDAY_PM=explode(";",$ligne["MONDAY_PM"]);
	$TUESDAY_PM=explode(";",$ligne["TUESDAY_PM"]);
	$WEDNESDAY_PM=explode(";",$ligne["WEDNESDAY_PM"]);
	$THURSDAY_PM=explode(";", $ligne["THURSDAY_PM"]);
	$FRIDAY_PM=explode(";", $ligne["FRIDAY_PM"]);
	$SATURDAY_PM=explode(";", $ligne["SATURDAY_PM"]);
	$SUNDAY_PM=explode(";", $ligne["SUNDAY_PM"]);
	
	
	
	$ARRAYF["MONDAY"]=true;
	$ARRAYF["MONDAY"]=true;
	$ARRAYF["TUESDAY"]=true;
	$ARRAYF["WEDNESDAY"]=true;
	$ARRAYF["THURSDAY"]=true;
	$ARRAYF["FRIDAY"]=true;
	$ARRAYF["SATURDAY"]=true;
	$ARRAYF["SUNDAY"]=true;
	
	while (list ($index, $line) = each ($ARRAYF)){
		$SAVE[]="var value=document.getElementById('{$index}_AM_1-$t').value;";
		$SAVE[]="var value2=document.getElementById('{$index}_AM_2-$t').value;";
		$SAVE[]="XHR.appendData('{$index}_AM',value+';'+value2);";
		
		$SAVE[]="var value=document.getElementById('{$index}_PM_1-$t').value;";
		$SAVE[]="var value2=document.getElementById('{$index}_PM_2-$t').value;";
		$SAVE[]="XHR.appendData('{$index}_PM',value+';'+value2);";
		
		$SwitchEnabled["ON"][]="document.getElementById('{$index}_PM_1-$t').disabled=false;";
		$SwitchEnabled["ON"][]="document.getElementById('{$index}_PM_2-$t').disabled=false;";
		$SwitchEnabled["ON"][]="document.getElementById('{$index}_AM_1-$t').disabled=false;";
		$SwitchEnabled["ON"][]="document.getElementById('{$index}_AM_2-$t').disabled=false;";
		
		$SwitchEnabled["OFF"][]="document.getElementById('{$index}_PM_1-$t').disabled=true;";
		$SwitchEnabled["OFF"][]="document.getElementById('{$index}_PM_2-$t').disabled=true;";
		$SwitchEnabled["OFF"][]="document.getElementById('{$index}_AM_1-$t').disabled=true;";
		$SwitchEnabled["OFF"][]="document.getElementById('{$index}_AM_2-$t').disabled=true;";
		
		
		$SwitchCopy[]="document.getElementById('{$index}_PM_1-$t').value=MONDAY_PM_0;";
		$SwitchCopy[]="document.getElementById('{$index}_PM_2-$t').value=MONDAY_PM_01;";
		$SwitchCopy[]="document.getElementById('{$index}_AM_1-$t').value=MONDAY_AM_0;";
		$SwitchCopy[]="document.getElementById('{$index}_AM_2-$t').value=MONDAY_AM_01;";
		
	}
	

	
	$field_MONDAY_AM_0=Field_array_Hash($array2, "MONDAY_AM_0-$t",-1,"style:font-size:16px");
	$field_MONDAY_AM_01=Field_array_Hash($array2, "MONDAY_AM_01-$t",-1,"style:font-size:16px");
	

	
	
	$field_MONDAY_AM_1=Field_array_Hash($array, "MONDAY_AM_1-$t",$MONDAY_AM[0],"style:font-size:18px");
	$field_MONDAY_AM_2=Field_array_Hash($array, "MONDAY_AM_2-$t",$MONDAY_AM[1],"style:font-size:18px");
	
	$field_TUESDAY_AM_1=Field_array_Hash($array, "TUESDAY_AM_1-$t",$TUESDAY_AM[0],"style:font-size:18px");
	$field_TUESDAY_AM_2=Field_array_Hash($array, "TUESDAY_AM_2-$t",$TUESDAY_AM[1],"style:font-size:18px");
	
	
	$field_WEDNESDAY_AM_1=Field_array_Hash($array, "WEDNESDAY_AM_1-$t",$WEDNESDAY_AM[0],"style:font-size:18px");
	$field_WEDNESDAY_AM_2=Field_array_Hash($array, "WEDNESDAY_AM_2-$t",$WEDNESDAY_AM[1],"style:font-size:18px");
	
	
	$field_THURSDAY_AM_1=Field_array_Hash($array, "THURSDAY_AM_1-$t",$THURSDAY_AM[0],"style:font-size:18px");
	$field_THURSDAY_AM_2=Field_array_Hash($array, "THURSDAY_AM_2-$t",$THURSDAY_AM[1],"style:font-size:18px");
	
	
	$field_FRIDAY_AM_1=Field_array_Hash($array, "FRIDAY_AM_1-$t",$FRIDAY_AM[0],"style:font-size:18px");
	$field_FRIDAY_AM_2=Field_array_Hash($array, "FRIDAY_AM_2-$t",$FRIDAY_AM[1],"style:font-size:18px");
	
	
	$field_SATURDAY_AM_1=Field_array_Hash($array, "SATURDAY_AM_1-$t",$SATURDAY_AM[0],"style:font-size:18px");
	$field_SATURDAY_AM_2=Field_array_Hash($array, "SATURDAY_AM_2-$t",$SATURDAY_AM[1],"style:font-size:18px");
	
	
	$field_SUNDAY_AM_1=Field_array_Hash($array, "SUNDAY_AM_1-$t",$SUNDAY_AM[0],"style:font-size:18px");
	$field_SUNDAY_AM_2=Field_array_Hash($array, "SUNDAY_AM_2-$t",$SUNDAY_AM[1],"style:font-size:18px");
	
	$array=array();
	$array["46800"]="13:00";
	$array["50400"]="14:00";
	$array["54000"]="15:00";
	$array["57600"]="16:00";
	$array["61200"]="17:00";
	$array["64800"]="18:00";
	$array["68400"]="19:00";
	$array["72000"]="20:00";
	$array["75600"]="21:00";
	$array["79200"]="22:00";
	$array["82800"]="23:00";
	
	$array2=$array;
	$array2[NULL]="{select}";
	
	$field_MONDAY_PM_0=Field_array_Hash($array2, "MONDAY_PM_0-$t",null,"style:font-size:16px");
	$field_MONDAY_PM_01=Field_array_Hash($array2, "MONDAY_PM_01-$t",null,"style:font-size:16px");

	
	$field_MONDAY_PM_1=Field_array_Hash($array, "MONDAY_PM_1-$t",$MONDAY_PM[0],"style:font-size:18px");
	$field_MONDAY_PM_2=Field_array_Hash($array, "MONDAY_PM_2-$t",$MONDAY_PM[1],"style:font-size:18px");
	
	$field_TUESDAY_PM_1=Field_array_Hash($array, "TUESDAY_PM_1-$t",$TUESDAY_PM[0],"style:font-size:18px");
	$field_TUESDAY_PM_2=Field_array_Hash($array, "TUESDAY_PM_2-$t",$TUESDAY_PM[1],"style:font-size:18px");
	
	
	$field_WEDNESDAY_PM_1=Field_array_Hash($array, "WEDNESDAY_PM_1-$t",$WEDNESDAY_PM[0],"style:font-size:18px");
	$field_WEDNESDAY_PM_2=Field_array_Hash($array, "WEDNESDAY_PM_2-$t",$WEDNESDAY_PM[1],"style:font-size:18px");
	
	
	$field_THURSDAY_PM_1=Field_array_Hash($array, "THURSDAY_PM_1-$t",$THURSDAY_PM[0],"style:font-size:18px");
	$field_THURSDAY_PM_2=Field_array_Hash($array, "THURSDAY_PM_2-$t",$THURSDAY_PM[1],"style:font-size:18px");
	
	
	$field_FRIDAY_PM_1=Field_array_Hash($array, "FRIDAY_PM_1-$t",$FRIDAY_PM[0],"style:font-size:18px");
	$field_FRIDAY_PM_2=Field_array_Hash($array, "FRIDAY_PM_2-$t",$FRIDAY_PM[1],"style:font-size:18px");
	
	
	$field_SATURDAY_PM_1=Field_array_Hash($array, "SATURDAY_PM_1-$t",$SATURDAY_PM[0],"style:font-size:18px");
	$field_SATURDAY_PM_2=Field_array_Hash($array, "SATURDAY_PM_2-$t",$SATURDAY_PM[1],"style:font-size:18px");
	
	
	$field_SUNDAY_PM_1=Field_array_Hash($array, "SUNDAY_PM_1-$t",$SUNDAY_PM[0],"style:font-size:18px");
	$field_SUNDAY_PM_2=Field_array_Hash($array, "SUNDAY_PM_2-$t",$SUNDAY_PM[1],"style:font-size:18px");

	$please_make_a_selection=$tpl->javascript_parse_text("{please_define_default_values}");
	
	$html="<div style='width:98%' class=form>
	<table style='width:100%'>
	
	<tr>
		<td style='font-size:22px' colspan=4>
			".Paragraphe_switch_img("{enable_internet_restriction}",
			"{enable_internet_restriction_explain}","enabled-$t",$ligne["enabled"],null,750,"SwitchEnabled$t()")."		
		</td>
	</tr>

	<tr>
		<td style='font-size:16px' colspan=4>{for_all_days}</td>
	</tr>
	
	<tr>
		<td class=legend style='font-size:16px'>{allow_internet_between}:</td>
		<td>$field_MONDAY_AM_0</td>
		<td class=legend style='font-size:16px'>{to_time}:</td>
		<td>$field_MONDAY_AM_01</td>	
	</tr>
	<tr>
		<td class=legend style='font-size:16px'{allow_internet_between}:</td>
		<td>$field_MONDAY_PM_0</td>
		<td class=legend style='font-size:16px'>{to_time}:</td>
		<td>$field_MONDAY_PM_01</td>	
	</tr>	
	<tr>
		<td class=legend style='font-size:28px' colspan=4><hr>
		". button("{apply}","AllDays$t()",22)."
		</td>
	</tr>

	
	
	<tr>
		<td style='font-size:22px' colspan=4>{monday}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{allow_internet_between}:</td>
		<td>$field_MONDAY_AM_1</td>
		<td class=legend style='font-size:18px'>{to_time}:</td>
		<td>$field_MONDAY_AM_2</td>	
	</tr>
	<tr>
		<td class=legend style='font-size:18px'{allow_internet_between}:</td>
		<td>$field_MONDAY_PM_1</td>
		<td class=legend style='font-size:18px'>{to_time}:</td>
		<td>$field_MONDAY_PM_2</td>	
	</tr>	
	<tr>
		<td class=legend style='font-size:28px' colspan=4><hr></td>
	</tr>
	

	<tr>
		<td style='font-size:22px' colspan=4>{tuesday}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{allow_internet_between}:</td>
		<td>$field_TUESDAY_AM_1</td>
		<td class=legend style='font-size:18px'>{to_time}:</td>
		<td>$field_TUESDAY_AM_2</td>	
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{allow_internet_between}:</td>
		<td>$field_TUESDAY_PM_1</td>
		<td class=legend style='font-size:18px'>{to_time}:</td>
		<td>$field_TUESDAY_PM_2</td>	
	</tr>	
	<tr>
		<td class=legend style='font-size:28px' colspan=4><hr></td>
	</tr>
	
	
	
	<tr>
		<td style='font-size:22px' colspan=4>{wednesday}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{allow_internet_between}:</td>
		<td>$field_WEDNESDAY_AM_1</td>
		<td class=legend style='font-size:18px'>{to_time}:</td>
		<td>$field_WEDNESDAY_AM_2</td>	
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{allow_internet_between}:</td>
		<td>$field_WEDNESDAY_PM_1</td>
		<td class=legend style='font-size:18px'>{to_time}:</td>
		<td>$field_WEDNESDAY_PM_2</td>	
	</tr>	
	<tr>
		<td class=legend style='font-size:28px' colspan=4><hr></td>
	</tr>	
	
	
	
	<tr>
		<td style='font-size:22px' colspan=4>{thursday}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{allow_internet_between}:</td>
		<td>$field_THURSDAY_AM_1</td>
		<td class=legend style='font-size:18px'>{to_time}:</td>
		<td>$field_THURSDAY_AM_2</td>	
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{allow_internet_between}:</td>
		<td>$field_THURSDAY_PM_1</td>
		<td class=legend style='font-size:18px'>{to_time}:</td>
		<td>$field_THURSDAY_PM_2</td>	
	</tr>	
	<tr>
		<td class=legend style='font-size:28px' colspan=4><hr></td>
	</tr>	

	<tr>
		<td style='font-size:22px' colspan=4>{friday}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{allow_internet_between}:</td>
		<td>$field_FRIDAY_AM_1</td>
		<td class=legend style='font-size:18px'>{to_time}:</td>
		<td>$field_FRIDAY_AM_2</td>	
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{allow_internet_between}:</td>
		<td>$field_FRIDAY_PM_1</td>
		<td class=legend style='font-size:18px'>{to_time}:</td>
		<td>$field_FRIDAY_PM_2</td>	
	</tr>	
	<tr>
		<td class=legend style='font-size:28px' colspan=4><hr></td>
	</tr>	

	
	<tr>
		<td style='font-size:22px' colspan=4>{saturday}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{allow_internet_between}:</td>
		<td>$field_SATURDAY_AM_1</td>
		<td class=legend style='font-size:18px'>{to_time}:</td>
		<td>$field_SATURDAY_AM_2</td>	
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{allow_internet_between}:</td>
		<td>$field_SATURDAY_PM_1</td>
		<td class=legend style='font-size:18px'>{to_time}:</td>
		<td>$field_SATURDAY_PM_2</td>	
	</tr>	
	<tr>
		<td class=legend style='font-size:28px' colspan=4><hr></td>
	</tr>	

	
	<tr>
		<td style='font-size:22px' colspan=4>{sunday}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{allow_internet_between}:</td>
		<td>$field_SUNDAY_AM_1</td>
		<td class=legend style='font-size:18px'>{to_time}:</td>
		<td>$field_SUNDAY_AM_2</td>	
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{allow_internet_between}:</td>
		<td>$field_SUNDAY_PM_1</td>
		<td class=legend style='font-size:18px'>{to_time}:</td>
		<td>$field_SUNDAY_PM_2</td>	
	</tr>	
	<tr>
		<td style='font-size:28px' colspan=4 align='right'><hr>". button("{apply}","SaveCMP$t()",32)."</td>
	</tr>		
	
	</table>
	</div>
<script>
var x_SaveCMP$t=function(obj){
	var results=trim(obj.responseText);
	if(results.length>2){alert(results);return;} 
	$('#table-$t').flexReload();
	Loadjs('squid.computer.access.progress.php');
}	
function SaveCMP$t(){
	var XHR = new XHRConnection();
	XHR.appendData('MAC','{$_GET["mac"]}');  
	XHR.appendData('enabled',document.getElementById('enabled-$t').value);  
	
	".@implode("\n", $SAVE)."
	XHR.sendAndLoad('$page', 'POST',x_SaveCMP$t);       
}

function SwitchEnabled$t(){
	var enabled=document.getElementById('enabled-$t').value;
	".@implode("\n", $SwitchEnabled["ON"])."
	if(enabled==0){
		".@implode("\n", $SwitchEnabled["OFF"])."
	}
}

function AllDays$t(){
	var MONDAY_AM_0=document.getElementById('MONDAY_AM_0-$t').value;
	var MONDAY_AM_01=document.getElementById('MONDAY_AM_01-$t').value;
	var MONDAY_PM_0=document.getElementById('MONDAY_PM_0-$t').value;
	var MONDAY_PM_01=document.getElementById('MONDAY_PM_01-$t').value;
	if(MONDAY_AM_0==-1){alert('$please_make_a_selection');return;}
	if(MONDAY_AM_01==-1){alert('$please_make_a_selection');return;}
	if(MONDAY_PM_0==-1){alert('$please_make_a_selection');return;}
	if(MONDAY_PM_01==-1){alert('$please_make_a_selection');return;}
	
	
	".@implode("\n", $SwitchCopy)."
	SaveCMP$t();
}

SwitchEnabled$t();
</script>
	";

	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
}

function Save(){
	$q=new mysql_squid_builder();
	$ARRAYF["MONDAY"]=true;
	$ARRAYF["MONDAY"]=true;
	$ARRAYF["TUESDAY"]=true;
	$ARRAYF["WEDNESDAY"]=true;
	$ARRAYF["THURSDAY"]=true;
	$ARRAYF["FRIDAY"]=true;
	$ARRAYF["SATURDAY"]=true;
	$ARRAYF["SUNDAY"]=true;
	
	$q=new mysql_squid_builder();
	
	$sql="CREATE TABLE IF NOT EXISTS `computers_time` (
				`MAC` VARCHAR( 90 ) NOT NULL,
				`ipaddr` VARCHAR( 90 ) NOT NULL,
				`enabled` SMALLINT(1) NOT NULL DEFAULT 0,
				`MONDAY_AM` VARCHAR( 90 ) NOT NULL,
				`MONDAY_PM` VARCHAR( 90 ) NOT NULL,
	
				`TUESDAY_AM` VARCHAR( 90 ) NOT NULL,
				`TUESDAY_PM` VARCHAR( 90 ) NOT NULL,
	
				`WEDNESDAY_AM` VARCHAR( 90 ) NOT NULL,
				`WEDNESDAY_PM` VARCHAR( 90 ) NOT NULL,
	
				`THURSDAY_AM` VARCHAR( 90 ) NOT NULL,
				`THURSDAY_PM` VARCHAR( 90 ) NOT NULL,
	
				`FRIDAY_AM` VARCHAR( 90 ) NOT NULL,
				`FRIDAY_PM` VARCHAR( 90 ) NOT NULL,
	
				`SATURDAY_AM` VARCHAR( 90 ) NOT NULL,
				`SATURDAY_PM` VARCHAR( 90 ) NOT NULL,
	
	
				`SUNDAY_AM` VARCHAR( 90 ) NOT NULL,
				`SUNDAY_PM` VARCHAR( 90 ) NOT NULL,
	
				 UNIQUE KEY `MAC` (`MAC`),
				 KEY `enabled` (`enabled`)
				 
				 )  ENGINE = MYISAM;
			";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
	while (list ($index, $line) = each ($ARRAYF)){
		$feditfields[]="`{$index}_PM`='{$_POST["{$index}_PM"]}'";
		$feditfields[]="`{$index}_AM`='{$_POST["{$index}_AM"]}'";
		$field1[]="`{$index}_PM`";
		$field1[]="`{$index}_AM`";
		$field2[]="'{$_POST["{$index}_PM"]}'";
		$field2[]="'{$_POST["{$index}_AM"]}'";
	}
	$add=false;
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `MAC` FROM computers_time WHERE `MAC`='{$_POST["MAC"]}'","artica_backup"));
	if($ligne["MAC"]==null){$add=true;}
	
	if($add){
		$sql="INSERT IGNORE INTO computers_time (`MAC`,`ipaddr`,`enabled`,".@implode(",", $field1).")
		VALUES ('{$_POST["MAC"]}','{$_POST["ipaddr"]}','{$_POST["enabled"]}',".@implode(",", $field2).")";
	}else{
		$sql="UPDATE computers_time SET `enabled`='{$_POST["enabled"]}',".@implode(",", $feditfields)." WHERE `MAC`='{$_POST["MAC"]}'";
		
		
	}
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n$sql\n";}
	

	
}
