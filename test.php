<?php



$f[]="http://rpm.pbone.net/index.php3/stat/4/idpl/13083198/dir/opensuse_11.x/com/librdmacm-1.0.8-10.2.i586.rpm.html";
$f[]="http://rpm.pbone.net/index.php3/stat/4/idpl/13082810/dir/opensuse_11.x/com/libibcommon-devel-1.1.2_20081020-6.2.i586.rpm.html";
$f[]="http://rpm.pbone.net/index.php3/stat/4/idpl/13082811/dir/opensuse_11.x/com/libibcommon1-1.1.2_20081020-6.2.i586.rpm.html";
$f[]="http://rpm.pbone.net/index.php3/stat/4/idpl/13082815/dir/opensuse_11.x/com/libibumad1-1.2.3_20081118-3.2.i586.rpm.html";
$f[]="http://rpm.pbone.net/index.php3/stat/4/idpl/11097915/dir/opensuse_11.x/com/mpi-selector-1.0.2-4.12.i586.rpm.html";
$f[]="http://rpm.pbone.net/index.php3/stat/4/idpl/13080739/dir/opensuse_11.x/com/compat-dapl-1.2.12-3.2.i586.rpm.html";
$f[]="http://rpm.pbone.net/index.php3/stat/4/idpl/13082814/dir/opensuse_11.x/com/libibumad-devel-1.2.3_20081118-3.2.i586.rpm.html";
$f[]="http://rpm.pbone.net/index.php3/stat/4/idpl/13082816/dir/opensuse_11.x/com/libibverbs-1.1.2-9.2.i586.rpm.html";
$f[]="http://rpm.pbone.net/index.php3/stat/4/idpl/13082817/dir/opensuse_11.x/com/libibverbs-devel-1.1.2-9.2.i586.rpm.html";
$f[]="http://rpm.pbone.net/index.php3/stat/4/idpl/13083921/dir/opensuse_11.x/com/openmpi-1.2.8-7.5.i586.rpm.html";
$f[]="http://rpm.pbone.net/index.php3/stat/4/idpl/13083922/dir/opensuse_11.x/com/openmpi-devel-1.2.8-7.5.i586.rpm.html";
$f[]="http://rpm.pbone.net/index.php3/stat/4/idpl/15613847/dir/opensuse_11.x/com/libboost_graph1_44_0-1.44.0-5.1.i586.rpm.html";
$f[]="http://rpm.pbone.net/index.php3/stat/4/idpl/15613848/dir/opensuse_11.x/com/libboost_iostreams1_44_0-1.44.0-5.1.i586.rpm.html";
$f[]="http://rpm.pbone.net/index.php3/stat/4/idpl/15613849/dir/opensuse_11.x/com/libboost_math1_44_0-1.44.0-5.1.i586.rpm.html";
$f[]="http://rpm.pbone.net/index.php3/stat/4/idpl/15613858/dir/opensuse_11.x/com/libboost_test1_44_0-1.44.0-5.1.i586.rpm.html";
$f[]="http://rpm.pbone.net/index.php3/stat/4/idpl/15613851/dir/opensuse_11.x/com/libboost_program_options1_44_0-1.44.0-5.1.i586.rpm.html";
$f[]="http://rpm.pbone.net/index.php3/stat/4/idpl/15613852/dir/opensuse_11.x/com/libboost_python1_44_0-1.44.0-5.1.i586.rpm.html";
$f[]="http://rpm.pbone.net/index.php3/stat/4/idpl/15613856/dir/opensuse_11.x/com/libboost_signals1_44_0-1.44.0-5.1.i586.rpm.html";
$f[]="http://rpm.pbone.net/index.php3/stat/4/idpl/15613857/dir/opensuse_11.x/com/libboost_system1_44_0-1.44.0-5.1.i586.rpm.html";
$f[]="http://rpm.pbone.net/index.php3/stat/4/idpl/15613859/dir/opensuse_11.x/com/libboost_thread1_44_0-1.44.0-5.1.i586.rpm.html";
$f[]="http://rpm.pbone.net/index.php3/stat/4/idpl/15613860/dir/opensuse_11.x/com/libboost_wave1_44_0-1.44.0-5.1.i586.rpm.html";
$f[]="http://rpm.pbone.net/index.php3/stat/4/idpl/15613854/dir/opensuse_11.x/com/libboost_regex1_44_0-1.44.0-5.1.i586.rpm.html";
$f[]="http://rpm.pbone.net/index.php3/stat/4/idpl/15613853/dir/opensuse_11.x/com/libboost_random1_44_0-1.44.0-5.1.i586.rpm.html	";
$f[]="http://rpm.pbone.net/index.php3/stat/4/idpl/15613850/dir/opensuse_11.x/com/libboost_mpi1_44_0-1.44.0-5.1.i586.rpm.html";
    
while (list ($num, $ligne) = each ($f) ){
	if(preg_match("#com\/(.*?)\.html#",$ligne,$re)){
		echo "<a href=\"$ligne\" target=_new>{$re[1]}</a><br>\n";
		
	}
	
}

return ;

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.templates.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.ini.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.squid.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::framework/class.unix.inc\n";}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::frame.class.inc\n";}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/class.categorize.externals.inc');

$u=new unix();
print_r($u->KERNEL_CONFIG());


?>