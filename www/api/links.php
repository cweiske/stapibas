<?php
namespace stapibas;
/**
 * Fetch the list of links in the given URL as well as their ping status.
 * Tells you if it was pinged, and what result it had.
 */

require_once '../www-header.php';

if (!isset($_GET['url'])) {
    header('HTTP/1.0 400 Bad Request');
    echo "url missing\n";
    exit(1);
}

$url = $_GET['url'];
$res = $db->query(
    'SELECT * FROM feedentries WHERE fe_url = ' . $db->quote($url)
);
$urlRow = $res->fetch(PDO::FETCH_OBJ);
if ($urlRow === false) {
    header('HTTP/1.0 404 Not Found');
    echo "Url not found\n";
    exit(1);
}
$json = (object) array(
    'url'         => $urlRow->fe_url,
    'updated'     => $urlRow->fe_updated,
    'needsUpdate' => (bool) $urlRow->fe_needs_update,
    'links'       => array()
);

$res = $db->query(
    'SELECT * FROM feedentryurls'
    . ' WHERE feu_fe_id = ' . $db->quote($urlRow->fe_id)
);
while ($linkRow = $res->fetch(\PDO::FETCH_OBJ)) {
    $status = null;
    if (!$linkRow->feu_pinged) {
        $status = 'queued';
    } else if ($linkRow->feu_retry && $linkRow->feu_tries < 5) {
        $status = 'pinging';
    } else if ($linkRow->feu_error) {
        $status = 'error';
    } else {
        $status = 'ok';
    }
    $json->links[] = (object) array(
        'url'     => $linkRow->feu_url,
        'pinged'  => (bool) $linkRow->feu_pinged,
        'updated' => $linkRow->feu_updated,
        'status'  => $status,
        'error'   => (object) array(
            'code'    => $linkRow->feu_error_code,
            'message' => $linkRow->feu_error_message
        ),
        'tries'   => $linkRow->feu_tries
    );
}

header('Content-type: application/json');
echo json_encode($json) . "\n";
?>
