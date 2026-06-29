<?php
session_start();
require_once '../app/Core/Database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$db = new \TEND\Core\Database();
$conn = $db->getConnection();
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>TEND Academy | Criar Rotina</title>
</head>
<body class="flex bg-gray-50 min-h-screen">

    <?php include '../app/Includes/sidebar.php'; ?>

    <main class="flex-1 p-4 lg:p-8 w-full">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Criar a Minha Rotina de Estudos</h1>

        <div id="secao-upload" class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm mb-8">
            <h2 class="text-lg font-bold text-indigo-900 mb-4">1. Importar Horário</h2>
            <div id="drop-zone" class="border-4 border-dashed border-indigo-200 rounded-xl p-10 text-center hover:border-indigo-500 transition cursor-pointer bg-gray-50">
                <p class="text-indigo-600 font-semibold">Arrasta o teu ficheiro (.xlsx) aqui ou clica para selecionar</p>
                <input type="file" id="file-input" class="hidden" accept=".xlsx">
            </div>
        </div>

        <div id="secao-turma" class="hidden bg-indigo-50 p-6 rounded-xl border border-indigo-200 mb-8">
            <h2 class="text-lg font-bold text-indigo-900 mb-4">2. Escolha a sua turma:</h2>
            <div id="lista-turmas" class="flex gap-4"></div>
        </div>

        <div id="tabela-container" class="hidden bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-8">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Disciplinas Extraídas</h2>
            <table class="w-full text-left">
                <thead><tr class="border-b text-indigo-900"><th class="py-2">Disciplina</th></tr></thead>
                <tbody id="disciplinas-body"></tbody>
            </table>
        </div>
        
        <div id="secao-parametros" class="hidden max-w-2xl bg-white p-8 rounded-xl shadow-sm border border-gray-100">
            <h2 class="text-lg font-bold text-gray-800 mb-6">3. Definir Parâmetros da IA</h2>
            <form action="../app/controllers/processar_rotina.php" method="POST" class="space-y-6">
                <input type="hidden" name="turma_selecionada" id="input-turma-selecionada">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Data do Próximo Teste</label>
                        <input type="date" name="data_teste" class="mt-1 block w-full border border-gray-300 rounded-md p-2" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Dias de preparação</label>
                        <input type="number" name="dias_antes" class="mt-1 block w-full border border-gray-300 rounded-md p-2" value="3" min="1" max="14">
                    </div>
                </div>
                <button type="submit" class="w-full bg-indigo-900 text-white py-3 rounded-lg font-bold hover:bg-indigo-800 transition">
                    Gerar Plano de Estudo Inteligente
                </button>
            </form>
        </div>
    </main>

    <script>
        const fileInput = document.getElementById('file-input');
        const dropZone = document.getElementById('drop-zone');
        
        dropZone.onclick = () => fileInput.click();

        fileInput.onchange = (e) => {
            if(!e.target.files[0]) return;
            const formData = new FormData();
            formData.append('horario_file', e.target.files[0]);
            
            fetch('../app/controllers/extrair_disciplinas.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                
                if(data.status === 'sucesso_detecao') {
                    window.mapaDisciplinas = data.mapa_disciplinas || {}; // Garante que é um objeto
                    const lista = document.getElementById('lista-turmas');
                    lista.innerHTML = '';
                    
                    data.turmas.forEach(t => {
                        lista.innerHTML += `
                            <button type="button" onclick="mostrarDisciplinas('${t}')" 
                            class="px-5 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 font-bold shadow">
                                ${t}
                            </button>`;
                    });
                    document.getElementById('secao-turma').classList.remove('hidden');
                } else {
                    alert("Erro do servidor: " + (data.message || 'Erro desconhecido'));
                }

            });
        };

        function mostrarDisciplinas(turmaSelecionada) {
            // Guarda a turma no input hidden para o formulário
            document.getElementById('input-turma-selecionada').value = turmaSelecionada;
            
            const body = document.getElementById('disciplinas-body');
            body.innerHTML = '';
            
            const listaDisciplinas = window.mapaDisciplinas[turmaSelecionada] || [];
            
            listaDisciplinas.forEach(d => {
                body.innerHTML += `<tr class="border-b"><td class="py-3 font-medium text-gray-800">${d}</td></tr>`;
            });
            
            document.getElementById('tabela-container').classList.remove('hidden');
            document.getElementById('secao-parametros').classList.remove('hidden');
            document.getElementById('tabela-container').scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>