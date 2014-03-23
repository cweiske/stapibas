<?php
namespace stapibas;
/**
 * Render the bookmarks/comments/links for a given URL
 *
 * @param string $url URL to get content for
 */
require_once 'www-header.php';

if (!isset($_GET['url'])) {
    header('HTTP/1.0 400 Bad Request');
    echo "HTTP POST 'url' parameter missing\n";
    exit(1);
}
$url = $_GET['url'];
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


$deps = new Dependencies();
$deps->db = new PDO($dbdsn, $dbuser, $dbpass);
$deps->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$deps->options = array(
    'template_dir' => __DIR__ . '/../data/templates/default/'
);

$r = new Renderer_Html($deps);
echo $r->render($url);
?>
