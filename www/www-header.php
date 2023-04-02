<?php
namespace stapibas;
header('HTTP/1.0 500 Internal Server error');
header('Content-type: text/plain');

require_once __DIR__ . '/../data/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

$db = new PDO($dbdsn, $dbuser, $dbpass);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
?>
