<?php
require_once('DB.php');

class Postgres extends DB
{
 

    public function getTables($schema): array
    {
        $stmt = $this->conn->prepare("SELECT tablename from pg_tables where schemaname = :schemaname;");
        $stmt->bindValue(':schemaname', $schema);
        $stmt->execute();
        return $stmt->fetchall(PDO::FETCH_COLUMN);
    }

    public function getSchemas(): array
    {
        $stmt = $this->conn->prepare("SELECT nspname from pg_catalog.pg_namespace where nspname not ilike '%pg_%' and nspname not ilike 'information_schema'");
        $stmt->execute();
        return $stmt->fetchall(PDO::FETCH_COLUMN);
    }

    
    public function getFields($tablename): array
    {
        $stmt = $this->conn->prepare("SELECT 
            a.attname AS \"Field\",
            format_type(t.oid, null) AS \"Type\",
            a.attnotnull AS \"Null\",
            a.atthasdef AS \"Default\",
         	contype AS \"Key\"
        FROM pg_class c
            INNER JOIN pg_attribute a ON a.attrelid = c.oid
            INNER JOIN pg_type t ON a.atttypid = t.oid
            LEFT JOIN pg_constraint con ON (con.conrelid =  c.oid and a.attnum = ANY (con.conkey) and contype != 'f')
        WHERE c.relname = :tablename AND a.attnum > 0
        ORDER BY a.attnum;");
        $stmt->bindValue(':tablename', $tablename);
        $stmt->execute();

        return $stmt->fetchall(PDO::FETCH_ASSOC);
    }

    public function getFieldName($tablename, $position)
    {
        $stmt = $this->conn->prepare("SELECT a.attname AS Field
        FROM pg_class c
        INNER JOIN pg_attribute a ON a.attrelid = c.oid
        WHERE c.relname = :tablename AND a.attnum = :position
        ORDER BY a.attnum;");
        $stmt->bindValue(':tablename', $tablename);
        $stmt->bindValue(':position', $position);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC)['field'];
    }

    public function isSerial($tablename, $field): bool
    {
        $stmt = $this->conn->prepare("SELECT * FROM pg_class c where c.relkind = 'S' and relname = :relname");
        $stmt->bindValue(':relname', $tablename . '_' . $field . '_seq');
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }


    public function getForeignKey($tablename): array
    {
        $stmt = $this->conn->prepare("SELECT conname as name,conkey AS fields, contype as type, confkey AS fields_reference,ck.relname AS reference, 'foreign key' as type
        FROM pg_constraint ctr
            JOIN pg_class c ON c.oid = conrelid
            JOIN pg_class ck ON ck.oid = confrelid
        WHERE ctr.contype = 'f' AND ctr.confrelid != 0 AND c.relname  = :tablename");
        $stmt->bindValue(':tablename', $tablename);
        $stmt->execute();
        $fk = $stmt->fetchall(PDO::FETCH_ASSOC);

        foreach ($fk as &$key) {
            $key['fields'] = explode(",", preg_replace("/[{}]/", "", $key['fields']));
            $key['fields_reference'] = explode(",", preg_replace("/[{}]/", "", $key['fields_reference']));
            foreach ($key['fields'] as &$field) {
                $field = $this->getFieldName($tablename, $field);
            }
            foreach ($key['fields_reference'] as &$field) {
                $field = $this->getFieldName($key['reference'], $field);
            }
            $key['name'] = $key['name'];
        }

        return $fk;
    }

    public function getConstraints($tablename): array
    {
        $stmt = $this->conn->prepare("SELECT conname AS name, conkey AS fields, contype as type
        FROM pg_constraint ctr
            JOIN pg_class c ON c.oid = conrelid
        WHERE  c.relname = :tablename and contype != 'f' ");
        $stmt->bindValue(':tablename', $tablename);
        $stmt->execute();
        $consraints = $stmt->fetchall(PDO::FETCH_ASSOC);
        $type = [
            'c' => 'check constraint',
            'u' => 'unique constraint',
            'p' => 'primary key'
        ];

        foreach ($consraints as &$key) {
            $key['fields'] = explode(",", preg_replace("/[{}]/", "", $key['fields']));
            foreach ($key['fields'] as &$field) {
                $field = $this->getFieldName($tablename, $field);
            }
            $key['type'] = $type[$key['type']];
        }

        return $consraints;
    }
}
