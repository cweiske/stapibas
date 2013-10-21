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
     * Fetches HTML content of all linkbacks that are marked as "needs update"
     */
    public function updateAll()
    {
        $this->log->info('Fetching linkback content..');
        $res = $this->db->query(
            'SELECT * FROM linkbacks'
            . ' WHERE l_use = 1' . $this->sqlNeedsUpdate()
        );
        $items = 0;
        while ($pingbackRow = $res->fetch(\PDO::FETCH_OBJ)) {
            ++$items;
            $this->updateContent($pingbackRow);
        }
        $this->log->info('Finished fetching %d linkback sources.', $items);
    }

    protected function updateContent($pingbackRow)
    {
        $this->log->info(
            'Fetching pingback source #%d: %s',
            $pingbackRow->l_id, $pingbackRow->l_source
        );

        $req = new \HTTP_Request2($pingbackRow->l_source);
        $req->setHeader('User-Agent', 'stapibas');
        $req->setHeader(
            'Accept',
            'application/xhtml+xml; q=1'
            . ', text/html; q=0.5'
        );

        $res = $req->send();
        if (intval($res->getStatus() / 100) != 2) {
            //no 2xx is an error for us
            $this->log->err('Error fetching pingback source content');
            return;
        }

        $qLid = $this->db->quote($pingbackRow->l_id);
        $this->db->exec('DELETE FROM linkbackcontent WHERE lc_l_id = ' . $qLid);
        $this->db->exec('DELETE FROM rbookmarks WHERE rb_l_id = ' . $qLid);
        $this->db->exec('DELETE FROM rcomments  WHERE rc_l_id = ' . $qLid);
        $this->db->exec('DELETE FROM rlinks     WHERE rl_l_id = ' . $qLid);

        $this->db->exec(
            'INSERT INTO linkbackcontent SET'
            . '  lc_l_id = ' . $qLid
            . ', lc_mime_type = '
            . $this->db->quote($res->getHeader('content-type'))
            . ', lc_fulltext = ' . $this->db->quote($res->getBody())
        );
        $this->db->exec(
            'UPDATE linkbacks'
            . ' SET l_needs_update = 0'
            . ' WHERE l_id = ' . $this->db->quote($pingbackRow->l_id)
        );
    }


    protected function sqlNeedsUpdate()
    {
        if ($this->deps->options['force']) {
            return '';
        }
        return ' AND l_needs_update = 1';
    }

}

?>
