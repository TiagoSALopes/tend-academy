<?php
require_once '../app/Includes/auth.php';

redirect_if_logged_in();

$errorMessage = '';
$successMessage = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'missing_fields':
            $errorMessage = 'Por favor, preenche todos os campos.';
            break;
        case 'invalid_email':
            $errorMessage = 'O email inserido não é válido.';
            break;
        case 'email_exists':
            $errorMessage = 'Já existe uma conta com esse email.';
            break;
        case 'username_exists':
            $errorMessage = 'Esse nome de utilizador já está em uso.';
            break;
        case 'user_exists':
            $errorMessage = 'Já existe uma conta com esse nome de utilizador ou email.';
            break;
        case 'database_error':
            $errorMessage = 'Erro interno. Tenta novamente mais tarde.';
            break;
        default:
            $errorMessage = 'Erro no registo. Tenta novamente.';
            break;
    }
}
if (isset($_GET['status']) && $_GET['status'] === 'sucesso') {
    $successMessage = 'Conta criada com sucesso! Já podes iniciar sessão.';
}
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>TEND Academy | Criar Conta</title>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="bg-white p-8 rounded-2xl shadow-lg w-full max-w-md">
        <h1 class="text-2xl font-bold text-center text-gray-800 mb-6">Criar Conta</h1>
        <?php if ($successMessage !== ''): ?>
            <div class="mb-4 rounded-lg border border-green-300 bg-green-50 p-4 text-sm text-green-700">
                <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php endif; ?>
        <?php if ($errorMessage !== ''): ?>
            <div class="mb-4 rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-700">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>
        
        <form action="/TEND-Academy/app/controllers/cadastro_action.php" method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Nome de Utilizador</label>
                <input type="text" name="username" required class="mt-1 w-full p-3 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" required class="mt-1 w-full p-3 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Palavra-passe</label>
                <input type="password" name="password" required class="mt-1 w-full p-3 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            
            <button type="submit" class="w-full bg-indigo-600 text-white py-3 rounded-lg font-bold hover:bg-indigo-700 transition">
                Registrar
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-600">
            Já tens conta? 
            <a href="login.php" class="text-indigo-600 font-bold hover:underline">Fazer login</a>
        </p>
    </div>
</body>
</html>