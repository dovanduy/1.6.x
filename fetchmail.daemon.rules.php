<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');
	include_once('ressources/class.fetchmail.inc');
	
	
$usersmenus=new usersMenus();
if($usersmenus->AsPostfixAdministrator==false){header('location:users.index.php');exit;}
if(isset($_POST["DeleteRule"])){rule_delete();exit;}
if(isset($_GET["Showlist"])){echo section_rules_list();exit;}
if(isset($_GET["search-list"])){echo section_rules_search();exit;}

if(isset($_GET["ajax"])){ajax_index();exit;}
if(isset($_GET["fetchmail-daemon-rules"])){echo section_rules_list();exit;}
if(isset($_POST["enabled"])){rule_enable();exit;}
if(isset($_POST["DeleteAll"])){rule_delete_all();exit;}
section_Fetchmail_Daemon();



function section_Fetchmail_Daemon(){
		
	$yum=new usersMenus();
	$tpl=new templates();
	$ini=new Bs_IniHandler();
	$sock=new sockets();		
	$title="{fetchmail_rules}";
	$add_fetchmail=Paragraphe('add-fetchmail-64.png','{add_new_fetchmail_rule}','{fetchmail_explain}',"javascript:add_fetchmail_rules()",null,340);

	$ini->loadString($sock->getfile('fetchmailstatus'));
	$status=DAEMON_STATUS_ROUND("FETCHMAIL",$ini,null);
	$status=$tpl->_ENGINE_parse_body($status);	
	$html="<table style='width:600px'>
		<tr>
			<td valign='top' width=1%><img src='img/bg_fetchmail2.jpg'>
			<td valign='top' align='right'><div style='width:350px'>$status <br> $add_fetchmail</div></td>
		</tr>
		<td colspan=2>
			<div id='fetchmail_daemon_rules'></div>
		</td>
		</tr>			
		</table>
		<script>LoadAjax('fetchmail_daemon_rules','fetchmail.daemon.rules.php?Showlist=yes');</script>";
					
	
	$tpl=new template_users($title,$html,0,0,0,0);
	echo $tpl->web_page;		
	
}
	
	
function ajax_index(){
	$page=CurrentPageName();
	$html="
	<div id='fetchmail_daemon_rules' style='width:99.5%;height:600px;overflow:auto'></div>
	<script>
		LoadAjax('fetchmail_daemon_rules','$page?fetchmail-daemon-rules=yes');
	</script>
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}
	
	
function section_rules_list(){
	
	if($_GET["tab"]==1){section_config();exit;}
	

	
	
	$page=CurrentPageName();
	$tpl=new templates();
	$member=$tpl->_ENGINE_parse_body("{member}");
	$server=$tpl->_ENGINE_parse_body("{server}");
	$proto=$tpl->_ENGINE_parse_body("{proto}");
	$uri=$tpl->_ENGINE_parse_body("{url}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$user=$tpl->_ENGINE_parse_body("{user}");
	$title=$tpl->_ENGINE_parse_body("{today}: {realtime_requests} ".date("H")."h");
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$delete_rule=$tpl->javascript_parse_text("{delete_rule}");
	$refresh=$tpl->_ENGINE_parse_body("{refresh}");
	$deleteAll=$tpl->_ENGINE_parse_body("{delete_all}");
	$apply=$tpl->_ENGINE_parse_body("{apply_parameters}");
	$t=time();
	
	
	$import=$tpl->_ENGINE_parse_body("{import}");
	
	
	$buttons="
	buttons : [
	{name: '$new_rule', bclass: 'Add', onpress : add_fetchmail_rules$t},
	{name: '$import', bclass: 'Copy', onpress : ImportBulk$t},
	{name: '$deleteAll', bclass: 'Delz', onpress : DeletAll$t},
	{name: '$refresh', bclass: 'Reload', onpress : Reload$t},
	{name: '$apply', bclass: 'Reconf', onpress : ApplyParams$t},
	
		],	";		
	
	
	$html="
	<div>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	</div>
	
<script>
var fetchid=0;
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?search-list=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$member', name : 'uid', width :169, sortable : true, align: 'left'},
		{display: '$server', name : 'poll', width :169, sortable : false, align: 'left'},
		{display: '$proto', name : 'proto', width : 78, sortable : false, align: 'center'},
		{display: '$user', name : 'user', width : 169, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'enabled', width : 31, sortable : true, align: 'left'},
		{display: 'DEL', name : 'DEL', width : 45, sortable : false, align: 'left'},
		],$buttons
	
	searchitems : [
		{display: '$member', name : 'uid'},
		{display: '$server', name : 'poll'},
		{display: '$user', name : 'user'},
		],
	sortname: 'uid',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 860,
	height: 408,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

function ImportBulk$t(){
	Loadjs('fetchmail.import.php?t=$t');
}

function UserFetchMailRule$t(num,userid){
	if(document.getElementById('dialog3_c')){
        if(document.getElementById('dialog3_c').style.visibility=='visible'){
	            YahooWin4('923','artica.wizard.fetchmail.php?LdapRules='+ num + '&uid='+ userid+'&t=$t',userid+'&raquo;&raquo;'+num);
	            return true;
	        }
		}
       	YahooWin2('923','artica.wizard.fetchmail.php?LdapRules='+ num + '&uid='+ userid+'&t=$t',userid+'&raquo;&raquo;'+num);
        }
        
var x_DeleteFetchmailRule= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
   	$('#rowfetch'+fetchid).remove();
	}        
        
  function DeleteFetchmailRule(ID){
  	if(confirm('$delete_rule '+ID+' ?')){
  		fetchid=ID;
	    var XHR = new XHRConnection();
        XHR.appendData('DeleteRule',ID);
		XHR.sendAndLoad('$page', 'POST',x_DeleteFetchmailRule);  	
  	}
  
  }
  
	var x_DeletAll$t= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
		Reload$t();
	}    
  
  function DeletAll$t(){
  	if(confirm('$deleteAll ?')){
   	 var XHR = new XHRConnection();
  	 XHR.appendData('DeleteAll','yes');
  	 XHR.sendAndLoad('$page', 'POST',x_DeletAll$t);   	
  	}
  }
  
  function ApplyParams$t(){
  	Loadjs('fetchmail.compile.progress.php?t=$t');
  }
  
  function Reload$t(){
  	$('#flexRT$t').flexReload();
  }
  
	var x_CheckFetchmailRule= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
	}   
  
  function CheckFetchmailRule(md,ID){
  	 var XHR = new XHRConnection();
  	 if(document.getElementById(md).checked){XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
  	 XHR.appendData('ID',ID);
  	 XHR.sendAndLoad('$page', 'POST',x_CheckFetchmailRule);  
  }

function add_fetchmail_rules$t(){
	YahooWinHide();
	YahooWin2('891','artica.wizard.fetchmail.php?AddNewFetchMailRule=yes&t=$t','$new_rule');
}

</script>";
	
	echo $html;
	return;
}

function ApplyParams(){
	
	
	$sock=new sockets();
	$sock->getFrameWork('cmd.php?restart-fetchmail=yes');		
}

function rule_delete(){
	$q=new mysql();
	$sql="DELETE FROM fetchmail_rules WHERE ID={$_POST["DeleteRule"]}";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}

	
}

function rule_delete_all(){
	$q=new mysql();
	$sql="TRUNCATE TABLE fetchmail_rules";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
}

function rule_enable(){
	$q=new mysql();
	$q->QUERY_SQL("UPDATE fetchmail_rules SET enabled={$_POST["enabled"]} WHERE ID={$_POST["ID"]}","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}

}

function section_rules_search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	
	$t=$_GET["t"];
	$search='%';
	$table="fetchmail_rules";
	$page=1;
	$ORDER="ORDER BY zDate DESC";
	
	$total=0;
	if($q->COUNT_ROWS($table,"artica_backup")==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		
	}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){
		$data['rows'][] = array('id' => $ligne[time()+1],'cell' => array($q->mysql_error,"", "",""));
		$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));
		echo json_encode($data);
		return;
	}	
	
	//if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$uid=$ligne["uid"];
		$color="black";
		if($ligne["enabled"]==0){$color="#B2B0B0";}
		$js="UserFetchMailRule$t('{$ligne['ID']}','$uid')";
		$href="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:$js\" 
		style='font-size:14px;font-weight:bold;text-decoration:underline;color:$color' >";
		$span="<span style='font-size:14px;color:$color'>";
		if($ligne["proto"]==null){$ligne["proto"]="auto";}
		
		$delete=imgtootltip("delete-24.png","{delete}","DeleteFetchmailRule({$ligne["ID"]})");
		$enable=Field_checkbox(md5($ligne["ID"]), $ligne["enabled"],1,"CheckFetchmailRule('".md5($ligne["ID"])."',{$ligne["ID"]})");
		$ligne["proto"]=strtoupper($ligne["proto"]);
		$data['rows'][] = array(
		'id' => "fetch".$ligne['ID'],
		'cell' => array(
			$span.$href.$uid."</a></span>",
			$span.$href.$ligne["poll"]."</a></span>",
			$span.$ligne["proto"]."</span>",
			$span.$ligne["user"]."</span>",
			"<div style='padding-top:10px'>$enable</div>",
			$delete
			)
		);
	}
	
	
echo json_encode($data);		

}


function section_config(){
	
	$fetch=new fetchmail();
	if(isset($_GET["build"])){
		
		$fetch->Save();
		$fetch=new fetchmail();
	}
	
	$fetchmailrc=$fetch->fetchmailrc;
	$FetchGetLive=$fetch->FetchGetLive;
	$save=Paragraphe('disk-save-64.png','{generate_config}','{generate_config_text}',"javascript:LoadAjax(\"fetchmail_daemon_rules\",\"fetchmail.daemon.rules.php?Showlist=yes&tab=1&build=yes\")");
	
	$fetchmailrc=htmlentities($fetchmailrc);
	$fetchmailrc=nl2br($fetchmailrc);
	
	$FetchGetLive=htmlentities($FetchGetLive);
	$FetchGetLive=nl2br($FetchGetLive);	
	
	$tpl=new templates();
	$html=section_tabs() ."<br><H5>{see_config}</H5><br>
	<table style='width:100%'>
	<tr>
	<td width=75% valign='top'>" . RoundedLightGreen("<code>$fetchmailrc</code>")  ."<br>" . RoundedLightGreen("<code>$FetchGetLive</code>")  . "</td>
	<td valign='top'>$save<br>" . applysettings("fetch") . "</td>
	</tr>
	</table>
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}





function section_tabs(){
	if(!isset($_GET["tab"])){$_GET["tab"]=0;};
	$page=CurrentPageName();
	$array[]='{fetchmail_rules}';
	$array[]='{see_config}';
	
	while (list ($num, $ligne) = each ($array) ){
		if($_GET["tab"]==$num){$class="id=tab_current";}else{$class=null;}
		$html=$html . "<li><a href=\"javascript:LoadAjax('fetchmail_daemon_rules','$page?Showlist=yes&section=yes&tab=$num')\" $class>$ligne</a></li>\n";
			
		}
	return "<div id=tablist>$html</div>";		
}  
	