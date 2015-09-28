<?php

class meta_orders{
	private $meta;
	private $unix;
	private $nohup;
	private $php5;
	
	function meta_orders(){
		$this->meta=new artica_meta();
		$this->unix=new unix();
		$this->nohup=$this->unix->find_program("nohup");
		$this->php5=$this->unix->LOCATE_PHP5_BIN();
	}
	
	
	public function  ACTION_PROXY_APPLY_PARAMS($orderid){
		
		if(isset($GLOBALS[__FUNCTION__][$orderid])){return;}
		$GLOBALS[__FUNCTION__][$orderid]=true;
		if(!$this->meta->removeorder($orderid,true)){return;}
		$this->meta->SEND_TASK_PERCENT(50);
		squid_admin_mysql(2, "Order to reconfigure proxy service by Meta Server", null,__FILE__,__LINE__);
		meta_admin_mysql(2, "Success launching reconfigure proxy service task", null,__FILE__,__LINE__);
		shell_exec("$this->nohup $this->php5 /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");
		$this->meta->SEND_TASK_PERCENT(0);
	}
	
	
	public function SQUID_TRANSPARENT_WBL($orderid,$array){
		if(isset($GLOBALS[__FUNCTION__][$orderid])){
			$this->events("Order already done...", __FUNCTION__, __FILE__,__LINE__);
			return;
		}
		
		$this->events("Processing $orderid", __FUNCTION__, __FILE__,__LINE__);
		$GLOBALS[__FUNCTION__][$orderid]=true;
		
		if(!$this->meta->removeorder($orderid,true)){
			$this->events("!!! Unable to remove order $orderid", __FUNCTION__, __FILE__,__LINE__);
			return;
		}
		
		$this->events("Processing 50% ARRAY=".count($array)." items", __FUNCTION__, __FILE__,__LINE__);
		$this->meta->SEND_TASK_PERCENT(50);
		squid_admin_mysql(2, "Order to update Transparent white-list addresses by Meta Server", null,__FILE__,__LINE__);
		$this->events("Saving /etc/squid3/meta-transparent-whitelist.array", __FUNCTION__, __FILE__,__LINE__);
		@file_put_contents("/etc/squid3/meta-transparent-whitelist.array", serialize($array));
		$this->meta->SEND_TASK_PERCENT(90);
		if(is_file("/etc/squid3/meta-transparent-whitelist.array")){
			if(is_file("/etc/init.d/firehol")){
				shell_exec("/etc/init.d/firehol restart >/dev/null 2>&1 &");
			}else{
				$cmd="$this->nohup $this->php5 /usr/share/artica-postfix/exec.squid.transparent.php";
				$this->events($cmd, __FUNCTION__, __FILE__,__LINE__);
				shell_exec($cmd);
			}
		}
		$this->meta->SEND_TASK_PERCENT(0);
	}
	
	
	public function ACTION_DISABLE_AD_EMERGENCY($orderid){
		if(isset($GLOBALS[__FUNCTION__][$orderid])){return;}
		$GLOBALS[__FUNCTION__][$orderid]=true;
		if(!$this->meta->removeorder($orderid,true)){return;}
		$this->meta->SEND_TASK_PERCENT(50);
		squid_admin_mysql(2, "Order to disable Active Directory Emergency by Meta Server", null,__FILE__,__LINE__);
		meta_admin_mysql(2, "Success launching disable Active Directory Emergency service task", null,__FILE__,__LINE__);
		shell_exec("$this->nohup $this->php5 /usr/share/artica-postfix/exec.kerbauth.watchdog.php --disable --meta >/dev/null 2>&1 &");
		$this->meta->SEND_TASK_PERCENT(0);
		
	}
	public function ACTION_ENABLE_AD_EMERGENCY($orderid){
		if(isset($GLOBALS[__FUNCTION__][$orderid])){return;}
		$GLOBALS[__FUNCTION__][$orderid]=true;
		if(!$this->meta->removeorder($orderid,true)){return;}
		$this->meta->SEND_TASK_PERCENT(50);
		squid_admin_mysql(2, "Order to Enable Active Directory Emergency by Meta Server", null,__FILE__,__LINE__);
		meta_admin_mysql(2, "Success launching Enable Active Directory Emergency service task", null,__FILE__,__LINE__);
		shell_exec("$this->nohup $this->php5 /usr/share/artica-postfix/exec.kerbauth.watchdog.php --disable --meta >/dev/null 2>&1 &");
		$this->meta->SEND_TASK_PERCENT(0);
	
	}	
	
	private function events($text,$function,$file=null,$line=0){
		if($file==null){$file=basename(__FILE__);}else{$file=basename($file);}
		$pid=@getmypid();
		$date=@date("H:i:s");
		$logFile="/var/log/artica-meta-agent.log";
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		$text="[$file][$pid] $date $function:: $text (L.$line)\n";
		if($GLOBALS["OUTPUT"]){echo $text;}
		if($GLOBALS["VERBOSE"]){echo $text;}
		@fwrite($f, $text);
		@fclose($f);
	
		$logFile="/var/log/artica-meta.log";
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		@fwrite($f, $text);
		@fclose($f);
	}	
	
	
	
	
}