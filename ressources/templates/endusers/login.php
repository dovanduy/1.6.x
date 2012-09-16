<?php

build();

function langsort($a, $b) { return strcasecmp($a, $b); } 
	


function build(){
$user = htmlentities($_GET["user"]);
header("Content-type: text/html; charset=utf-8");

$cookyname=md5("ZARAFUSR_{$_SERVER['SERVER_NAME']}");

if(isset($cookyname)){
	if(trim($user)==null){$user=$_COOKIE[$cookyname];}
}

if($user<>null){$user_post="&user=$user";}



  $langs = $GLOBALS["language"]->getLanguages();
  uasort($langs, 'langsort');
  foreach($langs as $lang=>$title){ 
		$lls[]="<option value=\"$lang\">$title</option>";
  }

$hidden="<input type=\"hidden\" name=\"action_url\" value=\"".stristr($_SERVER["REQUEST_URI"],"?action=")."\"></input>";

if($_POST && $_POST["action_url"] != "") {
	$hidden="<input type=\"hidden\" name=\"action_url\" value=\"{$_POST["action_url"]}\"></input>";
	
}
if(defined("DEBUG_SERVER_ADDRESS")){
	$footer[]="Server: ".DEBUG_SERVER_ADDRESS;
}
if(defined("DEBUG_SERVER_ADDRESS")){
	$footer[]=phpversion("mapi");
}
if(defined("SVN")){
	$footer[]="svn".SVN;
}


if (isset($_SESSION) && isset($_SESSION["hresult"])) {
		switch($_SESSION["hresult"]){
			case MAPI_E_LOGON_FAILED:
			case MAPI_E_UNCONFIGURED:
				$error= _("Logon failed, please check your name/password.");
				break;
			case MAPI_E_NETWORK_ERROR:
				$error= _("Cannot connect to the Zarafa Server.");
				break;
			default:
				$error= "Unknown MAPI Error: ".get_mapi_error_name($_SESSION["hresult"]);
		}
		unset($_SESSION["hresult"]);
	}else if (isset($_GET["logout"]) && $_GET["logout"]=="auto"){
		$error= _("You have been automatically logged out");
	}else{
		$error= "&nbsp;";
	}

$html="
<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">
<html>
	<head>
		<title>Zarafa WebAccess</title>
		<STYLE type=\"text/css\">
		
.ui-corner-bottom {
    -moz-border-radius-bottomleft: 3px;
    -moz-border-radius-bottomright: 3px;
}
.ui-corner-right {
    -moz-border-radius-bottomright: 3px;
    -moz-border-radius-topright: 3px;
}
.ui-corner-left {
    -moz-border-radius-bottomleft: 3px;
    -moz-border-radius-topleft: 3px;
}
.ui-corner-all {
    -moz-border-radius: 3px 3px 3px 3px;
}
.ui-state-default, .ui-widget-content .ui-state-default, .ui-widget-header .ui-state-default {
    background-color: #263849;
    color: #FFFFFF;
    font-weight: bold;
}
.ui-state-default a, .ui-state-default a:link, .ui-state-default a:visited {
    color: #FFFFFF;
    text-decoration: none;
}		
			
			body{
				font: 10pt Arial, Helvetica, sans-serif;
				background: #263849 url('/client/layout/img/i/pattern.png');
			}
			#sum{
				width: 485px;
				height: 221px;
				margin: 167px auto;
				
			}
			h1{
				width: 401px;
				height: 127px;
				background: transparent url('/client/layout/img/i/logo.png') no-repeat;
				margin: 0 27px 21px;
			} 
			h1 span{
				display: none;
			}
			#content{
				width: 485px;
				height: 221px;
				background: url('/client/layout/img/i/form.png') no-repeat;	
			}
			.f{
				padding: 18px 50px 45px 38px;	
				overflow: hidden;
			}
			.field{
				clear:both;
				text-align: right;
				margin-bottom: 15px;
			}
			.field label{
				float:left;
				font-weight: bold;
				line-height: 42px;
				font-size:14px;
			}
			.field input{
				background: #fff url('/client/layout/img/i/input.png') no-repeat;
				outline: none;
				border: none;
				font-size: 10pt;
				padding: 7px 9px 8px;
				width: 279px;
				height: 25px;
				font-size: 18px;
				font-weight:bolder;
				color:#444444;
			}
			.field input.active{
				background: url('/client/layout/img/i/input_act.png') no-repeat;
			}
			.button{
				width: 297px;
				float: right;
			}
			.button input{
				width: 69px;
				background: url('/client/layout/img/i/btn_bg.png') no-repeat;
				border: 0;
				font-weight: bold;
				height: 27px;
				float: left;
				padding: 0;
			}
		</STYLE>
		<link rel=\"icon\" href=\"client/layout/img/favicon.ico\"  type=\"image/x-icon\">
		<link rel=\"shortcut icon\" href=\"client/layout/img/favicon.ico\" type=\"image/x-icon\">	
		<script type=\"text/javascript\">
			window.onload = function(){
				if (document.getElementById(\"username\").value == \"\"){
					document.getElementById(\"username\").focus();
				}else if (document.getElementById(\"password\").value == \"\"){
					document.getElementById(\"password\").focus();
				}
			}
		</script>
	</head>

  <div id=\"sum\">
    <div id=\"header\">
      <h1><span>{TEMPLATE_TITLE_HEAD}</span></h1>
    </div>


 <center><span style='color:white;font-size:16px'>$error</span></center>

    <div id=\"content\">

			<form action=\"index.php?logon$user_post\" method='post' id='myform'>
			$hidden
			
				<div class=\"f\">
					<div class=\"field\">
						<label for=\"username\">"._("Name").":</label> <input type=\"text\" name=\"username\" id=\"username\" 
						onfocus=\"this.setAttribute('class','active')\" 
						onblur=\"this.removeAttribute('class');\" 
						OnKeyPress=\"javascript:SubmitZarafaFormCheck(event);\"
						value=\"$user\">
		
					</div>
					<div class=\"field\">
						<label for=\"password\">"._("Password").":</label> <input type=\"password\" name=\"password\" id=\"password\" 
						onfocus=\"this.setAttribute('class','active')\" 
						onblur=\"this.removeAttribute('class');\" 
						OnKeyPress=\"javascript:SubmitZarafaFormCheck(event);\">
					</div>
					<div class=\"field\">
						<label for=\"language\">"._("Language").":</label> <select  name=\"language\" id=\"language\" onfocus=\"this.setAttribute('class','active')\" onblur=\"this.removeAttribute('class');\">
						<option value=\"last\">"._("Last used language")."</option>
						".@implode("\n", $lls)."
						</select>
					</div>					
					
											
					
					<div class=\"field button\">
						<button onclick=\"javascript:this.className='ui-state-over';PushForm();\" class=\"ui-state-default ui-corner-all\" onmouseover=\"javascript:this.className='ui-state-active ui-corner-all';this.style.cursor='pointer'\" onmouseout=\"javascript:this.className='ui-state-default ui-corner-all';this.style.cursor='auto'\" style=\"padding: 3px; font-size: 18px; cursor: auto;\" type=\"submit\">&nbsp;Login&nbsp;&nbsp;»</button>
					</div>
				</div>
		
			</form>			
    </div><!-- /#content -->
    

    <div class=\"footer\">
    	
    	<center style='font-size:13px;font-weight:bold;color:white'>". @implode("-", $footer)."</center>
    </div><!-- /#footer -->
  </div>
  
  <script>
  	function SubmitZarafaFormCheck(e){
  		if(checkEnter(e)){PushForm();}
  	}
  	
  	function PushForm(){
  		Set_Cookie('$cookyname',document.getElementById('username').value);
  		document.forms['myform'].submit();
  	}
  
  
	function checkEnter(e){
		var characterCode 
		characterCode = (typeof e.which != \"undefined\") ? e.which : event.keyCode;
		if(characterCode == 13){ return true;}else{return false;}
	}  
	
function Set_Cookie( name, value, expires, path, domain, secure ) {
	var today = new Date();
	today.setTime( today.getTime() );

	if ( expires ){
		expires = expires * 1000 * 60 * 60 * 24;
	}
	var expires_date = new Date( today.getTime() + (expires) );

	document.cookie = name + \"=\" +escape( value ) +
	( ( expires ) ? \";expires=\" + expires_date.toGMTString() : \"\" ) + 
	( ( path ) ? \";path=\" + path : \"\" ) + 
	( ( domain ) ? \";domain=\" + domain : \"\" ) +
	( ( secure ) ? \";secure\" : \"\" );
}	
  
  </script>
 
</body>
</html>	
";	
echo $html;
	
}



