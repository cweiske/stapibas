<?php
namespace stapibas;

/**
 * Small error-handling wrapper around PDO
 */
class PDO extends \PDO
{
    public function query(
        string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs
    ): \PDOStatement|false {
        $args = func_get_args();
        $res = call_user_func_array(array('parent', 'query'), $args);
        if ($res !== false) {
            return $res;
        }

        $this->handleError();
    }

    public function exec(string $statement): int|false
    {
        $res = parent::exec($statement);
        if ($res !== false) {
            return $res;
        }

        $this->handleError();
    }

    protected function handleError()
    {
        echo "SQL error\n";
        echo " " . $this->errorCode() . "\n";
        echo " " . implode(' - ', $this->errorInfo()) . "\n";
        exit(2);
    }
}

?>
