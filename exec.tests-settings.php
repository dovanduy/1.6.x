<?php


include("/usr/share/artica-postfix/ressources/settings.inc");

if(!isset($_GLOBAL["ldap_admin"])){
	shell_exec("/usr/share/artica-postfix/bin/process1 --force --".time().">/dev/null 2>&1");
	die();
}


$t=@file_get_contents("/usr/share/artica-postfix/ressources/settings.inc");
if(preg_match("#<\?php(.+?)\?>#is", $t,$re)){
	@file_put_contents("/usr/share/artica-postfix/ressources/settings.inc", "<?php\n{$re[1]}\n?>");
}