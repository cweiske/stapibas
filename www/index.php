<?php
if (!isset($_SERVER['REQUEST_SCHEME'])) {
    $_SERVER['REQUEST_SCHEME'] = 'http';
}
$baseurl = $_SERVER['REQUEST_SCHEME'] . '://'
    . $_SERVER['HTTP_HOST']
    . preg_replace('#\?.+$#', '', $_SERVER['REQUEST_URI']);
?>
<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
 <head>
  <title>stapibas: The standalone linkback server</title>
  <meta name="robots" content="noindex,nofollow"/>
 </head>
 <body>
  <h3>Linkback stats</h3>
  <p>
   Add the following bookmarklet to your browser's bookmarks (right-click):
  </p>
  <p>
   <a href="javascript:s=document.createElement('script');s.src='<?php echo $baseurl; ?>js/show-links.js';document.body.appendChild(s);">stapibas linkback stats</a>
  </p>
 </body>
</html>
