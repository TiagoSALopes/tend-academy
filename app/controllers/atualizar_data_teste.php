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

if (!is_array($input) || empty($input['data_teste']) || empty($input['disciplina'])) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Dados inválidos.']));
}

$data_teste = filter_var($input['data_teste'], FILTER_SANITIZE_SPECIAL_CHARS);
$disciplina = trim(filter_var($input['disciplina'], FILTER_SANITIZE_SPECIAL_CHARS));

try {
    $db = new TEND\Core\Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("UPDATE planos_estudo SET data_teste = ?, disciplina = ? WHERE user_id = ? AND status = 'aceite' AND (data_teste IS NULL OR data_teste = '') AND disciplina LIKE ?");
    $stmt->execute([$data_teste, $disciplina, $_SESSION['user_id'], '%'.$disciplina.'%']);

    echo json_encode(['success' => true, 'message' => 'Data de teste e disciplina atualizadas com sucesso.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar data: ' . $e->getMessage()]);
}
