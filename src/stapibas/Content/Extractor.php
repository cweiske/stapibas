<?php
namespace stapibas;

class Content_Extractor
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
     * Extracts content from all pingbackcontent entries and puts it
     * into rbookmarks/rcomments/rlinks.
     */
    public function updateAll()
    {
        $this->log->info('Extracting linkback content..');
        $res = $this->db->query(
            'SELECT * FROM linkbackcontent, linkbacks'
            . ' WHERE l_id = lc_l_id' . $this->sqlNeedsUpdate()
        );
        $items = 0;
        while ($contentRow = $res->fetch(\PDO::FETCH_OBJ)) {
            ++$items;
            $this->extractContent($contentRow);
        }
        $this->log->info('Finished extracting %d linkback contents.', $items);
    }

    protected function extractContent($contentRow)
    {
        $doc = new \DOMDocument();
        $typeParts = explode(';', $contentRow->lc_mime_type);
        $type = $typeParts[0];
        if ($type == 'application/xhtml+xml'
            || $type == 'application/xml'
            || $type == 'text/xml'
        ) {
            $doc->loadXML($contentRow->lc_fulltext);
        } else { 
            $doc->loadHTML($contentRow->lc_fulltext);
        }

        //delete old content
        $this->db->exec(
            'DELETE FROM rbookmarks WHERE'
            . ' rb_lc_id = ' . $this->db->quote($contentRow->lc_id)
        );
        $this->db->exec(
            'DELETE FROM rcomments WHERE'
            . ' rc_lc_id = ' . $this->db->quote($contentRow->lc_id)
        );
        $this->db->exec(
            'DELETE FROM rlinks WHERE'
            . ' rl_lc_id = ' . $this->db->quote($contentRow->lc_id)
        );

        $ce = new Content_Extractor_Comment($this->deps->log);
        $data = $ce->extract($doc, $contentRow->l_source, $contentRow->l_target);
        if ($data !== null) {
            $this->log->info('Comment found');
            var_dump($data);
            $this->db->exec(
                'INSERT INTO rcomments SET'
                . '  rc_l_id = ' . $this->db->quote($contentRow->l_id)
                . ', rc_lc_id = ' . $this->db->quote($contentRow->lc_id)
                . ', rc_source = ' . $this->db->quote($contentRow->l_source)
                . ', rc_target = ' . $this->db->quote($contentRow->l_target)
                . ', rc_title = ' . $this->db->quote($data['title'])
                . ', rc_author_name = ' . $this->db->quote($data['author_name'])
                . ', rc_author_url = ' . $this->db->quote($data['author_url'])
                . ', rc_author_image = ' . $this->db->quote($data['author_image'])
                . ', rc_content = ' . $this->db->quote($data['content'])
                . ', rc_updated = NOW()'
            );
            $this->setDetectedType($contentRow, 'comment');
            return;
        }

        //FIXME: bookmark

        $ce = new Content_Extractor_Link($this->deps->log);
        $data = $ce->extract($doc, $contentRow->l_source, $contentRow->l_target);
        if ($data !== null) {
            $this->log->info('Link found');
            $this->db->exec(
                'INSERT INTO rlinks SET'
                . '  rl_l_id = ' . $this->db->quote($contentRow->l_id)
                . ', rl_lc_id = ' . $this->db->quote($contentRow->lc_id)
                . ', rl_source = ' . $this->db->quote($contentRow->l_source)
                . ', rl_target = ' . $this->db->quote($contentRow->l_target)
                . ', rl_title = ' . $this->db->quote($data['title'])
                . ', rl_author_name = ' . $this->db->quote($data['author_name'])
                . ', rl_author_url = ' . $this->db->quote($data['author_url'])
                . ', rl_author_image = ' . $this->db->quote($data['author_image'])
                . ', rl_updated = NOW()'
            );
            $this->setDetectedType($contentRow, 'link');
            return;
        }

        $this->setDetectedType($contentRow, 'nothing');
        $this->log->info('Nothing found');
    }

    protected function setDetectedType($contentRow, $type)
    {
        $this->db->exec(
            'UPDATE linkbackcontent'
            . ' SET lc_detected_type = ' . $this->db->quote($type)
            . ' WHERE lc_id = ' . $this->db->quote($contentRow->lc_id)
        );
    }


    protected function sqlNeedsUpdate()
    {
        if ($this->deps->options['force']) {
            return '';
        }
        return ' AND lc_detected_type = ""';
    }

}
?>
