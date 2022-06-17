<?php

class DB2Info
{

    private array $json;
    private DB $db;

    public function __construct($conn, $schema, $sgbd, $foreignKeyNaoDeclarada = [])
    {
        $this->db = new $sgbd($conn);
        $this->json = [];
        foreach ($this->db->getTables($schema) as $key) {
            $table = (object) [
                'name' => $key,
                'fields' => [],
                'constraints' => []
            ];

            $fields = $this->db->getFields($table->name);
            foreach ($fields as $fd) {
                array_push($table->fields, (object) [
                    'name' => $fd['Field'],
                    'type' => $this->db->isSerial($table->name, $fd['Field']) ? 'serial' : $fd['Type'],
                    'hasdefault' => $fd['Default'] ?  $fd['Default'] : false,
                    'null' =>  $fd['Null'] == 'NO' || $fd['Null'] == true ? false : true
                ]);
            }

            $constraints = $this->db->getConstraints($table->name);

            $foreignKey = $this->db->getForeignKey($table->name);

            foreach ($constraints as $key) {
                array_push($table->constraints, (object)[
                    'name' => $key['name'],
                    'type' => $key['type'],
                    'fields' => $key['fields']
                ]);
            }



            foreach ($foreignKey as $fk) {
                array_push($table->constraints, (object)[
                    'name' => $fk['name'],
                    'type' => $fk['type'],
                    'from' => 'db',
                    'fields' => $fk['fields'],
                    'reference' => (object) [
                        'name' => $fk['reference'],
                        'fields' => $fk['fields_reference']
                    ]
                ]);
            }
            array_push($this->json, $table);
        }
        if (count($foreignKeyNaoDeclarada) > 0) $this->fknaodeclarada($foreignKeyNaoDeclarada);
    }

    public function getJSON()
    {
        return $this->json;
    }

    public function fknaodeclarada($foreignKeyNaoDeclarada)
    {


        foreach ($foreignKeyNaoDeclarada as $fk) {

            $source = $this->stringToFK(explode(" = ", $fk)[0]);
            $target = $this->stringToFK(explode(" = ", $fk)[1]);


            if ($this->noExistFK($this->json[$source['pos']], $target,$source)) {
                array_push($this->json[$source['pos']]->constraints, (object)[
                    'name' => $source['name'],
                    'type' => 'foreign key',
                    'from' => 'user',
                    'fields' => $source['fields'],
                    'reference' => (object) [
                        'name' => $target['name'],
                        'fields' => $target['fields']
                    ]
                ]);
            } else throw new Exception("Já existe uma chave estrangeira entre " . $source['name'] . " e " . $target['name'], 1);
        }
    }

    private function noExistFK($tbl, $trg,$src)
    {
        foreach ($tbl->constraints as $key) {
            if ($key->type == 'foreign key') {
                if ($trg['name'] == $key->reference->name && $tbl->name == $src['name']) {
                    $match = 0;
                    for ($i=0; $i < count($key->fields); $i++) { 
                        $fd = $this->getField($key->reference->fields[$i],$tbl->name);
                        $fdReference = $this->getField($key->reference->fields[$i],$key->reference->name);
                        
                        if($key->fields[$i] == $src['fields'][$i]->name 
                            && $fd->type == $src['fields'][$i]->type 
                            && $fdReference->name == $trg['fields'][$i]->name 
                            && $fdReference->type == $trg['fields'][$i]->type 
                        ){
                            $match++;
                        }
                    }
                    return $match != count($key->fields);
                }

            }
        }
        return true;
    }

    private function stringToFK($string)
    {

        $tableName = explode(" (", $string)[0];

        $pos = null;
        foreach ($this->json as $key => $tbl) {
            if ($tbl->name == $tableName)
                $pos = $key;
        }

        if (isset($this->json[$pos])) {

            $fields = [];

            $pos1 = strpos($string, '(') + 1;
            $pos2 = strrpos($string, ')') - 1;

            $temp = substr($string, $pos1, $pos2 - $pos1);

            foreach (explode(",", $temp) as $fd) {
                $nameField = explode(":", $fd)[0];
                $field = $this->getField($nameField, $tableName);
                if ($field) array_push($fields, $field);
                else throw new Exception("O campo $nameField na $tableName não existe", 1);
            }
        } else throw new Exception("A $tableName não existe", 1);

        return [
            'name' => $tableName,
            'fields' => $fields,
            'pos' => $pos
        ];
    }

    public function getField($column, $table)
    {
        foreach ($this->json as $key) {
            if ($key->name == $table) {
                foreach ($key->fields as $field) {
                    if ($field->name == $column) {
                        return $field;
                    }
                }
            }
        }
        return false;
    }
}
