<?php
require_once 'includes/functions.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

header('Location: index.php');
exit();
?>
