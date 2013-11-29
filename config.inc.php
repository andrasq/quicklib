<?

error_reporting(E_ALL & ~E_NOTICE);
ini_set('precision', max(20, ini_get('precision')));

if (!function_exists('add_include_path')) {
    function add_include_path($path) {
	// append path to include_path if not already contained
	if (!in_array($path, explode(':', $includepath = get_include_path())))
	    set_include_path("$includepath:$path");
    }
}


// set_document_root.php and config.inc.php must live in the same directory
include dirname(__FILE__) . '/set_document_root.php';
add_include_path($_SERVER['DOCUMENT_ROOT']);
add_include_path($_SERVER['DOCUMENT_ROOT'].'/lib');

require_once 'Quick/Autoloader.php';

$al =
Quick_Autoloader::getInstance()
//    ->addSearchTree(getcwd()."/lib", ".php")
//    ->addSearchTree(getcwd()."/ext", ".php")
    ->install()
    ;

