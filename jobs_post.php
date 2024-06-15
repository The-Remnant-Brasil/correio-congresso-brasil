<?php

require "jobs.php";

if (
    !empty($_POST['titulo']) &&
    !empty($_POST['email-template']) &&
    !empty($_POST['assunto']) &&
    !empty($_POST['grupos-contato'])
) {
    try {
        $job = create_email_job(
            $_POST['titulo'],
            $_POST['email-template'],
            $_POST['assunto'],
            $_POST['grupos-contato'],
        );
        save_email_job($job);

        header("location: /jobs_index.php");
    } catch (Exception $e) {
        $error = "Não pode criar email: {$e->getMessage()}.";
    }
} else {
    $error = "Não pode criar email: Requisição falha.";
}

require "header.php";

?>

<main>
    <?php if (isset($message)): ?>
        <div> <?= $message ?> </div>
    <?php else: ?>
        <div class="error"> <?= $error ?></div>
        <a href="create_job.php"> Tentar novamente </a>
    <?php endif; ?>
</main>

<style>
    @import url("./styles.css");
</style>
