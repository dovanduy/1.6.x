<?php

	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.user.inc');
	
	if(isset($_GET["index"])){INDEX_CREATE();exit;}
	js();
	
function js(){
	$tpl=new templates();
	
	if(isset($_GET["encoded"])){$_GET["ou"]=base64_decode($_GET["ou"]);}
	$ou=$_GET["ou"];
	
	
	$title=$tpl->_ENGINE_parse_body('{add_user}::'.$ou);
	$ou_encoded=base64_encode($ou);
	$page=CurrentPageName();
	
	$js_add=file_get_contents('js/edit.user.js');
	
	if($ou==null){
		$tpl=new templates();
		$error=$tpl->_ENGINE_parse_body('{error_please_select_an_organization}');
		die("alert('$error')");
	}
	
	$html="
		$js_add
		function loadAdduser(){
			YahooUser(986,'$page?index=yes&ou=$ou_encoded&gpid={$_GET["gpid"]}&flexRT={$_GET["flexRT"]}','$title');
		
		}
		
		loadAdduser();
		
		
	";
	echo $html;
	
}
	

function INDEX_CREATE(){
	$ldap=new clladp();
	if($_GET["ou"]==null){die();}
	$_GET["ou"]=base64_decode($_GET["ou"]);
	$hash=$ldap->hash_groups($_GET["ou"],1);

	
	$domains=$ldap->hash_get_domains_ou($_GET["ou"]);
	
	if(count($domains)==0){
		$users=new usersMenus();
		if($users->POSTFIX_INSTALLED){
			$field_domains=Field_text('user_domain',"{$_GET["ou"]}.com","width:85px");
		}else{
			if(!preg_match("#(.+?)\.(.+)#",$_GET["ou"])){$dom="{$_GET["ou"]}.com";}else{$dom="{$_GET["ou"]}";}
			$field_domains="<code><strong>$dom</strong></code>".Field_hidden('user_domain',"$dom","width:120px");
			
		}
		
		
		
	}else{
		$field_domains=Field_array_Hash($domains,'user_domain',"style:font-size:18px;padding:3px");
	}
	
	$tpl=new templates();
	$hash[null]="{select}";
	$groups=Field_array_Hash($hash,'group_id',$_GET["gpid"],"style:font-size:18px;padding:3px");
	$error_no_password=$tpl->javascript_parse_text("{error_no_password}");	
	$error_no_userid=$tpl->javascript_parse_text("{error_no_userid}");
	$t=time();
	$title="{$_GET["ou"]}:{create_user}";
	
	$step1="
	<div style='width:98%' class=form>
	<table style='width:99%' class='TableRemove' OnMouseOver=\"javascript:HideExplainAll(1)\">
	<tr>
	<td valign='top' width=1%><img src='img/chiffre1_32.png'></td>
	<td valign='top'>
	<div style='font-size:18px;font-weight:bold;margin-bottom:5px'>{name_the_new_account_title}:</div>
	" . Field_text('new_userid',null,"font-size:18px;padding:3px;font-weight:bold;color:#C80000",null,"UserAutoChange_eMail()",null,false,"UserADDCheck(event)") ."

	</td>
	</tr>
	</table></div>";
	
	$step2="
	<div style='width:98%' class=form>
	<table style='width:99%' class='TableRemove' OnMouseOver=\"javascript:HideExplainAll(2)\">
	<tr>
	<td valign='top' width=1%><img src='img/chiffre2_32.png'></td>
	<td valign='top'>
	<div style='font-size:18px;font-weight:bold;margin-bottom:5px'>{email}</div><br>
	<input type='hidden' name='email' value='' id='email'>
	<span id='prefix_email' style='width:90px;border:1px solid #CCCCCC;padding:2px;font-size:18px;font-weight:bold;margin:2px'>
	</span>@$field_domains&nbsp;
	<div style='text-align:right;font-size:14px;'><i><a href='javascript:ChangeAddUsereMail();'>{change}</a></i>
	
	</td>
	</tr>
	</table></div>";
	
	$step3="
	<div style='width:98%' class=form>
	<table style='width:99%' class='TableRemove' OnMouseOver=\"javascript:HideExplainAll(4)\">
	<tr>
	<td valign='top' width=1%><img src='img/chiffre3_32.png'></td>
	<td valign='top'>
	<div style='font-size:18px;font-weight:bold;margin-bottom:5px'>{password}</div>
	" . Field_password("password-$t",null,"font-size:18px;padding:3px;width:190px;letter-spacing:3px",null,null,null,false,"UserADDCheck(event)") ."
	</td>
	</tr>
	</table>
	</div>
	";
	
	$step4="
	<div style='width:98%' class=form>
	<table style='width:99%' class='TableRemove' OnMouseOver=\"javascript:HideExplainAll(3)\">
	<tr>
	<td valign='top' width=1%><img src='img/chiffre4_32.png'></td>
	<td valign='top'>
	<div style='font-size:18px;font-weight:bold;margin-bottom:5px'>{group}</div>
	<div style='font-size:18px;margin-bottom:5px'>{select_user_group_title}:</div><br>$groups
	</td>
	</tr>
	</table></div>
	";
	
if($_GET["gpid"]>0){$step4="<input type='hidden' id='group_id' value='{$_GET["gpid"]}'>";}

	$html="
	<input type='hidden' id='ou-mem-add-form-user' value='{$_GET["ou"]}'>
	<input type='hidden' id='ou' value='{$_GET["ou"]}'>
	<div id='adduser_ajax_newfrm' style='margin-top:5px'>
	<div style='width:98%' class=form>
	<table style='width:100%' class=TableRemove>
	<tr>
	<td valign='top' style='width:450px;vertical-align:top'>
		<table style='width:450px'>
		<tr>
			<td valign='top' width=290px>$step1</td>
		</tr>
		<tr>
			<td valign='top'>$step2</td>
		</tr>
		<tr>
			<td valign='top'><br>$step3</td>
		</tr>
			<td valign='top'><br>$step4</td>
		</tr>
		<tr>
			<td align='right'>
				<hr>". button("{add}","UserADDSubmit()",26)."
			</td>
		</tr>			
		</table>
	</td>
	<td valign='top' style='width:50%'>
			<center style='margin-bottom:8px'><img src='img/add-woman-256.png'></center>
			<div style='padding-left:10px'>		
				<div class=text-info id='text-1' style='font-size:16px'>{name_the_new_account_explain}</div>
				<div class=text-info id='text-2' style='font-size:16px'>{user_email_text}</div>
				<div class=text-info id='text-3' style='font-size:16px'>{select_user_group_text}</div>
				<div class=text-info id='text-4' style='font-size:16px'>{give_password_text}</div>
			</div>
			
	</td>
	</tr>	
	</table>
	</div>
	</div>
	<input type='hidden' id='flexRTMEM' value='{$_GET["flexRT"]}'>
	<script>
		function VerifyFormAddUserCheck(){
			var pass;
			var uid;
			pass=document.getElementById('password-$t').value;
			uid=document.getElementById('new_userid').value;
			if(uid.length<1){alert('$error_no_userid');return false;}
			if(pass.length<1){alert('$error_no_password');return false;}
			return true;
			}
		
		function UserADDSubmit(){
			if(!VerifyFormAddUserCheck()){return;}
			UserADD$t();
		}
	
	
		function UserADDCheck(e){
			if(checkEnter(e)){UserADDSubmit();}
		}
		
		function HideExplainAll(id){
			document.getElementById('text-1').style.display='none';
			document.getElementById('text-2').style.display='none';
			document.getElementById('text-3').style.display='none';
			document.getElementById('text-4').style.display='none';  
			if(document.getElementById('text-'+id)){
				document.getElementById('text-'+id).style.display='block';
				} 
			
		}
		
function UserADD$t(){
		var XHR = new XHRConnection();
		var ou=document.getElementById('ou').value;
		if(ou.length==0){if(document.getElementById('ou-mem-add-form-user')){ou=document.getElementById('ou-mem-add-form-user').value;}}
		if(ou.length==0){Alert('Unable to stat Organization name (ou field is empty)');return;}
		
		XHR.appendData('ou',ou);
		XHR.appendData('new_userid',document.getElementById('new_userid').value);
		XHR.appendData('password',document.getElementById('password-$t').value);
		XHR.appendData('group_id',document.getElementById('group_id').value);
		XHR.appendData('email',document.getElementById('email').value);
		XHR.appendData('user_domain',document.getElementById('user_domain').value);
		
		if(document.getElementById('adduser_ajax_newfrm')){AnimateDiv('adduser_ajax_newfrm');}
		if(document.getElementById('bglego')){document.getElementById('bglego').src='img/wait_verybig.gif';}
		if(document.getElementById('member_add_to_wait')){AnimateDiv('member_add_to_wait');}
		XHR.sendAndLoad('domains.edit.user.php', 'POST',X_UserADD);	
	}		
	
	
		
		HideExplainAll();
</script>	
	
	";



echo $tpl->_ENGINE_parse_body($html);
}
?>