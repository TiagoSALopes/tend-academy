<?php
session_start();
require_once '../../vendor/autoload.php';
require_once '../Core/Database.php';

// 1. Segurança: Verificar sessão e método
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Acesso negado.");
}

// 2. Carregar variáveis de ambiente (Chave da API)
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// 3. Sanitizar e Validar Entradas
$turma = filter_input(INPUT_POST, 'turma_selecionada', FILTER_SANITIZE_SPECIAL_CHARS);
$data_teste = filter_input(INPUT_POST, 'data_teste', FILTER_SANITIZE_SPECIAL_CHARS);
$dias = filter_input(INPUT_POST, 'dias_antes', FILTER_VALIDATE_INT);

if (!$turma || !$data_teste || !$dias) {
    die("Dados inválidos. Por favor, preencha todos os campos corretamente.");
}

// 4. Buscar disciplinas na BD
try {
    $db = new \TEND\Core\Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT nome_disciplina FROM disciplines WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $disciplinas = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($disciplinas)) {
        die("Nenhuma disciplina encontrada. Importe o seu horário primeiro.");
    }
    $lista_disciplinas = implode(", ", $disciplinas);
} catch (Exception $e) {
    die("Erro na base de dados: " . $e->getMessage());
}

// 5. Preparar Prompt para a IA
$prompt = "Cria um plano de estudos detalhado e motivador para um estudante da turma $turma. 
O teste é no dia $data_teste e o estudante tem $dias dias de preparação. 
As disciplinas a estudar são: $lista_disciplinas. 
Organiza o plano por dia, sugerindo quais disciplinas estudar e tópicos de foco, mantendo um equilíbrio saudável.";

// 6. Chamada à API da OpenAI (Usando cURL)
$apiKey = $_ENV['OPENAI_API_KEY'];

$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'model' => 'gpt-3.5-turbo',
    'messages' => [['role' => 'user', 'content' => $prompt]],
    'temperature' => 0.7
]));

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    die("Erro ao comunicar com a IA: " . $error);
}

$data = json_decode($response, true);
$plano = $data['choices'][0]['message']['content'] ?? "Erro ao gerar plano. Tente novamente mais tarde.";

// 7. Exibir o resultado de forma limpa
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <title>TEND Academy | O Teu Plano</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen p-4 lg:p-12">
    <div class="max-w-4xl mx-auto bg-white p-8 rounded-xl shadow-sm border border-gray-100">
        <h1 class="text-3xl font-bold text-indigo-900 mb-6">Plano de Estudo Inteligente</h1>
        <div class="prose prose-indigo max-w-none text-gray-700 leading-relaxed whitespace-pre-line">
            <?php echo nl2br(htmlspecialchars($plano)); ?>
        </div>
        <div class="mt-8 flex gap-4">
            <button onclick="window.print()" class="bg-gray-200 text-gray-800 px-6 py-2 rounded-lg font-bold hover:bg-gray-300">Imprimir Plano</button>
            <a href="../../public/rotina.php" class="bg-indigo-900 text-white px-6 py-2 rounded-lg font-bold hover:bg-indigo-800">Voltar</a>
        </div>
    </div>
</body>
</html>