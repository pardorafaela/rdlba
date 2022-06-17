<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);

require "../class/Config.php";
require "../class/DB.php";
require "../class/DB2Info.php";


$protocolo = $_GET ?  $_GET : $_POST;


$func = pg_escape_string($protocolo['funcao']);


foreach ($protocolo as $key => $value) {
    $dados[$key] = pg_escape_string($value);
}

switch ($func) {
    case 'setConfig':
        try {
            $conteudo = "[database]\n";
            unset($dados['funcao']);
            foreach ($dados as $key => $value) {
                $conteudo .= $key . "='" . $value . "'\n";
            }

            file_put_contents("../config.ini", $conteudo, FILE_TEXT);

            $resp['title'] = 'Arquivo de  configuração inicial criado.';
            $resp['status'] = 'success';
            $resp['code'] = '200';
        } catch (\Exception $e) {
            $resp['title'] = 'Erro ao criar o arquivo de configuração inicial.';
            $resp['status'] = 'error';
            $resp['code'] = '500';
            $resp['report'] = $e->getMessage();
        }
        print_r(json_encode($resp));
        break;
    case 'setProblemas':
        try {
            unset($dados['funcao']);
            if (file_exists("../config.ini")) {

                $ini = parse_ini_file('../config.ini');

                $problemas = [];
                foreach (json_decode($dados['problemas']) as $key) {
                    array_push($problemas, $key->id);
                }

                $ini['problemas'] = implode(",", $problemas);
                $conteudo = '';

                foreach ($ini as $key => $value) {
                    if (!preg_match_all("/fk/", $key)) {
                        $conteudo .= $key . "='" . $value . "'\n";
                    }
                }

                file_put_contents("../config.ini", $conteudo, FILE_TEXT);

                $resp['title'] = 'Configurações adicionadas com sucesso no arquivo de configuração inicial.';
                $resp['status'] = 'success';
                $resp['code'] = '200';
            } else throw new Exception("Arquivo .ini não existe", 1);
        } catch (\Exception $e) {
            $resp['title'] = 'Erro ao adicionar os problemas no arquivo de configuração inicial.';
            $resp['status'] = 'error';
            $resp['code'] = '500';
            $resp['report'] = $e->getMessage();
        }
        print_r(json_encode($resp));
        break;
    case 'setSchema':
        try {
            if (file_exists("../config.ini")) {
                $ini = parse_ini_file('../config.ini');

                $ini['schema'] = $dados['schema'];
                $conteudo = '';
                foreach ($ini as $key => $value) {
                    $conteudo .= $key . "='" . $value . "'\n";
                }

                file_put_contents("../config.ini", $conteudo, FILE_TEXT);
            }
            $resp['title'] = 'Configurações adicionadas com sucesso no arquivo de configuração inicial.';
            $resp['status'] = 'success';
            $resp['code'] = '200';
        } catch (\Exception $e) {
            $resp['title'] = 'Erro ao adicionar schema no arquivo de configuração inicial.';
            $resp['status'] = 'error';
            $resp['code'] = '500';
            $resp['report'] = $e->getMessage();
        }
        print_r(json_encode($resp));
        break;
    case 'getSchemas':
        try {
            $ini = parse_ini_file('../config.ini');

            $config = new Config($ini);
            $conn = $config->getPDO();
            $db = new $ini['sgbd']($conn);

            $resp['title'] = 'Schemas';
            $resp['schemas'] = $db->getSchemas();
            $resp['status'] = 'success';
            $resp['code'] = '200';
        } catch (\Exception $e) {
            $resp['title'] = 'Erro ao buscar os schemas.';
            $resp['status'] = 'error';
            $resp['code'] = '500';
            $resp['report'] = $e->getMessage();
        }
        print_r(json_encode($resp));
        break;
    case 'getProblemas':
        try {
            $dir = "../problemas/";
            $problemas = [];
            if (is_dir($dir)) {
                if ($dh = opendir($dir)) {
                    while (($nameFile = readdir($dh)) !== false) {

                        if (preg_match_all("/.php/", $nameFile)) {
                            $file = file($dir . $nameFile);
                            foreach ($file as $key) {
                                if (preg_match_all("/descrição:/", $key)) {
                                    $nameFile = preg_replace("/.php/", "", $nameFile);
                                    $descricao = ['id' => $nameFile, 'descricao' => explode("descrição:", $key)[1]];
                                    array_push($problemas, $descricao);
                                }
                            }
                        }
                    }
                    closedir($dh);
                }
            }
            $resp['title'] = 'Problemas';
            $resp['problemas'] = $problemas;
            $resp['status'] = 'success';
            $resp['code'] = '200';
        } catch (\Exception $e) {
            $resp['title'] = 'Erro ao buscar os problemas.';
            $resp['status'] = 'error';
            $resp['code'] = '500';
            $resp['report'] = $e->getMessage();
        }
        print_r(json_encode($resp));

        break;
    case 'getDBInfo':
        try {
            $ini = parse_ini_file('../config.ini');
            $config = new Config($ini);
            $conn = $config->getPDO();

            $dbinfo = new DB2Info($conn, $config->getSchema(), $config->getSGBD());

            $resp['title'] = 'Sucesso ao buscar as tabelas.';
            $resp['tabelas'] = $dbinfo->getJSON();
            $resp['status'] = 'success';
            $resp['code'] = '200';
        } catch (\Exception $e) {
            $resp['title'] = 'Erro ao buscar as tabelas.';
            $resp['status'] = 'error';
            $resp['code'] = '500';
            $resp['report'] = $e->getMessage();
        }
        print_r(json_encode($resp));

        break;
    case 'setForeign':
        try {

            unset($dados['funcao']);

            if (file_exists("../config.ini")) {

                $ini = parse_ini_file('../config.ini');

                $conteudo = '';
                foreach ($ini as $key => $value) {
                    if (!preg_match_all("/fk/", $key))
                        $conteudo .= $key . "='" . $value . "'\n";
                }

                foreach (json_decode($dados['foreign']) as $key) {
                    $conteudo .= "fk" . $key->position . "='" . $key->name . "'\n";
                }

                file_put_contents("../config.ini", $conteudo, FILE_TEXT);
            }

            $resp['title'] = 'Sucesso ao adicionar as foreign keys no arquivo de configuração.';
            $resp['status'] = 'success';
            $resp['code'] = '200';
        } catch (\Exception $e) {
            $resp['title'] = 'Erro ao adicionar as foreign keys no arquivo de configuração.';
            $resp['status'] = 'error';
            $resp['code'] = '500';
            $resp['report'] = $e->getMessage();
        }
        print_r(json_encode($resp));

        break;
    case 'rdlba':
        try {
            require "../class/RDLBA.php";
            $ini = parse_ini_file('../config.ini');
            $rdlba = new RDLBA($ini);
            $rdlba->getRelatorio();

            file_put_contents("../relatorio-" . strtolower($ini['sgbd']) . "-" . $ini['host'] . "-" . $ini['db'] . ".txt", json_encode($rdlba->getRelatorio()), FILE_TEXT);

            $rdlba->geraPDF($ini);

            $resp['title'] = 'Sucesso ao gerar o relatorio.';
            $resp['status'] = 'success';
            $resp['code'] = '200';
        } catch (\Exception $e) {
            $resp['title'] = 'Erro ao gerar o relatorio.';
            $resp['status'] = 'error';
            $resp['code'] = '500';
            $resp['report'] = $e->getMessage();
        }
        print_r(json_encode($resp));
        break;
    case 'sendFile':
        try {

            require "../class/RDLBA.php";

            RDLBA::adicionaProblema($_FILES['file']);


            $resp['title'] = 'Sucesso ao adicionar problema.';
            $resp['status'] = 'success';
            $resp['code'] = '200';
        } catch (\Exception $e) {
            $resp['title'] = 'Erro ao adicionar problema.';
            $resp['status'] = 'error';
            $resp['code'] = '500';
            $resp['report'] = $e->getMessage();
        }
        print_r(json_encode($resp));
        break;
    case 'getRelatorio':
        try {
            $ini = parse_ini_file('../config.ini');
            $relatorio = JSON_DECODE(file_get_contents("../relatorio-" . strtolower($ini['sgbd']) . "-" . $ini['host'] . "-" . $ini['db'] . ".txt"));
            $resp['title'] = 'Sucesso ao buscar o relatorio.';
            $resp['status'] = 'success';
            $resp['relatorio'] = $relatorio;
            $resp['path'] = "../rdlba/relatorio/relatorio-" . strtolower($ini['sgbd']) . "-" . $ini['host'] . "-" . $ini['db'] . ".pdf";
            $resp['code'] = '200';
        } catch (\Exception $e) {
            $resp['title'] = 'Erro ao gerar o relatorio.';
            $resp['status'] = 'error';
            $resp['code'] = '500';
            $resp['report'] = $e->getMessage();
        }
        print_r(json_encode($resp));
        break;
    default:
        break;
}
