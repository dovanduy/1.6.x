<?php

FUNCTION NGINX_DESTINATION_EXPLAIN($cache_peer_id,$color=null){
	$q=new mysql_squid_builder();
	$tpl=new templates();

	if($color==null){$color="black";}
	$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_sources WHERE ID='$cache_peer_id'"));
	$servername=$ligne["servername"];
	$ipaddr=$ligne["ipaddr"];
	$port=$ligne["port"];
	$ssl=$ligne["ssl"];
	$cacheid=$ligne["cacheid"];
	$ssl_text=null;
	$forceddomain=$ligne["forceddomain"];
	$remote_path=$ligne["remote_path"];
	if($ssl==1){$ssl_text=" (SSL)";}
	$f[]="<a href=\"javascript:blur();\" 
	OnClick=\"javascript:Loadjs('nginx.peer.php?js=yes&ID={$cache_peer_id}');\" 
	style='font-size:26px;text-decoration:underline;color:$color'>$servername</span></a>";
	$f[]="<i style='font-size:18px;font-weight:normal;color:$color'>$ipaddr:$port$ssl_text</i>";
	if($forceddomain<>null){$f[]="<i style='font-size:18px;font-weight:normal;color:$color'>{virtualhost}:$forceddomain</i>";}
	if($remote_path<>null){$f[]="<i style='font-size:18px;font-weight:normal;color:$color'>{path}:$remote_path</i>";}
	if($cacheid>0){
		$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT `keys_zone` FROM nginx_caches WHERE ID='$cacheid'"));
		$f[]="<i style='font-size:12px;font-weight:normal;color:$color'>{use_cache}:<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('nginx.caches.php?js-cache=yes&ID=$cacheid')\"
		style='font-size:12px;font-weight:normal;text-decoration:underline;color:$color'>{$ligne["keys_zone"]}</a></i>";
	}

	$text=@implode("<br>", $f);
	return $tpl->_ENGINE_parse_body($text);


}

FUNCTION  NGINX_EXPLAIN_REVERSE($servername,$color=null){
	$q=new mysql_squid_builder();
	$servernameencode=urlencode($servername);
	$tpl=new templates();
	if($color==null){$color="black";}

	$nginx_zavailb_explain=$tpl->javascript_parse_text("{nginx_zavailb_explain}");
	$nginx_zavailb_explain=str_replace(",", ",<br>", $nginx_zavailb_explain);
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_www WHERE servername='$servername'"));
	$proxy_buffering=$ligne["proxy_buffering"];
	$RedirectQueries=$ligne["RedirectQueries"];
	$servername_pattern=$ligne["servername_pattern"];
	$servername_pattern_text=null;
	$zavail=$ligne["zavail"];
	$site_enabled=$ligne["enabled"];
	$ssl="{proto} (HTTP) ";

	if($ligne["ssl"]==1){
		$certificate_text=$tpl->_ENGINE_parse_body("<span style='font-weight:bold'>{certificate}: {default}</span><br>");
		$ssl="{proto} (HTTP<b>S</b>) ";

		if($ligne["port"]==80){
			$ssl="{proto} (HTTP) {and} {proto} (HTTP<b>S</b>) ";
		}
	}
	
	

	if($ligne["certificate"]<>null){
		$CommonName=urlencode($ligne["certificate"]);
		$js="<a href=\"javascript:blur()\"
		OnClick=\"javascript:Loadjs('certificates.center.php?certificate-edit-js=yes&CommonName=$CommonName');\"
		style='text-decoration:underline'>";
		$certificate_text=$tpl->_ENGINE_parse_body("<br>{certificate}: $js{$ligne["certificate"]}</a><br>");;
	}	
	



	$page=CurrentPageName();
	$cache_peer_id=$ligne["cache_peer_id"];
	$ssl_backend=$ligne["ssl_backend"];
	$DEST=array();
	if($cache_peer_id==0){
		return $tpl->_parse_body("<strong>{no_destination}</strong><br><i>{nginx_destination_none_explain}</i>");
	}
	
	
	if($cache_peer_id>0){
		$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT certificate,servername,ipaddr,port,ForceRedirect,OnlyTCP FROM reverse_sources WHERE ID='{$ligne["cache_peer_id"]}'"));
		if(!$q->ok){echo "<p class=text-error>$q->mysql_error in ".basename(__FILE__)." line ".__LINE__."</p>";}
		$ForceRedirect="<br>{ForceRedirectyes_explain_table}";
		$destination_server=$ligne["servername"];
		if($ligne["ForceRedirect"]==0){ $ForceRedirect="<br>{ForceRedirectno_explain_table}"; }
		if($ligne["ssl"]==1){ $ssl="{proto} (HTTP<b>S</b>) "; }
		if($ligne["OnlyTCP"]==1){ $ssl="{proto} TCP";$ForceRedirect=null; }
		$js="Loadjs('$page?js-source=yes&source-id={$ligne["cache_peer_id"]}')";
		
		
		if($ligne["certificate"]<>null){
			$CommonName=urlencode($ligne["certificate"]);
			$js="<a href=\"javascript:blur()\"
			OnClick=\"javascript:Loadjs('certificates.center.php?certificate-edit-js=yes&CommonName=$CommonName');\"
			style='text-decoration:underline'>";
			$certificate_text=$tpl->_ENGINE_parse_body("<br>{certificate} - <strong>{from}:$destination_server</strong> -: $js{$ligne["certificate"]}</a></strong><br>");;
		}	
			
			
		$DEST[]="<br>$ssl,&nbsp;";
		if($cache_peer_id>0){
			$DEST[]="{redirect_communications_to}:";
			if($ssl_backend==1){
				$ligne["port"]="<strong>443 (https)</strong>";
				
			}
			
			$DEST[]="<br>&laquo;{$ligne["servername"]}&raquo; {address} <strong>{$ligne["ipaddr"]}</strong> {on_port} <strong>{$ligne["port"]}</strong> id:$cache_peer_id";
			if($ForceRedirect<>null){$DEST[]=$ForceRedirect;}
		}
	}
	
	if(count($DEST)>0){$DESTINATION_TEXT=@implode(" ", $DEST);}
	
	

	$sql="SELECT * FROM nginx_exploits WHERE servername='$servername' LIMIT 0,5";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){senderror($q->mysql_error);}

	$filters=array();
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$groupid=$ligne["groupid"];
		$jsedit="Loadjs('miniadmin.nginx.exploits.groups.php?js-group=yes&ID=$groupid&servername={$_GET["servername"]}')";
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(*) as tcount FROM nginx_exploits_items WHERE groupid='$groupid'"));
		$RulesNumber=$ligne2["tcount"];
		$AF="<a href=\"javascript:blur();\" OnClick=\"javascript:$jsedit\" style='text-decoration:underline'>";
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM nginx_exploits_groups WHERE ID='$groupid'"));
		$filters[]="{group} $AF{$ligne2["groupname"]} ($RulesNumber {items})</a>";
	}
	if(count($filters)>0){
		$exp[]="<br>{check_anti_exploit_with}:".@implode(", ", $filters);
	}

	$jsban="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('miniadmin.nginx.exploits.php?firewall-js=yes&servername=$servername')\"
	style='text-decoration:underline'>";
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT maxaccess,sendlogs FROM nginx_exploits_fw WHERE servername='$servername'"));
	if($ligne["maxaccess"]>0){
		$exp[]="<br>{bann_ip_after} $jsban{$ligne["maxaccess"]} {events}</a>";
	}
	if($ligne["sendlogs"]==1){$exp[]=",&nbsp;{write_logs_for} {$jsban}403 {errors}</a>";}

	if($RedirectQueries==null){
		$proxy_buffering_text="<br><span style='color:#00B726'>{remote_webpages_are_cached}</span>";
		if($proxy_buffering==0){$proxy_buffering_text="<br><span style='color:#878787'>{caching_webpages_is_disabled}</span>";}
		if($proxy_buffering_text<>null){$exp[]=$proxy_buffering_text;}
	}
	
	if($RedirectQueries<>null){
		$DESTINATION_TEXT="<br>{RedirectQueries_explain_table}<br><a href=\"$RedirectQueries\" target=_new style='text-decoration:underline'>$RedirectQueries</a>";
	}
	
	
	if($servername_pattern<>null){
		$servername_pattern_text="{replace_server_directive}: <strong>$servername_pattern</strong>";
	}
	
	$sql="SELECT * FROM nginx_aliases WHERE servername='$servername' ORDER BY alias LIMIT 0,250";
	$results2=$q->QUERY_SQL($sql);
	$ali=array();$alitext=null;
	while($ligne=mysql_fetch_array($results2,MYSQL_ASSOC)){
			$ali[]="<a href=\"javascript:blur();\"
			OnClick=\"javascript:Loadjs('nginx.site.aliases.php?popup-js=yes&servername=$servernameencode');\"
			style='text-decoration:underline;color:$color'>{$ligne["alias"]}</a>";
	}
	if(count($ali)>0){$alitext="{alias}:&nbsp;(" .@implode("{or} ", $ali).")&nbsp;"."<br>";}
	if($site_enabled==1){
		if($zavail==0){$zavail_text="<br><i style='font-size:18px;color:#d32d2d'>$nginx_zavailb_explain</i>";}
	}
	
	$html=$tpl->_ENGINE_parse_body("$zavail_text<i style='font-size:18px;color:$color'>$certificate_text$alitext$servername_pattern_text$DESTINATION_TEXT".@implode(" ", $exp)."</i>");
	$html=str_replace("<br><br>", "<br>", $html);
	return $html;
}