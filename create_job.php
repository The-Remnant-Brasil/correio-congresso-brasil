<style>
    @import url("./styles.css");

    .create-job-section-content {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2em;
        margin-bottom: 2em;
    }

    .email-body-explanation textarea {
        width: 100%;
    }

    .form-group-checkbox-list {
        display: flex;
    }

    .form-group-checkbox-list > * ~ * {
        margin-left: 1em;
    }

    .form-group-checkbox-list input {
        width: 16px;
        height: 16px;
        outline: none;
        vertical-align: middle;
    }

    .contacts-attributes-table-list {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1em;
    }
    
    .email-body-explanation > section > * ~ * {
        margin-top: 0.5em;
    }

</style>

<?php

require "./contacts.php";

$all_contacts_groups = load_and_cache_all_contacts();

require "header.php";

?>

<section class="container" id="create-job-section">
    <h1>Criar novo email</h1>

    <div class="create-job-section-content">
        <form class="flowv content-box" action="jobs_post.php" method="post" accept-charset="utf-8">
            <div class="form-group form-group-block">
                <label for="titulo">Titulo de Identificação</label>
                <input name="titulo" required placeholder="email_001" pattern="[a-zA-Z0-9_]+">
                <p>Somente letras, números e sublinhados.</p>
                <p>Deve ser único</p>
            </div>

            <div class="form-group form-group-block">
                <label for="assunto">Assunto</label>
                <input name="assunto" required>
            </div>

            <div class="form-group form-group-block">
                <label for="email-template">Corpo do Email</label>
                <textarea name="email-template" rows="10" required placeholder="Prezad() (m=senhor)(f=senhora) {nome}, gostaria de falar sobre..."></textarea>
            </div>

            <div class="form-group flowv">
                <label for="grupos-contato[]">Para quem enviar</label><br>
                <div class="form-group-checkbox-list">
                <?php foreach ($all_contacts_groups as $group_name => $values): ?>
                   <div class="form-group-checkbox-list-item">
                       <label> <?= $group_name ?></label>
                       <input type="checkbox" name="grupos-contato[]" value="<?= $group_name ?>">
                   </div>
                <?php endforeach; ?>
                </div>
                <p class="warning">Não colocar mais de um tipo caso use atributos quem nem todos possuem</p>
            </div>

            <button type="submit">Salvar</button>
        </form>

        <div class="content-box flowv email-body-explanation">
            <h1>Atenção ao corpo do email</h1>

            <section>
                <h2>Texto em formato HTML</h2>

                <textarea class="example-html" readonly>
<i> Italico </i>
<b> Negrito </b>
                </textarea>
                
            </section>

            <section>
                <h2>Atributos disponíveis para cada tipo de contato</h2>
                <div class="contacts-attributes-table-list">
                    <?php foreach ($all_contacts_groups as $group_name => $values): ?>
                        <table class="body-description-table">
                            <caption> <?= $group_name ?></caption>
                            <tr> 
                                <th>Nome da Chave</th> 
                                <th>Exemplo de Valor</th> 
                            </tr>

                            <?php foreach ($values[array_key_first($values)] as $attribute_name => $value): ?>
                                <tr> 
                                    <td> {<?= $attribute_name ?>}</td> 
                                    <td> <?= $value ?> </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php endforeach; ?>
                </div>
            </section>
                
            <section>
                <h2> Campos Especiais </h2>
                <table class="body-description-table">
                    <tr>
                        <th>Chave</th>
                        <th>Será substituído por</th>
                    </tr>
                    <tr>
                        <td>(m=texto)</td>
                        <td>
                            O texto somente aparece se o destinatário for masculino
                        </td>
                    </tr>
                    <tr>
                        <td>(f=texto)</td>
                        <td>
                            O texto somente aparece se o destinatário for feminino
                        </td>
                    </tr>
                    <tr>
                        <td>()</td>
                        <td>
                            Se transforma em "o" para destinatário homem e "a" para mulher.
                        </td>
                    </tr>
                    <caption style="caption-side: top; margin-top: 0.5em; text-align: left;">Contato precisa ter campo {genero} definido para funcionar</caption>
                </table>
            </section>

            <section>
                <h2>Exemplo</h2>

                <textarea class="example-html" readonly rows=5>
Car() (f=senhora)(m=senhor) {nome}, um projeto... 
Muito <b> Importante </b> será votado

Atenciosamente <i> João </i> 
                </textarea>

                <p> Se transforma em: </p>

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

            </section>

        </div>
    </div>
</section>
