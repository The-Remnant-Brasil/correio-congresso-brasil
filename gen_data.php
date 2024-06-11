<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

function download_file($url, $path) {
    file_put_contents($path, file_get_contents($url));
}

function generate_politicians_list_data() {
    $data_file = "./deputado.xls";
    $url = 'https://www.camara.leg.br/internet/deputado/deputado.xls';

    if (!file_exists($data_file)) {
        download_file($url, $data_file);
    }

    if (!file_exists($data_file)) {
        return false;
    }

    $columns_data = [
        'A' => 'nomes',
        'N' => 'emails',
        'P' => 'generos',
    ];

    $data = [];

    $spread_sheet = IOFactory::load($data_file);

    $active = $spread_sheet->getActiveSheet();

    $row_itr = $active->getRowIterator();

    foreach ($row_itr as $row_key => $row) {
        $cell_itr = $row->getCellIterator();

        if ($row_key == 1)
            continue;

        foreach ($cell_itr as $cell_key => $cell) {
            if (!array_key_exists($cell_key, $columns_data)) {
                continue;
            }

            $column_type = $columns_data[$cell_key];

            $data[$column_type][] = trim($cell->getValueString());
        }
    }

    foreach ($data as $data_type => $data_values) {
        $data_string = implode("\n", $data_values);
        $data_string = trim($data_string);
        file_put_contents("$data_type.txt", $data_string);
    }

    return true;
}

