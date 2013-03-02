<?php

class squidbooster{
	public $ASROOT=false;
	private $DisableSquidSNMPMode=0;
	private $SquidBoosterMem=0;
	private $SquidBoosterMemK=0;
	private $CPU_NUMBER=0;
	private $EnableRemoteStatisticsAppliance=0;
	private $uuid=null;
	private $DisableAnyCache=0;
	private $SquidBoosterOnly=0;
	
	public function squidbooster(){
		if(posix_getuid()==0){$this->ASROOT=true;}
		
	}
	
	
	private function CPU_NUMBERUX(){
		$unix=new unix();
		$cat=$unix->find_program("cat");
		$grep=$unix->find_program("grep");
		$cut=$unix->find_program("cut");
		$wc=$unix->find_program("wc");
		$cmd="$cat /proc/cpuinfo |$grep \"model name\" |$cut -d: -f2|$wc -l 2>&1";
		$CPUNUM=exec($cmd);
		return $CPUNUM;
	}	
	
	
	private function FillSettings(){
		$sock=new sockets();
		$this->uuid=$sock->getFrameWork("services.php?GetMyHostId=yes");
		$this->EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
		if(!is_numeric($this->EnableRemoteStatisticsAppliance)){$this->EnableRemoteStatisticsAppliance=0;}
		$this->SquidBoosterMem=$sock->GET_INFO("SquidBoosterMem");
		$this->SquidBoosterMemK=$sock->GET_INFO("SquidBoosterMemK");
		$this->SquidBoosterOnly=$this->zcheck_squidbooster_value();
		$this->DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
		$this->DisableSquidSNMPMode=$sock->GET_INFO("DisableSquidSNMPMode");
		
		$q=new mysql_squid_builder();
		$sql="SELECT workers FROM cacheconfig WHERE `uuid`='$this->uuid'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		
		$this->CPU_NUMBER=$ligne["workers"];
		if($this->CPU_NUMBER==0){$this->CPU_NUMBER=$this->CPU_NUMBERUX();}
		if(!is_numeric($this->CPU_NUMBER)){$this->CPU_NUMBER=$this->CPU_NUMBERUX();}
		
	
	
		if($this->EnableRemoteStatisticsAppliance==1){
			if($this->ASROOT){echo "Starting......: [BOOST] Using remote statistics appliance parameters...\n";}
			include_once(dirname(__FILE__)."/class.blackboxes.inc");
			$sock=new blackboxes($this->uuid);
			$this->SquidBoosterMem=$sock->GET_SQUID_INFO("SquidBoosterMem");
			$this->SquidBoosterMemK=$sock->GET_SQUID_INFO("SquidBoosterMemK");
			$this->DisableAnyCache=$sock->GET_SQUID_INFO("DisableAnyCache");
			$this->DisableSquidSNMPMode=$sock->GET_SQUID_INFO("DisableSquidSNMPMode");
				
		}
	
		if(!is_numeric($this->DisableAnyCache)){$this->DisableAnyCache=0;}
		if(!is_numeric($this->DisableSquidSNMPMode)){$this->DisableSquidSNMPMode=1;}
		if(!is_numeric($this->SquidBoosterMem)){$this->SquidBoosterMem=0;}
		if(!is_numeric($this->SquidBoosterMemK)){$this->SquidBoosterMemK=50;}
		
	}	
	
	
	
	private function cache_booster_ram(){
		$f=file("/proc/mounts");
		while (list ($num, $val) = each ($f)){
			if(preg_match("#^tmpfs.+?\/var\/squid\/cache_booster\s+.+?size=([0-9]+)([a-zA-Z]+)#", $val,$re)){
				$size=$re[1];
				$unit=strtolower($re[2]);
				if($unit=="k"){return round($size/1024);}
				if($unit=="m"){return $size;}
	
			}
		}
		return 0;
	}
	private function cache_booster_ram_SMP($procnum){
		$f=file("/proc/mounts");
		while (list ($num, $val) = each ($f)){
			if(preg_match("#^tmpfs.+?\/var\/squid\/cache_booster-$procnum.+?size=([0-9]+)([a-zA-Z]+)#", $val,$re)){
				$size=$re[1];
				$unit=strtolower($re[2]);
				if($unit=="k"){return round($size/1024);}
				if($unit=="m"){return $size;}
	
			}
		}
		return 0;
	}	
	
	private function cache_booster_mount($RAM){
		$unix=new unix();
		$mount=$unix->find_program("mount");
		@mkdir("/var/squid/cache_booster",0755,true);
		@chown("/var/squid/cache_booster", "squid");
		@chgrp("/var/squid/cache_booster", "squid");
		shell_exec("$mount -t tmpfs -o size={$RAM}M tmpfs /var/squid/cache_booster >/dev/null 2>&1");
	
	}
	private function cache_booster_mount_SMP($procnum,$RAM){
		$unix=new unix();
		$mount=$unix->find_program("mount");
		@mkdir("/var/squid/cache_booster-$procnum",0755,true);
		@chown("/var/squid/cache_booster-$procnum", "squid");
		@chgrp("/var/squid/cache_booster-$procnum", "squid");
		shell_exec("$mount -t tmpfs -o size={$RAM}M tmpfs /var/squid/cache_booster-$procnum >/dev/null 2>&1");
	
	}
	
	private function cache_booster_umount(){
		$unix=new unix();
		$umount=$unix->find_program("umount");
		echo "Starting......: Cache Booster:: dismount /var/squid/cache_booster\n";
		shell_exec("$umount -l /var/squid/cache_booster >/dev/null 2>&1");
	
	}
	private function cache_booster_umount_SMP($procnum){
		$unix=new unix();
		$umount=$unix->find_program("umount");
		echo "Starting......: Cache Booster[$procnum]:: dismount /var/squid/cache_booster-$procnum\n";
		shell_exec("$umount -l /var/squid/cache_booster-$procnum >/dev/null 2>&1");
	
	}	
	
	public function cache_booster_SMP($procnum){
		if(!$this->ASROOT){return;}
		if($this->DisableSquidSNMPMode==1){return;}
		if($this->CPU_NUMBER==0){$this->CPU_NUMBER=$this->CPU_NUMBERUX();}
		$minmalvalue=10*$this->CPU_NUMBER;
		if($this->SquidBoosterMem<$minmalvalue){echo "Starting......: Cache Booster[$procnum]:: {$this->SquidBoosterMem}M < $minmalvalue, aborting...\n";return;}
		
		echo "Starting......: Cache Booster[$procnum]:: $this->SquidBoosterMem CPUs:$this->CPU_NUMBER\n";
		
		$MemPerCPU=round($this->SquidBoosterMem/$this->CPU_NUMBER)-10;
		if($MemPerCPU<10){return;}
		$mountedram=$this->cache_booster_ram_SMP($procnum);
		echo "Starting......: Cache Booster[$procnum]:: Need to boost for {$MemPerCPU}M, current {$mountedram}M\n";
		if($MemPerCPU<>$mountedram){
			$this->cache_booster_umount_SMP($procnum);
			$this->cache_booster_mount_SMP($procnum,$MemPerCPU);
		}
		$mountedram=$this->cache_booster_ram_SMP($procnum);
		echo "Starting......: Cache Booster[$procnum]:: current {$mountedram}M\n";
		if($mountedram==0){return null;}		
		$SquidBoosterMemK=$MemPerCPU*1024;
		$SquidBoosterMemDIFF=$mountedram-10;
		return "cache_dir diskd /var/squid/cache_booster-$procnum $SquidBoosterMemDIFF 16 256 max-size=$SquidBoosterMemK";
	}
	
	

	public function cache_booster(){
		if(!$this->ASROOT){return;}

		if($this->DisableSquidSNMPMode==0){
			echo "Starting......: Cache Booster:: SMP Mode, using booster for each process...\n";
			$this->cache_booster_umount();
			return;
		}

		if($this->SquidBoosterMem<15){
			echo "Starting......: Cache Booster:: {$this->SquidBoosterMem}M < 15, aborting...\n";
			$this->cache_booster_umount();
			return null;
		}
	
		$mountedram=$this->cache_booster_ram();
		echo "Starting......: Cache Booster:: Need to boost for {$this->SquidBoosterMem}M - 10M, current {$mountedram}M\n";
		if($this->SquidBoosterMem<>$mountedram){
			$this->cache_booster_umount();
			$this->cache_booster_mount($this->SquidBoosterMem);
		}
	
		$mountedram=$this->cache_booster_ram();
		echo "Starting......: Cache Booster:: current {$mountedram}M\n";
		if($mountedram==0){return null;}
		$this->SquidBoosterMemK=$this->SquidBoosterMemK*1024;
		$maxsize=" max-size=$this->SquidBoosterMemK";
		$SquidBoosterMemDIFF=$mountedram-10;
	
		return "cache_dir aufs /var/squid/cache_booster $SquidBoosterMemDIFF 16 256$maxsize";
		if($this->IS_32){
		return "cache_dir rock /var/squid/cache_booster $SquidBoosterMemDIFF max-size=$this->SquidBoosterMemK";
		}else{
		return "cache_dir diskd /var/squid/cache_booster $SquidBoosterMemDIFF 16 256 max-size=$this->SquidBoosterMemK";
		}
	
	
		}	
	
}