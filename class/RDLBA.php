<?php

require_once $_SERVER['DOCUMENT_ROOT'] != '' ? $_SERVER['DOCUMENT_ROOT'] . '/rdlba/vendor/autoload.php' : 'vendor/autoload.php';

require "Problema.php";


class RDLBA
{

    private $find = [];
    private $dbinfo;

    public function __construct($configFile)
    {

        $config = new Config($configFile);
        $conn = $config->getPDO();
        $this->dbinfo = new DB2Info($conn, $config->getSchema(), $config->getSGBD(), $config->getForeign($configFile));

        foreach ($config->getProblemas() as $key) {
            require_once $_SERVER['DOCUMENT_ROOT'] != '' ? $_SERVER['DOCUMENT_ROOT'] . "/rdlba/problemas/$key.php" : "problemas/$key.php";
            $prob = new $key($this->dbinfo, $conn);
            $retorno = $prob->analisa();
            if ($retorno) array_push($this->find, json_decode($retorno));
        };
    }


    public function getRelatorio()
    {
        return $this->find;
    }

    public function geraPDF($dados)
    {
        $sgbd = $dados['sgbd'];
        $host = $dados['host'];
        $port = $dados['port'];
        $user = $dados['user'];
        $db = $dados['db'];
        $schema = $dados['schema'];
        $img = $_SERVER['DOCUMENT_ROOT'] != '' ? $_SERVER['DOCUMENT_ROOT'] . "/rdlba/images/logo.jpeg" : "/rdlba/images/logo.jpeg";

        $mpdf = new \Mpdf\Mpdf(['tempDir' => '../tmp']);
        $mpdf->showImageErrors = true;
        $html = '
            <div style="position: absolute; left:0; right: 0; top: 0; bottom: 0;">
            <img src="' . $img . '" style="width: 210mm;margin: 0;" />
            </div><br><br><br><br>
            <h3>Informações da Conexão</h3><br>
                HOST: ' . $host . '<br>
                PORT: ' . $port . '<br>
                USER: ' . $user . '<br>
                DATABASE: ' . $db . '<br>
                SCHEMA: ' . $schema . '<br><br>';

        $html .= count($this->find) >= 1 ? '<h3>Problemas Encontrados</h3><br>' : '<h3>Nenhum problema encontrado</h3><br>';


        foreach ($this->find as $key) {
            $html .= '<h4>' . $key->description . '</h4><br>';
            if (count($key->describe) > 0) $html .= implode('<br>', $key->describe) . '<br>';
        }

        $mpdf->WriteHTML($html);
        $namePDF = '../relatorio/relatorio-' . strtolower($sgbd) . '-' . $host . '-' . $db . '.pdf';
        $mpdf->Output($namePDF);

        if (!file_exists($namePDF)) throw new Exception("Não foi possivel gerar o pdf.", 1);
    }

    public static function adicionaProblema($file)
    {

        $nameProblema = '../problemas/' . $file['name'];

        if (file_exists($nameProblema)) throw new Exception("Não foi possivel adicionar o problema, problema já existente.", 1);


        move_uploaded_file($_FILES['file']['tmp_name'], $nameProblema);


        if (!file_exists($nameProblema)) throw new Exception("Não foi possivel adicionar o problema.", 1);
    }
}
