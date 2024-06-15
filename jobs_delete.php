<?php

require "jobs.php";

$name = $_GET['titulo'];

delete_email_job($name);

header('location: jobs_index.php');

?>
