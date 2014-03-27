<?php
/**
 * Phar stub file for stapibas. Handles startup of the .phar file.
 *
 * PHP version 5
 *
 * @category  Tools
 * @package   Stapibas
 * @author    Christian Weiske <cweiske@cweiske.de>
 * @copyright 2014 Christian Weiske
 * @license   http://www.gnu.org/licenses/agpl.html GNU AGPL v3
 * @link      http://cweiske.de/bdrem.htm
 */
if (!in_array('phar', stream_get_wrappers()) || !class_exists('Phar', false)) {
    echo "Phar extension not avaiable\n";
    exit(255);
}

$web = 'www/index.php';
$cli = 'bin/phar-stapibas.php';

/**
 * Rewrite the HTTP request path to an internal file.
 * Maps "" and "/" to "www/index.php".
 *
 * @param string $path Path from the browser, relative to the .phar
 *
 * @return string Internal path.
 */
function rewritePath($path)
{
    if ($path == '' || $path == '/') {
        return 'www/index.php';
    }
    return $path;
}

//Phar::interceptFileFuncs();
set_include_path(
    'phar://' . __FILE__
    . PATH_SEPARATOR . 'phar://' . __FILE__ . '/lib/'
);
Phar::webPhar(null, $web, null, array(), 'rewritePath');

//work around https://bugs.php.net/bug.php?id=52322
if (php_sapi_name() == 'cgi-fcgi') {
    echo "Your PHP has a bug handling phar files :/\n";
    exit(10);
}

require 'phar://' . __FILE__ . '/' . $cli;
__HALT_COMPILER();
?>
