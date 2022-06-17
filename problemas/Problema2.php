<?php
// descrição:Coluna auto-incremento como única chave, permitindo duplicidade
class Problema2  extends Problema
{

    public $relatorio =  array(
        "type" => "warning",
        "description" => "Problema 2: Coluna auto-incremento como única chave, permitindo duplicidade.",
        "tables" => [],
        "describe" => []
    );

    // SAIDA fulano silva brasil, 123.456.789-01, Rua A, 100: 3 linhas com mais de X% de semelhança.
    public function analisa()
    {
        foreach ($this->dbinfo->getJSON() as $key) {
            $pkSerial = 0;
            $pkNatural = 0;
            $noExistPrimary = true;
            $noExistUnique = true;
            $field = null;

            if (count($key->constraints) == 0) {
                array_push($this->relatorio['tables'], [
                    "name" => $key->name,
                    "fields" => $key->fields
                ]);
            } else {
                foreach ($key->constraints as $ctr) {
                    if ($ctr->type == 'primary key') {
                        $noExistPrimary = false;
                        foreach ($ctr->fields as $fd) {
                            $field = $this->dbinfo->getField($fd, $key->name);
                            if (gettype($field) == 'object')  $field->type == 'serial' ? $pkSerial++ : $pkNatural++;
                        }
                    }
                    if ($ctr->type == 'unique constraint')
                        $noExistUnique = false;
                }

                if ($noExistPrimary || ($noExistUnique && $pkSerial > 0 && $pkNatural == 0)) {
                    if (gettype($field) == 'object') {
                        $this->duplicidade($key->name, $field->name);
                        array_push($this->relatorio['tables'], [
                            "name" => $key->name,
                            "fields" => [
                                [
                                    "name" => $field->name,
                                    "type" => $field->type
                                ]
                            ]
                        ]);
                    }
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


    public function duplicidade($tabela, $coluna)
    {
        setlocale(LC_ALL, "pt_BR.utf8");
        $stmt = $this->conn->prepare("SELECT *FROM $tabela");
        $stmt->execute();
        $result = $stmt->fetchall(PDO::FETCH_ASSOC);
        $res = [];
        $find = [];
        foreach ($result as $key) {
            $conc = "";
            foreach ($key as $k => $val) {
                if ($k != $coluna) {
                    $conc .= $val . ",";
                }
            }
            array_push($res, rtrim($conc, ","));
        }

        foreach ($res as $lin1  => $str1) {
            $str1 =  iconv("utf-8", "ascii//TRANSLIT", strtolower(preg_replace("/[^A-Za-z0-9]/", "", $str1)));
            $numeroLinhas = 0;
            foreach ($res as $lin2 => $str2) {
                if ($lin2 > $lin1) {
                    $str2 =  iconv("utf-8", "ascii//TRANSLIT", strtolower(preg_replace("/[^A-Za-z0-9]/", "", $str2)));

                    $lvt = levenshtein($str1, $str2, 1, 1, 0); // porcentagem > 0.90

                    $porcentagem =  (strlen($str1) - $lvt) / strlen($str1) * 100;

                    if ($porcentagem >= 90) {
                        $find[$res[$lin1]][$porcentagem] = isset($find[$res[$lin1]][$porcentagem]) ? $find[$res[$lin1]][$porcentagem] + 1 : 1;
                    }
                }
            }
        }
        foreach ($find as $key => $value) {
            array_push($this->relatorio['describe'], $value[key($value)] . " linha(s) com mais de " . key($value) . "% de semelhança.");
        }
    }
}
