<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

require "vendor/autoload.php";

function add_deputados_to_contacts(bool $always_update = false): bool
{
    $deputado_path = "./deputado.xls";
    $deputado_url = 'https://www.camara.leg.br/internet/deputado/deputado.xls';
    $downloaded = false;

    if ($always_update || !file_exists($deputado_path)) {
        $downloaded = download_file($deputado_url, $deputado_path);
    }

    $Deputados = load_deputados($deputado_path);
    return add_contacts(compact('Deputados')) == 1;
}

function add_senadores_to_contacts(bool $always_update = false): bool {
    $Senadores = load_senadores("./homens_senadores.txt", "./mulheres_senadoras.txt");

    return add_contacts(compact('Senadores')) == 1;
}

function download_file(string $url, string $path): bool
{
    $data = file_get_contents($url);
    if (!$data)
        return false;

    return file_put_contents($path, $data) > 0;
}

function get_deputado_gender(string $pronoun): string
{
    if (preg_match("/Deputada/", $pronoun)) {
        return 'f';
    } else if (preg_match("/Deputado/", $pronoun)) {
        return 'm';
    } else {
        throw new Exception("Arquivo com lista de deputados corrompido");
    }
}

function parse_senador_file($file): array
{
    static $begin_party_regex = "/[A-Z][A-Z]/";
    static $email_regex = "/[\w]+\.[\w]+@[\w]+(?:\.[\w+])+/";

    $contact_senadores = [];

    while ($line = fgets($file)) {
        preg_match($begin_party_regex, $line, $party_begin);
        preg_match($email_regex, $line, $email);
        $begin_party_index = strpos($line, $party_begin[0]);

        $name = trim(substr($line, 0, $begin_party_index));
        $email = trim($email[0]);

        $contact_senadores[$email] = [
            'nome' => $name
        ];
    }

    return $contact_senadores;
}

function load_senadores(string $male_path, string $female_path): array
{
    $male_file = fopen($male_path, "r");
    $female_file = fopen($female_path, "r");

    if (!$male_file || !$female_file)
        throw new Exception("Arquivos de senadores não existem");

    $males = parse_senador_file($male_file);
    $females = parse_senador_file($female_file);

    foreach ($males as $value) {
        $value['genero'] = 'm';
    }
    foreach ($females as $value) {
        $value['genero'] = 'f';
    }

    array_merge($males, $females);

    return $males;
}

function load_deputados(string $path): array
{
    $result = [];

    if (!file_exists($path)) {
        throw new Exception("Falha em obter dados dos parlamentares, arquivo $path não existe");
    }

    $data = load_xls($path, ['A' => true, 'P' => true, 'N' => true]);

    $count_name = count($data['A']);
    $count_email = count($data['N']);
    $count_gender = count($data['P']);

    assert($count_name === $count_email && $count_name === $count_gender);

    for ($i = 0; $i < $count_name; ++$i) {
        $email = $data['N'][$i];

        $result[$email] = [
            'nome' => $data['A'][$i],
            'genero' => get_deputado_gender($data['P'][$i]),
        ];
    }

    return $result;
}

function load_xls(string $data_path, array $rows_list_keys): array
{
    $data = [];
    $spread_sheet = IOFactory::load($data_path);
    $active = $spread_sheet->getActiveSheet();
    $row_itr = $active->getRowIterator();

    foreach ($row_itr as $row_key => $row) {
        $cell_itr = $row->getCellIterator();

        if ($row_key == 1)
            continue;

        foreach ($cell_itr as $cell_key => $cell) {
            if (array_key_exists($cell_key, $rows_list_keys)) {
                $data[$cell_key][] = trim($cell->getValueString());
            }
        }
    }

    return $data;
}
