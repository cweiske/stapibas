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
        $this->log->info('Extracting pingback content..');
        $res = $this->db->query(
            'SELECT * FROM pingbackcontent, pingbacks'
            . ' WHERE p_id = pc_p_id' . $this->sqlNeedsUpdate()
        );
        $items = 0;
        while ($contentRow = $res->fetch(\PDO::FETCH_OBJ)) {
            ++$items;
            $this->extractContent($contentRow);
        }
        $this->log->info('Finished extracting %d pingback contents.', $items);
    }

    protected function extractContent($contentRow)
    {
        $doc = new \DOMDocument();
        $typeParts = explode(';', $contentRow->pc_mime_type);
        $type = $typeParts[0];
        if ($type == 'application/xhtml+xml'
            || $type == 'application/xml'
            || $type == 'text/xml'
        ) {
            $doc->loadXML($contentRow->pc_fulltext);
        } else { 
            $doc->loadHTML($contentRow->pc_fulltext);
        }

        //delete old content
        $this->db->exec(
            'DELETE FROM rbookmarks WHERE'
            . ' rb_pc_id = ' . $this->db->quote($contentRow->pc_id)
        );
        $this->db->exec(
            'DELETE FROM rcomments WHERE'
            . ' rc_pc_id = ' . $this->db->quote($contentRow->pc_id)
        );
        $this->db->exec(
            'DELETE FROM rlinks WHERE'
            . ' rl_pc_id = ' . $this->db->quote($contentRow->pc_id)
        );

        $ce = new Content_Extractor_Comment($this->deps->log);
        $data = $ce->extract($doc, $contentRow->p_source, $contentRow->p_target);
        if ($data !== null) {
            $this->log->info('Comment found');
            var_dump($data);
            $this->db->exec(
                'INSERT INTO rcomments SET'
                . '  rc_p_id = ' . $this->db->quote($contentRow->p_id)
                . ', rc_pc_id = ' . $this->db->quote($contentRow->pc_id)
                . ', rc_source = ' . $this->db->quote($contentRow->p_source)
                . ', rc_target = ' . $this->db->quote($contentRow->p_target)
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
        $data = $ce->extract($doc, $contentRow->p_source, $contentRow->p_target);
        if ($data !== null) {
            $this->log->info('Link found');
            $this->db->exec(
                'INSERT INTO rlinks SET'
                . '  rl_p_id = ' . $this->db->quote($contentRow->p_id)
                . ', rl_pc_id = ' . $this->db->quote($contentRow->pc_id)
                . ', rl_source = ' . $this->db->quote($contentRow->p_source)
                . ', rl_target = ' . $this->db->quote($contentRow->p_target)
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
            'UPDATE pingbackcontent'
            . ' SET pc_detected_type = ' . $this->db->quote($type)
            . ' WHERE pc_id = ' . $this->db->quote($contentRow->pc_id)
        );
    }


    protected function sqlNeedsUpdate()
    {
        if ($this->deps->options['force']) {
            return '';
        }
        return ' AND pc_detected_type = ""';
    }

}
?>
