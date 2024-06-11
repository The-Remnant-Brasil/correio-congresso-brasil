<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

const GROUP_ALREADY_SEND_CACHE_FOLDER = __DIR__ . "/group_cache";

function politicians_list_data_exists() {
    $files = ['nomes.txt', 'generos.txt', 'emails.txt'];

    foreach ($files as $file)
        if (!file_exists($file))
            return false;

    return true;
}

function get_politicians_list_data() {

    if (!politicians_list_data_exists()) {
        require "./gen_data.php";
        generate_politicians_list_data();
    }

    if (!politicians_list_data_exists()) {
        throw new Exception(
            'Não foi possível obter dados dos políticos, arquivos de texto con nomes, emails e gêneros faltando. 
            <br>Arquivo deputado.xls não foi encontrado.
            <br> Não foi possível fazer o download do arquivo pelo site da câmara
        ');
    }

    $data = [];
    
    $data['names'] = parse_file("./nomes.txt", 'parse_name_callback');
    $data['emails'] = parse_file("./emails.txt", 'parse_email_callback');
    $data['genders'] = parse_file("./generos.txt", 'parse_gender_callback');

    return $data;
}

function get_filename_emails_already_sent($group_email_name) {
    if (!file_exists(GROUP_ALREADY_SEND_CACHE_FOLDER))
        mkdir(GROUP_ALREADY_SEND_CACHE_FOLDER);

    return GROUP_ALREADY_SEND_CACHE_FOLDER . "/" . preg_replace("[^a-zA-Z0-9_]", "", $group_email_name) . ".txt";
}

function store_already_sent_emails($path, $emails_already_sent) {
    $emails_already_sent_string = implode("\n", array_keys($emails_already_sent));
    file_put_contents($path, $emails_already_sent_string);
}

function get_emails_already_sent($path) {
    $emails_already_sent = [];
    $file = fopen($path, "r");
    if (!$file)
        return [];
    
    while ($line = fgets($file)) {
        $line = trim($line);
        $emails_already_sent[$line] = true;
    }

    fclose($file);

    return $emails_already_sent;
}

function send_email($from, $name, $body, $subject, $to, $password, $host, $port) {
    $mail = new PHPMailer(true);
    $mail->SMTPDebug = SMTP::DEBUG_OFF;                      //Enable verbose debug output
    $mail->isSMTP();                                            //Se
    $mail->Host       = $host;                     //Set the SMTP server to send through
    $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
    $mail->Username   = $from;                     //SMTP username
    $mail->Password   = $password;                               //SMTP password
    $mail->setLanguage("pt_br");
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
    $mail->Port       = $port;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
    $mail->isHTML(true);                                  //Set email format to HTML
    $mail->setFrom($from, $name);
    $mail->CharSet = PHPMailer::CHARSET_UTF8;

    $mail->addAddress($to);
    $mail->Body = $body;
    $mail->AltBody = $body;
    $mail->Subject = $subject;

    $mail->send();

    echo "
        <p>
            E-email enviado para $to:<br>
            <pre>
    $body
            </pre>
        </p>
    ";
}

function parse_gender_callback($line, &$genders) {
    if (preg_match("/Deputada/", $line)) {
        $genders[] = 'f';
    } else if (preg_match("/Deputado/", $line)) {
        $genders[] = 'm';
    } else {
        throw new Exception("Arquivo com lista de deputados corrompido");
    }
}

function parse_name_callback($line, &$names) {
    $names[] = $line;
}

function parse_email_callback($line, &$emails) {
    $emails[] = $line;
}

function parse_file($path, $lineCallback) {
    $file = fopen($path, 'r');
    if (!$file)
        throw new Exception("Erro ao tentar abrir $file");

    $data = [];

    while ($line = fgets($file)) {
        $line = trim($line);
        $lineCallback($line, $data);
    }

    fclose($file);

    return $data;
}

function get_body($depName, $gender, $template) {
    static $m_regex = "/\(m=([^)]+)\)/";
    static $f_regex = "/\(f=([^)]+)\)/";

    return preg_replace(
        [$m_regex, $f_regex, "/NOME_DEPUTADO/", "/\(\)/", "/\n|\r\n/"],
        [
            $gender === 'm' ? "$1" : "",
            $gender === 'f' ? "$1" : "",
            $depName,
            $gender === 'm' ? 'o' : 'a',
            "<br>"
        ],
        $template
    );
}

function get_host($from) {
    static $providers = [
        '@outlook' => 'smtp-email.outlook.com',
        '@gmail.com$' => 'smtp.gmail.com',
    ];

    foreach ($providers as $regex => $server_name) {
        if (preg_match("/$regex/", $from)) {
            return $server_name;
        }
    }

    throw new Exception("Servidor para email $from não suportado.");
}
