<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/saas_systems/db.php';
error_reporting(0);
ini_set('display_errors', 0);
