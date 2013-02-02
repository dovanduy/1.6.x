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
	private $DisableSquidSNMPMode=1;
	
	function squidworkers(){
		if(posix_getuid()==0){$this->ASROOT=true;}
		$users=new usersMenus();
		$sock=new sockets();
		
		$this->CPU_NUMBER=$users->CPU_NUMBER;
		if($this->ASROOT){$this->SQUID_VERSION=$this->root_squid_version();}else{$this->SQUID_VERSION=$users->SQUID_VERSION;}
		if(preg_match("#^3\.2\.#", $this->SQUID_VERSION)){$this->IS_32=true;}
		if(preg_match("#^3\.3\.#", $this->SQUID_VERSION)){$this->IS_32=true;}
		$this->uuid=$sock->getFrameWork("services.php?GetMyHostId=yes");
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
		$this->DisableSquidSNMPMode=$sock->GET_INFO("DisableSquidSNMPMode");
		
		if($this->EnableRemoteStatisticsAppliance==1){
			if($this->ASROOT){echo "Starting......: [SMP] Using remote statistics appliance parameters...\n";}
			include_once(dirname(__FILE__)."/class.blackboxes.inc");
			$sock=new blackboxes($this->uuid);
			$this->SquidBoosterMem=$sock->GET_SQUID_INFO("SquidBoosterMem");
			$this->SquidBoosterMemK=$sock->GET_SQUID_INFO("SquidBoosterMemK");
			$this->DisableAnyCache=$sock->GET_SQUID_INFO("DisableAnyCache");	
			$this->DisableSquidSNMPMode=$sock->GET_SQUID_INFO("DisableSquidSNMPMode");
			if($GLOBALS["VERBOSE"]){
				echo "Starting......: [SMP] $this->uuid is SquidBoosterMem ? `$this->SquidBoosterMem`\n";
				echo "Starting......: [SMP] $this->uuid is SquidBoosterMemK ? `$this->SquidBoosterMemK`\n";
				echo "Starting......: [SMP] $this->uuid is DisableAnyCache ? `$this->DisableAnyCache`\n";
				echo "Starting......: [SMP] $this->uuid is DisableSquidSNMPMode ? `$this->DisableSquidSNMPMode`\n";
			}
			
		}	
		
		if(!is_numeric($this->DisableAnyCache)){$this->DisableAnyCache=0;}
		if(!is_numeric($this->DisableSquidSNMPMode)){$this->DisableSquidSNMPMode=1;}
		if(!is_numeric($this->SquidBoosterMem)){$this->SquidBoosterMem=0;}
		if(!is_numeric($this->SquidBoosterMemK)){$this->SquidBoosterMemK=50;}	
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
		$cachesDirectory="/var/cache/squid2";
		$globalCachesize=5000;
		$sql="INSERT IGNORE INTO cacheconfig (`uuid`,`workers`,`globalCachesize`,`cachesDirectory`,`hostname`)
		VALUES('$this->uuid','$CPU','$globalCachesize','$cachesDirectory','$hostname')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){$sock->getFrameWork("squid.php?rebuild-caches=yes");}
	
	}	
	
	
	public function Workers($maincachedir=null){
		if(!$this->IS_32){return;}
		$sock=new sockets();
		
		$this->SquidBoosterOnly=$this->zcheck_squidbooster_value();
		echo "Starting......: [SMP] Use only Booster:$this->SquidBoosterOnly\n";
		
	
		$q=new mysql_squid_builder();
		$sql="SELECT uuid FROM cacheconfig WHERE `uuid`='$this->uuid'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if($ligne["uuid"]==null){$this->WorkersDefault();}
		$sql="SELECT * FROM cacheconfig WHERE `uuid`='$this->uuid'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$CPUS=$ligne["workers"];

		
	
		echo "Starting......: [SMP] $CPUS workers\n";
		if($this->ASROOT){
			$unix=new unix();
			echo "Starting......: [SMP] launch exec.squid.smp.php\n";
			system($unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.squid.smp.php");
		}
	
		$cachesDirectory=$ligne["cachesDirectory"];
		$globalCachesize=$ligne["globalCachesize"];
		if(!is_numeric($globalCachesize)){$globalCachesize=5000;}
		if($cachesDirectory==null){$cachesDirectory="/var/cache";}
		if(!is_numeric($CPUS)){$CPUS=1;}
		if($CPUS==0){$CPUS=1;}
		if($CPUS==1){$this->DisableSquidSNMPMode=1;}
		$boost=new squidbooster();
		echo "Starting......: [SMP] is disabled ? `$this->DisableSquidSNMPMode`\n";
		if($this->DisableSquidSNMPMode==1){
			$f[]="#--------- Multiple cpus -- (disabled) is default cache";
			$f[]="workers 1";
			if($maincachedir<>null){
				$f[]=$maincachedir;
			}
			return @implode("\n", $f);
		}
	
	
		$f[]="#--------- Multiple cpus -- (enabled)";
		$f[]="workers $CPUS";
		for($i=1;$i<$CPUS+1;$i++){
			$f[]="if \${process_number} = $i";
				$boost_cache=$boost->cache_booster_SMP($i);
				if($boost_cache<>null){$f[]=$boost_cache;}
				if($this->SquidBoosterOnly==0){$f[]="cache_dir aufs $cachesDirectory-$i $globalCachesize 128 512 $this->store_dir_minsize";}
			$f[]="endif";
		
		}
		$f[]="#------------------";
		return @implode("\n", $f);
	}

	public function zcheck_squidbooster_value(){
		if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
		
	
		if($GLOBALS["VERBOSE"]){
			echo "Starting......: [SMP] Use only \$SquidBoosterOnly:$this->SquidBoosterOnly\n";
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
		exec("$squidbin -v 2>&1",$results);
		while (list ($num, $val) = each ($results)){
			if(preg_match("#Squid Cache: Version.*?([0-9\.]+)#", $val,$re)){
				if($re[1]=="2.7."){$re[1]="2.7.0";}
				if($re[1]=="3.0."){$re[1]="3.0.0";}
				if($re[1]=="3.1."){$re[1]="3.1.0";}
				if($re[1]=="3.2."){$re[1]="3.2.0";}
				echo "Starting......: Squid : Version (as root) '{$re[1]}'\n";
				$GLOBALS[__FUNCTION__]=$re[1];
				return $re[1];
			}
		}
	
		$version=$unix->CACHE_VERSIONS("APP_SQUID");
		if($version<>null){$GLOBALS[__FUNCTION__]=$version;return $version;}
		echo "Warning !!!!!! cannot find version in $squidbin ! !!\n";
	}	
	
	
}