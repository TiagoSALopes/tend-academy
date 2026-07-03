<?php
header('Content-Type: application/json');
require_once '../Core/Database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Método inválido.']));
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Sessão expirada.']));
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!is_array($input) || empty($input['id']) || empty($input['data_teste'])) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Dados inválidos.']));
}

$id = filter_var($input['id'], FILTER_SANITIZE_NUMBER_INT);
$data_teste = filter_var($input['data_teste'], FILTER_SANITIZE_SPECIAL_CHARS);

try {
    $db = new TEND\Core\Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("UPDATE planos_estudo SET data_teste = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$data_teste, $id, $_SESSION['user_id']]);

    echo json_encode(['success' => true, 'message' => 'Data de teste atualizada com sucesso.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar data: ' . $e->getMessage()]);
}
