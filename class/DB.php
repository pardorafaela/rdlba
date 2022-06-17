<?php

require_once('Postgres.php');
require_once('Mysql.php');

abstract class DB
{

    protected  $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public abstract function getTables($schema): array;

    public abstract function getFields($tablename): array;

    public abstract function isSerial($tablename, $field): bool;

    public abstract function getForeignKey($tablename): array;

    public abstract function getConstraints($tablename): array;

    public abstract function getSchemas(): array;

}
