<?php
// public/dashboard.php
require_once '../app/Includes/auth.php';
require_once '../app/Core/Database.php';

require_login();

use TEND\Core\Database;
$db = new Database();
$conn = $db->getConnection();

// 1. Query para total de horas
$sqlHoras = "SELECT SUM(focus_duration_minutes) as total_minutos FROM study_sessions WHERE end_time IS NOT NULL";
$dados = $conn->query($sqlHoras)->fetch(PDO::FETCH_ASSOC);
$totalHoras = round(($dados['total_minutos'] ?? 0) / 60, 1);

// 2. Query para buscar tarefas ordenadas por prioridade (Inteligente)
$sqlTarefas = "SELECT t.*, d.nome AS disciplina_nome 
               FROM study_tasks t
               JOIN disciplines d ON t.discipline_id = d.id
               WHERE t.user_id = ? AND t.scheduled_date >= CURDATE()
               ORDER BY t.priority DESC, t.scheduled_date ASC";
$stmt = $conn->prepare($sqlTarefas);
$stmt->execute([$_SESSION['user_id']]);
$tarefas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.21/index.global.min.js'></script>
    <title>TEND Academy | Dashboard</title>
</head>
<body class="flex bg-gray-50 min-h-screen">
    <?php include '../app/Includes/sidebar.php'; ?>

    <main class="flex-1 p-4 lg:p-8 w-full">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Bem-vindo, Estudante!</h1>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            
            <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h2 class="text-gray-700 font-semibold mb-4">Calendário de Estudos</h2>
                <div id='calendar'></div>
            </div>

            <div class="space-y-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h2 class="text-gray-500 text-xs uppercase font-bold tracking-wider">Total de Estudo</h2>
                    <p class="text-4xl font-extrabold text-indigo-600 mt-2"><?php echo $totalHoras; ?>h</p>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h2 class="text-gray-700 font-bold mb-4">Plano de Foco</h2>
                    <?php if (empty($tarefas)): ?>
                        <p class="text-sm text-gray-500">Nenhuma tarefa prioritária. Aproveita para descansar!</p>
                    <?php else: foreach ($tarefas as $t): ?>
                        <div class="p-3 mb-2 rounded-lg border-l-4 <?php echo ($t['priority'] == 5) ? 'border-red-500 bg-red-50' : 'border-indigo-500 bg-gray-50'; ?>">
                            <p class="font-bold text-sm text-gray-800"><?php echo htmlspecialchars($t['disciplina_nome']); ?></p>
                            <p class="text-xs text-gray-500"><?php echo $t['scheduled_date']; ?></p>
                            <?php if ($t['priority'] == 5): ?>
                                <span class="text-[10px] font-bold text-red-600 uppercase">Foco no Teste</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Lógica do Calendário (já existente)
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'pt-br',
                events: [
                    <?php foreach ($tarefas as $t): ?>
                    { title: '<?= htmlspecialchars($t['disciplina_nome']) ?>', start: '<?= $t['scheduled_date'] ?>', color: '<?= ($t['priority'] == 5) ? '#ef4444' : '#6366f1' ?>' },
                    <?php endforeach; ?>
                ]
            });
            calendar.render();
        });
    </script>
</body>
</html>