<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once('ressources/class.system.network.inc');
	if(isset($_GET["org"])){$_GET["ou"]=$_GET["org"];}
	
	if(!PostFixMultiVerifyRights()){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	
	if(isset($_POST["MailFromRandomize"])){params_save();exit;}
	if(isset($_POST["MailBodyRandomize"])){params_save();exit;}	
	
	if(isset($_GET["Params-tabs"])){tabs();exit();}
	if(isset($_GET["popup"])){popup();exit();}
	if(isset($_GET["search"])){search();exit;}
	if(isset($_POST["domainname"])){domainname_add();exit;}
	if(isset($_POST["enable-domain"])){domainname_enable();exit;}
	if(isset($_POST["delete-domain"])){domainname_delete();exit;}
	if(isset($_POST["domainname-deleteall"])){domainname_delete_all();exit;}
	
	if(isset($_GET["mailfrom"])){headers_mailfrom_main();exit;}
	if(isset($_GET["headers-mailfrom-list"])){headers_mailfrom_list();exit;}
	if(isset($_POST["headers-mailfrom-add"])){headers_mailfrom_add();exit;}
	if(isset($_POST["headers-mailfrom-del"])){headers_mailfrom_del();exit;}
	
	if(isset($_GET["software"])){headers_software_main();exit;}
	if(isset($_POST["MailSoftRandomize"])){params_save();exit;}
	if(isset($_GET["headers-mailsoft-list"])){headers_mailsoft_list();exit;}
	if(isset($_POST["headers-mailsoft-add"])){headers_mailsoft_add();exit;}
	if(isset($_POST["headers-mailsoft-del"])){headers_mailsoft_del();exit;}	
	
	if(isset($_GET["helo"])){headers_mailhelo_main();exit;}
	if(isset($_POST["MailHeloRandomize"])){params_save();exit;}
	if(isset($_GET["headers-mailhelo-list"])){headers_mailhelo_list();exit;}
	if(isset($_POST["headers-mailhelo-add"])){headers_mailhelo_add();exit;}
	if(isset($_POST["headers-mailhelo-del"])){headers_mailhelo_del();exit;}		
	
	if(isset($_GET["body"])){headers_mailbody_main();exit;}
	
	
	if(isset($_GET["Params-js"])){params_js();exit;}
	if(isset($_GET["Params-popup"])){params_popup();exit;}
	if(isset($_POST["max_smtp_out"])){params_save();exit;}
	
	if(isset($_GET["js-authenticate"])){authenticate_js();exit;}
	if(isset($_GET["authenticate-popup"])){authenticate_popup();exit;}
	if(isset($_POST["AUTH-USER"])){authenticate_save();exit;}
	
	if(isset($_GET["CopyFrom-js"])){CopyFrom_js();exit;}
	if(isset($_GET["CopyFrom-popup"])){CopyFrom_popup();exit;}
	if(isset($_GET["CopyFrom-search"])){CopyFrom_search();exit;}
	if(isset($_POST["CopyFrom-domain"])){CopyFrom_perform();exit;}
	
	if(isset($_GET["headers"])){headers();exit;}
	if(isset($_GET["bodyreplace-list"])){headers_mailbody_bodyreplace_list();exit;}
	if(isset($_POST["bodyreplace-add"])){headers_mailbody_bodyreplace_add();exit;}
	if(isset($_POST["bodyreplace-del"])){headers_mailbody_bodyreplace_del();exit;}
	
	js();
	
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title="{$_GET["hostname"]}:{advanced_ISP_routing}";
	$title=$tpl->_ENGINE_parse_body($title);
	$html="RTMMail('650','$page?popup=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}','$title');";
	echo $html;
}
function CopyFrom_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title="{$_GET["hostname"]}:{copy_from}";
	$title=$tpl->_ENGINE_parse_body($title);
	$html="YahooWinBrowse('470','$page?CopyFrom-popup=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}','$title');";
	echo $html;
}


function params_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title="{$_GET["hostname"]}:{advanced_ISP_routing}";
	$title=$tpl->_ENGINE_parse_body($title);
	$html="YahooWinBrowse('590','$page?Params-tabs=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}&zmd5={$_GET["zmd5"]}','$title');";
	echo $html;	
	//tabs 
}

function authenticate_js(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$title="{$_GET["hostname"]}:{advanced_ISP_routing}:{authenticate}";
	$title=$tpl->_ENGINE_parse_body($title);
	$html="LoadWinORG2('450','$page?authenticate-popup=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}&zmd5={$_GET["zmd5"]}','$title');";
	echo $html;		
	
	
}

function tabs(){
	$ipaddr=$_GET["ipaddr"];
	$page=CurrentPageName();
	$tpl=new templates();
	$array["Params-popup"]="{parameters}";
	$array["mailfrom"]="{sender_address}";
	$array["software"]="{software_version}";
	$array["helo"]="{helo_header}";
	$array["body"]="BODY";
	
	while (list ($num, $ligne) = each ($array) ){
		
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}&zmd5={$_GET["zmd5"]}\"><span style='font-size:13px'>$ligne</span></a></li>\n");
			continue;
		
	}
	
	
	echo "
	<div id=main_config_postfixadvtabs style='width:100%;height:100%'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
		  $(document).ready(function() {
			$(\"#main_config_postfixadvtabs\").tabs();});
		</script>";	
	
}

function authenticate_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql();
	$sql="SELECT hostname,domainname,params FROM postfix_smtp_advrt WHERE zmd5='{$_GET["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$params=unserialize(base64_decode($ligne["params"]));	
	$t=time();	
	$html="
	<div id='$t'>
	<table style='width:99%' class=form>
	<tbody>
		<tr>
			<td class=legend style='font-size:16px'>{username}:</td>
			<td>". Field_text("username-$t", $params["AUTH-USER"],"font-size:16px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:16px'>{password}:</td>
			<td>". Field_password("password-$t", $params["AUTH-PASS"],"font-size:16px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:16px'>{smtp_server}:</td>
			<td>". Field_text("smtp-$t", $params["AUTH-SMTP"],"font-size:16px")."</td>
		</tr>		
		<tr>
			<td colspan=2 align='right'><hr>". button("{apply}","SaveAuth$t()",16)."</td>
		</tr>		
	</tbody>
	</table>
	</div>
		<script>
	
	
	var x_SaveAuth$t=function (obj) {
			var results=obj.responseText;
			WinORG2Hide();
			RefreshTab('main_config_postfixadvtabs');
		}	
		
		function SaveAuth$t(){
			var XHR = new XHRConnection();
    		XHR.appendData('AUTH-USER',document.getElementById('username-$t').value);
    		XHR.appendData('AUTH-PASS',document.getElementById('password-$t').value);
    		XHR.appendData('AUTH-SMTP',document.getElementById('smtp-$t').value);
			XHR.appendData('hostname','{$_GET["hostname"]}');
			XHR.appendData('ou','{$_GET["ou"]}');
			XHR.appendData('zmd5','{$_GET["zmd5"]}');    		
 			AnimateDiv('$t');
    		XHR.sendAndLoad('$page', 'POST',x_SaveAuth$t);
			
		}		
	
		
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);	
}
function authenticate_save(){
	$q=new mysql();
	$sql="SELECT params FROM postfix_smtp_advrt WHERE zmd5='{$_POST["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$params=unserialize(base64_decode($ligne["params"]));		
	$params["AUTH-USER"]=$_POST["AUTH-USER"];
	$params["AUTH-PASS"]=$_POST["AUTH-PASS"];
	$params["AUTH-SMTP"]=$_POST["AUTH-SMTP"];
	$newval=base64_encode(serialize($params));
	$sql="UPDATE postfix_smtp_advrt SET params='$newval'  WHERE zmd5='{$_POST["zmd5"]}'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}		
}

function headers_mailbody_bodyreplace_add(){
	$q=new mysql();
	$sql="SELECT params FROM postfix_smtp_advrt WHERE zmd5='{$_POST["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$params=unserialize(base64_decode($ligne["params"]));			
	$params["BODY_REPLACE"][$_POST["bodyreplace-add"]]=array();
	$newval=base64_encode(serialize($params));
	$sql="UPDATE postfix_smtp_advrt SET params='$newval'  WHERE zmd5='{$_POST["zmd5"]}'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}		
}
function headers_mailbody_bodyreplace_del(){
	$q=new mysql();
	$sql="SELECT params FROM postfix_smtp_advrt WHERE zmd5='{$_POST["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$params=unserialize(base64_decode($ligne["params"]));			
	unset($params["BODY_REPLACE"][base64_decode($_POST["bodyreplace-del"])]);
	$newval=base64_encode(serialize($params));
	$sql="UPDATE postfix_smtp_advrt SET params='$newval'  WHERE zmd5='{$_POST["zmd5"]}'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}		
}
	


function headers_mailbody_bodyreplace_list(){
	$zmd5=$_GET["zmd5"];
	$q=new mysql();
	$sql="SELECT hostname,domainname,params FROM postfix_smtp_advrt WHERE zmd5='{$_GET["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$params=unserialize(base64_decode($ligne["params"]));

	$BODY_REPLACE=$params["BODY_REPLACE"];
	$c=0;
	$data = array();
	$data['rows'] = array();

	if($_POST["query"]<>null){
		$search=$_POST["query"];
		$search=str_replace(".", "\.", $search);
		$search=str_replace("*", ".*?", $search);
	}	
	
	
	while (list ($keyword, $keyword_array) = each ($BODY_REPLACE) ){
		$color="black";
		$keyword_list=null;
		$tt=array();		
		
		if($search<>null){
			if($_POST["qtype"]=="keyword"){
				if(!preg_match("#$search#", $keyword)){continue;}
			}
		}
		
		if(count($keyword_array)==0){
			if($search<>null){
				$mybreak=true;
				if($_POST["qtype"]=="values"){continue;}
			}
		}
		
		
		$mybreak=false;
		if($search<>null){
			if($_POST["qtype"]=="values"){$mybreak=true;}
		}
		
		
		$md5=md5("{$_GET["zmd5"]}$keyword");
		while (list ($a, $b) = each ($keyword_array) ){
			if($search<>null){
				if($_POST["qtype"]=="values"){
					if(preg_match("#$search#", $a)){$mybreak=false;}
				}
			}			
			
			$tt[]=$a;
		}
		if($mybreak){continue;}
		$keyword_list=@implode(", ", $tt);
		$keyword_bs=base64_encode($keyword);
		$delete=imgsimple("delete-24.png",null,"KeyWordDel('$keyword_bs','$md5')");
		
		$c++;
	$data['rows'][] = array(
		'id' => "$md5",
		'cell' => array("
		<a href=\"javascript:blur();\"  
		OnClick=\"javascript:Loadjs('postfix.isp-routing.ranbody.php?keyword=$keyword_bs&zmd5={$_GET["zmd5"]}&ou={$_GET["ou"]}&hostname={$_GET["hostname"]}&t={$_GET["t"]}');\" 
		style='font-size:16px;text-decoration:underline;color:$color'>$keyword</span>",
		"<span style='font-size:14px;color:$color'>$keyword_list</span>",
		"<span style='font-size:14px;color:$color'>$delete</span>",
		)
		);		
		
	}
	
	if($c==0){json_error_show("No keyword set...");}
	$data['page'] = 1;
	$data['total'] = $c;
	echo json_encode($data);	
	
	
}

function headers_mailbody_main(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql();
	$sql="SELECT hostname,domainname,params FROM postfix_smtp_advrt WHERE zmd5='{$_GET["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$params=unserialize(base64_decode($ligne["params"]));	
	$keyword=$tpl->_ENGINE_parse_body("{keyword}");
	$values=$tpl->_ENGINE_parse_body("{values}");
	$new_keyword=$tpl->_ENGINE_parse_body("{new_keyword}");
	$body_randomize_table=$tpl->_ENGINE_parse_body("{body_randomize_table}");
	$t=time();
	$html="<table style='width:99%' class=form>
	<tbody>
		<tr>
			<td class=legend style='font-size:14px'>{enable_body_randomize}</td>
			<td>". Field_checkbox("MailBodyRandomize", 1,$params["MailBodyRandomize"],"MailBodyRandomizeCheck()")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:14px'>{enable_subject_randomize}</td>
			<td>". Field_checkbox("MailSubjectRandomize", 1,$params["MailSubjectRandomize"],"MailBodyRandomizeCheck()")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:14px'>{enable_body_randomize_replace}</td>
			<td>". Field_checkbox("MailBodyRandomizeReplace", 1,$params["MailBodyRandomizeReplace"],"MailBodyRandomizeCheck()")."</td>
		</tr>					
	</tbody>
	</table>
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
var tmp$t='';
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?bodyreplace-list=yes&t=$t&zmd5={$_GET["zmd5"]}&ou={$_GET["ou"]}&hostname={$_GET["hostname"]}',
	dataType: 'json',
	colModel : [
		{display: '$keyword', name : 'keyword', width : 271, sortable : true, align: 'left'},
		{display: '$values', name : 'values', width : 167, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 31, sortable : false, align: 'left'},
		
		
	],
buttons : [
	{name: '$new_keyword', bclass: 'add', onpress : BodyReplaceNew},

		],	
	searchitems : [
		{display: '$keyword', name : 'keyword'},
		{display: '$values', name : 'values'},
		],
	sortname: 'bweight',
	sortorder: 'asc',
	usepager: true,
	title: '$body_randomize_table',
	useRp: false,
	rp: 15,
	showTableToggleBtn: false,
	width: 550,
	height: 350,
	singleSelect: true
	
	});   
});	
	var x_BodyReplaceNew= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
		$('#table-$t').flexReload();
	}	

	function BodyReplaceNew(){
		var keyword=prompt('$keyword');
		if(keyword){
			var XHR = new XHRConnection();
			XHR.appendData('bodyreplace-add',keyword);
			XHR.appendData('hostname','{$_GET["hostname"]}');
			XHR.appendData('ou','{$_GET["ou"]}');
			XHR.appendData('zmd5','{$_GET["zmd5"]}');
			XHR.sendAndLoad('$page', 'POST',x_BodyReplaceNew);			
		
		}
	
	}
	
	var x_KeyWordDel= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
		$('#row'+tmp$t).remove();
	}		
	
	function KeyWordDel(wrdbs,md){
			tmp$t=md;
			var XHR = new XHRConnection();
			XHR.appendData('bodyreplace-del',wrdbs);
			XHR.appendData('hostname','{$_GET["hostname"]}');
			XHR.appendData('ou','{$_GET["ou"]}');
			XHR.appendData('zmd5','{$_GET["zmd5"]}');
			XHR.sendAndLoad('$page', 'POST',x_KeyWordDel);		
	
	}


	var x_MailBodyRandomizeCheck= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
	}
	
	function MailBodyRandomizeCheck(){
		var XHR = new XHRConnection();
		XHR.appendData('hostname','{$_GET["hostname"]}');
		XHR.appendData('ou','{$_GET["ou"]}');
		XHR.appendData('zmd5','{$_GET["zmd5"]}');
		if(document.getElementById('MailBodyRandomize').checked){XHR.appendData('MailBodyRandomize',1);}else{XHR.appendData('MailBodyRandomize',0);}
		if(document.getElementById('MailSubjectRandomize').checked){XHR.appendData('MailSubjectRandomize',1);}else{XHR.appendData('MailSubjectRandomize',0);}
		if(document.getElementById('MailBodyRandomizeReplace').checked){XHR.appendData('MailBodyRandomizeReplace',1);}else{XHR.appendData('MailBodyRandomizeReplace',0);}
		XHR.sendAndLoad('$page', 'POST',x_MailBodyRandomizeCheck);	
	}
</script>	

	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function headers_software_main(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql();
	$sql="SELECT hostname,domainname,params FROM postfix_smtp_advrt WHERE zmd5='{$_GET["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$params=unserialize(base64_decode($ligne["params"]));		
	
	$html="<table style='width:99%' class=form>
	<tbody>
		<tr>
			<td class=legend style='font-size:14px'>{enable_software_randomize}</td>
			<td>". Field_checkbox("MailSoftRandomize", 1,$params["MailSoftRandomize"],"MailSoftRandomizeCheck()")."</td>
		</tr>
	</tbody>
	</table>
	<div id='headers_mailsoft_main_id' style='width:100%;height:250px;overflow:auto'></div>
	
	
	<script>
		
	var x_MailSoftRandomizeCheck= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		RefreshTab('main_config_postfixadvtabs');
	}
	
	function MailSoftRandomizeCheck(){
		var XHR = new XHRConnection();
		XHR.appendData('hostname','{$_GET["hostname"]}');
		XHR.appendData('ou','{$_GET["ou"]}');
		XHR.appendData('zmd5','{$_GET["zmd5"]}');
		if(document.getElementById('MailSoftRandomize').checked){XHR.appendData('MailSoftRandomize',1);}else{XHR.appendData('MailSoftRandomize',0);}
		XHR.sendAndLoad('$page', 'POST',x_MailSoftRandomizeCheck);	
	}
	
	function MailSoftRandomizeRefresh(){
		LoadAjax('headers_mailsoft_main_id','$page?headers-mailsoft-list=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}&zmd5={$_GET["zmd5"]}');
	
	}
	MailSoftRandomizeRefresh();
	</script>	
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function headers_mailhelo_main(){
$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql();
	$sql="SELECT hostname,domainname,params FROM postfix_smtp_advrt WHERE zmd5='{$_GET["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$params=unserialize(base64_decode($ligne["params"]));		
	
	$html="<table style='width:99%' class=form>
	<tbody>
		<tr>
			<td class=legend style='font-size:14px'>{enable_helo_randomize}</td>
			<td>". Field_checkbox("MailHeloRandomize", 1,$params["MailHeloRandomize"],"MailHeloRandomizeCheck()")."</td>
		</tr>
	</tbody>
	</table>
	<div id='headers_mailhelo_main_id' style='width:100%;height:250px;overflow:auto'></div>
	
	
	<script>
		
	var x_MailHeloRandomizeCheck= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		RefreshTab('main_config_postfixadvtabs');
	}
	
	function MailHeloRandomizeCheck(){
		var XHR = new XHRConnection();
		XHR.appendData('hostname','{$_GET["hostname"]}');
		XHR.appendData('ou','{$_GET["ou"]}');
		XHR.appendData('zmd5','{$_GET["zmd5"]}');
		if(document.getElementById('MailHeloRandomize').checked){XHR.appendData('MailHeloRandomize',1);}else{XHR.appendData('MailHeloRandomize',0);}
		XHR.sendAndLoad('$page', 'POST',x_MailHeloRandomizeCheck);	
	}
	
	function MailHeloRandomizeRefresh(){
		LoadAjax('headers_mailhelo_main_id','$page?headers-mailhelo-list=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}&zmd5={$_GET["zmd5"]}');
	
	}
	MailHeloRandomizeRefresh();
	</script>	
	";
	
	echo $tpl->_ENGINE_parse_body($html);		
}


function headers_mailfrom_main(){
$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql();
	$sql="SELECT hostname,domainname,params FROM postfix_smtp_advrt WHERE zmd5='{$_GET["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$params=unserialize(base64_decode($ligne["params"]));		
	
	$html="<table style='width:99%' class=form>
	<tbody>
		<tr>
			<td class=legend style='font-size:14px'>{enable_from_randomize}</td>
			<td>". Field_checkbox("MailFromRandomize", 1,$params["MailFromRandomize"],"MailFromRandomizeCheck()")."</td>
		</tr>
	</tbody>
	</table>
	<div id='headers_mailfrom_main_id' style='width:100%;height:250px;overflow:auto'></div>
	
	
	<script>
		
	var x_MailFromRandomizeCheck= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		RefreshTab('main_config_postfixadvtabs');
	}
	
	function MailFromRandomizeCheck(){
		var XHR = new XHRConnection();
		XHR.appendData('hostname','{$_GET["hostname"]}');
		XHR.appendData('ou','{$_GET["ou"]}');
		XHR.appendData('zmd5','{$_GET["zmd5"]}');
		if(document.getElementById('MailFromRandomize').checked){XHR.appendData('MailFromRandomize',1);}else{XHR.appendData('MailFromRandomize',0);}
		XHR.sendAndLoad('$page', 'POST',x_MailFromRandomizeCheck);	
	}
	
	function MailFromRandomizeRefresh(){
		LoadAjax('headers_mailfrom_main_id','$page?headers-mailfrom-list=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}&zmd5={$_GET["zmd5"]}');
	
	}
	MailFromRandomizeRefresh();
	</script>	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function headers_mailhelo_list(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql();
	$sql="SELECT hostname,domainname,params FROM postfix_smtp_advrt WHERE zmd5='{$_GET["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$params=unserialize(base64_decode($ligne["params"]));		
	$MAILFROM_RANDOMIZE=$params["MAILHELO_RANDOMIZE"];
	
	$html="
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th width=1%>". imgtootltip("plus-24.png","{add}","MailHeloRandomizeAdd()")."</th>
		<th>{hostname}</th>
		<th width=1%>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>";
	$classtr=null;
	while(list( $num, $soft ) = each ($MAILFROM_RANDOMIZE)){
		if(trim($soft)==null){continue;}
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$html=$html."
		<tr  class=$classtr>
			<td style='font-size:14px;font-weight:bold' width=1%><img src=img/fw_bold.gif></td>
			<td style='font-size:14px;font-weight:bold' width=99%>$soft</a></td>
			<td style='font-size:14px;font-weight:bold' width=1%>". imgtootltip("delete-32.png","{delete} $soft","MailHeloRandomizeDel($num)")."</td>

	</tr>";	
	}
	
	$html=$html."</tbody></table>
	
	<script>
	
	var x_MailHeloRandomizeDel= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		MailHeloRandomizeRefresh();
	}	
	
		function MailHeloRandomizeAdd(){
			var soft=prompt('Helo eg: mx1.domain.tld');
			if(soft){
				var XHR = new XHRConnection();
				XHR.appendData('hostname','{$_GET["hostname"]}');
				XHR.appendData('ou','{$_GET["ou"]}');
				XHR.appendData('zmd5','{$_GET["zmd5"]}');
				XHR.appendData('headers-mailhelo-add',soft);
				XHR.sendAndLoad('$page', 'POST',x_MailHeloRandomizeDel);			
			}
		}
		
		function MailHeloRandomizeDel(index){
				var XHR = new XHRConnection();
				XHR.appendData('hostname','{$_GET["hostname"]}');
				XHR.appendData('ou','{$_GET["ou"]}');
				XHR.appendData('zmd5','{$_GET["zmd5"]}');
				XHR.appendData('headers-mailhelo-del',index);
				XHR.sendAndLoad('$page', 'POST',x_MailHeloRandomizeDel);			
		}		

	</script>
	";
	
echo $tpl->_ENGINE_parse_body($html);	
	
}

function headers_mailsoft_list(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql();
	$sql="SELECT hostname,domainname,params FROM postfix_smtp_advrt WHERE zmd5='{$_GET["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$params=unserialize(base64_decode($ligne["params"]));		
	$MAILFROM_RANDOMIZE=$params["MAILSOFT_RANDOMIZE"];
	
	$html="
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th width=1%>". imgtootltip("plus-24.png","{add}","MailSoftRandomizeAdd()")."</th>
		<th>{softwares}</th>
		<th width=1%>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>";
	$classtr=null;
	while(list( $num, $soft ) = each ($MAILFROM_RANDOMIZE)){
		if(trim($soft)==null){continue;}
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$html=$html."
		<tr  class=$classtr>
			<td style='font-size:14px;font-weight:bold' width=1%><img src=img/fw_bold.gif></td>
			<td style='font-size:14px;font-weight:bold' width=99%>$soft</a></td>
			<td style='font-size:14px;font-weight:bold' width=1%>". imgtootltip("delete-32.png","{delete} $soft","MailSoftRandomizeDel($num)")."</td>

	</tr>";	
	}
	
	$html=$html."</tbody></table>
	
	<script>
	
	var x_MailSoftRandomizeAdd= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		MailSoftRandomizeRefresh();
	}	
	
		function MailSoftRandomizeAdd(){
			var soft=prompt('Version eg: Thunderbird 2.0.0.24 (X11/20110404)');
			if(soft){
				var XHR = new XHRConnection();
				XHR.appendData('hostname','{$_GET["hostname"]}');
				XHR.appendData('ou','{$_GET["ou"]}');
				XHR.appendData('zmd5','{$_GET["zmd5"]}');
				XHR.appendData('headers-mailsoft-add',soft);
				XHR.sendAndLoad('$page', 'POST',x_MailSoftRandomizeAdd);			
			}
		}
		
		function MailSoftRandomizeDel(index){
				var XHR = new XHRConnection();
				XHR.appendData('hostname','{$_GET["hostname"]}');
				XHR.appendData('ou','{$_GET["ou"]}');
				XHR.appendData('zmd5','{$_GET["zmd5"]}');
				XHR.appendData('headers-mailsoft-del',index);
				XHR.sendAndLoad('$page', 'POST',x_MailSoftRandomizeAdd);			
		}		

	</script>
	";
	
echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function headers_mailfrom_list(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql();
	$sql="SELECT hostname,domainname,params FROM postfix_smtp_advrt WHERE zmd5='{$_GET["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$params=unserialize(base64_decode($ligne["params"]));		
	$MAILFROM_RANDOMIZE=$params["MAILFROM_RANDOMIZE"];
	
	$html="
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th width=1%>". imgtootltip("plus-24.png","{add}","MailFromRandomizeAdd()")."</th>
		<th>{email}</th>
		<th width=1%>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>";
	$classtr=null;
	while(list( $num, $email ) = each ($MAILFROM_RANDOMIZE)){
		if(trim($email)==null){continue;}
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$html=$html."
		<tr  class=$classtr>
			<td style='font-size:14px;font-weight:bold' width=1%><img src=img/fw_bold.gif></td>
			<td style='font-size:14px;font-weight:bold' width=99%>$email</a></td>
			<td style='font-size:14px;font-weight:bold' width=1%>". imgtootltip("delete-32.png","{delete} $email","MailFromRandomizeDel($num)")."</td>

	</tr>";	
	}
	
	$html=$html."</tbody></table>
	
	<script>
	
	var x_MailFromRandomizeAdd= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		MailFromRandomizeRefresh();
	}	
	
		function MailFromRandomizeAdd(){
			var email=prompt('Mail:');
			if(email){
				var XHR = new XHRConnection();
				XHR.appendData('hostname','{$_GET["hostname"]}');
				XHR.appendData('ou','{$_GET["ou"]}');
				XHR.appendData('zmd5','{$_GET["zmd5"]}');
				XHR.appendData('headers-mailfrom-add',email);
				XHR.sendAndLoad('$page', 'POST',x_MailFromRandomizeAdd);			
			}
		}
		
		function MailFromRandomizeDel(index){
				var XHR = new XHRConnection();
				XHR.appendData('hostname','{$_GET["hostname"]}');
				XHR.appendData('ou','{$_GET["ou"]}');
				XHR.appendData('zmd5','{$_GET["zmd5"]}');
				XHR.appendData('headers-mailfrom-del',index);
				XHR.sendAndLoad('$page', 'POST',x_MailFromRandomizeAdd);			
		}		

	</script>
	
	";
	
echo $tpl->_ENGINE_parse_body($html);	
	
	
}



function params_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql();
	$sql="SELECT hostname,domainname,params FROM postfix_smtp_advrt WHERE zmd5='{$_GET["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$params=unserialize(base64_decode($ligne["params"]));	
	if(!is_numeric($params["max_msg_per_connection"])){$params["max_msg_per_connection"]=5;}
	if(!is_numeric($params["max_msg_rate"])){$params["max_msg_rate"]=600;}
	if(!is_numeric($params["change_timecode"])){$params["change_timecode"]=1;}
	if(!is_numeric($params["max_smtp_out"])){$params["max_smtp_out"]=5;}
	if(!is_numeric($params["max_cnt_hour"])){$params["max_cnt_hour"]=500;}
	if(!is_numeric($params["wait_xs_per_message"])){$params["wait_xs_per_message"]=0;}
	if(!is_numeric($params["max_msg_rate_timeout"])){$params["max_msg_rate_timeout"]=300;}
	if(!is_numeric($params["CNX_421"])){$params["CNX_421"]=30;}
	if(!is_numeric($params["msgs_ttl"])){$params["msgs_ttl"]=300;}
	if(!is_numeric($params["min_subqueue_msgs"])){$params["min_subqueue_msgs"]=5;}
	if(!is_numeric($params["min_subqueue_msgs_ttl"])){$params["min_subqueue_msgs_ttl"]=30;}
	$lockMX=0;
	if($params["smtp_authenticate"]==1){if($params["AUTH-SMTP"]<>null){$lockMX=1;}}
	
	
	if($params["bounce_from"]==null){$params["bounce_from"]="MAILER-DAEMON";}
	
	

	$ip=new networking();
	$ips=$ip->ALL_IPS_GET_ARRAY();
	$main=new maincf_multi($_GET["hostname"]);
	$ipaddr=$main->ip_addr;		
	
	if(!isset($params["bind_address"])){$params["bind_address"]=$main->ip_addr;}
	$nets=Field_array_Hash($ips,"bind_address",$params["bind_address"],"style:font-size:16px;padding:3px");
	
	$arrayMXS=ResolveMXDomain($ligne["domainname"]);
	$arrayMXS[null]="{automatic}";
	$ForceMX=Field_array_Hash($arrayMXS,"ForceMX",$params["ForceMX"],"style:font-size:16px;padding:3px");
	
	
	$sql="SELECT hostname FROM postfix_smtp_advrt WHERE domainname='{$ligne["domainname"]}' ORDER BY hostname";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	while ($ligne421 = mysql_fetch_assoc($results)) {
		if($ligne421["hostname"]==$ligne["hostname"]){continue;}
		$ligne421AR[$ligne421["hostname"]]=$ligne421["hostname"];
	}
	$ligne421AR[null]="{select}";
	$MoveTo421Field=Field_array_Hash($ligne421AR,"CNX_421_HOST",$params["CNX_421_HOST"],"style:font-size:14px;padding:3px");
	
	
	$html="
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend style='font-size:14px'>{bind_address}:</td>
		<td style='font-size:14px'>$nets&nbsp;{ipaddr}</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{debug}:</td>
		<td style='font-size:14px'>". Field_checkbox("debug_parameters",1,$params["debug_parameters"])."</td>
		<td>". help_icon("{smtp_adv_debug_explain}")."</td>
	</tr>	
	
	
	
	<tr>
		<td class=legend style='font-size:14px'>{forceMX}:</td>
		<td style='font-size:14px'>$ForceMX</td>
		<td width=1%>". help_icon("{smtp_adv_forcemx_explain}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{authenticate}:</td>
		<td style='font-size:14px'>". Field_checkbox("smtp_authenticate",1,$params["smtp_authenticate"])."&nbsp;
		<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$page?js-authenticate=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}&zmd5={$_GET["zmd5"]}')\" style=\"font-size:13px;text-decoration:underline\">{parameters}</a>
		</td>
		<td width=1%>". help_icon("{smtpadv_authenticate_explain}")."</td>
	</tr>					
	<tr>
		<td class=legend style='font-size:14px'>{max_processes}:</td>
		<td style='font-size:14px'>". Field_text("max_smtp_out",$params["max_smtp_out"],"font-size:14px;width:65px")."&nbsp;{processes}</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{min_subqueue_msgs}:</td>
		<td style='font-size:14px'>". Field_text("min_subqueue_msgs",$params["min_subqueue_msgs"],"font-size:14px;width:65px")."&nbsp;{messages}</td>
		<td width=1%>". help_icon("{min_subqueue_msgs_explain}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{min_subqueue_msgs_ttl}:</td>
		<td style='font-size:14px'>". Field_text("min_subqueue_msgs_ttl",$params["min_subqueue_msgs_ttl"],"font-size:14px;width:65px")."&nbsp;{seconds}</td>
		<td width=1%>". help_icon("{min_subqueue_msgs_ttl_explain}")."</td>
	</tr>
	
	<tr>
		<td class=legend style='font-size:14px'>{max_msg_per_connection}:</td>
		<td style='font-size:14px'>". Field_text("max_msg_per_connection",$params["max_msg_per_connection"],"font-size:14px;width:65px")."&nbsp;{messages}</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{wait_xs_per_message}:</td>
		<td style='font-size:14px'>". Field_text("wait_xs_per_message",$params["wait_xs_per_message"],"font-size:14px;width:65px")."&nbsp;{seconds}</td>
		<td width=1%>". help_icon("{wait_xs_per_message_explain}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{max_cnt_hour}:</td>
		<td style='font-size:14px'>". Field_text("max_cnt_hour",$params["max_cnt_hour"],"font-size:14px;width:65px")."&nbsp;{connections}/{hour}</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{aiguil_max_msg_rate}:</td>
		<td style='font-size:14px'>". Field_text("max_msg_rate",$params["max_msg_rate"],"font-size:14px;width:65px")."&nbsp;{seconds}</td>
		<td>". help_icon("{aiguil_max_msg_rate_explain}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{max_msg_rate_timeout}:</td>
		<td style='font-size:14px'>". Field_text("max_msg_rate_timeout",$params["max_msg_rate_timeout"],"font-size:14px;width:65px")."&nbsp;{seconds}</td>
		<td width=1%></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{CNX_421}:</td>
		<td style='font-size:14px'>". Field_text("CNX_421",$params["CNX_421"],"font-size:14px;width:65px")."&nbsp;{minutes}</td>
		<td>". help_icon("{CNX_421_EXPLAIN}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{CNX_421_MOVE_TO}:</td>
		<td style='font-size:14px'>". Field_checkbox("CNX_421_MOVE",1,$params["CNX_421_MOVE"],"Check421Move()")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{CNX_421_MOVE_TO_HOST}:</td>
		<td style='font-size:14px'>$MoveTo421Field</td>
		<td>". help_icon("{CNX_421_MOVE_TO_HOST_EXPLAIN}")."</td>
	</tr>
	
	
	<tr>
		<td class=legend style='font-size:14px'>{msgs_ttl}:</td>
		<td style='font-size:14px'>". Field_text("msgs_ttl",$params["msgs_ttl"],"font-size:14px;width:65px")."&nbsp;{minutes}</td>
		<td>". help_icon("{msgs_ttl_explain}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{bounce_use_instance}:</td>
		<td style='font-size:14px'>". Field_checkbox("bounce_use_instance",1,$params["bounce_use_instance"])."</td>
		<td>". help_icon("{bounce_use_instance_explain}")."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:14px'>{bounce_from}:</td>
		<td style='font-size:14px'>". Field_text("bounce_from",$params["bounce_from"],"font-size:14px;width:220px")."</td>
		<td>". help_icon("{bounce_from_explain}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{bounce_to}:</td>
		<td style='font-size:14px'>". Field_text("bounce_to",$params["bounce_to"],"font-size:14px;width:220px")."</td>
		<td>". help_icon("{bounce_to_explain}")."</td>
	</tr>		
	
	<tr>
		<td class=legend style='font-size:14px'>{change_timecode}:</td>
		<td style='font-size:14px'>". Field_checkbox("change_timecode",1,$params["change_timecode"])."</td>
		<td>". help_icon("{change_timecode_explain}")."</td>
	</tr>	
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","SavePostfixAdvRouting()",14)."</td>
	</tr>
	</tbody>
	</table>	
<script>
	var x_SavePostfixAdvRouting= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		YahooWinBrowseHide();
		FlexReloadAdvPostRout();
	}
	
function SavePostfixAdvRouting(){
	var XHR = new XHRConnection();
	XHR.appendData('hostname','{$_GET["hostname"]}');
	XHR.appendData('ou','{$_GET["ou"]}');
	XHR.appendData('zmd5','{$_GET["zmd5"]}');
	XHR.appendData('max_smtp_out',document.getElementById('max_smtp_out').value);
	XHR.appendData('max_msg_per_connection',document.getElementById('max_msg_per_connection').value);
	XHR.appendData('bind_address',document.getElementById('bind_address').value);
	XHR.appendData('max_cnt_hour',document.getElementById('max_cnt_hour').value);
	XHR.appendData('CNX_421',document.getElementById('CNX_421').value);
	XHR.appendData('msgs_ttl',document.getElementById('msgs_ttl').value);
	XHR.appendData('bounce_from',document.getElementById('bounce_from').value);
	XHR.appendData('bounce_to',document.getElementById('bounce_to').value);
	XHR.appendData('max_msg_rate',document.getElementById('max_msg_rate').value);
	XHR.appendData('min_subqueue_msgs_ttl',document.getElementById('min_subqueue_msgs_ttl').value);
	XHR.appendData('max_msg_rate_timeout',document.getElementById('max_msg_rate_timeout').value);
	XHR.appendData('min_subqueue_msgs',document.getElementById('min_subqueue_msgs').value);
	XHR.appendData('ForceMX',document.getElementById('ForceMX').value);
	XHR.appendData('CNX_421_HOST',document.getElementById('CNX_421_HOST').value);
	if(document.getElementById('change_timecode').checked){XHR.appendData('change_timecode',1);}else{XHR.appendData('change_timecode',0);}
	if(document.getElementById('CNX_421_MOVE').checked){XHR.appendData('CNX_421_MOVE',1);}else{XHR.appendData('CNX_421_MOVE',0);}
	if(document.getElementById('debug_parameters').checked){XHR.appendData('debug_parameters',1);}else{XHR.appendData('debug_parameters',0);}
	if(document.getElementById('bounce_use_instance').checked){XHR.appendData('bounce_use_instance',1);}else{XHR.appendData('bounce_use_instance',0);}
	if(document.getElementById('smtp_authenticate').checked){XHR.appendData('smtp_authenticate',1);}else{XHR.appendData('smtp_authenticate',0);}
	
	
	
	
	XHR.sendAndLoad('$page', 'POST',x_SavePostfixAdvRouting);	
	}
	
	function Check421Move(){
		document.getElementById('CNX_421_HOST').disabled=true;
		if(document.getElementById('CNX_421_MOVE').checked){document.getElementById('CNX_421_HOST').disabled=false;}
	}
	
	function LockMXFields(){
		var lock=$lockMX;
		if(lock==1){
			document.getElementById('ForceMX').disabled=true;
		}
	}
LockMXFields();	
Check421Move();	
</script>		
";
	echo $tpl->_ENGINE_parse_body($html);
	
}

		
function ResolveMXDomain($domain){
			if(trim($domain)==null){return array();}
			getmxrr($domain, $mxhosts,$mxWeight);
			

			
			if(!is_array($mxWeight)){
				if(!is_array($mxhosts)){return null;}
				$Xmxs[0]=$mxhosts[0];
				return array($Xmxs[0]=>$Xmxs[0]);
			}
			while (list ($index, $WEIGHT) = each ($mxWeight) ){
				if(isset($Xmxs[$WEIGHT])){$WEIGHT++;$mxs[$WEIGHT]=$mxhosts[$index];continue;}
				$mxs[$WEIGHT]=$mxhosts[$index];
				
			}
			
			if(is_array($mxs)){
				ksort($mxs);
				while (list ($WEIGHT, $mx) = each ($mxs) ){$Xmxs[$mx]="[$WEIGHT] $mx";}
			}
			
			
			return $Xmxs;
		}

function params_save(){
	$q=new mysql();
	$sql="SELECT hostname,params FROM postfix_smtp_advrt WHERE zmd5='{$_POST["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$params=unserialize(base64_decode($ligne["params"]));		
	while(list( $key, $val ) = each ($_POST)){$params[$key]=$val;}
	$newval=base64_encode(serialize($params));
	$sql="UPDATE postfix_smtp_advrt SET params='$newval'  WHERE zmd5='{$_POST["zmd5"]}'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("postfix.php?reconfigure-single-instance=yes&hostname={$ligne["hostname"]}");
	
}

function headers_mailfrom_add(){
	$q=new mysql();
	$sql="SELECT params FROM postfix_smtp_advrt WHERE zmd5='{$_POST["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$params=unserialize(base64_decode($ligne["params"]));		
	$params["MAILFROM_RANDOMIZE"][]=$_POST["headers-mailfrom-add"];
	$newval=base64_encode(serialize($params));
	$sql="UPDATE postfix_smtp_advrt SET params='$newval'  WHERE zmd5='{$_POST["zmd5"]}'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}	
}
function headers_mailsoft_add(){
	$q=new mysql();
	$sql="SELECT params FROM postfix_smtp_advrt WHERE zmd5='{$_POST["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$params=unserialize(base64_decode($ligne["params"]));		
	$params["MAILSOFT_RANDOMIZE"][]=$_POST["headers-mailsoft-add"];
	$newval=base64_encode(serialize($params));
	$sql="UPDATE postfix_smtp_advrt SET params='$newval'  WHERE zmd5='{$_POST["zmd5"]}'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}		
}

function headers_mailhelo_add(){
	$q=new mysql();
	$sql="SELECT params FROM postfix_smtp_advrt WHERE zmd5='{$_POST["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$params=unserialize(base64_decode($ligne["params"]));		
	$params["MAILHELO_RANDOMIZE"][]=$_POST["headers-mailhelo-add"];
	$newval=base64_encode(serialize($params));
	$sql="UPDATE postfix_smtp_advrt SET params='$newval'  WHERE zmd5='{$_POST["zmd5"]}'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}		
}
function headers_mailhelo_del(){
	$q=new mysql();
	$sql="SELECT params FROM postfix_smtp_advrt WHERE zmd5='{$_POST["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$params=unserialize(base64_decode($ligne["params"]));		
	unset($params["MAILHELO_RANDOMIZE"][$_POST["headers-mailhelo-del"]]);
	$newval=base64_encode(serialize($params));
	$sql="UPDATE postfix_smtp_advrt SET params='$newval'  WHERE zmd5='{$_POST["zmd5"]}'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}		
}
function headers_mailsoft_del(){
	$q=new mysql();
	$sql="SELECT params FROM postfix_smtp_advrt WHERE zmd5='{$_POST["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$params=unserialize(base64_decode($ligne["params"]));		
	unset($params["MAILSOFT_RANDOMIZE"][$_POST["headers-mailsoft-del"]]);
	$newval=base64_encode(serialize($params));
	$sql="UPDATE postfix_smtp_advrt SET params='$newval'  WHERE zmd5='{$_POST["zmd5"]}'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}		
}


function headers_mailfrom_del(){
	$q=new mysql();
	$sql="SELECT params FROM postfix_smtp_advrt WHERE zmd5='{$_POST["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$params=unserialize(base64_decode($ligne["params"]));		
	unset($params["MAILFROM_RANDOMIZE"][$_POST["headers-mailfrom-del"]]);
	$newval=base64_encode(serialize($params));
	$sql="UPDATE postfix_smtp_advrt SET params='$newval'  WHERE zmd5='{$_POST["zmd5"]}'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}		
}

function CopyFrom_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$domains=$tpl->_ENGINE_parse_body("{domains}");
	
	$buttons="
	buttons : [
	{name: '$new_domain', bclass: 'add', onpress : AddTargetDomain},
	{name: '$copy_from', bclass: 'Copy', onpress : CopyFrom},
	],";$buttons=null;		
		
	
	
$html="

<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?CopyFrom-search=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}',
	dataType: 'json',
	colModel : [
		{display: '$hostname', name : 'hostname', width : 290, sortable : false, align: 'left'},	
		{display: '$domains', name : 'domainname', width :79, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'enabled', width : 30, sortable : true, align: 'center'},
		
		],
	$buttons
	searchitems : [
		{display: '$hostname', name : 'hostname'},
		{display: '$domains', name : 'domainname'},
		],
	sortname: 'hostname',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 450,
	height: 250,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

	var x_AdvRoutingPostfixCopyFrom= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		FlexReloadCopyFromPostRout();
		FlexReloadAdvPostRout();
		
	}

	function FlexReloadCopyFromPostRout(){
		$('#flexRT$t').flexReload();
	}

function AdvRoutingPostfixCopyFrom(nexthostname){
		var XHR = new XHRConnection();
		XHR.appendData('CopyFrom-domain',nexthostname);
		XHR.appendData('hostname','{$_GET["hostname"]}');
		XHR.appendData('ou','{$_GET["ou"]}');
		XHR.sendAndLoad('$page', 'POST',x_AdvRoutingPostfixCopyFrom);		
	}
</script>
";	
	echo $html;	
	
}

function CopyFrom_perform(){
	
	$q=new mysql();
	$sql="SELECT * FROM postfix_smtp_advrt WHERE hostname='{$_POST["CopyFrom-domain"]}'";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	$prefix="INSERT IGNORE INTO postfix_smtp_advrt (domainname,enabled,params,hostname,zmd5) VALUES ";
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$md5=md5($_POST["hostname"].$ligne["domainname"]);
		$params=unserialize(base64_decode($ligne["params"]));
		unset($params["bind_address"]);
		$params["CNX_421_MOVE"]=0;
		$params["CNX_421_HOST"]=null;
		$newparams=base64_encode(serialize($params));
		$f[]="('{$ligne["domainname"]}','{$ligne["enabled"]}','$newparams','{$_POST["hostname"]}','$md5')";
		
	}
	
	if(count($f)>0){
		$sql="$prefix".@implode(",", $f);
		$q=new mysql();
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
		$sock=new sockets();
		$sock->getFrameWork("postfix.php?postfix-reconfigure-transport=yes&hostname={$_POST["hostname"]}");			
	}
	
	
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$pattern=$tpl->_ENGINE_parse_body("{pattern}");
	$domain=$tpl->_ENGINE_parse_body("{domain}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$new_domain=$tpl->_ENGINE_parse_body("{new_domain}");
	$give_the_domain_name=$tpl->_ENGINE_parse_body("{give_the_domain_name}");
	$copy_from=$tpl->_ENGINE_parse_body("{copy_from}");
	$delete_all=$tpl->_ENGINE_parse_body("{delete_all_items}");
	$delete_confirm=$tpl->javascript_parse_text("{delete_headers_regex_text}");
	
	$buttons="
	buttons : [
	{name: '$new_domain', bclass: 'add', onpress : AddTargetDomain},
	{separator: true},
	{name: '$copy_from', bclass: 'Copy', onpress : CopyFrom},
	{separator: true},
	{name: '$delete_all', bclass: 'Delz', onpress : DeleteAllPar},
	],";		
		
	
	
$html="

<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?search=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}',
	dataType: 'json',
	colModel : [
		{display: '$domain', name : 'domainname', width : 217, sortable : false, align: 'left'},	
		{display: '$description', name : 'description', width :284, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'enabled', width : 25, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'delete', width : 32, sortable : false, align: 'left'},
		],
	$buttons
	searchitems : [
		{display: '$domain', name : 'domainname'},
		],
	sortname: 'hostname',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 632,
	height: 350,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

	var x_AddTargetDomain= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		FlexReloadAdvPostRout();
		
	}

	function FlexReloadAdvPostRout(){
		$('#flexRT$t').flexReload();
	}

function AddTargetDomain(){
	var mac=prompt('$give_the_domain_name:');
	if(mac){
		var XHR = new XHRConnection();
		XHR.appendData('domainname',mac);
		XHR.appendData('hostname','{$_GET["hostname"]}');
		XHR.appendData('ou','{$_GET["ou"]}');
		XHR.sendAndLoad('$page', 'POST',x_AddTargetDomain);		
	}
	
}

function DeleteAllPar(){
	if(confirm('$delete_confirm')){
		var XHR = new XHRConnection();
		XHR.appendData('domainname-deleteall','yes');
		XHR.appendData('hostname','{$_GET["hostname"]}');
		XHR.appendData('ou','{$_GET["ou"]}');
		XHR.sendAndLoad('$page', 'POST',x_AddTargetDomain);		
	}
}


function CopyFrom(){
	Loadjs('$page?CopyFrom-js=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}');

}



function AdvRoutingPostfixDelete(zmd5){
		var XHR = new XHRConnection();
		XHR.appendData('delete-domain',zmd5);
		XHR.appendData('hostname','{$_GET["hostname"]}');
		XHR.appendData('ou','{$_GET["ou"]}');		
		XHR.sendAndLoad('$page', 'POST',x_AddTargetDomain);
}

function AdvRoutingPostfixEnable(zmd5){
		var XHR = new XHRConnection();
		if(document.getElementById(zmd5).checked){XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
		XHR.appendData('enable-domain',zmd5);
		XHR.appendData('hostname','{$_GET["hostname"]}');
		XHR.appendData('ou','{$_GET["ou"]}');		
		XHR.sendAndLoad('$page', 'POST');
}

</script>

";	
	echo $html;
	
}

function domainname_add(){
	$domainname=strtolower(trim($_POST["domainname"]));
	if($domainname==null){return;}
	$md5=md5($_POST["hostname"].$domainname);
	$sql="INSERT IGNORE INTO postfix_smtp_advrt (domainname,zmd5,hostname,enabled) VALUES ('$domainname','$md5','{$_POST["hostname"]}',1)";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("postfix.php?postfix-reconfigure-transport=yes&hostname={$_POST["hostname"]}");	
}

function domainname_enable(){
	$sql="UPDATE postfix_smtp_advrt SET enabled='{$_POST["enabled"]}' WHERE zmd5='{$_POST["enable-domain"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("postfix.php?postfix-reconfigure-transport=yes&hostname={$_POST["hostname"]}");
	
}

function domainname_delete(){
	$sql="DELETE FROM postfix_smtp_advrt WHERE zmd5='{$_POST["delete-domain"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}		
	$sock=new sockets();
	$sock->getFrameWork("postfix.php?postfix-reconfigure-transport=yes&hostname={$_POST["hostname"]}");	
}
function domainname_delete_all(){
	$sql="DELETE FROM postfix_smtp_advrt WHERE hostname='{$_POST["hostname"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}		
	$sock=new sockets();
	$sock->getFrameWork("postfix.php?postfix-reconfigure-transport=yes&hostname={$_POST["hostname"]}");		
	
}

function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	
	
	$search='%';
	$table="postfix_smtp_advrt";
	$page=1;
	$FORCE_FILTER="AND hostname='{$_GET["hostname"]}'";
	
	if($q->COUNT_ROWS($table,"artica_backup")==0){
		$total=0;
		writelogs("$table, no row",__FILE__,__FUNCTION__,__FILE__,__LINE__);
		$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();
		echo json_encode($data);
		return ;
	}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_backup");
	
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
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$id=md5($ligne["zmd5"]);
		
		$domain=$ligne["domainname"];
		$params=unserialize(base64_decode($ligne["params"]));	
		$delete=imgtootltip("delete-24.png","{delete} {$ligne["domainname"]}","AdvRoutingPostfixDelete('{$ligne["zmd5"]}')");
		$enable=Field_checkbox($ligne['zmd5'],1,$ligne["enabled"],"AdvRoutingPostfixEnable('{$ligne["zmd5"]}')");

		if($params["bind_address"]==null){$params["bind_address"]="{automatic}";}
		$explain=$tpl->_ENGINE_parse_body("{max_processes}:{$params["max_smtp_out"]} (IP:{$params["bind_address"]})<br> {max_msg_per_connection}: {$params["max_msg_per_connection"]}");
		
	$data['rows'][] = array(
		'id' => $ligne['zmd5'],
		'cell' => array("<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('$MyPage?Params-js=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}&zmd5={$ligne["zmd5"]}');\" 
		style='font-size:16px;text-decoration:underline'>$domain</a>"
		,"<span style='font-size:11px'>$explain</span>",
		$enable,$delete )
		);
	}
	echo json_encode($data);		
}


function CopyFrom_search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	
	
	$search='%';
	$table="postfix_smtp_advrt";
	$page=1;
	
	
	if($q->COUNT_ROWS($table,"artica_backup")==0){
		writelogs("$table, no row",__FILE__,__FUNCTION__,__FILE__,__LINE__);
		$data['page'] = $page;$data['total'] = 0;$data['rows'] = array();
		echo json_encode($data);
		return ;
	}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="HAVING (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(domainname) as TCOUNT,hostname FROM `$table`GROUP BY hostname $searchstring";
		$results = $q->QUERY_SQL($sql,"artica_backup");
		$total = mysql_num_rows($results);
		
	}else{
		
		$sql="SELECT COUNT(domainname) as TCOUNT,hostname FROM `$table`GROUP BY hostname";
		$results = $q->QUERY_SQL($sql,"artica_backup");
		$total = mysql_num_rows($results);
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT COUNT(domainname) as TCOUNT,hostname FROM `$table`GROUP BY hostname $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_backup");
	
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
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$id=md5($ligne["hostname"]);
		if($ligne["hostname"]==$_GET["hostname"]){continue;}
		$domains=$ligne["TCOUNT"];
		
		$select=imgtootltip("arrow-left-24.png","{select} {$ligne["domainname"]}","AdvRoutingPostfixCopyFrom('{$ligne["hostname"]}')");
		
		
	$data['rows'][] = array(
		'id' => $ligne['zmd5'],
		'cell' => array("<span style='font-size:16px;'>{$ligne["hostname"]}</span>"
		,"<span style='font-size:16px'>$domains</span>",
		$select )
		);
	}
	echo json_encode($data);		
}
