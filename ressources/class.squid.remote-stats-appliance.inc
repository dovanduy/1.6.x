<?php
include_once(dirname(__FILE__).'/class.ccurl.inc');
include_once(dirname(__FILE__).'/class.mysql.inc');
include_once(dirname(__FILE__)."/class.mysql.dump.inc");


class squid_stats_appliance{
	var $URI=null;
	var $workdir=null;
	
	function squid_stats_appliance(){
		$this->workdir=dirname(__FILE__)."/squid-export";
		$this->URI=$this->SquidStatsApplianceUri();
		$sock=new sockets();
		$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
		$EnableRemoteSyslogStatsAppliance=$sock->GET_INFO("EnableRemoteSyslogStatsAppliance");
		if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
		if(!is_numeric($EnableRemoteSyslogStatsAppliance)){$EnableRemoteSyslogStatsAppliance=0;}
	
	}
	
	
	private function SquidStatsApplianceUri(){
		
		if($GLOBALS["AS_ROOT"]){
			$RemoteStatisticsApplianceSettings=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/RemoteStatisticsApplianceSettings")));
		}else{
			$sock=new sockets();
			$RemoteStatisticsApplianceSettings=unserialize(base64_decode($sock->GET_INFO('RemoteStatisticsApplianceSettings')));
		}
		if(!is_numeric($RemoteStatisticsApplianceSettings["SSL"])){$RemoteStatisticsApplianceSettings["SSL"]=1;}
		if(!is_numeric($RemoteStatisticsApplianceSettings["PORT"])){$RemoteStatisticsApplianceSettings["PORT"]=9000;}
		$REMOTE_SSERVER=$RemoteStatisticsApplianceSettings["SERVER"];
		$REMOTE_SPORT=$RemoteStatisticsApplianceSettings["PORT"];
		$REMOTE_SSL=$RemoteStatisticsApplianceSettings["SSL"];
		if($REMOTE_SSL==1){$refix="https";}else{$refix="http";}
		return "$refix://$REMOTE_SSERVER:$REMOTE_SPORT";		
		
	}

	
	public function Replicate(){
		$unix=new unix();
		$curl=new ccurl("$this->URI/squid.stats.listener.php");
		$curl->parms["SQUID_TABLES_INDEX"]="yes";
		if(!$curl->get()){
			ufdbguard_admin_events("Check $this->URI failed with error: $curl->error", __FUNCTION__, __FILE__, __LINE__, "squidstats");
			return;
		}
		
		if(preg_match("#<ERROR>(.*?)</ERROR>#is", $curl->data,$re)){ufdbguard_admin_events("Error from central server: {$re[1]}", __FUNCTION__, __FILE__, __LINE__, "squidstats");return;}
		if(!preg_match("#<SUCCESS>(.*?)</SUCCESS>#is", $curl->data,$re)){
			if($GLOBALS["VERBOSE"]){echo $curl->data."\n";}
			ufdbguard_admin_events("Error from central server: Protocol error", 
			__FUNCTION__, __FILE__, __LINE__, "squidstats");return;
		}
		$array=unserialize(base64_decode($re[1]));
		if(!is_array($array)){ufdbguard_admin_events("Error from central server: Protocol error (not an array)",__FUNCTION__, __FILE__, __LINE__, "squidstats");return;}
		if(count($array)==0){ufdbguard_admin_events("Error from central server: Protocol error no item",__FUNCTION__, __FILE__, __LINE__, "squidstats");return;}
		if(!is_dir($this->workdir)){@mkdir($this->workdir,0775,true);}
		
		
		while (list ($filename, $none) = each ($array) ){
			$curl=new ccurl("$this->URI/$filename");
			$target="$this->workdir/".basename($filename);
			if(!is_file($target)){@unlink($target);}
			$TARGETS[]="$this->workdir/".basename($filename);
			if(!$curl->GetFile($target)){
				ufdbguard_admin_events("Check $this->URI/$filename failed with error: $curl->error", __FUNCTION__, __FILE__, __LINE__, "squidstats");
				return;
			}
		}
		
		$mysqlbin=$unix->find_program("mysql");
		$q=new mysql();
		if($q->mysql_server=="127.0.0.1"){$servcmd=" --socket=/var/run/mysqld/mysqld.sock ";}else{$servcmd=" --host=$q->mysql_server --port=$q->mysql_port ";}
		if($q->mysql_password<>null){$password=" --password=$q->mysql_password ";}
		$cmdline="$mysqlbin --batch --force --user=$q->mysql_admin$password $servcmd";
		while (list ($index, $sourcefile) = each ($TARGETS) ){
			$destinationfile=$sourcefile.".sql";
			if(!$unix->uncompress($sourcefile, "$destinationfile")){ufdbguard_admin_events("replication failed uncompress $sourcefile", __FUNCTION__, __FILE__, __LINE__, "squidstats");continue;}
			$localdatabase=basename($sourcefile);
			$localdatabase=str_replace(".gz", "", $localdatabase);
			$cmd="$cmdline --database=$localdatabase <$destinationfile 2>&1";
			if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
			exec($cmd,$results);
			@unlink($destinationfile);
			if($q->IS_ERROR_DUMP_CMDLINE($results)){
				ufdbguard_admin_events("replication failed restoring $localdatabase with $destinationfile with error $q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "squidstats");
				continue;
			}
			ufdbguard_admin_events("replication success restoring database $localdatabase", __FUNCTION__, __FILE__, __LINE__, "squidstats");
		} 
	}
	
	
	public function GET_INDEX(){
		if(!is_file("$this->workdir/artica_backup.gz")){$this->export_tables();}
		if(!is_file("$this->workdir/squidlogs.gz")){$this->export_tables();}
		if(!is_file("$this->workdir/artica_backup.gz")){echo "<ERROR>artica_backup.gz no such file</ERROR>";die();}
		if(!is_file("$this->workdir/squidlogs.gz")){echo "<ERROR>artica_backup.gz no such file</ERROR>";die();}		
		$base="ressources/".basename($this->workdir);
		$array["$base/artica_backup.gz"]=true;
		$array["$base/squidlogs.gz"]=true;
		echo "<SUCCESS>".base64_encode(serialize($array))."</SUCCESS>";
		
	}
	
	
	public function export_tables(){
		$q=new mysql();
		$q->BD_CONNECT();
		$artica_backup["squid_pools"]=true;
		$artica_backup["squid_balancers"]=true;
		$artica_backup["squid_header_access"]=true;
		$artica_backup["squid_parents"]=true;
		$artica_backup["usersisp"]=true;
		$artica_backup["squid_ssl"]=true;
		$artica_backup["squid_accel"]=true;
		$artica_backup["websites_caches_params"]=true;
		$artica_backup["squid_speed"]=true;
		$artica_backup["squid_block"]=true;
		$artica_backup["dansguardian_files"]=true;
		$artica_backup["squid_white"]=true;
		
		$databases["artica_backup"]=$artica_backup;
		if(!is_dir($workdir)){@mkdir($this->workdir,0775,true);}
		$dump=new phpMyDumper("artica_backup",$q->mysql_connection,"$this->workdir/artica_backup.gz",true,$artica_backup);
		$dump->doDump();
		
		$q=new mysql_squid_builder();
		$q->BD_CONNECT();
		$squidlogs["webfilters_sqacls"]=true;
		$squidlogs["webfilters_sqaclaccess"]=true;
		$squidlogs["webfilters_sqgroups"]=true;
		$squidlogs["webfilters_sqacllinks"]=true;
		$squidlogs["webfilters_sqitems"]=true;
		$squidlogs["webfilters_sqtimes_rules"]=true;
		$squidlogs["webfilters_blkwhlts"]=true;
		$squidlogs["webfilters_usersasks"]=true;
		$squidlogs["squidtpls"]=true;
		
		
		$databases["squidlogs"]=$squidlogs;
		$dump=new phpMyDumper("squidlogs",$q->mysql_connection,"$this->workdir/squidlogs.gz",true,$squidlogs);
		$dump->doDump();
		
	}
	
	
}
