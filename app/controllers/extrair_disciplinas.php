<?php
session_start();
require '../../vendor/autoload.php';
require_once '../Core/Database.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json'); // Garante que o navegador sabe que é JSON

try {
    if (!isset($_FILES['horario_file'])) throw new Exception("Ficheiro não enviado.");

    $spreadsheet = IOFactory::load($_FILES['horario_file']['tmp_name']);
    $sheet = $spreadsheet->getActiveSheet();
    $resultados = [];

    foreach ($sheet->getRowIterator() as $row) {
        foreach ($row->getCellIterator() as $cell) {
            $value = (string)$cell->getValue();
            if (strpos($value, 'Meet - Aula:') !== false) {
                $partes = explode(':', $value);
                if (isset($partes[1])) {
                    $info = explode('-', $partes[1]);
                    $turmaRaw = trim($info[0]);
                    $disciplina = isset($info[1]) ? trim($info[1]) : 'Disciplina Desconhecida';
                    
                    $turmas = explode('|', $turmaRaw);
                    foreach ($turmas as $t) {
                        $t = trim($t);
                        if (!empty($t)) {
                            $resultados[$t][] = $disciplina;
                        }
                    }
                }
            }
        }
    }

    // Limpeza de duplicados antes de enviar
    foreach ($resultados as $turma => $disciplinas) {
        $resultados[$turma] = array_values(array_unique($disciplinas));
    }

    echo json_encode([
        'status' => 'sucesso_detecao',
        'turmas' => array_keys($resultados),
        'mapa_disciplinas' => $resultados
    ]);

} catch (Exception $e) {
    // Se algo falhar, devolvemos JSON também para o JS conseguir ler a mensagem de erro
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit();