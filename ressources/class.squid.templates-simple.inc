<?php

class template_simple{
	public $SquidHTTPTemplateNoVersion=0;
	public $SquidHTTPTemplateBackgroundColor="#8c1919";
	public $SquidHTTPTemplateFamily="Calibri, Candara, Segoe, \"Segoe UI\", Optima, Arial, sans-serif";
	public $SquidHTTPTemplateFontColor="#FFFFFF";
	public $SquidHTTPTemplateEnablePostmaster=1;
	public $SquidHTTPTemplateDisableHostname;
	public $SquidHTTPTemplateLanguage;
	public $TITLE=null;
	public $BODY=null;
	public $TEMPLATE_NAME=null;
	public $lang=null;
	public $arrayxLangs=array();
	private $TemplateConfig=array();
	
	
	function __construct($TEMPLATE_NAME=null,$lang=null){
		$this->languages();
		$this->OthersTemplates();
		if($TEMPLATE_NAME<>null){
			$this->TEMPLATE_NAME=$TEMPLATE_NAME;
			$this->lang=$lang;
			$this->loadTemplate();
		}
		
	}
	
	private function languages(){
		$this->arrayxLangs["fr"][]="fr-ca";
		$this->arrayxLangs["fr"][]="fr-mc";
		$this->arrayxLangs["fr"][]="fr-fr";
		$this->arrayxLangs["fr"][]="fr-lu";
		$this->arrayxLangs["fr"][]="fr-be";
		$this->arrayxLangs["fr"][]="fr-ch";
		$this->arrayxLangs["fr"][]="French";
		$this->arrayxLangs["es"][]="es-ve";
		$this->arrayxLangs["es"][]="es-gt";
		$this->arrayxLangs["es"][]="es-ar";
		$this->arrayxLangs["es"][]="es-py";
		$this->arrayxLangs["es"][]="es-cr";
		$this->arrayxLangs["es"][]="es-ni";
		$this->arrayxLangs["es"][]="es-pe";
		$this->arrayxLangs["es"][]="es-pa";
		$this->arrayxLangs["es"][]="es-pr";
		$this->arrayxLangs["es"][]="es-uy";
		$this->arrayxLangs["es"][]="es-sv";
		$this->arrayxLangs["es"][]="es-es";
		$this->arrayxLangs["es"][]="es-bo";
		$this->arrayxLangs["es"][]="es-mx";
		$this->arrayxLangs["es"][]="es-hn";
		$this->arrayxLangs["es"][]="es-cl";
		$this->arrayxLangs["es"][]="es-do";
		$this->arrayxLangs["es"][]="es-co";
		$this->arrayxLangs["es"][]="es-ec";
		
		$this->arrayxLangs["en"][]="en-us";
		$this->arrayxLangs["en"][]="en-au";
		$this->arrayxLangs["en"][]="en-sg";
		$this->arrayxLangs["en"][]="en-nz";
		$this->arrayxLangs["en"][]="en-bz";
		$this->arrayxLangs["en"][]="en-gb";
		$this->arrayxLangs["en"][]="en-ie";
		$this->arrayxLangs["en"][]="en-uk";
		$this->arrayxLangs["en"][]="en-tt";
		$this->arrayxLangs["en"][]="en-jm";
		$this->arrayxLangs["en"][]="en-za";
		$this->arrayxLangs["en"][]="en-ph";
		$this->arrayxLangs["en"][]="en-ca";
		$this->arrayxLangs["en"][]="en-in";
		$this->arrayxLangs["en"][]="en-zw";
		$this->arrayxLangs["en"][]="uk";
		$this->arrayxLangs["en"][]="uk-ua";
		
		$this->arrayxLangs["ar"][]="ar-dz";
		$this->arrayxLangs["ar"][]="ar-tn";
		$this->arrayxLangs["ar"][]="es-ar";
		$this->arrayxLangs["ar"][]="ar-ma";
		$this->arrayxLangs["ar"][]="ar-iq";
		$this->arrayxLangs["ar"][]="ar-ae";
		$this->arrayxLangs["ar"][]="ar-ly";
		$this->arrayxLangs["ar"][]="ar-jo";
		$this->arrayxLangs["ar"][]="ar-sa";
		$this->arrayxLangs["ar"][]="ar-sy";
		$this->arrayxLangs["ar"][]="ar-ye";
		$this->arrayxLangs["ar"][]="ar-eg";
		$this->arrayxLangs["ar"][]="ar-bh";
		$this->arrayxLangs["ar"][]="ar-om";
		$this->arrayxLangs["ar"][]="ar-kw";
		$this->arrayxLangs["ar"][]="ar-qa";
		$this->arrayxLangs["ar"][]="ar-lb";
		$this->arrayxLangs["zh"][]="zh-mo";
		$this->arrayxLangs["zh"][]="zh-hant-mo";
		$this->arrayxLangs["zh"][]="zh-hant-tw";
		$this->arrayxLangs["zh"][]="zh-hans-cn";
		$this->arrayxLangs["zh"][]="zh-hans";
		$this->arrayxLangs["zh"][]="zh-tw";
		$this->arrayxLangs["zh"][]="zh-hant-hk";
		$this->arrayxLangs["zh"][]="zh-cn";
		$this->arrayxLangs["zh"][]="zh-hans-sg";
		$this->arrayxLangs["zh"][]="zh-sg";
		$this->arrayxLangs["zh"][]="zh-hk";
		$this->arrayxLangs["zh"][]="zh-hant";
		$this->arrayxLangs["pt"][]="pt-br";
		$this->arrayxLangs["pt"][]="pt-pt";
		$this->arrayxLangs["pt"][]="pt-bz";
		$this->arrayxLangs["sr"][]="sr-cyrl-rs";
		$this->arrayxLangs["sr"][]="sr-latn-rs";
		$this->arrayxLangs["sr"][]="sr-sp";
		$this->arrayxLangs["sr"][]="sr-cyrl";
		$this->arrayxLangs["sr"][]="sr-latn-me";
		$this->arrayxLangs["sr"][]="sr-rs";
		$this->arrayxLangs["sr"][]="sr-latn-cs";
		$this->arrayxLangs["sr"][]="sr-latn";
		$this->arrayxLangs["sr"][]="sr-cyrl-me";
		$this->arrayxLangs["sr"][]="sr-cyrl-cs";
		$this->arrayxLangs["nl"][]="nl-nl";
		$this->arrayxLangs["pl"][]="pl-pl";
		$this->arrayxLangs["ru"][]="ru-ru";
		$this->arrayxLangs["hu"][]="hu-hu";
		$this->arrayxLangs["id"][]="id-id";
		$this->arrayxLangs["th"][]="th-th";
		$this->arrayxLangs["tr"][]="tr-tr";
		$this->arrayxLangs["vi"][]="vi-vn";
		$this->arrayxLangs["fi"][]="fi-fi";
		$this->arrayxLangs["lt"][]="lt-lt";
		$this->arrayxLangs["lv"][]="lv-lv";
		$this->arrayxLangs["az"][]="az-az";
		$this->arrayxLangs["bg"][]="bg-bg";
		
		$this->arrayxLangs["sl"][]="sl-si";
		$this->arrayxLangs["sk"][]="sk-sk";
		$this->arrayxLangs["hy"][]="hy-am";
		$this->arrayxLangs["hy"][]="hy-armn";
		
		$this->arrayxLangs["it"][]="it-it";
		$this->arrayxLangs["it"][]="it-ch";
		
		$this->arrayxLangs["sv"][]="sv-fi";
		$this->arrayxLangs["sv"][]="sv-se";
		
		$this->arrayxLangs["ro"][]="ro-md";
		$this->arrayxLangs["ro"][]="ro-ro";
		
		$this->arrayxLangs["fa"][]="fa-ir";
		$this->arrayxLangs["fa"][]="fa-fa";
		
		$this->arrayxLangs["ja"][]="ja-jp";
		$this->arrayxLangs["ja"][]="ja-ja";
		
		$this->arrayxLangs["ko"][]="ko-kp";
		$this->arrayxLangs["ko"][]="ko-kr";
		$this->arrayxLangs["ko"][]="ko-ko";
		
		$this->arrayxLangs["et"][]="et-ee";
		$this->arrayxLangs["et"][]="et-et";
		
		$this->arrayxLangs["he"][]="he-il";
		$this->arrayxLangs["he"][]="he-he";
		
		$this->arrayxLangs["ms"][]="ms-my";
		$this->arrayxLangs["ms"][]="ms-ms";
		
		$this->arrayxLangs["de"][]="de-at";
		$this->arrayxLangs["de"][]="de-de";
		$this->arrayxLangs["de"][]="de-lu";
		$this->arrayxLangs["de"][]="de-ch";
		$this->arrayxLangs["de"][]="de-li";
		
		$this->arrayxLangs["el"][]="el-gr";
		$this->arrayxLangs["el"][]="el-el";
		
		$this->arrayxLangs["cs"][]="cs-cz";
		$this->arrayxLangs["cs"][]="cs-cs";
		
		$this->arrayxLangs["da"][]="da-dk";
		$this->arrayxLangs["da"][]="da-da";
	}
	
	private function loadTemplate(){
		$sock=new sockets();
		$SquidHTTPTemplateNoVersion=0;
		$SquidHTTPTemplateBackgroundColor="#8c1919";
		$SquidHTTPTemplateFamily="Calibri, Candara, Segoe, \"Segoe UI\", Optima, Arial, sans-serif";
		$SquidHTTPTemplateFontColor="#FFFFFF";
		
		$SquidHTTPTemplateEnablePostmaster=1;
		$SquidHTTPTemplateNoVersion=intval($sock->GET_INFO("SquidHTTPTemplateNoVersion"));
		$SquidHTTPTemplateBackgroundColor=$sock->GET_INFO("SquidHTTPTemplateBackgroundColor");
		$SquidHTTPTemplateDisableHostname=intval($sock->GET_INFO("SquidHTTPTemplateDisableHostname"));
		$SquidHTTPTemplateEnablePostmaster=$sock->GET_INFO("SquidHTTPTemplateEnablePostmaster");
		$SquidHTTPTemplateFontColor=$sock->GET_INFO("SquidHTTPTemplateFontColor");
		$SquidHTTPTemplateFamily=$sock->GET_INFO("SquidHTTPTemplateFamily");
		
		if($SquidHTTPTemplateBackgroundColor==null){$SquidHTTPTemplateBackgroundColor="#8c1919";}
		if($SquidHTTPTemplateFamily==null){$SquidHTTPTemplateFamily="Calibri, Candara, Segoe, \"Segoe UI\", Optima, Arial, sans-serif";}
		if(!is_numeric($SquidHTTPTemplateEnablePostmaster)){$SquidHTTPTemplateEnablePostmaster=1;}
		if($SquidHTTPTemplateFontColor==null){$SquidHTTPTemplateFontColor="#FFFFFF";}
		if(!preg_match("#^\##", $SquidHTTPTemplateFontColor)){$SquidHTTPTemplateFontColor="#$SquidHTTPTemplateFontColor";}
		
		$this->SquidHTTPTemplateNoVersion=$SquidHTTPTemplateNoVersion;
		$this->SquidHTTPTemplateBackgroundColor=$SquidHTTPTemplateBackgroundColor;
		$this->SquidHTTPTemplateFamily=$SquidHTTPTemplateFamily;
		$this->SquidHTTPTemplateFontColor=$SquidHTTPTemplateFontColor;
		$this->SquidHTTPTemplateEnablePostmaster=$SquidHTTPTemplateEnablePostmaster;
		$this->SquidHTTPTemplateDisableHostname=$SquidHTTPTemplateDisableHostname;
		

		
		$this->TemplateConfig=unserialize($sock->GET_INFO("TemplateConfig"));
		
		if(!isset($this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME])){
			if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>Load default content</span><br>\n";}
			$this->defaultContent();
			return;
		}
		
		if($GLOBALS["VERBOSE"]){echo "$this->TEMPLATE_NAME: Build defaults -1- \n";}
		
		$TemplateChoose=$this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME];
		
		if(isset($TemplateChoose["SquidHTTPTemplateNoVersion"])){
			if(is_numeric($TemplateChoose["SquidHTTPTemplateNoVersion"])){
				$this->SquidHTTPTemplateNoVersion=$TemplateChoose["SquidHTTPTemplateNoVersion"];
				if($GLOBALS["VERBOSE"]){echo "SquidHTTPTemplateNoVersion: $this->SquidHTTPTemplateNoVersion\n";}
			}
		}
		
		if(isset($TemplateChoose["SquidHTTPTemplateEnablePostmaster"])){
			if(is_numeric($TemplateChoose["SquidHTTPTemplateEnablePostmaster"])){
				$this->SquidHTTPTemplateEnablePostmaster=$TemplateChoose["SquidHTTPTemplateEnablePostmaster"];
				if($GLOBALS["VERBOSE"]){echo "SquidHTTPTemplateEnablePostmaster: $this->SquidHTTPTemplateEnablePostmaster\n";}
			}
		}

		if(isset($TemplateChoose["SquidHTTPTemplateDisableHostname"])){
			if(is_numeric($TemplateChoose["SquidHTTPTemplateDisableHostname"])){
				$this->SquidHTTPTemplateDisableHostname=$TemplateChoose["SquidHTTPTemplateDisableHostname"];
				if($GLOBALS["VERBOSE"]){echo "SquidHTTPTemplateDisableHostname: $this->SquidHTTPTemplateNoVersion\n";}
			}
		}		
		
		if(isset($TemplateChoose["SquidHTTPTemplateBackgroundColor"])){
			if(trim($TemplateChoose["SquidHTTPTemplateBackgroundColor"])<>null){
				$this->SquidHTTPTemplateBackgroundColor=$TemplateChoose["SquidHTTPTemplateBackgroundColor"];
				if($GLOBALS["VERBOSE"]){echo "SquidHTTPTemplateBackgroundColor: $this->SquidHTTPTemplateBackgroundColor\n";}
			}
		}		
		if(isset($TemplateChoose["SquidHTTPTemplateFamily"])){
			if(trim($TemplateChoose["SquidHTTPTemplateFamily"])<>null){
				$this->SquidHTTPTemplateFamily=$TemplateChoose["SquidHTTPTemplateFamily"];
				if($GLOBALS["VERBOSE"]){echo "SquidHTTPTemplateFamily: $this->SquidHTTPTemplateFamily\n";}
			}
		}	

		if(isset($TemplateChoose["SquidHTTPTemplateFontColor"])){
			if(trim($TemplateChoose["SquidHTTPTemplateFontColor"])<>null){
				$this->SquidHTTPTemplateFontColor=$TemplateChoose["SquidHTTPTemplateFontColor"];
				if($GLOBALS["VERBOSE"]){echo "SquidHTTPTemplateFontColor: $this->SquidHTTPTemplateFontColor\n";}
			}
		}	

		
		

		
		if(!isset($this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME])){
			$this->defaultContent();
			return;
		}
		
		if(trim($this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME]["TITLE"])==null){
			$this->defaultContent();
			return;
		}
			
		if(trim($this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME]["BODY"])==null){
			$this->defaultContent();
			return;
		}	

		$this->TITLE=$this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME]["TITLE"];
		$this->BODY=$this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME]["BODY"];
		
	}
	
	public function OthersTemplates(){
		if(isset($GLOBALS["XTPL_SQUID_DEFAULT_OthersTemplates"])){return;}
		$GLOBALS["XTPL_SQUID_DEFAULT_OthersTemplates"]=true;
		if(!isset($GLOBALS["XTPL_SQUID_DEFAULT"])){
			$GLOBALS["XTPL_SQUID_DEFAULT"]=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/databases/squid.default.templates.db"));
		}
		$langs=$this->arrayxLangs;
		$langs["en-us"]=true;
		while (list ($lang, $line) = each ($langs)){
				$GLOBALS["XTPL_SQUID_DEFAULT"][$lang]["ERR_BLACKLISTED_SITE"]["TITLE"]="ERROR: Blacklisted Website";
				$GLOBALS["XTPL_SQUID_DEFAULT"][$lang]["ERR_BLACKLISTED_SITE"]["BODY"]="<div id=\"titles\">
				<h1>ERROR</h1>
				<h2>Blacklisted Website</h2>
				</div>
				<hr>
				
				<div id=\"content\">
				<p>The following error was encountered while trying to retrieve the URL: <a href=\"%U\">%U</a></p>
				
				<blockquote id=\"error\">
				<p><b>Access Denied by the global policy company</b></p>
				</blockquote>
				
				<p>Access control configuration prevents your request from being allowed at this time.
				Please contact your service provider if you feel this is incorrect.</p>
				
				<p>Your cache administrator is <a href=\"mailto:%w%W\">%w</a>.</p>
				<br>
				</div>
				
				<hr>
				<div id=\"footer\">
				<p>Generated %T by %h (%s)</p>
				<!-- %c -->
				</div>";
		}
		
	}
	
	
	private function defaultContent(){
		if(!isset($GLOBALS["XTPL_SQUID_DEFAULT"])){
			$GLOBALS["XTPL_SQUID_DEFAULT"]=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/databases/squid.default.templates.db"));
		}
		

		
		
		$this->TITLE=trim($GLOBALS["XTPL_SQUID_DEFAULT"][$this->lang][$this->TEMPLATE_NAME]["TITLE"]);
		$this->BODY=trim($GLOBALS["XTPL_SQUID_DEFAULT"][$this->lang][$this->TEMPLATE_NAME]["BODY"]);
		
	}
	
	
	public function Save(){
		
		$this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME]["SquidHTTPTemplateEnablePostmaster"]=$this->SquidHTTPTemplateEnablePostmaster;
		$this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME]["SquidHTTPTemplateBackgroundColor"]=$this->SquidHTTPTemplateBackgroundColor;
		$this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME]["SquidHTTPTemplateDisableHostname"]=$this->SquidHTTPTemplateDisableHostname;
		$this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME]["SquidHTTPTemplateFamily"]=$this->SquidHTTPTemplateFamily;
		$this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME]["SquidHTTPTemplateFontColor"]=$this->SquidHTTPTemplateFontColor;
		
		if($this->TITLE==null){
			$MAINDEF=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/databases/squid.default.templates.db"));
			$this->TITLE=$MAINDEF[$this->lang][$this->TEMPLATE_NAME]["TITLE"];
		}
		if($this->BODY==null){
			$MAINDEF=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/databases/squid.default.templates.db"));
			$this->BODY=$MAINDEF[$this->lang][$this->TEMPLATE_NAME]["BODY"];
		}
		$this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME]["TITLE"]=$this->TITLE;
		$this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME]["BODY"]=$this->BODY;
		$sock=new sockets();
		$sock->SaveConfigFile(serialize($this->TemplateConfig),"TemplateConfig");
		$sock->getFrameWork("squid.php?single-templates=yes");
	}
	
	
	public function TemplatesDesign(){
		$FOOTER=null;
	if(!isset($GLOBALS["CORP_LICENSE"])){
		$users=new usersMenus();
		$GLOBALS["CORP_LICENSE"]=$users->CORP_LICENSE;
	}
	
	$sock=new sockets();
	
	$SquidHTTPTemplateLogoEnable=intval($sock->GET_INFO("SquidHTTPTemplateLogoEnable"));
	
	
	if(!isset($GLOBALS["ARTICA_VERSION"])){$GLOBALS["ARTICA_VERSION"]=@file_get_contents("/usr/share/artica-postfix/VERSION");}
	
	$GlobalFontColor=$this->SquidHTTPTemplateFontColor;
	$BackGroundColor=$this->SquidHTTPTemplateBackgroundColor;
	$GlobalFontFamily=$this->SquidHTTPTemplateFamily;
	$SquidHTTPTemplateSmiley=$sock->GET_INFO("SquidHTTPTemplateSmiley");
	if(!is_numeric($SquidHTTPTemplateSmiley)){$SquidHTTPTemplateSmiley=2639;}
	$title=$this->TITLE;
	$content=$this->BODY;
	
	
	if(!$GLOBALS["CORP_LICENSE"]){
		$FOOTER="
		<table style='width:75%;border-top:1px solid $GlobalFontColor;margin-top:15px'>
		<tr><td colspan=2>&nbsp;</td></tr>
		<tr>
		<td width=64px><img src='/squid-internal-static/icons/silk/logo-artica-64.png'></td>
		<td style='font-size:14px;padding-left:10px' width=99%>
		You using Artica Proxy Appliance v{$GLOBALS["ARTICA_VERSION"]} in Community mode.<br>
		<i>Visit our  <a href=\"http://artica-proxy.com\">website</a> for technical informations or to purchase an Entreprise Edition License</i>
		</td>
		</tr>
		</table>
		</div>";
	}
	$f[]="<!DOCTYPE HTML>";
	$f[]="<html>";
	
	$title=utf8_decode($title);
	$content=utf8_decode($content);
	
	$f[]="<head>";
	$f[]="<!-- LANG '$this->SquidHTTPTemplateLanguage' - '$this->TEMPLATE_NAME' ".date("YmdHis") ." -->";
	$f[]="<title>$title</title>";
	$f[]="<script type=\"text/javascript\">";
	$f[]="    function checkIfTopMostWindow()";
	$f[]="    {";
	$f[]="        if (window.top != window.self) ";
	$f[]="        {  ";
	$f[]="            document.body.style.opacity    = \"0.0\";";
	$f[]="            document.body.style.background = \"#FFFFFF\";";
	$f[]="        }";
	$f[]="        else";
	$f[]="        {";
	$f[]="            document.body.style.opacity    = \"1.0\";";
	$f[]="            document.body.style.background = \"{$BackGroundColor}\";";
		$f[]="        } ";
	$f[]="    }";
	$f[]="</script>";
	$f[]="<style type=\"text/css\">";
	$f[]="    body {";
	$f[]="        color:            $GlobalFontColor; ";
	$f[]="        background-color: #FFFFFF; ";
	$f[]="        font-family:      {$GlobalFontFamily}; ";
	$f[]="        font-weight:      lighter;";
	$f[]="        font-size:        14pt; ";
	$f[]="        ";
	$f[]="        opacity:            0.0;";
	$f[]="        transition:         opacity 2s;";
	$f[]="        -webkit-transition: opacity 2s;";
	$f[]="        -moz-transition:    opacity 2s;";
	$f[]="        -o-transition:      opacity 2s;";
	$f[]="        -ms-transition:     opacity 2s;    ";
	$f[]="    }";
	
	
	$f[]="    center {";
	$f[]="        color:            $GlobalFontColor; ";
	$f[]="        font-family:      {$GlobalFontFamily}; ";
	$f[]="        font-weight:      lighter;";
	$f[]="        font-size:        12pt; ";
	$f[]="}";
	
	$f[]="    h1 {";
	$f[]="        font-size: 72pt; ";
	$f[]="        margin-bottom: 0; ";
	$f[]="        font-family: {$GlobalFontFamily};";
	$f[]="        margin-top: 0 ;";
	$f[]="    }    ";
	$f[]="    h2 {";
	$f[]="        font-size: 22pt; ";
	$f[]="        font-family: {$GlobalFontFamily}; ";
	$f[]="        font-weight: lighter;";
	$f[]="    }   ";
	$f[]="    h3 {";
	$f[]="        font-size: 18pt; ";
	$f[]="        font-family: {$GlobalFontFamily}; ";
	$f[]="        font-weight: lighter;";
	$f[]="        margin-bottom: 0 ;";
	$f[]="    }   ";
	$f[]="    #wrapper {";
	$f[]="        width: 700px ;";
	$f[]="        margin-left: auto ;";
	$f[]="        margin-right: auto ;";
	$f[]="    }    ";
	$f[]="    #info {";
	$f[]="        width: 600px ;";
	$f[]="        margin-left: auto ;";
	$f[]="        margin-right: auto ;";
	$f[]="    }    ";
	
	$f[]="    #titles h1 {";
	$f[]="        font-size: 72pt; ";
	$f[]="        margin-bottom: 0; ";
	$f[]="        font-family: {$GlobalFontFamily};";
	$f[]="        margin-top: 0 ;";
	$f[]="    }    ";
	
	$f[]="hr {
   	border-top: 1px dotted #f00;
  	color: #fff;
  	background-color: #fff;
  	height: 1px;
 	 width:50%;
}
";
	
	$f[]="    #content p {";
	$f[]="       font-size:  11pt;  ";
	$f[]="        margin-bottom: 0; ";
	$f[]="        font-family: {$GlobalFontFamily};";
	$f[]="        margin-top: 0 ;";
	$f[]="    }    ";
	
	$f[]="    #footer p {";
	$f[]="       font-size:  12pt;  ";
	$f[]="        margin-bottom: 0; ";
	$f[]="        font-family: {$GlobalFontFamily};";
	$f[]="        margin-top: 0 ;";
	$f[]="    }    ";	

	
	$f[]="    #data pre{";
	$f[]="       font-size:  12pt;  ";
	$f[]="        margin-bottom: 0; ";
	$f[]="        font-family: {$GlobalFontFamily};";
	$f[]="        font-weight: bold;";
	$f[]="        margin-top: 0 ;";
	$f[]="    }    ";
	$f[]="    #data pre:before{content: \"\\275D\";margin-right:5px;font-size:22pt}";
	$f[]="    #data pre:after{content: \"\\275E\";margin-left:5px;font-size:22pt}";
	$f[]=".bad{ font-size: 110px; float:left; margin-right:30px; }";
	$f[]=".bad:before{ content: \"\\{$SquidHTTPTemplateSmiley}\";}";
	
	$f[]="    td.info_title {    ";
	$f[]="        text-align: right;";
	$f[]="        font-size:  12pt;  ";
	$f[]="        min-width: 100px;";
	$f[]="    }";
	$f[]="    td.info_content {";
	$f[]="        text-align: left;";
	$f[]="        padding-left: 10pt ;";
	$f[]="        font-size:  12pt;  ";
	$f[]="    }";
	$f[]="    .break-word {";
	$f[]="        width: 500px;";
	$f[]="        word-wrap: break-word;";
	$f[]="    }    ";
	$f[]="    a {";
	$f[]="        text-decoration: underline;";
	$f[]="        color: $GlobalFontColor; ";
		$f[]="    }";
	$f[]="    a:visited{";
	$f[]="        text-decoration: underline;";
	$f[]="        color: $GlobalFontColor; ";
		$f[]="    }";
	$f[]="</style>";
	$f[]="</head>";
	$f[]="<body onLoad='checkIfTopMostWindow()'>";
	
	if($SquidHTTPTemplateLogoEnable==1){
		$SquidHTTPTemplateLogoPath=$sock->GET_INFO("SquidHTTPTemplateLogoPath");
		$basename=basename($SquidHTTPTemplateLogoPath);
		
		$SquidHTTPTemplateLogoPositionH=$sock->GET_INFO("SquidHTTPTemplateLogoPositionH");
		$SquidHTTPTemplateLogoPositionL=$sock->GET_INFO("SquidHTTPTemplateLogoPositionL");
		$SquidHTTPTemplateLogoPicturemode=$sock->GET_INFO("SquidHTTPTemplateLogoPicturemode");
		$SquidHTTPTemplateLogoPictureAlign=$sock->GET_INFO("SquidHTTPTemplateLogoPictureAlign");
		if($SquidHTTPTemplateLogoPictureAlign==null){$SquidHTTPTemplateLogoPictureAlign="absolute";}
		$widthDiv="100%";
		$heightDiv=null;
		
		list($width, $height, $type, $attr) = getimagesize($SquidHTTPTemplateLogoPath);
		$heightDiv="height:{$height}px;";
		$background="background-position:{$SquidHTTPTemplateLogoPositionL}% {$SquidHTTPTemplateLogoPositionH}%;";
		
		if($SquidHTTPTemplateLogoPicturemode=="absolute"){
			$widthDiv="{$width}px";
			$background=null;
		}
		
		if($SquidHTTPTemplateLogoPictureAlign<>null){
			$align="text-align:$SquidHTTPTemplateLogoPictureAlign;";
		}
		
		$f[]="<div style='position:{$SquidHTTPTemplateLogoPictureAlign};{$align}width:{$widthDiv};$heightDiv
		background-image:url(\"/squid-internal-static/icons/silk/$basename\");
		background-repeat:no-repeat;$background
		left:{$SquidHTTPTemplateLogoPositionL};
		top:{$SquidHTTPTemplateLogoPositionL};
		'
		>&nbsp;</div>
		";
		
	}
	
	
	
	
	
	$f[]="<div id=\"wrapper\">";
	$f[]="    <h1 class=bad></h1>";
	$f[]="    <div id=\"info\">";
	$f[]="$content";
	
	
	if($this->SquidHTTPTemplateNoVersion==0){
		$f[]="<center>Artica Proxy, version {$GLOBALS["ARTICA_VERSION"]}</center>";
	}
	$f[]="    </div>    $FOOTER";
	$f[]="</div>";
	$f[]="</body>";
	$f[]="<!-- ";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="-->";
	$f[]="</html>";
	return @implode("\n", $f);
	}
	
}