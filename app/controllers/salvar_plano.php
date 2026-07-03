<?php
/**
 * TEND Academy - Controlador de Persistência
 * Recebe o plano (possivelmente editado pelo utilizador) e guarda na DB.
 */
session_start();
require_once '../Core/Database.php';

// 1. Verificações de segurança
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Método inválido.']));
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Sessão expirada.']));
}

// 2. Leitura dos dados JSON enviados pelo fetch do JavaScript
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!$input) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Dados de entrada inválidos.']));
}

// 3. Validação dos dados necessários
$user_id    = $_SESSION['user_id'];
$disciplina = filter_var($input['disciplina'], FILTER_SANITIZE_SPECIAL_CHARS);
$conteudo   = $input['conteudo']; 
$status     = 'aceite'; // O utilizador clicou em "Aceitar"
$data_teste = filter_var($input['data_teste'], FILTER_SANITIZE_SPECIAL_CHARS);

try {
    // 4. Conexão e Gravação
    $db = new \TEND\Core\Database();
    $conn = $db->getConnection();

    $sql = "INSERT INTO planos_estudo (user_id, disciplina, conteudo, status, data_teste) 
            VALUES (:user_id, :disciplina, :conteudo, :status, :data_teste)";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':user_id'    => $user_id,
        ':disciplina' => $disciplina,
        ':conteudo'   => $conteudo,
        ':status'     => $status,
        ':data_teste' => $data_teste
    ]);

    // Resposta de sucesso para o JavaScript
    echo json_encode([
        'success' => true, 
        'message' => 'Plano gravado com sucesso!'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erro interno ao salvar: ' . $e->getMessage()
    ]);
}