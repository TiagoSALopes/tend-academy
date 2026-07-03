<?php
header('Content-Type: application/json');
require_once '../Core/Database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit(json_encode(['error' => 'Não autorizado']));
}

try {
    $db = new TEND\Core\Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT id, disciplina, conteudo FROM planos_estudo WHERE user_id = ? AND status = 'aceite' AND (data_teste IS NULL OR data_teste = '')");
    $stmt->execute([$_SESSION['user_id']]);
    $planos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $resultados = array_map(function ($plano) {
        $linhas = preg_split('/\r\n|\r|\n/', trim($plano['conteudo']), -1, PREG_SPLIT_NO_EMPTY);
        $resumo = !empty($linhas) ? mb_substr(trim($linhas[0]), 0, 80) : 'Plano sem resumo';
        return [
            'id' => $plano['id'],
            'disciplina' => $plano['disciplina'],
            'resumo' => $resumo
        ];
    }, $planos);

    echo json_encode($resultados);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao carregar planos sem data: ' . $e->getMessage()]);
}
