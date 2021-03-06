<?php
namespace stapibas;

/**
 * Fetches entries that need an update and extracts their links
 */
class Feed_UpdateEntries
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
        $this->log->info('Updating feed entries..');
        $res = $this->db->query(
            'SELECT * FROM feedentries'
            . ' WHERE ' . $this->sqlNeedsUpdate()
        );
        $items = 0;
        while ($entryRow = $res->fetch(\PDO::FETCH_OBJ)) {
            ++$items;
            $this->updateEntry($entryRow);
        }
        $this->log->info('Finished updating %d entries.', $items);
    }

    public function updateSome($urlOrIds)
    {
        $options = array();
        foreach ($urlOrIds as $urlOrId) {
            if (is_numeric($urlOrId)) {
                $options[] = 'fe_id = ' . intval($urlOrId);
            } else {
                $options[] = 'fe_url = ' . $this->db->quote($urlOrId);
            }
        }

        $this->log->info('Updating %d feed entries..', count($options));
        $res = $this->db->query(
            'SELECT * FROM feedentries'
            . ' WHERE ' . $this->sqlNeedsUpdate()
            . ' AND (' . implode(' OR ', $options) . ')'
        );

        $items = 0;
        while ($entryRow = $res->fetch(\PDO::FETCH_OBJ)) {
            ++$items;
            $this->updateEntry($entryRow);
        }
        $this->log->info('Finished updating %d entries.', $items);
    }

    protected function updateEntry($entryRow)
    {
        $this->log->info(
            'Updating feed entry #%d: %s', $entryRow->fe_id, $entryRow->fe_url
        );

        $req = new \HTTP_Request2($entryRow->fe_url);
        $req->setHeader('User-Agent', 'stapibas');
        $req->setHeader(
            'Accept',
            'application/xhtml+xml; q=1'
            . ', application/xml; q=0.9'
            . ', text/xml; q=0.9'
            . ', text/html; q=0.5'
            . ', */*; q=0.1'
        );

        if ($entryRow->fe_updated != '0000-00-00 00:00:00') {
            $req->setHeader(
                'If-Modified-Since',
                gmdate('r', strtotime($entryRow->fe_updated))
            );
        }

        $res = $req->send();
        if ($res->getStatus() == 304) {
            //not modified
            $this->setNoUpdate($entryRow);
            $this->log->info('Not modified');
            return;
        }

        if (intval($res->getStatus() / 100) != 2) {
            //no 2xx is an error for us
            $this->log->err('Error fetching feed entry URL');
            return;
        }

        $urls = $this->extractUrls($entryRow, $res);
        $this->updateUrls($entryRow, $urls);
        $this->setUpdated($entryRow, $res);
    }

    protected function updateUrls($entryRow, $urls)
    {
        $res = $this->db->query(
            'SELECT * FROM feedentryurls'
            . ' WHERE feu_fe_id = ' . $this->db->quote($entryRow->fe_id)
        );
        $urlRows = array();
        while ($urlRow = $res->fetch(\PDO::FETCH_OBJ)) {
            $urlRows[$urlRow->feu_url] = $urlRow;
        }

        $urls = array_unique($urls);

        $new = $updated = $deleted = 0;
        $items = count($urls);

        foreach ($urls as $url) {
            if (!isset($urlRows[$url])) {
                //URL is not known - insert it
                $this->db->exec(
                    'INSERT INTO feedentryurls SET'
                    . '  feu_fe_id = ' . $this->db->quote($entryRow->fe_id)
                    . ', feu_url = ' . $this->db->quote($url)
                    . ', feu_active = 1'
                    . ', feu_pinged = 0'
                    . ', feu_updated = NOW()'
                );
                ++$new;
            } else if ($urlRows[$url]->feu_active == 0) {
                //URL is known already, but was once deleted and is back now
                $this->db->exec(
                    'UPDATE feedentryurls SET'
                    . '  feu_active = 1'
                    . ', feu_updated = NOW()'
                    . ' WHERE feu_id = ' . $this->db->quote($urlRows[$url]->feu_id)
                );
                ++$updated;
                unset($urlRows[$url]);
            } else {
                //already known, all fine
                unset($urlRows[$url]);
            }
        }

        //these URLs are in DB but not on the page anymore
        foreach ($urlRows as $urlRow) {
            ++$deleted;
            $this->db->exec(
                'UPDATE feedentryurls SET'
                . '  feu_active = 0'
                . ', feu_updated = NOW()'
                . ' WHERE feu_id = ' . $this->db->quote($urlRow->feu_id)
            );
        }
        $this->log->info(
            'Feed entry #%d: %d new, %d updated, %d deleted of %d URLs',
            $entryRow->fe_id, $new, $updated, $deleted, $items
        );
    }

    protected function extractUrls($entryRow, \HTTP_Request2_Response $res)
    {
        $doc = new \DOMDocument();
        $typeParts = explode(';', $res->getHeader('content-type'));
        $type = $typeParts[0];
        if ($type == 'application/xhtml+xml'
            || $type == 'application/xml'
            || $type == 'text/xml'
        ) {
            $doc->loadXML($res->getBody());
        } else { 
            $doc->loadHTML($res->getBody());
        }

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('h', 'http://www.w3.org/1999/xhtml');
        // all links in e-content AND u-in-reply-to links
        $query = '//*[' . $this->xpc('h-entry') . ' or ' . $this->xpc('hentry') . ']'
            . '//*[' . $this->xpc('e-content') . ' or ' . $this->xpc('entry-content') . ']'
            . '//*[(self::a or self::h:a) and @href and not(starts-with(@href, "#"))]'
            . ' | '
            . '//*[' . $this->xpc('h-entry') . ' or ' . $this->xpc('hentry') . ']'
            . '//*['
            . '(self::a or self::h:a) and @href and not(starts-with(@href, "#"))'
            . 'and ' . $this->xpc('u-in-reply-to')
            . ']';
;
        $links = $xpath->query($query);
        $this->log->info('%d links found', $links->length);

        $entryUrl = new \Net_URL2($entryRow->fe_url);
        //FIXME: base URL in html code

        $urls = array();
        foreach ($links as $link) {
            $url = (string)$entryUrl->resolve(
                $link->attributes->getNamedItem('href')->nodeValue
            );
            $this->log->info('URL in entry: ' . $url);
            $urls[] = $url;
        }
        return $urls;
    }

    protected function xpc($class)
    {
        return 'contains('
            . 'concat(" ", normalize-space(@class), " "),'
            . '" ' . $class . ' "'
            . ')';
    }

    protected function setNoUpdate($entryRow)
    {
        $this->db->exec(
            'UPDATE feedentries SET fe_needs_update = 0'
            . ' WHERE fe_id = ' . $this->db->quote($entryRow->fe_id)
        );
    }

    protected function setUpdated($entryRow, \HTTP_Request2_Response $res)
    {
        $this->db->exec(
            'UPDATE feedentries'
            . ' SET fe_needs_update = 0'
            . ', fe_updated = ' . $this->db->quote(
                gmdate('Y-m-d H:i:s', strtotime($res->getHeader('last-modified')))
            )
            . ' WHERE fe_id = ' . $this->db->quote($entryRow->fe_id)
        );
    }

    protected function sqlNeedsUpdate()
    {
        if ($this->deps->options['force']) {
            return ' 1';
        }
        return ' (fe_needs_update = 1 OR fe_updated = "0000-00-00 00:00:00")';
    }
}
?>
