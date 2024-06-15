<style>
    @import url("./styles.css");
</style>

<?php

if (
    !empty($_GET['email']) &&
    !empty($_GET['senha']) &&
    !empty($_GET['titulo'])
) {
    $from = $_GET['email'];
    $name = $_GET['nome'];
    $password = $_GET['senha'];
    $job_title = $_GET['titulo'];
} else {
    $error = "Não pode enviar emails: Requisição mal formada";
}

require "header.php";

?>

<div class="flowv container">
    <h1>Enviando os emails</h1>
    <div class="warning">
        <p>Não fechar o programa: Operação pode demorar</p>
        <p>Caso o programa feche inesperadamente, a lista dos deputados que já receberam o email deste grupo pode não ficar salva, e não será possível fazer o envio com exclusão dos que já receberam da próxima vez.</p>
    </div>

    <?php if (isset($error)): ?>
        <div class="error"> <?= $error ?></div> 
    <?php else: ?>
        <div class="terminal flowv">
            <div>Enviando...</div>
            
            <?php

            require "vendor/autoload.php";
            require "jobs.php";

            try {
                $host = get_host($from);
                $sent_count = 0;

                $job = get_email_job($job_title);

                if (!empty($job->emails_already_sent_list)) {
                    echo "<div><b>Emails que já foram enviados:</b>";
                    echo "<ul>";
                    foreach ($job->emails_already_sent_list as $email => $contact_info) {
                        echo "<li style=\"margin-left: 2em\">$email</li>";
                    }
                    echo "</ul></div>";
                }

                while ($job->getRemainingEmailsToSend()) {
                    $email = $job->sendNext($from, $name, $password, $host, 465); 
                    ++$sent_count;

                    echo "<div>
                        Email enviado para {$email['to_info']['nome']}:
                        <pre>{$email['body']}</pre>
                    </div>";
                }

            } catch (\Exception $e) {
                echo " <div class=\"terminal-error\">Ocorreu um erro ao enviar os emails: {$e->getMessage()}</div> ";

            } finally {
                echo " <div>$sent_count emails enviados nesta tentativa, este grupo enviou {$job->getEmailsAlreadySendCount()} no total.</div> ";

                if (isset($job)) {
                    save_email_job($job);

                    if ($job->getRemainingEmailsToSend() > 0) {
                        echo " <div> Ainda restam {$job->getRemainingEmailsToSend()} para enviar, caso tenha excedido o limite, tente outro email ou tente novamente no dia seguinte. </div> ";
                    }
                }
            }

            ?>
        </div>
    <?php endif; ?>
</div>
