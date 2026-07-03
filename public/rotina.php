<?php
require_once '../app/Includes/auth.php';

require_login();
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>TEND Academy | Criar Rotina</title>
    <style>
        /* Oculta secções que dependem de passos anteriores */
        #secao-turma, #secao-disciplinas, #secao-parametros, #modal-sugestao { display: none; }
    </style>
</head>
<body class="flex bg-gray-50 min-h-screen">

    <?php include '../app/Includes/sidebar.php'; ?>

    <main class="flex-1 p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Criar a Minha Rotina de Estudos</h1>

        <div id="secao-upload" class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            <h2 class="text-lg font-bold text-indigo-900 mb-4">1. Importar Horário</h2>
            <div id="drop-zone" class="border-4 border-dashed border-indigo-200 rounded-xl p-10 text-center cursor-pointer hover:border-indigo-500 transition">
                <p class="text-indigo-600 font-semibold">Arrasta o teu ficheiro (.xlsx) aqui</p>
                <input type="file" id="file-input" class="hidden" accept=".xlsx">
            </div>
        </div>

        <div id="secao-turma" class="bg-indigo-50 p-6 rounded-xl border border-indigo-200 mt-8">
            <h2 class="text-lg font-bold text-indigo-900 mb-4">2. Escolha a sua turma:</h2>
            <div id="lista-turmas" class="flex gap-4"></div>
        </div>

        <div id="secao-disciplinas" class="bg-white p-6 rounded-xl border border-gray-200 mt-8">
            <h2 class="text-lg font-bold text-indigo-900 mb-4">3. Rever lista de disciplinas e horários</h2>
            <p class="text-sm text-gray-600 mb-4">Revê a lista de disciplinas e o horário de aulas extraído antes de gerar o plano.</p>
            <div id="lista-disciplinas" class="space-y-3"></div>
            <div id="lista-horarios" class="mt-6 space-y-2"></div>
            <div class="flex flex-wrap gap-3 mt-4">
                <button type="button" onclick="adicionarDisciplinaCustomizada()" class="bg-indigo-900 text-white px-4 py-2 rounded hover:bg-indigo-800">Adicionar Disciplina</button>
                <button type="button" onclick="resetarDisciplinas()" class="bg-gray-200 text-gray-800 px-4 py-2 rounded hover:bg-gray-300">Reiniciar lista</button>
            </div>
        </div>

        <div id="secao-parametros" class="max-w-2xl bg-white p-8 rounded-xl shadow-sm border mt-8">
            <h2 class="text-lg font-bold text-gray-800 mb-6">4. Parâmetros do plano</h2>
            <form id="form-parametros" class="space-y-4">
                <input type="hidden" name="turma_selecionada" id="input-turma-selecionada">
                <label class="block text-sm font-medium text-gray-700">Período do plano</label>
                <select name="periodo" id="select-periodo" class="w-full border p-2 rounded">
                    <option value="semestre">Semestre completo</option>
                    <option value="trimestre">Trimestre completo</option>
                </select>
                <p class="text-sm text-gray-500">A data do teste será definida depois, na página do calendário.</p>
                <button type="button" onclick="gerarPlanoCustomizado()" class="w-full bg-indigo-900 text-white py-2 rounded">Gerar Plano</button>
            </form>
        </div>
    </main>

    <div id="modal-sugestao" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl p-8 max-w-2xl w-full">
            <h2 class="text-2xl font-bold mb-4 text-indigo-900">Sugestão da TEND Academy</h2>
            <textarea id="texto-sugestao" class="w-full h-64 p-4 border rounded mb-4 bg-gray-50"></textarea>
            <div class="flex flex-wrap gap-4">
                <button onclick="aceitarPlano()" class="bg-green-600 text-white px-6 py-2 rounded font-bold hover:bg-green-700">Aceitar e Ver Calendário</button>
                <button onclick="mostrarParametros()" class="bg-indigo-900 text-white px-6 py-2 rounded font-bold hover:bg-indigo-800">Editar/Ajustar</button>
                <button onclick="recusarPlano()" class="bg-gray-300 text-gray-800 px-6 py-2 rounded font-bold hover:bg-gray-400">Recusar</button>
            </div>
        </div>
    </div>

    <script>
        let mapaTurmas = {};
        let disciplinasOriginais = [];
        let disciplinasSelecionadas = [];
        let horariosTurma = [];
        let turmaAtual = '';

        function renderizarTurmas(turmas) {
            const container = document.getElementById('lista-turmas');
            container.innerHTML = '';
            turmas.forEach(turma => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'bg-white border border-indigo-300 text-indigo-900 px-4 py-2 rounded-lg shadow-sm hover:bg-indigo-50';
                button.textContent = turma;
                button.onclick = () => selecionarTurma(turma);
                container.appendChild(button);
            });
            document.getElementById('secao-turma').style.display = 'block';
        }

        function renderizarListaDisciplinas() {
            const container = document.getElementById('lista-disciplinas');
            container.innerHTML = '';

            if (disciplinasSelecionadas.length === 0) {
                container.innerHTML = '<p class="text-sm text-gray-500">Nenhuma disciplina disponível para esta turma. Adiciona pelo menos uma disciplina para continuar.</p>';
                return;
            }

            disciplinasSelecionadas.forEach((disciplina, index) => {
                const item = document.createElement('div');
                item.className = 'flex items-center justify-between bg-indigo-50 border border-indigo-100 rounded p-3';
                item.innerHTML = `
                    <span class="font-medium text-gray-800">${disciplina}</span>
                    <button type="button" class="text-red-600 hover:text-red-800" onclick="removerDisciplina(${index})">Eliminar</button>
                `;
                container.appendChild(item);
            });
        }

        function renderizarHorario() {
            const container = document.getElementById('lista-horarios');
            container.innerHTML = '';

            if (horariosTurma.length === 0) {
                container.innerHTML = '<p class="text-sm text-gray-500">Não foi possível extrair horários para esta turma.</p>';
                return;
            }

            const horariosFiltrados = horariosTurma.filter(h => disciplinasSelecionadas.includes(h.disciplina));
            if (horariosFiltrados.length === 0) {
                container.innerHTML = '<p class="text-sm text-gray-500">O horário atual não contém disciplinas selecionadas.</p>';
                return;
            }

            horariosFiltrados.forEach(horario => {
                const item = document.createElement('div');
                item.className = 'rounded-xl border border-indigo-100 bg-indigo-50 p-3 text-sm text-gray-700';
                item.innerHTML = `<strong>${horario.dia}</strong> ${horario.inicio} - ${horario.fim} · ${horario.disciplina}`;
                container.appendChild(item);
            });
        }

        document.getElementById('file-input').onchange = (e) => {
            const file = e.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('horario_file', file);

            fetch('../app/controllers/extrair_disciplinas.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'sucesso_detecao') {
                    mapaTurmas = data.mapa_turmas;
                    renderizarTurmas(data.turmas);
                } else {
                    console.error('Erro na API:', data.message);
                    alert('Erro: ' + (data.message || 'Formato inválido'));
                }
            })
            .catch(() => {
                alert('Falha ao processar o ficheiro. Tenta novamente.');
            });
        };

        document.getElementById('drop-zone').onclick = () => {
            document.getElementById('file-input').click();
        };

        function selecionarTurma(turma) {
            turmaAtual = turma;
            document.getElementById('input-turma-selecionada').value = turma;
            disciplinasOriginais = mapaTurmas[turma]?.disciplinas || [];
            horariosTurma = mapaTurmas[turma]?.horarios || [];
            disciplinasSelecionadas = [...disciplinasOriginais];
            renderizarListaDisciplinas();
            renderizarHorario();
            document.getElementById('secao-disciplinas').style.display = 'block';
            document.getElementById('secao-parametros').style.display = 'block';
        }

        function removerDisciplina(index) {
            disciplinasSelecionadas.splice(index, 1);
            renderizarListaDisciplinas();
            renderizarHorario();
        }

        function adicionarDisciplinaCustomizada() {
            const novaDisciplina = prompt('Insere o nome da disciplina a adicionar:');
            if (!novaDisciplina) {
                return;
            }
            if (!disciplinasSelecionadas.includes(novaDisciplina.trim())) {
                disciplinasSelecionadas.push(novaDisciplina.trim());
                renderizarListaDisciplinas();
            }
        }

        function resetarDisciplinas() {
            disciplinasSelecionadas = [...disciplinasOriginais];
            renderizarListaDisciplinas();
            renderizarHorario();
        }

        function gerarPlanoCustomizado() {
            const turma = turmaAtual;
            const periodo = document.getElementById('select-periodo').value;

            if (!turma || disciplinasSelecionadas.length === 0) {
                alert('Escolhe a turma e pelo menos uma disciplina antes de gerar o plano.');
                return;
            }

            document.getElementById('modal-sugestao').style.display = 'flex';
            document.getElementById('texto-sugestao').value = 'A gerar sugestão ajustada com IA...';

            const horariosSelecionados = horariosTurma.filter(horario => disciplinasSelecionadas.includes(horario.disciplina));
            fetch('../app/controllers/processar_rotina.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    turma_selecionada: turma,
                    periodo: periodo,
                    disciplinas: disciplinasSelecionadas,
                    horarios: horariosSelecionados
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('texto-sugestao').value = data.plano;
                } else {
                    alert(data.message);
                    document.getElementById('modal-sugestao').style.display = 'none';
                }
            })
            .catch(() => {
                alert('Erro ao comunicar com o serviço de IA.');
                document.getElementById('modal-sugestao').style.display = 'none';
            });
        }

        function aceitarPlano() {
            const dados = {
                conteudo: document.getElementById('texto-sugestao').value,
                disciplina: disciplinasSelecionadas.join(', '),
                disciplinas: disciplinasSelecionadas,
                data_teste: null,
                status: 'aceite'
            };

            fetch('../app/controllers/salvar_plano.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dados)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'calendario.php';
                } else {
                    alert(data.message || 'Erro ao salvar o plano.');
                }
            })
            .catch(() => {
                alert('Falha ao guardar o plano.');
            });
        }

        function recusarPlano() {
            document.getElementById('modal-sugestao').style.display = 'none';
            document.getElementById('secao-parametros').style.display = 'block';
        }

        function mostrarParametros() {
            document.getElementById('modal-sugestao').style.display = 'none';
            document.getElementById('secao-parametros').style.display = 'block';
            document.getElementById('secao-disciplinas').style.display = 'block';
        }
    </script>
</body>
</html>