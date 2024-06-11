<style type="text/css">
    @import url("./styles.css");
</style>

<script> 
function setup_terminal_auto_scroll_on_message() {
    const terminal = document.querySelector('.terminal')

    if (!terminal) {
        setTimeout(setup_terminal_auto_scroll_on_message, 2000)
        return
    }

    const observer = new MutationObserver((records) => {
        setTimeout(() => window.scrollBy(0, 1000000), 100)
    })

    observer.observe(terminal, {
        childList: true,
        attributes: false,
        characterData: true,
    })
}

setTimeout(setup_terminal_auto_scroll_on_message, 2000)

</script>

<div class="flowv container">
    <h1>Enviando os emails...</h1>
    <a href="index.php"> Retornar a página inicial </a>
    <div class="warning">
        <p>Não fechar o programa: Operação pode demorar</p>
        <p>Caso o programa feche inesperadamente, a lista dos deputados que já receberam o email deste grupo pode não ficar salva, e não será possível fazer o envio com exclusão dos que já receberam da próxima vez.</p>
    </div>

    <div class="terminal flowv">
        <div>Enviando...</div>
        
        <?php

        require __DIR__ . "/functions.php";

        try {
            $data = get_politicians_list_data();

            $emails_send = 0;
            $emails_already_sent_count = 0;

            $count_name = count($data['names']);
            $count_emails = count($data['emails']);
            $count_genders = count($data['genders']);

            if (!($count_name === $count_emails && $count_emails === $count_genders)) {
                throw new Exception("Arquivos com informações dos deputados corrompidos");
            }

            $name = $_GET['nome'];
            $template = $_GET['corpo'];
            $subject = $_GET['assunto'];
            $from = $_GET['email'];
            $password = $_GET['senha'];
            $host = get_host($from);
            $group_email_name = $_GET['nome-grupo'];

            $file_emails_already_send = get_filename_emails_already_sent($group_email_name);

            $emails_already_sent = get_emails_already_sent($file_emails_already_send);
            $emails_already_sent_count = count($emails_already_sent);

            if (!empty($emails_already_sent))
                echo "<div>Arquivo $file_emails_already_send encontrado, $emails_already_sent_count parlamentares serão pulados para esta tentativa.</div>";
            else 
                echo "<div>Arquivo $file_emails_already_send não existe, nenhum parlamentar será pulado nesta tentativa.</div>";

            for ($i = 0; $i < $count_name; ++$i) {
                $current_to = $data['emails'][$i];
                $current_name = $data['names'][$i];

                if (isset($emails_already_sent[$current_to])) {
                    echo "<div>Email para $current_name ($current_to)  já foi enviado, pulando.</div>";
                    continue;
                }

                send_email(
                    $from,
                    $name,
                    get_body($current_name, $data['genders'][$i], $template),
                    $subject,
                    $current_to,
                    $password,
                    $host,
                    465,
                );

                ++$emails_send;
                ++$emails_already_sent_count;

                $emails_already_sent[$current_to] = true;
            }

        } catch (Exception $e) {
            echo "
                <div class=\"terminal-error\">Ocorreu um erro ao enviar os emails: {$e->getMessage()}</div>
            ";
        } finally {
            echo "<div>$emails_send emails enviados nesta tentiva, este grupo enviou $emails_already_sent_count no total.</div>";
             

            if (!empty($emails_already_sent)) {
                store_already_sent_emails($file_emails_already_send, $emails_already_sent);
                echo "
                    <div> 
                        Lista de deputados que receberam este email estão no arquivo \"$file_emails_already_send\",
                        e pode ser utilizado para pular emails da próxima tentativa
                    </div>
                ";
            }
        }

        ?>
    </div>
</div>
