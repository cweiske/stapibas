<?php
namespace stapibas;

class Feed_Manage
{
    public $db;
    public $log;

    public function __construct(Dependencies $deps)
    {
        $this->deps = $deps;
        $this->db   = $deps->db;
        $this->log  = $deps->log;
    }

    public function listAll()
    {
        $this->log->info('Listing all feeds..');
        $res = $this->db->query('SELECT * FROM feeds ORDER BY f_id');
        $items = 0;
        while ($feedRow = $res->fetch(\PDO::FETCH_OBJ)) {
            echo '#' . $feedRow->f_id . ' ' . $feedRow->f_url . "\n";
            ++$items;
        }
        $this->log->info('Finished listing %d URLs.', $items);
    }

    public function addFeed($url)
    {
        if ($url == '') {
            echo "URL empty\n";
            exit(1);
        }

        $this->db->exec(
            'INSERT INTO feeds SET'
            . '  f_url = ' . $this->db->quote($url)
            . ', f_needs_update = 1'
        );
        echo "Feed has been added\n";
    }

    public function removeFeed($urlOrId)
    {
        if ($urlOrId == '') {
            echo "URL/ID empty\n";
            exit(1);
        }

        if (is_numeric($urlOrId)) {
            $sqlWhere = ' f_id = ' . $this->db->quote($urlOrId);
        } else {
            $sqlWhere = ' f_url = ' . $this->db->quote($urlOrId);
        }

        $nRows = $this->db->exec(
            'DELETE FROM feeds WHERE' . $sqlWhere
        );
        echo sprintf("%d feed has been removed\n", $nRows);;
    }
}
?>
