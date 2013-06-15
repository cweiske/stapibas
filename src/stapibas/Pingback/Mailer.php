<?php
namespace stapibas;

class Pingback_Mailer
    implements \PEAR2\Services\Pingback\Server\Callback\IStorage
{
    public function storePingback(
        $target, $source, $sourceBody, \HTTP_Request2_Response $res
    ) {
        mail(
            'cweiske@cweiske.de',
            'New pingback',
            "A pingback just came in, for\n"
            . '> '  . $target . "\n"
            . "from\n"
            . '> ' . $source . "\n"
            . "\n\nLove, stapibas",
            "From: stapibas <server@cweiske.de>"
        );
    }
}
?>
