<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	include_once('ressources/class.squid.inc');
	
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}
if(isset($_POST["item-import"])){import();exit;}
if(isset($_GET["popup"])){popup();exit;}


js();


function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$group=$tpl->javascript_parse_text("{group2}");
	$t=$_GET["t"];
	$group_textenc=urlencode($_GET["group_text"]);
	echo "YahooWin6('950','$page?popup=yes&group_id={$_GET["group_id"]}&group_text=$group_textenc&tableid={$_GET["tableid"]}','{$_GET["group_text"]}');";
}

function popup(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();	
	
	$membertype[0]="{ipaddr}";
	$membertype[2]="{cdir}";
	$membertype[1]="{username}";
	
	
	
	$html="
	<div style='font-size:30px;margin-bottom:20px'>{$_GET["group_text"]}&nbsp;&raquo;&nbsp;{items}&nbsp;&raquo;&nbsp;{import}</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
		<tbody>
			<tr>
				<td class=legend style='font-size:22px;text-align:left' nowrap width=1%>{member_type}:</td>
				<td nowrap align=left>". field_array_Hash($membertype,"membertype-$t",null,"blur()",null,0,"font-size:22px")."
			</tr>
			<tr>
				<td colspan=2><textarea style='margin-top:5px;
				font-family:Courier New;font-weight:bold;width:98%;height:350px;
				border:5px solid #8E8E8E;overflow:auto;font-size:18px !important'
				id='textToParseCats-$t'></textarea>
				</td>
			</tr>
			<tr>
				<td style='text-align:right;height:30px' colspan=2><hr>". button("{import}", "SaveItemsMode$t()",26)."</td>
			</tr>
		</tbody>
	</table>
</div>
<script>
var x_SaveItemsMode$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#{$_GET["tableid"]}').flexReload();
	if( document.getElementById('DANSGUARDIAN_EDIT_GROUP_LIST') ){
		$('#'+document.getElementById('DANSGUARDIAN_EDIT_GROUP_LIST').value).flexReload();
	}
	
	YahooWin6Hide();
}

	
	
	
function SaveItemsMode$t(){
	var XHR = new XHRConnection();
	XHR.appendData('item-import', document.getElementById('textToParseCats-$t').value);
	XHR.appendData('membertype', document.getElementById('membertype-$t').value);
	XHR.appendData('group_id', '{$_GET["group_id"]}');
	XHR.sendAndLoad('$page', 'POST',x_SaveItemsMode$t);
}
</script>";
echo $tpl->_ENGINE_parse_body($html);	
	
}

function import(){
	$q=new mysql_squid_builder();
	
	$sql_add="INSERT IGNORE INTO webfilter_members (`membertype`,`pattern`,`enabled`,`groupid`) VALUES ";
	
	$f=explode("\n",$_POST["item-import"]);
	
	while (list ($num, $ligne) = each ($f) ){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		$RR=mysql_fetch_array($q->QUERY_SQL("SELECT ID FROM webfilter_members WHERE pattern='$ligne'"));
		$ID=intval($RR["ID"]);
		$ligne=mysql_escape_string2($ligne);
		$ZZ[]="('{$_POST["membertype"]}','$ligne',1,{$_POST["group_id"]})";
		
	}
	
	if(count($ZZ)==0){return;}
	$q->QUERY_SQL($sql_add.@implode(",", $ZZ));
	
}
