<?php
namespace TEND\Core;

class Database {
    private $host = 'localhost';
    private $db   = 'TENDAcademy';
    private $user = 'root';
    private $pass = '';

    public function getConnection() {
        try {
            $pdo = new \PDO("mysql:host={$this->host};dbname={$this->db};charset=utf8", $this->user, $this->pass);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (\PDOException $e) {
            die("Erro de conexão: " . $e->getMessage());
        }
    }
}