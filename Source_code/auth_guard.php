<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'BUSINESS') {
    http_response_code(403);
    die('Access denied.');
}
if (!isset($_SESSION['business_id'])) {
    http_response_code(403);
    die('Invalid business session.');
}