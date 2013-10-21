<?php
namespace stapibas;

class Linkback_Mailer
    implements \PEAR2\Services\Linkback\Server\Callback\IStorage
{
    public function storeLinkback(
        $target, $source, $sourceBody, \HTTP_Request2_Response $res
    ) {
        mail(
            'cweiske@cweiske.de',
            'New linkback',
            "A linkback just came in, for\n"
            . '> '  . $target . "\n"
            . "from\n"
            . '> ' . $source . "\n"
            . "\n\nLove, stapibas",
            "From: stapibas <server@cweiske.de>"
        );
    }
}
?>
