<?php
namespace stapibas;

class Logger
{
    public function err($msg)
    {
        $this->log($msg);
    }

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
