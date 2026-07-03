<?php
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

require_once '../Includes/auth.php';
require_once '../../vendor/autoload.php';
require_once '../Core/Database.php';

require_login();
require_post();

header('Content-Type: application/json');

try {
    if (!isset($_FILES['horario_file']) || $_FILES['horario_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Ficheiro não enviado ou erro no upload.");
    }

    $spreadsheet = IOFactory::load($_FILES['horario_file']['tmp_name']);
    /** @var \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet */
    $sheet = $spreadsheet->getActiveSheet();

    $dayNames = [
        'segunda' => 'Segunda',
        'segunda-feira' => 'Segunda',
        'seg' => 'Segunda',
        'terça' => 'Terça',
        'terca' => 'Terça',
        'terça-feira' => 'Terça',
        'ter' => 'Terça',
        'quarta' => 'Quarta',
        'quarta-feira' => 'Quarta',
        'qua' => 'Quarta',
        'quinta' => 'Quinta',
        'quinta-feira' => 'Quinta',
        'qui' => 'Quinta',
        'sexta' => 'Sexta',
        'sexta-feira' => 'Sexta',
        'sex' => 'Sexta',
        'sábado' => 'Sábado',
        'sabado' => 'Sábado',
        'sáb' => 'Sábado',
        'domingo' => 'Domingo',
        'dom' => 'Domingo',
    ];

    $resultados = [];
    $headers = [];
    $times = [];

    $maxRow = $sheet->getHighestRow();
    $maxColumn = Coordinate::columnIndexFromString($sheet->getHighestColumn());

    // Detect header row contendo dias da semana
    $headerRow = null;
    for ($row = 1; $row <= 6; $row++) {
        $countDays = 0;
        for ($col = 2; $col <= $maxColumn; $col++) {

        
            $coord = Coordinate::stringFromColumnIndex($col) . $row;
            $texto = mb_strtolower(trim((string)$sheet->getCell($coord)->getValue()), 'UTF-8');
            if ($texto === '') {
                continue;
            }
            foreach ($dayNames as $key => $value) {
                if (mb_strpos($texto, $key) !== false) {
                    $headers[$col] = $value;
                    $countDays++;
                    break;
                }
            }
        }
        if ($countDays >= 3) {
            $headerRow = $row;
            break;
        }
    }

    // Lê horários e disciplinas a partir de uma tabela típica
    if ($headerRow !== null) {
        $ultimoHorario = null;
        for ($row = $headerRow + 1; $row <= $maxRow; $row++) {
            $coord = Coordinate::stringFromColumnIndex(1) . $row;
            $timeValue = trim((string)$sheet->getCell($coord)->getValue());
            if ($timeValue !== '') {
                $timeValue = preg_replace('/\s+/', ' ', $timeValue);
                if (preg_match('/(\d{1,2}[:h]\d{2})(?:\s*[-–]\s*(\d{1,2}[:h]\d{2}))?/', $timeValue, $matches)) {
                    $ultimoHorario = [
                        'start' => str_replace('h', ':', $matches[1]),
                        'end' => isset($matches[2]) ? str_replace('h', ':', $matches[2]) : null,
                    ];
                }
            }
            if ($ultimoHorario !== null) {
                $times[$row] = $ultimoHorario;
            }

            for ($col = 2; $col <= $maxColumn; $col++) {
                $coord = Coordinate::stringFromColumnIndex($col) . $row;
                $celula = trim((string)$sheet->getCell($coord)->getValue());
                if ($celula === '') {
                    continue;
                }
                $dia = $headers[$col] ?? null;
                if (!$dia) {
                    continue;
                }

                $disciplinas = preg_split('/[\n,;]+/', $celula);
                foreach ($disciplinas as $item) {
                    $item = trim($item);
                    if ($item === '') {
                        continue;
                    }

                    $turma = 'Geral';
                    $disciplina = $item;
                    if (strpos($item, 'Meet - Aula:') !== false) {
                        $partes = explode(':', $item, 2);
                        if (isset($partes[1])) {
                            $info = explode('-', $partes[1]);
                            $turmaRaw = trim($info[0]);
                            $disciplina = isset($info[1]) ? trim($info[1]) : trim($info[0]);
                            $turmas = explode('|', $turmaRaw);
                            foreach ($turmas as $t) {
                                $t = trim($t);
                                if ($t !== '') {
                                    $turma = $t;
                                }
                            }
                        }
                    }

                    $resultados[$turma]['disciplinas'][] = $disciplina;
                    $resultados[$turma]['horarios'][] = [
                        'disciplina' => $disciplina,
                        'dia' => $dia,
                        'inicio' => $times[$row]['start'] ?? '08:00',
                        'fim' => $times[$row]['end'] ?? '10:00',
                        'raw' => $celula,
                    ];
                }
            }
        }
    }

    // fallback antigo se não houver cabeçalho de dias
    if (empty($resultados)) {
        foreach ($sheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            foreach ($cellIterator as $cell) {
                $value = trim((string)$cell->getValue());
                if (strpos($value, 'Meet - Aula:') !== false) {
                    $partes = explode(':', $value, 2);
                    if (isset($partes[1])) {
                        $info = explode('-', $partes[1]);
                        $turmaRaw = trim($info[0]);
                        $disciplina = isset($info[1]) ? trim($info[1]) : 'Disciplina Desconhecida';
                        $turmas = explode('|', $turmaRaw);
                        foreach ($turmas as $t) {
                            $t = trim($t);
                            if (!empty($t)) {
                                $resultados[$t]['disciplinas'][] = $disciplina;
                                $resultados[$t]['horarios'][] = [
                                    'disciplina' => $disciplina,
                                    'dia' => 'Segunda',
                                    'inicio' => '08:00',
                                    'fim' => '10:00',
                                    'raw' => $value,
                                ];
                            }
                        }
                    }
                }
            }
        }
    }

    if (empty($resultados)) {
        throw new Exception("Não foram encontradas disciplinas no formato esperado no ficheiro.");
    }

    foreach ($resultados as $turma => $dados) {
        $disciplinasUnicas = [];
        foreach ($dados['disciplinas'] as $disciplina) {
            $key = mb_strtolower(trim($disciplina), 'UTF-8');
            if ($key === '') {
                continue;
            }
            if (!isset($disciplinasUnicas[$key])) {
                $disciplinasUnicas[$key] = trim($disciplina);
            }
        }
        $resultados[$turma]['disciplinas'] = array_values($disciplinasUnicas);
    }

    echo json_encode([
        'status' => 'sucesso_detecao',
        'turmas' => array_keys($resultados),
        'mapa_turmas' => $resultados
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => $e->getMessage()
    ]);
}
exit();