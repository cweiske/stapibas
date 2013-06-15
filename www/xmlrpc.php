<?php
namespace stapibas;
/**
 * Simply stores all pingbacks in the database.
 */
header('HTTP/1.0 500 Internal Server error');
header('Content-type: text/plain');

require_once __DIR__ . '/../data/config.php';
require_once 'stapibas/autoloader.php';

$db = new PDO($dbdsn, $dbuser, $dbpass);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$s = new \PEAR2\Services\Pingback\Server();
$s->addCallback(new Pingback_DbStorage($db));
$s->addCallback(new Pingback_Mailer());
$s->run();
?>
