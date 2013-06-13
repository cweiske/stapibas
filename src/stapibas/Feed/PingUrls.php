<?php
namespace stapibas;

/**
 * Pings all URLs that have not been pinged yet
 */
class Feed_PingUrls
{
    public $db;
    public $log;
    public $pbc;

    public function __construct()
    {
        $this->pbc = new \PEAR2\Services\Pingback\Client();

        $req = new \HTTP_Request2();
        $req->setConfig(
            array(
                'ssl_verify_peer' => false,
                'ssl_verify_host' => false
            )
        );
        $this->pbc->setRequest($req);
        $this->pbc->setDebug(true);
    }

    public function pingAll()
    {
        $this->log->info('Pinging all URLs..');
        $res = $this->db->query(
            'SELECT fe_url, feu_id, feu_url FROM feedentries, feedentryurls'
            . ' WHERE fe_id = feu_fe_id AND feu_active = 1 AND feu_pinged = 0'
        );
        $items = 0;
        while ($row = $res->fetch(\PDO::FETCH_OBJ)) {
            $this->log->info(
                sprintf('Pinging URL #%d: %s', $row->feu_id, $row->feu_url)
            );
            $this->ping($row);
            ++$items;
        }
        $this->log->info(sprintf('Finished pinging %d URLs.', $items));
    }

    public function ping($row)
    {
        $from = $row->fe_url;
        $to   = $row->feu_url;

        try {
            $res = $this->pbc->send($from, $to);
        } catch (\Exception $e) {
            $this->log->err('Exception: ' . $e->getMessage());
            $this->db->exec(
                'UPDATE feedentryurls SET'
                . '  feu_pinged = 1'
                . ', feu_updated = NOW()'
                . ', feu_error = ' . $this->db->quote($e->getMessage())
                . ' WHERE feu_id = ' . $this->db->quote($row->feu_id)
            );
            return;
        }

        if (!$res->isError()) {
            //all fine
            $this->log->info('ok');
            $this->db->exec(
                'UPDATE feedentryurls SET'
                . '  feu_pinged = 1'
                . ', feu_updated = NOW()'
                . ', feu_error = ""'
                . ' WHERE feu_id = ' . $this->db->quote($row->feu_id)
            );
        } else {
            //error
            $this->log->err('Error: ' . $res->getCode() . ': ' . $res->getMessage());
            $this->log->info(
                'Pingback response: Status code ' . $res->getResponse()->getStatus()
                . ', headers: ' . print_r($res->getResponse()->getHeader(), true)
                . ', body: ' . $res->getResponse()->getBody()
            );
            $this->db->exec(
                'UPDATE feedentryurls SET'
                . '  feu_pinged = 1'
                . ', feu_updated = NOW()'
                . ', feu_error = '
                . $this->db->quote($res->getCode() . ': ' . $res->getMessage())
                . ' WHERE feu_id = ' . $this->db->quote($row->feu_id)
            );
        }
    }
}
?>
