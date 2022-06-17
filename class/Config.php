<?php

class Config
{

    private  $configConn;
    private  $schema;
    private  $SGBD;
    private  $problemas;


    public function __construct($file)
    {
        $this->validaArquivo($file);
        if (isset($file['problemas'])) $this->problemas = explode(",", $file['problemas']);
        if (isset($file['schema'])) $this->schema = $file['schema'];
        $this->SGBD = $file['sgbd'];
        $this->configConn =  $file['driver'] . ':host=' . $file['host'] . ';port=' . $file['port'] . ';dbname=' . $file['db'] . ';user=' . $file['user'] . ';password=' . $file['pass'];
    }

    private function validaArquivo($file)
    {
      
        $valida = [];
        foreach ($file as $key => $value) {
            if ($key == 'sgbd' && !preg_match('/[Postgres|Mysql]/i', $value))
                $valida[$key] = $value;
            if ($key == 'port' && !preg_match('/[\d]/i', $value))
                $valida[$key] = $value;
            if ($key == 'driver' && !preg_match('/[pgsql|mysql]/i', $value))
                $valida[$key] = $value;

            if ($key == 'problemas') {
                $problemas = explode(",", $value);
                $problemasDisponiveis = [];
                $dir = $_SERVER['DOCUMENT_ROOT'] != '' ? $_SERVER['DOCUMENT_ROOT'].'/rdlba/problemas/' : 'problemas/';
                if (is_dir($dir)) {
                    if ($dh = opendir($dir)) {
                        while (($nameFile = readdir($dh)) !== false) {
                            if (preg_match_all("/.php/", $nameFile)) {
                                $file = file($dir . $nameFile);
                                foreach ($file as $key) {
                                    $nameFile = preg_replace("/.php/", "", $nameFile);
                                    array_push($problemasDisponiveis, $nameFile);
                                }
                            }
                        }
                        closedir($dh);
                    }
                }
              
                foreach ($problemas as $prob) {
                    if (!in_array($prob, $problemasDisponiveis)) {
                        $valida[$prob] = $prob;
                    }
                }
            } 

            if (preg_match_all("/fk/", $key)) {
                if(!preg_match_all("/[a-zA-Z0-9]+\s([\([[a-zA-Z0-9]+:[a-zA-Z0-9]+(\(\d+\))?,?)+\)\s=\s[a-zA-Z0-9]+\s([\([[a-zA-Z0-9]+:[a-zA-Z0-9\s]+(\(\d+\))?,?)+\)/i", $value))
                    $valida[$key] = $value;
            }
        }
        if(count($valida) > 0)
            throw new Exception("Arquivo de configuração inicial com parâmetros errados", 1);
            
    }

    public function getSchema()
    {
        return $this->schema;
    }

    public function getSGBD()
    {
        return $this->SGBD;
    }


    public function getPDO() :PDO
    {

        $conn = new PDO($this->configConn);
        $conn->setAttribute(PDO::FETCH_ASSOC, PDO::FETCH_ASSOC);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    }


    public function getProblemas(): array
    {
        return $this->problemas;
    }

    public function getForeign($fk): array
    {
        $fks = [];
        foreach ($fk as $key => $value) {
            if (preg_match_all("/fk/", $key))
                array_push($fks, $value);
        }
        return $fks;
    }
}
