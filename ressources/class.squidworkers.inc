<?php


class squidworkers{
	public $ASROOT=false;
	private $SquidBoosterOnly=0;
	private $CPU_NUMBER=0;
	private $IS_32=false;
	private $store_dir_minsize=null;
	private $uuid=null;
	private $EnableRemoteStatisticsAppliance=0;
	private $SquidBoosterMemK;
	private $DisableAnyCache=0;
	private $SquidBoosterMem=0;
	
	private $SquidCachesMount=array();
	public $CPU_FINAL=0;
	
	function squidworkers(){
		if(posix_getuid()==0){$this->ASROOT=true;}
		$users=new usersMenus();
		$sock=new sockets();
		
		$this->CPU_NUMBER=$users->CPU_NUMBER;
		if($this->ASROOT){$this->SQUID_VERSION=$this->root_squid_version();}else{$this->SQUID_VERSION=$users->SQUID_VERSION;}
		if(preg_match("#^3\.2\.#", $this->SQUID_VERSION)){$this->IS_32=true;}
		if(preg_match("#^3\.3\.#", $this->SQUID_VERSION)){$this->IS_32=true;}
		$this->uuid=$sock->getFrameWork("services.php?GetMyHostId=yes");
		$this->SquidCachesMount=unserialize(base64_decode($sock->GET_INFO("SquidCachesMount")));
		$this->FillSettings();
		$this->store_dir_minsize_calc();
	}
	
	private function FillSettings(){
		$sock=new sockets();
		$this->EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
		if(!is_numeric($this->EnableRemoteStatisticsAppliance)){$this->EnableRemoteStatisticsAppliance=0;}
		$this->SquidBoosterMem=$sock->GET_INFO("SquidBoosterMem");
		$this->SquidBoosterMemK=$sock->GET_INFO("SquidBoosterMemK");
		$this->SquidBoosterOnly=$this->zcheck_squidbooster_value();
		$this->DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
		$SquidCacheLevel=$sock->GET_INFO("SquidCacheLevel");
		if(!is_numeric($SquidCacheLevel)){$SquidCacheLevel=4;}

		
		if($this->EnableRemoteStatisticsAppliance==1){
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SMP] Using remote statistics appliance parameters...\n";}
			include_once(dirname(__FILE__)."/class.blackboxes.inc");
			$sock=new blackboxes($this->uuid);
			$this->SquidBoosterMem=$sock->GET_SQUID_INFO("SquidBoosterMem");
			$this->SquidBoosterMemK=$sock->GET_SQUID_INFO("SquidBoosterMemK");
			$this->DisableAnyCache=$sock->GET_SQUID_INFO("DisableAnyCache");	
			
			if($GLOBALS["VERBOSE"]){
				echo "Starting......: ".date("H:i:s")." [SMP] $this->uuid is SquidBoosterMem ? `$this->SquidBoosterMem`\n";
				echo "Starting......: ".date("H:i:s")." [SMP] $this->uuid is SquidBoosterMemK ? `$this->SquidBoosterMemK`\n";
				echo "Starting......: ".date("H:i:s")." [SMP] $this->uuid is DisableAnyCache ? `$this->DisableAnyCache`\n";
				
			}
			
		}	
		
		if(!is_numeric($this->DisableAnyCache)){$this->DisableAnyCache=0;}
		if(!is_numeric($this->SquidBoosterMem)){$this->SquidBoosterMem=0;}
		if(!is_numeric($this->SquidBoosterMemK)){$this->SquidBoosterMemK=50;}
		if($SquidCacheLevel==0){$this->DisableAnyCache=1;}
		$this->SquidBoosterOnly=$this->zcheck_squidbooster_value();
	}
	
	
	private function store_dir_minsize_calc(){
		if($this->DisableAnyCache==1){$this->SquidBoosterMem=0;}
		
		if($this->SquidBoosterMem>0){
			$this->SquidBoosterMemK=$this->SquidBoosterMemK*1024;
			$this->store_dir_minsize="min-size=$this->SquidBoosterMemK";
		}		
	}
	
	private function WorkersDefault(){
		$users=new usersMenus();
		$sock=new sockets();
		$q=new mysql_squid_builder();
		$q->CheckTables();
		$hostname=trim($sock->GET_INFO("myhostname"));
		if($hostname==null){$hostname=$users->fqdn;}
		$CPU=$this->CPU_NUMBER;
		$cachesDirectory="/home/squid/cache/squid2";
		$globalCachesize=5000;
		$sql="INSERT IGNORE INTO cacheconfig (`uuid`,`workers`,`globalCachesize`,`cachesDirectory`,`hostname`)
		VALUES('$this->uuid','$CPU','$globalCachesize','$cachesDirectory','$hostname')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){$sock->getFrameWork("squid.php?rebuild-caches=yes");}
	
	}	
	
	
	private function olders_caches(){
		if($this->DisableAnyCache==1){return;}
		$sock=new sockets();
		$ini=new Bs_IniHandler();
		$ArticaSquidParameters=$sock->GET_INFO('ArticaSquidParameters');
		$ini->loadString($ArticaSquidParameters);
		$cache_list=array();
		if(!is_array($ini->_params)){return null;}
		reset($ini->_params);
		
		$CACHE_SIZE=$ini->_params["CACHE"]["CACHE_SIZE"];
		$CACHE_PATH=$ini->_params["CACHE"]["CACHE_PATH"];
		$CACHE_PATH=str_replace(" ", "_", $CACHE_PATH);
		$CACHE_TYPE=$ini->_params["CACHE"]["CACHE_TYPE"];
		
		if($CACHE_PATH<>null){
			$cache_list[$CACHE_PATH]["cache_type"]=$CACHE_TYPE;
			$cache_list[$CACHE_PATH]["cache_dir_level1"]=16;
			$cache_list[$CACHE_PATH]["cache_dir_level2"]=256;
			$cache_list[$CACHE_PATH]["cache_size"]=$CACHE_SIZE;
			$cache_list[$CACHE_PATH]["cache_maxsize"]=$this->store_dir_minsize;
		}
		
				
		while (list ($num, $val) = each ($ini->_params)){
			if(preg_match('#cache:(.+)#',$num,$re)){
				if($GLOBALS["VERBOSE"]){echo "Found cache: {$re[1]}<br>\n";}
				$re[1]=str_replace(" ", "_", $re[1]);
				$cache_list[$re[1]]=array(
							"cache_type"=>$ini->_params[$num]["cache_type"],
							"cache_dir_level1"=>$ini->_params[$num]["cache_dir_level1"],
							"cache_dir_level2"=>$ini->_params[$num]["cache_dir_level2"],
							"cache_size"=>$ini->_params[$num]["cache_size"],
							"cache_maxsize"=>$ini->_params[$num]["cache_maxsize"],
					);
			}
		}
		if(count($cache_list)==0){return;}
		
		while (list ($num, $val) = each ($cache_list)){
			if(trim($num)==null){continue;}
			$Directory=$num;
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SMP] CPU[1]: \"{$Directory}\" ({$cache_list[$num]["cache_type"]})\n";}
		
			if(preg_match("#^\/dev\/#", $Directory)){
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SMP] CPU[1]: \"$Directory\" Invalid cache path...!!\n";}
				continue;
			}
		
			if($cache_list[$num]["cache_type"]=="rock"){
				$mxsize=$this->cache_list[$num]["cache_maxsize"]*1000;
				$mxsize=$mxsize*1024;
				//$conf=$conf."cache_dir {$this->cache_list[$num]["cache_type"]} $num {$this->cache_list[$num]["cache_size"]} max-size=32768\n";
				continue;
			}
			
			
			$num=str_replace(" ", "_", $num);
			if($cache_list[$num]["cache_type"]=="ufs"){$cache_list[$num]["cache_type"]="aufs";}
			if(is_link($Directory)){$Directory=readlink($Directory);}
			if(!is_dir($Directory)){
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SMP] CPU[1]: \"$Directory\" ( No such directory )\n";}
				continue;
			}
			$conf[]="cache_dir {$cache_list[$num]["cache_type"]} $Directory {$cache_list[$num]["cache_size"]} {$cache_list[$num]["cache_dir_level1"]} {$cache_list[$num]["cache_dir_level2"]} $this->store_dir_minsize \n";
		}		
				
		return @implode("\n", $conf);
	}
	
	
	public function Workers($maincachedir=null){
		if(!$this->IS_32){return;}
		if(!isset($GLOBALS["NO_KILL_MYSQL"])){$GLOBALS["NO_KILL_MYSQL"]=false;}
		$sock=new sockets();
		
		$this->SquidBoosterOnly=$this->zcheck_squidbooster_value();
		echo "Starting......: ".date("H:i:s")." [SMP] Use only Booster:$this->SquidBoosterOnly\n";
		
		
		
		if(!function_exists("mysql_connect")){return;}
		
		$q=new mysql_squid_builder();
		if(!$q->BD_CONNECT(true)){
			if($this->ASROOT){
				echo "Starting......: ".date("H:i:s")." [SMP] MySQL database engine not ready, die()...\n";
				if(!$GLOBALS["NO_KILL_MYSQL"]){die();}
				return;
			}
		}
		echo "Starting......: ".date("H:i:s")." [SMP] Query MySQL Engine...for $this->uuid\n";
		$sql="SELECT uuid FROM cacheconfig WHERE `uuid`='$this->uuid'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if($ligne["uuid"]==null){$this->WorkersDefault();}
		$sql="SELECT * FROM cacheconfig WHERE `uuid`='$this->uuid'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$CPUS=$ligne["workers"];

		
	
		echo "Starting......: ".date("H:i:s")." [SMP] $CPUS workers\n";
		
		
		if($this->ASROOT){
			$unix=new unix();
			echo "Starting......: ".date("H:i:s")." [SMP] launch exec.squid.smp.php\n";
			system($unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.squid.smp.php");
		}
	
		$cachesDirectory=$ligne["cachesDirectory"];
		if($cachesDirectory==null){$cachesDirectory="/home/squid";}
		if(preg_match("#^\/var\/#", $cachesDirectory)){
			$cachesDirectory=str_replace("/var/", "/home/squid/", $cachesDirectory);
		}
		
		$CurrentDirectorySizeAvailable=$unix->DIRECTORY_FREEM($cachesDirectory);
		echo "Starting......: ".date("H:i:s")." [SMP]: $cachesDirectory = {$CurrentDirectorySizeAvailable}M available\n";
		
		$globalCachesize=$ligne["globalCachesize"];
		if(!is_numeric($globalCachesize)){$globalCachesize=5000;}
		
		
		
		
		if(!is_numeric($CPUS)){$CPUS=$this->CPU_NUMBER();}
		$boost=new squidbooster();
		
		echo "Starting......: ".date("H:i:s")." [SMP] is enabled checking new SMP mode feature...\n";
		$newworkers=$this->caches_mysql();
		if($newworkers<>null){return $newworkers;}
		$f[]="workers $CPUS";
		$olders_caches=$this->olders_caches();
		for($i=1;$i<$CPUS+1;$i++){
			$f[]="if \${process_number} = $i";
				$this->CPU_FINAL++;
				$boost_cache=$boost->cache_booster_SMP($i);
				if($boost_cache<>null){$f[]=$boost_cache;}
				if($this->SquidBoosterOnly==0){
					if($i==1){if($olders_caches<>null){$f[]=$olders_caches;}}
					$CurrentDirectorySizeAvailable=$CurrentDirectorySizeAvailable-$globalCachesize;
					if($CurrentDirectorySizeAvailable>5){
						$f[]="cache_dir aufs $cachesDirectory-$i $globalCachesize 128 512 $this->store_dir_minsize";
					}else{
						echo "Starting......: ".date("H:i:s")." [SMP] CPU[$i]: No space left on $cachesDirectory\n";
					}
				}
			$f[]="endif";
		
		}
		$f[]="#------------------";
		return @implode("\n", $f);
	}

	public function zcheck_squidbooster_value(){
		if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
		
	
		if($GLOBALS["VERBOSE"]){
			echo "Starting......: ".date("H:i:s")." [SMP] Use only \$SquidBoosterOnly:$this->SquidBoosterOnly\n";
		}
		if(!is_numeric($this->SquidBoosterOnly)){$this->SquidBoosterOnly=0;}
		if(!is_numeric($this->SquidBoosterMem)){$this->SquidBoosterMem=0;}
		if($this->SquidBoosterMem==0){$this->SquidBoosterOnly=0;}
		$GLOBALS[__FUNCTION__]=$this->SquidBoosterOnly;
		return $this->SquidBoosterOnly;
	}

	private function root_squid_version(){
		if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
		$unix=new unix();
		$squidbin=$unix->find_program("squid");
		if($squidbin==null){$squidbin=$unix->find_program("squid3");}
		if(!is_file($squidbin)){return;}
		exec("$squidbin -v 2>&1",$results);
		while (list ($num, $val) = each ($results)){
			if(preg_match("#Squid Cache: Version.*?([0-9\.]+)#", $val,$re)){
				if($re[1]=="2.7."){$re[1]="2.7.0";}
				if($re[1]=="3.0."){$re[1]="3.0.0";}
				if($re[1]=="3.1."){$re[1]="3.1.0";}
				if($re[1]=="3.2."){$re[1]="3.2.0";}
				echo "Starting......: ".date("H:i:s")." Squid : Version (as root) '{$re[1]}'\n";
				$GLOBALS[__FUNCTION__]=$re[1];
				return $re[1];
			}
		}
	
		$version=$unix->CACHE_VERSIONS("APP_SQUID");
		if($version<>null){$GLOBALS[__FUNCTION__]=$version;return $version;}
		
		
	}
	
	private function CountDeCaches($cpuindex,$uuid){
		$count=0;
		
		$CacheMount=$this->BuildSquidCachesMount($cpuindex);
		echo "Starting......: ".date("H:i:s")." [SMP] CPU[$cpuindex] : Memory Cache = `$CacheMount`\n";
		if($CacheMount<>null){$count++;}
		
		if($this->DisableAnyCache==0){
			$q=new mysql();
			$boost=new squidbooster();
			$boost_cache=$boost->cache_booster_SMP($cpuindex);
			if($boost_cache<>null){$count++;}
			if($this->SquidBoosterOnly==0){
				$results=$q->QUERY_SQL("SELECT * FROM squid_caches32 WHERE enabled=1 
					AND uuid='$uuid' AND cpunum=$cpuindex AND ToDelete=0 AND Building=2","artica_backup");
				if(mysql_num_rows($results)>0){$count++;}
			}
			
		}
		
		return $count;	
	}

	
	private function caches_mysql(){
		$unix=new unix();
		$uuid=$unix->GetUniqueID();
		
		$q=new mysql();
		$results=$q->QUERY_SQL("SELECT * FROM squid_caches32 WHERE enabled=1 AND uuid='$uuid' AND ToDelete=0 AND Building=2","artica_backup");
		$CPU_NUMBER=$this->CPU_NUMBER();
		echo "Starting......: ".date("H:i:s")." [SMP] CPU(s) = $CPU_NUMBER\n";
		
		$f[]="workers $CPU_NUMBER";
		$boost=new squidbooster();
		$WORKCOUNT=0;
		
		for($i=1;$i<$CPU_NUMBER+1;$i++){
			$CountDeCaches=$this->CountDeCaches($i,$uuid);
			echo "Starting......: ".date("H:i:s")." [SMP] CPU[$i] = CPU[$i] Caches = `$CountDeCaches`\n";
			if($CountDeCaches==0){continue;}
			$f[]="if \${process_number} = $i";
			$this->CPU_FINAL++;
			
			$CacheMount=$this->BuildSquidCachesMount($i);
			echo "Starting......: ".date("H:i:s")." [SMP] CPU[$i] = Cache mounted `$CacheMount`\n";
			if($CacheMount<>null){
				$WORKCOUNT++;
				$f[]=$CacheMount;
			}
			
			if($this->DisableAnyCache==0){
				$boost_cache=$boost->cache_booster_SMP($i);
				if($boost_cache<>null){$f[]=$boost_cache;}
				if($this->SquidBoosterOnly==1){continue;}
				$results=$q->QUERY_SQL("SELECT * FROM squid_caches32 WHERE enabled=1 AND uuid='$uuid' AND cpunum=$i AND ToDelete=0 AND Building=2","artica_backup");
				if(!$q->ok){echo "Starting......: ".date("H:i:s")." [SMP] CPU Number $i `$q->mysql_error`\n";continue;}
			
				if(mysql_num_rows($results)==0){
					echo "Starting......: ".date("H:i:s")." [SMP] CPU Number $i No cache saved, this CPU run wihout caching\n";
				}else{
					echo "Starting......: ".date("H:i:s")." [SMP] CPU Number $i `". mysql_num_rows($results)." caches`\n";
				}
				if(mysql_num_rows($results)>0){
					while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
						$cache_maxsizeT=null;
						$cache_directory=$ligne["cache_directory"];
						$cache_directory_calc=$ligne["cache_directory"];
						if(is_link($cache_directory_calc)){$cache_directory_calc=readlink($cache_directory_calc);}
						if(preg_match("#^\/var\/#", $cache_directory)){$cache_directory=str_replace("/var/", "/home/squid/", $cache_directory);}
						if(preg_match("#^\/var\/#", $cache_directory_calc)){$cache_directory_calc=str_replace("/var/", "/home/squid/", $cache_directory_calc);}
						
						if(isset($sizeAvailble[$cache_directory_calc])){
							$sizeAvailble[$cache_directory_calc]=$unix->DIRECTORY_FREEM($cache_directory_calc);
						}
						
						
						echo "Starting......: ".date("H:i:s")." [SMP] CPU Number $i cache dir:`$cache_directory`\n";
						if(!$this->ifValidCache($cache_directory)){echo "Starting......: ".date("H:i:s")." [SMP] CPU Number $i invalid cache path `$cache_directory`\n";continue;}
						$cache_directory=$ligne["cache_directory"];
						$cache_size=$ligne["size"]*1000;
						
						if($cache_size>$sizeAvailble[$cache_directory_calc]){
							echo "Starting......: ".date("H:i:s")." [SMP] CPU Number $i cache dir:`$cache_directory` no space left {$sizeAvailble[$cache_directory_calc]}\n";
							continue;
						}
						$sizeAvailble[$cache_directory_calc]=$sizeAvailble[$cache_directory_calc]-$cache_size;
						
						$cache_dir_level1=$ligne["cache_dir_level1"];
						$cache_dir_level2=$ligne["cache_dir_level2"];
						$cache_maxsize=$ligne["cache_maxsize"];
						$cache_type=$ligne["cache_type"];	

					
					
						if($cache_maxsize>0){
							$cache_maxsize=$cache_maxsize*1024;
							$cache_maxsizeT=" max-size=$cache_maxsize";
						}
					
						if(!is_numeric($cache_dir_level1)){$cache_dir_level1=16;}
						if(!is_numeric($cache_dir_level2)){$cache_dir_level2=256;}
						
						if($cache_type=="ufs"){$cache_type="aufs";}
						$f[]="cache_dir $cache_type $cache_directory $cache_size $cache_dir_level1 $cache_dir_level2 $cache_maxsizeT";
						$WORKCOUNT++;
					}
				}
			}
			
			
			
			$f[]="endif";
		
		}
		$f[]="#------------------";	
		if($WORKCOUNT>0){	
			return @implode("\n", $f);
		}
		
		
	}
	
	public function ifValidCache($directory){
		$unix=new unix();
		$directory=trim($directory);
		if($directory==null){return false;}
		if(preg_match("#^\/dev\/#", $directory)){return false;}
		$subdir=basename($directory);
		$MainDir=dirname($directory);
		$mountPoint=$unix->isDirInFsTab($MainDir);
		if($mountPoint==null){return true;}
		if(!$unix->alt_stat2($mountPoint)){return false;}
		return true;
		
	}
	
	private function BuildSquidCachesMount($Index){
		if(isset($GLOBALS["BuildSquidCachesMount"][$Index])){return $GLOBALS["BuildSquidCachesMount"][$Index];}
		
		
		if(!isset($this->SquidCachesMount[$Index])){
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SMP] CPU[$Index]: Memory cache: no array()\n";}
			return null;
		}
		$mountedram=intval($this->SquidCachesMount[$Index]);
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SMP] CPU[$Index] : Memory cache: ={$mountedram}M\n";}
		if(!is_numeric($mountedram)){return null;}
		if($mountedram<0){$mountedram=0;}
		
		if($mountedram==0){
			if($this->BuildSquidCachesMountIsMounted($Index)>0){
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SMP] CPU[$Index] : Memory cache umounting..\n";}
				$this->BuildSquidCachesMountUmount($Index);
			}
			return null;
		}
		
		$Dir="/home/squid/cache/MemBooster$Index";
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SMP] CPU[$Index] : Memory cache on `$Dir`\n";}
		
		$AlreadyMounted=$this->BuildSquidCachesMountIsMounted($Index);
		if($AlreadyMounted==0){$this->BuildSquidCachesMountInRam($mountedram,$Index);}
		$Diff=intval($mountedram-$AlreadyMounted);
		if(preg_match("#^-([0-9]+)#", $Diff,$re)){$Diff=intval($re[1]);}
		if($Diff>5){
			$this->BuildSquidCachesMountUmount($Index);
			$this->BuildSquidCachesMountInRam($mountedram,$Index);
		}
		
		
		$MemDIFF=$mountedram-10;
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SMP] CPU[$Index] : Return a cache of {$MemDIFF}M\n";}
		$GLOBALS["BuildSquidCachesMount"][$Index]="cache_dir aufs $Dir $MemDIFF 16 256";
		return "cache_dir aufs $Dir $MemDIFF 16 256";		
		
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
		
		private function BuildSquidCachesMountInRam($RAM,$Index){
			$unix=new unix();
			$mount=$unix->find_program("mount");
			$Dir="/home/squid/cache/MemBooster$Index";
			
			@mkdir($Dir,0755,true);
			@chown($Dir, "squid");
			@chgrp($Dir, "squid");
			shell_exec("$mount -t tmpfs -o size={$RAM}M tmpfs $Dir >/dev/null 2>&1");
		
		}		
	
		private function  BuildSquidCachesMountUmount($Index){
			$unix=new unix();
			$umount=$unix->find_program("umount");
			$rm=$unix->find_program("rm");
			$Dir="/home/squid/cache/MemBooster$Index";
			shell_exec("$umount -l $Dir");
			shell_exec("$rm -rf  -l $Dir");
			
		}
		
	
	private function CPU_NUMBER(){
		$unix=new unix();
		$cat=$unix->find_program("cat");
		$grep=$unix->find_program("grep");
		$cut=$unix->find_program("cut");
		$wc=$unix->find_program("wc");
		$cmd="$cat /proc/cpuinfo |$grep \"model name\" |$cut -d: -f2|$wc -l 2>&1";
		$CPUNUM=exec($cmd);
		return $CPUNUM;
	}	
	
	
}