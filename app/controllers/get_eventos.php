<?php
header('Content-Type: application/json');
require_once '../Core/Database.php';
session_start();

// Verifica se o utilizador está logado
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit(json_encode(['error' => 'Não autorizado']));
}

try {
    $db = new TEND\Core\Database();
    $conn = $db->getConnection();

    // Busca todos os planos aceites do utilizador
    $stmt = $conn->prepare("SELECT * FROM planos_estudo WHERE user_id = ? AND status = 'aceite'");
    $stmt->execute([$_SESSION['user_id']]);
    $planos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $eventos = [];

    $dayNames = [
        'segunda' => 1,
        'segunda-feira' => 1,
        'terca' => 2,
        'terça' => 2,
        'terça-feira' => 2,
        'quarta' => 3,
        'quarta-feira' => 3,
        'quinta' => 4,
        'quinta-feira' => 4,
        'sexta' => 5,
        'sexta-feira' => 5,
        'sabado' => 6,
        'sábado' => 6,
        'domingo' => 7,
    ];

    $rangeStart = isset($_GET['start']) ? new DateTimeImmutable($_GET['start']) : new DateTimeImmutable('first day of this month');
    $rangeEnd = isset($_GET['end']) ? new DateTimeImmutable($_GET['end']) : $rangeStart->modify('+1 month');
    if ($rangeEnd < $rangeStart) {
        $tmp = $rangeStart;
        $rangeStart = $rangeEnd;
        $rangeEnd = $tmp;
    }

    $weekNamesEnglish = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday'
    ];

    foreach ($planos as $plano) {
        $conteudo = trim($plano['conteudo']);
        $linhas = preg_split('/\r\n|\r|\n/', $conteudo, -1, PREG_SPLIT_NO_EMPTY);
        $eventCount = 0;
        $weeklyEvents = [];

        foreach ($linhas as $linha) {
            $linha = trim($linha);
            if ($linha === '') {
                continue;
            }

            $regexes = [
                '/\b(' . implode('|', array_map('preg_quote', array_keys($dayNames))) . ')\b[^\d\n]{0,15}(\d{1,2}(?:[:h]\d{2}))\s*(?:-|–|—|a|até|to)\s*(\d{1,2}(?:[:h]\d{2}))(?:\s*[-\-–—:]\s*(.+))?/iu',
                '/\b(' . implode('|', array_map('preg_quote', array_keys($dayNames))) . ')\b[^\d\n]{0,15}(\d{1,2}(?:[:h]\d{2}))\s*(?:às|as|a|at)\s*(\d{1,2}(?:[:h]\d{2}))(?:\s*[-\-–—:]\s*(.+))?/iu',
                '/\b(' . implode('|', array_map('preg_quote', array_keys($dayNames))) . ')\b.*?de\s*(\d{1,2}(?:[:h]\d{2}))\s*(?:a|até|to)\s*(\d{1,2}(?:[:h]\d{2}))(?:\s*[-\-–—:]\s*(.+))?/iu',
                '/\b(' . implode('|', array_map('preg_quote', array_keys($dayNames))) . ')\b.*?(\d{1,2}(?:[:h]\d{2}))(?:\s*[-\-–—:]\s*(.+))?/iu'
            ];

            foreach ($regexes as $regex) {
                if (preg_match($regex, $linha, $matches)) {
                    $dia = mb_strtolower($matches[1], 'UTF-8');
                    $inicio = str_replace('h', ':', $matches[2]);
                    $fim = str_replace('h', ':', $matches[3]);
                    $descricao = trim($matches[4] ?? '');
                    $weekday = $dayNames[$dia] ?? null;

                    if ($weekday) {
                        $weeklyEvents[] = [
                            'weekday' => $weekday,
                            'english' => $weekNamesEnglish[$weekday],
                            'start' => $inicio,
                            'end' => $fim,
                            'descricao' => $descricao ?: 'Sessão de estudo',
                            'linha' => $linha
                        ];
                    }
                    break;
                }
            }
        }

        foreach ($weeklyEvents as $weeklyEvent) {
            $current = $rangeStart->modify('this ' . $weeklyEvent['english']);
            if ($current < $rangeStart) {
                $current = $current->modify('next ' . $weeklyEvent['english']);
            }

            while ($current <= $rangeEnd) {
                $dataEvento = $current->format('Y-m-d');
                $eventos[] = [
                    'id' => $plano['id'] . '_' . $weeklyEvent['weekday'] . '_' . $weeklyEvent['start'] . '_' . $eventCount,
                    'title' => mb_substr($weeklyEvent['descricao'], 0, 60),
                    'start' => $dataEvento . 'T' . $weeklyEvent['start'] . ':00',
                    'end' => $dataEvento . 'T' . $weeklyEvent['end'] . ':00',
                    'extendedProps' => [
                        'descricao' => $conteudo,
                        'disciplina' => $plano['disciplina'],
                        'linha' => $weeklyEvent['linha']
                    ],
                    'backgroundColor' => '#312e81',
                    'borderColor' => '#1e1b4b'
                ];
                $eventCount++;
                $current = $current->modify('next ' . $weeklyEvent['english']);
            }
        }

        if ($plano['data_teste']) {
            $testDate = DateTimeImmutable::createFromFormat('Y-m-d', $plano['data_teste']);
            if ($testDate !== false && $testDate >= $rangeStart && $testDate <= $rangeEnd) {
                $eventos[] = [
                    'id' => 'teste_' . $plano['id'],
                    'title' => 'Teste: ' . mb_substr($plano['disciplina'], 0, 50),
                    'start' => $plano['data_teste'] . 'T09:00:00',
                    'end' => $plano['data_teste'] . 'T10:00:00',
                    'extendedProps' => [
                        'descricao' => $conteudo,
                        'disciplina' => $plano['disciplina'],
                        'tipo' => 'teste'
                    ],
                    'backgroundColor' => '#0f766e',
                    'borderColor' => '#115e59'
                ];
            }
        }
    }

    echo json_encode($eventos, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao processar eventos: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}