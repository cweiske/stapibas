<?php
/**
 * Request that a feed shall get updated, marks it as "requires update"
 * in the database
 *
 * Has to be called via POST, url given in parameter "url".
 */
require_once 'www-header.php';

if (!isset($_POST['url'])) {
    header('HTTP/1.0 400 Bad Request');
    echo "HTTP POST 'url' parameter missing\n";
    exit(1);
}
$url = $_POST['url'];
if ($url === '') {
    header('HTTP/1.0 400 Bad Request');
    echo "'url' parameter is empty\n";
    exit(1);
}
if (filter_var($url, FILTER_VALIDATE_URL) === false) {
    header('HTTP/1.0 400 Bad Request');
    echo "Invalid URL given\n";
    exit(1);
}


$res = $db->query(
    'SELECT f_id, f_needs_update FROM feeds WHERE f_url = ' . $db->quote($url)
);
$row = $res->fetch(PDO::FETCH_OBJ);
if ($row === false) {
    header('HTTP/1.0 404 Not Found');
    echo "Feed URL could not be found in database\n";
    exit(1);
}
if ($row->f_needs_update == 1) {
    header('HTTP/1.0 200 OK');
    echo "Already in the queue\n";
    exit(0);
}

$db->exec(
    'UPDATE feeds SET f_needs_update = 1'
    . ' WHERE f_id = ' . $db->quote($row->f_id)
);

header('HTTP/1.0 202 Accepted');
echo "Feed has been put into the queue\n";
exit(0);
?>
