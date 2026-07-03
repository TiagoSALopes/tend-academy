
<?php
require_once '../app/Includes/auth.php';

redirect_if_logged_in();

$errorMessage = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'missing_fields':
            $errorMessage = 'Por favor, preenche todos os campos.';
            break;
        case 'invalid_email':
            $errorMessage = 'O email inserido não é válido.';
            break;
        case 'dados_incorretos':
            $errorMessage = 'Email ou palavra-passe incorretos.';
            break;
        default:
            $errorMessage = 'Erro no login. Tenta novamente.';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>TEND Academy | Login</title>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="bg-white p-8 rounded-2xl shadow-lg w-full max-w-md">
        <h1 class="text-2xl font-bold text-center text-gray-800 mb-6">Bem-vindo de volta!</h1>
        <?php if ($errorMessage !== ''): ?>
            <div class="mb-4 rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-700">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>
        
        <form action="/TEND-Academy/app/controllers/login_action.php" method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" required class="mt-1 w-full p-3 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Palavra-passe</label>
                <input type="password" name="password" required class="mt-1 w-full p-3 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            
            <button type="submit" class="w-full bg-indigo-900 text-white py-3 rounded-lg font-bold hover:bg-indigo-800 transition">
                Entrar
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-600">
            Ainda não tens conta? 
            <a href="cadastro.php" class="text-indigo-600 font-bold hover:underline">Criar conta agora</a>
        </p>
    </div>
</body>
</html>