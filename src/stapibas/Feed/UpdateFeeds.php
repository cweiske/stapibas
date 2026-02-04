<?php
namespace stapibas;

/**
 * Fetches feeds that need an update and updates their feedentries.
 */
class Feed_UpdateFeeds
{
    public $db;
    public $log;

    public function __construct(Dependencies $deps)
    {
        $this->deps = $deps;
        $this->db   = $deps->db;
        $this->log  = $deps->log;
    }

    public function updateAll()
    {
        $this->log->info('Updating feeds..');
        $res = $this->db->query(
            'SELECT * FROM feeds'
            . ' WHERE ' . $this->sqlNeedsUpdate()
        );
        while ($feedRow = $res->fetch(\PDO::FETCH_OBJ)) {
            $this->updateFeed($feedRow);
        }
        $this->log->info('Finished updating feeds.');
    }

    public function updateSome($urlOrIds)
    {
        $options = array();
        foreach ($urlOrIds as $urlOrId) {
            if (is_numeric($urlOrId)) {
                $options[] = 'f_id = ' . intval($urlOrId);
            } else {
                $options[] = 'f_url = ' . $this->db->quote($urlOrId);
            }
        }

        $this->log->info('Updating %d feeds..', $options);
        $res = $this->db->query(
            'SELECT * FROM feeds'
            . ' WHERE'
            . $this->sqlNeedsUpdate()
            . ' AND (' . implode(' OR ', $options) . ')'
        );

        $items = 0;
        while ($feedRow = $res->fetch(\PDO::FETCH_OBJ)) {
            ++$items;
            $this->updateFeed($feedRow);
        }
        $this->log->info('Finished updating %d feeds.', $items);
    }

    protected function updateFeed($feedRow)
    {
        $this->log->info(
            'Updating feed #%d: %s', $feedRow->f_id, $feedRow->f_url
        );

        $req = new \HTTP_Request2($feedRow->f_url);
        $req->setHeader('User-Agent', 'stapibas');

        if ($feedRow->f_updated != '0000-00-00 00:00:00') {
            $req->setHeader(
                'If-Modified-Since',
                gmdate('r', strtotime($feedRow->f_updated))
            );
        }

        $res = $req->send();
        if ($res->getStatus() == 304) {
            //not modified
            $this->setNoUpdate($feedRow);
            $this->log->info('Not modified');
            return;
        }

        if (intval($res->getStatus() / 100) != 2) {
            //no 2xx is an error for us
            $this->log->err('Error fetching feed');
            return;
        }

        $this->updateEntries($feedRow, $res);
    }

    protected function updateEntries($feedRow, \HTTP_Request2_Response $res)
    {
        require_once $GLOBALS['stapibas_libdir'] . '/simplepie/autoloader.php';
        $sp = new \SimplePie();
        $sp->set_raw_data($res->getBody());
        $sp->init();

        $new = $updated = $items = 0;
        foreach ($sp->get_items() as $item) {
            ++$items;
            $url = $item->get_permalink();
            $entryRow = $this->db->query(
                'SELECT fe_id, fe_updated, fe_needs_update FROM feedentries'
                . ' WHERE fe_url = ' . $this->db->quote($url)
                . ' AND fe_f_id = ' . $this->db->quote($feedRow->f_id)
            )->fetch(\PDO::FETCH_OBJ);

            if ($entryRow === false) {
                //new item!
                $this->db->exec(
                    'INSERT INTO feedentries SET'
                    . '  fe_f_id = ' . $this->db->quote($feedRow->f_id)
                    . ', fe_url = ' . $this->db->quote($url)
                    . ', fe_updated = NOW()'
                    . ', fe_needs_update = 1'
                );
                ++$new;
                continue;
            }
            if ($entryRow->fe_needs_update == 0
                && $item->get_updated_gmdate('U') > strtotime($entryRow->fe_updated)
            ) {
                //updated
                $this->db->exec(
                    'UPDATE feedentries SET'
                    . '  fe_url = ' . $this->db->quote($url)
                    . ', fe_needs_update = 1'
                    . ' WHERE fe_id = ' . $this->db->quote($entryRow->fe_id)
                );
                ++$updated;
                continue;
            }
        }
        $this->log->info(
            'Feed #%d: %d new, %d updated of %d entries',
            $feedRow->f_id, $new, $updated, $items
        );
        $this->setUpdated($feedRow, $res);
    }

    protected function setNoUpdate($feedRow)
    {
        $this->db->exec(
            'UPDATE feeds SET f_needs_update = 0'
            . ' WHERE f_id = ' . $this->db->quote($feedRow->f_id)
        );
    }

    protected function setUpdated($feedRow, \HTTP_Request2_Response $res)
    {
        $this->db->exec(
            'UPDATE feeds'
            . ' SET f_needs_update = 0'
            . ', f_updated = ' . $this->db->quote(
                gmdate('Y-m-d H:i:s', strtotime($res->getHeader('last-modified')))
            )
            . ' WHERE f_id = ' . $this->db->quote($feedRow->f_id)
        );
    }

    protected function sqlNeedsUpdate()
    {
        if ($this->deps->options['force']) {
            return ' 1';
        }
        return ' (f_needs_update = 1 OR f_updated = "0000-00-00 00:00:00")';
    }
}
?>
