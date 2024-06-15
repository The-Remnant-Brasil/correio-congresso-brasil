<?php

function fetch_json(string $url) {
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'headers' => 'accept: application/json'
        ]
    ]);

    $content = file_get_contents(filename: $url, context: $ctx);
    if (!$content)
        throw new Exception("Falha em obter dados em $url");

    $json = json_decode($content, true);
    if (json_last_error() != JSON_ERROR_NONE)
        throw new Exception("Falha em analisar dados em $url: " . json_last_error_msg());

    return $json;
}

function fetch_deputados(string $sex) {
    $url = "https://dadosabertos.camara.leg.br/api/v2/deputados?siglaSexo=$sex&ordem=ASC&ordenarPor=nome";
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'headers' => 'accept: application/json'
        ]
    ]);

    $content = file_get_contents(filename: $url, context: $ctx);
    if (!$content)
        throw new Exception("Falha em obter dados de deputados em $url");

    $json = json_decode($content, true);
    if (json_last_error() != JSON_ERROR_NONE)
        throw new Exception("Falha em analisar dados de deputados de $url: " . json_last_error_msg());

    return $json;

}

function load_deputados_contacts(): array {
    $homens = fetch_deputados('M')['dados'];
    $mulheres = fetch_deputados('F')['dados'];
    
    $deputados = [];

    function transform(&$out, $in, $sex) {
        foreach ($in as $i) {
            $out[$i['email']] = [
                'nome' => $i['nome'],
                'genero' => $sex,
                'partido' => $i['siglaPartido'],
                'uf' => $i['siglaUf'],
            ];
        }
    };

    transform($deputados, $homens, 'masculino');
    transform($deputados, $mulheres, 'feminino');

    return $deputados;
}

function load_senadores_contacts(): array {
    $json = fetch_json('https://legis.senado.leg.br/dadosabertos/senador/lista/atual.json')['ListaParlamentarEmExercicio']['Parlamentares']['Parlamentar'];
    $senadores = [];

    foreach ($json as $senador) {
        $identificacao = $senador['IdentificacaoParlamentar'];
        if (isset($identificacao['EmailParlamentar'])) {
            $senadores[$identificacao['EmailParlamentar']] = [
                'nome' => $identificacao['NomeParlamentar'],
                'nome_completo' => $identificacao['NomeCompletoParlamentar'],
                'genero' => strtolower($identificacao['SexoParlamentar']),
                'partido' => $identificacao['SiglaPartidoParlamentar'],
                'uf' => $identificacao['UfParlamentar'],
            ];
        }
    }

    return $senadores;
}
