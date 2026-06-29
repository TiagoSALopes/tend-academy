<?php
require '../../vendor/autoload.php';
require_once '../Core/Database.php';
session_start();

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['turma'])) {
    $turmaSelecionada = $_POST['turma'];
    $tempPath = '../uploads/temp/' . $_SESSION['user_id'] . '_horario.xlsx';
    
    $spreadsheet = IOFactory::load($tempPath);
    $sheet = $spreadsheet->getActiveSheet();
    $db = (new \TEND\Core\Database())->getConnection();

    foreach ($sheet->getCellCollection() as $cellCoord) {
        $cell = $sheet->getCell($cellCoord);
        if ($cell->hasHyperlink() && strpos($cell->getValue(), $turmaSelecionada) !== false) {
            $link = $cell->getHyperlink()->getUrl();
            $nome = trim(explode('-', $cell->getValue())[1] ?? $cell->getValue());

            $stmt = $db->prepare("INSERT INTO disciplines (user_id, nome, link_aula) VALUES (?, ?, ?) 
                                  ON DUPLICATE KEY UPDATE link_aula = ?");
            $stmt->execute([$_SESSION['user_id'], $nome, $link, $link]);
        }
    }
    echo json_encode(['status' => 'success']);
}