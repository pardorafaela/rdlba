<?php

//descrição:O campo participa de uma chave estrangeira porém não participa da chave primária.

class Problema5 extends Problema
{

    public $relatorio =  array(
        "type" => "warning",
        "description" => "Problema 5: O campo participa de uma chave estrangeira porém não participa da chave primária.",
        "tables" => [],
        "describe" => []
    );

    // tabela1(campo1,campo2) referencia tabela2(campo1,campo2);
    // tabela2(campo1,campo2) não é chave primária


    public function analisa()
    {
        foreach ($this->dbinfo->getJSON() as $key) {
            if (count($key->constraints) > 0) {
                foreach ($key->constraints as $fk) {
                    if ($fk->type == 'foreign key') {
                        $val = [];
                        $tabela2 = [];
                        $naoPrimary  = [];
                        foreach ($fk->fields as $pos => $value) {
                            if (!$this->isPrimaryKey($fk->reference->fields[$pos], $fk->reference->name))
                                array_push($naoPrimary, isset($fk->reference->fields[$pos]->name) ? $fk->reference->fields[$pos]->name : $fk->reference->fields[$pos]);


                            array_push($val, isset($value->name) ? $value->name : $value);

                            array_push($tabela2, isset($fk->reference->fields[$pos]->name) ? $fk->reference->fields[$pos]->name : $fk->reference->fields[$pos]);
                        }
                        if (count($naoPrimary) > 0) {
                            $retorno = $key->name . "(" . implode(",", $val) . ") referencia " . $fk->reference->name . "(" . implode(".", $tabela2) . ")";
                            array_push($this->relatorio['describe'], $retorno);
                            $retorno = "\n" . $fk->reference->name . "(" . implode(",", $naoPrimary) . ") não é chave primária.";
                            array_push($this->relatorio['describe'], $retorno);
                        }
                    }
                }
            }
        }
        return count($this->relatorio['describe']) >= 1 ? json_encode($this->relatorio) : false;
    }


    private function isPrimaryKey($column, $table)
    {
        foreach ($this->dbinfo->getJSON() as $key) {
            if ($key->name == $table) {
                foreach ($key->constraints as $ctr) {
                    if ($ctr->type == 'primary key')
                        return in_array($column, $ctr->fields);
                }
            }
        }
        return false;
    }
}
