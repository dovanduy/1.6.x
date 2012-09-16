<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.cyrus.inc');
	include_once('ressources/class.cron.inc');
	
	$users=new usersMenus();
	if(!$users->AsPostfixAdministrator){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}	
	
	if(isset($_POST["ZarafaCacheCellSize"])){Save();exit;}
	
page();


function page(){
	
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	$memdispo=$users->MEM_TOTAL_INSTALLEE*1024;
	$page=CurrentPageName();
	
	
	$ZarafaCacheCellSize=$sock->GET_INFO("ZarafaCacheCellSize");
	$ZarafaCacheObjectSize=$sock->GET_INFO("ZarafaCacheObjectSize");
	$ZarafaCacheIndexedObjectSize=$sock->GET_INFO("ZarafaCacheIndexedObjectSize");
	$ZarafaCacheQuotaSize=$sock->GET_INFO("ZarafaCacheQuotaSize");
	$ZarafaCacheQuotaLifeTime=$sock->GET_INFO("ZarafaCacheQuotaLifeTime");
	$ZarafaCacheAclSize=$sock->GET_INFO("ZarafaCacheAclSize");
	$ZarafaCacheUserSize=$sock->GET_INFO("ZarafaCacheUserSize");
	$ZarafaCacheUserDetailsSize=$sock->GET_INFO("ZarafaCacheUserDetailsSize");
	$ZarafaCacheUserDetailsLifeTime=$sock->GET_INFO("ZarafaCacheUserDetailsLifeTime");
	$ZarafaThreadStackSize=$sock->GET_INFO("ZarafaThreadStackSize");
	$ZarafaCacheServerSize=$sock->GET_INFO("ZarafaCacheServerSize");
	
	
	if(!is_numeric($ZarafaCacheCellSize)){$ZarafaCacheCellSize=round($memdispo/2);}
	if(!is_numeric($ZarafaCacheQuotaLifeTime)){$ZarafaCacheQuotaLifeTime=1;}
	if(!is_numeric($ZarafaCacheUserDetailsLifeTime)){$ZarafaCacheUserDetailsLifeTime=5;}
	if(!is_numeric($ZarafaThreadStackSize)){$ZarafaThreadStackSize=512;}
	
	if(!is_numeric($ZarafaCacheUserDetailsSize)){$ZarafaCacheUserDetailsSize=1048576;}
	$ZarafaCacheUserDetailsSize=$ZarafaCacheUserDetailsSize/1024;
	$ZarafaCacheUserDetailsSize=$ZarafaCacheUserDetailsSize/1024;
	$ZarafaCacheUserDetailsSize=round($ZarafaCacheUserDetailsSize);		
	
	if(!is_numeric($ZarafaCacheAclSize)){$ZarafaCacheAclSize=1048576;}
	$ZarafaCacheAclSize=$ZarafaCacheAclSize/1024;
	$ZarafaCacheAclSize=$ZarafaCacheAclSize/1024;
	$ZarafaCacheAclSize=round($ZarafaCacheAclSize);	
	
	
	if(!is_numeric($ZarafaCacheServerSize)){$ZarafaCacheServerSize=1048576;}
	$ZarafaCacheServerSize=$ZarafaCacheServerSize/1024;
	$ZarafaCacheServerSize=$ZarafaCacheServerSize/1024;
	$ZarafaCacheServerSize=round($ZarafaCacheServerSize);		
	
	
	if(!is_numeric($ZarafaCacheUserSize)){$ZarafaCacheUserSize=1048576;}
	$ZarafaCacheUserSize=$ZarafaCacheUserSize/1024;
	$ZarafaCacheUserSize=$ZarafaCacheUserSize/1024;
	$ZarafaCacheUserSize=round($ZarafaCacheUserSize);		
	
	
	if(!is_numeric($ZarafaCacheQuotaSize)){$ZarafaCacheQuotaSize=16777216;}
	$ZarafaCacheQuotaSize=$ZarafaCacheQuotaSize/1024;
	$ZarafaCacheQuotaSize=$ZarafaCacheQuotaSize/1024;
	$ZarafaCacheQuotaSize=round($ZarafaCacheQuotaSize);
	
	$ZarafaCacheCellSize=$ZarafaCacheCellSize/1024;
	$ZarafaCacheCellSize=$ZarafaCacheCellSize/1024;
	$ZarafaCacheCellSize=round($ZarafaCacheCellSize);
	
	
	if(!is_numeric($ZarafaCacheObjectSize)){$ZarafaCacheObjectSize=16777216;}
	$ZarafaCacheObjectSize=$ZarafaCacheObjectSize/1024;
	$ZarafaCacheObjectSize=$ZarafaCacheObjectSize/1024;
	$ZarafaCacheObjectSize=round($ZarafaCacheObjectSize);	
	
	if(!is_numeric($ZarafaCacheIndexedObjectSize)){$ZarafaCacheIndexedObjectSize=16777216;}
	$ZarafaCacheIndexedObjectSize=$ZarafaCacheIndexedObjectSize/1024;
	$ZarafaCacheIndexedObjectSize=$ZarafaCacheIndexedObjectSize/1024;
	$ZarafaCacheIndexedObjectSize=round($ZarafaCacheIndexedObjectSize);	
	$t=time();
	
	$html="
	<div id='$t'>
	<div class=explain style='font-size:13px'>{zarafa_tune_explain}</div>
	<table style='width:99%' class=form>
	
	
	<tr>
		<td class=legend style='font-size:16px'>{ZarafaThreadStackSize}:</td>
		<td style='font-size:16px'>". Field_text("ZarafaThreadStackSize",$ZarafaThreadStackSize,"font-size:16px;width:100px")."&nbsp;KB</td>
		<tD>". help_icon("{ZarafaThreadStackSize_explain}")."</td>
	</tr>	
	
	
	<tr>
		<td class=legend style='font-size:16px'>{ZarafaCacheServerSize}:</td>
		<td style='font-size:16px'>". Field_text("ZarafaCacheServerSize",$ZarafaCacheServerSize,"font-size:16px;width:100px")."&nbsp;MB</td>
		<tD>". help_icon("{ZarafaCacheServerSize_explain}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{cache_cell_size}:</td>
		<td style='font-size:16px'>". Field_text("ZarafaCacheCellSize",$ZarafaCacheCellSize,"font-size:16px;width:100px")."&nbsp;MB</td>
		<tD>". help_icon("{zcache_cell_size_explain}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{cache_object_size}:</td>
		<td style='font-size:16px'>". Field_text("ZarafaCacheObjectSize",$ZarafaCacheObjectSize,"font-size:16px;width:100px")."&nbsp;MB</td>
		<tD>". help_icon("{cache_object_size_explain}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{cache_indexedobject_size}:</td>
		<td style='font-size:16px'>". Field_text("ZarafaCacheIndexedObjectSize",$ZarafaCacheIndexedObjectSize,"font-size:16px;width:100px")."&nbsp;MB</td>
		<tD>". help_icon("{cache_indexedobject_size_explain}")."</td>
	</tr>
	
	
	<tr>
		<td class=legend style='font-size:16px'>{ZarafaCacheUserSize}:</td>
		<td style='font-size:16px'>". Field_text("ZarafaCacheUserSize",$ZarafaCacheUserSize,"font-size:16px;width:100px")."&nbsp;MB</td>
		<tD>". help_icon("{ZarafaCacheUserSize_explain}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{ZarafaCacheUserDetailsSize}:</td>
		<td style='font-size:16px'>". Field_text("ZarafaCacheUserDetailsSize",$ZarafaCacheUserDetailsSize,"font-size:16px;width:100px")."&nbsp;MB</td>
		<tD>". help_icon("{ZarafaCacheUserDetailsSize_explain}")."</td>
	</tr>
	
	
	<tr>
		<td class=legend style='font-size:16px'>{ZarafaCacheAclSize}:</td>
		<td style='font-size:16px'>". Field_text("ZarafaCacheAclSize",$ZarafaCacheAclSize,"font-size:16px;width:100px")."&nbsp;MB</td>
		<tD>". help_icon("{ZarafaCacheAclSize_explain}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{ZarafaCacheQuotaSize}:</td>
		<td style='font-size:16px'>". Field_text("ZarafaCacheQuotaSize",$ZarafaCacheQuotaSize,"font-size:16px;width:30px")."&nbsp;MB</td>
		<td>". help_icon("{ZarafaCacheQuotaSize_explain}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{ZarafaCacheQuotaLifeTime}:</td>
		<td style='font-size:16px'>". Field_text("ZarafaCacheQuotaLifeTime",$ZarafaCacheQuotaLifeTime,"font-size:16px;width:30px")."&nbsp;{minutes}</td>
		<td>". help_icon("{ZarafaCacheQuotaLifeTime_explain}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{ZarafaCacheUserDetailsLifeTime}:</td>
		<td style='font-size:16px'>". Field_text("ZarafaCacheUserDetailsLifeTime",$ZarafaCacheUserDetailsLifeTime,"font-size:16px;width:30px")."&nbsp;{minutes}</td>
		<td>". help_icon("{ZarafaCacheUserDetailsLifeTime_explain}")."</td>
	</tr>
	
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","SaveZarafTuning()",18)."</td>
	</tr>
	
	</tbody>
	</table>
	</div>
	<script>
var X_SaveZarafTuning= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	RefreshTab('main_config_zarafa2');
	}	
	

	
function SaveZarafTuning(){
	var XHR = new XHRConnection();
	XHR.appendData('ZarafaCacheCellSize',document.getElementById('ZarafaCacheCellSize').value);
	XHR.appendData('ZarafaCacheObjectSize',document.getElementById('ZarafaCacheObjectSize').value);
	XHR.appendData('ZarafaCacheIndexedObjectSize',document.getElementById('ZarafaCacheIndexedObjectSize').value);
	
	XHR.appendData('ZarafaCacheUserSize',document.getElementById('ZarafaCacheUserSize').value);
	XHR.appendData('ZarafaCacheUserDetailsSize',document.getElementById('ZarafaCacheUserDetailsSize').value);
	XHR.appendData('ZarafaCacheAclSize',document.getElementById('ZarafaCacheAclSize').value);
	XHR.appendData('ZarafaCacheQuotaSize',document.getElementById('ZarafaCacheQuotaSize').value);
	XHR.appendData('ZarafaCacheQuotaLifeTime',document.getElementById('ZarafaCacheQuotaLifeTime').value);
	
	
	XHR.appendData('ZarafaCacheUserDetailsLifeTime',document.getElementById('ZarafaCacheUserDetailsLifeTime').value);
	XHR.appendData('ZarafaThreadStackSize',document.getElementById('ZarafaThreadStackSize').value);
	XHR.appendData('ZarafaCacheServerSize',document.getElementById('ZarafaCacheServerSize').value);
	
	
	
	AnimateDiv('$t');
	XHR.sendAndLoad('$page', 'POST',X_SaveZarafTuning);	
}

</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function Save(){
	
	$ZarafaCacheQuotaLifeTime=$_POST["ZarafaCacheQuotaLifeTime"];
	$ZarafaCacheUserDetailsLifeTime=$_POST["ZarafaCacheUserDetailsLifeTime"];
	$ZarafaThreadStackSize=$_POST["ZarafaThreadStackSize"];
	
	$ZarafaCacheServerSize=$_POST["ZarafaCacheServerSize"];
	$ZarafaCacheServerSize=$ZarafaCacheServerSize*1024;
	$ZarafaCacheServerSize=$ZarafaCacheServerSize*1024;

	
	$ZarafaCacheCellSize=$_POST["ZarafaCacheCellSize"];
	$ZarafaCacheCellSize=$ZarafaCacheCellSize*1024;
	$ZarafaCacheCellSize=$ZarafaCacheCellSize*1024;
	
	$ZarafaCacheObjectSize=$_POST["ZarafaCacheObjectSize"];
	$ZarafaCacheObjectSize=$ZarafaCacheObjectSize*1024;
	$ZarafaCacheObjectSize=$ZarafaCacheObjectSize*1024;	
	
	$ZarafaCacheUserSize=$_POST["ZarafaCacheUserSize"];
	$ZarafaCacheUserSize=$ZarafaCacheUserSize*1024;
	$ZarafaCacheUserSize=$ZarafaCacheUserSize*1024;	
	
	$ZarafaCacheUserDetailsSize=$_POST["ZarafaCacheUserDetailsSize"];
	$ZarafaCacheUserDetailsSize=$ZarafaCacheUserDetailsSize*1024;
	$ZarafaCacheUserDetailsSize=$ZarafaCacheUserDetailsSize*1024;	

	$ZarafaCacheAclSize=$_POST["ZarafaCacheAclSize"];
	$ZarafaCacheAclSize=$ZarafaCacheAclSize*1024;
	$ZarafaCacheAclSize=$ZarafaCacheAclSize*1024;	

	$ZarafaCacheQuotaSize=$_POST["ZarafaCacheQuotaSize"];
	$ZarafaCacheQuotaSize=$ZarafaCacheQuotaSize*1024;
	$ZarafaCacheQuotaSize=$ZarafaCacheQuotaSize*1024;	
	
	$ZarafaCacheIndexedObjectSize=$_POST["ZarafaCacheIndexedObjectSize"];
	$ZarafaCacheIndexedObjectSize=$ZarafaCacheIndexedObjectSize*1024;
	$ZarafaCacheIndexedObjectSize=$ZarafaCacheIndexedObjectSize*1024;		
	
	$sock=new sockets();
	$sock->SET_INFO("ZarafaCacheCellSize",$ZarafaCacheCellSize);
	$sock->SET_INFO("ZarafaCacheObjectSize",$ZarafaCacheObjectSize);
	$sock->SET_INFO("ZarafaCacheIndexedObjectSize",$ZarafaCacheIndexedObjectSize);	
	
	$sock->SET_INFO("ZarafaCacheUserSize",$ZarafaCacheUserSize);	
	$sock->SET_INFO("ZarafaCacheUserDetailsSize",$ZarafaCacheUserDetailsSize);	
	$sock->SET_INFO("ZarafaCacheAclSize",$ZarafaCacheAclSize);	
	$sock->SET_INFO("ZarafaCacheQuotaSize",$ZarafaCacheQuotaSize);	
	$sock->SET_INFO("ZarafaCacheQuotaLifeTime",$ZarafaCacheQuotaLifeTime);
	$sock->SET_INFO("ZarafaCacheUserDetailsLifeTime",$ZarafaCacheUserDetailsLifeTime);
	$sock->SET_INFO("ZarafaThreadStackSize",$ZarafaThreadStackSize);
	$sock->SET_INFO("ZarafaCacheServerSize",$ZarafaCacheServerSize);			
	

	
	
	$sock->getFrameWork("zarafa.php?restart=yes");
	
}
