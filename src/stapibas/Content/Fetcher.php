<?php
namespace stapibas;

class Content_Fetcher
{
    public $db;
    public $log;

    public function __construct(Dependencies $deps)
    {
        $this->deps = $deps;
        $this->db   = $deps->db;
        $this->log  = $deps->log;
    }

    /**
     * Fetches HTML content of all pingbacks that are marked as "needs update"
     */
    public function updateAll()
    {
        $this->log->info('Fetching pingback content..');
        $res = $this->db->query(
            'SELECT * FROM pingbacks'
            . ' WHERE p_use = 1' . $this->sqlNeedsUpdate()
        );
        $items = 0;
        while ($pingbackRow = $res->fetch(\PDO::FETCH_OBJ)) {
            ++$items;
            $this->updateContent($pingbackRow);
        }
        $this->log->info('Finished fetching %d pingback sources.', $items);
    }

    protected function updateContent($pingbackRow)
    {
        $this->log->info(
            'Fetching pingback source #%d: %s',
            $pingbackRow->p_id, $pingbackRow->p_source
        );

        $req = new \HTTP_Request2($pingbackRow->p_source);
        $req->setHeader('User-Agent', 'stapibas');
        $req->setHeader(
            'Accept',
            'application/xhtml+xml; q=1'
            . ', application/xml; q=0.9'
            . ', text/xml; q=0.9'
            . ', text/html; q=0.5'
            . ', */*; q=0.1'
        );

        $res = $req->send();
        if (intval($res->getStatus() / 100) != 2) {
            //no 2xx is an error for us
            $this->log->err('Error fetching pingback source content');
            return;
        }

        $qPid = $this->db->quote($pingbackRow->p_id);
        $this->db->exec('DELETE FROM pingbackcontent WHERE pc_p_id = ' . $qPid);
        $this->db->exec('DELETE FROM rbookmarks WHERE rb_p_id = ' . $qPid);
        $this->db->exec('DELETE FROM rcomments  WHERE rc_p_id = ' . $qPid);
        $this->db->exec('DELETE FROM rlinks     WHERE rl_p_id = ' . $qPid);

        $this->db->exec(
            'INSERT INTO pingbackcontent SET'
            . '  pc_p_id = ' . $qPid
            . ', pc_mime_type = '
            . $this->db->quote($res->getHeader('content-type'))
            . ', pc_fulltext = ' . $this->db->quote($res->getBody())
        );
        $this->db->exec(
            'UPDATE pingbacks'
            . ' SET p_needs_update = 0'
            . ' WHERE p_id = ' . $this->db->quote($pingbackRow->p_id)
        );
    }


    protected function sqlNeedsUpdate()
    {
        if ($this->deps->options['force']) {
            return '';
        }
        return ' AND p_needs_update = 1';
    }

}

?>
