********
stapibas
********
The standalone Pingback server, written in PHP.

Alternative to `Trackback ‘em All`__

__ http://scott.yang.id.au/code/trackback-em-all/


============
Dependencies
============
- PHP 5.3+
- PDO
- `Net_URL2`__
- `HTTP_Request2`__
- `PEAR2 Services_Pingback`__
- `SimplePie`__

__ http://pear.php.net/package/Net_URL2
__ http://pear.php.net/package/HTTP_Request2
__ https://github.com/pear2/Services_Pingback
__ http://simplepie.org/

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


Run the pinger
==============
Run stapibas every 5 minutes or every hour to check for feed updates,
extract new URLs from the feed and send pingbacks to them.

::

   $ php bin/stapibas
