<?php
require('./conf/constants.ini.php');
require('./config.php');

if($DEBUG)
{
	error_reporting(7);
	error_reporting(E_ALL|E_STRICT);
	ini_set('display_errors', 'on');
}

date_default_timezone_set('Asia/Chongqing');

//自动载入
//!defined('__DIR__') && define('__DIR__', dirname(__FILE__));
//ini_set('include_path',
//	ini_get('include_path') . PATH_SEPARATOR
//	. __DIR__ . '/../inc/' . PATH_SEPARATOR
//	. __DIR__ . '/../control/' . PATH_SEPARATOR
//	. __DIR__ . '/../' . PATH_SEPARATOR
//);
// 
//function __autoload($class)
//{
//    require_once(strtolower($class).'.php');
//}

define('S_ROOT', dirname(__FILE__).DIRECTORY_SEPARATOR);

$_SGLOBAL = array();

$mtime = explode(' ', microtime());
$_SGLOBAL['timestamp'] = $mtime[1];
$_SGLOBAL['m_secend'] = $mtime[0];
$_SGLOBAL['supe_starttime'] = $_SGLOBAL['timestamp'] + $mtime[0];

require('./inc/base.func.php');
require('./inc/util.func.php');
require('./inc/base.class.php');
require('./inc/common.class.php');

//require('./extend/client_alipays/alipayapi.php');//20170918  支付宝停用

//require(S_ROOT.'../inc/trace.func.php');

