<?php
// Inicia a sessão para podermos usar variáveis globais se necessário
session_start();

// O require_once aponta para a pasta Core. 
// Como estamos em 'app/auth/', subimos um nível para 'app/' com '../'
require_once '../Core/Database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recolha de dados do formulário
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validação básica
    if (empty($username) || empty($email) || empty($password)) {
        die("Por favor, preenche todos os campos.");
    }

    // Encriptação da palavra-passe para segurança
    $password_hashed = password_hash($password, PASSWORD_DEFAULT);

    try {
        // Conexão à base de dados
        $db = new \TEND\Core\Database();
        $conn = $db->getConnection();

        // Inserção na tabela 'users'
        $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $password_hashed]);

        // Redireciona para o login com uma mensagem de sucesso
        header("Location: ../../public/login.php?status=sucesso");
        exit();
    } catch (Exception $e) {
        // Exibe o erro se algo falhar (como email duplicado ou tabela inexistente)
        die("Erro ao registar: " . $e->getMessage());
    }
} else {
    // Se tentarem aceder a este ficheiro diretamente pela URL, redireciona para o cadastro
    header("Location: ../../public/cadastro.php");
    exit();
}