<?

ini_set('precision', max(20, ini_get('precision')));

error_reporting(E_ALL);
//error_reporting(E_ALL & ~E_NOTICE);

// caller should have set up TEST_ROOT and EXT_ROOT

// docroot is above the testroot
$_SERVER['DOCUMENT_ROOT'] = TEST_ROOT . '/../';

set_include_path(get_include_path() . ":{$_SERVER['DOCUMENT_ROOT']}:" . EXT_ROOT."/PHPUnit-3.4.15");
require_once EXT_ROOT . '/PHPUnit-3.4.15/PHPUnit/Util/Filter.php';
require_once EXT_ROOT . '/PHPUnit-3.4.15/PHPUnit/TextUI/TestRunner.php';

// set up the autoloader
require_once 'lib/Quick/Autoloader.php';
Quick_Autoloader::getInstance()
    ->addSearchTree(TEST_ROOT . "/../lib", ".php")
    ->addSearchTree(TEST_ROOT . "/../ext", ".php")
    ->install()
    ;

// preload classes that should not auto-detect as tests
class_exists('Quick_Test_Case');
class_exists('Quick_Test_Timer');

if (file_exists(dirname(__FILE__) . '/test_config_local.inc.php'))
    include 'test_config_local.inc.php';
