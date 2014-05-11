<?php
session_start();
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class=error-text>");
ini_set('error_append_string',"</p>\n");

$_SESSION["MINIADM"]=true;
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
if(!isset($_SESSION["uid"])){writelogs("Redirecto to miniadm.logon.php...","NULL",__FILE__,__LINE__);header("location:miniadm.logon.php");}
if($_SESSION["uid"]=="-100"){writelogs("Redirecto to location:admin.index.php...","NULL",__FILE__,__LINE__);header("location:admin.index.php");die();}
$users=new usersMenus();
if(!$users->AsDansGuardianAdministrator){header("location:miniadm.index.php");}

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["webfiltering-tabs"])){webfiltering_tabs();exit;}
if(isset($_GET["rules-section"])){rules_section();exit;}
if(isset($_GET["rules-search"])){rules_search();exit;}



main_page();
exit;

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;
}



function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();

	$html="<div class=BodyContent>
	<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a>&nbsp;&raquo;&nbsp;
	</div>
	<H1>{WEB_FILTERING}</H1>
	<p>{WEB_FILTERING_EXPLAIN}</p>
	<div class=BodyContentWork id='$t'></div>

	<script>
	LoadAjax('$t','$page?tabs=yes');
	</script>

	";
	echo $tpl->_ENGINE_parse_body($html);
}
function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$sock=new sockets();
	$SQUIDEnable=trim($sock->GET_INFO("SQUIDEnable"));
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	
	if($SQUIDEnable==0){
		echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{proxy_service_is_disabled}<hr>		<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.newbee.php?js_enable_disable_squid=yes')\" style='font-size:22px;text-decoration:underline'>
		{enable_squid_service}</a>"));
		return;
	}	
	
	$q=new mysql_squid_builder();
	$t=time();
	$boot=new boostrap_form();
	$array["{ACLS}"]="miniadmin.access.rules.php?tabs=yes&title=yes";
	$users=new usersMenus();
	if($users->APP_UFDBGUARD_INSTALLED){
		$array["{categories_filtering}"]="$page?webfiltering-tabs=yes";
		
	}
	if($users->APP_CHILLI_INSTALLED){
		if($users->AsHotSpotManager){
			$array["HotSpot"]="miniadmin.webfiltering.coova.php?tabs=yes";
		}
	}
	
	if($users->C_ICAP_INSTALLED){
		$array["Antivirus"]="miniadmin.proxy.cicap.php?tabs=yes";
	}
	
	
	echo $boot->build_tab($array);
}
function webfiltering_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$t=time();
	$boot=new boostrap_form();
	$array["{rules}"]="$page?rules-section=yes";
	$array["{your_categories}"]="miniadmin.webfiltering.categories.php";
	$array["{service_parameters}"]="miniadmin.webfiltering.ufdbguard.php?tabs=yes";
	echo $boot->build_tab($array);
}
function rules_section(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$boot=new boostrap_form();
	$button=button("{new_rule}","DansGuardianNewRule()",16);
	$apply_params=$tpl->_ENGINE_parse_body("{apply}");
	$button_edit=null;

	
	$compile_rules=button("{compile_rules}","CompileUfdbGuardRules()",16);
	
	
	$categories_group=button("{categories_groups}","Loadjs('dansguardian2.categories.group.php?tSource=$t');");
	
	
	$global_parameters=button("{global_parameters}","UfdbGuardConfigs()",16);
	$SearchQuery=$boot->SearchFormGen("groupname","rules-search");
	
	if($_GET["listen-port"]>0){
		$button_edit=" ".button("{bubble_rule}","Loadjs('$page?js-port={$_GET["listen-port"]}');",16);
	}
	
	$html="
	<table style='width:100%'>
	<tr>
	<td>$button $global_parameters $categories_group $compile_rules</td>
	<td></td>
	</tr>
	</table>
	$SearchQuery
	<script>
	ExecuteByClassName('SearchFunction');
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function rules_search(){
	
		$tpl=new templates();
		$MyPage=CurrentPageName();
		$page=CurrentPageName();
		$rule_text=$tpl->_ENGINE_parse_body("{rule}");
		$action_delete_rule=$tpl->javascript_parse_text("{action_delete_rule}");
		
		$q=new mysql_squid_builder();
		if(!$q->FIELD_EXISTS("webfilter_rules", "zOrder")){$q->QUERY_SQL("ALTER TABLE `webfilter_rules` ADD `zOrder` SMALLINT(2) NOT NULL,ADD INDEX ( `zOrder` )");}
		if(!$q->ok){json_error_show("$q->mysql_error");}
	
		if(!$q->FIELD_EXISTS("webfilter_rules", "AllSystems")){$q->QUERY_SQL("ALTER TABLE `webfilter_rules` ADD `AllSystems` smallint(1),ADD INDEX ( `AllSystems` )");}
		if(!$q->ok){json_error_show("$q->mysql_error");}
	
		$t=$_GET["t"];
		$search='%';
		$table="webfilter_rules";
		$page=CurrentPageName();
		$FORCE_FILTER=null;
		$total=0;
	
		if(!$q->TABLE_EXISTS($table)){$q->CheckTables();}
	
		$searchstring=string_to_flexquery("rules-search");
		$webfilter=new webfilter_rules();
	
		$styleTD="style='font-size:16px;font-weight:bold'";
		$styleTDCenter="style='font-size:16px;font-weight:bold;text-align:center !important'";
	
		$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER ORDER by zOrder";
		$results = $q->QUERY_SQL($sql);
		
		if(!$q->ok){senderror("$q->mysql_error");}
		
		$sock=new sockets();
		$ligne=unserialize(base64_decode($sock->GET_INFO("DansGuardianDefaultMainRule")));
		$DefaultPosition=$ligne["defaultPosition"];
		if(!is_numeric($DefaultPosition)){$DefaultPosition=0;}		
	
		$AllSystems=$tpl->_ENGINE_parse_body("{AllSystems}");
		$boot=new boostrap_form();
		
		if($DefaultPosition==0){$tr[]=DefaultRule();}

	while ($ligne = mysql_fetch_assoc($results)) {
			$ID=$ligne["ID"];
			$md5=md5($ligne["ID"]);
			$ligne["groupname"]=utf8_encode($ligne["groupname"]);
			$delete=imgtootltip("delete-24.png","{delete}","DansGuardianDeleteMainRule('{$ligne["ID"]}')");

			$js="DansGuardianEditRule('{$ligne["ID"]}','{$ligne["groupname"]}');";
			
			$link=$boot->trswitch($js);
			$link_blacklist=$boot->trswitch("Loadjs('dansguardian2.edit.php?js-blacklist-list=yes&RULEID={$ligne['ID']}&modeblk=0&group=&TimeID=&t=$t');");
			$link_whitelist=$boot->trswitch("Loadjs('dansguardian2.edit.php?js-blacklist-list=yes&RULEID={$ligne['ID']}&modeblk=1&group=&TimeID=&t=$t');");
			$link_group=$boot->trswitch("Loadjs('dansguardian2.edit.php?js-groups={$ligne["ID"]}&ID={$ligne["ID"]}&t=$t')");
			
			
			$TimeSpace=$webfilter->TimeToText(unserialize(base64_decode($ligne["TimeSpace"])));
			$color="black";
			if($ligne["enabled"]==0){$color="#8a8a8a";}
			$rules_dans_time_rule=$webfilter->rules_dans_time_rule($ligne["ID"]);
			if($ligne["groupmode"]==0){$warn="<div style='float:right'><img src='img/stop-24.png'></div>";}
			$duplicate=imgsimple("duplicate-24.png",null,"Loadjs('dansguardian2.duplicate.php?from={$ligne['ID']}&t=$t')");
			$TimeSpace=$webfilter->rule_time_list_explain($ligne["TimeSpace"],$ligne["ID"],$t);
	
			$styleupd="style='border:0px;margin:0px;padding:0px;background-color:transparent'";
			$up=imgsimple("arrow-up-32.png","","RuleDansUpDown('{$ligne['ID']}',1)");
			$down=imgsimple("arrow-down-32.png","","RuleDansUpDown('{$ligne['ID']}',0)");
			$zorder="<table $styleupd><tr><td $styleupd>$down</td $styleupd><td $styleupd>$up</td></tr></table>";
	
	
			$CountDeGroups="&laquo;&nbsp;".$webfilter->COUNTDEGROUPES($ligne["ID"])."&nbsp;&raquo;";
	
			
			$templatejs=$boot->trswitch("Loadjs('dansguardian.template.php?js=yes&ID={$ligne["ID"]}')");
			
			if($ligne["AllSystems"]==1){
						$jsGroups="*";
						$CountDeGroups="*";
			}
	
			$tr[]="
			<tr id='{$ligne['ID']}'>
			<td $styleTD $link width=99% ><span id='anim-img-0'>{$ligne["groupname"]}</span> $TimeSpace</td>
			<td $styleTDCenter $link_group width=1% align=center>$CountDeGroups</td>
			<td $styleTDCenter $link_blacklist width=1% align=center>".$webfilter->COUNTDEGBLKS($ligne['ID'])."</td>
			<td $styleTDCenter $link_whitelist width=1% align=center>".$webfilter->COUNTDEGBWLS($ligne['ID'])."</td>
			<td $styleTDCenter width=35px align=center nowrap>$zorder</td>
			<td $styleTDCenter $templatejs width=35px align=center nowrap><img src='img/banned-template-32.png'></td>
			<td $styleTDCenter width=35px align=center nowrap>$duplicate</td>
			<td width=35px align='center' nowrap $styleTDCenter>$delete</td>
			</tr>";

	}
	
	if($DefaultPosition==1){$tr[]=DefaultRule();}

	$table=$tpl->_ENGINE_parse_body("
			<table class='table table-bordered table-hover'>
			<thead>
			<tr>
			<th>{rule}</th>
			<th>{groups2}</th>
			<th>{blacklists}</th>
			<th>{whitelists}</th>
			<th>{order}</th>
			<th>{template}</th>
			<th>&nbsp;</th>
			<th>&nbsp;</th>
			</tr>
			</thead>
			<tbody>
			").@implode("\n", $tr);
	
$js="
<script>			
function DansGuardianNewRule(){
		DansGuardianEditRule(-1)
	}

	function DansGuardianEditRule(ID,rname){
		YahooWin3('935','dansguardian2.edit.php?ID='+ID+'&t=$t','$rule_text::'+ID+'::'+rname);
	}
	
	function CompileUfdbGuardRules(){
		Loadjs('dansguardian2.compile.php');
	}
	
	function UfdbGuardConfigs(){
		Loadjs('ufdbguard.php');
	}
	
	function UfdbguardEvents(){
		Loadjs('dansguardian2.mainrules.php?UfdbguardEvents=yes');
	}
	var x_RuleDansUpDown$t= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		ExecuteByClassName('SearchFunction');
	}	

		
	function RuleDansUpDown(ID,dir){
		var XHR = new XHRConnection();
		XHR.appendData('rule-move', ID);
		XHR.appendData('rule-dir', dir);
		XHR.sendAndLoad('dansguardian2.mainrules.php', 'POST',x_RuleDansUpDown$t);	
	}
	

	
		var x_DansGuardianDeleteMainRule= function (obj) {
			var res=obj.responseText;
			if (res.length>3){alert(res);}
			$('#'+rowid).remove();
		}		
		
		function DansGuardianDeleteMainRule(ID){
			rowid=ID;
			if(confirm('$action_delete_rule')){
				var XHR = new XHRConnection();
		     	XHR.appendData('DansGuardianDeleteMainRule', ID);
		      	XHR.sendAndLoad('dansguardian2.mainrules.php', 'POST',x_DansGuardianDeleteMainRule);  
			}
		}
		
		function RulesToolBox(){
			LoadAjaxTiny('rules-toolbox','dansguardian2.mainrules.php?rules-toolbox=yes');
		}
	
	RulesToolBox();	
	LoadAjaxTiny('rules-toolbox-left','dansguardian2.mainrules.php?rules-toolbox-left=yes');
	
</script>";

echo $table."\n".$js;
	
	}

function DefaultRule(){

	
	$t=$_GET["t"];
	$webfilter=new webfilter_rules();
	$boot=new boostrap_form();
	$styleTD="style='font-size:16px;font-weight:bold'";
	$styleTDCenter="style='font-size:16px;font-weight:bold;text-align:center !important'";
	
	$js="DansGuardianEditRule('0','default')";
	$jsblack="<a href=\"javascript:blur();\"
		OnClick=\"javascript:document.getElementById('anim-img-0').innerHTML='<img src=img/wait.gif>';\"
		style='text-decoration:underline;font-weight:bold'>";
	
	$delete="&nbsp;";
	$duplicate=imgsimple("duplicate-24.png",null,"Loadjs('dansguardian2.duplicate.php?default-rule=yes&t=$t')");
	$sock=new sockets();
	$ligne=unserialize(base64_decode($sock->GET_INFO("DansGuardianDefaultMainRule")));
	$TimeSpace=$webfilter->rule_time_list_explain($ligne["TimeSpace"],0,$t);
	
	
	$link=$boot->trswitch($js);
	$link_blacklist=$boot->trswitch("Loadjs('dansguardian2.edit.php?js-blacklist-list=yes&RULEID=0&modeblk=0&group=&TimeID=&t=$t');");
	$link_whitelist=$boot->trswitch("Loadjs('dansguardian2.edit.php?js-blacklist-list=yes&RULEID=0&modeblk=1&group=&TimeID=&t=$t');");
	$templatejs=$boot->trswitch("Loadjs('dansguardian.template.php?js=yes&ID=0')");
	return "
	<tr id='0'>
	<td $styleTD $link width=99%><span id='anim-img-0'>Default</span> $TimeSpace ".$webfilter->rules_dans_time_rule(0)."</td>
	<td $styleTDCenter $link width=1% align=center >*</td>
	<td $styleTDCenter $link_blacklist width=1% align=center>".$webfilter->COUNTDEGBLKS(0)."</td>
	<td $styleTDCenter $link_whitelist width=1% align=center>".$webfilter->COUNTDEGBWLS(0)."</td>
	<td>&nbsp;</td>
	<td $styleTDCenter $templatejs width=35px align=center nowrap><img src='img/banned-template-32.png'></td>
	<td width=35px align=center $styleTDCenter>$duplicate</td>
	<td>&nbsp;</td>
	</tr>";	
	
}