<?php
include_once(dirname(__FILE__)."/class.mysql.inc");

class squid_freewebs{
	
	
	
	function squid_freewebs(){
		$this->build_acls();
		
	}
	
	
	function build_acls(){
		$q=new mysql();
		$sql="SELECT * from freeweb WHERE enabled=1";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){return;}
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$servername=$ligne["servername"];
			$Params=@unserialize(base64_decode($ligne["Params"]));
			$servername=str_replace(".", "\.", $servername);
			if(trim($servername)==null){continue;}
			$f[]="acl FreeWebs dstdom_regex -i $servername";
			if(count($Params["ServerAlias"])>0){
				while (list ($host, $data) = each ($Params["ServerAlias"]) ){
					if(trim($host)==null){continue;}
					$host=str_replace(".", "\.", $host);
					$f[]="acl FreeWebs dstdom_regex -i $host";
				}
				
			}
		}
	
	
	if(count($f)>0){
		$f[]="http_access allow FreeWebs all";
		$f[]="url_rewrite_access deny FreeWebs all";
		@file_put_contents("/etc/squid3/freewebs.conf", @implode("\n", $f)."\n");
	}
	
}

}