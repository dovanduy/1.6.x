<?php

	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["ICON_FAMILY"]="ANTISPAM";
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');

	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}

	if(isset($_POST["MimeDefangFileHostingSubjectPrepend"])){options_save();exit;}
	if(isset($_GET["rulemd5"])){main_rule();exit;}
	if(isset($_GET["items-rules"])){items();exit;}
	if(isset($_GET["diclaimers-rule"])){disclaimer_rule();exit;}

	if(isset($_GET["options"])){options();exit;}
	
	if(isset($_POST["mailfrom"])){filehosting_rule_add();exit;}
	if(isset($_POST["del-zmd5"])){autocompress_rule_delete();exit;}
	popup();

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=300;
	$TB_WIDTH=880;
	
	
	$t=time();
	$new_entry=$tpl->_ENGINE_parse_body("{new_rule}");
	$from=$tpl->_ENGINE_parse_body("{sender}");
	$to=$tpl->_ENGINE_parse_body("{recipients}");
	$title=$tpl->_ENGINE_parse_body("{rules}:&nbsp;&laquo;{mimedefang_filehosting}&raquo;");
	$rules=$tpl->_ENGINE_parse_body("{rules}");
	$ask_delete_rule=$tpl->javascript_parse_text("{delete_this_rule}");
	$enable=$tpl->_ENGINE_parse_body("{enable}");
	$compile_rules=$tpl->_ENGINE_parse_body("{compile_rules}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	$options=$tpl->_ENGINE_parse_body("{options}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$buttons="
	buttons : [
	{name: '$new_entry', bclass: 'Add', onpress : NewGItem$t},
	{name: '$compile_rules', bclass: 'Reconf', onpress : MimeDefangCompileRules},
	{name: '$options', bclass: 'Settings', onpress : Options$t},
	{name: '$items', bclass: 'Db', onpress : ShowTable$t},
	{name: '$online_help', bclass: 'Help', onpress : ItemHelp$t},
	
	],	";
	
	$explain=$tpl->_ENGINE_parse_body("{mimedefang_filehosting_explain}");
	$html="
	
	<div class=text-info style='font-size:14px'>$explain</div>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?items-rules=yes&t=$t',
	dataType: 'json',
	colModel : [	
		{display: '$from', name : 'mailfrom', width :224, sortable : true, align: 'left'},
		{display: '$to', name : 'mailto', width :224, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'explain', width :334, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'action', width :31, sortable : false, align: 'center'},

	],
	$buttons

	searchitems : [
		{display: '$from', name : 'mailfrom'},
		{display: '$to', name : 'mailto'},

	],
	sortname: 'mailfrom',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

function ItemHelp$t(){
	s_PopUpFull('http://www.mail-appliance.org/index.php?cID=339','1024','900');
}


function ShowTable$t(){
	Loadjs('mimedefang.filehosting.table.php');
}



var x_NewGItem$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);}
    $('#flexRT$t').flexReload();
}

function NewGItem$t(){
	YahooWin('650','$page?rulemd5=&t=$t','$new_entry');
	
}
function GItem$t(zmd5,ttile){
	YahooWin('650','$page?rulemd5='+zmd5+'&t=$t',ttile);
	
}

function Options$t(){
	YahooWin3('650','$page?options=yes','$options');
}

var x_DeleteFileHosting$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);return;}
    $('#rowD'+mem$t).remove();
}

function GroupAmavisExtEnable(id){
	var value=0;
	if(document.getElementById('gp'+id).checked){value=1;}
 	var XHR = new XHRConnection();
    XHR.appendData('enable-gp',id);
    XHR.appendData('value',value);
    XHR.sendAndLoad('$page', 'POST',x_NewGItem$t);		
}


function DeleteFileHosting$t(md5){
	if(confirm('$ask_delete_rule')){
		mem$t=md5;
 		var XHR = new XHRConnection();
      	XHR.appendData('del-zmd5',md5);
      	XHR.sendAndLoad('$page', 'POST',x_DeleteFileHosting$t);		
	
	}

}

</script>";
	
	echo $html;
}

function items(){
	//1.4.010916
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
		
	
	$search='%';
	$table="mimedefang_filehosting";
	$database="artica_backup";
	$page=1;
	$FORCE_FILTER="";
	

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);}	
	
	$explainT=$tpl->_ENGINE_parse_body("{mimedefang_filehosting_explainrow}");
	
	while ($ligne = mysql_fetch_assoc($results)) {
	$zmd5=$ligne["zmd5"];

	
	$delete=imgsimple("delete-24.png","","DeleteFileHosting$t('$zmd5')");
	
	$urljs="<a href=\"javascript:blur();\" OnClick=\"javascript:GItem$t('$zmd5','{$ligne["mailfrom"]}&nbsp;&raquo;&nbsp;{$ligne["mailto"]}');\"
	style='font-size:16px;color:$color;text-decoration:underline'>";
	
	$explain=str_replace("%f", $ligne["mailfrom"], $explainT);
	$explain=str_replace("%t", $ligne["mailto"], $explain);
	$explain=str_replace("%s", $ligne["maxsize"], $explain);
	
	
	$data['rows'][] = array(
		'id' => "D$zmd5",
		'cell' => array(
			"<span style='font-size:16px;color:$color'>$urljs{$ligne["mailfrom"]}</a></span>",
			"<span style='font-size:18px;color:$color'>$urljs{$ligne["mailto"]}</a></span>",
			"<span style='font-size:12px;color:$color'>$explain</a></span>",
			"<span style='font-size:16px;color:$color'>$delete</a></span>",
			)
		);
	}
	
	
echo json_encode($data);	
	
}

function disclaimer(){
	
	$zmd5=$_GET["disclaimer"];
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$t=$_GET["t"];

	
	
	$array["diclaimers-rule"]='{rule}';
	
	while (list ($num, $ligne) = each ($array) ){

		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&zmd5=$zmd5&t=$t\"><span style='font-size:14px'>$ligne</span></a></li>\n");
	}
	
	
	$width="750px";
	$height="600px";
	$width="100%";$height="100%";
	
	echo "
	<div id=main_config_mimedefang_autozip style='width:{$width};height:{$height};overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_mimedefang_autozip').tabs();
			
			
			});
		</script>";		
}

function main_rule(){
	$t=$_GET["t"];
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql();
	$btname=button("{add}","Addisclaimer$t();","18px");
	$zmd5=$_GET["rulemd5"];
	
	if($zmd5<>null){
		$btname=null;
		$sql="SELECT * FROM mimedefang_filehosting WHERE zmd5='$zmd5'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	}
	
	
	$html="
	<input type='hidden' id='uncompress-$t' value='0'>
	<div id='$t-adddis'></div>
	 <table style='width:99%' class=form>
	 <tr>
	 	<td class=legend style='font-size:16px'>{sender}:</td>
	 	<td>". Field_text("mailfrom-$t",$ligne["mailfrom"],"font-size:16px;width:310px")."</td>
	 </tr>
	 <tr>
	 	<td class=legend style='font-size:16px'>{recipient}:</td>
	 	<td>". Field_text("mailto-$t",$ligne["mailto"],"font-size:16px;width:310px",null,null,null,false,"AddisclaimerC$t(event)")."</td>
	 </tr>	
	 <tr>
	 	<td class=legend style='font-size:16px'>{maxsize}:</td>
	 	<td style='font-size:16px'>". Field_text("maxsize-$t",$ligne["maxsize"],"font-size:16px;width:60px",null,null,null,false,"AddisclaimerC$t(event)")."&nbsp;M</td>
	 </tr> 		 
	<tr>
		<td colspan=2 align='right'><hr>$btname</td>
	</tr>
	</table>
	<script>
		var x_Addisclaimer$t= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			document.getElementById('$t-adddis').innerHTML='';
			$('#flexRT$t').flexReload();
			YahooWinHide();
		}		

		function AddisclaimerC$t(e){
			if(checkEnter(e)){Addisclaimer$t();}
		}
	
		function Addisclaimer$t(){
		var XHR = new XHRConnection();  
		  XHR.appendData('zmd5','$zmd5');
		  var uncompress=0;
	      XHR.appendData('mailfrom',document.getElementById('mailfrom-$t').value);
	      XHR.appendData('mailto',document.getElementById('mailto-$t').value);
	      XHR.appendData('maxsize',document.getElementById('maxsize-$t').value);
	      XHR.appendData('uncompress',uncompress);
		  AnimateDiv('$t-adddis');
		  XHR.sendAndLoad('$page', 'POST',x_Addisclaimer$t);
		}
		
		function AddisclaimerCheck$t(){
			var zmd5='$zmd5';
			if(zmd5.length>5){
				document.getElementById('mailfrom-$t').disabled=true;
				document.getElementById('mailto-$t').disabled=true;
				document.getElementById('maxsize-$t').disabled=true;
				document.getElementById('uncompress-$t').disabled=true;
			}
		}
		

	AddisclaimerCheck$t();
	</script>";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function autocompress_rule_delete(){
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM mimedefang_filehosting WHERE zmd5='{$_POST["del-zmd5"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
}

function filehosting_rule_add(){
	$tpl=new templates();
	$_POST["mailfrom"]=trim(strtolower($_POST["mailfrom"]));
	$_POST["mailto"]=trim(strtolower($_POST["mailto"]));
	if($_POST["mailto"]==null){$_POST["mailto"]="*";}
	if($_POST["mailfrom"]==null){echo $tpl->javascript_parse_text("{please_define_sender}");return;}
	$zmd5=md5($_POST["mailfrom"].$_POST["mailto"]);
	$q=new mysql();
	$q->QUERY_SQL("INSERT IGNORE INTO mimedefang_filehosting (zmd5,mailfrom,mailto,maxsize) 
	VALUES ('$zmd5','{$_POST["mailfrom"]}','{$_POST["mailto"]}','{$_POST["maxsize"]}')","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}

}

function options(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$MimeDefangFileHostingSubjectPrepend=$sock->GET_INFO("MimeDefangFileHostingSubjectPrepend");
	$MimeDefangFileHostingLink=$sock->GET_INFO("MimeDefangFileHostingLink");
	$MimeDefangFileHostingText=$sock->GET_INFO("MimeDefangFileHostingText");
	$MimeDefangFileHostingExternMySQL=$sock->GET_INFO("MimeDefangFileHostingExternMySQL");
	$MimeDefangFileHostingMySQLsrv=$sock->GET_INFO("MimeDefangFileHostingMySQLsrv");
	$MimeDefangFileHostingMySQLusr=$sock->GET_INFO("MimeDefangFileHostingMySQLusr");
	$MimeDefangFileHostingMySQLPass=$sock->GET_INFO("MimeDefangFileHostingMySQLPass");
	$MimeDefangFileMaxDaysStore=$sock->GET_INFO("MimeDefangFileMaxDaysStore");
	$MimeDefangFileHostingText=stripslashes($MimeDefangFileHostingText);
	if($MimeDefangFileHostingText==null){
		$MimeDefangFileHostingText="The attached file %s exceed the company's messaging rule.\nIt has been moved to our Web server for %d days.\nYou can download it by clicking on the link bellow.";}
		
	if(!is_numeric($MimeDefangFileMaxDaysStore)){$MimeDefangFileMaxDaysStore=5;}		
	
	if($MimeDefangFileHostingLink==null){$MimeDefangFileHostingLink="http://".$_SERVER["SERVER_NAME"];}
	$html="
	<div id='$t-adddis'></div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{prepend_subject}:</td>
		<td>". Field_text("MimeDefangFileHostingSubjectPrepend","$MimeDefangFileHostingSubjectPrepend","font-size:14px;width:220px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{link}:</td>
		<td>". Field_text("MimeDefangFileHostingLink","$MimeDefangFileHostingLink","font-size:14px;width:350px")."</td>
	</tr>	
	<tr>
	<td colspan=2>
		<textarea 
			style='margin-top:5px;font-family:Courier New;font-weight:bold;
			width:100%;height:120px;border:5px solid #8E8E8E;overflow:auto;
			margin-bottom:8px;font-size:14px' id='textcontent$t'>$MimeDefangFileHostingText</textarea>	
	</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{MaxDays}:</td>
		<td>". Field_text("MimeDefangFileMaxDaysStore","$MimeDefangFileMaxDaysStore","font-size:14px;width:60px")."</td>
	</tr>		
	
	
	<tr>
		<td class=legend style='font-size:14px'>{use_external_mysql_server}:</td>
		<td>". Field_checkbox("MimeDefangFileHostingExternMySQL", 1,"$MimeDefangFileHostingExternMySQL","MimeDefangFileHostingExternMySQLCheck()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{mysql_server}:</td>
		<td>". Field_text("MimeDefangFileHostingMySQLsrv","$MimeDefangFileHostingMySQLsrv","font-size:14px;width:220px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{mysql_username}:</td>
		<td>". Field_text("MimeDefangFileHostingMySQLusr","$MimeDefangFileHostingMySQLusr","font-size:14px;width:220px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{mysql_password}:</td>
		<td>". Field_password("MimeDefangFileHostingMySQLPass","$MimeDefangFileHostingMySQLPass","font-size:14px;width:220px")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}", "SaveSet$t()","18px")."</td>
	</tr>
	</table>			
	<script>
		var x_SaveDiclaimerText$t= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			document.getElementById('$t-adddis').innerHTML='';
			
		}		
	
		function SaveSet$t(){
		var XHR = new XHRConnection();  
		  var MimeDefangFileHostingExternMySQL=0;
		  if(document.getElementById('MimeDefangFileHostingExternMySQL').checked){MimeDefangFileHostingExternMySQL=1;}
		  XHR.appendData('MimeDefangFileHostingSubjectPrepend',document.getElementById('MimeDefangFileHostingSubjectPrepend').value);
	      XHR.appendData('MimeDefangFileHostingText',document.getElementById('textcontent$t').value);
	      XHR.appendData('MimeDefangFileHostingLink',document.getElementById('MimeDefangFileHostingLink').value);
	      XHR.appendData('MimeDefangFileHostingMySQLsrv',document.getElementById('MimeDefangFileHostingMySQLsrv').value);
	      XHR.appendData('MimeDefangFileHostingMySQLusr',document.getElementById('MimeDefangFileHostingMySQLusr').value);
	      XHR.appendData('MimeDefangFileMaxDaysStore',document.getElementById('MimeDefangFileMaxDaysStore').value);
	      var pp=encodeURIComponent(document.getElementById('MimeDefangFileHostingMySQLPass').value);
	      XHR.appendData('MimeDefangFileHostingMySQLPass',pp);
	      XHR.appendData('MimeDefangFileHostingExternMySQL',MimeDefangFileHostingExternMySQL);
		  AnimateDiv('$t-adddis');
		  XHR.sendAndLoad('$page', 'POST',x_SaveDiclaimerText$t);
		}	
		
		function MimeDefangFileHostingExternMySQLCheck(){
			document.getElementById('MimeDefangFileHostingMySQLPass').disabled=true;
			document.getElementById('MimeDefangFileHostingMySQLsrv').disabled=true;
			document.getElementById('MimeDefangFileHostingMySQLusr').disabled=true;
			if(document.getElementById('MimeDefangFileHostingExternMySQL').checked){
				document.getElementById('MimeDefangFileHostingMySQLPass').disabled=false;
				document.getElementById('MimeDefangFileHostingMySQLsrv').disabled=false;
				document.getElementById('MimeDefangFileHostingMySQLusr').disabled=false;			
			}
		
		}
		MimeDefangFileHostingExternMySQLCheck();
		
	</script>	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
function options_save(){
	
	$sock=new sockets();
	while (list ($key, $value) = each ($_POST) ){
		$sock->SET_INFO($key, $value);
	}
	
}


