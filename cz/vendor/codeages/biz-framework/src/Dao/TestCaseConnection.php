<?php

namespace Codeages\Biz\Framework\Dao;

class TestCaseConnection extends Connection
{
    private $insertedTables = array();

    public function insert($tableName, array $data, array $types = array())
    {
        $this->insertedTables[] = $tableName;

        return parent::insert($tableName, $data, $types);
    }

    public function getInsertedTables()
    {
        return $this->insertedTables;
    }

    public function resetInsertedTables()
    {
        $this->insertedTables = array();
    }
}
