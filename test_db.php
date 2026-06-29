<?php
require_once 'app/Core/Database.php';

// Como você está a usar Namespace no Database.php, use o caminho completo da classe:
$db = new \TEND\Core\Database(); 
$conn = $db->getConnection();
echo "Sucesso!";