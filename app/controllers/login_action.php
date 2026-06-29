<?php
session_start();
require_once '../core/Database.php'; // Verifica se o 'c' de core está minúsculo ou maiúsculo

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $db = new \TEND\Core\Database();
    $conn = $db->getConnection();

    // Selecionamos o id e a coluna password_hash
    $stmt = $conn->prepare("SELECT id, password_hash FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificamos a password contra a coluna password_hash
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        header("Location: ../../public/rotina.php"); 
    } else {
        header("Location: ../../public/login.php?error=dados_incorretos");
    }
}