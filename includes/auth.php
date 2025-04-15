<?php
session_start();
require_once 'db.php';

function is_logged_in() {
    return isset($_SESSION['user']);
}

function is_admin() {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
}

function redirect_if_not_logged_in() {
    if (!is_logged_in()) {
        header("Location: index.php");
        exit;
    }
}
?>
