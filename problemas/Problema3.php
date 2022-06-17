<?php
// descrição:Chaves estrangeiras não informadas
class Problema3 extends Problema
{

    public $relatorio =  array(
        "type" => "warning",
        "description" => "Problema 3: Chaves estrangeiras não informadas.",
        "tables" => [],
        "describe" => []
    );
    // 'ponto (idFuncionario:int) = funcionario (idFuncionario:serial)'

    public function analisa()
    {
        foreach ($this->dbinfo->getJSON() as $tbl) {
            foreach ($tbl->constraints as $ctr) {
                if ($ctr->type == 'foreign key' && $ctr->from == 'user') {
                    $fields = [];
                    $referenceFields = [];
                    foreach ($ctr->fields as $fd) {
                        array_push($fields, [
                            'name' => $fd->name,
                            'type' => $fd->type,
                        ]);
                    }
                    foreach ($ctr->reference->fields as $fd) {
                        array_push($referenceFields, [
                            'name' => $fd->name,
                            'type' => $fd->type,
                        ]);
                    }
                    array_push($this->relatorio['tables'], [
                        "name" => $tbl->name,
                        "fields" => $fields,
                    ]);
                    array_push($this->relatorio["describe"], $tbl->name . "(" . implode(",", array_column($fields, "name")) . ") references " . $ctr->reference->name . "(" . implode(",", array_column($referenceFields, "name")) . ")");
                }
            }
        }
        return count($this->relatorio['tables']) >= 1 ? json_encode($this->relatorio) : false;
    }
}
