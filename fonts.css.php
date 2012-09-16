<?php
include_once('ressources/class.templates.inc');

$sock=new sockets();
$font_family=$sock->GET_INFO("InterfaceFonts");
if($font_family==null){$font_family="'Lucida Grande',Arial, Helvetica, sans-serif";}

header("Content-type: text/css");

echo "
body{
	font-family:$font_family;
}

h3{
	font-size:14px;
}
";