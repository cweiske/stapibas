<?php
namespace stapibas;
/**
 * Simply stores all pingbacks in the database.
 */
require_once 'www-header.php';

$s = new \PEAR2\Services\Linkback\Server();
$s->addCallback(new Linkback_DbStorage($db));
$s->addCallback(new Linkback_Mailer());
$s->run();
?>
