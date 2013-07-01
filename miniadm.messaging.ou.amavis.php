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

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["policies"])){policies();exit;}
if(isset($_GET["SearchQuery"])){SearchQuery();exit;}
if(isset($_GET["policy-id-js"])){policy_js();exit;}
if(isset($_GET["policy-tab"])){policy_tab();exit;}
if(isset($_GET["policy1"])){policy1();exit;}
if(isset($_GET["policy2"])){policy2();exit;}
if(isset($_GET["policy3"])){policy3();exit;}


if(isset($_POST["policy_name"])){policy_name_save();exit;}
if(isset($_POST["spam_kill_level"])){policy_name_save();exit;}

main_page();
function main_page(){
	
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;	
}
function policy_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();	
	$id=$_GET["policy-id-js"];
	$title="{new_policy}";
	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin2(800,'$page?policy-tab=yes&policy-id=$id','$title')";
	
}
function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	

	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a></div>
		<H1>{antispam_settings} {$_SESSION["ou"]}</H1>
		<p>{antispam_settings_endusers_text}</p>
	</div>
	<div id='center'></div>

	<script>
	LoadAjax('center','$page?tabs=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}
function policy_tab(){
	$amavisdb=new amavisdb();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$array["{general_settings}"]="$page?policy1=yes&policy-id={$_GET["policy-id"]}";
	if($_GET["policy-id"]>0){
		$array["{scores}"]="$page?policy2=yes&policy-id={$_GET["policy-id"]}";
		$array["{notification}"]="$page?policy3=yes&policy-id={$_GET["policy-id"]}";
	}
	echo $boot->build_tab($array);
}

function policy1(){
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	$t=time();
	$q=new amavisdb();
	$users=new usersMenus();
	
	
	$policy_id=$_GET["policy-id"];
	$btname="{apply}";
	if($policy_id<1){
		$btname="{add}";
		$boot->set_CloseYahoo("YahooWin2");
	}else{
		$sql="SELECT * FROM policy WHERE id='$policy_id'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){$error="<p class='text-error'>$q->mysql_error.</p>";}
	}
	

	
	
	$boot->set_hidden("policy_id", $policy_id);
	$boot->set_field("policy_name", "{name}", $ligne["policy_name"],array("MANDATORY"=>true,"ENCODE"=>true));
	$boot->set_checkboxYN("virus_lover", "{virus_lover}", $ligne["virus_lover"]);
	$boot->set_checkboxYN("spam_lover", "{spam_lover}", $ligne["spam_lover"]);
	$boot->set_checkboxYN("unchecked_lover", "{unchecked_lover}", $ligne["unchecked_lover"]);
	$boot->set_checkboxYN("banned_files_lover", "{banned_files_lover}", $ligne["banned_files_lover"]);
	$boot->set_checkboxYN("bad_header_lover", "{bad_header_lover}", $ligne["bad_header_lover"]);
	
	$boot->set_checkboxYN("bypass_virus_checks", "{bypass_virus_checks}", $ligne["bypass_virus_checks"]);
	$boot->set_checkboxYN("bypass_spam_checks", "{bypass_spam_checks}", $ligne["bypass_spam_checks"]);
	$boot->set_checkboxYN("bypass_banned_checks", "{bypass_banned_checks}", $ligne["bypass_banned_checks"]);
	$boot->set_checkboxYN("bypass_header_checks", "{bypass_header_checks}", $ligne["bypass_header_checks"]);
	$boot->set_checkboxYN("spam_modifies_subj", "{spam_modifies_subj}", $ligne["spam_modifies_subj"]);
	$boot->set_RefreshSearchs();
	echo $boot->Compile();
}
function policy2(){
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	$t=time();
	$q=new amavisdb();
	$users=new usersMenus();


	$policy_id=$_GET["policy-id"];
	$btname="{apply}";
	if($policy_id<1){
		$btname="{add}";
		$boot->set_CloseYahoo("YahooWin2");
	}else{
		$sql="SELECT * FROM policy WHERE id='$policy_id'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){$error="<p class='text-error'>$q->mysql_error.</p>";}
	}




	$boot->set_hidden("policy_id", $policy_id);
	$boot->set_field("spam_tag_level", "{spam_tag_level}", $ligne["spam_tag_level"]);
	$boot->set_field("spam_tag2_level", "{spam_tag2_level}", $ligne["spam_tag2_level"]);
	$boot->set_field("spam_tag3_level", "{spam_tag3_level}", $ligne["spam_tag3_level"]);
	$boot->set_field("spam_kill_level", "{spam_kill_level}", $ligne["spam_kill_level"]);
	$boot->set_field("spam_dsn_cutoff_level", "{spam_dsn_cutoff_level}", $ligne["spam_dsn_cutoff_level"]);
	$boot->set_field("spam_quarantine_cutoff_level", "{spam_quarantine_cutoff_level}", $ligne["spam_quarantine_cutoff_level"]);
	
	$boot->set_RefreshSearchs();
	echo $boot->Compile();
}

function policy3(){
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	$t=time();
	$q=new amavisdb();
	$users=new usersMenus();
	
	
	$policy_id=$_GET["policy-id"];
	$btname="{apply}";
	$sql="SELECT * FROM policy WHERE id='$policy_id'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){$error="<p class='text-error'>$q->mysql_error.</p>";}
	
	
	
	
	$boot->set_hidden("policy_id", $policy_id);
	$boot->set_spacertitle("{warnrecip}");
	$boot->set_checkboxYN("warnvirusrecip", "{warnvirusrecip}", $ligne["warnvirusrecip"]);
	$boot->set_checkboxYN("warnbannedrecip", "{warnbannedrecip}", $ligne["warnbannedrecip"]);
	$boot->set_checkboxYN("warnbadhrecip", "{warnbadhrecip}", $ligne["warnbadhrecip"]);
	
	$boot->set_spacertitle("{sendreportto}");
	$boot->set_field("newvirus_admin", "{virus_detected}", $ligne["newvirus_admin"]);
	$boot->set_field("banned_admin", "{banned_files}", $ligne["banned_admin"]);
	$boot->set_field("bad_header_admin", "{bad_headers}", $ligne["bad_header_admin"]);
	$boot->set_field("spam_admin", "{spam_messages}", $ligne["spam_admin"]);
	
	$boot->set_spacertitle("{subjects_tags}");
	$boot->set_field("spam_subject_tag", "{non_spam_messages}", $ligne["spam_subject_tag"]);
	$boot->set_field("spam_subject_tag2", "{spam_messages}", $ligne["spam_subject_tag2"]);
	$boot->set_field("spam_subject_tag3", "{blatant_spam}", $ligne["spam_subject_tag3"]);
	
	$boot->set_RefreshSearchs();
	echo $boot->Compile();	
	
}

/*spam_tag_level  float default NULL, -- higher score inserts spam info headers
spam_tag2_level float default NULL, -- inserts 'declared spam' header fields
spam_tag3_level float default NULL, -- inserts 'blatant spam' header fields
spam_kill_level float default NULL, -- higher score triggers evasive actions
-- e.g. reject/drop, quarantine, ...
-- (subject to final_spam_destiny setting)
spam_dsn_cutoff_level        float default NULL,
spam_quarantine_cutoff_level float default NULL,
*/
function tabs(){
	$amavisdb=new amavisdb();
	$amavisdb->checkTables();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$array["{policies}"]="$page?policies=yes";
	$array["{domains_policies}"]="miniadm.messaging.ou.amavis.domains.php";
	echo $boot->build_tab($array);
}

function policies(){
	$boot=new boostrap_form();
	$SearchQuery=$boot->SearchFormGen("policy_name");
	$page=CurrentPageName();
	$tpl=new templates();	
	$html="
	<table style='width:100%'>
	<tr>
		<td>". button("{new_policy}","Loadjs('$page?policy-id-js=0')",16)."</td>
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

function policy_name_save(){
	if(isset($_POST["policy_name"])){
		$_POST["policy_name"]=url_decode_special_tool($_POST["policy_name"]);
		$_POST["policy_name"]=replace_accents($_POST["policy_name"]);
	}
	if(!isset($_POST["ou"])){$_POST["ou"]=$_SESSION["ou"];}
	
	$policy_id=$_POST["policy_id"];
	unset($_POST["policy_id"]);
	while (list ($head, $value) = each ($_POST) ){
		$qrf[]="`$head`";
		$qrv[]="'$value'";
		$qred[]="`$head`='$value'";
		
	}
	if($policy_id<1){
		$DEFS["spam_tag_level"]="3.0";
		$DEFS["spam_tag2_level"]="6.9";
		$DEFS["spam_kill_level"]="6.9";
		while (list ($head, $value) = each ($DEFS) ){
			$qrf[]="`$head`";
			$qrv[]="'$value'";			
		}
		
	}


	
	
	
	if($policy_id==0){
		$sql="INSERT IGNORE INTO policy (".@implode(",", $qrf).") VALUES (".@implode(",", $qrv).")";
	}else{
		$sql="UPDATE policy SET ".@implode(",", $qred)." WHERE id=$policy_id";
	}
	
	$q=new amavisdb();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n$sql";}
	
	
}

function SearchQuery(){
	$SearchQuery=string_to_flexquery();
	$q=new amavisdb();
	$page=CurrentPageName();
	$usersZ=new usersMenus();
	$tpl=new templates();
	$boot=new boostrap_form();	
	
	$sql="SELECT * FROM `policy` WHERE ou='{$_SESSION["ou"]}' $SearchQuery ORDER BY policy_name LIMIT 0,150 ";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<p class=text-error>$q->mysql_error<br>$sql<hr></p>";}

	$t=time();


	while ($ligne = mysql_fetch_assoc($results)) {
		$id=$ligne["id"];
		$policy_name=$ligne["policy_name"];
		$tr[]="
		<tr id='$id' ". $boot->trswitch("Loadjs('$page?policy-id-js=$id')").">
		<td width=30%><i class='icon-filter'></i> $policy_name</td>
		<td width=1%><i class='icon-eye-open'></i> {$ligne["spam_tag_level"]}</td>
		<td width=1%><i class='icon-fire'></i> {$ligne["spam_tag2_level"]}</td>
		<td width=1%><i class='icon-trash'></i> {$ligne["spam_kill_level"]}</td>
		<td style='text-align:center'>". imgsimple("delete-32.png",null,"Delete$t('$id')")."</td>
		</tr>
		";

		
	}

	$page=CurrentPageName();
	$ouF=null;
	
	$delete=$tpl->javascript_parse_text("{delete} {rule} ?");
	echo $tpl->_ENGINE_parse_body("<table class='table table-bordered table-hover'>

			<thead>
			<tr>
			<th>{policy}</th>
			<th width=1% nowrap>TAG {level}</th>
			<th width=1% nowrap>QUAR {level}</th>
			<th width=1% nowrap>KILL {level}</th>
			<th width=1% nowrap>&nbsp;</th>
			</tr>
			</thead>
			<tbody>
			").@implode("\n", $tr)." </tbody>
			</table>
<script>
	var meme$t='';
	var xDelete$t= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);return;}
			$('#'+meme$t).remove();
	}


	function Delete$t(xmd5){
		meme$t=xmd5;
		if(!confirm('$delete')){return;}
			var XHR = new XHRConnection();
			XHR.appendData('delete-md5',xmd5);
			XHR.sendAndLoad('$page', 'POST',xDelete$t);
	}
						
</script>";


}