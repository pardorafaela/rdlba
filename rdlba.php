<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

require 'class/Config.php';
require 'class/DB.php';
require 'class/DB2Info.php';
require 'class/RDLBA.php';

$ini = parse_ini_file('config.ini');
$rdlba = new RDLBA($ini);

$rdlba->getRelatorio();

$rdlba->geraPDF($ini);


echo "PDF GERADO COM SUCESSO com o seguinte nome:"."relatorio-" . strtolower($ini['sgbd']) . "-" . $ini['host'] . "-" . $ini['db'] . ".pdf\n" ;