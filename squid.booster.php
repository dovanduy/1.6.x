<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	if(posix_getuid()==0){die();}
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["SquidBoosterMem"])){Save();exit;}
	
	
	js();
	
	
function js() {

	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{squid_booster}");
	$page=CurrentPageName();
	$html="YahooWin3('550','$page?popup=yes','$title')";
	echo $html;	
	
}

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$sock=new sockets();
	$SquidBoosterMem=$sock->GET_INFO("SquidBoosterMem");
	$SquidBoosterMemK=$sock->GET_INFO("SquidBoosterMemK");
	$SquidBoosterOnly=$sock->GET_INFO("SquidBoosterOnly");
	if(!is_numeric($SquidBoosterMem)){$SquidBoosterMem=0;}
	if(!is_numeric($SquidBoosterMemK)){$SquidBoosterMemK=50;}
	if(!is_numeric($SquidBoosterOnly)){$SquidBoosterOnly=0;}
	$disabled=$tpl->javascript_parse_text("{disabled}");
	if($SquidBoosterMem==0){$SquidBoosterMemText="&nbsp;$disabled";}
	$warn_squid_restart=$tpl->javascript_parse_text("{warn_squid_restart}");
	
	$t=time();
	$maxMem=500;
	
	$currentMem=intval($sock->getFrameWork("cmd.php?GetTotalMemMB=yes"));
	
	if($currentMem>0){
		$maxMem=$currentMem-500;
	}
	
	
	$html="

	<div class=explain style='font-size:14px' id='$t-div'>{squid_booster_text}</div>
	
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px' widht=1%>{memory}:</td>
		<td width=99%><strong style='font-size:16px' id='$t-value'>{$SquidBoosterMem}M/{$currentMem}M$SquidBoosterMemText</strong><input type='hidden' id='$t-mem' value='$SquidBoosterMem'></td>
	</tr>
	<tr>
		<td colspan=2><div id='slider$t'></div></td>
	</tr>
	</table>
	
	
	
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px' widht=1% nowrap>{max_objects_size}:</td>
		<td width=99%><strong style='font-size:16px' id='$t-value2'>{$SquidBoosterMemK}K</strong>
		<input type='hidden' id='$t-ko' value='$SquidBoosterMemK'></td>
	</tr>
	<tr>
		<td colspan=2><div id='slider2$t'></div></td>
	</tr>
	<td class=legend style='font-size:16px' widht=1% nowrap>{UseOnlyBooster}:</td>
	<td align=left'>". Field_checkbox("$t-only", 1,$SquidBoosterOnly)."</td>
	</table>
	<div style='margin-top:8px;text-align:right'>". button("{apply}","SaveBooster$t()",18)."</div>	
	
	
	
	<script>
		$(document).ready(function(){
			$('#slider$t').slider({ max: $maxMem,step:5,
			value:$SquidBoosterMem,
			 slide: function(e, ui) {
          		ChangeSlideField$t(ui.value)
        	},
        	change: function(e, ui) {
          		ChangeSlideField$t(ui.value);
        	}
		});
		
		$('#slider2$t').slider({ max: 1000,step:5,
			value:$SquidBoosterMemK,
			 slide: function(e, ui) {
          		ChangeSlideFieldK$t(ui.value)
        	},
        	change: function(e, ui) {
          		ChangeSlideFieldK$t(ui.value);
        	}
		});		
		
		
		});
		
		function ChangeSlideField$t(val){
			var disabled='';
			if(val==0){disabled='&nbsp;$disabled';}
			document.getElementById('$t-value').innerHTML=val+'M/{$currentMem}M'+disabled;
			document.getElementById('$t-mem').value=val
		}
		function ChangeSlideFieldK$t(val){
			if(val<10){
				$('#slider2$t').slider( 'option', 'value', 10 );
				val=10;
				}
			document.getElementById('$t-value2').innerHTML=val+'K';
			document.getElementById('$t-ko').value=val
		}

	var x_SaveBooster$t=function(obj){
     	var tempvalue=obj.responseText;
      	if(tempvalue.length>3){alert(tempvalue);}
      	YahooWin3Hide();
     	}	

	function SaveBooster$t(){
		if(confirm('$warn_squid_restart')){
			var XHR = new XHRConnection();
			XHR.appendData('SquidBoosterMem',document.getElementById('$t-mem').value);
			XHR.appendData('SquidBoosterMemK',document.getElementById('$t-ko').value);
			if(document.getElementById('$t-only').checked){XHR.appendData('SquidBoosterOnly',1);}else{XHR.appendData('SquidBoosterOnly',0);}
			AnimateDiv('$t-div');
			XHR.sendAndLoad('$page', 'POST',x_SaveBooster$t);		
		}
	
	}		
		
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function Save(){
	$sock=new sockets();
	if($_POST["SquidBoosterMem"]==0){$_POST["SquidBoosterOnly"]=0;}
	$sock->SET_INFO("SquidBoosterMem",$_POST["SquidBoosterMem"]);
	$sock->SET_INFO("SquidBoosterMemK",$_POST["SquidBoosterMemK"]);
	$sock->SET_INFO("SquidBoosterOnly",$_POST["SquidBoosterOnly"]);		
	$sock->getFrameWork("cmd.php?squid-restart=yes");
	
}

