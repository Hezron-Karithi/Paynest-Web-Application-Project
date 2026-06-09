<?php
require_once __DIR__ . '/bootstrap.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: admin_login.php');
    exit;
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    http_response_code(403);
    die("Access denied. Admin privileges required.");
}
session_regenerate_id(true);