<?

/**
 * If not already set, point the document root at the base of the include directories.
 * If set this does nothing.
 *
 * 2013-02-09 - AR.
 */

if (empty($_SERVER['DOCUMENT_ROOT'])) {
    if (empty($_SERVER['SCRIPT_FILENAME'])) {
	// not running a script
	$__path = getcwd();
    }
    elseif ($_SERVER['SCRIPT_FILENAME'][0] === '/') {
	// script run by absolute pathname, docroot is given by script path
	$__path = $_SERVER['SCRIPT_FILENAME'];
    }
    elseif (strncmp($_SERVER['SCRIPT_FILENAME'], './', 2) === 0) {
	// running a script from ./
	$__path = getcwd();
    }
    else {
	// running a script by relative path
	$__path = getcwd()."/{$_SERVER['SCRIPT_FILENAME']}";
    }

    if (preg_match(':(^.*src/php/):', $__path, $mm))
	$_SERVER['DOCUMENT_ROOT'] = $mm[1];
    elseif (preg_match(':(^.*/(htdocs|www|html|src)/):', $__path, $mm))
	$_SERVER['DOCUMENT_ROOT'] = rtrim($mm[1], '/');

    unset($__path);
}
