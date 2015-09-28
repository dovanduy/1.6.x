<?php
include_once(dirname(__FILE__)."/class.settings.inc");
// Intel(R) Celeron(R) CPU  N2807  		@ 1.58GHz 	bogomips: 3166.52
// Intel(R) Atom(TM) CPU  230   		@ 1.60GHz	bogomips: 3191.99
// Atom N270 							@ 1.6GHZ 	bogomips: 3192.00

// Intel(R) Core(TM) i3 CPUM 330  		@ 2.13GHz	bogomips: 4256.47
// Intel(R) Core(TM) i3-3110M CPU 		@ 2.40GHz	bogomips:19157.00
// Intel(R) Core(TM) i7-3632QM CPU 		@ 2.20GHz	bogomips: 4389.82

// Intel(R) Xeon(R) CPU E5-2450 v2 		@ 2.50GHz	bogomips: 5000.00
// Intel(R) Core(TM) 2 Duo CPU E7200  	@ 2.53GHz 	bogomips: 5054.00
// Intel(R) Core(TM) i5 CPU M 560  		@ 2.67GHz 	bogomips: 5319.67
// Intel(R) Core(TM) i7-3840QM CPU 		@ 2.80GHz 	bogomips: 5581.95
// Intel(R) Pentium(R) 4 CPU 			@ 3.20GHz	bogomips: 6401.52
// Intel(R) Xeon(R) CPU E3-1245 V2 		@ 3.40GHz 	bogomips: 6820.26
// Intel(R) Core(TM) i3-4160 CPU 		@ 3.60GHz	bogomips: 7199.72





class status_hardware{
	
	private $cache_path="/usr/share/artica-postfix/ressources/interface-cache";
	
	
	function status_hardware(){
		if(!isset($GLOBALS["CLASS_UNIX"])){
			$GLOBALS["CLASS_SOCKETS"]=new sockets();
			$GLOBALS["CLASS_USERS"]=new settings_inc();
			$GLOBALS["CLASS_UNIX"]=new unix();
		}
		
			$this->Scan_processor();
	}
		
	
	
	private function Scan_processor(){
		
		
		if(is_file($this->cache_path."/processor_type")){
			$time=$GLOBALS["CLASS_UNIX"]->file_time_min($this->cache_path."/processor_type");
			if($time<480){return;}
			@unlink($this->cache_path."/processor_type");
		}
		$bogomips=0;
		$processor=array();
		$model=null;
		$f=explode("\n",@file_get_contents("/proc/cpuinfo"));
		
		while (list ($num, $line) = each ($f)){
			if($model==null){
				if(preg_match("#model name\s+:\s+(.+)#", $line,$re)){
					$model=$re[1];
					continue;
				}
			}
			if($bogomips==0){
				if(preg_match("#bogomips\s+:\s+(.+)#", $line,$re)){
					$bogomips=$re[1];
					continue;
				}
			}
			
			if(preg_match("#processor\s+:\s+([0-9]+)#",$line,$re)){
				$processor[]=$re[1];
				
			}
			
			
		}
		
		
		$array["MODEL"]=$model;
		$array["BOGOMIPS"]=$bogomips;
		$array["COUNT"]=count($processor);
		
		@file_put_contents($this->cache_path."/processor_type", serialize($array));
		
		
	}
	
	
	
}
