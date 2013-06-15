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
            $deps->options = array_merge(
                $result->options,
                $result->command ? $result->command->options : array(),
                ($result->command && $result->command->command)
                    ? $result->command->command->options
                    : array()
            );

            if ($result->command_name == 'feed') {
                $this->runFeed($result->command, $deps);
            } else if ($result->command_name == 'handle') {
                $this->runPingbackHandler($result->command, $deps);
            } else {
                $this->cliParser->displayUsage(1);
            }
        } catch (\Exception $e) {
            $msg = 'stapibas exception!' . "\n"
                . 'Code: ' . $e->getCode() . "\n"
                . 'Message: ' . $e->getMessage() . "\n";
            file_put_contents('php://stderr', $msg);
            exit(1);
        }
    }

    protected function runFeed(
        \Console_CommandLine_Result $command, Dependencies $deps
    ) {
        if ($command->command_name == 'update') {
            return $this->runFeedUpdate($command, $deps);
        }

        $mg = new Feed_Manage($deps);
        if ($command->command_name == 'add') {
            $mg->addFeed($command->command->args['feed']);
        } else if ($command->command_name ==  'remove') {
            $mg->removeFeed($command->command->args['feed']);
        } else {
            $mg->listAll();
        }
    }

    protected function runFeedUpdate(
        \Console_CommandLine_Result $result, Dependencies $deps
    ) {
        $tasks = array_flip(explode(',', $result->command->options['tasks']));

        if (isset($tasks['feeds'])) {
            $this->runFeedUpdateFeeds($deps);
        }
        if (isset($tasks['entries'])) {
            $this->runFeedUpdateEntries($deps);
        }
        if (isset($tasks['urls'])) {
            $this->runFeedUpdatePingUrls($deps);
        }
    }


    protected function runFeedUpdateFeeds($deps)
    {
        $uf = new Feed_UpdateFeeds($deps);
        if ($deps->options['feed'] === null) {
            $uf->updateAll();
        } else {
            $urlOrIds = explode(',', $deps->options['feed']);
            $uf->updateSome($urlOrIds);
        }
    }

    protected function runFeedUpdateEntries($deps)
    {
        $ue = new Feed_UpdateEntries($deps);
        if ($deps->options['entry'] === null) {
            $ue->updateAll();
        } else {
            $urlOrIds = explode(',', $deps->options['entry']);
            $ue->updateSome($urlOrIds);
        }
    }

    protected function runFeedUpdatePingUrls($deps)
    {
        $uf = new Feed_PingUrls($deps);
        if ($deps->options['entryurl'] === null) {
            $uf->pingAll();
        } else {
            $urls = explode(',', $deps->options['entryurl']);
            $uf->pingSome($urls);
        }
    }


    protected function runPingbackHandler(
        \Console_CommandLine_Result $command, Dependencies $deps
    ) {
        //fetch content of pingback source pages
        $cf = new Pingback_ContentFetcher($deps);
        $cf->updateAll();

        //FIXME
    }


    public function setupCli()
    {
        $p = new \Console_CommandLine();
        $p->description = 'Sends pingbacks to URLs linked in Atom feed entries';
        $p->version = '0.0.1';

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

        $this->setupCliFeed($p);
        $this->setupCliPingback($p);

        $this->cliParser = $p;
    }

    protected function setupCliFeed($p)
    {
        $feed = $p->addCommand(
            'feed',
            array(
                'description' => 'Edit, list or delete feeds'
            )
        );

        $add = $feed->addCommand(
            'add',
            array(
                'description' => 'Add the feed',
            )
        );
        $add->addArgument('feed', array('description' => 'URL of feed'));

        $remove = $feed->addCommand(
            'remove',
            array(
                'description' => 'Remove the feed',
            )
        );
        $remove->addArgument('feed', array('description' => 'URL or ID of feed'));
        

        $update = $feed->addCommand(
            'update',
            array(
                'description' => 'Update feed data and send out pings'
            )
        );
        $update->addOption(
            'feed',
            array(
                'short_name'  => '-i',
                'long_name'   => '--feed',
                'description' => 'Update this feed URL or ID',
                'help_name'   => 'URL|ID',
                'action'      => 'StoreString'
            )
        );
        $update->addOption(
            'entry',
            array(
                'short_name'  => '-e',
                'long_name'   => '--entry',
                'description' => 'Update this feed entry URL or ID',
                'help_name'   => 'URL|ID',
                'action'      => 'StoreString'
            )
        );
        $update->addOption(
            'tasks',
            array(
                'short_name'  => '-t',
                'long_name'   => '--tasks',
                'description' => 'Execute the given tasks (comma-separated: feeds,entries,urls)',
                'help_name'   => 'tasks',
                'action'      => 'StoreString',
                'default'     => 'feeds,entries,urls',
            )
        );
        $update->addOption(
            'list_tasks',
            array(
                'long_name'     => '--list-tasks',
                'description'   => 'Show all possible tasks',
                'action'        => 'List',
                'action_params' => array(
                    'list' => array('feeds', 'entries', 'urls')
                )
            )
        );
        $update->addOption(
            'entryurl',
            array(
                'short_name'  => '-u',
                'long_name'   => '--url',
                'description' => 'Ping this URL or ID',
                'help_name'   => 'URL|ID',
                'action'      => 'StoreString'
            )
        );
    }

    protected function setupCliPingback($p)
    {
        $handle = $p->addCommand(
            'handle',
            array(
                'description' => 'Handle pingbacks: Fetch content, extract data'
            )
        );
    }
}
?>
