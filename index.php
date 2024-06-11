<style type="text/css">
    @import url("./styles.css");
</style>

<?php

require __DIR__ . "/functions.php";

try {
    $data = get_politicians_list_data();

    $count_name = count($data['names']);
    $count_emails = count($data['emails']);
    $count_genders = count($data['genders']);

    if (!($count_name === $count_emails && $count_emails === $count_genders)) {
        throw new Exception("Arquivos com informações dos deputados corrompidos");
    }

    $group_email_name = $from = $name = $template = $subject = $password = '';

    if (
        !empty($_GET['nome']) &&
        !empty($_GET['corpo']) &&
        !empty($_GET['assunto']) &&
        !empty($_GET['email']) &&
        !empty($_GET['senha'])
    ) {
        $name = $_GET['nome'];
        $template = $_GET['corpo'];
        $subject = $_GET['assunto'];
        $from = $_GET['email'];
        $password = $_GET['senha'];
        $group_email_name = $_GET['nome-grupo'];
        $host = get_host($from);

        if (isset($_GET['preview'])) {
            $male_found = false;
            $female_found = false;

            for ($i = 0; $i < 3; ++$i) {
                if ($data['genders'][$i] === 'f' && !$female_found) {
                    $female_preview = get_body($data['names'][$i], $data['genders'][$i], $template);
                } else if (!$male_found) {
                    $male_preview = get_body($data['names'][$i], $data['genders'][$i], $template);
                }

                if ($male_found && $female_found)
                    break;
            }
        }
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

?>


<form class="email-form container flowv" action="send.php" method="get">
    <h1>
        Enviar Mensagem a todos deputados federais do país de uma única vez.
    </h1>

    <div class="prev-messages">
        <?php

        if (isset($error)) {
            echo "
                <div class=\"error\" role=\"alert\" aria-label=\"Alerta de erro\"> 
                    <h2> Erro ocorreu </h2> 
                    <p> $error </p>
                </div>
            ";
        }

        if (isset($male_preview)) {
            echo "
                 <div class=\"preview content-box flowv\">
                    <h2 style=\"font-size: 1.4em;\">Preview de mensagens</h2>
                     <div>
                         Email para destinatário masculino:
                         <pre>$male_preview</pre>
                     </div>
                     <div>
                         Email para destinatário feminino:
                         <pre>$female_preview</pre>
                     </div>

                     <p>
                     <b>Clique no botão de enviar emails para confirmar.</b>
                     </p>

                 </div>
            ";
        }

        if (isset($ok_message)) {
            echo "
                <h2 class=\"content-box ok-message\">
                    $ok_message
                </h2>
            ";
        }

        ?>

    </div>

    <div class="main-content flowv">
        <div class="side-info">
            <div class="content-box program-description flowv">

                <p>
                    Servidores de email suportados - GMAIL (recomendado) - HOTMAIL (Não testado)
                </p>

                <p>
                    <p>
                    A senha utilizada para o gmail é uma senha de app que pode ser obtida a partir do seguinte link (presumido que esteja logado):<br>
                    <a href="https://myaccount.google.com/apppasswords">https://myaccount.google.com/apppasswords</a>
                  </p>
                          
                  <p>
                  Para saber mais sobre as senhas de app:
                    <a href="https://support.google.com/accounts/answer/185833?hl=pt-BR">https://support.google.com/accounts/answer/185833?hl=pt-BR</a>
                  </p>
                </p>

                <p><b style="color: red">
                        Os provedores de email permitem mandar apenas 500 emails por dia gratuitamente, e por esta razão o programa só poderá mandar
                        para 500 deputados num mesmo dia. Caso queira enviar para todos, utilizar o campo
                        "Grupo de Envio" no formulário com o mesmo valor a fim de pular os deputados que já receberam o email e enviar no dia seguinte.
                        Ao invés de esperar outro dia, pode-se mudar o email de envio, mas é necessário manter o nome do "Grupo de envio" para não repetir os envios já feitos.
                    </b></p>

                <p>

                </p>
            </div>

            <div class="content-box program-warrant">
                <b>Atenção</b>: Este programa não irá coletar nem armazenar nenhum dado inserido nos campos de formulário.
                Os dados serão utilizados localmente e apenas uma única vez para o envio direto
                dos emails aos parlamentares.
                O código-fonte do programa está disponível no diretório www.
            </div>

            <div class="content-box flowv email-body-explanation">
                <h2>Atenção ao corpo do email</h2>

                <p> Texto em formato <abbr><b>HTML</b></abbr> </p>

                <textarea class="example-html" readonly>
<i> Italico </i>
<b> Negrito </b>
            </textarea>

                <p>Palavras chaves (inseridas automaticamente pelo programa):</p>
                <table class="body-description-table">
                    <tr>
                        <th>Chave</th>
                        <th>Será substituído por</th>
                    </tr>
                    <tr>
                        <td>NOME_DEPUTADO</td>
                        <td>
                            Se transforma no nome do deputado
                        </td>
                    </tr>
                    <tr>
                        <td>(m=texto)</td>
                        <td>
                            O texto somente aparece se o destinatário for homem
                        </td>
                    </tr>
                    <tr>
                        <td>(f=texto)</td>
                        <td>
                            O texto somente aparece se o destinatário for mulher
                        </td>
                    </tr>
                    <tr>
                        <td>()</td>
                        <td>
                            Se transforma em "o" para destinatário homem e "a" para mulher.
                        </td>
                    </tr>
                </table>


                <p>Exemplo:</p>

                <textarea class="example-html" readonly rows=5>
Car() (f=senhora)(m=senhor) NOME_DEPUTADO, um projeto... 
Muito <b> Importante </b> será votado

Atenciosamente <i> João </i> 
            </textarea>

                <p> Se transforma em: </p>

                <p class="examples">

                <div class="example-box">
                    Caro senhor Ronaldo Caiado, um projeto... <br>
                    Muito <b> Importante </b> será votado<br><br>
                    Atenciosamente <i> João </i>
                </div>

                <div class="example-box">
                    Cara senhora Ana campagnolo, um projeto... <BR>
                    Muito <b> Importante </b> será votado<br><br>
                    Atenciosamente <i> João </i>
                </div>

                </p>

            </div>

            <div class="content-box politicians-list-wrapper flowv">
                <h2>Lista de Deputados que receberão o email.</h2>

                <div class="politicians-list">
                    <table>
                        <tr>
                            <th>Nomes</th>
                            <th>Emails</th>
                        </tr>

                        <?php

                        for ($i = 0; $i < $count_name; ++$i) {
                            echo "
                            <tr>
                                <td> {$data['names'][$i]} </td>
                                <td> {$data['emails'][$i]} </td>
                            </tr>
                            ";
                        }

                        ?>

                    </table>
                </div>
            </div>
        </div>

        <div class="form-inputs flowv">
            <div class="form-group">
                <label for="nome">Nome</label>
                <input name="nome" required value="<?= $name ?>" placeholder="Nome que sera exibido no envio">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" required value="<?= $from ?>" placeholder="exemplo@gmail.com">
            </div>

            <div class="form-group">
                <label for="nome-grupo">Grupo de envio </label>
                <input name="nome-grupo" required pattern="[a-zA-Z0-9_]+" value="<?= $group_email_name ?>" placeholder="lei_1904">
                <div>Deve conter apenas letras, números e sublinhados</div>
                <div> (Caso falhe em mandar para todos os deputados, na próxima tentativa do grupo o programa irá pular os que já receberam o email para este grupo) </div>
            </div>

            <div class="form-group">

                <label for="senha">Senha</label>
                <input type="password" name="senha" required value="<?= $password ?>" placeholder="Minha Senha">
            </div>

            <div class="form-group">
                <label for="assunto">Assunto</label>
                <input name="assunto" required value="<?= $subject ?>" placeholder="Urgente: Projeto de Lei PL 1904...">
            </div>

            <div class="form-group">
                <label for="corpo">Corpo do Email</label>
                <textarea rows="10" name="corpo" required placeholder="Prezad() deputad() NOME_DEPUTADO, recentemente o projeto de lei ..."><?= $template ?></textarea>
            </div>

            <div class="buttons">
                <button type="submit" class="submit">Enviar emails</button>
                <button type="button" class="preview-button">Visualizar email enviado</button>
            </div>
        </div>
    </div>


</form>

<script defer>
    /** @type HTMLFormElement */
    const form = document.querySelector('.email-form')
    const button = document.querySelector(".preview-button")
    const prev_messages = document.querySelector(".prev-messages")
    const submit = document.querySelector('.submit')

    function remove_prev_messages() {
        prev_messages.parentNode.removeChild(prev_messages)
    }

    submit.addEventListener('click', remove_prev_messages)

    button.addEventListener('click', () => {
        const input = document.createElement('input')
        input.type = 'hidden'
        input.name = 'preview'
        form.appendChild(input)

        form.action = 'index.php'

        if (form.checkValidity())
            form.submit()
        else {
            form.reportValidity()
            remove_prev_messages()
        }
    })
</script>
