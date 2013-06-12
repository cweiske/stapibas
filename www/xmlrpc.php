<?php
/**
 * Simply stores all pingbacks in the database.
 */
require_once __DIR__ . '/../data/config.php';
require_once 'stapibas/autoloader.php';

$db = new PDO($dbdsn, $dbuser, $dbpass);

class PingbackStorage
    implements \PEAR2\Services\Pingback\Server\Callback\IStorage,
    \PEAR2\Services\Pingback\Server\Callback\ILink
{
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function storePingback(
        $target, $source, $sourceBody, \HTTP_Request2_Response $res
    ) {
        $stmt = $this->db->prepare(
            'INSERT INTO pingbacks'
            . ' (p_source, p_target, p_time, p_client_ip, p_client_agent, p_client_referer)'
            . ' VALUES(:source, :target, NOW(), :ip, :agent, :referer)'
        );
        $stmt->execute(
            array(
                ':source'  => $source,
                ':target'  => $target,
                ':ip'      => isset($_SERVER['REMOTE_ADDR'])
                    ? $_SERVER['REMOTE_ADDR'] : '',
                ':agent'   => isset($_SERVER['HTTP_USER_AGENT'])
                    ? $_SERVER['HTTP_USER_AGENT'] : '',
                ':referer' => isset($_SERVER['HTTP_REFERER'])
                    ? $_SERVER['HTTP_REFERER'] : '',
            )
        );
    }

    /**
     * Verifies that a link from $source to $target exists.
     *
     * @param string $target     Target URI that should be linked in $source
     * @param string $source     Pingback source URI that should link to target
     * @param string $sourceBody Content of $source URI
     * @param object $res        HTTP response from fetching $source
     *
     * @return boolean True if $source links to $target
     *
     * @throws Exception When something fatally fails
     */
    public function verifyLinkExists(
        $target, $source, $sourceBody, \HTTP_Request2_Response $res
    ) {
        return false;
    }
}

class PingbackMailer
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

$s = new \PEAR2\Services\Pingback\Server();
$s->addCallback(new PingbackStorage($db));
$s->addCallback(new PingbackMailer());
$s->run();
?>
