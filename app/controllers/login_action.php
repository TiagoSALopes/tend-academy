<?php
require_once '../Includes/auth.php';
require_once '../Core/Database.php';

redirect_if_logged_in();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../public/login.php');
    exit();
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    header('Location: ../../public/login.php?error=missing_fields');
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../../public/login.php?error=invalid_email');
    exit();
}

$db = new \TEND\Core\Database();
$conn = $db->getConnection();

$stmt = $conn->prepare('SELECT id, password_hash FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password_hash'])) {
    $_SESSION['user_id'] = $user['id'];
    header('Location: ../../public/rotina.php');
    exit();
}

header('Location: ../../public/login.php?error=dados_incorretos');
exit();
