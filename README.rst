********
stapibas
********
The standalone Linkback server, written in PHP.

- Receives linkbacks (`webmention`__ and `pingbacks`__)
- Watches your website's Feed to send out linkbacks to all linked URLs

Alternative to `Trackback ‘em All`__ and `Telegraph`__

__ https://www.w3.org/TR/webmention/
__ http://www.hixie.ch/specs/pingback/pingback
__ http://scott.yang.id.au/code/trackback-em-all/
__ https://telegraph.p3k.io/


=================
Linkback receiver
=================
stapibas receives linkbacks (webmentions + pingbacks) for your website
and puts them into a database.

It also sends them as email to a configured address.


Setup
=====
Let your website send out the following HTTP headers::

  X-Pingback: http://stapibas.example.org/xmlrpc.php
  Link: '<http://stapibas.example.org/xmlrpc.php>; rel="webmention"'

In Apache you can do this with the following configuration::

  Header set X-Pingback "http://stapibas.example.org/xmlrpc.php"
  Header append Link '<http://stapibas.example.org/xmlrpc.php>; rel="webmention"'


Now, whitelist your domain in the database:
Add an ``lt_url`` of ``https://example.org/%`` in the ``linkbacktargets`` table.

That's all.

.. note::
   stapibas does not display the linkbacks in any way - you have to do this yourself.

   If you're looking for a ready-made solution, look at the tools listed
   on https://indieweb.org/Webmention



===============
Linkback sender
===============
stapibas is able to send linkbacks out to other websites at behalf of
your website.

It does this by watching your website's Atom (or RSS) feed.
Whenever it changes, it fetches the articles that are new or got updated and
sends out pingbacks to the remote websites.

It only works on links that are inside an ``e-content`` section
that itself has to be inside a `h-entry`__.

__ http://microformats.org/wiki/h-entry


Setup
=====
Add your feed URL::

  $ ./bin/stapibas feed add http://example.org/feed.atom

Whenever you update your website, tell stapibas about it via a
HTTP POST request, sending the feed URL::

  $ curl -d url=http://example.org/feed.atom http://stapibas.example.org/request-feed-update.php

This tells stapibas to check this feed the next time the pinger runs.

.. note::
   stapibas does not check itself if the feed changed!

   You need to notify it manually.


Run the pinger
==============
Run stapibas every 5 minutes or every hour to check for feed updates,
extract new URLs from the feed and send pingbacks to them.

::

   $ php bin/stapibas


============
Dependencies
============
- PHP 8.0+
- PDO
- PHP libraries that get installed with ``composer install --no-dev``
