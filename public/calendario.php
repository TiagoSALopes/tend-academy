<?php
require_once '../app/Includes/auth.php';

require_login();
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TEND Academy | Calendário de Estudos</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
          initialView: 'timeGridWeek', // Visão semanal com horas
          headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek'
          },
          locale: 'pt',
          // O FullCalendar consome o feed do nosso ficheiro get_eventos.php
          events: '../app/controllers/get_eventos.php',
          
          // Ao clicar num evento, abre o plano detalhado (que guardámos em extendedProps)
          eventClick: function(info) {
            alert("Disciplina: " + info.event.title + "\n\nPlano de Estudo:\n" + info.event.extendedProps.descricao);
          },
          
          // Otimização visual para melhor leitura
          slotMinTime: "07:00:00", // Começa o dia às 07:00
          slotMaxTime: "22:00:00", // Termina às 22:00
          allDaySlot: false        // Esconde o slot de "dia inteiro"
        });
        calendar.render();
        window.tendCalendar = calendar;
        carregarPlanosSemData();
      });

      function carregarPlanosSemData() {
        fetch('../app/controllers/get_planos_sem_data.php')
          .then(res => res.json())
          .then(data => {
            const wrapper = document.getElementById('planos-sem-data');
            if (!data.length) {
              wrapper.innerHTML = '<p class="text-sm text-gray-600">Todos os planos aceites já têm data de teste atribuída.</p>';
              return;
            }

            wrapper.innerHTML = data.map(plano => `
              <div class="bg-gray-50 border border-gray-200 rounded-xl p-5 mb-4">
                <div class="flex flex-wrap justify-between items-center gap-4">
                  <div>
                    <h3 class="font-semibold text-gray-800">${plano.disciplina || 'Plano de estudos'}</h3>
                    <p class="text-sm text-gray-600 mt-1">${plano.resumo}</p>
                  </div>
                  <div class="flex flex-wrap gap-3 items-center">
                    <input type="date" id="data-teste-${plano.id}" class="border border-gray-300 rounded px-3 py-2" />
                    <button onclick="salvarDataTeste(${plano.id})" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Salvar Data</button>
                  </div>
                </div>
              </div>
            `).join('');
          })
          .catch(() => {
            document.getElementById('planos-sem-data').innerHTML = '<p class="text-sm text-red-600">Não foi possível carregar os planos sem data. Atualiza a página.</p>';
          });
      }

      function salvarDataTeste(planoId) {
        const input = document.getElementById(`data-teste-${planoId}`);
        const dataTeste = input.value;
        if (!dataTeste) {
          alert('Escolhe a data do teste antes de guardar.');
          return;
        }

        fetch('../app/controllers/atualizar_data_teste.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: planoId, data_teste: dataTeste })
        })
        .then(res => res.json())
        .then(response => {
          if (response.success) {
            alert('Data do teste guardada com sucesso.');
            window.tendCalendar.refetchEvents();
            carregarPlanosSemData();
          } else {
            alert(response.message || 'Erro ao guardar a data do teste.');
          }
        })
        .catch(() => {
          alert('Erro ao comunicar com o servidor.');
        });
      }
    </script>
</head>
<body class="bg-gray-50 flex min-h-screen">

    <?php include '../app/Includes/sidebar.php'; ?>

    <main class="flex-1 p-8">
        <div class="max-w-6xl mx-auto bg-white p-6 rounded-xl shadow-lg border">
            <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-indigo-900">O Meu Calendário de Estudos</h1>
                    <p class="text-sm text-gray-600">Aqui estão os planos aceites. Para começar um novo semestre/trimestre, importa um novo horário.</p>
                </div>
                <div class="flex gap-3">
                    <a href="rotina.php" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-indigo-700">+ Nova Rotina</a>
                    <a href="rotina.php" class="bg-white border border-indigo-600 text-indigo-600 px-4 py-2 rounded-lg text-sm font-bold hover:bg-indigo-50">Novo Horário</a>
                </div>
            </div>
            
            <div id='calendar' class="h-[750px]"></div>
            <div id="planos-sem-data" class="mt-8"></div>
        </div>
    </main>

</body>
</html>