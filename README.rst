********
stapibas
********
The standalone Pingback server, written in PHP.



=================
Pingback receiver
=================
stapibas receives pingbacks for your website and puts them into a database.


Setup
=====
Let your website send out the following HTTP header::

  X-Pingback: http://stapibas.example.org/xmlrpc.php

That's all.


===============
Pingback sender
===============
stapibas is able to send pingbacks out to other websites at behalf of
your website.

It does this by watching your website's Atom feed.
Whenever it changes, it fetches the articles that are new or got updated and
sends out pingbacks to the remote websites.


Setup
=====
Insert your feed URL in the ``feeds`` database table.

Whenever you update your website, tell stapibas about it via a
HTTP POST request::

  $ curl -d url=http://example.org/feed/ http://stapibas.example.org/request-feed-update.php
