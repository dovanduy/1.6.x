<?php
if(!isset($_SESSION["uid"])){session_start();}
if(!isset($GLOBALS["VERBOSE"])){$GLOBALS["VERBOSE"]=false;}

if($GLOBALS["VERBOSE"]){
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
}else{
	ini_set('display_errors', 0);
	ini_set('error_reporting', 0);
}
include_once(dirname(__FILE__)."/class.browser.detection.inc");
include_once(dirname(__FILE__)."/class.highcharts.inc");
include_once(dirname(__FILE__)."/class.sockets.inc");
include_once(dirname(__FILE__)."/class.user.inc");

class miniadm{
	
	function miniadm(){
		call_checks();
		
	}
	
	
	public function IFItsProxy(){
		$users=new usersMenus();
		if($users->WEBSTATS_APPLIANCE){return true;}
		if($users->PROXYTINY_APPLIANCE){return true;}
		if($users->SQUID_INSTALLED){return true;}
		if($users->SQUID_REVERSE_APPLIANCE){return true;}
	}
	
	public function BackupContainerPermissions($nocache=false){
		if(isset($GLOBALS["VERBOSE"])){$nocache=true;}
		if(!$nocache){if(isset($_SESSION["BackupContainerPermissions"])){return $_SESSION["BackupContainerPermissions"];}}
		$_SESSION["BackupContainerPermissions"]=array();
		

		$ARRAYPERS=$this->GetGroupsArray();
		if($GLOBALS["VERBOSE"]){
			while (list ($key, $val) = each ($ARRAYPERS) ){
				echo "<div style='color:blue'>BackupContainerPermissions:: Is a member of $key</div>\n";
			}
			
			echo "<div style='color:blue'>BackupContainerPermissions::".count($ARRAYPERS)." Groups</div>\n";}
		
		
		$q=new mysql();
		
		$sql="SELECT groupid,maxsize  FROM `storage_containers` WHERE enabled=1";
		$results = $q->QUERY_SQL($sql,'artica_backup');
		
		if($GLOBALS["VERBOSE"]){echo "<div style='color:blue'>BackupContainerPermissions::". mysql_num_rows($results)." Permissions...</div>\n";}
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$gpid=strtolower($ligne["groupid"]);
			
			if(!isset($ARRAYPERS[$gpid])){
				if($GLOBALS["VERBOSE"]){echo "<div style='color:blue'>BackupContainerPermissions::$gpid No match current group</div>\n";}
				continue;}
			$_SESSION["BackupContainerPermissions"][$gpid]=$ligne["maxsize"];
		}
		
		return $_SESSION["BackupContainerPermissions"];
		
	}
	
	private function GetGroupsArray(){
		
		if(isset($GLOBALS[__CLASS__.__FUNCTION__])){return $GLOBALS[__CLASS__.__FUNCTION__];}
		$ldap=new clladp();
		$ARRAYPERS=array();
		if($ldap->IsKerbAuth()){
			include_once(dirname(__FILE__)."/class.external.ad.inc");
			$ad=new external_ad_search();
			$groups=$ad->GroupsOfMember($_SESSION["uid"]);
			if(!is_array($groups)){$groups=array();}
			while (list ($dn, $name) = each ($groups) ){$ARRAYPERS[strtolower($dn)]=true;}
		
		}else{
			$users = new user ( $_SESSION["uid"]);
			$groups = $users->Groups_list();
			if(!is_array($groups)){$groups=array();}
			while (list ($gid, $name) = each ($groups) ){$ARRAYPERS[$gid]=true;}
		}
		
		$GLOBALS[__CLASS__.__FUNCTION__]=$ARRAYPERS;
		return $ARRAYPERS;
		
	}
	
	public function ProxyCategoriesPermissions($nocache=false){
		if(!$nocache){if(isset($_SESSION["ProxyCategoriesPermissions"])){return $_SESSION["ProxyCategoriesPermissions"];}}
		$_SESSION["ProxyCategoriesPermissions"]=array();
		if(!$this->IFItsProxy()){return $_SESSION["ProxyCategoriesPermissions"];}
		
		
		$ldap=new clladp();

		$ARRAYPERS=$this->GetGroupsArray();
		$q=new mysql_squid_builder();
		$sql="SELECT *  FROM `webfilter_catprivs`";
		$results = $q->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {
			$md5=$ligne["zmd5"];
			$categorykey=$ligne["categorykey"];
			$groupdata=$ligne["groupdata"];
			preg_match("#^@(.+?):(.+)#", $groupdata,$re);
			$GroupName=$re[1];
			$gpdata=strtolower(trim(base64_decode($re[2])));
			$allowrecompile=$ligne["allowrecompile"];
			if(!isset($ARRAYPERS[$gpdata])){continue;}
			$_SESSION["ProxyCategoriesPermissions"][$categorykey]=$allowrecompile;
		}
		
		return $_SESSION["ProxyCategoriesPermissions"];
	
	}

	public function time_to_date($xtime,$time=false){
		$tpl=new templates();
		$dateT=date("{l} {F} d",$xtime);
		if($time){$dateT=date("{l} {F} d H:i:s",$xtime);}
		if($tpl->language=="fr"){
			$dateT=date("{l} d {F} ",$xtime);
			if($time){$dateT=date("{l} d {F} H:i:s",$xtime);}
		}
		return $tpl->_ENGINE_parse_body($dateT);
	
	}
	
	
	public function squid_load_dynamic_acls($force=false){
		
		if($_SESSION["VirtAclUser"]){
			writelogs("VirtAclUser = TRUE, RETURN -> SQUID_DYNAMIC_ACLS_VIRTUALS",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			$_SESSION["SQUID_DYNAMIC_ACLS"]=$_SESSION["SQUID_DYNAMIC_ACLS_VIRTUALS"];
			return;
		}
		
		writelogs("squid_load_dynamic_acls:: run()",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		
		include_once(dirname(__FILE__)."/class.squid.dynamic-acls.inc");
		$dyn=new dynamic_acls_auth($_SESSION["uid"]);
		$dyn->SQUID_DYNAMIC_ACLS();

		
			
	}

	
	function NavBar(){
		$users=new usersMenus();
		$hrefProfile=null;
		$DisplayName=null;
		if($_SESSION["uid"]<>-100){
			$ct=new user($_SESSION["uid"]);
			if($ct->DisplayName==null){$ct->DisplayName=$_SESSION["uid"];}
			$hrefProfile="miniadm.profile.php";
			$DisplayName=$ct->DisplayName;
		}
		
		if($_SESSION["uid"]==-100){
			include(dirname(__FILE__)."/settings.inc");
			$DisplayName=$_GLOBAL["ldap_admin"];
			$hrefProfile="#";
		}
		
		$tpl=new templates();
		$storage=null;
		$messaging=null;
		
		$hide_menu=$tpl->javascript_parse_text("{hide_menu}");
		$show_menu=$tpl->javascript_parse_text("{show_menu}");
		if($users->POSTFIX_INSTALLED){
			$messaging="<li><a href=\"miniadm.messaging.php\">{messaging}</a></li>";
		}
		
		if(count($this->BackupContainerPermissions())>0){
			$storage="<li><a href=\"miniadm.backup.container.php\">{storage}</a></li>";
		}
		
			$html="
			<button type=\"button\" class=\"btn btn-navbar\" data-toggle=\"collapse\" data-target=\".nav-collapse\">
            <span class=\"icon-bar\"></span>
            <span class=\"icon-bar\"></span>
            <span class=\"icon-bar\"></span>
          </button>
          
          <div class=\"nav-collapse collapse\">
            <p class=\"navbar-text pull-right\">
           &nbsp;&nbsp;&nbsp;<i class='icon-user icon-white'></i> {logged_in_as} <a href=\"$hrefProfile\" class=\"navbar-link\">$DisplayName</a>
            </p>
             <p class=\"navbar-text pull-right\">
             	<a href=\"miniadm.logoff.php\" class=\"navbar-link\"><i class='icon-off icon-white'></i> {logoff}</a>
            </p>           
            
            
            <ul class=\"nav\">
              <li class=\"active\"><a href=\"miniadm.index.php\"><i class='icon-home icon-white'></i> {home}</a></li>
              $messaging$storage
              <li><a href=\"miniadm.about.php\">{about2}</a></li>
              <li><a href=\"javascript:blur();\" OnClick=\"javascript:MiniAdmHideMenu()\" id='hide-menu'>$hide_menu</a></li>
             
            </ul>
          </div>
		<script>
			function MiniAdmHideMenu(){
				var content=document.getElementById('left-menu').innerHTML;
				if(content.length>10){
					document.getElementById('left-menu').innerHTML='';
					document.getElementById('hide-menu').innerHTML='$show_menu';
					document.getElementById('left-menu-bar').style.width ='0px';
					document.getElementById('left-menu').classList.remove('span3');
					document.getElementById('left-menu').classList.remove('bs-docs-sidebar');
			
				}else{
					document.getElementById('left-menu-bar').style.width ='350px';
					LoadAjax('left-menu','miniadm.index.php?left-menu=yes');
					document.getElementById('hide-menu').innerHTML='$hide_menu';
					document.getElementById('left-menu').className='span3 bs-docs-sidebar';
					
				}
				
			}
		</script>
		
			
			";	
			
			$tpl=new templates();
			return $tpl->_ENGINE_parse_body($html);
		
	}
	

	
	function leftmenu(){
		$browser=browser_detection();
		$tpl=new templates();
		if(!isset($_SESSION["SQUID_DYNAMIC_ACLS"])){$_SESSION["SQUID_DYNAMIC_ACLS"]=array();}
		//if(!$GLOBALS["VERBOSE"]){if(isset($_SESSION[__FILE__][__FUNCTION__])){echo $_SESSION[__FILE__][__FUNCTION__];return;}}
		writelogs("Building menu",__FUNCTION__,__FILE__,__LINE__);
		$MAIL_ARCHIVE=true;
		$AMAVIS_USERS=true;
		$AMAVIS_ALL=true;
		$admorg=array();
		$users=new usersMenus();
		$sock=new sockets();
		$ASRAD=false;
		$admorgt=null;
		$prxyt=null;
		$admt=null;
		$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
		if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
		$EnableAmavisDaemon=$sock->GET_INFO("EnableAmavisDaemon");
		$ProxyUseArticaDB=$sock->GET_INFO("ProxyUseArticaDB");
		$AmavisPerUser=$sock->GET_INFO("AmavisPerUser");
		$MailArchiverEnabled=$sock->GET_INFO("MailArchiverEnabled");
		$MailArchiverUsePerl=$sock->GET_INFO("MailArchiverUsePerl");
		$WebstatisticsByMember=$sock->GET_INFO("WebstatisticsByMember");
		$SQUIDEnable=trim($sock->GET_INFO("SQUIDEnable"));
		if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
		if(!is_numeric($WebstatisticsByMember)){$WebstatisticsByMember=0;}
		$_SESSION["WebstatisticsByMember"]=$WebstatisticsByMember;
		
		if(!is_numeric($EnableAmavisDaemon)){$EnableAmavisDaemon=0;}
		if(!is_numeric($AmavisPerUser)){$AmavisPerUser=0;}
		if(!is_numeric($MailArchiverEnabled)){$MailArchiverEnabled=0;}
		if(!is_numeric($MailArchiverUsePerl)){$MailArchiverUsePerl=0;}
		if($MailArchiverEnabled==0){$MAIL_ARCHIVE=false;}
		if($MailArchiverUsePerl==0){$MAIL_ARCHIVE=false;}
		$SquidActHasReverse=0;
		
		if($EnableAmavisDaemon==0){$AMAVIS_USERS=false;$AMAVIS_ALL=false;}
		if($AmavisPerUser==0){$AMAVIS_USERS=false;}
		$Privileges_members_admins=Privileges_members_admins();
		if(!$users->AMAVIS_INSTALLED){$AMAVIS_USERS=false;$AMAVIS_ALL=false;}
		if(!$users->POSTFIX_INSTALLED){$MAIL_ARCHIVE=false;$AMAVIS_USERS=false;$AMAVIS_ALL=false;}
		if(isset($_SESSION["RADIUS_ID"])){
			if($_SESSION["RADIUS_ID"]>0){
				$ASRAD=true;
			}
		}
		
		if(!isset($_SESSION["ISACTIVEDIRECTORY"])){$ldap=new clladp();$_SESSION["ISACTIVEDIRECTORY"]=$ldap->IsKerbAuth();}
		
		if(!isset($_SESSION["NGNIX_PRIVS"])){
			include_once(dirname(__FILE__)."/class.mysql.squid.builder.php");
			$q=new mysql_squid_builder();
			if($q->TABLE_EXISTS("reverse_privs")){
				$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(*) as tcount FROM reverse_privs WHERE uid='{$_SESSION["uid"]}'"));
				if(!$q->ok){echo "<p class=error>$q->mysql_error</p>";}
				if(!is_numeric($ligne["tcount"])){$ligne["tcount"]=0;}
				$_SESSION["NGNIX_PRIVS"]=$ligne["tcount"];
			}else{
				$_SESSION["NGNIX_PRIVS"]=0;
			}
		}
		
		
		
		if($users->SQUID_INSTALLED){
			$sock=new sockets();
			$SquidActHasReverse=$sock->GET_INFO("SquidActHasReverse");
			if(!is_numeric($SquidActHasReverse)){$SquidActHasReverse=0;}
			if($SquidActHasReverse==1){
				$explainSquidActHasReverse="<div class=explain>{explain_freewebs_reverse}</div>";
			}
		}		
		
		$tpl=new templates();
		$monitorprox=array();
		$adm=array();
		$postfix=array();
		$messt=null;
		$postfixt=null;
		$messou=array();
		$styleGrey=null;
		$subactive="subactive";
		
		$AS_PROXY_SECTION=false;
		if($users->SQUID_INSTALLED){$AS_PROXY_SECTION=true;}
		if($users->APP_FTP_PROXY){$AS_PROXY_SECTION=true;}
		if($users->NGINX_INSTALLED){$AS_PROXY_SECTION=true;}
		
		
		
		if(is_array($_SESSION["privs"])){
			$r=$_SESSION["privs"];
			while (list ($key, $val) = each ($r) ){
				$_SESSION[$key]=$val;
			}
		}
		
		if($browser=="ie"){$subactive="active";}
		
		if(!$_SESSION["VirtAclUser"]){$StandardUSR["{profile}"]="miniadm.profile.php";}
		
		if(!$ASRAD){
			if($users->POSTFIX_INSTALLED){
				$StandardUSR["{messaging_options}"]="miniadm.messaging.php";
				if($users->AMAVIS_INSTALLED){
					if($EnableAmavisDaemon==1){
						if($AmavisPerUser==1){
							$StandardUSR["{antispam_settings}"]="miniadm.messaging.amavis.php";
						}
						
					}
					
				}
			}
		}
		
		if($users->SQUID_INSTALLED){
			if($WebstatisticsByMember==1){
				if(!$_SESSION["VirtAclUser"]){
					$StandardUSR["{web_statistics}"]="miniadm.webstats.ByMember.php?member-value={$_SESSION["uid"]}";
				}
			}
		}
		
		$BackupContainerPermissions=$this->BackupContainerPermissions();
		if(count($BackupContainerPermissions)>0){
			$StandardUSR["{storage}"]="miniadm.backup.container.php";
		}
		
		
		
		if($users->AsOrgAdmin){
			if(!$ASRAD){$admorg["{users_and_groups}"]="miniadmin.members.php";}
		}
		
		
		if($users->FREERADIUS_INSTALLED){
			if($Privileges_members_admins){
				if($GLOBALS["VERBOSE"]){echo " <strong>Add: manage_system -> miniadm.system.php</strong><br>\n";}
				$adm["{manage_system}"]="miniadm.system.php";
			}
		}
		

		
		
		if($users->AsMessagingOrg){
				if(!$ASRAD){
					if(!$users->AsOrgAdmin){$messou["{users_and_groups}"]="miniadmin.members.php";}
					$messou["{parameters}"]="miniadmin.messaging.organization.php";
					if($AMAVIS_USERS){$messou["{antispam_settings}"]="miniadm.messaging.ou.amavis.php";}		
					if($MAIL_ARCHIVE){$messou["{archive_module}"]="miniadmin.messaging.archive.php";}
				}
			}
			
	if($users->POSTFIX_INSTALLED){
		if($users->AsAnAdministratorGeneric){
			$postfix["{APP_POSTFIX}"]="miniadmin.postfix.php";
		}
		
		if($users->AsPostfixAdministrator){if($AMAVIS_ALL){$postfix["{APP_AMAVIS}"]="miniadmin.postfix.amavis.php";}}
		if($users->AsMailBoxAdministrator){
			if($users->ZARAFA_INSTALLED){
				$postfix["{mailboxes}"]="miniadmin.zarafa.php";
			}
		}		
					
		
		
			
		}
		
		
		if( ($users->AsSquidAdministrator) OR ($users->AsWebMaster) ){
			
			if($users->NGINX_INSTALLED){
				if($GLOBALS["VERBOSE"]){echo " <strong>Add: manage_system -> miniadm.system.php Line:".__LINE__."</strong><br>\n";}
				$adm["{manage_system}"]="miniadm.system.php";
			}
		}
			
		if($_SESSION["NGNIX_PRIVS"]>0){
			$prxy["{APP_PROXY}"]="miniadmin.proxy.index.php";
		}
		
			
		if($AS_PROXY_SECTION){
				if($Privileges_members_admins){
					if($GLOBALS["VERBOSE"]){echo " <strong>Add: manage_system -> miniadm.system.php Line:".__LINE__."</strong><br>\n";}
					$adm["{manage_system}"]="miniadm.system.php";
					$prxy["{APP_PROXY}"]="miniadmin.proxy.index.php";
				}
			
			
				if($users->AsDansGuardianAdministrator){
					$adm["{manage_system}"]="miniadm.system.php";
					$prxy["{WEB_FILTERING}"]="miniadmin.webfiltering.php";
				}			
			
			$this->squid_load_dynamic_acls();
			$this->ProxyCategoriesPermissions();
			
			
			
			if(count($_SESSION["SQUID_DYNAMIC_ACLS"])>0){
				$dynamic_acls_newbee_explain=str_replace("%s", count($_SESSION["SQUID_DYNAMIC_ACLS"]), $tpl->_ENGINE_parse_body("{dynamic_acls_newbee_explain}"));
				$prxy["{dynamic_acls_newbee}"]="miniadmin.proxy.dynamic.acls.php";
					
			}
			
			if(count($_SESSION["ProxyCategoriesPermissions"])>0){
				$prxy["{categories}"]="miniadmin.proxy.dynamic.categories.php";
				
			}
	
			if($users->AsProxyMonitor){$prxy["{proxy_monitor}"]="miniadmin.proxy.monitor.php";}
			
			
		}			
			
		if($Privileges_members_admins){
			$adm["{manage_system}"]="miniadm.system.php";
					
		}			
			
			
		if($_SESSION["AsWebStatisticsAdministrator"]){
			if($EnableRemoteStatisticsAppliance==0){
				$prxy["{web_statistics}"]="miniadm.webstats-start.php";
			}
		}
		

		if(isNetSessions() ) {
			if($GLOBALS["VERBOSE"]){echo " <strong>Add: manage_system -> miniadm.system.php Line:".__LINE__."</strong><br>\n";}
			$adm["{manage_system}"]="miniadm.system.php";
		}
		
		if($users->AsSystemWebMaster){
			if($GLOBALS["VERBOSE"]){echo " <strong>Add: manage_system -> miniadm.system.php Line:".__LINE__."</strong><br>\n";}
			$adm["{manage_system}"]="miniadm.system.php";
			$adm["FreeWebs"]="miniadm.freewebs.php";
		}		
		
		if($users->AsSystemAdministrator){
			if($GLOBALS["VERBOSE"]){echo " <strong>Add: manage_system -> miniadm.system.php Line:".__LINE__."</strong><br>\n";}
			$adm["{manage_system}"]="miniadm.system.php";
			$adm["{events}"]="miniadm.system.events.php";
		}
		
		if($SQUIDEnable==0){
			unset($prxy["{web_statistics}"]);
			unset($prxy["{WEB_FILTERING}"]);
			unset($prxy["{dynamic_acls_newbee}"]);
			unset($prxy["{proxy_monitor}"]);
		}
		
		
		if($users->SAMBA_INSTALLED){
			$EnableSambaVirtualsServers=$sock->GET_INFO("EnableSambaVirtualsServers");
			if(!is_numeric($EnableSambaVirtualsServers)){$EnableSambaVirtualsServers=0;}
		
		
			if($EnableSambaVirtualsServers==1){
				if(count($_SESSION["VIRTUALS_SERVERS"])>0){
					if(count($_SESSION["VIRTUALS_SERVERS"])>1){
						$adm["{file_sharing_services}"]="miniadm.samba-multiple.php";
					}
						
				}
			}
		
		}
		
		
		if(!$this->IFItsProxy()){
			$monitorprox=array();
			$prxy=array();
			
		}
		
		if(!$users->POSTFIX_INSTALLED){
			$messou=array();
			$postfix=array();
		}	

// **********************************************************************************************
		$ff=array();
		if(count($StandardUSR)>0){
			$ff[]="<li class=\"active\"><a href=\"#\">{myaccount}</a></li>";
			while (list ($a, $b) = each ($StandardUSR) ){
				$title=$tpl->_ENGINE_parse_body($a);
				$ff[]="<li style='text-transform:capitalize'><a href=\"$b\"><i class=\"icon-chevron-right\"></i> $title</a></li>";
			}
		
		}
		if(count($ff)>0){$EndUsers=@implode("\n", $ff);}
		
// **********************************************************************************************		
		
		
// **********************************************************************************************		
		$ff=array();
		if(count($adm)>0){
			$ff[]="<li class=\"active\"><a href=\"#\">{system}</a></li>";
			while (list ($a, $b) = each ($adm) ){
				$title=$tpl->_ENGINE_parse_body($a);
				$ff[]="<li style='text-transform:capitalize'><a href=\"$b\"><i class=\"icon-chevron-right\"></i> $title</a></li>";
			}

		}
		if(count($ff)>0){$admt=@implode("\n", $ff);}	
		
	// **********************************************************************************************
		$ff=array();
		if(count($admorg)>0){
			if(trim($_SESSION["ou"])<>null){
				if(!$_SESSION["ISACTIVEDIRECTORY"]){
					$ff[]="<li class=\"active\"><a href=\"#\">{organization} {$_SESSION["ou"]}</a></li>";
					while (list ($a, $b) = each ($admorg) ){
						$title=$tpl->_ENGINE_parse_body($a);
						$ff[]="<li style='text-transform:capitalize'><a href=\"$b\"><i class=\"icon-chevron-right\"></i> $title</a></li>";
					}
				}
			}
				
		}
		if(count($ff)>0){$admorgt=@implode("\n", $ff);}	
		
		
	// **********************************************************************************************		
		
		$ff=array();
		if(count($messou)>0){
			if(trim($_SESSION["ou"])<>null){
				$ff[]="<li class=\"active\"><a href=\"#\">{messaging} {$_SESSION["ou"]}</a></li>";
				while (list ($a, $b) = each ($messou) ){
					$title=$tpl->_ENGINE_parse_body($a);
					$ff[]="<li style='text-transform:capitalize'><a href=\"$b\"><i class=\"icon-chevron-right\"></i> $title</a></li>";
				}		
			}
		}
		if(count($ff)>0){$messt=@implode("\n", $ff);}
 	
	// **********************************************************************************************	
		$ff=array();
		if(count($postfix)>0){
			$users=new usersMenus();
			$ff[]="<li class=\"active\"><a href=\"#\">{messaging} {server}</a></li>";
			while (list ($a, $b) = each ($postfix) ){
				$title=$tpl->_ENGINE_parse_body($a);
				$ff[]="<li style='text-transform:capitalize'><a href=\"$b\"><i class=\"icon-chevron-right\"></i> $title</a></li>";
			}
				
		}
		if(count($ff)>0){$postfixt=@implode("\n", $ff);}
		
		// **********************************************************************************************
		
		$ff=array();
		if((count($prxy)>0) OR (count($monitorprox)>0)){
			$ff[]="<li style='text-transform:capitalize' class=\"active\"><a href=\"#\">Proxy</a></li>";
			if(count($monitorprox)>0){
				
				while (list ($a, $b) = each ($monitorprox) ){
					$title=$tpl->_ENGINE_parse_body($a);
					$ff[]="<li style='text-transform:capitalize'><a href=\"$b\"><i class=\"icon-chevron-right\"></i> $title</a></li>";
				}				
			}
			
			// **********************************************************************************************			
			
			
			if(count($prxy)>0){
				while (list ($a, $b) = each ($prxy) ){
					$title=$tpl->_ENGINE_parse_body($a);
					$ff[]="<li style='text-transform:capitalize'><a href=\"$b\"><i class=\"icon-chevron-right\"></i> $title</a></li>";
				}
			}
		
		}
		
		if(count($ff)>0){$prxyt=@implode("\n", $ff);}

		// **********************************************************************************************		

		return $tpl->_ENGINE_parse_body("<ul class=\"nav nav-list bs-docs-sidenav affix\">
				$EndUsers
				$admt
				$admorgt
				$messt
				$postfixt
				$prxyt
			</ul>
				
				");
	}
	

	
	
}



class boostrap_form{
	private $t=0;
	private $fields_array=array();
	private $lockedForm=false;
	private $js=array();
	private $buttonname="{apply}";
	private $CloseYahoo=null;
	private $ExecuteByClassName='';
	private $FlexID="";
	private $ExecuteByClassNameForced=false;
	private $title_form='';
	private $ajax_page='';
	private $PROTO="";
	private $desc_form='';
	private $Callback='';
	private $ToolTips=array();
	private $buttonsAdd=array();
	private $ScriptsToAdd=array();
	private $id_animate;
	private $AjaxFinal=array();
	private $form_error=null;
	private $cachedID=array();
	private $fields_ids=array();
	private $tpl;
	public $SQUID_CATEGORIES_FAM=array();
	public $DisableSpecialCharacters=0;
	function boostrap_form(){
		$this->t=time();
		$this->id_animate="animateJS$this->t";
		$this->tpl=new templates();;
		$this->SQUID_CATEGORIES_FAM[1]["TITLE"]="{dangerous_websites}";
		$this->SQUID_CATEGORIES_FAM[1]["EXPLAIN"]="{dangerous_websites_fam_explain}";
		$this->SQUID_CATEGORIES_FAM[2]["TITLE"]="{websites_network_pollution}";
		$this->SQUID_CATEGORIES_FAM[2]["EXPLAIN"]="{websites_network_pollution_explain}";
		$this->SQUID_CATEGORIES_FAM[3]["TITLE"]="{websites_human_suspects}";
		$this->SQUID_CATEGORIES_FAM[3]["EXPLAIN"]="{websites_human_suspects_explain}";	
		$this->SQUID_CATEGORIES_FAM[4]["TITLE"]="{websites_heavy_cat}";
		$this->SQUID_CATEGORIES_FAM[4]["EXPLAIN"]="{websites_heavy_cat_explain}";
		$this->SQUID_CATEGORIES_FAM[5]["TITLE"]="{websites_noprod}";
		$this->SQUID_CATEGORIES_FAM[5]["EXPLAIN"]="{websites_noprod_explain}";
		
		$sock=new sockets();
		$this->DisableSpecialCharacters=$sock->GET_INFO("DisableSpecialCharacters");
		if(!is_numeric($this->DisableSpecialCharacters)){$this->DisableSpecialCharacters=0;}
	}
	
	
	public function time_to_date($xtime,$time=false){
		$tpl=new templates();
		$dateT=date("{l} {F} d",$xtime);
		if($time){$dateT=date("{l} {F} d H:i:s",$xtime);}
		if($tpl->language=="fr"){
			$dateT=date("{l} d {F} ",$xtime);
			if($time){$dateT=date("{l} d {F} H:i:s",$xtime);}
		}
		return $tpl->_ENGINE_parse_body($dateT);
	
	}	
	
	
	public function trswitch($js){
		$java="OnClick=\"javascript:$js\"";
		return "$java OnMouseOver=\"javascript:this.style.cursor='pointer';\" OnMouseOut=\"javascript:this.style.cursor='default';\"";
	}
	
	
	public function set_button($buttonname){
		$this->buttonname=$buttonname;
		
	}
	
	
	public function set_Newbutton($buttonname,$js){
		$this->buttonsAdd[$buttonname]=$js;
	
	}	
	
	
	public function set_form_locked(){
		$this->lockedForm=true;
	}
	
	public function set_form_error($error){
		$this->form_error=$this->tpl->_ENGINE_parse_body($error);
	}
	
	public function setAjaxPage($page){
		$this->ajax_page=$page;
		
	}
	
	public function set_CallBack($function){
		$this->Callback=$function;
	
	}	
	
	public function set_PROTO($PROTO){
		$this->PROTO=$PROTO;
	}
	
	public function set_hidden($field_name,$value){
		
		$this->fields_array["$field_name"]=array("TYPE"=>"HIDDEN","VALUE"=>$value,);		
	}
	
	public function set_spacertitle($title){
		
		
		$this->fields_array[$title]=array("TYPE"=>"TITLE","VALUE"=>$this->tpl->_ENGINE_parse_body($title));	
	}
	
	public function set_subtitle($title){
		$this->fields_array[$title]=array("TYPE"=>"SUBTITLE","VALUE"=>$this->tpl->_ENGINE_parse_body($title));
	}
	
	
	function set_spacerexplain($explain){
		$this->fields_array[$explain]=array("TYPE"=>"EXPLAIN_SPACER","VALUE"=>$this->tpl->_ENGINE_parse_body($explain));
	}
	
	public function set_CloseYahoo($id){
		$this->CloseYahoo=$id;
	}
	
	public function set_RefreshSearchs(){
	
		$this->ExecuteByClassName="SearchFunction";
	}
	
	public function set_RefreshFlex($id){
		$this->FlexID=$id;
	}
	
	public function set_RefreshSearchsForced(){
		$this->ExecuteByClassName="SearchFunction";
		$this->ExecuteByClassNameForced=true;
	}
	
	public function set_AjaxFinal($script){
		$this->AjaxFinal[]=$script;
	}
	
	public function set_formtitle($title){
		$this->title_form=$this->tpl->_ENGINE_parse_body($title);
	}
	public function set_formdescription($text){
		$this->desc_form=$this->tpl->_ENGINE_parse_body($text);
	}
	
	public function set_checkbox($field_name,$caption,$value,$params=array()){
		$this->fields_array["$field_name"]=array(
				"TYPE"=>"CHECKNUM",
				"CAPTION"=>$caption,
				"VALUE"=>$value,
				"FIELDN"=>$this->genId($field_name),
				"PARAMS"=>$params
		
		);
		
	}
	public function set_checkboxYN($field_name,$caption,$value,$params=array()){
		$this->fields_array["$field_name"]=array(
				"TYPE"=>"CHECKYN",
				"CAPTION"=>$caption,
				"VALUE"=>$value,
				"FIELDN"=>$this->genId($field_name),
				"PARAMS"=>$params
	
		);
	
	}	
	public function set_checkboxYesNo($field_name,$caption,$value,$params=array()){
		$this->fields_array["$field_name"]=array(
				"TYPE"=>"CHECKYesNo",
				"CAPTION"=>$caption,
				"VALUE"=>$value,
				"FIELDN"=>$this->genId($field_name),
				"PARAMS"=>$params
	
		);
	
	}	
	public function TableCompile($array,$rows){
		$tFunc="SetSearchParams{$_GET["divid"]}";
		$Params=unserialize(base64_decode(url_decode_special_tool($_GET["Params"])));
		$f[]="<table class='table table-bordered table-hover'>
			<thead>
				<tr>";
		
		while (list ($key, $text) = each ($array) ){
			$colspan=0;
			$colspan_text=null;
			$style=null;
			if($key=="delete"){
				$f[]="<th>&nbsp;</th>";
				continue;
			}
			if($key=="choose"){
				$f[]="<th>&nbsp;</th>";
				continue;
			}
			
			if(preg_match("#(.+?):colspan=([0-9]+)#", $text,$re)){
				$colspan=$re[2];
				$text=$re[1];
			}
			
			if(preg_match("#(.+?):no#", $key,$re)){
				if(preg_match("#([0-9]+)px#", $text,$re)){
					$style="style='width:{$re[1]}px;vertical-align:top'";
					$text=str_replace("{$re[1]}px", "",$text);
				}
				$text=$this->tpl->_ENGINE_parse_body($text);
				$f[]="<th $colspan_text>$text</th>";
				continue;
			}
			
			if(!isset($Params["order-$key"])){$Params["order-$key"]=0;}
			if($Params["order-$key"]==1){$Params["order-$key"]=0;}else{$Params["order-$key"]=1;}
			$tri=$this->trswitch("$tFunc('order-$key','{$Params["order-$key"]}')");
			$text=$this->tpl->_ENGINE_parse_body($text);
			if($colspan>0){$colspan_text=" colspan=$colspan";}
			$f[]="<th $tri$colspan_text><u>$text</u></th>";
		}
		$f[]="</tr>
		</thead>
	<tbody>";
		
	$f[]=@implode("\n", $rows)." </tbody></table>";
	return @implode("\n", $f);
	}
	
	public function TableOrder($array){
		$Params=unserialize(base64_decode(url_decode_special_tool($_GET["Params"])));
		$FIRST=null;
		if(isset($Params["FIRST"])){
			$key=$Params["FIRST"];
			$FIRST=$key;
			$value=$Params[$key];
			if(preg_match("#order-(.+)#", $key,$re)){
				if($value==1){
				$f[]="{$re[1]} ASC";
				}else{$f[]="{$re[1]} DESC";}
			}
		}
		
		if(is_array($Params)){
			while (list ($key, $value) = each ($Params) ){
				if(preg_match("#order-(.+)#", $key,$re)){
					if($FIRST==$re[1]){continue;}
					if($value==1){$array[$re[1]]="ASC";}else{$array[$re[1]]="DESC";}
				}
			}
		}
		
		while (list ($field, $dir) = each ($array) ){	
			$f[]="`$field` $dir";
			
		}
		return @implode(",",$f);
	}
	
	public function set_AddScript($function,$array){
		$page=CurrentPageName();

		
		
		
		$script[]="function $function(){";
		if(isset($array["XHR"])){
			$this->ScriptsToAdd[]="var x_$function = function (obj) {
				var res=obj.responseText;
				document.getElementById('$this->id_animate').innerHTML='';	
				RefreshNavs$this->id_animate();
				if(res.length>3){alert(res);return;}
				
				ExecuteByClassName('SearchFunction');
			}";
			
			
			$script[]="\tvar XHR = new XHRConnection();";
			while (list ($key, $val) = each ($array["XHR"]) ){
				$script[]="\tXHR.appendData('$key', '$val');";
			}
			
			$script[]="\tAnimateDiv('$this->id_animate');";
			$script[]="\tXHR.sendAndLoad('$page', 'POST',x_$function);";
			
		}
		$script[]="}";
		
		
		$this->ScriptsToAdd[]=@implode("\n", $script);
		
		
	}
	
	public function set_field($field_name,$caption,$value,$params=array()){
		
		$this->fields_array["$field_name"]=array(
				"TYPE"=>"TEXT",
				"CAPTION"=>$caption,
				"VALUE"=>$value,
				"FIELDN"=>$this->genId($field_name),
				"PARAMS"=>$params
				
				);
	}
	
	public function set_textarea($field_name,$caption,$value,$params=array()){
	
		$this->fields_array["$field_name"]=array(
				"TYPE"=>"TEXTAREA",
				"CAPTION"=>$caption,
				"VALUE"=>$value,
				"FIELDN"=>$this->genId($field_name),
				"PARAMS"=>$params
	
		);
	}	
	
	
	
	
	
	public function set_fieldpassword($field_name,$caption,$value,$params=array()){
	
		$this->fields_array["$field_name"]=array(
				"TYPE"=>"PASSWORD",
				"CAPTION"=>$caption,
				"VALUE"=>$value,
				"FIELDN"=>$this->genId($field_name),
				"PARAMS"=>$params
	
		);
	}	
	
	
	public function set_list($field_name,$caption,$values=array(),$default,$params=array()){
		$this->fields_array["$field_name"]=array(
				"TYPE"=>"LIST",
				"CAPTION"=>$caption,
				"VALUES"=>$values,
				"VALUE"=>$default,
				"FIELDN"=>$this->genId($field_name),
				"PARAMS"=>$params
		
		);		
		
	}
	
	
	private function genId($field_name){
		
		$bignum=rand($this->t,$this->t+99999);
		$id="$field_name-$bignum";
		if(isset($this->cachedID[$id])){return $this->genId($field_name);}
		$this->cachedID[$id]=true;
		$this->fields_ids[$field_name]=$id;
		return $id;
	}
	
	public function build_tab($array,$id=null){
		if(!is_array($array)){return null;}
		$md5=md5(serialize($array).time());
		if($id==null){$id="MyTab".md5($md5.time());}
		$tpl=new templates();
		$f[]="<ul id='$id' class='nav nav-tabs' >";
		$c=0;
		while (list ($head, $target) = each ($array) ){
			$head=$tpl->_ENGINE_parse_body($head);
			$c++;
			$divs[]="<div id='$id-$c'></div>";
			$jsHide[]="document.getElementById('$id-$c').innerHTML = '';document.getElementById('$id-$c').style.display = \"none\";";
			$f[]="	<li class=FirstLetter><a data-target='#$id-$c' data-toggle='tab' href='$target'>$head</a></li>";
	
		}
	
	
		$f[]="</ul>";
		$f[]=@implode("", $divs);
		
		$t=time();
		$f[]="<script>
		
		function LoadTab$md5(){
			$('#$id').tab(); 
			$('#$id').bind('show', function(e) {
				var contentID = $(e.target).attr('data-target');
				var contentURL = $(e.target).attr('href');
				
				n=contentID.replace('#',''); 
				//alert('LoadAjax ->  '+n+' '+ contentURL);
				{$id}HideAll();
				document.getElementById(n).style.display ='block';
				LoadAjax(n,contentURL);
				$('#$id').tab();
			});
			$('#$id a:first').tab('show'); 

		}
		
		
		
		
		$(document).ready(function(){
			setTimeout('LoadTab$md5()',800);
		});
		
		function {$id}HideAll(){
			".@implode("\n", $jsHide)."
		
		}
		
		</script>";
		return @implode("\n", $f);
	
	}
	
	
public function SearchFormGen($qtype=null,$field="SearchQuery",$additional=null,$EXPLAIN=null){
		$t=time();
		$page=CurrentPageName();
		$tpl=new templates();
		$EXPLAINTXT=null;
		$EXPLAINTXTP=array();
		$buttons=null;
		$search=$tpl->_ENGINE_parse_body("{search}");
		$Loading=$tpl->javascript_parse_text("{loading}");
		$labels=array();
		
		if(is_array($EXPLAIN)){
			
			if(isset($EXPLAIN["EXPLAIN"])){
				$EXPLAINTXTP[]="<div class=explain>";
				$EXPLAINTXTP[]="\t{$EXPLAIN["EXPLAIN"]}";
				$EXPLAINTXTP[]="</div>";
			}
			
			if(isset($EXPLAIN["BUTTONS"])){
				if(count($EXPLAIN["BUTTONS"])>0){
					$EXPLAINTXTP[]=$tpl->_ENGINE_parse_body("<div style='width:100%'>".@implode(" ", $EXPLAIN["BUTTONS"])."</div>");
				
				}
			}
			
			if(isset($EXPLAIN["LINKS"])){
				if(count($EXPLAIN["LINKS"])>0){
					while (list ($index, $array) = each ($EXPLAIN["LINKS"]) ){
						$LABEL=$tpl->_ENGINE_parse_body($array["LABEL"]);
						$js=$array["JS"];
						$labels[]="<a href=\"javascript:blur();\" OnClick=\"javascript:$js\">$LABEL</a>";
					}
				}
			}
			
		}
		
		
		/*
		 *     <div class="dropdown">
    <a class="dropdown-toggle" data-toggle="dropdown" href="#">Dropdown trigger</a>
    <ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">
    ...
    </ul>
    </div>---
		 */
		
	$labeltxt=null;
	if(count($EXPLAINTXTP)>0){$EXPLAINTXT="<td>".@implode("\n", $EXPLAINTXTP)."</td>";}
	if(count($labels)>0){
		$labeltxt="<div style='margin-top:8px;margin-right:80px'>".@implode("&nbsp;|&nbsp;", $labels)."</div>";
	}
	
	return "
	<table style='width:100%;max-width:100%' class='TableRemove'>
	<tr>
	$EXPLAINTXT
	<td>
    <div class='form-search' style='margin:10px;text-align:right'>
    	<input type='hidden' id='SearchFunction$t' value='SearchQuery$t' class='SearchFunction'>
    	<input type='hidden' id='qtype$t' value='$qtype'>
    	<input type='hidden' id='Params$t' value=''>
   	 	 <input type='text' id='s-$t' class='input-medium search-query' OnKeyPress=\"javascript:SearchQueryQ$t(event)\">
   		 <button type='button' id='bt-$t' class='btn' data-loading-text=\"$Loading...\">$search</button>$labeltxt
    </div>
   	</td>
   	</tr>
   	</table>
	<div id='$t-search' style='margin-top:10px'></div>

	<script>
		function SearchQueryQ$t(e){
			if(!checkEnter(e)){return;}
			SearchQuery$t();
		}
		
		function SearchQuery$t(){
			var pp=encodeURIComponent(document.getElementById('s-$t').value);
			var pa=encodeURIComponent(document.getElementById('qtype$t').value);
			var pr=encodeURIComponent(document.getElementById('Params$t').value);
			var btn$t = $('#bt-$t');
			btn$t.button('loading');
			setTimeout(function () { btn$t.button('reset') }, 2000);
			LoadAjax('$t-search','$page?$field='+pp+'$additional&qtype='+pa+'&divid=$t&Params='+pr)		
		}
		
		function buttonHandler$t(){
			if(document.getElementById('ui-tooltip-1-content')){
				document.getElementById('ui-tooltip-1-content').innerHTML='';
			}
		
			$('#bt-$t').click(function(){ SearchQuery$t(); });		
		
		}
		
	var xSetSearchParams$t= function (obj) {
		var res=obj.responseText;
		if(res.length>3){
			document.getElementById('Params$t').value=res;
		}
		SearchQuery$t()
	}		
		
	function SetSearchParams$t(key,value){
		var XHR = new XHRConnection();
		var pr=encodeURIComponent(document.getElementById('Params$t').value);
		XHR.appendData('SourceParams', pr);
		XHR.appendData('KeyParams', key);
		XHR.appendData('value', value);
		XHR.sendAndLoad('miniadm.index.php', 'POST',xSetSearchParams$t);  
	}
		
		
	buttonHandler$t();
	SearchQuery$t();
		
	</script>";		
		
	}
	
	public function Compile(){
		$DIVERRORSTEXT=array();
		$FIELDS_EMAIL["email"]=true;
		$JSFUNCTIONS=array();
		$jsEnableAllCHK=null;
		$jsDisableAllInsteadCHK=null;
		$fieldsid=array();
		$final0=null;
		$btJSLOCK=0;
		$final0=null;
		$formtitle=null;
		$t=rand(50, time());
		$page=CurrentPageName();
		$tpl=new templates();
		$please_fill=$tpl->javascript_parse_text("{please_fill}");
		$error_on_this_field=$tpl->javascript_parse_text("{error_on_this_field}");
		$IsAnIpv4Error=$tpl->javascript_parse_text("{IsAnIpv4Error}");
		$functionName="Submit$t";
		if($this->ExecuteByClassName<>null){
			$final0="ExecuteByClassName('$this->ExecuteByClassName');";
		}
		
		if($this->FlexID<>null){
			$final0=$final0."\n$('#$this->FlexID').flexReload();";
		}
		
		if($this->form_error<>null){
			$f[]="<p class=text-error style='font-size:18px !important'>$this->form_error</p>";
		}
		
		$this->js=array();
		if($this->lockedForm){
			$f[]=$tpl->_ENGINE_parse_body("<p class=text-warning>{this_form_is_locked}</p>");
		}
		
		
		
		$f[]="
		<center id='$this->id_animate'></center>		
		<center id='animate$t'></center>		
		<div class='form-horizontal' id='formname-$t'>";
		$c=0;
		$jsLog=array();
		$bootFormName="Form".md5(serialize($this->fields_array));
		while (list ($fieldname, $array) = each ($this->fields_array) ){
			$disabled=null;
			$c++;
			$button=null;
			$params=array();
			$FieldButton=null;
			$SPECIALSCHARS=false;
			$VALUE=null;
			$MANDATORY=false;
			$ClassPrepend=null;
			$CAPTION=null;
			$CAPTIONjs=null;
			$SCHEDULE=false;
			$classField=null;
			$MAXSIZE=false;
			$FIELD_HEIGHT=0;
			$FIELDN=null;
			$TYPE=$array["TYPE"];
			$jsipv4Validator=false;
			$DISABLEALL=false;
			if(!isset($array["PARAMS"])){$array["PARAMS"]=array();}
			if(isset($array["CAPTION"])){
				$CAPTION=$tpl->_ENGINE_parse_body($array["CAPTION"]);
				$CAPTIONjs=$tpl->javascript_parse_text($array["CAPTION"]);
			}
			$char_alert_error=$tpl->javascript_parse_text("{char_alert_error}");
			if(isset($array["VALUE"])){ $VALUE=$array["VALUE"];; }
			if(isset($array["FIELDN"])){ $FIELDN=$array["FIELDN"]; }
			if(isset($array["PARAMS"])){ $params=$array["PARAMS"]; }
			
			
			$checked=null;
			$KEYPRESS=null;
			$xzf=array();
			
			if(is_array($params)){
				if(count($params)>0){
					while (list ($index, $fieldIS) = each ($params) ){$xzf[]=$index;}
					reset($params);
					if($FIELDN<>null){$jsLog[]="// $fieldname: $FIELDN: ".@implode(",", $xzf);}
				}
			}
			
			if(isset($params["MAXSIZE"])){$MAXSIZE=true;}
			if(isset($params["HEIGHT"])){$FIELD_HEIGHT=$params["HEIGHT"];}
			if(isset($params["MANDATORY"])){$MANDATORY=true;$jsLog[]="// $fieldname: $FIELDN -> IS MANDATORY LINE:".__LINE__;}
			if(isset($params["SCHEDULE"])){$SCHEDULE=true;}
			if(isset($params["DISABLED"])){$disabled="disabled";}
			if(isset($params["SPECIALSCHARS"])){$SPECIALSCHARS=true;}
			if(isset($params["DISABLEALL"])){$DISABLEALL=true;}
			if(isset($params["IPV4"])){$jsipv4Validator=true;}
			if(isset($params["EXPLAIN"])){$params["TOOLIP"]=$params["EXPLAIN"];}
			if(isset($params["KEYPRESS"])){$KEYPRESS=$params["KEYPRESS"];}
			if(isset($params["TOOLIP"])){if(!isset($params["TOOLTIP"])){$params["TOOLTIP"]=$params["TOOLIP"];}}
			$tooltip=null;
			
			if(isset($params["COOKIE"])){
				$this->js[]="\tSet_Cookie('{$params["COOKIE"]}', document.getElementById('$FIELDN').value, '3600', '/', '', '');";
			}
					
		
			if($this->lockedForm){$disabled="disabled";}
			
			if($TYPE=="HIDDEN"){$this->js[]="\tXHR.appendData('$fieldname','$VALUE');";continue;}
			if($TYPE=="TITLE"){$f[]="<H3>$VALUE</H3>";continue;}
			if($TYPE=="EXPLAIN_SPACER"){$f[]="<div class=explain>$VALUE</div>";continue;}
			if($TYPE=="SUBTITLE"){$f[]="<H4>$VALUE</H4>";continue;}
			
			
			
			$fieldsid[]=$FIELDN;
			
			if(isset($params["BUTTON"])){
				$params["BUTTON"]["JS"]=str_replace("%f", $FIELDN, $params["BUTTON"]["JS"]);
				$params["BUTTON"]["JS"]=str_replace("%s", $FIELDN, $params["BUTTON"]["JS"]);
				//$ClassPrepend="input-append";
				//$classField='span2';
				$button="<button class='btn' type=\"button\" OnClick=\"{$params["BUTTON"]["JS"]}\">{$params["BUTTON"]["LABEL"]}</button>";
			}
			
			if(isset($params["CDIR"])){
				$calculate=$this->tpl->_ENGINE_parse_body("{calculate}");
				$JSZ=explode(",",$params["CDIR"]);
				$functCdir=rand(0, time());
				$CDIRJS[]="var xCdir{$functCdir} = function (obj) {";
				$CDIRJS[]="\tvar results=obj.responseText;";
				$CDIRJS[]="\tif(results.length==0){return;}";
				$CDIRJS[]="\tdocument.getElementById('$FIELDN').value=results;";
				$CDIRJS[]="}";
				$CDIRJS[]="function Cdir{$functCdir}(){";
				$CDIRJS[]="\tvar XHR = new XHRConnection();";
				$CDIRJS[]="\tXHR.appendData('CalcCdir','yes');";
				while (list ($index, $fieldIS) = each ($JSZ) ){
					$CDIRJS[]="\tXHR.appendData('$fieldIS',document.getElementById('$fieldIS-$this->t').value);";
				}
				$CDIRJS[]="\tXHR.sendAndLoad('miniadm.network.php', 'GET',xCdir{$functCdir});";
				$CDIRJS[]="}";
				$FieldButton="<button class='btn' type=\"button\" OnClick=\"Cdir{$functCdir}()\">$calculate</button>";
				unset($JSZ);
			}
			
			if(isset($params["TOOLTIP"])){
				$tooltip=$tpl->_ENGINE_parse_body($params["TOOLTIP"]);
				$tooltip=str_replace("\n", "<br>", $tooltip);
				$tooltip=str_replace("\"", "'", $tooltip);
				$CAPTION="<a href=\"#\" id='$FIELDN-tips' OnMouseOver=\"javascript:$(this).tooltip('show');\" OnMouseOut=\"javascript:$(this).tooltip('hide');\"
				data-toggle=\"tooltip\" data-html='true' data-title=\"<div style=font-size:12px>$tooltip</div>\">$CAPTION</a>";
				//$this->ToolTips[]=""
			}
			
			$label="<label class='control-label' for='$FIELDN'>$CAPTION:</label>";
			
			if($TYPE=="CHECKNUM"){
				$label=null;			
				$iconCK=null;
				$jsp_a=null;
				$jsp_b=null;
				$functionjs=null;
				$functionjsI=null;
				if($VALUE==1){$checked="checked";$iconCK="<i class='icon-ok'></i>";}
				
				if(isset($params["LINK"])){
					$functionjs="{$functionName}CheckF". time();
					$functionjsI="$functionjs()";
					$this->js[]="// LINKED -> {$params["LINK"]} -> $functionjs";
					$JSZ=explode(",",$params["LINK"]);
					
					while (list ($index, $fieldIS) = each ($JSZ) ){
						$tre[]="\tif(document.getElementById('$FIELDN').checked){document.getElementById('{$this->fields_ids[$fieldIS]}').disabled=false;}else{document.getElementById('{$this->fields_ids[$fieldIS]}').disabled=true;}";
					}
					
					$JSFUNCTIONS[$functionjs]="function $functionjs(){\n".@implode("\n", $tre)."\n\t}";
				}
				
				$field="
			 	<label class='checkbox'>
					<input type=\"checkbox\" id='$FIELDN' 
					name=\"$FIELDN\" value=\"1\" OnClick=\"javascript:{$functionName}Iconz();$functionjsI\" $checked>&nbsp;$CAPTION <span id='$FIELDN-icon'>$iconCK</span>
				</label>		
				";
				
				if(isset($params["ONDISABLE"])){
					$DIVERRORSTEXT["$FIELDN-error"]=$tpl->_ENGINE_parse_body($params["ONDISABLE"]);
					$jsp_a="\n\t\tdocument.getElementById('$FIELDN-error').style.display='none';\n";
					$jsp_b="\n\t\tdocument.getElementById('$FIELDN-error').style.display='block';\n";
				}
				
				
				$jsEnableAllCHK="\n\t\tEnableAll$t();\n";
				$jsDisableAllInsteadCHK="\n\t\tDisableAllInstead$t('$FIELDN');\n";
				
				
			  	$this->js[]="\tif(document.getElementById('$FIELDN').checked){XHR.appendData('$fieldname',1);}else{XHR.appendData('$fieldname',0);}";
			  	$ICONZ[]="\tif(document.getElementById('$FIELDN').checked){{$jsp_a}\n\t\tdocument.getElementById('$FIELDN-icon').innerHTML='<i class=\"icon-ok\"></i>';\n\t}else{\n\t\t {$jsp_b}document.getElementById('$FIELDN-icon').innerHTML='';\n}";
			  	
			  	if($DISABLEALL){
			  		if(!$this->lockedForm){
			  			$FIELDNMD5=md5($FIELDN);
				  		$ICONZ[]="\t\tvar tmp$FIELDNMD5=0;";
				  		$ICONZ[]="\t\tif(document.getElementById('$FIELDN').checked){ tmp$FIELDNMD5=1;}";
				  		$ICONZ[]="\t\tif(tmp$FIELDNMD5==1){EnableAll$t();}";
				  		$ICONZ[]="\t\tif(tmp$FIELDNMD5==0){DisableAllInstead$t('$FIELDN');}";
			  		}
			  	}
			}
			
			if($TYPE=="CHECKYN"){
				$label=null;
				$iconCK=null;
				$jsp_a=null;
				$jsp_b=null;
				$functionjs=null;
				$functionjsI=null;
				if(strtoupper($VALUE)=="Y"){$checked="checked";$iconCK="<i class='icon-ok'></i>";}				
				$field="
				<label class='checkbox'>
				<input type=\"checkbox\" id='$FIELDN'
				name=\"$FIELDN\" value=\"Y\" OnClick=\"javascript:{$functionName}Iconz();$functionjsI\" $checked>&nbsp;$CAPTION <span id='$FIELDN-icon'>$iconCK</span>
				</label>
				";			
				
				if(isset($params["ONDISABLE"])){
					$DIVERRORSTEXT["$FIELDN-error"]=$tpl->_ENGINE_parse_body($params["ONDISABLE"]);
					$jsp_a="\n\t\tdocument.getElementById('$FIELDN-error').style.display='none';";
					$jsp_b="\n\t\tdocument.getElementById('$FIELDN-error').style.display='block';";
				}				
				
				$ICONZ[]="\tif(document.getElementById('$FIELDN').checked){\n\t\t{$jsp_a}document.getElementById('$FIELDN-icon').innerHTML='<i class=\"icon-ok\"></i>';\n\t}else{\n\t\t{$jsp_b}document.getElementById('$FIELDN-icon').innerHTML='';\n\t}";
				$this->js[]="\tif(document.getElementById('$FIELDN').checked){XHR.appendData('$fieldname','Y');}else{XHR.appendData('$fieldname','N');}";
				
			}
			
			if($TYPE=="CHECKYesNo"){
				$label=null;
				$iconCK=null;
				$jsp_a=null;
				$jsp_b=null;
				$functionjs=null;
				$functionjsI=null;
				if(strtolower($VALUE)=="yes"){$checked="checked";$iconCK="<i class='icon-ok'></i>";}
				$field="
				<label class='checkbox'>
				<input type=\"checkbox\" id='$FIELDN'
				name=\"$FIELDN\" value=\"yes\" OnClick=\"javascript:{$functionName}Iconz();$functionjsI\" $checked>&nbsp;$CAPTION <span id='$FIELDN-icon'>$iconCK</span>
				</label>
				";
			
				if(isset($params["ONDISABLE"])){
				$DIVERRORSTEXT["$FIELDN-error"]=$tpl->_ENGINE_parse_body($params["ONDISABLE"]);
				$jsp_a="\n\t\tdocument.getElementById('$FIELDN-error').style.display='none';";
				$jsp_b="\n\t\tdocument.getElementById('$FIELDN-error').style.display='block';";
				}
			
				$ICONZ[]="\tif(document.getElementById('$FIELDN').checked){\n\t\t{$jsp_a}document.getElementById('$FIELDN-icon').innerHTML='<i class=\"icon-ok\"></i>';\n\t}else{\n\t\t{$jsp_b}document.getElementById('$FIELDN-icon').innerHTML='';\n\t}";
				$this->js[]="\tif(document.getElementById('$FIELDN').checked){XHR.appendData('$fieldname','yes');}else{XHR.appendData('$fieldname','no');}";
			
			}			
			
			
			
			$this->js[]="\tdocument.getElementById('Control-$t-$c').className='control-group';";
			$this->js[]="\tdocument.getElementById('help-$t-$c').innerHTML='';";
			
			if($MANDATORY){
				$jsLog[]="// $fieldname: $FIELDN -> MANDATORY function Mandatory$bootFormName";
				$this->js[]="\tif(!Mandatory$bootFormName('$FIELDN','Control-$t-$c','$CAPTIONjs','help-$t-$c')){return;}";
			}
			

			
			if($SPECIALSCHARS){
				if($this->DisableSpecialCharacters==0){
					$this->js[]="\tvar field$c=document.getElementById('$FIELDN').value;";
					$this->js[]="\tif( DetectSpecialChars(field$c,'$char_alert_error') ){\n\t\treturn;\n\t}";
				}
			}
			
			if($TYPE<>"CHECKYN"){
				if($TYPE<>"CHECKNUM"){
					if($TYPE<>"PASSWORD"){
						if(isset($params["ENCODE"])){
							$this->js[]="\tXHR.appendData('$fieldname',encodeURIComponent(document.getElementById('$FIELDN').value));";
						}else{
							$this->js[]="\tXHR.appendData('$fieldname',document.getElementById('$FIELDN').value);";
						}
					}
				}
			}
			
			if($TYPE=="PASSWORD" OR $TYPE=="TEXT"){
				if($KEYPRESS<>null){
					$onKeyPress=" OnKeyPress=\"javascript:$KEYPRESS(event,'$FIELDN');SendForm$t(event)\" ";
				}else{
					$onKeyPress=" OnKeyPress=\"javascript:SendForm$t(event)\" ";
				}
			}
			
			
			if($jsipv4Validator){
				$this->js[]="\tvar field2$c=document.getElementById('$FIELDN').value;";
				$this->js[]="\tif(field2$c.length>1){";
				$this->js[]="\tif(field2$c=='___.___.___.___'){
					field2$c='0.0.0.0';
					document.getElementById('$FIELDN').value=field2$c;
				}";
				$this->js[]="\tif(!IsAnIpv4(field2$c)){";
				$this->js[]="\t\t\talert('$IsAnIpv4Error:'+field2$c);";
				$this->js[]="\t\t\treturn;";
				$this->js[]="\t\t}";
				$this->js[]="\t}";
				//$this->ToolTips[]="\t$(function(){ $('#{$FIELDN}').ipAddress(); });";
			}
			
			if($TYPE=="TEXT"){
				if(isset($params["TOOLTIP"])){$this->ToolTips[]=$this->BuildFieldToolTip($FIELDN,$params["TOOLTIP"]);}
				$field="<input type='text' id='$FIELDN' value='$VALUE' class='$classField' $onKeyPress$disabled>";
				if(isset($params["BROWSE"])){
					$browse=$tpl->_ENGINE_parse_body("{browse}");
					$field=$field."<button class='btn' type=\"button\" OnClick=\"Loadjs('SambaBrowse.php?no-shares=yes&field=$FIELDN')\">$browse...</button>";
				}	
				if($SCHEDULE){
					$bttxt=$tpl->_ENGINE_parse_body("{schedule}");
					$field=$field."<button class='btn' type=\"button\" OnClick=\"Loadjs('cron.php?field=$FIELDN')\">$bttxt...</button>";
				}				
				 
				
				if($FieldButton<>null){$field=$field.$FieldButton;}			
			}
			
			if($TYPE=="PASSWORD"){
				if(isset($params["TOOLTIP"])){$this->ToolTips[]=$this->BuildFieldToolTip($FIELDN,$params["TOOLTIP"]);}
				$field="<input type='password' id='$FIELDN' value='$VALUE' class='$classField' $onKeyPress$disabled>";
				$this->js[]="\tXHR.appendData('$fieldname',encodeURIComponent(document.getElementById('$FIELDN').value));";
			}	
			
			if($TYPE=="TEXTAREA"){
				if(isset($params["TOOLTIP"])){$this->ToolTips[]=$this->BuildFieldToolTip($FIELDN,$params["TOOLTIP"]);}
				$minheight=60;
				if($FIELD_HEIGHT>0){$minheight=$FIELD_HEIGHT;}
				$field="<textarea id='$FIELDN' class='$classField' $onKeyPress$disabled
				style=\"font-size:16px !important;width:99% !important;min-height:{$minheight}px !important\">". utf8_encode($VALUE)."</textarea>";
			}			

			
			
			if($TYPE=="LIST"){
				if(isset($params["TOOLTIP"])){$this->ToolTips[]=$this->BuildFieldToolTip($FIELDN,$params["TOOLTIP"]);}
				$VALUES=$array["VALUES"];
				$field=Field_array_Hash($VALUES, $FIELDN,$VALUE,null,"style:font-size:16px !important");
			}
			$f[]="<div class='control-group' id='Control-$t-$c'>";
			$f[]="	$label";
			$f[]="	<div class='controls'>";	
			$f[]="		<div class='$ClassPrepend'>";
			$f[]="			$field";
			$f[]="			$button";
			$f[]="			<span class='help-inline' id='help-$t-$c'></span>";
			$f[]="		</div>";
			$f[]="	</div>";
			$f[]="</div>";
	
	
	//disabled:button:{browse}...:javascript:Browse$t()value:myvalue
	
	
			
			
		}
		$this->buttonname=$tpl->_ENGINE_parse_body("$this->buttonname");
		if($this->CloseYahoo<>null){$CloseYahoo="{$this->CloseYahoo}Hide()";}else{$CloseYahoo=null;}
		
		
		while (list ($index, $ids) = each ($fieldsid) ){
			$jsdisableAll[]="\tdocument.getElementById('$ids').disabled=true;";
			$jsEnableAll[]="\tdocument.getElementById('$ids').disabled=false;";
			if(!isset($DIVERRORSTEXT["$ids-error"])){$DIVERRORSTEXT["$ids-error"]=null;}
			$DIVERRORS[]="<div id='$ids-error' style='display:none' class=explainWarn>\n\t{$DIVERRORSTEXT["$ids-error"]}\n</div>";
			
		}
		while (list ($functionanme, $code) = each ($JSFUNCTIONS) ){
			$fjs_a[]="$functionanme();";
			$fjs_b[]="$code";
		}
		
		$PROTO="POST";
		if($this->PROTO<>null){$PROTO=$this->PROTO;}
		if($this->ajax_page<>null){$page=$this->ajax_page;}
		$DIVERRORSF=@implode("\t\n", $DIVERRORS);
		$infoLock="";
		
		$btlock="btn-primary";
		$btjs="javascript:$functionName();";
		if($this->lockedForm){
			$btJSLOCK=1;
			$btlock=null;$btjs="blur();";$infoLock=" locked form (lockedForm =TRUE)";}
		
		$f[]="<div style='text-align:right'><hr>";
		$Loading=$tpl->javascript_parse_text("{loading}");
		if(count($this->buttonsAdd)>0){
			while (list ($xbuttonname, $xbutjs) = each ($this->buttonsAdd) ){
				$xbuttonname=$this->tpl->_ENGINE_parse_body($xbuttonname);
				$xtbtJSStand="$functionName()";
				$xbtlock="btn-primary";
				if($this->lockedForm){$xbtlock=null;$xbutjs="blur();";$xtbtJSStand=null;}
				$f[]="<button class=\"btn-large {$xbtlock}\" style='font-size:16px' type=\"button\"  
				OnClick=\"$xtbtJSStand;$xbutjs\" data-loading-text=\"$Loading...\">$xbuttonname</button>";
			}
		}
		
$CDIRJSFin=null;		
if(isset($CDIRJS)){	if(count($CDIRJS)>0){$CDIRJSFin=@implode("\n", $CDIRJS);}}		
$AlertReturn="return;";	

if($this->ExecuteByClassNameForced){
	$AlertReturn=null;
}

$f[]="
<!-- $infoLock -->		
<button class=\"btn-large {$btlock}\" id='MAINBUT-$t' style='font-size:16px' type=\"button\"  data-loading-text=\"$Loading...\">$this->buttonname&nbsp;&raquo;</button>";
$f[]="</div>		
</div>
<script>		
$CDIRJSFin
var mandatory{$c}_fieldid='';
var mandatory{$c}_controlid='';

var x$functionName= function (obj) {
\tvar btn$t = $('#MAINBUT-$t');
\tbtn$t.button('reset');
\tvar results=obj.responseText;
\tif(document.getElementById('animate$t')){
\t\tdocument.getElementById('animate$t').innerHTML='';
\t}
\tif(results.length>3){alert(results);$AlertReturn}
\t$CloseYahoo;
\t$final0
".@implode("\n", $this->AjaxFinal)."
\t$this->Callback;
	}		
function RefreshNavs$this->id_animate(){
	$('a[data-target]').each(function(){
		var e=$(this);
		var c=e.parent();
		var zclass=c.attr('class');
		if(zclass){
			zclass=zclass.replace('FirstLetter','');
			zclass=zclass.replace(' ','');
		}
		
		var contentID = e.attr('data-target');
		if(contentID){var contentID=contentID.replace('#','');} 
		var contentURL = e.attr('href');
		if(zclass=='active'){
			LoadAjax(contentID,contentURL);
		}
	});
		
}
function EnableAll$t(){
	$('input,select,hidden,textarea', '#formname-$t').each(function() {
		 	var \$t = $(this);
		 	var id=\$t.attr('id');
		 	var value=\$t.attr('value');
		 	var type=\$t.attr('type');
		 	document.getElementById(id).disabled=false;
		 	
		});	
}
	
function DisableAllInstead$t(zid){
	
	$('input,select,hidden,textarea', '#formname-$t').each(function() {
		 	var \$t = $(this);
		 	var id=\$t.attr('id');
			var value=\$t.attr('value');
		 	var type=\$t.attr('type');
		 	document.getElementById(id).disabled=true;
		 	}
		);	
		
	document.getElementById(zid).disabled=false;	
			
	}

function SendForm$t(e){
	if(!checkEnter(e)){return;}
	$functionName();
}
		
function $functionName(){
\tvar fmlock=$btJSLOCK;
\tif(fmlock==1){return;}
\tvar btn$t = $('#MAINBUT-$t');

\tvar XHR = new XHRConnection();
// JS ARRAY() -> ". count($this->js)."\n".@implode("\n", $jsLog)."
		
". @implode("\n", $this->js)."
\tbtn$t.button('loading');		
\tAnimateDiv('animate$t');
\tXHR.sendAndLoad('$page', '$PROTO',x$functionName);  
	}
			
function {$functionName}Disable(){
".@implode("\n", $jsdisableAll)."			
	}	
			
function {$functionName}Enable(){
".@implode("\n", $jsEnableAll)."			
	}
function {$functionName}Iconz(){
".@implode("\n", $ICONZ)."
	}
function {$functionName}Tootipz(){
".@implode("\n", $this->ToolTips)."
}
function {$functionName}MainButt(){
	$('#MAINBUT-$t').click(function(){
		$functionName();
	 });	

}



function Mandatory$bootFormName(fieldid,controlid,captionjs,helpid){
	mandatory{$c}_fieldid=fieldid;
	mandatory{$c}_controlid=controlid;
	var field$c=document.getElementById(mandatory{$c}_fieldid).value;
	if(field$c.length==0){
		document.getElementById(mandatory{$c}_controlid).className='control-group error';
		document.getElementById(helpid).innerHTML='$error_on_this_field';
		alert('$please_fill :`'+captionjs+'`');
		document.getElementById(mandatory{$c}_controlid).classList.remove('error');
		
		return false;
	}
	document.getElementById(helpid).innerHTML='';		
	return true;
}

".@implode("\n", $fjs_b)."\n".@implode("\n", $fjs_a)."\n
{$functionName}Tootipz();
{$functionName}Iconz();
{$functionName}MainButt();


\n".@implode("\n", $this->ScriptsToAdd)."\n
</script>
		";
$desc_form="<hr>";
		if($this->title_form<>null){
			$this->title_form=$tpl->_ENGINE_parse_body($this->title_form);
			$formtitle="<H2>$this->title_form</H2>";
		}
		
		if($this->desc_form<>null){
			$desc_form="<div class=explain style='margin-top:-1px;font-weight:bold;font-style: italic;font-size:13px;line-height:15px'>$this->desc_form</div>";
		}
		
		return $formtitle.$desc_form.$DIVERRORSF.@implode("\n", $f);
	}
	
	private function BuildFieldToolTip($fieldid,$text){
		$tpl=new templates();
		$text=$tpl->_ENGINE_parse_body($text);
		$text=str_replace("\n" ,"<br>", $text);
		$text=str_replace("\"" ,"'", $text);
		$text=str_replace("'" ,"&#130;", $text);
		$text=str_replace("`" ,"&#130;", $text);
		$text=str_replace(array("\"", "'",
				"\\","/"
				
				), array("&quot;", '&#039;','&#91;',"&#47;"
						
						
						), $text);
		
			return "$(\"#$fieldid\").on({
				\"keyup focus\": function() {
						$(this).tooltip(\"show\");
		
				},
				blur: function() {
					$(this).tooltip(\"hide\");
				}
			}).tooltip({
				placement: \"right\",
				trigger: \"manual\",
				html:true,
				title:'<div style=font-size:12px>$text</div>',
				
			});\n";
	}

}



function LineToClass($line){
	$class=null;
	if(preg_match("#FATAL#is", $line)){return "warning";}
	if(preg_match("#failed#is", $line)){return "warning";}
	if(preg_match("#abnormally#is", $line)){return "error";}
	if(preg_match("#Cannot allocate memory#is", $line)){return "error";}
	if(preg_match("#Reconfiguring#is", $line)){return "info";}
	if(preg_match("#Accepting HTTP#is", $line)){return "info";}
	if(preg_match("#Ready to serve requests#is", $line)){return "info";}
	if(preg_match("#aborting task#is", $line)){return "warning";}
	if(preg_match("#Too much instances#is", $line)){return "warning";}
	if(preg_match("#unable to#i", $line)){return "warning";}
	if(preg_match("#New Artica Database#is", $line)){return "info";}
	if(preg_match("#is marked as crashed#is", $line)){return "error";}
	if(preg_match("#error reading communication#is", $line)){return "warning";}
	if(preg_match("#Overloaded system#is", $line)){return "error";}
	if(preg_match("#Can't connect#is", $line)){return "warning";}
	if(preg_match("#ready for connection#is", $line)){return "info";}
	if(preg_match("#Disk is full#is", $line)){return "error";}
	if(preg_match("#(ERROR|WARN|FATAL|UNABLE|Failed|not found|denied|VIRUS DETECTED)#is", $line)){return "error";}
	return null;
}
function time_to_date($xtime,$time=false){
	$tpl=new templates();
	$dateT=date("{l} {F} d",$xtime);
	if($time){$dateT=date("{l} {F} d H:i:s",$xtime);}
	if($tpl->language=="fr"){$dateT=date("{l} d {F} ",$xtime);if($time){$dateT=date("{l} d {F} H:i:s",$xtime);}}
	return $tpl->_ENGINE_parse_body($dateT);	
	
}

function Privileges_members_admins(){
		if(isset($_SESSION["Privileges_members_admins"])){return $_SESSION["Privileges_members_admins"];}
		$users=new usersMenus();
		if($users->AsHotSpotManager){
			if($GLOBALS["VERBOSE"]){echo "<strong>Privileges_members_admins AsHotSpotManager =TRUE</strong><br>\n";}
			$_SESSION["Privileges_members_admins"]=true;return true;}
		if($users->AsSambaAdministrator){if($GLOBALS["VERBOSE"]){echo "<strong>Privileges_members_admins AsSambaAdministrator =TRUE</strong><br>\n";}$_SESSION["Privileges_members_admins"]=true;return true;}
		if($users->AsSystemAdministrator){if($GLOBALS["VERBOSE"]){echo "<strong>Privileges_members_admins AsSystemAdministrator =TRUE</strong><br>\n";}$_SESSION["Privileges_members_admins"]=true;return true;}
		if($users->AsSquidAdministrator){if($GLOBALS["VERBOSE"]){echo "<strong>Privileges_members_admins AsSquidAdministrator =TRUE</strong><br>\n";}$_SESSION["Privileges_members_admins"]=true;return true;}
		if($users->AsPostfixAdministrator){if($GLOBALS["VERBOSE"]){echo "<strong>Privileges_members_admins AsPostfixAdministrator =TRUE</strong><br>\n";}$_SESSION["Privileges_members_admins"]=true;return true;}
		
		if($GLOBALS["VERBOSE"]){echo "<strong>Privileges_members_admins FALSE</strong><br>\n";}
		$_SESSION["Privileges_members_admins"]=false;
		return false;
	
}

function isNetSessions(){
	$users=new usersMenus();
	if($users->AsSystemAdministrator){return true;}
	if($users->ASDCHPAdmin){return true;}
	if($users->AsOrgDNSAdmin){return true;}
	if($users->AllowChangeDomains){return true;}
	if($users->AsSquidAdministrator){return true;}
	if($users->AsSambaAdministrator){return true;}
	if($users->AsPostfixAdministrator){return true;}
	if($users->AsDansGuardianAdministrator){return true;}
	return false;

}

function ONLY_CORP_HTML(){
	if(!$_SESSION["CORP"]){
		$tpl=new templates();
		$onlycorpavailable=$tpl->_ENGINE_parse_body("{onlycorpavailable}");
		$content="<p class=text-error>$content</p>";
		echo $content;
		die();
	}	
	
}

function Privileges_members_ownstats(){
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){
		if($_SESSION["WebstatisticsByMember"]==0){die("<H1>Oups!</H1>");}
		$_GET["member-value"]=$_SESSION["uid"];
		$_GET["uid"]=$_SESSION["uid"];
	}else{
		if(!isset($_GET["uid"])){
			if(isset($_GET["member-value"])){$_GET["uid"]=$_GET["member-value"];}
		}
	}
}
function call_checks(){
	if(!function_exists(base64_decode("T05MWV9DT1JQX0hUTUw="))){
		echo base64_decode("PHAgY2xhc3M9dGV4dC1lcnJvcj5TT1VSQ0UgQ09ERSBBUyBCRUVOIEhBQ0tFRCEhPC9wPg==");
		die();
	}
}

function SearchToSql($field=null,$data=null){
	if($data==null){return null;}
	if($field==null){return null;}
	
	$specials["DATE_FORMAT"]=true;
	$specials["COUNT"]=true;
	$specials["AVG"]=true;
	$specials["SUM"]=true;
	$cots=true;
	while (list ($a, $b) = each ($specials) ){
		if(preg_match("#$a#i", $field)){$cots=false;}
	}
	if($cots){$field="`$field`";}
	
	$QUERY_OP=" = ";
	if(preg_match("#not.*?:(.+)#i", $data,$re)){$neg=true;$data=trim($re[1]);}
	if($neg){$QUERY_OP=" != ";}
	$data=str_replace("*", "%", $data);
	if(strpos(" $data", "%")>0){
		$QUERY_OP=" LIKE ";
		if($neg){$QUERY_OP=" NOT LIKE ";}
	}
	return "AND ($field $QUERY_OP'$data')";
	
}


function SEND_CORP_LICENSE(){
	if(!$_SESSION["CORP"]){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("<p class=text-error>{this_feature_is_disabled_corp_license}</p>");
		die();
	}	
}

function SEND_CORP_LICENSE_JAVASCRIPT(){
	if(!$_SESSION["CORP"]){
		$tpl=new templates();
		$onlycorpavailable=$tpl->javascript_parse_text("{onlycorpavailable}");
		echo "alert('$onlycorpavailable')";
		return;
	}	
	
}
function dashboard_box($title,$number=0,$text,$footer_text=null){
	
	if(!isset($GLOBALS["TPL"])){$GLOBALS["TPL"]=new templates();}
	$title=$GLOBALS["TPL"]->_ENGINE_parse_body($title);
	$footer_text=$GLOBALS["TPL"]->_ENGINE_parse_body($footer_text);
	$text=$GLOBALS["TPL"]->_ENGINE_parse_body($text);
	
	// <div id='box-holder'></div>
	return "<div style=\"
	background: url('/img/box-bg.png') no-repeat scroll 0 0 #EDEDED;
    border: 1px solid #DBDBDB;
    display: block;
    height: 190px;
    margin-bottom: 15px;
    overflow: hidden;
    position: relative;
    width: 235px;
    box-shadow: 0 1px 5px rgba(0, 0, 0, 0.2);
    border-radius: 5px 5px 5px 5px;
    margin-left: 0;
    float: left;
    outline: 0 none;
    padding: 0;
    vertical-align: baseline;
    font-size:100% !important;
    margin-right:5px;
    \">
	<div style=\"
		background: -moz-linear-gradient(-90deg, #FFFFFF, #E1E1E1) repeat scroll 0 0 transparent;
    	border: 1px solid #F7FEF4;
    	height: 140px;
   	 	margin: 10px 10px 5px;
    	padding: 5px;
    	box-shadow: 0 1px 10px rgba(0, 0, 0, 0.2) inset; \">
    	
	<div style=\"display: block;height: 145px;overflow: hidden;position: relative;
	background: none repeat scroll 0 0 transparent;
    border: 0 none;
    font-size: 100%;
    margin: 0;
    outline: 0 none;
    padding: 0;
    vertical-align: baseline;
	\">
		
	<div style=\"color: #646167;
    font-size: 5em !important;
    font-style: normal !important;
    font-weight: bold !important;
    margin-top: 10px !important;
    text-align: center !important;
    text-decoration: none !important;
    text-shadow: 0 1px 0 #FFFFFF !important;\">
	$number
	</div>
		
	<div style=\"color: #454549;
    font-size: 1.3em !important;
    font-style: normal !important;
    font-variant: normal !important;
    font-weight: normal !important;
    letter-spacing: -1px !important;
    margin-top: 5px !important;
    text-align: center !important;
    text-decoration: none !important;
    text-shadow: 0 1px 0 #FFFFFF !important;
    text-transform: uppercase !important;\">
	$title
	</div>
		
	<div style=\"bottom: 2px;
    display: block;
    font-size:11px !important;
    left: 5px;
    overflow: hidden;
    position: absolute;
    right: 5px;
    text-align: center;\">$text</div>
		
	</div>
	</div>

	<div style=\"
    coolor: #45637A !important;
    font-size: 0.9em !important;
    font-weight: bold !important;
    height: 20px !important;
    line-height: 20px !important;
    padding-left: 10px !important;
    padding-right: 10px !important;
    text-align: left !important;
    vertical-align: middle !important;
    \">
	<span style=\"color: #B2AEB0;background: none repeat scroll 0 0 transparent;
    border: 0 none !important;
    font-size: 100% !important;
    margin: 0 !important;
    outline: 0 none !important;
    padding: 0 !important;
    vertical-align: baseline !important;
    font-weight: bold !important;
    line-height: 20px !important;
    text-align: left !important;\">$footer_text</span>
	</div>

	</div>";

}

function stats_paragraphe($number,$title,$explain,$linkjs,$backgpict){
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body($title);
	$explain=$tpl->_ENGINE_parse_body($explain);
	
	$html="
	<div style='background: -moz-linear-gradient(-90deg, #FFFFFF, #E1E1E1) repeat scroll 0 0 transparent;
	border: 1px solid #F7FEF4; 
	border-radius: 5px 5px 5px 5px;    	
	min-height: 245px;    	 	
	margin: 10px 10px 5px;     	
	padding: 10px;     	
	box-shadow: 0 1px 10px rgba(0, 0, 0, 0.2) inset;cursor:pointer;'
	
	OnMouseOver=\"javascript:this.style.background='-moz-linear-gradient(-90deg, #FFFFFF, #FFF28E)'\"
	OnMouseOut=\"javascript:this.style.background='-moz-linear-gradient(-90deg, #FFFFFF, #E1E1E1)'\"
	OnClick=\"javascript:$linkjs\"
	
	>
	<table style='border:0px;padding:5px;maring-top:10px;margin-right:10px;width:100%'>
	<tr>
		<td style='vertical-align:top' width=1% nowrap><span style='font-size:42px;font-weight:bold;color:#545454'>$number</span></td>
		<td style='vertical-align:top;padding-left:10px'>
			<table style='margin:0;padding:0;border:0;width:100%'>
			<tr>
				<td><span style='font-size:18px;border-bottom:1px solid #545454 '>$title</span>
			</tr>
			<tr>
			<td style='padding-left:10px;padding-right:10px'>
				<p style=\"background-image:url('img/$backgpict');
					background-repeat:no-repeat;
					min-height:180px;
					background-position:bottom right;
					\">$explain</p>
			</td>
			</tr>
		</table>
		</td>
	</tr>
	</table></div>";
			
return $html;
	
}





function buildCategoriesCache($force=false){
	include_once(dirname(__FILE__)."/class.dansguardian.inc");
	if(!$force){
		if(isset($_SESSION["CategoriesList"])){return $_SESSION["CategoriesList"];}
	}
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$search='%';
	$table="webfilters_categories_caches";
	$page=1;
	$ORDER="ORDER BY categorykey ASC";
	$FORCE_FILTER=null;
	if($q->COUNT_ROWS($table)==0){$ss=new dansguardian_rules();$ss->CategoriesTableCache();}
	
	$sql="SELECT * FROM personal_categories ORDER BY category";
	
	$results=$q->QUERY_SQL($sql);
	
	while ($ligne = mysql_fetch_assoc($results)) {
	
		$_SESSION["CategoriesList"]["CATEGORIES_PERSO"][$ligne["category"]]=array(
				"DESC"=>utf8_encode($ligne["category_description"]),
				"IMG"=>"<img src='img/20-categories-personnal.png'>",
		);
	}
	
	
	
	
	
	$sql="SELECT *  FROM `$table` ORDER BY categorykey ASC";
	$results = $q->QUERY_SQL($sql);
	
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		if($ligne['categorykey']=="phishtank"){continue;}
		if(isset($_SESSION["CategoriesList"]["CATEGORIES_PERSO"][$ligne['categorykey']])){continue;}
		if($ligne["picture"]==null){$ligne["picture"]="20-categories-personnal.png";}
		$TextInterne=null;
		$img="img/{$ligne["picture"]}";
		$val=0;
		
		
		$_SESSION["CategoriesList"]["CATEGORIES_ARTICA"][$ligne['categorykey']]=array(
				"DESC"=>utf8_encode($ligne['description']),
				"IMG"=>"<img src='$img'>",
		);



	}
	
	return $_SESSION["CategoriesList"];
	

}
?>