#!/usr/bin/php -q
<?php
ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
$GLOBALS["VERBOSE"]=true;

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.os.system.tools.inc');
include_once(dirname(__FILE__).'/ressources/class.categorize.externals.inc');

include_once(dirname(__FILE__).'/ressources/class.squid.remote-stats-appliance.inc');

//include_once(dirname(__FILE__).'/ressources/class.sugaractions.inc');


echo REGISTER_LICENSE_GENERATE_STRING("tototoot")."\n";return;

$f=new squid_stats_appliance();
$f->export_tables();




return;


function REGISTER_LICENSE_GENERATE_STRING($string){
		$ascii=NULL;
		$serial=NULL;
		$secret_num=1;
		for ($i = 0; $i < strlen($string); $i++){$ascii .= $secret_num+ ord($string[$i]);}
		$ascii=substr($ascii,0,20);
		for ($i = 0; $i < strlen($ascii); $i+=2){
				$string=substr($ascii,$i,2);
					switch($string) {
						case $string>122:
						$string-=40;
						break;
						case $string<=48:
						$string+=40;
						break;
					}
				$serial .= chr($string);
			}	
		return $serial;
	}


$sitename="google.fr";
$ext=new external_categorize($sitename);
$extcat=trim($ext->K9());
echo "K9: $extcat\n";

$extcat=trim($ext->UBoxTrendmicroGetCatCode());
echo "Trend: $extcat\n";

$extcat=trim($ext->BrightcloudGetCatCode());
echo "Bright: $extcat\n";	


return;
exec("gluster volume info 2>&1",$results);

while (list ($num, $ligne) = each ($results) ){
	if(preg_match("#Volume Name:\s+(.+)#", $ligne,$re)){
		$volume_name=trim($re[1]);
		continue;
	}
	
	if(preg_match("#Volume ID:\s+(.+)#", $ligne,$re)){
		$VOLS[$volume_name]["ID"]=trim($re[1]);
		continue;
	}
	
	if(preg_match("#Status:\s+(.+)#", $ligne,$re)){
		$VOLS[$volume_name]["STATUS"]=trim(strtolower($re[1]));
		continue;
	}	
	
	if(preg_match("#Type:\s+(.+)#", $ligne,$re)){
		$VOLS[$volume_name]["TYPE"]=trim(strtolower($re[1]));
		continue;
	}

	if(preg_match("#Brick[0-9]+:\s+(.+)#", $ligne,$re)){
		$VOLS[$volume_name]["BRICKS"][]=trim(strtolower($re[1]));
		continue;
	}		
	
}

print_r($VOLS);









return;



$q=new mysql();

$sql="SELECT contacts_numserie.*,societes_numserie.* FROM contacts_numserie,societes_numserie
WHERE societes_numserie.Code_Societe=contacts_numserie.Code_Societe
AND LENGTH(contacts_numserie.eMail)>0
";


$q=new mysql();
$results=$q->QUERY_SQL($sql,"internal");

$sugar=new sugarleads();
while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
	
	$ARR["0d0a4d6d65"]="";
	$ARR["M"]="Mr.";
	$ARR["MR"]="Mr.";
	$ARR["Mr"]="Mr.";
	$ARR["cv"]="Mr.";
	$ARR["%%E"]="Mr.";
	$ARR["-"]="Mr.";
	$ARR["--"]="Mr.";
	$ARR["1"]="Mr.";
	$ARR["11/01/99"]="Mr.";
	$ARR["21"]="Mr.";
	$ARR["Capitaine"]="Mr.";
	$ARR["F"]="Mr.";
	$ARR["Jean"]="Mr.";
	$ARR["M"]="Mr.";
	$ARR["M%r"]="Mr.";
	$ARR["M,"]="Mr.";
	$ARR["M."]="Mr.";
	$ARR["M;"]="Mr.";
	$ARR["ME"]="Mrs.";
	$ARR["MELLE"]="Mrs.";
	$ARR["MLE"]="Mrs.";
	$ARR["MME"]="Mrs.";
	$ARR["MMME"]="Mrs.";
	$ARR["MMR"]="Mr.";
	$ARR["MR"]="Mr.";;
	$ARR["MRMME"]="Mrs.";
	$ARR["Mademoiselle"]="Mrs.";
	
	
	
	$Addr=array();
	$sugar->first_name=utf8_decode($ligne["Nom"]);
	$sugar->last_name=utf8_decode($ligne["Prenom"]);
	$sugar->title=utf8_decode($ligne["Fonction"]);
	$sugar->salutation=$ARR[$ligne["Sexe"]];
	$sugar->email_address=$ligne["eMail"];
	$sugar->phone_work=$ligne["Telephone_Direct"];
	$sugar->phone_mobile=$ligne["GSM"];
	$sugar->phone_fax=$ligne["Fax"];
	if($ligne["Service"]<>null){$Addr[]=utf8_decode($ligne["Service"]);}
	if($ligne["Adresse_1"]<>null){$Addr[]=utf8_decode($ligne["Adresse_1"]);}
	if($ligne["Adresse_2"]<>null){$Addr[]=utf8_decode($ligne["Adresse_2"]);}
	$sugar->website="http://{$ligne["DomainName"]}";
	$sugar->company=utf8_decode($ligne["Nom_Societe"]);
	$sugar->account_name=utf8_decode($ligne["Nom_Societe"]);
	$sugar->primary_address_city=utf8_decode($ligne["Ville"]);
	$sugar->primary_address_postalcode=$ligne["CP"];
	$sugar->primary_address_street=@implode(", ", $Addr);
	$sugar->primary_address_country=$ligne["Pays"];
	$sugar->lead_source="DBImport";
	if($ligne["WEB"]<>null){$sugar->website=$ligne["WEB"];}
	$sugar->AddCsvMemEntry();
}

$sugar->CompileCSVMem();


$f="error.log.8.bz2";
$ext = pathinfo($f, PATHINFO_EXTENSION);
echo "$f = `$ext`\n";



return;

$unix=new unix();


$size=$unix->file_size("/home/dtouzeau/Bureau/php.log");
$size=intval(round(($size/1024))/1000);
echo "$size M\n";






return;

$f=new external_categorize("google.com");

echo $f->UBoxTrendmicroGetCatCode();


return;

$f=new os_system();
echo $f->uptime();
return;


$url="http://192.168.1.1/popunder/img/loadingAnimation.gif";
$array=parse_url($url);
print_r(posix_uname());

$unix=new unix();
print_r($unix->DirFiles("/etc/"));

?>
