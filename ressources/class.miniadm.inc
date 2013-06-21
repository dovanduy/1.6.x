<?php

session_start();$_SESSION["MINIADM"]=true;
setcookie("MINIADM", "YES", time()+1000);
include_once(dirname(__FILE__)."/class.browser.detection.inc");
include_once(dirname(__FILE__)."/class.highcharts.inc");
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
	
	
	public function squid_load_dynamic_acls($force=false){
		
		if($_SESSION["VirtAclUser"]){
			$_SESSION["SQUID_DYNAMIC_ACLS"]=$_SESSION["SQUID_DYNAMIC_ACLS_VIRTUALS"];
			return;
		}
		if($GLOBALS["VERBOSE"]){echo "squid_load_dynamic_acls:: run()<br>\n";}
		if(!$GLOBALS["VERBOSE"]){
			if(!$force){
				if(isset($_SESSION["SQUID_DYNAMIC_ACLS"])){return;}
			}
		}
		if(!$this->IFItsProxy()){
			if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>squid_load_dynamic_acls::{$_SESSION["uid"]} Not a proxy...</span><br>\n";}
			return array();
		}
	
	
			$sql="SELECT webfilters_sqitems.pattern,webfilters_sqgroups.ID
			FROM webfilters_sqitems, webfilters_sqgroups
			WHERE webfilters_sqitems.enabled =1
			AND webfilters_sqitems.gpid = webfilters_sqgroups.ID
			AND webfilters_sqgroups.GroupType = 'dynamic_acls'
			AND webfilters_sqgroups.enabled =1";
	
			$q=new mysql_squid_builder();
			$results=$q->QUERY_SQL($sql);
			$ldap=new clladp();
			$ADenabled=$ldap->IsKerbAuth();
			if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>squid_load_dynamic_acls::IsKerbAuth() = $ADenabled</span><br>\n";}
			if($ADenabled==1){
				$ad=new external_ad_search();
				$ct=new user($_SESSION["uid"]);
				if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>squid_load_dynamic_acls::GroupsOfMember($ct->dn)</span><br>\n";}
				$groupsofusers=$ad->GroupsOfMember($ct->dn);
				if($GLOBALS["VERBOSE"]){
					echo "<H3>$ct->dn</H3>\n";
					while (list ($a, $b) = each ($groupsofusers) ){
						echo "<li>Member of group: groupsofusers[<strong>$a</strong>]</li>\n";
					}
					
				}
			}
		
			$_SESSION["SQUID_DYNAMIC_ACLS"]=array();
			if($GLOBALS["VERBOSE"]){echo "squid_load_dynamic_acls::{$_SESSION["uid"]} ".mysql_num_rows($results)." acls groups<br>\n";}
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				if($GLOBALS["VERBOSE"]){
						echo "<span style='color:blue'>squid_load_dynamic_acls::Pattern:{$ligne["pattern"]}</span><br>\n";
				}
				if(preg_match("#^AD:[0-9]+:(.+)#", $ligne["pattern"],$re)){
					$DNGroup=base64_decode($re[1]);
					if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>OK:squid_load_dynamic_acls::DNGroup:$DNGroup</span><br>\n";}
					if($ADenabled==0){
						if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>FAILED:squid_load_dynamic_acls::AD not enabled</span><br>\n";}
						continue;
					}
					if(isset($groupsofusers[$DNGroup])){
						$_SESSION["SQUID_DYNAMIC_ACLS"][$ligne["ID"]]=true;
						if($GLOBALS["VERBOSE"]){echo "<span style='color:blue;font-weight:bold;font-size:14px'><strong>ADDED:</strong> squid_load_dynamic_acls::{$groupsofusers[$DNGroup]}:TRUE</span><br>\n";}
					}else{
						if($GLOBALS["VERBOSE"]){echo "<span style='color:red;font-weight:bold;font-size:14px'><strong>groupsofusers[$DNGroup]:</strong> No match</span><br>\n";}
					}
				}else{
					if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>squid_load_dynamic_acls::No match...</span><br>\n";}
				}
		
		
			}
			

		
			
	}

	
	function NavBar(){
		$users=new usersMenus();
		$ct=new user($_SESSION["uid"]);
		if($ct->DisplayName==null){$ct->DisplayName=$_SESSION["uid"];}
		if($users->POSTFIX_INSTALLED){
			$messaging="<li><a href=\"miniadm.messaging.php\">{messaging}</a></li>";
		}
		
			$html="
			<button type=\"button\" class=\"btn btn-navbar\" data-toggle=\"collapse\" data-target=\".nav-collapse\">
            <span class=\"icon-bar\"></span>
            <span class=\"icon-bar\"></span>
            <span class=\"icon-bar\"></span>
          </button>
          
          <div class=\"nav-collapse collapse\">
            <p class=\"navbar-text pull-right\">
           &nbsp;&nbsp;&nbsp;<i class='icon-user icon-white'></i> {logged_in_as} <a href=\"miniadm.profile.php\" class=\"navbar-link\">$ct->DisplayName</a>
            </p>
             <p class=\"navbar-text pull-right\">
             	<a href=\"miniadm.logoff.php\" class=\"navbar-link\"><i class='icon-off icon-white'></i> {logoff}</a>
            </p>           
            
            
            <ul class=\"nav\">
              <li class=\"active\"><a href=\"miniadm.index.php\"><i class='icon-home icon-white'></i> {home}</a></li>
              $messaging
              <li><a href=\"miniadm.about.php\">{about2}</a></li>
             
            </ul>
          </div>";	
			
			$tpl=new templates();
			return $tpl->_ENGINE_parse_body($html);
		
	}
	

	
	function leftmenu(){
		$browser=browser_detection();
		$tpl=new templates();
		if(!$GLOBALS["VERBOSE"]){
			if(isset($_SESSION[__FILE__][__FUNCTION__])){echo $_SESSION[__FILE__][__FUNCTION__];return;}
		}
		$MAIL_ARCHIVE=true;
		$AMAVIS_USERS=true;
		$AMAVIS_ALL=true;
		$users=new usersMenus();
		$sock=new sockets();
		$ASRAD=false;
		$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
		if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
		$EnableAmavisDaemon=$sock->GET_INFO("EnableAmavisDaemon");
		$ProxyUseArticaDB=$sock->GET_INFO("ProxyUseArticaDB");
		$AmavisPerUser=$sock->GET_INFO("AmavisPerUser");
		$MailArchiverEnabled=$sock->GET_INFO("MailArchiverEnabled");
		$MailArchiverUsePerl=$sock->GET_INFO("MailArchiverUsePerl");
		$WebstatisticsByMember=$sock->GET_INFO("WebstatisticsByMember");
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
		$postfix=array();
		$messt=null;
		$postfixt=null;
		$messou=array();
		$styleGrey=null;
		$subactive="subactive";
		
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
		
		
		
		
		if($users->AsOrgAdmin){
			if(!$ASRAD){$admorg["{users_and_groups}"]="miniadmin.members.php";}
		}
		
		
		if($users->FREERADIUS_INSTALLED){
			if($Privileges_members_admins){
				$adm["{users_and_groups}"]="miniadmin.system.members.php";
						
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
		
		if($users->AsMailBoxAdministrator){
			if($users->ZARAFA_INSTALLED){
				$postfix["{mailboxes}"]="miniadmin.zarafa.php";
			}
		}		
		if($users->AsPostfixAdministrator){$postfix["{domains}"]="miniadm.messaging.domains.php?buildpage=yes";}			
		if($users->AsPostfixAdministrator){if($AMAVIS_ALL){$postfix["{APP_AMAVIS}"]="miniadmin.postfix.amavis.php";}}
		if($users->AsPostfixAdministrator){$postfix["{archive_module}"]="miniadmin.postfix.archive.php";}
			
		}
		
		
		if( ($users->AsSquidAdministrator) OR ($users->AsWebMaster) ){
			if($users->NGINX_INSTALLED){
				$adm["{certificates_center}"]="miniadmin.certificates.php";
				$prxy["{reverse_proxy_settings}"]="miniadmin.proxy.reverse.php";
			}
		}
			
			
		if($users->AsSquidAdministrator){
			if($users->SQUID_INSTALLED){
				$adm["{certificates_center}"]="miniadmin.certificates.php";
				$prxy["{proxy_main_settings}"]="miniadmin.proxy.php?startup=proxy-settings";
				if($SquidActHasReverse){
					$prxy["{reverse_proxy_settings}"]="miniadmin.proxy.reverse.php";
				}

				
			}
				
				if($users->APP_FTP_PROXY){
					$EnableFTPProxy=$sock->GET_INFO('EnableFTPProxy');
					if(!is_numeric($EnableFTPProxy)){$EnableFTPProxy=0;}
					if($EnableFTPProxy==1){
						$prxy["{proxy_ftp_main_settings}"]="miniadmin.ftp.proxy.php";
					}
				}
				
				
			}
			
			if($users->AsDansGuardianAdministrator){
				$prxy["{WEB_FILTERING}"]="miniadmin.webfiltering.php";
				
			}
			
			if($users->AsDansGuardianAdministrator){
				$adm["{certificates_center}"]="miniadmin.certificates.php";
				//$prxy["{access_rules}"]="miniadmin.access.rules.php";
			}			
			
			$this->squid_load_dynamic_acls();
			if(count($_SESSION["SQUID_DYNAMIC_ACLS"])>0){
				$dynamic_acls_newbee_explain=str_replace("%s", count($_SESSION["SQUID_DYNAMIC_ACLS"]), $tpl->_ENGINE_parse_body("{dynamic_acls_newbee_explain}"));
				$prxy["{dynamic_acls_newbee}"]="miniadmin.proxy.dynamic.acls.php";
					
			}
	
			if($users->AsProxyMonitor){
				
				
				$monitorprox["{proxy_monitor}"]="miniadmin.proxy.monitor.php";
				
				
			
			}
			
			if($Privileges_members_admins){
				$adm["{users_and_groups}"]="miniadmin.system.members.php";
					
			}			
			
			
			if($_SESSION["AsWebStatisticsAdministrator"]){
				if($EnableRemoteStatisticsAppliance==0){
					$monitorprox["{web_statistics}"]="miniadm.webstats-start.php";
				}
			}
		

		if(isNetSessions() ) {
			$adm["{network_services}"]="miniadm.network.php";
			
		}
		
		if($users->AsSystemWebMaster){
			$adm["{certificates_center}"]="miniadmin.certificates.php";
			$adm["FreeWebs"]="miniadm.freewebs.php";
		}		
		
		if($users->AsSystemAdministrator){
			$adm["{certificates_center}"]="miniadmin.certificates.php";
			$adm["{events}"]="miniadm.system.events.php";
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
				$ff[]="<li class=\"active\"><a href=\"#\">{organization} {$_SESSION["ou"]}</a></li>";
				while (list ($a, $b) = each ($admorg) ){
					$title=$tpl->_ENGINE_parse_body($a);
					$ff[]="<li style='text-transform:capitalize'><a href=\"$b\"><i class=\"icon-chevron-right\"></i> $title</a></li>";
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
				$ff[]="<li class='$subactive'><a href=\"#\" >{monitor}</a></li>";
				while (list ($a, $b) = each ($monitorprox) ){
					$title=$tpl->_ENGINE_parse_body($a);
					$ff[]="<li style='text-transform:capitalize'><a href=\"$b\"><i class=\"icon-chevron-right\"></i> $title</a></li>";
				}				
			}
			
			// **********************************************************************************************			
			
			
			if(count($prxy)>0){
				$ff[]="<li style='text-transform:capitalize' class=\"$subactive\"><a href=\"#\">{manage}</a></li>";
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
	private $tpl;
	function boostrap_form(){
		$this->t=time();
		$this->id_animate="animateJS$this->t";
		$this->tpl=new templates();;
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
	
	function set_spacerexplain($explain){
		$this->fields_array[$explain]=array("TYPE"=>"EXPLAIN_SPACER","VALUE"=>$this->tpl->_ENGINE_parse_body($explain));
	}
	
	public function set_CloseYahoo($id){
		$this->CloseYahoo=$id;
	}
	
	public function set_RefreshSearchs(){
	
		$this->ExecuteByClassName="SearchFunction";
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
				"FIELDN"=>"$field_name-$this->t",
				"PARAMS"=>$params
		
		);
		
	}
	public function set_checkboxYN($field_name,$caption,$value,$params=array()){
		$this->fields_array["$field_name"]=array(
				"TYPE"=>"CHECKYN",
				"CAPTION"=>$caption,
				"VALUE"=>$value,
				"FIELDN"=>"$field_name-$this->t",
				"PARAMS"=>$params
	
		);
	
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
				"FIELDN"=>"$field_name-$this->t",
				"PARAMS"=>$params
				
				);
	}
	
	public function set_textarea($field_name,$caption,$value,$params=array()){
	
		$this->fields_array["$field_name"]=array(
				"TYPE"=>"TEXTAREA",
				"CAPTION"=>$caption,
				"VALUE"=>$value,
				"FIELDN"=>"$field_name-$this->t",
				"PARAMS"=>$params
	
		);
	}	
	
	
	
	
	
	public function set_fieldpassword($field_name,$caption,$value,$params=array()){
	
		$this->fields_array["$field_name"]=array(
				"TYPE"=>"PASSWORD",
				"CAPTION"=>$caption,
				"VALUE"=>$value,
				"FIELDN"=>"$field_name-$this->t",
				"PARAMS"=>$params
	
		);
	}	
	
	
	public function set_list($field_name,$caption,$values=array(),$default,$params=array()){
		$this->fields_array["$field_name"]=array(
				"TYPE"=>"LIST",
				"CAPTION"=>$caption,
				"VALUES"=>$values,
				"VALUE"=>$default,
				"FIELDN"=>"$field_name-$this->t",
				"PARAMS"=>$params
		
		);		
		
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
			setTimeout('LoadTab$md5()',500);
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
			
			
			if(count($EXPLAIN["BUTTONS"])>0){
				$EXPLAINTXTP[]="<div style='width:100%'>".@implode(" ", $EXPLAIN["BUTTONS"])."</div>";
				
			}
			
			if(count($EXPLAIN["LINKS"])>0){
				while (list ($index, $array) = each ($EXPLAIN["LINKS"]) ){
					$LABEL=$tpl->_ENGINE_parse_body($array["LABEL"]);
					$js=$array["JS"];
					$labels[]="<a href=\"javascript:blur();\" OnClick=\"javascript:$js\">$LABEL</a>";
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
			var btn$t = $('#bt-$t');
			btn$t.button('loading');
			setTimeout(function () { btn$t.button('reset') }, 2000);
			LoadAjax('$t-search','$page?$field='+pp+'$additional&qtype='+pa)		
		}
		
		function buttonHandler$t(){
			if(document.getElementById('ui-tooltip-1-content')){
				document.getElementById('ui-tooltip-1-content').innerHTML='';
			}
		
			$('#bt-$t').click(function(){ SearchQuery$t(); });		
		
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
		$t=rand(50, time());
		$page=CurrentPageName();
		$tpl=new templates();
		$error_on_this_field=$tpl->javascript_parse_text("{error_on_this_field}");
		$IsAnIpv4Error=$tpl->javascript_parse_text("{IsAnIpv4Error}");
		$functionName="Submit$t";
		if($this->ExecuteByClassName<>null){
			$final0="ExecuteByClassName('$this->ExecuteByClassName');";
		}
		$this->js=array();
		if($this->lockedForm){
			$f[]="<p class=text-warning>{this_form_is_locked}</p>";
		}
		
		$f[]="
		<center id='$this->id_animate'></center>		
		<center id='animate$t'></center>		
		<div class='form-horizontal' id='formname-$t'>";
		$c=0;
		while (list ($fieldname, $array) = each ($this->fields_array) ){
			$disabled=null;
			$c++;
			$button=null;
			$ClassPrepend=null;
			$classField=null;
			$TYPE=$array["TYPE"];
			$jsipv4Validator=false;
			$DISABLEALL=false;
			$CAPTION=$tpl->_ENGINE_parse_body($array["CAPTION"]);
			$CAPTIONjs=$tpl->javascript_parse_text($array["CAPTION"]);
			$VALUE=$array["VALUE"];
			$FIELDN=$array["FIELDN"];
			$params=$array["PARAMS"];
			$checked=null;
			$MANDATORY=$params["MANDATORY"];
			if(isset($params["DISABLED"])){$disabled="disabled";}
			if(isset($params["DISABLEALL"])){$DISABLEALL=true;}
			if(isset($params["IPV4"])){$jsipv4Validator=true;}
			if(isset($params["TOOLIP"])){if(!isset($params["TOOLTIP"])){$params["TOOLTIP"]=$params["TOOLIP"];}}
			$tooltip=null;
			
			if(isset($params["COOKIE"])){
				$this->js[]="\tSet_Cookie('{$params["COOKIE"]}', document.getElementById('$FIELDN').value, '3600', '/', '', '');";
			}
					
			
			if($this->lockedForm){$disabled="disabled";}
			
			if($TYPE=="HIDDEN"){
				$this->js[]="\tXHR.appendData('$fieldname','$VALUE');";
				continue;
			}
			
			if($TYPE=="TITLE"){
				$f[]="<H3>$VALUE</H3>";continue;
			}
			
			if($TYPE=="EXPLAIN_SPACER"){
				$f[]="<div class=explain>$VALUE</div>";
				continue;
			}
			
			
			
			$fieldsid[]=$FIELDN;
			
			if(isset($params["BUTTON"])){
				$params["BUTTON"]["JS"]=str_replace("%f", $FIELDN, $params["BUTTON"]["JS"]);
				//$ClassPrepend="input-append";
				//$classField='span2';
				$button="<button class='btn' type=\"button\" OnClick=\"{$params["BUTTON"]["JS"]}\">{$params["BUTTON"]["LABEL"]}</button>";
			}
			
			if(isset($params["TOOLTIP"])){
				$tooltip=$tpl->_ENGINE_parse_body($params["TOOLTIP"]);
				$tooltip=str_replace("\n", "<br>", $tooltip);
				$tooltip=str_replace("\"", "'", $tooltip);
				$CAPTION="<a href=\"#\" id='$FIELDN-tips' OnMouseOver=\"javascript:$(this).tooltip('show');\" OnMouseOut=\"javascript:$(this).tooltip('hide');\"
				data-toggle=\"tooltip\" data-html='true' data-title=\"<div style='font-size:12px'>$tooltip</div>\">$CAPTION</a>";
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
						$tre[]="\tif(document.getElementById('$FIELDN').checked){document.getElementById('$fieldIS-$this->t').disabled=false;}else{document.getElementById('$fieldIS-$this->t').disabled=true;}";
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
				if($VALUE==1){$checked="checked";$iconCK="<i class='icon-ok'></i>";}				
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
			
			
			
			$this->js[]="\tdocument.getElementById('Control-$t-$c').className='control-group';";
			$this->js[]="\tdocument.getElementById('help-$t-$c').innerHTML='';";
			
			if($MANDATORY){
				$this->js[]="\tvar field$c=document.getElementById('$FIELDN').value;";
				$this->js[]="\tif(field$c.length==0){\n\t\tdocument.getElementById('Control-$t-$c').className='control-group error';\n\t\tdocument.getElementById('help-$t-$c').innerHTML='$error_on_this_field';\n\t\treturn;\n\t}";
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
				$onKeyPress=" OnKeyPress=\"javascript:SendForm$t(event)\" ";
			}
			
			
			if($jsipv4Validator){
				$this->js[]="\tvar field2$c=document.getElementById('$FIELDN').value;";
				$this->js[]="\tif(field2$c.length>1){";
				
				$this->js[]="\tif(!IsAnIpv4(field2$c)){";
				$this->js[]="\t\t\talert('$IsAnIpv4Error');";
				$this->js[]="\t\t\tdocument.getElementById('Control-$t-$c').className='control-group error';";
				$this->js[]="\t\t\tdocument.getElementById('help-$t-$c').innerHTML='$error_on_this_field';";
				$this->js[]="\t\t\treturn;";
				$this->js[]="\t\t}";
				$this->js[]="\t}";
			}
			
			if($TYPE=="TEXT"){
				if(isset($params["TOOLTIP"])){$this->ToolTips[]=$this->BuildFieldToolTip($FIELDN,$params["TOOLTIP"]);}
				$field="<input type='text' id='$FIELDN' value='$VALUE' class='$classField' $onKeyPress$disabled>";
				if(isset($params["BROWSE"])){
					$browse=$tpl->_ENGINE_parse_body("{browse}");
					$field=$field."<button class='btn' type=\"button\" OnClick=\"Loadjs('SambaBrowse.php?no-shares=yes&field=$FIELDN')\">$browse...</button>";
				}				
			}
			
			if($TYPE=="PASSWORD"){
				if(isset($params["TOOLTIP"])){$this->ToolTips[]=$this->BuildFieldToolTip($FIELDN,$params["TOOLTIP"]);}
				$field="<input type='password' id='$FIELDN' value='$VALUE' class='$classField' $onKeyPress$disabled>";
				$this->js[]="\tXHR.appendData('$fieldname',encodeURIComponent(document.getElementById('$FIELDN').value));";
			}	
			
			if($TYPE=="TEXTAREA"){
				if(isset($params["TOOLTIP"])){$this->ToolTips[]=$this->BuildFieldToolTip($FIELDN,$params["TOOLTIP"]);}
				$field="<textarea id='$FIELDN' class='$classField' $onKeyPress$disabled
				style=\"font-size:16px !important;width:99% !important;min-height:60px !important\">". utf8_encode($VALUE)."</textarea>";
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
		if($this->CloseYahoo<>null){$CloseYahoo="{$this->CloseYahoo}Hide()";}
		
		
		while (list ($index, $ids) = each ($fieldsid) ){
			$jsdisableAll[]="\tdocument.getElementById('$ids').disabled=true;";
			$jsEnableAll[]="\tdocument.getElementById('$ids').disabled=false;";
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
				
				$xbtlock="btn-primary";
				if($this->lockedForm){$xbtlock=null;$xbutjs="blur();";}
				$f[]="<button class=\"btn-large {$xbtlock}\" style='font-size:16px' type=\"button\"  
				OnClick=\"$xbutjs\" data-loading-text=\"$Loading...\">$xbuttonname</button>";
			}
		}
		$f[]="
		<!-- $infoLock -->		
		<button class=\"btn-large {$btlock}\" id='MAINBUT-$t' style='font-size:16px' type=\"button\"  data-loading-text=\"$Loading...\">$this->buttonname&nbsp;&raquo;</button>";
		$f[]="</div>		
		</div>
		<script>		
		
var x$functionName= function (obj) {
\tvar btn$t = $('#MAINBUT-$t');
\tbtn$t.button('reset');
\tvar results=obj.responseText;
\tif(document.getElementById('animate$t')){
\t\tdocument.getElementById('animate$t').innerHTML='';
\t}
\tif(results.length>3){alert(results);return;}
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
\tbtn$t.button('loading');
\tvar XHR = new XHRConnection();
". @implode("\n", $this->js)."
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
				title: \"<div style='font-size:12px'>$text</div>\",
				
			});\n";
	}

}



function LineToClass($line){
	$class=null;
	if(preg_match("#FATAL#i", $line)){return "warning";}
	if(preg_match("#failed#i", $line)){return "warning";}
	if(preg_match("#abnormally#i", $line)){return "error";}
	if(preg_match("#Cannot allocate memory#i", $line)){return "error";}
	if(preg_match("#Reconfiguring#i", $line)){return "info";}
	if(preg_match("#Accepting HTTP#i", $line)){return "info";}
	if(preg_match("#Ready to serve requests#i", $line)){return "info";}
	if(preg_match("#aborting task#i", $line)){return "warning";}
	if(preg_match("#Too much instances#i", $line)){return "warning";}
	if(preg_match("#unable to#i", $line)){return "warning";}
	if(preg_match("#New Artica Database#i", $line)){return "info";}
	if(preg_match("#is marked as crashed#i", $line)){return "error";}
	if(preg_match("#error reading communication#i", $line)){return "warning";}
	if(preg_match("#Can't connect#i", $line)){return "warning";}
	if(preg_match("#ready for connection#i", $line)){return "info";}
	if(preg_match("#Disk is full#i", $line)){return "error";}
	if(preg_match("#(ERROR|WARN|FATAL|UNABLE|Failed|not found|denied|VIRUS DETECTED)#i", $line)){return "error";}
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
		if($users->AsHotSpotManager){$_SESSION["Privileges_members_admins"]=true;return true;}
		if($users->AsSambaAdministrator){$_SESSION["Privileges_members_admins"]=true;return true;}
		if($users->AsSystemAdministrator){$_SESSION["Privileges_members_admins"]=true;return true;}
		if($users->AsSquidAdministrator){$_SESSION["Privileges_members_admins"]=true;return true;}
		if($users->AsPostfixAdministrator){$_SESSION["Privileges_members_admins"]=true;return true;}
	
	
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


?>