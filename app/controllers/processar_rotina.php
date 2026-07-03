<?php
session_start();
require_once '../../vendor/autoload.php';
require_once '../Core/Database.php';

// Retornar JSON sempre
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit();
}

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!is_array($input)) {
    parse_str($rawInput, $parsed);
    $input = $parsed;
}

$turma = filter_var($input['turma_selecionada'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
$periodo = filter_var($input['periodo'] ?? 'semestre', FILTER_SANITIZE_SPECIAL_CHARS);
$disciplinas = is_array($input['disciplinas']) ? array_filter($input['disciplinas'], fn($d) => !empty(trim($d))) : [];
$disciplinas = array_map(fn($d) => trim(filter_var($d, FILTER_SANITIZE_SPECIAL_CHARS)), $disciplinas);
$horarios = is_array($input['horarios']) ? $input['horarios'] : [];

if (empty($turma)) {
    echo json_encode(['success' => false, 'message' => 'A turma não foi identificada.']);
    exit();
}

if (empty($disciplinas)) {
    echo json_encode(['success' => false, 'message' => 'É necessário ter pelo menos uma disciplina selecionada para gerar o plano.']);
    exit();
}

$periodoTexto = $periodo === 'trimestre'
    ? 'um trimestre completo (cerca de 12 semanas)'
    : 'um semestre completo (cerca de 16 semanas)';

$listaDisciplinas = 'As disciplinas a incluir são: ' . implode(', ', $disciplinas) . '. ';
$disciplinaBase = count($disciplinas) === 1 ? $disciplinas[0] : 'as disciplinas ' . implode(', ', $disciplinas);

$horariosTexto = '';
if (!empty($horarios)) {
    $linhasHorario = [];
    foreach ($horarios as $horario) {
        if (empty($horario['dia']) || empty($horario['inicio'])) {
            continue;
        }
        $fim = $horario['fim'] ?: date('H:i', strtotime($horario['inicio'] . ' +1 hour'));
        $linhasHorario[] = trim($horario['dia']) . ' ' . trim($horario['inicio']) . '-' . trim($fim) . ' - ' . trim($horario['disciplina']);
    }
    if (!empty($linhasHorario)) {
        $horariosTexto = 'O horário de aulas fixas da turma é:
' . implode("\n", $linhasHorario) . "\n";
    }
}

$prompt = "Crie um plano de estudos detalhado para $disciplinaBase (Turma $turma), cobrindo $periodoTexto. " .
          "$listaDisciplinas" .
          $horariosTexto .
          "Organize o plano de estudos para os horários livres, evitando sobrepor com as aulas já marcadas, e use blocos de 1 a 2 horas. " .
          "Inclua revisão e preparação ativa para todas as disciplinas mesmo antes da atribuição da data do teste, distribuindo o trabalho de forma equilibrada ao longo de cada semana. " .
          "Não espere pela data do teste; gere o plano normal de estudos com blocos semanais regulares. " .
          "Responda apenas em português e utilize o formato: Segunda 08:00-10:00 - Estudo de Matemática. " .
          "Não inclua a data do teste no plano; a data do teste será inserida depois da geração.";

function chamarIA($servico, $prompt) {
    $tentativas = 0;
    while ($tentativas < 2) {
        if ($servico === 'groq') {
            $url = "https://api.groq.com/openai/v1/chat/completions";
            $headers = ['Authorization: Bearer ' . $_ENV['GROQ_API_KEY'], 'Content-Type: application/json'];
            $payload = ['model' => 'llama-3.3-70b-versatile', 'messages' => [['role' => 'user', 'content' => $prompt]]];
        } else {
            $url = "https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent?key=" . $_ENV['GEMINI_API_KEY'];
            $headers = ['Content-Type: application/json'];
            $payload = ['contents' => [['parts' => [['text' => $prompt]]]]];
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return ($servico === 'groq') ? $data['choices'][0]['message']['content'] : $data['candidates'][0]['content']['parts'][0]['text'];
        }
        $tentativas++;
        usleep(500000);
    }
    return null;
}


$plano = chamarIA('groq', $prompt) ?? chamarIA('gemini', $prompt);

if (!$plano) {
    echo json_encode(['success' => false, 'message' => 'Sistema indisponível.']);
} else {
    // Retornamos um JSON com o conteúdo e a data, para o JS do rotina.php tratar
    echo json_encode([
        'success' => true,
        'plano' => $plano,
        'disciplina' => $disciplinaBase
    ]);
}