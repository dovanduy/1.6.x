<?php
session_start();
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__).'/ressources/class.amavidb.inc');
include_once(dirname(__FILE__)."/ressources/class.user.inc");

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["SearchQuery"])){policies_search();exit;}
if(isset($_GET["policies"])){policies();exit;}
if(isset($_GET["policy-email-js"])){policy_email_js();exit;}
if(isset($_GET["policy-email"])){policy_email();exit;}
if(isset($_POST["policy_email"])){policy_email_save();exit;}
if(isset($_GET["wbl"])){wbl();exit;}
if(isset($_GET["wbl-search"])){wbl_search();exit;}
if(isset($_GET["wbl-js"])){wbl_js();exit;}
if(isset($_GET["wbl-popup"])){wbl_popup();exit;}
if(isset($_POST["wbl-type"])){wbl_save();exit;}

main_page();
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
	

	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a></div>
		<H1>{antispam_settings}</H1>
		<p>{antispam_settings_endusers_text}</p>
	</div>
	<div id='center'></div>

	<script>
	LoadAjax('center','$page?tabs=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}
function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$array["{policies}"]="$page?policies=yes";
	$array["{whitelist}"]="$page?wbl=yes&type=W";
	$array["{blacklist}"]="$page?wbl=yes&type=B";
	echo $boot->build_tab($array);
}
function policy_email_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();	
	header("content-type: application/x-javascript");
	$email=$_GET["policy-email-js"];
	$title=$tpl->javascript_parse_text("{policy}:$email");
	echo "YahooWin2('500','$page?policy-email=$email','$title');";
}
function wbl_js(){
	$page=CurrentPageName();
	$q=new amavisdb();
	$tpl=new templates();
	$t=time();
	$type=$_GET["type"];
	header("content-type: application/x-javascript");
	$email=$q->email_from_emailid($_GET["rid"]);
	$sender=$q->email_from_mailid($_GET["sid"]);
	if($type=="W"){$title=$tpl->javascript_parse_text("{whitelist}");}
	if($type=="B"){$title=$tpl->javascript_parse_text("{blacklist}");}
	$title=$tpl->javascript_parse_text("$title:$sender >> $email");
	echo "YahooWin2('500','$page?wbl-popup&sid={$_GET["sid"]}&rid={$_GET["rid"]}&type=$type','$title');";
}

function wbl_popup(){
	$page=CurrentPageName();
	$q=new amavisdb();
	$tpl=new templates();
	$t=time();
	$type=$_GET["type"];	
	$boot=new boostrap_form();
	$email=$q->email_from_emailid($_GET["rid"]);
	$sender=$q->email_from_mailid($_GET["sid"]);
	
	$user=new user($_SESSION["uid"]);
	$mails=$user->HASH_ALL_MAILS;
	while (list ($b,$email) = each ($mails) ){$mailZ[$email]=$email;}
	
	
	if($_GET["sid"]==0){
		$bt="{add}";
	}else{
		$bt="{apply}";
	}
	
	$policy_id=$q->policyid_from_mail($email);
	$boot->set_hidden("wbl-type", $type);
	$boot->set_list("rcpt", "{email}", $mailZ,$email);
	$boot->set_field("sender", "{sender}", $sender,array("MANDATORY"=>true));
	$boot->set_button($bt);
	$boot->set_CallBack("YahooWin2Hide");
	$boot->set_RefreshSearchs();
	$boot->set_formdescription("{amavis_wblsql_explain}");
	echo $boot->Compile();
	
	
}

function wbl_save(){
	$q=new amavisdb();
	$rcpt=$_POST["rcpt"];
	$sender=$_POST["sender"];
	$rcpt_id=$q->emailid_from_email($rcpt);
	$sender_id=$q->emailid_from_mailaddr($sender);
	if(!is_numeric($rcpt_id)){$rcpt_id=0;}
	if($rcpt_id==0){
		$user=new user($_SESSION["uid"]);
		$user->DisplayName=mysql_escape_string2($user->DisplayName);
		$sql="INSERT IGNORE INTO `users` (policy_id,email,uid,fullname,local) VALUES
		('','$rcpt','{$_SESSION["uid"]}','$user->DisplayName',1);";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;return;}
		$rcpt_id=$q->last_id;
	}
	if($sender_id==0){
		$sql="INSERT IGNORE INTO `mailaddr` (email) VALUES ('$sender');";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;return;}
		$sender_id=$q->last_id;
	}	
	
	$sql="SELECT wb FROM wblist WHERE sid=$sender_id AND rid=$rcpt_id";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if($ligne["wb"]==null){
		$q->QUERY_SQL("INSERT INTO wblist (sid,rid,wb) VALUES ($sender_id,$rcpt_id,'{$_POST["wbl-type"]}')");
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	
}

//wbl-js=yes&rid=0&sid=0&type

function policy_email(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$q=new amavisdb();
	$boot=new boostrap_form();
	$email=$_GET["policy-email"];
	$sql="SELECT id,policy_name FROM policy WHERE ou='{$_SESSION["ou"]}' ORDER BY policy_name";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<p class=text-error>$q->mysql_error<br>$sql<hr></p>";}
	$policies[0]="{default}";
	while ($ligne = mysql_fetch_assoc($results)) {
		$policies[$ligne["id"]]=$ligne["policy_name"];
	}
	
	
	$email_id=$q->emailid_from_email($email);
	$policy_id=$q->policyid_from_mail($email);
	$boot->set_hidden("policy_email", $email);
	$boot->set_hidden("email_id", $email_id);
	$boot->set_list("policy_id", "{policy}", $policies,$policy_id);
	$boot->set_button("{apply}");
	$boot->set_CallBack("YahooWin2Hide");
	$boot->set_RefreshSearchs();
	echo $boot->Compile();
	
}

function policy_email_save(){
	$q=new amavisdb();
	$user=new user($_SESSION["uid"]);
	$user->DisplayName=mysql_escape_string2($user->DisplayName);
	$sqladd="INSERT IGNORE INTO `users` (policy_id,email,uid,fullname,local) VALUES
			('{$_POST["policy_id"]}','{$_POST["policy_email"]}','{$_SESSION["uid"]}','$user->DisplayName',1);";
	
	$sqledit="UPDATE `users` SET 
			policy_id='{$_POST["policy_id"]}',
			fullname='{$_POST["$user->DisplayName"]}',
			uid='{$_SESSION["uid"]}' WHERE id='{$_POST["email_id"]}'";
			
				
	if($_POST["email_id"]==0){
		$q->QUERY_SQL($sqladd);
		if(!$q->ok){echo $q->mysql_error;}
	}else{
		$q->QUERY_SQL($sqledit);
		if(!$q->ok){echo $q->mysql_error."\n$sqledit\n";}	
	}
	
}


function policies(){
	$boot=new boostrap_form();
	$SearchQuery=$boot->SearchFormGen("policies-search");
	echo $SearchQuery;
}

function wbl(){
	$page=CurrentPageName();
	$boot=new boostrap_form();
	$button=button("{new_item}","NewWBL2('{$_GET["type"]}')",16);
	$SearchQuery=$boot->SearchFormGen("email","wbl-search","&type={$_GET["type"]}");
	$tpl=new templates();
	$html="
	<table style='width:100%'>
	<tr>
	<td>$button</td>
	<td></td>
	</tr>
	</table>
	$SearchQuery
	<script>
	ExecuteByClassName('SearchFunction');
	
	function NewWBL2(type){
		Loadjs('$page?wbl-js=yes&rid=0&sid=0&type='+type);
	
	}
	
	</script>
	";
		
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function wbl_search(){
	$amavis=new amavisdb();	
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$tpl=new templates();

	$searchstring=string_to_flexquery("wbl-search");
	$user=new user($_SESSION["uid"]);
	$mails=$user->HASH_ALL_MAILS;
	while (list ($b,$email) = each ($mails) ){
		$rid=$amavis->emailid_from_email($email);
		$f[]="(rid=$rid)";
	}
	
	if(!$amavis->TABLE_EXISTS("wblist")){
		$sql="CREATE TABLE IF NOT EXISTS wblist (rid integer unsigned NOT NULL,sid integer unsigned NOT NULL,wb varchar(10)  NOT NULL,PRIMARY KEY (rid,sid));";
		$amavis->QUERY_SQL($sql);
		if(!$q->ok){
			echo "<p class=text-error>$amavis->mysql_error<hr><code>$sql</code></p>";
			return;
		}
	}
	
	$table="(SELECT * FROM `wblist` WHERE (".@implode(" OR ", $f).") ) as t";
	$table="(SELECT `mailaddr`.email,`users`.email as rcpt, `t`.* FROM $table,`mailaddr`,`wblist` ,`users`
	WHERE 
	`mailaddr`.id=`t`.sid AND 
	`users`.id=`t`.rid AND
	t.wb='{$_GET["type"]}') as y";
	$sql="SELECT * FROM $table WHERE 1 $searchstring ORDER BY email";
	$results = $amavis->QUERY_SQL($sql);
	
	
	
	if(!$amavis->ok){
		echo "<p class=text-error>$amavis->mysql_error<hr><code>$sql</code></p>";
	}
	
	while ($ligne = mysql_fetch_assoc($results)) {	
		$jshost="Loadjs('$page?wbl-js=yes&rid={$ligne["rid"]}&sid={$ligne["sid"]}&type={$_GET["type"]}');";
		$link=$boot->trswitch($jshost);
		$tr[]="
		<tr id='$id'>
		<td $link><i class='icon-globe'></i> {$ligne["email"]}</a></td>
		<td $link><i class='icon-globe'></i> {$ligne["rcpt"]}</a></td>
		<td style='text-align:center'>$delete</td>
		</tr>";
	}
	echo $tpl->_ENGINE_parse_body("
	<table class='table table-bordered table-hover'><thead><tr>
			<th>{sender}</th>
			<th>{email}</th>
			
			<th>&nbsp;</th></tr></thead><tbody>").@implode("\n", $tr)." </tbody></table>";	
}
	
	
function policies_search(){	
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();	
	$html="<p class=text-info>{amavis_users_policies_explain}</p>";
	$boot=new boostrap_form();
	$user=new user($_SESSION["uid"]);
	$mails=$user->HASH_ALL_MAILS;
	$amavis=new amavisdb();
	
	$search=string_to_flexregex();
	
	while (list ($b,$email) = each ($mails) ){
		if($search<>null){if(!preg_match("#$search#", $email)){continue;}}
		$id=$amavis->policyid_from_mail($email);
		$ligne=$amavis->policy_array($id);
		$policy_name=$ligne["policy_name"];
		
		$tr[]="
		<tr id='$id' ". $boot->trswitch("Loadjs('$page?policy-email-js=$email')").">
		<td width=30%><i class='icon-envelope'></i> $email</td>
		<td width=30%><i class='icon-filter'></i> $policy_name</td>
		<td width=1%><i class='icon-eye-open'></i> {$ligne["spam_tag_level"]}</td>
		<td width=1%><i class='icon-fire'></i> {$ligne["spam_tag2_level"]}</td>
		<td width=1%><i class='icon-trash'></i> {$ligne["spam_kill_level"]}</td>
		
		</tr>
		";		
		
		
	}
	
	
	echo $tpl->_ENGINE_parse_body("<table class='table table-bordered table-hover'>
	
			<thead>
			<tr>
				<th>{email} &laquo;{$_SESSION["uid"]}&raquo;</th>
				<th>{policy}</th>
				<th width=1% nowrap>TAG {level}</th>
				<th width=1% nowrap>QUAR {level}</th>
				<th width=1% nowrap>KILL {level}</th>
			</tr>
			</thead>
			<tbody>
			").@implode("\n", $tr)." </tbody>
				</table>";	
}


