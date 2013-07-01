<?php
session_start();
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class=text-error>");
ini_set('error_append_string',"</p>");
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__).'/ressources/class.amavidb.inc');
include_once(dirname(__FILE__)."/ressources/class.user.inc");

$users=new usersMenus();
if(!$users->AsMessagingOrg){die();}
if(isset($_GET["domains-search"])){domains_search();exit;}
if(isset($_GET["policy-domain-js"])){policy_domain_js();exit;}
if(isset($_GET["policy-domain"])){policy_domain();exit;}
if(isset($_POST["policy_domain"])){policy_domain_save();exit;}



domains_page();
function policy_domain_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	header("content-type: application/x-javascript");
	$email=$_GET["policy-domain-js"];
	$title=$tpl->javascript_parse_text("{policy}:$email");
	echo "YahooWin2('500','$page?policy-domain=$email','$title');";
}
function policy_domain(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$q=new amavisdb();
	$boot=new boostrap_form();
	$email=$_GET["policy-domain"];
	$sql="SELECT id,policy_name FROM policy WHERE ou='{$_SESSION["ou"]}' ORDER BY policy_name";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<p class=text-error>$q->mysql_error<br>$sql<hr></p>";}
	$policies[0]="{default}";
	while ($ligne = mysql_fetch_assoc($results)) {
		$policies[$ligne["id"]]=$ligne["policy_name"];
	}


	$email_id=$q->emailid_from_email("@$email");
	$policy_id=$q->policyid_from_mail("@$email");
	$boot->set_hidden("policy_domain", $email);
	$boot->set_hidden("email_id", $email_id);
	$boot->set_list("policy_id", "{policy}", $policies,$policy_id);
	$boot->set_button("{apply}");
	$boot->set_CallBack("YahooWin2Hide");
	$boot->set_RefreshSearchs();
	echo $boot->Compile();

}
function policy_domain_save(){
	$q=new amavisdb();
	
	$sqladd="INSERT IGNORE INTO `users` (policy_id,email,uid,fullname,local) VALUES
	('{$_POST["policy_id"]}','@{$_POST["policy_domain"]}','{$_SESSION["uid"]}','{$_SESSION["ou"]}',1);";

	$sqledit="UPDATE `users` SET
	policy_id='{$_POST["policy_id"]}',
			fullname='{$_POST["$user->DisplayName"]}',
			WHERE id='{$_POST["email_id"]}'";
				

			if($_POST["email_id"]==0){
			$q->QUERY_SQL($sqladd);
			if(!$q->ok){echo $q->mysql_error;}
			}else{
			$q->QUERY_SQL($sqledit);
			if(!$q->ok){echo $q->mysql_error."\n$sqledit\n";}
			}

}
function domains_page(){
	$boot=new boostrap_form();
	$SearchQuery=$boot->SearchFormGen("domains-search","domains-search");
	$page=CurrentPageName();
	$tpl=new templates();
	$html="
	<table style='width:100%'>
	<tr>
		<td>". button("{domains}","Loadjs('$page?domain-id-js=0')",16)."</td>
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

function domains_search(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$ldap=new clladp();
	$mails=$ldap->hash_get_domains_ou($_SESSION["ou"],true);
	$amavis=new amavisdb();
	
	$search=string_to_flexregex();
	
	while (list ($b,$domain) = each ($mails) ){
		if($search<>null){if(!preg_match("#$search#", $domain)){continue;}}
		$id=$amavis->policyid_from_mail("@$domain");
		$ligne=$amavis->policy_array($id);
		$policy_name=$ligne["policy_name"];
	
		$tr[]="
		<tr id='$id' ". $boot->trswitch("Loadjs('$page?policy-domain-js=$domain')").">
		<td width=30%><i class='icon-envelope'></i> $domain</td>
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
			<th>{domains_policies} &laquo;{$_SESSION["ou"]}&raquo;</th>
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