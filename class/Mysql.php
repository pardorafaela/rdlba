<?php

require_once('DB.php');

class Mysql extends DB
{

    public function getTables($tablename): array
    {
        $stmt = $this->conn->prepare("SHOW TABLES FROM " . $tablename);
        $stmt->execute();
        return $stmt->fetchall(PDO::FETCH_COLUMN);
    }

    public function getSchemas(): array
    {
        $stmt = $this->conn->prepare("SHOW DATABASES");
        $stmt->execute();
        return $stmt->fetchall(PDO::FETCH_COLUMN);
    }

    public function getFields($tablename): array
    {
        $stmt = $this->conn->prepare("SHOW COLUMNS FROM " . $tablename);
        $stmt->execute();
        return $stmt->fetchall(PDO::FETCH_ASSOC);
    }

    public function isSerial($tablename, $field): bool
    {
        $stmt = $this->conn->prepare("SHOW COLUMNS FROM {$tablename}");
        $stmt->execute();
        $pri = $stmt->fetchall(PDO::FETCH_ASSOC);
        foreach ($pri as $key ) {
            if($key['Field'] == $field && $key['Key'] == 'PRI' && $key['Extra'] == 'auto_increment') 
                return true;
        }
        return false;
    }

    public function getForeignKey($tablename): array
    {
        $stmt = $this->conn->prepare('SHOW CREATE TABLE ' . $tablename);
        $stmt->execute();
        $createTable = $stmt->fetchall(PDO::FETCH_ASSOC);
        $regex = "/([a-zA-Z0-9_]+)[ \t]+foreign[ \t]+key[ \t]+((?:\(.+)\))/i";
        $fk = [];
        preg_match_all($regex, preg_replace("/[\"'\`\']/", "", $createTable[0]["Create Table"]), $match);
        if (count($match[2]) > 0) {
            foreach ($match[2] as $key) {
                $t = explode("REFERENCES ", $key);
                $source = explode(",", trim(preg_replace("/\(|\)/", '', $t[0])));
                $references = explode(' (', $t[1]);
                $target = explode(",", trim(preg_replace("/\(|\)/", '', $references[1])));

                array_push($fk, [
                    "name" => $match[1][0],
                    "type" => 'foreign key',
                    "fields" => $source,
                    "reference" =>  $references[0],
                    'fields_reference' => $target
                ]);
            }
        }
        return $fk;
    }

    public function getConstraints($tablename): array
    {
        $stmt = $this->conn->prepare('SHOW CREATE TABLE ' . $tablename);
        $stmt->execute();
        $createTable = $stmt->fetchall(PDO::FETCH_ASSOC);

        $regex = "/unique[ \t]+key[ \t]+([a-zA-Z0-9_]+)[ \t]+((?:\(.+\)))/i";
        $ctr = [];
        preg_match_all($regex, preg_replace("/[\"'\`\']/", "", $createTable[0]["Create Table"]), $match);

        if (count($match[2]) > 0) {
            foreach ($match[2] as $key) {
                $fields = explode(",", trim(preg_replace("/\(|\)/", '', $match[2][0])));
                array_push($ctr, [
                    "name" => $match[1][0],
                    "fields" => $fields,
                    "type" =>  'unique constraint',
                ]);
            }
        }

        $regex = "/primary[ \t]+key[ \t]+\(([^)]+)\)/i";
        // ((?:\(.+\)))/i";
        
        preg_match_all($regex, preg_replace("/[\"'\`\']/", "", $createTable[0]["Create Table"]), $match);
        if (count($match[1]) > 0) {
            $fields = explode(",", trim(preg_replace("/\(|\)/", '', $match[1][0])));
        
            array_push($ctr, [
                "name" => "PRIMARY",
                "fields" => $fields,
                "type" =>  'primary key',
            ]);
        }
        $regex = "/([a-zA-Z0-9_]+)[ \t]+check[ \t]+((?:\(.+))/i";
       
        preg_match_all($regex, preg_replace("/[\"'\`\']/", "", $createTable[0]["Create Table"]), $match);
        if (count($match[1]) > 0) {
            $fields = explode(",", trim(preg_replace("/\(|\)/", '', $match[2][0])));
            array_push($ctr, [
                "name" => $match[1][0],
                "fields" => [],
                "type" =>  'check constraint',
            ]);
        }
        return $ctr;
    }
}
