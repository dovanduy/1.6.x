<?php
session_start();

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class='text-error'>");
ini_set('error_append_string',"</p>");
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");


$users=new usersMenus();
if(!$users->AsMessagingOrg){die();}

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["zmd5-js"])){rule_js();exit;}
if(isset($_GET["zmd5"])){rule();exit;}
if(isset($_POST["zmd5"])){save();exit;}
if(isset($_POST["delete-md5"])){delete();exit;}
if(isset($_GET["SearchQuery"])){SearchQuery();exit;}
main_page();


function rule_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();	
	$zmd5=$_GET["zmd5-js"];
	if($zmd5==null){$title="{new_rule}";}else{$title="{rule}:: $zmd5";}
	$title=$tpl->_ENGINE_parse_body($title);
	echo "YahooWin('700','$page?zmd5=$zmd5','$title')";
}

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
		<H1>&laquo;{$_SESSION["ou"]}&raquo; {archive_module}</H1>
		<p>{mymessaging_archive_text}</p>
	</div>	
	<div id='messaging-left'></div>
	
	<script>
		LoadAjax('messaging-left','$page?popup=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function rule(){
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	$t=time();	
	$q=new mysql();
	$users=new usersMenus();
	
	if(!$q->TABLE_EXISTS("mailarchives", "artica_backup")){$q->BuildTables();}
	$zmd5=$_GET["zmd5"];
	$btname="{apply}";
	if($zmd5==null){
		$btname="{add}";
		$boot->set_CloseYahoo("YahooWin");
	}
	$sql="SELECT * FROM mailarchives WHERE zmd5='$zmd5'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!$q->ok){
		$error="<p class='text-error'>$q->mysql_error.</p>";
	}
	
	
	$directions["in"]="{inbound}";
	$directions["out"]="{outbound}";
	$directions["all"]="{all}";
	
	$boot->set_checkbox("enable", "{enabled_rule}", $ligne["enable"]);
	
	$boot->set_field("email", "{member}", $ligne["email"],array(
			"MANDATORY"=>true,
			"DISABLED"=>true,
			"BUTTON"=>array(
					"JS"=>"Loadjs('MembersBrowse.php?field-user=%f&OnlyUsers=1&OnlyGUID=0');",
					"LABEL"=>"{browse}...")
			));
	
	$boot->set_list("direction", "{direction}",$directions,$ligne["direction"]);
	
	if($users->AsPostfixAdministrator){
		$ldap=new clladp();
		$hash=$ldap->hash_get_ou(true);
		$boot->set_list("ou", "{organization}",$hash,$ligne["ou"]);
		
	}
	
	
	$boot->set_field("next", "{destination}", $ligne["next"],array(
			"MANDATORY"=>true,
			"BUTTON"=>array(
					"JS"=>"Loadjs('MembersBrowse.php?field-user=%f&OnlyUsers=1&OnlyGUID=0');",
					"LABEL"=>"{browse}...")
	));	
	
	
	$boot->set_button($btname);
	$boot->set_hidden("zmd5", $zmd5);
	
	$params=unserialize(base64_decode($ligne["params"]));
	$boot->set_checkbox("USE_SMTP_SRV", "{external_smtp_server}", $params["USE_SMTP_SRV"],array(
			"LINK"=>"SMTP_SRV,USE_AUTH"
			));
	$boot->set_field("SMTP_SRV", "{smtp_server}", $params["SMTP_SRV"]);
	$boot->set_checkbox("USE_AUTH", "{smtp_authentication}", $params["USE_AUTH"],array(
			"LINK"=>"SMTP_USERNAME,SMTP_PASSWORD"
			));
	
	$boot->set_field("SMTP_USERNAME", "{username}", $params["SMTP_USERNAME"]);
	$boot->set_fieldpassword("SMTP_PASSWORD", "{password}", $params["SMTP_PASSWORD"]);
	
	$boot->set_RefreshSearchs();
	$form=$boot->Compile();	
	$title=$tpl->_ENGINE_parse_body("{archive_rule}");
	$html=$error."<H2>$title</H2><hr>".$form;
	
	echo $tpl->_ENGINE_parse_body($html);
	
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();	
	$boot=new boostrap_form();
	$SearchQuery=$boot->SearchFormGen("email,next");
	$sock=new sockets();
	$MailArchiverUsePerl=$sock->GET_INFO("MailArchiverUsePerl");
	$MailArchiverEnabled=$sock->GET_INFO("MailArchiverEnabled");
	if(!is_numeric($MailArchiverUsePerl)){$MailArchiverUsePerl=0;}
	if(!is_numeric($MailArchiverEnabled)){$MailArchiverEnabled=0;}	
	if($MailArchiverUsePerl==0){$error="<div class=explainWarn>{MailArchiverUsePerl_disable_text}</div>";}
	if($MailArchiverEnabled==0){$error="<div class=explainWarn>{MailArchiverEnabled_disable_text}</div>";}
	$html="
	$error
	<table style='width:100%'>
	<tr>
		<td>". button("{new_rule}","Loadjs('$page?zmd5-js=')",16)."</td>
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

function save(){
	$ou=$_SESSION["ou"];
	$md5=$_POST["zmd5"];
	$email=$_POST["email"];
	$next=$_POST["next"];
	$direction=$_POST["direction"];
	
	$_POST["SMTP_PASSWORD"]=url_decode_special_tool($_POST["SMTP_PASSWORD"]);
	if(isset($_POST["ou"])){
		$ou=$_POST["ou"];
		$ouUpd=",ou='$ou'";
	}
	$params=mysql_escape_string2(base64_encode(serialize($_POST)));
	$ou=mysql_escape_string2($ou);
	
	if($md5==null){
		$md5=md5("$email$next$direction");
		$sql="INSERT IGNORE INTO `mailarchives` (zmd5,email,next,direction,enable,ou,params) 
		VALUES ('$md5','$email','$next','$direction',1,'$ou','$params')";
	}else{
		$sql="UPDATE mailarchives SET 
		email='$email',next='$next',
		direction='$direction',
		enable='{$_POST["enable"]}',
		params='$params'$ouUpd
		WHERE zmd5='$md5'";
	}
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error." in line: ".__LINE__."\nfunction: ".__FUNCTION__;}
	
}

function SearchQuery(){
	$SearchQuery=string_to_flexquery();
	$q=new mysql();
	$page=CurrentPageName();
	$usersZ=new usersMenus();
	$AsPostfixAdministrator=$usersZ->AsPostfixAdministrator;
	$sql="SELECT * FROM `mailarchives` WHERE ou='{$_SESSION["ou"]}' $SearchQuery ORDER BY email LIMIT 0,150 ";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		echo $q->mysql_error."<br>$sql<hr>";
	}
	$tpl=new templates();
	$boot=new boostrap_form();
	// table  table-hover table-bordered 
	
	$directions["in"]="{inbound}";
	$directions["out"]="{outbound}";
	$directions["all"]="{all}";	
	$t=time();
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$users=new user($ligne["email"]);
		$mails=$users->HASH_ALL_MAILS;
		$zmd5=$ligne["zmd5"];
		$params=unserialize(base64_decode($ligne["params"]));
		$color="black";
		
		$smtpsrv=null;
		if($params["USE_SMTP_SRV"]==1){
			if($params["SMTP_USERNAME"]<>null){$params["SMTP_USERNAME"]="{$params["SMTP_USERNAME"]}@";}
			$smtpsrv="<div><i class='icon-share'></i> {$params["SMTP_USERNAME"]}{$params["SMTP_SRV"]}</div>";
		}
		
		if($ligne["enable"]==0){
			$color="#8a8a8a";
		}
		
		
		
		$direction=$tpl->_ENGINE_parse_body("{$directions[$ligne["direction"]]}");
		$bootswtich=$boot->trswitch("Loadjs('$page?zmd5-js=$zmd5')");
		if($AsPostfixAdministrator){$ouF="<td style='color:$color' $bootswtich>{$ligne["ou"]}</td>";}
		$tr[]="
		<tr id='$zmd5'>
			<td style='color:$color' $bootswtich><i class='icon-user'></i> {$ligne["email"]}<br>". @implode(",<i class='icon-envelope'></i> ", $mails)."</td>
			$ouF
			<td style='color:$color' $bootswtich><i class='icon-resize-horizontal'></i> $direction</td>
			<td style='color:$color' $bootswtich><i class='icon-user'></i> {$ligne["next"]}$smtpsrv</td>
			<td style='text-align:center'>". imgsimple("delete-32.png",null,"Delete$t('$zmd5')")."</td>
		</tr>
		";
		
		
	}
	
	$page=CurrentPageName();
	$ouF=null;
	if($AsPostfixAdministrator){$ouF="<th>{organization}</th>";}
	$delete=$tpl->javascript_parse_text("{delete} {rule} ?");
	echo $tpl->_ENGINE_parse_body("<table class='table table-bordered table-hover'>
		
			<thead>
				<tr>
					<th>{member}</th>
					$ouF
					<th>{direction}</th>
					<th>{mailbox}</th>
					<th>&nbsp;</th>
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
function delete(){
	$q=new mysql();
	$sql="DELETE FROM mailarchives WHERE zmd5='{$_POST["delete-md5"]}'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error." in line: ".__LINE__."\nfunction: ".__FUNCTION__;}
	
}



