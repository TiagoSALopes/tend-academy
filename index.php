<?php
session_start();

// Verifica se o utilizador já está logado
if (isset($_SESSION['user_id'])) {
    // Se estiver logado, manda para o dashboard
    header("Location: public/dashboard.php");
} else {
    // Se não estiver, manda para o login
    header("Location: public/login.php");
}
exit();