<?php

class highcharts{
	
	public $container="container";
	public $datas=array();
	public $xAxis=array();
	public $ChartType='areaspline';
	public $Title='Chart title';
	public $yAxisTtitle='Y Axis title';
	public $xAxisTtitle="X Axis title";
	public $AreaColor="#005447";
	public $PieDatas=array();
	public $PiePlotTitle=null;
	public $TitleFontSize="18px";
	public $AxisFontsize="14px";
	public function highcharts($params=array()){
		
		while (list ($key, $value) = each ($params) ){
			$this->$key=$value;
		}
		
	}
	
	
	public function BuildChart(){
		
		$t=time();
		$tpl=new templates();
		
		$this->Title=replace_accents($this->Title);
		$this->Title=$tpl->javascript_parse_text($this->Title);
		$borderColor="005447";
		$page=CurrentPageName();
		if(preg_match("#^miniadm#", $page)){
			$borderColor="3B5998";
			$this->AreaColor="#$borderColor";
		}
		
		if($_SESSION["MINIADM"]==true){
			$borderColor="3B5998";
			$this->AreaColor="#$borderColor";			
		}
		
		
		$this->yAxisTtitle=$tpl->javascript_parse_text($this->yAxisTtitle);
		$this->xAxisTtitle=$tpl->javascript_parse_text($this->xAxisTtitle);
		$ChartString="Chart".md5($this->container.rand(0, time()));
		//$f[]="var chart;";
		$f[]="// Chart type = $this->ChartType";
		$f[]="$(document).ready(function(){";
		//$f[]="\tvar \$container$t = $('#$this->container');";
		
		
		$f[]="if($ChartString){ $ChartString.destroy(); }";
		$f[]="\tvar $ChartString = new Highcharts.Chart({";
		$f[]="\t\tchart: {";
		$f[]="\t\t\ttype: '$this->ChartType',";
		$f[]="\t\t\trenderTo: '$this->container',";
		if($this->ChartType=="areaspline"){	$f[]="\t\t\tstyle:{	color: '#$borderColor'}";}
		if($this->ChartType=="pie"){
			$f[]="\t\t\tplotBackgroundColor: null,";
			$f[]="\t\t\tplotBorderWidth: null,";
			$f[]="\t\t\tplotShadow: true,";
		}
		
		
		$f[]="\t\t},";
		
		$f[]="\t\t\tcredits: { enabled: false },";	
			
		$f[]="\t\t\ttitle: { text: '$this->Title' ,style:{	color: '#$borderColor', fontSize: '$this->TitleFontSize'} },";	
		if($this->ChartType=="areaspline"){	
			
			if(count($this->xAxis)==0){return $this->NoreturnedValue($this->xAxis,__LINE__);}
			if(count($this->xAxis)==1){return $this->OnlyOneValue($this->xAxis,__LINE__);}
				
			
			if(count($this->xAxis)>0){
				while (list ($a, $b) = each ($this->xAxis) ){
					if(strpos(" $b", "\"")>0){continue;}
					if(!is_numeric($b)){$b="\"$b\"";$this->xAxis[$a]=$b;}
				}
				reset($this->xAxis);
				
			}
			
			$f[]="\t\t\txAxis: {";
			$f[]="\t\t\t\tcategories: [". @implode(",", $this->xAxis)."],";
			$f[]="\t\t\t\ttitle: {";
			$f[]="\t\t\t\t\ttext: '$this->xAxisTtitle',style:{	color: '#$borderColor', fontSize: '$this->AxisFontsize'}";
			$f[]="\t\t\t}";
			$f[]="\t\t},";
		}

		if($this->ChartType=="areaspline"){
			$f[]="\t\t\tyAxis: {";
			$f[]="\t\t\t\ttitle: {";
			$f[]="\t\t\t\t\ttext: '$this->yAxisTtitle',style:{	color: '#$borderColor', fontSize: '$this->AxisFontsize'}";
			$f[]="\t\t\t}";
			$f[]="\t\t},";
		}
		
		if($this->ChartType=="line"){
			$f[]="plotLines: [{
					value: 0,
					width: 1,
					color: '#808080'
				}]
			},";
		}
		
		

		
		
		if($this->ChartType=="areaspline"){
			$f[]="\t\t\tplotOptions: {";
			$f[]="\t\t\t\tcolor:'$this->AreaColor',";
	   	 	$f[]="\t\t\t\tareaspline: {";
	   	 	$f[]="\t\t\t\t\tcolor:'$this->AreaColor',";
	   	 	$f[]="\t\t\t\t\tfillOpacity: 0.5,";
	   	 	$f[]="\t\t\t\t},";
	   	 	$f[]="\t\t\t\t\tseries: {";
	   	 	$f[]="\t\t\t\t\t\tmarker: { enabled: false },";
	   	 	$f[]="\t\t\t\t}";	   	 	
	   	 	$f[]="\t\t},";
		}
		
	$f[]=" tooltip: {
					useHTML: true,
					headerFormat: '<center><strong style\"font-size:16px\">{point.key}</strong><hr style=\"border:1px solid #CCCCCC\"></center>',
	                pointFormat: '<strong style\"font-size:16px\">{series.name}</strong> <b>{point.y:,.0f}</b><br/>'
	            },
	           ";	
	$f[]=" legend: {
	                itemStyle:{	color: '#$borderColor', fontSize: '12px'},
					shadow: true
	            },
	           ";	
	
	$f[]="series: [";
	
	if($this->ChartType=="areaspline"){
		if(count($this->datas)==0){return $this->NoreturnedValue($this->datas,__LINE__);}
		if(count($this->datas[0])==1){
			return $this->NoreturnedValue($this->datas,__LINE__);
		}
			
		
		
		
		while (list ($key, $series) = each ($this->datas) ){
			$key=$tpl->javascript_parse_text($key);
			$f[]="{name: '$key',";
			$f[]="data: [" .@implode(",", $series)."]";
			$f[]="},";
			
		}
	}
	
	if($this->ChartType=="pie"){
		
		$this->PiePlotTitle=$tpl->javascript_parse_text($this->PiePlotTitle);
		if(count($this->PieDatas)==0){return $this->NoreturnedValue($this->PieDatas,__LINE__);}
		
		while (list ($key, $value) = each ($this->PieDatas) ){
			
			$key=$tpl->javascript_parse_text($key);
			$key=str_replace("'", "`", $key);
			$P[]="\n\t['$key',   $value]";
		}
		
		$f[]="{type: 'pie',";
		$f[]="name: '$this->PiePlotTitle',";
		$f[]="data: [";
		$f[]=@implode(",", $P);
		$f[]="]},";
	}	
	
	
	
	$f[]="]";
	$f[]="});";
	$f[]="});";
	header("content-type: application/x-javascript");
	return @implode("\n", $f);
	
		
		
	}
	
	
	private function OnlyOneValue($array,$line=0){
			if(function_exists("debug_backtrace")){
				$trace=@debug_backtrace();
				if(isset($trace[1])){
					if($line>0){$trace[1]["line"]=$line;}
					$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
				}
			}
		$tpl=new templates();
		$onlionetext=$tpl->javascript_parse_text("{only_one_value_cannot_generate_graph}");
		
		if(is_array($this->datas)){
			while (list ($a, $b) = each ($this->datas) ){
				$bt=$b;
				$e=array();
				if(is_array($b)){
					while (list ($d, $e) = each ($b) ){
						if($d==0){$d=null;}
						$f[]="$d $e";}
					$bt=@implode(" ", $f);
				}
				if($a==0){$a=null;}
				$c[]="$a $bt";
					
			}
		}
		
		while (list ($a, $b) = each ($array) ){
				$bt=$b;
				$e=array();
				if(is_array($b)){
					while (list ($d, $e) = each ($b) ){
						if($d==0){$d=null;}
						$f[]="$d $e";}
					$bt=@implode(" ", $f);
				}
				if($a==0){$a=null;}
				$c[]="$a $bt";
			
		}
		$value=$tpl->javascript_parse_text(@implode(" ", $c));
		
		return "
		document.getElementById('$this->container').style.height='auto';
		document.getElementById('$this->container').style.width='auto';		
		document.getElementById('$this->container').innerHTML='<p class=text-warning><strong>$this->Title</strong><br>$onlionetext <strong style=font-size:14px>$value</strong><br>$called</p>';";
		
		
	}
	
	public function NoreturnedValue($array=array(),$line=0){
		if(function_exists("debug_backtrace")){
			$trace=@debug_backtrace();
			if(isset($trace[1])){
				if($line>0){$trace[1]["line"]=$line;}
				$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
			}
		}		
		$tpl=new templates();
		$onlionetext=$tpl->javascript_parse_text("{no_value_cannot_generate_graph}");
		
		return "
		document.getElementById('$this->container').style.height='auto';
		document.getElementById('$this->container').style.width='auto';		
		document.getElementById('$this->container').innerHTML='<p class=text-warning><strong>$this->Title</strong><br>$onlionetext<br>$called</p>';
		
		";
				
		
	}
	
	
	
	
	
}