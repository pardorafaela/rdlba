<?php

//descrição:Tipos incompatíveis entre campos da chave estrangeira.

class Problema6 extends Problema
{

    public $relatorio =  array(
        "type" => "warning",
        "description" => "Problema 6: Tipos incompatíveis entre campos da chave estrangeira.",
        "tables" => [],
        "describe" => []
    );

    // tabela1(campo1,campo2) referencia tabela2(campo1,campo2);
    // pra cada campo
    // tabela1(campo1:xx) difere de tabela2(campo1:yy)

    public function analisa()
    {
        foreach ($this->dbinfo->getJSON() as $key) {
            if (count($key->constraints) > 0) {
                foreach ($key->constraints as $fk) {
                    if ($fk->type == 'foreign key') {
                        $difere = false;
                        $srcFields = [];
                        $referenceFields = [];
                        foreach ($fk->fields as $pos => $value) {
                            $src = $this->dbinfo->getField(isset($fk->fields[$pos]->name) ? $fk->fields[$pos]->name : $fk->fields[$pos], $key->name);
                            $reference = $this->dbinfo->getField(isset($fk->reference->fields[$pos]->name) ? $fk->reference->fields[$pos]->name : $fk->reference->fields[$pos], $fk->reference->name);
                            array_push($srcFields, $reference->name);
                            array_push($referenceFields,$reference->name);
                            if ($reference->type != $src->type) {
                                $difere = true;
                                $retorno1 = "" . $key->name . "(" . $src->name . ":" . $src->type . ") difere de " . $fk->reference->name . "(" . $reference->name . ":" . $reference->type . ")";
                            }
                        }
                        if ($difere) {
                            $retorno = $key->name . "(" . implode(",", $srcFields) . ") referencia " . $fk->reference->name . "(" . implode(",", $referenceFields) . ")";
                            array_push($this->relatorio['describe'], $retorno);
                            array_push($this->relatorio['describe'], $retorno1);
                        }
                    }
                }
            }
        }
        return count($this->relatorio['describe']) >= 1 ? json_encode($this->relatorio) : false;
    }
}
