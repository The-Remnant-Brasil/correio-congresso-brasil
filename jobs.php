<?php

require "contacts.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

const CACHE_PATHS = [
    'incomplete_jobs' =>  __DIR__ . "/jobs/incomplete",
    'complete_jobs' =>  __DIR__ . "/jobs/complete",
];

class MailJob {
    public array $emails_already_sent_list;
    private array $emails_to_send_list;
    private int $emails_already_send_count;
    private int $total_emails_to_send_count;

    function __construct(private string $name, private string $template, private string $subject, private array $contacts_groups, array $contacts_list)
    {
        $this->emails_to_send_list = $contacts_list;
        $this->total_emails_to_send_count = count($contacts_list);
        $this->emails_already_send_count = 0;
        $this->emails_already_sent_list = [];
    }
    
    /** @return array{body: string, to_info: array{nome: string, genero: string}, to_email: string} */
    function sendNext(string $from, string $username, string $password, string $host, string $port): array|false
    {
        $email_to = array_key_last($this->emails_to_send_list);

        if (!$email_to)
            return false;

        $contact_info = $this->emails_to_send_list[$email_to];
        $body = self::transformTemplate($contact_info, $this->template);

        send_email($from, $username, $body, $this->subject, $email_to, $password, $host, $port);

        ++$this->emails_already_send_count;
        array_pop($this->emails_to_send_list);
        $this->emails_already_sent_list[$email_to] = $contact_info;

        return [
            'body' => $body,
            'to_info' => $contact_info,
            'to_email' => $email_to,
        ];
    }

    public function getEmailsPreview(string $attribute_key = null): array 
    {
        $previews = [];

        if ($attribute_key == null) {
            foreach ($this->emails_to_send_list as $email => $contact_info) {
                $previews[$email] = self::transformTemplate($contact_info, $this->template);
            }
        } else {
            foreach ($this->emails_to_send_list as $email => $contact_info) {
                $key = $contact_info[$attribute_key];
                if (!$key) {
                    throw new Exception("Não pode obter preview mapeado usando $attribute_key: chave não existe num dos grupos de contato [" . implode(", ", $this->contacts_groups) . "]");
                }
                $previews[$key] = self::transformTemplate($contact_info, $this->template);
            }
        }


        return $previews;
    }

    private static function transformTemplate(array $contact_info, string $template): string
    {
        static $male_replace = "/\(m=([^)]+)\)/";
        static $female_replace = "/\(f=([^)]+)\)/";

        $patterns = ["/\n|\r\n/"];
        $replacements = ["<br>"];

        foreach ($contact_info as $data_type => $value) {
            switch ($data_type) {
            case 'genero':
                $patterns[] = $male_replace;
                $replacements[] = $value[0] === 'm' ? "$1" : "";

                $patterns[] = $female_replace;
                $replacements[] = $value[0] === 'f' ? "$1" : "";

                $patterns[] = "/\(\)/";
                $replacements[] = $value[0] === 'm' ? 'o' : 'a';
                break;

            default: 
                $patterns[] = '/{' . $data_type . '}/';
                $replacements[] = $value;
            }
        }

        return preg_replace($patterns, $replacements, $template);
    }

    public function getEmailsAlreadySendCount(): int
    {
        return $this->emails_already_send_count;
    }

    public function getTotalEmailsToSendCount(): int
    {
        return $this->total_emails_to_send_count;
    }

    public function getRemainingEmailsToSend() : int {
        return $this->total_emails_to_send_count - $this->emails_already_send_count;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getContactsGroups(): array
    {
        return $this->contacts_groups;
    }
}

function get_complete_jobs_path(string $name_sanitized) {
    return CACHE_PATHS['complete_jobs'] . "/" . $name_sanitized . '.json';
}

function get_incomplete_jobs_path(string $name_sanitized) {
    return CACHE_PATHS['incomplete_jobs'] . "/" . $name_sanitized . '.json';
}

function send_email($from, $name, $body, $subject, $to, $password, $host, $port) {
    $mail = new PHPMailer(true);
    $mail->SMTPDebug = SMTP::DEBUG_OFF;                      //Enable verbose debug output
    $mail->isSMTP();                                            //Se
    $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
    $mail->setLanguage("pt_br");
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
    $mail->isHTML(true);                                  //Set email format to HTML
    $mail->CharSet = PHPMailer::CHARSET_UTF8;

    $mail->Username   = $from;                     //SMTP username
    $mail->Password   = $password;                               //SMTP password
    $mail->Host       = $host;                     //Set the SMTP server to send through
    $mail->Port       = $port;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->AltBody = $body;
    $mail->setFrom($from, $name);
    $mail->addAddress($to);

    $mail->send();
}

/**
 * @return array{unfinished: MailJob[], finished: MailJob[]}
 * */
function get_all_email_jobs() {
    $unfinished = load_all_files_contents_directory(CACHE_PATHS['incomplete_jobs']);
    $finished = load_all_files_contents_directory(CACHE_PATHS['complete_jobs']);

    $unfinished = array_map(fn($val) => unserialize($val), $unfinished);
    $finished = array_map(fn($val) => unserialize($val), $finished);

    return [
        'unfinished' => $unfinished,
        'finished' => $finished,
    ];
}

function get_email_job(string $name) : MailJob {
    $name = sanitize_filename($name);

    if (file_exists($path = get_complete_jobs_path($name))) {

    } else if (!file_exists($path = get_incomplete_jobs_path($name))) {
        throw new Exception("Arquivo com informações de grupo de email $name não existe ou foi deletado");
    }

    $content = file_get_contents($path);
    if (!($content = file_get_contents($path)) || !($job = unserialize($content)))
        throw new Exception("Arquivo com informações de grupo de email $name corrompido");

    return $job;
}

function save_email_job(MailJob $job): bool {
    $finished = $job->getEmailsAlreadySendCount() > 0;
    $name = sanitize_filename($job->getName());

    if ($finished) {
        if (file_exists($oldfile = get_incomplete_jobs_path($name)))
            unlink($oldfile);
        $path = get_complete_jobs_path($name);
    } else {
        $path = get_incomplete_jobs_path($name);
    }

    if (!file_exists($dir = dirname($path))) {
        mkdir(directory: $dir, recursive: true);
    }

    return file_put_contents($path, serialize($job)) > 0;
}

function delete_email_job(string $name) {
    $name = sanitize_filename($name);

    if (file_exists($path = get_incomplete_jobs_path($name)))
        unlink($path);
    else if (file_exists($path = get_complete_jobs_path($name)))
        unlink($path);
}

function create_email_job(string $name, string $template, string $subject, array $contacts_groups): MailJob {
    $contacts_list = get_contacts_by_group($contacts_groups);
    if (!$contacts_list) {
        throw new Exception("Não pode achar contatos do tipo: " . implode(", ", $contacts_groups));
    }

    return new MailJob($name, $template, $subject, $contacts_groups, $contacts_list);
}
