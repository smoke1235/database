<?php

namespace Smoke\Wrapper;

class MysqliResultWrapper extends \MySQLi_Result
{
    public function fetch()
    {
        return $this->fetch_assoc();
    }

    public function fetchAll()
    {
        $rows = array();
        while($row = $this->fetch())
        {
            $rows[] = $row;
        }
        return $rows;
    }
}


?>
