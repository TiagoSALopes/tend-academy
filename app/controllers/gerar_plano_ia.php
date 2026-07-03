<?php
require_once '../Includes/auth.php';
require_once '../Core/Database.php';

require_login();
require_post();

$db = new \TEND\Core\Database();
$conn = $db->getConnection();

$user_id = $_SESSION['user_id'];
$nome_plano = $_POST['nome_plano'];
$horas_dia = (int)$_POST['horas_dia'];

// 1. Obter disciplinas do utilizador ordenadas por dificuldade (a IA foca nas mais difíceis primeiro)
$stmt = $conn->prepare("SELECT id FROM disciplines WHERE user_id = ? ORDER BY dificuldade DESC");
$stmt->execute([$user_id]);
$disciplinas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Inserir o plano principal
$stmt = $conn->prepare("INSERT INTO planos_estudo (user_id, nome_plano) VALUES (?, ?)");
$stmt->execute([$user_id, $nome_plano]);
$plano_id = $conn->lastInsertId();

// 3. Gerar "IA" simples: Distribuir disciplinas nos dias da semana
$dias = ['Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira'];
$i = 0;

foreach ($disciplinas as $d) {
    // Insere cada disciplina num bloco de estudo (simplificação: 1 disciplina por dia)
    $stmt = $conn->prepare("INSERT INTO detalhes_plano (plano_id, disciplina_id, dia_semana) VALUES (?, ?, ?)");
    $stmt->execute([$plano_id, $d['id'], $dias[$i % 5]]);
    $i++;
}

header("Location: ../../public/dashboard.php?success=plano_gerado");