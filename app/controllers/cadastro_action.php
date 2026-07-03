<?php
require_once '../Includes/auth.php';
require_once '../Core/Database.php';

redirect_if_logged_in();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../public/cadastro.php');
    exit();
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $email === '' || $password === '') {
    header('Location: ../../public/cadastro.php?error=missing_fields');
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../../public/cadastro.php?error=invalid_email');
    exit();
}

try {
    $db = new \TEND\Core\Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare('SELECT username, email FROM users WHERE username = ? OR email = ? LIMIT 1');
    $stmt->execute([$username, $email]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        if (mb_strtolower($existing['email'], 'UTF-8') === mb_strtolower($email, 'UTF-8')) {
            header('Location: ../../public/cadastro.php?error=email_exists');
            exit();
        }
        if (mb_strtolower($existing['username'], 'UTF-8') === mb_strtolower($username, 'UTF-8')) {
            header('Location: ../../public/cadastro.php?error=username_exists');
            exit();
        }
        header('Location: ../../public/cadastro.php?error=user_exists');
        exit();
    }

    $password_hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
    $stmt->execute([$username, $email, $password_hashed]);

    header('Location: ../../public/login.php?status=sucesso');
    exit();
} catch (Exception $e) {
    header('Location: ../../public/cadastro.php?error=database_error');
    exit();
}
