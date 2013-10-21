<?php
namespace stapibas;

class Linkback_DbStorage
    implements \PEAR2\Services\Linkback\Server\Callback\IStorage,
    \PEAR2\Services\Linkback\Server\Callback\ILink,
    \PEAR2\Services\Linkback\Server\Callback\ITarget
{
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Verifies that the given target URI exists in our system.
     *
     * @param string $target Target URI that got linked to
     *
     * @return boolean True if the target URI exists, false if not
     *
     * @throws Exception When something fatally fails
     */
    public function verifyTargetExists($target)
    {
        $res = $this->db->query(
            'SELECT COUNT(*) as count FROM linkbacktargets'
            . ' WHERE ' . $this->db->quote($target) . ' LIKE lt_url'
        );
        $answer = $res->fetch(\PDO::FETCH_OBJ);
        if ($answer->count == 0) {
            throw new \Exception(
                'The specified target URI is not allowed as target.',
                33
            );
        }

        return true;
    }

    public function storeLinkback(
        $target, $source, $sourceBody, \HTTP_Request2_Response $res
    ) {
        if ($this->alreadyExists($target, $source)) {
            throw new \Exception(
                'Linkback from ' . $source . ' to ' . $target
                . ' has already been registered.',
                48
            );
        }
        $stmt = $this->db->prepare(
            'INSERT INTO linkbacks SET'
            . '  l_source = :source'
            . ', l_target = :target'
            . ', l_time = NOW()'
            . ', l_client_ip = :ip'
            . ', l_client_agent = :agent'
            . ', l_client_referer = :referer'
            . ', l_needs_review = 1'
            . ', l_use = 1'
            . ', l_needs_update = 1'
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
            'SELECT COUNT(*) as count FROM linkbacks'
            . ' WHERE l_source = ' . $this->db->quote($source)
            . ' AND l_target = ' . $this->db->quote($target)
        );
        $answer = $res->fetch(\PDO::FETCH_OBJ);
        return $answer->count > 0;
    }

    /**
     * Verifies that a link from $source to $target exists.
     *
     * @param string $target     Target URI that should be linked in $source
     * @param string $source     Linkback source URI that should link to target
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
