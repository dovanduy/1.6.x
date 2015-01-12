<?php

class squidbooster{
	public $ASROOT=false;
	private $SquidBoosterMem=0;
	private $SquidBoosterMemK=0;
	private $SquidBoosterEnable=0;
	private $CPU_NUMBER=0;
	private $EnableRemoteStatisticsAppliance=0;
	private $uuid=null;
	private $DisableAnyCache=0;
	private $SquidBoosterOnly=0;
	
	public function squidbooster(){
		if(posix_getuid()==0){$this->ASROOT=true;}
		
	}
	

	
	
	private function FillSettings(){
		$sock=new sockets();
		$this->uuid=$sock->getFrameWork("services.php?GetMyHostId=yes");
		$this->EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
		if(!is_numeric($this->EnableRemoteStatisticsAppliance)){$this->EnableRemoteStatisticsAppliance=0;}
		$this->SquidBoosterMem=intval($sock->GET_INFO("SquidBoosterMem"));
		$this->SquidBoosterEnable=intval($sock->GET_INFO("SquidBoosterEnable"));
		$this->DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
		$SquidCacheLevel=$sock->GET_INFO("SquidCacheLevel");
		if(!is_numeric($SquidCacheLevel)){$SquidCacheLevel=4;}

	
		if(!is_numeric($this->DisableAnyCache)){$this->DisableAnyCache=0;}
		if(!is_numeric($this->SquidBoosterMem)){$this->SquidBoosterMem=0;}
		if(!is_numeric($this->SquidBoosterMemK)){$this->SquidBoosterMemK=50;}
		if($SquidCacheLevel==0){$this->DisableAnyCache=1;}
		
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
	
	
	private function cache_booster_mount($RAM){
		$unix=new unix();
		$mount=$unix->find_program("mount");
		@mkdir("/var/squid/cache_booster",0755,true);
		@chown("/var/squid/cache_booster", "squid");
		@chgrp("/var/squid/cache_booster", "squid");
		shell_exec("$mount -t tmpfs -o size={$RAM}M tmpfs /var/squid/cache_booster >/dev/null 2>&1");
	
	}
	
	private function cache_booster_umount(){
		if($this->cache_booster_ram()==0){return;}
		$unix=new unix();
		$umount=$unix->find_program("umount");
		echo "Starting......: ".date("H:i:s")." Cache Booster:: dismount /var/squid/cache_booster\n";
		shell_exec("$umount -l /var/squid/cache_booster >/dev/null 2>&1");
	
	}

	public function cache_booster(){
		
		if(!$this->ASROOT){return;}
		$this->FillSettings();
		if($this->SquidBoosterEnable==0){$this->cache_booster_umount();return null;}

		if($this->SquidBoosterMem<15){
			echo "Starting......: ".date("H:i:s")." Cache Booster:: {$this->SquidBoosterMem}M < 15, aborting...\n";
			$this->cache_booster_umount();
			return null;
		}
	
		$mountedram=$this->cache_booster_ram();
		echo "Starting......: ".date("H:i:s")." Cache Booster:: Need to boost for {$this->SquidBoosterMem}M - 10M, current {$mountedram}M\n";
		if($this->SquidBoosterMem<>$mountedram){
			$this->cache_booster_umount();
			$this->cache_booster_mount($this->SquidBoosterMem);
		}
	
		$mountedram=$this->cache_booster_ram();
		echo "Starting......: ".date("H:i:s")." Cache Booster:: current {$mountedram}M\n";
		if($mountedram==0){return null;}
		$maxsize=" max-size=1000K";
		$SquidBoosterMemDIFF=$mountedram-10;
		@mkdir("/var/squid/cache_booster",0755,true);
		@chown("/var/squid/cache_booster","squid");
		@chgrp('/var/squid/cache_booster',"squid");
		return "cache_dir aufs /var/squid/cache_booster $SquidBoosterMemDIFF 16 256 max-size=1000K";
	}	
	
}