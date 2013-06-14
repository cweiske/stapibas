<?php
namespace stapibas;

class Logger
{
    public $debug = false;

    public function err($msg)
    {
        $args = func_get_args();
        if (count($args) > 1) {
            $msg = call_user_func_array('sprintf', $args);
        }
        file_put_contents('php://stderr', $msg . "\n");
    }

    public function info($msg)
    {
        if ($this->debug == 1) {
            $args = func_get_args();
            call_user_func_array(array($this, 'log'), $args);
        }
    }

    public function log($msg)
    {
        $args = func_get_args();
        if (count($args) > 1) {
            $msg = call_user_func_array('sprintf', $args);
        }
        echo $msg . "\n";
    }
}

?>
