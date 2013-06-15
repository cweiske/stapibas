<?php
namespace stapibas;

class Pingback_DbStorage
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
        if ($this->alreadyExists($target, $source)) {
            throw new \Exception(
                'Pingback from ' . $source . ' to ' . $target
                . ' has already been registered.',
                48
            );
        }
        $stmt = $this->db->prepare(
            'INSERT INTO pingbacks SET'
            . '  p_source = :source'
            . ', p_target = :target'
            . ', p_time = NOW()'
            . ', p_client_ip = :ip'
            . ', p_client_agent = :agent'
            . ', p_client_referer = :referer'
            . ', p_needs_review = 1'
            . ', p_use = 1'
            . ', p_needs_update = 1'
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

    protected function alreadyExists($target, $source)
    {
        $res = $this->db->query(
            'SELECT COUNT(*) as count FROM pingbacks'
            . ' WHERE p_source = ' . $this->db->quote($source)
            . ' AND p_target = ' . $this->db->quote($target)
        );
        $answer = $res->fetch(\PDO::FETCH_OBJ);
        return $answer->count > 0;
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
?>
