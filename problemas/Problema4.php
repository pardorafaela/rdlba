<?php
//descrição:Chave estrangeira Parcial


class Problema4 extends Problema
{
    public $relatorio =  array(
        "type" => "warning",
        "description" => "Problema 4: Chave estrangeira Parcial.",
        "tables" => [],
        "describe" => []
    );

    // tabela1(campo1) referencia tabela2(campo1);
    // A chave primária em tabela2 é (campo1,campo2)

    public function analisa()
    {

        foreach ($this->dbinfo->getJSON() as $key) {
            if (count($key->constraints) > 0) {
                foreach ($key->constraints as $fk) {
                    if ($fk->type == 'foreign key') {
                        foreach ($this->dbinfo->getJSON() as $pk) {
                            if ($pk->name == $fk->reference->name) {
                                foreach ($pk->constraints as $pkConstraint) {
                                    if ($pkConstraint->type == 'primary key') {
                                        if (count($pkConstraint->fields) != count($fk->fields)) {
                                            $fields = [];
                                            foreach ($fk->fields as $fd) {
                                                array_push($fields, $fd->name);
                                            }
                                            $retorno = $fk->name . "(" . implode(",", $fields) . ") referencia " . $pk->name . "(";

                                            array_push($this->relatorio, $retorno);

                                            $fields = [];
                                            foreach ($fk->reference->fields as $fd) {
                                                array_push($fields, $fd->name);
                                            }
                                            $retorno .= implode(",", $fields) . ");";
                                            
                                            array_push($this->relatorio['describe'], $retorno);

                                            $retorno =  "\n E a chave primária em " . $fk->reference->name . " é (";

                                            $fields = [];
                                            foreach ($pkConstraint->fields as $fd) {
                                                array_push($fields, $fd);
                                            }
                                            $retorno .= implode(",", $fields) . ")";

                                            array_push($this->relatorio['describe'], $retorno);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return count($this->relatorio['describe']) >= 1 ? json_encode($this->relatorio) : false;
    }
}
