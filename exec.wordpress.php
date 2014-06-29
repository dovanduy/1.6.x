<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/wordpress.index.progress";
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');
$GLOBALS["SERVICE_NAME"]="FetchMail Daemon";

$GLOBALS["SINGLE_DEBUG"]=false;
$GLOBALS["NOT_FORCE_PROXY"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["BYCRON"]=false;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["CHANGED"]=false;
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--force-nightly#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;$GLOBALS["FORCE"]=true;$GLOBALS["FORCE_NIGHTLY"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){
	$GLOBALS["VERBOSE"]=true;
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string'," Fatal..:");
	ini_set('error_append_string',"\n");
}

config();

function config(){
	$unix=new unix();
	$q=new mysql();
	$sock=new sockets();
	$DB_HOST="localhost:$q->SocketPath";
	
	$Salt=$unix->GetUniqueID();
	$WordPressDBPass=$sock->GET_INFO("WordPressDBPass");
	$wordpressDB=$sock->GET_INFO("WordPressDB");
	$DB_USER=$sock->GET_INFO("WordPressDBUser");
	if($DB_USER==null){$DB_USER="wordpress";}
	if($WordPressDBPass==null){$WordPressDBPass=md5(time());}
	$sock->SET_INFO("WordPressDBPass", $WordPressDBPass);
	$DB_PASSWORD=$WordPressDBPass;
	$Salts=$sock->GET_INFO("WordPressSalts");
	if($Salts==null){
		$TMP=$unix->FILE_TEMP();
		$curl=new ccurl("https://api.wordpress.org/secret-key/1.1/salt/");
		if(!$curl->GetFile("$TMP")){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Unable to download salts !!\n";}
			return;
		}
		
	}

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: MySQL user...........: $DB_USER\n";}
	
$f[]="<?php";
$f[]="/**";
$f[]=" * The base configurations of the WordPress.";
$f[]=" *";
$f[]=" * This file has the following configurations: MySQL settings, Table Prefix,";
$f[]=" * Secret Keys, WordPress Language, and ABSPATH. You can find more information";
$f[]=" * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing";
$f[]=" * wp-config.php} Codex page. You can get the MySQL settings from your web host.";
$f[]=" *";
$f[]=" * This file is used by the wp-config.php creation script during the";
$f[]=" * installation. You don't have to use the web site, you can just copy this file";
$f[]=" * to \"wp-config.php\" and fill in the values.";
$f[]=" *";
$f[]=" * @package WordPress";
$f[]=" */";
$f[]="";
$f[]="// ** MySQL settings - You can get this info from your web host ** //";
$f[]="/** The name of the database for WordPress */";
$f[]="define('DB_NAME', 'wordpress');";
$f[]="";
$f[]="/** MySQL database username */";
$f[]="define('DB_USER', '$DB_USER');";
$f[]="";
$f[]="/** MySQL database password */";
$f[]="define('DB_PASSWORD', '$DB_PASSWORD');";
$f[]="";
$f[]="/** MySQL hostname */";
$f[]="define('DB_HOST', '$DB_HOST');";
$f[]="";
$f[]="/** Database Charset to use in creating database tables. */";
$f[]="define('DB_CHARSET', 'utf8');";
$f[]="";
$f[]="/** The Database Collate type. Don't change this if in doubt. */";
$f[]="define('DB_COLLATE', '');";
$f[]="";
$f[]="/**#@+";
$f[]=" * Authentication Unique Keys and Salts.";
$f[]=" *";
$f[]=" * Change these to different unique phrases!";
$f[]=" * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}";
$f[]=" * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.";
$f[]=" *";
$f[]=" * @since 2.6.0";
$f[]=" */";
$f[]=$Salts;
$f[]="";
$f[]="/**#@-*/";
$f[]="";
$f[]="/**";
$f[]=" * WordPress Database Table prefix.";
$f[]=" *";
$f[]=" * You can have multiple installations in one database if you give each a unique";
$f[]=" * prefix. Only numbers, letters, and underscores please!";
$f[]=" */";
$f[]="\$table_prefix  = 'wp_';";
$f[]="";
$f[]="/**";
$f[]=" * WordPress Localized Language, defaults to English.";
$f[]=" *";
$f[]=" * Change this to localize WordPress. A corresponding MO file for the chosen";
$f[]=" * language must be installed to wp-content/languages. For example, install";
$f[]=" * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German";
$f[]=" * language support.";
$f[]=" */";
$f[]="define('WPLANG', '');";
$f[]="";
$f[]="/**";
$f[]=" * For developers: WordPress debugging mode.";
$f[]=" *";
$f[]=" * Change this to true to enable the display of notices during development.";
$f[]=" * It is strongly recommended that plugin and theme developers use WP_DEBUG";
$f[]=" * in their development environments.";
$f[]=" */";
$f[]="define('WP_DEBUG', false);";
$f[]="";
$f[]="/* That's all, stop editing! Happy blogging. */";
$f[]="";
$f[]="/** Absolute path to the WordPress directory. */";
$f[]="if ( !defined('ABSPATH') )";
$f[]="	define('ABSPATH', dirname(__FILE__) . '/');";
$f[]="";
$f[]="/** Sets up WordPress vars and included files. */";
$f[]="require_once(ABSPATH . 'wp-settings.php');";
$f[]="?>";
}

function build_progress($text,$pourc){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);

}
?>
