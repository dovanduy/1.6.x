<?php

class cicap_av{
	var $modules_path=null;
	var $main_array=array();
	
	function cicap_av($modules_path){
		$this->modules_path=$modules_path;
		$ini=new Bs_IniHandler();
		$sock=new sockets();
		$ini->loadString($sock->GET_INFO('CicapInternalConfiguration'));
		$this->main_array=$ini->_params;
	}
	
	private function BuildDefaults(){
		if($this->main_array["CONF"]["Timeout"]==null){$this->main_array["CONF"]["Timeout"]=300;}
		if($this->main_array["CONF"]["KeepAlive"]==null){$this->main_array["CONF"]["KeepAlive"]="on";}
		if($this->main_array["CONF"]["MaxKeepAliveRequests"]==null){$this->main_array["CONF"]["MaxKeepAliveRequests"]="100";}
		if($this->main_array["CONF"]["KeepAliveTimeout"]==null){$this->main_array["CONF"]["KeepAliveTimeout"]="600";}
		if($this->main_array["CONF"]["StartServers"]==null){$this->main_array["CONF"]["StartServers"]="3";}
		if($this->main_array["CONF"]["MaxServers"]==null){$this->main_array["CONF"]["MaxServers"]="10";}
		if($this->main_array["CONF"]["MinSpareThreads"]==null){$this->main_array["CONF"]["MinSpareThreads"]="10";}
		if($this->main_array["CONF"]["MaxSpareThreads"]==null){$this->main_array["CONF"]["MaxSpareThreads"]="20";}
		if($this->main_array["CONF"]["ThreadsPerChild"]==null){$this->main_array["CONF"]["ThreadsPerChild"]="0";}
		if($this->main_array["CONF"]["MaxRequestsPerChild"]==null){$this->main_array["CONF"]["MaxRequestsPerChild"]="0";}
		if($this->main_array["CONF"]["srv_clamav.SendPercentData"]==null){$this->main_array["CONF"]["srv_clamav.SendPercentData"]="5";}
		if($this->main_array["CONF"]["srv_clamav.StartSendPercentDataAfter"]==null){$this->main_array["CONF"]["srv_clamav.StartSendPercentDataAfter"]="2";}
		if($this->main_array["CONF"]["srv_clamav.MaxObjectSize"]==null){$this->main_array["CONF"]["srv_clamav.MaxObjectSize"]="5";}
		if($this->main_array["CONF"]["srv_clamav.ClamAvMaxFilesInArchive"]==null){$this->main_array["CONF"]["srv_clamav.ClamAvMaxFilesInArchive"]="10";}
		if($this->main_array["CONF"]["srv_clamav.ClamAvMaxFileSizeInArchive"]==null){$this->main_array["CONF"]["srv_clamav.ClamAvMaxFileSizeInArchive"]="100";}
		if($this->main_array["CONF"]["srv_clamav.ClamAvMaxRecLevel"]==null){$this->main_array["CONF"]["srv_clamav.ClamAvMaxRecLevel"]="5";}
		if($this->main_array["CONF"]["ThreadsPerChild"]==0){$this->main_array["CONF"]["ThreadsPerChild"]=10;}
		if($this->main_array["CONF"]["VirSaveDir"]==null){$this->main_array["CONF"]["VirSaveDir"]="/opt/artica/share/www/squid-attachments";}
		if($this->main_array["CONF"]["VirHTTPServer"]==null){$this->main_array["CONF"]["VirHTTPServer"]="https:///exec.cicap.php?usename=%f&remove=1&file=";}
		if($this->main_array["CONF"]["DebugLevel"]==null){$this->main_array["CONF"]["DebugLevel"]="3";}
		if($this->main_array["CONF"]["ViralatorMode"]==null){$this->main_array["CONF"]["ViralatorMode"]="0";}
	}
		
	function build(){
		$sock=new sockets();
		$EnableClamavInCiCap=$sock->GET_INFO("EnableClamavInCiCap");
		if(!is_numeric($EnableClamavInCiCap)){$EnableClamavInCiCap=1;}
		
		if($EnableClamavInCiCap==0){
			$f[]="# EnableClamavInCiCap = 0 ( no AV for iCPAP)";
			@file_put_contents("/etc/virus_scan.conf", @implode("\n", $f));
			return "Include /etc/virus_scan.conf";
		}
		
		if(!is_file("$this->modules_path/clamd_mod.so")){
			$f[]="# $this->modules_path/clamd_mod.so no such file";
			@file_put_contents("/etc/virus_scan.conf", @implode("\n", $f));
			return "Include /etc/virus_scan.conf";
		}
		$f[]="# Load the virus_scan service:";
		$f[]="Service antivirus_module virus_scan.so";
		$f[]="";
		$f[]="#Add an alias to srv_clamav for compatibility with old service name";
		$f[]="ServiceAlias srv_clamav virus_scan";
		$f[]="";
		$f[]="# Add the alias avscan for virus_scan service. It is used by many";
		$f[]="# ICAP based antivirus clients:";
		$f[]="ServiceAlias  avscan virus_scan?allow204=on&sizelimit=off&mode=simple";
		$f[]="";
		$f[]="virus_scan.ScanFileTypes TEXT DATA EXECUTABLE ARCHIVE MSOFFICE";
		$f[]="virus_scan.SendPercentData {$this->main_array["CONF"]["srv_clamav.SendPercentData"]}";
		$f[]="virus_scan.StartSendPercentDataAfter {$this->main_array["CONF"]["srv_clamav.StartSendPercentDataAfter"]}M";
		$f[]="virus_scan.Allow204Responces off";
		$f[]="virus_scan.MaxObjectSize  {$this->main_array["CONF"]["srv_clamav.MaxObjectSize"]}M";
		$f[]="virus_scan.PassOnError on";
		$f[]="virus_scan.VirScanFileTypes ARCHIVE EXECUTABLE";
		
		if($this->main_array["CONF"]["ViralatorMode"]==1){
			$sock->SET_INFO("CiCapViralatorMode",1);
			$f[]="#	virus_scan.VirSaveDir {$this->main_array["CONF"]["VirSaveDir"]}";
			$f[]="#	virus_scan.VirHTTPUrl   \"{$this->main_array["CONF"]["VirHTTPServer"]}\"";
			$f[]="#	virus_scan.VirUpdateTime   15";
			$f[]="#	virus_scan.VirScanFileTypes ARCHIVE EXECUTABLE";
			$f[]="";
		}		
		
		$f[]="Include /etc/clamd_mod.conf";
		$f[]="# End module: virus_scan";
		$f[]="";
		
		@file_put_contents("/etc/virus_scan.conf", @implode("\n", $f));
		
		
		$f=array();
		$f[]="Module common clamd_mod.so";
		$f[]="clamd_mod.ClamdSocket /var/run/clamav/clamav.sock";
		$f[]="";
		@file_put_contents("/etc/clamd_mod.conf", @implode("\n", $f));
		return "Include /etc/virus_scan.conf";
	}
	
}

