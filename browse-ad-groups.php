<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.active.directory.inc');
	include_once("ressources/class.harddrive.inc");
	include_once("ressources/class.external.ad.inc");
	
	if(isset($_GET["RightPan"])){RightPan();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["browser-infos"])){brower_infos();exit;}
	if(isset($_POST["create-folder"])){folder_create();exit;}
	if(isset($_POST["delete-folder"])){folder_delete();exit;}
	if(isset($_GET["browse-dn"])){browse_groups_for_ou();exit;}
	if(isset($_GET["RightPan"])){RightPan();exit;}
	if(isset($_GET["groups-items"])){items_groups();exit;}
	if(isset($_GET["users-items"])){items_users();exit;}
	
	js();

function js(){
	header("content-type: application/x-javascript");
	$t=time();
	$title=$_GET["ou"];
	$_GET["ou"]=urlencode($_GET["ou"]);
	if(!is_numeric($_GET["ADID"])){$_GET["ADID"]=0;}
	$page=CurrentPageName();
	
	$ad=new external_ad_search();
	
	$ad->suffix;
	if($title==null){$title=$ad->suffix;}
	echo "YahooWinBrowse(966,'$page?popup=yes&ADID={$_GET["ADID"]}&field-user={$_GET["field-user"]}&field-type={$_GET["field-type"]}&function={$_GET["function"]}&t=$t','Browse::$title');";
}

function popup(){
	$tpl=new templates();
	$t=$_GET["t"];
	$page=CurrentPageName();
	$ad=new external_ad_search();
	$ous=$ad->SearchOuSimple(null);
	$root=$ad->KerbAuthInfos["ADNETBIOSDOMAIN"];
	
	

	$style=" style='font-size:13px' OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\"";
	$f[]="<table style='width:100%'>";
	$f[]="<tr>";
	$f[]="<td width=30% style='vertical-align:top'>";
	$f[]="<div style='width:400px;' class=form>";
	$f[]="<ul id='root-$t' class='jqueryFileTree'>";
	$f[]="<li class=ou style='font-size:14px'>$root";
	$f[]="<ul id='mytree-$t' class='jqueryFileTree'>";
	

	
	while (list ($dn, $ounameArray) = each ($ous) ){
		$CLASS="ou";
		$ouname=$ounameArray["NAME"];
		if(isset($ounameArray["objectClass"]["container"])){$CLASS="container";}
		$id=md5("$ouname$dn");
		$js=texttooltip("$ouname",$ouname ,"TreeOuExpand$t('$id','$dn');");
		$f[]="<li class=$CLASS collapsed id='$id' $style>$js</li>";
		
	}
	
	$f[]="</ul>";
	$f[]="</li>";
	$f[]="</ul>";
	$f[]="</div>";
	$f[]="</td>";
	$f[]="<td width=510px style='vertical-align:top;padding-left:10px;border-left:3px #CCCCCC'>";
	$f[]="<div id='content-$t'></div>";
	$f[]="</td>";
	$f[]="</tr>";
	$f[]="</table>";
	
$f[]="<script>
var mem_id$t='';
var mem_path$t='';


function RightPan(){
	dn=encodeURIComponent(mem_path$t);
	LoadAjax('content-$t','$page?RightPan=yes&ADID={$_GET["ADID"]}&dn='+dn+'&field-type={$_GET["field-type"]}&field-user={$_GET["field-user"]}&function={$_GET["function"]}&t=$t',true);
}

function DirectPan(dn){
	mem_path$t=dn;
	RightPan();
}



var xTreeOuExpand$t= function (obj) {
	var results=obj.responseText;
	$('#'+mem_id$t).removeClass('collapsed');
	if($('#'+mem_id$t).hasClass('ou')){\$('#'+mem_id$t).addClass('ouExpanded');}
	if($('#'+mem_id$t).hasClass('container')){\$('#'+mem_id$t).addClass('containerExpanded');}
	$('#'+mem_id$t).append(results);
	RightPan(mem_path$t);
}

	function TreeOuExpand$t(id,DN){
		mem_id$t=id;
		mem_path$t=DN;
		var expanded=false;
		if($('#'+mem_id$t).hasClass('ouExpanded')){expanded=true;}
		if($('#'+mem_id$t).hasClass('containerExpanded')){expanded=true;}
		if(!expanded){if($('#'+mem_id$t).hasClass('ouExpanded')){expanded=true;}}
			
		if(!expanded){
			var XHR = new XHRConnection();
			XHR.appendData('browse-dn',encodeURIComponent(DN));
			XHR.appendData('function','{$_GET["function"]}');
			XHR.appendData('field-user','{$_GET["field-user"]}');
			XHR.appendData('t','{$_GET["t"]}');
			XHR.sendAndLoad('$page', 'GET',xTreeOuExpand$t);
		}else{
			$('#'+mem_id$t).children('ul').empty();
			if($('#'+mem_id$t).hasClass('ouExpanded')){\$('#'+mem_id$t).removeClass('ouExpanded');}
			if($('#'+mem_id$t).hasClass('containerExpanded')){\$('#'+mem_id$t).removeClass('containerExpanded');}
			$('#'+mem_id$t).addClass('collapsed');
	
		}
	}
	
		
</script>";
	
echo @implode("\n", $f);

}

function browse_groups_for_ou(){
	$DN=url_decode_special_tool($_GET["browse-dn"]);
	$t=$_GET["t"];
	$function=$_GET["function"];
	$field_user=$_GET["field-user"];
	$tpl=new templates();
	$ad=new external_ad_search();
	$ous=$ad->SearchOuSimple($DN);
	$id_root=md5($DN."1");
	$style=" style='font-size:13px' OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\"";
	
	
	
	if(count($ous)>0){
		$f[]="<ul id='ou-$id_root' class='jqueryFileTree'>";
		while (list ($dn, $ounameArray) = each ($ous) ){
			$CLASS="ou";
			$ouname=$ounameArray["NAME"];
			if(isset($ounameArray["objectClass"]["container"])){$CLASS="container";}
			$id=md5("$ouname$dn");
			$js=texttooltip("$ouname",$ouname ,"TreeOuExpand$t('$id','$dn');");
			$f[]="<li class=$CLASS collapsed id='$id' $style>$js</li>";
		
		}
		
		$f[]="</ul>";
	}
	
	
	$groups=$ad->searchGroupSimple($DN);
	
	if(count($groups)>0){
		$f[]="<ul id='group-$id_root' class='jqueryFileTree'>";
		ksort($groups);
		$group=$tpl->_ENGINE_parse_body("{group2}");
		while (list ($dnsearch, $groupname) = each ($groups) ){
			if($GLOBALS["VERBOSE"]){echo "$num -> $groupname<br>\n";}
			$id=md5($dnsearch);
			$CLASS="group";
			$f[]="<li class=$CLASS collapsed id='$id' >
			<a href=\"#\" 
			OnClick=\"javascript:DirectPan('$dnsearch');\"
			$style>$groupname</a>
			</li>";
		}
	
		$f[]="</ul>";	
	}
	
	echo @implode("\n", $f);
	
}


function RightPan(){
	$DN=url_decode_special_tool($_GET["dn"]);
	$ad=new external_ad_search();
	$hash=$ad->DNinfos($DN);
	for($z=0;$z<$hash[0]["objectclass"]["count"];$z++){
		$FINAL["objectClass"][$hash[0]["objectclass"][$z]]=true;
	
	}
	
	
	
	if( ( isset($FINAL["objectClass"]["container"]) ) OR ( isset($FINAL["objectClass"]["organizationalUnit"]) ) ){
		Interface_SearchGroups($DN);
		return;
	}
	Interface_SearchUsers($DN);
}

function Interface_SearchGroups($DN){
	$page=CurrentPageName();
	$tpl=new templates();
	$tt=time();
	$field_user=$_GET["field-user"];
	$type=$tpl->javascript_parse_text("{type}");
	$from=$tpl->_ENGINE_parse_body("{from}");
	$to=$tpl->javascript_parse_text("{to}");
	$rule=$tpl->javascript_parse_text("{rule}");
	$delete=$tpl->javascript_parse_text("{delete} {zone} ?");
	$rewrite_rules_fdb_explain=$tpl->javascript_parse_text("{rewrite_rules_fdb_explain}");
	$new_group=$tpl->javascript_parse_text("{new_group}");
	$groupname=$tpl->javascript_parse_text("{groupname}");
	$rules=$tpl->javascript_parse_text("{rules}");
	$rule=$tpl->javascript_parse_text("{rule}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$parameters=$tpl->javascript_parse_text("{parameters}");
	$items=$tpl->javascript_parse_text("{items}");
	$item_add=$tpl->javascript_parse_text("{item_added}");
	$select_this_group=$tpl->javascript_parse_text("{select_this_group}");
	
	$ad=new external_ad_search();
	$hash=$ad->DNinfos($DN);
	$tt=md5(time());
	
	$description=$hash[0]["description"][0];
	if(isset($hash[0]["name"][0])){$name=$hash[0]["name"][0];}
	if(isset($hash[0]["ou"][0])){$name=$hash[0]["ou"][0];}
	$title="$name<br><span style=font-size:10px;font-style:italic>$description</span>";
	
	for($z=0;$z<$hash[0]["objectclass"]["count"];$z++){
		$FINAL["objectClass"][$hash[0]["objectclass"][$z]]=true;
		
	}
	
	$fieldtype=$_GET["field-type"];
	if(!is_numeric($fieldtype)){$fieldtype=0;}
	$OPENBT=false;
	$DN_enc=urlencode($DN);	
	$DN_base64=base64_encode($DN);
	if(isset($FINAL["objectClass"]["group"])){
	if($_GET["field-user"]<>null){
		$OPENBT=true;
		$select="{name: '$select_this_group', bclass: 'Down', onpress : Select$tt},";
	}
	}
	
	
$buttons="
	buttons : [
		{name: '$parameters', bclass: 'Group', onpress : Parameters$tt},
		$select
		],";
if(!$OPENBT){$buttons=null;}
$html="
<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:100%'></table>
<script>
function Start$tt(){
	$('#flexRT$tt').flexigrid({
	url: '$page?groups-items=yes&tt=$tt&DN=$DN_enc&field-user={$_GET["field-user"]}&field-type={$_GET["field-type"]}',
	dataType: 'json',
	colModel : [
	{display: '&nbsp;', name : 'link', width : 31, sortable : false, align: 'center'},
	{display: '$groupname', name : 'groupname', width :295, sortable : false, align: 'left'},
	{display: '$items', name : 'items', width :32, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'link', width : 31, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'delete', width : 31, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
		{display: '$groupname', name : 'groupname'},
	],
	sortname: 'groupname',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '500',
	height: 450,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	});
}

function EditField$tt(base64,Name){
	var fieldtype='$fieldtype';
	var ADID='{$_GET["ADID"]}';
	if(document.getElementById('$field_user')){
		if(fieldtype==2){
			document.getElementById('$field_user').value='AD:'+ADID+':'+base64;
			alert('$item_add mode:'+fieldtype);
			return;
		}
		if(fieldtype==3){
			document.getElementById('$field_user').value=Name;
			alert('$item_add `'+Name+'`');
			return;
		}		
		document.getElementById('$field_user').value=base64;
		alert('$item_add mode:'+fieldtype);
		
	}
}

function Select$tt(){
	EditField$tt('$DN_base64','$name');
}

Start$tt();
</script>
";
echo $html;
	
}

function items_users(){
	$search=$_POST["query"];
	$search="*$search*";
	$search=str_replace("**", "*", $search);
	$search=str_replace("**", "*", $search);	
	$search=str_replace("*", ".*?", $search);
	$ad=new external_ad_search();
	
	$hash=$ad->DNinfos($_GET["DN"]);
	
	if($GLOBALS["VERBOSE"]){
		print_r($hash);
	}
	
	if($hash[0]["member"]["count"]==0){
		json_error_show("$search no data");
	}
	if(!is_numeric($_POST["rp"])){$_POST["rp"]=50;}
	if($hash[0]["member"]["count"]>$_POST["rp"]){$hash[0]["member"]["count"]=$_POST["rp"];}
	$data = array();
	$data['page'] = 1;
	$data['total'] = $hash[0]["member"]["count"];
	$data['rows'] = array();
	$fontsize=14;
	$c=0;
	for($i=0;$i<$hash[0]["member"]["count"];$i++){
		$dn=$hash[0]["member"][$i];
		
		$hash2=$ad->DNinfos($dn);
		$sn=$hash2[0]["sn"][0];
		$displayname=$hash2[0]["displayname"][0];
		$samaccountname=$hash2[0]["displayname"][0];
		$description=$hash2[0]["description"][0];
		if(isset($hash2[0]["userprincipalname"][0])){
			$email=$hash2[0]["userprincipalname"][0];
		}
		if($displayname==null){$displayname=$samaccountname;}
		if($displayname==null){$displayname=$hash2[0]["cn"][0];;}
		
		if($search<>null){
			if(!preg_match("#$search#", "$sn $displayname $samaccountname $email")){continue;}
		}
		$c++;
		
		$color="black";
		
		$description="<br><span style='font-size:12px;font-style:italic;font-weight:normal'>$description - $samaccountname $email</span>";
		$data['rows'][] = array(
				'id' => md5($dn),
				'cell' => array(
						"<span style='font-size:{$fontsize}px;font-weight:bold;color:$color'>$editjs$displayname$description</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$link</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$delete</span>"
		
						,)
		);
		
	}
	
	$data['total'] = $c;
	echo json_encode($data);
}

function items_groups(){
	
	
	$tt=$_GET["tt"];
	$search=$_POST["query"];
	$search="*$search*";
	$search=str_replace("**", "*", $search);
	$search=str_replace("**", "*", $search);
	$field_user=$_GET["field-user"];
	
	$ad=new external_ad_search();
	$array=$ad->SearchGroups($search,$_GET["DN"],$_POST["rp"]);
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = $array["count"];
	$data['rows'] = array();
	$fontsize=13;
	$c=0;
	for($i=0;$i<$array["count"];$i++){
		$c++;
			$color="black";
			$DN=$array[$i]["dn"];
			$samaccountname=$array[$i]["samaccountname"][0];
			$description=$array[$i]["description"][0];
			$itemsNum=$array[$i]["member"]["count"];
			if($samaccountname==null){$samaccountname=$array[$i]["cn"][0];}
			if(!is_numeric($itemsNum)){$itemsNum=0;}
			$select="&nbsp;";
			
			$DN_enc=urlencode($DN);
			$FicheGroup="Loadjs('domains.edit.group.php?ou=ABC&js=yes&group-id=$DN_enc',true)";
			$editjs="<a href=\"javascript:Blur();\" OnClick=\"$FicheGroup\" style='text-decoration:underline;font-size:{$fontsize}px;font-weight:bold;'>";
			
			if($field_user<>null){
				$base64=base64_encode($DN);
				$select=imgsimple("arrow-right-24.png",null,"EditField$tt('$base64','$samaccountname')");
			}
			
			if($description<>null){$description="<br><span style='font-size:10px;font-style:italic;font-weight:normal'>$description</span>";}
			$data['rows'][] = array(
			'id' => md5($DN),
			'cell' => array(
				"<img src='img/wingroup.png'>",
				"<span style='font-size:{$fontsize}px;font-weight:bold;color:$color'>$editjs$samaccountname</a>$description</span>",
				"<span style='font-size:{$fontsize}px;font-weight:bold;color:$color'>$editjs$itemsNum</a></span>",
				"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$select</span>",
				"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$link</span>",
				"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$delete</span>"
	
			,)
		);
	}
	
	$array=$ad->SearchUsers($search,$_GET["DN"],$_POST["rp"]);
	
	for($i=0;$i<$array["count"];$i++){
		if($c>$_POST["rp"]){break;}
		$c++;
		$color="black";
		$DN=$array[$i]["dn"];
		$samaccountname=$array[$i]["samaccountname"][0];
		$description=$array[$i]["description"][0];
		$itemsNum="-";
		if($samaccountname==null){$samaccountname=$array[$i]["cn"][0];}
		$select="&nbsp;";
		$jsUser=MEMBER_JS($samaccountname,0,0,$DN);
		$editjs="<a href=\"javascript:Blur();\" $jsUser style='text-decoration:underline;font-size:{$fontsize}px;font-weight:bold;'>";
		if($description<>null){$description="<br><span style='font-size:10px;font-style:italic;font-weight:normal'>$description</span>";}
		$data['rows'][] = array(
				'id' => md5($DN),
				'cell' => array(
						"<img src='img/user-18.png'>",
						"<span style='font-size:{$fontsize}px;font-weight:bold;color:$color'>$editjs$samaccountname$description</span>",
						"<span style='font-size:{$fontsize}px;font-weight:bold;color:$color'>$itemsNum</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$itemsNum</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$link</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$delete</span>"
	
						,)
		);
	}

	if($c==0){
		json_error_show("$search no data");
	}
	
	$data['total'] = $c;
	echo json_encode($data);	
	
}



function Interface_SearchUsers($DN){
	$page=CurrentPageName();
	$tpl=new templates();
	$tt=time();

	$type=$tpl->javascript_parse_text("{type}");
	$from=$tpl->_ENGINE_parse_body("{from}");
	$to=$tpl->javascript_parse_text("{to}");
	$rule=$tpl->javascript_parse_text("{rule}");
	$delete=$tpl->javascript_parse_text("{delete} {zone} ?");
	$rewrite_rules_fdb_explain=$tpl->javascript_parse_text("{rewrite_rules_fdb_explain}");
	$new_group=$tpl->javascript_parse_text("{new_group}");
	$groupname=$tpl->javascript_parse_text("{members}");
	$privilegesandparameters=$tpl->javascript_parse_text("{privilegesandparameters}");
	$rule=$tpl->javascript_parse_text("{rule}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$action=$tpl->javascript_parse_text("{action}");
	$items=$tpl->javascript_parse_text("{items}");
	$select_this_group=$tpl->javascript_parse_text("{select_this_group}");
	$item_add=$tpl->javascript_parse_text("{item_added}");
	$ad=new external_ad_search();
	$hash=$ad->DNinfos($DN);
	$DN_enc=urlencode($DN);
	$tt=md5($DN);
	$DN_ENC=urlencode($DN);
	$description=$hash[0]["description"][0];
	$name=$hash[0]["samaccountname"][0];
	$title="$name<br><span style=font-size:10px;font-style:italic>$description</span>";
	$field_user=$_GET["field-user"];
	$fieldtype=$_GET["field-type"];
	$FicheGroup="Loadjs('domains.edit.group.php?ou=ABC&js=yes&group-id=$DN_enc&field-type={$_GET["field-type"]}',true)";
	
	$OPENBT=false;
	$DN_enc=urlencode($DN);
	$DN_base64=base64_encode($DN);
	if($_GET["field-user"]<>null){
			$OPENBT=true;
			$select="{name: '$select_this_group', bclass: 'Down', onpress : Select$tt},";
		}
	


	$buttons="
	buttons : [
	{name: '$privilegesandparameters', bclass: 'Group', onpress : Parameters$tt},$select
	],";
	
	$html="
	<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:100%'></table>
	<script>
	function Start$tt(){
	$('#flexRT$tt').flexigrid({
	url: '$page?users-items=yes&tt=$tt&DN=$DN_ENC&field-user={$_GET["field-user"]}',
	dataType: 'json',
	colModel : [
	{display: '$groupname', name : 'groupname', width :382, sortable : false, align: 'left'},
	{display: '&nbsp;', name : 'link', width : 31, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'delete', width : 31, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$groupname', name : 'groupname'},
	],
	sortname: 'groupname',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '500',
	height: 450,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
}

function Parameters$tt(){
	$FicheGroup
}

function EditField$tt(base64,Name){
	var fieldtype='$fieldtype';
	var ADID='{$_GET["ADID"]}';
	if(document.getElementById('$field_user')){
		if(fieldtype==2){
			document.getElementById('$field_user').value='AD:'+ADID+':'+base64;
			alert('$item_add mode:'+fieldtype);
			return;
		}
		
		if(fieldtype==3){
			document.getElementById('$field_user').value=Name;
			alert('$item_add `'+Name+'`');
			return;
		}		
		
		document.getElementById('$field_user').value=base64;
		alert('$item_add mode:'+fieldtype);
		
	}
}

function Select$tt(){
	EditField$tt('$DN_base64','$name');
}


Start$tt();
</script>
		";
		echo $html;

}