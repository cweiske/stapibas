********
stapibas
********
The standalone Pingback server, written in PHP.

- Receives linkbacks (webmention and pingbacks)
- Watches your website's Feed to send out linkbacks to all linked URLs

Alternative to `Trackback ‘em All`__

__ http://scott.yang.id.au/code/trackback-em-all/


============
Dependencies
============
- PHP 5.3+
- PDO
- `Console_CommandLine`__
- `Net_URL2`__
- `HTTP_Request2`__
- `PEAR2 Services_Linkback`__
- `SimplePie`__

__ http://pear.php.net/package/Console_CommandLine
__ http://pear.php.net/package/Net_URL2
__ http://pear.php.net/package/HTTP_Request2
__ https://github.com/pear2/Services_Linkback
__ http://simplepie.org/


=================
Pingback receiver
=================
stapibas receives pingbacks for your website and puts them into a database.

It also sends them as email to a configured address.


Setup
=====
Let your website send out the following HTTP headers::

  X-Pingback: http://stapibas.example.org/xmlrpc.php
  Header set Link '<http://stapibas.example.org/xmlrpc.php>; rel="webmention"'

Now, whitelist your domain in the database:
Add an ``lt_url`` of ``https://example.org/%`` in the ``linkbacktargets`` table.

That's all.


===============
Pingback sender
===============
stapibas is able to send pingbacks out to other websites at behalf of
your website.

It does this by watching your website's Atom (or RSS) feed.
Whenever it changes, it fetches the articles that are new or got updated and
sends out pingbacks to the remote websites.


Setup
=====
Add your feed URL::

  $ ./bin/stapibas feed add http://example.org/feed.atom

Whenever you update your website, tell stapibas about it via a
HTTP POST request, sending the feed URL::

  $ curl -d url=http://example.org/feed.atom http://stapibas.example.org/request-feed-update.php

This tells stapibas to check this feed the next time the pinger runs.


Run the pinger
==============
Run stapibas every 5 minutes or every hour to check for feed updates,
extract new URLs from the feed and send pingbacks to them.

::

   $ php bin/stapibas
