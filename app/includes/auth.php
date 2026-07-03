<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!defined('APP_BASE')) {
    define('APP_BASE', '/TEND-Academy');
}

function is_json_request(): bool
{
    $accept = strtolower($_SERVER['HTTP_ACCEPT'] ?? '');
    $xhr = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');

    return $xhr === 'xmlhttprequest' || str_contains($accept, 'application/json');
}

function require_login(): void
{
    if (!isset($_SESSION['user_id'])) {
        if (is_json_request()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Não autorizado.']);
            exit();
        }

        header('Location: ' . APP_BASE . '/public/login.php');
        exit();
    }
}

function redirect_if_logged_in(): void
{
    if (isset($_SESSION['user_id'])) {
        header('Location: ' . APP_BASE . '/public/dashboard.php');
        exit();
    }
}

function require_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit(json_encode(['success' => false, 'message' => 'Método inválido.']));
    }
}
