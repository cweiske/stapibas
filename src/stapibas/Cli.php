<?php
namespace stapibas;

class Cli
{
    protected $cliParser;

    public function __construct()
    {
        $this->setupCli();
    }

    public function run()
    {
        try {
            $result = $this->cliParser->parse();
        } catch (\Exception $exc) {
            $this->cliParser->displayError($exc->getMessage());
        }

        $log = new Logger();
        if ($result->options['debug']) {
            $log->debug = true;
        }

        try {
            $deps = new Dependencies();
            $deps->db = new PDO(
                $GLOBALS['dbdsn'], $GLOBALS['dbuser'], $GLOBALS['dbpass']
            );
            $deps->log = $log;
            $deps->options = $result->options;

            $tasks = array_flip(explode(',', $result->options['tasks']));

            if (isset($tasks['feeds'])) {
                $this->runUpdateFeeds($deps);
            }
            if (isset($tasks['entries'])) {
                $this->runUpdateEntries($deps);
            }
            if (isset($tasks['urls'])) {
                $this->runPingUrls($deps);
            }
        } catch (\Exception $e) {
            $msg = 'stapibas exception!' . "\n"
                . 'Code: ' . $e->getCode() . "\n"
                . 'Message: ' . $e->getMessage() . "\n";
            file_put_contents('php://stderr', $msg);
            exit(1);
        }
    }

    protected function runUpdateFeeds($deps)
    {
        $uf = new Feed_UpdateFeeds($deps);
        if ($deps->options['feed'] === null) {
            $uf->updateAll();
        } else {
            $urlOrIds = explode(',', $deps->options['feed']);
            $uf->updateSome($urlOrIds);
        }
    }

    protected function runUpdateEntries($deps)
    {
        $ue = new Feed_UpdateEntries($deps);
        if ($deps->options['entry'] === null) {
            $ue->updateAll();
        } else {
            $urlOrIds = explode(',', $deps->options['entry']);
            $ue->updateSome($urlOrIds);
        }
    }

    protected function runPingUrls($deps)
    {
        $uf = new Feed_PingUrls($deps);
        if ($deps->options['entryurl'] === null) {
            $uf->pingAll();
        } else {
            $urls = explode(',', $deps->options['entryurl']);
            $uf->pingSome($urls);
        }
    }


    public function setupCli()
    {
        $p = new \Console_CommandLine();
        $p->description = 'Sends pingbacks to URLs linked in Atom feed entries';
        $p->version = '0.0.1';

        $p->addOption(
            'feed',
            array(
                'short_name'  => '-i',
                'long_name'   => '--feed',
                'description' => 'Update this feed URL or ID',
                'help_name'   => 'URL|ID',
                'action'      => 'StoreString'
            )
        );

        $p->addOption(
            'entry',
            array(
                'short_name'  => '-e',
                'long_name'   => '--entry',
                'description' => 'Update this feed entry URL or ID',
                'help_name'   => 'URL|ID',
                'action'      => 'StoreString'
            )
        );

        $p->addOption(
            'tasks',
            array(
                'short_name'  => '-t',
                'long_name'   => '--tasks',
                'description' => 'Execute the given tasks (comma-separated)',
                'help_name'   => 'tasks',
                'action'      => 'StoreString',
                'default'     => 'feeds,entries,urls',
            )
        );
        $p->addOption(
            'list_tasks',
            array(
                'long_name'   => '--list-tasks',
                'description' => 'Show all possible tasks',
                'action'      => 'List',
                'list'        => array('feeds', 'entries', 'urls')
            )
        );

        $p->addOption(
            'entryurl',
            array(
                'short_name'  => '-u',
                'long_name'   => '--url',
                'description' => 'Ping this URL or ID',
                'help_name'   => 'URL|ID',
                'action'      => 'StoreString'
            )
        );


        $p->addOption(
            'debug',
            array(
                'short_name'  => '-d',
                'long_name'   => '--debug',
                'description' => "Output debug messages",
                'action'      => 'StoreTrue'
            )
        );
        $p->addOption(
            'force',
            array(
                'short_name'  => '-f',
                'long_name'   => '--force',
                'description' => "Update even when resource did not change",
                'action'      => 'StoreTrue'
            )
        );

        $this->cliParser = $p;
    }

}
?>
