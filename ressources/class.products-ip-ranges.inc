<?php

class products_ip_ranges{
	
	public function dropbox_networks(){
		$c=array(
				"108.160.160.0/20",
				"205.189.0.0/24",
				"208.43.202.0/24",
				"107.21.230.0/24",
				"107.20.249.0/24",
				"173.193.134.0/24",
				"173.194.37.0/24",
				"174.36.51.0/24",
				"174.36.30.0/24",
				"174.129.27.0/24",
				"174.129.195.0/24",
				"174.129.196.0/24",
				"174.129.217.0/24",
				"184.73.211.0/24",
					
				"50.19.85.0/24",
				"50.17.185.0/24",
				"50.17.210.0/24",
				"50.17.241.0/24",
				"50.17.243.0/24",
				"50.17.244.0/24",
				"50.17.245.0/24",
				"50.17.246.0/24",
					
				"50.17.248.0/24",
					
					
				"50.16.202.0/24",
				"50.16.206.0/24",
				"50.16.208.0/24",
				"50.16.209.0/24",
				"50.16.210.0/24",
				"50.16.211.0/24",
				"50.16.212.0/24",
				"50.16.213.0/24",
				"50.16.222.0/24",
					
				"50.19.96.0/24",
				"50.19.98.0/24",
					
				"50.19.109.0/24",
				"50.19.116.0/24",
				"50.19.117.0/24",
					
				"54.221.201.0/24",
				"54.221.233.0/24",
				"54.221.232.0/24",
				"54.221.249.0/24",
				"54.230.185.0/24",
					
				"23.21.66.0/24",
				"23.21.82.0/24",
				"23.21.191.0/24",
				"23.21.201.0/24",
				"23.23.225.0/24",
					
				"174.129.203.0/24",
				"174.129.205.0/24",
				"174.129.206.0/24",
				"174.129.207.0/24",
				"174.129.208.0/24",
				"174.129.209.0/24",
				"174.129.210.0/24",
				"174.129.211.0/24",
				"174.129.218.0/24",
				"174.129.219.0/24",
				"174.129.220.0/24",
				"174.129.221.0/24",
				"174.129.222.0/24",
				"174.129.223.0/24",
				"174.129.224.0/24",
				"174.129.225.0/24",
				"174.129.226.0/24",
					
				"184.72.255.0/24","75.101.147.0/24","204.236.220.0/24",
				"75.126.110.0/24",
				"208.43.219.0/24","208.43.223.0/24",
				"199.47.216.0/24","199.47.218.0/24","199.47.219.0/24",
		);
			
	
			
			
		@file_put_contents("/etc/squid3/acls/dropbox-nets.acl", @implode("\n", $c)."\n");
			
	}
	
	
	public function skype_networks(){
		$c=array(
		"111.221.74.0/24",
		"111.221.77.0/24",
		"157.55.130.0/24",
		"157.55.235.0/24",
		"157.55.56.0/24",
		"157.56.52.0/24",
		"213.199.179.0/24",
		"64.4.23.0/24",
		"65.55.223.0/24");
		@file_put_contents("/etc/squid3/acls/skype-nets.acl", @implode("\n", $c)."\n");
			
	}
	
	public function office365_networks(){
		$mic["65.54.74.0/23"]=true;
		$mic["65.54.80.0/20"]=true;
		$mic["65.55.86.0/23"]=true;
		$mic["70.37.128.0/23"]=true;
		$mic["70.37.142.0/23"]=true;
		$mic["70.37.159.0/24"]=true;
		$mic["94.245.68.0/22"]=true;
		$mic["94.245.82.0/23"]=true;
		$mic["94.245.84.0/24"]=true;
		$mic["94.245.86.0/24"]=true;
		$mic["111.221.70.0/25"]=true;
		$mic["111.221.71.0/25"]=true;
		$mic["157.56.200.0/23"]=true;
		$mic["207.46.70.0/24"]=true;
		$mic["207.46.206.0/23"]=true;
		$mic["213.199.148.0/23"]=true;
		$mic["132.245.0.0/16"]=true;
		$mic["157.55.59.128/25"]=true;
		$mic["157.55.155.0/25"]=true;
		$mic["157.55.130.0/25"]=true;
		$mic["157.55.145.0/25"]=true;
		$mic["157.56.53.128/25"]=true;
		$mic["157.56.55.0/25"]=true;
		$mic["157.56.58.0/25"]=true;
		$mic["157.56.151.0/25"]=true;
		$mic["157.55.227.192/26"]=true;
		$mic["207.46.150.128/25"]=true;
		$mic["207.46.198.0/25"]=true;
		$mic["213.199.182.128/25"]=true;
		$mic["207.46.57.128/25"]=true;
		$mic["111.221.127.112/28"]=true;
		$mic["134.170.0.0/16"]=true;
		$mic["65.54.54.128/25"]=true;
		$mic["65.55.121.128/27"]=true;
		$mic["65.55.127.0/24"]=true;
		$mic["111.221.17.128/27"]=true;
		$mic["111.221.22.64/26"]=true;
		$mic["111.221.23.0/25"]=true;
		$mic["111.221.76.96/27"]=true;
		$mic["111.221.76.128/25"]=true;
		$mic["111.221.77.0/26"]=true;
		$mic["132.245.0.0/16"]=true;
		$mic["134.170.0.0/16"]=true;
		$mic["157.55.40.128/25"]=true;
		$mic["157.55.46.0/27"]=true;
		$mic["157.55.46.64/26"]=true;
		$mic["157.55.104.96/27"]=true;
		$mic["157.55.229.128/27"]=true;
		$mic["157.55.232.128/26"]=true;
		$mic["157.55.238.0/25"]=true;
		$mic["207.46.5.0/24"]=true;
		$mic["207.46.7.128/27"]=true;
		$mic["207.46.57.0/25"]=true;
		
		$mic["111.221.111.196"]=true;
		$mic["65.52.196.64"]=true;
		$mic["94.245.108.85"]=true;
		$mic["70.37.97.234"]=true;
		$mic["65.52.208.73"]=true;
		$mic["65.52.240.233"]=true;
		$mic["157.55.185.100"]=true;
		$mic["157.55.194.46"]=true;
		$mic["65.54.165.55"]=true;
		$mic["65.55.239.168"]=true;
		$mic["207.46.216.54"]=true;
		$mic["207.46.73.250"]=true;
		$mic["94.245.117.53"]=true;
		$mic["65.54.55.201"]=true;
		$mic["65.55.150.61"]=true;
		$mic["65.55.150.158"]=true;
		$mic["65.55.150.160"]=true;
		$mic["207.46.14.52"]=true;
		$mic["207.46.14.62"]=true;
		$mic["207.46.14.63"]=true;
		$mic["65.52.98.231"]=true;
		$mic["157.55.44.71"]=true;
		$mic["157.55.160.109"]=true;
		
		while (list ($cidr,$none) = each ($mic)){
			$t[]=$cidr;
		}
		@file_put_contents("/etc/squid3/acls/office365-nets.acl", @implode("\n", $t)."\n");
		
	}
	

	
	public function office365_domains(){	
		$mic["microsoftonline.com"]=true;
		$mic["microsoftonline-p.com"]=true;
		$mic["onmicrosoft.com"]=true;
		$mic["microsoft.com"]=true;
		$mic["sharepoint.com"]=true;
		$mic["outlook.com"]=true;
		$mic["lync.com"]=true;
		$mic["verisign.com"]=true;
		$mic["sa.symcb.com"]=true;
		$mic["office.com.au"]=true;
		$mic["office.com.br"]=true;
		$mic["office.com.mx"]=true;
		$mic["sharepoint.com.br"]=true;
		$mic["sharepoint.com.mx"]=true;
		$mic["sharepoint.com.au"]=true;
		$mic["aadrm.com"]=true;
		$mic["windowsazure.com"]=true;
		$mic["live.com"]=true;
		$mic["lync.com"]=true;
		$mic["microsoft.com"]=true;
		$mic["microsoftonline.com"]=true;
		$mic["microsoftonline-p.com"]=true;
		$mic["microsoftonline-p.net"]=true;
		$mic["microsoftonlineimages.com"]=true;
		$mic["microsoftonlinesupport.net¹"]=true;
		$mic["msecnd.net"]=true;
		$mic["msocdn.com"]=true;
		$mic["msn.com"]=true;
		$mic["msn.co.jp"]=true;
		$mic["msn.co.uk"]=true;
		$mic["onmicrosoft.com"]=true;
		$mic["office.com"]=true;
		$mic["office.net"]=true;
		$mic["office365.com"]=true;
		$mic["outlook.com"]=true;
		$mic["phonefactor.net"]=true;
		$mic["Sharepoint.com"]=true;
		$mic["Sharepointonline.com"]=true;
		
		while (list ($cidr,$none) = each ($mic)){
			$t[]=".".$cidr;
		}
		@file_put_contents("/etc/squid3/acls/office365-domains.acl", @implode("\n", $t)."\n");
		
	}
	
}