
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