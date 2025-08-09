<?php
require_once '../includes/functions.php';

header('Content-Type: application/json');

$response = ['valid' => false];

if (isLoggedIn()) {
    checkSessionTimeout();
    $response['valid'] = true;
} else {
    session_unset();
    session_destroy();
}

echo json_encode($response);
?>
