<?php
session_start();
require_once 'db.php';

// Preveri, če je uporabnik prijavljen
function is_logged_in() {
    return isset($_SESSION['user']);
}

// Preveri, če ima uporabnik admin vlogo
function is_admin() {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
}

// Preusmeri neprijavljene uporabnike na prijavo
function redirect_if_not_logged_in() {
    if (!is_logged_in()) {
        header("Location: index.php");
        exit;
    }
}

// Generiraj CSRF žeton za zaščito obrazcev
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Preveri veljavnost CSRF žetona
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>