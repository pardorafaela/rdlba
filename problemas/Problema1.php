<?php
// descrição:Ignorar a 1FN
class Problema1 extends Problema
{
    // public $relatorio = ['Ignorar a 1FN'];

    public $relatorio =  array(
        "type" => "warning",
        "description" => "Problema 1: Ignorar a 1FN.",
        "tables" => [],
        "describe" => []
    );

    public function analisa()
    {
        foreach ($this->dbinfo->getJSON() as $key) {

            $prefixo = [];

            foreach ($key->fields as $field) {
                preg_match_all('/([^0-9]+)[0-9]+/i', $field->name, $fd);
                $field->prefixo = isset($fd[1][0]) ? $fd[1][0] : $field->name;

                if (!isset($prefixo[$field->prefixo]))  $prefixo[$field->prefixo] = [];

                array_push($prefixo[$field->prefixo], ["name" => $field->name, "type" => $field->type]);
            }

            foreach ($prefixo as $pref) {
                if (count($pref) > 1) {
                    $fields = [];
                    foreach ($pref as $fid) {
                        array_push($fields, [
                            "name" => $fid['name'],
                            "type" => $fid['type']
                        ]);
                    }

                    array_push($this->relatorio['tables'], [
                        "name" => $key->name,
                        "fields" => $fields
                    ]);
                }
            }
        }

        foreach ($this->relatorio['tables'] as $key1) {
            if ($key1) {
                array_push($this->relatorio['describe'], $key1['name'] . '(' . implode(',', array_column($key1['fields'], 'name')) . ')');
            }
        }
        
        return count($this->relatorio['tables']) >= 1 ? json_encode($this->relatorio) : false;
    }
}
