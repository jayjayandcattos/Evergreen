<?php
    session_start();
    session_destroy();
        header("Location: viewing.php");
        exit;
?>
