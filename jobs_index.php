<style>
    @import url("./styles.css");

    .confirm-job-send-dialog {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        margin: 0 auto;
        width: 50%;
        padding: 1em;
    }

    .mail-job-card-contact-list-items li {
        margin-left: 2em;
    }

    .emails-list {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1em;
    }

</style>


<?php

require "jobs.php";

try {
    $all_jobs = get_all_email_jobs();
} catch (Exception $e) {
    $error = "Não pode achar jobs: {$e->getMessage()}";
}

function mail_job_card(MailJob $job, bool $already_sent = false) {
?>
    <div class="content-box mail-job-card flowv">

        <div> Nome: <?= htmlspecialchars($job->getName()) ?></div>
        <div> Assunto: <?= htmlspecialchars($job->getSubject()) ?></div>

        <div class="mail-job-card-contact-list">
            Enviando para:
            <ul class="mail-job-card-contact-list-items">
                <?php foreach ($job->getContactsGroups() as $group): ?>
                    <li> <?= htmlspecialchars($group) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

            <form accept-charset="utf-8" action="jobs_preview.php" method="get">
            <?php if (!$already_sent): ?>
                <button type="submit">Prosseguir para Envio</button>
            <?php endif; ?>
                <button type="submit" class="button-secondary" formaction="jobs_delete.php">Deletar</button>
                <input type="hidden" name="titulo" value="<?= $job->getName() ?>">
            </form>

    </div>
<?php
}

require "./header.php";

?>

<section class="container flowv emails-list">
    <?php if (isset($error)): ?>
        <div class="error"> <?= htmlspecialchars($error) ?></div> 
    <?php else: ?>
        <dialog class="confirm-job-send-dialog" >
            <form class="flowv" action="jobs_send.php" method="get" accept-charset="utf-8">
                <div class="form-group form-group-block">
                    <label for="email">Seu email</label>
                    <input type="email" name="email" required placeholder="exemplo@gmail.com">
                </div>

                <div class="form-group form-group-block">
                    <label for="senha">Sua Senha</label>
                    <input type="password" name="senha" required placeholder="Minha Senha">
                </div>

                <input name="titulo" type="hidden">
                <button type="submit">Confirmar</button>
            </form>
        </dialog>
 
        <div class="flowv">
            <h1>Emails para Enviar</h1>

            <?php
                foreach ($all_jobs['unfinished'] as $job) {
                    mail_job_card($job);
                } 
            ?>
        </div>

        <div class="flowv">
            <h1>Emails Finalizados</h1>
            <?php
                foreach ($all_jobs['finished'] as $job) {
                    mail_job_card($job, true);
                } 
            ?>
        </div>
    <?php endif; ?> 
</section>

<script>
    const buttons = document.querySelectorAll('[data-action=job-send-email-button]')
    /** @type {HTMLDialogElement} */
    const send_dialog = document.querySelector('.confirm-job-send-dialog')
    const send_dialog_form = send_dialog.querySelector('form')

    buttons.forEach(button => {
        button.addEventListener('click', () => {
            /** @type {HTMLInputElement} */ 
            const job_name_field = send_dialog_form.querySelector('[name=titulo]')
            job_name_field.value = button.getAttribute('data-job-name')
            send_dialog.showModal()
        })
    })
</script>
