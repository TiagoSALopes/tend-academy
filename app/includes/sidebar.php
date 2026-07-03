<?php
// Obtém o nome do ficheiro atual para comparar
$pagina_atual = basename($_SERVER['PHP_SELF']);

// Função auxiliar para aplicar as classes CSS se a página estiver ativa
function getSidebarClass(string $pagina_alvo, string $pagina_atual): string {
    $base_classes = "block py-2 px-4 rounded transition-all duration-300 border-l-4";
    if ($pagina_atual == $pagina_alvo) {
        // Estilo para o item ativo (com destaque na borda e fundo)
        return $base_classes . " bg-indigo-700 border-white text-white font-bold hover:scale-105";
    } else {
        // Estilo para itens inativos
        return $base_classes . " border-transparent hover:bg-indigo-800 hover:border-indigo-400 text-indigo-200 hover:text-white";
    }
}
?>

<aside id="sidebar" class="fixed lg:static w-64 bg-indigo-900 h-screen text-white p-5 flex flex-col hidden lg:flex z-50">
    <h2 class="text-2xl font-bold mb-10 border-b border-indigo-700 pb-4">TEND Academy</h2>
    
    <nav class="flex-1">
        <ul class="space-y-4">
            <li>
                <a href="/TEND-Academy/public/dashboard.php" class="<?php echo getSidebarClass('dashboard.php', $pagina_atual); ?>">
                    Dashboard
                </a>
            </li>
            <li>
                <a href="#" class="<?php echo getSidebarClass('sessoes.php', $pagina_atual); ?>">
                    Sessões de Foco
                </a>
            </li>
            <li>
                <a href="#" class="<?php echo getSidebarClass('quizzes.php', $pagina_atual); ?>">
                    Quizzes
                </a>
            </li>
            <li>
                <a href="/TEND-Academy/public/rotina.php" class="<?php echo getSidebarClass('rotina.php', $pagina_atual); ?>">
                    Criar minha rotina
                </a>
            </li>
            <li>
                <a href="/TEND-Academy/public/logout.php" class="block py-2 px-4 rounded transition-all duration-300 border-l-4 border-transparent hover:bg-indigo-800 hover:border-indigo-400 text-indigo-200 hover:text-white">
                    Sair
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="mt-auto border-t border-indigo-700 pt-4">
        <p class="text-xs text-indigo-300">Logado como: Estudante</p>
    </div>
</aside>