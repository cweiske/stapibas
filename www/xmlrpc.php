<?php
namespace stapibas;
/**
 * Simply stores all pingbacks in the database.
 */
require_once '../vendor/autoload.php';

$s = new \PEAR2\Services\Linkback\Server();

$fs = new \PEAR2\Services\Linkback\Server\Callback\FetchSource();
$fs->getRequest()->setConfig(
    array(
        'ssl_verify_peer' => false,
        'ssl_verify_host' => false
    )
);
$callbacks = array(
    $fs,
    new \PEAR2\Services\Linkback\Server\Callback\LinkExists(),
    new Linkback_DbStorage($db),
    new Linkback_Mailer()
);
$s->setCallbacks($callbacks);
$s->run();
?>
