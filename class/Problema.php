<?php


abstract class Problema
{

    protected DB2Info $dbinfo;
    protected PDO $conn;

    public function __construct($dbinfo,$conn)
    {
        $this->dbinfo = $dbinfo;
        $this->conn = $conn;
    }

    abstract public function analisa();


}
