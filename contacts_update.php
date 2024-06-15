<style>
    @import url("./styles.css");
</style>

<?php

require "header.php";
require "api.php";
require "contacts.php";

try {
    $Senadores = load_senadores_contacts();
    $Deputados = load_deputados_contacts();

    if (add_contacts(compact('Senadores', 'Deputados')) !== 2) {
        $error = "Falha em atualizar contatos";
    }
} catch (Exception $e) {
    $error = "Falha em atualizar contatos: {$e->getMessage()}";
}

?>

<main class="container">
    <?php if (isset($error)): ?>
        <div class="error"> <?= $error ?></div>
    <?php else: ?>
       Lista Atualizada com sucesso 
    <?php endif; ?>
</main>
