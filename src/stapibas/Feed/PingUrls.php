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

    public function __construct(Dependencies $deps)
    {
        $this->deps = $deps;
        $this->db   = $deps->db;
        $this->log  = $deps->log;

        $this->pbc = new \PEAR2\Services\Linkback\Client();
        $req = $this->pbc->getRequest();
        $req->setConfig(
            array(
                'ssl_verify_peer' => false,
                'ssl_verify_host' => false
            )
        );
        $headers = $req->getHeaders();
        $req->setHeader('user-agent', 'stapibas / ' . $headers['user-agent']);

        $this->pbc->setDebug(true);
    }

    public function pingAll()
    {
        $this->log->info('Pinging all URLs..');
        $res = $this->db->query(
            'SELECT fe_url, feu_id, feu_url, feu_tries'
            . ' FROM feedentries, feedentryurls'
            . ' WHERE fe_id = feu_fe_id' . $this->sqlNeedsUpdate()
        );
        $items = 0;
        while ($row = $res->fetch(\PDO::FETCH_OBJ)) {
            $this->log->info(
                'Pinging URL #%d: %s', $row->feu_id, $row->feu_url
            );
            $this->ping($row);
            ++$items;
        }
        $this->log->info('Finished pinging %d URLs.', $items);
    }

    public function pingSome($urlOrIds)
    {
        $options = array();
        foreach ($urlOrIds as $urlOrId) {
            if (is_numeric($urlOrId)) {
                $options[] = 'feu_id = ' . intval($urlOrId);
            } else {
                $options[] = 'feu_url = ' . $this->db->quote($urlOrId);
            }
        }

        $this->log->info('Pinging %d URLs..', count($options));
        $res = $this->db->query(
            'SELECT fe_url, feu_id, feu_url, feu_tries'
            . ' FROM feedentries, feedentryurls'
            . ' WHERE fe_id = feu_fe_id'
            . $this->sqlNeedsUpdate()
            . ' AND (' . implode(' OR ', $options) . ')'
        );
        $items = 0;
        while ($row = $res->fetch(\PDO::FETCH_OBJ)) {
            $this->log->info(
                'Pinging URL #%d: %s', $row->feu_id, $row->feu_url
            );
            $this->ping($row);
            ++$items;
        }
        $this->log->info('Finished pinging %d URLs.', $items);
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
                . ', feu_error = 1'
                . ', feu_error_code = ' . $this->db->quote($e->getCode())
                . ', feu_error_message = ' . $this->db->quote($e->getMessage())
                . ', feu_tries = ' . $this->db->quote($row->feu_tries + 1)
                . ', feu_retry = ' . $this->sqlRetry($e)
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
                . ', feu_error = 0'
                . ', feu_error_code = ""'
                . ', feu_error_message = ""'
                . ', feu_tries = ' . $this->db->quote($row->feu_tries + 1)
                . ', feu_retry = 0'
                . ' WHERE feu_id = ' . $this->db->quote($row->feu_id)
            );
        } else {
            //error
            $code = $res->getCode();
            $this->log->err('Error: ' . $code . ': ' . $res->getMessage());
            $httpRes = $res->getResponse();
            if ($httpRes) {
                $this->log->info(
                    'Pingback response: Status code ' . $httpRes->getStatus()
                    . ', headers: ' . print_r($httpRes->getHeader(), true)
                );
                if ($code == 100 || $code == 101 || $code == -32600) {
                    $this->log->info('HTTP body: ' . $httpRes->getBody());
                }
            }
            $this->db->exec(
                'UPDATE feedentryurls SET'
                . '  feu_pinged = 1'
                . ', feu_updated = NOW()'
                . ', feu_error = 1'
                . ', feu_error_code = ' . $this->db->quote($res->getCode())
                . ', feu_error_message = ' . $this->db->quote($res->getMessage())
                . ', feu_tries = ' . $this->db->quote($row->feu_tries + 1)
                . ', feu_retry = ' . $this->sqlRetry($res)
                . ' WHERE feu_id = ' . $this->db->quote($row->feu_id)
            );
        }
    }

    protected function sqlNeedsUpdate()
    {
        if ($this->deps->options['force']) {
            return '';
        }
        $sqlRetry = '(feu_retry = 1 AND feu_tries < 5)';
        //FIXME: wait at least 1 hour before retrying

        return ' AND feu_active = 1 AND (feu_pinged = 0 OR ' . $sqlRetry . ')';
    }

    /**
     * Determines if it should be retried to pingback the URL after some time
     *
     * @param $obj mixed Exception or Pingback response
     */
    protected function sqlRetry($obj)
    {
        if ($obj instanceof \Exception) {
            return '1';
        }

        switch ($obj->getCode()) {
        case -32601:  //they have xmp-rpc, but do not support pingback
        case 17:  //they think we don't link to them
        case 18:  //they think we send out pingback spam
        case 48:  //already registered
        case 49:  //access denied
        case 200: //pingback not supported
        case 201: //Unvalid target URI
            return '0';
        }

        return '1';
    }
}
?>
