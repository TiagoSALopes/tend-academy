<?php
session_start();
require_once '../../app/Core/Database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $db = new \TEND\Core\Database();
    $conn = $db->getConnection();

    $user_id = $_SESSION['user_id'];
    $data_teste = $_POST['data_teste'] ?? null;
    $dias_antes = (int)($_POST['dias_antes'] ?? 3);
    $disciplina_id = $_POST['disciplina_id'] ?? null;

    if (!$data_teste || !$disciplina_id) {
        die("Erro: Dados do teste em falta.");
    }

    // Opcional: Upload de ficheiro, mantido como estava
    if (isset($_FILES['horario_file']) && $_FILES['horario_file']['error'] === 0) {
        $fileName = time() . '_' . basename($_FILES['horario_file']['name']);
        move_uploaded_file($_FILES['horario_file']['tmp_name'], '../uploads/horarios/' . $fileName);
    }

    // Preparação das datas
    $data_inicio = new DateTime($data_teste);
    $data_inicio->modify("-$dias_antes days");
    $data_fim = new DateTime($data_teste);

    // ALTERAÇÃO AQUI: Incluímos o campo 'priority' na query
    // task_type 'preparacao_teste' sempre com prioridade 5 (Alta)
    $sql = "INSERT INTO study_tasks (user_id, discipline_id, task_type, scheduled_date, priority, is_test_day) 
            VALUES (?, ?, 'preparacao_teste', ?, 5, ?)";
    
    $stmt = $conn->prepare($sql);

    while ($data_inicio <= $data_fim) {
        $is_test = ($data_inicio->format('Y-m-d') == $data_fim->format('Y-m-d')) ? 1 : 0;
        
        $stmt->execute([
            $user_id, 
            $disciplina_id, 
            $data_inicio->format('Y-m-d'), 
            $is_test
        ]);
        
        $data_inicio->modify('+1 day');
    }

    // Redireciona para o dashboard
    header("Location: ../../public/dashboard.php?status=success");
    exit();
} else {
    header("Location: ../../public/rotina.php?error=invalid_request");
    exit();
}