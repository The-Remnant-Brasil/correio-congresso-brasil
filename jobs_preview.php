<style>
    @import url("./styles.css");
</style>

<?php

require "jobs.php";

if (
    !empty($_GET['titulo'])
) {
    $job_title = $_GET['titulo'];

    try {
        $job = get_email_job($job_title);
        $previews = $job->getEmailsPreview('nome');
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
        
} else {
    $error = "Não pode achar email: Requisição mal formada";
}

function mail_job_preview(array $preview): void {
?>
    <section class="flowv">
        <h1>Emails que serão enviados</h1>
        <?php foreach ($preview as $key => $body): ?>
            <div>
                <p>Email para <?= htmlspecialchars($key) ?>:</p>
                <pre class="content-box"><?= htmlspecialchars($body) ?></pre>
            </div> 
        <?php endforeach; ?>
    </section>
<?php
}

require "header.php";

?>

<main class="container flowv">
    <?php if (isset($error)): ?>
        <div class="error"> <?= $error ?></div> 
    <?php else: ?>
        <form class="flowv" action="jobs_send.php" method="get" accept-charset="utf-8">
            <div class="form-group form-group-block">
                <label for="email">Seu email</label>
                <input type="email" name="email" required placeholder="exemplo@gmail.com">
            </div>

            <div class="form-group form-group-block">
                <label for="email">Seu Nome</label>
                <input name="nome" required placeholder="Nome">
            </div>

            <div class="form-group form-group-block">
                <label for="senha">Sua Senha</label>
                <input type="password" name="senha" required placeholder="Minha Senha">
            </div>

            <input name="titulo" type="hidden" value="<?= $job_title ?>">
            <button type="submit">Confirmar Envio</button>
        </form>
        <?php mail_job_preview($previews) ?>
    <?php endif; ?>
</main>
