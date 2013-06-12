<?php
namespace stapibas;

class Logger
{
    public function info($msg)
    {
        $this->log($msg);
    }

    public function log($msg)
    {
        echo $msg . "\n";
    }
}

?>
