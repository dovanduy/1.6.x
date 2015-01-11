<?php
include_once(dirname(__FILE__).'/class.mysql.inc');


class SquidCacheCenter{
	
	private $table="squid_caches_center";
	private $database="artica_backup";
	private $CORP_LICENSE=false;
	private $SquidBoosterEnable=0;
	private $DisableAnyCache=0;
	private $SquidCacheLevel=4;
	public $CURRENT_PROGRESS=0;
	public $MAX_PROGRESS=0;
	public $ASROOT=false;
	public $CACHES_ARRAY=array();

	function SquidCacheCenter(){
		$this->CacheDefault();
		$sock=new sockets();
		$users=new usersMenus();
		$this->CORP_LICENSE=$users->CORP_LICENSE;
		$this->SquidBoosterEnable=intval($sock->GET_INFO("SquidBoosterEnable"));
		$this->DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
		$this->SquidCacheLevel=$sock->GET_INFO("SquidCacheLevel");
		if(!is_numeric($this->SquidCacheLevel)){$this->SquidCacheLevel=4;}
		if($this->SquidCacheLevel==0){$this->DisableAnyCache=1;$this->SquidBoosterEnable=0;}
		$this->CACHES_ARRAY=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/squid_caches_center.db"));
		
	}
	
	
	private function CacheDefault(){
		$q=new mysql();
		
		if(!$q->FIELD_EXISTS("squid_caches_center","remove","artica_backup")){
			$sql="ALTER TABLE `squid_caches_center` ADD `remove` smallint(1) NOT NULL,ADD INDEX (remove)";
			$q->QUERY_SQL($sql,$this->database);
			if(!$q->ok){writelogs("$q->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}
		
		
		if(!$q->COUNT_ROWS($this->table, $this->database)==0){
			$squid=new squidbee();
			$cachename=basename($squid->CACHE_PATH);
			$q->QUERY_SQL("INSERT IGNORE INTO $this->table (cachename,cpu,cache_dir,cache_type,cache_size,cache_dir_level1,cache_dir_level2,enabled,percentcache,usedcache,remove)
			VALUES('$cachename',1,'$squid->CACHE_PATH','$squid->CACHE_TYPE','2000','128','256',1,0,0,0)",$this->database);
		}
		

	}
	
	
	private function WORKERS(){
		$q=new mysql();
		$f[1]=1;
		if(!$this->CORP_LICENSE){return 1;}
		$sock=new sockets();
		$DisableSquidSMP=intval($sock->GET_INFO("DisableSquidSMP"));
		if($DisableSquidSMP==1){return 1;}
		
		$sql="SELECT cpu FROM $this->table WHERE `enabled`=1 AND `remove`=0 AND cpu>0 ORDER BY cpu";
	
		$results=$q->QUERY_SQL($sql,"artica_backup");
		while ($ligne = mysql_fetch_assoc($results)) {
			$f[$ligne["cpu"]]=$ligne["cpu"];
		}
		
		$c=1;
		while (list ($cpu, $val) = each ($f)){
			if($cpu>$c){$c=$cpu;}
		}
		
		return $c;
	}
	

	private function build_progress($text){
			
		if(!isset($GLOBALS["PRC"])){$GLOBALS["PRC"]=$this->CURRENT_PROGRESS;}
		if(!$this->ASROOT){return;}
			
		///--wccp-build
			
		$GLOBALS["PRC"]++;
		echo "Starting......: ".date("H:i:s")." $text {$GLOBALS["PRC"]}%\n";
		if(!function_exists("build_progress")){return;}
		if($this->CURRENT_PROGRESS==0){return;}
		if($GLOBALS["PRC"]>$this->MAX_PROGRESS){$GLOBALS["PRC"]=$this->MAX_PROGRESS;}
		build_progress("{reconfigure} $text",$GLOBALS["PRC"]);
	}
	
	
	public function build(){
		$this->build_progress("Checking Workers");
		$WORKERS=$this->WORKERS();
		$this->build_progress("Checking Default cache");
		$this->CacheDefault();
		$conf[]="#";
		$conf[]="#";
		$conf[]="# # # # # # CACHES MANAGEMENT # # # # # #";
		$conf[]="#";
		$conf[]="# --------- MULTIPLE CPUs --------- ".__CLASS__."/".__FUNCTION__." [".__LINE__."]";
		$conf[]="# --------- Number of CPU.....: $WORKERS ".__CLASS__."/".__FUNCTION__." [".__LINE__."]";
		$conf[]="# --------- SquidBoosterEnable: $this->SquidBoosterEnable ".__CLASS__."/".__FUNCTION__." [".__LINE__."]";
		$conf[]="# --------- DisableAnyCache...: $this->DisableAnyCache ".__CLASS__."/".__FUNCTION__." [".__LINE__."]";
		$conf[]="# --------- SquidCacheLevel...: $this->SquidCacheLevel ".__CLASS__."/".__FUNCTION__." [".__LINE__."]";
		
		
		$this->build_progress("Checking Rock cache");
		$conf[]=$this->build_rock();
		$conf[]="#";
		$conf[]="workers $WORKERS";
		
		for($i=1;$i<$WORKERS+1;$i++){
			$conf[]="# --------- CPU #{$i} ".__CLASS__."/".__FUNCTION__." [".__LINE__."]";
			$conf[]="if \${process_number} = $i";
			$this->build_progress("Checking #CPU $i");
			$conf[]=$this->build_CPU($i);
			$conf[]="endif";
			
		}
		$conf[]="#";
		$conf[]="#";
		$this->build_progress("Checking SMP done.");
		return @implode("\n", $conf);
	}
	
	
	private function build_rock(){
		if($this->DisableAnyCache==1){return;}
		$q=new mysql();
		$unix=new unix();
		$conf=array();
		if($this->SquidBoosterEnable==1){
			@mkdir("/home/squid/cache_rock",0755,true);
			@chown("/home/squid/cache_rock", "squid");
			@chgrp("/home/squid/cache_rock", "squid");
			$conf[]="# --------- Cache Booster one rock for better performance...";
			$conf[]="cache_dir rock /home/squid/cache_rock 5000 min-size=1 max-size=31000 max-swap-rate=250 swap-timeout=350";
			
		}
		
		$sql="SELECT * FROM $this->table WHERE `enabled`=1 AND `remove`=0 AND `cache_type`='rock' ORDER BY zOrder";
		$results=$q->QUERY_SQL($sql,$this->database);
		if(mysql_num_rows($results)==0){return @implode("\n", $conf);}
		while ($ligne = mysql_fetch_assoc($results)) {
			$cachename=$ligne["cachename"];
			$cache_dir=$ligne["cache_dir"];
			$cache_type=$ligne["cache_type"];
			$cache_size=$ligne["cache_size"];
			if($this->DisableAnyCache==1){$cache_type="Cachenull";}
			
			if($cache_type=="Cachenull"){
				$conf[]="# --------- $cachename is a null cache";
				continue;
			}
			
			$cache_dir_level1=$ligne["cache_dir_level1"];
			$cache_dir_level2=$ligne["cache_dir_level2"];
			$cache_partition=$unix->DIRPART_OF($cache_dir);
			
			if(!isset($GLOBALS["CACHES_SIZE"][$cache_partition])){
				$GLOBALS["CACHES_SIZE"][$cache_partition]=$unix->DIRECTORY_FREEM($cache_dir);
			}
			
			
			$used=$unix->DIRSIZE_MB($cache_dir);
			$current_cache_size=$cache_size-$used;
			$reste=$GLOBALS["CACHES_SIZE"][$cache_partition]-$current_cache_size;
			
			if($GLOBALS["VERBOSE"]){
				echo "$cache_type $cache_dir\nDefined Size:{$ligne["cache_size"]}M\n";
				echo "Partition: $cache_partition\n";
				echo "Available:  {$GLOBALS["CACHES_SIZE"][$cache_partition]}M\n";
				echo "Used: {$used}M size = $cache_size\n";
				echo "Reste: {$reste}M\n";
				
				echo "--------------------------\n";
				echo "New size: {$GLOBALS["CACHES_SIZE"][$cache_partition]}M\n\n\n";
			}
					
					
			$this->CreateDir($cache_dir);
			$conf[]="# --------- $cache_dir used:{$used}M/{$cache_size}M Disk:$cache_partition free:{$reste}M";
			if($reste<1){
				$conf[]="# --------- $cache_dir no space left\n";
				continue;
			}
			$GLOBALS["CACHES_SIZE"][$cache_partition]=$reste;
			$conf[]="cache_dir rock $cache_dir $cache_size min-size=1 max-size=31000 max-swap-rate=250 swap-timeout=350";
		}
		return @implode("\n", $conf);
	}
	
	
	private function build_CPU($cpu){
		
		if($cpu==1){
			if($this->SquidBoosterEnable==1){
				$squidbooster=new squidbooster();
				$this->build_progress("Checking Cache booster");
				$conf[]=$squidbooster->cache_booster();
			}
		}
		
		$q=new mysql();
		$unix=new unix();
		$this->build_progress("Loading MySQL settings for CPU #1");
		$sql="SELECT * FROM $this->table WHERE `enabled`=1 AND `remove`=0 AND `cpu`=$cpu ORDER BY zOrder";
		$results=$q->QUERY_SQL($sql,$this->database);
		while ($ligne = mysql_fetch_assoc($results)) {
			$ID=$ligne["ID"];
			
			$cachename=$ligne["cachename"];
			$cache_dir=$ligne["cache_dir"];
			$cache_type=$ligne["cache_type"];
			$cache_size=abs(intval($ligne["cache_size"]));
			$cache_dir_level1=$ligne["cache_dir_level1"];
			$cache_dir_level2=$ligne["cache_dir_level2"];
			
			$min_size=$ligne["min_size"];
			$max_size=$ligne["max_size"];			
			
			
			$this->build_progress("Cache $cachename Cache size:{$cache_size}M");
			$conf[]="#";
			$conf[]="# --------- [CPU #{$cpu}]: Cache $cachename ID = $ID type:$cache_type Cache size:{$cache_size}M [".__LINE__."]";
			if($this->DisableAnyCache==1){$cache_type="Cachenull";}
			
			if($cache_type=="tmpfs"){
				$conf[]=$this->build_MemBooster($ID,$cache_size,$min_size,$max_size);
				continue;
			}
			
			if($cache_type=="Cachenull"){
				continue;
			}
			
			if($cache_type=="rock"){continue;}
			
			if(@is_link($cache_dir)){$cache_dir=@readlink($cache_dir);}
			$this->CreateDir($cache_dir);
		
			
			$cache_partition=$unix->DIRPART_OF($cache_dir);
		
			if(!isset($GLOBALS["CACHES_SIZE"][$cache_partition])){
				
				if(!isset($this->CACHES_ARRAY["CACHES_SIZE"][$cache_partition])){
					$GLOBALS["CACHES_SIZE"][$cache_partition]=$unix->DIRECTORY_FREEM($cache_dir);
				}else{
					$GLOBALS["CACHES_SIZE"][$cache_partition]=$this->CACHES_ARRAY["CACHES_SIZE"][$cache_partition];
				}
			}
		
			
			
		if(!isset($this->CACHES_ARRAY[$cache_dir]["DIRSIZE_MB"])){
			$this->build_progress("Calculate $cache_dir size {please_wait}");
			$used=$unix->DIRSIZE_MB($cache_dir);
		}else{
			$used=$this->CACHES_ARRAY[$cache_dir]["DIRSIZE_MB"];
		}
		$conf[]="# --------- [CPU #{$cpu}]: $cache_dir: Used: $used";
		$current_cache_size=$cache_size-$used;
		$reste=$GLOBALS["CACHES_SIZE"][$cache_partition]-$current_cache_size;
		
		if($GLOBALS["VERBOSE"]){
			echo "$cache_type $cache_dir\nDefined Size:{$ligne["cache_size"]}M\n";
			echo "Partition: $cache_partition\n";
			echo "Available:  {$GLOBALS["CACHES_SIZE"][$cache_partition]}M\n";
			echo "Used: {$used}M size = $cache_size\n";
			echo "--------------------------\n";
			echo "Reste: {$reste}M\n";
		}
			
		
		$conf[]="# --------- [CPU #{$cpu}]: $cache_dir: {$used}M/{$ligne["cache_size"]}M $cache_partition: {$GLOBALS["CACHES_SIZE"][$cache_partition]}M free:{$reste}MB";
		
		if($reste<1){
			$conf[]="# --------- [CPU #{$cpu}]: No space left on $cache_dir / $cache_partition for {$cache_size}M free:{$reste}M";
			continue;
		}
		$GLOBALS["CACHES_SIZE"][$cache_partition]=$reste;
		$min_size_text=" min-size=0";
		if($max_size>0){
			$max_size=$max_size*1024;
			$max_size_text=" max-size=$max_size";
		}
		
		if($min_size>0){
			$min_size=$min_size*1024;
			$min_size_text=" min-size=$min_size";
		}
		$conf[]="cache_dir\t$cache_type\t$cache_dir\t$cache_size\t$cache_dir_level1\t$cache_dir_level2$min_size_text$max_size_text";
			
		}
		
		$conf[]="# --------- [CPU #{$cpu}]: ".count($conf)." items [".__LINE__."]";
		$this->build_progress("Checking #CPU $cpu - Done -");
		return @implode("\n", $conf);
		
	}
	
	private function build_MemBooster($ID,$cache_size,$minsize=0,$maxsize=0){
		$GLOBALS["BuildSquidCachesMountInRam"][]="# Checking Memory Booster $ID {$cache_size}MB [".__LINE__."]";
		$this->build_progress("Checking Memory Booster $ID {$cache_size}MB");
		$directory="/home/squid/cache/MemBooster$ID";
		
		$cache_size_conf=$cache_size-10;
		
		$options[]="min-size=$minsize";
		if($maxsize>0){$options[]="max-size=$maxsize";}
		 
		
		$conf="cache_dir	aufs	$directory $cache_size_conf 16 256 ".@implode(" ", $options);
		
		$this->CreateDir($directory);
		$MountedRAM=$this->BuildSquidCachesMountIsMounted($ID);
		if($MountedRAM==0){
			$this->BuildSquidCachesMountInRam($cache_size,$ID);
			$MountedRAM=$this->BuildSquidCachesMountIsMounted($ID);
			if($MountedRAM>0){return $conf;}
			return "# --------- $ID {$cache_size}MB unable to mount $directory\n".@implode("\n", $GLOBALS["BuildSquidCachesMountInRam"]);
			
		}
		
		
		$Diff=intval($cache_size-$MountedRAM);
		if(preg_match("#^-([0-9]+)#", $Diff,$re)){$Diff=intval($re[1]);}
		if($Diff>5){
			$this->BuildSquidCachesMountUmount($ID);
			$this->BuildSquidCachesMountInRam($cache_size,$ID);
			$MountedRAM=$this->BuildSquidCachesMountIsMounted($ID);
			if($MountedRAM>0){return $conf;}
			return "# --------- $ID {$cache_size}MB unable to mount $directory";
		}
		return $conf;
		
	}
	
	private function  BuildSquidCachesMountUmount($Index){
		$unix=new unix();
		$umount=$unix->find_program("umount");
		$rm=$unix->find_program("rm");
		$Dir="/home/squid/cache/MemBooster$Index";
		shell_exec("$umount -l $Dir");
		shell_exec("$rm -rf  -l $Dir");
			
	}	
	
	private function CreateDir($directory){
		@mkdir($directory,0755,true);
		@chown($directory, "squid");
		@chgrp($directory, "squid");
		
	}
	
	private function BuildSquidCachesMountInRam($RAM,$Index){
		$unix=new unix();
		$GLOBALS["BuildSquidCachesMountInRam"]=array();
		$mount=$unix->find_program("mount");
		$Dir="/home/squid/cache/MemBooster$Index";
		$this->CreateDir($Dir);
		$this->build_progress("Checking Memory Booster $Index {$RAM}MB mounting...");
		$GLOBALS["BuildSquidCachesMountInRam"][]="# Checking Memory Booster $Index {$RAM}MB mounting...";
		exec("$mount -t tmpfs -o size={$RAM}M tmpfs $Dir 2>&1",$results);
		while (list ($num, $val) = each ($results)){
			$GLOBALS["BuildSquidCachesMountInRam"][]="# $val";
		}
	
	}	
	
	
	private function BuildSquidCachesMountIsMounted($Index){
		$f=file("/proc/mounts");
		while (list ($num, $val) = each ($f)){
			if(preg_match("#^tmpfs.+?\/home\/squid\/cache\/MemBooster$Index\s+.+?size=([0-9]+)([a-zA-Z]+)#", $val,$re)){
				$size=$re[1];
				$unit=strtolower($re[2]);
				if($unit=="k"){return round($size/1024);}
				if($unit=="m"){return $size;}
	
			}
		}
		return 0;
	}	
	
}