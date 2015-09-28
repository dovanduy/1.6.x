<?php
include_once(dirname(__FILE__)."/class.squid.acls.inc");
include_once(dirname(__FILE__)."/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/class.tcpip.inc");
class acls_cache_deny{
	public $acl=array();
	
	private $cachefile="/etc/squid3/GlobalAccessManager_deny_cache.conf";
	
	function acls_cache_deny(){
		
		
		
	}
	
	private function root_squid_version_nocache(){
		if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
		$unix=new unix();
	
		$squidbin=$unix->LOCATE_SQUID_BIN();
		if($squidbin==null){$squidbin=trim($unix->find_program("squid3"));}
		if(!is_file($squidbin)){return;}
		exec("$squidbin -v 2>&1",$results);
	
		$this->SQUID_VERSIONS_LOGS[]="# Binary $squidbin";
	
	
		while (list ($num, $val) = each ($results)){
				if(!preg_match("#Squid Cache: Version.*?([0-9\.]+)#", $val,$re)){continue;}
				if($re[1]=="2.7."){$re[1]="2.7.0";}
				if($re[1]=="3.0."){$re[1]="3.0.0";}
				if($re[1]=="3.1."){$re[1]="3.1.0";}
				if($re[1]=="3.2."){$re[1]="3.2.0";}
				if($re[1]=="3.3."){$re[1]="3.3.0";}
				if($re[1]=="3.4."){$re[1]="3.4.0";}
				if($re[1]=="3.5."){$re[1]="3.5.0";}
				if($re[1]=="3.6."){$re[1]="3.6.0";}
				if($re[1]=="3.7."){$re[1]="3.7.0";}
				$GLOBALS[__FUNCTION__]=$re[1];
				return $re[1];
			
		}
	
	}
	
	
	
	function build(){
		$root_squid_version_nocache=$this->root_squid_version_nocache();
		$sock=new sockets();
		$this->acl[]="#Builded on ". date("Y-m-d H:i:s"). " Squid v{$root_squid_version_nocache}";
		
		$VERZ=explode(".",$root_squid_version_nocache);
		$MAJOR=intval($VERZ[0]);
		$MINOR=intval($VERZ[1]);
		
		$SquidCacheLevel=$sock->GET_INFO("SquidCacheLevel");
		$SquidUrgency=intval($sock->GET_INFO("SquidUrgency"));
		if(!is_numeric($SquidCacheLevel)){$SquidCacheLevel=4;}
		if($SquidCacheLevel==0){
			$this->acl[]="# Do not cache ALL domains: SquidCacheLevel:$SquidCacheLevel\n";
			$this->acl[]="acl DoNotCache dstdomain all";
			return;
		}
		
		
		if($SquidUrgency==1){
			
			$this->acl[]="# Emergency enabled, deny ACL `DoNotCache`";
			$this->acl[]="acl DoNotCache dstdomain all";
			return;
		}
		$this->acl[]="acl DoNotCache dstdomain s497977761.onlinehome.fr";
		$this->acl[]="acl DoNotCache dstdomain .unveiltech.com";
		$this->acl[]="acl DoNotCache dstdomain .artica.fr";
		
		
		$this->webfilters_blkwhlts();
		$this->deny_cache_domains();
		
		if($MAJOR>2){
			if($MINOR>4){
				$this->acl[]="send_hit deny DoNotCache";
				$this->acl[]="store_miss deny DoNotCache";
			}
		}
	}
	
	private function deny_cache_domains(){
		$already=array();
		$q=new mysql_squid_builder();
		$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `deny_cache_domains` ( `items` VARCHAR(256) NOT NULL PRIMARY KEY ) ENGINE=MYISAM;");
		if(!$q->TABLE_EXISTS("deny_cache_domains")){return;}
		if($q->COUNT_ROWS("deny_cache_domains")==0){
			$this->acl[]="# deny_cache_domains - no rows";
			return;}
		
		$sql="SELECT items FROM deny_cache_domains";
		$results=$q->QUERY_SQL($sql);
		$IPClass=new IP();
		
		$DOMAINS=array();
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$www=trim($ligne["items"]);
			$www_def=$www;
			if($www==null){continue;}
			
			if($IPClass->isIPAddressOrRange($www)){
				$this->acl[]="acl DoNotCache dst $www";
				continue;
			}
			

			$www=$this->BaseNameWebSite($www);
			if($www==null){continue;}
			if(isset($already[$www])){continue;}
			$already[$www]=true;
			
			$DOMAINS[]=$www;
			
			
		}
		if(count($DOMAINS)>0){
			$acls=new squid_acls();
			$FINAL=$acls->clean_dstdomains($DOMAINS);
			while (list ($index, $domain) = each ($FINAL) ){
				$this->acl[]="acl DoNotCache dstdomain $domain";
			}
		}
			
		
	}
	private function BaseNameWebSite($www){
		if(trim($www)==null){return;}
		if(is_numeric(trim($www))){return;}
			
		if(strpos(" $www", "//")>0){
			$arrayURI=parse_url($www);
			$www=$arrayURI["host"];
	
		}
		if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}
		if(preg_match("#(.+):[0-9]+#", $www,$re)){$www=$re[1];}
		if(substr($www,0,1)=="."){$www=substr($www, 1,strlen($www));}
		if(substr($www,0,1)=="^"){$www=substr($www, 1,strlen($www));}
		if(substr($www,strlen($www)-1,1)=="/"){$www=substr($www, 0,strlen($www)-1);}
		$sitebase=$www;
		$sitebase=str_replace('.*.', '.', $sitebase);
		$sitebase=str_replace('*', '.', $sitebase);
		$sitebase=str_replace('$', '', $sitebase);
		$sitebase=str_replace('(', '', $sitebase);
		$sitebase=str_replace(')', '', $sitebase);
		$sitebase=str_replace(';', '.', $sitebase);
		$sitebase=str_replace(',', '.', $sitebase);
		$sitebase=str_replace('%', '', $sitebase);
		$sitebase=str_replace('+', '', $sitebase);
		$sitebase=str_replace('/', '', $sitebase);
		$sitebase=str_replace('?', '', $sitebase);
		$sitebase=str_replace('!', '', $sitebase);
		$sitebase=str_replace(':', '', $sitebase);
		$sitebase=str_replace('{', '', $sitebase);
		$sitebase=str_replace('}', '', $sitebase);
		$sitebase=str_replace('^', '', $sitebase);
		$sitebase=str_replace('\\', '', $sitebase);
		$sitebase=str_replace('\'', '', $sitebase);
		$sitebase=str_replace('"', '', $sitebase);
		$sitebase=str_replace('&', '', $sitebase);
		$sitebase=str_replace('<', '', $sitebase);
		$sitebase=str_replace('>', '', $sitebase);
		$sitebase=str_replace('[', '', $sitebase);
		$sitebase=str_replace(']', '', $sitebase);
		return $sitebase;
	}
	
	private function webfilters_blkwhlts(){
		$q=new mysql_squid_builder();
		if(!$q->TABLE_EXISTS("webfilters_blkwhlts")){return;}
		if($q->COUNT_ROWS("webfilters_blkwhlts")==0){return;}
		$already=array();
		$sql="SELECT pattern,PatternType FROM webfilters_blkwhlts WHERE blockType=4 AND enabled=1";
		$results=$q->QUERY_SQL($sql);
			
		$this->acl[]="# Do not cache domains: ". mysql_num_rows($results)." items in ".__CLASS__."/".__FUNCTION__." Line:".__LINE__;

			
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$www=trim($ligne["pattern"]);
			$www_def=$www;
			if($www==null){continue;}
			if($ligne["PatternType"]==2){
				$sq=new squid_acls();
				$sqgroups=$sq->GetItems($www,"dstdomain");
				while (list ($indexed, $www) = each ($sqgroups) ){$www=$this->BaseNameWebSite($www);if($www==null){continue;}if(isset($already[$www])){continue;}$already[$www]=true;$acl[]="acl DoNotCache dstdomain .$www";continue;}
				continue;
			}
			if($ligne["PatternType"]<>0){continue;}
			$www=$this->BaseNameWebSite($www);
			if($www==null){continue;}
			if(isset($already[$www])){continue;}
			$already[$www]=true;
			
			if(substr($www_def,0,1)=="^"){
				$this->acl[]="acl DoNotCache dstdomain $www";
				continue;
			}
			
			$this->acl[]="acl DoNotCache dstdomain .$www";
		
		}
		
	}
	
	
	
}